<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jan 2018
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\QueueAbstract;
use IPS\Log;
use IPS\Member;
use IPS\Sitemap;
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
class RebuildSitemap extends QueueAbstract
{

	/**
	 * Return the Sitemap Extension Instance
	 *
	 * @param $data
	 * @return null
	 */
	protected function getExtension( $data )
	{
	    /* Get all sitemap extensions and use the guest object for access permissions */
		$extensions	= Application::allExtensions( 'core', 'Sitemap', new Member, 'core' );
		if ( isset( $extensions[ $data[ 'extensionKey'] ] ) )
		{
			return $extensions[ $data[ 'extensionKey' ] ];
		}
		return NULL;
	}

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		Log::debug( "Getting preQueueData for " . $data[ 'extensionKey'], 'rebuildSitemap' );

		$extension = $this->getExtension( $data );
		if ( !$extension )
		{
			return NULL;
		}

		$files = $extension->getFilenames();
		$data['count'] = count( $files );

		if( $data['count'] == 0 )
		{
			return NULL;
		}

		$data['files'] = $files;
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
	    if ( $offset >= $data['count'] )
        {
            throw new \IPS\Task\Queue\OutOfRangeException;
        }

        $extension = $this->getExtension( $data );

        if ( !$extension )
        {
			Log::log( "Trying to build sitemap for not existing class " . $data[ 'extensionKey'], 'rebuildSitemapError' );
            throw new \IPS\Task\Queue\OutOfRangeException;
        }

        $filenames = $data['files'];

        /* Done */
        if ( !isset( $data['files'][$offset] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$name = $filenames[$offset];

		$sitemap = new Sitemap;
		$extension->generateSitemap( $name, $sitemap );
		return $offset + 1;
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
	    /* We need to load the extension to load the dynamic langstrings */
        $extension = $this->getExtension( $data );

        /* Was the application probably uninstalled? */
        if ( !$extension )
        {
            throw new OutOfRangeException;
        }

        /* If this is a content class Sitemap Extension, we can use the class to build the title */
        if ( isset( $extension->class ) )
        {
            $class  = $extension->class;
            $key = 'sitemap_core_Content_' . mb_substr( str_replace( '\\', '_', $class ), 4 ) ;
            Member::loggedIn()->language()->words[ $key ] = Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE );
        }
        else
        {
           $key = 'sitemap_' . $data[ 'extensionKey' ];
        }

        return array( 'text' => Member::loggedIn()->language()->addToStack( 'rebuilding_sitemap_stuff', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $key ) ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}

}