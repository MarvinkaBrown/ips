<?php
/**
 * @brief		Package Group Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use OutOfRangeException;
use function count;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package Group
 */
class Group extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_package_groups';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'pg_';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'product_groups';
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static ?string $subnodeClass = 'IPS\nexus\Package';

	/**
	 * @brief	Content Item Class
	 */
	public static ?string $contentItemClass = 'IPS\nexus\Package\Item';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_pgroup_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';
								
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'nexus',
		'module'	=> 'store',
		'prefix'	=> 'packages_',
	);

	/**
	 * Return only the root groups that have packages OR subcategories/groups
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	array				$where				Additional WHERE clause
	 * @return	array
	 */
	public static function rootsWithViewablePackages( ?string $permissionCheck='view', ?Member $member=NULL, array $where=array() ) : array
	{
		$roots = static::roots( $permissionCheck, $member, $where );

		foreach( $roots as $index => $group )
		{
			if( !$group->hasSubgroups() AND !$group->hasPackages( NULL, array( array( "p_store=1 AND ( p_member_groups='*' OR " . Db::i()->findInSet( 'p_member_groups', Member::loggedIn()->groups ) . ' )' ) ) ) )
			{
				unset( $roots[ $index ] );
			}
		}

		return $roots;
	}
	
	/**
	 * Load record based on a URL
	 *
	 * @param Url $url	URL to load from
	 * @return	static
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl( Url $url ): static
	{
		$qs = array_merge( $url->queryString, $url->hiddenQueryString );
		
		if ( isset( $qs['cat'] ) )
		{
			return static::load( $qs['cat'] );
		}
		
		throw new InvalidArgumentException;
	}
		
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->addHeader('pg_basic_settings');
		$form->add( new Translatable( 'pg_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_pgroup_{$this->id}" : NULL ) ) );
		$form->add( new Translatable( 'pg_desc', NULL, FALSE, array(
			'app'		=> 'nexus',
			'key'		=> ( $this->id ? "nexus_pgroup_{$this->id}_desc" : NULL ),
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-group-{$this->id}" : "nexus-new-group" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pgroup' ) : NULL, 'minimize' => 'pg_desc_placeholder'
			)
		) ) );

		$class = get_called_class();

		$form->add( new Node( 'pg_parent', $this->id ? $this->parent : 0, TRUE, array( 'class' => 'IPS\nexus\Package\Group', 'subnodes' => FALSE, 'zeroVal' => 'no_parent', 'permissionCheck' => function( $node ) use ( $class )
		{
			if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
			{
				return FALSE;
			}

			return !isset( Request::i()->id ) or ( $node->id != Request::i()->id and !$node->isChildOf( $node::load( Request::i()->id ) ) );
		} ) ) );
		$form->add( new Upload( 'pg_image', $this->image ? File::get( 'nexus_PackageGroups', $this->image ) : NULL, FALSE, array( 'storageExtension' => 'nexus_PackageGroups', 'image' => TRUE, 'allowStockPhotos' => TRUE ) ) );
		
		$priceFilters = array();
		if ( $this->price_filters )
		{
			foreach ( json_decode( $this->price_filters, TRUE ) as $currency => $prices )
			{
				foreach ( $prices as $i => $price )
				{
					$priceFilters[ $i ][ $currency ] = $price;
				}
			}
		}
		
		$form->addHeader('pg_filters_header');
		$form->add( new Node( 'pg_filters', $this->filters ? explode( ',', $this->filters ) : [], FALSE, array( 'class' => 'IPS\nexus\Package\Filter', 'multiple' => TRUE ) ) );
		$form->add( new Stack( 'pg_price_filters', $priceFilters, FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\Money' ) ) );
		
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{		
		if( isset( $values['pg_parent'] ) )
		{
			$values['parent'] = $values['pg_parent'] ? $values['pg_parent']->id : 0;
		}

		if( isset( $values['pg_image'] ) )
		{
			$values['image'] = (string) $values['pg_image'];
		}
		
		if ( !$this->id )
		{
			$this->save();
			File::claimAttachments( 'nexus-new-group', $this->id, NULL, 'pgroup', TRUE );
		}
		elseif( isset( $values['pg_name'] ) OR isset( $values['pg_desc'] ) )
		{
			$this->save();
		}
		
		if( isset( $values['pg_name'] ) )
		{
			Lang::saveCustom( 'nexus', "nexus_pgroup_{$this->id}", $values['pg_name'] );
			unset( $values['pg_name'] );
		}

		if( isset( $values['pg_desc'] ) )
		{
			Lang::saveCustom( 'nexus', "nexus_pgroup_{$this->id}_desc", $values['pg_desc'] );
			unset( $values['pg_desc'] );
		}
		
		$values['pg_filters'] = $values['pg_filters'] ? implode( ',', array_keys( $values['pg_filters'] ) ) : NULL;
		
		$priceFilters = array();
		foreach ( $values['pg_price_filters'] as $filter )
		{
			foreach ( $filter as $currency => $amount )
			{
				$priceFilters[ $currency ][] = $amount->amount->jsonSerialize();
			}
		}
		$values['pg_price_filters'] = count( $priceFilters ) ? json_encode( $priceFilters ) : NULL;

		return $values;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * Get URL
	 *
	 * @return    Url\Internal|string|null
	 */
	public function url(): Url\Internal|string|null
	{
		if( $this->_url === NULL )
		{
			$this->_url = Url::internal( "app=nexus&module=store&controller=store&cat={$this->id}", 'front', 'store_group', Friendly::seoTitle( Member::loggedIn()->language()->get( 'nexus_pgroup_' . $this->id ) ) );
		}

		return $this->_url;
	}
	
	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @return    Url
	 */
	public static function urlFromIndexData( array $indexData, array $itemData, ?array $containerData ): Url
	{
		return Url::internal( "app=nexus&module=store&controller=store&cat={$indexData['index_container_id']}", 'front', 'store_group', Member::loggedIn()->language()->addToStack( 'nexus_pgroup_' . $indexData['index_container_id'], FALSE, array( 'seotitle' => TRUE ) ) );
	}
	
	/**
	 * Get full image URL
	 *
	 * @return string|null
	 */
	public function get_image() : string|null
	{
		return ( isset( $this->_data['image'] ) ) ? (string) File::get( 'nexus_PackageGroups', $this->_data['image'] )->url : NULL;
	}
	
	/**
	 * Does this group have subgroups?
	 *
	 * @param	array	$_where	Additional WHERE clause
	 * @return	bool
	 */
	public function hasSubgroups( array $_where=array() ) : bool
	{
		return ( $this->childrenCount( NULL, NULL, FALSE, $_where ) > 0 );
	}
	
	/**
	 * Does this group have packages?
	 *
	 * @param	Member|NULL|FALSE	$member	The member to perform the permission check for, or NULL for currently logged in member, or FALSE for no permission check
	 * @param	array					$_where			Additional WHERE clause
	 * @param	bool					$viewableOnly	Only check packages the member can view
	 * @return	bool
	 */
	public function hasPackages( Member|null|bool $member=NULL, array $_where=array(), bool $viewableOnly=FALSE ) : bool
	{
		if( $viewableOnly === TRUE )
		{
			$member = $member ?: Member::loggedIn();

			$_where[]	= array( "p_store=1 AND ( p_member_groups='*' OR " . Db::i()->findInSet( 'p_member_groups', $member->groups ) . ' )' );
		}

		return ( $this->childrenCount( $member === FALSE ? FALSE : 'view', $member, NULL, $_where ) > 0 );
	}
	
	/**
	 * Get Filter Options
	 *
	 * @param	Lang	$language	The language to return options in
	 * @return	array
	 */
	public function filters( Lang $language ) : array
	{
		$return = array();
		
		if ( $this->filters )
		{
			foreach ( Db::i()->select( 'pfilter_id', 'nexus_package_filters', array( Db::i()->in( 'pfilter_id', explode( ',', $this->filters ) ) ), 'pfilter_order' ) as $filterId )
			{
				$return[ $filterId ] = array();
			}
						
			foreach ( Db::i()->select( '*', 'nexus_package_filters_values', array( array( Db::i()->in( 'pfv_filter', array_keys( $return ) ) ), array( 'pfv_lang=?', $language->id ) ), 'pfv_order' ) as $value )
			{
				$return[ $value['pfv_filter'] ][ $value['pfv_value'] ] = $value['pfv_text'];
			}
		}

		return $return;
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone() : void
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldImage = $this->image;

		parent::__clone();

		if ( $oldImage )
		{
			try
			{
				$icon = File::get( 'nexus_PackageGroups', $oldImage );
				$newIcon = File::create( 'nexus_PackageGroups', $icon->originalFilename, $icon->contents() );
				$this->image = (string) $newIcon;
			}
			catch ( Exception )
			{
				$this->pg_image = NULL;
			}

			$this->save();
		}
	}
	
	/**
	 * Is this node currently queued for deleting or moving content?
	 *
	 * @return	bool
	 */
	public function deleteOrMoveQueued(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	* array(
	* array(
	* 'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	* 'title'	=> 'foo',		// Language key to use for button's title parameter
	* 'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	* 'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	* ),
	* ...							// Additional buttons
	* );
	 * @endcode
	 * @param Url $url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ):array
	{
		$buttons = parent::getButtons( $url, $subnode );

		if( isset( $buttons['content'] ) )
		{
			$buttons['content']['title'] = 'mass_manage_productgroups';
		}

		return $buttons;
	}
}
