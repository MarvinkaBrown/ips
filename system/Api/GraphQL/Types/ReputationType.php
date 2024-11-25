<?php
/**
 * @brief		GraphQL: Reputation type defintiion
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content;
use IPS\Db;
use IPS\Member;
use IPS\Settings;
use function defined;
use function get_class;
use function intval;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ReputationType for GraphQL API
 */
class ReputationType extends ObjectType
{
	/**
	 * Get root type
	 *
	 */
	public function __construct()
	{		 
		$config = [
			'name' => 'Reputation',
			'description' => 'Returns the reputation',
			'fields' => [
				'reactionCount' => [
					'type' => TypeRegistry::int(),
					'resolve' => function ($content, $args) {
						if( !Settings::i()->reputation_enabled )
						{
							return 0;
						}

						return $content->reactionCount();
					}
				],
				'canViewReps' => [
					'type' => TypeRegistry::boolean(),
					'resolve' => function ($content, $args) {
						return Settings::i()->reputation_enabled && Member::loggedIn()->group['gbw_view_reps'];
					}
				],
				'canReact' => [
					'type' => TypeRegistry::boolean(),
					'resolve' => function ($content, $args) {
						return Settings::i()->reputation_enabled && $content->canReact();
					}
				],
				'hasReacted' => [
					'type' => TypeRegistry::boolean()
				],
				'isLikeMode' => [
					'type' => TypeRegistry::boolean(),
					'resolve' => function ($content, $args) {
						return Settings::i()->reputation_enabled && Content\Reaction::isLikeMode();
					}
				],
				'givenReaction' => [
					'type' => \IPS\core\api\GraphQL\TypeRegistry::contentReaction()
				],
				'defaultReaction' => [
					'type' => \IPS\core\api\GraphQL\TypeRegistry::contentReaction(),
				],
				'availableReactions' => [
					'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::contentReaction() )
				],
				'reactions' => [
					'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::contentReaction() ),
					'resolve' => function ($content) {
						if( !Settings::i()->reputation_enabled )
						{
							return NULL;
						}

						$return = array();
						$idColumn = $content::$databaseColumnId;

						foreach( $content->reactBlurb() as $key => $count )
						{
							$return[] = array( 'id' => md5( get_class( $content ) . '-' . $content->$idColumn ) . '-' . $key, 'reactionId' => $key, 'count' => $count );
						}

						return $return;
					}
				],
				'whoReacted' => [
					'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::member() ),
					'args' => [
						'id' => TypeRegistry::int(),
						'offset' => [
							'type' => TypeRegistry::int(),
							'defaultValue' => 0
						],
						'limit' => [
							'type' => TypeRegistry::int(),
							'defaultValue' => 25
						]
					],
					'resolve' => function ($content, $args) {
						if( !Settings::i()->reputation_enabled || !Member::loggedIn()->group['gbw_view_reps'] )
						{
							return NULL;
						}

						$reaction = NULL;
						$members = array();
						$offset = max( $args['offset'], 0 );
						$limit = min( $args['limit'], 50 );

						if( isset( $args['id'] ) )
						{
							$reaction = intval( $args['id']);
						}

						foreach( Db::i()->select( '*', 'core_reputation_index', $content->getReactionWhereClause( $reaction ), 'rep_date desc', array( $offset, $limit ) )->join( 'core_reactions', 'reaction=reaction_id' ) AS $reaction )
						{
							$members[] = Member::load( $reaction['member_id'] );
						}

						return $members;
					}
				]
			],
			'resolveField' => function ($content, $args, $context, $info) {
				if( !Settings::i()->reputation_enabled )
				{
					return NULL;
				}

				$enabledReactions = Content\Reaction::enabledReactions();
				$defaultReaction = reset( $enabledReactions );
				$reacted = $content->reacted();

				switch( $info->fieldName )
				{
					case 'hasReacted':
						return ( $reacted and isset( $enabledReactions[ $reacted->id ] ) );

					case 'givenReaction':
						if( !$reacted )
						{
							return NULL;
						}

						$reaction = $enabledReactions[$reacted->id] ?? $defaultReaction;
						return array( 
							'id' => $reaction->id,
							'reaction' => $reaction
						);

					case 'defaultReaction':
						return array( 
							'id' => $defaultReaction->id,
							'reaction' => $defaultReaction
						);

					case 'availableReactions':
						$reactions = array();

						foreach( $enabledReactions as $reaction )
						{
							$reactions[] = array(
								'id' => $reaction->id,
								'reaction' => $reaction
							);
						}

						return $reactions;

				}

				return null;
			}
		];

		parent::__construct( $config );
	}
}
