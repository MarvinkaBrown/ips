<?php
/**
 * @brief		Automatic Content Moderation Rules
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		7 Dec 2017
 */

namespace IPS\core\Reports;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Settings;
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
class Rules extends Model
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_automatic_moderation_rules';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'rule_';
	
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
	public static string $nodeTitle = 'automaticmoderation';
	
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
	public static ?string $titleLangPrefix = 'automaticmoderation_';
	
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
		$form->addHeader( 'automaticmoderation_generic_details' );
		$form->add( new Translatable( 'automaticmoderation_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? 'automaticmoderation_' . $this->id : NULL ) ) ) );
		$form->add( new YesNo( 'automaticmoderation_enabled', $this->id ? $this->enabled : 1, TRUE ) );
		$form->add( new Number( 'automaticmoderation_points', $this->id ? $this->points_needed : 10, TRUE, array( 'min' => 1, 'max' => 9999 ), NULL, NULL, Member::loggedIn()->language()->addToStack('automaticmoderation_points_suffix') ) );
		
		$options = array();
		if( count( Types::roots() ) > 0 )
		{
			foreach(Types::roots() as $type )
			{
				$options[ $type->id ] = $type->_title;
			}
			$form->add( new CheckboxSet( 'automaticmoderation_type', $this->types ? explode( ',', $this->types ) : array(), TRUE, array( 'options' => $options ) ) );
		}

		

		/* Loop over our member filters */
		$form->addHeader( 'automaticmoderation_generic_filters' );
		$options	= $this->id ? $this->_filters : array();

		$lastApp	= 'core';

		/* We take an extra step with groups to disable invalid options */
		//$options['core_Group']['disabled_groups']	= $this->getDisabledGroups();

		foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
		{
			if( $extension->availableIn( 'automatic_moderation' ) )
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
	}

	/**
	 * Return an array of groups that cannot be promoted
	 *
	 * @return array
	 */
	protected function getDisabledGroups() : array
	{
		$return = array( Settings::i()->guest_group );

		foreach( Group::groups() as $group )
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
		Lang::saveCustom( 'core', 'automaticmoderation_' . $this->id, $values['automaticmoderation_title'] );

		/* Json-encode the rules */
		$_options	= array();

		/* Loop over bulk mail extensions to format the options */
		foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
		{
			if( $extension->availableIn( 'automatic_moderation' ) )
			{
				/* Grab our fields and add to the form */
				$_value = $extension->save( $values );

				if( $_value )
				{
					$_options[ $key ]	= $_value;
				}
			}
		}

		$values['rule_filters'] = json_encode( $_options );
		$values['rule_points_needed'] = $values['automaticmoderation_points'];
		$values['rule_enabled']	= $values['automaticmoderation_enabled'];

		if( isset( $values['automaticmoderation_type'] ) )
		{
			$values['rule_types'] = $values['automaticmoderation_type'];
		}
		
		/* Now we have to remove any fields that aren't valid... */
		foreach( $values as $k => $v )
		{
			if( !in_array( $k, array( 'rule_filters', 'rule_points_needed', 'rule_enabled', 'rule_types', 'rule_position' ) ) )
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
	public function get__filters() : array
	{
		return json_decode( $this->filters, TRUE );
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
				$return[ $node['rule_id'] ] = static::constructFromData( $node );
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
	 * @note	Note that all records are returned, even disabled report rules. Enable status needs to be checked in userland code when appropriate.
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->automatic_moderation_rules ) )
		{
			Store::i()->automatic_moderation_rules = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, "rule_position ASC" )->setKeyField( 'rule_id' ) );
		}
		
		return Store::i()->automatic_moderation_rules;
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
	protected array $caches = array( 'automatic_moderation_rules' );

	/**
	 * @brief	Cache extensions so we only need to load them once
	 */
	protected static ?array $extensions = NULL;

	/**
	 * Check if a member matches the rule
	 *
	 * @param	Member		$member			Member to check
	 * @param	array			$typeCounts		Array of type counts indexed by report_type (constants in \IPS\core\Reports\Report)
	 * @return	bool
	 */
	public function matches( Member $member, array $typeCounts=array() ) : bool
	{
		if ( ! Settings::i()->automoderation_enabled )
		{
			return FALSE;
		}
		
		if( static::$extensions === NULL )
		{
			static::$extensions = Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' );
		}
		
		/* Nothing flagged as bad? Back you go */
		if ( ! count( $typeCounts ) )
		{
			return FALSE;
		}
		
		/* Author can bypass? */
		if ( $member->group['gbw_immune_auto_mod'] )
		{
			return FALSE;
		}
		
		/* Match the count threshold */
		$count = 0;
		foreach( explode( ',', $this->types ) as $type )
		{
			if ( isset( $typeCounts[ $type ] ) )
			{
				$count += $typeCounts[ $type ];
			}
		}
		
		if ( $count < $this->points_needed )
		{
			/* You lose */
			return FALSE;
		}
		
		/* Loop over the filters */
		foreach( $this->_filters as $key => $filter )
		{
			if( isset( static::$extensions[ $key ] ) AND method_exists( static::$extensions[ $key ], 'matches' ) )
			{
				/* Ask the extension if this member matches the defined rule...if not, just return FALSE now */
				if( !static::$extensions[ $key ]->matches( $member, $filter, $this ) )
				{
					return FALSE;
				}
			}
		}

		/* If we are still here, then the rule matched */
		return TRUE;
	}

	/* ! ACP STUFF */
	
	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		return array(
			0 => 'ipsBadge ipsBadge--positive',
			1 => Member::loggedIn()->language()->addToStack( 'automoderation_points_needed_badge', FALSE, array( 'pluralize' => array( $this->points_needed	) ) )
		);
	}
	
	/**
	 * Return a warning if this promotion uses not existing groups
	 *
	 * @return	string|null
	 */
	public function get__description(): ?string
	{
		if( static::$extensions === NULL )
		{
			static::$extensions = Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' );
		}

		/* Loop over the filters */
		$descriptions = array();
		if( $this->_filters )
		{
			foreach( $this->_filters as $key => $filter )
			{
				if( isset( static::$extensions[ $key ] ) )
				/* Ask the extension if this member matches the defined rule...if not, just return FALSE now */
				if( method_exists( static::$extensions[ $key ], 'getDescription' ) and $description = static::$extensions[ $key ]->getDescription( $filter ) )
				{
					$descriptions[] = $description; /* Is this descriptive enough? */
				}
			}
		}
		
		return Member::loggedIn()->language()->addToStack( 'automaticmoderation_row_desc', TRUE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $descriptions ) ) ) );
	}

	/**
	 * Returns the hide reason language string
	 *
	 * @param	null|int	$languageId	Language ID (or NULL to use default language)
	 * @return	string
	 */
	public static function getDefaultHideReason( ?int $languageId = NULL ) : string
	{
		$languageId = $languageId ? : Lang::defaultLanguage() ;
		return Lang::load( $languageId )->get( 'automaticmoderation_hide_reason' );
	}
}