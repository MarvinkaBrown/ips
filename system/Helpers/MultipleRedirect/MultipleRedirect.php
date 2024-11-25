<?php
/**
 * @brief		Multiple Redirector
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Apr 2013
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Log;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;
use function intval;
use function is_numeric;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Multiple Redirector
 */
class MultipleRedirect
{
	/**
	 * @brief	URL
	 */
	protected mixed $url = NULL;
	
	/**
	 * @brief	Output
	 */
	protected string $output = '';
	
	/**
	 * @brief	Prevents the final redirect
	 */
	public bool $noFinalRedirect = FALSE;

	/**
	 * Constructor
	 *
	 * @param	mixed		$url			The URL where the redirector takes place
	 * @param callback $callback		The function to run - should return an array with three elements, or NULL to indicate the process is finished or a string to display -
	 *	@li	Data to pass back to itself for the next "step"
	 *	@li	A message to display to the user
	 *	@li	[Optional] A number between 	1 and 100 for a progress bar
	 * @param callback $finished		Code to run when finished
	 * @param bool $finalRedirect	If FALSE, will not force a real redirect to the finished method
	 * @return	void
	 */
	public function __construct( mixed $url, callable $callback, callable $finished, bool $finalRedirect=TRUE )
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_core.js', 'core', 'global' ) );
		Output::i()->bypassCsrfKeyCheck = TRUE;
		
		$this->url	= $url;
		$key		= 'mr-' . md5( $url );

		if ( isset( Request::i()->mr ) and ! isset( Request::i()->mr_continue ) )
		{
			if ( isset( Request::i()->_mrReset ) )
			{
				$data = NULL;
				$iteration = 1;
			}
			elseif ( isset( $_SESSION[ $key ] ) )
			{
				$data = json_decode( $_SESSION[ $key ], TRUE );
				$iteration = intval( Request::i()->mr ) + 1;
			}
			elseif ( !is_numeric( Request::i()->mr ) and $base64Decoded = @base64_decode( Request::i()->mr ) )
			{
				$data = json_decode( urldecode( $base64Decoded ), TRUE );
				$iteration = 1;
			}
			else
			{
				$data = NULL;
				$iteration = 1;
			}
			
			if ( $data === '__done' )
			{
				unset( $_SESSION[ $key ] );
				
				$finished();
				return;
			}
			
			try
			{ 
				$response = $callback( $data );
				
				if ( $response === NULL )
				{
					unset( $_SESSION[ $key ] );
					if ( !Request::i()->isAjax() )
					{
						$finished();
						return;
					}
					else
					{
						$_SESSION['mr-' . md5( $url ) ] = json_encode( $finalRedirect ? '__done' : '__done__' );
						Output::i()->json( $finalRedirect ? array( 'done' => true ) : array( 'close' => true ) );
					}
				}
				elseif ( is_string( $response[0] ) )
				{
					$this->output = $response[0];
					if ( Request::i()->isAjax() )
					{
						Output::i()->json( array( 'custom' => $response[0] ) );
					}
				}
				else
				{
					$_SESSION['mr-' . md5( $url ) ] = json_encode( $response[0] );

					if ( !Request::i()->isAjax() )
					{
						Output::i()->redirect( $this->url->setQueryString( 'mr', $iteration ), $response[1], 303, TRUE );
					}
					else
					{
						Output::i()->json( $response );
					}
				}
			}
			catch ( Exception $e )
			{
				if ( \IPS\IN_DEV )
				{
					Output::i()->error( $e->getMessage() . ' ' . $e->getLine() . ' ' . str_replace( "\n", "<br>", $e->getTraceAsString() ), '1S111/1', 403, '' );
				}

				Log::log( $e, 'multiredirect' );
				Output::i()->error( $e->getMessage(), '1S111/1', 403, '' );
			}
		}
	}
	
	/**
	 * Get Starting HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		/* Don't cache for a short while to ensure sessions work */
		Output::setCacheTime( false );
		
		return $this->output ?: Theme::i()->getTemplate( 'global', 'core', 'global' )->multipleRedirect( $this->url, ( ( isset( Request::i()->mr_continue ) and isset( Request::i()->mr ) ) ? Request::i()->mr : 0 ), isset( Request::i()->_wizardHeight ) ? intval( Request::i()->_wizardHeight ) : NULL );
	}
}