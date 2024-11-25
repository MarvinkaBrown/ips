<?php
/**
 * @brief		tasks
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\DateTime;
use IPS\Db;
use IPS\Developer;
use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
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
 * tasks
 */
class tasks extends Controller
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
		$json = ROOT_PATH . "/applications/{$this->application->directory}/data/tasks.json";

		$data = array();
		foreach ( json_decode( file_get_contents( $json ), TRUE ) as $k => $f )
		{
			$data[ $k ] = array(
				'dev_task_key' => $k,
				'dev_task_frequency' => $f
			);
		}

		$table = new Custom( $data, $this->url );
		$table->quickSearch = 'dev_task_key';
		$table->rootButtons = array(
			'add' => array(
				'icon'	=> 'plus',
				'title'	=> 'add',
				'link'	=> $this->url->setQueryString( 'do', 'taskForm' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
			)
		);

		$url = $this->url;
		$table->rowButtons = function( $row ) use ( $url )
		{
			$task = null;
			try
			{
				$task = Task::load( $row['dev_task_key'], 'key' );
			}
			catch( OutOfRangeException ){}

			$buttons = array();

			$fileLink = ROOT_PATH . "/applications/{$this->application->directory}/tasks/{$row['dev_task_key']}.php";
			if( $ideLink = Developer::getIdeHref($fileLink ) )
			{
				$buttons['ide'] = [
				'icon'		=> 'fa-file-code',
				'title'		=> 'open_in_ide',
				'link'		=> $ideLink
				];
			};
			
			$buttons['edit'] = array(
					'icon'	=> 'pencil',
					'title'	=> 'edit',
					'link'	=> $url->setQueryString( array( 'do' => 'taskForm', 'key' => $row['dev_task_key'] ) ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
				);
			$buttons['delete'] = array(
					'icon'	=> 'times-circle',
					'title'	=> 'delete',
					'link'	=> $url->setQueryString( array( 'do' => 'taskDelete', 'key' => $row['dev_task_key'] ) )->csrf(),
					'data'	=> array( 'delete' => '' )
				);

			if( $task !== null )
			{
				if( $task->running )
				{
					$buttons['unlock'] = [
						'icon'	=> 'unlock',
						'title'	=> 'task_manager_unlock',
						'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=unlockTask&id={$task->id}" )->csrf()
					];
				}
				else
				{
					$buttons['run'] = [
						'icon'	=> 'play-circle',
						'title'	=> 'task_manager_run',
						'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=runTask&id={$task->id}" )->csrf()
					];
				}

				$buttons['logs'] = array(
					'icon'	=> 'search',
					'title'	=> 'task_manager_logs',
					'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=taskLogs&id={$task->id}" )
				);
			}

			return $buttons;
		};

		$table->parsers = array(
			'dev_task_frequency' => function( $v )
			{
				$interval = new DateInterval( $v );
				$return = array();
				foreach ( array( 'y' => 'years', 'm' => 'months', 'd' => 'days', 'h' => 'hours', 'i' => 'minutes', 's' => 'seconds' ) as $k => $v )
				{
					if ( $interval->$k )
					{
						$return[] = Member::loggedIn()->language()->addToStack( 'every_x_' . $v, FALSE, array( 'pluralize' => array( $interval->format( '%' . $k ) ) ) );
					}
				}

				return Member::loggedIn()->language()->formatList( $return );
			}
		);

		Output::i()->output = (string) $table;
	}

	/**
	 * @return void
	 */
	protected function taskForm() : void
	{
		$json = ROOT_PATH . "/applications/{$this->application->directory}/data/tasks.json";
		$form = new Form;

		$current = NULL;
		if ( isset( Request::i()->key ) )
		{
			$tasks = json_decode( file_get_contents( $json ), TRUE );
			if ( array_key_exists( Request::i()->key, $tasks ) )
			{
				$current = array(
					'dev_task_key'			=> Request::i()->key,
					'dev_task_frequency'	=> new DateInterval( $tasks[ Request::i()->key ] )
				);

				try
				{
					$current['id']	= Db::i()->select( 'id', 'core_tasks', array( '`key`=?', Request::i()->key ) )->first();
				}
				catch( UnderflowException $e ){}

				$form->hiddenValues['old'] = $current['dev_task_key'];
			}
			unset( $tasks );
		}

		$form->add( new Text( 'dev_task_key', $current ? $current['dev_task_key'] : NULL, TRUE, array( 'maxLength' => 255, 'regex' => '/^[a-z0-9_]*$/i' ), function( $val ) use ( $current )
		{
			$where = array( array( '`key`=?', $val ) );

			if ( isset( $current['id'] ) )
			{
				$where[] = array( 'id<>?', $current['id'] );
			}

			if ( Db::i()->select( 'count(*)', 'core_tasks', $where )->first() )
			{
				throw new DomainException( 'dev_task_key_err' );
			}
		} ) );
		$form->add( new Form\Custom( 'dev_task_frequency', $current ? $current['dev_task_frequency'] : NULL, TRUE, array(
			'getHtml' => function( $element )
			{
				return Theme::i()->getTemplate( 'forms', 'core' )->dateinterval( $element->name, $element->value ?: new DateInterval( 'P0D' ) );
			},
			'formatValue' => function ( $element )
			{
				if ( !( $element->value instanceof DateInterval ) )
				{
					if( !empty($element->value) )
					{
						try
						{
							$interval	= new DateInterval( "P{$element->value['y']}Y{$element->value['m']}M{$element->value['d']}DT{$element->value['h']}H{$element->value['i']}M{$element->value['s']}S" );
						}
						catch( Exception $e )
						{
							$interval	= DateInterval::createFromDateString('1 day');
						}
					}
					else
					{
						$interval	= DateInterval::createFromDateString('1 day');
					}

					return $interval;
				}
				return $element->value;
			}
		), function ( $val )
		{
			foreach ( $val as $k => $v )
			{
				if ( $v )
				{
					return;
				}
			}
			throw new InvalidArgumentException( 'form_required' );
		} ) );

		if ( $values = $form->values() )
		{
			/* Write PHP file */
			$taskDirectory = ROOT_PATH . "/applications/{$this->application->directory}/tasks";
			$taskFile =  $taskDirectory . "/{$values['dev_task_key']}.php";
			if ( isset( $values['old'] ) and $values['old'] !== $values['dev_task_key'] and file_exists( $taskDirectory . "/{$values['old']}.php" ) )
			{
				@rename( $taskDirectory . "/{$values['old']}.php", $taskFile );
				Db::i()->delete( 'core_tasks', array( '`key`=?', $values['old'] ) );
			}
			if ( !file_exists( $taskFile ) )
			{
				if ( !is_dir( $taskDirectory ) )
				{
					mkdir( $taskDirectory );
					chmod( $taskDirectory, IPS_FOLDER_PERMISSION);
				}

				file_put_contents( $taskFile, str_replace(
					array(
						'{key}',
						"{subpackage}\n",
						'{date}',
						'{namespace}',
					),
					array(
						$values['dev_task_key'],
						( $this->application->directory != 'core' ) ? ( " * @subpackage\t" . $this->application->directory . "\n" ) : '',
						date( 'd M Y' ),
						$this->application->directory . '\tasks',
					),
					file_get_contents( ROOT_PATH . "/applications/core/data/defaults/Task.txt" )
				) );
			}

			/* Add to DB */
			$frequency = "P{$values['dev_task_frequency']->y}Y{$values['dev_task_frequency']->m}M{$values['dev_task_frequency']->d}DT{$values['dev_task_frequency']->h}H{$values['dev_task_frequency']->i}M{$values['dev_task_frequency']->s}S";
			Db::i()->replace( 'core_tasks', array(
				'app'		=>  $this->application->directory,
				'key'		=> $values['dev_task_key'],
				'frequency'	=> $frequency,
				'next_run'	=> DateTime::create()->add( new DateInterval( $frequency ) )->getTimestamp(),
				'running'	=> 0,
			) );

			/* Add to JSON file */
			$tasks = json_decode( file_get_contents( $json ), TRUE );
			$tasks[ $values['dev_task_key'] ] = $frequency;
			if ( isset( $values['old'] ) and $values['old'] !== $values['dev_task_key'] and isset( $tasks[ $values['old'] ] ) )
			{
				unset( $tasks[ $values['old'] ] );
			}
			file_put_contents( $json, json_encode( $tasks ) );

			/* Redirect */
			Output::i()->redirect( $this->url, 'saved' );

		}

		Output::i()->output = (string) $form;
	}

	/**
	 * @return void
	 */
	protected function taskDelete() : void
	{
		Session::i()->csrfCheck();

		$json = ROOT_PATH . "/applications/{$this->application->directory}/data/tasks.json";

		$tasks = json_decode( file_get_contents( $json ), TRUE );
		if ( array_key_exists( Request::i()->key, $tasks ) )
		{
			unset( $tasks[ Request::i()->key ] );
			file_put_contents( $json, json_encode( $tasks ) );

			$taskDirectory = ROOT_PATH . "/applications/{$this->application->directory}/tasks";
			if ( file_exists( $taskDirectory . "/" . Request::i()->key . ".php" ) )
			{
				unlink( $taskDirectory . "/" . Request::i()->key . ".php" );
			}

			Db::i()->delete( 'core_tasks', array(  'app=? AND `key`=?', $this->application->directory, Request::i()->key ) );
		}
		Output::i()->redirect( $this->url, 'saved' );
	}
}