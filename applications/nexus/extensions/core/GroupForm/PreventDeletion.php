<?php
/**
 * @brief		Admin CP Group Form
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		12 Oct 2021
 */

namespace IPS\nexus\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\GroupFormAbstract;
use IPS\Helpers\Form;
use IPS\Member\Group;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Group Form
 */
class PreventDeletion extends GroupFormAbstract
{
	/**
	 * Process Form
	 *
	 * @param	Form		$form	The form
	 * @param	Group		$group	Existing Group
	 * @return	void
	 */
	public function process( Form $form, Group $group ): void
	{		

	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	Group	$group	The group
	 * @return	void
	 */
	public function save( array $values, Group $group ) : void
	{

	}

	/**
	 * Can this group be deleted?
	 *
	 * @param	Group	$group	The group
	 * @return	bool
	 */
	public function canDelete( Group $group ) : bool
	{
		// Is this group used for group promotion after a product purchase?
		try
		{
			Db::i()->select( '*', 'nexus_packages', ['p_primary_group=? OR ' . Db::i()->findInSet('p_secondary_group', [$group->g_id] ), $group->g_id ] )->first();
			return FALSE;
		}
		catch(UnderflowException )
		{
		}

		// Is this group used for group promotion after a subscription purchase?
		try
		{
			Db::i()->select( '*', 'nexus_member_subscription_packages', ['sp_primary_group=? OR ' . Db::i()->findInSet('sp_secondary_group', [$group->g_id] ), $group->g_id ] )->first();
			return FALSE;
		}
		catch(UnderflowException )
		{
		}

		return TRUE;
	}
}