<?php

/**
 * @brief        GroupFormAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/17/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Member\Group;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class GroupFormAbstract
{
	/**
	 * Process Form
	 *
	 * @param	Form		$form	The form
	 * @param	Group		$group	Existing Group
	 * @return	void
	 */
	abstract public function process( Form $form, Group $group ) : void;

	/**
	 * Save
	 *
	 * @param	array	$values	Values from form
	 * @param	Group	$group	The group
	 * @return	void
	 */
	abstract public function save( array $values, Group $group ) : void;

	/**
	 * Can this group be deleted?
	 *
	 * @param	Group	$group	The group
	 * @return	bool
	 */
	public function canDelete( Group $group ): bool
	{
		return TRUE;
	}
}