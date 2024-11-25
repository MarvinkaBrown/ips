<?php
/**
 * @brief		Text Parser
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Jun 2013
 */

namespace IPS\Text;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXpath;
use Exception;
use HTMLPurifier;
use HTMLPurifier_AttrDef_CSS_Border;
use HTMLPurifier_AttrDef_CSS_Composite;
use HTMLPurifier_AttrDef_CSS_Length;
use HTMLPurifier_AttrDef_CSS_Multiple;
use HTMLPurifier_AttrDef_CSS_Percentage;
use HTMLPurifier_AttrDef_Enum;
use HTMLPurifier_AttrDef_HTML_Bool;
use HTMLPurifier_AttrDef_URI;
use HTMLPurifier_Config;
use HTMLPurifier_CSSDefinition;
use HTMLPurifier_DefinitionCacheFactory;
use HTMLPurifier_HTMLDefinition;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\core\Profanity;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Http\Url;
use IPS\Http\Url\Internal;
use IPS\IPS;
use IPS\Lang;
use IPS\Log;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\DOMDocument;
use OutOfRangeException;
use UnderflowException;
use UnexpectedValueException;
use function chr;
use function count;
use function defined;
use function function_exists;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function json_decode;
use function str_replace;
use function substr;
use const IPS\DEFAULT_REQUEST_TIMEOUT;
use const IPS\ROOT_PATH;
use const PHP_URL_SCHEME;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Text Parser
 */
class Parser
{
	/**
	 * Get the regex pattern used to match emojis in text
	 *
	 * @return string
	 */
	public static function getEmojiRegex() : string
	{
		static $output = null;
		if ( !is_string( $output ) OR !$output )
		{
			$cacheKey = 'IPS_TEXT_PARSER_REGEX';
			try
			{
				$output = Cache::i()->getWithExpire( $cacheKey, true );
			}
			catch ( OutOfRangeException ) {}

			if ( !$output )
			{
				$output = "";
				$data = json_decode( file_get_contents( ROOT_PATH . '/applications/core/data/emojiRegex.json' ), true );
				if ( is_array( $data ) and isset( $data['emojiRegex'] ) and is_string( $data['emojiRegex'] ) )
				{
					$output = $data["emojiRegex"];
				}

				Cache::i()->storeWithExpire( $cacheKey, $output, ( new DateTime() )->add( new DateInterval( 'P1D' ) ) );
			}
		}

		return $output;
	}

	/**
	 * @brief	Regex for detecting email addresses
	 */
	const EMAIL_REGEX = '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,9}';

	/* !Parser: Bootstrap */

	/**
	 * @brief	Attachment IDs
	 */
	protected mixed $attachIds = NULL;
		
	/**
	 * @brief	Attachment Lang
	 */
	protected ?int $attachIdsLang = NULL;
		
	/**
	 * @brief	Rows from core_attachments_map containing attachments which belong to the content being edited - as they are found by the parser, they will be removed so we are left with attachments that have been removed
	 */
	public array $existingAttachments = array();
	
	/**
	 * @brief	Attachment IDs
	 */
	public array $mappedAttachments = array();
	
	/**
	 * @brief	If parsing attachments, the member posting
	 */
	protected ?Member $member = NULL;
	
	/**
	 * @brief	If parsing attachments, the Editor area we're parsing in. e.g. "core_Signatures".
	 */
	protected string|bool|null $area = NULL;
	
	/**
	 * @brief	Loose Profanity Filters
	 */
	protected array $looseProfanity = array();
	
	/**
	 * @brief	Exact Profanity Filters
	 */
	protected array $exactProfanity = array();
	
	/**
	 * @brief	Case-sensitive Acronyms
	 */
	public array $caseSensitiveAcronyms = array();
	
	/**
	 * @brief	Case-insensitive Acronyms
	 */
	public array $caseInsensitiveAcronyms = array();
	
	/**
	 * @brief	If cleaning HTML, the HTMLPurifier object
	 */
	protected ?HTMLPurifier $htmlPurifier = NULL;

	/**
	 * @brief Save on queries and fetch the alt label would just the once
	 */
	protected ?string $_altLabelWord = NULL;
				
	/**
	 * Constructor
	 *
	 * @param mixed $attachIds			array of ID numbers to idenfity content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, an MD5 hash used to claim attachments after saving.
	 * @param	Member|null		$member				The member posting, NULL will use currently logged in member.
	 * @param bool|string $area				If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @param bool $filterProfanity	Remove profanity?
	 * @param callback|null $htmlPurifierConfig	A function which will be passed the HTMLPurifier_Config object to customise it - see example
	 * @param bool $parseAcronyms		Parse acronyms?
	 * @param	?int $attachIdsLang		Language ID number if this Editor is part of a Translatable field.
	 * @return	void
	 */
	public function __construct( mixed $attachIds=NULL, Member $member=NULL, bool|string $area=FALSE, bool $filterProfanity=TRUE, callable $htmlPurifierConfig=NULL, bool $parseAcronyms=TRUE, int $attachIdsLang=NULL )
	{
		/*  Set the Member */
		$this->member = $member ?: Member::loggedIn();

		/* Set the member and area */
		if ( $attachIds )
		{
			$this->area = $area;
		}
		
		/* Get attachments */
		$this->attachIds = $attachIds;
		$this->attachIdsLang = $attachIdsLang;
		if( $attachIds !== NULL )
		{
			$where = array( array( 'location_key=?', $area ) );
			if ( is_array( $attachIds ) )
			{
				$i = 1;
				foreach ( $attachIds as $id )
				{
					$where[] = array("id{$i}=?", $id);
					$i++;
				}
			}
			elseif ( is_string( $attachIds ) )
			{
				$where[] = array( 'temp=?', $attachIds );
			}

			$this->existingAttachments = iterator_to_array( Db::i()->select( '*', 'core_attachments_map', $where )->setKeyField( 'attachment_id' ) );
			$this->mappedAttachments = array_keys( $this->existingAttachments );
		}

		/* Get profanity filters */
		if ( $filterProfanity )
		{
			foreach( Profanity::getProfanity() AS $profanity )
			{
				if ( $profanity->action == 'swap' )
				{
					if ( $profanity->m_exact )
					{
						$this->exactProfanity[ $profanity->type ] = $profanity->swop;
					}
					else
					{
						$this->looseProfanity[ $profanity->type ] = $profanity->swop;
					}
				}
			}
		}
		
		/* Get HTMLPurifier Configuration */
		if ( !function_exists('idn_to_ascii') )
		{
			IPS::$PSR0Namespaces['TrueBV'] = ROOT_PATH . "/system/3rd_party/php-punycode";
			require_once ROOT_PATH . "/system/3rd_party/php-punycode/polyfill.php";
		}
		require_once ROOT_PATH . "/system/3rd_party/HTMLPurifier/HTMLPurifier.auto.php";
		$this->htmlPurifier = new HTMLPurifier( $this->_htmlPurifierConfiguration( $htmlPurifierConfig ) );
				
		/* Get acronyms */
		if ( $parseAcronyms )
		{
			$this->caseSensitiveAcronyms = iterator_to_array( Db::i()->select( array( 'a_short', 'a_long', 'a_type' ), 'core_acronyms', array( 'a_casesensitive=1' ) )->setKeyField( 'a_short' ) );
			
			$this->caseInsensitiveAcronyms = array();
			foreach ( Db::i()->select( array( 'a_short', 'a_long', 'a_type' ), 'core_acronyms', array( 'a_casesensitive=0' ) )->setKeyField( 'a_short' ) as $k => $v )
			{
				$this->caseInsensitiveAcronyms[ mb_strtolower( $k ) ] = $v;
			} 
		}
	}

	/**
	 * Parse
	 *
	 * @param string $value	HTML to parse
	 * @return	string
	 */
	public function parse( string $value ): string
	{		
		/* Clean HTML */
		$value = $this->purify( $value );

		/* Profanity, etc. */
		if ( $value )
		{
			$value = $this->_parseContent( $value );
		}
						
//		/* Clean HTML */
//		$value = $this->purify( $value ); // this is almost certainly redundant now. After the first use of purify, there shouldn't be anything this will remove

		/* Replace any {fileStore.whatever} tags with <fileStore.whatever> */
		$value = static::replaceFileStoreTags( $value );

		/* HTML Purifier converts <br> to <br></br>. In browsers, this gets rendered as <br><br> */
		$value = str_replace( "</br>", "", $value );

		/* Return */
		return $value;
	}

	/**
	 * Parse
	 *
	 * @param string $value	HTML to run through HTML purifier
	 * @return	string
	 */
	public function purify( string $value ): string
	{
		/* Clean HTML */
		if ( $value and $this->htmlPurifier )
		{
			$value = $this->htmlPurifier->purify( $value );
		}

		return $value;
	}

	/**
	 * Returns the blank image used as a placeholder to facilitate lazy loading
	 *
	 * @return	string
	 */
	public static function blankImage(): string
	{
		return (string) Url::internal( "applications/core/interface/js/spacer.png", 'none', NULL, array(), Url::PROTOCOL_RELATIVE );
	}

	/**
	 * Returns a url to a blank page used as a placeholder to facilitate lazy loading in frames
	 *
	 * @return	string
	 */
	public static function blankPage(): string
	{
		return (string) Url::internal( "applications/core/interface/index.html", 'none', NULL, array(), Url::PROTOCOL_RELATIVE );
	}

	/**
	 * Remove image proxy
	 *
	 * @param string $content		HTML to parse
	 * @param bool $useProxyUrl	Use the proxied image URL (the locally stored image) instead of the original URL
	 * @return	string
	 */
	public static function removeImageProxy( string $content, bool $useProxyUrl=FALSE ): string
	{
		$source = new DOMDocument( '1.0', 'UTF-8' );
		$source->loadHTML( DOMDocument::wrapHtml( $content ) );

		/* Get document images */
		$contentImages = $source->getElementsByTagName( 'img' );

		foreach( $contentImages as $element )
		{
			static::_removeImageProxy($element, $useProxyUrl);
		}

		/* Get DOMDocument output */
		$content = DOMParser::getDocumentBodyContents( $source );

		/* Replace file storage tags */
		$content = preg_replace( '/&lt;fileStore\.([\d\w\_]+?)&gt;/i', '<fileStore.$1>', $content );

		/* DOMDocument::saveHTML will encode the base_url brackets, so we need to make sure it's in the expected format. */
		return str_replace( '&lt;___base_url___&gt;', '<___base_url___>', $content );
	}

	/**
	 * Parse content to remove old lazy loading
	 *
	 * @param   string $content	HTML to parse
	 * @return	string
	 */
	public static function parseLazyLoad( string $content ): string
	{
		/* Lazy loading applies to images, iframes and videos - return now if we don't detect any with basic string checks */
		if( mb_strpos( $content, '<img' ) === FALSE AND mb_strpos( $content, '<iframe' ) === FALSE AND mb_strpos( $content, '<source' ) === FALSE AND mb_strpos( $content, '<video' ) === FALSE )
		{
			return $content;
		}

		/* We can only remove legacy lazy-loading now */
		return static::replaceLegacyLazyLoad( $content );
	}

	/**
	 * Remove lazy-loading from content
	 *
	 * @param string $content	HTML to parse
	 * @return	string
	 */
	public static function replaceLegacyLazyLoad( string $content ): string
	{		
		/* Load source */
		$source = new DOMDocument( '1.0', 'UTF-8' );
		$source->loadHTML( DOMDocument::wrapHtml( $content ) );
		
		/* Swap data-src for src */
		$contentImages = $source->getElementsByTagName( 'img' );
		foreach( $contentImages as $element )
		{
			if ( $element->hasAttribute('data-src') )
			{
				$element->setAttribute( 'src', $element->getAttribute('data-src') );
				$element->removeAttribute( 'data-src' );
			}

			$element->setAttribute( 'loading', 'lazy' );

			/* Convert ratio into height */
			if( !$element->hasAttribute( 'height' ) AND $element->hasAttribute( 'width' ) AND $element->hasAttribute( 'data-ratio' ) )
			{
				$element->setAttribute( 'height', ( (int) $element->getAttribute( 'width' ) / 100 ) * (int) $element->getAttribute( 'data-ratio' ) );
				$element->removeAttribute( 'data-ratio' );
			}
		}

		$contentVideos = $source->getElementsByTagName( 'video' );
		foreach( $contentVideos as $element )
		{
			if ( $element->hasAttribute('data-video-embed') )
			{
				$element->setAttribute( 'data-controller', 'core.global.core.embeddedvideo' );
				$element->removeAttribute( 'data-video-embed' );
			}
			$element->setAttribute( 'preload', 'metadata' );
		}
		
		/* Swap data-video-src for src */
		$contentVideos = $source->getElementsByTagName( 'source' );
		foreach( $contentVideos as $element )
		{
			if ( $element->parentNode->tagName === 'video' and $element->hasAttribute('data-video-src') )
			{
				$element->setAttribute( 'src', $element->getAttribute('data-video-src') );
				$element->removeAttribute( 'data-video-src' );
			}
		}

		/* Fix Audio Tags */
		$contentAudio = $source->getElementsByTagName( 'audio' );
		foreach( $contentAudio as $element )
		{
			if ( $element->hasAttribute('data-audio-embed') )
			{
				$element->setAttribute( 'data-controller', 'core.global.core.embeddedaudio' );
				$element->removeAttribute( 'data-audio-embed' );
			}
			$element->setAttribute( 'preload', 'metadata' );
		}

		/* Swap data-embed-src for src */
		$contentEmbeds = $source->getElementsByTagName( 'iframe' );
		foreach( $contentEmbeds as $element )
		{
			if ( $element->hasAttribute('data-embed-src') )
			{
				$element->setAttribute( 'src', $element->getAttribute('data-embed-src') );
				$element->removeAttribute( 'data-embed-src' );
			}

			$element->setAttribute( 'loading', 'lazy' );
		}
		
		/* Get DOMDocument output */
		$content = DOMParser::getDocumentBodyContents( $source );

		/* Replace file storage tags */
		$content = preg_replace( '/&lt;fileStore\.([\d\w\_]+?)&gt;/i', '<fileStore.$1>', $content );

		/* DOMDocument::saveHTML will encode the base_url brackets, so we need to make sure it's in the expected format. */
		return str_replace( '&lt;___base_url___&gt;', '<___base_url___>', $content );
	}
	
	/**
	 * Replace {fileStore.xxx} with <fileStore.xxx> 
	 *
	 * @param string $value	HTML to parse
	 * @return	string
	 */
	public static function replaceFileStoreTags( string $value ): string
	{		
		/* Some tags have multiple __base_url__ replacements, so we have to replace this in a safe way ensuring we only match inside A, IMG, IFRAME and VIDEO tags to prevent tampering */
		preg_match_all( '#<(img|a|iframe|video|audio|source|blockquote)([^>]+?)%7B___base_url___%7D([^>]+?)>#i', $value, $matches, PREG_SET_ORDER );
		foreach( $matches as $val )
		{
			$changed = $val[0];
			
			/* srcset can have multiple urls in it */
			preg_match( '#srcset=(\'|")([^\'"]+?)(\1)#i', $changed, $srcsetMatches );
			
			if ( isset( $srcsetMatches[2] ) )
			{
				if ( mb_stristr( $srcsetMatches[2], '%7B___base_url___%7D' ) )
				{
					$changed = str_replace( $srcsetMatches[2], str_replace( '%7B___base_url___%7D', '<___base_url___>', $srcsetMatches[2] ), $changed );
				}
			}
			
			$changed = preg_replace( '#(href|src|data\-fileid|data\-ipshover\-target|cite)=(\'|")%7B___base_url___%7D/#i', '\1=\2<___base_url___>/', $changed );
			if ( $changed != $val[0] )
			{
				$value = str_replace( $val[0], $changed, $value );
			}
		}
		
		/* Replace {fileStore.xxx} with <fileStore.xxx> */
		$value = preg_replace( '#(srcset|src|href|cite)=(\'|")(%7B|\{)fileStore\.([\d\w\_]+?)(%7D|\})/#i', '\1=\2<fileStore.\4>/', $value );

		/* Return */
		return $value;
	}
	
	/* !Parser: HTMLPurifier */
	
