<?php
/**
 * @brief		Downloads Application Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		27 Sep 2013
 * @version		
 */
 
namespace IPS\downloads;

use IPS\Application as SystemApplication;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;
use function is_numeric;

/**
 * Downloads Application Class
 */
class Application extends SystemApplication
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init(): void
	{
		/* Handle RSS requests */
		if ( Request::i()->module == 'downloads' and Request::i()->controller == 'browse' and Request::i()->do == 'rss' )
		{
			$member = NULL;
			if( Request::i()->member AND Request::i()->key )
			{
				$member = Member::load( Request::i()->member );
				if( !Login::compareHashes( $member->getUniqueMemberHash(), (string) Request::i()->key ) )
				{
					$member = NULL;
				}
			}

			$this->sendDownloadsRss( $member ?? new Member );

			if( !Member::loggedIn()->group['g_view_board'] )
			{
				Output::i()->error( 'node_error', '2D220/1', 404, '' );
			}
		}

		static::outputCss();
	}

	/**
	 * Output CSS files
	 *
	 * @return void
	 */
	public static function outputCss() : void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'downloads.css', 'downloads', 'front' ) );
	}

	/**
	 * Send the latest file RSS feed for the indicated member
	 *
	 * @param Member $member		Member
	 * @return	void
	 */
	protected function sendDownloadsRss( Member $member ) : void
	{
		if( !Settings::i()->idm_rss )
		{
			Output::i()->error( 'rss_offline', '2D175/2', 403, 'rss_offline_admin' );
		}

		$document = Rss::newDocument( Url::internal( 'app=downloads&module=downloads&controller=browse', 'front', 'downloads' ), $member->language()->get('idm_rss_title'), $member->language()->get('idm_rss_title') );
		
		foreach (File::getItemsWithPermission( array(), NULL, 10, 'read', Filter::FILTER_AUTOMATIC, 0, $member ) as $file )
		{
			$content = $file->desc;
			Output::i()->parseFileObjectUrls( $content );
			$document->addItem( $file->name, $file->url(), $file->desc, DateTime::ts( $file->updated ), $file->id );
		}
		
		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'download';
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation(): array
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Downloads' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
	
	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters() : void
	{
		if ( isset( Request::i()->showfile ) AND is_numeric( Request::i()->showfile ) )
		{
			try
			{
				$file = File::loadAndCheckPerms( Request::i()->showfile );
				
				Output::i()->redirect( $file->url() );
			}
			catch( OutOfRangeException $e ) {}
		}

		if ( isset( Request::i()->module ) AND Request::i()->module == 'post' AND isset( Request::i()->controller ) AND Request::i()->controller == 'submit' )
		{
			Output::i()->redirect( Url::internal( "app=downloads&module=downloads&controller=submit", "front", "downloads_submit" ) );
		}
	}
	
	/**
	 * Get any settings that are uploads
	 *
	 * @return	array
	 */
	public function uploadSettings(): array
	{
		/* Apps can overload this */
		return array( 'idm_watermarkpath' );
	}

	/**
	 * Returns a list of all existing webhooks and their payload in this app.
	 *
	 * @return array
	 */
	public function getWebhooks(): array
	{
		return array_merge( parent::getWebhooks(), [ 'downloads_new_version' => File::class ] );
	}

}