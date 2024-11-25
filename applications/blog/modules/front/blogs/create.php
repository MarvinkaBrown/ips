<?php
/**
 * @brief		Create Blog
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		24 Mar 2014
 */

namespace IPS\blog\modules\front\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Blog
 */
class create extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( !Blog::canCreate() )
		{
			Output::i()->error( 'no_module_permission', '2B227/1', 403, '' );
		}
		
		$form = new Form( 'select_blog', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--create-blog';
		
		$blog	= new Blog;
		$blog->member_id = Member::loggedIn()->member_id;
		$blog->form( $form, TRUE );

		if ( $values = $form->values() )
		{
			$blog->saveForm( $blog->formatFormValues( $values ) );
		
			/* Redirect */
			Output::i()->redirect( $blog->url() );
		}
		
		Session::i()->setLocation( Url::internal( 'app=blog', 'front', 'blogs' ), array(), 'loc_blog_creating' );
		
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_view.js', 'blog', 'front' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('create_blog');

		if( Request::i()->isAjax() )
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'submit' )->createBlog( $form );
		}
	}
}