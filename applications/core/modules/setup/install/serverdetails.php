<?php
/**
 * @brief		Installer: Server Details
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Apr 2013
 */
 
namespace IPS\core\modules\setup\install;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\core\Setup\Upgrade;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use UnderflowException;
use function defined;
use function file_put_contents;
use function function_exists;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Installer: Server Details
 */
class serverdetails extends Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		$form = new Form( 'serverdetails', 'continue' );
		
		$form->addHeader( 'mysql_server' );
		$form->add( new Text( 'sql_host', ini_get('mysqli.default_host') ?: 'localhost', TRUE ) );
		$form->add( new Text( 'sql_user', ini_get('mysqli.default_user'), TRUE ) );
		$form->add( new Password( 'sql_pass', ini_get('mysqli.default_pw'), FALSE ) );
		$form->add( new Text( 'sql_database', NULL, TRUE ) );
		$form->add( new Number( 'sql_port', ini_get('mysqli.default_port'), FALSE ) );
		$form->add( new Text( 'sql_socket', ini_get('mysqli.default_socket'), FALSE ) );
		$form->add( new Text( 'sql_tbl_prefix', NULL, FALSE, [ 'regex' => '/^([a-z0-9_-]+?)?$/i' ] ) );
		$form->addHeader( 'http_server' );
		$form->add( new Text( 'base_url', ( Request::i()->isSecure() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . mb_substr( $_SERVER['SCRIPT_NAME'], 0, -mb_strlen( 'admin/install/index.php' ) ), TRUE, array( 'size' => 50 ) ) );
		$form->add( new YesNo( 'diagnostics_reporting', FALSE ) );
		
		if ( $values = $form->values() )
		{
			/* Enforce UTF8MB4 */
			$values['sql_utf8mb4'] = TRUE;

			try
			{
				try
				{
					$db = Db::i( 'test', $values );

					/* Connection Success, check MySQL version */
					$result = Upgrade::mysqlRequirements( $db );

					if( !$result['requirements']['MySQL']['version']['success'] AND !CIC )
					{
						throw new DomainException( $result['requirements']['MySQL']['version']['message'] );
					}
				}
				catch( DbException $e )
				{
					/* Can't connect. Maybe db doesn't exist, let's create it if allowed */
					if( $e->getCode() == 1049 )
					{
						try
						{
							$dbName = $values['sql_database'];
							unset( $values['sql_database'] );
							
							$db = Db::i( 'create', $values )->createDatabase( $dbName );
							$values['sql_database'] = $dbName;
							
							$db = Db::i( 'created', $values );
						}
						catch( Exception $e )
						{
							throw new DomainException( Member::loggedIn()->language()->addToStack('err_db_cant_create') );
						}
					}
					else
					{
						throw new DomainException( $e->getMessage() );
					}
				}
				
				try
				{
					if ( $db->checkForTable( 'core_sys_conf_settings') )
					{
						throw new DomainException( Member::loggedIn()->language()->addToStack('err_db_exists') );
					}
				}
				catch ( UnderflowException $e ) { }
				
				$INFO = NULL;
				require \IPS\ROOT_PATH . '/conf_global.php';
				$INFO = array_merge( $INFO, $values );
				
				$toWrite = "<?php\n\n" . '$INFO = ' . var_export( $INFO, TRUE ) . ';';

				try
				{
					if ( file_put_contents( \IPS\ROOT_PATH . '/conf_global.php', $toWrite ) )
					{
						/* PHP 5.6 - clear opcode cache or details won't be seen on next page load */
						if ( function_exists( 'opcache_invalidate' ) )
						{
							@opcache_invalidate( \IPS\ROOT_PATH . '/conf_global.php' );
						}
											
						Output::i()->redirect( Url::internal( 'controller=admin' ) );
					}
				}
				catch( Exception $ex )
				{
					$errorform = new Form( 'serverdetails', 'continue' );
					$errorform->add( new TextArea( 'conf_global_error', $toWrite, FALSE ) );
					
					foreach( $values as $k => $v )
					{
						$errorform->hiddenValues[ $k ] = $v;
					}
					
					Output::i()->output = Theme::i()->getTemplate( 'global' )->confWriteError( $errorform, \IPS\ROOT_PATH );
					return;
				}
			}
			catch ( Exception $e )
			{
				if( $e->getMessage() == 'mysqli::set_charset(): Error executing query' )
				{
					$form->error = Member::loggedIn()->language()->addToStack('err_no_utf8mb4');
				}
				else
				{
					$form->error = $e->getMessage();
				}
			}
		}
		
		Output::i()->title	= Member::loggedIn()->language()->addToStack('serverdetails');
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'serverdetails', $form );
	}
}