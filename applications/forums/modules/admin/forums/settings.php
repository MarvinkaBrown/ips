<?php
/**
 * @brief		Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		20 Jan 2014
 */

namespace IPS\forums\modules\admin\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\forums\Topic;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings as SettingsClass;
use IPS\Theme;
use LengthException;
use function count;
use function defined;
use function in_array;
use function IPS\Cicloud\getForumArchiveForm;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * settings
 */
class settings extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Dispatcher::i()->checkAcpPermission( 'settings_access' );
		
		$tabs = array();
		if ( Member::loggedIn()->hasAcpRestriction( 'forums', 'forums', 'forum_settings' ) )
		{
			$tabs['settings'] = 'forum_settings';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'forums', 'forums', 'archive_manage' ) )
		{
			$tabs['archiving'] = 'archiving';
		}
		
		$activeTab = Request::i()->tab ?: 'settings';
		$methodFunction = 'manage' . IPS::mb_ucfirst( $activeTab );
		$activeTabContents = $this->$methodFunction();
		
		if( Request::i()->isAjax() and !isset( Request::i()->ajaxValidate ) )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{		
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__forums_forums_settings');
			Output::i()->output 	= Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $activeTabContents, Url::internal( "app=forums&module=forums&controller=settings" ) );
		}
	}
	
	/**
	 * Archive settings
	 *
	 * @return	string
	 */
	protected function manageArchiving(): string
	{
		Dispatcher::i()->checkAcpPermission( 'archive_manage' );

		if ( Application::appIsEnabled('cloud') )
		{
			return getForumArchiveForm();
		}

		return $this->manageArchivingForm();
	}
	
	/**
	 * Archive settings
	 *
	 * @return	string
	 */
	protected function manageArchivingForm(): string
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_settings.js', 'forums', 'admin' ) );
		
		/* Init */
		$maxTopics = Db::i()->select( 'COUNT(*)', 'forums_topics' )->first();
		$existingRules = iterator_to_array( Db::i()->select( '*', 'forums_archive_rules' ) );
		$existingCount = SettingsClass::i()->archive_on ? Db::i()->select( 'COUNT(*)', 'forums_topics', array_merge( array( array( 'topic_archive_status!=?', Topic::ARCHIVE_EXCLUDE ) ), Application::load('forums')->archiveWhere( $existingRules ) ) )->first() : 0;
				
		/* Work out existing values */
		$existingValues = array();
		foreach ( $existingRules as $rule )
		{
			switch ( $rule['archive_field'] )
			{
				case 'lastpost':
					if ( $rule['archive_skip'] )
					{
						$existingValues['archive_not_last_post'] = array( $rule['archive_value'], $rule['archive_text'], $rule['archive_unit'], FALSE );
					}
					else
					{
						$existingValues['archive_last_post'] = array( $rule['archive_value'], $rule['archive_text'], $rule['archive_unit'], FALSE );
					}
					break;
				
				case 'forum':
					if ( $rule['archive_value'] == '+' )
					{
						$existingValues['archive_topic_forums'] = explode( ',', $rule['archive_text'] );
					}
					else
					{
						$existingValues['archive_topic_not_forums'] = explode( ',', $rule['archive_text'] );
					}
					break;
					
				case 'pinned':
				case 'featured':
				case 'state':
				case 'approved':
				case 'poll':
					$existingValues[ "archive_topic_{$rule['archive_field']}" ] = array( $rule['archive_value'] );
					break;
					
				case 'post':
				case 'view':
				case 'rating':
					if ( $rule['archive_skip'] )
					{
						$existingValues[ "archive_not_topic_{$rule['archive_field']}" ] = array( $rule['archive_value'], $rule['archive_text'], FALSE );
					}
					else
					{
						$existingValues[ "archive_topic_{$rule['archive_field']}" ] = array( $rule['archive_value'], $rule['archive_text'], FALSE );
					}
					break;
				
				case 'member':
					$members = array();
					foreach( explode( ',', $rule['archive_text'] ) AS $v )
					{
						$members[] = Member::load( $v );
					}
					if ( $rule['archive_value'] == '+' )
					{
						$existingValues['archive_topic_starter'] = $members;
					}
					else
					{
						$existingValues['archive_topic_starter_not'] = $members;
					}
					break;
			}
		}
		
		/* Build Form */
		$form = new Form;
		$form->attributes['id'] = 'elArchiveForm';
		$form->addHeader( 'archive_settings' );
		$form->add( new YesNo( 'archive_on', SettingsClass::i()->archive_on, FALSE, array(
			'togglesOn'	=> array(
				'archive_storage_location',
				'archive_last_post',
				'archive_topic_forums',
				'archive_topic_pinned',
				'archive_topic_featured',
				'archive_topic_state',
				'archive_topic_approved',
				'archive_topic_poll',
				'archive_topic_post',
				'archive_topic_view',
				'archive_topic_rating',
				'archive_topic_starter',
				'archive_topic_not_forums',
				'archive_not_topic_post',
				'archive_not_topic_view',
				'archive_not_topic_rating',
				'archive_not_last_post',
				'archive_topic_starter_not',
				'form_header_archive_topics_where',
				'form_header_archive_topics_not_where'
			)
		) ) );
		
		$form->add( new Radio( 'archive_storage_location', SettingsClass::i()->archive_remote_sql_host ? 'remote' : 'local', FALSE, array(
			'options'	=> array( 'local' => 'archive_storage_location_local', 'remote' => 'archive_storage_location_remote' ),
			'toggles'	=> array( 'remote' => array( 'archive_sql_host', 'archive_sql_user', 'archive_sql_pass', 'archive_sql_database', 'archive_sql_port', 'archive_sql_socket', 'archive_sql_tbl_prefix' ) )
		), NULL, NULL, NULL, 'archive_storage_location' ) );

		$form->add( new Text( 'archive_remote_sql_host', SettingsClass::i()->archive_remote_sql_host ?: ini_get('mysqli.default_host') ?: 'localhost', FALSE, array(), NULL, NULL, NULL, 'archive_sql_host' ) );
		$form->add( new Text( 'archive_remote_sql_user', SettingsClass::i()->archive_remote_sql_user ?: ini_get('mysqli.default_user'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_user' ) );
		$form->add( new Text( 'archive_remote_sql_pass', SettingsClass::i()->archive_remote_sql_pass ?: ini_get('mysqli.default_pw'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_pass' ) );
		$form->add( new Text( 'archive_remote_sql_database', SettingsClass::i()->archive_remote_sql_database ?: NULL, FALSE, array(), NULL, NULL, NULL, 'archive_sql_database' ) );
		$form->add( new Number( 'archive_sql_port', SettingsClass::i()->archive_sql_port ?: ini_get('mysqli.default_port'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_port' ) );
		$form->add( new Text( 'archive_sql_socket', SettingsClass::i()->archive_sql_socket ?: ini_get('mysqli.default_socket'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_socket' ) );
		$form->add( new Text( 'archive_sql_tbl_prefix', SettingsClass::i()->archive_sql_tbl_prefix ?: NULL, FALSE, array(), NULL, NULL, NULL, 'archive_sql_tbl_prefix' ) );
	
		
		$form->addHeader( 'archive_topics_where' );
		$form->add( new Custom( 'archive_last_post', $existingValues['archive_last_post'] ?? array( '>', 0, 'm', TRUE ), FALSE, array( 'getHtml' => array( $this, '_beforeAfterTimeAgo' ), 'validate' => array( $this, '_validateBeforeAfterTimeAgo' ) ), NULL, NULL, NULL, 'archive_last_post' ) );
		$form->add( new Node( 'archive_topic_forums', $existingValues['archive_topic_forums'] ?? 0, FALSE, array( 'class' => 'IPS\forums\Forum', 'zeroVal' => 'any', 'multiple' => TRUE, 'permissionCheck' => function ( $forum )
		{
			return $forum->sub_can_post and !$forum->redirect_url;
		} ), NULL, NULL, NULL, 'archive_topic_forums' ) );
		$form->add( new CheckboxSet( 'archive_topic_pinned', $existingValues['archive_topic_pinned'] ?? array( 1, 0 ), FALSE, array( 'options' => array( 1 => 'pinned', 0 => 'mod_confirm_unpin' ) ), NULL, NULL, NULL, 'archive_topic_pinned' ) );
		$form->add( new CheckboxSet( 'archive_topic_featured', $existingValues['archive_topic_featured'] ?? array( 1, 0 ), FALSE, array( 'options' => array( 1 => 'featured', 0 => 'mod_confirm_unfeature' ) ), NULL, NULL, NULL, 'archive_topic_featured' ) );
		$form->add( new CheckboxSet( 'archive_topic_state', $existingValues['archive_topic_state'] ?? array( 'closed', 'open' ), FALSE, array( 'options' => array( 'closed' => 'locked', 'open' => 'unlocked' ) ), NULL, NULL, NULL, 'archive_topic_state' ) );
		$form->add( new CheckboxSet( 'archive_topic_approved', $existingValues['archive_topic_approved'] ?? array( -1, 1 ), FALSE, array( 'options' => array( -1 => 'hidden', 1 => 'unhidden' ) ), NULL, NULL, NULL, 'archive_topic_approved' ) );
		$form->add( new CheckboxSet( 'archive_topic_poll', $existingValues['archive_topic_poll'] ?? array( 1, 0 ), FALSE, array( 'options' => array( 1 => 'topic_has_poll', 0 => 'topic_does_not_have_poll' ) ), NULL, NULL, NULL, 'archive_topic_poll' ) );
		$form->add( new Custom( 'archive_topic_post', $existingValues['archive_topic_post'] ?? array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_topic_post' ) );
		$form->add( new Custom( 'archive_topic_view', $existingValues['archive_topic_view'] ?? array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_topic_view' ) );
		$form->add( new Custom( 'archive_topic_rating', $existingValues['archive_topic_rating'] ?? array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_topic_rating' ) );
		$form->add( new FormMember( 'archive_topic_starter', $existingValues['archive_topic_starter'] ?? array(), FALSE, array( 'multiple' => 999999 ), NULL, NULL, NULL, 'archive_topic_starter' ) );
		$form->addHeader( 'archive_topics_not_where' );
		$form->add( new Node( 'archive_topic_not_forums', $existingValues['archive_topic_not_forums'] ?? array(), FALSE, array( 'class' => 'IPS\forums\Forum', 'multiple' => TRUE, 'permissionCheck' => function ( $forum )
		{
			return $forum->sub_can_post and !$forum->redirect_url;
		} ), NULL, NULL, NULL, 'archive_topic_not_forums' ) );
		$form->add( new Custom( 'archive_not_topic_post', $existingValues['archive_not_topic_post'] ?? array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_not_topic_post' ) );
		$form->add( new Custom( 'archive_not_topic_view', $existingValues['archive_not_topic_view'] ?? array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_not_topic_view' ) );
		$form->add( new Custom( 'archive_not_topic_rating', $existingValues['archive_not_topic_rating'] ?? array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_not_topic_rating' ) );
		$form->add( new Custom( 'archive_not_last_post', $existingValues['archive_not_last_post'] ?? array( '>', 0, 'm', TRUE ), FALSE, array( 'getHtml' => array( $this, '_beforeAfterTimeAgo' ), 'validate' => array( $this, '_validateBeforeAfterTimeAgo' ) ), NULL, NULL, NULL, 'archive_not_last_post' ) );
		$form->add( new FormMember( 'archive_topic_starter_not', $existingValues['archive_topic_starter_not'] ?? array(), FALSE, array( 'multiple' => 999999 ), NULL, NULL, NULL, 'archive_topic_starter_not' ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Translate into rules */
			$rules = array();
			foreach ( $values as $k => $v )
			{
				if ( in_array( $k, array( 'archive_last_post' ) ) )
				{
					if ( ( !isset( $v[3] ) or !$v[3] ) and $v[1] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'lastpost',
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> $v[2],
							'archive_skip'	=> 0,
						);
					}
				}
				elseif ( in_array( $k, array( 'archive_not_last_post' ) ) )
				{
					if ( ( !isset( $v[3] ) or !$v[3] ) and $v[1] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'lastpost',
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> $v[2],
							'archive_skip'	=> 1,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_forums' ) ) )
				{
					if ( !empty( $v ) )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'forum',
							'archive_value'	=> '+',
							'archive_text'	=> implode( ',', array_keys( $v ) ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_not_forums' ) ) )
				{
					if ( !empty( $v ) )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'forum',
							'archive_value'	=> '-',
							'archive_text'	=> implode( ',', array_keys( $v ) ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif ( in_array( $k, array( 'archive_topic_pinned', 'archive_topic_featured', 'archive_topic_state', 'archive_topic_approved', 'archive_topic_poll' ) ) )
				{
					if ( !empty( $v ) and count( $v ) == 1 )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> mb_substr( $k, 14 ),
							'archive_value'	=> array_pop( $v ),
							'archive_text'	=> '',
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_post', 'archive_topic_view', 'archive_topic_rating' ) ) )
				{
					if ( !isset( $v[2] ) and $v[1] and $v[0] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> mb_substr( $k, 14 ),
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_not_topic_post', 'archive_not_topic_view', 'archive_not_topic_rating' ) ) )
				{
					if ( !isset( $v[2] ) and $v[1] and $v[0] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> mb_substr( $k, 18 ),
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> '',
							'archive_skip'	=> 1,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_starter' ) ) )
				{
					if ( is_array( $v ) and count( $v ) )
					{
						$ids = array();
						foreach( $v AS $member )
						{
							$ids[] = $member->member_id;
						}
						
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'member',
							'archive_value'	=> '+',
							'archive_text'	=> implode( ',', $ids ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_starter_not' ) ) )
				{
					if ( is_array( $v ) AND count( $v ) )
					{
						$ids = array();
						foreach( $v AS $member )
						{
							$ids[] = $member->member_id;
						}
						
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'member',
							'archive_value'	=> '-',
							'archive_text'	=> implode( ',', $ids ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
			}
			
			/* Did we just want a new count? */
			if ( isset( Request::i()->getCount ) )
			{
				if ( !$values['archive_on'] )
				{
					$count = 0;
				}
				else
				{				
					$count = Db::i()->select( 'COUNT(*)', 'forums_topics', array_merge( array( array( 'topic_archive_status!=?', Topic::ARCHIVE_EXCLUDE ) ), Application::load('forums')->archiveWhere( $rules ) ) )->first();
				}
				Output::i()->json( array( 'count' => $count, 'percentage' => ( $maxTopics * $count > 0 ) ? round( 100 / $maxTopics * $count ) : 0 ) );
			}
			
			/* No, we're actually saving */
			else
			{
				/* Check remote database */
				if ( $values['archive_storage_location'] === 'remote' )
				{
					try
					{
						$remoteDatabase = Db::i( 'archive', array(
							'sql_host'		=> $values['archive_remote_sql_host'],
							'sql_user'		=> $values['archive_remote_sql_user'],
							'sql_pass'		=> $values['archive_remote_sql_pass'],
							'sql_database'	=> $values['archive_remote_sql_database'],
							'sql_port'		=> $values['archive_sql_port'],
							'sql_socket'	=> $values['archive_sql_socket'],
							'sql_tbl_prefix'=> $values['archive_sql_tbl_prefix'],
							'sql_utf8mb4'	=> isset( SettingsClass::i()->sql_utf8mb4 ) ? SettingsClass::i()->sql_utf8mb4 : FALSE
						) );
						
						if ( !$remoteDatabase->checkForTable('forums_archive_posts') )
						{
							$remoteDatabase->createTable( Db::i()->getTableDefinition('forums_archive_posts') );
						}
					}
					catch ( Exception $e )
					{
						$form->error = Member::loggedIn()->language()->addToStack( 'archive_remote_db_error', FALSE, array( 'sprintf' => array( "{$e->getMessage()} ({$e->getCode()})" ) ) );
						goto showForm; // Dinosaur attack!!!!
					}
				}
				
				/* Save settings */
				$settingValues = array();
				foreach ( array( 'archive_on', 'archive_remote_sql_host', 'archive_remote_sql_user', 'archive_remote_sql_user', 'archive_remote_sql_pass', 'archive_remote_sql_database', 'archive_sql_port', 'archive_sql_socket', 'archive_sql_tbl_prefix' ) as $k )
				{
					if ( $k !== 'archive_on' and $values['archive_storage_location'] !== 'remote' )
					{
						$settingValues[ $k ] = NULL;
					}
					else
					{
						$settingValues[ $k ] = $values[ $k ];
					}
				}
				$form->saveAsSettings( $settingValues );
				
				Db::i()->delete( 'forums_archive_rules' );
				if ( count( $rules ) )
				{
					Db::i()->insert( 'forums_archive_rules', $rules );
				}
				
				/* Do we need to unarchive some? */
				if( $values['archive_on'] )
				{
					$whereClause = Db::i()->compileWhereClause( Application::load('forums')->archiveWhere( $rules ) );
					$_whereClause = array( 'topic_archive_status=? AND !( ' . $whereClause['clause'] . ')' );
					$_whereClause[] = Topic::ARCHIVE_DONE;
					foreach ( $whereClause['binds'] as $bind )
					{
						$_whereClause[] = $bind;
					}
				}
				else
				{
					$_whereClause = array( 'topic_archive_status NOT IN(' . Topic::ARCHIVE_NOT . ',' . Topic::ARCHIVE_EXCLUDE . ')' );
				}

				/* Make sure the unarchive task is enabled - it will disable itself if there is no work to do */
				Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'unarchive' ) );

				/* And disable the archive task if archiving is disabled */
				if( !$values['archive_on'] )
				{
					Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'archive' ) );
				}
				/* Or enable it if archiving is enabled */
				else
				{
					Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'archive' ) );
				}
				
				/* Log and redirect */
				Session::i()->log( 'acplogs__archive_settings' );
				if ( Db::i()->select( 'COUNT(*)', 'forums_topics', $_whereClause )->first() )
				{
					Output::i()->redirect( Url::internal( 'app=forums&module=forums&controller=settings&do=unarchive' ) );
				}
				else
				{				
					Output::i()->redirect( Url::internal( 'app=forums&module=forums&controller=settings&tab=archiving' ) );
				}
			}
		}
		
		/* Display */
		showForm:
		return Theme::i()->getTemplate( 'settings' )->archiveRules( $form, $maxTopics, $existingCount, ( $maxTopics * $existingCount > 0 ) ? round( 100 / $maxTopics * $existingCount ) : 0 );
	}
		
	/**
	 * Greater/Less Than X or Any
	 *
	 * @param Custom $field	The field
	 * @return	string
	 */
	public function _greaterThanLessThanField( Custom $field ): string
	{
		return Theme::i()->getTemplate( 'settings' )->archiveRuleGtLt( $field->name, $field->value );
	}
	
	/**
	 * Before/After X days/months/years ago
	 *
	 * @param Custom $field	The field
	 * @return	string
	 */
	public function _beforeAfterTimeAgo( Custom $field ): string
	{
		return Theme::i()->getTemplate( 'settings' )->archiveRuleTime( $field->name, $field->value );
	}
	
	/**
	 * Before/After X days/months/years ago
	 *
	 * @param Custom $field	The field
	 * @return	void
	 */
	public function _validateBeforeAfterTimeAgo( Custom $field ) : void
	{
		if ( isset( $field->value[1] ) and $field->value[1] < 0 )
		{
			throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_number_min', FALSE, array( 'sprintf' => array( 0 ) ) ) );
		}
	}
	
	/**
	 * Unarchive topics that no longer match settings?
	 *
	 * @return	void
	 */
	public function unarchive() : void
	{
		Dispatcher::i()->checkAcpPermission( 'archive_manage' );
		
		if ( isset( Request::i()->confirm ) )
		{
			Session::i()->csrfCheck();
			
			if ( SettingsClass::i()->archive_on )
			{
				$whereClause = Db::i()->compileWhereClause( Application::load('forums')->archiveWhere( Db::i()->select( '*', 'forums_archive_rules' ) ) );
				$_whereClause = array( 'topic_archive_status=? AND !( ' . $whereClause['clause'] . ')' );
				$_whereClause[] = Topic::ARCHIVE_DONE;
				foreach ( $whereClause['binds'] as $bind )
				{
					$_whereClause[] = $bind;
				}
				Db::i()->update( 'forums_topics', array( 'topic_archive_status' => Topic::ARCHIVE_RESTORE ), $_whereClause );
			}
			else
			{
				Db::i()->update( 'forums_topics', array( 'topic_archive_status' => Topic::ARCHIVE_RESTORE ), array( 'topic_archive_status=?', Topic::ARCHIVE_DONE ) );
			}
			
			Output::i()->redirect( Url::internal( 'app=forums&module=forums&controller=settings&tab=archiving' ) );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->decision( 'uanrchive_settings_change', array(
				'restore_unmatched_settings'	=> Url::internal( 'app=forums&module=forums&controller=settings&do=unarchive&confirm=1' )->csrf(),
				'leave_archived_topics'			=> Url::internal( 'app=forums&module=forums&controller=settings&tab=archiving' ),
			) );
		}
	}

	/**
	 * Settings
	 *
	 * @return Form|string
	 */
	protected function manageSettings(): Form|string
	{
		Dispatcher::i()->checkAcpPermission( 'forum_settings' );
		
		$form = new Form;
		$form->addHeader('forum_settings');
		$form->add( new YesNo( 'forums_rss', SettingsClass::i()->forums_rss ) );

		$form->add( new YesNo( 'forums_fluid_pinned', SettingsClass::i()->forums_fluid_pinned, FALSE, array(), NULL, NULL, NULL, 'forums_fluid_pinned' ) );
		$form->add( new Number( 'forums_topics_per_page', SettingsClass::i()->forums_topics_per_page , TRUE, array( 'min' => 5, 'max' => 100 ) ) );

		$form->addHeader('topic_settings');

		$form->add( new Number( 'forums_posts_per_page', SettingsClass::i()->forums_posts_per_page , TRUE, array( 'min' => 2, 'max' => 250 ) ) );

		$form->add( new CheckboxSet( 'forums_topics_show_meta', SettingsClass::i()->forums_topics_show_meta ? json_decode( SettingsClass::i()->forums_topics_show_meta, TRUE ) : array(), FALSE, array(
			'options' => array(
				'time'           => 'forums_topic_show_meta_time',
				'moderation'     => 'forums_topic_show_meta_moderation'
			),
			'toggles' => array(
				'moderation'	 => array( 'forums_mod_actions_anon' )
			) ) ) );

		$form->add( new YesNo( 'forums_mod_actions_anon', !SettingsClass::i()->forums_mod_actions_anon, FALSE, array(), NULL, NULL, NULL, 'forums_mod_actions_anon' ) );
		$form->add( new Number( 'forums_solved_topic_reengage', SettingsClass::i()->forums_solved_topic_reengage , FALSE, array( 'min' => 1, 'max' => 365, 'unlimited' => 0, 'unlimitedLang' => 'forums_solved_topic_reengage_never' ), NULL, Member::loggedIn()->language()->addToStack('forums_solved_nudge_experts_prefix'), Member::loggedIn()->language()->addToStack('forums_solved_nudge_experts_suffix') ) );
		$form->add( new Number( 'forums_helpful_highlight', SettingsClass::i()->forums_helpful_highlight , FALSE, array( 'min' => 1, 'unlimited' => 0, 'unlimitedLang' => 'forums_helpful_highlight_never' ), NULL, Member::loggedIn()->language()->addToStack('forums_helpful_highlight_prefix'), Member::loggedIn()->language()->addToStack('forums_helpful_highlight_suffix') ) );

		if( Bridge::i()->featureIsEnabled( 'experts' ) )
		{
			$form->add( new Number( 'forums_solved_nudge_experts', SettingsClass::i()->forums_solved_nudge_experts, FALSE, array( 'min' => 1, 'max' => 365, 'unlimited' => 0, 'unlimitedLang' => 'forums_solved_nudge_experts_never' ), NULL, Member::loggedIn()->language()->addToStack('forums_solved_topic_reengage_prefix'), Member::loggedIn()->language()->addToStack('forums_solved_topic_reengage_suffix') ) );
		}

		$form->add( new YesNo( "forum_post_use_minimal_editor", (bool) SettingsClass::i()->forum_post_use_minimal_editor, false ) );

		$form->addHeader('topic_overview_settings');

		$form->add( new CheckboxSet( 'forums_topic_activity', SettingsClass::i()->forums_topic_activity ? json_decode( SettingsClass::i()->forums_topic_activity, TRUE ) : NULL, FALSE, array(
			'options' => array( 'desktop' => 'forums_topic_activity_desktop_on', 'mobile' => 'forums_topic_activity_mobile_on' ),
			'toggles' => array( 'desktop' => array( 'forums_topic_activity_desktop' ) )
		) ) );
		$form->add( new Radio( 'forums_topic_activity_desktop', SettingsClass::i()->forums_topic_activity_desktop, FALSE, array( 'options' => array( 'sidebar' => 'forums_topic_activity_desktop_sidebar', 'post' => 'forums_topic_activity_desktop_post' ) ), NULL, NULL, NULL, 'forums_topic_activity_desktop' ) );

		$form->add( new Number( 'forums_topics_activity_pages_show', SettingsClass::i()->forums_topics_activity_pages_show , TRUE, array( 'unlimited' => 0, 'unlimitedLang'=> 'forums_topics_activity_pages_show_unlimited', 'min' => 0, 'max' => 100 ), NULL, Member::loggedIn()->language()->addToStack('forums_topics_activity_pages_show_prefix') ) );

		$form->add( new CheckboxSet( 'forums_topic_activity_features', SettingsClass::i()->forums_topic_activity_features ? json_decode( SettingsClass::i()->forums_topic_activity_features, TRUE ) : array(), FALSE, array(
			'options' => array(
				'popularDays' => 'forums_topic_activity_features_popularDays',
				'topPost'     => 'forums_topic_activity_features_topPost',
				'helpful'	  => 'forums_topic_activity_features_helpful',
				'uploads'     => 'forums_topic_activity_features_uploads'
		) ) ) );

		if ( $values = $form->values() )
		{
			/* This setting needs to be reversed */
			$values['forums_mod_actions_anon'] = !$values['forums_mod_actions_anon'];

			if ( isset( $values['forums_default_view_choose'] ) )
			{
				$values['forums_default_view_choose'] = ( is_array( $values['forums_default_view_choose'] ) AND !count( $values['forums_default_view_choose'] ) ) ? NULL : json_encode( $values['forums_default_view_choose'] );
			}

			if ( isset( $values['forums_topic_activity'] ) )
			{
				$values['forums_topic_activity'] = json_encode( $values['forums_topic_activity'] );
			}

			if ( isset( $values['forums_topic_activity_features'] ) )
			{
				$values['forums_topic_activity_features'] = json_encode( $values['forums_topic_activity_features'] );
			}

			if ( isset( $values['forums_topics_show_meta'] ) )
			{
				$values['forums_topics_show_meta'] = json_encode( $values['forums_topics_show_meta'] );
			}
						
			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__forums_settings' );
			Output::i()->redirect( Url::internal( 'app=forums&module=forums&controller=settings&tab=settings' ), 'saved' );
		}
		
		return $form;
	}
}