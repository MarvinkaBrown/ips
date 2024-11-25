<?php
/**
 * @brief		5.0.0 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		19 Apr 2023
 */

namespace IPS\nexus\setup\upg_500001;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Lang;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Upgrade Code
 */
class Upgrade
{
	/* @todo at some point when we are sure everyone has conveted their data, we need to handle dropping the following tables
	 * {
	"method": "dropTable",
	"params": [
	"nexus_support_departments",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_fields",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_notify",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_ratings",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_replies",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_requests",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_request_log",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_severities",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_staff_dpt_order",
	true
	]
	},
	{
	"method": "dropTable",
	"params": [
	"nexus_support_staff_preferences",
	true
	]
	},
	 *
	 **/

	/**
	 * Move physical products to a separate package group and make them non-purchasable
	 * @return bool|array
	 */
	public function step1() : bool|array
	{
		/* Move all physical products */
		$physicalProducts = iterator_to_array(
			Db::i()->select( 'p_id', 'nexus_packages_products', array( 'p_physical=?', 1 ) )
		);
		if( count( $physicalProducts ) )
		{
			/* Create new package group */
			$max = (int) Db::i()->select( 'max(pg_position)', 'nexus_package_groups', array( 'pg_parent=?', 0 ) )->first();
			$groupId = Db::i()->insert( 'nexus_package_groups', array( 'pg_parent' => 0, 'pg_position' => ( $max + 1 ) ) );
			Lang::saveCustom( 'nexus', 'nexus_pgroup_' . $groupId, "Legacy Products" );
			Lang::saveCustom( 'nexus', 'nexus_pgroup_' . $groupId . '_desc', "" );

			/* Move all physical products to the legacy group and lock them */
			Db::i()->update( 'nexus_packages', array(
				'p_group' => $groupId,
				'p_store' => 0,
				'p_locked' => 1
			), Db::i()->in( 'p_id', $physicalProducts ) );
		}

        /* Cleanup for removing physical products */
        Db::i()->dropColumn( 'nexus_packages_products', 'p_physical' );

		return TRUE;
	}
}