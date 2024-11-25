<?php
/**
 * @brief		Checkout
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\modules\front\checkout;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use InvalidArgumentException;
use IPS\core\Facebook\Pixel;
use IPS\core\modules\front\system\register;
use IPS\core\ProfileFields\Field;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Events\Event;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Wizard;
use IPS\Log;
use IPS\Login;
use IPS\Login\Handler;
use IPS\Login\Success;
use IPS\Member;
use IPS\Member\Device;
use IPS\MFA\MFAHandler;
use IPS\MFA\SecurityQuestions\Question;
use IPS\nexus\Coupon;
use IPS\nexus\Customer;
use IPS\nexus\Customer\Address;
use IPS\nexus\Customer\CreditCard;
use IPS\nexus\Customer\CustomField;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Encrypt;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_int;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Checkout
 */
class checkout extends Controller
{
	/**
	 * @brief	Invoice
	 */
	protected ?Invoice $invoice = null;
	
	/**
	 * @brief	Does the user need to log in?
	 */
	protected bool $needsToLogin = FALSE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'checkout.css', 'nexus' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'store.css', 'nexus' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_checkout.js', 'nexus', 'front' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_gateways.js', 'nexus', 'global' ) );
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'module__nexus_checkout' );
		parent::execute();
	}

	/**
	 * Checkout
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		/* Load invoice */
		try
		{
			$this->invoice = Invoice::load( Request::i()->id );
			
			if ( !$this->invoice->canView() )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			$msg = 'no_invoice_view';
			if ( !Member::loggedIn()->member_id )
			{
				$msg = 'no_invoice_view_guest';
			}
			
			Output::i()->error( $msg, '2X196/1', 403, '' );
		}
		$checkoutUrl = $this->invoice->checkoutUrl();
		
		/* Is it paid? */
		if ( $this->invoice->status === Invoice::STATUS_PAID )
		{
			if ( $this->invoice->return_uri )
			{
				Output::i()->redirect( $this->invoice->return_uri );
			}
			else
			{
				Output::i()->redirect( $this->invoice->url() );
			}
		}
		
		/* Or cancelled or expired? */
		if ( $this->invoice->status !== Invoice::STATUS_PENDING )
		{
			Output::i()->redirect( $this->invoice->url() );
		}
		
		/* Do we need to *show* the first step */
		$canSkipFirstStepIfNameAndBillingAddressIsKnown = TRUE;
		if ( Member::loggedIn()->member_id )
		{
			$showFirstStep = FALSE;
			if ( $this->invoice->hasItemsRequiringBillingAddress() )
			{
				$showFirstStep = TRUE;
			}

			foreach ( CustomField::roots() as $field )
			{
				$column = $field->column;
				if ( $field->purchase_show and $field->purchase_require and !$this->invoice->member->$column )
				{
					$showFirstStep = TRUE;
					$canSkipFirstStepIfNameAndBillingAddressIsKnown = FALSE;
					break;
				}
			}		
		}
		else
		{
			$showFirstStep = TRUE;
		}
						
		/* What are the steps? */
		$steps = array();
		if ( $showFirstStep )
		{
			$steps['checkout_customer'] = array( $this, '_customer' );
		}
		$steps['checkout_pay'] = array( $this, '_pay' );
		
		/* Even if we have to show the first step, can we skip it because we already have their name and a primary billing address? */
		if ( $showFirstStep and $canSkipFirstStepIfNameAndBillingAddressIsKnown and Member::loggedIn()->member_id and $this->invoice->member->cm_first_name and $this->invoice->member->cm_last_name and !isset( $_SESSION[ 'wizard-' . md5( $checkoutUrl ) . '-step' ] ) )
		{
			if ( $primaryBillingAddress = Customer::loggedIn()->primaryBillingAddress() )
			{
				$this->invoice->billaddress = $primaryBillingAddress;
				$this->invoice->recalculateTotal();
				$this->invoice->save();
				$_SESSION[ 'wizard-' . md5( $checkoutUrl ) . '-step' ] = 'checkout_pay';
			}
		}
		
		/* Do we need to log in? */
		$this->needsToLogin = ( !Member::loggedIn()->member_id and ( $this->invoice->requiresLogin() ) );
		
		/* Facebook Pixel */
		Pixel::i()->InitiateCheckout = true;
		
		/* Do the wizard */
		Output::i()->sidebar['enabled'] = FALSE;
		if ( isset( Output::i()->breadcrumb['module'][0] ) )
		{
			Output::i()->breadcrumb['module'][0] = NULL;
		}
		Output::i()->output = Theme::i()->getTemplate('checkout')->checkoutWrapper( (string) new Wizard( $steps, $checkoutUrl, ( !isset( $steps['checkout_login'] ) and isset( $steps['checkout_customer'] ) ) ) );
	}
		
	/**
	 * Step: Customer Details
	 *
	 * @return	string|array
	 */
	public function _customer() : string|array
	{
		/* Fire an event to run on this checkout step */
		Event::fire( 'onCheckout', $this->invoice, [ 'customer' ] );

		/* Init */
		$buttonLang = 'continue_to_review';
		$needBillingInfo = $this->invoice->hasItemsRequiringBillingAddress();
		$needTaxStatus = NULL;
		$guestData = NULL;
		$postBeforeRegister = NULL;

		$form = new Form( 'customer', $buttonLang, $this->invoice->checkoutUrl()->setQueryString( '_step', 'checkout_customer' ) );
		
		/* Account Information */
		if ( $this->needsToLogin and !in_array( Login::registrationType(), array( 'disabled', 'redirect' ) ) )
		{
			$guestData = $this->invoice->guest_data;
			
			$postBeforeRegister = NULL;
			if ( isset( Request::i()->cookie['post_before_register'] ) )
			{
				try
				{
					$postBeforeRegister = Db::i()->select( '*', 'core_post_before_registering', array( 'secret=?', Request::i()->cookie['post_before_register'] ) )->first();
				}
				catch ( UnderflowException ) { }
			}
			
			/* Add the registration related js stuff to the checkout form for email field validation */
			$form->attributes['data-controller'] = 'core.front.system.register';
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_system.js', 'core', 'front' ), Output::i()->js( 'front_templates.js', 'core', 'front' ) );

			if( isset( $_SESSION['coppa_user'] ) )
			{
				if ( Settings::i()->minimum_age > 0 )
				{
					$message = Member::loggedIn()->language()->addToStack( 'register_denied_age', FALSE, array( 'sprintf' => array( Settings::i()->minimum_age ) ) );
					Output::i()->error( $message, '2X196/D', 403, '' );
				}
				else
				{
					Output::i()->title = Member::loggedIn()->language()->addToStack('reg_awaiting_validation');
					return Output::i()->output = Theme::i()->getTemplate( 'system', 'core' )->notCoppaValidated();
				}
			}
			
			if ( Settings::i()->minimum_age > 0 OR Settings::i()->use_coppa )
			{
				$form->addHeader( 'coppa_title' );
				
				/* We dynamically replace this as we need to show this message, however we do not want to create a "bday_desc" language string which may not be appropriate on other forms */
				Member::loggedIn()->language()->words['bday_desc'] = Member::loggedIn()->language()->addToStack( 'coppa_verification_only' );
				$form->add( new Date( 'bday', NULL, TRUE, array( 'max' => DateTime::create() ) ) );
			}

			$form->addHeader('account_information');
			if ( Settings::i()->nexus_checkreg_usernames )
			{
				$form->add( new Text( 'username', isset( $guestData['member']['name'] ) ? $guestData['name'] : NULL, TRUE, array( 'accountUsername' => TRUE, 'htmlAutocomplete' => "username" ) ) );
			}
			$form->add( new Email( 'email_address', $guestData ? $guestData['member']['email'] : ( $postBeforeRegister ? $postBeforeRegister['email'] : NULL ), TRUE, array( 'accountEmail' => TRUE, 'maxLength' => 150, 'htmlAutocomplete' => "email" ) ) );
			$form->add( new Password( 'password', NULL, TRUE, array( 'protect' => TRUE, 'showMeter' => Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'htmlAutocomplete' => "new-password" ) ) );
			$form->add( new Password( 'password_confirm', NULL, TRUE, array( 'protect' => TRUE, 'confirm' => 'password', 'htmlAutocomplete' => "new-password" ) ) );
			if ( $needBillingInfo )
			{
				$form->addHeader('billing_information');
			}
		}
		
		/* Billing Information */
		if ( $needBillingInfo or ( $this->needsToLogin and !in_array( Login::registrationType(), array( 'disabled', 'redirect' ) ) and !Settings::i()->nexus_checkreg_usernames ) )
		{
			$form->add( new Text( 'cm_first_name', $this->invoice->member->cm_first_name, TRUE, array( 'htmlAutocomplete' => "given-name" ) ) );
			$form->add( new Text( 'cm_last_name', $this->invoice->member->cm_last_name, TRUE, array( 'htmlAutocomplete' => "family-name" ) ) );
		}
		if ( $needBillingInfo )
		{
			/* Do we need to know if they are a business or consumer? */
			foreach ( $this->invoice->items as $item )
			{
				if ( $tax = $item->tax )
				{
					if ( $tax->type === 'eu' )
					{
						$needTaxStatus = 'eu';
						break;
					}
					if ( $tax->type === 'business' )
					{
						$needTaxStatus = 'business';
					}
				}
			}
			$addressHelperClass = $needTaxStatus ? 'IPS\nexus\Form\BusinessAddress' : 'IPS\Helpers\Form\Address';
			$addressHelperOptions = ( $needTaxStatus === 'eu' ) ? array( 'vat' => TRUE ) : array();

			/* The actual billing address */
			$addresses = Db::i()->select( '*', 'nexus_customer_addresses', array( '`member`=?', Member::loggedIn()->member_id ) );
			if ( count( $addresses ) )
			{
				$billing = NULL;
				$options = array();
				foreach ( new ActiveRecordIterator( $addresses, 'IPS\nexus\Customer\Address' ) as $address )
				{
					$options[ $address->id ] = $address->address->toString('<br>') . ( ( isset( $address->address->business ) and $address->address->business and isset( $address->address->vat ) and $address->address->vat ) ? ( '<br>' . Member::loggedIn()->language()->addToStack('cm_checkout_vat_number') . ': ' . mb_strtoupper( preg_replace( '/[^A-Z0-9]/', '', $address->address->vat ) ) ) : '' );
					if ( ( !$this->invoice->billaddress and $address->primary_billing ) or $this->invoice->billaddress == $address->address )
					{
						$billing = $address->id;
					}
				}
				$options[0] = Member::loggedIn()->language()->addToStack( 'other' );
				
				$form->add( new Radio( 'billing_address', $billing, TRUE, array( 'options' => $options, 'toggles' => array( 0 => array( 'new_billing_address' ) ), 'parse' => 'raw' ) ) );
				$newAddress = new $addressHelperClass( 'new_billing_address', !$billing ? $this->invoice->billaddress : NULL, FALSE, $addressHelperOptions, NULL, NULL, NULL, 'new_billing_address' );
				$newAddress->label = ' ';
				$form->add( $newAddress );
			}
			else
			{
				$form->add( new $addressHelperClass( 'new_billing_address', $this->invoice->billaddress, TRUE, $addressHelperOptions ) );
			}
		}
		
		/* Customer Fields */
		$customer = Customer::loggedIn();
		foreach ( CustomField::roots() as $field )
		{
			/* @var CustomField $field */
			if ( $field->purchase_show )
			{
				$column = $field->column;
				$field->not_null = $field->purchase_require;
				$input = $field->buildHelper( $customer->$column );
				$input->appearRequired = $field->purchase_require;
				$form->add( $input );
			}
		}
		
		/* Additional Information */
		if ( $this->needsToLogin and !in_array( Login::registrationType(), array( 'disabled', 'redirect' ) ) )
		{
			$form->addHeader('additional_information');

			/* Custom fields */
			if( Login::registrationType() == 'full' )
			{
				$customFields = Field::fields( $guestData ? $guestData['profileFields'] : [], Field::REG );
				if ( count( $customFields ) )
				{
					foreach ( $customFields as $group => $fields )
					{
						foreach ( $fields as $field )
						{
							$form->add( $field );
						}
					}
					$form->addSeparator();
				}
			}
			
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
						$optOutCheckbox->description = Member::loggedIn()->language()->addToStack('security_questions_opt_out_warning_value');
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
			
			/* Q&A */
			if ( Settings::i()->nexus_checkreg_captcha )
			{
				$question = FALSE;
				try
				{
					$question = Db::i()->select( '*', 'core_question_and_answer', NULL, "RAND()" )->first();
				}
				catch ( UnderflowException ) {}
				
				if( $question )
				{
					$form->hiddenValues['q_and_a_id'] = $question['qa_id'];
				
					$form->add( new Text( 'q_and_a', NULL, TRUE, array(), function( $val )
					{
						$qanda  = intval( Request::i()->q_and_a_id );
						$pass = true;
					
						if( $qanda )
						{
							$question = Db::i()->select( '*', 'core_question_and_answer', array( 'qa_id=?', $qanda ) )->first();
							$answers = json_decode( $question['qa_answers'], true );
				
							if( count( $answers ) )
							{
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
					
					Member::loggedIn()->language()->words['q_and_a'] = Member::loggedIn()->language()->addToStack( 'core_question_and_answer_' . $question['qa_id'], FALSE );
				}
			}
			
			/* Captcha */
			if ( !$guestData )
			{
				$captcha = new Captcha;
				if ( (string) $captcha !== '' )
				{
					$form->add( $captcha );
				}
			}
			
			/* Misc */
			$form->add( new Checkbox( 'reg_admin_mails', $guestData ? $guestData['member']['allow_admin_mails'] : ( (bool) Settings::i()->updates_consent_default == 'enabled' ), FALSE ) );
			register::buildRegistrationTerm();
			$form->add( new Checkbox( 'reg_agreed_terms', (bool) $guestData, TRUE, array(), function( $val )
			{
				if ( !$val )
				{
					throw new InvalidArgumentException('reg_not_agreed_terms');
				}
			} ) );
		}

		/* Extensions */
		$form->addExtensionFields( Form::FORM_CHECKOUT, [ Customer::loggedIn(), $this->invoice ] );
		
		/* Handle submission */
		if ( $values = $form->values() )
		{
			/* Set guest transaction key cookie so only this guest can view transaction info */
			if( !isset( Request::i()->cookie['guestTransactionKey'] ) AND !Member::loggedIn()->member_id )
			{
				$guestTransactionKey = Login::generateRandomString();
				Request::i()->setCookie( 'guestTransactionKey', $guestTransactionKey, DateTime::ts( time() )->add( new DateInterval( 'P30D' ) ) );
			}

			/* If user is a guest create the member object but don't save it */
			if ( $this->needsToLogin )
			{
				/* It shouldn't be possible to get here */
				if ( in_array( Login::registrationType(), array( 'disabled', 'redirect' ) ) )
				{
					Output::i()->error( 'reg_disabled', '3X196/A', 403, '' );
				}
				
				/* Did we pass the minimum age requirement? */
				if ( Settings::i()->minimum_age > 0 OR Settings::i()->use_coppa )
				{
					if ( Settings::i()->minimum_age > 0 AND $values['bday']->diff( DateTime::create() )->y < Settings::i()->minimum_age )
					{
						$_SESSION['coppa_user'] = TRUE;
						
						$message = Member::loggedIn()->language()->addToStack( 'register_denied_age', FALSE, array( 'sprintf' => array( Settings::i()->minimum_age ) ) );
						Output::i()->error( $message, '2X196/E', 403, '' );
					}
					/* We did, but we should check normal COPPA too */
					else if( ( $values['bday']->diff( DateTime::create() )->y < 13 ) )
					{
						$_SESSION['coppa_user'] = TRUE;
						return Output::i()->output = Theme::i()->getTemplate( 'system', 'core' )->notCoppaValidated();
					}
				}
				
				/* Security questions */
				$securityQuestionAnswers = array();
				if ( Settings::i()->security_questions_enabled and ( Settings::i()->security_questions_prompt === 'register' or ( Settings::i()->security_questions_prompt === 'optional' and !$values['security_questions_optout_title'] ) ) )
				{
					foreach ( $values as $k => $v )
					{
						if ( preg_match( '/^security_question_q_(\d+)$/', $k, $matches ) )
						{
							if ( isset( $securityQuestionAnswers[ $v ] ) )
							{
								$form->error = Member::loggedIn()->language()->addToStack( 'security_questions_unique', FALSE, array( 'pluralize' => array( Settings::i()->security_questions_number ?: 3 ) ) );
								break;
							}
							else
							{
								$securityQuestionAnswers[ $v ] = Encrypt::fromPlaintext( $values[ 'security_question_a_' . $matches[1] ] )->tag();
							}
						}
					}
				}
				
				/* Continue... */
				if ( !$form->error )
				{
					/* Set basic details */
					$member = new Customer;
					if ( Settings::i()->nexus_checkreg_usernames )
					{
						if ( $needBillingInfo )
						{
							$member->cm_first_name		= $values['cm_first_name'];
							$member->cm_last_name		= $values['cm_last_name'];
						}
						$member->name				= $values['username'];
					}
					else
					{
						$member->cm_first_name		= $values['cm_first_name'];
						$member->cm_last_name		= $values['cm_last_name'];

						/* If this name is available, use that, otherwise append a number to the name to avoid an incomplete account. */
						$i = 0;
						do
						{
							$name = "{$values['cm_first_name']} {$values['cm_last_name']}";
							if ( $i > 0 )
							{
								$name .= $i;
							}
							
							if ( !Login::usernameIsInUse( $name ) )
							{
								$member->name = $name;
								break;
							}
							$i++;
						}
						while( TRUE );
					}
					$member->email				= $values['email_address'];
					$member->setLocalPassword( $values['password'] );
					$member->allow_admin_mails  = $values['reg_admin_mails'];
					$member->member_group_id	= Settings::i()->member_group;
					
					/* Customer Fields */
					foreach ( CustomField::roots() as $field )
					{
						/* @var CustomField $field */
						if ( $field->purchase_show )
						{
							$column = $field->column;
							$helper = $field->buildHelper();
							$member->$column = $helper::stringValue( $values["nexus_ccfield_{$field->id}"] );
						}
						
						if ( $field->type === 'Editor' )
						{
							$field->claimAttachments( $member->member_id );
						}
					}
					
					/* Custom Fields */
					$profileFields = array();
					foreach ( Field::fields( array(), Field::REG ) as $group => $fields )
					{
						foreach ( $fields as $id => $field )
						{
							/* @var FormAbstract $field */
							if ( $field instanceof Upload )
							{
								$profileFields[ "field_{$id}" ] = (string) $values[ $field->name ];
							}
							else
							{
								$profileFields[ "field_{$id}" ] = $field::stringValue( !empty( $values[ $field->name ] ) ? $values[ $field->name ] : NULL );
							}
	
							if ( $fields instanceof Editor )
							{
								$field->claimAttachments( $member->member_id );
							}
						}
					}
					
					/* Run it through the spam service */
					$spamCode = NULL;
					$spamAction = NULL;
					if( Settings::i()->spam_service_enabled )
					{
						$spamAction = $member->spamService( 'register', NULL, $spamCode );
						if( $spamAction == 4 )
						{
							Output::i()->error( 'spam_denied_account', '2S129/1', 403, '' );
						}
					}
					
					/* Save on invoice */
					$this->invoice->guest_data = array(
						'member'					=> $member->changed,
						'profileFields' 		=> $profileFields,
						'securityAnswers'		=> $securityQuestionAnswers,
						'spamData'				=> array( 'code' => $spamCode, 'action' => $spamAction ),
						'guestTransactionKey'	=> Request::i()->cookie['guestTransactionKey'],
						'pbr'					=> $postBeforeRegister ? $postBeforeRegister['secret'] : NULL,
						'referred_by'			=> isset( Request::i()->cookie['referred_by'] ) ? Request::i()->cookie['referred_by'] : NULL,
						'agreed_terms'			=> ( bool ) $values['reg_agreed_terms']
					);
				}
			}
			/* Otherwise just update the name and details */
			else
			{
				
				$changes = array();
				if ( $needBillingInfo )
				{
					foreach ( array( 'cm_first_name', 'cm_last_name' ) as $k )
					{
						if ( $values[ $k ] != Customer::loggedIn()->$k )
						{
							$changes['name'] = Customer::loggedIn()->cm_name;
							Customer::loggedIn()->$k = $values[ $k ];
						}
					}
				}
				foreach ( CustomField::roots() as $field )
				{
					/* @var CustomField $field */
					if ( $field->purchase_show )
					{
						$column = $field->column;
						$helper = $field->buildHelper();
						$valueToSave = $helper::stringValue( $values["nexus_ccfield_{$field->id}"] );
						if ( Customer::loggedIn()->$column != $valueToSave )
						{
							$changes['other'][] = array( 'name' => 'nexus_ccfield_' . $field->id, 'value' => $field->displayValue( $valueToSave ), 'old' => $field->displayValue( Customer::loggedIn()->$column ) );
						}
 						Customer::loggedIn()->$column = $valueToSave;
					}
				}
				if ( !empty( $changes ) )
				{
					Customer::loggedIn()->log( 'info', $changes );
				}
				
				/* We only want to do this if it's an actual account */
				if ( Customer::loggedIn()->member_id )
				{
					Customer::loggedIn()->save();
				}
				else
				{
					/* Otherwise, we need to store this as guest data */
					$this->invoice->guest_data = array( 'member' => Customer::loggedIn()->changed, 'profileFields' => array(), 'securityAnswers' => array(), 'guestTransactionKey' => Request::i()->cookie['guestTransactionKey'] );
				}
			}

			/* Save extension fields here, before we redirect */
			Form::saveExtensionFields( Form::FORM_CHECKOUT, $values, [ $member ?? Customer::loggedIn(), $this->invoice ] );
			
			if ( !$form->error )
			{
				/* Save the billing address */
				if ( $needBillingInfo )
				{
					if ( count( $addresses ) and $values['billing_address'] )
					{
						$this->invoice->billaddress = Address::load( $values['billing_address'] )->address;
					}
					else
					{
						if( empty( $values['new_billing_address']->addressLines ) or !$values['new_billing_address']->city or !$values['new_billing_address']->country or ( !$values['new_billing_address']->region and array_key_exists( $values['new_billing_address']->country, GeoLocation::$states ) ) or !$values['new_billing_address']->postalCode )
						{
							$form->error = Member::loggedIn()->language()->addToStack('billing_address_required');
							return $form;
						}
						
						if ( Member::loggedIn()->member_id )
						{
							$address = new Address;
							$address->member = Member::loggedIn();
							$address->address = $values['new_billing_address'];
							$address->primary_billing = !count( $addresses );
							$address->save();
							
							Customer::loggedIn()->log( 'address', array( 'type' => 'add', 'details' => json_encode( $values['new_billing_address'] ) ) );
						}
						
						$this->invoice->billaddress = $values['new_billing_address'];
					}
				}
							
				/* Save */
				$this->invoice->recalculateTotal();
				$this->invoice->save();
				return array();
			}
		}
		
		/* If we're not logged in, and we need an account for this purchase, show the login form */
		$login = NULL;
		$loginError = NULL;
		if ( $this->needsToLogin )
		{
			/* Two-Factor Authentication */
			if ( isset( Request::i()->mfa ) and isset( $_SESSION['processing2FACheckout'] ) and $_SESSION['processing2FACheckout']['invoice'] === $this->invoice->id )
			{
				$member = Member::load( $_SESSION['processing2FACheckout']['memberId'] );
				if ( !$member->member_id )
				{
					unset( $_SESSION['processing2FACheckout'] );
					Output::i()->redirect( $this->invoice->checkoutUrl() );
				}
				
				$device = Device::loadOrCreate( $member );
				$mfaOutput = MFAHandler::accessToArea( 'core', $device->known ? 'AuthenticateFrontKnown' : 'AuthenticateFront', $this->invoice->checkoutUrl()->setQueryString( 'mfa', 1 ), $member );
				if ( !$mfaOutput )
				{
					/* Set the invoice owner */
					$this->invoice->member = $member;
					$this->invoice->save();
					
					/* Process the login */
					( new Success( $member, Handler::load( $_SESSION['processing2FACheckout']['handler'] ), $_SESSION['processing2FACheckout']['remember'], $_SESSION['processing2FACheckout']['anonymous'] ) )->process();
										
					/* Redirect */
					Output::i()->redirect( $this->invoice->checkoutUrl() );
				}
			}
			
			/* Login */			
			$login = new Login( $this->invoice->checkoutUrl() );
			try
			{
				if ( $success = $login->authenticate() )
				{
					/* Process the login */
					if ( $success->mfa() )
					{
						$_SESSION['processing2FACheckout'] = array( 'memberId' => $success->member->member_id, 'invoice' => $this->invoice->id, 'anonymous' => $success->anonymous, 'remember' => $success->rememberMe, 'handler' => $success->handler->id );
						Output::i()->redirect( $this->invoice->checkoutUrl()->setQueryString( 'mfa', 1 ) );
					}
					else
					{
						/* Set the invoice owner */
						$this->invoice->member = $success->member;
						$this->invoice->save();
						
						/* Process the login */
						$success->process();
											
						/* Redirect */
						Output::i()->redirect( $this->invoice->checkoutUrl() );
					}
				}
			}
			catch (Login\Exception $e )
			{
				$loginError = $e->getMessage();
			}
		}
		

		/* Display */
		return Theme::i()->getTemplate('checkout')->customerInformation( $form->customTemplate( array( Theme::i()->getTemplate( 'checkout', 'nexus' ), 'customerInformationForm' ) ), $login, $loginError, $this->invoice );
	}
	
	/**
	 * Step: Select Payment Method
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _pay( array $data ) : string|array
	{
		/* Fire an event to run on this checkout step */
		Event::fire( 'onCheckout', $this->invoice, [ 'pay' ] );

		/* How much are we paying? */
		$this->invoice->recalculateTotal();
		$amountToPay = $this->invoice->amountToPay( TRUE );
		if ( isset( Request::i()->split ) )
		{
			$split = new \IPS\Math\Number( Request::i()->split );
			if ( $amountToPay->amount->compare( $split ) === 1 )
			{
				$amountToPay->amount = $split;
			}
		}
		if ( !$amountToPay->amount->isPositive() )
		{
			Output::i()->error( 'err_no_methods', '5X196/8', 500, '' );
		}

		foreach ( $this->invoice->items as $item )
		{
			/* Verify whether we're allowed to purchase this */
			if( Member::loggedIn()->member_id )
			{
				try
				{
					$item->memberCanPurchase( Member::loggedIn() );
				}
				catch ( DomainException $e )
				{
					Output::i()->error( $e->getMessage(), '2X196/H', 403, '' );
				}
			}

			/* Verify stock level one last time. It's possible someone added an item to their cart, then someone else did and checked out and the stock level is now 0. */
			if( $item instanceof \IPS\nexus\extensions\nexus\Item\Package )
			{
				$package = Package::load( $item->id );
				$data = $package->optionValuesStockAndPrice( $package->optionValues( $item->details ) );
	
				if ( $data['stock'] != -1 and $data['stock'] < $item->quantity )
				{
					Output::i()->error( Member::loggedIn()->language()->addToStack( 'not_enough_in_stock_checkout', FALSE, array( 'pluralize' => array( $data['stock'] ), 'sprintf' => array( $item->name ) ) ), '1X196/G', 403, '' );
				}
			}
		}
		
		/* Work out recurring payments */
		$recurrings = array();
		$overriddenRenewalTerms = array();
		foreach ( $this->invoice->items as $item )
		{
			if ( $item->groupWithParent and is_int( $item->parent ) and isset( $item->renewalTerm ) and $item->renewalTerm )
			{
				/* @var Invoice\Item\Purchase $item */
				$parent = $this->invoice->items[ $item->parent ];
				if ( ( isset( $parent->renewalTerm ) and $parent->renewalTerm ) or isset( $overriddenRenewalTerms[ $item->parent ] ) )
				{
					$oldTerm = $overriddenRenewalTerms[$item->parent] ?? $parent->renewalTerm;
					
					for( $i=0, $j=$item->quantity; $i < $j; $i++ )
					{
						$overriddenRenewalTerms[ $item->parent ] = new RenewalTerm( ( isset( $overriddenRenewalTerms[ $item->parent ] ) ) ? $overriddenRenewalTerms[ $item->parent ]->add( $item->renewalTerm ) : $oldTerm->add( $item->renewalTerm ), $oldTerm->interval, $oldTerm->tax );
					}
				}
				else
				{
					$overriddenRenewalTerms[ $item->parent ] = $item->renewalTerm;
				}
			}
		}
		foreach ( $this->invoice->items as $k => $item )
		{
			if ( !$item->groupWithParent )
			{
				$term = NULL;
				$dueDate = NULL;
				if ( isset( $overriddenRenewalTerms[ $k ] ) )
				{
					$term = $overriddenRenewalTerms[ $k ];
					$dueDate = DateTime::create()->add( $term->interval );
				}
				elseif ( $item instanceof Renewal )
				{
					$term = Purchase::load( $item->id )->renewals;
					
					if ( $expireDate = Purchase::load( $item->id )->expire and $expireDate->getTimestamp() > time() )
					{
						$dueDate = Purchase::load( $item->id )->expire;
					}
					else
					{
						$dueDate = DateTime::create();
					}
					
					for ( $i = 0; $i < $item->quantity; $i++ )
					{
						$dueDate = $dueDate->add( $term->interval );
					}
				}
				elseif ( isset( $item->renewalTerm ) and $item->renewalTerm )
				{
					$term = $item->renewalTerm;
					$dueDate = DateTime::create()->add( $item->initialInterval ?: $term->interval );
				}

				if ( $term )
				{
					$format = $item->groupWithParent ? 'grouped' : $term->interval->format('%d/%m/%y') . '/' . $term->cost->currency . '/' . ( $term->tax ? $term->tax->id : '0' );
					
					$clonedItem = clone $item;
					$showDueDate = true;
					if ( $item instanceof Renewal ) // If they are renewing for more than one cycle now, that's fine, but subsequently they will be moved back to the normal terms
					{
						$clonedItem->quantity = 1;
					}

					/* If there are different expiration dates then we want to display things a little differently */
					if( isset( $item->initialInterval ) AND $item->initialInterval instanceof DateInterval )
					{
						$clonedItem->expireDate = DateTime::create()->add( $item->initialInterval );
						$showDueDate = false;
					}
					else
					{
						$clonedItem->expireDate = DateTime::create()->add( $term->interval );
					}

					if ( isset( $recurrings[ $format ] ) )
					{
						$recurrings[ $format ]['items'][] = $clonedItem;
					}
					else
					{
						$recurrings[ $format ] = array( 'items' => array( $clonedItem ), 'term' => new RenewalTerm( new Money( 0, $term->cost->currency ), $term->interval, $term->tax ), 'showDueDate' => true );
					}

					$recurrings[ $format ]['term']->cost->amount = $recurrings[ $format ]['term']->cost->amount->add( $term->cost->amount->multiply( new \IPS\Math\Number( "{$clonedItem->quantity}" ) ) );
					$recurrings[ $format ]['dueDate'] = $dueDate;
					if( !$showDueDate )
					{
						$recurrings[ $format ]['showDueDate'] = false;
					}
				}
			}
		}
		
		/* Get available payment methods, removing any that aren't supported by the items on this invoice */
		$paymentMethods = array();
		foreach ( Gateway::roots() as $gateway )
		{
			$paymentMethods[ $gateway->id ] = $gateway;
		}
		$canUseAccountCredit = TRUE;
		foreach ( $this->invoice->items as $item )
		{
			/* @var Invoice\Item $item */
			if ( $item->paymentMethodIds )
			{
				foreach ( $paymentMethods as $k => $v )
				{
					if ( in_array( '*', $item->paymentMethodIds ) ) // This looks odd but in older versions IPS\nexus\extensions\nexus\Item\Subscription::renewalPaymentMethodIds() was mistakenly returning array('*') and since the value is stored in the invoice we have to keep this for compatibility with invoices created at that time
					{
						continue;
					}
					
					if ( !in_array( $k, $item->paymentMethodIds ) )
					{
						unset( $paymentMethods[ $k ] );
					}
				}				
			}
			
			if ( !$item->canUseAccountCredit() )
			{
				$canUseAccountCredit = FALSE;
			}
		}
		
		/* If there's something to pay, show a form for it... */
		$hiddenValues = array();
		$showSubmitButton = FALSE;
		$paymentMethodsToggles = array();
		$paymentMethodOptions = NULL;
		if ( $amountToPay->amount->isGreaterThanZero() )
		{
			$paymentType = 'pay';
			
			/* Remove any payment methods that can't be used for this transaction */
			foreach ( $paymentMethods as $gateway )
			{
				if ( !$gateway->checkValidity( $amountToPay, $this->invoice->billaddress, Customer::loggedIn()->member_id ? Customer::loggedIn() : Customer::constructFromData( $this->invoice->guest_data['member'] ), $recurrings ) )
				{
					unset( $paymentMethods[ $gateway->id ] );
				}
			}
			
			/* If we don't have any available payment methods, show an error */
			if ( count( $paymentMethods ) === 0 and !$amountToPay->amount->isZero() )
			{
				Output::i()->error( 'err_no_methods', '4X196/3', 500, 'err_no_methods_admin' );
			}
													
			/* Build form */
			$elements = array();
			foreach ( $paymentMethods as $gateway )
			{
				foreach ( $gateway->paymentScreen( $this->invoice, $amountToPay, NULL, $recurrings ) as $element )
				{
					if ( !$element->htmlId )
					{
						$element->htmlId = $gateway->id . '-' . $element->name;
					}
					$elements[] = $element;
					$paymentMethodsToggles[ $gateway->id ][] = $element->htmlId;
				}
				
				if ( $gateway->showSubmitButton() )
				{
					$showSubmitButton = TRUE;
					$paymentMethodsToggles[ $gateway->id ][] = 'paymentMethodSubmit';
				}
			}
			$paymentMethodOptions = array();
			
			foreach ( $paymentMethods as $k => $v )
			{
				$paymentMethodOptions[ $k ] = $v->_title;
			}

			if ( $canUseAccountCredit and isset( Customer::loggedIn()->cm_credits[ $this->invoice->currency ] ) and Customer::loggedIn()->cm_credits[ $this->invoice->currency ]->amount->isGreaterThanZero() )
			{
				$credit = Customer::loggedIn()->cm_credits[ $this->invoice->currency ]->amount;
				if( $credit >= $amountToPay->amount or ( $amountToPay->amount->subtract( $credit ) > new \IPS\Math\Number( '0.50' ) ) )
				{
					$paymentMethodOptions[0] = Member::loggedIn()->language()->addToStack( 'account_credit_with_amount', FALSE, array( 'sprintf' => array( Customer::loggedIn()->cm_credits[ $this->invoice->currency ] ) ) );
					$paymentMethodsToggles[0][] = 'paymentMethodSubmit';
				}
			}
			
			if ( !$paymentMethodOptions )
			{
				$showSubmitButton = TRUE;
			}
		}
		/* If there's nothing to pay now, but there will be something to pay later... if we DON'T have any stored payment methods, we may be able to prompt the user to create one */
		elseif ( $recurrings and array_intersect( array_keys( $paymentMethods ), array_keys( Gateway::cardStorageGateways() ) ) and !Db::i()->select( 'COUNT(*)', 'nexus_customer_cards', array( 'card_member=?', Customer::loggedIn()->member_id ) )->first() and $elements = CreditCard::createFormElements( Customer::loggedIn(), FALSE, $showSubmitButton, $hiddenValues ) )
		{
			$paymentType = 'card';
		}
		/* Otherwise, just allow the user to confirm without paying anything */
		else
		{
			/* But wait - if they have *already* submitted payment, and we are waiting for that to be approved, there's nothing to do right now */
			foreach ( $this->invoice->transactions( [ Transaction::STATUS_HELD, Transaction::STATUS_REVIEW, Transaction::STATUS_GATEWAY_PENDING ] ) as $previousTransaction )
			{
				Output::i()->redirect( $previousTransaction->url() );
			}
			
			/* Otherwise they just need to confirm */
			$paymentType = 'none';
			$showSubmitButton = TRUE;
			$elements = [];
		}
		
		/* Build the form */
		$checkoutUrl = $this->invoice->checkoutUrl()->setQueryString( '_step', 'checkout_pay' );
		if ( isset( Request::i()->split ) )
		{
			$checkoutUrl = $checkoutUrl->setQueryString( 'split', $amountToPay->amountAsString() );
		}
		$form = new Form( 'select_method', 'checkout_pay', $checkoutUrl );
		foreach ( $hiddenValues as $k => $v )
		{
			$form->hiddenValues[ $k ] = $v;
		}
		$form->class = 'ipsForm--vertical ipsForm--select-payment-method';
		if ( isset( Request::i()->previousTransactions ) )
		{
			$form->hiddenValues['previousTransactions'] = Request::i()->previousTransactions;
		}
		else
		{
			if ( $previousTransactions = $this->invoice->transactions() and count( $previousTransactions ) )
			{
				$previousTransactionIds = array();
				foreach ( $previousTransactions as $previousTransaction )
				{
					$previousTransactionIds[] = $previousTransaction->id;
				}
				$form->hiddenValues['previousTransactions'] = implode( ',', $previousTransactionIds );
			}
		}
		if ( $paymentType === 'pay' and count( $paymentMethodOptions ) > 1 )
		{
			$form->add( new Radio( 'payment_method', NULL, TRUE, array( 'options' => $paymentMethodOptions, 'toggles' => $paymentMethodsToggles ) ) );
		}
		foreach ( $elements as $element )
		{
			$form->add( $element );
		}
		if ( Settings::i()->nexus_tac === 'checkbox' )
		{
			$form->add( new Checkbox( 'i_agree_to_tac', FALSE, TRUE, array( 'labelHtmlSprintf' => array( "<a href='" . htmlspecialchars( Settings::i()->nexus_tac_link, ENT_DISALLOWED, 'UTF-8', FALSE ) . "' target='_blank' rel='noopener'>" . Member::loggedIn()->language()->addToStack( 'terms_and_conditions' ) . '</a>' ) ), function( $val )
			{
				if ( !$val )
				{
					throw new DomainException( 'you_must_agree_to_tac' );
				}
			} ) );
		}
		
		/* Error to show? */
		if ( isset( Request::i()->err ) )
		{
			$form->error = Request::i()->err;
		}
		
		/* Submitted? */
		$values = $form->values();
		if ( $values !== FALSE )
		{
			/* Actually take a payment */
			if ( $paymentType === 'pay' )
			{
				/* Load gateway */
				$gateway = NULL;
				if ( isset( $values['payment_method'] ) )
				{
					if ( $values['payment_method'] != 0 )
					{
						$gateway = Gateway::load( $values['payment_method'] );
					}
				}
				else
				{
					$gateway = array_pop( $paymentMethods );
				}
							
				/* Do we already have a "waiting" transaction (which means a manual payment, such as by check or bank wire) we don't
					need to create a new one since it'll be exactly the same. We can just take them to the screen for the transaction
					we already have which shows the instructions they need */
				try
				{
					$existingWaitingTransaction = Db::i()->select( '*', 'nexus_transactions', array(
						't_member=? AND t_invoice=? AND t_method=? AND t_status=? AND t_amount=? AND t_currency=?',
						Member::loggedIn()->member_id,
						$this->invoice->id,
						( $gateway === NULL ) ? 0 : $gateway->_id,
						Transaction::STATUS_WAITING,
						(string) $amountToPay->amount,
						$amountToPay->currency
					) )->first();
	
					Output::i()->redirect( Transaction::constructFromData( $existingWaitingTransaction )->url() );
				}
				catch ( UnderflowException ) { }
	
				/* Create a transaction */
				$transaction = new Transaction;
				$transaction->member = Member::loggedIn();
				$transaction->invoice = $this->invoice;
				$transaction->amount = $amountToPay;
				$transaction->ip = Request::i()->ipAddress();
				
				/* Account Credit? */
				if ( $gateway === NULL )
				{
					$credits = Customer::loggedIn()->cm_credits;
					$inWallet = $credits[ $this->invoice->currency ]->amount;
					if ( $transaction->amount->amount->compare( $inWallet ) === 1 )
					{
						$transaction->amount = new Money( $inWallet, $this->invoice->currency );
					}
					$transaction->status = $transaction::STATUS_PAID;
					$transaction->save();
								
					$credits[ $this->invoice->currency ]->amount = $credits[ $this->invoice->currency ]->amount->subtract( $transaction->amount->amount );
					Customer::loggedIn()->cm_credits = $credits;
					Customer::loggedIn()->save();
					
					$this->invoice->member->log( 'transaction', array(
						'type'			=> 'paid',
						'status'		=> Transaction::STATUS_PAID,
						'id'			=> $transaction->id,
						'invoice_id'	=> $this->invoice->id,
						'invoice_title'	=> $this->invoice->title,
					) );
					
					$transaction->sendNotification();
					
					if ( !$this->invoice->amountToPay()->amount->isGreaterThanZero() )
					{	
						$this->invoice->markPaid();
					}
					
					Output::i()->redirect( $transaction->url() );
				}
				/* Nope - gateway */
				else
				{
					$transaction->method = $gateway;
				}			
							
				/* Create a MaxMind request */
				$maxMind = NULL;
				if ( Settings::i()->maxmind_key and ( !Settings::i()->maxmind_gateways or Settings::i()->maxmind_gateways == '*' or in_array( $transaction->method->id, explode( ',', Settings::i()->maxmind_gateways ) ) ) )
				{
					$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
					$maxMind->setTransaction( $transaction );
				}
				
				/* Authorize */			
				try
				{
					$auth = $gateway->auth( $transaction, $values, $maxMind, $recurrings, 'checkout' );
					if ( is_array( $auth ) )
					{
						return $this->_webhookRedirector( $auth );
					}
					else
					{				
						$transaction->auth = $auth;
					}
				}
				catch ( LogicException $e )
				{
					$form->error = $e->getMessage();
					return $form;
				}
				catch ( RuntimeException $e )
				{
					Log::log( $e, 'checkout' );
					
					$form->error = Member::loggedIn()->language()->addToStack('gateway_err');
					return $form;
				}
							
				/* Check Fraud Rules and capture */
				try
				{
					$memberJustCreated = $transaction->checkFraudRulesAndCapture( $maxMind );
				}
				catch ( LogicException $e )
				{
					$form->error = $e->getMessage();
					return $form;
				}
				catch ( RuntimeException $e )
				{
					Log::log( $e, 'checkout' );
					
					$form->error = Member::loggedIn()->language()->addToStack('gateway_err');
					return $form;
				}			
				
				/* Logged in? */
				if ( $memberJustCreated )
				{
					Session::i()->setMember( $memberJustCreated );
					Device::loadOrCreate( $memberJustCreated, FALSE )->updateAfterAuthentication( NULL );
				}
				
				/* Send email receipt */
				$transaction->sendNotification();
				
				/* Show status screen */
				Output::i()->redirect( $transaction->url() );
			}

			/* Otherwise, we can just go ahead */
			else
			{
				/* If this is a guest checking our, go ahead and create their account */
				$customer = Customer::loggedIn();
				$memberJustCreated = NULL;
				if ( !$customer->member_id )
				{
					$customer = $this->invoice->createAccountForGuest();
					$memberJustCreated = $customer;
				}
				
				/* Did we want to store a card? */
				if ( $paymentType === 'card' )
				{
					try
					{
						$card = CreditCard::createFormSubmit( $values, $customer, FALSE );
					}
					catch ( DomainException $e )
					{
						$form->error = $e->getMessage();
						return $form;
					}
				}
				
				/* Check if there's anything still to pay */
				if ( $this->invoice->amountToPay()->amount->isZero() )
				{
					/* Only mark paid if the invoice itself was worth zero */
					if( $this->invoice->total->amount->isZero() )
					{
						$this->invoice->markPaid();
					}

					/* Redirect */
					$destination = $this->invoice->return_uri ?: $this->invoice->url();
					if ( Member::loggedIn()->member_id )
					{
						Output::i()->redirect( $destination );
					}
					else
					{
						if ( $memberJustCreated )
						{
							Session::i()->setMember( $memberJustCreated );
							Device::loadOrCreate( $memberJustCreated, FALSE )->updateAfterAuthentication( NULL );
						}
						
						Output::i()->redirect( $destination );
					}
				}
				/* They're waiting for approval - show them a screen to indicate this so they don't try to pay twice */
				else
				{
					foreach ( $this->invoice->transactions( array( Transaction::STATUS_HELD, Transaction::STATUS_REVIEW, Transaction::STATUS_GATEWAY_PENDING ) ) as $transaction )
					{
						Output::i()->redirect( $transaction->url() );
					}
					Output::i()->error( 'err_no_methods', '5X196/C', 500, '' );
				}
			}
		}
		
		/* Coupons */
		$couponForm = NULL;
		if ( Db::i()->select( 'COUNT(*)', 'nexus_coupons' )->first() )
		{
			$canUseCoupons = TRUE;
			foreach ( $this->invoice->items as $item )
			{
				if ( !$item->canUseCoupons() )
				{
					$canUseCoupons = FALSE;
					break;
				}
			}
			
			if ( $canUseCoupons )
			{
				$invoice = $this->invoice;
				$couponForm = new Form( 'coupon', 'save', $this->invoice->checkoutUrl()->setQueryString( '_step', 'checkout_pay' ) );
				$couponForm->add( new Custom( 'coupon_code', NULL, TRUE, array(
					'getHtml'	=> function( $field )
					{
						return Theme::i()->getTemplate( 'forms', 'core', 'global' )->text( $field->name, 'text', $field->value, $field->required, 25 );
					},
					'formatValue'	=> function( $field ) use ( $invoice )
					{
						if ( $field->value )
						{
							try
							{
								return Coupon::load( $field->value, 'c_code' )->useCoupon( $invoice, Customer::loggedIn() );
							}
							catch ( OutOfRangeException )
							{
								throw new DomainException('coupon_code_invalid');
							}
						}
						return '';
					}
				) ) );
				if ( $values = $couponForm->values() )
				{
					$invoice->addItem( $values['coupon_code'] );
					$invoice->save();
					Output::i()->redirect( $invoice->checkoutUrl()->setQueryString( '_step', 'checkout_pay' ) );
				}
			}
		}
		
		/* Display */
		return Theme::i()->getTemplate('checkout')->confirmAndPay( $this->invoice, $this->invoice->summary(), $form->customTemplate( array( Theme::i()->getTemplate( 'checkout', 'nexus' ), 'paymentForm' ), $this->invoice, $amountToPay, $showSubmitButton ), $amountToPay, $couponForm?->customTemplate(array(Theme::i()->getTemplate('checkout', 'nexus'), 'couponForm')), $recurrings, $overriddenRenewalTerms );
	}
	
	/**
	 * Split Payment
	 *
	 * @return	void
	 */
	public function split() : void
	{
		/* Load invoice */
		try
		{
			$invoice = Invoice::loadAndCheckPerms( Request::i()->id );

			$minSplitAmount = $invoice->canSplitPayment();
			if ( $minSplitAmount === FALSE )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X196/4', 404, '' );
		}
		
		/* What is the max? */
		$maxSplitAmount = floatval( (string) ( $invoice->amountToPay()->amount->subtract( new \IPS\Math\Number( number_format( $minSplitAmount, Money::numberOfDecimalsForCurrency( $invoice->currency ), '.', '' ) ) ) ) );
				
		/* Build Form */
		$form = new Form( 'split', 'continue', $invoice->checkoutUrl()->setQueryString( 'do', 'split' ) );
		$form->add( new Number( 'split_payment_amount', 0, TRUE, array( 'min' => $minSplitAmount, 'max' => $maxSplitAmount, 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
		
		/* Handle Submissions */
		if ( $values = $form->values() )
		{
			Output::i()->redirect( $invoice->checkoutUrl()->setQueryString( 'split', $values['split_payment_amount'] ) );
		}
		
		/* Display */
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * View Transaction Status
	 *
	 * @return	void
	 */
	public function transaction() : void
	{
		try
		{
			$transaction = Transaction::load( Request::i()->t );
			if ( !$transaction->member->member_id or $transaction->member->member_id !== Member::loggedIn()->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			/* If we're a guest, we may still be able to view for the checkout session */
			if ( !Member::loggedIn()->member_id and isset( Request::i()->cookie['guestTransactionKey'] ) and isset( $transaction->invoice->guest_data['guestTransactionKey'] ) and Login::compareHashes( Request::i()->cookie['guestTransactionKey'], $transaction->invoice->guest_data['guestTransactionKey'] ) )
			{
				/* Allowing it as a guest */
				if ( $transaction->member->member_id )
				{
					Session::i()->setMember( $transaction->member );
					Device::loadOrCreate( $transaction->member, FALSE )->updateAfterAuthentication( NULL );
				}
			}
			else
			{
				Output::i()->error( 'node_error', '2X196/5', 403, '' );
			}
		}

		$output = '';
		$checkoutStatus = '';
		
		switch ( $transaction->status )
		{
			case Transaction::STATUS_PAID:
				$complete = ( $transaction->invoice->status === Invoice::STATUS_PAID );
				$purchases = array();
				$checkoutStatus = 'complete';

				if ( $complete )
				{
					if ( $transaction->invoice->return_uri )
					{
						Output::i()->redirect( $transaction->invoice->return_uri );
					}
					else
					{
						$purchases = $transaction->invoice->purchasesCreated();
					}
				}
				else
				{
					$checkoutStatus = 'continue';
				}
				
				$output = Theme::i()->getTemplate('checkout')->transactionOkay( $transaction, $complete, $purchases );
				break;
				
			case Transaction::STATUS_WAITING:
				$checkoutStatus = 'waiting';
				$output = Theme::i()->getTemplate('checkout')->transactionWait( $transaction );
				break;
				
			case Transaction::STATUS_HELD:
				$checkoutStatus = 'hold';
				$output = Theme::i()->getTemplate('checkout')->transactionHold( $transaction );
				break;
				
			case Transaction::STATUS_REFUSED:
				$checkoutStatus = 'refused';
				$output = Theme::i()->getTemplate('checkout')->transactionFail( $transaction );
				break;
				
			case Transaction::STATUS_GATEWAY_PENDING:
				$checkoutStatus = 'pending';
				$output = Theme::i()->getTemplate('checkout')->transactionGatewayPending( $transaction );
				break;
				
			case Transaction::STATUS_PENDING:
				if ( isset( Request::i()->pending ) )
				{
					$checkoutStatus = 'pending';
					$output = Theme::i()->getTemplate('checkout')->transactionGatewayPending( $transaction );
					break;
				}
			
			default:
				Output::i()->redirect( $transaction->invoice->checkoutUrl() );
		}

		/* Facebook Pixel */
		Pixel::i()->Purchase = array( 'value' => $transaction->invoice->total->amount, 'currency' => $transaction->invoice->total->currency );
		
		Output::i()->output = Theme::i()->getTemplate('checkout')->checkoutWrapper( $output, $checkoutStatus );
	}
	
	/**
	 * Wait for the webhook for a transaction to come through before it has been created
	 *
	 * @return	void
	 */
	public function webhook() : void
	{
		/* Load invoice */
		try
		{
			$this->invoice = Invoice::load( Request::i()->id );
			
			if ( !$this->invoice->canView() )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			/* If we're a geust, we may still be able to view for the checkout session */
			if ( !Member::loggedIn()->member_id and isset( Request::i()->cookie['guestTransactionKey'] ) and isset( $this->invoice->guest_data['guestTransactionKey'] ) and Login::compareHashes( Request::i()->cookie['guestTransactionKey'], $this->invoice->guest_data['guestTransactionKey'] ) )
			{
				// Allowing it as a guest
			}
			else
			{
				Output::i()->error( 'node_error', '2X196/9', 403, '' );
			}
		}
		
		/* Have we decided to give up waiting and just show a pending screen? */
		if ( isset( Request::i()->pending ) )
		{
			$checkoutStatus = 'pending';
			$output = Theme::i()->getTemplate('checkout')->transactionGatewayPending( NULL, $this->invoice );
			Pixel::i()->Purchase = array( 'value' => $this->invoice->total->amount, 'currency' => $this->invoice->total->currency );
			Output::i()->output = Theme::i()->getTemplate('checkout')->checkoutWrapper( $output, $checkoutStatus );
			return;
		}
		
		/* Nope - show a redirector */
		Output::i()->output = $this->_webhookRedirector( isset( Request::i()->exclude ) ? explode( ',', Request::i()->exclude ) : array() );
	}
	
	/**
	 * Get a redirector that points to do=webhook
	 *
	 * @param	array	$exclude		Transaction IDs to exclude
	 * @return	MultipleRedirect
	 */
	protected function _webhookRedirector( array $exclude ) : MultipleRedirect
	{		
		return new MultipleRedirect(
			$this->invoice->checkoutUrl()->setQueryString( array( 'do' => 'webhook', 'exclude' => implode( ',', $exclude ) ) ),
			function( $data ) use ( $exclude ) {	
				if ( $data === NULL )
				{
					return array( time(), Member::loggedIn()->language()->addToStack('processing_your_payment') );
				}
				else
				{
					/* Do we have any transactions yet? */
					foreach ( $this->invoice->transactions( array( Transaction::STATUS_PAID, Transaction::STATUS_HELD, Transaction::STATUS_REFUSED ), $exclude ? array( array( Db::i()->in( 't_id', $exclude, TRUE ) ) ) : array() ) as $transaction )
					{
						Output::i()->redirect( $transaction->url() );
					}
					
					$giveUpTime = ( $data + 60 );
					if ( time() > $giveUpTime )
					{
						return NULL;
					}
					else
					{
						sleep(5);
						return array( $data, Member::loggedIn()->language()->addToStack('processing_your_payment') );
					}
				}
			},
			function() {
				Output::i()->redirect( $this->invoice->checkoutUrl()->setQueryString( array( 'do' => 'webhook', 'pending' => 1 ) ) );
			}
		);
	}

	/**
	 * Virtual Stripe/Apple Domain Verification File
	 *
	 * @return void
	 */
	protected function appleVerification() : void
	{
		if( $file = Gateway::getStripeAppleVerificationFile() )
		{
			Output::i()->sendOutput( $file->contents(), 200, 'text/plain' );
		}
		Output::i()->error( 'node_error', '2X196/5', 403, '' );
	}
}