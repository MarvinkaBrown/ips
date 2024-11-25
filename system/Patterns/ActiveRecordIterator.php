<?php
/**
 * @brief		ActiveRecord IteratorIterator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Nov 2013
 */

namespace IPS\Patterns;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Countable;
use IteratorIterator;
use Traversable;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ActiveRecord IteratorIterator
 */
class ActiveRecordIterator extends IteratorIterator implements Countable
{
	/**
	 * @brief	Classname
	 */
	public string $classname;

	/**
	 * @brief	Parent classname
	 */
	public mixed $parentClassname = NULL;
		
	/**
	 * Constructor
	 *
	 * @param	Traversable	$iterator			The iterator
	 * @param	string			$classname			The classname
	 * @param string|null $parentClassname		Parent classname, if the parent data is also available (e.g. joined in a query)
	 * @return	void
	 */
	public function __construct(Traversable $iterator, $classname, string $parentClassname = NULL )
	{
		$this->classname		= $classname;
		$this->parentClassname	= $parentClassname;

		parent::__construct( $iterator );
	}
	
	/**
	 * Get current
	 *
	 * @return    ActiveRecord
	 */
	public function current(): ActiveRecord
	{
		$current = parent::current();

		/* This is here purely to prime caches to prevent queries later. If we've already joined (or otherwise have) the data for another class, then we can construct
			an active record object of it in memory to prevent calls to load() against that class resulting in separate queries later. */
		if( $this->parentClassname !== NULL )
		{
			$parentClass = $this->parentClassname;

			/* We need to make sure we actually have the data first though */
			$idColumn = $parentClass::$databasePrefix . $parentClass::$databaseColumnId;

			if( isset( $current[ $idColumn ] ) )
			{
				$parentClass::constructFromData( $current );
			}
		}

		/* @var ActiveRecord $classname */
		$classname = $this->classname;
		return $classname::constructFromData( $current );
	}
	
	/**
	 * Get count
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) $this->getInnerIterator()->count();
	}
}