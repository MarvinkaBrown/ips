<?php
/**
 * @brief		Content Item Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Jul 2013
 */

namespace IPS\Content;

use ArrayIterator;
use BadMethodCallException;
use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\Application\Module;
use IPS\Content;
use IPS\Content\Search\Index;
use IPS\core\Achievements\Recognize;
use IPS\core\Approval;
use IPS\core\DataLayer;
use IPS\core\IndexNow;
use IPS\core\Messenger\Conversation;
use IPS\core\Profanity;
use IPS\core\ShareLinks\Service;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Events\Event;
use IPS\File;
use IPS\forums\Topic;
use IPS\Helpers\Badge;
use IPS\Helpers\Badge\Icon;
use IPS\Helpers\Form\Poll;
use IPS\Helpers\Menu;
use IPS\Helpers\Menu\Separator;
use IPS\IPS;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Rating;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Form\Url as UrlForm;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Log;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Output;
use IPS\Output\UI\UiExtension;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Parser;
use IPS\Theme;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use UnderflowException;
use function array_slice;
use function count;
use function defined;
use function func_get_args;
use function get_called_class;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_null;
use function is_numeric;
use function is_object;
use function is_string;
use function substr;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Item Model
 */
abstract class Item extends Content
{
	/**
	 * @brief	[Content\Item]	Comment Class
	 */
	public static ?string $commentClass = NULL;

	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static bool $firstCommentRequired = FALSE;
	
	/**
	 * @brief	[Content\Item]	First comment
	 */
	public Comment|null $firstComment = NULL;
	
	/**
	 * @brief	[Content\Item]	Follower count
	 */
	public int|null $followerCount = NULL;

	/**
	 * Should IndexNow be skipped for this item? Can be used to prevent that Private Messages,
	 * Reports and other content which is never going to be visible to guests is triggering the requests.
	 * @var bool
	 */
	public static bool $skipIndexNow = FALSE;
	
	/**
	 * @brief	[Content\Item]	If $firstCommentRequired is TRUE, when comments are split from an item or items are merged, the author
	 * 							of the item is set to the author of the new first comment. If this is set to FALSE, this won't be
	 *							done. Useful for circumstances like support requests where the first comment author is not necessarily
	 *							the item author
	 */
	public static bool $changeItemAuthorChangingFirstComment = TRUE;

	/**
	 * @brief	[Content\Item]	Include these items in trending content
	 */
	public static bool $includeInTrending = TRUE;

	/**
	 * @brief   [Content\Item]  Group Posted cache
	 */
	public mixed $groupsPosted = [];

	/**
	 * @brief	[Content\Comment]	EditLine Template
	 */
	public static array $editLineTemplate = array( array( 'global', 'core', 'front' ), 'contentEditLine' );

	/**
	 * Analytics item
	 *
	 * @return array
	 */
	public function analyticsItem(): array
	{
		return Bridge::i()->analyticsItem( $this );
	}

	/**
	 * Get the last modification date for the sitemap
	 *
	 * @return DateTime|null		timestamp of the last modification time for the sitemap
	 */
	public function lastModificationDate(): DateTime|NULL
	{
		$lastMod = NULL;
		if ( isset( static::$databaseColumnMap['last_comment'] ) )
		{
			$lastCommentField = static::$databaseColumnMap['last_comment'];
			if ( is_array( $lastCommentField ) )
			{
				foreach ( $lastCommentField as $column )
				{
					if( $this->$column )
					{
						$lastMod = DateTime::ts( $this->$column );
					}
				}
			}
			else
			{
				if( $this->$lastCommentField )
				{
					$lastMod = DateTime::ts( $this->$lastCommentField );
				}
			}
		}

		return $lastMod;
	}

