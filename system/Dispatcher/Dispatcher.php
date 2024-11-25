<?php
/**
 * @brief		Dispatcher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS;
 
/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use ErrorException;
use IPS\Application\Module;
use IPS\Dispatcher\Controller;
use IPS\Platform\Bridge;
use RuntimeException;
use function defined;
use function dirname;
use function get_called_class;
use function strstr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dispatcher
 *
 * @property Application $application
 * @property Module $module
 * @property string $controller
 */
abstract class Dispatcher
{
	/**
	 * @brief	Singleton Instance
	 */
	protected static mixed $instance = NULL;
	
	/**
	 * Check if a dispatcher instance is available
	 *
	 * @return	bool
	 * @note	This should be used sparingly, primarily for gateway scripts that do not need a dispatcher but still use the framework
	 */
	public static function hasInstance(): bool
	{
		return ( static::$instance !== NULL );
	}

	/**
	 * Get instance
	 *
	 * @return	static
	 */
	public static function i(): static
	{
		if( static::$instance === NULL )
		{
			$class = get_called_class();

			if( $class == 'IPS\\Dispatcher' )
			{
				throw new RuntimeException( "Only subclasses of Dispatcher can be instantiated" );
			}
			
			static::$instance = new $class;
			
			if( static::$instance->controllerLocation != 'setup' )
			{
				$_redirect	= FALSE;
				
				if ( !file_exists( SITE_FILES_PATH . '/conf_global.php' ) )
				{
					$_redirect	= TRUE;
				}
				else
				{
					require SITE_FILES_PATH . '/conf_global.php';

					if( !isset( $INFO['sql_database'] ) )
					{
						$_redirect	= TRUE;
					}
					else if ( !isset( $INFO['installed'] ) OR !$INFO['installed'] )
					{
						/* This looks weird, but there was a period of time where "installed" was misspelled as "instaled" on Community in the Cloud after install finished. So, if that is present, assume we're okay. */
						if ( !isset( $INFO['instaled'] ) )
						{
							if( isset( $_SERVER['SERVER_PROTOCOL'] ) and strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
							{
								header( "HTTP/1.0 503 Service Unavailable" );
							}
							else
							{
								header( "HTTP/1.1 503 Service Unavailable" );
							}
									
							require ROOT_PATH . '/admin/install/installing.html';
							exit;
						}
					}
				}

				if( $_redirect === TRUE )
				{
					/* conf_global.php does not exist, forward to installer - we'll do this manually to avoid any code in Output.php that anticipates the installation already being complete (such as setting CSP header in __construct()) */
					$url	= ( Request::i()->isSecure()  ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . rtrim( dirname( $_SERVER['SCRIPT_NAME'] ), '/' );

					header( "HTTP/1.1 307 Temporary Redirect" );
					foreach(Output::getNoCacheHeaders() as $headerKey => $headerValue )
					{
						header( "{$headerKey}: {$headerValue}" );
					}
					header( "Location: {$url}/admin/install/" );
					exit;
				}
			}
			
			static::$instance->init();
		}
		
		return static::$instance;
	}
	
	/**
	 * @brief	Controller Classname
	 */
	protected ?string $classname = NULL;

	/**
	 * @brief	Controller instance
	 */
	public mixed $dispatcherController = NULL;

	/**
	 * Init
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	abstract public function init() : void;

	/**
	 * Run
	 *
	 * @return	void
	 */
	public function run() : void
	{
		/* Init class */
		if( !class_exists( $this->classname ) )
		{
			Output::i()->error( 'page_doesnt_exist', '2S100/1', 404 );
		}
		$this->dispatcherController = new $this->classname;
		if( !( $this->dispatcherController instanceof Controller ) )
		{
			Output::i()->error( 'page_not_found', '5S100/3', 500, '' );
		}
		
		/* Execute */
		$this->dispatcherController->execute();
		
		$this->finish();
	}

	/**
	 * Finish
	 *
	 * @return    void
	 * @throws ErrorException
	 */
	public function finish() : void
	{
		Bridge::i()->dispatcherFinish();

		/* If we're still here - output */
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Output::i()->output ), 200, 'text/html' );
		}
		else
		{
			/* Just prefetch this to save a query later */
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, $this->getLocationData() ), 200, 'text/html' );
		}
	}

	/**
	 * Get an array of data representing the user's current location
	 * This gets passed to the templates in order to apply some attributes to the body tag
	 * 
	 * @return 	array{app:string,module:string,controller:string,id?:int|null}
	 */
	public function getLocationData(): array
	{
		return array( 
			'app' => Dispatcher::i()->application->directory,
			'module' => Dispatcher::i()->module->key,
			'controller' => Dispatcher::i()->controller,
			'id' => Request::i()->id ? (int) Request::i()->id : NULL
		);
	}

	/**
	 * Check the current location to determine if it's a match
	 *
	 * @param string $location
	 * @param string|null $app
	 * @param string|null $module
	 * @param string|null $controller
	 * @return bool
	 */
	public static function checkLocation( string $location, ?string $app = null, ?string $module = null, ?string $controller = null ) : bool
	{
		/* If we have no instance it's always false */
		if( !static::hasInstance() )
		{
			return false;
		}

		/* This is required */
		if( static::i()->controllerLocation != $location )
		{
			return false;
		}

		/* If we specified an application, check that first */
		if( $app !== null )
		{
			if( isset( static::i()->application ) )
			{
				if( static::i()->application->directory != $app )
				{
					return false;
				}
			}
			elseif( isset( Request::i()->app ) and Request::i()->app != $app )
			{
				return false;
			}
		}

		/* Repeat for module */
		if( $module !== null )
		{
			if( isset( static::i()->module ) )
			{
				if( static::i()->module->key != $module )
				{
					return false;
				}
			}
			elseif( isset( Request::i()->module ) and Request::i()->module != $module )
			{
				return false;
			}
		}

		/* And finally the controller */
		if( $controller !== null )
		{
			if( isset( static::i()->controller ) and static::i()->controller != $controller )
			{
				return false;
			}
			elseif( isset( Request::i()->controller ) and Request::i()->controller != $controller )
			{
				return false;
			}
		}

		/* Still here? All good */
		return true;
	}
}