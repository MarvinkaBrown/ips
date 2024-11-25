<?php
/**
 * @brief		Folder Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		15 Jan 2014
 */

namespace IPS\cms\Pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Session;
use OutOfRangeException;
use function count;
use function defined;
use function func_get_args;
use function get_called_class;
use function get_class;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Folder Model
 */
class Folder extends Model
{
	/**
	 * Munge different record types
	 *
	 * @return  array
	 */
	public static function munge() : array
	{
		$rows = array();
		$args = func_get_args();
	
		foreach( $args as $arg )
		{
			foreach( $arg as $id => $obj )
			{
				$rows[ $obj->getSortableName() . '_' . $obj::$databaseTable . '_' . $obj->id  ] = $obj;
			}
		}
	
		ksort( $rows );
	
		return $rows;
	}
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_folders';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'folder_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('folder_name', 'folder_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Parent ID Root Value
	 * @note	This normally doesn't need changing though some legacy areas use -1 to indicate a root node
	 */
	public static int $databaseColumnParentRootValue = 0;
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'path';

	/**
	 * @brief	[Node] Automatically set position for new nodes
	 */
	public static bool $automaticPositionDetermination = FALSE;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'folder';
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static ?string $subnodeClass = 'IPS\cms\Pages\Page';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;
	
	/**
	 * @brief	[Node] Restrictions
	 */
	protected static ?array $restrictions = array(
 		'app'		=> 'cms',
 		'module'	=> 'pages',
 		'all'		=> 'page_manage',
 		'prefix'	=> 'page_'
	);
	
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
	 * Get sortable name
	 *
	 * @return	string
	 */
	public function getSortableName() : string
	{
		return $this->name;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 *
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );
		$return  = array();
		
		if ( isset( $buttons['copy'] ) )
		{
			unset( $buttons['copy'] );
		}
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['icon']	 = 'folder-open';
			$buttons['add']['title'] = 'content_add_folder';
			$buttons['add']['data']  = array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('content_add_folder') );
			$buttons['add']['link']	 = $url->setQueryString( array( 'subnode' => 0, 'do' => 'form', 'parent' => $this->_id ) );
			
