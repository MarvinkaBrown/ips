<?php
/**
 * @brief		GraphQL: Content query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use Exception;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Application;
use IPS\Content as ContentClass;
use IPS\Content\Api\GraphQL\ContentType;
use IPS\Http\Url;
use IPS\Member;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Me query for GraphQL API
 */
class Content
{

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Return a generic piece of content";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'url' => TypeRegistry::nonNull( TypeRegistry::string() )
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() : ContentType
	{
		return \IPS\Content\Api\GraphQL\TypeRegistry::content();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return    ContentClass|null
	 */
	public function resolve( mixed $val, array $args ) : ?ContentClass
	{
		$url = Url::createFromString( $args['url'] );

		if ( !isset( $url->hiddenQueryString['app'] ) )
		{
			throw new Exception('Not a valid URL');
		}

		foreach ( Application::load( $url->hiddenQueryString['app'] )->extensions( 'core', 'ContentRouter' ) as $ext )
		{
			foreach ( $ext->classes as $class )
			{
				try
				{
					$object = $class::loadFromUrl( $url );

					if ( isset( $url->queryString['do'] ) and $url->queryString['do'] === 'findComment' )
					{
						$commentClass = $object::$commentClass;
						$object = $commentClass::load( $url->queryString['comment'] );
					}
					elseif ( isset( $url->queryString['do'] ) and $url->queryString['do'] === 'findReview' )
					{
						$reviewClass = $object::$reviewClass;
						$object = $reviewClass::load( $url->queryString['review'] );
					}

					if ( !$object->canView( Member::loggedIn() ) )
					{
						// No permission
						return NULL;
					}

					return $object;
				}
				catch (Exception $e)
				{}
			}
		}

		return NULL;
	}
}
