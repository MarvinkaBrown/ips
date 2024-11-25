<?php
/**
 * @brief		GraphQL: Club Node Type
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
use IPS\Http\Url\Friendly;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ClubNodeType for GraphQL API
 */
class ClubNodeType extends ObjectType
{
    /**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_ClubNode',
			'description' => 'Club Nodes',
			'fields' => function () {
				return [
					'id' => [
						'type' => TypeRegistry::id(),
						'description' => "Node ID",
						'resolve' => function ($node) {
							return $node['node_id'];
						}
					],
					'name' => [
						'type' => TypeRegistry::string(),
						'description' => "Node name",
						'resolve' => function ($node) {
							return $node['name'];
						}
					],
					'seoTitle' => [
						'type' => TypeRegistry::string(),
						'description' => "Node's SEO title",
						'resolve' => function ($node) {
							return Friendly::seoTitle( $node['name'] );
						}
					],
					'type' => [
						'type' => TypeRegistry::string(),
						'description' => "Node type (from classname)",
						'resolve' => function ($node) {
							return str_replace('\\', '_', str_replace('IPS\\', '', $node['node_class'] ) );
						}
					]
				];
			}
		];

        parent::__construct($config);
	}
}
