<?php
/**
 * @brief		Club Page Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Feb 2017
 */

namespace IPS\Member\Club;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Http\Url\Internal;
use IPS\Member;
use IPS\Member\Club;
use IPS\Patterns\ActiveRecord;
use IPS\Settings;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Club Page Model
 */
class Page extends ActiveRecord
{
		/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_club_pages';
	
	/**
	 * @brief	[ActiveRecord]	Database Prefix
	 */
	public static string $databasePrefix = 'page_';
	
	/**
	 * @brief	Club Store
	 */
	protected static array $clubs = array();
	
	/**
	 * Get Club
	 *
	 * @return	Club
	 */
	public function get_club(): Club
	{
		if ( !isset( static::$clubs[ $this->_data['club'] ] ) )
		{
			static::$clubs[ $this->_data['club'] ] = Club::load( $this->_data['club'] );
		}
		
		return static::$clubs[ $this->_data['club'] ];
	}
	
	/**
	 * Set Club
	 *
	 * @param	Club		$club	The club
	 * @return	void
	 */
	public function set_club( Club $club ) : void
	{
		$this->_data['club'] = $club->id;
		unset( static::$clubs[ $club->id ] );
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function get_title(): string
	{
		if ( !$this->_data['seo_title'] )
		{
			$this->seo_title = Friendly::seoTitle( $this->_data['title'] );
			$this->save();
		}
		
		return $this->_data['title'];
	}
	
	/**
	 * Set Title
	 *
	 * @param	string	$title	The page title
	 * @return	void
	 */
	public function set_title( string $title ) : void
	{
		$this->_data['title'] = $title;
		$this->_data['seo_title'] = Friendly::seoTitle( $title );
	}
	
	/**
	 * Set who can view this page
	 *
	 * @param	array|NULL	$value		The value to set
	 * @return	void
	 */
	public function set_can_view( ?array $value ) : void
	{
		if ( is_array( $value ) )
		{
			$this->_data['can_view'] = implode( ',', $value );
		}
		else
		{
			$this->_data['can_view'] = NULL;
		}
	}
	
	/**
	 * Get who can view this page
	 *
	 * @return	array|NULL
	 */
	public function get_can_view(): ?array
	{
		return $this->_data['can_view'] ? explode( ',', $this->_data['can_view'] ) : NULL;
	}
	
	/**
	 * Form
	 *
	 * @param	Form			$form		Form Object
	 * @param	Club				$club		Club this page belongs too.
	 * @param Page|NULL	$current	If we are editing, the current page.
	 * @return	void
	 */
	public static function form( Form $form, Club $club, ?Page $current = NULL ) : void
	{
		$form->hiddenValues['page_club'] = $club->id;
		$form->add( new Text( 'club_page_title', ( $current ) ? $current->title : NULL, TRUE ) );
		$form->add( new Editor( 'club_page_content', ( $current ) ? $current->content : NULL, TRUE, array(
			'app'			=> 'core',
			'key'			=> 'ClubPage',
			'autoSaveKey'	=> ( $current ) ? "club-page-{$current->id}" : "club-page-new",
			'attachIds'		=> ( $current ) ? array( $current->id, NULL, NULL ) : NULL
		) ) );
		
		if ( $club->type !== Club::TYPE_PUBLIC )
		{
			$defaults = array( 'member', 'moderator' );
			if ( $club->type !== Club::TYPE_PRIVATE )
			{
				$defaults[] = 'nonmember';
			}
			$form->add( new CheckboxSet( 'page_can_view', ( $current AND $current->can_view ) ? $current->can_view : $defaults, FALSE, array( 'options' => array(
				'nonmember'	=> 'club_page_nonmembers',
				'member'		=> 'club_page_members',
				'moderator'	=> 'club_page_moderators'
			) ) ) );
		}

		/* Add the index setting if this page is shown to guests */
		if ( $club->type !== Club::TYPE_PRIVATE AND Member::loggedIn()->group['gbw_club_manage_indexing'] )
		{
			$form->add( new YesNo('club_page_meta_index', ( $current ) ? $current->meta_index : TRUE, FALSE, [], NULL, NULL, NULL, 'club_page_meta_index' ) );
		}
	}
	
	/**
	 * Format Form Values
	 *
	 * @param	$values		array	Values
	 * @return	void
	 */
	public function formatFormValues( array $values ) : void
	{
		$this->club			= Club::load( $values['page_club'] );
		$this->title			= $values['club_page_title'];
		$this->content		= $values['club_page_content'];
		if ( array_key_exists( 'page_can_view', $values ) )
		{
			$this->can_view		= $values['page_can_view'];
		}
		else
		{
			$this->can_view		= NULL;
		}
		if( array_key_exists( 'club_page_meta_index', $values ) )
		{
			$this->meta_index	= $values['club_page_meta_index'];
		}
	}
	
	/**
	 * URL
	 *
	 * @param	NULL|string		$action		Value for the "do" parameter, or NULL for no action.
	 * @return	Friendly
	 */
	public function url( ?string $action = NULL ): Internal
	{
		$return = Url::internal( "app=core&module=clubs&controller=page&id={$this->id}", 'front', 'clubs_page', array( $this->seo_title ) );
		
		if ( $action )
		{
			$return = $return->setQueryString( 'do', $action );
		}
		
		return $return;
	}
	
	/**
	 * Load and check permissions
	 *
	 * @param	int					$id			ID of the page to load
	 * @param	Member|NULL		$member		Optional member to check against
	 * @return    Page
	 * @throws OutOfRangeException
	 */
	public static function loadAndCheckPerms( int $id, ?Member $member = NULL ): Page
	{
		$page = static::load( $id );
		
		if ( !$page->canView( $member ) )
		{
			throw new OutOfRangeException;
		}
		
		return $page;
	}
	
	/**
	 * Can view this page
	 *
	 * @param	Member|NULL		$member	The member trying to view the page.
	 * @return	bool
	 */
	public function canView( ?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		
		/* If NULL, everyone can view */
		if ( $this->can_view === NULL )
		{
			return TRUE;
		}
		
		/* Site moderators can see everything */
		if ( $member->modPermission('can_access_all_clubs') )
		{
			return TRUE;
		}
		
		/* If it's not approved, only moderators and the person who created it can see it */
		if ( Settings::i()->clubs_require_approval and !$this->club->approved )
		{
			return ( $member->modPermission('can_access_all_clubs') or ( $this->club->owner AND $member->member_id == $this->club->owner->member_id ) );
		}
		
		/* Owner or leader? */
		if ( $member->member_id === $this->club->owner->member_id OR $this->club->memberStatus( $member ) === Club::STATUS_LEADER )
		{
			return TRUE;
		}
		
		/* Moderators? */
		if ( in_array( 'moderator', $this->can_view ) AND $this->club->memberStatus( $member ) === Club::STATUS_MODERATOR )
		{
			return TRUE;
		}
		
		/* Members */
		if ( in_array( 'member', $this->can_view ) AND in_array( $this->club->memberStatus( $member ), array( Club::STATUS_MEMBER, Club::STATUS_INVITED, Club::STATUS_INVITED_BYPASSING_PAYMENT, Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR ) ) )
		{
			return TRUE;
		}
		
		if ( in_array( 'nonmember', $this->can_view ) )
		{
			return TRUE;
		}
		
		/* Still here? Nothing worked */
		return FALSE;
	}
	
	/**
	 * Can Add a page
	 *
	 * @param	Club		$club	The club the page will be added too.
	 * @param	Member|NULL		$member	The member adding the page.
	 * @return	bool
	 */
	public static function canAdd( Club $club, ?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return $club->owner->member_id === $member->member_id OR $club->isLeader( $member );
	}
	
	/**
	 * Can edit this page
	 *
	 * @param	Member|NULL			$member	The member editing the page.
	 * @return	bool
	 * @note	Functionally, this is no different from canAdd, however it's been abstracted out for third parties.
	 */
	public function canEdit( ?Member $member = NULL ): bool
	{
		return static::canAdd( $this->club, $member );
	}
	
	/**
	 * Can delete this page
	 *
	 * @param	Member|NULL $member
	 * @return	bool
	 * @note	Functionally, this is no different from canAdd, however it's been abstracted out for third parties.
	 */
	public function canDelete( ?Member $member = NULL ): bool
	{
		return static::canAdd( $this->club, $member );
	}
	
	/**
	 * Delete
	 *
	 * @param bool $updateClub		Update club tabs
	 * @return    void
	 */
	public function delete( bool $updateClub=TRUE ): void
	{
		File::unclaimAttachments( 'core_ClubPage', $this->id );

		if( $updateClub === TRUE )
		{
			$tabs = @json_decode( $this->club->menu_tabs, TRUE );
			if ( is_countable( $tabs ) AND count( $tabs ) )
			{
				if ( isset( $tabs['page-' . $this->id] ) )
				{
					unset( $tabs['page-' . $this->id] );
					$this->club->menu_tabs = json_encode( $tabs );
					$this->club->save();
				}
			}
		}
		
		parent::delete();
	}
}