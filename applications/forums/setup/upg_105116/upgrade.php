<?php
/**
 * @brief		4.5.4 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		29 Sep 2020
 */

namespace IPS\forums\setup\upg_105116;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\forums\Topic;
use IPS\Settings;
use function defined;
use function IPS\Cicloud\getForcedArchiving;
use const IPS\CIC2;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.5.4 Upgrade Code
 */
class Upgrade
{
	/**
	 * Tweak archiving for CIC2
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		/* If this is a CIC2 site... */
		if ( CIC2 )
		{
			/* Make sure archiving is on if it should be */
			if( getForcedArchiving() )
			{
				Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'archive' ) );
			}
			/* Otherwise unarchive everything and make sure archiving is not enabled. They can turn back on later if they want. */
			else
			{
				Db::i()->update( 'forums_topics', array( 'topic_archive_status' => Topic::ARCHIVE_RESTORE ), array( 'topic_archive_status=?', Topic::ARCHIVE_DONE ) );

				Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'archive' ) );
				Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'unarchive' ) );

				/* This disables the archiving setting */
				Settings::i()->changeValues( array( 'archive_last_post_cloud' => 100 ) );
			}
		}

		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Adjusting archiving options";
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}