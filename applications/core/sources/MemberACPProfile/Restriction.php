<?php
/**
 * @brief		Member Restrictions Extension Base
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Nov 2017
 */

namespace IPS\core\MemberACPProfile;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member Restrictions Extension Base
 */
class Restriction
{
	/**
	 * @brief	Member
	 */
	protected ?Member $member = null;
	
	/**
	 * Constructor
	 *
	 * @param	Member	$member	The member
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		$this->member = $member;
	}
	
	/**
	 * Is this extension available?
	 *
	 * @return	bool
	 */
	public function enabled() : bool
	{
		return TRUE;
	}
	
	/**
	 * Modify Edit Restrictions form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form $form ): void
	{

	}
	
	/**
	 * Save Form
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function save( array $values ) : array
	{
		return $values;
	}
	
	/**
	 * What restrictions are active on the account?
	 *
	 * @return	array
	 */
	public function activeRestrictions() : array
	{
		return array();
	}
}