<?php
/**
 * @brief		Poll Vote Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jan 2014
 */

namespace IPS\Poll;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Poll;
use IPS\Request;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Poll Vote Model
 */
class Vote extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_voters';
	
	/**
	 * @brief	Database ID Column
	 */
	public static string $databaseColumnId = 'vid';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'member_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * Create from form
	 *
	 * @param	array|NULL	$values	Form values
	 * @return    Vote
	 */
	public static function fromForm( array|null $values ): Vote
	{
		$vote = new static;
		$vote->member_id = Member::loggedIn()->member_id;
		if ( $values )
		{
			$vote->member_choices = $values;
		}
		$vote->ip_address = Request::i()->ipAddress();
		return $vote;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->vote_date = new DateTime;
	}
	
	/**
	 * Set vote date
	 *
	 * @param	DateTime	$value	Value
	 * @return	void
	 */
	public function set_vote_date( DateTime $value ) : void
	{
		$this->_data['vote_date'] = $value->getTimestamp();
	}
	
	/**
	 * Get vote date
	 *
	 * @return	DateTime
	 */
	public function get_vote_date(): DateTime
	{
		return DateTime::ts( $this->_data['vote_date'] );
	}
	
	/**
	 * Set choices
	 *
	 * @param	array	$value	Value
	 * @return	void
	 */
	public function set_member_choices( array $value ) : void
	{
		$this->_data['member_choices'] = json_encode( $value );
	}
	
	/**
	 * Get choices
	 *
	 * @return	array|null
	 */
	public function get_member_choices(): ?array
	{
		return isset( $this->_data['member_choices'] ) ? json_decode( $this->_data['member_choices'], TRUE ) : NULL;
	}
	
	/**
	 * Set poll
	 *
	 * @param	Poll	$value	Value
	 * @return	void
	 */
	public function set_poll( Poll $value ) : void
	{
		$this->_data['poll'] = $value->pid;
	}
	
	/**
	 * Get poll
	 *
	 * @return	Poll
	 */
	public function get_poll(): Poll
	{
		return Poll::load( $this->_data['poll'] );
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		if ( $this->member_choices !== NULL )
		{
			$choices = $this->poll->choices;
			foreach ( $this->member_choices as $k => $v )
			{
				if ( isset( $choices[ $k ] ) ) // If the question has been deleted since this vote was cast, this won't be set
				{
					if ( is_array( $v ) )
					{
						foreach ( $v as $key => $memberValues )
						{
							if ( $choices[ $k ]['votes'][ $memberValues ] > 0 )
							{
								$choices[ $k ]['votes'][ $memberValues ]--;
							}
						}
					}
					else
					{
						if ( $choices[ $k ]['votes'][ $v ] > 0 )
						{
							$choices[ $k ]['votes'][ $v ]--;
						}
					}
				}

			}
			$this->poll->choices = $choices;
			$this->poll->save();
		}
		
		parent::delete();
	}
}