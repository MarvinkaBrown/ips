<?php
/**
 * @brief		Bulkmail central library
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Jun 2013
 */

namespace IPS\core\BulkMail;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\File;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Settings;
use function defined;
use function is_array;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Bulk mail central library
 */
class Bulkmailer extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_bulk_mail';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'mail_';

	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	Flag to denote just getting the count only.
	 */
	const GET_COUNT_ONLY = 1;
	
	/**
	 * Get the mail options
	 *
	 * @return	array
	 */
	public function get__options() : array
	{
		if ( !isset( $this->_data['opts'] ) )
		{
			return array();
		}
		
		return json_decode( $this->_data['opts'], TRUE );
	}

	/**
	 * Set the mail options
	 *
	 * @param	array	$value	Mail options
	 * @return	void
	 */
	public function set__options( array $value ) : void
	{
		$this->opts	= json_encode( $value );
	}

	/**
	 * Retrieve the query to fetch members based on our filters
	 *
	 * @param	array|int|null	$option		A constant to fetch counts only or an array with the limit to apply to the query
	 * @return	Select
	 */
	public function getQuery( array|int|null $option=NULL ) : Select
	{
		/* Compile where */
		$where = array();
		$where[] = array( "core_members.allow_admin_mails=1" );
		$where[] = array( "core_members.temp_ban=0" );
		$where[] = array( "core_members.email!=''" );
		$where[] = array( '( ! ' . Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) . ' )' );

		foreach ( Application::allExtensions( 'core', 'MemberFilter', FALSE, 'core' ) as $key => $extension )
		{
			if( $extension->availableIn( 'bulkmail' ) )
			{
				/* Grab our fields and add to the form */
				if( !empty( $this->_options[ $key ] ) )
				{
					if( $_where = $extension->getQueryWhereClause( $this->_options[ $key ] ) )
					{
						if ( is_string( $_where ) )
						{
							$_where = array( $_where );
						}
						
						$where	= array_merge( $where, $_where );
					}
				}
			}
		}
		
		/* Compile query */
		$select = ( $option !== static::GET_COUNT_ONLY ) ? 'core_members.member_id AS my_member_id, core_members.*' : 'COUNT(DISTINCT core_members.member_id)';
		$limit = is_array( $option ) ? $option : NULL;
		$query = Db::i()->select( $select, 'core_members', $where, ( $option !== static::GET_COUNT_ONLY ) ? 'core_members.member_id' : NULL, $limit, NULL, NULL, Db::SELECT_DISTINCT );
		
		/* Run callbacks */
		foreach ( Application::allExtensions( 'core', 'MemberFilter', FALSE, 'core' ) as $key => $extension )
		{
			if( $extension->availableIn( 'bulkmail' ) )
			{
				/* Grab our fields and add to the form */
				if( !empty( $this->_options[ $key ] ) )
				{
					$data = $this->_options[ $key ];
					$extension->queryCallback( $data, $query );
				}
			}
		}

		return $query;
	}

	/**
	 * Return tag values
	 *
	 * @param	int					$type	0=All, 1=Global, 2=Member-specific
	 * @param	NULL|Member	$member	Member object if $type is 0 or 2
	 * @return	array
	 */
	public function returnTagValues( int $type, ?Member $member=NULL ) : array
	{
		$tags	= array();

		/* Do we want global tags? */
		if( $type === 0 OR $type === 1 )
		{
			$mostOnline = json_decode( Settings::i()->most_online, TRUE );

			$tags['{suite_name}']		= Settings::i()->board_name;
			$tags['{suite_url}']		= Settings::i()->base_url;
			$tags['{busy_time}']		= DateTime::ts( ( $mostOnline['time'] ) ?: time() )->localeDate();
			$tags['{busy_count}']		= $mostOnline['count'];
			
			/* Only bother querying if we need the value */
			if( mb_strpos( $this->_data['content'], '{reg_total}' ) !== FALSE )
			{
				$tags['{reg_total}']		= Db::i()->select( 'count(*)', 'core_members', 'member_id > 0' )->first();
			}
		}

		/* Do we want member tags? */
		if( $type === 0 OR $type === 2 )
		{
			$tags['{member_id}']			= $member->member_id;
			$tags['{member_email}']			= $member->email;
			$tags['{member_name}']			= $member->name;
			$tags['{member_url}']			= $member->url();
			$tags['{member_joined}']		= $member->joined->localeDate();
			$tags['{member_last_visit}']	= DateTime::ts( (int) $member->last_visit )->localeDate();
			$tags['{member_posts}']			= $member->member_posts;
			$tags['{unsubscribe_url}']		= (string) Url::internal( 'app=core&module=system&controller=unsubscribe', 'front', 'unsubscribe' )->setQueryString( array(
				'email'	=> $member->email,
				'key'	=> md5( $member->email . ':' . $member->members_pass_hash )
			) );
			$tags['{unsubscribe_key}']		= md5( $member->email . ':' . $member->members_pass_hash );
		}

		/* Now retrieve tags via any bulk mail extensions.  We only want them to return an array of tags to perform formatting, but
			$body is passed in case a particular tag is computationally expensive so that the extension may "sniff" for it and elect
			not to perform the computation if it is not used. */
		foreach ( Application::allExtensions( 'core', 'BulkMail', TRUE, 'core' ) as $key => $extension )
		{
			$tags	= array_merge( $tags, $extension->returnTagValues( $this->_data['content'], $type, $member ) );
		}

		return $tags;
	}

	/**
	 * Retrieve the tags that can be used in bulk mails
	 *
	 * @return	array 	An array of tags in foramt of 'tag' => 'explanation text'
	 */
	public static function getTags() : array
	{
		/* Default tags */
		$tags	= array(
			'{member_id}'			=> Member::loggedIn()->language()->addToStack('bmtag_member_id'),
			'{member_name}'			=> Member::loggedIn()->language()->addToStack('bmtag_member_name'),
			'{member_url}'			=> Member::loggedIn()->language()->addToStack('bmtag_member_profileurl'),
			'{member_joined}'		=> Member::loggedIn()->language()->addToStack('bmtag_member_joined'),
			'{member_last_visit}'	=> Member::loggedIn()->language()->addToStack('bmtag_member_last_visit'),
			'{member_posts}'		=> Member::loggedIn()->language()->addToStack('bmtag_member_posts'),
			'{reg_total}'			=> Member::loggedIn()->language()->addToStack('bmtag_reg_total'),
			'{suite_name}'			=> Member::loggedIn()->language()->addToStack('bmtag_suite_name'),
			'{suite_url}'			=> Member::loggedIn()->language()->addToStack('bmtag_suite_url'),
			'{busy_count}'			=> Member::loggedIn()->language()->addToStack('bmtag_busy_count'),
			'{busy_time}'			=> Member::loggedIn()->language()->addToStack('bmtag_busy_time'),
		);

		/* Now grab tags from any bulk mail extensions */
		foreach ( Application::allExtensions( 'core', 'BulkMail', TRUE, 'core' ) as $key => $extension )
		{
			if( method_exists( $extension, 'getTags' ) )
			{
				$tags	= array_merge( $tags, $extension->getTags() );
			}
		}

		return $tags;
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* Unclaim Attachments */
		File::unclaimAttachments( 'core_Admin', $this->id, NULL, 'bulkmail' );
		
		/* Make sure any queue rows are removed */
		foreach( Db::i()->select( '*', 'core_queue', array( "`key`=?", 'Bulkmail' ) ) AS $task )
		{
			$data = json_decode( $task['data'], TRUE );
			
			if ( isset( $data['mail_id'] ) AND $data['mail_id'] == $this->id )
			{
				Db::i()->delete( 'core_queue', array( "id=?", $task['id'] ) );
			}
		}
		
		parent::delete();
	}
}