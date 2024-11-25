<?php
/**
 * @brief		stream Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Nov 2019
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content;
use IPS\core\Stream as StreamClass;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\PermissionCache;
use function count;
use function defined;
use function in_array;
use function is_array;
use function strstr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * stream Widget
 */
class stream extends PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'stream';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
		

	
	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init(): void
	{
		parent::init(); //outputCss
		
		/* Necessary CSS/JS */
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_streams.js', 'core' ) );
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/streams.css' ) );
		
		$apps = array();
		if( isset( $this->configuration['content'] ) and strstr( $this->configuration['content'], ',' ) )
		{
			foreach( explode( ',', $this->configuration['content'] ) as $content )
			{
				$class = explode( '\\', $content );
				$apps[] = $class[1];
			}
		}
		
		/* We will need specific CSS */
		foreach( Application::enabledApplications() as $appDir => $app )
		{
			if ( isset( $this->configuration['content'] ) and $this->configuration['content'] === 0 or in_array( $appDir, $apps ) )
			{
				if ( method_exists( $app, 'outputCss' ) )
				{
					$app::outputCss();
				}
			}
		}
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
		$options = array();
		foreach( Content\Search\SearchContent::searchableClasses() as $class )
		{
			$options[ $class ] = Member::loggedIn()->language()->addToStack( $class::$title . '_pl' );
		}
		$form->add( new Text( 'title', $this->configuration['title'] ?? NULL, TRUE ) );
		$form->add( new Select( 'content', ( isset( $this->configuration['content'] ) AND $this->configuration['content'] !== 0 ) ? explode( ',', $this->configuration['content'] ) : 0, FALSE, array(
			'options'	=> $options,
			'multiple'	=> TRUE,
			'unlimited'	=> 0,
		) ) );

		$dateField = new Select( 'date', ( isset( $this->configuration['date'] ) ) ? $this->configuration['date'] : 'year', FALSE, array(
			'options' => array(
				'today'		=> 'search_day',
				'week'		=> 'search_week',
				'month'		=> 'search_month',
				'year'		=> 'search_year'
			)
		) );
		$dateField->label = Member::loggedIn()->language()->addToStack( 'stream_date_relative_days' );
		$form->add( $dateField );

		$form->add( new Number( 'max_results', $this->configuration['max_results'] ?? 5, TRUE, array( 'max' => 20 ) ) );
		return $form;
	} 
	
	 /**
	 * Ran before saving widget configuration
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function preConfig( array $values ): array
	{
		if ( is_array( $values['content'] ) )
		{
			$save = [];
			foreach( $values['content'] AS $class )
			{
				$save[] = $class;
			}
			$values['content'] = implode( ',', $save );
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
		if ( !isset( $this->configuration['content'] ) )
		{
			return '';
		}
		
		/* Set our content */
		if ( $this->configuration['content'] === 0 )
		{
			$stream = StreamClass::allActivityStream();
		}
		else
		{
			$stream = new StreamClass;
			$stream->classes = $this->configuration['content'];
		}
		
		/* Set our date range */
		$stream->date_type			= 'relative';
		$stream->date_relative_days = 365;
		if ( isset( $this->configuration['date'] ) )
		{
			switch( $this->configuration['date'] )
			{
				case 'today':
					$stream->date_relative_days = 1;
					break;
				
				case 'week':
					$stream->date_relative_days = 7;
					break;
				
				case 'month':
					$stream->date_relative_days = 30;
					break;
				
				case 'year':
				default:
					$stream->date_relative_days = 365;
					break;
			}
		}
		
		/* Set some defaults */
		$stream->id					= 0;
		$stream->include_comments	= TRUE;
		$stream->baseUrl			= Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' );
		
		/* Query */
		$query		= $stream->query()->setLimit( $this->configuration['max_results'] ?? 5 );
		$results	= $query->search();
		
		if ( !count( $results ) )
		{
			return '';
		}

		return $this->output( $stream, $results, $this->configuration['title'] ?? Member::loggedIn()->language()->addToStack( 'block_stream' ), $this->orientation );
	}
}