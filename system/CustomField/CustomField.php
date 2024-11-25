<?php

/**
 * @brief		Custom Field Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Apr 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use DomainException;
use IPS\Extensions\CustomFieldAbstract;
use IPS\Db\Exception;
use IPS\Helpers\Form;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Text\Encrypt;
use OutOfRangeException;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom Field Node
 */
abstract class CustomField extends Model
{
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static string $langKey;

	/**
	 * @brief	[CustomField] Column Map
	 */
	public static array $databaseColumnMap = array(
		'not_null'	=> 'not_null'
	);
	
	/**
	 * @brief	[CustomField] Additional Field Classes
	 */
	public static array $additionalFieldTypes = array();
	
	/**
	 * @brief	[CustomField] Additional Field Toggles
	 */
	public static array $additionalFieldToggles = array();

	/**
	 * @brief   [CustomField] An array of the 'keys' of the Field types toggles that shouldn't be an option. The main use is for excluding polls
	 */
	public static array $disabledFieldTypes = array();
	
	/**
	 * @brief	[CustomField] Set to TRUE if uploads fields are capable of holding the submitted content for moderation
	 */
	public static bool $uploadsCanBeModerated = FALSE;

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;
	
	/**
	 * Get
	 *
	 * @param mixed $key	Key
	 * @return	mixed	$value	Value
	 */
	public function __get( mixed $key )
	{
		if ( isset( static::$databaseColumnMap[ $key ] ) )
		{
			$key = static::$databaseColumnMap[ $key ];
		}
		
		return parent::__get( $key );
	}
	
