<?php
/**
 * @brief		Club Membership Fee
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		02 Jan 2018
 */

namespace IPS\core\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application\Module;
use IPS\DateTime;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ClubMembership
 */
class ClubMembership extends Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'club';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'users';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'club_membership_item';
	
	/**
	 * Image
	 *
	 * @return File|null
	 */
	public function image(): File|null
	{
		try
		{
			if ( $photo = Club::load( $this->id )->profile_photo )
			{
				return File::get( 'core_Clubs', $photo );
			}
		}
		catch ( Exception ) {}
		
		return NULL;
	}
		
	/**
	 * Image
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return File|null
	 */
	public static function purchaseImage( Purchase $purchase ): File|null
	{
		try
		{
			if ( $photo = Club::load( $purchase->item_id )->profile_photo )
			{
				return File::get( 'core_Clubs', $photo );
			}
		}
		catch ( Exception ) {}
		
		return NULL;
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array ( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public static function clientAreaPage( Purchase $purchase ): array
	{
		try
		{
			$club = Club::load( $purchase->item_id );
			
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/clubs.css', 'core', 'front' ) );
			return array( 'packageInfo' => Theme::i()->getTemplate( 'clubs', 'core' )->clubClientArea( $club ) );
		}
		catch ( OutOfRangeException ) { }
		
		return array();
	}
	
	/**
	 * Get ACP Page Buttons
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param Url $url		The page URL
	 * @return    array
	 */
	public static function acpButtons(Purchase $purchase, Url $url ): array
	{
		try
		{
			$club = Club::load( $purchase->item_id );
			
			return array( 'view_club' => array(
				'icon'	=> 'users',
				'title'	=> 'view_club',
				'link'	=> $club->url(),
				'target'=> '_blank'
			) );
		}
		catch ( OutOfRangeException ) { }
		
		return array();
	}
		
	/**
	 * URL
	 *
	 * @return Url|string|NULL
	 */
	function url(): Url|string|null
	{
		try
		{
			return Club::load( $this->id )->url();
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * ACP URL
	 *
	 * @return Url|NULL
	 */
	public function acpUrl(): Url|null
	{
		try
		{
			return Club::load( $this->id )->url();
		}
		catch ( OutOfRangeException  )
		{
			return NULL;
		}
	}
	
	/** 
	 * Get renewal payment methods IDs
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array|NULL
	 */
	public static function renewalPaymentMethodIds( Purchase $purchase ): array|null
	{
		if ( Settings::i()->clubs_paid_gateways )
		{
			return explode( ',', Settings::i()->clubs_paid_gateways );
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Purchase can be renewed?
	 *
	 * @param	Purchase $purchase	The purchase
	 * @return    boolean
	 */
	public static function canBeRenewed( Purchase $purchase ): bool
	{
		try
		{
			if ( !$purchase->member->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
			{
				return FALSE;
			}
			
			return in_array( Club::load( $purchase->item_id )->memberStatus( $purchase->member ), array( Club::STATUS_MEMBER, Club::STATUS_MODERATOR, Club::STATUS_LEADER, Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR ) );
		}
		catch ( OutOfRangeException  ) {}

		return FALSE;
	}

	/**
	 * Can Renew Until
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	bool				$admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return	DateTime|bool				TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime means can renew until that date
	 */
	public static function canRenewUntil( Purchase $purchase, bool $admin=FALSE ): DateTime|bool
	{
		if( $admin )
		{
			return TRUE;
		}

		return static::canBeRenewed( $purchase );
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function onPurchaseGenerated( Purchase $purchase, Invoice $invoice ): void
	{
		try
		{
			$club = Club::load( $purchase->item_id );
			$club->addMember( $purchase->member, Club::STATUS_MEMBER, TRUE, NULL, NULL, TRUE );
			$club->recountMembers();

		}
		catch ( Exception  ) {}
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onExpire( Purchase $purchase ): void
	{		
		try
		{
			$club = Club::load( $purchase->item_id );
									
			switch ( $club->memberStatus( $purchase->member ) )
			{
				case $club::STATUS_MEMBER:
					$club->addMember( $purchase->member, Club::STATUS_EXPIRED, TRUE );
					break;
					
				case $club::STATUS_MODERATOR:
					$club->addMember( $purchase->member, Club::STATUS_EXPIRED_MODERATOR, TRUE );
					break;
			}
			$club->recountMembers();
		}
		catch ( Exception  ) { }
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onCancel( Purchase $purchase ): void
	{
		try
		{
			$club = Club::load( $purchase->item_id );
			
			switch ( $club->memberStatus( $purchase->member ) )
			{
				case $club::STATUS_MEMBER:
				case $club::STATUS_MODERATOR:
				case $club::STATUS_EXPIRED:
				case $club::STATUS_EXPIRED_MODERATOR:
					$club->removeMember( $purchase->member );
					break;
			}
		}
		catch ( Exception  ) {}
	}
	
	/**
	 * Warning to display to admin when cancelling a purchase
	 *
	 * @param	Purchase	$purchase	The Purchase
	 * @return    string|null
	 */
	public static function onCancelWarning( Purchase $purchase ): string|null
	{
		return NULL;
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onDelete( Purchase $purchase ): void
	{
		static::onCancel( $purchase );
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onReactivate( Purchase $purchase ): void
	{
		try
		{
			$club = Club::load( $purchase->item_id );
			
			switch ( $club->memberStatus( $purchase->member ) )
			{
				case $club::STATUS_EXPIRED_MODERATOR:
					$club->addMember( $purchase->member, Club::STATUS_MODERATOR, TRUE );
					break;
					
				default:
					$club->addMember( $purchase->member, Club::STATUS_MEMBER, TRUE );
					break;
			}
			$club->recountMembers();
		}
		catch ( Exception  ) {}
	}
	
	/**
	 * On Transfer (is ran before transferring)
	 *
	 * @param	Purchase	$purchase		The purchase
	 * @param	Member			$newCustomer	New Customer
	 * @return    void
	 */
	public static function onTransfer( Purchase $purchase, Member $newCustomer ): void
	{
		try
		{
			$club = Club::load( $purchase->item_id );
		}
		catch ( OutOfRangeException  )
		{
			return;
		}
									
		switch ( $club->memberStatus( $newCustomer ) )
		{
			/* If they are already a member, we can't really transfer a different membership to them... */
			case Club::STATUS_MEMBER:
			case Club::STATUS_INVITED_BYPASSING_PAYMENT:
			case Club::STATUS_EXPIRED:
			case Club::STATUS_EXPIRED_MODERATOR:
			case Club::STATUS_MODERATOR:
			case Club::STATUS_LEADER:
				throw new DomainException( 'club_cannot_transfer_membership' );
			
			/* Buf if they're *not* a member we can */
			case NULL:
			case Club::STATUS_INVITED:
			case Club::STATUS_REQUESTED:
			case Club::STATUS_WAITING_PAYMENT:
			case Club::STATUS_DECLINED:
			case Club::STATUS_BANNED:
				
				/* Remove the old member's record */
				$club->removeMember( $purchase->member );
				
				/* Now if the purchase isn't cancelled... */
				if ( !$purchase->cancelled )
				{
					/* Remove any request/invitation for the new member and add the new record */
					$club->removeMember( $newCustomer );
					$club->addMember( $newCustomer, $purchase->active ? Club::STATUS_MEMBER : Club::STATUS_EXPIRED, FALSE, Member::loggedIn() );
				}
				
				/* Recount */
				$club->recountMembers();
		}
	}
	
	/**
	 * Generate Invoice Form
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function form( Form $form, Invoice $invoice ): void
	{
		$form->add( new FormUrl( 'url_to_club', NULL, TRUE, array(), function($value )
		{
			try
			{
				$club = Club::loadFromUrl( $value );
			}
			catch ( Exception  )
			{
				throw new DomainException('url_to_club_invalid');
			}
			if ( !$club->isPaid() )
			{
				throw new DomainException('url_to_club_free');
			}			
		} ) );
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values	Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return    ClubMembership
	 */
	public static function createFromForm( array $values, Invoice $invoice ): ClubMembership
	{
		$club = Club::loadFromUrl( $values['url_to_club'] );
		
		$fee = $club->joiningFee( $invoice->currency );
		
		$item = new ClubMembership( $club->name, $fee );
		$item->id = $club->id;
		try
		{
			$item->tax = Settings::i()->clubs_paid_tax ? Tax::load( Settings::i()->clubs_paid_tax ) : NULL;
		}
		catch ( OutOfRangeException  ) { }
		if ( Settings::i()->clubs_paid_gateways )
		{
			$item->paymentMethodIds = explode( ',', Settings::i()->clubs_paid_gateways );
		}
		$item->renewalTerm = $club->renewalTerm( $fee->currency );
		$item->payTo = $club->owner;
		$item->commission = Settings::i()->clubs_paid_commission;
		if ( $fees = Settings::i()->clubs_paid_transfee and isset( $fees[ $fee->currency ] ) )
		{
			$item->fee = new Money( $fees[ $fee->currency ]['amount'], $fee->currency );
		}
		
		return $item;
	}
}