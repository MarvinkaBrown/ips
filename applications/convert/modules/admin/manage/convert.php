<?php
/**
 * @brief		Converter: Perform conversion
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\modules\admin\manage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\convert\App;
use IPS\convert\Exception;
use IPS\convert\Library;
use IPS\convert\Software;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_bool;
use const IPS\CIC;
use const IPS\CONVERTERS_DEV_UI;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Run conversion
 */
class convert extends Controller
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
		if ( CIC )
		{
			Output::i()->error( 'module_no_permission', '2V368/2', 403, '' );
		}
		
		Output::i()->responsive = FALSE;
		Dispatcher::i()->checkAcpPermission( 'convert_manage' );
		parent::execute();
	}

	/**
	 * Show the conversion menu
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Do we have an id? */
		if ( ! isset( Request::i()->id ) )
		{
			Output::i()->error( 'no_conversion_app', '2V101/1' );
		}
		
		/* Load the app */
		try
		{
			$app = App::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'conversion_app_not_found', '2V101/2', 404 );
		}
		
		/* Do a quick parent storage check */
		\IPS\convert\Application::checkConvParent( $app->getSource()->getLibrary()->app );

		/* Check child apps also have temporary columns */
		foreach( $app->children() as $child )
		{
			\IPS\convert\Application::checkConvParent( $child->getSource()->getLibrary()->app );
		}

		/* Are we using the legacy UI? If not, then start converting as we already have everything we need. */
		if( !CONVERTERS_DEV_UI )
		{
			$configurationNeeded = FALSE;

			/* If we have not yet configured the app, go there first */
			if( !count( $app->_session['more_info'] ) )
			{
				$configurationNeeded = TRUE;
			}

			/* Check child apps also have more_info */
			foreach( $app->children() as $childApp )
			{
				if( !count( $childApp->_session['more_info'] ) )
				{
					$configurationNeeded = TRUE;
					break;
				}
			}

			/* Do we need to configure? */
			if( $configurationNeeded )
			{
				$url = Url::internal( "app=convert&module=manage&controller=create&_moveToStep=convert_start_conversion_details&id=" . ( $app->parent ?: $app->app_id ) );
			}
			/* Otherwise start converting! */
			else
			{
				$url = Url::internal( "app=convert&module=manage&controller=convert&do=runStep&id=" . $app->app_id )->csrf();

				if( isset( Request::i()->continue ) )
				{
					$url = $url->setQueryString( 'continue', Request::i()->continue );
				}
			}

			Output::i()->redirect( $url );
		}
		
		/* Get our details */
		$softwareClass				= $app->getSource();
		$libraryClass				= $softwareClass->getLibrary();
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'converting_x_to_x', FALSE, array( 'sprintf' => array( $softwareClass::softwareName(), Application::load( $app->sw )->_title ), 'striptags' => true ) );
		
		/* Build our table. If I can do this using only the Table helper, I'll be impressed */
		$menuRows	= static::getMenuRows( $softwareClass );
		
		$table						= new Custom( $menuRows, Url::internal( "app=convert&module=manage&controller=convert" ) );
		$table->rowsTemplate		= array( Theme::i()->getTemplate( 'table' ), 'convertMenuRow' );
		$table->extra				= array( 'sessionData' => $app->_session, 'appClass' => $app, 'softwareClass' => $softwareClass, 'libraryClass' => $libraryClass, 'menuRows' => $menuRows );
		$table->mainColumn			= 'step_title';
		$table->showAdvancedSearch	= FALSE;
		$table->noSort				= array( 'step_title', 'ips_rows', 'source_rows', 'per_cycle', 'empty_local_data', 'step_method' );
		$table->include				= array( 'step_title', 'ips_rows', 'source_rows', 'per_cycle', 'empty_local_data', 'step_method' );
		$table->parsers				= array(
			'step_title' => function( $row )
			{
				return Member::loggedIn()->language()->addToStack( $row );
			},
		);
		
		Output::i()->output = '';
		
		if ( $softwareClass::canConvertSettings() !== FALSE )
		{
			Output::i()->output	.= Theme::i()->getTemplate( 'table' )->settingsMessage( $app );
		}
		
		Output::i()->output	.= $table;
		
		if ( $libraryClass->getPostConversionInformation() != NULL )
		{
			Output::i()->output	.= Theme::i()->getTemplate( 'table' )->postConversionInformation( $libraryClass->getPostConversionInformation() );
		}
	}

	/**
	 * Get the appropriate menu rows for the library
	 *
	 * @param	Software	$softwareClass	Library class
	 * @param	bool					$filter			Filter out extra steps
	 * @param	bool					$return			Let exception bubble instead of outputting it
	 * @param	bool					$count			Count database rows
	 * @return	array
	 */
	public static function getMenuRows( Software $softwareClass, bool $filter=TRUE, bool $return=FALSE, bool $count=TRUE ) : array
	{
		$libraryClass	= $softwareClass->getLibrary();

		try
		{
			$menuRows = $libraryClass->menuRows( $count );

			/* "Extra steps" should not show in the list of things to convert, but still need to be defined in menu rows */
			$extraSteps = array();

			foreach( $softwareClass::canConvert() as $row )
			{
				if( isset( $row['extra_steps'] ) )
				{
					$extraSteps = array_merge( $extraSteps, $row['extra_steps'] );
				}
			}

			if( $filter === TRUE )
			{
				$menuRows = array_filter( $menuRows, function( $row ) use ( $extraSteps ) {
					if( in_array( $row['step_method'], $extraSteps ) )
					{
						return FALSE;
					}

					return TRUE;
				});
			}
		}
		catch( Exception $e )
		{
			if( $return )
			{
				throw $e;
			}

			Output::i()->error( $e->getMessage(), '1V101/3' );
		}

		return $menuRows;
	}

	/**
	 * Figure out the next steps for converting
	 *
	 * @param	App	$app	Application we are converting
	 * @return	array
	 */
	protected function _getNextStep( App $app ) : array
	{
		/* Set our variables */
		$appId	= $app->app_id;
		$method	= null;

		if ( !isset( $_SESSION['convertCountRows'] ) )
		{
			$_SESSION['convertCountRows'] = array();
		}

		$applicationsToCheck = array();

		if( $app->parent )
		{
			$masterAppId = $app->_parent->app_id;
			$applicationsToCheck[]	= $app->_parent;
			$applicationsToCheck	= array_merge( $applicationsToCheck, iterator_to_array( $app->_parent->children() ) );
		}
		else
		{
			$masterAppId = $app->app_id;
			$applicationsToCheck[]	= $app;
			$applicationsToCheck	= array_merge( $applicationsToCheck, iterator_to_array( $app->children() ) );
		}

		/* Set up cache array */
		if( !isset( $_SESSION['convertCountRows'][ $masterAppId ] ) )
		{
			$_SESSION['convertCountRows'][ $masterAppId ] = array();
		}

		/* Loop over all applications */
		if( count( $applicationsToCheck ) )
		{
			foreach( $applicationsToCheck as $appToCheck )
			{
				/* If this is the parent app, and we can convert settings, and we elected to convert them, and no steps are done, go there first */
				$softwareClass	= $appToCheck->getSource();

				/* Set up cache array */
				if( !isset( $_SESSION['convertCountRows'][ $masterAppId ][ $appToCheck->app_id ] ) )
				{
					$_SESSION['convertCountRows'][ $masterAppId ][ $appToCheck->app_id ] = array();
				}

				if( !$appToCheck->parent AND $softwareClass::canConvertSettings() AND isset( $appToCheck->_session['more_info']['convertSettings'] ) AND $appToCheck->_session['more_info']['convertSettings']['convert_settings'] AND !count( $appToCheck->_session['completed'] ) )
				{
					$method = 'convertSettings';
					$_SESSION['convertCountRows'][ $masterAppId ][ $appToCheck->app_id ][ $method ] = 1;
				}

				/* First, get all of the menu rows for this application to see if there are any (more) to run. 
					Do NOT filter out extra steps as we need to run those too. */
				$menuRows	= static::getMenuRows( $softwareClass, FALSE, FALSE, FALSE );

				foreach( $menuRows as $row )
				{
					/* We need the total counts so we can show the progress bar on the multiredirect */
					if ( !isset( $_SESSION['convertCountRows'][ $masterAppId ][ $appToCheck->app_id ][ $row['step_method'] ] ) )
					{
						/* Only count the rows if the cache is missing */
						$counts = $softwareClass->getLibrary()->getDatabaseRowCounts( array( $row ), FALSE, TRUE );
						$_SESSION['convertCountRows'][ $masterAppId ][ $appToCheck->app_id ][ $row['step_method'] ] = $counts[0]['source_rows'];
					}

					if( $method !== NULL )
					{
						continue;
					}

					$appId = $appToCheck->app_id;

					/* If the row is marked working, this is the one we're on */
					if( array_key_exists( $row['step_method'], $appToCheck->_session['working'] ) )
					{
						$method	= $row['step_method'];
						continue;
					}

					/* We chose to convert a specific step and it's not complete */
					if( isset( Request::i()->method ) AND $row['step_method'] == Request::i()->method )
					{
						if( !in_array( $row['step_method'], $appToCheck->_session['completed'] ) )
						{
							$method = $row['step_method'];
						}

						continue;
					} 

					/* Check whether the dependency was set to be converted, or whether it actually converted (it may not have any source data) */
					foreach( $row['dependencies'] as $k => $v )
					{
						if( empty( $menuRows[ $v ] ) OR empty( $appToCheck->_session['more_info'][ $v ][ $menuRows[ $v ]['step_title'] ] ) )
						{
							/* It wasn't, so we can continue without this dependency */
							unset( $row['dependencies'][ $k ] );
						}
					}

					/* If this step has any dependencies not yet converted, skip for now */
					if( count( array_filter( $row['dependencies'], array( $appToCheck, 'dependencies' ) ) ) )
					{
						continue;
					}

					/* If this step is already completed, also skip */
					if( in_array( $row['step_method'], $appToCheck->_session['completed'] ) )
					{
						continue;
					}

					/* If we don't want this converted, also skip */
					if( empty( $appToCheck->_session['more_info'][ $row['step_method'] ][ $row['step_title'] ] ) AND !in_array( $row['step_method'], array_keys( $softwareClass->extraMenuRows() ) ) )
					{
						continue;
					}

					/* Otherwise, this is our next step */
					$method = $row['step_method'];
				}
			}
		}

		return array( 'id' => $appId, 'method' => $method );
	}
	
	/**
	 * Remove Converted Data
	 *
	 * @return	void
	 */
	protected function emptyData() : void
	{
		Session::i()->csrfCheck();

		if ( ! isset( Request::i()->id ) )
		{
			Output::i()->error( 'no_conversion_app', '2V101/4' );
		}
		
		try
		{
			$app = App::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'conversion_app_not_found', '2V101/5' );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'removing_data' );
		Output::i()->output = new MultipleRedirect( Url::internal( "app=convert&module=manage&controller=convert&do=emptyData&id={$app->app_id}&method=" . Request::i()->method ),
		function( $data ) use ( $app )
		{
			try
			{
				return $app->getSource()->getLibrary()->emptyData( $data, Request::i()->method );
			}
			catch( Exception $e )
			{
				Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&do=error&id={$app->app_id}" ) );
			}
		},
		function() use ( $app )
		{
			Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&id={$app->app_id}" ) );
		} );
	}
	
	/**
	 * Run a conversion step
	 *
	 * @return	void
	 */
	protected function runStep() : void
	{
		Session::i()->csrfCheck();

		/* Make sure we have our app id */
		if ( ! isset( Request::i()->id ) )
		{
			Output::i()->error( 'no_conversion_app', '2V101/6' );
		}
		
		/* Load our app */
		try
		{
			$app = App::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'conversion_app_not_found', '2V101/7', 404 );
		}

		/* If we decided to truncate, we need to do so before getMoreInfo() is generated */
		if ( CONVERTERS_DEV_UI AND isset( Request::i()->empty_local_data ) AND Request::i()->empty_local_data == 1 )
		{
			$app->getSource()->getLibrary()->emptyLocalData( Request::i()->method );

			/* If this step has extra steps, we need to unset the completed flag. */
			$canConvert = $app->getSource()->getLibrary()->getConvertableItems();
			$session = $app->_session;
			if ( isset( $canConvert[ Request::i()->method ]['extra_steps'] ) )
			{
				foreach( $canConvert[ Request::i()->method ]['extra_steps'] as $next )
				{
					if( $key = array_search( $next, $session['completed'] ) )
					{
						unset( $session['completed'][ $key ] );
					}
				}
			}

			/* Since we're emptying everything, remove the main step from the completed list
				so that 'continue' functionality can be used on the next run. */
			if( $key = array_search( Request::i()->method, $session['completed'] ) )
			{
				unset( $session['completed'][ $key ] );
			}

			/* Set the new session data */
			$app->_session = $session;

			/* Reset finish flag if they're reconverting an area that uses rebuilds */
			foreach( $app->getSource()->getLibrary()->menuRows() as $v )
			{
				if( isset( $v['requires_rebuild'] ) AND $v['requires_rebuild'] )
				{
					$app->finished = FALSE;
					$app->save();
					break;
				}
			}
		}
		
		/* Do we need more information? */
		if ( CONVERTERS_DEV_UI AND ! isset( $app->_session['more_info'][ Request::i()->method ] ) OR ( isset( Request::i()->reconfigure ) AND Request::i()->reconfigure == 1 ) )
		{
			if( $this->_checkMethodConfiguration( $app ) )
			{
				return;
			}
		}
		
		/* Are we continuing? We need to tell the library that, but we need to use sessions to do it so it only does it on the first cycle. */
		if ( isset( Request::i()->continue ) )
		{
			/* Is it set? If not, just go ahead. */
			if ( !isset( $_SESSION['convertContinue'] ) )
			{
				$_SESSION['convertContinue'] = TRUE;
			}
		}
		
		/* Generate our multiredirect URL */
		$redirectUrl = Url::internal( "app=convert&module=manage&controller=convert&do=runStep&id={$app->app_id}" )->csrf();

		/* Legacy URL parameters we won't need now, but will if using the developer UI */
		if( isset( Request::i()->per_cycle ) )
		{
			$redirectUrl	= $redirectUrl->setQueryString( 'per_cycle', Request::i()->per_cycle );
		}

		if( isset( Request::i()->method ) )
		{
			$redirectUrl	= $redirectUrl->setQueryString( 'method', Request::i()->method );
		}

		/* Output multiredirector */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'converting' );
		Output::i()->output = new MultipleRedirect(
			/* The multiredirect URL */
			$redirectUrl,

			/* The function we are running on each step */
			function( $data ) use ( $app )
			{
				/* Are we reconverting using the dev ui and this is the first iteration? */
				if( $data === NULL )
				{
					if( isset( Request::i()->method ) )
					{
						$session = $app->_session;
						if( $key = array_search( Request::i()->method, $session['completed'] ) )
						{
							unset( $session['completed'][ $key ] );
							$app->_session = $session;
						}
					} 
				}

				/* Determine which method we are converting */
				$convertData = $this->_getNextStep( $app );

				/* If there is no method returned, we are done */
				if( $convertData['method'] === NULL )
				{
					return NULL;
				}

				/* If we are moving on to a different app, load it now */
				if( $convertData['id'] != $app->app_id )
				{
					$app	= App::load( $convertData['id'] );
				}

				/* Convert settings if that's what we're doing */
				if( $convertData['method'] == 'convertSettings' )
				{
					$app->getSource()->convertSettings( $app->_session['more_info']['convertSettings'] );
					Settings::i()->clearCache();

					/* A Software Exception indicates we are done */
					$completed	= $app->_session['completed'];
					$more_info	= $app->_session['more_info'];
					if ( !in_array( $convertData['method'], $completed ) )
					{
						$completed[] = $convertData['method'];
					}

					/* Manually set running flag to save write queries */
					$running = (isset( $app->_session['running'] ) ) ? $app->_session['running'] : array();
					$running[ $convertData['method'] ] = FALSE;

					$app->_session = array( 'working' => array(), 'more_info' => $more_info, 'completed' => $completed, 'running' => $running );

					$percentage	= 100 / Library::getTotalCachedRows( $app->getMasterConversionId() );
					if ( $percentage > 100 )
					{
						$percentage = 100;
					}
					return array( 0, sprintf( Member::loggedIn()->language()->get( 'converted_x_of_x' ), 1, 1, Member::loggedIn()->language()->addToStack( '_convert_settings' ) ), $percentage );
				}

				try
				{
					/* If the current method has been running for 45 seconds, set the flag to 'continue' */
					if( $app->getRunningFlag( $convertData['method'] ) AND $app->getRunningFlag( $convertData['method'] ) < ( time() - 45 ) )
					{
						$_SESSION['convertContinue'] = TRUE;
					}

					return $app->getSource()->getLibrary()->process( $data, $convertData['method'], $app->getSource()->getLibrary()->getMethodFromMenuRows( $convertData['method'] )['per_cycle'] );
				}
				catch( Exception $e )
				{
					Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&do=error&id={$app->app_id}" ) );
				}
			},

			/* The final function to run */
			function() use ( $app )
			{
				if( CONVERTERS_DEV_UI AND isset( Request::i()->method ) )
				{
					Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&do=manage&id=" . ( $app->parent ?: $app->app_id ) ) );
				}
				else
				{
					Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&do=finish&wasConfirmed=1&id=" . ( $app->parent ?: $app->app_id ) )->csrf() );
				}
			}
		 );
	}

	/**
	 * Check if we need to configure this conversion step
	 *
	 * @param	App	$app	App we are converting
	 * @return	bool
	 */
	protected function _checkMethodConfiguration( App $app ) : bool
	{
		$softwareClass	= $app->getSource();

		if ( in_array( Request::i()->method, $softwareClass::checkConf() ) )
		{
			$getMoreInfo	= $softwareClass->getMoreInfo( Request::i()->method );
			if ( is_array( $getMoreInfo ) AND count( $getMoreInfo ) )
			{
				$form = new Form( Request::i()->method . '_more_info', 'continue' );
				$form->hiddenValues['per_cycle'] = Request::i()->per_cycle;
				
				if ( isset( Request::i()->reconfigure ) )
				{
					$form->hiddenValues['reconfigure'] = 1;
				}

				/* Show the 'more info' fields */
				foreach( $getMoreInfo as $key => $input )
				{
					$fieldClass = $input['field_class'];
					$form->add( new $fieldClass( $key, $input['field_default'], $input['field_required'], $input['field_extra'], $input['field_validation'] ?? NULL ) );
					if ( $input['field_hint'] !== NULL )
					{
						$form->addMessage( $input['field_hint'], 'ipsMessage ipsMessage--info' );
					}
				}
				
				if ( $values = $form->values() )
				{
					$per_cycle = $values['per_cycle'];
					unset( $values['per_cycle'] );
					
					$app->saveMoreInfo( Request::i()->method, $values );

					Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&do=runStep&id={$app->app_id}&per_cycle={$per_cycle}&method=" . Request::i()->method )->csrf() );
				}
				
				Output::i()->title	= Member::loggedIn()->language()->addToStack( 'more_info_needed' );
				Output::i()->output = $form;
				return TRUE;
			}
		}

		return FALSE;
	}
	
	/**
	 * Show an error
	 *
	 * @return	void
	 */
	public function error() : void
	{
		if ( !isset( Request::i()->id ) )
		{
			Output::i()->error( 'no_conversion_app', '2V101/8' );
		}
		
		try
		{
			$app = App::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'conversion_app_not_found', '2V101/9', 404 );
		}
		
		/* Load the last actual error logged */
		$error = Db::i()->select( '*', 'convert_logs', array( 'log_app=?', $app->app_id ), 'log_id DESC', 1 )->first();
		
		/* Just use generic error wrapper */
		Output::i()->error( $error['log_method'] . ': ' . $error['log_message'], '2V101/A' );
	}
	
	/**
	 * Convert Settings
	 *
	 * @return	void
	 */
	public function settings() : void
	{
		if ( !isset( Request::i()->id ) )
		{
			Output::i()->error( 'no_conversion_app', '2V101/B' );
		}
		
		try
		{
			$app = App::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'conversion_app_not_found', '2V101/C', 404 );
		}
		
		$softwareClass = $app->getSource();
		
		if ( $softwareClass::canConvertSettings() === FALSE )
		{
			Output::i()->error( 'settings_conversion_not_supported', '2V101/D' );
		}
		
		$form = new Form;
		foreach( $softwareClass->settingsMapList() as $key => $setting )
		{
			$value = $setting['value'];
			if ( is_bool( ( $setting['value'] ) ) )
			{
				$value = $setting['value'] === TRUE ? 'On' : 'Off';
			}
			
			$form->add( new Checkbox( $setting['our_key'], TRUE, FALSE, array( 'label' => $value ) ) );
		}
		
		if ( $values = $form->values() )
		{
			$softwareClass->convertSettings( $values );
			Settings::i()->clearCache();
			
			Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&id={$app->app_id}" ), 'saved' );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'converting_settings' );
		Output::i()->output	= $form;
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish() : void
	{
		if ( !isset( Request::i()->id ) )
		{
			Output::i()->error( 'no_conversion_app', '2V101/E' );
		}

		try
		{
			$app = App::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			output::i()->error( 'conversion_app_not_found', '2V101/F', 404 );
		}

		/* Make sure the user confirmed they want to finish */
		Request::i()->confirmedDelete( 'convert_finish_confirm_title', 'convert_finish_confirm', 'finish' );

		$softwareClass = $app->getSource();

		$messages = array();

		/* If we have a parent, run them. */
		try
		{
			if ( $app->parent )
			{
				$parent = App::load( $app->parent );

				if ( method_exists( $parent->getSource(), 'finish' ) AND !$parent->finished )
				{
					$return = $parent->getSource()->finish();
					$parent->log( 'app_finished', __METHOD__, App::LOG_NOTICE );
					$parent->finished = TRUE;
					$parent->save();

					if ( is_array( $return ) )
					{
						$messages = array_merge( $messages, $return );
					}
				}
				elseif( $parent->finished )
				{
					$parent->log( 'app_finished_skipped', __METHOD__, App::LOG_NOTICE );
				}

				/* Run siblings if need be */
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'convert_apps', array( "parent=? AND app_id!=?", $parent->app_id, $app->app_id ) ), 'IPS\convert\App' ) AS $sibling )
				{
					if ( method_exists( $sibling->getSource(), 'finish' ) AND !$sibling->finished )
					{
						$return = $sibling->getSource()->finish();
						$sibling->log( 'app_finished', __METHOD__, App::LOG_NOTICE );
						$sibling->finished = TRUE;
						$sibling->save();

						if ( is_array( $return ) )
						{
							$messages = array_merge( $messages, $return );
						}
					}
					elseif( $sibling->finished )
					{
						$sibling->log( 'app_finished_skipped', __METHOD__, App::LOG_NOTICE );
					}
				}
			}
			else
			{
				/* No parent - bubble up to the exception */
				throw new OutOfRangeException;
			}
		}
		catch( OutOfRangeException $e )
		{
			/* This is a parent - run it's children */
			foreach( $app->children() AS $child )
			{
				$childSoftwareClass = $child->getSource();

				if ( method_exists( $childSoftwareClass, 'finish' ) AND !$child->finished )
				{
					$return = $childSoftwareClass->finish();
					$child->log( 'app_finished', __METHOD__, App::LOG_NOTICE );
					$child->finished = TRUE;
					$child->save();

					if ( is_array( $return ) )
					{
						$messages = array_merge( $messages, $return );
					}
				}
				elseif( $child->finished )
				{
					$child->log( 'app_finished_skipped', __METHOD__, App::LOG_NOTICE );
				}
			}
		}

		/* And finally, run this one */
		if ( method_exists( $softwareClass, 'finish' ) AND !$app->finished )
		{
			$return = $softwareClass->finish();
			$app->log( 'app_finished', __METHOD__, App::LOG_NOTICE );
			$app->finished = TRUE;
			$app->save();

			if ( is_array( $return ) )
			{
				$messages = array_merge( $messages, $return );
			}
		}
		elseif( $app->finished )
		{
			$app->log( 'app_finished_skipped', __METHOD__, App::LOG_NOTICE );
		}

		/* Any Messages? */
		if( !count( $messages ) )
		{
			$messages = array( 'nothing_to_finish' );
		}

		$messages = array_map( function( $key ) {
			return Member::loggedIn()->language()->addToStack( $key );
		}, $messages );

		Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=manage" ), Member::loggedIn()->language()->formatList( $messages ) );
	}
}