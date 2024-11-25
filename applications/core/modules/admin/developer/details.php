<?php
/**
 * @brief		details
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		06 Feb 2024
 */

namespace IPS\core\modules\admin\developer;

use DirectoryIterator;
use IPS\Content\Item;
use IPS\Developer\Controller;
use IPS\Extensions\NotificationsAbstract;
use IPS\Helpers\Form;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Permissions;
use IPS\Output;
use IPS\Theme;
use function count;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * details
 */
class details extends Controller
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
		Output::i()->sidebar['actions']['edit'] = [
			'icon' => 'pencil',
			'title' => 'edit',
			'link' => $this->url->setQueryString( 'do', 'edit' )
		];

		Output::i()->globalControllers[] = 'core.admin.developer.details';
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'dev_details' );
		Output::i()->output = Theme::i()->getTemplate( 'developer' )->dashboard( $this->application );
	}

	/**
	 * @return void
	 */
	protected function edit() : void
	{
		$form = new Form;
		$this->application->form( $form );

		if( $values = $form->values() )
		{
			$this->application->saveForm( $this->application->formatFormValues( $values ) );

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * @return void
	 */
	protected function scan() : void
	{
		/* If this is a 1st party app, and we don't have our super special constant, don't scan it */
		if( in_array( $this->application->directory, IPS::$ipsApps ) and ( !defined( '\IPS\USE_DEVELOPMENT_BUILDS' ) or \IPS\USE_DEVELOPMENT_BUILDS === false ) )
		{
			Output::i()->json( array(
				'html' => Theme::i()->getTemplate( 'support' )->supportBlockList( array() ),
				'criticalIssues' => 0,
				'recommendedIssues' => 0
			) );
		}

		$scanResults = array( 'advice' => 0, 'failures' => 0, 'list' => array() );

		$languageStrings = Lang::readLangFiles( $this->application->directory );

		$missingAdminStrings = $this->_scanAdminStrings( $languageStrings );
		if( count( $missingAdminStrings ) )
		{
			$total = 0;
			foreach( $missingAdminStrings as $k => $v )
			{
				$total += count( $v );
			}

			$scanResults['failures']++;
			$scanResults['list'][] = array(
				'critical'		=> TRUE,
				'advice'		=> FALSE,
				'success'		=> FALSE,
				'element'		=> 'acpDevLang',
				'dialogTitle'	=> Member::loggedIn()->language()->addToStack( 'devscan__missing_admin_strings_title' ),
				'detail'		=> Member::loggedIn()->language()->addToStack( 'devscan__missing_admin_strings', false, array( 'pluralize' => array( $total, $total ) ) ),
				'body'			=> Theme::i()->getTemplate( 'developer' )->missingStrings( $missingAdminStrings, $this->application->directory ),
				'learnmore'		=> true
			);
		}

		$missingFrontStrings = $this->_scanFrontStrings( $languageStrings );
		if( count( $missingFrontStrings ) )
		{
			$total = 0;
			foreach( $missingFrontStrings as $k => $v )
			{
				$total += count( $v );
			}

			$scanResults['failures']++;
			$scanResults['list'][] = array(
				'critical'		=> true,
				'advice'		=> false,
				'success'		=> false,
				'element'		=> 'frontDevLang',
				'dialogTitle'	=> Member::loggedIn()->language()->addToStack( 'devscan__missing_front_strings_title' ),
				'detail'		=> Member::loggedIn()->language()->addToStack( 'devscan__missing_front_strings', false, array( 'pluralize' => array( $total, $total ) ) ),
				'body'			=> Theme::i()->getTemplate( 'developer' )->missingStrings( $missingFrontStrings, $this->application->directory ),
				'learnmore'		=> true
			);
		}

		$missingSearchKeywords = $this->_scanSearchKeywords( $languageStrings );
		if( count( $missingSearchKeywords ) )
		{
			$total = 0;
			foreach( $missingSearchKeywords as $k => $v )
			{
				$total += count( $v );
			}

			$scanResults['failures']++;
			$scanResults['list'][] = array(
				'critical'		=> true,
				'advice'		=> false,
				'success'		=> false,
				'element'		=> 'acpDevSearchKeywords',
				'dialogTitle'	=> Member::loggedIn()->language()->addToStack( 'devscan__missing_search_keywords_title' ),
				'detail'		=> Member::loggedIn()->language()->addToStack( 'devscan__missing_search_keywords', false, array( 'pluralize' => array( $total, $total ) ) ),
				'body'			=> Theme::i()->getTemplate( 'developer' )->missingSearchKeywords( $missingSearchKeywords, $this->application->directory ),
				'learnmore'		=> true
			);
		}

		$duplicateSearchKeywords = $this->_scanSearchKeywordsForDupes();
		if( count( $duplicateSearchKeywords ) )
		{
			$total = count( $duplicateSearchKeywords );

			$scanResults['advice']++;
			$scanResults['list'][] = array(
				'critical'		=> false,
				'advice'		=> true,
				'success'		=> false,
				'element'		=> 'acpDevSearchKeywordsDupes',
				'dialogTitle'	=> Member::loggedIn()->language()->addToStack( 'devscan__dupe_search_keywords_title' ),
				'detail'		=> Member::loggedIn()->language()->addToStack( 'devscan__dupe_search_keywords', false, array( 'pluralize' => array( $total, $total ) ) ),
				'body'			=> Theme::i()->getTemplate( 'developer' )->duplicateSearchKeywords( $duplicateSearchKeywords, $this->application->directory ),
				'learnmore'		=> true
			);
		}

		$missingEmailTemplates = $this->_checkEmailTemplates();
		if( count( $missingEmailTemplates ) )
		{
			$scanResults['failures']++;
			$scanResults['list'][] = array(
				'critical'		=> true,
				'advice'		=> false,
				'success'		=> false,
				'element'		=> 'frontDevEmail',
				'dialogTitle'	=> Member::loggedIn()->language()->addToStack( 'devscan__missing_emailtpl_title' ),
				'detail'		=> Member::loggedIn()->language()->addToStack( 'devscan__missing_emailtpl', false, array( 'pluralize' => array( count( $missingEmailTemplates ), count( $missingEmailTemplates ) ) ) ),
				'body'			=> Theme::i()->getTemplate( 'developer' )->missingTemplates( $missingEmailTemplates, $this->application->directory ),
				'learnmore'		=> true
			);
		}

		/* Check for content-related extensions */
		foreach( $this->application->extensions( 'core', 'ContentRouter' ) as $ext )
		{
			/* @var Item $itemClass */
			foreach( $ext->classes as $itemClass )
			{
				/* Bypass for Pages */
				if( strpos( $itemClass, 'IPS\cms\Records' ) !== false )
				{
					continue;
				}

				if( isset( $itemClass::$containerNodeClass ) )
				{
					$class = $itemClass::$containerNodeClass;
					if( in_array( Permissions::class, class_implements( $class ) ) )
					{
						$permissionsExtension = false;
						foreach( $this->application->extensions( 'core', 'Permissions' ) as $ext )
						{
							if( array_key_exists( $class, $ext->getNodeClasses() ) )
							{
								$permissionsExtension = true;
								break;
							}
						}

						if( !$permissionsExtension )
						{
							$scanResults['advice']++;
							$scanResults['list'][] = array(
								'critical' => false,
								'advice' => true,
								'success' => false,
								'detail' => Member::loggedIn()->language()->addToStack( 'devscan__missing_permissions', false, array( 'sprintf' => $class ) )
							);
						}
					}
				}

				/* Check EditorLocation for comments/reviews */
				$missingExtensions = [];
				$extensions = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/extensions.json" );
				if( isset( $itemClass::$commentClass ) or isset( $itemClass::$reviewClass ) )
				{
					$key = IPS::mb_ucfirst( $itemClass::$module );
					if( !isset( $extensions['core']['EditorLocations'] ) or !array_key_exists( $key, $extensions['core']['EditorLocations'] ) )
					{
						$missingExtensions[] = $this->application->directory . '_' . $key;
						$scanResults['failures']++;
						$scanResults['list'][] = array(
							'critical' => true,
							'advice' => false,
							'success' => false,
							'detail' => Member::loggedIn()->language()->addToStack( 'devscan__missing_editor', false, array( 'sprintf' => $this->application->directory . '_' . $key ) )
						);
					}
				}

				/* Check form elements */
				foreach( $itemClass::formElements( null, null ) as $element )
				{
					/* Check editor fields for missing EditorLocation extensions */
					if( $element instanceof Form\Editor )
					{
						/* Did we already check this? */
						if( in_array( $element->options['app'] . '_' . $element->options['key'], $missingExtensions ) )
						{
							continue;
						}

						$json = ( $element->options['app'] == $this->application->directory ) ? $extensions : $this->_getJson( ROOT_PATH . "/applications/" . $element->options['app'] . "/data/extensions.json" );

						if( !isset( $json['core']['EditorLocations'] ) or !array_key_exists( $element->options['key'], $json['core']['EditorLocations'] ) )
						{
							$missingExtensions[] = $element->options['app'] . '_' . $element->options['key'];
							$scanResults['failures']++;
							$scanResults['list'][] = array(
								'critical' => true,
								'advice' => false,
								'success' => false,
								'detail' => Member::loggedIn()->language()->addToStack( 'devscan__missing_editor', false, array( 'sprintf' => $element->options['app'] . '_' . $element->options['key'] ) )
							);
						}
					}
					elseif( $element instanceof Form\Upload and isset( $element->options['storageExtension'] ) )
					{
						/* Did we already check this? */
						if( in_array( $element->options['storageExtension'], $missingExtensions ) )
						{
							continue;
						}

						list( $app, $key ) = explode( "_", $element->options['storageExtension'] );
						$json = ( $app == $this->application->directory ) ? $extensions : $this->_getJson( ROOT_PATH . "/applications/" . $app . "/data/extensions.json" );

						if( !isset( $json['core']['FileStorage'] ) or !array_key_exists( $key, $json['core']['FileStorage'] ) )
						{
							$missingExtensions[] = $element->options['storageExtension'];
							$scanResults['failures']++;
							$scanResults['list'][] = array(
								'critical' => true,
								'advice' => false,
								'success' => false,
								'detail' => Member::loggedIn()->language()->addToStack( 'devscan__missing_filestorage', false, array( 'sprintf' => $element->options['storageExtension'] ) )
							);
						}
					}
				}
			}
		}

		if( count( $this->application->modules( 'front' ) ) )
		{
			/* Check for Front Navigation items */
			$frontNavigation = $this->application->extensions( 'core', 'FrontNavigation', false );
			if( !count( $frontNavigation ) )
			{
				$scanResults['advice']++;
				$scanResults['list'][] = array(
					'critical' => false,
					'advice' => true,
					'success' => false,
					'detail' => Member::loggedIn()->language()->addToStack( 'devscan__missing_frontnav' )
				);
			}

			/* Check FURLs */
			$furls = $this->parseFurlsFile( ROOT_PATH . "/applications/" . $this->application->directory . "/data/furl.json" );
			if( !isset( $furls['pages'] ) or empty( $furls['pages'] ) )
			{
				$scanResults['advice']++;
				$scanResults['list'][] = array(
					'critical' => false,
					'advice' => true,
					'success' => false,
					'detail' => Member::loggedIn()->language()->addToStack( 'devscan__missing_furls' )
				);
			}
		}

		/* Sort so that critical errors are first */
		$resultList = $scanResults['list'];
		uasort( $resultList, function( $a, $b )
		{
			if( $a['critical'] and $b['critical'] )
			{
				return 0;
			}
			elseif( $a['critical'] and !$b['critical'] )
			{
				return -1;
			}
			else
			{
				return 1;
			}
		});

		Output::i()->json( array(
			'html' => Theme::i()->getTemplate( 'support' )->supportBlockList( $resultList ),
			'criticalIssues' => $scanResults['failures'],
			'recommendedIssues' => $scanResults['advice']
		) );
	}

	/**
	 * Check for missing admin language strings
	 *
	 * @param array $languageStrings
	 * @return array
	 */
	protected function _scanAdminStrings( array $languageStrings ) : array
	{
		$missingAdminStrings = [ 'menu' => [], 'restrictions' => [], 'keywords' => [], 'settings' => [], 'notifications' => [] ];

		/* Start with the acp menu */
		$menu = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/acpmenu.json" );
		foreach( $menu as $module => $controllers )
		{
			$string = 'menu__' . $this->application->directory . '_' . $module;
			$hasSubMenuItems = false;

			foreach( $controllers as $controller => $menuData )
			{
				if( !array_key_exists( 'menutab__' . $menuData['tab'], $languageStrings ) )
				{
					$missingAdminStrings['menu'][] = 'menutab__' . $menuData['tab'];
				}
				if( !array_key_exists( 'menutab__' . $menuData['tab'] . '_icon', $languageStrings ) )
				{
					$missingAdminStrings['menu'][] = 'menutab__' . $menuData['tab'] . '_icon';
				}

				if( isset( $menuData['activemenuitem'] ) and $menuData['activemenuitem'] )
				{
					if( !array_key_exists( 'menu__' . $menuData['activemenuitem'], $languageStrings ) )
					{
						$missingAdminStrings['menu'][] = 'menu__' . $menuData['activemenuitem'];
					}
				}
				else
				{
					$hasSubMenuItems = true;
					if( !array_key_exists( $string . '_' . $controller, $languageStrings ) )
					{
						$missingAdminStrings['menu'][] = $string . '_' . $controller;
					}
				}
			}

			if( $hasSubMenuItems and !array_key_exists( $string, $languageStrings ) )
			{
				$missingAdminStrings['menu'][] = $string;
			}
		}

		/* Check ACP restrictions */
		$restrictions = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/acprestrictions.json" );
		foreach( $restrictions as $module => $groups )
		{
			foreach( $groups as $group => $keys )
			{
				if( !array_key_exists( 'r__' . $group, $languageStrings ) )
				{
					$missingAdminStrings['restrictions'][] = 'r__' . $group;
				}

				foreach( $keys as $k => $v )
				{
					if( !array_key_exists( 'r__' . $k, $languageStrings ) )
					{
						$missingAdminStrings['restrictions'][] = 'r__' . $k;
					}
				}
			}
		}

		/* Check search keywords */
		$keywords = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/acpsearch.json" );
		foreach( $keywords as $keyword )
		{
			if( !array_key_exists( $keyword['lang_key'], $languageStrings ) )
			{
				$missingAdminStrings['keywords'][] = $keyword['lang_key'];
			}
		}

		/* Check Settings */
		$settings = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/settings.json" );
		foreach( $settings as $setting )
		{
			if( !array_key_exists( $setting['key'], $languageStrings ) )
			{
				$missingAdminStrings['settings'][] = $setting['key'];
			}
		}

		/* I really hate notifications */
		foreach( $this->application->extensions( 'core', 'Notifications', false ) as $extensionKey => $ext )
		{
			/* @var NotificationsAbstract $ext */
			$stringsToCheck = [];
			foreach( $ext::configurationOptions() as $key => $config )
			{
				if( isset( $config['adminDescription'] ) )
				{
					$stringsToCheck[] = $config['adminDescription'];
				}
				if( isset( $config['admin_lang'] ) )
				{
					$stringsToCheck = array_merge( $stringsToCheck, array_values( $config['admin_lang'] ) );
				}

				foreach( $stringsToCheck as $string )
				{
					if( !array_key_exists( $string, $languageStrings ) )
					{
						$missingAdminStrings['notifications'][] = $string;
					}
				}
			}
		}

		/* Check Email Templates */
		if( file_exists( ROOT_PATH . "/applications/" . $this->application->directory . "/dev/email" ) )
		{
			foreach( new DirectoryIterator( ROOT_PATH . "/applications/" . $this->application->directory . "/dev/email" ) as $file )
			{
				if ( $file->isDir() or $file->isDot() )
				{
					continue;
				}

				$dot = strrpos( $file->getFilename(), '.' );
				$extension = strtolower( substr( $file->getFilename(), $dot + 1 ) );
				if ( $extension == 'txt' )
				{
					$emailKey = substr( $file->getFilename(), 0, $dot );
					if( !array_key_exists( 'emailtpl_' . $emailKey, $languageStrings ) )
					{
						$missingAdminStrings['emails'][] = 'emailtpl_' . $emailKey;
					}
				}
			}
		}

		/* FileStorage extensions */
		foreach( $this->application->extensions( 'core', 'FileStorage', false ) as $extensionKey => $ext )
		{
			$stringToCheck = 'filestorage__' . $this->application->directory . '_' . $extensionKey;
			if( !array_key_exists( $stringToCheck, $languageStrings ) )
			{
				$missingAdminStrings['filestorage'][] = $stringToCheck;
			}
		}

		/* re-scan in case another app has the string */
		return $this->_rescanStrings( $missingAdminStrings );
	}

	/**
	 * Scan for missing front-end strings
	 *
	 * @param array $languageStrings
	 * @return array
	 */
	protected function _scanFrontStrings( array $languageStrings ) : array
	{
		$missingFrontStrings = [ 'modules' => [], 'content' => [], 'contentform' => [],'widgets' => [], 'notifications' => [] ];

		/* Check modules */
		foreach( $this->application->modules( 'front' ) as $module )
		{
			$string = 'module__' . $this->application->directory . '_' . $module->key;
			if( $module->key != 'ajax' and !array_key_exists( $string, $languageStrings ) )
			{
				$missingFrontStrings['modules'][] = $string;
			}
		}

		/* Check items/comments/reviews */
		foreach( $this->application->extensions( 'core', 'ContentRouter' ) as $ext )
		{
			/* @var Item $itemClass */
			foreach( $ext->classes as $itemClass )
			{
				$classes = [ $itemClass ];
				if( isset( $itemClass::$commentClass ) )
				{
					$classes[] = $itemClass::$commentClass;
				}
				if( isset( $itemClass::$reviewClass ) )
				{
					$classes[] = $itemClass::$reviewClass;
				}

				foreach( $classes as $class )
				{
					$stringsToCheck = array( $class::$title, '__indefart_' . $class::$title, '__defart_' . $class::$title, $class::$title . '_pl', $class::$title . '_pl_lc' );

					foreach( $stringsToCheck as $string )
					{
						if( !array_key_exists( $string, $languageStrings ) )
						{
							/* We might have a _plural instead of a _pl */
							if( $string == '__defart_' . $class::$title . '_pl' and array_key_exists( $string . 'ural', $languageStrings ) )
							{
								continue;
							}

							$missingFrontStrings['content'][] = $string;
						}
					}
				}

				if( isset( $itemClass::$containerNodeClass ) )
				{
					$class = $itemClass::$containerNodeClass;
					$stringsToCheck = array( $class::$nodeTitle, $class::$nodeTitle . '_sg', $class::$nodeTitle . '_sg_lc' );
					foreach( $stringsToCheck as $string )
					{
						if( !array_key_exists( $string, $languageStrings ) )
						{
							$missingFrontStrings['content'][] = $string;
						}
					}
				}

				/* Check form elements */
				foreach( $itemClass::formElements( null, null ) as $element )
				{
					/* Hard-coding a list of strings to skip, mainly because we
					have some nodes that are also items and this causes a whole bunch of issues here */
					if( in_array( $element->name, [ 'container'] ) )
					{
						continue;
					}

					/* @var Form\FormAbstract $element */
					if( !array_key_exists( $element->name, $languageStrings ) )
					{
						$missingFrontStrings['contentform'][] = $element->name;
					}
				}
			}
		}

		/* Check Widgets */
		$widgets = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/widgets.json" );
		foreach( $widgets as $k => $v )
		{
			if( !array_key_exists( 'block_' . $k, $languageStrings ) )
			{
				$missingFrontStrings['widgets'][] = 'block_' . $k;
			}
		}

		/* Friggin notifications */
		foreach( $this->application->extensions( 'core', 'Notifications', false ) as $extensionKey => $ext )
		{
			/* @var NotificationsAbstract $ext */
			$stringsToCheck = [ 'notifications__' . $this->application->directory . '_' . $extensionKey ];
			foreach( $ext::configurationOptions() as $key => $config )
			{
				if( isset( $config['title'] ) )
				{
					$stringsToCheck[] = $config['title'];
				}
				if( isset( $config['description'] ) )
				{
					$stringsToCheck[] = $config['description'];
				}
				if( isset( $config['notificationTypes'] ) and count( $config['notificationTypes'] ) )
				{
					foreach( $config['notificationTypes'] as $type )
					{
						if( file_exists( ROOT_PATH . "/applications/" . $this->application->directory . "dev/email/notification_" . $type . ".phtml" ) )
						{
							$stringsToCheck[] = 'mailsub__' . $this->application->directory . '_notification_' . $type;
						}
					}
				}

				foreach( $stringsToCheck as $string )
				{
					if( !array_key_exists( $string, $languageStrings ) )
					{
						$missingFrontStrings['notifications'][] = $string;
					}
				}
			}
		}

		/* Admin notification emails */
		foreach( $this->application->extensions( 'core', 'AdminNotifications', false ) as $extensionKey => $ext )
		{
			$string = 'mailsub__' . $this->application->directory . '_acp_notification_' . $extensionKey;
			if( !array_key_exists( $string, $languageStrings ) )
			{
				$missingFrontStrings['notifications'][] = $string;
			}
		}

		/* Check Email Templates */
		if( file_exists( ROOT_PATH . "/applications/" . $this->application->directory . "/dev/email" ) )
		{
			foreach( new DirectoryIterator( ROOT_PATH . "/applications/" . $this->application->directory . "/dev/email" ) as $file )
			{
				if ( $file->isDir() or $file->isDot() )
				{
					continue;
				}

				$dot = strrpos( $file->getFilename(), '.' );
				$extension = strtolower( substr( $file->getFilename(), $dot + 1 ) );
				if ( $extension == 'txt' )
				{
					$emailKey = substr( $file->getFilename(), 0, $dot );
					if( !array_key_exists( 'mailsub__' . $this->application->directory . '_' . $emailKey, $languageStrings ) )
					{
						$missingFrontStrings['notifications'][] = 'mailsub__' . $this->application->directory . '_' . $emailKey;
					}
				}
			}
		}

		/* re-scan in case another app has the string */
		return $this->_rescanStrings( $missingFrontStrings );
	}

	/**
	 * Check missing strings against other apps
	 *
	 * @param array $missingStrings
	 * @return array
	 */
	protected function _rescanStrings( array $missingStrings ) : array
	{
		foreach( $missingStrings as $group => $missing )
		{
			$missing = array_unique( $missing );
			if( count( $missing ) )
			{
				foreach( $missing as $index => $string )
				{
					if( Member::loggedIn()->language()->checkKeyExists( $string ) )
					{
						unset( $missing[ $index ] );
					}
				}
			}

			/* Do we still have anything missing? */
			if( count( $missing ) )
			{
				$missingStrings[ $group ] = $missing;
			}
			else
			{
				unset( $missingStrings[ $group ] );
			}
		}

		return $missingStrings;
	}

	/**
	 * Use the menu file to check for the presence of search keywords
	 *
	 * @param array 	$languageStrings
	 * @return array
	 */
	protected function _scanSearchKeywords( array $languageStrings=array() ) : array
	{
		$missingKeywords = [ 'menu' => [], 'settings' => [] ];

		/* Load the current search keyword strings */
		$keywords = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/acpsearch.json" );
		$existingKeywords = [];
		foreach( $keywords as $k => $v )
		{
			$existingKeywords[] = $v['lang_key'];
		}

		/* First check the menu items */
		$menu = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/acpmenu.json" );
		foreach( $menu as $module => $controllers )
		{
			$hasSubMenuItems = false;
			foreach( $controllers as $controller => $menuData )
			{
				/* Skip the dev center */
				if( $menuData['tab'] == 'developer' )
				{
					continue;
				}

				$string = 'menu__' . ( $menuData['activemenuitem'] ?? $this->application->directory . '_' . $module . '_' . $controller );
				if( !in_array( $string, $existingKeywords ) )
				{
					/* Maybe we are referencing the URI somewhere with a different key */
					$uri = "app=" . $this->application->directory . "&module=" . ( $menuData['module_url'] ?? $module ) . "&controller=" . $controller . ( ( isset( $menuData['do'] ) and $menuData['do'] ) ? "&do=" . $menuData['do'] : '' );
					if( !array_key_exists( $uri, $keywords ) )
					{
						$missingKeywords['menu'][] = $string;
					}
				}
			}
		}

		/* Now check settings */
		$settings = $this->_getJson( ROOT_PATH . "/applications/" . $this->application->directory . "/data/settings.json" );
		foreach( $settings as $setting )
		{
			/* Skip settings that don't have a language string, because those are likely hidden */
			if( array_key_exists( $setting['key'], $languageStrings ) and !in_array( $setting['key'], $existingKeywords ) )
			{
				/* Skip sitemap settings */
				if( str_starts_with( $setting['key'], 'sitemap' ) )
				{
					continue;
				}

				/* Check if the setting is somewhere in the list */
				$found = false;
				foreach( array_keys( $keywords ) as $uri )
				{
					if( strpos( $uri, "searchResult={$setting['key']}" ) !== false )
					{
						$found = true;
						break;
					}
				}

				if( !$found )
				{
					//$missingKeywords['settings'][] = $setting['key'];
				}
			}
		}

		/* Clear empty sections */
		foreach( $missingKeywords as $group => $missing )
		{
			if( !count( $missing ) )
			{
				unset( $missingKeywords[ $group ] );
			}
		}

		return $missingKeywords;
	}

	/**
	 * Scan the ACP Search keywords and check for duplicates
	 *
	 * @return array
	 */
	protected function _scanSearchKeywordsForDupes() : array
	{
		$usedLangs = [];
		$dupes = [];
		$usedParsedLangs = [];
		$dupeParsedLangs = [];
		$acpSearch = $this->_getJson( ROOT_PATH . '/applications/' . $this->application->directory . '/data/acpsearch.json' );
		foreach( $acpSearch as $url => $data )
		{
			if( array_key_exists( $data['lang_key'], $usedLangs ) )
			{
				if( !array_key_exists( $data['lang_key'], $dupes ) )
				{
					$dupes[ $data['lang_key'] ] = [ $usedLangs[ $data['lang_key'] ] ];
				}
				$dupes[ $data['lang_key'] ][] = $url;
			}
			else
			{
				$usedLangs[ $data['lang_key'] ] = $url;
			}

			if( Member::loggedIn()->language()->checkKeyExists( $data['lang_key'] ) )
			{
				$parsedLangKey = Member::loggedIn()->language()->get( $data['lang_key'] );
			}
			else
			{
				$parsedLangKey = $data['lang_key'];
			}

			if( array_key_exists( $parsedLangKey, $usedParsedLangs ) )
			{
				if( !array_key_exists( $parsedLangKey, $dupeParsedLangs ) )
				{
					$dupeParsedLangs[ $parsedLangKey ] = [
						$usedParsedLangs[ $parsedLangKey ]['key'] => [ $usedParsedLangs[ $parsedLangKey ]['url'] ]
					];
				}

				if( !array_key_exists( $data['lang_key'], $dupeParsedLangs[ $parsedLangKey ] ) )
				{
					$dupeParsedLangs[ $parsedLangKey ][ $data['lang_key'] ] = [];
				}

				$dupeParsedLangs[ $parsedLangKey ][ $data['lang_key'] ][] = $url;
			}
			else
			{
				$usedParsedLangs[ $parsedLangKey ] = [
					'key' => $data['lang_key'],
					'url' => $url
				];
			}
		}

		$return = array_keys( $dupes );
		foreach( $dupeParsedLangs as $parsedLangKey => $data )
		{
			foreach( $data as $langKey => $urls )
			{
				if( !in_array( $langKey, $return ) )
				{
					$return[] = $langKey;
				}
			}
		}
		return $return;
	}

	/**
	 * Compares the email templates against the templates
	 * in the dev/email folder
	 *
	 * @return array
	 */
	protected function _checkEmailTemplates() : array
	{
		/* First build a list of email templates that we have */
		$templates = [];
		$emailFolder = ROOT_PATH . "/applications/" . $this->application->directory . "/dev/email";

		/* If we don't have an email folder, then we are missing all of them */
		if( file_exists( $emailFolder ) )
		{
			foreach( new DirectoryIterator( $emailFolder ) as $file )
			{
				if( $file->isDir() or $file->isDot() )
				{
					continue;
				}

				$dot = strrpos( $file->getFilename(), '.' );
				$extension = strtolower( substr( $file->getFilename(), $dot + 1 ) );
				if ( $extension == 'txt' )
				{
					$templates[] = substr( $file->getFilename(), 0, $dot );
				}
			}
		}

		/* Check the notifications */
		$missingTemplates = [];
		foreach( $this->application->extensions( 'core', 'Notifications', false ) as $extensionKey => $ext )
		{
			/* @var NotificationsAbstract $ext */
			foreach( $ext::configurationOptions() as $key => $config )
			{
				/* Don't bother with notifications that don't support email */
				if( isset( $config['disabled'] ) and in_array( 'email', $config['disabled'] ) )
				{
					continue;
				}

				if( isset( $config['notificationTypes'] ) and count( $config['notificationTypes'] ) )
				{
					foreach( $config['notificationTypes'] as $type )
					{
						if( !in_array( 'notification_' . $type, $templates ) )
						{
							$missingTemplates[] = 'notification_' . $type;
						}
					}
				}
			}
		}

		return $missingTemplates;
	}
}