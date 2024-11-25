<?php
/**
 * @brief		themeeditor
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		16 Feb 2024
 */

namespace IPS\core\modules\admin\developer;

use IPS\Application;
use IPS\Db;
use IPS\Developer\Controller;
use IPS\Helpers\Badge;
use IPS\Helpers\Form;
use IPS\Helpers\Tree\Tree;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Theme\Editor\Category;
use IPS\Theme\Editor\Setting;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * themeeditor
 */
class themeeditor extends Controller
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
		$tree = new Tree(
			$this->url,
			'dev_themeeditor',
			array( $this, '_getCategories' ),
			array( $this, '_getCategoryRow' ),
			function( $id ){
				return null;
			},
			array( $this, '_getCategoryChildren' ),
			function(){
				return [
					'add' => [
						'icon'	=> 'plus',
						'title'	=> 'add',
						'link'	=> $this->url->setQueryString( 'do', 'categoryForm' ),
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
					]
				];
			},
			true
		);

		Output::i()->output = (string) $tree;
	}

	/**
	 * @return array
	 */
	public function _getCategories() : array
	{
		$return = [];
		foreach( Category::roots( null ) as $category )
		{
			/* @var Category $category */
			if( $category->app and !$category->set_id )
			{
				$return[ $category->id ] = $this->_getCategoryRow( $category );
			}
		}
		return $return;
	}

	/**
	 * @param int|Category $category
	 * @return string
	 */
	public function _getCategoryRow( int|Category $category ) : string
	{
		if( is_numeric( $category ) )
		{
			$category = Category::load( $category );
		}

		$buttons = [];

		if( $category->app == $this->application->directory )
		{
			$buttons['edit'] = [
				'icon' => 'pencil',
				'title' => 'edit',
				'link' => $this->url->setQueryString( array( 'do' => 'categoryForm', 'id' => $category->id ) ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
			];
			$buttons['delete'] = [
				'icon' => 'times-circle',
				'title' => 'delete',
				'link' => $this->url->setQueryString( array( 'do' => 'categoryDelete', 'id' => $category->id ) )->csrf(),
				'data' 	=> ( $category->hasChildren( NULL ) or $category->showDeleteOrMoveForm() ) ? array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('delete') ) : array( 'delete' => '' ),
				'hotkey'=> 'd'
			];
		}

		if( $category->hasChildren( null, null, false ) or !$category->hasContents() )
		{
			$buttons['child'] = [
				'icon' => 'plus',
				'title' => 'themeeditor_cat_add',
				'link' => $this->url->setQueryString( array( 'do' => 'categoryForm', 'parent' => $category->id ) )
			];
		}
		if( $category->hasSettings() or $category->hasColors() or !$category->hasContents() )
		{
			$buttons['add'] = array(
				'icon'	=> 'plus-circle',
				'title'	=> Category::$nodeTitle . '_add_child',
				'link'	=> $this->url->setQueryString( array( 'do' => 'settingForm', 'category' => $category->id ) )
			);
		}

		$badge = null;
		if( $category->app and $category->app != $this->application->directory )
		{
			$badge = [
				0 => 'ipsBadge ipsBadge--intermediary',
				1 => Application::$titleLangPrefix . $category->app,
				2 => new Badge( Badge::BADGE_INTERMEDIARY, Member::loggedIn()->language()->addToStack( Application::$titleLangPrefix . $category->app ), icon: 'fa-lock' )
			];
		}

		return Theme::i()->getTemplate( 'trees', 'core' )->row(
			$this->url,
			$category->id,
			$category->_title,
			$category->hasContents(),
			$buttons,
			'', // description
			$category->icon(),
			$category->position,
			false,
			null, // toggleStatus
			( $category->app != $this->application->directory ), // locked
			$badge, // badge
			false, // titleHtml
			false // descriptionHtml
		);
	}

	public function _getCategoryChildren( int|Category $category ) : array
	{
		if( is_numeric( $category ) )
		{
			$category = Category::load( $category );
		}

		$children = [];
		foreach( $category->children() as $child )
		{
			if( $child->set_id )
			{
				continue;
			}

			if( $child instanceof Setting )
			{
				$children[ 's.' . $child->id ] = $this->_getSettingRow( $child );
			}
			else
			{
				$children[ $child->id ] = $this->_getCategoryRow( $child );
			}
		}
		return $children;
	}

	/**
	 * @param int|Setting $setting
	 * @return string
	 */
	public function _getSettingRow( int|Setting $setting ) : string
	{
		if( is_numeric( $setting ) )
		{
			$setting = Setting::load( $setting );
		}

		$buttons = [];
		if( $setting->app == $this->application->directory )
		{
			$buttons['edit'] = [
				'icon' => 'pencil',
				'title' => 'edit',
				'link' => $this->url->setQueryString( array( 'do' => 'settingForm', 'id' => $setting->id ) )
			];
			$buttons['delete'] = [
				'icon' => 'times-circle',
				'title' => 'delete',
				'link' => $this->url->setQueryString( array( 'do' => 'settingDelete', 'id' => $setting->id ) )->csrf(),
				'data' => array( 'delete' => '' ),
				'hotkey'=> 'd'
			];
		}

		$badge = null;
		if( $setting->app != $this->application->directory )
		{
			$badge = [
				0 => 'ipsBadge ipsBadge--intermediary',
				1 => Application::$titleLangPrefix . $setting->app,
				2 => new Badge( Badge::BADGE_INTERMEDIARY, Member::loggedIn()->language()->addToStack( Application::$titleLangPrefix . $setting->app ), icon: 'fa-lock' )
			];
		}

		return Theme::i()->getTemplate( 'trees', 'core' )->row(
			$this->url,
			's.' . $setting->id,
			$setting->_title,
			false,
			$buttons,
			$setting->description, // description
			null, // icon
			$setting->position,
			false,
			null, // toggleStatus
			null, // locked
			$badge, // badge
			false, // titleHtml
			true, // descriptionHtml
			false, // acceptsChildren
			false // canBeRoot
		);
	}

	/**
	 * @return void
	 */
	protected function categoryForm() : void
	{
		$form = new Form;
		if( isset( Request::i()->id ) )
		{
			try
			{
				$category = Category::load( Request::i()->id );
				if( !$category->canEdit() or $category->app != $this->application->directory )
				{
					Output::i()->error( 'node_noperm_edit', '2D101/A', 403 );
				}
			}
			catch( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2D101/B', 404 );
			}
		}
		else
		{
			$category = new Category;
			$category->app = $this->application->directory;
			$category->parent = Request::i()->parent ?? 0;
		}

		$category->form( $form );
		if( $values = $form->values() )
		{
			$category->saveForm( $category->formatFormValues( $values ) );

			$this->application->buildThemeEditorSettings();

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * @return void
	 */
	protected function categoryDelete() : void
	{
		try
		{
			$category = Category::load( Request::i()->id );
			if( !$category->canEdit() or $category->app != $this->application->directory )
			{
				Output::i()->error( 'node_noperm_edit', '2D101/C', 403 );
			}
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2D101/D', 404 );
		}

		/* Do we have any children or content? */
		if ( $category->hasChildren( NULL ) or $category->showDeleteOrMoveForm() or isset( Request::i()->ajaxValidate ) )
		{
			$form = $category->deleteOrMoveForm();
			if ( $values = $form->values() )
			{
				$category->deleteOrMoveFormSubmit( $values );

				if ( isset( $values['node_move_children'] ) AND $values['node_move_children'] )
				{
					$moveToId = ( isset( $values['node_destination'] ) ) ? $values['node_destination'] : Request::i()->node_move_children;

					Session::i()->log( 'acplog__node_deleted_m', array( Category::$nodeTitle => TRUE, $category->titleForLog() => FALSE, $category::load( $moveToId )->titleForLog() => FALSE ) );
				}
				else
				{
					Session::i()->log( 'acplog__node_deleted_c', array( Category::$nodeTitle => TRUE, $category->titleForLog() => FALSE ) );
				}

				$this->application->buildThemeEditorSettings();

				Output::i()->redirect( $this->url, 'deleted' );
			}
			else
			{
				/* Show form */
				Output::i()->output = $form;
				return;
			}
		}
		else
		{
			/* Make sure the user confirmed the deletion */
			Request::i()->confirmedDelete();
		}

		/* Delete it */
		Session::i()->log( 'acplog__node_deleted', array( Category::$nodeTitle => TRUE, $category->titleForLog() => FALSE ) );
		$category->delete();

		$this->application->buildThemeEditorSettings();

		/* Boink */
		if( Request::i()->isAjax() )
		{
			Output::i()->json( "OK" );
		}
		else
		{
			Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $category->parent() ? $category->parent()->_id : '' ) ) ), 'deleted' );
		}
	}

	/**
	 * @return void
	 */
	protected function settingForm() : void
	{
		$form = new Form;
		if( isset( Request::i()->id ) )
		{
			try
			{
				$setting = Setting::load( Request::i()->id );
				if( !$setting->canEdit() or $setting->app != $this->application->directory )
				{
					Output::i()->error( 'node_noperm_edit', '2D101/E', 403 );
				}
			}
			catch( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2D101/F', 404 );
			}
		}
		else
		{
			$setting = new Setting;
			$setting->app = $this->application->directory;
			$setting->category_id = Request::i()->category ?? 0;
		}

		$setting->form( $form );
		if( $values = $form->values() )
		{
			$setting->saveForm( $setting->formatFormValues( $values ) );

			$this->application->buildThemeEditorSettings();

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * @return void
	 */
	protected function settingDelete() : void
	{
		Request::i()->confirmedDelete();

		try
		{
			$setting = Setting::load( Request::i()->id );
			Session::i()->log( 'acplog__node_deleted', array( Setting::$nodeTitle => TRUE, $setting->titleForLog() => FALSE ) );
			$setting->delete();
			$this->application->buildThemeEditorSettings();
		}
		catch( OutOfRangeException ){}

		Output::i()->redirect( $this->url, 'deleted' );
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
		foreach( $order as $parent => $settings )
		{
			foreach( $settings as $id => $position )
			{
				if( mb_substr( $id, 0, 2 ) == 's.' )
				{
					try
					{
						$setting = Setting::load( mb_substr( $id, 2 ) );
						$setting->category_id = $parent;
						$setting->position = $position;
						$setting->save();
					}
					catch( OutOfRangeException ){}
				}
				else
				{
					try
					{
						$category = Category::load( $id );
						$category->position = $position;

						if( is_numeric( $parent ) )
						{
							$category->parent = $parent;
						}

						$category->save();
					}
					catch( OutOfRangeException ){}
				}
			}
		}

		$this->application->buildThemeEditorSettings();

		/* If this is an AJAX request, just respond */
		if( Request::i()->isAjax() )
		{
			return;
		}
		/* Otherwise, redirect */
		else
		{
			Output::i()->redirect( $this->url->setQueryString( array( 'root' => Request::i()->root ) ) );
		}
	}

	/**
	 * Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$rows = array();

		/* Get results */
		$nodeClass = Category::class;
		$results = $nodeClass::search( '_title', Request::i()->input, '_title' );

		/* Get results of subnodes */
		if ( isset( $nodeClass::$subnodeClass ) )
		{
			$subnodeClass = $nodeClass::$subnodeClass;
			$results = array_merge( $results, array_values( $subnodeClass::search( '_title', Request::i()->input, '_title' ) ) );

			usort( $results, function( $a, $b ) {
				return strnatcasecmp( $a->_title, $b->_title );
			} );
		}

		/* Convert to HTML */
		foreach ( $results as $result )
		{
			if( $result instanceof Category )
			{
				$rows[ $result->_id ] = $this->_getCategoryRow( $result );
			}
			else
			{
				$rows[ 's.' . $result->_id ] = $this->_getSettingRow( $result );
			}
		}

		Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}
}