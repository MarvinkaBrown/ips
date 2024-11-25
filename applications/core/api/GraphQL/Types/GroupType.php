<?php
/**
 * @brief		GraphQL: Group Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Db;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * GroupType for GraphQL API
 */
class GroupType extends ObjectType
{
    /**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_Group',
			'description' => 'Member groups',
			'fields' => function () {
				return [
					'id' => [
						'type' => TypeRegistry::int(),
						'description' => "Group ID",
						'resolve' => function ($group) {
							return $group->g_id;
						}
					],
					'groupType' => [
						'type' => TypeRegistry::eNum([
							'name' => 'groupType',
							'values' => ['GUEST', 'MEMBER', 'ADMIN']
						]),
						'description' => "Is this a guest, member or admin group?",
						'resolve' => function ($group) {
							if( $group->g_id == Settings::i()->guest_group )
							{
								return 'GUEST';
							}
							elseif( isset( Member::administrators()['g'][ $group->g_id ] ) )
							{
								return 'ADMIN';
							}
							else
							{
								return 'MEMBER';
							}
						}
					],
					'name' => [
						'type' => TypeRegistry::string(),
						'description' => "Group name",
						'args' => [
							'formatted' => [
								'type' => TypeRegistry::boolean(),
								'defaultValue' => FALSE
							]
						],
						'resolve' => function ($group, $args) {
							return ( $args['formatted'] ) ? $group->get_formattedName() : $group->name;
						}
					],
					'canAccessSite' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Can users in this group access the site?",
						'resolve' => function ($group) {
							return $group->g_view_board;
						}
					],
					'canAccessOffline' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Can users in this group access the site when it's offline?",
						'resolve' => function ($group) {
							return $group->g_access_offline;
						}
					],
					'canTag' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Can users in this group tag content (if enabled)?",
						'resolve' => function ($group) {
							return ( !$group->g_id || !Settings::i()->tags_enabled ) ? FALSE : !( $group->g_bitoptions['gbw_disable_tagging'] );
						}
					],
					'maxMessengerRecipients' => [
						'type' => TypeRegistry::int(),
						'description' => "Maximum number of recipients to a PM sent by a member in this group",
						'resolve' => function ($group) {
							return Member::loggedIn()->group['g_max_mass_pm'];
						}
					],
					'members' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::member() ),
						'description' => "List of members in this group",
						'args' => [
							'offset' => [
								'type' => TypeRegistry::int(),
								'defaultValue' => 0
							],
							'limit' => [
								'type' => TypeRegistry::int(),
								'defaultValue' => 25
							]
						],
						'resolve' => function ($group, $args) {
							/* If we don't allow filtering by this group, don't return the members in it */
							if( $group->g_bitoptions['gbw_hide_group'] )
							{
								return NULL;
							}

							$offset = max( $args['offset'], 0 );
							$limit = min( $args['limit'], 50 );
							return new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array('member_group_id=?', $group->g_id), NULL, array( $offset, $limit ) ), 'IPS\Member' );
						}
					]
				];
			}
		];

		parent::__construct($config);  
	}
}
