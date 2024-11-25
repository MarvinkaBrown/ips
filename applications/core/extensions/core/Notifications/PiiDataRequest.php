<?php
/**
 * @brief		Notification Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		08 Mar 2023
 */

namespace IPS\core\extensions\core\Notifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\NotificationsAbstract;
use IPS\Http\Url;
use IPS\Member;
use IPS\Notification\Inline;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class PiiDataRequest extends NotificationsAbstract
{
	/**
	 * Get fields for configuration
	 *
	 * @param	Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	public static function configurationOptions( ?Member $member = NULL ): array
	{
		$return = [];
		/* WE don't need any options in the frontend for this, they have just to accept it;) */
		if( $member === NULL )
		{
			$return['pii_data'] = [
				'type'				=> 'standard',
				'notificationTypes'	=> [ 'pii_data' ],
				'title'				=> 'notifications__core_pii_data_request',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__core_pii_data_request_desc',
				'default'			=> [ 'email','inline' ],
				'disabled'			=> [],
				'extra'				=> []
			];
			$return['pii_data_rejected'] = [
				'type'				=> 'standard',
				'notificationTypes'	=> [ 'pii_data_rejected' ],
				'title'				=> 'notifications__core_pii_data_request_rejected',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__core_pii_data_request_rejected_desc',
				'default'			=> [ 'email','inline' ],
				'disabled'			=> [],
				'extra'				=> []
			];
			
			$return['account_del_request_rejected'] = [
			'type'				=> 'standard',
			'notificationTypes'	=> [ 'account_del_request_rejected' ],
			'title'				=> 'notifications__core_account_deletion_rejected',
			'showTitle'			=> TRUE,
			'description'		=> 'notifications__core_account_deletion_rejected__desc',
			'default'			=> [ 'email','inline' ],
			'disabled'			=> [],
			'extra'				=> []
			];
		}

		return $return;
	}
	
	// For each type of notification you need a method like this which controls what will be displayed when the user clicks on the notification icon in the header:
	// Note that for each type of notification you must *also* create email templates. See documentation for details: https://remoteservices.invisionpower.com/docs/devdocs-notifications
	
	/**
	 * Parse notification: key
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 return array(
		 'title'		=> "Mark has replied to A Topic",	// The notification title
		 'url'			=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
		 'content'		=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
		 													// 	 explains what the notification is about - just include any appropriate content.
		 													// 	 For example, if the notification is about a post, set this as the body of the post.
		 'author'		=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_pii_data( Inline $notification, bool $htmlEscape=TRUE ): array
	{
		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'pii_data_ready_notification'),
			'url'			=> Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front')
		);
	}

	/**
	 * Parse notification: key
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	return array(
	'title'		=> "Mark has replied to A Topic",	// The notification title
	'url'			=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	'content'		=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	// 	 explains what the notification is about - just include any appropriate content.
	// 	 For example, if the notification is about a post, set this as the body of the post.
	'author'		=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	);
	 * @endcode
	 */
	public function parse_pii_data_rejected( Inline $notification, bool $htmlEscape=TRUE ): array
	{
		return array(
		'title'		=> Member::loggedIn()->language()->addToStack( 'pii_data_request_rejected'),
		'url'			=> Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front')
		);
	}


	/**
	 * Parse notification: key
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	return array(
	'title'		=> "Mark has replied to A Topic",	// The notification title
	'url'			=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	'content'		=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	// 	 explains what the notification is about - just include any appropriate content.
	// 	 For example, if the notification is about a post, set this as the body of the post.
	'author'		=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	);
	 * @endcode
	 */
	public function parse_account_del_request_rejected( Inline $notification, bool $htmlEscape=TRUE ): array
	{
		return array(
		'title'		=> Member::loggedIn()->language()->addToStack( 'account_del_request_rejected'),
		'url'			=> Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front')
		);
	}
}