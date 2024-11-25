<?php
/**
 * @brief		ACP Notification: IPS Bulletins
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Jul 2018
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\core\AdminNotification;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use Throwable;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: IPS Bulletins
 */
class Bulletin extends AdminNotification
{
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'important';
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 1;
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this notification type compared to others in the same group
	 */
	public static int $itemPriority = 2;
		
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_Bulletin';
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return $member->hasAcpRestriction( 'core', 'overview', 'ips_notifications' );
	}
	
	/**
	 * Is this type of notification ever optional (controls if it will be selectable as "viewable" in settings)
	 *
	 * @return	bool
	 */
	public static function mayBeOptional(): bool
	{
		return FALSE;
	}
	
	/**
	 * Is this type of notification might recur (controls what options will be available for the email setting)
	 *
	 * @return	bool
	 */
	public static function mayRecur(): bool
	{
		return FALSE;
	}

	/**
	 * @brief	Cached data (so we don't query it multiple times)
	 */
	protected ?array $_bulletinData = NULL;
		
	/**
	 * Get notification data
	 *
	 * @return	array
	 */
	public function data() : array
	{
		if( $this->_bulletinData !== NULL )
		{
			return $this->_bulletinData;
		}

		try
		{
			$data = Db::i()->select( '*', 'core_ips_bulletins', array( 'id=?', $this->extra ) )->first();
		}
		catch( UnderflowException $e )
		{
			$data = array( 'cached' => 0, 'id' => $this->extra );
		}
		
		if ( ( time() - $data['cached'] ) > 3600 ) // If data was cached more than an hour ago, check again in case it's been updated
		{
			try
			{
				$response = Url::ips("bulletin/{$data['id']}")->request()->get();
				$bulletin = $response->decodeJson();
				if ( isset( $bulletin['title'] ) )
				{
					$data = array(
						'id' 			=> $data['id'],
						'title'			=> $bulletin['title'],
						'body'			=> $bulletin['body'],
						'severity'		=> $bulletin['severity'],
						'style'			=> $bulletin['style'],
						'dismissible'	=> $bulletin['dismissible'],
						'link'			=> $bulletin['link'],
						'conditions'	=> $bulletin['conditions'],
						'cached'		=> time(),
						'min_version'	=> $bulletin['minVersion'],
						'max_version'	=> $bulletin['maxVersion']
					);
					Db::i()->update( 'core_ips_bulletins', $data, array( 'id=?', $this->extra ) );
				}
				else
				{
					if( (int) $response->httpResponseCode === 410 )
					{
						Db::i()->delete( 'core_ips_bulletins', [ 'id=?', $this->extra ] );
						$this->delete();
					}

					throw new DomainException;
				}
			}
			catch ( Exception $e )
			{
				Db::i()->update( 'core_ips_bulletins', array( 'cached' => ( time() + 3600 - 900 ) ), array( 'id=?', $this->extra ) ); // Try again in 15 minutes

				$data = array(
					'id' 			=> $data['id'],
					'title'			=> null,
					'body'			=> null,
					'severity'		=> null,
					'style'			=> null,
					'dismissible'	=> false,
					'link'			=> null,
					'conditions'	=> null,
					'cached'		=> ( time() + 3600 - 900 ),
					'min_version'	=> null,
					'max_version'	=> null
				);
			}
		}

		$this->_bulletinData = $data;
		
		return $data;
	}
	
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{		
		return $this->data()['title'];
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		return $this->data()['body'];
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		return $this->data()['severity'];
	}
	
	/**
	 * Dismissible?
	 *
	 * @return	string
	 */
	public function dismissible(): string
	{
		return $this->data()['dismissible'];
	}
	
	/**
	 * Style
	 *
	 * @return	string
	 */
	public function style(): string
	{
		return $this->data()['style'];
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url
	 */
	public function link(): Url
	{
		return $this->data()['link'] ?: parent::link();
	}
	
	/**
	 * Should this notification dismiss itself?
	 *
	 * @note	This is checked every time the notification shows. Should be lightweight.
	 * @return	bool
	 */
	public function selfDismiss(): bool
	{
		try
		{
			if( $this->data()['min_version'] AND $this->data()['min_version'] > Application::load('core')->long_version )
			{
				return TRUE;
			}

			if( $this->data()['max_version'] AND $this->data()['max_version'] < Application::load('core')->long_version )
			{
				return TRUE;
			}

			return !@eval( $this->data()['conditions'] );
		}
		catch ( Throwable | Exception $e )
		{
			return FALSE;
		}
	}
}