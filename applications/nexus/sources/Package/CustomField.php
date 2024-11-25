<?php
/**
 * @brief		Custom Package Field Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		1 May 2013
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\CustomField as SystemCustomField;
use IPS\Helpers\Form;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Ftp;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\nexus\Package;
use IPS\Text\Encrypt;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;

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
	public static ?string $databaseTable = 'nexus_package_fields';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'cf_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static string $langKey = 'nexus_pfield';
	
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
		'module'	=> 'store',
		'prefix'	=> 'package_fields_',
	);
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'custom_package_fields';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_pfield_';
	
	/**
	 * @brief	[CustomField] Column Map
	 */
	public static array $databaseColumnMap = array(
		'content'	=> 'extra',
		'not_null'	=> 'required'
	);
	
	/**
	 * @brief	[CustomField] Additional Field Classes
	 */
	public static array $additionalFieldTypes = array(
		'UserPass'	=> 'sf_type_UserPass',
		'Ftp'		=> 'sf_type_Ftp'
	);
	
	/**
	 * @brief	[CustomField] Upload Field Storage Extension
	 */
	public static string $uploadStorageExtension = 'nexus_PurchaseFields';
	
	/**
	 * @brief	[CustomField] Editor Options
	 */
	public static array $editorOptions = array( 'app' => 'nexus', 'key' => 'Purchases' );
	
	/**
	 * @brief	[CustomField] Additional Field Toggles
	 */
	public static array $additionalFieldToggles = array(
		'Address'		=> array( 'cf_sticky' ),
		'Checkbox'		=> array( 'cf_sticky' ),
		'CheckboxSet'	=> array( 'cf_sticky' ),
		'Date'			=> array( 'cf_sticky' ),
		'Email'			=> array( 'cf_sticky' ),
		'Number'		=> array( 'cf_sticky' ),
		'Password'		=> array( 'cf_sticky' ),
		'Radio'			=> array( 'cf_sticky' ),
		'Text'			=> array( 'cf_sticky' ),
		'Url'			=> array( 'cf_sticky' ),
	);
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		parent::form( $form );

		$packages = array();
		foreach ( array_filter( explode( ',', $this->packages ) ) as $id )
		{
			try
			{
				$packages[] = Package::load( $id );
			}
			catch ( OutOfRangeException ) { }
		}
		$form->add( new Node( 'cf_packages', $packages, FALSE, array( 'class' => 'IPS\nexus\Package\Group', 'noParentNodes' => 'custom_packages', 'multiple' => TRUE, 'permissionCheck' => function( $node )
		{
			return !( $node instanceof Group);
		} ) ), 'pf_desc' );
		$form->add( new YesNo( 'cf_sticky', $this->sticky ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'cf_sticky' ), 'pf_type' );
		$form->add( new YesNo( 'cf_allow_attachments', $this->id ? $this->allow_attachments : 1, FALSE, array( ), NULL, NULL, NULL, 'pf_allow_attachments' ) );
		
		$form->addHeader( 'display_settings' );
		$form->add( new YesNo( 'cf_purchase', $this->purchase, FALSE ) );
		$form->add( new YesNo( 'cf_editable', $this->editable, FALSE ) );
		$form->add( new YesNo( 'cf_required', $this->required ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'pf_not_null' ) );
		$form->add( new YesNo( 'cf_invoice', $this->invoice ) );
		
		unset( $form->elements[''][1] );	
		unset( $form->elements['']['pf_not_null'] );
		unset( $form->elements['']['pf_max_input'] );
		unset( $form->elements['']['pf_input_format'] );		
		unset( $form->elements[''][2] );
		unset( $form->elements['']['pf_search_type'] );
		unset( $form->elements['']['pf_search_type_on_off'] );
		unset( $form->elements['']['pf_format'] );
		unset( $form->elements['']['pf_allow_attachments'] );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['cf_packages'] ) AND is_array( $values['cf_packages'] ) )
		{
			$values['packages'] = implode( ',', array_map( function( $node ){ return $node->id; }, $values['cf_packages'] ) );
			unset( $values['cf_packages'] );
		}

		/*
		 * Make sure that the multiple field is set to 0 for the Radio field, otherwise the package package_stock_adjustments will fail..
		 * it seems that people changed a select field often to a radio field, which caused that the field was missing in the stock adjustment
		 */
		if( isset( $values['pf_type'] ) and $values['pf_type'] == 'Radio' )
		{
			$values['pf_multiple'] = 0;
		}

		/* Disable 'sticky' for fields that don't support it */
		if( !in_array( 'cf_sticky', static::$additionalFieldToggles[ $values['pf_type'] ] ?? [] ) )
		{
			$values['cf_sticky'] = false;
		}

		return parent::formatFormValues( $values );
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
	public function buildHelper( mixed $value=NULL, callable $customValidationCode=NULL, ?Content $content = NULL, int $flags=0 ): Text|FormAbstract
	{
		if ( $this->type === 'UserPass' )
		{
			$class = 'IPS\nexus\Form\\' . $this->type;
			return new $class( static::$langKey . '_' . $this->id, $value, $this->not_null, array(), NULL, NULL, NULL, static::$langKey . '_' . $this->id );
		}
		elseif ( $this->type === 'Ftp' )
		{
			return new Ftp( static::$langKey . '_' . $this->id, $value, $this->not_null, array( 'validate' => $this->validate ), NULL, NULL, NULL, static::$langKey . '_' . $this->id );
		}
		
		return parent::buildHelper( $value, $customValidationCode, $content );
	}
	
	/**
	 * Display Value
	 *
	 * @param	mixed	$value						The value
	 * @param	bool	$showSensitiveInformation	If TRUE, potentially sensitive data (like passwords) will be displayed - otherwise will be blanked out
	 * @param	string|null	$separator					Used to separate items when displaying a field with multiple values.
	 * @return	string|null
	 */
	public function displayValue( mixed $value=NULL, bool $showSensitiveInformation=FALSE, ?string $separator=NULL ): ?string
	{
		if ( $this->type === 'UserPass' )
		{
			if ( !is_array( $value ) )
			{
				$value = json_decode( Encrypt::fromTag( $value )->decrypt(), TRUE );
			}
			
			if ( !$showSensitiveInformation )
			{
				$value['pw'] = '********';
			}
			
			return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->usernamePasswordDisplay( $value );
		}

		/* Select boxes store the value and not the index, but the parent method uses the index to determine the value.
		When using numeric options, this causes the wrong value to be returned. */
		if ( $this->type == 'Select' )
		{
			if ( $this->multiple )
			{
				return implode( ( $separator ) ?: '<br>', explode( ',', htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', false ) ) );
			}

			return htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', false );
		}
		
		return parent::displayValue( $value, $showSensitiveInformation );
	}
}