<?php
/**
 * @brief		Error Logs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 Aug 2013
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Error Logs
 */
class errorLogs extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'system_logs_view' );
		parent::execute();
	}
	
	/**
	 * Get table
	 *
	 * @param	Url	$url	The URL where the table will be displayed
	 * @return	Db
	 */
	public static function table( Url $url ) : Db
	{
		$table = new Db( 'core_error_logs', $url );
		$table->langPrefix = 'errorlogs_';
		$table->include = array( 'log_error_code', 'log_error', 'log_ip_address', 'log_request_uri', 'log_member', 'log_date' );
		$table->mainColumn = 'log_error_code';
		$table->widths = array( 'log_error_code' => 10, 'log_ip_address' => 10, 'log_request_uri' => 30, 'log_date' => 10, 'log_member' => 10 );
		$table->rowClasses = array( 'log_error' => array( 'ipsTable_wrap' ), 'log_request_uri' => array( 'ipsTable_wrap' ) );
		$table->parsers = array(
			'log_member'	=> function( $val )
			{
				$member = Member::load( $val );

				if( $member->member_id )
				{
					return htmlentities( $member->name, ENT_DISALLOWED, 'UTF-8', FALSE );
				}
				else
				{
					return '';
				}
			},
			'log_ip_address'=> function( $val )
			{
				if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) )
				{
					return "<a href='" . Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
				}
				return $val;
			},
			'log_date'		=> function( $val )
			{
				return (string) DateTime::ts( $val );
			},
			'log_error'		=> function( $val )
			{
				return Member::loggedIn()->language()->addToStack( $val );
			},
			'log_request_uri'	=> function( $val )
			{
				return Theme::i()->getTemplate( 'global', 'core', 'global' )->truncatedUrl( $val );
			}
		);
		$table->sortBy = $table->sortBy ?: 'log_date';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		$table->advancedSearch = array(
			'log_member'			=> SEARCH_MEMBER,
			'log_ip_address'		=> SEARCH_CONTAINS_TEXT,
			'log_date'				=> SEARCH_DATE_RANGE,
			'log_error'				=> SEARCH_CONTAINS_TEXT,
			'log_error_code'		=> SEARCH_CONTAINS_TEXT,
			'log_request_uri'		=> SEARCH_CONTAINS_TEXT,
		);
		$table->quickSearch = 'log_error';
		
		return $table;
	}

	/**
	 * Manage Error Logs
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'diagnostic_log_settings' ) )
		{
			Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'title'		=> 'settings',
					'icon'		=> 'cog',
					'link'		=> Url::internal( 'app=core&module=support&controller=errorLogs&do=settings' ),
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
				)
			);
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('errorlogs');
		Output::i()->output	= (string) static::table( Url::internal( 'app=core&module=support&controller=errorLogs' ) );
	}
	
	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'diagnostic_log_settings' );
		
		$levelOptions = array(
			'0' => 'level_number_0',
			'1'	=> 'level_number_1',
			'2'	=> 'level_number_2',
			'3'	=> 'level_number_3',
			'4'	=> 'level_number_4',
			'5'	=> 'level_number_5',
		);
		
		$form = new Form;
		$form->add( new Radio( 'error_log_level', Settings::i()->error_log_level, FALSE, array( 'options' => $levelOptions ) ) );
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'error_prune' ) )
		{
			$form->add( new Interval( 'prune_log_error', Settings::i()->prune_log_error, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_log_error' ) );
		}
		
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__errorlog_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=support&controller=errorLogs' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('errorlogssettings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'errorlogssettings', $form, FALSE );
	}

}