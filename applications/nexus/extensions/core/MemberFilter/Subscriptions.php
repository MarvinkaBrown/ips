<?php
/**
 * @brief		Member filter extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		20 Apr 2015
 */

namespace IPS\nexus\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Node;
use IPS\nexus\Subscription\Package;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use function defined;
use function in_array;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member filter extension
 */
class Subscriptions extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param string $area Area to check (bulkmail, group_promotions, automatic_moderation)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail' ) );
	}
	
	/** 
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		return array(
			new Node( 'nexus_bm_filters_subscription_pkgs', isset( $criteria['nexus_bm_filters_subscription_pkgs'] ) ? array_filter( array_map( function( $val )
			{
				try
				{
					return Package::load( $val );
				}
				catch ( OutOfRangeException )
				{
					return NULL;
				}
			}, explode( ',', $criteria['nexus_bm_filters_subscription_pkgs'] ) ) ) : 0, FALSE, array( 'zeroVal' => 'nexus_bm_filters_subscription_none', 'multiple' => TRUE, 'class' => 'IPS\nexus\Subscription\Package', 'zeroValTogglesOff' => array( 'nexus_bm_filters_subscription_type' ) ) ),
			
			new CheckboxSet( 'nexus_bm_filters_subscription_type', isset( $criteria['nexus_bm_filters_subscription_type'] ) ? explode( ',', $criteria['nexus_bm_filters_subscription_type'] ) : array( 'active' ), FALSE, array( 'toggles' => array( 'expired' => array( 'nexus_bm_filters_type_expired_date' ) ), 'options' => array( 'active' => 'nexus_bm_filters_type_active', 'expired' => 'nexus_bm_filters_type_expired' ) ), NULL, NULL, NULL, 'nexus_bm_filters_subscription_type' ),
			
			new Custom( 'nexus_bm_filters_type_expired_date', array(
				0 => $criteria['nexus_bm_filters_type_expired_date']['range'] ?? '',
				1 => $criteria['nexus_bm_filters_type_expired_date']['days'] ?? NULL
			), FALSE, array(
				'getHtml'	=> function( $element )
				{
					$dateRange = new DateRange( "{$element->name}[0]", $element->value[0], FALSE );

					return Theme::i()->getTemplate( 'members', 'core', 'global' )->dateFilters( $dateRange, $element );
				}
			), NULL, NULL, NULL, 'nexus_bm_filters_type_expired_date' ),
		);
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return    array|bool            False, or an array of data to use later when filtering the members
	 * @throws LogicException
	 */
	public function save( array $post ): array|bool
	{
		if ( is_array( $post['nexus_bm_filters_subscription_pkgs'] ) )
		{
			$ids = array();
			foreach ( $post['nexus_bm_filters_subscription_pkgs'] as $package )
			{
				$ids[] = $package->id;
			}

			$return = array(
				'nexus_bm_filters_subscription_pkgs' => implode( ',', $ids ),
				'nexus_bm_filters_subscription_active' => ( in_array( "active", $post['nexus_bm_filters_subscription_type'] ) ),
				'nexus_bm_filters_subscription_canceled' => ( in_array( "expired", $post['nexus_bm_filters_subscription_type'] ) ),
			);

			if( isset( $post['nexus_bm_filters_type_expired_date'][2] ) AND $post['nexus_bm_filters_type_expired_date'][2] == 'days' )
			{
				$return['nexus_bm_filters_type_expired_date'] = $post['nexus_bm_filters_type_expired_date'][1] ? array( 'days' => intval( $post['nexus_bm_filters_type_expired_date'][1] ) ) : FALSE;
			}
			else
			{
				/* Normalize objects to their array form. Bulk mailer stores options as a json array where as member export does not, so $data['range']['start'] is a DateTime object */
				$return['nexus_bm_filters_type_expired_date'] = ( empty($post['nexus_bm_filters_type_expired_date'][0]) ) ? FALSE : array( 'range' => json_decode( json_encode( $post['nexus_bm_filters_type_expired_date'][0] ), TRUE ) );
			}


			return $return;
		}
		
		return FALSE;
	}

	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( isset( $data['nexus_bm_filters_subscription_pkgs'] ) and $data['nexus_bm_filters_subscription_pkgs'] )
		{
			return array( 'nexus_purchases.ps_id IS NOT NULL' );
		}
		return NULL;
	}
	
	/**
	 * Callback for member retrieval database query
	 * Can be used to set joins
	 *
	 * @param array $data	The array returned from the save() method
	 * @param	Select	$query	The query
	 * @return	void
	 */
	public function queryCallback( array $data, Select $query ) : void
	{
		if ( isset( $data['nexus_bm_filters_subscription_pkgs'] ) and $data['nexus_bm_filters_subscription_pkgs'] )
		{
			$types = array();

			if ( isset($data[ 'nexus_bm_filters_subscription_active' ]) and $data[ 'nexus_bm_filters_subscription_active' ] )
			{
				$types[] = 'nexus_purchases.ps_active=1';
			}

			if ( isset( $data[ 'nexus_bm_filters_subscription_canceled' ] ) and $data[ 'nexus_bm_filters_subscription_canceled' ] )
			{
				$types[] = 'nexus_purchases.ps_cancelled=1';
			}

			if ( !empty( $data[ 'nexus_bm_filters_type_expired_date' ][ 'range' ] ) AND !empty( $data[ 'nexus_bm_filters_type_expired_date' ][ 'range' ][ 'end' ] ) )
			{
				$start = ( $data[ 'nexus_bm_filters_type_expired_date' ][ 'range' ][ 'start' ] ) ? new DateTime( $data[ 'nexus_bm_filters_type_expired_date' ][ 'range' ][ 'start' ] ) : NULL;
				$end = new DateTime( $data[ 'nexus_bm_filters_type_expired_date' ][ 'range' ][ 'end' ] );

				if ( $start )
				{
					$types[] = "( nexus_purchases.ps_active=0 AND nexus_purchases.ps_cancelled=0 AND nexus_purchases.ps_expire BETWEEN {$start->getTimestamp()} AND {$end->getTimestamp()})";
				}
			}
			elseif ( !empty( $data[ 'nexus_bm_filters_type_expired_date' ][ 'days' ] ) )
			{
				$date = DateTime::create()->sub( new DateInterval( 'P' . $data[ 'nexus_bm_filters_type_expired_date' ][ 'days' ] . 'D' ) );
				$types[] = "( nexus_purchases.ps_active=0 AND nexus_purchases.ps_cancelled=0 AND nexus_purchases.ps_expire < {$date->getTimestamp()})";
			}
			
			if ( !empty( $types ) )
			{
				$types = implode( ' OR ', $types );
				$query->join( 'nexus_purchases', "nexus_purchases.ps_app='nexus' AND nexus_purchases.ps_type='subscription' AND nexus_purchases.ps_member=core_members.member_id AND " . Db::i()->in( 'nexus_purchases.ps_item_id', explode( ',', $data['nexus_bm_filters_subscription_pkgs'] ) ) . " AND ( {$types} )" );
			}
		}
	}
}