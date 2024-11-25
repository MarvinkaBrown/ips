<?php
/**
 * @brief		Keyword Tracking
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Mar 2017
 */

namespace IPS\core\modules\admin\activitystats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\core\Statistics\Chart;
use IPS\Content;
use IPS\Content\Comment;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Keyword Tracking
 */
class keywords extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'keywords_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Show button to adjust settings */
		Output::i()->sidebar['actions']['settings'] = array(
			'icon'		=> 'cog',
			'primary'	=> TRUE,
			'title'		=> 'manage_keywords',
			'link'		=> Url::internal( 'app=core&module=activitystats&controller=keywords&do=settings' ),
			'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
		);

		$tabs = array(
			'time'	=> 'keywords_usage_over_time',
			'list'	=> 'keywords_usage_list',
		);

		Request::i()->tab ??= 'time';
		$activeTab = ( array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'time';

		if ( $activeTab === 'time' )
		{
			$output = Chart::loadFromExtension( 'core', 'Keywords' )->getChart( Url::internal( "app=core&module=activitystats&controller=keywords&tab=time" ) );
		}
		else
		{
			/* Create the table */
			$table = new TableDb( 'core_statistics', Url::internal( 'app=core&module=activitystats&controller=keywords&tab=list' ), array( array( 'type=?', 'keyword' ) ) );
			$table->langPrefix = 'keywordstats_';
			$table->quickSearch = 'value_4';

			/* Columns we need */
			$table->include = array( 'value_4', 'extra_data', 'author', 'time' );
			$table->mainColumn = 'value_4';
			$table->noSort	= array( 'extra_data' );

			$table->sortBy = $table->sortBy ?: 'time';
			$table->sortDirection = $table->sortDirection ?: 'desc';

			/* Custom parsers */
			$table->parsers = array(
				'time'			=> function( $val, $row )
				{
					return DateTime::ts( $val )->localeDate();
				},
				'author'		=> function( $val, $row )
				{
					$data = json_decode( $row['extra_data'], true );

					try
					{
						$class = $data['class'];

						/* Check that the class exists */
						if ( !class_exists( $class ) )
						{
							throw new InvalidArgumentException;
						}

						$item = $class::load( $data['id'] );

						return $item->author()->link();
					}
					catch ( Exception $e )
					{
						return Member::loggedIn()->language()->addToStack( 'unknown' );
					}
				},
				'extra_data'	=> function( $val, $row )
				{
					$data = json_decode( $val, TRUE );

					try
					{
						$class	= $data['class'];

						/* Check that the class exists */
						if( !class_exists( $class ) )
						{
							throw new InvalidArgumentException;
						}

						$item	= $class::load( $data['id'] );

						return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $item->url(), TRUE, ( $item instanceof Comment ) ? $item->item()->mapped('title') : $item->mapped('title'), TRUE );
					}
					catch( Exception $e )
					{
						return Member::loggedIn()->language()->addToStack( 'content_deleted' );
					}
				}
			);

			/* Display */
			$output	= Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table, TRUE, 'i-padding_3 i-margin-top_1' );
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $output;
		}
		else
		{
			Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_activitystats_keywords');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $output, Url::internal( "app=core&module=activitystats&controller=keywords" ), 'tab', '', 'i-padding_3' );
		}
	}

	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$form = new Form;
		$form->add( new Stack( 'stats_keywords', Settings::i()->stats_keywords ? json_decode( Settings::i()->stats_keywords, true ) : array(), FALSE, array( 'stackFieldType' => 'Text' ), NULL, NULL, NULL, 'stats_keywords' ) );
		$form->add( new Interval( 'stats_keywords_prune', Settings::i()->stats_keywords_prune, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL ) );
	
		if ( $values = $form->values() )
		{
			$values['stats_keywords'] = json_encode( array_unique( $values['stats_keywords'] ) );

			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__statskeywords_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=activitystats&controller=keywords' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('settings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'settings', $form, FALSE );
	}

	/**
	 * Rebuild
	 *
	 * @return	void
	 */
	protected function rebuild() : void
	{
		if ( !Settings::i()->stats_keywords )
		{
			Output::i()->error( 'no_keywords_to_rebuild', '2C433/1', 403, '' );
		}

		Session::i()->csrfCheck();

		Db::i()->delete( 'core_statistics', array( 'type=?', 'keyword' ) );
		foreach( Content::routedClasses() AS $class )
		{
			Task::queue( 'core', 'RebuildKeywords', array( 'class' => $class ) );
		}

		Session::i()->log( 'acplog__statskeywords_rebuilt' );
		Output::i()->redirect( Url::internal( 'app=core&module=activitystats&controller=keywords' ), 'saved' );
	}
}