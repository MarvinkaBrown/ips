<?php
/**
 * @brief		Item selector for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Apr 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Db;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/* @todo limit by container IDs */

/**
 * Content Item selector
 */
class Item extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'class'				=> '\IPS\core\Foo',				// The \Content\Item class
	 		'permissionCheck'	=> 'read',						// If a permission key is provided, only content items that the member has that permission for will be available.
	 		'maxItems'			=> NULL,						// Maximum items allowed to select, or NULL for unlimited
	        'orderResults'      => NULL|FALSE|array             // NULL for default date ordering, FALSE for no ordering, or array( field, 'asc' ) where field is a mappable content item field (date, title, etc)
	        'itemTemplate'      => NULL                         // NULL or array of template group and template name array( \IPS\Theme::i()->getTemplate( 'group', 'app', 'location'), 'name' )
			'containerIds'      => array                        // Array of container IDs to limit the search
	        'minAjaxLength'     => int                          // Number of characters to type before selector appears
	 *      'where'             => array                        // Array of additional where array( array( 'foo=?', 1 ) )
	 * );
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'class'				=> NULL,
		'permissionCheck'   => 'read',
		'maxItems'      	=> NULL,
		'orderResults'      => NULL,
		'itemTemplate'      => NULL,
		'containerIds'      => array(),
		'minAjaxLength'     => 3,
		'where'             => array()
	);

	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		/* Display */
		$url = Request::i()->url()->setQueryString( '_itemSelectName', $this->name );

		$template = NULL;
		if ( $this->options['itemTemplate'] === NULL )
		{
			$template = array( Theme::i()->getTemplate( 'forms', 'core', 'global' ), 'itemResult' );
		}
		else
		{
			$template = $this->options['itemTemplate'];
		}

		/* Are we getting some AJAX stuff? */
		if ( isset( Request::i()->_itemSelectName ) and Request::i()->_itemSelectName === $this->name )
		{
			/* @var array $databaseColumnMap */
			$results = array();
			$class   = $this->options['class'];
			$field   = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['title'];
			$where   = array( array( $field . " LIKE CONCAT('%', ?, '%')", Request::i()->q ) );
			$idField = $class::$databaseColumnId;
			if ( isset( $class::$databaseColumnMap['container'] ) and is_array( $this->options['containerIds'] ) and count( $this->options['containerIds'] ) )
			{
				$where[] = array( Db::i()->in( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['container'], $this->options['containerIds'] ) );
			}

			if ( isset( $this->options['where'] ) and count( $this->options['where'] ) )
			{
				$where = array_merge( $where, $this->options['where'] );
			}

			foreach( $class::getItemsWithPermission( $where, 'LENGTH(' . $field . ') ASC', array( 0, 20 ), $this->options['permissionCheck'] ) as $item )
			{
				$results[] = array(
					'id'	=> $item->$idField,
					'html'  => $template( $item )
				);
			}

			Output::i()->json( $results );
		}

		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->item( $this->name, $this->value, $this->options['maxItems'], $this->options['minAjaxLength'], $url, $template );
	}

	/**
	 * Get Value
	 *
	 * @return	array
	 */
	public function getValue(): mixed
	{
		$name = $this->name . '_values';
		if ( isset( Request::i()->$name ) )
		{
			return explode( ',', Request::i()->$name );
		}
		else
		{
			return array();
		}
	}

	/**
	 * Format Value
	 *
	 * @return	array|NULL
	 */
	public function formatValue(): mixed
	{
		$itemClass = $this->options['class'];
		$order     = NULL;
		$items     = array();
		$idField = $itemClass::$databaseColumnId;
		if ( ! $this->value )
		{
			$this->value = array();
		}
		else
		{
			/* $this->value may be a single, numeric, ID, so look for that to avoid a Countable error */
			if ( is_numeric( $this->value ) )
			{
				$this->value = array( $this->value );
			}
			else
			{
				$this->value = ( is_string( $this->value ) ) ? explode( ',', $this->value ) : $this->value;
			}
		}

		if ( count( $this->value ) )
		{
			if ( is_array( $this->options['orderResults'] ) )
			{
				if ( isset( $itemClass::$databaseColumnMap[ $this->options['orderResults'][0] ] ) )
				{
					$order = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap[ $this->options['orderResults'][0] ] . ' ' . $this->options['orderResults'][1];
				}
			}

			$where = array( Db::i()->in( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnId, $this->value ) );
			foreach( $itemClass::getItemsWithPermission( array( $where ), $order, NULL, $this->options['permissionCheck'] ) as $item )
			{
				$items[ $item->$idField ] = $item;
			}

			/* If we didn't want to order results, reset the order of results as appropriate */
			if( $this->options['orderResults'] === FALSE )
			{
				$_sortedItems = array();

				foreach( $this->value as $id )
				{
					$_sortedItems[ $id ] = $items[ $id ];
				}

				$items = $_sortedItems;
			}
		}

		$this->value = $items;

		return $this->value;
	}
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return    string|int|null
	 */
	public static function stringValue( mixed $value ): string|int|null
	{
		if ( is_array( $value ) )
		{
			return implode( ',', array_keys( $value ) );
		}
		elseif ( is_object( $value ) )
		{
			return $value->_id;
		}
		return (string) $value;
	}
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		if( empty( $this->value ) and $this->required )
		{
			throw new InvalidArgumentException('form_required');
		}
		
		return parent::validate();
	}
}