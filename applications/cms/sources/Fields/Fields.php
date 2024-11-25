<?php
/**
 * @brief		Database Field Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		2 Apr 2014
 */

/**
 * 
 * @todo Shared media field type
 *
 */
namespace IPS\cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use DomainException;
use ErrorException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\CustomField;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Dispatcher;
use IPS\Extensions\CustomFieldAbstract;
use IPS\File;
use IPS\File\FileSystem;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Output;
use IPS\Patterns\ActiveRecord;
use IPS\Request;
use IPS\Task;
use IPS\Text\Encrypt;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use UnderflowException;
use function call_user_func;
use function call_user_func_array;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_integer;
use function is_numeric;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Field Node
 */
class Fields extends CustomField implements Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons = array();
		
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_database_fields';
	
	/**
	 * @brief	[Fields] Custom Database Id
	 */
	public static ?int $customDatabaseId = NULL;
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'field_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('field_id', 'field_key');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static string $langKey = 'content_field';
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static bool $gotAll = FALSE;
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
			'view' 				=> 'view',
			'edit'				=> 2,
			'add'               => 3
	);
	
	/**
	 * @brief	[Node] App for permission index
	*/
	public static ?string $permApp = 'cms';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'fields';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_content_field_';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'cms',
		'module'	=> 'databases',
		'prefix'	=> 'cms_fields_',
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'content_field_';

	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = TRUE;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = '';
	
	/**
	 * @brief	[CustomField] Database table
	 */
	protected static string $contentDatabaseTable = '';
	
	/**
	 * @brief	[CustomField] Upload storage extension
	 */
	protected static string $uploadStorageExtension = 'cms_Records';
	
	/**
	 * @brief	[CustomField] Set to TRUE if uploads fields are capable of holding the submitted content for moderation
	 */
	public static bool $uploadsCanBeModerated = TRUE;

	/**
	 * @brief	[CustomField] Cache retrieved fields
	 */
	protected static array $cache = array();

	/**
	 * @brief   Custom Media fields
	 */
	protected static array $mediaFields = array( 'Youtube', 'Spotify', 'Soundcloud' );

	/**
	 * @brief	Skip the title and content fields
	 */
	const FIELD_SKIP_TITLE_CONTENT = 1;
	
	/**
	 * @brief	Show only fields allowed on the comment form
	 */
	const FIELD_DISPLAY_COMMENTFORM = 2;
	
	/**
	 * @brief	Show only fields allowed on the listing view
	 */
	const FIELD_DISPLAY_LISTING = 4;
	
	/**
	 * @brief	Show only fields allowed on the record view
	 */
	const FIELD_DISPLAY_RECORD  = 8;
	
	/**
	 * @brief	Show only fields allowed to be filterable
	 */
	const FIELD_DISPLAY_FILTERS = 16;
	
	/**
	 * @brief	Fields that cannot be title fields
	 */
	public static array $cannotBeTitleFields = array( 'Member', 'Editor', 'CheckboxSet', 'YesNo', 'Radio', 'Item' );
	
	/**
	 * @brief	Fields that cannot be content fields
	 */
	public static array $cannotBeContentFields = array();
	
	/**
	 * @brief	Fields that can be filtered on the front end. These appear in \Table advanced search and also in the DatabaseFilters widget.
	 */
	protected static array $filterableFields = array( 'CheckboxSet', 'Radio', 'Select', 'YesNo', 'Date', 'Member' );

	/**
	 * @brief	Fields that use toggles
	 */
	public static array $canUseTogglesFields = array( 'Checkbox', 'CheckboxSet', 'Radio', 'Select', 'YesNo' );

	/**
	 * Load Record
	 *
	 * @param int|string|null $id ID
	 * @param string|null $idField The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param mixed $extraWhereClause Additional where clause(s) (see \IPS\Db::build for details)
	 * @return ActiveRecord|Fields
	 * @see        Db::build
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
	{
		if ( $idField === 'field_key' )
		{
			$extraWhereClause = array( 'field_database_id=?', static::$customDatabaseId );
		}
		
		return parent::load( $id, $idField, $extraWhereClause );
	}
	
	/**
	 * Fetch All Root Nodes
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param Member|null $member				The member to check permissions for or NULL for the currently logged in member
	 * @param mixed $where				Additional WHERE clause
	 * @param array|null $limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		$permissionCheck = ( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation === 'admin' ) ? NULL : $permissionCheck;

		if ( ! isset( static::$cache[ static::$customDatabaseId ][ $permissionCheck ] ) )
		{
			$langToLoad = array();
			$where[]    = array( 'field_database_id=?', static::$customDatabaseId );

			static::$cache[ static::$customDatabaseId ][ $permissionCheck ] = parent::roots( $permissionCheck, $member, $where, $limit );

			foreach( static::$cache[ static::$customDatabaseId ][ $permissionCheck ] as $id => $obj )
			{
				if ( ! array_key_exists( $obj->type, static::$additionalFieldTypes ) AND ( ! class_exists( '\IPS\Helpers\Form\\' . IPS::mb_ucfirst( $obj->type ) ) AND ! class_exists( '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $obj->type ) ) ) )
				{
					unset( static::$cache[ static::$customDatabaseId ][ $permissionCheck ][ $id ] );
					continue;
				}

				$langToLoad[] = static::$langKey . '_' . $obj->id;
				$langToLoad[] = static::$langKey . '_' . $obj->id . '_desc';
				$langToLoad[] = static::$langKey . '_' . $obj->id . '_warning';
			}

			if ( count( $langToLoad ) AND Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation !== 'setup' )
			{
				Member::loggedIn()->language()->get( $langToLoad );
			}
		}

		return static::$cache[ static::$customDatabaseId ][ $permissionCheck ];
	}
	
	/**
	 * Just return all field IDs in a database without permission checking
	 *
	 * @return array
	 */
	public static function databaseFieldIds(): array
	{
		$key = 'cms_fieldids_' . static::$customDatabaseId;
		
		if ( ! isset( Store::i()->$key ) )
		{
			Store::i()->$key = iterator_to_array( Db::i()->select( 'field_id', 'cms_database_fields', array( array( 'field_database_id=?', static::$customDatabaseId ) ) )->setKeyField('field_id') );
		}
		
		return Store::i()->$key;
	}
	
	/**
	 * Get Field Data
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Model|NULL		$container			Parent container
	 * @param   INT                         $flags              Bit flags
	 *
	 * @return	array
	 */
	public static function data( string $permissionCheck=NULL, Model $container=NULL, int $flags=0 ): array
	{
		$fields   = array();
		$database = Databases::load( static::$customDatabaseId );
		
		foreach( static::roots( $permissionCheck ) as $row )
		{
			if ( $container !== NULL AND $database->use_categories )
			{
				if ( $container->fields !== '*' AND $container->fields !== NULL )
				{
					if ( ! in_array( $row->id, $container->fields ) AND $row->id != $database->field_title AND $row->id != $database->field_content )
					{
						continue;
					}
				}
			}

			if ( $flags & self::FIELD_SKIP_TITLE_CONTENT AND ( $row->id == $database->field_title OR $row->id == $database->field_content ) )
			{
				continue;
			}
			
			if ( $flags & self::FIELD_DISPLAY_FILTERS )
			{
				if ( ! $row->filter )
				{
					continue;
				}
				
				if ( $row->type === 'Date' )
				{
					$row->type = 'DateRange';
				}
			}
			
			$fields[ $row->id ] = $row;
		}
		
		return $fields;
	}
	
	/**
	 * Get Fields
	 *
	 * @param array $values				Current values
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Model|NULL		$container			Parent container
	 * @param int $flags				Bit flags
	 * @param Records|null       $record             The record itself
	 * @param bool $includeDefaultValue Include the default value for the field
	 * @return	array
	 */
	public static function fields( array $values, ?string $permissionCheck='view', Model $container=NULL, int $flags=0, Records $record = NULL, bool $includeDefaultValue = TRUE ): array
	{
		$fields        = array();
		$database      = Databases::load( static::$customDatabaseId );

		foreach( static::roots( $permissionCheck ) as $row )
		{
			$row->required = (bool) $row->required;
			
			if ( $container !== NULL AND $database->use_categories )
			{
				if ( $container->fields !== '*' AND $container->fields !== NULL )
				{
					if ( ! in_array( $row->id, $container->fields ) AND $row->id != $database->field_title AND $row->id != $database->field_content )
					{
						continue;
					}
				}
			}

			if ( $flags & self::FIELD_SKIP_TITLE_CONTENT AND ( $row->id == $database->field_title OR $row->id == $database->field_content ) )
			{
				continue;
			}
			
			if ( $flags & self::FIELD_DISPLAY_COMMENTFORM )
			{
				if ( ! $row->display_commentform )
				{
					continue;
				}
			}

			if ( $flags & self::FIELD_DISPLAY_LISTING AND ( ! $row->display_listing ) )
			{
				continue;
			}
			
			if ( $flags & self::FIELD_DISPLAY_RECORD AND ( ! $row->display_display ) )
			{
				continue;
			}
			
			if ( $flags & self::FIELD_DISPLAY_FILTERS  )
			{
				if ( ! $row->filter )
				{
					continue;
				}
				else
				{
					if ( $row->type === 'Radio' )
					{
						$row->type = 'Select';
					}
					
					if ( $row->type === 'Date' )
					{
						$row->type = 'DateRange';
					}

					$row->required = FALSE;
					
					if ( $row->type === 'Select' )
					{
						$row->is_multiple = true;
					}
				}
			}
			
			$customValidationCode = NULL;
			
			if ( $row->unique )
			{
				$customValidationCode = function( $val ) use ( $database, $row, $record )
				{
					$class = 'IPS\cms\Fields' . static::$customDatabaseId;
					/* @var $class Fields */
					$class::validateUnique( $val, $row, $record );
				};
			}

			if( $row->id == $database->field_title AND $row->type === 'Select' )
			{
				$customValidationCode = function( $val )
				{
					if( $val === NULL )
					{
						throw new DomainException( 'form_required' );
					}
				};
			}

			if ( isset( $values['field_' . $row->id ] ) )
			{
				$fields[ $row->id ] = $row->buildHelper( $values['field_' . $row->id ], $customValidationCode, $record, $flags );
			}
			else
			{
				$fields[ $row->id ] = $row->buildHelper( $includeDefaultValue ? $row->default_value : NULL , $customValidationCode, $record, $flags );
			}
		}

		return $fields;
	}
	
	/**
	 * Get Values
	 *
	 * @param array $values				Current values
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Model|NULL		$container			Parent container
	 * @return	array
	 */
	public static function values( array $values, ?string $permissionCheck='view', Model $container=NULL ): array
	{
		$fields   = array();
		$database = Databases::load( static::$customDatabaseId );
		
		foreach( static::roots( $permissionCheck ) as $row )
		{
			if ( $container !== NULL AND $database->use_categories )
			{
				if ( $container->fields !== '*' AND $container->fields !== NULL )
				{
					if ( ! in_array( $row->id, $container->fields ) AND $row->id != $database->field_title AND $row->id != $database->field_content )
					{
						continue;
					}
				}
			}
			
			if ( isset( $values[ 'field_' . $row->id ] ) )
			{
				$fields[ 'field_' . $row->id ] = $values[ 'field_' . $row->id ];
			}
		}
		
		return $fields;
	}
	
	/**
	 * Display Values
	 *
	 * @param array $values				Current values
	 * @param string|null $display			Type of display (listing/display/raw/processed).
	 * @param	Model|NULL		$container			Parent container
	 * @param string $index              Field to index return array on
	 * @param object|null $record				Record showing this field
	 * @note    Raw means the value saved from the input field, processed has the form display value method called. Listing and display take the options set by the field (badges, custom, etc)
	 * @return	array
	 */
	public static function display( array $values, ?string $display='listing', Model $container=NULL, string $index='key', object $record=NULL ): array
	{
		$fields   = array();
		$database = Databases::load( static::$customDatabaseId );

		foreach( static::roots() as $row )
		{
			if ( $display !== 'record' AND ( $row->id == $database->field_title OR $row->id == $database->field_content ) )
			{
				continue;
			}

			if ( $container !== NULL AND $database->use_categories )
			{
				if ( $container->fields !== '*' AND $container->fields !== NULL )
				{
					if ( ! in_array( $row->id, $container->fields ) AND $row->id != $database->field_title AND $row->id != $database->field_content )
					{
						continue;
					}
				}
			}

			/* If we don't need these fields we don't need to do any further formatting */
			if ( ( $display === 'listing' and !$row->display_listing ) or ( ( $display === 'display' or $display === 'display_top' or $display === 'display_bottom' ) and !$row->display_display ) )
			{
				continue;
			}

			$formValue = ( isset( $values[ 'field_' . $row->id ] ) AND $values[ 'field_' . $row->id ] !== '' AND $values[ 'field_' . $row->id ] !== NULL ) ? $values[ 'field_' . $row->id ] : $row->default_value;
			 			
			$value     = $row->displayValue( $formValue );

			if ( $display === 'listing' )
			{
				$value = $row->truncate( $value, TRUE );

				if ( $value !== '' AND $value !== NULL )
				{
					$value = $row->formatForDisplay( $value, $formValue, 'listing', $record );
				}
			}
			else if ( $display === 'display' or $display === 'display_top' or $display === 'display_bottom' )
			{
				$displayData = $row->display_json;

				if ( $display === 'display_bottom' )
				{
					if ( isset( $displayData['display']['where'] ) )
					{
						if ( $displayData['display']['where'] !== 'bottom' )
						{
							continue;
						}
					}
					else
					{
						continue;
					}
				}
				else if ( $display === 'display_top' )
				{
					if ( isset( $displayData['display']['where'] ) )
					{
						if ( $displayData['display']['where'] !== 'top' )
						{
							continue;
						}
					}
				}

				if ( $value !== '' AND $value !== NULL )
				{
					$value = $row->formatForDisplay( $value, $formValue, 'display', $record );
				}
			}
			else if ( $display === 'raw' )
			{
				$value = $formValue;
			}

			$fields[ ( $index === 'id' ? $row->id : $row->key ) ] = $value;
		}

		return $fields;
	}

	/**
	 * Display the field
	 *
	 * @param   mixed        $value         Processed value
	 * @param   mixed        $formValue     Raw form value
	 * @param string $type          Type of display (listing/display/raw/processed).
	 * @param Records|null $record	Record showing this field
	 * @note    Raw means the value saved from the input field, processed has the form display value method called. Listing and display take the options set by the field (badges, custom, etc)
	 *
	 * @return mixed|string
	 * @throws ErrorException
	 */
	public function formatForDisplay( mixed $value, mixed $formValue, string $type='listing', Records $record=NULL ): mixed
	{
		if ( $type === 'raw' )
		{
			if ( $this->type === 'Upload' )
			{
				if ( $this->is_multiple )
				{
					$images = array();
					foreach( explode( ',', $value ) as $val )
					{
						$images[] = File::get( static::$uploadStorageExtension, $val )->url;
					}
					
					return $images;
				}
				
				return (string) File::get( static::$uploadStorageExtension, $value )->url;
			}

			if ( $this->type === 'Item' )
			{
				if ( ! is_array( $formValue ) and mb_strstr( $formValue, ',' ) )
				{
					$value = explode( ',', $formValue );
				}
				else
				{
					$value = array( $formValue );
				}

				if ( count( $value ) and isset( $this->extra['database'] ) and $this->extra['database'] )
				{
					$results = array();
					$class   = '\IPS\cms\Records' . $this->extra['database'];
					/* @var $class Records */
					/* @var $databaseColumnMap array */
					$field   = $class::$databasePrefix . $class::$databaseColumnMap['title'];
					$where   = array( Db::i()->in( $class::$databaseColumnId, $value ) );

					foreach ( $class::getItemsWithPermission( array( $where ), $field, NULL ) as $item )
					{
						$results[ $item->_id ] = $item;
					}

					return $results;
				}
			}

			return $formValue;
		}
		else if ( $type === 'processed' )
		{
			return $value;
		}
		else if ( $type === 'thumbs' and $this->type === 'Upload' )
		{
			if ( isset( $this->extra['thumbsize'] ) )
			{
				if ( $this->is_multiple )
				{
					$thumbs = iterator_to_array( Db::i()->select( '*', 'cms_database_fields_thumbnails', array( array( 'thumb_field_id=? AND thumb_record_id=?', $this->id, $record->_id ) ) )->setKeyField('thumb_original_location')->setValueField('thumb_location') );
					$images = array();
				
					foreach( $thumbs as $orig => $thumb )
					{
						try
						{
							$images[] = File::get( static::$uploadStorageExtension, $thumb )->url;
						}
						catch( Exception $e ) { }
					}
					
					return $images;
				}
				else
				{
					try
					{
						return (string) File::get( static::$uploadStorageExtension, Db::i()->select( 'thumb_location', 'cms_database_fields_thumbnails', array( array( 'thumb_field_id=? AND thumb_record_id=?', $this->id, $record->_id ) ) )->first() )->url;
					}
					catch( Exception $e ) { }
				}
			}
			
			return $this->formatForDisplay( $value, $formValue, 'raw', $record );
		}

		$options = $this->display_json;

		if ( isset( $options[ $type ]['method'] ) AND $options[ $type ]['method'] !== 'simple' )
		{
			if ( in_array( $this->type, static::$mediaFields ) )
			{
				$template = mb_strtolower( $this->type );

				if ( $options[ $type ]['method'] === 'player' )
				{
					$class = '\IPS\cms\Fields\\' . $this->type;

					if ( method_exists( $class, 'displayValue' ) )
					{
						$value = $class::displayValue( $formValue, $this );
					}
					else
					{
						try
						{
							$value = Theme::i()->getTemplate( 'records', 'cms', 'global' )->$template( $formValue, $this->extra );
						}
						catch( Exception $e )
						{
							$value = $formValue;
						}
					}
				}
				else
				{
					$value = $formValue;
				}
			}
			else
			{
				if ( $options[ $type ]['method'] == 'custom' )
				{
					if ( $this->type === 'Upload' )
					{
						if ( mb_strstr( $value, ',' ) )
						{
							$files = explode( ',', $value );
						}
						else
						{
							$files = array( $value );
						}

						$objects = array();
						foreach ( $files as $file )
						{
							$object = File::get( static::$uploadStorageExtension, (string) $file );
							
							if ( $object->isImage() and $type === 'display' )
							{
								Output::i()->metaTags['og:image:url'][] = (string) $object->url;
							}
							
							$objects[] = $object;
						}

						if ( ! $this->is_multiple )
						{
							$value = array_shift( $objects );
						}
						else
						{
							$value = $objects;
						}
					}

					$value = trim( $this->parseCustomHtml( $type, $options[ $type ]['html'], $formValue, $value, $record ) );
				}
				else if ( $options[ $type ]['method'] !== 'none' )
				{
					$class = 'ipsBadge--style' . ( is_numeric( $options[ $type ]['method'] ) ? $options[ $type ]['method'] : '1' );

					if ( isset( $options[ $type ]['right'] ) AND $options[ $type ]['right'] )
					{
						$class .= ' ' . 'i-float_end';
					}

					if ( $this->type === 'Address' and $formValue and isset( $options[ $type ]['map'] ) AND $options[ $type ]['map'] AND GeoLocation::enabled() )
					{
						$value .= GeoLocation::buildFromJson( $formValue )->map()->render( $options[ $type ]['mapDims'][0], $options[ $type ]['mapDims'][1] );
					}

					if ( $this->type === 'Upload' )
					{
						if ( mb_strstr( $value, ',' ) )
						{
							$files = explode( ',', $value );
						}
						else
						{
							$files = array( $value );
						}

						$parsed = array();
						foreach( $files as $idx => $file )
						{
							$file = File::get( static::$uploadStorageExtension, (string) $file );

							if ( $file->isImage() and $type === 'display' )
							{
								Output::i()->metaTags['og:image:url'][] = (string) $file->url;
							}

							$fileKey		= Encrypt::fromPlaintext( (string) $file )->tag();
							$downloadUrl	= Url::internal( 'applications/core/interface/file/cfield.php', 'none' )->setqueryString( array(
								'storage'	=> $file->storageExtension,
								'path'		=> $file->originalFilename,
								'fileKey'   => $fileKey
							) );
							
							$parsed[] = Theme::i()->getTemplate( 'global', 'cms', 'front' )->uploadDisplay( File::get( static::$uploadStorageExtension, $file ), $record, $downloadUrl, $fileKey );
						}

						$value = implode( " ", $parsed );
					}
					else if ( $this->type === 'Member' )
					{
						if ( mb_strstr( $value, "\n" ) )
						{
							$members = explode( "\n", $formValue );
						}
						else
						{
							$members = array( $formValue );
						}

						$parsed = array();

						foreach( $members as $id )
						{
							try
							{
								$parsed[] = Member::load( $id )->link();
							}
							catch( Exception $e ) { }
						}
						
						$value = implode( ", ", $parsed );
						
					}

					$value = Theme::i()->getTemplate( 'records', 'cms', 'global' )->fieldBadge( $this->_title, $value, $class, $options[ $type ]['bgcolor'] ?? null, $options[ $type ]['color'] ?? null );
				}
			}
		}
		else
		{
			if ( $this->type === 'Address' and $formValue and isset( $options[ $type ]['map'] ) AND $options[ $type ]['map'] AND GeoLocation::enabled() )
			{
				$value .= GeoLocation::buildFromJson( $formValue )->map()->render( $options[ $type ]['mapDims'][0], $options[ $type ]['mapDims'][1] );
			}
			else if ( $this->type === 'Upload' )
			{
				if ( mb_stristr( $value, ',' ) )
				{
					$files = explode( ',', $value );
				}
				else
				{
					$files = array( $value );
				}
				
				if ( count( $files ) )
				{
					$parsed = array();
					foreach( $files AS $file )
					{
						$file = File::get( static::$uploadStorageExtension, (string) $file );

						if( $options[ $type ]['method'] == 'simple' )
						{
							$fileKey		= Encrypt::fromPlaintext( (string) $file )->tag();
							$downloadUrl	= Url::internal( 'applications/core/interface/file/cfield.php', 'none' )->setqueryString( array(
								'storage'	=> $file->storageExtension,
								'path'		=> $file->originalFilename,
								'fileKey'   => $fileKey
							) );

							$parsed[] = Theme::i()->getTemplate( 'global', 'cms', 'front' )->uploadDisplay( File::get( static::$uploadStorageExtension, $file ), $record, $downloadUrl, $fileKey );
						}
						else
						{
							$parsed[] = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $file->fullyQualifiedUrl( $file->url ) );
						}
					}

					$value = implode( '', $parsed );
				}

				$value = implode( " ", $parsed );
			}
			
			$value = Theme::i()->getTemplate( 'records', 'cms', 'global' )->fieldDefault( $this->_title, $value );
		}

		return $value;
	}

	/**
	 * Parse custom HTML
	 *
	 * @param string $type           Type of display
	 * @param string $template       The HTML to parse
	 * @param string|null $formValue      The form value (key of select box, for example)
	 * @param mixed $value          The display value
	 * @param Records|null $record	Record showing this field
	 *
	 * @return string
	 */
	public function parseCustomHtml( string $type, string $template, ?string $formValue, mixed $value, Records $record=null ): string
	{
		$functionName = $this->fieldTemplateName( $type );
		$options      = $this->display_json;

		if ( $formValue and $this->type === 'Address' )
		{
			$functionName .= '_' . mt_rand();
			
			$template = str_replace( '{map}'    , GeoLocation::buildFromJson( $formValue )->map()->render( $options[ $type ]['mapDims'][0], $options[ $type ]['mapDims'][1] ), $template );
			$template = str_replace( '{address}', GeoLocation::parseForOutput( $formValue ), $template );
			$template = Theme::compileTemplate( $template, $functionName, '$value, $formValue, $label, $record' );
		}
		else
		{
			if ( $this->type === 'Upload' )
			{
				if ( is_array( $value ) )
				{
					foreach( $value as $idx => $val )
					{
						if ( $val instanceof File )
						{
							$fileKey		= Encrypt::fromPlaintext( (string) $val )->tag();
							$downloadUrl	= Url::internal( 'applications/core/interface/file/cfield.php', 'none' )->setqueryString( array(
								'storage'	=> $val->storageExtension,
								'path'		=> $val->originalFilename,
								'fileKey'   => $fileKey
							) );

							$value[ $idx ] = Theme::i()->getTemplate( 'global', 'cms', 'front' )->uploadDisplay( File::get( static::$uploadStorageExtension, $val ), $record, $downloadUrl, $fileKey );
						}
					}
				}
				else if ( $value instanceof File )
				{
					$fileKey		= Encrypt::fromPlaintext( (string) $value )->tag();
					$downloadUrl	= Url::internal( 'applications/core/interface/file/cfield.php', 'none' )->setqueryString( array(
						'storage'	=> $value->storageExtension,
						'path'		=> $value->originalFilename,
						'fileKey'   => $fileKey
					) );

					$value = Theme::i()->getTemplate( 'global', 'cms', 'front' )->uploadDisplay( File::get( static::$uploadStorageExtension, $value ), $record, $downloadUrl, $fileKey );
				}

				/* Restructure formValue so that it has the full URL */
				$updatedFormValue = [];
				foreach( explode( ",", $formValue ) as $val )
				{
					$updatedFormValue[] = File::get( static::$uploadStorageExtension, $val )->url;
				}
				$formValue = implode( ",", $updatedFormValue );
			}
			if ( ! isset( Store::i()->$functionName ) )
			{
				Store::i()->$functionName = Theme::compileTemplate( $template, $functionName, '$value, $formValue, $label, $record' );
			}

			$template = Store::i()->$functionName;
		}

		Theme::runProcessFunction( $template, $functionName );

		$themeFunction = 'IPS\\Theme\\'. $functionName;
		return $themeFunction( $value, $formValue, $this->_title, $record );
	}
	
	/**
	 * Show this form field?
	 * 
	 * @param string $field		Field key
	 * @param string $where		Where to show, form or record
	 * @param Group|Member|null $member			The member or group to check (NULL for currently logged in member)
	 * @return	 boolean
	 */
	public static function fixedFieldFormShow( string $field, string $where='form', Group|Member $member=NULL ): bool
	{
		$fixedFields = Databases::load( static::$customDatabaseId )->fixed_field_perms;
		$perm        = ( $where === 'form' ) ? 'perm_2' : 'perm_view';
		
		if ( ! in_array( $field, array_keys( $fixedFields ) ) )
		{
			return FALSE;
		}
		
		$permissions = $fixedFields[ $field ];
		
		if ( empty( $permissions['visible'] ) OR empty( $permissions[ $perm ] ) )
		{
			return FALSE;
		}
		
		/* Load member */
		if ( $member === NULL )
		{
			$member = Member::loggedIn();
		}
		
		/* Finally check permissions */
		if( $member instanceof Group )
		{
			return ( $permissions[ $perm ] === '*' or ( $permissions[ $perm ] and in_array( $member->g_id, explode( ',', $permissions[ $perm ] ) ) ) );
		}
		else
		{
			return ( $permissions[ $perm ] === '*' or ( $permissions[ $perm ] and $member->inGroup( explode( ',', $permissions[ $perm ] ) ) ) );
		}
	}
	
	/**
	 * Get fixed field permissions as an array or a *
	 * 
	 * @param string|null $field		Field Key
	 * @return array|string|null
	 */
	public static function fixedFieldPermissions( string $field=NULL ): array|string|null
	{
		$fixedFields = Databases::load( static::$customDatabaseId )->fixed_field_perms;
		
		if ( $field !== NULL AND in_array( $field, array_keys( $fixedFields ) ) )
		{
			return $fixedFields[ $field ]; 
		}
		
		return ( $field !== NULL ) ? NULL : $fixedFields;
	}
	
	/**
	 * Set fixed field permissions
	 *
	 * @param string $field		Field Key
	 * @param array $values		Perm values
	 * @return  void
	 */
	public static function setFixedFieldPermissions( string $field, array $values ) : void
	{
		$fixedFields = Databases::load( static::$customDatabaseId )->fixed_field_perms;

		foreach( $values as $k => $v )
		{
			$fixedFields[ $field ][ $k ] = $v;
		}

		Databases::load( static::$customDatabaseId )->fixed_field_perms = $fixedFields;
		Databases::load( static::$customDatabaseId )->save();
	}
	
	/**
	 * Set the visiblity
	 *
	 * @param string $field		Field Key
	 * @param bool $value		True/False
	 * @return  void
	 */
	public static function setFixedFieldVisibility( string $field, bool $value=FALSE ) : void
	{
		$fixedFields = Databases::load( static::$customDatabaseId )->fixed_field_perms;
	
		$fixedFields[ $field ]['visible'] = $value;

		Databases::load( static::$customDatabaseId )->fixed_field_perms = $fixedFields;
		Databases::load( static::$customDatabaseId )->save();
	}
	
	/**
	 * Magic method to capture validateInput_{id} callbacks
	 * @param	string $name		Name of method called
	 * @param	mixed 		$arguments	Args passed
	 *@throws InvalidArgumentException
	 */
	public static function __callStatic( string $name, mixed $arguments)
	{
		if ( mb_substr( $name, 0, 14 ) === 'validateInput_' )
		{
			$id = mb_substr( $name, 14 );
			
			if ( is_numeric( $id ) )
			{
				$field = static::load( $id );
			}
			
			if ( ! empty($arguments[0]) AND $field->validator AND $field->validator_custom )
			{
				if ( ! preg_match( $field->validator_custom, $arguments[0] ) )
				{
					throw new InvalidArgumentException( ( Member::loggedIn()->language()->addToStack('content_field_' . $field->id . '_validation_error') === 'content_field_' . $field->id . '_validation_error' ) ? 'content_exception_invalid_custom_validation' : Member::loggedIn()->language()->addToStack('content_field_' . $field->id . '_validation_error') );
				}
			}
		}
	}
	
	/**
	 * Checks to see if this value is unique
	 * Used in custom validation for fomr helpers
	 *
	 * @param mixed $val	The value to check
	 * @param Fields $field	The field
	 * @param Records|null $record	The record (if any)
	 * @return	void
	 * @throws LogicException
	 */
	public static function validateUnique( mixed $val, Fields $field, ?Records $record ) : void
	{
		if ( $val === '' )
		{
			return;
		}
		
		$database = Databases::load( static::$customDatabaseId );

		if( $field->type == 'Member' AND $val instanceof Member )
		{
			$val = $val->member_id;
		}
		
		$where = array( array( 'field_' . $field->id . '=?', $val ) );
							
		if ( $record !== NULL and $record->_id )
		{

			$where[] = array( 'primary_id_field != ?', $record->_id );
		}

		if ( Db::i()->select( 'COUNT(*)', 'cms_custom_database_' . $database->id, $where )->first() )
		{
			throw new LogicException( Member::loggedIn()->language()->addToStack( "field_unique_entry_not_unique", FALSE, array( 'sprintf' => array( $database->recordWord( 1 ) ) ) ) );
		}
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}
		
		parent::__clone();
		
		$this->key .= '_' . $this->id;
		$this->save();
	}
	
	/**
	 * Set some default values
	 * 
	 * @return void
	 */
	public function setDefaultValues() : void
	{
		$this->_data['extra'] = '';
		$this->_data['default_value'] = '';
		$this->_data['format_opts'] = '';
		$this->_data['validator'] = '';
		$this->_data['topic_format'] = '';
		$this->_data['allowed_extensions'] = '';
		$this->_data['validator_custom'] = '';
		$this->_data['display_json'] = array();
	}

	/**
	 * Field custom template name
	 *
	 * @param string $type   Type of name to fetch
	 * @return	string
	 */
	public function fieldTemplateName( string $type ): string
	{
		return 'pages_field_custom_html_' . $type . '_' . $this->id;
	}

	/**
	 * Set the "display json" field
	 *
	 * @param mixed $value	Value
	 * @return void
	 */
	public function set_display_json( mixed $value ) : void
	{
		$this->_data['display_json'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get the "display json" field
	 *
	 * @return array
	 */
	public function get_display_json(): array
	{
		return ( is_array( $this->_data['display_json'] ) ) ? $this->_data['display_json'] : json_decode( $this->_data['display_json'], TRUE );
	}

	/**
	 * Set the "Format Options" field
	 *
	 * @param mixed $value	Value
	 * @return void
	 */
	public function set_format_opts( mixed $value ) : void
	{
		$this->_data['format_opts'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "Format Options" field
	 *
	 * @return mixed
	 */
	public function get_format_opts(): mixed
	{
		return isset( $this->_data['format_opts'] ) ? json_decode( $this->_data['format_opts'], TRUE ) : [];
	}
	
	/**
	 * Set the "extra" field
	 * 
	 * @param mixed $value	Value
	 * @return void
	 */
	public function set_extra( mixed $value ) : void
	{
		$this->_data['extra'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Set the "allowed_extensions" field
	 *
	 * @param mixed $value	Value
	 * @return void
	 */
	public function set_allowed_extensions( mixed $value ) : void
	{
		$this->_data['allowed_extensions'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "extra" field
	 *
	 * @return array
	 */
	public function get_extra(): mixed
	{
		return isset( $this->_data['extra'] ) ? json_decode( $this->_data['extra'], TRUE ) : [];
	}
	
	/**
	 * Get the "allowed_extensions" field
	 *
	 * @return array|null
	 */
	public function get_allowed_extensions(): ?array
	{
		return isset( $this->_data['allowed_extensions'] ) ? json_decode( $this->_data['allowed_extensions'], TRUE ) : [];
	}
	
	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		if ( !$this->id )
		{
			return '';
		}
		
		try
		{
			return Member::loggedIn()->language()->get( static::$langKey . '_' . $this->id );
		}
		catch( UnderflowException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * [Node] Get Description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		try
		{
			return Member::loggedIn()->language()->get( static::$langKey . '_' . $this->id . '_desc' );
		}
		catch( UnderflowException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		$badge = null;

		if ( Databases::load( $this->database_id )->field_title == $this->id )
		{
			$badge = array( 0 => 'positive i-float_end', 1 => 'content_fields_is_title' );
		}
		else if ( Databases::load( $this->database_id )->field_content == $this->id )
		{
			$badge = array( 0 => 'positive i-float_end', 1 => 'content_fields_is_content' );
		}
		
		return $badge;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note    Return the class for the icon (e.g. 'globe')
	 * @return mixed
	 */
	protected function get__icon(): mixed
	{
		if ( class_exists( '\IPS\Helpers\Form\\' . IPS::mb_ucfirst( $this->type ) ) )
		{
			return NULL;
		}
		else if ( class_exists( '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $this->type ) ) )
		{
			return NULL;
		}

		return 'warning';
	}


	/**
	 * Truncate the field value
	 *
	 * @param string|null $text Value to truncate
	 * @param boolean $oneLine Truncate to a single line?
	 * @return    string|null
	 */
	public function truncate( ?string $text, bool $oneLine=FALSE ): ?string
	{
		if ( ! $this->truncate )
		{
			return $text;
		}
		
		switch( IPS::mb_ucfirst( $this->type ) )
		{
			default:
				// No truncate
			break;
			case 'Radio':
			case 'Select':
			case 'Text':
				$text = mb_substr( $text, 0, $this->truncate );
			break;
			case 'TextArea':
			case 'Editor':
				$text = preg_replace( '#</p>(\s+?)?<p>#', $oneLine ? ' $1' : '<br>$1', $text );
				$text = str_replace( array( '<p>', '</p>', '<div>', '</div>' ), '', $text );
				$text = '<div data-ipsTruncate data-ipsTruncate-size="' . $this->truncate . ' lines">' . $text . '</div>';
			break;
		}
		
		return $text;
	}

	/**
	 * Display Value
	 *
	 * @param mixed $value The value
	 * @param bool $showSensitiveInformation If TRUE, potentially sensitive data (like passwords) will be displayed - otherwise will be blanked out
	 * @param string|null $separator Used to separate items when displaying a field with multiple values.
	 * @return string|null
	 */
	public function displayValue( mixed $value=NULL, bool $showSensitiveInformation=FALSE, string $separator=NULL ): ?string
	{
		$database = Databases::load( static::$customDatabaseId );

		/* Extension */
		if( $extension = $this->extension() )
		{
			return $extension::displayValue( $this, $value, $showSensitiveInformation, $separator );
		}
		
		if ( class_exists( '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $this->type ) ) )
		{
			/* Is special! */
			$class = '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $this->type );
			
			if ( method_exists( $class, 'displayValue' ) )
			{
				return $class::displayValue( $value, $this );
			}
		}
		
		switch( IPS::mb_ucfirst( $this->type ) )
		{
			case 'Upload':
				/* We need to return NULL if there's no value, File::get will return an URL object even if $value is empty */
				if ( empty( $value ) )
				{
					return NULL;
				}

				return File::get( 'cms_Records', $value )->url;

			case 'Text':
			case 'TextArea':
				$value = $this->applyFormatter( $value );

				/* We don't want the parent adding wordbreak to the title */
				if ( $this->id == $database->field_title )
				{
					return $value;
				}
				else if ( $this->id == $database->field_content )
				{
					return nl2br( $value );
				}
				
				/* If we allow HTML, then do not pass to parent::displayValue as htmlspecialchars is run */
				if ( $this->html )
				{
					return $value;
				}
			break;
			case 'Select':
			case 'Radio':
			case 'CheckboxSet':
				/* This comes from a keyValue stack, so reformat */
				if ( $this->extra and isset( $this->extra[0]['key'] ) )
				{
					$extra = array();
					foreach( $this->extra as $id => $row )
					{
						$extra[ $row['key'] ] = $row['value']; 
					}
			
					$this->extra = $extra;
				}

				if ( ! is_array( $value ) )
				{
					$value = explode( ',', $value );
				}

				if ( is_array( $value ) )
				{
					$return = array();
					foreach( $value as $key )
					{
						$return[] = isset( $this->extra[ $key ] ) ? htmlspecialchars( $this->extra[ $key ], ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) : htmlspecialchars( $key, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE );
					}

					return implode( ', ', $return );
				}
				else
				{
					return ( isset( $this->extra[ $value ] ) ? htmlspecialchars( $this->extra[ $value ], ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) : htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) );
				}

			case 'Member':
				if ( ! $value )
				{
					return NULL;
				}
				else
				{
					$links = array();

					$value = is_array( $value ) ? $value : ( ( $value instanceof Member ) ? array( $value ) : explode( "\n", $value ) );
					
					foreach( $value as $id )
					{
						$links[] = ( $id instanceof Member ) ? $id->link() : Member::load( $id )->link();
					}
					
					return implode( ', ', $links );
				}

			case 'Url':
				if ( Dispatcher::hasInstance() AND class_exists( '\IPS\Dispatcher', FALSE ) and Dispatcher::i()->controllerLocation === 'front' )
				{
					return ( $value ) ? Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $value, TRUE, NULL, FALSE ) : NULL;
				}
			break;
			case 'Date':
			case 'DateRange':
				if ( is_numeric( $value ) )
				{
					$time = DateTime::ts( $value );
					
					if ( isset( $this->extra['timezone'] ) and $this->extra['timezone'] )
					{
						/* The timezone is already set to user by virtue of DateTime::ts() */
						if ( $this->extra['timezone'] != 'user' )
						{							
							$time->setTimezone( new DateTimeZone( $this->extra['timezone'] ) );
						}
					}
					else
					{
						$time->setTimezone( new DateTimeZone( 'UTC' ) );
					}
	
					return $this->extra['time'] ? (string) $time : $time->localeDate();
				}
				
				if ( ! is_array( $value ) )
				{
					return Member::loggedIn()->language()->addToStack('field_no_value_entered');
				}
				
				$start = NULL;
				$end   = NULL;
				foreach( array( 'start', 'end' ) as $t )
				{
					if ( isset( $value[ $t ] ) )
					{
						$time = ( is_integer( $value[ $t ] ) ) ? DateTime::ts( $value[ $t ], TRUE ) : $value[ $t ];
						if ( isset( $this->extra['timezone'] ) and $this->extra['timezone'] )
						{
							try
							{
								$time->setTimezone( new DateTimeZone( $this->extra['timezone'] ) );
							}
							catch( Exception $e ){}
						}
						
						$$t = $time->localeDate();
					}
				}
				
				if ( $start and $end )
				{
					return Member::loggedIn()->language()->addToStack( 'field_daterange_start_end', FALSE, array( 'sprintf' => array( $start, $end ) ) );
				}
				else if ( $start )
				{
					return Member::loggedIn()->language()->addToStack( 'field_daterange_start', FALSE, array( 'sprintf' => array( $start ) ) );
				}
				else if ( $end )
				{
					return Member::loggedIn()->language()->addToStack( 'field_daterange_end', FALSE, array( 'sprintf' => array( $end ) ) );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('field_no_value_entered');
				}

			case 'Item':
				if ( ! is_array( $value ) and mb_strstr( $value, ',' ) )
				{
					$value = explode( ',', $value );
				}
				else
				{
					$value = array( $value );
				}

				if ( count( $value ) and isset( $this->extra['database'] ) and $this->extra['database'] )
				{
					$results = array();
					$class   = '\IPS\cms\Records' . $this->extra['database'];
					/* @var $databaseColumnMap array */
					/* @var $class Records */
					$field   = $class::$databasePrefix . $class::$databaseColumnMap['title'];
					$where   = array( Db::i()->in( $class::$databaseColumnId, $value ) );

					foreach( $class::getItemsWithPermission( array( $where ), $field, NULL ) as $item )
					{
						$results[] = $item;
					}

                    if( count( $results ) )
                    {
                        return Theme::i()->getTemplate( 'global', 'cms', 'front' )->basicRelationship( $results );
                    }
				}

				return NULL;
		}

		/* Formatters */
		try
		{
			return parent::displayValue( $value, $showSensitiveInformation );
		}
		catch( InvalidArgumentException $ex )
		{
			return NULL;
		}
	}
	
	/**
	 * Apply formatter
	 *
	 * @param	mixed	$value	The value
	 * @return	string
	 */
	public function applyFormatter( mixed $value ): string
	{
		if ( is_array( $this->format_opts ) and count( $this->format_opts ) )
		{
			foreach( $this->format_opts as $id => $type )
			{
				switch( $type )
				{
					case 'strtolower':
						$value	= mb_convert_case( $value, MB_CASE_LOWER );
					break;
					
					case 'strtoupper':
						$value	= mb_convert_case( $value, MB_CASE_UPPER );
					break;
					
					case 'ucfirst':
						$value	= ( mb_strtoupper( mb_substr( $value, 0, 1 ) ) . mb_substr( $value, 1, mb_strlen( $value ) ) );
					break;
					
					case 'ucwords':
						$value	= mb_convert_case( $value, MB_CASE_TITLE );
					break;
					
					case 'punct':
						$value	= preg_replace( "/\?{1,}/"		, "?"		, $value );
						$value	= preg_replace( "/(&#33;){1,}/"	, "&#33;"	, $value );
					break;
					
					case 'numerical':
						$value	= Member::loggedIn()->language()->formatNumber( $value );
					break;
				}
			}
		}
		
		return $value ?? '';
	}


	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 * array(
	 * array(
	 * 'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 * 'title'	=> 'foo',		// Language key to use for button's title parameter
	 * 'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 * 'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 * ),
	 * ...							// Additional buttons
	 * );
	 * @endcode
	 * @param Url $url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ):array
	{
		$buttons  = parent::getButtons( $url, $subnode );
		$database = Databases::load( $this->database_id );

		if ( $this->canEdit() )
		{
			if ( $this->id != $database->field_title and $this->id != $database->field_content )
			{
				if ( $this->canBeTitleField() )
				{
					$buttons['set_as_title'] = array(
						'icon'	=> 'list-ul',
						'title'	=> 'cms_set_field_as_title',
						'link'	=> $url->setQueryString( array( 'do' => 'setAsTitle', 'id' => $this->_id ) )->csrf(),
						'data'	=> array()
					);
				}

				if ( $this->canBeContentField() )
				{
					$buttons['set_as_content'] = array(
						'icon'	=> 'file-text',
						'title'	=> 'cms_set_field_as_content',
						'link'	=> $url->setQueryString( array( 'do' => 'setAsContent', 'id' => $this->_id ) )->csrf(),
						'data'	=> array()
					);
				}
			}

			if( in_array( $this->type, static::$canUseTogglesFields ) )
			{
				$buttons['toggles'] = array(
					'icon' => 'toggle-on',
					'title' => 'cms_fields_toggles',
					'link' => $url->setQueryString( array( 'do' => 'toggles', 'id' => $this->_id ) ),
					'data' => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'cms_fields_toggles' ) )
				);
			}
		}

		return $buttons;
	}

	/**
	 * Can this field be a title field?
	 *
	 * @return boolean
	 */
	public function canBeTitleField(): bool
	{
		if ( $this->is_multiple or in_array( IPS::mb_ucfirst( $this->type ), static::$cannotBeTitleFields ) )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Can this field be a content field?
	 *
	 * @return boolean
	 */
	public function canBeContentField(): bool
	{
		if ( $this->is_multiple or in_array( IPS::mb_ucfirst( $this->type ), static::$cannotBeContentFields ) )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		$database = Databases::load( $this->database_id );

		if ( $this->id == $database->field_title or $this->id == $database->field_content )
		{
			return FALSE;
		}

		return parent::canDelete();
	}

	/**
	 *
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		return true;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->hiddenValues['database_id'] = static::$customDatabaseId;

		if ( $this->type )
		{
			$ok = FALSE;
			if ( class_exists( '\IPS\Helpers\Form\\' . IPS::mb_ucfirst( $this->type ) ) )
			{
				$ok = TRUE;
			}
			else if ( class_exists( '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $this->type ) ) )
			{
				$ok = TRUE;
			}

			if ( !$ok )
			{
				Output::i()->output .= Theme::i()->getTemplate( 'global', 'core', 'global' )->message( Member::loggedIn()->language()->addToStack( 'cms_field_no_type_warning', FALSE, array( 'sprintf' => array( $this->type ) ) ), 'warning', NULL, FALSE );
			}
		}

		$form->addTab( 'field_generaloptions' );
		$form->addHeader( 'pfield_settings' );

		$form->add( new Translatable( 'field_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id : NULL ) ) ) );
		$form->add( new Translatable( 'field_description', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id . '_desc' : NULL ) ) ) );

		$displayDefaults = array( 'field_display_listing_json_badge', 'field_display_listing_json_custom', 'field_display_display_json_badge', 'field_display_display_json_custom', 'field_display_display_json_where' );

		$options = array_merge( array(
            'Address'    => 'pf_type_Address',
            'Checkbox'   => 'pf_type_Checkbox',
            'CheckboxSet' => 'pf_type_CheckboxSet',
            'Codemirror' => 'pf_type_Codemirror',
            'Item'       => 'pf_type_Relational',
            'Date'		 => 'pf_type_Date',
            'Editor'	 => 'pf_type_Editor',
            'Email'		 => 'pf_type_Email',
            'Member'     => 'pf_type_Member',
            'Number'	 => 'pf_type_Number',
            'Password'	 => 'pf_type_Password',
            'Radio'		 => 'pf_type_Radio',
            'Select'	 => 'pf_type_Select',
            'Tel'		 => 'pf_type_Tel',
            'Text'		 => 'pf_type_Text',
            'TextArea'	 => 'pf_type_TextArea',
            'Upload'	 => 'pf_type_Upload',
            'Url'		 => 'pf_type_Url',
            'YesNo'		 => 'pf_type_YesNo',
            'Youtube'    => 'pf_type_Youtube',
            'Spotify'    => 'pf_type_Spotify',
            'Soundcloud' => 'pf_type_Soundcloud',
        ), static::$additionalFieldTypes );

		$toggles = array(
			'Address'	=> [ ...$displayDefaults ], // copy to a new array juuuust in case
			'Codemirror'=> array_merge( array( 'field_default_value', 'field_truncate' ), $displayDefaults ),
			'Checkbox'  => array_merge( array( 'field_default_value', 'field_truncate' ), $displayDefaults ),
			'CheckboxSet' => array_merge( array( 'field_extra', 'field_default_value', 'field_truncate', 'field_option_other' ), $displayDefaults ),
			'Date'		=> array_merge( array( 'field_default_value', 'field_date_time_override', 'field_date_time_time' ), $displayDefaults ),
			'Editor'    => array_merge( array( 'field_max_length', 'field_default_value', 'field_truncate', 'field_allow_attachments' ), $displayDefaults ),
			'Email'		=> array_merge( array( 'field_max_length', 'field_default_value', 'field_unique' ), $displayDefaults ),
			'Item'      => array_merge( array( 'field_is_multiple', 'field_relational_db', 'field_crosslink' ), $displayDefaults ),
			'Member'    => array_merge( array( 'field_is_multiple', 'field_unique' ), $displayDefaults ),
			'Number'    => array_merge( array( 'field_default_value', 'field_number_decimals_on', 'field_number_decimals', 'field_unique', 'field_number_min', 'field_number_max' ), $displayDefaults ),
			'Password'  => array_merge( array( 'field_default_value' ), $displayDefaults ),
			'Radio'     => array_merge( array( 'field_extra', 'field_default_value', 'field_truncate', 'field_unique', 'field_option_other' ), $displayDefaults ),
			'Select'    => array_merge( array( 'field_extra', 'field_default_value', 'field_is_multiple', 'field_truncate', 'field_unique', 'field_option_other' ), $displayDefaults ),
			'Tel'		=> array_merge( array( 'field_default_value', 'field_unique' ), $displayDefaults ),
			'Text'		=> array_merge( array( 'field_validator', 'field_format_opts_on', 'field_max_length', 'field_default_value', 'field_html', 'field_truncate', 'field_unique' ), $displayDefaults ),
			'TextArea'	=> array_merge( array( 'field_validator', 'field_format_opts_on', 'field_max_length', 'field_default_value', 'field_html', 'field_truncate', 'field_unique' ), $displayDefaults ),
			'Upload'    => array_merge( array( 'field_upload_is_image', 'field_upload_is_multiple', 'field_upload_thumb' ), $displayDefaults ),
			'Url'		=> array_merge( array( 'field_default_value', 'field_unique' ), $displayDefaults ),
			'YesNo'		=> array_merge( array( 'field_default_value' ), $displayDefaults ),
			'Youtube'   => array( 'media_params', 'media_display_listing_method', 'media_display_display_method', 'field_unique' ),
			'Spotify'   => array( 'media_params', 'media_display_listing_method', 'media_display_display_method', 'field_unique' ),
			'Soundcloud'=> array( 'media_params', 'media_display_listing_method', 'media_display_display_method', 'field_unique' )
		);

		foreach ( $toggles as $k => $v )
		{
			$toggles[$k] = array_merge( $v, [ 'field_display_listing', 'field_display_display' ] );
		}

		/* Add field options and toggles from the extensions */
		foreach( Application::allExtensions( 'core', 'CustomField' ) as $ext )
		{
			/* @var CustomFieldAbstract $ext */
			if( $ext::isEnabled() )
			{
				$options[ $ext::fieldType() ] = $ext::fieldTypeTitle();
				$toggles[ $ext::fieldType() ] = $ext::fieldTypeToggles();
			}
		}
		
		foreach( static::$filterableFields as $field )
		{
			$toggles[ $field ][] = 'field_filter';
		}

		foreach ( static::$additionalFieldTypes as $k => $v )
		{
			$toggles[ $k ] = static::$additionalFieldToggles[$k] ?? array('pf_not_null');
		}
		
		/* Title or content? */
		$isTitleField	= FALSE;

		if ( $this->id )
		{
			$database = Databases::load( static::$customDatabaseId );
		
			if ( $this->id == $database->field_title )
			{
				$isTitleField	= TRUE;

				foreach( static::$cannotBeTitleFields as $type )
				{
					unset( $options[ $type ] );
					unset( $toggles[ $type ] );
				}
			}
			else if ( $this->id == $database->field_content )
			{
				foreach( static::$cannotBeContentFields as $type )
				{
					unset( $options[ $type ] );
					unset( $toggles[ $type ] );
				}
			}
		}
		
		ksort( $options );

		if ( !$this->_new )
		{
			Member::loggedIn()->language()->words['field_type_warning'] = Member::loggedIn()->language()->addToStack('custom_field_change');

			foreach ( $toggles as $k => $_toggles )
			{
				if ( !$this->canKeepValueOnChange( $k ) )
				{
					$toggles[ $k ][] = 'form_' . $this->id . '_field_type_warning';
				}
			}
		}

		$form->add( new Select( 'field_type', $this->id ? IPS::mb_ucfirst( $this->type ) : 'Text', TRUE, array(
				'options' => $options,
				'toggles' => $toggles
		) ) );

		/* Relational specific */
		if( !$isTitleField )
		{
			$databases = array();
			$disabled  = array();
			foreach(Databases::databases() as $db )
			{
				if ( $db->page_id )
				{
					$databases[ $db->id ] = $db->_title;
				}
				else
				{
					$disabled[] = $db->id;
					$databases[ $db->id ] = Member::loggedIn()->language()->addToStack( 'cms_db_relational_with_name_disabled', FALSE, array( 'sprintf' => array( $db->_title ) ) );
				}
			}
			if ( ! count( $databases ) )
			{
				$databases[0] = Member::loggedIn()->language()->addToStack('cms_relational_field_no_dbs');
				$disabled[] = 0;
			}

			$form->add( new Select( 'field_relational_db', ($this->extra['database'] ?? NULL), FALSE, array( 'options' => $databases, 'disabled' => $disabled ), NULL, NULL, NULL, 'field_relational_db' ) );
			$form->add( new YesNo( 'field_crosslink', $this->id && ( isset( $this->extra['crosslink'] ) and $this->extra['crosslink'] ), FALSE, array(), NULL, NULL, NULL, 'field_crosslink' ) );
		}

		/* Number specific */
		$form->add( new Number( 'field_number_min', ( $this->id and isset( $this->extra['min'] ) ) ? $this->extra['min'] : NULL, FALSE, array( 'unlimited' => '', 'unlimitedLang' => 'any', 'decimals' => true ), NULL, NULL, NULL, 'field_number_min' ) );
		$form->add( new Number( 'field_number_max', ( $this->id and isset( $this->extra['max'] ) ) ? $this->extra['max'] : NULL, FALSE, array( 'unlimited' => '', 'unlimitedLang' => 'any', 'decimals' => true ), NULL, NULL, NULL, 'field_number_max' ) );
		$form->add( new YesNo( 'field_number_decimals_on', $this->id && ( isset( $this->extra['on'] ) and $this->extra['on'] ), FALSE, array( 'togglesOn' => array( 'field_number_decimals' ) ), NULL, NULL, NULL, 'field_number_decimals_on' ) );
		$form->add( new Number( 'field_number_decimals', $this->id ? ( ( isset( $this->extra['places'] ) and $this->extra['places'] ) ? $this->extra['places'] : 0 ) : 0, FALSE, array( 'max' => 6 ), NULL, NULL, NULL, 'field_number_decimals' ) );

		/* Upload specific */
		$form->add( new YesNo( 'field_upload_is_multiple', $this->id ? $this->is_multiple : 0, FALSE, array( ), NULL, NULL, NULL, 'field_upload_is_multiple' ) );

		$form->add( new Radio( 'field_upload_is_image', $this->id ? ( ( isset( $this->extra['type'] ) and $this->extra['type'] == 'image' ) ? 'yes' : 'no' ) : 'yes', TRUE, array(
			'options'	=> array(
				'yes' => 'cms_upload_field_is_image',
				'no'  => 'cms_upload_field_is_not_image',

			),
			'toggles' => array(
				'yes' => array( 'field_image_size', 'field_upload_thumb' ),
				'no'  => array( 'field_allowed_extensions' )
			)
		), NULL, NULL, NULL, 'field_upload_is_image' ) );

		$widthHeight = NULL;
		$thumbWidthHeight = array( 0, 0 );
		if ( isset( $this->extra['type'] ) and $this->extra['type'] === 'image' )
		{
			$widthHeight = $this->extra['maxsize'];
			
			if ( isset( $this->extra['thumbsize'] ) )
			{
				$thumbWidthHeight = $this->extra['thumbsize'];
			}
		}

		$form->add( new WidthHeight( 'field_image_size', $this->id ? $widthHeight : array( 0, 0 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'field_image_size' ) );
		
		$form->add( new WidthHeight( 'field_upload_thumb', $this->id ? $thumbWidthHeight : array( 0, 0 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ), 'unlimitedLang' => 'field_upload_thumb_none' ), NULL, NULL, NULL, 'field_upload_thumb' ) );
		
		$form->add( new Text( 'field_allowed_extensions', $this->id ? ( $this->allowed_extensions ?: NULL ) : NULL, FALSE, array(
			'autocomplete' => array( 'unique' => 'true' ),
			'nullLang'     => 'content_any_extensions'
		), NULL, NULL, NULL, 'field_allowed_extensions' ) );

		/* Editor Specific */
		if( !$isTitleField )
		{
			$form->add( new YesNo( 'field_allow_attachments', $this->id ? $this->allow_attachments : 1, FALSE, array( ), NULL, NULL, NULL, 'field_allow_attachments' ) );
		}

		/* Date specific */
		$tzValue = 'UTC';
		if ( isset( $this->extra['timezone'] ) and $this->extra['timezone'] )
		{
			if ( ! in_array( $this->extra['timezone'], array( 'UTC', 'user' ) ) )
			{
				 $tzValue = 'set';
			}
			else
			{
				$tzValue = $this->extra['timezone'];
			}
		}
		
		$form->add( new Radio( 'field_date_time_override', $tzValue, FALSE, array(
			'options' => array(
				'UTC'  => 'field_date_tz_utc',
				'set'  => 'field_date_tz_set',
				'user' => 'field_date_tz_user'
			),
			'toggles' => array(
				'set' => array( 'field_date_timezone' )
			)
		), NULL, NULL, NULL, 'field_date_time_override' ) );
			
		$timezones = array();
		foreach ( DateTime::getTimezoneIdentifiers() as $tz )
		{
			if ( $pos = mb_strpos( $tz, '/' ) )
			{
				$timezones[ 'timezone__' . mb_substr( $tz, 0, $pos ) ][ $tz ] = 'timezone__' . $tz;
			}
			else
			{
				$timezones[ $tz ] = 'timezone__' . $tz;
			}
		}
		$form->add( new Select( 'field_date_timezone', ( $this->extra['timezone'] ?? Member::loggedIn()->timezone), FALSE, array( 'options' => $timezones ), NULL, NULL, NULL, 'field_date_timezone' ) );
		$form->add( new YesNo( 'field_date_time_time', ( $this->extra['time'] ?? 0), FALSE, array(), NULL, NULL, NULL, 'field_date_time_time' ) );

		$form->add( new YesNo( 'field_is_multiple', $this->id ? $this->is_multiple : 0, FALSE, array(), NULL, NULL, NULL, 'field_is_multiple' ) );
		
		$form->add( new TextArea( 'field_default_value', $this->id ? $this->default_value : '', FALSE, array(), NULL, NULL, NULL, 'field_default_value' ) );

		if ( ! $this->_new )
		{
			$form->add( new YesNo( 'field_default_update_existing', FALSE, FALSE, array(), NULL, NULL, NULL, 'field_default_update_existing' ) );
		}

		$form->add( new Number( 'field_max_length', $this->id ? $this->max_length : NULL, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'field_max_length' ) );
		
		$form->add( new YesNo( 'field_validator', $this->id ? intval( $this->validator ) : 0, FALSE, array(
			'togglesOn' =>array( 'field_validator_custom', 'field_validator_error' )
		), NULL, NULL, NULL, 'field_validator' ) );
		
		$form->add( new Text( 'field_validator_custom', $this->id ? $this->validator_custom : NULL, FALSE, array( 'placeholder' => '/[A-Z0-9]+/i' ), NULL, NULL, NULL, 'field_validator_custom' ) );
		$form->add( new Translatable( 'field_validator_error', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id . '_validation_error' : NULL ) ), NULL, NULL, NULL, 'field_validator_error' ) );
		
		$form->add( new YesNo( 'field_format_opts_on', $this->id ? $this->format_opts : 0, FALSE, array( 'togglesOn' => array('field_format_opts') ), NULL, NULL, NULL, 'field_format_opts_on' ) );
		
		$form->add( new Select( 'field_format_opts', $this->id ? $this->format_opts : 'none', FALSE, array(
				'options' => array(
						'strtolower' => 'content_format_strtolower',
						'strtoupper' => 'content_format_strtoupper',
						'ucfirst'    => 'content_format_ucfirst',
						'ucwords'    => 'content_format_ucwords',
						'punct'	     => 'content_format_punct',
						'numerical'	 => 'content_format_numerical'
				),
				'multiple' => true
		), NULL, NULL, NULL, 'field_format_opts' ) );
		
		$extra = array();
		if ( $this->id AND $this->extra )
		{
			foreach( $this->extra as $k => $v )
			{
				$extra[] = array( 'key' => $k, 'value' => $v );
			}
		}
		
		$form->add( new Stack( 'field_extra', $extra, FALSE, array( 'stackFieldType' => 'KeyValue'), NULL, NULL, NULL, 'field_extra' ) );

		$form->add( new YesNo( 'field_option_other', $this->format_opts['use_other'] ?? null, false, array(), null, null, null, 'field_option_other' ) );

		/* Media specific stack */
		$form->add( new Stack( 'media_params', $extra, FALSE, array( 'stackFieldType' => 'KeyValue'), NULL, NULL, NULL, 'media_params' ) );

		$form->addheader( 'pfield_options' );
		
		$form->add( new YesNo( 'field_unique', $this->id ? $this->unique : 0, FALSE, array(), NULL, NULL, NULL, 'field_unique' ) );
		
		$form->add( new YesNo( 'field_filter', $this->id ? $this->filter : 0, FALSE, array(), NULL, NULL, NULL, 'field_filter' ) );

		/* Until we have a mechanism for other field searching, remove this $form->add( new \IPS\Helpers\Form\YesNo( 'field_is_searchable', $this->id ? $this->is_searchable : 0, FALSE, array(), NULL, NULL, NULL, 'field_is_searchable' ) );*/

		if ( isset( Request::i()->database_id ) )
		{
			$usingForum = Databases::load( Request::i()->database_id )->forum_record;
			if ( ! $usingForum and $this->id )
			{
				$usingForum = Db::i()->select( 'COUNT(*)', 'cms_database_categories', array( 'category_database_id=? and category_forum_override=1 and category_forum_record=1', $this->id ) )->first();
			}
			
			if ( $usingForum )
			{
				$form->add( new TextArea( 'field_topic_format', $this->id ? $this->topic_format : '', FALSE, array( 'placeholder' => "<strong>{title}:</strong> {value}" ) ) );
			}
		}

		if ( !$isTitleField )
		{
			$form->add( new YesNo( 'field_required', $this->id ? $this->required : TRUE, FALSE ) );
		}

		$form->add( new YesNo( 'field_html', $this->id ? $this->html : FALSE, FALSE, array( 'togglesOn' => array( 'field_html_warning' ) ), NULL, NULL, NULL, 'field_html' ) );

		$form->addTab( 'field_displayoptions' );

		$isTitleOrContent = FALSE;
		if ( $this->id AND ( $this->id == Databases::load( static::$customDatabaseId )->field_title OR $this->id == Databases::load( static::$customDatabaseId )->field_content ) )
		{
			$isTitleOrContent = TRUE;

			if ( $this->id == Databases::load( static::$customDatabaseId )->field_title )
			{
				$form->addMessage( 'field_display_opts_title', 'ipsMessage ipsMessage_info' );
			}

			if ( $this->id == Databases::load( static::$customDatabaseId )->field_content )
			{
				$form->addMessage( 'field_display_opts_content', 'ipsMessage ipsMessage_info' );
			}
		}

		$form->add( new Text( 'field_key', $this->id ? $this->key : FALSE, FALSE, array(), function( $val )
		{
			try
			{
				if ( ! $val )
				{
					return true;
				}

				$class = '\IPS\cms\Fields' . Request::i()->database_id;
				/* @var $class Fields */
				try
				{
					$testField = $class::load( $val, 'field_key');
				}
				catch( OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
					return true;
				}

				/* It's taken... */
				if ( Request::i()->id == $testField->id )
				{
					/* But it's this one so that's ok */
					return true;
				}

				/* and if we're here, it's not... */
				throw new InvalidArgumentException('cms_field_key_not_unique');
			}
			catch ( OutOfRangeException $e )
			{
				/* Slug is OK as load failed */
				return true;
			}
		} ) );

		$displayToggles = array(
			'badge' => array( 'field_display_display_badge_bgcolor', 'field_display_display_badge_color' ),
			'custom' => array( 'field_display_display_json_custom' )
		);
		$listingToggles = array(
			'badge' => array( 'field_display_listing_badge_bgcolor', 'field_display_listing_badge_color' ),
			'custom' => array( 'field_display_listing_json_custom' )
		);
		$displayJson    = $this->display_json;
		$displayDefault = $displayJson['display']['method'] ?? 'badge';
		$listingDefault = $displayJson['listing']['method'] ?? 'badge';
		$mediaDisplayDefault = $displayJson['display']['method'] ?? 'player';
		$mediaListingDefault = $displayJson['listing']['method'] ?? 'url';
		$mapDisplay = $displayJson['display']['map'] ?? FALSE;
		$mapListing = $displayJson['listing']['map'] ?? FALSE;
		$mapDisplayDims = $displayJson['display']['mapDims'] ?? array(200, 200);
		$mapListingDims = $displayJson['listing']['mapDims'] ?? array(100, 100);
		$listingOptions = array(
			'badge' => Theme::i()->getTemplate( 'records', 'cms', 'global' )->fieldBadge( Member::loggedIn()->language()->addToStack('cms_badge_label'), Member::loggedIn()->language()->addToStack('cms_badge_value'), 'ipsBadge--front ipsBadge--style1', $displayJson['listing']['bgcolor'] ?? null, $displayJson['listing']['color'] ?? null ),
			'simple' => Theme::i()->getTemplate( 'records', 'cms', 'global' )->fieldDefault( Member::loggedIn()->language()->addToStack('cms_badge_label'), Member::loggedIn()->language()->addToStack('cms_badge_value' ) ),
			'custom' => Member::loggedIn()->language()->addToStack('field_display_custom'),
			'none' => Member::loggedIn()->language()->addToStack('field_display_none')
		);

		$displayOptions = $listingOptions;
		$displayOptions['badge'] = Theme::i()->getTemplate( 'records', 'cms', 'global' )->fieldBadge( Member::loggedIn()->language()->addToStack('cms_badge_label'), Member::loggedIn()->language()->addToStack('cms_badge_value'), 'ipsBadge--front ipsBadge--style1', $displayJson['display']['bgcolor'] ?? null, $displayJson['display']['color'] ?? null );

		$form->addHeader( 'field_display_listing_header' );
		if ( ! $isTitleOrContent )
		{
			$form->add( new YesNo( 'field_display_listing', $this->id ? $this->display_listing : 1, FALSE, array(
				'togglesOn' => array('field_display_listing_json_badge', 'field_show_map_listing', 'media_display_listing_method' )
			), NULL, NULL, NULL, 'field_display_listing' ) );
		}

		$form->add( new Radio( 'field_display_listing_json_badge', $listingDefault, FALSE, array( 'options' => $listingOptions, 'toggles' => $listingToggles, 'parse' => 'raw' ), NULL, NULL, NULL, 'field_display_listing_json_badge' ) );

		$form->add( new Color( 'field_display_listing_badge_bgcolor', $displayJson['listing']['bgcolor'] ?? null, false, array( 'allowNone' => true, 'allowNoneLanguage' => 'field_display_badge_default' ), null, null, null, 'field_display_listing_badge_bgcolor' ) );
		$form->add( new Color( 'field_display_listing_badge_color', $displayJson['listing']['color'] ?? null, false, array( 'allowNone' => true, 'allowNoneLanguage' => 'field_display_badge_default' ), null, null, null, 'field_display_listing_badge_color' ) );

		$form->add( new YesNo( 'field_show_map_listing', $mapListing, FALSE, array(
			'togglesOn' => array( 'field_show_map_listing_dims' )
		), NULL, NULL, NULL, 'field_show_map_listing' ) );
		$form->add( new WidthHeight( 'field_show_map_listing_dims', $mapListingDims, FALSE, array( 'resizableDiv' => FALSE ), NULL, NULL, NULL, 'field_show_map_listing_dims' ) );

		$form->add( new Codemirror( 'field_display_listing_json_custom', ($displayJson['listing']['html'] ?? NULL), FALSE, array( 'placeholder' => '{label}: {value}', 'codeModeAllowedLanguages' => [ 'ipsphtml' ] ), function($val )
        {
            /* Test */
            try
            {
	            Theme::checkTemplateSyntax( $val );
            }
            catch( LogicException $e )
            {
	            throw new LogicException('cms_field_error_bad_syntax');
            }

        }, NULL, NULL, 'field_display_listing_json_custom' ) );

		/* Media listing */
		$mediaListingOptions = array( 'player' => 'media_display_as_player', 'url' => 'media_display_as_url' );
		$form->add( new Radio( 'media_display_listing_method', $mediaListingDefault, FALSE, array( 'options' => $mediaListingOptions ), NULL, NULL, NULL, 'media_display_listing_method' ) );

		if ( ! $isTitleOrContent )
		{
			$form->add( new Number( 'field_truncate', $this->id ? $this->truncate : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'field_truncate' ) );
		}

		$form->addHeader( 'field_display_display_header' );

		if ( ! $isTitleOrContent )
		{
			$form->add( new YesNo( 'field_display_display', $this->id ? $this->display_display : 1, FALSE, array(
				'togglesOn' => array( 'field_display_display_json_badge', 'field_show_map_display', 'field_display_display_json_where', 'media_display_display_method' )
			), NULL, NULL, NULL, 'field_display_display' ) );
		}

		$form->add( new Radio( 'field_display_display_json_badge', $displayDefault, FALSE, array( 'options' => $displayOptions, 'toggles' => $displayToggles, 'parse' => 'raw' ), NULL, NULL, NULL, 'field_display_display_json_badge' ) );

		$form->add( new Color( 'field_display_display_badge_bgcolor', $displayJson['display']['bgcolor'] ?? null, false, array( 'allowNone' => true, 'allowNoneLanguage' => 'field_display_badge_default' ), null, null, null, 'field_display_display_badge_bgcolor' ) );
		$form->add( new Color( 'field_display_display_badge_color', $displayJson['display']['color'] ?? null, false, array( 'allowNone' => true, 'allowNoneLanguage' => 'field_display_badge_default' ), null, null, null, 'field_display_display_badge_color' ) );

		$form->add( new YesNo( 'field_show_map_display', $mapDisplay, FALSE, array(
			'togglesOn' => array( 'field_show_map_display_dims' )
		), NULL, NULL, NULL, 'field_show_map_display' ) );
		$form->add( new WidthHeight( 'field_show_map_display_dims', $mapDisplayDims, FALSE, array( 'resizableDiv' => FALSE ), NULL, NULL, NULL, 'field_show_map_display_dims' ) );

		$form->add( new Codemirror( 'field_display_display_json_custom', ($displayJson['display']['html'] ?? NULL), FALSE, array( 'placeholder' => '{label}: {value}', 'codeModeAllowedLanguages' => [ 'ipsphtml' ] ), function($val )
        {
            /* Test */
            try
            {
	            Theme::checkTemplateSyntax( $val );
            }
            catch( LogicException $e )
            {
	            throw new LogicException('cms_field_error_bad_syntax');
            }

        }, NULL, NULL, 'field_display_display_json_custom' ) );

		/* Display where? */
		$form->add( new Radio( 'field_display_display_json_where', ($displayJson['display']['where'] ?? 'top'), FALSE, array( 'options' => array( 'top' => 'cms_field_display_top', 'bottom' => 'cms_field_display_bottom' ) ), NULL, NULL, NULL, 'field_display_display_json_where' ) );

		/* Media display */
		$form->add( new Radio( 'media_display_display_method', $mediaDisplayDefault, FALSE, array( 'options' => $mediaListingOptions ), NULL, NULL, NULL, 'media_display_display_method' ) );

		$form->addSeparator();

		$form->add( new YesNo( 'field_display_commentform', $this->id ? $this->display_commentform : 0, FALSE, array(), NULL, NULL, NULL, 'field_display_commentform' ) );

		/* Add form fields from extensions */
		foreach( Application::allExtensions( 'core', 'CustomField' ) as $ext )
		{
			/* @var CustomFieldAbstract $ext */
			if( $ext::isEnabled() )
			{
				$ext::form( $form, $this );
			}
		}

		Output::i()->globalControllers[]  = 'cms.admin.fields.form';
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_fields.js', 'cms' ) );

		Output::i()->title  = ( $this->id ) ? Member::loggedIn()->language()->addToStack('cms_edit_field', FALSE, array( 'sprintf' => array( $this->_title ) ) ) : Member::loggedIn()->language()->addToStack('cms_add_field');
	}

	/**
	 * @brief	Disable the copy button - useful when the forms are very distinctly different
	 */
	public bool $noCopyButton	= TRUE;

	/**
	 * @brief	Update the default value in records
	 */
	protected bool $_updateDefaultValue = FALSE;

	/**
	 * @brief	Stores the old default value after a change
	 */
	protected ?string $_oldDefaultValue = NULL;

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @todo	Separate out the need for `$this->save()` to be called by moving the database table field creation to postSaveForm()
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		static::$contentDatabaseTable = 'cms_custom_database_' . static::$customDatabaseId;

		$values['field_max_length'] = ( isset( $values['field_max_length'] ) ) ? intval( $values['field_max_length'] ) : 0;
		
		/* Work out the column definition */
		if( isset( $values['field_type'] ) )
		{
			$columnDefinition = array( 'name' => "field_{$this->id}" );
			switch ( $values['field_type'] )
			{
				case 'CheckboxSet':
				case 'Member':
				case 'Radio':
				case 'Select':
					/* Reformat keyValue pairs */
					if ( isset( $values['field_extra'] ) AND is_array( $values['field_extra'] ) )
					{
						$extra = array();
						foreach( $values['field_extra'] as $row )
						{
							if ( isset( $row['key'] ) )
							{
								$extra[ $row['key'] ] = $row['value'];
							}
						}

						if ( count( $extra ) )
						{
							$values['field_extra'] = $extra;
						}
					}
					if ( $values['field_type'] === 'Select' )
					{
						$columnDefinition['type'] = 'TEXT';
					}
					else
					{
						$columnDefinition['type']	= 'VARCHAR';
						$columnDefinition['length']	= 255;
					}
					if( $values['field_type'] != 'Member' )
					{
						if( isset( $values['field_option_other'] ) AND $values['field_option_other'] )
						{
							$values['field_format_opts_on'] = true;
							$values['field_format_opts'] = [
								'use_other' => true
							];
						}
						else
						{
							$values['field_format_opts'] = null;
						}
					}
					break;
				case 'Youtube':
				case 'Spotify':
				case 'Soundcloud':
					/* Reformat keyValue pairs */
					if ( isset( $values['media_params'] ) AND is_array( $values['media_params'] ) )
					{
						$extra = array();
						foreach( $values['media_params'] as $row )
						{
							if ( isset( $row['key'] ) )
							{
								$extra[ $row['key'] ] = $row['value'];
							}
						}

						if ( count( $extra ) )
						{
							$values['field_extra'] = $extra;
						}
					}
					$columnDefinition['type'] = 'TEXT';
					break;
				case 'Date':
					$columnDefinition['type'] = 'INT';
					$columnDefinition['length'] = 10;
					$values['field_extra'] = '';
					$values['default_value'] = ( isset( $values['default_value'] ) ) ? (int) $values['default_value'] : NULL;
					break;
				case 'Number':
					if ( isset( $values['field_number_decimals_on'] ) and $values['field_number_decimals_on'] and $values['field_number_decimals'] )
					{
						$columnDefinition['type'] = 'DECIMAL(20,' . $values['field_number_decimals'] . ')';
						$values['field_extra'] = '';
					}
					else
					{
						$columnDefinition['type'] = 'VARCHAR';
						$columnDefinition['length'] = 255;
						$values['field_extra'] = '';
						break;
					}
					break;
				case 'YesNo':
					$columnDefinition['type'] = 'INT';
					$columnDefinition['length'] = 10;
					$values['field_extra'] = '';
					break;
				
				case 'Address':
				case 'Codemirror':
				case 'Editor':
				case 'TextArea':
				case 'Upload':
					$columnDefinition['type'] = 'MEDIUMTEXT';
					$values['field_extra'] = '';
					break;
				
				case 'Email':
				case 'Password':
				case 'Tel':
				case 'Text':
				case 'Url':
				case 'Checkbox':
					if ( !isset( $values['field_max_length'] ) OR !$values['field_max_length'] )
					{
						$columnDefinition['type'] = 'MEDIUMTEXT';
						unset( $columnDefinition['length'] );
					}
					else
					{
						$columnDefinition['type'] = 'VARCHAR';
						$columnDefinition['length'] = 255;
					}

					$values['field_extra'] = '';
					break;
				default:
					$columnDefinition['type'] = 'TEXT';
					break;
			}

			/* Process values for extension */
			foreach( Application::allExtensions( 'core', 'CustomField' ) as $ext )
			{
				/* @var CustomFieldAbstract $ext */
				if( $ext::isEnabled() and $values['field_type'] == $ext::fieldType() )
				{
					$values = $ext::formatFormValues( $values );
					$columnDefinition['type'] = $ext::columnDefinition();
					break;
				}
			}
			
			if ( ! empty( $values['field_max_length'] ) )
			{
				if( $values['field_max_length'] > 255 )
				{
					$columnDefinition['type'] = 'MEDIUMTEXT';

					if( isset( $columnDefinition['length'] ) )
					{
						unset( $columnDefinition['length'] );
					}
				}
				else
				{
					$columnDefinition['length'] = $values['field_max_length'];
				}
			}
			else if ( empty( $columnDefinition['length'] ) )
			{
				$columnDefinition['length'] = NULL;
			}
		}

		if( isset( $values['media_params'] ) )
		{
			unset( $values['media_params'] );
		}

		/* Add/Update the content table */
		if ( !$this->id )
		{
			/* field key cannot be null, so we assign a temporary key here which is overwritten below */
			$this->key = md5( mt_rand() );
			$this->database_id = static::$customDatabaseId;
			$values['database_id']	= $this->database_id;
			
			$this->save();
			
			$columnDefinition['name'] = "field_{$this->id}";
			
			if ( isset( static::$contentDatabaseTable ) )
			{
				try
				{
					Db::i()->addColumn( static::$contentDatabaseTable, $columnDefinition );
				}
				catch( DbException $e )
				{
					if ( $e->getCode() === 1118 )
					{
						# 1118 is thrown when there are too many varchar columns in a single table. BLOBs and TEXT fields do not add to this limit
						$columnDefinition['length'] = NULL;
						$columnDefinition['type'] = 'TEXT';
						
						Db::i()->addColumn( static::$contentDatabaseTable, $columnDefinition );
					}
				}
				if ( ! empty( $values['field_filter'] ) and $values['field_type'] != 'Upload' )
				{
					try
					{
						if ( in_array( $columnDefinition['type'], array( 'TEXT', 'MEDIUMTEXT' ) ) )
						{
							Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => "field_{$this->id}", 'columns' => array( "field_{$this->id}" ) ) );
						}
						else
						{
							Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => "field_{$this->id}", 'columns' => array( "field_{$this->id}" ) ) );
						}
					}
					catch( DbException $e )
					{
						if ( $e->getCode() !== 1069 )
						{
							# 1069: MyISAM can only have 64 indexes per table. This should be really rare though so we silently ignore it.
							throw $e;
						}
					}
				}
			}
		}
		elseif( !$this->canKeepValueOnChange( $values['field_type'] ) )
		{
			try
			{
				/* Drop the index if it exists */
				if( Db::i()->checkForIndex( static::$contentDatabaseTable, "field_{$this->id}" ) )
				{
					Db::i()->dropIndex( static::$contentDatabaseTable, "field_{$this->id}" );
				}
				Db::i()->dropColumn( static::$contentDatabaseTable, "field_{$this->id}" );
			}
			catch ( DbException $e ) { }

			Db::i()->addColumn( static::$contentDatabaseTable, $columnDefinition );

			if ( $values['field_type'] != 'Upload' )
			{
				try
				{
					if ( in_array( $columnDefinition['type'], array( 'TEXT', 'MEDIUMTEXT' ) ) )
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => "field_{$this->id}", 'columns' => array( "field_{$this->id}" ) ) );
					}
					else
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => "field_{$this->id}", 'columns' => array( "field_{$this->id}" ) ) );
					}
				}
				catch( DbException $e )
				{
					if ( $e->getCode() !== 1069 )
					{
						# 1069: MyISAM can only have 64 indexes per table. This should be really rare though so we silently ignore it.
						throw $e;
					}
				}
			}
		}
		elseif ( isset( static::$contentDatabaseTable ) AND isset( $columnDefinition ) )
		{
			try
			{
				/* Drop the index if it exists */
				if( Db::i()->checkForIndex( static::$contentDatabaseTable, "field_{$this->id}" ) )
				{
					Db::i()->dropIndex( static::$contentDatabaseTable, "field_{$this->id}" );
				}
				Db::i()->changeColumn( static::$contentDatabaseTable, "field_{$this->id}", $columnDefinition );
			}
			catch ( DbException $e ) { }

			if ( $values['field_filter'] and $values['field_type'] != 'Upload' )
			{
				try
				{
					if ( in_array( $columnDefinition['type'], array( 'TEXT', 'MEDIUMTEXT' ) ) )
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => "field_{$this->id}", 'columns' => array( "field_{$this->id}" ) ) );
					}
					else
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => "field_{$this->id}", 'columns' => array( "field_{$this->id}" ) ) );
					}
				}
				catch( DbException $e )
				{
					if ( $e->getCode() !== 1069 )
					{
						# 1069: MyISAM can only have 64 indexes per table. This should be really rare though so we silently ignore it.
						throw $e;
					}
				}
			}
		}
		
		/* Save the name and desctipn */
		if( isset( $values['field_title'] ) )
		{
			Lang::saveCustom( 'cms', static::$langKey . '_' . $this->id, $values['field_title'] );
		}
		
		if ( isset( $values['field_description'] ) )
		{
			Lang::saveCustom( 'cms', static::$langKey . '_' . $this->id . '_desc', $values['field_description'] );
			unset( $values['field_description'] );
		}
		
		if ( array_key_exists( 'field_validator_error', $values ) )
		{
			Lang::saveCustom( 'cms', static::$langKey . '_' . $this->id . '_validation_error', $values['field_validator_error'] );
			unset( $values['field_validator_error'] );
		}

		if ( isset( $values['field_format_opts_on'] ) AND ! $values['field_format_opts_on'] )
		{
			$values['field_format_opts'] = NULL;
		}

		if ( isset( $values['field_key'] ) AND ! $values['field_key'] )
		{
			if ( is_array( $values['field_title'] ) )
			{
				/* We need to make sure the internal pointer on the array is on the first element */
				reset( $values['field_title'] );
				$values['field_key'] = Friendly::seoTitle( $values['field_title'][ key( $values['field_title'] ) ] );
			}
			else
			{
				$values['field_key'] = Friendly::seoTitle( $values['field_title'] );
			}

			/* Now test it */
			/* @var Fields $class */
			$class = '\IPS\cms\Fields' . Request::i()->database_id;

			try
			{
				$testField = $class::load( $this->key, 'field_key');

				/* It's taken... */
				if ( $this->id != $testField->id )
				{
					$this->key .= '_' . mt_rand();
				}
			}
			catch( OutOfRangeException $ex )
			{
				/* Doesn't exist? Good! */
			}
		}

		if( isset( $values['field_type'] ) AND ( !isset( $values['_skip_formatting'] ) OR $values['_skip_formatting'] !== TRUE ) )
		{
			$displayJson = array( 'display' => array( 'method' => NULL ), 'listing' => array( 'method' => NULL ) );

			/* Listing */
			if ( in_array( $values['field_type'], static::$mediaFields ) )
			{
				$displayJson['listing']['method'] = $values['media_display_listing_method'];
				$displayJson['display']['method'] = $values['media_display_display_method'];
			}
			else
			{
				if ( $values['field_type'] === 'Address' )
				{
					if ( isset( $values['field_show_map_listing'] ) )
					{
						$displayJson['listing']['map'] = (boolean) $values['field_show_map_listing'];
					}

					if ( isset( $values['field_show_map_listing_dims'] ) )
					{
						$displayJson['listing']['mapDims'] = $values['field_show_map_listing_dims'];
					}

					if ( isset( $values['field_show_map_display'] ) )
					{
						$displayJson['display']['map'] = (boolean) $values['field_show_map_display'];
					}

					if ( isset( $values['field_show_map_display_dims'] ) )
					{
						$displayJson['display']['mapDims'] = $values['field_show_map_display_dims'];
					}
				}

				if ( isset( $values['field_display_listing_json_badge'] ) )
				{
					if( isset( $values['field_display_listing_json_custom'] ) )
					{
						$displayJson['listing']['html'] = $values['field_display_listing_json_custom'];
						unset( $values['field_display_listing_json_custom'] );
					}
					else
					{
						$displayJson['listing']['html'] = NULL;
					}

					$displayJson['listing']['method'] = $values['field_display_listing_json_badge'];
					if ( $values['field_display_listing_json_badge'] != 'custom' )
					{
						if( $values['field_display_listing_json_badge'] == 'badge' )
						{
							$displayJson['listing']['bgcolor'] = $values['field_display_listing_badge_bgcolor'];
							$displayJson['listing']['color'] = $values['field_display_listing_badge_color'];
						}
					}
				}

				/* Display */
				if ( isset( $values['field_display_display_json_badge'] ) )
				{
					if( isset( $values['field_display_display_json_custom'] ) )
					{
						$displayJson['display']['html'] = $values['field_display_display_json_custom'];
						unset( $values['field_display_display_json_custom'] );
					}
					else
					{
						$displayJson['display']['html'] = NULL;
					}

					$displayJson['display']['method'] = $values['field_display_display_json_badge'];
					if ( $values['field_display_display_json_badge'] == 'badge' )
					{
						$displayJson['display']['bgcolor'] = $values['field_display_display_badge_bgcolor'];
						$displayJson['display']['color'] = $values['field_display_display_badge_color'];
					}

					if ( isset( $values['field_display_display_json_where'] ) )
					{
						$displayJson['display']['where'] = $values['field_display_display_json_where'];
					}
				}
			}

			$values['display_json'] = json_encode( $displayJson );
		}

		/* If we are importing a database we skip the json formatting as it gets set after */
		if( array_key_exists( '_skip_formatting', $values ) )
		{
			unset( $values['_skip_formatting'] );
		}

		/* Special upload stuffs */
		if ( isset( $values['field_type'] ) AND $values['field_type'] === 'Upload' )
		{
			if ( isset( $values['field_upload_is_image'] ) and $values['field_upload_is_image'] === 'yes')
			{
				$values['extra'] = array( 'type' => 'image', 'maxsize' => $values['field_image_size'] );
				
				if ( $values['field_upload_thumb'][0] > 0 )
				{
					$values['extra']['thumbsize'] = $values['field_upload_thumb'];
				}
			}
			else
			{
				$values['extra'] = array( 'type' => 'any' );
			}

			if ( isset( $values['field_upload_is_multiple'] ) and $values['field_upload_is_multiple'] )
			{
				$values['field_is_multiple'] = 1;
			}
			else
			{
				$values['field_is_multiple'] = 0;
			}

			$values['field_default_value'] = NULL;
		}

		/* Special date stuff */
		if ( isset( $values['field_type'] ) AND $values['field_type'] === 'Date' )
		{
			$values['extra'] = array();
			if ( isset( $values['field_date_time_override'] ) )
			{
				if ( $values['field_date_time_override'] === 'set' )
				{
					$values['extra']['timezone'] = $values['field_date_timezone'];
				}
				else
				{
					$values['extra']['timezone'] = $values['field_date_time_override'];
				}
			}
			
			if ( isset( $values['field_date_time_time'] ) )
			{
				$values['extra']['time'] = $values['field_date_time_time'];
			}
		}

		/* Special relational stuff */
		if ( isset( $values['field_type'] ) AND $values['field_type'] === 'Item' )
		{
			if ( array_key_exists( 'field_relational_db', $values ) and empty( $values['field_relational_db'] ) )
			{
				throw new LogicException( Member::loggedIn()->language()->addToStack('cms_relational_field_no_db_selected') );
			}
			
			if ( isset( $values['field_relational_db'] ) )
			{
				$values['extra'] = array( 'database' => $values['field_relational_db'] );
			}
			
			if ( array_key_exists( 'field_crosslink', $values ) and ! empty( $values['field_crosslink'] ) )
			{
				$values['extra']['crosslink'] = (boolean) $values['field_crosslink'];
			}
			
			/* Best remove the stored data incase the crosslink setting changed */
			unset( Store::i()->database_reciprocal_links );
		}
		
		/* Special number stuff */
		if ( isset( $values['field_type'] ) AND $values['field_type'] === 'Number' AND ( isset( $values['field_number_decimals_on'] ) or isset( $values['field_number_min'] ) or isset( $values['field_number_max'] ) ) )
		{
			$values['extra'] = array( 'on' => (boolean) $values['field_number_decimals_on'], 'places' => $values['field_number_decimals'], 'min' => $values['field_number_min'], 'max' => $values['field_number_max']  );
		}
		
		/* Remove the filter flag if this field cannot be filtered */
		if ( isset( $values['field_type'] ) AND isset( $values['field_filter'] ) AND $values['field_filter'] and ! in_array( $values['field_type'], static::$filterableFields ) )
		{
			$values['field_filter'] = false;
		}
		
		if ( ! $this->new AND isset( $values['field_default_update_existing'] ) AND $values['field_default_update_existing'] AND $values['field_default_value'] !== $this->default_value )
		{
			$this->_updateDefaultValue = TRUE;
			$this->_oldDefaultValue    = $this->default_value;
		}

		foreach( array( 'field_crosslink', 'field_number_decimals_on', 'field_number_decimals', 'field_number_min', 'field_number_max', 'field_format_opts_on', 'field_relational_db', 'field_upload_is_multiple', 'field_default_update_existing', 'field_date_time_override', 'field_date_timezone', 'field_date_time_time', 'field_upload_is_image', 'field_image_size', 'field_upload_thumb', 'field_title', 'field_display_display_json_badge', 'field_display_display_json_custom', 'field_display_listing_json_badge', 'field_display_listing_json_custom', 'media_display_listing_method', 'media_display_display_method', 'field_show_map_listing', 'field_show_map_listing_dims', 'field_show_map_display', 'field_show_map_display_dims', 'field_display_display_json_where', 'field_option_other', 'field_display_listing_badge_bgcolor', 'field_display_listing_badge_color', 'field_display_display_badge_bgcolor', 'field_display_display_badge_color' ) as $field )
		{
			if ( array_key_exists( $field, $values ) )
			{
				unset( $values[ $field ] );
			}
		}

		return parent::formatFormValues( $values );
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		/* Ensure it has some permissions */
		$this->permissions();

		if ( $this->_updateDefaultValue )
		{
			static::$contentDatabaseTable = 'cms_custom_database_' . static::$customDatabaseId;

			$field = 'field_' . $this->id;
			Db::i()->update( static::$contentDatabaseTable, array( $field => $this->default_value ), array( $field . '=?  OR ' . $field . ' IS NULL', $this->_oldDefaultValue ) );
		}

		parent::postSaveForm( $values );
	}

	/**
	 * Does the change mean wiping the value?
	 *
	 * @param string $newType The new type
	 * @return bool
	 */
	protected function canKeepValueOnChange( string $newType ): bool
	{
		$custom = array( 'Youtube', 'Spotify', 'Soundcloud', 'Relational' );

		if ( ! in_array( $this->type, $custom ) )
		{
			return parent::canKeepValueOnChange( $newType );
		}

		switch ( $this->type )
		{
			case 'Youtube':
				return in_array( $newType, array( 'Youtube', 'Text', 'TextArea' ) );

			case 'Spotify':
				return in_array( $newType, array( 'Spotify', 'Text', 'TextArea' ) );

			case 'Soundcloud':
				return in_array( $newType, array( 'Soundcloud', 'Text', 'TextArea' ) );
		}

		return FALSE;
	}

	/**
	 * [ActiveRecord] Save Record
	 *
	 * @return    void
	 */
	public function save(): void
	{
		static::$contentDatabaseTable = 'cms_custom_database_' . static::$customDatabaseId;
		static::$cache = array();

		$functionName = $this->fieldTemplateName('listing');

		if ( isset( Store::i()->$functionName ) )
		{
			unset( Store::i()->$functionName );
		}

		$functionName = $this->fieldTemplateName('display');

		if ( isset( Store::i()->$functionName ) )
		{
			unset( Store::i()->$functionName );
		}

		parent::save();
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @param bool $skipDrop	Skip dropping the column/index, useful when we are deleting the entire table
	 * @return    void
	 */
	public function delete( bool $skipDrop=FALSE ): void
	{
		static::$contentDatabaseTable = ''; // This ensures the parent class doesn't try to drop the column regardless
		static::$cache = array();

		/* Remove reciprocal map data */
		Db::i()->delete( 'cms_database_fields_reciprocal_map', array( 'map_origin_database_id=? AND map_field_id=? ', static::$customDatabaseId, $this->id ) );

		parent::delete();

		if( $skipDrop === TRUE )
		{
			return;
		}
		
		if( $this->type == 'Upload' )
		{
			/* Delete thumbnails */
			Task::queue( 'core', 'FileCleanup', array(
				'table'				=> 'cms_database_fields_thumbnails',
				'column'			=> 'thumb_location',
				'storageExtension'	=> 'cms_Records',
				'where'				=> array( array( 'thumb_field_id=?', $this->id ) ),
				'deleteRows'		=> TRUE,
			), 4 );

			/* Delete records */
			Task::queue( 'core', 'FileCleanup', array(
				'table'				=> 'cms_custom_database_' . static::$customDatabaseId,
				'column'			=> 'field_' . $this->id,
				'storageExtension'	=> 'cms_Records',
				'primaryId'			=> 'primary_id_field',
				'dropColumn'		=> 'field_' . $this->id,
				'dropColumnTable'	=> 'cms_custom_database_' . static::$customDatabaseId,
				'multipleFiles'		=> $this->is_multiple
			), 4 );
		}
		else
		{
			try
			{
				Db::i()->dropColumn( 'cms_custom_database_' . static::$customDatabaseId, "field_{$this->id}" );
			}
			catch( DbException $e ) { }
		}
		
		Lang::deleteCustom( 'cms', "content_field_{$this->id}_desc" );
		Lang::deleteCustom( 'cms', "content_field_{$this->id}_validation_error" );
	}

	/**
	 * Build Form Helper
	 *
	 * @param mixed|null $value The value
	 * @param callback|null $customValidationCode Custom validation code
	 * @param Content|NULL $content The associated content, if editing
	 * @param int $flags
	 * @return Text|FormAbstract
	 */
	public function buildHelper( mixed $value=NULL, callable $customValidationCode=NULL, Content $content = NULL, int $flags=0 ): Text|FormAbstract
	{
		if( $extension = $this->extension() )
		{
			$class = $extension::formClass();
			$options = $extension::formHelperOptions( $this );
		}
		elseif ( class_exists( '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $this->type ) ) )
		{
			/* Is special! */
			$class = '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $this->type );
		}
		else if ( class_exists( '\IPS\Helpers\Form\\' . IPS::mb_ucfirst( $this->type ) ) )
		{
			$class = '\IPS\Helpers\Form\\' . IPS::mb_ucfirst( $this->type );

			$options = $this->extra;
			if( method_exists( $class, 'formatOptions' ) )
			{
				$options = $class->formatOptions( $options );
			}
		}
		else
		{
			/* Fail safe */
			$this->type = 'Text';
			$class = '\IPS\Helpers\Form\Text';
		}

		$options    = array();
		switch ( IPS::mb_ucfirst( $this->type ) )
		{
			case 'Editor':
				$options['app']         = 'cms';
				$options['key']         = 'Records' . static::$customDatabaseId;
				$options['allowAttachments'] = $this->allow_attachments;
				$options['autoSaveKey'] = 'RecordField_' . ( ( $content === NULL OR !$content->_id ) ? 'new' : $content->_id ) . '_' . $this->id;
				$options['attachIds']   = ( $content === NULL OR !$content->_id ) ? NULL : array( $content->_id, $this->id,  static::$customDatabaseId );
				break;
			case 'Email':
			case 'Password':
			case 'Tel':
			case 'Text':
			case 'TextArea':
			case 'Url':
				$options['maxLength']	= $this->max_length ?: NULL;
				$options['regex']		= $this->input_format ?: NULL;
				break;
			case 'Upload':
				$options['storageExtension'] = static::$uploadStorageExtension;
				$options['canBeModerated'] = static::$uploadsCanBeModerated;

				if ( isset( $this->extra['type'] ) )
				{
					if ( $this->extra['type'] === 'image' )
					{
						$options['allowStockPhotos'] = TRUE;
						$options['image'] = array( 'maxWidth' => $this->extra['maxsize'][0] ?: NULL, 'maxHeight' => $this->extra['maxsize'][1] ?: NULL );
					}
					else
					{
						$options['allowedFileTypes'] = $this->allowed_extensions ?: NULL;
					}
				}
				else
				{
					$options['allowedFileTypes'] = $this->allowed_extensions ?: NULL;
				}

				if ( $this->is_multiple )
				{
					$options['multiple'] = TRUE;
				}

				if( $value and ! is_array( $value ) )
				{
					if ( mb_strstr( $value, ',' ) )
					{
						$files = explode( ',', $value );

						$return = array();
						foreach( $files as $file )
						{
							try
							{
								$return[] = File::get( static::$uploadStorageExtension, $file );
							}
							catch ( OutOfRangeException $e ) { }
						}

						$value = $return;
					}
					else
					{
						try
						{
							$value = array( File::get( static::$uploadStorageExtension, $value ) );
						}
						catch ( OutOfRangeException $e )
						{
							$value = NULL;
						}
					}
				}
				break;
			case 'Select':
			case 'CheckboxSet':
				$options['multiple'] = ( IPS::mb_ucfirst( $this->type ) == 'CheckboxSet' ) ? TRUE : $this->is_multiple;
				
				if ( $flags & self::FIELD_DISPLAY_FILTERS or ( ! $this->default_value and ! $this->required ) )
				{
					$options['noDefault'] = true;
				}

				if ( $flags & self::FIELD_DISPLAY_COMMENTFORM )
				{
					$options['noDefault'] = true;
					$this->required       = false;
				}

				if ( $options['multiple'] and ! is_array( $value ) )
				{
					$exp   = $value ? explode( ',', $value ) : [];
					$value = array();
					foreach( $exp as $val )
					{
						if ( is_numeric( $val ) and intval( $val ) == $val )
						{
							$value[] = intval( $val );
						}
						else
						{
							$value[] = $val;
						}
					}
				}
				else
				{
					if ( is_numeric( $value ) and intval( $value ) == $value )
					{
						$value = intval( $value );
					}
				}

				$json = $this->extra;
				$options['options'] = ( $json ) ?: array();

				if( isset( $this->format_opts['use_other'] ) AND $this->format_opts['use_other'] and !( $flags & self::FIELD_DISPLAY_FILTERS ) )
				{
					$userSuppliedInput = 'content_field_other_' . $this->id;
					$options['options'][$userSuppliedInput] = Member::loggedIn()->language()->get( 'field_opt_other' );
					$options['userSuppliedInput'] = $userSuppliedInput;
					$options['toggles'][ $userSuppliedInput ] = [ $userSuppliedInput ];
				}

				break;
			case 'Radio':
				$json = $this->extra;
				$options['options'] = ( $json ) ?: array();
				if( isset( $this->format_opts['use_other'] ) AND $this->format_opts['use_other'] )
				{
					$options['options']['content_field_other_' . $this->id] = Member::loggedIn()->language()->get( 'field_opt_other' );
					$options['userSuppliedInput'] = 'content_field_other_' . $this->id;
				}
				$options['multiple'] = FALSE;
				break;
			case 'Address':
				$value = GeoLocation::buildFromJson( $value );
				break;
			
			case 'Member':
				if ( ! $value )
				{
					$value = NULL;
				}
				
				$options['multiple'] = $this->is_multiple ? NULL : 1;
				
				if ( is_string( $value ) )
				{
					$value = array_map( function( $id )
					{
						return Member::load( intval( $id ) );
					}, explode( "\n", $value ) );
				}
				
				break;
			case 'Date':
				if ( is_numeric( $value ) )
				{
					/* We want to normalize based on user time zone here */
					$value = DateTime::ts( $value );
				}
				
				if ( isset( $this->extra['timezone'] ) and $this->extra['timezone'] )
				{
					/* The timezone is already set to user by virtue of DateTime::ts() */
					if ( $this->extra['timezone'] != 'user' )
					{
						$options['timezone'] = new DateTimeZone( $this->extra['timezone'] );
						
						if ( $value instanceof DateTime )
						{
							$value->setTimezone( $options['timezone'] );
						}
					}
				}
				/* If we haven't specified a timezone, default back to UTC to normalize the date, so a date of 5/6/2016 doesn't become 5/5/2016 depending
					on who submits and who views */
				else
				{
					$options['timezone'] = new DateTimeZone( 'UTC' );

					if ( $value instanceof DateTime )
					{
						$value->setTimezone( $options['timezone'] );
					}
				}
				
				if ( $this->extra['time'] )
				{
					$options['time'] = true;
				}
				break;
			case 'Item':
				$options['maxItems'] = ( $this->is_multiple ) ? NULL : 1;
				$options['class']    = '\IPS\cms\Records' . $this->extra['database'];
				break;
			case 'Number':
				if ( $this->extra['on'] and $this->extra['places'] )
				{
					$options['decimals'] = $this->extra['places'];
				}

				if ( isset( $this->extra['min'] ) and $this->extra['min'] )
				{
					$options['min'] = $this->extra['min'];
				}

				if ( isset( $this->extra['max'] ) and $this->extra['max'])
				{
					$options['max'] = $this->extra['max'];
				}

				if( !$this->required )
				{
					$options['unlimited'] 		= '';
					$options['unlimitedLang']	= 'cms_number_none';
				}
				break;
		}

		if( $this->toggles )
		{
			$toggles = json_decode( $this->toggles, true );
			if( is_array( $toggles ) AND count( $toggles ) )
			{
				foreach( $toggles as $k => $v )
				{
					foreach( $v as $_k => $_v )
					{
						$toggles[$k][$_k] = 'content_field_' . $_v;
					}
				}

				if( $this->type == 'YesNo' OR $this->type == 'Checkbox' )
				{
					$options = array_merge( $options, $toggles );
				}
				else
				{
					$options['toggles'] = $toggles;
				}
			}
		}

		if ( $this->validator AND $this->validator_custom )
		{
			switch( IPS::mb_ucfirst( $this->type ) )
			{
				case 'Text':
				case 'TextArea':
					if ( $this->unique )
					{
						$field = $this;
						$customValidationCode = function( $val ) use ( $field, $content )
						{
							call_user_func_array( 'IPS\cms\Fields' . static::$customDatabaseId . '::validateUnique', array( $val, $field, $content ) );
							
							return call_user_func( 'IPS\cms\Fields' . static::$customDatabaseId . '::validateInput_' . $field->id, $val );
						};
					}
					else
					{
						$customValidationCode = 'IPS\cms\Fields' . static::$customDatabaseId . '::validateInput_' . $this->id;
					}
				break;
			}
		}

		/* If the field is required, but can be toggled, change this to null */
		if( $this->required AND in_array( $this->id, static::getToggledFieldIds() ) )
		{
			$this->required = null;
		}

		return new $class( 'content_field_' . $this->id, $value, $this->required, $options, $customValidationCode, null, null, 'content_field_' . $this->id );
	}

	/**
	 * @var array|null
	 */
	protected static ?array $_cachedToggleIds = null;

	/**
	 * Returns all field IDs that might be toggled by another field
	 *
	 * @return array
	 */
	protected static function getToggledFieldIds() : array
	{
		if( static::$_cachedToggleIds === null )
		{
			$ids = [];
			foreach( static::roots( null ) as $field )
			{
				if( $field->toggles AND $toggles = json_decode( $field->toggles, true ) )
				{
					foreach( $toggles as $k => $v )
					{
						$ids = array_merge( $ids, $v );
					}
				}
			}
			static::$_cachedToggleIds = array_unique( $ids );
		}

		return static::$_cachedToggleIds;
	}

	
	/**
	 * Get output for API
	 *
	 * @param			Member|NULL		$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return			array
	 * @apiresponse		int					id					ID number
	 * @apiresponse		string				title				Title
	 * @apiresponse		string|null			description			Description
	 * @apiresponse		string				type				The field type - e.g. "Text", "Editor", "Radio"
	 * @apiresponse		string|null			default				The default value
	 * @apiresponse		bool					required			If the field is required
	 * @apiresponse		object|null			options				If the field has certain options (for example, it is a select field), the possible values
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'id'			=> $this->_id,
			'title'			=> $this->_title,
			'description'	=> $this->_description ?: NULL,
			'type'			=> $this->type,
			'default'		=> $this->default_value ?: NULL,
			'required'		=> $this->required,
			'options'		=> $this->extra
		);
	}
}