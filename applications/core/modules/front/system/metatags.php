<?php
/**
 * @brief		Live meta tag editor
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		4 Sept 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use XMLWriter;
use function count;
use function defined;
use function is_array;
use function json_encode;
use function time;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Live meta tag editor
 */
class metatags extends Controller
{
	/**
	 * Redirect the request appropriately
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$this->_checkPermissions();

		$_SESSION['live_meta_tags']	= TRUE;

		Output::i()->redirect( Url::external( Settings::i()->base_url ) );
	}

	/**
	 * Save a meta tag
	 *
	 * @return	void
	 */
	protected function save() : void
	{
		/* Check permissions and CSRF */
		$this->_checkPermissions();

		Session::i()->csrfCheck();

		/* Delete any existing database entries, as we are about to re-insert */
		Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', Request::i()->meta_url ) );
		Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', trim( Request::i()->meta_url, '/' ) ) );

		/* Start save array */
		$save	= array(
			'meta_url'		=> Request::i()->meta_url,
			'meta_title'	=> Request::i()->meta_tag_title,
		);

		$_tags	= array();

		$metaTagNames		= Request::i()->meta_tag_name;
		$metaTagCustomNames = Request::i()->meta_tag_name_other;
		$metaTagValues		= Request::i()->meta_tag_content;

		/* Remove any default meta tags that have not been edited - don't save them permanently */
		if( isset( Request::i()->defaultMetaTag ) )
		{
			foreach( Request::i()->defaultMetaTag as $k => $v )
			{
				if( ( $key = array_search( $k, $metaTagNames ) ) !== FALSE AND $metaTagValues[ $key ] == $v )
				{
					unset( $metaTagNames[ $key ], $metaTagCustomNames[ $key ], $metaTagValues[ $key ] );
				}
				elseif( ( $key = array_search( $k, $metaTagCustomNames ) ) !== FALSE AND $metaTagValues[ $key ] == $v )
				{
					unset( $metaTagNames[ $key ], $metaTagCustomNames[ $key ], $metaTagValues[ $key ] );
				}
			}
		}

		/* If we asked to remove a default meta tag, store specially so we can do so */
		if( isset( Request::i()->deleteDefaultMeta ) )
		{
			foreach( Request::i()->deleteDefaultMeta as $v )
			{
				$_tags[ $v ] = NULL;

				if( ( $key = array_search( $v, $metaTagNames ) ) !== FALSE )
				{
					unset( $metaTagNames[ $key ], $metaTagCustomNames[ $key ], $metaTagValues[ $key ] );
				}
				elseif( ( $key = array_search( $v, $metaTagCustomNames ) ) !== FALSE )
				{
					unset( $metaTagNames[ $key ], $metaTagCustomNames[ $key ], $metaTagValues[ $key ] );
				}
			}
		}

		/* Store the new meta tags */
		if( is_array( $metaTagNames ) )
		{
			foreach( $metaTagNames as $k => $v )
			{
				if( $v AND ( $v != 'other' OR !empty( $metaTagCustomNames[ $k ] ) ) AND !isset( $_tags[ $v != 'other' ? $v : $metaTagCustomNames[ $k ] ] ) )
				{
					$_tags[ ( $v != 'other' ) ? $v : $metaTagCustomNames[ $k ] ]	= $metaTagValues[ $k ];
				}
			}
		}

		/* Save the meta tags, if there are any to save */
		if( count( $_tags ) OR Request::i()->meta_tag_title != '' )
		{
			$save['meta_tags']	= json_encode( $_tags );

			Db::i()->insert( 'core_seo_meta', $save );
		}

		unset( Store::i()->metaTags );

		/* Send back to the page */
		if( Request::i()->isAjax() )
		{
			return;
		}

		Output::i()->redirect( Url::external( Settings::i()->base_url . Request::i()->url ) );
	}

	/**
	 * Stop editing meta tags
	 *
	 * @return	void
	 */
	protected function end() : void
	{
		Session::i()->csrfCheck();
		
		$_SESSION['live_meta_tags']	= FALSE;
		
		Output::i()->redirect( Request::i()->referrer() ?: Url::internal( '' ) );
	}

