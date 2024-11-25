<?php
/**
 * @brief		Installer: License
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Apr 2013
 */

namespace IPS\core\modules\setup\install;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
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
 * Installer: License
 */
class license extends Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		$form = new Form( 'license', 'continue', Url::external( ( Request::i()->isSecure() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '?controller=license' ) );
		$form->add( new Text( 'lkey', 'NULLED BY NULLFORUMS.NET', TRUE, array( 'size' => 50 ), function( $val )
		{
			IPS::checkLicenseKey( $val, ( Request::i()->isSecure() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . mb_substr( $_SERVER['SCRIPT_NAME'], 0, -mb_strlen( 'admin/install/index.php' ) ) );
		}, NULL, '<a href="' . Url::ips( 'docs/find_lkey' ) . '" target="_blank" rel="noopener">' . Member::loggedIn()->language()->addToStack('lkey_help') . ' <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32h82.7L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3V192c0 17.7 14.3 32 32 32s32-14.3 32-32V32c0-17.7-14.3-32-32-32H320zM80 32C35.8 32 0 67.8 0 112V432c0 44.2 35.8 80 80 80H400c44.2 0 80-35.8 80-80V320c0-17.7-14.3-32-32-32s-32 14.3-32 32V432c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16H192c17.7 0 32-14.3 32-32s-14.3-32-32-32H80z"/></svg></a>' ) );
		$form->add( new Checkbox( 'eula', TRUE, TRUE, array( 'label' => 'eula_suffix' ), function( $val )
		{
			if ( !$val )
			{
				throw new InvalidArgumentException('eula_err');
			}
		}, "<textarea disabled style='width: 100%; height: 250px'>" . file_get_contents( 'eula.txt' ) . "</textarea>" ) );

		if ( $values = $form->values() )
		{
			$values['lkey'] = trim( $values['lkey'] );

			if ( mb_substr( $values['lkey'], -12 ) === '-TESTINSTALL' )
			{
				$values['lkey'] = mb_substr( $values['lkey'], 0, -12 );
			}

			$toWrite = "<?php\n\n" . '$INFO = ' . var_export( array( 'lkey' => 'LICENSE KEY GOES HERE!-123456789' ), TRUE ) . ';';

			try
			{
				$file = @file_put_contents( \IPS\ROOT_PATH . '/conf_global.php', $toWrite );
				if ( !$file )
				{
					throw new Exception;
				}
				else
				{
					/* PHP 5.5 - clear opcode cache or details won't be seen on next page load */
					if ( function_exists( 'opcache_invalidate' ) )
					{
						@opcache_invalidate( \IPS\ROOT_PATH . '/conf_global.php' );
					}

					Output::i()->redirect( Url::internal( 'controller=applications' ) );
				}
			}
			catch( Exception $ex )
			{
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'error' );
				$errorform = new Form( 'license', 'continue' );
				$errorform->class = '';
				$errorform->add( new TextArea( 'conf_global_error', $toWrite, FALSE ) );

				foreach( $values as $k => $v )
				{
					$errorform->hiddenValues[ $k ] = $v;
				}

				Output::i()->output = Theme::i()->getTemplate( 'global' )->confWriteError( $errorform, \IPS\ROOT_PATH );
				return;
			}
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('license');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->block( 'license', $form, TRUE, TRUE );
	}
}