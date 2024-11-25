<?php
/**
 * @brief		Notification Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Apr 2013
 */

namespace IPS\core\extensions\core\Notifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Extensions\NotificationsAbstract;
use IPS\Lang;
use IPS\Member;
use IPS\Notification\Inline;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class Profile extends NotificationsAbstract
{
	/**
	 * Get fields for configuration
	 *
	 * @param	Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	public static function configurationOptions( ?Member $member = NULL ): array
	{
		$return = array();
		
		$module = Module::get( 'core', 'members', 'front' );
		if ( $module->_enabled and ( $member === NULL or $member->canAccessModule( $module ) ) )
		{
			$return['core_Profile_follow'] = array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'member_follow' ),
				'title'				=> 'notifications__core_Profile_follow',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__core_Profile_follow_desc',
				'default'			=> array( 'inline', 'push' ),
				'disabled'			=> array(),
			);
		}

		if( Settings::i()->ref_on )
		{
			$return['referral'] = array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'referral' ),
				'title'				=> 'notifications__referral',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__referral_desc',
				'default'			=> array( 'inline' ),
				'disabled'			=> array(),
			);
		}

		return $return;
	}
		
	/**
	 * Parse notification: member_follow
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_member_follow( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$member = $notification->item;

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__member_follow', FALSE, array( ( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $member->name ) ) ),
			'url'		=> $member->url(),
			'author'	=> $member,
		);
	}
	
	/**
	 * Parse notification for mobile: member_follow
	 *
	 * @param	Lang	$language	The language that the notification should be in
	 * @param	Member	$member		The member that started following the recipient
	 * @return	array
	 */
	public static function parse_mobile_member_follow( Lang $language, Member $member ) : array
	{
		return array(
			'title' => $language->addToStack( 'notification__member_follow_title' ),
			'body' => $language->addToStack( 'notification__member_follow', FALSE, array( 'htmlsprintf' => array( $member->name ) ) ),
			'data' => array(
				'url' => (string)$member->url(),
				'author	' => $member
			),
			'channelId' => 'your-profile',
		);
	}
	
	/**
	 * Parse notification: referral
	 *
	 * @param	Inline	$notification	The notification
	 * @return	array
	 * @code
	return array(
	'title'		=> "Mark has replied to A Topic",	// The notification title
	'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	// explains what the notification is about - just include any appropriate content.
	// For example, if the notification is about a post, set this as the body of the post.
	'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	);
	 * @endcode
	 */
	public function parse_referral( Inline $notification ) : array
	{
		$member = $notification->item;

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__referral', FALSE, array( 'sprintf' => array( $member->name ) ) ),
			'url'		=> $member->url(),
			'author'	=> $member,
		);
	}
}