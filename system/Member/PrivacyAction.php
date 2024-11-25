<?php
/**
 * @brief		Privacy Action
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 March 2023
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\AdminNotification;
use IPS\Db;
use IPS\Email;
use IPS\Login;
use IPS\Member;
use IPS\Notification;
use IPS\Patterns\ActiveRecord;
use IPS\Session;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PrivacyAction Model
 */
class PrivacyAction extends ActiveRecord
{
	/**
	 * @brief    [ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief    [ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_member_privacy_actions';

	/**
	 * @brief    [ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap = [];

	/**
	 * @brief    PII request
	 */
	const TYPE_REQUEST_PII = 'pii_download';

	/**
	 * @brief    Account deletion request
	 */
	const TYPE_REQUEST_DELETE = 'delete_account';

	/**
	 * @brief    Account deletion requires validation
	 */
	const TYPE_REQUEST_DELETE_VALIDATION = 'delete_account_validation';

	/**
	 * Request the PII Data
	 * 
	 * @param Member|null $member
	 * @return void
	 */
	public static function requestPiiData( Member $member = NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		$obj = new static;
		$obj->member_id = $member->member_id;
		$obj->request_date = time();
		$obj->action = static::TYPE_REQUEST_PII;
		$obj->save();

		AdminNotification::send( 'core', 'PiiDataRequest', NULL, TRUE, $member );
	}

	/**
	 * Can the member request his PII Data?
	 * 
	 * @param Member|null $member
	 * @return bool
	 */
	public static function canRequestPiiData( Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		if ( $member->member_id  AND !static::hasPiiRequest($member) )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Can the member download his PII Data?
	 * 
	 * @param Member|null $member
	 * @return bool
	 */
	public static function canDownloadPiiData( Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		if( !$member->member_id )
		{
			return FALSE;
		}
		
		if ( Db::i()->select( 'count(*)', static::$databaseTable, [ 'member_id=? AND action=? AND approved=?', $member->member_id, static::TYPE_REQUEST_PII, 1 ] )->first() > 0 )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Get the deletion request by member and key
	 * 
	 * @param Member $member
	 * @param string $key
	 * @throws OutOfRangeException
	 * @return static
	 */
	public static function getDeletionRequestByMemberAndKey( Member $member, string $key ): static
	{
		try
		{
			$where = [];
			$where[] = ['member_id=?', $member->member_id];
			$where[] = ['vkey=?', $key];
			$where[] = [ Db::i()->in( 'action',[static::TYPE_REQUEST_DELETE, static::TYPE_REQUEST_DELETE_VALIDATION ] )];
			return static::constructFromData( Db::i()->select( '*', static::$databaseTable, $where  )->first() );
		}
		catch( UnderflowException $e )
		{
			throw new OutOfRangeException;
		}

	}

	/**
	 * Is a PII Data Request pending for this member?
	 * 
	 * @param Member|null $member
	 * @param bool $approved
	 * @return bool
	 */
	public static function hasPiiRequest( Member $member = NULL, bool $approved = FALSE ): bool
	{
		$member = $member ?: Member::loggedIn();
		try
		{
			Db::i()->select( '*', static::$databaseTable, [ 'member_id=? AND action=? AND approved=?', $member->member_id, static::TYPE_REQUEST_PII, (int) $approved ] )->first();
			return TRUE;
		}
		catch( UnderflowException $e ){}
		return FALSE;
	}

	/**
	 * Approve the PII Request
	 * 
	 * @return void
	 */
	public function approvePiiRequest() : void
	{
		$this->approved = TRUE;
		$this->save();
		$notification = new Notification( Application::load( 'core' ), 'pii_data', $this, array( $this ) );
		$notification->recipients->attach( $this->member );
		$notification->send();
		static::resetPiiAcpNotifications();
	}

	/**
	 * Reject the PII Data Request
	 * 
	 * @return void
	 */
	public function rejectPiiRequest() : void
	{
		$notification = new Notification( Application::load( 'core' ), 'pii_data_rejected', $this, array( $this ) );
		$notification->recipients->attach( $this->member );
		$notification->send();
		$this->delete();
		static::resetPiiAcpNotifications();
	}

	/**
	 * Get the member object
	 * 
	 * @return Member
	 */
	public function get_member(): Member
	{
		return Member::load( $this->member_id );
	}

	/**
	 * Reset the PII request ACP notifications
	 *
	 * @return void
	 */
	public static function resetPiiAcpNotifications() : void
	{
		if ( !Db::i()->select( 'COUNT(*)', static::$databaseTable, array( 'action=? AND approved=?', static::TYPE_REQUEST_PII, 0 ) )->first() )
		{
			AdminNotification::remove( 'core', 'PiiDataRequest' );
		}
	}

	/**
	 * Reset the account deletion ACP notifications
	 * 
	 * @return void
	 */
	public static function resetDeletionAcpNotifications() : void
	{
		if ( !Db::i()->select( 'COUNT(*)', static::$databaseTable, array( 'action=?', static::TYPE_REQUEST_DELETE ) )->first() )
		{
			AdminNotification::remove( 'core', 'AccountDeletion' );
		}
	}

	/**
	 * Can the current member request account deletion?
	 * 
	 * @param Member|null $member
	 * @return bool
	 */
	public static function canDeleteAccount( Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return Db::i()->select( 'count(*)', static::$databaseTable, [
				[ 'member_id=?', $member->member_id ],
				[ Db::i()->in( 'action', [ static::TYPE_REQUEST_DELETE, static::TYPE_REQUEST_DELETE_VALIDATION ] ) ]
			] )->first() == 0;
	}

	/**
	 * Create and log the account  deletion request
	 * 
	 * @param Member|null $member
	 * @return void
	 */
	public static function requestAccountDeletion( Member $member = NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		$obj = new static;
		$obj->member_id = $member->member_id;
		$obj->request_date = time();
		$obj->action = static::TYPE_REQUEST_DELETE_VALIDATION;
		$vkey = md5( $member->members_pass_hash . Login::generateRandomString() );
		$obj->vkey = $vkey;
		Email::buildFromTemplate( 'core', 'account_deletion_confirmation', array( $member, $vkey ), Email::TYPE_TRANSACTIONAL )->send( $member );

		$obj->save();
	}

	/**
	 * Confirm the account deletion request
	 *
	 * @return void
	 */
	public function confirmAccountDeletion() : void
	{
		$this->action = static::TYPE_REQUEST_DELETE;
		AdminNotification::send( 'core', 'AccountDeletion', NULL, TRUE, $this->member );
		$this->member->logHistory( 'core', 'privacy', array( 'type' => 'account_deletion_requested' ) );
		$this->save();
	}

	/**
	 * Delete the account
	 * 
	 * @return void
	 */
	public function deleteAccount() : void
	{
		/** @var Member $member */
		$member = $this->member;

		$member->delete( TRUE, FALSE );
		static::resetDeletionAcpNotifications();
		Session::i()->log( 'acplog__members_deleted_id', array( $member->name => FALSE, $member->member_id => FALSE ) );
	}

	/**
	 * Reject the account deletion request
	 *
	 * @return void
	 */
	public function rejectDeletionRequest() : void
	{
		$notification = new Notification( Application::load( 'core' ), 'account_del_request_rejected', $this, array( $this ) );
		$notification->recipients->attach( $this->member );
		$notification->send();
		$this->delete();
		static::resetDeletionAcpNotifications();
	}
}
