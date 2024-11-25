<?php
/**
 * @brief		Profile Completion
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		04 Jan 2018
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\ProfileStep;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOFRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile Completion
 */
class profilecompletion extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\Member\ProfileStep';

	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		$return = parent::_getRootButtons();
		
		if ( isset( Output::i()->sidebar['actions']['add'] ) )
		{
			$class = new ProfileStep;
			if ( ! $class->canAdd() )
			{
				unset( Output::i()->sidebar['actions']['add'] );
			}
		}
		
		return $return;
	}
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'profilefields_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		$class = new ProfileStep;
		Output::i()->output = Theme::i()->getTemplate( 'members' )->profileCompleteBlurb( $class->canAdd() );
		parent::manage();
	}

	/**
	 * Enable quick registration
	 *
	 * @return	void
	 */
	public function enableQuickRegister() : void
	{
		Session::i()->csrfCheck();
		
		Settings::i()->changeValues( array( 'quick_register' => 1 ) );
		
		Session::i()->log( 'acplog__quick_register_enabled' );
		
		Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=profiles&tab=profilecompletion' ), 'profile_complete_quick_register_off_enabled' );
	}
	
	/**
	 * Add Step Form
	 *
	 * @return	void
	 */
	public function form() : void
	{
		$form = new Form;
		if ( isset( Request::i()->id ) )
		{
			try
			{
				$step = ProfileStep::load( Request::i()->id );
			}
			catch( OutOFRangeException $e )
			{
				Output::i()->error( 'node_error', '2C360/1', 404, '' );
			}
		}
		else
		{
			$step = new ProfileStep;
		}
		
		$step->form( $form );
		
		if ( $values = $form->values() )
		{
			$values = $step->formatFormValues( $values );
			
			$extension = ProfileStep::loadExtensionFromAction( $values['step_completion_act'] );
			$step->required = ( isset( $values['step_required'] ) ) ? $values['step_required'] : FALSE;
			$step->registration = $values['step_registration'];
			$step->completion_act = $values['step_completion_act'];
			$step->subcompletion_act = $values['step_subcompletion_act'] ?? NULL;
			$step->save();
			
			$step->postSaveForm( $values );
			
			$extension->postAcpSave( $step, $values );
			
			Member::updateAllMembers( array( "members_bitoptions2 = members_bitoptions2 & ~" . Member::$bitOptions['members_bitoptions']['members_bitoptions2']['profile_completed'] ) );
			
			Session::i()->log( 'acplog__profile_step_added', array( "profile_step_title_{$step->id}" => TRUE ) );
			
			Output::i()->redirect( Url::internal( "app=core&module=membersettings&controller=profiles&tab=profilecompletion" ), 'saved' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'profile_completion' );
		Output::i()->output	= $form;
	}
}