	/**
	 * Set
	 *
	 * @param mixed $key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( mixed $key, mixed $value )
	{
		if ( isset( static::$databaseColumnMap[ $key ] ) )
		{
			$key = static::$databaseColumnMap[ $key ];
		}

		if( $value instanceof Model )
		{
			$value = $value->_id;
		}
		
		parent::__set( $key, $value );
	}

	/**
	 * @brief	Field ID controlling formatting that we should show/hide depending upon field type selection
	 */
	protected string $fieldFormattingId = 'pf_format';
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{		
		$form->addHeader( 'pfield_settings' );

		/* Are we being forced to change the field type? */
		if ( !$this->_new AND isset( $this->type ) AND in_array( $this->type, static::$disabledFieldTypes ) )
		{
			//\IPS\Member::loggedIn()->language()->words['pf_type_deprecated'] = \IPS\Member::loggedIn()->language()->addToStack('custom_field_type_deprecated');
			$form->addMessage( 'custom_field_type_deprecated', 'ipsMessage ipsMessage_info i-margin_3' );
		}

		$form->add( new Translatable( 'pf_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id : NULL ) ) ) );
		$form->add( new Translatable( 'pf_desc', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id . '_desc' : NULL ) ) ) );
		
		if ( isset( static::$parentNodeClass ) )
		{
			$form->add( new Node( 'pf_group_id', $this->group_id, TRUE, array( 'class' => static::$parentNodeClass, 'subnodes' => FALSE  ) ) );
		}
		
		$options = array_merge( array(
			'Address'		=> 'pf_type_Address',
			'Checkbox'		=> 'pf_type_Checkbox',
			'CheckboxSet'	=> 'pf_type_CheckboxSet',
			'Codemirror'	=> 'pf_type_Codemirror',
			'Color'			=> 'pf_type_Color',
			'Date'			=> 'pf_type_Date',
			'Editor'		=> 'pf_type_Editor',
			'Email'			=> 'pf_type_Email',
			'Member'		=> 'pf_type_Member',
			'Number'		=> 'pf_type_Number',
			'Password'		=> 'pf_type_Password',
			'Poll'			=> 'pf_type_Poll',
			'Radio'			=> 'pf_type_Radio',
			'Rating'		=> 'pf_type_Rating',
			'Select'		=> 'pf_type_Select',
			'Tel'			=> 'pf_type_Tel',
			'Text'			=> 'pf_type_Text',
			'TextArea'		=> 'pf_type_TextArea',
			'Upload'		=> 'pf_type_Upload',
			'Url'			=> 'pf_type_Url',
			'YesNo'			=> 'pf_type_YesNo',
		), static::$additionalFieldTypes );

		$toggles = array(
			'CheckboxSet'	=> array( 'pf_content', 'pf_not_null', 'pf_search_type_on_off', "{$form->id}_header_pfield_displayoptions" ),
			'Codemirror'	=> array( 'pf_not_null', 'pf_max_input', "{$form->id}_header_pfield_displayoptions" ),
			'Email'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions" ),
			'Member'		=> array( 'pf_not_null', 'pf_multiple', "{$form->id}_header_pfield_displayoptions" ),
			'Password'		=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', "{$form->id}_header_pfield_displayoptions" ),
			'Select'		=> array( 'pf_not_null', 'pf_content', 'pf_multiple', 'pf_search_type_on_off', "{$form->id}_header_pfield_displayoptions" ),
			'Tel'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions" ),
			'Text'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions" ),
			'TextArea'		=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions" ),
			'Url'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions" ),
			'Radio'			=> array( 'pf_content', "{$form->id}_header_pfield_displayoptions", 'pf_search_type_on_off' ),
			'Address'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions" ),
			'Color'			=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions" ),
			'Date'			=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions" ),
			'Editor'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_search_type', 'pf_allow_attachments' ),
			'Number'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions"),
			'Poll'			=> array( 'pf_not_null' ),
			'Rating'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions" ),
			'Upload'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions" ),
		);

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


		foreach( $options as $id => $toggle )
		{
			$toggles[$id][] = $this->fieldFormattingId;
		}

		foreach ( static::$additionalFieldTypes as $k => $v )
		{
			$toggles[ $k ] = static::$additionalFieldToggles[$k] ?? array( 'pf_not_null' );
		}
		foreach ( static::$additionalFieldToggles as $k => $v )
		{
			if ( isset( $toggles[ $k ] ) )
			{
				$toggles[ $k ] = array_merge( $toggles[ $k ], $v );
			}
			else
			{
				$toggles[ $k ] = $v;
			}
		}

		/* Remove the disabled fields */
		foreach( static::$disabledFieldTypes as $type )
		{
			unset( $options[ $type ] );
			unset( $toggles[ $type ] );
		}
		
		ksort( $options );

		if ( !$this->_new )
		{
			Member::loggedIn()->language()->words['pf_type_warning']	= Member::loggedIn()->language()->addToStack('custom_field_change');

			foreach ( $toggles as $k => $_toggles )
			{
				if ( !$this->canKeepValueOnChange( $k ) )
				{
					$toggles[ $k ][] = 'form_' . $this->id . '_pf_type_warning';
				}
			}
		}

		$form->add( new Select( 'pf_type', $this->id ? $this->type : 'Text', TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );

		$form->add( new YesNo( 'pf_allow_attachments', $this->id ? $this->allow_attachments : 1, FALSE, array( ), NULL, NULL, NULL, 'pf_allow_attachments' ) );

		$form->add( new Stack( 'pf_content', $this->id ? json_decode( $this->content, TRUE ) : array(), FALSE, array( 'removeEmptyValues' => FALSE ), NULL, NULL, NULL, 'pf_content' ) );
		$form->add( new YesNo( 'pf_multiple', $this->id ? $this->multiple : FALSE, FALSE, array(), NULL, NULL, NULL, 'pf_multiple' ) );

        $requiredColumn = ( isset( static::$databaseColumnMap['not_null'] ) and static::$databaseColumnMap['not_null'] ) ? static::$databaseColumnMap['not_null'] : NULL;
		
		if ( isset( static::$databaseColumnMap['not_null'] ) )
		{
	        $form->add( new YesNo( 'pf_not_null', $this->id ? $this->$requiredColumn : TRUE, FALSE, array(), NULL, NULL, NULL, 'pf_not_null' ) );
	    }
	    
		$form->add( new Number( 'pf_max_input', $this->id ? $this->max_input : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'pf_max_input' ) );
		$form->add( new Text( 'pf_input_format', $this->id ? $this->input_format : NULL, FALSE, array( 'placeholder' => '/[A-Z0-9]+/i' ), function( $val )
		{
			if ( $val AND @preg_match( $val, NULL ) === false )
			{
				throw new DomainException('form_bad_value');
			}
		}, NULL, NULL, 'pf_input_format' ) );
		$form->addHeader( 'pfield_displayoptions' );
		$form->add( new Select( 'pf_search_type', $this->id ? $this->search_type : 'loose', FALSE, array( 'options' => array( 'exact' => 'pf_search_type_exact', 'loose' => 'pf_search_type_loose', '' => 'pf_search_type_none' ) ), NULL, NULL, NULL, 'pf_search_type' ) );
		$form->add( new Select( 'pf_search_type_on_off', $this->id ? $this->search_type : 'exact', FALSE, array( 'options' => array( 'exact' => 'pf_search_type_exact', '' => 'pf_search_type_none' ) ), NULL, NULL, NULL, 'pf_search_type_on_off' ) );

		/* Add form fields from extensions */
		foreach( Application::allExtensions( 'core', 'CustomField' ) as $ext )
		{
			/* @var CustomFieldAbstract $ext */
			if( $ext::isEnabled() )
			{
				$ext::form( $form, $this );
			}
		}

        parent::form( $form );
	}
	
	/**
	 * Does the change mean wiping the value?
	 *
	 * @param string $newType	The new type
	 * @return	bool
	 */
	protected function canKeepValueOnChange( string $newType ): bool
	{
		/* Check additional field types */
		if ( in_array( $newType, array_keys( static::$additionalFieldTypes ) ) )
		{
			return $newType === $this->type;
		}

		/* Check extensions */
		if( $extension = $this->extension() )
		{
			return $extension::canKeepValueOnChange( $this, $newType );
		}
		
		switch ( $this->type )
		{
			case 'Address':
				return $newType == 'Address';
			
			case 'Checkbox':
			case 'YesNo':
				return in_array( $newType, array( 'Checkbox', 'YesNo' ) );
				
			case 'CheckboxSet':
				return in_array( $newType, array( 'CheckboxSet', 'Select' ) );
			
			case 'Code':
			case 'Codemirror':
				return in_array( $newType, array( 'Code', 'Codemirror', 'Editor', 'TextArea' ) );
				
			case 'Color':
				return in_array( $newType, array( 'Color', 'Text' ) );
				
			case 'Date':
				return in_array( $newType, array( 'Date', 'Text' ) );
				
			case 'Editor':
			case 'TextArea':
				return in_array( $newType, array( 'Code', 'Editor', 'TextArea' ) );
			
			case 'Email':
				return in_array( $newType, array( 'Email', 'Password', 'Text' ) );
				
			case 'Item':
				return in_array( $newType, array( 'Item', 'TextArea' ) );
			
			case 'Member':
				return in_array( $newType, array( 'Member', 'Text', 'TextArea', 'Editor' ) );
				
			case 'Number':
			case 'Rating':
				return in_array( $newType, array( 'Number', 'Password', 'Rating', 'Text', 'Tel' ) );
				
			case 'Password':
				return in_array( $newType, array( 'Number', 'Password', 'Text', 'Tel' ) );
				
			case 'Poll':
				return $newType == 'Poll';
				
			case 'Radio':
				return in_array( $newType, array( 'Radio', 'Select' ) );
				
			case 'Select':
				return in_array( $newType, array( 'Select', 'CheckboxSet' ) );
				
			case 'Tel':
				return in_array( $newType, array( 'Password', 'Rating', 'Text', 'Tel' ) );
				
			case 'Text':
				return in_array( $newType, array( 'Email', 'Password', 'Text', 'Tel', 'Url', 'Editor', 'TextArea', 'Code', 'Codemirror' ) );

			case 'Upload':
				return $newType == 'Upload';

			case 'Url':
				return in_array( $newType, array( 'Text', 'Url' ) );
		}
		
		return FALSE;
	}


	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$column = $this->column ?? "field_{$this->id}";

		/* Checkbox sets are always multiple */
		if ( isset( $values['pf_type'] ) AND $values['pf_type'] === 'CheckboxSet' )
		{
			$values['pf_multiple'] = TRUE;
		}

		if( isset( $values['pf_multiple'] ) )
		{
			$values['pf_multiple']	= (bool)$values['pf_multiple'];
		}

		/* Add/Update the content table */
		if ( isset( static::$contentDatabaseTable ) AND isset( $values['pf_type'] ) )
		{
			$columnDefinition = array( 'name' => $column );
			switch ( $values['pf_type'] )
			{
				case 'CheckboxSet':
				case 'Member':
					if ( $values['pf_multiple'] )
					{
						$columnDefinition['type']	= 'TEXT';
					}
					else
					{
						$columnDefinition['type']	= 'INT';
						$columnDefinition['length']	= 10;
					}
					break;
					
				case 'Date':
				case 'Poll':
					$columnDefinition['type'] = 'INT';
					$columnDefinition['length'] = 10;
					break;
				
				case 'Editor':
				case 'TextArea':
				case 'Upload':
				case 'Address':
				case 'Codemirror':
				case 'Select':
					$columnDefinition['type'] = 'MEDIUMTEXT';
					break;
				
				case 'Email':
				case 'Password':
				case 'Tel':
				case 'Text':
				case 'Url':
				case 'Color':
				case 'Radio':
				case 'Number':
					if ( !isset( $values['pf_max_input'] ) OR !$values['pf_max_input'] )
					{
						$columnDefinition['type'] = 'MEDIUMTEXT';
						unset( $columnDefinition['length'] );
					}
					else
					{
						$columnDefinition['type'] = 'VARCHAR';
						$columnDefinition['length'] = 255;
					}

					break;
				
				case 'YesNo':
				case 'Checkbox':
				case 'Rating':
					$columnDefinition['type'] = 'TINYINT';
					$columnDefinition['length'] = 1;
					break;
			}

			/* Process values for extension */
			foreach( Application::allExtensions( 'core', 'CustomField' ) as $ext )
			{
				/* @var CustomFieldAbstract $ext */
				if( $ext::isEnabled() and $values['pf_type'] == $ext::fieldType() )
				{
					$values = $ext::formatFormValues( $values );
					$columnDefinition['type'] = $ext::columnDefinition();
					break;
				}
			}

			if ( isset( $values['pf_max_input'] ) and $values['pf_max_input'] )
			{
				if( $values['pf_max_input'] > 255 )
				{
					$columnDefinition['type'] = 'MEDIUMTEXT';
					unset( $columnDefinition['length'] );
				}
				else
				{
					$columnDefinition['length'] = $values['pf_max_input'];
				}
			}
			
			if ( !$this->id )
			{
				$this->save();
				$columnDefinition['name'] = $this->column ?? "field_{$this->id}";
				
				Db::i()->addColumn( static::$contentDatabaseTable, $columnDefinition );
				
				if ( $values['pf_type'] != 'Upload' )
				{
					if ( in_array( $columnDefinition['type'], array( 'TEXT', 'MEDIUMTEXT' ) ) )
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
					}
					else
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
					}
				}
			}
			elseif( !$this->canKeepValueOnChange( $values['pf_type'] ) )
			{
				try
				{
					Db::i()->dropIndex( static::$contentDatabaseTable, $column );
					Db::i()->dropColumn( static::$contentDatabaseTable, $column );
				} 
				catch ( Exception $e )
				{

				}

				Db::i()->addColumn( static::$contentDatabaseTable, $columnDefinition );

				if ( $values['pf_type'] != 'Upload' )
				{
					if ( in_array( $columnDefinition['type'], array( 'TEXT', 'MEDIUMTEXT' ) ) )
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $column, 'columns' => array( $column ) ) );
					}
					else
					{
						Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $column, 'columns' => array( $column ) ) );
					}
				}
			}
			else
			{
				Db::i()->dropIndex( static::$contentDatabaseTable, $column );

				Db::i()->changeColumn( static::$contentDatabaseTable, $column, $columnDefinition );

				if ( in_array( $columnDefinition['type'], array( 'TEXT', 'MEDIUMTEXT' ) ) )
				{
					Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $column, 'columns' => array( $column ) ) );
				}
				else
				{
					Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $column, 'columns' => array( $column ) ) );
				}
			}
		}
		elseif ( !$this->id )
		{
			$this->save();
		}
				
		/* Save the name and description */
		if( isset( $values['pf_title'] ) )
		{
			Lang::saveCustom( 'core', static::$langKey . '_' . $this->id, $values['pf_title'] );
			unset( $values['pf_title'] );
		}

		if( isset( $values['pf_desc'] ) )
		{
			Lang::saveCustom( 'core', static::$langKey . '_' . $this->id . '_desc', $values['pf_desc'] );
			unset( $values['pf_desc'] );
		}
		
		/* And the other fields */
		if ( isset( static::$parentNodeClass ) AND isset( $values['pf_group_id'] ) )
		{
			$values['group_id'] = is_object( $values['pf_group_id'] ) ? $values['pf_group_id']->_id : $values['pf_group_id'];
		}
		
		/* "Required" means nothing for radio and checkbox */
		if( isset( $values['pf_type'] ) and in_array( $values['pf_type'], array( 'Radio', 'Checkbox' ) ) )
		{
			$values['pf_not_null'] = FALSE;
		}
		
		if ( array_key_exists( 'pf_search_type_on_off', $values ) and isset( $values['pf_type'] ) and ( in_array( $values['pf_type'], array( 'Select', 'Radio', 'CheckboxSet') ) ) )
		{
			$values['pf_search_type'] = (string) $values['pf_search_type_on_off'];
		}
		
		unset( $values['pf_search_type_on_off'] );
		
		/* Translate keys */
		foreach ( array( 'type', 'content', 'multiple', 'not_null', 'max_input', 'input_format', 'search_type', 'format' ) as $k )
		{
			if ( array_key_exists( "pf_{$k}", $values ) )
			{
				$_value = $values[ "pf_{$k}" ];
				unset( $values[ "pf_{$k}" ] );
				
				$values[ static::$databasePrefix . $k ] = ( $k === 'content' ? json_encode( $_value ) : $_value );
			}
		}
		
		unset( $values['pf_change_fieldtype'] );

		return $values;
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
		
		return Member::loggedIn()->language()->addToStack( static::$langKey . '_' . $this->id );
	}

	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd(): bool
	{
		return FALSE;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		return false;
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldId		= $this->id;
		$oldColumn	= $this->column ?? "field_{$oldId}";
		parent::__clone();
        $column = "field_{$this->id}";
		
		if ( isset( static::$contentDatabaseTable ) )
		{
			$definition = Db::i()->getTableDefinition( static::$contentDatabaseTable, TRUE );

			/* @var array $fieldDefinition */
			$fieldDefinition = $definition['columns'][ $oldColumn ];
			$fieldDefinition['name'] = $column;

			Db::i()->addColumn( static::$contentDatabaseTable, $fieldDefinition );

			if ( $this->type != 'Upload' )
			{
				if ( in_array( $fieldDefinition['type'], array( 'TEXT', 'MEDIUMTEXT' ) ) )
				{
					Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $column, 'columns' => array( $column ) ) );
				}
				else
				{
					Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $column, 'columns' => array( $column ) ) );
				}
			}
		}

		if ( isset( $this->column ) )
		{
			$this->column = NULL;
		}

		Lang::saveCustom( 'core', static::$langKey . '_' . $this->id, iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$langKey . '_' . $oldId ) )->setKeyField( 'lang_id' )->setValueField( 'word_custom' ) ) );
		Lang::saveCustom( 'core', static::$langKey . '_' . $this->id . '_desc', iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$langKey . '_' . $oldId . '_desc' ) )->setKeyField( 'lang_id' )->setValueField( 'word_custom' ) ) );
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$column = $this->column ?? "field_{$this->id}";

		parent::delete();
		
		Lang::deleteCustom( 'core', static::$langKey . '_' . $this->id );
		Lang::deleteCustom( 'core', static::$langKey . '_' . $this->id . '_desc' );

		if ( isset( static::$contentDatabaseTable ) and static::$contentDatabaseTable )
		{
			if( $this->type == 'Upload' )
			{
				/* Delete records */
				Task::queue( 'core', 'FileCleanup', array(
					'table'				=> static::$contentDatabaseTable,
					'column'			=> $column,
					'storageExtension'	=> static::$uploadStorageExtension,
					'dropColumn'		=> $column,
					'dropColumnTable'	=> static::$contentDatabaseTable,
					'multipleFiles'		=> $this->multiple
				), 4 );
			}
			else
			{
				try
				{
					Db::i()->dropColumn( static::$contentDatabaseTable, $column );
				}
				catch( Exception $e ) { }
			}
		}
	}

	/**
	 * Build Form Helper
	 *
	 * @param mixed|null $value The value
	 * @param callback|null $customValidationCode Custom validation code
	 * @param Content|NULL $content The associated content, if editing
	 * @param int $flags
	 * @return FormAbstract
	 */
	public function buildHelper( mixed $value=NULL, callable $customValidationCode=NULL, Content $content = NULL, int $flags=0 ): FormAbstract
	{
		$class = '\IPS\Helpers\Form\\' . $this->type;
		$options = array();

		/* Check the extensions first */
		if( !class_exists( $class ) )
		{
			if( $extension = $this->extension() )
			{
				$class = $extension::formClass();
				$options = $extension::formHelperOptions( $this );
			}
		}

		/* Fallback option in case the extension is no longer valid. Disabling or uninstalling an extension can
		then cause an error here, so we default to text. */
		if ( ! class_exists( $class ) )
		{
			$class = '\IPS\Helpers\Form\\Text';
		}
		
		$maxLength = $this->max_input ?: NULL;

		/* Set the max length to 255 because that's the max the column can store */
		if( !$maxLength AND !in_array( $this->type, [ 'Editor', 'TextArea', 'Upload', 'Address', 'CodeMirror', 'Select' ] ) )
		{
			$maxLength = 255;
		}

		switch ( $this->type )
		{
			case 'Date':
				// If the user has a custom profile field specifying a date (for example, their anniversary), you would expect
				// that date to display the same to all users regardless of their timezone.
				// To faciliate this, the timestamp for the inputted date UTC is saved, rather that the inputted date in the submitting user's timezone
				// displayValue will then not apply the viewing user's timestamp.
				$options['timezone'] = new DateTimeZone('UTC');
				
				if ( is_numeric( $value ) )
				{
					$value = DateTime::ts( $value );
				}
				break;
			
			case 'Email':
			case 'Password':
			case 'Tel':
			case 'Text':
			case 'TextArea':
			case 'Url':
				$options['maxLength']	= $maxLength;
				$options['regex']		= $this->input_format ?: NULL;
				break;
			
			case 'CheckboxSet':
				$options['multiple'] = TRUE;
				$options['noDefault'] = TRUE;
				$options['options'] = json_decode( $this->content, TRUE );

				if ( $this->multiple )
				{
					if( $value !== NULL AND $value !== '' )
					{
						$value = explode( ',', htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) );
					}

					$options['multiple'] = $this->multiple;
				}
				break;
			case 'Select':
				$options['options'] = json_decode( $this->content, TRUE );
				$options['options'] = ( $options['options'] ) ? array_combine( $options['options'], $options['options'] ) : array();
				
				$requiredColumn = ( isset( $this::$databaseColumnMap['not_null'] ) and $this::$databaseColumnMap['not_null'] ) ? $this::$databaseColumnMap['not_null'] : NULL;
				if ( !$this->$requiredColumn AND !$this->multiple )
				{
					/* We cannot use array_unshift because it reindexes numerical indexes, which breaks custom field options */
					$options['options'] = array( '' ) + $options['options'];
				}

				if ( $this->multiple )
				{
					$options['noDefault'] = TRUE;
					
					if( $value !== NULL AND $value !== '' )
					{
						$values = explode( ',', $value );
						$value = array();
						foreach( $values as $val )
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
										
					$options['multiple'] = $this->multiple;
				}
				else
				{
					if ( is_numeric( $value ) and intval( $value ) == $value )
					{
						$value = intval( $value );
					}
				}

				break;

			case 'Radio':
				$options['returnLabels'] = TRUE;
				$options['options'] = json_decode( $this->content, TRUE );
				break;
				
			case 'Upload':
				$options['storageExtension'] = static::$uploadStorageExtension;
				$options['canBeModerated'] = static::$uploadsCanBeModerated;
			if( $value )
				{
					try
					{
						$value = File::get( static::$uploadStorageExtension, $value );
					}
					catch ( OutOfRangeException $e )
					{
						$value = NULL;
					} 
				}
				break;
				
			case 'Editor':
				$options = static::$editorOptions;
				
				if ( !isset( $options['autoSaveKey'] ) )
				{
					$options['autoSaveKey'] = md5( get_class( $this ) . '-' . $this->id  . '-' . ( $content ? $content->id : 'new' ) );
				}
				
				$options['allowAttachments'] = $this->allow_attachments;
				break;
			
			case 'Address':
				$value = GeoLocation::buildFromJson( $value );
				break;
				
			case 'Member':
				$options['autocomplete']['minimized'] = false;
				$options['multiple'] = $this->multiple ? NULL : 1;
				if ( $value )
				{
					$value = array_map( function( $id )
					{
						return Member::load( $id );
					}, explode( "\n", $value ) );
				}
				break;
				
			case 'Poll':
				if ( $value )
				{
					try
					{
						$value = Poll::load( $value );
					}
					catch ( OutOfRangeException $e )
					{
						$value = NULL;
					} 
				}
				break;
				
			case 'Ftp':
				$options['allowBypassValidation'] = TRUE;
				break;
		}
		
		/* Editor form field names and IDs should differ */
		if ( $this->type == 'Editor' )
		{
			return new $class( static::$langKey . '_' . $this->id, $value, $this->not_null, $options, $customValidationCode, NULL, NULL, static::$langKey . '_' . $this->id . '_editor' );
		}
		else
		{
			return new $class( static::$langKey . '_' . $this->id, $value, $this->not_null, $options, $customValidationCode, NULL, NULL, static::$langKey . '_' . $this->id );
		}
	}
	
	/**
	 * Claim attachments for an editor field
	 *
	 * @param int|null $id1					ID 1	(ID 2 will be the field ID)
	 * @param mixed|null $id3					ID 3
	 * @param Content|null $contentBeingEdited		If you passed $content to buildHelpers(), pass that same object here. ONLY if editing, don't pass a newly created piece of content
	 * @return	void
	 */
	public function claimAttachments( int $id1 = NULL, mixed $id3 = NULL, Content $contentBeingEdited = NULL ) : void
	{
		$options = static::$editorOptions;
		
		if ( !isset( $options['autoSaveKey'] ) )
		{
			$options['autoSaveKey'] = md5( get_class( $this ) . '-' . $this->id . '-' . ( $contentBeingEdited ? $contentBeingEdited->id : 'new' ) );
		}

		File::claimAttachments( $options['autoSaveKey'], $id1, $this->id, $id3 );
	}
	
	/**
	 * Return values in API-suitable format
	 *
	 * @param mixed|null $value						The value
	 * @param bool $showSensitiveInformation	If TRUE, potentially sensitive data (like passwords) will be displayed - otherwise will be blanked out
	 * @return	int|bool|array|string|null 			Must return a value that can be JSON serialized
	 */
	public function apiValue( mixed $value=NULL, bool $showSensitiveInformation = true ): int|bool|array|string|null
	{
		switch( $this->type )
		{
			case 'Address':
				return json_decode( $value );
			case 'CheckboxSet':
			case 'Select':
				if ( is_array( $this->extra ) AND isset( $this->extra[ $value ] ) )
				{
					$values = $this->extra[ $value ];
				}
				else
				{
					$options = json_decode( $this->content, TRUE );

					/* Options are based on numeric indices, however when creating the original form field we may have added
						a dummy option to the beginning - we need to do the same here or else index '5' is not the same index '5'
						we originally selected from the form element */
					if( $this->type == 'Select' )
					{
						$requiredColumn = ( isset( $this::$databaseColumnMap['not_null'] ) and $this::$databaseColumnMap['not_null'] ) ? $this::$databaseColumnMap['not_null'] : NULL;
						if ( !$this->$requiredColumn AND !$this->multiple AND is_array( $options ) )
						{
							array_unshift( $options, '' );
						}
					}

					$values = array();

					if( $this->multiple )
					{
						foreach( explode( ',', $value ) as $_value )
						{
							$values[] = ( isset( $options[ $_value ] ) ) ? $options[ $_value ] : $_value;
						}
					}
					else
					{
						$values[] = ( isset( $options[ $value ] ) ) ? $options[ $value ] : $value;
					}
				}
				
				return $values;
			case 'Member':
				return array_map( function( $id )
				{
					$member = Member::load( $id );
					$group = Group::load( $member->group['g_id'] );

					return array(
						'id' => $member->member_id,
						'name' => $member->name,
						'photo' => $member->photo,
						'groupName' => $group->name
					);
				}, explode( "\n", $value ) );
			case 'Password':
				if ( $value )
				{
					return $showSensitiveInformation ? $value : '********';
				}
				return '';
			case 'Poll':
			case 'Ftp':			
				return FALSE; // Not implemented in API yet
			case 'Upload':
				if( $value )
				{
					$file = File::get( static::$uploadStorageExtension, $value );
					$downloadUrl = Url::internal( 'applications/core/interface/file/cfield.php', 'none' )->setqueryString( array(
						'storage'	=> $file->storageExtension,
						'path'		=> $file->originalFilename,
						'fileKey'   => base64_encode( Encrypt::fromPlaintext( (string) $file )->cipher )
					) );
					return (string) $downloadUrl;
				}
				else
				{
					return '';
				}
			case 'YesNo':
				return (bool) $value;
			case 'Color':
				return ( $value AND $value !== '#' ) ? $value : '';
			case 'Url':
				return ( $value ) ? (string) Url::external( $value ) : NULL;
			case 'Rating':
				return array(
					'value' => $value,
					'max' => 5
				);

			// These types can just return value as-is
			case 'Checkbox':
			case 'CodeMirror':
			case 'Date':
			case 'Editor':
			case 'TextArea':
			default:
				return $value;
		}
	}

	/**
	 * Display Value
	 *
	 * @param mixed|null $value The value
	 * @param bool $showSensitiveInformation If TRUE, potentially sensitive data (like passwords) will be displayed - otherwise will be blanked out
	 * @param string|null $separator Used to separate items when displaying a field with multiple values.
	 * @return string|null
	 */
	public function displayValue( mixed $value=NULL, bool $showSensitiveInformation=FALSE, string $separator=NULL ): ?string
	{
		switch ( $this->type )
		{
			case 'Address':
				return GeoLocation::buildFromJson( $value )->toString();
			
			case 'Checkbox':
			case 'YesNo':
				return $value ? Member::loggedIn()->language()->addToStack('yes') : Member::loggedIn()->language()->addToStack('no');
			
			case 'CheckboxSet':
			case 'Select':
				if ( is_array( $this->extra ) AND isset( $this->extra[ $value ] ) )
				{
					$value = $this->extra[ $value ];
				}
				else
				{
					$options = json_decode( $this->content, TRUE );

					/* Options are based on numeric indices, however when creating the original form field we may have added
						a dummy option to the beginning - we need to do the same here or else index '5' is not the same index '5'
						we originally selected from the form element */
					if( $this->type == 'Select' )
					{
						$requiredColumn = ( isset( $this::$databaseColumnMap['not_null'] ) and $this::$databaseColumnMap['not_null'] ) ? $this::$databaseColumnMap['not_null'] : NULL;
						if ( !$this->$requiredColumn AND !$this->multiple AND is_array( $options )  )
						{
							array_unshift( $options, '' );
						}
					}

					if( $this->multiple )
					{
						$_values = array();

						foreach( explode( ',', $value ) as $_value )
						{
							$_values[]	= ( isset( $options[ $_value ] ) ) ? $options[ $_value ] : $_value;
						}

						$value = implode( ',', $_values );
					}
					else
					{
						$value		= ( isset( $options[ $value ] ) ) ? $options[ $value ] : $value;
					}
				}
				
				if ( $this->multiple )
				{
					return implode( ( $separator ) ?: '<br>', explode( ',', htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) ) );
				}
				else
				{
					return htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE );
				}
			
			case 'Codemirror':
				return Theme::i()->getTemplate( 'global', 'core', 'global' )->prettyprint( $value );
			
			case 'Color':
				return ( $value AND $value !== '#' ) ? Theme::i()->getTemplate( 'forms', 'core', 'global' )->colorDisplay( $value ) : '';
			
			case 'Date':
				return $value ? DateTime::ts( $value, TRUE )->localeDate() : ''; // See buildHelper for why we ignore the timezone
			
			case 'Editor':
				return $value;
				
			case 'Member':
				return implode( '<br>', array_map( function( $id )
				{
					return Member::load( $id )->link();
				}, explode( "\n", $value ) ) );
				
			case 'Password':
				if ( $value )
				{
					return $showSensitiveInformation ? htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) : '********';
				}
				return '';
				
			case 'Poll':
				return $value ? ( (string) Poll::load( $value ) ) : NULL;
			
			case 'Rating':
				return Theme::i()->getTemplate( 'global', 'core', 'front' )->rating( $this->options['max'] ?? null, $value );
				
			case 'TextArea':
				return nl2br( htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) );
				
			case 'Url':
				return ( $value ) ? Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $value, TRUE, $value, TRUE, TRUE, TRUE ) : NULL;
							
			case 'Upload':
				if( $value )
				{
					$file = File::get( static::$uploadStorageExtension, $value );
					$downloadUrl = Url::internal( 'applications/core/interface/file/cfield.php', 'none' )->setqueryString( array(
						'storage'	=> $file->storageExtension,
						'path'		=> $file->originalFilename,
						'fileKey'   => Encrypt::fromPlaintext( (string) $file )->tag()
					) );
					return Theme::i()->getTemplate( 'forms', 'core', 'global' )->uploadDisplay( $file, $downloadUrl );
				}
				else
				{
					return '';
				}

			case 'Ftp':
				$value = is_array( $value ) ? $value : json_decode( Encrypt::fromTag( $value )->decrypt(), TRUE );
				if ( $showSensitiveInformation )
				{
					$url = Url::createFromComponents( $value['server'], $value['protocol'] ? ( $value['protocol'] == 'ssl_ftp' ? 'ftps': $value['protocol'] ) : 'ftp', $value['path'] ?: NULL, NULL, $value['port'] ?: 21, $value['un'] ?: NULL, $value['pw'] ?: NULL );
				}
				else
				{
					$url = NULL;
					$value['pw'] = '********';
				}
				return Theme::i()->getTemplate( 'forms', 'core', 'global' )->ftpDisplay( $value, $url );

			default:

				/* Is this an extension? */
				if( $extension = $this->extension() )
				{
					return $extension::displayValue( $this, $value, $showSensitiveInformation, $separator );
				}

				return htmlspecialchars( $value, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE );
		}
	}

	/**
	 * Does this field use an extension?
	 *
	 * @return CustomFieldAbstract|null
	 */
	public function extension() : ?CustomFieldAbstract
	{
		foreach( Application::allExtensions( 'core', 'CustomField' ) as $ext )
		{
			/* @var CustomFieldAbstract $ext */
			if( $ext::isEnabled() and $ext::fieldType() == $this->type )
			{
				return $ext;
			}
		}

		return null;
	}
}