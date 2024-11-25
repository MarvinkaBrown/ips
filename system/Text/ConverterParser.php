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

use DOMElement;
use DOMNode;
use DOMText;
use DOMXpath;
use Exception;
use HTMLPurifier;
use IPS\Application;
use IPS\core\Profanity;
use IPS\Db;
use IPS\forums\Topic\Post;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Settings;
use IPS\Xml\DOMDocument;
use OutOfRangeException;
use function count;
use function defined;
use function function_exists;
use function in_array;
use function is_array;
use function is_string;
use function substr;
use const IPS\DEFAULT_REQUEST_TIMEOUT;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Text Parser
 */
class ConverterParser extends Parser
{
	/**
	 * @brief	Regex for detecting email addresses
	 */
	const EMAIL_REGEX = '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,9}';

	/* !Parser: Bootstrap */

	/**
	 * @brief	If parsing BBCode, the supported BBCode tags
	 */
	protected ?array $bbcode = NULL;
				
	/**
	 * Constructor
	 *
	 * @param bool $bbcode				Parse BBCode?
	 * @param mixed $attachIds			array of ID numbers to idenfity content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, an MD5 hash used to claim attachments after saving.
	 * @param	Member|null		$member				The member posting, NULL will use currently logged in member.
	 * @param bool|string $area				If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @param bool $filterProfanity	Remove profanity?
	 * @param bool $cleanHtml			If TRUE, HTML will be cleaned through HTMLPurifier
	 * @param callback|null $htmlPurifierConfig	A function which will be passed the HTMLPurifier_Config object to customise it - see example
	 * @param bool $parseAcronyms		Parse acronyms?
	 * @param	?int $attachIdsLang		Language ID number if this Editor is part of a Translatable field.
	 * @return	void
	 */
	public function __construct(bool $bbcode=FALSE, mixed $attachIds=NULL, Member $member=NULL, bool|string $area=FALSE, bool $filterProfanity=TRUE, bool $cleanHtml=TRUE, callable $htmlPurifierConfig=NULL, bool $parseAcronyms=TRUE, int $attachIdsLang=NULL )
	{
		/*  Set the Member */
		$this->member = $member ?: Member::loggedIn();

		/* Set the member and area */
		if ( $bbcode or $attachIds )
		{
			$this->area = $area;
		}
		
		/* Get available BBCodes */
		if ( $bbcode )
		{
			$this->bbcode = static::bbcodeTags( $this->member, $this->area );
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
		if ( $cleanHtml )
		{
			if ( !function_exists('idn_to_ascii') )
			{
				IPS::$PSR0Namespaces['TrueBV'] = ROOT_PATH . "/system/3rd_party/php-punycode";
				require_once ROOT_PATH . "/system/3rd_party/php-punycode/polyfill.php";
			}
			require_once ROOT_PATH . "/system/3rd_party/HTMLPurifier/HTMLPurifier.auto.php";
			$this->htmlPurifier = new HTMLPurifier( $this->_htmlPurifierConfiguration( $htmlPurifierConfig ) );
		}
				
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
	 * Force bbcode parsing enabled
	 *
	 * @return void
	 */
	public function __set( $name, $enabled = TRUE )
	{
		if( $name == 'forceBbcodeEnabled' )
		{
			$this->forceBbcodeEnabled = $enabled;

			if( $enabled )
			{
				$this->bbcode = static::bbcodeTags( $this->member, $this->area );
			}
			else
			{
				$this->bbcode = NULL;
			}
		}
	}
	
	/**
	 * @brief	The closing BBCode tags we are looking for and how many are open
	 */
	protected array $closeTagsForOpenBBCode = array();
	
	/**
	 * @brief	Open Inline BBCode tags
	 */
	protected array $openInlineBBCode = array();
	
	/**
	 * @brief	All open Block-Level BBCode tags
	 */
	protected array $openBlockBBCodeByTag = array();
	
	/**
	 * @brief	All open Block-Level BBCode tags in the order they were created
	 */
	protected array $openBlockBBCodeInOrder = array();
		
	/**
	 * @brief	Open Block-Level BBCode tags
	 */
	protected ?int $openBlockDepth = NULL;

	/**
	 * @brief	Force BBCode enabled (used by 4.0 upgrader parsing and converters)
	 * @see		set_forceBbcodeEnabled()
	 */
	protected bool $forceBbcodeEnabled = FALSE;

	/**
	 * @brief	This is used to stop BBCode parsing temporarily (such as in [code] tags)
	 */
	protected bool $bbcodeParse = TRUE;
	
	/**
	 * @brief	If we have opened a BBCode tag which we don't parse other BBCode inside, the string at which we will resume parsing
	 */
	protected ?string $resumeBBCodeParsingOn = NULL;
	
	/**
	 * Parse BBCode, Profanity, etc. by loading into a DOMDocument
	 *
	 * @param string $value	HTML to parse
	 * @return	string
	 */
	protected function _parseContent( string $value ): string
	{
		/* This fix resolves an issue using <br> mode where BBCode tags are wrapped in P tags like so: <p>[tag]</p><p>Content</p><p>[/tag]</p> tags.
		   The fix just removes the </p><p> tags inside block BBCode tags, so our example ends up parsing like so: <p>[tag]<br><br>Content<br><br>[/tag]</p>
		   We will want to find a more elegant fix for this at some point */
		if ( $this->bbcode !== NULL and ! Settings::i()->editor_paragraph_padding )
		{
			$blockTags = array();
			foreach( $this->bbcode as $tag => $data )
			{
				if ( ! empty( $data['block'] ) )
				{
					$blockTags[] = $tag;
				}
			}

			if ( count( $blockTags ) )
			{
				/* If we are inside block tags, ensure that </p> <p> tags are converted to <br> to prevent parser confusion */
				preg_match_all( '#\[(' . implode( '|', $blockTags ) . ')\](.+?)\[/\1\]#si', $value, $matches, PREG_SET_ORDER );
				
				foreach( $matches as $id => $match )
				{
					$value = str_replace( $match[0], preg_replace( '#</p>\s{0,}<p([^>]+?)?'.'>#i', '<br><br>', $match[0] ), $value );
				}
			}
		}
		
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
				
		/* [page] tags need to be handled specially */
		if ( $this->bbcode !== NULL and $this->containsPageTags )
		{
			$body = DOMParser::getDocumentBody( $document );
			
			$bodyWithPages = $this->_parseContentWithSeparationTag(
				$body,
				function ( \DOMDocument $document ) {
					$mainDiv = $document->createElement('div');
					$mainDiv->setAttribute( 'data-controller', 'core.front.core.articlePages' );
					return $mainDiv;
				},
				function ( \DOMDocument $document ) {
					$subDiv = $document->createElement('div');
					$subDiv->setAttribute( 'data-role', 'contentPage' );
					$hr = $document->createElement('hr');
					$hr->setAttribute( 'data-role', 'contentPageBreak' );
					$subDiv->appendChild( $hr );
					return $subDiv;
				},
				'[page]'
			);
			
			$newBody = new DOMElement('body');
			$body->parentNode->replaceChild( $newBody, $body );
			$newBody->appendChild( $bodyWithPages );			
 		}

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
		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtStartOfNode( $parent );

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
			if ( $newElement )
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

			/* <pre> tags don't parse BBCode inside */
			$resumeBBCodeAfterPre = FALSE;
			if ( $newElement and $newElement->tagName == 'pre' and $this->bbcodeParse )
			{
				$this->bbcodeParse = FALSE;
				$resumeBBCodeAfterPre = TRUE;
			}
		}
		else
		{
			$newElement = $parent;
		}

		/* Loop children */
		$parser->_parseDomNodeList( $element->childNodes, $newElement );

		/* Finish */
		if ( $okayToParse )
		{
			/* <pre> tags don't parse BBCode inside */
			if ( $newElement and $newElement->tagName == 'pre' and $resumeBBCodeAfterPre )
			{
				$this->bbcodeParse = TRUE;
			}

			/* End of an <abbr>? */
			if ( $element->tagName === 'abbr' and $element->hasAttribute('title') )
			{
				$k = array_search( $element->getAttribute('title'), $this->openAbbrTags );
				if ( $k !== FALSE )
				{
					unset( $this->openAbbrTags[ $k ] );
				}
			}

			/* If we did have children, but now we don't (for example, the entire content is a block-level BBCode), drop this element to avoid unintentional whitespace */
			if ( $newElement and $newElement->parentNode and $element->childNodes->length and !$newElement->childNodes->length )
			{
				$parent->removeChild( $newElement );
			}
		}

		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtEndOfNode( $parent );
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
		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtStartOfNode( $parent );
				
		/* Init */
		$text = $textNode->wholeText;
		$breakPoints = array( '(' . static::getEmojiRegex() . ')' );
		
		/* Contains [page] tags? */
		if ( mb_strpos( $text, '[page]' ) !== FALSE )
		{
			$this->containsPageTags = TRUE;
		}
		
		/* If we are parsing BBCode, we will look for opening (e.g. "[foo=bar]") and closing (e.g. "[/foo]") tags */
		if ( $this->bbcode !== NULL and count( $this->bbcode ) )
		{
			/* First, if we have any single-tag BBCodes (e.g. "[img=URL]") expressed as normal BBCodes (e.g. "[img]URL[/img]") - fix that */
			foreach ( $this->bbcode as $tag => $bbcode )
			{
				if ( isset( $bbcode['single'] ) and $bbcode['single'] )
				{
					if ( isset( $bbcode['attributes'] ) and in_array( '{option}', $bbcode['attributes'] ) )
					{
						$text = preg_replace( '/\[(' . preg_quote( $tag, '/' ) . ')\](.+?)\[\/' . preg_quote( $tag, '/' ) . '\]/i', '[$1=$2]', $text );
					}
					else
					{
						$text = preg_replace( '/\[(' . preg_quote( $tag, '/' ) . ')\]\s*\[\/' . preg_quote( $tag, '/' ) . '\]/i', '[$1]', $text );
					}
				}
			}
			
			/* And add our regex to the breakpoints */
			$breakPoints[] = '(\[\/?(?:' . implode( '|', array_map( function ( $value ) { return preg_quote( $value, '/' ); }, array_keys( $this->bbcode ) ) ) . ')(?:[=\s].+?)?\])';
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
				$this->_parseTextSection( $section, $parent, ++$sectionId, count( $sections ) );
			}		
		}
		else
		{
			$this->_parseTextSection( $textNode->wholeText, $parent, 1, 1 );
		}
				
		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtEndOfNode( $parent );
	}

