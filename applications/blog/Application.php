<?php
/**
 * @brief		Blog Application Class
 * @author		<a href=''>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Blog
 * @since		3 Mar 2014
 * @version		
 */
 
namespace IPS\blog;

use IPS\Application as SystemApplication;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Lang;
use IPS\Login;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;
use function array_merge;

/**
 * Blog Application Class
 */
class Application extends SystemApplication
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init() : void
	{
		/* Handle RSS requests */
		if ( Request::i()->module == 'blogs' and Request::i()->controller == 'view' and Request::i()->do == 'rss' )
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

			$this->sendBlogRss( $member ?? new Member );

			if( !Member::loggedIn()->group['g_view_board'] )
			{
				Output::i()->error( 'node_error', '2B221/1', 404, '' );
			}
		}
	}

	/**
	 * Send the blog's RSS feed for the indicated member
	 *
	 * @param	Member		$member		Member
	 * @return	void
	 */
	protected function sendBlogRss( Member $member ) : void
	{
		try
		{
			$blog = Blog::load( Request::i()->id );

			if( !$blog->can( 'view', $member ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			/* We'll let the regular controller handle the error */
			return;
		}

		if( !Settings::i()->blog_allow_rss or !$blog->settings['allowrss'] )
		{
			Output::i()->error( 'blog_rss_offline', '2B201/5', 403, 'blog_rss_offline_admin' );
		}

		/* We have to use get() to ensure CDATA tags wrap the blog title properly */
		$title	= $blog->member_id ? $blog->name : $member->language()->get( "blogs_blog_{$blog->id}" );

		$document = Rss::newDocument( $blog->url(), $title, $blog->description );
	
		foreach (Entry::getItemsWithPermission( array( array( 'entry_blog_id=?', $blog->id ), array( 'entry_status!=?', 'draft' ) ), 'entry_date DESC', 25, 'read', Filter::FILTER_PUBLIC_ONLY, 0, $member, FALSE, FALSE, FALSE, FALSE, NULL, $blog ) as $entry )
		{
			/* @var Entry $entry */
			$content = $entry->content;
			Output::i()->parseFileObjectUrls( $content );

			$document->addItem( $entry->name, $entry->url(), $entry->content, DateTime::ts( $entry->date ), $entry->id );
		}
	
		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		Output::i()->sendOutput( $document->asXML(), 200, 'text/xml', array(), TRUE, parseFileObjects: FALSE );
	}

	/**
	 * Perform any additional installation needs
	 *
	 * @return void
	 */
	public function installOther() : void
	{
		/* Allow non guests to create and comment on Blogs */
		foreach( Group::groups( TRUE, FALSE ) as $group )
		{
			$group->g_blog_allowlocal = TRUE;
			$group->g_blog_allowcomment = TRUE;
			$group->save();
		}

		/* Create new default category */
		$category = new Category;
		$category->seo_name = 'general';
		$category->save();

		Lang::saveCustom( 'blog', "blog_category_{$category->id}", "General" );
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'file-text';
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
			'browseTabs'	=> array( array( 'key' => 'Blogs' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Returns a list of all existing webhooks and their payload in this app.
	 *
	 * @return array
	 */
	public function getWebhooks() : array
	{
		return array_merge(  [
			'blogBlog_create' => Blog::class
		], parent::getWebhooks());
	}

	/**
	 * Output CSS files
	 *
	 * @return void
	 */
	public static function outputCss() : void
	{
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'blog.css', 'blog', 'front' ) );
		}
	}
}