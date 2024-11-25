<?php

/**
 * @brief		Categories
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		27 Sep 2013
 */

namespace IPS\downloads\modules\admin\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\downloads\Category;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Task;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Categories
 */
class categories extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\downloads\Category';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'categories_manage' );
		parent::execute();
	}
	
	/**
	 * Recalculate Downloads
	 *
	 * @return	void
	 */
	protected function recountDownloads() : void
	{
		Dispatcher::i()->checkAcpPermission( 'categories_recount_downloads' );
		Session::i()->csrfCheck();
	
		try
		{
			$category = Category::load( Request::i()->id );
			
			Db::i()->update( 'downloads_files', array( 'file_downloads' => Db::i()->select( 'COUNT(*)', 'downloads_downloads', array( 'dfid=file_id' ) ) ), array( 'file_cat=?', $category->id ) );
			Session::i()->log( 'acplogs__downloads_recount_downloads', array( $category->_title => FALSE ) );
		
			Output::i()->redirect( Url::internal( "app=downloads&module=downloads&controller=categories&do=form&id=" . Request::i()->id ), 'clog_recount_done' );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2D180/1', 404, '' );
		}
	}

	/**
	 * Show the add/edit form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		parent::form();

		if ( Request::i()->id )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('edit_category')  . ': ' . Output::i()->title;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('add_category');
		}
	}

	/**
	 * Rebuild the Downloads Files Topics
	 *
	 * @return void
	 */
	public function rebuildTopicContent() : void
	{
		Session::i()->csrfCheck();
		
		$class = $this->nodeClass;
		Task::queue( 'core', 'ResyncTopicContent', array( 'class' => $class, 'categoryId' => Request::i()->id ), 3, array( 'categoryId' ) );

		/* @var Model $class */
		Session::i()->log( 'acplogs__downloads_resync_topics', array( $class::$titleLangPrefix . Request::i()->id => TRUE ) );
		Output::i()->redirect( Url::internal( 'app=downloads&module=downloads&controller=categories&do=form&id=' . Request::i()->id ), Member::loggedIn()->language()->addToStack('rebuilding_stuff', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'category_forums_integration' ) ) ) ) );
	}
}