<?php
/**
 * @brief		ACP Member Profile: Notification Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Jun 2019
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\MemberACPProfile\Block;
use IPS\Http\Url;
use IPS\Member;
use IPS\Notification;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Profile Data Block
 */
class Notifications extends Block
{
	protected ?array $extensions = array();

	/**
	 * Constructor
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		parent::__construct( $member );
		
		$this->extensions = Application::allExtensions( 'core', 'Notifications' );
	}
		
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		return (string) Theme::i()->getTemplate('memberprofile')->notificationTypes( $this->member, Notification::membersOptionCategories( $this->member, $this->extensions ) );
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		if ( isset( Request::i()->type ) and array_key_exists( Request::i()->type, $this->extensions ) )
		{
			$form = Notification::membersTypeForm( $this->member, $this->extensions[ Request::i()->type ] );
			if ( $form === TRUE )
			{
				Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$this->member->member_id}" ), 'saved' );
			}
			
			return $form;
		}
		else
		{
			Output::i()->error( 'node_error', '2C403/1', 404, '' );
		}
	}
}