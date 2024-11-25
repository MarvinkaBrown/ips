<?php
/**
 * @brief		User subscription model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Feb 2018
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\Member;
use IPS\nexus\Subscription\Package;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * User subscription model
 */
class Subscription extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_member_subscriptions';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'sub_';

	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'sub_purchase_id' );

	/**
	 * Get the DateTime object for when this little subscription doth run out
	 *
	 * @return	DateTime|NULL
	 */
	public function get__expire() : DateTime|null
	{
		if ( $this->expire )
		{
			if ( $this->purchase_id )
			{
				/* This has been invoiced, so there is a purchase row - fetch the expiration from that */
				try
				{
					$purchase = Purchase::load( $this->purchase_id );
					
					if ( $purchase->expire )
					{
						return $purchase->expire;
					} 
				}
				catch( OutOfRangeException ) { }
			}
			
			return DateTime::ts( $this->expire );
		}
		
		return NULL;
	}

	/**
	 * Get the package (ooh, very mysterious, sounds like something from Narcos)
	 *
	 * @return	Package|null
	 */
	public function get_package() : Package|null
	{
		try
		{
			return Package::load( $this->package_id );
		}
		catch( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * Get the purchase
	 *
	 * @return	Purchase|null
	 */
	public function get_purchase() : Purchase|null
	{
		try
		{
			return Purchase::load( $this->purchase_id );
		}
		catch( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * Change the subscription package
	 *
	 * @param	Package		$package		The new package innit
	 * @param	DateTime|NULL					$expires		The new expiration date
	 * @return void
	 */
	public function changePackage( Package $package, ?DateTime $expires=NULL ) : void
	{
		$this->package_id = $package->id;
		$this->expire     = ( $expires === NULL ) ? 0 : $expires->getTimeStamp();
		$this->renews     = ! empty( $package->renew_options ) ? 1 : 0;
		$this->save();
	}
	
	/**
	 * Get a nice blurb explaining about the current subscription. If you want, up to you.
	 *
	 * @return string
	 */
	public function currentBlurb() : string
	{
		if ( !$this->active AND !$this->purchase->cancelled )
		{
			return Member::loggedIn()->language()->addToStack( 'nexus_subs_subscribed_expired' );
		}
		elseif( $this->purchase->cancelled AND $this->purchase->can_reactivate )
		{
			return Member::loggedIn()->language()->addToStack( 'nexus_subs_subscribed_cancelled' );
		}
		elseif( $this->purchase->cancelled )
		{
			return Member::loggedIn()->language()->addToStack( 'nexus_subs_subscribed_cancelled_no_reactivate' );
		}
		if ( $this->expire and $this->renews and !$this->manually_added )
		{
			return Member::loggedIn()->language()->addToStack( 'nexus_subs_subscribed_with_expire' . ( ( $this->purchase and $this->purchase->renewals ) ? '' : '_no_renewal' ), NULL, array( 'sprintf' => array( $this->_expire->dayAndMonth() . ' ' . $this->_expire->format('Y') ) ) );
		}
		
		return Member::loggedIn()->language()->addToStack( 'nexus_subs_subscribed' );
	}
	
	/**
	 * Find and return the package this person is currently subscribed to, or NULL
	 *
	 * @param	Member					$member			The member
	 * @param	bool						$activeOnly		If TRUE, returns only active subscription
	 * @return	static|ActiveRecord|null
	 */
	public static function loadByMember( Member $member, bool $activeOnly ) : static|ActiveRecord|null
	{		
		try
		{
			$where = [];
			$where[] = [ 'sub_member_id=?', $member->member_id ];
			if ( $activeOnly )
			{
				$where[] = [ 'sub_active=1' ];
			}
			
			return static::constructFromData( Db::i()->select( '*', 'nexus_member_subscriptions', $where, 'sub_active DESC, sub_start DESC' )->first() );
		}
		catch( Exception )
		{
			return NULL;
		}
	}
	
	/**
	 * Find and return the package this person is currently subscribed to, or NULL
	 *
	 * @param		Member|NULL	$member		Pass a member in if you like, no pressure really up to you
	 * @return        static|null
	 * @deprecated	Use loadByMember() instead
	 */
	public static function loadActiveByMember( ?Member $member=NULL ) : static|null
	{
		return static::loadByMember( $member ?: Member::loggedIn(), TRUE );
	}

	/**
	 * Load a subscription by member and package
	 *
	 * @param	Member							$member		Take a guess
	 * @param	Package		$package	I mean it's really writing itself
	 * @param	boolean								$activeOnly		Only get active packages
	 * @return    static|ActiveRecord
	 * @throws OutOfRangeException
	 */
	public static function loadByMemberAndPackage( Member $member, Package $package, bool $activeOnly = TRUE ) : static|ActiveRecord
	{
		try
		{
			$where = array( array( 'sub_package_id=? and sub_member_id=?', $package->id, $member->member_id ) );
			
			if ( $activeOnly === TRUE )
			{
				$where[] = array( 'sub_active=1' );
			}

			return static::constructFromData( Db::i()->select( '*', 'nexus_member_subscriptions', $where )->first() );
		}
		catch( Exception )
		{
			throw new OutOfRangeException;
		}
	}
	
	/**
	 * Mark all subscriptions by this member as inactive
	 *
	 * @param	Member		$member		I dunno, take a guess
	 * @return	void
	 */
	public static function markInactiveByUser( Member $member ) : void
	{
		Db::i()->update( 'nexus_member_subscriptions', array( 'sub_active' => 0 ), array( 'sub_member_id=?', $member->member_id ) );
	}
}