<?php
/**
 * @brief		Badge Model (as in, a representation of a badge a member *can* earn, not a badge a particular member *has* earned)
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Mar 21
 */

namespace IPS\core\Achievements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use IPS\Application;
use IPS\Content;
use IPS\Db;
use IPS\Member;
use IPS\Notification;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use function defined;
use function get_class;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Recognize
 */
class Recognize extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_member_recognize';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'r_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons = array();

	/**
	 * Get member object
	 *
	 * @return Member
	 */
	public function get__given_by(): Member
	{
		return Member::load( $this->given_by );
	}

	/**
	 * Load a recognize row from the content item
	 *
	 * @param	Content						$content		Content item that has been rewarded
	 * @return ActiveRecord
	 */
	public static function loadFromContent( Content $content ): ActiveRecord
	{
		$idField = $content::$databaseColumnId;

		/* Let any exceptions bubble up */
		return static::constructFromData(
			Db::i()->select( '*', 'core_member_recognize', [ 'r_content_class=? and r_content_id=?', get_class( $content ), $content->$idField ] )->first()
		);
	}

	/**
	 * Add a new recognize entry
	 *
	 * @param	Content						$content		Content item that is being rewarded
	 * @param	Member							$member			Member to award
	 * @param	int									$points			Number of points to add
	 * @param Badge|NULL	$badge			Badge to assign (if any)
	 * @param	string								$message		Custom message (if any)
	 * @param	Member							$awardedBy		Awarded by
	 * @param	bool								$showPublicly	Show this message to everyone
	 * @return void
	 */
	public static function add( Content $content, Member $member, int $points, ?Badge $badge, string $message, Member $awardedBy, bool $showPublicly=FALSE ) : void
	{
		$idField = $content::$databaseColumnId;
		
		/* Add to database */
		$recognize = new static;
		$recognize->member_id = $member->member_id;
		$recognize->content_class = get_class( $content );
		$recognize->content_id = $content->$idField;
		$recognize->added = time();
		$recognize->points = $points;
		$recognize->badge = $badge ? $badge->_id : 0;
		$recognize->message = $message;
		$recognize->given_by = $awardedBy->member_id;
		$recognize->public = $showPublicly;
		$recognize->save();

		if ( $badge )
		{
			$member->awardBadge( $badge, 0, 0, ['subject'], $recognize->id );
			$member->logHistory( 'core', 'badges', [ 'action' => 'manual', 'id' => $badge->_id, 'recognize' => $recognize->id ] );
		}
		if ( $points )
		{
			$member->logHistory( 'core', 'points', array('by' => 'manual', 'points' => $points, 'recognize' => $recognize->id ) );
			$member->awardPoints( $points, 0, [], ['subject'], $recognize->id );
		}

		$notification = new Notification( Application::load( 'core' ), 'new_recognize', $member, [ $member, $recognize ], [ $recognize->id ] );
		$notification->recipients->attach( $member );
		$notification->send();
	}
	
	/**
	 * Return the content object
	 *
	 * @return Content
	 */
	public function content(): Content
	{
		$class = $this->content_class;
		return $class::loadAndCheckPerms( $this->content_id );
	}

	/**
	 * Wrapper to get content.
	 *
	 * @return	Content|NULL
	 * @note	This simply wraps content()
	 */
	public function contentWrapper() : Content|null
	{
		try
		{
			return $this->content();
		}
		catch( BadMethodCallException | OutOfRangeException ) { }

		return null;
	}
	
	/**
	 * Return a badge, or null
	 * 
	 * @return NULL|Badge
	 */
	public function badge() : Badge|null
	{
		try
		{
			return Badge::load( $this->badge );
		}
		catch( OutOfRangeException ) { }
		
		return NULL;
	}
	
	/**
	 * Return a member or NULL if the member no longer exists
	 * 
	 * @return NULL|Member
	 */
	public function awardedBy() : Member|null
	{
		try
		{
			return Member::load( $this->given_by );
		}
		catch( OutOfRangeException ) { }
		
		return NULL;
	}

	/**
	 * Remove the recognize and remove points / badges earned
	 *
	 * @return void
	 */
	public function delete(): void
	{
		try
		{
			if ( $this->points )
			{
				/* Wade does it this way so I guess there's a reason. And let the reason be love */
				Db::i()->update( 'core_members', "achievements_points = achievements_points - " . intval( $this->points ), [ 'member_id=?', $this->member_id ] );
				Db::i()->delete( 'core_points_log', [ 'recognize=?', $this->id ] );
			}

			if ( $this->badge )
			{
				Db::i()->delete( 'core_member_badges', [ 'member=? and recognize=?', $this->member_id, $this->id ] );
			}

			/* Remove notifications */
			Db::i()->delete( 'core_notifications', array( 'item_class=? and notification_key=? and item_id=? and extra=?', 'IPS\Member', 'new_recognize', $this->member_id, '[' . $this->id . ']' ) );
		}
		catch( Exception ) {}

		parent::delete();
	}
}