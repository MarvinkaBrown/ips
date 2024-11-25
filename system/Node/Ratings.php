<?php
/**
 * @brief		Rating Trait for Nodes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Mar 2014
 */

namespace IPS\Node;

use IPS\Member;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Rating;
use IPS\Request;
use IPS\Output;
use IPS\Theme;
use UnderflowException;


use function defined;
use function header;
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
 * Rating Trait for Nodes
 */
trait Ratings
{
	/**
	 * Can Rate?
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canRate( Member|null $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		if( !$member->member_id )
		{
			return false;
		}

		try
		{
			$idColumn = static::$databaseColumnId;
			Db::i()->select( '*', 'core_ratings', array( 'class=? AND item_id=? AND `member`=?', get_called_class(), $this->$idColumn, $member->member_id ) )->first();
			return FALSE;
		}
		catch ( UnderflowException $e )
		{
			return TRUE;
		}
	}

	/**
	 * Get average rating
	 *
	 * @return	int
	 */
	public function averageRating(): int
	{
		if ( isset( static::$ratingColumnMap['rating_average'] ) )
		{
			$column	= static::$ratingColumnMap['rating_average'];
			return $this->$column;
		}
		elseif ( isset( static::$ratingColumnMap['rating_total'] ) and isset( static::$ratingColumnMap['rating_hits'] ) )
		{
			$hits	= static::$ratingColumnMap['rating_hits'];
			$total	= static::$ratingColumnMap['rating_total'];
			return $this->$hits ? round( $this->$total / $this->$hits, 1 ) : 0;
		}
		else
		{
			$idColumn = static::$databaseColumnId;
			return round( Db::i()->select( 'AVG(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first(), 1 );
		}
	}

	/**
	 * Display rating (will just display stars if member cannot rate)
	 *
	 * @return	string
	 */
	public function rating(): string
	{
		if ( $this->canRate() )
		{
			$idColumn = static::$databaseColumnId;

			$form = new Form('rating');
			$form->add( new Rating( 'rating', $this->averageRating() ) );

			if ( $values = $form->values() )
			{
				Db::i()->insert( 'core_ratings', array(
					'class'			=> get_called_class(),
					'item_id'		=> $this->$idColumn,
					'member'		=> Member::loggedIn()->member_id,
					'rating'		=> $values['rating'],
					'ip'			=> Request::i()->ipAddress(),
					'rating_date'	=> time()
				), TRUE );

				if ( isset( static::$ratingColumnMap['rating_average'] ) )
				{
					$column = static::$ratingColumnMap['rating_average'];
					$this->$column = round( Db::i()->select( 'AVG(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first(), 1 );
				}
				if ( isset( static::$ratingColumnMap['rating_total'] ) )
				{
					$column = static::$ratingColumnMap['rating_total'];
					$this->$column = Db::i()->select( 'SUM(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first();
				}
				if ( isset( static::$ratingColumnMap['rating_hits'] ) )
				{
					$column = static::$ratingColumnMap['rating_hits'];
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
			return Theme::i()->getTemplate( 'global', 'core' )->rating( 'veryLarge', $this->averageRating() );
		}
	}
}