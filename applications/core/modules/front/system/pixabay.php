<?php
/**
 * @brief		Pixabay AJAX functions Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Jan 2020
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
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
 * Editor AJAX functions Controller
 */
class pixabay extends Controller
{
	/**
	 * Show the dialog window
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function manage() : void
	{
		if ( Settings::i()->pixabay_enabled )
		{
			$output = Theme::i()->getTemplate( 'system' )->pixabay( Request::i()->uploader );
			Output::i()->sendOutput( $output );
		}
	}

	/**
	 * Search pixabay
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function search() : void
	{
		if ( Settings::i()->pixabay_enabled )
		{
			$limit = isset( Request::i()->limit ) ? Request::i()->limit : 20;
			$offset = isset( Request::i()->offset ) ? Request::i()->offset : 0;
			$query = isset( Request::i()->search ) ? Request::i()->search : '';
			$url = Url::external( "https://pixabay.com/api/" );

			$parameters = array(
				'key' => Settings::i()->pixabay_apikey,
				'image_type' => 'photo',
				'per_page' => 20,
				'page' => ( $offset ) ? ceil( $offset / 20 ) + 1 : 1,
				'safesearch' => ( Settings::i()->pixabay_safesearch ) ? 'true' : 'false',
			);

			$parameters['q'] = urlencode( $query );
			$cacheKey = 'pixabay_' . md5( json_encode( $parameters ) );

			try
			{
				$request = Cache::i()->getWithExpire( $cacheKey, TRUE );
			}
			catch( OutOfRangeException $e )
			{
				$url = $url->setQueryString($parameters);
				$request = json_decode( $url->request()->get()->content, true );

				Cache::i()->storeWithExpire( $cacheKey, $request, DateTime::create()->add( new DateInterval('P1D') ), TRUE );
			}

			if ( isset( $request['message'] ) AND $request['message'] )
			{
				Output::i()->json( array('error' => $request['message'] ) );
			}


			$results = array( 'pagination' => array( 'total_count' => $request['total'] ) );
			foreach ( $request['hits'] as $row )
			{
				$results['images'][] = array(
					'thumb'	=> $row['webformatURL'],
					'url'   => $row['largeImageURL'],
					'imgid'	=> $row['id'],
					'imageHeight' => $row['imageHeight'],
					'imageWidth' => $row['imageWidth'],
					'thumbHeight' => $row['webformatHeight'],
					'thumbWidth' => $row['webformatWidth'],
				);
			}

			if ( empty( $results['images'] ) )
			{
				Output::i()->json( array( 'error' => Theme::i()->getTemplate( 'system', 'core' )->noResults() ) );
			}

			Output::i()->json( $results );
		}
	}

	/**
	 * Search pixabay
	 *
	 * @return void
	 */
	protected function getById() : void
	{
		if ( isset( Request::i()->id ) )
		{

			$url = Url::external( "https://pixabay.com/api/" )->setQueryString( array(
				'key' => Settings::i()->pixabay_apikey,
				'id' => Request::i()->id
			) );
			

			$request = json_decode( $url->request()->get()->content, true );
			
			$url = NULL;
			$filename = NULL;
			foreach ( $request['hits'] as $row )
			{
				$url = $row['largeImageURL'];
				
				if ( ! empty( $row['previewURL'] ) )
				{
					$filename = str_replace( '_150.', '.', basename( $row['previewURL'] ) );
				}
			}

			/* Now get the URL contents */
			$data = Url::external( $url )->request()->get();
			
			if ( ! $filename )
			{
				$filename = basename( $url );
			}

			list( $image, $type ) = explode( '/', $data->httpHeaders['Content-Type'] );
			Output::i()->json( array( 'content' => base64_encode( $data->content ), 'type' => $data->httpHeaders['Content-Type'], 'imageType' => $type, 'filename' => $filename ) );
		}
	}
}