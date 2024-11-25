<?php
/**
 * @brief		Content Router extension: Records
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Board
 * @since		17 Apr 2014
 */

namespace IPS\cms\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Databases;
use IPS\Extensions\ContentRouterAbstract;
use IPS\Member;
use IPS\Member\Group;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Records
 */
class Records extends ContentRouterAbstract
{
	/**
	 * @brief	Can be shown in similar content
	 */
	public bool $similarContent = TRUE;

	/**
	 * Constructor
	 *
	 * @param Member|Group|null $member If checking access, the member/group to check for, or NULL to not check access
	 */
	public function __construct( Member|Group $member = NULL )
	{
		try
		{
			foreach ( Databases::databases() as $id => $database )
			{
				if( $database->page_id )
				{
					if ( !$member or $database->can( 'view', $member ) )
					{
						$this->classes[] = 'IPS\cms\Records' . $id;
					}
				}
			}
		}
		catch ( Exception $e ) {} // If you have not upgraded pages but it is installed, this throws an error
	}
}