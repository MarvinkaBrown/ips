<?php
/**
 * @brief		listeners
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use DomainException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Developer;
use IPS\Developer\Controller;
use IPS\Events\ListenerType;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use OutOfRangeException;
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
 * listeners
 */
class listeners extends Controller
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
		$json = ROOT_PATH . "/applications/" . $this->application->directory . "/data/listeners.json";
		if ( !file_exists( $json ) )
		{
			file_put_contents( $json, json_encode( array() ) );
		}

		$data = array();
		foreach ( json_decode( file_get_contents( $json ), TRUE ) as $k => $f )
		{
			$data[ $k ] = array(
				'dev_listeners_filename' => $k,
				'dev_listeners_fires' => $f['classname']::getExtendedClass()
			);
		}

		$table = new Custom( $data, Url::internal( "app=core&module=developer&controller=listeners&appKey=" . $this->application->directory ) );
		$table->include = array( 'dev_listeners_filename', 'dev_listeners_fires' );
		$table->sortBy = $table->sortBy ?: 'dev_listeners_filename';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->rootButtons = array(
			'add' => array(
				'icon'	=> 'plus',
				'title'	=> 'add',
				'link'	=> Url::internal( "app=core&module=developer&controller=listeners&do=listenerAdd&appKey=" . $this->application->directory ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
			)
		);

		$table->rowButtons = function( $row )
		{
			$buttons = [];
			$path = ROOT_PATH . "/applications/" . $this->application->directory . '/listeners/' . $row['dev_listeners_filename'] . '.php';
		
			if( $ideLink = Developer::getIdeHref( $path ) )
			{
				$buttons['ide'] = [
				'icon'		=> 'fa-file-code',
				'title'		=> 'open_in_ide',
				'link'		=> $ideLink
				];
			};
			$buttons['delete'] = array(
					'icon'	=> 'times-circle',
					'title'	=> 'delete',
					'link' => Url::internal( "app=core&module=developer&controller=listeners&do=listenerDelete&appKey=" . $this->application->directory . "&key=" . $row['dev_listeners_filename'] )->csrf(),
					'data'	=> array( 'delete' => '' )
				);
			return $buttons;
		};
		
		Output::i()->output = (string) $table;
	}

	/**
	 * Add a listener
	 * 
	 * @return void
	 */
	protected function listenerAdd() : void
	{
		$form = new Form;

		$types = array();
		$toggles = array();
		foreach( ListenerType::listenerTypes() as $k => $v )
		{
			/** @var $v ListenerType */
			$types[$k] = 'dev_listeners_' . $k;
			if( $v::$requiresClassDeclaration )
			{
				$toggles[$k] = array( 'dev_listeners_classname' );
			}
		}

		$form->add( new Form\Select( 'dev_listeners_type', NULL, TRUE, array(
			'options' => $types,
			'toggles' => $toggles
		) ) );
		$form->add( new Text( 'dev_listeners_classname', NULL, NULL, array(), function( $val )
		{
			$class = 'IPS\\' . $val;
			if( !class_exists( $class ) )
			{
				throw new DomainException( 'dev_listeners_not_supported' );
			}

			/* @var ListenerType $listenerType */
			$listenerType = 'IPS\\Events\\ListenerType\\' . Request::i()->dev_listeners_type;
			if( !$listenerType::supportsObject( $class ) )
			{
				throw new DomainException( 'dev_listeners_not_supported' );
			}

		}, 'IPS\\', NULL, 'dev_listeners_classname' ) );
		$form->add( new Text( 'dev_listeners_filename', NULL, TRUE, array( 'regex' => '/^[a-z0-9_]*$/i' ) ) );

		if ( $values = $form->values() )
		{
			$json = ROOT_PATH . "/applications/" . $this->application->directory . "/data/listeners.json";
			$listenerDirectory = ROOT_PATH . "/applications/" . $this->application->directory . "/listeners";

			/* @var ListenerType $baseListener */
			$baseListener = 'IPS\\Events\\ListenerType\\' . $values['dev_listeners_type'];
			$extendedClass = ( isset( $values['dev_listeners_classname'] ) AND $values['dev_listeners_classname'] ) ? 'IPS\\' . $values['dev_listeners_classname'] : NULL;

			/* Write PHP file */
			$listenerFile = $listenerDirectory . "/{$values['dev_listeners_filename']}.php";
			if( !file_exists( $listenerFile ) )
			{
				if( !is_dir( $listenerDirectory ) )
				{
					mkdir( $listenerDirectory );
					chmod( $listenerDirectory, IPS_FOLDER_PERMISSION );
				}

				file_put_contents( $listenerFile, str_replace(
					array(
						'{app}',
						'{filename}',
						'{date}',
						'{class}'
					),
					array(
						$this->application->directory,
						$values['dev_listeners_filename'],
						date( 'd M Y' ),
						'\\' . ( $extendedClass ? $extendedClass . '::class' : '' )
					),
					file_get_contents( ROOT_PATH . "/applications/core/data/defaults/listeners/{$values['dev_listeners_type']}.txt" )
				) );
			}

			/* Update JSON file */
			$listeners = json_decode( file_get_contents( $json ), TRUE );
			$listeners[ $values['dev_listeners_filename'] ] = array(
				'type' => $values['dev_listeners_type'],
				'classname' => 'IPS\\' . $this->application->directory . '\\listeners\\' . $values['dev_listeners_filename'],
				'extends' => ( $extendedClass ?: $baseListener::getExtendedClass() )
			);
			Application::writeJson( $json, $listeners );

			/* Clear listener cache */
			try
			{
				unset( Store::i()->listeners );
			}
			catch( OutOfRangeException ){}

			/* Redirect */
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=listeners&appKey=" . $this->application->directory ), 'saved' );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * Delete a listener
	 * 
	 * @return void
	 */
	protected function listenerDelete() : void
	{
		Session::i()->csrfCheck();

		$json = ROOT_PATH . "/applications/" . $this->application->directory . "/data/listeners.json";
		$listenerDirectory = ROOT_PATH . "/applications/" . $this->application->directory . "/listeners";

		$listeners = json_decode( file_get_contents( $json ), TRUE );
		if ( array_key_exists( Request::i()->key, $listeners ) )
		{
			unset( $listeners[ Request::i()->key ] );
			Application::writeJson( $json, $listeners );

			if ( file_exists( $listenerDirectory . "/" . Request::i()->key . ".php" ) )
			{
				unlink( $listenerDirectory . "/" . Request::i()->key . ".php" );
			}

			/* Clear listener cache */
			try
			{
				unset( Store::i()->listeners );
			}
			catch( OutOfRangeException ){}
		}

		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=listeners&do=listenerDelete&appKey=" . $this->application->directory ), 'saved' );
	}
}