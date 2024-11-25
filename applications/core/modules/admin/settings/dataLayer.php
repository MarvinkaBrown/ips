<?php
/**
 * @brief		dataLayer
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		08 Feb 2022
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\core\DataLayer as DataLayerClass;
use IPS\core\DataLayer\Handler;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Login\Handler as HandlerClass;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function call_user_func_array;
use function count;
use function defined;
use function mb_substr;
use function strlen;
use function strtolower;
use function substr;
use function trim;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * dataLayer
 */
class dataLayer extends Controller
{
	/**
	 * @breif the tabs
	 */
	protected static array $tabs = array(
		'main'        => 'datalayer_main',
		'pageContext' => 'datalayer_pageContext',
		'properties'  => 'datalayer_properties',
		'events'      => 'datalayer_events',
	);

	/**
	 * @breif   Makes this controller work
	 */
	public static bool $csrfProtected = true;

	/**
	 * @brief   used to actually handle events and properties in the browser
	 */
	public static string $handlerClass = '\IPS\core\DataLayer\Handler';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if ( Application::appIsEnabled('cloud') and DataLayerClass::i() instanceof \IPS\cloud\DataLayer\Handler )
		{
			static::$handlerClass = 'IPS\cloud\DataLayer\Handler';
		}

		return parent::__construct();
	}

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'dataLayer_manage' );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'menu__core_settings_dataLayer' );

		/* Can we use custom handlers? Only show the tab on sites that have full analytics */
		$class = static::$handlerClass;
		if (
			class_exists( $class ) AND
			class_exists( 'IPS\cloud\Application' ) AND
			method_exists( $class, 'handlerForm' ) AND
			method_exists( $class, 'loadWhere' ) AND
			Member::loggedIn()->hasAcpRestriction( 'dataLayer_handlers_view' ) AND
			\IPS\cloud\Application::featureIsEnabled('analytics_full')
		)
		{
			static::$tabs = array(
				'main'          => 'datalayer_main',
				'pageContext'   => 'datalayer_pageContext',
				'handlers'      => 'datalayer_handlers',
				'properties'    => 'datalayer_properties',
				'events'        => 'datalayer_events',
			);
		}

		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'settings/general.css', 'core', 'admin' ) );
		parent::execute();
	}

	/**
	 *  Call Magic Method, seamless way to integrate cloud features if there are any
	 *
	 * @return  string|void
	 */
	public function __call( $name, $arguments )
	{
		if ( method_exists( $this, $name ) )
		{
			return call_user_func_array( $this->$name, $arguments );
		}
		elseif ( method_exists( DataLayerClass::i(), $name ) )
		{
			$method = array( DataLayerClass::i(), $name );
			return call_user_func_array( $method, $arguments );
		}

		return "You should Upgrade!";
	}

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tab    = Request::i()->tab;
		$tab    = empty( static::$tabs[ $tab ] ) ? 'main' : $tab;
		$output = $this->$tab();
		$output = "<div id='dataLayerContent'>$output</div>" ;

		if ( isset( static::$tabs['handlers'] ) AND Member::loggedIn()->hasAcpRestriction( 'dataLayer_handlers_edit' ) )
		{
			Output::i()->sidebar['actions']['addHandler'] = array(
				'title'     => Member::loggedIn()->language()->addToStack( 'datalayer_handler_form' ),
				'icon'      =>  'plus',
				'link'      => Url::internal( 'app=core&module=settings&controller=dataLayer&do=addHandler' ),
				'data'      => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'datalayer_handler_form' ) )
			);
		}


		$addPropertyUrl = Url::internal( 'app=core&module=settings&controller=dataLayer&do=addProperty' );
		Output::i()->sidebar['actions']['addProperty'] = array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'datalayer_add_property' ),
			'icon'		=> 'plus',
			'link'		=> $addPropertyUrl,
			'data'      => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'datalayer_add_property' ) )
		);

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $output;
		}
		else
		{
			if ( isset( $_SESSION['deleted_datalayer_property'] ) )
			{
				Output::i()->inlineMessage = 'Deleted Property';
				unset( $_SESSION['deleted_datalayer_property'] );
			}

			Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->tabs(
				static::$tabs,
				$tab,
				$output,
				Url::internal( 'app=core&module=settings&controller=dataLayer', 'admin' )
			);
		}
	}


	/**
	 * Gets the general settings form
	 *
	 * @return string
	 */
	protected function main() : string
	{
		$form = new Form( 'datalayer_general' );

		$form->addHeader('Settings');
		$form->add( new YesNo( 'core_datalayer_enabled', Settings::i()->core_datalayer_enabled, false ) );
		$form->add( new YesNo( 'core_datalayer_include_pii', Settings::i()->core_datalayer_include_pii, false, array( 'togglesOn' => [ 'datalayer_general_core_datalayer_member_pii_choice' ] ) ) );
		$form->add( new YesNo( 'core_datalayer_member_pii_choice', Settings::i()->core_datalayer_member_pii_choice, false ) );

		try
		{
			$default = HandlerClass::load( Settings::i()->core_datalayer_replace_with_sso );
		}
		catch ( OutOfRangeException $e )
		{
			$default = 0;
		}

		$form->add( new Node( 'core_datalayer_replace_with_sso', $default, false, array(
			'class'   => '\IPS\Login\Handler',
			'zeroVal'   => 'Use internal Member ID (default)',
			'where'     => array(
				['login_enabled=?', 1],
				['login_classname LIKE ?', '%Login_Handler%'],
				['login_classname NOT LIKE ?', '%Login_Handler_Standard']
			),

		) ) );

		$form->addHeader('enhancements__core_GoogleTagManager');
		$form->addMessage( 'use_gtm_for_installation', 'ipsMessage ipsMessage--info i-margin_3' );
		$form->add( new YesNo( 'core_datalayer_use_gtm', Settings::i()->core_datalayer_use_gtm, false ) );
		$gtmkey = explode( '.', Settings::i()->core_datalayer_gtmkey );
		$form->add( new Text(
			'core_datalayer_gtmkey',
			array_pop( $gtmkey ),
			false,
			array(),
            function( $_value ) {
	            /* Check for invalid characters */
	            if ( preg_match( '/[^a-zA-Z0-9_]/', $_value ) )
	            {
		            throw new InvalidArgumentException( 'The variable name can only contain alphanumeric characters and underscores.' );
	            }
	            elseif ( preg_match( '/[0-9]/', mb_substr( $_value, 0, 1 ) ) )
	            {
		            throw new InvalidArgumentException( 'The variable name cannot start with a number.' );
	            }

				/* Is a custom handler using this? Note that, in JS, window.variable and variable are nearly always the same */
	            $where = [
		            [ 'enabled=?', 1 ],
		            [ 'use_js=?', 1 ],
		            Db::i()->in( 'datalayer_key', [ $_value, "window.$_value" ] ),
	            ];

	            if (
		            class_exists( 'IPS\cloud\DataLayer' ) AND
					DataLayerClass::i() instanceof \IPS\cloud\DataLayer AND
		            count( Db::i()->select( 'datalayer_key', \IPS\cloud\DataLayer\Handler::$databaseTable, $where, null, 1 ) )
	            )
	            {
		            throw new InvalidArgumentException( "$_value or window.$_value is in use by a custom handler." );
	            }
            },
			'<span class="i-font-family_monospace">window.</span>'
        ));

		if ( $values = $form->values() )
		{
			Session::i()->csrfCheck();

			if ( isset( $values['core_datalayer_replace_with_sso'] ) )
			{
				$values['core_datalayer_replace_with_sso'] = $values['core_datalayer_replace_with_sso'] ? $values['core_datalayer_replace_with_sso']->_id : 0;
			}

			/* Prefix the gtmkey with window. */
			if ( isset( $values['core_datalayer_gtmkey'] ) )
			{
				$values['core_datalayer_gtmkey'] = "window.{$values['core_datalayer_gtmkey']}";
			}

			/* Since GTM is a handler, cachebust the handler for template if it was updated */
			foreach( ['core_datalayer_use_gtm', 'core_datalayer_gtmkey', 'googletag_head_code', 'googletag_noscript_code'] as $input )
			{
				if ( isset( $values[$input] ) )
				{
					$key = Handler::$handlerCacheKey;
					unset( Store::i()->$key );
					break;
				}
			}

			/* Clear the cached configuration since we're changing it */
			DataLayerClass::i()->clearCachedConfiguration([ '_jsConfig', '_eventProperties', '_propertyEvents' ]);

			Settings::i()->changeValues( $values );
			
			Session::i()->log( 'acplog__datalayer_settings_edited' );

			/* Redirect to avoid csrfKey errors */
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=dataLayer&tab=main' ) );
		}

		return "<br>$form";
	}

	/**
	 * Property settings and configuration
	 *
	 * @return string
	 */
	protected function pageContext() : string
	{
		/* Get the page level properties */
		$_properties = DataLayerClass::i()->propertiesConfiguration;
		foreach ( $_properties as $k => $property )
		{
			if ( $property['page_level'] AND $property['enabled'] )
			{
				$properties[$k] = array(
					'name'      => $property['formatted_name'],
					'type'      => $property['type'],
					'pii'       => $property['pii'],
					'custom'    => $property['custom'] ?? 0,
					'short'     => $property['short'],
				);
			}
		}

		$propertyBlock              = new Custom(
			$properties,
			Url::internal( 'app=core&module=settings&controller=dataLayer&tab=pageContext' )
		);
		$propertyBlock->classes[]   = 'ipsPadding:none';
		$propertyBlock->exclude     = array('custom', 'description');
		$propertyBlock->langPrefix  = 'datalayer_';

		if ( !Request::i()->sortby )
		{
			$propertyBlock->sortBy = 'name';
		}

		if ( !Request::i()->sortdirection )
		{
			$propertyBlock->sortDirection = 'asc';
		}

		$propertyBlock->parsers = array(
			'pii'   => array( $this, '_yesNo' ),
			'name'  => function ( $val, $row ) {
				$url = Url::internal('app=core&module=settings&controller=dataLayer&tab=properties&property_key=' . $val);
				$title = $row['short'];
				return "<span class='i-font-family_monospace'><a href='$url' data-ipstooltip title='$title'>$val</a></span>";
			},
		);

		return Theme::i()->getTemplate( 'settings', 'core', 'admin' )->dataLayerContext( $propertyBlock );
	}

	/**
	 * Property settings and configuration
	 *
	 * @return string
	 */
	protected function properties() : string
	{
		/* Load our properties */
		$properties     = DataLayerClass::i()->propertiesConfiguration;
		$property_key   = Request::i()->property_key ?: array_keys( $properties )[0];

		/* Do we know the requested property? */
		if ( !isset( $properties[$property_key] ) )
		{
			Output::i()->redirect( Request::i()->url()->stripQueryString([ 'property_key' ]) );
		}
		$property               = $properties[$property_key];

		/* Sidebar */
		$propertySelector   = Theme::i()->getTemplate( 'settings', 'core', 'admin' )->dataLayerSelector(
			$properties,
			'properties',
			'property_key',
			'Data Layer Properties',
			$property_key,
			true,
			array( $this, '_truncate' )
		);

		/* Form */
		$form           = new Form( 'datalayer_properties_' . $property_key, 'save', Url::internal( 'app=core&module=settings&controller=dataLayer&tab=properties&property_key=' . $property_key ) );
		$form->class    = 'ipsForm--vertical ipsForm--datalayer-properties';

		$form->add( new YesNo( 'datalayer_property_enabled', $property['enabled'], false ) );
		$form->add( new Text( 'datalayer_property_formatted_name', $property['formatted_name'], false, array(), $this->formattedNameValid('properties', $property_key) ) );

		if ( $property['custom'] ?? 0 )
		{
			$form->add( new Text( 'datalayer_property_value', $property['value'], true ) );
			$form->add( new YesNo( 'datalayer_property_page_level', $property['page_level'], true ) );
			$form->add( new Text( 'datalayer_property_short', $property['short'], false, array( 'maxLength' => 50 ) ) );

			$events = DataLayerClass::i()->eventConfiguration;
			$options = array();
			foreach ( $events as $key => $data )
			{
				$options[$key]  = $data['formatted_name'];
			}

			$form->add( new CheckboxSet( 'datalayer_property_event_keys', $property['event_keys'], false, array('options' => $options, 'multiple' => 1) ) );
		}

		/* Update the values as long as the property_key was specified in the request */
		if ( ( $values = $form->values() ) AND Request::i()->property_key === $property_key )
		{
			/* Get our submitted values */
			$_values = array();
			if ( isset( $values['datalayer_property_enabled'] ) )
			{
				$_values['enabled'] = (bool) $values['datalayer_property_enabled'];
			}

			if ( isset( $values['datalayer_property_formatted_name'] ) )
			{
				$_values['formatted_name'] = $values['datalayer_property_formatted_name'];
			}

			if ( $property['custom'] ?? 0 )
			{
				foreach ( $values as $field => $value )
				{
					if ( isset( $_values[$field] ) )
					{
						continue;
					}

					$field = str_replace( 'datalayer_property_', '', $field );
					$_values[$field] = $value;
				}
			}

			/* Save the property */
			if ( !empty( $_values ) )
			{
				try
				{
					DataLayerClass::i()->savePropertyConfiguration( $property_key, $_values );
				}
				catch ( InvalidArgumentException $e ) {}
				
				Session::i()->log( 'acplog__data_layer_property_edited', array( $property_key => FALSE ) );
			}

			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=dataLayer&tab=properties&property_key=' . $property_key ) );
		}

		/* Load our events */
		$_events    = DataLayerClass::i()->getPropertyEvents( $property_key );
		$events     = array();
		foreach( $_events as $key => $event )
		{
			$events[$key] = array(
				'name'          => $event['formatted_name'],
				'key'           => $key,
				'enabled'       => $event['enabled'],
				'short'         => $event['short'],
			);
		}

		/* Property's events table */
		if ( empty( $events ) )
		{
			$eventTable = "";
		}
		else
		{
			$eventTable = new Custom(
				$events,
				Url::internal( 'app=core&module=settings&controller=dataLayer&tab=properties&property_key=' . $property_key )
			);



			if ( !Request::i()->sortby )
			{
				$eventTable->sortBy = 'name';
			}
			if ( !Request::i()->sortdirection )
			{
				$eventTable->sortDirection = 'asc';
			}

			$eventTable->langPrefix = 'datalayer_';
			$eventTable->exclude    = array( 'description', 'key' );
			$eventTable->parsers    = array(
				'name'      => function ( $val, $row )
				{
					$url    = Url::internal('app=core&module=settings&controller=dataLayer&tab=events&event_key=' . $row['key']);
					$title  = $row['short'];
					return  "<span class='i-font-family_monospace'><a href='$url' data-ipstooltip title='$title'>$val</a></span>";
				},
				'enabled'   => array( $this, '_enabledDisabled' ),
			);
		}

		/* Render Content */
		$content = Theme::i()->getTemplate( 'settings', 'core', 'admin' )->dataLayerTitleContent(
			$property,
			$property_key,
			'property',
			$form,
			(string) $eventTable
		);

		return Theme::i()->getTemplate( 'settings', 'core', 'admin' )->dataLayerTab( $propertySelector, $content );
	}

	/**
	 * Property settings and configuration
	 *
	 * @return void
	 */
	protected function addProperty() : void
	{
		Output::i()->title = 'Add property';
		$form = new Form( 'datalayer_add_property' );
		$form->add( new Text( 'datalayer_property_formatted_name', null, true, array(), $this->formattedNameValid() ) );
		$form->add( new Text( 'datalayer_property_value', null, true ) );
		$form->add( new TextArea( 'datalayer_property_description', null, true ) );
		$form->add( new Text( 'datalayer_property_short', null, false, array( 'maxLength' => 50 ) ) );
		$form->add( new YesNo( 'datalayer_property_page_level', 1, true ) );

		$events = DataLayerClass::i()->eventConfiguration;
		$options = array();
		foreach ( $events as $key => $data )
		{
			$options[$key]  = $data['formatted_name'];
		}

		$form->add( new CheckboxSet( 'datalayer_property_event_keys', array(), false, array('options' => $options, 'multiple' => 1) ) );

		if ( $values = $form->values() )
		{
			Session::i()->csrfCheck();
			$_values = array();
			foreach ( $values as $field => $value )
			{
				$field = str_replace( 'datalayer_property_', '', $field );
				$_values[$field] = $value;
			}
			$values = $_values;

			$values['enabled'] = true;
			$key = $values['formatted_name'];

			DataLayerClass::i()->savePropertyConfiguration( $key, $values, true );
			
			Session::i()->log( 'acplog__data_layer_property_added', array( $key => FALSE ) );

			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=dataLayer&tab=properties&property_key=' . $key ) );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * Delete a property
	 */
	protected function deleteProperty() : void
	{
		Session::i()->csrfCheck();

		$key = Request::i()->property_key;
		$propertiesUrl = Url::internal( 'app=core&module=settings&controller=dataLayer&tab=properties' );
		if ( empty( $key ) )
		{
			Output::i()->redirect( $propertiesUrl );
		}

		$properties = DataLayerClass::i()->propertiesConfiguration;
		if ( isset( $properties[$key] ) )
		{
			if ( $properties[$key]['custom'] ?? 0 )
			{
				$setting = json_decode( Settings::i()->core_datalayer_properties, true ) ?: array();
				unset( $setting[$key] );
				Settings::i()->changeValues([ 'core_datalayer_properties' => json_encode( $setting ) ]);
				DataLayerClass::i()->clearCachedConfiguration();
				Session::i()->log( 'acplog__data_layer_property_deleted', array( $key => FALSE ) );
				$_SESSION['deleted_datalayer_property'] = 1;
			}
			else
			{
				$propertiesUrl = $propertiesUrl->setQueryString([ 'property_key' => $key ]);
			}
		}
		Output::i()->redirect( $propertiesUrl );
	}

	/**
	 * Event settings and configuration
	 *
	 * @return string
	 */
	protected function events() : string
	{
		/* Load our events */
		$events = DataLayerClass::i()->eventConfiguration;
		$event_key = Request::i()->event_key ?: 'content_create';

		/* Do we know the recognized one? */
		if ( !isset( $events[$event_key] ) )
		{
			Output::i()->redirect( Request::i()->url()->stripQueryString([ 'event_key' ]) );
		}
		$event                      = $events[$event_key];

		foreach ( array_keys( $events ) as $_eventKey )
		{
			$events[$_eventKey]['description'] = 'Fires when ' . $events[$_eventKey]['description'];
		}

		/* Create our sidebar */
		$eventSelector  = Theme::i()->getTemplate( 'settings', 'core', 'admin' )->dataLayerSelector(
			$events,
			'events',
			'event_key',
			'Data Layer Events',
			$event_key,
			true,
			array( $this, '_truncate' )
		);

		/* Form */
		$form           = new Form( 'datalayer_events_' . $event_key, 'save',  Url::internal( 'app=core&module=settings&controller=dataLayer&tab=events&event_key=' . $event_key ) );
		$form->class    = 'ipsForm--vertical ipsForm--datalayer-events';

		$form->add( new YesNo( 'datalayer_event_enabled', $event['enabled'], false ) );
		$form->add( new Text( 'datalayer_event_formatted_name', $event['formatted_name'], false, array(), $this->formattedNameValid( 'events', $event_key ) ) );

		/* We only want to change values when a real event is specified in the request */
		if ( ( $values = $form->values() ) AND Request::i()->event_key === $event_key )
		{
			/* Pull out the values to use */
			$_values = array();
			if ( isset( $values['datalayer_event_enabled'] ) )
			{
				$_values['enabled'] = (bool) $values['datalayer_event_enabled'];
			}

			if ( isset( $values['datalayer_event_formatted_name'] ) )
			{
				$_values['formatted_name'] = $values['datalayer_event_formatted_name'];
			}

			/* Save the event */
			if ( !empty( $_values ) )
			{
				try
				{
					DataLayerClass::i()->saveEventConfiguration( $event_key, $_values );
				}
				catch ( InvalidArgumentException $e ) {}
			}
			
			Session::i()->log( 'acplog__data_layer_event_edited', array( $event_key => FALSE ) );

			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=dataLayer&tab=events&event_key=' . $event_key ) );
		}


		/* Load our properties */
		$_properties    = DataLayerClass::i()->getEventProperties( $event_key );
		$properties     = array();
		foreach( $_properties as $key => $property )
		{
			$properties[$key] = array(
				'name'      => $property['formatted_name'],
				'key'       => $key,
				'type'      => $property['type'],
				'enabled'   => $property['enabled'],
				'pii'       => $property['pii'],
				'custom'    => $property['custom'] ?? 0,
				'short'     => $property['short'],
			);
		}

		/* Create our neat properties table */
		if ( empty( $properties ) )
		{
			$propertyTable = "";
		}
		else
		{
			$propertyTable = new Custom(
				$properties,
				Url::internal( 'app=core&module=settings&controller=dataLayer&tab=events&event_key=' . $event_key )
			);

			if ( !Request::i()->sortby )
			{
				$propertyTable->sortBy  = 'name';
			}

			if ( !Request::i()->sortdirection )
			{
				$propertyTable->sortDirection   = 'asc';
			}

			$propertyTable->langPrefix  = 'datalayer_';
			$propertyTable->exclude     = array( 'custom', 'short', 'key' );
			$propertyTable->parsers     = array(
				'enabled'   => array( $this, '_enabledDisabled' ),
				'pii'       => array( $this, '_yesNo' ),
				'name'      => function ( $val, $row )
				{
					$url     = Url::internal( 'app=core&module=settings&controller=dataLayer&tab=properties&property_key=' . $row['key'] );
					$title   = $row['short'];
					return  "<pre><a href='$url' data-ipstooltip title='$title' >$val</a></pre>";
				},
			);
		}

		/* Render Content */
		$event['description'] = "Fires when {$event['description']}";
		$content = Theme::i()->getTemplate( 'settings', 'core', 'admin' )->dataLayerTitleContent(
			$event,
			$event_key,
			'event',
			(string) $form,
			(string) $propertyTable
		);

		return Theme::i()->getTemplate( 'settings', 'core', 'admin' )->dataLayerTab( $eventSelector, $content );
	}

	/**
	 * Is the value a valid property or event formatted name?
	 *
	 * @param   string      $group      Either properties or events; the datalayer collection to test the key against
	 * @param   ?string     $current    The key of the property/event that currently has this value set as its formatted name
	 *
	 * @return callable
	 */
	public function formattedNameValid( string $group='properties', ?string $current=null ) : callable
	{
		return function( $value ) use ( $group, $current )
		{
			if ( $group === 'properties' AND strtolower( $value ) === 'event' )
			{
				throw new InvalidArgumentException( "You cannot name a property '$value' because that key is reserved" );
			}

			if ( preg_match( '/[^a-zA-Z_]/', $value ) )
			{
				throw new InvalidArgumentException( 'The name can only contain letters and underscores.' );
			}

			$collection = ( $group === 'events' ) ? DataLayerClass::i()->eventConfiguration : DataLayerClass::i()->propertiesConfiguration;
			foreach ( $collection as $key => $data )
			{
				if ( $key === $current )
				{
					continue;
				}

				if ( isset( $data['formatted_name'] ) AND $data['formatted_name'] === $value )
				{
					throw new InvalidArgumentException( 'This Data Layer name is already in use' );
				}
			}
		};
	}

	/**
	 * Formatter method to make code cleaner
	 *
	 * @param mixed   $val
	 * @param array|null $row	 *
	 * @return  string
	 */
	public function _enabledDisabled( mixed $val, ?array $row=null ) : string
	{
		return $val ? 'Enabled' : 'Disabled';
	}

	/**
	 * Formatter method to make code cleaner
	 *
	 * @param   mixed   $val
	 * @param array|null $row
	 * @return  string
	 */
	public function _yesNo( mixed $val, ?array $row=null ) : string
	{
		return $val ? 'Yes' : 'No';
	}

	/**
	 * Truncates the string and removes tags. Ends in ... if the string is longer than the limit
	 *
	 * @param   string  $_string    The string to truncate
	 * @param   int     $length     The desired length
	 *
	 * @return  string
	 */
	public function _truncate( string $_string, int $length=100 ) : string
	{
		$_string   = strip_tags( $_string );
		$string    = substr( $_string, 0, $length );
		$changedLength  = ( strlen( $_string ) !== strlen( $string ) );
		$string    = trim( $string, ". \n\r\t\v\x00" );
		if ( $changedLength )
		{
			$string = trim( substr( $string, 0, $length - 3 ), ". \n\r\t\v\x00" ) . '...';
		}
		return $string;
	}

}