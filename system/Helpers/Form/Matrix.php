<?php
/**
 * @brief		Matrix Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Feb 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Helpers\Form;
use IPS\IPS;
use IPS\Login;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use Throwable;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_object;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Matrix Builder
 * @code
$matrix = new \IPS\Helpers\Form\Matrix;

$matrix->columns = array(
	'foo'	=> function( $key, $value, $data )
	{
		return new \IPS\Helpers\Form\Text( $key, $value );
	},
	//...
);

$matrix->rows = array(
	0	=> array(
		'foo'	=> TRUE,
		// ...
	),
	// ...
)

if ( $values = $matrix->values() )
{

}

\IPS\Output::i()->output = $matrix;
 * @endcode
 */
class Matrix extends Form
{
	/**
	 * @brief	Input Elements array
	 */
	public array $elements = array();
	
	/**
	 * @brief	Columns array
	 */
	public array $columns = array();
	
	/**
	 * @brief	Widths
	 */
	public array $widths = array();
	
	/**
	 * @brief	Columns to have "check all" checkboxes
	 */
	public array $checkAlls = array();
	
	/**
	 * @brief	Should rows have all/none toggles?
	 */
	public bool $checkAllRows = FALSE;
	
	/**
	 * @brief	Rows array
	 */
	public array $rows = array();
	
	/**
	 * @brief	Manageable? (Rows can be added and deleted)
	 */
	public bool $manageable = TRUE;
	
	/**
	 * @brief	Sortable?
	 */
	public bool $sortable = FALSE;

	/**
	 * @brief	Squash fields? (values in the matrix are json_encode'd as a single value to get around max_post_vars limits)
	 */
	public bool $squashFields = TRUE;
	
	/**
	 * @brief	Added rows
	 * @see        Matrix::values
	 */
	public array $addedRows = array();
	
	/**
	 * @brief	Changed rows
	 * @see        Matrix::values
	 */
	public array $changedRows = array();
	
	/**
	 * @brief	Removed rows
	 * @see        Matrix::elements
	 */
	public array $removedRows = array();
	
	/**
	 * @brief	Prefix to add to the language keys used for column headers
	 */
	public string $langPrefix = '';

	/**
	 * @brief	Classnames to add to the table within the matrix
	 */
	public array $classes = array();

	/**
	 * @brief	Show tooltips in each cell?
	 */
	public bool $showTooltips = FALSE;

	/**
	 * @brief	Form ID, Set if Matrix is part of a form
	 */
	public ?string $formId = NULL;

