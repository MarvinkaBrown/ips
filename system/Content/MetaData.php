<?php
/**
 * @brief		MetaData Trait for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Nov 2013
 */

namespace IPS\Content;

use IPS\Member;
use IPS\Db;
use IPS\Application;
use IPS\Request;
use IPS\Output;
use IPS\Theme;
use IPS\Member\Group;
use BadMethodCallException;
use OutOfRangeException;
use UnderflowException;
use InvalidArgumentException;

use function defined;
use function header;
use function get_class;
use function in_array;
use function time;
use function json_encode;
use function json_decode;
use function count;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * MetaData Trait for Content Models/Comments
 */
trait MetaData
{
	/**
	 * Metadata types supported by this content
	 *
	 * @return	array
	 */
	abstract static function supportedMetaDataTypes(): array;
	
	/**
	 * Check if this content has metadata
	 *
	 * @return	bool
	 * @throws    BadMethodCallException
	 */
	public function hasMetaData(): bool
	{
		if ( isset( static::$databaseColumnMap['meta_data'] ) )
		{
			$column = static::$databaseColumnMap['meta_data'];
			return (bool) $this->$column;
		}
		
		throw new BadMethodCallException( "Class using must define meta_data column in column map" );
	}
	
	/**
	 * @brief	Meta Data Cache
	 */
	protected ?array $_metaData = NULL;
	
	/**
	 * Fetch Meta Data
	 *
	 * @return	array
	 */
	public function getMeta(): array
	{
		/* If we don't have any, don't bother */
		if ( $this->hasMetaData() === FALSE )
		{
			return array();
		}
		
		$idColumn = static::$databaseColumnId;
		
		if ( $this->_metaData === NULL )
		{
			$this->_metaData = array();
			foreach( Db::i()->select( '*', 'core_content_meta', array( "meta_class=? AND meta_item_id=?", get_class( $this ), $this->$idColumn ) ) AS $row )
			{
				$this->_metaData[ $row['meta_type'] ][ $row['meta_id'] ] = json_decode( $row['meta_data'], TRUE );
			}
		}
		
		return $this->_metaData;
	}
	
	/**
	 * Add Meta Data
	 *
	 * @param	string	$type	The type of data
	 * @param	array	$data	The data
	 * @return	int		The ID of the inserted metadata record
	 * @throws    BadMethodCallException
	 */
	public function addMeta( string $type, array $data ): int
	{
		if ( !static::supportedMetaDataTypes() OR !in_array( $type, static::supportedMetaDataTypes() ) )
		{
			throw new BadMethodCallException;
		}
		
		$idColumn = static::$databaseColumnId;
		$data = json_encode( $data );
		$id = Db::i()->insert( 'core_content_meta', array(
			'meta_class'		=> get_class( $this ),
			'meta_item_id'		=> $this->$idColumn,
			'meta_type'			=> $type,
			'meta_data'			=> $data,
			'meta_item_author'  => (int) $this->author()->member_id,
			'meta_added' 		=> time()
		) );
		
		$column = static::$databaseColumnMap['meta_data'];
		$this->$column = 1;
		$this->save();
		
		$this->_metaData = NULL;
		
		return $id;
	}
	
	/**
	 * Edit Meta Data
	 *
	 * @param	int		$id		The ID
	 * @param	array	$data	The data
	 * @return	void
	 * @throws BadMethodCallException
	 */
	public function editMeta( int $id, array $data ): void
	{
		try
		{
			/* Get current data */
			$idColumn = static::$databaseColumnId;
			
			$current = json_decode( Db::i()->select( 'meta_data', 'core_content_meta', array( "meta_class=? AND meta_item_id=? AND meta_id=?", get_class( $this ), $this->$idColumn, $id ) )->first(), true );
			
			foreach ( $data as $key => $value )
			{
				$current[ $key ] = $value;
			}

			Db::i()->update( 'core_content_meta', array( 'meta_data' => json_encode( $current ) ), array( "meta_id=?", $id ) );
			
			/* Make sure our flag is set */
			$column = static::$databaseColumnMap['meta_data'];
			$this->$column = TRUE;
			$this->save();
			
			$this->_metaData = NULL;
		}
		catch( UnderflowException )
		{
			throw new OutOfRangeException;
		}
	}
	
