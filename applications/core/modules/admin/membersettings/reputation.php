<?php
/**
 * @brief		Reputation
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Mar 2013
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content\Reaction;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Timezone;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reputation
 */
class reputation extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\Content\Reaction';
	
	/**
	 * Title can contain HTML?
	 */
	public bool $_titleHtml = TRUE;

	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'reps_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->title		= Member::loggedIn()->language()->addToStack('reputation_title');
		
		/* Init */
		$activeTab = Request::i()->tab ?: NULL;
		$activeTabContents = '';
		$tabs = array();
		
		/* Settings */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'reps_settings' ) )
		{
			$tabs['settings'] = 'reputation_settings';
		}

		/* Reactions */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'reactions_manage' ) )
		{
			$tabs['reactions'] = 'reactions';
		}

		/* Leaderboard */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'reps_settings' ) )
		{
			$tabs['leaderboard'] = 'reputation_leaderboard';
		}
		
		/* Levels */		
		if ( Settings::i()->reputation_enabled and Settings::i()->reputation_show_profile )
		{
			$tabs['levels'] = 'reputation_levels';
		}
		
		/* Make sure we have a tab */
		if ( empty( $tabs ) )
		{
			Output::i()->error( 'no_module_permission', '1C225/1', 403, '' );
		}
		elseif ( !$activeTab or !array_key_exists( $activeTab, $tabs ) )
		{
			$_tabs = array_keys( $tabs );
			$activeTab = array_shift( $_tabs );
		}

		$blurbLangKey = $activeTab;
		
		/* Do it */
		if ( $activeTab === 'reactions' )
		{
			Dispatcher::i()->checkAcpPermission( 'reactions_manage' );
			parent::manage();
			$activeTabContents = Theme::i()->getTemplate( 'global' )->paddedBlock( Output::i()->output );
			if ( Bridge::i()->liveTopicsUnconverted() )
			{
				$blurbLangKey = 'reactions__locked__livetopics';
				$message = Theme::i()->getTemplate( 'global', 'cloud' )->reactionsDisabledLivetopicsMessage();
				Member::loggedIn()->language()->parseOutputForDisplay( $message );
				Member::loggedIn()->language()->words['rep_' . $blurbLangKey . '_blurb' ] = $message . Member::loggedIn()->language()->addToStack( 'rep_reactions_blurb' );
			}
		}
		else if ( $activeTab === 'settings' )
		{
			Dispatcher::i()->checkAcpPermission( 'reps_settings' );
		
			/* Random for the "like" preview */
			$maxMemberId =  Db::i()->select( 'MAX(member_id)', 'core_members' )->first();
			$names = array();
			foreach ( range( 1, ( $maxMemberId > 3 ) ? 3 : $maxMemberId ) as $i )
			{
				do
				{
					$randomMemberId = rand( 1, $maxMemberId );
				}
				while ( array_key_exists( $randomMemberId, $names ) );
								
				try
				{
					$where = array( array( 'member_id>=?', $randomMemberId ) );
					if ( !empty( $names ) )
					{
						$where[] = Db::i()->in( 'member_id', array_keys( $names ), TRUE );
					}
					
					$member = Member::constructFromData( Db::i()->select( '*', 'core_members', $where, 'member_id ASC', 1 )->first() );
					$names[ $member->member_id ] = '<a>' . htmlentities( $member->name, ENT_DISALLOWED, 'UTF-8', FALSE ) . '</a>';
				}
				catch ( Exception $e )
				{
					break;
				}
			}
			if ( count( $names ) == 3 )
			{
				$names[] = Member::loggedIn()->language()->addToStack( 'like_blurb_others', FALSE, array( 'pluralize' => array( 2 ) ) );
			}
			if ( empty( $names ) )
			{
				$blurb = '';
			}
			else
			{
				$blurb = Member::loggedIn()->language()->addToStack( 'like_blurb', FALSE, array( 'htmlsprintf' => array( Member::loggedIn()->language()->formatList( $names ) ), 'pluralize' => array( count( $names ) ) ) );
			}

			/* Build Form */
			$form = new Form();
			$form->add( new YesNo( 'reputation_enabled', Settings::i()->reputation_enabled, FALSE, array( 'togglesOn' => array( 'reputation_point_types', 'reputation_protected_groups', 'reputation_can_self_vote', 'reputation_highlight', 'reputation_show_profile', 'overall_reaction_count' ) ) ) );
			$form->add( new CheckboxSet( 'reputation_protected_groups', explode( ',', Settings::i()->reputation_protected_groups ), FALSE, array( 'options' => Group::groups( TRUE, FALSE ), 'parse' => 'normal', 'multiple' => TRUE ), NULL, NULL, NULL, 'reputation_protected_groups' ) );
			$form->add( new YesNo( 'reputation_can_self_vote', Settings::i()->reputation_can_self_vote, FALSE, array(
				'disabled' => Bridge::i()->liveTopicsUnconverted()
			), NULL, NULL, NULL, 'reputation_can_self_vote' ) );
			$form->add( new Number( 'reputation_highlight', Settings::i()->reputation_highlight, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('reputation_highlight_prefix'), Member::loggedIn()->language()->addToStack('reputation_highlight_suffix'), 'reputation_highlight' ) );
			$form->add( new YesNo( 'reputation_show_profile', Settings::i()->reputation_show_profile, FALSE, array(), NULL, NULL, NULL, 'reputation_show_profile' ) );
			$form->add( new Radio( 'reaction_count_display', Settings::i()->reaction_count_display, FALSE, array( 'options' => array( 'individual' => 'individual_reactions', 'count' => 'overall_reaction_count' ) ) ) );
		
			/* Save */
			if ( $form->saveAsSettings() )
			{
				Session::i()->log( 'acplogs__rep_settings' );
				Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=reputation&tab=settings' ), 'saved' );
			}
			
			/* Display */
			$activeTabContents = (string) $form;
		}
		else if ( $activeTab === 'leaderboard' )
		{
			Dispatcher::i()->checkAcpPermission( 'reps_settings' );
			
			/* Get available filters */
			$topMemberFilters = Member::topMembersOptions();
		
			/* Build Form */
			$form = new Form();
			$form->add( new YesNo( 'reputation_leaderboard_on', Settings::i()->reputation_leaderboard_on, FALSE, array( 'togglesOn' => array( 'reputation_leaderboard_default_tab', 'form_header_leaderboard_tabs_leaderboard', 'reputation_show_days_won_trophy', 'reputation_timezone', 'form_header_leaderboard_tabs_members', 'reputation_top_members_overview', 'reputation_overview_max_members', 'reputation_top_members_filters', 'reputation_max_members', 'leaderboard_excluded_groups' ) ), NULL, NULL, NULL, 'reputation_leaderboard_on' ) );
			$form->add( new Radio( 'reputation_leaderboard_default_tab', Settings::i()->reputation_leaderboard_default_tab, FALSE, array( 'options' => array( 'leaderboard' => 'leaderboard_tabs_leaderboard', 'history' => 'leaderboard_tabs_history', 'members' => 'leaderboard_tabs_members' ) ), NULL, NULL, NULL, 'reputation_leaderboard_default_tab' ) );
			$form->add( new CheckboxSet( 'leaderboard_excluded_groups', explode( ',', Settings::i()->leaderboard_excluded_groups ), FALSE, array( 'options' => Group::groups( TRUE, FALSE ), 'parse' => 'normal', 'multiple' => TRUE ), NULL, NULL, NULL, 'leaderboard_excluded_groups' ) );
			$form->addHeader('leaderboard_tabs_leaderboard');
			$form->add( new YesNo( 'reputation_show_days_won_trophy', Settings::i()->reputation_show_days_won_trophy, FALSE, array( 'togglesOn' => array() ), NULL, NULL, NULL, 'reputation_show_days_won_trophy' ) );
			$form->add( new Timezone( 'reputation_timezone', Settings::i()->reputation_timezone, FALSE, array(), NULL, NULL, NULL, 'reputation_timezone' ) );
			$form->addHeader('leaderboard_tabs_members');
			$form->add( new CheckboxSet( 'reputation_top_members_overview', explode( ',', Settings::i()->reputation_top_members_overview ), FALSE, array( 'options' => $topMemberFilters ), NULL, NULL, NULL, 'reputation_max_members' ) );
			$form->add( new Number( 'reputation_overview_max_members', Settings::i()->reputation_overview_max_members, FALSE, array( 'min' => 3, 'max' => 100 ), NULL, NULL, NULL, 'reputation_overview_max_members' ) );
			$form->add( new CheckboxSet( 'reputation_top_members_filters', Settings::i()->reputation_top_members_filters == '*' ? array_keys( $topMemberFilters ) : explode( ',', Settings::i()->reputation_top_members_filters ), FALSE, array( 'options' => $topMemberFilters ), NULL, NULL, NULL, 'reputation_top_members_filters' ) );
			$form->add( new Number( 'reputation_max_members', Settings::i()->reputation_max_members, FALSE, array( 'min' => 3, 'max' => '100' ), NULL, NULL, NULL, 'reputation_max_members' ) );
			
			/* Save */
			if ( $values = $form->values() )
			{
				/* Save */
				$values['reputation_top_members_overview'] = implode( ',', $values['reputation_top_members_overview'] );
				$values['leaderboard_excluded_groups'] = implode( ',', $values['leaderboard_excluded_groups'] );
				$values['reputation_top_members_filters'] = $values['reputation_top_members_filters'] == array_keys( $topMemberFilters ) ? '*' : implode( ',', $values['reputation_top_members_filters'] );				
				$form->saveAsSettings( $values );

				Session::i()->log( 'acplogs__rep_settings' );
				Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=reputation&tab=leaderboard' ), 'saved' );
			}
			
			/* Display */
			$activeTabContents = (string) $form;
		}
		else
		{
			/* Create the table */
			$table = new TableDb( 'core_reputation_levels', Url::internal( 'app=core&module=membersettings&controller=reputation&tab=levels' ) );
			$table->langPrefix = 'rep_';
			
			/* Columns */
			$table->joins = array(
				array( 'select' => 'w.word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'core_reputation_level_', core_reputation_levels.level_id ) AND w.lang_id=" . Member::loggedIn()->language()->id )
			);
			$table->include = array( 'word_custom', 'level_image', 'level_points' );
			$table->mainColumn = 'word_custom';
			$table->quickSearch = 'word_custom';
			
			/* Sorting */
			$table->noSort = array( 'level_image' );
			$table->sortBy = $table->sortBy ?: 'level_points';
			$table->sortDirection = $table->sortDirection ?: 'asc';
			
			/* Parsers */
			$table->parsers = array(
				'level_image'	=> function( $val )
				{
					if ( $val )
					{
						return "<img src='" . File::get( "core_Theme", $val )->url . "' alt=''>";
					}
					return '';
				}
			);
			
			/* Buttons */
			$table->rootButtons = array(
				'add'	=> array(
					'title'	=> 'add',
					'icon'	=> 'plus',
					'link'	=> Url::internal( 'app=core&module=membersettings&controller=reputation&do=levelForm' ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
				),
			);
			$table->rowButtons = function( $row )
			{
				return array(
					'edit'	=> array(
						'title'	=> 'edit',
						'icon'	=> 'pencil',
						'link'	=> Url::internal( 'app=core&module=membersettings&controller=reputation&do=levelForm&id=' ) . $row['level_id'],
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
					),
					'delete'	=> array(
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'link'	=> Url::internal( 'app=core&module=membersettings&controller=reputation&do=deleteLevel&id=' ) . $row['level_id'],
						'data'	=> array( 'delete' => '' )
					),
				);
			};
			
			/* Display */
			$activeTabContents = (string) $table;
		}

		$activeTabContents = Theme::i()->getTemplate( 'forms' )->blurb( 'rep_' . $blurbLangKey . '_blurb', TRUE, TRUE ) . $activeTabContents;
			
		/* Display */
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, Url::internal( "app=core&module=membersettings&controller=reputation" ) );
		}
	}
	
	/**
	 * Add/Edit
	 *
	 * @return	void
	 */
	public function levelForm() : void
	{
		$current = NULL;
		if ( Request::i()->id )
		{
			$current = Db::i()->select( '*', 'core_reputation_levels', array( 'level_id=?', Request::i()->id ) )->first();
		}
	
		$form = new Form();
		
		$form->add( new Translatable( 'rep_level_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $current ? "core_reputation_level_{$current['level_id']}" : NULL ) ) ) );
		$form->add( new Upload( 'rep_level_image', ( $current and $current['level_image'] ) ? File::get( 'core_Theme', $current['level_image'] ) : NULL, FALSE, array( 'storageExtension' => 'core_Theme' ) ) );
		$form->add( new Number( 'rep_level_points', $current ? $current['level_points'] : 0, TRUE, array( 'min' => NULL ) ) );
		
		if ( $values = $form->values() )
		{
			$save = array(
				'level_image'	=> $values['rep_level_image'] ? str_replace( \IPS\ROOT_PATH . '/uploads/', '', $values['rep_level_image'] ) : '',
				'level_points'	=> $values['rep_level_points'],
			);
		
			if ( $current )
			{
				Db::i()->update( 'core_reputation_levels', $save, array( 'level_id=?', $current['level_id'] ) );
				$id = $current['level_id'];
			}
			else
			{
				$id = Db::i()->insert( 'core_reputation_levels', $save );
			}

			Session::i()->log( 'acplogs__rep_edited', array( $save['level_points'] => FALSE ) );
			
			unset( Store::i()->reputationLevels );

			Lang::saveCustom( 'core', "core_reputation_level_{$id}", $values['rep_level_title'] );
			
			Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=reputation&tab=levels' ), 'saved' );
		}

		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( $current ? "core_reputation_level_{$current['level_id']}" : 'add', $form, FALSE );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function deleteLevel() : void
	{
		Dispatcher::i()->checkAcpPermission( 'reps_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$current = Db::i()->select( '*', 'core_reputation_levels', array( 'level_id=?', Request::i()->id ) )->first();
			
			Session::i()->log( 'acplogs__rep_deleted', array( $current['level_points'] => FALSE ) );
			Db::i()->delete( 'core_reputation_levels', array( 'level_id=?', Request::i()->id ) );
			unset( Store::i()->reputationLevels );

			Lang::deleteCustom( 'core', 'core_reputation_level_' . Request::i()->id );
		}
		catch ( UnderflowException $e ) { }
				
		Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=reputation&tab=levels' ) );
	}
	
	/**
	 * Rebuild leaderboard
	 *
	 * @return void
	 */
	 public function rebuildLeaderboard() : void
	 {
		if ( ! isset( Request::i()->process ) )
		{
			 Output::i()->title = Member::loggedIn()->language()->addToStack('reputation_leaderboard_rebuild_title');
			 Output::i()->output = Theme::i()->getTemplate( 'settings' )->reputationLeaderboardRebuild();
		}
		else
		{
			Session::i()->csrfCheck();
			Task::queue( 'core', 'RebuildReputationLeaderboard', array(), 4 );
			Db::i()->delete('core_reputation_leaderboard_history');
			Session::i()->log( 'acplog__rebuilt_leaderboard' );
			Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=reputation&tab=leaderboard' ), 'reputation_leaderboard_rebuilding' );
		}
	 }
	 
	 /**
	 * Function to execute after nodes are reordered. Do nothing by default but app can extend.
	 *
	 * @param	array	$order	The new ordering that was saved
	 * @return	void
	 */
	protected function _afterReorder( array $order ) : void
	{
		unset( Store::i()->reactions );
	}

	/**
	 * Redirect after save
	 *
	 * @param Model|null $old A clone of the node as it was before or NULL if this is a creation
	 * @param Model $new The node now
	 * @param string $lastUsedTab The tab last used in the form
	 * @return    void
	 */
	protected function _afterSave( ?Model $old, Model $new, mixed $lastUsedTab = FALSE ): void
	{
		if( Request::i()->isAjax() )
		{
			Output::i()->json( array() );
		}
		else
		{
			if( isset( Request::i()->save_and_reload ) )
			{
				$buttons = $new->getButtons($this->url, !($new instanceof $this->nodeClass));

				Output::i()->redirect( ( $lastUsedTab ? $buttons['edit']['link']->setQueryString('activeTab', $lastUsedTab ) : $buttons['edit']['link'] ), 'saved' );
			}
			else
			{
				Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $new->parent() ? $new->parent()->_id : '' ), 'tab' => 'reactions' ) ), 'saved' );
			}
		}
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		/* Get node */
		/* @var Reaction $nodeClass */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}
		
		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/J', 404, '' );
		}
		 
		/* Permission check */
		if( !$node->canDelete() )
		{
			Output::i()->error( 'node_noperm_delete', '2S101/H', 403, '' );
		}

		/* Do we have any children or content? */
		$form = new Form;
		$form->add( new Radio( 'existing_reactions', NULL, TRUE, array( 'options' => array( 'change' => 'change_new_reaction', 'delete' => 'delete' ), 'toggles' => array( 'change' => array( 'new_reaction' ) ) ) ) );
		$form->add( new Node( 'new_reaction', NULL, TRUE, array( 'class' => 'IPS\Content\Reaction', 'permissionCheck' => function( $row ) use ( $node ) {
			if ( $row->_id == $node->_id )
			{
				return FALSE;
			}
			
			return TRUE;
		} ), NULL, NULL, NULL, 'new_reaction' ) );
		
		if ( $values = $form->values() )
		{
			if ( $values['existing_reactions'] == 'change' AND array_key_exists( 'new_reaction', $values ) )
			{
				Db::i()->update( 'core_reputation_index', array( 'reaction' => $values['new_reaction']->_id ), array( "reaction=?", $node->_id ) );
			}
			else
			{
				Db::i()->delete( 'core_reputation_index', array( "reaction=?", $node->_id ) );
			}
			
			/* Delete it */
			Session::i()->log( 'acplog__node_deleted', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
			$node->delete();
	
			/* Boink */
			if( Request::i()->isAjax() )
			{
				Output::i()->json( "OK" );
			}
			else
			{
				Output::i()->redirect( $this->url->setQueryString( 'tab', 'reactions' )->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->_id : '' ) ) ), 'deleted' );
			}
		}
		
		Output::i()->output = $form;
	}
}