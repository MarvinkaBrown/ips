<?php
/**
 * @brief		View Blog Entry Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		03 Mar 2014
 */

namespace IPS\blog\modules\front\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\blog\Blog;
use IPS\blog\Entry;
use IPS\blog\Entry\Category;
use IPS\core\FrontNavigation;
use IPS\core\Rss\Import;
use IPS\Db;
use IPS\File;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\CoverPhoto\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Url;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Content;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Member\Club;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use IPS\Xml\Atom;
use IPS\Xml\Rss;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View Blog Controller
 */
class view extends Controller
{
	
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\blog\Blog';

	/**
	 * Blog object
	 */
	protected ?Blog $blog = NULL;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Load blog and check permissions */
		try
		{
			$this->blog	= Blog::loadAndCheckPerms( Request::i()->id, 'read' );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2B201/1', 404, '' );
		}

		if ( $this->blog->cover_photo )
		{
			Output::i()->metaTags['og:image'] = File::get( $this->_coverPhotoStorageExtension(), $this->blog->cover_photo )->url;
		}
		
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$this->blog->clubCheckRules();
		
		/* Build table */
		$tableUrl = $this->blog->url();
		$where = array();
		if ( !in_array( $this->blog->id, array_keys( Blog::loadByOwner( Member::loggedIn() ) ) ) AND !Entry::canViewHiddenItems( Member::loggedIn(), $this->blog ) )
		{
			if ( !( $club = $this->blog->club() AND in_array( $club->memberStatus( Member::loggedIn() ), array( Club::STATUS_LEADER, Club::STATUS_MODERATOR ) ) ) )
			{
				$where[] = array( "entry_status!='draft'" );
			}
		}

		/* Are we limiting by category? */
		try
		{
			$category = Category::load( (int) Request::i()->cat );
			$tableUrl = $tableUrl->setQueryString( [ 'cat' => $category->id ] );
		}
		catch( OutOfRangeException )
		{
			$category = NULL;
		}

		if( $category )
		{
			$where[] = array( "entry_category_id=?", $category->id );
		}
		
		$table = new Content( 'IPS\blog\Entry', $tableUrl, $where, $this->blog );
		
		$table->tableTemplate = array( Theme::i()->getTemplate( 'view' ), 'blogTable' );

