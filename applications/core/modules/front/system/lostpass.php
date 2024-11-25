<?php
/**
 * @brief		Lost Password
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Aug 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Email;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Email as FormEmail;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Handler;
use IPS\Login\Handler\Standard;
use IPS\Login\Success;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Encrypt;
use IPS\Theme;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Lost Password
 */
class lostpass extends Controller
{
	/**
	 * @brief	Is this for displaying "content"? Affects if advertisements may be shown
	 */
	public bool $isContentPage = FALSE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( Settings::i()->allow_forgot_password == 'disabled' )
		{
			Output::i()->error( 'page_doesnt_exist', '2S151/2', 404, '' );
		}
		
		if ( Settings::i()->allow_forgot_password == 'redirect' )
		{
			Output::i()->redirect( Url::external( Settings::i()->allow_forgot_password_target ) );
		}
		
		parent::execute();
	}	
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Build the form */
		$form =  new Form( "lostpass", 'request_password' );
		$form->add( new FormEmail( 'email_address', NULL, TRUE, array( 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL ) ) );

		$captcha = new Captcha;
		
		if ( (string) $captcha !== '' )
		{
			$form->add( $captcha );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('lost_password');
		
		/* Handle the reset */
		if ( $values = $form->values() )
		{
			if( !Login::emailIsInUse( $values['email_address'] ) )
			{
				/* We intentionally show the same message as if the request was successful to avoid leaking information about membership */
				Output::i()->sidebar['enabled'] = FALSE;
				Output::i()->bodyClasses[] = 'ipsLayout_minimal';
				Output::i()->output = Theme::i()->getTemplate( 'system' )->lostPassConfirm( 'lost_pass_confirm' );
				return;
			}

			/* If using "normal" method and we have an account, and at least one login handler we can process a password change for, we're good */
			$member = Member::load( $values['email_address'], 'email' );
			if ( $member->member_id and Settings::i()->allow_forgot_password == 'normal' )
			{
				foreach ( Login::methods() as $method )
				{
					if ( $method->canChangePassword( $member ) )
					{
						$this->_sendForgotPasswordEmail( $member );
						return;
					}
				}
			}
						
			/* If not, send them to the handler if we can */
			foreach( Login::methods() as $method )
			{
				if( $method->emailIsInUse( $values['email_address'] ) === TRUE )
				{
					if ( $url = $method->forgotPasswordUrl() )
					{
						Output::i()->redirect( $url );
					}
				}
			}
			
			/* If we have no way to reset the password, can we allow creating a local password as a last attempt? */
			if ( $member->member_id )
			{
				foreach( Login::methods() as $method )
				{
					if ( $method instanceof Standard )
					{
						$this->_sendForgotPasswordEmail( $member );
						return;
					}
				}
			}
			
			/* Otherwise, sorry, we can't do this */
			Output::i()->error( 'lost_pass_not_possible', '1S151/3', 403, '' );
		}
		
		/* Show form */
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->output = Theme::i()->getTemplate( 'system' )->lostPass( $form );
	}
	
	/**
	 * Send forgot password email
	 *
	 * @param	Member	$member	The member
	 * @return	void
	 */
	protected function _sendForgotPasswordEmail( Member $member ) : void
	{
		/* If we have an existing validation record, we can just reuse it */
		$sendEmail = TRUE;

		/* Delete any lost pass validating records that are older than 45 minutes - These records are only valid for one hour. */
		Db::i()->delete( 'core_validating', [ 'member_id=? AND lost_pass=1 AND entry_date<?', $member->member_id, time() - 2700 ] );

		try
		{
			$existing = Db::i()->select( array( 'vid', 'email_sent' ), 'core_validating', array( 'member_id=? AND lost_pass=1', $member->member_id ) )->first();
			$vid = $existing['vid'];
			
			/* If we sent a lost password email within the last 15 minutes, don't send another one otherwise someone could be a nuisence */
			if ( $existing['email_sent'] and $existing['email_sent'] > ( time() - 900 ) )
			{
				$plainSecurityKey = $existing['security_key'];
				$sendEmail = FALSE;
			}
			else
			{
				$plainSecurityKey = Login::generateRandomString();
				Db::i()->update( 'core_validating', [ 'email_sent' => time(), 'security_key' => Encrypt::fromPlaintext( $plainSecurityKey )->tag() ], [ 'vid=?', $vid ] );
			}
		}
		catch ( UnderflowException $e )
		{
			$vid = md5( $member->members_pass_hash . Login::generateRandomString() );
			$plainSecurityKey = Login::generateRandomString();
			Db::i()->insert( 'core_validating', array(
				'vid'           => $vid,
				'member_id'     => $member->member_id,
				'entry_date'    => time(),
				'lost_pass'     => 1,
				'ip_address'    => $member->ip_address,
				'email_sent'    => time(),
				'security_key'  => Encrypt::fromPlaintext( $plainSecurityKey )->tag()
			) );
		}
					
		/* Send email */
		if ( $sendEmail )
		{
			Email::buildFromTemplate( 'core', 'lost_password_init', array( $member, $vid, $plainSecurityKey ), Email::TYPE_TRANSACTIONAL )->send( $member );
			$message = "lost_pass_confirm";
		}
		else
		{
			$message = "lost_pass_too_soon";
		}
		
		/* Show confirmation page with further instructions */
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->output = Theme::i()->getTemplate( 'system' )->lostPassConfirm( $message );
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

		try
		{
			$record = Db::i()->select( '*', 'core_validating', array( 'vid=? AND member_id=? AND lost_pass=1', Request::i()->vid, Request::i()->mid ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'no_validation_key', '2S151/1', 410, '' );
		}

		/* Check security key is valid. */
		if( !Login::compareHashes( Encrypt::fromTag( $record['security_key'] )->decrypt(), Request::i()->security_key ) )
		{
			Output::i()->error( 'lostpass_invalid_security_key', '2S151/5', 403, '' );
		}

		/* Show a nicer error message if their link has expired */
		if( $record['entry_date'] < DateTime::create()->sub( new DateInterval( 'PT1H' ) )->getTimestamp() )
		{
			Output::i()->error( 'lost_pass_expired', '2S151/4', 410, '' );
		}
		
		/* Show form for new password */
		$form =  new Form( "resetpass", 'save' );
		$form->add( new Password( 'password', NULL, TRUE, array( 'protect' => TRUE, 'showMeter' => Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'strengthMember' => Member::load( Request::i()->mid ) ) ) );
		$form->add( new Password( 'password_confirm', NULL, TRUE, array( 'protect' => TRUE, 'confirm' => 'password' ) ) );

		/* Set new password */
		if ( $values = $form->values() )
		{
			/* Get the member */
			$member = Member::load( $record['member_id'] );

			/* Reset the failed logins storage - we don't need to save because the login handler will do that for us later */
			Db::i()->delete( 'core_login_failures', [ 'login_member_id=?', $member->member_id ] );
			$member->failed_login_count = 0;

			/* Now reset the member's password. If no handlers accept the change, create a local password */
			if ( !$member->changePassword( $values['password'], 'lost' ) )
			{
				$member->setLocalPassword( $values['password'] );
				$member->save();
			}
			
			$member->invalidateSessionsAndLogins( Session::i()->id );
			
			/* Delete validating record and log in */
			Db::i()->delete( 'core_validating', array( 'member_id=? AND lost_pass=1', $member->member_id ) );
			
			$success = new Success( $member, Handler::findMethod( 'IPS\Login\Handler\Standard' ) );
			if ( $success->mfa() )
			{
				$_SESSION['processing2FA'] = array( 'memberId' => $success->member->member_id, 'anonymous' => $success->anonymous, 'remember' => $success->rememberMe, 'destination' => (string) Url::internal( '' ), 'handler' => $success->handler->id );
				Output::i()->redirect( Url::internal( '' )->setQueryString( '_mfaLogin', 1 ) );
			}
			$success->process();
			Output::i()->redirect( Url::internal( '' )->setQueryString( '_fromLogin', 1 ) );
		}

		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->output = Theme::i()->getTemplate( 'system' )->resetPass( $form );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'lost_password' );
	}
}