	/**
	 * Get HTML Purifier Configuration
	 *
	 * @param callback|null $callback	A function which will be passed the HTMLPurifier_Config object to customise it
	 * @return	HTMLPurifier_Config
	 */
	protected function _htmlPurifierConfiguration( callable $callback = NULL ): HTMLPurifier_Config
	{
		/* Start with a base configruation */
		$config = HTMLPurifier_Config::createDefault();

		/* HTMLPurifier by default caches data to disk which we cannot allow. Register our custom
			cache definiton to use \IPS\Data\Store instead */
		$definitionCacheFactory	= HTMLPurifier_DefinitionCacheFactory::instance();
		$definitionCacheFactory->register( 'IPSCache', "HtmlPurifierDefinitionCache" );
		require_once( ROOT_PATH . '/system/Text/HtmlPurifierDefinitionCache.php' );
		$config->set( 'Cache.DefinitionImpl', 'IPSCache' );
		
		/* Allow iFrames from services we allow. We limit this to a whitelist because to allow any iframe would
			open us to phishing and other such security issues */
		$config->set( 'HTML.SafeIframe', true );
		$config->set( 'URI.SafeIframeRegexp', static::safeIframeRegexp() );
		$config->set( 'Output.Newline', "\n" );
		
		/* Set allowed CSS classes.  We limit this to a whitelist because to allow any iframe would open
			us to phishing (for example, someone posts something which, by using our CSS classes, looks like a
			login form), and general annoyances */
		$config->set( 'Attr.AllowedClasses', static::getAllowedCssClasses() );

		/* Increase default image width */
		$config->set( 'CSS.MaxImgLength', "4800px" );
		$config->set( 'HTML.MaxImgLength', "4800" );

		/* Callback */
		if ( $callback )
		{
			$callback( $config );
		}
		
		/* HTML Definition */
		$htmlDefinition = $config->getHTMLDefinition( TRUE );
		$this->_htmlPurifierModifyHtmlDefinition( $htmlDefinition );
		
		/* CSS Definition */
		$cssDefinition = $config->getCSSDefinition();
		$this->_htmlPurifierModifyCssDefinition( $cssDefinition, $config );

		$uri = $config->getDefinition('URI');
		$uri->addFilter( new HtmlPurifierHttpsImages(), $config );

		/* Return */
		return $config;
	}
	
	/**
	 * Customize HTML Purifier HTML Definition
	 *
	 * @param	HTMLPurifier_HTMLDefinition	$def	The definition
	 * @return	void
	 */
	protected function _htmlPurifierModifyHtmlDefinition( HTMLPurifier_HTMLDefinition $def ) : void
	{
		/* Links (set by _parseAElement) */
		$def->addAttribute( 'a', 'rel', 'Text' );
		
		/* srcset for emoticons (used by _parseImgElement) */
		$def->addAttribute( 'img', 'srcset', new HtmlPurifierSrcsetDef( TRUE ) );
		
		/* Quotes (used by ipsquote editor plugin) */
		$def->addAttribute( 'blockquote', 'data-ipsquote', 'Bool' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-timestamp', 'Number' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-username', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentapp', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentclass', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contenttype', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentid', 'Number' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentcommentid', 'Number' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-userid', 'Number' );
		$def->addAttribute( 'blockquote', 'data-cite', 'Text' );
		$def->addAttribute( 'blockquote', 'cite', 'Text' );
		$def->addAttribute( 'div', 'data-ipstruncate', 'Text' ); // this is used on the div.ipsQuote_contents element inside the blockquote
		$def->addElement( "header", 'Block', "Inline", 'Common' );
		
		/* Mentions (used by ipsmentions editor plugin) */
		$def->addAttribute( 'a', 'data-ipshover', new HtmlPurifierSwitchAttrDef( 'a', array( 'data-ipshover-target' ), new HTMLPurifier_AttrDef_HTML_Bool(''), new HTMLPurifier_AttrDef_Enum( array() ) ) );
		$def->addAttribute( 'a', 'data-ipshover-target', new HtmlPurifierInternalLinkDef( TRUE, array( array( 'app' => 'core', 'module' => 'members', 'controller' => 'profile', 'do' => 'hovercard' ) ) ) );
		$def->addAttribute( 'a', 'data-mentionid', 'Number' );
		$def->addAttribute( 'a', 'contenteditable', 'Enum#false' );
		
		/* Emoticons (used by the ipsautolink plugin) */
		$def->addAttribute( 'img', 'data-emoticon', 'Bool' ); // Identifies emoticons and stops lightbox running on them
		
		/* Attachments (set by _parseAElement, _parseImgElement and "insert existing attachment") - Gallery/Downloads use the full URL rather than an ID, hence Text */
		$def->addAttribute( 'a', 'data-fileid', new HtmlPurifierIntOrInternalLink() );
		$def->addAttribute( 'img', 'data-fileid', new HtmlPurifierIntOrInternalLink() );
		$def->addAttribute( 'a', 'data-fileext', 'Text' );
		
		/* Existing media (inserted with data-extension by the JS so that _getFile is able to locate) */
		$def->addAttribute( 'img', 'data-extension', 'Text' );
		$def->addAttribute( 'a', 'data-extension', 'Text' );

		/* This is needed because a tags can have images whose width is defined by the image; Attachments are a good example of this */
		$def->addAttribute( 'a', 'style', 'Text' );

		/* Lazy loading */
		$def->addAttribute( 'img', 'width', 'Number' );
		$def->addAttribute( 'img', 'height', 'Number' );
		$def->addAttribute( 'img', 'style', 'Text' );
		$def->addAttribute( 'iframe', 'style', 'Text' );
		$def->addAttribute( 'div', 'style', 'Text' );
		$def->addAttribute( 'img', 'loading', new HTMLPurifier_AttrDef_Enum( [ 'lazy', 'eager' ] ) );
		$def->addAttribute( 'iframe', 'data-embed-src', new HTMLPurifier_AttrDef_URI( TRUE ) );
		$def->addAttribute( 'iframe', 'src', 'Text' );
		
		/* iFrames (used by embeddableMedia) */
		$def->addAttribute( 'iframe', 'data-controller', new HTMLPurifier_AttrDef_Enum( array( 'core.front.core.autosizeiframe' ) ) ); // used in core/global/embed/iframe.phtml
        $def->addAttribute( 'iframe', 'data-embedid', 'Text' ); //  used in core/global/embed/iframe.phtml
		$def->addAttribute( 'iframe', 'data-embedauthorid', 'Number' ); //  used for embed notifications
		$def->addAttribute( 'iframe', 'data-embedcontent', 'Text' ); // used in embeddableMedia
		$def->addAttribute( 'iframe', 'data-ipsembed-contentapp', 'Text' ); // used in embeddableMedia
		$def->addAttribute( 'iframe', 'data-ipsembed-contentid', 'Text' ); // used in embeddableMedia
		$def->addAttribute( 'iframe', 'data-ipsembed-contentclass', 'Text' ); // used in embeddableMedia
		$def->addAttribute( 'iframe', 'data-ipsembed-contentcommentid', 'Text' ); // used in embeddableMedia
		$def->addAttribute( 'iframe', 'data-ipsembed-timestamp', 'Text' ); // used in embeddableMedia
		$def->addAttribute( 'iframe', 'data-internalembed', 'Text' ); // used in embeddableMedia
		$def->addAttribute( 'iframe', 'allowfullscreen', 'Text' ); // Some services will specify this property

		/* Custom Iframes need these */
		$def->addAttribute( 'iframe', 'width', 'Number' ); // Some services will specify this property
		$def->addAttribute( 'iframe', 'height', 'Number' ); // Some services will specify this property

		/* Tiptap Adds Style and Classes to Spans */
		$def->addAttribute( 'span', 'class', 'Text' );
		$def->addAttribute( 'span', 'style', 'Text' );
		$def->addAttribute( 'span', 'data-ips-font-size', 'Text' );

		/* Highlight colors */
		$def->addElement( "mark", "Inline", "Inline", "Common" );
		$def->addAttribute( 'mark', 'data-i-background-color', 'Text' );

		/* data-controllers */
		$allowedDivDataControllers = array(
			'core.front.core.articlePages', 	// [page] (set by _parseContent)
		);

		foreach( Application::enabledApplications() as $app )
		{
			$settingsFile = $app->getApplicationPath() . "/data/parser.json";
			if( file_exists( $settingsFile ) )
			{
				$contents = json_decode( file_get_contents( $settingsFile ), true );
				$allowedDivDataControllers = array_merge( $allowedDivDataControllers, $contents['controllers'] );
			}
		}

		$def->addAttribute( 'div', 'data-controller', new HTMLPurifier_AttrDef_Enum( $allowedDivDataControllers, TRUE ) );

		/* [page] (set by _parseContent) */
		$def->addAttribute( 'div', 'data-role', new HTMLPurifier_AttrDef_Enum( array( 'contentPage' ), TRUE ) );
		$def->addAttribute( 'hr', 'data-role', new HTMLPurifier_AttrDef_Enum( array( 'contentPageBreak' ), TRUE ) );
		
		/* data-munge-src used by _removeMunge() */
		$def->addAttribute( 'img', 'data-munge-src', 'Text' );
		$def->addAttribute( 'iframe', 'data-munge-src', 'Text' );
		
		/* Videos */
		$def->addElement( 'video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
			'controls' 			=> 'Bool',
			'data-controller'	=> new HTMLPurifier_AttrDef_Enum( array( 'core.global.core.embeddedvideo' ) ),
			'data-video-embed'	=> 'Text',
			'preload'           => new HTMLPurifier_AttrDef_Enum( [ 'auto', 'metadata', 'none' ] ),
			'style'				=> 'Text'
		) );
		$def->addElement( 'source', 'Block', 'Flow', 'Common',  array(
			'src' 				=> new HTMLPurifier_AttrDef_URI( TRUE ),
			'srcset'			=> new HtmlPurifierSrcsetDef( TRUE ),
			'media'				=> 'Text',
			'type' 				=> 'Text',
			'data-video-src'	=> new HTMLPurifier_AttrDef_URI( TRUE )
		) );

