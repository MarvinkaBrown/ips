<?php
/**
 * @brief		Rating Trait for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jan 2013
 */

namespace IPS\Content;

use BadMethodCallException;
use IPS\Member;
use IPS\Db;
use IPS\Request;
use IPS\Output;
use IPS\Theme;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Rating;

use UnderflowException;
use function defined;
use function header;
use function intval;
use function get_called_class;
use function round;
use function time;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Rating Trait for Content Models/Comments
 */
trait Ratings
{
	/**
	 * Can Rate?
	 *
	 * @param Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	bool
	 * @throws	BadMethodCallException
	 */
	public function canRate( Member|null $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		if( !$member->member_id )
		{
			return false;
		}

		switch ( $member->group['g_topic_rate_setting'] )
		{
			case 2:
				return TRUE;
			case 1:
				return $this->memberRating( $member ) === NULL;
			default:
				return FALSE;
		}
	}
	
	/**
	 * @brief	Ratings submitted by members
	 */
	protected array $memberRatings = array();
	
	/**
	 * Rating submitted by member
	 *
	 * @param Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	int|null
	 * @throws	BadMethodCallException
	 */
	public function memberRating( Member|null $member = NULL ): int|NULL
	{
		$member = $member ?: Member::loggedIn();
		
		$idColumn = static::$databaseColumnId;
		if ( !array_key_exists( $member->member_id, $this->memberRatings ) )
		{
			try
			{
				$this->memberRatings[ $member->member_id ] = intval( Db::i()->select( 'rating', 'core_ratings', array( 'class=? AND item_id=? AND `member`=?', get_called_class(), $this->$idColumn, $member->member_id ) )->first() );
			}
			catch ( UnderflowException $e )
			{
				$this->memberRatings[ $member->member_id ] = NULL;
			}
		}
		
		return $this->memberRatings[ $member->member_id ];
	}

	/**
	 * @brief	Calculated average rating
	 */
	protected int|null $_averageRating = NULL;
	
	/**
	 * Get average rating
	 *
	 * @return	float
	 * @throws	BadMethodCallException
	 */
	public function averageRating(): float
	{	
		if ( isset( static::$databaseColumnMap['rating_average'] ) )
		{
			return (float) $this->mapped('rating_average');
		}
		elseif ( isset( static::$databaseColumnMap['rating_total'] ) and isset( static::$databaseColumnMap['rating_hits'] ) )
		{
			return $this->mapped('rating_hits') ? round( $this->mapped('rating_total') / $this->mapped('rating_hits'), 1 ) : 0;
		}
		else
		{
			if( $this->_averageRating === NULL )
			{
				$idColumn = static::$databaseColumnId;
				$this->_averageRating = round( Db::i()->select( 'AVG(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first(), 1 );
			}

			return $this->_averageRating;
		}
	}

	/**
	 * Get number of ratings
	 *
	 * @return	float
	 * @throws	BadMethodCallException
	 */
	public function numberOfRatings(): float
	{	
		if ( isset( static::$databaseColumnMap['rating_total'] ) and isset( static::$databaseColumnMap['rating_hits'] ) )
		{
			return $this->mapped('rating_hits') ?: 0;
		}
		else
		{
			$idColumn = static::$databaseColumnId;
			return Db::i()->select( 'COUNT(*)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first();
		}
	}
		
	/**
	 * Display rating (will just display stars if member cannot rate)
	 *
	 * @return	string
	 * @throws	BadMethodCallException
	 */
	public function rating(): string
	{
		if ( $this->canRate() )
		{
			$idColumn = static::$databaseColumnId;
						
			$form = new Form('rating');
			$averageRating = $this->averageRating();
			$form->add( new Rating( 'rating', NULL, FALSE, array( 'display' => $averageRating, 'userRated' => $this->memberRating() ) ) );

			if ( $values = $form->values() )
			{
				Db::i()->insert( 'core_ratings', array(
					'class'			=> get_called_class(),
					'item_id'		=> $this->$idColumn,
					'member'		=> (int) Member::loggedIn()->member_id,
					'rating'		=> $values['rating'],
					'ip'			=> Request::i()->ipAddress(),
					'rating_date'	=> time()
				), TRUE );
				 
				if ( isset( static::$databaseColumnMap['rating_average'] ) )
				{
					$column = static::$databaseColumnMap['rating_average'];
					$this->$column = round( Db::i()->select( 'AVG(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first(), 1 );
				}
				if ( isset( static::$databaseColumnMap['rating_total'] ) )
				{
					$column = static::$databaseColumnMap['rating_total'];
					$this->$column = Db::i()->select( 'SUM(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first();
				}
				if ( isset( static::$databaseColumnMap['rating_hits'] ) )
				{
					$column = static::$databaseColumnMap['rating_hits'];
					$this->$column = Db::i()->select( 'COUNT(*)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first();
				}

				$this->save();

				if ( Request::i()->isAjax() )
				{
					Output::i()->json( 'OK' );
				}
			}
			
			return $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'ratingTemplate' ) );
		}
		else
		{
			return Theme::i()->getTemplate( 'global', 'core' )->rating( 'veryLarge', $this->averageRating(), 5, $this->memberRating() );
		}
	}
}