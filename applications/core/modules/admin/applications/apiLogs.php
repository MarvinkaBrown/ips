<?php
/**
 * @brief		API Logs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		04 Dec 2015
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Key;
use IPS\Api\OAuthClient;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function defined;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_NODE;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * apiReference
 */
class apiLogs extends Dispatcher\Controller
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
		Dispatcher::i()->checkAcpPermission( 'api_logs' );
		parent::execute();
	}

	/**
	 * View Logs
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Init table */
		$table = new TableDb( 'core_api_logs', Url::internal('app=core&module=applications&controller=api&tab=apiLogs') );
		$table->langPrefix = 'api_log_';
		
		/* Columns */
		$table->include = array( 'date', 'endpoint', 'api_key', 'ip_address', 'response_code' );
		$table->parsers = array(
			'date'		=> function( $val )
			{
				return DateTime::ts( $val );
			},
			'endpoint'	=> function( $val, $row )
			{
				return Theme::i()->getTemplate('api')->apiKey( $row['method'] . ' ' . $val );
			},
			'api_key'	=> function( $val, $row )
			{
				$apiKey = NULL;
				$client = NULL;
				$member = NULL;
				if ( $row['api_key'] )
				{
					try
					{
						$apiKey = Key::load( $row['api_key'] );
					}
					catch ( OutOfRangeException $e ) { }
				}
				if ( $row['client_id'] )
				{
					try
					{
						$client = OAuthClient::load( $row['client_id'] );
					}
					catch ( OutOfRangeException $e ) { }
					
					if ( $row['member_id'] )
					{
						$member = Member::load( $row['member_id'] );
					}
				}
				
				return Theme::i()->getTemplate('api')->apiLogCredentials( $row, $apiKey, $client, $member );
			},
			'response_code'	=> function( $val )
			{
				return $val . ' ' . Output::$httpStatuses[ $val ];
			}
		);
		
		/* Default sort */
		$table->sortBy = $table->sortBy ?: 'date';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Filters */
		$table->filters = array(
			'api_log_success'	=> array( 'response_code LIKE ?', '2%' ),
			'api_log_fail'		=> array( 'response_code NOT LIKE ?', '2%' ),
		);
		
		/* Search */
		$endpoints = array( '' => 'any' );
		foreach ( Controller::getAllEndpoints() as $k => $data )
		{
			$endpoints[ $data['title'] ] = $data['title'];
		}
		$statuses = array( '' => 'any' );
		foreach ( Output::$httpStatuses as $code => $name )
		{
			$statuses[ $code ] = "{$code} {$name}";
		}
		$table->advancedSearch = array(
			'date'			=> SEARCH_DATE_RANGE,
			'endpoint'		=> array( SEARCH_SELECT, array( 'options' => $endpoints ), function( $val )
			{
				$exploded = explode( ' ', $val );
				$endpoint = array();
				foreach( explode( '/', $exploded[1] ) AS $piece )
				{
					if ( str_starts_with( $piece, '{' ) )
					{
						$endpoint[] = '([a-zA-Z0-9-_]+)';
					}
					else
					{
						$endpoint[] = $piece;
					}
				}
				
				return array( 'method=? AND endpoint REGEXP ?', $exploded[0], trim( implode( '/', $endpoint ), '/' ) . '$' );
			} ),
			'api_key'		=> array( SEARCH_NODE, array( 'class' => 'IPS\Api\Key' ) ),
			'ip_address'	=> SEARCH_CONTAINS_TEXT,
			'response_code'	=> array( SEARCH_SELECT, array( 'options' => $statuses ) ),
		);
		
		/* Buttons */
		$table->rowButtons = function( $row )
		{
			$return = array(
				'view'	=> array(
					'icon'	=> 'search',
					'title'	=> 'view',
					'link'	=> Url::internal('app=core&module=applications&controller=apiLogs&do=view')->setQueryString( 'id', $row['id'] ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $row['method'] . ' ' . $row['endpoint'] )
				)
			);
			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'api_logs_delete' ) )
			{
				$return['delete'] = array(
					'icon'	=> 'times-circle',
					'title'	=> 'delete',
					'link'	=> Url::internal('app=core&module=applications&controller=apiLogs&do=delete')->setQueryString( 'id', $row['id'] ),
					'data'	=> array( 'delete' => '' )
				);
			}
			return $return;
		};
		
		/* Display */
		if ( !isset( Request::i()->advancedSearchForm ) )
		{
			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'api_logs_settings' ) )
			{
				if ( Application::appIsEnabled('cloud') )
				{
					$blurb = Member::loggedIn()->language()->addTostack( 'api__log_blurb_change_cloud' );
				}
				else
				{
					$blurb = Member::loggedIn()->language()->addToStack( 'api__log_blurb_change', options: [ 'sprintf' => [ Settings::i()->api_log_prune ] ] );
				}

				Output::i()->output = \IPS\Theme::i()->getTemplate( 'forms' )->blurb( $blurb, FALSE, TRUE ) . $table;
			}
			else
			{
				Output::i()->output = Theme::i()->getTemplate( 'forms' )->blurb( Member::loggedIn()->language()->addToStack( 'api_log_blurb', FALSE, array( 'sprintf' => array( Settings::i()->api_log_prune ) ) ), TRUE, TRUE ) . $table;
			}
		}
		else
		{
			Output::i()->output = $table;
		}
	}
	
	/**
	 * View Log
	 *
	 * @return	void
	 */
	protected function view() : void
	{
		try
		{
			$log = Db::i()->select( '*', 'core_api_logs', array( 'id=?', Request::i()->id ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2C293/1', 404, '' );
		}
		
		Output::i()->output = Theme::i()->getTemplate('api')->viewLog( $log['request_data'], $log['response_output'] );
	}
	
	/**
	 * Delete Log
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'api_logs_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$log = Db::i()->select( '*', 'core_api_logs', array( 'id=?', Request::i()->id ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2C293/2', 404, '' );
		}
		
		Db::i()->delete( 'core_api_logs', array( 'id=?', $log['id'] ) );
		
		Session::i()->log( 'acplog__api_log_deleted', array( $log['id'] => FALSE ) );
		
		Output::i()->redirect( Url::internal('app=core&module=applications&controller=api&tab=apiLogs') );
	}
	
	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		if ( ! Application::appIsEnabled('cloud') )
		{
			Dispatcher::i()->checkAcpPermission( 'api_logs_settings' );

			$form = new Form;
			$form->add( new Interval( 'api_log_prune', Settings::i()->api_log_prune, FALSE, array( 'valueAs' => Interval::DAYS ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL ) );

			if ( $values = $form->values() )
			{
				$form->saveAsSettings();
				Session::i()->log( 'acplogs__api_log_settings' );
				Output::i()->redirect( Url::internal('app=core&module=applications&controller=api&tab=apiLogs') );
			}

			Output::i()->output = $form;
		}
		else
		{
			Output::i()->error( 'api__log_no_cloud', '2C293/3', 403 );
		}
	}	
}