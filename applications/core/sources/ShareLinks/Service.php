<?php
/**
 * @brief		Share Links Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Jun 2013
 */

namespace IPS\core\ShareLinks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Content;
use IPS\Content\ShareServices;
use IPS\Data\Store;
use IPS\Db;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Settings;
use LogicException;
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
 * Share Link Node
 */
class Service extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_share_links';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'share_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'share_key' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'sharelinks';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'settings',
		'prefix'	=> 'sharelinks_',
	);

	/**
	 * [Node] Reset our roots so they can be reloaded
	 *
	 * @return	void
	 */
	public static function resetRootResult() : void
	{
		static::$rootsResult	= NULL;
	}
	
	/**
	 * @brief	Cached sharelinks
	 */
	protected static ?array $cachedShareLinks = NULL;

	/**
	 * Fetch All Nodes
	 *
	 * @return	array
	 */
	public static function shareLinks() : array
	{
		if( static::$cachedShareLinks === NULL )
		{
			static::$cachedShareLinks = array();
			foreach ( static::getStore() as $service )
			{
				static::$cachedShareLinks[] = static::constructFromData( $service );
			}
		}
		
		return static::$cachedShareLinks;
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->shareLinks ) )
		{
			Store::i()->shareLinks = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, static::$databasePrefix . static::$databaseColumnOrder ) );
		}
		
		return Store::i()->shareLinks;
	}
	
	/**
	 * Fetch All Share Services
	 *
	 * @param Url $url	URL to the content [optional - if omitted, some services will figure out on their own]
	 * @param	string				$title	Default text for the content, usually the title [optional - if omitted, some services will figure out on their own]
	 * @param	Member|NULL	$member	Member the links will display to or NULL for currently logged in member
	 * @param	Content|NULL	$item	Content item (or comment) to share
	 * @return	array
	 */
	public static function getAllServices(Url $url, string $title, ?Member $member = NULL, ?Content $item = NULL ) : array
	{
		$member = $member ?: Member::loggedIn();

		if( Settings::i()->ref_on )
		{
			$url = $url->setQueryString( array( '_rid' => $member->member_id  ) );
		}

		$services = array();
		foreach( static::shareLinks() as $node )
		{
			if( $node->enabled and ( $node->groups === "*" or $member->inGroup( explode( ',', $node->groups ) ) ) )
			{
				try
				{
					$services[ $node->key ]	= $node->getService( $url, $title, $item );
				}
				catch ( LogicException $e ) { }
			}
		}
		return $services;
	}

	/**
	 * Get \IPS\Content\ShareServices class
	 *
	 * @param Url $url URL to the content [optional - if omitted, some services will figure out on their own]
	 * @param string $title Default text for the content, usually the title [optional - if omitted, some services will figure out on their own]
	 * @param Content|null $item
	 * @return    ShareServices
	 */
	public function getService( Url $url, string $title, ?Content $item ) : ShareServices
	{
		try
		{
			$className = ShareServices::getClassByKey( $this->key );

			return new $className( $url, $title, $item );
		}
		catch ( InvalidArgumentException $e )
		{
			throw new LogicException;
		}
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
		$buttons = array();
		
		if ( $subnode )
		{
			$url = $url->setQueryString( array( 'subnode' => 1 ) );
		}

		if( $this->canEdit() )
		{
			$buttons['edit'] = array(
				'icon'	=> 'pencil',
				'title'	=> 'edit',
				'link'	=> $url->setQueryString( array( 'do' => 'form', 'id' => $this->_id ) ),
				'data'	=> ( static::$modalForms ? array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') ) : array() ),
				'hotkey'=> 'e return'
				);
		}

		return $buttons;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Text( 'share_title', $this->title, TRUE ) );
		$form->add( new CheckboxSet( 'share_groups', ( $this->groups != '*' ) ? explode( ",", $this->groups ) : $this->groups, FALSE, array( 'options' => Group::groups(), 'parse' => 'normal', 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'everyone', 'impliedUnlimited' => TRUE ) ) );

		/* Find the service and see if it has any additional settings... */
		$services = ShareServices::services();
		$className	= $services[ ucwords( $this->key ) ];

		/* @var ShareServices $className */
		$className::modifyForm( $form, $this );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( count( $values ) )
		{
			if ( isset( $values[ 'share_autoshare_' . ucwords( $this->key ) ] ) )
			{
				$values['share_autoshare'] = $values[ 'share_autoshare_' . ucwords( $this->key ) ];
				unset( $values[ 'share_autoshare_' . ucwords( $this->key ) ] );
								
				$loginHandlers = Login::getStore();

				foreach( $loginHandlers as $handler )
				{
					$_key = mb_substr( $handler['login_classname'], 10 );

					if ( $_key == ucwords( $this->key ) )
					{
						$settings = $handler->settings;
						$settings['autoshare'] = $values['share_autoshare'];
						$handler->settings = json_encode( $settings );
						$handler->save();
					}
				}
			}
			
			$settingsToUpdate = array();
			foreach ( $values as $k => $v )
			{
				if( !in_array( $k, array( 'share_title', 'share_groups', 'share_autoshare' ) ) )
				{
					if ( $v instanceof GeoLocation )
					{
						$v = json_encode( $v );
					}
					if ( is_array( $v ) )
					{
						$v = implode( ',', $v );
					}
					
					$settingsToUpdate[ $k ] = $v;
					unset( $values[ $k ] );
				}
			}

			Settings::i()->changeValues( $settingsToUpdate );

			/* Remove prefix */
			$_values = $values;
			$values = array();
			foreach ( $_values as $k => $v )
			{
				if( mb_substr( $k, 0, 6 ) === 'share_' )
				{
					$values[ mb_substr( $k, 6 ) ] = $v;
				}
				else
				{
					$values[ $k ]	= $v;
				}
			}
		}

		return $values;
	}
	
	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		if ( !$this->id )
		{
			return '';
		}
		
		return $this->title;
	}
	
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		return (bool)$this->enabled;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( bool|int $enabled ) : void
	{
		$this->enabled	= $enabled;
	}

	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		return FALSE;
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
	public static function search( string $column, string $query, string $order=NULL, mixed $where=array() ): array
	{	
		if ( $column === '_title' )
		{
			$column = 'share_title';
		}
		if ( $order === '_title' )
		{
			$order = 'share_title';
		}
		return parent::search( $column, $query, $order, $where );
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'shareLinks' );
}