	/**
	 * Parse a section of text after it has been split into relevant sections
	 *
	 * @param	string		$section		The text from the source document to parse
	 * @param	DOMNode	$parent			The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @param int|null $sectionId		The position of this section out of all the sections in the node - used to indicate if there's text before/after this section
	 * @param int|null $sectionCount	The total number of sections in the node - used to indicate if there's text before/after this section
	 * @return	void
	 */
	protected function _parseTextSection( string $section, DOMNode &$parent, ?int $sectionId=null, ?int $sectionCount=null ) : void
	{
		/* The legacy method required $sectionId and $sectionCount as ints, in v5 these were removed, so if they are null we treat this as the new format */
		if ( is_null( $sectionId ) and is_null( $sectionCount ) )
		{
			parent::_parseTextSection( $section, $parent );
			return;
		}

		/* If it's empty, skip it */
		if ( $section === '' )
		{
			return;
		}

		/* If this restarts parsing, do that */
		if ( $section == $this->resumeBBCodeParsingOn )
		{
			$this->bbcodeParse = TRUE;
			$this->resumeBBCodeParsingOn = NULL;
		}

		/* Start of BBCode tag? */
		if (
			$this->bbcode !== NULL and $this->bbcodeParse and // BBCode is enabled
			preg_match( '/^\[([a-z\*]+?)(?:([=\s])(.+?))?\]$/i', $section, $matches ) and // It looks like a BBCode tag
			array_key_exists( mb_strtolower( $matches[1] ), $this->bbcode ) and // The tag is in the list
			( !isset( $this->bbcode[ mb_strtolower( $matches[1] ) ]['allowOption'] ) or $this->bbcode[ mb_strtolower( $matches[1] ) ]['allowOption'] === TRUE or !isset( $matches[3] ) or !$matches[3] ) // If options aren't allowed for this tag, there isn't one
		)
		{
			/* What was the option? */
			$option = NULL;
			if ( isset( $matches[3] ) )
			{
				$option = $matches[3];

				/* If it's [foo="bar"] then we strip the quotes, (if it's [foo bar="baz"] then we don't) */
				if ( !preg_match( '/^\s*$/', $matches[2] ) )
				{
					$option = trim( $option, '"\'' );
				}
			}

			/* Send to _openBBcode */
			$this->_openBBCode( mb_strtolower( $matches[1] ), $option, $parent, $sectionId, $sectionCount );
		}

		/* End of BBCode tag? */
		elseif ( $this->bbcodeParse and array_key_exists( mb_strtolower( $section ), $this->closeTagsForOpenBBCode ) )
		{
			$this->_closeBBCode( mb_substr( mb_strtolower( $section ), 2, -1 ), $parent, $sectionId, $sectionCount );
		}

		/* Normal text */
		else
		{
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

			/* Acronym? */
			if ( $this->bbcodeParse and array_key_exists( $section, $this->caseSensitiveAcronyms ) and !in_array( $this->caseSensitiveAcronyms[ $section ], $this->openAbbrTags ) )
			{
				switch( $this->caseSensitiveAcronyms[ $section ]['a_type'] )
				{
					case 'acronym':
							$parent = $parent->appendChild( new DOMElement( 'abbr' ) );
							$parent->setAttribute( 'title', $this->caseSensitiveAcronyms[ $section ]['a_long'] );
						break;
					case 'link':
							$replace = ( $parent->tagName != 'a' );

							$parentNode = $parent;
							while( ( $parentNode = $parentNode->parentNode ) !== NULL )
							{
								if( $parentNode instanceof DOMElement AND $parentNode->tagName == 'a' )
								{
									$replace = FALSE;
									break;
								}
							}

							if( $replace )
							{
								$parent = $parent->appendChild( new DOMElement( 'a' ) );
								$parent->setAttribute( 'href', $this->caseSensitiveAcronyms[ $section ]['a_long'] );

								try
								{
									$rels	= $this->_getRelAttributes( Url::createFromString( $this->caseSensitiveAcronyms[ $section ]['a_long'] ) );
								}
								catch(Url\Exception $e )
								{
									$rels	= array();
								}

								/* Add rels */
								$parent->setAttribute( 'rel', implode( ' ', $rels ) );
							}
						break;
				}
			}
			elseif ( $this->bbcodeParse and array_key_exists( mb_strtolower( $section ), $this->caseInsensitiveAcronyms ) and !in_array( $this->caseInsensitiveAcronyms[ mb_strtolower( $section ) ], $this->openAbbrTags ) )
			{
				switch( $this->caseInsensitiveAcronyms[ mb_strtolower( $section ) ]['a_type'] )
				{
					case 'acronym':
							$parent = $parent->appendChild( new DOMElement( 'abbr' ) );
							$parent->setAttribute( 'title', $this->caseInsensitiveAcronyms[ mb_strtolower( $section ) ]['a_long'] );
						break;
					case 'link':
							$replace = ( $parent->tagName != 'a' );

							$parentNode = $parent;
							while( ( $parentNode = $parentNode->parentNode ) !== NULL )
							{
								if( $parentNode instanceof DOMElement AND $parentNode->tagName == 'a' )
								{
									$replace = FALSE;
									break;
								}
							}

							if( $replace )
							{
								$parent = $parent->appendChild( new DOMElement( 'a' ) );
								$parent->setAttribute( 'href', $this->caseInsensitiveAcronyms[ mb_strtolower( $section ) ]['a_long'] );

								try
								{
									$rels	= $this->_getRelAttributes( Url::createFromString( $this->caseInsensitiveAcronyms[ mb_strtolower( $section ) ]['a_long'] ) );
								}
								catch(Url\Exception $e )
								{
									$rels	= array();
								}

								/* Add rels */
								$parent->setAttribute( 'rel', implode( ' ', $rels ) );
							}
						break;
				}
			}

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
			$this->_insertNodeApplyingInlineBBcode( new DOMText( $section ), $parent );

			/* Restore the parent */
			$parent = $originalParent;
		}
	}
	
