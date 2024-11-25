<?php
/**
 * @brief		schema
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use Diff;
use DomainException;
use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Custom;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * schema
 */
class schema extends Controller
{
	/**
	 * @var bool
	 */
	public static bool $csrfProtected = true;

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Create the file if it doesn't exist */
		$json = $this->_getSchema();

		/* Build list table */
		$table = new Custom( $json, $this->url );
		$table->langPrefix = 'database_table_';
		$table->mainColumn = 'name';
		$table->limit	   = 150;
		$table->include = array( 'name' );

		/* Set default sort */
		$table->sortBy = $table->sortBy ?: 'name';
		$table->sortDirection = $table->sortDirection ?: 'asc';

		/* Add the "add" button */
		$table->rootButtons = array(
			'add' => array(
				'icon'	=> 'plus',
				'title'	=> 'database_table_create',
				'link'	=> $this->url->setQueryString( 'do', 'addTable' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('database_table_create') )
			)
		);

		/* Add the buttons for each row */
		$url = $this->url;
		$table->rowButtons = function( $row ) use ( $url )
		{
			return array(
				'edit' => array(
					'icon'	=> 'pencil',
					'title'	=> 'database_table_edit',
					'link'	=> $url->setQueryString( array( 'do' => 'editSchema', '_name' => $row['name'] ) ),
					'hotkey'=> 'e'
				),
				'delete' => array(
					'icon'	=> 'times-circle',
					'title'	=> 'database_table_delete',
					'link'	=> $url->setQueryString( array( 'do' => 'deleteTable', 'name' => $row['name'] ) ),
					'data'	=> array( 'delete' => '', 'delete-warning' => Member::loggedIn()->language()->addToStack( 'database_droptable_info' ) )
				)
			);
		};

