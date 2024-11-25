<?php

/**
 * @brief        Icon
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/14/2023
 */

namespace IPS\Helpers\Badge;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Badge;
use IPS\Theme;

if (!defined('\IPS\SUITE_UNIQUE_KEY'))
{
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

class Icon extends Badge
{
	/**
	 * @param string $badgeType
	 * @param string $name
	 * @param string $size
	 * @param string $icon
	 * @param array $additionalClasses
	 */
	public function __construct( string $badgeType, string $icon, string $name = '', string $size='', array $additionalClasses = [] )
	{
		/* Icon is required here and name is not */
		parent::__construct( $badgeType, $name, $size, $icon, $additionalClasses );
	}

	/**
	 * @return string
	 */
	public function __toString() : string
	{
		return Theme::i()->getTemplate( 'global', 'core', 'front' )->badgeIcon( $this->badgeType, $this->icon, $this->size, $this->name, $this->additionalClasses );
	}
}