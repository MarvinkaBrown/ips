<?php
/**
 * @brief		My followed content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Apr 2014
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\core\Followed\Table;
use IPS\Dispatcher\Controller;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * My followed content
 */
class followed extends Controller
{
	/**
	 * My followed content
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Guests can't follow things */
		if( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C266/1', 403, '' );
		}

		/* Get the different types */
		$types			= array();
		
		foreach ( Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			if( IPS::classUsesTrait( $class, 'IPS\Content\Followable' ) )
			{
				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class;

				if ( isset( $class::$containerNodeClass ) )
				{
					$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$containerNodeClass, 4 ) ) ) ] = $class::$containerNodeClass;
				}
				
				if ( isset( $class::$containerFollowClasses ) )
				{
					foreach( $class::$containerFollowClasses as $followClass )
					{
						$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $followClass, 4 ) ) ) ] = $followClass;
					}
				}
			}
		}
		
		/* Don't forget Members - add this on to the end so it never defaults UNLESS we only have apps that do not have followable content */
		$types['core'] = array( 'core_member' => "\IPS\Member", 'core_tag' => "IPS\Content\Tag" );

		/* What type are we looking at? */
		$currentAppModule = NULL;
		$currentType = NULL;
		if ( isset( Request::i()->type ) )
		{
			foreach ( $types as $appModule => $_types )
			{
				if ( array_key_exists( Request::i()->type, $_types ) )
				{
					$currentAppModule = $appModule;
					$currentType = Request::i()->type;
					break;
				}
			}
		}
		
		if ( $currentType === NULL )
		{
			foreach ( $types as $appModule => $_types )
			{
				foreach ( $_types as $key => $class )
				{
					$currentAppModule = $appModule;
					$currentType = $key;
					break 2;
				}
			}
		}
		
		$currentClass = $types[ $currentAppModule ][ $currentType ];

		$output = new Table( $currentClass, explode( '_', $currentType ) );
		
		/* If we've clicked from the tab section */
		if ( Request::i()->isAjax() && Request::i()->change_section )
		{
			Output::i()->output = Theme::i()->getTemplate( 'system' )->followedContentSection( $types, $currentAppModule, $currentType, $output );
		}
		else
		{

			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/profiles.css' ) );
			Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_system.js', 'core' ) );
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu_followed_content');
			Output::i()->sidebar['enabled'] = FALSE;
			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('menu_followed_content') );
			Output::i()->output = Theme::i()->getTemplate( 'system' )->followedContent( $types, $currentAppModule, $currentType, $output );
		}
	}
}