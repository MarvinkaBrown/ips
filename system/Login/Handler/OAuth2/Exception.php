<?php
/**
 * @brief		OAuth Exception Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Apr 2017
 */

namespace IPS\Login\Handler\OAuth2;

use Exception as PHPException;

/**
 * OAuth2 Exception
 */
class Exception extends PHPException
{
	/**
	 * @brief	Description of the error
	 */
	public ?string $description = NULL;
	
	/**
	 * Constructor
	 *
	 * @param	string		$message		Exception Message
	 * @param	string|NULL	$description	Error Description or NULL.
	 * @return	void
	 */
	public function __construct( string $message, ?string $description = null )
	{
		parent::__construct( $message );
		$this->description = $description;
	}
}