<?php
/**
 * @brief		Member History Table
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		7 Dec 2016
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme;
use Throwable;
use function defined;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_QUERY_TEXT;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member History Model
 */
class History extends \IPS\Helpers\Table\Db
{
	/**
	 * @brief	Parser extensions
	 */
	static ?array $extensions = NULL;

	/**
	 * Constructor
	 *
	 * @param	Url	$url			The URL the table will be displayed on
	 * @param	mixed			$where			WHERE clause
	 * @param bool $showIp			If the IP address column should be included
	 * @param bool $showMember		If the customer column should show
	 * @param bool $showApp		If the app column should show
	 * @param bool $showType		If the type column should show
	 * @return	void
	 */
	public function __construct( Url $url, mixed $where, bool $showIp=TRUE, bool $showMember=FALSE, bool $showApp=TRUE, bool $showType=FALSE )
	{
		parent::__construct( 'core_member_history', $url, $where );

		$this->include = array();
		if( static::$extensions === NULL )
		{
			$apps = Application::appsWithExtension( 'core', 'MemberHistory' );

			foreach( $apps as $application )
			{
				$result = $application->extensions( 'core', 'MemberHistory' );

				/* Since we're parsing, we only want to have one extension */
				static::$extensions[ $application->directory ] = array_pop( $result );
			}
		}

		if( $showType )
		{
			$this->include[] = 'log_type';
		}

		/* This is here specifically so that log_type is always shown first, if required */
		$this->include = array_merge( $this->include, array( 'log_date', 'log_data' ) );

		if ( $showIp )
		{
			$this->include[] = 'log_ip_address';
		}
		if ( $showMember )
		{
			$this->include[] = 'log_member';
		}

		$options	= array();
		$extensions	= static::$extensions;

		foreach( $extensions as $extension )
		{
			foreach( $extension->getTypes() as $type )
			{
				if ( $type === 'oauth' and Db::i()->select( 'COUNT(*)', 'core_oauth_clients', array( Db::i()->findInSet( 'oauth_grant_types', array( 'authorization_code', 'implicit', 'password' ) ) ) )->first() === 1 )
				{
					foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_oauth_clients', array( Db::i()->findInSet( 'oauth_grant_types', array( 'authorization_code', 'implicit', 'password' ) ) ) ), 'IPS\Api\OAuthClient' ) as $client )
					{
						$options[ $type ] = $client->_title;
						continue 2;
					}
				}

				$options[ $type ] = 'log_type_title_' . $type;
			}
		}
		
		$this->advancedSearch = array(
			'log_ip_address'		=> SEARCH_QUERY_TEXT,
			'log_date'				=> SEARCH_DATE_RANGE,
			'log_type'				=> array( SEARCH_SELECT, array( 'options' => $options, 'multiple' => TRUE ) )
		);

		$this->sortBy = $this->sortBy ?: 'log_date';
		$this->noSort = array( 'log_type', 'log_ip_address', 'log_data' );
		$this->rowClasses = array( 'log_data' => array( 'ipsTable_wrap' ) );
		$this->parsers = array(
			'log_type'	=> function( $val, $row ) use ( $extensions )
			{
				try
				{
					return $extensions[ $row['log_app'] ]->parseLogType( $val, $row );
				}
				catch( Throwable $e )
				{
					Log::log( $e, 'member_history' );
				}

				return $val;
			},
			'log_date'	=> function( $val )
			{
				return DateTime::ts( $val );
			},
			'log_ip_address'	=> function( $val )
			{
				return "<a href='" . Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
			},
			'log_member'=> function( $val, $row ) use ( $extensions )
			{
				try
				{
					return $extensions[ $row['log_app'] ]->parseLogMember( $val, $row );
				}
				catch( Throwable $e )
				{
					Log::log( $e, 'member_history' );
				}

				$member = Member::load( $val );
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
			},
			'log_data'	=> function( $val, $row ) use ( $extensions )
			{
				try
				{
					return $extensions[ $row['log_app'] ]->parseLogData( $val, $row );
				}
				catch( Throwable $e )
				{
					Log::log( $e, 'member_history' );
					return $val; # Return the value so the admin may have some clue as to what the log entry was for
				}

				return '';
			}
		);
	}
}