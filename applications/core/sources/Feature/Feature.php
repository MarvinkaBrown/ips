<?php
/**
 * @brief		Promote model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Feb 2017
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecord;
use IPS\Settings;
use OutofRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_class;
use function htmlspecialchars;
use function intval;
use function is_array;
use function nl2br;
use function str_replace;
use const ENT_DISALLOWED;
use const ENT_QUOTES;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Promote Model
 */
class Feature extends ActiveRecord
{
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_content_promote';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'promote_';
	
	/**
	 * @brief	Class object
	 */
	protected Content|Model|null $object = NULL;
	
	/**
	 * @brief	Author object
	 */
	protected ?Member $author = NULL;
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		/* Ensure TEXT fields are never NULL */
		$this->_data['text'] = array();
		$this->_data['images'] = array();
		$this->_data['media'] = array();
	}

	/**
	 * Set the "text" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_text( string|array $value ) : void
	{
		$this->_data['text'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "text" field
	 *
	 * @return array|null
	 */
	public function get_text() : array|null
	{
		return json_decode( $this->_data['text'], TRUE );
	}
	
	/**
	 * Set the "attach_ids" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_images( string|array $value ) : void
	{
		$this->_data['images'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "attach_ids" field
	 *
	 * @return array
	 */
	public function get_images() : array
	{
		return $this->_data['images'] ? json_decode( $this->_data['images'], TRUE ) : array();
	}
	
	/**
	 * Set the "media" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_media( string|array $value ) : void
	{
		$this->_data['media'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "media" field
	 *
	 * @return array
	 */
	public function get_media() : array
	{
		return $this->_data['media'] ? json_decode( $this->_data['media'], TRUE ) : array();
	}
	
	/**
	 * Set the "form_data" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_form_data( string|array $value ) : void
	{
		$this->_data['form_data'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "form_data" field
	 *
	 * @return array
	 */
	public function get_form_data() : array
	{
		return $this->_data['form_data'] ? json_decode( $this->_data['form_data'], TRUE ) : array();
	}
	
	/**
	 * The author object
	 *
	 * @return Member
	 */
	public function author() : Member
	{
		if ( $this->author === NULL )
		{
			if ( $this->added_by )
			{
				try
				{
					$this->author = Member::load( $this->added_by );
				}
				catch ( Exception $e )
				{
					$this->author = new Member;
					$this->author->name = Settings::i()->board_name;
				}
			}
			else
			{
				$this->author = new Member;
				$this->author->name = Settings::i()->board_name;
			}
		}
		
		return $this->author;
	}
	
	/**
	 * The content object
	 *
	 * @return Content
	 */
	public function object() : Content
	{
		if ( $this->object === NULL )
		{
			$class = $this->class;
			if( class_exists( $class ) and IPS::classUsesTrait( $class, 'IPS\Content\Featurable' ) )
			{
				$this->object = $class::load( $this->class_id );
			}
		}
		
		return $this->object;
	}
	
	/**
	 * Get Our Picks title
	 *
	 * @return string
	 */
	public function get_ourPicksTitle() : string
	{
		$settings = $this->form_data;
		return ( isset( $settings['internal']['title'] ) and ! empty( $settings['internal']['title'] ) ) ? $settings['internal']['title'] : $this->objectTitle;
	}
	
	/**
	 * Get the object title
	 *
	 * @return string
	 */
	public function get_objectTitle() : string
	{
		return static::objectTitle( $this->object() );
	}
	
	/**
	 * Get the object date posted
	 *
	 * @return DateTime|NULL
	 */
	public function get_objectDatePosted() : DateTime|NULL
	{
		$object = $this->object();
		
		if ( isset( $object::$databaseColumnMap['date'] ) )
		{
			return DateTime::ts( $object->mapped('date') );
		}
			
		/* Valid object, but there isn't any date data available */
		return null;
	}

	/**
	 * Get the object unread status
	 *
	 * @return bool
	 */
	public function get_objectIsUnread() : bool
	{
		$object = $this->object();
		
		if ( $object instanceof Item )
		{
			return $object->unread() === 1;
		}
		else
		{
			return $object->item()->unread() === 1;
		}
	}

	/**
	 * Get the number and indefinite article for replies/children where applicable
	 *
	 * @return array|null
	 */
	public function get_objectDataCount() : array|NULL
	{
		return $this->objectDataCount();
	}

	/**
	 * Get the number and indefinite article for replies/children where applicable
	 *
	 * @param	Lang|null	$language	Language to use (or NULL for currently logged in member's language)
	 * @return	array|null
	 */
	public function objectDataCount( ?Lang $language=NULL ) : array|NULL
	{
		$language	= $language ?: Member::loggedIn()->language();
		$object		= $this->object();
		
		if ( $object instanceof Item )
		{
			try
			{
				$container = $object->container();
			}
			catch( Exception $e ){}

			if ( $object::supportsComments( NULL, $container ) )
			{
				$count = $object->mapped('num_comments');

				if ( $count AND isset( $object::$firstCommentRequired ) AND $object::$firstCommentRequired === TRUE )
				{
					$count--;
				}
				
				return array( 'count' => $count, 'words' => $language->addToStack( 'num_replies', FALSE, array( 'pluralize' => array( $count ) ) ) );
			}

			if ( $object::supportsReviews( NULL, $container ) )
			{
				$count = $object->mapped('num_reviews');

				return array( 'count' => $count, 'words' => $language->addToStack( 'num_reviews', FALSE, array( 'pluralize' => array( $count ) ) ) );
			}
		}

		return null;
	}
	
	/**
	 * Get reactions class for this object
	 *
	 * @return Content|null
	 */
	public function get_objectReactionClass() : Content|NULL
	{
		$object = $this->object();
		$class = NULL;
		
		if ( ! Settings::i()->reputation_enabled )
		{
			return NULL;
		}
		
		if ( $object instanceof Item )
		{
			/* The first post has the reactions for this item */
			if ( $object::$firstCommentRequired )
			{
				try
				{
					$class = $object->comments( 1, NULL, 'date', 'asc' );
				}
				catch( Exception $e )
				{

				}
			}
			else
			{
				$class = $object;
			}
		}
		else
		{
			$class = $object;
		}
		
		return ( $class and IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) ) ? $class : NULL;
	}
	
	/**
	 * Return an array of File objects
	 *
	 * @return array|null
	 */
	public function imageObjects() : array|NULL
	{
		$photos = array();
		if ( count( $this->images ) )
		{
			foreach( $this->images as $image )
			{
				foreach( $image as $ext => $url )
				{
					$photos[] = File::get( $ext, $url );
				}
			}
		}
		
		if ( count( $this->media ) )
		{
			foreach( $this->media as $media )
			{
				$photos[] = File::get( 'core_Promote', $media );
			}
		}
		
		return ( count( $photos ) ) ? $photos : NULL;
	}
	
	/**
	 * Look for a specific image
	 *
	 * @param	string	$path		Image path monthly_x_x/foo.gif
	 * @param	string	$extension	Storage extension
	 * @return boolean
	 */
	public function hasImage( string $path, string $extension='core_Attachment' ) : bool
	{
		foreach( $this->images as $image )
		{
			foreach( $image as $ext => $url )
			{
				if ( $ext == $extension and $path == $url )
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Returns text sent to a named service
	 *
	 * @param	boolean		$forDisplay		Is this for display in output?
	 * @return  string
	 */
	public function getText( bool $forDisplay=false ) : string
	{
		$text = $this->text;

		if ( isset( $text['internal'] ) )
		{
			return ( $forDisplay ? nl2br( htmlspecialchars( $text[ 'internal' ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) ) : $text['internal'] );
		}

		/* Fall back to the original content @note can this be more efficient when listing multiple items? */

		return strip_tags( $forDisplay ? str_replace( [ "<br>", "</p>" ], "<br>", $this->object()->content() ) : str_replace( [ "<br>", "</p>" ], "\n", $this->object()->content() ) );
	}
	
	/**
	 * Sets text for a named service
	 *
	 * @param	string		$text			Text to save
	 * @return  void
	 */
	public function setText( string $text ) : void
	{
		$allText = $this->text;
		$allText[ 'internal' ] = $text;
		$this->text = $allText;
	}
	
	/**
	 * @brief	Cache internal stream data
	 */
	protected static array $internalStream = array();

	/**
	 * Promote stream of internally promoted items
	 *
	 * @param	int|array	$limit			Number of items to fetch
	 * @param	string		$sortField		Sort by field
	 * @param	string		$sortDirection	Sort by direction (asc, desc)
	 * @return  array
	 */
	public static function internalStream( int|array $limit=10, string $sortField='promote_added', string $sortDirection='desc') : array
	{
		$_key = md5( $limit . $sortField . $sortDirection );

		if( array_key_exists( $_key, static::$internalStream ) )
		{
			return static::$internalStream[ $_key ];
		}

		$items = array();
		
		foreach( Db::i()->select( '*', static::$databaseTable, array( 'promote_hide=0' ), $sortField . ' ' . $sortDirection, $limit ) as $row )
		{
			$items[ $row['promote_id'] ] = static::constructFromData( $row );
		}
		
		static::$internalStream[ $_key ] = $items;
		return $items;
	}

	/**
	 * Returns "Foo posted {{indefart}} in {{container}}, {{date}}
	 *
	 * @param Lang|NULL	$plaintextLanguage	If specified, will return plaintext (not linking the user or the container in the language specified). If NULL, returns with links based on logged in user's theme and language
	 * @note	This function was extracted from IPS\Promote
	 * @return	string
	 */
	public function objectMetaDescription( ?Lang $plaintextLanguage=NULL ) : string
	{
		$object = $this->object();

		if ( $object instanceof Item AND $container = $object->containerWrapper() )
		{
			$author = $object->author();
			if ( !$plaintextLanguage )
			{
				return Member::loggedIn()->language()->addToStack( 'promote_metadescription_container', FALSE, array(
					'htmlsprintf'	=> array( $author->link(), DateTime::ts( $object->mapped('date')  )->html( FALSE ) ),
					'sprintf'		=> array( $object->indefiniteArticle(), $container->url(), $container->_title ),
				) );
			}
			else
			{
				return $plaintextLanguage->addToStack( 'promote_metadescription_container_nolink', FALSE, array(
					'sprintf'		=> array( $object->indefiniteArticle( $plaintextLanguage ), $container->getTitleForLanguage( $plaintextLanguage ), $author->name, DateTime::ts( $object->mapped('date') ) ),
				) );
			}
		}
		else
		{
			$author = $object->author();

			if ( !$plaintextLanguage )
			{
				return Member::loggedIn()->language()->addToStack( 'promote_metadescription_nocontainer', FALSE, array(
					'htmlsprintf'	=> array( $author->link(), DateTime::ts( $object->mapped('date') )->html( FALSE ) ),
					'sprintf'		=> array( $object->indefiniteArticle() )
				) );
			}
			else
			{
				return $plaintextLanguage->addToStack( 'promote_metadescription_nocontainer', FALSE, array(
					'sprintf'		=> array( $object->indefiniteArticle( $plaintextLanguage ), $author->name, DateTime::ts( $object->mapped('date') ) )
				) );
			}
		}
	}

	/**
	 * Get title wrapper for items and nodes
	 *
	 * @param Content $object Object
	 * @param bool $returnRawText
	 * @return string
	 */
	public static function objectTitle( Content $object, bool $returnRawText = false ) : string
	{
		if ( $object instanceof Item )
		{
			return $object->mapped('title');
		}
		else if ( $object instanceof Comment )
		{
			try
			{
				if ( $returnRawText )
				{
					/* We do not want the hash, we want the actual text */
					return sprintf( Member::loggedIn()->language()->get('promote_thing_in_thing' ), Member::loggedIn()->language()->get( $object::$title ), $object->item()->mapped('title') );
				}

				return Member::loggedIn()->language()->addToStack( 'promote_thing_in_thing', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $object::$title ), $object->item()->mapped('title') ) ) );
			}
			catch( Exception $e )
			{
				return $object->item()->mapped('title');
			}
		}
		
		throw new OutofRangeException('object_not_valid');
	}
	
	/**
	 * Get content wrapper for items and nodes
	 *
	 * @param	Content	$object		Object
	 * @return string
	 */
	public static function objectContent( Content $object ) : string
	{
		$result = NULL;

		if ( $object instanceof Item )
		{
			if ( isset( $object::$databaseColumnMap['content'] ) )
			{
				$result = $object->truncated();
			}
			else if ( $object::$firstCommentRequired )
			{
				$firstComment = $object->comments( 1, NULL, 'date', 'asc' );
				
				$result = $firstComment->truncated();
			}
		}
		else if ( $object instanceof Comment )
		{
			$result = $object->truncated();
		}
		
		/* If result was not set, throw exception now */
		if( $result === NULL )
		{
			throw new OutofRangeException('object_not_valid');
		}

		/* If we treat enter key as newline instead of paragraph, we need to clean up a bit further */
		if( !Settings::i()->editor_paragraph_padding )
		{
			$result = str_replace( "\n", '', $result );
		}

		/* Clean up excess newlines */
		$result = trim( preg_replace( "#(<br>){1,}#", "\n",  preg_replace( '#(<br>)\s+#', "\n", $result ) ) );

		/* Clean up excess spaces at the beginning of text lines */
		$lines = array();

		foreach( explode( "\n", $result ) as $line )
		{
			$lines[] = trim( $line );
		}

		return implode( "\n", $lines );
	}
	
	/**
	 * Load promote row for this class and id
	 */
	protected static array $classAndIdLookup = array();
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$object = parent::constructFromData( $data, $updateMultitonStoreIfExists );
		static::$classAndIdLookup[ $object->class ][ $object->class_id ] = $object->id;

		/* If the class does not exist, throw an exception */
		if( !class_exists( $object->class ) )
		{
			throw new OutOfRangeException;
		}

		return $object;
	}

	/**
	 * Load promote row for this class and id
	 *
	 * @param	string		$class 				Class name
	 * @param	integer		$id					Item ID
	 * @return  static|NULL	Item
	 */
	public static function loadByClassAndId( string $class, int  $id ) : static|NULL
	{
		if ( !isset( static::$classAndIdLookup[ $class ][ $id ] ) )
		{
			try
			{
				$object = static::constructFromData( Db::i()->select( '*', static::$databaseTable, array( 'promote_class=? and promote_class_id=?', $class, $id ) )->first() );
			}
			catch( UnderflowException $e )
			{
				static::$classAndIdLookup[ $class ][ $id ] = 0;
				return NULL;
			}
		}
		else
		{
			if ( static::$classAndIdLookup[ $class ][ $id ] )
			{
				$object = static::load( static::$classAndIdLookup[ $class ][ $id ] );
			}
			else
			{
				return NULL;
			}
		}

		return $object;
	}
	
	/**
	 * Change the hidden flag for all rows that match a class
	 *
	 * @param	$class 	Object	Class (eg IPS\Blog\entry)
	 * @param	$hidden	Boolean	Set hidden
	 * @return	void
	 */
	public static function changeHiddenByClass( Object $class, bool|int $hidden=0 ) : void
	{
		Db::i()->update( static::$databaseTable, array( 'promote_hide' => intval( $hidden ) ), array( 'promote_class=?', get_class( $class ) ) );
		
		if ( $class instanceof Item )
		{
			if ( isset( $class::$commentClass ) )
			{
				Db::i()->update( static::$databaseTable, array( 'promote_hide' => intval( $hidden ) ), array( 'promote_class=?', $class::$commentClass ) );
			}
			
			if ( isset( $class::$reviewClass ) )
			{
				Db::i()->update( static::$databaseTable, array( 'promote_hide' => intval( $hidden ) ), array( 'promote_class=?', $class::$reviewClass ) );
			}
		}
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 *
	 * @apiresponse		\IPS\Member		promotedBy		The member who promoted the content item
	 * @apiresponse		string			itemClass		The FQN classname
	 * @apiresponse		string			itemTitle		Title
	 * @apiresponse		string			itemDescription	The content
	 * @apiresponse		object			item			The promoted content item
	 *
	 */
	public function apiOutput( ?Member $authorizedMember = NULL ): array
	{
		return [
			'promotedBy' => $this->author()->apiOutput($authorizedMember),
			'itemClass' => get_class($this->object()),
			'itemTitle' => $this->objectTitle,
			'itemDescription' => $this->objectMetaDescription,
			'item' => $this->object()->apiOutput($authorizedMember),
		];
	}
}