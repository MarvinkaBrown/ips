<?php
/**
 * @brief		ACP Notification: Transactions Requiring Attention
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Jul 2018
 */

namespace IPS\nexus\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Transaction as NexusTransaction;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: Transactions Requiring Attention
 */
class Transaction extends AdminNotification
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
	public static int $itemPriority = 2;
	
	/**
	 * Get queue HTML
	 *
	 * @param	string	$status	Status to show
	 * @return	string
	 */
	public static function queueHtml( string $status ) : string
	{
		$select = Db::i()->select( '*', 'nexus_transactions', array( 't_status=?', $status ), 't_date ASC', array( 0, 12 ) );
		
		if ( count( $select ) )
		{ 	
			return Theme::i()->getTemplate( 'notifications', 'nexus' )->transactions( new ActiveRecordIterator( $select, 'IPS\nexus\Transaction' ) );
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_Transaction';
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
			$this->count = Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_status=?', $this->extra ) )->first();
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
		return $member->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' );
	}
	
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{		
		return Member::loggedIn()->language()->addToStack( 'acpNotification_nexusTransaction_' . $this->extra, FALSE, array( 'pluralize' => array( $this->count() ) ) );
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
			return DateTime::ts( Db::i()->select( 't_date', 'nexus_transactions', array( 't_status=?', $this->extra ), 't_date asc', 1 )->first() )->relative();
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
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('admin_notifications.js', 'nexus') );
		
		return static::queueHtml( $this->extra );
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
		if ( $this->extra === NexusTransaction::STATUS_DISPUTED )
		{
			return static::STYLE_WARNING;
		}
		else
		{
			return static::STYLE_INFORMATION;
		}
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url
	 */
	public function link(): Url
	{
		return Url::internal('app=nexus&module=payments&controller=transactions&attn=1');
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