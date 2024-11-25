<?php
/**
 * @brief		Search Result from Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Sep 2015
*/

namespace IPS\Content\Search\Result;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content\Comment;
use IPS\Content\Search\Result;
use IPS\Content\Search\SearchContent;
use IPS\DateTime;
use IPS\File;
use IPS\Xml\Rss;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Result
 */
class Content extends Result
{
	/**
	 * Index Data
	 */
	protected array $indexData;
	
	/**
	 * Author Data
	 */
	protected array $authorData;
	
	/**
	 * Item Data
	 */
	protected array $itemData;
	
	/**
	 * Author Data
	 */
	protected array|null $containerData;

	/**
	 * Reputation Data
	 */
	protected array|null $reputationData;
	
	/**
	 * Review Rating
	 */
	protected int|null $reviewRating;
	
	/**
	 * If the user has posted in the item
	 */
	protected mixed $iPostedIn = array();
	
	/**
	 * Reactions
	 */
	protected array $reactions;
	
	/**
	 * Constructor
	 *
	 * @param	array		$indexData		Data from index
	 * @param	array		$authorData		Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	array		$reputationData	Array of people who have given reputation and the reputation they gave
	 * @param	int|NULL	$reviewRating	If this is a review, the rating
	 * @param	bool		$iPostedIn		If the user has posted in the item
	 * @param	array		$reactions		Reaction Data
	 * @return	void
	 */
	public function __construct( array $indexData, array $authorData, array $itemData, array|null $containerData, array $reputationData, int|null $reviewRating = NULL, bool $iPostedIn = FALSE, array $reactions=array() )
	{
		$this->createdDate = DateTime::ts( $indexData['index_date_created'] );
		$this->lastUpdatedDate = DateTime::ts( $indexData['index_date_updated'] );
		$this->indexData = $indexData;
		$this->authorData = $authorData;
		$this->itemData = $itemData;
		$this->containerData = $containerData;
		$this->reputationData = $reputationData;
		$this->reviewRating = $reviewRating;
		$this->iPostedIn = $iPostedIn;
		$this->reactions = $reactions;
	}
	
	/**
	 * HTML
	 *
	 * @param	string	$view	'expanded' or 'condensed'
	 * @param	bool	$asItem	Displaying results as items?
	 * @param	bool	$canIgnoreComments	Can ignore comments in the result stream? Activity stream can, but search results cannot.
	 * @param	array|NULL	$template	Optional custom template
	 * @return	string
	 */
	public function html( string $view = 'expanded', bool $asItem = FALSE, bool $canIgnoreComments=FALSE, array|null $template=NULL ): string
	{
		if( $extension = SearchContent::extension( $this->indexData['index_class'] ) )
		{
			$searchResultTemplate = array( $extension, 'searchResult' );
			return $searchResultTemplate( $this->indexData, $this->authorData, $this->itemData, $this->containerData, $this->reputationData, $this->reviewRating, $this->iPostedIn, $view, $asItem, $canIgnoreComments, $template, $this->reactions );
		}
		return "";
	}
	
	/**
	 * Return search index data as an array
	 *
	 * @return array( 'indexData' => array() ... )
	 */
	public function asArray(): array
	{
		$extension = SearchContent::extension( $this->indexData['index_class'] );

		return array(
			'indexData' => $this->indexData,
			'authorData' => $this->authorData,
			'itemData' => $this->itemData,
			'containerData' => $this->containerData,
			'reputationData' => $this->reputationData,
			'reviewRating' => $this->reviewRating,
			'iPostedIn' => $this->iPostedIn,
			'template' => $extension ? $extension::searchResultBlock() : null,
			'url' => $extension ? $extension::urlFromIndexData( $this->indexData, $this->itemData ) : null,
			'searchResultClassName' => $extension ? $extension::$searchResultClassName : ''
		);
	}
	
	/**
	 * Add to RSS feed
	 *
	 * @param	Rss	$document	Document to add to
	 * @return	void
	 */
	public function addToRssFeed( Rss $document ): void
	{
		try
		{
			$class = $this->indexData['index_class'];
			$object = $class::load( $this->indexData['index_object_id'] );
			$enclosure = NULL;

			if ( $images = $object->contentImages(1) )
			{
				$data = array_pop( $images );
				$key = key( $data );

				try
				{
					$enclosure = File::get( $key, $data[ $key ] );
				}
				catch( Exception ) { }
			}

			$document->addItem( $object instanceof Comment ? $object->item()->mapped( 'title' ) : $object->mapped( 'title' ), $object->url(), Result::preDisplay( $this->indexData['index_content'] ), DateTime::ts( $this->indexData['index_date_created'] ), NULL, $enclosure );
		}
		/* If the search result was orphaned, let us continue */
		catch( OutOfRangeException ){}
	}
}