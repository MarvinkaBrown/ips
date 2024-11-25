<?php
/**
 * @brief		Inline Notification Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		6 Sep 2013
 */

namespace IPS\Notification;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use function defined;
use function get_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Inline Notification Model
 */
class Inline extends ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_notifications';
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->sent_time = time();
		$this->updated_time = time();
	}
	
	/**
	 * Get sent time
	 *
	 * @return	DateTime
	 */
	public function get_sent_time(): DateTime
	{
		return DateTime::ts( $this->_data['sent_time'] );
	}
	
	/**
	 * Get updated time
	 *
	 * @return	DateTime
	 */
	public function get_updated_time(): DateTime
	{
		return DateTime::ts( $this->_data['updated_time'] );
	}
	
	/**
	 * Get member
	 *
	 * @return	Member
	 */
	public function get_member(): Member
	{
		return Member::load( $this->_data['member'] );
	}

	/**
	 * Set member
	 *
	 * @param Member $member
	 * @return    void
	 */
	public function set_member( Member $member ) : void
	{
		$this->_data['member'] = $member->member_id;
	}
	
	/**
	 * Get member data
	 *
	 * @return	array
	 */
	public function get_member_data(): array
	{
		return json_decode( $this->_data['member_data'], TRUE );
	}
	
	/**
	 * Set member data
	 *
	 * @param	mixed	$data	Member data
	 * @return	void
	 */
	public function set_member_data( mixed $data ) : void
	{
		$this->_data['member_data'] = $data ? json_encode( $data ) : NULL;
	}
	
	/**
	 * Get item
	 *
	 * @return	object|NULL
	 */
	public function get_item(): ?object
	{
		if ( $this->_data['item_class'] and $this->_data['item_id'] )
		{
			try
			{
				$class = $this->_data['item_class'];
				if ( class_exists( $class ) )
				{
					return $class::load( $this->_data['item_id'] );
				}
			}
			catch ( OutOfRangeException $e )
			{
				return NULL;
			}
		}
		return NULL;
	}
	
	/**
	 * Set item
	 *
	 * @param object $item	The item
	 * @return	void
	 */
	public function set_item( object $item ) : void
	{
		$idColumn = $item::$databaseColumnId;
		$this->_data['item_class'] = get_class( $item );
		$this->_data['item_id'] = $item->$idColumn;
	}
	
	/**
	 * Get subitem
	 *
	 * @return	object|NULL
	 */
	public function get_item_sub(): ?object
	{
		if ( $this->_data['item_sub_class'] and $this->_data['item_sub_id'] )
		{
			try
			{
				$class = $this->_data['item_sub_class'];
				if ( class_exists( $class ) )
				{
					return $class::load( $this->_data['item_sub_id'] );
				}
			}
			catch ( OutOfRangeException $e )
			{
				return NULL;
			}
		}
		return NULL;
	}
	
	/**
	 * Get application
	 *
	 * @return	Application
	 */
	public function get_notification_app(): Application
	{
		return Application::load( $this->_data['notification_app'] );
	}
	
	/**
	 * Set application
	 *
	 * @param	Application	$app
	 * @return	void
	 */
	public function set_notification_app( Application $app ) : void
	{
		$this->_data['notification_app'] = $app->directory;
	}
	
	/**
	 * Get extra data
	 *
	 * @return	array
	 */
	public function get_extra(): array
	{
		return $this->_data['extra'] ? json_decode( $this->_data['extra'], TRUE ) : array();
	}
	
	/**
	 * Set extra data
	 *
	 * @param	mixed	$data	Member data
	 * @return	void
	 */
	public function set_extra( mixed $data ) : void
	{
		$this->_data['extra'] = $data ? json_encode( $data ) : NULL;
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		$this->member->recountNotifications();
	}
	
	/**
	 * Get data from extension
	 *
	 * @param bool $htmlEscape	TRUE to escape HTML
	 * @return	array
	 * @throws	RuntimeException
	 */
	public function getData( bool $htmlEscape = TRUE ): array
	{
		$method = "parse_{$this->notification_key}";
		
		foreach ( $this->notification_app->extensions( 'core', 'Notifications' ) as $class )
		{
			if ( method_exists( $class, $method ) )
			{
				$return = $class->$method( $this, $htmlEscape );
				
				if ( !isset( $return['unread'] ) )
				{
					$return['unread'] = !$this->read_time;
				}
				
				return $return;
			}
		}
		throw new RuntimeException;
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse	string	notificationType	The type (key) of notification sent
	 * @apiresponse	string	notificationApp		The app that processes this type of notification
	 * @apiresponse	string	itemClass			The class that triggered the notification
	 * @apiresponse	int		itemId				The ID of the content that triggered the notification
	 * @apiresponse	string	subItemClass		The sub-class that triggered the notification (e.g. for comments or reviews)
	 * @apiresponse	int		subItemId			The sub-ID of the content that triggered the notification (e.g. for comments or reviews)
	 * @apiresponse	object|null	item			A representation of the item being notified about (if supported), or null
	 * @apiresponse	object|null	itemSub			A representation of the subitem being notified about (if supported), or null
	 * @apiresponse	datetime	sentDate		Date and time the notification was sent
	 * @apiresponse	datetime	updatedDate		Date and time the notification was last updated
	 * @apiresponse	datetime|null	readDate	Date and time the notification was read by the user
	 * @apiresponse	array	notificationData	Array of additional data relevant to this specific notification
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		try
		{
			$ourData = $this->getData();
		}
		catch( LogicException | RuntimeException $e )
		{
			$ourData = array();
		}

		return array(
			'notificationType'		=> $this->notification_key,
			'notificationApp'		=> $this->notification_app->directory,
			'itemClass'				=> $this->item_class,
			'itemId'				=> $this->item_id,
			'subItemClass'			=> $this->item_sub_class,
			'subItemId'				=> $this->item_sub_id,
			'item'					=> ( $this->item AND method_exists( $this->item, 'apiOutput' ) ) ? $this->item->apiOutput( $authorizedMember ) : NULL,
			'itemSub'				=> ( $this->item_sub AND method_exists( $this->item_sub, 'apiOutput' ) ) ? $this->item_sub->apiOutput( $authorizedMember ) : NULL,
			'sentDate'				=> $this->sent_time->rfc3339(),
			'updatedDate'			=> $this->updated_time->rfc3339(),
			'readDate'				=> $this->read_time ? DateTime::ts( $this->read_time )->rfc3339() : NULL,
			'notificationData'		=> array_map( function( $val ) {
				if( $val instanceof Url )
				{
					return (string) $val;
				}
				elseif( $val instanceof Member )
				{
					return $val->apiOutput();
				}
				elseif( $val instanceof DateTime )
				{
					return $val->rfc3339();
				}
				else
				{
					return $val;
				}
			}, $ourData )
		);
	}
}