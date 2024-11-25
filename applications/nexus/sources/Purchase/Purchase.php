<?php
/**
 * @brief		Purchase Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use ErrorException;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Events\Event;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Customer\BillingAgreement;
use IPS\nexus\Invoice\Item\Purchase as ItemPurchase;
use IPS\nexus\Package\CustomField;
use IPS\nexus\Purchase\LicenseKey;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use UnexpectedValueException;
use function call_user_func_array;
use function count;
use function defined;
use function intval;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Purchase Model
 * @method onExpirationDateChange() : void
 * @method onExpireWarning() : bool
 * @method onRenew(int $cycles = 1): void
 * @method onTransfer( Member $newCustomer ) : void
 * @method onCancelWarning() : string|null
 * @property Customer $member
 */
class Purchase extends Model
{
	/**
	 * Tree
	 *
	 * @param	Url					$url			URL
	 * @param	array							$where			Where clause
	 * @param	string							$ref			Referer
	 * @param Purchase|NULL		$root			Root (NULL for all root purchases)
	 * @param	bool							$includeRoot	Show the root?
	 * @return	Tree
	 */
	public static function tree(Url $url, array $where, string $ref = 'c', ?Purchase $root = NULL, bool $includeRoot = TRUE ) : Tree
	{		
		$where[] = array( 'ps_show=1' );
				
		return new Tree(
			$url,
			'Purchases',
			function( $limit ) use ( $url, $where, $ref, $root, $includeRoot )
			{
				if ( $root )
				{
					if ( $includeRoot )
					{
						$where[] = array( 'ps_id=?', $root->id );
					}
					else
					{
						$where[] = array( 'ps_parent=?', $root->id );
					}
				}
				else
				{
					$where[] = array( 'ps_parent=0' );
				}
				
				$rows = array();				
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', $where, 'ps_start DESC', $limit ), 'IPS\nexus\Purchase' ) as $purchase )
				{
					/* @var Purchase $purchase */
					$rows[ $purchase->id ] = $purchase->treeRow( $url, $ref );
				}
				return $rows;
			},
			function( $id ) use ( $url, $ref )
			{
				return Purchase::load( $id )->treeRow( $url, $ref );
			},
			function( $id )
			{
				return Purchase::load( $id )->parent();
			},
			function( $id ) use ( $url, $ref, $where )
			{
				$rows = array();
				foreach (Purchase::load( $id )->children( NULL, NULL, TRUE, NULL, $where ) as $child )
				{
					$rows[ $child->id ] = $child->treeRow( $url, $ref );
				}
				return $rows;
			},
			NULL,
			FALSE,
			FALSE,
			FALSE,
			NULL,
			function()
			{
				$where[] = array( 'ps_parent=0' );
				return Db::i()->select( 'COUNT(*)', 'nexus_purchases', $where )->first();
			}
		);
	}
	
	/**
	 * Tree Row
	 *
	 * @param Url $url	URL
	 * @param string $ref	Referer
	 * @return    string
	 */
	public function treeRow( Url $url, string $ref ): string
	{				
		$childCount = $this->childrenCount( NULL, NULL, TRUE, array( array( 'ps_show=1' ) ) );
		
		$hasCustomFields = FALSE;
		foreach ( $this->custom_fields as $k => $v )
		{
			try
			{
				if ( trim( CustomField::load( $k )->displayValue( $v, TRUE ) ) )
				{
					$hasCustomFields = TRUE;
					break;
				}
			}
			catch( OutOfRangeException ) {}
		}

		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			$description = $extension::getPurchaseNameInfo( $this );

			if( empty( $description ) )
			{
				$description[] = Member::loggedIn()->language()->addToStack( 'purchase_number', FALSE, array( 'sprintf' => array( $this->id ) ) );
			}
		}
		catch ( OutOfRangeException )
		{
			$description = array();
		}
		if ( $this->grouped_renewals )
		{
			$description[] = Member::loggedIn()->language()->addToStack('purchase_grouped');
		}
		elseif ( $this->renewals )
		{
			$renewals = (string) $this->renewals;
			if ( $this->renewals->tax )
			{
				$renewals .= Member::loggedIn()->language()->addToStack( 'plus_tax_rate', FALSE, array( 'sprintf' => array( $this->renewals->tax->_title ) ) );
			}
			$description[] = $renewals;
		}

		$description = implode( ' &middot; ', $description );

