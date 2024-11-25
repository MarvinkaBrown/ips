<?php
/**
 * @brief		Member filter extension: member last visit date
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 June 2013
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\DateRange;
use IPS\Member;
use IPS\Theme;
use LogicException;
use function defined;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member filter: Member last visit date
 */
class Lastvisit extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail', 'group_promotions', 'automatic_moderation', 'passwordreset' ) );
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
			new Custom( 'bmf_members_last_visit', array( 0 => $criteria['range'] ?? '', 1 => $criteria['days'] ?? NULL, 3 => $criteria['days_lt'] ?? NULL ), FALSE, array(
				'getHtml'	=> function( $element )
				{
					$dateRange = new DateRange( "{$element->name}[0]", $element->value[0], FALSE );

					return Theme::i()->getTemplate( 'members', 'core', 'global' )->dateFilters( $dateRange, $element );
				}
			) )
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
		if ( isset( $post['bmf_members_last_visit'][2] ) )
		{
			if ( $post['bmf_members_last_visit'][2] == 'days' )
			{
				return $post['bmf_members_last_visit'][1] ? array( 'days' => intval( $post['bmf_members_last_visit'][1] ) ) : FALSE;
			}
			else if ( $post['bmf_members_last_visit'][2] == 'days_lt' )
			{
				return $post['bmf_members_last_visit'][3] ? array( 'days_lt' => intval( $post['bmf_members_last_visit'][3] ) ) : FALSE;
			}
			elseif( $post['bmf_members_last_visit'][2] == 'range' )
			{
				return ( empty( $post['bmf_members_last_visit'][0] ) OR empty( $post['bmf_members_last_visit'][0]['start'] ) ) ? FALSE : array( 'range' => json_decode( json_encode( $post['bmf_members_last_visit'][0] ), TRUE ) );
			}
		}
		else
		{
			/* Normalize objects to their array form. Bulk mailer stores options as a json array where as member export does not, so $data['range']['start'] is a DateTime object */
			return ( empty( $post['bmf_members_last_visit'][0] ) OR empty( $post['bmf_members_last_visit'][0]['start'] ) ) ? FALSE : array( 'range' => json_decode( json_encode( $post['bmf_members_last_visit'][0] ), TRUE ) );
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
		if( !empty($data['range']) AND !empty($data['range']['end']) )
		{
			$start	= NULL;
			$end	= NULL;
			if ( $data['range']['start'] )
			{
				try
				{
					/* Try just what is stored */
					$start = new DateTime( $data['range']['start'] );
				}
				catch( Exception $e )
				{
					/* If there was an error, try dashes so DateTime will assume European dates. @see <a href='http://php.net/manual/en/function.strtotime.php#refsect1-function.strtotime-notes'>PHP Documentation</a> */
					$start = new DateTime( str_replace( '/', '-', $data['range']['start'] ) );
				}
			}

			try
			{
				$end = new DateTime( $data['range']['end'] );
			}
			catch( Exception $e )
			{
				$end = new DateTime( str_replace( '/', '-', $data['range']['end'] ) );
			}

			if( $start )
			{
				return array( "GREATEST( core_members.last_activity, core_members.last_visit ) BETWEEN {$start->getTimestamp()} AND {$end->getTimestamp()}" );
			}
		}
		elseif( !empty( $data['days'] ) AND (int) $data['days'] )
		{
			$date = DateTime::create()->sub( new DateInterval( 'P' . (int) $data['days'] . 'D' ) );

			return array( "GREATEST( core_members.last_activity, core_members.last_visit ) < {$date->getTimestamp()}" );
		}
		elseif( !empty( $data['days_lt'] ) AND (int) $data['days_lt'] )
		{
			$date = DateTime::create()->sub( new DateInterval( 'P' . (int) $data['days_lt'] . 'D' ) );

			return array( "GREATEST( core_members.last_activity, core_members.last_visit ) > {$date->getTimestamp()}" );
		}

		return NULL;
	}

	/**
	 * Determine if a member matches specified filters
	 *
	 * @note	This is only necessary if availableIn() includes group_promotions
	 * @param	Member	$member		Member object to check
	 * @param	array 		$filters	Previously defined filters
	 * @param	object|NULL	$object		Calling class
	 * @return	bool
	 */
	public function matches( Member $member, array $filters, ?object $object=NULL ) : bool
	{
		/* If we aren't filtering by this, then any member matches */
		if( ( !isset( $filters['range'] ) OR !$filters['range'] OR empty( $filters['range']['end'] ) ) AND ( !isset( $filters['days'] ) OR !$filters['days'] ) AND ( !isset( $filters['days_lt'] ) OR !$filters['days_lt'] ) )
		{
			return TRUE;
		}

		$lastVisit = DateTime::ts( max( (int) $member->last_visit, (int) $member->last_activity ) );

		if( !empty( $filters['range'] ) AND !empty( $filters['range']['end'] ) )
		{
			$start	= NULL;
			$end	= NULL;
			
			if ( $filters['range']['start'] )
			{
				try
				{
					$start = new DateTime( $filters['range']['start'] );
				}
				catch( Exception $e )
				{
					$start = new DateTime( $filters['range']['start'] );
				}
			}

			try
			{
				$end = new DateTime( $filters['range']['end'] );
			}
			catch( Exception $e )
			{
				$end = new DateTime( str_replace( '/', '-', $filters['range']['end'] ) );
			}

			if( $start )
			{
				return ( $lastVisit->getTimestamp() > $start->getTimestamp() AND $lastVisit->getTimestamp() < $end->getTimestamp() );
			}
		}
		elseif( !empty( $filters['days'] ) AND (int) $filters['days'] )
		{
			return ( $lastVisit->add( new DateInterval( 'P' . (int) $filters['days'] . 'D' ) )->getTimestamp() < time() );
		}
		elseif( !empty( $filters['days_lt'] ) AND (int) $filters['days_lt'] )
		{
			return ( $lastVisit->add( new DateInterval( 'P' . (int) $filters['days_lt'] . 'D' ) )->getTimestamp() > time() );
		}

		return false;
	}
	
	/**
	 * Return a lovely human description for this rule if used
	 *
	 * @param	mixed				$filters	The array returned from the save() method
	 * @return	string|NULL
	 */
	public function getDescription( array $filters ) : ?string
	{
		if( !empty( $filters['range'] ) AND !empty( $filters['range']['end'] ) )
		{
			$start	= NULL;
			$end	= NULL;
			
			if ( $filters['range']['start'] )
			{
				try
				{
					$start = new DateTime( $filters['range']['start'] );
				}
				catch( Exception $e )
				{
					$start = new DateTime( $filters['range']['start'] );
				}
			}

			try
			{
				$end = new DateTime( $filters['range']['end'] );
			}
			catch( Exception $e )
			{
				$end = new DateTime( str_replace( '/', '-', $filters['range']['end'] ) );
			}

			if ( $start )
			{
				return Member::loggedIn()->language()->addToStack( 'member_filter_core_lastvisit_range_desc', FALSE, array( 'sprintf' => array( $start->localeDate(), $end->localeDate() ) ) );
			}
		}
		elseif( !empty( $filters['days'] ) AND (int) $filters['days'] )
		{
			return Member::loggedIn()->language()->addToStack( 'member_filter_core_lastvisit_days_desc', FALSE, array( 'sprintf' => array( $filters['days'] ) ) );
		}
		elseif( !empty( $filters['days_lt'] ) AND (int) $filters['days_lt'] )
		{
			return Member::loggedIn()->language()->addToStack( 'member_filter_core_lastvisit_days_lt_desc', FALSE, array( 'sprintf' => array( $filters['days_lt'] ) ) );
		}
		
		return NULL;
	}
}