<?php
/**
 * @brief		GraphQL: Search Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Sep 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\modules\front\search\search;
use IPS\Member;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * SearchType for GraphQL API
 */
class SearchType extends ObjectType
{
	/**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_Search',
			'description' => 'Search results',
			'fields' => function () {
				return [
					'count' => [
						'type' => TypeRegistry::int(),
						'description' => "Total number of results",
						'resolve' => function ($search) {
							return $search['count'];
						}
					],
					'results' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::searchResult() ),
						'description' => "List of items in this stream",
						'resolve' => function ($search, $args, $context) {
							return $search['results'];
						}
					],
					'types' => [
						'type' => TypeRegistry::listOf( new ObjectType([
							'name' => 'core_search_types',
							'description' => "The available search types",
							'fields' => [
								'key' => TypeRegistry::string(),
								'lang' => TypeRegistry::string()
							],
							'resolveField' => function ($type, $args, $context, $info) {
								switch( $info->fieldName )
								{
									case 'key':
										return $type;

									case 'lang':
										return Member::loggedIn()->language()->get( $type . '_pl' );

								}

								return null;
							}
						]) ),
						'resolve' => function () {
							return array_merge( array('core_members'), array_keys( search::contentTypes() ) );
						}
					]
				];
			}
		];

		parent::__construct($config);
	}
}
