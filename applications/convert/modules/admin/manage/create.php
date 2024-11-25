<?php
/**
 * @brief		Converter: Start a new conversion
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\modules\admin\manage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\convert\App;
use IPS\Convert\Exception;
use IPS\convert\Library;
use IPS\convert\Software;
use IPS\DB;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_bool;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create new conversion session
 */
class create extends Controller
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
			Output::i()->error( 'module_no_permission', '2V391/3', 403, '' );
		}
		
		Output::i()->responsive = FALSE;
		Dispatcher::i()->checkAcpPermission( 'create_manage' );

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'convert.css' ) );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* We only use the Wizard helper for starting a conversion, so we need to make sure it is always starting new and not using old session data */
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'convert_start' );
		
		/* Let's call on the wizard to make some magic */
		Output::i()->output = (string) new Wizard( array(
			'convert_start_source_software' => array( $this, '_chooseSoftware' ),
			'convert_start_application'	=> array( $this, '_chooseApplications' ),
			'convert_start_database_details' => array( $this, '_setDatabaseDetails' ),
			'convert_start_conversion_details' => array( $this, '_setConversionDetails' ),
		), Url::internal( 'app=convert&module=manage&controller=create' ) );
	}

	/**
	 * Choose what software you want to convert from
	 *
	 * @param	array	$data	Wizard data from previous steps
	 * @return	Form|array
	 */
	public function _chooseSoftware( array $data ) : Form|array
	{
		$options = array();
		foreach( Software::software()['core'] AS $key => $classname )
		{
			if ( class_exists( $classname ) AND $classname::canConvert() !== NULL )
			{
				$options[ $classname::softwareKey() ] = $classname::softwareName();
			}
		}
		
		$form = new Form( 'convert_source_software', 'choose_what_to_convert' );
		$form->add( new Select( 'convert_start_source_software', NULL, TRUE, array( 'options' => $options ) ) );

		if ( $values = $form->values() )
		{
			return $values;
		}
		
		return $form;
	}

	/**
	 * Choose which applications to convert
	 *
	 * @param	array	$data	Wizard data from previous steps
	 * @return	Form|array
	 */
	public function _chooseApplications( array $data ) : Form|array
	{
		/* Figure out what converter libraries we have for the selected application */
		$software		= $data['convert_start_source_software'];
		$applications	= array( 'core' => Member::loggedIn()->language()->addToStack( 'core_node_select' ) );

		foreach( Library::libraries() AS $key => $class )
		{
			/* Check app is installed and enabled */
			if( !Application::appIsEnabled( $key ) )
			{
				continue;
			}

			foreach( Software::software()[ $key ] AS $softwareKey => $softwareClass )
			{
				if( $softwareKey == $software )
				{
					$applications[ $key ]	= Member::loggedIn()->language()->addToStack( $key . '_node_select' );
				}
				elseif( isset( $softwareClass::parents()['core'] ) )
				{
					if( in_array( $software, $softwareClass::parents()['core'] ) )
					{
						$applications[ $key . '_' . $softwareKey ]	= Member::loggedIn()->language()->get( $key . '_node_select' ) . ' - ' . $softwareClass::softwareName();
					}
				}
			}
		}

		/* Don't select the 'extra' converters */
		$preSelected = $applications;
		array_walk( $preSelected, function( $value, $key ) use( &$preSelected ) {
			if( mb_stristr( $key, '_' ) )
			{
				unset( $preSelected[ $key ] );
			}
		});

		$form = new Form( 'convert_application', 'enter_database_details' );
		$form->add( new CheckboxSet( 'choose_what_to_convert', array_keys( $preSelected ), FALSE, array( 'options' => $applications, 'disabled' => array( 'core' ) ) ) );
		
		if ( $values = $form->values() )
		{
			/* Core is required, and it was disabled on the form, so make sure it's in the array just to prevent people breaking stuff intentionally */
			if( !in_array( 'core', $values['choose_what_to_convert'] ) )
			{
				$values['choose_what_to_convert'][] = 'core';
			}

			return $values;
		}
		
		return $form;
	}

	/**
	 * Allow the admin to supply the needed details
	 *
	 * @param	array	$data	Wizard data from previous steps
	 * @return	Form|array
	 */
	public function _setDatabaseDetails( array $data ) : Form|array
	{
		$form = new Form( 'convert_database_details', 'enter_conversion_details' );

		$usePrefix	= NULL;

		foreach( $data['choose_what_to_convert'] as $application )
		{
			if( mb_stristr( $application, '_' ) )
			{
				$converter = explode( '_', $application );
				$classname = Software::software()[ $converter[0] ] [ $converter[1] ];
			}
			else
			{
				$classname = Software::software()[ $application ] [ mb_strtolower( $data['convert_start_source_software'] ) ];
			}

			if ( $classname::getPreConversionInformation() !== NULL )
			{
				$form->addMessage( Member::loggedIn()->language()->addToStack( $classname::getPreConversionInformation() ), '', FALSE );
			}

			if( $application == 'core' )
			{
				$usePrefix = $classname::usesPrefix();
			}
		}
		
		$form->add( new Text( 'convert_start_database_host', NULL, FALSE ) );
		$form->add( new Number( 'convert_start_database_port', 3306, FALSE, array( 'max' => 65535, 'min' => 1 ) ) );
		$form->add( new Text( 'convert_start_database_name', NULL, FALSE ) );
		$form->add( new Text( 'convert_start_database_user', NULL, FALSE ) );
		$form->add( new Password( 'convert_start_database_pass', NULL, FALSE ) );
		
		/* If the source software doesn't use a prefix, then we should not ask for one to avoid confusion. */
		if ( $usePrefix )
		{
			$form->add( new Text( 'convert_start_database_prefix', NULL, FALSE ) );
		}
		
		$form->add( new Text( 'convert_start_database_charset', NULL, FALSE, array( 'placeholder' => 'utf8mb4' ) ) );

		if ( $values = $form->values() )
		{
			/* Start the (core) app object */
			$app			= new App;
			$app->sw		= 'core';
			$app->app_key	= $data['convert_start_source_software'];
			$app->parent	= 0;
			
			try
			{
				if ( !isset( $values['convert_start_database_prefix'] ) )
				{
					$values['convert_start_database_prefix'] = '';
				}

				/* Test the connection */
				$connectionSettings = array(
					'sql_host'			=> $values['convert_start_database_host'],
					'sql_port'			=> $values['convert_start_database_port'],
					'sql_user'			=> $values['convert_start_database_user'],
					'sql_pass'			=> $values['convert_start_database_pass'],
					'sql_database'		=> $values['convert_start_database_name'],
					'sql_tbl_prefix'	=> $values['convert_start_database_prefix'],
				);
				
				if ( $values['convert_start_database_charset'] === 'utf8mb4' )
				{
					$connectionSettings['sql_utf8mb4'] = TRUE;
				}

				$db = Db::i( 'convertertest', $connectionSettings );
				$db->checkConnection();

				/* Now test the charset */
				if ( $values['convert_start_database_charset'] AND !in_array( $values['convert_start_database_charset'], array( 'utf8', 'utf8mb4' ) ) )
				{
					/* Get all db charsets and make sure the one we entered is valid */
					$charsets = Software::getDatabaseCharsets( $db );

					if ( !in_array( mb_strtolower( $values['convert_start_database_charset'] ), $charsets ) )
					{
						throw new InvalidArgumentException( 'invalid_charset' );
					}
					
					$db->set_charset( $values['convert_start_database_charset'] );
				}

				$appsToCheck = array( $app );
				$childAppsToRemove = array();

				foreach( $data['choose_what_to_convert'] as $application )
				{
					$childApp			= new App;

					if( mb_stristr( $application, '_' ) )
					{
						$converter = explode( '_', $application );
						$childApp->sw = $converter[0];
						$childApp->app_key = $converter[1];
					}
					else
					{
						$childApp->sw = $application;
						$childApp->app_key = $data['convert_start_source_software'];
					}

					$appsToCheck[] = $childApp;
				}

				foreach( $appsToCheck as $appToCheck )
				{
					/* Try to verify that the db prefix is correct */
					$appClassName	= $appToCheck->getSource( FALSE, FALSE );
					$canConvert		= $appClassName::canConvert();
					$appClass		= new $appClassName( $appToCheck, FALSE );
					$appClass->db	= $db;

					$testAgainst	= array_shift( $canConvert );

					/* We specifically use CountRows to test this, canConvert() may contain references to tables that do not exist..
					 * ..This is for the row count look up.
					 */
					try
					{
						$appClass->countRows( $testAgainst['table'] );
					}
					catch( UnderflowException $e )
					{
						throw new InvalidArgumentException( 'invalid_prefix' );
					}
					/* Can't find the table */
					catch( Exception $e )
					{
						if( $appToCheck->sw != 'core' )
						{
							$childAppsToRemove[] = $appToCheck->sw;
						}
						else
						{
							$form->error = $e->getMessage();
							return $form;
						}
					}
				}
			}
			catch( InvalidArgumentException $e )
			{
				if( $e->getMessage() == 'invalid_charset' )
				{
					$form->error	= Member::loggedIn()->language()->addToStack('convert_cant_connect_db_charset');
					return $form;
				}
				else if( $e->getMessage() == 'invalid_prefix' )
				{
					$form->error	= Member::loggedIn()->language()->addToStack('convert_cant_connect_db_prefix');
					return $form;
				}
				else
				{
					throw $e;
				}
			}
			catch( \Exception $e )
			{
				$form->error	= Member::loggedIn()->language()->addToStack('convert_cant_connect_db');
				return $form;
			}

			$app->db_host		= $values['convert_start_database_host'];
			$app->db_port		= $values['convert_start_database_port'];
			$app->db_db			= $values['convert_start_database_name'];
			$app->db_user		= $values['convert_start_database_user'];
			$app->db_pass		= $values['convert_start_database_pass'];
			$app->db_prefix		= $values['convert_start_database_prefix'];

			/* Check for existing apps */
			try
			{
				$existingApp = DB::i()->select( '*', 'convert_apps', array( 'sw=? AND app_key=? AND db_host=? AND db_db=? AND db_user=? AND db_pass=? AND db_prefix=?', $app->sw, $app->app_key, $app->db_host, $app->db_db, $app->db_user, $app->db_pass, $app->db_prefix ) )->first();

				/* App already exists */
				Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&id={$existingApp['app_id']}" ), 'conversion_app_already_exists' );
			}
			catch( UnderflowException $e ) { }

			if ( !empty( $values['convert_start_database_charset'] ) )
			{
				$app->db_charset	= $values['convert_start_database_charset'];
			}
			else
			{
				$app->db_charset	= 'utf8mb4';
			}

			$app->save();

			/* Now save the child applications */
			foreach( $data['choose_what_to_convert'] as $application )
			{
				if( $application == 'core' )
				{
					continue;
				}

				if( in_array( $application, $childAppsToRemove ) )
				{
					continue;
				}

				$childApp = clone $app;
				$childApp->parent	= $app->app_id;

				if( mb_stristr( $application, '_' ) )
				{
					$converter = explode( '_', $application );
					$childApp->sw = $converter[0];
					$childApp->app_key = $converter[1];
				}
				else
				{
					$childApp->sw = $application;
				}

				$childApp->save();
			}

			$values['_app_parent']	= $app->app_id;

			return $values;
		}
		
		return $form;
	}

	/**
	 * Allow the admin to supply the needed details
	 *
	 * @param	array	$data	Wizard data from previous steps
	 * @return	Form
	 */
	public function _setConversionDetails( array $data ) : Form
	{
		/* We may be coming here from a previously incomplete conversion setup...detect and set the parent app ID appropriately */
		if( ( !isset( $data['_app_parent'] ) OR !$data['_app_parent'] ) AND isset( Request::i()->id ) )
		{
			$data['_app_parent'] = Request::i()->id;
		}

		$form = new Form( 'convert_other_details', 'start_conversion' );

		try
		{
			$parentApplication = App::load( $data['_app_parent'] );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'conversion_app_not_found', '3V391/1', 404, '' );
		}

		$this->_addConversionApplication( $parentApplication, $form );

		foreach( $parentApplication->children() as $childApplication )
		{
			$this->_addConversionApplication( $childApplication, $form );
		}

		if( $values = $form->values() )
		{
			/* We know the core/parent app is fine, but any child apps that don't have 'more_info' should be deleted as they cannot be converted */
			foreach( $parentApplication->children() as $childApplication )
			{
				if( !count( $childApplication->_session['more_info'] ) )
				{
					$childApplication->delete();
				}
			}

			/* We have already saved our custom configuration, so now we can head off to the races */
			Output::i()->redirect( Url::internal( "app=convert&module=manage&controller=convert&id={$data['_app_parent']}" ) );
		}

		return $form;
	}

	/**
	 * Add form elements for the specified application to the form
	 *
	 * @param	App	$app	Conversion application
	 * @param	Form	$form	Form object
	 * @return	void
	 */
	protected function _addConversionApplication( App $app, Form $form ) : void
	{
		$softwareClass				= $app->getSource();
		$libraryClass				= $softwareClass->getLibrary();

		/* Add a nice little header to the form */
		$form->addHeader( Member::loggedIn()->language()->addToStack( 'converting_x_to_x', FALSE, array( 'sprintf' => array( $softwareClass::softwareName(), Application::load( $app->sw )->_title ) ) ) );
		
		/* Now fetch the details about this application we are converting so we can build the appropriate form elements */
		try
		{
			$menuRows	= convert::getMenuRows( $softwareClass, TRUE, TRUE );
		}
		catch( Exception $e )
		{
			$form->addMessage( 'nothing_convertable_found', 'ipsMessage ipsMessage--warning' );
			return;
		}

		/* Does this converter have settings that can be converted? */
		if ( $softwareClass::canConvertSettings() !== FALSE )
		{
			$settings = array();

			foreach( $softwareClass->settingsMapList() as $key => $setting )
			{
				$value = $setting['value'];
				if ( is_bool( ( $setting['value'] ) ) )
				{
					$value = $setting['value'] === TRUE ? 'On' : 'Off';
				}
				
				$settings[ $setting['our_key'] ] = new Checkbox( $setting['our_key'], TRUE, FALSE, array( 'label' => $value ), NULL, NULL, NULL, $setting['our_key'] );
			}

			/* Add a nice little header to the form */
			$form->addHeader( 'convert_settings' );
			$form->add( new YesNo( 'convert_settings', TRUE, TRUE, array( 'togglesOn' => array_keys( $settings ) ) ) );

			foreach( $settings as $settingFormField )
			{
				$form->add( $settingFormField );
			}

			/* Did we just submit? If so save our configuration */
			if( $values = $form->values() )
			{
				$values = array_filter( $values, function( $key ) use ( $settings ) { return $key == 'convert_settings' OR in_array( $key, array_keys( $settings ) ); }, ARRAY_FILTER_USE_KEY );

				$app->saveMoreInfo( 'convertSettings', $values );
			}
		}

		/* Now build the menu rows... */
		foreach( $menuRows as $row )
		{
			/* Determine if this step has any extra configuration questions */
			$formElements = array();

			if ( in_array( $row['step_method'], $softwareClass::checkConf() ) )
			{
				$getMoreInfo	= $softwareClass->getMoreInfo( $row['step_method'] );
				if ( is_array( $getMoreInfo ) AND count( $getMoreInfo ) )
				{
					/* Show the 'more info' fields */
					foreach( $getMoreInfo as $key => $input )
					{
						$fieldClass = $input['field_class'];
						$formElements[ $key ] = new $fieldClass( $key, $input['field_default'], $input['field_required'], $input['field_extra'], $input['field_validation'] ?? NULL, NULL, NULL, $key );
						if ( $input['field_hint'] !== NULL )
						{
							Member::loggedIn()->language()->words[ $key . '_desc' ] = $input['field_hint'];
						}
					}
				}
			}

			$form->addHeader( $row['step_title'] );
			$form->add( new YesNo( $row['step_title'], TRUE, TRUE, array( 'togglesOn' => array_keys( $formElements ) ) ) );

			if( count( $formElements ) )
			{
				foreach( $formElements as $key => $element )
				{
					$form->add( $element );
				}
			}

			/* Did we just submit? If so save our configuration */
			if( $values = $form->values() )
			{
				$values = array_filter( $values, function( $key ) use ( $formElements, $row ) { return $key == $row['step_title'] OR in_array( $key, array_keys( $formElements ) ); }, ARRAY_FILTER_USE_KEY );

				$app->saveMoreInfo( $row['step_method'], $values );
			}
		}

		/* If there was nothing to convert, show an appropriate message */
		if( $softwareClass::canConvertSettings() === FALSE AND !count( $menuRows ) )
		{
			$form->addMessage( 'nothing_convertable_found', 'ipsMessage ipsMessage--warning' );
		}

		/* If there is a message to show, go ahead and show it */
		if( $postConversionMessage = $libraryClass->getPostConversionInformation() )
		{
			$form->addMessage( $postConversionMessage, 'ipsMessage ipsMessage--info', FALSE );
		}
	}
}