		Output::i()->output = (string) $table;
	}

	/**
	 * Database Schema: Add Table
	 *
	 * @return	void
	 */
	protected function addTable() : void
	{
		/* Get our current working version queries */
		$queriesJson = $this->_getQueries( 'working' );

		/* Get form */
		$message = NULL;
		$activeTab = Request::i()->tab ?: 'new';
		$form = new Form( "database_table_{$activeTab}" );
		switch ( $activeTab )
		{
			/* Create New */
			case 'new':
				$form->add( new Text(
					'database_table_name',
					NULL,
					TRUE,
					array(
						'maxLength' => ( 64 - strlen( "{$this->application->directory}_" ) )
					),
					function( $value )
					{
						if( Db::i()->checkForTable( Request::i()->appKey . '_' . $value ) === TRUE )
						{
							throw new DomainException( 'database_table_exists' );
						}
					},
					"{$this->application->directory}_"
				) );
				$message = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('database_newtable_info'), 'information' );
				break;

			/* Import */
			case 'import':

				/* Fetch tables */
				$tables = array();
				$stmt = Db::i()->query( "SHOW TABLES;" );
				while ( $row = $stmt->fetch_assoc() )
				{
					$tableName = array_pop( $row );
					$tables[ $tableName ] = $tableName;
				}

				/* Add the form element */
				$form->add( new Select(
					'database_table_import',
					NULL,
					TRUE,
					array( 'options' => $tables, 'parse' => 'normal' )
				) );

				/* Warn the user we may rename the table */
				$message = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('database_renametable_info', FALSE, array( 'sprintf' => array( "{$this->application->directory}_" ) ) ), 'information' );

				break;

			/* Upload */
			case 'upload':
				$appKey = $this->application->directory;
				$form->add( new Upload(
					'upload',
					NULL,
					TRUE,
					array( 'allowedFileTypes' => array( 'sql' ), 'temporary' => TRUE ),
					function( $value ) use ( $appKey )
					{
						/* Get contents and remove comments */
						$contents = Db::stripComments( file_get_contents( $value ) );

						/* If there's more than one ; character - reject it */
						if( mb_substr_count( $contents, ';' ) > 1 )
						{
							throw new DomainException( 'database_upload_too_many_queries' );
						}

						/* Is it a CREATE TABLE statement */
						preg_match( '/^CREATE (TEMPORARY )?TABLE (IF NOT EXISTS )?`?(.+?)`?\s+?\(/i', $contents, $matches );
						if( empty( $matches ) or !$matches[3] )
						{
							throw new DomainException( 'database_upload_no_create' );
						}

						/* Does the table already exist? */
						if ( mb_substr( $matches[3], 0, mb_strlen( $appKey ) + 1 ) !== "{$appKey}_" )
						{
							$matches[3] = "{$appKey}_{$matches[3]}";
						}
						if( Db::i()->checkForTable( $matches[3] ) )
						{
							throw new DomainException( 'database_table_exists' );
						}
					}
				) );
				$message = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('database_newtable_info'), 'information' );
				break;
		}

		/* Has the form been submitted? */
		if( $values = $form->values() )
		{
			/* Work out defintion */
			switch ( $activeTab )
			{
				/* New table */
				case 'new':
					/* Set definition */
					$definition = array(
						'name'		=> $this->application->directory . '_' . $values['database_table_name'],
						'columns'	=> array(
							'id' => array(
								'name'				=> 'id',
								'type'				=> 'BIGINT',
								'length'			=> '20',
								'unsigned'			=> TRUE,
								'allow_null'		=> FALSE,
								'default'			=> NULL,
								'auto_increment'	=> TRUE,
								'comment'			=> Member::loggedIn()->language()->get('database_default_column_comment')
							),
						),
						'indexes'	=> array(
							'PRIMARY' => array(
								'type'		=> 'primary',
								'name'		=> 'PRIMARY',
								'columns'	=> array( 'id' ),
								'length'	=> array( NULL ),
							),
						),
					);

					/* Create table */
					Db::i()->createTable( $definition );

					/* Add to the queries.json file */
					$queriesJson = $this->_addQueryToJson( $queriesJson, array( 'method' => 'createTable', 'params' => array( $definition ) ) );
					$this->_writeQueries( 'working', $queriesJson );

					break;

				/* Import existing table */
				case 'import':
					/* Get definition */
					if ( Db::i()->prefix AND mb_strpos( $values['database_table_import'], Db::i()->prefix ) === 0 )
					{
						$values['database_table_import'] = mb_substr( $values['database_table_import'], mb_strlen( Db::i()->prefix ) );
					}
					$definition = Db::i()->getTableDefinition( $values['database_table_import'] );

					/* Do we need to rename? */
					if ( mb_substr( $definition['name'], 0, mb_strlen( $this->application->directory ) + 1 ) !== "{$this->application->directory}_" )
					{
						/* Do it */
						Db::i()->renameTable( $definition['name'], "{$this->application->directory}_{$definition['name']}" );

						/* Add to the queries.json file */
						$queriesJson = $this->_addQueryToJson( $queriesJson,  array( 'method' => 'renameTable', 'params' => array( $definition['name'], "{$this->application->directory}_{$definition['name']}" ) ) );
						$this->_writeQueries( 'working', $queriesJson );

						/* Set the name for later */
						$definition['name'] = "{$this->application->directory}_{$definition['name']}";
					}

					break;

				/* Uploaded .sql file */
				case 'upload':
					/* Get contents */
					$contents = Db::stripComments( file_get_contents( $values['upload'] ) );

					/* Put the app key in if it's not already */
					$appKey = $this->application->directory;
					$contents = preg_replace_callback( '/CREATE (TEMPORARY )?TABLE (IF NOT EXISTS )?`?(.+?)`?\s+?/i', function( $matches ) use ( $appKey )
					{
						$prefix = '';
						if ( Db::i()->prefix AND mb_substr( $matches[3], 0, mb_strlen( Db::i()->prefix ) ) !== Db::i()->prefix )
						{
							$prefix = Db::i()->prefix;
						}

						if ( mb_substr( $matches[3], 0, mb_strlen( $appKey ) + 1 ) !== "{$appKey}_" )
						{
							return str_replace( $matches[3], "{$prefix}{$appKey}_{$matches[3]}", $matches[0] );
						}

						return $matches[0];
					}, $contents );

					/* Work out the name */
					$prefix = Db::i()->prefix;
					preg_match( "/CREATE (TEMPORARY )?TABLE (IF NOT EXISTS )?`?{$prefix}(.+?)`?\s+?\(/i", $contents, $matches );
					$name = $matches[3];

					/* Run the query */
					Db::i()->query( $contents );

					/* Now get the definition */
					$definition = Db::i()->getTableDefinition( $name );

					/* Add to the queries.json file */
					$queriesJson = $this->_addQueryToJson( $queriesJson, array( 'method' => 'createTable', 'params' => array( $definition ) ) );
					$this->_writeQueries( 'working', $queriesJson );

					/* Delete the file */
					unlink( $values['upload'] );

					break;
			}

			/* Add to schema.json */
			$schema = $this->_getSchema();
			$schema[ $definition['name'] ] = $definition;
			$this->_writeSchema( $schema );

			/* Redirect */
			Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'] ) ) );
		}

		/* If not, show it */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->tabs(
			array(
				'new'		=> 'database_table_new',
				'import'	=> 'database_table_import',
				'upload'	=> 'database_table_upload',
			),
			$activeTab,
			$message . $form,
			$this->url->setQueryString( array( 'do' => 'addTable', 'existing' => 1 ) )
		);

		if( Request::i()->isAjax() )
		{
			if( Request::i()->existing )
			{
				Output::i()->output = $message . $form;
			}
		}
	}

	/**
	 * Database Schema: View/Edit Table
	 *
	 * @return	void
	 */
	protected function editSchema() : void
	{
		/* Get table definition */
		$schema = $this->_getSchema();
		if ( !isset( $schema[ Request::i()->_name ] ) )
		{
			Output::i()->error( 'node_error', '2C103/A', 404, '' );
		}
		$definition = Db::i()->normalizeDefinition( $schema[ Request::i()->_name ] );

		/* Init Output */
		Output::i()->title = $definition['name'];
		$this->breadcrumbs[] = [ $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'] ) ), $definition['name'] ];
		Output::i()->output .= Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('database_changes_info'), 'information' );

		/* Does it match the database? */
		$_definition = $definition;
		unset( $_definition['inserts'] );
		unset( $_definition['comment'] );
		unset( $_definition['reporting'] );
		try
		{
			$localDefinition = $this->_getTableDefinition( $definition['name'] );
		}
		catch ( OutOfRangeException $e )
		{
			Db::i()->createTable( $definition );
			$localDefinition = $definition;
		}

		$localDefinition = Db::i()->normalizeDefinition( $localDefinition );

		unset( $localDefinition['comment'] );

		if ( $_definition != $localDefinition )
		{
			$string1 = str_replace( array( '&lt;?php', '<br>', '<br />' ), "\n", highlight_string( "<?php\n" . var_export( $_definition, TRUE ), TRUE ) );
			$string2 = str_replace( array( '&lt;?php', '<br>', '<br />' ), "\n", highlight_string( "<?php\n" . var_export( $localDefinition, TRUE ), TRUE ) );

			require_once ROOT_PATH . "/system/3rd_party/Diff/class.Diff.php";
			$diff = html_entity_decode( Diff::toTable( Diff::compare( $string1, $string2 ) ) );

			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/diff.css', 'core', 'admin' ) );
			Output::i()->output = Theme::i()->getTemplate( 'applications', 'core' )->schemaConflict( $definition['name'], $diff );
			return;
		}

		/* Get schema file */
		$schemaJson = $this->_getSchema();
		$_schemaJson = $schemaJson;

		/* We'll probably also need the queries.json file */
		$queriesJson = $this->_getQueries( 'working' );
		$_queriesJson = $queriesJson;
		$queries = array();

		/* Display "Show Schema" button */
		Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'database_show_schema',
				'icon'		=> 'code',
				'link'		=> $this->url->setQueryString( array( 'do' => 'showSchema', '_name' => $definition['name'] ) ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('database_show_schema') )
			),
		);

		/* Work out tab */
		$activeTab = Request::i()->tab ?: 'info';

		//-----------------------------------------
		// Info
		//-----------------------------------------

		if ( $activeTab === 'info' )
		{
			/* Build Form */
			$output = new Form();

			$output->add( new Text(
				'database_table_name',
				( mb_substr( $definition['name'], 0, mb_strlen( $this->application->directory ) ) === $this->application->directory ) ? mb_substr( $definition['name'], mb_strlen( $this->application->directory ) + 1 ) : $definition['name'],
				TRUE,
				array( 'maxLength' => ( 64 - mb_strlen( "{$this->application->directory}_" ) ) ),
				NULL,
				"{$this->application->directory}_"
			) );

			$output->add( new Text(
				'database_comment',
				$definition['comment'] ?? '',
				FALSE,
				array( 'maxLength' => 60, 'size' => 80 )
			) );
			$output->add( new Select(
				'database_table_engine',
				$definition['engine'] ?? '',
				FALSE,
				array(
					'options' => array(
						''			=> 'database_table_engine_default',
						'MyISAM'	=> 'MyISAM',
						'InnoDB'	=> 'InnoDB',
						'MEMORY'	=> 'MEMORY',
					)
				),
				function( $v ) use ( $definition )
				{
					$fulltextSupported	= FALSE;

					if( $v AND $v === 'MyISAM' )
					{
						$fulltextSupported	= TRUE;
					}
					else if( ( ( $v AND $v === 'InnoDB' ) OR !$v ) AND Db::i()->server_version >= 50600 )
					{
						$fulltextSupported	= TRUE;
					}

					if ( !$fulltextSupported )
					{
						foreach ( $definition['indexes'] as $index )
						{
							if ( $index['type'] === 'fulltext' )
							{
								throw new DomainException( 'database_table_engine_fulltext' );
							}
						}
					}
				}
			) );

			if ( in_array( $this->application->directory, IPS::$ipsApps ) )
			{
				$output->add( new Select( 'database_table_reporting', $definition['reporting'] ?? 'none', FALSE, array( 'options' => array(
					'none'	=> 'database_table_reporting_none',
					'count'	=> 'database_table_reporting_count',
				) ) ) );
			}

			/* Handle submissions */
			if ( $values = $output->values() )
			{
				/* Changed the comment? */
				if ( !isset( $definition['comment'] ) or $values['database_comment'] !== $definition['comment']  )
				{
					$schemaJson[ $definition['name'] ]['comment'] = $values['database_comment'];
				}

				/* Changed the engine? */
				if ( ( !isset( $definition['engine'] ) and $values['database_table_engine'] ) or ( isset( $definition['engine'] ) and $values['database_table_engine'] != $definition['engine'] ) )
				{
					if ( $values['database_table_engine'] )
					{
						$queries[] = array( 'method' => 'alterTable', 'params' => array( $definition['name'], NULL, $values['database_table_engine'] ) );
						$schemaJson[ $definition['name'] ]['engine'] = $values['database_table_engine'];
					}
					else
					{
						unset( $schemaJson[ $definition['name'] ]['engine'] );
					}
				}

				/* Changed reporting? */
				if ( isset( $values['database_table_reporting'] ) AND ( !isset( $definition['reporting'] ) or $values['database_table_reporting'] !== $definition['reporting'] ) )
				{
					$schemaJson[ $definition['name'] ]['reporting'] = $values['database_table_reporting'];
				}

				/* Renamed table? */
				$values['database_table_name'] = "{$this->application->directory}_{$values['database_table_name']}";
				if ( $values['database_table_name'] !== $definition['name'] )
				{
					$queries[] = array( 'method' => 'renameTable', 'params' => array( $definition['name'], $values['database_table_name'] ) );

					$schemaJson[ $values['database_table_name'] ] = $schemaJson[ $definition['name'] ];
					$schemaJson[ $values['database_table_name'] ]['name'] = $values['database_table_name'];
					unset( $schemaJson[ $definition['name'] ] );
					$definition['name'] = $values['database_table_name'];
				}

				/* Run queries */
				foreach ( $queries as $query )
				{
					/* Execute it */
					try
					{
						$method = $query['method'];
						$params = $query['params'];
						Db::i()->$method( ...$params );
					}
					catch ( DbException $e )
					{
						Output::i()->error( Member::loggedIn()->language()->addToStack('database_schema_error', FALSE, array( 'sprintf' => array( $e->query, $e->getCode(), $e->getMessage() ) ) ), '1C103/E', 403, '' );
					}

					/* Add it to the queries.json file */
					$queriesJson = $this->_addQueryToJson( $queriesJson, $query );
				}

				/* Write the json files if we've changed it */
				$changesMade = !empty( $queries );
				if ( $_schemaJson !== $schemaJson )
				{
					$this->_writeSchema( $schemaJson );
				}
				if ( $_queriesJson !== $queriesJson )
				{
					$this->_writeQueries( 'working', $queriesJson );
				}

				/* Redirect */
				Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'tab' => $activeTab ) ) );
			}

		}

		//-----------------------------------------
		// Columns
		//-----------------------------------------

		elseif ( $activeTab === 'columns' )
		{
			$output = new Custom( $definition['columns'], $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'existing' => 1, 'tab' => 'columns' ) ) );
			$output->langPrefix = 'database_column_';
			$output->include = array( 'name', 'type', 'length', 'unsigned', 'allow_null', 'default', 'auto_increment', 'comment' );
			$output->limit = 150;
			$output->sortBy = '';
			$output->rootButtons = array(
				'add'	=> array(
					'icon'	=> 'plus',
					'title'	=> 'database_columns_add',
					'link'	=> $this->url->setQueryString( array( 'do' => 'editSchemaColumn', '_name' => $definition['name'] ) ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('database_columns_add') )
				),
			);

			$url = $this->url;
			$output->rowButtons = function( $row ) use ( $definition, $url )
			{
				return array(
					'edit'	=> array(
						'icon'	=> 'pencil',
						'title'	=> 'edit',
						'link'	=> $url->setQueryString( array( 'do' => 'editSchemaColumn', '_name' => $definition['name'], 'column' => $row['name'] ) ),
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $row['name'] ),
						'hotkey'=> 'e'
					),
					'delete'	=> array(
						'icon'	=> 'times-circle',
						'title'	=> 'delete',
						'link'	=> $url->setQueryString( array( 'do' => 'editSchemaDeleteColumn', '_name' => $definition['name'], 'column' => $row['name'] ) )->csrf(),
						'data'	=> array( 'delete' => '' )
					)
				);
			};

			$boolParser = function( $val )
			{
				return $val ? '&#10003;' : '&#10007;';
			};
			$output->parsers = array(
				'length'		=> function( $val, $row )
				{
					if ( isset( $row['decimals'] ) AND $row['decimals'] )
					{
						return "{$row['length']},{$row['decimals']}";
					}
					if ( isset( $row['values'] ) AND $row['values'] )
					{
						return implode( '<br>', $row['values'] );
					}
					return $val;
				},
				'unsigned'		=> $boolParser,
				'allow_null'	=> $boolParser,
				'auto_increment'=> $boolParser,
			);

			$output = $output . Theme::i()->getTemplate('global')->message( 'database_schema_member_columns', 'warning', NULL, TRUE, TRUE );
		}

		//-----------------------------------------
		// Indexes
		//-----------------------------------------

		elseif ( $activeTab === 'indexes' )
		{
			$output = new Custom( $definition['indexes'], $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'existing' => 1, 'tab' => 'indexes' ) ) );
			$output->langPrefix = 'database_index_';
			$output->exclude	= array( 'length' );
			$output->limit      = 150;
			$output->rootButtons = array(
				'add'	=> array(
					'icon'	=> 'plus',
					'title'	=> 'database_indexes_add',
					'link'	=> $this->url->setQueryString( array( 'do' => 'editSchemaIndex', '_name' => $definition['name'] ) ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('database_indexes_add') )
				),
			);

			$appKey = $this->application->directory;
			$url = $this->url;
			$output->rowButtons = function( $row ) use ( $definition, $url )
			{
				return array(
					'edit'	=> array(
						'icon'	=> 'pencil',
						'title'	=> 'edit',
						'link'	=> $url->setQueryString( array( 'do' => 'editSchemaIndex', '_name' => $definition['name'], 'index' => $row['name'] ) ),
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $row['name'] ),
						'hotkey'=> 'e'
					),
					'delete'	=> array(
						'icon'	=> 'times-circle',
						'title'	=> 'delete',
						'link'	=> $url->setQueryString( array( 'do' => 'editSchemaDeleteIndex', '_name' => $definition['name'], 'index' => $row['name'] ) )->csrf(),
						'data'	=> array( 'delete' => '' )
					)
				);
			};

			$output->parsers = array(
				'type'		=> function( $val )
				{
					return mb_strtoupper( $val );
				},
				'columns'	=> function( $val, $data )
				{
					$output	= array();

					foreach( $data['columns'] as $_idx => $value )
					{
						$output[]	= $value . ' (' . (int) $data['length'][ $_idx ] . ')';
					}

					return implode( '<br>', $output );
				}
			);
		}

		//-----------------------------------------
		// Default Inserts
		//-----------------------------------------

		elseif ( $activeTab === 'inserts' )
		{
			$keys = [];
			if( isset( $definition['inserts'] ) )
			{
				foreach( $definition['inserts'] as $row )
				{
					$keys = array_keys( $row );
					break;
				}
			}

			$output = new Custom( $definition['inserts'] ?? array(), $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'existing' => 1, 'tab' => 'inserts' ) ) );
			$output->langPrefix = "&zwnj;";
			$output->limit      = 150;

			foreach( $keys as $key )
			{
				$output->parsers[ $key ] = function( $val )
				{
					if ( mb_strlen( $val ) > 100 )
					{
						return mb_substr( $val, 0, 97 ) . '...';
					}

					return $val;
				};
			}

			$output->rootButtons = array(
				'add'	=> array(
					'icon'	=> 'plus',
					'title'	=> 'database_inserts_add',
					'link'	=> $this->url->setQueryString( array( 'do' => 'editSchemaInsert', '_name' => $definition['name'] ) ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('database_inserts_add') )
				),
			);

			$url = $this->url;
			$output->rowButtons = function( $row, $k ) use ( $definition, $url )
			{
				return array(
					'edit'	=> array(
						'icon'	=> 'pencil',
						'title'	=> 'edit',
						'link'	=>  $url->setQueryString( array( 'do' => 'editSchemaInsert', '_name' => $definition['name'], 'row' => $k ) ),
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('database_inserts_edit') ),
						'hotkey'=> 'e'
					),
					'delete'	=> array(
						'icon'	=> 'times-circle',
						'title'	=> 'delete',
						'link'	=> $url->setQueryString( array( 'do' => 'editSchemaDeleteInsert', '_name' => $definition['name'], 'row' => $k ) )->csrf(),
						'data'	=> array( 'delete' => '' )
					)
				);
			};
		}

		//-----------------------------------------
		// Display
		//-----------------------------------------

		Output::i()->output = Theme::i()->getTemplate( 'global' )->tabs(
			array(
				'info'		=> 'database_table_settings',
				'columns'	=> 'database_columns',
				'indexes'	=> 'database_indexes',
				'inserts'	=> 'database_inserts'
			),
			$activeTab,
			(string) $output,
			$this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'existing' => 1 ) )
		);

		if( Request::i()->isAjax() )
		{
			if( Request::i()->existing )
			{
				Output::i()->output = $output;
			}
		}
	}

	/**
	 * Get definition from database, ignoring columns added by other apps
	 *
	 * @param	string	$name	Table name
	 * @return	array
	 */
	protected function _getTableDefinition( string $name ) : array
	{
		$definition = Db::i()->getTableDefinition( $name );
		foreach ( Application::applications() as $app )
		{
			$file = ROOT_PATH . "/applications/{$app->directory}/setup/install/queries.json";
			if ( file_exists( $file ) )
			{
				foreach( json_decode( file_get_contents( $file ), TRUE ) as $query )
				{
					if ( $query['method'] === 'addColumn' and $query['params'][0] === $definition['name'] )
					{
						unset( $definition['columns'][ $query['params'][1]['name'] ] );
					}
				}
			}
		}
		return $definition;
	}

	/**
	 * Edit Schema: Add/Edit Column
	 *
	 * @return	void
	 */
	protected function editSchemaColumn() : void
	{
		/* Get current column */
		$column = NULL;
		$schema = $this->_getSchema();
		$definition = $schema[ Request::i()->_name ];
		if ( Request::i()->column )
		{
			$column = $definition['columns'][ Request::i()->column ];
		}

		/* Build form */
		$form = new Form();
		$form->add( new Text( 'database_column_name', $column ? $column['name'] : '', TRUE, array( 'maxLength' => 64 ) ) );
		$form->add( new Select( 'database_column_type', $column ? $column['type'] : 'VARCHAR', TRUE, array(
			'options' 	=> Db::$dataTypes,
			'toggles'	=> array(
				'TINYINT'	=> array( 'database_column_unsigned', 'database_column_auto_increment', 'database_column_default' ),
				'SMALLINT'	=> array( 'database_column_unsigned', 'database_column_auto_increment', 'database_column_default' ),
				'MEDIUMINT'	=> array( 'database_column_unsigned', 'database_column_auto_increment', 'database_column_default' ),
				'INT'		=> array( 'database_column_unsigned', 'database_column_auto_increment', 'database_column_default' ),
				'BIGINT'	=> array( 'database_column_unsigned', 'database_column_auto_increment', 'database_column_default' ),
				'DECIMAL'	=> array( 'database_column_length', 'database_column_decimals', 'database_column_default' ),
				'FLOAT'		=> array( 'database_column_length', 'database_column_default' ),
				'BIT'		=> array( 'database_column_length', 'database_column_default' ),
				'DATE'		=> array( 'database_column_default' ),
				'DATETIME'	=> array( 'database_column_default' ),
				'TIMESTAMP'	=> array( 'database_column_default' ),
				'TIME'		=> array( 'database_column_default' ),
				'YEAR'		=> array( 'database_column_default' ),
				'CHAR'		=> array( 'database_column_length', 'database_column_default' ),
				'VARCHAR'	=> array( 'database_column_length', 'database_column_default' ),
				'BINARY'	=> array( 'database_column_length', 'database_column_default' ),
				'VARBINARY'	=> array( 'database_column_length', 'database_column_default' ),
				'TINYBLOB'	=> array(  ),
				'BLOB'		=> array(  ),
				'MEDIUMBLOB'=> array(  ),
				'BIGBLOB'	=> array(  ),
				'ENUM'		=> array( 'database_column_values', 'database_column_default' ),
				'SET'		=> array( 'database_column_values', 'database_column_default' ),
			),
		) ) );

		$form->add( new Number( 'database_column_length', ( $column and $column['length'] !== NULL ) ? $column['length'] : -1, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'no_value' ), NULL, NULL, NULL, 'database_column_length' ) );
		$form->add( new Number( 'database_column_decimals', ( $column and isset( $column['decimals'] ) ) ? $column['decimals'] : -1, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'no_value' ), NULL, NULL, NULL, 'database_column_decimals' ) );
		$form->add( new Stack( 'database_column_values', ( $column and isset( $column['values'] ) ) ? $column['values'] : NULL, FALSE, array(), NULL, NULL, NULL, 'database_column_values' ) );
		$form->add( new YesNo( 'database_column_allow_null', $column ? $column['allow_null'] : TRUE, TRUE ) );
		$form->add( new TextArea( 'database_column_default', $column ? $column['default'] : NULL, FALSE, array( 'nullLang' => 'NULL' ), NULL, NULL, NULL, 'database_column_default' ) );
		$form->add( new TextArea( 'database_column_comment', $column ? $column['comment'] : NULL, FALSE ) );
		$form->add( new YesNo( 'database_column_unsigned', $column ? $column['unsigned'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_column_unsigned' ) );
		$form->add( new YesNo( 'database_column_auto_increment', $column ? $column['auto_increment'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_column_auto_increment' ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Change -1 to NULL where appropriate */
			foreach ( array( 'database_column_length', 'database_column_decimals' ) as $k )
			{
				if ( $values[ $k ] === -1 )
				{
					$values[ $k ] = NULL;
				}
			}

			/* Check default value is a number, where it should be a number */
			if( !$values['database_column_allow_null'] AND empty( $values['database_column_default'] ) )
			{
				if( in_array( $values['database_column_type'], array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC' ) ) )
				{
					$values['database_column_default'] = 0;
				}
			}

			/* Get a column definition */
			$save = array();
			foreach ( $values as $k => $v )
			{
				/* If this is a new column and we have set the auto_increment flag, or if this is an existing column and the auto_increment
					flag was not previously set but we have toggled it on, then we need to add the primary key flag as well because MySQL
					requires any auto_increment column to also be a primary key */
				if( $k == 'database_column_auto_increment' AND $v AND ( !$column OR ( !$column['auto_increment'] ) ) )
				{
					$save['primary'] = true;
				}

				$save[ str_replace( 'database_column_', '', $k ) ] = $v;
			}

			/* Save */
			try
			{
				if ( $this->_schemaJsonIsWritable() !== true )
				{
					$form->error = Member::loggedIn()->language()->addToStack('dev_could_not_write_schema_data');
				}
				else
				{
					if ( !$column )
					{
						Db::i()->addColumn( $definition['name'], $save );
						$this->_writeQueries( 'working', $this->_addQueryToJson( $this->_getQueries( 'working' ), array( 'method' => 'addColumn', 'params' => array( $definition['name'], $save ) ) ) );
					}
					else
					{
						Db::i()->changeColumn( $definition['name'], $column['name'], $save );
						$this->_writeQueries( 'working', $this->_addQueryToJson( $this->_getQueries( 'working' ), array( 'method' => 'changeColumn', 'params' => array( $definition['name'],  $column['name'], $save ) ) ) );

						if ( $column['name'] != $save['name'] )
						{
							unset( $schema[ $definition['name'] ]['columns'][ $column['name'] ] );
						}
					}

					/* If we added the 'primary' flag, remove it before saving schema.json because it should not be reflected there...BUT we
						need to add primary key index definition to the schema.json instead in this case */
					if( isset( $save['primary'] ) )
					{
						unset( $save['primary'] );
						$schema[ $definition['name'] ]['columns'][ $save['name'] ] = $save;

						$schema[ $definition['name'] ]['indexes']['PRIMARY'] = array(
							'type'		=> 'primary',
							'name'		=> 'PRIMARY',
							'length'	=> array( 0 => NULL ),
							'columns'	=> array( 0 => $save['name'] )
						);
					}
					else
					{
						$schema[ $definition['name'] ]['columns'][ $save['name'] ] = $save;
					}

					/* Did we rename the column? */
					if( $column AND $save['name'] !== $column['name'] )
					{
						/* Fix references to the column name in indexes */
						if( isset( $schema[ $definition['name'] ]['indexes'] ) )
						{
							foreach( $schema[ $definition['name'] ]['indexes'] as $indexName => $indexDefinition )
							{
								foreach( $indexDefinition['columns'] as $_idx => $columnName )
								{
									if( $columnName == $column['name'] )
									{
										$schema[ $definition['name'] ]['indexes'][ $indexName ]['columns'][ $_idx ]	= $save['name'];
									}
								}
							}
						}

						/* Fix references to the column name in inserts */
						if( isset( $schema[ $definition['name'] ]['inserts'] ) )
						{
							foreach( $schema[ $definition['name'] ]['inserts'] as $_idx => $insert )
							{
								$insert[ $save['name'] ] = $insert[ $column['name'] ];
								unset( $insert[ $column['name'] ] );

								$schema[ $definition['name'] ]['inserts'][ $_idx ] = $insert;
							}
						}
					}

					$this->_writeSchema( $schema );

					Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'tab' => 'columns' ) ) );
				}
			}
			catch ( Exception $e )
			{
				$form->error = $e->getMessage();
			}
		}

		/* Display */
		Output::i()->output = $form;
	}

	/**
	 * Edit Schema: Delete Column
	 *
	 * @return	void
	 */
	protected function editSchemaDeleteColumn() : void
	{
		Session::i()->csrfCheck();

		try
		{
			if ( $this->_schemaJsonIsWritable() !== true )
			{
				throw new Exception('dev_could_not_write_schema_data');
			}

			Db::i()->dropColumn( Request::i()->_name, Request::i()->column );
			$this->_writeQueries( 'working', $this->_addQueryToJson( $this->_getQueries( 'working' ), array( 'method' => 'dropColumn', 'params' => array( Request::i()->_name, Request::i()->column ) ) ) );

			$schema = $this->_getSchema();
			unset( $schema[ Request::i()->_name ]['columns'][ Request::i()->column ] );

			/* Do any indexes use this column? */
			if ( isset( $schema[ Request::i()->_name ]['indexes'] ) and is_array( $schema[ Request::i()->_name ]['indexes'] ) )
			{
				foreach( $schema[ Request::i()->_name ]['indexes'] as $name => $definition )
				{
					$changed = false;

					foreach( $definition['columns'] as $id => $colName )
					{
						if ( $colName === Request::i()->column )
						{
							unset( $definition['columns'][ $id ] );
							unset( $definition['length'][ $id ] );

							$changed = true;
						}
					}

					/* Still have columns? */
					if ( ! count( $definition['columns'] ) )
					{
						/* Remove the index from schema.json, MySQL will do this automatically */
						unset( $schema[ Request::i()->_name ]['indexes'][ $name ] );
					}
					else if ( $changed )
					{
						/* Alter it */
						Db::i()->changeIndex( Request::i()->_name, $name, $definition );

						$schema[ Request::i()->_name ]['indexes'][ $name ] = $definition;

						$this->_writeQueries( 'working', $this->_addQueryToJson( $this->_getQueries( 'working' ), array( 'method' => 'changeIndex', 'params' => array( $name,  $name, $definition ) ) ) );
					}
				}
			}

			$this->_writeSchema( $schema );

			if( Request::i()->isAjax() )
			{
				Output::i()->output = 1;
				return;
			}

			Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => Request::i()->_name, 'tab' => 'columns' ) ) );
		}
		catch ( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '1C103/N', 403, '' );
		}
	}

	/**
	 * Edit Schema: Add/Edit Index
	 *
	 * @return	void
	 */
	protected function editSchemaIndex() : void
	{
		/* Get current index */
		$maxIndexLength = 250;
		$index  = NULL;
		$schema = $this->_getSchema();
		$definition = $schema[ Request::i()->_name ];
		if ( Request::i()->index )
		{
			$index = $definition['indexes'][ Request::i()->index ];
		}

		/* Build form */
		$form = new Form();
		$form->add( new Select( 'database_index_type', $index ? $index['type'] : 'key', TRUE, array( 'options' => array(
			'primary'	=> 'PRIMARY',
			'unique'	=> 'UNIQUE',
			'fulltext'	=> 'FULLTEXT',
			'key'		=> 'KEY'
		) ) ) );
		$form->add( new Text( 'database_index_name', $index ? $index['name'] : NULL, TRUE, array( 'maxLength' => 64 ) ) );
		$form->add( new Stack( 'database_index_columns', $index ? $index['columns'] : array(), TRUE, array(
			'options'			=> array_combine( array_keys( $definition['columns'] ), array_keys( $definition['columns'] ) ),
			'parse'				=> 'normal',
			'stackFieldType'	=> 'Select',
		) ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Get a definition */
			$save = array();
			foreach ( $values as $k => $v )
			{
				$save[ str_replace( 'database_index_', '', $k ) ] = $v;
			}

			foreach( $save['columns'] as $id => $field )
			{
				if ( isset( $definition['columns'][ $field ] ) )
				{
					if ( ( mb_substr( mb_strtolower( $definition['columns'][ $field ]['type'] ), -4 ) === 'text' ) OR ( ! empty( $definition['columns'][ $field ]['length'] ) AND is_integer( $definition['columns'][ $field ]['length']) AND $definition['columns'][ $field ]['length'] > $maxIndexLength ) )
					{
						$save['length'][ $id ] = $maxIndexLength;
					}
				}

				if ( ! isset( $save['length'][ $id ] ) )
				{
					$save['length'][ $id ] = null;
				}
			}

			/* Save */
			try
			{
				if ( $this->_schemaJsonIsWritable() !== true )
				{
					$form->error = Member::loggedIn()->language()->addToStack('dev_could_not_write_schema_data');
				}
				else
				{
					if ( !$index )
					{
						Db::i()->addIndex( $definition['name'], $save );
						$this->_writeQueries( 'working', $this->_addQueryToJson( $this->_getQueries( 'working' ), array( 'method' => 'addIndex', 'params' => array( $definition['name'], $save ) ) ) );
					}
					else
					{
						Db::i()->changeIndex( $definition['name'], $index['name'], $save );
						$this->_writeQueries( 'working', $this->_addQueryToJson( $this->_getQueries( 'working' ), array( 'method' => 'changeIndex', 'params' => array( $definition['name'],  $index['name'], $save ) ) ) );

						if ( $index['name'] != $save['name'] )
						{
							unset( $schema[ $definition['name'] ]['indexes'][ $index['name'] ] );
						}
					}
					$schema[ $definition['name'] ]['indexes'][ $save['name'] ] = $save;

					$this->_writeSchema( $schema );

					if( Request::i()->isAjax() )
					{
						Output::i()->output = 1;
						return;
					}

					Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'tab' => 'indexes' ) ) );
				}
			}
			catch ( Exception $e )
			{
				$form->error = $e->getMessage();
			}
		}

		/* Display */
		Output::i()->output = $form;
	}

	/**
	 * Edit Schema: Delete Index
	 *
	 * @return	void
	 */
	protected function editSchemaDeleteIndex() : void
	{
		Session::i()->csrfCheck();

		try
		{
			if ( $this->_schemaJsonIsWritable() !== true )
			{
				throw new Exception('dev_could_not_write_schema_data');
			}

			Db::i()->dropIndex( Request::i()->_name, Request::i()->index );
			$this->_writeQueries( 'working', $this->_addQueryToJson( $this->_getQueries( 'working' ), array( 'method' => 'dropIndex', 'params' => array( Request::i()->_name, Request::i()->index ) ) ) );

			$schema = $this->_getSchema();
			unset( $schema[ Request::i()->_name ]['indexes'][ Request::i()->index ] );
			$this->_writeSchema( $schema );

			Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => Request::i()->_name, 'tab' => 'indexes' ) ) );
		}
		catch ( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '1C103/O', 403, '' );
		}
	}

	/**
	 * Edit Schema: Add/Edit Insert Row
	 *
	 * @return	void
	 */
	protected function editSchemaInsert() : void
	{
		/* Get current row */
		$index = NULL;
		$schema = $this->_getSchema();
		$definition = $schema[ Request::i()->_name ];
		$data = array();
		if ( isset( Request::i()->row ) )
		{
			$index = Request::i()->row;
			$data = $definition['inserts'][ Request::i()->row ];
		}

		/* Build form */
		$form = new Form();
		foreach ( $definition['columns'] as $column )
		{
			if ( array_key_exists( $column['type'], Db::$dataTypes['database_column_type_numeric'] ) and $column['type'] !== 'BIT' )
			{
				$min = NULL;
				$max = NULL;

				switch ( $column['type'] )
				{
					case 'TINYINT':
						$min = $column['unsigned'] ? 0 : -128;
						$max = $column['unsigned'] ? 255 : 127;
						break;

					case 'SMALLINT':
						$min = $column['unsigned'] ? 0 : -32768;
						$max = $column['unsigned'] ? 65535 : 32767;
						break;

					case 'MEDIUMINT':
						$min = $column['unsigned'] ? 0 : -8388608;
						$max = $column['unsigned'] ? 16777215 : 8388607;
						break;

					case 'INT':
					case 'INTEGER':
						$min = $column['unsigned'] ? 0 : -2147483648;
						$max = $column['unsigned'] ? 4294967295 : 2147483647;
						break;

					case 'BIGINT':
						$min = $column['unsigned'] ? 0 : -9223372036854775808;
						$max = $column['unsigned'] ? 18446744073709551615 : 9223372036854775807;
						break;
				}

				$options = array();
				if ( $column['allow_null'] or $column['auto_increment'] )
				{
					//$options = array( 'unlimited' => 'NULL', 'unlimitedLang' => 'NULL' );
					$options = array( 'nullLang' => 'NULL' );
				}
				if ( isset( $column['decimals'] ) )
				{
					$options['decimals'] = $column['decimals'];
				}

				/*if ( isset( $data[ $column['name'] ] ) and $data[ $column['name'] ] === NULL )
				{
					$data[ $column['name'] ] = 'NULL';
				}*/

				$options['min']	= NULL;

				$value = NULL;
				if ( $data and isset( $data[ $column['name'] ] ) )
				{
					$value = $data[ $column['name'] ];
				}
				else
				{
					$value = ( $column['auto_increment'] or $column['default'] === NULL ) ? NULL : $column['default'];
				}

				$element = new Text( $column['name'], $value, FALSE, $options );
			}
			elseif ( in_array( $column['type'], array( 'CHAR', 'VARCHAR', 'BINARY', 'VARBINARY' ) ) )
			{
				$element = new Text( $column['name'], ( $data AND isset( $data[ $column['name'] ] ) ) ? $data[ $column['name'] ] : $column['default'], FALSE, $column['allow_null'] ? array( 'nullLang' => 'NULL' ) : array() );
			}
			elseif ( in_array( $column['type'], array( 'TEXT', 'MEDIUMTEXT', 'BIGTEXT', 'TINYBLOB', 'BLOB', 'MEDIUMBLOB', 'BIGBLOB' ) ) )
			{
				$element = new TextArea( $column['name'], ( $data AND isset( $data[ $column['name'] ] ) ) ? $data[ $column['name'] ] : $column['default'], FALSE, $column['allow_null'] ? array( 'nullLang' => 'NULL' ) : array() );
			}
			elseif ( $column['type'] === 'ENUM' )
			{
				$element = new Select( $column['name'], ( $data AND isset( $data[ $column['name'] ] ) ) ? $data[ $column['name'] ] : $column['default'], FALSE, array( 'options' => array_combine( $column['values'], $column['values'] ) ) );
			}
			elseif ( $column['type'] === 'SET' )
			{
				$element = new Select( $column['name'], ( $data AND isset( $data[ $column['name'] ] ) ) ? explode( ',', $data[ $column['name'] ] ) : explode( ',', $column['default'] ), FALSE, array( 'options' => array_combine( $column['values'], $column['values'] ), 'multiple' => TRUE ) );
			}
			else
			{
				$element = new Text( $column['name'], ( $data AND isset( $data[ $column['name'] ] ) ) ? $data[ $column['name'] ] : $column['default'], FALSE, $column['allow_null'] ? array( 'nullLang' => 'NULL' ) : array() );
			}

			$element->label = $column['name'];
			$form->add( $element );
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			foreach ( $definition['columns'] as $column )
			{
				if ( array_key_exists( $column['type'], Db::$dataTypes['database_column_type_numeric'] ) and $column['type'] !== 'BIT' and $values[ $column['name'] ] === 'NULL' )
				{
					$values[ $column['name'] ] = NULL;
				}
			}

			try
			{
				if ( $this->_schemaJsonIsWritable() !== true )
				{
					$form->error = Member::loggedIn()->language()->addToStack('dev_could_not_write_schema_data');
				}
				else
				{
					if ( $index !== NULL )
					{
						$schema[ $definition['name'] ]['inserts'][ $index ] = $values;
					}
					else
					{
						Db::i()->insert( $definition['name'], $values );
						$schema[ $definition['name'] ]['inserts'][] = $values;
					}

					$this->_writeSchema( $schema );

					Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $definition['name'], 'tab' => 'inserts' ) ) );
				}
			}
			catch ( Exception $e )
			{
				$form->error = $e->getMessage();
			}
		}

		/* Display */
		Output::i()->output = $form;
	}

	/**
	 * Edit Schema: Delete Insert
	 *
	 * @return	void
	 */
	protected function editSchemaDeleteInsert() : void
	{
		Session::i()->csrfCheck();

		try
		{
			if ( $this->_schemaJsonIsWritable() !== true )
			{
				throw new Exception('dev_could_not_write_schema_data');
			}

			$schema = $this->_getSchema();
			unset( $schema[ Request::i()->_name ]['inserts'][ Request::i()->row ] );
			$this->_writeSchema( $schema );

			Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => Request::i()->_name, 'tab' => 'inserts' ) ) );
		}
		catch ( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '1C103/P', 403, '' );
		}
	}

	/**
	 * Show Schema
	 *
	 * @return	void
	 */
	protected function showSchema() : void
	{
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block(
			Request::i()->_name,
			str_replace( '&lt;?php', '', highlight_string( "<?php " . var_export(Db::i()->getTableDefinition( Request::i()->_name ), TRUE ), TRUE ) ),
			FALSE
		);
	}

	/**
	 * Resolve Schema Conflicts
	 *
	 * @return	void
	 */
	protected function resolveSchemaConflicts() : void
	{
		Session::i()->csrfCheck();

		/* Get table definitions */
		$schema = $this->_getSchema();
		if ( !isset( $schema[ Request::i()->_name ] ) )
		{
			Output::i()->error( 'node_error', '2C103/G', 404, '' );
		}
		$schemaDefinition = $schema[ Request::i()->_name ];
		$localDefinition = $this->_getTableDefinition( $schemaDefinition['name'] );

		/* Use local database */
		if ( Request::i()->local )
		{
			foreach ( $localDefinition['columns'] as $i => $data )
			{
				if ( $data['type'] == 'BIT' )
				{
					$localDefinition['columns'][ $i ]['default'] = intval( preg_replace( "/^b'(\d+?)'\$/", '$1', $data['default'] ) );
				}
			}
			$schema[ Request::i()->_name ] = $localDefinition;
			if ( isset( $schemaDefinition['inserts'] ) )
			{
				$schema[ Request::i()->_name ]['inserts'] = $schemaDefinition['inserts'];
			}
			if ( isset( $schemaDefinition['engine'] ) )
			{
				$schema[ Request::i()->_name ]['engine'] = $schemaDefinition['engine'];
			}
			else
			{
				unset( $schema[ Request::i()->_name ]['engine'] );
			}

			if( isset( $schemaDefinition['reporting'] ) )
			{
				$schema[ Request::i()->_name ]['reporting'] = $schemaDefinition['reporting'];
			}

			$this->_writeSchema( $schema );
		}
		/* Use schema file */
		else
		{
			/* Create a new table */
			$_newTable = $schemaDefinition;
			$_newTable['name'] = $_newTable['name'] . '_temp';
			Db::i()->createTable( $_newTable );

			/* Work out our columns */
			$columns = array();
			foreach ( array_keys( $schemaDefinition['columns'] ) as $column )
			{
				if ( isset( $localDefinition['columns'][ $column ] ) )
				{
					$columns[] = $column;
				}
			}
			$columns = implode( ',', array_map( function( $v ){ return "`{$v}`"; }, $columns ) );

			/* Insert the rows */
			if ( !empty( $columns ) )
			{
				Db::i()->query( 'INSERT IGNORE INTO `' . Db::i()->prefix . $_newTable['name'] . "` ( {$columns} ) SELECT {$columns} FROM `" . Db::i()->prefix . $schemaDefinition['name'] . '`' );
			}

			/* Drop the old table */
			Db::i()->dropTable( $schemaDefinition['name'] );

			/* Rename the new table */
			Db::i()->renameTable( $_newTable['name'], $schemaDefinition['name'] );
		}

		/* Redirect */
		Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'editSchema', '_name' => $schemaDefinition['name'] ) ) );
	}

	/**
	 * Delete Table
	 *
	 * @return	void
	 */
	protected function deleteTable() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		$table = Request::i()->name;

		/* Drop the table */
		Db::i()->dropTable( $table, TRUE );

		/* Add the drop to the queries.json file */
		$queries = $this->_getQueries( 'working' );
		$queries = $this->_addQueryToJson( $queries, array( 'method' => 'dropTable', 'params' => array( $table, TRUE ) ) );
		$this->_writeQueries( 'working', $queries );

		/* Remove from schema.json */
		$schemaJson = $this->_getSchema();
		unset( $schemaJson[ $table ] );
		$this->_writeSchema( $schemaJson );

		/* And redirect */
		Output::i()->redirect( $this->url );
	}

	/**
	 * Get schema.json
	 *
	 * @return	array
	 */
	protected function _getSchema() : array
	{
		return $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/schema.json" );
	}

	/**
	 * Write schema.json file
	 *
	 * @param	array	$json	Data
	 * @return	void
	 */
	protected function _writeSchema( array $json ) : void
	{
		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/schema.json", $json );
	}

	/**
	 * Checks to see if schema JSON is writeable
	 *
	 * @return boolean
	 */
	protected function _schemaJsonIsWritable() : bool
	{
		if ( !is_writable( ROOT_PATH . "/applications/{$this->application->directory}/data/schema.json" ) )
		{
			return false;
		}

		$file = ROOT_PATH . "/applications/{$this->application->directory}/setup/upg_working/queries.json";
		if ( file_exists( $file ) and !is_writable( $file ) )
		{
			return false;
		}

		return true;
	}

	/**
	 * Add a query to a queries.json file, looking for CREATE TABLE statements and
	 * adjusting those instead if necessary
	 *
	 * @param	array	$queriesJson	Decoded queries.json file
	 * @param	array	$query			The query to add
	 * @return	array	Decoded queries.json file, modified as necessary
	 */
	protected function _addQueryToJson( array $queriesJson, array $query ) : array
	{
		$added = FALSE;

		$tableName = NULL;
		switch ( $query['method'] )
		{
			case 'renameTable':
			case 'dropTable':
			case 'addColumn':
			case 'changeColumn':
			case 'dropColumn':
			case 'addIndex':
			case 'changeIndex':
			case 'dropIndex':
				$tableName = $query['params'][0];
				break;
		}

		if ( $tableName !== NULL )
		{
			foreach ( $queriesJson as $i => $q )
			{
				if ( $q['method'] === 'createTable' and $q['params'][0]['name'] === $tableName )
				{
					switch ( $query['method'] )
					{
						case 'renameTable':
							$queriesJson[ $i ]['params'][0]['name'] = $query['params'][1];
							$added = TRUE;
							break;

						case 'dropTable':
							unset( $queriesJson[ $i ] );
							$added = TRUE;
							break;

						case 'addColumn':
							$queriesJson[ $i ]['params'][0]['columns'][ $query['params'][1]['name'] ] = $query['params'][1];
							$added = TRUE;
							break;

						case 'changeColumn':
							unset( $queriesJson[ $i ]['params'][0]['columns'][ $query['params'][1] ] );
							$queriesJson[ $i ]['params'][0]['columns'][ $query['params'][2]['name'] ] = $query['params'][2];

							/* Fix references to the column name in indexes */
							if ( isset( $queriesJson[ $i ]['params'][0]['indexes'] ) )
							{
								foreach( $queriesJson[ $i ]['params'][0]['indexes'] as $indexName => $indexDefinition )
								{
									foreach( $indexDefinition['columns'] as $_idx => $columnName )
									{
										if( $columnName == $query['params'][1] )
										{
											$queriesJson[ $i ]['params'][0]['indexes'][ $indexName ]['columns'][ $_idx ] = $query['params'][2]['name'];
										}
									}
								}
							}
							$added = TRUE;
							break;

						case 'dropColumn':
							unset( $queriesJson[ $i ]['params'][0]['columns'][ $query['params'][1] ] );
							$added = TRUE;
							break;

						case 'addIndex':
							$queriesJson[ $i ]['params'][0]['indexes'][ $query['params'][1]['name'] ] = $query['params'][1];
							$added = TRUE;
							break;

						case 'changeIndex':
							unset( $queriesJson[ $i ]['params'][0]['indexes'][ $query['params'][1] ] );
							$queriesJson[ $i ]['params'][0]['indexes'][ $query['params'][2]['name'] ] = $query['params'][2];
							$added = TRUE;
							break;

						case 'dropIndex':
							unset( $queriesJson[ $i ]['params'][0]['indexes'][ $query['params'][1] ] );
							$added = TRUE;
							break;
					}
				}
			}
		}

		if ( $added === FALSE )
		{
			$queriesJson[] = $query;
		}

		return $queriesJson;
	}
}