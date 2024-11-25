<?php
/**
 * @brief		email
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Email as EmailClass;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Email as FormEmail;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function get_class;
use function is_string;
use const IPS\CIC;
use const IPS\DEMO_MODE;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * email
 */
class email extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'email_manage' );
		parent::execute();
	}

	/**
	 * Email Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Build the settings form */
		$messageHtml = '';

		$settingsForm = new Form( 'settings_form' );
		$settingsForm->addHeader( 'basic_settings' );
		$settingsForm->add( new FormEmail( 'email_out', Settings::i()->email_out, TRUE, array(), NULL, NULL, NULL, 'email_out' ) );
		$settingsForm->add( new FormEmail( 'email_in', Settings::i()->email_in, TRUE ) );
		$settingsForm->add( new Color( 'email_color', Settings::i()->email_color, TRUE ) );
		$settingsForm->add( new Upload( 'email_logo', Settings::i()->email_logo ? File::get( 'core_Theme', Settings::i()->email_logo ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'core_Theme' ) ) );
		$settingsForm->add( new YesNo( 'social_links_in_email', Settings::i()->social_links_in_email, FALSE, array(), NULL, NULL, NULL, 'social_links_in_email' ) );
		$settingsForm->add( new YesNo( 'our_picks_in_email', Settings::i()->our_picks_in_email, FALSE, array(), NULL, NULL, NULL, 'our_picks_in_email' ) );

		
		$settingsForm->add( new YesNo( 'email_truncate', ( Settings::i()->email_truncate ), FALSE, array(), NULL, NULL, NULL, 'email_truncate' ) );
		$settingsForm->add( new YesNo( 'email_log_do', ( Settings::i()->prune_log_emailstats != 0 ), FALSE, array( 'togglesOn' => array( 'prune_log_emailstats' ) ), NULL, NULL, NULL, 'email_log_do' ) );
		$settingsForm->add( new Interval( 'prune_log_emailstats', Settings::i()->prune_log_emailstats ?: 60, FALSE, array( 'valueAs' => 'd', 'unlimited' => '-1', 'unlimitedLang' => 'never' ), NULL, NULL, NULL, 'prune_log_emailstats' ) );
		if ( !DEMO_MODE )
		{
			$settingsForm->addHeader( 'advanced_settings' );
					
			$method = Settings::i()->mail_method;
			$disabled = array();
	
			if ( Settings::i()->sendgrid_api_key AND Settings::i()->sendgrid_use_for > 0  )
			{
				if ( Settings::i()->sendgrid_use_for == 2 )
				{
					$method = 'sendgrid';
				}
			}
			else
			{
				$disabled[] = 'sendgrid';
			}
	
			/* We previously renamed this to 'php' */
			if( $method == 'mail' )
			{
				$method = 'php';
			}

			$settingsForm->add( new Radio( 'mail_method', $method, TRUE, array(
				'options' 	=> array( 'php' => ( CIC ? 'mail_method_cic' : 'mail_method_php' ), 'smtp' => 'mail_method_smtp', 'sendgrid' => 'mail_method_sendgrid' ),
				'disabled'	=> $disabled,
				'toggles' => array(
					'php'		=> array( 'php_mail_extra' ),
					'smtp'		=> array( 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_helo', 'smtp_protocol' ),
				)
			) ) );
			$options = array( 'plain' => 'smtp_plaintext', 'ssl' => 'smtp_ssl', 'tls' => 'smtp_tls' );
			$settingsForm->add( new Text( 'smtp_host', Settings::i()->smtp_host, FALSE, array(), NULL, NULL, NULL, 'smtp_host' ) );
			$settingsForm->add( new Select( 'smtp_protocol', Settings::i()->smtp_protocol, FALSE, array( 'options' => $options ), NULL, NULL, NULL, 'smtp_protocol' ) );
			$settingsForm->add( new Number( 'smtp_port', Settings::i()->smtp_port, FALSE, array(), NULL, NULL, NULL, 'smtp_port' ) );
			$settingsForm->add( new Text( 'smtp_user', Settings::i()->smtp_user, FALSE, array(), NULL, NULL, NULL, 'smtp_user' ) );
			$settingsForm->add( new Password( 'smtp_pass', Settings::i()->smtp_pass, FALSE, array( 'enforceMaxLimit' => FALSE ), NULL, NULL, NULL, 'smtp_pass' ) );
			if ( !CIC )
			{
				$settingsForm->add( new Text( 'php_mail_extra', Settings::i()->php_mail_extra, FALSE, array(), NULL, NULL, NULL, 'php_mail_extra' ) );
			}
		}
		if ( $values = $settingsForm->values() )
		{
			$sendTestEmail = FALSE;

			foreach( array( 'mail_method', 'smtp_host', 'smtp_port', 'smtp_pass', 'smtp_user', 'php_mail_extra', 'smtp_protocol' ) as $setting )
			{
				if( isset( $values[ $setting ] ) AND $values[ $setting ] != Settings::i()->$setting )
				{
					$sendTestEmail = TRUE;
				}
			}

			if( !$values['email_log_do'] )
			{
				$values['prune_log_emailstats'] = 0;
			}

			unset( $values['email_log_do'] );
			
			if ( $values['mail_method'] != 'sendgrid' and Settings::i()->sendgrid_use_for == 2 )
			{
				$values['sendgrid_use_for'] = 1;
			}
			elseif ( $values['mail_method'] == 'sendgrid' )
			{
				$values['sendgrid_use_for'] = 2;
			}

			if ( $values['email_logo'] )
			{
				$values['email_logo'] = (string) $values['email_logo'];
			}

			$settingsForm->saveAsSettings( $values );
			Session::i()->log( 'acplogs__email_settings' );

			if( $sendTestEmail )
			{
				$email			=  EmailClass::buildFromContent( Member::loggedIn()->language()->get('email_test_subject'), Member::loggedIn()->language()->addToStack('email_test_message'), Member::loggedIn()->language()->addToStack('email_test_message'),  EmailClass::TYPE_BULK );
				
				try
				{
					$result = $email->send( to: Member::loggedIn(), fromEmail:  Settings::i()->email_out, returnException: TRUE );
					if( $result )
					{
						$messageHtml = Theme::i()->getTemplate( 'global', 'core', 'global' )->message( 'email_test_okay_auto', 'success' );
					}

					/* Sent successfully, remove notification */
					AdminNotification::remove( 'core', 'ConfigurationError', 'failedMail' );
					Db::i()->update( 'core_mail_error_logs', array( 'mlog_notification_sent' => TRUE ) );
				}
				catch ( Exception $e )
				{
					$messageHtml = Theme::i()->getTemplate( 'global', 'core', 'global' )->message( $e->getMessage() ?: 'email_test_error_auto', 'error', $e->getMessage() );
				}
			}
		}
		
		
		/* Build the test form */
		$testFormHtml = '';
		if (  !DEMO_MODE AND Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'email_test' ) )
		{
			Output::i()->sidebar['actions']['test'] = array(
				'title'		=> 'email_test',
				'icon'		=> 'cog',
				'link'		=> Url::internal( '#testForm' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('email_test'), 'ipsDialog-content' => '#testForm' )
			);
			
			$testForm = new Form( 'test_form' );
			$testForm->add( new FormEmail( 'from', Settings::i()->email_out, TRUE ) );
			$testForm->add( new FormEmail( 'to', Member::loggedIn()->email, TRUE ) );
			$testForm->add( new Text( 'subject', Member::loggedIn()->language()->addToStack('email_test_subject'), TRUE ) );
			$testForm->add( new TextArea( 'message', Member::loggedIn()->language()->addToStack('email_test_message'), TRUE ) );
			if ( $values = $testForm->values() )
			{
				try
				{
					$email =  EmailClass::buildFromContent( $values['subject'], $values['message'], $values['message'],  EmailClass::TYPE_BULK );
					$result = $email->send( to: $values['to'], fromEmail: $values['from'], returnException: TRUE );

					/* Sent successfully, remove notification */
					AdminNotification::remove( 'core', 'ConfigurationError', 'failedMail' );
					Db::i()->update( 'core_mail_error_logs', array( 'mlog_notification_sent' => TRUE ) );
					if( $result )
					{
						$messageHtml = Theme::i()->getTemplate( 'global', 'core', 'global' )->message( 'email_test_okay', 'success' );
					}

				}
				catch ( Exception $exception )
				{
					$content = $exception->getMessage();
					if ( !( $exception instanceof \IPS\Email\Outgoing\Exception ) )
					{
						$content = get_class( $exception ) . ": " . $exception->getMessage() . " (" . $exception->getCode() . ")";
					}
					$messageHtml = Theme::i()->getTemplate( 'global', 'core', 'global' )->message( $content, 'error' );
				}
			}

			$testFormHtml = Theme::i()->getTemplate( 'global' )->block( 'email_test', $testForm, FALSE, 'ipsJS_hide', 'testForm' );
		}
		
		/* Add a button for logs */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'email_errorlog' ) )
		{
			Output::i()->sidebar['actions']['errorLog'] = array(
				'title'		=> 'emailerrorlogs',
				'icon'		=> 'exclamation-triangle',
				'link'		=> Url::internal( 'app=core&module=settings&controller=email&do=errorLog' ),
			);
		}
		
		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('email_settings');
		Output::i()->output	= $messageHtml;
		Output::i()->output	.= Theme::i()->getTemplate( 'global' )->block( 'email_settings', $settingsForm );
		Output::i()->output	.= $testFormHtml;
	}
	
	/**
	 * Error Log
	 *
	 * @return	void
	 */
	protected function errorLog() : void
	{
		Dispatcher::i()->checkAcpPermission( 'email_errorlog' );
		
		/* Add a button for settings */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'email_errorlog_prune' ) )
		{
			Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'title'		=> 'prunesettings',
					'icon'		=> 'cog',
					'link'		=> Url::internal( 'app=core&module=settings&controller=email&do=errorLogSettings' ),
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('prunesettings') )
				),
			);
		}

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('emailerrorlogs');
		Output::i()->output	= (string) static::emailErrorLogTable( Url::internal( 'app=core&module=settings&controller=email&do=errorLog' ) );
	}
	
	/**
	 * Get email error log table
	 *
	 * @param	Url	$url	Base URL for table
	 * @param 	array			$where	Where Array
	 * @return    TableDb
	 */
	public static function emailErrorLogTable( Url $url, array $where=array() ): TableDb
	{
		/* Create the table */
		$table = new TableDb( 'core_mail_error_logs', $url, $where );
		$table->langPrefix = 'emailerrorlogs_';
		
		/* Columns we need */
		$table->include	= array( 'mlog_to', 'mlog_subject', 'mlog_date', 'mlog_msg' );
		$table->rowClasses = array( 'mlog_subject' => array( 'ipsTable_wrap' ), 'mlog_msg' => array( 'ipsTable_wrap' ) );

		$table->sortBy	= $table->sortBy ?: 'mlog_date';
		$table->sortDirection	= $table->sortDirection ?: 'DESC';
		$table->noSort	= array( 'mlog_msg' );
		
		/* Search */
		$table->quickSearch = 'mlog_to';
		$table->advancedSearch = array(
				'mlog_to'			=> SEARCH_CONTAINS_TEXT,
				'mlog_from'			=> SEARCH_CONTAINS_TEXT,
				'mlog_subject'		=> SEARCH_CONTAINS_TEXT,
				'mlog_msg'			=> SEARCH_CONTAINS_TEXT,
				'mlog_content'		=> SEARCH_CONTAINS_TEXT,
		);
		
		/* Custom parsers */
		$table->parsers = array(
			'mlog_date'				=> function( $val )
			{
				return DateTime::ts( $val );
			},
			'mlog_msg'				=> function( $val, $row )
			{
				if( !$val )
				{
					$val = Member::loggedIn()->language()->get('phpmail_not_sent');
				}
				else
				{
					if( $data = json_decode( $val, true ) )
					{
						/* We may not have a 'message' key if this is an older SendGrid log entry */
						if( isset( $data['message'] ) )
						{
							$val = is_string( $data['message'] ) ?
								( ( isset( $data['details'] ) AND $data['details'] ) ? Member::loggedIn()->language()->addToStack( $data['message'], FALSE, array( 'sprintf' => $data['details'] ) ) : Member::loggedIn()->language()->addToStack( $data['message'] ) ) :
								$val;
						}
					}
				}

				return Theme::i()->getTemplate( 'logs' )->emailErrorLog( nl2br( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) ), $row );
			},
			'mlog_content'				=> function( $val, $row )
			{
				return Theme::i()->getTemplate( 'logs' )->emailErrorBody( $val, $row );
			},
		);

		/* Row buttons */
		$table->rowButtons = function( $row )
		{
			$return = array();

			$return['view'] = array(
				'icon'		=> 'search',
				'title'		=> 'view_email_error_body',
				'link'		=> Url::internal( 'app=core&module=settings&controller=email&do=errorLogView&id=' ) . $row['mlog_id'],
				'hotkey'	=> 'Return',
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('emailerrorlogs_mlog_content') )
			);

			$return['resend'] = array(
				'icon'		=> 'refresh',
				'title'		=> 'resend_email_error',
				'link'		=> Url::internal( 'app=core&module=settings&controller=email&do=errorLogResend&id=' . $row['mlog_id'] )->csrf(),
			);

			return $return;
		};
		
		/* Return */
		return $table;
	}

	/**
	 * Attempt to resend the email that failed
	 *
	 * @return void
	 */
	public function errorLogResend() : void
	{
		Dispatcher::i()->checkAcpPermission( 'email_errorlog' );
		Session::i()->csrfCheck();
		
		$id			= (int) Request::i()->id;
		$log		= Db::i()->select( '*', 'core_mail_error_logs', array( 'mlog_id=?', $id ) )->first();
		$emailData	= json_decode( $log['mlog_resend_data'], true );
		
		try
		{
			$fromName = NULL;
			if ( isset( $emailData['headers']['From'] ) and preg_match( '/=\?UTF-8\?B\?(.+?)?= /', $emailData['headers']['From'], $matches ) )
			{
				$fromName = base64_decode( $matches[1] );
			}

			/* Reset basic headers for failed mails */
			unset( $emailData['headers']['MIME-Version'] );
			unset( $emailData['headers']['Content-Type'] );
			unset( $emailData['headers']['Content-Transfer-Encoding'] );
			
			$email =  EmailClass::buildFromContent( $log['mlog_subject'], $emailData['body']['html'], $emailData['body']['plain'], $emailData['type'] ?? EmailClass::TYPE_TRANSACTIONAL );
			$email->_send(
				array_map( 'trim', explode( ',', $log['mlog_to'] ) ),
				isset( $emailData['headers']['Cc'] ) ? array_map( 'trim', explode( ',', $emailData['headers']['Cc'] ) ) : array(),
				isset( $emailData['headers']['Bcc'] ) ? array_map( 'trim', explode( ',', $emailData['headers']['Bcc'] ) ) : array(),
				$log['mlog_from'],
				$fromName,
				$emailData['headers']
			);
		}
		catch( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '4C143/1', 500, '' );
		}

		Db::i()->delete( 'core_mail_error_logs', array( 'mlog_id=?', $id ) );

		/* Remove notification since this was re-sent with success and it is below the recent threshold */
		if(  EmailClass::countFailedMail( DateTime::create()->sub( new DateInterval( 'P3D' ) ), FALSE, TRUE ) <= 3 )
		{
			AdminNotification::remove( 'core', 'ConfigurationError', 'failedMail' );
			Db::i()->update( 'core_mail_error_logs', array( 'mlog_notification_sent' => TRUE ), array( 'mlog_id=?', $id ) );
		}

		Output::i()->redirect( Url::internal( "app=core&module=settings&controller=email&do=errorLog" ), 'emailerror_resent' );
	}

	/**
	 * View a failed email details
	 *
	 * @return void
	 */
	public function errorLogView() : void
	{
		Dispatcher::i()->checkAcpPermission( 'email_errorlog' );
		
		$id		= (int) Request::i()->id;
		$log	= Db::i()->select( '*', 'core_mail_error_logs', array( 'mlog_id=?', $id ) )->first();

		Output::i()->output = Theme::i()->getTemplate('logs')->emailErrorBody( $log );
	}

	/**
	 * Error log Prune Settings
	 *
	 * @return	void
	 */
	protected function errorLogSettings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'email_errorlog_prune' );
		
		$form = new Form;
		$form->add( new Interval( 'prune_log_email_error', Settings::i()->prune_log_email_error, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_log_email_error' ) );
		
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__emailerrorlog_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=email&do=errorLog' ), 'saved' );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('emailerrorlogssettings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'emailerrorlogssettings', $form, FALSE );
	}
}
