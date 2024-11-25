<?php
/**
 * @brief		Nexus Package Content Item Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content\Embeddable;
use IPS\Content\Featurable;
use IPS\Content\Filter;
use IPS\Content\Hideable;
use IPS\Content\Item as ContentItem;
use IPS\Content\MetaData;
use IPS\Content\Shareable;
use IPS\Db;
use IPS\File;
use IPS\File\Iterator;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use RuntimeException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package Item Model
 */
class Item extends ContentItem implements Embeddable
{
	use	MetaData,
		Shareable,
		Hideable,
		Featurable
		{
			Hideable::moderateNewReviews as public _moderateNewReviews;
			Hideable::hiddenStatus as public _hidden;
		}
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'store';
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_packages';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'p_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	Node Class
	 */
	public static ?string $containerNodeClass = 'IPS\nexus\Package\Group';
	
	/**
	 * @brief	Review Class
	 */
	public static string $reviewClass = 'IPS\nexus\Package\Review';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'title'					=> 'name',
		'container'				=> 'group',
		'featured'				=> 'featured',
		'num_reviews'			=> 'reviews',
		'unapproved_reviews'	=> 'unapproved_reviews',
		'hidden_reviews'		=> 'hidden_reviews',
		'rating'				=> 'rating',
		'meta_data'				=> 'meta_data',
		'date'					=> 'date_added',
		'updated'				=> 'date_updated'
	);
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'product';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'box';
	
	/**
	 * @brief	Include In Sitemap
	 */
	public static bool $includeInSitemap = FALSE;
	
	/**
	 * @brief	Can this content be moderated normally from the front-end (will be FALSE for things like Pages and Commerce Products)
	 */
	public static bool $canBeModeratedFromFrontend = FALSE;
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function get_title() : string
	{
		return Member::loggedIn()->language()->addToStack("nexus_package_{$this->id}");
	}
	
	/**
	 * Get description
	 *
	 * @return	string|null
	 */
	public function content(): ?string
	{
		return Member::loggedIn()->language()->get("nexus_package_{$this->id}_desc"); // Has to be get() rather than addToStack() so we can reliably strips tags, etc.
	}
	
	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( ?Member $member=null ): bool
	{
		if ( !$this->store )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		return $this->member_groups === '*' or $member->inGroup( explode( ',', $this->member_groups ) );
	}

	/**
	 * Delete Package Data
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* Delete Images */
		foreach( Db::i()->select( '*', 'nexus_package_images', array( 'image_product=?', $this->id ) ) as $image )
		{
			try
			{
				File::get( 'nexus_Products', $image['image_location'] )->delete();
			}
			catch ( Exception ) { }
		}
		Db::i()->delete( 'nexus_package_images', array( 'image_product=?', $this->id ) );

		/* Delete Product Options */
		Db::i()->delete( 'nexus_product_options', array( 'opt_package=?', $this->id ) );

		/* Delete Base Prices */
		Db::i()->delete( 'nexus_package_base_prices', array( 'id=?', $this->id ) );

		parent::delete();

		if( Application::appIsEnabled( 'downloads') )
		{
			/* Look for files linked to this product */
			foreach( Db::i()->select( '*', 'downloads_files', array( Db::i()->findInSet( 'file_nexus', array( $this->id ) ) ) ) as $file )
			{
				$packages = explode( ',', $file['file_nexus'] );
				$packages = array_filter( $packages, function( $packageId ){
					return $packageId != $this->id;
				} );

				/* If the only package was the one we just deleted, set it as not purchasable but do not remove the file_nexus value or the product will become downloadable by anyone */
				if( !count( $packages ) )
				{
					Db::i()->update( 'downloads_files', array( 'file_purchasable' => 0 ), array( 'file_id=?', $file['file_id'] ) );
				}
				/* Otherwise if there are other products that can be purchased, remove this product from the purchasable list */
				else
				{
					Db::i()->update( 'downloads_files', array( 'file_nexus' => implode( ',', $packages ) ), array( 'file_id=?', $file['file_id'] ) );
				}
			}
		}
	}
	
	/**
	 * Get items with permisison check
	 *
	 * @param array $where				Where clause
	 * @param string|null $order				MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit				Limit clause
	 * @param string $permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param int|bool|null $includeHiddenItems	Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param int $queryFlags			Select bitwise flags
	 * @param	Member|null	$member				The member (NULL to use currently logged in member)
	 * @param bool $joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly			If true will return the count
	 * @param array|null $joins				Additional arbitrary joins for the query
	 * @param bool|Model $skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param bool $joinTags			If true, will join the tags table
	 * @param bool $joinAuthor			If true, will join the members table for the author
	 * @param bool $joinLastCommenter	If true, will join the members table for the last commenter
	 * @param bool $showMovedLinks		If true, moved item links are included in the results
	 * @param array|null $location			Array of item lat and long
	 * @return	ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( array $where=array(), string $order=NULL, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems= Filter::FILTER_AUTOMATIC, int $queryFlags=0, ?Member $member=null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins=null, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, array|null $location=null ): ActiveRecordIterator|int
	{
		$member = $member ?: Member::loggedIn();

    	$where[] = "( p_member_groups='*' OR " . Db::i()->findInSet( 'p_member_groups', $member->groups ) . ' )';
		$where[] = array( 'p_store=?', 1 );

    	$return = parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
		$return->classname = 'IPS\nexus\Package';
		return $return;
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( ?string $action=NULL ): Url
	{
		$_key = $action ? md5( $action ) : NULL;

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = Url::internal( "app=nexus&module=store&controller=product&id={$this->id}", 'front', 'store_product', Friendly::seoTitle( Member::loggedIn()->language()->get( 'nexus_package_' . $this->id ) ) );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/**
	 * Should new reviews be moderated?
	 *
	 * @param	Member	$member							The member posting
	 * @param	bool		$considerPostBeforeRegistering	If TRUE, and $member is a guest, will check if a newly registered member would be moderated
	 * @return	bool
	 */
	public function moderateNewReviews( Member $member, bool $considerPostBeforeRegistering = FALSE ): bool
	{
		if ( $this->review_moderate )
		{
			return TRUE;
		}

		return $this->_moderateNewReviews( $member, $considerPostBeforeRegistering );
	}
	
	/**
	 * Images
	 *
	 * @return	Iterator
	 */
	public function images() : Iterator
	{
		return new Iterator( Db::i()->select( 'image_location', 'nexus_package_images', array( 'image_product=?', $this->id ),'image_primary desc' ), 'nexus_Products', NULL, TRUE );
	}
	
	/* !Embeddable */
	
	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		$memberCurrency = ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency() );
		$package = Package::load( $this->id );

		/* Do we have renewal terms? */
		$renewalTerm = NULL;
		$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
		if ( count( $renewOptions ) )
		{
			$renewalTerm = TRUE;
			if ( count( $renewOptions ) === 1 )
			{
				$renewalTerm = array_pop( $renewOptions );
				$renewalTerm = new RenewalTerm( new Money( $renewalTerm['cost'][ $memberCurrency ]['amount'], $memberCurrency ), new DateInterval( 'P' . $renewalTerm['term'] . mb_strtoupper( $renewalTerm['unit'] ) ), $package->tax ? Tax::load( $package->tax ) : NULL, $renewalTerm['add'] );
			}
		}

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'nexus', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'nexus' )->embedProduct( $this, $renewalTerm, $this->url()->setQueryString( $params ), $this->embedImage() );
	}
		
	/**
	 * Get image for embed
	 *
	 * @return	File|NULL
	 */
	public function embedImage(): ?File
	{
		return $this->_data['image'] ? File::get( 'nexus_Products', $this->_data['image'] ) : NULL;
	}

	/**
	 * Get mapped value
	 *
	 * @param string $key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( string $key ): mixed
	{
		if ( $key === 'title' )
		{
			return $this->title;
		}
		elseif ( $key === 'date' )
		{
			return $this->date_added;
		}
		return parent::mapped($key);
	}
	
	/**
	 * Supported Meta Data Types
	 *
	 * @return	array
	 */
	public static function supportedMetaDataTypes(): array
	{
		return array();
	}
	
	/* !Search */

	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		return array( 'p_id', 'p_base_price', 'p_reviews', 'p_discounts', 'p_renew_options', 'p_tax', 'p_stock', 'p_initial_term' );
	}
	
	
	/**
	 * Query to get additional data for search result / stream view
	 *
	 * @param	array	$items	Item data (will be an array containing values from basicDataColumns())
	 * @return	array
	 */
	public static function searchResultExtraData( array $items ): array
	{
		$images = iterator_to_array( Db::i()->select( array( 'image_product', 'image_location' ), 'nexus_package_images', array( array( Db::i()->in( 'image_product', array_keys( $items ) ) ), array( 'image_primary=1' ) ) )->setKeyField( 'image_product' )->setValueField( 'image_location' ) );
		
		$taxIds = array();
		foreach ( $items as $data )
		{
			if ( $data['p_tax'] )
			{
				$taxIds[ $data['p_tax'] ] = $data['p_tax'];
			}
		}
		
		$taxData = array();
		if ( $taxIds )
		{
			$taxData = iterator_to_array( Db::i()->select( '*', 'nexus_tax', Db::i()->in( 't_id', $taxIds ) )->setKeyField('t_id') );
		}
				
		$return = array();
		foreach ( $items as $k => $data )
		{
			$return[ $k ]['image'] = $images[$k] ?? NULL;
			$return[ $k ]['tax'] = ( $data['p_tax'] and isset( $taxData[ $data['p_tax'] ] ) ) ? $taxData[ $data['p_tax'] ] : NULL;
		}
		
		return $return;
	}

	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( string $type, Member $member = NULL, Model $container = NULL ): bool
	{
		/* Commerce Items have no own content type, so we can use only the general mod permissions */
		$member = $member ?: Member::loggedIn();
		if ( in_array( $type, array( 'hide', 'unhide', 'delete' ) ) and $container )
		{
			if ( $member->modPermission( "can_{$type}" ) )
			{
				return TRUE;
			}
		}

		return parent::modPermission( $type, $member, $container );
	}

	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 */
	public static function incrementPostCount( Model $container = NULL ): bool
	{
		return FALSE;
	}

	/**
	 * Check if a specific action is available for this Content.
	 * Default to TRUE, but used for overrides in individual Item/Comment classes.
	 *
	 * @param string $action
	 * @param Member|null	$member
	 * @return bool
	 */
	public function actionEnabled( string $action, ?Member $member=null ) : bool
	{
		switch( $action )
		{
			case 'edit':
			case 'move':
				return FALSE;

			case 'review':
				if ( !$this->reviewable )
				{
					return FALSE;
				}

				$member = $member ?: Member::loggedIn();
				if ( !Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_member=?', 'nexus', 'package', $this->id, $member->member_id ) )->first() )
				{
					return FALSE;
				}
				break;
		}

		return parent::actionEnabled( $action, $member );
	}

	/**
	 * Do Moderator Action
	 *
	 * @param	string				$action	The action
	 * @param	Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param	string|NULL			$reason	Reason (for hides)
	 * @param	bool				$immediately	Delete immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction( string $action, Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		throw new InvalidArgumentException;
	}

	/**
	 * Returns the meta description
	 *
	 * @param string|null $return	Specific description to use (useful for paginated displays to prevent having to run extra queries)
	 * @return	string
	 * @throws	BadMethodCallException
	 */
	public function metaDescription( string $return = NULL ): string
	{
		return Member::loggedIn()->language()->addToStack("nexus_package_{$this->id}_desc", FALSE, array( 'striptags' => TRUE, 'removeNewlines' => TRUE ) );
	}

	/**
	 * Get container
	 *
	 * @return	Model
	 * @note	Certain functionality requires a valid container but some areas do not use this functionality (e.g. messenger)
	 * @throws	OutOfRangeException|BadMethodCallException
	 */
	public function container(): Model
	{
		if( $this->custom > 0 )
		{
			throw new BadMethodCallException;
		}

		return parent::container();
	}

	/**
	 * Content is hidden?
	 *
	 * @return	int
	 *	@li -3 is a post made by a guest using the "post before register" feature
	 *	@li -2 is pending deletion
	 * 	@li	-1 is hidden having been hidden by a moderator
	 * 	@li	0 is unhidden
	 *	@li	1 is hidden needing approval
	 * @note	The actual column may also contain 2 which means the item is hidden because the parent is hidden, but it is not hidden in itself. This method will return -1 in that case.
	 *
	 * @note    A piece of content (item and comment) can have an alias for hidden OR approved.
	 *          With hidden: 0=not hidden, 1=hidden (needs moderator approval), -1=hidden by moderator, 2=parent item is hidden, -2=pending deletion, -3=guest post before register
	 *          With approved: 1=not hidden, 0=hidden (needs moderator approval), -1=hidden by moderator, -2=pending deletion, -3=guest post before register
	 *
	 *          User posting has moderator approval set: When adding an unapproved ITEM (approved=0, hidden=1) you should *not* increment container()->_comments but you should update container()->_unapprovedItems
	 *          User posting has moderator approval set: When adding an unapproved COMMENT (approved=0, hidden=1) you should *not* increment item()->num_comments in item or container()->_comments but you should update item()->unapproved_comments and container()->_unapprovedComments
	 *
	 *          User post is hidden by moderator (approved=-1, hidden=0) you should decrement item()->num_comments and decrement container()->_comments but *not* increment item()->unapproved_comments or container()->_unapprovedComments
	 *          User item is hidden by a moderator (approved=-1, hidden=0) you should decrement container()->comments and subtract comment count from container()->_comments, but *not* increment container()->_unapprovedComments
	 *
	 *          Moderator hides item (approved=-1, hidden=-1) you should substract num_comments from container()->_comments. Comments inside item are flagged as approved=-1, hidden=2 but item()->num_comments should not be substracted from
	 *
	 *          Comments with a hidden value of 2 should increase item()->num_comments but not container()->_comments
	 * @throws	RuntimeException
	 */
	public function hiddenStatus(): int
	{
		return 0;
	}
}
