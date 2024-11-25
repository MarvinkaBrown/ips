<?php
/**
 * @brief		Moderator Permissions Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		7 Sept 2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions Extension
 */
abstract class ModeratorPermissionsAbstract
{
	/**
	 * Get Permissions
	 *
	 * @code
	 * return array(
	 * 'key'    => 'YesNo',    // Can just return a string with type
	 * 'key'    => array(    // Or an array for more options
	 * 'YesNo',            // Type
	 * array( ... ),        // Options (as defined by type's class)
	 * 'prefix',            // Prefix
	 * 'suffix',            // Suffix
	 * ),
	 * ...
	 * );
	 * @endcode
	 * @param array $toggles
	 * @return    array
	 */
	abstract public function getPermissions( array $toggles ): array;

	/**
	 * Pre-save
	 *
	 * @note	This can be used to adjust the values submitted on the form prior to saving
	 * @param	array	$values		The submitted form values
	 * @return	void
	 */
	public function preSave( array &$values ) : void
	{

	}

	/**
	 * After change
	 *
	 * @param	array	$moderator	The moderator
	 * @param	array	$changed	Values that were changed
	 * @return	void
	 */
	public function onChange( array $moderator, array $changed ) : void
	{

	}

	/**
	 * After delete
	 *
	 * @param	array	$moderator	The moderator
	 * @return	void
	 */
	public function onDelete( array $moderator ) : void
	{

	}

	/**
	 * Get Content Permission Permissions
	 *
	 * @param	array	$toggles	Toggle data
	 * @code
	return array(
	'key'	=> 'YesNo',	// Can just return a string with type
	'key'	=> array(	// Or an array for more options
	'YesNo',			// Type
	array( ... ),		// Options (as defined by type's class)
	'prefix',			// Prefix
	'suffix',			// Suffix
	),
	...
	);
	 * @endcode
	 * @return	array
	 */
	public function getContentPermissions( array $toggles ): array
	{
		return array();
	}

	/**
	 * After Content Permission change
	 *
	 * @param	array	$moderator	The moderator
	 * @param	array	$changed	Values that were changed
	 * @return	void
	 */
	public function onContentChange( array $moderator, array $changed ) : void
	{

	}

	/**
	 * After Content Permission change
	 *
	 * @param	array	$moderator	The moderator
	 * @return	void
	 */
	public function onContentDelete( array $moderator ) : void
	{

	}
}