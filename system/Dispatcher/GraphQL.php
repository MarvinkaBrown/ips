<?php
/**
 * @brief		GraphQL API Dispatcher
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Api\GraphQL as GraphQLApi;
use IPS\Api\OAuthClient;
use IPS\IPS;
use IPS\Member;
use IPS\Request;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;
use const IPS\DEBUG_GRAPHQL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/* Register our GraphQL library */
IPS::$PSR0Namespaces['GraphQL'] = \IPS\ROOT_PATH . "/system/3rd_party/graphql-php";

/**
 * @brief	API Dispatcher
 */
class GraphQL extends Api
{
	/**
	 * @brief Path
	 */
	public ?string $path = '/graphql';
	
	/**
	 * Init
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	public function init() : void
	{		
		try
		{
			/* Check our IP address isn't banned */
			$this->_checkIpAddressIsAllowed();
			
			/* Authenticate */
			$client = NULL;
			$this->_setRawCredentials();
			try
			{
				if ( $this->rawAccessToken )
				{
					$this->_setAccessToken();
					$client = OAuthClient::load( $this->accessToken['client_id'] );

					if( $client->api_access == 'rest' )
					{
						throw new \IPS\Api\Exception( 'NO_GRAPHQL_ACCESS', '3S426_graphql/1', 403 );
					}
					Member::$loggedInMember = Member::load( $this->accessToken['member_id'] );
				}
				else
				{
					$client = OAuthClient::load( $this->rawApiKey );

					/* Check that the API key has access to the GraphQL API */
					if( $client->api_access == 'rest' )
					{
						throw new \IPS\Api\Exception( 'NO_GRAPHQL_ACCESS', '3S426_graphql/1', 403 );
					}
					Member::$loggedInMember = new Member;
				}
			}
			catch ( OutOfRangeException $e )
			{
				throw new \IPS\Api\Exception( 'INVALID_API_KEY', '3S290_graphql/7', 401 );
			}
			
			/* Check that the OAuth client has access to the GraphQL API */
			if( $client->api_access === 'rest' )
			{
				throw new \IPS\Api\Exception( 'NO_GRAPHQL_ACCESS', '2S291_graphql/3', 403 );
			}
		}
		catch ( \IPS\Api\Exception $e )
		{
			/* Build resonse */
			$response = json_encode( array( 'errors' => array( array( 'message' => $e->getMessage(), 'id' => $e->exceptionCode ) ) ), JSON_PRETTY_PRINT );
			
			/* Do we need to log this? */
			if ( in_array( $e->exceptionCode, array( '2S290_graphql/8', '2S290_graphql/B', '3S290_graphql/7', '3S290_graphql/9' ) ) )
			{
				$this->_log( (array)$response, $e->getCode(), in_array( $e->exceptionCode, array( '3S290/7', '3S290/9', '3S290/B' ) ) );
			}
			
			/* Output */
			$this->_respond( $response, $e->getCode(), $e->oauthError );
		}
	}
	
	/**
	 * Run
	 *
	 * @return	void
	 */
	public function run() : void
	{
		try
		{
			/* Work out the query (can either use a JSON-encoded or form-encoded) body */
			$query = NULL;
			$variables = [];
			if( Request::i()->query )
			{
				$query = Request::i()->query;

				if( isset( Request::i()->variables )  )
				{
					if( is_array( Request::i()->variables ) )
					{
						$variables = Request::i()->variables;
					}
					else
					{
						$variables = json_decode( Request::i()->variables, TRUE );
					}
				}
			}
			elseif ( $json = json_decode( file_get_contents('php://input'), TRUE ) )
			{
				$query = $json['query'];
				if( isset( $json['variables'] ) )
				{
					$variables = $json['variables'];
				}
			}

			if( !$query )
			{
				$this->_respond( json_encode( array( 'errors' => array( array( 'message' => 'NO_QUERY' ) ) ), JSON_PRETTY_PRINT ), 400 );
			}

			$output = GraphQLApi::execute( $query, $variables );

			Member::loggedIn()->language()->parseOutputForDisplay( $output );
			$this->_respond( json_encode( $output, JSON_PRETTY_PRINT ), 200 );
			
		}
		catch ( Exception $e )
		{
			$response = json_encode( array( 'errors' => array( array( 'message' => ( \IPS\IN_DEV OR DEBUG_GRAPHQL ) ? $e->getMessage() : 'UNKNOWN_ERROR', 'id' => $e->getCode() ) ) ), JSON_PRETTY_PRINT );
			$this->_respond( $response, 500 );
		}
	}
}