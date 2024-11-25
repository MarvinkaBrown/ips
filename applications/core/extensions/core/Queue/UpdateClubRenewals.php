<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		13 Apr 2023
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Member\Club;
use IPS\nexus\Purchase;
use IPS\Task\Queue\OutOfRangeException as QueueException;
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
class UpdateClubRenewals extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): array|null
	{
		$data['count'] = $this->getQuery( 'COUNT(*)', $data )->first();

		if( $data['count'] == 0 )
		{
			return null;
		}

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws    QueueException    Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		if ( !Application::appisEnabled( 'nexus' ) )
		{
			throw new QueueException;
		}

		$select	= $this->getQuery( 'nexus_purchases.*', $data, $offset );

		if ( !$select->count() or $offset > $data['count'] )
		{
			throw new QueueException;
		}

		foreach( $select AS $row )
		{
			try
			{
				$club = Club::load( $data['club'] );
				$purchase = Purchase::constructFromData( $row );

				$club->updatePurchase( $purchase, $data['changes'], TRUE );
			}
			catch( Exception $e ) {}

			$offset++;
		}

		return $offset;
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
		$text = Member::loggedIn()->language()->addToStack('updating_club_renewals', FALSE, array() );

		return array( 'text' => $text, 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}

	/**
	 * Return the query
	 *
	 * @param	string	$select		What to select
	 * @param	array	$data		Queue data
	 * @param	int|bool		$offset		Offset to use (FALSE to not apply limit)
	 * @return	Select
	 */
	protected function getQuery( string $select, array $data, int|bool $offset=FALSE ) : Select
	{
		return Db::i()->select( $select, 'nexus_purchases', array( "ps_app=? and ps_type=? and ps_item_id=?", 'core', 'club', $data['club'] ), 'ps_id', ( $offset !== FALSE ) ? array( $offset, REBUILD_QUICK ) : array()  );
	}
}