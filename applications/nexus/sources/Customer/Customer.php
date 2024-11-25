<?php
/**
 * @brief		Customer Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Feb 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Db;
use IPS\Db\Select;
use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer\CustomField;
use IPS\nexus\Donation\Goal;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_called_class;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Customer Model
 */
class Customer extends Member
{
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	Cached logged in member
	 */
	public static ?Member $loggedInMember	= NULL;
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'member_id';

	/**
	 * Get logged in member
	 *
	 * @return	Member|null
	 */
	public static function loggedIn(): ?static
	{
		/* If we haven't loaded the member yet, or if the session member has changed since we last loaded the member, reload and cache */
		if( static::$loggedInMember === NULL )
		{
			static::$loggedInMember = static::load( parent::loggedIn()->member_id );
		}

		return static::$loggedInMember;
	}

	/**
	 * Construct Load Query
	 *
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to
	 * @param	mixed		$extraWhereClause	Additional where clause(s)
	 * @return	Select
	 */
	protected static function constructLoadQuery( int|string $id, string $idField, mixed $extraWhereClause ): Select
	{
		$where = array( array( 'core_members.' . $idField . '=?', $id ) );
		if( $extraWhereClause !== NULL )
		{
			if ( !is_array( $extraWhereClause ) or !is_array( $extraWhereClause[0] ) )
			{
				$extraWhereClause = array( $extraWhereClause );
			}
			$where = array_merge( $where, $extraWhereClause );
		}
		
		return static::db()->select( '*, core_members.member_id AS _member_id', static::$databaseTable, $where )->join( 'nexus_customers', 'nexus_customers.member_id=core_members.member_id' );
	}
	
	/**
	 * Load Record
	 *
	 * @see        Db::build
	 * @param	int|string|null	$id					ID
	 * @param	string|null		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @return	static
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): static
	{
		/* Guests */
		if( $id === NULL OR $id === 0 OR $id === '' )
		{
			$classname = get_called_class();
			return new $classname;
		}
		
		/* If we didn't specify an ID field, assume the default */
		if( $idField === NULL )
		{
			$idField = static::$databasePrefix . static::$databaseColumnId;
		}
		
		/* If we did, check it's valid */
		elseif( !in_array( $idField, static::$databaseIdFields ) )
		{
			throw new InvalidArgumentException;
		}
				
		/* Does that exist in the multiton store? */
		if( $idField === static::$databasePrefix . static::$databaseColumnId and !empty( static::$multitons[ $id ] ) )
		{
			return static::$multitons[ $id ];
		}
		
