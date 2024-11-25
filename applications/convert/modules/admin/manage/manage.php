<?php
/**
 * @brief		Converter: Manage conversions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\modules\admin\manage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\convert\App;
use IPS\convert\Software;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function file_put_contents;
use function get_class;
use function in_array;
use const IPS\CIC;
use const IPS\CONVERTERS_DEV_UI;
use const IPS\DEMO_MODE;
use const IPS\FOLDER_PERMISSION_NO_WRITE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Converter overview
 */
class manage extends Controller
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
	public function execute(): void
	{
		if ( DEMO_MODE === TRUE )
		{
			Output::i()->error( 'demo_mode_function_blocked', '2V407/1', 403, '' );
		}
		
		Output::i()->responsive = FALSE;
		Dispatcher::i()->checkAcpPermission( 'manage_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* If mod_rewrite is not enabled, advise the administrator that old URLs will not redirect to the new locations automatically */
		if( !Settings::i()->htaccess_mod_rewrite )
		{
			Output::i()->output	.= Theme::i()->getTemplate( 'global', 'core' )->message( 'convert_mod_rewrite_urls', 'warning' );
		}

		/* Remind the user that they must configure permissions after the conversion is complete */
		Output::i()->output	.= Theme::i()->getTemplate( 'global', 'core' )->message( 'convert_configure_permissions', 'info' );

		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'convert_apps', Url::internal( 'app=convert&module=manage&controller=manage' ), array( 'parent=?', 0 ) );
		$table->langPrefix = 'convert_';
		$table->include = array( 'app_key', 'sw', 'start_date', 'finished' );
		
		$table->parsers = array(
			'sw'				=> function( $val, $row )
			{
				$translate = function( $app )
				{
					switch( $app )
					{
						case 'board':
							$app = 'forums';
							break;

						case 'ccs':
							$app = 'cms';
							break;
					}

					return $app;
				};

				/* The main row will always be the core application (except for legacy conversions) */
				$applications = array( Application::load( $translate( $val ) )->_title );

				foreach( Db::i()->select( '*', 'convert_apps', array( 'parent=?', $row['app_id'] ) ) as $software )
				{
					/* Translate the software key, if required */
					$software['sw'] = $translate( $software['sw'] );

					try
					{
						if ( CONVERTERS_DEV_UI === TRUE )
						{
							$continueUrl = Url::internal( "app=convert&module=manage&controller=convert&id={$software['app_id']}&continue=1" )->csrf();
							$applications[] = "<a href='{$continueUrl}'>" . Application::load( $software['sw'] )->_title . "</a>";
							continue;
						}
						$applications[] = Application::load( $software['sw'] )->_title;
					}
					catch( OutOfRangeException $e )
					{
						$applications[] = IPS::mb_ucfirst( $software );
					}
				}

				return Member::loggedIn()->language()->formatList( $applications );
			},
			'app_key'			=> function( $val, $row )
			{
				$app = App::constructFromData( $row );

				try
				{
					$classname = get_class( $app->getSource() );

					/* @var Software $classname */
					return $classname::softwareName();
				}
				catch( Exception $ex )
				{
					return $val;
				}
			},
			'start_date'		=> function( $val )
			{
				return (string) DateTime::ts( $val );
			},
			'finished'			=> function( $val )
			{
				if ( $val )
				{
					return '&#10003;';
				}
				else
				{
					return '&#10007;';
				}
			}
		);

		if( !CIC )
		{
			Output::i()->sidebar['actions']['start'] = [
				'primary' => true,
				'icon' => 'plus',
				'title' => 'convert_start',
				'link' => Url::internal( "app=convert&module=manage&controller=create&_new=1" ),
			];
		}

		if ( \IPS\IN_DEV )
		{
			Output::i()->sidebar['actions']['software'] = array(
				'icon'		=> 'plus',
				'title'	=> 'new_software',
				'link'	=> Url::internal( "app=convert&module=manage&controller=manage&do=software" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('new_software') )
			);
			Output::i()->sidebar['actions']['library'] = array(
				'icon'		=> 'plus',
				'title'	=> 'new_library',
				'link'	=> Url::internal( "app=convert&module=manage&controller=manage&do=library" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('new_library') )
			);
		}
		
		$table->rowButtons = function( $row )
		{
			try
			{
				/* Try to load the application to make sure it's installed */
				Application::load( $row['sw'] );

				/* Try to load the app class - if exception, the converter no longer exists */
				App::constructFromData( $row )->getSource();
				
				$return = array();
				
				if ( !$row['finished'] OR CONVERTERS_DEV_UI === TRUE )
				{
					$return[] = array(
						'icon'	=> 'chevron-circle-right',
						'title'	=> 'continue',
						'link'	=> Url::internal( "app=convert&module=manage&controller=convert&id={$row['app_id']}&continue=1" )->csrf()
					);

					if( CONVERTERS_DEV_UI === TRUE AND !$row['finished'] )
					{
						$return[] = array(
							'icon'	=> 'check',
							'title'	=> 'finish',
							'link'	=> Url::internal( "app=convert&module=manage&controller=convert&do=finish&id={$row['app_id']}" )->csrf(),
							'data'	=> array(
								'confirm' => '', 'confirmSubMessage' => Member::loggedIn()->language()->get( 'convert_finish_confirm' )
							)
						);
					}

					$return[] = array(
						'icon'	=> 'pencil',
						'title'	=> 'edit',
						'link'	=> Url::internal( "app=convert&module=manage&controller=manage&do=edit&id={$row['app_id']}" ),
					);
				}

				return $return;
			}
			catch( InvalidArgumentException | OutOfRangeException $e )
			{
				return array();
			}
		};

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'menu__convert_manage' );
		Output::i()->output	.= $table;
	}
	
	/**
	 * Create New Converter
	 *
	 * @return	void
	 */
	public function software() : void
	{
		if ( \IPS\IN_DEV === FALSE )
		{
			Output::i()->error( 'new_sofware_not_in_dev', '1V100/1', 403 );
		}
		
		$form = new Form;
		$form->add( new Text( 'classname', NULL, TRUE ) );

		if ( $values = $form->values() )
		{
			/* Get the default code */
			$default		= file_get_contents( \IPS\ROOT_PATH . '/applications/convert/data/defaults/Software.txt' );

			/* Explode the entered class name to figure out the file path and namespace */
			$exploded		= explode( '\\', ltrim( $values['classname'], '\\' ) );
			
			/* Copy the exploded array to generate the path */
			$copied 		= $exploded;
			
			/* Shift off IPS from the namespace */
			array_shift( $copied );
			
			/* Get our application key */
			$application	= array_shift( $copied );
			
			/* Generate our path */
			$filepath		= \IPS\ROOT_PATH . "/applications/{$application}/sources/" . implode( '/', $copied ) . ".php";
			
			/* Now figure out our namespace, class, and default code with replacements */
			$classname		= array_pop( $exploded );
			$namespace		= implode( '\\', $exploded );
			$code			= str_replace( array( '<#NAMESPACE#>', '<#CLASS#>', '<#CLASS_LOWER#>' ), array( $namespace, $classname, mb_strtolower( $classname ) ), $default );

			/* Check if we need to create a folder */
			if( count( $copied ) > 1 )
			{
				$folder = \IPS\ROOT_PATH . "/applications/{$application}/sources/" . $copied[0];
				if( !file_exists( $folder ) )
				{
					@mkdir( $folder );
					@chmod( $folder, FOLDER_PERMISSION_NO_WRITE );
				}
			}
			
			/* Generate the file */
			file_put_contents( $filepath, $code );
			
			Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=manage" ), 'saved' );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'new_software' );
		Output::i()->output	= (string) $form;
	}
	
	/**
	 * Create New Library
	 *
	 * @return	void
	 */
	public function library() : void
	{
		if ( \IPS\IN_DEV === FALSE )
		{
			Output::i()->error( 'new_library_not_in_dev', '1V100/2', 403 );
		}
		
		$form = new Form;
		$form->add( new Text( 'classname', NULL, TRUE ) );
		
		if ( $values = $form->values() )
		{
			/* Get the default code */
			$default		= file_get_contents( \IPS\ROOT_PATH . '/applications/convert/data/defaults/Library.txt' );
			
			/* Explode the entered class name to figure out the file path and namespace */
			$exploded		= explode( '\\', ltrim( $values['classname'], '\\' ) );
			
			/* Copy the exploded array to generate the path */
			$copied 		= $exploded;
			
			/* Shift off IPS from the namespace */
			array_shift( $copied );
			
			/* Get our application key */
			$application	= array_shift( $copied );
			
			/* Generate our path */
			$filepath		= \IPS\ROOT_PATH . "/applications/{$application}/sources/" . implode( '/', $copied ) . ".php";
			
			/* Now figure out our namespace, class, and default code with replacements */
			$classname		= array_pop( $exploded );
			$namespace		= implode( '\\', $exploded );
			$code			= str_replace( array( '<#NAMESPACE#>', '<#CLASS#>', '<#CLASS_LOWER#>' ), array( $namespace, $classname, mb_strtolower( $classname ) ), $default );

			/* Check if we need to create a folder */
			if( count( $copied ) > 1 )
			{
				$folder = \IPS\ROOT_PATH . "/applications/{$application}/sources/" . $copied[0];
				if( !file_exists( $folder ) )
				{
					@mkdir( $folder );
					@chmod( $folder, FOLDER_PERMISSION_NO_WRITE );
				}
			}
			
			/* Generate the file */
			file_put_contents( $filepath, $code );
			
			Output::i()->redirect( Url::internal( 'app=convert&module=manage&controller=manage' ), 'saved' );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'new_library' );
		Output::i()->output	= (string) $form;
	}
	
	/**
	 * Edit a conversion
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		if ( !isset( Request::i()->id ) )
		{
			Output::i()->error( 'no_conversion_app', '2V100/3' );
		}
		
		try
		{
			$app = App::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'conversion_app_not_found', '2V100/4', 404 );
		}
		
		$classname = $app->getSource( FALSE );
		
		$form = new Form;

		if ( $classname::getPreConversionInformation() !== NULL )
		{
			$form->addMessage( Member::loggedIn()->language()->addToStack( $classname::getPreConversionInformation() ), '', FALSE );
		}
		
		$form->add( new Text( 'db_host', $app->db_host, FALSE ) );
		$form->add( new Number( 'db_port', $app->db_port, FALSE, array( 'max' => 65535, 'min' => 1 ) ) );
		$form->add( new Text( 'db_db', $app->db_db, FALSE ) );
		$form->add( new Text( 'db_user', $app->db_user, FALSE ) );
		$form->add( new Password( 'db_pass', $app->db_pass, FALSE ) );
		$form->add( new Text( 'db_prefix', $app->db_prefix, FALSE ) );
		$form->add( new Text( 'db_charset', $app->db_charset, FALSE ) );
		
		Output::i()->title	= Member::loggedIn()->language()->addToStack( 'edit_conversion' );
				
		if ( $values = $form->values() )
		{
			try
			{
				/* Test the connection */
				$connectionSettings = array(
					'sql_host'			=> $values['db_host'],
					'sql_port'			=> $values['db_port'],
					'sql_user'			=> $values['db_user'],
					'sql_pass'			=> $values['db_pass'],
					'sql_database'		=> $values['db_db'],
					'sql_tbl_prefix'	=> $values['db_prefix'],
				);
				
				if ( $values['db_charset'] === 'utf8mb4' )
				{
					$connectionSettings['sql_utf8mb4'] = TRUE;
				}

				$db = Db::i( 'convertertest', $connectionSettings );

				/* Now test the charset */
				if ( $values['db_charset'] AND !in_array( $values['db_charset'], array( 'utf8', 'utf8mb4' ) ) )
				{
					/* Get all db charsets and make sure the one we entered is valid */
					$charsets = Software::getDatabaseCharsets( $db );

					if ( !in_array( mb_strtolower( $values['db_charset'] ), $charsets ) )
					{
						throw new InvalidArgumentException( 'invalid_charset' );
					}
					
					$db->set_charset( $values['db_charset'] );
				}

				/* Try to verify that the db prefix is correct */
				$appClass 	= $app->getSource( FALSE, FALSE );
				$canConvert	= $appClass::canConvert();

				$testAgainst	= array_shift( $canConvert );

				if( !$db->checkForTable( $testAgainst['table'] ) )
				{
					throw new InvalidArgumentException( 'invalid_prefix' );
				}
			}
			catch( InvalidArgumentException $e )
			{
				if( $e->getMessage() == 'invalid_charset' )
				{
					$form->error	= Member::loggedIn()->language()->addToStack('convert_cant_connect_db_charset');
					Output::i()->output = $form;
					return;
				}
				else if( $e->getMessage() == 'invalid_prefix' )
				{
					$form->error	= Member::loggedIn()->language()->addToStack('convert_cant_connect_db_prefix');
					Output::i()->output = $form;
					return;
				}
				else
				{
					throw $e;
				}
			}
			catch( Exception $e )
			{
				$form->error	= Member::loggedIn()->language()->addToStack('convert_cant_connect_db');
				Output::i()->output = $form;
				return;
			}

			$app->db_host		= $values['db_host'];
			$app->db_port		= $values['db_port'];
			$app->db_db			= $values['db_db'];
			$app->db_user		= $values['db_user'];
			$app->db_pass		= $values['db_pass'];
			$app->db_prefix		= $values['db_prefix'];
			$app->db_charset	= $values['db_charset'];
			$app->save();

			foreach( $app->children() as $child )
			{
				$child->db_host		= $values['db_host'];
				$child->db_port		= $values['db_port'];
				$child->db_db		= $values['db_db'];
				$child->db_user		= $values['db_user'];
				$child->db_pass		= $values['db_pass'];
				$child->db_prefix	= $values['db_prefix'];
				$child->db_charset	= $values['db_charset'];
				$child->save();
			}
			
			Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=manage" ), 'saved' );
		}
		
		Output::i()->output = $form;
	}
}