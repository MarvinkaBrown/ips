<?php
/**
 * @brief		Member Restrictions: Downloads
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Nov 2017
 */

namespace IPS\downloads\extensions\core\MemberRestrictions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\Restriction;
use IPS\Helpers\Form;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member Restrictions: Downloads
 */
class Downloads extends Restriction
{
	/**
	 * Modify Edit Restrictions form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form $form ) : void
	{
		$form->add( new YesNo( 'idm_block_submissions', !$this->member->idm_block_submissions ) );
	}
	
	/**
	 * Save Form
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function save( array $values ): array
	{
		$return = array();
		
		if ( $this->member->idm_block_submissions == $values['idm_block_submissions'] )
		{
			$return['idm_block_submissions'] = array( 'old' => $this->member->idm_block_submissions, 'new' => !$values['idm_block_submissions'] );
			$this->member->idm_block_submissions = !$values['idm_block_submissions'];
		}
		
		return $return;
	}
	
	/**
	 * What restrictions are active on the account?
	 *
	 * @return	array
	 */
	public function activeRestrictions(): array
	{
		$return = array();
		
		if ( $this->member->idm_block_submissions )
		{
			$return[] = 'restriction_no_downloads';
		}
		
		return $return;
	}

	/**
	 * Get details of a change to show on history
	 *
	 * @param	array	$changes	Changes as set in save()
	 * @param   array   $row        Row of data from member history table.
	 * @return	array
	 */
	public static function changesForHistory( array $changes, array $row ): array
	{
		if ( isset( $changes['idm_block_submissions'] ) )
		{
			return array( Member::loggedIn()->language()->addToStack( 'history_restrictions_downloads_' . intval( $changes['idm_block_submissions']['new'] ) ) );
		}
		return array();
	}
}