	/**
	 * Open BBCode tag
	 *
	 * @param string $tag			The tag (e.g. "b")
	 * @param string|null $option			If an option was provided (e.g. "[foo=bar]"), it's value
	 * @param	DOMNode	$parent			The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @param int $sectionId		The position of this section out of all the sections in the node - used to indicate if there's text before/after this section
	 * @param int $sectionCount	The total number of sections in the node - used to indicate if there's text before/after this section
	 * @return	void
	 */
	protected function _openBBCode( string $tag, ?string $option, DOMNode &$parent, int $sectionId, int $sectionCount ) : void
	{
		/* Get definition */
		$bbcode = $this->bbcode[ $tag ];
		
		/* Get the document */
		$document = $parent->ownerDocument ?: $parent;
				
		/* Create the element */
		$bbcodeElement = $document->createElement( $bbcode['tag'] );
		
		/* Add any attributes */
		if ( isset( $bbcode['attributes'] ) )
		{
			foreach ( $bbcode['attributes'] as $k => $v )
			{				
				$bbcodeElement->setAttribute( $k, str_replace( '{option}', ( $option ?: ( $bbcode['defaultOption'] ?? '' ) ), $v ) );
			}
		}
		
		/* Callback */
		if ( isset( $bbcode['callback'] ) )
		{
			$callback  = $bbcode['callback'];
			$bbcodeElement = $callback( $bbcodeElement, array( 2 => $option ), $document );
		}
				
		/* Stop parsing? ([code] blocks make it so BBCode isn't parsed inside them) */
		if ( isset( $bbcode['noParse'] ) and $bbcode['noParse'] )
		{
			$this->bbcodeParse = FALSE;
			$this->resumeBBCodeParsingOn = "[/{$tag}]";
		}
		
		/* Parse it */
		$bbcodeElement = $this->_parseElement( $bbcodeElement );
		
		/* Single only? */
		if ( ( $bbcodeElement instanceof DOMElement ) and isset( $bbcode['single'] ) and $bbcode['single'] )
		{
			$this->_insertNodeApplyingInlineBBcode( $bbcodeElement, $parent );
		}
		/* Or with content? */
		else
		{
			/* Block level? */
			if ( isset( $bbcode['block'] ) and $bbcode['block'] )
			{
				/* Insert the block level element */
				$lastOpenedBlockId = NULL;
				if ( !empty( $this->openBlockBBCodeInOrder ) )
				{
					$openBBCodeBlocks = array_keys( $this->openBlockBBCodeInOrder );
					$lastOpenedBlockId = array_pop( $openBBCodeBlocks );
				}

				if ( $bbcodeElement instanceof DOMElement )
				{
					if ( $lastOpenedBlockId and list( $id, $tagName ) = explode( '-', $lastOpenedBlockId ) and isset( $this->bbcode[ $tagName ]['noChildren'] ) and $this->bbcode[ $tagName ]['noChildren'] )
					{
						$parent->appendChild( $bbcodeElement );
					}
					else
					{
						$parent->parentNode->appendChild( $bbcodeElement );
					}
				}
				
				/* Callback */
				$blockElement = $bbcodeElement;
				if ( $bbcodeElement instanceof DOMElement and isset( $bbcode['getBlockContentElement'] ) )
				{
					$callback = $bbcode['getBlockContentElement'];
					$blockElement = $callback( $bbcodeElement );
				}
				
				/* Create an element of the same type (normally <p>) to go in the block-level element for any content left (e.g. "<p>[center]This needs to be centered</p>") and set the parent being used to it */
				if ( $sectionId != $sectionCount )
				{
					if ( !isset( $bbcode['noChildren'] ) or !$bbcode['noChildren'] )
					{
						$contentElement = $parent->cloneNode( FALSE );
						$blockElement->appendChild( $contentElement );
						$parent = $contentElement;
					}
					else
					{
						$parent = $bbcodeElement;
					}
				}

				if ( $bbcodeElement instanceof DOMElement )
				{
					/* Add to $openBlockBBcode for closing later */
					$id = mt_rand() . '-' . $tag;
					$this->openBlockBBCodeByTag[ $tag ][ $id ] = $bbcodeElement;

					/* Add to $penBlockBBCodeInOrder to that so _parseDomElement() will use that as the parent for subsequent elements */
					$this->openBlockBBCodeInOrder[ $id ] = $blockElement;
				}
			}
			
			/* Inline */
			elseif ( $bbcodeElement instanceof DOMElement )
			{
				$this->openInlineBBCode[ $tag ][] = $bbcodeElement;
			}
						
			/* Add it to the array */
			if ( !isset( $this->closeTagsForOpenBBCode[ "[/{$tag}]" ] ) )
			{
				$this->closeTagsForOpenBBCode[ "[/{$tag}]" ] = 0;
			}
			$this->closeTagsForOpenBBCode[ "[/{$tag}]" ]++;
		}
	}
		
