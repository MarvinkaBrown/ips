<?php
/**
 * @brief		utf8mb4 Converter
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 April 2016
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\Db;
use IPS\Db\Select;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function in_array;
use function is_array;
use function mb_strtolower;
use function strtoupper;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * utf8mb4 Converter
 */
class utf8mb4 extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Bootstrap
	 *
	 * @return	void
	 */
	protected function manage() : void
	{	
		Output::i()->title = Member::loggedIn()->language()->addToStack('utf8mb4_converter');

		/* Check it isn't utf8mb4 already */
		if ( Settings::i()->getFromConfGlobal('sql_utf8mb4') === TRUE)
		{
			Output::i()->output = Theme::i()->getTemplate('global', 'core')->message( 'utf8mb4_converter_finished', 'success' );
			return;
		}
		
		/* Requires MySQL 5.5.3 */
		if ( !CIC AND version_compare( Db::i()->server_info, '5.5.3', '<' ) )
		{
			Output::i()->error( 'utf8mb4_converter_requires_553', '1C325/1', 403, '' );
		}
		
		/* Display Wizard */
		$supportController = new support;
		Output::i()->output = (string) new Wizard( array(
			'utf8mb4_converter_intro'	=> array( $this, '_introduction' ),
			'utf8mb4_converter_convert'	=> array( $this, '_convert' ),
			'utf8mb4_converter_finish'	=> array( $this, '_finish' ),
		), Url::internal( 'app=core&module=support&controller=utf8mb4' )->csrf() );
	}
	
	/**
	 * Introduction
	 *
	 * @param	array	$data	Wizard Data
	 * @return	array|string
	 */
	public function _introduction( array $data ) : array|string
	{
		$form = new Form( 'utf8mb4_converter_intro', 'continue' );
		$form->hiddenValues['continue'] = 1;
		$form->addMessage( CIC ? 'utf8mb4_converter_cic_explain' : 'utf8mb4_converter_explain' );
		
		if ( $form->values() )
		{
			return array();
		}
		
		return (string) $form;
	}
	
	/**
	 * Convert
	 *
	 * @param	array	$wizardData	Wizard Data
	 * @return	array|string
	 */
	public function _convert( array $wizardData ) : array|string
	{
		Session::i()->csrfCheck();

		if ( isset( Request::i()->finished ) )
		{
			return $wizardData;
		}
		
		$baseUrl = Url::internal( 'app=core&module=support&controller=utf8mb4' )->csrf();
		
		return new MultipleRedirect( $baseUrl,
			function( $mrData )
			{
				try
				{
					/* If this is the first run, do the database itself... */
					if ( !is_array( $mrData ) )
					{
						$databaseName = Settings::i()->sql_database;
						Db::i()->query("ALTER DATABASE `{$databaseName}` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;");
											
						return array( array( 'done' => array() ), Member::loggedIn()->language()->addToStack('utf8mb4_converter_converting'), 0 );
					}
					
					/* Set properties */
					Db::i()->charset = 'utf8mb4';
					Db::i()->collation = 'utf8mb4_unicode_ci';
					Db::i()->binaryCollation = 'utf8mb4_bin';
					
					/* Do each table */
					$select = Settings::i()->sql_tbl_prefix ? ( new Select( "SHOW TABLES LIKE '" . Db::i()->escape_string( Settings::i()->sql_tbl_prefix ) . "%'", array(), Db::i() ) ) : ( new Select( "SHOW TABLES", array(), Db::i() ) );
					$totalCount = count( $select );
					$i = 0;
					foreach ( $select as $table )
					{						
						$i++;
						$table = mb_substr( $table, mb_strlen( Settings::i()->sql_tbl_prefix ) );
												
						/* If we've already done it, skip to next */
						if ( in_array( $table, $mrData['done'] ) )
						{
							continue;
						}
						
						/* Check it belongs to us */
						$appName = mb_substr( $table, 0, mb_strpos( $table, '_' ) );
						try
						{
							$app = Application::load( $appName );
							$schemaRoot = in_array( $app->directory, \IPS\IPS::$ipsApps ) ? \IPS\ROOT_PATH : \IPS\SITE_FILES_PATH;
							$schema	= json_decode( file_get_contents( $schemaRoot . "/applications/{$app->directory}/data/schema.json" ), TRUE );
							if ( !array_key_exists( $table, $schema ) )
							{
								/* Make a special exception for CMS database tables */
								if( $app->directory !== 'cms' OR mb_strpos( $table, 'cms_custom_database_' ) !== 0 )
								{
									continue;
								}
							}
						}
						catch ( Exception $e )
						{
							continue;
						}

						/* If this is the search index table, clear it first. Some clients have timeouts on this step with the search index
							table because it is large and it has a primary key and a unique index, so the table work is too intensive. */
						if( $table == 'core_search_index' )
						{
							Db::i()->delete( 'core_search_index' );
						}
						
						/* Get table definition */
						$tableDefinition = Db::i()->getTableDefinition( $table, FALSE, TRUE );
						
						/* Drop any potentially problematic indexes */
						$indexesToRecreate = array();
						$maxLen = mb_strtolower( $tableDefinition['engine'] ) === 'innodb' ? 767 : 1000;
						foreach ( $tableDefinition['indexes'] as $indexName => $indexData )
						{
							/* If this is a fulltext index, we don't need to drop it and recreate it */
							if( mb_strtolower( $indexData['type'] ) == 'fulltext' )
							{
								continue;
							}

							/* If all columns in an index are already utf8mb4 then we don't need to worry about recreating the index */
							$needToRecreate = FALSE;

							$length = 0;
							$hasText = false;
							foreach( $indexData['columns'] as $column )
							{
								if ( in_array( mb_strtolower( $tableDefinition['columns'][ $column ]['type'] ), array( 'mediumtext', 'text' ) ) )
								{
									$hasText = true;
								}
								if ( isset( $tableDefinition['columns'][ $column ]['length'] ) )
								{
									$length += (int) ( $tableDefinition['columns'][ $column ]['length'] );
								}

								if( isset( $tableDefinition['columns'][ $column ]['collation'] ) AND $tableDefinition['columns'][ $column ]['collation'] !== 'utf8mb4_unicode_ci' )
								{
									$needToRecreate = TRUE;
								}
							}
														
							if ( $needToRecreate AND ( ( $length * 4 > $maxLen ) or $hasText ) )
							{
								$indexesToRecreate[ $indexName ] = $indexData;
								Db::i()->dropIndex( $table, $indexName );
							}
						}
												
						/* Do the table */
						$repair	= false;

						if( $tableDefinition['collation'] !== 'utf8mb4_unicode_ci' )
						{
							Db::i()->query("ALTER TABLE `" . Settings::i()->sql_tbl_prefix . "{$table}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
							Db::i()->query("ALTER TABLE `" . Settings::i()->sql_tbl_prefix . "{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
							$repair	= true;
						}

						/* Aggregate all the changes into one query for efficiency */
						$aggregatedChanges = array();

						/* Do each column */
						foreach ( $tableDefinition['columns'] as $columnName => $columnData )
						{
							if( in_array( strtoupper( $columnData['type'] ), array( 'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'ENUM', 'SET' ) ) )
							{
								if( $tableDefinition['columns'][ $columnName ]['collation'] !== 'utf8mb4_unicode_ci' )
								{
									$aggregatedChanges[] = "CHANGE COLUMN `" . Db::i()->escape_string( $columnName ) . "` " . Db::i()->compileColumnDefinition( $columnData );
									$repair = true;
								}
							}
						}
						
						/* Recreate any indexes */
						foreach ( $indexesToRecreate as $indexName => $indexData )
						{
							$aggregatedChanges[] = Db::i()->buildIndex( $table, $indexData );
							$repair	= true;
						}

						/* Now if we have any changes to make....make them */
						if( count( $aggregatedChanges ) )
						{
							Db::i()->query( "ALTER TABLE " . Db::i()->prefix . Db::i()->escape_string( $table ) . " " . implode( ', ', $aggregatedChanges ) );
						}
												
						/* Repair and optimize */
						if( $repair === true )
						{
							Db::i()->query("REPAIR TABLE `" . Settings::i()->sql_tbl_prefix . "{$table}`");
							//\IPS\Db::i()->query("OPTIMIZE TABLE `" . \IPS\Settings::i()->sql_tbl_prefix . "{$table}`");
						}

						/* If this is the search index table, initiate rebuild now. */
						if( $table == 'core_search_index' and Settings::i()->search_method == 'mysql' )
						{
							Index::i()->rebuild();
						}
						
						/* Continue */
						$mrData['done'][] = $table;
						return array( $mrData, Member::loggedIn()->language()->addToStack('utf8mb4_converter_converting'), floor( 100 / $totalCount * $i ) );
					}
					
					/* If we get to this point, we're finished */
					return NULL;
				}
				catch ( Exception $e )
				{
					Log::log( $e, 'utf8mb4' );
					Output::i()->error( Member::loggedIn()->language()->addToStack( 'utf8mb4_converter_error', FALSE, array( 'sprintf' => array( $e->getMessage() ) ) ), '4C171/4', 403, '' );
				}	
				
			},
			function() use ( $baseUrl )
			{
				Output::i()->redirect( $baseUrl->setQueryString( 'finished', 1 ) );
			}
		);
	}
	
	/**
	 * Finish
	 *
	 * @param	array	$data	Wizard Data
	 * @return	array|string
	 */
	public function _finish( array $data ) : array|string
	{
		Session::i()->log( 'acplog__ran_utf8mb4_converter' );

		return Theme::i()->getTemplate('support')->finishUtf8Mb4Conversion();
	}
}