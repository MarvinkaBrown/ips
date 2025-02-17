<?php
/**
 * @brief		RSS Exports
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Jul 2015
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Content\Search\ContentFilter;
use IPS\Content\Search\Query;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Xml\Rss as XmlRss;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * RSS Exports
 */
class Rss extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_rss_export';
	
	/**
	 * @brief	[ActiveRecord]	Database Prefix
	 */
	public static string $databasePrefix = 'rss_';
			
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'rss_exports';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'rss_export_title_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'enabled';
	
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
		'app'		=> 'core',
		'module'	=> 'discovery',
		'all'	 	=> 'rss_export_manage',
	);
	
	/**
	 * @brief	[Node] URL Base
	 */
	public static string $urlBase = 'app=core&module=discover&controller=rss&id=';
	
	/**
	 * @brief	[Node] SEO Template
	 */
	public static string $urlTemplate = 'rss_feed';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'seo_title';

	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you MUST define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = TRUE;

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		/* Main Settings Form */
		$form->addHeader( 'rss_settings' );
		$form->add( new Translatable( 'rss_name', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->_id ) ? "rss_export_title_{$this->_id}" : NULL ) ) );
		$form->add( new Translatable( 'rss_desc', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->_id ) ? "rss_export_title_{$this->_id}_desc" : NULL ) ) );
		$form->add( new Number( 'rss_count', ( $this->_id ) ? $this->count : 25, TRUE, array( 'min' => 1, 'max' => 100 ) ) );
		
		$groups = array();
		foreach( Group::groups() AS $group_id => $group )
		{
			$groups[ $group_id ] = $group->name;
		}
		$form->add( new CheckboxSet( 'rss_groups', ( $this->_id ) ? $this->groups : '*', FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'impliedUnlimited' => TRUE ) ) );
		
		/* Content Form */
		$form->addHeader( 'rss_content' );
		foreach( Content::routedClasses( TRUE, FALSE, TRUE ) AS $class )
		{
			if ( isset( $class::$databaseColumnMap['author'] ) )
			{
				$options		= array();
				$containerClass	= NULL;
				if ( isset( $class::$containerNodeClass ) )
				{
					$options = array( 'togglesOn' => array( 'rss_nodes_' . $class::$title ) );
					$containerClass = $class::$containerNodeClass;
				}

				Member::loggedIn()->language()->words["rss_classes_{$class}"] = Member::loggedIn()->language()->addToStack( $class::$title . '_pl' );
				$form->add( new YesNo( 'rss_classes_' . $class, ( $this->_id ) ? in_array( $class, $this->configuration['classes'] ) : FALSE, FALSE, $options ) );
				
				if ( $containerClass )
				{
					Member::loggedIn()->language()->words["nodes_{$class}"] = Member::loggedIn()->language()->addToStack( $containerClass::$nodeTitle );
					$form->add( new Node( "nodes_{$class}", ( $this->_id AND isset( $this->configuration['containers'][$class] ) ) ? $this->configuration['containers'][$class] : 0, FALSE, array(
						'class'				=> $containerClass,
						'zeroVal'			=> 'all',
						'multiple'			=> TRUE,
						'permissionCheck'	=> function( $val ) { return $val->can( 'view', new Member ); },
						'forceOwner'		=> FALSE,
					), NULL, NULL, NULL, "rss_nodes_{$class::$title}" ) );
				}
			}
		}
		
		$members = NULL;
		if ( $this->_id AND isset( $this->configuration['members'] ) )
		{
			if ( count( $this->configuration['members'] ) )
			{
				$members = array();
				foreach( $this->configuration['members'] AS $member )
				{
					$members[] = Member::load( $member );
				}
			}
		}
		
		$form->add( new FormMember( 'rss_members', $members, FALSE, array( 'multiple' => NULL, 'nullLang' => 'everyone' ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		/* Build our config */
		$config = array(
			'classes'		=> array(),
			'containers'	=> array(),
			'members'		=> array()
		);
		
		/* Set the classes and containers */
		foreach( $values AS $k => $v )
		{
			if ( mb_substr( $k, 0, 12 ) == 'rss_classes_' )
			{
				if ( $v )
				{
					$config['classes'][] = str_replace( 'rss_classes_', '', $k );
				}
				
				unset( $values[$k] );
			}
			else if ( mb_substr( $k, 0, 6 ) == 'nodes_' )
			{
				if ( is_array( $v ) )
				{
					foreach( $v AS $id => $node )
					{
						if ( $node instanceof Model )
						{
							$config['containers'][ str_replace( 'nodes_', '', $k ) ][] = $node->_id;
						}
						else
						{
							$config['containers'][ str_replace( 'nodes_', '', $k )][] = $node;
						}
					}
				}
				else
				{
					if ( $v instanceof Model )
					{
						$config['containers'][ str_replace( 'nodes_', '', $k ) ][] = $v->_id;
					}
					else
					{
						$config['containers'][ str_replace( 'nodes_', '', $k ) ] = $v;
					}
				}
				
				unset( $values[$k] );
			}
		}
		
		/* Set the members */
		if ( array_key_exists( 'rss_members', $values ) )
		{
			if ( is_array( $values['rss_members'] ) )
			{
				foreach( $values['rss_members'] AS $member )
				{
					if ( $member instanceof Member )
					{
						$config['members'][] = $member->member_id;
					}
				}
			}
			else if ( $values['rss_members'] instanceof Member )
			{
				$config['members'][] = $values['rss_members']->member_id;
			}
			unset( $values['rss_members'] );
		}
		
		$this->configuration = $config;
		
		/* Save, as we need the ID after this point */
		if ( !$this->_id )
		{
			$this->save();
		}
		
		/* Custom Language Strings */
		if ( array_key_exists( 'rss_name', $values ) )
		{
			Lang::saveCustom( 'core', "rss_export_title_{$this->_id}", $values['rss_name'] );
			
			if ( is_array( $values['rss_name'] ) )
			{
				reset( $values['rss_name'] );
				$this->seo_title = Friendly::seoTitle( $values['rss_name'][ key( $values['rss_name'] ) ] );
			}
			else
			{
				$this->seo_title = Friendly::seoTitle( $values['rss_name'] );
			}
			
			unset( $values['rss_name'] );
		}
		
		if ( array_key_exists( 'rss_desc', $values ) )
		{
			Lang::saveCustom( 'core', "rss_export_title_{$this->_id}_desc", $values['rss_desc'] );
			
			unset( $values['rss_desc'] );
		}
		
		/* Pass to parent */
		return parent::formatFormValues( $values );
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'rssFeeds' );

	/**
	 * Get Configuration
	 *
	 * @return	array
	 */
	public function get_configuration() : array
	{
		return json_decode( $this->_data['configuration'], TRUE );
	}
	
	/**
	 * Set Configuration
	 *
	 * @param	array	$values	The configuration
	 * @return	void
	 */
	public function set_configuration( array $values ) : void
	{
		$this->_data['configuration'] = json_encode( $values );
	}
	
	/**
	 * Set Groups
	 *
	 * @param	array|string	$values	The groups, or an asterisk for all groups
	 * @return	void
	 */
	public function set_groups( array|string $values ) : void
	{
		if ( is_array( $values ) )
		{
			$this->_data['groups'] = implode( ',', $values );
		}
		else
		{
			$this->_data['groups'] = '*';
		}
	}
	
	/**
	 * Get Groups
	 *
	 * @return	array|string
	 */
	public function get_groups() : array|string
	{
		if ( $this->_data['groups'] == '*' )
		{
			return $this->_data['groups'];
		}
		
		return explode( ',', $this->_data['groups'] );
	}
	
	/**
	 * Generate the feed
	 *
	 * @param	Member|NULL	$member	The member, or NULL for a Guest
	 * @return	string
	 */
	public function generate( ?Member $member=NULL ) : string
	{
		$member = $member ?: new Member;
		
		$search			= Query::init( $member );
		$filterByClass	= FALSE;

		if ( count( $this->configuration['classes'] ) )
		{
			$filterByClass	= TRUE;
			$filters		= array();

			foreach( $this->configuration['classes'] AS $class )
			{
				if( class_exists( $class ) )
				{
					if ( isset( $class::$firstCommentRequired ) AND $class::$firstCommentRequired )
					{
						$filter = ContentFilter::init( $class, TRUE, TRUE, FALSE )->onlyFirstComment();
					}
					else
					{
						$filter = ContentFilter::init( $class, TRUE, FALSE, FALSE );
					}
					
					if ( !empty( $this->configuration['containers'][$class] ) )
					{
						$filter->onlyInContainers( $this->configuration['containers'][$class] );
					}
					
					$filters[] = $filter;
				}
			}
			
			$search->filterByContent( $filters );
		}
		
		if ( isset( $this->configuration['members'] ) AND count( $this->configuration['members'] ) )
		{
			$search->filterByAuthor( $this->configuration['members'] );
		}
		
		$search->setLimit( $this->count );
		$search->setOrder( Query::ORDER_NEWEST_CREATED );
		
		if( $filterByClass AND !count( $filters ) )
		{
			$results = array();
		}
		else
		{
			$results = $search->search();
		}

		/* We have to use get() to ensure CDATA tags wrap the title properly */
		$title			= Member::loggedIn()->language()->get( "rss_export_title_{$this->_id}" );
		$description	= Member::loggedIn()->language()->get( "rss_export_title_{$this->_id}_desc" );
		
		$document = XmlRss::newDocument( $this->url(), $title, $description );
		
		foreach( $results AS $result )
		{
			$result->addToRssFeed( $document );
		}
		
		return $document->asXML();
	}

	/**
	 * Get all saved RSS feeds (enabled status must be checked in userland code)
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->rssFeeds ) )
		{
			Store::i()->rssFeeds = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, "rss_position ASC" )->setKeyField( 'rss_id' ) );
		}

		return Store::i()->rssFeeds;
	}
	
	/**
	 * Get URL
	 *
	 * @return	Url|string|null
	 */
	public function url(): Url|string|null
	{
		$url = parent::url();
		
		if ( Member::loggedIn()->member_id )
		{
			$url = $url->setQueryString( array( 'member_id' => Member::loggedIn()->member_id, 'key' => Member::loggedIn()->getUniqueMemberHash() ) );
		}
		
		return $url;
	}
}