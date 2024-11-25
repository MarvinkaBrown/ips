<?php
/**
 * @brief		Folder Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		07 July 2015
 */

namespace IPS\cms\Media;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\cms\Media;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use OutOfRangeException;
use function count;
use function defined;
use function func_get_args;

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
	public static ?string $databaseTable = 'cms_media_folders';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'media_folder_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('media_folder_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent';
	
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
	public static ?string $subnodeClass = 'IPS\cms\Media';
	
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
 		'all'		=> 'media_manage',
 		'prefix'	=> 'media_'
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
			$buttons['add']['title'] = 'cms_add_media_folder';
			$buttons['add']['data']  = array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cms_add_media_folder') );
			$buttons['add']['link']	 = $url->setQueryString( array( 'subnode' => 0, 'do' => 'form', 'parent' => $this->_id ) );
			
			$buttons['add_page'] = array(
					'icon'	=> 'plus-circle',
					'title'	=> 'cms_add_media',
					'link'	=> $url->setQueryString( array( 'subnode' => 1, 'do' => 'add', 'parent' => $this->_id ) ),
					'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cms_add_media') )
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
			$return['delete'] = $buttons['delete'];
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
		$form->add( new Text( 'media_folder_name', $this->id ? $this->name : '', TRUE, array( 'maxLength' => 64 ), function( $val )
		{
			try
			{
				$test = Folder::load( Friendly::seoTitle( $val ), 'media_folder_name' );

				if ( ! empty( Request::i()->id ) and $test->id != Request::i()->id )
				{
					throw new InvalidArgumentException('content_folder_name_in_use');
				}
			}
			catch ( OutOfRangeException $e )
			{
			}
		} ) );
		
		$form->add( new Node( 'media_folder_parent', $this->parent ? $this->parent : 0, FALSE, array(
				'class'         => '\IPS\cms\Media\Folder',
				'zeroVal'         => 'node_no_parent',
				'permissionCheck' => function( $node )
				{
					if ( ! isset( Request::i()->id ) )
					{
						return true;
					}

					if ( ! isset( Request::i()->parent ) )
					{
						return $node->id != Request::i()->id;
					}

					return true;
				}
		) ) );
	}

	/**
	 * @brief	Original parent ID
	 */
	protected int $origParentId;

	/**
	 * @brief	Original parent Name
	 */
	protected string $origName;

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( ! $this->id )
		{
			$this->save();
		}
		
		$this->origParentId = (int) $this->parent;
		$this->origName     = (string) $this->name;
		
		if ( isset( $values['media_folder_parent'] ) AND ( ! empty( $values['media_folder_parent'] ) OR $values['media_folder_parent'] === 0 ) )
		{
			$values['parent'] = ( $values['media_folder_parent'] === 0 ) ? 0 : $values['media_folder_parent']->id;
			unset( $values['media_folder_parent'] );
		}
		
		if( isset( $values['media_folder_name'] ) )
		{
			$values['name'] = Friendly::seoTitle( $values['media_folder_name'] );
			unset( $values['media_folder_name'] );
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		if ( $this->origParentId !== $values['parent'] OR $this->origName !== $values['name'] )
		{
			$this->resetPath( true );
		}
	}
	
	/**
	 * Resets the stored path
	 * 
	 * @param	boolean	$recursivelyCheck	Recursively reset up and down the tree
	 * @return	void
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
		
		/* Update media */
		Media::resetPath( $this->id );
		
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