<?php
/**
 * @brief		Tags trait for Content Models
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Nov 2013
 */

namespace IPS\Content;

use DomainException;
use IPS\Content\Search\SearchContent;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use IPS\Db;
use IPS\Content\Search\Index;
use IPS\Helpers\Form\Text;
use IPS\IPS;

use OutOfRangeException;
use function defined;
use function header;
use function get_called_class;
use function rawurlencode;
use function is_array;
use function time;
use function json_encode;
use function md5;

/* To prevent PHP errors (extending class does not exist) revealing path */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Tags trait for Content Models
 *
 * @note	Content classes will gain special functionality by implementing this interface
 */
trait Taggable
{
	/**
	 * Can tag?
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	bool
	 */
	public static function canTag( ?Member $member = NULL, ?Model $container = NULL ): bool
	{
		/* Commenting this out until we decide whether we are allowing
		nodes to have tags enabled/disabled */
		/*if( $container and !$container->checkAction( 'tags' ) )
		{
			return FALSE;
		}*/

		/* Are tags enabled? */
		if( !Settings::i()->tags_enabled )
		{
			return false;
		}

		/* Do we have at least one tag set up? */
		if( !count( Tag::getStore() ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();
		return !( $member->group['gbw_disable_tagging'] ) and !( $member->members_bitoptions['bw_disable_tagging'] );
	}
	
	/**
	 * Can use prefixes?
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	bool
	 */
	public static function canPrefix( ?Member $member = NULL, ?Model $container = NULL ): bool
	{
		/* Commenting this out until we decide whether we are allowing
		nodes to have tags enabled/disabled */
		/*if( $container and !$container->checkAction( 'prefix' ) )
		{
			return FALSE;
		}*/

		$member = $member ?: Member::loggedIn();
		return Settings::i()->tags_enabled and Settings::i()->tags_can_prefix and !( $member->group['gbw_disable_tagging'] ) and !( $member->group['gbw_disable_prefixes'] ) and !( $member->members_bitoptions['bw_disable_tagging'] ) and !( $member->members_bitoptions['bw_disable_prefixes'] );
	}
	
	/**
	 * Defined Tags
	 *
	 * @return	array
	 */
	public static function definedTags(): array
	{
		return array_values( Tag::getStore() );
	}
	
	/**
	 * @brief	Tags cache
	 */
	protected ?array $tags = NULL;
	
	/**
	 * Get prefix
	 *
	 * @param	bool		$encode	Encode returned value
	 * @return	string|NULL
	 */
	public function prefix( bool $encode=FALSE ): string|NULL
	{
		if ( $this->tags === NULL )
		{
			$this->tags();
		}
								
		return isset( $this->tags['prefix'] ) ? ( ( $encode ) ? rawurlencode( $this->tags['prefix'] ) : $this->tags['prefix'] ) : NULL;
	}
	
	/**
	 * Get tags
	 *
	 * @return	array
	 */
	public function tags(): array
	{
		if ( Settings::i()->tags_enabled )
		{
			if ( $this->tags === NULL )
			{
				$idColumn = static::$databaseColumnId;
				$this->tags = array( 'tags' => array(), 'prefix' => NULL );
				$tagList = Tag::getStore();
				array_walk( $tagList, function( &$value, $key ) use ( &$tagList ) {
					$tagList[ $key ] = strtolower( $value );
				} );

				foreach ( Db::i()->select( '*', 'core_tags', array( 'tag_meta_app=? AND tag_meta_area=? AND tag_meta_id=?', static::$application, static::$module, $this->$idColumn ) ) as $tag )
				{
					if ( $tag['tag_prefix'] )
					{
						$this->tags['prefix'] = $tag['tag_text'];
					}
					/* Sanity check in case the tag is pending deletion */
					elseif( in_array( strtolower( $tag['tag_text'] ), $tagList ) )
					{
						$this->tags['tags'][] = $tag['tag_text'];
					}
				}
			}

			return ($this->tags['tags'] ?? []);
		}
		else
		{
			return [];
		}
	}
	
	/**
	 * Set tags
	 *
	 * @param	array|string				$set	The tags (if one has the key "prefix", it will be set as the prefix)
	 * @param	Member|NULL	$member	The member saving the tags, or NULL for currently logged in member
	 * @return	void
	 */
	public function setTags( array|string $set, ?Member $member=NULL ): void
	{
		if( $member === NULL )
		{
			$member = Member::loggedIn();
		}

		/* Grab the current tags so that we can update those later */
		$currentTags = array( 'tags' => $this->tags(), 'prefix' => $this->prefix() );

		$aaiLookup = $this->tagAAIKey();
		$aapLookup = $this->tagAAPKey();
		$idColumn = static::$databaseColumnId;
		$this->tags = array( 'tags' => array(), 'prefix' => NULL );
		
		Db::i()->delete( 'core_tags', array( 'tag_aai_lookup=?', $aaiLookup ) );

		if ( !is_array( $set ) )
		{
			$set = array( $set );
		}

		$insert = [];
		foreach ( $set as $key => $tag )
		{
			$insert[] = array(
				'tag_aai_lookup'		=> $aaiLookup,
				'tag_aap_lookup'		=> $aapLookup,
				'tag_meta_app'			=> static::$application,
				'tag_meta_area'			=> static::$module,
				'tag_meta_id'			=> $this->$idColumn,
				'tag_meta_parent_id'	=> ( isset( static::$containerNodeClass ) ? $this->container()->_id : 0 ),
				'tag_member_id'			=> $member->member_id ?: 0,
				'tag_added'				=> time(),
				'tag_prefix'			=> $key == 'prefix',
				'tag_text'				=> $tag
			);
			
			if ( $key == 'prefix' )
			{
				$this->tags['prefix'] = $tag;
			}
			else
			{
				$this->tags['tags'][] = $tag;
			}
		}

		if( count( $insert ) )
		{
			Db::i()->insert( 'core_tags', $insert, true );
		}
					
		Db::i()->insert( 'core_tags_cache', array(
			'tag_cache_key'		=> $aaiLookup,
			'tag_cache_text'	=> json_encode( array( 'tags' => $this->tags['tags'], 'prefix' => $this->tags['prefix'] ) ),
			'tag_cache_date'	=> time()
		), TRUE );

		if( isset( static::$containerNodeClass ) )
		{
			$containerClass = static::$containerNodeClass;
			if ( isset( $containerClass::$permissionMap['read'] ) )
			{
				$permissions = $containerClass::load( $this->container()->_id )->permissions();

				if ( isset( $permissions[ 'perm_' . $containerClass::$permissionMap['read'] ] ) )
				{
					Db::i()->insert( 'core_tags_perms', array(
						'tag_perm_aai_lookup'		=> $aaiLookup,
						'tag_perm_aap_lookup'		=> $aapLookup,
						'tag_perm_text'				=> $permissions[ 'perm_' . $containerClass::$permissionMap['read'] ],
						'tag_perm_visible'			=> ( $this->hidden() OR ( IPS::classUsesTrait( $this, 'FuturePublishing' ) AND $this->isFutureDate() ) ) ? 0 : 1,
					), TRUE );
				}
			}
		}

		/* Update the tag totals for the new and old tags */
		Tag::updateTagTotals( $this, $currentTags );

		/* Callback once tags are updated */
		$this->processAfterTagUpdate();
	}
	
	/**
	 * Get tag AAI key
	 *
	 * @return	string
	 */
	public function tagAAIKey(): string
	{
		$idColumn = static::$databaseColumnId;
		return md5( static::$application . ';' . static::$module . ';' . $this->$idColumn );
	}
	
	/**
	 * Get tag AAP key
	 *
	 * @return	string
	 */
	public function tagAAPKey(): string
	{
		if( isset( static::$containerNodeClass ) )
		{
			$containerClass = static::$containerNodeClass;
			return md5( $containerClass::$permApp . ';' . $containerClass::$permType . ';' . $this->container()->_id );
		}

		return md5( static::$application . ';' . static::$module );
	}
	
	/**
	 * Generate the tags form element
	 *
	 * @note	It is up to the calling code to verify the tag input field should be shown
	 * @param Item|null $item		Item, if editing
	 * @param string|Model|null $container	Container
	 * @param bool $minimized	If the form field should be minimized by default
	 * @return	Text|NULL
	 */
	public static function tagsFormField( ?Item $item, Model|string|null $container = null, bool $minimized = FALSE ): Text|NULL
	{
		/* Include existing tags in case we're editing */
		if ( $item )
		{
			$source = $item->prefix() ? array_merge( array( 'prefix' => $item->prefix() ), $item->tags() ) : $item->tags();
		}
		else
		{
			$source = array();
		}

		/* And get the defined tags */
		if( static::definedTags() )
		{
			$source = array_unique( array_merge( $source, static::definedTags() ) );
		}

		$options = array(
			'autocomplete' => array(
				'unique' => TRUE,
				'source' => $source,
				'resultItemTemplate' => 'core.autocomplete.tagsResultItem',
				'freeChoice' => false,
				'minimized' => $minimized,
				'addTokenText' => Member::loggedIn()->language()->get( 'tags_optional' )
			) );

		if ( Settings::i()->tags_force_lower )
		{
			$options['autocomplete']['forceLower'] = TRUE;
		}
		if ( Settings::i()->tags_min )
		{
			$options['autocomplete']['minItems'] = Settings::i()->tags_min;
		}
		if ( Settings::i()->tags_max )
		{
			$options['autocomplete']['maxItems'] = Settings::i()->tags_max;
		}

		$options['autocomplete']['prefix'] = static::canPrefix( NULL, $container );
		$options['autocomplete']['disallowedCharacters'] = array( '#' ); // @todo Pending \IPS\Http\Url rework, hashes cannot be used in URLs
					
		if ( count( $options['autocomplete']['source'] ) )
		{
			$containerClass = static::$containerNodeClass;
			$containerFieldName = static::$formLangPrefix . 'container';
			$thisClass = get_called_class();
			return new Text( static::$formLangPrefix . 'tags', $item ? ( $item->prefix() ? array_merge( array( 'prefix' => $item->prefix() ), $item->tags() ) : $item->tags() ) : array(), ( Settings::i()->tags_min and Settings::i()->tags_min_req ) ? ( $container ? TRUE : NULL ) : FALSE, $options, function ( $val ) use ( $container, $containerClass, $containerFieldName, $thisClass ) {
				if ( empty( $val ) and Settings::i()->tags_min and Settings::i()->tags_min_req )
				{
					if ( !$container )
					{
						if( $containerClass )
						{
							try
							{
								$container = $containerClass::load( Request::i()->$containerFieldName );
							}
							catch ( OutOfRangeException )
							{
								return TRUE;
							}
						}

						/* @var Item $thisClass */
						if ( $thisClass::canTag( NULL, $container ) )
						{
							throw new DomainException('form_required');
						}
					}
				}
				return TRUE;
			} );
		}

		return NULL;
	}
	
	/**
	 * Callback to execute when tags are edited
	 *
	 * @return	void
	 */
	public function processAfterTagUpdate(): void
	{
		if( IPS::classUsesTrait( $this, 'IPS\Content\ItemTopic' ) )
		{
			$this->itemTagsUpdated();
		}
	}
}