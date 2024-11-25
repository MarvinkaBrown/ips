<?php
/**
 * @brief		Blog Category Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		30 Jul 2019
 */

namespace IPS\blog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content\Item;
use IPS\Content\ViewUpdates;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;
use function get_called_class;
use function get_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog Category
 */
class Category extends Model
{
	use ViewUpdates;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'blog_categories';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'category_';

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
	public static string $nodeTitle = 'blog_categories';

	/**
	 * @brief	[Node] Subnode class
	 */
	public static ?string $subnodeClass = 'IPS\blog\Blog';

	/**
	 * @brief	Content Item Class
	 */
	public static ?string $contentItemClass = 'IPS\blog\Entry';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'blog_category_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'seo_name';

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
		'app'		=> 'blog',
		'module'	=> 'categories',
		'prefix'	=> 'categories_',
	);

	/**
	 * @brief   The class of the ACP \IPS\Node\Controller that manages this node type
	 */
	protected static ?string $acpController = "IPS\\blog\\modules\\admin\\blogs\\blogs";

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->addHeader('category_basic_settings');
		$form->add( new Translatable( 'category_name', NULL, TRUE, array( 'app' => 'blog', 'key' => $this->id ? "blog_category_{$this->id}" : NULL ) ) );

		Member::loggedIn()->language()->words['category_desc'] = Member::loggedIn()->language()->addToStack('blog_category_desc');
		$form->add( new Translatable( 'category_desc', NULL, FALSE, array(
			'app'		=> 'blog',
			'key'		=> ( $this->id ? "blog_category_{$this->id}_desc" : NULL ),
			'editor'	=> array(
				'app'			=> 'blog',
				'key'			=> 'Categories',
				'autoSaveKey'	=> ( $this->id ? "blog-category-{$this->id}" : "blog-new-category" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'bcategory' ) : NULL, 'minimize' => 'category_desc_placeholder'
			)
		) ) );

		$class = get_called_class();

		$form->add( new Node( 'category_parent', $this->id ? $this->parent : 0, TRUE, array( 'class' => 'IPS\blog\Category', 'subnodes' => FALSE, 'zeroVal' => 'no_parent', 'permissionCheck' => function( $node ) use ( $class )
		{
			if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
			{
				return FALSE;
			}

			return !isset( Request::i()->id ) or ( $node->id != Request::i()->id and !$node->isChildOf( $node::load( Request::i()->id ) ) );
		} ) ) );

        parent::form( $form );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['category_parent'] ) )
		{
			$values['parent'] = $values['category_parent'] ? $values['category_parent']->id : 0;
		}

		if ( !$this->id )
		{
			$this->save();
			File::claimAttachments( 'blog-new-category', $this->id, NULL, 'bcategory', TRUE );
		}
		elseif( isset( $values['category_name'] ) OR isset( $values['category_desc'] ) )
		{
			$this->save();
		}

		if( isset( $values['category_name'] ) )
		{
			Lang::saveCustom( 'blog', "blog_category_{$this->id}", $values['category_name'] );
			$values['seo_name'] = Friendly::seoTitle( $values['category_name'][ Lang::defaultLanguage() ] );
		
			unset( $values['category_name'] );
		}

		if( isset( $values['category_desc'] ) )
		{
			Lang::saveCustom( 'blog', "blog_category_{$this->id}_desc", $values['category_desc'] );
			unset( $values['category_desc'] );
		}

	
		return $values;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * Get URL
	 *
	 * @return Url|string|null
	 */
	public function url(): Url|string|null
	{
		if( $this->_url === NULL )
		{
			$seoTitleColumn = static::$seoTitleColumn;
			$this->_url = Url::internal( "app=blog&module=blogs&controller=browse&id={$this->id}", 'front', 'blog_category', $this->$seoTitleColumn ? $this->$seoTitleColumn : '-' );
		}

		return $this->_url;
	}
	
	/**
	 * Get HTML link
	 *
	 * @return	string
	 */
	public function link(): string
	{
		return Theme::i()->getTemplate( 'global', 'blog' )->blogCategoryLink( $this );
	}
	
	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @return	Url
	 */
	public static function urlFromIndexData( array $indexData, array $itemData, ?array $containerData ): Url
	{
		return Url::internal( "app=blog&module=blogs&controller=browse&category={$indexData['index_container_id']}", 'front', 'blog_category', Member::loggedIn()->language()->addToStack( 'blog_category_' . $indexData['index_container_id'], FALSE, array( 'seotitle' => TRUE ) ) );
	}

	/**
	 * [Node] Get Node Icon
	 *
	 * @return	string|null
	 */
	protected function get__icon(): string|null
	{
		return ( $this->parent > 0 ) ? 'caret-down' : NULL ;
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse	int			id			ID number
	 * @apiresponse	string		name		Name
	 * @apiresponse	string		url			URL
	 * @apiresponse	string		class		Node class
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		$return = array(
			'id'		=> $this->id,
			'name'		=> $this->_title,
			'url'		=> (string) $this->url(),
			'class'		=> get_class( $this ),
		);

		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\ClubContainer' ) AND $this->club() )
		{
			$return['public'] = ( $this->isPublic() ) ? 1 : 0;
		}
		
		return $return;
	}
	
	/**
	 * Retrieve the content item count
	 *
	 * @param array|null $data	Data array for mass move/delete
	 * @return	null|int
	 */
	public function getContentItemCount( array $data=NULL ): ?int
	{
		if ( !isset( static::$contentItemClass ) )
		{
			return null;
		}

		/* @var Item $contentItemClass */
		$contentItemClass = static::$contentItemClass;

		$idColumn = static::$databaseColumnId;

		$where = array( array( 'blog_blogs.blog_category_id=?', $this->$idColumn ) );

		if( $data )
		{
			$where = array_merge_recursive( $where, $this->massMoveorDeleteWhere( $data ) );
		}

		$select = Db::i()->select( 'COUNT(*)', $contentItemClass::$databaseTable, $where )->join( 'blog_blogs', "blog_entries.entry_blog_id=blog_blogs.blog_id" );
				
		return (int) $select->first();
	}

	/**
	 * Retrieve content items (if applicable) for a node.
	 *
	 * @param int|null $limit			The limit
	 * @param int|null $offset			The offset
	 * @param	array	$additionalWhere		Where Additional where clauses
	 * @param bool|int $countOnly		If TRUE, will get the number of results
	 * @return	ActiveRecordIterator|int
	 * @throws	BadMethodCallException
	 */
	public function getContentItems( ?int $limit, ?int $offset, array $additionalWhere = array(), bool|int $countOnly=FALSE ): ActiveRecordIterator|int
	{
		if ( !isset( static::$contentItemClass ) )
		{
			throw new BadMethodCallException;
		}

		/* @var Item $contentItemClass */
		$contentItemClass = static::$contentItemClass;
		
		$where		= array();
		$where[]	= array( 'blog_blogs.blog_category_id=?', $this->_id );

		if ( count( $additionalWhere ) )
		{
			foreach( $additionalWhere AS $clause )
			{
				$where[] = $clause;
			}
		}
		
		if ( $countOnly )
		{
			return Db::i()->select( 'COUNT(*)', $contentItemClass::$databaseTable, $where )->join( 'blog_blogs', "blog_entries.entry_blog_id=blog_blogs.blog_id" )->first();
		}
		else
		{
			$limit	= ( $offset !== NULL ) ? array( $offset, $limit ) : NULL;
			return new ActiveRecordIterator( Db::i()->select( '*', $contentItemClass::$databaseTable, $where, $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId, $limit )->join( 'blog_blogs', "blog_entries.entry_blog_id=blog_blogs.blog_id" ), $contentItemClass );
		}
	}
}