			$buttons['add_page'] = array(
					'icon'	=> 'plus-circle',
					'title'	=> 'content_add_page',
					'link'	=> $url->setQueryString( array( 'subnode' => 1, 'do' => 'form', 'parent' => $this->_id ) )
			);
		}
		
		/* Re-arrange */
		if ( isset( $buttons['edit'] ) )
		{
			$return['edit'] = $buttons['edit'];
		}
		
		if ( isset( $buttons['add_page'] ) )
		{
			$return['add_page'] = $buttons['add_page'];
		}
		
		if ( isset( $buttons['add'] ) )
		{
			$return['add'] = $buttons['add'];
		}
			
		if ( isset( $buttons['delete'] ) )
		{
			if ( $this->getItemCount() )
			{
				$return['delete'] = array(
					'icon'	=> 'trash',
					'title'	=> 'empty',
					'link'	=> $url->setQueryString( array( 'do' => 'delete', 'id' => $this->_id ) ),
					'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('empty') ),
					'hotkey'=> 'd'
				);
			}
			else
			{
				$return['delete'] = $buttons['delete'];
			}
		}	
		
		return $return;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		/* Build form */
		$form->add( new Text( 'folder_name', $this->id ? $this->name : '', TRUE, array( 'maxLength' => 64 ), function( $val )
		{
			try
			{
				$testPage = Page::load( $val, 'page_seo_name' );
				
				/* Ok, we have a folder, but is it on the same tree as us ?*/
				if ( intval( Request::i()->folder_parent_id ) == $testPage->folder_id )
				{
					/* Yep, this will break designers' mode and may confuse the FURL engine so we cannot allow this */
					throw new InvalidArgumentException('content_folder_name_furl_collision_folder');
				}
			}
			catch ( OutOfRangeException $e )
			{
				/* Nothing with the same name, so that's proper good that is */
			}
			
			try
			{
				$test = Folder::load( ( $val === '.well-known' ) ? $val : Friendly::seoTitle( $val ), 'folder_name' );

				if ( empty( Request::i()->id ) or $test->id != Request::i()->id )
				{
					throw new InvalidArgumentException('content_folder_name_in_use');
				}
			}
			catch ( OutOfRangeException $e )
			{
				/* If we hit here, we don't have an existing folder by that name so check for a collision */
				if ( Request::i()->folder_parent_id == 0 AND Page::isFurlCollision( ( $val === '.well-known' ) ? $val : Friendly::seoTitle( $val ) ) )
				{
					throw new InvalidArgumentException('content_folder_name_furl_collision');
				}
			}
		} ) );

		$class = get_called_class();

		$form->add( new Node( 'folder_parent_id', $this->parent_id ? $this->parent_id : 0, FALSE, array(
				'class'         => '\IPS\cms\Pages\Folder',
				'zeroVal'         => 'node_no_parent',
				'permissionCheck' => function( $node ) use ( $class )
				{
					if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
					{
						return FALSE;
					}

					return !isset( Request::i()->id ) or ( $node->id != Request::i()->id and !$node->isChildOf( $node::load( Request::i()->id ) ) );
				}
		) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$isNew = $this->_new;

		if ( !$this->id )
		{
			$this->save();
		}
		
		$this->origParentId = $this->parent_id;
		$this->origName     = $this->name;
		
		if ( isset( $values['folder_parent_id'] ) AND ( ! empty( $values['folder_parent_id'] ) OR $values['folder_parent_id'] === 0 ) )
		{
			$values['parent_id'] = ( $values['folder_parent_id'] === 0 ) ? 0 : $values['folder_parent_id']->id;
			unset( $values['folder_parent_id'] );
		}
		
		if( isset( $values['folder_name'] ) )
		{
			/* If the folder name is explicitly .well-known, allow that as it will allow sites on CiC to use Apple Pay */
			if ( $values['folder_name'] === '.well-known' )
			{
				$values['name'] = $values['folder_name'];
			}
			else
			{
				$values['name'] = Friendly::seoTitle( $values['folder_name'] );
			}
			unset( $values['folder_name'] );
		}

		if ( ! $isNew and ( $this->parent_id !== $values['parent_id'] or $this->name !== $values['name'] ) )
		{
			$this->storeUrl();
		}

		return $values;
	}

	/**
	 * @brief	Original parent ID
	 */
	protected ?int $origParentId = null;

	/**
	 * @brief	Original Name
	 */
	protected ?string $origName = null;

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		if ( $this->origParentId !== $values['parent_id'] OR $this->origName !== $values['name'] )
		{
			$this->resetPath( true );
		}
	}

	/**
	 * Stores the URL so when its changed, the old can 301 to the new location
	 *
	 * @return void
	 */
	public function storeUrl() : void
	{
		Db::i()->insert( 'cms_url_store', array(
			'store_path'       => $this->path,
			'store_current_id' => $this->_id,
			'store_type'       => 'folder'
		) );
	}

	/**
	 * Save a folder
	 * 
	 * @return void
	 */
	public function save(): void
	{
		$this->last_modified = time();
		
		parent::save();
	}
	
	/**
	 * Retrieve item \count(if applicable) for a node.
	 *
	 * @return	int
	 */
	public function getItemCount() : int
	{
		$pages = (int) Db::i()->select( 'COUNT(*)', 'cms_pages', array( 'page_folder_id=?', $this->id ) )->first();
		$subFolders = (int) Db::i()->select( 'COUNT(*)', 'cms_folders', array( 'folder_parent_id=?', $this->id ) )->first();

		return $pages + $subFolders;
	}
	
	/**
	 * Form to delete or move content
	 *
	 * @param	bool	$showMoveToChildren	If TRUE, will show "move to children" even if there are no children
	 * @return	Form
	 */
	public function deleteOrMoveForm( bool $showMoveToChildren=FALSE ): Form
	{
		$hasContent = $this->getItemCount();
			
		$form = new Form( 'form', 'delete' );
		
		if ( $hasContent )
		{
			$form->add( new Node( 'cms_move_pages', 0, TRUE, array( 'class' => get_class( $this ), 'disabled' => array( $this->_id ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'cms_move_to_root', 'subnodes' => FALSE, 'permissionCheck' => function( $node )
			{
				return Request::i()->id != $node->id;
			} ) ) );
		}
		
		return $form;
	}
	
	/**
	 * Handle submissions of form to delete or move content
	 *
	 * @param	array	$values			Values from form
	 * @return	void
	 */
	public function deleteOrMoveFormSubmit( array $values ) : void
	{
		if ( $values['cms_move_pages'] )
		{
			$folderId = $values['cms_move_pages']->_id;
		}
		else
		{
			$folderId = 0;
		}
		
		Db::i()->update( 'cms_pages', array( 'page_folder_id' => $folderId ), array( 'page_folder_id=?', $this->_id ) );
		Db::i()->update( 'cms_folders', array( 'folder_parent_id' => $folderId ), array( 'folder_id=?', $this->_id ) );
		Db::i()->update( 'cms_folders', array( 'folder_parent_id' => $folderId ), array( 'folder_parent_id=?', $this->_id ) );
		
		unset( Store::i()->pages_page_urls );
		
		/* Update pages */
		Page::resetPath( $folderId );
		
		/* Delete it */
		Session::i()->log( 'acplog__node_deleted', array( $this->title => TRUE, $this->titleForLog() => FALSE ) );
		$this->delete();
	}
	
	/**
	 * Resets the stored path
	 * 
	 * @param	boolean	$recursivelyCheck	Recursively reset up and down the tree
	 * @return void
	 */
	public function resetPath( bool $recursivelyCheck=true ) : void
	{
		$path = array();
		
		foreach( $this->parents() as $obj )
		{
			$path[] = $obj->name;
		}
		
		$this->path = ( count( $path ) ) ? implode( '/', $path ) . '/' . $this->name : $this->name;
		
		/* Save path update */
		parent::save();
		
		/* Update pages */
		Page::resetPath( $this->id );
		
		if ( $recursivelyCheck === true )
		{
			/* Fix children */
			foreach( $this->children( NULL, NULL, FALSE ) as $child )
			{
				$child->resetPath( false );
				$child->_recursivelyResetChildPaths();
			}
			
			/* Fix parents */
			foreach( $this->parents() as $parent )
			{
				$parent->resetPath( false );
			}
		}
	}
	
	/**
	 * Recurse through the node tree to reset kids
	 * 
	 * @return void
	 */
	protected function _recursivelyResetChildPaths() : void
	{
		foreach( $this->children( NULL, NULL, FALSE ) as $child )
		{
			$child->resetPath( false );
			$child->_recursivelyResetChildPaths();
		}
	}
}