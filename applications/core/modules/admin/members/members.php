<?php
/**
 * @brief		Manage Members
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Mar 2013
 */

namespace IPS\core\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use BadMethodCallException;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Rank;
use IPS\core\Achievements\Recognize;
use IPS\core\AdminNotification;
use IPS\core\extensions\core\AdminNotifications\NewRegValidate;
use IPS\core\extensions\core\LiveSearch\Members as MembersExtension;
use IPS\core\ProfileFields\Field;
use IPS\core\Warnings\Warning;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Email;
use IPS\Events\Event;
use IPS\Extensions\SSOAbstract;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\CoverPhoto\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Email as FormEmail;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Image;
use IPS\Lang;
use IPS\Login;
use IPS\Login\Handler;
use IPS\Login\Handler\Standard;
use IPS\Member;
use IPS\Member\Device;
use IPS\Member\Group;
use IPS\Member\History;
use IPS\nexus\Customer;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Text\Encrypt;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use PasswordStrength;
use UnderflowException;
use function array_keys;
use function array_merge;
use function chr;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function mb_stripos;
use function str_replace;
use function strlen;
use function strtotime;
use const IPS\Helpers\Table\HEADER;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_NUMERIC;
use const IPS\Helpers\Table\SEARCH_NUMERIC_TEXT;
use const IPS\Helpers\Table\SEARCH_QUERY_TEXT;
use const IPS\Helpers\Table\SEARCH_RADIO;
use const IPS\Helpers\Table\SEARCH_SELECT;
use const IPS\PHOTO_THUMBNAIL_SIZE;
use const IPS\SUITE_UNIQUE_KEY;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Manage Members
 */
