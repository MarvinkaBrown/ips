<?php
/**
 * @brief		ACP Member Profile: Login Methods Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\LazyLoadingBlock;
use IPS\Http\Request\Exception;
use IPS\Log;
use IPS\Login;
use IPS\Login\Handler\Standard;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Login Methods Block
 */
class LoginMethods extends LazyLoadingBlock
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function lazyOutput(): string
	{
		$loginMethods = array();
		foreach ( Login::methods() as $method )
		{
			if ( $method->canProcess( $this->member ) and !( $method instanceof Standard ) )
			{
				$link = NULL;
				try
				{					
					$link = $method->userLink( $method->userId( $this->member ), $method->userProfileName( $this->member ) );
					
					$forceSyncErrors = array();
					foreach ( $method->forceSync() as $type )
					{
						if ( isset( $this->member->profilesync[ $type ]['error'] ) )
						{
							$forceSyncErrors[ $type ] = Member::loggedIn()->language()->addToStack( $this->member->profilesync[ $type ]['error'] );
						}
					}
					
					$syncOptions = FALSE;
					foreach ( $method->syncOptions( $this->member ) as $option )
					{
						if ( $option == 'photo' and !$this->member->group['g_edit_profile'] )
						{
							continue;
						}
						if ( $option == 'cover' and ( !$this->member->group['g_edit_profile'] or !$this->member->group['gbw_allow_upload_bgimage'] ) )
						{
							continue;
						}
						
						$syncOptions = TRUE;
						break;
					}
					
					$canDisassociate = FALSE;
					foreach ( Login::methods() as $_method )
					{
						if ( $_method->id != $method->id and $_method->canProcess( $this->member ) )
						{
							$canDisassociate = TRUE;
							break;
						}
					}

					/* Login handlers may return NULL if they do not support display name syncing */
					$memberName = $method->userProfileName( $this->member ) ?? Member::loggedIn()->language()->get( 'profilesync_unknown_name' );
					
					$loginMethods[ $method->id ] = array(
						'title'				=> $method->_title,
						'blurb'				=> Member::loggedIn()->language()->addToStack( 'profilesync_headline', FALSE, array( 'sprintf' => array( $memberName ) ) ),
						'forceSyncErrors'	=> $forceSyncErrors,
						'icon'				=> $method->userProfilePhoto( $this->member ),
						'logo'				=> $method->logoForUcp(),
						'link'				=> $link,
						'edit'				=> $syncOptions,
						'delete'			=> $canDisassociate
					);
				}
				catch ( Login\Exception $e )
				{
					$loginMethods[ $method->id ] = array( 'title' => $method->_title, 'blurb' => Member::loggedIn()->language()->addToStack('profilesync_reauth_needed'), 'logo' => $method->logoForUcp(), 'link' => $link );
				}
				catch( Exception $e )
				{
					Log::log( $e, 'login_method_connect' );
				}
			}
		}
		
		return (string) Theme::i()->getTemplate('memberprofile')->loginMethods( $this->member, $loginMethods );
	}
}