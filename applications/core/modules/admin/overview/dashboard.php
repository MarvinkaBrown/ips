<?php
/**
 * @brief		ACP Dashboard
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 July 2013
 */

namespace IPS\core\modules\admin\overview;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;
use function intval;
use function strpos;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Dashboard
 */
class dashboard extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Show the ACP dashboard
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Dispatcher::i()->checkAcpPermission( 'view_dashboard' );
		
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('admin_dashboard.js', 'core') );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/dashboard.css', 'core', 'admin' ) );

		/* Figure out which blocks we should show */
		$toShow	= $this->current( TRUE );

		/* Now grab dashboard extensions */
		$blocks	= array();
		$info	= array();
		foreach ( Application::allExtensions( 'core', 'Dashboard', TRUE, 'core' ) as $key => $extension )
		{
			if ( $extension->canView() )
			{
				$info[ $key ]	= array(
							'name'	=> Member::loggedIn()->language()->addToStack('block_' . $key ),
							'key'	=> $key,
							'app'	=> substr( $key, 0, strpos( $key, '_' ) )
				);
				
				foreach( $toShow as $row )
				{
					if( in_array( $key, $row ) )
					{
						$blocks[ $key ]	= $extension->getBlock();
						break;
					}
				}
			}
		}

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('dashboard');
		Output::i()->customHeader = Theme::i()->getTemplate( 'dashboard' )->dashboardHeader( $info, $blocks );
		Output::i()->output	= Theme::i()->getTemplate( 'dashboard' )->dashboard( $toShow, $blocks, $info );
	}

	/**
	 * Return a json-encoded array of the current blocks to show
	 *
	 * @param	bool	$return	Flag to indicate if the array should be returned instead of output
	 * @return	array|null
	 */
	public function current( bool $return=FALSE ) : ?array
	{
		if( Settings::i()->acp_dashboard_blocks )
		{
			$blocks = json_decode( Settings::i()->acp_dashboard_blocks, TRUE );
		}
		else
		{
			$blocks = array();
		}

		$toShow	= isset( $blocks[ Member::loggedIn()->member_id ] ) ? $blocks[ Member::loggedIn()->member_id ] : array();

		if( !$toShow OR !isset( $toShow['main'] ) OR !isset( $toShow['side'] ) )
		{
			$toShow	= array(
				'main'		=> array( 'core_Registrations', 'core_BackgroundQueue' ),
				'side'		=> array( 'core_AdminNotes', 'core_OnlineUsers' ),
				'collapsed'	=> array( 'core_BackgroundQueue' ),
			);

			$blocks[ Member::loggedIn()->member_id ]	= $toShow;

			Settings::i()->changeValues( array( 'acp_dashboard_blocks' => json_encode( $blocks ) ) );
		}
		/* Upon initial upgrade to 4.3 the key won't exist, so apply to bg queue by default */
		elseif( !array_key_exists( 'collapsed', $toShow ) )
		{
			$toShow['collapsed']	= array( 'core_BackgroundQueue' );
		}

		if( $return === TRUE )
		{
			return $toShow;
		}

		Output::i()->output		= json_encode( $toShow );
		return null;
	}

	/**
	 * Return an individual block's HTML
	 *
	 * @return	void
	 */
	public function getBlock() : void
	{
		$output		= '';

		/* Loop through the dashboard extensions in the specified application */
		foreach( Application::load( Request::i()->appKey )->extensions( 'core', 'Dashboard', 'core' ) as $key => $_extension )
		{
			if( Request::i()->appKey . '_' . $key == Request::i()->blockKey )
			{
				if( method_exists( $_extension, 'getBlock' ) )
				{
					$output	= $_extension->getBlock();
				}

				break;
			}
		}

		Output::i()->output	= $output;
	}

	/**
	 * Update our current block configuration/order
	 *
	 * @return	void
	 * @note	When submitted via AJAX, the array should be json-encoded
	 */
	public function update() : void
	{
		Session::i()->csrfCheck();
		
		if( Settings::i()->acp_dashboard_blocks )
		{
			$blocks = json_decode( Settings::i()->acp_dashboard_blocks, TRUE );
		}
		else
		{
			$blocks = array();
		}

		$saveBlocks = Request::i()->blocks;
		
		foreach( array( 'main', 'side', 'collapsed' ) as $saveKey )
		{
			if( !isset( $saveBlocks[ $saveKey ] ) )
			{
				$saveBlocks[ $saveKey ]	= array();
			}
		}
		
		$blocks[ Member::loggedIn()->member_id ] = $saveBlocks;

		Settings::i()->changeValues( array( 'acp_dashboard_blocks' => json_encode( $blocks ) ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->output = 1;
			return;
		}

		Output::i()->redirect( Url::internal( "app=core&module=overview&controller=dashboard" ), 'saved' );
	}

	/**
	 * Snooze the switch to invision link for a bit
	 *
	 * @return void
	 */
	public function switchSnooze() : void
	{
		$value = [ 'hits' => 1, 'lastClick' => time() ];
		if ( isset( Request::i()->cookie['acpLinkSnooze'] ) and $value = json_decode( Request::i()->cookie['acpLinkSnooze'], true ) )
		{
			$value['hits'] = intval( $value['hits'] ) + 1;
			$value['lastClick'] = time();
		}

		Request::i()->setCookie( 'acpLinkSnooze', json_encode( $value ), ( new DateTime )->add( new DateInterval( 'P2Y' ) ) );

		Output::i()->redirect( Url::internal( "app=core&module=overview&controller=dashboard" ), 'The Switch to Cloud link won\'t show for a while' );
	}
}