<?php
/**
 * @brief		GraphQL: Login Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Oct 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Session;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * LoginType for GraphQL API
 */
class LoginType extends ObjectType
{
    /**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_Login',
			'description' => 'Login type',
			'fields' => function () {
				return [
					'id' => [
						'type' => TypeRegistry::id(),
						'description' => "Handler ID",
						'resolve' => function ($login) {
							return $login->_id;
						}
					],
					'title' => [
						'type' => TypeRegistry::string(),
						'description' => "Login name",
						'resolve' => function ($login) {
							return Member::loggedIn()->language()->get( $login->getTitle() );
						}
					],
					'text' => [
						'type' => TypeRegistry::string(),
						'description' => "Login button text",
						'resolve' => function ($login) {
							return Member::loggedIn()->language()->get( $login->buttonText() );
						}
					],
					'icon' => [
						'type' => TypeRegistry::string(),
						'description' => "URL to the icon for this service",
						'resolve' => function ($login) {
							return $login->buttonIcon();
						}
					],
					'color' => [
						'type' => TypeRegistry::string(),
						'description' => "Color of this handler's button",
						'resolve' => function ($login) {
							return $login->buttonColor();
						}
					],
					'url' => [
						'type' => TypeRegistry::string(),
						'description' => "URL to visit to authorize with this handler",
						'resolve' => function ($login) {
							$loginClass = new Login;
							return (string) Url::internal( $loginClass->url )->setQueryString('csrfKey', Session::i()->csrfKey)->setQueryString('_processLogin', $login->_id);
						}
					]
				];
			}
		];

		parent::__construct($config);  
	}
}
