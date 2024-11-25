<?php
/**
 * @brief		Background Task - Bulk Mails
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Nov 2016
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\BulkMail\Bulkmailer;
use IPS\Db;
use IPS\Email;
use IPS\Extensions\QueueAbstract;
use IPS\Lang;
use IPS\Member;
use OutOfRangeException;
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
class Bulkmail extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = Bulkmailer::load( $data['mail_id'] )->getQuery( Bulkmailer::GET_COUNT_ONLY )->first();
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
		try
		{
			$mail = Bulkmailer::load( $data['mail_id'] );
			$classToUse = Email::classToUse( Email::TYPE_BULK );
			
			/* Reduce the maximum number of emails to be sent for bulk mail to 500 to prevent member specific tag timeouts */
			$mailPerGo = min( $classToUse::MAX_EMAILS_PER_GO, 500 );
			$existingvalue = Db::i()->readWriteSeparation;
			Db::i()->readWriteSeparation = FALSE;
			$results = $mail->getQuery( array( $offset, $mailPerGo ) );
			
			if ( !count( $results ) )
			{
				$mail->active = 0;
				$mail->offset = 0;
				$mail->save();
				Db::i()->readWriteSeparation = $existingvalue;
				throw new \IPS\Task\Queue\OutOfRangeException;
			}

			/* Convert $results into an array with replacement tags */
			$recipients = array();
			foreach ( $results as $memberData )
			{
				$member = Member::constructFromData( $memberData );
				
				$vars = array();
				foreach ( $mail->returnTagValues( 2, $member ) as $k => $v )
				{
					$vars[ mb_substr( $k, 1, -1 ) ] = $v;
				}
				
				$recipients[ $member->language()->_id ][ $memberData['email'] ] = $vars;
			}
					
			/* Convert member-specific {{tag}} into *|tag|* and global {{tag}} into the value */
			$content = $mail->content;
			foreach ( $mail->returnTagValues( 1 ) as $k => $v )
			{
				$content = str_replace( $k, $v, $content );
			}
			foreach( array_keys( Bulkmailer::getTags() ) as $k )
			{
				if ( mb_strpos( $content, $k ) !== FALSE )
				{
					$content = str_replace( $k, '*|' . str_replace( array( '{', '}' ), '', $k ) . '|*', $content );
				}
			}

			/* Format content */
			$content = Email::staticParseTextForEmail( $content, Lang::load( Lang::defaultLanguage() ) );

			foreach( array_keys( Bulkmailer::getTags() ) as $k )
			{
				$content = str_replace( '%7B' . mb_substr( $k, 1, -1 ) . '%7D', '*|' . mb_substr( $k, 1, -1 ) . '|*', $content );
				$content = str_replace( '*%7C' . mb_substr( $k, 1, -1 ) . '%7C*', '*|' . mb_substr( $k, 1, -1 ) . '|*', $content );
			}
									
			/* Send it */
			$email = Email::buildFromContent( $mail->subject, $content, NULL, Email::TYPE_BULK, Email::WRAPPER_USE, 'bulk_mail' )
				->setUnsubscribe( 'core', 'unsubscribeBulk' );
			$sent = 0;
			foreach ( $recipients as $languageId => $_recipients )
			{
				$sent += $email->mergeAndSend( $_recipients, NULL, NULL, array( 'List-Unsubscribe' => '<*|unsubscribe_url|*>' ), Lang::load( $languageId ) );
			}
			
			$mail->updated	= time();
			$mail->offset	= ( $mail->offset + $mailPerGo );
			$mail->sentto	= ( $mail->sentto + $sent );
			$mail->save();
			Db::i()->readWriteSeparation = $existingvalue;
			return $offset + $mailPerGo;
		}
		catch( OutOfRangeException $e )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
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
		$mail = Bulkmailer::load( $data['mail_id'] );
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'bulk_mail_queue_running', FALSE, array( 'sprintf' => array( $mail->subject ) ) ), 'complete' => round( 100 / $data['count'] * $offset, 2 ) );
	}
}