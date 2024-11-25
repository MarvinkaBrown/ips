<?php
/**
 * @brief		IndexNow Submission Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Jan 2022
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Request\CurlException;
use IPS\Http\Url;
use IPS\Log;
use IPS\Patterns\Singleton;
use IPS\Settings;
use IPS\Task;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IndexNow class
 *
 * Submits the content to indexnow - https://www.indexnow.org/documentation
 *
 * The submission should always happen via the queue system!
 */
class IndexNow extends Singleton
{
	/**
	 * @brief	Singleton Instances
	 */
	protected static ?Singleton $instance = NULL;

	/**
	 * @brief Target URL for the API Request
	 *
	 * @var string
	 */
	static string $apiUrl = "https://api.indexnow.org/indexnow/";

	/**
	 * Is the extension enabled?
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return (bool) Settings::i()->indexnow_enabled;
	}

	/**
	 * Queues a URL to be sent to indexnow
	 *
	 * @param Url|string $url
	 * @return void
	 */
	public static function addUrlToQueue( Url|string $url ) : void
	{
		static::addUrlsToQueue([ $url ] );
	}

	/**
	 * Queues an array with URLs to be sent to indexnow
	 *
	 * @param array $urls
	 * @return void
	 */
	public static function addUrlsToQueue( array $urls ) : void
	{
		$obj = static::i();
		if( $obj->isEnabled() )
		{
			Task::queue( 'core', 'IndexNow', [ 'urls' => array_map('strval', $urls) ] );
		}
	}

	/**
	 * Sends all the urls in the queue to indexnow
	 *
	 * @param array $urls
	 * @return void
	 */
	public function send( array $urls ) : void
	{
		/* Move on if there's nothing to send */
		if( !count( $urls ) )
		{
			return;
		}

		$url = Url::internal( '', 'front' );
		$data = array(
			'host'         => Url::baseUrl(Url::PROTOCOL_WITHOUT ),
			'key'          => Settings::i()->indexnow_key,
			'keyLocation'  => (string) $url->setPath( $url->data[ Url::COMPONENT_PATH ] .  $this->getKeyFileName() ),
			'urlList'     => $urls,
		);


		try {
			$response = Url::external(static::$apiUrl)->request()
				->setHeaders(array('Content-Type' => 'application/json'))->post(json_encode($data));
			if (!$response->isSuccessful()) {
				switch ($response->httpResponseCode) {
					case 400:
						$error = 'invalid_request';
						break;
					case 403:
						$error = 'invalid_api_key';
						break;
					case 422:
						$error = 'invalid_url';
						break;
					case 429:
					default:
						$error = 'unknown_error';
				}
				Log::log( $response->httpResponseCode . ' ' . $error . ' ' . print_r($response, TRUE) . print_r($data, TRUE), 'IndexNow');
			}
		} catch (CurlException $e)
		{
			Log::log($e->getMessage(), 'IndexNow');
		}
	}

	/**
	 * Return the indexnow verification file name
	 *
	 * @return string
	 */
	public function getKeyFileName(): string
	{
		return Settings::i()->indexnow_key . '.txt';
	}

	/**
	 * Return the indexnow verification file content, which is literally only the key!
	 *
	 * @return string
	 */
	public function getKeyfileContent(): string
	{
		return Settings::i()->indexnow_key;
	}
}