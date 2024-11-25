<?php
/**
 * @brief		API Reference
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		03 Dec 2015
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Dispatcher;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use ReflectionMethod;
use function defined;
use function extension_loaded;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * apiReference
 */
class apiReference extends Dispatcher\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'api_reference' );
		parent::execute();
	}

	/**
	 * View Reference
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* If Opcache is enabled but opcache.save_comments is disabled, the API reference won't work */
		if ( ( extension_loaded( 'opcache' ) OR extension_loaded( 'Zend Opcache' ) ) AND ini_get( 'opcache.save_comments' ) == 0 )
		{
			Output::i()->error( 'api_opcache_disable', '4C331/1', 403, '' );
		}

		/* Get endpoint details */
		$endpoints = Controller::getAllEndpoints();
		$selected = NULL;
		$content = '';
		
		/* If we're viewing a specific one, get information about it */
		if ( isset( Request::i()->endpoint ) and array_key_exists( Request::i()->endpoint, $endpoints ) )
		{
			$selected = Request::i()->endpoint;
			
			$additionalClassesToReference = array();

			$params = array();
			if ( isset( $endpoints[ $selected ]['details']['reqapiparam'] ) )
			{
				$params = array_map( function( $v ) {
					$v[4] = 'required';
					return $v;
				}, $endpoints[ $selected ]['details']['reqapiparam'] );
			}
			if ( isset( $endpoints[ $selected ]['details']['apiparam'] ) )
			{
				$params = array_merge( $params, $endpoints[ $selected ]['details']['apiparam'] );
			}

			foreach( $params as $index => $data )
			{
				if ( mb_strpos( $data[0], '|' ) === FALSE AND !in_array( $data[0], array( 'int', 'string', 'float', 'datetime', 'bool', 'object', 'array' ) ) )
				{
					if ( mb_substr( $data[0], 0, 1 ) == '[' )
					{
						if ( !in_array( mb_substr( $data[0], 1, -1 ), array( 'int', 'string', 'float', 'datetime', 'bool', 'object', 'array' ) ) )
						{
							if( $returned = $this->_getAdditionalClasses( mb_substr( $data[0], 1, -1 ) ) )
							{
								$additionalClassesToReference = array_merge( $additionalClassesToReference, $returned );
							}
							elseif( $returned === NULL )
							{
								unset( $params[ $index ] );
							}
						}
					}
					else
					{
						if( $returned = $this->_getAdditionalClasses( $data[0] ) )
						{
							$additionalClassesToReference = array_merge( $additionalClassesToReference, $returned );
						}
						elseif( $returned === NULL )
						{
							unset( $params[ $index ] );
						}
					}
				}
			}

			$exceptions = $endpoints[$selected]['details']['throws'] ?? NULL;
			if ( isset( $endpoints[ $selected ]['details']['apimemberonly'] ) )
			{
				$exceptions[] = array(
					'3S290/C',
					'MEMBER_ONLY',
					Member::loggedIn()->language()->addToStack('api_endpoint_member_only_err')
				);
			}
			if ( isset( $endpoints[ $selected ]['details']['apiclientonly'] ) )
			{
				$exceptions[] = array(
					'3S290/D',
					'CLIENT_ONLY',
					Member::loggedIn()->language()->addToStack('api_endpoint_client_only_err')
				);
			}

			$response = NULL;
			$endPointReturn = $endpoints[ $selected ]['details']['apireturn'] ?? $endpoints[ $selected ]['details']['return'];
			$return = array_filter( $endPointReturn[0] );
			$return = array_pop( $return );
			if ( $return == 'array' )
			{
				if ( isset( $endpoints[ $selected ]['details']['apiresponse'] ) )
				{
					foreach ( $endpoints[ $selected ]['details']['apiresponse'] as $index => $data )
					{
						if ( mb_strpos( $data[0], '|' ) === FALSE AND !in_array( $data[0], array( 'int', 'string', 'float', 'datetime', 'bool', 'object', 'array' ) ) )
						{
							if ( mb_substr( $data[0], 0, 1 ) == '[' )
							{
								if ( !in_array( mb_substr( $data[0], 1, -1 ), array( 'int', 'string', 'float', 'datetime', 'bool', 'object', 'array' ) ) )
								{
									if( $returned = $this->_getAdditionalClasses( mb_substr( $data[0], 1, -1 ) ) )
									{
										$additionalClassesToReference = array_merge( $additionalClassesToReference, $returned );
									}
									elseif( $returned === NULL )
									{
										unset( $endpoints[ $selected ]['details']['apiresponse'][ $index ] );
									}
								}
							}
							else
							{
								if( $returned = $this->_getAdditionalClasses( $data[0] ) )
								{
									$additionalClassesToReference = array_merge( $additionalClassesToReference, $returned );
								}
								elseif( $returned === NULL )
								{
									unset( $endpoints[ $selected ]['details']['apiresponse'][ $index ] );
								}
							}
						}
					}
					$response = Theme::i()->getTemplate('api')->referenceTable( $endpoints[ $selected ]['details']['apiresponse'] );
				}
			}
			elseif ( mb_substr( $return, 0, mb_strlen( 'PaginatedResponse' ) ) == 'PaginatedResponse' )
			{
				$class = mb_substr( trim( $return ), mb_strlen( 'PaginatedResponse' ) + 1, -1 );
				$additionalClassesToReference = array_merge( $additionalClassesToReference, $this->_getAdditionalClasses( $class ) );
				$response = Theme::i()->getTemplate('api')->referenceTable( array(
					array( 'int', 'page', 'api_int_page' ),
					array( 'int', 'perPage', 'api_int_perpage' ),
					array( 'int', 'totalResults', 'api_int_totalresults' ),
					array( 'int', 'totalPages', 'api_int_totalpages' ),
					array( "[{$class}]", 'results', 'api_results_thispage' ),
				) );
			}
			elseif ( $return = trim( $return ) and class_exists( $return ) and method_exists( $return, 'apiOutput' ) )
			{
				$additionalClassesToReference = array_merge( $additionalClassesToReference, $this->_getAdditionalClasses( $return, TRUE ) );
				$reflection = new ReflectionMethod( $return, 'apiOutput' );
				$decoded = Controller::decodeDocblock( $reflection->getDocComment() );
				$response = Theme::i()->getTemplate('api')->referenceTable( $decoded['details']['apiresponse'] );
			}
			else
			{
				$return = array_filter( $endPointReturn[0] );
				if( in_array( $return[0], array( 'int', 'string', 'float', 'datetime', 'bool' ) ) )
				{
					$response = Theme::i()->getTemplate('api')->referenceTable( array(
						array( $return[0], '', $return[1] ?? '' )
					) );
				}
			}
			
			$additionalClasses = array();
			foreach ( $additionalClassesToReference as $class )
			{
				$reflection = new ReflectionMethod( $class, 'apiOutput' );
				$decoded = Controller::decodeDocblock( $reflection->getDocComment() );
				$additionalClasses[ mb_strtolower( mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 ) ) ] = Theme::i()->getTemplate('api')->referenceTable( $decoded['details']['apiresponse'] );
			}
			
			$content = Theme::i()->getTemplate('api')->referenceEndpoint( $endpoints[ $selected ], $params, $exceptions, $response, $additionalClasses );
		}

		$endpointTree = array();
		foreach ( $endpoints as $key => $endpoint )
		{
			$pieces = explode('/', $key);
			$endpointTree[ $pieces[0] ][ $pieces[1] ][ $key ] = $endpoint;
		}
		
		if ( Request::i()->endpoint and Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $content );
		}

		/* Output */
		Output::i()->output = Theme::i()->getTemplate('api')->referenceTemplate( $endpoints, $endpointTree, $selected, $content );
	}
	
	/**
	 * Get any additional classes referenced in the return types of this class
	 *
	 * @param	string	$class 		The classname
	 * @param	bool	$exclude	If FALSE, will include this class itself in the return array
	 * @return	array|NULL
	 */
	protected function _getAdditionalClasses( string $class, bool $exclude=FALSE ) : ?array
	{
		if( !class_exists( $class ) )
		{
			return NULL;
		}

		$return = $exclude ? array() : array( $class => $class );
		$reflection = new ReflectionMethod( $class, 'apiOutput' );
		$decoded = Controller::decodeDocblock( $reflection->getDocComment() );
		foreach ( $decoded['details']['apiresponse'] as $response )
		{
			if ( mb_strpos( $response[0], '|' ) === FALSE AND !in_array( $response[0], array( 'int', 'string', 'float', 'datetime', 'bool', 'object', 'array' ) ) )
			{
				if ( mb_substr( $response[0], 0, 1 ) == '[' )
				{
					if ( !in_array( mb_substr( $response[0], 1, -1 ), $return ) and !in_array( mb_substr( $response[0], 1, -1 ), array( 'int', 'string', 'float', 'datetime', 'bool', 'object', 'array' ) ) )
					{
						if( $returned = $this->_getAdditionalClasses( mb_substr( $response[0], 1, -1 ) ) )
						{
							$return = array_merge( $return, $returned );
						}
					}
				}
				elseif ( !in_array( $response[0], $return ) )
				{
					if( $returned = $this->_getAdditionalClasses( $response[0] ) )
					{
						$return = array_merge( $return, $returned );
					}
				}
			}
		}
		return $return;
	}
}