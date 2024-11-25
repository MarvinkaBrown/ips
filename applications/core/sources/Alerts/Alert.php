<?php
/**
 * @brief		Alert model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 May 2022
 * @todo js to hide modal when replying
 */

namespace IPS\core\Alerts;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Application\Module;
use IPS\Content\Item;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Member\Group;
use IPS\Request;
use function count;
use function defined;
use function in_array;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Alerts Model
 */
class Alert extends Item
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_alerts';

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'alerts' );

	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'alert_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
		
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
			'title'			=> 'title',
			'date'			=> 'start',
			'author'		=> 'member_id',
			'views'			=> 'views',
			'content'		=> 'content'
	);
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'alert';
	
	/**
	 * @brief	Title
	 */
	public static string $icon = 'bullhorn';


	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_seo_title() : string
	{
		if( !$this->_data['seo_title'] )
		{
			$this->seo_title	= Friendly::seoTitle( $this->title );
			$this->save();
		}

		return $this->_data['seo_title'] ?: Friendly::seoTitle( $this->title );
	}

	/**
	 * Display Form
	 *
	 * @param	static|NULL	$alert	Existing alert (for edits)
	 * @return	Form
	 */
	public static function form( ?Alert $alert ) : Form
	{
		/* Build the form */
		$form = new Form( NULL, 'save' );
		$form->class = 'ipsForm--vertical ipsForm--edit-alert';

		if ( ! Member::loggedIn()->canUseMessenger() )
		{
			$form->addMessage( 'alert_you_cannot_receive_messages', 'ipsMessage ipsMessage_info' );
		}

		$form->add( new Text( 'alert_title', ( $alert ) ? $alert->title : NULL, TRUE, array( 'maxLength' => 255 ) ) );

		$today = new DateTime;
		$form->add( new Date( 'alert_start', ( $alert ) ? DateTime::ts( $alert->start ) : new DateTime, TRUE, array( 'time' => TRUE ) ) );
		$form->add( new Date( 'alert_end', ( $alert AND $alert->end ) ? DateTime::ts( $alert->end ) : 0, FALSE, array( 'min' => $today->setTime( 0, 0, 1 )->add( new DateInterval( 'P1D' ) ), 'unlimited' => 0, 'unlimitedLang' => 'none' ) ) );
		$form->add( new Editor( 'alert_content', ( $alert ) ? $alert->content : NULL, TRUE, array( 'app' => 'core', 'key' => 'Alert', 'autoSaveKey' => ( $alert ? 'editAlert__' . $alert->id : 'createAlert' ), 'attachIds' => $alert ? array( $alert->id, NULL, 'alert' ) : NULL ), NULL, NULL, NULL, 'alert_content' ) );

		$options = array( 'user' => 'alert_type_user', 'group' => 'alert_type_group' );
		$toggles = array( 'user' => array( 'alert_recipient_user' ), 'group' => array( 'alert_recipient_group', 'alert_show_to' ) );
		$form->add( new Radio( 'alert_recipient_type', ( $alert ) ? $alert->recipient_type : NULL, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );

		$groups = array();
		foreach ( Group::groups( TRUE, FALSE ) as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}

		$autocomplete = [
			'source' 				=> 'app=core&module=system&controller=ajax&do=findMember',
			'resultItemTemplate' 	=> 'core.autocomplete.memberItem',
			'commaTrigger'			=> false,
			'unique'				=> true,
			'minAjaxLength'			=> 3,
			'disallowedCharacters'  => array(),
			'lang'					=> 'mem_optional',
			'maxItems'				=> 1,
			'minimized' => FALSE
		];

		$form->add( new FormMember( 'alert_recipient_user',  ( $alert and $alert->recipient_user ) ? Member::load( $alert->recipient_user ) : ( isset( Request::i()->user ) ? Member::load( Request::i()->user ) : NULL ), FALSE, array( 'multiple' => 1, 'autocomplete' => $autocomplete ), function( $member ) use ( $form )
		{
			if ( Request::i()->alert_recipient_type === 'user' )
			{
				if( !is_object( $member ) or !$member->member_id )
				{
					throw new InvalidArgumentException( 'alert_no_recipient_selected' );
				}

				if ( ! $member->canUseMessenger() and Request::i()->alert_reply == 2 )
				{
					throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack( 'alert_member_pm_disabled', NULL, [ 'sprintf' => $member->name ] ) );
				}
			}
		},
			NULL, NULL, 'alert_recipient_user' ) );

		$form->add( new CheckboxSet( 'alert_recipient_group', ( $alert and $alert->recipient_group ) ? explode( ',', $alert->recipient_group ) : array(), FALSE, array( 'options' => $groups, 'multiple' => TRUE ), function( $groups )
		{
			if ( Request::i()->alert_reply == 2 )
			{
				$module = Module::get( 'core', 'messaging' );
				$names = [];
				foreach( $groups as $group )
				{
					$group = Group::load( $group );
					if ( ! ( Application::load( $module->application )->canAccess( $group ) and ( $module->protected or $module->can( 'view', $group ) ) ) )
					{
						$names[] = $group->name;
					}
				}

				if ( count( $names ) )
				{
					throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack( 'alert_member_group_pm_disabled', NULL, [ 'htmlsprintf' => Member::loggedIn()->language()->formatList( $names ) ] ) );
				}
			}
		},
			NULL, NULL, 'alert_recipient_group' ) );

		$form->add(  new Radio( 'alert_show_to', ( $alert and $alert->show_to ) ? $alert->show_to : 'all', FALSE, [
			'options' => [
				'all' => 'alert_show_to_all',
				'new' => 'alert_show_to_new'
			]
		], NULL, NULL, NULL, 'alert_show_to' ) );

		$form->add( new YesNo( 'alert_anonymous', $alert and $alert->anonymous, TRUE, array( 'togglesOff' => array( 'alert_reply') ) ) );
		$form->add( new Radio( 'alert_reply', $alert ? $alert->reply : 0, TRUE, array( 'disabled' => (bool) Member::loggedIn()->members_disable_pm, 'options' => array( '0' => 'alert_no_reply', '1' => 'alert_can_reply', '2' => 'alert_must_reply' ) ), NULL, NULL, NULL, 'alert_reply' ) );

		if ( Member::loggedIn()->members_disable_pm )
		{
			Member::loggedIn()->language()->words['alert_reply_desc'] = Member::loggedIn()->language()->get('alert_reply__nopm_desc');
		}

		return $form;
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->alerts ) )
		{
			Store::i()->alerts = iterator_to_array( Db::i()->select( '*', static::$databaseTable, [
				[ 'alert_enabled=?', 1 ],
				[ 'alert_start < ?', time() ],
				[ '( alert_end = 0 or alert_end > ? )', time() ]
			], "alert_start ASC" )->setKeyField( 'alert_id' ) );
		}

		return Store::i()->alerts;
	}
	
	/**
	 * Create from form
	 *
	 * @param	array	$values	Values from form
	 * @param Alert|null $current	Current alert
	 * @return    Alert
	 */
	public static function _createFromForm( array $values, ?Alert $current ) : static
	{
		if( $current )
		{
			$obj = static::load( $current->id );
		}
		else
		{
			$obj = new static;
			$obj->member_id = Member::loggedIn()->member_id;
		}

		$obj->title			= $values['alert_title'];
		$obj->seo_title 	= Friendly::seoTitle( $values['alert_title'] );
		$obj->content		= $values['alert_content'];
		$obj->start			= !empty( $values['alert_start'] ) ? ( $values['alert_start']->getTimestamp() < time() ) ? time() : $values['alert_start']->getTimestamp() : time();
		$obj->end			= !empty ($values['alert_end'] ) ? $values['alert_end']->getTimestamp() : 0;

		$obj->recipient_type = $values['alert_recipient_type'];

		if( $obj->recipient_type == 'user' )
		{
			if( is_object( $values['alert_recipient_user'] ) )
			{
				$values['alert_recipient_user']	= $values['alert_recipient_user']->member_id;
			}
			$obj->recipient_user = $values['alert_recipient_user'];
			$obj->recipient_group = NULL;
		}
		else
		{
			$obj->recipient_user = NULL;
			$obj->recipient_group = implode( ",", $values['alert_recipient_group'] );
			$obj->show_to = $values['alert_show_to'];
		}

		$obj->anonymous = $values['alert_anonymous'];
		$obj->reply = ( ! Member::loggedIn()->canUseMessenger() ) ? 0 : $values['alert_reply'];

		$obj->save();
		
		if( !$current )
		{
			File::claimAttachments( 'createAlert', $obj->id, NULL, 'alert' );
		}
		
		return $obj;
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( string|null $action=NULL ): Url
	{
		$_key	= $action ? md5( $action ) : NULL;

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = Url::internal( "app=core&module=modcp&controller=modcp&tab=alerts&id={$this->id}", 'front', 'modcp_alerts' );
			$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'action', $action );
		}

		return $this->_url[ $_key ];
	}

	/**
	 * Get owner
	 *
	 * @return	Member
	 */
	public function owner() : Member
	{
		return Member::load( $this->member_id );
	}
	
	/**
	 * Unclaim attachments
	 *
	 * @return	void
	 */
	protected function unclaimAttachments(): void
	{
		File::unclaimAttachments( 'core_Alert', $this->id, NULL, 'alert' );
	}

	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public static function getTableFilters(): array
	{
		return array(
			'active', 'inactive'
		);
	}

	/**
	 * @param Member $member
	 *
	 * @return Alert|NULL
	 */
	public static function getNextAlertForMember( Member $member ): ?Alert
	{
		$alerts = static::getStore();

		if ( ! count( $alerts ) )
		{
			return NULL;
		}

		/* Get possible alerts for this member (group alerts are checked in PHP) */
		$query = Db::i()->select( '*', 'core_alerts', [
			[ 'alert_enabled=?', 1 ],
			[ 'alert_start < ?', time() ],
			[ '( alert_end = 0 or alert_end > ? )', time() ],
			[ '( alert_recipient_type=? OR ( alert_recipient_type=? AND alert_recipient_user=? ) )', 'group', 'user', $member->member_id ],
			[ 'alert_id NOT IN (?)', Db::i()->select( 'seen_alert_id', 'core_alerts_seen', [ 'seen_member_id=?', $member->member_id ] ) ]
		], 'alert_start ASC' );

		foreach( $query as $data )
		{
			$alert = static::constructFromData( $data );
			if ( $alert->forMember( $member ) )
			{
				return $alert;
			}
		}

		return NULL;
	}

	/**
	 * Is this alert valid for the member?
	 *
	 * @param Member $member
	 * @return	Bool
	 */
	public function forMember( Member $member ) : bool
	{
		/* Is it disabled? */
		if ( ! $this->enabled )
		{
			return FALSE;
		}

		/* Are we the alert author? */
		if ( $this->member_id == Member::loggedIn()->member_id )
		{
			return FALSE;
		}

		/* Is it in the future? */
		if( $this->start > time() )
		{
			return FALSE;
		}

		/* Is it in the past? */
		if( $this->end and $this->end < time() )
		{
			return FALSE;
		}

		/* Show only if to this user or group */
		if( $this->recipient_type == 'user' and $this->recipient_user !== $member->member_id )
		{
			return FALSE;
		}

		if( $this->recipient_type == 'group' )
		{
			if( !$member->inGroup( explode( ",", $this->recipient_group ) ) )
			{
				return FALSE;
			}

			if ( $this->show_to == 'new' and ( Member::loggedIn()->joined->getTimestamp() < $this->start ) )
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Return the names of the groups for this alert.
	 * Convenience method for use in templates.
	 *
	 * @return array
	 */
	public function groupNames(): array
	{
		$names = [];

		if ( $this->recipient_type == 'group' and $this->recipient_group )
		{
			$groups = explode( ',', $this->recipient_group );

			foreach ( Group::groups() as $group )
			{
				if ( in_array( $group->g_id, $groups ) )
				{
					$names[] = $group->name;
				}
			}
		}

		return $names;
	}

	/**
	 * Return the namee of the member for this alert
	 * Convenience method for use in templates.
	 *
	 * @return string
	 */
	public function memberName(): string
	{
		$member = Member::load( $this->recipient_user );
		return ( $member->member_id ) ? $member->name : Member::loggedIn()->language()->addToStack( 'deleted_member' );
	}

	/**
	 * Mark this alert as viewed
	 *
	 * @param Member|null $member	Member who viewed
	 *
	 * @return void
	 */
	public function viewed( ?Member $member=NULL ) : void
	{
		$this->viewed++;
		$this->save();
	}

	/**
	 * Dismiss alert
	 *
	 * @param Member|null $member	Member who viewed
	 *
	 * @return	void
	 */
	public function dismiss( ?Member $member=NULL ) : void
	{
		$member = $member ?: Member::loggedIn();

		if( !$this->forMember( $member ) )
		{
			return;
		}

		Db::i()->insert( 'core_alerts_seen', [
			'seen_alert_id' => $this->id,
			'seen_member_id' => $member->member_id,
			'seen_date' => time()
		], TRUE );

		$member->latest_alert = $this->start;
		$member->save();
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();

		Db::i()->delete( 'core_alerts_seen', [ 'seen_alert_id=?', $this->id ] );
	}

	/**
	 * Get the count of how many replies have been sent in via this alert
	 *
	 * @return int
	 */
	public function membersRepliedCount(): int
	{
		return (int) Db::i()->select( 'COUNT(*)', 'core_message_topics', [ 'mt_alert=?', $this->id ] )->first();
	}

	/**
	 * Returns the current alert that messenger is being filtered by
	 *
	 * @param Member|null $member
	 * @return Alert|null
	 */
	public static function getAlertCurrentlyFilteringMessages( ?Member $member = NULL ): ?Alert
	{
		$member = $member ?: Member::loggedIn();

		if ( isset( $_SESSION['mt_alert'] ) )
		{
			try
			{
				$alert = static::load( $_SESSION['mt_alert'] );

				/* Only show details about this alert to the alert owner */
				if ( $alert->author()->member_id != $member->member_id )
				{
					return NULL;
				}

				return $alert;
			}
			catch( Exception $e ) { }
		}

		return NULL;
	}

	/**
	 * Set the alert to currently filter by
	 *
	 * @param Alert $alert
	 * @return void
	 */
	public static function setAlertCurrentlyFilteringMessages( Alert $alert ) : void
	{
		$_SESSION['mt_alert'] = $alert->id;
	}

	/**
	 * Unset any messenger filters
	 *
	 * @return void
	 */
	public static function clearMessengerFilters() : void
	{
		unset( $_SESSION['mt_alert'] );
	}

	/**
	 * Get the form to create a conversation based from an alert
	 *
	 * @param Member|null $member
	 * @return Form|null
	 */
	public function getNewConversationForm( ?Member $member = null ) : Form|null
	{
		$member = $member?: Member::loggedIn();
		if ( !$this->anonymous and $this->reply and $this->author()->member_id and $member->canUseMessenger())
		{
			$form = \IPS\core\Messenger\Conversation::create();
			$form->class = 'ipsForm ipsForm--fullWidth';
			$form->elements['']['messenger_content']->defaultValue = '<blockquote class="ipsQuote">' . $this->content . "</blockquote><br>";
			$form->elements['']['messenger_content']->setValue(TRUE , FALSE );
			$form->elements['']['messenger_to']->defaultValue = $form->elements['']['messenger_to']->value =  $this->author();
			$form->elements['']['messenger_title']->defaultValue = $form->elements['']['messenger_title']->value = \IPS\Member::loggedIn()->language()->get( "alert_response_prefix" ) . $this->title;
			$form->hiddenValues['alert'] = $this->id;
			$form->hiddenValues['messenger_title'] = $this->title;
			\IPS\Member::loggedIn()->language()->words['messenger_content'] = \IPS\Member::loggedIn()->language()->addToStack( 'reply' );
			return $form;
		}

		return null;
	}
}