		/* Audio */
		$def->addElement('audio', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
			'controls' 			=> 'Bool',
			'data-controller'	=> new HTMLPurifier_AttrDef_Enum( array( 'core.global.core.embeddedaudio' ) ),
			'src' 				=> new HTMLPurifier_AttrDef_URI( TRUE ),
			'srcset'			=> new HtmlPurifierSrcsetDef( TRUE ),
			'media'				=> 'Text',
			'type' 				=> 'Text',
			'data-audio-embed'	=> 'Text',
			'data-audio-src'	=> new HTMLPurifier_AttrDef_URI( TRUE ),
			'preload'           => new HTMLPurifier_AttrDef_Enum( [ 'auto', 'metadata', 'none' ] )
		));

		/* Picture tag - we don't use it, but RSS imports might */
		$def->addElement( 'picture', 'Inline', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array() );

		/* Tiptap CodeboxLowLight Styles */
		$def->addAttribute( 'code', 'class', 'Text' );
		$def->addAttribute( 'pre', 'data-language', 'Text' );
		$def->addAttribute( 'pre', 'spellcheck', 'Bool' );


		/* Font colors */
		$def->addAttribute( 'span', 'data-i-color', 'Text' );
		$def->addAttribute( 'span', 'data-i-background-color', 'Text' );

		/* Tables */
		$def->addAttribute( 'table', 'style', 'Text' );
		$def->addAttribute( 'tr', 'style', 'Text' );
		$def->addAttribute( 'th', 'style', 'Text' );
		$def->addAttribute( 'td', 'style', 'Text' );

		/* This is important for the tiptap og embed embeds */
		$def->addElement( 'figure', 'Block', 'Optional: Flow | (source, Flow) | (Flow, source)', 'Common', array(
			'class'=> 'Text'
		) );
		$def->addElement( 'figcaption', 'Block', 'Optional: Flow | (source, Flow) | (Flow, source)', 'Common', array(
			'class' => 'Text'
		));
		foreach ( static::$ogFields as $field )
		{
			$def->addAttribute( 'figure', 'data-og-' . $field, 'Text' );
		}

		foreach ( ['figure','div','video','audio','img','iframe','embed'] as $embedType )
		{
			$def->addAttribute( $embedType, 'data-og-user_text', 'Text' );
		}

		/* IPS Boxes */
		$def->addElement( 'details', 'Block', 'Optional: Flow | (source, Flow) | (Flow, source)', 'Common' );
		$def->addElement( 'summary', 'Block', "Optional: Flow | (source, Flow) | (Flow, source)", 'Common' );
		$def->addAttribute('details', 'class', 'Text');
		$def->addAttribute( 'summary', 'data-i-background-color', 'Text' );
		$def->addAttribute( 'details', 'data-i-background-color', 'Text' );
		$def->addAttribute( 'div', 'data-i-background-color', 'Text' );
		$def->addAttribute( 'details', 'open', 'Text' );

		/* I tag for fa icons */
		$def->addElement( 'i', 'Inline', "Inline", "Common" );
		$def->addAttribute( 'i', 'class', 'Text' );

		/* Allow BR tags */
		$def->addElement( 'br', 'Inline', "Inline", "Common" );
	}

	/**
	 * @var string[]	The allowed fields in an embed parsed from OG data.
	 */
	public static array $ogFields = [
		'url',
		'site_name',
		'title',
		'description',
		'type',
		'image',
		'image_width',
		'image_height',
		'locale',
		'favicon_url'
	];

	/**
	 * @brief	Maximum allowed width/height px values (to prevent causing page layout oddities)
	 */
	protected int $cssMaxWidthHeight	= 1000;

	/**
	 * @brief	Maximum allowed border width px value (to prevent causing page layout oddities)
	 */
	protected int $cssMaxBorderWidth	= 50;

	/**
	 * Customize HTML Purifier CSS Definition
	 *
	 * @param HTMLPurifier_CSSDefinition $def The definition
	 * @param HTMLPurifier_Config $config HTML Purifier configuration object
	 * @return    void
	 */
	protected function _htmlPurifierModifyCssDefinition( HTMLPurifier_CSSDefinition $def, HTMLPurifier_Config $config ) : void
	{
		/* Do not allow negative margins */
		$margin = $def->info['margin-right'] = $def->info['margin-left'] = $def->info['margin-bottom'] = $def->info['margin-top'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_CSS_Length( 0 ),
                new HTMLPurifier_AttrDef_CSS_Percentage( TRUE ),
                new HTMLPurifier_AttrDef_Enum(array('auto'))
            )
        );
        $def->info['margin'] = new HTMLPurifier_AttrDef_CSS_Multiple( $margin );
        
        /* Don't allow white-space:nowrap */
        $def->info['white-space'] = new HTMLPurifier_AttrDef_Enum(
            array( 'normal', 'pre', 'pre-wrap', 'pre-line')
        );

        /* Limit the maximum width and height allowed */
		$def->info['width'] = $def->info['height'] = new HTMLPurifier_AttrDef_CSS_Composite( array(
			new HTMLPurifier_AttrDef_CSS_Length( '0px', $this->cssMaxWidthHeight . 'px' ),
			new HTMLPurifier_AttrDef_CSS_Percentage( true ),
			new HTMLPurifier_AttrDef_Enum( array( 'auto' ) )
		) );

		/* Limit the maximum border width allowed */
		$border_width =
			$def->info['border-top-width'] =
			$def->info['border-bottom-width'] =
			$def->info['border-left-width'] =
			$def->info['border-right-width'] = new HTMLPurifier_AttrDef_CSS_Composite(array(
				new HTMLPurifier_AttrDef_Enum( array( 'thin', 'medium', 'thick' ) ),
				new HTMLPurifier_AttrDef_CSS_Length( '0px', $this->cssMaxBorderWidth . 'px' )
		) );

		$def->info['border-width'] = new HTMLPurifier_AttrDef_CSS_Multiple( $border_width );

		/* We have to reset this so the constructor picks up the new values we just specified */
		$def->info['border'] = $def->info['border-bottom'] = $def->info['border-top'] = $def->info['border-left'] = $def->info['border-right'] = new HTMLPurifier_AttrDef_CSS_Border( $config );
	}

	/**
	 * Get URL bases (whout schema) that we'll allow iframes from
	 *
	 * @return array|string
	 */
	protected static function safeIframeRegexp(): array|string
	{
		$return = array();

		/* 3rd party sites (YouTube, etc.) */
		foreach ( static::allowedIFrameBases() as $base )
		{
			$return[] = '(https?:)?//' . preg_quote( $base, '%' );
		}
		
		/*
			Some, but not all local URLs
			Allowed: Any URLs which go through the front-end, e.g.:
				site.com/?app=core&module=system&controller=embed&url=whatever
				site.com/index.php?app=core&module=system&controller=embed&url=whatever
				site.com/topic/1-test/?do=embed
				site.com/index.php?/topic/1-test/?do=embed
				site.com/index.php?app=forums&module=forums&controller=topic&id=1&do=embed
			Not Allowed: Anything which goes to anything in an /interface directory - e.g.:
				site.com/applications/core/interface/file/attachment.php - this would automatically cause files to be downloaded
		    Not Allowed: Any file traversal to expose FileSystem directories, e.g:
				site.com/applications/core/../uploads/monthly_xx_xx/file.js?
			Not Allowed: URLs to the open proxy:
				site.com/index.php?app=core&module=system&controller=redirect
		 */
		$notAllowed = array();
		foreach( Application::enabledApplications() as $app )
		{
			$notAllowed[] = str_replace( '/', '(?:/{1,})', '(?:/{0,})' . preg_quote( 'applications/' . $app->directory . '/interface/', '%' ) );
		}

		foreach( File::getStore() as $configuration )
		{
			if ( $configuration['method'] == 'FileSystem' and ! empty( $configuration['configuration'] ) and $config = json_decode( $configuration['configuration'], TRUE ) )
			{
				if ( !empty( $config['dir'] ) and mb_strpos( $config['dir'], '{root}' ) !== false )
				{
					if ( $path = trim( str_replace( '{root}', '', $config['dir'] ), '\/' ) )
					{
						$notAllowed[] = '(?:.+?/{1,})\.{1,}(?:/{1,})' . $path;
					}
				}
			}
		}

		$return[] = '(https?:)?//' . preg_quote( str_replace( array( 'http://', 'https://' ), '', Settings::i()->base_url ), '%' ) . '\/?(\?|index\.php\?|(?!' . implode( '|', $notAllowed ) . ').+?\?)((?!(controller|section)=redirect).)*$';
		$return[] = preg_quote( '%7B___base_url___%7D', '%' ) . '\/?(\?|index\.php\?|(?!' . implode( '|', $notAllowed ) . ').+?\?)((?!(controller|section)=redirect).)*$';

		/* Return */	
		return '%^(' . implode( '|', $return ) . ')%';
	}
			
	/**
	 * Get URL bases (whout schema) that we'll allow iframes from
	 *
	 * @return	array
	 */
	protected static function allowedIFrameBases(): array
	{
		$return = array();
				
		/* Our default embed options */		
		$return = array_merge( $return, array(
			'www.youtube.com/embed/',
			'www.youtube-nocookie.com/embed/',
			'player.vimeo.com/video/',
			'www.hulu.com/embed.html',
			'www.collegehumor.com/e/',
			'embed-ssl.ted.com/',
			'embed.ted.com',
			'embed.spotify.com/',
			'www.dailymotion.com/embed/',
			'www.funnyordie.com/',
			'coub.com/',
			'www.reverbnation.com/',
			'api.smugmug.com/services/embed/',
			'www.google.com/maps/',
			'www.screencast.com/users/',
			'fast.wistia.net/embed/',
			'www.screencast.com/users/',
			'players.brightcove.net/',
		) );
		
		/* Extra admin-defined options */
		foreach( Application::enabledApplications() as $app )
		{
			$settingsFile = $app->getApplicationPath() . "/data/parser.json";
			if( file_exists( $settingsFile ) )
			{
				$contents = json_decode( file_get_contents( $settingsFile ), true );
				$return = array_merge( $return, $contents['iframe'] );
			}
		}

		/* If the CMS root URL is not inside the IPS4 directory, then embeds will fails as the src will not be allowed */
		if ( Application::appIsEnabled( 'cms' ) and Settings::i()->cms_root_page_url )
		{
			$pages = iterator_to_array( Db::i()->select( 'database_page_id', 'cms_databases', array( 'database_page_id > 0' ) ) );

			foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'cms_pages', array( Db::i()->in( 'page_id', $pages ) ) ), 'IPS\cms\Pages\Page' ) as $page )
			{
				$return[] = str_replace( array( 'http://', 'https://' ), '', $page->url() );
			}
		}
		
		return $return;
	}
	
	/**
	 * Get allowed CSS classes
	 *
	 * @return	array
	 */
	protected function getAllowedCssClasses(): array
	{		
		/* Init */
		$return = array();
		
		/* Quotes (used by ipsquote editor plugin) */
		$return[] = 'ipsQuote';
		$return[] = 'ipsQuote_citation';
		$return[] = 'ipsQuote_contents';
		
		/* Code (used by ipscode editor plugin) */
		$return[] = 'ipsCode';
		$return[] = 'prettyprint';
		$return[] = 'prettyprinted';
		$return[] = 'lang-auto';
		$return[] = 'lang-javascript';
		$return[] = 'lang-php';
		$return[] = 'lang-css';
		$return[] = 'lang-html';
		$return[] = 'lang-xml';
		$return[] = 'lang-c';
		$return[] = 'lang-sql';
		$return[] = 'lang-lua';
		$return[] = 'lang-swift';
		$return[] = 'lang-perl';
		$return[] = 'lang-python';
		$return[] = 'lang-ruby';
		$return[] = 'lang-latex';
		$return[] = 'tag';
		$return[] = 'pln';
		$return[] = 'atn';
		$return[] = 'atv';
		$return[] = 'pun';
		$return[] = 'com';
		$return[] = 'kwd';
		$return[] = 'str';
		$return[] = 'lit';
		$return[] = 'typ';
		$return[] = 'dec';
		$return[] = 'src';
		$return[] = 'nocode';
		
		/* Box */
		$return[] = 'ipsRichTextBox';
		$return[] = "ipsRichTextBox--alwaysopen";
		$return[] = "ipsRichTextBox--expandable";
		$return[] = "ipsRichTextBox--collapsible";
		$return[] = 'ipsRichTextBox__title';


		/* Alignment settings */
		$return[] = 'ipsRichText__align';
		$return[] = 'ipsRichText__align--left';
		$return[] = 'ipsRichText__align--right';
		$return[] = 'ipsRichText__align--block';
		$return[] = 'ipsRichText__align--inline';
		$return[] = 'ipsRichText__align--width-small';
		$return[] = 'ipsRichText__align--width-big';
		$return[] = 'ipsRichText__align--width-medium';
		$return[] = 'ipsRichText__align--width-fullwidth';
		$return[] = 'ipsRichText__align--width-custom';

		/* Images and attachments (used when attachments are inserted into the editor) */
		$return[] = 'ipsImage';
		$return[] = 'ipsImage_thumbnailed';
		$return[] = 'ipsAttachLink';
		$return[] = 'ipsAttachLink_image';
		$return[] = 'ipsAttachLink_left';
		$return[] = 'ipsAttachLink_right';
		$return[] = 'ipsEmoji';
		
		/* Embeds (used by various return values of embeddedMedia) */
		$return[] = 'ipsEmbedded';
		$return[] = 'ipsEmbeddedVideo';
		$return[] = 'ipsEmbeddedVideo_limited';
		$return[] = 'ipsEmbeddedOther';
		$return[] = 'ipsEmbeddedOther_limited';
		$return[] = 'ipsRawIframe';
		$return[] = 'ipsEmbedded__wrap';
		$return[] = 'ipsEmbedded__wrap--center';
		$return[] = 'ipsEmbedded__wrap--end';

		/* Links (Used to replace disallowed URLs */
		$return[] = 'ipsType_noLinkStyling';
		$return[] = 'ipsMention';
		
		/* Brightcove */
		$return[] = 'ipsEmbeddedBrightcove';
		$return[] = 'ipsEmbeddedBrightcove_inner';
		$return[] = 'ipsEmbeddedBrightcove_frame';

		/* OG Embed */
		$return[] = 'ipsEmbedded_og';
		foreach ( [ 'image', 'description', 'title', 'title--alone', 'site-name', 'favicon'] as $k )
		{
			$return[] = 'ipsEmbedded_og__' . $k;
		}

		/* Custom */
		foreach( Application::enabledApplications() as $app )
		{
			$settingsFile = $app->getApplicationPath() . "/data/parser.json";
			if( file_exists( $settingsFile ) )
			{
				$contents = json_decode( file_get_contents( $settingsFile ), true );
				$return = array_merge( $return, $contents['css'] );
			}
		}

		/* Language classes */
		foreach( json_decode( file_get_contents( Application::load( 'core' )->getApplicationPath() . '/data/allKnownCodeLanguages.json' ), true )['languages'] as $supportedLang )
		{
			$return[] = strtolower( "language-{$supportedLang}" );
		}

		return $return;
	}
	
	/* !Parser: Main Parser */
	
	/**
	 * @brief	Does the content contain [page] tags?
	 */
	protected bool $containsPageTags = FALSE;
	
	/**
	 * @brief	Open <abbr> tags
	 */
	protected array $openAbbrTags = array();
	
	/**
	 * Parse Profanity, etc. by loading into a DOMDocument
	 *
	 * @param string $value	HTML to parse
	 * @return	string
	 */
	protected function _parseContent( string $value ): string
	{
		/* The editor button just drops in a <hr>, so we need to make sure that the structure is correct, as follows:
			<div data-controller="core.front.core.articlePages">
				<div data-role="contentPage">
					<hr data-role="contentPageBreak" />
					<p>
						Page one
					</p>
				</div>
				<div data-role="contentPage">
					<hr data-role="contentPageBreak" />
					<p>
						Page two
					</p>
				</div>
			</div>
			
			The editor will just have
			<p>
				Page one
			</p>
			<hr data-role="contentPageBreak">
			<p>
				Page two
			</p>
		*/

		/* Parse */
		$parser = new DOMParser( array( $this, '_parseDomElement' ), array( $this, '_parseDomText' ) );
		$document = $parser->parseValueIntoDocument( $value );
				
		/* Return */
 		return DOMParser::getDocumentBodyContents( $document );
	}
	
	/**
	 * Parse HTML element (e.g. <html>, <p>, <a>, etc.)
	 *
	 * @param	DOMElement			$element	The element from the source document to parse
	 * @param	DOMNode			$parent		The node from the new document which will be this node's parent
	 * @param DOMParser $parser		DOMParser Object
	 * @return	void
	 */
	public function _parseDomElement(DOMElement $element, DOMNode $parent, DOMParser $parser ) : void
	{		
		/* Start of an <abbr>? */
		$okayToParse = TRUE;
		if ( $element->tagName === 'abbr' and $element->hasAttribute('title') )
		{
			$title = $element->getAttribute('title');
			if ( !in_array( $title, $this->openAbbrTags ) )
			{
				$this->openAbbrTags[] = $title;
			}
			else
			{
				$okayToParse = FALSE;
			}
		}
		
		/* Import */
		if ( $okayToParse )
		{
			/* Import the element as it is */
			$ownerDocument = $parent->ownerDocument ?: $parent;
			$newElement = $ownerDocument->importNode( $element );
					
			/* Element-specific parsing */
			$newElement = $this->_parseElement( $newElement );
			
			/* Append */
			if ( $newElement instanceof DOMElement )
			{
				$parent->appendChild( $newElement );
			}
			
			/* Swap out emoticons that should be plaintext (meaning we hit the maximum limit of emoticons per editor) */
			foreach( $parent->getElementsByTagName( 'img' ) AS $img )
			{
				if ( $img->hasAttribute( 'data-ipsEmoticon-plain' ) )
				{
					$replace = $parent->appendChild( new DOMText( $img->getAttribute( 'data-ipsEmoticon-plain' ) ) );
					$parent->replaceChild( $replace, $img );
				}
			}
		}
		else
		{
			$newElement = $parent;
		}

		// we only care to copy the element's children if it's new
		if ( $newElement instanceof DOMNode )
		{
			$parser->_parseDomNodeList( $element->childNodes, $newElement );
		}
		
		/* Finish */
		if ( $okayToParse )
		{
			/* End of an <abbr>? */
			if ( $element->tagName === 'abbr' and $element->hasAttribute('title') )
			{
				$k = array_search( $element->getAttribute('title'), $this->openAbbrTags );
				if ( $k !== FALSE )
				{
					unset( $this->openAbbrTags[ $k ] );
				}
			}
			
			/* If we did have children, but now we don't, drop this element to avoid unintentional whitespace */
			if ( $newElement instanceof DOMNode and $newElement->parentNode and $element->childNodes->length and !$newElement->childNodes->length )
			{
				$parent->removeChild( $newElement );
			}
		}
	}

	/**
	 * Parse Text
	 *
	 * @param DOMText $textNode The text from the source document to parse
	 * @param DOMNode $parent The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @param DOMParser $parser
	 * @return    void
	 */
	public function _parseDomText(DOMText $textNode, DOMNode &$parent, DOMParser $parser ) : void
	{		
		/* Init */
		$text = $textNode->wholeText;
		$breakPoints = array( '(' . static::getEmojiRegex() . ')' );
		
		/* Contains [page] tags? */
		if ( mb_strpos( $text, '[page]' ) !== FALSE )
		{
			$this->containsPageTags = TRUE;
		}
		
		/* If we have any acronyms, they also need to be breakpoints */
		if ( count( $this->caseSensitiveAcronyms ) or count( $this->caseInsensitiveAcronyms ) )
		{
			$breakPoints[] = '((?=<^|\b|\W)(?:' . implode( '|', array_merge( array_map( function ( $value ) { return preg_quote( $value, '/' ); }, array_keys( $this->caseSensitiveAcronyms ) ), array_map( function ( $value ) { return preg_quote( $value, '/' ); }, array_keys( $this->caseInsensitiveAcronyms ) ) ) ) . ')(?=\b|\W|$))';
		}
						
		/* Loop through each section */
		if ( count( $breakPoints ) )
		{
			$sections = array_values( array_filter( preg_split( '/' . implode( '|', $breakPoints ) . '/iu', $text, null, PREG_SPLIT_DELIM_CAPTURE ), function( $val ) { return $val !== ''; } ) );
			foreach( $sections as $sectionId => $section )
			{
				$this->_parseTextSection( $section, $parent );
			}		
		}
		else
		{
			$this->_parseTextSection( $textNode->wholeText, $parent );
		}
	}
	
	/**
	 * Parse a section of text after it has been split into relevant sections
	 *
	 * @param	string		$section		The text from the source document to parse
	 * @param	DOMNode	$parent			The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @return	void
	 */
	protected function _parseTextSection( string $section, DOMNode &$parent ) : void
	{		
		/* If it's empty, skip it */
		if ( $section === '' )
		{
			return;
		}
		/* HTMLPurifier will strip carrage returns, but if HTML posting is enabled this doesn't happen which
				leaves blank spaces - so we need to strip here */
		if ( !$this->htmlPurifier )
		{
			$section = str_replace( "\r", '', $section );
		}

		/* Profanity */
		foreach ( $this->exactProfanity as $bad => $good )
		{
			$section = preg_replace( '/(^|\b|\s)' . preg_quote( $bad, '/' ) . '(\b|\s|!|\?|\.|,|$)/iu', "\\1" . $good . "\\2", $section );
		}
		$section = str_ireplace( array_keys( $this->looseProfanity ), array_values( $this->looseProfanity ), $section );

		/* Note what $parent is */
		$originalParent = $parent;

		/* Emoji? */
		if ( ( !$originalParent->getAttribute('class') or !in_array( 'ipsEmoji', explode( ' ', $originalParent->getAttribute('class') ) ) ) and preg_match( '/' . static::getEmojiRegex() . '/u', $section ) )
		{
			$parent = $parent->appendChild( new DOMElement( 'span' ) );
			$parent->setAttribute( 'class', 'ipsEmoji' );
		}

		/* Check for emails */
		if ( Settings::i()->email_filter_action == 'replace' and preg_match( '/' . static::EMAIL_REGEX . '/u', $section ) )
		{
			$section = preg_replace( '/' . static::EMAIL_REGEX . '/u', Settings::i()->email_filter_replace_text, $section );
		}

		/* Insert the text */
		$parent->appendChild( new DOMText( $section ) );

		/* Restore the parent */
		$parent = $originalParent;
	}
	
	/* !Parser: Element-Specific Parsing */
	
	/**
	 * Element-Specific Parsing
	 *
	 * @param	DOMElement			$element			The element
	 *
	 * @note	_parseDomElement() creates a new element and imports it into the document. You can inspect $originalElement if you need to check context.
	 * @return	DOMNode|bool|DOMElement|null
	 */
	protected function _parseElement( DOMElement $element): DOMNode|bool|DOMElement|null
	{
		/* Element-Specific */
		switch ( $element->tagName )
		{
			case 'a':
				$element = $this->_parseAElement( $element );
				break;
				
			case 'img':
				$element = $this->_parseImgElement( $element );
				break;
				
			case 'iframe':
				$element = $this->_parseIframeElement( $element );
				break;

			case 'video':
				$element = $this->_parseVideoElement( $element );
				break;

			case 'audio':
				$element = $this->_parseAudioElement( $element );
				break;
		}

		if ( !( $element instanceof DOMElement ) )
		{
			return $element;
		}
		
		/* Anything which has a URL may need swapping out */
		foreach ( array( 'href', 'src', 'srcset', 'data-ipshover-target', 'data-fileid', 'cite', 'action', 'longdesc', 'usemap', 'poster' ) as $attribute )
		{
			if ( $element->hasAttribute( $attribute ) )
			{				
				if ( preg_match( '#^(https?:)?//(' . preg_quote( rtrim( str_replace( array( 'http://', 'https://' ), '', Settings::i()->base_url ), '/' ), '#' ) . ')/(.+?)$#', $element->getAttribute( $attribute ), $matches ) )
				{
					$element->setAttribute( $attribute, '%7B___base_url___%7D/' . $matches[3] );
				}
			}
		}

		foreach ( array( 'srcset', 'style' ) as $attribute )
		{
			if ( $element->hasAttribute( $attribute ) )
			{
				if ( mb_strpos( $element->getAttribute( $attribute ), Settings::i()->base_url ) )
				{
					$element->setAttribute( $attribute, str_replace( Settings::i()->base_url, '%7B___base_url___%7D/', $element->getAttribute( $attribute ) ) );
				}
			}
		}
		
		/* Return */
		return $element;
	}
	
	/**
	 * Parse <a> element
	 *
	 * @param	DOMElement	$element	The element
	 * @return	DOMElement
	 */
	protected function _parseAElement( DOMElement $element ) : DOMElement
	{
		/* Punycode it if necessary */
		if ( !preg_match( '/^[\x00-\x7F]*$/', $element->getAttribute('href') ) )
		{
			try
			{
				$punycodeEncoded = (string) Url::createFromString( $element->getAttribute('href') );
				$element->setAttribute( 'href', $punycodeEncoded );
			}
			catch(Url\Exception $e ) { }
		}
		
		/* If it's not allowed, remove the href */
		if ( !static::isAllowedUrl( $element->getAttribute('href') ) )
		{
			$element->removeAttribute( 'href' );
			$element->setAttribute( 'class', 'ipsType_noLinkStyling' );
			return $element;
		}
				
		/* Attachment? */
		if ( $attachment = static::_getAttachment( $element->getAttribute('href'), $element->hasAttribute('data-fileid') ? $element->getAttribute('data-fileid') : NULL ) )
		{
			$element->setAttribute( 'data-fileid', $attachment['attach_id'] );
			$element->setAttribute( 'href', str_replace( array( 'http:', 'https:' ), '', str_replace( static::$fileObjectClasses['core_Attachment']->baseUrl(), '{fileStore.core_Attachment}', $element->getAttribute('href') ) ) );
			$element->setAttribute( 'data-fileext', $attachment['attach_ext'] );

			if ( ! empty( $attachment['attach_labels'] ) and $labels = static::getAttachmentLabels( $attachment ) )
			{
				if ( count( $labels ) )
				{
					if ( $this->_altLabelWord === NULL )
					{
						$this->_altLabelWord = Lang::load( Lang::defaultLanguage() )->get( 'alt_label_could_be' );
					}

					$element->setAttribute( 'alt', $this->_altLabelWord . ' ' . implode( ', ', $labels ) );
				}
			}
			
			$this->_logAttachment( $attachment );
		}
		
		/* Some other media? */
		elseif ( $element->getAttribute('data-extension') and $file = $this->_getFile( $element->getAttribute('data-extension'), $element->getAttribute('href') ) )
		{
			$element->setAttribute( 'href', '{fileStore.' . $file->storageExtension . '}/' . $file );
		}
		
		try
		{
			$rels	= $this->_getRelAttributes( Url::createFromString( $element->getAttribute('href') ) );
		}
		catch(Url\Exception $e )
		{
			$rels	= array(); 
		}
		
		/* Add rels */
		$element->setAttribute( 'rel', implode( ' ', $rels ) );
		
		return $element;
	}
	
	/**
	 * @brief Emoticon Count
	 */
	protected int $_emoticons = 0;
	
	/**
	 * Parse <img> element
	 *
	 * @param	DOMElement	$element	The element
	 * @return	bool|DOMElement
	 */
	protected function _parseImgElement( DOMElement $element ): bool|DOMElement
	{
		/* When editing content in the AdminCP, images and iframes get the src munged. When we save, we need to put that back */
		$this->_removeMunge( $element );

		if ( $element->getAttribute( 'class' ) and preg_match( '#ipsEmbedded_og__(favicon|image)#', $element->getAttribute( 'class' ) ) )
		{
			return $element;
		}

		/* Is it an emoji? */
		if ( $element->getAttribute('class') and in_array( 'ipsEmoji', explode( ' ', $element->getAttribute('class') ) ) and $element->getAttribute('alt') )
		{
			$newElement = $element->ownerDocument->importNode( new DOMElement( 'span' ) );
			$newElement->setAttribute( 'class', 'ipsEmoji' );
			$newElement->appendChild( new DOMText( $element->getAttribute('alt') ) );
			return $newElement;
		}

		/* If it's not allowed, remove the src */
		try
		{
			static::isAllowedContentUrl( $element->getAttribute( 'src' ) );
		}
		catch( UnexpectedValueException $e )
		{
			$newElement = $element->ownerDocument->importNode( new DOMElement( 'span' ) );
			$newElement->appendChild( new DOMText( $element->getAttribute( 'src' ) ) );

			return $newElement;
		}

		/* Is it an emoticon? */
		if ( $element->hasAttribute('data-emoticon') )
		{
			if ( $this->_emoticons < 75 )
			{
				if ( !isset( static::$fileObjectClasses['core_Emoticons'] ) )
				{
					static::$fileObjectClasses['core_Emoticons'] = File::getClass('core_Emoticons' );
				}

				$element->setAttribute( 'src', str_replace( array( 'http:', 'https:' ), '', str_replace( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '{fileStore.core_Emoticons}', $element->getAttribute('src') ) ) );

				if ( $srcSet = $element->getAttribute('srcset') )
				{
					$element->setAttribute( 'srcset', str_replace( array( 'http:', 'https:' ), '', str_replace( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '%7BfileStore.core_Emoticons%7D', $srcSet ) ) );
				}

				$this->_emoticons++;
			}
			else
			{
				/* Set an attribute on the element - we'll need to know this later */
				$element->setAttribute( 'data-ipsEmoticon-plain', $element->getAttribute('title') );
			}
		}
		
		/* Or an attachment? */
		elseif ( $attachment = static::_getAttachment( $element->getAttribute('src'), $element->hasAttribute('data-fileid') ? $element->getAttribute('data-fileid') : NULL ) )
		{
			$file = $this->_getFile( 'core_Attachment', $element->getAttribute('src') );

			$element->setAttribute( 'data-fileid', $attachment['attach_id'] );
			$element->setAttribute( 'src', str_replace( array( 'http:', 'https:' ), '', str_replace( static::$fileObjectClasses['core_Attachment']->baseUrl(), '{fileStore.core_Attachment}', $element->getAttribute('src') ) ) );


			if ( ( !$element->hasAttribute('alt') or $element->getAttribute('alt') == $file->filename ) and ! empty( $attachment['attach_labels'] ) and $labels = static::getAttachmentLabels( $attachment ) )
			{
				if ( count( $labels ) )
				{
					if ( $this->_altLabelWord === NULL )
					{
						$this->_altLabelWord = Lang::load( Lang::defaultLanguage() )->get( 'alt_label_could_be' );
					}

					$element->setAttribute( 'alt', $this->_altLabelWord . ' ' . implode( ', ', $labels ) );
				}
			}

			if ( !$element->getAttribute('alt') )
			{
				$element->setAttribute( 'alt', $attachment['attach_file'] );
			}

			$this->_logAttachment( $attachment );
		}

		/* Or some other media? */
		elseif ( $element->getAttribute('data-extension') and $file = $this->_getFile( $element->getAttribute('data-extension'), $element->getAttribute('src') ) )
		{
			$element->setAttribute( 'src', '{fileStore.' . $file->storageExtension . '}/' . $file );
			if ( !$element->getAttribute('alt') )
			{
				$element->setAttribute( 'alt', $file->originalFilename );
			}
		}
		
		/* Nope, regular image */
		else
		{
			/* We need an alt (HTMLPurifier handles this normally, but it may not always run) */
			if ( !$element->getAttribute('alt') )
			{
				$element->setAttribute( 'alt', mb_substr( basename( $element->getAttribute('src') ), 0, 40 ) );
			}
		}

		/* Native Lazyload all imgs */
		$element->setAttribute( 'loading', 'lazy' );

		return $element;
	}

	/**
	 * Parse <video> element
	 *
	 * @param	DOMElement	$element	The element
	 * @return	DOMElement
	 */
	protected function _parseVideoElement( DOMElement $element ): DOMElement
	{
		$element->setAttribute( 'preload', 'metadata' );
		return $element;
	}

	/**
	 * Parse <audio> element
	 *
	 * @param	DOMElement	$element	The element
	 * @return	DOMElement
	 */
	protected function _parseAudioElement( DOMElement $element ): DOMElement
	{
		$element->setAttribute( 'preload', 'metadata' );
		return $element;
	}

	/**
	 * Parse <iframe> element
	 *
	 * @param	DOMElement	$element	The element
	 * @return	bool|DOMElement
	 */
	protected function _parseIframeElement( DOMElement $element ): bool|DOMElement
	{
		if ( $element->tagName === 'iframe' and $element->hasAttribute( 'class' ) and preg_match( "/(^|\\s)ipsRawIframe($|\\s)/", $element->getAttribute( 'class' ) ) )
		{
			if ( Settings::i()->ipb_embed_url_filter_option )
			{
				$domains = explode( ',', Settings::i()->ipb_embed_url_whitelist );
				$domains[] = parse_url( Settings::i()->base_url, PHP_URL_HOST );
				$domainRegex = [];
				$randomStr = md5( microtime() );
				foreach ( $domains as $domain )
				{
					$domainRegex[] = preg_quote( str_replace( '*', $randomStr, $domain ) );
				}

				$domainRegex = str_replace( $randomStr, '.*', '/^(' . implode( '|', $domainRegex ) . ')$/i' );

				try
				{
					// No iframes with no source
					if ( !$element->hasAttribute( 'src' ) )
					{
						throw new Exception;
					}

					$iframeDomain = parse_url( $element->getAttribute( 'src' ), PHP_URL_HOST ) ?: '';

					// Should be a valid domain
					if ( !$iframeDomain )
					{
						throw new Exception;
					}

					if ( !preg_match( $domainRegex, $iframeDomain ) )
					{
						throw new Exception;
					}

					// we don't want unknown attributes
					foreach ( $element->attributes as $name => $attr )
					{
						if ( !in_array( strtolower( $name ), ['src', 'width', 'height', 'title', 'class'] ) )
						{
							$element->removeAttribute( $name );
						}
					}

					$element->setAttribute( 'sandbox', 'allow-scripts' );
					// the width and height need to be set in the style attribute
					$style = "";
					foreach ( [ 'width', 'height' ] as $attr )
					{
						if ( !$element->hasAttribute( $attr ) )
						{
							continue;
						}
						else if ( $value = preg_replace( "/px$/", "", $element->getAttribute( $attr ) ) and !is_numeric( $value ) )
						{
							$element->removeAttribute( $attr );
						}
						else
						{
							$value = max( 0, min( $attr === 'width' ? intval( Settings::i()->max_internalembed_width ?: 1200 ) : 2000, (int) $value ) );
							$style .= "{$attr}: {$value}px;";
						}
					}

					if ( $style )
					{
						$element->setAttribute( 'style', $style );
					}
				}
				catch ( Exception )
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			try
			{
				$src = Url::createFromString( $element->getAttribute( 'src' ) );

				if ( mb_strpos( $src->data['host'], 'youtube.com' ) !== FALSE or mb_strpos( $src->data['host'], 'youtube-nocookie.com' ) !== FALSE )
				{
					/* If this is a youtube link, let's strip auto-play... */
					$element->setAttribute( 'src', (string)$src->stripQueryString( 'autoplay' ) );
				}
			}
			catch ( Url\Exception ) {}
		}

		/* Native Lazyload all iFrames */
		$element->setAttribute( 'loading', 'lazy' );
		$this->_removeMunge( $element );
		
		return $element;
	}

	/**
	 * Remove Image Proxy URL
	 *
	 * @param	DOMElement		$element		The element we are working with
	 * @param bool $useProxyUrl	Use the proxied image URL (the locally stored image) instead of the original URL
	 * @return	void
	 */
	protected static function _removeImageProxy( DOMElement $element, bool $useProxyUrl=FALSE ) : void
	{
		$imageProxyUrl = "<___base_url___>/applications/core/interface/imageproxy/imageproxy.php";
		$attributeName = $element->hasAttribute( 'data-src' ) ? 'data-src' : 'src';

		$imageSrc = $element->getAttribute( $attributeName );

		/* If it's a local placeholder, we don't need to process it.
		 * - There was a minor bug in 4.2.5 that could affect some internal images,
		 * this specifically only checks that the string starts with the file storage replacement
		 * so that it can still fix the issue by disabling the image proxy.
		 */
		if( preg_match( '#^({|%7B|\<|%3C)fileStore\.#', $imageSrc ) )
		{
			return;
		}
		
		if( mb_stristr( $imageSrc, $imageProxyUrl ) )
		{
			try
			{
				$srcUrl = Url::createFromString( str_replace( array( '<___base_url___>', '%7B___base_url___%7D' ), rtrim( Settings::i()->base_url, '/' ), $imageSrc ) )->queryString['img'];
			}
			catch(Url\Exception $e )
			{
				parse_str( parse_url( str_replace( array( '<___base_url___>', '%7B___base_url___%7D' ), rtrim( Settings::i()->base_url, '/' ), $imageSrc ), PHP_URL_QUERY ), $queryString );

				$srcUrl = static::_getProxiedImageUrl( $queryString['img'], $useProxyUrl );
			}

			$element->setAttribute( $attributeName, static::_getProxiedImageUrl( $srcUrl, $useProxyUrl ) );
			
			/* We also need to remove image proxy from the parent A tag if it exists */
			if( $element->parentNode->tagName === 'a' AND mb_stristr( $element->parentNode->getAttribute( 'href' ), $imageProxyUrl ) )
			{
				try
				{
					$hrefSrcUrl = Url::createFromString( str_replace( array( '<___base_url___>', '%7B___base_url___%7D' ), rtrim( Settings::i()->base_url, '/' ), $element->parentNode->getAttribute( 'href' ) ) )->queryString['img'];
				}
				catch(Url\Exception $e )
				{
					parse_str( parse_url( str_replace( array( '<___base_url___>', '%7B___base_url___%7D' ), rtrim( Settings::i()->base_url, '/' ), $element->parentNode->getAttribute( 'href' ) ), PHP_URL_QUERY ), $queryString );

					$srcUrl = static::_getProxiedImageUrl( $queryString['img'], $useProxyUrl );
				}

				$element->parentNode->setAttribute( 'href', static::_getProxiedImageUrl( $hrefSrcUrl, $useProxyUrl ) );
			}
		}

		/* Remove data attribute */
		$element->removeAttribute('data-imageproxy-source');

		if( $element->getAttribute('srcset') )
		{
			$urls = explode( ',', $element->getAttribute('srcset') );
			$fixedUrls = array();

			foreach( $urls as $url )
			{
				/* Format is: http://url.com/img.png size */
				$data = explode( ' ', trim( $url ) );

				if( count( $data ) <= 2 )
				{
					/* If for some reason we're processing an existing imageproxy URL, set the full URL. */
					try
					{
						$imageSrcUrl = Url::createFromString( str_replace( array( '<___base_url___>', '%7B___base_url___%7D' ), rtrim( Settings::i()->base_url, '/' ), $data[0] ) );

						$imageSrcUrl = $imageSrcUrl->queryString['img'] ?? $data[0];
					}
					catch(Url\Exception $e )
					{
						/* If we're here just return the original URL. Legacy data can result in many different problems that just can't be accounted for in every case. */
						parse_str( parse_url( str_replace( array( '<___base_url___>', '%7B___base_url___%7D' ), rtrim( Settings::i()->base_url, '/' ), $data[0] ), PHP_URL_QUERY ), $queryString );

						$imageSrcUrl = ( isset( $queryString['img'] ) ) ? $queryString['img'] : $data[0];
					}

					$fixedUrls[] = static::_getProxiedImageUrl( $imageSrcUrl, $useProxyUrl ) . ( ! empty( $data[1] ) ? ' ' . $data[1] : '' );
				}
			}

			if ( count( $fixedUrls ) )
			{
				$element->setAttribute( 'srcset', implode( ', ', $fixedUrls ) );
			}
		}
	}

	/**
	 * Return the URL to restore when image proxy is disabled.
	 *
	 * @param string $url			Url to use
	 * @param bool $useProxyUrl	Use the proxied image URL (the locally stored image) instead of the original URL
	 * @return	string
	 */
	protected static function _getProxiedImageUrl( string $url, bool $useProxyUrl ): string
	{
		/* We want the original URL */
		if( !$useProxyUrl )
		{
			return $url;
		}

		/* If the table no longer exists because it was dropped but we are re-running a remove image proxy step, just use original URL */
		if( !Db::i()->checkForTable( 'core_image_proxy' ) )
		{
			return $url;
		}

		try
		{
			$cacheEntry = Db::i()->select( '*', 'core_image_proxy', array( 'md5_url=?', md5( $url ) ) )->first();

			if( $cacheEntry['location'] )
			{
				return (string) File::get( 'core_Imageproxycache', $cacheEntry['location'] )->url;
			}
		}
		catch( UnderflowException $e ) {}

		/* If we're here, we couldn't find the proxied image URL so just return the original URL */
		return $url;
	}
	
	/**
	 * When editing content in the AdminCP, images and iframes get the src munged. When we save, we need to put that back
	 *
	 * @param	DOMElement	$element	The element
	 * @return	void
	 */
	protected function _removeMunge( DOMElement $element ) : void
	{
		if ( $originalUrl = $element->getAttribute('data-munge-src') )
		{
			$element->removeAttribute( 'src' );
			$element->setAttribute( 'src', $originalUrl );
			$element->removeAttribute( 'data-munge-src' );
		}
	}
	
	/* !Parser: Element-Specific Parsing: URLs */
	
	/**
	 * Get "rel" attribute values for a URL
	 *
	 * @param	Url	$url	The URL
	 * @return	array
	 */
	protected function _getRelAttributes( Url $url ): array
	{
		$rels = array();
		
		/* We add external/nofollow rel attributes for non-internal non-local links */
		if ( !( $url instanceof Internal ) )
		{
			$rels[] = 'external';
			
			/* Do we also want to add nofollow? */
			if( Settings::i()->posts_add_nofollow )
			{
				/* If we aren't excluding any domains, then add it */
				if( !Settings::i()->posts_add_nofollow_exclude )
				{
					$rels[] = 'nofollow';
				}
				else
				{
					/* HTML Purifier converts IDN to punycode, so we need to do the same with our 'follow' domains */
					if ( !function_exists('idn_to_ascii') )
					{
						IPS::$PSR0Namespaces['TrueBV'] = ROOT_PATH . "/system/3rd_party/php-punycode";
						require_once ROOT_PATH . "/system/3rd_party/php-punycode/polyfill.php";
					}

					$follow	= array_map( function( $val ) {
						return idn_to_ascii( preg_replace( '/^www\./', '', $val ) );
					}, json_decode( Settings::i()->posts_add_nofollow_exclude ) );

					if( isset( $url->data['host'] ) AND !in_array( preg_replace( '/^www\./', '', $url->data['host'] ), $follow ) )
					{
						$rels[] = 'nofollow';
					}
				}
			}
		}
		
		return $rels;
	}

	/**
	 * Is allowed URL for embedded content (images or link previews)?
	 *
	 * @param string $url	The URL
	 * @return	bool	Returns TRUE on success, or throws an exception with the reason on failure
	 * @throws	UnexpectedValueException
	 */
	static public function isAllowedContentUrl( string $url ): bool
	{
		/* We want a URL object */
		try
		{
			$url = Url::createFromString( $url );
		}
		catch(Url\Exception $e )
		{
			/* The URL is not valid */
			throw new UnexpectedValueException( $e->getMessage() );
		}

		/* We will always allow internal URLs */
		if( $url instanceof Internal )
		{
			return true;
		}

		/* If the URL is blacklisted, just return it */
		if( !static::isAllowedUrl( $url ) )
		{
			throw new UnexpectedValueException( 'embed_bad_url' );
		}

		if( parse_url( $url, PHP_URL_SCHEME ) !== NULL AND parse_url( $url, PHP_URL_SCHEME ) !== 'https' )
		{
			throw new UnexpectedValueException( 'embed_only_https' );
		}

		return true;
	}

	/**
	 * Is allowed URL
	 *
	 * @param	string	$url	The URL
	 * @return	bool
	 */
	static public function isAllowedUrl( string $url ): bool
	{
		if ( Settings::i()->url_filter_action == 'moderate' or Member::loggedIn()->group['g_bypass_badwords']  )
		{
			/* We want to moderate content with this URL, so do nothing here, so it returns a full <a> tag */
			return true;	
		}
		
		if ( Settings::i()->ipb_url_filter_option != 'none' )
		{
			$links = Settings::i()->ipb_url_filter_option == "black" ? Settings::i()->ipb_url_blacklist : Settings::i()->ipb_url_whitelist;
	
			if( $links )
			{
				$linkValues = array();
				$linkValues = explode( "," , $links );
	
				if( Settings::i()->ipb_url_filter_option == 'white' )
				{
					$linkValues[]	= "http://" . parse_url( Settings::i()->base_url, PHP_URL_HOST ) . "/*";
					$linkValues[]	= "https://" . parse_url( Settings::i()->base_url, PHP_URL_HOST ) . "/*";
				}
	
				if ( !empty( $linkValues ) )
				{
					$goodUrl = FALSE;
					
					if ( count( $linkValues ) )
					{
						foreach( $linkValues as $link )
						{
							if( !trim($link) )
							{
								continue;
							}
		
							$link = preg_quote( $link, '/' );
							$link = str_replace( '\*', "(.*?)", $link );
		
							if ( Settings::i()->ipb_url_filter_option == "black" )
							{
								if( preg_match( '/' . $link . '/i', $url ) )
								{
									return false;
								}
							}
							else
							{
								if ( preg_match( '/' . $link . '/i', $url ) )
								{
									$goodUrl = TRUE;
								}
							}
						}
					}
	
					if ( ! $goodUrl AND Settings::i()->ipb_url_filter_option == "white" )
					{
						return false;
					}
				}
			}
		}
	
		return true;
	}
	
	/* !Parser: Element-Specific Parsing: File System */
	
	/**
	 * @brief	Stored file object classes
	 */
	protected static array $fileObjectClasses = array();
	
	/**
	 * Get attachment data from URL
	 *
	 * @param string $url		The URL
	 * @param int|null $fileId		If we are editing and the fileid is already set, that's even better!
	 * @return	array|NULL
	 */
	protected static function _getAttachment( string $url, int $fileId = NULL ): ?array
	{
		/* We need the storage extension */
		if ( !isset( static::$fileObjectClasses['core_Attachment'] ) )
		{
			static::$fileObjectClasses['core_Attachment'] = File::getClass('core_Attachment');
		}

		/* If we have the fileid, we can do this the easy way */
		if( $fileId !== NULL )
		{
			try
			{
				return Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $fileId ) )->first();
			}
			catch( UnderflowException $e ){}
		}

		/* De-munge it */
		if ( preg_match( '#^(?:http:|https:)?' . preg_quote( rtrim( str_replace( array( 'http://', 'https://' ), '//', Settings::i()->base_url ), '/' ), '#' ) . '/index.php\?app=core&module=system&controller=redirect&url=(.+?)&key=.+?(?:&resource=[01])?$#', $url, $matches ) )
		{
			$url = urldecode( $matches[1] );
		}
		
		/* If it's URL to applications/core/interface/file/attachment.php, it's definitely an attachment */
		if ( preg_match( '#^(?:http:|https:)?' . preg_quote( rtrim( str_replace( array( 'http://', 'https://' ), '//', Settings::i()->base_url ), '/' ), '#' ) . '/applications/core/interface/file/attachment\.php\?id=(\d+)(?:&key=[a-f0-9]{32})?$#', $url, $matches ) )
		{
			try
			{
				return Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $matches[1] ) )->first();
			}
			catch ( UnderflowException $e ) { }
		}
		
		/* Otherwise, we need to see if it matches the actual attachment storage URL */
		if ( preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Attachment']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $url, $matches ) )
		{
			try
			{
				return Db::i()->select( '*', 'core_attachments', array( 'attach_location=? OR attach_thumb_location=?', $matches[2], $matches[2] ) )->first();
			}
			catch ( UnderflowException $e ) { }
		}

		/* No, but it may have the URL replacement instead of the URL - Further refined since curly braces may be present instead if this is running in the AdminCP */
		if ( preg_match( '#^(({|%7B)fileStore.core_Attachment(%7D|}))/(.+?)$#', $url, $matches ) )
		{
			try
			{
				return Db::i()->select( '*', 'core_attachments', array( 'attach_location=? OR attach_thumb_location=?', $matches[4], $matches[4] ) )->first();
			}
			catch ( UnderflowException $e ) { }
		}
		
		/* Nope, not an attachment */
		return NULL;
	}
	
	/**
	 * Log that at attachment is being used in the content
	 *
	 * @param array $attachment	Attachment data
	 * @return	void
	 */
	protected function _logAttachment(array $attachment ) : void
	{        
		if ( isset( $this->existingAttachments[ $attachment['attach_id'] ] ) )
		{
			unset( $this->existingAttachments[ $attachment['attach_id'] ] );
		}
		elseif ( $attachment['attach_member_id'] === (int) $this->member->member_id and !in_array( $attachment['attach_id'], $this->mappedAttachments ) )
		{
			if( $this->area and !isset( Request::i()->_previewField ) )
			{			
				Db::i()->replace( 'core_attachments_map', array(
					'attachment_id'	=> $attachment['attach_id'],
					'location_key'	=> $this->area,
					'id1'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[0] ) ) ? $this->attachIds[0] : NULL,
					'id2'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[1] ) ) ? $this->attachIds[1] : NULL,
					'id3'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[2] ) ) ? $this->attachIds[2] : NULL,
					'temp'			=> is_string( $this->attachIds ) ? $this->attachIds : NULL,
					'lang'			=> $this->attachIdsLang
				) );
			}
			
			$this->mappedAttachments[] = $attachment['attach_id'];
		}
	}
	
	/**
	 * Get file data
	 *
	 * @param string $extension	The extension
	 * @param string $url		The URL
	 * @return	File|NULL
	 */
	protected function _getFile( string $extension, string $url ): ?File
	{
		if ( !isset( static::$fileObjectClasses[ $extension ] ) )
		{
			static::$fileObjectClasses[ $extension ] = File::getClass( $extension );
		}
		
		if ( preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses[ $extension ]->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $url, $matches ) )
		{
			return File::get( $extension, $url );
		}
		
		return NULL;
	}
	
	/**
	 * @brief	Cached permissions
	 */
	protected static array $permissions = array();
	
	/**
	 * Parse content looking for a separation tag
	 * Used for lists where [*] breaks and whole content where [page] breaks
	 *
	 * @param DOMNode $originalNode The node containing the content we want to examine
	 * @param callback $mainElementCreator A callback which returns a \DOMElement to be the main element. Is passed \DOMDocument as a parameter
	 * @param callback $subElementCreator A callback which returns a \DOMElement to be a new sub element. Is passed \DOMDocument as a parameter
	 * @param string|null $separator
	 * @return    DOMElement
	 */
	protected function _parseContentWithSeparationTag( DOMNode $originalNode, callable $mainElementCreator, callable $subElementCreator, string $separator=NULL ): DOMElement
	{
		/* Create a copy of the node */
		$workingDocument = new \DOMDocument;
		$workingNode = $workingDocument->importNode( $originalNode, TRUE );
		
		/* Create a fresh <ul> with a single <li> inside */
		$mainElement = $mainElementCreator( $originalNode->ownerDocument );
		$currentSubElement = $mainElement->appendChild( $subElementCreator( $originalNode->ownerDocument ) );
		
		/* Parse */
		$ignoreNextSeparator = TRUE;
		foreach ( $workingNode->childNodes as $node )
		{
			$this->_parseContentWithSeparationTagLoop( $mainElement, $currentSubElement, $separator, $subElementCreator, $node, $mainElement, $ignoreNextSeparator );
		}
				
		/* Return */
		return $mainElement;
	}
	
	/**
	 * Loop for _parseContentWithSeparationTag
	 *
	 * @param	DOMElement	$mainElement			The main element all of our content is going into
	 * @param	DOMElement	$currentSubElement		The current sub element that nodes go into. When a $separator is detected, a new one is created
	 * @param string $separator				The separator, e.g. "[*]" or "[page]"
	 * @param callback $subElementCreator		A callback which returns a \DOMElement to be a new sub element
	 * @param	DOMNode	$node					The current node we're examining
	 * @param	DOMNode	$parent					The parent of the current node we're examining
	 * @param bool $ignoreNextSeparator	If we have [list][*]Foo[/list] We want to ignore the first [*] so we don't end up with <ul><li></li><li>Foo</li></ul> - this keeps track of that
	 * @return	void
	 */
	protected function _parseContentWithSeparationTagLoop( DOMElement $mainElement, DOMElement &$currentSubElement, string $separator, callable $subElementCreator, DOMNode $node, DOMNode &$parent, bool &$ignoreNextSeparator ) : void
	{
		/* If the node is an element... */
		if ( $node->nodeType === XML_ELEMENT_NODE )
		{			
			/* Ignore any preceeding <br>s */
			if ( $ignoreNextSeparator and $node->tagName == 'br' )
			{
				return;
			}
			
			/* Import and insert */
			$newElement = $mainElement->ownerDocument->importNode( $node );
			if ( $parent->isSameNode( $mainElement ) )
			{
				$currentSubElement->appendChild( $newElement );
			}
			else
			{
				$parent->appendChild( $newElement );
			}
			
			/* Loop children */
			foreach ( $node->childNodes as $child )
			{
				$this->_parseContentWithSeparationTagLoop( $mainElement, $currentSubElement, $separator, $subElementCreator, $child, $newElement, $ignoreNextSeparator );
			}
		}
		
		/* Or if it's text... */
		elseif ( $node->nodeType === XML_TEXT_NODE )
		{			
			/* Ignore any closing tag */
			$text = $node->wholeText;
			$text = str_replace( preg_replace( '/\[(.+?)\]/', '[/$1]', $separator ), '', $text );
			
			/* Break it up where we find the separator... */			
			foreach ( array_filter( preg_split( '/(' . preg_quote( $separator, '/' ) . ')/', $text, null, PREG_SPLIT_DELIM_CAPTURE ), 'trim' ) as $textSection )
			{
				/* If this section the separator... */
				if ( $textSection === $separator )
				{
					/* Unless we're ignoring it, create a new element... */
					if ( !$ignoreNextSeparator )
					{
						/* Strip any extrenous <br> from the last one */
						if ( $currentSubElement->lastChild and $currentSubElement->lastChild->nodeType === XML_ELEMENT_NODE and $currentSubElement->lastChild->tagName == 'br' )
						{
							$currentSubElement->removeChild( $currentSubElement->lastChild );
						}
						
						/* Create a new one */
						$currentSubElement = $mainElement->appendChild( $subElementCreator( $mainElement->ownerDocument ) );
						if ( !$parent->isSameNode( $mainElement ) )
						{
							$parent = $parent->cloneNode( FALSE );
							$currentSubElement->appendChild( $parent );
						}
					}
					/* Or if we are, then don't ignore the next one */
					else
					{
						$ignoreNextSeparator = FALSE;
					}
				}
				else
				{
					/* Insert */				
					if ( $parent->isSameNode( $mainElement ) )
					{
						$currentSubElement->appendChild( new DOMText( $textSection ) );
					}
					else
					{
						$parent->appendChild( new DOMText( $textSection ) );
					}
					
					/* If we're meant to be ignoring the next separator, but we have found content before it, remove that flag */
					if ( $ignoreNextSeparator )
					{
						$ignoreNextSeparator = FALSE;
					}
				}
			}
		}
	}	
		
	/* !Embeddable Media */
	
	/**
	 * Get OEmbed Services
	 * Implemented in this way so it's easy for hook authors to override if they wanted to
	 *
	 * @see		<a href="http://www.oembed.com">oEmbed</a>
	 * @return	array
	 */
	protected static function oembedServices(): array
	{
		$services = array(
			'youtube.com'					=> array( 'https://www.youtube.com/oembed', static::EMBED_VIDEO ),
			'm.youtube.com'					=> array( 'https://www.youtube.com/oembed', static::EMBED_VIDEO ),
			'youtu.be'						=> array( 'https://www.youtube.com/oembed', static::EMBED_VIDEO ),
			'flickr.com'					=> array( 'https://www.flickr.com/services/oembed/', static::EMBED_IMAGE ),
			'flic.kr'						=> array( 'https://www.flickr.com/services/oembed/', static::EMBED_IMAGE ),
			'hulu.com'						=> array( 'https://www.hulu.com/api/oembed.json', static::EMBED_VIDEO ),
			'vimeo.com'						=> array( 'https://vimeo.com/api/oembed.json', static::EMBED_VIDEO ),
			'collegehumor.com'				=> array( 'https://www.collegehumor.com/oembed.json', static::EMBED_VIDEO ),
			'twitter.com'					=> array( 'https://publish.twitter.com/oembed', static::EMBED_TWEET ),
			'mobile.twitter.com'			=> array( 'https://publish.twitter.com/oembed', static::EMBED_TWEET ),
			'x.com' 						=> array( 'https://publish.x.com/oembed', static::EMBED_TWEET ),
			'x.twitter.com' 				=> array( 'https://publish.x.com/oembed', static::EMBED_TWEET ),
			'soundcloud.com'				=> array( 'https://soundcloud.com/oembed', static::EMBED_VIDEO ),
			'open.spotify.com'				=> array( 'https://embed.spotify.com/oembed', static::EMBED_VIDEO ),
			'play.spotify.com'				=> array( 'https://embed.spotify.com/oembed', static::EMBED_VIDEO ),
			'ted.com'						=> array( 'https://www.ted.com/services/v1/oembed', static::EMBED_VIDEO ),
			'vine.co'						=> array( 'https://vine.co/oembed.json', static::EMBED_VIDEO ),
			'dailymotion.com'				=> array( 'https://www.dailymotion.com/services/oembed', static::EMBED_VIDEO ),
			'dai.ly'						=> array( 'https://www.dailymotion.com/services/oembed', static::EMBED_VIDEO ),
			'coub.com'						=> array( 'https://coub.com/api/oembed.json', static::EMBED_VIDEO ),
			'*.deviantart.com'				=> array( 'https://backend.deviantart.com/oembed', static::EMBED_IMAGE ),
			'docs.com'						=> array( 'https://docs.com/api/oembed', static::EMBED_LINK ),
			'funnyordie.com'				=> array( 'https://www.funnyordie.com/oembed.json', static::EMBED_VIDEO ),
			'gettyimages.com'				=> array( 'https://embed.gettyimages.com/oembed', static::EMBED_IMAGE ),
			'ifixit.com'					=> array( 'https://www.ifixit.com/Embed', static::EMBED_LINK ),
			'kickstarter.com'				=> array( 'https://www.kickstarter.com/services/oembed', static::EMBED_LINK ),
			'meetup.com'					=> array( 'https://api.meetup.com/oembed', static::EMBED_LINK ),
			'mixcloud.com'					=> array( 'https://www.mixcloud.com/oembed/', static::EMBED_VIDEO ),
			'mix.office.com'				=> array( 'https://mix.office.com/oembed', static::EMBED_VIDEO ),
			'reddit.com'					=> array( 'https://www.reddit.com/oembed', static::EMBED_LINK ),
			'reverbnation.com'				=> array( 'https://www.reverbnation.com/oembed', static::EMBED_VIDEO ),
			'screencast.com'				=> array( 'https://api.screencast.com/external/oembed', static::EMBED_IMAGE ),
			'slideshare.net'				=> array( 'https://www.slideshare.net/api/oembed/2', static::EMBED_VIDEO ),
			'*.smugmug.com'					=> array( 'https://api.smugmug.com/services/oembed', static::EMBED_IMAGE ),
			'ustream.tv'					=> array( 'https://www.ustream.tv/oembed', static::EMBED_VIDEO ),
			'*.wistia.com'					=> array( 'https://fast.wistia.com/oembed', static::EMBED_VIDEO ),
			'*.wi.st'						=> array( 'https://fast.wistia.com/oembed', static::EMBED_VIDEO ),
			'tiktok.com'					=> array( 'https://www.tiktok.com/oembed', static::EMBED_VIDEO ),
			'm.tiktok.com'					=> array( 'https://www.tiktok.com/oembed', static::EMBED_VIDEO ),
			'vm.tiktok.com'					=> array( 'https://www.tiktok.com/oembed', static::EMBED_VIDEO ),
		);

		/* Can we support Facebook and Instagram oembeds? */
		if( $token = static::getFacebookToken() )
		{
			$services['instagram.com'] = array(
				array( 'https://graph.facebook.com/v9.0/instagram_oembed?access_token=' . $token, static::EMBED_IMAGE )
			);

			$services['instagr.am'] = $services['instagram.com'];

			$services['facebook.com'] = array(
				'(\/.+?\/videos\/|video\.php)'	=> array( 'https://graph.facebook.com/v9.0/oembed_video?access_token=' . $token, static::EMBED_VIDEO ),
				'(\/.+?\/posts\/|\/.+?\/activity\/|\/.+?\/photos?\/|photo.php|\/.+?\/media\/|permalink\.php|\/.+?\/questions\/|\/.+?\/notes\/|\/.+?\/media\/)'	=> array( 'https://graph.facebook.com/v9.0/oembed_post?access_token=' . $token, static::EMBED_STATUS ),
				0								=> array( 'https://graph.facebook.com/v9.0/oembed_page?access_token=' . $token, static::EMBED_STATUS ),
			);
		}

		return $services;
	}

	/**
	 * Get an application token from Facebook for oembed support
	 *
	 * @return	NULL|string
	 */
	protected static function getFacebookToken(): ?string
	{
		try
		{
			if( !Settings::i()->fb_ig_oembed_token AND Settings::i()->fb_ig_oembed_appid AND Settings::i()->fb_ig_oembed_appsecret )
			{
				$value = Url::external( "https://graph.facebook.com/oauth/access_token?client_id=" . Settings::i()->fb_ig_oembed_appid . "&client_secret=" . Settings::i()->fb_ig_oembed_appsecret . "&grant_type=client_credentials" )->request()->get()->decodeJson();

				if( !empty( $value['access_token'] ) )
				{
					Settings::i()->changeValues( array( 'fb_ig_oembed_token' => $value['access_token'] ) );
				}
				else
				{
					Log::debug( "Facebook access token could not be retrieved for oembed:\n" . var_export( $value, true ), 'facebook_oembed' );
				}
			}
		}
		catch( Exception $e )
		{
			Log::debug( $e, 'facebook_oembed' );
		}

		return Settings::i()->fb_ig_oembed_token ? Settings::i()->fb_ig_oembed_token : NULL;
	}
	
	/**
	 * @brief	External link request timeout
	 */
	public static int $requestTimeout	= DEFAULT_REQUEST_TIMEOUT;

	/**
	 * Convert URL to embed HTML
	 *
	 * @param 	Url 			$url		URL
	 * @param 	bool 			$iframe		Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @param 	Member|null 	$member		Member to check permissions against or NULL for currently logged in member
	 *
	 * @return	string|null		HTML embded code, or NULL if URL is not embeddable
	 */
	public static function embeddableMedia( Url $url, bool $iframe=FALSE, Member $member=NULL ): ?string
	{
		/* Internal */
		if ( $url instanceof Internal )
		{
			/* Internal Embed */
			if ( $embedCode = static::_internalEmbed( $url, $member ) )
			{
				return $embedCode;
			}
		}
		
		/* External */
		else
		{
			/* oEmbed? */
			if ( $embedCode = static::_oembedEmbed($url, $iframe) )
			{
				return $embedCode;
			}
			
			/* Other services */
			if ( $embedCode = static::_customEmbed( $url ) )
			{
				return $embedCode;
			}
		}
		
		/* Still here? It's not embeddable */
		return NULL;
	}

	/**
	 * @brief	Embed type constants
	 */
	const EMBED_IMAGE	= 1;
	const EMBED_VIDEO	= 2;
	const EMBED_STATUS	= 3;
	const EMBED_TWEET	= 4;
	const EMBED_LINK	= 5;
	
	/**
	 * oEmbed Embed Code
	 *
	 * @param	Url		$url			URL
	 * @param 	bool 	$iframe			Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @param 	int 	$attemptNumber	The attempt number, useful for automatically retrying a service
	 * @return	string|null
	 */
	protected static function _oembedEmbed( Url $url, bool $iframe=FALSE, int $attemptNumber=1 ): ?string
	{
		/* Strip the "www." from the domain */
		$domain = preg_replace( '/^www\.(.*)$/i', "$1", $url->data['host'] );
		if( !$domain )
		{
			return null;
		}

		/* TikTok does not support oembed for their short links, but will redirect to the full URL which does */
		if( $domain == 'vm.tiktok.com' )
		{
			try
			{
				$response = $url->request( null, null, 0 )->get();

				if( $response->httpResponseCode == 301 )
				{
					$url = Url::external( $response->httpHeaders['Location'] )->stripQueryString();
					$domain = $url->data['host'];
				}
				else
				{
					throw new \IPS\Http\Request\Exception;
				}
			}
			catch( \IPS\Http\Request\Exception $e )
			{
				throw new UnexpectedValueException( 'embed__fail_500',  (float) static::EMBED_VIDEO );
			}
		}
		
		/* TikTok doesn't return logical response codes for invalid URLs */
		if( $domain == 'tiktok.com' AND mb_strpos( $url->data['path'], '/video/' ) === FALSE )
		{
			return NULL;
		}
		elseif( $domain == 'm.tiktok.com' AND mb_strpos( $url->data['path'], '/v/' ) === FALSE )
		{
			return NULL;
		}
		
		/* Get oEmbed Services */
		$oembedServices = static::oembedServices();
				
		/* If the URL's domain is in the list... */
		$entry		= NULL;
		$entryKey	= NULL;

		if ( array_key_exists( $domain, $oembedServices ) )
		{
			$entry		= $oembedServices[ $domain ];
			$entryKey	= $domain;
		}
		else
		{
			foreach( $oembedServices as $k => $v )
			{
				if( mb_strpos( $k, '*' ) !== FALSE )
				{
					if( preg_match( "/^" . str_replace( array( '.', '*' ), array( '\\.', '.*?' ), $k ) . "$/i", $domain ) )
					{
						$entry		= $v;
						$entryKey	= $k;
						break;
					}
				}
			}
		}

		if( $entry )
		{
			$endtype	= static::EMBED_LINK;
			$endpoint	= NULL;

			/* If we have multiple possible ones, find the best */
			if ( is_array( $entry[0] ) )
			{
				foreach ( $entry as $regex => $endpoints )
				{
					if ( is_string( $regex ) and preg_match( $regex, $url ) )
					{
						$endpoint	= $endpoints[0];
						$endtype	= $endpoints[1];
						break;
					}
				}

				if( $endpoint === NULL )
				{
					$endpoint	= $entry[0][0];
					$endtype	= $entry[0][1];
				}
			}
			else
			{
				$endpoint = $entry[0];
				$endtype  = $entry[1];
			}

			$otherOembedRequestParams = [];

			/* Youtube shorts feature doesn't return a valid response in its own oembed API so we need to tweak it */
			if ( ( $domain == 'youtube.com' or $domain == 'm.youtube.com' or $domain == 'youtu.be' ) and mb_strpos( $url->data['path'], '/shorts/' ) !== FALSE)
			{
				$url->queryString['v'] = preg_replace( '#/shorts/(\w+?)#', '$1', $url->data['path'] );
				$url->data['path'] = '/watch';
			}

			/* Twitter lets us add a theme (light|dark) to the request */
			if ( $domain == 'x.com' or $domain == 'twitter.com' )
			{
				$otherOembedRequestParams['theme'] = Theme::i()->getCurrentCSSScheme() == 'dark' ? 'dark' : 'light';
			}

			/* Call oEmbed Service */
			try
			{
				$language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? Lang::load( Lang::defaultLanguage() )->bcp47();

				$response = Url::external( $endpoint )
					->setQueryString( array(
						'format'	=> 'json',
						'url'		=> (string) $url->stripQueryString( 'autoplay' ),
						'scheme'	=> ( $url->data[ Url::COMPONENT_SCHEME ] === 'https' or Request::i()->isSecure() ) ? 'https' : null,
						...$otherOembedRequestParams
					) )
					->request( static::$requestTimeout )
					->setHeaders( array( 'Accept-Language' => $language ) )
					->get();

				if( $response->httpResponseCode != '200' )
				{
					/* If this is FB/IG we can reattempt one more time in case the stored access token was incorrect or expired */
					if( in_array( $entryKey, array( 'instagram.com', 'instagr.am', 'facebook.com' ) ) AND $attemptNumber < 2 )
					{
						Settings::i()->changeValues( array( 'fb_ig_oembed_token' => '' ) );
						return static::_oembedEmbed($url, $iframe, 2);
					}

					switch( $response->httpResponseCode )
					{
						case '404':
							throw new UnexpectedValueException( 'embed__fail_404', (float) $endtype );

						case '401':
						case '403':
							throw new UnexpectedValueException( 'embed__fail_403', (float) $endtype );

						default:
							throw new UnexpectedValueException( 'embed__fail_500',  (float) $endtype );
					}
				}

				$response = $response->decodeJson();
			}
			/* If it error'd (connection error or unexpected response), we'll not embed this */
			catch ( \IPS\Http\Request\Exception $e )
			{
				throw new UnexpectedValueException( 'embed__fail_500', (float) $endtype );
			}
			
			/* Flickr when used in the video template is a bit quirky. Requires rich. */
			if ( $domain == 'flickr.com' OR $domain == 'flic.kr' )
			{
				$response['type'] = ( $response['type'] == 'video' ) ? 'rich' : 'photo';

				/* If we embed an album, no 'url' is returned but a thumbnail URL is so use that but stick with "photo" as the template. */
				if( $response['type'] == 'photo' AND !isset( $response['url'] ) AND isset( $response['thumbnail_url'] ) )
				{
					$response['url'] = $response['thumbnail_url'];
				}
			}

			/* Coub returns 'video' as the type, but points to an iframe with the video embedded, so switch to rich */
			if( $domain == 'coub.com' )
			{
				$response['type'] = ( $response['type'] == 'video' ) ? 'rich' : $response['type'];
			}

			/* For Youtube, we need to be a bit hacky to pass rel=0 */
			if ( $domain == 'youtube.com' or $domain == 'm.youtube.com' or $domain == 'youtu.be' )
			{
				/* YouTube has no way to make it return youtube-nocookie from the oEmbed endpoint, so we need to swap it manually. */
				if ( mb_strpos( $response['html'], 'youtube-nocookie.com/embed' ) === FALSE )
				{
					$response['html'] = str_replace( 'youtube.com', 'youtube-nocookie.com', $response['html'] );
				}
				
				if ( isset( $url->queryString['rel'] ) )
				{
					$response['html'] = str_replace( '?feature=oembed', '?feature=oembed&rel=0', $response['html'] );
				}
				
				if ( isset( $url->queryString['start'] ) )
				{
					$response['html'] = str_replace( '?feature=oembed', '?feature=oembed&start=' . intval( $url->queryString['start'] ), $response['html'] );
				}
			}

			/* Tiktok tries to embed a <script src="https://tiktok.com/embed.js"></script> tag which doesn't survive HTMLPurifier, so force it to load in an iframe */
			if ( $domain == 'tiktok.com' OR $domain == 'm.tiktok.com' )
			{
				$response['type'] = 'rich';
			}
			
			/* We need a type otherwise we can't embed */
			if( !isset( $response['type'] ) )
			{
				throw new UnexpectedValueException( 'embed_no_oembed_type' );
			}

			/* The "type" parameter is a way for services to indicate the type of content they are retruning. It is not strict, but we use it to identify the best styles to apply. */
			switch ( $response['type'] )
			{
				/* Static photo - show an <img> tag, linked if necessary, using .ipsImage to be responsive. Similar outcome to if a user had used the "insert image from URL" button */
				case 'photo':
					return Theme::i()->getTemplate( 'embed', 'core', 'global' )->photo( $response['url'], $url, $response['title'] );
				
				/* Video - insert the provided HTML directly (it will be a video so there's nothing we need to prevent from being edited), using .ipsEmbeddedVideo to make it responsive */
				case 'video':
                    $response['html'] = str_replace( 'allowfullscreen', 'allowfullscreen=""', $response['html'] );
					return Theme::i()->getTemplate( 'embed', 'core', 'global' )->video( $response['html'] );
				
				/* Other - show an <iframe> with the provided HTML inside, using .ipsEmbeddedOther to make the width right and data-controller="core.front.core.autoSizeIframe" to make the height right */
				case 'rich':						
					if ( $iframe )
					{
						return $response['html'];
					}
					else
					{
						$embedId = md5( mt_rand() );

						return Theme::i()->getTemplate( 'embed', 'core', 'global' )->iframe( (string) Url::internal( 'app=core&module=system&controller=embed', 'front' )->setQueryString( 'url', (string) $url ), NULL, NULL, $embedId );
					}
				
				/* Link - none of the defautl services use this, but provided for completeness. Just inserts an <a> tag */
				case 'link':
					return Theme::i()->getTemplate( 'embed', 'core', 'global' )->link( $response['url'], $response['title'] );
			}
		}
		
		/* Still here? It's not an oEmbed URL */
		return NULL;
	}
	
	/**
	 * Custom (services which don't support oEmbed but we still want to support) Embed Code
	 *
	 * @param	Url	$url		URL
	 * @return	string|null
	 */
	protected static function _customEmbed( Url $url ): ?string
	{
		/* Google Maps */
		if ( Settings::i()->googlemaps and Settings::i()->google_maps_api_key )
		{
			$googleTLDs = array(
				'.com',
				'.ac',
				'.ad',
				'.ae',
				'.com.af',
				'.com.ag',
				'.com.ai',
				'.al',
				'.am',
				'.co.ao',
				'.com.ar',
				'.as',
				'.at',
				'.com.au',
				'.az',
				'.ba',
				'.com.bd',
				'.be',
				'.bf',
				'.bg',
				'.com.bh',
				'.bi',
				'.bj',
				'.com.bn',
				'.com.bo',
				'.com.br',
				'.bs',
				'.bt',
				'.co.bw',
				'.by',
				'.com.bz',
				'.ca',
				'.com.kh',
				'.cc',
				'.cd',
				'.cf',
				'.cat',
				'.cg',
				'.ch',
				'.ci',
				'.co.ck',
				'.cl',
				'.cm',
				'.cn',
				'.com.co',
				'.co.cr',
				'.com.cu',
				'.cv',
				'.com.cy',
				'.cz',
				'.de',
				'.dj',
				'.dk',
				'.dm',
				'.com.do',
				'.dz',
				'.com.ec',
				'.ee',
				'.com.eg',
				'.es',
				'.com.et',
				'.fi',
				'.com.fj',
				'.fm',
				'.fr',
				'.ga',
				'.ge',
				'.gf',
				'.gg',
				'.com.gh',
				'.com.gi',
				'.gl',
				'.gm',
				'.gp',
				'.gr',
				'.com.gt',
				'.gy',
				'.com.hk',
				'.hn',
				'.hr',
				'.ht',
				'.hu',
				'.co.id',
				'.iq',
				'.ie',
				'.co.il',
				'.im',
				'.co.in',
				'.io',
				'.is',
				'.it',
				'.je',
				'.com.jm',
				'.jo',
				'.co.jp',
				'.co.ke',
				'.ki',
				'.kg',
				'.co.kr',
				'.com.kw',
				'.kz',
				'.la',
				'.com.lb',
				'.com.lc',
				'.li',
				'.lk',
				'.co.ls',
				'.lt',
				'.lu',
				'.lv',
				'.com.ly',
				'.co.ma',
				'.md',
				'.me',
				'.mg',
				'.mk',
				'.ml',
				'.com.mm',
				'.mn',
				'.ms',
				'.com.mt',
				'.mu',
				'.mv',
				'.mw',
				'.com.mx',
				'.com.my',
				'.co.mz',
				'.com.na',
				'.ne',
				'.com.nf',
				'.com.ng',
				'.com.ni',
				'.nl',
				'.no',
				'.com.np',
				'.nr',
				'.nu',
				'.co.nz',
				'.com.om',
				'.com.pk',
				'.com.pa',
				'.com.pe',
				'.com.ph',
				'.pl',
				'.com.pg',
				'.pn',
				'.com.pr',
				'.ps',
				'.pt',
				'.com.py',
				'.com.qa',
				'.ro',
				'.rs',
				'.ru',
				'.rw',
				'.com.sa',
				'.com.sb',
				'.sc',
				'.se',
				'.com.sg',
				'.sh',
				'.si',
				'.sk',
				'.com.sl',
				'.sn',
				'.sm',
				'.so',
				'.st',
				'.sr',
				'.com.sv',
				'.td',
				'.tg',
				'.co.th',
				'.com.tj',
				'.tk',
				'.tl',
				'.tm',
				'.to',
				'.tn',
				'.com.tr',
				'.tt',
				'.com.tw',
				'.co.tz',
				'.com.ua',
				'.co.ug',
				'.co.uk',
				'.us',
				'.com.uy',
				'.co.uz',
				'.com.vc',
				'.co.ve',
				'.vg',
				'.co.vi',
				'.com.vn',
				'.vu',
				'.ws',
				'.co.za',
				'.co.zm',
				'.co.zw',
			);

			if ( preg_match( '/^https:\/\/[a-z]+?\.?google(' . implode( '|', array_map( 'preg_quote', $googleTLDs ) ) . ')\/maps\/(.+)/i', (string) $url, $matches ) )
			{
				/* Extract the address and gps coordinates from the query string */
				$qbits = explode( "/", $matches[2] );

				switch ( $qbits[0] )
				{
					case 'place':
						/* This seems odd but sometimes the place names can already be url encoded and we don't want to double encode */
						return Theme::i()->getTemplate( 'embed', 'core', 'global' )->googleMaps( urlencode( urldecode( $qbits[1] ) ), 'place' );

					case 'dir':
						/* Let's do some cleanup - we may have 'waypoints' in between the origin and destination, but Google doesn't tell us that from the URL */
						$route = array();
						foreach( $qbits AS $bit )
						{
							/* Skip these as we know for sure that they are not a part of the route. */
							if (
								$bit === 'dir' # This is safe because we already know we're requesting directions
								OR mb_substr( $bit, 0, 1 ) === '@' # This is safe because Google only looks for three value types to find a place in a route (Name, Address, or PlaceID) - Lat/Long is not one of them
								OR mb_substr( $bit, 0, 5 ) === 'data=' # This is simply miscellaneous data which we do not need
							)
							{
								continue;
							}
							
							$route[] = $bit;
						}
						
						/* Assign our origin, which will always be at the start */
						$origin = array_shift( $route );
						
						/* And our destination, which will always be at the end */
						$destination = array_pop( $route );
						
						/* If we have anything left, then they are waypoints which need to be shown between the origin and destination - do that */
						if ( count( $route ) )
						{
							$params = array( 'origin' => $origin, 'waypoints' => implode( '|', $route ), 'destination' => $destination );
						}
						else
						/* Otherwise, just pass the start and end */
						{
							$params = array( 'origin' => $origin, 'destination' => $destination );
						}
						
						return Theme::i()->getTemplate( 'embed', 'core', 'global' )->googleMaps( $params, 'dir' );

					case 'search':
						return Theme::i()->getTemplate( 'embed', 'core', 'global' )->googleMaps( urlencode( urldecode( $qbits[1] ) ), 'search' );

					default:
						$params = explode( ",", mb_substr( $qbits[0], 1, -1 ) );
						$coordinates = implode( "," , array( $params[0], $params[1]) );
						return  Theme::i()->getTemplate( 'embed', 'core', 'global' )->googleMaps( $coordinates, 'coordinates', $params[2] );
				}
			}
		}
		
		/* Brightcove */
		$domain = $url->data['host'];
		if( !$domain )
		{
			return null;
		}

		if( $domain == 'bcove.video' or $domain == 'players.brightcove.net' )
		{
			/* If using a shortcode we need to redirect to the full URL */
			if( $domain == 'bcove.video' )
			{
				try
				{
					$response = $url->request( null, null, 0 )->get();

					if( $response->httpResponseCode == 301 )
					{
						$url = Url::external( $response->httpHeaders['Location'] );
					}
					else
					{
						throw new \IPS\Http\Request\Exception;
					}
				}
				catch( \IPS\Http\Request\Exception $e )
				{
					throw new UnexpectedValueException( 'embed__fail_500',  (float) static::EMBED_VIDEO );
				}
			}
			
			return Theme::i()->getTemplate( 'embed', 'core', 'global' )->brightcove( $url );
		}

		/* So if it's not that, just return */
		return NULL;
	}
	
	/**
	 * Internal Embed Code
	 *
	 * @param	Url				$url		URL
	 * @param 	Member|null 	$member		Member to check permissions against or NULL for currently logged in member
	 *
	 * @return	string|null
	 */
	protected static function _internalEmbed( Url $url, Member $member=NULL ): ?string
	{
		/* If this URL has a #comment-123 fragment, change it to the findComment URL so the comment embeds rather than the item */
		if ( isset( $url->data['fragment'] ) and mb_stristr( $url->data['fragment'], 'comment-' ) and empty( $url->queryString['do'] ) )
		{
			$url = $url->setQueryString( array( 'do' => 'findComment', 'comment' => preg_replace( '#(find)?comment-#i', '', $url->data['fragment'] ) ) );
		}

		/* And for reviews */
		if ( isset( $url->data['fragment'] ) and mb_stristr( $url->data['fragment'], 'review-' ) and empty( $url->queryString['do'] ) )
		{
			$url = $url->setQueryString( array( 'do' => 'findReview', 'review' => preg_replace( '#(find)?review-#i', '', $url->data['fragment'] ) ) );
		}

		/* Get the "real" query string (whatever the query string is, plus what we can get from decoding the FURL) */
		$qs = array_merge( $url->queryString, $url->hiddenQueryString );
		
		/* We need an app, and it needs to not be an RSS link */
		if ( !isset( $qs['app'] ) or ( isset( $qs['do'] ) and $qs['do'] === 'rss' ) )
		{
			return NULL;
		}
		
		/* Load the application, but be aware it could be an old invalid URL if this is an upgrade */
		try
		{
			$application = Application::load( $qs['app'] );
		}
		catch( OutOfRangeException|UnexpectedValueException ) // Application does not exist | Application is out of date
		{
			return NULL;
		}
		
		/* Loop through our content classes and see if we can find one that matches */
		foreach ( $application->extensions( 'core', 'ContentRouter' ) as $extension )
		{
			/* We need to check the class itself, along with owned nodes (Blogs, etc.) and anything else which isn't 
				normally part of the content item system but the app wants to be embeddable (Commerce product reviews, etc) */
			$classes = $extension->classes;
			if ( isset( $extension->ownedNodes ) )
			{
				$classes = array_merge( $classes, $extension->ownedNodes );
			}
			if ( isset( $extension->embeddableContent ) )
			{
				$classes = array_merge( $classes, $extension->embeddableContent );
			}
			
			/* But we're only interested in classes which implement IPS\Content\Embeddable */
			$classes = array_filter( $classes, function( $class ) {
				return in_array( 'IPS\Content\Embeddable', class_implements( $class ) );
			} );
						
			/* So for each of those... */
			foreach ( $classes as $class )
			{
				/* Try to load it */
				try
				{
					$item = $class::loadFromURL( $url );

					$member = $member ?? Member::loggedIn();

					$canView = TRUE;
					if ( $item instanceof Content or $item instanceof Club )
					{
						$canView = $item->canView( $member );
					}
					elseif ( $item instanceof Model )
					{
						$canView = $item->can( 'view', $member );
					}

					if( !$canView )
					{
						throw new InvalidArgumentException;
					}
				}
				catch ( Exception $e )
				{
					continue;
				}
				
				/* It needs to be embeddable... */
				if( !in_array( 'IPS\Content\Embeddable', class_implements( $item ) ) )
				{
					continue;
				}
				
				/* The URL needs to actually match... */
				$urlDiff = array_diff_assoc( $qs, array_merge( $item->url()->queryString, $item->url()->hiddenQueryString ) );
				if ( count( array_intersect( array( 'app', 'module', 'controller' ), array_keys( $urlDiff ) ) ) )
				{
					continue;
				}
				
				/* Okay, get the correct embed URL! */
				try
				{
					$preview = Url::createFromString( (string) $url );
				}
				catch ( Url\Exception )
				{
					throw new UnexpectedValueException( 'embed__fail_500', static::EMBED_LINK );
				}

				/* Add the author ID for notifications */
				try
				{
					if ( $item instanceof Club )
					{
						$author = $item->owner->member_id;
					}
					else
					{
						$author = ( method_exists( $item, 'author' ) ) ? $item->author()->member_id : 0;
					}
				}
				catch ( Exception )
				{
					$author = 0;
				}

				/* Strip the CSRF key if present - normally shouldn't be, but just in case..*/
				$preview = $preview->setQueryString( 'do', 'embed' )->stripQueryString( 'csrfKey' );

				/* If this is a comment or review, set a query parameter for that and recheck the author */
				if ( isset( $url->queryString['do'] ) and $url->queryString['do'] == 'findComment' )
				{
					$preview = $preview->setQueryString( 'embedComment', $url->queryString['comment'] );

					/* Get the correct author ID */
					$commentClass = $item::$commentClass;

					try
					{
						$comment	= $commentClass::loadAndCheckPerms( $url->queryString['comment'] );
						$author		= $comment->author()->member_id;
					}
					catch( Exception ){}
				}
				elseif ( isset( $url->queryString['do'] ) and $url->queryString['do'] == 'findReview' )
				{
					$preview = $preview->setQueryString( 'embedReview', $url->queryString['review'] );

					/* Get the correct author ID */
					$reviewClass = $item::$reviewClass;

					try
					{
						$review		= $reviewClass::loadAndCheckPerms( $url->queryString['review'] );
						$author		= $review->author()->member_id;
					}
					catch( Exception $e ){}
				}
				if( isset( $url->queryString['do'] ) )
				{
					$preview = $preview->setQueryString( 'embedDo', $url->queryString['do'] );
				}
				if ( isset( $url->queryString['page'] ) AND $url->queryString['page'] > 1 )
				{
					$preview = $preview->setPage( 'page', $url->queryString['page'] );
				}

				/* And return */
				$contentClass = str_replace( '\\', '_', trim( $item::class, '\\' ) );
				if ( str_starts_with( $contentClass, 'IPS_' ) )
				{
					$contentClass = substr( $contentClass, 4 );
				}
				$idCol = $item::$databaseColumnId;
				$contentId = $item->$idCol;
				$embed = Theme::i()->getTemplate( 'embed', 'core', 'global' )->internal( $preview, $author, $qs['app'], $contentClass, $contentId, time(), $comment ?? null );
				Member::loggedIn()->language()->parseOutputForDisplay( $embed );
				return $embed;
			}
		}
		
		/* Still here? Not an internal embed */
		return NULL;
	}
	
	/**
	 * Image Embed Code
	 *
	 * @param	string|Url	$url		URL to image (that you know is an image)
	 * @param int $width		Image width (the actual value, which this method will auto-adjust if it exceeds our allowed size)
	 * @param int $height		Image height (the actual value, which this method will auto-adjust if it exceeds our allowed size)
	 * @return	string|null
	 */
	public static function imageEmbed( string|Url $url, int $width, int $height ): ?string
	{
		/* If the URL is blacklisted, just return it */
		try
		{
			static::isAllowedContentUrl( $url );
		}
		catch( UnexpectedValueException $e )
		{
			return (string) $url;
		}

		$maxImageDims = Settings::i()->attachment_image_size ? explode( 'x', Settings::i()->attachment_image_size ) : array( 1000, 750 );
		$widthToUse = $width;
		$heightToUse = $height;

		$maxImageDims = array_map('intval', $maxImageDims);

		/* 0x0 means unlimited, so only do these calculations if a specific size has been set */
		if( intval( $maxImageDims[0] ) !== 0 || intval( $maxImageDims[1] ) !== 0 )
		{
			/* Adjust the width/height according to our maximum dimensions */
			if ( $width > $maxImageDims[0] )
			{
				$widthToUse	= $maxImageDims[0];
				$heightToUse = floor( $height / $width * $widthToUse );

				if ( $heightToUse > $maxImageDims[1] )
				{
					$widthToUse	= floor( $maxImageDims[1] * ( $widthToUse / $heightToUse ) );
					$heightToUse = $maxImageDims[1];
				}
			}
			elseif( $height > $maxImageDims[1] )
			{
				$heightToUse	= $maxImageDims[1];
				$widthToUse = floor( $width / $height * $heightToUse );

				if ( $widthToUse > $maxImageDims[0] )
				{
					$heightToUse	= floor( $maxImageDims[0] * ( $heightToUse / $widthToUse ) );
					$widthToUse = $maxImageDims[0];
				}
			}
		}
		
		/* And return the embed */
		return Theme::i()->getTemplate( 'embed', 'core', 'global' )->photo( $url, NULL, NULL, $widthToUse, $heightToUse );
	}
	
	/* !Utility Methods */
		
	/**
	 * Parse statically
	 *
	 * @param string $value				The value to parse
	 * @param array|null $attachIds			array of ID numbers to idenfity content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, an MD5 hash used to claim attachments after saving.
	 * @param Member|null $member				The member posting, NULL will use currently logged in member.
	 * @param bool|string $area				If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @param bool $filterProfanity	Remove profanity?
	 * @param callback|null $htmlPurifierConfig	A function which will be passed the HTMLPurifier_Config object to customise it - see example
	 * @return	string
	 * @see		__construct
	 */
	public static function parseStatic( string $value, array $attachIds=NULL, Member $member=NULL, bool|string $area=FALSE, bool $filterProfanity=TRUE, callable $htmlPurifierConfig=NULL ): string
	{
		$obj = new static( $attachIds, $member, $area, $filterProfanity, $htmlPurifierConfig );
		return $obj->parse( $value );
	}
		
	/**
	 * Remove specific elements, useful for cleaning up content for display or truncating
	 *
	 * @param string $value			The value to parse
	 * @param array|string $elements		Element to remove, or array of elements to remove. Can be in format "element[attribute=value]"
	 * @return	string
	 */
	public static function removeElements( string $value, array|string $elements=array( 'blockquote', 'img', 'a' ) ): string
	{
		/* Init */
		$elementsToRemove = is_string( $elements ) ? array( $elements ) : $elements;
		
		/* Do it */
		return DOMParser::parse( $value, function(DOMElement $element, DOMNode $parent, DOMParser $parser ) use ( $elementsToRemove )
		{
			/* Check all of the $elementsToRemove */
			foreach( $elementsToRemove as $definition )
			{
				/* If this is in the element[attribute=value] format... */
				if ( mb_strstr( $definition, '[' ) and mb_strstr( $definition, '=' ) )
				{
					/* Break it up */
					preg_match( '#^([a-z]+?)\[([^\]]+?)\]$#i', $definition, $matches );
					
					/* If the element tag name matches the first bit... */
					if( $element->tagName == $matches[1] )
					{
						/* Break up the definition into name and value */
						[ $attribute, $value ] = explode( '=', trim( $matches[2] ) );
						
						/* Remove quotes */
						$value = str_replace( array( '"', "'" ), '', $value );
						
						/* If it matches, return to skip this element. */
						if ( $element->getAttribute( $attribute ) == $value )
						{
							return;
						}
					}
				}
				/* Or if it's just in normal format, check it and if it matches, return to skip this element. */
				else if ( $element->tagName == $definition )
				{
					return;
				}
			}
			
			/* If we're still here, it's fine and we can import it */
			$ownerDocument = $parent->ownerDocument ?: $parent;
			$newElement = $ownerDocument->importNode( $element );
			$parent->appendChild( $newElement );
			
			/* And continue to children */
			$parser->_parseDomNodeList( $element->childNodes, $newElement );
		} );		
	}
	
	/**
	 * Removes HTML and optionally truncates content
	 *
	 * @param string $content	The text to truncate
	 * @param bool $oneLine	If TRUE, will use spaces instead of line breaks. Useful if using a single line display.
	 * @param int|null $length		If supplied, and $oneLine is set to TRUE, the returned content will be truncated to this length
	 * @return	string
	 * @note	For now we are removing all HTML. If we decide to change this to remove specific tags in future, we can use \IPS\Text\Parser::removeElements( $this->content() )
	 */
	public static function truncate( string $content, bool $oneLine=FALSE, ?int $length=500 ): string
	{	
		/* Specifically remove quotes, any scripts (which someone with HTML posting allowed may have legitimately enabled, and spoilers (to prevent contents from being revealed) */
		$text = static::removeElements( $content, array( 'blockquote', 'script', 'div[class=ipsSpoiler]' ) );
		
		/* Convert headers and paragraphs into line breaks or just spaces */
		$text = str_replace( array( '</p>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>' ), ( $oneLine ? ' ' : '<br>' ), $text );

		if( $oneLine === TRUE )
		{
			$text = str_replace( '<br>', ' ', $text );
		}

		/* Add a space at the end of list items to prevent two list items from running into each other */
		$text = str_replace( '</li>', ' </li>', $text );
		
		/* Remove all HTML apart from <br>s*/
		$text = strip_tags( $text, ( $oneLine === TRUE ) ? NULL : '<br>' );
		
		/* Remove any <br>s from the start so there isn't just blank space at the top, but maintaining <br>s elsewhere */
		$text = preg_replace( '/^(\s|<br>|' . chr(0xC2) . chr(0xA0) . ')+/', '', $text );

		/* Truncate to length, if appropriate */
		if( $oneLine === TRUE AND $length > 0 )
		{
			$text = mb_substr( $text, 0, $length );
		}
		
		/* Return */
		return $text;
	}
	
	/**
	 * @brief	Emoticons
	 */
	protected static ?array $emoticons = NULL;
	
	/**
	 * Rebuild attachment urls
	 *
	 * @param string $textContent	Content
	 * @return    string|bool    False, or rebuilt content
	 */
	public static function rebuildAttachmentUrls( string $textContent ): string|bool
	{
		$rebuilt	= FALSE;
		
		$textContent = preg_replace( '#<([^>]+?)(href|src)=(\'|")<fileStore\.([\d\w\_]+?)>/#i', '<\1\2=\3%7BfileStore.\4%7D/', $textContent );
		$textContent = preg_replace( '#<([^>]+?)(href|src)=(\'|")<___base_url___>/#i', '<\1\2=\3%7B___base_url___%7D/', $textContent );
		$textContent = preg_replace( '#<([^>]+?)(data-(fileid|ipshover\-target))=(\'|")<___base_url___>/#i', '<\1\2=\3%7B___base_url___%7D/', $textContent );
		
		/* srcset can have multiple urls in it */
		preg_match_all( '#<(?:[^>]+?)srcset=(\'|")([^\'"]+?)(\1)#i', $textContent, $srcsetMatches, PREG_SET_ORDER );
		
		foreach( $srcsetMatches as $val )
		{
			if ( mb_stristr( $val[2], '<___base_url___>' ) )
			{
				$textContent = str_replace( $val[2], str_replace( '<___base_url___>', '%7B___base_url___%7D', $val[2] ), $textContent );
			}
		}
		
		/* Create DOMDocument */
		$content = new DOMDocument( '1.0', 'UTF-8' );
		@$content->loadHTML( DOMDocument::wrapHtml( $textContent ) );
		
		$xpath = new DOMXpath( $content );
		
		foreach ( $xpath->query('//img') as $image )
		{
			if( $image->getAttribute( 'data-fileid' ) )
			{
				try
				{
					$attachment	= Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $image->getAttribute( 'data-fileid' ) ) )->first();
					$image->setAttribute( 'src', '{fileStore.core_Attachment}/' . ( $attachment['attach_thumb_location'] ?: $attachment['attach_location'] ) );
					
					$anchor = $image->parentNode;
					
					/* Make sure it's actually an anchor */
					if ( $anchor->tagName !== 'a' )
					{
						/* It's not, so create one and add the image to it */
						$parent = $image->parentNode;
						$clonedImage = clone $image;
						$anchor = $content->createElement( 'a' );
						$anchor->setAttribute( 'href', '{fileStore.core_Attachment}/' . $attachment['attach_location'] );
						$anchor->appendChild( $clonedImage );
						$parent->replaceChild( $anchor, $image );
					}
					else
					{
						$anchor->setAttribute( 'href', '{fileStore.core_Attachment}/' . $attachment['attach_location'] );
					}
					
					$rebuilt = TRUE;
				}
				catch ( Exception $e ) { }
			}
			else
			{
				if ( ! isset( static::$fileObjectClasses['core_Emoticons'] ) )
				{
					static::$fileObjectClasses['core_Emoticons'] = File::getClass('core_Emoticons' );
				}
				
				if ( static::$emoticons === NULL )
				{
					static::$emoticons = array();
		
					try
					{
						foreach ( Db::i()->select( 'image, image_2x, width, height', 'core_emoticons' ) as $row )
						{
							static::$emoticons[] = $row;
						}
					}
					catch(Db\Exception $ex )
					{
						/* The image_2x column was added in 4.1 so may not exist if Parser is used in previous upgrade modules */
						foreach ( Db::i()->select( 'image, NULL as image_2x, 0 as width, 0 as height', 'core_emoticons' ) as $row )
						{
							static::$emoticons[] = $row;
						}
					}
				}

				if ( ( $image->tagName === 'img' and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $image->getAttribute('src'), $matches ) ) )
				{
					foreach( static::$emoticons as $emo )
					{
						if ( $emo['image'] == $matches[2] )
						{
							$image->setAttribute( 'src', '{fileStore.core_Emoticons}/' . $matches[2] );

							if( $emo['image_2x'] && $emo['width'] && $emo['height'] )
							{
								/* Retina emoticons require a width and height for proper scaling */
								$image->setAttribute( 'srcset', '%7BfileStore.core_Emoticons%7D/' . $emo['image_2x'] . ' 2x' );
								$image->setAttribute( 'width', $emo['width'] );
								$image->setAttribute( 'height', $emo['height'] );
							}
							$rebuilt = TRUE;
						}
					}
				}
			}
		}

		if( $rebuilt )
		{
			$value = $content->saveHTML();
			
			$value = preg_replace( '/<meta http-equiv(?:[^>]+?)>/i', '', preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>', '<head>', '</head>' ), '', $value ) ) );
			
			
			/* Replace any {fileStore.whatever} tags with <fileStore.whatever> */
			return static::replaceFileStoreTags( $value );
		}

		return FALSE;
	}
	
	/**
	 * Perform a safe html_entity_decode if you are not using UTF-8 MB4
	 *
	 * @param string $value	Value to html entity decode
	 * @return	string
	 */
	public static function utf8mb4SafeDecode( string $value ): string
	{
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

		if ( Settings::i()->getFromConfGlobal('sql_utf8mb4') !== TRUE )
		{
			$value = preg_replace_callback( '/[\x{10000}-\x{10FFFF}]/u', function( $mb4Character ) {
				return mb_convert_encoding( $mb4Character[0], 'HTML-ENTITIES', 'UTF-8' );
			}, $value );
		}

		return $value;
	}
	
	/**
	 * Does this post contain a quote by member?
	 *
	 * @param int $memberId		I dunno, take a wild guess. We don't pass a member object as they may have been deleted.
	 * @param string $content		Content, that's "content", not "content". I'm data, not relaxed.
	 * @return	boolean
	 */
	public static function containsQuoteBy( int $memberId, string $content ): bool
	{
		if ( $memberId and $content )
		{
			return preg_match( '#data-ipsquote-userid=[\'"]' . $memberId . '[\'"]#i', $content );
		}
	
		return FALSE;
	}

	/**
	 * Extract all quote data from a post
	 *
	 * @param string $content
	 * @param string|array|null $allowedApps Allowed apps; Defaults to NULL (includes all apps), or an array of apps to allow
	 * @param string|array|null $allowedContentClasses
	 * @return array
	 */
	public static function extractAllQuotesData( string $content, string|array $allowedApps=null, string|array $allowedContentClasses=null ): array
	{
		$quotes = array();

		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->loadHTML( DOMDocument::wrapHtml( $content ) );
		$xpath = new DOMXPath($doc);
		$query = "//blockquote[@data-ipsquote]";
		$allowedContentClasses = is_string( $allowedContentClasses ) ? [ $allowedContentClasses ] : $allowedContentClasses;
		$allowedApps = is_string( $allowedApps ) ? [ $allowedApps ] : $allowedApps;

		if ( is_array( $allowedApps ) AND count( $allowedApps ) )
		{
			$selectors = [];
			foreach ( $allowedApps as $app )
			{
				$selectors[] = "@data-ipsquote-contentapp='{$app}'";
			}
			$query .= '['.implode( ' or ', $selectors ).']';
		}

		if ( is_array( $allowedContentClasses ) AND count( $allowedContentClasses ) )
		{
			$selectors = [];
			foreach( $allowedContentClasses as $_contentClass )
			{
				$contentClass = str_replace( "\\", '_', trim( $_contentClass, '\\' ) );
				if ( str_starts_with( $contentClass, 'IPS_' ) )
				{
					$contentClass = substr( $contentClass, 4 );
				}
				$selectors[] = "@data-ipsquote-contentclass='{$contentClass}'";
			}
			$query .= '[' . implode( ' or ', $selectors ) . ']';
		}

		foreach( $xpath->query( $query ) as $element )
		{
			$quotes[] = array(
				'app'		=> $element->getAttribute('data-ipsquote-contentapp'),
				'itemClass'	=> 'IPS\\' . str_replace( '_', '\\', $element->getAttribute('data-ipsquote-contentclass') ),
				'itemId'	=> (int) $element->getAttribute('data-ipsquote-contentid'),
				'commentId'	=> (int) $element->getAttribute('data-ipsquote-contentcommentid'),
				'userId'	=> (int) $element->getAttribute('data-ipsquote-userid'),
				'time'		=> (int) $element->getAttribute('data-ipsquote-timestamp'),
			);
		}

		return $quotes;
	}

	/**
	 * Extract all embed data from a post
	 *
	 * @param string $content
	 * @param string|array|null $allowedApps Allowed apps; Defaults to NULL (includes all apps), or an array of apps to allow
	 * @param string|array|null $allowedContentClasses
	 *
	 * @return array
	 */
	public static function extractAllEmbedsData( string $content, string|array $allowedApps=null, string|array $allowedContentClasses=null ): array
	{
		$embeds = array();

		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->loadHTML( DOMDocument::wrapHtml( "<div>{$content}</div>" ) );
		$xpath = new DOMXPath( $doc );
		$allowedContentClasses = is_string( $allowedContentClasses ) ? [ $allowedContentClasses ] : $allowedContentClasses;
		$allowedApps = is_string( $allowedApps ) ? [ $allowedApps ] : $allowedApps;
		$query = "//iframe[@data-embedauthorid]";

		if ( is_array( $allowedApps ) AND count( $allowedApps ) )
		{
			$selectors = [];
			foreach ( $allowedApps as $app )
			{
				$selectors[] = "@data-ipsembed-contentapp='{$app}'";
			}
			$query .= '['.implode( ' or ', $selectors ).']';
		}

		if ( is_array( $allowedContentClasses ) AND count( $allowedApps ) )
		{
			$selectors = [];
			foreach( $allowedContentClasses as $_contentClass )
			{
				$contentClass = str_replace( "\\", '_', trim( $_contentClass, '\\' ) );
				if ( str_starts_with( $contentClass, 'IPS_' ) )
				{
					$contentClass = substr( $contentClass, 4 );
				}
				$selectors[] = "@data-ipsembed-contentclass='{$contentClass}'";
			}
			$query .= '[' . implode( ' or ', $selectors ) . ']';
		}


		/* Embeds */
		foreach( $xpath->query( $query ) as $element )
		{
			$embeds[] = array(
				'app'		=> $element->getAttribute('data-ipsembed-contentapp'),
				'itemClass'	=> 'IPS\\' . str_replace( '_', '\\', $element->getAttribute('data-ipsembed-contentclass') ),
				'itemId'	=> (int) $element->getAttribute('data-ipsembed-contentid'),
				'commentId'	=> (int) $element->getAttribute('data-ipsembed-contentcommentid'),
				'userId'	=> (int) $element->getAttribute('data-embedauthorid'),
				'time'		=> (int) $element->getAttribute('data-ipsembed-timestamp'),
			);
		}

		return $embeds;
	}
	 
	/**
	 * Remove quote attribution (sets it to guest )
	 *
	 * @param int $memberId	Member ID. We don't pass a member object as they may have been deleted.
	 * @param string $content	Still Content
	 * @return	boolean
	 */
	public static function removeQuoteAttributionBy( int $memberId, string $content ): bool
	{
		/* Do it */
		return DOMParser::parse( $content, function(DOMElement $element, DOMNode $parent, DOMParser $parser ) use( $memberId )
		{
			/* If the element tag name matches the first bit... */
			if( $element->tagName == 'blockquote' )
			{
				if ( $element->getAttribute('data-ipsquote-userid') == $memberId )
				{
					$element->setAttribute('data-ipsquote-userid', 0 );
					
					if ( $element->hasAttribute('data-ipsquote-username') )
					{
						$element->setAttribute('data-ipsquote-username', '' );
					}
					
					foreach( $element->childNodes AS $child )
					{ 
						if ( $child instanceof DOMElement and mb_strpos( $child->getAttribute('class'), 'ipsQuote_citation' ) !== FALSE )
						{
							$replace = new DOMElement( 'div' );
							$element->replaceChild( $replace, $child );
							$replace->setAttribute('class', 'ipsQuote_citation' );
						}
					}
				}
			}
			
			/* If we're still here, it's fine and we can import it */
			$ownerDocument = $parent->ownerDocument ?: $parent;
			$newElement = $ownerDocument->importNode( $element );
			$parent->appendChild( $newElement );
			
			/* And continue to children */
			$parser->_parseDomNodeList( $element->childNodes, $newElement );
		} );
	}

	/**
	 * Checks if there is actual content or if this was just an empty editor submission
	 *
	 * @param string $text	Text that came from an editor
	 * @return	bool
	 */
	public static function hasContent( string $text ): bool
	{
		if( !trim( $text ) )
		{
			return FALSE;
		}

		if( preg_match( "/^\s*<p>\s*(<br[^>]*>|&nbsp;|\u00A0|&#160;)?\s*<\/p>\s*$/i", $text ) )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Parse attachment labels and pull the required data
	 *
	 * @param array $attachment	Row from core_attachments
	 * @return array
	 */
	public static function getAttachmentLabels( array $attachment ): array
	{
        $labels = [];
        if ( is_string( $attachment['attach_labels'] ) AND $data = json_decode( $attachment['attach_labels'], TRUE ) and is_array( $data ) )
        {
            foreach( $data as $label )
            {
                $labels[] = $label['Name'];
            }
        }

        return $labels;
	}

	/**
	 * Gets the emoji index from the json
	 *
	 * @todo this should be cached to prevent unnecessary repeated JSON-decoding
	 *
	 * @return array|null
	 * @throws DomainException
	 */
	public static function getEmojiIndex() : array|null
	{
		static $index = null;
		if ( $index === null )
		{
			foreach ( [ 'emoji', 'skinTones', 'hairStyles' ] as $file )
			{
				$index[$file] = json_decode( file_get_contents( ROOT_PATH . '/applications/core/data/'.$file.'.json' ), true );
				if ( !is_array( $index[$file] ) )
				{
					throw new DomainException;
				}
			}
		}
		return $index;
	}
}