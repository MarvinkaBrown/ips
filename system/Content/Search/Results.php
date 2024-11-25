<?php
/**
 * @brief		Search Results
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Aug 2014
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ArrayIterator;
use IPS\Application;
use IPS\Content\Item;
use IPS\Content\Reaction;
use IPS\Content\Review;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use function array_slice;
use function count;
use function defined;
use function get_class;
use function gettype;
use function in_array;
use function is_integer;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Results
 */
class Results extends ArrayIterator
{	
	/**
	 * @brief	Count
	 */
	protected mixed $countAllRows = null;
	
	/**
	 * @brief	Has this been initated?
	 */
	protected bool $initated = FALSE;
		
	/**
	 * @brief	Author data
	 */
	protected array $authorData = array();
	
	/**
	 * @brief	Item titles
	 */
	protected array $itemData = array();
	
	/**
	 * @brief	Container data
	 */
	protected array $containerData = array();
	
	/**
	 * @brief	Reputation data
	 */
	protected array $reputationData = array();
	
	/**
	 * @brief	Reaction Data
	 */
	protected array $reactions = array();

	/**
	 * @brief	Attached Images
	 */
	protected array $attachedImages = array();
	
	/**
	 * @brief	Review Ratings
	 */
	protected array $reviewRatings = array();
	
	/**
	 * @brief	Index IDs of items I have posted in
	 */
	protected array $iPostedIn = array();
	
	/**
	 * @brief	Tag values for index_ids returned from search
	 */
	protected array $tags = array();

	/**
	 * @brief	Prefixes for index_ids returned from search
	 */
	protected array $prefixes = array();
		
