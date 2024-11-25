<?php
/**
 * @brief		Customization AJAX actions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		07 May 2013
 */

namespace IPS\cms\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cms\Blocks\Block;
use IPS\cms\Databases;
use IPS\cms\Pages\Page;
use IPS\cms\Templates;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Members AJAX actions
 */
class ajax extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Return a CSS or HTML menu
	 *
	 * @return	void
	 */
	public function loadMenu() : void
	{
		$request   = array(
			't_location'  => ( isset( Request::i()->t_location ) ) ? Request::i()->t_location : null,
			't_group'     => ( isset( Request::i()->t_group ) ) ? Request::i()->t_group : null,
			't_key' 	  => ( isset( Request::i()->t_key ) ) ? Request::i()->t_key : null,
			't_type'      => ( isset( Request::i()->t_type ) ) ? Request::i()->t_type : 'templates',
		);

		switch( $request['t_type'] )
		{
			default:
			case 'template':
				$flag = Templates::RETURN_ONLY_TEMPLATE;
				break;
			case 'js':
				$flag = Templates::RETURN_ONLY_JS;
				break;
			case 'css':
				$flag = Templates::RETURN_ONLY_CSS;
				break;
		}

		$templates = Templates::buildTree( Templates::getTemplates( $flag + Templates::RETURN_DATABASE_ONLY ) );

		$current = new Templates;
		
		if ( ! empty( $request['t_key'] ) )
		{
			try
			{
				$current = Templates::load( $request['t_key'] );
			}
			catch( OutOfRangeException $ex )
			{
				
			}
		}

		Output::i()->output = Theme::i()->getTemplate( 'templates' )->menu( $templates, $current, $request );
	}

	/**
	 * Return HTML template as JSON
	 *
	 * @return	void
	 */
	public function loadTemplate() : void
	{
		$t_location  = Request::i()->t_location;
		$t_key       = Request::i()->t_key;
		
		if ( $t_location === 'block' and $t_key === '_default_' and isset( Request::i()->block_key ) )
		{

			$plugin = Widget::load( Application::load( Request::i()->block_app ), Request::i()->block_key, mt_rand() );
			$location = $plugin->getTemplateLocation();
			
			$templateBits  = Theme::master()->getAllTemplates( $location['app'], $location['location'], $location['group'], Theme::RETURN_ALL );
			$templateBit   = $templateBits[ $location['app'] ][ $location['location'] ][ $location['group'] ][ $location['name'] ];
			
			if ( ! isset( Request::i()->noencode ) OR ! Request::i()->noencode )
			{
				$templateBit['template_content'] = htmlentities( $templateBit['template_content'], ENT_DISALLOWED, 'UTF-8' );
			}
			
			$templateArray = array(
				'template_id' 			=> $templateBit['template_id'],
				'template_key' 			=> 'template_' . $templateBit['template_name'] . '.' . $templateBit['template_id'],
				'template_title'		=> $templateBit['template_name'],
				'template_desc' 		=> null,
				'template_content' 		=> $templateBit['template_content'],
				'template_location' 	=> null,
				'template_group' 		=> null,
				'template_container' 	=> null,
				'template_rel_id' 		=> null,
				'template_user_created' => null,
				'template_user_edited'  => null,
				'template_params'  	    => $templateBit['template_data']
			);
		}
		else
		{
			try
			{
				if ( is_numeric( $t_key ) )
				{
					$template = Templates::load( $t_key, 'template_id' );
				}
				else
				{
					$template = Templates::load( $t_key );
				}
			}
			catch( OutOfRangeException $ex )
			{
				Output::i()->json( array( 'error' => true ) );
			}

			if ( $template !== null )
			{
				$templateArray = array(
	                'template_id' 			=> $template->id,
	                'template_key' 			=> $template->key,
	                'template_title'		=> $template->title,
	                'template_desc' 		=> $template->desc,
	                'template_content' 		=> ( isset( Request::i()->noencode ) AND Request::i()->noencode ) ? ( $template->content ?? '' ) : htmlentities( ( $template->content ?? '' ), ENT_DISALLOWED, 'UTF-8' ),
	                'template_location' 	=> $template->location,
	                'template_group' 		=> $template->group,
	                'template_container' 	=> $template->container,
	                'template_rel_id' 		=> $template->rel_id,
	                'template_user_created' => $template->user_created,
	                'template_user_edited'  => $template->user_edited,
	                'template_params'  	    => $template->params
	            );
			}
		}

		if ( Request::i()->show == 'json' )
		{
			Output::i()->json( $templateArray );
		}
		else
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Theme::i()->getTemplate( 'templates', 'cms', 'admin' )->viewTemplate( $templateArray ) ) );
		}
	}
	
	/**
	 * [AJAX] Search templates
	 *
	 * @return	void
	 */
	public function searchtemplates() : void
	{
		Dispatcher::i()->checkAcpPermission( 'template_manage' );
				
		$where = array();
		if ( Request::i()->term )
		{
			$where[] = array( '( LOWER(template_title) LIKE ? OR LOWER(template_content) LIKE ? )', '%' . mb_strtolower( Request::i()->term ) . '%', '%' . mb_strtolower( Request::i()->term ) . '%' );
		}
	
		if ( ! in_array( 'custom', explode( ',', Request::i()->filters ) ) )
		{
			$where[] = array( 'template_master=1 OR (template_user_created=1 and template_user_edited=0)' );
		}
		
		if ( ! in_array( 'unmodified', explode( ',', Request::i()->filters ) ) )
		{
			$where[] = array( 'template_user_created=1 and template_user_edited=1' );
		}

		if ( isset( Request::i()->type ) )
		{
			$where[] = array( 'template_type=?', Request::i()->type );
		}
		
		$select = Db::i()->select(
			'*',
			'cms_templates',
			$where,
			'template_location, template_group, template_title, template_master desc'
		);

		$return = array();
		foreach( $select as $result )
		{
			$return[ $result['template_location'] ][ $result['template_group'] ][ $result['template_key'] ] = $result['template_title'];
		}
		
		Output::i()->json( $return );
	}
	
	/**
	 * Load Tags
	 *
	 * @return	void
	 */
	public function loadTags() : void
	{
		$page = NULL;
		if ( isset( Request::i()->pageId ) )
		{
			try
			{
				$page = Page::load( Request::i()->pageId );
			}
			catch( OutOfRangeException $e ) {}
		}
		
		$tags		= array();
		$tagLinks	= array();

		if ( $page and $page->id == Settings::i()->cms_error_page )
		{
			$tags['cms_error_page']['{error_message}'] = 'cms_error_page_message';
			$tags['cms_error_page']['{error_code}']    = 'cms_error_page_code';
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'databases', 'databases_use' ) )
		{
			foreach ( Databases::roots( NULL ) as $id => $db )
			{
				if ( !$db->page_id )
				{
					$tags['cms_tag_databases']['{database="' . $db->key . '"}'] = $db->_title;
				}
			}
		}

		foreach( Block::roots( NULL ) as $id => $block )
		{
			$tags['cms_tag_blocks']['{block="' . $block->key . '"}'] = $block->_title;
		}

		foreach( Db::i()->select( '*', 'cms_pages', NULL, 'page_full_path ASC', array( 0, 50 ) ) as $page )
		{
			$tags['cms_tag_pages']['{pageurl="' . $page['page_full_path'] . '"}' ] = Member::loggedIn()->language()->addToStack( Page::$titleLangPrefix . $page['page_id'] );
		}
		
		/* If we can manage words, then the header needs to always show */
		if (  Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			$tags['cms_tag_lang'] = array();
			$tagLinks['cms_tag_lang']	= array(
				'icon'		=> 'plus',
				'title'		=> Member::loggedIn()->language()->addToStack('add_word'),
				'link'		=> Url::internal( "app=core&module=languages&controller=languages&do=addWord" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'add_word' ), 'ipsDialog-remoteSubmit' => TRUE, 'action' => "wordForm" )
			);
		}
		
		foreach( Db::i()->select( '*', 'core_sys_lang_words', array( "word_is_custom=? AND lang_id=?", 1, Member::loggedIn()->language()->_id ) ) AS $lang )
		{
			$tags['cms_tag_lang']['{lang="' . $lang['word_key'] . '"}'] = $lang['word_custom'] ?: $lang['word_default'];
		}
		
		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Theme::i()->getTemplate( 'forms', 'core', 'global' )->editorTags( $tags, $tagLinks ) ) );
	}
}