<?php

/**
 * @brief        ContentListenerType
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/23/2023
 */

namespace IPS\Events\ListenerType;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Events\ListenerType;
use IPS\Node\Model;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

/**
 * @method onLoad( Model $node ) : void
 * @method onDelete( Model $node ) : void
 */
class NodeListenerType extends ListenerType
{
	/**
	 * @brief	Determine whether this listener requires an explicitly set class
	 * 			Example: MemberListeners are always for \IPS\Member, but ContentListeners
	 * 			will require a specific class.
	 * @var bool
	 */
	public static bool $requiresClassDeclaration = TRUE;

	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array(
		Model::class
	);
}