<?php
/**
 * @brief		Records Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		8 April 2014
 */

namespace IPS\cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Application\Module;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Pages\Page;
use IPS\cms\Records\CommentTopicSync;
use IPS\cms\Records\Revisions;
use IPS\Content\Anonymous;
use IPS\Content\Assignable;
use IPS\Content\Comment;
use IPS\Content\ContentMenuLink;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Followable;
use IPS\Content\FuturePublishing;
use IPS\Content\Hideable;
use IPS\Content\Item;
use IPS\Content\ItemTopic;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Featurable;
use IPS\Content\Pinnable;
use IPS\Content\Ratings;
use IPS\Content\Reactable;
use IPS\Content\Reaction;
use IPS\Content\ReadMarkers;
use IPS\Content\Reportable;
use IPS\Content\Review;
use IPS\Content\Shareable;
use IPS\Content\Taggable;
use IPS\Content\Statistics;
use IPS\Content\ViewUpdates;
use IPS\DateTime;
use IPS\Db;
use IPS\Events\Event;
use IPS\File;
use IPS\forums\Topic;
use IPS\forums\Topic\Post;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Menu;
use IPS\Helpers\Table\Content as TableHelper;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function array_slice;
use function count;
use function defined;
use function get_called_class;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function round;
use function strtolower;
use const IPS\ENFORCE_ACCESS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 *
 */
