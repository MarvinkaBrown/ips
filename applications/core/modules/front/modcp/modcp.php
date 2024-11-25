<?php
/**
 * @brief		Moderator Control Panel
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Oct 2013
 */

namespace IPS\core\modules\front\modcp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\DataLayer;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function method_exists;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Control Panel
 */
class modcp extends Controller
{
	/**
	 * @brief	Is this for displaying "content"? Affects if advertisements may be shown
	 */
	public bool $isContentPage = FALSE;

	/**
	 * Dispatcher looks for methods to match do param, which may exist in the extension files, so this method
	 * prevents dispatcher throwing a 2S106/1 and allows the extensions to use the values
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 * @return	void
	 */
	public function __call( string $method, array $args ) : void
	{
		if ( Settings::i()->core_datalayer_enabled )
		{
			DataLayer::i()->addContextProperty( 'community_area', Lang::load( Lang::defaultLanguage() )->addToStack('modcp') );
		}

		if ( isset( Request::i()->do ) )
		{
			$activeTab	= Request::i()->tab ?: 'overview';
			foreach ( Application::allExtensions( 'core', 'ModCp' ) as $key => $extension )
			{
				if( mb_strtolower( $extension->getTab() ) == mb_strtolower( $activeTab ) )
				{
					if ( method_exists( $extension, Request::i()->do ) )
					{
						$this->manage();
					}
				}
			}			
		}

		/* Still here? */
		Output::i()->error( 'page_not_found', '2C139/6', 404, '' );
	}
	
	/**
	 * Moderator Control Panel
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Check we're not a guest */
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission', '2S194/1', 403, '' );
		}

		/* Make sure we are a moderator */
		if ( Member::loggedIn()->modPermission() === FALSE )
		{
			Output::i()->error( 'no_module_permission', '2S194/2', 403, '' );
		}
		
		/* Set up the tabs */
		$activeTab	= Request::i()->tab ?: 'overview';
		$tabs		= array( 'reports' => array(), 'approval' => array() );
		$content	= '';
		$approvalQueueCount = 0;

		foreach ( Application::allExtensions( 'core', 'ModCp' ) as $key => $extension )
		{
			/* We use this to group the sidebar for the ModCP */
			$tabType = 'other';
			if ( $extension->manageType() )
			{
				$tabType = in_array( $extension->manageType(), array( 'content', 'members' ) ) ? $extension->manageType() : 'other';
			}

			$tab = $extension->getTab();

			if ( $tab )
			{
				$tabs[ $tabType ][ $tab ][] = $key;
			}

			/* Get the count for the approval queue from the extension */
			if( $tab == 'approval' )
			{
				$approvalQueueCount = $extension->getApprovalQueueCount( false );
			}
			
			if( mb_strtolower( (string) $extension->getTab() ) == mb_strtolower( (string) $activeTab ) )
			{
				$method = ( Request::i()->action and preg_match( '/^[a-zA-Z\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', Request::i()->action ) ) ? Request::i()->action : 'manage';

				if ( $method !== 'getTab' AND ( method_exists( $extension, $method ) or method_exists( $extension, '__call' ) ) )
				{
					$content = $extension->$method();
					if ( !$content )
					{
						$content = Output::i()->output;
					}
				}
			}
		}

		$tabs = array_filter( $tabs, 'count' );
		
		/* Got a page? */
		if ( !$content )
		{
			foreach ( $tabs as $tabType => $tabList )
			{
				foreach( $tabList as $k => $data )
				{
					Output::i()->redirect( Url::internal( "app=core&module=modcp&controller=modcp&tab={$k}", 'front', "modcp_{$k}" ) );
				}
			}
		}
		
		/* Display */
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/modcp.css' ) );
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_modcp.js', 'core' ) );
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $content;
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'modcp' )->template( $content, $tabs, $activeTab, $approvalQueueCount );
		}
	}
}