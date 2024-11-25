<?php
/**
 * @brief		Custom Customer Field Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		16 Apr 2013
 */

namespace IPS\nexus\Customer;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\CustomField as SystemCustomField;
use IPS\Helpers\Form;
use IPS\Helpers\Form\YesNo;
use IPS\Login;
use IPS\Member\ProfileStep;
use IPS\Settings;
use IPS\Widget;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom Profile Field Node
 */
class CustomField extends SystemCustomField
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_customer_fields';
	
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
	protected static string $langKey = 'nexus_ccfield';
	
	/**
	 * @brief	[CustomField] Content database table
	 */
	protected static string $contentDatabaseTable = 'nexus_customers';
	
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
		'app'		=> 'nexus',
		'module'	=> 'customers',
		'prefix'	=> 'customer_fields_'
	);
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'nexus_customer_fields';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_ccfield_';
	
	/**
	 * @brief	[CustomField] Column Map
	 */
	public static array $databaseColumnMap = array(
		'content'	=> 'extra',
		'not_null'	=> 'reg_require'
	);
	
	/**
	 * @brief	[CustomField] Editor Options
	 */
	public static array $editorOptions = array( 'app' => 'nexus', 'key' => 'Customer' );
	
	/**
	 * @brief	[CustomField] Upload Storage Extension
	 */
	public static string $uploadStorageExtension = 'nexus_Customer';
			
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		parent::form( $form );
		
		if ( Login::registrationType() == 'full' )
		{
			/* Quick register is disabled */
			$form->addHeader( 'customer_field_registration' );
			$form->add( new YesNo( 'f_reg_show', $this->reg_show ) );
			$form->add( new YesNo( 'f_reg_require', $this->reg_require ) );
		}
		
		$form->addHeader( 'customer_field_purchase' );
		$form->add( new YesNo( 'f_purchase_show', $this->purchase_show ) );
		$form->add( new YesNo( 'f_purchase_require', $this->purchase_require ) );

		unset( $form->elements[''][1] );
		unset( $form->elements['']['pf_not_null'] );
		unset( $form->elements['']['pf_max_input'] );
		unset( $form->elements['']['pf_input_format'] );
		unset( $form->elements[''][2] );
		unset( $form->elements['']['pf_search_type'] );
		unset( $form->elements['']['pf_search_type_on_off'] );
		unset( $form->elements['']['pf_format'] );
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		if ( !$this->column )
		{
			$this->column = "field_{$this->id}";
			$this->save();
		}

		parent::postSaveForm( $values );
	}
	
	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		Widget::deleteCaches( 'donations', 'nexus' );
	}
	
	/**
	 * [ActiveRecord] Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();

		/* Do we need to do stuff with profile steps? */
		ProfileStep::resync();
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$values['allow_attachments']	= $values['pf_allow_attachments'];
		unset( $values['pf_allow_attachments'] );

		return parent::formatFormValues( $values );
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		parent::__clone();

		$this->column = "field_{$this->id}";
		$this->save();
	}
}