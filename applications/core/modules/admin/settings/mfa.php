<?php
/**
 * @brief		Multi-Factor Authentication
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Nov 2016
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Multi-Factor Authentication
 */
class mfa extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'mfa_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$activeTabContents = '';
		$tabs = array(
			'handlers' 	=> 'mfa_handlers',
			'settings'	=> 'mfa_settings'
		);
		$activeTab = ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'handlers';
		
		if ( $activeTab === 'handlers' )
		{
			$activeTabContents = $this->_manageHandlers();
		}
		else
		{
			$activeTabContents = $this->_manageSettings();
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_settings_mfa');
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'forms' )->blurb( 'mfa_blurb' ) . Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, Url::internal( "app=core&module=settings&controller=mfa" ) );
		}
	}
	
	/**
	 * Manage Handlers
	 *
	 * @return	string
	 */
	protected function _manageHandlers() : string
	{
		/* Create the tree */
		$url = Url::internal( "app=core&module=settings&controller=mfa&tab=handlers" );
		$tree = new Tree(
			$url,
			NULL,
			function() use( $url ) {
				$return = array();
				
				foreach ( MFAHandler::handlers() as $key => $handler )
				{
					$return[] = Theme::i()->getTemplate( 'trees', 'core' )->row(
						$url,
						$key,
						Member::loggedIn()->language()->addToStack("mfa_{$key}_title"),
						FALSE,
						array(
							'settings' => array(
								'icon'	=> 'cog',
								'title'	=> 'settings',
								'link'	=> $url->setQueryString( array( 'do' => 'settings', 'key' => $key ) ),
							)
						),
						Member::loggedIn()->language()->addToStack("mfa_{$key}_desc"),
						NULL,
						NULL,
						FALSE,
						$handler->isEnabled()
					);
				}
				
				return $return;
			},
			NULL,
			NULL,
			NULL
		);
		
		/* Return */
		return $tree;
	}
	
	/**
	 * Enable/Disable Toggle
	 *
	 * @return	void
	 */
	protected function enableToggle() : void
	{
		Session::i()->csrfCheck();
		
		$key = Request::i()->id;
		$handlers = MFAHandler::handlers();
		if ( !isset( $handlers[ $key ] ) )
		{
			Output::i()->error( 'node_error', '2C345/1', 404, '' );
		}
		
		try
		{
			$handlers[ $key ]->toggle( Request::i()->status );
			
			if ( Request::i()->status )
			{
				Session::i()->log( 'acplogs__mfa_handler_enabled', array( "mfa_{$key}_title" => TRUE ) );
			}
			else
			{
				Session::i()->log( 'acplogs__mfa_handler_disabled', array( "mfa_{$key}_title" => TRUE ) );
			}
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->json('OK');
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=settings&controller=mfa" ) );
			}
		}
		catch ( Exception $e )
		{
			Output::i()->redirect( Url::internal( "app=core&module=settings&controller=mfa&tab=handlers&do=settings&key=" . $key ) );
		}
	}
	
	/**
	 * Handler Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$key = Request::i()->key;
		$handlers = MFAHandler::handlers();
		if ( !isset( $handlers[ $key ] ) )
		{
			Output::i()->error( 'node_error', '2C345/2', 404, '' );
		}
		
		$output = $handlers[ $key ]->acpSettings();
		
		Output::i()->title = Member::loggedIn()->language()->addToStack("mfa_{$key}_title");
		Output::i()->output = $output;
		Output::i()->breadcrumb[] = array( Url::internal('app=core&module=settings&controller=mfa&tab=handlers'), Member::loggedIn()->language()->addToStack('menu__core_settings_mfa') );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack("mfa_{$key}_title") );
	}
	
	/**
	 * Manage Settings
	 *
	 * @return	string
	 */
	protected function _manageSettings() : string
	{
		$form = new Form;
		
		$form->addHeader('mfa_header_setup');
		$groups = array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) );
		$form->add( new CheckboxSet( 'mfa_required_groups', Settings::i()->mfa_required_groups == '*' ? '*' : explode( ',', Settings::i()->mfa_required_groups ), FALSE, array(
			'multiple'			=> TRUE,
			'options'			=> $groups,
			'unlimited'			=> '*',
			'unlimitedLang'		=> 'everyone',
			'impliedUnlimited'	=> TRUE
		) ) );
		$form->add( new Radio( 'mfa_required_prompt', Settings::i()->mfa_required_prompt, FALSE, array(
			'options'	=> array(
				'immediate'	=> 'mfa_prompt_immediate',
				'access'	=> 'mfa_prompt_access',
			)
		), NULL, NULL, NULL, 'mfa_required_prompt' ) );
		$form->add( new Radio( 'mfa_optional_prompt', Settings::i()->mfa_optional_prompt, FALSE, array(
			'options'	=> array(
				'immediate'	=> 'mfa_prompt_immediate',
				'access'	=> 'mfa_prompt_access',
				'none'		=> 'mfa_prompt_none',
			),
			'toggles'	=> array(
				'immediate'	=> array( 'security_questions_opt_out_warning' ),
				'access'	=> array( 'security_questions_opt_out_warning' ),
			)
		), NULL, NULL, NULL, 'mfa_optional_prompt' ) );
		$form->add( new Translatable( 'security_questions_opt_out_warning', NULL, FALSE, array( 'app' => 'core', 'key' => 'security_questions_opt_out_warning_value' ), NULL, NULL, NULL, 'security_questions_opt_out_warning' ) );

		$form->addHeader('mfa_header_authentication');
		$form->add( new CheckboxSet( 'security_questions_areas', Settings::i()->security_questions_areas ? explode( ',', Settings::i()->security_questions_areas ) : array_keys( MFAHandler::areas() ), FALSE, array( 'options' => MFAHandler::areas() ), NULL, NULL, NULL, 'security_questions_areas' ) );
		$form->add( new Interval( 'security_questions_timer', Settings::i()->security_questions_timer, FALSE, array( 'valueAs' => Interval::MINUTES, 'unlimited' => 0, 'unlimitedLang' => 'security_questions_timer_session' ) ) );

		$form->addHeader('mfa_header_recovery');
		$form->add( new Number( 'security_questions_tries', Settings::i()->security_questions_tries, FALSE, array( 'min' => 1 ) ) );
		$form->add( new Radio( 'mfa_lockout_behaviour', Settings::i()->mfa_lockout_behaviour, FALSE, array(
			'options'	=> array(
				'lock'		=> 'mfa_lockout_behaviour_lock',
				'email'		=> 'mfa_lockout_behaviour_email',
				'contact'	=> 'mfa_lockout_behaviour_contact',
			),
			'toggles'	=> array(
				'lock'		=> array( 'mfa_lockout_time' )
			)
		) ) );
		$form->add( new Interval( 'mfa_lockout_time', Settings::i()->mfa_lockout_time, FALSE, array( 'valueAs' => Interval::MINUTES, 'min' => 1 ), NULL, NULL, NULL, 'mfa_lockout_time' ) );
		$form->add( new CheckboxSet( 'mfa_forgot_behaviour', explode( ',', Settings::i()->mfa_forgot_behaviour ), FALSE, array(
			'options' => array(
				'email'		=> 'mfa_forgot_behaviour_email',
				'contact'	=> 'mfa_forgot_behaviour_contact',
			)
		) ) );

		
		if ( $values = $form->values() )
		{
			Lang::saveCustom( 'core', 'security_questions_opt_out_warning_value', $values['security_questions_opt_out_warning'] );
			unset( $values['security_questions_opt_out_warning'] );
			
			$values['mfa_required_groups'] = ( $values['mfa_required_groups'] == '*' ) ? '*' : implode( ',', $values['mfa_required_groups'] );
			$values['mfa_forgot_behaviour'] = implode( ',', $values['mfa_forgot_behaviour'] );
			$values['security_questions_areas'] = implode( ',', $values['security_questions_areas'] );
			
			$form->saveAsSettings( $values );			
			
			Session::i()->log( 'acplogs__mfa_settings_updated' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=mfa&tab=settings' ), 'saved' );
		}
		
		return (string) $form;
	}

	/**
	 * Reset security answers
	 *
	 * @return	void
	 */
	public function resetSecurityAnswers() : void
	{
		Session::i()->csrfCheck();

		Db::i()->delete( 'core_security_answers' );
		Db::i()->update( 'core_members', "members_bitoptions2=members_bitoptions2 &~ 512" );

		/* Log MFA reset */
		Session::i()->log( 'acplogs__mfa_questions_reset' );

		Output::i()->redirect( Url::internal( "app=core&module=settings&controller=mfa&tab=handlers&do=settings&key=questions" ), 'acplogs__mfa_questions_reset' );
	}
}