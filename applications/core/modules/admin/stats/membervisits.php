<?php
/**
 * @brief		Member visit statistics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Mar 2017
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member visit statistics
 */
class membervisits extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'membervisits_manage' );
		parent::execute();
	}

	/**
	 * Member visit statistics
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$count		= NULL;
		$table		= NULL;
		$start		= NULL;
		$end		= NULL;

		$defaults = array( 'start' => DateTime::create()->setDate( date('Y'), date('m'), 1 ), 'end' => new DateTime );

		if( isset( Request::i()->visitDateStart ) AND isset( Request::i()->visitDateEnd ) )
		{
			$defaults = array( 'start' => DateTime::ts( Request::i()->visitDateStart ), 'end' => DateTime::ts( Request::i()->visitDateEnd ) );
		}

		$groupOptions = array_combine( array_keys( Group::groups( TRUE, FALSE ) ), array_map( function( $_group ) { return (string) $_group; }, Group::groups( TRUE, FALSE ) ) );

		if( isset( Request::i()->visitGroups ) )
		{
			$defaultGroups = explode( ',', Request::i()->visitGroups );
		}
		else
		{
			$defaultGroups = array_keys( $groupOptions );
		}

		$form = new Form( 'visits', 'continue' );
		$form->add( new DateRange( 'date', $defaults, TRUE ) );
		$form->add( new CheckboxSet( 'groups', $defaultGroups, FALSE, array( 'options' => $groupOptions ), NULL, NULL, NULL, 'group_filters' ) );

		if( $values = $form->values() )
		{
			/* Determine start and end time */
			$startTime	= $values['date']['start']->getTimestamp();
			$endTime	= $values['date']['end']->getTimestamp();

			$start		= $values['date']['start']->html();
			$end		= $values['date']['end']->html();

			$groups		= ( count( array_diff( array_keys( $groupOptions ), $values['groups'] ) ) ) ? $values['groups'] : NULL;
		}
		else
		{
			/* Determine start and end time */
			$startTime	= $defaults['start']->getTimestamp();
			$endTime	= $defaults['end']->getTimestamp();

			$start		= $defaults['start']->html();
			$end		= $defaults['end']->html();

			$groups		= ( count( array_diff( array_keys( $groupOptions ), $defaultGroups ) ) ) ? $defaultGroups : NULL;
		}

		/* Do we have our date ranges? */
		if( $start AND $end )
		{
			/* Build our where clause */
			$where = array( array( 'last_visit BETWEEN ? AND ?', $startTime, $endTime ) );

			if( $groups !== NULL )
			{
				$where[] = array( '(' . \IPS\Db::i()->in( 'member_group_id', $groups ) . ' OR ' . \IPS\Db::i()->findInSet( 'mgroup_others', $groups ) . ')' );
			}

			/* Get the count */
			$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_members', $where )->first();
			
			/* And now build the table */
			$table = new Db( 'core_members', Request::i()->url()->setQueryString( array( 'visitDateStart' => $startTime, 'visitDateEnd' => $endTime, 'visitGroups' => is_array( $groups ) ? implode( ',', $groups ) : NULL ) ), $where );

			$table->include = array( 'name', 'email', 'last_visit', 'group_name', 'ip_address' );
			$table->mainColumn = 'name';
			$table->langPrefix = 'visits_';
			$table->rowClasses = array( 'email' => array( 'ipsTable_wrap' ), 'group_name' => array( 'ipsTable_wrap' ) );

			/* Default sort options */
			$table->sortBy = $table->sortBy ?: 'last_visit';
			$table->sortDirection = $table->sortDirection ?: 'desc';
			
			/* Custom parsers */
			$table->parsers = array(
				'name'			=> function( $val, $row )
				{
					$member = Member::constructFromData( $row );
					return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
				},
				'email'				=> function( $val )
				{
					return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberEmailCell( htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) );
				},
				'last_visit'				=> function( $val )
				{
					return DateTime::ts( $val )->html();
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
				'ip_address'	=> function( $val )
				{
					if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) )
					{
						return "<a href='" . Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
					}
					return $val;
				},
			);

			$table->extraHtml = Theme::i()->getTemplate( 'stats' )->tableheader( $start, $end, $count, "member_visits_results" );
		}

		$formHtml = $form->customTemplate( array( Theme::i()->getTemplate( 'stats' ), 'filtersFormTemplate' ) );

		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_stats.js', 'core' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_membervisits');
		Output::i()->output = Theme::i()->getTemplate( 'stats' )->membervisits( $formHtml, $count, $table );
	}
}