		return Theme::i()->getTemplate( 'trees', 'core' )->row(
			$url,
			$this->id,
			( $hasCustomFields and !$childCount ) ? Theme::i()->getTemplate( 'purchases', 'nexus' )->link( $this, FALSE, FALSE, TRUE ) : $this->_name,
			$childCount,
			array_merge( array(
				'view'	=> array(
					'link'	=> $this->acpUrl()->setQueryString( 'popup', true ),
					'title'	=> 'view',
					'icon'	=> 'search',
				)
			), $this->buttons( $ref ) ),
			$description,
			$this->getIcon(),
			NULL,
			$this->id == Request::i()->root,
			NULL,
			NULL,
			!$this->active ? ( $this->cancelled ? array( 'style5', 'purchase_canceled' ) : array( 'style6', 'purchase_expired' ) ) : NULL,
			$hasCustomFields
		);
	}
	
	/* ActiveRecord */
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_purchases';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'ps_';
	
	/* !Node */
	
	/**
	 * @brief	Node Title
	 */
	public static string $nodeTitle = 'purchases';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent';
	
	/**
	 * @brief	[Node] If the node can be "owned", the owner "type" (typically "member" or "group") and the associated database column
	 */
	public static ?array $ownerTypes = array(
		'member'	=> 'member'
	);
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function get__title(): string
	{
		return $this->name;
	}
	
	/* !Columns */
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->_data['active'] = TRUE; // do directly so it doesn't call onReactivate
		$this->start = new DateTime;
		$this->renewal_price = 0;
		$this->renewal_currency = '';
		$this->invoice_pending = NULL;
	}
	
	/**
	 * @brief	Name with sticky fields
	 */
	protected string $nameWithStickyFields = '';
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function get_name() : string
	{
		if ( !$this->nameWithStickyFields )
		{
			$this->nameWithStickyFields = $this->_data['name'];
			try
			{
				/* @var ItemPurchase $extension */
				$extension = $this->extension();
				$info = $extension::getPurchaseNameInfo( $this );
				if ( count( $info ) )
				{
					$this->nameWithStickyFields .= ' (' . implode( ' &middot; ', $info ) . ')';
				}
			}
			catch ( OutOfRangeException ) { }
		}
		return $this->nameWithStickyFields;
	}
	
	/**
	 * Get name without sticky fields
	 *
	 * @return	string
	 */
	public function get__name() : string
	{
		return $this->_data['name'];
	}
	
	/**
	 * Get member
	 *
	 * @return	Member
	 */
	public function get_member() : Member
	{
		return Customer::load( $this->_data['member'] );
	}
	
	/**
	 * Set member
	 *
	 * @param	Member $member
	 * @return	void
	 */
	public function set_member( Member $member ) : void
	{
		$this->_data['member'] = $member->member_id;
	}
	
	/**
	 * Set active
	 *
	 * @param	bool	$active	Is active?
	 * @return	void
	 */
	public function set_active( bool $active ) : void
	{	
		if ( $this->id )
		{
			if ( $this->_data['active'] and !$active )
			{
				$this->_data['active'] = FALSE;
				$this->onExpire();
			}
			elseif ( !$this->_data['active'] and $active )
			{
				$this->_data['active'] = TRUE;
				$this->onReactivate();
			}
		}
		
		$this->_data['active'] = $active;
	}
	
	/**
	 * Set cancelled
	 *
	 * @param	bool	$cancelled	Is cancelled?
	 * @return	void
	 */
	public function set_cancelled( bool $cancelled ) : void
	{
		if ( $cancelled )
		{
			$this->_data['active'] = FALSE; // We call directly so onExpire doesn't run (as onCancel will run)
			$this->onCancel();
		}
		else
		{
			if ( !$this->expire or $this->expire->getTimestamp() > time() )
			{
				$this->active = TRUE;
				if ( $this->_data['cancelled'] )
				{
					$this->onReactivate();
				}
			}
		}
		
		$this->_data['cancelled'] = $cancelled;
	}
	
	/**
	 * Get start date
	 *
	 * @return	DateTime
	 */
	public function get_start() : DateTime
	{
		return DateTime::ts( $this->_data['start'] );
	}
	
	/**
	 * Set start date
	 *
	 * @param	DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_start( DateTime $date ) : void
	{
		$this->_data['start'] = $date->getTimestamp();
	}
	
	/**
	 * Get expire date
	 *
	 * @return	DateTime|NULL
	 */
	public function get_expire() : DateTime|null
	{
		return ( isset( $this->_data['expire'] ) and $this->_data['expire'] ) ? DateTime::ts( $this->_data['expire'] ) : NULL;
	}
	
	/**
	 * Set expire date
	 *
	 * @param	DateTime|NULL	$date	The invoice date
	 * @return	void
	 */
	public function set_expire( ?DateTime $date = NULL ) : void
	{
		if ( $date === NULL )
		{
			$this->_data['expire'] = 0;
			$this->active = !$this->cancelled;
		}
		else
		{	
			$this->_data['expire'] = $date->getTimestamp();
			$this->active = ( !$this->cancelled and $date->add( new DateInterval( 'PT' . intval( $this->grace_period ) . 'S' ) )->getTimestamp() > time() );
		}
		if ( $this->id )
		{
			$this->onExpirationDateChange();
		}
	}
	
	/**
	 * Get renewal term
	 *
	 * @return	RenewalTerm|NULL
	 */
	public function get_renewals() : RenewalTerm|null
	{
		if( isset( $this->_data['renewals'] ) and $this->_data['renewals'] )
		{
			$tax = NULL;
			if ( $this->_data['tax'] )
			{
				try
				{
					$tax = Tax::load( $this->_data['tax'] );
				}
				catch ( Exception ) { }
			}
			
			return new RenewalTerm( new Money( $this->_data['renewal_price'], $this->_data['renewal_currency'] ), new DateInterval( 'P' . $this->_data['renewals'] . mb_strtoupper( $this->_data['renewal_unit'] ) ), $tax );
		}
		return NULL;
	}
	
	/**
	 * Set renewal term
	 *
	 * @param	RenewalTerm|NULL	$term	The renewal term
	 * @return	void
	 */
	public function set_renewals( ?RenewalTerm $term = NULL ) : void
	{
		if ( $term === NULL OR $term->cost->amount->isZero() )
		{
			$this->_data['renewals'] = 0;
		}
		else
		{
			$data = $term->getTerm();
			$this->_data['renewals'] = $data['term'];
			$this->_data['renewal_unit'] = $data['unit'];
			$this->_data['renewal_price'] = $term->cost->amount;
			$this->_data['renewal_currency'] = $term->cost->currency;
			$this->_data['tax'] = $term->tax ? $term->tax->id : 0;
		}
	}
	
	/**
	 * Get custom fields
	 *
	 * @return	array
	 */
	public function get_custom_fields() : array
	{
		return json_decode( $this->_data['custom_fields'], TRUE ) ?: array();
	}
	
	/**
	 * Set custom fields
	 *
	 * @param	array|null	$customFields	The data
	 * @return	void
	 */
	public function set_custom_fields( ?array $customFields ) : void
	{
		$this->_data['custom_fields'] = json_encode( $customFields );
	}
	
	/**
	 * Get extra information
	 *
	 * @return	array
	 */
	public function get_extra() : array
	{
		return json_decode( $this->_data['extra'], TRUE ) ?: array();
	}
	
	/**
	 * Set extra information
	 *
	 * @param	array|null	$extra	The data
	 * @return	void
	 */
	public function set_extra( ?array $extra ) : void
	{
		$this->_data['extra'] = json_encode( $extra );
	}
		
	/**
	 * Set parent purchase
	 *
	 * @param Purchase|null $purchase
	 * @return	void
	 */
	public function set_parent( ?Purchase $purchase = NULL ) : void
	{
		$this->_data['parent'] = $purchase ? $purchase->id : 0;
	}
	
	/**
	 * Get pending invoice
	 *
	 * @return    Invoice|null
	 */
	public function get_invoice_pending() : Invoice|null
	{
		try
		{
			return $this->_data['invoice_pending'] ? Invoice::load( $this->_data['invoice_pending'] ) : NULL;
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * Set pending invoice
	 *
	 * @param Invoice|null $invoice
	 * @return	void
	 */
	public function set_invoice_pending( ?Invoice $invoice = NULL ) : void
	{
		$this->_data['invoice_pending'] = $invoice ? $invoice->id : 0;
		
		if ( !$invoice )
		{
			$this->_data['invoice_warning_sent'] = FALSE;
		}
	}
	
	/**
	 * Get member to receive payments
	 *
	 * @return	Member|null
	 */
	public function get_pay_to() : Member|null
	{
		try
		{
			return $this->_data['pay_to'] ? Customer::load( $this->_data['pay_to'] ) : NULL;
		}
		catch ( Exception )
		{
			return NULL;
		}
	}
	
	/**
	 * Set member to receive payments
	 *
	 * @param	Member $member
	 * @return	void
	 */
	public function set_pay_to( Member $member ) : void
	{
		$this->_data['pay_to'] = $member->member_id;
	}
	
	/**
	 * Get fee to commission on renewal charges
	 *
	 * @return    Money|NULL
	 */
	public function get_fee() : Money|null
	{
		if ( $this->_data['fee'] and $this->renewals )
		{
			try
			{
				return new Money( $this->_data['fee'], $this->renewals->cost->currency );
			}
			catch ( Exception ) { }
		}
		return NULL;
	}
	
	/**
	 * Set fee to commission on renewal charges
	 *
	 * @param Money|NULL	$fee	The fee
	 * @return	void
	 */
	public function set_fee( ?Money $fee = NULL ) : void
	{
		$this->_data['fee'] = $fee?->amount;
	}
	
	/**
	 * Get original invoice
	 *
	 * @return    Invoice
	 */
	public function get_original_invoice() : Invoice
	{
		return Invoice::load( $this->_data['original_invoice'] );
	}
	
	/**
	 * Set original invoice
	 *
	 * @param Invoice $invoice
	 * @return	void
	 */
	public function set_original_invoice( Invoice $invoice ) : void
	{
		$this->_data['original_invoice'] = $invoice->id;
	}
	
	/**
	 * Get grouped renewals information
	 *
	 * @return	array|null
	 */
	public function get_grouped_renewals() : array|null
	{
		return $this->_data['grouped_renewals'] ? json_decode( $this->_data['grouped_renewals'], TRUE ) : NULL;
	}
	
	/**
	 * Set grouped renewals information
	 *
	 * @param	array|NULL	$data	The data
	 * @return	void
	 */
	public function set_grouped_renewals( ?array $data ) : void
	{
		$this->_data['grouped_renewals'] = !is_null( $data ) ? json_encode( $data ) : NULL;
	}
	
	/**
	 * Get billing agreement
	 *
	 * @return	BillingAgreement|NULL
	 */
	public function get_billing_agreement() : BillingAgreement|null
	{
		if ( $this->_data['billing_agreement'] )
		{
			try
			{
				return BillingAgreement::load( $this->_data['billing_agreement'] );
			}
			catch ( OutOfRangeException )
			{
				$this->_data['billing_agreement'] = NULL;
			}
		}
		return NULL;
	}
	
	/**
	 * Set billing agreement
	 *
	 * @param	BillingAgreement|NULL	$billingAgreement	The billing agreement
	 * @return	void
	 */
	public function set_billing_agreement( ?BillingAgreement $billingAgreement = NULL ) : void
	{
		$this->_data['billing_agreement'] = $billingAgreement?->id;
	}
	
	/* !Properties and syncing */
	
	/**
	 * @brief	Extension
	 */
	protected ?string $extension = null;
	
	/** 
	 * Get extension
	 *
	 * @return    string
	 * @throws	OutOfRangeException
	 */
	protected function extension() : string
	{
		if ( $this->extension === NULL )
		{
			/* Load the app. \IPS\Application::load() throws UnexpectedValueException 
				if the Application.php does not exist, which we'll standardize to
				OutOfRangeException so areas calling this method only need to check
				for that */
			try
			{
				$app = Application::load( $this->app );
			}
			catch ( UnexpectedValueException )
			{
				throw new OutOfRangeException;
			}
			
			/* Find the extension */
			foreach ( $app->extensions( 'nexus', 'Item', FALSE ) as $ext )
			{
				if ( $ext::$type == $this->type )
				{
					$this->extension = $ext;
					break;
				}
			}
			
			/* Don't have it? */
			if ( !$this->extension )
			{
				throw new OutOfRangeException;
			}
		}
		return $this->extension;
	}
	
	/** 
	 * Get icon
	 *
	 * @return	string
	 */
	public function getIcon() : string
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::getIcon( $this );
		}
		catch ( OutOfRangeException )
		{
			return 'question';
		}
	}
	
	/** 
	 * Get icon
	 *
	 * @return	string|null
	 */
	public function getTypeTitle() : string|null
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::getTypeTitle( $this );
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/** 
	 * Get image
	 *
	 * @return    File|null
	 */
	public function image() : File|null
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::purchaseImage( $this );
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/** 
	 * Get ACP Page HTML
	 *
	 * @return    string
	 */
	public function acpPage(): string
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::acpPage( $this );
		}
		catch ( OutOfRangeException | BadMethodCallException )
		{
			return '';
		}
	}
	
	/** 
	 * ACP Edit Form
	 *
	 * @param	Form				$form		The form
	 * @param RenewalTerm|null $renewals	The renewal term
	 * @return    void
	 */
	public function acpEdit(Form $form, ?RenewalTerm $renewals ): void
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			$extension::acpEdit( $this, $form, $renewals );
		}
		catch ( OutOfRangeException ){}
	}
	
	/** 
	 * ACP Edit Save
	 *
	 * @param	array	$values		Values from form
	 * @return    void
	 */
	public function acpEditSave( array $values ): void
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			$extension::acpEditSave( $this, $values );
		}
		catch ( OutOfRangeException ){}
	}
	
	/** 
	 * Get Client Area Page HTML
	 *
	 * @return    array
	 */
	public function clientAreaPage(): array
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::clientAreaPage( $this );
		}
		catch ( OutOfRangeException | BadMethodCallException | ErrorException )
		{
			return array( 'packageInfo' => '', 'purchaseInfo' => '' );
		}
	}
	
	/** 
	 * Perform ACP Action
	 *
	 * @return    string|null
	 */
	public function acpAction(): string|null
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::acpAction( $this );
		}
		catch ( OutOfRangeException  )
		{
			return null;
		}
	}
	
	/** 
	 * Perform Client Area Action
	 *
	 * @return    void
	 */
	public function clientAreaAction(): void
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			$extension::clientAreaAction( $this );
		}
		catch ( OutOfRangeException  ){}
	}
	
	/** 
	 * Get renewal payment methods IDs
	 *
	 * @return    array|NULL
	 */
	public function renewalPaymentMethodIds(): array|null
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::renewalPaymentMethodIds( $this );
		}
		catch ( OutOfRangeException  )
		{
			return NULL;
		}
	}
			
	/**
	 * Call on*
	 *
	 * @param string $method	Method
	 * @param array $params	Params
	 * @return    mixed
	 */
	public function __call(string $method, array $params ): mixed
	{
		if ( mb_substr( $method, 0, 2 ) === 'on' )
		{
			try
			{
				$result = call_user_func_array( array( $this->extension(), $method ), array_merge( array( $this ), $params ) );

				/* Fire the event, but do it on the original invoice item, not on the purchase itself.
				This way we support all possible invoice items. */

				$price = new Money( "0", $this->member->defaultCurrency() );
				if( $this->renewals instanceof RenewalTerm )
				{
					$price = $this->renewals->cost;
				}
				$item = new $this->extension( $this->name, $price );
				Event::fire( $method, $item, array_merge( array( $this ), $params ) );

				return $result;
			}
			catch ( OutOfRangeException  ) { }
		}
		throw new BadMethodCallException;
	}
	
	/**
	 * Purchase can be reactivated in the ACP?
	 *
	 * @param string|NULL $error		Error to show, passed by reference
	 * @return    bool
	 */
	public function canAcpReactivate( ?string &$error=NULL ): bool
	{
		/* @var ItemPurchase $extension */
		$extension = $this->extension();
		return $extension::canAcpReactivate( $this, $error );
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse		int									id				ID number
	 * @apiresponse		string								name			Name
	 * @apiresponse		string								itemApp			Key for application. For example, 'nexus' for products and renewals; 'downloads' for Downloads files
	 * @apiresponse		string								itemType		Key for item type. For example, 'package' for products; 'file' for Downloads files.
	 * @apiresponse		int									itemId			The ID for the item. For example, the product ID or the file ID.
	 * @apiresponse		\IPS\nexus\Customer					customer		Customer
	 * @apiresponse		datetime							purchased		Purchased date
	 * @apiresponse		datetime							expires			Expiration date
	 * @apiresponse		bool								active			If purchase is currently active (not expired)
	 * @apiresponse		bool								canceled		If purchase has been canceled
	 * @apiresponse		\IPS\nexus\Purchase\RenewalTerm		renewalTerm		Renewal term
	 * @apiresponse		object								customFields	Values for custom fields
	 * @apiresponse		\IPS\nexus\Purchase					parent			Parent purchase
	 * @apiresponse		bool								show			If this purchase shows in the client area and AdminCP
	 * @apiresponse		string								licenseKey		License key
	 * @apiresponse		string								image			If the item has a relevant image (for exmaple, product image, Downloads file screenshot), the URL to it
	 * @apiresponse		string								url				The URL for the customer to view this purchase in the client area
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		$parent = NULL;
		if ( $this->parent )
		{
			try
			{
				$parent = static::load( $this->parent )->apiOutput( $authorizedMember );
			}
			catch ( OutOfRangeException ) {}
		}
		
		return array(
			'id'			=> $this->id,
			'name'			=> $this->name,
			'itemApp'		=> $this->app,
			'itemType'		=> $this->type,
			'itemId'		=> $this->item_id,
			'customer'		=> $this->member->apiOutput( $authorizedMember ),
			'purchased'		=> $this->start->rfc3339(),
			'expires'		=> $this->expire ? $this->expire->rfc3339() : null,
			'active'		=> $this->active,
			'canceled'		=> (bool) $this->cancelled,
			'renewalTerm'	=> $this->renewals?->apiOutput($authorizedMember),
			'customFields'	=> $this->custom_fields,
			'parent'		=> $parent,
			'show'			=> (bool) $this->show,
			'licenseKey'	=> $this->licenseKey() ? $this->licenseKey()->key : null,
			'image'			=> $this->image() ? ( (string) $this->image()->url ) : null,
			'url'			=> (string) $this->url(),
		);
	}
	
	/* !License Keys */
	
	/**
	 * @brief	License key
	 */
	public LicenseKey|null|bool $licenseKey = null;
	
	/**
	 * Get license key
	 *
	 * @return	LicenseKey|NULL|bool
	 */
	public function licenseKey() : LicenseKey|null|bool
	{
		if ( $this->licenseKey === NULL )
		{
			try
			{
				$this->licenseKey = LicenseKey::load( $this->id, 'lkey_purchase' );
			}
			catch ( OutOfRangeException )
			{
				$this->licenseKey = FALSE;
			}
		}
		return $this->licenseKey ?: NULL;
	}
	
	/**
	 * onExpire
	 *
	 * @return	void
	 */
	public function onExpire() : void
	{
		if ( $licenseKey = $this->licenseKey() )
		{
			$licenseKey->active = FALSE;
			$licenseKey->save();
		}
		
		$this->__call( 'onExpire', array() );
	}
	
	/**
	 * onCancel
	 *
	 * @return	void
	 */
	public function onCancel() : void
	{
		if ( $licenseKey = $this->licenseKey() )
		{
			$licenseKey->active = FALSE;
			$licenseKey->save();
		}
		
		$this->__call( 'onCancel', array() );
	}
	
	/**
	 * onReactivate
	 *
	 * @return	void
	 */
	public function onReactivate() : void
	{
		if ( $licenseKey = $this->licenseKey() )
		{
			$licenseKey->active = TRUE;
			$licenseKey->save();
		}
		
		$this->__call( 'onReactivate', array() );
	}
	
	/**
	 * onDelete
	 *
	 * @return	void
	 */
	public function onDelete() : void
	{
		if ( $licenseKey = $this->licenseKey() )
		{
			$licenseKey->delete();
		}
		
		$this->__call( 'onDelete', array() );
	}
	
	/* !Client Area */
	
	/**
	 * Can view?
	 *
	 * @param Member|Group|null $member	The member to check (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canView( Member|Group|null $member=null ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* Show only purchases from active applications */
		try
		{
			$app = Application::load( $this->app );
		}
		catch ( UnexpectedValueException )
		{
			return FALSE;
		}
		
		if( !$app->canAccess() )
		{
			return FALSE;
		}

		if ( $this->member->member_id === $member->member_id or array_key_exists( $member->member_id, iterator_to_array( $this->member->alternativeContacts( array( Db::i()->findInSet( 'purchases', array( $this->id ) ) ) ) ) ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Admin can change expire date / renewal term?
	 *
	 * @return	bool
	 */
	public function canChangeExpireDate() : bool
	{
		if ( $this->billing_agreement and !$this->billing_agreement->canceled )
		{
			return FALSE;
		}
		
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::canChangeExpireDate( $this );
		}
		catch ( OutOfRangeException )
		{
			return TRUE;
		}
	}
	
	/**
	 * Can Renew Until
	 *
	 * @param	Member|NULL	$member		The member to check (NULL for currently logged in member)
	 * @param	bool				$inCycles	Get in cycles rather than a date?
	 * @param bool $admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return    bool|DateTime|int    TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime (or int if $inCycles is TRUE) means can renew until that date
	 */
	public function canRenewUntil(?Member $member = NULL, bool $inCycles = FALSE, bool $admin = FALSE ): DateTime|int|bool
	{
		/* Can this purchase be renewed at all? */
		if ( !$this->canBeRenewed() )
		{
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();
		
		if ( !$admin and $this->member->member_id !== $member->member_id and !array_key_exists( $member->member_id, iterator_to_array( $this->member->alternativeContacts( array( Db::i()->findInSet( 'purchases', array( $this->id ) ) . ' AND billing=1' ) ) ) ) )
		{
			return FALSE;
		}
		
		if ( $this->cancelled or !$this->renewals or ( $this->billing_agreement and !$this->billing_agreement->canceled ) )
		{
			return FALSE;
		}
		
		if ( !$admin and $pendingInvoice = $this->invoice_pending and $pendingInvoice->status === $pendingInvoice::STATUS_PENDING )
		{
			return FALSE;
		}
				
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			$date = $extension::canRenewUntil( $this, $admin );
					
			if ( $inCycles and $date instanceof DateTime )
			{
				$now = DateTime::create();
				$cycles = 0;
				while ( $now->add( $this->renewals->interval )->getTimestamp() < $date->getTimestamp() )
				{
					$cycles++;
				}
				return $cycles;
			}
			else
			{
				return $date;
			}
		}
		catch ( OutOfRangeException )
		{
			return FALSE;
		}
	}

	/**
	 * Purchase can be renewed?
	 *
	 * @return    boolean|NULL
	 */
	public function canBeRenewed(): bool|null
	{
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			return $extension::canBeRenewed( $this );
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * Can Cancel
	 *
	 * @param Member|NULL $member	The member to check (NULL for currently logged in member)
	 * @return    bool
	 */
	public function canCancel(?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		if ( $this->member->member_id !== $member->member_id and !array_key_exists( $member->member_id, iterator_to_array( $this->member->alternativeContacts( array( Db::i()->findInSet( 'purchases', array( $this->id ) ) . ' AND billing=1' ) ) ) ) )
		{
			return FALSE;
		}
		
		return !$this->grouped_renewals and ( !$this->billing_agreement or $this->billing_agreement->canceled ) and $this->renewals and $this->active;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * Get URL
	 *
	 * @return	Url\Internal|string|null
	 */
	function url(): Url\Internal|string|null
	{
		if( $this->_url === NULL )
		{
			$this->_url = Url::internal( "app=nexus&module=clients&controller=purchases&do=view&id={$this->id}", 'front', 'clientspurchase', array( Friendly::seoTitle( $this->name ) ) );
		}

		return $this->_url;
	}
	
	/* !Actions */
		
	/**
	 * Transfer
	 *
	 * @param	Member	$newCustomer
	 * @return	void
	 */
	public function transfer( Member $newCustomer ) : void
	{
		$this->onTransfer( $newCustomer );
		$this->member = $newCustomer;
		$this->save();
		
		foreach ( $this->children() as $child )
		{
			$child->transfer( $newCustomer );
		}
	}
	
	/**
	 * Delete
	 *
	 * @param	bool	$deleteChildren		If TRUE, child purchases will also be deleted. If FALSE, they will become unassociated.
	 * @return    void
	 */
	public function delete( bool $deleteChildren = TRUE ): void
	{
		File::unclaimAttachments( 'nexus_Purchases', $this->id, NULL, 'purchase' );
		
		foreach ( $this->children( NULL ) as $child )
		{
			if ( $deleteChildren )
			{
				$child->delete();
			}
			else
			{
				$child->parent = NULL;
				$child->save();
			}
		}

		try
		{
			$this->onDelete();
		}
		catch ( BadMethodCallException ) {}

		parent::delete();
	}
	
	/* !Grouping */
	
	/**
	 * Group with parent
	 *
	 * @return	void
	 * @throws	LogicException
	 */
	public function groupWithParent() : void
	{		
		/* Get the parent */
		$parent = $this->parent();
		if ( !$parent )
		{
			throw new BadMethodCallException('no_parent');
		}
				
		/* If we have a renewal term, we need to merge it with the parent... */
		if ( $this->renewals )
		{
			/* Remember what our term is */
			$term = $this->renewals->getTerm();
			$this->grouped_renewals = array( 'term' => $term['term'], 'unit' => $term['unit'], 'price' => $this->renewals->cost->amount, 'currency' => $this->renewals->cost->currency, 'tax' => $this->renewals->tax ? $this->renewals->tax->id : 0 );
			$this->save();
			
			/* Add our term to the parent... */
			if ( !$this->cancelled )
			{
				/* If the parent also has a renewal term, merge them */
				if ( $parent->renewals )
				{
					$parent->renewals = new RenewalTerm( $parent->renewals->add( $this->renewals ), $parent->renewals->interval, $parent->renewals->tax );
				}
				/* Otherwise just set the parent to this */
				else
				{
					$parent->renewals = $this->renewals;
					if ( !$parent->expire )
					{
						$parent->expire = $this->expire;
					}
				}

				$parent->save();
			}
			
			/* Cancel any pending invoices as they're no longer valid */
			if ( $invoice = $parent->invoice_pending )
			{
				$invoice->status = $invoice::STATUS_CANCELED;
				$invoice->save();
			}
			if ( $invoice = $this->invoice_pending )
			{
				$invoice->status = $invoice::STATUS_CANCELED;
				$invoice->save();
			}
		}
		else
		{
			$this->grouped_renewals = array();
		}
		
		/* Update this purchase */
		$this->expire = NULL;
		$this->save();
	}
	
	/**
	 * Ungroup from parent
	 *
	 * @return	void
	 * @throws	BadMethodCallException
	 */
	public function ungroupFromParent() : void
	{		
		/* Get the parent */
		$parent = $this->parent();
		if ( !$parent )
		{
			throw new BadMethodCallException('no_parent');
		}
		
		/* If we have a renewal term, we need to remove it from the parent... */
		if ( $this->renewals )
		{
			/* Restore parent renewal term */
			if ( $parent->renewals and !$this->cancelled )
			{
				$newRenewalPrice = $parent->renewals->subtract( $this->renewals );
				if ( $newRenewalPrice->amount->isZero() )
				{
					$parent->renewals = NULL;
				}
				else
				{
					$parent->renewals = new RenewalTerm( $newRenewalPrice, $parent->renewals->interval, $parent->renewals->tax );
				}
				$parent->save();
			}
			
			/* Restore this purchase renewal term */
			$groupedRenewals = $this->grouped_renewals;
			if ( $groupedRenewals['term'] and $groupedRenewals['unit'] )
			{
				try
				{
					$tax = ( isset( $groupedRenewals['tax'] ) and $groupedRenewals['tax'] ) ? Tax::load( $groupedRenewals['tax'] ) : NULL;
				}
				catch ( OutOfRangeException )
				{
					$tax = NULL;
				}
				$this->renewals = new RenewalTerm(
					new Money( $groupedRenewals['price'], $groupedRenewals['currency'] ?? $this->member->defaultCurrency()),
					new DateInterval( 'P' . $groupedRenewals['term'] . mb_strtoupper( $groupedRenewals['unit'] ) ),
					$tax
				);
			}
			$this->expire = $parent->expire;
		}
		
		/* Ungroup */
		$this->grouped_renewals = NULL;
		$this->save();
		
		/* Cancel any pending invoices as they're no longer valid */
		if ( $invoice = $parent->invoice_pending )
		{
			$invoice->status = $invoice::STATUS_CANCELED;
			$invoice->save();
		}
		if ( $invoice = $this->invoice_pending )
		{
			$invoice->status = $invoice::STATUS_CANCELED;
			$invoice->save();
		}
	}
	
	/* !ACP Display */
		
	/**
	 * Get the URL of the AdminCP page for this node
	 *
	 * @param   string|null  $do The "do" query parameter of the url (e.g. 'form', 'permissions', etc).
	 *
	 * @return Url | NULL
	 */
	public function acpUrl( ?string $do="form" ): ?Url
	{
		return Url::internal( "app=nexus&module=customers&controller=purchases&do=view&id={$this->id}", 'admin' );
	}
	
	/**
	 * ACP Buttons
	 *
	 * @param	string	$ref	Referer
	 * @return	array
	 */
	public function buttons( string $ref='v' ) : array
	{
		$return = array();
		
		$url = $this->acpUrl()->setQueryString( 'r', $ref );
		
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_edit' ) )
		{
			$return['edit'] = array(
				'icon'	=> 'pencil',
				'title'	=> 'edit',
				'link'	=> $url->setQueryString( 'do', 'edit' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
			);
		}
		
		$extension = NULL;
		try
		{
			/* @var ItemPurchase $extension */
			$extension = $this->extension();
			$return = array_merge( $return, $extension::acpButtons( $this, $url ) );
		}
		catch ( OutOfRangeException ) { }
		
		$parent = $this->parent();
				
		if ( !$this->grouped_renewals and ( !$this->billing_agreement or $this->billing_agreement->canceled ) and $this->renewals and Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_add' ) )
		{
			$return['renew'] = array(
				'icon'	=> 'refresh',
				'title'	=> 'generate_renewal_invoice',
				'link'	=> $url->setQueryString( 'do', 'renew' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('generate_renewal_invoice') )
			);
		}
		
		if ( ( !$this->billing_agreement or $this->billing_agreement->canceled ) and $this->parent() and Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_edit' ) )
		{
			if ( !$this->grouped_renewals )
			{
				$return['group'] = array(
					'icon'	=> 'compress',
					'title'	=> 'group_with_parent',
					'link'	=> $url->setQueryString( 'do', 'group' )->csrf(),
					'data'	=> array( 'confirm' => '', 'confirmSubMessage' => Member::loggedIn()->language()->addToStack('group_with_parent_info') )
				);
			}
			else
			{
				$return['group'] = array(
					'icon'	=> 'expand',
					'title'	=> 'ungroup_from_parent',
					'link'	=> $url->setQueryString( 'do', 'ungroup' )->csrf(),
					'data'	=> array( 'confirm' => '', 'confirmSubMessage' => Member::loggedIn()->language()->addToStack('ungroup_from_parent_info') )
				);
			}
		}
		
		if( ( !$this->billing_agreement or $this->billing_agreement->canceled ) and !$this->grouped_renewals and Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_transfer' ) )
		{
			$return['transfer'] = array(
				'icon'	=> 'user',
				'title'	=> 'transfer',
				'link'	=> $url->setQueryString( 'do', 'transfer' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('transfer') )
			);
		}
		
		if ( ( !$this->billing_agreement or $this->billing_agreement->canceled ) and ( !$this->grouped_renewals or !$parent or !$parent->billing_agreement or $parent->billing_agreement->canceled ) and Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_cancel' ) )
		{
			if ( $this->cancelled )
			{
				/* @var ItemPurchase $extension */
				if ( $extension AND $extension::canAcpReactivate( $this ) )
				{
					$return['reactivate'] = array(
						'icon'	=> 'check',
						'title'	=> 'reactivate',
						'link'	=> $url->setQueryString( 'do', 'reactivate' )->csrf(),
						'data'	=> array( 'confirm' => '' )
					);
				}
			}
			else
			{
				$return['cancel'] = array(
					'icon'	=> 'times',
					'title'	=> 'cancel',
					'link'	=> $url->setQueryString( 'do', 'cancel' ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cancel') )
				);
			}
		}
				
		if ( ( !$this->billing_agreement or $this->billing_agreement->canceled ) and ( !$this->grouped_renewals or !$parent or !$parent->billing_agreement or $parent->billing_agreement->canceled ) and Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_delete' ) )
		{
			$return['delete'] = array(
				'icon'	=> 'times-circle',
				'title'	=> 'delete',
				'link'	=> $url->setQueryString( 'do', 'delete' ),
				'data'	=> array( 'delete' => '' )
			);
		}
		
		return $return;
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		return $this->image();
	}
}