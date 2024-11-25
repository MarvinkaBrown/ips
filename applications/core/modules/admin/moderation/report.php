<?php
/**
 * @brief		report
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		17 Sep 2024
 */

namespace IPS\core\modules\admin\moderation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * report
 */
class report extends Controller
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
	public function execute(): void
	{
		/* Add a button for settings */
		Output::i()->sidebar['actions'] = array(
			'automatic'	=> array(
				'title'		=> 'manage_automatic_rules',
				'icon'		=> 'bolt',
				'link'		=> Url::internal( 'app=core&module=moderation&controller=reportedContent' )
			),
			'settings'	=> array(
				'title'		=> 'reportedContent_types',
				'icon'		=> 'cog',
				'link'		=> Url::internal( 'app=core&module=moderation&controller=reportedContentTypes' )
			)
		);

		Dispatcher::i()->checkAcpPermission( 'reportedContentTypes_manage' );
		parent::execute();
	}

	/**
	 * Settings form
	 *
	 * @return	void
	 */
	protected function manage()
	{
		Dispatcher::i()->checkAcpPermission( 'reportedContent_manage' );

		/* Work out output */
		Request::i()->tab = isset( Request::i()->tab ) ? Request::i()->tab : 'settings';
		if ( $pos = mb_strpos( Request::i()->tab, '-' ) )
		{
			$tabMethod			= '_manage' . IPS::mb_ucfirst( mb_substr( Request::i()->tab, 0, $pos ) );
			$activeTabContents	= $this->$tabMethod( mb_substr( Request::i()->tab, $pos + 1 ) );
		}
		else
		{
			$tabMethod			= '_manage' . IPS::mb_ucfirst( Request::i()->tab );
			$activeTabContents	= $this->$tabMethod();
		}

		/* If this is an AJAX request, just return it */
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
			return;
		}

		/* Build tab list */
		$tabs = [
			'settings'	=> 'menu__core_moderation_report',
			'notifications' => 'report_author_notification_title'
		];

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_moderation_report');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->tabs( $tabs, Request::i()->tab, $activeTabContents, Url::internal( "app=core&module=moderation&controller=report" ) );
	}

	/**
	 * Manage settings
	 *
	 * @return string
	 */
	protected function _manageSettings(): string
	{
		$form = new Form;

		/* Can guests report */
		$guest = new Member;
		$guestDisabled = false;
		$guestSetting = Settings::i()->report_capture_guest_details;
		if ( ! $guest->group['g_can_report'] )
		{
			$guestDisabled = true;
			$guestSetting = false;
			Member::loggedIn()->language()->words['report_capture_guest_details_warning'] = Member::loggedIn()->language()->addToStack( 'report_capture_guest_details__warning' );
		}

		$form->add( new YesNo( 'report_capture_guest_details', $guestSetting, FALSE, array( 'disabled' => $guestDisabled, 'togglesOn' => [ 'report_guest_details_name_mandatory', 'report_guest_details_store_days'] ), NULL, NULL, NULL, 'report_capture_guest_details' ) );
		$form->add( new YesNo( 'report_guest_details_name_mandatory', Settings::i()->report_guest_details_name_mandatory, FALSE, array(), NULL, NULL, NULL, 'report_guest_details_name_mandatory' ) );
		$form->add( new Number( 'report_guest_details_store_days', Settings::i()->report_guest_details_store_days, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'report_guest_details_store_unlimited' ), NULL, NULL, Member::loggedIn()->language()->addToStack('days'), 'report_guest_details_store_days' ) );

		$form->add( new YesNo( 'report_content_mandatory', Settings::i()->report_content_mandatory, FALSE, array(), NULL, NULL, NULL, 'report_content_mandatory' ) );

		$form->add( new YesNo( 'automoderation_enabled', Settings::i()->automoderation_enabled, FALSE, array( 'togglesOn' => [ 'automoderation_report_again_mins' ] ), NULL, NULL, NULL, 'automoderation_enabled' ) );
		$form->add( new Number( 'automoderation_report_again_mins', Settings::i()->automoderation_report_again_mins, FALSE, array(), NULL, NULL, Member::loggedIn()->language()->addToStack('automoderation_report_again_mins_suffix'), 'automoderation_report_again_mins' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings();

			Db::i()->update( 'core_tasks', array( 'enabled' => (int) $values['automoderation_enabled'] ), array( '`key`=?', 'automaticmoderation' ) );

			Session::i()->log( 'acplog__automoderation_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=report&tab=settings' ), 'saved' );
		}

		return Theme::i()->getTemplate('forms')->blurb( 'reporting_content_blurb', true, true ) . $form;
	}

	/**
	 * @return string
	 */
	protected function _manageNotifications(): string
	{
		/* Init */
		$table = new \IPS\Helpers\Table\Db( 'core_rc_author_notification_text', Url::internal( 'app=core&module=moderation&controller=report&tab=notifications' ) );
		$table->include = array( 'title' );
		$table->sortBy        = $table->sortBy        ?: 'title';
		$table->sortDirection = $table->sortDirection ?: 'asc';

		/* Row buttons */
		$table->rowButtons = function( $row )
		{
			$return = array();

			$return['edit'] = array(
				'icon'	=> 'pencil',
				'link'	=> Url::internal( 'app=core&module=moderation&controller=report&tab=notifications&do=notificationForm&id=' ) . $row['id'],
				'title'	=> 'edit',
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
			);

			$return['delete'] = array(
				'icon'	=> 'times-circle',
				'link'	=> Url::internal( 'app=core&module=moderation&controller=report&tab=notifications&do=notificationDelete&id=' ) . $row['id'],
				'title'	=> 'delete',
				'data'	=> array( 'delete' => '' )
			);

			return $return;
		};

		/* Add button */
		$table->rootButtons = array(
			'add'	=> array(
				'icon'	=> 'plus',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=moderation&controller=report&tab=notifications&do=notificationForm' ),
				'title'	=> 'add',
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add') )
			)
		);

		/* Display */
		return Theme::i()->getTemplate('forms')->blurb( 'reporting_content_notifications_blurb', true, true ) . $table;
	}

	/**
	 * Report notification to author of content form
	 *
	 * @return	void
	 */
	protected function notificationForm()
	{
		$current = NULL;
		if ( Request::i()->id )
		{
			$current = Db::i()->select( '*', 'core_rc_author_notification_text', array( 'id=?', Request::i()->id ) )->first();
		}

		$form = new Form;
		$form->add( new Text( 'report_notifications_title', ( $current ? $current['title'] : '' ), true ) );
		$form->add( new TextArea( 'report_notifications_text', ( $current ? $current['text'] : '' ), true, [ 'rows' => 20 ] ) );

		if ( $values = $form->values() )
		{
			$save = [
				'title'	=> $values['report_notifications_title'],
				'text'	=> $values['report_notifications_text']
			];

			if ( $current )
			{
				Db::i()->update( 'core_rc_author_notification_text', $save, array( 'id=?', $current['id'] ) );
				Session::i()->log( 'acplog__report_author_notification_edited', array( $current['title'] => true ) );
			}
			else
			{
				Db::i()->insert( 'core_rc_author_notification_text', $save );
				Session::i()->log( 'acplog__report_author_notification_created', array( $save['title'] => true ) );
			}

			Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=report&tab=notifications' ), 'saved' );
		}

		Output::i()->output .= Theme::i()->getTemplate( 'global' )->block( 'report_author_notification_title', $form, FALSE );
	}

	/**
	 * Report notification to author of content delete
	 *
	 * @return	void
	 */
	protected function notificationDelete()
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$current = Db::i()->select( '*', 'core_rc_author_notification_text', array( 'id=?', Request::i()->id ) )->first();

			Session::i()->log( 'acplog__report_author_notification_deleted', array( $current['title'] => true ) );
			Db::i()->delete( 'core_rc_author_notification_text', array( 'id=?', Request::i()->id ) );
		}
		catch ( \UnderflowException $e ) { }

		Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=report&tab=notifications' ), 'deleted' );
	}
}