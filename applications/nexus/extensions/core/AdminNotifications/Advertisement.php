<?php
/**
 * @brief		ACP Notification: Advertisements Requiring Approval
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Jul 2018
 */

namespace IPS\nexus\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\core\modules\admin\promotion\advertisements;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: Advertisements Requiring Approval
 */
class Advertisement extends AdminNotification
{	
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'commerce';
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 4;
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this notification type compared to others in the same group
	 */
	public static int $itemPriority = 4;
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_Advertisement';
	}
	
	/**
	 * Is this type of notification ever optional (controls if it will be selectable as "viewable" in settings)
	 *
	 * @return	bool
	 */
	public static function mayBeOptional(): bool
	{
		return TRUE;
	}
	
	/**
	 * Is this type of notification might recur (controls what options will be available for the email setting)
	 *
	 * @return	bool
	 */
	public static function mayRecur(): bool
	{
		return TRUE;
	}
	
	/**
	 * @brief	Current count
	 */
	protected ?int $count = NULL;
	
	/**
	 * Get count
	 *
	 * @return	int
	 */
	public function count(): int
	{
		if ( $this->count === NULL )
		{
			$this->count = Db::i()->select( 'COUNT(*)', 'core_advertisements', array( 'ad_active=-1' ) )->first();
		}
		return $this->count;
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return $member->hasAcpRestriction( 'core', 'promotion', 'advertisements_manage' ) and Db::i()->select( 'COUNT(*)', 'nexus_packages_ads' )->first();
	}
	
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{		
		return Member::loggedIn()->language()->addToStack( 'acpNotification_nexusAdvertisements', FALSE, array( 'pluralize' => array( $this->count() ) ) );
	}
	
	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle(): ?string
	{
		try
		{
			return DateTime::ts( Db::i()->select( 'ad_start', 'core_advertisements', array( 'ad_active=-1' ), 'ad_start asc', 1 )->first() )->relative();
		}
		catch ( UnderflowException )
		{
			return NULL;
		}
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		$table = advertisements::table( Url::internal('app=core&module=overview&controller=notifications&_table=nexus_Advertisement') );
		$table->limit = 10;
		$table->filters = array();
		$table->quickSearch = NULL;
		$table->advancedSearch = [];
		$table->where[] = array( 'ad_active=-1' );
		
		return (string) $table;
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		return static::SEVERITY_OPTIONAL;
	}
	
	/**
	 * Dismissible?
	 *
	 * @return	string
	 */
	public function dismissible(): string
	{	
		return static::DISMISSIBLE_NO;
	}
	
	/**
	 * Style
	 *
	 * @return	string
	 */
	public function style(): string
	{
		return static::STYLE_INFORMATION;
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url
	 */
	public function link(): Url
	{
		return Url::internal('app=core&module=promotion&controller=advertisements&filter=ad_filters_pending');
	}
	
	/**
	 * Should this notification dismiss itself?
	 *
	 * @note	This is checked every time the notification shows. Should be lightweight.
	 * @return	bool
	 */
	public function selfDismiss(): bool
	{
		return !$this->count();
	}
}