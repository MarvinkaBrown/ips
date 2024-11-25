<?php
/**
 * @brief		Announcement model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Aug 2013
 */

namespace IPS\core\Announcements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Content\Controller;
use IPS\Content\Item;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use RuntimeException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Announcements Model
 */
class Announcement extends Item
{
	/**
	 * @brief	Title-only announcement
	 */
	const TYPE_NONE = 0;

	/**
	 * @brief	Standard announcement (title and body)
	 */
	const TYPE_CONTENT = 1;

	/**
	 * @brief	URL announcement (title and link)
	 */
	const TYPE_URL = 2;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_announcements';

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'announcements' );

	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'announce_';
	
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
	public static string $title = 'announcement';
	
	/**
	 * @brief	Title
	 */
	public static string $icon = 'bullhorn';

	/**
	 * Get page location array
	 *
	 * @return	array
	 */
	protected function get_page_location() : array
	{
		return explode( ',', $this->_data['page_location'] );
	}

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
	 * Load announcements by page location
	 *
	 * @param	string		$location		Page location: top, content or sidebar
	 * @return	array
	 */
	public static function loadAllByLocation( string $location ) : array
	{
		/* Are we banned? If so, do not return any announcements */
		if ( Member::loggedIn()->isBanned() )
		{
			return [];
		}

		$announcements = static::getStore();

		$return = array();
		foreach( $announcements as $announce )
		{
			$announcement = static::constructFromData( $announce );

			/* Check page location, active status and permissions */
			if( ! in_array( $location, $announcement->page_location ) OR !$announcement->active OR ( $announcement->start !== 0 AND $announcement->start >= time() ) OR ( $announcement->end !== 0 AND $announcement->end <= time() ) OR !$announcement->canView() )
			{
				continue;
			}

			/* Is this a global announcement? */
			if( $announcement->app == '*' )
			{
				$return[] = $announcement;
				continue;
			}

			/* If the dispatcher did not finish loading (perhaps we hit an error that occurred early in execution) we can't check per-app locations */
			if( !Dispatcher::i()->application )
			{
				continue;
			}

			$extensions = Dispatcher::i()->application->extensions( 'core', 'Announcements' );

			/* If we have no extension, we can only check the global setting */
			if ( !$extensions AND $announcement->app == Dispatcher::i()->application->directory )
			{
				$return[] = $announcement;
			}
			else
			{
				/* App and container specific announcements */
				foreach ( $extensions as $key => $extension )
				{
					$id = $extension::$idField;

					if ( $announcement->ids AND isset( Request::i()->$id ) )
					{
						/* Are we viewing a content item */
						if ( Dispatcher::i()->dispatcherController instanceof Controller )
						{
							foreach( Dispatcher::i()->application->extensions( 'core', 'ContentRouter' ) AS $contentRouter )
							{
								foreach( $contentRouter->classes AS $class )
								{
									try
									{
										if( $announcement->location == $key AND in_array( $class::load( Request::i()->$id )->mapped('container'), explode( ',', $announcement->ids ) ) )
										{
											$return[] = $announcement;
										}
									}
									catch( OutOfRangeException $e ){}
								}
							}
						}
						/* Or are we inside an allowed controller */
						else if (isset( Dispatcher::i()->dispatcherController ) AND in_array( get_class( Dispatcher::i()->dispatcherController ), $extension::$controllers ) )
						{
							try
							{
								if( $announcement->location == $key AND in_array(  Request::i()->$id , explode( ',', $announcement->ids ) ) )
								{
									$return[] = $announcement;
								}
							}
							catch( OutOfRangeException $e ){}
						}
					}
					/* App specific, doesn't matter which container we're in */
					elseif( $announcement->location == $key AND !$announcement->ids )
					{
						$return[] = $announcement;
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Display Form
	 *
	 * @param	static|NULL	$announcement	Existing announcement (for edits)
	 * @return	Form
	 */
	public static function form( ?Announcement $announcement ) : Form
	{
		/* Build the form */
		$form = new Form( NULL, 'save' );
		$form->class = 'ipsForm--vertical ipsForm--edit-announcement';
		
		$form->add( new Text( 'announce_title', ( $announcement ) ? $announcement->title : NULL, TRUE, array( 'maxLength' => 255 ) ) );
		$form->add( new Date( 'announce_start', ( $announcement ) ? DateTime::ts( $announcement->start ) : new DateTime ) );
		$form->add( new Date( 'announce_end', ( $announcement AND $announcement->end ) ? DateTime::ts( $announcement->end ) : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'indefinitely' ), function($val){

			/* Compare with announce_start and make sure it's after this */
			$start =	new DateTime( Request::i()->announce_start );
			if( $val and $val->getTimestamp() < $start->getTimestamp() )
			{
				throw new InvalidArgumentException( 'invalid_end_date' );
			}
		} ) );

		$form->add( new Radio( 'announce_type', ( $announcement ) ? $announcement->type : 'content', TRUE, array(
			'options' => array(
				static::TYPE_NONE		=> Member::loggedIn()->language()->addToStack( 'announce_type_none' ),
				static::TYPE_CONTENT	=> Member::loggedIn()->language()->addToStack( 'announce_type_content' ),
				static::TYPE_URL		=> Member::loggedIn()->language()->addToStack( 'announce_type_url' ),
			),
			'toggles' => array(
				static::TYPE_CONTENT 	=> array( 'announce_content' ),
				static::TYPE_URL 		=> array( 'announce_url' )
			 ),
		) ) );

		$form->add( new Editor( 'announce_content', ( $announcement ) ? $announcement->content : NULL, NULL, array( 'app' => 'core', 'key' => 'Announcement', 'autoSaveKey' => ( $announcement ? 'editAnnouncement__' . $announcement->id : 'createAnnouncement' ), 'attachIds' => $announcement ? array( $announcement->id, NULL, 'announcement' ) : NULL ), NULL, NULL, NULL, 'announce_content' ) );
		$form->add( new FormUrl( 'announce_url', ( $announcement ) ? $announcement->url : NULL, NULL, array( 'maxLength' => 2048 ), NULL, NULL, NULL, 'announce_url' ) );
		$form->add( new CheckboxSet( 'announce_page_location', ( $announcement ) ? $announcement->page_location : array(), TRUE, array( 'options' => array( 'top' => 'page_top', 'content' => 'content_top', 'sidebar' => 'sidebar' ) ) ) );

		/* Apps */
		$apps = array();
		
		foreach( Application::applications() as $key => $data )
		{
			if ( $key != 'core' )
			{
				/* Don't list apps without front modules or that are not being revealed */
				if( !count( $data->modules( 'front' ) ) OR $data->hide_tab )
				{
					continue;
				}
				$apps[ $key ] = $data->_title;
			}
		}
		$apps['core'] = 'announce_other_areas';
		
		$toggles = array();
		$formFields = array();
		
		foreach ( Application::allExtensions( 'core', 'Announcements', TRUE, 'core' ) as $key => $extension )
		{
			$app = mb_substr( $key, 0, mb_strpos( $key, '_' ) );

			/* Grab our fields and add to the form */
			$field	= $extension->getSettingField( $announcement );

			$toggles[ $app ][] = $field->name;
			$formFields[] = $field;
		}
		
		$form->add( new Select( 'announce_app', ( $announcement ) ? $announcement->app : '*', TRUE, array( 'options' => $apps,'toggles' => $toggles, 'unlimited' => "*", 'unlimitedLang' => "everywhere" ) ) );

		foreach( $formFields as $field )
		{
			$form->add( $field );
		}

		$groups = array();
		foreach ( Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}

		$form->add( new CheckboxSet( 'announce_permissions', $announcement ? ( $announcement->permissions == '*' ? '*' : explode( ',', $announcement->permissions ) ) : '*', NULL, array( 'multiple' => TRUE, 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'everyone', 'impliedUnlimited' => TRUE ) ) );
		$form->add( new Custom( 'announce_color', $announcement ? $announcement->color : 'information', NULL, array( 'getHtml' => function( $element )
        {
            return Theme::i()->getTemplate( 'forms', 'core', 'front' )->colorSelection( $element->name, $element->value );
        } ), NULL, NULL, NULL, 'announce_color' ) );

		return $form;
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->announcements ) )
		{
			Store::i()->announcements = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, "announce_id ASC" )->setKeyField( 'announce_id' ) );
		}

		return Store::i()->announcements;
	}
	
	/**
	 * Create from form
	 *
	 * @param	array	$values	Values from form
	 * @param Announcement|null $current	Current announcement
	 * @return    Announcement
	 */
	public static function _createFromForm( array $values, ?Announcement $current ) : static
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

		$obj->title			= $values['announce_title'];
		$obj->seo_title 	= Friendly::seoTitle( $values['announce_title'] );
		$obj->type			= $values['announce_type'];
		$obj->content		= $values['announce_content'];
		$obj->url			= $values['announce_url'];
		$obj->start			= $values['announce_start'] ? $values['announce_start']->getTimestamp() : time();
		$obj->end			= $values['announce_end'] ? $values['announce_end']->getTimestamp() : 0;
		$obj->app			= $values['announce_app'] ?: "*";
		$obj->permissions	= is_array( $values['announce_permissions'] ) ? implode( ',', $values['announce_permissions'] ) : '*';
		$obj->page_location = $values['announce_page_location'];
		$obj->color			= $values['announce_color'];

		/* We need to set the data, before we then iterate over the toggled extension form fields */
		$obj->location = "*";
		$obj->ids = NULL;

        if( in_array( $obj->app, array_keys(Application::applications() ) ) )
        {
            foreach ( Application::load( $obj->app )->extensions( 'core', 'Announcements' ) as $key => $extension )
            {
				$field = $extension->getSettingField( $obj );
				$obj->ids = is_array( $values[ $field->name ] ) ? implode( ",", array_keys( $values[ $field->name ] ) ) : $values[ $field->name ];
				$obj->location = mb_substr( $key, mb_strpos( $key, '_' ) );
			}
        }
		
		$obj->save();
		
		if( !$current )
		{
			File::claimAttachments( 'createAnnouncement', $obj->id, NULL, 'announcement' );
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
			if( $action )
			{
				$this->_url[ $_key ] = Url::internal( "app=core&module=modcp&controller=modcp&tab=announcements&id={$this->id}", 'front', 'modcp_announcements' );
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'action', $action );
			}
			else
			{
				$this->_url[ $_key ] = Url::internal( "app=core&module=system&controller=announcement&id={$this->id}", 'front', 'announcement', $this->seo_title );
			}
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
		File::unclaimAttachments( 'core_Announcement', $this->id, NULL, 'announcement' );
	}

	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( string $type, ?Member $member = NULL, ?Model $container = NULL ): bool
	{
		if( in_array( $type, array( 'move', 'merge', 'lock', 'unlock', 'feature', 'unfeature', 'pin', 'unpin' ) ) )
		{
			return FALSE;
		}

		if( $type == 'hide' OR $type == 'unhide' OR $type == 'active' OR $type == 'inactive' )
		{
			$member = $member ?: Member::loggedIn();
			return $member->modPermission( "can_manage_announcements" );
		}

		return parent::modPermission( $type, $member, $container );
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
	 * Get content table states
	 *
	 * @return string
	 */
	public function tableStates(): string
	{
		$states = explode( ' ', parent::tableStates() );

		if( !$this->active )
		{
			$states[]	= "inactive";
		}
		else
		{
			$states[]	= "active";
		}

		return implode( ' ', $states );
	}

	/**
	 * Do Moderator Action
	 *
	 * @param	string				$action	The action
	 * @param	Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param	string|NULL			$reason	Reason (for hides)
	 * @param	bool				$immediately	Delete immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction( string $action, ?Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		if( static::modPermission( $action, $member ) )
		{
			if( $action == 'active' )
			{
				Session::i()->modLog( 'modlog__action_announceactive', array( static::$title => TRUE, $this->url()->__toString() => FALSE, $this->mapped('title') => FALSE ), $this );

				$this->active	= 1;
				$this->save();

				return;
			}

			if( $action == 'inactive' )
			{
				Session::i()->modLog( 'modlog__action_announceinactive', array( static::$title => TRUE, $this->url()->__toString() => FALSE, $this->mapped('title') => FALSE ), $this );

				$this->active	= 0;
				$this->save();

				return;
			}
		}

		parent::modAction( $action, $member, $reason, $immediately );
	}

	/**
	 * Return any custom multimod actions this content item class supports
	 *
	 * @return	array
	 */
	public function customMultimodActions(): array
	{
		if( !$this->active )
		{
			return array( "active" );
		}
		else
		{
			return array( "inactive" );
		}
	}

	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( ?Member $member=NULL ): bool
	{
		/* If all groups have access, we can */
		if( $this->permissions == '*' )
		{
			return TRUE;
		}

		/* Check member */
		$member	= ( $member === NULL ) ? Member::loggedIn() : $member;
		$memberGroups	= array_merge( array( $member->member_group_id ), array_filter( explode( ',', $member->mgroup_others ) ) );
		$accessGroups	= explode( ',', $this->permissions );

		/* Are we in an allowed group? */
		if( count( array_intersect( $accessGroups, $memberGroups ) ) )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Return any available custom multimod actions this content item class supports
	 *
	 * @note	Return in format of array( array( 'action' => ..., 'icon' => ..., 'language' => ... ) )
	 * @return	array
	 */
	public static function availableCustomMultimodActions(): array
	{
		return array(
			array(
				'groupaction'	=> 'active',
				'icon'			=> 'eye',
				'grouplabel'	=> 'announce_active_status',
				'action'		=> array(
					array(
						'action'	=> 'active',
						'icon'		=> 'eye',
						'label'		=> 'announce_mark_active'
					),
					array(
						'action'	=> 'inactive',
						'icon'		=> 'eye',
						'label'		=> 'announce_mark_inactive'
					)
				)
			)
		);
	}

	/**
	 * Set page location array
	 *
	 * @param	array		$value	Page locations: top, content and sidebar
	 * @return	void
	 */
	protected function set_page_location( array $value ) : void
	{
		$this->_data['page_location'] = implode( ',', $value );
	}
}