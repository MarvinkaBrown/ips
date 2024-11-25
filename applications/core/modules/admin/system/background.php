<?php
/**
 * @brief		Background processes 'Run Now'
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Jan 2015
 */

namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Task;
use IPS\Theme;
use UnderflowException;
use function defined;
use function intval;
use function is_array;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background processes 'Run Now'
 */
class background extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( CIC )
		{
			Output::i()->error( 'no_writes', '2C347/1', 403, '' );
		}
		
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->title = Member::loggedIn()->language()->addToStack('background_process_run_title');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->backgroundProcessesRunNow();
	}
	
	/**
	 * Process
	 *
	 * @return	void
	 */
	protected function process() : void
	{
		Session::i()->csrfCheck();
		
		$self = $this;
		$multiRedirect = new MultipleRedirect(
			Url::internal('app=core&module=system&controller=background&do=process')->csrf(),
			function( $data ) use ( $self )
			{
				/* Make sure the task is locked */
				$task = Task::load('queue', 'key');
				$task->running = TRUE;
				$task->next_run = time() + 900;
				$task->save();
				
				if ( ! is_array( $data ) )
				{
					$count = $self->getCount();
					 
					return array( array( 'count' => $count, 'done' => 0 ), Member::loggedIn()->language()->addToStack('background_process_starting') );
				}
				else
				{
					try
					{
						/* Run the next queue task, if any */
						$queueData = Task::runQueue();
					}
					catch ( UnderflowException $e )
					{
						/* If we're here it means there were no rows in core_queue and we are done */
						return NULL;
					}
					
					$data['done']	= $data['done'] + ( $queueData['offset'] - $queueData['_originalOffset'] );
					$json			= json_decode( $queueData['data'], TRUE );

					$lang = array( $queueData['key'] );
					
					if ( isset( $json['class'] ) )
					{
						$lang[] = $json['class'];
					}
					else if ( isset( $json['extension'] ) )
					{
						$lang[] = $json['extension'];
					}
					else if ( isset( $json['storageExtension'] ) )
					{
						$lang[] = $json['storageExtension'];
					}
					
					if ( isset( $json['count'] ) )
					{
						/* If the offset is larger than the count, then we should just show the count instead (to avoid situations where it will display 150 / 139, for example) */
						$offset = intval( $queueData['offset'] );
						if ( $offset > $json['count'] )
						{
							$offset = $json['count'];
						}
						$lang[] = " " . $offset . ' / ' . $json['count'];
					}
					
					return array( $data, Member::loggedIn()->language()->addToStack('background_processes_processing', FALSE, array( 'sprintf' => array( implode( ' - ', $lang ) ) ) ), ( $data['count'] ) ? round( ( 100 / $data['count'] * $data['done'] ), 2 ) : 100 );
				}
			},
			function()
			{
				/* Make sure the task is unlocked */
				$task = Task::load('queue', 'key');
				$task->running = FALSE;
				$task->next_run = time() + 60;
				$task->save();
				
				Session::i()->log( 'acplog__background_tasks_ran' );
				
				Output::i()->redirect( Url::internal('app=core&module=overview&controller=dashboard'), 'completed' );
			}
		);
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('background_process_run_title');
		Output::i()->output = $multiRedirect;
	}
	
	/**
	 * Get the count of items to process
	 *
	 * @return int
	 */
	public function getCount() : int
	{
		$count = 0;
		foreach( Db::i()->select( '*', 'core_queue' ) as $row )
		{
			if ( ! empty( $row['data'] ) )
			{
				$data = json_decode( $row['data'], TRUE );
				
				if( isset( $data['realCount'] ) )
				{
					$count += intval( $data['realCount'] );
				}
				elseif ( isset( $data['count'] ) )
				{
					$count += intval( $data['count'] );
				}
				else
				{
					$count++;
				}
			}
		}
		
		return $count;
	}
}