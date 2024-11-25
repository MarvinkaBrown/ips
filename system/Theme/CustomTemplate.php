<?php
/**
 * @brief		Custom Template Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2nd May 2023
 */

namespace IPS\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ErrorException;
use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Log;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Http\Url;
use IPS\Request;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use ParseError;
use UnderflowException;
use UnexpectedValueException;
use function array_keys;
use function array_merge;
use function count;
use function defined;
use function file_get_contents;
use function implode;
use function in_array;
use function preg_match;
use function str_replace;
use const IPS\DEBUG_CUSTOM_TEMPLATES;
use const IPS\IN_DEV;
use const IPS\DEBUG_TEMPLATES;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom Template Class
 */
class CustomTemplate extends Model
{
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons = array();

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_theme_templates_custom';

	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = ['template_key'];

	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'template_';

	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'custom_templates';

	/**
	 * @brief	[Node] Sortable
	 */
	public static bool $nodeSortable = TRUE;

	/**
	 * @brief	[Node] Positon Column
	 */
	public static ?string $databaseColumnOrder = 'order';

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'custom_templates', 'custom_templates_rows' );

	/**
	 * @brief Templates already loaded and evald
	 */
	public static array $calledTemplates = array();

	/**
	 * @brief Current scope? (app/theme)
	 */
	public static string $scopeKey = '';
	public static string $scopeValue = '';

	/**
	 * Return the cleaned name, suitable for a PHP function
	 *
	 * @return string
	 */
	public function cleanName(): string
	{
		return Theme::cleanGroupName( $this->name );
	}

	/**
	 * Return all templates
	 *
	 * @return ActiveRecordIterator
	 */
	public static function all(): ActiveRecordIterator
	{
		return new ActiveRecordIterator( Db::i()->select( '*', static::$databaseTable, null, static::$databasePrefix . static::$databaseColumnOrder ), 'IPS\Theme\CustomTemplate' );
	}

	/**
	 * Get all custom templates that match a main template path (eg core/front/global/globalTemplate)
	 *
	 * @param $templatePath
	 * @return ActiveRecordIterator
	 */
	public static function byHookableTemplatePath( $templatePath ): ActiveRecordIterator
	{
		return new ActiveRecordIterator( Db::i()->select( '*', static::$databaseTable, [ Db::i()->like( 'template_hookpoint', $templatePath ) ] ), 'IPS\Theme\CustomTemplate' );
	}

	/**
	 * Get the template this custom template hooks into
	 *
	 * @param string|null $fullPath
	 * @return array|null
	 */
	public function hookedTemplate( ?string $fullPath=NULL ): ?array
	{
		$fullPath = $fullPath ?: $this->hookpoint;
		[ $path, $hook ] = explode( ':', $fullPath );
		$pathData = CustomTemplate::pathToArray( $path );

		if ( IN_DEV )
		{
			$template = Theme::i()->getTemplate( $pathData['group'], $pathData['app'], $pathData['location'] );

			$html = @file_get_contents( $template->sourceFolder . $pathData['templateName'] . '.phtml' );

			if ( ! $html )
			{
				return null;
			}

			/* Parse the header tag */
			preg_match( '/^<ips:template parameters="(.+?)?"(.+?)?\/>(\r\n?|\n)/', $html, $params );

			/* Strip it */
			$html = ( isset($params[0]) ) ? str_replace( $params[0], '', $html ) : $html;

			return [
				'template_content' => $html,
				'template_app'     => $pathData['app'],
				'template_location'=> $pathData['location'],
				'template_group'   => $pathData['group'],
				'template_name'    => $pathData['templateName'],
				'template_data'    => ( isset( $params[1] ) ) ? $params[1] : '',
				'template_set_id'  => 0
			];
		}
		else
		{
			try
			{
				return Db::i()->select( '*', 'core_theme_templates', array('template_set_id=? and template_app=? and template_location=? and template_group=? and template_name=?', 0, $pathData['app'], $pathData['location'], $pathData['group'], $pathData['templateName']) )->first();
			}
			catch ( UnderflowException $e )
			{
				return null;
			}
		}
	}

	/**
	 * We want a single template PHP function to be grouped by a common name
	 * The common name would be something like core_front_global_globalTemplate__hookpoint__hookPointType
	 *
	 * @return string
	 */
	public function groupedPhpFunctionName(): string
	{
		[ $path, $hook ] = explode( ':', $this->hookpoint );
		return str_replace( '/', '_', $path ) . '__' . $hook . '__' . str_replace( '-', '', $this->hookpoint_type );
	}

	/**
	 * Convert the path and hookpoint to a PHP function name
	 * @param string $path
	 * @param string $hookpoint
	 * @param string $hooktype
	 * @return string
	 */
	public static function pathAndHookPointToFunctionName( string $path, string $hookpoint, string $hooktype ): string
	{
		return str_replace( '/', '_', $path ) . '__' . $hookpoint . '__' . str_replace( '-', '', $hooktype );
	}

	/**
	 * Return the available params for the template this hooks into
	 *
	 * @param string|null $params
	 * @param bool $returnVariablesOnly Only return the variable names, not the ='foo' defaults
	 * @return array|null
	 */
	public function availableParams( ?string $params=null, bool $returnVariablesOnly = false ): array|null
	{
		if ( $template = $this->hookedTemplate() and $template['template_content'] )
		{
			$params = ( $params ) ?: $template['template_data'];
			if ( $returnVariablesOnly )
			{
				$matches = [];
				/* We just want the $varname, not the ='foo' defaults */
				preg_match_all( '/(\$[a-zA-Z0-9_]+)/', $params, $matches );
				return array_unique( $matches[1] );
			}
			else
			{
				// Extract key-value pairs using regular expression
				preg_match_all('/(\$[a-zA-Z_][a-zA-Z0-9_]*)(\s*=\s*("[^"]*"|\'[^\']*\'|[^\s,]*))?/', $params, $matches);

				$results = [];
				foreach( $matches[0] as $row ) {
					$results[] = $row;
				}

				return $results;
			}
		}

		return NULL;
	}

	/**
	 * Convert the stored path string to an array of app/location/group/name params
	 *
	 * @param string $path
	 * @return array
	 */
	public static function pathToArray( string $path ): array
	{
		$bits = explode( "/", $path );
		$app = array_shift( $bits );
		$location = array_shift( $bits );
		$group = array_shift( $bits );
		$templateName = implode( "_", $bits );
		//[ $app, $location, $group, $templateName ] = explode( '/', $path );

		return [
			'app' => $app,
			'location' => $location,
			'group' => $group,
			'templateName' => $templateName
		];
	}

	/**
	 * Compile custom templates into a single data store object
	 *
	 * @return	boolean|null
	 */
	public static function compileCustomTemplates(): ?bool
	{
		$flagKey = 'template_compiling_custom';
		if ( Theme::checkLock( $flagKey ) )
		{
			return NULL;
		}
		Theme::lock( $flagKey );

		$functions = [];
		$organised = [];
		foreach( static::all() as $template )
		{
			/* Only use templates that are enabled */
			if ( $template->app and ! in_array( $template->app, array_keys( Application::enabledApplications() ) ) )
			{
				continue;
			}

			if ( ! $template->hookpoint )
			{
				if ( $template->key )
				{
					/* This isn't using a hookpoint, so save it as a function we can call directly */
					$organised[ 'custom_nohook_' . $template->key ] = [ 'content' => $template->content, 'params' => '' ];
				}
			}
			else
			{
				if ( ! isset( $organised[ $template->groupedPhpFunctionName() ] ) )
				{
					$paramList = '';
					if ( $template->availableParams() )
					{
						$paramList = implode( ',', $template->availableParams() );
					}

					$organised[ $template->groupedPhpFunctionName() ] = [ 'params' => $paramList, 'content' => $template->contentForPhpFunction() ];
				}
				else
				{
					$organised[ $template->groupedPhpFunctionName() ]['content'] .= $template->contentForPhpFunction();
				}
			}
		}

		foreach( $organised as $functionName => $data )
		{
			$functions[] = Theme::_compileTemplate( $data['content'], $functionName, TRUE, FALSE, $data['params'] );
		}

		/* Put them in a class */
		$template = <<<EOF
namespace _NAMESPACE_;
class CustomTemplateClass
{
EOF;

		$template .= implode( "\n\n", $functions );
		$template .= <<<EOF
}
EOF;

		Store::i()->custom_templates = str_replace( 'namespace _NAMESPACE_', 'namespace IPS\Theme', $template );

		Theme::unlock( $flagKey );
		return TRUE;
	}

	public function contentForPhpFunction(): string
	{
		$header = "\n<!--CT: " . $this->name . "-->\n";

		if ( $this->set_id )
		{
			/* Is this for a specific theme set? */
			$content = $header . "{{if \IPS\Theme::i()->id == {$this->set_id}}}\n" . $this->content . "\n{{endif}}";
		}
		elseif ( $this->app )
		{
			/* Is this for a specific app? */
			$content = $header . "{{if \IPS\Application::appIsEnabled( '{$this->app}' )}}\n" . $this->content . "\n{{endif}}";
		}
		else
		{
			/* This is a global template */
			$content = $header . $this->content;
		}

		return $content;
	}

	/**
	 * @param string $name core/front/global/globalTemplate
	 * @param string $hookPoint body:inside-start
	 * @return string
	 * @throws ErrorException
	 */
	public static function getCustomTemplatesForHookPoint( string $name, string $hookPoint ): string
	{
		if ( $hookPoint )
		{
			[$hookName, $hookType] = explode( ':', $hookPoint );
		}
		else
		{
			$hookName = null;
			$hookType = null;
		}
		$args = [];
		/* The {customtemplate..} tag when automatically added via static::expandHookPoints() will pass in the arguments
		   to the function, so we need to grab them here. This allows those scoped variables to be used
		   in the custom templates we create */
		if ( func_num_args() > 2 )
		{
			/* The forth argument is an array of the template_data params */
			$args = func_get_arg(2);
		}

		if ( !in_array( 'custom_templates', array_keys( static::$calledTemplates ) ) )
		{
			/* If we don't have a compiled template, do that now */
			if ( !isset( Store::i()->custom_templates ) )
			{
				/* It can take a few seconds for templates to finish compiling if initiated elsewhere, so let's try a few times sleeping 1 second between attempts
				   to give the compilation time to finish */
				$attempts = 0;
				while( $attempts < 6 )
				{
					$built = CustomTemplate::compileCustomTemplates();

					if ( $built === NULL )
					{
						$attempts++;
						sleep(1);
					}
					else
					{
						break;
					}
				}

				/* Still no key? */
				if ( ! isset( Store::i()->custom_templates ) )
				{
					Log::log( "Custom templates store missing", "template_store_missing" );
					throw new ErrorException( 'template_store_missing' );
				}
			}

			if ( ! class_exists( '\IPS\Theme\CustomTemplateClass', false ) )
			{
				$storedTemplate = Store::i()->custom_templates;

				if ( DEBUG_TEMPLATES )
				{
					Theme::runDebugTemplate( 'custom_template', $storedTemplate );
				}
				else
				{
					try
					{
						if ( @eval( $storedTemplate ) === FALSE )
						{
							if ( IN_DEV )
							{
								throw new TemplateException( 'Invalid Custom Template' );
							}
						}
					}
					catch ( ParseError $e )
					{
						throw new UnexpectedValueException;
					}
				}

				/* Init */
				static::$calledTemplates['custom_template'] = new \IPS\Theme\CustomTemplateClass;
			}
		}

		if ( $hookPoint )
		{
			$functionName = CustomTemplate::pathAndHookPointToFunctionName( $name, $hookName, $hookType );
		}
		else
		{
			$functionName = "custom_nohook_" . $name;
		}

		/* If we're debugging, unset the store on shutdown */
		if ( DEBUG_CUSTOM_TEMPLATES )
		{
			register_shutdown_function( function() {
				unset( Store::i()->custom_templates );
			} );
		}

		if ( method_exists( static::$calledTemplates['custom_template'], $functionName ) )
		{
			return static::$calledTemplates['custom_template']->$functionName( ...$args );
		}
		else
		{
			return '';
		}
	}

	/* Node class handler stuff */
	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param mixed|array $where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck ='view', $member=NULL, $where=array(), $limit=NULL ): array
	{
		if ( !count( $where ) )
		{
			$return = array();
			foreach( static::getStore() AS $node )
			{
				if ( static::$scopeKey === 'set_id' )
				{
					if ( $node['template_set_id'] != static::$scopeValue )
					{
						continue;
					}
				}

				$return[ $node['template_id'] ] = static::constructFromData( $node );
			}

			return $return;
		}
		else
		{
			return parent::roots( $permissionCheck, $member, $where, $limit );
		}
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 * @note	Note that all records are returned, even disabled promotion rules. Enable status needs to be checked in userland code when appropriate.
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->custom_templates_rows) )
		{
			$select = [
				'template_id',
				'template_name',
				'template_hookpoint',
				'template_updated',
				'template_version',
				'template_set_id',
				'template_app',
				'template_hookpoint_type',
				'template_key',
				'template_order'
			];

			Store::i()->custom_templates_rows = iterator_to_array( Db::i()->select( implode( ', ', $select ), static::$databaseTable, NULL, "template_order ASC" )->setKeyField( 'template_id' ) );
		}

		return Store::i()->custom_templates_rows;
	}

	/**
	 * [Node] Get Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		return $this->name;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		/* Is there an update to show? */
		$badge = [];

		if ( $this->hookpoint and $this->hookpoint_type )
		{
			[ $path, $hook ] = explode( ':', $this->hookpoint );
			$pathData = CustomTemplate::pathToArray( $path );

			$badge = array(
				0 => 'style2',
				1 => implode( "/", $pathData ) . ':' . $hook . ' ' . $this->hookpoint_type
			);
		}
		else if ( $this->key )
		{
			$badge = array(
				0 => 'style3',
				1 => $this->key
			);
		}

		return $badge;
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );

		if ( isset( $buttons['copy'] ) )
		{
			unset( $buttons['copy'] );
		}

		$buttons['edit']['link'] = Url::internal( "app=core&module=customization&controller=customtemplates&do=form&" . static::$scopeKey. "=" . static::$scopeValue . "&id=" . $this->id );

		/* Remove the modal for editing */
		unset( $buttons['edit']['data']['ipsDialog'], $buttons['edit']['data']['ipsDialog-title'] );

		return $buttons;
	}

	/**
	 * [Node] Does the currently logged-in user have permission to edit permissions for this node?
	 * @note We currently do not use the permission system for templates.
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged-in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		return FALSE;
	}

	/**
	 * Form
	 *
	 * @param Form $form	The form
	 * @return	void
	 */
	public function form( Form &$form ): void
	{
		//$form->class = 'ipsForm--vertical ipsForm--custom-template ipsForm_fullWidth';
		$form->attributes['data-controller'] = 'core.admin.customization.customTemplatesForm';
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_customization.js', 'core', 'admin' ) );

		$applications = [];
		$hookPointsSelect = [];
		$pathNamesSelect = [];
		foreach( Application::applications() as $application )
		{
			foreach ( Theme::i()->getHookPoints( [ $application->directory ] ) as $path => $hookPointNames )
			{
				$applications[ $application->directory ] = $application->_title;
				foreach ( $hookPointNames as $hookPointName )
				{
					$fixedPath = preg_replace( '#^' . $application->directory . '/#', '', $path );
					$pathNamesSelect[ $application->directory ][ str_replace( '/', '_', $fixedPath ) ] = $fixedPath;
					$hookPointsSelect[ $application->directory . '_' . str_replace( '/', '_', $fixedPath ) ][ $hookPointName ] = $hookPointName;
				}
			}
		}

		/* Work out some defaults */
		$defaultHookapp = 'core';
		$defaultPath = '';
		$defaultTemplateHook = '';
		if ( $this->id and $this->hookpoint )
		{
			$array = static::pathToArray( $this->hookpoint );

			if ( isset( $array['app'] ) )
			{
				$defaultHookapp = $array['app'];
			}

			if ( isset( $array['group'] ) and isset( $array['templateName'] ) )
			{
				[ $templateName, $defaultTemplateHook ] = explode( ':', $array['templateName'] );
				$defaultPath = $array['location'] . '_' . $array['group'] . '_' . $templateName;
			}
		}

		$form->add( new Text( 'template_name', $this->id ? $this->name : NULL, true ) );
		$form->add( new Radio( 'template_type', ( $this->id and $this->hookpoint ) ? 'hook' : 'custom', true, array( 'options' => array( 'hook' => 'template_type_hook', 'custom' => 'template_type_custom' ) ), NULL, NULL, NULL, 'template_type' ) );
		$form->add( new Select( 'template_hookapp', $defaultHookapp, FALSE, array( 'options' => $applications ), NULL, NULL, NULL, 'template_hookapp' ) );

		foreach( $pathNamesSelect as $applicationDirectory => $path )
		{
			$form->add( new Select( 'template_path_' . $applicationDirectory, $defaultPath, FALSE, array( 'options' => $path, 'parse' => 'normal' ), NULL, NULL, Theme::i()->getTemplate( 'customization', 'core', 'admin' )->previewTemplateLink(), 'template_path_' . $applicationDirectory ) );
			Member::loggedIn()->language()->words[ 'template_path_' . $applicationDirectory ] = Application::load( $applicationDirectory )->_title;
		}

		foreach( $hookPointsSelect as $data => $hookPoints )
		{
			[ $applicationDirectory, $path ] = explode( '_', $data, 2 );

			$form->add( new Select( 'template_hookpoint_' . $applicationDirectory . '_' . $path, $defaultTemplateHook, FALSE, array( 'options' => $hookPoints, 'parse' => 'normal' ), NULL, NULL, NULL, 'template_hookpoint_' . $applicationDirectory . '_' . $path ) );
			Member::loggedIn()->language()->words[ 'template_hookpoint_' . $applicationDirectory . '_' . $path ] = Member::loggedIn()->language()->get('template_hookpoint');
		}

		$form->add( new Select( 'template_hookpoint_type', $this->id ? $this->hookpoint_type : 'before', FALSE, array( 'options' => [
			'before' => 'template_custom_before',
			'inside-start' => 'template_custom_inside_start',
			'inside-end' => 'template_custom_inside_end',
			'after' => 'template_custom_after',
		] ), NULL, NULL, NULL, 'template_hookpoint_type' ) );
		$form->add( new Text( 'template_key', $this->id ? $this->key : NULL, FALSE, [], function( $val )
		{
			try
			{
				if ( ! $val )
				{
					if ( Request::i()->template_type == 'custom' )
					{
						/* This is required if we're not using a hookpoint */
						throw new InvalidArgumentException('custom_template_key_cant_be_empty');
					}

					return true;
				}

				$val = Theme::cleanGroupName( $val );

				try
				{
					$template = CustomTemplate::load( $val, 'template_key');
				}
				catch( OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
					return true;
				}

				/* It's taken... */
				if ( Request::i()->id == $template->id )
				{
					/* But it's this one so that's ok */
					return true;
				}

				/* and if we're here, it's not... */
				throw new InvalidArgumentException('custom_template_key_not_unique');
			}
			catch ( OutOfRangeException $e )
			{
				/* Slug is OK as load failed */
				return true;
			}
		}, NULL, NULL, 'template_key' ) );

		$form->add( new Codemirror( 'template_content', $this->id ? $this->content : null, FALSE, [ 'codeModeAllowedLanguages' => [ 'ipsphtml' ] ], function( $val )
		{
			/* Test */
			try
			{
				$currentApp = Request::i()->template_hookapp;
				$params = '';

				if ( $currentApp !== 'none' )
				{
					$path = Request::i()->{'template_path_' . $currentApp};
					$hook = Request::i()->{'template_hookpoint_' . $currentApp . '_' . $path};

					$template = new CustomTemplate();
					$template->hookpoint = $currentApp . '/' . str_replace( '_', '/', $path ) . ':' . $hook;
					$template->hookpoint_type = Request::i()->template_hookpoint_type;
					$params = implode( ', ', $template->availableParams() );
				}

				Theme::checkTemplateSyntax( $val, $params );
			}
			catch( LogicException $e )
			{
				throw new LogicException('template_error_bad_syntax');
			}

		} ) );

		/* If we are editing, we can save and reload */
		if( $this->id )
		{
			$form->canSaveAndReload = true;
		}
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$values['template_updated']	= DateTime::create()->getTimestamp();
		$values['template_version']	= Application::load('core')->long_version;
		$values['template_key'] = $values['template_key'] ? Theme::cleanGroupName( $values['template_key'] ) : null;

		if ( $values['template_type'] === 'hook' and isset( $values[ 'template_path_' . $values['template_hookapp'] ] ) )
		{
			$currentApp = $values['template_hookapp'];
			$path = $values[ 'template_path_' . $currentApp ];
			$hook = $values[ 'template_hookpoint_' . $currentApp . '_' . $path ];

			$values['template_hookpoint'] = $currentApp . '/' . str_replace( '_', '/', $path ) . ':' . $hook;
		}
		else
		{
			$values['template_hookpoint'] = null;
		}

		if ( static::$scopeKey === 'set_id' )
		{
			$values['template_set_id'] = (int) static::$scopeValue;
		}
		else if ( static::$scopeKey === 'appKey' )
		{
			$values['template_app'] = static::$scopeValue;
		}

		/* Unset some things we don't need */
		foreach( $values as $k => $v )
		{
			if( $k == 'template_hookpoint_type' )
			{
				continue;
			}

			if ( stristr( $k, 'template_hookpoint_' ) or stristr( $k, 'template_path_' ) )
			{
				unset( $values[ $k ] );
			}
		}

		unset( $values['template_hookapp'], $values['template_type'] );
		return parent::formatFormValues( $values );
	}

	/**
	 * Import custom templates from a json file
	 *
	 * @param string $file
	 * @param string $appKey
	 * @param int|null $offset
	 * @param int|null $limit
	 * @return int
	 */
	public static function importFromFile( string $file, string $appKey, ?int $offset=null, ?int $limit=null ) : int
	{
		$templates = json_decode( file_get_contents( $file ), true );
		$keys = array_keys( $templates );
		$offset = $offset ?? 0;
		if( $limit !== null )
		{
			$keys = array_slice( $keys, $offset, $limit );
		}

		$current = iterator_to_array(
			Db::i()->select( '*', static::$databaseTable, [ 'template_app=?', $appKey ] )
				->setKeyField( 'template_name' )
		);

		$imported = 0;
		foreach( $keys as $name )
		{
			$data = $templates[ $name ];

			if( isset( $current[ $name ] ) )
			{
				Db::i()->update( static::$databaseTable, [
					'template_hookpoint' => $data['hookpoint'],
					'template_hookpoint_type' => $data['type'],
					'template_key' => $data['key'],
					'template_version' => $data['version'],
					'template_content' => $data['content'],
					'template_updated' => time()
				], [ 'template_id=?', $current[ $name ]['template_id'] ] );

				unset( $current[ $name ] );
			}
			else
			{
				Db::i()->insert( static::$databaseTable, [
					'template_app' => $appKey,
					'template_name' => $name,
					'template_hookpoint' => $data['hookpoint'],
					'template_hookpoint_type' => $data['type'],
					'template_key' => $data['key'],
					'template_version' => $data['version'],
					'template_content' => $data['content'],
					'template_updated' => time()
				]);
			}

			$imported++;
		}

		Db::i()->delete( static::$databaseTable, [
			[ 'template_app=?', $appKey ],
			[ Db::i()->in( 'template_name', array_keys( $current ) ) ]
		]);

		return $imported;
	}
}