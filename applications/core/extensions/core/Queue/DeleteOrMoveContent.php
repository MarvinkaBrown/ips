<?php
/**
 * @brief		Background Task: Delete or move content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 May 2014
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Delete or move content
 */
class DeleteOrMoveContent extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];
		$node = $classname::load( $data['id'] );
		$data['count'] = (int) $node->getContentItemCount();

		if ( !$data['count'] )
		{
			if ( isset( $data['deleteWhenDone'] ) and $data['deleteWhenDone'] )
			{
				$node->delete();
			}
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
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		try
		{
			$node = $classname::load( $data['id'] );
		}
		catch( OutOfRangeException $e )
		{
			/* Item no longer exists, so we're done here. */
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$moveTo = NULL;
		if ( isset( $data['moveTo'] ) )
		{
			$moveToClass = $data['moveToClass'] ?? $classname;
			try
			{
				$moveTo = $moveToClass::load( $data['moveTo'] );
			}
			catch( OutOfRangeException $e ){}
		}
		
		$return = $node->massMoveorDelete( $moveTo, $data );
		
		if ( $return === NULL and isset( $data['deleteWhenDone'] ) and $data['deleteWhenDone'] )
		{
			$node->delete();
		}

		if( $return === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $return + $offset;
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
		$classname = $data['class'];
		$exploded = explode( '\\', $classname );
		if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}
		
		$node = $classname::load( $data['id'] );
		if ( isset( $data['moveTo'] ) )
		{
			$moveTo = $classname::load( $data['moveTo'] );
			$link1 = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $node->url(), TRUE, $node->_title, FALSE );
			$link2 = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $moveTo->url(), TRUE, $moveTo->_title, FALSE );
			$text = Member::loggedIn()->language()->addToStack('backgroundQueue_move_content', FALSE, array( 'htmlsprintf' => array( $link1, $link2 ) ) );
		}
		else
		{
			$link = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $node->url(), TRUE, $node->_title, FALSE );
			$text = Member::loggedIn()->language()->addToStack('backgroundQueue_deleting', FALSE, array( 'htmlsprintf' => array( $link ) ) );
		}
		
		return array( 'text' => $text, 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}
}