	/**
	 * Close BBCode tag
	 *
	 * @param string $tag			The tag (e.g. "b")
	 * @param	DOMNode	$parent			The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @param int $sectionId		The position of this section out of all the sections in the node - used to indicate if there's text before/after this section
	 * @param int $sectionCount	The total number of sections in the node - used to indicate if there's text before/after this section
	 * @return	void
	 */
	protected function _closeBBCode( string $tag, DOMNode &$parent, int $sectionId, int $sectionCount ) : void
	{
		/* Get definition */
		$bbcode = $this->bbcode[ $tag ];
		
		/* Block level? */
		if ( isset( $bbcode['block'] ) and $bbcode['block'] )
		{
			/* Find the block we're closing */
			foreach ( $this->openBlockBBCodeByTag[ $tag ] as $key => $block ) { } // Just sets $key and $block for the last one
						
			/* Create a content element to go after the block-level element for any remaining text in this DOMText node (e.g. "<p>[/center]This should not be centered</p>") and set the parent being used to it */
			if ( $block->previousSibling and $block->previousSibling instanceof DOMText ) // Happens for noChildren tags - e.g. "[list]Foo[list]Bar[/list]Baz[/list]"
			{
				$parent = $block->parentNode;
			}
			else
			{
				if ( $block->previousSibling )
				{
					$contentElement = $block->previousSibling->cloneNode( FALSE );
				}
				else
				{
					$contentElement = $parent->ownerDocument->createElement('p');
				}
				$block->parentNode->appendChild( $contentElement );
				$parent = $contentElement;
			}
			
			/* Remove it from the list of open blocks */
			unset( $this->openBlockBBCodeByTag[ $tag ][ $key ] );
			unset( $this->openBlockBBCodeInOrder[ $key ] );
			
			/* Finished callback? */
			if ( isset( $bbcode['finishedCallback'] ) and $bbcode['finishedCallback'] )
			{
				$callback = $bbcode['finishedCallback'];
				$newBlock = $callback( $block );
				if ( $block->parentNode )
				{
					$block->parentNode->replaceChild( $newBlock, $block );
				}
				else
				{
					$parent->ownerDocument->getElementsByTagName('body')->item(0)->appendChild( $newBlock );
				}
			}
		}
		
		/* Inline */
		else
		{
			array_pop( $this->openInlineBBCode[ $tag ] );
			if ( empty( $this->openInlineBBCode[ $tag ] ) )
			{
				unset( $this->openInlineBBCode[ $tag ] );
			}
		}
		
		/* Remove from array of open BBCodes */
		$this->closeTagsForOpenBBCode["[/{$tag}]"]--;
		if ( !$this->closeTagsForOpenBBCode["[/{$tag}]"] )
		{
			unset( $this->closeTagsForOpenBBCode["[/{$tag}]"]  );
		}
	}
	
