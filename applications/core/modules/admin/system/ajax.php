<?php
/**
 * @brief		Core AJAX Responders
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Mar 2013
 */

namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\modules\front\system\ajax as SystemAjax;
use IPS\Db;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use function count;
use function defined;
use function file_put_contents;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Core AJAX Responders
 */
class ajax extends SystemAjax
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Save ACP Tabs
	 *
	 * @return	void
	 */
	protected function saveTabs() : void
	{
		Session::i()->csrfCheck();

		$tabs	= array();
		if ( is_array( Request::i()->tabOrder ) )
		{
			foreach( Request::i()->tabOrder as $topLevelTab )
			{
				$tabs[ str_replace( "tab_", "", $topLevelTab ) ]	= ( isset( Request::i()->menuOrder[ $topLevelTab ] ) ) ? Request::i()->menuOrder[ $topLevelTab ] : array();
			}
			
			$tabs = json_encode( $tabs );

			Db::i()->insert( 'core_acp_tab_order', array( 'id' => Member::loggedIn()->member_id, 'data' => $tabs ), TRUE );
			
			Request::i()->setCookie( 'acpTabs', $tabs );
		}
		
		Output::i()->json( ['message' =>'ok', 'tabs' => $tabs], 201 );
	}
	
	/**
	 * Save search keywords
	 *
	 * @return	void
	 */
	protected function searchKeywords() : void
	{
		Session::i()->csrfCheck();
		
		if ( \IPS\IN_DEV )
		{
			$url = base64_decode( Request::i()->url );
			$qs = array();
			parse_str( $url, $qs );
			
			Db::i()->delete( 'core_acp_search_index', array( 'url=?', $url ) );
			
			$inserts = array();

			foreach ( Request::i()->keywords as $word )
			{
				$inserts[] = array(
					'url'			=> $url,
					'keyword'		=> $word,
					'app'			=> $qs['app'],
					'lang_key'		=> Request::i()->lang_key,
					'restriction'	=> Request::i()->restriction ?: NULL
				);
			}
			
			if( count( $inserts ) )
			{
				Db::i()->insert( 'core_acp_search_index', $inserts );
			}

			$keywords = array();
			foreach ( Db::i()->select( '*', 'core_acp_search_index', array( 'app=?', $qs['app'] ), 'url ASC, keyword ASC' ) as $word )
			{
				$keywords[ $word['url'] ]['lang_key'] = $word['lang_key'];
				$keywords[ $word['url'] ]['restriction'] = $word['restriction'];
				$keywords[ $word['url'] ]['keywords'][] = $word['keyword'];
			}

			/* Make sure the keywords are unique */
			foreach( $keywords as $url => $entry )
			{
				$keywords[ $url ]['keywords'] = array_unique( $entry['keywords'] );
			}

			file_put_contents( \IPS\ROOT_PATH . "/applications/{$qs['app']}/data/acpsearch.json", json_encode( $keywords, JSON_PRETTY_PRINT ) );
		}
		
		Output::i()->json( 'ok' );
	}
}