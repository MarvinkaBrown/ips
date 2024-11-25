<?php
/**
 * @brief		topSubmitters Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		09 Jan 2014
 */

namespace IPS\downloads\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * topSubmitters Widget
 */
class topSubmitters extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'topSubmitters';
	
	/**
	 * @brief	App
	 */
	public string $app = 'downloads';
		


	/**
	* Init the widget
	*
	* @return	void
	*/
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'downloads', 'front' ) );

		parent::init();
	}
		
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );
 		
		$form->add( new Number( 'number_to_show', $this->configuration['number_to_show'] ?? 5, TRUE ) );
		return $form;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		foreach ( array( 'week' => 'P1W', 'month' => 'P1M', 'year' => 'P1Y', 'all' => NULL ) as $time => $interval )
		{
			/* What's the time period we care about? */
			$intervalWhere = NULL;
			if ( $interval )
			{
				$intervalWhere = array( 'file_submitted>?', DateTime::create()->sub( new DateInterval( $interval ) )->getTimestamp() );
			}
			
			/* Get the submitters ordered by their rating */
			$where = array( array( 'file_submitter != ? and file_rating > ? AND file_open = 1', 0, 0 ) );
			if ( $interval )
			{
				$where[] = $intervalWhere;
			}
			$ratings = iterator_to_array( Db::i()->select(
				'downloads_files.file_submitter, AVG(file_rating) as rating, count(file_id) AS files',
				'downloads_files',
				$where,
				'files DESC, rating DESC',
				$this->configuration['number_to_show'] ?? 5, 'file_submitter'
			)->setKeyField('file_submitter')->setValueField('rating') );
			
			${$time} = array();

			if( count( $ratings ) )
			{
				/* Get their file counts */
				$where = array( array( Db::i()->in( 'file_submitter', array_keys( $ratings ) ) ) );
				if ( $interval )
				{
					$where[] = $intervalWhere;
				}
				
				$fileCounts = iterator_to_array( Db::i()->select( 'file_submitter, count(*) AS count', 'downloads_files', $where, NULL, NULL, 'file_submitter' )->setKeyField('file_submitter')->setValueField('count') );
							
				/* Get member data and put it together */
				foreach( Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', array_keys( $ratings ) ) ) as $key => $memberData )
				{
					${$time}[$key]['member'] = Member::constructFromData( $memberData );
					${$time}[$key]['files']  = $fileCounts[$memberData['member_id']] ?? 0;
					${$time}[$key]['rating'] = $ratings[ $memberData['member_id'] ];
				}
			}
		}

		return $this->output( $week, $month, $year, $all );
	}
}