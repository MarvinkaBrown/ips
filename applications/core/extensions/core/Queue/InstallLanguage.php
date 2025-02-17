<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Nov 2017
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Lang;
use IPS\Member;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class InstallLanguage extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		/* Get count...this is an estimate but is good enough for the progress bar */
		$data['count'] = Db::i()->select( 'COUNT(DISTINCT(word_key))', 'core_sys_lang_words', array( 'word_app=?', $data['application'] ) )->first();

		if ( !$data['count'] )
		{
			return NULL;
		}

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		if( Application::appIsEnabled( $data['application'] ) AND file_exists( \IPS\ROOT_PATH . "/applications/{$data['application']}/data/lang.xml" ) )
		{
			$rowsInserted = Application::load( $data['application'] )->installLanguages( $offset, REBUILD_QUICK );

			/* If we didn't insert any, then we're done */
			if( $rowsInserted )
			{
				return $rowsInserted + $offset;
			}
		}

		throw new \IPS\Task\Queue\OutOfRangeException;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'backgroundQueue_importing_english', FALSE, array( 'sprintf' => Application::load( $data['application'] )->_title ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}

	/**
	 * Perform post-completion processing
	 *
	 * @param	array	$data		Data returned from preQueueData
	 * @param	bool	$processed	Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{
		$data = json_decode( $data['data'], TRUE );

		Db::i()->query( "REPLACE INTO " . Db::i()->prefix . "core_sys_lang_words (lang_id, word_app, word_plugin, word_theme, word_key, word_custom, word_default, word_default_version, word_custom_version, word_js, word_export ) SELECT {$data['language_id']} AS lang_id, word_app, word_plugin, word_theme, word_key, word_custom, word_default, word_default_version, word_custom_version, word_js, 0 FROM " . Db::i()->prefix  . "core_sys_lang_words WHERE word_export=0 AND word_app='{$data['application']}' AND lang_id=" . Lang::defaultLanguage() );

		unset( Store::i()->languages );
	}
}