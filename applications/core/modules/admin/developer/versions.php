<?php
/**
 * @brief		versions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use DirectoryIterator;
use DomainException;
use InvalidArgumentException;
use IPS\Db\Exception as DbException;
use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Tree\Tree;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Xml\SimpleXML;
use ParseError;
use function defined;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * versions
 */
class versions extends Controller
{
	/**
	 * @var bool
	 */
	public static bool $csrfProtected = true;

	/**
	 * @var array
	 */
	protected array $json = [];

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Create the file if it doesn't exist */
		$this->json = $this->_getVersions();

		/* Build node tree */
		$appKey = $this->application->directory;
		$url = $this->url;
		$tree = new Tree(
			$this->url,
			'dev_versions',
			/* Get Roots */
			array( $this, '_getVersionRows' ),
			/* Get Row */
			array( $this, '_getVersionRow' ),
			/* Get Row's Parent ID */
			function( $id )
			{
				return NULL;
			},
			/* Get Children */
			array( $this, '_getQueriesRows' ),
			/* Get Root Buttons */
			function() use ( $appKey, $url )
			{
				return array(
					'add'	=> array(
						'icon'	=> 'plus',
						'title'	=> 'versions_add',
						'link'	=> $url->setQueryString( array( 'do' => 'addVersion' ) ),
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('versions_add') )
					)
				);
			},
			FALSE,
			FALSE,
			TRUE
		);

		Output::i()->output = (string) $tree;
	}

	/**
	 * @return void
	 */
	protected function reorder() : void
	{
		Session::i()->csrfCheck();

		/* Normalise AJAX vs non-AJAX */
		if( isset( Request::i()->ajax_order ) )
		{
			$order = array();
			$position = array();
			foreach( Request::i()->ajax_order as $id => $parent )
			{
				if ( !isset( $order[ $parent ] ) )
				{
					$order[ $parent ] = array();
					$position[ $parent ] = 1;
				}
				$order[ $parent ][ $id ] = $position[ $parent ]++;
			}
		}
		/* Non-AJAX way */
		else
		{
			$order = array( Request::i()->root ?: 'null' => Request::i()->order );
		}

		/* Work out */
		$queries = array();
		$write = array();
		foreach ( $order as $versionKey => $keys )
		{
			if ( $versionKey != 'null' )
			{
				asort( $keys );
				foreach ( $keys as $key => $position )
				{
					$versionNumber = mb_substr( $key, 0, mb_strpos( $key, '.' ) );
					if ( !isset( $queries[ $versionNumber ] ) )
					{
						$queries[ $versionNumber ] = $this->_getQueries( $versionNumber );
					}
					$write[ $versionNumber ][] = $queries[ $versionNumber ][ mb_substr( $key, mb_strpos( $key, '.' ) + 1 ) ];
				}
			}
		}
		foreach ( $write as $versionNumber => $queries )
		{
			$this->_writeQueries( $versionNumber, $queries );
		}
	}

	/**
	 * Get all version rows
	 *
	 * @return	array
	 */
	public function _getVersionRows() : array
	{
		$rows = array( 'install' => $this->_getVersionRow( 'install' ) );
		foreach ( $this->json as $long => $human )
		{
			array_unshift( $rows, $this->_getVersionRow( $long ) );
		}
		array_unshift( $rows, $this->_getVersionRow( 'working' ) );
		return $rows;
	}

	/**
	 * Get individual version row
	 *
	 * @param	int|string		$long	Version ID
	 * @param	bool	$root	Format this as the root node?
	 * @return	string
	 */
	public function _getVersionRow( int|string $long, bool $root=FALSE ) : string
	{
		$dir = ROOT_PATH . "/applications/{$this->application->directory}/setup/" . ( $long === 'install' ? $long : "upg_{$long}" );

		$hasChildren = FALSE;
		if ( is_dir( $dir ) )
		{
			foreach ( new DirectoryIterator( $dir ) as $file )
			{
				if ( !$file->isDot() and mb_substr( $file, 0, 1 ) !== '.' and $file != 'index.html' )
				{
					$hasChildren = TRUE;
					break;
				}
			}
		}

		$buttons = array();

		if( $long != 'install' )
		{
			$buttons['phpcode'] = array(
				'icon'		=> 'code',
				'title'		=> 'versions_code',
				'link'		=> $this->url->setQueryString( array( 'do' => 'versionCode', 'id' => $long ) )->csrf()
			);
		}

		$buttons['add'] = array(
			'icon'		=> 'plus-circle',
			'title'		=> 'versions_query',
			'link'		=> $this->url->setQueryString( array( 'do' => 'addVersionQuery', 'id' => $long ) ),
			'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('versions_query'), 'ipsDialog-remoteVerify' => "false" )
		);

		if( $long != 'install' and $long !== 'working' )
		{
			$buttons['delete']	= array(
				'icon'		=> 'times-circle',
				'title'		=> 'delete',
				'link'		=> $this->url->setQueryString( array( 'do' => 'deleteVersion', 'id' => $long ) ),
				'data'		=> array( 'delete' => '' )
			);
		}

		if ( $long === 'install' )
		{
			$nameToDisplay = Member::loggedIn()->language()->addToStack('versions_install');
		}
		elseif ( $long === 'working' )
		{
			$nameToDisplay = Member::loggedIn()->language()->addToStack('versions_working');
		}
		else
		{
			$nameToDisplay = $this->json[$long] ?? $long;
		}

		return Theme::i()->getTemplate( 'trees', 'core' )->row( $this->url, $long, $nameToDisplay, $hasChildren, $buttons, $long, NULL, NULL, $root );
	}

	/**
	 * Get versions
	 *
	 * @return	array
	 */
	protected function _getVersions() : array
	{
		$result = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/versions.json" );
		ksort( $result );

		return $result;
	}

	/**
	 * Write versions.json file
	 *
	 * @param	array	$json	Data
	 * @return	void
	 */
	protected function _writeVersions( array $json ) : void
	{
		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/versions.json", $json );
	}

	/**
	 * Create a PHP class for a version
	 *
	 * @return	void
	 */
	protected function versionCode() : void
	{
		Session::i()->csrfCheck();

		/* Get version */
		if ( Request::i()->id !== 'working' )
		{
			$long = intval( Request::i()->id );
			$json = $this->_getVersions();
			if ( !isset( $json[ $long ] ) )
			{
				Output::i()->error( 'node_error', '2C103/8', 404, '' );
			}
			$human = $json[ $long ];
		}
		else
		{
			$long = 'working';
			$human = '{version_human}';
		}

		/* Write the file if we don't already have one */
		$phpFilePath = ROOT_PATH . "/applications/{$this->application->directory}/setup/upg_{$long}";
		$phpFile = $phpFilePath . '/upgrade.php';
		if ( !file_exists( $phpFile ) )
		{
			/* Work out the contents */
			$contents = str_replace(
				array(
					'{version_human}',
					"{subpackage}",
					'{date}',
					'{app}',
					'{version_long}',
				),
				array(
					$human,
					( $this->application->directory != 'core' ) ? ( " * @subpackage\t" . Member::loggedIn()->language()->get( "__app_{$this->application->directory}" ) ) : '',
					date( 'd M Y' ),
					$this->application->directory,
					$long,
				),
				file_get_contents( ROOT_PATH . "/applications/core/data/defaults/Upgrade.txt" )
			);

			/* If this isn't an IPS app, strip out our header */
			if ( !in_array( $this->application->directory, IPS::$ipsApps ) )
			{
				$contents = preg_replace( '/(<\?php\s)\/*.+?\*\//s', '$1', $contents );
			}

			/* Write */
			if ( !is_dir( $phpFilePath ) )
			{
				mkdir( $phpFilePath );
				chmod( $phpFilePath, IPS_FOLDER_PERMISSION );
			}
			if( @file_put_contents( $phpFile, $contents ) === FALSE )
			{
				Output::i()->error( 'dev_could_not_write_setup', '1C103/9', 403, '' );
			}
		}

		/* And redirect */
		Output::i()->redirect( $this->url, 'file_created' );
	}

	/**
	 * Versions: Add Version
	 *
	 * @return	void
	 */
	protected function addVersion() : void
	{
		/* Load existing versions.json file */
		$json = $this->_getVersions();

		/* Get form */
		$activeTab = Request::i()->tab ?: 'new';
		$form = new Form( 'versions_add' );
		switch ( $activeTab )
		{
			/* Create New */
			case 'new':
				$form->addMessage( 'versions_add_information' );
				$form->add( new Text( 'versions_human', NULL, TRUE, array( 'placeholder' => '1.0.0' ), function( $val )
				{
					if ( !preg_match( '/^([0-9]+\.[0-9]+\.[0-9]+)/', $val ) )
					{
						throw new DomainException( 'versions_human_bad' );
					}
				} ) );
				$form->add( new Text( 'versions_long', NULL, TRUE, array( 'placeholder' => '10000' ), function( $val ) use ( $json )
				{
					if ( !preg_match( '/^\d*$/', $val ) )
					{
						throw new DomainException( 'form_number_bad' );
					}
					if( isset( $json[ $val ] ) )
					{
						throw new DomainException( 'versions_long_exists' );
					}
				} ) );
				break;

			/* Upload */
			case 'upload':
				$appKey = $this->application->directory;
				$form->add( new Upload(
					'upload',
					NULL,
					TRUE,
					array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE )
				) );

				break;
		}

		/* Has the form been submitted? */
		if( $values = $form->values() )
		{
			/* Add values */
			$toAdd = array();
			switch ( $activeTab )
			{
				/* New Version */
				case 'new':
					$json[ $values['versions_long'] ] = $values['versions_human'];

					/* If this was for the core, add it to all IPS apps */
					if ( $this->application->directory === 'core' )
					{
						foreach ( IPS::$ipsApps as $_appKey )
						{
							$appJson = $this->_getJson( ROOT_PATH . "/applications/{$_appKey}/data/versions.json" );
							$appJson[ $values['versions_long'] ] = $values['versions_human'];
							$this->_writeJson( ROOT_PATH . "/applications/{$_appKey}/data/versions.json", $appJson );
						}
					}

					break;

				/* Uploaded versions.xml file */
				case 'upload':
					$xml = NULL;
					try
					{
						$xml = SimpleXML::loadFile( $values['upload'] );
					}
					catch ( InvalidArgumentException $e ) {}

					if ( !$xml or $xml->getName() !== 'versions' )
					{
						Output::i()->redirect( $this->url, 'versions_upload_badxml' );
					}

					foreach ( $xml as $version )
					{
						$json[ (int) $version->long ] = (string) $version->human;
					}
					unlink( $values['upload'] );
					break;
			}

			/* Save a snapshot of the default theme for diffs */
			if ( $this->application->directory === 'core' )
			{
				Theme::master()->saveHistorySnapshot();
			}

			/* Save it */
			$this->_writeVersions( $json );

			/* Redirect */
			Output::i()->redirect( $this->url );
		}

		if( Request::i()->isAjax() and $activeTab == 'upload' )
		{
			Output::i()->output = $form;
			return;
		}



		/* If not, show it */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->tabs(
			array(
				'new'		=> 'versions_add_new',
				'upload'	=> 'versions_add_upload',
			),
			$activeTab,
			$form,
			$this->url->setQueryString( array( 'do' => 'addVersion' ) )
		);

		if( Request::i()->isAjax() )
		{
			if( Request::i()->existing )
			{
				Output::i()->output = $form;
			}
		}
	}

	/**
	 * Delete Version
	 *
	 * @return	void
	 */
	protected function deleteVersion() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		$versionsFile = ROOT_PATH . "/applications/{$this->application->directory}/data/versions.json";
		$json = $this->_getVersions();
		if ( isset( $json[ intval( Request::i()->id ) ] ) )
		{
			unset( $json[ intval( Request::i()->id ) ] );
		}
		$this->_writeVersions( $json );
		Output::i()->redirect( $this->url );
	}

	/**
	 * Add version query
	 *
	 * @return	void
	 */
	protected function addVersionQuery() : void
	{
		/* Build Form */
		$form = new Form( 'add_version_query' );
		$form->add( new TextArea( 'versions_query_code', '\IPS\Db::i()->', TRUE, array( 'size' => 45 ), function( $val )
		{
			/* Check it starts with \IPS\Db::i()-> */
			$val = trim( $val );
			if ( mb_substr( $val, 0, 14 ) !== '\IPS\Db::i()->' )
			{
				throw new DomainException( 'versions_query_start' );
			}

			/* Check there's only one query */
			if ( mb_substr( $val, -1 ) !== ';' )
			{
				$val .= ';';
			}
			if ( mb_substr_count( $val, ';' ) > 1 )
			{
				throw new DomainException( 'versions_query_one' );
			}

			/* Check our Regex will be okay with it */
			preg_match( '/^\\\IPS\\\Db::i\(\)->(.+?)\(\s*[\'"](.+?)[\'"]\s*(,\s*(.+?))?\)\s*;$/', $val, $matches );
			if ( empty( $matches ) )
			{
				throw new DomainException( 'versions_query_format' );
			}

			/* Run it if we're adding it to the current working version */
			if( Request::i()->id == 'working' )
			{
				try
				{
					try
					{
						if ( @eval( $val ) === FALSE )
						{
							throw new DomainException( 'versions_query_phperror' );
						}
					}
					catch ( ParseError $e )
					{
						throw new DomainException( 'versions_query_phperror' );
					}
				}
				catch ( DbException $e )
				{
					throw new DomainException( $e->getMessage() );
				}
			}
		} ) );

		/* If submitted, add to json file */
		if ( $values = $form->values() )
		{
			/* Get our file */
			$version = Request::i()->id;
			$json = $this->_getQueries( $version );

			/* Work out the different parts of the query */
			$val = trim( $values['versions_query_code'] );
			if ( mb_substr( $val, -1 ) !== ';' )
			{
				$val .= ';';
			}
			preg_match( '/^\\\IPS\\\Db::i\(\)->(.+?)\(\s*(.+?)\s*\)\s*;$/', $val, $matches );

			/* Add it on */
			$json[] = array( 'method' => $matches[1], 'params' => eval( 'return array( ' . $matches[2] . ' );' ) );

			/* Write it */
			$this->_writeQueries( $version, $json );

			/* Redirect us */
			Output::i()->redirect( $this->url->setQueryString( 'root', $version ) );
		}

		/* Or display it */
		else
		{
			Output::i()->output .= Theme::i()->getTemplate( 'global' )->block( 'versions_query', $form, FALSE );
		}
	}

	/**
	 * Delete Version Query
	 *
	 * @return	void
	 */
	protected function deleteVersionQuery() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		$version = ( Request::i()->version == 'install' ? 'install' : Request::i()->version );

		$json = $this->_getQueries( $version );
		unset( $json[ intval( Request::i()->query ) ] );
		$this->_writeQueries( $version, $json );
		Output::i()->redirect( $this->url->setQueryString( 'root', $version ) );
	}

	/**
	 * Get the queries rows for a version
	 *
	 * @param	int|string		$long	Version ID
	 * @return	array
	 */
	public function _getQueriesRows( int|string $long ) : array
	{
		$queries = $this->_getQueries( $long );
		$order = 1;
		$rows = array();
		foreach ( $queries as $qid => $data )
		{
			$params = array();
			if ( isset( $data['params'] ) and is_array( $data['params'] ) )
			{
				foreach ( $data['params'] as $v )
				{
					$params[] = var_export( $v, TRUE );
				}
			}

			$rows["{$long}.{$qid}"] = Theme::i()->getTemplate( 'trees', 'core' )->row( $this->url, "{$long}.{$qid}", str_replace( '&lt;?php', '', highlight_string( "<?php \\IPS\\Db::i()->{$data['method']}( ". implode( ', ', $params ) ." )", TRUE ) ), FALSE, array(
				'delete'	=> array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> $this->url->setQueryString( array( 'do' => 'deleteVersionQuery', 'version' => $long, 'query' => $qid ) ),
					'data'		=> array( 'delete' => '' )
				)
			), NULL, NULL, NULL, FALSE, NULL, NULL, NULL, TRUE, FALSE, FALSE, FALSE );

			$order++;
		}

		return $rows;
	}
}