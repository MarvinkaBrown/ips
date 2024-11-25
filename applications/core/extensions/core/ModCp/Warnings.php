<?php
/**
 * @brief		Moderator Control Panel Extension: Recent Warnings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 May 2014
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\ModCpAbstract;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Recent Warnings
 */
class Warnings extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string|null
	 */
	public function getTab() : ?string
	{
		if ( ! Member::loggedIn()->modPermission('mod_see_warn') OR ! Settings::i()->warn_on )
		{
			return null;
		}
		
		return 'recent_warnings';
	}

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return	string
	 */
	public function manageType() : string
	{
		return 'members';
	}

	/**
	 * Get content to display
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		if ( ! Member::loggedIn()->modPermission('mod_see_warn') )
		{
			Output::i()->error( 'no_module_permission', '2C224/1', 403, '' );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'modcp_recent_warnings' );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'modcp_recent_warnings' ) );
		$table = new Content( 'IPS\core\Warnings\Warning', Url::internal( 'app=core&module=modcp&controller=modcp&tab=recent_warnings', 'front', 'modcp_recent_warnings' ) );
		$table->tableTemplate = array( Theme::i()->getTemplate('modcp'), 'recentWarningsTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate('modcp'), 'recentWarningsRows' );

		Output::i()->output = (string) $table;
	}
}