<?php

/**
 * @brief		Converter phpBB Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use DomainException;
use Exception;
use IPS\Application\Module;
use IPS\Content\Search\Index;
use IPS\convert\Login\HashCryptPrivate;
use IPS\convert\Software;
use IPS\core\Ignore;
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
use PasswordHash;
use UnderflowException;
use function count;
use function defined;
use function is_null;
use function is_numeric;
use function strlen;
use function unserialize;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PhpBB Core Converter
 */
class Phpbb extends Software
{
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "phpBB (3.1.x/3.2.x/3.3.x)";
	}
	
	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "phpbb";
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertEmoticons'			=> array(
				'table'		=> 'smilies',
				'where'		=> NULL
			),
			'convertProfileFields'	=> array(
				'table'		=> 'profile_fields',
				'where'		=> NULL
			),
			'convertGroups'			=> array(
				'table'		=> 'groups',
				'where'		=> NULL
			),
			'convertMembers'			=> array(
				'table'		=> 'users',
				'where'		=> array( "user_type<>?", 2 )
			),
			'convertIgnoredUsers'		=> array(
				'table'		=> 'zebra',
				'where'		=> array( "foe=?", 1 )
			),
			'convertPrivateMessages'	=> array(
				'table'		=> 'privmsgs',
				'where'		=> NULL
			),
			'convertPrivateMessageReplies'	=> array(
				'table'		=> 'privmsgs',
				'where'		=> NULL,
			),
			'convertProfanityFilters'	=> array(
				'table'		=> 'words',
				'where'		=> NULL
			),
			'convertBanfilters'		=> array(
				'table'			=> 'banfilters',
				'where'			=> NULL,
				'extra_steps'	=> array( 'convertBanfilters2' ),
			),
			'convertBanfilters2'		=> array(
				'table'			=> 'disallow',
				'where'			=> NULL,
			)
		);
	}

	/**
	 * Allows software to add additional menu row options
	 *
	 * @return    array
	 */
	public function extraMenuRows(): array
	{
		$rows = array();
		$rows['convertBanfilters2'] = array(
			'step_title'		=> 'convert_banfilters',
			'step_method'		=> 'convertBanfilters2',
			'ips_rows'			=> Db::i()->select( 'COUNT(*)', 'core_banfilters' ),
			'source_rows'		=> array( 'table' => static::canConvert()['convertBanfilters2']['table'], 'where' => static::canConvert()['convertBanfilters2']['where'] ),
			'per_cycle'			=> 200,
			'dependencies'		=> array( 'convertBanfilters' ),
			'link_type'			=> 'core_banfilters'
		);

		return $rows;
	}

	/**
	 * Count Source Rows for a specific step
	 *
	 * @param string $table		The table containing the rows to count.
	 * @param string|array|NULL $where		WHERE clause to only count specific rows, or NULL to count all.
	 * @param bool $recache	Skip cache and pull directly (updating cache)
	 * @return    integer
	 * @throws	\IPS\convert\Exception
	 */
	public function countRows( string $table, string|array|null $where=NULL, bool $recache=FALSE ): int
	{
		switch( $table )
		{
			case 'banfilters':
			case 'disallow':
				try
				{
					$count = 0;
					$count += $this->db->select( 'COUNT(*)', 'banlist', array( "ban_userid=?", 0 ) )->first();
					$count += $this->db->select( 'COUNT(*)', 'disallow' )->first();
				}
				catch( Exception $e )
				{
					throw new \IPS\convert\Exception( sprintf( Member::loggedIn()->language()->get( 'could_not_count_rows' ), $table ) );
				}
				return $count;
			
			default:
				return parent::countRows( $table, $where, $recache );
		}
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
		return 'convert_phpbb_preconvert';
	}
	
	/**
	 * List of conversion methods that require additional information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array(
			'convertEmoticons',
			'convertProfileFields',
			'convertGroups',
			'convertMembers',
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
			case 'convertEmoticons':
				$return['convertEmoticons'] = array(
					'emoticon_path'				=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> Member::loggedIn()->language()->addToStack('convert_phpbb_emoticons'),
						'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new DomainException( 'path_invalid' ); } },
					),
					'keep_existing_emoticons'	=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Checkbox',
						'field_default'		=> TRUE,
						'field_required'	=> FALSE,
						'field_extra'		=> array(),
						'field_hint'		=> NULL,
					)
				);
				break;
			
			case 'convertProfileFields':
				$return['convertProfileFields'] = array();
				
				$options = array();
				$options['none'] = Member::loggedIn()->language()->addToStack( 'none' );
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_pfields_data' ), 'IPS\core\ProfileFields\Field' ) AS $field )
				{
					$options[$field->_id] = $field->_title;
				}
				
				foreach( $this->db->select( '*', 'profile_fields' ) AS $field )
				{
					Member::loggedIn()->language()->words["map_pfield_{$field['field_id']}"]		= $this->db->select( 'lang_name', 'profile_lang', array( "field_id=?", $field['field_id'] ) )->first();
					Member::loggedIn()->language()->words["map_pfield_{$field['field_id']}_desc"]	= Member::loggedIn()->language()->addToStack( 'map_pfield_desc' );
					
					$return['convertProfileFields']["map_pfield_{$field['field_id']}"] = array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Select',
						'field_default'		=> NULL,
						'field_required'	=> FALSE,
						'field_extra'		=> array( 'options' => $options ),
						'field_hint'		=> NULL,
					);
				}
				break;
			
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
					Member::loggedIn()->language()->words["map_group_{$group['group_id']}"]			= $group['group_name'];
					Member::loggedIn()->language()->words["map_group_{$group['group_id']}_desc"]	= Member::loggedIn()->language()->addToStack( 'map_group_desc' );
					
					$return['convertGroups']["map_group_{$group['group_id']}"] = array(
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

				try
				{
					/* Try to auto detect the salt */
					$salt = $this->db->select( 'config_value', 'config', array( 'config_name=?', 'avatar_salt' ) )->first();

					if( empty( $salt ) )
					{
						throw new UnderflowException();
					}
				}
				catch( UnderflowException $e )
				{
					/* We can only retain one type of photo */
					$return['convertMembers']['photo_hash'] = array(
						'field_class'			=> 'IPS\\Helpers\\Form\\Text',
						'field_default'			=> NULL,
						'field_required'		=> TRUE,
						'field_extra'			=> array(),
						'field_hint'			=> Member::loggedIn()->language()->addToStack('convert_phpbb_randstring'),
					);
				}
				
				/* Find out where the photos live */
				Member::loggedIn()->language()->words['photo_location_desc'] = Member::loggedIn()->language()->addToStack( 'photo_location_nodb_desc' );
				$return['convertMembers']['photo_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> TRUE,
					'field_extra'			=> array(),
					'field_hint'			=> Member::loggedIn()->language()->addToStack('convert_phpbb_avatars'),
					'field_validation'		=> function( $value ) { if ( !@is_dir( $value ) ) { throw new DomainException( 'path_invalid' ); } },
				);
				
				$return['convertMembers']['gallery_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> FALSE,
					'field_extra'			=> array(),
					'field_hint'			=> Member::loggedIn()->language()->addToStack('convert_phpbb_avatargallery'),
					'field_validation'		=> function( $value ) { if ( $value AND !@is_dir( $value ) ) { throw new DomainException( 'path_invalid' ); } },
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

		Task::queue( 'convert', 'RebuildProfilePhotos', array( 'app' => $this->app->app_id ), 5, array( 'app' ) );
		Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_message_posts', 'extension' => 'core_Messaging' ), 2, array( 'app', 'link', 'extension' ) );
		Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_members', 'extension' => 'core_Signatures' ), 2, array( 'app', 'link', 'extension' ) );
		
		/* Content Counts */
		Task::queue( 'core', 'RecountMemberContent', array( 'app' => $this->app->app_id ), 4, array( 'app' ) );

		/* Attachments */
		Task::queue( 'core', 'RebuildAttachmentThumbnails', array( 'app' => $this->app->app_id ), 1, array( 'app' ) );

		/* First Post Data */
		Task::queue( 'convert', 'RebuildConversationFirstIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		
		return array( "f_search_index_rebuild", "f_clear_caches", "f_rebuild_pms", "f_signatures_rebuild" );
	}

	/**
	 * Strip the bbcode UID
	 *
	 * @param	string	$post	Post text
	 * @param	string	$uid	BBCode uid
	 * @note	PHPBB does bbcode like '[b:lsu27ljs]Bold text here[/b:lsu27ljs]' so we need to strip these uids to parse properly
	 * @return	string
	 */
	public static function stripUid( string $post, string $uid ) : string
	{
		return str_replace( ":" . $uid . "]", "]", $post );
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
		/* Convert newlines to <br> tags */
		$post = nl2br( $post );

		/* Preserve code data */
		$codeboxes = array();
		preg_match_all( "/\[code(.*?)\](.+?)\[\/code\]/ims", $post, $matches );

		foreach( $matches[0] as $k => $m )
		{
			$c = count( $codeboxes );
			$codeboxes[ $c ] = $m;

			$replacement = '__CODE_BOX_' . $c . '__';

			$post = str_replace( $m, $replacement, $post );
		}

		/* Convert HTML entities back into actual entities */
		$post = html_entity_decode( $post, ENT_COMPAT | ENT_HTML401, "UTF-8" );

		/* Put codeboxes back */
		foreach( $codeboxes as $k => $v )
		{
			$post = str_replace( '__CODE_BOX_' . $k . '__', $v, $post );
		}

		/* Sort out emoticons */
		$post = preg_replace( "/<!-- s(\S+?) --><img(?:[^<]+?)<!-- (?:\S+?) -->/", '$1', $post );

		/* PhpBB 3.2 */
		$search = array( '<r>', '<t>', '<s>', '<e>', '<code>', '</r>', '</t>', '</s>', '</e>', '</img>', '</url>', '</quote>', '</link_text>', '</code>' );
		$post = str_ireplace( $search, '', $post );
		$post = preg_replace( '/<url url=\"([^\]]+?)"([^>]+?)?>/i', '', $post );
		$post = preg_replace( '/<img src=\"([^\]]+?)"([^>]+?)?>/i', '', $post );
		$post = preg_replace( '/<quote author=\"([^\]]+?)"([^>]+?)?>/i', '', $post );
		$post = preg_replace( '/<link_text text=\"([^\]]+?)"([^>]+?)?>/i', '', $post );

		/* Rework quotes so they'll match our parser style */
		$post = str_replace( '][quote', "]\n[quote", $post );
		$post = preg_replace( "#\[quote=(.+)\]#", "[quote name=$1]", $post );

		/* Convert size tags */
		$post = preg_replace_callback( '/\[size=(\d+)\]/', function( $match ) {
			return '[size=' . ceil( ( $match[1] / 100 ) * 4 ) . ']';
		}, $post );

		/* Get rid of extra list markup we don't need */
		$post = str_replace( array( "[/*]", "[/*:m]" ), "", $post );
		$post = str_replace( array( "[/list:o]", "[/list:u]" ), "[/list]", $post );

		/* Sort out media */
		$post = preg_replace( "#<!-- m --><a class=\"postlink\" href=\"([^\]]+?)\"([^>]+?)?>([^\]]+?)</a><!-- m -->#i", "$1", $post );

		/* And sort out URLs */
		$post = preg_replace( "#<a class=\"postlink\" href=\"([^\]]+?)\"([^>]+?)?>([^\]]+?)</a>#i", "[url=$1]$3[/url]", $post );

		return $post;
	}

	/**
	 * Convert emoticons
	 *
	 * @return	void
	 */
	public function convertEmoticons() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'smiley_id' );
		
		foreach( $this->fetch( 'smilies', 'smiley_id' ) AS $row )
		{
			$libraryClass->convertEmoticon( array(
				'id'		=> $row['smiley_id'],
				'typed'		=> $row['code'],
				'filename'	=> $row['smiley_url'],
				'emo_position'	=> $row['smiley_order'],
				'width'		=> $row['smiley_width'],
				'height'	=> $row['smiley_height'],
			), array(
				'set'		=> md5( 'Converted' ),
				'title'		=> 'Converted',
				'position'	=> 1,
			), $this->app->_session['more_info']['convertEmoticons']['keep_existing_emoticons'], $this->app->_session['more_info']['convertEmoticons']['emoticon_path'] );
			
			$libraryClass->setLastKeyValue( $row['smiley_id'] );
		}
	}
	
	/**
	 * Convert profile fields
	 *
	 * @return	void
	 */
	public function convertProfileFields() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'field_id' );
		
		foreach( $this->fetch( 'profile_fields', 'field_id' ) AS $row )
		{
			try
			{
				$name = $this->db->select( 'lang_name', 'profile_lang', array( "field_id=?", $row['field_id'] ) )->first();
			}
			catch( UnderflowException $e )
			{
				$name = $row['field_name'];
			}
			
			$field_type	= explode( '.', $row['field_type'] );
			$field_type	= array_pop( $field_type );
			$default	= $row['field_default_value'];
			
			switch( $field_type )
			{
				case 'bool':
					$type = 'YesNo';
					break;
				
				case 'date':
					$type = 'Date';
					break;
				
				case 'dropdown':
					$type = 'Select';
					
					$options = array();
					
					foreach( $this->db->select( 'option_id, lang_value', 'profile_fields_lang', array( "field_id=?", $row['field_id'] ) )->setKeyField( 'option_id' )->setValueField( 'lang_value' ) AS $key => $value )
					{
						$options[$key] = $value;
					}
					
					$default = json_encode( $options );
					break;
				
				case 'int':
					$type = 'Number';
					break;
				
				case 'text':
					$type = 'TextArea';
					break;
				
				case 'url':
					$type = 'Url';
					break;

				case 'googleplus':
				case 'string':
				default:
					$type = 'Text';
					break;
			}
			
			$info = array(
				'pf_id'				=> $row['field_id'],
				'pf_name'			=> $name,
				'pf_type'			=> $type,
				'pf_content'		=> $default,
				'pf_not_null'		=> $row['field_required'],
				'pf_member_hide'	=> $row['field_hide'] ? 'hide' : 'all',
				'pf_max_input'		=> ( $field_type == 'dropdown' ) ? NULL : $row['field_maxlen'], # ignore this for selects
				'pf_member_edit'	=> $row['field_active'],
				'pf_position'		=> $row['field_order'],
				'pf_show_on_reg'	=> $row['field_show_on_reg'],
				'pf_input_format'	=> $row['field_validation']  ? '/' . preg_quote( $row['field_validation'], '/' ) . '/i' : NULL,
			);
			
			$merge = ( $this->app->_session['more_info']['convertProfileFields']["map_pfield_{$row['field_id']}"] != 'none' ) ? $this->app->_session['more_info']['convertProfileFields']["map_pfield_{$row['field_id']}"] : NULL;
			
			$libraryClass->convertProfileField( $info, $merge );
			
			$libraryClass->setLastKeyValue( $row['field_id'] );
		}
	}
	
	/**
	 * Convert groups
	 *
	 * @return	void
	 */
	public function convertGroups() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'group_id' );
		
		foreach( $this->fetch( 'groups', 'group_id' ) AS $row )
		{
			$prefix = '';
			$suffix = '';
			
			if ( $row['group_colour'] )
			{
				$prefix = "<span style='color: {$row['group_colour']}'>";
				$suffix = "</span>";
			}
			
			$info = array(
				'g_id'				=> $row['group_id'],
				'g_name'			=> $row['group_name'],
				'g_use_pm'			=> $row['group_receive_pm'],
				'g_max_messages'	=> $row['group_message_limit'],
				'g_max_mass_pm'		=> $row['group_max_recipients'],
				'prefix'			=> $prefix,
				'suffix'			=> $suffix,
			);
			
			$merge = ( $this->app->_session['more_info']['convertGroups']["map_group_{$row['group_id']}"] != 'none' ) ? $this->app->_session['more_info']['convertGroups']["map_group_{$row['group_id']}"] : NULL;
			
			$libraryClass->convertGroup( $info, $merge );
			$libraryClass->setLastKeyValue( $row['group_id'] );
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
		$libraryClass::setKey( 'user_id' );

		try
		{
			/* Try to auto-detect the salt */
			$avatarSalt = $this->db->select( 'config_value', 'config', array( 'config_name=?', 'avatar_salt' ) )->first();

			if( empty( $avatarSalt ) )
			{
				throw new UnderflowException();
			}
		}
		catch( UnderflowException $e )
		{
			$avatarSalt = rtrim( $this->app->_session['more_info']['convertMembers']['photo_hash'], '_' );
		}
		
		foreach( $this->fetch( 'users', 'user_id', array( "user_type<>?", 2 ) ) AS $row )
		{
			/* Work out birthday */
			$bday_day	= NULL;
			$bday_month	= NULL;
			$bday_year	= NULL;
			
			if ( $row['user_birthday'] AND $row['user_birthday'] != " 0- 0-   0"  )
			{
				list( $bday_day, $bday_month, $bday_year ) = explode( '-', $row['user_birthday'] );
				
				if ( trim( $bday_day, ' -' ) == '0' )
				{
					$bday_day = NULL;
				}
				
				if ( trim( $bday_month, ' -' ) == '0' )
				{
					$bday_month = NULL;
				}
				
				if ( trim( $bday_year ) == '0' )
				{
					$bday_year = NULL;
				}
			}
			
			/* Work out secondary groups */
			$groups = array();
			foreach( $this->db->select( 'group_id', 'user_group', array( "user_id=? AND group_id!=? AND user_pending=?", $row['user_id'], $row['group_id'], 0 ) ) AS $group )
			{
				$groups[] = $group;
			}
			
			/* Work out timezone - we don't really need to create an instance of \DateTimeZone here, however it helps check for invalid ones */
			try
			{
				$timezone = new DateTimeZone( $row['user_timezone'] );
			}
			catch( Exception $e )
			{
				$timezone = 'UTC';
			}
			
			/* Work out banned stuff */
			$temp_ban = 0;
			try
			{
				$ban = $this->db->select( '*', 'banlist', array( "ban_userid=?", $row['user_id'] ) )->first();
				
				if ( $ban['ban_end'] == 0 )
				{
					$temp_ban = -1;
				}
				else
				{
					$temp_ban = $ban['ban_end'];
				}
			}
			catch( UnderflowException $e ) {}
			
			/* Array of basic data */
			$info = array(
				'member_id'				=> $row['user_id'],
				'email'					=> $row['user_email'],
				'name'					=> $row['username'],
				'password'				=> $row['user_password'],
				'member_group_id'		=> $row['group_id'],
				'joined'				=> $row['user_regdate'],
				'ip_address'			=> $row['user_ip'],
				'warn_level'			=> $row['user_warnings'],
				'warn_lastwarn'			=> $row['user_last_warning'],
				'bday_day'				=> $bday_day,
				'bday_month'			=> $bday_month,
				'bday_year'				=> $bday_year,
				'msg_count_new'			=> $row['user_unread_privmsg'],
				'msg_count_total'		=> $this->db->select( 'COUNT(*)', 'privmsgs_to', array( "user_id=?", $row['user_id'] ) )->first(),
				'last_visit'			=> $row['user_lastvisit'],
				'last_activity'			=> $row['user_lastmark'],
				'mgroup_others'			=> $groups,
				'timezone'				=> $timezone,
				'allow_admin_mails'		=> (bool)$row['user_allow_massemail'],
				'members_disable_pm'	=> ( $row['user_allow_pm'] ) ? 0 : 1,
				'member_posts'			=> $row['user_posts'],
				'member_last_post'		=> $row['user_lastpost_time'],
				'signature'				=> static::stripUid( $row['user_sig'], $row['user_sig_bbcode_uid'] ),
				'temp_ban'				=> $temp_ban,
			);
			
			/* Profile Photos */
			$filepath = NULL;
			$filename = NULL;
			
			if ( $row['user_avatar_type'] )
			{
				/* Is it numeric? Apparently phpBB doesn't clean up after itself. */
				if ( is_numeric( $row['user_avatar_type'] ) )
				{
					switch( $row['user_avatar_type'] )
					{
						case 1:
							$row['user_avatar_type'] = 'avatar.driver.upload';
							break;
						
						case 2:
							$row['user_avatar_type'] = 'avatar.driver.remote';
							break;
						
						case 3:
							$row['user_avatar_type'] = 'avatar.driver.gallery';
							break;
					}
				}
				
				switch( $row['user_avatar_type'] )
				{
					case 'avatar.driver.upload':
						$fileext = explode( '.', $row['user_avatar'] );
						$fileext = array_pop( $fileext );
						$filepath = $this->app->_session['more_info']['convertMembers']['photo_location'];
						$filename = $avatarSalt . '_' . $row['user_id'] . '.' . $fileext;
						break;
					
					case 'avatar.driver.remote':
						/* The library uses file_get_contents() so we can just pop the file name off and pass the URL directly */
						$filebits = explode( "/", $row['user_avatar'] );
						$filename = array_pop( $filebits );
						$filepath = implode( '/', $filebits );
						break;

					case 'avatar.driver.local':
					case 'avatar.driver.gallery':
						/* I couldn't figure out how to set these up so they are probably wrong */
						$filepath = $this->app->_session['more_info']['convertMembers']['gallery_location'];
						$filename = $row['user_avatar'];
						break;
				}
			}
			
			/* Profile Fields */
			$pfields = array();
			try
			{
				$userfields = $this->db->select( '*', 'profile_fields_data', array( "user_id=?", $row['user_id'] ) )->first();
			}
			catch( UnderflowException $e ) {}
			
			foreach( $this->db->select( '*', 'profile_fields' ) AS $field )
			{
				/* if this is a select field, we need to pull the value from profile_fields_lang */
				$field_type = explode( '.', $field['field_type'] );
				$field_type = array_pop( $field_type );
				
				if ( isset( $userfields['pf_' . $field['field_name'] ] ) )
				{
					if ( $field_type == 'dropdown' )
					{
						try
						{
							/* phpBB stores the option incremented from 1 - however the options are stored incremented from 0. So if you select the first option, 1 is stored but profile_fields_lang actually has it as 0. I don't get it either. */
							$pfields[ $field['field_id'] ] = $this->db->select( 'lang_value', 'profile_fields_lang', array( "field_id=? AND option_id=?", $field['field_id'], $userfields['pf_' . $field['field_name'] ] - 1 ) )->first();
						}
						catch( UnderflowException $e ) { }
					}
					else
					{
						$pfields[ $field['field_id'] ] = $userfields['pf_' . $field['field_name'] ];
					}
				}
			}

			/* PM Folders */
			$pmFolders = iterator_to_array( $this->db->select( 'folder_name', 'privmsgs_folder', array( 'user_id=?', $row['user_id'] ) ) );
			$info['pconversation_filters'] = count( $pmFolders ) ? $pmFolders : NULL;
			
			/* Finally */
			$libraryClass->convertMember( $info, $pfields, $filename, $filepath );
			
			/* Friends */
			foreach( $this->db->select( '*', 'zebra', array( "user_id=? AND friend=?", $row['user_id'], 1 ) ) AS $follower )
			{
				$libraryClass->convertFollow( array(
					'follow_app'			=> 'core',
					'follow_area'			=> 'member',
					'follow_rel_id'			=> $follower['zebra_id'],
					'follow_rel_id_type'	=> 'core_members',
					'follow_member_id'		=> $follower['user_id'],
				) );
			}
			
			/* Warnings */
			foreach( $this->db->select( '*', 'warnings', array( "user_id=? AND post_id<>?", $row['user_id'], 0 ) ) AS $warn )
			{
				try
				{
					$log	= $this->db->select( '*', 'log', array( "log_id=?", $warn['log_id'] ) )->first();
					$data	= @unserialize( $log['log_data'] );
				}
				catch( UnderflowException $e )
				{
					$log	= array( 'user_id' => 0 );
					$data	= array( 0 => NULL );
				}
				
				$warnId = $libraryClass->convertWarnLog( array(
						'wl_id'					=> $warn['warning_id'],
						'wl_member'				=> $warn['user_id'],
						'wl_moderator'			=> $log['user_id'],
						'wl_date'				=> $warn['warning_time'],
						'wl_points'				=> 1,
						'wl_note_member'		=> $data[0] ?? NULL,
					) );

				/* Add a member history record for this member */
				$libraryClass->convertMemberHistory( array(
						'log_id'		=> 'w' . $warn['warning_id'],
						'log_member'	=> $warn['user_id'],
						'log_by'		=> $log['user_id'],
						'log_type'		=> 'warning',
						'log_data'		=> array( 'wid' => $warnId ),
						'log_date'		=> $warn['warning_time']
					)
				);
			}
			
			$libraryClass->setLastKeyValue( $row['user_id'] );
		}
	}
	
	/**
	 * Convert ignored users
	 *
	 * @return	void
	 */
	public function convertIgnoredUsers() : void
	{
		$libraryClass = $this->getLibrary();
		
		foreach( $this->fetch( 'zebra', 'user_id', array( "foe=?", 1 ) ) AS $row )
		{
			$info = array(
				'ignore_id'			=> $row['user_id'] . '-' . $row['zebra_id'],
				'ignore_owner_id'	=> $row['user_id'],
				'ignore_ignore_id'	=> $row['zebra_id'],
			);
			
			foreach( Ignore::types() AS $type )
			{
				$info['ignore_' . $type] = 1;
			}
			
			$libraryClass->convertIgnoredUser( $info );
		}
	}
	
	/**
	 * Convert profannity filters
	 *
	 * @return	void
	 */
	public function convertProfanityFilters() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'word_id' );
		
		foreach( $this->fetch( 'words', 'word_id' ) AS $row )
		{
			$libraryClass->convertProfanityFilter( array(
				'wid'		=> $row['word_id'],
				'type'		=> $row['word'],
				'swop'		=> $row['replacement'],
				'm_exact'   => TRUE
			) );
			
			$libraryClass->setLastKeyValue( $row['word_id'] );
		}
	}
	
	/**
	 * Convert ban filters
	 *
	 * @return	void
	 */
	public function convertBanfilters() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'ban_id' );
		
		foreach( $this->fetch( 'banlist', 'ban_id', array( "ban_userid=?", 0 ) ) AS $row )
		{
			$type = NULL;
			if ( $row['ban_ip'] )
			{
				$type = 'ip';
			}
			else if ( $row['ban_email'] )
			{
				$type = 'email';
			}
			
			/* If Type is null - skip */
			if ( is_null( $type ) )
			{
				$libraryClass->setLastKeyValue( $row['ban_id'] );
				continue;
			}
			
			$libraryClass->convertBanfilter( array(
				'ban_id'		=> 'b' . $row['ban_id'],
				'ban_type'		=> $type,
				'ban_content'	=> ( $type == 'ip' ) ? $row['ban_ip'] : $row['ban_email'],
				'ban_date'		=> $row['ban_start'],
				'ban_reason'	=> $row['ban_give_reason']
			) );
			
			$libraryClass->setLastKeyValue( $row['ban_id'] );
		}
	}
	
	/**
	 * Convert ban filters (second time)
	 *
	 * @return	void
	 */
	public function convertBanfilters2() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'disallow_id' );
		
		foreach( $this->fetch( 'disallow', 'disallow_id' ) AS $row )
		{
			$libraryClass->convertBanfilter( array(
				'ban_id'		=> 'd' . $row['disallow_id'],
				'ban_content'	=> $row['disallow_username'],
				'ban_type'		=> 'name',
				'ban_date'		=> time(),
			) );
			
			$libraryClass->setLastKeyValue( $row['disallow_id'] );
		}
	}
	
	/**
	 * Convert PMs
	 *
	 * @return	void
	 */
	public function convertPrivateMessages() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'msg_id' );
		
		foreach( $this->fetch( 'privmsgs', 'msg_id' ) AS $row )
		{
			/* Set up the topic */
			$topic = array(
				'mt_id'				=> $row['msg_id'],
				'mt_date'			=> $row['message_time'],
				'mt_title'			=> $row['message_subject'],
				'mt_starter_id'		=> $row['author_id'],
				'mt_start_time'		=> $row['message_time'],
				'mt_last_post_time'	=> $row['message_time'],
			);
			
			/* Now the maps */
			$maps = array();
			
			/* First one first initial author */
			$maps[ $row['author_id'] ] = array(
				'map_user_id'			=> $row['author_id'],
				'map_is_starter'		=> 1,
				'map_last_topic_reply'	=> $row['message_time'],
			);

			/* Fetch recipients for the message if it has been moved to a custom folder */
			$messageTo	= explode( ':', $row['to_address'] );

			foreach( $messageTo as $messageRecipient )
			{
				$messageRecipient	= (int) mb_substr( $messageRecipient, 2 );

				$maps[ $messageRecipient ] = array(
					'map_user_id'			=> $messageRecipient,
					'map_is_starter'		=> 0,
					'map_last_topic_reply'	=> $row['message_time'],
				);
			}
			
			/* Now everyone else */
			foreach( $this->db->select( '*', 'privmsgs_to', array( "msg_id=?", $row['msg_id'] ) ) AS $to )
			{
				/* If the user already exists just skip. This wouldn't normally matter, however if the sender has moved the message
					to a custom folder then they will have a row in privmsgs_to and we will end up resetting map_is_starter to 0 otherwise */
				if( isset( $maps[ $to['user_id'] ] ) )
				{
					if( $to['folder_id'] > 0 )
					{
						$maps[ $to['user_id'] ]['map_folder_id'] = $this->_getPmFolder( $to['user_id'], $to['folder_id'] );
					}
					continue;
				}

				$maps[ $to['user_id'] ] = array(
					'map_user_id'			=> $to['user_id'],
					'map_is_starter'		=> 0,
					'map_last_topic_reply'	=> $row['message_time'],
					'map_folder_id'			=> $to['folder_id'] > 0 and $this->_getPmFolder( $to['user_id'], $to['folder_id'] )
				);
			}
			
			$libraryClass->convertPrivateMessage( $topic, $maps );
			
			$libraryClass->setLastKeyValue( $row['msg_id'] );
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
		
		$libraryClass::setKey( 'msg_id' );
		
		foreach( $this->fetch( 'privmsgs', 'msg_id' ) AS $row )
		{
			$libraryClass->convertPrivateMessageReply( array(
				'msg_id'			=> $row['msg_id'],
				'msg_topic_id'		=> $row['msg_id'],
				'msg_post'			=> static::stripUid( $row['message_text'], $row['bbcode_uid'] ),
				'msg_date'			=> $row['message_time'],
				'msg_author_id'		=> $row['author_id'],
				'msg_ip_address'	=> $row['author_ip'],
			) );
			
			$libraryClass->setLastKeyValue( $row['msg_id'] );
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

		if( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'memberlist.php' ) !== FALSE )
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
	 * @brief	Lookup cache
	 */
	protected static array $pmFolders = array();

	/**
	 * @brief	PhpBB Source Folders
	 */
	protected static ?array $phpBbFolders = NULL;

	/**
	 * Get array key for the folder
	 *
	 * @param	int			$user			User ID
	 * @param	string		$folderId		Folder Name
	 * @return	boolean
	 */
	protected function _getPmFolder( int $user, string $folderId ) : bool
	{
		/* Check for cached folder info */
		if( isset( static::$pmFolders[ $user ][ $folderId ] ) )
		{
			return static::$pmFolders[ $user ][ $folderId ];
		}

		/* Check for cached phpBB data */
		if( static::$phpBbFolders ===  NULL )
		{
			static::$phpBbFolders = iterator_to_array( $this->db->select( 'folder_id,folder_name', 'privmsgs_folder' )->setKeyField( 'folder_id' )->setValueField( 'folder_name' ) );
		}

		try
		{
			/* Does the referenced folder exist in PhpBB? */
			if( !isset( static::$phpBbFolders[ $folderId ] ) )
			{
				throw new OutOfRangeException;
			}

			$member = Member::load( $this->app->getLink( $user, 'core_members' ) );
			$filters = json_decode( $member->pconversation_filters, TRUE );

			/* No PM Folders or it's a guest */
			if( $filters === NULL OR !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch( OutOfRangeException $e )
		{
			return static::$pmFolders[ $user ][ $folderId ] = FALSE;
		}

		/* Search PM folders and return match */
		return static::$pmFolders[ $user ][ $folderId ] = array_search( static::$phpBbFolders[ $folderId ], $filters );
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
		$password = html_entity_decode( $password );
		$success = FALSE;
		$hash = preg_replace('/^\$CP\$/', '', $member->conv_password ); // Remove the prefix.

		/* phpBB 3.1 */
		if( preg_match( '/^\$2[ay]\$(0[4-9]|[1-2][0-9]|3[0-1])\$[a-zA-Z0-9.\/]{53}/', $hash ) )
		{
			require_once \IPS\ROOT_PATH . "/applications/convert/sources/Login/PasswordHash.php";
			$ph = new PasswordHash( 8, TRUE );
			$success = $ph->CheckPassword( $password, $member->conv_password );
		}

		$hashLibrary = new HashCryptPrivate;
		if ( $success === FALSE )
		{
			/* phpBB3 */
			$single_md5_pass = md5( $password );
			$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

			if ( strlen( $hash ) == 34 )
			{
				$success = Login::compareHashes( $hash, $hashLibrary->hashCryptPrivate( $password, $hash, $itoa64 ) );
			}
			else
			{
				$success = Login::compareHashes( $hash, $single_md5_pass );
			}
		}

		/* phpBB2 */
		if ( !$success )
		{
			$success = Login::compareHashes( $hash, $hashLibrary->hashCryptPrivate( $single_md5_pass, $hash, $itoa64 ) );
		}

		/* Fallback to password_verify(), this applies to PhpBB 3.3+ where Argon may be used */
		if( !$success )
		{
			$success = password_verify( $password, $member->conv_password );
		}

		return $success;
	}
}