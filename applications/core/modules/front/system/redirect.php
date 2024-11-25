<?php
/**
 * @brief		External redirector with key checks
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Jun 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\core\Advertisement;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redirect
 */
class redirect extends Controller
{
	/**
	 * @brief	Is this for displaying "content"? Affects if advertisements may be shown
	 */
	public bool $isContentPage = FALSE;

	/**
	 * Handle munged links
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* The URL may have had a line-break added in the outbound email if it's a long URL, we need to fix that. Then check the key matches.  */
		try
		{
			$url = str_replace( [ '%20','%0D' ], '', (string) Url::createFromString( Request::i()->url ) );
			if ( Login::compareHashes( hash_hmac( "sha256", $url, Settings::i()->site_secret_key ), (string) Request::i()->key ) OR Login::compareHashes( hash_hmac( "sha256", $url, Settings::i()->site_secret_key . 'r' ), (string) Request::i()->key ) )
			{
				/* Construct the URL */
				$url = Url::external( $url );

				/* If this is coming from email tracking, log the click */
				if( isset( Request::i()->email ) AND Settings::i()->prune_log_emailstats != 0 )
				{
					/* If we have a row for "today" then update it, otherwise insert one */
					$today = DateTime::create()->format( 'Y-m-d' );

					try
					{
						/* We only include the time column in the query so that the db index can be effectively used */
						if( !Request::i()->type )
						{
							$currentRow = Db::i()->select( '*', 'core_statistics', array( 'type=? AND time>? AND value_4=? AND extra_data IS NULL', 'email_clicks', 1, $today ) )->first();
						}
						else
						{
							$currentRow = Db::i()->select( '*', 'core_statistics', array( 'type=? AND time>? AND value_4=? AND extra_data=?', 'email_clicks', 1, $today, Request::i()->type ) )->first();
						}

						Db::i()->update( 'core_statistics', "value_1=value_1+1", array( 'id=?', $currentRow['id'] ) );
					}
					catch( UnderflowException $e )
					{
						Db::i()->insert( 'core_statistics', array( 'type' => 'email_clicks', 'value_1' => 1, 'value_4' => $today, 'time' => time(), 'extra_data' => Request::i()->type ) );
					}
				}

				/* Send the user to the URL after setting the referrer policy header */
				Output::i()->sendHeader( "Referrer-Policy: origin" );
				Output::i()->redirect( $url, Request::i()->email ? '' : Member::loggedIn()->language()->addToStack('external_redirect'), 303, !Request::i()->email );
			}
			/* If it doesn't validate, send the user to the index page */
			else
			{
				throw new DomainException();
			}
		}
		catch( DomainException $e )
		{
			Output::i()->redirect( Url::internal('') );
		}
	}

	/**
	 * Redirect an advertisement click
	 *
	 * @return	void
	 */
	protected function advertisement() : void
	{
		/* Get the advertisement */
		$advertisement	= array();

		if( isset( Request::i()->ad ) )
		{
			try
			{
				$advertisement	= Advertisement::load( Request::i()->ad );
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'ad_not_found', '2C159/2', 404, 'ad_not_found_admin' );
			}
		}

		if( !$advertisement->id OR !$advertisement->link )
		{
			Output::i()->error( 'ad_not_found', '2C159/1', 404, 'ad_not_found_admin' );
		}

		if ( Login::compareHashes( hash_hmac( "sha256", $advertisement->link, Settings::i()->site_secret_key ), (string) Request::i()->key ) OR Login::compareHashes( hash_hmac( "sha256", $advertisement->link, Settings::i()->site_secret_key . 'a' ), (string) Request::i()->key ) )
		{
			/* We need to update click count for this advertisement. Does it need to be shut off too due to hitting click maximum?
				Note that this needs to be done as a string to do "col=col+1", which is why we're not using the ActiveRecord save() method.
				Updating by doing col=col+1 is more reliable when there are several clicks at nearly the same time. */
			$update	= "ad_clicks=ad_clicks+1, ad_daily_clicks=ad_daily_clicks+1";

			if( $advertisement->maximum_unit == 'c' AND $advertisement->maximum_value > -1 AND $advertisement->clicks + 1 >= $advertisement->maximum_value )
			{
				$update	.= ", ad_active=0";
			}

			/* Update the database */
			Db::i()->update( 'core_advertisements', $update, array( 'ad_id=?', $advertisement->id ) );

			/* And do the redirect */
			Output::i()->redirect( Url::external( $advertisement->link ) );
		}
		/* If it doesn't validate, send the user to the index page */
		else
		{
			Output::i()->redirect( Url::internal('') );
		}
	}
}