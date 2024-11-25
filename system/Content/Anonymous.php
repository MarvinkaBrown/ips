<?php
/**
 * @brief		Anonymous Trait for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Jun 2020
 */

namespace IPS\Content;

use IPS\Member;
use IPS\Db;
use IPS\IPS;
use BadMethodCallException;
use UnderflowException;

use function get_class;
use function defined;
use function header;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Anonymous Trait for Content Models/Comments
 */
trait Anonymous
{
	/**
	 * Is this an anonymous entry?
	 *
	 * @return bool
	 */
	public function isAnonymous(): bool
	{
		if ( $this instanceof Comment AND $this->isFirst() )
		{
			if( IPS::classUsesTrait( $this->item(), 'IPS\Content\Anonymous' ) )
			{
				return $this->item()->isAnonymous();
			}

			return FALSE;
		}
		else
		{
			return (bool) $this->mapped('is_anon');
		}
	}
	
	/**
	 * Set anonymous state
	 *
	 * @param	bool				$state		The state, TRUE for anonymous FALSE for not
	 * @param	Member|null 	$member		The member posting anonymously or NULL for logged in member
	 * @return	void
	 */
	public function setAnonymous( bool $state = TRUE, ?Member $member = NULL ): void
	{
		if( $state == $this->isAnonymous() )
		{
			return;
		}

		$class = get_class( $this );
		$idColumn = static::$databaseColumnId;
		$anonColumn = static::$databaseColumnMap['is_anon'];

		if( !$state )
		{
			try
			{
				$originalAuthor = Db::i()->select( 'anonymous_member_id', 'core_anonymous_posts', array( 'anonymous_object_class=? and anonymous_object_id=?', $class, $this->$idColumn ) )->first();

				$member = Member::load( $originalAuthor );
			}
			catch ( UnderflowException )
			{
				$member = NULL;
			}
		}

		$member = $member ?: Member::loggedIn();

		if( $state and !$this->container()->canPostAnonymously( 0, $member ) )
		{
			throw new BadMethodCallException();
		}

		if( $state )
		{
			/* Insert the anonymous map */
			$save = array(
				'anonymous_member_id'				=> ( $this->author()->member_id ) ? $this->author()->member_id : $member->member_id,
				'anonymous_object_class'			=> $class,
				'anonymous_object_id'				=> $this->$idColumn
			);

			Db::i()->replace( 'core_anonymous_posts', $save );

			$member = new Member;
			$this->$anonColumn = 1;
		}
		else
		{
			Db::i()->delete( 'core_anonymous_posts', array( 'anonymous_object_class=? and anonymous_object_id=?', $class, $this->$idColumn ) );

			$this->$anonColumn = 0;
		}

		/* @todo only run the rest of the code here if the $anonColumn state is different from original. Waste of processing otherwise */
		$this->save();
		$this->changeAuthor( $member, FALSE );

		if( $this instanceof Comment )
		{
			$this->item()->rebuildFirstAndLastCommentData();
		}

		$this->expireWidgetCaches();
	}
}