		/* If not, find it */
		else
		{
			/* Load it */
			try
			{
				$row = static::constructLoadQuery( $id, $idField, $extraWhereClause )->first();
			}
			catch ( UnderflowException )
			{
				throw new OutOfRangeException;
			}
			
			/* If it doesn't exist in the multiton store, set it */
			if( !isset( static::$multitons[ $row[ static::$databasePrefix . static::$databaseColumnId ] ] ) )
			{
				static::$multitons[ $row['_member_id'] ] = static::constructFromData( $row );
			}
			
			/* And return it */
			return static::$multitons[ $row['_member_id'] ];
		}
	}
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		if ( isset( $data['_member_id'] ) )
		{
			$data['member_id'] = $data['_member_id'];
			unset( $data['_member_id'] );
		}

		/* If this was guest data there may be no member_id set, which will cause an undefined index */
		if( !isset( $data['member_id'] ) )
		{
			$data['member_id']	= 0;
		}
		
		return parent::constructFromData( $data, $updateMultitonStoreIfExists );
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->_data['cm_first_name'] = '';
		$this->_data['cm_last_name'] = '';
		parent::setDefaultValues();
	}
	
	/**
	 * Get customer name
	 *
	 * @return	string
	 */
	public function get_cm_name(): string
	{
		return ( $this->cm_first_name or $this->cm_last_name ) ? "{$this->cm_first_name} {$this->cm_last_name}" : $this->name;
	}
	
	/**
	 * Get account credit
	 *
	 * @return	array
	 */
	public function get_cm_credits(): array
	{
		$amounts = ( $this->member_id AND isset( $this->_data['cm_credits'] ) ) ? json_decode( $this->_data['cm_credits'], TRUE ) : array();
		$return = array();
		foreach (Money::currencies() as $currency )
		{
			if ( isset( $amounts[ $currency ] ) )
			{
				$return[ $currency ] = new Money( $amounts[ $currency ], $currency );
			}
			else
			{
				$return[ $currency ] = new Money( 0, $currency );
			}
		}
		return $return;
	}
	
	/**
	 * Set account credit
	 *
	 * @param array $amounts	Amounts
	 * @return	void
	 */
	public function set_cm_credits( array $amounts ) : void
	{
		$save = array();
		foreach ( $amounts as $amount )
		{
			$save[ $amount->currency ] = $amount->amountAsString();
		}
		$this->_data['cm_credits'] = json_encode( $save );
	}
	
	/**
	 * Get profiles
	 *
	 * @return	array
	 */
	public function get_cm_profiles(): array
	{
		return ( isset( $this->_data['cm_profiles'] ) and $this->_data['cm_profiles'] ) ? json_decode( $this->_data['cm_profiles'], TRUE ) : array();
	}
	
	/**
	 * Set profiles
	 *
	 * @param array $profiles	Profiles
	 * @return	void
	 */
	public function set_cm_profiles( array $profiles ) : void
	{
		$this->_data['cm_profiles'] = json_encode( $profiles );
	}
	
	/**
	 * Get default currency
	 *
	 * @return	string
	 */
	public function defaultCurrency() : string
	{
		if ( $currencies = json_decode( Settings::i()->nexus_currency, TRUE ) )
		{
			foreach ( $currencies as $k => $v )
			{
				if ( in_array( $this->language()->id, $v ) )
				{
					return $k;
				}
			}
			
			$keys = array_keys( $currencies );
			return array_shift( $keys );
		}
		else
		{
			return Settings::i()->nexus_currency;
		}
	}

	/**
	 * @brief	Cache primary billing address in case the method is called multiple times
	 */
	protected GeoLocation|bool|null $primaryBillingAddress = false;
	
	/**
	 * Get primary billing address, if one exists
	 *
	 * @return	GeoLocation|NULL
	 */
	public function primaryBillingAddress() : GeoLocation|null
	{
		if( $this->primaryBillingAddress === FALSE )
		{
			try
			{
				$this->primaryBillingAddress = GeoLocation::buildFromJson( Db::i()->select( 'address', 'nexus_customer_addresses', array( '`member`=? AND primary_billing=1', $this->member_id ) )->first() );
			}
			catch ( UnderflowException )
			{
				$this->primaryBillingAddress = NULL;
			}
		}

		return $this->primaryBillingAddress;
	}
	
	/**
	 * Estimated location
	 *
	 * @return	GeoLocation|NULL
	 */
	public function estimatedLocation(): ?GeoLocation
	{
		if ( $this->member_id === Customer::loggedIn()->member_id )
		{
			if ( isset( Request::i()->cookie['location'] ) AND ( !Request::i()->cookie['location'] OR ( Request::i()->cookie['location'] AND $data = json_decode( Request::i()->cookie['location'], true ) AND array_key_exists( 'member_id', $data ) AND $data['member_id'] == $this->member_id ) ) )
			{
				$location = NULL;

				if( Request::i()->cookie['location'] AND $data = json_decode( Request::i()->cookie['location'], true ) AND $data['member_id'] == $this->member_id )
				{
					$location = GeoLocation::buildFromJson( Request::i()->cookie['location'] );
				}
			}
			else
			{
				$location = $this->primaryBillingAddress();
				if ( !$location )
				{
					try
					{
						$location = GeoLocation::getRequesterLocation();
						Request::i()->setCookie( 'location', $this->_addMemberToAddress( $location ) );
					}
					catch ( Exception )
					{
						if( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) )
						{
							$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
							$exploded = explode( '-', array_shift( $languages ) );

							if ( in_array( mb_strtoupper( $exploded[1] ), GeoLocation::$countries ) )
							{
								$location = new GeoLocation;
								$location->country = mb_strtoupper( $exploded[1] );
								Request::i()->setCookie( 'location', $this->_addMemberToAddress( $location ) );
							}
							else
							{
								$location = NULL;
								Request::i()->setCookie( 'location', '' );
							}
						}
						else
						{
							$location = NULL;
							Request::i()->setCookie( 'location', '' );
						}
					}
				}
			}
		}
		else
		{
			$location = $this->primaryBillingAddress();
		}
		
		return $location;
	}

	/**
	 * Add the member ID to the address object so we can validate it on subsequent requests
	 *
	 * @param GeoLocation $location	Location object
	 * @return	string
	 */
	protected function _addMemberToAddress( GeoLocation $location ): string
	{
		return json_encode( array_merge( json_decode( json_encode( $location ), true ), array( 'member_id' => $this->member_id ) ) );
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		$data = $this->_data;
		
		$customerTable = array();
		foreach ( ( $this->_new ? $this->_data : $this->changed ) as $k => $v )
		{
			if ( ( mb_substr( $k, 0, 3 ) === 'cm_' and !in_array( $k, array( 'cm_credits', 'cm_return_group', 'cm_reg' ) ) ) or mb_substr( $k, 0, 6 ) === 'field_'  )
			{
				$customerTable[ $k ] = $v;
				unset( $this->_data[ $k ] );
				unset( $this->changed[ $k ] );
			}
		}
				
		parent::save();
		$data['member_id'] = $this->_data['member_id'];
		$this->_data = $data;
		
		if ( count( $customerTable ) )
		{
			$customerTable['member_id'] = $this->member_id;
			Db::i()->insert( 'nexus_customers', $customerTable, TRUE );
		}
	}
	
	/**
	 * Log Action
	 *
	 * @param string $type	Log type
	 * @param mixed|null $extra	Any extra data for the type
	 * @param mixed|null $by		The member performing the action. NULL for currently logged in member or FALSE for no member
	 * @return	void
	 */
	public function log( string $type, mixed $extra=NULL, mixed $by=NULL ) : void
	{
		$this->logHistory( 'nexus', $type, $extra, $by );
	}

	/**
	 * Get total amount spent
	 *
	 * @return Money|string
	 */
	public function totalSpent(): Money|string
	{
		$return = array();
		foreach (Db::i()->select( 't_currency, ( SUM(t_amount)-SUM(t_partial_refund) ) AS amount', 'nexus_transactions', array( 't_member=? AND ( t_status=? OR t_status=? )', $this->member_id, Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED ), NULL, NULL, 't_currency' ) as $amount )
		{
			$return[] = (string) new Money( $amount['amount'], $amount['t_currency'] );
		}
		return count( $return ) ? implode( ' + ', $return ) : new Money( 0, $this->defaultCurrency() );
	}
	
	/**
	 * @brief	Number of previous purchases by package ID
	 */
	protected mixed $previousPurchasesCount = NULL;
	
	/**
	 * Get number of previous purchases of a package ID (used to calculate loyalty discounts)
	 *
	 * @param int $packageID	Package ID
	 * @param bool $activeOnly	Active only?
	 * @return	int|array
	 */
	public function previousPurchasesCount( int $packageID, bool $activeOnly=FALSE ): int|array
	{
		if ( $this->previousPurchasesCount === NULL )
		{			
			$this->previousPurchasesCount['all'] = iterator_to_array( Db::i()->select( 'ps_item_id, COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_member=?', 'nexus', 'package', $this->member_id ), NULL, NULL, 'ps_item_id' )->setKeyField('ps_item_id')->setValueField('COUNT(*)') );
			$this->previousPurchasesCount['active'] = iterator_to_array( Db::i()->select( 'ps_item_id, COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_member=? AND ps_active=1', 'nexus', 'package', $this->member_id ), NULL, NULL, 'ps_item_id' )->setKeyField('ps_item_id')->setValueField('COUNT(*)') );
		}
				
		return $this->previousPurchasesCount[$activeOnly ? 'active' : 'all'][$packageID] ?? 0;
	}
	
	/**
	 * Client Area Links
	 *
	 * @return	array
	 */
	public function clientAreaLinks(): array
	{
		$return = array( 'invoices' );
		if ( Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_member=? AND ps_show=1', $this->member_id ) ) )
		{
			$return[] = 'purchases';
		}
		
		$return[] = 'addresses';
		if ( count( Gateway::cardStorageGateways() ) )
		{
			$return[] = 'cards';
		}
		if ( count( CustomField::roots() ) )
		{
			$return[] = 'info';
		}
		
		if ( Settings::i()->nexus_min_topup or count( json_decode( Settings::i()->nexus_payout, TRUE ) ) )
		{
			$return[] = 'credit';
		}
		
		$return[] = 'alternatives';
		
		if ( count( Goal::roots() ) )
		{
			$return[] = 'donations';
		}
		
		if ( Settings::i()->ref_on )
		{
			$return[] = 'referrals';
		}
		
		return $return;
	}
	
	/**
	 * Alternative Contacts
	 *
	 * @param array $where	WHERE clause
	 * @return	ActiveRecordIterator
	 */
	public function alternativeContacts( array $where = array() ): ActiveRecordIterator
	{
		$where = count( $where ) ? array( $where ) : $where;
		$where[] = array( 'main_id=?', $this->member_id );
		return new ActiveRecordIterator( Db::i()->select( '*', 'nexus_alternate_contacts', $where )->setKeyField( 'alt_id' ), 'IPS\nexus\Customer\AlternativeContact' );
	}
	
	/**
	 * Parent Contacts
	 *
	 * @param array $where	WHERE clause
	 * @return	ActiveRecordIterator
	 */
	public function parentContacts( array $where = array() ): ActiveRecordIterator
	{
		$where = count( $where ) ? array( $where ) : $where;
		$where[] = array( 'alt_id=?', $this->member_id );
		return new ActiveRecordIterator( Db::i()->select( '*', 'nexus_alternate_contacts', $where )->setKeyField( 'main_id' ), 'IPS\nexus\Customer\AlternativeContact' );
	}
	
	/**
	 * ACP Customer Page URL
	 *
	 * @return	Url
	 */
	public function acpUrl(): Url
	{
		return parent::acpUrl()->setQueryString( 'tab', 'nexus_Main' );
	}

	/**
	 * ACP Customer Page URL
	 *
	 * @param	Number	$amount	 	Adjustment amount
	 * @param string $currency	Currency code
	 * @param bool $refund		Should the spend be reduced?
	 *
	 * @return	void
	 */
	public function updateSpend( Number $amount, string $currency, bool $refund=FALSE ) : void
	{
		try
		{
			$currentSpend = Db::i()->select( 'spend_amount', 'nexus_customer_spend', array( "spend_member_id=? AND spend_currency=?", $this->member_id, $currency ) )->first();
			$newSpend = new Number( number_format( $currentSpend, Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) );
			$newSpend = ( $refund ) ? $newSpend->subtract( $amount ) : $newSpend->add( $amount );
			Db::i()->replace( 'nexus_customer_spend', array( 'spend_member_id' => $this->member_id, 'spend_amount' => $newSpend, 'spend_currency' => $currency ) );
		}
		catch ( UnderflowException )
		{
			Db::i()->insert( 'nexus_customer_spend', array( 'spend_member_id' => $this->member_id, 'spend_amount' => ( $refund ) ? $amount->multiply( new Number( "-1" ) ) : $amount, 'spend_currency' => $currency ), TRUE );
		}
	}

	/**
	 * Recalculate total amount spent in all currencies
	 *
	 * @return	void
	 */
	public function recountTotalSpend() : void
	{
		Db::i()->delete( 'nexus_customer_spend', array( 'spend_member_id=?', $this->member_id ) );

		$spend = array();
		foreach (Db::i()->select( 't_currency, ( SUM(t_amount)-SUM(t_partial_refund) ) AS amount', 'nexus_transactions', array( 't_member=? AND ( t_status=? OR t_status=? )', $this->member_id, Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED ), NULL, NULL, 't_currency' ) as $amount )
		{
			$spend[] = array( 'spend_member_id' => $this->member_id, 'spend_amount' => $amount['amount'], 'spend_currency' => $amount['t_currency'] );
		}

		if( count( $spend ) )
		{
			Db::i()->insert( 'nexus_customer_spend', $spend );
		}
	}
}