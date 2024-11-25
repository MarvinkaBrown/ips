<?php
/**
 * @brief		Hello API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Dec 2015
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Response;
use IPS\Application;
use IPS\IPS;
use IPS\Settings;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Hello API
 */
class hello extends Controller
{
	/**
	 * GET /core/hello
	 * Get basic information about the community.
	 *
	 * @apireturn	array
	 * @apiresponse	string	communityName	The name of the community
	 * @apiresponse	string	communityUrl	The community URL
	 * @apiresponse	string	ipsVersion		The Invision Community version number
	 * @apiresponse	array	applications	The installed IPS Applications
	 * @return Response
	 */
	public function GETindex(): Response
	{
		return new Response( 200, array(
			'communityName'	=> Settings::i()->board_name,
			'communityUrl'	=> Settings::i()->base_url,
			'ipsVersion'	=> Application::load('core')->version,
			'ipsApplications' => array_filter( array_keys( Application::applications() ), function( $k ) { return in_array( $k, IPS::$ipsApps ); }  )
			) );
	}
}