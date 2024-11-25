<?php
/**
 * @brief		Block Container Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		19 Feb 2014
 */

namespace IPS\cms\Blocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Block Container Model
 */
class Container extends Model
{

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_containers';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'container_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('container_key');
	
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
	public static ?string $databaseColumnOrder = 'order';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = '';
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static ?string $subnodeClass = 'IPS\cms\Blocks\Block';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;
	
	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = TRUE;

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
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		return parent::roots( $permissionCheck, $member, array( array( 'container_type=?', 'block' ) ), $limit );
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
			$buttons['add']['title'] = 'content_block_cat_add';
			$buttons['add']['data']  = array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('content_block_cat_add') );
			$buttons['add']['link']	 = $url->setQueryString( array( 'subnode' => 0, 'do' => 'form', 'parent' => $this->_id ) );
				
			$buttons['add_block'] = array(
					'icon'	=> 'plus-circle',
					'title'	=> 'content_block_block_add',
					'link'	=> $url->setQueryString( array( 'subnode' => 1, 'do' => 'addBlockType', 'parent' => $this->_id ) )
			);
		}
		
		/* Re-arrange */
		if ( isset( $buttons['edit'] ) )
		{
			$return['edit'] = $buttons['edit'];
		}
		
		if ( isset( $buttons['add_block'] ) )
		{
			$return['add_block'] = $buttons['add_block'];
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
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		if ( $this->key == 'block_custom' OR $this->key == 'block_plugins' )
		{
			return FALSE;
		}

		return parent::canDelete();
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
		$form->add( new Text( 'container_name', $this->id ? $this->name : '', TRUE, array( 'maxLength' => 64 ) ) );

		$class = get_called_class();

		$form->add( new Node( 'container_parent_id', $this->parent_id ? $this->parent_id : 0, FALSE, array(
				'class'         => '\IPS\cms\Blocks\Container',
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
		if ( isset( $values['container_parent_id'] ) AND ( ! empty( $values['container_parent_id'] ) OR $values['container_parent_id'] === 0 ) )
		{
			$values['container_parent_id'] = ( $values['container_parent_id'] === 0 ) ? 0 : $values['container_parent_id']->id;
		}
		
		$values['type'] = 'block';
		
		return $values;
	}
	
	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( string $column, string $query, ?string $order=NULL, mixed $where=array() ): array
	{
		if ( $column === '_title' )
		{
			$column = 'container_name';
		}
		
		$where = array( array( 'container_type=?', 'block' ) );
	
		return parent::search( $column, $query, 'container_name ASC', $where );
	}
}