	/**
	 * Insert a node to a parent while applying inline BBCode 
	 *
	 * @param	DOMNode	$node	Node to insert
	 * @param	DOMNode	$parent	Parent to insert into
	 * @return	void
	 */
	protected function _insertNodeApplyingInlineBBcode( DOMNode $node, DOMNode $parent ) : void
	{
		/* Apply any open inline BBCode elements */
		if ( $this->bbcodeParse )
		{
			foreach ( $this->openInlineBBCode as $tag => $elements )
			{
				foreach ( $elements as $bbcodeElement )
				{
					$parent = $parent->appendChild( $bbcodeElement->cloneNode( TRUE ) );
				}
			}
		}
		
		/* Insert the text */
		$parent->appendChild( $node );
	}
	
	/**
	 * Adjust for Block-Level BBCode if necessary at start of the node
	 *
	 * @param	DOMNode	$parent		The node from the new document which will be the working node's parent. Passed by reference and will be modified if there is an open block-level BBCode
	 * @return	void
	 */
	protected function _adjustParentForBlockBBCodeAtStartOfNode( DOMNode &$parent ) : void
	{
		/* If we have an open block-level BBCode element, and we're not already on a child
			of one we have already moved, insert this element into that instead of the
			defined parent */
		if ( count( $this->openBlockBBCodeInOrder ) )
		{
			if ( !$this->openBlockDepth )
			{
				$openBlocks = $this->openBlockBBCodeInOrder;
				$parent = array_pop( $openBlocks );
			}
			$this->openBlockDepth++;
		}
	}
	
	/**
	 * Adjust for Block-Level BBCode if necessary at end of the node
	 *
	 * @param	DOMNode	$parent		The node from the new document which will be the working node's parent. Passed by reference and will be modified if there is an open block-level BBCode
	 * @return	void
	 */
	protected function _adjustParentForBlockBBCodeAtEndOfNode( DOMNode &$parent ) : void
	{
		/* If we have an open block-level BBCode element, decrease the depth we're at */
		if ( $this->openBlockDepth )
		{
			if ( $this->openBlockDepth == 1 )
			{
				$parent = $parent->parentNode;
			}
			$this->openBlockDepth--;
		}
	}
	
	/* !BBCode */
	
