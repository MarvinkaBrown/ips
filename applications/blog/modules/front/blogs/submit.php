<?php
/**
 * @brief		Submit
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		10 Mar 2014
 */

namespace IPS\blog\modules\front\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog;
use IPS\blog\Entry;
use IPS\core\FrontNavigation;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Submit
 */
class submit extends Controller
{
	/**
	 * @brief This blog
	 */
	protected ?Blog $blog = NULL;
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( isset( Request::i()->id ) )
		{
			try
			{
				$this->blog = Blog::load( Request::i()->id );
			}
			catch( OutOfRangeException ) {}
			Entry::canCreate( Member::loggedIn(), $this->blog, TRUE );
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
		Output::i()->title	= Member::loggedIn()->language()->addToStack( 'submit_entry' );
		
		/* Load Blog */
		try
		{
			/* Can we add to this Blog? We load the blog in execute, so emulate an OutOfRangeException here if we don't actually have one. */
			if ( $this->blog === NULL )
			{
				throw new OutOfRangeException;
			}
			
			Entry::canCreate( Member::loggedIn(), $this->blog, TRUE );
			
			$form = Entry::create( $this->blog );
			$formTemplate = $form->customTemplate( array( Theme::i()->getTemplate( 'submit', 'blog' ), 'submitFormTemplate' ) );
			
			if ( $club = $this->blog->club() )
			{
				FrontNavigation::$clubTabActive = TRUE;
				Output::i()->breadcrumb = array();
				Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
				Output::i()->breadcrumb[] = array( $club->url(), $club->name );
				
				if ( Settings::i()->clubs_header == 'sidebar' )
				{
					Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $this->blog, 'sidebar' );
				}
			}
            Output::i()->breadcrumb[] = array( $this->blog->url(), $this->blog->_title );

			if( !$this->blog->social_group )
			{
				Session::i()->setLocation( $this->blog->url(), array(), 'loc_blog_adding_entry', array( $this->blog->_title => FALSE ) );
			}

			Output::i()->output	= Theme::i()->getTemplate( 'submit' )->submit( $formTemplate, $this->blog );
		}
		catch ( OutOfRangeException )
		{
			$form = new Form( 'select_blog', 'continue' );
			$form->class = 'ipsForm--vertical ipsForm--manage-blog ipsForm--noLabels';
			$form->add( new Node( 'blog_select', NULL, TRUE, array(
					'url'					=> Url::internal( 'app=blog&module=blogs&controller=submit', 'front', 'blog_submit' ),
					'class'					=> 'IPS\blog\Blog',
					'permissionCheck'		=> 'add',
					'forceOwner'			=> Member::loggedIn(),
					'clubs'					=> Settings::i()->club_nodes_in_apps
			) ) );
			
			if ( $values = $form->values() )
			{
				$url = Url::internal( 'app=blog&module=blogs&controller=submit', 'front', 'blog_submit' )->setQueryString( 'id', $values['blog_select']->_id );
				Output::i()->redirect( $url );
			}
			
			Output::i()->output = Theme::i()->getTemplate( 'submit' )->blogSelector( $form );
		}
	}
}