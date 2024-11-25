<?php
/**
 * @brief		File
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		05 Aug 2014
 */

namespace IPS\downloads\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\downloads\File as DownloadsFile;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File
 */
class File extends \IPS\nexus\Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 *
	 */
	public static string $application = 'downloads';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'file';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'download';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'file';
	
	/**
	 * Image
	 *
	 * @return \IPS\File|null
	 */
	public function image(): \IPS\File|null
	{
		try
		{
			return DownloadsFile::load( $this->id )->primary_screenshot;
		}
		catch ( Exception  )
		{
			return NULL;
		}
	}

	/**
	 * Image
	 *
	 * @param Purchase $purchase The purchase
	 * @return \IPS\File|null
	 */
	public static function purchaseImage( Purchase $purchase ): \IPS\File|null
	{
		try
		{			
			return DownloadsFile::load( $purchase->item_id )->primary_screenshot;
		}
		catch ( Exception  )
		{
			return NULL;
		}
	}

	/**
	 * Client Area Action
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function clientAreaAction( Purchase $purchase ): void
	{
		if( Request::i()->act == 'reactivate' AND $purchase->can_reactivate )
		{
			/* Cannot renew, do not set renewal periods */
			$file = DownloadsFile::load( $purchase->item_id );
			if( !$file->container()->can( 'download', $purchase->member ) )
			{
				parent::clientAreaAction( $purchase );
			}

			try
			{
				$file = DownloadsFile::load( $purchase->item_id );
			}
			catch ( OutOfRangeException  )
			{
				parent::clientAreaAction( $purchase );
			}
			$renewalCosts = json_decode( $file->renewal_price, TRUE );

			/* If invoice doesn't exist, or currency no longer exists, use default */
			try
			{
				$currency = $purchase->original_invoice->currency;
				if( !isset( $renewalCosts[ $currency ] ) )
				{
					throw new OutOfRangeException;
				}
			}
			catch( OutOfRangeException  )
			{
				$currency = Customer::loggedIn()->defaultCurrency();
			}

			$tax = NULL;
			if ( $purchase->tax )
			{
				try
				{
					$tax = Tax::load( $purchase->tax );
				}
				catch ( Exception  ) { }
			}

			Session::i()->csrfCheck();

			$purchase->renewals = new RenewalTerm( new Money( $renewalCosts[ $currency ]['amount'], $currency ), new DateInterval( 'P' . $file->renewal_term . mb_strtoupper( $file->renewal_units ) ), $tax );
			$purchase->cancelled = FALSE;
			$purchase->save();

			$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'change_renewals', 'to' => array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm() ) ) );

			if ( !$purchase->active and $cycles = $purchase->canRenewUntil( NULL, TRUE ) AND $cycles !== FALSE )
			{
				$url = $cycles === 1 ? $purchase->url()->setQueryString( 'do', 'renew' )->csrf() : $purchase->url()->setQueryString( 'do', 'renew' );
				Output::i()->redirect( $url );
			}
			else
			{
				Output::i()->redirect( $purchase->url() );
			}
		}
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array    array( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public static function clientAreaPage( Purchase $purchase ): array
	{
		try
		{
			$file = DownloadsFile::load( $purchase->item_id );

			/* Reactivate */
			$reactivateUrl = NULL;
			if ( $file->container()->can( 'download', $purchase->member ) and !$purchase->renewals and $file->renewal_term and $file->renewal_units and $file->renewal_price and $purchase->can_reactivate and ( !$purchase->billing_agreement or $purchase->billing_agreement->canceled ) )
			{
				$reactivateUrl = Url::internal( "app=nexus&module=clients&controller=purchases&id={$purchase->id}&do=extra&act=reactivate", 'front', 'clientspurchaseextra', Url::seoTitle( $purchase->name ) )->csrf();
			}
			
			return array( 'packageInfo' => Theme::i()->getTemplate( 'nexus', 'downloads' )->fileInfo( $file ), 'purchaseInfo' => Theme::i()->getTemplate( 'nexus', 'downloads' )->filePurchaseInfo( $file, $reactivateUrl ) );
		}
		catch ( OutOfRangeException  ) { }
		
		return [];
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @param Purchase $purchase
	 * @return    string
	 */
	public static function acpPage( Purchase $purchase ): string
	{
		try
		{
			$file = DownloadsFile::load( $purchase->item_id );
			return (string) Theme::i()->getTemplate( 'nexus', 'downloads' )->fileInfo( $file );
		}
		catch ( OutOfRangeException  ) { }
		
		return "";
	}

	/**
	 * Generate Invoice Form
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function form( Form $form, Invoice $invoice ) : void
	{
		$form->add( new Form\Item( 'downloads_file', null, true, [
			'class' => DownloadsFile::class,
			'maxItems' => 1,
			'where' => [
				[ 'file_cost is not null' ],
				[ 'file_open=?', 1 ],
				[ 'file_submitter != ?', $invoice->member->member_id ]
			],
			'itemTemplate' => [ Theme::i()->getTemplate( 'nexus', 'downloads' ), 'itemResultTemplate' ]
		] ) );
	}

	/**
	 * Create From Form
	 *
	 * @param	array				$values	Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return	static
	 */
	public static function createFromForm( array $values, Invoice $invoice ): static
	{
		$file = array_shift( $values['downloads_file'] );
		$price = $file->price( $invoice->member );
		$item = new static( $file->name, $price );
		$item->id = $file->id;

		try
		{
			$item->tax = Settings::i()->item_nexus_tas ? Tax::load( Settings::i()->idm_nexus_tax ) : null;
		}
		catch( OutOfRangeException ){}

		if ( Settings::i()->idm_nexus_gateways )
		{
			$item->paymentMethodIds = explode( ',', Settings::i()->idm_nexus_gateways );
		}

		$item->renewalTerm = $file->renewalTerm( $invoice->member );
		$item->payTo = $file->author();
		$item->commission = Settings::i()->idm_nexus_percent;
		if ( $fees = json_decode( Settings::i()->idm_nexus_transfee, TRUE ) and isset( $fees[ $price->currency ] ) )
		{
			$item->fee = new Money( $fees[ $price->currency ]['amount'], $price->currency );
		}

		return $item;
	}
	
	/**
	 * URL
	 *
	 * @return Url|string|null
	 */
	function url(): Url|string|null
	{
		try
		{
			return DownloadsFile::load( $this->id )->url();
		}
		catch ( OutOfRangeException  )
		{
			return NULL;
		}
	}
	
	/**
	 * ACP URL
	 *
	 * @return Url|null
	 */
	public function acpUrl(): Url|null
	{
		return $this->url();
	}
	
	/** 
	 * Get renewal payment methods IDs
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array|NULL
	 */
	public static function renewalPaymentMethodIds( Purchase $purchase ): array|null
	{
		if ( Settings::i()->idm_nexus_gateways )
		{
			return explode( ',', Settings::i()->idm_nexus_gateways );
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
			$file = DownloadsFile::load( $purchase->item_id );

			/* File is viewable and basic download permission check is good */
			if( $file->canView( $purchase->member ) AND $file->container()->can( 'download', $purchase->member ) )
			{
				return TRUE;
			}
		}
		catch ( OutOfRangeException  ) {}

		return FALSE;
	}

	/**
	 * Can Renew Until
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	bool					$admin		If TRUE, is for ACP. If FALSE, is for front-end.
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
}