	/**
	 * Get BBCode Tags
	 *
	 * @param	Member	$member	The member
	 * @param bool|string $area The Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @code
	 	* return array(
	 		* 'font'	=> array(																	// Key represents the BBCode tag (e.g. [font])
		 		* 'tag'			=> 'span',															// The HTML tag to use
		 		* 'attributes'	=> array( ... )														// Key/Value pairs of attributes to use (optional) - can use {option} to get the [tag=option] value
		 		* 'defaultOption'	=> '..',															// Value to use for {option} if one isn't specified
		 		* 'block'			=> FALSE,															// If this is a block-level tag (optional, default false)
		 		* 'single'		=> FALSE,															// If this is a single tag, with no content (optional, default false)
		 		* 'noParse'		=> FALSE,															// If other BBCode shouldn't be parsed inside (optional, default false)
		 		* 'noChildren'	=> FALSE,															// If it is not appropriate for this element to have child elements (for example <pre> can't have <p>s inside) (optional, default false)
		 		* 'callback'		=> function( \DOMElement $node, $matches, \DOMDocument $document )	// A callback to modify the DOMNode object (optional)
		 		* {
		 			* ...
		 			* return $node;
		 		* },
		 		* 'getBlockContentElement' => function( \DOMElement $node )							// If the callback modifies, an additional callback can be specified to provide the node which children should go into (for example, spoilers have a header, and we want children to go into the content body) (optional)
		 		* {
			 		* ...
			 		* return $node;
			 	* },
		 		* 'finishedCallback'	=> function( \DOMElement $originalNode )						// A callback which is ran after all children have been parsed for any additional parsing (optional)
		 		* {
			 		* ...
			 		* return $node;
			 	* },
			 	* 'allowOption'	=> FALSE,															// If options are allowed. Defaults to TRUE
	 	* )
	 * @endcode
	 * @return	array|NULL
	 */
	public function bbcodeTags( Member $member, bool|string $area ): ?array
	{
		$return = array();

		/* If BBCode parsing is disabled and we aren't forcing it (e.g. for converters) then return no tags to parse now */
		if( !$this->forceBbcodeEnabled )
		{
			return NULL;
		}
		
		/* Acronym */
		$return['acronym'] = array( 'tag' => 'abbr', 'attributes' => array( 'title' => '{option}' ), 'allowOption' => TRUE );
		
		/* Background */
		$return['background'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'background-color:{option}' ), 'allowOption' => TRUE );
		
		/* Bold */
		$return['b'] = array( 'tag' => 'strong', 'allowOption' => FALSE );
		
		/* Code */
		$code = array( 'tag' => 'pre', 'attributes' => array( 'class' => 'ipsCode' ), 'block' => TRUE, 'noParse' => TRUE, 'noChildren' => TRUE, 'allowOption' => TRUE, 'finishedCallback' => function( DOMElement $originalNode ) {

			/* Parse breaks - with BBCode we'll be getting things like [code]line1<br>line2[/code] so we need to make sure they're formatted properly */
			$contents = substr( trim( DOMParser::parse(
				$originalNode->ownerDocument->saveHtml( $originalNode ),
				/* DOMElement Parse */
				function (DOMElement $element, DOMNode $parent, DOMParser $parser )
				{
					/* Control elements get inserted normally */
					if ( $parent instanceof \DOMDocument or in_array( $parent->tagName, array( 'html', 'head', 'body' ) ) )
					{
						$ownerDocument = $parent->ownerDocument ?: $parent;
						$newElement = $ownerDocument->importNode( $element );

						$parent->appendChild( $newElement );

						$parser->_parseDomNodeList( $element->childNodes, $newElement );
					}
					/* Everything else becomes a direct child of the <pre> */
					else
					{
						/* With "\n"s inserted appropriately */
						if ( $element->tagName == 'br' or $element->tagName == 'p' )
						{
							$parent->appendChild( new DOMText("\n") );
						}

						$parser->_parseDomNodeList( $element->childNodes, $parent );
					}
				},
				/* DOMText Parse todo test this and see if anything can be deleted now we use Tiptap instead of ckeditor */
				function (DOMText $textNode, DOMNode $parent, DOMParser $parser ) {
					/* Tiptap sometimes sends "\n" so just strip those so we don't get double breaks */
					$text = str_replace( "\n", '', $textNode->textContent );

					/* @legacy CKEditor will also send "<br>\t" and "<br></p><p>\t" so also strip any whitespace after a break
						CKEditor doesn't actually have a way to indent individual lines */
					if (
						( $previousSibling = $textNode->previousSibling and $previousSibling instanceof DOMElement and $previousSibling->tagName == 'br' )
						or
						( $textNode->parentNode instanceof DOMElement and $textNode->parentNode->tagName == 'p' and $textNode->parentNode->lastChild and $textNode->parentNode->lastChild instanceof DOMElement and $textNode->parentNode->lastChild->tagName == 'br' )
					) {
						$text = preg_replace( '/^\s/', '', $text );
					}

					/* Insert */
					$parent->appendChild( new DOMText( $text ) );
				}
			) ), 21, -6 );

			/* Create a new <pre> with those contents */
			$return = $originalNode->ownerDocument->createElement( 'pre' );
			$return->appendChild( new DOMText( html_entity_decode( $contents ) ) ); // We have to decode HTML entities otherwise they'll be double-encoded. Test with "[code]<strong>Test</strong>[/code]"
			$return->setAttribute( 'class', 'ipsCode' );
			return $return;
		} );

		$return['code'] = $code;
		$return['codebox'] = $code;
		$return['html'] = $code;
		$return['php'] = $code;
		$return['sql'] = $code;
		$return['xml'] = $code;
		
