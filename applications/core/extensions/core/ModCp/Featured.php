<?php
/**
 * @brief		Moderator Control Panel Extension: Announcements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Oct 2013
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ErrorException;
use IPS\core\Feature\Table;
use IPS\Extensions\ModCpAbstract;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use function array_merge;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Announcements
 */
class Featured extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return string|null
	 */
	public function getTab() : ?string
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_feature_content') )
		{
			return null;
		}
		
		return 'featured';
	}

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return	string
	 */
	public function manageType() : string
	{
		return 'content';
	}

	/**
	 * Manage featured content
	 *
	 * @return    void
	 * @throws ErrorException
	 */
	public function manage() : void
	{
		/* Guests can't promote things */
		if( ! Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C356/1', 403, '' );
		}

		/* Create the table */
		$table = new Table( Url::internal( 'app=core&module=feature&controller=feature', 'front', 'featured_show' ) );

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/promote.css' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('promote_manage_link');
		Output::i()->output = $table;
	}
}