<?php
/**
 * @brief		whosOnline Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Jul 2014
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Session\Front;
use IPS\Session\Store;
use IPS\Theme;
use IPS\Widget\PermissionCache;
use function array_slice;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * whosOnline Widget
 */
class whosOnline extends PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'whosOnline';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';



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

		if( $member->member_id AND !$member->isOnlineAnonymously() )
		{
			Output::i()->jsVars['member_id']				= $member->member_id;
			Output::i()->jsVars['member_url']				= (string) $member->url();
			Output::i()->jsVars['member_hovercardUrl']		= (string) $member->url()->setQueryString( 'do', 'hovercard' );
			Output::i()->jsVars['member_formattedName']	= str_replace( '"', '\\"', Group::load( $member->member_group_id )->formatName( $member->name ) );
		}

		/* We want to use the user's permissions array which will validate social groups, clubs and groups. But, we need to remove the individual member entry */
		$member = Member::loggedIn() ?: new Member;
		$permissions = $member->permissionArray();

		foreach( $permissions as $key => $entry )
		{
			if( mb_substr( $entry, 0, 1 ) === 'm' )
			{
				unset( $permissions[ $key ] );
				break;
			}
		}

		/* We sort to ensure the array is always in the same order */
		sort( $permissions );

		$theme = $member->skin ?: Theme::defaultTheme();
		$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( json_encode( $configuration ) . "_" . $member->language()->id . "_" . $theme . "_" . json_encode( $permissions ) . $orientation . '-' . (int) Member::loggedIn()->canAccessModule( Module::get( 'core', 'online', 'front' ) ) );
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
		
		/* Init */
		$members     = array();
		$anonymous   = 0;
		
		$users = Store::i()->getOnlineUsers( Store::ONLINE_MEMBERS, 'desc', NULL, NULL, TRUE );
		foreach( $users as $row )
		{
			switch ( $row['login_type'] )
			{
				/* Not-anonymous Member */
				case Front::LOGIN_TYPE_MEMBER:
					if ( $row['member_name'] )
					{
						$members[ $row['member_id'] ] = $row;
					}
					break;
					
				/* Anonymous member */
				case Front::LOGIN_TYPE_ANONYMOUS:
					$anonymous += 1;
					break;
			}
		}
		$memberCount = count( $members );

		/* Get an accurate guest count */
		$guests = Store::i()->getOnlineUsers( Store::ONLINE_GUESTS | Store::ONLINE_COUNT_ONLY, 'desc', NULL, NULL, TRUE );
		
		/* If it's on the sidebar (rather than at the bottom), we want to limit it to 60 so we don't take too much space */
		if ( $this->orientation === 'vertical' and count( $members ) >= 60 )
		{
			$members = array_slice( $members, 0, 60 );
		}

		/* Display */
		return $this->output( $members, $memberCount, $guests, $anonymous );
	}
}