	/**
	 * Build form to create
	 *
	 * @param	Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @return	Form
	 */
	public static function create( Model|null $container=NULL ): Form
	{
		/* Perform permission checks */
		static::canCreate( Member::loggedIn(), $container, TRUE );
		
		/* Build the form */
		$form = static::buildCreateForm( $container );
				
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Disable read/write separation */
			Db::i()->readWriteSeparation = FALSE;

			try
			{
				$obj = static::createFromForm( $values, $container );
				
				if ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and $obj->hidden() === -3 )
				{
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register', 'front', 'register' ) );
				}
				elseif ( !Member::loggedIn()->member_id and $obj->hidden() )
				{
					Output::i()->redirect( $obj->container()->url(), 'mod_queue_message' );
				}
				else if ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and $obj->hidden() == 1 )
				{
					Output::i()->redirect( $obj->url(), 'mod_queue_message' );
				}
				else
				{
					Output::i()->redirect( $obj->url() );
				}
			}
			catch ( DomainException $e )
			{
				$form->error = $e->getMessage();
			}			
		}
		
		/* Return */
		return $form;
	}

	/**
	 * Build form to create
	 *
	 * @param Model|NULL $container Container (e.g. forum), if appropriate
	 * @param Item|NULL $item Content item, e.g. if editing
	 * @return    Form
	 * @throws Exception
	 */
	protected static function buildCreateForm( Model|null $container=NULL, Item|null $item=NULL ): Form
	{
		$form = new Form( 'form', Member::loggedIn()->language()->checkKeyExists( static::$formLangPrefix . '_save' ) ? static::$formLangPrefix . '_save' : 'save' );
		$form->class = 'ipsForm--new-content';
		$formElements = static::formElements( $item, $container );
		if ( isset( $formElements['poll'] ) )
		{
			$form->addTab( static::$formLangPrefix . 'mainTab' );
		}
		foreach ( $formElements as $key => $object )
		{
			if ( $key === 'poll' )
			{
				$form->addTab( static::$formLangPrefix . 'pollTab' );
			}
			
			if ( is_object( $object ) )
			{
				$form->add( $object );
			}
			else
			{
				$form->addMessage( $object, NULL, FALSE, $key );
			}
		}

        /* Now loop through and add all the elements to the form */
        foreach( UiExtension::i()->run( $item ?: get_called_class(), 'formElements', array( $container ) ) as $element )
        {
	        $form->add( $element );
        }

		return $form;
	}

	/**
	 * Build form to edit
	 *
	 * @return    Form
	 * @throws Exception
	 */
	public function buildEditForm(): Form
	{
		return static::buildCreateForm( $this->containerWrapper(), $this );
	}

	/**
	 * Create generic object
	 *
	 * @param Member $author The author
	 * @param string|null $ipAddress The IP address
	 * @param DateTime $time The time
	 * @param Model|null $container Container (e.g. forum), if appropriate
	 * @param bool|null $hidden Hidden? (NULL to work our automatically)
	 * @return    static
	 */
	public static function createItem( Member $author, ?string $ipAddress, DateTime $time, ?Model $container = NULL, ?bool $hidden=NULL ): static
	{
		/* Create the object */
		$obj = new static;

		foreach ( array( 'date', 'updated', 'author', 'author_name', 'ip_address', 'last_comment', 'last_comment_by', 'last_comment_name', 'last_review', 'container', 'approved', 'hidden', 'locked', 'status', 'views', 'pinned', 'featured', 'is_future_entry', 'num_comments', 'num_reviews', 'unapproved_comments', 'hidden_comments', 'unapproved_reviews', 'hidden_reviews', 'future_date' ) as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) )
			{
				$val = NULL;
				switch ( $k )
				{
					case 'container':
						$val = $container->_id;
						break;
					
					case 'last_comment':
					case 'last_review':
					case 'updated':
					case 'date':
						$val = $time->getTimestamp();
						break;

					case 'author':
					case 'last_comment_by':
						$val = (int) $author->member_id;
						break;
					
					case 'author_name':
					case 'last_comment_name':
						$val = ( $author->member_id ) ? $author->name : $author->real_name;
						break;

					case 'ip_address':
						$val = $ipAddress;
						break;
						
					case 'approved':
						if ( $hidden === NULL )
						{
							if ( !$author->member_id and $container and !$container->can( 'add', $author, FALSE ) )
							{
								$val = -3;
							}
							else
							{
								$val = $obj::moderateNewItems( $author, $container ) ? 0 : 1;
							}
						}
						else
						{
							$val = intval( !$hidden );
						}
						break;
					
					case 'hidden':
						if ( $hidden === NULL )
						{
							if ( !$author->member_id and $container and !$container->can( 'add', $author, FALSE ) )
							{
								$val = -3;
							}
							else
							{
								$val = static::moderateNewItems( $author, $container ) ? 1 : 0;
							}
						}
						else
						{
							$val = intval( $hidden );
						}
						break;
						
					case 'locked':
						$val = FALSE;
						break;
						
					case 'status':
						$val = 'open';
						break;
					
					case 'views':
					case 'pinned':
					case 'featured':
					case 'num_comments':
					case 'num_reviews':
					case 'unapproved_comments':
					case 'hidden_comments':
					case 'unapproved_reviews':
					case 'hidden_reviews':
						$val = 0;
						break;

					case 'is_future_entry':
						$val = ( $time->getTimestamp() > time() ) ? 1 : 0;
						break;
					case 'future_date':
						$val = ( $time->getTimestamp() > time() ) ? $time->getTimestamp() : time();
						break;
				}
				
				foreach ( is_array( static::$databaseColumnMap[ $k ] ) ? static::$databaseColumnMap[ $k ] : array( static::$databaseColumnMap[ $k ] ) as $column )
				{
					$obj->$column = $val;
				}
			}
		}

		/* Update the container */
		if ( $container )
		{
			if( IPS::classUsesTrait( $obj, Hideable::class ) )
			{
				$hiddenStatus = $obj->hidden();
			}
			else
			{
				$hiddenStatus = 0;
			}

			if ( IPS::classUsesTrait( $obj, 'IPS\Content\FuturePublishing' ) AND $obj->isFutureDate() )
			{
				if ( $container->_futureItems !== NULL )
				{
					$container->_futureItems = ( $container->_futureItems + 1 );
				}
			}
			elseif ( !$hiddenStatus )
			{
				if ( $container->_items !== NULL )
				{
					$container->_items = ( $container->_items + 1 );
				}
			}
			elseif ( $hiddenStatus !== -3 and $container->_unapprovedItems !== NULL )
			{
				$container->_unapprovedItems = ( $container->_unapprovedItems + 1 );
			}
			$container->save();
		}
		
		/* Increment post count */
		if ( ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and !$obj->hidden() ) and static::incrementPostCount( $container ) and ( IPS::classUsesTrait( $obj, 'IPS\Content\Anonymous' ) AND ! $obj->isAnonymous() ) )
		{
			$obj->author()->member_posts++;
		}
		
		/* Update member's last post */
		if( $obj->author()->member_id AND $obj::incrementPostCount() AND ( IPS::classUsesTrait( $obj, 'IPS\Content\Anonymous' ) AND ! $obj->isAnonymous() ) )
		{
			$obj->author()->member_last_post = time();
			$obj->author()->save();
		}

		/* Return */
		return $obj;
	}

	/**
	 * Create from form
	 *
	 * @param array $values Values from form
	 * @param Model|null $container Container (e.g. forum), if appropriate
	 * @param bool $sendNotification TRUE to automatically send new content notifications (useful for items that may be uploaded in bulk)
	 * @return    static
	 */
	public static function createFromForm( array $values, ?Model $container = NULL, bool $sendNotification = TRUE ): static
	{
		/* Some applications may include the container selection on the form itself. If $container is NULL, attempt to find it automatically. */
		if( $container === NULL )
		{
			if( isset( $values[ static::$formLangPrefix . 'container'] ) AND isset( static::$containerNodeClass ) AND static::$containerNodeClass AND $values[ static::$formLangPrefix . 'container'] instanceof static::$containerNodeClass )
			{
				$container	= $values[ static::$formLangPrefix . 'container'];
			}
		}

		$member	= Member::loggedIn();

		if( isset( $values['guest_name'] ) AND isset( static::$databaseColumnMap['author_name'] ) )
		{
			$member->name = $values['guest_name'];
		}

		/* Create the item */
		$time = ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\FuturePublishing' ) AND static::canFuturePublish( NULL, $container ) and isset( static::$databaseColumnMap['future_date'] ) and isset( $values[ static::$formLangPrefix . 'date' ] ) and $values[ static::$formLangPrefix . 'date' ] instanceof DateTime ) ? $values[ static::$formLangPrefix . 'date' ] : new DateTime;

		/* Create the item */
		$obj = static::createItem( $member, Request::i()->ipAddress(), $time, $container );
		$obj->processBeforeCreate( $values );
		$obj->processForm( $values );
		$obj->save();

		/* It is possible that $member has changed */
		if( $obj->author()->member_id != $member->member_id )
		{
			$member = $obj->author();
		}

		/* Create the comment */
		$comment = NULL;
		if ( isset( static::$commentClass ) and static::$firstCommentRequired )
		{
			$commentClass = static::$commentClass;
			/* @var $commentClass Comment */
			$comment = $commentClass::create( $obj, $values[ static::$formLangPrefix . 'content' ], TRUE, ( !$member->real_name ) ? NULL : $member->real_name, ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and $obj->hidden() ) ? FALSE : NULL, $member, $time );
			
			$idColumn = static::$databaseColumnId;
			$commentIdColumn = $commentClass::$databaseColumnId;
			
			if ( isset( static::$databaseColumnMap['first_comment_id'] ) )
			{
				$firstCommentIdColumn = static::$databaseColumnMap['first_comment_id'];
				$obj->$firstCommentIdColumn = $comment->$commentIdColumn;
				$obj->save();
			}
		}

		/* Update posts per day limits - don't do this for content items that require a first comment as the comment class will handle that */
		if ( $member->member_id AND $member->group['g_ppd_limit'] AND static::$firstCommentRequired === FALSE )
		{
			$current = $member->members_day_posts;
			
			$current[0] += 1;
			if ( $current[1] == 0 )
			{
				$current[1] = DateTime::create()->getTimestamp();
			}
			
			$member->members_day_posts = $current;
			$member->save();
		}
		
		/* Post anonymously */
		if( isset( $values[ 'post_anonymously' ] ) )
		{
			$obj->setAnonymous( $values[ 'post_anonymously' ], $member );
		}
		
		/* Do any processing */
		$obj->processAfterCreate( $comment, $values );
		
		/* Auto-follow */
		if( isset( $values[ static::$formLangPrefix . 'auto_follow'] ) AND $values[ static::$formLangPrefix . 'auto_follow'] )
		{
			$followArea = mb_strtolower( mb_substr( get_called_class(), mb_strrpos( get_called_class(), '\\' ) + 1 ) );
			
			/* Insert */
			$idColumn = static::$databaseColumnId;
			$save = array(
				'follow_id'				=> md5( static::$application . ';' . $followArea . ';' . $obj->$idColumn . ';' .  Member::loggedIn()->member_id ),
				'follow_app'			=> static::$application,
				'follow_area'			=> $followArea,
				'follow_rel_id'			=> $obj->$idColumn,
				'follow_member_id'		=> Member::loggedIn()->member_id,
				'follow_is_anon'		=> isset( $values[ 'post_anonymously' ] ) ? (bool) $values[ 'post_anonymously' ] : 0,
				'follow_added'			=> time() + 1, // Make sure streams show follows after content is created
				'follow_notify_do'		=> 1,
				'follow_notify_meta'	=> '',
				'follow_notify_freq'	=> Member::loggedIn()->auto_follow['method'],
				'follow_notify_sent'	=> 0,
				'follow_visible'		=> 1
			);
			
			Db::i()->insert( 'core_follow', $save );
		}
		
		/* Auto-share */
		if ( ( IPS::classUsesTrait( $obj, 'IPS\Content\Shareable' ) and $obj->canShare() ) and ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and !$obj->hidden() ) and ( IPS::classUsesTrait( $obj, 'IPS\Content\FuturePublishing' ) AND !$obj->isFutureDate() ) )
		{
			foreach( Service::shareLinks() as $node )
			{
				if ( isset( $values[ "auto_share_{$node->key}" ] ) and $values[ "auto_share_{$node->key}" ] )
				{
					try
					{
						$key = ShareServices::getClassByKey( $node->key );
						$obj->autoShare( $key );
					}
					catch( InvalidArgumentException $e )
					{
						/* Anything we can do here? Can't and shouldn't stop the submission */
					}
				}
			}
		}

		/* Send notifications */
		if ( $sendNotification and ( IPS::classUsesTrait( $obj, 'IPS\Content\FuturePublishing' ) AND !$obj->isFutureDate() ) )
		{
			if ( !$obj->hidden() )
			{
				$obj->sendNotifications();
			}
			else if( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and !in_array( $obj->hidden(), array( -1, -3 ) ) )
			{
				$obj->sendUnapprovedNotification();
			}
		}
		
		/* Dish out points */
		if ( ( IPS::classUsesTrait( $obj, 'IPS\Content\FuturePublishing' ) AND !$obj->isFutureDate() ) and !$obj->hidden() and !$obj instanceof Conversation )
		{
			$member->achievementAction( 'core', 'NewContentItem', $obj );
		}

		/* Sync topics */
		if( IPS::classUsesTrait( $obj, 'IPS\Content\ItemTopic' ) )
		{
			$obj->itemCreatedFromForm();
		}

		/* Return */
		return $obj;
	}
	
	/**
	 * Share this content using a share service
	 *
	 * @param	string	$className	The share service classname
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	protected function autoShare( string $className ): void
	{
		if( method_exists( $className, 'publish' ) )
		{
			$className::publish( $this->mapped('title'), $this->url() );
		}
	}
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( array $values ): void
	{
		/* General columns */
		foreach ( array( 'title', 'poll' ) as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) and array_key_exists( static::$formLangPrefix . $k , $values ) )
			{
				$val = $values[ static::$formLangPrefix . $k ];
				if ( $k === 'poll' )
				{
					$val = $val ? $val->pid : NULL;
				}

				foreach ( is_array( static::$databaseColumnMap[ $k ] ) ? static::$databaseColumnMap[ $k ] : array( static::$databaseColumnMap[ $k ] ) as $column )
				{
					$this->$column = $val;
				}
			}
		}
				
		/* Tags */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) and $this::canTag( NULL, $this->containerWrapper() ) and isset( $values[ static::$formLangPrefix . 'tags' ] ) )
		{
			$idColumn = static::$databaseColumnId;
			if ( !$this->$idColumn )
			{
				$this->save();
			}
			
			$this->setTags( $values[ static::$formLangPrefix . 'tags' ] ?: array() );
		}
		
		/* Future Publishing */
		if( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND static::canFuturePublish( NULL, $this->containerWrapper() ) and isset( static::$databaseColumnMap['future_date'] ) and isset( $values[ static::$formLangPrefix . 'date'] ) )
		{
			$this->setFuturePublishingDates( $values );
		}
		
		/* Post before registering */
		if ( isset( $values['guest_email'] ) and ( !$this->containerWrapper() or !$this->containerWrapper()->can( 'add', Member::loggedIn(), FALSE ) ) )
		{
			$idColumn = static::$databaseColumnId;
			if ( !$this->$idColumn )
			{
				$this->save();
			}
			
			Request::i()->setCookie( 'post_before_register', $this->_logPostBeforeRegistering( $values['guest_email'], isset( Request::i()->cookie['post_before_register'] ) ? Request::i()->cookie['post_before_register'] : NULL ) );
		}
	}
			
	/**
	 * Can a given member create this type of content?
	 *
	 * @param	Member	$member		The member
	 * @param	Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param bool $showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( Member $member, Model|null $container=NULL, bool $showError=FALSE ) : bool
	{
		$return = TRUE;
		$error = $member->member_id ? 'no_module_permission' : 'no_module_permission_guest';
				
		/* Are we restricted from posting completely? */
		if ( $member->restrict_post )
		{
			$return = FALSE;
			$error = 'restricted_cannot_comment';
			
			if ( $member->restrict_post > 0 )
			{
				$error = $member->language()->addToStack( $error ) . ' ' . $member->language()->addToStack( 'restriction_ends', FALSE, array( 'sprintf' => array( DateTime::ts( $member->restrict_post )->relative() ) ) );
			}
		}
		
		/* Or have an unacknowledged warning? */
		if ( $member->members_bitoptions['unacknowledged_warnings'] and Settings::i()->warn_on and Settings::i()->warnings_acknowledge )
		{
			$return = FALSE;
			
			if ( $showError )
			{
				/* If we are running from the command line (ex: profilesync task syncing statuses while using cron) then this can cause an error due to \IPS\Dispatcher not being instantiated.
					If we are not showing an error, then we do not need to call the template. */
				$error = Theme::i()->getTemplate( 'forms', 'core' )->createItemUnavailable( 'unacknowledged_warning_cannot_post', $member->warnings( 1, FALSE ) );
			}
		}
		
		/* Do we have permission? */
		if ( $container !== NULL AND in_array( 'IPS\Node\Permissions', class_implements( $container ) ) )
		{
			if ( !$container->can('add') )
			{
				$return = FALSE;
			}
		}
		else if( $container === NULL and isset( static::$containerNodeClass ) )
		{
			$containerClass	= static::$containerNodeClass;
			if( in_array( 'IPS\Node\Permissions', class_implements( $containerClass ) ) )
			{
				if ( !$containerClass::canOnAny('add') )
				{
					$return = FALSE;
				}
			}
		}
		
		/* Can we access the module */
		if ( !static::_canAccessModule( $member ) )
		{
			$return = FALSE;
		}
		
		/* Return */
		if ( $showError and !$return )
		{
			Output::i()->error( $error, '2C137/3', 403 );
		}
		return $return;
	}

	/**
	 * During canCreate() check, verify member can access the module too
	 *
	 * @param	Member	$member		The member
	 * @note	The only reason this is abstracted at this time is because Pages creates dynamic 'modules' with its dynamic records class which do not exist
	 * @return	bool
	 */
	protected static function _canAccessModule( Member $member ): bool
	{
		/* Can we access the module */
		return $member->canAccessModule( Module::get( static::$application, static::$module, 'front' ) );
	}

	/**
	 * Get elements for add/edit form
	 *
	 * @param Item|null $item The current item if editing or NULL if creating
	 * @param Model|null $container Container (e.g. forum), if appropriate
	 * @return    array
	 * @throws Exception
	 */
	public static function formElements( ?Item $item=NULL, ?Model $container=NULL ): array
	{
		$return = array();

		/* Title */
		if ( isset( static::$databaseColumnMap['title'] ) )
		{
			$return['title'] = new Text( static::$formLangPrefix . 'title',  isset( Request::i()->title ) ? Request::i()->title : $item?->mapped( 'title' ), TRUE, array( 'maxLength' => Settings::i()->max_title_length ?: 255, 'bypassProfanity' => Text::BYPASS_PROFANITY_SWAP ), function( $val ) {
				if ( ! Member::loggedIn()->group['g_bypass_badwords'] AND $word = Profanity::checkProfanityBlocks( $val ) )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( "form_err_word_blocked", FALSE, array( "sprintf" => array( $word ) ) ) );
				}
			} );
			$return['title']->rowClasses[] = 'ipsFieldRow--primary';
			$return['title']->rowClasses[] = 'ipsFieldRow--fullWidth';
		}
		
		/* Container */
		if ( $container === NULL AND isset( static::$containerNodeClass ) AND static::$containerNodeClass )
		{
			$return['container'] = new Node( static::$formLangPrefix . 'container', NULL, TRUE, array(
				'class'				=> static::$containerNodeClass,
				'permissionCheck'	=> 'add',
				'togglePerm'		=> 'add',
				'togglePermPBR'		=> FALSE,
				'toggleIds'			=> array( 'guest_name' ),
				'toggleIdsOff'		=> array( 'guest_email' ),
			), NULL, NULL, NULL, static::$formLangPrefix . 'container' );
		}

		if ( !Member::loggedIn()->member_id )
		{
			$guestsCanPostInContainer = $container?->can( 'add', Member::loggedIn(), false );
			
			if ( !$container or !$guestsCanPostInContainer )
			{
				$return['guest_email'] = new Email( 'guest_email', NULL, TRUE, array( 'accountEmail' => TRUE, 'htmlAutocomplete' => "email" ), NULL, NULL, NULL, 'guest_email' );
			}
			if ( !$container or $guestsCanPostInContainer )
			{
				if ( isset( static::$databaseColumnMap['author_name'] ) )
				{
					$return['guest_name']	= new Text( 'guest_name', NULL, FALSE, array( 'minLength' => Settings::i()->min_user_name_length, 'maxLength' => Settings::i()->max_user_name_length, 'placeholder' => Member::loggedIn()->language()->addToStack('comment_guest_name'), 'htmlAutocomplete' => "username" ), NULL, NULL, NULL, 'guest_name' );
				}
			}
			if ( Settings::i()->bot_antispam_type !== 'none' and Settings::i()->guest_captcha )
			{
				$return['captcha']	= new Captcha;
			}
		}

		/* Tags */
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Taggable' ) and static::canTag( NULL, $container ) )
		{
			if( $tagsField = static::tagsFormField( $item, $container ) )
			{
				$return['tags']	= $tagsField;
			}
		}

		/* Intitial Comment */
		if ( isset( static::$commentClass ) and static::$firstCommentRequired )
		{
			$idColumn = static::$databaseColumnId;
			$commentClass = static::$commentClass;
			if ( $item )
			{
				$commentObj = $item->firstComment();
			}

			/* @var Comment $commentClass */
			$commentIdColumn = $commentClass::$databaseColumnId;
			$return['content'] = new Editor( static::$formLangPrefix . 'content', $item ? $commentObj->mapped('content') : NULL, TRUE, array(
				'app'			=> static::$application,
				'key'			=> IPS::mb_ucfirst( static::$module ),
				'autoSaveKey'	=> ( $item === NULL ? ( 'newContentItem-' . static::$application . '/' . static::$module . '-' . ( $container ? $container->_id : 0 ) ) : ( 'contentEdit-' . static::$application . '/' . static::$module . '-' . $item->$idColumn ) ),
				'attachIds'		=> ( $item === NULL ? NULL : array( $item->$idColumn, $commentObj->$commentIdColumn ) )
			), '\IPS\Helpers\Form::floodCheck', NULL, NULL, static::$formLangPrefix . 'content_editor' );
			
			if ( $item AND IPS::classUsesTrait( $commentClass, 'IPS\Content\EditHistory' ) and Settings::i()->edit_log )
			{
				if ( Settings::i()->edit_log == 2 or isset( $commentClass::$databaseColumnMap['edit_reason'] ) )
				{
					$return['comment_edit_reason'] = new Text( 'comment_edit_reason', ( isset( $commentClass::$databaseColumnMap['edit_reason'] ) ) ? $commentObj->mapped('edit_reason') : NULL, FALSE, array( 'maxLength' => 255 ) );
				}
				if ( Member::loggedIn()->group['g_append_edit'] )
				{
					$return['comment_log_edit'] = new Checkbox( 'comment_log_edit', FALSE );
				}
			}
		}
		else
		{
			/* Edit Reason */
			if ( $item AND IPS::classUsesTrait( $item, 'IPS\Content\EditHistory' ) and Settings::i()->edit_log )
			{
				if ( Settings::i()->edit_log == 2 or isset( $item::$databaseColumnMap['edit_reason'] ) )
				{
					$return['edit_reason'] = new Text( 'edit_reason', ( isset( $item::$databaseColumnMap['edit_reason'] ) ) ? $item->mapped('edit_reason') : NULL, FALSE, array( 'maxLength' => 255 ) );
				}
				if ( Member::loggedIn()->group['g_append_edit'] )
				{
					$return['log_edit'] = new Checkbox( 'log_edit', FALSE );
				}
			}
		}

		/* Auto-follow */
		if ( $item === NULL and IPS::classUsesTrait( get_called_class(), 'IPS\Content\Followable' ) and Member::loggedIn()->member_id )
		{
			$return['auto_follow']	= new YesNo( static::$formLangPrefix . 'auto_follow', (bool) Member::loggedIn()->auto_follow['content'], FALSE, array( 'label' => Member::loggedIn()->language()->addToStack( static::$formLangPrefix . 'auto_follow_suffix' ) ), NULL, NULL, NULL, static::$formLangPrefix . 'auto_follow' );
		}
		
		/* Post Anonymously */
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Anonymous' ) )
		{
			if ( $container and $container->canPostAnonymously( $container::ANON_ITEMS ) and ( $item === NULL or ( $item->author() and $item->author()->group['gbw_can_post_anonymously'] ) or $item->isAnonymous() ) )
			{
				$return['post_anonymously']	= new YesNo( 'post_anonymously', $item && $item->isAnonymous(), FALSE, array( 'label' => Member::loggedIn()->language()->addToStack( 'post_anonymously_suffix' ) ), NULL, NULL, NULL, 'post_anonymously' );
			}
		}
		
		/* Share Links */
		if ( $item === NULL and IPS::classUsesTrait( get_called_class(), 'IPS\Content\Shareable' ) )
		{
			foreach( Service::roots() as $node )
			{
				if ( $node->enabled AND $node->autoshare )
				{
					/* Do guests have permission to see this? */
					if ( $container and !$container->can( 'read', new Member ) )
					{
						continue;
					}

					try
					{
						$class = ShareServices::getClassByKey( $node->key );

						if ( $class::canAutoshare() )
						{
							$return["auto_share_{$node->key}"] = new Checkbox( "auto_share_{$node->key}", 0, FALSE );
						}
					}
					catch ( InvalidArgumentException $e )
					{
					}
				}
			}
		}
		
		/* Polls */
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Polls' ) and static::canCreatePoll( NULL, $container ) )
		{
			/* Can we create a poll on this item? */
			$existingPoll = NULL;
			$canCreatePoll = FALSE;
			
			if ( $item )
			{
				$existingPoll = $item->getPoll();
				
				/* If there's already a poll, we can edit it... */
				if ( $existingPoll )
				{
					$canCreatePoll = TRUE;
				}
				/* Otherwise, it depends on the cutoff for adding a poll */
				else
				{
					if ( ! empty( Settings::i()->startpoll_cutoff ) )
					{
						$canCreatePoll = ( Settings::i()->startpoll_cutoff == -1 or DateTime::create()->sub( new DateInterval( 'PT' . Settings::i()->startpoll_cutoff . 'H' ) )->getTimestamp() < $item->mapped('date') );
					}
				}
			}
			else
			{
				/* If this is a new item, we can create a poll */
				$canCreatePoll = TRUE;
			}
			
			/* Create form element */
			if ( $canCreatePoll )
			{
				$return['poll'] = new Poll( static::$formLangPrefix . 'poll', $existingPoll, FALSE, array( 'allowPollOnly' => TRUE, 'itemClass' => get_called_class() ) );
			}
		}

		/* Show the future date field for new items or while editing an item, but only if the item wasn't published yet */
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\FuturePublishing' ) AND  static::supportsPublishDate( $item ) and static::canFuturePublish( NULL, $container ) )
		{
			$return['date'] = static::getPublishDateField( $item );
		}

		return $return;
	}
	
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( array $values ): void
	{

		/* Check for banned IP - The banned ip addresses are only checked inside the register and login controller, so people are able to bypass them when PBR is used */
		if( !Member::loggedIn()->member_id AND Request::i()->ipAddressIsBanned() )
		{
			Output::i()->showBanned();
		}

        Event::fire( 'onBeforeCreateOrEdit', $this, array( $values, TRUE ) );
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param Comment|NULL	$comment	The first comment
	 * @param	array		$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( Comment|null $comment, array $values ): void
	{
		if ( Bridge::i()->checkItemForSpam( $this ) )
		{
			/* This is spam, so do not continue */
			return;
		}

		/* Add to search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}

		/* Are we tracking keywords? */
		$this->checkKeywords( (string) ( $comment ? $comment->mapped('content') : $this->mapped('content') ), $this->mapped('title') );
		
		/* Send webhook */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) and in_array( $this->hidden(), array( -1, 0, 1 ) ) ) // i.e. not post before register or pending deletion
		{
			Webhook::fire( str_replace( '\\', '', substr( get_called_class(), 3 ) ) . '_create', $this, $this->webhookFilters() );
		}

		/* Data Layer Event */
		if ( Settings::i()->core_datalayer_enabled )
		{
			/* make sure it isn't a PM Conversation */
			if ( !isset( static::$title ) or static::$title !== Conversation::$title )
			{
				DataLayer::i()->addEvent( 'content_create', $this->getDataLayerProperties() );
			}
		}

		/* Send this URL to IndexNow if the guest can view it */
		if ( $this->canView( new Member ) AND !static::$skipIndexNow )
		{
			IndexNow::addUrlToQueue( $this->url() );
		}
		
		/* Was it moderated? Let's see why. */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) and $this->hidden() === 1 )
		{
			$idColumn = static::$databaseColumnId;
			
			/* Check we don't already have a reason from profanity / url / email filters */
			try
			{
				Approval::loadFromContent( get_called_class(), $this->$idColumn );
			}
			catch( OutOfRangeException $e )
			{
				/* If the user is mod-queued - that's why. These will cascade, so check in that order. */
				$foundReason = FALSE;
				$log = new Approval;
				$log->content_class	= get_called_class();
				$log->content_id	= $this->$idColumn;
				if ( $this->author()->mod_posts )
				{
					
					$log->held_reason	= 'user';
					$foundReason = TRUE;
				}
				
				/* If the user isn't mod queued, but is in a group that is, that's why. */
				if ( $foundReason === FALSE AND $this->author()->group['g_mod_preview'] )
				{
					$log->held_reason	= 'group';
					$foundReason = TRUE;
				}
				
				/* If the user isn't on mod queue, but the container requires approval, that's why. */
				if ( $foundReason === FALSE )
				{
					try
					{
						if ( $this->container() AND $this->container()->contentHeldForApprovalByNode( 'item', $this->author() ) === TRUE )
						{
							$log->held_reason = 'node';
							$foundReason = TRUE;
						}
					}
					catch( BadMethodCallException $e ) { }
				}
				
				if ( $foundReason )
				{
					$log->save();
				}	
			}
		}

        Event::fire( 'onCreateOrEdit', $this, array( $values, TRUE ) );

		$this->ui( 'formPostSave', array( $values ) );
	}

    /**
     * Process before the object has been edited on the front-end
     *
     * @param array $values
     * @return void
     */
    public function processBeforeEdit( array $values ) : void
    {
        Event::fire( 'onBeforeCreateOrEdit', $this, array( $values ) );
    }

	/**
	 * Process after the object has been edited on the front-end
	 *
	 * @param array $values		Values from form
	 * @return	void
	 */
	public function processAfterEdit( array $values ): void
	{
		/* Add to search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}

		/* Initial Comment */
		if ( isset( static::$commentClass ) and static::$firstCommentRequired )
		{
			/* @var Comment $commentClass */
			$commentClass = static::$commentClass;
			$commentObj = $this->firstComment();
			/* @var $databaseColumnMap array */
			$column = $commentClass::$databaseColumnMap['content'];
			$idField = $commentClass::$databaseColumnId;

			/* Update the comment date, in case the topic scheduled publish date has changed */
			if( isset( $values[ static::$formLangPrefix . 'date' ] ) and $values[ static::$formLangPrefix . 'date' ] instanceof DateTime and isset( $commentClass::$databaseColumnMap['date'] ) )
			{
				$dateColumn = $commentClass::$databaseColumnMap['date'];
				$commentObj->$dateColumn = $values[ static::$formLangPrefix . 'date' ]->getTimestamp();
			}

			if( IPS::classUsesTrait( $commentObj, EditHistory::class ) and Settings::i()->edit_log )
			{
				/* @var $commentObj Content */
				$commentObj->logEdit( $values );

				$sendNotifications = true;
				/* Check if profanity filters should mod-queue this comment */
				if ( IPS::classUsesTrait( $commentObj, 'IPS\Content\Hideable' ) )
				{
					/* Check if profanity filters should mod-queue this comment */
					$sendNotifications = $commentObj->checkProfanityFilters( TRUE, TRUE, $values[ static::$formLangPrefix . 'content'] );
				}

				/* Send notifications */
				if ( $sendNotifications AND !in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
				{
					if ( $commentObj->hidden() === 1 )
					{
						$commentObj->sendUnapprovedNotification();
					}
				}
			}

			$oldValue = $commentObj->$column;
			$commentObj->$column = $values[ static::$formLangPrefix . 'content'];
			$commentObj->save();
			$commentObj->sendAfterEditNotifications( $oldValue );

			if( Content\Search\SearchContent::isSearchable( $commentObj ) )
			{
				Index::i()->index( $commentObj );
			}
		}
		else if ( !static::$firstCommentRequired  AND IPS::classUsesTrait( $this, EditHistory::class ) and Settings::i()->edit_log )
		{
			$this->logEdit( $values );
		}
		
		$container = $this->containerWrapper();

		if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND isset( $values[ static::$formLangPrefix . 'date' ] ) )
		{
			$column = static::$databaseColumnMap['is_future_entry'];
			
			if ( $container AND isset( $this->changed[ $column ] ) AND !$this->changed[ $column ] ) // If the changed value is now false, it has just been published
			{
				if ( ( ! ( $values[ static::$formLangPrefix . 'date' ] instanceof DateTime ) AND $values[ static::$formLangPrefix . 'date' ] == 0 ) OR ( $values[ static::$formLangPrefix . 'date' ] instanceof DateTime AND $values[ static::$formLangPrefix . 'date' ]->getTimestamp() <= time() ) )
				{
					/* Was future, now not */
					$this->publish();
				}
			}
			else if ( $container AND isset( $this->changed[ $column ] ) AND $this->changed[ $column ] === TRUE ) // If the changed value is true, it has just been unpublished
			{
				if ( $values[ static::$formLangPrefix . 'date' ] instanceof DateTime AND $values[ static::$formLangPrefix . 'date' ]->getTimestamp() > time() )
				{
					/* Was not future, now is */
					$this->unpublish();
				}
			}
		}

		/* Post anonymously */
		if( isset( $values[ 'post_anonymously' ] ) and ( $container and $container->canPostAnonymously( $container::ANON_ITEMS ) ) )
		{
			$this->setAnonymous( $values[ 'post_anonymously' ], $this->author() );
		}

		/* Send this URL to IndexNow if the guest can view it */
		if( $this->canView( new Member ) )
		{
			IndexNow::addUrlToQueue( $this->url() );
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\ItemTopic' ) )
		{
			$this->itemEdited();
		}

        Event::fire( 'onCreateOrEdit', $this, array( $values ) );

		$this->ui( 'formPostSave', array( $values ) );
	}

	/* Holds the old content for edit logging */
	protected ?string $oldContent = NULL;

	/**
	 * Set value in data store
	 *
	 * @see        ActiveRecord::save
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( mixed $key, mixed $value ): void
	{
		if( !$this->_new AND Settings::i()->edit_log == 2 AND isset( $this::$databaseColumnMap['content'] ) )
		{
			$column = $this::$databaseColumnMap['content'];
			if ( $key === $column )
			{
				$this->oldContent = $this->$column;
			}
		}

		parent::__set($key, $value);
	}
	
	/**
	 * @brief	Container
	 */
	protected Model|null $container = NULL;

	/**
	 * Wrapper to get container. May return NULL if there is no container (e.g. private messages)
	 *
	 * @param bool $allowOutOfRangeException	If TRUE, will return NULL if the container doesn't exist rather than throw OutOfRangeException
	 * @return	Model|NULL
	 * @note	This simply wraps container()
	 * @see		container()
	 */
	public function containerWrapper( bool $allowOutOfRangeException = FALSE ): Model|NULL
	{
		/* Get container, if valid */
		$container = NULL;

		try
		{
			$container = $this->container();
		}
		catch( OutOfRangeException $e )
		{
			if ( !$allowOutOfRangeException )
			{
				throw $e;
			}
		}
		catch( BadMethodCallException $e ){}

		return $container;
	}

	/**
	 * Get container
	 *
	 * @return	Model
	 * @note	Certain functionality requires a valid container but some areas do not use this functionality (e.g. messenger)
	 * @throws	OutOfRangeException|BadMethodCallException
	 */
	public function container(): Model
	{
		if ( $this->container === NULL )
		{
			if ( !isset( static::$containerNodeClass ) or !isset( static::$databaseColumnMap['container'] ) )
			{
				throw new BadMethodCallException;
			}

			$containerClass		= static::$containerNodeClass;
			$this->container	= $containerClass::load( $this->mapped('container') );
		}
		
		return $this->container;
	}

	/**
	 * Return the node directly above this Item.
	 * Used for Items that can belong to more than one node type (e.g. Images).
	 * We need this default method here so that we can reference things
	 * like container permissions and titles for items that are not searchable.
	 * What a mess.
	 *
	 * @return    Model
	 * @throws BadMethodCallException
	 */
	public function directContainer() : Model
	{
		return $this->container();
	}

	/**
	 * Get URL
	 *
	 * @param string|null $action Action
	 * @return    Url
	 */
	public function url( ?string $action=NULL ): Url
	{
		if( $action === 'getPrefComment' AND Member::loggedIn()->member_id  )
		{
			$pref = Member::loggedIn()->linkPref() ?: Settings::i()->link_default;

			switch( $pref )
			{
				case 'unread':
					$action = Member::loggedIn()->member_id ? 'getNewComment' : NULL;
					break;

				case 'last':
					$action = 'getLastComment';
					break;

				default:
					$action = NULL;
					break;
			}
		}
		elseif( ( $action == 'getPrefComment' OR $action == 'getNewComment' OR $action == 'getLastComment' ) AND !Member::loggedIn()->member_id  )
		{
			$action = NULL;
		}

		if ( isset( static::$urlBase ) and isset( static::$urlTemplate ) and isset( static::$seoTitleColumn ) )
		{
			$_key	= $action ? md5( $action ) : NULL;
	
			if( !isset( $this->_url[ $_key ] ) )
			{
				$idColumn = static::$databaseColumnId;
				$seoTitleColumn = static::$seoTitleColumn;
				
				try
				{
					$this->_url[ $_key ] = Url::internal( static::$urlBase . $this->$idColumn, 'front', static::$urlTemplate, $this->$seoTitleColumn );
				}
				catch ( Url\Exception $e )
				{					
					if ( isset( static::$databaseColumnMap['title'] ) )
					{
						$titleColumn = static::$databaseColumnMap['title'];
						$correctSeoTitle = Friendly::seoTitle( $this->$titleColumn );
						if ( $this->$seoTitleColumn != $correctSeoTitle )
						{
							$this->$seoTitleColumn = $correctSeoTitle;
							$this->save();
							return $this->url( $action );
						}
					}
					
					throw $e;
				}
			
				if ( $action )
				{
					$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
				}
			}
		
			return $this->_url[ $_key ];
		}
		throw new BadMethodCallException;
	}

	/**
	 * Get a shareable URL
	 *
	 * @param int|null $commentId
	 * @return    Url
	 */
	public function shareableUrl( int $commentId = NULL ): Url
	{
		$idColumn = static::$databaseColumnId;
		$url = $this->url();

		if ( $commentId )
		{
			$url = $url->setFragment( 'findComment-' . $commentId );
		}

		return $url;
	}

	/**
	 * Get mapped value
	 *
	 * @param string $key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( string $key ): mixed
	{
		$return = parent::mapped( $key );
		
		/* unapproved_comments etc may be set to NULL if the value has not yet been calculated */
		if ( $return === NULL and isset( static::$databaseColumnMap[ $key ] ) and in_array( $key, array( 'unapproved_comments', 'hidden_comments', 'unapproved_reviews', 'hidden_reviews' ) ) )
		{			
			/* Work out if we're using the comment class or the review class */
			if ( $key === 'unapproved_comments' or $key === 'hidden_comments' )
			{
				$commentClass = static::$commentClass;
			}
			else
			{
				$commentClass = static::$reviewClass;
			}
			
			/* Set the intial where for the ID column */
			$idColumn = static::$databaseColumnId;
			/* @var $databaseColumnMap array */
			$where = array( array( "{$commentClass::$databasePrefix}{$commentClass::$databaseColumnMap['item']}=?", $this->$idColumn ) );
			
			/* Work out the appropriate value to look for depending on if the class uses "approved" or "hidden" */
			if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
			{
				$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=?', ( $key === 'unapproved_comments' or $key === 'unapproved_reviews' ) ? 0 : -1 );
			}
			else
			{
				$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=?', ( $key === 'unapproved_comments' or $key === 'unapproved_reviews' ) ? 1 : -1 );
			}
			
			/* Query */
			$return = Db::i()->select( 'COUNT(*)', $commentClass::$databaseTable, $where )->first();
			
			/* Save that value */
			$mappedKey = static::$databaseColumnMap[ $key ];
			$this->$mappedKey = $return;
			$this->save();			
		}
		
		return $return;
	}

	/**
	 * Returns the content
	 *
	 * @return	string|null
	 * @throws	BadMethodCallException
	 */
	public function content(): ?string
	{
		if ( isset( static::$databaseColumnMap['content'] ) )
		{
			return parent::content();
		}
		elseif ( static::$commentClass )
		{
			if ( $comment = $this->firstComment() )
			{
				return $comment->content();
			}
			else
			{
				throw new BadMethodCallException;
			}
		}
		else
		{
			throw new BadMethodCallException;
		}
	}
	
	/**
	 * Returns the content images
	 *
	 * @param	int|null	$limit				Number of attachments to fetch, or NULL for all
	 * @param	bool		$ignorePermissions	If set to TRUE, permission to view the images will not be checked
	 * @return	array|NULL
	 * @throws	BadMethodCallException
	 */
	public function contentImages( int $limit = NULL, bool $ignorePermissions = FALSE ): array|null
	{
		$idColumn = static::$databaseColumnId;
		$internal = array();
		$attachments = array();
		$loadedExtensions = array();
		
		/* Get attachments from the content, or all comments */
		if ( isset( static::$databaseColumnMap['content'] ) or isset( static::$commentClass ) )
		{
			$internal = iterator_to_array( Db::i()->select( '*', 'core_attachments_map', array( 'location_key=? and id1=?', static::$application . '_' . IPS::mb_ucfirst( static::$module ), $this->$idColumn ) )->setKeyField('attachment_id') );
		}
						
		if ( $internal )
		{
			foreach( Db::i()->select( '*', 'core_attachments', array( array( Db::i()->in( 'attach_id', array_keys( $internal ) ) ), array( 'attach_is_image=1' ) ), 'attach_id ASC', $limit ) as $row )
			{
				if( $ignorePermissions )
				{
					$attachments[] = array( 'core_Attachment' => $row['attach_location'] );
				}
				else
				{
					$map = $internal[ $row['attach_id'] ];
					
					if ( !isset( $loadedExtensions[ $map['location_key'] ] ) )
					{
						$exploded = explode( '_', $map['location_key'] );
						try
						{
							$extensions = Application::load( $exploded[0] )->extensions( 'core', 'EditorLocations' );
							if ( isset( $extensions[ $exploded[1] ] ) )
							{
								$loadedExtensions[ $map['location_key'] ] = $extensions[ $exploded[1] ];
							}
						}
						catch ( OutOfRangeException $e ) { }
					}
									
					if ( isset( $loadedExtensions[ $map['location_key'] ] ) )
					{		
						try
						{
							if ( $loadedExtensions[ $map['location_key'] ]->attachmentPermissionCheck( Member::loggedIn(), $map['id1'], $map['id2'], $map['id3'], $row, TRUE ) )
							{
								$attachments[] = array( 'core_Attachment' => $row['attach_location'] );
							}
						}
						catch ( Exception $e ) { }
					}
				}
			}
		}

		/* IS there a club with a cover photo? */
		if( $container = $this->containerWrapper() )
		{
			if ( IPS::classUsesTrait( $container, 'IPS\Content\ClubContainer' ) and $club = $container->club() AND $club->cover_photo )
			{
				$attachments[] = array( 'core_Clubs' => $club->cover_photo );
			}
		}
		
		return count( $attachments ) ? array_slice( $attachments, 0, $limit ) : NULL;
	}
	
	/**
	 * Returns the meta description
	 *
	 * @param string|null $return	Specific description to use (useful for paginated displays to prevent having to run extra queries)
	 * @return	string
	 * @throws	BadMethodCallException
	 */
	public function metaDescription( string $return = NULL ): string
	{
		if( $return === NULL AND isset( $_SESSION['_findComment'] ) )
		{
			$commentId	= $_SESSION['_findComment'];
			unset( $_SESSION['_findComment'] );

			$commentClass	= static::$commentClass;
			/* @var $commentClass Content */
			if( $commentClass !== NULL )	
			{
				try
				{
					$comment = $commentClass::loadAndCheckPerms( $commentId );

					$return = $comment->content();
				}
				catch( Exception $e ){}
			}
		}
		
		if ( $return === NULL )
		{
			if ( isset( static::$databaseColumnMap['content'] ) )
			{
				$return = parent::content();
			}
			elseif( static::$firstCommentRequired AND $comment = $this->firstComment() )
			{
				$return = $comment->content();
			}
			else
			{
				$return = $this->mapped('title');
			}
		}
		
		if ( $return )
		{
			$return =  trim( preg_replace( "/\s+/um", " ", str_replace( '&nbsp;', ' ', strip_tags( preg_replace('#(<(script|style)\b[^>]*>).*?(</\2>)#is', "$1$3", $return ) ) ) ) );
			if ( mb_strlen( $return ) > 300 )
			{
				$return = mb_substr( $return, 0, 297 ) . '...';
			}
		}
		
		return $return ?? '';
	}
	
	/**
	 * @brief	Hot stats
	 */
	public array $hotStats = array();
	
	/**
	 * Stats for table view
	 *
	 * @param bool $includeFirstCommentInCommentCount	Determines whether the first comment should be inlcluded in the comment \count(e.g. For "posts", use TRUE. For "replies", use FALSE)
	 * @return	array
	 */
	public function stats( bool $includeFirstCommentInCommentCount=TRUE ): array
	{
		$return = array();

		if ( static::$commentClass )
		{
			$return['comments'] = (int) $this->mapped('num_comments');
			if ( !$includeFirstCommentInCommentCount )
			{
				$return['comments']--;
			}

			if ( $return['comments'] < 0 )
			{
				$return['comments'] = 0;
			}
		}

		if( IPS::classUsesTrait( $this, ViewUpdates::class ) )
		{
			$return['num_views'] = (int) $this->mapped('views');
		}
		
		return $return;
	}
	
	/**
	 * Move
	 *
	 * @param	Model	$container	Container to move to
	 * @param bool $keepLink	If TRUE, will keep a link in the source
	 * @return	void
	 */
	public function move( Model $container, bool $keepLink=FALSE ): void
	{
		/* Reduce the counts in the old node */
		$oldContainer = $this->container();

		if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() and $oldContainer->_futureItems !== NULL )
		{
			$oldContainer->_futureItems = intval( $oldContainer->_futureItems - 1 );
		}
		else if ( !$this->hidden() )
		{
			if ( $oldContainer->_items !== NULL )
			{
				$oldContainer->_items = intval( $oldContainer->_items - 1 );
			}
			if ( isset( static::$commentClass ) and $oldContainer->_comments !== NULL )
			{
				$oldContainer->_comments = intval( $oldContainer->_comments - $this->mapped('num_comments') );
			}
			if ( isset( static::$reviewClass ) and $oldContainer->_reviews !== NULL )
			{
				$oldContainer->_reviews = intval( $oldContainer->_reviews - $this->mapped('num_reviews') );
			}
		}
		elseif ( $this->hidden() === 1 and $oldContainer->_unapprovedItems !== NULL )
		{
			$oldContainer->_unapprovedItems = intval( $oldContainer->_unapprovedItems - 1 );
		}

		if ( isset( static::$commentClass ) and $oldContainer->_unapprovedComments !== NULL and isset( static::$databaseColumnMap['unapproved_comments'] ) )
		{
			$oldContainer->_unapprovedComments = ( $oldContainer->_unapprovedComments > 0 ) ? intval( $oldContainer->_unapprovedComments - $this->mapped('unapproved_comments') ) : 0;
		}
		if ( isset( static::$reviewClass ) and $oldContainer->_unapprovedReviews !== NULL and isset( static::$databaseColumnMap['unapproved_reviews'] ) )
		{
			$oldContainer->_unapprovedReviews = ( $oldContainer->_unapprovedReviews > 0 ) ? intval( $oldContainer->_unapprovedReviews - $this->mapped('unapproved_reviews') ) : 0;
		}

		/* Make a link */
		if ( $keepLink )
		{
			$link = clone $this;
			$movedToColumn = static::$databaseColumnMap['moved_to'];
			$idColumn = static::$databaseColumnId;
			$link->$movedToColumn = $this->$idColumn . '&' . $container->_id;
			
			/* Do not keep comment counts on the link item */
			if ( isset( static::$databaseColumnMap['num_comments'] ) )
			{
				$commentsColumn = static::$databaseColumnMap['num_comments'];
				$link->$commentsColumn = 0;
			}
			
			if ( isset( static::$databaseColumnMap['num_reviews'] ) )
			{
				$reviewsColumn = static::$databaseColumnMap['num_reviews'];
				$link->$reviewsColumn = 0;
			}
			
			if ( isset( static::$databaseColumnMap['unapproved_comments'] ) )
			{
				$unapprovedComments = static::$databaseColumnMap['unapproved_comments'];
				$link->$unapprovedComments = 0;
			}
			
			if ( isset( static::$databaseColumnMap['unapproved_reviews'] ) )
			{
				$unapprovedReviews = static::$databaseColumnMap['unapproved_reviews'];
				$link->$unapprovedReviews = 0;
			}
			
			if ( isset( static::$databaseColumnMap['state'] ) )
			{
				$stateColumn = static::$databaseColumnMap['state'];
				$link->$stateColumn = 'link';
			}
			if ( isset( static::$databaseColumnMap['moved_on'] ) )
			{
				$movedOnColumn = static::$databaseColumnMap['moved_on'];
				$link->$movedOnColumn = time();
			}
			
			$link->save();
		}
		
		/* Change container */
		$column = static::$databaseColumnMap['container'];
		$this->$column = $container->_id;
		$this->save();
		$this->container = $container;
	
		/* Rebuild tags */
		$containerClass = static::$containerNodeClass;
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) )
		{
			/* If the user can post tags in the destination forum, then we will want to retain the tags */
			if( static::canTag( $this->author(), $container ) )
			{
				Db::i()->update( 'core_tags', array(
					'tag_aap_lookup'		=> $this->tagAAPKey(),
					'tag_meta_parent_id'	=> $container->_id
				), array( 'tag_aai_lookup=?', $this->tagAAIKey() ) );

				if ( isset( $containerClass::$permissionMap['read'] ) )
				{
					Db::i()->update( 'core_tags_perms', array(
						'tag_perm_aap_lookup'	=> $this->tagAAPKey(),
						'tag_perm_text'			=> Db::i()->select( 'perm_' . $containerClass::$permissionMap['read'], 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', $containerClass::$permApp, $containerClass::$permType, $container->_id ) )->first()
					), array( 'tag_perm_aai_lookup=?', $this->tagAAIKey() ) );
				}
			}
			else
			{
				$tagsToKeep = array();

				/* We need to ensure we retain tags that were set by users (i.e. moderators) who can post tags in the destination forum */
				foreach( Db::i()->select( '*', 'core_tags', array( 'tag_aai_lookup=?', $this->tagAAIKey() ) ) as $tag )
				{
					if( static::canTag( Member::load( $tag['tag_member_id'] ), $container ) )
					{
						if( $tag['tag_prefix'] )
						{
							$tagsToKeep['prefix']	= $tag['tag_text'];
						}
						else
						{
							$tagsToKeep[]	= $tag['tag_text'];
						}
					}
				}

				$this->setTags( $tagsToKeep );
			}
		}
		
		/* Update the counts in the new node */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() and $container->_futureItems !== NULL )
		{
			$container->_futureItems = ( $container->_futureItems + 1 );
		}
		elseif ( !$this->hidden() )
		{
			if ( $container->_items !== NULL )
			{
				$container->_items = ( $container->_items + 1 );
			}
			if ( isset( static::$commentClass ) and $container->_comments !== NULL )
			{
				$container->_comments = ( $container->_comments + $this->mapped('num_comments') );
			}
			if ( isset( static::$reviewClass ) and $this->container()->_reviews !== NULL )
			{
				$container->_reviews = ( $container->_reviews + $this->mapped('num_reviews') );
			}
		}
		elseif ( $this->hidden() === 1 and $container->_unapprovedItems !== NULL )
		{
			$container->_unapprovedItems = ( $container->_unapprovedItems + 1 );
		}
		if ( isset( static::$commentClass ) and $container->_unapprovedComments !== NULL and isset( static::$databaseColumnMap['unapproved_comments'] ) )
		{
			$container->_unapprovedComments = ( $container->_unapprovedComments >= 0 ) ? ( $container->_unapprovedItems + $this->mapped('unapproved_comments') ) : 0;
		}
		if ( isset( static::$reviewClass ) and $this->container()->_unapprovedReviews !== NULL and isset( static::$databaseColumnMap['unapproved_reviews'] ) )
		{
			$container->_unapprovedReviews = ( $container->_unapprovedReviews + $this->mapped('unapproved_reviews') );
		}
				
		/* Rebuild node data */
		if( !$this->skipContainerRebuild )
		{
			$oldContainer->setLastComment();
			$oldContainer->setLastReview();
			$oldContainer->save();
			$container->setLastComment();
			$container->setLastReview();
			$container->save();
		}

		/* Add to search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			if ( isset( static::$commentClass ) OR isset( static::$reviewClass ) )
			{
				Index::i()->index( ( static::$firstCommentRequired and $this->firstComment() ) ? $this->firstComment() : $this );
				Index::i()->indexSingleItem( $this );
			}
			else
			{
				/* Either this is a comment / review, or the item doesn't support comments or reviews, so we can just reindex it now. */
				Index::i()->index( $this );
			}
		}

		/* Update reports */
		Db::i()->update( 'core_rc_index', array( 'node_id' => $container->_id ), array( 'class=? and content_id=?', get_class( $this ), $oldContainer->_id ) );

		/* Topic Synch */
		if( IPS::classUsesTrait( $this, 'IPS\Content\ItemTopic' ) )
		{
			$this->itemMoved( $keepLink );
		}

		/* Update caches */
		$this->expireWidgetCaches();

		try
		{
			$this->adjustSessions();
		}
		catch( LogicException $e ) {}

		/* If we have a link, mark it read */
		if ( $keepLink )
		{
			$link->markRead();
		}

        Event::fire( 'onItemMove', $this, array( $oldContainer, $keepLink ) );
	}
	
	/**
	 * Moved to
	 *
	 * @return	static|NULL
	 */
	public function movedTo(): static|NULL
	{
		if ( isset( static::$databaseColumnMap['moved_to'] ) )
		{
			$exploded = explode( '&', $this->mapped('moved_to') );
			try
			{
				return static::load( $exploded[0] );
			}
			catch ( Exception $e ) { }
		}
		
		return NULL;
	}
	
	/**
	 * Get Next Item
	 *
	 * @return	static|NULL
	 */
	public function nextItem(): static|NULL
	{
		try
		{
			$column = $this->getDateColumn();
			$idColumn = static::$databaseColumnId;

			$item	= NULL;

			foreach( static::getItemsWithPermission( array(
				array( static::$databaseTable . '.' . static::$databasePrefix . $column . '>?', $this->$column ),
				array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'] . '=?', $this->container()->_id ),
				array( static::$databaseTable . '.' . static::$databasePrefix . $idColumn . '!=?', $this->$idColumn )
			), static::$databasePrefix . $column . ' ASC', 1 ) AS $item )
			{
				break;
			}

			return $item;
		}
		catch( Exception $e ) { }
		
		return NULL;
	}
	
	/**
	 * Get Previous Item
	 *
	 * @return	static|NULL
	 */
	public function prevItem(): static|NULL
	{
		try
		{
			$column = $this->getDateColumn();
			$idColumn = static::$databaseColumnId;

			$item	= NULL;
			foreach( static::getItemsWithPermission( array(
				array( static::$databaseTable . '.' . static::$databasePrefix . $column . '<?', $this->$column ),
				array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'] . '=?', $this->container()->_id ),
				array( static::$databaseTable . '.' . static::$databasePrefix . $idColumn . '!=?', $this->$idColumn )
			), static::$databasePrefix . $column . ' DESC', 1 ) AS $item )
			{
				break;
			}
			
			return $item;
		}
		catch( Exception $e ) { }
		
		return NULL;
	}

	/**
	 * Get date column for next/prev item
	 * Does not use last comment / last review as these will often be 0 and is not how items are generally ordered
	 *
	 * @return	string
	 */
	protected function getDateColumn(): string
	{
		if( isset( static::$databaseColumnMap['updated'] ) )
		{
			$column	= is_array( static::$databaseColumnMap['updated'] ) ? static::$databaseColumnMap['updated'][0] : static::$databaseColumnMap['updated'];
		}
		else if( isset( static::$databaseColumnMap['date'] ) )
		{
			$column	= is_array( static::$databaseColumnMap['date'] ) ? static::$databaseColumnMap['date'][0] : static::$databaseColumnMap['date'];
		}

		return $column ?? '';
	}
	
	/**
	 * Merge other items in (they will be deleted, this will be kept)
	 *
	 * @param	array	$items		Items to merge in
	 * @param bool $keepLinks	Retain redirect links for the items that were merge in
	 * @return	void
	 */
	public function mergeIn( array $items, bool $keepLinks=FALSE ): void
	{
		$idColumn = static::$databaseColumnId;
		$views    = 0;		
		foreach ( $items as $item )
		{
			if ( isset( static::$commentClass ) )
			{
				/* @var Comment $commentClass */
				/* @var array $databaseColumnMap */
				$commentClass = static::$commentClass;
				
				if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Hideable' ) and isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					if ( $item->hidden() and !$this->hidden() )
					{
						Db::i()->update( $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] => 0 ), array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=2', $item->$idColumn ) );
					}
					elseif ( $this->hidden() and !$item->hidden() )
					{
						Db::i()->update( $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] => 2 ), array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=0', $item->$idColumn ) );
					}
				}
				
				$commentUpdate = array();
				$commentUpdate[ $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] ] = $this->$idColumn;
				if ( isset( $commentClass::$databaseColumnMap['first'] ) )
				{
					/* This item is being merged into another, so any comments defined as "first" need to be reset */
					$commentUpdate[ $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['first'] ] = FALSE;
				}
				Db::i()->update( $commentClass::$databaseTable, $commentUpdate, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $item->$idColumn ) );

				if( $extension = Content\Search\SearchContent::extension( $this ) )
				{
					Index::i()->massUpdate( $commentClass, NULL, $item->$idColumn, $extension->searchIndexPermissions(), $this->hidden() ? 2 : NULL, $extension->searchIndexContainer(), NULL, $this->$idColumn, $this->author()->member_id );
				}
			}
			if ( isset( static::$reviewClass ) )
			{
				/* @var array $databaseColumnMap */
				$reviewClass = static::$reviewClass;
				$reviewUpdate = array();
				$reviewUpdate[ $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] ] = $this->$idColumn;

				Db::i()->update( $reviewClass::$databaseTable, $reviewUpdate, array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $item->$idColumn ) );

				if( $extension = Content\Search\SearchContent::extension( $this ) )
				{
					Index::i()->massUpdate( $reviewClass, NULL, $item->$idColumn, $extension->searchIndexPermissions(), $this->hidden() ? 2 : NULL, $extension->searchIndexContainer(), NULL, $this->$idColumn, $this->author()->member_id );
				}
			}
						
			/* Merge view counts */
			if( IPS::classUsesTrait( $this, ViewUpdates::class ) )
			{
				$views += $item->mapped('views');
				Db::i()->update( 'core_view_updates', array( 'id' => $this->$idColumn ), array( 'classname=? and id=?', get_class( $item ), $item->$idColumn ) );
			}
			
			/* Attachments */
			$locationKey = $item::$application . '_' . IPS::mb_ucfirst( $item::$module );
			Db::i()->update( 'core_attachments_map', array( 'id1' => $this->$idColumn ), array( 'location_key=? and id1=?', $locationKey, $item->$idColumn ) );

			/* Update notifications */
			Db::i()->update( 'core_notifications', array( 'item_id' => $this->$idColumn ), array( 'item_class=? and item_id=?', get_class( $item ), $item->$idColumn ) );

			/* Follows */
			if ( IPS::classUsesTrait( $this, 'IPS\Content\Followable' ) )
			{
				Db::i()->update( 'core_follow', "`follow_id` = MD5( CONCAT( `follow_app`, ';', `follow_area`, ';', {$this->$idColumn}, ';', `follow_member_id` ) ), `follow_rel_id` = {$this->$idColumn}", array( "follow_id=MD5( CONCAT( `follow_app`, ';', `follow_area`, ';', {$item->$idColumn}, ';', `follow_member_id` ) )" ), array(), NULL, Db::IGNORE );
				Db::i()->delete( 'core_follow_count_cache', array( 'class=? AND id=?', get_called_class(), (int) $this->$idColumn ) );
			}
			
			/* Update moderation history */
            Db::i()->update( 'core_moderator_logs', array( 'item_id' => $this->$idColumn ), array( 'item_id=? AND class=?', $item->$idColumn, get_class( $this ) ) );
			Session::i()->modLog( 'modlog__action_merge', array( $item->mapped('title') => FALSE, $this->url()->__toString() => FALSE, $this->mapped('title') => FALSE ), $this );
			
			/* Add to the redirect table */
			$item->setRedirectTo( $this );
			
			/* If we are adding redirects to the merged items, then we need to change these to link items. */
			if ( $keepLinks AND isset( $item::$databaseColumnMap['moved_to'] ) )
			{
				$movedToColumn			= static::$databaseColumnMap['moved_to'];
				$item->$movedToColumn	= $this->$idColumn . '&' . $this->container()->_id;
				
				if ( isset( static::$databaseColumnMap['status'] ) )
				{
					$statusColumn			= static::$databaseColumnMap['status'];
					$item->$statusColumn	= 'merged';
				}
				
				if ( isset( static::$databaseColumnMap['moved_on'] ) )
				{
					$movedOnColumn			= static::$databaseColumnMap['moved_on'];
					$item->$movedOnColumn	= time();
				}

				/* Move links cannot be hidden or pending approval */
				if ( IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) and ( isset( $item::$databaseColumnMap['hidden'] ) OR isset( $item::$databaseColumnMap['approved'] ) ) )
				{
					/* Now do the actual stuff */
					if ( isset( $item::$databaseColumnMap['hidden'] ) )
					{
						$column = $item::$databaseColumnMap['hidden'];

						$item->$column = 0;
					}
					elseif ( isset( $item::$databaseColumnMap['approved'] ) )
					{
						$column = $item::$databaseColumnMap['approved'];

						$item->$column = 1;
					}
				}

				/* Also remove unapproved and hidden comment counts if this is a move/merge link */
				if ( isset( $item::$databaseColumnMap['unapproved_comments'] ) )
				{
					$column = $item::$databaseColumnMap['unapproved_comments'];

					$item->$column = 0;
				}
				if ( isset( $item::$databaseColumnMap['hidden_comments'] ) )
				{
					$column = $item::$databaseColumnMap['hidden_comments'];

					$item->$column = 0;
				}

				if ( isset( $item::$databaseColumnMap['unapproved_reviews'] ) )
				{
					$column = $item::$databaseColumnMap['unapproved_reviews'];

					$item->$column = 0;
				}
				if ( isset( $item::$databaseColumnMap['hidden_reviews'] ) )
				{
					$column = $item::$databaseColumnMap['hidden_reviews'];

					$item->$column = 0;
				}
				
				$item->save();
			}
			else
			{
				/* Otherwise just delete them */
				$item->delete();
			}

			/* We need to reset container counts after */
			try
			{
				$item->container()->resetCommentCounts();
				$item->container()->save();
			}
			catch( BadMethodCallException $e ) {}
		}
		
		if ( $views > 0 )
		{
			/* @var array $databaseColumnMap */
			$viewColumn = $item::$databaseColumnMap['views'];
			$this->$viewColumn = $this->mapped('views') + $views;
		}
		
		$this->rebuildFirstAndLastCommentData();

		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->rebuildAfterMerge( $this );
		}

        Event::fire( 'onMerge', $this, array( $items ) );
	}

	/**
	 * @brief	Force comments() calls to write database server if read/write separation is used
	 */
	protected static bool $useWriteServer	= FALSE;
	
	/**
	 * Rebuild meta data after splitting/merging
	 *
	 * @return	void
	 */
	public function rebuildFirstAndLastCommentData(): void
	{
		$existingFlag = static::$useWriteServer;
		static::$useWriteServer = TRUE;

		if ( isset( static::$commentClass ) )
		{
			$firstComment = $this->comments( 1, 0, 'date', 'asc', NULL, static::$firstCommentRequired ?: FALSE, NULL, NULL, TRUE );
			$idColumn = static::$databaseColumnId;

			/* @var Comment $commentClass */
			$commentClass = static::$commentClass;
			$commentIdColumn = $commentClass::$databaseColumnId;

			/* Reset the content 'author' if the first comment is required (i.e. in posts), otherwise the first comment author
			should not be set as the file submitter in downloads (eg) */
			if ( static::$firstCommentRequired )
			{
				if ( static::$changeItemAuthorChangingFirstComment )
				{
					if ( isset( static::$databaseColumnMap['author'] ) )
					{
						$authorField = static::$databaseColumnMap['author'];
						$this->$authorField = $firstComment->author()->member_id ?: 0;
					}
					if ( isset( static::$databaseColumnMap['author_name'] ) )
					{
						$authorNameField = static::$databaseColumnMap['author_name'];
						$this->$authorNameField = $firstComment->mapped('author_name');
					}
				}
				if ( isset( static::$databaseColumnMap['date'] ) )
				{
					if( is_array( static::$databaseColumnMap['date'] ) )
					{
						$dateField = static::$databaseColumnMap['date'][0];
					}
					else
					{
						$dateField = static::$databaseColumnMap['date'];
					}

					$this->$dateField = $firstComment->mapped('date');
				}
			}
			if ( isset( static::$databaseColumnMap['first_comment_id'] ) )
			{
				$firstCommentField = static::$databaseColumnMap['first_comment_id'];
				$this->$firstCommentField = $firstComment->$commentIdColumn;
			}

			/* Set first comments */
			if ( isset( $commentClass::$databaseColumnMap['first'] ) )
			{
				/* This can fail if we are, for example, splitting a post into a new topic, where a previous comment does not exist */
				$hasPrevious = TRUE;
				try
				{
					$previousFirstComment = $commentClass::constructFromData( Db::i()->select( '*', $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['first'] . '=?', $this->$idColumn, TRUE ), NULL, 1 )->first() );
				}
				catch( UnderflowException $e )
				{
					$hasPrevious = FALSE;
				}

				if ( $hasPrevious )
				{
					if ( $previousFirstComment->$commentIdColumn !== $firstComment->$commentIdColumn )
					{
						$firstColumn = $commentClass::$databaseColumnMap['first'];

						$previousFirstComment->$firstColumn = FALSE;
						$previousFirstComment->save();

						$firstComment->$firstColumn = TRUE;
						$firstComment->save();
					}
				}
				else
				{
					$firstColumn = $commentClass::$databaseColumnMap['first'];

					$firstComment->$firstColumn = TRUE;
					$firstComment->save();
				}
			}

			/* If this is a new item from a split and the first comment is hidden, we need to adjust the item hidden/approved attribute. */
			if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) and static::$firstCommentRequired and isset( $firstComment::$databaseColumnMap['hidden'] ) )
			{
				$commentColumn = $firstComment::$databaseColumnMap['hidden'];
				if ( $firstComment->$commentColumn == -1 )
				{
					/* The first comment is hidden so ensure topic is actually hidden correctly and all posts have a queued status of 2 to denote parent is hidden */
					Db::i()->update( $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] => 2 ), array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
					$this->hide( NULL );
				}
			}

			/* Update mappings */
			if ( isset( static::$databaseColumnMap['container'] ) and IPS::classUsesTrait( $this->container(), 'IPS\Node\Statistics' ) )
			{
				$this->container()->rebuildPostedIn( array( $this->$idColumn ) );
			}
		}
		
		/* Update last comment stuff */
		$this->resyncLastComment();

		/* Update last review stuff */
		$this->resyncLastReview();

		/* Update number of comments */
		$this->resyncCommentCounts();

		/* Update number of reviews */
		$this->resyncReviewCounts();

		/* Save*/
		$this->save();

		/* run only if we have a container */
		if ( isset( static::$databaseColumnMap['container'] ) )
		{
			/* Update container */
			$this->container()->resetCommentCounts();
			$this->container()->setLastComment();
			$this->container()->setLastReview();
			$this->container()->save();
		}
		
		/* Clear cached statistics */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Statistics' ) )
		{
			$this->clearCachedStatistics();
		}

		/* Add to search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}

		static::$useWriteServer = $existingFlag;
	}
		
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* Remove from search index - we must do this before deleting comments so we know what to remove */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->removeFromSearchIndex( $this );
		}

		$idColumn = static::$databaseColumnId;

		/* Don't do anything for shadow items */
		if ( isset( static::$databaseColumnMap['moved_to'] ) )
		{
			$movedToColumn = static::$databaseColumnMap['moved_to'];
			if ( $this->$movedToColumn )
			{
				/* Go ahead and delete this item record and return now */
				parent::delete();

				return;
			}
		}

		Db::i()->delete( 'core_item_member_map', ['map_class=? and map_item_id=?', get_class( $this ), (int)$this->$idColumn] );

		/* Remove any meta data */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\MetaData' ) )
		{
			$this->deleteAllMeta();
		}

		/* Unclaim attachments */
		$this->unclaimAttachments();

		/* Delete it from the database */
		parent::delete();

		/* Update count */
		try
		{
			if ( $this->container()->_items !== null )
			{
				if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) and $this->isFutureDate() and $this->container()->_futureItems !== null )
				{
					$this->container()->_futureItems = ( $this->container()->_futureItems - 1 );
				}
				elseif ( !$this->hidden() )
				{
					$this->container()->_items = ( $this->container()->_items - 1 );
				}
				elseif ( $this->hidden() === 1 )
				{
					$this->container()->_unapprovedItems = ( $this->container()->_unapprovedItems - 1 );
				}
			}
		}
		catch ( BadMethodCallException $e )
		{
		}

		/* Delete comments */
		if ( isset( static::$commentClass ) )
		{
			/* @var Comment $commentClass */
			/* @var array $databaseColumnMap */
			$commentClass = static::$commentClass;
			/* @var $databaseColumnMap array */
			$where = [[$commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $this->$idColumn]];

			if ( method_exists( $commentClass, 'deleteWhereSql' ) )
			{
				$where = $commentClass::deleteWhereSql( $this->$idColumn );
			}

			/* Remove any deletion logs for comments */
			$commentIds = [];
			$commentIdColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnId;
			foreach ( Db::i()->select( $commentIdColumn, $commentClass::$databaseTable, [$commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $this->$idColumn] ) as $commentId )
			{
				$commentIds[] = $commentId;
			}

			$this->deleteCommentOrReviewData( $commentIds, $commentClass );

			if ( count( $where ) )
			{
				Db::i()->delete( $commentClass::$databaseTable, $where );
			}

			if ( !$this->skipContainerRebuild )
			{
				try
				{
					if ( $this->container()->_comments !== null )
					{
						/* We decrement the comment count onHide() */
						if ( !$this->hidden() )
						{
							$this->container()->_comments = ( $this->container()->_comments - $this->mapped( 'num_comments' ) );
						}

						$this->container()->setLastComment();
					}
					if ( $this->container()->_unapprovedComments !== null )
					{
						$this->container()->_unapprovedComments = ( $this->container()->_unapprovedComments > 0 ) ? ( $this->container()->_unapprovedComments - $this->mapped('unapproved_comments') ) : 0;
					}
					$this->container()->save();
				}
				catch ( BadMethodCallException $e )
				{
				}
			}
		}

		/* Delete reviews */
		if ( isset( static::$reviewClass ) )
		{
			/* @var array $databaseColumnMap */
			$reviewClass = static::$reviewClass;
			$where = [[$reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn]];

			if ( method_exists( $reviewClass, 'deleteWhereSql' ) )
			{
				$where = $reviewClass::deleteWhereSql( $this->$idColumn );
			}

			/* Remove any deletion logs for reviews */
			$reviewIds = [];
			$reviewIdColumn = $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'];
			foreach ( Db::i()->select( $reviewIdColumn, $reviewClass::$databaseTable, [$reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn] ) as $reviewId )
			{
				$reviewIds[] = $reviewId;
			}

			$this->deleteCommentOrReviewData( $reviewIds, $reviewClass );

			Db::i()->delete( $reviewClass::$databaseTable, $where );

			if ( !$this->skipContainerRebuild )
			{
				try
				{
					if ( $this->container()->_reviews !== null )
					{
						/* We decrement the review count onHide() */
						if ( !$this->hidden() )
						{
							$this->container()->_reviews = ( $this->container()->_reviews - $this->mapped( 'num_reviews' ) );
						}

						$this->container()->setLastReview();
					}
					if ( $this->container()->_unapprovedReviews !== null )
					{
						$this->container()->_unapprovedReviews = ( $this->container()->_unapprovedReviews - $this->mapped( 'unapproved_reviews' ) );
					}
					$this->container()->save();
				}
				catch ( BadMethodCallException $e )
				{
				}
			}
		}

		/* Delete tags */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) )
		{
			$aaiLookup = $this->tagAAIKey();
			Db::i()->delete( 'core_tags', ['tag_aai_lookup=?', $aaiLookup] );
			Db::i()->delete( 'core_tags_cache', ['tag_cache_key=?', $aaiLookup] );
			Db::i()->delete( 'core_tags_perms', ['tag_perm_aai_lookup=?', $aaiLookup] );

			$idColumn = static::$databaseColumnId;
			Db::i()->delete( 'core_tags_pinned', [ 'pinned_item_class=? and pinned_item_id=?', get_called_class(), $this->$idColumn ] );
		}

		/* Delete follows */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Followable' ) )
		{
			$followArea = mb_strtolower( mb_substr( get_called_class(), mb_strrpos( get_called_class(), '\\' ) + 1 ) );
			Db::i()->delete( 'core_follow', ['follow_app=? AND follow_area=? AND follow_rel_id=?', static::$application, $followArea, (int)$this->$idColumn] );
			Db::i()->delete( 'core_follow_count_cache', ['class=? AND id=?', get_called_class(), (int)$this->$idColumn] );
		}

		/* Remove Notifications */
		$memberIds = [];

		foreach ( Db::i()->select( '`member`', 'core_notifications', ['item_class=? AND item_id=?', get_class( $this ), (int)$this->$idColumn] ) as $member )
		{
			$memberIds[$member] = $member;
		}

		Db::i()->delete( 'core_notifications', ['item_class=? AND item_id=?', get_class( $this ), (int)$this->$idColumn] );

		/* Delete from redirect links */
		Db::i()->delete( 'core_item_redirect', ['redirect_class=? AND redirect_new_item_id=?', get_class( $this ), (int)$this->$idColumn] );

		/* Delete Polls */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Polls' ) and $this->getPoll() )
		{
			$this->getPoll()->delete();
		}

		/* Delete Ratings */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Ratings' ) )
		{
			Db::i()->delete( 'core_ratings', ['class=? AND item_id=?', get_called_class(), $this->$idColumn] );
		}

		foreach ( $memberIds as $member )
		{
			Member::load( $member )->recountNotifications();
		}

		/** Item::url() can throw a LogicException exception in specific cases like when a Pages Record has no valid page */
		if ( !static::$skipIndexNow )
		{
			try
			{
				IndexNow::addUrlToQueue( $this->url() );
			}
			catch ( LogicException $e )
			{
			}
		}

		Event::fire( 'onDelete', $this );
	}

