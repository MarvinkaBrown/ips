<?php
/**
 * @brief		blogs Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		13 Jul 2015
 */

namespace IPS\blog\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use OutOfRangeException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * blogs Widget
 */
class blogs extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'blogs';
	
	/**
	 * @brief	App
	 */
	public string $app = 'blog';
	
	/**
	 * Initialize this widget
	 *
	 * @return	void
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'blog.css', 'blog' ) );
		
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

		/* Block title */
		$form->add( new Text( 'widget_feed_title', $this->configuration['widget_feed_title'] ?? Member::loggedIn()->language()->addToStack('blogs')) );

		/* Author */
		$author = NULL;
		try
		{
			if ( isset( $this->configuration['widget_feed_author'] ) and is_array( $this->configuration['widget_feed_author'] ) )
			{
				foreach( $this->configuration['widget_feed_author']  as $id )
				{
					$author[ $id ] = Member::load( $id );
				}
			}
		}
		catch( OutOfRangeException ) { }
		$form->add( new Form\Member( 'widget_feed_author', $author, FALSE, array( 'multiple' => null ) ) );



		$form->add( new Select( 'widget_blog_last_date', $this->configuration['widget_blog_last_date'] ?? '0', FALSE, array(
			'options' => array(
				0	=> 'show_all',
				1	=> 'today',
				5	=> 'last_5_days',
				7	=> 'last_7_days',
				10	=> 'last_10_days',
				15	=> 'last_15_days',
				20	=> 'last_20_days',
				25	=> 'last_25_days',
				30	=> 'last_30_days',
				60	=> 'last_60_days',
				90	=> 'last_90_days',
			)
		) ) );


		/* Number to show */
		$form->add( new Number( 'widget_feed_show', $this->configuration['widget_feed_show'] ?? 5, TRUE ) );

		$form->add( new Select( 'widget_feed_sort_on', $this->configuration['widget_feed_sort_on'] ?? 'blog_last_edate', FALSE, array( 'options' => array(
			'blog_last_edate'		=> 'widget__blog_edate',
			'blog_count_entries'  	=> 'widget__blog_count_entries',
			'blog_count_comments' 	=> 'widget__blog_count_comments'
		) ), NULL, NULL, NULL, 'widget_feed_sort_on' ) );

		$form->add( new Select( 'widget_feed_sort_dir', $this->configuration['widget_feed_sort_dir'] ?? 'desc', FALSE, array(
			'options' => array(
				'desc'   => 'descending',
				'asc'    => 'ascending'
			)
		) ) );
		return $form;
	}

	/**
	 * Ran before saving widget configuration
	 *
	 * @param array $values	Values from form
	 * @return	array
	 */
	public function preConfig( array $values ): array
	{
		if ( is_array( $values['widget_feed_author'] ) )
		{
			$members = array();
			foreach( $values['widget_feed_author'] as $member )
			{
				$members[] = $member->member_id;
			}

			$values['widget_feed_author'] = $members;
		}

		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$where  = array( array( 'blog_disabled=0 AND blog_social_group IS NULL AND blog_club_id IS NULL' ) );
		$sortBy = ( isset( $this->configuration['widget_feed_sort_on'] ) and isset( $this->configuration['widget_feed_sort_dir'] ) ) ? ( $this->configuration['widget_feed_sort_on'] . ' ' . $this->configuration['widget_feed_sort_dir'] ) : NULL;
		$limit  = $this->configuration['widget_feed_show'] ?? 5;

		if ( isset( $this->configuration['widget_feed_author'] ) and is_array( $this->configuration['widget_feed_author'] ) and count( $this->configuration['widget_feed_author'] ) )
		{
			$where[] = array( Db::i()->in( 'blog_member_id', $this->configuration['widget_feed_author'] ) );
		}

		/* Get results */
		$blogs = array();

		foreach( Db::i()->select( '*', 'blog_blogs', $where, $sortBy, array( 0, $limit ) ) as $row )
		{
			$blog = Blog::constructFromData( $row );
			$blog->coverPhoto()->editable = false;
			$blogs[] = $blog;
		}

		if ( count( $blogs ) )
		{
			return $this->output( $blogs, $this->configuration['widget_feed_title'] ?? Member::loggedIn()->language()->addToStack('blogs'));
		}
		else
		{
			return '';
		}

	}
}