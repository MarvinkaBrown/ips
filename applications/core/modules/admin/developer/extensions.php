<?php
/**
 * @brief		extensions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use DirectoryIterator;
use IPS\Application;
use IPS\Developer;
use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Output\UI\UiExtension;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;
use function sprintf;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\REBUILD_QUICK;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * extensions
 */
class extensions extends Controller
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
		/* Check for unsupported extensions */
		$message = '';
		if( $badClasses = UiExtension::unsupportedExtensions( $this->application ) )
		{
			$message = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack( 'dev_unsupported_uinode', true, array( 'sprintf' => implode( ", ", $badClasses ) ) ), 'warning' );
		}

		$this->url = Url::internal( "app=core&module=developer&controller=extensions&appKey={$this->application->directory}" );
		$table = new Tree( $this->url, 'dev_extensions', array( $this, 'extGetRoots' ), array( $this, 'extGetRow' ), array( $this, 'extGetRowParentId' ), array( $this, 'extGetChildren' ), NULL, FALSE, TRUE, TRUE );

		Output::i()->output =  $message . $table;
	}

	/**
	 * Extensions: Get Root Rows
	 *
	 * @return	array
	 */
	public function extGetRoots() : array
	{
		$rows = array();

		foreach ( Application::applications() as $app )
		{
			$haveExtensions = FALSE;

			if( is_dir( ROOT_PATH . "/applications/{$app->directory}/data/defaults/extensions" ) )
			{
				foreach ( new DirectoryIterator( ROOT_PATH . "/applications/{$app->directory}/data/defaults/extensions" ) as $file )
				{
					if ( mb_substr( $file, 0, 1 ) !== '.' AND $file != 'index.html' )
					{
						$haveExtensions = TRUE;
						break;
					}
				}
			}

			if ( $haveExtensions === TRUE )
			{
				$rows[ $app->directory ] = Theme::i()->getTemplate( 'trees', 'core' )->row( $this->url, $app->directory, $app->directory, TRUE );
			}
		}

		return $rows;
	}

	/**
	 * Extensions: Get row
	 *
	 * @param	string	$row	Row ID
	 * @param	bool	$root	Is root?
	 * @return	string
	 */
	public function extGetRow( string $row, bool $root ) : string
	{
		return Theme::i()->getTemplate( 'trees', 'core' )->row( $this->url, $row, $row, TRUE, array(), '', NULL, NULL, $root );
	}

	/**
	 * Extensions: Get Children
	 *
	 * @param	string	$folder	Folder
	 * @return	array
	 */
	public function extGetChildren( string $folder ) : array
	{
		$rows = array();

		if ( is_dir( ROOT_PATH . "/applications/{$this->application->directory}/extensions/{$folder}" ) )
		{
			foreach ( new DirectoryIterator( ROOT_PATH . "/applications/{$this->application->directory}/extensions/{$folder}" ) as $file )
			{
				if ( mb_substr( $file, 0, 1 ) !== '.' AND $file != 'index.html' )
				{
					$buttons = array();

					if ( $file->isDir() )
					{
						if ( file_exists( ROOT_PATH . "/applications/{$folder}/data/defaults/extensions/{$file}.txt" ) )
						{
							$title = sprintf( Member::loggedIn()->language()->get( 'dev_extensions_create' ), $file );
							$buttons['add'] = array(
								'icon'	=> 'plus-circle',
								'title'	=> $title,
								'link'	=> Url::internal( "app=core&module=developer&controller=extensions&appKey={$this->application->directory}&do=addExtension&type={$file}&extapp={$folder}" ),
								'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $title, 'ipsDialog-forceReload' => true )
							);
						}
					}
					else
					{
						$name = mb_substr( $file, 0, -4 );

						if( $ideLink = Developer::getIdeHref( $file->getPathname() ) )
						{
							$buttons['ide'] = [
							'icon'		=> 'fa-file-code',
							'title'		=> 'open_in_ide',
							'link'		=> $ideLink
							];
						}
						
						$buttons['delete'] = array(
							'icon'	=> 'times-circle',
							'title'	=> Member::loggedIn()->language()->addToStack('delete'),
							'link'	=> Url::internal( "app=core&module=developer&controller=extensions&appKey={$this->application->directory}&do=removeExtension&file={$name}&ext={$folder}" )->csrf(),
							'data'	=> array( 'delete' => '' )
						);
					}

					$name = str_replace( '\\', '/', mb_substr( $file->getPathName(), mb_strlen( ROOT_PATH . "/applications/{$this->application->directory}/extensions/" ) ) );

					$rows[ $name ] = Theme::i()->getTemplate( 'trees', 'core' )->row( $this->url, $name, mb_substr( $name, mb_strlen( $folder ) + 1 ), $file->isDir(), $buttons, $file->isDir() ? ( Member::loggedIn()->language()->addToStack( 'ext__' . mb_substr( $name, mb_strlen( $folder ) + 1 ) ) ) : '', NULL, NULL, FALSE, NULL, NULL, NULL, FALSE, TRUE );
				}
			}
		}

		$extensionApp	= explode( '/', $folder );
		if( count( $extensionApp ) == 1 )
		{
			foreach ( new DirectoryIterator( ROOT_PATH . "/applications/{$extensionApp[0]}/data/defaults/extensions" ) as $file )
			{
				if ( mb_substr( $file, 0, 1 ) !== '.' AND $file != 'index.html' )
				{
					$name = $folder . '/' .mb_substr( $file->getPathName(), mb_strlen( ROOT_PATH . "/applications/{$extensionApp[0]}/data/defaults/extensions/" ), -4 );

					if ( !isset( $rows[ $name ] ) )
					{
						$rows[ $name ] = Theme::i()->getTemplate( 'trees', 'core' )->row(
							$this->url,
							$name,
							mb_substr( $name, mb_strlen( $folder ) + 1 ),
							$file->isDir(),
							array(
								'add' => array(
									'icon'	=> 'plus-circle',
									'title'	=> Member::loggedIn()->language()->addToStack( 'dev_extensions_create', FALSE, array( 'sprintf' => array( mb_substr( $file, 0, -4 ) ) ) ),
									'link'	=> Url::internal( "app=core&module=developer&controller=extensions&appKey={$this->application->directory}&do=addExtension&type=" . mb_substr( $file, 0, -4 ) . "&extapp={$folder}" ),
									'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('dev_extensions_create', FALSE, array( 'sprintf' => array( mb_substr( $file, 0, -4 ) ) ) ) )
								)
							),
							Member::loggedIn()->language()->addToStack( 'ext__' . mb_substr( $name, mb_strlen( $folder ) + 1 ) ),
							null, // icon
							null, // draggablePosition
							false, // root
							null, // toggleStatus
							null, // locked
							null, // badge
							true, // titleHtml
							true // descriptionHtml
						);
					}
				}
			}
		}

		ksort( $rows );

		return $rows;
	}

	/**
	 * Extensions: Get parent ID
	 *
	 * @param	string	$folder	Folder name
	 * @return	string|null
	 */
	public function extGetRowParentId( string $folder ) : ?string
	{
		$bits = explode( "/", $folder );
		if( count( $bits ) > 1 )
		{
			return $bits[0];
		}

		return NULL;
	}

	/**
	 * Add Extension
	 *
	 * @return	void
	 */
	protected function addExtension() : void
	{
		$form = new Form();
		$form->hiddenFields['type'] = Request::i()->type;

		if( Member::loggedIn()->language()->checkKeyExists( 'ext__' . Request::i()->type ) )
		{
			$form->addHeader( 'ext__' . Request::i()->type );
		}

		$form->add( new Text( 'dev_extensions_classname', NULL, TRUE, array( 'regex' => '/^[A-Z0-9]+$/i' ) ) );

		$additionalFields = Application::load( Request::i()->extapp )->extensionHelper( Request::i()->type, $this->application->directory );
		foreach( $additionalFields as $field )
		{
			$form->add( $field );
		}

		if ( $values = $form->values() )
		{
			/* Pre-process any custom fields */
			$values = Application::load( Request::i()->extapp )->extensionGenerate( Request::i()->type, $this->application->directory, $values );

			$search = array(
				"{subpackage}",
				'{date}',
				'{app}',
				'{class}',
			);

			$replace = array(
				( $this->application->directory != 'core' ) ? ( " * @subpackage\t" . Member::loggedIn()->language()->get( "__app_{$this->application->directory}" ) ) : '',
				date( 'd M Y' ),
				$this->application->directory,
				$values['dev_extensions_classname']

			);

			foreach( $additionalFields as $tag => $field )
			{
				if( array_key_exists( $field->name, $values ) )
				{
					if( is_array( $values[ $field->name ] ) and count( $values[ $field->name ] ) )
					{
						$values[ $field->name ] = implode( ",\n", $values[ $field->name] );
					}
				}

				$search[] = $tag;
				$replace[] = $values[ $field->name ];
			}

			$contents = str_replace(
				$search,
				$replace,
				file_get_contents( ROOT_PATH . '/applications/' . Request::i()->extapp . '/data/defaults/extensions/' . Request::i()->type . '.txt' )
			);

			$dir = ROOT_PATH . "/applications/{$this->application->directory}/extensions/" . Request::i()->extapp . '/' . Request::i()->type;
			if ( !is_dir( $dir ) )
			{
				mkdir( $dir, IPS_FOLDER_PERMISSION, TRUE );
			}

			file_put_contents( ROOT_PATH . "/applications/{$this->application->directory}/extensions/" . Request::i()->extapp . '/' . Request::i()->type . "/{$values['dev_extensions_classname']}.php", $contents );
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/extensions.json", $this->application->buildExtensionsJson() );
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=extensions&appKey={$this->application->directory}&root=" . Request::i()->extapp . '/' . Request::i()->type ), 'file_created' );
		}

		Output::i()->output = Theme::i()->getTemplate('global')->block( Member::loggedIn()->language()->addToStack('dev_extensions_create', FALSE, array( 'sprintf' => array( Request::i()->type ) ) ), $form, FALSE );
	}

	/**
	 * Remove Extension
	 *
	 * @return	void
	 */
	protected function removeExtension() : void
	{
		Session::i()->csrfCheck();

		if( is_file( ROOT_PATH ."/applications/{$this->application->directory}/extensions/". Request::i()->ext.'/'. Request::i()->file.'.php'))
		{
			unlink( ROOT_PATH ."/applications/{$this->application->directory}/extensions/". Request::i()->ext.'/'. Request::i()->file.'.php' );
		}

		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/extensions.json", $this->application->buildExtensionsJson() );
		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=extensions&appKey={$this->application->directory}" ), 'deleted' );
	}
}