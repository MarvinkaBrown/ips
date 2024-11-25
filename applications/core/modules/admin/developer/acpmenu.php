<?php
/**
 * @brief		acpmenu
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use DirectoryIterator;
use http\Exception\InvalidArgumentException;
use IPS\Application\Module;
use IPS\Developer\Controller as DeveloperController;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;
use const IPS\REBUILD_QUICK;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * acpmenu
 */
class acpmenu extends DeveloperController
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
		$modules = $this->_getModules();
		$url = Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$this->application->directory}" );
		$menu = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json" );
		$appKey = $this->application->directory;

		/* Display the table */
		$tree = new Tree( $url, 'dev_acpmenu',
			/* Get Roots */
			function () use ( $url, $appKey, $menu )
			{
				$rows	= array();
				$order	= 1;
				foreach ( array_keys($menu) as $k )
				{
					$lang = "menu__{$appKey}_{$k}";
					$rows[ $k ] = Theme::i()->getTemplate( 'trees' )->row( $url, $k, Member::loggedIn()->language()->addToStack( $lang ), isset( $menu[ $k ] ), array(
						'add'	=> array(
							'icon'	=> 'plus-circle',
							'title'	=> 'acpmenu_add',
							'link'	=> Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$appKey}&do=menuForm&module_key={$k}" )
						)
					), $lang, NULL, $order );
					$order++;
				}
				return $rows;
			},
			/* Get Row */
			function ( $k, $root ) use ( $url, $menu, $appKey )
			{
				$lang = "menu__{$appKey}_{$k}";
				return Theme::i()->getTemplate( 'trees' )->row( $url, $k, Member::loggedIn()->language()->addToStack( $lang ), isset( $menu[ $k ] ), array(
					'add'	=> array(
						'icon'	=> 'plus-circle',
						'title'	=> 'acpmenu_add',
						'link'	=> Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$appKey}&do=menuForm&module_key={$k}" )
					)
				), $lang, NULL, NULL, $root );
			},
			/* Get Row Parent */
			function ()
			{
				return NULL;
			},
			/* Get Children */
			function ( $k ) use ( $url, $menu, $appKey )
			{
				$rows = array();
				$pos = 0;
				foreach ( $menu[ $k ] as $id => $row )
				{
					$description = "app={$appKey}&amp;module=" . ( $row['module_url'] ?? $k ) . "&amp;controller=" . $row['controller'] . ( $row['do'] ? "&amp;do=" . $row['do'] : '' );

					$lang = "menu__" . ( $row['activemenuitem'] ?? "{$appKey}_{$k}_{$id}" );
					$rows[ 's@' . $id ] = Theme::i()->getTemplate( 'trees' )->row( $url, 's@' . $id, Member::loggedIn()->language()->addToStack( $lang ), FALSE, array(
						'edit'	=> array(
							'icon'	=> 'pencil',
							'title'	=> 'edit',
							'link'	=> Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$appKey}&do=menuForm&module_key={$k}&id={$id}" ),
							'hotkey'=> 'e'
						),
						'delete'	=> array(
							'icon'	=> 'times-circle',
							'title'	=> 'delete',
							'link'	=> Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$appKey}&do=menuDelete&module_key={$k}&id={$id}" ),
							'data'	=> array( 'delete' => '' )
						),
					), $description, NULL, ++$pos, FALSE, NULL, NULL, NULL, FALSE, FALSE, FALSE );
				}
				return $rows;
			},
			function() use ( $appKey ){
				return [
					'add' => [
						'icon' => 'plus',
						'title' => 'acpmenu_group_add',
						'link' => Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$appKey}&do=menuGroupForm" ),
						'data' => [ 'ipsDialog' => '', 'ipsDialog-size' => 'narrow', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'acpmenu_group_add' ) ]
					]
				];
			},
			FALSE,
			FALSE,
			TRUE
		);

		Output::i()->output = (string) $tree;
	}

	/**
	 * New menu group
	 * @return void
	 */
	protected function menuGroupForm() : void
	{
		$menu = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json" );

		$form = new Form;
		$form->add( new Text( 'acpmenu_group_key', null, true, array(), function( $val ) use ( $menu ){
			if( isset( $menu[ $val ] ) )
			{
				throw new InvalidArgumentException( 'err_duplicate_acp_menu_group' );
			}
		} ) );

		if( $values = $form->values() )
		{
			$menu[ $values['acpmenu_group_key'] ] = [];
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json", $menu );

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * Menu Form Item
	 *
	 * @return	void
	 */
	protected function menuForm() : void
	{
		/* Current Menu */
		$menu = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json" );

		/* Get module and controllers */
		$menuGroup = Request::i()->module_key;

		/* Load existing */
		$current = NULL;
		if ( Request::i()->id and isset( $menu[ $menuGroup ][ Request::i()->id ] ) )
		{
			$current = $menu[ $menuGroup ][ Request::i()->id ];
			if( isset( $current['module_url'] ) and $current['module_url'] != Request::i()->module_key )
			{
				Request::i()->module_key = $current['module_url'];
			}
		}

		$module = $this->_loadModule( true );

		$adminModules = [ '' => '' ];
		foreach( $this->_getModules()[ 'admin' ] as $k => $v )
		{
			$adminModules[ $k ] = $k;
		}

		/* And controllers */
		$controllers = array();
		if( $module !== null )
		{
			foreach ( new DirectoryIterator( ROOT_PATH . "/applications/{$this->application->directory}/modules/admin/{$module->key}" ) as $file )
			{
				if ( !$file->isDot() and mb_substr( $file, -4 ) === '.php' )
				{
					$controllers[ mb_substr( $file, 0, -4 ) ] = (string) $file;
				}
			}
		}
		else
		{
			foreach( new DirectoryIterator( ROOT_PATH . "/applications/{$this->application->directory}/modules/admin" ) as $moduleFolder )
			{
				if( $moduleFolder->isDir() and !$moduleFolder->isDot() )
				{
					foreach( new DirectoryIterator( $moduleFolder->getRealPath() ) as $file )
					{
						if ( !$file->isDot() and mb_substr( $file, -4 ) === '.php' )
						{
							$controllers[ $moduleFolder->getFilename() ][ mb_substr( $file, 0, -4 ) ] = (string) $file;
						}
					}
				}
			}
		}

		/* And restrictions */
		$restrictions = $this->_getRestrictions( $module );

		/* And tabs */
		$tabs = $this->_getAcpMenuTabs();

		/* Show Form */
		$form = new Form();
		$form->addTab( 'acpmenu_tab_general' );
		$form->add( new Select( 'acpmenu_controller', ( $current ? $current['controller'] : NULL ), TRUE, array( 'options' => $controllers ) ) );
		$form->add( new Text( 'acpmenu_tab', ( $current ? $current['tab'] : $module?->application ), TRUE, array( 'autocomplete' => array(
			'source' 	=> 	$tabs,
			'maxItems'	=> 1,
		) ) ) );
		$form->add( new Select( 'acpmenu_module_url', ( $current and isset( $current['module_url'] ) ) ? $current['module_url'] : null, false, [ 'options' => $adminModules ] ) );
		$form->add( new Text( 'acpmenu_doaction', ( $current ? $current['do'] : NULL ), FALSE, array(), NULL, 'do=' ) );
		$form->add( new Select( 'acpmenu_restriction', ( $current ? explode( ',', $current['restriction'] ) : '' ), FALSE, array( 'options' => $restrictions, 'multiple' => true, 'noDefault' => true ) ) );
		$form->add( new Select( 'acpmenu_subcontrollers', ( $current and isset( $current['subcontrollers'] ) ) ? explode( ',', $current['subcontrollers'] ) : null, false, array( 'options' => $controllers, 'multiple' => true, 'noDefault' => true  ) ) );

		$form->addTab( 'acpmenu_tab_advanced' );
		$form->add( new Text( 'acpmenu_activemenuitem', ( $current and isset( $current['activemenuitem'] ) ) ? $current['activemenuitem'] : null, false ) );
		$form->add( new Text( 'acpmenu_badge', ( $current and isset( $current['badge'] ) ) ? $current['badge'] : null, false ) );
		$form->add( new Codemirror( 'acpmenu_callback', ( $current and isset( $current['callback'] ) ) ? $current['callback'] : null, false, [ 'codeModeAllowedLanguages' => [ 'php' ] ] ) );

		$menuChecks = [];
		if( $current and isset( $current['menu_checks'] ) )
		{
			foreach( $current['menu_checks'] as $param => $paramValues )
			{
				foreach( $paramValues as $pv )
				{
					$menuChecks[] = $param . '=' . $pv;
				}
			}
		}
		$form->add( new Stack( 'acpmenu_menu_checks', $menuChecks, false ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$menuItemKey = $current ? Request::i()->id : $values['acpmenu_controller'];
			if( $current === null and isset( $menu[ $menuGroup ][ $menuItemKey ] ) and $values['acpmenu_doaction'] )
			{
				$menuItemKey .=  $values['acpmenu_doaction'];
			}

			$menuItem = array_merge( ( $current ?? [] ), [
				'tab'			=> $values['acpmenu_tab'],
				'controller'	=> $values['acpmenu_controller'],
				'do'			=> $values['acpmenu_doaction'],
				'restriction'	=> count( $values['acpmenu_restriction'] ) ? implode( ',', $values['acpmenu_restriction'] ) : ''
			] );

			if( $menuItem['restriction'] and $restrictionModule = $this->_checkRestrictionModule( $menuItem['restriction'] ) )
			{
				if( $module === null or $restrictionModule != $module->key )
				{
					$menuItem['restriction_module'] = $restrictionModule;
				}
			}

			if( $values['acpmenu_subcontrollers'] )
			{
				$menuItem['subcontrollers'] = implode( ",", $values['acpmenu_subcontrollers'] );
			}

			foreach( [ 'module_url', 'callback', 'activemenuitem', 'badge' ] as $key )
			{
				if( $values[ 'acpmenu_' . $key ] )
				{
					$menuItem[ $key ] = $values['acpmenu_' . $key ];
				}
			}

			if( !empty( $values['acpmenu_menu_checks'] ) )
			{
				$menuChecks = [];
				foreach( $values['acpmenu_menu_checks'] as $menuCheck )
				{
					$_menuCheck = explode( "=", $menuCheck );
					if( !isset( $menuChecks[ $_menuCheck[0] ] ) )
					{
						$menuChecks[ $_menuCheck[0] ] = [];
					}
					$menuChecks[ $_menuCheck[0] ][] = $_menuCheck[1];
				}
				$menuItem['menu_checks'] = $menuChecks;
			}

			$menu[ $menuGroup ][ $menuItemKey ] = $menuItem;

			/* If we edited the item key, remove the old one */
			if( $current !== null and Request::i()->id != $menuItemKey )
			{
				unset( $menu[ $menuGroup ][ Request::i()->id ] );
			}

			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json", $menu );

			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$this->application->directory}&root={$menuGroup}" ) );
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'acpmenu_add', $form, FALSE );
	}

	/**
	 * Delete Menu Item
	 *
	 * @return	void
	 */
	protected function menuDelete() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		/* Get Menu */
		$menu = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json" );

		/* Get module and controllers */
		$menuGroup = Request::i()->module_key;

		/* Delete It */
		unset( $menu[ $menuGroup ][ Request::i()->id ] );
		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json", $menu );

		/* Redirect */
		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=acpmenu&appKey={$this->application->directory}&root={$menuGroup}" ) );
	}

	/**
	 * @return void
	 */
	protected function reorder() : void
	{
		Session::i()->csrfCheck();

		$menu = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json" );

		/* Normalise AJAX vs non-AJAX */
		if( isset( Request::i()->ajax_order ) )
		{
			$order = array();
			$position = array();
			foreach( Request::i()->ajax_order as $id => $parent )
			{
				/* We have to fudge children "ids" to prevent conflicts in the array */
				$id = str_replace( 's@', '', $id );

				if ( !isset( $order[ $parent ] ) )
				{
					$order[ $parent ] = array();
					$position[ $parent ] = 1;
				}
				$order[ $parent ][ $id ] = $position[ $parent ]++;
			}
		}
		/* Non-AJAX way */
		else
		{
			$order = array( Request::i()->root ?: 'null' => Request::i()->order );
		}

		/* Sort */
		$_menu = $menu;
		$menu = array();

		if( isset( $order['null'] ) )
		{
			foreach( $order['null'] as $key => $position )
			{
				foreach ( $_menu as $_parent => $_items )
				{
					if ( $key == $_parent )
					{
						$menu[ $_parent ] = $_menu[ $_parent ];
						break;
					}
				}
			}
		}

		foreach( $_menu as $root => $items )
		{
			/* If we were sorting one level of the menu, and this is not it, then leave it as is */
			if( isset( Request::i()->root ) and Request::i()->root != $root )
			{
				$menu[ $root ] = $items;
			}
			elseif( isset( $order[ $root ] ) )
			{
				$menu[ $root ] = array();
				foreach( $order[ $root ] as $key => $position )
				{
					$menu[ $root ][ $key ] = $_menu[ $root ][ $key ];
				}
			}
		}

		/* Write */
		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json", $menu );
	}

	/**
	 * Find the restriction module for the specified restriction key(s)
	 *
	 * @param string $restrictions
	 * @return string
	 */
	protected function _checkRestrictionModule( string $restrictions ) : string
	{
		$allRestrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );
		foreach( explode( ",", $restrictions ) as $_restriction )
		{
			foreach( $allRestrictions as $moduleKey => $groups )
			{
				foreach( $groups as $groupKey => $rows )
				{
					if( array_key_exists( $_restriction, $rows ) )
					{
						return $moduleKey;
					}
				}
			}
		}

		return '';
	}
}