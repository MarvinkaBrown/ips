<?php
/**
 * @brief		Editor class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Apr 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DirectoryIterator;
use DomainException;
use Exception;
use Garfix\JsMinify\Minifier;
use InvalidArgumentException;
use IPS\Application;
use IPS\core\Profanity;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Http\Url;
use IPS\Image;
use IPS\Lang;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Parser;
use IPS\Theme;
use IPS\Xml\SimpleXML;
use OutOfBoundsException;
use UnderflowException;
use function count;
use function defined;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_null;
use function is_string;
use function strlen;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor class for Form Builder
 */
class Editor extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'app'			=> 'core',		// The application that owns this type of editor (as defined by an extension)
	 		'key'			=> 'Example',	// The key for this type of editor (as defined by an extension)
	 		'autoSaveKey'	=> 'abc',		// Pass a string which identifies this editor's purpose. For example, if the editor is for replying to a topic with ID 5, you could use "topic-reply-5". Make sure you pass the same key every time, but a different key for different editors.
	 		'attachIds'		=> array(		// ID numbers to identify content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, you must claim attachments after saving.
	 			1,
	 			2,
	 			'foo'
	 		),
			'attachIdsLang'	=> NULL,		// Language ID number if this Editor is part of a Translatable field.
	 		'minimize'		=> 'clickme',	// Language string to use for minimized view. NULL will mean editor is not minimized.
	 		'tags'			=> array(),		// An array of extra insertable tags in key => value pair with key being what is inserted and value serving as a description
	 		'minimizeWithContent'=> FALSE,	// If TRUE, the editor will be minimized even if there is default content
	 		'maxLength'		=> FALSE,	// The maximum length. Note that the content is HTML, so this isn't the maximum number of visible characters, so should only be used for database limits. The database's max allowed packet will override if smaller
	 		'editorId'		=> NULL,	// Passed to editorAttachments. Only necessary if the name may be changed
	 		'allowAttachments => TRUE,	// Should the editor show upload options?
	 		'contentClass'	 => NULL,		// If set to a string, will check this class for prioritizing mentions to participants.
	 		'contentId'			=> NULL,	// If set, will check this particular item ID for prioritizing mentions to participants.
	 		"comments" => false 			// If true, this editor uses the member's restrictions for the comment editor
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'app'					=> NULL,
		'key'					=> NULL,
		'autoSaveKey'			=> NULL,
		'attachIds'				=> NULL,
		'attachIdsLang'			=> NULL,
		'minimize'				=> NULL,
		'tags'					=> array(),
		'minimizeWithContent'	=> FALSE,
		'maxLength'				=> 16777215, // The default for MEDIUMTEXT */
		'editorId'				=> NULL,
		'allowAttachments'		=> TRUE,
		'profanityBlock'		=> TRUE,
		'contentClass'			=> NULL,
		'contentId'				=> NULL,
		"comments"				=> false, //whether this is for a comment
	);
	
	/**
	 * @brief	The extension that owns this type of editor
	 */
	protected mixed $extension = NULL;
	
	/**
	 * @brief	The uploader helper
	 */
	protected Upload|null|false $uploader = NULL;

	/**
	 * @brief	Editor identifier
	 */
	protected ?string $postKey = NULL;

	/**
	 * These restrictions are global restrictions that can be applied to the editor
	 *
	 * @var string[] $editorRestrictions
	 */
	protected static array $editorRestrictions = [
		"heading",
		"heading_1",
		"box",
		"box_color",
		"float",
		"font_family",
		"font_size",
		"font_color",
		"giphy",
		"internal_embed",
		"external_embed",
		"external_media", // both images and video
		"external_image",
		"external_video",
		'og_embed',
		"raw_embed",
	];

	/**
	 * Mapping of restriction -> dependency
	 *
	 * @var string[]
	 */
	protected static array $dependentRestrictions = [
		'heading_1' => 'heading',
		'box_color' => 'box',
		"external_media" => 'external_embed',
		"external_image" => "external_media",
		"external_video" => "external_media",
		'raw_embed' => 'external_embed',
		'og_embed'	=> 'external_embed',
		'giphy' => 'external_image',
	];

	/**
	 * Constructor
	 *
	 * @param string $name Name
	 * @param mixed $defaultValue Default value
	 * @param bool|null $required Required? (NULL for not required, but appears to be so)
	 * @param array $options Type-specific options
	 * @param callable|null $customValidationCode Custom validation code
	 * @param string|null $prefix HTML to show before input field
	 * @param string|null $suffix HTML to show after input field
	 * @param string|null $id The ID to add to the row
	 */
	public function __construct( string $name, mixed $defaultValue=NULL, ?bool $required=FALSE, array $options=array(), callable $customValidationCode=NULL, string $prefix=NULL, string $suffix=NULL, string $id=NULL )
	{
		$this->postKey = md5( $options['autoSaveKey'] . ':' . session_id() );

		if ( isset( Request::i()->usingEditor ) and Request::i()->isAjax() and Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'front' )
		{
			Session::i()->setUsingEditor();
			Output::i()->json( array( true ) );
		}

		/* Get our extension */
		$extensions = Application::load( $options['app'] )->extensions( 'core', 'EditorLocations' );
		if ( !isset( $extensions[ $options['key'] ] ) )
		{
			throw new OutOfBoundsException( $options['key'] );
		}

		$this->extension = $extensions[ $options['key'] ];

		/* Don't minimize if we have a value */
		$name = $options['editorId'] ?? $name;
		if ( ( !isset( $options['minimizeWithContent'] ) or !$options['minimizeWithContent'] ) and ( $defaultValue or Request::i()->$name or ( Lang::vleActive() ) ) )
		{
			$options['minimize'] = NULL;
		}

		/* Create the upload helper - if the form has been submitted, this has to be done before parent::__construct() as we need the uploader present for getValue(), but for views, we won't load until the editor is clicked */
		$this->options = array_merge( $this->defaultOptions, $options );
		if ( $this->canAttach() AND $this->options['allowAttachments'] )
		{
			if ( ( isset( Request::i()->getUploader ) and Request::i()->getUploader === $name ) or ( isset( Request::i()->postKey ) and Request::i()->postKey === $this->postKey and isset( Request::i()->deleteFile ) ) )
			{
				if ( $uploader = $this->getUploader( $name ) )
				{
					Output::i()->sendOutput( $uploader->html() );
				}
				else
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'forms', 'core', 'global' )->editorAttachmentsPlaceholder( $name, $this->postKey ) );
				}
			}
			elseif( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' or !$this->options['minimize'] )
			{
				$this->uploader = $this->getUploader( $name );
			}
			else
			{
				$this->uploader = FALSE;
			}
		}

		/* Work out the biggest value MySQL will allow */
		if ( !isset( Store::i()->maxAllowedPacket ) )
		{
			$maxAllowedPacket = 0;
			foreach ( Db::i()->query("SHOW VARIABLES LIKE 'max_allowed_packet'") as $row )
			{
				$maxAllowedPacket = $row['Value'];
			}
			Store::i()->maxAllowedPacket = $maxAllowedPacket;
		}
		if ( Store::i()->maxAllowedPacket )
		{
			if ( !isset( $options['maxLength'] ) or $options['maxLength'] > Store::i()->maxAllowedPacket )
			{
				$options['maxLength'] = Store::i()->maxAllowedPacket;
			}
		}

		/* Go */
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );

		/* Load Editor JS */
		static::loadEditorFiles( $this->options['minimize'] );
	}

	/**
	 * @var bool
	 */
	protected static bool $_editorFilesAdded = false;

	/**
	 * Load required editor files. Moved to static method
	 * so that we can call it from the Codemirror helper when necessary
	 *
	 * @param string|null $minimize
	 * @return void
	 */
	public static function loadEditorFiles( ?string $minimize=null ) : void
	{
		/* Include editor JS - but not if this page was loaded via ajax OR if we're a guest and the editor is minimized - because the JS loader will handle that on demand */
		if ( !(\IPS\IN_DEV AND defined('EDITOR_DEV') AND \EDITOR_DEV) and !static::$_editorFilesAdded and ( !$minimize or Member::loggedIn()->member_id ) and !Request::i()->isAjax() )
		{
			$dir = Application::load( 'core' )->directory;
			$manifest = json_decode( file_get_contents( ROOT_PATH . "/applications/{$dir}/data/editorManifest.json" ), true );
			foreach ( $manifest as $entry )
			{
				if ( !@$entry['isEntry'] ) {
					continue;
				}
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, array( Url::internal( "applications/core/interface/tiptap/{$entry['file']}", 'none', NULL, array(), Url::PROTOCOL_RELATIVE ) ) );
			}

			Output::i()->jsVars['editorPreloading'] = true;

			static::$_editorFilesAdded = true;
		}
	}
	
	/**
	 * Get HTML
	 *
	 * @param bool $raw	If TRUE, will return without HTML any chrome
	 * @return	string
	 */
	public function html( bool $raw=FALSE ): string
	{
		/* Clean resources in ACP */
		$value = $this->value;
		Output::i()->parseFileObjectUrls( $value );

		// Todo we can probably can be deleted. The <br> is now saved, and we also have CSS that makes the empty <p></p> tags hidden. Needs Ehren's signoff :)
		/* The editor will replace <p></p> (which doesn't display anything in a normal post) with <p><br></p> (which does)
			which creates a discrepency between wheat displays in a post and what displays in the editor */
//		$value = preg_replace( '/<p>\s*<\/p>/', '', $value );

		/* Show full uploader */
		if ( $this->uploader )
		{
			$attachmentArea = $this->uploader->html();
		}
		/* Or show a loading icon where the uploader will go if the editor is minimized */
		elseif ( $this->uploader === FALSE )
		{
			$attachmentArea = Theme::i()->getTemplate( 'forms', 'core', 'global' )->editorAttachmentsMinimized( $this->name );
			
			/* We still need to include plupload otherwise it won't work when they click in */
			if ( \IPS\IN_DEV )
			{
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/moxie.js', 'core', 'interface' ) );
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/plupload.dev.js', 'core', 'interface' ) );
			}
			else
			{
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/plupload.full.min.js', 'core', 'interface' ) );
			}
		}
		/* Or if the user can't attach, just show a bar */
		else
		{
			$attachmentArea = Theme::i()->getTemplate( 'forms', 'core', 'global' )->editorAttachmentsPlaceholder( $this->name, $this->postKey, $this->noUploaderError, $this->canUseMediaExtension() );
		}

		if ( Member::loggedIn()->group['g_bypass_badwords'] )
		{
			$this->options['profanityBlock'] = FALSE;
		}

		if ( $this->options['profanityBlock'] )
		{
			$this->options['profanityBlock'] = [];
			foreach( Profanity::getProfanity() AS $profanity )
			{
				if ( $profanity->action == 'block' )
				{
					$this->options['profanityBlock'][] = [ 'word' => $profanity->type, 'type' => $profanity->m_exact ? 'exact' : 'loose' ];
				}
			}
		}

		/* Check for plugins */
		$this->options['loadPlugins'] = static::hasPlugins();

		/* Display */
		$template = $raw ? 'editorRawV5' : 'editor';
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->$template( $this->name, $value, $this->options, md5( $this->options['autoSaveKey'] . ':' . session_id() ), $attachmentArea, json_encode( array() ), $this->options['tags'], $this->options['contentClass'], $this->options['contentId'] );
	}

	/**
	 * @brief Save on queries and fetch the alt label would just the once
	 */
	protected ?string $_altLabelWord = NULL;

	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		$error = NULL;
		$value = parent::getValue();
		
		/* Remove any invisible spaces used by the editor JS */
		$value = preg_replace( '/[\x{200B}\x{2063}]/u', '', $value );

		/* Parse value */
		if ( $value )
		{
			$parser = $this->_getParser();
			$value = $parser->parse( $value );
		}

		/* Add any attachments that weren't inserted in the content */
		if ( $this->uploader )
		{
			$inserts = array();
			$fileAttachments = array();

			foreach ( $this->getAttachments() as $attachment )
			{
				if ( !isset( $parser ) or !in_array( $attachment['attach_id'], $parser->mappedAttachments ) or array_key_exists( $attachment['attach_id'], $parser->existingAttachments ) )
				{
					$ext = mb_substr( $attachment['attach_file'], mb_strrpos( $attachment['attach_file'], '.' ) + 1 );
					if ( in_array( mb_strtolower( $ext ), File::$videoExtensions ) or in_array( mb_strtolower( $ext ), File::$audioExtensions ) )
					{
						$url = Url::baseUrl( Url::PROTOCOL_RELATIVE ) . "applications/core/interface/file/attachment.php?id=" . $attachment['attach_id'];
						if ( $attachment['attach_security_key'] )
						{
							$url .= "&key={$attachment['attach_security_key']}";
						}

						if ( in_array( mb_strtolower( $ext ), File::$videoExtensions ) )
						{
							$value .= Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedVideo( $attachment['attach_location'], $url, $attachment['attach_file'], File::getMimeType( $attachment['attach_file'] ), $attachment['attach_id'] );
						}
						elseif ( in_array( mb_strtolower( $ext ), File::$audioExtensions ) )
						{
							$value .= Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedAudio( $attachment['attach_location'], $url, $attachment['attach_file'], File::getMimeType( $attachment['attach_file'] ), $attachment['attach_id'] );
						}
					}
					elseif ( $attachment['attach_is_image'] )
					{
						if ( $attachment['attach_thumb_location'] )
						{
							$height = $attachment['attach_thumb_height'];
							$width = $attachment['attach_thumb_width'];
						}
						else
						{
							$height = $attachment['attach_img_height'];
							$width = $attachment['attach_img_width'];
						}

						$altText = NULL;

						if ( Settings::i()->ips_imagescanner_enable_discovery and ! empty( $attachment['attach_labels'] ) )
						{
							if ( $this->_altLabelWord === NULL )
							{
								/* This is stored with the post, so it cannot be the user's language */
								$this->_altLabelWord = Lang::load( Lang::defaultLanguage() )->get( 'alt_label_could_be' );
							}

							$altText = $this->_altLabelWord . ' ' . implode( ', ', Parser::getAttachmentLabels( $attachment ) );
						}

						$value .= Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedImage( $attachment['attach_location'], $attachment['attach_thumb_location'] ?: $attachment['attach_location'], $attachment['attach_file'], $attachment['attach_id'], $width, $height, $altText );
					}
					else
					{
						$url = Url::baseUrl() . "applications/core/interface/file/attachment.php?id=" . $attachment['attach_id'];
						if ( $attachment['attach_security_key'] )
						{
							$url .= "&key={$attachment['attach_security_key']}";
						}
						$fileAttachments[] = Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedFile( $url, $attachment['attach_file'], FALSE, $attachment['attach_ext'], $attachment['attach_id'], $attachment['attach_security_key'] );
					}

					if ( !isset( $parser ) or !in_array( $attachment['attach_id'], $parser->mappedAttachments ) )
					{
						$inserts[] = array(
							'attachment_id'	=> $attachment['attach_id'],
							'location_key'	=> "{$this->options['app']}_{$this->options['key']}",
							'id1'			=> ( is_array( $this->options['attachIds'] ) and isset( $this->options['attachIds'][0] ) ) ? $this->options['attachIds'][0] : NULL,
							'id2'			=> ( is_array( $this->options['attachIds'] ) and isset( $this->options['attachIds'][1] ) ) ? $this->options['attachIds'][1] : NULL,
							'id3'			=> ( is_array( $this->options['attachIds'] ) and isset( $this->options['attachIds'][2] ) ) ? $this->options['attachIds'][2] : NULL,
							'temp'			=> is_string( $this->options['attachIds'] ) ? $this->options['attachIds'] : ( $this->options['attachIds'] === NULL ? md5( $this->options['autoSaveKey'] ) : $this->options['attachIds'] ),
							'lang'			=> $this->options['attachIdsLang'],
						);
					}
				}
			}

			/* Add any file attachments on a single line */
			if( count( $fileAttachments ) )
			{
				$value .= str_replace( Url::baseUrl(), '<___base_url___>/', "<p>" . implode( ' ', $fileAttachments ) . "</p>" );
			}

			if( count( $inserts ) )
			{
				Db::i()->insert( 'core_attachments_map', $inserts, TRUE );
			}

			/* Clear out the post key for attachments that are claimed automatically */
			if ( $this->options['attachIds'] )
			{
				Db::i()->update( 'core_attachments', array( 'attach_post_key' => '' ), array( 'attach_post_key=?', $this->postKey ) );
			}
		}

		/* Remove abandoned attachments */
		foreach ( Db::i()->select( '*', 'core_attachments', array( array( 'attach_id NOT IN(?)', Db::i()->select( 'DISTINCT attachment_id', 'core_attachments_map', NULL, NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER ) ), array( 'attach_member_id=? AND attach_date<?', Member::loggedIn()->member_id, time() - 86400 ) ) ) as $attachment )
		{
			try
			{
				Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );
				File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
				if ( $attachment['attach_thumb_location'] )
				{
					File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
				}
			}
			catch ( Exception $e ) { }
		}

		/* Throw any errors */
		if ( $error )
		{
			$this->value = $value;
			throw $error;
		}

		/* Return */
		return $value;
	}
	
	/**
	 * Set the value of the element
	 *
	 * @param	bool	$initial	Whether this is the initial call or not. Do not reset default values on subsequent calls.
	 * @param	bool	$force		Set the value even if one was not submitted (done on the final validation when getting values)?
	 * @return    void
	 */
	public function setValue( bool $initial=FALSE, bool $force=FALSE ): void
	{
		// @todo extend this to all form elements. See commit notes.
		
		if ( !isset( Request::i()->csrfKey ) or !Login::compareHashes( Session::i()->csrfKey, (string) Request::i()->csrfKey ) )
		{
			$name = $this->name;
			unset( Request::i()->$name );
		}
		
		parent::setValue( $initial, $force );
	}

	/**
	 * Get parser object
	 *
	 * @return	Parser
	 */
	protected function _getParser(): Parser
	{
		return new Parser( ( $this->options['attachIds'] === NULL ? md5( $this->options['autoSaveKey'] ) : $this->options['attachIds'] ), NULL, "{$this->options['app']}_{$this->options['key']}", !$this->bypassFilterProfanity(), method_exists( $this->extension, 'htmlPurifierConfig' ) ? array( $this->extension, 'htmlPurifierConfig' ) : NULL, TRUE, $this->options['attachIdsLang'] );
	}
		
	/**
	 * Can Attach?
	 *
	 * @return	bool
	 */
	protected function canAttach(): bool
	{
		$canAttach = !( ( Member::loggedIn()->group['g_attach_max'] == '0' ) );

		if ( $this->extension )
		{
			$extensionCanAttach = $this->extension->canAttach( Member::loggedIn(), $this );
			if ( $extensionCanAttach !== NULL )
			{
				$canAttach = $extensionCanAttach;
			}
		}
		return $canAttach;
	}

	/**
	 * Can the member attach any of his existing media uploads?
	 *
	 * @return bool
	 */
	protected function canUseMediaExtension(): bool
	{
		/* If we have an extension and it implicit disallows any attaachments, don't allow media extensions too , i.e. contact us form needs this */
		if ( $this->extension )
		{
			$extensionCanAttach = $this->extension->canAttach( Member::loggedIn(), $this );
			if ( $extensionCanAttach === FALSE )
			{
				return FALSE;
			}
		}

		foreach ( Application::allExtensions( 'core', 'EditorMedia' ) as $k => $class )
		{
			if ( $class->count( Member::loggedIn(), '' ) )
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * @brief	Error message to display if we're not showing the uploader
	 */
	protected ?string $noUploaderError = NULL;
	
	/**
	 * Get uploader
	 *
	 * @param	string	$name	Form name
	 * @return    Upload|NULL
	 */
	protected function getUploader( string $name ): ?Upload
	{
		/* Attachments enabled? */
		if ( Settings::i()->attach_allowed_types == 'none' )
		{
			return NULL;
		}
		
		/* Load existing attachments */
		$existing = array();
		$currentPostUsage = 0;
		foreach ( $this->getAttachments() as $attachment )
		{
			try
			{
				$file = File::get( 'core_Attachment', $attachment['attach_location'], $attachment['attach_filesize'] );
				$file->attachmentThumbnailUrl = $attachment['attach_thumb_location'] ? File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->url : $file->url;
				
				/* Reset the original filename based on the attachment record as filenames can be renamed if they contain special characters as AWS being the lowest common denominator cannot
				   handle special characters */
				$file->originalFilename = $attachment['attach_file'];
				$file->securityKey = $attachment['attach_security_key'];
				
				$existing[ $attachment['attach_id'] ] = $file;
				
				$currentPostUsage += $attachment['attach_filesize'];
			}
			catch ( Exception $e ) { }
		}
	
		/* Can we upload more? */
		$error = NULL;
		$maxTotalSize = NULL;
		if ( $maxTotalSize = static::maxTotalAttachmentSize( Member::loggedIn(), $currentPostUsage, $error ) )
		{
			$maxTotalSize = $maxTotalSize / 1048576; // Bytes -> MB or KB -> GB
			$this->noUploaderError = $error;
		}
		
		/* Create the uploader */
		if ( $maxTotalSize === NULL or $maxTotalSize > 0 )
		{
			$maxTotalSize = ( !is_null( $maxTotalSize ) ) ? $maxTotalSize : NULL;
			$postKey = $this->postKey;

			$allowStockPhotos = FALSE;

			if ( ( Settings::i()->pixabay_enabled ) and ( Settings::i()->pixabay_editor_permissions == '*' OR Member::loggedIn()->inGroup( explode( ',', Settings::i()->pixabay_editor_permissions ) ) ) )
			{
				$allowStockPhotos = TRUE;
			}

			$options = array(
				'allowedFileTypes'	=> static::allowedFileExtensions(),
				'template'			=> 'core.attachments.fileItem',
				'multiple'			=> TRUE,
				'postKey'			=> $this->postKey,
				'storageExtension'	=> 'core_Attachment',
				'retainDeleted'		=> TRUE,
				'totalMaxSize'		=> $maxTotalSize,
				'maxFileSize'		=> Member::loggedIn()->group['g_attach_per_post'] ? ( Member::loggedIn()->group['g_attach_per_post'] * 1024 ) / 1048576 : NULL,
				'allowStockPhotos'  => $allowStockPhotos,
				'canBeModerated'	=> ( $this->extension AND Dispatcher::i()->module->key != 'developer' AND method_exists( $this->extension, 'canBeModerated' ) and $this->extension->canBeModerated( Member::loggedIn(), $this ) ),
				'callback' => function( $file ) use ( $postKey )
				{
					try
					{
						$fileInfo = Db::i()->select( 'requires_moderation, labels', 'core_files_temp', array( 'contents=?', (string) $file ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
						$requiresModeration = (bool) $fileInfo['requires_moderation'];
						$labels = $fileInfo['labels'];
					}
					catch ( UnderflowException $e )
					{
						$requiresModeration = FALSE;
						$labels = NULL;
					}

					Db::i()->delete( 'core_files_temp', array( 'contents=?', (string) $file ) );
					
					$attachment = $file->makeAttachment( $postKey, Member::loggedIn(), $requiresModeration, $labels );
					return $attachment['attach_id'];
				}
			);
						
			if ( Settings::i()->attachment_resample_size )
			{
				$maxImageSizes = explode( 'x', Settings::i()->attachment_resample_size );
				if ( $maxImageSizes[0] and $maxImageSizes[1] )
				{
					$options['image'] = array( 'maxWidth' => $maxImageSizes[0], 'maxHeight' => $maxImageSizes[1] );
					if ( Settings::i()->attach_allowed_types != 'images' )
					{
						$options['image']['optional'] = TRUE;
					}
				}
				elseif ( Settings::i()->attach_allowed_types == 'images' )
				{
					$options['image'] = TRUE;
				}
			}
			elseif ( Settings::i()->attach_allowed_types == 'images' )
			{
				$options['image'] = TRUE;
			}
			
			$uploaderName = str_replace( array( '[', ']' ), '_', $name ) . '_upload';
			unset( Request::i()->$uploaderName ); // We are setting the value here, so we don't want the normal form helper to overload, which will wipe out any attachments if there is an error elsewhere in the form
			$uploader = new Upload( $uploaderName, $existing, FALSE, $options );
			$uploader->template = array( Theme::i()->getTemplate( 'forms', 'core', 'global' ), 'editorAttachments' );
			
			/* Handle delete calls */
			if ( isset( Request::i()->postKey ) and Request::i()->postKey == $this->postKey and isset( Request::i()->deleteFile ) )
			{
				/* CSRF check */
				Session::i()->csrfCheck();
				
				/* Get the attachment */
				try
				{				
					$attachment = Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', Request::i()->deleteFile ) )->first();
				}
				catch ( UnderflowException $e )
				{
					Output::i()->json( 'NO_ATTACHMENT' );
				}
				
				/* Delete the maps - Only do this for attachments that have actually been saved (if they haven't been saved, there's nothing in core_attachments_map for us to delete */
				if ( isset( $this->options['attachIds'] ) and is_array( $this->options['attachIds'] ) and count( $this->options['attachIds'] ) )
				{
					$where = array( array( 'location_key=?', "{$this->options['app']}_{$this->options['key']}" ), array( 'attachment_id=?', $attachment['attach_id'] ) );
					$i = 1;

					foreach ( $this->options['attachIds'] as $id )
					{
						$where[] = array( "id{$i}=?", $id );
						$i++;
					}
					if ( $this->options['attachIdsLang'] )
					{
						$where[] = array( "lang=?", $this->options['attachIdsLang'] );
					}

					Db::i()->delete( 'core_attachments_map', $where );
				}
				else
				{
					/* If the attachment hasn't been claimed yet, it should only be deletable by the person who uploaded it */
					if( $attachment['attach_member_id'] != Member::loggedIn()->member_id )
					{
						Output::i()->json( 'NO_ATTACHMENT' );
					}
				}
				
				/* If there's no other maps, we can delete the attachment itself */
				$otherMaps = Db::i()->select( 'COUNT(*)', 'core_attachments_map', array( 'attachment_id=?', $attachment['attach_id'] ) )->first();
				if ( !$otherMaps )
				{
					Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );
					try
					{
						File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
					}
					catch ( Exception $e ) { }
					if ( $attachment['attach_thumb_location'] )
					{
						try
						{
							File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
						}
						catch ( Exception $e ) { }
					}
				}
				
				/* Output */
				Output::i()->json( 'OK' );
			}
		}
		else
		{
			$uploader = NULL;
		}
		
		/* Return */
		return $uploader;
	}
	
	/**
	 * Attachments
	 */
	protected mixed $attachments = NULL;

	/**
	 * Fetch existing attachments
	 *
	 * @return	mixed
	 */
	protected function getAttachments(): mixed
	{
		if ( $this->attachments === NULL )
		{
			$existingAttachments = '';
			$where = array();
			if ( isset( $this->options['attachIds'] ) and is_array( $this->options['attachIds'] ) and count( $this->options['attachIds'] ) )
			{
				$where = array( array( 'location_key=?', "{$this->options['app']}_{$this->options['key']}" ) );
				$i = 1;
				foreach ( $this->options['attachIds'] as $id )
				{
					$where[] = array( "id{$i}=?", $id );
					$i++;
				}				
				
				/* If we only want ones for particular languages, filter them out. We don't do this in the WHERE clause for backwards compatibility */
				if ( $this->options['attachIdsLang'] )
				{
					$setToAllLangs = [];
					$_existingAttachments = [];
										
					foreach ( Db::i()->select( '*', 'core_attachments_map', $where ) as $existingAttachment )
					{
						if ( $existingAttachment['lang'] === NULL )
						{
							$existingAttachment['lang'] = $this->options['attachIdsLang'];
							if ( !in_array( $existingAttachment['attachment_id'], $setToAllLangs ) )
							{
								Db::i()->delete( 'core_attachments_map', [ 'attachment_id=? AND location_key=? AND id1=? AND id2=? AND id3=? and lang IS NULL', $existingAttachment['attachment_id'], $existingAttachment['location_key'], $existingAttachment['id1'], $existingAttachment['id2'], $existingAttachment['id3'] ] );
								foreach ( Lang::languages() as $lang )
								{
									$newRow = $existingAttachment;
									$newRow['lang'] = $lang->id;
									Db::i()->insert( 'core_attachments_map', $newRow );
								}
								$setToAllLangs[] = $existingAttachment['attachment_id'];
							}
						}
						if ( $existingAttachment['lang'] == $this->options['attachIdsLang'] )
						{
							$_existingAttachments[] = $existingAttachment['attachment_id'];
						}
					}
					
					
				}
				else
				{
					$_existingAttachments = iterator_to_array( Db::i()->select( 'attachment_id', 'core_attachments_map', $where ) );
				}
				
				if ( !empty( $_existingAttachments ) )
				{
					$existingAttachments = Db::i()->in( 'attach_id', $_existingAttachments ) . ' OR ';
				}
			}
			
			$this->attachments = Db::i()->select( '*', 'core_attachments', array( $existingAttachments . 'attach_post_key=?', $this->postKey ) );
		}
		
		return $this->attachments;
	}

	/**
	 * Bypass the Profanity Check
	 *
	 * @return bool
	 */
	protected function bypassFilterProfanity(): bool
	{
		return  (bool) Member::loggedIn()->group['g_bypass_badwords'];
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();
		
		if ( $this->options['maxLength'] and strlen( $this->value ) > $this->options['maxLength'] )
		{
			throw new DomainException('form_maxlength_unspecific');
		}

		/* Check for empty content. Considered stripping tags for a more accurate check but there are too many tags that would be allowed,
		so my level of care is low. */
		if( $this->required and $this->value == '<p></p>' )
		{
			throw new InvalidArgumentException('form_required');
		}
		
		return TRUE;
	}

	/**
	 * Get allowed extension file types (NULL for any, empty array for none)
	 *
	 * @return	array|NULL
	 */
	public static function allowedFileExtensions(): ?array
	{
		if ( Settings::i()->attach_allowed_types == 'none' )
		{
			return array();
		}
		elseif ( Settings::i()->attach_allowed_types == 'all' and Settings::i()->attach_allowed_extensions )
		{
			return explode( ',', Settings::i()->attach_allowed_extensions );
		}
		elseif ( Settings::i()->attach_allowed_types == 'media' )
		{
			return array_merge( Image::supportedExtensions(), File::$videoExtensions );
		}
		elseif ( Settings::i()->attach_allowed_types == 'images' )
		{
			return Image::supportedExtensions();
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * @var	Cache|array used within maxTotalAttachmentSize()
	 */
	protected static array|Cache $membersAttachmentUsage = [];
	
	/**
	 * Get the maximum combined size of attachments that can be used or NULL for no limit
	 *
	 * @param	Member	$member				The member
	 * @param int $currentPostUsage	Size, in bytes, of current attachments in the post
	 * @param string|null $error				If a value is passed by reference, will be set to an error string if the value is 0
	 * @return	int|NULL
	 */
	public static function maxTotalAttachmentSize(Member $member, int $currentPostUsage, string &$error = NULL ): ?int
	{
		$maxTotalSize = array();
		
		/* Get the global limit */
		if ( $member->member_id and $member->group['g_attach_max'] > 0 )
		{
			if( !isset( $membersAttachmentUsage[ $member->member_id ] ) )
			{
				$membersAttachmentUsage[ $member->member_id ] = Db::i()->select( 'SUM(attach_filesize)', 'core_attachments', array( 'attach_member_id=?', Member::loggedIn()->member_id ) )->first();
			}
			$globalSpaceRemaining = ( ( $member->group['g_attach_max'] * 1024 ) - $membersAttachmentUsage[ $member->member_id ] );

			$maxTotalSize[] = $globalSpaceRemaining;
			if ( $globalSpaceRemaining <= 0 )
			{
				$error = 'editor_used_global_space';
			}
		}
		
		/* Get the per post limit */
		if ( $member->group['g_attach_per_post'] )
		{
			$perPostSpaceRemaining = ( ( $member->group['g_attach_per_post'] * 1024 ) - $currentPostUsage );
			
			$maxTotalSize[] = $perPostSpaceRemaining;
			if ( $perPostSpaceRemaining <= 0 )
			{
				$error = 'editor_used_post_space';
			}
		}
		
		/* Return whichever is lower */
		return $maxTotalSize ? min( $maxTotalSize ) : NULL;
	}

	/**
	 * Determines if we have any editor plugins
	 *
	 * @return bool
	 */
	public static function hasPlugins() : bool
	{
		/* If we are in dev, just check if we have any editor files at all */
		if( \IPS\IN_DEV )
		{
			foreach( Application::enabledApplications() as $app )
			{
				$path = Application::getRootPath( $app->directory ) . "/applications/" . $app->directory . "/dev/editor";
				if( file_exists( $path ) )
				{
					foreach( new DirectoryIterator( $path ) as $file )
					{
						if( !$file->isDir() and !$file->isDot() and $file->getFilename() != 'js' )
						{
							$components = explode( '.', $file->getFilename() );
							$extension = array_pop( $components );
							if( $extension == 'js' )
							{
								return true;
							}
						}
					}
				}
			}

			return false;
		}

		/* If we are not in dev, check if we have the bundled JS, and if not, build it */
		$pluginJs = "";
		$hasPlugins = false;
		foreach( Application::enabledApplications() as $app )
		{
			$editorFile = Application::getRootPath( $app->directory ) . "/applications/" . $app->directory . "/data/editor.xml";
			if( file_exists( $editorFile ) )
			{
				$xml = SimpleXML::loadFile( $editorFile );
				foreach( $xml->plugin as $plugin )
				{
					$contents = (string) $plugin;
					if( $contents )
					{
						$hasPlugins = true;
					}
					$pluginJs .= <<<js
;((() => {
"use strict";
try {

{$contents}

} catch (e) {

window.Debug?.error(e);

}
})());
js;

				}
			}
		}


		/* This tells the editor that the file is done processing */
		$pluginJs .= <<<js

document.dispatchEvent(new CustomEvent('ips:editorPluginsReady'));
js;

		if( $hasPlugins )
		{
			require_once( ROOT_PATH . '/system/3rd_party/JsMinify/Minifier.php' );
			require_once( ROOT_PATH . '/system/3rd_party/JsMinify/MinifierError.php' );
			require_once( ROOT_PATH . '/system/3rd_party/JsMinify/MinifierExpressions.php' );

			$pluginJs = Minifier::minify( $pluginJs, array( 'flaggedComments' => false ) );

			$jsFile = File::create( 'core_Theme', "editorPlugins.js", $pluginJs );
			Settings::i()->changeValues([
				'editor_plugin_js' => (string) $jsFile
			]);
		}

		return $hasPlugins;
	}

	/**
	 * Get all restrictions that can be applied to the editor
	 *
	 * @return string[]
	 */
	public static function getAllRestrictions() : array
	{
		return static::$editorRestrictions;
	}

	/**
	 * Get the cached restriction setting (saves DB queries)
	 *
	 * @return array
	 */
	public static function getRestrictionSetting() : array
	{
		if ( !isset( static::$restrictionCache ) )
		{
			static::$restrictionCache = [];
			$setting = json_decode( Settings::i()->editor_mode_restrictions, true );
			foreach ( $setting as $k => $v )
			{
				static::$restrictionCache[$k] = array_values( $v ); // it is possible that this gets stored as an associative array/object, but we want a standard indexed array
			}
		}

		return static::$restrictionCache;
	}

	/**
	 * @return string[]
	 */
	public static function getRestrictionDependencies() : array
	{
		return static::$dependentRestrictions;
	}

	/**
	 * Check if a member has permission to use a feature
	 *
	 * @param string $permission	The permission key. This is one of the keys returned by getAllRestrictions()
	 * @param Member|null $member	The member/null for logged in member
	 * @param bool $comments		Whether to use restrictions for the comment editor rather than the normal one
	 *
	 * @return bool
	 */
	public static function memberHasPermission( string $permission, ?Member $member=null, bool $comments = false ) : bool
	{
		$member = $member ?: Member::loggedIn();
		$settings = static::getRestrictionSetting();
		$restrictionLevel = in_array( $permission, $settings[0] ?? [] ) ? 0 : ( in_array( $permission, $settings[1] ?? [] ) ? 1 : null );
		if ( $restrictionLevel === null )
		{
			return true;
		}

		foreach ( $member->groups as $gid )
		{
			$group = Member\Group::load( $gid );
			if ( $group->getEditorRestrictionLevel( $comments ) > $restrictionLevel )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get editor restrictions for the member based on group settings
	 *
	 * @param Member|null $member	The member/null for logged in member
	 * @param bool $comments		Whether to use restrictions for the comment editor rather than the normal one
	 *
	 * @return string[]
	 */
	public static function getMemberRestrictions( ?Member $member=null, bool $comments = false ) : array
	{
		$member = $member ?: Member::loggedIn();
		$settings = static::getRestrictionSetting();
		$restrictions = [];

		$restrictLevel = 0;
		foreach ( $member->groups as $gid )
		{
			$group = Member\Group::load( $gid );
			$restrictLevel = max( $group->getEditorRestrictionLevel( $comments ), $restrictLevel );

			// 2 is the advanced editor, we won't find one more advanced
			if ( $restrictLevel >= 2 )
			{
				$restrictLevel = 2;
				break;
			}
		}

		foreach ( $settings as $level => $_restrictions )
		{
			if ( (int) $restrictLevel > (int) $level )
			{
				continue;
			}

			foreach( $_restrictions as $restriction )
			{
				$restrictions[] = $restriction;
			}
		}

		// Make sure we factor in each dependent restriction
		foreach ( static::$dependentRestrictions as $dependentRestriction => $dependency )
		{
			if ( in_array( $dependency, $restrictions ) and !in_array( $dependentRestriction, $restrictions ) )
			{
				$restrictions[] = $dependentRestriction;
			}
		}

		return $restrictions;
	}

	/**
	 * Save the level of a restriction
	 *
	 * @param string 	$restriction	The restriction
	 * @param int 		$level			Set the level of restriction. -1 everywhere, 0 - not in the comment editor, 1 - only in advanced mode
	 *
	 * @return void
	 */
	public static function setRestrictionLevel( string $restriction, int $level ) : void
	{
		$settings = static::getRestrictionSetting();
		if ( !in_array( $restriction, @$settings[$level] ?: [] ) )
		{
			foreach ( $settings as $_level => $restrictions )
			{
				if ( $_level != $level and in_array( $restriction, $restrictions ) )
				{
					$settings[ $_level ] = array_filter( $restrictions, function( $val ) use ( $restriction ) {
						return $val !== $restriction;
					});
				}
			}

			$settings[$level][] = $restriction;

			Settings::i()->changeValues( [ "editor_mode_restrictions" => json_encode( $settings ) ] );
			static::$restrictionCache = $settings;
		}


		// we're always going to have to change dependencies
		foreach ( static::$dependentRestrictions as $dependent => $dependency )
		{
			if ( $dependency == $restriction and static::getRestrictionLevel( $dependent ) < $level )
			{
				static::setRestrictionLevel( $dependent, $level );
			}
		}
	}

	/**
	 * Remove a custom restriction from the settings array so it's reset to the default (or never used)
	 *
	 * @param string $restriction
	 *
	 * @return void
	 */
	public static function removeRestriction( string $restriction ) : void
	{
		$settings = static::getRestrictionSetting();
		foreach ( $settings as $level => $restrictions )
		{
			$newRestrictions = [];
			foreach ( $restrictions as $_restriction )
			{
				if ( $restriction == $_restriction )
				{
					continue;
				}
				$newRestrictions[] = $_restriction;
			}
			$settings[$level] = $newRestrictions;
		}
		static::$restrictionCache = $settings;
		Settings::i()->changeValues( [ "editor_mode_restrictions" => json_encode( $settings ) ] );
	}

	/**
	 * Cache of the restriction setting to reduce json_decode calls and db queries
	 * @var array
	 */
	protected static array $restrictionCache;

	/**
	 * Get the level of a restriction based on the settings
	 *
	 * @param string $restriction
	 * @return int
	 */
	public static function getRestrictionLevel( string $restriction ) : int
	{
		$settings = static::getRestrictionSetting();
		foreach ( $settings as $level => $restrictions )
		{
			if ( in_array( $restriction, $restrictions ) )
			{
				$dependentLevel = -1;
				if ( isset( static::$dependentRestrictions[$restriction] ) )
				{
					$dependentLevel = static::getRestrictionLevel( static::$dependentRestrictions[ $restriction ] );
				}

				// we want to give the stricter restriction, this or its dependency
				return max( $dependentLevel, (int) $level );
			}
		}

		return -1;
	}
}
