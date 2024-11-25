<?php
/**
 * @brief		Register
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 July 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Application\Module;
use IPS\core\ProfileFields\Field;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Email;
use IPS\Events\Event;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Email as FormEmail;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Http\Url\Internal;
use IPS\Lang;
use IPS\Login;
use IPS\Login\Handler;
use IPS\Login\Success;
use IPS\Member;
use IPS\Member\Device;
use IPS\Member\ProfileStep;
use IPS\MFA\MFAHandler;
use IPS\MFA\SecurityQuestions\Question;
use IPS\nexus\Package;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Session\Front;
use IPS\Settings;
use IPS\Text\Encrypt;
use IPS\Theme;
use OutOfRangeException;
use UnderFlowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use const IPS\SUITE_UNIQUE_KEY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Register
 */
class register extends Controller
{
	/**
	 * @brief	Is this for displaying "content"? Affects if advertisements may be shown
	 */
	public bool $isContentPage = FALSE;
	
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		if ( Member::loggedIn()->member_id and isset( Request::i()->_fromLogin ) )
		{
			Output::i()->redirect( Settings::i()->base_url );
		}
		
		if( Request::i()->do !== 'complete' and Request::i()->do !== 'setPassword'
			and Request::i()->do !== 'changeEmail' and Request::i()->do !== 'validate'
			and Request::i()->do !== 'validating' and Request::i()->do !== 'reconfirm'
			and Request::i()->do !== 'finish' and Request::i()->do !== 'cancel'
			and Request::i()->do !== 'resend' )
		{
			if ( Login::registrationType() == 'redirect' )
			{
				Output::i()->redirect( Url::external( Settings::i()->allow_reg_target ) );
			}
			elseif ( Login::registrationType() == 'disabled' )
			{
				Output::i()->error( 'reg_disabled', '2S129/5', 403, '' );
			}
		}
		
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		if ( isset( Request::i()->oauth ) )
		{
			Output::i()->bodyClasses[] = 'ipsLayout_minimalNoHome';
		}
		Output::i()->sidebar['enabled'] = FALSE;
		Output::setCacheTime( false );
		Output::i()->linkTags['canonical'] = (string) Url::internal( 'app=core&module=system&controller=register', 'front', 'register' );
	}
	
	/**
	 * Register
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if( !Settings::i()->site_online )
		{
			Output::i()->showOffline();
		}

		if( Application::appIsEnabled( 'nexus' ) )
		{
			if ( Member::loggedIn()->canAccessModule( Module::get( 'nexus', 'store' ) ) and ( Settings::i()->nexus_reg_force or !isset( Request::i()->noPurchase ) ) and Package::haveRegistrationProducts() )
			{
				Output::i()->redirect( Url::internal( 'app=nexus&module=store&controller=store&do=register', 'front', 'store' ) );
			}
			else if ( Member::loggedIn()->canAccessModule( Module::get( 'nexus', 'subscriptions' ) ) and ( Settings::i()->nexus_subs_register ) )
			{
				Output::i()->redirect( Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions&register=1', 'front', 'nexus_subscriptions' ) );
			}
		}

		/* Set Session Location */
		Session::i()->setLocation( Url::internal( 'app=core&module=system&controller=register', NULL, 'register' ), array(), 'loc_registering' );
		Output::i()->allowDefaultWidgets = FALSE;
		
		/* What's the "log in" link? */
		$loginUrl = Url::internal( 'app=core&module=system&controller=login', NULL, 'login' );
		if ( isset( Request::i()->oauth ) and $ref = static::_refUrl() and $ref->base === 'none' )
		{
			$loginUrl = $ref;
		}
		
		/* Post before registering? */
		$postBeforeRegister = NULL;
		if ( isset( Request::i()->cookie['post_before_register'] ) or isset( Request::i()->pbr ) )
		{
			try
			{
				$postBeforeRegister = Db::i()->select( '*', 'core_post_before_registering', array( 'secret=?', Request::i()->pbr ?: Request::i()->cookie['post_before_register'] ) )->first();
			}
			catch ( UnderflowException $e ) { }
		}
		
		/* Quick registration does not work with COPPA */
		if ( Login::registrationType() == 'normal' )
		{
			$form = $this->_registrationForm( $postBeforeRegister );
			$form->class = 'ipsForm_fullWidth';

			Output::i()->title	= Member::loggedIn()->language()->addToStack('registration');

			if( Request::i()->isAjax() )
			{
				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupRegisterTemplate' ), new Login( $loginUrl, Login::LOGIN_REGISTRATION_FORM ), $postBeforeRegister  );
			}
			else
			{
				Output::i()->output = Theme::i()->getTemplate( 'system' )->register( $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupRegisterTemplate' ), new Login( $loginUrl, Login::LOGIN_REGISTRATION_FORM ), $postBeforeRegister ), new Login( $loginUrl ), $postBeforeRegister );
			}
			
			return;
		}
				
		if( isset( $_SESSION['coppa_user'] ) AND ( Settings::i()->use_coppa OR Settings::i()->minimum_age > 0 ) )
		{
			if ( Settings::i()->minimum_age > 0 )
			{
				$message = Member::loggedIn()->language()->addToStack( 'register_denied_age', FALSE, array( 'sprintf' => array( Settings::i()->minimum_age ) ) );
				Output::i()->error( $message, '2C223/7', 403, '' );
			}
			else
			{
				Output::i()->title = Member::loggedIn()->language()->addToStack('reg_awaiting_validation');
				Output::i()->output = Theme::i()->getTemplate( 'system' )->notCoppaValidated();
				return;
			}
		}
		
		/* Set up the step array */
		$steps = array();
				
		/* If coppa is enabled we need to add a birthday verification */
		if ( Settings::i()->use_coppa OR Settings::i()->minimum_age > 0 )
		{
			$steps['coppa'] = function( $data ) use ( $postBeforeRegister )
			{
				/* Build the form */
				$form = new Form( 'coppa', 'register_button' );
				$form->add( new Date( 'bday', NULL, TRUE, array( 'max' => DateTime::create(), 'htmlAutocomplete' => "bday" ) ) );

				if( $values = $form->values() )
				{
					/* Did we pass the minimum age requirement? */
					if ( Settings::i()->minimum_age > 0 AND $values['bday']->diff( DateTime::create() )->y < Settings::i()->minimum_age )
					{
						$_SESSION['coppa_user'] = TRUE;
						
						$message = Member::loggedIn()->language()->addToStack( 'register_denied_age', FALSE, array( 'sprintf' => array( Settings::i()->minimum_age ) ) );
						Output::i()->error( $message, '2C223/8', 403, '' );
					}
					/* We did, but we should check normal COPPA too */
					else if( ( $values['bday']->diff( DateTime::create() )->y < 13 ) )
					{
						$_SESSION['coppa_user'] = TRUE;
						return Output::i()->output = Theme::i()->getTemplate( 'system' )->notCoppaValidated();
					}
								
					return $values;
				}
				
				return Output::i()->output = Theme::i()->getTemplate( 'system' )->coppa( $form, $postBeforeRegister );
			};
		}

		$self = $this;
		
		$steps['basic_info'] = function ( $data ) use ( $self, $postBeforeRegister, $loginUrl )
		{
			$form = $self->_registrationForm( $postBeforeRegister );

			if( is_array( $form ) )
			{
				return $form;
			}

			return Output::i()->output = Theme::i()->getTemplate( 'system' )->register( $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupRegisterTemplate' ), new Login( $loginUrl, Login::LOGIN_REGISTRATION_FORM ), $postBeforeRegister ), new Login( $loginUrl, Login::LOGIN_REGISTRATION_FORM ), $postBeforeRegister );
		};

		/* Do we have any profile completion steps marked for registration? */
		foreach( ProfileStep::loadAll() as $step )
		{
			if( $step->registration )
			{
				$extension = $step->extension;
				foreach( $extension::wizard() as $key => $form )
				{
					if( $key == $step->key )
					{
						$steps[ $key ] = $form;
					}
				}
			}
		}

		/* If we have more than one step, add a redirect step at the end */
		if( count( $steps ) > 1 )
		{
			$steps['profile_done'] = function( $data ) use( $postBeforeRegister )
			{
				$this->_performRedirect( $postBeforeRegister );
			};
		}
		
		/* Output */
		Output::i()->title	= Member::loggedIn()->language()->addToStack('registration');
		Output::i()->output = (string) new Wizard( $steps, Url::internal( 'app=core&module=system&controller=register' ), FALSE );
	}
	
	/**
	 * Normal registration form
	 *
	 * @param	array|NULL	$postBeforeRegister	The row from core_post_before_registering if applicable
	 * @return Form|array
	 */
	protected function _registrationForm( ?array $postBeforeRegister ) : Form|array
	{
		$form = static::buildRegistrationForm( $postBeforeRegister );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$profileFields = array();

			if( Login::registrationType() == 'full' )
			{
				foreach ( Field::fields( array(), Field::REG ) as $group => $fields )
				{
					foreach ( $fields as $id => $field )
					{
						if ( $field instanceof Upload )
						{
							$profileFields[ "field_{$id}" ] = (string) $values[ $field->name ];
						}
						else
						{
							$profileFields[ "field_{$id}" ] = $field::stringValue( !empty( $values[ $field->name ] ) ? $values[ $field->name ] : NULL );
						}
					}
				}
			}

			if ( Settings::i()->security_questions_enabled and ( Settings::i()->security_questions_prompt === 'register' or ( Settings::i()->security_questions_prompt === 'optional' and !$values['security_questions_optout_title'] ) ) )
			{
				$answers = array();
				foreach ( $values as $k => $v )
				{
					if ( preg_match( '/^security_question_q_(\d+)$/', $k, $matches ) )
					{
						if ( isset( $answers[ $v ] ) )
						{
							$form->error = Member::loggedIn()->language()->addToStack( 'security_questions_unique', FALSE, array( 'pluralize' => array( Settings::i()->security_questions_number ?: 3 ) ) );
							break;
						}
						else
						{
							$answers[ $v ] = $v;
						}
					}
				}
			}
			
			if ( !$form->error )
			{
				/* Set referral cookie */
				if( isset( $values['referred_by'] ) and !isset( Request::i()->cookie[ 'referred_by' ] ) )
				{
					Request::i()->setCookie( 'referred_by', ( $values['referred_by'] instanceof Member ? $values['referred_by']->member_id : $values['referred_by'] ) );
				}

				/* Create Member */
				$member = static::_createMember( $values, $profileFields, $postBeforeRegister, $form );

				/* Form Extensions */
				Form::saveExtensionFields( Form::FORM_REGISTRATION, $values, [ $member ] );
				
				/* Log them in */
				Session::i()->setMember( $member );
				Device::loadOrCreate( $member, FALSE )->updateAfterAuthentication( TRUE );

				/* If we have profile steps on registration, return instead of redirect */
				foreach( ProfileStep::loadAll() as $step )
				{
					if( $step->registration )
					{
						return [ 'member' => $member->member_id ];
					}
				}

				/* Redirect */
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( array( 'redirect' => (string) $this->_performRedirect( $postBeforeRegister, TRUE ) ) );
				}
				else
				{
					$this->_performRedirect( $postBeforeRegister );
				}
			}
		}
		
		return $form;
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish() : void
	{
		/* You must be logged in for this action */
		if( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission', '2C223/B', 403, '' );
		}

		$steps = ProfileStep::loadAll();
		
		/* Do we need to bother? We should only show this form if there are required items, but will show both required and suggested where possible to allow the user to complete as much of their profile as possible */
		if( !isset( Request::i()->finishStarted ) )
		{
			$haveRequired = FALSE;
			foreach( $steps AS $id => $step )
			{
				if ( $step->required AND $step->canComplete() AND !$step->completed( Member::loggedIn() ) )
				{
					$haveRequired = TRUE;
					break;
				}
			}
			
			if ( $haveRequired === FALSE )
			{
				/* Nope, forward */
				$this->_performRedirect();
			}

			/* Make sure we reset any temp data that might have been stored in the session */
			if( isset( $_SESSION['profileCompletionData'] ) )
			{
				unset( $_SESSION['profileCompletionData'] );
			}
		}

		$wizardSteps = array();
		$url = Url::internal( 'app=core&module=system&controller=register&do=finish&finishStarted=1', 'front', 'register' )->setQueryString( 'ref', Request::i()->ref );

		foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $extension )
		{
			if ( is_array( $extension::wizard() ) AND count( $extension::wizard() ) )
			{
				$wizardSteps = array_merge( $wizardSteps, $extension::wizard() );
			}
			if ( method_exists( $extension, 'extraStep') AND count( $extension::extraStep() ) )
			{
				$wizardSteps = array_merge( $wizardSteps, $extension::extraStep() );
			}
		}

		$wizardSteps = ProfileStep::setOrder( $wizardSteps );

		$wizardSteps = array_merge( $wizardSteps, array( 'profile_done' => function( $data ) {
			$this->_performRedirect( NULL, FALSE, 'saved' );
		} ) );
		
		$wizard = new Wizard( $wizardSteps, $url, TRUE, NULL, TRUE );
		$wizard->template = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'completeWizardTemplate' );
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/settings.css' ) );
		Output::i()->title	= Member::loggedIn()->language()->addToStack('complete_profile_registration');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->finishRegistration( (string) $wizard );
	}

	/**
	 * Build Registration Form
	 *
	 * @param	array|NULL	$postBeforeRegister	The row from core_post_before_registering if applicable
	 * @return	Form
	 */
	public static function buildRegistrationForm( ?array $postBeforeRegister = NULL ) : Form
	{				
		/* Build the form */
		$form = new Form( 'form', 'register_button', NULL, array( 'data-controller' => 'core.front.system.register') );
		$form->add( new Text( 'username', NULL, TRUE, array( 'accountUsername' => TRUE, 'htmlAutocomplete' => "username" ) ) );
		$form->add( new FormEmail( 'email_address', $postBeforeRegister ? $postBeforeRegister['email'] : NULL, TRUE, array( 'accountEmail' => TRUE, 'maxLength' => 150, 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "email" ) ) );
		$form->add( new Password( 'password', NULL, TRUE, array( 'protect' => TRUE, 'showMeter' => Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'strengthRequest' => array( 'username', 'email_address' ), 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "new-password" ) ) );
		$form->add( new Password( 'password_confirm', NULL, TRUE, array( 'protect' => TRUE, 'confirm' => 'password', 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "new-password" ) ) );
	
		/* Profile fields */
		if ( Login::registrationType() == 'full' )
		{
			foreach ( Field::fields( array(), Field::REG ) as $group => $fields )
			{
				foreach ( $fields as $field )
				{
					$form->add( $field );
				}
			}
			$form->addSeparator();
		}
		else
		{
			$form->class = 'ipsForm--vertical ipsForm--registration-form';
		}
		
		$question = FALSE;
		try
		{
			$question = Db::i()->select( '*', 'core_question_and_answer', NULL, "RAND()" )->first();
		}
		catch ( UnderflowException $e ) {}
		
		/* 2FA Q&A? */
		static::addQuestion2FA( $form );
		
		/* Random Q&A */
		if( $question )
		{
			$form->hiddenValues['q_and_a_id'] = $question['qa_id'];
	
			$form->add( new Text( 'q_and_a', NULL, TRUE, array(), function( $val )
			{
				$qanda  = intval( Request::i()->q_and_a_id );
				$pass = true;
			
				if( $qanda )
				{
					try
					{
						$question = Db::i()->select( '*', 'core_question_and_answer', array( 'qa_id=?', $qanda ) )->first();
					}
					catch( UnderflowException $e )
					{
						throw new DomainException( 'q_and_a_incorrect' );
					}

					$answers = json_decode( $question['qa_answers'], true );

					if( $answers )
					{
						$answers = is_array( $answers ) ? $answers : array( $answers );
						$pass = FALSE;
					
						foreach( $answers as $answer )
						{
							$answer = trim( $answer );

							if( mb_strlen( $answer ) AND mb_strtolower( $answer ) == mb_strtolower( $val ) )
							{
								$pass = TRUE;
							}
						}
					}
				}
				else
				{
					$questions = Db::i()->select( 'count(*)', 'core_question_and_answer', 'qa_id > 0' )->first();
					if( $questions )
					{
						$pass = FALSE;
					}
				}
				
				if( !$pass )
				{
					throw new DomainException( 'q_and_a_incorrect' );
				}
			} ) );
			
			/* Set the form label */
			Member::loggedIn()->language()->words['q_and_a'] = Member::loggedIn()->language()->addToStack( 'core_question_and_answer_' . $question['qa_id'], FALSE );
		}

		if( Settings::i()->ref_on and Settings::i()->ref_member_input and !isset( Request::i()->cookie[ 'referred_by' ] ) )
		{
			$form->add( new FormMember( 'referred_by', NULL, FALSE, array( 'autocomplete' => array( 'lang' => 'referred_by_button' ) ) ) );
		}
		
		$captcha = new Captcha;
		
		if ( (string) $captcha !== '' )
		{
			$form->add( $captcha );
		}
		
		if ( $question OR (string) $captcha !== '' )
		{
			$form->addSeparator();
		}
		
		$form->add( new Checkbox( 'reg_admin_mails', Settings::i()->updates_consent_default == 'enabled' or Request::i()->newsletter, FALSE ) );

		static::buildRegistrationTerm();
		
		$form->add( new Checkbox( 'reg_agreed_terms', NULL, TRUE, array(), function( $val )
		{
			if ( !$val )
			{
				throw new InvalidArgumentException('reg_not_agreed_terms');
			}
		} ) );

		/* Check for extensions */
		$form->addExtensionFields( Form::FORM_REGISTRATION );
		
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_system.js', 'core', 'front' ), Output::i()->js( 'front_templates.js', 'core', 'front' ) );
		
		return $form;
	}
	
	/**
	 * Add in the Q&A 2FA if it is enforced
	 *
	 * @param	Form	$form		Form
	 * @return void
	 */
	protected static function addQuestion2FA( Form &$form ) : void
	{
		/* Security Questions */
		if ( Settings::i()->security_questions_enabled and in_array( Settings::i()->security_questions_prompt, array( 'register', 'optional' ) ) )
		{
			$numberOfQuestions = Settings::i()->security_questions_number ?: 3;
			$securityQuestions = array();
			foreach ( Question::roots() as $securityQuestion )
			{
				$securityQuestions[ $securityQuestion->id ] = $securityQuestion->_title;
			}
			
			$form->addMessage( Member::loggedIn()->language()->addToStack('security_questions_setup_blurb', FALSE, array( 'pluralize' => array( $numberOfQuestions ) ) ) );
			
			if ( Settings::i()->security_questions_prompt === 'optional' )
			{
				$securityOptoutToggles = array();
				foreach ( range( 1, min( $numberOfQuestions, count( $securityQuestions ) ) ) as $i )
				{
					$securityOptoutToggles[] = 'security_question_q_' . $i;
					$securityOptoutToggles[] = 'security_question_a_' . $i;
				}
				
				$optOutCheckbox = new Checkbox( 'security_questions_optout_title', FALSE, FALSE, array( 'togglesOff' => $securityOptoutToggles ) );
				if ( Member::loggedIn()->language()->checkKeyExists('security_questions_opt_out_warning_value') )
				{
					$optOutCheckbox->description = Member::loggedIn()->language()->addToStack('security_questions_opt_out_warning_value', TRUE, array( 'returnBlank' => TRUE ) );
				}
				$form->add( $optOutCheckbox );
			}
			foreach ( range( 1, min( $numberOfQuestions, count( $securityQuestions ) ) ) as $i )
			{
				$securityValidation = function( $val ) {
					if ( !$val and ( Settings::i()->security_questions_prompt === 'register' or !isset( Request::i()->security_questions_optout_title_checkbox ) ) )
					{
						throw new DomainException('form_required');
					}
				};
				
				$questionField = new Select( 'security_question_q_' . $i, NULL, FALSE, array( 'options' => $securityQuestions ), $securityValidation, NULL, NULL, 'security_question_q_' . $i );
				$questionField->label = Member::loggedIn()->language()->addToStack('security_question_q');
	
				$answerField = new Text( 'security_question_a_' . $i, NULL, NULL, array(), $securityValidation, NULL, NULL, 'security_question_a_' . $i );
				$answerField->label = Member::loggedIn()->language()->addToStack('security_question_a');
				
				$form->add( $questionField );
				$form->add( $answerField );
			}
			$form->addSeparator();
		}
	}

	/**
	 * Create Member
	 *
	 * @param	array 				$values   		    Values from form
	 * @param	array				$profileFields		Profile field values from registration
	 * @param	array|NULL			$postBeforeRegister	The row from core_post_before_registering if applicable
	 * @param	Form	$form				The form object
	 * @return  Member
	 */
	public static function _createMember( array $values, array $profileFields, ?array $postBeforeRegister, Form &$form ) : Member
	{
		/* Create */
		$member = new Member;
		$member->name	   = $values['username'];
		$member->email		= $values['email_address'];
		$member->setLocalPassword( $values['password'] );
		$member->allow_admin_mails  = $values['reg_admin_mails'];
		$member->member_group_id	= Settings::i()->member_group;
		$member->members_bitoptions['view_sigs'] = TRUE;
		$member->last_visit = time();
		
		if( isset( Request::i()->cookie['language'] ) AND Request::i()->cookie['language'] )
		{
			$member->language = Request::i()->cookie['language'];
		}
		elseif ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) )
		{
			$member->language = Lang::autoDetectLanguage( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
		}
		
		/* Query spam service */
		$spamCode = NULL;
		$spamAction = NULL;
		$disposable = FALSE;
		$geoBlock = FALSE;

		if( Settings::i()->spam_service_enabled )
		{
			$spamAction = $member->spamService( 'register', NULL, $spamCode, $disposable, $geoBlock );
			if( $spamAction == 4 )
			{
				Output::i()->error( 'spam_denied_account', '2S129/1', 403, '' );
			}
		}
		
		if ( Settings::i()->allow_reg != 'disabled' )
		{
			/* Initial Save */
			$member->save();
			
			/* This looks a bit weird, but the extensions expect an account to exist at this point, so we'll let the system save it now, then do what we need to do, then save again */
			foreach( ProfileStep::loadAll() AS $step )
			{
				if( !$step->registration )
				{
					$extension = $step->extension;
					$extension::formatFormValues( $values, $member, $form );
				}
			}
		}
		
		/* Save anything the profile extensions did */
		$member->save();
		$member->logHistory( 'core', 'account', array( 'type' => 'register', 'spamCode' => $spamCode, 'spamAction' => $spamAction, 'disposable' => $disposable, 'geoBlock' => $geoBlock ), FALSE );
		
		/* Security Questions */
		if ( Settings::i()->security_questions_enabled and in_array( Settings::i()->security_questions_prompt, array( 'register', 'optional' ) ) )
		{
			if ( isset( $values['security_questions_optout_title'] ) )
			{
				$member->members_bitoptions['security_questions_opt_out'] = TRUE;

				/* Log MFA Opt-out */
				$member->logHistory( 'core', 'mfa', array( 'handler' => 'questions', 'enable' => FALSE, 'optout' => TRUE ) );
			}
			else
			{
				$answers = array();
				
				foreach ( $values as $k => $v )
				{
					if ( preg_match( '/^security_question_q_(\d+)$/', $k, $matches ) )
					{
						$answers[ $v ] = array(
							'answer_question_id'	=> $v,
							'answer_member_id'		=> $member->member_id,
							'answer_answer'			=> Encrypt::fromPlaintext( $values[ 'security_question_a_' . $matches[1] ] )->tag()
						);
					}
				}
								
				if ( count( $answers ) )
				{
					Db::i()->insert( 'core_security_answers', $answers );
				}
				
				$member->members_bitoptions['has_security_answers'] = TRUE;

				/* Log MFA Enable */
				$member->logHistory( 'core', 'mfa', array( 'handler' => 'questions', 'enable' => TRUE ) );
			}
			$member->save();
		}

		/* Cycle profile fields */
		foreach( $profileFields as $id => $fieldValue )
		{
			$field = Field::loadWithMember( mb_substr( $id, 6 ) );
			if( $field->type == 'Editor' )
			{
				$field->claimAttachments( $member->member_id );
			}
		}

		/* Save custom field values */
		Db::i()->replace( 'core_pfields_content', array_merge( array( 'member_id' => $member->member_id ), $profileFields ) );
		
		/* Log that we gave consent for admin emails */
		$member->logHistory( 'core', 'admin_mails', array( 'enabled' => (boolean) $member->allow_admin_mails ) );
		
		/* Log that we gave consent for terms and privacy */
		if ( Settings::i()->privacy_type != 'none' )
		{
			$member->logHistory( 'core', 'terms_acceptance', array( 'type' => 'privacy' ) );
		}
		
		$member->logHistory( 'core', 'terms_acceptance', array( 'type' => 'terms' ) );
			
		/* Handle validation, but not if we were flagged as a spammer and banned */
		if( $spamAction != 3 )
		{
			$member->postRegistration( FALSE, FALSE, $postBeforeRegister, static::_refUrl() );
		}

		/* Save and return */
		return $member;
	}
	
	/**
	 * A printable coppa form
	 *
	 * @return	void
	 */
	protected function coppaForm() : void
	{
		$output = Theme::i()->getTemplate( 'system' )->coppaConsent();
		Member::loggedIn()->language()->parseOutputForDisplay( $output );
		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ) );
	}

	/**
	 * Awaiting Validation
	 *
	 * @return	void
	 */
	protected function validating() : void
	{
		if( !Member::loggedIn()->member_id )
		{
			Output::i()->redirect( Url::internal( '' ) );
		}
		
		/* Fetch the validating record to see what we're dealing with */
		try
		{
			$validating = Db::i()->select( '*', 'core_validating', array( 'member_id=? AND ( new_reg=? OR email_chg=? )', Member::loggedIn()->member_id, 1, 1 ) )->first();
		}
		catch ( UnderflowException $e )
		{
			/* Reset the validation flag and redirect the member to the index page if we have no row */
			if( Member::loggedIn()->members_bitoptions['validating'] )
			{
				Member::loggedIn()->members_bitoptions['validating'] = FALSE;
				Member::loggedIn()->save();
				$this->_performRedirect( NULL, FALSE, 'validate_no_record' );
			}

			Output::i()->error( 'validate_no_record', '2S129/4', 404, '' );
		}
		
		/* They're not validated but in what way? */
		if ( $validating['reg_cancelled'] )
		{
			/* They are cancelled and will be deleted, haha, etc */
			Output::i()->error( 'reg_is_cancelled', '2C223/9', 403, '' );
		}
		else if ( $validating['user_verified'] )
		{
			Output::i()->output = Theme::i()->getTemplate( 'system' )->notAdminValidated();
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'system' )->notValidated( $validating );
		}
		
		/* Display */
		Output::i()->title	= Member::loggedIn()->language()->addToStack('reg_awaiting_validation');
	}
	
	/**
	 * Resend validation email
	 *
	 * @return	void
	 */
	protected function resend() : void
	{
		Session::i()->csrfCheck();

		$validating = Db::i()->select( '*', 'core_validating', array( 'member_id=?', Member::loggedIn()->member_id ) );
	
		if ( !count( $validating ) )
		{
			Output::i()->error( 'validate_no_record', '2S129/3', 404, '' );
		}
	
		foreach( $validating as $reg )
		{
			if ( $reg['email_sent'] and $reg['email_sent'] > ( time() - 900 ) )
			{
				Output::i()->error( Member::loggedIn()->language()->addToStack('validation_email_rate_limit', FALSE, array( 'sprintf' => array( DateTime::ts( $reg['email_sent'] )->relative( DateTime::RELATIVE_FORMAT_LOWER ) ) ) ), '1C223/4', 429, '', array( 'Retry-After' => DateTime::ts( $reg['email_sent'] )->add( new DateInterval( 'PT15M' ) )->format('r') ) );
			}

			/* Rotate security key */
			$plainSecurityKey = Login::generateRandomString();
			Db::i()->update( 'core_validating', [ 'security_key' => Encrypt::fromPlaintext( $plainSecurityKey )->tag(), 'email_sent' => time() ], [ 'vid=?', $reg['vid'] ] );
			
			Email::buildFromTemplate( 'core', $reg['email_chg'] ? 'email_change' : 'registration_validate', array( Member::loggedIn(), $reg['vid'], $plainSecurityKey ), Email::TYPE_TRANSACTIONAL )->send( Member::loggedIn() );
		}
		
		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' ), 'reg_email_resent' );
	}
	
	/**
	 * Validate
	 *
	 * @return	void
	 */
	protected function validate() : void
	{
		/* Prevent the vid key from being exposed in referrers */
		Output::i()->sendHeader( "Referrer-Policy: origin" );

		if( Request::i()->vid AND Request::i()->mid )
		{
			/* Load record */
			try
			{
				$record = Db::i()->select( '*', 'core_validating', array( 'vid=? AND member_id=? AND ( new_reg=? or email_chg=? )', Request::i()->vid, Request::i()->mid, 1, 1 ) )->first();
			}
			catch ( UnderflowException $e )
			{
				$this->_performRedirect( NULL, FALSE, 'validate_no_record' );
			}

			/* Check security key is valid. */
			if( !Login::compareHashes( Encrypt::fromTag( $record['security_key'] )->decrypt(), Request::i()->security_key ) )
			{
				Output::i()->error( 'validate_invalid_security_key', '2C223/I', 403, '' );
			}

			if ( isset( $record['ref'] ) )
			{
				Request::i()->ref = base64_encode( $record['ref'] );
			}

			/* If this is a new registration and the user has already validated their email, redirect */
			if ( $record['new_reg'] AND $record['user_verified'] )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' ), 'reg_email_already_validated_admin' );
			}

			$member = Member::load( Request::i()->mid );
			
			/* Post before registering? */
			try
			{
				$postBeforeRegister = Db::i()->select( '*', 'core_post_before_registering', array( '`member`=?', $member->member_id ) )->first();
			}
			catch ( UnderflowException $e )
			{
				$postBeforeRegister = NULL;
			}

			/* Ask the user to confirm - this prevents spiders and similar scrapers seeing the link and following it without the user's knowledge */
			$form = new Form( 'form', 'validate_my_account' );
			$form->hiddenValues['custom'] = 'submitted';

			if( $submitted = $form->values() )
			{
				/* Validate */
				if ( $record['new_reg'] )
				{
					$member->emailValidationConfirmed( $record );
				}
				else
				{
					$member->members_bitoptions['validating'] = FALSE;
					$member->save();

					Event::fire( 'onEmailChange', $member, array( $member->email, $record['prev_email'] ) );
					
					Db::i()->delete( 'core_validating', array( 'member_id=?', $member->member_id ) );

					/* Send a confirmation email */
					Email::buildFromTemplate( 'core', 'email_address_changed', array( $member, $record['prev_email'] ), Email::TYPE_TRANSACTIONAL )->send( $record['prev_email'], array(), array(), NULL, NULL, array( 'Reply-To' => Settings::i()->email_in ) );
				}
				
				/* Log in */
				Session::i()->setMember( $member );
				Device::loadOrCreate( $member )->updateAfterAuthentication( TRUE );
				
				/* Redirect */
				$this->_performRedirect( $postBeforeRegister, FALSE, 'validate_email_confirmation' );
			}

			Output::i()->title	= Member::loggedIn()->language()->addToStack('reg_complete_details');
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'completeValidation' ), $member );
			return;
		}

		/* If we're still here, just redirect to homepage */
		$this->_performRedirect( NULL, FALSE, 'validate_no_record' );
	}

	/**
	 * Complete Profile
	 *
	 * @return	void
	 */
	protected function complete() : void
	{
		/* Check we are an incomplete member */
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
		elseif ( Member::loggedIn()->real_name and Member::loggedIn()->email )
		{
			/* If we somehow came here from the oauth authorization prompt but the member, redirect back there */
			if ( isset( Request::i()->oauth ) and $ref = static::_refUrl() and $ref->base === 'none' )
			{
				Output::i()->redirect( $ref );
			}
			Output::i()->redirect( Url::internal( '' ) );
		}
				
		/* Build the form */
		$form = new Form( 'form', 'register_button' );
		if ( isset( Request::i()->ref ) )
		{
			$form->hiddenValues['ref'] = Request::i()->ref;
		}
		if( !Member::loggedIn()->real_name OR Member::loggedIn()->name === Member::loggedIn()->language()->get('guest') )
		{
			$form->add( new Text( 'username', NULL, TRUE, array( 'accountUsername' => Member::loggedIn() ) ) );
		}
		if( !Member::loggedIn()->email )
		{
			$form->add( new FormEmail( 'email_address', NULL, TRUE, array( 'accountEmail' => TRUE ) ) );
		}
		
		$form->add( new Checkbox( 'reg_admin_mails', Settings::i()->updates_consent_default == 'enabled', FALSE ) );
		
		static::buildRegistrationTerm();
			
		$form->add( new Checkbox( 'reg_agreed_terms', NULL, TRUE, array(), function( $val )
		{
			if ( !$val )
			{
				throw new InvalidArgumentException('reg_not_agreed_terms');
			}
		} ) );
		
		$form->addButton( 'cancel', 'link', Url::internal( 'app=core&module=system&controller=register&do=cancel', 'front', 'register' )->csrf() );

		/* Handle the submission */
		if ( $values = $form->values() )
		{
			if( isset( $values['username'] ) )
			{
				Member::loggedIn()->name = $values['username'];
			}
			$spamCode = NULL;
			$spamAction = NULL;
			$disposable = FALSE;
			$geoBlock = FALSE;
			if( isset( $values['email_address'] ) )
			{
				Member::loggedIn()->email = $values['email_address'];

				if( Settings::i()->spam_service_enabled )
				{
					$spamAction = Member::loggedIn()->spamService( 'register', NULL, $spamCode );
					if( $spamAction == 4 )
					{
						$action = Settings::i()->spam_service_action_4;

						/* Any other action will automatically be handled by the call to spamService() */
						if( $action == 4 )
						{
							Member::loggedIn()->delete();
						}

						Output::i()->error( 'spam_denied_account', '2S272/1', 403, '' );
					}
				}
			}
			Member::loggedIn()->members_bitoptions['must_reaccept_terms'] = FALSE;
			Member::loggedIn()->allow_admin_mails  = $values['reg_admin_mails'];

			/* We should run geolocation again, this may have been an account created via login handler that has since changed details - check for admin validation */
			if( !$spamAction )
			{
				Member::loggedIn()->geoSpamCheck( $geoBlock );
			}

			/* Save */
			Member::loggedIn()->save();
			/* Log that we gave consent for admin emails */
			Member::loggedIn()->logHistory( 'core', 'admin_mails', array( 'enabled' => (boolean) Member::loggedIn()->allow_admin_mails ) );

			/* Log that we gave consent for terms and privacy */
			if ( Settings::i()->privacy_type != 'none' )
			{
				Member::loggedIn()->logHistory( 'core', 'terms_acceptance', array( 'type' => 'privacy' ) );
			}

			/* Log that the terms were accepted */
			Member::loggedIn()->logHistory( 'core', 'terms_acceptance', array( 'type' => 'terms' ) );
			Member::loggedIn()->logHistory( 'core', 'account', array( 'type' => 'complete' ), FALSE );
			
			/* Handle validation */
			$postBeforeRegister = NULL;
			if ( isset( Request::i()->cookie['post_before_register'] ) )
			{
				try
				{
					$postBeforeRegister = Db::i()->select( '*', 'core_post_before_registering', array( 'secret=?', Request::i()->cookie['post_before_register'] ) )->first();
				}
				catch ( UnderflowException $e ) { }
			}
			Member::loggedIn()->postRegistration( !isset( $values['email_address'] ), FALSE, $postBeforeRegister, static::_refUrl() );
			
			/* Set member as a full member in the session table */
			Session::i()->setType( Front::LOGIN_TYPE_MEMBER );
			
			/* Redirect */
			$this->_performRedirect( $postBeforeRegister );
		}

		Output::i()->title	= Member::loggedIn()->language()->addToStack('reg_complete_details');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->completeProfile( $form );
	}

	/**
	 * Change Email
	 *
	 * @return	void
	 */
	protected function changeEmail() : void
	{
		/* Are we logged in and pending validation? */
		if( !Member::loggedIn()->member_id OR !Member::loggedIn()->members_bitoptions['validating'] )
		{
			Output::i()->error( 'no_module_permission', '2C223/2', 403, '' );
		}

		/* Do we have any pending validation emails? */
		try
		{
			$pending = Db::i()->select( '*', 'core_validating', array( 'member_id=? AND ( new_reg=1 or email_chg=1 )', Member::loggedIn()->member_id ), 'entry_date DESC' )->first();
		}
		catch( UnderflowException $e )
		{
			$pending = null;
		}

		/* If we're a new registration, no longer allow email addresses to be changed. */
		if ( $pending and $pending['new_reg'] )
		{
			Output::i()->error( 'no_module_permission', '2C223/6', 403, '' );
		}
				
		/* Build the form */
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--change-email';
		$form->add( new FormEmail( 'new_email', '', TRUE, array( 'accountEmail' => Member::loggedIn(), 'htmlAutocomplete' => "email" ) ) );
		$captcha = new Captcha;
		if ( (string) $captcha !== '' )
		{
			$form->add( $captcha );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Check spam defense whitelist */
			if( Settings::i()->spam_service_enabled AND ( isset( $pending['new_reg'] ) AND $pending['new_reg'] ) AND Member::loggedIn()->spamDefenseWhitelist() )
			{
				/* We specifically say it's 'register' so that actions are still performed on the account */
				$newEmailScore = Member::loggedIn()->spamService( 'register', $values['new_email'] );

				/* Is it a ban response? */
				if( $newEmailScore == 4 )
				{
					Output::i()->error( 'spam_denied_account', '2C223/A', 403, '' );
				}
			}

			/* Change the email */
			$oldEmail = Member::loggedIn()->email;
			Member::loggedIn()->email = $values['new_email'];
			Member::loggedIn()->save();
			foreach ( Login::methods() as $method )
			{
				try
				{
					$method->changeEmail( Member::loggedIn(), $oldEmail, $values['new_email'] );
				}
				catch( BadMethodCallException $e ) {}
			}

			Member::loggedIn()->logHistory( 'core', 'email_change', array( 'old' => $oldEmail, 'new' => $values['new_email'], 'by' => 'manual' ) );
			
			/* Invalidate sessions except this one */
			Member::loggedIn()->invalidateSessionsAndLogins( Session::i()->id );
			if( isset( Request::i()->cookie['login_key'] ) )
			{
				Device::loadOrCreate( Member::loggedIn() )->updateAfterAuthentication( TRUE );
			}
			
			/* If email validation is required, do that... */
			if ( in_array( Settings::i()->reg_auth_type, array( 'user', 'admin_user' ) ) )
			{
				/* Delete any pending validation emails */
				if ( $pending['vid'] )
				{
					Db::i()->delete( 'core_validating', array( 'member_id=? AND ( new_reg=1 or email_chg=1 )', Member::loggedIn()->member_id ) );
				}
			
				$vid = Login::generateRandomString();
				$plainSecurityKey = Login::generateRandomString();
		
				Db::i()->insert( 'core_validating', [
					'vid'			=> $vid,
					'member_id'		=> Member::loggedIn()->member_id,
					'entry_date'	=> time(),
					'new_reg'		=> !$pending or $pending['new_reg'],
					'email_chg'		=> $pending and $pending['email_chg'],
					'user_verified'	=> ( Settings::i()->reg_auth_type == 'admin' ),
					'ip_address'	=> Request::i()->ipAddress(),
					'email_sent'	=> time(),
					'security_key'  => Encrypt::fromPlaintext( $plainSecurityKey )->tag()
				] );
							
				Email::buildFromTemplate( 'core', $pending['email_chg'] ? 'email_change' : 'registration_validate', array( Member::loggedIn(), $vid, $plainSecurityKey ), Email::TYPE_TRANSACTIONAL )->send( Member::loggedIn() );
			}
			else
			{
				Event::fire( 'onEmailChange', Member::loggedIn(), array( $values['new_email'], $oldEmail ) );
			}
			
			/* Redirect */				
			Output::i()->redirect( Url::internal( '' ) );
		}
		
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Cancel Registration
	 *
	 * @return	void
	 */
	protected function cancel() : void
	{
		/* This bit is kind of important - don't allow externally created accounts to be deleted, they could already have commerce data */
		Session::i()->csrfCheck();
		if ( (Member::loggedIn()->name and Member::loggedIn()->email
			and !Db::i()->select( 'COUNT(*)', 'core_validating', array( 'member_id=? AND new_reg=1', Member::loggedIn()->member_id ) )->first() )
			OR Member::loggedIn()->members_bitoptions['created_externally'] )
		{
			Output::i()->error( 'no_module_permission', '2C223/1', 403, '' );
		}

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete( 'reg_cancel', 'reg_cancel_confirm', 'reg_cancel' );

		/* Log the user out. Previously, we immediately deleted the account however this has been changed to let the cleanup task handle this instead. */
		Login::logout();
				
		/* Flag user as having cancelled their registration */
		Db::i()->update( 'core_validating', array( 'reg_cancelled' => time() ), array( 'member_id=? AND new_reg=1', Member::loggedIn()->member_id ) );
		
		/* Redirect */
		Output::i()->redirect( Url::internal( '' ), 'reg_canceled' );
	}
	
	/**
	 * Reconfirm terms or privacy policy
	 *
	 * @return	void
	 */
	protected function reconfirm() : void
	{
		/* You must be logged in for this action */
		if( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission', '2C223/C', 403, '' );
		}

		/* Generate form */
		$form = new Form( 'reconfirm_checkbox', 'reconfirm_checkbox' );
		$form->hiddenValues['ref'] = base64_encode( Request::i()->referrer( FALSE, FALSE, 'front' ) ?? Url::baseUrl() );
		$form->class = 'ipsForm--vertical ipsForm--reconfirm-terms';
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Log that we gave consent */
			if ( Member::loggedIn()->members_bitoptions['must_reaccept_privacy'] )
			{
				Member::loggedIn()->logHistory( 'core', 'terms_acceptance', array( 'type' => 'privacy' ) );
			}
			
			if ( Member::loggedIn()->members_bitoptions['must_reaccept_terms'] )
			{
				Member::loggedIn()->logHistory( 'core', 'terms_acceptance', array( 'type' => 'terms' ) );
			}
			
			Member::loggedIn()->members_bitoptions['must_reaccept_privacy'] = FALSE;
			Member::loggedIn()->members_bitoptions['must_reaccept_terms'] = FALSE;
			Member::loggedIn()->save();
			
			$this->_performRedirect();
		}

		$subprocessors = array();
		/* Work out the main subprocessors that the user has no direct choice over */
		if ( Settings::i()->privacy_show_processors )
		{
			foreach( Application::enabledApplications() as $app )
			{
				$subprocessors = array_merge( $subprocessors, $app->privacyPolicyThirdParties() );
			}
		}
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('terms_of_use');
		Output::i()->output = Theme::i()->getTemplate('system')->reconfirmTerms(  Member::loggedIn()->members_bitoptions['must_reaccept_terms'],  Member::loggedIn()->members_bitoptions['must_reaccept_privacy'], $form, $subprocessors );
	}
	
	/**
	 * Cancel the post before registering submission
	 *
	 * @return	void
	 */
	protected function cancelPostBeforeRegister() : void
	{
		if( ! isset( Request::i()->id ) or ! isset( Request::i()->pbr ) or ! Settings::i()->post_before_registering )
		{
			Output::i()->error( 'no_module_permission', '2C223/D', 403, '' );
		}
		
		try
		{
			$pbr = Db::i()->select( '*', 'core_post_before_registering', array( "id=? and secret=?", Request::i()->id, Request::i()->pbr ) )->first();
			
			$class = $pbr['class'];
			try
			{
				$class::load( $pbr['id'] )->delete();
			}
			catch ( OutOfRangeException $e ) { }
			
			Db::i()->delete( 'core_post_before_registering', array( 'class=? AND id=?', $pbr['class'], $pbr['id'] ) );
			
			Output::i()->redirect( Url::internal(''), 'post_before_register_submission_cancelled' );
		}
		catch( UnderFlowException $e )
		{
			Output::i()->error( 'pbr_row_not_found', '2C223/E', 403, '' );
		}
	}

	/**
	 * Builds the reg_agreed_terms language string which takes the privacy type settings into account
	 *
	 * @return	void
	 */
	public static function buildRegistrationTerm() : void
	{
		Member::loggedIn()->language()->words[ "reg_agreed_terms" ] = sprintf( Member::loggedIn()->language()->get("reg_agreed_terms"), Url::internal( 'app=core&module=system&controller=terms', 'front', 'terms' ) );

		/* Build the appropriate links for registration terms & privacy policy */
		if ( Settings::i()->privacy_type == "internal" )
		{
			Member::loggedIn()->language()->words[ "reg_agreed_terms" ] .= sprintf( Member::loggedIn()->language()->get("reg_privacy_link"), Url::internal( 'app=core&module=system&controller=privacy', 'front', 'privacy', array(), Url::PROTOCOL_RELATIVE ), 'data-ipsDialog data-ipsDialog-size="wide" data-ipsDialog-title="' . Member::loggedIn()->language()->get("privacy") . '"' );
		}
		else if ( Settings::i()->privacy_type == "external" )
		{
			Member::loggedIn()->language()->words[ "reg_agreed_terms" ] .= sprintf( Member::loggedIn()->language()->get("reg_privacy_link"), Url::external( Settings::i()->privacy_link ), 'target="_blank" rel="noopener"' );
		}
	}

	/**
	 * Redirect the user
	 * -consolidated to reduce duplicate code
	 *
	 * @param	array|NULL	$postBeforeRegister		Post before registration data
	 * @param	bool		$return					Return the URL instead of redirecting
	 * @param	string		$message				(Optional) message to show during redirect
	 * @return	Url
	 */
	protected function _performRedirect( ?array $postBeforeRegister=NULL, bool $return=FALSE, string $message='' ) : Url
	{
		/* Redirect */
		if ( $ref = static::_refUrl() )
		{
			// We got it!
		}
		elseif ( $postBeforeRegister )
		{
			try
			{
				$class = $postBeforeRegister['class'];
				$ref = $class::load( $postBeforeRegister['id'] )->url();
			}
			catch ( OutOfRangeException $e )
			{
				$ref = Url::internal('');
			}
		}
		else
		{
			$ref = Url::internal('');
		}
		
		if( $return === TRUE )
		{
			return $ref;
		}

		Output::i()->redirect( $ref, $message );
	}
	
	/**
	 * Get referral URL
	 *
	 * @return	Url|NULL
	 */
	protected static function _refUrl() : ?Url
	{
		if ( isset( Request::i()->ref ) and $ref = @base64_decode( Request::i()->ref ) )
		{
			try
			{
				$ref = Url::createFromString( $ref );
				if ( ( $ref instanceof Internal ) and in_array( $ref->base, array( 'front', 'none' ) ) and !$ref->openRedirect() )
				{
					return $ref;
				}
			}
			catch ( Exception $e ){ }
		}
		return NULL;
	}
	
	/**
	 * Set Password
	 *
	 * @return	void
	 */
	public function setPassword() : void
	{
		try
		{
			$member = Member::load( Request::i()->mid );
			
			if ( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C223/F', 403, '' );
		}
		
		/* If this user isn't being forced, error */
		if ( !$member->members_bitoptions['password_reset_forced'] )
		{
			Output::i()->error( 'node_error', '2C223/H', 403, '' );
		}
		
		if ( !Login::compareHashes( md5( SUITE_UNIQUE_KEY . $member->email . $member->name ), (string) Request::i()->passkey ) )
		{
			Output::i()->error( 'node_error', '2C223/G', 403, '' );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'set_password_title', FALSE, array( 'sprintf' => array( $member->name ) ) );
		if ( $mfa = MFAHandler::accessToArea( 'core', 'AuthenticateFront', Request::i()->url(), $member ) )
		{
			Output::i()->output = $mfa;
			return;
		}

		$form = new Form;
		$form->add( new Password( 'password', NULL, TRUE, array( 'protect' => TRUE, 'showMeter' => Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'strengthRequest' => array( 'username', 'email_address' ), 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "new-password" ) ) );
		$form->add( new Password( 'password_confirm', NULL, TRUE, array( 'protect' => TRUE, 'confirm' => 'password', 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "new-password" ) ) );
		if ( $values = $form->values() )
		{
			$changed = $member->changePassword( $values['password'], 'forced' );
			if ( !$changed and Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
			{
				$member->setLocalPassword( $values['password'] );
				$member->save();
			}
			
			Request::i()->setCookie( 'noCache', 1 );
			
			$success = new Success( $member, Handler::findMethod( 'IPS\Login\Handler\Standard' ) );
			$success->process();
			
			Output::i()->redirect( Url::internal( '' ), 'set_password_stored' );
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'system' )->registerSetPassword( $form, $member );
	}
}
