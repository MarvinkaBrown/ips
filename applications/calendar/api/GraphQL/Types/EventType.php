<?php
/**
 * @brief		GraphQL: Topic Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright		(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\calendar\Event;
use IPS\Content\Api\GraphQL\ItemType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0').' 403 Forbidden' );
	exit;
}

/**
 * Entry for GraphQL API
 */
class EventType extends ItemType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $itemClass = Event::class;

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'calendar_Event';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'A calendar event';

	/*
	 * @brief 	Follow data passed in to FollowType resolver
	 */
	protected static array $followData = array( 'app' => 'calendar', 'area' => 'event' );

	/**
	 * Get the comment type that goes with this item type
	 *
	 * @return    ObjectType
	 */
	protected static function getCommentType(): ObjectType
	{
		return \IPS\calendar\api\GraphQL\TypeRegistry::comment();
	}

	/**
	 * Return the fields available in this type
	 *
	 * @return    array
	 */
	public function fields(): array
	{
		// Extend our fields with image-specific stuff
		$defaultFields = parent::fields();
		$imageFields = array(
		'start' => [
		'type' => TypeRegistry::string(),
		'resolve' => function( $event ){
			return $event->start_date;
		}
		]

		);

		// Remove duplicated fields
		unset( $defaultFields[ 'poll' ], $defaultFields[ 'isLocked' ], $defaultFields[ 'isPinned' ], $defaultFields[ 'isFeatured' ] );

		return array_merge( $defaultFields, $imageFields );
	}

	/**
	 * Return the sort options available for this type
	 * 
	 * @return array|string[]
	 */
	public static function getOrderByOptions(): array
	{
		return array_merge( parent::getOrderByOptions(), array(
			'saved','last_comment','start_date'
		) );
	}
}
