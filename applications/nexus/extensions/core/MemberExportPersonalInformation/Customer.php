<?php
/**
 * @brief		ACP Export Personal Information
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 May 2018
 */

namespace IPS\nexus\extensions\core\MemberExportPersonalInformation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\MemberExportPiiAbstract;
use IPS\GeoLocation;
use IPS\Member;
use IPS\nexus\Customer as NexusCustomer;
use IPS\nexus\Customer\CustomField;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use function defined;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Export Personal Information
 */
class Customer extends MemberExportPiiAbstract
{
	/**
	 * Return data
	 * @param	Member		$member		The member
	 *
	 * @return	array
	 */
	public function getData( Member $member ): array
	{
		$return = array();
		
		try
		{
			$customer = NexusCustomer::load( $member->member_id );
			
			/* Name and addresses */
			$return['customer'] = array(
				'first_name' => $customer->cm_first_name,
				'last_name'  => $customer->cm_last_name
			);
			
			foreach( Db::i()->select( '*', 'nexus_customer_addresses', array( '`member`=?', $member->member_id ) ) as $address )
			{
				$return['addresses'][] = GeoLocation::buildFromJson( $address['address'] )->toString( "," );
			}
			
			/* Customer custom fields */
			foreach ( CustomField::roots() as $field )
			{
				$column = $field->column;
				if ( $column )
				{
					$return['fields'][ $column ] = $field->displayValue( $customer->$column, TRUE );
				}
			}
			
			/* Credit cards */
			foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_customer_cards', array( 'card_member=?', $member->member_id ) ), 'IPS\nexus\Customer\CreditCard' ) as $card )
			{
				try
				{
					$cardData = $card->card;
					$return['credit_cards'][ $card->id ] = array(
						'card_type'		=> $cardData->type,
						'card_number'	=> $cardData->lastFour,
						'card_expire'	=> ( !is_null( $cardData->expMonth ) AND !is_null( $cardData->expYear ) ) ? str_pad( $cardData->expMonth , 2, '0', STR_PAD_LEFT ). '/' . $cardData->expYear : NULL
					);
				}
				catch ( Exception ) {}
			}
		}
		catch( OutOfRangeException )
		{
			
		}
		
		return $return;
	}
	
}