<?php
/**
 * @brief		ACP Member Profile: OAuth Apps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Api\OAuthClient;
use IPS\core\MemberACPProfile\Block;
use IPS\Db;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Groups Block
 */
class OAuth extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'oauth_tokens' ) and $count = Db::i()->select( 'COUNT(*)', 'core_oauth_clients', array( Db::i()->findInSet( 'oauth_grant_types', array( 'authorization_code', 'implicit', 'password' ) ) ) )->first() )
		{

			$tokens = array();
			foreach ( Db::i()->select( '*', 'core_oauth_server_access_tokens', array( 'member_id=?', $this->member->member_id ), 'issued DESC' ) as $token )
			{

				try
				{
					$client = OAuthClient::load( $token['client_id'] );
					
					$title = $client->_title;
					
					$tokens[] = array(
						'title'					=> $title,
						'use_refresh_tokens'		=> $client->use_refresh_tokens,
						'data'					=> $token
					);
				}
				catch ( Exception $e ) {}
			}
			
			$onlyApp = NULL;
			if ( $count === 1 )
			{
				$onlyApp = OAuthClient::constructFromData( Db::i()->select( '*', 'core_oauth_clients', array( Db::i()->findInSet( 'oauth_grant_types', array( 'authorization_code', 'implicit', 'password' ) ) ) )->first() );
			}
			
			return (string) Theme::i()->getTemplate('memberprofile')->oauth( $this->member, $tokens, $onlyApp );
		}

		return '';
	}
}