class Records extends Item implements
	Embeddable,
	Filter
{
	use Reactable,
		Reportable,
		Pinnable,
		Anonymous,
		Followable,
		FuturePublishing,
		Lockable,
		MetaData,
		Ratings,
		Shareable,
		Taggable,
		ReadMarkers,
		Statistics,
		Hideable,
		Featurable,
		ViewUpdates,
		ItemTopic,
		Assignable
		{
			FuturePublishing::onPublish as public _onPublish;
			FuturePublishing::getMinimumPublishDate as public _getMinimumPublishDate;
			FuturePublishing::allowPublishDateWhileEditing as public _allowPublishDateWhileEditing;
			Hideable::logDelete as public _logDelete;
			Hideable::onUnhide as public _onUnhide;
			Ratings::canRate as public _canRate;
		}
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = NULL;
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'primary_id_field';

    /**
     * @brief	[ActiveRecord] Database ID Fields
     */
    protected static array $databaseIdFields = array('record_static_furl', 'record_topicid');
    
    /**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = '';

	/**
	 * @brief	Application
	 */
	public static string $application = 'cms';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'records';
	
	/**
	 * @brief	Node Class
	 */
	public static ?string $containerNodeClass = NULL;
	
	/**
	 * @brief	[Content\Item]	Comment Class
	 */
	public static ?string $commentClass = NULL;

	/**
	 * @brief	[Content\Review] Review Class
	 */
	public static ?string $reviewClass = null;
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static bool $firstCommentRequired = FALSE;
	
	/**
	 * @brief	[Content\Item]	Form field label prefix
	 */
	public static string $formLangPrefix = 'content_record_form_';
	
	/**
	 * @brief	[Records] Custom Database Id
	 */
	public static ?int $customDatabaseId = NULL;
	
	/**
	 * @brief 	[Records] Database object
	 */
	protected static array $database = array();
	
	/**
	 * @brief 	[Records] Database object
	 */
	public static string $title = 'content_record_title';
		
	/**
	 * @brief	[Records] Standard fields
	 */
	protected static array $standardFields = array( 'record_publish_date', 'record_expiry_date', 'record_allow_comments', 'record_comment_cutoff' );

	/**
	 * @brief	[CoverPhoto]	Storage extension
	 */
	public static string $coverPhotoStorageExtension = 'cms_Records';

	/**
	 * @brief	Use a default cover photo
	 */
	public static bool $coverPhotoDefault = false;

	/**
	 * The real definition of this happens in the magic autoloader inside the cms\Application class, but we need this one which contains all the "none database id related" fields for the Content Widget
	 *
	 * @var string[]
	 */
	public static array $databaseColumnMap = array(
		'author'				=> 'member_id',
		'container'				=> 'category_id',
		'date'					=> 'record_saved',
		'is_future_entry'       => 'record_future_date',
		'future_date'           => 'record_publish_date',
		'num_comments'			=> 'record_comments',
		'unapproved_comments'	=> 'record_comments_queued',
		'hidden_comments'		=> 'record_comments_hidden',
		'last_comment'			=> 'record_last_comment',
		'last_comment_by'		=> 'record_last_comment_by',
		'last_comment_name'		=> 'record_last_comment_name',
		'views'					=> 'record_views',
		'approved'				=> 'record_approved',
		'pinned'				=> 'record_pinned',
		'locked'				=> 'record_locked',
		'featured'				=> 'record_featured',
		'rating'				=> 'record_rating',
		'rating_hits'			=> 'rating_hits',
		'rating_average'	    => 'record_rating',
		'rating_total'			=> 'rating_value',
		'num_reviews'	        => 'record_reviews',
		'last_review'	        => 'record_last_review',
		'last_review_by'        => 'record_last_review_by',
		'last_review_name'      => 'record_last_review_name',
		'updated'				=> 'record_last_comment',
		'meta_data'				=> 'record_meta_data',
		'author_name'			=> 'record_author_name',
		'is_anon'				=> 'record_is_anon',
		'last_comment_anon'		=> 'record_last_comment_anon',
		'item_topicid'			=> 'record_topicid',
		'cover_photo'			=> 'record_image',
		'cover_photo_offset'	=> 'record_image_offset',
	);


	/**
	 * @brief	Icon
	 */
	public static string $icon = 'file-text';
	
	/**
	 * @brief	Include In Sitemap (We do not want to include in Content sitemap, as we have a custom extension
	 */
	public static bool $includeInSitemap = FALSE;
	
	/**
	 * @brief	Prevent custom fields being fetched twice when loading/saving a form
	 */
	public static array|null $customFields = NULL;

	/**
	 * Most, if not all of these are the same for different events, so we can just have one method
	 *
	 * @param Comment|null $comment A comment item, leave null for these keys to be omitted
	 *
	 * @return  array
	 */
	public function getDataLayerProperties( ?Comment $comment = null ): array
	{
		$commentIdColumn = $comment ? $comment::$databaseColumnId : null;
		$index = $commentIdColumn ? ( $comment->$commentIdColumn ?: 0 ) : 0;
		if ( isset( $this->_dataLayerProperties[$index] ) )
		{
			return $this->_dataLayerProperties[$index];
		}

		/* Set the content_type and comment_type to lower case for consistency */
		$properties = parent::getDataLayerProperties( $comment );
		if ( isset( $properties['content_type'] ) )
		{
			$properties['content_type'] = strtolower( Lang::load( Lang::defaultLanguage() )->addToStack( static::$title ) );
		}

		$this->_dataLayerProperties[$index] = $properties;
		return $properties;
	}

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data Row from database table
	 * @param bool $updateMultitonStoreIfExists Replace current object in multiton store if it already exists there?
	 * @return Records
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );

		/* Prevent infinite redirects */
		if ( ! $obj->record_dynamic_furl and ! $obj->record_static_furl )
		{
			if ( $obj->_title )
			{
				$obj->record_dynamic_furl = Friendly::seoTitle( mb_substr( $obj->_title, 0, 255 ) );
				$obj->save();
			}
		}

		if ( $obj->useForumComments() )
		{
			$obj::$commentClass = 'IPS\cms\Records\CommentTopicSync' . static::$customDatabaseId;
		}

		return $obj;
	}

	/**
	 * Set custom posts per page setting
	 *
	 * @return int
	 */
	public static function getCommentsPerPage(): int
	{
		if ( ! empty( Dispatcher::i()->recordId ) )
		{
			$class = 'IPS\cms\Records' . static::$customDatabaseId;
			try
			{
				/* @var	$class	Records */
				$record = $class::load( Dispatcher::i()->recordId );
				
				if ( $record->_forum_record and $record->_forum_comments and Application::appIsEnabled('forums') )
				{
					return Topic::getCommentsPerPage();
				}
			}
			catch( OutOfRangeException $e )
			{
				/* recordId is usually the record we're viewing, but this method is called on recordFeed widgets in horizontal mode which means recordId may not be __this__ record, so fail gracefully */
				return static::database()->field_perpage;
			}
		}
		else if( static::database()->forum_record and static::database()->forum_comments and Application::appIsEnabled('forums') )
		{
			return Topic::getCommentsPerPage();
		}

		return static::database()->field_perpage;
	}

	/**
	 * Returns the database parent
	 * 
	 * @return Databases
	 */
	public static function database(): Databases
	{
		if ( ! isset( static::$database[ static::$customDatabaseId ] ) )
		{
			static::$database[ static::$customDatabaseId ] = Databases::load( static::$customDatabaseId );
		}
		
		return static::$database[ static::$customDatabaseId ];
	}
	
	/**
	 * Load record based on a URL
	 *
	 * @param	Url	$url	URL to load from
	 * @return	mixed
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl( Url $url ): mixed
	{
		/* First, make sure the PAGE matches */
		$page = Page::loadFromUrl( $url );

		if( $page->_id != static::database()->page_id )
		{
			throw new OutOfRangeException;
		}

		$qs = array_merge( $url->queryString, $url->hiddenQueryString );
		
		if ( isset( $qs['path'] ) )
		{
			$bits = explode( '/', trim( $qs['path'], '/' ) );
			$path = array_pop( $bits );
			
			try
			{
				return static::loadFromSlug( $path, FALSE );
			}
			catch ( Exception $e ) { }
		}

		return parent::loadFromUrl( $url );
	}

	/**
	 * Load from slug
	 *
	 * @param string $slug Thing that lives in the garden and eats your plants
	 * @param bool $redirectIfSeoTitleIsIncorrect If the SEO title is incorrect, this method may redirect... this stops that
	 * @param integer|null $categoryId Optional category ID to restrict the look up in.
	 * @return Records
	 * @throws Exception
	 */
	public static function loadFromSlug( string $slug, bool $redirectIfSeoTitleIsIncorrect=TRUE, int $categoryId=NULL ): static
	{
		$slug = trim( $slug, '/' );
		
		/* If the slug is an empty string, then there is nothing to try and load. */
		if ( empty( $slug ) )
		{
			throw new OutOfRangeException;
		}

		/* Try the easiest option */
		preg_match( '#-r(\d+?)$#', $slug, $matches );

		if ( isset( $matches[1] ) AND is_numeric( $matches[1] ) )
		{
			try
			{
				$record = static::load( $matches[1] );

				/* Check to make sure the SEO title is correct */
				if ( $redirectIfSeoTitleIsIncorrect and urldecode( str_replace( $matches[0], '', $slug ) ) !== $record->record_dynamic_furl and !Request::i()->isAjax() and mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and !ENFORCE_ACCESS )
				{
					$url = $record->url();

					/* Don't correct the URL if the visitor cannot see the record */
					if( !$record->canView() )
					{
						throw new OutOfRangeException;
					}

					/* Redirect to the embed form if necessary */
					if( isset( Request::i()->do ) and Request::i()->do == 'embed' )
					{
						$url = $url->setQueryString( array( 'do' => "embed" ) );
					}

					Output::i()->redirect( $url );
				}

				static::$multitons[ $record->primary_id_field ] = $record;

				return static::$multitons[ $record->primary_id_field ];
			}
			catch( OutOfRangeException $ex ) { }
		}

		$where = array( array( '(? LIKE CONCAT( record_dynamic_furl, \'%\') OR LOWER(record_static_furl)=?)', $slug, mb_strtolower( $slug ) ) );
		if ( $categoryId )
		{
			$where[] = array( 'category_id=?', $categoryId );
		}
		
		foreach( Db::i()->select( '*', static::$databaseTable, $where ) as $record )
		{
			$pass = FALSE;

			if ( $record['record_static_furl'] and mb_strtolower( $slug ) === mb_strtolower( $record['record_static_furl'] ) )
			{
				$pass = TRUE;
			}
			else
			{
				if ( isset( $matches[1] ) AND is_numeric( $matches[1] ) AND $matches[1] == $record['primary_id_field'] )
				{
					$pass = TRUE;
				}
			}
				
			if ( $pass === TRUE )
			{
				static::$multitons[ $record['primary_id_field'] ] = static::constructFromData( $record );
				
				if ( $redirectIfSeoTitleIsIncorrect AND $slug !== $record['record_static_furl'] )
				{
					Output::i()->redirect( static::$multitons[ $record['primary_id_field'] ]->url() );
				}
			
				return static::$multitons[ $record['primary_id_field'] ];
			}	
		}
		
		/* Still here? Consistent with AR pattern */
		throw new OutOfRangeException();
	}

	/**
	 * Load from slug history, so we can 301 to the correct record.
	 *
	 * @param string $slug	Thing that lives in the garden and eats your plants
	 * @return    static
	 * @throws	OutOfRangeException
	 */
	public static function loadFromSlugHistory( string $slug ): static
	{
		$slug = trim( $slug, '/' );

		try
		{
			$row = Db::i()->select( '*', 'cms_url_store', array( 'store_type=? and store_path=?', 'record', $slug ) )->first();
			return static::load( $row['store_current_id'] );
		}
		catch( UnderflowException ) { }

		/* Still here? Consistent with AR pattern */
		throw new OutOfRangeException();
	}

	/**
	 * Indefinite Article
	 *
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @return    string
	 */
	public function indefiniteArticle( ?Lang $lang = NULL ): string
	{
		$lang = $lang ?: Member::loggedIn()->language();
		return $lang->addToStack( 'content_db_lang_ia_' . static::$customDatabaseId, FALSE );
	}

	/**
	 * Indefinite Article
	 *
	 * @param array|null $containerData Container data
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @return    string
	 */
	public static function _indefiniteArticle( ?array $containerData = NULL, ?Lang $lang = NULL ): string
	{
		$lang = $lang ?: Member::loggedIn()->language();
		return $lang->addToStack( 'content_db_lang_ia_' . static::$customDatabaseId, FALSE );
	}

	/**
	 * Definite Article
	 *
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @param integer|boolean $count Number of items. If not FALSE, pluralized version of phrase will be used
	 * @return    string
	 */
	public function definiteArticle( ?Lang $lang = NULL, int|bool $count = FALSE ): string
	{
		$lang = $lang ?: Member::loggedIn()->language();
		if( $count === TRUE || ( is_int( $count ) && $count > 1 ) )
		{
			return $lang->addToStack( 'content_db_lang_pl_' . static::$customDatabaseId, FALSE );
		}
		else
		{
			return $lang->addToStack( 'content_db_lang_sl_' . static::$customDatabaseId, FALSE );
		}
	}

	/**
	 * Definite Article
	 *
	 * @param array|null $containerData Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @param array $options Options to pass to \IPS\Lang::addToStack
	 * @param integer|boolean $count Number of items. If not false, pluralized version of phrase will be used.
	 * @return    string
	 */
	public static function _definiteArticle( ?array $containerData = NULL, ?Lang $lang = NULL, array $options = array(), int|bool $count = FALSE ): string
	{
		$lang = $lang ?: Member::loggedIn()->language();
		if( $count === TRUE || ( is_int( $count ) && $count > 1 ) )
		{
			return $lang->addToStack( 'content_db_lang_pl_' . static::$customDatabaseId, FALSE, $options );
		}
		else
		{
			return $lang->addToStack( 'content_db_lang_sl_' . static::$customDatabaseId, FALSE, $options );
		}
	}

	/**
	 * Get elements for add/edit form
	 *
	 * @param Item|null $item The current item if editing or NULL if creating
	 * @param Model|null $container Container (e.g. forum), if appropriate
	 * @return    array
	 * @throws Exception
	 */
	public static function formElements( ?Item $item=NULL, ?Model $container=NULL ): array
	{
		/* @var	$item Records */
		/* @var	$fieldsClass	Fields */
		$customValues = ( $item ) ? $item->fieldValues() : array();
		$database     = Databases::load( static::$customDatabaseId );
		$fieldsClass  = 'IPS\cms\Fields' .  static::$customDatabaseId;
		$formElements = array();
		$elements     = parent::formElements( $item, $container );
		static::$customFields = $fieldsClass::fields( $customValues, ( $item ? 'edit' : 'add' ), $container, 0, ( ! $item ? NULL : $item ) );

		/* Build the topic state toggles */
		$options = array();
		$toggles = array();
		$values  = array();
		
		/* Title */
		if ( isset( static::$customFields[ $database->field_title ] ) )
		{
			$formElements['title'] = static::$customFields[ $database->field_title ];
			$formElements['title']->rowClasses[] = 'ipsFieldRow--primary';
			$formElements['title']->rowClasses[] = 'ipsFieldRow--fullWidth';
		}

		if ( isset( $elements['guest_name'] ) )
		{
			$formElements['guest_name'] = $elements['guest_name'];
		}
		
		if ( isset( $elements['guest_email'] ) )
		{
			$formElements['guest_email'] = $elements['guest_email'];
		}

		if ( isset( $elements['captcha'] ) )
		{
			$formElements['captcha'] = $elements['captcha'];
		}

		if ( Member::loggedIn()->modPermission('can_content_edit_record_slugs') )
		{
			$formElements['record_static_furl_set'] = new YesNo( 'record_static_furl_set', ( ( $item AND $item->record_static_furl ) ? TRUE : FALSE ), FALSE, array(
					'togglesOn' => array( 'record_static_furl' )
			)  );
			$formElements['record_static_furl'] = new Text( 'record_static_furl', ( ( $item AND $item->record_static_furl ) ? $item->record_static_furl : NULL ), FALSE, array(), function( $val ) use ( $database )
            {
                /* Make sure key is unique */
                if ( empty( $val ) )
                {
                    return true;
                }
                
                /* Make sure it does not match the dynamic URL format */
                if ( preg_match( '#-r(\d+?)$#', $val ) )
                {
	                throw new InvalidArgumentException('content_record_slug_not_unique');
                }

                try
                {
                    $cat = intval( ( isset( Request::i()->content_record_form_container ) ) ? Request::i()->content_record_form_container : 0 );
                    $recordsClass = '\IPS\cms\Records' . $database->id;

					/* @var	$recordsClass Records */
					if ( $recordsClass::isFurlCollision( $val ) )
					{
						 throw new InvalidArgumentException('content_record_slug_not_unique');
					}
					
                    /* Fetch record by static slug */
                    $record = $recordsClass::load( $val, 'record_static_furl' );

                    /* In the same category though? */
                    if ( isset( Request::i()->id ) and $record->_id == Request::i()->id )
                    {
                        /* It's ok, it's us! */
                        return true;
                    }

                    if ( $cat === $record->category_id )
                    {
                        throw new InvalidArgumentException('content_record_slug_not_unique');
                    }
                }
                catch ( OutOfRangeException $e )
                {
                    /* Slug is OK as load failed */
                    return true;
                }

                return true;
            }, Member::loggedIn()->language()->addToStack('record_static_url_prefix', FALSE, array( 'sprintf' => array( Settings::i()->base_url ) ) ), NULL, 'record_static_furl' );
		}
		
		if ( isset( $elements['tags'] ) )
		{ 
			$formElements['tags'] = $elements['tags'];
		}

		/* Now custom fields */
		foreach( static::$customFields as $id => $obj )
		{
			if ( $database->field_title === $id )
			{
				continue;
			}

			$formElements['field_' . $id ] = $obj;

			if ( $database->field_content == $id )
			{
				if ( isset( $elements['auto_follow'] ) )
				{
					$formElements['auto_follow'] = $elements['auto_follow'];
				}

				if ( Settings::i()->edit_log and $item )
				{
					if ( Settings::i()->edit_log == 2 )
					{
						$formElements['record_edit_reason'] = new Text( 'record_edit_reason', $item?->record_edit_reason, FALSE, array( 'maxLength' => 255 ) );
					}
					if ( Member::loggedIn()->group['g_append_edit'] )
					{
						$formElements['record_edit_show'] = new Checkbox( 'record_edit_show', FALSE );
					}
				}
			}
		}
		
		if ( isset( $elements['date'] ) AND $fieldsClass::fixedFieldFormShow( 'record_publish_date' ) AND ( Member::loggedIn()->modPermission( "can_future_publish_content" ) or Member::loggedIn()->modPermission( "can_future_publish_" . static::$title ) ) )
		{
			$formElements['record_publish_date'] = $elements['date'];
		}

		if ( $fieldsClass::fixedFieldFormShow( 'record_image' ) )
		{
			$fixedFieldSettings = static::database()->fixed_field_settings;
			$dims = TRUE;

			if ( isset( $fixedFieldSettings['record_image']['image_dims'] ) AND $fixedFieldSettings['record_image']['image_dims'][0] AND $fixedFieldSettings['record_image']['image_dims'][1] )
			{
				$dims = array( 'maxWidth' => $fixedFieldSettings['record_image']['image_dims'][0], 'maxHeight' => $fixedFieldSettings['record_image']['image_dims'][1] );
			}

			$formElements['record_image'] = new Upload( 'record_image', ( ( $item and $item->record_image ) ? File::get( 'cms_Records', $item->record_image ) : NULL ), FALSE, array( 'image' => $dims, 'storageExtension' => 'cms_Records', 'multiple' => false, 'allowStockPhotos' => true, 'canBeModerated' => TRUE ), NULL, NULL, NULL, 'record_image' );
		}

		if ( $fieldsClass::fixedFieldFormShow( 'record_expiry_date' ) )
		{
			$formElements['record_expiry_date'] = new Date( 'record_expiry_date', ( ( $item AND $item->record_expiry_date ) ? DateTime::ts( $item->record_expiry_date ) : NULL ), FALSE, array(
					'time'          => true,
					'unlimited'     => -1,
					'unlimitedLang' => 'record_datetime_noval'
			) );
		}

		if ( $fieldsClass::fixedFieldFormShow( 'record_allow_comments' ) )
		{
			$formElements['record_allow_comments'] = new YesNo( 'record_allow_comments', ( ( $item ) ? $item->record_allow_comments : TRUE ), FALSE, array(
					'togglesOn' => array( 'record_comment_cutoff' )
			)  );
		}
		
		if ( $fieldsClass::fixedFieldFormShow( 'record_comment_cutoff' ) )
		{
			$formElements['record_comment_cutoff'] = new Date( 'record_comment_cutoff', ( ( $item AND $item->record_comment_cutoff ) ? DateTime::ts( $item->record_comment_cutoff ) : NULL ), FALSE, array(
					'time'          => true,
					'unlimited'     => -1,
					'unlimitedLang' => 'record_datetime_noval'
			), NULL, NULL, NULL, 'record_comment_cutoff' );
		}
		
		/* Post Anonymously */
		if ( $container and $container->canPostAnonymously( $container::ANON_ITEMS ) )
		{
			$formElements['post_anonymously'] = new YesNo( 'post_anonymously', ( $item ) ? $item->isAnonymous() : FALSE , FALSE, array( 'label' => Member::loggedIn()->language()->addToStack( 'post_anonymously_suffix' ) ), NULL, NULL, NULL, 'post_anonymously' );
		}

		if ( static::modPermission( 'lock', NULL, $container ) )
		{
			$options['lock'] = 'create_record_locked';
			$toggles['lock'] = array( 'create_record_locked' );
			
			if ( $item AND $item->record_locked )
			{
				$values[] = 'lock';
			}
		}
			
		if ( static::modPermission( 'pin', NULL, $container ) )
		{
			$options['pin'] = 'create_record_pinned';
			$toggles['pin'] = array( 'create_record_pinned' );
			
			if ( $item AND $item->record_pinned )
			{
				$values[] = 'pin';
			}
		}
		
		$canHide = ( $item ) ? $item->canHide() : ( Member::loggedIn()->group['g_hide_own_posts'] == '1' or in_array( 'IPS\cms\Records' . $database->id, explode( ',', Member::loggedIn()->group['g_hide_own_posts'] ) ) );
		if ( static::modPermission( 'hide', NULL, $container ) or $canHide )
		{
			$options['hide'] = 'create_record_hidden';
			$toggles['hide'] = array( 'create_record_hidden' );
			
			if ( $item AND $item->record_approved === -1 )
			{
				$values[] = 'hide';
			}
		}
			
		if ( static::modPermission( 'feature', NULL, $container ) )
		{
			$options['feature'] = 'create_record_featured';
			$toggles['feature'] = array( 'create_record_featured' );

			if ( $item AND $item->record_featured === 1 )
			{
				$values[] = 'feature';
			}
		}
		
		if ( Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			$formElements['record_meta_keywords'] = new TextArea( 'record_meta_keywords', $item ? $item->record_meta_keywords : '', FALSE );
			$formElements['record_meta_description'] = new TextArea( 'record_meta_description', $item ? $item->record_meta_description : '', FALSE );
		}
		
		if ( count( $options ) or count( $toggles ) )
		{
			$formElements['create_record_state'] = new CheckboxSet( 'create_record_state', $values, FALSE, array(
					'options' 	=> $options,
					'toggles'	=> $toggles,
					'multiple'	=> TRUE
			) );
		}

		return $formElements;
	}

	/**
	 * Total item \count(including children)
	 *
	 * @param	Model	$container			The container
	 * @param	bool			$includeItems		If TRUE, items will be included (this should usually be true)
	 * @param	bool			$includeComments	If TRUE, comments will be included
	 * @param	bool			$includeReviews		If TRUE, reviews will be included
	 * @param	int				$depth				Used to keep track of current depth to avoid going too deep
	 * @return	int|NULL|string	When depth exceeds 10, will return "NULL" and initial call will return something like "100+"
	 * @note	This method may return something like "100+" if it has lots of children to avoid exahusting memory. It is intended only for display use
	 * @note	This method includes counts of hidden and unapproved content items as well
	 */
	public static function contentCount( Model $container, bool $includeItems=TRUE, bool $includeComments=FALSE, bool $includeReviews=FALSE, int $depth=0 ): int|NULL|string
	{
		/* Are we in too deep? */
		if ( $depth > 10 )
		{
			return '+';
		}

		$count = $container->_items;

		if ( static::canViewHiddenItems( NULL, $container ) )
		{
			$count += $container->_unapprovedItems;
		}

		if ( static::canViewFutureItems( NULL, $container ) )
		{
			$count += $container->_futureItems;
		}

		if ( $includeComments )
		{
			$count += $container->record_comments;
		}

		/* Add Children */
		$childDepth	= $depth++;
		foreach ( $container->children() as $child )
		{
			$toAdd = static::contentCount( $child, $includeItems, $includeComments, $includeReviews, $childDepth );
			if ( is_string( $toAdd ) )
			{
				return $count . '+';
			}
			else
			{
				$count += $toAdd;
			}

		}
		return $count;
	}

	/**
	 * [brief] Display title
	 */
	protected string|null $displayTitle = NULL;

	/**
	 * [brief] Display content
	 */
	protected string|null $displayContent = NULL;

	/**
	 * [brief] Record page
	 */
	protected string|null|Page $recordPage = NULL;

	/**
	 * [brief] Custom Display Fields
	 */
	protected array $customDisplayFields = array();
	
	/**
	 * [brief] Custom Fields Database Values
	 */
	protected mixed $customValueFields = NULL;
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( array $values ): void
	{
		$isNew = $this->_new;
		$fieldsClass  = 'IPS\cms\Fields' . static::$customDatabaseId;
		$database     = Databases::load( static::$customDatabaseId );
		$categoryClass = 'IPS\cms\Categories' . static::$customDatabaseId;
		/* @var	$categoryClass Categories */
		$container    = ( ! isset( $values['content_record_form_container'] ) ? $categoryClass::load( $this->category_id ) : $values['content_record_form_container'] );
		$autoSaveKeys = [];
		$imageUploads = [];

		/* Store a revision */
		if ( $database->revisions AND !$isNew )
		{
			$revision = new Revisions;
			$revision->database_id = static::$customDatabaseId;
			$revision->record_id   = $this->_id;
			$revision->data        = $this->fieldValues( TRUE );

			$revision->save();
		}

		if ( isset( Request::i()->postKey ) )
		{
			$this->post_key = Request::i()->postKey;
		}

		if ( $isNew )
		{
			/* Peanut Butter Registering */
			if ( !Member::loggedIn()->member_id and $container and !$container->can( 'add', Member::loggedIn(), FALSE ) )
			{
				$this->record_approved = -3;
			}
			else
			{
				$this->record_approved = static::moderateNewItems( Member::loggedIn(), $container ) ? 0 : 1;
			}
		}

		/* Moderator actions */
		if ( isset( $values['create_record_state'] ) )
		{
			if ( in_array( 'lock', $values['create_record_state'] ) )
			{
				$this->record_locked = 1;
			}
			else
			{
				$this->record_locked = 0;
			}
	
			if ( in_array( 'hide', $values['create_record_state'] ) )
			{
				$this->record_approved = -1;
			}
			else if  ( $this->record_approved !== 0 )
			{
				$this->record_approved = 1;
			}
	
			if ( in_array( 'pin', $values['create_record_state'] ) )
			{
				$this->record_pinned = 1;
			}
			else
			{
				$this->record_pinned = 0;
			}
	
			if ( in_array( 'feature', $values['create_record_state'] ) )
			{
				$this->record_featured = 1;
			}
			else
			{
				$this->record_featured = 0;
			}
		}
	
		/* Dates */
		if ( isset( $values['record_expiry_date'] ) and $values['record_expiry_date'] )
		{
			if ( $values['record_expiry_date'] === -1 )
			{
				$this->record_expiry_date = 0;
			}
			else
			{
				$this->record_expiry_date = $values['record_expiry_date']->getTimestamp();
			}
		}
		if ( isset( $values['record_comment_cutoff'] ) and $values['record_comment_cutoff'] )
		{
			if ( $values['record_comment_cutoff'] === -1 )
			{
				$this->record_comment_cutoff = 0;
			}
			else
			{
				$this->record_comment_cutoff = $values['record_comment_cutoff']->getTimestamp();
			}
		}

		/* Edit stuff */
		if ( !$isNew )
		{
			if ( isset( $values['record_edit_reason'] ) )
			{
				$this->record_edit_reason = $values['record_edit_reason'];
			}

			$this->record_edit_time        = time();
			$this->record_edit_member_id   = Member::loggedIn()->member_id;
			$this->record_edit_member_name = Member::loggedIn()->name;

			if ( isset( $values['record_edit_show'] ) )
			{
				$this->record_edit_show = Member::loggedIn()->group['g_append_edit'] ? $values['record_edit_show'] : TRUE;
			}
		}

		/* Record image */
		if ( array_key_exists( 'record_image', $values ) )
		{			
			if ( $values['record_image'] === NULL )
			{			
				if ( $this->record_image )
				{
					try
					{
						File::get( 'cms_Records', $this->record_image )->delete();
					}
					catch ( Exception $e ) { }
				}
				if ( $this->record_image_thumb )
				{
					try
					{
						File::get( 'cms_Records', $this->record_image_thumb )->delete();
					}
					catch ( Exception $e ) { }
				}
					
				$this->record_image = NULL;
				$this->record_image_thumb = NULL;
			}
			else
			{
				$imageUploads[] = $values['record_image'];
				$fixedFieldSettings = static::database()->fixed_field_settings;

				if ( isset( $fixedFieldSettings['record_image']['thumb_dims'] ) )
				{
					if ( $this->record_image_thumb )
					{
						try
						{
							File::get( 'cms_Records', $this->record_image_thumb )->delete();
						}
						catch ( Exception $e ) { }
					}
					
					$thumb = $values['record_image']->thumbnail( 'cms_Records', $fixedFieldSettings['record_image']['thumb_dims'][0], $fixedFieldSettings['record_image']['thumb_dims'][1] );
				}
				else
				{
					$thumb = $values['record_image'];
				}

				$this->record_image       = (string)$values['record_image'];
				$this->record_image_thumb = (string)$thumb;
			}
		}
		
		/* Should we just lock this? */
		if ( ( isset( $values['record_allow_comments'] ) AND ! $values['record_allow_comments'] ) OR ( $this->record_comment_cutoff > $this->record_publish_date ) )
		{
			$this->record_locked = 1;
		}
		
		if ( Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			foreach( array( 'record_meta_keywords', 'record_meta_description' ) as $k )
			{
				if ( isset( $values[ $k ] ) )
				{
					$this->$k = $values[ $k ];
				}
			}
		}

		/* Custom fields */
		$customValues = array();
		$afterEditNotificationsExclude = [ 'quotes' => [], 'mentions' => [] ];
	
		foreach( $values as $k => $v )
		{
			if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
			{
				$customValues[ $k ] = $v;
			}
		}

		/* @var	$fieldsClass Fields */
		$fieldObjects = $fieldsClass::data( NULL, $container );
		
		if ( static::$customFields === NULL )
		{
			static::$customFields = $fieldsClass::fields( $customValues, ( $isNew ? 'add' : 'edit' ), $container, 0, ( $isNew ? NULL : $this ) );
		}
		
		$seen = [];
		
		foreach( static::$customFields as $key => $field )
		{
			$fieldId = $key;
			$seen[] = $fieldId;
			$key = 'field_' . $fieldId;
			
			if ( !$isNew AND $this->$key )
			{
				$afterEditNotificationsExclude = array_merge_recursive( static::_getQuoteAndMentionIdsFromContent( $this->$key ) );
			}
			
			if ( isset( $customValues[ $field->name ] ) and get_class( $field ) == 'IPS\Helpers\Form\Upload' )
			{
				if ( is_array( $customValues[ $field->name ] ) )
				{
					$items = array();
					foreach( $customValues[ $field->name ] as $obj )
					{
						$imageUploads[] = $obj;
						$items[] = (string) $obj;
					}
					$this->$key = implode( ',', $items );
				}
				else
				{
					$imageUploads[] = $customValues[ $field->name ];
					$this->$key = (string) $customValues[ $field->name ];
				}
			}
			/* If we're using decimals, then the database field is set to DECIMALS, so we cannot using stringValue() */
			else if ( isset( $customValues[ $field->name ] ) and get_class( $field ) == 'IPS\Helpers\Form\Number' and ( isset( $field->options['decimals'] ) and $field->options['decimals'] > 0 ) )
			{
				$this->$key = ( $field->value === '' ) ? NULL : $field->value;
			}
			else
			{
				if ( get_class( $field ) == 'IPS\Helpers\Form\Editor' )
				{
					$autoSaveKeys[] = $isNew ? "RecordField_new_{$fieldId}" : [ $this->_id, $fieldId, static::$customDatabaseId ];
				}
				
				$this->$key = $field::stringValue($customValues[$field->name] ?? NULL);
			}
		}

		/* Now set up defaults */
		if ( $isNew )
		{
			foreach ( $fieldObjects as $obj )
			{
				if ( !in_array( $obj->id, $seen ) )
				{
					/* We've not got a value for this as the field is hidden from us, so let us add the default value here */
					$key        = 'field_' . $obj->id;
					$this->$key = $obj->default_value;
				}
			}
		}

		/* Other data */
		if ( $isNew OR $database->_comment_bump & Databases::BUMP_ON_EDIT )
		{
			$this->record_updated = time();
		}

		$this->record_allow_comments   = $values['record_allow_comments'] ?? (!$this->record_locked);
		
		if ( isset( $values[ 'content_field_' . $database->field_title ] ) )
		{
			$this->record_dynamic_furl     = Friendly::seoTitle( $values[ 'content_field_' . $database->field_title ] );
		}

		if ( isset( $values['record_static_furl_set'] ) and $values['record_static_furl_set'] and isset( $values['record_static_furl'] ) and $values['record_static_furl'] )
		{
			$newFurl = Friendly::seoTitle( $values['record_static_furl'] );

			if ( $newFurl != $this->record_static_furl )
			{
				$this->storeUrl();
			}
			
			$this->record_static_furl = $newFurl;
		}
		else
		{
			if( $isNew )
			{
				$this->record_static_furl = NULL;
			}
			/* Only remove the custom set furl if we are editing, we have the fields set, and they are empty. Otherwise an admin may have set the furl and then changed the author
				to a user who does not have permission to set the furl in which case we don't want it being reset */
			elseif ( isset( $values['record_static_furl_set'] ) and ( !$values['record_static_furl_set'] OR !isset( $values['record_static_furl'] ) OR !$values['record_static_furl'] ) )
			{
				$this->record_static_furl = NULL;
			}
		}
		
		$sendFilterNotifications = $this->checkProfanityFilters( FALSE, !$isNew, NULL, NULL, 'cms_Records' . static::$customDatabaseId, $autoSaveKeys, $imageUploads );
		
		if ( $isNew )
		{
			/* Set the author ID on 'new' only */
			$this->member_id = (int) ( static::$createWithMember ? static::$createWithMember->member_id : Member::loggedIn()->member_id );
		}
		elseif ( !$sendFilterNotifications )
		{
			$this->sendQuoteAndMentionNotifications( array_unique( array_merge( $afterEditNotificationsExclude['quotes'], $afterEditNotificationsExclude['mentions'] ) ) );
		}
		
		if ( isset( $values['content_record_form_container'] ) )
		{
			$this->category_id = ( $values['content_record_form_container'] === 0 ) ? 0 : $values['content_record_form_container']->id;
		}

		$idColumn = static::$databaseColumnId;
		if ( ! $this->$idColumn )
		{
			$this->save();
		}

		/* Check for relational fields and claim attachments once we have an ID */
		foreach( $fieldObjects as $id => $row )
		{
			if ( $row->can( ( $isNew ? 'add' : 'edit' ) ) and $row->type == 'Editor' )
			{
				File::claimAttachments( 'RecordField_' . ( $isNew ? 'new' : $this->_id ) . '_' . $row->id, $this->primary_id_field, $id, static::$customDatabaseId );
			}
			
			if ( $row->can( ( $isNew ? 'add' : 'edit' ) ) and $row->type == 'Upload' )
			{
				
				if ( $row->extra['type'] == 'image' and isset( $row->extra['thumbsize'] ) )
				{
					$dims = $row->extra['thumbsize'];
					$field = 'field_' . $row->id;
					$extra = $row->extra;
					$thumbs = iterator_to_array( Db::i()->select( '*', 'cms_database_fields_thumbnails', array( array( 'thumb_field_id=?', $row->id ) ) )->setKeyField('thumb_original_location')->setValueField('thumb_location') );
					
					if ( $this->$field  )
					{
						foreach( explode( ',', $this->$field ) as $img )
						{
							try
							{
								$original = File::get( 'cms_Records', $img );
								
								try
								{								
									$thumb = $original->thumbnail( 'cms_Records', $dims[0], $dims[1] );
									
									if ( isset( $thumbs[ (string) $original ] ) )
									{
										Db::i()->delete( 'cms_database_fields_thumbnails', array( array( 'thumb_original_location=? and thumb_field_id=? and thumb_record_id=?', (string) $original, $row->id, $this->primary_id_field ) ) );
										
										try
										{
											File::get( 'cms_Records', $thumbs[ (string) $original ] )->delete();
										}
										catch ( Exception $e ) { }
									}
									
									Db::i()->insert( 'cms_database_fields_thumbnails', array(
										'thumb_original_location' => (string) $original,
										'thumb_location'		  => (string) $thumb,
										'thumb_field_id'		  => $row->id,
										'thumb_database_id'		  => static::$customDatabaseId,
										'thumb_record_id'		  => $this->primary_id_field
									) );
								}
								catch ( Exception $e ) { }
							}
							catch ( Exception $e ) { }
						}
				
						/* Remove any thumbnails if the original has been removed */
						$orphans = iterator_to_array( Db::i()->select( '*', 'cms_database_fields_thumbnails', array( array( 'thumb_record_id=?', $this->primary_id_field ), array( 'thumb_field_id=?', $row->id ), array( Db::i()->in( 'thumb_original_location', explode( ',', $this->$field ), TRUE ) ) ) ) );
						
						if ( count( $orphans ) )
						{
							foreach( $orphans as $thumb )
							{
								try
								{
									File::get( 'cms_Records', $thumb['thumb_location'] )->delete();
								}
								catch ( Exception $e ) { }
							}
							
							Db::i()->delete( 'cms_database_fields_thumbnails', array( array( 'thumb_record_id=?', $this->primary_id_field ), array( 'thumb_field_id=?', $row->id ), array( Db::i()->in( 'thumb_original_location', explode( ',', $this->$field ), TRUE ) ) ) );
						}
					}
				}
			}

			if ( $row->can( ( $isNew ? 'add' : 'edit' ) ) and $row->type == 'Item' )
			{
				$field = $this->processItemFieldData( $row );
			}
		}

		parent::processForm( $values );
	}

	/**
	 * Stores the URL so when its changed, the old can 301 to the new location
	 *
	 * @return void
	 */
	public function storeUrl() : void
	{
		if ( $this->record_static_furl )
		{
			Db::i()->insert( 'cms_url_store', array(
				'store_path'       => $this->record_static_furl,
			    'store_current_id' => $this->_id,
			    'store_type'       => 'record'
			) );
		}
	}

	/**
	 * Stats for table view
	 *
	 * @param bool $includeFirstCommentInCommentCount	Determines whether the first comment should be inlcluded in the comment \count(e.g. For "posts", use TRUE. For "replies", use FALSE)
	 * @return	array
	 */
	public function stats( bool $includeFirstCommentInCommentCount=TRUE ): array
	{
		$return = array();

		if ( static::$commentClass and static::database()->options['comments'] )
		{
			$return['comments'] = (int) $this->mapped('num_comments');
		}

		$return['num_views'] = (int) $this->mapped('views');

		return $return;
	}

	/**
	 * Get URL
	 *
	 * @param string|null $action Action
	 * @return    Url
	 */
	public function url( ?string $action=NULL ): Url
	{
		if( $action == 'getPrefComment' )
		{
			$pref = Member::loggedIn()->linkPref() ?: Settings::i()->link_default;

			switch( $pref )
			{
				case 'unread':
					$action = Member::loggedIn()->member_id ? 'getNewComment' : NULL;
					break;

				case 'last':
					$action = 'getLastComment';
					break;

				default:
					$action = NULL;
					break;
			}
		}
		elseif( !Member::loggedIn()->member_id AND $action == 'getNewComment' )
		{
			$action = NULL;
		}

		if ( ! $this->recordPage )
		{
			/* If we're coming through the database controller embedded in a page, $currentPage will be set. If we're coming in via elsewhere, we need to fetch the page */
			try
			{
				$this->recordPage = Page::loadByDatabaseId( static::$customDatabaseId );
			}
			catch( OutOfRangeException $ex )
			{
				if ( Page::$currentPage )
				{
					$this->recordPage = Page::$currentPage;
				}
				else
				{
					throw new LogicException;
				}
			}
		}

		if ( $this->recordPage )
		{
			$pagePath   = $this->recordPage->full_path;
			$class		= '\IPS\cms\Categories' . static::$customDatabaseId;
			/* @var	$class Categories */
			$catPath    = $class::load( $this->category_id )->full_path;
			$recordSlug = ! $this->record_static_furl ? $this->record_dynamic_furl . '-r' . $this->primary_id_field : $this->record_static_furl;

			if ( static::database()->use_categories )
			{
				$url = Url::internal( "app=cms&module=pages&controller=page&path=" . $pagePath . '/' . $catPath . '/' . $recordSlug, 'front', 'content_page_path', $recordSlug );
			}
			else
			{
				$url = Url::internal( "app=cms&module=pages&controller=page&path=" . $pagePath . '/' . $recordSlug, 'front', 'content_page_path', $recordSlug );
			}
		}

		if ( $action )
		{
			$url = $url->setQueryString( 'do', $action );
			$url = $url->setQueryString( 'd' , static::database()->id );
			$url = $url->setQueryString( 'id', $this->primary_id_field );
		}

		return $url;
	}
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		$return = parent::basicDataColumns();
		$return[] = 'category_id';
		$return[] = 'record_static_furl';
		$return[] = 'record_dynamic_furl';
		$return[] = 'record_image';
		return $return;
	}
	
	/**
	 * Query to get additional data for search result / stream view
	 *
	 * @param	array	$items	Item data (will be an array containing values from basicDataColumns())
	 * @return	array
	 */
	public static function searchResultExtraData( array $items ): array
	{
		$categoryIds = array();
		
		foreach ( $items as $item )
		{
			if ( $item['category_id'] )
			{
				$categoryIds[ $item['category_id'] ] = $item['category_id'];
			}
		}
		
		if ( count( $categoryIds ) )
		{
			$categoryPaths = iterator_to_array( Db::i()->select( array( 'category_id', 'category_full_path' ), 'cms_database_categories', Db::i()->in( 'category_id', $categoryIds ) )->setKeyField('category_id')->setValueField('category_full_path') );
			
			$return = array();
			foreach ( $items as $item )
			{
				if ( $item['category_id'] )
				{
					$return[ $item['primary_id_field'] ] = $categoryPaths[ $item['category_id'] ];
				}
			}
			return $return;
		}
		
		return array();
	}

	/**
	 * Template helper method to fetch custom fields to display
	 *
	 * @param string $type       Type of display
	 * @return  array
	 */
	public function customFieldsForDisplay( string $type='display' ): array
	{
		if ( ! isset( $this->customDisplayFields['all'][ $type ] ) )
		{
			$fieldsClass = '\IPS\cms\Fields' . static::$customDatabaseId;
			/* @var	$fieldsClass Fields */
			$this->customDisplayFields['all'][ $type ] = $fieldsClass::display( $this->fieldValues(), $type, $this->container(), 'key', $this );
		}

		return $this->customDisplayFields['all'][ $type ];
	}

	/**
	 * Display a custom field by its key
	 *
	 * @param mixed      $key       Key to fetch
	 * @param string $type      Type of display to fetch
	 * @return mixed
	 */
	public function customFieldDisplayByKey( mixed $key, string $type='display' ): mixed
	{
		$fieldsClass = '\IPS\cms\Fields' . static::$customDatabaseId;
		/* @var	$fieldsClass Fields */
		if ( ! isset( $this->customDisplayFields[ $key ][ $type ] ) )
		{
			foreach ( $fieldsClass::roots( 'view' ) as $row )
			{
				if ( $row->key === $key )
				{
					$field = 'field_' . $row->id;
					$value = ( $this->$field !== '' AND $this->$field !== NULL ) ? $this->$field : $row->default_value;
					$this->customDisplayFields[ $key ][ $type ] = $row->formatForDisplay( $row->displayValue( $value ), $value, $type, $this );
				}
			}
		}

		/* Still nothing? */
		if ( ! isset( $this->customDisplayFields[ $key ][ $type ] ) )
		{
			$this->customDisplayFields[ $key ][ $type ] = NULL;
		}

		return $this->customDisplayFields[ $key ][ $type ];
	}

	/**
	 * Get custom field_x keys and values
	 *
	 * @param boolean $allData	All data (true) or just custom field data (false)
	 * @return	array
	 */
	public function fieldValues( bool $allData=FALSE ): array
	{
		$fields = array();
		
		foreach( $this->_data as $k => $v )
		{
			if ( $allData === TRUE OR mb_substr( $k, 0, 6 ) === 'field_')
			{
				$fields[ $k ] = $v;
			}
		}

		return $fields;
	}
	
	/**
	 * Returns the content images
	 *
	 * @param	int|null	$limit				Number of attachments to fetch, or NULL for all
	 * @param	bool		$ignorePermissions	If set to TRUE, permission to view the images will not be checked
	 * @return	array|NULL
	 * @throws	BadMethodCallException
	 */
	public function contentImages( int $limit = NULL, bool $ignorePermissions = FALSE ): array|null
	{
		$idColumn = static::$databaseColumnId;
		$attachments = array();
		
		/* Record image */
		if ( $this->record_image )
		{
			$attachments[] = array( 'cms_Records' => $this->record_image );
		}

		$internal = Db::i()->select( 'attachment_id', 'core_attachments_map', array( '(location_key=? OR location_key=?) and id1=? and id3=?', 'cms_Records', 'cms_Records' . static::$customDatabaseId, $this->$idColumn, static::$customDatabaseId ) );
		
		/* Attachments */
		foreach( Db::i()->select( '*', 'core_attachments', array( array( 'attach_id IN(?)', $internal ), array( 'attach_is_image=1' ) ), 'attach_id ASC', $limit ) as $row )
		{
			$attachments[] = array( 'core_Attachment' => $row['attach_location'] );
		}
			
		/* Any upload fields */
		$categoryClass = 'IPS\cms\Categories' . static::$customDatabaseId;
		/* @var	$categoryClass Categories */
		/* @var	$fieldsClass Fields */
		$container = $categoryClass::load( $this->category_id );
		$fieldsClass  = 'IPS\cms\Fields' . static::$customDatabaseId;
		$fieldValues = $this->fieldValues();
		$customFields = $fieldsClass::fields( $fieldValues, $ignorePermissions ? NULL : 'edit', $container, 0, $this );

		foreach( $customFields as $key => $field )
		{
			$fieldName = mb_substr( $field->name, 8 );
			if ( get_class( $field ) == 'IPS\Helpers\Form\Upload' )
			{
				if ( is_array( $fieldValues[ $fieldName ] ) )
				{
					foreach( $fieldValues[ $fieldName ] as $fileName )
					{
						$obj = File::get( 'cms_Records', $fileName );
						if ( $obj->isImage() )
						{
							$attachments[] = array( 'cms_Records' => $fileName );
						}
					}
				}
				else
				{
					$obj = File::get( 'cms_Records', $fieldValues[ $fieldName ] );
					if ( $obj->isImage() )
					{
						$attachments[] = array( 'cms_Records' => $fieldValues[ $fieldName ] );
					}
				}
			}
		}
		
		return count( $attachments ) ? array_slice( $attachments, 0, $limit ) : NULL;
	}

	/**
	 * Get the post key or create one if one doesn't exist
	 *
	 * @return  string
	 */
	public function get__post_key() : string
	{
		return ! empty( $this->post_key ) ? $this->post_key : md5( mt_rand() );
	}

	/**
	 * Get the publish date
	 *
	 * @return	string
	 */
	public function get__publishDate() : string
	{
        return $this->record_publish_date ? $this->record_publish_date : $this->record_saved;
	}

	/**
	 * Get the record id
	 *
	 * @return	int|null
	 */
	public function get__id(): ?int
	{
		return $this->primary_id_field;
	}
	
	/**
	 * Get value from data store
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( mixed $key ) : mixed
	{
		$val = parent::__get( $key );
		
		if ( $val === NULL )
		{
			if ( mb_substr( $key, 0, 6 ) === 'field_' and ! preg_match( '/^[0-9]+?/', mb_substr( $key, 6 ) ) )
			{
				$realKey = mb_substr( $key, 6 );
				if ( $this->customValueFields === NULL )
				{
					$fieldsClass = '\IPS\cms\Fields' . static::$customDatabaseId;
					/* @var	$fieldsClass Fields */
					foreach ( $fieldsClass::roots( 'view' ) as $row )
					{
						$field = 'field_' . $row->id; 
						$this->customValueFields[ $row->key ] = array( 'id' => $row->id, 'content' => $this->$field );
					}
				}
				
				if ( isset( $this->customValueFields[ $realKey ] ) )
				{
					$val = $this->customValueFields[ $realKey ]['content'];
				} 
			}
		}
		
		return $val;
	}
	
	/**
	 * Set value in data store
	 *
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 *@see		\IPS\Patterns\ActiveRecord::save
	 */
	public function __set( mixed $key, mixed $value ): void
	{
		if ( $key == 'field_' . static::database()->field_title )
		{
			$this->displayTitle = NULL;
		}
		if ( $key == 'field_' . static::database()->field_content )
		{
			$this->displayContent = NULL;
		}
		
		if ( mb_substr( $key, 0, 6 ) === 'field_' )
		{
			$realKey = mb_substr( $key, 6 );
			
			if ( preg_match( '/^[0-9]+?/', $realKey ) )
			{
				/* Wipe any stored values */
				$this->customValueFields = NULL;
			}
			else
			{
				/* This is setting by key */
				if ( $this->customValueFields === NULL )
				{
					$fieldsClass = '\IPS\cms\Fields' . static::$customDatabaseId;
					/* @var	$fieldsClass Fields */
					foreach ( $fieldsClass::roots( 'view' ) as $row )
					{
						$field = 'field_' . $row->id; 
						$this->customValueFields[ $row->key ] = array( 'id' => $row->id, 'content' => $this->$field );
					}
				}
			
				$field = 'field_' . $this->customValueFields[ $realKey ]['id'];
				$this->$field = $value;
				
				$this->customValueFields[ $realKey ]['content'] = $value;
				
				/* Rest key for the parent::__set() */
				$key = $field;
			}
		}
		
		parent::__set( $key, $value );
	}

	/**
	 * Get the record title for display
	 *
	 * @return	string
	 */
	public function get__title(): string
	{
		$field = 'field_' . static::database()->field_title;

		try
		{
			if ( ! $this->displayTitle )
			{
				$class = '\IPS\cms\Fields' .  static::database()->id;
				/* @var	$class Fields */
				$this->displayTitle = $class::load( static::database()->field_title )->displayValue( $this->$field );
			}

			return $this->displayTitle;
		}
		catch( Exception $e )
		{
			return $this->$field;
		}
	}
	
	/**
	 * Get the record content for display
	 *
	 * @return	string|null
	 */
	public function get__content(): ?string
	{
		$field = 'field_' . static::database()->field_content;

		try
		{
			if ( ! $this->displayContent )
			{
				$class = '\IPS\cms\Fields' .  static::database()->id;
				/* @var	$class Fields */
				$this->displayContent = $class::load( static::database()->field_content )->displayValue( $this->$field );
			}

			return $this->displayContent;
		}
		catch( Exception $e )
		{
			return $this->$field;
		}
	}
	
	/**
	 * Return forum sync on or off
	 *
	 * @return	int
	 */
	public function get__forum_record() : int
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_record;
		}
		
		return static::database()->forum_record;
	}
	
	/**
	 * Return forum post on or off
	 *
	 * @return	int
	 */
	public function get__forum_comments() : int
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_comments;
		}
		
		return static::database()->forum_comments;
	}
	
	/**
	 * Return forum sync delete
	 *
	 * @return	int
	 */
	public function get__forum_delete() : int
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_delete;
		}
		
		return static::database()->forum_delete;
	}
	
	/**
	 * Return forum sync forum
	 *
	 * @return	int
	 * @throws  UnderflowException
	 */
	public function get__forum_forum(): int
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			if( !$this->container()->forum_forum )
			{
				throw new UnderflowException('forum_sync_disabled');
			}

			return $this->container()->forum_forum;
		}
		
		return static::database()->forum_forum;
	}
	
	/**
	 * Return forum sync prefix
	 *
	 * @return	int
	 */
	public function get__forum_prefix() : int
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_prefix;
		}
	
		return static::database()->forum_prefix;
	}
	
	/**
	 * Return forum sync suffix
	 *
	 * @return	int
	 */
	public function get__forum_suffix() : int
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_suffix;
		}
	
		return static::database()->forum_suffix;
	}

	/**
	 * Return record image thumb
	 *
	 * @return	string
	 */
	public function get__record_image_thumb() : string
	{
		return $this->record_image_thumb ?: $this->record_image;
	}

	/**
	 * Get edit line
	 *
	 * @return	string|NULL
	 */
	public function editLine() : ?string
	{
		if ( $this->record_edit_time and ( $this->record_edit_show or Member::loggedIn()->modPermission('can_view_editlog') ) and Settings::i()->edit_log )
		{
			return \IPS\cms\Theme::i()->getTemplate( static::database()->template_display, 'cms', 'database' )->recordEditLine( $this );
		}
		return NULL;
	}

	/**
	 * Get mapped value
	 *
	 * @param string $key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( string $key ): mixed
	{
		if ( $key === 'title' )
		{
			return $this->_title;
		}
		else if ( $key === 'content' )
		{
			return $this->_content;
		}
		else if( $key === 'date')
		{
			return $this->_publishDate;
		}
		
		if ( isset( static::$databaseColumnMap[ $key ] ) )
		{
			$field = static::$databaseColumnMap[ $key ];
				
			if ( is_array( $field ) )
			{
				$field = array_pop( $field );
			}
				
			return $this->$field;
		}
		return NULL;
	}
	
	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void
	{
		$new = $this->_new;
			
		if ( $new OR static::database()->_comment_bump & Databases::BUMP_ON_EDIT )
		{
			$member = Member::load( $this->member_id );
	
			/* Set last comment as record so that category listing is correct */
			if ( $this->record_saved > $this->record_last_comment )
			{
				$this->record_last_comment = $this->record_saved;
			}

			if ( $new )
			{
				$this->record_last_comment_by   = $this->member_id;
				$this->record_last_comment_name = $member->name;
			}
		}
	
		parent::save();

		if ( $this->category_id )
		{
			unset( static::$multitons[ $this->primary_id_field ] );
			
			foreach( static::$multitonMap as $fieldKey => $data )
			{
				foreach( $data as $fieldValue => $primaryId )
				{
					if( $primaryId == $this->primary_id_field )
					{
						unset( static::$multitonMap[ $fieldKey ][ $fieldValue ] );
					}
				}
			}
			
            $class = '\IPS\cms\Categories' . static::$customDatabaseId;
			/* @var	$class Categories */
            $category = $class::load( $this->category_id );
            $category->setLastComment();
			$category->setLastReview();
            $category->save();
        }
	}
	
	/**
	 * Resync last comment
	 *
	 * @param	Comment|null $comment The comment
	 * @return	void
	 */
	public function resyncLastComment( Comment $comment = NULL ): void
	{
		if ( $this->useForumComments() )
		{
			if ( $topic = $this->topic( FALSE ) )
			{
				$topic->resyncLastComment();
			}
		}
		
		parent::resyncLastComment();
	}
	
	/**
	 * Utility method to reset the last commenter of a record
	 *
	 * @param boolean $setCategory    Check and set the last commenter for a category
	 * @return void
	 */
	public function resetLastComment( bool $setCategory=false ): void
	{
		$comment = $this->comments( 1, 0, 'date', 'desc', NULL, FALSE );

		if ( $comment )
		{
			/* @var	$comment Records\Comment */
			$this->record_last_comment      = $comment->mapped('date');
			$this->record_last_comment_by   = $comment->author()->member_id;
			$this->record_last_comment_name = $comment->author()->name;
			$this->record_last_comment_anon	= $comment->isAnonymous();
			$this->save();

			if ( $setCategory and $this->category_id )
			{
				$class = '\IPS\cms\Categories' . static::$customDatabaseId;
				/* @var	$class Categories */
				$class::load( $this->category_id )->setLastComment( NULL );
				$class::load( $this->category_id )->save();
			}
		}
	}

	/**
	 * Resync the comments/unapproved comment counts
	 *
	 * @param string|null $commentClass	Override comment class to use
	 * @return void
	 */
	public function resyncCommentCounts( string $commentClass=NULL ): void
	{
		if ( $this->useForumComments() )
		{
			$topic = $this->topic( FALSE );

			if ( $topic )
			{
				$this->record_comments = $topic->posts - 1;
				$this->record_comments_queued = $topic->topic_queuedposts;
				$this->record_comments_hidden = $topic->topic_hiddenposts;
				$this->save();
			}
		}
		else
		{
			parent::resyncCommentCounts( $commentClass );
		}
	}
	
	/**
	 * Are comments supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsComments( Member $member = NULL, Model $container = NULL ): bool
	{
		return parent::supportsComments() and static::database()->options['comments'];
	}
	
	/**
	 * Are reviews supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsReviews( Member $member = NULL, Model $container = NULL ): bool
	{
		return parent::supportsReviews() and static::database()->options['reviews'];
	}

	/**
	 * Ensure there aren't any collisions with page slugs
	 *
	 * @param string $slug
	 * @return  boolean
	 */
	static public function isFurlCollision( string $slug ): bool
	{
		try
		{
			Db::i()->select( 'page_id', 'cms_pages', array( 'page_seo_name=?', Friendly::seoTitle( $slug ) ) )->first();
			
			return TRUE;
		}
		catch( UnderflowException $e )
		{
			return FALSE;
		}
	}
	
	/* !Relational Fields */
	/**
	 * Returns an array of Content items that have been linked to from another database.
	 * I think at least. The concept makes perfect sense until I think about it too hard.
	 *
	 * @note The returned array is in the format of {field_id} => array( object, object... )
	 *
	 * @return array|bool
	 */
	public function getReciprocalItems() : array|bool
	{
		/* Check to see if any fields are linking to this database in this easy to use method wot I writted myself */
		if ( Databases::hasReciprocalLinking( static::database()->_id ) )
		{
			$return = array();
			/* Oh that's just lovely then. Lets be a good fellow and fetch the items then! */
			foreach( Db::i()->select( '*', 'cms_database_fields_reciprocal_map', array( 'map_foreign_database_id=? and map_foreign_item_id=?', static::database()->_id, $this->primary_id_field ) ) as $record )
			{
				try
				{
					$recordClass = 'IPS\cms\Records' . $record['map_origin_database_id'];
					/* @var	$recordClass Records */
                    $linkedRecord = $recordClass::load( $record['map_origin_item_id'] );
                    if( $linkedRecord->canView() )
                    {
                        $return[ $record['map_field_id'] ][] = $linkedRecord;
                    }
				}
				catch ( Exception $ex ) { }
			}
			
			/* Has something gone all kinds of wonky? */
			if ( ! count( $return ) )
			{
				return FALSE;
			}

			return $return;
		}

		return FALSE;
	}
	
	/* !IP.Board Integration */
	
	/**
	 * Use forum for comments
	 *
	 * @return boolean
	 */
	public function useForumComments(): bool
	{
		try
		{
			return $this->_forum_record and $this->_forum_comments and $this->record_topicid and Application::appIsEnabled('forums');
		}
		catch( Exception $e)
		{
			return FALSE;
		}

	}

	/**
	 * Convert the record to a RecordTopicSynch object
	 *
	 * @return Records
	 */
	public function recordForTopicSynch() : Records
	{
		/* @var Records $class */
		$class = 'IPS\cms\Records\RecordsTopicSync' . static::$customDatabaseId;
		return $class::constructFromData( $this->_data );
	}

	/**
	 * Do Moderator Action
	 *
	 * @param string $action	The action
	 * @param Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param string|null $reason	Reason (for hides)
	 * @param bool $immediately Delete Immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction( string $action, ?Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		parent::modAction( $action, $member, $reason, $immediately );
		
		if ( $this->useForumComments() and ( $action === 'lock' or $action === 'unlock' ) )
		{
			if ( $topic = $this->topic() )
			{
				$topic->state = ( $action === 'lock' ? 'closed' : 'open' );
				$topic->save();	
			}
		}
	}

	/**
	 * Move
	 *
	 * @param	Model	$container	Container to move to
	 * @param bool $keepLink	If TRUE, will keep a link in the source
	 * @return	void
	 */
	public function move( Model $container, bool $keepLink=FALSE ): void
	{
		parent::move( $container, $keepLink );

		if( $this->record_static_furl )
		{
			$this->storeUrl();
		}
	}

	/**
	 * Get comments
	 *
	 * @param int|null $limit The number to get (NULL to use static::getCommentsPerPage())
	 * @param int|null $offset The number to start at (NULL to examine \IPS\Request::i()->page)
	 * @param string $order The column to order by
	 * @param string $orderDirection "asc" or "desc"
	 * @param Member|null $member If specified, will only get comments by that member
	 * @param bool|null $includeHiddenComments Include hidden comments or not? NULL to base of currently logged in member's permissions
	 * @param DateTime|null $cutoff If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param mixed $extraWhereClause Additional where clause(s) (see \IPS\Db::build for details)
	 * @param bool|null $bypassCache Used in cases where comments may have already been loaded i.e. splitting comments on an item.
	 * @param bool $includeDeleted Include Deleted Comments
	 * @param bool|null $canViewWarn TRUE to include Warning information, NULL to determine automatically based on moderator permissions.
	 * @return    array|NULL|Comment    If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public function comments( ?int $limit=NULL, ?int $offset=NULL, string $order='date', string $orderDirection='asc', ?Member $member=NULL, ?bool $includeHiddenComments=NULL, ?DateTime $cutoff=NULL, mixed $extraWhereClause=NULL, bool $bypassCache=FALSE, bool $includeDeleted=FALSE, ?bool $canViewWarn=NULL ): array|NULL|Comment
	{
		if ( $this->useForumComments() )
		{
			$recordClass = 'IPS\cms\Records\RecordsTopicSync' . static::$customDatabaseId;

			/* If we are pulling in ASC order we want to jump up by 1 to account for the first post, which is not a comment */
			if( mb_strtolower( $orderDirection ) == 'asc' )
			{
				$_pageValue = ( Request::i()->page ? intval( Request::i()->page ) : 1 );

				if( $_pageValue < 1 )
				{
					$_pageValue = 1;
				}
				
				$offset = ( ( $_pageValue - 1 ) * static::getCommentsPerPage() ) + 1;
			}

			/* @var	$recordClass Records */
			return $recordClass::load( $this->record_topicid )->comments( $limit, $offset, $order, $orderDirection, $member, $includeHiddenComments, $cutoff, $extraWhereClause, $bypassCache, $includeDeleted, $canViewWarn );
		}
		else
		{
			/* Because this is a static property, it may have been overridden by a block on the same page. */
			if ( get_called_class() != 'IPS\cms\Records\RecordsTopicSync' . static::$customDatabaseId )
			{
				static::$commentClass = 'IPS\cms\Records\Comment' . static::$customDatabaseId;
			}
		}

		$where = NULL;
		if( static::$commentClass != 'IPS\cms\Records\CommentTopicSync' . static::$customDatabaseId )
		{
			$where = array( array( 'comment_database_id=?', static::$customDatabaseId ) );
			
			if( $extraWhereClause !== NULL )
			{
				if ( !is_array( $extraWhereClause ) or !is_array( $extraWhereClause[0] ) )
				{
					$extraWhereClause = array( $extraWhereClause );
				}
				
				$where = array_merge( $where, $extraWhereClause );
			}
		}
		
		return parent::comments( $limit, $offset, $order, $orderDirection, $member, $includeHiddenComments, $cutoff, $where, $bypassCache, $includeDeleted, $canViewWarn );
	}

	/**
	 * Get review page count
	 *
	 * @return	int
	 */
	public function reviewPageCount(): int
	{
		if ( $this->reviewPageCount === NULL )
		{
			/* @var Review $reviewClass */
			/* @var array $databaseColumnMap */
			$reviewClass = static::$reviewClass;
			$idColumn = static::$databaseColumnId;
			/* @var array $databaseColumnMap */
			$where = array( array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
			$where[] = array( 'review_database_id=?', static::$customDatabaseId );
			$count = $reviewClass::getItemsWithPermission( $where, NULL, NULL, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );
			$this->reviewPageCount = ceil( $count / static::$reviewsPerPage );

			if( $this->reviewPageCount < 1 )
			{
				$this->reviewPageCount	= 1;
			}
		}
		return $this->reviewPageCount;
	}

	/**
	 * Get reviews
	 *
	 * @param int|null $limit The number to get (NULL to use static::getCommentsPerPage())
	 * @param int|null $offset The number to start at (NULL to examine \IPS\Request::i()->page)
	 * @param string|null $order The column to order by (NULL to examine \IPS\Request::i()->sort)
	 * @param string $orderDirection "asc" or "desc" (NULL to examine \IPS\Request::i()->sort)
	 * @param Member|null $member If specified, will only get comments by that member
	 * @param bool|null $includeHiddenReviews
	 * @param DateTime|null $cutoff If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param mixed $extraWhereClause Additional where clause(s) (see \IPS\Db::build for details)
	 * @param bool|null $bypassCache
	 * @param bool $includeDeleted Include deleted content
	 * @param bool|null $canViewWarn TRUE to include Warning information, NULL to determine automatically based on moderator permissions.
	 * @return    array|NULL|Review    If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public function reviews( ?int $limit=NULL, ?int $offset=NULL, ?string $order='date', string $orderDirection='asc', ?Member $member=NULL, ?bool $includeHiddenReviews=NULL, ?DateTime $cutoff=NULL, mixed $extraWhereClause=NULL, bool $bypassCache=FALSE, bool $includeDeleted=FALSE, ?bool $canViewWarn=NULL ): array|NULL|Review
	{
		$where = array( array( 'review_database_id=?', static::$customDatabaseId ) );

		return parent::reviews( $limit, $offset, $order, $orderDirection, $member, $includeHiddenReviews, $cutoff, $where, $includeDeleted );
	}

	/**
	 * Get available comment/review tabs
	 *
	 * @return	array
	 */
	public function commentReviewTabs(): array
	{
		$tabs = array();
		if ( static::database()->options['reviews'] )
		{
			$tabs['reviews'] = Member::loggedIn()->language()->addToStack( 'cms_review_count', TRUE, array( 'pluralize' => array( $this->mapped('num_reviews') ) ) );
		}
		if ( static::database()->options['comments'] )
		{
			$count = $this->mapped('num_comments');
			if ( Application::appIsEnabled('forums') and $this->_forum_comments and $topic = $this->topic() )
			{
				if ( $count != ( $topic->posts - 1 ) )
				{
					$this->record_comments = $topic->posts - 1;
					$this->save();
				}
				
				$count = ( $topic->posts - 1 ) > 0 ? $topic->posts - 1 : 0;
			}
			
			$tabs['comments'] = Member::loggedIn()->language()->addToStack( 'cms_comment_count', TRUE, array( 'pluralize' => array( $count ) ) );
		}

		return $tabs;
	}

	/**
	 * Get comment/review output
	 *
	 * @param string|null $tab Active tab
	 * @return    string
	 */
	public function commentReviews( string $tab=NULL ): string
	{
		if ( $tab === 'reviews' )
		{
			return (string) \IPS\cms\Theme::i()->getTemplate( static::database()->template_display, 'cms', 'database' )->reviews( $this );
		}
		elseif( $tab === 'comments' )
		{
			return (string) \IPS\cms\Theme::i()->getTemplate( static::database()->template_display, 'cms', 'database' )->comments( $this );
		}

		return '';
	}

	/**
	 * @brief Skip topic creation, useful if the topic may already exist
	 */
	public static bool $skipTopicCreation = FALSE;

	/**
	 * @brief Are we creating a record? Ignore topic syncs until we are done if so.
	 */
	protected static bool $creatingRecord = FALSE;

	/**
	 * @brief Store the member we are creating with if not the logged in member
	 */
	protected static ?Member $createWithMember = NULL;

	/**
	 * Create from form
	 *
	 * @param array $values Values from form
	 * @param Model|null $container Container (e.g. forum), if appropriate
	 * @param bool $sendNotification TRUE to automatically send new content notifications (useful for items that may be uploaded in bulk)
	 * @return    static
	 */
	public static function createFromForm( array $values, Model $container = NULL, bool $sendNotification = TRUE ): static
	{
		if ( isset( $values['record_author_choice'] ) and $values['record_author_choice'] == 'notme' )
		{
			static::$createWithMember = $values['record_member_id'];
		}

		static::$creatingRecord = TRUE;
		$record = parent::createFromForm( $values, $container, $sendNotification );
		static::$creatingRecord = FALSE;

		return $record;
	}

	/**
	 * Create generic object
	 *
	 * @param Member $author The author
	 * @param string|null $ipAddress The IP address
	 * @param DateTime $time The time
	 * @param Model|null $container Container (e.g. forum), if appropriate
	 * @param bool|null $hidden Hidden? (NULL to work our automatically)
	 * @return    static
	 */
	public static function createItem( Member $author, ?string $ipAddress, DateTime $time, Model $container = NULL, bool $hidden=NULL ): static
	{
		/* This is fired inside createFromForm, and we need to switch the author? */
		return parent::createItem( static::$createWithMember !== NULL ? static::$createWithMember : $author, $ipAddress, $time, $container, $hidden );
	}

	/**
	 * Set the reciprocal field data
	 *
	 * @param mixed $row
	 * @return string
	 */
	public function processItemFieldData(Fields $row ): string
	{
		$idColumn = static::$databaseColumnId;
		Db::i()->delete( 'cms_database_fields_reciprocal_map', array('map_origin_database_id=? and map_field_id=? and map_origin_item_id=?', static::$customDatabaseId, $row->id, $this->_id) );

		$field = 'field_' . $row->id;
		$extra = $row->extra;
		if ( $this->$field and !empty( $extra['database'] ) )
		{
			foreach ( explode( ',', $this->$field ) as $foreignId )
			{
				if ( $foreignId )
				{
					Db::i()->insert( 'cms_database_fields_reciprocal_map', array(
						'map_origin_database_id' => static::$customDatabaseId,
						'map_foreign_database_id' => $extra['database'],
						'map_origin_item_id' => $this->$idColumn,
						'map_foreign_item_id' => $foreignId,
						'map_field_id' => $row->id
					) );
				}
			}
		}
		return $field;
	}
	
	/**
	 * Process the comment form
	 *
	 * @param	array	$values		Array of `$form` values
	 * @return  Comment
	 */
	public function processCommentForm( array $values ): Comment
	{
		if ( $this->useForumComments() )
		{
			$topic = $this->topic( FALSE );
		
			if ( $topic === NULL )
			{
				try
				{
					$this->syncTopic();
				}
				catch( Exception $ex ) { }
				
				/* Try again */
				/** @var Topic $topic */
				$topic = $this->topic( FALSE );
				if ( ! $topic or $topic->isArchived() )
				{
					return parent::processCommentForm( $values );
				}
			}
			
			$comment = $values[ static::$formLangPrefix . 'comment' . '_' . $this->_id ];
			$post    = Post::create( $topic, $comment, false, ( $values['guest_name'] ?? null ) );
			
			$commentClass = 'IPS\cms\Records\CommentTopicSync' . static::$customDatabaseId;

			$idColumn = static::$databaseColumnId;
			$autoSaveKey = 'reply-' . static::$application . '/' . static::$module  . '-' . $this->$idColumn;

			/* First we have to update the attachment location key */
			Db::i()->update( 'core_attachments_map', array( 'location_key' => 'forums_Forums' ), array( 'temp=?', md5( $autoSaveKey ) ) );

			/* Then "claim" the attachments */
			$parameters = array_merge( array( $autoSaveKey ), $post->attachmentIds() );
			File::claimAttachments( ...$parameters );
			
			$topic->markRead();
			
			/* Post anonymously */
			if( isset( $values[ 'post_anonymously' ] ) )
			{
				$post->setAnonymous( $values[ 'post_anonymously' ] );
				$this->syncRecordFromTopic( $topic );
			}

			/* @var	$commentClass Records\Comment */
			$comment = $commentClass::load( $post->pid );

			/* Fire the event here because we don't run the parent method */
			Event::fire( 'onCreateOrEdit', $comment, array( $values, TRUE ) );

			return $comment;
			
		}
		else
		{
			return parent::processCommentForm( $values );
		}
	}
	
	/**
	 * Syncing to run when publishing something previously pending publishing
	 *
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onPublish( Member|false|null $member ) : void
	{
		$this->_onPublish( $member );

		/* If last topic/review columns are in the future, reset them or the content will indefinitely show as unread */
		$this->record_last_review = ( $this->record_last_review > $this->record_publish_date ) ? $this->record_publish_date : $this->record_last_review;
		$this->record_last_comment = ( $this->record_last_comment > $this->record_publish_date ) ? $this->record_publish_date : $this->record_last_comment;
		$this->save();
	}
	
	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool					$approving	If true, is being approved for the first time
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnhide( bool $approving, Member|bool|null $member ) : void
	{
		$this->_onUnhide( $approving, $member );
		
		if ( $this->record_expiry_date )
		{
			$this->record_expiry_date = 0;
			$this->save();
		}
	}
	
	/**
	 * Get last comment author
	 * Overloaded for the bump on edit shenanigans 
	 *
	 * @return	Member
	 * @throws	BadMethodCallException
	 */
	public function lastCommenter(): Member
	{
		if ( ( static::database()->_comment_bump & ( Databases::BUMP_ON_EDIT + Databases::BUMP_ON_COMMENT ) and $this->record_edit_time > 0 and $this->record_edit_time > $this->record_last_comment ) OR
			 ( ( static::database()->_comment_bump & Databases::BUMP_ON_EDIT ) and !( static::database()->_comment_bump & ( Databases::BUMP_ON_EDIT + Databases::BUMP_ON_COMMENT ) ) and $this->record_edit_time > 0 ) )
		{
			try
			{
				$this->_lastCommenter = Member::load( $this->record_edit_member_id );
				return $this->_lastCommenter;
			}
			catch( Exception $e ) { }
		}
		
		return parent::lastCommenter();
	}

	/**
	 * Is this topic linked to a record?
     *
     * @param   Topic   $topic  Forums topic
	 * @return boolean
	 */
	public static function topicIsLinked( Topic $topic ) : bool
	{
		return !((static::getLinkedRecord($topic) === NULL));
	}

	/**
	 * @brief	Cached linked record checks to prevent duplicate queries
	 */
	protected static array $linkedRecordLookup = array();
	
	/**
	 * Is this topic linked to a record?
     *
     * @param   Item   $topic  Forums topic
	 * @return  Records|NULL
	 */

	public static function getLinkedRecord( Topic $topic ) : ?Records
	{
		if( array_key_exists( $topic->tid, static::$linkedRecordLookup ) )
		{
			return static::$linkedRecordLookup[ $topic->tid ];
		}

		static::$linkedRecordLookup[ $topic->tid ] = NULL;

		foreach( Databases::databases() as $database )
		{
			try
			{
				if ( $database->forum_record and $database->forum_forum == $topic->container()->_id )
				{
					$class = '\IPS\cms\Records' . $database->id;
					/* @var	$class Records */
					$record = $class::load( $topic->tid, 'record_topicid' );
				
					if ( $record->_forum_record )
					{
						static::$linkedRecordLookup[ $topic->tid ] = $record;
					}
				}
			}
			catch( Exception $e ) { }
		}
		
		return static::$linkedRecordLookup[ $topic->tid ];
	}

	/**
	 * Sync topic details to the record
	 *
	 * @param   Topic   $topic  Forums topic
	 * @return  void
	 */
	public function syncRecordFromTopic( Topic $topic ) : void
	{
		if ( $this->_forum_record and $this->_forum_forum and $this->_forum_comments )
		{
			$this->record_last_comment_by   = $topic->last_poster_id;
			$this->record_last_comment_name = $topic->last_poster_name;
			$this->record_last_comment      = $topic->last_post;
			$this->record_comments_queued   = $topic->topic_queuedposts;
			$this->record_comments_hidden 	= $topic->topic_hiddenposts;
			$this->record_comments          = $topic->posts - 1;
			$this->save();
		}
	}

	/**
	 * Get fields for the topic
	 * 
	 * @return array
	 */
	public function topicFields() : array
	{
		$fieldsClass = 'IPS\cms\Fields' . static::$customDatabaseId;
		/* @var	$fieldsClass Fields */
		$fieldData   = $fieldsClass::data( 'view', $this->container() );
		$fieldValues = $fieldsClass::display( $this->fieldValues(), 'record', $this->container(), 'id' );

		$fields = array();
		foreach( $fieldData as $id => $data )
		{
			if ( $data->topic_format )
			{
				if ( isset( $fieldValues[ $data->id ] ) )
				{
					$html = str_replace( '{title}'  , $data->_title, $data->topic_format );
					$html = str_replace( '{content}', $fieldValues[ $data->id ], $html );
					$html = str_replace( '{value}'  , $fieldValues[ $data->id ], $html );
				
					$fields[ $data->id ] = $html;
				}
			}
		}

		if ( ! count( $fields ) )
		{
			$fields[ static::database()->field_content ] = $fieldValues['content'];
		}

		return $fields;
	}
	
	/**
	 * @brief	Store the comment page count otherwise $topic->posts is reduced by 1 each time it is called
	 */
	protected ?int $recordCommentPageCount = NULL;
	
	/**
	 * Get comment page count
	 *
	 * @param	bool		$recache		TRUE to recache the value
	 * @return	int
	 */
	public function commentPageCount( bool $recache=FALSE ): int
	{
		if ( $this->recordCommentPageCount === NULL or $recache === TRUE )
		{
			/* If the database has a custom per page set, use that. */
			if ( static::database()->comments_perpage )
			{
				$this->recordCommentPageCount = parent::commentPageCount( $recache );
			}
			/* Otherwise do the normal stuff */
			else
			{
				if ( $this->useForumComments() )
				{
					try
					{
						$topic = $this->topic();

						if ( $topic !== null )
						{
							/* Store the real count so it is not accidentally written as the actual value */
							$realCount = $topic->posts;

							/* Compensate for the first post (which is actually the record) */
							$topic->posts = ( $topic->posts - 1 ) > 0 ? $topic->posts - 1 : 0;

							/* Get our page count considering all of that */
							$this->recordCommentPageCount = $topic->commentPageCount();

							/* Reset the count back to the real count */
							$topic->posts = $realCount;
						}
						else
						{
							$this->recordCommentPageCount = 1;
						}
					}
					catch ( Exception $e )
					{
					}
		}
			}
		}
		
		return $this->recordCommentPageCount ?? 1;
	}

	/**
	 * Log for deletion later
	 *
	 * @param	Member|null 	$member	The member, NULL for currently logged in, or FALSE for no member
	 * @return	void
	 */
	public function logDelete( Member $member = NULL ) : void
	{
		$this->_logDelete( $member );

		if ( $topic = $this->topic() and $this->_forum_delete )
		{
			$topic->logDelete( $member );
		}
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$topic        = $this->topic();
		$commentClass = static::$commentClass;
		
		if ( $this->topic() and $this->_forum_delete )
		{
			$topic->delete();
		}
		else if ( $this->topic() )
		{
			/* We have an attached topic, but we don't want to delete the topic so remove commentClass otherwise we'll delete posts */
			static::$commentClass = NULL;
		}

		/* Remove Record Image And Record Thumb Image */
		if ( $this->record_image )
		{
			try
			{
				File::get( 'cms_Records', $this->record_image )->delete();
			}
			catch( Exception $e ){}
		}

		if ( $this->record_image_thumb )
		{
			try
			{
				File::get( 'cms_Records', $this->record_image_thumb )->delete();
			}
			catch ( Exception $e ) { }
		}

		/* Clean up any other uploaded files */
		$fieldsClass = '\IPS\cms\Fields' . static::$customDatabaseId;
		/* @var	$fieldsClass Fields */
		foreach( $fieldsClass::roots( NULL ) as $id => $field )
		{
			if( $field->type == 'Upload' )
			{
				$fieldName = 'field_' . $field->id;

				if ( $this->$fieldName )
				{
					try
					{
						File::get( 'cms_Records', $this->$fieldName )->delete();
					}
					catch( Exception $e ){}
				}

				/* Delete thumbnails */
				foreach( Db::i()->select( '*', 'cms_database_fields_thumbnails', array( array( 'thumb_field_id=? AND thumb_record_id=?', $field->id, $this->primary_id_field ) ) ) as $thumb )
				{
					try
					{
						File::get( 'cms_Records', $thumb['thumb_location'] )->delete();
					}
					catch( Exception $e ){}
				}
			}
		}

		/* Remove any reciprocal linking */
		Db::i()->delete( 'cms_database_fields_reciprocal_map', array( 'map_origin_database_id=? and map_origin_item_id=?', static::database()->id, $this->_id ) );
		
		parent::delete();
		
		if ( $this->topic() )
		{
			static::$commentClass = $commentClass;
		}
	}

	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( Member $member=null ): bool
	{
		if( !parent::canView( $member ) )
		{
			return FALSE;
		}

		/* This prevents auto share and notifications being sent out */
		try
		{
			$page = Page::loadByDatabaseId( static::database()->id );
			if ( !$page->can( 'view', $member ) )
			{
				return FALSE;
			}
		}
		catch( OutOfRangeException $e )
		{
			/* If the database isn't assigned to a page they won't be able to view the record */
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();

		if ( !$this->container()->can_view_others and !$member->modPermission( 'can_content_view_others_records' ) )
		{
			if ( $member !== $this->author() )
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Check if a specific action is available for this Content.
	 * Default to TRUE, but used for overrides in individual Item/Comment classes.
	 *
	 * @param string $action
	 * @param Member|null	$member
	 * @return bool
	 */
	public function actionEnabled( string $action, ?Member $member=null ) : bool
	{
		if( $action == 'move' and !static::database()->use_categories )
		{
			return FALSE;
		}

		return parent::actionEnabled( $action, $member );
	}

	/**
	 * Get the club of this record's container if there is one
	 *
	 * @return Club|null
	 */
	public function get_club() : Club|null
	{
		static $club = false;
		if ( $club !== null and !( $club instanceof Club ) )
		{
			$club = null;
			if ( $container = $this->container() and $container instanceof Categories )
			{
				$club = $container->_club;
			}
		}
		return $club;
	}

	/**
	 * Could edit an item?
	 * Useful to see if one can edit something even if the cut off has expired
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function couldEdit( ?Member $member=NULL ): bool
	{
		$couldEdit = parent::couldEdit( $member );
		if ( $couldEdit )
		{
			return TRUE;
		}
		else
		{
			$member = $member ?: Member::loggedIn();
			if ( ( ( static::database()->options['indefinite_own_edit'] AND $member->member_id === $this->member_id ) OR ( $member->member_id and static::database()->all_editable ) ) AND ! $this->locked() AND in_array( $this->hidden(), array(  0, 1 ) ) )
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/* ! Moderation */
	
	/**
	 * Can edit?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( ?Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		if ( ( ( static::database()->options['indefinite_own_edit'] AND $member->member_id === $this->member_id ) OR ( $member->member_id and static::database()->all_editable ) ) AND ! $this->locked() AND in_array( $this->hidden(), array(  0, 1 ) ) )
		{
			return TRUE;
		}

		/* Am I a Moderator with edit permissions ?*/
		if ( static::modPermission( 'edit', $member, $this->containerWrapper() ) )
		{
			return TRUE;
		}

		if ( parent::canEdit( $member ) )
		{
			/* Test against specific perms for this category */
			return $this->container()->can( 'edit', $member );
		}

		return FALSE;
	}
	
	/**
	 * Can edit title?
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEditTitle( ?Member $member=NULL ): bool
	{
		if ( $this->canEdit( $member ) )
		{
			try
			{
				$class = '\IPS\cms\Fields' .  static::database()->id;
				/* @var	$class Fields */
				$field = $class::load( static::database()->field_title );
				return $field->can( 'edit', $member );
			}
			catch( Exception $e )
			{
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * Can manage revisions?
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	bool
	 * @throws	BadMethodCallException
	 */
	public function canManageRevisions( Member $member = null ): bool
	{
		return static::database()->revisions and static::modPermission( 'content_revisions', $member );
	}

	/**
	 * During canCreate() check, verify member can access the module too
	 *
	 * @param	Member	$member		The member
	 * @note	The only reason this is abstracted at this time is because Pages creates dynamic 'modules' with its dynamic records class which do not exist
	 * @return	bool
	 */
	protected static function _canAccessModule( Member $member ): bool
	{
		/* Can we access the module */
		return $member->canAccessModule( Module::get( static::$application, 'database', 'front' ) );
	}

	/**
	 * Can a given member create this type of content?
	 *
	 * @param Member $member		The member
	 * @param Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param bool $showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( Member $member, Model $container=NULL, bool $showError=FALSE ): bool
	{
		$return = parent::canCreate( $member, $container, $showError );

		if( $return )
		{

			/* Check for title and content field permissions */
			try
			{
				$class = '\IPS\cms\Fields' .  static::database()->id;
				/* @var $class Fields */
				$title = $class::load( static::database()->field_title );
				$content = $class::load( static::database()->field_content );
				$return = $title->can( 'add', $member ) and $content->can( 'add', $member );
			}
			catch( Exception $e )
			{
				$return = FALSE;
			}
			
		}

		/* Return */
		if ( $showError and !$return )
		{
			$error = 'cms_no_title_content_permission';
			Output::i()->error( $error, '2C137/3', 403 );
		}

		return (bool) $return;
	}

	/**
	 * Already reviewed?
	 *
	 * @param	Member|NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function hasReviewed( ?Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* Check cache */
		if( isset( $this->_hasReviewed[ $member->member_id ] ) and $this->_hasReviewed[ $member->member_id ] !== NULL )
		{
			return $this->_hasReviewed[ $member->member_id ];
		}

		$reviewClass = static::$reviewClass;
		$idColumn    = static::$databaseColumnId;

		$where = array();
		/* @var Review $reviewClass */
		/* @var array $databaseColumnMap */
		$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn );
		$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'] . '=?', $member->member_id );
		$where[] = array( $reviewClass::$databasePrefix . 'database_id=?', static::$customDatabaseId );


		if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) )
		{
			/* Exclude content pending deletion, as it will not be shown inline  */
			if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
			{
				$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '<>?', -2 );
			}
			elseif( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
			{
				$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '<>?', -2 );
			}
		}

		$this->_hasReviewed[ $member->member_id ] = Db::i()->select(
			'COUNT(*)', $reviewClass::$databaseTable, $where
		)->first();

		return $this->_hasReviewed[ $member->member_id ];
	}
	
	/* ! Comments */
	/**
	 * Add the comment form elements
	 *
	 * @return	array
	 */
	public function commentFormElements(): array
	{
		return parent::commentFormElements();
	}

	/**
	 * Add a comment when the filtes changed. If they changed.
	 *
	 * @param   array   $values   Array of new form values
	 * @return  Records\Comment|bool
	 */
	public function addCommentWhenFiltersChanged( array $values ): bool|Records\Comment
	{
		if ( ! $this->canComment() )
		{
			return FALSE;
		}

		$currentValues = $this->fieldValues();
		$commentClass  = 'IPS\cms\Records\Comment' . static::$customDatabaseId;
		$categoryClass = 'IPS\cms\Categories' . static::$customDatabaseId;
		$fieldsClass   = 'IPS\cms\Fields' . static::$customDatabaseId;
		$newValues     = array();
		/* @var	$fieldsClass Fields */
		/* @var	$categoryClass Categories */
		$fieldsFields  = $fieldsClass::fields( $values, 'edit', $this->category_id ?  $categoryClass::load( $this->category_id ) : NULL, $fieldsClass::FIELD_DISPLAY_COMMENTFORM );

		foreach( $currentValues as $name => $data )
		{
			$id = mb_substr( $name, 6 );
			if ( $id == static::database()->field_title or $id == static::database()->field_content )
			{
				unset( $currentValues[ $name ] );
			}

			/* Not filterable? */
			if ( ! isset( $fieldsFields[ $id ] ) )
			{
				unset( $currentValues[ $name ] );
			}
		}

		foreach( $fieldsFields as $key => $field )
		{
			$newValues[ 'field_' . $key ] = $field::stringValue( $values[$field->name] ?? NULL );
		}

		$diff = array_diff_assoc( $currentValues, $newValues );

		if ( count( $diff ) )
		{
			$show    = array();
			$display = $fieldsClass::display( $newValues, NULL, NULL, 'id' );

			foreach( $diff as $name => $value )
			{
				$id = mb_substr( $name, 6 );

				if ( $display[ $id ] )
				{
					$show[ $name ] = sprintf( Member::loggedIn()->language()->get( 'cms_record_field_changed' ), Member::loggedIn()->language()->get( 'content_field_' . $id ), $display[ $id ] );
				}
			}

			if ( count( $show ) )
			{
				$post = \IPS\cms\Theme::i()->getTemplate( static::database()->template_display, 'cms', 'database' )->filtersAddComment( $show );
				Member::loggedIn()->language()->parseOutputForDisplay( $post );

				/* @var	$commentClass CommentTopicSync */
				if ( $this->useForumComments() )
				{
					$topic = $this->topic();
					$post  = Post::create( $topic, $post, false );
					
					$commentClass = 'IPS\cms\Records\CommentTopicSync' . static::$customDatabaseId;
					
					$comment = $commentClass::load( $post->pid );
					$this->resyncLastComment();

					return $comment;
				}
				else
				{
					return $commentClass::create( $this, $post, FALSE );
				}
			}
		}

		return TRUE;
	}

	/**
	 * Use a custom table helper when building content item tables
	 *
	 * @param TableHelper $table	Table object to modify
	 * @param	string				$currentClass	Current class
	 * @return    TableHelper
	 */
	public function reputationTableCallback( TableHelper $table, string $currentClass ): TableHelper
	{
		return $table;
	}
	
	/* !Notifications */
	
	/**
	 * @brief	Custom Field Notification Excludes
	 */
	protected array $_fieldNotificationExcludes = array();
	
	/**
	 * Set notification exclusions for custom field updates.
	 *
	 * @param	array	$exclude		Predetermined array of member IDs to exclude
	 * @return	void
	 */
	public function setFieldQuoteAndMentionExcludes( array $exclude = array() ): void
	{
		$className = 'IPS\cms\Fields' . static::$customDatabaseId;
		/* @var $className Fields */
		foreach( $className::data() AS $field )
		{
			if ( $field->type == 'Editor' )
			{
				$key = "field_{$field->id}";
				$_data  = static::_getQuoteAndMentionIdsFromContent( $this->$key );
				foreach( $_data AS $type => $memberIds )
				{
					$this->_fieldNotificationExcludes = array_merge( $this->_fieldNotificationExcludes, $memberIds );
				}
			}
		}
		
		$this->_fieldNotificationExcludes = array_unique( $this->_fieldNotificationExcludes );
	}
	
	/**
	 * Send notifications for custom field updates
	 *
	 * @return array
	 */
	public function sendFieldQuoteAndMentionNotifications(): array
	{
		return $this->sendQuoteAndMentionNotifications( $this->_fieldNotificationExcludes );
	}
	
	/**
	 * Send quote and mention notifications
	 *
	 * @param array $exclude		An array of member IDs *not* to send notifications to
	 * @return	array	Member IDs sent to
	 */
	protected function sendQuoteAndMentionNotifications( array $exclude=array() ): array
	{
		$data = array( 'quotes' => array(), 'mentions' => array(), 'embeds' => array() );
		
		$className = 'IPS\cms\Fields' .  static::$customDatabaseId;
		/* @var $className Fields */
		foreach ( $className::data() as $field )
		{
			if ( $field->type == 'Editor' )
			{
				$key = "field_{$field->id}";
				
				$_data = static::_getQuoteAndMentionIdsFromContent( $this->$key );
				foreach ( $_data as $type => $memberIds )
				{
					$_data[ $type ] = array_filter( $memberIds, function( $memberId ) use ( $field )
					{
						return $field->can( 'view', Member::load( $memberId ) );
					} );
				}
				
				$data = array_map( 'array_unique', array_merge_recursive( $data, $_data ) );
			}
		}
		
		return $this->_sendQuoteAndMentionNotifications( $data, $exclude );
	}

	/**
	 * Review Rating submitted by member
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	int|null
	 * @throws	BadMethodCallException
	 */
	public function memberReviewRating( Member|null $member = NULL ): int|NULL
	{
		$member = $member ?: Member::loggedIn();

		if( $this->memberReviewRatings === null )
		{
			/* @var Review $reviewClass */
			$reviewClass = static::$reviewClass;
			$idColumn = static::$databaseColumnId;

			/* @var $databaseColumnMap array */
			$where = array();
			$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=? AND review_database_id=?', $this->$idColumn, static::$customDatabaseId );

			if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '=?', 1 );
				}
				elseif ( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '=?', 0 );
				}
			}

			$this->memberReviewRatings = iterator_to_array( Db::i()->select( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] . ',' . $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'], $reviewClass::$databaseTable, $where )
				->setKeyField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'] )
				->setValueField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] ) );

			$this->_averageReviewRating = count( $this->memberReviewRatings ) ? array_sum( $this->memberReviewRatings ) / count( $this->memberReviewRatings ) : 0;
		}

		return $this->memberReviewRatings[ $member->member_id ] ?? null;
	}

	/**
	 * If, when making a post, we should merge with an existing comment, this method returns the comment to merge with
	 *
	 * @return	Comment|NULL
	 */
	public function mergeConcurrentComment(): ?Comment
	{
		$lastComment = parent::mergeConcurrentComment();

		/* If we sync to the forums, make sure that the "last comment" is not actually the first post */
		if( $this->record_topicid AND $lastComment !== NULL )
		{
			$firstComment = Topic::load( $this->record_topicid )->comments( 1, 0, 'date', 'asc' );

			if( $firstComment->pid == $lastComment->pid )
			{
				return NULL;
			}
		}

		return $lastComment;
	}

	/**
	 * Deletion log Permissions
	 * Usually, this is the same as searchIndexPermissions. However, some applications may restrict searching but
	 * still want to allow delayed deletion log viewing and searching
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function deleteLogPermissions(): string
	{
		if( ! $this->container()->can_view_others )
		{
			$return = $this->container()->searchIndexPermissions();
			/* If the search index permissions are empty, just return now because no one can see content in this forum */
			if( !$return )
			{
				return $return;
			}

			$return = $this->container()->permissionsThatCanAccessAllRecords();

			if ( $this->member_id )
			{
				$return[] = "m{$this->member_id}";
			}

			return implode( ',', $return );
		}
		
		try
		{
            /* We can't use $extension->searchIndexPermissions() here because it calls for deleteLogPermissions() which makes this get caught in a loop
               looking at v4 code, it used to call parent::searchIndexPermissions() which no longer exists as it is not a class method, but the code did
               this below */
            return $this->container()->searchIndexPermissions();
		}
		catch ( BadMethodCallException $e )
        {
            return '*';
        }
	}

	/**
	 * Online List Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function onlineListPermissions(): string
	{
		/* If search is disabled for this database, we want to use the page/category permissions
		instead of falling back to searchIndexPermissions */
		if( ! static::database()->search )
		{
			return $this->container()->readPermissionMergeWithPage();
		}

		return parent::onlineListPermissions();
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return    array
	 * @apiresponse	int						id				ID number
	 * @apiresponse	string					title			Title
	 * @apiresponse	\IPS\cms\Categories		category		Category
	 * @apiresponse	object					fields			Field values
	 * @apiresponse	\IPS\Member				author			The member that created the event
	 * @apiresponse	datetime				date			When the record was created
	 * @apiresponse	string					description		Event description
	 * @apiresponse	int						comments		Number of comments
	 * @apiresponse	int						reviews			Number of reviews
	 * @apiresponse	int						views			Number of posts
	 * @apiresponse	string					prefix			The prefix tag, if there is one
	 * @apiresponse	[string]				tags			The tags
	 * @apiresponse	bool					locked			Event is locked
	 * @apiresponse	bool					hidden			Event is hidden
	 * @apiresponse	bool					pinned			Event is pinned
	 * @apiresponse	bool					featured		Event is featured
	 * @apiresponse	string|NULL				url				URL, or NULL if the database has not been embedded onto a page
	 * @apiresponse	float					rating			Average Rating
	 * @apiresponse	string					image			Record Image
	 * @apiresponse	\IPS\forums\Topic		topic			The topic
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		/* You can have a database that is not embedded onto a page */
		try
		{
			$url = (string) $this->url();
		}
		catch( LogicException $e )
		{
			$url = NULL;
		}

		$return = array(
			'id'			=> $this->primary_id_field,
			'title'			=> $this->_title,
			'category'		=> $this->container() ? $this->container()->apiOutput() : null,
			'fields'		=> $this->fieldValues(),
			'author'		=> $this->author()->apiOutput( $authorizedMember ),
			'date'			=> DateTime::ts( $this->record_saved )->rfc3339(),
			'description'	=> $this->content(),
			'comments'		=> $this->record_comments,
			'reviews'		=> $this->record_reviews,
			'views'			=> $this->record_views,
			'prefix'		=> $this->prefix(),
			'tags'			=> $this->tags(),
			'locked'		=> $this->locked(),
			'hidden'		=> (bool) $this->hidden(),
			'pinned'		=> (bool) $this->mapped('pinned'),
			'featured'		=> (bool) $this->mapped('featured'),
			'url'			=> $url,
			'rating'		=> $this->averageRating(),
			'image'			=> $this->record_image ? (string) File::get( 'cms_Records', $this->record_image )->url : null,
			'topic'			=> $this->topicid ? $this->topic()->apiOutput( $authorizedMember ) : NULL,
		);

		if ( IPS::classUsesTrait( $this, 'IPS\Content\Reactable' ) )
		{
			if ( $reactions = $this->reactions() )
			{
				$enabledReactions = Reaction::enabledReactions();
				$finalReactions = [];
				foreach( $reactions as $memberId => $array )
				{
					foreach( $array as $reaction )
					{
						$finalReactions[ $memberId ][] = [
							'title' => $enabledReactions[ $reaction ]->_title,
							'id'    => $reaction,
							'value' => $enabledReactions[ $reaction ]->value,
							'icon'  => (string) $enabledReactions[ $reaction ]->_icon->url
						];
					}
				}

				$return['reactions'] = $finalReactions;
			}
			else
			{
				$return['reactions'] = [];
			}
		}

		return $return;
	}

	/**
	 * Get items with permission check
	 *
	 * @param array $where Where clause
	 * @param string|null $order MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit Limit clause
	 * @param string $permissionKey A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param int|bool|null $includeHiddenItems Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param int $queryFlags Select bitwise flags
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param bool $joinContainer If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly If true will return the count
	 * @param array|null $joins Additional arbitrary joins for the query
	 * @param bool|Model $skipPermission If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param bool $joinTags If true, will join the tags table
	 * @param bool $joinAuthor If true, will join the members table for the author
	 * @param bool $joinLastCommenter If true, will join the members table for the last commenter
	 * @param bool $showMovedLinks If true, moved item links are included in the results
	 * @param array|null $location Array of item lat and long
	 * @return    ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( array $where=array(), string $order=null, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member $member=null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins=null, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, array|null $location=null ): ActiveRecordIterator|int
	{
		$where = static::getItemsWithPermissionWhere( $where, $permissionKey, $member, $joinContainer, $skipPermission );
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
	}

	/**
	 * WHERE clause for getItemsWithPermission
	 *
	 * @param array $where Current WHERE clause
	 * @param string $permissionKey A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param bool $joinContainer If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool|mixed $skipPermission If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip container-based permission. You must still specify this in the $where clause
	 * @return    array
	 */
	public static function getItemsWithPermissionWhere( array $where, string $permissionKey, ?Member $member, bool &$joinContainer, mixed $skipPermission=FALSE ): array
	{
		/* Don't show records from categories in which records only show to the poster */
		if ( $skipPermission !== TRUE and in_array( $permissionKey, array( 'view', 'read' ) ) )
		{
			$member = $member ?: Member::loggedIn();
			if ( !$member->modPermission( 'can_content_view_others_records' ) )
			{
				if ( $skipPermission instanceof Categories)
				{
					if ( !$skipPermission->can_view_others )
					{
						$where['item'][] = array( 'cms_custom_database_' . static::database()->id . '.member_id=?', $member->member_id );
					}
				}
				else
				{
					$joinContainer = TRUE;

					$where[] = array( '( category_can_view_others=1 OR cms_custom_database_' . static::database()->id . '.member_id=? )', $member->member_id );
				}
			}
		}
		
		/* Return */
		return $where;
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		$databaseId = static::database()->_id;
		return "record_id_{$databaseId}";
	}
	
	/**
	 * Supported Meta Data Types
	 *
	 * @return	array
	 */
	public static function supportedMetaDataTypes(): array
	{
		return array( 'core_FeaturedComments', 'core_ContentMessages' );
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'cms', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'cms' )->embedRecord( $this, $this->url()->setQueryString( $params ) );
	}

	/**
	 * Give a content item the opportunity to filter similar content
	 * 
	 * @note Intentionally blank but can be overridden by child classes
	 * @return array|NULL
	 */
	public function similarContentFilter(): ?array
	{
		if( $this->record_topicid )
		{
			return array(
				array( '!(tag_meta_app=? and tag_meta_area=? and tag_meta_id=?)', 'forums', 'forums', $this->record_topicid )
			);
		}

		return NULL;
	}

	/**
	 * Get a count of the database table
	 *
	 * @param   bool    $approximate     Accept an approximate result if the table is large (approximate results are faster on large tables)
	 * @return  int
	 */
	public static function databaseTableCount( bool $approximate=FALSE ): int
	{
		if ( static::$databaseTable == NULL )
		{
			return 0;
		}
		else
		{
			return parent::databaseTableCount( $approximate );
		}
	}

	/**
	 * Get the last modification date for the sitemap
	 *
	 * @return DateTime|null		timestamp of the last modification time for the sitemap
	 */
	public function lastModificationDate(): DateTime|NULL
	{
		$lastMod = parent::lastModificationDate();

		if ( !$lastMod AND $this->record_updated )
		{
			$lastMod = DateTime::ts( $this->record_updated );
		}

		return $lastMod;
	}

	/**
	 * Returns the earliest publish date for the new content item, we can have past items for records.
	 *
	 * @return DateTime|null
	 */
	protected static function getMinimumPublishDate(): ?DateTime
	{
		return NULL;
	}
	
	/**
	 * Can the publish date be changed while editing the item?
	 * Formerly a properly, however classes cannot overload / redeclare properties from traits.
	 *
	 * @return bool
	 */
	public static function allowPublishDateWhileEditing(): bool
	{
		return TRUE;
	}

	/**
	 * Get the topic title
	 *
	 * @return string
	 */
	function getTopicTitle(): string
	{
		$title = '';

		if( $prefix = static::database()->forum_prefix )
		{
			$title .= $prefix . ' ';
		}

		$column = 'field_' . static::database()->field_title;
		$title .= $this->$column;

		if( $suffix = static::database()->forum_suffix )
		{
			$title .= ' ' . $suffix;
		}

		return $title;
	}

	/**
	 * Get the topic content
	 *
	 * @return mixed
	 */
	function getTopicContent(): mixed
	{
		return Theme::i()->getTemplate( 'submit', 'cms', 'front' )->topic( $this );
	}

	/**
	 * Return the Forum ID
	 *
	 * @return	int
	 */
	public function getForumId() : int
	{
		return $this->_forum_forum;
	}

	/**
	 * Container has assignable enabled
	 *
	 * @return    bool
	 */
	public function containerAllowsAssignable(): bool
	{
		return (bool) static::database()->options['assignments'];
	}

	/**
	 * Do we have record image enabled here?
	 * Just made this easier to figure out from inside templates
	 *
	 * @return bool
	 */
	public function showRecordImage() : bool
	{
		/* @var Fields $fieldClass */
		$fieldClass = 'IPS\cms\Fields' . static::$customDatabaseId;
		return $fieldClass::fixedFieldFormShow( 'record_image' );
	}

	/**
	 * Returns the CoverPhoto File Instance or NULL if there's none
	 *
	 * @return null|File
	 */
	public function coverPhotoFile(): ?File
	{
		if( !$this->showRecordImage() )
		{
			return null;
		}

		return parent::coverPhotoFile();
	}
}