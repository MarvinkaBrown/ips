<?php
/**
 * @brief		tag
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		02 Apr 2024
 */

namespace IPS\core\modules\front\discover;

use IPS\Content;
use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\Content\Tag as SystemTag;
use IPS\Content\Taggable;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\CoverPhoto\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Table\Content as TableContent;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function sprintf;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tag
 */
class tag extends Controller
{
	/**
	 * @var SystemTag|null
	 */
	protected ?SystemTag $tag = null;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/tags_page.css' ) );

		try
		{
			$this->tag = SystemTag::load( Request::i()->tag, 'tag_text_seo' );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2C341/1', 404 );
		}

		parent::execute();
	}

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$contentTypes = SystemTag::getTaggableContentTypes();

		$tabs = [];
		foreach( $contentTypes as $type => $class )
		{
			$totalItems = $class::getItemsWithPermission( $this->_getWhereClause( $class ), null, null, 'read', Filter::FILTER_AUTOMATIC, 0, null, false, false, false, true );
			if( $totalItems )
			{
				$tabs[] = $type;
			}
		}

		if( count( $tabs ) == 1 )
		{
			$content = $this->_getTaggedContent( $tabs[0] );
		}
		elseif( isset( Request::i()->tab ) and Request::i()->tab and array_key_exists( Request::i()->tab, $contentTypes ) )
		{
			$content = $this->_getTaggedContent( Request::i()->tab );
		}
		else
		{
			$content = $this->_getOverview();
		}

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $content );
		}

		Output::i()->title = $this->tag->text;
		Output::i()->breadcrumb[] = [ $this->tag->url(), $this->tag->text ];
		Output::i()->linkTags['canonical'] = (string) ( Request::i()->page > 1 ) ? $this->tag->url()->setPage( 'page', Request::i()->page ) : $this->tag->url() ;
		Output::i()->metaTags['og:title'] = $this->tag->text;
		Output::i()->metaTags['og:type'] = 'website';
		Output::i()->metaTags['og:url'] = (string) $this->tag->url();

		if( $image = $this->tag->coverPhotoFile() )
		{
			Output::i()->metaTags['og:image'] = (string) $image;
		}

		/* Do not set description tags for page 2+ as you end up with duplicate tags */
		if ( Request::i()->page < 2 and $metaDescription = $this->tag->metaDescription() )
		{
			Output::i()->metaTags['description'] = $metaDescription;
			Output::i()->metaTags['og:description'] = Output::i()->metaTags['description'];
		}

		Output::i()->output = Theme::i()->getTemplate( 'tags' )->view( $this->tag, $tabs, ( count( $tabs ) == 1 ? $content : '' ) );
	}

	/**
	 * Build the where clause to filter by content type and tag
	 *
	 * @param string $class
	 * @return array[]
	 */
	protected function _getWhereClause( string $class ) : array
	{
		/* @var Item $class */
		$where = [
			[ $class::$databasePrefix . $class::$databaseColumnId . ' IN (?)', Db::i()->select( 'tag_meta_id', 'core_tags', [ 'tag_meta_app=? and tag_meta_area=? and tag_text=?', $class::$application, $class::$module, $this->tag->text ] ) ]
		];

		/* Are any of these items pinned? */
		if( $pinnedItems = $this->tag->getPinnedItems() )
		{
			$pinned = [];
			$idColumn = $class::$databaseColumnId;
			foreach( $pinnedItems as $pinnedItem )
			{
				if( $pinnedItem instanceof $class )
				{
					$pinned[] = $pinnedItem->$idColumn;
				}
			}

			if( count( $pinned ) )
			{
				$where[] = [ Db::i()->in( $class::$databasePrefix . $class::$databaseColumnId, $pinned, true ) ];
			}
		}

		return $where;
	}

	/**
	 * Build an overview with all available content types
	 *
	 * @return string
	 */
	protected function _getOverview() : string
	{
		$contentTypes = SystemTag::getTaggableContentTypes();
		$pinnedItems = $this->tag->getPinnedItems();
		$items = [];
		foreach( Db::i()->select( '*', [ 'core_tags', 't' ], [
			[ 't.tag_text=?', $this->tag->text ],
			[ 'p.tag_perm_visible=?', 1 ],
			[ '(p.tag_perm_text=? or ' . Db::i()->findInSet( 'p.tag_perm_text', Member::loggedIn()->groups ) . ')', '*' ]
		], 't.tag_added desc', 50 )
				 ->join( [ 'core_tags_perms', 'p' ], 't.tag_aai_lookup=p.tag_perm_aai_lookup and t.tag_aap_lookup=p.tag_perm_aap_lookup' ) as $row )
		{
			if( count( $items ) >= Settings::i()->tags_landing_limit )
			{
				break;
			}

			foreach( $contentTypes as $k => $itemClass )
			{
				if( $itemClass::$application == $row['tag_meta_app'] and $itemClass::$module == $row['tag_meta_area'] )
				{
					try
					{
						$item = $itemClass::load( $row['tag_meta_id'] );
						$idColumn = $itemClass::$databaseColumnId;
						$pinned = false;
						if( is_array( $pinnedItems ) )
						{
							foreach( $pinnedItems as $pinnedItem )
							{
								if( get_class( $pinnedItem ) == $itemClass and $pinnedItem->$idColumn == $item->$idColumn )
								{
									$pinned = true;
									break;
								}
							}
						}

						if( !$pinned )
						{
							$items[] = $item;
						}
					}
					catch( OutOfRangeException ){}
					break;
				}
			}
		}

		return Theme::i()->getTemplate( 'tags', 'core', 'front' )->overview( $items, $this->tag );
	}

	/**
	 * Show a table for the content type
	 *
	 * @param string $type
	 * @return string
	 */
	protected function _getTaggedContent( string $type ) : string
	{
		$allTypes = SystemTag::getTaggableContentTypes();
		$class = $allTypes[ $type ];

		$table = new TableContent( $class, $this->tag->url()->setQueryString( 'tab', $type ), $this->_getWhereClause( $class ) );
		$table->classes[] = 'ipsData--grid ipsData--tags-data ipsData--tags-' . $type;
		$table->limit = 20;
		$table->title = sprintf( Member::loggedIn()->language()->get( 'tag_content_tagged' ), Member::loggedIn()->language()->get( $type . '_pl' ), $this->tag->text );
		$table->rowsTemplate = [ Theme::i()->getTemplate( 'tags', 'core' ), 'contentTableRows' ];
		$table->noModerate = true;
		return (string) $table;
	}

	/**
	 * Pin an item to the top of the tag page
	 *
	 * @return void
	 */
	protected function pin() : void
	{
		if( !Member::loggedIn()->modPermission( 'can_pin_tagged' ) )
		{
			Output::i()->error( 'no_permission', '2C341/3', 403 );
		}

		$form = new Form;
		$form->addMessage( 'tag_pin_info', 'ipsMessage ipsMessage--form' );
		$form->add( new Date( 'tag_pin_end_date', -1, false, [ 'unlimited' => -1, 'unlimitedLang' => 'tag_pin_end_date_none' ] ) );
		$form->add( new Upload( 'tag_pin_image', null, false, [
			'storageExtension' => 'core_Tags',
			'image' => true,
			'multiple' => false
		] ) );
		if( $values = $form->values() )
		{
			$itemClass = Request::i()->itemClass;

			/* what tab are we on? */
			$activeTab = null;
			foreach( SystemTag::getTaggableContentTypes() as $tab => $class )
			{
				if( $class == $itemClass )
				{
					$activeTab = $tab;
					break;
				}
			}

			try
			{
				$item = $itemClass::load( Request::i()->itemId );
				$this->tag->pinItem( $item, ( $values['tag_pin_end_date'] instanceof DateTime ? $values['tag_pin_end_date'] : null ), $values['tag_pin_image'] );
			}
			catch( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2C341/2', 404 );
			}

			Output::i()->redirect( $this->tag->url()->setQueryString( 'tab', $activeTab ) );
		}

		Output::i()->output = (string) $form;
	}

	protected function unpin() : void
	{
		Request::i()->confirmedDelete();

		if( !Member::loggedIn()->modPermission( 'can_pin_tagged' ) )
		{
			Output::i()->error( 'no_permission', '2C341/4', 403 );
		}

		$itemClass = Request::i()->itemClass;
		try
		{
			$item = $itemClass::load( Request::i()->itemId );
			$this->tag->removePinnedItem( $item );
		}
		catch( OutOfRangeException ){}

		Output::i()->redirect( $this->tag->url() );
	}

	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return    string
	 */
	protected function _coverPhotoStorageExtension(): string
	{
		return SystemTag::$coverPhotoStorageExtension;
	}

	/**
	 * Set Cover Photo
	 *
	 * @param CoverPhoto $photo New Photo
	 * @return    void
	 */
	protected function _coverPhotoSet( CoverPhoto $photo ): void
	{
		$this->tag->cover_photo = (string) $photo->file;
		$this->tag->cover_offset = $photo->offset;
		$this->tag->save();
	}

	/**
	 * Get Cover Photo
	 *
	 * @return    CoverPhoto
	 */
	protected function _coverPhotoGet(): CoverPhoto
	{
		return $this->tag->coverPhoto();
	}
}