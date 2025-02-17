<?php
/**
 * @brief		Moderator Permissions: Content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jan 2014
 */

namespace IPS\core\extensions\core\ModeratorPermissions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Review;
use IPS\IPS;
use IPS\Application;
use IPS\Content\ExtensionGenerator;
use IPS\Platform\Bridge;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions: Content
 */
class ContentGenerator extends ExtensionGenerator
{
	/**
	 * @brief	If TRUE, will prevent comment classes being included
	 */
	protected static bool $contentItemsOnly = TRUE;
			
	/**
	 * Get Permissions
	 *
	 * @param	array	$toggles	Toggle data
	 * @code
	 	return array(
	 		'key'	=> 'YesNo',	// Can just return a string with type
	 		'key'	=> array(	// Or an array for more options
	 			'YesNo',			// Type
	 			array( ... ),		// Options (as defined by type's class)
	 			'prefix',			// Prefix
	 			'suffix',			// Suffix
	 		),
	 		...
	 	);
	 * @endcode
	 * @return	array
	 */
	public function getPermissions( array $toggles ): array
	{
		/* Init */
		/* @var Item $class */
		$class = $this->class;
		if ( !$class::$canBeModeratedFromFrontend )
		{
			return array();
		}
		$return = array();

		/* Node selector */
		if ( isset( $class::$containerNodeClass ) )
		{
			$containerNodeClass = $class::$containerNodeClass;
			
			if ( isset( $containerNodeClass::$modPerm ) )
			{
				$return[ $containerNodeClass::$modPerm ] = array( 'Node', array( 'class' => $containerNodeClass, 'zeroVal' => 'all', 'multiple' => TRUE, 'forceOwner' => FALSE ) );
			}
		}

		/* Content items */
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Pinnable' ))
		{
			$return[ "can_pin_{$class::$title}" ] = 'YesNo';
			$return[ "can_unpin_{$class::$title}" ] = 'YesNo';
		}
		$return[ "can_edit_{$class::$title}" ] = 'YesNo';
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
		{
			$return[ "can_hide_{$class::$title}" ] = 'YesNo';
			$return[ "can_unhide_{$class::$title}" ] = 'YesNo';
			$return[ "can_view_hidden_{$class::$title}" ] = 'YesNo';
		}
		if ( IPS::classUsesTrait( $class, 'IPS\Content\FuturePublishing' ) )
		{
			$return[ "can_future_publish_{$class::$title}" ] = 'YesNo';
			$return[ "can_view_future_{$class::$title}" ] = 'YesNo';
		}
		if ( isset( $class::$containerNodeClass ) )
		{
			$return[ "can_move_{$class::$title}" ] = 'YesNo';
		}
		if ( IPS::classUSesTrait( $class, 'IPS\Content\Lockable' ) )
		{
			$return[ "can_lock_{$class::$title}" ] = 'YesNo';
			$return[ "can_unlock_{$class::$title}" ] = 'YesNo';
			$return[ "can_reply_to_locked_{$class::$title}" ] = 'YesNo';
		}
		if( IPS::classUsesTrait( $class, 'IPS\Content\Assignable' ) and Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			$return["can_assign_{$class::$title}" ] = 'YesNo';
		}
		if ( IPS::classUsesTrait( $class, 'IPS\Content\MetaData' ) )
		{
			if ( in_array( 'core_FeaturedComments', $class::supportedMetaDataTypes() ) )
			{
				$return[ "can_feature_comments_{$class::$title}" ] = 'YesNo';
				$return[ "can_unfeature_comments_{$class::$title}" ] = 'YesNo';
			}
			
			if ( in_array( 'core_ContentMessages', $class::supportedMetaDataTypes() ) )
			{
				$return[ "can_add_item_message_{$class::$title}" ] = 'YesNo';
				$return[ "can_edit_item_message_{$class::$title}" ] = 'YesNo';
				$return[ "can_delete_item_message_{$class::$title}" ] = 'YesNo';
			}
			
			if ( in_array( 'core_ItemModeration', $class::supportedMetaDataTypes() ) )
			{
				$return[ "can_toggle_item_moderation_{$class::$title}" ] = 'YesNo';
			}
		}
		$return[ "can_delete_{$class::$title}" ] = 'YesNo';
		if ( $class::$firstCommentRequired )
		{
			$return[ "can_split_merge_{$class::$title}" ] = 'YesNo';
		}

		if( IPS::classUsesTrait( $class, 'IPS\Content\Reportable' ) )
		{
			$return[ "can_view_reports_{$class::$title}" ] = 'YesNo';
		}
		
		/* Comments */
		if ( isset( $class::$commentClass ) )
		{
			/* @var Comment $commentClass */
			$commentClass = $class::$commentClass;
			$return[ "can_edit_{$commentClass::$title}" ] = 'YesNo';
			if ( IPS::classUsesTrait( $class::$commentClass, 'IPS\Content\Hideable' ) )
			{
				$return[ "can_hide_{$commentClass::$title}" ] = 'YesNo';
				$return[ "can_unhide_{$commentClass::$title}" ] = 'YesNo';
				$return[ "can_view_hidden_{$commentClass::$title}" ] = 'YesNo';
			}
			$return[ "can_delete_{$commentClass::$title}" ] = 'YesNo';
		}
		
		/* Reviews */
		if ( isset( $class::$reviewClass ) )
		{
			/* @var Review $reviewClass */
			$reviewClass = $class::$reviewClass;
			$return[ "can_edit_{$reviewClass::$title}" ] = 'YesNo';
			if ( IPS::classUsesTrait( $class::$reviewClass, 'IPS\Content\Hideable' ) )
			{
				$return[ "can_hide_{$reviewClass::$title}" ] = 'YesNo';
				$return[ "can_unhide_{$reviewClass::$title}" ] = 'YesNo';
				$return[ "can_view_hidden_{$reviewClass::$title}" ] = 'YesNo';
			}
			$return[ "can_delete_{$reviewClass::$title}" ] = 'YesNo';
		}
		
		/* Other */
		foreach ( Application::load( $class::$application )->extensions( 'core', 'ModeratorPermissions' ) as $ext )
		{
			$return = array_merge( $return, $ext->getContentPermissions( $toggles ) );
		}
		
		/* Return */
		return $return;
	}
	
	/**
	 * After change
	 *
	 * @param	array	$moderator	The moderator
	 * @param	array	$changed	Values that were changed
	 * @return	void
	 */
	public function onChange( array $moderator, array $changed ) : void
	{
		/* @var Item $class */
		$class = $this->class;
		foreach ( Application::load( $class::$application )->extensions( 'core', 'ModeratorPermissions' ) as $ext )
		{
			$ext->onChange( $moderator, $changed );
		}
	}
	
	/**
	 * After delete
	 *
	 * @param	array	$moderator	The moderator
	 * @return	void
	 */
	public function onDelete( array $moderator ) : void
	{
		/* @var Item $class */
		$class = $this->class;
		foreach ( Application::load( $class::$application )->extensions( 'core', 'ModeratorPermissions' ) as $ext )
		{
			$ext->onContentDelete( $moderator );
		}
	}

	/**
	 * Pre save
	 *
	 * @param	array	$changed	Values that were changed
	 * @return	void
	 */
	public function preSave( array $changed ): void
	{
		/* @var Item $class */
		$class = $this->class;
		foreach ( Application::load( $class::$application )->extensions( 'core', 'ModeratorPermissions' ) as $ext )
		{
			$ext->preSave( $changed );
		}
	}
}