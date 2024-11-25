<?php
/**
 * @brief		X share link
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Sept 2023
 * @see			<a href='https://dev.x.com/docs/tweet-button'>X button documentation</a>
 */

namespace IPS\Content\ShareServices;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Content\ShareServices;
use IPS\core\ShareLinks\Service;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\IPS;
use IPS\Login\Handler;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use Throwable;
use function defined;
use function ord;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Twitter share link
 */
class X extends ShareServices
{
	/**
	 * Determine whether the logged in user has the ability to autoshare
	 *
	 * @return	boolean
	 */
	public static function canAutoshare(): bool
	{
		if ( $method = Handler::findMethod( 'IPS\Login\Handler\OAuth1\Twitter' ) and $method->canProcess( Member::loggedIn() ) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Publish text or a URL to this service
	 *
	 * @param	string	$content	Text to publish
	 * @param	string|null	$url		[URL to publish]
	 * @return	void
	 */
	public static function publish( string $content, string|null $url=null ): void
	{
		throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('x_publish_no_user') );
	}

	/**
	 * Add any additional form elements to the configuration form. These must be setting keys that the service configuration form can save as a setting.
	 *
	 * @param	Form				$form		Configuration form for this service
	 * @param	Service	$service	The service
	 * @return	void
	 */
	public static function modifyForm( Form &$form, Service $service ): void
	{
		$form->add( new Text( 'x_hashtag', Settings::i()->x_hashtag, FALSE ) );
	}

	/**
	 * Return the HTML code to show the share link
	 *
	 * @return	string
	 */
	public function __toString(): string
	{
		try
		{
			$url = preg_replace_callback( "{[^0-9a-z_.!~*'();,/?:@&=+$#-]}i",
				function ( $m )
				{
					return sprintf( '%%%02X', ord( $m[0] ) );
				},
				$this->url) ;

			$title = $this->title ?: NULL;
			if ( Settings::i()->x_hashtag !== '')
			{
				$title .= ' ' . Settings::i()->x_hashtag;
			}
			return Theme::i()->getTemplate( 'sharelinks', 'core' )->x( urlencode( $url ), rawurlencode( $title ) );
		}
		catch ( Exception | Throwable $e )
		{
			IPS::exceptionHandler( $e );
		}
		return '';
	}
}