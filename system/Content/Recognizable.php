<?php
/**
 * @brief		Recognizable Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 March 2021
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Recognize;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Solvable Trait
 */
trait Recognizable
{

	/**
	 * Can this member "unrecognize" the content author?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	boolean
	 */
	public function canRemoveRecognize( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'unrecognize', $member ) )
		{
			return false;
		}

		/* Already recognized? (First quick check when built from item::comments() */
		$isRecognized = FALSE;
		if ( isset( $this->recognized ) and $this->recognized )
		{
			$isRecognized = TRUE;
		}

		/* Not there, try loading from content */
		if ( ! $isRecognized and ( !isset( $this->recognized ) or $this->recognized !== false ) )
		{
			try
			{
				Recognize::loadFromContent( $this );
				$isRecognized = TRUE;
			}
			catch ( OutOfRangeException | UnderflowException ){}
		}

		if ( ! $isRecognized )
		{
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();

		/* Moderator check */
		return $this->canModerateRecognized( $member );
	}

	/**
	 * Can this member "recognize" the content author?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	boolean
	 */
	public function canRecognize( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'recognize', $member ) )
		{
			return false;
		}

		/* Are achievements enabled? */
		if ( !Settings::i()->achievements_enabled )
		{
			return FALSE;
		}
		
		/* Already recognized? */
		if ( isset( $this->recognized ) )
		{
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();

		/* Moderator check */
		if ( ! $this->canModerateRecognized( $member ) )
		{
			return FALSE;
		}

		if ( ! $this->author()->member_id )
		{
			return FALSE;
		}

		/* Can not recognize yourself */
		if ( $member->member_id === $this->author()->member_id )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Check to see if we have moderator permissions
	 *
	 * @param Member|null $member
	 * @return bool
	 */
	public function canModerateRecognized( ?Member $member ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* Moderator check */
		if ( ! $member->modPermission('can_recognize_content') )
		{
			return FALSE;
		}

		if ( $member->modPermission('can_recognize_content') != '*' AND ! count( $member->modPermission('can_recognize_content_options') ) )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Should we show this recognized data?
	 *
	 * @param Member|null $viewing
	 * @return bool
	 */
	public function showRecognized( ?Member $viewing=NULL ): bool
	{
		$viewing = $viewing ?: Member::loggedIn();
		if ( isset( $this->recognized ) and $this->recognized )
		{
			if ( $this->recognized->public or ( ( $viewing->member_id == $this->recognized->member_id ) OR ( $viewing->modPermission('can_recognize_content') ) ) )
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Remove recognition given to this content
	 *
	 * @return void
	 */
	public function removeRecognize(): void
	{
		Recognize::loadFromContent( $this )->delete();
	}

	/**
	 * Return a blurb about this
	 *
	 * @return array
	 */
	public function recognizedBlurb(): array
	{
		if( !isset( $this->recognized ) or $this->recognized === false )
		{
			return [];
		}

		$return = [ 'message' => NULL ];
		$return['main'] = Member::loggedIn()->language()->addToStack( 'recognize_blurb_main', FALSE, [ 'htmlsprintf' => [ Member::loggedIn()->language()->addToStack( $this::$title . '_lc' ), $this->recognized->_given_by->link() ] ] );
		if ( $this->recognized->badge and $this->recognized->points )
		{
			if ( $this->author()->member_id === Member::loggedIn()->member_id )
			{
				$return['awards'] = Member::loggedIn()->language()->addToStack( 'recognize_blurb_awarded_points_and_badge', FALSE, ['htmlsprintf' => [$this->recognized->badge()->_title, $this->recognized->points]] );
			}
			else
			{
				$return['awards'] = Member::loggedIn()->language()->addToStack( 'recognize_blurb_awarded_points_and_badge_third', FALSE, ['htmlsprintf' => [$this->author()->name, $this->recognized->badge()->_title, $this->recognized->points]] );
			}
		}
		else if ( $this->recognized->points )
		{
			if ( $this->author()->member_id === Member::loggedIn()->member_id )
			{
				$return['awards'] = Member::loggedIn()->language()->addToStack( 'recognize_blurb_awarded_points', FALSE, [ 'sprintf' => [ $this->recognized->points ] ] );
			}
			else
			{
				$return['awards'] = Member::loggedIn()->language()->addToStack( 'recognize_blurb_awarded_points_third', FALSE, [ 'sprintf' => [ $this->author()->name, $this->recognized->points ] ] );
			}
		}
		else if ( $this->recognized->badge )
		{
			if ( $this->author()->member_id === Member::loggedIn()->member_id )
			{
				$return['awards'] = Member::loggedIn()->language()->addToStack( 'recognize_blurb_awarded_badge', FALSE, [ 'htmlsprintf' => [ $this->recognized->badge()->_title ] ] );
			}
			else
			{
				$return['awards'] = Member::loggedIn()->language()->addToStack( 'recognize_blurb_awarded_badge_third', FALSE, [ 'htmlsprintf' => [ $this->author()->name, $this->recognized->badge()->_title ] ] );
			}
		}

		if ( $this->recognized->message )
		{
			$return['message'] = $this->recognized->message;
		}

		return $return;
	}
}