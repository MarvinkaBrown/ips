<?php
/**
 * @brief		Deletion Log Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Nov 2016
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Review;
use IPS\Db;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Deletion Log Model
 */
class Approval extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_approval_queue';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'approval_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * Set Held Data
	 *
	 * @param	NULL|array	$data	The data indicating why the content was held for approval
	 * @return	void
	 */
	public function set_held_data( ?array $data ) : void
	{
		if ( is_array( $data ) )
		{
			$this->_data['held_data'] = json_encode( $data );
			return;
		}
		
		$this->_data['held_data'] = NULL;
	}
	
	/**
	 * Get Held Data
	 *
	 * @return	array|null
	 */
	public function get_held_data(): ?array
	{
		if ( $this->_data['held_data'] )
		{
			return json_decode( $this->_data['held_data'], true );
		}
		
		return NULL;
	}
	
	/**
	 * Set Held Reason
	 *
	 * @param	NULL|string		$reason		The reason for requiring approval.
	 * @return	void
	 */
	public function set_held_reason( ?string $reason ) : void
	{
		if ( $reason AND in_array( $reason, static::availableReasons() ) )
		{
			$this->_data['held_reason'] = $reason;
			return;
		}
		
		$this->_data['held_reason'] = NULL;
	}
	
	/**
	 * Get Held Reason
	 *
	 * @param	Member|NULL	$member		The member, or NULL for currently logged in.
	 * #return	NULL|string
	 */
	public function reason( ?Member $member = NULL ): string
	{
		$member = $member ?: Member::loggedIn();
		if ( $this->held_reason )
		{
			if ( $extra = $this->parseReason() )
			{
				return $member->language()->addToStack( $extra['lang'], TRUE, ( isset( $extra['sprintf'] ) ) ? array( 'sprintf' => $extra['sprintf'] ) : array() );
			}
			else
			{
				return $member->language()->addToStack( "approval_reason_{$this->held_reason}" );
			}
		}
		else
		{
			return $member->language()->addToStack( "approval_reason_unknown" );
		}
	}
	
	/**
	 * Parse Reason
	 *
	 * @param	Member|NULL	$member		The member, or NULL for currently logged in.
	 * @return	NULL|array
	 */
	public function parseReason( ?Member $member = NULL ): ?array
	{
		$member = $member ?: Member::loggedIn();
		if ( $this->held_reason )
		{
			switch( $this->held_reason )
			{
				case 'profanity':
					return array(
						'lang'		=> 'approval_reason_profanity',
						'sprintf'	=> array( $this->held_data['word'] )
					);
				
				case 'url':
					return array(
						'lang'		=> 'approval_reason_url',
						'sprintf'	=> array( $this->held_data['url'] )
					);
				
				case 'email':
					return array(
						'lang'		=> 'approval_reason_email',
						'sprintf'	=> array( $this->held_data['email'] )
					);
				
				case 'node':
					$contentClass = $this->content_class;
					$content = $contentClass::load( $this->content_id );
					return array(
						'lang'		=> 'approval_reason_node',
						'sprintf'	=> array( $content->indefiniteArticle(), $content->container()->url(), $content->container()->getTitleForLanguage( $member->language() ), $content->definiteArticle( $member->language(), 2 ) )
					);
				
				case 'item':
					$contentClass = $this->content_class;
					$content = $contentClass::load( $this->content_id );
					$title = ( $content instanceof Comment ) ? $content->item()->mapped('title') : $content->mapped('title');
					return array(
						'lang'		=> 'approval_reason_item',
						'sprintf'	=> array( $content->url(), $title )
					);
				
				default:
					/* See if we have an extension */
					foreach( Application::allExtensions( 'core', 'ApprovalReason' ) AS $ext )
					{
						if ( $ext->reasonKey() === $this->held_reason AND $data = $ext->parseReason( $this ) )
						{
							return $data;
						}
					}
					return NULL;
			}
		}
		
		return NULL;
	}
	
	/**
	 * Available Reasons
	 *
	 * @return	array
	 */
	public static function availableReasons(): array
	{
		$reasons = array(
			'profanity',
			'url',
			'email',
			'user',
			'group',
			'node',
			'item'
		);

		if ( Application::appIsEnabled( 'cloud' ) )
		{
			$reasons[] = 'image';
		}
		
		foreach( Application::allExtensions( 'core', 'ApprovalReason' ) AS $ext )
		{
			$reasons[] = $ext->reasonKey();
		}

		return $reasons;
	}
	
	/**
	 * Load from Content
	 *
	 * @param	string	$class	Content class
	 * @param	int		$id		Content ID
	 * @return	static
	 * @throws OutOfRangeException
	 */
	public static function loadFromContent( string $class, int $id ): static
	{
		try
		{
			return static::constructFromData( Db::i()->select( '*', 'core_approval_queue', array( "approval_content_class=? AND approval_content_id=?", $class, $id ) )->first() );
		}
		catch( UnderflowException )
		{
			throw new OutOfRangeException;
		}
	}

	/**
	 * Load the item that is pending approval
	 *
	 * @return Content|Member\Club|null
	 */
	public function item() : Content|Member\Club|null
	{
		$class = $this->content_class;
		try
		{
			return $class::load( $this->content_id );
		}
		catch( OutOfRangeException )
		{
			return null;
		}
	}

	/**
	 * Output of the row for the ModCP
	 *
	 * @return string
	 */
	public function html() : string
	{
		$item = $this->item();
		if( !$item )
		{
			return "";
		}

		$container = NULL;
		$actions = $this->modActions( $item );

		if( $item instanceof Member\Club )
		{
			return Theme::i()->getTemplate( 'modcp', 'core', 'front' )->approvalQueueClubWrapper( Theme::i()->getTemplate('clubs')->clubCard( $item, TRUE ), $actions );
		}

		if ( $item instanceof Comment )
		{
			$itemClass = $item::$itemClass;
			$ref = base64_encode( json_encode( array( 'app' => $itemClass::$application, 'module' => $itemClass::$module, 'id_1' => $item->mapped('item'), 'id_2' => $item->id ) ) );
			$title = $item->item()->mapped('title');
			$container = $item->item()->container();
		}
		else
		{
			$ref = base64_encode( json_encode( array( 'app' => $item::$application, 'module' => $item::$module, 'id_1' => $item->id ) ) );

			try
			{
				$container = $item->container();
			}
			catch ( Exception ) { }

			$title = $item->mapped('title');
		}

		return Theme::i()->getTemplate( 'modcp', 'core', 'front' )->approvalQueueItemWrapper( $item->approvalQueueHtml( $ref, $container, $title ), $actions, $this->id );
	}

	/**
	 * Figure out which moderation actions are available for this item
	 *
	 * @param Content|Member\Club $item
	 * @return array
	 */
	public function modActions( Content|Member\Club $item ) : array
	{
		$idColumn = $item::$databaseColumnId;
		$actions = [];

		if( $item instanceof Member\Club )
		{
			$actions['approve'] = $item->url()->setQueryString( array( 'do' => 'approve', 'approved' => 1 ) )->csrf();
			$actions['delete'] = $item->url()->setQueryString( array( 'do' => 'approve', 'approved' => 0 ) )->csrf();
			return $actions;
		}

		if ( $item instanceof Comment )
		{
			$classType = ( $item instanceof Review ) ? 'Review' : 'Comment';
			if( $item->canUnhide() )
			{
				$actions['approve'] = $item->url()->setQueryString( array( 'do' => 'unhide' . $classType, strtolower( $classType ) => $item->$idColumn ) )->csrf();
			}
			if( $item->canDelete() )
			{
				$actions['delete'] = $item->url()->setQueryString( array( 'do' => 'delete' . $classType, strtolower( $classType ) => $item->$idColumn ) )->csrf();
			}
			if( $item->canHide() )
			{
				$actions['hide'] = $item->url()->setQueryString( array( 'do' => 'hide' . $classType, strtolower( $classType ) => $item->$idColumn ) )->csrf();
			}
		}
		else
		{
			if( $item->canUnhide() )
			{
				$actions['approve'] = $item->url()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) )->csrf();
			}
			if( $item->canDelete() )
			{
				$actions['delete'] = $item->url()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) )->csrf();
			}
			if( $item->canHide() )
			{
				$actions['hide'] = $item->url()->setQueryString( array( 'do' => 'moderate', 'action' => 'hide', '_fromApproval' => 1 ) )->csrf();
			}
		}

		return $actions;
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		/* Store the container ID */
		if( $this->_new OR $this->container_id === null )
		{
			try
			{
				$class = $this->content_class;
				$item = $class::load( $this->content_id );
				if( $item instanceof Item )
				{
					$this->container_id = $item->mapped( 'container' );
				}
				elseif( $item instanceof Comment )
				{
					$this->container_id = $item->item()->mapped( 'container' );
				}
			}
			catch( OutOfRangeException ){}
		}

		parent::save();
	}

}