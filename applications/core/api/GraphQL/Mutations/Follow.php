<?php
/**
 * @brief		GraphQL: Follow something mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		9 Sep 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Mutations;
use InvalidArgumentException;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Api\GraphQL\Types\FollowType;
use IPS\Application;
use IPS\Content;
use IPS\core\Followed\Follow as FollowClass;
use IPS\Db;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Notification;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function get_class;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Follow something mutation for GraphQL API
 */
class Follow
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Follow a node, item or member";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'app' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'area' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'id' => TypeRegistry::nonNull( TypeRegistry::id() ),
			'anonymous' => TypeRegistry::nonNull( TypeRegistry::boolean() ),
			'type' => TypeRegistry::nonNull( TypeRegistry::eNum([
				'name' => 'core_Follow_followOptions',
				'values' => ['IMMEDIATE', 'WEEKLY', 'DAILY', 'NONE'],
				'defaultValue' => 'IMMEDIATE'
			]))
		];
	}

	/**
	 * Return the mutation return type
	 *
	 * @return FollowType
	 */
	public function type() : FollowType
	{
		return TypeRegistry::follow();
	}

	/**
	 * Resolves this mutation
	 * @todo this is basically copied and pasted from notifications.php which isn't ideal, so we 
	 * might want to consider refactoring to abstract this functionality.
	 *
	 * @param 	mixed $val	Value passed into this resolver
	 * @param 	array $args	Arguments
	 * @return	array
	 */
	public function resolve( mixed $val, array $args ) : array
	{
		if( !Member::loggedIn()->member_id )
		{
			throw new SafeException( 'NOT_LOGGED_IN', 'GQL/0001/6', 403 );
		}
		/* Get class */
		try
		{
			$class = FollowClass::getClassToFollow( $args['app'], $args['area'] );
		}
		catch( InvalidArgumentException $e )
		{
			throw new SafeException( 'NOT_FOUND', 'GQL/0001/4', 404 );
		}

		if( $args['app'] == 'core' and $args['area'] == 'member' )
		{
			/* You can't follow yourself */
			if( $args['id'] == Member::loggedIn()->member_id )
			{
				throw new SafeException( 'CANT_FOLLOW_SELF', 'GQL/0001/1', 403 );
			}
			
			/* Following disabled */
			$member = Member::load( $args['id'] );

			if( !$member->member_id )
			{
				throw new SafeException( 'CANT_FOLLOW_MEMBER', 'GQL/0001/2', 403 );
			}

			if( $member->members_bitoptions['pp_setting_moderate_followers'] and !Member::loggedIn()->following( 'core', 'member', $member->member_id ) )
			{
				throw new SafeException( 'CANT_FOLLOW_MEMBER', 'GQL/0001/3', 403 );
			}
		}

		/* Get our return info ready */
		$return = array(
			'app' => $args['app'],
			'area' => $args['area'],
			'id' => $args['id']
		);
		
		/* Get thing */
		$thing = NULL;
		try
		{
			if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				/* @var Model $class */
				$thing = $class::loadAndCheckPerms( (int) $args['id'] );
			}
			elseif ( $class == 'IPS\Member\Club' )
			{
				/* @var Member\Club $class */
				$thing = $class::loadAndCheckPerms( (int) $args['id'] );
			}
			elseif ( $class != "IPS\Member" )
			{
				/* @var Content $class */
				$thing = $class::loadAndCheckPerms( (int) $args['id'] );
			}
			else 
			{
				if( !IPS::classUsesTrait( $class, 'IPS\Content\Followable' ) )
				{
					throw new OutOfRangeException;
				}

				/* @var Member $class */
				$thing = $class::load( (int) $args['id'] );
			}
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NOT_FOUND', 'GQL/0001/5', 404 );
		}

		/* Do we follow it? */
		try
		{
			$current = Db::i()->select( '*', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=? AND follow_member_id=?', $args['app'], $args['area'], $args['id'], Member::loggedIn()->member_id ) )->first();
		}
		catch ( UnderflowException $e )
		{
			$current = FALSE;
		}

		/* Insert */
		$save = array(
			'follow_id'				=> md5( $args['app'] . ';' . $args['area'] . ';' . $args['id'] . ';' .  Member::loggedIn()->member_id ),
			'follow_app'			=> $args['app'],
			'follow_area'			=> $args['area'],
			'follow_rel_id'			=> $args['id'],
			'follow_member_id'		=> Member::loggedIn()->member_id,
			'follow_is_anon'		=> $args['anonymous'],
			'follow_added'			=> time(),
			'follow_notify_do'		=> $args['type'] == 'NONE' ? 0 : 1,
			'follow_notify_meta'	=> '',
			'follow_notify_freq'	=> ( $class == "IPS\Member" ) ? 'immediate' : mb_strtolower($args['type']),
			'follow_notify_sent'	=> 0,
			'follow_visible'		=> 1,
		);
		if ( $current )
		{
			Db::i()->update( 'core_follow', $save, array( 'follow_id=?', $current['follow_id'] ) );
		}
		else
		{
			Db::i()->insert( 'core_follow', $save );
		}

		/* Also follow all nodes if following club */
		if( $class == "IPS\Member\Club"  )
		{
			foreach ( $thing->nodes() as $node )
			{
				$itemClass = $node['node_class']::$contentItemClass;
				$followApp = $itemClass::$application;
				$followArea = mb_strtolower( mb_substr( $node['node_class'], mb_strrpos( $node['node_class'], '\\' ) + 1 ) );
				
				$save = array(
					'follow_id'				=> md5( $followApp . ';' . $followArea . ';' . $node['node_id'] . ';' .  Member::loggedIn()->member_id ),
					'follow_app'			=> $followApp,
					'follow_area'			=> $followArea,
					'follow_rel_id'			=> $node['node_id'],
					'follow_member_id'		=> Member::loggedIn()->member_id,
					'follow_is_anon'		=> $args['anonymous'],
					'follow_added'			=> time(),
					'follow_notify_do'		=> $args['type'] == 'NONE' ? 0 : 1,
					'follow_notify_meta'	=> '',
					'follow_notify_freq'	=> mb_strtolower($args['type']),
					'follow_notify_sent'	=> 0,
					'follow_visible'		=> 1,
				);
				Db::i()->insert( 'core_follow', $save, TRUE );
			}
		}
		
		/* Send notification if following member */
		if( $class == "IPS\Member"  )
		{
			$notification = new Notification( Application::load( 'core' ), 'member_follow', Member::loggedIn(), array( Member::loggedIn() ) );
			$notification->recipients->attach( $thing );
			$notification->send();
		}

		

		if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
		{
			$return = array_merge($return, array(
				'node' => $thing,
				'nodeClass' => get_class( $thing )
			));
		}
		else if( $class == 'IPS\Member' )
		{
			$return = array_merge($return, array(
				'member' => $thing
			));
		}
		else if( $class == 'IPS\Member\Club' )
		{
			// @future Support club follows
		}
		else
		{
			$return = array_merge($return, array(
				'item' => $thing,
				'itemClass' => get_class( $thing )
			));
		}

		return $return;
	}
}