	/**
	 * Constructor
	 *
	 * @param	array				$results			The results
	 * @param	Select|int	$countAllRows		Count for all rows query or int
	 * @return	void
	 */
	public function __construct( $results, $countAllRows )
	{
		$this->countAllRows = $countAllRows;
		parent::__construct( $results );
	}
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init(): void
	{
		/* Init */
		$membersToLoad = array();
		$itemsToLoad = array();
		$containersToLoad = array();
		$reputationIds = array();
		$reviewRatings = array();
		$itemIndexIds = array();
		$attachedImages = array();

		/* Loop */
		foreach ( $this->getArrayCopy() as $result )
		{
			if ( $result['index_author'] )
			{
				$membersToLoad[ $result['index_author'] ] = $result['index_author'];
			}
			
			if ( in_array( 'IPS\Content\Comment', class_parents( $result['index_class'] ) ) )
			{
				$commentClass = $result['index_class'];
				$itemsToLoad[ $commentClass::$itemClass ][ $result['index_item_id'] ] = $result['index_item_id'];
			}
			else
			{
				$itemsToLoad[ $result['index_class'] ][ $result['index_item_id'] ] = $result['index_item_id'];
			}
			
			if ( $result['index_container_id'] )
			{
				$class = $result['index_class'];
				$itemClass = in_array( 'IPS\Content\Comment', class_parents( $class ) ) ? $class::$itemClass : $class;
				if ( isset( $itemClass::$containerNodeClass ) )
				{
					$containerClass = $itemClass::$containerNodeClass;
					if ( isset( $containerClass::$seoTitleColumn ) )
					{
						$containersToLoad[ $itemClass::$containerNodeClass ][ $result['index_container_id'] ] = $result['index_container_id'];
					}
				}
			}
			
			if ( IPS::classUsesTrait( $result['index_class'], 'IPS\Content\Reactable' ) )
			{
				$reputationIds[ $result['index_class'] ][ $result['index_object_id'] ] = $result['index_object_id'];
			}
			
			if ( in_array( 'IPS\Content\Review', class_parents( $result['index_class'] ) ) )
			{
				$reviewRatings[ $result['index_class'] ][ $result['index_object_id'] ] = $result['index_object_id'];
			}
			
			if ( $result['index_item_index_id'] )
			{
				$itemIndexIds[] = $result['index_item_index_id'];
			}
			
			if ( $result['index_club_id'] )
			{
				$containersToLoad[ 'IPS\Member\Club' ][ $result['index_club_id'] ] = $result['index_club_id'];
			}

			$attachedImages[ $result['index_class'] ][ $result['index_item_id'] ][] = $result['index_object_id'];
		}

		/* Load item data */
		foreach ( $itemsToLoad as $itemClass => $itemIds )
		{
			/* @var Item $itemClass */
			/* @var array $databaseColumnMap */
			foreach ( Db::i()->select( $itemClass::basicDataColumns(), $itemClass::$databaseTable, Db::i()->in( $itemClass::$databasePrefix . $itemClass::$databaseColumnId, $itemIds ) )->setKeyField( $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) as $itemId => $itemData )
			{
				$this->itemData[ $itemClass ][ $itemId ] = $itemData;
				
				if ( isset( $itemClass::$databaseColumnMap ) and isset( $itemClass::$databaseColumnMap['author'] ) and isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ] ) )
				{
					$memberId = $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ];
					$membersToLoad[ $memberId ] = $memberId;
				}
			}
			
			if ( method_exists( $itemClass, 'searchResultExtraData' ) )
			{
				foreach ( $itemClass::searchResultExtraData( $this->itemData[ $itemClass ] ) as $id => $extraData )
				{
					$this->itemData[ $itemClass ][ $id ]['extra'] = $extraData;
				}
			}
		}
				
		/* Load author data */
		if( count( $membersToLoad ) )
		{
			$this->authorData = iterator_to_array( Db::i()->select( Member::columnsForPhoto(), 'core_members', Db::i()->in( 'member_id', $membersToLoad ) )->setKeyField( 'member_id' ) );
		}
		
		/* Load container data */
		foreach ( $containersToLoad as $containerClass => $containerIds )
		{
			/* @var string|Model $containerClass */
			$this->containerData[ $containerClass ] = iterator_to_array( Db::i()->select( $containerClass::basicDataColumns(), $containerClass::$databaseTable, Db::i()->in( $containerClass::$databasePrefix . $containerClass::$databaseColumnId, $containerIds ) )->setKeyField( $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) );
		}
		
		/* Load reputation data */
		if ( count( $reputationIds ) )
		{			
			$clause = array();
			$binds = array();
			foreach ( $reputationIds as $class => $ids )
			{
				/* @var Item $class */
				$clause[] = "( reaction_enabled=? AND app=? AND type=? AND " . Db::i()->in( 'type_id', $ids ) . " )";
				$binds[] = 1;
				$binds[] = $class::$application;
				$binds[] = $class::reactionType();
			}
			
			$where = array( array_merge( array( implode( ' OR ', $clause ) ), $binds ) );
			foreach ( Db::i()->select( array( 'app', 'type', 'type_id', 'member_id', 'rep_rating', 'reaction' ), 'core_reputation_index', $where )->join( 'core_reactions', 'reaction=reaction_id' ) as $rep )
			{
				$this->reputationData[ $rep['app'] ][ $rep['type'] ][ $rep['type_id'] ][ $rep['member_id'] ] = $rep['reaction'];
				
				if ( !isset( $this->reactions[ $rep['app'] ][ $rep['type'] ][ $rep['type_id' ] ][ $rep['reaction'] ] ) )
				{
					$this->reactions[ $rep['app'] ][ $rep['type'] ][ $rep['type_id' ] ][ $rep['reaction'] ] = 0;
				}

				$this->reactions[ $rep['app'] ][ $rep['type'] ][ $rep['type_id' ] ][ $rep['reaction'] ]++;
			}
		}
		
		/* Load rating reviews */
		foreach ( $reviewRatings as $reviewClass => $reviewIds )
		{
			/* @var string|Review $reviewClass */
			/* @var array $databaseColumnMap */
			$this->reviewRatings[ $reviewClass ] = iterator_to_array( Db::i()->select( array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnId, $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] ), $reviewClass::$databaseTable, Db::i()->in( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnId, $reviewIds ) )->setKeyField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnId )->setValueField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] ) );
		}
		
		/* Load data for the "I posted in this" stars */
		if ( count( $itemIndexIds ) and Member::loggedIn()->member_id )
		{
			$this->iPostedIn = Index::i()->iPostedIn( $itemIndexIds, Member::loggedIn() );
		}

		/* Load tags and prefixes */
		if ( count( $itemIndexIds ) and Settings::i()->search_method == 'mysql' and Settings::i()->tags_enabled)
		{
			$orderBy = '';
			$this->tags = iterator_to_array( Db::i()->select( 'index_id, GROUP_CONCAT(index_tag' . $orderBy . ') as index_tags', 'core_search_index_tags', array( array( Db::i()->in( 'index_id', $itemIndexIds ) ), array( 'index_is_prefix = 0' ) ), NULL, NULL, 'index_id' )->setKeyField('index_id') );
			$this->prefixes = iterator_to_array( Db::i()->select( 'index_id, index_tag as index_prefix', 'core_search_index_tags', array( array( Db::i()->in( 'index_id', $itemIndexIds ) ), array( 'index_is_prefix = 1' ) ) )->setKeyField('index_id') );
		}

		/* Load attached images */
		$clause = [];
		$binds = [];
		$locationKeyMap = [];
		foreach ( $attachedImages as $class => $ids )
		{
			$itemClass = in_array( 'IPS\Content\Comment', class_parents( $class ) ) ? $class::$itemClass : $class;
			foreach( $ids as $indexId => $commentIds )
			{
				foreach( $commentIds as $commentId )
				{
					$locationKey = $itemClass::$application . '_' . IPS::mb_ucfirst( $itemClass::$module );
					$locationKeyMap[$locationKey] = $class;
					$clause[] = "( location_key=? AND id1=? AND id2=?)";
					$binds[] = $locationKey;
					$binds[] = $indexId;
					$binds[] = $commentId;
				}
			}
		}

		if ( count( $attachedImages ) and count( $clause ) )
		{
			/* As we need to join back onto the map table, 2 queries are much more efficient */
			$ids = iterator_to_array( Db::i()->select( 'attachment_id', 'core_attachments_map', array( array_merge( array( implode( ' OR ', $clause ) ), $binds ) ) ) );
			foreach ( Db::i()->select( '*', 'core_attachments', array( array( Db::i()->in( 'attach_id', $ids ) ), array('attach_is_image=1') ), 'attach_id ASC' )
					  ->join( 'core_attachments_map', 'core_attachments_map.attachment_id=core_attachments.attach_id' ) as $row )
			{
				if ( isset( $locationKeyMap[ $row['location_key'] ] ) and isset( $row['id2'] ) )
				{
					$this->attachedImages[ $locationKeyMap[ $row['location_key'] ] ][ $row['id2'] ][] = [
						'extension' => 'core_Attachment',
						'location' => $row['attach_location'],
						'thumb_location' => $row['attach_thumb_location'],
						'labels' => implode( ', ', Parser::getAttachmentLabels( $row ) )
					];
				}
			}
		}

		/* Set that we're initiated */
		$this->initated = TRUE;
	}
	
	/**
	 * Get current
	 *
	 * @return	Result\Content
	 */
	public function current(): Result\Content
	{
		$data = parent::current();

		$class = $data['index_class'];
		$itemClass = in_array( 'IPS\Content\Comment', class_parents( $class ) ) ? $class::$itemClass : $class;
		$containerClass = $itemClass::$containerNodeClass ?? NULL;
		
		$reputationData = array();
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) and isset( $this->reputationData[ $class::$application ][ $class::reactionType() ][ $data['index_object_id'] ] ) )
		{
			$reputationData = $this->reputationData[ $class::$application ][ $class::reactionType() ][ $data['index_object_id'] ];
		}
		
		$reactions = array();
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) and isset( $this->reactions[ $class::$application ][ $class::reactionType() ][ $data['index_object_id'] ] ) )
		{
			$reactions = $this->reactions[ $class::$application ][ $class::reactionType() ][ $data['index_object_id'] ];
		}
		
		/* Ensure we have an item if results are gathered from the map table */
		if ( ! isset( $this->itemData[ $itemClass ][ $data['index_item_id'] ] ) )
		{
			/* This should not happen in production, so debug log */
			Log::debug( "index_item_id ID " . $data['index_item_id'] . " for " . $itemClass . " has no itemData" , 'searchResults' );

			/* Then cleanup/remove the item now */
			Index::i()->directIndexRemoval( $data['index_class'], $data['index_object_id'] );

			/* And continue, moving on to the next item */
			static::next();
			return static::current();
		}
		
		$itemData = $this->itemData[ $itemClass ][ $data['index_item_id'] ];
		if ( isset( $itemClass::$databaseColumnMap['author'] ) and isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ] ) )
		{
			$memberId = $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ];
			if ( isset( $this->authorData[ $memberId ] ) )
			{
				$itemData['author'] = $this->authorData[ $memberId ];
			}
		}

		if ( isset( $this->attachedImages[ $class ][ $data['index_object_id'] ] ) )
		{
			$itemData['attachedImages'] = $this->attachedImages[ $class ][ $data['index_object_id'] ];
		}

		if ( isset( $this->tags[ $data['index_item_index_id'] ] ) )
		{
			$data['index_tags'] = $this->tags[ $data['index_item_index_id'] ]['index_tags'];
		}
		
		if ( isset( $this->prefixes[ $data['index_item_index_id'] ]) )
		{
			$data['index_prefix'] = $this->prefixes[ $data['index_item_index_id'] ]['index_prefix'];
		}
		
		$containerData = NULL;
		if ( $containerClass and isset( $this->containerData[ $containerClass ] ) and isset( $this->containerData[ $containerClass ][ $data['index_container_id'] ] ) )
		{
			$containerData = $this->containerData[ $containerClass ][ $data['index_container_id'] ];
			if ( $data['index_club_id'] and isset( $this->containerData[ 'IPS\Member\Club' ][ $data['index_club_id'] ] ) )
			{
				$containerData['_club'] = $this->containerData[ 'IPS\Member\Club' ][ $data['index_club_id'] ];
			}
		}
		
		return new Result\Content(
			$data,
			$this->authorData[$data['index_author']] ?? array('member_id' => 0, 'name' => Member::loggedIn()->language()->addToStack('guest'), 'members_seo_name' => '', 'pp_main_photo' => NULL, 'pp_photo_type' => 'none'),
			$itemData,
			$containerData,
			$reputationData,
			$this->reviewRatings[$data['index_class']][$data['index_object_id']] ?? NULL,
			in_array( $data['index_item_index_id'], $this->iPostedIn ),
			$reactions
		);
	}
	
	/**
	 * Rewind
	 *
	 * @return	void
	 */
	public function rewind(): void
	{
		if ( !$this->initated )
		{
			$this->init();
		}
		
		parent::rewind();
	}
	
	/**
	 * Get count
	 *
	 * @param	bool	$allRows	If TRUE, will get the number of rows ignoring the limit
	 * @return	int
	 */
	public function count( bool $allRows = FALSE ): int
	{
		if ( $allRows )
		{
			if ( is_integer( $this->countAllRows ) )
			{
				return $this->countAllRows;
			}
			else if ( gettype( $this->countAllRows ) === 'object' and get_class( $this->countAllRows ) === 'IPS\Db\Select' )
			{
				$this->countAllRows = $this->countAllRows->first();
				
				return $this->countAllRows;
			}
			else
			{
				throw new LogicException;
			}
		}
		else
		{
			return parent::count();
		}
	}
	
	/**
	 * Add in "extra" items
	 *
	 * @param	array				$extra		Types ('register', 'follow_member', 'follow_content', 'photo', 'votes', 'like', 'rep_neg')
	 * @param	Member|NULL	$author		The author to limit extra items to
	 * @param	DateTime|NULL	$lastTime	If provided, only items since this date is included. If NULL, it works out which to include based on what results are being shown
	 * @param	DateTime|NULL	$firstTime	If provided, only items before this date is included. If NULL, it works out which to include based on what results are being shown
	 * @return	array
	 * @note	Each thing is limited to 10 items. Even though this may result in accuracies, the limit is necessary so that if there's more extra items that content it doesn't create lots of queries and slow loading
	 */
	public function addExtraItems( array $extra, Member|null $author = NULL, DateTime|null $lastTime=NULL, DateTime|null $firstTime = NULL ): array
	{
		/* Work out the timestamps */
		$results = iterator_to_array( $this );
		if ( $firstTime )
		{
			$firstTime = $firstTime->getTimestamp();
		}
		elseif ( isset( Request::i()->page ) and Request::i()->page != 1 )
		{
			foreach ( $results as $result )
			{
				$firstTime = $result->createdDate->getTimestamp();
				break;
			}
		}
		if ( $lastTime )
		{
			$lastTime = $lastTime->getTimestamp();
		}
		else
		{
			foreach ( $results as $key => $result )
			{
				/* If an item has been removed incorrectly an orphan search index entry can be left. This is already logged in current() so let's not fail catastrophically */
				if( !$result )
				{
					unset( $results[ $key ] );
					continue;
				}

				$lastTime = $result->createdDate->getTimestamp();
			}
		}
		
		/* We need at least a last time */
		if ( !$lastTime )
		{
			return $results;
		}
				
		/* Get the extra items... */
		$extraItems = array();
		if ( $lastTime )
		{
			/* Users registered */
			if ( in_array( 'register', $extra ) )
			{
				$where = array( array( 'joined>?', $lastTime ), array( 'completed=?', true ), array( 'temp_ban!=?', '-1' ) );
				if ( $firstTime )
				{
					$where[] = array( 'joined<?', $firstTime );
				}
				if ( $author )
				{
					$where[] = array( 'core_members.member_id=?', $author->member_id );
				}
				
				foreach (
					Db::i()->select( implode( ', ', array_merge( array_map( function( $c ) { return "core_members.{$c}"; }, Member::columnsForPhoto() ), array( 'core_members.joined', 'core_validating.new_reg' ) ) ), 'core_members', $where, 'joined DESC', 10 )
						->join( 'core_validating', 'core_validating.member_id=core_members.member_id' )
				as $member ) {
					if ( empty( $member['new_reg'] ) )
					{
						$extraItems[] = new Result\Custom(
							DateTime::ts( $member['joined'] ),
							Member::loggedIn()->language()->addToStack( 'activity_member_joined', FALSE, array( 'htmlsprintf' => Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $member['member_id'], $member['name'], $member['members_seo_name'], $member['member_group_id'] ) ) ),
							Theme::i()->getTemplate( 'global', 'core', 'front' )->userPhotoFromData( $member['member_id'], $member['name'], $member['members_seo_name'], Member::photoUrl( $member ), 'fluid' )
						);
					}
				}
			}
			
			/* Users changed photo */
			if ( in_array( 'photo', $extra ) )
			{
				$where = array( array( 'photo_last_update>? AND photo_last_update>(joined+300)', $lastTime ), array( 'completed=?', true ) );
				if ( $firstTime )
				{
					$where[] = array( 'photo_last_update<?', $firstTime );
				}
				if ( $author )
				{
					$where[] = array( 'member_id=?', $author->member_id );
				}
				foreach ( Db::i()->select( array_merge( Member::columnsForPhoto(), array( 'photo_last_update' ) ), 'core_members', $where, 'photo_last_update DESC', 10 ) as $member )
				{					
					$extraItems[] = new Result\Custom(
						DateTime::ts( $member['photo_last_update'] ),
						Member::loggedIn()->language()->addToStack( 'activity_member_updated_photo', FALSE, array( 'htmlsprintf' => Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $member['member_id'], $member['name'], $member['members_seo_name'], $member['member_group_id'] ) ) ),
						Theme::i()->getTemplate( 'global', 'core', 'front' )->userPhotoFromData( $member['member_id'], $member['name'], $member['members_seo_name'], Member::photoUrl( $member ), 'fluid' )
					);
				}
			}
			
			/* Follow */
			if ( in_array( 'follow_member', $extra ) or in_array( 'follow_content', $extra ) )
			{						
				$where = array( array( 'follow_is_anon=0' ), array( 'follow_added>?', $lastTime ) );
				if ( $firstTime )
				{
					$where[] = array( 'follow_added<?', $firstTime );
				}
				if ( in_array( 'follow_member', $extra ) xor in_array( 'follow_content', $extra ) )
				{
					if ( in_array( 'follow_member', $extra ) )
					{
						$where[] = array( 'follow_app=? AND follow_area=?', 'core', 'member' );
					}
					else
					{
						$where[] = array( '( follow_app!=? OR follow_area!=? )', 'core', 'member' );
					}
				}
				if ( $author )
				{
					if ( in_array( 'follow_member', $extra ) and in_array( 'follow_content', $extra ) )
					{
						$where[] = array( '( follow_id IN (?) )', Db::i()->select( 'follow_id', 'core_follow', array( '( follow_member_id=? OR ( follow_app=? AND follow_area=? AND follow_rel_id=? ) )', $author->member_id, 'core', 'member', $author->member_id ) ) );
					}
					elseif ( in_array( 'follow_member', $extra ) )
					{
						$where[] = array( 'follow_rel_id=?', $author->member_id );
					}
					else
					{
						$where[] = array( 'follow_member_id=?', $author->member_id );
					}
				}
				
				/* If an application was not updated or is not installed, do not try to fetch it or we can get a class does not exist fatal error */
				$where[] = array( "follow_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')" );

				$lastClass	= NULL;
				$lastMember = NULL;

				foreach ( Db::i()->select( array( 'follow_app', 'follow_area', 'follow_rel_id', 'follow_member_id', 'follow_added' ), 'core_follow', $where, 'follow_added DESC', 10 ) as $follow )
				{
					if ( $follow['follow_app'] == 'core' and $follow['follow_area'] == 'member' )
					{
						$extraItems[] = new Result\Custom(
							DateTime::ts( $follow['follow_added'] ),
							Member::loggedIn()->language()->addToStack( 'activity_member_followed', FALSE, array( 'htmlsprintf' => array( Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( Member::load( $follow['follow_member_id'] ) ), Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( Member::load( $follow['follow_rel_id'] ) ) ) ) )
						);
					}
					else
					{
						$class = 'IPS\\' . $follow['follow_app'] . '\\' . IPS::mb_ucfirst( $follow['follow_area'] );

						try
						{
							if ( class_exists( $class ) )
							{
								/* It is possible we've already queried and fetched this item, so attempt to pull from our search results first */
								$urlToThing = NULL;

								foreach( $results as $result )
								{
									$result = $result->asArray();

									if( $result['indexData']['index_class'] == $class AND $result['indexData']['index_object_id'] == $follow['follow_rel_id'] )
									{
										$urlToThing = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $result['url'], FALSE, $result['indexData']['index_title'], FALSE );
										break;
									}
								}

								if( $urlToThing === NULL )
								{
									$thingBeingFollowed	= $class::loadAndCheckPerms( $follow['follow_rel_id'] );
									$urlToThing			= Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $thingBeingFollowed->url(), FALSE, $thingBeingFollowed instanceof Model ? $thingBeingFollowed->_title : $thingBeingFollowed->mapped('title'), FALSE );
								}
								
								/* Do we need to merge with the last result? */
								if( $lastClass !== NULL AND $lastClass === $class AND $lastMember === $follow['follow_member_id'] AND count( $extraItems ) )
								{
									$extraItem	= array_pop( $extraItems );

									$mergeList  = array_filter( array_merge( array( $urlToThing ), $extraItem->getMergeData() ) );
									$listToDisplay	= $mergeList;

									if( count( $mergeList ) > 3 )
									{
										$listToDisplay = array_slice( $mergeList, 0, 3 );
										$listToDisplay[] = Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( count( $mergeList ) - 3 ) ) );
									}

									$extraItem->mergeInData( Member::loggedIn()->language()->addToStack( 'activity_member_followed', FALSE, array( 'htmlsprintf' => array( Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( Member::load( $follow['follow_member_id'] ) ), Member::loggedIn()->language()->formatList( $listToDisplay ) ) ) ), $urlToThing );
									$extraItems[] = $extraItem;
								}
								else
								{
									$extraItems[] = new Result\Custom(
										DateTime::ts( $follow['follow_added'] ),
										Member::loggedIn()->language()->addToStack( 'activity_member_followed', FALSE, array( 'htmlsprintf' => array( Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( Member::load( $follow['follow_member_id'] ) ), $urlToThing ) ) ),
										NULL,
										$urlToThing
									);
								}
							}

						}
						catch ( OutOfRangeException ) { }

						$lastClass = $class;
						$lastMember = $follow['follow_member_id'];
					}
				}
			}
			
			/* Likes */
			if ( Settings::i()->reputation_enabled and Member::loggedIn()->group['gbw_view_reps'] and ( in_array( 'like', $extra ) or in_array( 'react', $extra ) ) )
			{
				$where = array( array( 'rep_date>?', $lastTime ) );
				$where[] = array( 'reaction_enabled=?', 1 );
				if ( $firstTime )
				{
					$where[] = array( 'rep_date<?', $firstTime );
				}

				/* get only the reputation data for installed applications */
				$where[] =  Db::i()->in( 'app', array_keys( Application::applications() ) ) ;

				if ( $author )
				{
					foreach ( Db::i()->select( '*', 'core_reputation_index', array_merge( $where, array( array( 'member_id=?', $author->member_id ) ) ), 'rep_date DESC', 10 )->join( 'core_reactions', 'reaction=reaction_id' ) as $rep )
					{
						$this->addReputationExtraItem( $rep, $extraItems );
					}

					foreach ( Db::i()->select( '*', 'core_reputation_index', array_merge( $where, array( array( 'member_received=?', $author->member_id ) ) ), 'rep_date DESC', 10 )->join( 'core_reactions', 'reaction=reaction_id' ) as $rep )
					{
						$this->addReputationExtraItem( $rep, $extraItems );
					}
				}
				else
				{
					foreach ( Db::i()->select( '*', 'core_reputation_index', $where, 'rep_date DESC', 10 )->join( 'core_reactions', 'reaction=reaction_id' ) as $rep )
					{
						$this->addReputationExtraItem( $rep, $extraItems );
					}
				}
			}
			
			/* Clubs */
			if ( Settings::i()->clubs and in_array( 'clubs', $extra ) )
			{
				$where = array( array( 'created>?', $lastTime ) );
				if ( $firstTime )
				{
					$where[] = array( 'created<?', $firstTime );
				}
				if ( $author )
				{
					$where[] = array( 'owner=?', $author->member_id );
				}
				
				foreach ( Club::clubs( Member::loggedIn(), NULL, 'created', FALSE, array(), $where ) as $club )
				{
					if ( $club->owner AND $club->owner->member_id )
					{
						$extraItems[] = new Result\Custom(
							$club->created,
							Member::loggedIn()->language()->addToStack( 'activity_club', FALSE, array( 'htmlsprintf' => array( Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( $club->owner ), Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $club->url(), FALSE, $club->name, FALSE ) ) ) )
						);
					}
				}
			}

			/* Extensions */
			foreach ( Application::allExtensions( 'core', 'StreamItems', TRUE, 'core' ) as $key => $extension )
			{
				$settingKey = "all_activity_" . mb_strtolower( $key );

				if( Settings::i()->$settingKey )
				{
					$extraItems = array_merge( $extraItems, $extension->extraItems( $author, $lastTime, $firstTime ) );
				}
			}
		}

		/* Merge them in */
		if ( !empty( $extraItems ) )
		{
			$results = array_merge( $results, $extraItems );
			uasort( $results, function( $a, $b )
			{
				if ( $a->createdDate->getTimestamp() == $b->createdDate->getTimestamp() )
				{
					return 0;
				}
				elseif( $a->createdDate->getTimestamp() < $b->createdDate->getTimestamp() )
				{
					return 1;
				}
				else
				{
					return -1;
				}
			} );
		}
		
		/* And return */
		return $results;
	}

	/**
	 * Add reputation given/received to extra things array
	 *
	 * @param	array 	$rep		Reputation row from database
	 * @param	array 	$extraItems	Array of extra items (passed by reference)
	 * @return	void
	 */
	protected function addReputationExtraItem( array $rep, array &$extraItems ): void
	{
		try
		{
			$thingBeingRepped = NULL;
			foreach ( Application::load( $rep['app'] )->extensions( 'core', 'ContentRouter', TRUE, TRUE ) as $ext )
			{
				foreach ( $ext->classes as $class )
				{
					if ( IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) and $class::reactionType() == $rep['type'] )
					{
						$thingBeingRepped = $class::loadAndCheckPerms( $rep['type_id'] );
						break;
					}
					if ( isset( $class::$commentClass ) )
					{
						$commentClass = $class::$commentClass;
						if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Reactable' ) and $commentClass::reactionType() == $rep['type'] )
						{
							$thingBeingRepped = $commentClass::loadAndCheckPerms( $rep['type_id'] );
							break;
						}
					}
					if ( isset( $class::$reviewClass ) )
					{
						$reviewClass = $class::$reviewClass;
						if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Reactable' ) and $reviewClass::reactionType() == $rep['type'] )
						{
							$thingBeingRepped = $reviewClass::loadAndCheckPerms( $rep['type_id'] );
							break;
						}
					}
				}
			}
			if ( !$thingBeingRepped )
			{
				throw new OutOfRangeException;
			}
			
			if ( Reaction::isLikeMode() )
			{
				$extraItems[] = new Result\Custom(
					DateTime::ts( $rep['rep_date'] ),
					Member::loggedIn()->language()->addToStack( 'activity_member_liked', FALSE, array( 'htmlsprintf' => array(
						Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( Member::load( $rep['member_id'] ) ),
						$thingBeingRepped->indefiniteArticle(),
						Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $thingBeingRepped->url(), FALSE, $thingBeingRepped instanceof Item ? $thingBeingRepped->mapped('title') : $thingBeingRepped->item()->mapped('title'), FALSE )
					) ) )
				);
			}
			else
			{
				$reaction = Reaction::load( $rep['reaction'] );
				$extraItems[] = new Result\Custom(
					DateTime::ts( $rep['rep_date'] ),
					Member::loggedIn()->language()->addToStack( 'activity_member_reacted', FALSE, array( 'htmlsprintf' => array(
						(string) $reaction->_icon->url,
						$reaction->_title,
						Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( Member::load( $rep['member_id'] ) ),
						$thingBeingRepped->indefiniteArticle(),
						Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $thingBeingRepped->url(), FALSE, $thingBeingRepped instanceof Item ? $thingBeingRepped->mapped('title') : $thingBeingRepped->item()->mapped('title'), FALSE )
					) ) )
				);
			}
		}
		catch ( OutOfRangeException ) { }
	}
}