	/**
	 * @brief Determines whether the Row Titles are processed as raw html instead of a language string. Not needed if $langPrefix is set
	 */
	public bool $styledRowTitle = FALSE;
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		try
		{			
			return Theme::i()->getTemplate( 'forms', 'core', 'global' )->matrix( $this->id, array_keys( $this->columns ), $this->elements(), $this->action, $this->hiddenValues, $this->actionButtons, $this->langPrefix, $this->calculateWidths(), $this->manageable, $this->checkAlls, $this->checkAllRows, $this->classes, $this->showTooltips, $this->squashFields, $this->sortable, $this->styledRowTitle );
		}
		catch ( Exception|Throwable $e )
		{
			IPS::exceptionHandler( $e );
			return '';
		}
	}
	
	/**
	 * Get Nested HTML
	 *
	 * @return	string
	 */
	public function nested(): string
	{		
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->matrixNested( $this->id, array_keys( $this->columns ), $this->elements(), $this->action, $this->hiddenValues, $this->actionButtons, $this->langPrefix, $this->calculateWidths(), $this->manageable, $this->checkAlls, $this->checkAllRows, $this->classes, $this->showTooltips, $this->squashFields, $this->sortable, $this->styledRowTitle );
	}
	
	/**
	 * Calculate widths
	 *
	 * @return	array
	 */
	protected function calculateWidths(): array
	{
		$widths = $this->widths;
		$width = ( ( 100 - array_sum( $this->widths ) ) / count( $this->columns ) );
		foreach ( array_keys( $this->columns ) as $c )
		{
			if ( !isset( $widths[ $c ] ) )
			{
				$widths[ $c ] = number_format( $width, 2, '.', '' );
			}
		}
				
		return $widths;
	}

	/**
	 * Get elements
	 *
	 * @param bool $getValues Get values?
	 * @return array|null
	 */
	public function elements( bool $getValues=TRUE ): ?array
	{
		if( !count( $this->elements ) )
		{
			/* Stuff about our form */
			$name = "{$this->id}_submitted";
			$formName = $this->formId ? "{$this->formId}_submitted" : NULL;
			$matrixName = "{$this->id}_matrixRows";
			$matrixValues = Request::i()->$matrixName;

			/* Loop our defined rows */
			$this->elements = array();
			foreach ( $this->rows as $rowId => $data )
			{
				/* Have we deleted this row? */
				$deleteKey = "{$rowId}_delete";
				if ( $this->manageable and ( isset( Request::i()->$name ) or ( $formName and isset( Request::i()->$formName ) ) ) and ( isset( Request::i()->$deleteKey ) or !isset( $matrixValues[ $rowId ] ) or !$matrixValues[ $rowId ] ) )
				{
					$this->removedRows[] = $rowId;
					continue;
				}
				
				/* Build the row */
				$this->elements[ $rowId ] = $this->buildRow( $rowId, $data );
			}
						
			/* Create blank row */
			if ( $this->manageable )
			{
				$blankRow = $this->buildRow( "_new_[x]", NULL );
			}
						
			/* Look for added ones */
			if ( isset( Request::i()->_new_ ) )
			{
				$i = 1;
				foreach ( Request::i()->_new_ as $newId => $data )
				{
					$added = TRUE;
					if ( $newId === 'x_unlimited' )
					{
						continue;
					}
					elseif ( $newId === 'x' )
					{
						$added = FALSE;
						foreach ( $data as $k => $v )
						{
							if ( isset( $blankRow[$k] ) and $v and $v !== $blankRow[$k]->value )
							{
								$added = TRUE;
								$blankRow[$k] = $blankRow[$k]->getValue();
							}
						}
					}
					
					if ( $added )
					{
						/* Select lists can have user-supplied input, so look for that too */
						foreach( Request::i() as $inputKey => $inputValue )
						{
							if( $pos = mb_strpos( $inputKey, '_new_' ) AND $inputKey !== '_new_' )
							{
								if( is_array( $inputValue ) AND isset( $inputValue[ $newId ] ) )
								{
									/* @note This previously used an array_merge() however this was causing elements to be overwritten with a blank value in some cases */
									foreach( $inputValue[ $newId ] AS $key => $value )
									{
										if ( $value )
										{
											$data[ $key ] = $value;
										}
									}
								}
							}
						}

						$this->elements["_new_[{$newId}]"] = $this->buildRow( "_new_[{$newId}]", $data );
						$this->addedRows[] = "_new_[{$newId}]";
						$i++;
					}
				}
			}
									
			/* Add blank one */
			if ( $this->manageable and $getValues )
			{
				$this->elements["_new_[x]"] = $blankRow;
			}
			
			/* Do we need to order it? */
			if ( $this->sortable )
			{
				$matrixOrderName = "{$this->id}_matrixOrder";
				if  ( isset( Request::i()->$matrixOrderName ) )
				{
					$matrixOrderValues = Request::i()->$matrixOrderName;
					
					uksort( $this->elements, function( $a, $b ) use ( $matrixOrderValues ) {
						if ( in_array( $a, $matrixOrderValues ) and in_array( $b, $matrixOrderValues ) )
						{
							return array_search( $a, $matrixOrderValues ) - array_search( $b, $matrixOrderValues );
						}
						return 0;
					} );
				}
			}
		}

		return $this->elements;
	}

	/**
	 * Build Row
	 *
	 * @param mixed $rowId Row identifier
	 * @param mixed $data Values
	 * @return array|string
	 */
	protected function buildRow( mixed $rowId, mixed $data ): array|string
	{
		$row = array();
		if ( is_string( $data ) )
		{
			return $data;
		}
		foreach ( $this->columns as $columnName => $columnData )
		{
			$inputName = "{$rowId}[{$columnName}]";
				
			/* Create using array */
			if ( is_array( $columnData ) )
			{
				$classname = '\IPS\Helpers\Form\\' . $columnData[0];
				$row[ $columnName ] = new $classname(
					$inputName,
					$data[$columnName] ?? ( $columnData[1] ?? NULL ),
					$columnData[2] ?? FALSE,
					$columnData[3] ?? array()
					);
			}
			/* Create using callback function */
			else
			{
				$row[ $columnName ] = $columnData( $inputName, $data[$columnName] ?? NULL, $data );
			}
		}
		if ( isset( $data['_level'] ) )
		{
			$row['_level'] = $data['_level'];
		}

		return $row;
	}
	
	/**
	 * Get submitted values
	 *
	 * @param bool $stringValues	If true, wil not check if form was submitted (used for nested matrixes)
	 * @return	array|FALSE		Array of field values or FALSE if the form has not been submitted or if there were validation errors
	 */
	public function values( bool $stringValues=FALSE ): array|false
	{
		$values = array();
				
		$name = "{$this->id}_submitted";		
		if( $stringValues or ( isset( Request::i()->$name ) and Login::compareHashes( Session::i()->csrfKey, (string) Request::i()->csrfKey ) ) )
		{
			// Do we need to unsquash any values?
			// Squashed values are json_encoded by javascript to prevent us exceeding max_post_vars
			$squashedField = $this->id . '_squashed';
			
			// If 'squashedField' isn't in the request it might indicate the user didn't have JS enabled
			if ( $this->squashFields && isset( Request::i()->$squashedField ) )
			{
				if ( isset( Request::i()->$squashedField ) )
				{
					$unsquashed = json_decode( Request::i()->$squashedField, TRUE );
					
					foreach( $unsquashed as $key => $value )
					{
						Request::i()->$key = $value;
					}
				}
			}

			foreach ( $this->elements( FALSE ) as $rowId => $columns )
			{
				if ( is_array( $columns ) )
				{
					foreach ( $columns as $columnName => $element )
					{
						/* If this was a ehader or something, skip it */
						if ( !is_object( $element ) )
						{
							continue;
						}
						
						/* Return FALSE on error */
						if( $element->error !== NULL )
						{
							return FALSE;
						}
						
						/* If the "check all" box was checked, set it to TRUE */
						if ( $element instanceof Checkbox and isset( Request::i()->__all[ $columnName ] ) and ! $element->options['disabled'] )
						{
							$element->value = TRUE;
						}
						
						/* If our element has an unlimited option, and that's set, set that. */
						if ( isset( $element->options['unlimited'] ) )
						{
							$unlimitedKey = "{$rowId}[{$columnName}_unlimited]";
							$value = Request::i()->valueFromArray( $unlimitedKey );
							if ( $value !== NULL )
							{
								$element->value = $value;
							}
						}

						if ( isset( $element->options['nullLang'] ) )
						{
							$nullKey = "{$rowId}[{$columnName}_null]";
							if ( $value = Request::i()->valueFromArray( $nullKey ) )
							{
								$element->value = NULL;
							}
						}
																				
						/* Set value */
						$values[ $rowId ][ $columnName ] = $element->value;
						
						/* Not if it's changed */
						if ( $element->value !== $element->defaultValue and mb_substr( $rowId, 0, 5 ) !== '_new_' )
						{					
							$this->changedRows[ $rowId ] = $rowId;
						}
					}
				}
			}
				
			return $values;
		}
		
		return FALSE;
	}
}