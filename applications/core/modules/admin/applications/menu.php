<?php
/**
 * @brief		Menu Manager
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Jun 2015
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\FrontNavigation;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Icon;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use UnderflowException;
use function defined;
use function explode;
use function in_array;
use function intval;
use function json_decode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * menu
 */
class menu extends Controller
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
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'menu_manage' );
		parent::execute();
	}

	/**
	 * Manage Menu
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tree = new Tree(
			Url::internal( "app=core&module=applications&controller=menu" ),
			'menu__core_applications_menu',
			array( $this, '_getMenuRoots' ),
			array( $this, '_getMenuRow' ),
			function( $id ){
				return null;
			},
			array( $this, '_getMenuChildren' ),
			function(){
				return [
					'add' => [
						'icon'	=> 'plus',
						'title'	=> 'add',
						'link'	=> Url::internal( "app=core&module=applications&controller=menu&do=form" ),
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
					],
					'restore' => [
						'icon'	=> 'refresh',
						'link'	=> Url::internal('app=core&module=applications&controller=menu&do=restore')->csrf(),
						'title'	=> 'menu_manager_revert',
						'data' => [ 'confirm' => '' ]
					]
				];
			},
			false
		);

		Output::i()->headerMessage = Theme::i()->getTemplate( 'applications' )->menuManagerHeader();
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_system.js', 'core', 'admin' ) );
		Output::i()->globalControllers[] = 'core.admin.system.menuManager';
		Output::i()->jsVars['menuChanged'] = ( isset( Store::i()->frontNavigation ) and Store::i()->frontNavigation != FrontNavigation::frontNavigation( true ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_applications_menu');
		Output::i()->output = (string) $tree;
	}

	/**
	 * Return root menu items
	 *
	 * @return array
	 */
	public function _getMenuRoots() : array
	{
		$roots = [];
		foreach ( Db::i()->select( '*', 'core_menu', [ 'parent is null or parent=?', 0 ], 'position' ) as $item )
		{
			if( Application::appIsEnabled( $item['app'] ) )
			{
				$roots[ $item['id'] ] = $this->_getMenuRow( $item );
			}
		}
		return $roots;
	}

	/**
	 * Load child menu items
	 *
	 * @param int $parentId
	 * @return array
	 */
	public function _getMenuChildren( int $parentId ) : array
	{
		$children = [];
		foreach ( Db::i()->select( '*', 'core_menu', [ 'parent=?', $parentId ], 'position' ) as $item )
		{
			if ( Application::appIsEnabled( $item['app'] ) )
			{
				if( $child = $this->_getMenuRow( $item ) )
				{
					$children[ $item['id'] ] = $child;
				}
			}
		}
		return $children;
	}

	/**
	 * Load the extension from the database row
	 *
	 * @param array $item
	 * @return FrontNavigationAbstract|null
	 */
	protected function _getClassFromRow( array $item ) : ?FrontNavigationAbstract
	{
		if ( $item['app'] == 'core' and $item['extension'] == 'Menu' )
		{
			$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];

			if ( class_exists( $class ) )
			{
				return new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'], $item['menu_types'], json_decode( (string) $item['icon'], TRUE ) );
			}
		}

		$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
		if ( class_exists( $class ) )
		{
			return new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'], $item['menu_types'], json_decode( (string) $item['icon'], TRUE ) );
		}

		return null;
	}

	/**
	 * Format menu item for the tree
	 *
	 * @param int|array $item
	 * @return string
	 */
	public function _getMenuRow( int|array $item ) : string
	{
		if( is_numeric( $item ) )
		{
			try
			{
				$item = Db::i()->select( '*', 'core_menu', [ 'id=?', $item ] )->first();
			}
			catch( UnderflowException )
			{
				return '';
			}
		}

		$menuItem = $this->_getClassFromRow( $item );
		if( $menuItem === null or !Application::appIsEnabled( $item['app'] ) )
		{
			return '';
		}

		$buttons = [];
		if( $item['app'] == 'core' and $item['extension'] == 'Menu' )
		{
			$buttons['add'] = [
				'title' => 'add',
				'link' => Url::internal( "app=core&module=applications&controller=menu&do=form&parent=" . $item['id'] ),
				'icon' => 'plus-circle',
				'data' => [ 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'add' ) ]
			];
		}

		$buttons['edit'] = [
			'title' => 'edit',
			'link' => Url::internal( "app=core&module=applications&controller=menu&do=form&id={$item['id']}" ),
			'icon' => 'pencil',
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
		];
		$buttons['delete'] = [
			'title' => 'delete',
			'link' => Url::internal( "app=core&module=applications&controller=menu&do=remove&id={$item['id']}" )->csrf(),
			'icon' => 'times-circle',
			'data' => [ 'confirm' => '' ]
		];

		$icon = null;
		if( $iconData = $menuItem->icon )
		{
			if( $iconData[0]['type'] == 'emoji' )
			{
				$icon = $iconData[0]['html'];
			}
			else
			{
				$bits = explode( ":", $iconData[0]['key'] );
				$icon = 'fa-';
				switch( $bits[1] )
				{
					case 'fas':
						$icon .= 'solid';
						break;
					case 'far':
						$icon .= 'regular';
						break;
					case 'fab':
						$icon .= 'brands';
						break;
				}

				$icon .= ' fa-' . $bits[0];
			}
		}

		return Theme::i()->getTemplate( 'trees', 'core' )->row(
			Url::internal( "app=core&module=applications&controller=menu" ),
			$item['id'],
			$menuItem->title(),
			(bool) Db::i()->select( 'count(id)', 'core_menu', [ 'parent=?', $item['id'] ] )->first(),
			$buttons,
			'', // description
			$icon,
			$item['position'],
			false,
			null, // toggleStatus
			false,
			null, // badge
			false, // titleHtml
			false, // descriptionHtml
			( $item['app'] == 'core' and $item['extension'] == 'Menu' ) // acceptsChildren
		);
	}
	
	/**
	 * Publish Menu
	 *
	 * @return	void
	 */
	protected function publish() : void
	{
		Session::i()->csrfCheck();
		
		if ( Db::i()->select( 'COUNT(*)', 'core_menu' )->first() )
		{
			unset( Store::i()->frontNavigation );
		}
		else
		{
			Store::i()->frontNavigation = array( 0 => array(), 1 => array() );
		}

		/* Rebuild the data store so that we know something was changed */
		FrontNavigation::frontNavigation();

		Session::i()->log( 'acplog__menu_published' );
		Output::i()->redirect( Url::internal('app=core&module=applications&controller=menu'), 'menu_manager_published' );
	}
	
	/**
	 * Add Menu Item
	 *
	 * @return	void
	 */
	protected function form() : void
	{
		/* What menu items have we already configured? */
		$current = array();
		foreach ( Db::i()->select( '*', 'core_menu', NULL, 'position' ) as $item )
		{
			if ( !isset( $current[ $item['app'] ][ $item['extension'] ] ) )
			{
				$current[ $item['app'] ][ $item['extension'] ] = 0;
			}
			$current[ $item['app'] ][ $item['extension'] ]++;
		}
		
		/* Are we editing an existing item? */
		$existing = NULL;
		if ( Request::i()->id )
		{
			try
			{
				$existing = Db::i()->select( '*', 'core_menu', array( 'id=?', Request::i()->id ) )->first();
			}
			catch ( UnderflowException $e ) { }
		}
		
		/* What options are available? */		
		$options = array();
		$toggles = array();
		$fieldNames = array();
		$extraFields = array();
		foreach ( Application::allExtensions( 'core', 'FrontNavigation', FALSE, 'core', NULL, FALSE ) as $key => $class )
		{
			/* Don't allow a million levels of nesting */
			if( $key == 'core_Menu' and isset( Request::i()->parent ) )
			{
				try
				{
					$parent = Db::i()->select( '*', 'core_menu', [ 'id=?', Request::i()->parent ] )->first();
					if( $parent['parent'] )
					{
						continue;
					}
				}
				catch( UnderflowException ){}
			}

			if ( method_exists( $class, 'typeTitle' ) )
			{
				$exploded = explode( '_', $key );
				if ( $class::allowMultiple() or !isset( $current[ $exploded[0] ][ $exploded[1] ] ) or ( $existing and $existing['app'] == $exploded[0] and $existing['extension'] == $exploded[1] ) )
				{
					$options[ $key ] = $class::typeTitle();
					foreach ( $class::configuration( $existing ? json_decode( $existing['config'], TRUE ) : array(), $existing ? $existing['id'] : NULL ) as $field )
					{
						if ( !$field->htmlId )
						{
							$field->htmlId = md5( mt_rand() );
						}

						$toggles[ $key ][ $field->name ] = $field->htmlId;
						$fieldNames[ $key ][] = $field->name;
						$extraFields[] = $field;
					}
					
					if ( $class::permissionsCanInherit() )
					{
						$toggles[ $key ][] = 'menu_manager_access_type';
					}
					else
					{
						$toggles[ $key ][] = 'menu_manager_access';
					}
				}
			}
		}
		
		/* Create the form */
		$form = new Form( 'menu_item', 'save_menu_item' );
		$form->hiddenFields['parent'] = Request::i()->parent ?? null;
		$form->add( new Select( 'menu_manager_extension', $existing ? "{$existing['app']}_{$existing['extension']}" : NULL, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		foreach ( $extraFields as $field )
		{
			$form->add( $field );
		}
		$groups = array();
		foreach ( Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		$form->add( new Radio( 'menu_manager_access_type', ( $existing and $existing['permissions'] !== NULL ) ? 1 : 0, TRUE, array(
			'options'	=> array( 0 => 'menu_manager_access_type_inherit', 1 => 'menu_manager_access_type_override' ),
			'toggles'	=> array( 1 => array( 'menu_manager_access' ) )
		), NULL, NULL, NULL, 'menu_manager_access_type' ) );
		$form->add( new CheckboxSet( 'menu_manager_access', $existing ? ( $existing['permissions'] == '*' ? '*' : explode( ',', (string) $existing['permissions'] ) ) : '*', NULL, array( 'multiple' => TRUE, 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'everyone', 'impliedUnlimited' => TRUE ), NULL, NULL, NULL, 'menu_manager_access' ) );

		$menuValue = '*';
		if ( isset( $existing['menu_types'] ) and $json = json_decode( $existing['menu_types'], true ) )
		{
			if ( is_array( $json ) )
			{
				$menuValue = $json;
			}
		}
		$form->add( new CheckboxSet( 'menu_manager_menutype', $menuValue, null, array( 'multiple' => true,
			'options' => [
				'header' => 'menu_manager_menutype_header',
				'sidebar' => 'menu_manager_menutype_sidebar',
				'smallscreen' => 'menu_manager_menutype_smallscreen'
			], 'unlimited' => '*', 'unlimitedLang' => 'everyone', 'impliedUnlimited' => TRUE ), NULL, NULL, NULL, 'menu_manager_menutype' ) );

		$form->add( new Icon( 'menu_manager_icon', $existing['icon'] ?? null, false, [], null, null, null, 'menu_manager_icon' ) );

		if ( ! $existing )
		{
			$form->hiddenValues['newItem'] = TRUE;
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$exploded = explode( '_', $values['menu_manager_extension'] );

			/* @var FrontNavigationAbstract $class */
			$class = 'IPS\\' . $exploded[0] . '\extensions\core\FrontNavigation\\' . $exploded[1];
			$config = array();
			if ( isset( $fieldNames[ $values['menu_manager_extension'] ] ) )
			{
				foreach ( $values as $k => $v )
				{
					if ( in_array( $k, $fieldNames[ $values['menu_manager_extension'] ] ) )
					{
						$config[ $k ] = $v;
					}
				}
			}
		
			$save = array(
				'app'			=> $exploded[0],
				'extension'		=> $exploded[1],
				'config'		=> json_encode( $config ),
				'parent'		=> $existing ? $existing['parent'] : ( Request::i()->parent ?: NULL ),
				'is_menu_child'	=> FALSE,
				'menu_types'     => is_array( $values['menu_manager_menutype'] ) ? json_encode( $values['menu_manager_menutype'] ) : '*',
				'icon'			 => ( $values['menu_manager_icon'] and is_array( $values['menu_manager_icon'] ) ) ? json_encode( $values['menu_manager_icon'] ) : null
			);

			/* Make this a child, but only if this itself is not a submenu */
			if ( $save['parent'] and !( $save['app'] == 'core' and $save['extension'] == 'Menu' ) )
			{
				try
				{
					$parent = Db::i()->select( '*', 'core_menu', array( 'id=?', $save['parent'] ) )->first();
					if ( ( $parent['app'] === 'core' ) and ( $parent['extension'] === 'Menu' ) )
					{
						$save['is_menu_child'] = TRUE;
					}
				}
				catch ( UnderflowException $e ) { }
			}

			/* First we need to determine if the access type option was even shown */
			$hasAccessType = in_array( 'menu_manager_access_type', $toggles[ $values['menu_manager_extension'] ] );

			/* If we didn't have the access type field, then we should just check the permissions that were specified */
			if ( $values['menu_manager_access_type'] OR !$hasAccessType )
			{
				$save['permissions'] = $values['menu_manager_access'] == '*' ? '*' : implode( ',', $values['menu_manager_access'] );
			}
			else
			{
				$save['permissions'] = $class::permissionsCanInherit() ? NULL : '';
			}

			if ( $existing )
			{
				$id = $existing['id'];
				
				$_config = $class::parseConfiguration( $config, $id );
				
				if ( $_config != $config )
				{
					$save['config'] = json_encode( $_config );
				}
				
				Db::i()->update( 'core_menu', $save, array( 'id=?', $id ) );
			}
			else
			{
				try
				{
					$save['position'] = Db::i()->select( 'MAX(position)', 'core_menu', array( 'parent=?', Request::i()->parent ) )->first() + 1;
				}
				catch ( UnderflowException $e )
				{
					$save['position'] = 1;
				}
				
				$id = Db::i()->insert( 'core_menu', $save );
				
				$_config = $class::parseConfiguration( $config, $id );
			
				if ( $_config != $config )
				{
					Db::i()->update( 'core_menu', array( 'config' => json_encode( $_config ) ), array( 'id=?', $id ) );
				}
			}

			/**
			 * If this entry used to be a drop-down and we change the link type without deleting the child links, we
			 * should convert the child links to sub-menu entries (and vice-versa)
			 */
			Db::i()->update(
				'core_menu',
				array( 'is_menu_child' => ( ( $save['app'] === 'core' ) and ( $save['extension'] === 'Menu' ) ) ),
				array( 'parent=? and (app !=? or extension !=?)', $id, 'core', 'Menu' )
			);

			Output::i()->redirect( Url::internal('app=core&module=applications&controller=menu') );
		}

		Output::i()->output = (string) $form;
	}
	
	/**
	 * Remove an item
	 *
	 * @return	void
	 */
	protected function remove() : void
	{
		Request::i()->confirmedDelete();
		static::_remove( intval( Request::i()->id ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->json('OK');
		}

		Output::i()->redirect( Url::internal( "app=core&module=applications&controller=menu" )->setQueryString( 'root', Request::i()->root ) );
	}
	
	/**
	 * Remove a menu item
	 *
	 * @param	int	$id	ID of item to remove
	 * @return	void
	 */
	protected static function _remove( int $id ) : void
	{
		foreach ( Db::i()->select( 'id', 'core_menu', array( 'parent=?', $id ) ) as $child )
		{
			static::_remove( $child );
		}
		Db::i()->delete( 'core_menu', array( 'id=?', $id ) );

		/* remove the title language strings */
		Lang::deleteCustom( 'core', "menu_item_{$id}" );
	}

	/**
	 * Reorder
	 *
	 * @return	void
	 */
	protected function reorder() : void
	{
		Session::i()->csrfCheck();

		/* Normalise AJAX vs non-AJAX */
		if( isset( Request::i()->ajax_order ) )
		{
			$order = array();
			$position = array();
			foreach( Request::i()->ajax_order as $id => $parent )
			{
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

		/* Okay, now order */
		foreach( $order as $parent => $children )
		{
			foreach( $children as $id => $position )
			{
				Db::i()->update( 'core_menu', [ 'position' => $position, 'parent' => (int) $parent ], [ 'id=?', $id ] );
			}
		}

		/* If this is an AJAX request, just respond */
		if( Request::i()->isAjax() )
		{
			return;
		}
		/* Otherwise, redirect */
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=menu" )->setQueryString( array( 'root' => Request::i()->root ) ) );
		}
	}
	
	/**
	 * Restore Default Menu
	 *
	 * @return	void
	 */
	protected function restore() : void
	{
		Request::i()->confirmedDelete();
		FrontNavigation::buildDefaultFrontNavigation();
		FrontNavigation::frontNavigation();
		Output::i()->redirect( Url::internal('app=core&module=applications&controller=menu'), 'menu_manager_reverted' );
	}
}