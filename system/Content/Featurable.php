<?php
/**
 * @brief		Featurable Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 May 2023
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Feature;
use IPS\File;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use function class_implements;
use function get_called_class;
use function in_array;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

trait Featurable
{
	/**
	 * Can add this object be featured?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	boolean
	 */
	public function canFeature( ?Member $member=NULL ) : bool
	{
		if( !$this->actionEnabled( 'feature', $member ) )
		{
			return false;
		}

		/* Is this the first comment in an item that requires first comments? */
		if ( $this instanceof Comment )
		{
			$item = $this->item();
			if ( $item::$firstCommentRequired and $this->isFirst() )
			{
				/* Yeah, so do not allow it to be featured, as we want them to feature via the Item Actions menu */
				return false;
			}
		}

		/* Do we have moderator permission to feature stuff in the container? */
		if ( static::modPermission( 'feature', $member, ( $this instanceof Item ) ? $this->containerWrapper() : $this->item()->containerWrapper() ) )
		{
			return true;
		}

		return false;
	}

	/**
	 * Determines if the object is already featured
	 *
	 * @return bool
	 */
	public function isFeatured() : bool
	{
		if ( isset( static::$databaseColumnMap['featured'] ) )
		{
			$featuredColumn = static::$databaseColumnMap['featured'];
			return (bool) $this->$featuredColumn;
		}
		else
		{
			try
			{
				$idColumn = static::$databaseColumnId;
				if ( Feature::loadByClassAndId( get_called_class(), $this->$idColumn ) )
				{
					return true;
				}
			}
			catch ( OutOfRangeException )
			{
			}
		}

		return FALSE;
	}

	/**
	 * Set the featured state
	 *
	 * @param bool $featured
	 * @return bool
	 */
	public function setFeatured( bool $featured ) : bool
	{
		if( isset( static::$databaseColumnMap['featured'] ) )
		{
			$featuredColumn = static::$databaseColumnMap['featured'];
			$featured = (int) $featured;

			$this->$featuredColumn = $featured;
			$this->save();
		}

		return true;
	}

	/**
	 * Get featured items
	 *
	 * @param int $limit Number to get
	 * @param string $order MySQL ORDER BY clause
	 * @param bool $thisClassOnly
	 * @return    ActiveRecordIterator
	 */
	public static function featured( $limit=10, $order='RAND()', $thisClassOnly=true ): ActiveRecordIterator
	{
		$where = [ [ 'promote_hide=0' ] ];

		if ( $thisClassOnly )
		{
			$where[] = [ 'promote_class=?', get_called_class() ];
		}

		$where = array( array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['featured'] . '=?', 1 ) );


		if ( in_array( 'IPS\Content\FuturePublishing', class_implements( get_called_class() ) ) )
		{
			$where[] = array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['is_future_entry'] . '=?', 0 );
		}

		return static::getItemsWithPermission( $where, $order, $limit );
	}

	/**
	 * Return the featured image for this content
	 *
	 * @return File|null
	 */
	public function featuredImage() : ?File
	{
		$idColumn = static::$databaseColumnId;
		if( $promote = Feature::loadByClassAndId( get_class( $this ), $this->$idColumn ) )
		{
			$images = $promote->imageObjects();
			if( is_array( $images ) and count( $images ) )
			{
				return $images[0];
			}
		}

		return null;
	}
}