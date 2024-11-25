<?php
/**
 * @brief		activeUsers Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Nov 2013
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Dispatcher;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Session\Store;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget\StaticCache;
use function array_slice;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * activeUsers Widget
 */
class activeUsers extends StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'activeUsers';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
	


	/**
	 * @brief	Cache Expiration
	 * @note	We only let this cache be valid for up to 60 seconds
	 */
	public int $cacheExpiration = 60;

	/**
	 * @var string
	 */
	protected string $url = '';

	/**
	 * Constructor
	 *
	 * @param String $uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param array|string|null $access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation			Orientation (top, bottom, right, left)
	 * @param string $layout
	 * @return	void
	 */
	public function __construct(string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='table' )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );
		
		$member = Member::loggedIn() ?: new Member;

		/* We can't run the URL related logic if we have no dispatcher because this class could also be initialized by the CLI cron job */
		if( Dispatcher::hasInstance() )
		{
			$parts = parse_url( (string) Request::i()->url()->setPage() );
			
			if ( Settings::i()->htaccess_mod_rewrite )
			{
				$this->url = $parts['scheme'] . "://" . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . $parts['path'];
			}
			else
			{
				$this->url = $parts['scheme'] . "://" . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . $parts['path'] . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
			}

			if( $member->member_id AND !$member->isOnlineAnonymously() )
			{
				Output::i()->jsVars['member_id']				= $member->member_id;
				Output::i()->jsVars['member_url']				= (string) $member->url();
				Output::i()->jsVars['member_hovercardUrl']		= (string) $member->url()->setQueryString( 'do', 'hovercard' );
				Output::i()->jsVars['member_formattedName']	= str_replace( '"', '\\"', Group::load( $member->member_group_id )->formatName( $member->name ) );
			}

			$theme = $member->skin ?: Theme::defaultTheme();
			$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( $this->url . '_' . json_encode( $configuration ) . "_" . $member->language()->id . "_" . $theme . "_" . $orientation . '-' . (int) Member::loggedIn()->canAccessModule( Module::get( 'core', 'members', 'front' ) ) );
		}
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* Do we have permission? */
		if ( !Member::loggedIn()->canAccessModule( Module::get( 'core', 'online', 'front' ) ) )
		{
			return "";
		}
		
		$members = Store::i()->getOnlineMembersByLocation( Dispatcher::i()->application->directory, Dispatcher::i()->module->key, Dispatcher::i()->controller, Request::i()->id, $this->url );

		$memberCount = count( $members );
		
		/* If it's on the sidebar (rather than at the bottom), we want to limit it to 60 so we don't take too much space */
		if ( $this->orientation === 'vertical' and count( $members ) >= 60 )
		{
			$members = array_slice( $members, 0, 60 );
		}

		/* Display */
		return $this->output( $members, $memberCount );
	}
}