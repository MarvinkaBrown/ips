<?php
/**
 * @brief		Installer: Admin Account
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Apr 2013
 */
 
namespace IPS\core\modules\setup\install;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use function defined;
use function file_put_contents;
use function function_exists;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Installer: Admin Account
 */
class admin extends Controller
{
	/**
	 * Show Form
	 */
	public function manage() : void
	{
		$form = new Form( 'admin', 'continue' );
		
		$form->add( new Text( 'admin_user', NULL, TRUE ) );
		$form->add( new Password( 'admin_pass1', NULL, TRUE, array() ) );
		$form->add( new Password( 'admin_pass2', NULL, TRUE, array( 'confirm' => 'admin_pass1' ) ) );
		$form->add( new Email( 'admin_email', NULL, TRUE ) );
		
		if ( $values = $form->values() )
		{
			$INFO = [];
			require \IPS\ROOT_PATH . '/conf_global.php';
			$INFO = array_merge( $INFO, $values );
			
			$toWrite = "<?php\n\n" . '$INFO = ' . var_export( $INFO, TRUE ) . ";";
			
			try
			{
				if ( file_put_contents( \IPS\ROOT_PATH . '/conf_global.php', $toWrite ) )
				{
					/* PHP 5.5 - clear opcode cache or details won't be seen on next page load */
					if ( function_exists( 'opcache_invalidate' ) )
					{
						@opcache_invalidate( \IPS\ROOT_PATH . '/conf_global.php' );
					}
					
					Output::i()->redirect( Url::internal( 'controller=install' ) );
				}
			}
			catch( Exception $ex )
			{
				$errorform = new Form( 'admin', 'continue' );
				$errorform->add( new TextArea( 'conf_global_error', $toWrite, FALSE ) );
				
				foreach( $values as $k => $v )
				{
					$errorform->hiddenValues[ $k ] = $v;
				}
				
				Output::i()->output = Theme::i()->getTemplate( 'global' )->confWriteError( $errorform, \IPS\ROOT_PATH );
				return;
			}
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('admin');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->block( 'admin', $form );
	}
}