		$viewMode = Member::loggedIn()->getLayoutValue( 'blog_view' );
		$template = ( $viewMode == 'grid' ) ? 'rowsGrid' : 'rows';
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'view' ), $template );
		$table->title = Member::loggedIn()->language()->addToStack('entries_in_this_blog');
        $table->sortBy = Request::i()->sortby ?: 'date';

		/* Update views */
		Db::i()->update(
			'blog_blogs',
			"`blog_num_views`=`blog_num_views`+1",
			array( "blog_id=?", $this->blog->id ),
			array(),
			NULL,
			Db::LOW_PRIORITY
		);

		if ( ! Request::i()->isAjax() )
		{
			$this->blog->updateViews();
		}

		/* Online User Location */
		if( !$this->blog->social_group )
		{
			Session::i()->setLocation( $this->blog->url(), array(), 'loc_blog_viewing_blog', array( "blogs_blog_{$this->blog->id}" => TRUE ) );
		}

		if( Settings::i()->blog_allow_rss and $this->blog->settings['allowrss'] )
		{
			Output::i()->rssFeeds['blog_rss_title'] = \IPS\Http\Url::internal( "app=blog&module=blogs&controller=view&id={$this->blog->_id}", 'front', 'blogs_rss', array( $this->blog->seo_name ) );

			if ( Member::loggedIn()->member_id )
			{
				$key = Member::loggedIn()->getUniqueMemberHash();

				Output::i()->rssFeeds['blog_rss_title'] = Output::i()->rssFeeds['blog_rss_title']->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
			}
		}

		/* Add JSON-ld */
		Output::i()->jsonLd['blog']	= array(
			'@context'		=> "https://schema.org",
			'@type'			=> "Blog",
			'url'			=> (string) $this->blog->url(),
			'name'			=> $this->blog->_title,
			'description'	=> $this->blog->member_id ? $this->blog->desc : Member::loggedIn()->language()->addToStack( Blog::$titleLangPrefix . $this->blog->_id . Blog::$descriptionLangSuffix, TRUE, array( 'striptags' => TRUE, 'escape' => TRUE ) ),
			'commentCount'	=> $this->blog->_comments,
			'interactionStatistic'	=> array(
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/ViewAction",
					'userInteractionCount'	=> $this->blog->num_views
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/FollowAction",
					'userInteractionCount'	=> Entry::containerFollowerCount( $this->blog )
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/CommentAction",
					'userInteractionCount'	=> $this->blog->_comments
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/WriteAction",
					'userInteractionCount'	=> $this->blog->_items
				)
			)
		);

		if( $this->blog->coverPhoto()->file )
		{
			Output::i()->jsonLd['blog']['image'] = (string) $this->blog->coverPhoto()->file->url;
		}

		if( $this->blog->member_id )
		{
			Output::i()->jsonLd['blog']['author'] = array(
				'@type'		=> 'Person',
				'name'		=> Member::load( $this->blog->member_id )->name,
				'url'		=> (string) Member::load( $this->blog->member_id )->url(),
				'image'		=> Member::load( $this->blog->member_id )->get_photo( TRUE, TRUE )
			);
		}

		if( Settings::i()->blog_enable_sidebar and $this->blog->sidebar )
		{
			Output::i()->sidebar['contextual'] = Theme::i()->getTemplate('view')->blogSidebar( $this->blog->sidebar );
		}

		Output::i()->breadcrumb = array();
		if ( $club = $this->blog->club() )
		{
			FrontNavigation::$clubTabActive = TRUE;
			Output::i()->breadcrumb = array();
			Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
			Output::i()->breadcrumb[] = array( $club->url(), $club->name );

		}
		else
		{
			Output::i()->breadcrumb['module'] = array( \IPS\Http\Url::internal( 'app=blog', 'front', 'blogs' ), Member::loggedIn()->language()->addToStack( '__app_blog' ) );
		}


		try
		{
		    	foreach( $this->blog->category()->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $this->blog->category()->url(), $this->blog->category()->_title );
		} 
		catch (OutOfRangeException ) {}
		
		Output::i()->breadcrumb[] = array( $this->blog->url(), $this->blog->_title );

		/* Categories */
		$categories = Category::roots( NULL, NULL, array( 'entry_category_blog_id=?', $this->blog->id ) );

		/* Set default search option */
		Output::i()->defaultSearchOption = array( 'blog_entry', 'blog_entry_pl' );

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_browse.js', 'blog', 'front' ) );
		Output::i()->output	= Theme::i()->getTemplate( 'view' )->view( $this->blog, (string) $table, $category );
		Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_blogs' ) ] = array( 'type' => 'blog_entry', 'nodes' => $this->blog->_id );
	}
	
	/**
	 * Edit blog
	 *
	 * @return	void
	 */
	protected function editBlog() : void
	{
		if( !$this->blog->canEdit() OR $this->blog->groupblog_ids or $this->blog->club_id )
		{
			Output::i()->error( 'no_module_permission', '2B201/2', 403, '' );
		}
	
		Session::i()->csrfCheck();
	
		/* Build form */
		$form = new Form( 'form', 'save', $this->blog->url()->setQueryString( array( 'do' => 'editBlog' ) )->csrf() );
		$form->class .= 'ipsForm--vertical ipsForm--edit-blog';
	
		$this->blog->form( $form, TRUE );
	
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if( !$values['blog_name'] )
			{
				$form->elements['']['blog_name']->error	= Member::loggedIn()->language()->addToStack('form_required');
	
				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
				return;
			}
	
			$this->blog->saveForm( $this->blog->formatFormValues( $values ) );
				
			Output::i()->redirect( $this->blog->url() );
		}
	
		/* Display form */
		Output::i()->title = $this->blog->_title;
		Output::i()->breadcrumb[] = array( $this->blog->url(), $this->blog->_title );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_view.js', 'blog', 'front' ) );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Delete Blog
	 *
	 * @return	void
	 */
	protected function deleteBlog() : void
	{
		Session::i()->csrfCheck();
		
		if( !$this->blog->canDelete() or $this->blog->club_id )
		{
			Output::i()->error( 'no_module_permission', '2B201/3', 403, '' );
		}

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		$this->blog->disabled = TRUE;
		$this->blog->save();

		/* Log */
		Session::i()->modLog( 'modlog__action_delete_blog', array( $this->blog->name => FALSE ) );

		Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => 'IPS\blog\Blog', 'id' => $this->blog->id, 'deleteWhenDone' => TRUE ) );
		Output::i()->redirect( \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=browse', 'front', 'blogs' ) );
	}
	
	/**
	 * Pin/Unpin Blog
	 *
	 * @return	void
	 */
	protected function changePin() : void
	{
		Session::i()->csrfCheck();
		
		/* Do we have permission */
		if ( ( $this->blog->pinned and !Member::loggedIn()->modPermission('can_unpin_content') ) or ( !$this->blog->pinned and !Member::loggedIn()->modPermission('can_pin_content') ) or $this->blog->club_id )
		{
			Output::i()->error( 'no_module_permission', '2B201/4', 403, '' );
		}
		
		$this->blog->pinned = !$this->blog->pinned;
		$this->blog->save();
		
		/* Respond or redirect */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( $this->blog->url() );
		}
	}

	/**
	 * RSS imports
	 *
	 * @return	void
	 */
	protected function rssImport() : void
	{
		if( !Settings::i()->blog_allow_rssimport )
		{
			Output::i()->error( 'rss_import_disabled', '2B201/7', 403, '' );
		}
		
		if( !$this->blog->canEdit() )
		{
			Output::i()->error( 'no_module_permission', '2B201/6', 403, '' );
		}
		
		/* Check for existing feed */
		try
		{
			$existing = Db::i()->select( '*', 'core_rss_import', array( 'rss_import_class=? AND rss_import_node_id=?', 'IPS\\blog\\Entry', $this->blog->id ) )->first();
			$feed = Import::constructFromData( $existing );
		}
		catch ( UnderflowException )
		{
			$feed = new Import;
			$feed->class = 'IPS\\blog\\Entry';
		}

		$form = new Form;

		$form->add( new YesNo( 'blog_enable_rss_import', (bool)$feed->url, FALSE, array( 'togglesOn' => array( 'blog_rss_import_url', 'blog_rss_import_auth_user', 'blog_rss_import_auth_pass', 'blog_rss_import_show_link', 'blog_rss_import_tags' ) ) ) );

		$form->add( new Url( 'blog_rss_import_url', $feed->url, TRUE, array(), NULL, NULL, NULL, 'blog_rss_import_url' ) );
		$form->add( new Text( 'blog_rss_import_auth_user', $feed->auth_user ?: null, FALSE, array(), NULL, NULL, NULL, 'blog_rss_import_auth_user' ) );
		$form->add( new Text( 'blog_rss_import_auth_pass', $feed->auth_pass ?: null, FALSE, array(), NULL, NULL, NULL, 'blog_rss_import_auth_pass' ) );
		$form->add( new Text( 'blog_rss_import_show_link', $feed->showlink ?: Member::loggedIn()->language()->addToStack('blog_rss_import_show_link_default' ), FALSE, array(), NULL, NULL, NULL, 'blog_rss_import_show_link' ) );

		$options = array(
			'autocomplete' => array(
				'unique' => TRUE,
				'minimized' => FALSE,
				'source' => Entry::definedTags(),
				'freeChoice' => false
			)
		);

		if ( Settings::i()->tags_force_lower )
		{
			$options['autocomplete']['forceLower'] = TRUE;
		}
		if ( Settings::i()->tags_min )
		{
			$options['autocomplete']['minItems'] = Settings::i()->tags_min;
		}
		if ( Settings::i()->tags_max )
		{
			$options['autocomplete']['maxItems'] = Settings::i()->tags_max;
		}

		$options['autocomplete']['prefix'] = Entry::canPrefix( NULL, $this->blog );
		$options['autocomplete']['disallowedCharacters'] = array( '#' ); // @todo Pending \IPS\Http\Url rework, hashes cannot be used in URLs

		$form->add( new Text( 'blog_rss_import_tags', $feed->tags ? json_decode( $feed->tags, TRUE ) : array(), Settings::i()->tags_min and Settings::i()->tags_min_req, $options, NULL, NULL, NULL, 'blog_rss_import_tags' ) );
		
		if ( $values = $form->values() )
		{
			if( $values['blog_enable_rss_import'] )
			{
				try
				{
					$request = $values['blog_rss_import_url']->request();

					if ( $values['blog_rss_import_auth_user'] or $values['blog_rss_import_auth_pass'] )
					{
						$request = $request->login( $values['blog_rss_import_auth_user'], $values['blog_rss_import_auth_pass'] );
					}

					$response = $request->get();

					if ( $response->httpResponseCode == 401 )
					{
						$form->error = Member::loggedIn()->language()->addToStack( 'rss_import_auth' );
					}

					$response = $response->decodeXml();
					
					if ( !( $response instanceof Rss ) and !( $response instanceof Atom ) )
					{
						$form->error = Member::loggedIn()->language()->addToStack( 'rss_import_invalid' );
					}

					if( !$form->error )
					{
						$feed->node_id = $this->blog->id;
						$feed->url = $values['blog_rss_import_url'];
						$feed->showlink = $values['blog_rss_import_show_link'];
						$feed->auth_user = $values['blog_rss_import_auth_user'];
						$feed->auth_pass = $values['blog_rss_import_auth_pass'];
						$feed->member = $this->blog->owner() ? $this->blog->owner()->member_id : Member::loggedIn()->member_id;
						$feed->settings = json_encode( array( 'tags' => $values['blog_rss_import_tags'] ) );
						$feed->save();
						
						try
						{
							$feed->run();
						}
						catch ( Exception ) { }
						
						/* Redirect */
						Output::i()->redirect( $this->blog->url() );
					}

				}
				catch ( \IPS\Http\Request\Exception )
				{
					$form->error = Member::loggedIn()->language()->addToStack( 'form_url_bad' );
				}
				catch ( Exception )
				{
					$form->error = Member::loggedIn()->language()->addToStack( 'rss_import_invalid' );
				}
			}
			else
			{
				Db::i()->delete( 'core_rss_import', array( 'rss_import_class=? AND rss_import_node_id=?', 'IPS\\blog\\Entry', $this->blog->id ) );

				/* Redirect */
				Output::i()->redirect( $this->blog->url() );
			}
		}
				
		/* Display */
		Output::i()->output = $form->error ? $form : Theme::i()->getTemplate( 'view', 'blog', 'front' )->rssImport( $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) );
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	protected function _coverPhotoStorageExtension(): string
	{
		return 'blog_Blogs';
	}
	
	/**
	 * Set Cover Photo
	 *
	 * @param	CoverPhoto	$photo	New Photo
	 * @return	void
	 */
	protected function _coverPhotoSet( CoverPhoto $photo ) : void
	{
		$this->blog->cover_photo = (string) $photo->file;
		$this->blog->cover_photo_offset = $photo->offset;
		$this->blog->save();
	}
	
	/**
	 * Get Cover Photo
	 *
	 * @return	CoverPhoto
	 */
	protected function _coverPhotoGet(): CoverPhoto
	{
		return $this->blog->coverPhoto();
	}
	
	/**
	 * Return categories as JSON
	 *
	 * @return	void
	 */
	protected function categoriesJson() : void
	{
		$cats = array();
		foreach( Category::roots( NULL, NULL, array( 'entry_category_blog_id=?', $this->blog->id ) ) as $meow )
		{
			$cats[] = array(
				'id'   => $meow->id,
				'name' => $meow->name
			);
		}
			
		Output::i()->json( array( 'categories' => $cats ) );
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manageCategories() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_view.js', 'blog', 'front' ) );

		if( !$this->blog->canEdit()  )
		{
			Output::i()->error( 'no_module_permission', '2B201/A', 403, '' );
		}

		$form = new Form;
		$form->addHtml( Theme::i()->getTemplate( 'view', 'blog', 'front' )->manageCategories( $this->blog ) );
		$form->hiddenValues['submitted'] = 1;

		if( $values = $form->values() )
		{
			Output::i()->redirect( $this->blog->url() );
		}

		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('blog_manage_entry_categories');
		Output::i()->output = $form->error ? $form : Theme::i()->getTemplate( 'view', 'blog', 'front' )->rssImport( $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) );
	}

	/**
	 * Edit a category name
	 *
	 * @return void
	 */
	protected function editCategoryName(): void
	{
		Session::i()->csrfCheck();

		if( !$this->blog->canEdit() )
		{
			Output::i()->error( 'no_module_permission', '2B201/C', 403, '' );
		}
		
		if( ! Request::i()->name )
		{
			Output::i()->error( 'blog_error_missing_name', '1B201/B', 403, '' );
		}
			
		/* New category? */
		if ( Request::i()->cat === 'new' )
		{
			$newCategory = new Category;
			$newCategory->name = Request::i()->name;
			$newCategory->seo_name = Friendly::seoTitle( Request::i()->name );

			$newCategory->blog_id = $this->blog->id;
			$newCategory->save();
		}
		else
		{
			try
			{
				$category = Category::load( Request::i()->cat );
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'blog_error_not_find_category', '2B201/D', 403, '' );
			}
	
			if( $category->blog_id !== $this->blog->id )
			{
				Output::i()->error( 'blog_error_not_find_category', '2B201/E', 403, '' );
			}
			
			$category->name = Request::i()->name;
			$category->save();
		}
		
		Output::i()->json( 'OK' );
	}
	
	/**
	 * Delete Category
	 *
	 * @return	void
	 */
	protected function deleteCategory() : void
	{
		Session::i()->csrfCheck();

		if( !$this->blog->canEdit()  )
		{
			Output::i()->error( 'no_module_permission', '2B201/8', 403, '' );
		}

		try
		{
			$category = Category::load( Request::i()->cat );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->redirect( $this->blog->url() );
		}

		if( $category->blog_id !== $this->blog->id )
		{
			Output::i()->error( 'no_module_permission', '2B201/9', 403, '' );
		}

		/* Update Entries */
		Db::i()->update( 'blog_entries', array( 'entry_category_id' => NULL ), array( 'entry_category_id=?', $category->id ) );

		$category->delete();

		/* Redirect */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( $this->blog->url(), 'deleted' );
		}
	}

	/**
	 * Reorder blog entry categories
	 *
	 * @return	void
	 */
	public function categoriesReorder() : void
	{
		Session::i()->csrfCheck();

		if( !$this->blog->canEdit() )
		{
			Output::i()->error( 'no_module_permission', '2B201/F', 403, '' );
		}

		/* Set order */
		$position = 1;

		if( !Request::i()->isAjax() )
		{
			Request::i()->ajax_order = explode( ',', Request::i()->ajax_order );
		}

		foreach( Request::i()->ajax_order as $category )
		{
			$node = Category::load( $category );

			/* No funny business trying to reorder another blog's categories */
			if( $node->blog_id !== $this->blog->id )
			{
				continue;
			}

			$node->position = $position;
			$node->save();

			$position++;
		}

		if( Request::i()->isAjax() )
		{
			Output::i()->json( 'ok' );
		}
		else
		{
			Output::i()->redirect( $this->blog->url(), 'saved' );
		}
	}
}