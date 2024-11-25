<?php

/**
 * @brief        Tag
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        3/29/2024
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function get_class;
use function in_array;
use function is_array;
use function count;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Tag extends Model
{
	use Followable;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_tags_data';

	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'tag_';

	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 * @note	If using this, declare a static $multitonMap = array(); in the child class to prevent duplicate loading queries
	 */
	protected static array $databaseIdFields = array( 'tag_text', 'tag_text_seo' );

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'tags';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'tags_tag_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';

	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'enabled';

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'text_seo';

	/**
	 * @brief	Cover Photo Storage Extension
	 */
	public static string $coverPhotoStorageExtension = 'core_Tags';

	/**
	 * @brief	Use a default cover photo
	 */
	public static bool $coverPhotoDefault = true;

	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'cover_photo'			=> 'cover_photo',
		'cover_photo_offset'	=> 'cover_offset'
	);

	/**
	 * @brief	Application
	 */
	public static string $application = 'core';

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		$totalItems = array_sum( $this->totals );
		$text = Member::loggedIn()->language()->addToStack( 'tag_total_items', true, [ 'pluralize' => [ $totalItems ] ] );
		Member::loggedIn()->language()->parseOutputForDisplay( $text );

		return [
			0 => ( $totalItems ? 'ipsBadge ipsBadge--positive' : 'ipsBadge ipsBadge--neutral' ),
			2 => $text
		];
	}

	/**
	 * [Node] Get Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		if( Member::loggedIn()->language()->checkKeyExists( static::$titleLangPrefix . $this->_id ) )
		{
			return Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->_id, NULL, array( 'escape' => TRUE ) );
		}

		return '';
	}

	/**
	 * [Node] Get content table description
	 *
	 * @return	string|null
	 */
	protected function get_description(): ?string
	{
		if( Member::loggedIn()->language()->checkKeyExists( static::$titleLangPrefix . $this->id . static::$descriptionLangSuffix ) )
		{
			return Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->id . static::$descriptionLangSuffix );
		}
		return NULL;
	}

	/**
	 * @return array
	 */
	public function get_totals() : array
	{
		return isset( $this->_data['totals'] ) ? json_decode( $this->_data['totals'], true ) : array();
	}

	/**
	 * @param array|null $val
	 * @return void
	 */
	public function set_totals( ?array $val ) : void
	{
		$this->_data['totals'] = ( is_array( $val ) and count( $val ) ) ? json_encode( $val ) : null;
	}

	/**
	 * Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ): void
	{
		$form->add( new Text( 'tag_text', $this->text, true, [], function( $val ){
			try
			{
				$test = Db::i()->select( '*', static::$databaseTable, [ 'tag_text=? and tag_id <> ?', $val, (int) $this->id ] )->first();
				throw new InvalidArgumentException( 'err__tag_duplicate' );
			}
			catch( UnderflowException ){}
		} ) );

		$form->add( new Text( 'tag_text_seo', $this->text_seo, false, array(), function( $val ){
			if( empty( $val ) )
			{
				$val = Friendly::seoTitle( Request::i()->tag_text );
			}

			try
			{
				$test = Db::i()->select( '*', static::$databaseTable, [ 'tag_text_seo=? and tag_id <> ?', $val, (int) $this->id ] )->first();
				throw new InvalidArgumentException( 'err__tag_duplicate' );
			}
			catch( UnderflowException ){}
		} ) );

		$form->add( new Translatable( 'tag_headline', null, false, [
			'app' => 'core',
			'key' => ( $this->id ? static::$titleLangPrefix . $this->id : null )
		] ) );

		$form->add( new Translatable( 'tag_description', null, false, [
			'app' => 'core',
			'key' => ( $this->id ? static::$titleLangPrefix . $this->id . static::$descriptionLangSuffix : null ),
			'textArea' => [
				'rows' => 5
			]
		] ) );

		$form->add( new Upload( 'tag_cover_photo', $this->coverPhotoFile(), false, [
			'storageExtension' => 'core_Tags',
			'image' => true,
			'multiple' => false,
			'allowStockPhotos' => true
		] ) );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( !$this->id )
		{
			/* Enable by default */
			$values['tag_enabled'] = true;

			$this->text = $values['tag_text'];
			$this->save();
		}
		/* Do we need to update the tag name? */
		elseif( $this->text != $values['tag_text'] )
		{
			Task::queue( 'core', 'UpdateTaggedItems', [ 'tag' => $this->text, 'new' => $values['tag_text'] ], 5, [ 'tag' ] );
		}

		$values['tag_text_seo'] = $values['tag_text_seo'] ?: Friendly::seoTitle( $values['tag_text'] );
		$values['tag_cover_photo'] = $values['tag_cover_photo'] instanceof File ? (string) $values['tag_cover_photo'] : null;

		Lang::saveCustom( 'core', static::$titleLangPrefix . $this->id, $values['tag_headline'] );
		Lang::saveCustom( 'core', static::$titleLangPrefix . $this->id . static::$descriptionLangSuffix, $values['tag_description'] );
		unset( $values['tag_headline'] );
		unset( $values['tag_description'] );

		return $values;
	}

	/**
	 * Recount the totals for this content type all affected tags
	 *
	 * @param Item $item
	 * @param array $oldTags
	 * @return void
	 */
	public static function updateTagTotals( Item $item, array $oldTags ) : void
	{
		if( !IPS::classUsesTrait( $item, Taggable::class ) )
		{
			return;
		}

		$newTags = array_diff( $item->tags(), $oldTags['tags'] );
		$removedTags = array_diff( $oldTags['tags'], $item->tags() );
		$tagsToUpdate = array_merge( $newTags, $removedTags );

		if( $oldTags['prefix'] and $oldTags['prefix'] != $item->prefix() )
		{
			if( !in_array( $oldTags['prefix'], $tagsToUpdate ) )
			{
				$tagsToUpdate[] = $oldTags['prefix'];
			}
			if( $prefix = $item->prefix() )
			{
				if( !in_array( $prefix, $tagsToUpdate ) )
				{
					$tagsToUpdate[] = $prefix;
				}
			}
		}

		$itemType = $item::$application . '_' . $item::$module;
		foreach( $tagsToUpdate as $tag )
		{
			try
			{
				static::load( $tag, 'tag_text' )->recount();
			}
			catch( OutOfRangeException ){}
		}

		/* Clean-up anything pinned in removed tags */
		if( count( $removedTags ) )
		{
			foreach( $removedTags as $tag )
			{
				try
				{
					static::load( $tag, 'tag_text' )->removePinnedItem( $item );
				}
				catch( OutOfRangeException ){}
			}
		}
	}

	/**
	 * Rebuild the statistics for the tag
	 *
	 * @return void
	 */
	public function recount() : void
	{
		$totals = [];
		$this->last_used = null;
		foreach( Db::i()->select( 'tag_meta_app, tag_meta_area, count(tag_meta_id) as total, max(tag_added) as tag_added', 'core_tags', [ 'tag_text=?', $this->text ], null, null, [ 'tag_meta_app', 'tag_meta_area' ] ) as $row )
		{
			$type = $row['tag_meta_app'] . '_' . $row['tag_meta_area'];
			$totals[ $type ] = $row['total'];

			if( $this->last_used === null or $this->last_used < $row['tag_added'] )
			{
				$this->last_used = $row['tag_added'];
			}
		}

		$this->totals = $totals;
		$this->save();
	}

	/**
	 * Return all enabled tags as SystemTag objects
	 *
	 * @return array
	 */
	public static function allEnabledTagsAsObjects() : array
	{
		$tags = [];
		foreach( Db::i()->select( '*', static::$databaseTable, [ 'tag_enabled=?', 1 ] ) as $tag )
		{
			$tag = static::constructFromData( $tag );
			$tags[ $tag->text_seo ] = $tag;
		}

		return $tags;
	}
	/**
	 * Return all tags, even if disabled.
	 * Primarily used for the search screen.
	 *
	 * @return array
	 */
	public static function allTags() : array
	{
		return iterator_to_array(
			Db::i()->select( 'tag_text', static::$databaseTable, null, 'tag_text' )
		);
	}

	/**
	 * Return all active content types that use tags
	 *
	 * @return array
	 */
	public static function getTaggableContentTypes() : array
	{
		$return = [];
		foreach( Content::routedClasses( Member::loggedIn(), false, true ) as $class )
		{
			if( IPS::classUsesTrait( $class, Taggable::class ) )
			{
				$return[ $class::$title ] = $class;
			}
		}
		return $return;
	}

	/**
	 * Max amount of pinned items that will show on a tag page
	 * because sometimes people can be ridiculous
	 */
	const TAG_PINNED_LIMIT = 20;

	/**
	 * @var array|null
	 */
	protected ?array $_pinnedItems = null;

	/**
	 * Return the content item pinned to the top of the tag page
	 *
	 * @param int|null $limit
	 * @return array|null
	 */
	public function getPinnedItems( ?int $limit=null ) : ?array
	{
		if( $this->_pinnedItems === null )
		{
			$items = [];
			foreach( Db::i()->select( '*', 'core_tags_pinned', [ 'pinned_tag_id=?', $this->id ], 'rand()', ( $limit ?? static::TAG_PINNED_LIMIT ) ) as $row )
			{
				$itemClass = $row['pinned_item_class'];
				if( !Application::appIsEnabled( $itemClass::$application ) )
				{
					continue;
				}

				try
				{
					$item = $itemClass::load( $row['pinned_item_id'] );

					$item->pinnedData = $row;
					$items[] = $item;
				}
				catch( OutOfRangeException ){}
			}

			$this->_pinnedItems = $items;
		}

		return count( $this->_pinnedItems ) ? $this->_pinnedItems : null;
	}

	/**
	 * Pin an item to the top of the tag page
	 *
	 * @param Item $item
	 * @param DateTime|null $endDate
	 * @param File|null $image
	 * @return void
	 */
	public function pinItem( Item $item, ?DateTime $endDate=null, ?File $image=null ) : void
	{
		$idColumn = $item::$databaseColumnId;

		try
		{
			$test = Db::i()->select( '*', 'core_tags_pinned', [ 'pinned_tag_id=? and pinned_item_class=? and pinned_item_id=?', $this->id, get_class( $item ), $item->$idColumn ] )->first();

		}
		catch( UnderflowException )
		{
			Db::i()->insert( 'core_tags_pinned',[
				'pinned_tag_id' => $this->id,
				'pinned_item_class' => get_class( $item ),
				'pinned_item_id' => $item->$idColumn,
				'pinned_date' => time(),
				'pinned_end_date' => $endDate?->getTimestamp(),
				'pinned_image' => $image ? (string) $image : ''
			] );
		}
	}

	/**
	 * Remove pinned item from the top of the tag page
	 *
	 * @param Item $item
	 * @return void
	 */
	public function removePinnedItem( Item $item ) : void
	{
		$idColumn = $item::$databaseColumnId;

		/* Delete the image, if there is one */
		try
		{
			$image = Db::i()->select( 'pinned_image', 'core_tags_pinned', [ 'pinned_tag_id=? and pinned_item_class=? and pinned_item_id=?', $this->id, get_class( $item ), $item->$idColumn ] )->first();
			if( $image )
			{
				File::get( 'core_Tags', $image )->delete();
			}
		}
		catch( UnderflowException ){}

		Db::i()->delete( 'core_tags_pinned', [ 'pinned_tag_id=? and pinned_item_class=? and pinned_item_id=?', $this->id, get_class( $item ), $item->$idColumn ] );
	}

	/**
	 * Use the data store to find the correct slug for the tag
	 *
	 * @param string $tagText
	 * @return Url
	 */
	public static function buildTagUrl( string $tagText ) : Url
	{
		if( !isset( Store::i()->tagSlugs ) )
		{
			Store::i()->tagSlugs = iterator_to_array(
				Db::i()->select( 'tag_text,tag_text_seo', static::$databaseTable, [ 'tag_enabled=?', 1 ] )
					->setKeyField( 'tag_text' )
					->setValueField( 'tag_text_seo' )
			);
		}

		$cache = Store::i()->tagSlugs;
		$slug = $cache[ $tagText ] ?? Friendly::seoTitle( $tagText );
		return Url::internal( "app=core&module=discover&controller=tag&tag=" . $slug, "front", "tags" );
	}

	/**
	 * Get URL
	 *
	 * @return    Url|string|null
	 * @throws	BadMethodCallException
	 */
	public function url(): Url|string|null
	{
		return Url::internal( "app=core&module=discover&controller=tag&tag=" . $this->text_seo, "front", "tags" );
	}

	/**
	 * Cover Photo
	 *
	 * @return	mixed
	 */
	public function coverPhoto(): mixed
	{
		$photo = parent::coverPhoto();
		$photo->editable = ( Member::loggedIn()->isAdmin() and Member::loggedIn()->hasAcpRestriction( 'core', 'discovery', 'tags_manage' ) );
		$photo->overlay = Theme::i()->getTemplate( 'tags', 'core', 'front' )->coverPhotoOverlay( $this );
		return $photo;
	}

	/**
	 * Use the cache to build a list of tag IDs used in this item
	 *
	 * @param Item $item
	 * @return array
	 */
	public static function getTagIdsForItem( Item $item ) : array
	{
		$ids = [];
		foreach( $item->tags() as $tag )
		{
			if( $id = array_search( $tag, static::getStore() ) )
			{
				$ids[] = $id;
			}
		}

		if( $prefix = $item->prefix() )
		{
			if( $id = array_search( $prefix, static::getStore() ) )
			{
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Tag Store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->tags ) )
		{
			/* Cache only the enabled tags */
			Store::i()->tags = iterator_to_array(
				Db::i()->select( 'tag_id,tag_text', static::$databaseTable, [ 'tag_enabled=?', 1 ], 'tag_text' )
					->setKeyField( 'tag_id' )
					->setValueField( 'tag_text' )
			);
		}

		return Store::i()->tags;
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'tags', 'tagSlugs' );

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

		$oldPhoto = $this->coverPhotoFile();

		$this->text = sprintf( Member::loggedIn()->language()->get( 'copy_tag_text' ), $this->text );
		$this->text_seo = Friendly::seoTitle( $this->text );
		$this->totals = null;

		parent::__clone();

		if( $oldPhoto )
		{
			try
			{
				$newPhoto = File::create( 'core_Tags', $oldPhoto->originalFilename, $oldPhoto->contents() );
				$this->cover_photo = (string) $newPhoto;
			}
			catch( Exception )
			{
				$this->cover_photo = null;
				$this->cover_offset = 0;
			}
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
		parent::delete();

		if( $photo = $this->coverPhotoFile() )
		{
			$photo->delete();
		}

		/* Queue the background task to remove the tag from all currently tagged items */
		Task::queue( 'core', 'UpdateTaggedItems', [ 'tag' => $this->text, 'delete' => true ], 5, [ 'tag' ] );
	}
}