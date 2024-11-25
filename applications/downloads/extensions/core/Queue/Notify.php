<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		14 Oct 2019
 */

namespace IPS\downloads\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content;
use IPS\Db;
use IPS\downloads\File;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Notification;
use IPS\Task\Queue\OutOfRangeException;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class Notify extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		try
		{
			$file = File::load( $data['file'] );
		}
		catch( \OutOfRangeException $e )
		{
			throw new OutOfRangeException;
		}

		$notifyIds = array();

		$recipients = iterator_to_array( Db::i()->select( 'downloads_files_notify.*', 'downloads_files_notify', array( 'notify_file_id=?', $data['file'] ), 'notify_id ASC', array( $offset, Content::NOTIFICATIONS_PER_BATCH ) ) );

		if( !count( $recipients ) )
		{
			throw new OutOfRangeException;
		}

		$notification = new Notification( Application::load( 'downloads' ), 'new_file_version', $file, array( $file ) );

		foreach( $recipients AS $recipient )
		{
			$recipientMember = Member::load( $recipient['notify_member_id'] );
			if ( $file->container()->can( 'view', $recipientMember ) )
			{
				$notifyIds[] = $recipient['notify_id'];
				$notification->recipients->attach( $recipientMember );
			}
		}

		Db::i()->update( 'downloads_files_notify', array( 'notify_sent' => time() ), Db::i()->in( 'notify_id', $notifyIds ) );
		$notification->send();

		return $offset + Content::NOTIFICATIONS_PER_BATCH;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		try
		{
			$file = File::load( $data['file'] );
		}
		catch( \OutOfRangeException $e )
		{
			throw new OutOfRangeException;
		}

		$complete			= $data['notifyCount'] ? round( 100 / $data['notifyCount'] * $offset, 2 ) : 100;

		return array( 'text' => Member::loggedIn()->language()->addToStack('backgroundQueue_new_version', FALSE, array( 'htmlsprintf' => array( Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $file->url(), TRUE, $file->name, FALSE ) ) ) ), 'complete' => $complete );
	}
}