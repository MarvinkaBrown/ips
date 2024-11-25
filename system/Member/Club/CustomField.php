<?php
/**
 * @brief		Clubs Customer Field Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		3 Mar 2017
 */

namespace IPS\Member\Club;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ArrayIterator;
use IPS\CustomField as SystemCustomField;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\YesNo;
use IPS\Node\Permissions;
use IPS\Patterns\ActiveRecordIterator;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Clubs Customer Field Node
 */
class CustomField extends SystemCustomField implements Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_clubs_fields';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'f_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static string $langKey = 'core_clubfield';
	
	/**
	 * @brief	[CustomField] Content database table
	 */
	protected static string $contentDatabaseTable = 'core_clubs_fieldvalues';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'clubs',
		'all'		=> 'fields_manage'
	);
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'clubs_custom_fields';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'core_clubfield_';
	
	/**
	 * @brief	[CustomField] Column Map
	 */
	public static array $databaseColumnMap = array(
		'content'	=> 'extra',
		'not_null'	=> 'required',
	);
	
	/**
	 * @brief	[CustomField] Additional Field Toggles
	 */
	public static array $additionalFieldToggles = array(
		'Checkbox'		=> array( 'f_filterable' ),
		'CheckboxSet'	=> array( 'f_filterable' ),
		'Radio'			=> array( 'f_filterable' ),
		'Select'		=> array( 'f_filterable' ),
		'YesNo'			=> array( 'f_filterable' ),
	);
	
	/**
	 * @brief	[CustomField] Editor Options
	 */
	public static array $editorOptions = array( 'app' => 'core', 'key' => 'Clubs' );
	
	/**
	 * @brief	[CustomField] Upload Storage Extension
	 */
	public static string $uploadStorageExtension = 'core_ClubField';

	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
		'view' 				=> 'view',
		'edit'				=> 2
	);

	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'core';

	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'clubfields';

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_club_field_';

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
	 * Get fields
	 *
	 * @return	array|ActiveRecordIterator
	 */
	public static function fields(): ActiveRecordIterator|array
	{
		if ( !isset( Store::i()->clubFields ) )
		{		
			$fields = array();
			$filterable = FALSE;
			
			foreach ( Db::i()->select( '*', 'core_clubs_fields', NULL, 'f_position' ) as $row )
			{
				$fields[ $row['f_id'] ] = $row;
				if ( $row['f_filterable'] and in_array( $row['f_type'], array( 'Checkbox', 'CheckboxSet', 'Radio', 'Select', 'YesNo' ) ) )
				{
					$filterable = TRUE;
				}
			}
				
			Store::i()->clubFields = array( 'fields' => $fields, 'filterable' => $filterable );
		}
		
		return new ActiveRecordIterator( new ArrayIterator( Store::i()->clubFields['fields'] ), 'IPS\Member\Club\CustomField' );
	}
	
	/**
	 * Get if there are any filterable fields
	 *
	 * @return	bool
	 */
	public static function areFilterableFields(): bool
	{
		if ( !isset( Store::i()->clubFields ) )
		{
			static::fields();
		}
		return Store::i()->clubFields['filterable'];
	}
			
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		parent::form( $form );
		
		$form->add( new YesNo( 'f_filterable', (bool) $this->filterable, FALSE, array(), NULL, NULL, NULL, 'f_filterable' ) );

		unset( $form->elements[''][1] );
		unset( $form->elements['']['pf_max_input'] );
		unset( $form->elements['']['pf_input_format'] );
		unset( $form->elements[''][2] );
		unset( $form->elements['']['pf_search_type'] );
		unset( $form->elements['']['pf_search_type_on_off'] );
		unset( $form->elements['']['pf_format'] );
	}
	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'clubFields' );

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( array_key_exists( 'pf_allow_attachments', $values ) )
		{
			$values[ 'allow_attachments' ] = $values[ 'pf_allow_attachments' ];
			unset( $values[ 'pf_allow_attachments' ] );
		}

		return parent::formatFormValues( $values );
	}
}