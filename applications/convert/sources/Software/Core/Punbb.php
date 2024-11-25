<?php

/**
 * @brief		Converter Punbb Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application\Module;
use IPS\Content\Search\Index;
use IPS\convert\Software;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Task;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PunBB Core Converter
 */
class Punbb extends Software
{
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "PunBB (1.x)";
	}
	
	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "punbb";
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertGroups'			=> array(
				'table'						=> 'groups',
				'where'						=> NULL,
			),
			'convertMembers'			=> array(
				'table'						=> 'users',
				'where'						=> NULL,
			),
			'convertPrivateMessages'	=> array(
				'table'						=> 'messages',
				'where'						=> NULL,
			),
			'convertPrivateMessageReplies'	=> array(
				'table'						=> 'messages',
				'where'						=> NULL
			)
		);
	}
	
	/**
	 * Can we convert passwords from this software.
	 *
	 * @return    boolean
	 */
	public static function loginEnabled(): bool
	{
		return TRUE;
	}

	/**
	 * List of conversion methods that require additional information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array(
			'convertGroups',
			'convertMembers'
		);
	}
	
	/**
	 * Get More Information
	 *
	 * @param string $method	Conversion method
	 * @return    array|null
	 */
	public function getMoreInfo( string $method ): ?array
	{
		$return = array();
		
		switch( $method )
		{
			case 'convertGroups':
				$return['convertGroups'] = array();
				
				$options = array();
				$options['none'] = 'None';
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_groups' ), 'IPS\Member\Group' ) AS $group )
				{
					$options[$group->g_id] = $group->name;
				}
				
				foreach( $this->db->select( '*', 'groups' ) AS $group )
				{
					Member::loggedIn()->language()->words["map_group_{$group['g_id']}"]			= $group['g_title'];
					Member::loggedIn()->language()->words["map_group_{$group['g_id']}_desc"]	= Member::loggedIn()->language()->addToStack( 'map_group_desc' );
					
					$return['convertGroups']["map_group_{$group['g_id']}"] = array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Select',
						'field_default'		=> NULL,
						'field_required'	=> FALSE,
						'field_extra'		=> array( 'options' => $options ),
						'field_hint'		=> NULL,
					);
				}
				break;
			
			case 'convertMembers':
				/* Pseudo Profile Fields */
				foreach( [ 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'location', 'title' ] AS $field )
				{
					Member::loggedIn()->language()->words["field_{$field}"]		= Member::loggedIn()->language()->addToStack( 'pseudo_field', FALSE, array( 'sprintf' => ucwords( $field ) ) );
					Member::loggedIn()->language()->words["field_{$field}_desc"]	= Member::loggedIn()->language()->addToStack( 'pseudo_field_desc' );
					$return['convertMembers']["field_{$field}"] = array(
						'field_class'			=> 'IPS\\Helpers\\Form\\Radio',
						'field_default'			=> 'no_convert',
						'field_required'		=> TRUE,
						'field_extra'			=> array(
							'options'				=> array(
								'no_convert'			=> Member::loggedIn()->language()->addToStack( 'no_convert' ),
								'create_field'			=> Member::loggedIn()->language()->addToStack( 'create_field' ),
							),
							'userSuppliedInput'		=> 'create_field'
						),
						'field_hint'			=> NULL
					);
				}
				break;
		}
		
		return ( isset( $return[ $method ] ) ) ? $return[ $method ] : array();
	}
	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		/* Search Index Rebuild */
		Index::i()->rebuild();
		
		/* Clear Cache and Store */
		Store::i()->clearAll();
		Cache::i()->clearAll();
		
		/* Non-Content Rebuilds */
		Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_message_posts', 'extension' => 'core_Messaging' ), 2, array( 'app', 'link', 'extension' ) );
		Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_members', 'extension' => 'core_Signatures' ), 2, array( 'app', 'link', 'extension' ) );
		
		/* Content Counts */
		Task::queue( 'core', 'RecountMemberContent', array( 'app' => $this->app->app_id ), 4, array( 'app' ) );

		/* First Post Data */
		Task::queue( 'convert', 'RebuildConversationFirstIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		
		return array( "f_search_index_rebuild", "f_clear_caches", "f_rebuild_pms", "f_signatures_rebuild" );
	}

	/**
	 * Pre-process content for the Invision Community text parser
	 *
	 * @param	string			$post	The post
	 * @param	string|null		$className Classname passed by post-conversion rebuild
	 * @param	int|null		$contentId ID passed by post-conversion rebuild
	 * @return	string			The converted post
	 */
	public static function fixPostData( string $post, ?string $className=null, ?int $contentId=null ): string
	{
		$post = nl2br( $post );
			
		$post = preg_replace( "#\[quote=(.+)\]#", "[quote name=$1]", $post );
		return $post;
	}
	
	/**
	 * Convert groups
	 *
	 * @return	void
	 */
	public function convertGroups() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'g_id' );
		
		foreach( $this->fetch( 'groups', 'g_id' ) AS $row )
		{
			$info = array(
				'g_id'					=> $row['g_id'],
				'g_name'				=> $row['g_title'],
				'g_view_board'			=> $row['g_read_board'],
				'g_mem_info'			=> $row['g_view_users'],
				'g_is_supmod'			=> $row['g_moderator'],
				'g_use_search'			=> $row['g_search'],
				'g_edit_posts'			=> $row['g_edit_posts'],
				'g_use_pm'				=> $row['g_pm'],
				'g_pm_flood_mins'		=> ( $row['g_email_flood'] ) ? ceil( $row['g_email_flood'] / 60 ) : 0,
				'g_delete_own_posts'	=> $row['g_delete_posts'],
				'g_avoid_flood'			=> ( $row['g_post_flood'] == 0 ) ? 1 : 0,
				'g_max_messages'		=> $row['g_pm_limit'],
				'prefix'				=> ( $row['g_color'] ) ? "<span style='color:{$row['g_color']}'>" : NULL,
				'suffix'				=> ( $row['g_color'] ) ? "</span>" : NULL,
				'g_search_flood'		=> $row['g_search_flood'],
			);
			
			$merge = ( $this->app->_session['more_info']['convertGroups']["map_group_{$row['g_id']}"] != 'none' ) ? $this->app->_session['more_info']['convertGroups']["map_group_{$row['g_id']}"] : NULL;
			
			$libraryClass->convertGroup( $info );
			
			$libraryClass->setLastKeyValue( $row['g_id'] );
		}

		/* Now check for group promotions */
		if( count( $libraryClass->groupPromotions ) )
		{
			foreach( $libraryClass->groupPromotions as $groupPromotion )
			{
				$libraryClass->convertGroupPromotion( $groupPromotion );
			}
		}
	}
	
	/**
	 * Convert members
	 *
	 * @return	void
	 */
	public function convertMembers() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'users', 'id' ) AS $row )
		{
			$info = array(
				'member_id'				=> $row['id'],
				'email'					=> $row['email'],
				'name'					=> $row['username'],
				'password'				=> $row['password'],
				'conv_password_extra'		=> $row['salt'],
				'member_group_id'		=> $row['group_id'],
				'joined'				=> $row['registered'],
				'ip_address'			=> $row['registration_ip'],
				'last_visit'			=> $row['last_visit'],
				'last_activity'			=> $row['last_visit'],
				'auto_track'			=> ( $row['auto_notify'] ) ? [ 'content' => 1, 'comments' => 1, 'method' => 'immediate' ] : 0,
				'members_bitoptions'	=> [ 'view_sigs' => $row['show_sig'] ],
				'signature'				=> $row['signature'],
				'timezone'				=> $row['timezone'],
				'member_posts'			=> $row['num_posts'],
			);
			
			/* Pseudo Fields */
			$profileFields = array();
			foreach( [ 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'location', 'title' ] AS $pseudo )
			{
				/* Are we retaining? */
				if ( $this->app->_session['more_info']['convertMembers']["field_{$pseudo}"] == 'no_convert' )
				{
					/* No, skip */
					continue;
				}
				
				try
				{
					/* We don't actually need this, but we need to make sure the field was created */
					$fieldId = $this->app->getLink( $pseudo, 'core_pfields_data' );
				}
				catch( OutOfRangeException $e )
				{
					$libraryClass->convertProfileField( array(
						'pf_id'				=> $pseudo,
						'pf_name'			=> $this->app->_session['more_info']['convertMembers']["field_{$pseudo}"],
						'pf_desc'			=> '',
						'pf_type'			=> 'Text',
						'pf_content'		=> '[]',
						'pf_member_hide'	=> 'all',
						'pf_max_input'		=> 255,
						'pf_member_edit'	=> 1,
						'pf_show_on_reg'	=> 0,
					) );
				}
				
				$profileFields[$pseudo] = $row[$pseudo];
			}
			
			$libraryClass->convertMember( $info, $profileFields );
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
	
	/**
	 * Convert private messages
	 *
	 * @return	void
	 */
	public function convertPrivateMessages() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'messages', 'id' ) AS $row )
		{
			/* PunBB does not have conversations, so convert each one into and individual PM topic */
			$topic = array(
				'mt_id'				=> $row['id'],
				'mt_date'			=> $row['posted'],
				'mt_title'			=> $row['subject'],
				'mt_starter_id'		=> $row['sender_id'],
				'mt_start_time'		=> $row['posted'],
				'mt_last_post_time'	=> $row['posted'],
				'mt_to_count'		=> ( $row['sender_id'] == $row['owner'] ) ? 1 : 2,
				'mt_replies'		=> 1,
			);
			
			$maps = array();
			
			/* Sender */
			$maps[$row['sender_id']] = array(
				'map_user_id'			=> $row['sender_id'],
				'map_is_starter'		=> 1,
				'map_last_topic_reply'	=> $row['posted'],
			);
			
			/* Recipient... if it isn't the sender */
			if ( $row['sender_id'] != $row['owner'] )
			{
				$maps[$row['owner']] = array(
					'map_user_id'			=> $row['owner'],
					'map_is_starter'		=> 0,
					'map_last_topic_reply'	=> $row['posted']
				);
			}
			
			/* Simples! */
			$libraryClass->convertPrivateMessage( $topic, $maps );
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Convert PM replies
	 *
	 * @return	void
	 */
	public function convertPrivateMessageReplies() : void
	{
		$libraryClass = $this->getLibrary();

		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'messages', 'id' ) AS $row )
		{
			$libraryClass->convertPrivateMessageReply( array(
				'msg_id'			=> $row['id'],
				'msg_topic_id'		=> $row['id'],
				'msg_date'			=> $row['posted'],
				'msg_post'			=> $row['message'],
				'msg_author_id'		=> $row['sender_id'],
				'msg_ip_address'	=> $row['sender_ip'],
				'msg_is_first_post'	=> 1,
			) );

			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return    Url|NULL
	 */
	public function checkRedirects(): ?Url
	{
		/* If we can't access profiles, don't bother trying to redirect */
		if( !Member::loggedIn()->canAccessModule( Module::get( 'core', 'members' ) ) )
		{
			return NULL;
		}

		$url = Request::i()->url();

		if( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'profile.php' ) !== FALSE )
		{
			try
			{
				$data = (string) $this->app->getLink( Request::i()->u, array( 'members', 'core_members' ) );
				return Member::load( $data )->url();
			}
			catch( Exception $e )
			{
				return NULL;
			}
		}

		return NULL;
	}

	/**
	 * Process a login
	 *
	 * @param	Member		$member			The member
	 * @param	string			$password		Password from form
	 * @return	bool
	 */
	public static function login( Member $member, string $password ) : bool
	{
		$success = FALSE;

		if ( mb_strlen( $member->conv_password ) == 40 )
		{
			/* Password with salt */
			$success = Login::compareHashes( $member->conv_password, sha1( $member->conv_password_extra . sha1( $password ) ) );

			if ( !$success )
			{
				/* No salt */
				$success = Login::compareHashes( $member->conv_password, sha1( $password ) );
			}
		}
		else
		{
			/* MD5 */
			$success = Login::compareHashes( $member->conv_password, md5( $password ) );
		}

		return $success;
	}
}