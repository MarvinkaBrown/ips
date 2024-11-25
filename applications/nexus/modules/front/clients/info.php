<?php

/**
 * @brief		Custom Fields
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		08 Sep 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Member;
use IPS\MFA\MFAHandler;
use IPS\nexus\Customer;
use IPS\nexus\Customer\CustomField;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom Fields
 */
class info extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2X242/1', 403, '' );
		}
		
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=info', 'front', 'clientsinfo' ), Member::loggedIn()->language()->addToStack('client_info') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('client_info');
		Output::i()->sidebar['enabled'] = FALSE;
		
		if ( $output = MFAHandler::accessToArea( 'nexus', 'BillingInfo', Url::internal( 'app=nexus&module=clients&controller=info', 'front', 'clientsinfo' ) ) )
		{
			$form = new Form;
			foreach( $this->_buildInfoForm( TRUE ) AS $formElement )
			{
				$form->add( $formElement );
			}
			Output::i()->output = Theme::i()->getTemplate('clients')->info( $form ) . $output;
			return;
		}
		
		parent::execute();
	}
	
	/**
	 * Edit Info
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = new Form;
		foreach( $this->_buildInfoForm() AS $formElement )
		{
			$form->add( $formElement );
		}
		
		if ( $values = $form->values() )
		{
			$changes = array();
			foreach( array( 'cm_first_name', 'cm_last_name' ) AS $nameField )
			{
				if ( isset( $values[ $nameField ] ) )
				{
					if ( $values[ $nameField ] != Customer::loggedIn()->$nameField )
					{
						/* We only need to log this once, so do it if it isn't set */
						if ( !isset( $changes['name'] ) )
						{
							$changes['name'] = Customer::loggedIn()->cm_name;
						}
						
						Customer::loggedIn()->$nameField = $values[ $nameField ];
					}
				}
			}
			
			foreach ( CustomField::roots() as $field )
			{
				/* @var CustomField $field */
				$column = $field->column;
				$helper = $field->buildHelper();
				if ( $helper instanceof Upload )
				{
					$valueToSave = (string) $values["nexus_ccfield_{$field->id}"];
				}
				else
				{
					$valueToSave = $helper::stringValue( $values["nexus_ccfield_{$field->id}"] );
				}
				if ( Customer::loggedIn()->$column != $valueToSave )
				{
					$changes['other'][] = array( 'name' => 'nexus_ccfield_' . $field->id, 'value' => $field->displayValue( $valueToSave ), 'old' => $field->displayValue( Customer::loggedIn()->$column ) );
					Customer::loggedIn()->$column = $valueToSave;
				}
				
				if ( $field->type === 'Editor' )
				{
					$field->claimAttachments( Customer::loggedIn()->member_id );
				}
			}
			if ( !empty( $changes ) )
			{
				Customer::loggedIn()->log( 'info', $changes );
			}
			Customer::loggedIn()->save();
			Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=info', 'front', 'clientsinfo' ) );
		}
		
		Output::i()->output = Theme::i()->getTemplate('clients')->info( $form );
	}
	
	/**
	 * Build Information Form
	 *
	 * @param	bool	$protected	If TRUE, current values will be blanked out
	 * @return	array
	 */
	protected function _buildInfoForm( bool $protected = FALSE ) : array
	{
		$formElements = array();
		$formElements['cm_first_name']	= new Text( 'cm_first_name', $protected ? NULL : Customer::loggedIn()->cm_first_name, TRUE );
		$formElements['cm_last_name']	= new Text( 'cm_last_name', $protected ? NULL : Customer::loggedIn()->cm_last_name, TRUE );
		foreach ( CustomField::roots() as $field )
		{
			/* @var CustomField $field */
			$column = $field->column;
			if ( $field->type === 'Editor' )
			{
				$field::$editorOptions = array_merge( $field::$editorOptions, array( 'attachIds' => array( Customer::loggedIn()->member_id ) ) );
			}
			$formElements[ $column ] = $field->buildHelper( $protected ? NULL : Customer::loggedIn()->$column );
		}
		
		return $formElements;
	}
}