<?php
/**
 * @brief		Spam Prevention Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Apr 2013
 */

namespace IPS\core\modules\admin\moderation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function defined;
use function in_array;
use function intval;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_NUMERIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Spam Prevention Settings
 */
class spam extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	The current tab
	 */
	protected mixed $activeTab;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Get tab content */
		$this->activeTab = Request::i()->tab ?: 'captcha';

		Dispatcher::i()->checkAcpPermission( 'spam_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Work out output */
		$methodFunction = '_manage' . IPS::mb_ucfirst( $this->activeTab );
		$activeTabContents = $this->$methodFunction();

		/* If this is an AJAX request, just return it */
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
			return;
		}

		/* Build tab list */
		$tabs = array();
		$tabs['captcha']	= 'spamprevention_captcha';
		$tabs['flagging']	= 'spamprevention_flagging';
		$tabs['service']	= 'enhancements__core_SpamMonitoring';

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'qanda_manage' ) and in_array( Login::registrationType(), array( 'normal', 'full' ) ) )
		{
			$tabs['qanda']		= 'qanda_settings';
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'geolocation' ) and in_array( Login::registrationType(), array( 'normal', 'full' ) ) AND Settings::i()->ipsgeoip )
		{
			$tabs['geolocation']  = 'geolocation_settings';
		}

		/* Add a button for logs */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'spam_service_log' ) )
		{
			Output::i()->sidebar['actions']['errorLog'] = array(
					'title'		=> 'spamlogs',
					'icon'		=> 'exclamation-triangle',
					'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=serviceLogs' ),
			);
		}

		/* Add a button for whitelist */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'spam_whitelist_manage' ) )
		{
			Output::i()->sidebar['actions']['whitelist'] = array(
					'title'		=> 'spam_whitelist',
					'icon'		=> 'shield',
					'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=whitelist' ),
			);
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_delete' ) )
		{
			Output::i()->sidebar['actions']['delete_guest_content'] = array(
					'title'		=> 'member_delete_guest_content',
					'icon'		=> 'trash',
					'link'		=> Url::internal( 'app=core&module=members&controller=members&do=deleteGuestContent' ),
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('member_delete_guest_content') )
			);
		}

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_moderation_spam');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->tabs( $tabs, $this->activeTab, $activeTabContents, Url::internal( "app=core&module=moderation&controller=spam" ) );
	}

	/**
	 * Return the CAPTCHA options - abstracted for third parties
	 *
	 * @param	string	$type	'options' for the select options, 'toggles' for the toggles
	 * @return	array
	 */
	protected function getCaptchaOptions( string $type='options' ) : array
	{
		switch( $type )
		{
			case 'options':
				return array( 'none' => 'captcha_type_none', 'invisible' => 'captcha_type_invisible', 'recaptcha2' => 'captcha_type_recaptcha2', 'keycaptcha' => 'captcha_type_keycaptcha', 'hcaptcha' => 	'captcha_type_hcaptcha' );

			case 'toggles':
				return array(
					'none'			=> array( 'bot_antispam_type_warning' ),
					'recaptcha2'	=> array( 'guest_captcha', 'recaptcha2_public_key', 'recaptcha2_private_key' ),
					'invisible'		=> array( 'guest_captcha', 'recaptcha2_public_key', 'recaptcha2_private_key' ),
					'keycaptcha'	=> array( 'guest_captcha', 'keycaptcha_privatekey' ),
					'hcaptcha'		=> array( 'guest_captcha', 'hcaptcha_sitekey', 'hcaptcha_secret')
				);
		}

		return array();
	}

	/**
	 * Show CAPTCHA settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageCaptcha() : string
	{
		/* Build Form */
		$form = new Form;
		$form->add( new Radio( 'bot_antispam_type', Settings::i()->bot_antispam_type, TRUE, array(
			'options'	=> $this->getCaptchaOptions( 'options' ),
			'toggles'	=> $this->getCaptchaOptions( 'toggles' ),
		), NULL, NULL, NULL, 'bot_antispam_type' ) );
		$form->add( new Text( 'recaptcha2_public_key', Settings::i()->recaptcha2_public_key, FALSE, array(), NULL, NULL, NULL, 'recaptcha2_public_key' ) );
		$form->add( new Text( 'recaptcha2_private_key', Settings::i()->recaptcha2_private_key, FALSE, array(), NULL, NULL, NULL, 'recaptcha2_private_key' ) );
		$form->add( new Text( 'keycaptcha_privatekey', Settings::i()->keycaptcha_privatekey, FALSE, array(), NULL, NULL, NULL, 'keycaptcha_privatekey' ) );
		$form->add( new Text( 'hcaptcha_sitekey', Settings::i()->hcaptcha_sitekey, FALSE, array(), NULL, NULL, NULL, 'hcaptcha_sitekey' ) );
		$form->add( new Text( 'hcaptcha_secret', Settings::i()->hcaptcha_secret, FALSE, array(), NULL, NULL, NULL, 'hcaptcha_secret' ) );
		$form->add( new YesNo( 'guest_captcha', Settings::i()->guest_captcha, FALSE, array(), NULL, NULL, NULL, 'guest_captcha' ) );

		/* Save values */
		if ( $form->values() )
		{
			$form->saveAsSettings();

			Session::i()->log( 'acplogs__spamprev_settings' );
		}

		return $form;
	}

	/**
	 * Show spammer flagging settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageFlagging() : string
	{
		/* Build Form */
		$form = new Form;
		$form->add( new CheckboxSet( 'spm_option', explode( ',', Settings::i()->spm_option ), FALSE, array(
			'options' 	=> array( 'disable' => 'spm_option_disable', 'unapprove' => 'spm_option_unapprove', 'delete' => 'spm_option_delete', 'ban' => 'spm_option_ban' ),
		) ) );

		/* Save values */
		if ( $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplogs__spamprev_settings' );
		}

		return $form;
	}

	/**
	 * Show IPS Spam Service settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageService() : string
	{
		$licenseData = IPS::licenseKey();

		/* Build Form */
		$actions = array( 1 => 'spam_service_act_1', 5 => 'spam_service_act_5', 2 => 'spam_service_act_2', 3 => 'spam_service_act_3', 4 => 'spam_service_act_4' );
		$days = json_decode( Settings::i()->spam_service_days, TRUE );

		$form = new Form;
		$form->addHeader( 'enhancements__core_SpamMonitoring' );

		$disabled = FALSE;
		if( !$licenseData or !isset( $licenseData['products']['spam'] ) or !$licenseData['products']['spam'] or ( !$licenseData['cloud'] AND strtotime( $licenseData['expires'] ) < time() ) )
		{
			$disabled = TRUE;
			if( !Settings::i()->ipb_reg_number )
			{
				Member::loggedIn()->language()->words['spam_service_enabled_desc'] = Member::loggedIn()->language()->addToStack( '__ipbnullforums__nulling_null_alert' ); //Changing error message to custom
			}
			else
			{
				Member::loggedIn()->language()->words['spam_service_enabled_desc'] = Member::loggedIn()->language()->addToStack( '__ipbnullforums__nulling_null_alert' ); //Changing error message to custom
			}
		}

		$form->add( new YesNo( 'spam_service_enabled', Settings::i()->spam_service_enabled, FALSE, array( 'disabled' => $disabled, 'togglesOn' => array( 'spam_service_send_to_ips', 'spam_service_action_0', 'spam_service_action_1', 'spam_service_action_2', 'spam_service_action_3', 'spam_service_action_4', 'spam_service_disposable' ) ) ) );
		$form->add( new YesNo( 'spam_service_send_to_ips', Settings::i()->spam_service_send_to_ips, FALSE, array( 'disabled' => $disabled ), NULL, NULL, NULL, 'spam_service_send_to_ips' ) );
		$form->add( new Select( 'spam_service_action_1', Settings::i()->spam_service_action_1, FALSE, array( 'disabled' => $disabled, 'options' => $actions, 'toggles' => array( '5' => array( 'spam_service_action_1_num' ) ) ), NULL, NULL, NULL, 'spam_service_action_1' ) );
		$form->add( new Number( 'spam_service_action_1_num', ( $days[1] ?? -1 ), FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'spam_service_action_unlimited_days'), NULL, NULL, Member::loggedIn()->language()->addToStack('days'), 'spam_service_action_1_num' ) );
		$form->add( new Select( 'spam_service_action_2', Settings::i()->spam_service_action_2, FALSE, array( 'disabled' => $disabled, 'options' => $actions, 'toggles' => array( '5' => array( 'spam_service_action_2_num' ) ) ), NULL, NULL, NULL, 'spam_service_action_2' ) );
		$form->add( new Number( 'spam_service_action_2_num', ( $days[2] ?? -1 ), FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'spam_service_action_unlimited_days'), NULL, NULL, Member::loggedIn()->language()->addToStack('days'), 'spam_service_action_2_num' ) );
		$form->add( new Select( 'spam_service_action_3', Settings::i()->spam_service_action_3, FALSE, array( 'disabled' => $disabled, 'options' => $actions, 'toggles' => array( '5' => array( 'spam_service_action_3_num' ) ) ), NULL, NULL, NULL, 'spam_service_action_3' ) );
		$form->add( new Number( 'spam_service_action_3_num', ( $days[3] ?? -1 ), FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'spam_service_action_unlimited_days'), NULL, NULL, Member::loggedIn()->language()->addToStack('days'), 'spam_service_action_3_num' ) );
		$form->add( new Select( 'spam_service_action_4', Settings::i()->spam_service_action_4, FALSE, array( 'disabled' => $disabled, 'options' => $actions, 'toggles' => array( '5' => array( 'spam_service_action_4_num' ) ) ), NULL, NULL, NULL, 'spam_service_action_4' ) );
		$form->add( new Number( 'spam_service_action_4_num', ( $days[4] ?? -1 ), FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'spam_service_action_unlimited_days'), NULL, NULL, Member::loggedIn()->language()->addToStack('days'), 'spam_service_action_4_num' ) );

		$form->add( new Select( 'spam_service_disposable', Settings::i()->spam_service_disposable, FALSE, array( 'disabled' => $disabled, 'options' => $actions, 'toggles' => array( '5' => array( 'spam_service_disposable_num' ) ) ), NULL, NULL, NULL, 'spam_service_disposable' ) );
		$form->add( new Number( 'spam_service_disposable_num', ( $days['disposable'] ?? -1 ), FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'spam_service_action_unlimited_days'), NULL, NULL, Member::loggedIn()->language()->addToStack('days'), 'spam_service_disposable_num' ) );

		$form->add( new Select( 'spam_service_action_0', Settings::i()->spam_service_action_0, FALSE, array( 'disabled' => $disabled, 'options' => $actions ), NULL, NULL, NULL, 'spam_service_action_0' ) );

		if ( $values = $form->values() )
		{
			$values['spam_service_days'] = array();
			foreach( array( 'action_1', 'action_2', 'action_3', 'action_4', 'disposable' ) as $num )
			{
				if ( $values['spam_service_' . $num ] == 5 )
				{
					$values['spam_service_days'][ str_replace( 'action_', '', $num ) ] = $values['spam_service_' . $num . '_num' ];
				}

				unset( $values['spam_service_' . $num . '_num' ] );
			}

			$values['spam_service_days'] = json_encode( $values['spam_service_days'] );

			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_SpamMonitoring' => TRUE ) );
		}

		return $form;
	}

	/**
	 * Show question and answer challenge settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageQanda() : string
	{
		Dispatcher::i()->checkAcpPermission( 'qanda_manage' );

		/* Create the table */
		$table					= new TableDb( 'core_question_and_answer', Url::internal( 'app=core&module=moderation&controller=spam&tab=qanda' ) );
		$table->include			= array( 'qa_question' );
		$table->joins			= array(
										array( 'select' => 'w.word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'core_question_and_answer_', core_question_and_answer.qa_id ) AND w.lang_id=" . Member::loggedIn()->language()->id )
									);
		$table->parsers			= array(
										'qa_question'		=> function( $val, $row )
										{
											return ( $row['word_custom'] ?: $row['qa_question'] );
										}
									);
		$table->mainColumn		= 'qa_question';
		$table->sortBy			= $table->sortBy ?: 'qa_question';
		$table->quickSearch		= array( 'word_custom', 'qa_question' );
		$table->sortDirection	= $table->sortDirection ?: 'asc';

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'qanda_add' ) )
		{
			$table->rootButtons	= array(
				'add'	=> array(
					'icon'		=> 'plus',
					'title'		=> 'qanda_add_question',
					'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=question' ),
				)
			);
		}

		$table->rowButtons		= function( $row )
		{
			$return	= array();

			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'qanda_edit' ) )
			{
				$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=question&id=' ) . $row['qa_id'],
				);
			}

			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'qanda_delete' ) )
			{
				$return['delete'] = array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=delete&id=' ) . $row['qa_id'],
					'data'		=> array( 'delete' => '' ),
				);
			}

			return $return;
		};

		return Theme::i()->getTemplate( 'spam' )->spamQandASettings( $table );
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function question() : void
	{
		/* Init */
		$id			= 0;
		$question	= array();

		/* Start the form */
		$form	= new Form;

		/* Load question */
		try
		{
			$id	= intval( Request::i()->id );
			$form->hiddenValues['id'] = $id;
			$question	= Db::i()->select( '*', 'core_question_and_answer', array( 'qa_id=?', $id ) )->first();

			Dispatcher::i()->checkAcpPermission( 'qanda_edit' );
		}
		catch ( UnderflowException $e )
		{
			Dispatcher::i()->checkAcpPermission( 'qanda_add' );
		}

		$form->add( new Translatable( 'qa_question', NULL, TRUE, array( 'app' => 'core', 'key' => ( $id ? "core_question_and_answer_{$id}" : NULL ) ) ) );
		$form->add( new Stack( 'qa_answers', $id ? json_decode( $question['qa_answers'], TRUE ) : array(), TRUE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$save = array(
				'qa_answers'	=> json_encode( $values['qa_answers'] ),
			);

			if ( $id )
			{
				Db::i()->update( 'core_question_and_answer', $save, array( 'qa_id=?', $question['qa_id'] ) );

				Session::i()->log( 'acplogs__question_edited' );
			}
			else
			{
				$id	= Db::i()->insert( 'core_question_and_answer', $save );
				Session::i()->log( 'acplogs__question_added' );
			}

			Lang::saveCustom( 'core', "core_question_and_answer_{$id}", $values['qa_question'] );

			Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=spam&tab=qanda' ), 'saved' );
		}

		/* Display */
		Output::i()->title	 		= Member::loggedIn()->language()->addToStack('qanda_settings');
		Output::i()->breadcrumb[]	= array( NULL, Output::i()->title );
		Output::i()->output 		= Theme::i()->getTemplate( 'global' )->block( Output::i()->title, $form );
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	protected function delete() : void
	{
		$id = intval( Request::i()->id );
		Dispatcher::i()->checkAcpPermission( 'qanda_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		Db::i()->delete( 'core_question_and_answer', array( 'qa_id=?', $id ) );
		Session::i()->log( 'acplogs__question_deleted' );

		Lang::deleteCustom( 'core', "core_question_and_answer_{$id}" );

		/* And redirect */
		Output::i()->redirect( Url::internal( "app=core&module=moderation&controller=spam&tab=qanda" ) );
	}

	/**
	 * Spam Service Log
	 *
	 * @return	void
	 */
	protected function serviceLogs() : void
	{
		Dispatcher::i()->checkAcpPermission( 'spam_service_log' );

		/* Create the table */
		$table = new TableDb( 'core_spam_service_log', Url::internal( 'app=core&module=moderation&controller=spam&do=serviceLogs' ) );

		$table->langPrefix = 'spamlogs_';

		/* Columns we need */
		$table->include = array( 'log_date', 'log_code', 'email_address', 'ip_address' );

		$table->sortBy	= $table->sortBy ?: 'log_date';
		$table->sortDirection	= $table->sortDirection ?: 'DESC';

		/* Search */
		$table->advancedSearch = array(
				'email_address'		=> SEARCH_CONTAINS_TEXT,
				'ip_address'		=> SEARCH_CONTAINS_TEXT,
				'log_code'			=> SEARCH_NUMERIC,
		);

		$table->quickSearch = 'email_address';

		/* Custom parsers */
		$table->parsers = array(
				'log_date'				=> function( $val )
				{
					return DateTime::ts( $val )->localeDate();
				},
		);

		/* Add a button for settings */
		Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
						'title'		=> 'prunesettings',
						'icon'		=> 'cog',
						'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=serviceLogSettings' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('prunesettings') )
				),
		);

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('spamlogs');
		Output::i()->output	= (string) $table;
	}

	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function serviceLogSettings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'spam_service_log' );

		$form = new Form;

		$form->add( new Interval( 'prune_log_spam', Settings::i()->prune_log_spam, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_log_spam' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__spamlog_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=spam&do=serviceLogs' ), 'saved' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('spamlogssettings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'spamlogssettings', $form, FALSE );
	}

	/**
	 * Spam defense whitelist
	 *
	 * @return	void
	 */
	protected function whitelist() : void
	{
		Dispatcher::i()->checkAcpPermission( 'spam_whitelist_manage' );
		$table = new TableDb( 'core_spam_whitelist', Url::internal( 'app=core&module=moderation&controller=spam&do=whitelist' ) );

		$table->filters = array(
				'spam_whitelist_ip'		=> 'whitelist_type=\'ip\'',
				'spam_whitelist_domain'	=> 'whitelist_type=\'domain\''
		);

		$table->include    = array( 'whitelist_type', 'whitelist_content', 'whitelist_reason', 'whitelist_date' );
		$table->mainColumn = 'whiteist_content';
		$table->rowClasses = array( 'whitelist_reason' => array( 'ipsTable_wrap' ) );

		$table->sortBy        = $table->sortBy        ?: 'whitelist_date';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->quickSearch   = 'whitelist_content';
		$table->advancedSearch = array(
			'whitelist_reason'	=> SEARCH_CONTAINS_TEXT,
			'whitelist_date'	=> SEARCH_DATE_RANGE
		);

		/* Custom parsers */
		$table->parsers = array(
				'whitelist_date'			=> function( $val )
				{
					return DateTime::ts( $val )->localeDate();
				},
				'whitelist_type'			=> function( $val )
				{
					switch( $val )
					{
						default:
						case 'ip':
							return Member::loggedIn()->language()->addToStack('spam_whitelist_ip_select');

						case 'domain':
							return Member::loggedIn()->language()->addToStack('spam_whitelist_domain_select');

					}
				}
		);

		/* Row buttons */
		$table->rowButtons = function( $row )
		{
			$return = array();

			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'spam_whitelist_edit' ) )
			{
				$return['edit'] = array(
							'icon'		=> 'pencil',
							'title'		=> 'edit',
							'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') ),
							'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=whitelistForm&id=' ) . $row['whitelist_id'],
				);
			}

			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'spam_whitelist_delete' ) )
			{
				$return['delete'] = array(
							'icon'		=> 'times-circle',
							'title'		=> 'delete',
							'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=whitelistDelete&id=' ) . $row['whitelist_id'],
							'data'		=> array( 'delete' => '' ),
				);
			}

			return $return;
		};

		/* Add an add button for whitelist */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'spam_whitelist_add' ) )
		{
			Output::i()->sidebar['actions'] = array(
				'add'	=> array(
					'primary'	=> TRUE,
					'icon'		=> 'plus',
					'title'		=> 'spam_whitelist_add',
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('spam_whitelist_add') ),
					'link'		=> Url::internal( 'app=core&module=moderation&controller=spam&do=whitelistForm' )
				)
			);
		}

        /* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('spam_whitelist');
		Output::i()->output	= (string) $table;
	}

	/**
	 * Whitelist add/edit form
	 *
	 * @return	void
	 */
	protected function whitelistForm() : void
	{
		$current = NULL;
		if ( Request::i()->id )
		{
			$current = Db::i()->select( '*', 'core_spam_whitelist', array( 'whitelist_id=?', Request::i()->id ) )->first();

			Dispatcher::i()->checkAcpPermission( 'spam_whitelist_edit' );
		}
		else
		{
			Dispatcher::i()->checkAcpPermission( 'spam_whitelist_add' );
		}

		/* Build form */
		$form = new Form();
		$form->add( new Select( 'whitelist_type', $current ? $current['whitelist_type'] : NULL, TRUE,
				array(
					'options' => array(
						'ip'    => 'spam_whitelist_ip_select',
						'domain' => 'spam_whitelist_domain_select'
					),
					'toggles' => array(
						'ip' => array( 'whitelist_ip_content' ),
						'domain' => array( 'whitelist_domain_content' )
					)
			) ) );

		$form->add( new Text( 'whitelist_ip_content', $current ? $current['whitelist_content'] : NULL, TRUE, array(), NULL, NULL, NULL, 'whitelist_ip_content' ) );
		$form->add( new Text( 'whitelist_domain_content', $current ? $current['whitelist_content'] : NULL, TRUE, array( 'placeholder' => 'mycompany.com' ), function( $value ) {
			if( isset( $value ) AND mb_stripos( $value, '@' ) )
			{
				throw new DomainException( 'whitelist_domain_email_detected' );
			}
		}, NULL, NULL, 'whitelist_domain_content' ) );
		$form->add( new Text( 'whitelist_reason', $current ? $current['whitelist_reason'] : NULL ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$whitelistContent = $values['whitelist_type'] == 'ip' ? $values['whitelist_ip_content'] : $values['whitelist_domain_content'];
			$save = array(
				'whitelist_type'    => $values['whitelist_type'],
				'whitelist_content' => $whitelistContent,
				'whitelist_reason'  => $values['whitelist_reason'],
				'whitelist_date'	=> time()
			);

			if ( $current )
			{
				unset( $save['whitelist_date'] );
				Db::i()->update( 'core_spam_whitelist', $save, array( 'whitelist_id=?', $current['whitelist_id'] ) );
				Session::i()->log( 'acplog__spam_whitelist_edited', array( 'spam_whitelist_' . $save['whitelist_type'] . '_select' => TRUE, $save['whitelist_content'] => FALSE ) );
			}
			else
			{
				Db::i()->insert( 'core_spam_whitelist', $save );
				Session::i()->log( 'acplog__spam_whitelist_created', array( 'spam_whitelist_' . $save['whitelist_type'] . '_select' => TRUE, $save['whitelist_content'] => FALSE ) );
			}

			Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=spam&do=whitelist' ), 'saved' );
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( $current ? $current['whitelist_content'] : 'add', $form, FALSE );
	}

	/**
	 * Delete whitelist entry
	 *
	 * @return	void
	 */
	protected function whitelistDelete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'spam_whitelist_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$current = Db::i()->select( '*', 'core_spam_whitelist', array( 'whitelist_id=?', Request::i()->id ) )->first();
			Session::i()->log( 'acplog__spam_whitelist_deleted', array( 'whitelist_filter_' . $current['whitelist_type'] . '_select' => TRUE, $current['whitelist_content'] => FALSE ) );
			Db::i()->delete( 'core_spam_whitelist', array( 'whitelist_id=?', Request::i()->id ) );
		}
		catch ( UnderflowException $e ) { }

		Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=spam&do=whitelist' ) );
	}

	/**
	 * Show GeoLocation settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageGeolocation() : string
	{
		$licenseData = IPS::licenseKey();

		/* Build Form */
		$settings = Settings::i()->spam_geo_settings ? json_decode( Settings::i()->spam_geo_settings, true ) : array();

		$matrix = new Matrix;

		if( !$licenseData or !isset( $licenseData['products']['spam'] ) or !$licenseData['products']['spam'] or ( !$licenseData['cloud'] AND strtotime( $licenseData['expires'] ) < time() ) )
		{
			if( !Settings::i()->ipb_reg_number )
			{
				Member::loggedIn()->language()->words['spam_service_enabled_desc'] = Member::loggedIn()->language()->addToStack( '__ipbnullforums__nulling_null_alert' ); //Changing error message to custom
			}
			else
			{
				Member::loggedIn()->language()->words['spam_service_enabled_desc'] = Member::loggedIn()->language()->addToStack( '__ipbnullforums__nulling_null_alert' ); //Changing error message to custom
			}
		}

		$matrix->columns = array(
			'country'	=> function( $key, $value, $data ) {
				$options = array();
				foreach( GeoLocation::$countries AS $country )
				{
					$options[ $country ] = Member::loggedIn()->language()->addToStack( 'country-' . $country );
				}
				return new Select( $key, $value, TRUE, array( 'options' => $options ) );
			},
			'action'	=> function( $key, $value, $data ) {
				return new Select( $key, $value, TRUE, array( 'options' => array( 'moderate' => 'spam_service_act_2', 'block' => 'spam_service_act_4' ) ) );
			}
		);

		$matrix->rows = array();
		foreach( $settings AS $country => $action )
		{
			$matrix->rows[] = array(
				'country'		=> $country,
				'action'		=> $action
			);
		}

		if ( $values = $matrix->values() )
		{
			$save = array();
			foreach( $values AS $key => $value )
			{
				$save[ $value['country'] ] = $value['action'];
			}

			Settings::i()->changeValues( array( 'spam_geo_settings' => json_encode( $save ) ) );
			Output::i()->redirect( Url::internal( "app=core&module=moderation&controller=spam&tab=geolocation" ), 'saved' );
		}

		return Theme::i()->getTemplate( 'spam' )->spamGeoSettings( $matrix );
	}
}