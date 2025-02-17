<?php
/**
 * @brief		GraphQL: Popular Contributors Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Member;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PopularContributorsType for GraphQL API
 */
class PopularContributorType extends ObjectType
{
    /**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_PopularContributor',
			'fields' => function () {
				return [
					'rep' => [
						'type' => TypeRegistry::int(),
						'resolve' => function ($contributor) {
							return $contributor['rep'];
						}
					],
					'user' => [
						'type' => \IPS\core\api\GraphQL\TypeRegistry::member(),
						'resolve' => function ($contributor) {
							if( $contributor['member_id'] )
							{
								return Member::load( $contributor['member_id'] );
							}

							return new Member;
						}
					]
				];
			}
		];

        parent::__construct($config);
	}
}
