<?php
/**
 * @brief		Saved Action Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		31 Jan 2014
 */

namespace IPS\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Data\Store;
use IPS\Db;
use IPS\File;
use IPS\forums\Topic\Post;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use function defined;
use function in_array;
use function is_array;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Saved Action Node
 */
class SavedAction extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'forums_topic_mmod';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'saved_actions';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'mm_id';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'forums',
		'module'	=> 'forums',
		'prefix'	=> 'savedActions_'
	);
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'forums_mmod_';
	
	/**
	 * Get available saved actions for a forum
	 *
	 * @param Forum $forum	The forum
	 * @param	Member|NULL	$member	The member (NULL for currently logged in)
	 * @return	array
	 */
	public static function actions( Forum $forum, Member $member = NULL ): array
	{
		$return = array();
		$member = $member ?: Member::loggedIn();
		
		if ( $member->modPermission('can_use_saved_actions') and Topic::modPermission( 'use_saved_actions', $member, $forum ) )
		{
			foreach ( static::getStore() as $action )
			{
				if ( $action['mm_enabled'] and ( $action['mm_forums'] == '*' or in_array( $forum->id, array_filter( explode( ',', $action['mm_forums'] ) ) ) ) )
				{
					$return[ $action['mm_id'] ] = static::constructFromData( $action );
				}
			}
		}
		
		return $return;
	}
	
		
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		return $this->mm_enabled;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( bool|int $enabled ) : void
	{
		$this->mm_enabled	= $enabled;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->addHeader( 'settings' );
		$form->add( new Translatable( 'mm_title', NULL, TRUE, array( 'app' => 'forums', 'key' => ( $this->mm_id ? "forums_mmod_{$this->mm_id}" : NULL ) ) ) );
		$form->add( new Node( 'mm_forums', ( $this->mm_id and $this->mm_forums != '*' ) ? $this->mm_forums : 0, FALSE, array( 'class' => 'IPS\forums\Forum', 'multiple' => TRUE, 'zeroVal' => 'all' ) ) );
		
		$form->addHeader( 'topic_properties' );
		$form->add( new Radio( 'topic_state', $this->mm_id ? $this->topic_state : 'leave', FALSE, array( 'options' => array( 'leave' => 'mm_leave', 'open' => 'unlock', 'close' => 'lock' ) ) ) );
		$form->add( new Radio( 'topic_pin', $this->mm_id ? $this->topic_pin : 'leave', FALSE, array( 'options' => array( 'leave' => 'mm_leave', 'pin' => 'pin', 'unpin' => 'unpin' ) ) ) );
		$form->add( new Radio( 'topic_approve', $this->mm_id ? $this->topic_approve : 0, FALSE, array( 'options' => array( 0 => 'mm_leave', 1 => 'unhide', 2 => 'hide' ) ) ) );
		$form->add( new Node( 'topic_move', ( $this->mm_id and $this->topic_move != -1 ) ? $this->topic_move : 0, FALSE, array( 'class' => 'IPS\forums\Forum', 'zeroVal' => 'topic_move_none', 'zeroValTogglesOff' => array( 'topic_move_link' ), 'permissionCheck' => function ( $forum )
					{
						return $forum->sub_can_post and !$forum->redirect_url;
					} ) ) );
		$form->add( new YesNo( 'topic_move_link', $this->mm_id ? $this->topic_move_link : TRUE, FALSE, array(), NULL, NULL, NULL, 'topic_move_link' ) );
		
		$form->addHeader( 'topic_title' );
		$form->add( new Text( 'topic_title_st', $this->mm_id ? $this->topic_title_st : NULL, FALSE, array( 'trim' => FALSE ) ) );
		$form->add( new Text( 'topic_title_end', $this->mm_id ? $this->topic_title_end : NULL, FALSE, array( 'trim' => FALSE ) ) );
		
		$form->addHeader( 'add_reply' );
		$form->add( new YesNo( 'topic_reply', $this->mm_id ? $this->topic_reply : FALSE, FALSE, array( 'togglesOn' => array( 'topic_reply_content_editor', 'topic_reply_postcount' ) ) ) );
		$form->add( new Editor( 'topic_reply_content', $this->mm_id ? $this->topic_reply_content : FALSE, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => ( $this->mm_id ? "forums-mmod-{$this->mm_id}" : "forums-new-mmod" ), 'attachIds' => $this->mm_id ? array( $this->mm_id, NULL, 'mmod' ) : NULL ), NULL, NULL, NULL, 'topic_reply_content_editor' ) );
		$form->add( new YesNo( 'topic_reply_postcount', $this->mm_id ? $this->topic_reply_postcount : TRUE, FALSE, array(), NULL, NULL, NULL, 'topic_reply_postcount' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['mm_forums'] ) )
		{
			if ( $values['mm_forums'] == 0 )
			{
				$values['mm_forums'] = '*';
			}
			else 
			{
				$forums = array();
				foreach ( $values['mm_forums'] as $forum )
				{
					$forums[] = $forum->_id;
				}
				
				$values['mm_forums'] = ( implode( ',', $forums ) );
			}
		}

		if( isset( $values['topic_move'] ) )
		{
			if ( !is_object( $values['topic_move'] ) and $values['topic_move'] == 0 )
			{
				$values['topic_move'] = -1;
			}
			else
			{
				$values['topic_move'] = $values['topic_move']->_id;
			}
		}
				
		if ( !$this->mm_id )
		{
			$this->mm_enabled = $values['mm_enabled'] = TRUE;
			$this->save();
			File::claimAttachments( 'forums-new-mmod', $this->mm_id, NULL, 'forumsSavedAction' );
		}

		if ( isset( $values['mm_title'] ) )
		{
			Lang::saveCustom( 'forums', "forums_mmod_{$this->mm_id}", $values['mm_title'] );
			unset( $values['mm_title'] );
		}

		return $values;
	}
	
	/**
	 * Check Permissions and run the saved action
	 *
	 * @param Topic $topic	The topic to run on
	 * @param	Member|NULL	$member	Member running (NULL for currently logged in member)
	 * @return	void
	 */
	public function runOn( Topic $topic, Member $member = NULL ) : void
	{
		/* Permission Checks */
		$member = $member ?: Member::loggedIn();

		/* Check the member can use saved actions, and they can moderate in this forum */
		if ( !$member->modPermission('can_use_saved_actions') )
		{
			throw new DomainException('NO_PERMISSION');
		}

		if ( $member->modPermission( 'forums' ) !== -1 AND $member->modPermission( 'forums' ) !== TRUE )
		{
			if ( !is_array( $member->modPermission( 'forums' ) ) or !in_array( $topic->container()->_id, $member->modPermission( 'forums' ) ) )
			{
				throw new DomainException('NO_PERMISSION');
			}
		}
		/* Check the action is enabled and allowed for the content item */
		if ( !$this->mm_enabled )
		{
			throw new DomainException('DISABLED');
		}
		if ( $this->mm_forums !== '*' and !in_array( $topic->container()->_id, explode( ',', $this->mm_forums ) ) )
		{
			throw new DomainException('BAD_FORUM');
		}

		$this->_runOn( $topic, $member);
	}

	/**
	 * Run saved action
	 *
	 * @param Topic $topic	The topic to run on
	 * @param	Member|NULL	$member	Member running (NULL for currently logged in member)
	 * @return	void
	 */
	protected function _runOn( Topic $topic, Member $member = NULL ) : void
	{
		/* Archived Topics can't used saved actions */
		if ( $topic->isArchived() )
		{
			throw new DomainException('TOPIC_ARCHIVED');
		}
		/* Open/Close */
		if ( $this->topic_state == 'open' )
		{
			$topic->state = 'open';
		}
		elseif ( $this->topic_state == 'close' )
		{
			$topic->state = 'closed';
		}

		/* Pin/Unpin */
		if ( $this->topic_pin == 'pin' )
		{
			$topic->pinned = TRUE;
		}
		elseif ( $this->topic_pin == 'unpin' )
		{
			$topic->pinned = FALSE;
		}

		/* Title */
		if ( $this->topic_title_st )
		{
			$topic->title = $this->topic_title_st . $topic->title;
		}
		if ( $this->topic_title_end )
		{
			$topic->title .= $this->topic_title_end;
		}

		/* Save */
		$topic->save();

		/* Hide/Unhide */
		if ( $this->topic_approve == 1 )
		{
			$topic->unhide( $member );
		}
		elseif ( $this->topic_approve == 2 )
		{
			$topic->hide( $member );
		}

		/* Reply */
		if ( $this->topic_reply )
		{
			$reply = Post::create( $topic, $this->topic_reply_content, false, null, isset( $this->topic_reply_postcount ) and $this->topic_reply_postcount );
		}

		/* Move */
		if ( $this->topic_move != -1 )
		{
			try
			{
				$topic->move( Forum::load( $this->topic_move ), $this->topic_move_link );
			}
			catch ( Exception $e ) { }
		}
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		File::unclaimAttachments( 'core_Admin', $this->mm_id, NULL, 'forumsSavedAction' );
		parent::delete();
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'forumsSavedActions' );

	/**
	 * Get data store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->forumsSavedActions ) )
		{
			Store::i()->forumsSavedActions = iterator_to_array( Db::i()->select( '*', 'forums_topic_mmod' ) );
		}
		
		return Store::i()->forumsSavedActions;
	}
}