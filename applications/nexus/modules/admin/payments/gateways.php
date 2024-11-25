<?php
/**
 * @brief		Payment Gateways
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Lang;
use IPS\nexus\Gateway;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Task;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Payment Gateways
 */
class gateways extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\nexus\Gateway';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'gateways_manage' );
		parent::execute();
	}
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		$buttons = parent::_getRootButtons();
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['link'] = $buttons['add']['link']->setQueryString( '_new', TRUE );
		}
		
		return $buttons;
	}
	
	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		if ( Request::i()->id )
		{
			parent::form();
		}
		else
		{
			if ( \IPS\IN_DEV )
			{
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/moxie.js', 'core', 'interface' ) );
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/plupload.dev.js', 'core', 'interface' ) );
			}
			else
			{
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/plupload.full.min.js', 'core', 'interface' ) );
			}
			Output::i()->output = (string) new Wizard( array(
				'gateways_gateway'	=> function( $data )
				{
					$options = array();
					foreach ( Gateway::gateways() as $key => $class )
					{
						$options[ $key ] = 'gateway__' . $key;
					}

					$form = new Form;
					$form->add( new Radio( 'gateways_gateway', TRUE, NULL, array( 'options' => $options ) ) );
					if ( $values = $form->values() )
					{
						return array( 'gateway' => $values['gateways_gateway'] );
					}
					return $form;
				},
				'gateways_details'	=> function( $data )
				{
					$form = new Form('gw');
					$class = Gateway::gateways()[ $data['gateway'] ];
					$obj = new $class;
					$obj->gateway = $data['gateway'];
					$obj->active = TRUE;
					$obj->form( $form );
					if ( $values = $form->values() )
					{

						$settings = array();
						foreach ( $values as $k => $v )
						{
							if ( $k !== 'paymethod_name' AND $k !== 'paymethod_countries' )
							{
								$settings[ mb_substr( $k, mb_strlen( $data['gateway'] ) + 1 ) ] = $v;
							}
						}
						try
						{
							$settings = $obj->testSettings( $settings );
						}
						catch ( InvalidArgumentException $e )
						{
							$form->error = $e->getMessage();
							return $form;
						}
						
						$name = $values['paymethod_name'];
						$values = $obj->formatFormValues( $values );
						$obj->settings = json_encode( $settings );
						$obj->countries = $values['countries'];
						if( isset(  $values['validationfile'] ) )
						{
							$obj->validationfile = $values['validationfile'];
						}

						$obj->save();
						Lang::saveCustom( 'nexus', "nexus_paymethod_{$obj->id}", $name );
						Session::i()->log( 'acplogs__nexus_added_gateway', array( "nexus_paymethod_{$obj->id}" => TRUE ) );

						Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=paymentsettings&tab=gateways') );
					}
					return $form;
				}
			), Url::internal('app=nexus&module=payments&controller=paymentsettings&tab=gateways&do=form') );
		}
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		/* Get node */
		/* @var Gateway $nodeClass */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}
		
		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X411/1', 404, '' );
		}
		 
		/* Permission check */
		if( !$node->canDelete() )
		{
			Output::i()->error( 'node_noperm_delete', '2X411/2', 403, '' );
		}

		if( $node->hasActiveBillingAgreements() )
		{
			/* Make sure the user confirmed the deletion */
			Request::i()->confirmedDelete();
			
			Task::queue( 'nexus', 'DeletePaymentMethod', array( 'id' => $node->id ), 3, array( 'id' ) );
			Session::i()->log( 'acplog__node_deleted_c', array( $node->_title => TRUE, $node->titleForLog() => FALSE ) );
			Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->_id : '' ) ) ), 'deleted' );
		}
		else
		{
			parent::delete();
		}
	}
}