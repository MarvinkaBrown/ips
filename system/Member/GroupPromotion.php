<?php
/**
 * @brief		Group promotion node model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		4 Apr 2017
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Settings;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group promotion node model
 */
class GroupPromotion extends Model
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_group_promotions';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'promote_';
	
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
	public static string $nodeTitle = 'grouppromotions';
	
	/**
	 * @brief	[Node] Sortable
	 */
	public static bool $nodeSortable = TRUE;
	
	/**
	 * @brief	[Node] Positon Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'g_promotion_';
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'enabled';
	
	/**
	 * Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->addHeader( 'generic_gp_details' );
		$form->add( new Translatable( 'promote_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? 'g_promotion_' . $this->id : NULL ) ) ) );
		$form->add( new YesNo( 'promote_enabled', $this->id ? $this->enabled : 1, TRUE ) );

		/* Loop over our member filters */
		$form->addHeader( 'generic_gp_filters' );
		$options	= $this->id ? $this->_filters : array();

		$lastApp	= 'core';

		/* We take an extra step with groups to disable invalid options */
		$options['core_Group']['disabled_groups']	= $this->getDisabledGroups();

		foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
		{
			if( $extension->availableIn( 'group_promotions' ) )
			{
				/* See if we need a new form header - one per app */
				$_key		= explode( '_', $key );

				if( $_key[0] != $lastApp )
				{
					$lastApp	= $_key[0];
					$form->addHeader( $lastApp . '_bm_filters' );
				}

				/* Grab our fields and add to the form */
				$fields		= $extension->getSettingField( !empty( $options[ $key ] ) ? $options[ $key ] : array() );

				foreach( $fields as $field )
				{
					$form->add( $field );
				}
			}
		}

		$form->addHeader( 'generic_gp_actions' );
		$groups		= array_combine( array_keys( Group::groups( TRUE, FALSE ) ), array_map( function($_group ) { return (string) $_group; }, Group::groups( TRUE, FALSE ) ) );
		$primary	= array( 0 => 'do_not_change_group' ) + $groups;

		/* And then allow the admin to choose which groups to promote to */
		$form->add( new Radio( 'promote_group_primary', $this->id ? $this->_actions['primary_group'] : 0, FALSE,
							array( 'options' => $primary ) ) );

		$form->add( new CheckboxSet( 'promote_group_secondary', $this->id ? $this->_actions['secondary_group'] : array(), FALSE,
							array( 'options' => $groups, 'multiple' => true ) ) );

		$form->add( new CheckboxSet( 'demote_group_secondary', $this->id ? $this->_actions['secondary_remove'] : array(), FALSE,
							array( 'options' => $groups, 'multiple' => true ) ) );
	}

	/**
	 * Return an array of groups that cannot be promoted
	 *
	 * @return array
	 */
	protected function getDisabledGroups(): array
	{
		$return = array( Settings::i()->guest_group );

		foreach(Group::groups() as $group )
		{
			if( $group->g_promote_exclude )
			{
				$return[] = $group->g_id;
			}
		}

		return $return;
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
		
		/* Save the title */
		Lang::saveCustom( 'core', 'g_promotion_' . $this->id, $values['promote_title'] );

		/* Json-encode the rules */
		$_options	= array();

		/* Loop over bulk mail extensions to format the options */
		foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
		{
			if( $extension->availableIn( 'group_promotions' ) )
			{
				/* Grab our fields and add to the form */
				$_value		= $extension->save( $values );

				if( $_value )
				{
					$_options[ $key ]	= $_value;
				}
			}
		}

		$values['promote_filters'] = json_encode( $_options );

		/* Json-encode the actions */
		$values['promote_actions'] = json_encode( array( 
			'primary_group'		=> $values['promote_group_primary'],
			'secondary_group'	=> $values['promote_group_secondary'],
			'secondary_remove'	=> $values['demote_group_secondary']
		) );
		
		/* Now we have to remove any fields that aren't valid... */
		foreach( $values as $k => $v )
		{
			if( !in_array( $k, array( 'promote_enabled', 'promote_filters', 'promote_actions', 'promote_position' ) ) )
			{
				unset( $values[ $k ] );
			}
		}

		return parent::formatFormValues( $values );
	}

	/**
	 * Return our filters as an array
	 *
	 * @return array
	 */
	public function get__filters(): array
	{
		return json_decode( $this->filters, TRUE );
	}

	/**
	 * Return our actions as an array
	 *
	 * @return array
	 */
	public function get__actions(): array
	{
		return json_decode( $this->actions, TRUE );
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
		if ( !count( $where ) )
		{
			$return = array();
			foreach( static::getStore() AS $node )
			{
				$return[ $node['promote_id'] ] = static::constructFromData( $node );
			}
			
			return $return;
		}
		else
		{
			return parent::roots( $permissionCheck, $member, $where, $limit );
		}
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 * @note	Note that all records are returned, even disabled promotion rules. Enable status needs to be checked in userland code when appropriate.
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->group_promotions ) )
		{
			Store::i()->group_promotions = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, "promote_position ASC" )->setKeyField( 'promote_id' ) );
		}
		
		return Store::i()->group_promotions;
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'group_promotions' );

	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you MUST define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = TRUE;

	/**
	 * @brief	Cache extensions so we only need to load them once
	 */
	protected static ?array $extensions = NULL;

	/**
	 * @brief	Flag to indicate whether or not to check secondary groups
	 */
	public bool $memberFilterCheckSecondaryGroups	= FALSE;

	/**
	 * Check if a member matches the rule
	 *
	 * @param	Member		$member	Member to check
	 * @return	bool
	 */
	public function matches( Member $member ): bool
	{
		if( static::$extensions === NULL )
		{
			static::$extensions = Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' );
		}

		/* Did we check any matches? - It's possible that some rules are available */
		$matchMethodExists = FALSE;

		/* Loop over the filters */
		foreach( $this->_filters as $key => $filter )
		{
			if( isset( static::$extensions[ $key ] ) AND static::$extensions[ $key ]->availableIn( 'group_promotions' ) AND method_exists( static::$extensions[ $key ], 'matches' ) )
			{
				/* Yes the rule had a match method */
				$matchMethodExists = TRUE;

				/* Ask the extension if this member matches the defined rule...if not, just return FALSE now */
				if( !static::$extensions[ $key ]->matches( $member, $filter, $this ) )
				{
					return FALSE;
				}
			}
		}

		/* If we are still here, then the rule matched unless we didn't find any matches methods */
		return $matchMethodExists;
	}

	/**
	 * Return a warning if this promotion uses not existing groups
	 *
	 * @return	string|NULL
	 */
	public function get__description(): ?string
	{
		$action = $this->_actions;
		$showWarning = FALSE;

		if ( $action['primary_group'] )
		{
			try
			{
				$group = Group::load( $action['primary_group'] );
			}
			catch ( OutOfRangeException $e )
			{
				$showWarning = TRUE;
			}
		}
		if ( count( $action['secondary_group'] )  )
		{
			foreach ( $action['secondary_group'] as $key => $group )
			{
				try
				{
					$group = Group::load( $group );
				}
				catch ( OutOfRangeException $e )
				{
					$showWarning = TRUE;
				}
			}
		}

		if ( $showWarning )
		{
			return Member::loggedIn()->language()->addToStack( 'grouppromotion_warning' );
		}

		return NULL;
	}
}