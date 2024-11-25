<?php
/**
 * @brief		Chart Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Jul 2013
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\DateTime;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use LengthException;
use LogicException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_bool;
use function is_null;
use function is_numeric;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Chart Helper
 *
 * @code
	$chart = new \IPS\Helpers\Chart;
	
	$chart->addHeader( "Year", 'string' );
	$chart->addHeader( "Sales", 'number' );
	$chart->addHeader( "Expenses", 'number' );
			
	$chart->addRow( array( '2004', 1000, 400 ) );
	$chart->addRow( array( '2005', 1170, 460 ) );
	$chart->addRow( array( '2006', 660, 1120 ) );
	$chart->addRow( array( '2007', 1030, 540 ) );
	
	\IPS\Output::i()->output = $chart->render( 'PieChart', array(
		'title'	=> "Cash Flow",
		'is3D'	=> TRUE
	) );
 * @endcode
 */
class Chart
{
	/**
	 * @brief	Headers
	 * @see		addHeader();
	 */
	public array $headers = array();
	
	/**
	 * @brief	Rows
	 * @see		addHeader();
	 */
	public array $rows = array();
	
	/**
	 * @brief	Google Charts will assume numbers can be negative, which can produce graphs showing negative data points if no data is provided. The default baheviour is to set the minimum value to 0. Change this property if the chart should be able to show negative number values.
	 */
	public bool $numbersCanBeNegative = FALSE;

	/**
	 * Add Header
	 *
	 * @param string $label	Label
	 * @param string $type	Type of value
	 *	@li	string
	 *	@li	number
	 *	@li	boolean
	 *	@li	date
	 *	@li	datetime
	 *	@li	timeofday
	 * @return	void
	 */
	public function addHeader( string $label, string $type ) : void
	{
		$this->headers[] = array( 'label' => $label, 'type' => $type );
	}
	
	/**
	 * Add Row
	 *
	 * @param array $values	Values, in the order that headers were added
	 * @return	void
	 * @throws	LogicException
	 */
	public function addRow( array $values ) : void
	{
		if ( count( $values ) !== count( $this->headers ) )
		{
			throw new LengthException('COLUMN_COUNT_MISMATCH');
		}
		
		$i = 0;
		$values = array_values( $values );
		foreach ( $this->headers as $data )
		{
			$value = is_array( $values[ $i ] ) ? $values[ $i ]['value'] : $values[ $i ];
			
			switch ( $data['type'] )
			{
				case 'string':
					if ( !is_string( $value ) )
					{
						throw new InvalidArgumentException( "VALUE_{$i}_NOT_STRING" );
					}
					break;
				
				case 'number':
					if ( !is_numeric( $value ) and !is_null( $value ) )
					{
						throw new InvalidArgumentException( "VALUE_{$i}_NOT_NUMBER" );
					}
					break;
					
				case 'bool':
					if ( !is_bool( $value ) )
					{
						throw new InvalidArgumentException( "VALUE_{$i}_NOT_BOOL" );
					}
					break;
					
				case 'date':
				case 'datetime':
				case 'timeofday':
					if ( !( $value instanceof DateTime ) )
					{
						throw new InvalidArgumentException( "VALUE_{$i}_NOT_DATETIME" );
					}
					
					if ( is_array( $values[ $i ] ) )
					{
						$values[ $i ]['value'] = $value->rfc3339();
					}
					else
					{
						$values[ $i ] = $value->rfc3339();
					}
					break;
			}
			$i++;
		}
				
		$this->rows[] = $values;
	}
	
	/**
	 * Render
	 *
	 * @param	string $type		Type
	 * @param array $options	Options
	 * @param string|null $format		Value for number formatter
	 * @return	string
	 *@see		<a href='https://google-developers.appspot.com/chart/interactive/docs/gallery'>Charts Gallery - Google Charts - Google Developers</a>
	 */
	public function render( string $type, array $options=array(), string $format=NULL ): string
	{
		if ( !Request::i()->isAjax() )
		{
			Output::i()->jsFiles[] = 'https://www.gstatic.com/charts/loader.js';
			Output::i()->headJs .= "google.charts.load( '47', { 'packages':['corechart'] } );";
		}
		
		if ( !$this->numbersCanBeNegative and in_array( $type, array( 'LineChart', 'ColumnChart' ) ) and !isset( $options['vAxis']['viewWindow']['min'] ) )
		{
			$options['vAxis']['viewWindow']['min'] = 0;
		}
				
		return Theme::i()->getTemplate( 'global', 'core', 'global' )->chart( $this, $type, json_encode( $options ), $format );
	}
}