/**
 * Deletes any additional comment Related data
	 *
	 * @param array 	$ids			comment or review ids which are going to be deleted
	 * @param string	$class			comment or review class name
	 *
	 * @return	void
	 */
	protected function deleteCommentOrReviewData( array $ids, string $class ): void
	{
		Db::i()->delete( 'core_deletion_log', array('dellog_content_class=? AND ' . Db::i()->in( 'dellog_content_id', $ids ), $class) );

		if( IPS::classUsesTrait( $class, 'IPS\Content\Featurable' ) )
		{
			Db::i()->delete( 'core_content_promote', array('promote_class=? AND ' . Db::i()->in( 'promote_class_id', $ids ), $class) );
		}

		Db::i()->delete( 'core_reputation_index', array('rep_class=? AND ' . Db::i()->in( 'type_id', $ids ), $class) );
		Db::i()->delete( 'core_solved_index', array('comment_class=? AND ' . Db::i()->in( 'comment_id', $ids ), $class) );
	}

	/**
	 * Deletion log Permissions
	 * Usually, this is the same as searchIndexPermissions. However, some applications may restrict searching but
	 * still want to allow delayed deletion log viewing and searching
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function deleteLogPermissions(): string
	{
		try
		{
			return $this->container()->deleteLogPermissions();
		}
			/* The container may not exist */
		catch( BadMethodCallException | OutOfRangeException )
		{
			return '';
		}
	}

	/**
	 * Online List Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function onlineListPermissions(): string
	{
		if( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) and $this->hidden() )
		{
			return '0';
		}

		if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() )
		{
			return '0';
		}

		if( $extension = Content\Search\SearchContent::extension( $this ) )
		{
			return $extension->searchIndexPermissions();
		}

		try
		{
			return $this->directContainer()->searchIndexPermissions();
		}
			/* If no container supported, assume yes. */
		catch( BadMethodCallException )
		{
			return '*';
		}
			/* If container is missing, assume no. */
		catch( OutOfRangeException )
		{
			return '0';
		}
	}

	/**
	 * Get permission index ID
	 *
	 * @return	int|NULL
	 */
	public function permId(): int|NULL
	{
		try
		{
			$permissions = $this->container()->permissions();
			return $permissions['perm_id'];
		}
		catch( BadMethodCallException )
		{
			return NULL;
		}
	}
	
	/**
	 * Change IP Address
	 * @param string $ip		The new IP address
	 *
	 * @return void
	 */
	public function changeIpAddress( string $ip ): void
	{
		parent::changeIpAddress( $ip );
				
		/* How about a required comment? */
		if ( isset( static::$commentClass ) and static::$firstCommentRequired )
		{
			$commentClass = static::$commentClass;

			if ( isset( static::$databaseColumnMap['first_comment_id'] ) AND $comment = $this->firstComment() )
			{
				$comment->changeIpAddress( $ip );
			}
		}
	}
	
	/**
	 * Change Author
	 *
	 * @param	Member	$newAuthor	The new author
	 * @param bool $log		If TRUE, action will be logged to moderator log
	 * @return	void
	 */
	public function changeAuthor( Member $newAuthor, bool $log=TRUE ): void
	{
		$oldAuthor = $this->author();

		/* If we delete a member, then change author, the old author returns 0 as does the new author as the
		   member row is deleted before the task is run */
		if( $newAuthor->member_id and ( $oldAuthor->member_id == $newAuthor->member_id ) )
		{
			return;
		}

		/* Update the row */
		parent::changeAuthor( $newAuthor, $log );
		
		/* Adjust post counts, but only if this is a visible post or the previous user was not a guest */
		if ( static::incrementPostCount( $this->containerWrapper() ) AND ( $oldAuthor->member_id OR $this->hidden() === 0 ) and ( IPS::classUsesTrait( $this, 'IPS\Content\Anonynmous' ) AND ! $this->isAnonymous() ) )
		{
			if( $oldAuthor->member_id )
			{
				$oldAuthor->member_posts--;
				$oldAuthor->save();
			}
			
			if( $newAuthor->member_id )
			{
				$newAuthor->member_posts++;
				$newAuthor->save();
			}
		}

		$setComment	= FALSE;
		if ( isset( static::$commentClass ) and static::$firstCommentRequired )
		{
			if ( isset( static::$databaseColumnMap['first_comment_id'] ) AND $comment = $this->firstComment() )
			{
				$comment->changeAuthor( $newAuthor, $log );

				$setComment	= TRUE;
			}
		}
		
		/* Update container, but don't bother if we just updated the comment because it will have triggered the container to update */
		if ( !$setComment AND $container = $this->containerWrapper() )
		{
			$container->setLastComment( updatedItem: $this );
			$container->setLastReview();
			$container->save();
		}
		
		/* Update search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\ItemTopic' ) )
		{
			$this->itemAuthorChanged( $newAuthor, $log );
		}
	}
	
	/**
	 * Unclaim attachments
	 *
	 * @return	void
	 */
	protected function unclaimAttachments(): void
	{
		$idColumn = static::$databaseColumnId;
		File::unclaimAttachments( static::$application . '_' . IPS::mb_ucfirst( static::$module ), $this->$idColumn );
	}
	
	/**
	 * @brief Cached containers we can access
	 */
	protected static array $permissionSelect	= array();

	/**
	 * @brief Query flag to select IDs first. This is generally more efficient as it means you do not have to use loads of joins which slows down the query.
	 */
	const SELECT_IDS_FIRST = 256;
	
	/**
	 * Get items with permission check
	 *
	 * @param array $where				Where clause
	 * @param string|null $order				MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit				Limit clause
	 * @param string $permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param int|bool|null $includeHiddenItems	Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param int $queryFlags			Select bitwise flags
	 * @param	Member|null	$member				The member (NULL to use currently logged in member)
	 * @param bool $joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly			If true will return the count
	 * @param array|null $joins				Additional arbitrary joins for the query
	 * @param bool|Model $skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip container-based permission. You must still specify this in the $where clause
	 * @param bool $joinTags			If true, will join the tags table
	 * @param bool $joinAuthor			If true, will join the members table for the author
	 * @param bool $joinLastCommenter	If true, will join the members table for the last commenter
	 * @param bool $showMovedLinks		If true, moved item links are included in the results
	 * @param array|null $location			Array of item lat and long
	 * @return	ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( array $where=array(), string $order=NULL, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems= Filter::FILTER_AUTOMATIC, int $queryFlags=0, ?Member $member=null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, ?array $joins=null, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, ?array $location=null ): ActiveRecordIterator|int
	{
		/* Are we trying to improve count performance? */
		$countShortcut = FALSE;

		$having = NULL;
		if ( isset( $location['lat'] ) and isset( $location['lon'] ) )
		{
			/* Make sure co-ordinates are in a valid format regardless of locale */
			$location['lat'] = number_format( $location['lat'], 6, '.', '' );
			$location['lon'] = number_format( $location['lon'], 6, '.', '' );

			$where[] = array( static::$databasePrefix . 'latitude' . ' IS NOT NULL AND ' . static::$databasePrefix . 'longitude' . ' IS NOT NULL' );
			$having = array( 'distance < 500' );
			$order = 'distance ASC';
		}

		/* Do we really need tags? */
		if ( $joinTags and ! Settings::i()->tags_enabled )
		{
			$joinTags = FALSE;	
		}
		
		/* Work out the order */
		if ( $order === NULL )
		{
			if( isset( static::$databaseColumnMap[ 'date' ] ) )
			{
				$dateColumn = static::$databaseColumnMap['date'];
				if ( is_array( $dateColumn ) )
				{
					$dateColumn = array_pop( $dateColumn );
				}
				$order = static::$databaseTable . '.' . static::$databasePrefix . $dateColumn . ' DESC';
			}
		}
		
		$containerWhere = array();
		
		/* Queries are always more efficient when the WHERE clause is added to the ON */
		if ( is_array( $where ) )
		{
			foreach( $where as $key => $value )
			{
				if ( $key == 'item' )
				{
					$where = array_merge( $where, $value );
					
					unset( $where[ $key ] );
				}
				
				if ( $key == 'container' )
				{
					$containerWhere = array_merge( $containerWhere, $value );
					unset( $where[ $key ] );

					/* $containerWhere is used for exclusion purposes now,
					so we leave this as part of the where condition as well. */
					$where = array_merge( $where, $value );
				}
			}
		}
		
		/* Exclude hidden items */
		$includeAdditionalApprovalClauses = true;
		if( IPS::classUSesTrait( get_called_class(), 'IPS\Content\Hideable' ) and $includeHiddenItems === Filter::FILTER_AUTOMATIC )
		{
			$containersTheUserCanViewHiddenItemsIn = static::canViewHiddenItemsContainers( $member );
			if ( $containersTheUserCanViewHiddenItemsIn === TRUE )
			{
				$includeHiddenItems = Filter::FILTER_SHOW_HIDDEN;
			}
			elseif ( is_array( $containersTheUserCanViewHiddenItemsIn ) )
			{
				$includeHiddenItems = $containersTheUserCanViewHiddenItemsIn;
			}
			else
			{
				$includeHiddenItems = Filter::FILTER_OWN_HIDDEN;
			}
		}

		if ( IPS::classUSesTrait( get_called_class(), 'IPS\Content\Hideable' ) and $includeHiddenItems === Filter::FILTER_ONLY_HIDDEN )
		{
			/* If we can't view hidden stuff, just return now */
			if( !static::canViewHiddenItemsContainers( $member ) )
			{
				return $countOnly ? 0 : new ActiveRecordIterator( new ArrayIterator( array() ), get_called_class() );
			}

			if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['approved'];
				$where[] = array( "{$col}=0" );
			}
			elseif ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['hidden'];
				$where[] = array( "{$col}=1" );
			}

			$includeAdditionalApprovalClauses = false;
		}
		elseif ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Hideable' ) and $includeHiddenItems !== Filter::FILTER_SHOW_HIDDEN )
		{
			$member = $member ?: Member::loggedIn();
			$extra = is_array( $includeHiddenItems ) ? ( ' OR ' . Db::i()->in( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'], $includeHiddenItems ) ) : '';
			
			if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['approved'];
				if ( $member->member_id and $includeHiddenItems !== Filter::FILTER_PUBLIC_ONLY and isset( static::$databaseColumnMap['author'] ) )
				{
					$authorCol = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['author'];
					/* Only fetching a count, for a single container with a item cache count, no future publishing, and there is only one where clause (the container limitation) */
					if( $countOnly === TRUE AND $skipPermission instanceof Model AND $skipPermission->_items !== NULL AND !in_array( 'IPS\Content\FuturePublishing', class_implements( get_called_class() ) ) AND count( $where ) === 1 )
					{
						$countShortcut = TRUE;
						$where[] = array( "({$col}=0 AND ( {$authorCol}={$member->member_id}{$extra} ) )" );
					}
					else
					{
						$where[] = array( "( {$col}=1 OR ( {$col}=0 AND ( {$authorCol}={$member->member_id}{$extra} ) ) )" );
					}
				}
				else
				{
					$where[] = array( "{$col}=1" );
				}

				$includeAdditionalApprovalClauses = false;
			}
			elseif ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['hidden'];
				if ( $member->member_id and $includeHiddenItems !== Filter::FILTER_PUBLIC_ONLY and isset( static::$databaseColumnMap['author'] ) )
				{
					$authorCol = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['author'];
					/* Only fetching a count, for a single container with a item cache count, no future publishing, and there is only one where clause (the container limitation) */
					if( $countOnly === TRUE AND $skipPermission instanceof Model AND $skipPermission->_items !== NULL AND !IPS::classUsesTrait( get_called_class(), 'IPS\Content\FuturePublishing' ) AND count( $where ) === 1 )
					{
						$countShortcut = TRUE;
						$where[] = array( "({$col}=1 AND ( {$authorCol}={$member->member_id}{$extra} ) )" );
					}
					else
					{
						$where[] = array( "( {$col}=0 OR ( {$col}=1 AND ( {$authorCol}={$member->member_id}{$extra} ) ) )" );
					}
				}
				else
				{
					$where[] = array( "{$col}=0" );
				}

				$includeAdditionalApprovalClauses = false;
			}
		}
        else
        {
			if ( is_array( $includeHiddenItems ) )
			{
				$where[] = array( Db::i()->in( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'], $includeHiddenItems ) );
			}
	        
            /* Legacy items pending deletion in 3.x at time of upgrade may still exist */
            $col	= null;

            if ( isset( static::$databaseColumnMap['approved'] ) )
            {
                $col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['approved'];
            }
            else if( isset( static::$databaseColumnMap['hidden'] ) )
            {
                $col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['hidden'];
            }

            if( $col )
            {
            	$where[] = array( "{$col} < 2" );
            }
        }
        
        /* This only makes sense if we have not already filtered by a single value */
		if( $includeAdditionalApprovalClauses )
		{
			/* No matter if we can or cannot view hidden items, we do not want these to show: -2 is queued for deletion and -3 is posted before register */
			if ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['hidden'];
				$where[] = array( "{$col}!=-2 AND {$col} !=-3" );
			}
			else if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['approved'];
				$where[] = array( "{$col}!=-2 AND {$col}!=-3" );
			}
		}
        
		/* Future items? */
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\FuturePublishing' ) )
		{
			$member = $member ?: Member::loggedIn();
			$authorCol = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['author'];

			if ( ! static::canViewFutureItems( $member ) )
			{
				$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['is_future_entry'];
				if ( $member->member_id )
				{
					$where[] = array( "( {$col}=0 OR ( {$col}=1 AND {$authorCol}={$member->member_id} ) )" );
				}
				else
				{
					$where[] = array( "{$col}=0" );
				}
			}
		}
		
		/* Don't show links to moved items? */
		if ( ! $showMovedLinks and isset( static::$databaseColumnMap['moved_to'] ) and ( ( $skipPermission or $permissionKey === NULL ) or ( !$skipPermission and in_array( $permissionKey, array( 'view', 'read' ) ) ) ) )
		{
			$where[] = array( "( NULLIF(" . static::$databaseTable . "." . static::$databaseColumnMap['moved_to'] . ", '') IS NULL )" );
		}

		/* Set permissions */
		if ( $permissionKey !== NULL and !$skipPermission and isset( static::$containerNodeClass ) and is_subclass_of( static::$containerNodeClass, 'IPS\Node\Permissions' ) )
		{
			$containerClass = static::$containerNodeClass;
			$member = $member ?: Member::loggedIn();
			$categories	= array();
			$lookupKey	= md5( $containerClass::$permApp . $containerClass::$permType . $permissionKey . json_encode( $member->groups ) );

			/* EME: Switch to use exclusion instead of inclusion */
			/* @var $permissionMap array */
			if( !isset( static::$permissionSelect[ $lookupKey ] ) )
			{
				static::$permissionSelect[ $lookupKey ] = array();
				$permQueryJoinContainer = (bool) ( count( $containerWhere ) );
				$clubWhere = "";

				/* If we cannot access clubs, skip them */
				if ( IPS::classUsesTrait( $containerClass, 'IPS\Content\ClubContainer' ) AND !$member->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
				{
					$permQueryWhere = array( "core_permission_index.app='" . $containerClass::$permApp . "' AND core_permission_index.perm_type='" . $containerClass::$permType . "' AND (" . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::clubIdColumn() . " IS NOT NULL OR !(" . Db::i()->findInSet( 'perm_' . $containerClass::$permissionMap[ $permissionKey ], $member->permissionArray() ) . ' OR ' . 'perm_' . $containerClass::$permissionMap[ $permissionKey ] . "='*' ))" );
					$permQueryJoinContainer = true;
				}
				else
				{
					$permQueryWhere = array( "core_permission_index.app='" . $containerClass::$permApp . "' AND core_permission_index.perm_type='" . $containerClass::$permType . "' AND !(" . Db::i()->findInSet( 'perm_' . $containerClass::$permissionMap[ $permissionKey ], $member->permissionArray() ) . ' OR ' . 'perm_' . $containerClass::$permissionMap[ $permissionKey ] . "='*' )" );
				}

				$permQuery = Db::i()->select( 'perm_type_id', 'core_permission_index', $permQueryWhere );

				if ( $permQueryJoinContainer )
				{
					$permQuery->join( $containerClass::$databaseTable, array_merge( $containerWhere, array( 'core_permission_index.perm_type_id=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) ), 'STRAIGHT_JOIN' );
				}

				foreach( $permQuery as $result )
				{
					static::$permissionSelect[ $lookupKey ][] = $result;
				}
			}

			$categories = static::$permissionSelect[ $lookupKey ];

			if( count( $categories ) )
			{
				$where[] = array( static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnMap['container'] . ' NOT IN (' . implode( ',', $categories ) . ')' );
			}
		}
		
		$groupBy = ( $joinComments ? static::$databasePrefix . static::$databaseColumnId : NULL );
		/* @var $databaseColumnMap array */
		/* Build the select clause */
		if( $countOnly )
		{
			$select = Db::i()->select( 'COUNT(*) as cnt', static::$databaseTable, $where, NULL, NULL, $groupBy, NULL, $queryFlags );
			if ( $joinContainer AND isset( static::$containerNodeClass ) )
			{
				/* EME: Removed the $containerWhere from the join because it is now in the where clause.
				We are now using $containerWhere to exclude forums that shouldn't be visible. */
				$containerClass = static::$containerNodeClass;
				$select->join( $containerClass::$databaseTable, array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'] . '=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) );
			}
			if ( $joinComments )
			{
				/* @var Comment $commentClass */
				$commentClass = static::$commentClass;
				$select->join( $commentClass::$databaseTable, array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=' . static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId ) );
			}
			if ( $joins !== NULL AND count( $joins ) )
			{
				foreach( $joins as $join )
				{
					$select->join( $join['from'], ( $join['where'] ?? null ), ( $join['type'] ?? 'LEFT' ) );
				}
			}
			
			try
			{
				$count = $select->first();
			}
			catch ( UnderflowException $e )
			{
				$count = 0;
			}

			/* Were we trying to take a shortcut for performance reasons? */
			if( $countShortcut === TRUE )
			{
				return $count + $skipPermission->_items;
			}

			return $count;
		}
		else
		{
			if ( ( $queryFlags & static::SELECT_IDS_FIRST or CIC ) or $groupBy )
			{
				$pass = false;
				
				if ( is_numeric( $limit ) and $limit <= 2000 )
				{
					$pass = true;
				}
				else if ( is_array( $limit ) and $limit[1] <= 2000 )
				{
					$pass = true;
				}

				if ( $pass === true )
				{
					$subSelectClause = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId;

					/* Are we doing a pseudo-rand ordering? */
					if( $order == '_rand' )
					{
						$subSelectClause	.= static::_getRandomizationSql( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId, static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['title'] );
					}

					$select = Db::i()->select( $subSelectClause, static::$databaseTable, array_merge( $where, $containerWhere ), $order, $limit, ( $joinComments ? static::$databasePrefix . static::$databaseColumnId : NULL ), NULL, $queryFlags );
					
					if ( ( $joinContainer OR $containerWhere ) AND isset( static::$containerNodeClass ) )
					{
						$containerClass = static::$containerNodeClass;
						$select->join( $containerClass::$databaseTable, array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'] . '=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) );
					}
					
					if ( $joinComments )
					{
						/* @var Comment $commentClass */
						$commentClass = static::$commentClass;
						$select->join( $commentClass::$databaseTable, array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=' . static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId ) );
					}
					
					if ( $joins !== NULL AND count( $joins ) )
					{
						foreach( $joins as $join )
						{
							$select->join( $join['from'], ( $join['where'] ?? null ), ( $join['type'] ?? 'LEFT' ) );
						}
					}

					if( $order == '_rand' )
					{
						$ids = array();
						foreach ( iterator_to_array( $select ) as $item )
						{
							$ids[] = $item[static::$databasePrefix . static::$databaseColumnId];
						}
					}
					else
					{
						$ids = iterator_to_array( $select );
					}

					if ( count( $ids ) )
					{
						/* Reset the where */
						$where = array( array( Db::i()->in( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId, $ids ) ) );

						/* Reset the offset */
						$limit = NULL;
						
						/* Drop the group by as it will fail due to ONLY_FULL_GROUP_BY and we already have the item ids we need */
						$groupBy = NULL;
						
						/* Set joinComments to false as we do not need it now we have the ids */
						$joinComments = FALSE;
					}
					else
					{
						/* If no ids were found, stop now - there are no results. If we don't return, the original regular query will run and return unexpected results */
						return new ActiveRecordIterator( new ArrayIterator, get_called_class() );
					}
				}
			}
			
			/* We always want to make this multidimensional */
			$queryFlags |= Db::SELECT_MULTIDIMENSIONAL_JOINS;
			
			$selectClause = static::$databaseTable . '.*';

			if ( isset( $location['lat'] ) and isset( $location['lon'] ) and is_numeric( $location['lat'] ) and is_numeric( $location['lon'] )  )
			{
				$selectClause .= ', ( 3959 * acos( cos( radians(' . $location['lat'] . ') ) * cos( radians( ' . static::$databasePrefix . 'latitude' . ' ) ) * cos( radians( ' . static::$databasePrefix . 'longitude' . ' ) - radians( ' . $location['lon'] . ' ) ) + sin( radians( ' . $location['lat'] . ' ) ) * sin( radians( ' . static::$databasePrefix . 'latitude' . ') ) ) ) AS distance';
			}

            if( $joinAuthor and isset( static::$databaseColumnMap['author'] ) )
            {
                $selectClause .= ', author.*';
            }
            if( $joinLastCommenter and isset( static::$databaseColumnMap['last_comment_by'] ) )
            {
                $selectClause .= ', last_commenter.*';
            }

			/* Are we doing a pseudo-rand ordering? */
			if( $order == '_rand' )
			{
				$selectClause	.= static::_getRandomizationSql( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId, static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['title'] );
			}

			if ( $joins !== NULL AND count( $joins ) )
			{
				foreach( $joins as $join )
				{
					if( isset( $join['select']) AND $join['select'] )
					{
						$selectClause .= ', ' . $join['select'];
					}
				}
			}
			
			if ( $joinTags and IPS::classUsesTrait( get_called_class(), 'IPS\Content\Taggable' ) )
			{
				$selectClause .= ', core_tags_cache.tag_cache_text';
			}

			$select = Db::i()->select( $selectClause, static::$databaseTable, $where, $order, $limit, $groupBy, $having, $queryFlags );
		}

		/* Join stuff */
		if ( $joinContainer AND isset( static::$containerNodeClass ) )
		{
			$containerClass = static::$containerNodeClass;
			$select->join( $containerClass::$databaseTable, array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'] . '=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) );
		}
		if ( $joinComments )
		{
			/* @var Comment $commentClass */
			$commentClass = static::$commentClass;
			$select->join( $commentClass::$databaseTable, array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=' . static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId ) );
		}
		if ( $joinReviews )
		{
			$reviewClass = static::$reviewClass;
			$select->join( $reviewClass::$databaseTable, array( $reviewClass::$databaseTable . '.' . $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=' . static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnId ) );
		}

		/* Join the tags cache, if applicable */
		if ( $joinTags and IPS::classUsesTrait( get_called_class(), 'IPS\Content\Taggable' ) )
		{
			/* @var Item $itemClass */
			$itemClass = get_called_class();
			$idColumn = static::$databasePrefix . static::$databaseColumnId;
			$select->join( 'core_tags_cache', array( "tag_cache_key=MD5(CONCAT(?,{$itemClass::$databaseTable}.{$idColumn}))", static::$application . ';' . static::$module . ';' ) );
		}

        /* Join the members table */
        if ( $joinAuthor and isset( static::$databaseColumnMap['author'] ) )
        {
            $authorColumn = static::$databaseColumnMap['author'];
            $select->join( array( 'core_members', 'author' ), array( 'author.member_id = ' . static::$databaseTable . '.' . static::$databasePrefix . $authorColumn ) );
        }
	    if ( $joinLastCommenter and isset( static::$databaseColumnMap['last_comment_by'] ) )
	    {
	        $lastCommeneterColumn = static::$databaseColumnMap['last_comment_by'];
            $select->join( array( 'core_members', 'last_commenter' ), array( 'last_commenter.member_id = ' . static::$databaseTable . '.' . static::$databasePrefix . $lastCommeneterColumn ) );
	    }

        if ( $joins !== NULL AND count( $joins ) )
		{
 			foreach( $joins as $join )
			{
				$select->join( $join['from'], ( $join['where'] ?? null ), ( $join['type'] ?? 'LEFT' ) );
			}
		}

		/* Return */
		return new ActiveRecordIterator( $select, get_called_class() );
	}

	/**
	 * Get randomization SQL query clause
	 *
	 * @param	string		$id			ID column to use
	 * @param	string		$title		Text column to use
	 * @return	string
	 */
	protected static function _getRandomizationSql( string $id, string $title ): string
	{
		return ", SUBSTR( MD5( CONCAT( {$id}, {$title} ) ), " . rand( 2, 25 ) . " ) as _rand";
	}
	
	/**
	 * Additional WHERE clauses for Follow view
	 *
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	array		$joins				Other joins
	 * @return	array
	 */
	public static function followWhere( bool &$joinContainer, array &$joins ): array
	{
		return array();
	}

	/**
	 * @brief	Allow the title to be editable via AJAX
	 */
	public bool $editableTitle	= TRUE;
	
	/**
	 * Get template for content tables
	 *
	 * @return	array
	 */
	public static function contentTableTemplate(): array
	{
		return array( Theme::i()->getTemplate( 'tables', 'core', 'front' ), 'rows' );
	}

	/**
	 * Get HTML for search result display snippet
	 *
	 * @return	array
	 */
	public static function manageFollowRows(): array
	{
		return array( Theme::i()->getTemplate( 'tables', 'core', 'front' ), 'manageFollowRow' );
	}
	
	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public static function getTableFilters(): array
	{
		$return = array();
		
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\ReadMarkers' ) )
		{
			$return[] = 'read';
			$return[] = 'unread';
		}
		
		$return = array_merge( $return, parent::getTableFilters() );
		
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Lockable' ) )
		{
			$return[] = 'locked';
		}
		
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Pinnable' ) )
		{
			$return[] = 'pinned';
		}
		
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Featurable' ) )
		{
			$return[] = 'featured';
		}
				
		return $return;
	}

	/**
	 * Get content table states
	 *
	 * @return string
	 */
	public function tableStates(): string
	{
		$return	= explode( ' ', parent::tableStates() );
		
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
		{
			if( $this->hidden() === -1 )
			{
				$return[]	= "hidden";
			}
			else if( $this->hidden() === 1 )
			{
				$return[]	= "unapproved";
			}
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\ReadMarkers' ) )
		{
			$return[]	= ( $this->unread() === -1 or $this->unread() === 1 ) ? "unread" : "read";
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\Pinnable' ) and $this->mapped('pinned') )
		{
			$return[]	= "pinned";
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\Featurable' ) and $this->mapped('featured') )
		{
			$return[]	= "featured";
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\Lockable' ) AND $this->locked() )
		{
			$return[]	= "locked";
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() )
		{
			$return[]	= "future";
		}
		
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Followable' ) AND $this->_followData )
		{
			$return[] = 'follow_freq_' . $this->_followData['follow_notify_freq'];
			$return[] = 'follow_privacy_' . intval( $this->_followData['follow_is_anon'] );
		}

		return implode( ' ', $return );
	}
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		$return = array( static::$databasePrefix . static::$databaseColumnId, static::$databasePrefix . static::$databaseColumnMap['title'], static::$databasePrefix . static::$databaseColumnMap['author'] );
		
		if ( isset( static::$databaseColumnMap['num_comments'] ) )
		{
			$return[] = static::$databasePrefix . static::$databaseColumnMap['num_comments'];
		}
		
		if ( isset( static::$databaseColumnMap['num_reviews'] ) )
		{
			$return[] = static::$databasePrefix . static::$databaseColumnMap['num_reviews'];
		}

		return $return;
	}
				
	/* !Comments & Reviews */
	
	/**
	 * Are comments supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsComments( Member $member = NULL, Model $container = NULL ): bool
	{		
		return isset( static::$commentClass );
	}
	
	/**
	 * Are reviews supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsReviews( Member $member = NULL, Model $container = NULL ): bool
	{
		return isset( static::$reviewClass );
	}

	/**
	 * @brief	[Content\Item]	Number of reviews to show per page
	 */
	public static int $reviewsPerPage = 25;

	/**
	 * @brief	Review Page count
	 * @see		reviewPageCount()
	 */
	protected int|null $reviewPageCount = NULL;

	/**
	 * @brief	Comment Page count
	 * @see		commentPageCount()
	 */
	protected int|null $commentPageCount = NULL;

	/**
	 * Get number of comments to show per page
	 *
	 * @return int
	 */
	public static function getCommentsPerPage(): int
	{
		return 25;
	}

	/**
	 * Get comment page count
	 *
	 * @param	bool		$recache		TRUE to recache the value
	 * @return	int
	 */
	public function commentPageCount( bool $recache=FALSE ): int
	{		
		if ( $this->commentPageCount === NULL or $recache )
		{
			$this->commentPageCount = ceil( $this->commentCount() / $this->getCommentsPerPage() );

			if( $this->commentPageCount < 1 )
			{
				$this->commentPageCount	= 1;
			}
		}
		return $this->commentPageCount;
	}
	
	/**
	 * Get comment count
	 *
	 * @return	int
	 */
	public function commentCount(): int
	{
		if( !isset( static::$commentClass ) )
		{
			return 0;
		}

		$count = $this->mapped('num_comments');

		if( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) AND $this->canViewHiddenComments() )
		{
			if ( isset( static::$databaseColumnMap['hidden_comments'] ) )
			{
				$count += $this->mapped('hidden_comments');
			}
			if ( isset( static::$databaseColumnMap['unapproved_comments'] ) )
			{
				$count += $this->mapped('unapproved_comments');
			}
		}
		elseif ( isset( static::$databaseColumnMap['unapproved_comments'] ) and Member::loggedIn()->member_id and $this->mapped('unapproved_comments') )
		{
			/* @var $databaseColumnMap array */
			$idColumn = static::$databaseColumnId;

			/* @var Comment $class */
			$class = static::$commentClass;
			$authorCol = $class::$databasePrefix . $class::$databaseColumnMap['author'];
			$where = array( array( $class::$databasePrefix . $class::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
			if ( isset( $class::$databaseColumnMap['approved'] ) )
			{
				$col = $class::$databasePrefix . $class::$databaseColumnMap['approved'];
				$where[] = array( "{$col}=0 AND {$authorCol}=" . Member::loggedIn()->member_id );
			}
			elseif( isset( $class::$databaseColumnMap['hidden'] ) )
			{
				$col = $class::$databasePrefix . $class::$databaseColumnMap['hidden'];
				$where[] = array( "{$col}=1 AND {$authorCol}=" . Member::loggedIn()->member_id );
			}
			$count += Db::i()->select( 'COUNT(*)', $class::$databaseTable, $where )->first();
		}

		return $count;
	}
	
	/**
	 * Get review page count
	 *
	 * @return	int
	 */
	public function reviewPageCount(): int
	{
		if ( $this->reviewPageCount === NULL )
		{
			$this->reviewPageCount = ceil( $this->reviewCount() / static::$reviewsPerPage );

			if( $this->reviewPageCount < 1 )
			{
				$this->reviewPageCount	= 1;
			}
		}
		return $this->reviewPageCount;
	}
	
	/**
	 * Get review count
	 *
	 * @return	int
	 */
	public function reviewCount(): int
	{
		if( !isset( static::$reviewClass ) )
		{
			return 0;
		}

		$count = $this->mapped('num_reviews');

		if( $this->canViewHiddenReviews() )
		{
			if ( isset( static::$databaseColumnMap['hidden_reviews'] ) )
			{
				$count += $this->mapped('hidden_reviews');
			}
			if ( isset( static::$databaseColumnMap['unapproved_reviews'] ) )
			{
				$count += $this->mapped('unapproved_reviews');
			}
		}
		elseif ( isset( static::$databaseColumnMap['unapproved_reviews'] ) and Member::loggedIn()->member_id and $this->mapped('unapproved_reviews') )
		{
			$idColumn = static::$databaseColumnId;
			$class = static::$reviewClass;
			/* @var $databaseColumnMap array */
			$authorCol = $class::$databasePrefix . $class::$databaseColumnMap['author'];
			$where = array( array( $class::$databasePrefix . $class::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
			if ( isset( $class::$databaseColumnMap['approved'] ) )
			{
				$col = $class::$databasePrefix . $class::$databaseColumnMap['approved'];
				$where[] = array( "{$col}=0 AND {$authorCol}=" . Member::loggedIn()->member_id );
			}
			elseif( isset( $class::$databaseColumnMap['hidden'] ) )
			{
				$col = $class::$databasePrefix . $class::$databaseColumnMap['hidden'];
				$where[] = array( "{$col}=1 AND {$authorCol}=" . Member::loggedIn()->member_id );
			}
			$count += Db::i()->select( 'COUNT(*)', $class::$databaseTable, $where )->first();
		}

		return $count;
	}
	
	/**
	 * Get comment pagination
	 *
	 * @param	array				$qs	Query string parameters to keep (for example sort options)
	 * @param	string				$template	Template to use
	 * @param	int|null			$pageCount	The number of pages, if known, or NULL to calculate automatically
	 * @param Url|NULL	$baseUrl	The base URL, if not the normal item url
	 * @return	string
	 */
	public function commentPagination( array $qs=array(), string $template='pagination', int|null $pageCount = NULL, Url|null $baseUrl = NULL ): string
	{
		return $this->_pagination( $qs, $pageCount ?: $this->commentPageCount(), $this->getCommentsPerPage(), $template, $baseUrl, 'comments' );
	}
	
	/**
	 * Get review pagination
	 *
	 * @param	array				$qs			Query string parameters to keep (for example sort options)
	 * @param	string				$template	Template to use
	 * @param	int|null			$pageCount	The number of pages, if known, or NULL to calculate automatically
	 * @param Url|NULL	$baseUrl	The base URL, if not the normal item url
	 * @return	string
	 */
	public function reviewPagination( array $qs=array(), string $template='pagination', int|null $pageCount = NULL, Url|null $baseUrl = NULL ): string
	{
		return $this->_pagination( $qs, $pageCount ?: $this->reviewPageCount(), static::$reviewsPerPage, $template, $baseUrl, 'reviews' );
	}
	
	/**
	 * Get comment/review pagination
	 *
	 * @param	array				$qs			Query string parameters to keep (for example sort options)
	 * @param	int					$count		Page count
	 * @param	int					$perPage	Number per page
	 * @param	string				$template	Name of the pagination template
	 * @param Url|NULL	$baseUrl	The base URL, if not the normal item url
	 * @param	string|null				$fragment	Query Parameter which can be applied to the url as anchor/fragment
	 * @return	string
	 */
	protected function _pagination( array $qs, int $count, int $perPage, string $template, Url|null $baseUrl = NULL, string|null $fragment = NULL ): string
	{
		$url = $baseUrl ?: $this->url();
		foreach ( $qs as $key )
		{
			if ( isset( Request::i()->$key ) )
			{
				$url = $url->setQueryString( $key, Request::i()->$key );
			}
		}

		if ( $fragment )
		{
			$url = $url->setFragment( $fragment );
		}

		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		return Theme::i()->getTemplate( 'global', 'core', 'global' )->$template( $url->setPage( 'page', $page ), $count, $page, $perPage );
	}

	/**
	 * Whether we're viewing the last page of reviews/comments on this item
	 *
	 * @param	string	$type		"reviews" or "comments"
	 * @return	bool
	 */
	public function isLastPage( string $type='comments' ): bool
	{
		/* If this class does not have any comments or reviews, return true */
		if ( !isset( static::$commentClass ) AND !isset( static::$reviewClass ) )
		{
			return TRUE;
		}
		
		$pageCount = ( $type == 'reviews' ) ? $this->reviewPageCount() : $this->commentPageCount();

		if( ( ( Request::i()->page && Request::i()->page == $pageCount ) || !isset( Request::i()->page ) && in_array( $pageCount, array( 0, 1 ) ) ) )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Whether we're viewing the first page of reviews/comments on this item
	 *
	 * @param	string	$type		"reviews" or "comments"
	 * @return	bool
	 */
	public function isFirstPage( string $type='comments' ): bool
	{
		/* If this class does not have any comments or reviews, return true */
		if ( !isset( static::$commentClass ) AND !isset( static::$reviewClass ) )
		{
			return TRUE;
		}

		if( ! isset( Request::i()->page ) or Request::i()->page == 1 )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Get comments
	 *
	 * @param int|null $limit The number to get (NULL to use static::getCommentsPerPage())
	 * @param int|null $offset The number to start at (NULL to examine \IPS\Request::i()->page)
	 * @param string $order The column to order by
	 * @param string $orderDirection "asc" or "desc"
	 * @param Member|null $member If specified, will only get comments by that member
	 * @param bool|null $includeHiddenComments Include hidden comments or not? NULL to base of currently logged in member's permissions
	 * @param DateTime|null $cutoff If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param mixed $extraWhereClause Additional where clause(s) (see \IPS\Db::build for details)
	 * @param bool|null $bypassCache Used in cases where comments may have already been loaded i.e. splitting comments on an item.
	 * @param bool $includeDeleted Include Deleted Comments
	 * @param bool|null $canViewWarn TRUE to include Warning information, NULL to determine automatically based on moderator permissions.
	 * @return    array|NULL|Comment    If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public function comments( ?int $limit=NULL, ?int $offset=NULL, string $order='date', string $orderDirection='asc', ?Member $member=NULL, ?bool $includeHiddenComments=NULL, ?DateTime $cutoff=NULL, mixed $extraWhereClause=NULL, bool $bypassCache=FALSE, bool $includeDeleted=FALSE, ?bool $canViewWarn=NULL ): array|NULL|Comment
	{		
		static $comments	= array();
		$idField			= static::$databaseColumnId;
		$_hash				= md5( $this->$idField . json_encode( func_get_args() ) );

		if( !$bypassCache and isset( $comments[ $_hash ] ) )
		{
			return $comments[ $_hash ];
		}

		$class = static::$commentClass;

		if ( !$class )
		{
			return NULL;
		}

		/* @var Comment $class */
		$comments[ $_hash ]	= $this->_comments( $class, $limit ?: $this->getCommentsPerPage(), $offset, ( isset( $class::$databaseColumnMap[ $order ] ) ? ( $class::$databasePrefix . $class::$databaseColumnMap[ $order ] ) : $order ) . ' ' . $orderDirection, $member, $includeHiddenComments, $cutoff, $canViewWarn, $extraWhereClause, $includeDeleted );
		return $comments[ $_hash ];
	}

	/**
	 * @brief	Cached review pulls
	 */
	protected array $cachedReviews	= array();

	/**
	 * Get reviews
	 *
	 * @param int|null $limit The number to get (NULL to use static::getCommentsPerPage())
	 * @param int|null $offset The number to start at (NULL to examine \IPS\Request::i()->page)
	 * @param string|null $order The column to order by (NULL to examine \IPS\Request::i()->sort)
	 * @param string $orderDirection "asc" or "desc" (NULL to examine \IPS\Request::i()->sort)
	 * @param Member|null $member If specified, will only get comments by that member
	 * @param bool|null $includeHiddenReviews
	 * @param DateTime|null $cutoff If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param mixed $extraWhereClause Additional where clause(s) (see \IPS\Db::build for details)
	 * @param bool|null $bypassCache
	 * @param bool $includeDeleted Include deleted content
	 * @param bool|null $canViewWarn TRUE to include Warning information, NULL to determine automatically based on moderator permissions.
	 * @return    array|NULL|Review    If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public function reviews( ?int $limit=NULL, ?int $offset=NULL, ?string $order='date', string $orderDirection='asc', ?Member $member=NULL, ?bool $includeHiddenReviews=NULL, ?DateTime $cutoff=NULL, mixed $extraWhereClause=NULL, bool $bypassCache=FALSE, bool $includeDeleted=FALSE, ?bool $canViewWarn=NULL ): array|NULL|Review
	{
		$cacheKey	= md5( json_encode( func_get_args() ) );

		if( isset( $this->cachedReviews[ $cacheKey ] ) )
		{
			return $this->cachedReviews[ $cacheKey ];
		}

		$class = static::$reviewClass;

		if ( !$class )
		{
			return NULL;
		}
	
		if ( $order === NULL )
		{
			/* @var $databaseColumnMap array */
			if ( isset( Request::i()->sort ) and Request::i()->sort === 'newest' )
			{
				$order = $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' DESC';
			}
			else
			{
				$order = "({$class::$databasePrefix}{$class::$databaseColumnMap['votes_helpful']}/{$class::$databasePrefix}{$class::$databaseColumnMap['votes_total']}) DESC, {$class::$databasePrefix}{$class::$databaseColumnMap['votes_helpful']} DESC, {$class::$databasePrefix}{$class::$databaseColumnMap['date']} DESC";
			}
		}
		else
		{
			$order = ( isset( $class::$databaseColumnMap[ $order ] ) ? ( $class::$databasePrefix . $class::$databaseColumnMap[ $order ] ) : $order ) .  ' ' . $orderDirection;
		}
		
		$this->cachedReviews[ $cacheKey ]	= $this->_comments( $class, $limit ?: static::$reviewsPerPage, $offset, $order, $member, $includeHiddenReviews, $cutoff, $canViewWarn, $extraWhereClause, $includeDeleted );
		return $this->cachedReviews[ $cacheKey ];
	}
	
	/**
	 * Get comments/reviews
	 *
	 * @param	string				$class 					The class
	 * @param	int|NULL			$limit					The number to get (NULL to use $perPage)
	 * @param	int|NULL			$offset					The number to start at (NULL to examine \IPS\Request::i()->page)
	 * @param	string				$order					The ORDER BY clause
	 * @param	Member|NULL	$member					If specified, will only get comments by that member
	 * @param	bool|NULL			$includeHidden			Include hidden comments or not? NULL to base of currently logged in member's permissions
	 * @param	DateTime|NULL	$cutoff					If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param	bool|NULL			$canViewWarn			TRUE to include Warning information, NULL to determine automatically based on moderator permissions.
	 * @param	mixed				$extraWhereClause		Additional where clause(s) (see \IPS\Db::build for details)
	 * @param	bool				$includeDeleted			Include Deleted Content
	 * @return	array|NULL|Comment    If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	protected function _comments( string $class, int|null $limit, int|null $offset=NULL, string $order='date DESC', Member|null $member=NULL, bool|null $includeHidden=NULL, DateTime|null $cutoff=NULL, bool|null $canViewWarn=NULL, mixed $extraWhereClause=NULL, bool $includeDeleted=FALSE ): array|NULL|Comment
	{
		/* Initial WHERE clause */
		/* @var Comment $class */
		$idColumn = static::$databaseColumnId;
		/* @var $databaseColumnMap array */
		$where = array( array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
		if ( $member !== NULL )
		{
			$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['author'] . '=?', $member->member_id );
		}
		if ( $cutoff !== NULL )
		{
			$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['date'] . '>?', $cutoff->getTimestamp() );
		}

		/* Exclude hidden comments? */
		$skipDeletedCheck = FALSE;

		if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
		{
			/* If $includeHidden is not a bool, work it out from the member's permissions */
			$includeHiddenByMember = FALSE;
			if ( $includeHidden === NULL )
			{
				/* The comment class supports hidden but does the item class? */
				if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
				{
					if ( isset( static::$commentClass ) and $class == static::$commentClass )
					{
						$includeHidden = $this->canViewHiddenComments();
					}
					else
					{
						if ( isset( static::$reviewClass ) and $class == static::$reviewClass )
						{
							$includeHidden = $this->canViewHiddenReviews();
						}
					}
				}
				else
				{
					/* No - so don't show hidden comments */
					$includeHidden = false;
				}

				$includeHiddenByMember = TRUE;
			}

			/* Does the item have any hidden comments? */
			if ( $includeHiddenByMember and isset( $class::$databaseColumnMap['unapproved_comments'] ) and ! $this->mapped('unapproved_comments') )
			{
				$includeHiddenByMember = FALSE;
			}

			/* If we can't view hidden comments, exclude them with the WHERE clause */
			if ( !$includeHidden )
			{
				/* @var $databaseColumnMap array */
				$authorCol = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['author'];
				if ( isset( $class::$databaseColumnMap['approved'] ) )
				{
					$col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['approved'];
					if ( $includeHiddenByMember and Member::loggedIn()->member_id )
					{
						$where[] = array( "({$col}=1 OR ( {$col}=0 AND {$authorCol}=" . Member::loggedIn()->member_id . '))' );
					}
					else
					{
						$where[] = array( "{$col}=1" );
					}
				}
				elseif( isset( $class::$databaseColumnMap['hidden'] ) )
				{
					$col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['hidden'];
					
					/* Possible values for this column are -2, -1, 0, 1, 2. We want to select 0 and 2. However, when we use "OR", this can force MySQL to stop using indexes correctly. This is true of forums, for example. Using AND allows the index to be used. */
					$hiddenWhereClause = "({$col} IN(0,2))";
					$skipDeletedCheck	= TRUE;
					
					if ( $includeHiddenByMember and Member::loggedIn()->member_id )
					{
						$where[] = array( "( {$hiddenWhereClause} OR ( {$col}=1 AND {$authorCol}=" . Member::loggedIn()->member_id . '))' );
					}
					else
					{
						
						$where[] = array( $hiddenWhereClause );
					}
				}
			}
		}

		if ( $includeDeleted === FALSE AND $skipDeletedCheck === FALSE )
		{
	        if ( isset( $class::$databaseColumnMap['hidden'] ) )
	        {
		        $col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['hidden'];
		        $where[] = array( "{$col}!=-2" );
	        }
	        else if ( isset( $class::$databaseColumnMap['approved'] ) )
	        {
		        $col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['approved'];
		        $where[] = array( "{$col}!=-2" );
	        }
	    }
		
		/* We do not want to show any PBR content at all */
		if( $skipDeletedCheck === FALSE )
		{
			if ( isset( $class::$databaseColumnMap['hidden'] ) )
			{
				$col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['hidden'];
				$where[] = array( "{$col}!=-3" );
			}
			else if ( isset( $class::$databaseColumnMap['approved'] ) )
			{
				$col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['approved'];
				$where[] = array( "{$col}!=-3" );
			}
		}

		/* Additional where clause */
		if( $extraWhereClause !== NULL )
		{
			if ( !is_array( $extraWhereClause ) or !is_array( $extraWhereClause[0] ) )
			{
				$extraWhereClause = array( $extraWhereClause );
			}
			$where = array_merge( $where, $extraWhereClause );
		}

		/* @var Content\Comment $class */

		/* Get the joins */
		$selectClause = $class::$databaseTable . '.*';		
		$joins = $class::joins( $this );
		if ( is_array( $joins ) )
		{
			foreach ( $joins as $join )
			{
				if ( isset( $join['select'] ) )
				{
					$selectClause .= ', ' . $join['select'];
				}
			}
		}

		/* Bad offset values can create an SQL error with a negative limit */
		$_pageValue = ( Request::i()->page ? intval( Request::i()->page ) : 1 );

		if( $_pageValue < 1 )
		{
			$_pageValue = 1;
		}

		/* If we have a cutoff with no offset explicitly defined, we should not automatically generate one for pagination since our results will be limited */
		$offset	= ( $cutoff and $offset === NULL ) ? 0 : ( $offset !== NULL ? $offset : ( ( $_pageValue - 1 ) * $limit ) );

		/* Construct the query */
		$results = array();
		$bits = Db::SELECT_MULTIDIMENSIONAL_JOINS;

		if( static::$useWriteServer === TRUE )
		{
			$bits += Db::SELECT_FROM_WRITE_SERVER;
		}

		$query = $class::db()->select( $selectClause, $class::$databaseTable, $where, $order, array( $offset, $limit ), NULL, NULL, $bits );
		if ( is_array( $joins ) )
		{
			foreach ( $joins as $join )
			{
				$query->join( $join['from'], $join['where'] );
			}
		}

		/* Get the results */
		$commentIdColumn = $class::$databaseColumnId;
		foreach ( $query as $row )
		{
			$result = $class::constructFromData( $row );
			if ( $limit === 1 )
			{
				return $result;
			}
			else
			{
				if ( IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) )
				{
					$result->reputation = array();
					$result->reactBlurb = array();
				}
				$results[ $result->$commentIdColumn ] = $result;
			}
		}
		
		/* Get the reputation stuff now so we don 't have to do lots of queries later */
		if ( Settings::i()->reputation_enabled AND IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) AND count( $results ) AND Dispatcher::hasInstance() )
		{
			/* Some basic init */
			$names				= array();
			$reactions			= array();
			$enabledReactions	= Content\Reaction::enabledReactions();

			/* Jump ahead if there are no enabled reactions */
			if( !count( $enabledReactions ) )
			{
				goto noReactions;
			}

			/* Work out the query */
			$reputationWhere	= array();
			$reputationWhere[]	= array( 'core_reputation_index.rep_class=? AND core_reputation_index.type=?', $class::reactionClass(), $class::reactionType() );
			$reputationWhere[]	= array( Db::i()->in( 'core_reputation_index.type_id', array_keys( $results ) ) );
			$reputationWhere[]	= array( Db::i()->in( 'core_reputation_index.reaction', array_keys( $enabledReactions ) ) );
			
			$select = Db::i()->select( 'core_reputation_index.type_id, core_reputation_index.member_id, core_reputation_index.reaction', 'core_reputation_index', $reputationWhere );
			
			/* Get the reputation data first */
			$reputationData	= array();
			$memberIds		= array();

			foreach ( $select as $reputation )
			{
				$reputationData[]	= $reputation;
				$memberIds[ $reputation['member_id'] ] = $reputation['member_id'];
			}

			/* Sanity check to make sure we have reputation data */
			if( !count( $reputationData ) )
			{
				goto noReactions;
			}

			/* Get the member data */
			$memberData = iterator_to_array( Db::i()->select( 'member_id, name, members_seo_name, member_group_id', 'core_members', array( Db::i()->in( 'member_id', $memberIds ) ) )->setKeyField('member_id') );

			/* Randomize the reactions */
			shuffle( $reputationData );

			/* Now loop over the reputation data and assign as appropriate */
			foreach ( $reputationData as $reputation )
			{
				if ( !isset( $memberData[ $reputation['member_id'] ] ) )
				{
					continue;
				}

				$results[ $reputation['type_id'] ]->reputation[ $reputation['member_id'] ] = $reputation['reaction'];

				if ( $reputation['member_id'] === Member::loggedIn()->member_id )
				{
					if( isset( $names[ $reputation['type_id'] ] ) )
					{
						array_unshift( $names[ $reputation['type_id'] ], '' );
					}
					else
					{
						$names[ $reputation['type_id'] ][0] = '';
					}
				}
				elseif ( !isset( $names[ $reputation['type_id'] ] ) or count( $names[ $reputation['type_id'] ] ) < 3 )
				{
					$names[ $reputation['type_id'] ][ $reputation['member_id'] ] = Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $reputation['member_id'], $memberData[ $reputation['member_id'] ]['name'], $memberData[ $reputation['member_id'] ]['members_seo_name'], $memberData[ $reputation['member_id'] ]['member_group_id'] );
				}
				elseif ( count( $names[ $reputation['type_id'] ] ) < 18 )
				{
					$names[ $reputation['type_id'] ][ $reputation['member_id'] ] = htmlspecialchars( $memberData[ $reputation['member_id'] ]['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
				}

				if ( !isset( $reactions[ $reputation['type_id'] ][ $reputation['reaction'] ] ) )
				{
					$reactions[ $reputation['type_id'] ][ $reputation['reaction'] ] = 0;
				}
				
				$reactions[ $reputation['type_id'] ][ $reputation['reaction'] ]++;
			}

			if ( count( $reactions ) )
			{
				/* Sort the reactions */
				foreach( array_keys( $reactions ) as $typeId )
				{
					/* Error suppressor for: https://bugs.php.net/bug.php?id=50688 */
					@uksort( $reactions[ $typeId ], function( $a, $b ) use ( $enabledReactions ) {
						$positionA = $enabledReactions[ $a ]->position;
						$positionB = $enabledReactions[ $b ]->position;
						
						if ( $positionA == $positionB )
						{
							return 0;
						}
						
						return ( $positionA < $positionB ) ? -1 : 1;
					} );
				}				
			}	

			noReactions:

			$commentOrReview = ( isset( static::$commentClass ) and $class == static::$commentClass ) ? 'Comment' : 'Review';

			/* If we need to display the "like blurb", compile that now */
			$langPrefix = 'react_';
			if ( Content\Reaction::isLikeMode() )
			{
				$langPrefix = 'like_';
			}
			foreach ( $names as $commentId => $people )
			{
				$i = 0;

				if ( isset( $people[0] ) )
				{						
					if ( count( $names[ $commentId ] ) === 1 )
					{
						$results[ $commentId ]->likeBlurb['reg'] = Member::loggedIn()->language()->addToStack( "{$langPrefix}blurb_just_you" );
						continue;
					}
					
					$people[0] = Member::loggedIn()->language()->addToStack("{$langPrefix}blurb_you_and_others");
				}
				
				$peopleToDisplayInMainView = array();
				$peopleToDisplayInSecondaryView = array();
				$numberOfLikes = count( $results[ $commentId ]->reputation );
				$andXOthers = $numberOfLikes;
				foreach ( $people as $id => $name )
				{
					if ( $i < 3 )
					{
						$peopleToDisplayInMainView[] = $name;
						$andXOthers--;
					}
					else
					{
						$peopleToDisplayInSecondaryView[] = strip_tags( $name );
					}
					$i++;
				}
				
				if ( $andXOthers )
				{
					if ( count( $peopleToDisplayInSecondaryView ) < $andXOthers )
					{
						$peopleToDisplayInSecondaryView[] = Member::loggedIn()->language()->addToStack( "{$langPrefix}blurb_others_secondary", FALSE, array( 'pluralize' => array( $andXOthers - count( $peopleToDisplayInSecondaryView ) ) ) );
					}
					$peopleToDisplayInMainView[] = Theme::i()->getTemplate( 'global', 'core', 'front' )->reputationOthers( $results[ $commentId ]->url( 'showReactions' ), Member::loggedIn()->language()->addToStack( "{$langPrefix}blurb_others", FALSE, array( 'pluralize' => array( $andXOthers ) ) ), json_encode( $peopleToDisplayInSecondaryView ) );
				}
				
				$results[ $commentId ]->likeBlurb['reg'] = Member::loggedIn()->language()->addToStack( "{$langPrefix}blurb", FALSE, array( 'pluralize' => array( $numberOfLikes ), 'htmlsprintf' => array( Member::loggedIn()->language()->formatList( $peopleToDisplayInMainView ) ) ) );
			}
			
			foreach( $reactions AS $commentId => $reaction )
			{
				$results[ $commentId ]->reactBlurb = $reaction;
			}
		}

		/* We don't need to fetch report data if there is no instance (i.e. generating a content/comment digest) */
		if( Dispatcher::hasInstance() )
		{
			$member = $member ?: Member::loggedIn();

			/* Do report stuff so we don't have to do lots of queries later */
			if ( IPS::classUsesTrait( $this, 'IPS\Content\Reportable' ) and ( $member->group['g_can_report'] == '1' OR in_array( $class, explode( ',', $member->group['g_can_report'] ) ) ) )
			{
				$reportIds = array();

				if( count( $results ) )
				{
					foreach ( Db::i()->select( 'id, content_id', 'core_rc_index', array( array( 'class=?', $class ), array( Db::i()->in( 'content_id', array_keys( $results ) ) ) ) ) as $report )
					{
						$reportIds[ $report['id'] ] = $report['content_id'];
					}
				}

				if ( count( $reportIds ) )
				{
					foreach ( Db::i()->select( '*', 'core_rc_reports', array( array( 'report_by=?', $member->member_id ), array( Db::i()->in( 'rid', array_keys( $reportIds ) ) ) ) ) as $detail )
					{
						$results[ $reportIds[ $detail['rid'] ] ]->reportData = $detail;
					}
				}

				/* Now populate the rest of the results */
				foreach ( $results as $id => $obj )
				{
					if ( !isset( $obj->reportData ) )
					{
						$results[ $id ]->reportData = array();
					}
				}
			}

			/* Get the warning stuff now so we don 't have to do lots of queries later */
			$canViewWarn = is_null( $canViewWarn ) ? Member::loggedIn()->modPermission( 'mod_see_warn' ) : $canViewWarn;
			if ( $canViewWarn and count( $results ) )
			{
				$module = static::$module;

				if ( isset( static::$commentClass ) and $class == static::$commentClass )
				{
					$module .= '-comment';
				}
				if ( isset( static::$reviewClass ) and $class == static::$reviewClass )
				{
					$module .= '-review';
				}

				$where = array( array( 'wl_content_app=? AND wl_content_module=? AND wl_content_id1=?', static::$application, $module, $this->$idColumn ) );
				$where[] = array( Db::i()->in( 'wl_content_id2', array_keys( $results ) ) );

				foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_members_warn_logs', $where ), 'IPS\core\Warnings\Warning' ) as $warning )
				{
					$results[ $warning->content_id2 ]->warning = $warning;
				}
			}
		}

		/* Solved count */
		$commentClass = static::$commentClass;
		if ( $commentClass AND IPS::classUsesTrait( $this, Solvable::class ) )
		{
			$memberIds = array();
			$solvedCounts = array();
			$authorField = $commentClass::$databaseColumnMap['author'];

			foreach( $results as $id => $data )
			{
				$memberIds[ $data->$authorField ] = $data->$authorField;
			}

			if ( count( $memberIds ) )
			{
				$where=[];
				$where[] = [ Db::i()->in( 'member_id', $memberIds ) ];
				$where[] = [ 'type=?', 'solved' ];
				foreach( Db::i()->select( 'COUNT(*) as count, member_id', 'core_solved_index', $where, NULL, NULL, 'member_id' ) as $member )
				{
					$solvedCounts[ $member['member_id'] ] = $member['count'];
				}

				foreach( $results as $id => $data )
				{
					if ( isset( $solvedCounts[ $data->$authorField ] ) )
					{
						$results[ $id ]->author_solved_count = $solvedCounts[ $data->$authorField ];
					}
				}
			}

			/* Now get the helpful counts */
			if ( IPS::classUsesTrait( $this, 'IPS\Content\Helpful' ) )
			{
				$helpfulCounts = [];
				foreach( Db::i()->select( '*', 'core_solved_index', array( array( "app=? AND item_id=? AND type=? AND hidden=0", static::$application, $this->$idColumn, 'helpful' ) ) ) as $helpful )
				{
					if ( ! isset( $helpfulCounts[ $helpful['comment_id'] ] ) )
					{
						$helpfulCounts[ $helpful['comment_id'] ] = [ 'count' => 0, 'marked_by' => [] ];
					}
					$helpfulCounts[ $helpful['comment_id'] ]['marked_by'][] = $helpful['member_given'];
					$helpfulCounts[ $helpful['comment_id'] ]['count'] += 1;
				}

				foreach( $results as $id => $data )
				{
					$results[ $id ]->helpful = [ 'count' => 0, 'marked_by' => [] ];

					if ( isset( $helpfulCounts[ $id ] ) )
					{
						$results[ $id ]->helpful = $helpfulCounts[ $id ];
					}
				}
			}
		}

		/* Recognized content */
		if ( $commentClass AND IPS::classUsesTrait( $commentClass, 'IPS\Content\Recognizable' ) )
		{
			foreach( Db::i()->select( '*', 'core_member_recognize', [ [ 'r_content_class=?', $commentClass ], [ Db::i()->in('r_content_id', array_keys( $results ) ) ] ] ) as $row )
			{
				if ( isset( $results[ $row['r_content_id'] ] ) )
				{
					$results[ $row['r_content_id'] ]->recognized = Recognize::constructFromData( $row );
				}
			}

			/* Set the property for any unrecognized content to prevent additional queries later */
			foreach( $results as $k => $v )
			{
				if( !isset( $v->recognized ) )
				{
					$results[ $k ]->recognized = false;
				}
			}
		}

		/* Return */
		return ( $limit === 1 ) ? NULL : $results;
	}
		
	/**
	 * @brief	Comment form output cached
	 */
	protected string|null $_commentFormHtml	= NULL;
	
	/**
	 * If, when making a post, we should merge with an existing comment, this method returns the comment to merge with
	 *
	 * @return    Comment|NULL
	 */
	public function mergeConcurrentComment(): Comment|NULL
	{
		if ( Member::loggedIn()->member_id and Settings::i()->merge_concurrent_posts and $this->lastCommenter()->member_id == Member::loggedIn()->member_id and !Member::loggedIn()->moderateNewContent() )
		{
			$lastComment = $this->comments( 1, 0, 'date', 'desc', NULL, TRUE );

			if ( $lastComment !== NULL and $lastComment->mapped('date') > DateTime::create()->sub( new DateInterval( 'PT' . Settings::i()->merge_concurrent_posts . 'M' ) )->getTimestamp() AND $lastComment->mapped('author') == Member::loggedIn()->member_id AND !$lastComment->hidden() )
			{
				return $lastComment;
			}
		}
		return NULL;
	}
	
	/**
	 * When making a reply, the javascript handler will post the reply form if the ajax post fails. We want to ensure that we're not creating a duplicate post.
	 * We will consider a post to be duplicate if the author matches, the content matches and it is within a 2 minute window and the last comment is not hidden
	 *
	 * @param	string		$comment	Comment content as returned from the editor
	 * @return    Comment|false
	 */
	public function isDuplicateComment( string $comment ): Comment|bool
	{
		if ( isset( Request::i()->failedReply ) )
		{
			if ( Member::loggedIn()->member_id )
			{
				/* It is possible that even though this is a duplicate post, it is not the last reply in this item, so let us just get the latest reply by this member */
				$lastComment = $this->comments( 1, 0, 'date', 'desc', Member::loggedIn() );
	
				if ( $lastComment !== NULL and $lastComment->mapped('date') > DateTime::create()->sub( new DateInterval( 'PT2M' ) )->getTimestamp() AND !$lastComment->hidden() )
				{
					if ( $lastComment->mapped('content') == $comment )
					{
						return $lastComment;
					}
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * @brief	Check posts per day limits? Useful for things that use the content system, but aren't necessarily content themselves.
	 */
	public static bool $checkPostsPerDay = TRUE;
	
	/**
	 * Return the comment form object
	 *
	 * @return	Form
	 */
	protected function _commentForm(): Form
	{
		$idColumn			= static::$databaseColumnId;
		$form				= new Form( 'commentform' . '_' . $this->$idColumn, static::$formLangPrefix . 'submit_comment' );
		$form->class		= 'ipsForm--vertical ipsForm--comment-form';
		$form->hiddenValues['_contentReply']	= TRUE;

		return $form;
	}

	/**
	 * Build comment form
	 *
	 * @param int|null $lastSeenId Last ID seen (point to start from for new comment polling)
	 * @return string|NULL
	 */
	public function commentForm( ?int $lastSeenId = NULL ): string|NULL
	{
		/* Have we built it already? */
		if( $this->_commentFormHtml !== NULL )
		{
			return $this->_commentFormHtml;
		}

		/* Can we comment? */
		if ( $this->canComment() )
		{
			/* @var Comment $commentClass */
			$commentClass = static::$commentClass;
			$idColumn = static::$databaseColumnId;
			$commentIdColumn = $commentClass::$databaseColumnId;
			/* @var $databaseColumnMap array */
			$commentDateColumn = $commentClass::$databaseColumnMap['date'];
			
			$form	= $this->_commentForm();

			$elements = $this->commentFormElements();
			
			foreach( $elements as $element )
			{
				$form->add( $element );
			}
						
			if ( $values = $form->values() )
			{
				/* Disable read/write separation */
				Db::i()->readWriteSeparation = FALSE;
				
				$newCommentContent = $values[ static::$formLangPrefix . 'comment' . '_' . $this->$idColumn ];
				
				/* Is this a duplicate comment? */
				if ( $duplicateComment = $this->isDuplicateComment( $newCommentContent ) )
				{
					/* Log it */
					Log::debug( "Member ID:" . Member::loggedIn()->member_id . "\nContent: " . mb_substr( $newCommentContent, 0, 1000 ), "duplicate_comment" );
					
					/* And redirect them */
					Output::i()->redirect( $this->lastCommentPageUrl()->setFragment( 'comment-' . $duplicateComment->$commentIdColumn ) );
				}
				
				/* Check Post Per Day Limits */
				if ( Member::loggedIn()->member_id AND static::$checkPostsPerDay === TRUE AND Member::loggedIn()->checkPostsPerDay() === FALSE )
				{
					if ( Request::i()->isAjax() )
					{
						Output::i()->json( array( 'type' => 'error', 'message' => Member::loggedIn()->language()->addToStack( 'posts_per_day_error' ) ) );
					}
					else
					{
						Output::i()->error( 'posts_per_day_error', '2S177/2', 403, '' );
					}
				}

				/* Check for banned IP - The banned ip addresses are only checked inside the register and login controller, so people are able to bypass them when PBR is used */
				if( !Member::loggedIn()->member_id AND Request::i()->ipAddressIsBanned() )
				{
					Output::i()->showBanned();

				}
				
				$currentPageCount = Request::i()->currentPage;
				
				/* Merge? */
				if ( $lastComment = $this->mergeConcurrentComment() AND ( !isset( $values['hide'] ) OR !$values['hide'] ) )
				{
					/* Determine if the post is hidden to start with */
					$isHidden	= $lastComment->hidden();

					$valueField = $lastComment::$databaseColumnMap['content'];		
					$newContent = $lastComment->$valueField . $newCommentContent;				
					$lastComment->editContents( $newContent );

					$parameters = array_merge( array( 'reply-' . static::$application . '/' . static::$module  . '-' . $this->$idColumn ), $lastComment->attachmentIds() );
					File::claimAttachments( ...$parameters );
					
					if ( Request::i()->isAjax() )
					{
						$newPageCount = $this->commentPageCount( true );
						/* We will do a redirect if either the page number changes or if the post was not hidden but is now */
						if ( $currentPageCount != $newPageCount OR $isHidden != $lastComment->hidden() )
						{
							Output::i()->json( array( 'type' => 'redirect', 'page' => $newPageCount, 'total' => $this->mapped('num_comments'), 'content' => $lastComment->html(), 'url' => (string) $lastComment->url('find') ) );
						}
						else
						{
							Output::i()->json( array( 'type' => 'merge', 'id' => $lastComment->$commentIdColumn, 'page' => $newPageCount, 'total' => $this->mapped('num_comments'), 'content' => $newContent ) );
						}
					}
					else
					{
						Output::i()->redirect( $this->lastCommentPageUrl()->setFragment( 'comment-' . $lastComment->$commentIdColumn ) );
					}
				}
				
				/* Or post? */
				$comment = $this->processCommentForm( $values );
				unset( $this->commentPageCount );

				$newPageCount = $this->commentPageCount( true );
				
				if ( IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() === -3 )
				{
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register', 'front', 'register' ) );
				}
				elseif( IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() AND !Member::loggedIn()->member_id )
				{
					Output::i()->json( array( 'type' => 'add', 'id' => $comment->$commentIdColumn, 'page' => $newPageCount, 'total' => $this->mapped('num_comments'), 'content' => '', 'message' => Member::loggedIn()->language()->addToStack( 'mod_queue_message' ) ) );
				}
				elseif ( Request::i()->isAjax() )
				{
					if ( IPS::classUsesTrait( $this, 'IPS\Content\ReadMarkers' ) )
					{
						$this->markRead( NULL, NULL, NULL, TRUE );
					}

					if ( $currentPageCount != $newPageCount )
					{
						Output::i()->json( array( 'type' => 'redirect', 'page' => $newPageCount, 'total' => $this->mapped('num_comments'), 'content' => $comment->html(), 'url' => (string) $comment->url('find') ) );
					}
					else
					{
						$output = '';
						/* This comes from a form field and has an underscore, see the form definition above */
						if ( isset( Request::i()->_lastSeenID ) and intval( Request::i()->_lastSeenID ) )
						{
							try
							{
								$lastComment = $commentClass::load( Request::i()->_lastSeenID );
								foreach ( $this->comments( NULL, 0, 'date', 'asc', NULL, NULL, DateTime::ts( $lastComment->$commentDateColumn ) ) as $newComment )
								{
									if ( $newComment->$commentIdColumn != $comment->$commentIdColumn )
									{
										$output .= $newComment->html();
									}
								}
							}
							catch ( OutOfRangeException $e) {}

						}
						$output .= $comment->html();
						
						$message = '';
						if ( IPS::classUsesTrait( $comment, '\\IPS\\Content\\Hideable' ) AND $comment->hidden() == 1 )
						{
							$message = Member::loggedIn()->language()->addToStack( 'mod_queue_message' );
						}

						/* Data Layer stuff for comments here, not in Comment class. We track user actions, and the user action is handled here */
						$dataLayer = Settings::i()->core_datalayer_enabled ? $this->getDataLayerProperties( $comment ) : array();

						Output::i()->json(
							array(
								'type' => 'add',
								'id' => $comment->$commentIdColumn,
								'page' => $newPageCount,
								'total' => $this->mapped('num_comments'),
								'content' => $output,
								'message' => $message,
								'postedByLoggedInMember' => true,
								/* This is used on the front end */
								"dataLayer" => $dataLayer,
							)
						);
					}
				}
				else
				{
					Output::i()->redirect( $this->lastCommentPageUrl()->setFragment( 'comment-' . $comment->$commentIdColumn ) );
				}
			}
			elseif ( Request::i()->isAjax() )
			{
				$hasError = FALSE;
				foreach ( $elements as $input )
				{
					if ( $input->error )
					{
						$hasError = $input->error;
					}
				}
				if ( $hasError )
				{
					/* @var $formTemplate array */
					Output::i()->json( array( 'type' => 'error', 'message' => Member::loggedIn()->language()->addToStack( $hasError ), 'form' => $form->customTemplate( array( Theme::i()->getTemplate( $commentClass::$formTemplate[0][0], $commentClass::$formTemplate[0][1], $commentClass::$formTemplate[0][2] ), $commentClass::$formTemplate[1] ) ) ) );
				}
			}
			
			/* Mod Queue? */
			$return = '';
			$guestPostBeforeRegister = ( !Member::loggedIn()->member_id ) ? ( !isset( static::$containerNodeClass ) or ( $container = $this->container() and !$container->can( 'reply', Member::loggedIn(), FALSE ) ) ) : FALSE;

			$modQueued = false;
			if( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
			{
				$modQueued = static::moderateNewComments( Member::loggedIn(), $guestPostBeforeRegister );
			}

			if ( $guestPostBeforeRegister or $modQueued )
			{
				$return .= Theme::i()->getTemplate( 'forms', 'core' )->postingInformation( $guestPostBeforeRegister, $modQueued );
			}

			/* @var $formTemplate array */
			$this->_commentFormHtml	= $return . $form->customTemplate( array( Theme::i()->getTemplate( $commentClass::$formTemplate[0][0], $commentClass::$formTemplate[0][1], $commentClass::$formTemplate[0][2] ), $commentClass::$formTemplate[1] ) );
			return $this->_commentFormHtml;
		}
		/* Show an explanation why comments are disabled for future items */
		else if ( Member::loggedIn()->member_id AND IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() )
		{
			return $this->_commentFormHtml	= Member::loggedIn()->language()->addToStack( 'comments_disabled_future_item', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( static::$title . '_pl_lc' ) ) ) );
		}

		/* Hang on, are we a guest, but if logged in, could comment? */
		if ( !Member::loggedIn()->member_id )
		{
			$testUser = new Member;
			$testUser->member_group_id = Settings::i()->member_group;
			if ( $this->canComment( $testUser ) )
			{
				$this->_commentFormHtml	= $this->guestTeaser();
				return $this->_commentFormHtml;
			}
		}
		
		/* Nope, just display nothing */
		$this->_commentFormHtml	= '';

		return $this->_commentFormHtml;
	}

	protected array $_dataLayerProperties = array();

	/**
	 * Most, if not all of these are the same for different events, so we can just have one method
	 *
	 * @param Comment|null $comment A comment item, leave null for these keys to be omitted
	 *
	 * @return  array
	 */
	public function getDataLayerProperties( ?Comment $comment = null ): array
	{
		$commentIdColumn = $comment ? $comment::$databaseColumnId : null;
		$index = $commentIdColumn ? ( $comment->$commentIdColumn ?: 0 ) : 0;

		if ( !isset( $this->_dataLayerProperties[$index] ) )
		{
			$app      = static::$application ?? null;
			$idColumn = static::$databaseColumnId;
			$dataLayer = array(
				'author_id'     => DataLayer::i()->getSsoId( $this->author()->member_id ),
				'author_name'   => $this->author()->real_name ?: null,
				'content_age'   => $this->mapped( 'date' ) ? intval( floor( ( time() - $this->mapped( 'date' ) ) / 86400 ) ) : null,
				'content_container_id'   => null,
				'content_container_name' => null,
				'content_container_type' => null,
				'content_container_url'  => null,
				'content_id'    => $this->$idColumn,
				'content_title' => $this->mapped( 'title' ),
				'content_type'  => static::$contentType ?? null,
				'content_url'   => (string) $this->url(),
			);

			if ( $comment AND ( $commentIdColumn = $comment::$databaseColumnId ?? null ) )
			{
				$dataLayer = array_replace( $dataLayer, array(
					'comment_id'    => $comment->$commentIdColumn,
					'comment_type'  => $comment::$commentType ?? null,
					'comment_url'   => (string) $comment->url()
				) );
			}

			/* For QA forums, the comment_type and content_type is an exception */
			if ( Settings::i()->core_datalayer_distinguish_qa AND
			     $this instanceof Topic AND
			     $this->container()->_forum_type === 'qa' )
			{
				$dataLayer['content_type'] = 'question';
				if ( isset( $comment ) )
				{
					$dataLayer['comment_type'] = 'answer';
				}
			}
			/* If we didn't find a Comment or Content type, try pulling that info from the static title fields */
			elseif ( ( !isset( $dataLayer['content_type'] ) AND isset( static::$title ) ) OR ( !isset( $dataLayer['comment_type'] ) AND isset( $comment, $comment::$title ) ) )
			{
				$contentType = ( $app ? preg_replace( "/[{$app}_]?(.*)/", '$1', static::$title ?? "" ) : ( static::$title ?? null ) ) ?: null;
				if ( $contentType AND !isset( $dataLayer['content_type'] ) )
				{
					$dataLayer['content_type'] = $contentType;
				}

				if ( $comment AND isset( $comment::$title ) AND !isset( $dataLayer['comment_type'] ) )
				{
					$commentType = ($app or $contentType) ? preg_replace( "/" . ( $app ? "[{$app}_]?" : '' ) . ( $contentType ? "[{$contentType}_]?" : '' ) . "(.*)/", "$1", $comment::$title ) : $comment::$title;
					$dataLayer['comment_type'] = $commentType;
				}
			}

			/* Use either comment or review if there still is no comment type (note this should never happen as of IPS v4.6 unless 3rd party things are going on) */
			if ( $comment AND !isset( $dataLayer['comment_type'] ) )
			{
				$dataLayer['comment_type'] = $comment instanceof Review ? 'review' : 'comment';
			}

			try
			{
				$container = $this->container();
				$dataLayer = array_replace( $dataLayer, $container->getDataLayerProperties() );
			}
			catch ( OutOfRangeException | BadMethodCallException $e ) {}

			if ( !isset( $dataLayer['content_area'] ) AND $app )
			{
				$lang = Lang::load( Lang::defaultLanguage() );
				if ( $lang->checkKeyExists( '__app_' . static::$application ) )
				{
					$dataLayer['content_area'] = $lang->addToStack( '__app_' . static::$application );
				}
			}

			$this->_dataLayerProperties[$index] = DataLayer::i()->filterProperties( $dataLayer );
		}

		return $this->_dataLayerProperties[$index];
	}

	/**
	 * Get the feed id to put in comment and comment feed controllers
	 *
	 * @return string
	 */
	public function get_feedId() : string
	{
		static $feedId = null;
		if ( $feedId === null )
		{
			$idCol = static::$databaseColumnId;
			$itemKey = static::$contentType ?? strtolower( preg_replace( "/[^a-z0-9]+/i", '_', static::class ) );
			$nodeTitle = $this->containerWrapper() ? ( $this->containerWrapper() )::$nodeTitle : "";

			$feedId = ( $nodeTitle ? $nodeTitle . '-' : '' ) . $itemKey . '-' . $this->$idCol;
		}

		return $feedId;
	}


	/**
	 * Get the feed id to put in commment and comment feed controllers
	 *
	 * @return string
	 */
	public function get_reviewFeedId() : string
	{
		return $this->feedId . "-reviews";
	}

	/**
	 * Whether the comments of this item should use the comment editor. Forum Topics override this to respect the global setting
	 *
	 * @return bool
	 */
	public function commentsUseCommentEditor() : bool
	{
		return true;
	}

	/**
	 * Add the comment form elements
	 *
	 * @return	array
	 */
	public function commentFormElements(): array
	{
		$commentClass = static::$commentClass;
		$idColumn = static::$databaseColumnId;
		$return   = array();
		$submitted = 'commentform' . '_' . $this->$idColumn . '_submitted';
		
		$self = $this;
		$editorField = new Editor( static::$formLangPrefix . 'comment' . '_' . $this->$idColumn, NULL, TRUE, array(
			'app'			=> static::$application,
			'key'			=> IPS::mb_ucfirst( static::$module ),
			'autoSaveKey' 	=> 'reply-' . static::$application . '/' . static::$module . '-' . $this->$idColumn,
			'minimize'		=> isset( Request::i()->$submitted ) ? NULL : static::$formLangPrefix . '_comment_placeholder',
			'contentClass'	=> get_called_class(),
			'contentId'		=> $this->$idColumn,
			'comments' => $this->commentsUseCommentEditor(),
		), function() use( $self ) {
			if ( !$self->mergeConcurrentComment() )
			{
				Form::floodCheck();
			}
		} );
		$return['editor'] = $editorField;

		if ( !Member::loggedIn()->member_id )
		{
			if ( !$this->canComment( Member::loggedIn(), FALSE ) )
			{
				$return['guest_email'] = new Email( 'guest_email', NULL, TRUE, array( 'accountEmail' => TRUE, 'placeholder' => Member::loggedIn()->language()->addToStack('comment_guest_email'), 'htmlAutocomplete' => "email" ) );
			}
			else
			{
				if ( isset( $commentClass::$databaseColumnMap['author_name'] ) )
				{
					$return['guest_name'] = new Text( 'guest_name', NULL, FALSE, array( 'minLength' => Settings::i()->min_user_name_length, 'maxLength' => Settings::i()->max_user_name_length, 'placeholder' => Member::loggedIn()->language()->addToStack('comment_guest_name') ) );
				}
			}
			if ( Settings::i()->bot_antispam_type !== 'none' and Settings::i()->guest_captcha )
			{
				$return['captcha'] = new Captcha;
			}
		}
		
		$followArea = mb_strtolower( mb_substr( get_called_class(), mb_strrpos( get_called_class(), '\\' ) + 1 ) );
	
		/* Add in the "automatically follow" option */
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Followable' ) and Member::loggedIn()->member_id )
		{
			$return['follow'] = new YesNo( static::$formLangPrefix . 'auto_follow', ( Member::loggedIn()->auto_follow['comments'] or Member::loggedIn()->following( static::$application, $followArea, $this->$idColumn ) ), FALSE, array( 'label' => static::$formLangPrefix . 'auto_follow_suffix' ), NULL, NULL, NULL, 'auto_follow_toggle' );
		}

		$container = $this->containerWrapper();
		$member = Member::loggedIn();

		if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Hideable' ) and ( static::modPermission( 'hide', $member, $container ) OR $member->group['g_hide_own_posts'] == '1'  ) )
		{
			$return['hide'] = new YesNo( 'hide', FALSE , FALSE, array( 'label' => 'hide' ) );
		}

		/* Post Anonymously */
		if ( $container and $container->canPostAnonymously( $container::ANON_COMMENTS ) )
		{
			$return['post_anonymously']	= new YesNo( 'post_anonymously', FALSE, FALSE, array( 'label' => Member::loggedIn()->language()->addToStack( 'post_anonymously_suffix' ) ), NULL, NULL, NULL, 'post_anonymously' );
		}

        /* Anything else? */
		if( $extraFields = Bridge::i()->commentFormFields( $this ) )
		{
			foreach( $extraFields as $key => $element )
			{
				$return[$key] = $element;
			}
		}

		foreach( UiExtension::i()->run( $commentClass, 'formElements', array( $this ) ) as $key => $element )
		{
            $return[$key] = $element;
		}

		return $return;
	}
	
	/**
	 * Process the comment form
	 *
	 * @param	array	$values		Array of $form values
	 * @return  Comment
	 */
	public function processCommentForm( array $values ): Comment
	{
		/* @var Comment $commentClass */
		$commentClass = static::$commentClass;
		$idColumn = static::$databaseColumnId;
		$commentIdColumn = $commentClass::$databaseColumnId;
		$followArea = mb_strtolower( mb_substr( get_called_class(), mb_strrpos( get_called_class(), '\\' ) + 1 ) );

		/* Moderator wants to hide the comment */
		if( isset( $values['hide'] ) AND $values['hide'] )
		{
			$hidden = -1;
		}
		else
		{
			$hidden = NULL;
		}

		$comment = $commentClass::create( $this, $values[ static::$formLangPrefix . 'comment' . '_' . $this->$idColumn ], FALSE, $values['guest_name'] ?? NULL, NULL, NULL, NULL, NULL, $hidden, ( isset( $values[ 'post_anonymously' ] ) ? (bool) $values[ 'post_anonymously' ] : NULL ) );

        Event::fire( 'onCreateOrEdit', $comment, array( $values, TRUE ) );

		/* Auto-follow - If posted anonymously we should set follow to anonymous as well */
		if( isset( $values[ static::$formLangPrefix . 'auto_follow' ] ) )
		{
			if ( $values[ static::$formLangPrefix . 'auto_follow' ] and !Member::loggedIn()->following( static::$application, $followArea, $this->$idColumn ) )
			{
				/* Insert */
				$save = array(
					'follow_id'				=> md5( static::$application . ';' . $followArea . ';' . $this->$idColumn . ';' .  Member::loggedIn()->member_id ),
					'follow_app'			=> static::$application,
					'follow_area'			=> $followArea,
					'follow_rel_id'			=> $this->$idColumn,
					'follow_member_id'		=> Member::loggedIn()->member_id,
					'follow_is_anon'		=> isset( $values[ 'post_anonymously' ] ) ? (bool) $values[ 'post_anonymously' ] : 0,
					'follow_added'			=> time() + 1, // Make sure streams show follows after content is created
					'follow_notify_do'		=> 1,
					'follow_notify_meta'	=> '',
					'follow_notify_freq'	=> Member::loggedIn()->auto_follow['method'],
					'follow_notify_sent'	=> 0,
					'follow_visible'		=> 1
				);
			
				Db::i()->insert( 'core_follow', $save );
				Db::i()->delete( 'core_follow_count_cache', array( 'class=? AND id=?', get_called_class(), (int) $this->$idColumn ) );
			}
			else if ( $values[ static::$formLangPrefix . 'auto_follow' ] === false AND Member::loggedIn()->following( static::$application, $followArea, $this->$idColumn ) )
			{
				Db::i()->delete( 'core_follow', array( 'follow_id=?', md5( static::$application . ';' . $followArea . ';' . $this->$idColumn . ';' . Member::loggedIn()->member_id ) ) );
				Db::i()->delete( 'core_follow_count_cache', array( 'id=? AND class=?', $this->$idColumn, get_called_class() ) );
			}
		}

		/* Update the search index (note: we already index the comment in Comment::create()) */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			if ( static::$firstCommentRequired and !$comment->isFirst() )
			{
				/* The first comment might have been pruned, so check the time frame and reindex if necessary */
				if( Settings::i()->search_method == 'mysql' and Settings::i()->search_index_timeframe )
				{
					$cutoff = DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->search_index_timeframe . 'D' ) )->getTimestamp();
					if( $this->mapped( 'date' ) <= $cutoff )
					{
						Index::i()->index( $this->firstComment() );
					}
				}
			}
		}
				
		/* Post before registering */
		if ( isset( $values['guest_email'] ) )
		{
			Request::i()->setCookie( 'post_before_register', $comment->_logPostBeforeRegistering( $values['guest_email'], isset( Request::i()->cookie['post_before_register'] ) ? Request::i()->cookie['post_before_register'] : NULL ) );
		}

		$comment->ui( 'formPostSave', array( $values ) );

		return $comment;
	}
	
	/**
	 * Build review form
	 *
	 * @return	string
	 */
	public function reviewForm(): string
	{
		/* Can we review? */
		if ( $this->canReview() )
		{
			$reviewClass = static::$reviewClass;
			$idColumn = static::$databaseColumnId;
			$reviewIdColumn = static::$databaseColumnId;
			
			$form = new Form( 'review', 'add_review' );
			$form->class  = 'ipsForm--vertical ipsForm--add-review';
			
			if ( !Member::loggedIn()->member_id )
			{
				if ( !$this->canReview( Member::loggedIn(), FALSE ) )
				{
					$form->add( new Email( 'guest_email', NULL, TRUE, array( 'accountEmail' => TRUE, 'htmlAutocomplete' => "email" ) ) );
				}
				else
				{
					if ( isset( $reviewClass::$databaseColumnMap['author_name'] ) )
					{
						$form->add( new Text( 'guest_name', NULL, FALSE, array( 'minLength' => Settings::i()->min_user_name_length, 'maxLength' => Settings::i()->max_user_name_length, 'placeholder' => Member::loggedIn()->language()->addToStack('comment_guest_name') ) ) );
					}
				}
				if ( Settings::i()->bot_antispam_type !== 'none' and Settings::i()->guest_captcha )
				{
					$form->add( new Captcha );
				}
			}
			
			$form->add( new Rating( static::$formLangPrefix . 'rating_value', NULL, TRUE, array( 'max' => Settings::i()->reviews_rating_out_of ) ) );
			$editorField = new Editor( static::$formLangPrefix . 'review_text', NULL, TRUE, array(
				'app'			=> static::$application,
				'key'			=> IPS::mb_ucfirst( static::$module ),
				'autoSaveKey' 	=> 'review-' . static::$application . '/' . static::$module . '-' . $this->$idColumn,
				'minimize'		=> static::$formLangPrefix . '_review_placeholder'
			), '\IPS\Helpers\Form::floodCheck' );
			$form->add( $editorField );

			foreach( UiExtension::i()->run( $reviewClass, 'formElements', array( $this ) ) as $element )
			{
				$form->add( $element );
			}
			
			if ( $values = $form->values() )
			{
				/* Disable read/write separation */
				Db::i()->readWriteSeparation = FALSE;
			
				$currentPageCount = Request::i()->currentPage;
				
				unset( $this->reviewpageCount );
								
				$review = $this->processReviewForm( $values );
				
				if ( $review->hidden() === -3 )
				{
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register', 'front', 'register' ) );
				}
				else
				{
					Output::i()->redirect( $review->url(), 'thanks_for_your_review' );
				}
			}
			elseif ( Request::i()->isAjax() and $editorField->error )
			{
				Output::i()->json( array( 'type' => 'error', 'message' => Member::loggedIn()->language()->addToStack( $editorField->error ) ) );
			}

			/* Mod Queue? */
			$return = '';
			$guestPostBeforeRegister = ( !Member::loggedIn()->member_id ) ? ( !isset( static::$containerNodeClass ) or ( $container = $this->container() and !$container->can( 'reply', Member::loggedIn(), FALSE ) ) ) : FALSE;
			$modQueued = static::moderateNewReviews( Member::loggedIn(), $guestPostBeforeRegister );
			if ( $guestPostBeforeRegister or $modQueued )
			{
				$return .= Theme::i()->getTemplate( 'forms', 'core' )->postingInformation( $guestPostBeforeRegister, $modQueued );
			}
			/* @var $formTemplate array */
			$return .= $form->customTemplate( array( Theme::i()->getTemplate( $reviewClass::$formTemplate[0][0], $reviewClass::$formTemplate[0][1], $reviewClass::$formTemplate[0][2] ), $reviewClass::$formTemplate[1] ) );
			return $return;
		}
		
		/* Hang on, are we a guest, but if logged in, could comment? */
		if ( !Member::loggedIn()->member_id )
		{
			$testUser = new Member;
			$testUser->member_group_id = Settings::i()->member_group;
			
			if ( $this->canReview( $testUser ) )
			{
				return $this->guestTeaser( TRUE );
			}
		}
		
		/* Nope, just display nothing */
		return '';
	}
	
	/**
	 * Process the review form
	 *
	 * @param	array	$values		Array of $form values
	 * @return  Review
	 */
	public function processReviewForm( array $values ): Review
	{
		$reviewClass = static::$reviewClass;
		$idColumn = static::$databaseColumnId;
		$reviewIdColumn = $reviewClass::$databaseColumnId;
		
		$review = $reviewClass::create( $this, $values[ static::$formLangPrefix . 'review_text' ], FALSE, $values[ static::$formLangPrefix . 'rating_value' ], $values['guest_name'] ?? NULL );

		/* Update with the rating */
		/* @var $databaseColumnMap array */
		$column = $reviewClass::$databaseColumnMap['rating'];
		$review->$column = $values[ static::$formLangPrefix . 'rating_value' ];
		$review->save();

		Event::fire( 'onCreateOrEdit', $review, array( $values, TRUE ) );

		$parameters = array_merge( array( 'review-' . static::$application . '/' . static::$module  . '-' . $this->$idColumn ), $review->attachmentIds() );
		File::claimAttachments( ...$parameters );

		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}
		
		if ( isset( $values['guest_email'] ) )
		{
			Request::i()->setCookie( 'post_before_register', $review->_logPostBeforeRegistering( $values['guest_email'], isset( Request::i()->cookie['post_before_register'] ) ? Request::i()->cookie['post_before_register'] : NULL ) );
		}

		$review->ui( 'formPostSave', array( $values ) );
		
		return $review;
	}
	
	/**
	 * Message explaining to guests that if they log in they can comment
	 *
	 * @param	bool	$isReview	Is this a review form instead of a comment form?
	 * @return	string
	 * @note	April fools joke!
	 */
	public function guestTeaser( bool $isReview=FALSE ): string
	{
		return Theme::i()->getTemplate( 'global', 'core' )->guestCommentTeaser( $this, $isReview );
	}
	
	/**
	 * Get URL for last comment page
	 *
	 * @return    Url
	 */
	public function lastCommentPageUrl(): Url
	{
		$url = $this->url();
		$lastPage = $this->commentPageCount();
		if ( $lastPage != 1 )
		{
			$url = $url->setPage( 'page', $lastPage );
		}
		return $url;
	}
	
	/**
	 * Get URL for last review page
	 *
	 * @return    Url
	 */
	public function lastReviewPageUrl(): Url
	{
		$url = $this->url();
		$lastPage = $this->reviewPageCount();
		if ( $lastPage != 1 )
		{
			$url = $url->setPage( 'page', $lastPage );
		}
		return $url;
	}
	
	/**
	 * Can comment?
	 *
	 * @param	Member|NULL	$member							The member (NULL for currently logged in member)
	 * @param	bool				$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 */
	public function canComment( ?Member $member=NULL, bool $considerPostBeforeRegistering = TRUE ): bool
	{
		if( $this->containerWrapper() and !$this->container()->checkAction( 'comment' ) )
		{
			return FALSE;
		}

		return $this->canCommentReview( 'reply', $member, $considerPostBeforeRegistering );
	}
	
	/**
	 * Can review?
	 *
	 * @param	Member|NULL	$member							The member (NULL for currently logged in member)
	 * @param	bool				$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 */
	public function canReview( ?Member $member=NULL, bool $considerPostBeforeRegistering = TRUE ): bool
	{
		if( $this->containerWrapper() and !$this->container()->checkAction( 'review' ) )
		{
			return FALSE;
		}

		return $this->canCommentReview( 'review', $member, $considerPostBeforeRegistering ) and !$this->hasReviewed( $member );
	}

	/**
 	 * @brief	Cache if we have already reviewed this item
 	 */
	protected array|null $_hasReviewed	= NULL;

	/**
	 * Already reviewed?
	 *
	 * @param	Member|NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function hasReviewed( ?Member $member=NULL ): bool
	{
		$reviewClass = static::$reviewClass;
		$idColumn = static::$databaseColumnId;
		
		$isGuest	= ( $member === NULL and !Member::loggedIn()->member_id );
		$member		= $member ?: Member::loggedIn();
		
		if( !isset( $this->_hasReviewed[ $member->member_id ] )  )
		{
			/* If guest, check core_post_before_registering */
			if ( $isGuest )
			{
				if ( isset( Request::i()->cookie['post_before_register'] ) )
				{
					$this->_hasReviewed[ $member->member_id ] = 0;

					foreach( Db::i()->select( '*', 'core_post_before_registering', array( 'class=? AND secret=?', $reviewClass, Request::i()->cookie['post_before_register'] ) ) as $pbrWithoutJelly )
					{
						try
						{
							$theJelly	= $pbrWithoutJelly['class']::load( $pbrWithoutJelly['id'] );
							$jellyId	= $theJelly->item()::$databaseColumnId;

							if( $theJelly->item()->$idColumn == $this->$idColumn )
							{
								$this->_hasReviewed[ $member->member_id ]++;
							}
						}
						catch( OutOfRangeException $e ){}
					}
				}
				else
				{
					$this->_hasReviewed[ $member->member_id ] = FALSE;
				}
			}
			
			/* Otherwise check the DB */
			else
			{
				$where = array();
				/* @var $databaseColumnMap array */
				$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn );
				$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'] . '=?', $member->member_id );

				if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) )
				{
					/* Exclude content pending deletion, as it will not be shown inline  */
					if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
					{
						$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '<>?', -2 );
					}
					elseif( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
					{
						$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '<>?', -2 );
					}
				}

				$this->_hasReviewed[ $member->member_id ]	= Db::i()->select( 'COUNT(*)', $reviewClass::$databaseTable, $where )->first();
				return $this->_hasReviewed[ $member->member_id ];
			}
		}
		
		return $this->_hasReviewed[ $member->member_id ];
	}
	
	/**
	 * Can Comment/Review
	 *
	 * @param string $type							Type
	 * @param	Member|NULL	$member							The member (NULL for currently logged in member)
	 * @param	bool				$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 */
	protected function canCommentReview( string $type, Member|null $member = NULL, bool $considerPostBeforeRegistering = TRUE ): bool
	{
		if( !$this->actionEnabled( $type, $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();

		/* Are we restricted from posting completely? */
		if ( $member->restrict_post )
		{
			return FALSE;
		}

		/* Future Items can't be commented and reviewed */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() )
		{
			return FALSE;
		}

		/* Or have an unacknowledged warning? */
		if ( $member->members_bitoptions['unacknowledged_warnings'] )
		{
			return FALSE;
		}

		/* Is this locked? */
		if ( ( IPS::classUsesTrait( $this, 'IPS\Content\Lockable' ) and $this->locked() ) or ( IPS::classUsesTrait( $this, 'IPS\Content\Polls' ) and $this->getPoll() and $this->getPoll()->poll_only ) )
		{
			if ( !$member->member_id )
			{
				return FALSE;
			}

			return ( static::modPermission( 'reply_to_locked', $member, $this->containerWrapper() ) and $this->can( $type, $member ) );
		}

		/* Check permissions as normal */
		return $this->can( $type, $member, $considerPostBeforeRegistering );
	}
		
	/**
	 * @brief	Review Ratings submitted by members
	 */
	protected ?array $memberReviewRatings = null;
	
	/**
	 * Review Rating submitted by member
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	int|null
	 * @throws	BadMethodCallException
	 */
	public function memberReviewRating( Member|null $member = NULL ): int|NULL
	{
		$member = $member ?: Member::loggedIn();

		if( $this->memberReviewRatings === null )
		{
			$reviewClass = static::$reviewClass;
			$idColumn = static::$databaseColumnId;

			/* @var $databaseColumnMap array */
			$where = array();
			$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn );

			if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '=?', 1 );
				}
				elseif ( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '=?', 0 );
				}
			}

			$this->memberReviewRatings = iterator_to_array( Db::i()->select( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] . ',' . $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'], $reviewClass::$databaseTable, $where )
				->setKeyField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'] )
				->setValueField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] ) );

			$this->_averageReviewRating = count( $this->memberReviewRatings ) ? array_sum( $this->memberReviewRatings ) / count( $this->memberReviewRatings ) : 0;
		}

		return $this->memberReviewRatings[ $member->member_id ] ?? null;
	}
	
	/**
	 * @brief	Cached calculated average review rating
	 */
	protected int|float|null $_averageReviewRating = NULL;

	/**
	 * Get average review rating
	 *
	 * @return	int|float
	 */
	public function averageReviewRating(): int|float
	{
		if( $this->_averageReviewRating === NULL )
		{
			$this->memberReviewRating();
		}

		return $this->_averageReviewRating;
	}
	
	/**
	 * @brief	Cached last commenter
	 */
	protected Member|null $_lastCommenter	= NULL;

	/**
	 * Get last comment author
	 *
	 * @return	Member
	 * @throws	BadMethodCallException
	 */
	public function lastCommenter(): Member
	{
		if ( !isset( static::$commentClass ) )
		{
			throw new BadMethodCallException;
		}

		if( $this->_lastCommenter === NULL )
		{
			if ( isset( static::$databaseColumnMap['last_comment_by'] ) )
			{
				$this->_lastCommenter	= Member::load( $this->mapped('last_comment_by') );
				
				if ( ! $this->_lastCommenter->member_id and isset( static::$databaseColumnMap['is_anon'] ) )
				{
					$_lastComment = $this->comments( 1, 0, 'date', 'desc' );
					if ( $_lastComment !== NULL AND IPS::classUsesTrait( $_lastComment, 'IPS\Content\Anonymous' ) AND $_lastComment->isAnonymous() )
					{
						$this->_lastCommenter->name = Member::loggedIn()->language()->addToStack( "post_anonymously_placename" );
					}
				}
				else if ( ! $this->_lastCommenter->member_id and isset( static::$databaseColumnMap['last_comment_name'] ) )
				{
					if ( $this->mapped('last_comment_name') )
					{
						/* A bug in 4.0.0 - 4.0.5 allowed the md5 hash of the word 'Guest' to be stored' */
						if ( ! preg_match( '#^[0-9a-f]{32}$#', $this->mapped('last_comment_name') ) )
						{
							$this->_lastCommenter->name = $this->mapped('last_comment_name');
						}
					}
				}
			}
			else
			{
				$_lastComment = $this->comments( 1, 0, 'date', 'desc' );

				if( $_lastComment !== NULL )
				{
					if ( IPS::classUsesTrait( $_lastComment, 'IPS\Content\Anonymous' ) AND $_lastComment->isAnonymous() )
					{
						$this->_lastCommenter	= new Member;
						$this->_lastCommenter->name = Member::loggedIn()->language()->addToStack( "post_anonymously_placename" );
					}
					else
					{
						$this->_lastCommenter	= $this->comments( 1, 0, 'date', 'desc' )->author();
					}	
				}
				else
				{
					$this->_lastCommenter	= new Member;
				}
			}
		}

		return $this->_lastCommenter;
	}

	/**
	 * Resync the comments/unapproved comment counts
	 *
	 * @param string|null $commentClass	Override comment class to use
	 * @return void
	 */
	public function resyncCommentCounts( string $commentClass=null ): void
	{
		$idColumn     = static::$databaseColumnId;
		$commentClass = $commentClass ?: static::$commentClass;

		if( !isset( static::$commentClass ) )
		{
			return;
		}

		/* @var Comment $commentClass */
		/* @var $databaseColumnMap array */
		$map = static::$databaseColumnMap;
		$commentMap = $commentClass::$databaseColumnMap;

		/* Number of comments */
		if ( isset( $map['num_comments'] ) )
		{
			/* @var $commentClass Comment */
			$where = array( array( $commentClass::$databasePrefix . $commentMap['item'] . '=?', $this->$idColumn ) );
			
			if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $commentMap['approved'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentMap['approved'] . '=?', 1 );
				}
				elseif ( isset( $commentMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentMap['hidden'] . ' IN( 0, 2 )' ); # 2 means the parent is hidden but the post itself is not
				}
			}

			if ( $commentClass::commentWhere() !== NULL )
			{
				$where[] = $commentClass::commentWhere();
			}

			$numCommentsField        = static::$databaseColumnMap['num_comments'];
			$this->$numCommentsField = Db::i()->select( 'COUNT(*)', $commentClass::$databaseTable, $where, NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		}
		if ( isset( $map['unapproved_comments'] ) )
		{
			$where = array( array( $commentClass::$databasePrefix . $commentMap['item'] . '=?', $this->$idColumn ) );

			if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $commentMap['approved'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentMap['approved'] . '=?', 0 );
				}
				elseif ( isset( $commentMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentMap['hidden'] . '=?', 1 );
				}
			}

			if ( $commentClass::commentWhere() !== NULL )
			{
				$where[] = $commentClass::commentWhere();
			}

			$numUnapprovedCommentsField        = static::$databaseColumnMap['unapproved_comments'];
			$this->$numUnapprovedCommentsField = Db::i()->select( 'COUNT(*)', $commentClass::$databaseTable, $where, NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		}
		if ( isset( $map['hidden_comments'] ) )
		{
			$where = array( array( $commentClass::$databasePrefix . $commentMap['item'] . '=?', $this->$idColumn ) );
			
			if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $commentMap['approved'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentMap['approved'] . '=?', -1 );
				}
				elseif ( isset( $commentMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentMap['hidden'] . '=?', -1 );
				}
			}
			
			if ( $commentClass::commentWhere() !== NULL )
			{
				$where[] = $commentClass::commentWhere();
			}
			
			$numHiddenCommentsField			= static::$databaseColumnMap['hidden_comments'];
			$this->$numHiddenCommentsField	= Db::i()->select( 'COUNT(*)', $commentClass::$databaseTable, $where, NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		}
	}

	/**
	 * Resync the hidden/approved/unapproved review counts
	 *
	 * @return void
	 */
	public function resyncReviewCounts(): void
	{
		if( !isset( static::$reviewClass ) )
		{
			return;
		}

		$idColumn		= static::$databaseColumnId;
		$reviewClass	= static::$reviewClass;

		/* Number of reviews */
		if ( isset( static::$databaseColumnMap['num_reviews'] ) )
		{
			/* @var $databaseColumnMap array */
			$where = array( array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
			
			if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '=?', 1 );
				}
				elseif ( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . ' IN( 0, 2 )' ); # 2 means the parent is hidden but the post itself is not
				}
			}

			if ( $reviewClass::commentWhere() !== NULL )
			{
				$where[] = $reviewClass::commentWhere();
			}

			$numCommentsField        = static::$databaseColumnMap['num_reviews'];
			$this->$numCommentsField = Db::i()->select( 'COUNT(*)', $reviewClass::$databaseTable, $where )->first();
		}

		/* Number of unapproved reviews */
		if ( isset( static::$databaseColumnMap['unapproved_reviews'] ) )
		{
			/* @var array $databaseColumnMap */
			$where = array( array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );

			if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '=?', 0 );
				}
				elseif ( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '=?', 1 );
				}
			}

			$numUnapprovedCommentsField        = static::$databaseColumnMap['unapproved_reviews'];
			$this->$numUnapprovedCommentsField = Db::i()->select( 'COUNT(*)', $reviewClass::$databaseTable, $where )->first();
		}

		/* Number of hidden reviews */
		if ( isset( static::$databaseColumnMap['hidden_reviews'] ) )
		{
			/* @var array $databaseColumnMap */
			$where = array( array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
			
			if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) )
			{
				if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '=?', -1 );
				}
				elseif ( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '=?', -1 );
				}
			}

			$numHiddenCommentsField			= static::$databaseColumnMap['hidden_reviews'];
			$this->$numHiddenCommentsField	= Db::i()->select( 'COUNT(*)', $reviewClass::$databaseTable, $where )->first();
		}
	}
		
	/**
	 * Resync last comment
	 *
	 * @param	Comment|null $comment The comment
	 *
	 * @return	void
	 */
	public function resyncLastComment( Comment $comment = NULL ) : void
	{
		if( !isset( static::$commentClass ) )
		{
			return;
		}

		$columns = array( 'last_comment', 'last_comment_by', 'last_comment_name', 'last_comment_anon' );
		$resync = FALSE;
		foreach ( $columns as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) )
			{
				$resync = TRUE;
			}
		}

		/* If this comment isn't newer than the cached value, we don't need to do anything */
		if( $resync AND $comment !== NULL AND isset( static::$databaseColumnMap['last_comment'] ) AND $comment->mapped('date') < $this->mapped('last_comment' ) )
		{
			return;
		}

		if ( $resync )
		{
			$existingFlag = static::$useWriteServer;
			static::$useWriteServer = TRUE;

			try
			{
				$comment = $this->comments( 1, 0, 'date', 'desc', NULL, FALSE, NULL, NULL, TRUE );
				if ( !$comment )
				{
					throw new UnderflowException;
				}
				if ( isset( static::$databaseColumnMap['last_comment'] ) )
				{
					$lastCommentField = static::$databaseColumnMap['last_comment'];
					if ( is_array( $lastCommentField ) )
					{
						foreach ( $lastCommentField as $column )
						{
							$this->$column = $comment->mapped('date');
						}
					}
					else
					{
						$this->$lastCommentField = $comment->mapped('date');
					}
				}
				if ( isset( static::$databaseColumnMap['last_comment_by'] ) )
				{
					$lastCommentByField = static::$databaseColumnMap['last_comment_by'];
					$this->$lastCommentByField = (int) $comment->author()->member_id;
				}
				if ( isset( static::$databaseColumnMap['last_comment_name'] ) )
				{
					$lastCommentNameField = static::$databaseColumnMap['last_comment_name'];
					$this->$lastCommentNameField = ( !$comment->author()->member_id and isset( $comment::$databaseColumnMap['author_name'] ) ) ? $comment->mapped('author_name') : $comment->author()->name;
				}
				if ( IPS::classUsesTrait( $this, 'IPS\Content\Anonymous' ) AND isset( static::$databaseColumnMap['last_comment_anon'] ) )
				{
					$lastCommentAnonField = static::$databaseColumnMap['last_comment_anon'];
					$this->$lastCommentAnonField = (int) $comment->isAnonymous();
				}
			}
			catch ( UnderflowException $e )
			{
				foreach ( $columns as $c )
				{
					if ( $c === 'last_comment' and isset( static::$databaseColumnMap['last_comment'] ) and is_array( static::$databaseColumnMap['last_comment'] ) )
					{
						$lastCommentField = static::$databaseColumnMap['last_comment'];
						if ( is_array( $lastCommentField ) )
						{
							foreach ( $lastCommentField as $col )
							{
								$this->$col = 0;
							}
						}
					}
					else if ( $c === 'last_comment' and isset( static::$databaseColumnMap['last_comment'] ) )
					{
						$field        = static::$databaseColumnMap[$c];
						$this->$field = 0;
					}
					else if( $c === 'last_comment_by' AND isset( static::$databaseColumnMap['last_comment_by'] ) )
					{
						$field        = static::$databaseColumnMap[$c];
						$this->$field = 0;
					}
					else if( $c === 'last_comment_anon' AND isset( static::$databaseColumnMap['last_comment_anon'] ) )
					{
						$field        = static::$databaseColumnMap[$c];
						$this->$field = 0;
					}
					else
					{
						if ( isset( static::$databaseColumnMap[$c] ) )
						{
							$field        = static::$databaseColumnMap[$c];
							$this->$field = NULL;
						}
					}
				}
			}

			static::$useWriteServer = $existingFlag;
		}
	}
	
	/**
	 * Resync last review
	 *
	 * @return	void
	 */
	public function resyncLastReview( Comment $comment = NULL ): void
	{
		if( !isset( static::$reviewClass ) )
		{
			return;
		}

		$columns = array( 'last_review', 'last_review_by', 'last_review_name' );
		$resync = FALSE;
		foreach ( $columns as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) )
			{
				$resync = TRUE;
			}
		}

		/* If this comment isn't newer than the cached value, we don't need to do anything */
		if( $resync AND $comment !== NULL AND isset( static::$databaseColumnMap['last_review'] ) AND $comment->mapped('date') < static::$databaseColumnMap['last_review'] )
		{
			return;
		}

		if ( $resync )
		{
			$existingFlag = static::$useWriteServer;
			static::$useWriteServer = TRUE;

			try
			{
				$review = $this->reviews( 1, 0, 'date', 'desc', NULL, FALSE );
				
				if ( isset( static::$databaseColumnMap['last_review'] ) )
				{
					$lastReviewField = static::$databaseColumnMap['last_review'];
					if ( is_array( $lastReviewField ) )
					{
						foreach ( $lastReviewField as $column )
						{
							$this->$column = $review->mapped('date');
						}
					}
					else
					{
						if ( !is_null( $review ) )
						{
							$this->$lastReviewField = $review->mapped('date');
						}
						else
						{
							$this->$lastReviewField = $this->date;
						}
					}
				}
				if ( isset( static::$databaseColumnMap['last_review_by'] ) )
				{
					$lastReviewByField = static::$databaseColumnMap['last_review_by'];
					$this->$lastReviewByField = ( is_null( $review ) ? NULL : $review->author()->member_id );
				}
				if ( isset( static::$databaseColumnMap['last_review_name'] ) )
				{
					$lastReviewNameField = static::$databaseColumnMap['last_review_name'];
					$this->$lastReviewNameField = ( is_null( $review ) ? NULL : ( ( !$review->author()->member_id and isset( $review::$databaseColumnMap['author_name'] ) ) ? $review->mapped('author_name') : $review->author()->name ) );
				}
			}
			catch ( UnderflowException $e )
			{
				if ( is_array( $columns ) )
				{
					foreach ( $columns as $c )
					{
						if ( isset( static::$databaseColumnMap[ $c ] ) )
						{
							$field = static::$databaseColumnMap[ $c ];
							$this->$field = NULL;
						}
					}
				}
				else
				{
					if ( isset( static::$databaseColumnMap[ $column ] ) )
					{
						$field = static::$databaseColumnMap[ $column ];
						$this->$field = NULL;
					}
				}
			}

			static::$useWriteServer = $existingFlag;
		}
	}
	
	/**
	 * @brief	Item counts
	 */
	protected static array $itemCounts = array();
	
	/**
	 * @brief	Comment counts
	 */
	protected static array $commentCounts = array();
	
	/**
	 * @brief	Review counts
	 */
	protected static array $reviewCounts = array();
	
	/**
	 * Total item \count(including children)
	 *
	 * @param	Model	$container			The container
	 * @param	bool			$includeItems		If TRUE, items will be included (this should usually be true)
	 * @param	bool			$includeComments	If TRUE, comments will be included
	 * @param	bool			$includeReviews		If TRUE, reviews will be included
	 * @param	int				$depth				Used to keep track of current depth to avoid going too deep
	 * @return	int|NULL|string	When depth exceeds 10, will return "NULL" and initial call will return something like "100+"
	 * @note	This method may return something like "100+" if it has lots of children to avoid exahusting memory. It is intended only for display use
	 * @note	This method includes counts of hidden and unapproved content items as well
	 */
	public static function contentCount( Model $container, bool $includeItems=TRUE, bool $includeComments=FALSE, bool $includeReviews=FALSE, int $depth=0 ): int|NULL|string
	{
		/* Are we in too deep? */
		if ( $depth > 3 )
		{
			return '+';
		}

		/* Generate a key */
		$_key	= md5( get_class( $container ) . $container->_id );
		
		/* Count items */
		$count = 0;
		if( $includeItems )
		{
			if ( $container->_items === NULL )
			{
				if ( !isset( static::$itemCounts[ $_key ] ) )
				{
					$_count = static::getItemsWithPermission( array( array( static::$databasePrefix . static::$databaseColumnMap['container'] . '=?', $container->_id ) ), NULL, 1, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );

					$_key = md5( get_class( $container ) . $container->_id );
					static::$itemCounts[ $_key ][ $container->_id ] = $_count;
				}

				if ( isset( static::$itemCounts[ $_key ][ $container->_id ] ) )
				{
					$count += static::$itemCounts[ $_key ][ $container->_id ];
				}
			}
			else
			{
				$count += $container->_items;
			}
		}

		/* Count comments */
		if ( $includeComments )
		{
			if ( $container->_comments === NULL )
			{
				if ( !isset( static::$commentCounts ) )
				{
					/* @var Comment $commentClass */
					$commentClass = static::$commentClass;
					/* @var $databaseColumnMap array */
					static::$commentCounts[ $_key ] = iterator_to_array( Db::i()->select(
						'COUNT(*) AS count, ' . static::$databasePrefix . static::$databaseColumnMap['container'],
						$commentClass::$databaseTable,
						NULL,
						NULL,
						NULL,
						static::$databasePrefix . static::$databaseColumnMap['container']
					)->join( static::$databaseTable, $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=' . static::$databasePrefix . static::$databaseColumnId )
					->setKeyField( static::$databasePrefix . static::$databaseColumnMap['container'] )
					->setValueField('count') );
				}
				
				if ( isset( static::$commentCounts[ $_key ][ $container->_id ] ) )
				{
					$count += static::$commentCounts[ $_key ][ $container->_id ];
				}
			}
			else
			{
				$count += $container->_comments;
			}
		}
		
		/* Count Reviews */
		if ( $includeReviews )
		{
			if ( $container->_reviews === NULL )
			{
				if ( !isset( static::$reviewCounts ) )
				{
					/* @var Review $reviewClass */
					/* @var array $databaseColumnMap */
					$reviewClass = static::$commentClass;
					static::$reviewCounts[ $_key ] = iterator_to_array( Db::i()->select(
						'COUNT(*) AS count, ' . static::$databasePrefix . static::$databaseColumnMap['container'],
						$reviewClass::$databaseTable,
						NULL,
						NULL,
						NULL,
						static::$databasePrefix . static::$databaseColumnMap['container']
					)->join( static::$databaseTable, $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=' . static::$databasePrefix . static::$databaseColumnId )
					->setKeyField( static::$databasePrefix . static::$databaseColumnMap['container'] )
					->setValueField('count') );
				}

				if ( isset( static::$reviewCounts[ $_key ][ $container->_id ] ) )
				{
					$count += static::$reviewCounts[ $_key ][ $container->_id ];
				}
			}
			else
			{
				$count += $container->_reviews;
			}
		}
		
		/* Add Children */
		$childDepth	= $depth++;
		foreach ( $container->children() as $child )
		{
			$toAdd = static::contentCount( $child, $includeItems, $includeComments, $includeReviews, $childDepth );
			if ( is_string( $toAdd ) )
			{
				return $count . '+';
			}
			else
			{
				$count += $toAdd;
			}
			
		}
		return $count;
	}
	
	/**
	 * @brief	Actions to show in comment multi-mod
	 * @see	Item::commentMultimodActions()
	 */
	protected array|null $_commentMultiModActions = null;
	
	/**
	 * @brief	Actions to show in review multi-mod
	 * @see		Item::reviewMultimodActions()
	 */
	protected array|null $_reviewMultiModActions = null;
	
	/**
	 * Actions to show in comment multi-mod
	 *
	 * @param	Member|NULL	$member	Member (NULL for currently logged in member)
	 * @return	array
	 */
	public function commentMultimodActions( ?Member $member = NULL ): array
	{
		if ( $this->_commentMultiModActions === NULL )
		{
			$member = $member ?: Member::loggedIn();
			$this->_commentMultiModActions = array();
			if ( isset( static::$commentClass ) )
			{
				$this->_commentMultiModActions = $this->_commentReviewMultimodActions( static::$commentClass, $member );
			}
		}
		
		return $this->_commentMultiModActions;
	}
	
	/**
	 * Actions to show in review multi-mod
	 *
	 * @param	Member|null	$member	Member (NULL for currently logged in member)
	 * @return	array
	 */
	public function reviewMultimodActions( Member|null $member = NULL ): array
	{
		if ( $this->_reviewMultiModActions === NULL )
		{
			$member = $member ?: Member::loggedIn();
			$this->_reviewMultiModActions = array();
			if ( isset( static::$reviewClass ) )
			{
				$this->_reviewMultiModActions = $this->_commentReviewMultimodActions( static::$reviewClass, $member );
			}
		}
		
		return $this->_reviewMultiModActions;
	}
	
	/**
	 * Actions to show in comment/review multi-mod
	 *
	 * @param string $class 	The class
	 * @param	Member	$member	Member (NULL for currently logged in member)
	 * @return	array
	 */
	protected function _commentReviewMultimodActions( string $class, Member $member ): array
	{
		/* @var Comment $class */
		$itemClass = $class::$itemClass;

		$return = array();
		$check = array();
		$check[] = 'split_merge';
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
		{
			$check[] = 'approve';
			$check[] = 'hide';
			$check[] = 'unhide';
		}
		$check[] = 'delete';
		
		foreach ( $check as $k )
		{
			if ( $k == 'split_merge' )
			{
				if( $itemClass::modPermission( $k, $member, $this->containerWrapper() ) )
				{
					$return[] = $k;
				}
			}
			else
			{
				/* @var $class Content */
				if( $class::modPermission( $k, $member, $this->containerWrapper() ) )
				{
					$return[] = $k;
				}
			}
		}

		return $return;
	}
	
	/**
	 * Generate meta data between comments. It is assumed they all belong to the same topic
	 *
	 * @param 	array	$comments	Array of $comment objects
	 * @param bool $anonymous	Anonymize the moderator actions
	 * @return	array
	 */
	public function generateCommentMetaData( array $comments, bool $anonymous = FALSE ) : array
	{
		if ( ! $this->showCommentMeta('time') and ! $this->showCommentMeta('moderation') )
		{
		    return $comments;
        }

        $showAnonymous = FALSE; 

        if( $anonymous )
        {
        	$containerClass = static::$containerNodeClass;

        	if( isset( $containerClass::$modPerm ) AND $containerClass::$modPerm )
        	{
        		$showAnonymous = !(
					Member::loggedIn()->modPermission( $containerClass::$modPerm ) === TRUE
					or
					Member::loggedIn()->modPermission( $containerClass::$modPerm ) === -1
					or
					(
						is_array( Member::loggedIn()->modPermission( $containerClass::$modPerm ) )
						and
						in_array( $this->container()->_id, Member::loggedIn()->modPermission( $containerClass::$modPerm ) )
					)
				);
        	}
        	else
        	{
        		$showAnonymous = TRUE;
        	}
        }


        $anonymous && Member::loggedIn()->modPermissions();
		
		$lowestCommentDate = 0;
		$highestCommentDate = $this->isLastPage() ? time() : 0; // If we're on the last page, we want any events from the earliest comment to now, not just the last post

		/* Get the lowest and highest possible timestamps from $comments */
		foreach( $comments as $comment )
		{
			$commentDate = $comment->mapped( 'date' );

			if( $lowestCommentDate === 0 || $commentDate < $lowestCommentDate )
			{
				$lowestCommentDate = $commentDate;
			}

			if( $commentDate > $highestCommentDate )
			{
				$highestCommentDate = $commentDate;
			}
		}

		/* Get moderation items */
		$idColumn = static::$databaseColumnId;
		$moderationItems = array();

		if ( $this->showCommentMeta('moderation') )
		{
			foreach ( Db::i()->select( '*', 'core_moderator_logs', array('class=? AND ( ctime BETWEEN ? AND ? ) AND item_id=?', get_class( $this ), $lowestCommentDate - 1, $highestCommentDate + 1, $this->$idColumn), 'ctime ASC' ) as $row )
			{
				if ( in_array( $row['lang_key'], array('modlog__action_unfeature', 'modlog__action_feature', 'modlog__action_unlock', 'modlog__action_lock', 'modlog__action_unpin', 'modlog__action_pin', 'modlog__item_edit', 'modlog__comment_edit_title') ) )
				{
					$moderationItems[] = $row;
				}
			}
		}

		foreach( $comments as $oid => &$comment )
		{
			$next = next( $comments );
			$commentDate = $comment->mapped( 'date' );
			$nextCommentDate = ( $next ) ? $next->mapped( 'date' ) : 0;

			if ( $this->showCommentMeta('moderation') )
			{
				$otherActions = array();
				$currentActionGroup = array();
				$lastActionDate = NULL;
				$lastActionMember = NULL;

				foreach ( $moderationItems as $id => $data )
				{
					$modActionDate = $data['ctime'];

					if ( ( $modActionDate >= $commentDate and $modActionDate <= $nextCommentDate ) or ( $modActionDate >= $commentDate and !$next )  )
					{
						$commentDate = $modActionDate;
						$blurb = NULL;
						$langKey = 'comment_meta_moderation_' . ( $showAnonymous ? 'anon_' : '' ) . $data['lang_key'];

						/* Edits always get their own meta action bubble, so handle those individually */
						if ( in_array( $data['lang_key'], array( 'modlog__item_edit', 'modlog__comment_edit_title' ) ) )
						{
							$modNotes = json_decode( $data['note'], TRUE );
							if( $modNotes )
							{
								$note = array_keys( $modNotes );
							}
							else
							{
								$note = array();
							}
							
							if( ( count( $note ) == 4 && $data['lang_key'] == 'modlog__item_edit' ) || ( count( $note ) == 3 && $data['lang_key'] == 'modlog__comment_edit_title' ) )
							{
								$oldTitle = array_pop( $note );
								$newTitle = array_pop( $note );

								if ( $oldTitle )
								{
									$blurb = Member::loggedIn()->language()->addToStack( $langKey, FALSE, array('htmlsprintf' => array(Member::load( $data['member_id'] )->link()), 'sprintf' => array( $newTitle ) ) );

									$comment->_data['metaData']['comment']['moderation'][$data['lang_key']] = array(
										'row' => $data,
										'blurb' => $blurb,
										'action' => $data['lang_key']
									);
								}
							}
						}
						else
						{
							/* Other actions like pinned, featured etc. get combined into one bubble if they are close together, so keep track of those here 
							   If this action occurred more than 10 minutes after the last, start a new group. Otherwise, append to previous group.
							   We want to end up with a structure like this, assuming pinned and featured were within 10 mins but lock happened later:
							   
								array (
									0 => array( 0 => [pinned data], 1 => [featured data] ),
									1 => array( 0 => [locked data] )
								); 
							*/						
							if( count( $currentActionGroup) && ( $modActionDate - $lastActionDate > 600 || $data['member_id'] !== $lastActionMember ) ){
								$otherActions[] = $currentActionGroup;
								$currentActionGroup = array();
							}

							$currentActionGroup[] = array(
								'row' => $data,
								'action' => $data['lang_key'],
								'lang_key' => $langKey
							);

							$lastActionDate = $modActionDate;	
							$lastActionMember = $data['member_id'];
						}

						unset( $moderationItems[$id] );
					}
				}

				/* Push any remaining actions into otherActions */
				if( count( $currentActionGroup ) )
				{
					$otherActions[] = $currentActionGroup;
				}

				/* Process any other actions */
				if( count( $otherActions ) )
				{
					foreach( $otherActions as $groupIdx => $actions )
					{
						if( count( $actions ) === 1 )
						{
							/* If this is just one action, generate a full bubble */
							$comment->_data['metaData']['comment']['moderation'][$actions[0]['action']] = array(
								'row' => $actions[0]['row'],
								'blurb' => Member::loggedIn()->language()->addToStack( $actions[0]['lang_key'], FALSE, array('htmlsprintf' => array($this->definiteArticle(), Member::load( $actions[0]['row']['member_id'] )->link())) ),
								'action' => $actions[0]['lang_key']
							);
						}
						else
						{
							$actionPhrases = array_map( 
								function ($_action) {
									return Member::loggedIn()->language()->addToStack( 'comment_meta_moderation_' . $_action['action'] . '_short' );
								},
								$actions
							);

							$comment->_data['metaData']['comment']['moderation'][$groupIdx] = array(
								'row' => $actions[0]['row'], // Use the first action in this group as the 'row', to provide the time etc. for all items in this bubble
								'blurb' => Member::loggedIn()->language()->addToStack( 'comment_meta_moderation_'  . ( $showAnonymous ? 'anon_' : '' ) . 'modlog__action_group', FALSE, array('htmlsprintf' => array($this->definiteArticle(), Member::load( $lastActionMember )->link(), Member::loggedIn()->language()->formatList( $actionPhrases ) ) ) ),
								'action' => $actions[0]['lang_key']
							);
						}
					}
				}
			}

			$date = DateTime::ts( $commentDate );
			$nextDate = ( $next ) ? DateTime::ts( $nextCommentDate ) : NULL;

			if ( $this->showCommentMeta('time') )
			{
				if ( $nextDate instanceOf DateTime and $nextDate->diff( $date )->days > 7 )
				{
					$blurb = NULL;

					/* Preload some language strings */
					Member::loggedIn()->language()->get( ['comment_meta_date_weeks_later', 'comment_meta_date_months_later', 'comment_meta_date_years_later'] );

					/* Years? */
					if ( $nextDate->diff( $date )->y > 0 )
					{
						$blurb = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get( 'comment_meta_date_years_later' ), array($nextDate->diff( $date )->y) );
					}
					else if ( $nextDate->diff( $date )->m > 0 )
					{
						$blurb = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get( 'comment_meta_date_months_later' ), array($nextDate->diff( $date )->m) );
					}
					else if ( $nextDate->diff( $date )->days > 7 )
					{
						$blurb = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get( 'comment_meta_date_weeks_later' ), array(ceil( $nextDate->diff( $date )->days / 7 )) );
					}

					$comment->_data['metaData']['comment']['timeGap'] = array(
						'days' => $nextDate->diff( $date )->days,
						'blurb' => $blurb
					);
				}
			}
		}
		
		return $comments;
	}

	/**
	 * @brief Setting name for show_meta
	 */
	public static string|null $showMetaSettingKey = NULL;

	/**
	 * Show the topic meta?
	 *
	 * @param	$key    string        Key to check (time, moderation)
	 * @return boolean
	 */
	public function showCommentMeta( string $key ): bool
	{
		if( $key == 'moderation' AND Member::loggedIn()->group['gbw_hide_inline_modevents'] )
		{
			return FALSE;
		}

		$metaKey = static::$showMetaSettingKey;

		if ( isset( Settings::i()->$metaKey ) and Settings::i()->$metaKey )
		{
			$meta = json_decode( Settings::i()->$metaKey, TRUE );
			if ( $meta and in_array( $key, $meta ) )
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
	/**
	 * Get table showing moderation actions
	 *
	 * @return	string
	 * @throws	DomainException
	 */
	public function moderationTable(): string
	{
		if( !Member::loggedIn()->modPermission('can_view_moderation_log') )
		{
			throw new DomainException;
		}
		
		$idColumn = static::$databaseColumnId;
		$where = array( 'class=? AND item_id=?', get_class( $this ), $this->$idColumn );
	
		$table = new \IPS\Helpers\Table\Db( 'core_moderator_logs', $this->url( 'modLog' ), $where );
		$table->langPrefix = 'modlogs_';
		$table->include = array( 'member_id', 'action', 'ip_address', 'ctime' );
		$table->mainColumn = 'action';
		/* Because this is shown in a modal, limit the number of results per page */
		$table->limit = 10;
		
		$table->tableTemplate	= array( Theme::i()->getTemplate( 'moderationLog', 'core' ), 'table' );
		$table->rowsTemplate	= array( Theme::i()->getTemplate( 'moderationLog', 'core' ), 'rows' );
		
		$table->parsers = array(
				'action'	=> function( $val, $row )
				{
					if ( $row['lang_key'] )
					{
						$langKey = $row['lang_key'];
						$params = array();
						foreach ( json_decode( $row['note'], TRUE ) as $k => $v )
						{
							$params[] = $v ? Member::loggedIn()->language()->addToStack( $k ) : $k;
						}

						return Member::loggedIn()->language()->addToStack( $langKey, FALSE, array( 'sprintf' => $params ) );
					}
					else
					{
						return $row['note'];
					}
				}
		);
		$table->sortBy = $table->sortBy ?: 'ctime';
		$table->sortDirection = $table->sortDirection ?: 'desc';

		if( !Request::i()->isAjax() )
		{
			return Theme::i()->getTemplate( 'tables', 'core' )->container( (string) $table );
		}
		else
		{
			return (string) $table;
		}
	}

	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission						A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param	Member|Group|NULL	$member							The member or group to check (NULL for currently logged in member)
	 * @param	bool								$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	OutOfBoundsException	If $permission does not exist in map
	 */
	public function can( mixed $permission, Member|Group|null $member=NULL, bool $considerPostBeforeRegistering=TRUE ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* If the member is banned they can't do anything? */
		if ( ! ( $member instanceof Group ) and ! $member->group['g_view_board'] )
		{
			return FALSE;
		}

		/* If we can find the node... */
		try
		{
			/* Check with the node if we can do what we're trying to do */
			if( !$this->container()->can( $permission, $member, $considerPostBeforeRegistering ) )
			{
				return FALSE;
			}

			/* If we're trying to *read* a content item (or in fact anything, but we only check read since if we managed to access it we don't need to check this again for other permissions),
			   check if we can *view* (i.e. access) all of the parents. This is so if an admin, for example, removes a group's permission to view (i.e. access) a node, they will not be able
			   to access content within it. Though this is not in line with conventional ACL practices, it is how the suite has always worked and we don't want to mess up permissions for upgrades  */
			if ( $permission === 'read' )
			{
				foreach( $this->container()->parents() as $parent )
				{
					if( !$parent->can( 'view', $member ) )
					{
						return FALSE;
					}
				}
			}
		}
			/* If the node has been lost, assume we can do nothing */
		catch ( OutOfRangeException )
		{
			return FALSE;
		}
			/* If this content doesn't actually have a container, ignore that */
		catch( BadMethodCallException ) { }

		/* Still here? It must be okay */
		return TRUE;
	}

	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( ?Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* Check it isn't hidden */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideble' ) and $this->hidden() )
		{
			/* If we're a moderator who can see hidden items it's fine, unless it's a guest post before register */
			if ( $this->hidden() !== -3 and static::canViewHiddenItems( $member, $this->containerWrapper() ) )
			{
				// OK
			}
			/* If it's pending approval, and we're the author it's fine */
			elseif ( $this->hidden() === 1 and $this->author()->member_id and $this->author()->member_id == $member->member_id )
			{
				// OK
			}
			/* Otherwise hidden content can't be viewed */
			else
			{
				return FALSE;
			}
		}

		/* Check it isn't set to be published in the future */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) )
		{
			$future = static::$databaseColumnMap['is_future_entry'];

			if ( $this->$future == 1 AND ( !static::canViewFutureItems( $member, $this->containerWrapper() ) AND $this->author()->member_id != $member->member_id ) )
			{
				return FALSE;
			}
		}

		/* Check node */
		return $this->containerWrapper() ? $this->container()->can( 'read', $member ) : true;
	}

	/**
	 * Can change author?
	 *
	 * @param	Member|NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canChangeAuthor( ?Member $member = NULL ): bool
	{
		return static::modPermission( 'edit', $member, $this->container() );
	}
	
	/* !Moderation */
	
	/**
	 * Can edit?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'edit', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();

		$couldEdit = $this->couldEdit( $member );
		
		if ( $couldEdit === TRUE )
		{
			if ( static::modPermission( 'edit', $member, $this->containerWrapper() ) )
			{
				return TRUE;
			}
			
			/* Still here, we can edit this post */
			if ( !$member->group['g_edit_cutoff'] )
			{
				return TRUE;
			}
			else
			{
				/* Check if we are looking for a time out */
				if( DateTime::ts( $this->mapped('date') )->add( new DateInterval( "PT{$member->group['g_edit_cutoff']}M" ) ) > DateTime::create() )
				{
					return TRUE;
				}
				return FALSE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Could edit an item?
	 * Useful to see if one can edit something even if the cut off has expired
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function couldEdit( ?Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* Are we restricted from posting or have an unacknowledged warning? */
		if ( $member->restrict_post or ( $member->members_bitoptions['unacknowledged_warnings'] and Settings::i()->warn_on and Settings::i()->warnings_acknowledge ) )
		{
			return FALSE;
		}

		if ( $member->member_id )
		{
			/* Do we have moderator permission to edit stuff in the container? */
			if ( static::modPermission( 'edit', $member, $this->containerWrapper() ) )
			{
				return TRUE;
			}

			/* Can the member edit their own content? */
			if ( $member->member_id == $this->author()->member_id and ( $member->group['g_edit_posts'] == '1' or in_array( get_class( $this ), explode( ',', $member->group['g_edit_posts'] ) ) ) )
			{
				if ( IPS::classUsesTrait( $this, 'IPS\Content\Lockable' ) AND $this->locked() )
				{
					return FALSE;
				}
				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Can edit title?
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEditTitle( ?Member $member=NULL ): bool
	{
		return $this->canEdit( $member );
	}

	/**
	 * Can move?
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canMove( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'move', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();

		try
		{
			return ( $member->member_id and $this->container() and ( static::modPermission( 'move', $member, $this->containerWrapper() ) ) );
		}
		catch( BadMethodCallException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Can Merge?
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canMerge( Member|null $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'merge', $member ) )
		{
			return false;
		}

		if ( static::$firstCommentRequired )
		{
			$member = $member ?: Member::loggedIn();
			return ( $member->member_id and ( static::modPermission( 'split_merge', $member, $this->containerWrapper() ) ) );
		}
		return FALSE;
	}
	
	/**
	 * Can delete?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'delete', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();
		
		/* Guests can never delete */
		if ( !$member->member_id )
		{
			return FALSE;
		}
		
		/* Can we delete our own content? */
		if ( $member->member_id == $this->author()->member_id and ( $member->group['g_delete_own_posts'] == '1' or in_array( get_class( $this ), explode( ',', $member->group['g_delete_own_posts'] ) ) ) )
		{
			return TRUE;
		}
		
		/* What about this? */
		try
		{
			return static::modPermission( 'delete', $member, $this->containerWrapper() );
		}
		catch ( BadMethodCallException $e )
		{
			return $member->modPermission( "can_delete_content" );
		}
	}

	/**
	 * Warning Reference Key
	 *
	 * @return	string|NULL
	 */
	public function warningRef(): string|NULL
	{
		/* If the member cannot warn, return NULL so we're not adding ugly parameters to the profile URL unnecessarily */
		if ( !Member::loggedIn()->modPermission('mod_can_warn') )
		{
			return NULL;
		}
		
		$idColumn = static::$databaseColumnId;
		return base64_encode( json_encode( array( 'app' => static::$application, 'module' => static::$module, 'id_1' => $this->$idColumn ) ) );
	}
		
	/* !\IPS\Helpers\Table */
	
	/**
	 * @brief	Table hover URL
	 */
	public ?bool $tableHoverUrl = NULL;
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );
		
		if ( isset( $data[ static::$databaseTable ] ) and is_array( $data[ static::$databaseTable ] ) )
		{
			if ( isset( $data['core_tags_cache'] ) )
			{
				$obj->tags = ! empty( $data['core_tags_cache']['tag_cache_text'] ) ? json_decode( $data['core_tags_cache']['tag_cache_text'], TRUE ) : array( 'tags' => array(), 'prefix' => NULL );
			}
			if ( isset( $data['last_commenter'] ) )
			{
				Member::constructFromData( $data['last_commenter'], FALSE );
			}
		}
		
		return $obj;
	}
	
	/* !Sitemap */
	
	/**
	 * WHERE clause for getting items for sitemap (permissions are already accounted for)
	 *
	 * @return    array
	 */
	public static function sitemapWhere(): array
	{
		return array();
	}
	
	/**
	 * Sitemap Priority
	 *
	 * @return    int|null    NULL to use default
	 */
	public function sitemapPriority(): ?int
	{
		return NULL;
	}

	/**
	 * Return the first comment on the item
	 *
	 * @return Comment|NULL
	 */
	public function firstComment(): Comment|NULL
	{
		$comment		= NULL;
		$commentClass	= static::$commentClass;

		if( isset( static::$archiveClass ) AND method_exists( $this, 'isArchived' ) AND $this->isArchived() )
		{
			$commentClass	= static::$archiveClass;
		}

		/* If we map the first comment ID, load using that (if it's set) */
		if ( isset( static::$databaseColumnMap['first_comment_id'] ) )
		{
			$col = static::$databaseColumnMap['first_comment_id'];

			if( $this->$col )
			{
				try
				{
					$comment = $commentClass::load( $this->$col );
				}
				catch( OutOfRangeException $e ){}
			}
		}

		/* If we still don't have the comment, load the old fashioned way */
		if( !$comment )
		{
			try
			{
				$idColumn	= static::$databaseColumnId;
				/* @var $databaseColumnMap array */
				$comment	= $commentClass::constructFromData( Db::i()->select( '*', $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $this->$idColumn ), $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'] . ' ASC', 1 )->first() );

				/* If we do map the first_comment_id and we're here, it was either empty or wrong..let's fix that for next time */
				if ( isset( static::$databaseColumnMap['first_comment_id'] ) )
				{
					$col 				= static::$databaseColumnMap['first_comment_id'];
					$commentIdColumn	= $commentClass::$databaseColumnId;
					$this->$col = $comment->$commentIdColumn;
					$this->save();
				}
			}
			catch( UnderflowException $e ){}
		}

		return $comment;
	}
	
	/* ! Redirect links */
	
	/**
	 * Store a redirect
	 *
	 * Saves a redirect so when this class:item_id is attempted to be loaded in the future, it 301 redirects to the new item
	 *
	 * @param Item $item	The item to redirect to
	 *
	 * @return	void
	 */
	public function setRedirectTo( Item $item ): void
	{
		$idColumn = static::$databaseColumnId;
		Db::i()->insert( 'core_item_redirect', array(
			'redirect_class'       => get_class( $item ),
			'redirect_item_id'     => $this->$idColumn,
			'redirect_new_item_id' => $item->$idColumn
		) );
	}

	/**
	 * Can view reports?
	 *
	 * @param Member|NULL $member	The member to check for (NULL for currently logged in member)
	 * @return bool
	 */
	public function canViewReports( ?Member $member=NULL ) : bool
	{
		if( !( IPS::classUsesTrait( $this, 'IPS\Content\Reportable' ) ) )
		{
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();
		return ( $member->member_id and static::modPermission( 'view_reports', $member, $this->containerWrapper() ) );
	}

	/**
	 * Get the redirect to data
	 *
	 * Fetches the \IPS\Content\Item we want to redirect to
	 *
	 * @param int $id			The ID to look up
	 * @param bool $checkPerms	Check permissions when loading
	 *
	 * @return	static
	 * @throws	OutOfRangeException
	 */
	public static function getRedirectFrom( int $id, bool $checkPerms=TRUE ): static
	{
		$idColumn = static::$databaseColumnId;
		try
		{
			$method = ( $checkPerms ) ? 'loadAndCheckPerms' : 'load'; 
			return static::$method( Db::i()->select( 'redirect_new_item_id', 'core_item_redirect', array( 'redirect_class=? and redirect_item_id=?', get_called_class(), $id ) )->first() );
		}
		catch( UnderflowException $e )
		{
			throw new OutOfRangeException;
		}
	}

	/**
	 * Get widget sort options
	 *
	 * @return array
	 */
	public static function getWidgetSortOptions(): array
	{
		$sortOptions = array();
		foreach ( array( 'updated', 'title', 'num_comments', 'date', 'views', 'rating_average' ) as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) )
			{
				$sortOptions[ static::$databaseColumnMap[ $k ] ] = 'sort_' . $k;
			}
		}

		return $sortOptions;
	}

	/**
	 * Give a content item the opportunity to filter similar content
	 * 
	 * @note Intentionally blank but can be overridden by child classes
	 * @return array|NULL
	 */
	public function similarContentFilter(): ?array
	{
		return NULL;
	}

	/**
	 * Return the form to merge two content items
	 *
	 * @return Form
	 */
	public function mergeForm(): Form
	{
		$class = $this;

		$form = new Form( 'form', 'merge' );
		$form->class = 'ipsForm--vertical ipsForm--merge';
		$form->add( new UrlForm( 'merge_with', NULL, TRUE, array(), function ( $val ) use ( $class )
		{
			/* Load it */
			try
			{
				$toMerge = $class::loadFromUrl( $val );

				if ( !$toMerge->canView() )
				{
					throw new OutOfRangeException;
				}

				/* Make sure the URL matches the content type we're merging */
				foreach( array( 'app', 'module', 'controller') as $index )
				{
					if( $toMerge->url()->hiddenQueryString[ $index ] != $val->hiddenQueryString[ $index ] )
					{
						throw new OutOfRangeException;
					}
				}
			}
			catch ( OutOfRangeException $e )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_url_bad_item', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title, FALSE, array( 'strtolower' => TRUE ) ) ) ) ) );
			}
			
			/* Make sure it isn't the same */
			if ( $toMerge == $class )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'cannot_merge_with_self' ) );
			}
			/* Or that it's a redirect link that is pointing to ourself */
			elseif( isset( static::$databaseColumnMap['moved_to'] ) AND $movedTo = $toMerge->mapped('moved_to') )
			{
				$movedToData	= explode( '&', $movedTo );
				$idColumn		= static::$databaseColumnId;

				if( $movedToData[0] == $class->$idColumn )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'cannot_merge_with_link_to_self' ) );
				}
			}
			/* Or that we're not a redirect link pointing to it */
			elseif( isset( static::$databaseColumnMap['moved_to'] ) AND $movedTo = $class->mapped('moved_to') )
			{
				$movedToData	= explode( '&', $movedTo );
				$idColumn		= static::$databaseColumnId;

				if( $movedToData[0] == $toMerge->$idColumn )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'cannot_merge_with_link_to_self' ) );
				}
			}

			/* And that we have permission */
			if ( !$toMerge->canMerge() )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'no_merge_permission', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title, FALSE, array( 'strtolower' => TRUE ) ) ) ) ) );
			}
		
		} ) );
		Member::loggedIn()->language()->words['merge_with_desc'] = Member::loggedIn()->language()->addToStack( 'merge_with__desc', FALSE, array( 'sprintf' => array( $this->definiteArticle(), $this->mapped( 'title' ) ) ) );
		if ( isset( static::$databaseColumnMap['moved_to'] ) )
		{
			$form->add( new Checkbox( 'move_keep_link' ) );
			
			if ( Settings::i()->topic_redirect_prune )
			{
				Member::loggedIn()->language()->words['move_keep_link_desc'] = Member::loggedIn()->language()->addToStack( '_move_keep_link_desc', FALSE, array( 'pluralize' => array( Settings::i()->topic_redirect_prune ) ) );
			}
		}

		return $form;
	}

	/**
	 * Produce a random hex color for a background
	 *
	 * @return string
	 */
	public function coverPhotoBackgroundColor(): string
	{
		return $this->staticCoverPhotoBackgroundColor( $this->mapped('title') );
	}

	/**
	 * WHERE clause for getting items for digest (permissions are already accounted for)
	 *
	 * @return	array
	 */
	public static function digestWhere(): array
	{
		return array( );
	}

	/**
	 * Webhook filters
	 *
	 * @return	array
	 */
	public function webhookFilters(): array
	{
		$filters = parent::webhookFilters();
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Lockable' ) )
		{
			$filters['locked'] = $this->locked();
		}
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Pinnable' ) )
		{
			$filters['pinned'] = (bool) $this->mapped('pinned');
		}
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Featurable' ) )
		{
			$filters['featured'] = (bool) $this->mapped('featured');
		}
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Polls' ) )
		{
			$filters['hasPoll'] = (bool) $this->mapped('poll');
		}

		return $filters;
	}


	public static string $itemMenuKey = 'moderator_actions';
	public static string $itemMenuCss = 'ipsButton ipsButton--text';

	/**
	 * Build the moderation menu links
	 *
	 * @param Member|null $member
	 * @return Menu
	 */
	public function menu( Member $member = null ): Menu
	{
		$member = $member ?: Member::loggedIn();
		$menu = new Menu( name: static::$itemMenuKey, css: static::$itemMenuCss );

		if( $this->canEdit( $member ) )
		{
			$menu->add( new ContentMenuLink( $this->url()->setQueryString( 'do', 'edit' ), 'edit', identifier: 'edit' ) );
		}
		
		if( $member->modPermission('can_manage_deleted_content') AND $this->hidden() == -2 )
		{
			$restore = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'restore' ) ), languageString: 'restore_as_visible', identifier: 'restore' );
			$restore->requiresConfirm( 'restore_as_visible_desc' );
			$menu->add( $restore );

			$menu->add( new ContentMenuLink( $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'restoreAsHidden' ) ), 'restore_as_hidden', identifier: 'restore_hidden' ) );
			$menu->add( new ContentMenuLink( $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete', 'immediate' => 1 ) ), 'delete_immediately', identifier: 'delete_immediately' ) );
		}
		else
		{
			if( !$this::$firstCommentRequired and $this->canReportOrRevoke() === TRUE )
			{
				$report = new ContentMenuLink( url: $this->url('report'), languageString: 'report', identifier: 'report' );
				if( $member->member_id OR Captcha::supportsModal() )
				{
					$report->opensDialog(title: 'report', remoteSubmit: TRUE );
				}
				$menu->add( $report );
			}

			if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() AND static::canFuturePublish($member, $this->container()))
			{
				$menu->add( new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'action' => 'publish' ) ), languageString: 'publish', dataAttributes: ['data-confirm','title=\'{lang="publish_desc"}\''], identifier: 'publish' ) );
			}


			if( IPS::classUsesTrait( $this, 'IPS\Content\Pinnable' ) AND $this->canPin( $member ) )
			{
				$menu->add( new ContentMenuLink( $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'pin' ) ), 'pin', identifier: 'pin'  ) );
			}

			if( IPS::classUsesTrait( $this, 'IPS\Content\Pinnable' ) AND $this->canUnpin( $member ) )
			{
				$menu->add( new ContentMenuLink( $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unpin' ) ), 'unpin', identifier: 'unpin' ) );
			}
			
			if( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
			{
				if( $this->canHide($member))
				{
					$menu->add( new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'hide' ) ), languageString:  'hide' , dataAttributes: [
						'data-ipsDialog' => 'true',
						'data-ipsDialog-size' => 'medium',
						'data-ipsDialog-title' => Member::loggedIn()->language()->addToStack('hide'),
						'data-ipsDialog-destructOnClose' => 'true'
					], identifier: 'hide' ) );
				}

				if( $this->canUnhide($member) )
				{
					$menu->add( new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), languageString: $this->hidden() === 1 ? 'approve' : 'unhide', dataAttributes: [
						'data-ipsDialog-destructOnClose' => 'true'
					], identifier: 'unhide' ) );
				}
			}

			if( IPS::classUsesTrait( $this, 'IPS\Content\Lockable' ) )
			{
				if( $this->canLock( $member ) )
				{
					$lock = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'lock' ) ), languageString: 'lock', identifier: 'lock' );
					if( $member->modPermission('can_manage_alerts') AND $this->author()->member_id )
					{
						$lock->addAttribute( 'data-ipsDialog')
									  ->addAttribute( 'data-ipsDialog-size', 'medium')
									  ->addAttribute( 'data-ipsDialog-title', Member::loggedIn()->language()->addToStack( 'lock'))
									  ->addAttribute( 'data-ipsDialog-destructOnClose', 'true');
					}
					$menu->add( $lock );
				}

				if( $this->canUnlock( $member ) )
				{
					$menu->add( new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unlock' ) ), languageString: 'unlock' ) );
				}
			}

			if( $this->canMove( $member ) )
			{
				$menu->add( new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'move' ) ), languageString: 'move' , opensDialog: true ) );
			}

			if( $this->canMerge( $member ) )
			{
				$menu->add( new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'merge' ) ), languageString: 'merge', opensDialog: true ) );
			}

			if( isset( static::$archiveClass ) )
			{
				if( $this->canUnarchive( $member ) )
				{
					$unarchive = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'unarchive' ) ), languageString: 'unarchive');
					$unarchive->addAttribute( 'data-confirm')
									   ->addAttribute( 'data-confirmSubMessage', $this->unarchiveBlurb() );
					$menu->add( $unarchive );
				}

				if( $this->canRemoveArchiveExcludeFlag( $member ) )
				{
					$menu->add( new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'removeArchiveExcludeFlag' ) ), languageString: 'remove_archive_exclude_flag') );
				}
			}

			if( IPS::classUsesTrait( $this, 'IPS\Content\Featurable' ) )
			{
				if( $this->canFeature( $member ) )
				{
					$class = get_class( $this );
					$column = $class::$databaseColumnId;
					$id = $this->$column;

					if ( !$this->isFeatured() )
					{
						$feature = new ContentMenuLink( url: $this->url()->setQueryString( array( 'do' => 'feature', 'fromItem' => 1 ) ), languageString: 'promote_social_button' );
						$feature->addAttribute( 'data-ipsDialog-flashMessage', Member::loggedIn()->language()->addToStack( 'promote_flash_msg' ) )
							->addAttribute( 'data-ipsDialog-flashMessageTimeout', 5 )
							->addAttribute( 'data-ipsDialog-flashMessageEscape', 'false' )
							->addAttribute( 'data-ipsDialog' )
							->addAttribute( 'data-ipsDialog-size', 'large' )
							->addAttribute( 'data-ipsDialog-title', Member::loggedIn()->language()->addToStack( 'promote_social_button' ) );
					}
					else
					{
						$feature = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'unfeature', 'fromItem' => 1 ) ), languageString: 'demote_social_button' );
						$feature->addAttribute( 'data-ipsDialog-flashMessage', Member::loggedIn()->language()->addToStack( 'demote_flash_msg' ) )
							->addAttribute( 'data-ipsDialog-flashMessageTimeout', 5 )
							->addAttribute( 'data-ipsDialog-flashMessageEscape', 'false' )
							->addAttribute( 'data-confirm' );
					}

					$menu->add( $feature );
				}
			}

			if( $this->canDelete( $member ) )
			{
				$delete = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' =>'delete' ) ), languageString: 'delete');
				$delete->addAttribute( 'data-confirm' );
				$menu->add( $delete );
			}

			if( $member->modPermission('can_view_moderation_log') )
			{
				$menu->addSeparator();

				$analytics = new ContentMenuLink( url:$this->url()->setQueryString( array( 'do' => 'analytics' ) ), languageString: 'analytics_and_stats' );
				$analytics->opensDialog( 'analytics_and_stats', 'large' );
				$menu->add( $analytics );
				$menu->add( new ContentMenuLink( url:$this->url()->setQueryString( array( 'do' => 'modlog' ) ), languageString: 'moderation_history'  , opensDialog: true ) );
			}

			if( $this->canOnMessage( 'add', $member ) )
			{
				$menu->add( new ContentMenuLink( url: $this->url()->setQueryString( array( 'do' => 'messageForm' ) ), languageString: 'add_message' , opensDialog: true ) );
			}

			if( $this->canToggleItemModeration( $member ) )
			{
				$toggle = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'toggleItemModeration' ) ), languageString:  $this->itemModerationEnabled() ?'disable_topic_moderation' : 'enable_topic_moderation' );
				$toggle
					->addAttribute( 'data-confirm')
					->addAttribute( 'data-confirmSubMessage', Member::loggedIn()->language()->addToStack( $this->itemModerationEnabled() ? 'disable_topic_moderation_confirm' : 'enable_topic_moderation_confirm' ) );
				$menu->add( $toggle );
			}

			if ( method_exists( $this,'availableSavedActions' )AND $actions = $this->availableSavedActions() )
			{
				$menu->addSeparator();
				foreach ( $actions as $action )
				{
					$actionLink = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'savedAction', 'action' => $action->_id ) ), languageString:  $action->_title );
					$actionLink->addAttribute( 'data-confirm' );
					$menu->add( $actionLink );
				}
			}
		}

		foreach( $this->ui( 'menuItems', array(), TRUE ) as $key => $link )
		{
			$menu->add( $link );
		}

		return $menu;
	}

	/**
	 * Return badges that should be displayed with the content header
	 *
	 * @return array
	 */
	public function badges() : array
	{
		$return = array();

		if( IPS::classUsesTrait( $this, Lockable::class ) AND $this->locked() )
		{
			$return['locked'] = new Icon( 'ipsBadge--locked', 'fa-solid fa-lock', Member::loggedIn()->language()->addToStack( 'locked' ) );
		}

        if( IPS::classUsesTrait( $this, Polls::class ) AND $this->mapped( 'poll' ) )
        {
            $return['poll'] = new Icon( 'ipsBadge--poll', 'fa-solid fa-chart-simple', Member::loggedIn()->language()->addToStack( 'topic_has_poll' ) );
        }

		if( IPS::classUsesTrait( $this, FuturePublishing::class ) AND $this->isFutureDate() )
		{
			$return['future'] = new Icon( Badge::BADGE_WARNING, 'fa-regular fa-clock', $this->futureDateBlurb() );
		}

		if( IPS::classUsesTrait( $this, Hideable::class ) )
		{
			if( $this->hidden() === -1 )
			{
				$return['hidden'] = new Icon( Badge::BADGE_WARNING, 'fa-solid fa-eye-slash', Member::loggedIn()->language()->addToStack( 'hidden_awaiting_approval' ) );
			}
			elseif( $this->hidden() === -2 )
			{
				$return['hidden'] = new Icon( Badge::BADGE_WARNING, 'fa-solid fa-trash', $this->deletedBlurb() );
			}
			elseif( $this->hidden() === 1 )
			{
				$return['hidden'] = new Icon( Badge::BADGE_WARNING, 'fa-solid fa-triangle-exclamation', Member::loggedIn()->language()->addToStack( 'pending_approval' ) );
			}
		}

        if ( IPS::classUsesTrait( $this, MetaData::class ) AND static::supportedMetaDataTypes() !== NULL AND in_array( 'core_ItemModeration', static::supportedMetaDataTypes() ) )
        {
            if( $this->canToggleItemModeration() AND $this->itemModerationEnabled() )
            {
                $return['moderation'] = new Icon(Badge::BADGE_WARNING, 'fa-solid fa-user-times', Member::loggedIn()->language()->addToStack('topic_moderation_enabled') );
            }
        }

		if( IPS::classUsesTrait( $this, Pinnable::class ) AND $this->pinned() )
		{
			$return['pinned'] = new Icon( 'ipsBadge--pinned', 'fa-solid fa-thumbtack', Member::loggedIn()->language()->addToStack( 'pinned' ) );
		}

		if( IPS::classUsesTrait( $this, Featurable::class ) AND $this->isFeatured() )
		{
			$return['featured'] = new Icon( 'ipsBadge--featured', 'fa-solid fa-star', Member::loggedIn()->language()->addToStack( 'featured' ) );
		}

        if( IPS::classUsesTrait( $this, Solvable::class ) AND $this->isSolved() )
        {
            $return['solved'] = new Icon( 'ipsBadge--solved', 'fa-solid fa-check', Member::loggedIn()->language()->addToStack( 'this_is_solved' ) );
        }

        /* Allow for UI extension */
        foreach( UiExtension::i()->run( $this, 'badges' ) as $badge )
        {
            $return[] = $badge;
        }

		return $return;
	}

	/**
	 * Is Spam
	 *
	 * @return	bool
	 */
	public function isSpam(): bool
	{
		return isset( $this->markedSpam()['date'] ) AND isset( $this->markedSpam()['member'] );
	}

	/**
	 * @brief	Marked Spam Data
	 */
	protected null|array $_markedSpam = null;

	/**
	 * @brief	Comments marked spam
	 */
	protected null|array $_commentsMarkedSpam = null;

	/**
	 * Marked Spam
	 *
	 * @return	array|null
	 */
	public function markedSpam(): ?array
	{
		return Bridge::i()->markedSpam( $this );
	}

	/**
	 * Comments Marked Spam
	 *
	 * @return	array
	 */
	public function commentsMarkedSpam(): array
	{
		return Bridge::i()->commentsMarkedSpam( $this );
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL				$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return    array
	 * @apiresponse	int							id				ID number
	 * @apiresponse	string|null					title			Title ( if available )
	 * @apiresponse	\IPS\Node\Model				container		Container
	 * @apiresponse	\IPS\Member					author			The member that created the item
	 * @apiresponse	datetime					date			Date
	 * @apiresponse	string						content			Content
	 * @apiresponse	string						url				URL
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'id'		=> $this->id,
			'title'		=> $this->title ? $this->title : NULL,
			'container'	=> $this->containerWrapper()?->apiOutput( $authorizedMember ),
			'author'	=> $this->author()->apiOutput( $authorizedMember ),
			'date'		=> DateTime::ts( $this->date )->rfc3339(),
			'content'	=> $this->content(),
			'url'		=> (string) $this->url()
		);
	}
}