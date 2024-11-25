<?php
/**
 * @brief		File Storage Extension: Records
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage  Content
 * @since		11 April 2014
 */

namespace IPS\cms\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Search\Index;
use IPS\Data\Store;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Extensions\UninstallAbstract;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Remove custom databases
 */
class Databases extends UninstallAbstract
{
    /**
     * Uninstall custom databases
     *
	 * @param string $application
	 * @return void
     */
    public function preUninstall( string $application ) : void
    {
        if ( Db::i()->checkForTable( 'cms_databases' ) )
        {
            foreach ( Db::i()->select( '*', 'cms_databases' ) as $db )
            {
                /* The content router only returns databases linked to pages. In theory, you may have linked a database and then removed it,
                    so the method to remove all app content from the search index fails, so we need to account for that here: */
                Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records' . $db['database_id'] );
            }
        }
    }

    /**
     * Uninstall custom databases
     *
	 * @param string $application
	 * @return void
     */
    public function postUninstall( string $application ) : void
    {
        /* cms_databases has been removed */
        $tables = array();
        try
        {
            $databaseTables = Db::i()->query( "SHOW TABLES LIKE '" . Db::i()->prefix . "cms_custom_database_%'" )->fetch_assoc();
            if ( $databaseTables )
            {
                foreach( $databaseTables as $row )
                {
                    if( is_array( $row ) )
                    {
                        $tables[] = array_pop( $row );
                    }
                    else
                    {
                        $tables[] = $row;
                    }
                }
            }

        }
        catch( Exception $ex ) { }

        foreach( $tables as $table )
        {
            if ( Db::i()->checkForTable( $table ) )
            {
                Db::i()->dropTable( $table );
            }
        }

        if ( isset( Store::i()->cms_menu ) )
        {
            unset( Store::i()->cms_menu );
        }
    }
}