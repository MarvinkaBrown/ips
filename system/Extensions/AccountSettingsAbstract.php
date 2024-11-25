<?php

/**
 * @brief        AccountSettingsExtension
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/29/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY'))
{
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

abstract class AccountSettingsAbstract
{
	/**
	 * @var string
	 */
	public static string $icon = 'cog';

	/**
	 * Return the key for the tab, or NULL if it should not be displayed
	 *
	 * @return string|null
	 */
	abstract public function getTab() : string|null;

	/**
	 * Return the content for the main tab
	 *
	 * @return string
	 */
	abstract public function getContent() : string;

	/**
	 * Return the language string that will be used as the title.
	 * Defaults to the tab key.
	 *
	 * @return string
	 */
	public function getTitle() : string
	{
		return $this->getTab();
	}

	/**
	 * Determines if a warning icon should be displayed next to this tab
	 *
	 * @return bool
	 */
	public function showWarning() : bool
	{
		return false;
	}
}