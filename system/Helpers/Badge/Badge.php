<?php

/**
 * @brief        Badge
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/14/2023
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Theme;

if (!defined('\IPS\SUITE_UNIQUE_KEY'))
{
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

class Badge
{
	/* Constants for frequently used badge classes */
	const BADGE_POSITIVE = 'ipsBadge--positive';
	const BADGE_NEGATIVE = 'ipsBadge--negative';
	const BADGE_WARNING = 'ipsBadge--warning';
	const BADGE_INTERMEDIARY = 'ipsBadge--intermediary';
	const BADGE_NEUTRAL = 'ipsBadge--neutral';

	/**
	 * Main badge class (e.g. ipsBadge--positive, ipsBadge--negative)
	 *
	 * @var string
	 */
	public string $badgeType = '';

	/**
	 * Optional icon to show in the badge
	 *
	 * @var string|null
	 */
	public ?string $icon = null;

	/**
	 * Language string for the badge text
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Badge size
	 *
	 * @var string
	 */
	public string $size = 'medium';

	/**
	 * Additional CSS classes
	 *
	 * @var array
	 */
	public array $additionalClasses = [];

	/**
	 * @param string $badgeType
	 * @param string $name
	 * @param string $size
	 * @param string $icon
	 * @param array $additionalClasses
	 */
	public function __construct( string $badgeType, string $name, string $size='', string $icon = '', array $additionalClasses = [] )
	{
		$this->badgeType = $badgeType;
		$this->name = $name;
		$this->size = $size;
		$this->icon = $icon;
		$this->additionalClasses = $additionalClasses;
	}

	/**
	 * @return string
	 */
	public function __toString() : string
	{
		return Theme::i()->getTemplate( 'global', 'core', 'front' )->badge( $this->badgeType, $this->name, $this->size, $this->icon, $this->additionalClasses );
	}
}