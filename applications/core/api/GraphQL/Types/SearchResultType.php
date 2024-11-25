<?php
/**
 * @brief		Union type for search results
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Sep 2018
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\UnionType;
use IPS\core\api\GraphQL\TypeRegistry;
use IPS\Member;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Search result union type
 */
class SearchResultType extends UnionType
{
	public function __construct()
	{
		$config = [
			'name' => 'core_SearchResult',
			'description' => 'A union type that returns either a member, or a content search result',
			'types' => [
				TypeRegistry::member(),
				TypeRegistry::contentSearchResult()
			],
			'resolveType' => function ($result) {
				if ( $result instanceof Member )
				{
					return TypeRegistry::member();
				}
				else
				{
					return TypeRegistry::contentSearchResult();
				}
			}
		];

		parent::__construct($config);
	}
}