<?php
/**
 * @brief		Reaction Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Nov 2016
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Platform\Bridge;
use IPS\core\CustomBadge;
use IPS\Data\Store;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Settings;
use function count;
use function defined;
use function get_called_class;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Reaction Model
 */
class Reaction extends Model
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_reactions';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'reaction_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'reactions';
	
	/**
	 * @brief	[Node] Sortable
	 */
	public static bool $nodeSortable = TRUE;
	
	/**
	 * @brief	[Node] Positon Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Modal Forms because Charles loves them so
	 */
	public static bool $modalForms = TRUE;
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'reaction_title_';
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'enabled';

	/**
	 * @brief Icon Cache
	 */
	public static array $icons = array();

	/**
	 * Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ): void
	{
		$form->add( new Translatable( 'reaction_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? 'reaction_title_' . $this->id : NULL ) ) ) );
		$form->add( new Radio( 'reaction_value', $this->id ? $this->value : 1, TRUE, array( 'options' => array( 1 => 'positive', 0 => 'neutral', -1 => 'negative' ) ) ) );
		CustomBadge::getRecordBadge( $this )
			->addBadgeFieldsToForm( $form, (bool) $this->use_custom, ['reaction_icon'], includeNumberOverlay: false );
		$form->add( new Upload( 'reaction_icon', $this->id ? File::get( 'core_Reaction', (string) $this->icon ) : NULL, false, array( 'image' => TRUE, 'storageExtension' => 'core_Reaction', 'storageContainer' => 'reactions', 'obscure' => FALSE ), null, null, null, 'reaction_icon' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( !$this->id )
		{
			$this->save();
		}

		if ( array_key_exists( 'badge_use_custom', $values ) )
		{
			$values['use_custom'] = $values['badge_use_custom'];
		}

		CustomBadge::saveRecordBadge( $this, CustomBadge::generateBadgeFromFormValues( $values ) );
		
		Lang::saveCustom( 'core', 'reaction_title_' . $this->id, $values['reaction_title'] );
		unset( $values['reaction_title'] );
		
		return parent::formatFormValues( $values );
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		if ( $this->id === 1 OR Bridge::i()->liveTopicsUnconverted() )
		{
			return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public static function canAddRoot() : bool
	{
		/* When live topics are in session, prevent users from modifying the content */
		if ( Bridge::i()->liveTopicsUnconverted() )
		{
			return false;
		}
		return parent::canAddRoot();
	}

	/**
	 * @inheritdoc
	 */
	public function canAdd() : bool
	{
		if ( Bridge::i()->liveTopicsUnconverted() )
		{
			return false;
		}
		return parent::canAdd();
	}

	/**
	 * @inheritdoc
	 */
	public function canEdit() : bool
	{
		if ( Bridge::i()->liveTopicsUnconverted() )
		{
			return false;
		}
		return parent::canEdit();
	}

	/**
	 * @inheritdoc
	 */
	public function canManagePermissions() : bool
	{
		if ( Bridge::i()->liveTopicsUnconverted() )
		{
			return false;
		}
		return parent::canManagePermissions();
	}

	/**
	 * @inheritdoc
	 */
	public function canMassManageContent() : bool
	{
		if ( Bridge::i()->liveTopicsUnconverted() )
		{
			return false;
		}
		return parent::canMassManageContent();
	}

	/**
	 * @inheritdoc
	 */
	public function canCopy() : bool
	{
		if ( Bridge::i()->liveTopicsUnconverted() )
		{
			return false;
		}
		return parent::canCopy();
	}

	/**
	 * Get Icon
	 *
	 * @return	mixed
	 */
	public function get__icon(): mixed
	{
		if ( !isset( static::$icons[ $this->id ] ) )
		{
			static::$icons[ $this->id ] = $this->use_custom ? CustomBadge::getRecordBadge( $this )->url() : File::get( 'core_Reaction', $this->_data['icon'] );
		}

		return static::$icons[ $this->id ];
	}

	/**
	 * Get Description
	 *
	 * @return	string|null
	 */
	public function get__description(): ?string
	{
		if ( $this->value == 1 )
		{
			return Member::loggedIn()->language()->addToStack('positive');
		}
		elseif ( $this->value == -1 )
		{
			return Member::loggedIn()->language()->addToStack('negative');
		}
		else
		{
			return Member::loggedIn()->language()->addToStack('neutral');
		}
	}
	
	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	(static|Model)[]|static[]
	 */
	public static function roots( ?string $permissionCheck='view', Member|null $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		if ( !count( $where ) )
		{
			$cacheKey	= md5( get_called_class() . $permissionCheck );

			if( isset( static::$rootsResult[ $cacheKey ] ) )
			{
				return static::$rootsResult[ $cacheKey ];
			}

			static::$rootsResult[ $cacheKey ]	= array();
			foreach( static::getStore() AS $reaction )
			{
				static::$rootsResult[ $cacheKey ][ $reaction['reaction_id'] ] = static::constructFromData( $reaction );
			}
			
			return static::$rootsResult[ $cacheKey ];
		}
		else
		{
			return parent::roots( $permissionCheck, $member, $where, $limit );
		}
	}
	
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		if ( $this->id == 1 )
		{
			return NULL;
		}
		
		return parent::get__enabled();
	}

	/**
	 * Reaction Store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->reactions ) )
		{
			Store::i()->reactions = iterator_to_array( Db::i()->select( '*', 'core_reactions', NULL, "reaction_position ASC" )->setKeyField( 'reaction_id' ) );
		}
		
		return Store::i()->reactions;
	}
	
	/**
	 * Is Like Mode
	 *
	 * @return	bool
	 */
	public static function isLikeMode(): bool
	{
		return (bool) Settings::i()->reaction_is_likemode;
	}
	
	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you MUST define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = TRUE;

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'reactions' );

	/**
	 * Clear any defined caches
	 *
	 * @param	bool	$removeMultiton		Should the multiton record also be removed?
	 * @return void
	 */
	public function clearCaches( bool $removeMultiton=FALSE ): void
	{
		parent::clearCaches( $removeMultiton );

		static::updateLikeModeSetting();
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
		
		if ( $this->canDelete() )
		{
			$buttons['delete'] = array(
				'icon'	=> 'times-circle',
				'title'	=> 'delete',
				'link'	=> $url->setQueryString( array( 'do' => 'delete', 'id' => $this->_id ) ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('delete') ),
				'hotkey'=> 'd'
			);
		}
		return $buttons;
	}

	/**
	 * Update the setting which stores if likemode is enabled ( = only one reaction is being used )
	 *
	 * @return void
	 */
	public static function updateLikeModeSetting(): void
	{
		Settings::i()->changeValues( array( 'reaction_is_likemode' => intval( count( static::enabledReactions() ) == 1 ) ) );
	}

	/**
	 * Return enabled reactions
	 *
	 * @return array
	 */
	public static function enabledReactions(): array
	{
		return array_filter( 
			static::roots(), 
			function( $reaction ){
				return ( $reaction->_enabled !== FALSE );
			}
		);
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone(): void
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldIcon = $this->icon;

		parent::__clone();

			try
			{
				$icon = File::get( 'core_Reaction', $oldIcon );
				$newIcon = File::create( 'core_Reaction', $icon->originalFilename, $icon->contents() );
				$this->icon = (string) $newIcon;
			}

			catch ( Exception $e )
			{
				$this->icon = NULL;
			}

			$this->save();
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		try
		{
			File::get( 'core_Reaction', $this->icon )->delete();
		}
		catch( Exception $ex ) { }

		parent::delete();
	}
}