		/* Color */
		$return['color'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'color:{option}' ), 'allowOption' => TRUE );
		
		/* Font */
		$return['font'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'font-family:{option}' ), 'allowOption' => TRUE );
		
		/* HR */
		$return['hr'] = array( 'tag' => 'hr', 'single' => TRUE, 'allowOption' => FALSE );

		/* Image */
		$return['img'] = array( 'tag' => 'img', 'attributes' => array( 'src' => '{option}', 'class' => 'ipsImage' ), 'single' => TRUE, 'allowOption' => TRUE );
		
		/* Indent */
		$return['indent'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'margin-left:{option}px' ), 'block' => FALSE, 'defaultOption' => 25, 'allowOption' => TRUE );
		
		/* Italics */
		$return['i'] = array( 'tag' => 'em', 'allowOption' => FALSE );
		
		/* Justify */
		$return['left'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:left' ), 'block' => TRUE, 'allowOption' => FALSE );
		$return['center'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:center' ), 'block' => TRUE, 'allowOption' => FALSE );
		$return['right'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:right' ), 'block' => TRUE, 'allowOption' => FALSE );
		
		/* Links */
		/* Email */
		$return['email'] = array( 'tag' => 'a', 'attributes' => array( 'href' => 'mailto:{option}' ), 'allowOption' => TRUE );

		/* Member */
		$return['member'] = array(
			'tag'		=> 'a',
			'attributes'=> array( 'contenteditable' => 'false', 'data-ipsHover' => '' ),
			'callback'	=> function( DOMElement $node, $matches, \DOMDocument $document )
			{
				try
				{
					$member = Member::load( $matches[2], 'name' );
					if ( $member->member_id != 0 )
					{
						$node->setAttribute( 'href',  $member->url() );
						$node->setAttribute( 'data-ipsHover-target',  $member->url()->setQueryString( 'do', 'hovercard' ) );
						$node->setAttribute( 'data-mentionid',  $member->member_id );
						$node->appendChild( $document->createTextNode( '@' . $member->name ) );
					}

				}
				catch ( Exception $e ) {}

				return $node;
			},
			'single'	=> TRUE,
			'allowOption' => TRUE
		);

		/* Links */
		$return['url'] = array( 'tag' => 'a', 'attributes' => array( 'href' => '{option}' ), 'allowOption' => TRUE );
				
		/* List */
		$return['list'] = array(
			'tag' => 'ul',
			'callback' => function( $node, $matches, $document )
			{
				/* Set the main attributes for our <ul> or <ol> element */
				if ( isset( $matches[2] ) )
				{
					$node = $document->createElement( 'ol' );
					switch ( $matches[2] )
					{
						case '1':
							$node->setAttribute( 'style', 'list-style-type: decimal' );
							break;
						case '0':
							$node->setAttribute( 'style', 'list-style-type: decimal-leading-zero' );
							break;
						case 'a':
							$node->setAttribute( 'style', 'list-style-type: lower-alpha' );
							break;
						case 'A':
							$node->setAttribute( 'style', 'list-style-type: upper-alpha' );
							break;
						case 'i':
							$node->setAttribute( 'style', 'list-style-type: lower-roman' );
							break;
						case 'I':
							$node->setAttribute( 'style', 'list-style-type: upper-roman' );
							break;
					}
				}

				return $node;
			},
			'finishedCallback'	=> function( DOMElement $originalNode ) {

				/* If the [/list] was in it's own paragraph, that empty paragraph will be present. Remove it */
				if ( $originalNode->lastChild and $originalNode->lastChild->nodeType === XML_ELEMENT_NODE and !$originalNode->lastChild->childNodes->length )
				{
					$originalNode->removeChild( $originalNode->lastChild );
				}

				/* Do it */
				return $this->_parseContentWithSeparationTag(
					$originalNode,
					function ( \DOMDocument $document ) use ( $originalNode ) {
						return $document->importNode( $originalNode->cloneNode() );
					},
					function ( \DOMDocument $document ) {
						return new DOMElement('li');
					},
					'[*]'
				);
			},
			'block' => TRUE,
			'noChildren' => TRUE,
			'allowOption' => TRUE
		);
				
		/* Quote */
		$return['quote'] = array(
			'tag' => 'blockquote',
			'callback' => function( DOMElement $node, $matches, \DOMDocument $document )
			{
				/* What options do we have? */
				$options = array();
				if ( isset( $matches[2] ) and $matches[2] )
				{
					preg_match_all('/\s?(.+?)=[\'"](.+?)[\'"$]/', trim( $matches[2] ), $_options );
					foreach ( $_options[0] as $k => $v )
					{
						$options[ $_options[1][ $k ] ] = $_options[2][ $k ];
					}
				}

				/* Set the main attributes for our <blockquote> element */
				$node->setAttribute( 'class', 'ipsQuote' );
				$node->setAttribute( 'data-ipsQuote', '' );
				if ( isset( $options['name'] ) and $options['name'] )
				{
					$node->setAttribute( 'data-ipsQuote-username', $options['name'] );
				}
				if ( isset( $options['date'] ) and $options['date'] )
				{
					$node->setAttribute( 'data-ipsQuote-timestamp', strtotime( $options['date'] ) );
				}
				if ( Application::appIsEnabled('forums') and isset( $options['post'] ) and $options['post'] )
				{
					try
					{
						$post = Post::load( $options['post'] );

						$node->setAttribute( 'data-ipsQuote-contentapp', 'forums' );
						$node->setAttribute( 'data-ipsQuote-contenttype', 'forums' );
						$node->setAttribute( 'data-ipsQuote-contentclass', 'forums_Topic' );
						$node->setAttribute( 'data-ipsQuote-contentid', $post->item()->tid );
						$node->setAttribute( 'data-ipsQuote-contentcommentid', $post->pid );
					}
					catch ( OutOfRangeException $e ) {}
				}

				/* Create the citation element */
				$citation = $document->createElement('div');
				$citation->setAttribute( 'class', 'ipsQuote_citation' );
				$node->appendChild( $citation );

				/* Create the content element */
				$contents = $document->createElement('div');
				$contents->setAttribute( 'class', 'ipsQuote_contents ipsClearfix' );
				$node->appendChild( $contents );

				return $node;
			},
			'getBlockContentElement' => function( DOMElement $node )
			{
				foreach ( $node->childNodes as $child )
				{
					if ( $child instanceof DOMElement and mb_strpos( $child->getAttribute('class'), 'ipsQuote_contents' ) !== FALSE )
					{
						return $child;
					}
				}
				return $node;
			},
			'block' => TRUE,
			'allowOption' => TRUE
		);
		
		/* Size */
		$return['size'] = array(
			'tag'		=> 'span',
			'callback'	=> function( $node, $matches )
			{
				switch ( $matches[2] )
				{
					case 1:
						$node->setAttribute( 'style', 'font-size:8px' );
						break;
					case 2:
						$node->setAttribute( 'style', 'font-size:10px' );
						break;
					case 3:
						$node->setAttribute( 'style', 'font-size:12px' );
						break;
					case 4:
						$node->setAttribute( 'style', 'font-size:14px' );
						break;
					case 5:
						$node->setAttribute( 'style', 'font-size:18px' );
						break;
					case 6:
						$node->setAttribute( 'style', 'font-size:24px' );
						break;
					case 7:
						$node->setAttribute( 'style', 'font-size:36px' );
						break;
					case 8:
						$node->setAttribute( 'style', 'font-size:48px' );
						break;
				}
				return $node;
			},
			'allowOption' => TRUE
		);
		
		/* Spoiler */
		$return['spoiler'] = array(
			'tag' => 'div',
			'callback' => function( DOMElement $node, $matches, \DOMDocument $document )
			{
				/* Set the main attributes for our <div> element */
				$node->setAttribute( 'class', 'ipsSpoiler' );
				$node->setAttribute( 'data-ipsSpoiler', '' );

				/* Create the citation element */
				$header = $document->createElement('div');
				$header->setAttribute( 'class', 'ipsSpoiler_header' );
				$node->appendChild( $header );
				$headerSpan = $document->createElement('span');
				$header->appendChild( $headerSpan );

				/* Create the content element */
				$contents = $document->createElement('div');
				$contents->setAttribute( 'class', 'ipsSpoiler_contents' );
				$node->appendChild( $contents );

				return $node;
			},
			'getBlockContentElement' => function( DOMElement $node )
			{
				foreach ( $node->childNodes as $child )
				{
					if ( $child instanceof DOMElement and $child->getAttribute('class') == 'ipsSpoiler_contents' )
					{
						return $child;
					}
				}
				return $node;
			},
			'block' => TRUE,
			'allowOption' => FALSE
		);
		
		/* Strike */
		$return['s'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:line-through' ), 'allowOption' => FALSE );
		$return['strike'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:line-through' ), 'allowOption' => FALSE );
		
		/* Subscript */
		$return['sub'] = array( 'tag' => 'sub', 'allowOption' => FALSE );
		
		/* Superscript */
		$return['sup'] = array( 'tag' => 'sup', 'allowOption' => FALSE );
		
		/* Underline */
		$return['u'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:underline' ), 'allowOption' => FALSE );
		
		return $return;
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
	 * @param bool $bbcode				Parse BBCode?
	 * @param bool $cleanHtml			If TRUE, HTML will be cleaned through HTMLPurifier
	 *
	 * @return	string
	 * @see		__construct
	 */
	public static function parseStatic( string $value, array $attachIds=NULL, Member $member=NULL, bool|string $area=FALSE, bool $filterProfanity=TRUE, callable $htmlPurifierConfig=NULL, bool $bbcode=FALSE, bool $cleanHtml=TRUE ): string
	{
		$obj = new static($bbcode, $attachIds, $member, $area, $filterProfanity, $cleanHtml, $htmlPurifierConfig);
		return $obj->parse( $value );
	}
	
	/**
	 * Rebuild rel tag contents for posts
	 *
	 * @param string $textContent	The content of the text, and they say comments aren't useful
	 * @param Member $member		The author of the content
	 * @return mixed	FALSE or changed content
	 */
	public static function rebuildUrlRels( string $textContent, Member $member ): mixed
	{
		$obj = new static(TRUE, NULL, $member);
		$rebuilt = FALSE;
		
		/* Create DOMDocument */
		$content = new DOMDocument( '1.0', 'UTF-8' );
		@$content->loadHTML( DOMDocument::wrapHtml( $textContent ) );
		
		$xpath = new DOMXpath( $content );
		foreach( $xpath->query('//a') as $link )
		{
			if ( $link->getAttribute('href') )
			{
				try
				{
					$url = Url::createFromString( str_replace( array( '<___base_url___>', '%7B___base_url___%7D' ), rtrim( Settings::i()->base_url, '/' ), $link->getAttribute('href') ) );
					$rels = $obj->_getRelAttributes( $url );
					$link->setAttribute( 'rel', implode( ' ', $rels ) );
					
					$rebuilt = TRUE;
				}
				catch( Exception $e ) { }
			}
		}
		
		if ( $rebuilt )
		{
			$value = $content->saveHTML();
			$value = preg_replace( '/<meta http-equiv(?:[^>]+?)>/i', '', preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>', '<head>', '</head>' ), '', $value ) ) );

			/* Replace file storage tags */
			$value = preg_replace( '/&lt;fileStore\.([\d\w\_]+?)&gt;/i', '<fileStore.$1>', $value );

			/* DOMDocument::saveHTML will encode the base_url brackets, so we need to make sure it's in the expected format. */
			return str_replace( '&lt;___base_url___&gt;', '<___base_url___>', $value );
		}
		
		return FALSE;
	}
}
