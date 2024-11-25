<?php
/**
 * @brief		Template Exception Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Dec 2017
 */

namespace IPS\Theme;
 
/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Theme;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Exception Class
 */
class TemplateException extends RuntimeException
{
	/**
	 * @brief	Template data
	 */
	public array $template	= array( 'location' => NULL, 'group' => NULL, 'app' => NULL );

	/**
	 * @brief	Theme
	 */
	public ?Theme $theme		= NULL;

	/**
	 * Constructor
	 *
	 * @param string|null $message MySQL Error message
	 * @param int $code MySQL Error Code
	 * @param Exception|NULL $previous Previous Exception
	 * @param array|null $template Template data (app, group, location)
	 * @param Theme|null $theme Theme object
	 */
	public function __construct( ?string $message = null, int $code = 0, ?Exception $previous = null, ?array $template=NULL, ?Theme $theme=NULL )
	{
		/* Store these for the extraLogData() method */
		$this->template = $template;
		$this->theme = $theme;
				
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Is this an issue with a third party theme?
	 *
	 * @return	bool
	 */
	public function isThirdPartyError() : bool
	{
		/* Look for a custom template inserted via hookpoint */
		try
		{
			return (bool) Db::i()->select( 'count(*)', 'core_theme_templates_custom', [
				[ 'template_set_id=?', $this->theme->id ],
				[ "template_hook_point LIKE CONCAT(?,'%')", ( $this->template['app'] . '/' . $this->template['location'] . '/' . $this->template['group'] . '/' ) ]
			])->first();
		}
		catch( Exception $e )
		{
			return FALSE;
		}
	}
}