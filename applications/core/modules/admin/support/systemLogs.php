<?php
/**
 * @brief		System Logs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Mar 2016
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DirectoryIterator;
use DomainException;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_SELECT;
use const IPS\NO_WRITES;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Error Logs
 */
class systemLogs extends Controller
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
	 * Manage Error Logs
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Button to settings */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'diagnostic_log_settings' ) )
		{
			Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'title'		=> 'prunesettings',
					'icon'		=> 'cog',
					'link'		=> Url::internal( 'app=core&module=support&controller=systemLogs&do=logSettings' ),
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('prunesettings') )
				),
			);

			Output::i()->sidebar['actions']['delete'] = array(
				'icon'	=> 'trash',
				'link'	=> Url::internal( 'app=core&module=support&controller=systemLogs&do=deleteAll' )->csrf(),
				'title'	=> 'nulled_delete_logs',
				'data'	=> array( 'confirm' => '' )
			);
		}

		/* Button to view filebased logs */
		$dir = Log::fallbackDir();
		if ( !NO_WRITES and is_dir( $dir ) )
		{
			$hasFiles = FALSE;
			$dir = new DirectoryIterator( $dir );
			foreach ( $dir as $file )
			{
				if ( mb_substr( $file, 0, 1 ) !== '.' and $file != 'index.html' )
				{
					$hasFiles = TRUE;
					break;
				}
			}
			
			if ( $hasFiles )
			{
				Output::i()->sidebar['actions']['files'] = array(
					'title'		=> 'log_view_file_logs',
					'icon'		=> 'search',
					'link'		=> Url::internal( 'app=core&module=support&controller=systemLogs&do=fileLogs' ),
				);
			}
		}


		/* Create table */
		$table = new \IPS\Helpers\Table\Db( 'core_log', Url::internal( 'app=core&module=support&controller=systemLogs' ) );
		$table->langPrefix = 'log_';
		$table->include = array( 'time', 'category', 'message' );
		$table->parsers = array(
			'message'	=> function( $val, $row )
			{
				if ( mb_strlen( $val ) > 100 )
				{
					$val = mb_substr( $val, 0, 100 ) . '...';
				}
				if ( $row['exception_class'] )
				{
					$val = "{$row['exception_class']} ({$row['exception_code']})\n{$val}";
				}

				return htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE );
			},
			'time'		=> function( $val )
			{
				return DateTime::ts( $val );
			},
			'member_id'	=> function ( $val )
			{
				return htmlspecialchars( Member::load( $val )->name, ENT_DISALLOWED, 'UTF-8', FALSE );
			}
		);
		$table->sortBy = $table->sortBy ?: 'time';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		$table->quickSearch = 'message';
		$table->advancedSearch = array(
			'category'	=> array( SEARCH_SELECT, array( 'options' => iterator_to_array( Db::i()->select( 'DISTINCT(category) AS cat', 'core_log' )->setKeyField( 'cat' )->setValueField( 'cat' ) ), 'multiple' => TRUE, 'parse' => 'normal' ) ),
			'message'	=> SEARCH_CONTAINS_TEXT,
			'time'		=> SEARCH_DATE_RANGE,
			'member_id'	=> SEARCH_MEMBER,
			'url'		=> SEARCH_CONTAINS_TEXT
		);
		$table->rowButtons = function( $row ) {
			return array(
				'view'		=> array(
					'title'	=> 'view',
					'icon'	=> 'search',
					'link'	=> Url::internal( 'app=core&module=support&controller=systemLogs&do=view' )->setQueryString( 'id', $row['id'] )
				),
				'delete'	=> array(
					'title'	=> 'delete',
					'icon'	=> 'times-circle',
					'link'	=> Url::internal( 'app=core&module=support&controller=systemLogs&do=delete' )->setQueryString( 'id', $row['id'] )->csrf(),
					'data'	=> array( 'delete' => '' )
				)
			);
		};
		
		/* Display */		
		Output::i()->title = Member::loggedIn()->language()->addToStack('r__system_logs');
		Output::i()->output = $table;
	}
	
	/**
	 * View a log
	 * 
	 * @return void
	 */
	protected function view() : void
	{
		/* Load */
		try
		{
			$log = Log::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C324/1', 404, '' );
		}
		
		/* Delete button */
		Output::i()->sidebar['actions']['delete'] = array(
			'icon'	=> 'times-circle',
			'link'	=> Url::internal( 'app=core&module=support&controller=systemLogs&do=delete' )->setQueryString( 'id', $log->id )->csrf(),
			'title'	=> 'delete',
			'data'	=> array( 'confirm' => '' )
		);
		
		/* Display */
		Output::i()->title	 = Member::loggedIn()->language()->addToStack('r__system_logs');
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=support&controller=systemLogs" ), Member::loggedIn()->language()->addToStack('r__system_logs') );
		Output::i()->output = Theme::i()->getTemplate( 'system' )->systemLogView( $log );
	}

	/**
	 * Delete all logs
	 * 
	 * @return void
	 */
	protected function deleteAll()
	{
		/* Delete */
		Db::i()->query( "TRUNCATE " . Settings::i()->sql_tbl_prefix . "core_log" );

		Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=support&controller=systemLogs' ), 'deleted' );
	}
	
	/**
	 * Delete a log
	 * 
	 * @return void
	 */
	protected function delete() : void
	{
		/* Load */
		try
		{
			$log = Log::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C324/2', 404, '' );
		}

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		/* Delete */
		$log->delete();
		
		/* Log and redirect */
		if ( $log->category )
		{
			Session::i()->log( 'acplog__log_delete', array( $log->category => FALSE, ( (string) DateTime::ts( $log->time ) ) => FALSE ) );
		}
		else
		{
			Session::i()->log( 'acplog__log_delete_uncategoried', array( ( (string) DateTime::ts( $log->time ) ) => FALSE ) );
		}
		Output::i()->redirect( Url::internal( 'app=core&module=support&controller=systemLogs' ), 'deleted' );
	}
	
	/**
	 * View File-based Logs list
	 *
	 * @return	void
	 */
	protected function fileLogs() : void
	{
		/* NO_WRITES check */
		if ( NO_WRITES )
		{
			Output::i()->error( 'no_writes', '1C324/8', 403, '' );
		}
		
		/* Get list of files */
		$dir = Log::fallbackDir();
		$source = array();
		if ( is_dir( $dir ) )
		{
			$directoryIterator = new DirectoryIterator( $dir );
			foreach ( $directoryIterator as $file )
			{
				if ( mb_substr( $file, 0, 1 ) !== '.' and $file != 'index.html' )
				{
					$source[] = array( 'time' => $file->getMTime(), 'file' => (string) $file );
				}
			}
		}
		
		/* Create table */
		$table = new Custom( $source, Url::internal( 'app=core&module=support&controller=systemLogs&do=fileLogs' ) );
		$table->langPrefix = 'log_';
		$table->parsers = array(
			'time'		=> function( $val )
			{
				return DateTime::ts( $val );
			}
		);
		$table->sortBy = $table->sortBy ?: 'time';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		$table->rowButtons = function( $row ) {
			return array(
				'view'		=> array(
					'title'	=> 'view',
					'icon'	=> 'search',
					'link'	=> Url::internal( 'app=core&module=support&controller=systemLogs&do=viewFile' )->setQueryString( 'file', $row['file'] )
				),
				'delete'	=> array(
					'title'	=> 'delete',
					'icon'	=> 'times-circle',
					'link'	=> Url::internal( 'app=core&module=support&controller=systemLogs&do=deleteFile' )->setQueryString( 'file', $row['file'] )->csrf(),
					'data'	=> array( 'delete' => '' )
				)
			);
		};
		
		/* Display */		
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=support&controller=systemLogs" ), Member::loggedIn()->language()->addToStack('r__system_logs') );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('file_logs') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('file_logs');
		Output::i()->output = Theme::i()->getTemplate( 'forms' )->blurb( Member::loggedIn()->language()->addToStack('log_view_file_logs_info', FALSE, array( 'sprintf' => $dir ) ) ) . $table;
	}
	
	/**
	 * View File-based Log
	 *
	 * @return	void
	 */
	protected function viewFile() : void
	{
		/* NO_WRITES check */
		if ( NO_WRITES )
		{
			Output::i()->error( 'no_writes', '1C324/3', 403, '' );
		}
		
		/* Try to find it */
		$file = Log::fallbackDir() . DIRECTORY_SEPARATOR . preg_replace( '/[^a-z_0-9\.]/i', '', Request::i()->file );
		if ( !is_file( $file ) )
		{
			Output::i()->error( 'node_error', '2C324/5', 404, '' );
		}
		
		/* Delete button */
		Output::i()->sidebar['actions']['delete'] = array(
			'icon'	=> 'times-circle',
			'link'	=> Url::internal( 'app=core&module=support&controller=systemLogs&do=deleteFile' )->setQueryString( 'file', Request::i()->file )->csrf(),
			'title'	=> 'delete',
			'data'	=> array( 'confirm' => '' )
		);
		
		/* Display */
		Output::i()->title	 = basename( $file );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=support&controller=systemLogs" ), Member::loggedIn()->language()->addToStack('r__system_logs') );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=support&controller=systemLogs&do=fileLogs" ), Member::loggedIn()->language()->addToStack('log_view_file_logs') );
		Output::i()->output = Theme::i()->getTemplate( 'system' )->systemLogFileView( file_get_contents( $file ) );
	}
	
	/**
	 * View File-based Log
	 *
	 * @return	void
	 */
	protected function deleteFile() : void
	{
		Session::i()->csrfCheck();
		
		/* NO_WRITES check */
		if ( NO_WRITES )
		{
			Output::i()->error( 'no_writes', '1C324/4', 403, '' );
		}
		
		/* Try to find it */
		$file = Log::fallbackDir() . DIRECTORY_SEPARATOR . preg_replace( '/[^a-z_0-9\.]/i', '', Request::i()->file );
		if ( !is_file( $file ) )
		{
			Output::i()->error( 'node_error', '2C324/6', 404, '' );
		}
		
		/* Delete it */
		if ( !@unlink( $file ) )
		{
			Output::i()->error( 'log_file_could_not_delete', '1C324/7', 403, '' );
		}
		
		/* Log and redirect */
		Session::i()->log( 'acplog__log_delete_file', array( basename( $file ) => FALSE ) );
		Output::i()->redirect( Url::internal( 'app=core&module=support&controller=systemLogs&do=fileLogs' ), 'deleted' );
	}
	
	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function logSettings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'diagnostic_log_settings' );
		
		$form = new Form;
		$form->add( new Interval( 'prune_log_system', Settings::i()->prune_log_system, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), function( $val ) {
			if( $val > 0 AND $val < 7 )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_interval_min_d', FALSE, array( 'pluralize' => array( 6 ) ) ) );
			}
		}, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_log_moderator' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__systemlog_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=support&controller=systemLogs' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('systemlogssettings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'systemlogssettings', $form, FALSE );
	}
}