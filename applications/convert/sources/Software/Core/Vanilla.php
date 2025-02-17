<?php
/**
 * @brief		Converter Vanilla Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		IPS Social Suite
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use erusev\Parsedown;
use IPS\Content\Search\Index;
use IPS\convert\Software;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Login;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use PasswordHash;
use function count;
use function defined;
use function is_array;
use function unserialize;
use const PATHINFO_BASENAME;
use const PATHINFO_DIRNAME;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Vanilla Core Converter
 */
class Vanilla extends Software
{
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "Vanilla (3.x)";
	}

	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "vanilla";
	}

	/**
	 * Can we convert settings?
	 *
	 * @return    boolean
	 */
	public static function canConvertSettings(): bool
	{
		return TRUE;
	}

	/**
	 * Settings Map Listing
	 *
	 * @return    array
	 */
	public function settingsMapList(): array
	{
		$settings = array(
			'fluid'	=> array( 'title' => Member::loggedIn()->language()->addToStack( 'use_fluid_view_convert' ), 'value' => true, 'our_key' => 'use_fluid_view_convert', 'our_title' => Member::loggedIn()->language()->addToStack( 'use_fluid_view_convert' ) )
		);
		
		return $settings;
	}

	/**
	 * Convert one or more settings
	 *
	 * @param	array	$settings	Settings to convert
	 * @return	void
	 */
	public function convertSettings( array $settings=array() ) : void
	{
		if ( !isset( $settings['use_fluid_view_convert'] ) OR !$settings['use_fluid_view_convert'] )
		{
			return;
		}

		Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 'fluid' ), array( "conf_key=?", 'forums_default_view' ) );
	}

	/**
	 * Resolve the filesystem location of a file from a path stored in the database
	 * There are several different ways Vanilla may store these, including as remote URL's
	 *
	 * @param   string	$location       File location retrieved from the database
	 * @param   string	$uploadsPath    Configured uploads path
	 * @return  Url|string|null
	 */
	public static function parseMediaLocation( string $location, string $uploadsPath ) : Url|string|null
	{
		$uploadsPath = str_replace( '\\', '/', $uploadsPath );

		// URL
		if ( preg_match( '`^https?://`', $location ) )
		{
			return Url::external( $location );
		}
		// Full filesystem path
		elseif ( mb_strpos( $location, $uploadsPath ) === 0 )
		{
			return $location;
		}
		// Deprecated "plugin based" path
		elseif ( preg_match( '`^~([^/]*)/(.*)$`', $location, $matches ) )
		{
			return rtrim( $uploadsPath, '/' ) . '/' . $matches[2];
		}
		else
		{
			$parts = parse_url( $location );
			if ( empty( $parts['scheme'] ) )
			{
				return rtrim( $uploadsPath, '/' ) . '/' . $location;
			}
			else
			{
				if ( !isset( $parts['path'], $parts['host'] ) )
				{
					return null;
				}

				// This is a url in the format type:://domain/path.
				$result = array(
					'name'   => ltrim( $parts['path'], '/'),
					'type'   => $parts['scheme'],
					'domain' => $parts['host']
				);

				return rtrim( $uploadsPath, '/' ) . '/' . ltrim( $parts['path'], '/' );

				// @TODO: This is deprecated, and I'm not sure what it was for.
				#$format = "{$result['type']}://{$result['domain']}/%s";
				#return sprintf( $format, $result['name'] );
			}
		}
	}

	/**
	 * Attempt to convert a MySQL datetime string into a unix timestamp
	 *
	 * @param   string  $date   Date(Time) string
	 * @return  DateTime    Timestamp on successful conversion, otherwise NULL
	 */
	public static function mysqlToDateTime( string $date ) : DateTime
	{
		return DateTime::ts( strtotime( $date ) ?: time() );
	}

	/**
	 * Content we can convert from this software.
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertGroups'        => array(
				'table'     => 'Role',
				'where'     => NULL
			),
			'convertMembers'       => array(
				'table'         => 'User',
				'where'         => array( "Deleted<>?", 1 ),
			),
			'convertPrivateMessages'  => array(
				'table'     => 'Conversation',
				'where'     => NULL
			),
			'convertPrivateMessageReplies'	=> array(
				'table'		=> 'ConversationMessage',
				'where'		=> NULL,
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
	 * Returns a block of text, or a language string, that explains what the admin must do to start this conversion
	 *
	 * @return    string|null
	 */
	public static function getPreConversionInformation(): ?string
	{
		return 'convert_vanilla_preconvert';
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
		switch( $method )
		{
			case 'convertGroups':
				$return['convertGroups'] = array();

				$options = array();
				$options['none'] = 'None';
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_groups' ), 'IPS\Member\Group' ) AS $group )
				{
					$options[ $group->g_id ] = $group->name;
				}

				foreach( $this->db->select( '*', 'Role' ) AS $group )
				{
					Member::loggedIn()->language()->words["map_group_{$group['RoleID']}"]		= $group['Name'];
					Member::loggedIn()->language()->words["map_group_{$group['RoleID']}_desc"]	= Member::loggedIn()->language()->addToStack( 'map_group_desc' );

					$return['convertGroups']["map_group_{$group['RoleID']}"] = array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Select',
						'field_default'		=> NULL,
						'field_required'	=> FALSE,
						'field_extra'		=> array( 'options' => $options ),
						'field_hint'		=> NULL,
					);
				}
				break;

			case 'convertMembers':
				$return['convertMembers'] = array();

				/* Find out where the photos live */
				Member::loggedIn()->language()->words['attach_location_desc'] = Member::loggedIn()->language()->addToStack( 'attach_location' );
				$return['convertMembers']['attach_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> TRUE,
					'field_extra'			=> array(),
					'field_hint'			=> Member::loggedIn()->language()->addToStack('convert_vanilla_photopath'),
				);
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
		Task::queue( 'convert', 'RebuildProfilePhotos', array( 'app' => $this->app->app_id ), 5, array( 'app' ) );
		Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_message_posts', 'extension' => 'core_Messaging' ), 2, array( 'app', 'link', 'extension' ) );
		Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_members', 'extension' => 'core_Signatures' ), 2, array( 'app', 'link', 'extension' ) );

		/* Content Counts */
		Task::queue( 'core', 'RecountMemberContent', array( 'app' => $this->app->app_id ), 4, array( 'app' ) );

		/* First Post Data */
		Task::queue( 'convert', 'RebuildConversationFirstIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );

		/* Attachments */
		Task::queue( 'core', 'RebuildAttachmentThumbnails', array( 'app' => $this->app->app_id ), 1, array( 'app' ) );

		return array( "f_search_index_rebuild", "f_clear_caches", "f_rebuild_pms", "f_signatures_rebuild", "f_rebuild_attachments" );
	}

	/**
	 * @brief   Store parsedown object
	 */
	protected static ?Parsedown $_parseDown = NULL;

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
		/* Is this Markdown? */
		if( mb_substr( $post, 0, 15 ) == '<!--Markdown-->' )
		{
			/* Load parser */
			if( static::$_parseDown === NULL )
			{
				IPS::$PSR0Namespaces['erusev'] = \IPS\ROOT_PATH . "/applications/convert/sources/Tools/Vanilla/Parsedown";
				static::$_parseDown = new Parsedown();
			}

			$post = str_replace( '<!--Markdown-->', '', $post );
			$post = static::$_parseDown->text( $post );
		}
		elseif( mb_substr( $post, 0, 3 ) == '[{"' ) // Probably a delta
		{
			$postData = str_replace( [ "\r\n","\n" ], '', $post );
			$postData = json_decode( $postData, TRUE );

			$post = '';
			foreach( $postData as $data )
			{
				if( is_array( $data['insert'] ) )
				{
					switch ( $data['insert']['embed-external']['data']['embedType'] )
					{
						case 'quote':
							$post .= '[quote name="' . $data['insert']['embed-external']['data']['insertUser']['name'] . '" timestamp="' . strtotime( $data['insert']['embed-external']['data']['dateInserted'] ) . '"]' . $data['insert']['embed-external']['data']['body'] . "[/quote]";
							break;
					}
				}
				else
				{
					$post .= $data['insert'] . "<br>";
				}
			}
		}

		/* Mentions */
		$matches = [];
		preg_match_all( '/@("|&quot;)([^@"]+)("|&quot;)/i', $post, $matches );

		if( count( $matches ) )
		{
			foreach( $matches[0] as $key => $tag )
			{
				$member = Member::load( $matches[2][ $key ], 'name' );
				if( !$member->member_id )
				{
					continue;
				}

				$post = str_replace( $tag, "[mention={$member->member_id}]{$member->name}[/mention]", $post );
			}
		}

		/**
		 * Vanilla has a quotes plugin that seems to have changed formats quite often. - We'll try to match as many formats as we can
		 */
		$post = preg_replace( '/(?:<blockquote\s+(?:class=\"(?:User)?Quote\")?(?:\s+rel=\"(?:[^\"]+)\")?>)(?:\s+)?<div class=\"QuoteAuthor\">([^\"]+)<\/div>(?:\s+)?<div class=\"QuoteText\">(?:<p>)?(.*?)(?:<\/p>)?<\/div>(?:\s+)?<\/blockquote>/i',
			'[quote name="$1"]$2[/quote]' ,
			$post );

		$post = preg_replace( '/(?:<blockquote\s+(?:class=\"Quote (?:User)?Quote\")?(?:\s+rel=\"(?:[^\"]+)\")?>)(?:\s+)?<div class=\"QuoteText\">(?:<p>)?(.*?)(?:<\/p>)?<\/div>(?:\s+)?<\/blockquote>/i',
			'[quote]$1[/quote]' ,
			$post );

		$post = preg_replace( '/<blockquote\s+rel=\"([^\"]+)\"?>(.*?)<\/blockquote>/i',
			'[quote name="$1"]$2[/quote]' ,
			$post );

		$post = preg_replace( '/<blockquote>(.*?)<\/blockquote>/ims',
			'[quote]$1[/quote]' ,
			$post );

		return $post;
	}

	/**
	 * Convert groups
	 *
	 * @return 	void
	 */
	public function convertGroups() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'r.RoleID' );

		/* Run conversions */
		foreach ( $this->fetch( array( 'Role', 'r' ), 'RoleID' ) as $row )
		{
			$info = array(
				'g_id'      => $row['RoleID'],
				'g_name'    => $row['Name'],
			);
			$merge = ( $this->app->_session['more_info']['convertGroups']["map_group_{$row['RoleID']}"] != 'none' )
				? $this->app->_session['more_info']['convertGroups']["map_group_{$row['RoleID']}"]
				: NULL;

			$libraryClass->convertGroup( $info, $merge );
			$libraryClass->setLastKeyValue( $row['RoleID'] );
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
	 * @return 	void
	 */
	public function convertMembers() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'u.UserID' );

		$uploadsPath = $this->app->_session['more_info']['convertMembers']['attach_location'];

		$users = $this->fetch( array( 'User', 'u' ), 'u.UserID', array( "u.Deleted<>?", 1 ), 'u.*, ur.RoleID' );
		$users->join( array( 'UserRole', 'ur' ), 'ur.UserID=u.UserID' );

		$data = iterator_to_array( $users );
		$this->app->preCacheLinks( $data, [ 'core_groups' => 'RoleID' ] );

		foreach( $data AS $row )
		{
			/* Work out birthday */
			$bdayDay	= NULL;
			$bdayMonth	= NULL;
			$bdayYear	= NULL;

			$dateOfBirth = !empty( $row['DateOfBirth'] ) ? @DateTime::ts( strtotime( $row['DateOfBirth'] ) ) : NULL;
			if ( $dateOfBirth instanceof DateTime )
			{
				$bdayYear  = $dateOfBirth->format( 'Y' );
				$bdayMonth = $dateOfBirth->format( 'n' );
				$bdayDay   = $dateOfBirth->format( 'j' );
			}

			/* Work out banned stuff */
			$tempBan = ( $row['Banned'] == 1 ) ? -1 : 0;

			/* Array of basic data */
			$info = array(
				'member_id'       => $row['UserID'],
				'email'           => $row['Email'],
				'name'            => $row['Name'],
				'password'        => $row['Password'],
				'member_group_id'    => $row['RoleID'] ?: NULL,
				'joined'          => static::mysqlToDateTime( $row['DateInserted'] ),
				'ip_address'      => $row['InsertIPAddress'],
				'temp_ban'        => $tempBan,
				'bday_day'        => $bdayDay,
				'bday_month'      => $bdayMonth,
				'bday_year'       => $bdayYear,
				'msg_count_new'   => (int) $row['CountUnreadDiscussions'],
				'msg_count_total' => (int) $row['CountDiscussions'],
				'last_visit'      => static::mysqlToDateTime( $row['DateLastActive'] ),
				'timezone'        => !empty( $row['HourOffset'] )
					? (int) $row['HourOffset']
					: new DateTimeZone(
						'UTC'
					),
				'member_posts'    => (int) $row['CountComments'],
			);

			/* Profile Photos */
			$filepath = NULL;
			$filename = NULL;

			if ( !empty( $row['Photo'] ) AND ( $location = static::parseMediaLocation( $row['Photo'], $uploadsPath ) ) )
			{
				if ( $location instanceof Url )
				{
					/* The library uses file_get_contents() so we can just pop the file name off and pass the URL directly */
					$filebits = explode( '/', (string) $location );
					$filename = array_pop( $filebits );
					$filepath = implode( '/', $filebits );
				}
				else
				{
					// Full-sized profile photos start with "p", small profile photos start with "n"
					$filename = 'p' . pathinfo( $location, PATHINFO_BASENAME );
					$filepath = pathinfo( $location, PATHINFO_DIRNAME );
				}

			}

			/* Finally */
			$libraryClass->convertMember( $info, array(), $filename, $filepath );
			$libraryClass->setLastKeyValue( $row['UserID'] );
		}
	}

	/**
	 * Convert PMs
	 *
	 * @return 	void
	 */
	public function convertPrivateMessages() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'ConversationID' );

		$data = iterator_to_array( $this->fetch( 'Conversation', 'ConversationID' ) );
		$this->app->preCacheLinks( $data, [ 'core_members' => 'InsertUserID' ] );

		/* Run conversions */
		foreach ( $data as $row )
		{
			/* Message topic information */
			//$firstMessageId = $row['FirstMessageID'];
			$topicInfo = array(
				'mt_id'             => $row['ConversationID'],
				'mt_title'          => $row['Subject'] ?: NULL,
				'mt_date'           => static::mysqlToDateTime( $row['DateInserted'] ),
				'mt_starter_id'     => $row['InsertUserID'],
				'mt_last_post_time' => static::mysqlToDateTime( $row['DateUpdated'] ),
				'mt_to_count'       => ( isset( $row['CountParticipants'] ) ) ? $row['CountParticipants'] : count( unserialize( $row['Contributors'] ) ),
				'mt_replies'        => $row['CountMessages'],
			);

			/* Message maps */
			$maps = array();
			$rows = iterator_to_array( $this->db->select( '*', 'UserConversation', array( 'ConversationID=?', $row['ConversationID'] ) ) );
			$this->app->preCacheLinks( $rows, [ 'core_members' => 'UserID' ] );
			foreach ( $rows as $r )
			{
				$maps[ $r['UserID'] ] = array(
					'map_user_id'           => $r['UserID'],
					'map_read_time'         => static::mysqlToDateTime( $r['DateLastViewed'] ),
					//'map_folder_id'        => TODO: Create a Bookmarks folder for "Bookmarked" conversations?
					'map_user_active'       => $r['Deleted'] == 0 ? 1 : 0,
					'map_last_topic_reply'  => static::mysqlToDateTime( $r['DateConversationUpdated'] )
				);
			}

			$libraryClass->convertPrivateMessage( $topicInfo, $maps );
			$libraryClass->setLastKeyValue( $row['ConversationID'] );
		}
	}

	/**
	 * Convert PM replies
	 *
	 * @return 	void
	 */
	public function convertPrivateMessageReplies() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'MessageID' );

		$data = iterator_to_array( $this->fetch( 'ConversationMessage', 'MessageID' ) );
		$this->app->preCacheLinks( $data, [ 'core_members' => 'InsertUserID', 'core_message_topics' => 'ConversationID' ] );

		foreach( $data AS $row )
		{
			// Add Format Type (for later processing) if Markdown
			if( $row['Format'] == 'Markdown' )
			{
				$row['Body'] = '<!--Markdown-->' . $row['Body'];
			}

			$libraryClass->convertPrivateMessageReply( array(
				'msg_id'            => $row['MessageID'],
				'msg_topic_id'      => $row['ConversationID'],
				'msg_date'          => static::mysqlToDateTime( $row['DateInserted'] ),
				'msg_post'          => $row['Body'],
				'msg_author_id'     => $row['InsertUserID'],
				'msg_ip_address'    => $row['InsertIPAddress']
			) );
			
			$libraryClass->setLastKeyValue( $row['MessageID'] );
		}
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
		/* Vanilla 2.2 */
		if( preg_match( '/^\$2[ay]\$(0[4-9]|[1-2][0-9]|3[0-1])\$[a-zA-Z0-9.\/]{53}/', $member->conv_password ) OR mb_substr( $member->conv_password, 0, 3 ) == '$P$' )
		{
			require_once \IPS\ROOT_PATH . "/applications/convert/sources/Login/PasswordHash.php";
			$ph = new PasswordHash( 8, TRUE );
			return $ph->CheckPassword( $password, $member->conv_password );
		}

		if ( Login::compareHashes( $member->conv_password, md5( md5( str_replace( '&#39;', "'", html_entity_decode( $password ) ) ) . $member->misc ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}