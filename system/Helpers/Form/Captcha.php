<?php
/**
 * @brief		Captcha class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Apr 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use InvalidArgumentException;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Helpers\Form;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use function defined;
use function func_get_args;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Captcha class for Form Builder
 */
class Captcha extends FormAbstract
{
	/**
	 * CAPTCHA Class
	 */
	protected mixed $captcha = NULL;
	
	/**
	 * Does the configured CAPTCHA service support being added in a modal?
	 * 
	 * @return	bool
	 */
	public static function supportsModal(): bool
	{
		if ( Settings::i()->bot_antispam_type === 'none' )
		{
			return TRUE;
		}
		
		$class = '\IPS\Helpers\Form\Captcha\\' . IPS::mb_ucfirst( Settings::i()->bot_antispam_type );
		return ( !isset( $class::$supportsModal ) or $class::$supportsModal ); // isset() check is for backwards compatibility
	}
	
	/**
	 * Constructor
	 *
	 * @see        FormAbstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		$params = func_get_args();
		if ( !isset( $params[0] ) )
		{
			$params[0] = 'captcha_field';
		}
		
		if ( Settings::i()->bot_antispam_type != 'none' )
		{
			$class = '\IPS\Helpers\Form\Captcha\\' . IPS::mb_ucfirst( Settings::i()->bot_antispam_type );
			if ( !class_exists( $class ) )
			{
				Output::i()->error( 'unexpected_captcha', '4S262/1', 500, 'unexpected_captcha_admin' );
			}
			$this->captcha = new $class;
		}
		
		parent::__construct( ...$params );

		$this->required = TRUE;
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		if ( $this->captcha === NULL )
		{
			return '';
		}
		return parent::__toString();
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		if ( $this->captcha === NULL )
		{
			return '';
		}
		return $this->captcha->getHtml();
	}
	
	/**
	 * Get HTML
	 *
	 * @param	Form|null	$form	Form helper object
	 * @return	string
	 */
	public function rowHtml( Form $form=NULL ): string
	{
		if ( $this->captcha === NULL )
		{
			return '';
		}
		return ( method_exists( $this->captcha, 'rowHtml' ) and !$this->error ) ? $this->captcha->rowHtml() : parent::rowHtml( $form );
	}

	/**
	 * Get Value
	 *
	 * @return bool|null TRUE/FALSE indicate if the test passed or not. NULL indicates the test failed, but the captcha system will display an error so we don't have to.
	 */
	public function getValue(): ?bool
	{
		if ( $this->captcha === NULL )
		{
			return TRUE;
		}
		else
		{
			/* If we previously did an AJAX validate which is still valid, return true */
			$cached = NULL;
			$cacheKey =  'captcha-val-' . $this->name . '-' . Member::loggedIn()->ip_address;
			try
			{
				$cached = Cache::i()->getWithExpire( $cacheKey, TRUE );
			}
			catch( Exception $ex ) { }
			
			if ( $cached )
			{
				unset( Cache::i()->$cacheKey );
				return TRUE;
			}
			/* Otherwise, check with service */
			else
			{
				/* Check */
				$return = $this->captcha->verify();
				
				/* If it's valid and we're doing an AJAX validate, save that in the session so the next request doesn't check again */
				if ( $return and Request::i()->isAjax() )
				{
					Cache::i()->storeWithExpire( $cacheKey, time(), DateTime::create()->add( new DateInterval( 'PT1M' ) ), TRUE );
				}
				
				/* Return */
				return $return;
			}
		}
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		if ( $this->value !== TRUE )
		{
			throw new InvalidArgumentException( 'form_bad_captcha' );
		}
		
		return TRUE;
	}
}