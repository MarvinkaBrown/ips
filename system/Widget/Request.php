<?php

namespace IPS\Widget;

use BadMethodCallException;
use IPS\Application;
use IPS\Http\Url;
use IPS\Dispatcher;
use IPS\Request as IPSRequest;
use Exception;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Request
{

	/**
	 * @brief	The intstance
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static null|Request|IPSRequest $instance = NULL;

	/**
	 * @brief	Cached URL
	 */
	protected ?URL\Internal $_url	= NULL;

	/**
	 * Any additional data that has been set
	 *
	 * @var array
	 */
	protected array $data = array();

	/**
	 * Get the widget request instance; for the page builder, sometimes pass a "currentURL" flag to use as the current url so that things like request references in templates will work
	 *
	 * @return IPSRequest|Request
	 */
	public static function i() : IPSRequest|Request
	{
		if ( static::$instance === null )
		{
			try
			{
				if ( IPSRequest::i()->isAjax() and Dispatcher::hasInstance() and ( $lData = Dispatcher::i()->getLocationData() ) and ( ( $lData['app'] === 'core' and $lData['module'] === 'system' and $lData['controller'] === 'widgets' ) or ( $lData['app'] === "cms" and $lData['module'] === 'pages' and $lData['controller'] === 'builder' ) ) and IPSRequest::i()->currentURL and ( $url = Url::createFromString( IPSRequest::i()->currentURL ) ) and $url instanceof Url\Internal and is_array( $url->queryString ) )
				{
					static::$instance = new Request( $url );
				}
				else
				{
					throw new Exception;
				}
			}
			catch ( Exception )
			{
				static::$instance = IPSRequest::i();
			}
		}

		return static::$instance;
	}

	/**
	 * Construct a new Widget Request instance with the given URL
	 *
	 * @param URL\Internal	$url		The url that widgets should perceive
	 */
	public function __construct( URL\Internal $url )
	{
		$this->_url = $url;
	}

	/**
	 * Get a key from the current url the user is visiting, not the ajax url that they requested
	 * @param $key
	 * @return mixed
	 */
	public function __get( $key ) : mixed
	{
		if ( isset( $this->_url->queryString[$key] ) )
		{
			return $this->_url->queryString[$key];
		}

		if ( array_key_exists( $key, $this->data ) )
		{
			return $this->data[$key];
		}

		return IPSRequest::i()->$key ?? null;
	}

	/**
	 * Set a data value
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set( string $key, mixed $value ) : void
	{
		$this->data[ $key ] = $value;
	}

	/**
	 * Unset a key; note accessing via the magic method will still return null
	 * @param string $key
	 * @return void
	 */
	public function __unset( string $key )
	{
		$this->data[ $key ] = null;
	}

	/**
	 * Calls the method of \IPS\Request::i() to act as a proxy
	 *
	 * @param string $method
	 * @param array|null $args
	 *
	 * @return mixed
	 *
	 * @throws BadMethodCallException
	 */
	public function __call( string $method, ?array $args ) : mixed
	{
		if ( is_callable( [ IPSRequest::i(), $method ] ) )
		{
			return call_user_func_array( [ IPSRequest::i()], $args );
		}

		throw new BadMethodCallException( "The IPS\Widget\Request::{$method} is not callable!" );
	}

	/**
	 * If the instance is a base \IPS\Request instance, reset it to null
	 *
	 * @return void
	 */
	public static function reset() : void
	{
		if ( static::$instance instanceof IPSRequest )
		{
			static::$instance = null;
		}
	}

	/**
	 * For these requests, the actual request is ajax, but the goal is to render content on a non-ajax page, so the widgets and templates should read false
	 *
	 * @return bool
	 */
	public function isAjax() : bool
	{
		return false;
	}

	/**
	 * Get the url associated with this request
	 *
	 * @return Url
	 */
	public function url() : Url
	{
		return $this->_url;
	}

	/**
	 * Act like this is a normal get request
	 *
	 * @return string
	 */
	public function requestMethod() : string
	{
		return "GET";
	}
}