	/**
	 * Delete Meta Data
	 *
	 * @param	int		$id		The ID
	 * @return	void
	 */
	public function deleteMeta( int $id ): void
	{
		$idColumn = static::$databaseColumnId;
		Db::i()->delete( 'core_content_meta', array( "meta_class=? AND meta_item_id=? AND meta_id=?", get_class( $this ), $this->$idColumn, $id ) );
		
		/* Any left? */
		$count = Db::i()->select( 'COUNT(*)', 'core_content_meta', array( "meta_class=? AND meta_item_id=?", get_class( $this ), $this->$idColumn ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		
		if ( !$count )
		{
			$column = static::$databaseColumnMap['meta_data'];
			$this->$column = FALSE;
			$this->save();
		}
		
		$this->_metaData = NULL;
	}
	
	/**
	 * Delete All Meta Data
	 *
	 * @return	void
	 */
	public function deleteAllMeta(): void
	{
		$idColumn = static::$databaseColumnId;
		Db::i()->delete( 'core_content_meta', array( "meta_class=? AND meta_item_id=?", get_class( $this ), $this->$idColumn ) );
	}
	
	/**
	 * Can perform an action on a message
	 *
	 * @param	string				$action	The action
	 * @param	Member|NULL	$member	The member, or NULL for currently logged in
	 * @return	bool
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function canOnMessage( string $action, ?Member $member = NULL ): bool
	{
		if( !$this->actionEnabled( 'onMessage', $member ) )
		{
			return false;
		}

		return Application::load('core')->extensions( 'core', 'MetaData' )['ContentMessages']->canOnMessage( $action, $this, $member );
	}
	
	/**
	 * Add Item Message
	 *
	 * @param	string				$message		The message
	 * @param	string|NULL			$color			The message color
	 * @param	Member|NULL	$member			User adding the message
	 * @param	bool				$isPublic		Who should see the message
	 * @return	int
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function addMessage( string $message, ?string $color = NULL, ?Member $member = NULL, bool $isPublic = TRUE  ): int
	{
		return Application::load('core')->extensions( 'core', 'MetaData' )['ContentMessages']->addMessage( $message, $color, $this, $member, $isPublic );
	}
	
	/**
	 * Edit Item Message
	 *
	 * @param	int					$id			The ID
	 * @param	string				$message	The new message
	 * @param	string|NULL			$color		Color
	 * @param	Member|NULL	$member		The member editing the message, or NULL for currently logged in
	 * @param	bool				$onlyStaff		Who should see the message
	 * @return	void
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function editMessage( int $id, string $message, ?string $color = NULL, ?Member $member = NULL, bool $onlyStaff = FALSE ): void
	{
		Application::load('core')->extensions( 'core', 'MetaData' )['ContentMessages']->editMessage( $id, $message, $color, $this, $member, $onlyStaff );
	}
	
	/**
	 * Delete Item Message
	 *
	 * @param	int					$id		The ID
	 * @param	Member|NULL	$member	The member deleting the message
	 * @return	void
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function deleteMessage( int $id, ?Member $member = NULL ): void
	{
		Application::load('core')->extensions( 'core', 'MetaData' )['ContentMessages']->deleteMessage( $id, $this, $member );
	}
	
	/**
	 * Get Item Messages
	 *
	 * @return	array
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function getMessages(): array
	{
		return Application::load('core')->extensions( 'core', 'MetaData' )['ContentMessages']->getMessages( $this );
	}

	/**
	 * Is a featured comment?
	 *
	 * @param Comment $comment
	 * @return    bool
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function isFeaturedComment( Comment $comment ): bool
	{
		return Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->isFeaturedComment( $comment );
	}

	/**
	 * Can Feature a Comment
	 *
	 * @param	Member|NULL	$member	The member, or NULL for currently logged in
	 * @return	bool
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function canFeatureComment( ?Member $member = NULL ): bool
	{
		if( !$this->actionEnabled( 'featureComment', $member ) )
		{
			return false;
		}

		return Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->canFeatureComment( $this, $member );
	}
	
	/**
	 * Can Unfeature a Comment
	 *
	 * @param	Member|NULL	$member	The member, or NULL for currently logged in
	 * @return	bool
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function canUnfeatureComment( ?Member $member = NULL ): bool
	{
		if( !$this->actionEnabled( 'unfeatureComment', $member ) )
		{
			return false;
		}

		return Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->canUnfeatureComment( $this, $member );
	}
	
	/**
	 * Feature A Comment
	 *
	 * @param	Comment	$comment	The Comment
	 * @param	string|NULL				$note		An optional note to include
	 * @param	Member|NULL		$member		The member featuring the comment
	 * @return	void
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function featureComment( Comment $comment, ?string $note = NULL, ?Member $member = NULL ): void
	{
		Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->featureComment( $this, $comment, $note, $member );

		/* Give points */
		$comment->author()->achievementAction( 'core', 'ContentPromotion', [
			'content'	=> $comment,
			'promotype'	=> 'recommend' //Yeah it says feature, but it's really recommend
		] );
	}
	
	/**
	 * Unfeature a comment
	 *
	 * @param	Comment	$comment	The Comment
	 * @param	Member|NULL		$member		The member unfeaturing the comment
	 * @return	void
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function unfeatureComment( Comment $comment, ?Member $member = NULL ): void
	{
		Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->unfeatureComment( $this, $comment, $member );
	}
	
	/**
	 * Get Featured Comments in the most efficient way possible
	 *
	 * @return	array
	 */
	public function featuredComments(): array
	{
		$featured = Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->featuredComments( $this );

		if ( Request::i()->isAjax() && Request::i()->recommended == 'comments' )
		{
			/* @todo this really shouldn't be here, and should be in the controller */
			Output::i()->json( array( 
				'html' => Theme::i()->getTemplate( 'global', 'core', 'front' )->featuredComments( $featured, $this->url()->setQueryString( 'recommended', 'comments' ) ),
				'count' => count( $featured )
			) );
		}

		return $featured;
	}
	
	/**
	 * Is item-level moderation enabled?
	 *
	 * @param	Member|Group|NULL	$memberOrGroup		A member of member group to check for bypassing moderation.
	 * @return	bool
	 */
	public function itemModerationEnabled( Member|Group|null $memberOrGroup = NULL ): bool
	{
		return (bool) Application::load('core')->extensions( 'core', 'MetaData' )['ItemModeration']->enabled( $this, $memberOrGroup );
	}
	
	/**
	 * Can toggle item-level moderation?
	 *
	 * @param	Member|NULL		$member
	 * @return	bool
	 */
	public function canToggleItemModeration( ?Member $member = NULL ): bool
	{
		if( !$this->actionEnabled( 'toggleItemModeration', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();
		
		return (bool) Application::load('core')->extensions( 'core', 'MetaData' )['ItemModeration']->canToggle( $this, $member );
	}
	
	/**
	 * Toggle item-level moderation
	 *
	 * @param	string				$action
	 * @param	Member|NULL		$member
	 * @return	void
	 * @throws
	 *	@li \InvalidArgumentException
	 *	@li \BadMethodCallException
	 */
	public function toggleItemModeration( string $action, ?Member $member = NULL ): void
	{
		if ( !in_array( $action, array( 'enable', 'disable' ) ) )
		{
			throw new InvalidArgumentException;
		}
		
		$member = $member ?: Member::loggedIn();
		
		if ( !$this->canToggleItemModeration( $member ) )
		{
			throw new BadMethodCallException;
		}
		
		Application::load('core')->extensions( 'core', 'MetaData' )['ItemModeration']->$action( $this, $member );
	}
}