	/**
	 * Check permissions to use the tool
	 *
	 * @return	void
	 */
	protected function _checkPermissions() : void
	{
		if( !Member::loggedIn()->member_id OR !Member::loggedIn()->isAdmin() )
		{
			Output::i()->error( 'meta_editor_no_admin', '2C155/1', 403, '' );
		}

		if( !Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'seo_manage' ) )
		{
			Output::i()->error( 'meta_editor_no_acpperm', '3C155/2', 403, '' );
		}
	}

	/**
	 * Output the web manifest file
	 *
	 * @return	void
	 */
	protected function manifest() : void
	{
		if( \IPS\IN_DEV === FALSE AND isset( Store::i()->manifest ) )
		{
			$output = Store::i()->manifest;
		}
		else
		{
			$manifest = json_decode( Settings::i()->manifest_details, TRUE );

			if( !$manifest )
			{
				$manifest = [];
			}

			if ( ! isset( $manifest['cache_key'] ) )
			{
				$manifest['cache_key'] = time();
				Settings::i()->changeValues( array( 'manifest_details' => json_encode( $manifest ) ) );
			}

			$output	= array(
				'scope'				=> rtrim( Settings::i()->base_url, '/' ) . '/',
				'name'				=> Settings::i()->board_name
			);

			foreach( $manifest as $k => $v )
			{
				if( $v )
				{
					$output[ $k ] = $v;
				}
			}

			$homeScreen = json_decode( Settings::i()->icons_homescreen, TRUE ) ?? array();
			$homeScreenMaskable = json_decode( Settings::i()->icons_homescreen_maskable, TRUE ) ?? array();

			foreach( $homeScreen as $k => $v )
			{
				if( mb_strpos( $k, 'android' ) !== FALSE )
				{
					if( !isset( $output['icons'] ) )
					{
						$output['icons']	= array();
					}

					$file = File::get( 'core_Icons', $v['url'] );

					$output['icons'][] = array(
						'src'	=> (string) $file->url->setQueryString( 'v', $manifest['cache_key'] ),
						'type'	=> File::getMimeType( $file->originalFilename ),
						'sizes'	=> $v['width'] . 'x' . $v['height'],
						'purpose' => 'any'
					);
				}
			}

			foreach( $homeScreenMaskable as $k => $v )
			{
				if( mb_strpos( $k, 'android' ) !== FALSE )
				{
					if( !isset( $output['icons'] ) )
					{
						$output['icons']	= array();
					}

					$file = File::get( 'core_Icons', $v['url'] );

					$output['icons'][] = array(
						'src'	=> (string) $file->url->setQueryString( 'v', $manifest['cache_key'] ),
						'type'	=> File::getMimeType( $file->originalFilename ),
						'sizes'	=> $v['width'] . 'x' . $v['height'],
						'purpose' => 'maskable'
					);
				}
			}

			Store::i()->manifest = $output;
		}

		$cacheHeaders	= ( !\IPS\IN_DEV ) ? Output::getCacheHeaders( time(), 86400 ) : array();
		
		Output::i()->sendOutput( json_encode( $output, JSON_PRETTY_PRINT ), 200, 'application/manifest+json', $cacheHeaders );
	}

	/**
	 * Output the IE browserconfig.xml file
	 *
	 * @return	void
	 */
	protected function iebrowserconfig() : void
	{
		if( \IPS\IN_DEV === FALSE AND isset( Store::i()->iebrowserconfig ) )
		{
			$output = Store::i()->iebrowserconfig;
		}
		else
		{
			$manifest = json_decode( Settings::i()->manifest_details, TRUE );

			/* Init */
			$xml = new XMLWriter;
			$xml->openMemory();
			$xml->setIndent( TRUE );
			$xml->startDocument( '1.0', 'UTF-8' );
			$xml->startElement( 'browserconfig' );
			$xml->startElement( 'msapplication' );
			$xml->startElement( 'tile' );

			if( isset( $manifest['theme_color'] ) AND $manifest['theme_color'] )
			{
				$xml->writeElement( 'TileColor', $manifest['theme_color'] );
			}

			$homeScreen = json_decode( Settings::i()->icons_homescreen, TRUE ) ?? array();

			foreach( $homeScreen as $k => $v )
			{
				if( mb_strpos( $k, 'msapplication' ) !== FALSE AND $k !== 'msapplication-TileImage' )
				{
					$file = File::get( 'core_Icons', $v['url'] );

					$xml->startElement( str_replace( 'msapplication-', '', $k ) );
					$xml->startAttribute( 'src' );
					$xml->text( (string) $file->url );
					$xml->endAttribute();
					$xml->endElement();
				}
			}

			$xml->endElement();
			$xml->endElement();
			$xml->endElement();
			$xml->endDocument();

			$output	= $xml->outputMemory();

			Store::i()->iebrowserconfig = $output;
		}

		$cacheHeaders	= ( !\IPS\IN_DEV ) ? Output::getCacheHeaders( time(), 86400 ) : array();
		
		Output::i()->sendOutput( $output, 200, 'application/xml', $cacheHeaders );
	}
}