class members extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Get the available filters for the main table
	 *
	 * @return 	array
	 */
	protected static function _getTableFilters() : array
	{
		$filters = [
			'members_filter_banned'			=> 'temp_ban<>0',
			'members_filter_spam'			=> Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ),
			'members_filter_validating'		=> '( v.lost_pass=0 AND v.forgot_security=0 AND v.vid IS NOT NULL )',
			'members_filter_administrators'	=> '(1=1)' // We use a straight join when this filter is activated, so there's no additional where needed
		];

		if ( Db::i()->select( 'COUNT(*)', 'core_members', array( "email <> '' and name=''" ) )->first() )
		{
			$filters['members_filtered_reserved'] = "(name='')";
		}

		if( Settings::i()->ipb_bruteforce_attempts )
		{
			/* We do this so we can put the locked filter at the 'end' of the buttons - array_unshift does not give us a simple method to retain keys */
			$filters += array( 'members_filter_locked' => '( failed_login_count>=' . (int) Settings::i()->ipb_bruteforce_attempts . ' OR failed_mfa_attempts>=' . (int) Settings::i()->security_questions_tries . ')' );
		}

		return array_merge( Bridge::i()->bounceMemberViewGetTableFilters(), $filters );
	}

	/**
	 * Get the joins for the main table
	 *
	 * @returns array
	 */
	protected static function _getTableJoins( array $joinFields, TableDb $table ) : array
	{
		$joins = [
			array(
				'select' => 'v.vid, v.coppa_user, v.lost_pass, v.forgot_security, v.new_reg, v.email_chg, v.user_verified, v.spam_flag, v.reg_cancelled',
				'from' => array( 'core_validating', 'v' ),
				'where' => 'v.member_id=core_members.member_id AND v.lost_pass != 1 AND v.forgot_security != 1' ),
			array(
				'select' => implode( ',', $joinFields ),
				'from' => array( 'core_pfields_content', 'p' ),
				'where' => 'p.member_id=core_members.member_id' ),
			array(
				'select' => 'm.row_id',
				'from' => array( 'core_admin_permission_rows', 'm' ),
				'where' => "( m.row_id_type='group' and ( m.row_id=core_members.member_group_id OR FIND_IN_SET( m.row_id, core_members.mgroup_others ) ) or ( m.row_id=core_members.member_id AND m.row_id_type='member' ))",
				'type' => 'STRAIGHT_JOIN' )
		];

		/* Makes query less efficient */
		if ( $table->filter !== 'members_filter_administrators' )
		{
			unset( $joins[2] );
		}

		if ( ! isset( Request::i()->advanced_search_submitted ) )
		{
			unset( $joins[1] );
		}

		return array_merge( Bridge::i()->bounceMemberViewGetTableJoins(), $joins );
	}
	
	/**
	 * Manage Members
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( isset( Request::i()->searchResult ) )
		{
			Output::i()->output .= Theme::i()->getTemplate( 'global', 'core' )->message( sprintf( Member::loggedIn()->language()->get('search_results_in_nodes'), mb_strtolower( Member::loggedIn()->language()->get('members') ) ), 'information' );
		}

		/* Some advanced search links may bring us here */
		Output::i()->bypassCsrfKeyCheck = true;

		$forceIndex = 'joined';

		/* Yeah, this is the level of hackery we've gotten to. Millions of rows in core_members is hard work */
		if ( isset( Request::i()->filter ) and Request::i()->filter == 'members_filter_validating' )
		{
			$forceIndex = NULL;
		}

		/* Create the table */
		$table = new TableDb( 'core_members', Url::internal( 'app=core&module=members&controller=members' ), array( array( 'core_members.email<>?', '' ) ), $forceIndex );
		$table->langPrefix = 'members_';
		$table->keyField = 'member_id';

		/* Columns we need */
		$table->include = array( 'photo', 'name', 'email', 'joined', 'group_name' );
		if ( Settings::i()->achievements_enabled AND Rank::getStore() )
		{
			$table->include[] = 'achievements_points';
			$table->widths['achievements_points'] = '17';
		}
		$table->include[] = 'ip_address';
		$table->mainColumn = 'name';
		$table->noSort	= array( 'photo' );
		$table->rowClasses = array( 'email' => array( 'ipsTable_wrap' ), 'group_name' => array( 'ipsTable_wrap' ) );
		
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'joined';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Groups for advanced filter (need to do it this way because array_merge renumbers the result) */
		$groups     = array( '' => 'any_group' );
		$joinFields = array( 'core_members.member_id as member_id' );
		
		foreach ( Group::groups() as $k => $v )
		{
			$groups[ $k ] = $v->name;
		}
		
		$fieldsToAdd	= array();
		
		/* Profile fields */
		foreach ( Field::fields( array(), Field::STAFF ) as $group => $fields )
		{
			/* Header */
			Member::loggedIn()->language()->words[ "members_core_pfieldgroups_{$group}" ] = Member::loggedIn()->language()->addToStack( "core_pfieldgroups_{$group}", FALSE );
			
			/* Fields */
			foreach ( $fields as $id => $field )
			{
				/* Alias the lang keys */
				$realLangKey = "core_pfield_{$id}";
				$fakeLangKey = "members_field_{$id}";
				Member::loggedIn()->language()->words[ $fakeLangKey ] = Member::loggedIn()->language()->addToStack( $realLangKey, FALSE );

				/* Work out the object type so we can show the appropriate field */
				$type = get_class( $field );
				$helper = NULL;

				switch ( $type )
				{
					case 'IPS\Helpers\Form\Text':
					case 'IPS\Helpers\Form\Tel':
					case 'IPS\Helpers\Form\Editor':
					case 'IPS\Helpers\Form\Email':
					case 'IPS\Helpers\Form\TextArea':
					case 'IPS\Helpers\Form\Url':
						$helper = SEARCH_CONTAINS_TEXT;
						break;
					case 'IPS\Helpers\Form\Date':
						$helper = SEARCH_DATE_RANGE;
						break;
					case 'IPS\Helpers\Form\Number':
						$helper = SEARCH_NUMERIC_TEXT;
						break;
					case 'IPS\Helpers\Form\Select':
					case 'IPS\Helpers\Form\Radio':
						if( $field->options['multiple'] )
						{
							$options = array();
						}
						else
						{
							$options = array( '' => "");
						}

						if( is_array( $field->options['options'] ) and count( $field->options['options'] ) )
						{
							foreach ( $field->options['options'] as $option )
							{
								$options[$option] = $option;
							}
						}

						$helper = array( SEARCH_SELECT, array( 'options' => $options, 'multiple' => (bool)$field->options['multiple'], 'noDefault' => true ) );
						break;
				}
								
				if ( $helper )
				{
					$fieldsToAdd[ "field_{$id}" ] = $helper;
				}

				/* Set fields we need for the table joins below */
				$joinFields[] = "field_{$id}";
			}
		}

		/* Joins */
		$table->joins = static::_getTableJoins( $joinFields, $table );

		/* Ranks */
		$rankOptions = array( '' => 'any' );
		foreach( Rank::getStore() as $rank )
		{
			$rankOptions[ $rank->id ] = $rank->_title;
		}
			
		/* Search */
		$table->quickSearch = function( $string ) {
			return Db::i()->like( 'name', $string, TRUE, TRUE, MembersExtension::canPerformInlineSearch() );
		};
		
		$table->advancedSearch = array(
			'name'				=> SEARCH_QUERY_TEXT,
			'member_id'			=> array( SEARCH_NUMERIC, array(), function( $v ){
				switch ( $v[0] )
				{
					case 'gt':
						return array( "core_members.member_id>?", (float) $v[1] );
					case 'lt':
						return array( "core_members.member_id<?", (float) $v[1] );
					case 'eq':
						return array( "core_members.member_id=?", (float) $v[1] );
				}
				return [];
			} ),
			'email'				=> array( SEARCH_CONTAINS_TEXT, array(), function( $val )
			{
				return array( "core_members.email LIKE ?", '%' . $val . '%' );
			} ),
			'ip_address'		=> array( SEARCH_CONTAINS_TEXT, array(), function( $val )
			{
				return array( "core_members.ip_address LIKE ?", '%' . $val . '%' );
			} ),
			'member_group_id'	=> array( SEARCH_SELECT, array( 'options' => $groups ), function( $val )
			{
				return array( '( member_group_id=? OR FIND_IN_SET( ?, mgroup_others ) )', $val, $val );
			} ),
			'achievements_points'	=> array( SEARCH_SELECT, array( 'options' => $rankOptions ), function( $val )
			{
				$minPoints = 0;
				$maxPoints = 0;
				$minSet = FALSE;
				foreach( Rank::getStore() as $rank )
				{
					if ( !$minSet and $rank->id == $val )
					{
						$minPoints = $rank->points;
						$minSet = TRUE;
					}
					
					if ( $minSet and $rank->points > $minPoints )
					{
						$maxPoints = $rank->points;
						break;
					}
				}
				
				if ( $minPoints and $maxPoints )
				{
					return array( '( achievements_points BETWEEN ? AND ? )', $minPoints, $maxPoints - 1 );
				}
				elseif ( $minPoints )
				{
					return array( '( achievements_points >= ? )', $minPoints );
				}
				elseif ( $maxPoints )
				{
					return array( '( achievements_points < ? )', $maxPoints );
				}
				return [];
			} ),
			'joined'					=> SEARCH_DATE_RANGE,
			'member_last_post'			=> SEARCH_DATE_RANGE,
			'last_activity'				=> SEARCH_DATE_RANGE,
			'member_posts'				=> SEARCH_NUMERIC,
			'allow_admin_emails'		=> array( SEARCH_RADIO, array( 'options' => array( 'a' => 'allow_admin_emails_any', 1 => 'yes', 0 => 'no' ) ), function( $val )
			{
				return ( $val == 'a' ) ? array( '1=1' ) : array( 'allow_admin_mails=?', intval( $val ) );
			} )
			);
		
		if( count( $fieldsToAdd ) )
		{
			$table->advancedSearch[ "core_pfieldgroups_{$group}" ] = HEADER;

			$table->advancedSearch	= array_merge( $table->advancedSearch, $fieldsToAdd );
		}
						
		/* Filters */
		$table->filters = static::_getTableFilters();

		/* Custom parsers */
		$table->parsers = array(
			'email'				=> function( $val, &$row )
			{
				if ( ! array_key_exists( 'vid', $row ) )
				{
					/* Grab the data if need be */
					$member = Member::constructFromData( $row );

					if ( $member->members_bitoptions['validating'] )
					{
						try
						{
							$validating = Db::i()->select( '*', 'core_validating', [ 'member_id=?', $member->member_id ] )->first();
							$row = array_merge( $validating, $row );
						}
						catch( UnderflowException $e ) { }
					}
				}

				if ( ! empty( $row['vid'] ) )
				{
					return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberEmailCell( Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberValidatingCell( $val, Member::constructFromData( $row )->validatingDescription( $row ) ) );
				}
				else
				{
					return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberEmailCell( htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) );
				}				
			},
			'photo'				=> function( $val, $row )
			{
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( Member::constructFromData( $row ), 'tiny' );
			},
			'joined'			=> function( $val, $row )
			{
				return DateTime::ts( $val )->localeDate();
			},
			'group_name'	=> function( $val, $row )
			{
				$secondary = Member::constructFromData( $row )->groups;
				
				foreach( $secondary as $k => $v )
				{
					if( $v == $row['member_group_id'] or $v == 0 )
					{
						unset( $secondary[ $k ] );
						continue;
					}
					
					$secondary[ $k ] = Group::load( $v );
				}

				return Theme::i()->getTemplate( 'members', 'core', 'admin' )->groupCell( Group::load( $row['member_group_id'] ), $secondary );
			},
			'ip_address'	=> function( $val, $row )
			{
				if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) )
				{
					return "<a href='" . Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
				}
				return $val;
			},
			'member_last_post' => function( $val, $row )
			{
				return ( $val ) ? DateTime::ts( $val )->localeDate() : Member::loggedIn()->language()->addToStack( 'never' );
			},
			'last_activity' => function( $val, $row )
			{
				return ( $val ) ? DateTime::ts( $val )->localeDate() : Member::loggedIn()->language()->addToStack( 'never' );
			},
			'name' => function( $val, $row )
			{
				if ( $val )
				{
					$member = Member::constructFromData( $row );
	
					if ( $banned = $member->isBanned() )
					{
						if ( $banned instanceof DateTime )
						{
							$title = Member::loggedIn()->language()->addToStack( 'suspended_until', FALSE, array( 'sprintf' => array( $banned->localeDate() ) ) );
						}
						else
						{
							$title = Member::loggedIn()->language()->addToStack( 'banned' );
						}
						return "<a href='" . Url::internal( 'app=core&module=members&controller=members&do=view&id=' ) . $row['member_id'] .  "'>" . htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) . "</a> &nbsp; <span class='ipsBadge ipsBadge--negative'>" . $title ."</span> ";
					}
					else
					{
						return "<a href='" . Url::internal( 'app=core&module=members&controller=members&do=view&id=' ) . $row['member_id'] . "'>" . htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) . "</a>";
					}
				}
				else
				{
					return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberReserved( Member::constructFromData( $row ) );
				}
			},
			'allow_admin_emails' => function( $val, $row )
			{
				return $row['allow_admin_mails'] ? '&#10004;' : '&#10007;';
			},
			'achievements_points' => function( $val, $row )
			{
				return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberRank( $val );
			},		
		);
		
		/* Specify the buttons */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add' ) and Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
		{
			Output::i()->sidebar['actions']['add'] = array(
				'primary'	=> true,
				'icon'		=> 'plus',
				'title'		=> 'members_add',
				'link'		=> Url::internal( 'app=core&module=members&controller=members&do=add' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('members_add') )
			);
		}
		$table->rowButtons = function( $row )
		{
			$member = Member::constructFromData( $row );
			
			$return = array();
			
			if ( isset( $row['vid'] ) and $row['vid'] and Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_validating' ) )
			{
				$return['approve'] = array(
					'icon'		=> 'check-circle',
					'title'		=> 'approve',
					'link'		=> Url::internal( 'app=core&module=members&controller=members&do=approve&id=' . $member->member_id )->getSafeUrlFromFilters()->csrf(),
					'id'		=> "{$member->member_id}-approve",
					'data'		=> array(
						'bubble' 		=> '',
					)
				);
				$return['ban'] = array(
					'icon'		=> 'times',
					'title'		=> 'ban',
					'link'		=> Url::internal( 'app=core&module=members&controller=members&do=ban&id=' . $member->member_id . '&permban=1' )->csrf()->getSafeUrlFromFilters(),
					'id'		=> "{$member->member_id}-ban",
					'data'		=> array(
						'bubble'		=> '',
					)
				);
				
				if ( !$row['user_verified'] )
				{
					$return['resend_email'] = array(
						'icon'		=> 'envelope',
						'title'		=> 'resend_validation_email',
						'link'		=> Url::internal( 'app=core&module=members&controller=members&do=resendEmail&id=' . $member->member_id )->csrf()->getSafeUrlFromFilters(),
						'data' 		=> array( 'doajax' => '' ),
						'id'		=> "{$member->member_id}-resend",
					);
				}
			}
			
			if (
				Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_locked' ) and
				(
					Settings::i()->ipb_bruteforce_attempts and $row['failed_login_count'] >= (int) Settings::i()->ipb_bruteforce_attempts
					or
					Settings::i()->security_questions_tries and $row['failed_mfa_attempts'] >= (int) Settings::i()->security_questions_tries
				)
			) {
				$return['unlock'] = array(
					'icon'		=> 'unlock',
					'title'		=> 'unlock',
					'link'		=> Url::internal( 'app=core&module=members&controller=members&do=unlock&id=' . $member->member_id )->csrf()->getSafeUrlFromFilters(),
					'data'		=> array( 'bubble' => '' )
				);
			}
			
			$return['view'] = array(
				'icon'		=> 'search',
				'title'		=> 'view',
				'link'		=> Url::internal( 'app=core&module=members&controller=members&do=view&id=' . $member->member_id )->getSafeUrlFromFilters(),
			);
			
			if ( !Request::i()->_groupFilter )
			{
				if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) )
				{
					if ( $member->member_id != Member::loggedIn()->member_id )
					{
						$return['flag'] = array(
							'icon'		=> 'flag',
							'title'		=> 'spam_flag',
							'link'		=> Url::internal( 'app=core&module=members&controller=members&do=spam&id=' . $member->member_id . '&status=1')->csrf()->getSafeUrlFromFilters(),
							'hidden'	=> $member->members_bitoptions['bw_is_spammer'],
							'id'		=> "{$member->member_id}-flag",
							'data'		=> array(
								'controller'	=> 'core.admin.members.listFlagSpammer',
							)
						);
						$return['unflag'] = array(
							'icon'		=> 'flag ipsType_spammer',
							'title'		=> 'spam_unflag',
							'link'		=> Url::internal( 'app=core&module=members&controller=members&do=spam&id=' . $member->member_id . '&status=0' )->csrf()->getSafeUrlFromFilters(),
							'hidden'	=> !$member->members_bitoptions['bw_is_spammer'],
							'id'		=> "{$member->member_id}-unflag",
							'data'		=> array(
								'controller'	=> 'core.admin.members.listFlagSpammer',
							)
						);
					}
				}
							
				if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) and ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) or !$member->isAdmin() ) and $member->member_id != Member::loggedIn()->member_id )
				{
					$return['delete'] = array(
						'icon'		=> 'times-circle',
						'title'		=> 'delete',
						'link'		=> Url::internal( 'app=core&module=members&controller=members&do=delete&id=' . $member->member_id )->getSafeUrlFromFilters(),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'delete_member', FALSE, array( 'sprintf' => $member->name ) ) ),
					);
				}
			}
			
			return $return;
		};
		
		/* Display */
		if( Request::i()->advanced_search_submitted OR Request::i()->quicksearch )
		{
			$query = [];
			
			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) OR Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
			{
				$query = array(
					'members_name'				=> Request::i()->quicksearch ?: Request::i()->members_name,
                    //'members_member_id'			=> ( \IPS\Request::i()->members_member_id[0] != 'any' ) ? \IPS\Request::i()->members_member_id[1] : 0,
                    'members_member_id'			=> Request::i()->members_member_id,
					'members_email'				=> Request::i()->members_email,
					'members_ip_address'		=> Request::i()->members_ip_address,
					'members_member_group_id'	=> Request::i()->members_member_group_id,
					'members_joined'			=> Request::i()->members_joined,
					'members_last_post'			=> Request::i()->members_member_last_post,
					'members_last_activity'		=> Request::i()->members_last_activity,
					'members_posts'				=> Request::i()->members_member_posts,
					'filter'					=> Request::i()->filter,
					'members_allow_admin_emails' => Request::i()->members_allow_admin_emails
				);

				foreach ( Request::i() as $k => $v )
				{
					/* Add profile fields */
					if ( mb_substr( $k, 0, 14 ) === 'members_field_' and $v and ( !is_array( $v ) or ( !in_array( '__EMPTY', $v ) ) ) )
					{
						$query[ $k ] = $v;

						/* And also add them as parsers so they display properly */
						$table->parsers[ str_replace( 'members_', '', $k ) ] = function( $val, $row ) use ( $k )
						{
							try
							{
								return Field::load( str_replace( 'members_field_', '', $k ) )->displayValue( $val, FALSE, NULL, Field::STAFF, Member::load( $row['member_id'] ) );
							}
							catch ( Exception $e )
							{
								return NULL;
							}
						};
					}
				}
			}
			
			$table->extraHtml = Theme::i()->getTemplate( 'members' )->memberListResultsInfobox( Url::internal( "app=core&module=members&controller=members&do=massManage" )->setQueryString( $query ) );
		}
		
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add' ) and Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
		{
			Output::i()->sidebar['actions']['import'] = array(
				'icon'		=> 'cloud-upload',
				'title'		=> 'members_import',
				'link'		=> Url::internal( 'app=core&module=members&controller=members&do=import&_new=1' )
			);
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_export' ) )
		{
			Output::i()->sidebar['actions']['export'] = array(
				'icon'		=> 'cloud-download',
				'title'		=> 'members_export',
				'link'		=> Url::internal( 'app=core&module=members&controller=members&do=export&_new=1' )
			);
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) )
		{
			/* Do we have at least one login handler that can process password resets? */
			$canProcess = FALSE;
			foreach( Login::methods() AS $handler )
			{
				/* Doesn't matter what it is, as long as there is one */
				if ( $handler->canSyncPassword() )
				{
					$canProcess = TRUE;
					break;
				}
			}
			
			if ( $canProcess === TRUE )
			{
				Output::i()->sidebar['actions']['force_password_reset'] = array(
					'icon'		=> 'wrench',
					'title'		=> 'force_password_reset',
					'link'		=> Url::internal( "app=core&module=members&controller=members&do=forcePassReset" )
				);
			}
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('members');
		Output::i()->output	.= $table;
	}

	protected function toggleDataLayerPii() : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_edit' );

		$member = Member::load( Request::i()->id );
		if ( $member->isAdmin() )
		{
			Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
		}

		if ( $member->member_id )
		{
			$member->members_bitoptions['datalayer_pii_optout'] = !$member->members_bitoptions['datalayer_pii_optout'];
			$member->save();
		}
		$_SESSION['member_datalayer_changed'] = 1;
		Output::i()->redirect( Url::internal( 'app=core&module=members&controller=members&do=view&id=' . Request::i()->id ?? 0 ) );
	}

	/**
	 * Prune members
	 *
	 * @return	void
	 */
	public function massManage() : void
	{
		switch( Request::i()->action )
		{
			case 'prune':
				Dispatcher::i()->checkAcpPermission( 'member_delete' );
			break;
			
			case 'move':
				Dispatcher::i()->checkAcpPermission( 'member_edit' );
			break;
		}
		
		$where = array();
		
		if ( Request::i()->members_name )
		{
			if ( is_array( Request::i()->members_name ) )
			{
				if ( Request::i()->members_name[1] )
				{
					switch( Request::i()->members_name[0] )
					{
						case 'c':
							$where[] = Db::i()->like( 'name', Request::i()->members_name[1], TRUE, TRUE, TRUE );
						break;
						
						case 'bw':
							$where[] = Db::i()->like( 'name', Request::i()->members_name[1] );
						break;
						
						case 'eq':
							$where[] = array( 'name=?', Request::i()->members_name[1] );
						break;
					}
				}
			}
			else
			{
				$where[] = Db::i()->like( 'name', Request::i()->members_name );
			}
		}
		
		if ( isset( Request::i()->members_member_id ) AND isset( Request::i()->members_member_id[1] ) AND Request::i()->members_member_id[1] )
		{
			switch ( Request::i()->members_member_id[0] )
			{
				case 'gt':
					$where[] = array( "core_members.member_id>?", Request::i()->members_member_id[1] );
				break;
				case 'lt':
					$where[] = array( "core_members.member_id<?", Request::i()->members_member_id[1] );
				break;
				case 'eq':
					$where[] = array( "core_members.member_id=?", Request::i()->members_member_id[1] );
				break;
			}
		}
		
		if ( Request::i()->members_email )
		{
			$where[] = Db::i()->like( 'email', Request::i()->members_email, TRUE, TRUE, TRUE );
		}

		if ( Request::i()->members_ip_address )
		{
			$where[] = array( "core_members.ip_address LIKE CONCAT( '%', ?, '%' )", (string) Request::i()->members_ip_address );
		}
		
		if ( Request::i()->members_member_group_id )
		{
			$adminGroups	= array_keys( Member::administrators()['g'] );
			
			/* We do a generic permissions check here, then later on when the process is actually running, we check each individual one to make sure we don't do something we shouldn't do */
			if
			(
				(
					(
						Request::i()->action === 'prune' AND Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' )
					)
					OR
					(
						Request::i()->action === 'move' AND
						(
							Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) OR
							Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin1' ) OR
							Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' )
						)
					)
				)
				OR
				!in_array( Request::i()->members_member_group_id, $adminGroups )
			)
			{
				$where[] = array( '( member_group_id=? OR FIND_IN_SET( ?, mgroup_others ) )', (int) Request::i()->members_member_group_id,(int) Request::i()->members_member_group_id );
			}
		}
		
		foreach ( array( 'joined', 'last_post', 'last_activity' ) as $k )
		{
			$requestKey = "members_{$k}";
			if ( $k === 'last_post' )
			{
				$k = 'member_last_post';
			}
			
			$request = Request::i()->$requestKey;
			
			if ( $request AND ( $request['start'] or $request['end'] ) )
			{
				$start = NULL;
				$end = NULL;
				if ( isset( $request['start'] ) and $request['start'] )
				{
					try
					{
						$time = Date::_convertDateFormat( $request['start'] );
						if ( is_numeric( $time ) )
						{
							$start = DateTime::ts( $time );
						}
						else
						{
							$start = new DateTime( $time );
						}
						$start = $start->setTime( 0, 0, 0 );
					}
					catch ( Exception $e )
					{
						Output::i()->error( 'members_manage_error', '2C114/M', 400, '' );
					}
				}
				if ( isset( $request['end'] ) and $request['end'] )
				{
					try
					{
						$time = Date::_convertDateFormat( $request['end'] );
						if ( is_numeric( $time ) )
						{
							$end = DateTime::ts( $time );
						}
						else
						{
							$end = new DateTime( $time );
						}
						$end = $end->setTime( 23, 59, 59 );
					}
					catch ( Exception $e )
					{
						Output::i()->error( 'members_manage_error', '2C114/N', 400, '' );
					}
				}
				
				if ( $start and $end )
				{
					$where[] = array( "{$k} BETWEEN ? AND ?", $start->getTimestamp(), $end->getTimestamp() );
				}
				elseif ( $start )
				{
					$where[] = array( "{$k}>?", $start->getTimestamp() );
				}
				elseif ( $end )
				{
					$where[] = array( "{$k}<?", $end->getTimestamp() );
				}
			}
		}

		if ( ( isset( Request::i()->members_posts[0] ) AND Request::i()->members_posts[0] != 'any' ) AND isset( Request::i()->members_posts[1] ) )
		{
			switch( Request::i()->members_posts[0] )
			{
				case 'gt':
					$operator = '>';
				break;
				
				case 'lt':
					$operator = '<';
				break;
				
				case 'eq':
					$operator = '=';
				break;
			}
			$where[] = array( 'member_posts'.$operator.'?', (int) Request::i()->members_posts[1] );
		}

		$joinValidating = $joinAdmins = FALSE;

		if( isset( Request::i()->filter ) )
		{
			switch ( Request::i()->filter )
			{
				case 'members_filter_banned':
					$where[] = array( 'temp_ban<>0' );
					break;
				case 'members_filter_locked':
					$where[] = array( 'failed_login_count>=' . (int) Settings::i()->ipb_bruteforce_attempts );
					break;
				case 'members_filter_spam':
					$where[] = array( Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) );
					break;
				case 'members_filter_validating':
					$where[] = array( '( v.lost_pass=0 AND v.forgot_security=0 AND v.vid IS NOT NULL )' );
					$joinValidating	= TRUE;
					break;
				case 'members_filter_administrators':
					$where[] = array( '( m.row_id IS NOT NULL OR g.row_id IS NOT NULL )' );
					$joinAdmins		= TRUE;
					break;
			}
		}
		
		foreach ( Request::i() as $k => $v )
		{
			if ( mb_substr( $k, 0, 14 ) === 'members_field_' )
			{
				try
				{
					/* Only include these for a non-empty value */
					if ( !empty( $v ) )
					{
						$field = Field::load( mb_substr( $k, 14 ) );
						switch ( $field->type )
						{
							case 'Text':
							case 'Tel':
							case 'Editor':
							case 'TextArea':
							case 'Url':
								$where[] = array( "field_{$field->id} LIKE CONCAT( '%', ?, '%' )", $v );
								break;
							case 'Date':
								if ( isset( $v['start'] ) and $v['start'] )
								{
									$where[] = array( "field_{$field->id}>?", ( new DateTime( $v['start'] ) )->getTimestamp() );
								}
								if ( isset( $v['end'] ) and $v['end'] )
								{
									$where[] = array( "field_{$field->id}<?", ( new DateTime( $v['end'] ) )->setTime( 23, 59, 59 )->getTimestamp() );
								}
								break;
							case 'Number':
								switch ( $v[0] )
								{
									case 'gt':
										$where[] = array( "field_{$field->id}>?", intval( $v[1] ) );
										break;
									case 'lt':
										$where[] = array( "field_{$field->id}<?", intval( $v[1] ) );
										break;
									case 'eq':
										$where[] = array( "field_{$field->id}=?", intval( $v[1] ) );
										break;
								}
								break;
							case 'Select':
							case 'Radio':
								$where[] = array( "field_{$field->id}=?", $v );
								break;
						}
					}
				}
				catch ( OutOfRangeException $e ) { }
			}
		}
		
		/* Bulk email */
		if ( isset( Request::i()->members_allow_admin_emails ) and Request::i()->members_allow_admin_emails != 'a' )
		{
			$where[] = array( 'allow_admin_mails=?', intval( Request::i()->members_allow_admin_emails ) );
		}
		
		if ( !count( $where ) )
		{
			if ( Request::i()->action === 'prune' )
			{
				Output::i()->error( 'member_prune_no_results', '2C114/E', 404, '' );
			}
			else
			{
				Output::i()->error( 'member_move_no_results', '2C114/G', 404, '' );
			}
		}

		/* Unset any previous session data */
		$_SESSION['members_manage_where']	= $where;
		$_SESSION['members_manage_action']	= Request::i()->action;
		
		if ( Request::i()->action === 'prune' or Request::i()->action === 'unSub' )
		{
			$count = Db::i()->select( 'COUNT(*) AS count', 'core_members', $where )
				->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' );

			if( $joinValidating )
			{
				$count = $count->join( array( 'core_validating', 'v' ), 'v.member_id=core_members.member_id');
			}

			if( $joinAdmins )
			{
				$count = $count->join( array( 'core_admin_permission_rows', 'm' ), "m.row_id=core_members.member_id AND m.row_id_type='member'" )
					->join( array( 'core_admin_permission_rows', 'g' ), array( 'g.row_id', Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) );
			}

			/* We need to remember the group we are moving *from*, if that is what we are doing */
			if( isset( Request::i()->members_member_group_id ) )
			{
				$_SESSION['members_manage_old_group']	= Request::i()->members_member_group_id;
			}

            Output::i()->output	= Theme::i()->getTemplate( 'members' )->confirmMassAction( $count->first(), Request::i()->action );
			Output::i()->title		= Member::loggedIn()->language()->addToStack( 'member_prune_confirm' );
		}
		else
		{
			$form = new Form;
			$groups = Group::groups( TRUE, FALSE );

			/* We can remove the group we are moving *from* */
			if( isset( Request::i()->members_member_group_id ) )
			{
				unset( $groups[Request::i()->members_member_group_id] );
			}
			$form->add( new Select( 'move_to_group', NULL, TRUE, array( 'options'	=> $groups, 'parse' => 'normal' ) ) );
			
			if ( $values = $form->values() )
			{
				$group = Group::load( $values['move_to_group'] );
				
				if ( in_array( $group->g_id, array_keys( Member::administrators()['g'] ) ) AND !Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' ) )
				{
					Output::i()->error( 'member_move_admin_group', '2C114/H', 403, '' );
				}
				
				$count = Db::i()->select( 'COUNT(*)', 'core_members', $_SESSION['members_manage_where'] )
					->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' );

				if( $joinValidating )
				{
					$count = $count->join( array( 'core_validating', 'v' ), 'v.member_id=core_members.member_id');
				}

				if( $joinAdmins )
				{
					$count = $count->join( array( 'core_admin_permission_rows', 'm' ), "m.row_id=core_members.member_id AND m.row_id_type='member'" )
						->join( array( 'core_admin_permission_rows', 'g' ), array( 'g.row_id', Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) );
				}

				$_SESSION['members_manage_group']	= $group->g_id;

				/* We need to remember the group we are moving *from*, if that is what we are doing */
				if( isset( Request::i()->members_member_group_id ) )
				{
					$_SESSION['members_manage_old_group']	= Request::i()->members_member_group_id;
				}

				Output::i()->output			= Theme::i()->getTemplate( 'members' )->confirmMassAction( $count->first(), 'move', $group );
				Output::i()->title				= Member::loggedIn()->language()->addToStack( 'member_move_confirm' );
			}
			else
			{
				Output::i()->output	= $form;
				Output::i()->title		= Member::loggedIn()->language()->addToStack( 'member_search_move' );
			}
		}
	}
	
	/**
	 * Move Members
	 *
	 * @return	void
	 */
	public function doMove() : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();

		/* Don't queue the task if the session variables are null (i.e. the page has been reloaded) */
		if( isset( $_SESSION['members_manage_where'] )
			AND isset( $_SESSION['members_manage_action'] ) )
		{
			Task::queue( 'core', 'MoveMembers', array( 'where' => $_SESSION['members_manage_where'], 'group' => $_SESSION['members_manage_group'], 'oldGroup' => ( isset( $_SESSION['members_manage_old_group'] ) ) ? $_SESSION['members_manage_old_group'] : NULL, 'by' => Member::loggedIn()->member_id ), 2 );
			Session::i()->log( 'acplog__members_mass_move' );

			$_SESSION['members_manage_where']		= NULL;
			$_SESSION['members_manage_action']		= NULL;
			$_SESSION['members_manage_old_group']	= NULL;
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members" ), 'members_queued_for_moving' );
		}

		Output::i()->error( 'members_manage_error', '2C114/K', 404, '' );
	}
	
	/**
	 * Unsubscribe Members
	 *
	 * @return	void
	 */
	public function doUnsub() : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();

		/* Don't queue the task if the session variables are null (i.e. the page has been reloaded) */
		if( isset( $_SESSION['members_manage_where'] )
			AND isset( $_SESSION['members_manage_action'] ) )
		{
			Task::queue( 'core', 'UnsubMembers', array( 'where' => $_SESSION['members_manage_where'] ), 2 );
			Session::i()->log( 'acplog__members_mass_unsub' );

			$_SESSION['members_manage_where']		= NULL;
			$_SESSION['members_manage_action']		= NULL;
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members" ), 'members_queued_for_unsub' );
		}

		Output::i()->error( 'members_manage_error', '2C114/K', 404, '' );
	}

	/**
	 * Prune members
	 *
	 * @return	void
	 */
	public function doPrune() : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_delete' );
		Session::i()->csrfCheck();

		/* Don't queue the task if the session variables are null (i.e. the page has been reloaded) */
		if( isset( $_SESSION['members_manage_where'] ) AND $_SESSION['members_manage_where'] !== NULL
			AND isset( $_SESSION['members_manage_action'] ) AND $_SESSION['members_manage_action'] !== NULL )
		{
			Task::queue( 'core', 'PruneMembers', array( 'where' => $_SESSION['members_manage_where'], 'group' => ( isset( $_SESSION['members_manage_old_group'] ) ) ? $_SESSION['members_manage_old_group'] : NULL ), 2 );
			Session::i()->log( 'acplog__members_mass_delete' );

			$_SESSION['members_manage_where']		= NULL;
			$_SESSION['members_manage_action']		= NULL;
			$_SESSION['members_manage_old_group']	= NULL;
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members" ), 'members_queued_for_pruning' );
		}

		Output::i()->error( 'members_manage_error', '2C114/L', 404, '' );
	}

	/**
	 * Add Member
	 *
	 * @return	void
	 */
	public function add() : void
	{
		/* Check permissions */
		Dispatcher::i()->checkAcpPermission( 'member_add' );
		
		/* Build form */
		$form = new Form;
		$form->add( new Text( 'username', NULL, TRUE, array( 'accountUsername' => TRUE ) ) );
		Member::loggedIn()->language()->words['password_set_login_desc'] = Member::loggedIn()->language()->addToStack( 'password_email_always' );
		$form->add( new YesNo( 'password_set_login', FALSE, FALSE, array( 'togglesOn' => array( 'member_add_password', 'member_add_confirmemail' ) ) ) );
		$form->add( new Password( 'password', NULL, FALSE, array( 'protect' => TRUE, 'showMeter' => Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'strengthRequest' => array( 'username', 'email_address' ) ), NULL, NULL, NULL, 'member_add_password' ) );
		$form->add( new FormEmail( 'email_address', NULL, TRUE, array( 'maxLength' => 150, 'accountEmail' => TRUE, 'bypassProfanity' => TRUE ) ) );
		$form->add( new Select( 'group', Settings::i()->member_group, TRUE, array( 'options' => Group::groups( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add_admin' ), FALSE ), 'parse' => 'normal' ) ) );
		$form->add( new CheckboxSet( 'secondary_groups', array(), FALSE, array( 'options' => Group::groups( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add_admin' ), FALSE ), 'multiple' => TRUE, 'parse' => 'normal' ) ) );
		foreach ( Lang::languages() as $lang )
		{
			$languages[ $lang->id ] = $lang->title;
		}
		$form->add( new Select( 'language', NULL, TRUE, array( 'options' => $languages ) ) );
		
		foreach( Theme::themes() as $theme )
		{
			$themes[ $theme->id ] = $theme->_title;
		}
		$themes[0] = 'skin_none';
		
		$form->add( new Select( 'skin', 0, TRUE, array( 'options' => $themes ) ) );
		$form->add( new YesNo( 'member_add_confirmemail', TRUE, FALSE, array(), NULL, NULL, NULL, 'member_add_confirmemail' ) );
		
		if( Settings::i()->use_coppa )
		{
			$form->add( new YesNo( 'member_add_coppa_user', FALSE, FALSE, array(), NULL, NULL, NULL, 'member_add_coppa_user' ) );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$member = new Member;
			$member->name				= $values['username'];
			$member->email				= $values['email_address'];
			$member->member_group_id	= $values['group'];
			$member->mgroup_others		= implode( ',', $values['secondary_groups'] );
			$member->language			= $values['language'];
			$member->skin				= ( $values['skin'] ) ?: NULL;
			
			$forcePass = FALSE;
			if ( (string) $values['password'] )
			{
				$member->setLocalPassword( $values['password'] );
			}
			else
			{
				$forcePass = TRUE;
				$member->members_bitoptions['password_reset_forced'] = TRUE;
			}
			
			$passSetKey = md5( SUITE_UNIQUE_KEY . $values['email_address'] . $values['username'] );
			
			if( Settings::i()->use_coppa )
			{
				$member->members_bitoptions['coppa_user'] = ( $values['member_add_coppa_user'] ) ?: FALSE;
			}
			
			$member->save();
			$member->logHistory( 'core', 'account', array( 'type' => 'register_admin' ) );
			
			/* Reset statistics */
			Widget::deleteCaches( 'stats', 'core' );
			
			Session::i()->log( 'acplog__members_created', array( $member->name => FALSE ) );
				
			if ( ( isset( $values['member_add_confirmemail'] ) AND $values['member_add_confirmemail'] ) OR $forcePass )
			{
				Email::buildFromTemplate( 'core', 'admin_reg', array( $member, $forcePass, $passSetKey ), Email::TYPE_TRANSACTIONAL )->send( $member );
			}
			
			Output::i()->redirect( Url::internal( 'app=core&module=members&controller=members&do=view&id=' . $member->member_id ), 'saved' );
		}
		
		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->outputTemplate = array( Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		Output::i()->title	= Member::loggedIn()->language()->addToStack( 'members_add' );
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'members_add', $form, FALSE );
	}
	
	/**
	 * View Member
	 *
	 * @return	void
	 */
	public function view() : void
	{
		/* Load the member */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C114/O', 404, '' );
		}
		
		/* Get the available tabs */
		$extensions = array();
		foreach( Application::allExtensions( 'core', 'MemberACPProfileTabs', TRUE, 'core', 'Main', FALSE ) AS $key => $ext )
		{
			$class = new $ext( $member );
			if ( $class->canView() )
			{
				$extensions[ $key ] = $ext;
			}
		}
		
		/* What's our active tab? */
		$activeTab = ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $extensions ) ) ? Request::i()->tab : 'core_Main';
		$classname = $extensions[ $activeTab ];
		$tab = new $classname( $member );
						
		/* Output */
		Output::i()->title = $member->name ?: Member::loggedIn()->language()->addToStack('members_name_missing_as_reserved');
		if ( Request::i()->isAjax() )
		{
			if ( isset( Request::i()->blockKey ) )
			{
				$exploded = explode( '_', Request::i()->blockKey );
				$class = 'IPS\\' . $exploded[0] . '\\extensions\\core\\MemberACPProfileBlocks\\' . $exploded[1];
				if ( class_exists( $class ) )
				{
					$block = new $class( $member );
					Output::i()->output = $block->tabOutput( Request::i()->block[ Request::i()->blockKey ] );
				}
				else
				{
					Output::i()->output = $class;
				}
			}
			else
			{
				Output::i()->output = $tab->output();
			}
		}
		else
		{
			$history = NULL;
			$historyFilters = array();
			if( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_history' ) )
			{
				$history = new History( $member->acpUrl()->setQueryString( array( 'do' => 'history' ) ), array( array( 'log_member=?', $member->member_id ) ), TRUE, FALSE, TRUE );
				$history->tableTemplate = array( Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyTable' );
				$history->rowsTemplate = array( Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyRows' );
				$history->limit = 20;
				
				$historyFilters = iterator_to_array( Db::i()->select( 'log_app, log_type, count(*) AS count', 'core_member_history', array( 'log_member=?', $member->member_id ), 'log_app, log_type', NULL, array( 'log_app', 'log_type' ) ) );

				$history = Theme::i()->getTemplate('memberprofile')->history( $member, $history, $historyFilters );
			}

			if ( isset( $_SESSION['member_datalayer_changed'] ) )
			{
				Output::i()->inlineMessage = 'Saved';
				unset( $_SESSION['member_datalayer_changed'] );
			}
						
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'members/view.css', 'core', 'admin' ) );
			Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_members.js', 'core' ) );

			if ( Application::appIsEnabled('cloud' ) )
			{
				Output::i()->addJsFiles( 'app.js', 'cloud', 'admin' );
			}

			Output::i()->hiddenElements = array('acpHeader');
			Output::i()->bodyClasses = array('acpNoPadding');
			Output::i()->output = Theme::i()->getTemplate('memberprofile')->mainTemplate( $member, $extensions, $activeTab, $tab->output(), $history );
		}
	}
	
	/**
	 * Edit window for a block
	 *
	 * @csrfChecked	Doesn't actually save changes, shows dialog 7 Oct 2019
	 * @return	void
	 */
	public function editBlock() : void
	{
		/* Load the member */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C114/S', 404, '' );
		}
		
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		if ( $member->isAdmin() )
		{
			Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
		}
		
		/* Display */
		$class = Request::i()->block;
		
		if( !is_subclass_of( $class, "\IPS\core\MemberACPProfile\Block" ) )
		{
			Output::i()->error( 'node_error', '2C114/14', 404, '' );
		}
		
		$object = new $class( $member );
		
		Output::i()->output = $object->edit();
	}
	
	/**
	 * View Member: Lazy-Loading Block
	 *
	 * @return	void
	 */
	public function lazyBlock() : void
	{
		/* Load the member */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id )
		{
			Output::i()->output = '';
		}
		
		$class = Request::i()->block;
		
		if( !is_subclass_of( $class, "\IPS\core\MemberACPProfile\Block" ) )
		{
			Output::i()->error( 'node_error', '2C114/15', 404, '' );
		}
		
		$object = new $class( $member );
		
		Output::i()->output = $object->lazyOutput();
	}
	
	/**
	 * Edit Member
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member */
		$member = Member::load( Request::i()->id );

		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C114/1', 404, '' );
		}
		if ( $member->isAdmin() )
		{
			Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
		}

		
		/* Build form */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_members.js', 'core', 'admin' ) );
		Output::i()->sidebar['actions'] = array();
		$form = new Form;

		$form->addHeader('member_preferences_system');

		/* Language */
		$languages = array( 0 => 'language_none' );
		foreach ( Lang::languages() as $lang )
		{
			$languages[ $lang->id ] = $lang->title;
		}
		$form->add( new Select( 'language', $member->language, TRUE, array( 'options' => $languages ) ) );

		if( $member->isAdmin() )
		{
			$form->add( new Select( 'acp_language', $member->acp_language, TRUE, array( 'options' => $languages ) ) );
		}

		/* Skin */
		$themes = array();
		foreach( Theme::themes() as $theme )
		{
			$themes[ $theme->id ] = $theme->_title;
		}
		$themes[0] = 'skin_none';

		$form->add( new Select( 'skin', ( $member->skin ) ?: 0, TRUE, array( 'options' => $themes ) ) );

		/* Content */
		$form->addHeader('member_preferences_content');
		$form->add( new YesNo( 'view_sigs', $member->members_bitoptions['view_sigs'], FALSE ) );

		/* Profile */
		$form->addHeader('member_preferences_profile');
		$form->add( new YesNo( 'pp_setting_count_visitors', $member->members_bitoptions['pp_setting_count_visitors'], FALSE ) );
		$form->add( new YesNo( 'pp_setting_moderate_followers', !$member->members_bitoptions['pp_setting_moderate_followers'] ) );

		/* Link behavior */
		$form->add( new Radio( 'link_pref', $member->linkPref() ?: Settings::i()->link_default, FALSE, array( 'options' => array(
		'unread'	=> 'profile_settings_cvb_unread',
		'last'	=> 'profile_settings_cvb_last',
		'first'	=> 'profile_settings_cvb_first'
		) ) ) );

	
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Language and Theme */
			$member->language = $values['language'];
			$member->skin = ( $values['skin'] ) ?: NULL;
			if( $member->isAdmin() )
			{
				$member->acp_language = $values['acp_language'];
			}

			/* Link Behavior */
			switch( $values['link_pref'] )
			{
				case 'last':
					$member->members_bitoptions['link_pref_unread'] = FALSE;
					$member->members_bitoptions['link_pref_last'] = TRUE;
					break;
				case 'unread':
					$member->members_bitoptions['link_pref_unread'] = TRUE;
					$member->members_bitoptions['link_pref_last'] = FALSE;
					break;
				default:
					$member->members_bitoptions['link_pref_unread'] = FALSE;
					$member->members_bitoptions['link_pref_last'] = FALSE;
					break;
			}

			/* Other */
			$member->members_bitoptions['view_sigs'] = $values['view_sigs'];
			$member->members_bitoptions['pp_setting_count_visitors']		= $values['pp_setting_count_visitors'];
			$member->members_bitoptions['pp_setting_moderate_followers']	= !$values['pp_setting_moderate_followers'];
			$member->save();
			
			Session::i()->log( 'acplog__members_edited_prefs', array( $member->name => FALSE ) );
			Output::i()->redirect( Url::internal( 'app=core&module=members&controller=members&do=view&id=' . $member->member_id ), 'saved' );
		}
		
		/* Display */	
		Output::i()->title		= $member->name;
		Output::i()->output	= $form;
	}
	
	/**
	 * Change Password
	 *
	 * @return	void
	 */
	protected function password() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/2', 404, '' );
		}
		
		/* Show Form */
		$form = new Form;
		
		// This looks weird, but we only want this showing here, and not anywhere else 'password' is used as a form key.
		Member::loggedIn()->language()->words['password_desc'] = Member::loggedIn()->language()->addToStack( 'force_password_reset_member', FALSE, array( 'sprintf' => array( $member->acpUrl()->csrf()->setQueryString( 'do', 'forcePassReset' ), Member::loggedIn()->language()->addToStack( 'force_password_reset_member_confirmmsg' ) ) ) );
		
		$form->add( new Password( 'password', '', TRUE, array( 'protect' => TRUE, 'confirm' => 'password_confirm', 'showMeter' => Settings::i()->password_strength_meter, 'minimumStrength' => 1, 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL ) ) );
		$form->add( new Password( 'password_confirm', '', TRUE, array( 'protect' => TRUE, 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			require_once \IPS\ROOT_PATH . "/system/3rd_party/phpass/phpass.php";
			$phpass = new PasswordStrength();

			if( (string) $values['password'] == $member->name OR (string) $values['password'] == $member->email )
			{
				$strength		= $phpass::STRENGTH_VERY_WEAK;
			}
			else
			{
				$strength = $phpass->classify( (string) $values['password'] );
			}

			if( !isset( Request::i()->proceed ) and ( Settings::i()->password_strength_meter_enforce and $strength < Settings::i()->password_strength_option ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'err_acp_password_strength', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'strength_' . $strength ), Member::loggedIn()->language()->addToStack( 'strength_' . Settings::i()->password_strength_option ) ) ) );
				$form->hiddenValues['proceed'] = TRUE;
				$form->actionButtons = array( Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( 'continue', 'submit', null, 'ipsButton ipsButton--primary', array( 'tabindex' => '2', 'accesskey' => 's' ) ) );
			}
			else
			{
				$changed = $member->changePassword( $values['password'] );
				if ( !$changed and Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
				{
					$member->setLocalPassword( $values['password'] );
					$member->save();
				}
				$member->invalidateSessionsAndLogins( TRUE, Session::i()->id );

				Session::i()->log( 'acplog__members_edited_password', array( $member->name => FALSE ) );

				Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
			}
		}
		
		/* Display */
		Output::i()->title	= Member::loggedIn()->language()->addToStack('change_password_for', FALSE, array( 'sprintf' => array( $member->name ) ) );
		Output::i()->outputTemplate = array( Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		Output::i()->output = Theme::i()->getTemplate('global')->block( 'password', $form, FALSE );
	}
	
	
	/**
	 * Force Password Reset
	 *
	 * @return	void
	 */
	protected function forcePassReset() : void
	{
		$handlers	= array(); // Login Handlers that can sync passwords (Internal, external database, LDAP, etc.)
		$internal	= FALSE;
		
		foreach( Login::methods() AS $handler )
		{
			if ( $handler->_enabled )
			{
				if ( $handler->canSyncPassword() )
				{
					$handlers[] = $handler;
					
					if ( !$internal AND ( $handler instanceof Standard ) )
					{
						$internal = TRUE;
					}
				}
			}
		}
		
		if ( !count( $handlers ) )
		{
			Output::i()->error( 'force_password_reset_no_handlers', '2C114/11', 403, '' );
		}
		
		/* Is this for a single member? */
		if ( isset( Request::i()->id ) )
		{
			/* Yes, let's check some stuff. First CSRF. */
			Session::i()->csrfCheck();
			
			/* Now, ACP restrictions round one. */
			Dispatcher::i()->checkAcpPermission( 'member_edit' );
			
			try
			{
				$member = Member::load( Request::i()->id );
				
				if ( !$member->member_id )
				{
					throw new OutOfRangeException;
				}
				
				/* ACP Restrictions, round two */
				if ( $member->isAdmin() )
				{
					Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
				}
				
				$canProcess		= FALSE;
				$externals		= array();
				foreach( $handlers AS $handler )
				{
					if ( $handler->canChangePassword( $member ) )
					{
						$canProcess = TRUE; // We only need to know if at least one can process a password change.
						if ( !( $handler instanceof Standard ) )
						{
							$externals[] = $handler->_title;
						}
					}
				}
				
				/* If we can't process at all, stop here. */
				if ( $canProcess === FALSE and !$internal )
				{
					Output::i()->error( 'force_password_reset_no_handlers_member', '2C114/13', 403, '' );
				}
				
				/* If we have external login handlers, we need to show an interstitual page indicating that with caveats. */
				if ( count( $externals ) )
				{
					$msg = Member::loggedIn()->language()->addToStack( 'force_password_reset_member_external', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $externals ) ) ) );
					$form = new Form( 'form', 'reset_password' );
					$form->hiddenValues['id'] = $member->member_id;
					$form->addMessage( $msg, 'ipsMessage ipsMessage--warning' );
					if ( $values = $form->values() )
					{
						/* Do it */
						$member->forcePasswordReset();
						Output::i()->redirect( $member->acpUrl() );
					}
					
					Output::i()->title = Member::loggedIn()->language()->addToStack( 'force_password_reset' );
					Output::i()->output = (string) $form;
					return;
				}
				else
				{
					/* Still here? We've already confirmed and we only have the internal login handler, so go ahead. */
					$member->forcePasswordReset();

					Session::i()->log( 'acplog__reset_password_member', array( $member->name => FALSE ) );

					Output::i()->redirect( $member->acpUrl() );
				}
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2C114/10', 403, '' );
			}
		}
		else
		{
			/* No, we're doing a mass-update. */
			Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			
			/* If we have the internal login handler and other external ones OR if internal is disabled, and we have external ones */
			if (  ( $internal AND count( $handlers ) > 1 ) OR ( !$internal and count( $handlers ) ) )
			{
				$titles = array();
				foreach( $handlers AS $ext )
				{
					if ( $ext instanceof Standard )
					{
						continue;
					}
					
					$titles[] = $ext->_title;
				}
				
				$title = Member::loggedIn()->language()->formatList( $titles );
				$msg = Member::loggedIn()->language()->addToStack( 'force_password_reset_warning_ext', FALSE, array( 'sprintf' => array( $title ) ) );
			}
			/* If internal is the only handler present */
			else if ( $internal AND count( $handlers ) == 1 )
			{
				$msg = Member::loggedIn()->language()->addToStack( 'force_password_reset_warning' );
			}
			/* If we're here, none of our login methods can process passwords, so we need to stop. */
			else
			{
				Output::i()->error( 'force_password_reset_no_handlers', '2C114/12', 403, '' );
			}
			
			/* Build out our form */
			$form = new Form( 'form', 'reset_password_pl' );
			$form->addMessage( $msg, 'ipsMessage ipsMessage--warning' );
			$form->addHeader( 'members_force_password_reset_acp_header' );
	
			$lastApp	= 'core';
	
			/* Now grab bulk mail extensions */
			foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
			{
				if( $extension->availableIn( 'passwordreset' ) )
				{
					/* See if we need a new form header - one per app */
					$_key		= explode( '_', $key );
	
					if( $_key[0] != $lastApp )
					{
						$lastApp	= $_key[0];
						$form->addHeader( $lastApp . '_bm_filters' );
					}
	
					/* Grab our fields and add to the form */
					$fields		= $extension->getSettingField( array() );
	
					foreach( $fields as $field )
					{
						$form->add( $field );
					}
				}
			}
			
			if ( $values = $form->values() )
			{
				$options = array();
				foreach( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) AS $key => $extension )
				{
					if ( $extension->availableIn( 'passwordreset' ) )
					{
						$_value = $extension->save( $values );
						
						if ( $_value )
						{
							$options[ $key ] = $_value;
						}
					}
				}
				
				Task::queue( 'core', 'ForcePasswordReset', $options, 1, array_keys( $options ) );

				Session::i()->log( 'acplog__reset_password_all' );

				Output::i()->redirect( Url::internal( "app=core&module=members&controller=members" ), 'saved' );
			}
			
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'force_password_reset' );
			Output::i()->output = (string) $form;
		}
	}
	
	/**
	 * Change Display Name
	 *
	 * @return	void
	 */
	protected function name() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();
		
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/U', 404, '' );
		}
		
		/* Did we change it? */
		$error = NULL;
		if ( Request::i()->name and Request::i()->name != $member->name )
		{
			/* Validate */
			if ( mb_strlen( Request::i()->name ) < Settings::i()->min_user_name_length )
			{
				$error = Member::loggedIn()->language()->addToStack( 'form_minlength', FALSE, array( 'pluralize' => array( Settings::i()->min_user_name_length ) ) );
			}
			elseif ( mb_strlen( Request::i()->name ) > Settings::i()->max_user_name_length )
			{
				$error = Member::loggedIn()->language()->addToStack( 'form_maxlength', FALSE, array( 'pluralize' => array( Settings::i()->max_user_name_length ) ) );
			}
			elseif ( !Login::usernameIsAllowed( Request::i()->name ) )
			{
				$error = Member::loggedIn()->language()->addToStack('form_bad_value');
			}
			elseif ( $message = Login::usernameIsInUse( Request::i()->name, $member ) )
			{
				$error = $message;
			}
			else
			{
				foreach( Db::i()->select( 'ban_content', 'core_banfilters', array("ban_type=?", 'name') ) as $bannedName )
				{
					if( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedName, '/' ) ) . '$/i', Request::i()->name ) )
					{
						$error = Member::loggedIn()->language()->addToStack('form_name_banned');
						break;
					}
				}
			}
			if ( $error )
			{
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( $error, 403 );
				}
				else
				{
					Output::i()->error( $error, '2C114/V', 403, '' );
				}
			}
			
			/* Change */
			$member->logHistory( 'core', 'display_name', array( 'old' => $member->name, 'new' => Request::i()->name, 'by' => 'manual' ) );
			foreach ( Login::methods() as $method )
			{
				try
				{
					$method->changeUsername( $member, $member->name, Request::i()->name );
				}
				catch( BadMethodCallException $e ){}
			}
			$member->name = Request::i()->name;
			$member->save();
			Widget::deleteCaches();
			Session::i()->log( 'acplog__members_edited_name', array( $member->name => FALSE ) );
		}
		
		/* OK */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( true );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
	}
	
	/**
	 * Change Email
	 *
	 * @return	void
	 */
	protected function email() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();
		
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/W', 404, '' );
		}
		
		/* Did we change it? */
		if ( Request::i()->email and Request::i()->email != $member->email )
		{
			/* Validate */
			$error = NULL;
			if ( filter_var( Request::i()->email, FILTER_VALIDATE_EMAIL ) === FALSE )
			{
				$error = Member::loggedIn()->language()->addToStack( 'form_email_bad' );
			}
			elseif ( $message = Login::emailIsInUse( Request::i()->email, $member ) )
			{
				$error = $message;
			}
			else
			{
				foreach ( Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'email' ) ) as $bannedEmail )
	 			{	 			
		 			if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedEmail, '/' ) ) . '$/i', Request::i()->email ) )
		 			{
		 				$error = Member::loggedIn()->language()->addToStack( 'form_email_banned' );
		 			}
	 			}
	 			if ( Settings::i()->allowed_reg_email !== '' AND $allowedEmailDomains = explode( ',', Settings::i()->allowed_reg_email )  )
				{
					$matched = FALSE;
					foreach ( $allowedEmailDomains AS $domain )
					{
						if( mb_stripos( Request::i()->email,  "@" . $domain ) !== FALSE )
						{
							$matched = TRUE;
						}
					}
					if ( count( $allowedEmailDomains ) AND !$matched )
					{
						$error = Member::loggedIn()->language()->addToStack( 'form_email_banned' );
					}
				}
			}
			if ( $error )
			{
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( $error, 403 );
				}
				else
				{
					Output::i()->error( $error, '2C114/X', 403, '' );
				}
			}
			
			/* Change */
			$oldEmail = $member->email;
			$member->email = Request::i()->email;
			$member->save();
			foreach ( Login::methods() as $method )
			{
				try
				{
					$method->changeEmail( $member, $oldEmail, Request::i()->email );
				}
				catch( BadMethodCallException $e ) {}
			}
			$member->logHistory( 'core', 'email_change', array( 'old' => $oldEmail, 'new' => Request::i()->email, 'by' => 'manual' ) );
			Event::fire( 'onEmailChange', $member, array( Request::i()->email, $oldEmail ) );
			$member->invalidateSessionsAndLogins( TRUE, Session::i()->id );
			Session::i()->log( 'acplog__members_edited_email', array( $member->name => FALSE ) );
		}
		
		/* OK */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( true );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
	}
	
	/**
	 * Change Photo
	 *
	 * @return	void
	 */
	public function photo() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		/* Load member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/3', 404, '' );
		}
		
		/* Are we just removing? */
		if ( isset( Request::i()->remove ) )
		{
			Session::i()->csrfCheck();
			
			$member->pp_photo_type = 'none';
			$member->pp_main_photo = NULL;
			$member->pp_thumb_photo = NULL;
			$member->photo_last_update = NULL;
			$member->save();
			Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
			$member->logHistory( 'core', 'photo', array( 'action' => 'remove' ) );
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}

		/* Build Form */
		$form = new Form;
		$form->ajaxOutput = TRUE;
		$customVal = NULL;
		if ( $member->pp_photo_type === 'custom' )
		{
			$customVal = File::get( 'core_Profile', $member->pp_main_photo );
		}
		$photoVars = explode( ':', $member->group['g_photo_max_vars'] );
		$form->add( new Upload( 'member_photo_upload', $customVal, FALSE, array( 'image' => array( 'maxWidth' => $photoVars[1], 'maxHeight' => $photoVars[2] ), 'allowStockPhotos' => TRUE, 'storageExtension' => 'core_Profile' ), NULL, NULL, NULL, 'member_photo_upload' ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{	
			/* Save main photo */
			if ( $values['member_photo_upload'] )
			{
				$member->pp_photo_type  = 'custom';
				$member->pp_main_photo  = (string) $values['member_photo_upload'];
				$member->pp_thumb_photo = (string) $values['member_photo_upload']->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE, TRUE );
				$member->photo_last_update = time();
			}
												
			/* Save and log */
			$member->save();
			Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
			$member->logHistory( 'core', 'photo', array( 'action' => 'new', 'type' => $member->pp_photo_type ) );
			
			/* Redirect */
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
		
		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->outputTemplate = array( Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		Output::i()->output = Theme::i()->getTemplate('global')->block( 'photo', $form, FALSE );
	}
	
	/**
	 * Change Login Method Settings
	 *
	 * @return	void
	 */
	protected function loginEdit() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member and Method */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
			
			$method = Handler::load( Request::i()->method );
			if ( !$method->canProcess( $member ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/Y', 404, '' );
		}
		
		/* Create form */
		$form = new Form;
		foreach ( $method->syncOptions( $member ) as $option )
		{
			if ( $option == 'photo' and !$member->group['g_edit_profile'] )
			{
				continue;
			}
			if ( $option == 'cover' and ( !$member->group['g_edit_profile'] or !$member->group['gbw_allow_upload_bgimage'] ) )
			{
				continue;
			}

			if ( $option == 'status' )
			{
				$checked = ( isset( $member->profilesync[ $option ] ) and array_key_exists( $method->id, $member->profilesync[ $option ]) );
			}
			else
			{
				$checked = ( isset( $member->profilesync[ $option ] ) and $member->profilesync[ $option ]['handler'] == $method->id );
			}
			$field = new YesNo( "profilesync_{$option}_admin", $checked, FALSE, array(), NULL, NULL, NULL, "profilesync_{$option}_{$method->id}" );
			if ( $checked and ( ( $option == 'status' and $error = $member->profilesync[ $option ][ $method->id ]['error'] ) or ( $option != 'status' and $error = $member->profilesync[ $option ]['error'] ) ) )
			{
				$field->description = Theme::i()->getTemplate( 'system', 'core', 'front' )->settingsLoginMethodSynError( $error );
			}		
			$form->add( $field );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$profileSync = $member->profilesync;
			$changes = array();
			
			foreach ( $values as $k => $v )
			{
				$option = mb_substr( $k, 12, -6 );
				if ( $option === 'status' )
				{
					if ( isset( $member->profilesync[ $option ][ $method->id ] ) )
					{
						if ( !$v )
						{
							unset( $profileSync[ $option ][ $method->id ] );
							$changes[ $option ] = FALSE;
						}
					}
					else
					{
						if ( $v )
						{
							$profileSync[ $option ][ $method->id ] = array( 'lastsynced' => NULL, 'error' => NULL );
							$changes[ $option ] = TRUE;
						}
					}
				}
				else
				{
					if ( isset( $member->profilesync[ $option ] ) and $member->profilesync[ $option ]['handler'] == $method->id )
					{
						if ( !$v )
						{
							unset( $profileSync[ $option ] );
							$changes[ $option ] = FALSE;
						}
					}
					else
					{
						if ( $v )
						{
							$profileSync[ $option ] = array( 'handler' => $method->id, 'ref' => NULL, 'error' => NULL );
							$changes[ $option ] = TRUE;
						}
					}
				}
			}
			
			if ( count( $changes ) )
			{
				$member->logHistory( 'core', 'social_account', array( 'changed' => $changes, 'handler' => $method->id, 'service' => $method::getTitle() ) );
			}
			
			$member->profilesync = $profileSync;
			$member->save();
			$member->profileSync();
			Session::i()->log( 'acplog__members_edited_login', array( $member->name => FALSE ) );
			
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
		
		/* Display */
		Output::i()->output = $form;
	}
	
	/**
	 * Disassociate Login Method
	 *
	 * @return	void
	 */
	protected function loginDelete() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();
		
		/* Load Member and Method */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
			
			$method = Handler::load( Request::i()->method );
			if ( !$method->canProcess( $member ) )
			{
				throw new OutOfRangeException;
			}
			
			$canDisassociate = FALSE;
			foreach ( Login::methods() as $_method )
			{
				if ( $_method->id != $method->id and $_method->canProcess( $member ) )
				{
					$canDisassociate = TRUE;
					break;
				}
			}
			if ( !$canDisassociate )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/Y', 404, '' );
		}
		
		/* Do it */
		$method->disassociate( $member );
		Session::i()->log( 'acplog__members_edited_login_unlink', array( $member->name => FALSE ) );
		
		/* Redirect */
		Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}
	
	/**
	 * Find IP Addresses
	 *
	 * @return	void
	 */
	public function ip() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'membertools_ip' );
		
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/5', 404, '' );
		}
		
		/* Init Table */
		$ips				= $member->ipAddresses();
		$geoLocationData	= array();

		try
		{
			$geoLocationData = GeoLocation::getByIp( array_keys( $ips ) );
		}
		catch ( BadFunctionCallException $e )
		{
			foreach( array_keys( $ips ) as $ip )
			{
				$geoLocationData[ $ip ] = Member::loggedIn()->language()->addToStack('geolocation_enable_service');
			}
		}
		catch ( Exception $e )
		{
			foreach( array_keys( $ips ) as $ip )
			{
				$geoLocationData[ $ip ] = Member::loggedIn()->language()->addToStack('unknown');
			}
		}

		$table = new \IPS\Helpers\Table\Custom( $ips, Url::internal( "app=core&module=members&controller=members&id={$member->member_id}&do=ip" ) );
		$table->langPrefix  = 'members_iptable_';
		$table->mainColumn  = 'ip';
		$table->sortBy      = $table->sortBy ?: 'last';
		$table->quickSearch = 'ip';
		$table->include = array( 'ip', 'location', 'count', 'first', 'last' );
		
		/* Parsers */
		$table->parsers = array(
			'first'			=> function( $val )
			{
				return $val ? DateTime::ts( $val )->localeDate() : Member::loggedIn()->language()->addToStack('unknown');
			},
			'last'			=> function( $val )
			{
				return $val ? DateTime::ts( $val )->localeDate() : Member::loggedIn()->language()->addToStack('unknown');
			},
			'location'	=> function( $val, $row ) use ( $geoLocationData )
			{
				return $geoLocationData[ $row['ip'] ] ?: Member::loggedIn()->language()->addToStack('unknown');
			},
		);
		
		/* Buttons */
		$table->rowButtons = function( $row )
		{
			return array(
				'view'	=> array(
					'icon'		=> 'search',
					'title'		=> 'see_uses',
					'link'		=> Url::internal( 'app=core&module=members&controller=ip&ip=' ) . $row['ip'],
				),
			);
		};
		
		/* Display */
		Output::i()->title			= $member->name;
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=members" ), 'members' );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), $member->name );
		Output::i()->breadcrumb[] = array( NULL, 'ip_addresses' );
		Output::i()->output		= Theme::i()->getTemplate( 'forms' )->blurb( Member::loggedIn()->language()->addToStack( 'members_ips_info', FALSE, array( 'sprintf' => array( $member->name ) ) ), TRUE, TRUE ) . $table . Theme::i()->getTemplate( 'members' )->geoipDisclaimer();
	}
	
	/**
	 * Photo Resize
	 *
	 * @return	void
	 */
	public function photoResize() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		/* Load member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/3', 404, '' );
		}
		if ( $member->isAdmin() )
		{
			Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
		}
		
		/* Get photo */
		$image = File::get( 'core_Profile', $member->pp_main_photo );
	
		/* Build Form */
		$form = new Form;
		$form->add( new WidthHeight( 'member_photo_resize', NULL, TRUE, array( 'image' => $image ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Create new file */
			$original = File::get( 'core_Profile', $member->pp_main_photo );
			$image = Image::create( $original->contents() );
			$image->resize( $values['member_photo_resize'][0], $values['member_photo_resize'][1] );
			
			/* Save the new */
			$member->pp_main_photo = File::create( 'core_Profile', $original->filename, (string) $image );
			$member->pp_thumb_photo = (string) $member->pp_main_photo->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE, TRUE );
			$member->save();
			
			/* Delete the original */
			$original->delete();
			
			/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
				Widget::deleteCaches();
						
			/* Log and redirect */
			Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
			$member->logHistory( 'core', 'photo', array( 'action' => 'resize' ) );
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
		
		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->outputTemplate = array( Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		Output::i()->output = Theme::i()->getTemplate('global')->block( 'member_photo_resize', $form, FALSE );
	}
	
	/**
	 * Crop Photo
	 *
	 * @return	void
	 */
	protected function photoCrop() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		/* Load member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/T', 404, '' );
		}
		if ( $member->isAdmin() )
		{
			Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
		}
		
		/* Get the photo */
		$original = File::get( 'core_Profile', $member->pp_main_photo );
		$image = Image::create( $original->contents() );
		
		/* Work out which dimensions to suggest */
		if ( $image->width < $image->height )
		{
			$suggestedWidth = $suggestedHeight = $image->width;
		}
		else
		{
			$suggestedWidth = $suggestedHeight = $image->height;
		}
		
		/* Build form */
		$form = new Form( 'photo_crop', 'save' );
		$form->class = 'ipsForm_noLabels';
		$form->add( new Custom('photo_crop', array( 0, 0, $suggestedWidth, $suggestedHeight ), FALSE, array(
			'getHtml'	=> function( $field ) use ( $original, $member )
			{
				return Theme::i()->getTemplate('members', 'core', 'global')->photoCrop( $field->name, $field->value, $member->acpUrl()->setQueryString( 'do', 'cropPhotoGetPhoto' )->csrf() );
			}
		) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			try
			{
				/* Create new file */
				$image->cropToPoints( $values['photo_crop'][0], $values['photo_crop'][1], $values['photo_crop'][2], $values['photo_crop'][3] );
				
				/* Delete the current thumbnail */					
				if ( $member->pp_thumb_photo )
				{
					try
					{
						File::get( 'core_Profile', $member->pp_thumb_photo )->delete();
					}
					catch ( Exception $e ) { }
				}
								
				/* Save the new */
				$cropped = File::create( 'core_Profile', $original->originalFilename, (string) $image );
				$member->pp_thumb_photo = (string) $cropped->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE );
				$member->save();
				Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
				$member->logHistory( 'core', 'photo', array( 'action' => 'crop' ) );

				/* Delete the temporary full size cropped image */
				$cropped->delete();

				/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
				Widget::deleteCaches();
								
				/* Redirect */
				Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
			}
			catch ( Exception $e )
			{
				$form->error = Member::loggedIn()->language()->addToStack('photo_crop_bad');
			}
		}
		
		Output::i()->output = $form;
	}
	
	/**
	 * Get photo for cropping
	 * If the photo is on a different domain to the JS that handles cropping,
	 * it will be blocked because of CORS. See notes in Cropper documentation.
	 *
	 * @return	void
	 */
	protected function cropPhotoGetPhoto() : void
	{
		Session::i()->csrfCheck();
		$original = File::get( 'core_Profile', Member::load( Request::i()->id )->pp_main_photo );
		$headers = array( "Content-Disposition" => Output::getContentDisposition( 'inline', $original->filename ) );
		Output::i()->sendOutput( $original->contents(), 200, File::getMimeType( $original->filename ), $headers );
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	protected function _coverPhotoStorageExtension(): string
	{
		return 'core_Profile';
	}
	
	/**
	 * Get Cover Photo
	 *
	 * @return	CoverPhoto
	 */
	protected function _coverPhotoGet(): CoverPhoto
	{
		return Member::load( Request::i()->id )->coverPhoto();
	}
	
	/**
	 * Set Cover Photo
	 *
	 * @param	CoverPhoto	$photo	New Photo
	 * @param	string|null					$type	'new', 'remove', 'reposition'
	 * @return	void
	 */
	protected function _coverPhotoSet( CoverPhoto $photo, ?string $type=NULL ) : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		$member = Member::load( Request::i()->id );
		if ( $member->isAdmin() )
		{
			Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
		}
		
		/* Disable syncing */
		$profileSync = $member->profilesync;
		if ( isset( $profileSync['cover'] ) )
		{
			unset( $profileSync['cover'] );
			$member->profilesync = $profileSync;
		}
		
		$member->pp_cover_photo = (string) $photo->file;
		$member->pp_cover_offset = $photo->offset;
		
		/* Reset Profile Complete flag in case this was an optional step */
		$member->members_bitoptions['profile_completed'] = FALSE;
			
		$member->save();
		if ( $type != 'reposition' )
		{
			$member->logHistory( 'core', 'coverphoto', array( 'action' => $type ) );
		}
		Session::i()->log( 'acplog__members_edited_cover_photo', array( $member->name => FALSE ) );
	}
	
	/**
	 * Get URL to return to after editing cover photo
	 *
	 * @return	Url
	 */
	protected function _coverPhotoReturnUrl(): Url
	{
		return parent::_coverPhotoReturnUrl()->setQueryString( 'do', 'view' );
	}
	
	/**
	 * Unlock
	 *
	 * @return	void
	 */
	public function unlock() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/9', 404, '' );
		}
		/* Remove login failures */
		Db::i()->delete( 'core_login_failures', [ 'login_member_id=?', $member->member_id ] );
		$member->failed_login_count = 0;
		$member->failed_mfa_attempts = 0;
		$mfaDetails = $member->mfa_details;
		if ( isset( $mfaDetails['_lockouttime'] ) )
		{
			unset( $mfaDetails['_lockouttime'] );
			$member->mfa_details = $mfaDetails;
		}
		$member->save();
		$member->logHistory( 'core', 'login', array( 'type' => 'unlock' ) );
		
		Session::i()->log( 'acplog__members_unlocked', array( $member->name => FALSE ) );
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
	}
			
	/**
	 * Flag as spammer
	 *
	 * @return	void
	 */
	public function spam() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$member = Member::load( Request::i()->id );
			
			if ( $member->member_id == Member::loggedIn()->member_id or $member->modPermission() or $member->isAdmin() )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_flag_spam_self', '2C114/8', 404, '' );
		}

		if( !$member->member_id )
		{
			Output::i()->error( 'node_delete', '2C114/Z', 404, '' );
		}
				
		if ( Request::i()->status )
		{
			$member->flagAsSpammer();
			Session::i()->log( 'modlog__spammer_flagged', array( $member->name => FALSE ) );
		}
		else
		{
			$member->unflagAsSpammer();
			Session::i()->log( 'modlog__spammer_unflagged', array( $member->name => FALSE ) );
		}
				
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( Member::loggedIn()->language()->addToStack( Request::i()->status ? 'account_flagged' : 'account_unflagged' ) );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), ( Request::i()->status ? 'account_flagged' : 'account_unflagged' ) );
		}
	}
	
	/**
	 * Approve
	 *
	 * @return	void
	 */
	public function approve() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/A', 404, '' );
		}
		
		$member->logHistory( 'core', 'account', array( 'type' => 'admin_validated' ) );
		$member->validationComplete( Member::loggedIn() );
		
		if ( !Db::i()->select( 'COUNT(*)', 'core_validating', array( 'user_verified=?', TRUE ) )->first() )
		{
			AdminNotification::remove( 'core', 'NewRegValidate' );
		}
		
		/* Log */
		Session::i()->log( 'acplog__members_approved', array( $member->name => FALSE ) );

		if ( Request::i()->isAjax() )
		{
			if ( isset( Request::i()->queue ) )
			{
				Output::i()->json( NewRegValidate::queueHtml() );
			}
			else
			{			
				Output::i()->json( 'OK' );
			}
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'account_approved' );
		}
	}
	
	/**
	 * Resend Validation Email
	 *
	 * @return	void
	 */
	public function resendEmail() : void
	{
		Session::i()->csrfCheck();
		
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/B', 404, '' );
		}
		
		/* Send */
		foreach ( Db::i()->select( '*', 'core_validating', array( 'member_id=?', $member->member_id ) ) as $row )
		{
			$plainTextKey = '';
			if( !empty( $row['security_key'] ) )
			{
				$plainTextKey = Encrypt::fromTag( $row['security_key'] )->decrypt();
			}

			if ( !$row['user_verified'] )
			{
				/* Lost Pass */
				if ( $row['lost_pass'] )
				{
					Email::buildFromTemplate( 'core', 'lost_password_init', array( $member, $row['vid'], $plainTextKey  ), Email::TYPE_TRANSACTIONAL )->send( $member );
				}
				/* New Reg */
				elseif ( $row['new_reg'] )
				{
					Email::buildFromTemplate( 'core', 'registration_validate', array( $member, $row['vid'], $plainTextKey  ), Email::TYPE_TRANSACTIONAL )->send( $member );
				}
				/* Email Change */
				elseif ( $row['email_chg'] )
				{
					Email::buildFromTemplate( 'core', 'email_change', array( $member, $row['vid'], $plainTextKey  ), Email::TYPE_TRANSACTIONAL )->send( $member );
				}
				/* Forgot security answers */
				elseif ( $row['forgot_security'] )
				{
					Email::buildFromTemplate( 'core', 'mfaRecovery', array( $member, $row['vid'], $plainTextKey  ), Email::TYPE_TRANSACTIONAL )->send( $member );
				}
			}
		}
		
		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( Member::loggedIn()->language()->get('validation_email_resent') );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'validation_email_resent' );
		}
	}
	
	/**
	 * Merge
	 *
	 * @return	void
	 */
	public function merge() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'members_merge' );
		
		/* Load first member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/6', 404, '' );
		}
		
		/* Build form */
		$form = new Form;
		$form->add( new FormMember( 'member_merge', NULL, TRUE ) );
		$form->add( new Select( 'member_merge_keep', 1, TRUE, array( 'options' => array( 1 => Member::loggedIn()->language()->addToStack( 'member_merge_keep_1', FALSE, array( 'sprintf' => array( $member->name ) ) ), 2 => 'member_merge_keep_2' ) ) ) );
		
		/* Merge */
		if ( $values = $form->values() )
		{
			/* Which account are we keeping */
			if ( $values['member_merge_keep'] == 1 )
			{
				$accountToKeep		= $member;
				$accountToDelete	= $values['member_merge'];
			}
			else
			{
				$accountToDelete	= $member;
				$accountToKeep		= $values['member_merge'];
			}
			
			/* Do it */
			try
			{
				$accountToKeep->merge( $accountToDelete );
			}
			catch( InvalidArgumentException $e )
			{
				Output::i()->error( $e->getMessage(), '3C114/J', 403, '' );
			}
						
			/* Delete the account */
			$accountToDelete->delete( FALSE );
			
			/* Log */
			Session::i()->log( 'acplog__members_merge', array( $accountToKeep->name => FALSE, $accountToDelete->name => FALSE ) );
			
			/* Boink */
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$accountToKeep->member_id}" ), 'saved' );
		}
		
		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->outputTemplate = array( Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		Output::i()->output = Theme::i()->getTemplate('global')->block( 'merge', $form, FALSE );
	}
	
	/**
	 * Ban
	 *
	 * @return	void
	 */
	public function ban() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_ban' );
		
		/* Load member */
		try
		{
			$member = Member::load( Request::i()->id );
			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_ban_admin' );
			}
			if ( $member->member_id == Member::loggedIn()->member_id )
			{
				throw new OutOfRangeException;
			}
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/7', 404, '' );
		}
		
		/* Just do it? */
		if ( Request::i()->permban )
		{
			Session::i()->csrfCheck();
			
			$member->temp_ban = -1;
			$member->save();
			
			$member->logHistory( 'core', 'warning', array( 'restrictions' => array( 'ban' => array( 'old' => NULL, 'new' => $member->temp_ban ) ) ) );
			Session::i()->log( 'acplog__members_edited', array( $member->name => FALSE ) );
			
			if ( !Db::i()->select( 'COUNT(*)', 'core_validating', array( 'user_verified=?', TRUE ) )->first() )
			{
				AdminNotification::remove( 'core', 'NewRegValidate' );
			}

			if ( Request::i()->isAjax() )
			{
				if ( isset( Request::i()->queue ) )
				{
					Output::i()->json( NewRegValidate::queueHtml() );
				}
				else
				{			
					Output::i()->json( 'OK' );
				}
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'account_banned' );
			}
		}
		else
		{
			/* Get existing banned IPs */
			$bannedIps = iterator_to_array( Db::i()->select( 'ban_content', 'core_banfilters', array( 'ban_type=?', 'ip' ) ) );
			
			/* Build form */
			$form = new Form;
			$form->add( new Date( 'member_ban_until', $member->temp_ban, FALSE, array(
				'time'				=> TRUE,
				'unlimited'			=> -1,
				'unlimitedLang'		=> 'permanently',
			), NULL, NULL, NULL, 'member_ban_until' ) );
			
			if ( $member->temp_ban === 0 )
			{
				$form->add( new Select( 'member_ban_group', $member->member_group_id, FALSE, array( 'options' => Group::groups( FALSE, FALSE ), 'parse' => 'normal' ), NULL, NULL, NULL, 'member_ban_group' ) );

				$memberIps = array();
				foreach( $member->ipAddresses() as $k => $v )
				{
					$memberIps[ $k ] = $k;
				}
				$form->add( new CheckboxSet( 'member_ban_ips', array_intersect( $memberIps, $bannedIps ), FALSE, array( 'options' => $memberIps, 'multiple' => TRUE ), NULL, NULL, NULL, 'member_ban_ips' ) );
			}
			
			/* Ban */
			if ( $values = $form->values() )
			{
				$_existingValue	= $member->temp_ban;

				if ( $values['member_ban_until'] === -1 )
				{
					$member->temp_ban = -1;
				}
				elseif ( !$values['member_ban_until'] )
				{
					$member->temp_ban = 0;
				}
				else
				{
					$member->temp_ban = $values['member_ban_until']->getTimestamp();
				}
				
				if ( $_existingValue != $member->temp_ban )
				{
					$member->logHistory( 'core', 'warning', array( 'restrictions' => array( 'ban' => array( 'old' => $_existingValue, 'new' => $member->temp_ban ) ) ) );
				}

				if ( isset( $values['member_ban_group'] ) AND $values['member_ban_group'] != $member->member_group_id )
				{
					$member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'manual', 'old' => $member->member_group_id, 'new' => $values['member_ban_group'] ) );
					$member->member_group_id = $values['member_ban_group'];
				}

				if ( isset( $values['member_ban_ips'] ) )
				{
					foreach ( $memberIps as $key => $ip )
					{
						if ( in_array( $key, $values['member_ban_ips'] ) and !in_array( $ip, $bannedIps ) )
						{
							Db::i()->insert( 'core_banfilters', array( 'ban_type' => 'ip', 'ban_content' => $ip, 'ban_date' => time(), 'ban_reason' => $member->name ) );
						}
						elseif ( !in_array( $key, $values['member_ban_ips'] ) and in_array( $ip, $bannedIps ) )
						{
							Db::i()->delete( 'core_banfilters', array( 'ban_content=? AND ban_type=?', $ip, 'ip' ) );
						}
					}

					unset( Store::i()->bannedIpAddresses );
				}

				$member->save();

				Session::i()->log( 'acplog__members_edited', array( $member->name => FALSE ) );
				
				Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
			}
			
			/* Display */
			if ( Request::i()->isAjax() )
			{
				Output::i()->outputTemplate = array( Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
			}
			Output::i()->output = Theme::i()->getTemplate('global')->block( 'ban', $form, FALSE );
		}
	}
	
	/**
	 * Login as member
	 *
	 * @return	void
	 */
	public function login() : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_login' );
		Session::i()->csrfCheck();

		/* Check compatibility with front-end SSO */
		$appBlocks = [];
		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $app => $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() AND !$ext->supportsLoginAs() )
			{
				$appBlocks[] = Member::loggedIn()->language()->addToStack( '__app_' . explode( '_', $app )[0], FALSE );
			}
		}

		if( count( $appBlocks ) )
		{
			Output::i()->error( Member::loggedIn()->language()->addToStack( 'sso_signin_as_unsupported', FALSE, [ 'pluralize' => [ count( $appBlocks ) ],'htmlsprintf' =>  [ Member::loggedIn()->language()->formatList( $appBlocks ) ] ] ), '1C114/16', 403, '' );
		}
		
		/* Load Member and Admin*/
		$member = Member::load( Request::i()->id );
		$admin = Member::loggedIn();
		
		/* Generate a hash and store it in \IPS\Data\Store */
		$key = Login::generateRandomString();
		Store::i()->admin_login_as_user = $key;
		
		/* Log It */
		Session::i()->log( 'acplog__members_loginas', array( $member->name => FALSE ) );
		
		/* Redirect to front controller to update session */
		Output::i()->redirect( Url::internal( "app=core&module=system&controller=login&do=loginas&admin={$admin->member_id}&id={$member->member_id}&key={$key}", 'front' ) );
	}
	
	/**
	 * Delete Content
	 *
	 * @return	void
	 */
	public function deleteContent() : void
	{
		Dispatcher::i()->checkAcpPermission( 'membertools_delete' );
		
		/* Load Member */
		$member = Member::load( Request::i()->id );

		if( !$member->member_id )
		{
			Output::i()->error( 'node_delete', '2C114/Y', 404, '' );
		}
		
		/* Build form */
		$form = new Form('delete_content', 'delete');
		$form->add( new Radio( 'hide_or_delete_content', NULL, TRUE, array( 'options' => array( 'hide' => 'hide', 'delete' => 'delete' ) ) ) );
		if ( $values = $form->values() )
		{
			$member->hideOrDeleteAllContent( $values['hide_or_delete_content'] );

			/* Log It */
			Session::i()->log( 'acplog__members_' . $values['hide_or_delete_content'] . 'content', array( $member->name => FALSE ) );
			
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'deleted' );
		}
		
		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->outputTemplate = array( Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		
		Output::i()->output = Theme::i()->getTemplate('global')->block( 'deletecontent', $form, FALSE );
	}
	
	/**
	 * Delete Guest Content
	 *
	 * @return	void
	 */
	public function deleteGuestContent() : void
	{
		Dispatcher::i()->checkAcpPermission( 'membertools_delete' );
		
		$form = new Form;
		$form->add( new Text( 'guest_name_to_delete', NULL, TRUE ) );
		if ( $values = $form->values() )
		{
			$classes = array();
			foreach ( Content::routedClasses( FALSE, TRUE ) as $class )
			{
				if ( isset( $class::$databaseColumnMap['author'] ) and isset( $class::$databaseColumnMap['author_name'] ) )
				{
					Task::queue( 'core', 'MemberContent', array( 'member_id' => 0, 'name' => $values['guest_name_to_delete'], 'class' => $class, 'action' => 'delete' ) );
				}
			}
			
			Session::i()->log( 'acplog__deleted_guest_content', array( $values['guest_name_to_delete'] => FALSE ) );
			
			Output::i()->redirect( Url::internal( "app=core&module=moderation&controller=spam&searchResult=guest_captcha" ), 'deleted' );
		}
		
		Output::i()->output = $form;
	}

	/**
	 * Delete Member
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_delete' );

		/* Load member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( $member->isAdmin() )
			{
				Dispatcher::i()->checkAcpPermission( 'member_delete_admin' );
			}

			if( $member->member_id == Member::loggedIn()->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/7', 404, '' );
		}

		/* Build Form */
		$form = new Form( 'deletiontype', 'delete' );
		$form->addMessage( Member::loggedIn()->language()->get('delete_member_warn') );
		$form->add( new Radio( 'member_deletion_content', 'delete', FALSE, array( 'options' => array( 'delete' => 'delete_content', 'hide' => 'hide_content', 'leave' => 'leave_content' ), 'toggles' => array( 'hide' => array( 'member_deletion_keep_name' ), 'leave' => array( 'member_deletion_keep_name' ) ) ) ) );

		$options = array(
			'keep_name'		=>	'keep_name',
			'remove_name'	=>	'remove_name'
		);
		$form->add( new Radio( 'member_deletion_keep_name', 'keep_name', FALSE, array( 'options' => $options ), NULL, NULL, NULL, 'member_deletion_keep_name' ) );
		Member::loggedIn()->language()->words['keep_name'] = sprintf( Member::loggedIn()->language()->get('keep_name'), htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', false ) );

		/* Handle Submissions */
		if ( $values = $form->values() )
		{
			Session::i()->log( 'acplog__members_deleted_id', array( $member->name => FALSE, $member->member_id => FALSE ) );

			switch( $values['member_deletion_content'] )
			{
				case 'delete':
					$member->hideOrDeleteAllContent( 'delete' );
					$member->delete( FALSE );
					break;
				case 'hide':
					$member->hideOrDeleteAllContent( 'hide' );
					$member->delete( TRUE, $values['member_deletion_keep_name'] == 'keep_name' );
					break;
				case 'leave':
					$member->delete( TRUE, $values['member_deletion_keep_name'] == 'keep_name' );
					break;
			}

			Output::i()->redirect( Url::internal("app=core&module=members&controller=members" )->getSafeUrlFromFilters(), 'deleted' );
		}

		/* Display Form */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'delete' );
		Output::i()->output = $form;
	}
	
	/**
	 * Admin Details
	 *
	 * @return	void
	 */
	public function adminDetails() : void
	{
		$details = array(
					'username'		=> Member::loggedIn()->name,
					'email_address'	=> Member::loggedIn()->email,
				);
				
		$canChangePassword = FALSE;
		foreach ( Login::methods() as $method )
		{
			if ( $method->canChangePassword( Member::loggedIn() ) )
			{
				$details['password'] = Member::loggedIn()->language()->addToStack('password_hidden');
			}
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('change_details');
		Output::i()->output	= Theme::i()->getTemplate( 'members', 'core' )->adminDetails( $details );
	}
	
	/**
	 * Admin Password
	 *
	 * @return	void
	 */
	protected function adminPassword() : void
	{
		$form = new Form( 'form' );
		$form->add( new Password( 'current_password', '', TRUE, array( 'protect' => TRUE, 'validateFor' => Member::loggedIn(), 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "current-password" ) ) );
		$form->add( new Password( 'new_password', '', TRUE, array( 'protect' => TRUE, 'showMeter' => Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'strengthMember' => Member::loggedIn(), 'htmlAutocomplete' => "new-password" ) ) );
		$form->add( new Password( 'confirm_new_password', '', TRUE, array( 'protect' => TRUE, 'confirm' => 'new_password', 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "new-password" ) ) );
		
		if ( $values = $form->values() )
		{
			/* Save it */
			Member::loggedIn()->changePassword( $values['new_password'] );
			
			/* Invalidate sessions except this one */
			Member::loggedIn()->invalidateSessionsAndLogins( TRUE, Session::i()->id );
			if( isset( Request::i()->cookie['login_key'] ) )
			{
				Device::loadOrCreate( Member::loggedIn() )->updateAfterAuthentication( TRUE );
			}
			
			/* Log */
			Session::i()->log( 'acplogs__admin_pass_updated' );
			
			/* Redirect */
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=adminDetails" ), 'saved' );
		}

		Output::i()->output = Theme::i()->getTemplate('global')->block( Member::loggedIn()->language()->addToStack('change_password'), $form, FALSE );
	}
	
	/**
	 * Admin Email
	 *
	 * @return	void
	 */
	public function adminEmail() : void
	{
		$form = new Form( 'form' );
		$form->add( new FormEmail( 'email_address', NULL, TRUE, array( 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete'	=> "email" ) ) );
		
		if ( $values = $form->values() )
		{
			/* Change email */
			$oldEmail = Member::loggedIn()->email;
			Member::loggedIn()->email = $values['email_address'];
			Member::loggedIn()->save();
			foreach ( Login::methods() as $method )
			{
				try
				{
					$method->changeEmail( Member::loggedIn(), $oldEmail, $values['email_address'] );
				}
				catch( BadMethodCallException $e ) {}
			}
			Member::loggedIn()->logHistory( 'core', 'email_change', array( 'old' => $oldEmail, 'new' => $values['email_address'], 'by' => 'manual' ) );
			Event::fire( 'onEmailChange', Member::loggedIn(), array( $values['email_address'], $oldEmail ) );
			
			/* Invalidate sessions except this one */
			Member::loggedIn()->invalidateSessionsAndLogins( TRUE, Session::i()->id );
			if( isset( Request::i()->cookie['login_key'] ) )
			{
				Device::loadOrCreate( Member::loggedIn() )->updateAfterAuthentication( TRUE );
			}
			
			/* Log */
			Session::i()->log( 'acplogs__admin_email_updated' );
			 	
			/* Redirect */
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=adminDetails" ), 'saved' );
		}
		Output::i()->output = Theme::i()->getTemplate('global')->block( Member::loggedIn()->language()->addToStack('change_email'), $form, FALSE );
	}
	
	/**
	 * Recount Content Item Count
	 *
	 * @return	void
	 */
	public function recountContent() : void
	{
		Session::i()->csrfCheck();
		
		if ( !Request::i()->prompt )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=members&controller=reset&do=posts' )->csrf() );
		}
		
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_recount_content' );
		
		/* Load Member */
		$member = Member::load( Request::i()->id );
		
		/* Rebuild */
		$member->recountContent();
		
		/* redirect */
		Session::i()->log( 'acplog__members_edited_content', array( $member->name => FALSE ) );
		Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}
	
	/**
	 * Recount Reputation Count
	 *
	 * @return	void
	 */
	public function recountReputation() : void
	{
		Session::i()->csrfCheck();

		if ( !Request::i()->prompt )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=members&controller=reset&do=rep' )->csrf() );
		}
		
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_recount_content' );
		
		/* Load Member */
		$member = Member::load( Request::i()->id );
		
		/* Rebuild */
		$member->recountReputation();
		
		/* redirect */
		Session::i()->log( 'acplog__members_edited_rep', array( $member->name => FALSE ) );
		Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}

	/**
	 * Remove reputation for a member
	 *
	 * @return	void
	 */
	public function removeReputation() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();

		/* Load Member */
		$member = Member::load( Request::i()->id );

		if( isset( Request::i()->type ) and in_array( Request::i()->type, array( 'given', 'received' ) ) )
		{
			/* Rebuild */
			$member->removeReputation( Request::i()->type );
		}
		
		Session::i()->log( 'acplog__member_reaction_removed_' . Request::i()->type , array( $member->name => FALSE ) );

		/* redirect */
		Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}
	
	/**
	 * Import
	 *
	 * @return	void
	 */
	public function import() : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_add' );
		
		$maxMemberId = 16777215; // This is the maximum value for UNSIGNED MEDIUMINT. Even though nowadays we use BIGINT, older installs might still have MEDIUMINT, plus setting the limit to lower than what the limit actually is ensures there's room for future members.

		$wizard = new Wizard(
			array(
				/* Step 1: Upload .csv file */
				'import_upload_csv'		=> function()
				{
					$form = new Form( 'csv_form', 'continue' );
					$form->attributes = array( 'data-bypassAjax' => true );
					$form->add( new Upload( 'import_members_csv_file', NULL, TRUE, array( 'temporary' => TRUE, 'allowedFileTypes' => array( 'csv' ) ), function( $val ) {
						$fh = fopen( $val, 'r' );
						$r = fgetcsv( $fh );
						fclose( $fh );
						if ( empty( $r ) )
						{
							throw new DomainException('import_members_csv_file_err');
						}
					} ) );
					$form->add( new YesNo( 'import_members_contains_header', TRUE ) );
					if ( $values = $form->values() )
					{
						$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
						move_uploaded_file( $values['import_members_csv_file'], $tempFile );
			
						return array( 'file' => $tempFile, 'header' => $values['import_members_contains_header'] );
					}
					return (string) $form;
				},
				/* Step 2: Select Columns */
				'import_select_cols'	=> function( $data ) use ( $maxMemberId )
				{										
					/* Init */
					$fh = fopen( $data['file'], 'r' );
					$form = new Form( 'cols_form', 'continue' );
					
					/* Basic settings like fallback group */
					$form->addHeader( 'import_members_import_settings' );
					$groups = array();
					foreach ( Group::groups( TRUE, FALSE ) as $group )
					{
						$groups[ $group->g_id ] = $group->name;
					}
					$form->add( new Select( 'import_members_fallback_group', Settings::i()->member_group, FALSE, array( 'options' => $groups ) ) );
					$form->add( new YesNo( 'import_members_send_confirmation' ) );
					
					/* Init Matrix */
					$form->addHeader( 'import_members_csv_details' );
					$form->addMessage( 'import_date_explain' );
					$matrix = new Matrix;
					$matrix->langPrefix = FALSE;
					$matrix->manageable = FALSE;
					
					/* Define matrix columns with available places we can import data to */
					$matrix->columns = array(
						'import_column'	=> function( $key, $value, $data )
						{
							return $value;
						},
						'import_as'	=> function( $key, $value, $data )
						{
							$importOptions =  array(
								NULL	=> 'do_not_import',
								'import_basic_data'	=> array(
									'member_id'		=> 'member_id',
									'name'			=> 'username',
									'email'			=> 'email_address',
									'member_posts'	=> 'members_member_posts',
									'joined'		=> 'import_joined_date',
									'ip_address'	=> 'ip_address',
								),
								'import_group'	=> array(
									'group_id'				=> 'import_group_id',
									'secondary_group_id'	=> 'import_secondary_group_id',
								),
								'import_passwords'	=> array(
									'password_plain'			=> 'import_password_plain',
									'password_blowfish_hash'	=> 'import_password_blowfish_hash',
								),
								'import_member_preferences'	=> array(
									'timezone'			=> 'timezone',
									'birthday'			=> 'import_birthday',
									'allow_admin_mails'	=> 'import_allow_admin_mails',
								),
								'import_member_other'	=> array(
									'last_visit'	=> 'import_last_visit_date',
									'last_post'		=> 'last_post',
								)
							);
							$languages = Lang::languages();
							foreach ( $languages as $lang )
							{
								$importOptions['import_group'][ 'group_name_' . $lang->id ] = count( $languages ) == 1 ? 'import_group_name' : Member::loggedIn()->language()->addToStack( 'import_group_name_lang', FALSE, array( 'sprintf' => $lang->_title ) );
								$importOptions['import_group'][ 'group_secondary_name_' . $lang->id ] = count( $languages ) == 1 ? 'import_secondary_group_name' : Member::loggedIn()->language()->addToStack( 'import_secondary_group_name_lang', FALSE, array( 'sprintf' => $lang->_title ) );
							}
							if ( Settings::i()->signatures_enabled )
							{
								$importOptions['import_member_preferences']['signature'] = 'signature';
							}
							if ( Settings::i()->reputation_enabled )
							{
								$importOptions['import_basic_data']['pp_reputation_points'] = 'import_member_reputation';
							}
							if ( Settings::i()->warn_on )
							{
								$importOptions['import_basic_data']['warn_level'] = 'import_member_warn_level';
							}
							
							if ( count( Theme::themes() ) > 1 )
							{
								$importOptions['import_member_preferences']['skin']		= 'import_theme_id';
								
								foreach ( $languages as $lang )
								{
									$importOptions['import_member_preferences'][ 'skin_name_' . $lang->id ] = count( $languages ) == 1 ? 'import_theme_name' : Member::loggedIn()->language()->addToStack( 'import_theme_name_lang', FALSE, array( 'sprintf' => $lang->_title ) );
								}
								
							}
							if ( count( Lang::languages() ) > 1 )
							{
								$importOptions['import_member_preferences']['language']		= 'import_language_id';
								$importOptions['import_member_preferences']['language_name']	= 'import_language_name';
							}
							foreach ( Field::fields( array(), Field::STAFF ) as $groupId => $fields )
							{
								foreach ( $fields as $fieldId => $field )
								{
									$importOptions['import_custom_fields'][ 'pfield_' . $fieldId ] = 'core_pfield_' . $fieldId;
									unset( Member::loggedIn()->language()->words[ 'core_pfield_' . $fieldId . '_desc' ] );
								}
							}
							return new Select( $key, $value, FALSE, array( 'options' => $importOptions, 'toggles' => array( 'member_id' => array( 'elImportMemberIdWarning' ) ) ) );
						}
					);
					
					/* Look at the first row in the .csv file and ask where to put each piece of data
						- if the first row is a header, guess from what it says what content it might
						contain (for example, if the header is "email" - that's obviously where the
						email addresses are */
					$headers = fgetcsv( $fh );
					fclose( $fh );
					$i = 0;
					foreach ( $headers as $i => $header )
					{
						if ( $data['header'] )
						{						
							$value = NULL;
							$parsedHeader = preg_replace( '/[-_]/', '', $header );
							switch ( mb_strtolower( $parsedHeader ) )
							{								
								case 'name':
								case 'username':
								case 'displayname':
									$value = 'name';
									break;
								
								case 'email':
								case 'emailaddress':
									$value = 'email';
									break;
									
								case 'memberposts':
								case 'posts':
									$value = 'member_posts';
									break;
									
								case 'joined':
								case 'joineddate':
								case 'joindate':
								case 'regdate':
									$value = 'joined';
									break;
									
								case 'ip':
								case 'ipaddress':
									$value = 'ip_address';
									break;
								
								case 'group':
								case 'primarygroup':
								case 'primarygroupid':
									$value = 'group_id';
									break;
								
								case 'groupname':
								case 'primarygroupname':
									$value = 'group_name_' . Lang::defaultLanguage();
									break;
								
								case 'secondarygroup':
								case 'secondarygroupids':
									$value = 'secondary_group_id';
									break;
								
								case 'secondarygroupname':
								case 'secondarygroupnames':
									$value = 'group_secondary_name_' . Lang::defaultLanguage();
									break;
									
								case 'pass':
								case 'password':
									$value = 'password_plain';
									break;
								
								case 'passhash':
								case 'passwordhash':
									$value = 'password_blowfish_hash';
									break;
																	
								case 'timezone':
									$value = 'timezone';
									break;
								
								case 'bday':
								case 'birthday':
								case 'birthdate':
									$value = 'birthday';
									break;
								
								case 'mailinglist':
								case 'allowadminmails':
								case 'newsletter':
								case 'sendnews':
									$value = 'allow_admin_mails';
									break;
									
								case 'lastvisit':
								case 'lastactivity':
									$value = 'last_visit';
									break;
								
								case 'lastpost':
									$value = 'last_post';
									break;
									
								case 'sig':
								case 'signature':
									$value = 'signature';
									break;
									
								case 'rep':
								case 'reputation':
								case 'ppreputationpoints':
									$value = 'pp_reputation_points';
									break;
									
								case 'warningpoints':
								case 'warnpoints':
								case 'warninglevel':
								case 'warnlevel':
									$value = 'warn_level';
									break;
																
								case 'skin':
									$value = 'skin';
									break;
									
								case 'skinname':
								case 'theme':
									$value = 'skin_name_' . Lang::defaultLanguage();
									break;
								
								case 'language':
									$value = 'language';
									break;
									
								case 'languagename':
								case 'lang':
									$value = 'language_name';
									break;
							}

							$matrix->rows[] = array( 'import_column' => htmlspecialchars( $header, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ), 'import_as' => $value );
						}
						else
						{
							$matrix->rows[] = array( 'import_column' => Member::loggedIn()->language()->addToStack( 'import_column_number', FALSE, array( 'sprintf' => array( ++$i ) ) ), 'import_as' => '' );
						}
					}
					
					/* Add the matrix */
					$form->addMatrix( 'columns', $matrix );
					$form->addMessage( Member::loggedIn()->language()->addToStack( 'import_member_id_warning', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatNumber( $maxMemberId ) ) ) ), 'ipsMessage ipsMessage--warning', FALSE, 'elImportMemberIdWarning' );
					
					/* Handle submissions */
					if ( $values = $form->values() )
					{					
						$data['import_members_fallback_group'] = $values['import_members_fallback_group'];
						$data['import_members_send_confirmation'] = $values['import_members_send_confirmation'];
						
						foreach ( $values['columns'] as $k => $vals )
						{
							if ( $vals['import_as'] )
							{
								$data['columns'][ $k ] = $vals['import_as'];
							}
						}
						
						if ( !in_array( 'name', $data['columns'] ) and !in_array( 'email', $data['columns'] ) )
						{
							$form->error = Member::loggedIn()->language()->addToStack('import_member_no_name_or_email');
						}
						else
						{
							return $data;
						}
					}
					
					/* Display */
					return (string) $form;
				},
				/* Step 3: Import */
				'import_do_import'	=> function( $wizardData ) use ( $maxMemberId )
				{
					return (string) new MultipleRedirect( Url::internal('app=core&module=members&controller=members&do=import'),	function( $mrData ) use ( $wizardData, $maxMemberId )
					{
						/* Get line from the file */
						$fh = fopen( $wizardData['file'], 'r' );
						if ( $mrData === 0 OR $mrData === NULL )
						{
							/* Ignore the header row */
							if ( $wizardData['header'] )
							{
								fgetcsv( $fh );
							}
							
							/* Set the MultipleRedirect data */
							$mrData = array( 'currentPosition' => 0, 'errors' => array() );
						}
						else
						{
							fseek( $fh, $mrData['currentPosition'] );
						}

						$count = 0;
						begin:
						$line = fgetcsv( $fh );
						$count++;
						
						/* Are we done/ */
						if ( !$line )
						{
							fclose( $fh );
							
							Widget::deleteCaches( 'stats', 'core' );
							
							if ( isset( $mrData['errors'] ) AND count( $mrData['errors'] ) )
							{
								return array( Theme::i()->getTemplate( 'members' )->importMemberErrors( $mrData['errors'] ) );
							}
							else
							{
								return NULL;
							}
						}

						/* Create the member */
						try
						{
							$member = new Member;
							$member->member_group_id = $wizardData['import_members_fallback_group'];
							$member->members_bitoptions['password_reset_forced'] = TRUE;
							$profileFields = array();
							foreach ( $line as $k => $v )
							{
								if ( isset( $wizardData['columns'][ $k ] ) )
								{
									if ( mb_substr( $wizardData['columns'][ $k ], 0, 11 ) == 'group_name_' )
									{
										try
										{
											$member->member_group_id = mb_substr( Db::i()->select( 'word_key', 'core_sys_lang_words', array( 'lang_id=? AND word_key LIKE ? AND word_custom=?', mb_substr( $wizardData['columns'][ $k ], 11 ), '%core_group_%', $v ) )->first(), 11 );
										}
										catch ( UnderflowException $e ) { }
									}
									elseif ( mb_substr( $wizardData['columns'][ $k ], 0, 21 ) == 'group_secondary_name_' )
									{
										$secondaryGroupIds = array();
										foreach ( array_filter( explode( ',', $wizardData['columns'][ $k ] ) ) as $secondaryGroupName )
										{
											try
											{
												$secondaryGroupIds[] = mb_substr( Db::i()->select( 'word_key', 'core_sys_lang_words', array( 'lang_id=? AND word_key LIKE ? AND word_custom=?', mb_substr( $wizardData['columns'][ $k ], 11 ), '%core_group_%', $v ) )->first(), 11 );
											}
											catch ( UnderflowException $e ) { }
										}
										$member->mgroup_others = implode( ',', $secondaryGroupIds );
									}
									elseif ( mb_substr( $wizardData['columns'][ $k ], 0, 10 ) == 'skin_name_' )
									{
										try
										{
											$member->skin = mb_substr( Db::i()->select( 'word_key', 'core_sys_lang_words', array( 'lang_id=? AND word_key LIKE ? AND word_custom=?', mb_substr( $wizardData['columns'][ $k ], 10 ), '%core_theme_set_title_%', $v ) )->first(), 21 );
										}
										catch ( UnderflowException $e ) { }
									}
									elseif ( mb_substr( $wizardData['columns'][ $k ], 0, 7 ) == 'pfield_' )
									{
										try
										{
											$field = Field::load( str_replace( 'pfield_', '', $wizardData['columns'][ $k ] ) );
											/* Do some additional formatting for specific field types */
											switch( $field->type )
											{
												case 'Date':
													if ( !is_numeric( $v ) )
													{
														$v = strtotime( $v );
													}
													break;
											}
											
											$profileFields[ mb_substr( $wizardData['columns'][ $k ], 1 ) ] = $v;
										}
										catch( OutOfRangeException $e ) { }
									}
									else
									{
										switch ( $wizardData['columns'][ $k ] )
										{
											case 'member_id':
												if ( !is_numeric( $v ) or $v < 0 or $v > $maxMemberId )
												{
													throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'import_member_id_invalid' ), $v, Member::loggedIn()->language()->formatNumber( $maxMemberId ) ) );
												}
												$v = intval( $v );
																								
												$existingMember = Member::load( $v );
												if ( $existingMember->member_id )
												{
													throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'import_member_id_exists' ), $v ) );
												}
												$member->member_id = $v;
												break;
											
											case 'name':
												if ( !$v )
												{
													throw new DomainException( Member::loggedIn()->language()->get( 'import_no_name' ) );
												}
												if ( strlen( $v ) > 255 )
												{
													throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'import_name_too_long' ), $v ) );
												}
												if ( Login::usernameIsInUse( $v, NULL, TRUE ) )
												{
													throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'import_name_exists' ), $v ) );
												}
												$member->name = $v;
												break;
												
											case 'email':
												/* There may be an erroneous space in the column */
												$v	= trim( $v );

												if ( !$v )
												{
													throw new DomainException( Member::loggedIn()->language()->get( 'import_no_email' ) );
												}
												if ( strlen( $v ) > 255 )
												{
													throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'import_email_too_long' ), $v ) );
												}
												if ( filter_var( $v, FILTER_VALIDATE_EMAIL ) === FALSE )
												{
													throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'import_email_invalid' ), $v ) );
												}
												if ( Login::emailIsInUse( $v, NULL, TRUE ) )
												{
													throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'import_email_exists' ), $v ) );
												}
												$member->email = $v;
												break;
												
											case 'group_id':
												try
												{
													$member->member_group_id = Group::load( $v )->g_id;
												}
												catch ( OutOfRangeException $e ) { }
												break;
												
											case 'secondary_group_id':
												$secondaryGroupIds = array();
												foreach ( array_filter( explode( ',', $v ) ) as $secondaryGroupId )
												{
													try
													{
														$secondaryGroupIds[] = Group::load( $secondaryGroupId )->g_id;
													}
													catch ( OutOfRangeException $e ) { }
												}
												$member->mgroup_others = implode( ',', $secondaryGroupIds );
												break;
												
											case 'password_plain':
												$member->setLocalPassword( $v );
												$member->members_bitoptions['password_reset_forced'] = FALSE;
												break;
												
											case 'password_blowfish_hash':
												$member->members_pass_hash = $v;
												$member->members_pass_salt = NULL;
												$member->members_bitoptions['password_reset_forced'] = FALSE;
												break;
																								
											case 'birthday':
												$exploded = explode( '-', $v );

												if ( count( $exploded ) == 2 OR count( $exploded ) == 3 )
												{
													if ( intval( $exploded[0] ) <= 31 and intval( $exploded[1] ) <= 12 )
													{
														$member->bday_day = intval( $exploded[0] );
														$member->bday_month = intval( $exploded[1] );
														if ( isset( $exploded[2] ) AND is_numeric( $exploded[2] ) )
														{
															$member->bday_year = intval( $exploded[2] );
														}
													}
												}
												break;
												
											case 'language_name':
												try
												{
													$member->language = Db::i()->select( 'lang_id', 'core_sys_lang', array( 'lang_title=?', $v ) )->first();
												}
												catch ( UnderflowException $e ) { }
												break;
												
											case 'last_post':
												if( $v AND strtotime( $v ) )
												{
													$member->member_last_post = strtotime( $v );
												}
												break;

											case 'joined':
											case 'last_visit':
												if( $v AND strtotime( $v ) )
												{
													$key = $wizardData['columns'][ $k ];
													$member->$key = strtotime( $v );
												}
												break;
												
											default:
												$key = $wizardData['columns'][ $k ];
												$member->$key = $v;
												break;
										}
									}
								}
							}
							if ( !$member->name and !$member->email )
							{
								throw new DomainException( Member::loggedIn()->language()->get( 'import_no_name' ) );
							}
							if( !$member->joined )
							{
								$member->joined = time();
							}
							
							$member->members_bitoptions['created_externally'] = TRUE;

							$member->completed = TRUE;

							$member->save();
							if ( count( $profileFields ) )
							{
								Db::i()->replace( 'core_pfields_content', array_merge( array( 'member_id' => $member->member_id ), $profileFields ) );
							}
							Session::i()->log( 'acplog__members_created', array( $member->name => FALSE ) );
						}
						catch ( DomainException $e )
						{
							$mrData['errors'][] = $e->getMessage();
						}
						
						/* Send email */
						if ( $wizardData['import_members_send_confirmation'] )
						{
							Email::buildFromTemplate( 'core', 'admin_reg', array( $member, $member->members_bitoptions['password_reset_forced'], md5( SUITE_UNIQUE_KEY . $member->email . $member->real_name ) ), Email::TYPE_TRANSACTIONAL )->send( $member );
						}

						/* If we haven't hit our limit, go again. */
						if ( $count <= 100 )
						{
							goto begin;
						}
						
						/* Continue */
						$mrData['currentPosition'] = ftell( $fh );
						fclose( $fh );
						return array( $mrData, Member::loggedIn()->language()->addToStack('import_members_processing'), 100 / filesize( $wizardData['file'] ) * $mrData['currentPosition'] );
					},
					function() use ( $wizardData )
					{
						@unlink( $wizardData['file'] );
						Output::i()->redirect( Url::internal('app=core&module=members&controller=members') );
					} );					
				}
			),
			Url::internal('app=core&module=members&controller=members&do=import')
		);
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('members_import');
		Output::i()->output = (string) $wizard;
	}
	
	/**
	 * Export
	 *
	 * @return	void
	 */
	public function export() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_export' );

		$initialData = NULL;
		if ( isset( Request::i()->group ) )
		{
			$initialData['filters']['core_Group']['groups'] = Request::i()->group;
		}
		
		/* Wizard */
		$wizard = new Wizard(
			array(
				/* Step 1: Choose data */
				'export_choose_data'	=> array( $this, '_exportChooseData' ),
				/* Step 2: Build List */
				'export_build_list'		=> array( $this, '_exportBuildList' ),
				/* Step 3: Show the download link */
				'export_download_file'	=> function( $wizardData )
				{					
					if ( isset( Request::i()->download ) )
					{
						$csv = file_get_contents( $wizardData['file'] );

						/* Clean up before encoding */
						Output::i()->parseFileObjectUrls( $csv );

						/* Excel requires a BOM this for non-ASCII characters to show properly */
						$csv = chr(0xEF) . chr(0xBB) . chr(0xBF) . $csv;

						Session::i()->log( 'acplog__exported_member_list' );
						Output::i()->sendOutput( $csv, 200, 'text/csv', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', Member::loggedIn()->language()->get('member_pl') . '.csv' ) ), FALSE, FALSE, FALSE, FALSE );
					}
					
					return Theme::i()->getTemplate( 'members' )->downloadMemberList( $wizardData['removedData'] ?? array(), $wizardData['includeInsecure'] ?? FALSE );
				}
			),
			Url::internal('app=core&module=members&controller=members&do=export'),
			TRUE,
			$initialData
		);
		
		/* Output */
		Output::i()->title = Member::loggedIn()->language()->addToStack('members_export');
		Output::i()->output = (string) $wizard;
	}
	
	/**
	 * Get columns for member export
	 *
	 * @return array
	 */
	protected function _getExportColumns() : array
	{
		/* Define what columns are available */
		$columns = array(
			'member_id'				=> 'member_id',
			'name'					=> 'username',
			'email'					=> 'email',
			'member_group_id'		=> 'import_group_id',
			'primary_group_name'	=> 'import_group_name',
			'mgroup_others'			=> 'import_secondary_group_id',
			'secondary_group_names'	=> 'import_secondary_group_name',
			'member_posts'			=> 'members_member_posts',
			'pp_reputation_points'	=> 'member_reputation',
			'joined'				=> 'import_joined_date',
			'ip_address'			=> 'members_ip_address',
			'timezone'				=> 'timezone',
			'last_visit'			=> 'import_last_visit_date',
			'last_post'				=> 'last_post',
			'birthday'				=> 'import_birthday',
			'allow_admin_mails'		=> 'import_allow_admin_mails',
			'achievements_points'	=> 'achievement_points',
			'member_rank'			=> "members_member_rank_id",
		);
		if ( Settings::i()->signatures_enabled )
		{
			$columns['signature'] = 'signature';
		}
		if ( Settings::i()->reputation_enabled )
		{
			$columns['pp_reputation_points'] = 'import_member_reputation';
		}
		if ( Settings::i()->warn_on )
		{
			$columns['warn_level'] = 'import_member_warn_level';
		}
		
		if ( count( Theme::themes() ) > 1 )
		{
			$columns['skin']		= 'import_theme_id';
			$columns['skin_name']	= 'import_theme_name';
		}
		if ( count( Lang::languages() ) > 1 )
		{
			$columns['language']		= 'import_language_id';
			$columns['language_name']	= 'import_language_name';
		}

		foreach ( Field::fieldData() as $groupId => $fields )
		{
			foreach ( $fields as $fieldId => $fieldData )
			{
				$columns[ 'pfield_' . $fieldId ] = 'core_pfield_' . $fieldId;
				unset( Member::loggedIn()->language()->words[ 'core_pfield_' . $fieldId . '_desc' ] );
			}
		}

		return $columns;
	}

	/**
	 * Get headers for member export
	 *
	 * @param	array	$wizardData	Wizard data
	 * @return	array
	 */
	protected function _getExportCsvHeaders( array $wizardData ) : array
	{
		$headers = array();
		foreach ( $wizardData['columns'] as $column )
		{
			$headers[] = $column;
		}

		return $headers;
	}

	/**
	 * Member export: build list
	 *
	 * @param	array	$wizardData	Wizard data
	 * @return    string|array
	 */
	public function _exportBuildList( array $wizardData ) : string|array
	{
		$baseUrl = Url::internal('app=core&module=members&controller=members&do=export');
		
		if ( isset( Request::i()->buildDone ) )
		{
			if ( isset( $_SESSION['removedData'] ) AND $_SESSION['removedData'] AND ( !isset( $wizardData['includeInsecure'] ) OR !$wizardData['includeInsecure'] ) )
			{							
				$wizardData['removedData'] = $_SESSION['removedData'];
				unset( $_SESSION['removedData'] );
			}
			return $wizardData;
		}
							
		return (string) new MultipleRedirect(
			$baseUrl,
			function( $mrData ) use ( $wizardData, $baseUrl )
			{
				$doPerLoop = 2500;
				if ( !is_array( $mrData ) )
				{
					$mrData = array( 'offset' => 0, 'removedData' => array(), 'total' => 0 );
				}
				
				/* Compile where */
				$where = array();
				foreach ( Application::allExtensions( 'core', 'MemberFilter', FALSE, 'core' ) as $key => $extension )
				{
					/* Grab our fields and add to the form */
					if( isset( $wizardData[ 'filters' ][ $key ] ) )
					{
						if( $_where = $extension->getQueryWhereClause( $wizardData[ 'filters' ][ $key ] ) )
						{
							if( is_string( $_where ) )
							{
								$_where = [$_where];
							}

							$where[] = $_where;
						}
					}
				}
				
				/* Do we need to join profile field data? */
				$select = array( 'core_members.*' );
				$customFields = array();
				foreach ( $wizardData['columns'] as $column )
				{
					if ( mb_substr( $column, 0, 7 ) == 'pfield_' )
					{
						$customFields[] = 'core_pfields_content.field_' . mb_substr( $column, 7 );
					}
				}
				if ( count( $customFields ) )
				{
					$select[] = implode( ',', $customFields );
				}

				/* If we don't have our total count, get it */
				if( !isset( $mrData['count'] ) )
				{
					/* Compile query */
					$query = Db::i()->select( 'COUNT(*)', 'core_members', $where );
					
					/* Run callbacks */
					foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
					{
						if( method_exists( $extension, 'queryCallback' ) )
						{
							/* Grab our fields and add to the form */
							if( !empty( $wizardData['filters'][ $key ] ) )
							{
								$data = $wizardData['filters'][ $key ];
								$extension->queryCallback( $data, $query );
							}
						}
					}

					$mrData['count'] = $query->first();
				}

				/* Compile query */
				$query = Db::i()->select( implode( ',', $select ), 'core_members', $where, 'core_members.member_id', array( $mrData['offset'], $doPerLoop ) );
				
				/* Run callbacks */
				foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
				{
					/* Grab our fields and add to the form */
					if( !empty( $wizardData['filters'][ $key ] ) )
					{
						$data = $wizardData['filters'][ $key ];
						$extension->queryCallback( $data, $query );
					}
				}

				/* Finished? */
				if ( !$query->count() )
				{
					if( count( $mrData['removedData'] ) AND ( !isset( $wizardData['includeInsecure'] ) OR !$wizardData['includeInsecure'] ) )
					{
						$_SESSION['removedData'] = $mrData['removedData'];
					}

					Output::i()->redirect( $baseUrl->setQueryString( array( 'buildDone' => 1 ) ) );
				}
				
				/* Open file */
				$fh = fopen( $wizardData['file'], 'a' );
				
				/* Run */
				foreach ( $query as $member )
				{
					$dataToWrite = $this->_getDataToWriteCsv( $member, $wizardData, $mrData );

					if( $dataToWrite === FALSE )
					{
						continue;
					}
					
					/* Write */
					fputcsv( $fh, $dataToWrite );
				}
				
				/* Close and loop */
				fclose( $fh );
				$mrData['offset'] += $doPerLoop;
				return array( $mrData, Member::loggedIn()->language()->addToStack('export_members_processing'), ( $mrData['offset'] > $mrData['count'] ) ? 100 : ( 100 / $mrData['count'] ) * $mrData['offset'] );
			},
			function() use ( $baseUrl )
			{
				Output::i()->redirect( $baseUrl->setQueryString( array( 'buildDone' => 1 ) ) );
			}
		);
	}

	/**
	 * Member export: get data to write to CSV
	 *
	 * @param	array	$member		Member record
	 * @param	array	$wizardData	Wizard data
	 * @param	array	$mrData		Multiredirect data (passed by reference so it can be modified if row is skipped)
	 * @return    array|bool
	 */
	protected function _getDataToWriteCsv( array $member, array $wizardData, array &$mrData ) : array|bool
	{
		$dataToWrite = array();
		foreach ( $wizardData['columns'] as $column )
		{
			$valueToWrite = '';
			
			switch ( $column )
			{
				case 'primary_group_name':
					try
					{
						$valueToWrite = Member::loggedIn()->language()->get( 'core_group_' . $member['member_group_id'] );
					}
					catch ( UnderflowException $e ){}
					break;
				
				case 'secondary_group_names':
					$secondaryGroupNames = array();
					foreach ( array_filter( explode( ',', $member['mgroup_others'] ) ) as $secondaryGroupId )
					{
						try
						{
							$secondaryGroupNames[] = Member::loggedIn()->language()->get( 'core_group_' . $secondaryGroupId );
						}
						catch ( UnderflowException $e ) { }
					}
					$valueToWrite = implode( ',', $secondaryGroupNames );
					break;

				case 'last_visit':
					$column = $member[ 'last_visit' ] > $member['last_activity'] ? 'last_visit' : 'last_activity';
					$valueToWrite = $member[ $column ] ? date( 'Y-m-d H:i', $member[ $column ] ) : '';
					break;
				case 'joined':
				case 'last_post':
					if ( $column === 'last_post' )
					{
						$column = 'member_last_post';
					}
					
					$valueToWrite = $member[ $column ] ? date( 'Y-m-d H:i', $member[ $column ] ) : '';
					break;
					
				case 'birthday':
					if ( $member['bday_day'] and $member['bday_month'] )
					{
						$valueToWrite = ( $member['bday_year'] ?: '????' ) . '-' . str_pad( $member['bday_month'], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $member['bday_day'], 2, '0', STR_PAD_LEFT );
					}
					break;
					
				case 'skin_name':
					$themeId = $member['skin'] ?: Theme::defaultTheme();
					try
					{
						$valueToWrite = Member::loggedIn()->language()->get( 'core_theme_set_title_' . $themeId );
					}
					catch ( UnderflowException $e ){}
					break;
					
				case 'language_name':
					$langId = $member['language'] ?: Lang::defaultLanguage();
					try
					{
						$valueToWrite = Lang::load( $langId )->_title;
					}
					catch ( OutOfRangeException $e ){}
					break;
				case 'member_rank':
					if( $rank = Rank::fromPoints( $member['achievements_points'] ) )
					{
						$valueToWrite = Member::loggedIn()->language()->get( 'core_member_rank_' . $rank->_id );
					}
					break;
				
				default:
					if ( mb_substr( $column, 0, 7 ) == 'pfield_' )
					{
						$fieldId = mb_substr( $column, 7 );
						/* If this is a Checkbox or YesNo field, then we can't use the displayValue() as it will just come out as a hashed string, and the CSV file can be large, so just replace these at generation time rather than parsing the entire file. */
						$field = Field::load( $fieldId );

						$valueToWrite = match( $field->type ) {
							'YesNo', 'Checkbox' => $member[ 'field_' . $fieldId ] ? Member::loggedIn()->language()->get('yes') : Member::loggedIn()->language()->get('no'),
							default				=> $member[ 'field_' . $fieldId ] ? Field::load( $fieldId )->displayValue( $member[ 'field_' . $fieldId ], FALSE, NULL, 0, NULL, TRUE, ',' ) : ''
						};
					}
					else
					{
						$valueToWrite = $member[ $column ];
					}
					break;
			}
			
			/* Cells starting with =, + or - can be a security risk. */
			if ( !isset( $wizardData['includeInsecure'] ) and !in_array( $column, array( 'primary_group_name', 'secondary_group_names', 'skin_name', 'language_name', 'members_pass_hash', 'members_pass_salt' ) ) and in_array( mb_substr( $valueToWrite, 0, 1 ), array( '=', '+', '-', '@' ) ) and ! is_numeric( str_replace( ' ', '', $valueToWrite ) ) )
			{
				$mrData['removedData'][ $member['member_id'] ] = array( $column, base64_encode( $valueToWrite ) );
				return FALSE;
			}
			
			/* Add it */
			$dataToWrite[] = $valueToWrite;
		}

		return $dataToWrite;
	}

	/**
	 * Member export: choose data
	 *
	 * @param	array	$wizardData	Wizard data
	 * @return    string|array
	 */
	public function _exportChooseData( array $wizardData ) : string|array
	{
		if ( isset( Request::i()->includeInsecure ) )
		{
			$wizardData['includeInsecure'] = TRUE;
		}

		$columns = $this->_getExportColumns();
		
		$form = new Form( 'choose_data', 'continue' );
		
		$form->addHeader( 'export_columns_to_include' );
		$form->add( new CheckboxSet( 'export_columns_to_include', $wizardData['columns'] ?? array( 'member_id', 'name', 'email', 'primary_group_name', 'secondary_group_names', 'member_posts', 'joined', 'skin_name', 'language_name' ), TRUE, array( 'options' => $columns ) ) );
		
		$form->addHeader( 'generic_bm_filters' );
		$lastApp = 'core';
		foreach ( Application::allExtensions( 'core', 'MemberFilter', FALSE, 'core' ) as $key => $extension )
		{
			if( method_exists( $extension, 'getSettingField' ) )
			{
				$_key		= explode( '_', $key );
				if( $_key[0] != $lastApp )
				{
					$lastApp	= $_key[0];
					$form->addHeader( $lastApp . '_bm_filters' );
				}
				
				foreach ( $extension->getSettingField( $wizardData['filters'][$key] ?? array() ) as $field )
				{
					$form->add( $field );
				}
			}
		}
		
		if ( $values = $form->values() )
		{
			$wizardData['columns'] = $values['export_columns_to_include'];
			
			foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
			{
				if( method_exists( $extension, 'save' ) )
				{
					$_value = $extension->save( $values );
					if( $_value )
					{
						$wizardData['filters'][ $key ] = $_value;
					}
				}
			}
			
			$wizardData['file'] = tempnam( TEMP_DIRECTORY, 'IPS' );
			$fh = fopen( $wizardData['file'], 'w' );

			fputcsv( $fh, $this->_getExportCsvHeaders( $wizardData ) );
			fclose( $fh );
			
			return $wizardData;
		}
		
		return (string) $form;
	}

	/**
	 * Export Personal Information
	 *
	 * @return void
	 */
	public function exportPersonalInfo() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_export_pi' );

		/* Member */
		$member = Member::load( Request::i()->id );

		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C114/V', 404, '' );
		}
		
		if ( ! isset( Request::i()->process ) )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('member_export_pi_title');
			Output::i()->output = Theme::i()->getTemplate('memberprofile')->downloadPersonalInfo( $member );
		}
		else
		{
			$xml = $member->getPiiData();
			
			Session::i()->log( 'acplog__member_pii_exported', array( $member->name => FALSE ) );
	
			/* Build */
			Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( "Content-Disposition" => Output::getContentDisposition( 'attachment', $member->name . '_personal_information.xml' ) ), FALSE, FALSE, FALSE );
		}
	}

	/**
	 * Member History
	 *
	 * @return	void
	 */
	public function history() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_history' );

		/* Member */
		$member = Member::load( Request::i()->id );

		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C114/N', 404, '' );
		}

		$title = 'member_account';
		$link = $member->acpUrl();

		/* Viewer came from commerce customer page */
		if( Application::appIsEnabled( 'nexus' ) AND Request::i()->nexus_return )
		{
			$title = 'view_account';
			$link = Customer::load( $member->member_id )->acpUrl();
		}

		/* History */
		$history = new History( $member->acpUrl()->setQueryString( 'do', 'history' ), array( array( 'log_member=?', $member->member_id ) ) );
		
		if ( Request::i()->isAjax() and isset( Request::i()->_fromFilter ) )
		{
			$history->tableTemplate = array( Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyTable' );
			$history->rowsTemplate = array( Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyRows' );
			$history->limit = 20;
		}

		/* Output */
		Output::i()->title = Member::loggedIn()->language()->addToStack('member_history_member', FALSE, array( 'sprintf' => array( $member->name ) ) );
		Output::i()->output = $history;

		Output::i()->sidebar['actions'][] = array(
			'icon'	=> 'arrow-left',
			'title'	=> $title,
			'link'	=> $link
		);
	}
		
	/**
	 * View Warning
	 *
	 * @return	void
	 */
	protected function viewWarning() : void
	{
		/* Load it */
		try
		{
			$warning = Warning::loadAndCheckPerms( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/P', 404, '' );
		}
		
		/* Show it */
		Output::i()->output = Theme::i()->getTemplate('memberprofile')->warningView( $warning );
	}
	
	/**
	 * Revoke Warning
	 *
	 * @return	void
	 */
	protected function warningRevoke() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$item	= Warning::loadAndCheckPerms( Request::i()->id );
			$member	= Member::load( $item->member );
			
			if ( $item->canDelete() )
			{
				if ( Request::i()->prompt )
				{
					$item->undo();
				}
				$item->delete();
				Output::i()->redirect( $member->acpUrl(), 'warn_revoked' );
			}
			else
			{
				Output::i()->error( 'generic_error', '2C114/R', 403, '' );
			}
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/Q', 404, '' );
		}
	}
	
	/**
	 * Change Points
	 *
	 * @return	void
	 */
	protected function points() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();
		
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/W', 404, '' );
		}
		
		/* Build form */
		$form = new Form( 'points_form', 'save' );
		$form->add( new Number( 'member_achievements_points', $member->achievements_points, TRUE ) );
		Member::loggedIn()->language()->words['member_achievements_points'] = sprintf( Member::loggedIn()->language()->get('member_achievements_points_o'), $member->name );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if ( $values['member_achievements_points'] != $member->achievements_points )
			{
				$points = $values['member_achievements_points'] - $member->achievements_points;
				$member->logHistory( 'core', 'points', array('by' => 'manual', 'old' => $member->achievements_points, 'new' => $values['member_achievements_points'] ) );
				$member->awardPoints( $points, 0, [], ['subject'] );
				
				Session::i()->log( 'acplog__members_edited_points', array( $member->name => FALSE ) );
			}
			
			/* OK */
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( true );
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Points&id={$member->member_id}" ), 'saved' );
			}
		}
		
		/* Display */
		Output::i()->output = $form;
	}

	/**
	 * Badges management
	 *
	 * @return	string
	 */
	public function badges(): string
	{
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/W', 404, '' );
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		{
			Output::i()->sidebar['actions']['addbadge'] = array(
				'primary' => true,
				'icon' => 'plus',
				'link' => $member->acpUrl()->setQueryString('do', 'addBadge')->csrf(),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acp_profile_badge_add' ) ),
				'title' => 'acp_profile_badge_add',
			);
		}

		$table = new TableDb( 'core_member_badges', $member->acpUrl()->setQueryString( array( 'do' => 'badges' ) ) );
		$table->where = [ [ 'core_member_badges.member=' . $member->member_id ] ];

		/* Filters */
		$table->filters = [
			'acp_manage_points_manual' => 'rule=0',
			'acp_manage_points_rule' => 'rule>0',
		];

		$table->joins[] = [
			'select'	=> 'core_badges.id as badge_id, image, badge_use_image',
			'from'		=> 'core_badges',
			'where'		=> 'core_badges.id=core_member_badges.badge',
			'type'		=> 'INNER'
		];

		$table->joins[] = [
			'select'	=> 'core_achievements_log.action, core_achievements_log.identifier',
			'from'		=> 'core_achievements_log',
			'where'		=> 'core_achievements_log.id=core_member_badges.action_log',
			'type'		=> 'LEFT'
		];

		$table->include = [ 'badge', 'action_log', 'datetime' ];
		$table->sortBy = $table->sortBy ?: 'datetime';
		$table->langPrefix = 'acp_badges_log_table_';
		$table->parsers = [
			'badge' => function( $val, $row )
			{
				/* Wade likes simple table field names like 'id', I like unique ones like 'log_id' for this reason :D */
				$badge = Badge::constructFromData( array_merge( $row, [ 'id' => $row['badge_id'] ] ) );
				return $badge->html('ipsDimension i-margin-end_icon i-basis_40') . ' ' . $badge->_title;
			},
			'datetime' => function( $val ) {
				return DateTime::ts( $val );
			},
			'action_log' => function( $val, $row )
			{
				if ( ! $row['action'] )
				{
					return Member::loggedIn()->language()->addToStack( 'acp_badge_unknown' );
				}

				$exploded = explode( '_', $row['action'] );

				if ( ! empty( $row['recognize'] ) )
				{
					try
					{
						$recognize = Recognize::load( $row['recognize'] );
						return Member::loggedIn()->language()->addToStack( 'acp_badge_from_recognize', FALSE, [ 'sprintf' => [ $recognize->content()->url(), $recognize->content()->indefiniteArticle() ] ] );
					}
					catch( Exception $e )
					{
						return Member::loggedIn()->language()->addToStack( 'acp_badge_manual' );
					}
				}
				else if ( isset( $exploded[1] ) )
				{
					$extension = Application::load( $exploded[0] )->extensions( 'core', 'AchievementAction' )[$exploded[1]];
					return $extension->logRow( $row['identifier'], explode( ',', $row['actor'] ) );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack( 'acp_badge_manual' );
				}
			}
		];

		$table->rowButtons = function( $row ) use( $member )
		{
			return [ 'delete' => [
				'icon'  => 'times-circle',
				'title' => 'delete',
				'link'  => $row['rule'] ? NULL : $member->acpUrl()->setQueryString( ['do' => 'deleteBadge', 'id' => $row['badge_id'], 'member_id' => $member->member_id ] ),
				'class' => $row['rule'] ? 'ipsControlStrip_disabled' : '',
				'data'  => $row['rule'] ? [] : [ 'delete' => '' ],
				'tooltip' => Member::loggedIn()->language()->addToStack( $row['rule'] ? 'acp_badge_cannot_delete' : 'delete' )
			] ];
		};

		Output::i()->title = Member::loggedIn()->language()->addToStack('acp_profile_badges_manage_title');
		return Output::i()->output = Theme::i()->getTemplate( 'members' )->badgesLog( $table, $member );
	}

	/**
	 * Manually add a bage
	 *
	 * @return	void
	 */
	protected function addBadge() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'member_edit' );
		Session::i()->csrfCheck();

		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/X', 404, '' );
		}

		/* Build form */
		$form = new Form( 'badge_form', 'save' );
		$form->add( new Node( 'acp_manual_badge', NULL, TRUE, [
			'class' => '\IPS\core\Achievements\Badge',
			'permissionCheck' => function( $node )
			{
				return $node->manually_awarded;
			},
			'disabledIds' => iterator_to_array( Db::i()->select( 'badge', 'core_member_badges', [ '`member`=?', $member->member_id ] ) ),
			'url'  => $member->acpUrl()->setQueryString('do', 'addBadge')
		] ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$member->awardBadge( $values['acp_manual_badge'], 0, 0, ['subject'] );
			$member->logHistory( 'core', 'badges', [ 'action' => 'manual', 'id' => $values['acp_manual_badge']->_id ] );

			/* OK */
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( true );
			}
			else
			{
				Output::i()->redirect( $member->acpUrl()->setQueryString('do', 'badges'), 'saved' );
			}
		}

		/* Display */
		Output::i()->output = $form;
	}

	/**
	 * Delete a manually awarded badge
	 *
	 * @return void
	 */
	protected function deleteBadge() : void
	{
		/* Load Member */
		try
		{
			$member = Member::load( Request::i()->member_id );

			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C114/Y', 404, '' );
		}

		/* Load Badge */
		try
		{
			$badge = Db::i()->select( '*', 'core_member_badges', [ 'rule=0 and member=? and badge=?', $member->member_id, Request::i()->id ] )->first();

			/* Looks good, now remove it */
			Db::i()->delete( 'core_member_badges', [ '`id`=?', $badge['id'] ] );
			$member->logHistory( 'core', 'badges', [ 'action' => 'delete', 'id' => $badge['badge'] ] );

			Output::i()->redirect( $member->acpUrl()->setQueryString('do', 'badges'), 'deleted' );
		}
		catch( Exception $e )
		{
			Output::i()->error( 'node_error', '2C114/Z', 404, '' );
		}
	}

	/**
	 * Request that a member's email is unblocked (via ajax)
	 *
	 * @return  void
	 */
	public function requestEmailUnblock(): void
	{
		Bridge::i()->bounceMemberViewRequestEmailUnblock();
	}

}