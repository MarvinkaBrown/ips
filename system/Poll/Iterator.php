<?php
/**
 * @brief		Poll Votes Filter Iterator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	
 * @since		21 Aug 2014
 */

namespace IPS\Poll;

/* To prevent PHP errors (extending class does not exist) revealing path */

use FilterIterator;
use IPS\Patterns\ActiveRecordIterator;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Poll Votes Filter Iterator
 * @note	When we require PHP 5.4+ this can just be replaced with a CallbackFilterIterator
 */
class Iterator extends FilterIterator
{
	/**
	 * @brief	Question
	 */
	protected ?int $question = NULL;
	
	/**
	 * @brief	Option
	 */
	protected ?int $option = NULL;
	
	/**
	 * Constructor
	 *
	 * @param	ActiveRecordIterator	$iterator	Iterator
	 * @param	int|null							$question	Question
	 * @param int|null $option		Option
	 * @return	void
	 */
	public function __construct(ActiveRecordIterator $iterator, int $question=NULL, int $option=NULL )
	{
		$this->question	= $question;
		$this->option	= $option;
		parent::__construct( $iterator );
	}
	
	/**
	 * Does this rule apply?
	 *
	 * @return	bool
	 */
	public function accept() : bool
	{	
		$row = $this->getInnerIterator()->current();
		
		if ( is_array( $row->member_choices[ $this->question ] ) )
		{
			return in_array( $this->option, $row->member_choices[ $this->question ] );
		}
		
		return $row->member_choices[ $this->question ] == $this->option;
	}
}