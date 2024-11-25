<?php
/**
 * @brief		Custom input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Helpers\Form;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom input class for Form Builder
 */
class Custom extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'getHtml'		=> function(){...}	// Function to get output
	 		'formatValue'	=> function(){...}	// Function to format value
	 		'validate'		=> function(){...}	// Function to validate
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'getHtml'		=> NULL,
		'formatValue'	=> NULL,
		'validate'		=> NULL,
	);

	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$htmlFunction = $this->options['getHtml'];
		return $htmlFunction( $this );
	}
	
	/**
	 * Get HTML
	 *
	 * @param	Form|null	$form	Form helper object
	 * @return	string
	 */
	public function rowHtml( Form $form=NULL ): string
	{
		if ( isset( $this->options['rowHtml'] ) )
		{
			$htmlFunction = $this->options['rowHtml'];
			return $htmlFunction( $this, $form );
		}
		return parent::rowHtml( $form );
	}
	
	/** 
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue(): mixed
	{
		if ( $this->options['formatValue'] !== NULL )
		{
			$formatValueFunction = $this->options['formatValue'];
			return $formatValueFunction( $this );
		}
		else
		{
			return parent::formatValue();
		}
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{		
		parent::validate();
		
		if ( $this->options['validate'] )
		{
			$validationFunction = $this->options['validate'];
			$validationFunction( $this );
		}

		return TRUE;
	}
}