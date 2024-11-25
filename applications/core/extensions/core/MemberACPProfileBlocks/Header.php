<?php
/**
 * @brief		ACP Member Profile: Header Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\core\MemberACPProfile\Block;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Header Block
 */
class Header extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		/* Sparkline */
		$rows = array();
		$cutoff = DateTime::create()->setTime( 0, 0, 0 )->sub( new DateInterval('P30D') );
		$date = clone $cutoff;
		while ( $date->getTimestamp() < time() )
		{
			$rows[ $date->format( 'Y-n-j' ) ] = 0;
			$date->add( new DateInterval( 'P1D' ) );
		}
		$sparkline = new Chart;
		$classes = array();
		foreach ( Application::allExtensions( 'core', 'ContentRouter' ) as $contentRouter )
		{
			foreach ( $contentRouter->classes as $class )
			{
				$classes[] = $class;
				if ( isset( $class::$commentClass ) )
				{
					$classes[] = $class::$commentClass;
				}
				if ( isset( $class::$reviewClass ) )
				{
					$classes[] = $class::$reviewClass;
				}
			}
		}
		foreach ( $classes as $class )
		{
			if( !isset( $class::$databaseColumnMap['author'] ) )
			{
				continue;
			}
			$dateColumn = $class::$databasePrefix . ( $class::$databaseColumnMap['updated'] ?? $class::$databaseColumnMap['date'] );
			$authorColumn = $class::$databasePrefix . $class::$databaseColumnMap['author'];
			foreach ( Db::i()->select( "COUNT(*) AS count, DATE_FORMAT( FROM_UNIXTIME( IFNULL( {$dateColumn}, 0 ) ), '%Y-%c-%e' ) as time", $class::$databaseTable, array( "{$dateColumn}>? AND {$dateColumn}<? AND {$authorColumn}=?", $cutoff->getTimestamp(), time(), $this->member->member_id ), NULL, NULL, array( $authorColumn, $dateColumn ) ) as $row )
			{
				$rows[ $row['time'] ] += $row['count'];
			}
		}
		$sparkline->addHeader( Member::loggedIn()->language()->addToStack('date'), 'date' );
		$sparkline->addHeader( Member::loggedIn()->language()->addToStack('members_posts'), 'number' );
		foreach ( $rows as $time => $val )
		{
			$datetime = new DateTime;
			$datetime->setTime( 0, 0, 0 );
			$exploded = explode( '-', $time );
			$datetime->setDate( $exploded[0], $exploded[1], $exploded[2] );
			
			$sparkline->addRow( array( $datetime, $val ) );
		}
		$sparkline = $sparkline->render( 'AreaChart', array(
			'areaOpacity'		=> 0.7,
			'backgroundColor'	=> '#edf0f5',
			'colors'			=> array( '#0c849f' ),
			'chartArea'			=> array(
				'left'				=> 0,
				'top'				=> 0,	
				'width'				=> '100%',
				'height'			=> '100%',
			),
			'hAxis'				=> array(
				'baselineColor'		=> '#F3F3F3',
				'gridlines'			=> array(
					'count'				=> 0,
				)
			),
			'height'			=> 60,
			'legend'			=> array(
				'position'			=> 'none',
			),
			'lineWidth'			=> 1,
			'vAxis'				=> array(
				'baselineColor'		=> '#F3F3F3',
				'gridlines'			=> array(
					'count'				=> 0,
				)
			),
		) );
		
		/* Validating */
		$validatingRow = NULL;
		if ( $this->member->members_bitoptions['validating'] )
		{
			try
			{
				$validatingRow = Db::i()->select( '*', 'core_validating', array( 'member_id=?', $this->member->member_id ) )->first();
			}
			catch ( UnderflowException $e ) { }
		}
		
		/* Output */
		return (string) Theme::i()->getTemplate('memberprofile')->header( $this->member, $validatingRow, $sparkline );
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		/* Build Form */
		$form = new Form;
		if ( Request::i()->type == 'content' )
		{
			$form->add( new Number( 'member_content_items', $this->member->member_posts, FALSE ) );
		}
		elseif ( Settings::i()->reputation_enabled and Request::i()->type == 'reputation' )
		{
			$form->add( new Number( 'member_reputation', $this->member->pp_reputation_points, FALSE, array( 'min' => NULL ) ) );
		}
		else
		{
			Output::i()->error( 'node_error', '2C114/U', 404, '' );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if ( Request::i()->type == 'content' )
			{
				$this->member->member_posts = $values['member_content_items'];
				Session::i()->log( 'acplog__members_edited_content', array( $this->member->name => FALSE ) );
			}
			elseif ( Settings::i()->reputation_enabled and Request::i()->type == 'reputation' )
			{
				$this->member->pp_reputation_points = $values['member_reputation'];
				Session::i()->log( 'acplog__members_edited_rep', array( $this->member->name => FALSE ) );
			}
			$this->member->save();
			
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$this->member->member_id}" ), 'saved' );
		}
		
		/* Display */
		return $form;
	}
}