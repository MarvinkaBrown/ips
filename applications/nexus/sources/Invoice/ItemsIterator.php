<?php
/**
 * @brief		Invoice Items Iterator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		24 Mar 2014
 */

namespace IPS\nexus\Invoice;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ArrayIterator;
use DateInterval;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\nexus\Customer;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\nexus\Money;
use IPS\nexus\Package\CustomField;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Text\Encrypt;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Items Iterator
 */
class ItemsIterator extends ArrayIterator
{
	/**
	 * @brief	Currency
	 */
	public string $currency;
	
	/**
	 * @brief	Class Names
	 */
	protected static ?array $classnames = NULL;
	
	/**
	 * Convert array into object
	 *
	 * @param	array|Item	$data	Data
	 * @return    Item
	 */
	public function arrayToObject( array|Item $data ) : Item
	{
		if ( is_object( $data ) )
		{
			return $data;
		}
				
		/* Load correct class */
		if ( $data['act'] === 'renewal' )
		{
			$obj = new Renewal( $data['itemName'], new Money( $data['cost'], $this->currency ) );
		}
		else
		{
			if ( static::$classnames === NULL )
			{
				static::$classnames = array();
				foreach ( Application::allExtensions( 'nexus', 'Item', FALSE, NULL, NULL, FALSE ) as $ext )
				{
					static::$classnames[ $ext::$application ][ $ext::$type ] = $ext;
				}
			}
			if ( $data['app'] === 'nexus' and in_array( $data['type'], array( 'product', 'ad' ) ) )
			{
				$data['type'] = 'package';
			}
			
			$class = static::$classnames[$data['app']][$data['type']] ?? NULL;
			if ( !$class )
			{
				$class = 'IPS\nexus\extensions\nexus\Item\MiscellaneousCharge';
			}
			$obj = new $class( $data['itemName'], new Money( number_format( $data['cost'], Money::numberOfDecimalsForCurrency( $this->currency ), '.', '' ), $this->currency ) );
		}
		
		/* Basic information */
		$obj->quantity = $data['quantity'];
		$obj->id = $data['itemID'];
		$obj->appKey = $data['app'];
		$obj->typeKey = $data['type'];
		
		/* Details */
		$details = array();
		$purchaseDetails = array();
		if ( isset( $data['cfields'] ) and $data['cfields'] )
		{
			foreach( $data['cfields'] AS $key => $value )
			{
				$purchaseDetails[ $key ] = $value;
				try
				{
					$field = CustomField::load( $key );
					if ( $field->invoice )
					{
						switch( $field->type )
						{
							case 'UserPass':
								$value = json_decode( Encrypt::fromTag( $value )->decrypt(), TRUE );
								$display = array();
								if ( $value['un'] )
								{
									$display[] = $value['un'];
								}
								
								if ( $value['pw'] )
								{
									$display[] = $value['pw'];
								}
								
								$details[ $key ] = implode( ' &sdot; ', $display );
								break;
							
							case 'Ftp':
								$value = json_decode( Encrypt::fromTag( $value )->decrypt(), TRUE );
								$details[ $key ] = (string) Url::createFromComponents(
									$value['server'],
									( isset( $value['protocol'] ) ) ? "{$value['protocol']}://" : 'ftp://',
									( isset( $value['path'] ) ) ? $value['path'] : NULL,
									NULL,
									( isset( $value['port'] ) ) ? $value['port'] : NULL,
									( isset( $value['un'] ) ) ? $value['un'] : NULL,
									( isset( $value['pw'] ) ) ? $value['pw'] : NULL
								);
								break;
								
							default:
								$details[ $key ] = $value;
								break;
						}
					}
				}
				catch( OutOfRangeException ) {}
			}
		}

		$obj->details = $details;
		$obj->purchaseDetails = $purchaseDetails;
		
		/* Tax */
		if ( isset( $data['tax'] ) AND $data['tax'] )
		{
			try
			{
				$obj->tax = Tax::load( $data['tax'] );
			}
			catch ( Exception ) { }
		}
		
		/* Renewal terms */
		if ( isset( $data['renew_term'] ) and $data['renew_term'] )
		{
			$obj->renewalTerm = new RenewalTerm( new Money( $data['renew_cost'], $this->currency ), new DateInterval( 'P' . $data['renew_term'] . mb_strtoupper( $data['renew_units'] ) ), $obj->tax, FALSE, isset( $data['grace_period'] ) ? new DateInterval( 'PT' . $data['grace_period'] . 'S' ) : NULL );
			if ( isset( $data['initial_interval_term'] ) and $data['initial_interval_term'] )
			{
				$obj->initialInterval = new DateInterval( 'P' . $data['initial_interval_term'] . mb_strtoupper( $data['initial_interval_unit'] ) );
			}
		}
		
		/* Expire Date */
		if ( isset( $data['expires'] ) and $data['expires'] )
		{
			$obj->expireDate = DateTime::ts( $data['expires'] );
		}
		
		/* Available methods */
		if ( isset( $data['methods'] ) and $data['methods'] !== '*' )
		{
			$obj->paymentMethodIds = $data['methods'];
		}
		
		/* Parent */
		if ( isset( $data['assoc'] ) )
		{
			if ( $data['assocBought'] )
			{
				try
				{
					$obj->parent = Purchase::load( $data['assoc'] );
				}
				catch ( OutOfRangeException ) { }
			}
			else
			{
				$obj->parent = $data['assoc'];
			}
			
			$obj->groupWithParent = $data['groupParent'] ?? FALSE;
		}
		
		/* Paying another member? */
		if ( isset( $data['payTo'] ) and $data['payTo'] )
		{
			try
			{
				$obj->payTo = Customer::load( $data['payTo'] );
				if ( isset( $data['commission'] ) )
				{
					$obj->commission = ( $data['commission'] <= 100 ) ? $data['commission'] : 100;
				}
				if ( isset( $data['fee'] ) )
				{
					$obj->fee = new Money( $data['fee'], $this->currency );
				}
			}
			catch ( Exception ) { }
		}
		
		/* URIs? */
		if ( isset( $data['itemURI'] ) )
		{
			$obj->itemURI = $data['itemURI'];
		}
		if ( isset( $data['adminURI'] ) )
		{
			$obj->adminURI = $data['adminURI'];
		}
		
		/* Extra */
		if ( isset( $data['extra'] ) )
		{
			$obj->extra = $data['extra'];
		}
		
		return $obj;
	}
	
	/**
	 * Get current
	 *
	 * @return	Item
	 */
	public function current(): Item
	{
		return $this->arrayToObject( parent::current() );
	}
	
	/**
	 * Get offset
	 *
	 * @param	mixed	$key	Index
	 * @return	Item
	 */
	public function offsetGet( mixed $key ): Item
	{
		return $this->arrayToObject( parent::offsetGet( $key ) );
	}
}