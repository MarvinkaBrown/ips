<?php
/**
 * @brief		File Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		8 Oct 2013
 */

namespace IPS\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use CachingIterator;
use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\Content\Anonymous;
use IPS\Content\Comment;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Followable;
use IPS\Content\Hideable;
use IPS\Content\Item;
use IPS\Content\ItemTopic;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Featurable;
use IPS\Content\Pinnable;
use IPS\Content\Reactable;
use IPS\Content\ReadMarkers;
use IPS\Content\Reportable;
use IPS\Content\Shareable;
use IPS\Content\Statistics;
use IPS\Content\ViewUpdates;
use IPS\Content\Taggable;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\downloads\extensions\nexus\Item\File as FileNexusItem;
use IPS\downloads\File\PendingVersion;
use IPS\File as SystemFile;
use IPS\File\Iterator;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Menu;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Math\Number;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Customer;
use IPS\nexus\Form\Money as FormMoney;
use IPS\nexus\Form\RenewalTerm;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Purchase\RenewalTerm as PurchaseRenewalTerm;
use IPS\nexus\Tax;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Output;
use IPS\Output\Plugin\Filesize;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfBoundsException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function array_slice;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_string;
use const IPS\NOTIFICATION_BACKGROUND_THRESHOLD;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Model
 */
class File extends Item implements Embeddable,
Filter
{
	use Reactable,
		Reportable,
		Pinnable,
		Anonymous,
		Followable,
		Lockable,
		ItemTopic,
		MetaData,
		Shareable,
		Taggable,
		EditHistory,
		ReadMarkers,
		Hideable,
		Statistics,
		ViewUpdates,
		Featurable
		{
			Hideable::unhide as public _unhide;
			Hideable::sendApprovedNotification as public _sendApprovedNotification;
			Hideable::logDelete as public _logDelete;
			ItemTopic::syncTopic as public _syncTopic;
		}
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'downloads';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'downloads';
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'downloads_files';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'file_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	Node Class
	 */
	public static ?string $containerNodeClass = 'IPS\downloads\Category';
	
	/**
	 * @brief	Comment Class
	 */
	public static ?string $commentClass = 'IPS\downloads\File\Comment';
	
	/**
	 * @brief	Review Class
	 */
	public static string $reviewClass = 'IPS\downloads\File\Review';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'container'				=> 'cat',
		'author'				=> 'submitter',
		'author_name'			=> 'author_name',
		'views'					=> 'views',
		'title'					=> 'name',
		'content'				=> 'desc',
		'num_comments'			=> 'comments',
		'unapproved_comments'	=> 'unapproved_comments',
		'hidden_comments'		=> 'hidden_comments',
		'num_reviews'			=> 'reviews',
		'unapproved_reviews'	=> 'unapproved_reviews',
		'hidden_reviews'		=> 'hidden_reviews',
		'last_comment'			=> 'last_comment',
		'last_review'			=> 'last_review',
		'date'					=> 'submitted',
		'updated'				=> 'updated',
		'rating'				=> 'rating',
		'approved'				=> 'open',
		'approved_by'			=> 'approver',
		'approved_date'			=> 'approvedon',
		'pinned'				=> 'pinned',
		'featured'				=> 'featured',
		'locked'				=> 'locked',
		'ip_address'			=> 'ipaddress',
		'meta_data'				=> 'meta_data',
		'edit_time'				=> 'edit_time',
		'edit_member_name'		=> 'edit_name',
		'edit_show'				=> 'append_edit',
		'edit_reason'			=> 'edit_reason',
		'is_anon'				=> 'is_anon',
		'last_comment_anon'		=> 'last_comment_anon',
		'item_topicid'			=> 'topicid'
	);
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'downloads_file';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'download';
	
	/**
	 * @brief	Form Lang Prefix
	 */
	public static string $formLangPrefix = 'file_';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'downloads-file';
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		$return = parent::basicDataColumns();
		$return[] = 'file_primary_screenshot';
		$return[] = 'file_version';
		$return[] = 'file_downloads';
		$return[] = 'file_cost';
		$return[] = 'file_nexus';
		return $return;
	}
	
	/**
	 * Query to get additional data for search result / stream view
	 *
	 * @param	array	$items	Item data (will be an array containing values from basicDataColumns())
	 * @return	array
	 */
	public static function searchResultExtraData( array $items ): array
	{
		$screenshotIds = array();
		foreach ( $items as $itemData )
		{
			if ( $itemData['file_primary_screenshot'] )
			{
				$screenshotIds[] = $itemData['file_primary_screenshot'];
			}
		}
		
		if ( count( $screenshotIds ) )
		{
			return iterator_to_array( Db::i()->select( array( 'record_file_id', 'record_location', 'record_thumb' ), 'downloads_files_records', Db::i()->in( 'record_id', $screenshotIds ) )->setKeyField( 'record_file_id' ) );
		}
		
		return array();
	}
		
	/**
	 * Set name
	 *
	 * @param string $name	Name
	 * @return	void
	 */
	public function set_name( string $name ) : void
	{
		$this->_data['name'] = $name;
		$this->_data['name_furl'] = Friendly::seoTitle( $name );
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_furl(): string
	{
		if( !$this->_data['name_furl'] )
		{
			$this->name_furl	= Friendly::seoTitle( $this->name );
			$this->save();
		}

		return $this->_data['name_furl'] ?: Friendly::seoTitle( $this->name );
	}

	/**
	 * Get primary screenshot ID
	 *
	 * @return	int|null
	 */
	public function get__primary_screenshot(): ?int
	{
		return ( isset( $this->_data['primary_screenshot'] ) ) ? $this->_data['primary_screenshot'] : NULL;
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=downloads&module=downloads&controller=view&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'downloads_file';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'name_furl';
	
	/**
	 * Get URL for last comment page
	 *
	 * @return	Url
	 */
	public function lastCommentPageUrl(): Url
	{
		return parent::lastCommentPageUrl()->setQueryString( 'tab', 'comments' );
	}
	
	/**
	 * Get URL for last review page
	 *
	 * @return	Url
	 */
	public function lastReviewPageUrl(): Url
	{
		return parent::lastReviewPageUrl()->setQueryString( 'tab', 'reviews' );
	}
	
	/**
	 * Get template for content tables
	 *
	 * @return	array
	 */
	public static function contentTableTemplate(): array
	{
		/* Load our CSS */
		\IPS\downloads\Application::outputCss();
		return array( Theme::i()->getTemplate( 'browse', 'downloads', 'front' ), 'rows' );
	}

	/**
	 * HTML to manage an item's follows 
	 *
	 * @return	array
	 */
	public static function manageFollowRows(): array
	{		
		return array( Theme::i()->getTemplate( 'global', 'downloads', 'front' ), 'manageFollowRow' );
	}

	/**
	 * Files
	 */
	protected array $_files = array();

	/**
	 * Get files
	 *
	 * @param int|null $version		If provided, will get the file records for a specific previous version (downloads_filebackup.b_id)
	 * @param bool $includeLinks	If true, will include linked files
	 * @param bool $pendingVersion	Only include files from a pending new version
	 * @return	Iterator
	 */
	public function files( int $version=NULL, bool $includeLinks=TRUE, bool $pendingVersion=FALSE ): Iterator
	{
		if( isset( $this->_files[ (int) $version ] ) )
		{
			return $this->_files[ (int) $version ];
		}

		$where = $includeLinks ? array( array( 'record_file_id=? AND ( record_type=? OR record_type=? )', $this->id, 'upload', 'link' ) ) : array( array( 'record_file_id=? AND record_type=?', $this->id, 'upload' ) );
		if ( $version !== NULL )
		{
			try
			{
				$backup = Db::i()->select( 'b_records', 'downloads_filebackup', array( 'b_id=?', $version ) )->first();
				$where[] = Db::i()->in( 'record_id', explode( ',', $backup ) );
			}
			catch( UnderflowException $e )
			{
				/* Default to current version if the previous version does not exist */
				$where[] = array( 'record_backup=0' );
			}
		}
		else
		{
			$where[] = array( 'record_backup=0' );
		}

		/* Exclude future versions */
		$where[] = array( 'record_hidden=?', $pendingVersion );
						
		$iterator = Db::i()->select( '*', 'downloads_files_records', $where )->setKeyField( 'record_id' );
		$iterator = new Iterator( $iterator, 'downloads_Files', 'record_location', FALSE, 'record_realname', 'record_size' );

		$this->_files[ (int) $version ]	= $iterator;
		return $this->_files[ (int) $version ];
	}
	
	/**
	 * Total filesize
	 */
	protected ?int $_filesize = NULL;
		
	/**
	 * Get Total filesize
	 *
	 * @return	int|null
	 */
	public function filesize(): ?int
	{
		if ( $this->_filesize === NULL )
		{
			$this->_filesize = Db::i()->select( 'SUM(record_size)', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $this->id, 'upload' ) )->first();
		}
		
		return $this->_filesize;
	}
	
	/**
	 * Is this a paid file?
	 *
	 * @return	bool
	 */
	public function isPaid(): bool
	{
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
		{
			if ( $this->nexus )
			{
				return TRUE;
			}
			
			if ( $this->cost )
			{
				$costs = json_decode( $this->cost, TRUE );
				if ( is_array( $costs ) )
				{
					foreach ( $costs as $currency => $data )
					{
						if ( $data['amount'] )
						{
							return TRUE;
						}
					}
				}
				else
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Is Purchasable?
	 *
	 * @param	bool	$checkPaid	Check if the file is a paid file
	 * @return	bool
	 */
	public function isPurchasable( bool $checkPaid = TRUE ): bool
	{
		/* If it's not a paid file, then it's not purchasable */
		if ( $checkPaid and !$this->isPaid() )
		{
			return FALSE;
		}
		
		return (bool) $this->purchasable;
	}
	
	/**
	 * Can enable purchases?
	 *
	 * @param	Member|NULL	$member	Member to check, or NULL for currently logged in member
	 * @return	bool
	 */
	public function canEnablePurchases( ?Member $member = NULL ): bool
	{
		if ( !$this->isPaid() )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		if( !$member->modPermission('can_make_purchasable') )
		{
			return FALSE;
		}

		/* If there isn't an existing product record, do not allow purchases to be re-enabled */
		if ( $this->nexus )
		{
			$productIds	= explode( ',', $this->nexus );
			$hasProduct	= false;

			foreach ( $productIds as $productId )
			{
				try
				{
					Package::load( $productId );

					$hasProduct = true;
					break;
				}
				catch ( OutOfRangeException $e ) { }
			}

			if( !$hasProduct )
			{
				return FALSE;
			}
		}

		return TRUE;
	}
	
	/**
	 * Can disable purchases?
	 *
	 * @param	Member|NULL	$member	Member to check, or NULL for currently logged in member
	 * @return	bool
	 */
	public function canDisablePurchases( ?Member $member = NULL ): bool
	{
		if ( !$this->isPaid() )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		return (bool) $member->modPermission('can_make_unpurchasable');
	}
	
	/**
	 * Get Price
	 *
	 * @param Customer|null $customer
	 * @return	Money|NULL
	 */
	public function price( ?Customer $customer=null ): ?Money
	{
		return static::_price( $this->cost, $this->nexus, $customer );
	}

	/**
	 * Get Price
	 *
	 * @param string|null $cost The cost
	 * @param string|null $nexusPackageIds Comma-delimited list of associated package IDs
	 * @param Customer|null $customer
	 * @return    Money|NULL
	 */
	public static function _price( ?string $cost, ?string $nexusPackageIds, ?Customer $customer=null ): ?Money
	{
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
		{
			if ( $nexusPackageIds )
			{
				$packages = explode( ',', $nexusPackageIds );
				try
				{
					return Package::lowestPriceToDisplay( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_packages', Db::i()->in( 'p_id', $packages ) ), 'IPS\nexus\Package' ), null, true );
				}
				catch ( OutOfRangeException | OutOfBoundsException $e ) { }

				return NULL;
			}
			
			if ( $cost )
			{
				$customer = $customer ?: Customer::loggedIn();
				$currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : $customer->defaultCurrency();
				
				/* If $cost is an empty JSON array, the conditional will evaluate false thus resulting in [] being passed to \IPS\nexus\Money (which will fail). */
				$costs = json_decode( $cost, TRUE );
				if ( is_array( $costs ) )
				{
					if ( isset( $costs[ $currency ]['amount'] ) and $costs[ $currency ]['amount'] )
					{
						return new Money( $costs[ $currency ]['amount'], $currency );
					}
				}
				else
				{
					return new Money( $cost, $currency );
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 * @brief	Number of purchases
	 */
	protected static ?array $purchaseCounts = NULL;
	
	/**
	 * Get number of purchases
	 *
	 * @return	int|null
	 */
	public function purchaseCount(): ?int
	{
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on AND !$this->nexus )
		{
			if ( static::$purchaseCounts === NULL )
			{
				static::$purchaseCounts = iterator_to_array( Db::i()->select( 'COUNT(*) AS count, ps_item_id', 'nexus_purchases', array( array( 'ps_app=? AND ps_type=?', 'downloads', 'file' ), Db::i()->in( 'ps_item_id', array_keys( static::$multitons ) ) ), NULL, NULL, 'ps_item_id' )->setKeyField('ps_item_id')->setValueField('count') );
				foreach ( array_keys( static::$multitons ) as $k )
				{
					if ( !isset( static::$purchaseCounts[ $k ] ) )
					{
						static::$purchaseCounts[ $k ] = 0;
					}
				}
			}
			
			if ( !isset( static::$purchaseCounts[ $this->id ] ) )
			{
				static::$purchaseCounts[ $this->id ] = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'downloads', 'file', $this->id ) )->first();
			}
			
			return static::$purchaseCounts[ $this->id ];
		}
		
		return NULL;
	}
	
	/**
	 * Get Renewal Term
	 *
	 * @param Customer|null	$customer
	 * @return    PurchaseRenewalTerm|NULL
	 */
	public function renewalTerm( ?Customer $customer = null ): ?PurchaseRenewalTerm
	{
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on and $this->renewal_term )
		{
			$customer = $customer ?: Customer::loggedIn();
			$renewalPrice = json_decode( $this->renewal_price, TRUE );
			$renewalPrice = is_array( $renewalPrice ) ? $renewalPrice[ $customer->defaultCurrency() ] : array( 'currency' => $customer->defaultCurrency(), 'amount' => $renewalPrice );
			
			$tax = NULL;
			try
			{
				$tax = Settings::i()->idm_nexus_tax ? Tax::load( Settings::i()->idm_nexus_tax ) : NULL;
			}
			catch ( OutOfRangeException $e ) { }
			
			return new PurchaseRenewalTerm( new Money( $renewalPrice['amount'], $renewalPrice['currency'] ), new DateInterval( "P{$this->renewal_term}" . mb_strtoupper( $this->renewal_units ) ), $tax );
		}
		
		return NULL;
	}

	/**
	 * @brief	Cache of normal screenshots
	 */
	protected ?CachingIterator $_screenshotsNormal = NULL;

	/**
	 * @brief	Cache of thumbnails
	 */
	protected ?CachingIterator $_screenshotsThumbs = NULL;

	/**
	 * @brief	Cache of original screenshot images
	 */
	protected ?CachingIterator $_screenshotsOriginal = NULL;
	
	/**
	 * Get screenshots
	 *
	 * @param	int			$type			0 = Normal, 1 = Thumbnails, 2 = No watermark
	 * @param	bool		$includeLinks	If true, will include linked files
	 * @param	int|NULL	$version		If provided, will get the file records for a specific previous version (downloads_filebackup.b_id)
	 * @param 	bool		$pendingVersion	Only show screenshots from new pending version
	 * @return	CachingIterator
	 */
	public function screenshots( int $type=0, bool $includeLinks=TRUE, ?int $version = NULL, bool $pendingVersion=FALSE ) : CachingIterator
	{
		$fileSizeField = null;
		switch ( $type )
		{
			case 0:
				if( $this->_screenshotsNormal !== NULL AND $pendingVersion === FALSE )
				{
					return $this->_screenshotsNormal;
				}
				$valueField = 'record_location';
				$property	= "_screenshotsNormal";
				$fileSizeField = 'record_size';
				break;
			case 1:
				if( $this->_screenshotsThumbs !== NULL AND $pendingVersion === FALSE )
				{
					return $this->_screenshotsThumbs;
				}
				$valueField = function( $row ) { return ( $row['record_type'] == 'sslink' ) ? 'record_location' : 'record_thumb'; };
				$property	= "_screenshotsThumbs";
				$fileSizeField = FALSE;
				break;
			case 2:
				if( $this->_screenshotsOriginal !== NULL AND $pendingVersion === FALSE )
				{
					return $this->_screenshotsOriginal;
				}
				$valueField = function( $row ) { return $row['record_no_watermark'] ? 'record_no_watermark' : 'record_location'; };
				$property	= "_screenshotsOriginal";
				break;
			default:
				throw new InvalidArgumentException;
		}
		
		$where = array( array( 'record_file_id=?', $this->id ) );
		
		if ( $includeLinks )
		{
			$where[] = array( '( record_type=? OR record_type=? )', 'ssupload', 'sslink' );
		}
		else
		{
			$where[] = array( 'record_type=?', 'ssupload' );
		}
		
		if ( $version !== NULL )
		{
			$backup = Db::i()->select( 'b_records', 'downloads_filebackup', array( 'b_id=?', $version ) )->first();
			$where[] = Db::i()->in( 'record_id', explode( ',', $backup ) );
		}
		else
		{
			$where[] = array( 'record_backup=0' );
		}

		/* Ignore future versions */
		$where[] = array( 'record_hidden=?', $pendingVersion );

		$iterator = Db::i()->select( 'record_id, record_location, record_thumb, record_no_watermark, record_default, record_type, record_realname, record_size', 'downloads_files_records', $where, 'record_time DESC' )->setKeyField( 'record_id' );
		$iterator = new Iterator( $iterator, 'downloads_Screenshots', $valueField, FALSE, 'record_realname', $fileSizeField );
		$iterator = new CachingIterator( $iterator, CachingIterator::FULL_CACHE );

		/* Do not cache if loading pending version data */
		if( $pendingVersion === TRUE )
		{
			return $iterator;
		}

		$this->$property	= $iterator;
		return $this->$property;
	}
	
	/**
	 * Returns the content images
	 *
	 * @param	int|null	$limit				Number of attachments to fetch, or NULL for all
	 * @param	bool		$ignorePermissions	If set to TRUE, permission to view the images will not be checked
	 * @return	array|NULL
	 * @throws	BadMethodCallException
	 */
	public function contentImages( ?int $limit = NULL, bool $ignorePermissions = FALSE ): array|null
	{
		$count = 0;
		$images = array();

		foreach( $this->screenshots( 0, FALSE ) as $image )
		{
			if ( $count >= $limit )
			{
				break;
			}
			
			$images[] = array( 'downloads_Screenshots' => (string) $image );
			$count++;
		}

		if( $count < $limit )
		{
			$contentImages = parent::contentImages( $limit, $ignorePermissions ) ?: array();
			$images = array_merge( $images, $contentImages );
		}

		return count( $images ) ? array_slice( $images, 0, $limit ) : NULL;
	}
	
	/**
	 * @brief Cached primary screenshot
	 */
	protected SystemFile|bool|null $_primaryScreenshot	= FALSE;

	/**
	 * Get primary screenshot
	 *
	 * @return	SystemFile|bool|null
	 */
	public function get_primary_screenshot(): SystemFile|bool|null
	{
		if( $this->_primaryScreenshot !== FALSE )
		{
			return $this->_primaryScreenshot;
		}
		
		/* isset() here returns FALSE if the value is null, which then results in the else block here never being triggered to pull the first screenshot it finds */
		if ( array_key_exists( 'primary_screenshot', $this->_data ) )
		{
			$screenshots = $this->screenshots();
			if ( $this->_data['primary_screenshot'] and isset( $screenshots[ $this->_data['primary_screenshot'] ] ) )
			{
				$this->_primaryScreenshot	= $screenshots[ $this->_data['primary_screenshot'] ];
				return $this->_primaryScreenshot;
			}
			else
			{
				foreach ( $screenshots as $id => $screenshot )
				{
					if ( !$this->_data['primary_screenshot'] or $id === $this->_data['primary_screenshot'] )
					{
						$this->_primaryScreenshot	= $screenshot;
						return $this->_primaryScreenshot;
					}
				}
			}
		}

		$this->_primaryScreenshot	= NULL;
		return $this->_primaryScreenshot;
	}

	/**
	 * @brief Cached primary screenshot thumb
	 */
	protected mixed $_primaryScreenshotThumb	= FALSE;

	/**
	 * Get primary screenshot thumbnail
	 *
	 * @return SystemFile|bool|null
	 */
	public function get_primary_screenshot_thumb(): SystemFile|bool|null
	{
		if( $this->_primaryScreenshotThumb !== FALSE )
		{
			return $this->_primaryScreenshotThumb;
		}

		$screenshots = $this->screenshots( 1 );
		if ( isset( $this->_data['primary_screenshot'] ) and isset( $screenshots[ $this->_data['primary_screenshot'] ] ) )
		{
			$this->_primaryScreenshotThumb	= $screenshots[ $this->_data['primary_screenshot'] ];
			return $this->_primaryScreenshotThumb;
		}
		else
		{
			foreach( $screenshots as $id => $screenshot )
			{
				if ( !$this->_data['primary_screenshot'] or $id === $this->_data['primary_screenshot'] )
				{
					$this->_primaryScreenshotThumb	= $screenshot;
					return $this->_primaryScreenshotThumb;
				}
			}
		}

		$this->_primaryScreenshotThumb	= NULL;
		return $this->_primaryScreenshotThumb;
	}
		
	/**
	 * @brief	Custom Field Cache
	 */
	protected ?array $_customFields = NULL;
	
	/**
	 * @brief	Field Data Cache
	 */
	protected ?array $_fieldData = NULL;
	
	/**
	 * Get custom field values
	 *
	 * @param bool $topic	Are we returning the custom fields for the topic? If so we need to apply the display formatting.
	 * @return	array
	 */
	public function customFields( bool $topic=FALSE ): array
	{
		$return = array();
		$fields = $this->container()->cfields;

		if( $topic === TRUE AND $this->_fieldData === NULL )
		{
			$this->_fieldData	= iterator_to_array( Db::i()->select( '*', 'downloads_cfields', array( 'cf_topic=?', 1 ) )->setKeyField( 'cf_id' ) );
		}

		try
		{
			if ( $this->_customFields === NULL )
			{
				$this->_customFields = Db::i()->select( '*', 'downloads_ccontent', array( 'file_id=?', $this->id ) )->first();
			}
			
			foreach ( $this->_customFields as $k => $v )
			{
				$fieldId = str_replace( 'field_', '', $k );

				/* If we're getting fields for the topic we need to skip any that are set to not be included */
				if( $topic === TRUE and !isset( $this->_fieldData[ $fieldId ] ) )
				{
					continue;
				}

				if ( array_key_exists( $fieldId, $fields ) )
				{
					if( $topic === TRUE )
					{
						$thisField = Field::constructFromData( $this->_fieldData[ $fieldId ] );

						if( isset( $this->_fieldData[ $fieldId ] ) )
						{
							$v	= str_replace( '{content}', htmlspecialchars( $v, ENT_DISALLOWED, 'UTF-8', FALSE ), $thisField->displayValue( $v ) );
							$v	= str_replace( '{member_id}', Member::loggedIn()->member_id, $v );
							$v	= str_replace( '{title}', Member::loggedIn()->language()->addToStack( 'downloads_field_' . $fieldId ), $v );
						}
						else
						{
							$v	= htmlspecialchars( $v, ENT_DISALLOWED, 'UTF-8', FALSE );
						}
					}

					$return[ $k ] = $v;
				}
			}
		}
		catch( UnderflowException $e ){}
		
		return $return;
	}
	
	/**
	 * Get available comment/review tabs
	 *
	 * @return	array
	 */
	public function commentReviewTabs(): array
	{
		$tabs = array();
		if ( $this->container()->bitoptions['reviews'] )
		{
			$tabs['reviews'] = Member::loggedIn()->language()->addToStack( 'file_review_count', TRUE, array( 'pluralize' => array( $this->mapped('num_reviews') ) ) );
		}
		if ( $this->container()->bitoptions['comments'] )
		{
			$tabs['comments'] = Member::loggedIn()->language()->addToStack( 'file_comment_count', TRUE, array( 'pluralize' => array( $this->mapped('num_comments') ) ) );
		}
				
		return $tabs;
	}

	/**
	 * Get comment/review output
	 *
	 * @param string|null $tab Active tab
	 * @return    string
	 */
	public function commentReviews( ?string $tab=NULL ): string
	{
		if ( $tab === 'reviews' )
		{
			return (string) Theme::i()->getTemplate('view')->reviews( $this );
		}
		elseif( $tab === 'comments' )
		{
			return (string) Theme::i()->getTemplate('view')->comments( $this );
		}
		
		return '';
	}
	
	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( ?Member $member=null ): bool
	{
		$member = $member ?: Member::loggedIn();
		if ( !$this->container()->open and !$member->isAdmin() )
		{
			return FALSE;
		}
		return parent::canView( $member );
	}
	
	/**
	 * Can edit?
	 * Authors can always edit their own files
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( ?Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		if ( !$member->member_id )
		{
			return FALSE;
		}

		return ( $member->member_id == $this->author()->member_id ) or parent::canEdit( $member );
	}

	/**
	 * Get items with permisison check
	 *
	 * @param array $where Where clause
	 * @param string|null $order MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit Limit clause
	 * @param string $permissionKey A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param int|bool|null $includeHiddenItems Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param int $queryFlags Select bitwise flags
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param bool $joinContainer If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly If true will return the count
	 * @param array|null $joins Additional arbitrary joins for the query
	 * @param bool|Model $skipPermission If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param bool $joinTags If true, will join the tags table
	 * @param bool $joinAuthor If true, will join the members table for the author
	 * @param bool $joinLastCommenter If true, will join the members table for the last commenter
	 * @param bool $showMovedLinks If true, moved item links are included in the results
	 * @param array|null $location Array of item lat and long
	 * @return    ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( array $where=array(), string $order=null, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member $member=null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins=null, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, array|null $location=null ): ActiveRecordIterator|int
	{
		$member = $member ?: Member::loggedIn();
		if ( !$member->isAdmin() )
		{
			$where[] = array( 'downloads_categories.copen=1' );
			$joinContainer = TRUE;
		}
				
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
	}
	
	/**
	 * Can a given member create this type of content?
	 *
	 * @param	Member	$member		The member
	 * @param	Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param bool $showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( Member $member, ?Model $container=null, bool $showError=FALSE ) : bool
	{
		if ( $member->idm_block_submissions )
		{
			if ( $showError )
			{
				Output::i()->error( 'err_submissions_blocked', '1D168/1', 403, '' );
			}
			
			return FALSE;
		}

		return parent::canCreate( $member, $container, $showError );
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
		if( $action == 'review' and $this->mustDownloadBeforeReview( $member ) )
		{
			return false;
		}

		return parent::actionEnabled( $action, $member );
	}
	
	/**
	 * Member has to download before they can review?
	 *
	 * @param	Member|NULL	$member		The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function mustDownloadBeforeReview( ?Member $member = NULL ): bool
	{
		if ( $this->container()->bitoptions['reviews_download'] )
		{
			try
			{
				Db::i()->select( '*', 'downloads_downloads', array( 'dfid=? AND dmid=?', $this->id, $member ? $member->member_id : Member::loggedIn()->member_id ) )->first();
			}
			catch ( UnderflowException $e )
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Change Author
	 *
	 * @param	Member	$newAuthor	The new author
	 * @param bool $log		If TRUE, action will be logged to moderator log
	 * @return	void
	 */
	public function changeAuthor( Member $newAuthor, bool $log=TRUE ): void
	{
		if ( Application::appIsEnabled( 'nexus' ) )
		{
			Db::i()->update( 'nexus_purchases', array( 'ps_pay_to' => $newAuthor->member_id ), array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'downloads', 'file', $this->id ) );
		}

		/* Update any pending versions */
		Db::i()->update( 'downloads_files_pending', array( 'pending_member_id' => $newAuthor->member_id ), array( 'pending_file_id=?', $this->id ) );

		parent::changeAuthor( $newAuthor, $log );
	}
	
	/**
	 * @brief	Can download?
	 */
	protected ?bool $canDownload = NULL;
	
	/**
	 * Can the member download this file?
	 *
	 * @param	Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canDownload( ?Member $member = NULL ): bool
	{
		if ( $this->canDownload === NULL )
		{
			try
			{
				$this->downloadCheck( NULL, $member );
				$this->canDownload = TRUE;
			}
			catch ( DomainException $e )
			{
				$this->canDownload = FALSE;
			}
		}
		
		return $this->canDownload;
	}

	/**
	 * @brief	Requires download confirmation? Used to determine if CSRF key should be included in download URL.
	 */
	protected ?bool $requiresDownloadConfirmation = NULL;
	
	/**
	 * Can the member download this file?
	 *
	 * @param	Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function requiresDownloadConfirmation( ?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		if ( $this->requiresDownloadConfirmation === NULL )
		{
			$this->requiresDownloadConfirmation = FALSE;

			$category = $this->container();
			
			if( $category->message('disclaimer') and in_array( $category->disclaimer_location, [ 'download', 'both' ] ) )
			{
				$this->requiresDownloadConfirmation = TRUE;
			}

			$version = Request::i()->changelog ?? Request::i()->version ?? NULL;
			$files = $this->files($version);

			if( count( $files ) > 1 AND !isset( Request::i()->r ) )
			{
				$this->requiresDownloadConfirmation = TRUE;
			}

			if( $member->group['idm_wait_period'] AND ( !$this->isPaid() OR $member->group['idm_paid_restrictions'] ) )
			{
				$this->requiresDownloadConfirmation = TRUE;
			}
		}
		
		return $this->requiresDownloadConfirmation;
	}
	
	/**
	 * Can the member buy this file?
	 *
	 * @param	Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canBuy( ?Member $member = NULL ): bool
	{
		/* Is this a paid file? */
		if ( !$this->isPaid() )
		{
			return FALSE;
		}
		
		/* Init */
		$member = $member ?: Member::loggedIn();
		$restrictions = json_decode( $member->group['idm_restrictions'], TRUE );

        /* File author */
        if( $member === $this->author() )
        {
            return FALSE;
        }
		
		/* Basic permission check */
		if ( !$this->container()->can( 'download', $member ) )
		{
			/* Hold on - if we're a guest and buying means we'll have to register which will put us in a group with permission, we can continue */
			if ( $member->member_id or !$this->container()->can( 'download', Group::load( Settings::i()->member_group ) ) )
			{
				return FALSE;
			}
		}
		
		/* If restrictions aren't applying to Paid files, stop here */
		if ( !$member->group['idm_paid_restrictions'] )
		{
			return TRUE;
		}
		
		/* Minimum posts */
		if ( $member->member_id and $restrictions['min_posts'] and $restrictions['min_posts'] > $member->member_posts )
		{
			return FALSE;
		}

		/* Is this an associated file ? */
		if ( $this->nexus )
		{
			$productIds = explode( ',', $this->nexus );

			foreach ( $productIds as $productId )
			{
				try
				{
					$package = Package::load( $productId );

					try
					{
						/* The method does not return anything, but throws an exception if we cannot buy */
						$package->memberCanPurchase( $member );
					}
					catch ( DomainException $e )
					{
						return FALSE;
					}
				}
				catch ( OutOfRangeException $e )
				{
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Purchases that can be renewed
	 *
	 * @param	Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	array
	 */
	public function purchasesToRenew( ?Member $member = NULL ): array
	{
		/** return an empty array if we don't have commerce */
		if ( !Application::appIsEnabled( 'nexus' ) )
		{
			return array();
		}
		$member = $member ?: Member::loggedIn();
		
		$return = array();

		foreach ( FileNexusItem::getPurchases( Customer::load( $member->member_id ), $this->id ) as $purchase )
		{
			if ( !$purchase->active and $purchase->canRenewUntil() !== FALSE )
			{
				$return[] = $purchase;
			}
		}
		return $return;
	}
	
	/**
	 * Download check
	 *
	 * @param	array|NULL			$record		Specific record to download
	 * @param	Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	void
	 * @throws	DomainException
	 */
	public function downloadCheck( ?array $record = NULL, ?Member $member = NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		$restrictions = $member->group['idm_restrictions'] ? json_decode( $member->group['idm_restrictions'], TRUE ) : [];
		
		/* Basic permission check */
		if ( !$this->container()->can( 'download', $member ) )
		{
			throw new DomainException( $this->container()->message('npd') ?: 'download_no_perm' );
		}

		/* If the file is hidden and this isn't a moderator, they can't access regardless of 'view' permission */
		if ( $this->hidden() and !static::canViewHiddenItems( $member, $this->containerWrapper() ) and ( $this->hidden() !== 1 or $this->author()->member_id !== $member->member_id ) )
		{
			throw new DomainException( $this->container()->message('npd') ?: 'download_no_perm' );
		}
		
		/* Paid? */
		if ( $this->isPaid() )
		{
			/* Guests can't download paid files */
			if ( !$member->member_id )
			{
				throw new DomainException( $this->container()->message('npd') ?: 'download_no_perm' );
			}

			if ( !$member->group['idm_bypass_paid'] and $member->member_id != $this->author()->member_id )
			{
				if ( $this->cost )
				{
					if ( !count( FileNexusItem::getPurchases( Customer::load( $member->member_id ), $this->id, FALSE ) ) )
					{
						throw new DomainException( 'file_not_purchased' );
					}
				}
				elseif ( $this->nexus )
				{
					if ( !count( \IPS\nexus\extensions\nexus\Item\Package::getPurchases( Customer::load( $member->member_id ), explode( ',', $this->nexus ), FALSE ) ) )
					{
						throw new DomainException( 'file_not_purchased' );
					}
				}
			}
			
			/* If restrictions aren't applying to Paid files, stop here */
			if ( !$member->group['idm_paid_restrictions'] )
			{
				return;
			}
		}

		
		/* Minimum posts */
		if ( $member->member_id and isset( $restrictions['min_posts'] ) and $restrictions['min_posts'] and $restrictions['min_posts'] > $member->member_posts )
		{
			throw new DomainException( $member->language()->addToStack( 'download_min_posts', FALSE, array( 'pluralize' => array( $restrictions['min_posts'] ) ) ) );
		}
		
		/* Simultaneous downloads */
		if ( isset( $restrictions['min_posts'] ) AND $restrictions['limit_sim'] )
		{
			if ( $this->getCurrentDownloadSessions( $member ) >= $restrictions['limit_sim'] )
			{
				throw new DomainException( $member->language()->addToStack( 'max_simultaneous_downloads', FALSE, array( 'pluralize' => array( $restrictions['limit_sim'] ) ) ) );
			}
		}
				
		/* For bandwidth checks, we need a record. If we don't have one - use the one with the smallest filesize */
		if ( !$record )
		{
			$it = $this->files();
			foreach ( $it as $file )
			{
				$data = $it->data();
				if ( !$record or $record['record_size'] > $data['record_size'] )
				{
					$record = $data;
				}
			}
		}
		
		/* Bandwidth & Download limits */
		$logWhere = $member->member_id ? array( 'dmid=?', $member->member_id ) : array( 'dip=?', Request::i()->ipAddress() );
		foreach ( array( 'daily' => 'P1D', 'weekly' => 'P1W', 'monthly' => 'P1M' ) as $k => $interval )
		{
			$timePeriodWhere = array( $logWhere, array( 'dtime>?', DateTime::create()->sub( new DateInterval( $interval ) )->getTimestamp() ) );
			
			/* Bandwidth */
			if ( isset( $restrictions[ $k . '_bw' ] ) AND $restrictions[ $k . '_bw' ] )
			{
				$usedThisPeriod = Db::i()->select( 'SUM(dsize)', 'downloads_downloads', $timePeriodWhere )->first();
				if ( ( $record['record_size'] + $usedThisPeriod ) > ( $restrictions[ $k . '_bw' ] * 1024 ) )
				{
					if ( $record['record_size'] > ( $restrictions[ $k . '_bw' ] * 1024 ) )
					{
						throw new DomainException( $member->language()->addToStack( 'bandwidth_limit_' . $k . '_never', FALSE, array( 'sprintf' => array( Filesize::humanReadableFilesize( $restrictions[ $k . '_bw' ] * 1024 ), Filesize::humanReadableFilesize( $record['record_size'] ) ) ) ) );
					}
					else
					{
						$date = new DateTime;
						foreach ( Db::i()->select( '*', 'downloads_downloads', $timePeriodWhere, 'dtime ASC' ) as $log )
						{
							$usedThisPeriod -= $log['dsize'];
							if ( ( $record['record_size'] + $usedThisPeriod ) < ( $restrictions[ $k . '_bw' ] * 1024 ) )
							{
								$date = DateTime::ts( $log['dtime'] );
								break;
							}
						}
												
						throw new DomainException( $member->language()->addToStack( 'bandwidth_limit_' . $k, FALSE, array( 'sprintf' => array( Filesize::humanReadableFilesize( $restrictions[ $k . '_bw' ] * 1024 ), (string) $date->add( new DateInterval( $interval ) ) ) ) ) );
					}
				}
			}
			
			/* Download */
			if ( isset( $restrictions[ $k . '_dl' ] ) AND $restrictions[ $k . '_dl' ] )
			{
				try
				{
					$downloadsThisPeriod = Db::i()->select( 'COUNT(*)', 'downloads_downloads', $timePeriodWhere )->first();
				}
				catch( UnderflowException $e )
				{
					$downloadsThisPeriod = 0;
				}

				if( $downloadsThisPeriod >= $restrictions[ $k . '_dl' ] )
				{
					throw new DomainException( $member->language()->addToStack( 'download_limit_' . $k, FALSE, array( 'pluralize' => array( $restrictions[ $k . '_dl' ] ), 'sprintf' => array( (string) DateTime::ts( Db::i()->select( 'dtime', 'downloads_downloads', $timePeriodWhere, 'dtime ASC', array( 0, 1 ) )->first() )->add( new DateInterval( $interval ) ) ) ) ) );
				}
			}
		}
	}

	/**
	 * @brief Cached number of current download sessions
	 */
	protected array $_currentDownloadSessions = array();

	/**
	 * Get the current number of download sessions
	 *
	 * @param Member $member		Member to check
	 * @return int
	 */
	public function getCurrentDownloadSessions( Member $member ): int
	{
		if( !array_key_exists( $member->member_id, $this->_currentDownloadSessions ) )
		{
			$this->_currentDownloadSessions[ $member->member_id ] = Db::i()->select( 'COUNT(*)', 'downloads_sessions', array( array( 'dsess_start > ?', time() - ( 60 * 15 ) ), $member->member_id ? array( 'dsess_mid=?', $member->member_id ) : array( 'dsess_ip=?', Request::i()->ipAddress() ) ) )->first();
		}

		return $this->_currentDownloadSessions[ $member->member_id ];
	}
	
	/**
	 * Can view downloaders?
	 *
	 * @param	Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canViewDownloaders( ?Member $member = null ): bool
	{
		if ( $this->container()->log === 0 )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		if ( $member->member_id == $this->author()->member_id and $this->container()->bitoptions['submitter_log'] )
		{
			return TRUE;
		}
				
		return $member->group['idm_view_downloads'];
	}

	/**
	 * Get elements for add/edit form
	 *
	 * @param Item|null $item The current item if editing or NULL if creating
	 * @param Model|null $container Container (e.g. forum) ID, if appropriate
	 * @param string $bulkKey If we are submitting multiple files at once, a key that is used to differentiate between which fields are for which files
	 * @return    array
	 * @throws Exception
	 */
	public static function formElements( ?Item $item=NULL, ?Model $container=NULL, string $bulkKey = '' ): array
	{
		/* Init */
		$return = [];
		foreach ( parent::formElements( $item, $container ) as $k => $input )
		{
			$input->name = "{$bulkKey}{$input->name}";
			$return[ $k ] = $input;
		}

		/* Description */
		$return['description'] = new Editor( "{$bulkKey}file_desc", $item?->desc, TRUE, array( 'app' => 'downloads', 'key' => 'Downloads', 'autoSaveKey' => ( $item ? "downloads-file-{$item->id}" : "{$bulkKey}downloads-new-file" ), 'attachIds' => ( $item === NULL ? NULL : array( $item->id, NULL, 'desc' ) ) ), '\IPS\Helpers\Form::floodCheck' );

		/* Edit Log Fields need to be under the editor */
		$editReason = NULL;
		if( isset( $return['edit_reason']) )
		{
			$editReason = $return['edit_reason'];
			unset( $return['edit_reason'] );
			$return['edit_reason'] = $editReason;
		}

		$logEdit = NULL;
		if( isset( $return['log_edit']) )
		{
			$logEdit = $return['log_edit'];
			unset( $return['log_edit'] );
			$return['log_edit'] = $logEdit;
		}

		/* Primary screenshot */
		if ( $item )
		{
			$screenshotOptions = array();
			foreach ( Db::i()->select( '*', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $item->id, 'ssupload' ) ) as $ss )
			{
				$screenshotOptions[ $ss['record_id'] ] = SystemFile::get( 'downloads_Screenshots', $ss['record_location'] )->url;
			}

			if ( count( $screenshotOptions ) > 1 )
			{
				$return['primary_screenshot'] = new Radio( "{$bulkKey}file_primary_screenshot", $item->_primary_screenshot, FALSE, array( 'options' => $screenshotOptions, 'parse' => 'image' ) );
			}
		}
		
		/* Nexus Integration */
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on and Member::loggedIn()->group['idm_add_paid'] )
		{
			$options = array(
				'free'		=> 'file_free',
				'paid'		=> 'file_paid',
			);
			if ( Member::loggedIn()->isAdmin() AND count( Package::roots() ) > 0 )
			{
				$options['nexus'] = 'file_associate_nexus';
			}
			
			$return['file_cost_type'] = new Radio( "{$bulkKey}file_cost_type", $item ? ( $item->cost ? 'paid' : ( $item->nexus ? 'nexus' : 'free' ) ) : 'free', TRUE, array(
				'options'	=> $options,
				'toggles'	=> array(
					'paid'		=> array( "{$bulkKey}file_cost", "{$bulkKey}file_renewals" ),
					'nexus'		=> array( "{$bulkKey}file_nexus" )
				)
			) );
			
			$commissionBlurb = NULL;
			$fees = NULL;
			if ( $_fees = json_decode( Settings::i()->idm_nexus_transfee, TRUE ) )
			{
				$fees = array();
				foreach ( $_fees as $fee )
				{
					$fees[] = (string) ( new Money( $fee['amount'], $fee['currency'] ) );
				}
				$fees = Member::loggedIn()->language()->formatList( $fees, Member::loggedIn()->language()->get('or_list_format') );
			}
			if ( Settings::i()->idm_nexus_percent and $fees )
			{
				$commissionBlurb = Member::loggedIn()->language()->addToStack( 'file_cost_desc_both', FALSE, array( 'sprintf' => array( Settings::i()->idm_nexus_percent, $fees ) ) );
			}
			elseif ( Settings::i()->idm_nexus_percent )
			{
				$commissionBlurb = Member::loggedIn()->language()->addToStack('file_cost_desc_percent', FALSE, array( 'sprintf' => Settings::i()->idm_nexus_percent ) );
			}
			elseif ( $fees )
			{
				$commissionBlurb = Member::loggedIn()->language()->addToStack('file_cost_desc_fee', FALSE, array( 'sprintf' => $fees ) );
			}

			$minimums = json_decode( Settings::i()->idm_nexus_mincost, true );
			$minCosts = [];
			foreach( $minimums as $currency => $cost )
			{
				if( ( new Number( $cost['amount'] ) )->isGreaterThanZero() )
				{
					$minCosts[] = (string) ( new Money( $cost['amount'], $currency ) );
				}
			}

			if( count( $minCosts ) )
			{
				$commissionBlurb .= Member::loggedIn()->language()->addToStack('file_cost_desc_minimum', FALSE, array( 'sprintf' => Member::loggedIn()->language()->formatList( $minCosts ) ) );
			}
			
			Member::loggedIn()->language()->words['file_cost_desc'] = $commissionBlurb;

			$return['file_cost'] = new FormMoney( "{$bulkKey}file_cost", $item ? json_decode( $item->cost, TRUE ) : array(), NULL, array(), function( $val ) use ( $minimums, $bulkKey ) {
				foreach( $val as $currency => $money )
				{
					if( isset( $minimums[ $currency ]['amount'] ) AND $money->amount->compare( new Number( $minimums[ $currency ]['amount'] ) ) === -1 )
					{
						throw new DomainException('file_cost_too_low');
					}
				}
			}, NULL, NULL, "{$bulkKey}file_cost" );
			$return['file_renewals']  = new Radio( "{$bulkKey}file_renewals", $item ? ( $item->renewal_term ? 1 : 0 ) : 0, TRUE, array(
				'options'	=> array( 0 => 'file_renewals_off', 1 => 'file_renewals_on' ),
				'toggles'	=> array( 1 => array( "{$bulkKey}file_renewal_term" ) )
			), NULL, NULL, NULL, "{$bulkKey}file_renewals" );
			Member::loggedIn()->language()->words['file_renewal_term_desc'] = $commissionBlurb;
			$renewTermForEdit = NULL;
			if ( $item and $item->renewal_term )
			{
				$renewPrices = array();
				foreach ( json_decode( $item->renewal_price, TRUE ) as $currency => $data )
				{
					$renewPrices[ $currency ] = new Money( $data['amount'], $currency );
				}
				$renewTermForEdit = new PurchaseRenewalTerm( $renewPrices, new DateInterval( 'P' . $item->renewal_term . mb_strtoupper( $item->renewal_units ) ) );
			}
			$return['file_renewal_term'] = new RenewalTerm( "{$bulkKey}file_renewal_term", $renewTermForEdit, NULL, array( 'allCurrencies' => TRUE ), NULL, NULL, NULL, "{$bulkKey}file_renewal_term" );

			if ( Member::loggedIn()->isAdmin() AND count( Package::roots() ) > 0 )
			{
				$return['file_nexus'] = new Node( "{$bulkKey}file_nexus", $item ? $item->nexus : array(), FALSE, array( 'class' => '\IPS\nexus\Package', 'multiple' => TRUE ), NULL, NULL, NULL, "{$bulkKey}file_nexus" );
			}
		}
		
		/* Custom Fields */
		$customFieldValues = $item ? $item->customFields() : array();
		if( $container )
		{
			foreach ( $container->cfields as $k => $field )
			{
				$_id = $field->id;
				$field->id = "{$bulkKey}{$field->id}";
				if ( $field->type === 'Editor' )
				{
					if ( $field->allow_attachments AND $item )
					{
						$attachIds = array( $item->id, $_id, 'fields' );
						$field::$editorOptions = array_merge( $field::$editorOptions, array( 'attachIds' => $attachIds ) );
					}
				}
				$helper = $field->buildHelper( $customFieldValues["field_{$k}"] ?? NULL, NULL, $item );
				$helper->label = Member::loggedIn()->language()->addToStack( 'downloads_field_' . $_id );
				$field->id = $_id;
				if ( $field->type === 'Editor' )
				{
					if ( $field->allow_attachments AND !$item )
					{
						$field::$editorOptions = array_merge( $field::$editorOptions, array( 'attachIds' => NULL ) );
					}
				}
				$return[] = $helper;
			}
		}

		if( $item )
		{
			$return['versioning']	= new Custom( "{$bulkKey}file_versioning_info", NULL, FALSE, array( 'getHtml' => function( $element ) use ( $item )
			{
				return Theme::i()->getTemplate( 'submit' )->editDetailsInfo( $item );
			} ) );
		}
		
		return $return;
	}
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( array $values ): void
	{
		$new = $this->_new;

		parent::processForm( $values );

		if ( !$new )
		{
			$oldContent = $this->desc;
		}
		$this->desc	= $values['file_desc'];
		
		$imageUploads = $values['files'] ?? [];
		$editorLookups = $new ? [ ( $values['_bulkKey'] ?? '' ) . 'downloads-new-file'] : [ [ $this->id, NULL, 'desc' ] ];
		if ( isset( $values['screenshots'] ) and $values['screenshots'] )
		{
			$imageUploads = array_merge( $imageUploads, $values['screenshots'] );
		}
		foreach ( $this->container()->cfields as $field )
		{
			$helper = $field->buildHelper( NULL, NULL, $new ? NULL : $this );
			if ( $helper instanceof Upload )
			{
				if ( is_array( $values["downloads_field_{$field->id}"] ) )
				{
					$imageUploads = array_merge( $imageUploads, $values["downloads_field_{$field->id}"] );
				}
				elseif ( $values["downloads_field_{$field->id}"] )
				{
					$imageUploads[] = $values["downloads_field_{$field->id}"];
				}
			}
			elseif ( $helper instanceof Editor )
			{
				$editorLookups[] = $new ? md5( 'IPS\downloads\Field-' . ( $values['_bulkKey'] ?? '' ) . $field->id . '-new' ) : [ $this->id, $field->id, 'fields' ];
			}
		}
		$sendFilterNotifications = $this->checkProfanityFilters( FALSE, !$new, NULL, NULL, 'downloads_Downloads', $editorLookups, $imageUploads );

		if ( !$new AND $sendFilterNotifications === FALSE )
		{
			$this->sendAfterEditNotifications( $oldContent );
		}

		if( isset( $values['file_primary_screenshot'] ) )
		{
			$this->primary_screenshot	= (int) $values['file_primary_screenshot'];
		}
		
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on and Member::loggedIn()->group['idm_add_paid'] )
		{
			switch ( $values['file_cost_type'] )
			{
				case 'free':
					$this->cost = NULL;
					$this->renewal_term = 0;
					$this->renewal_units = NULL;
					$this->renewal_price = NULL;
					$this->nexus = NULL;
					break;
				
				case 'paid':
					$this->cost = json_encode( $values['file_cost'] );
					if ( $values['file_renewals'] and $values['file_renewal_term'] )
					{						
						$term = $values['file_renewal_term']->getTerm();
						$this->renewal_term = $term['term'];
						$this->renewal_units = $term['unit'];
						$this->renewal_price = json_encode( $values['file_renewal_term']->cost );
					}
					else
					{
						$this->renewal_term = 0;
						$this->renewal_units = NULL;
						$this->renewal_price = NULL;
					}
					$this->nexus = NULL;
					break;
				
				case 'nexus':
					$this->cost = NULL;
					$this->renewal_term = 0;
					$this->renewal_units = NULL;
					$this->renewal_price = NULL;
					$this->nexus = implode( ',', array_keys( $values['file_nexus'] ) );
					break;
			}
		}
		
		$this->save();
		$cfields = array();
		foreach ( $this->container()->cfields as $field )
		{
			$helper	 = $field->buildHelper( NULL, NULL, $new ? NULL : $this );
			if ( $helper instanceof Upload )
			{
				$cfields[ "field_{$field->id}" ] = (string) $values[ "downloads_field_{$field->id}" ];
			}
			else
			{
				$cfields[ "field_{$field->id}" ] = $helper::stringValue( $values[ "downloads_field_{$field->id}" ] );
			}
			
			if ( $helper instanceof Editor )
			{
				$field->claimAttachments( $this->id, 'fields' );
			}
		}
		
		if ( !empty( $cfields ) )
		{
			Db::i()->insert( 'downloads_ccontent', array_merge( array( 'file_id' => $this->id, 'updated' => time() ), $cfields ), TRUE );
		}
		
		/* Update Category */
		$this->container()->setLastFile( ( $new and $this->open ) ? $this : NULL );
		$this->container()->save();
	}
	
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( array $values ): void
	{
		/* Set version */
		$this->version = ( isset( $values['file_version'] ) ) ? $values['file_version'] : NULL;
		
		/* Try to set the primary screenshot */
		try
		{
			$this->primary_screenshot = Db::i()->select( 'record_id', 'downloads_files_records', array( 'record_post_key=? AND ( record_type=? or record_type=? ) AND record_backup=0', $values['postKey'], 'ssupload', 'sslink' ), 'record_default DESC, record_id ASC' )->first();
		}
		catch ( Exception $e ) { }

		parent::processBeforeCreate( $values );
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( ?Comment $comment, array $values ): void
	{
		SystemFile::claimAttachments( 'downloads-new-file', $this->id, NULL, 'desc' );
		
		if ( $this->_primary_screenshot )
		{
			Db::i()->update( 'downloads_files_records', array( 'record_default' => 1 ), array( 'record_id=?', $this->_primary_screenshot ) );
		}
		Db::i()->update( 'downloads_files_records', array( 'record_file_id' => $this->id, 'record_post_key' => NULL ), array( 'record_post_key=?', $values['postKey'] ) );
		$this->size = (int) Db::i()->select( 'SUM(record_size)', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $this->id, 'upload' ) )->first();
		$this->save();
		
		parent::processAfterCreate( $comment, $values );
	}
	
	/**
	 * Process after the object has been edited on the front-end
	 *
	 * @param array $values		Values from form
	 * @return	void
	 */
	public function processAfterEdit( array $values ): void
	{
		parent::processAfterEdit( $values );

		Request::i()->setClearAutosaveCookie( 'downloads-file-' . $this->id );
		foreach ( $this->container()->cfields as $field )
		{
			Request::i()->setClearAutosaveCookie( md5( 'IPS\downloads\Field-' . $field->id . '-' . $this->id ) );
		}
	}

	/**
	 * Log for deletion later
	 *
	 * @param	Member|false|null 	$member	The member, NULL for currently logged in, or FALSE for no member
	 * @return	void
	 */
	public function logDelete( Member|false|null $member = NULL ) : void
	{
		$this->_logDelete( $member );

		if ( $topic = $this->topic() and $this->container()->bitoptions['topic_delete'] )
		{
			$topic->logDelete( $member );
		}

		if ( $this->hasPendingVersion() )
		{
			PendingVersion::load( $this->id, 'pending_file_id' )->delete();
		}
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		if ( $topic = $this->topic() and $this->container()->bitoptions['topic_delete'] )
		{
			$topic->delete();
		}
		
		if ( Application::appIsEnabled( 'nexus' ) )
		{
			Db::i()->update( 'nexus_purchases', array( 'ps_cancelled' => TRUE, 'ps_can_reactivate' => FALSE ), array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'downloads', 'file', $this->id ) );
		}
		
		parent::delete();
				
		foreach ( new Iterator( Db::i()->select( 'record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=?', $this->id, 'upload' ) ), 'downloads_Files' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( Exception $e ) { }
		}

		foreach ( new Iterator( Db::i()->select( 'record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=?', $this->id, 'ssupload' ) ), 'downloads_Screenshots' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( Exception $e ) { }
		}

		foreach ( new Iterator( Db::i()->select( 'record_thumb', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_thumb IS NOT NULL', $this->id, 'ssupload' ) ), 'downloads_Screenshots' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( Exception $e ) { }
		}

		foreach ( new Iterator( Db::i()->select( 'record_no_watermark', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_no_watermark IS NOT NULL', $this->id, 'ssupload' ) ), 'downloads_Screenshots' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( Exception $e ) { }
		}
		
		Db::i()->delete( 'downloads_ccontent', array( 'file_id=?', $this->id ) );
		Db::i()->delete( 'downloads_downloads', array( 'dfid=?', $this->id ) );
		Db::i()->delete( 'downloads_filebackup', array( 'b_fileid=?', $this->id ) );
		Db::i()->delete( 'downloads_files_records', array( 'record_file_id=?', $this->id ) );
		Db::i()->delete( 'downloads_files_notify', array( 'notify_file_id=?', $this->id ) );

		/* Delete pending version */
		if( $this->hasPendingVersion() )
		{
			PendingVersion::load($this->id, 'pending_file_id' )->delete();
		}

		/* Update Category */
		$this->container()->setLastFile();
		$this->container()->save();
	}

	/**
	 * Delete Records
	 *
	 * @param array|int|null $ids Must be INT if url and handler are provided
	 * @param string|null $url Record location
	 * @param string|null $handler File storage handler key
	 * @return void
	 */
	public function deleteRecords( array|int|null $ids, ?string $url=NULL, ?string $handler=NULL ) : void
	{
		if( $ids AND $handler AND $url )
		{
			try
			{
				if( !Db::i()->select( 'COUNT(record_id)', 'downloads_files_records', array( 'record_id<>? AND record_location=?', $ids, $url ) )->first() )
				{
					SystemFile::get( $handler, $url )->delete();
				}
			}
			catch ( Exception $e ) { }
			Db::i()->delete( 'downloads_files_records', array( 'record_id=? AND record_location=?', $ids, $url ) );
		}
		else
		{
			if( $ids !== NULL AND !is_array( $ids ) )
			{
				$ids = array( $ids );
			}

			Db::i()->delete( 'downloads_files_records', array( Db::i()->in( 'record_id', $ids ) ) );
		}
	}

	/**
	 * URL Blacklist Check
	 *
	 * @param array|null|string $val	URLs to check
	 * @return	void
	 * @throws	DomainException
	 */
	public static function blacklistCheck( array|null|string $val ) : void
	{
		if ( is_array( $val ) )
		{
			foreach ( explode( ',', Settings::i()->idm_link_blacklist ) as $blackListedDomain )
			{
				foreach ( array_filter( $val ) as $url )
				{
					if ( is_string( $url ) )
					{
						$url = Url::external( $url );
					}
					
					if ( mb_substr( $url->data['host'], -mb_strlen( $blackListedDomain ) ) == $blackListedDomain )
					{
						throw new DomainException( Member::loggedIn()->language()->addToStack( 'err_url_file_blacklist', FALSE, array( 'sprintf' => $blackListedDomain ) ) );
					}
				}
			}
		}
	}

	/**
	 * Are comments supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsComments( ?Member $member = NULL, ?Model $container = NULL ): bool
	{
		if( $container !== NULL )
		{
			return parent::supportsComments() and $container->bitoptions['comments'] AND ( !$member or $container->can( 'read', $member ) );
		}
		else
		{
			return parent::supportsComments() and ( !$member or Category::countWhere( 'read', $member, array( 'cbitoptions & 4' ) ) );
		}
	}
	
	/**
	 * Are reviews supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsReviews( ?Member $member = NULL, ?Model $container = NULL ): bool
	{
		if( $container !== NULL )
		{
			return parent::supportsReviews() and $container->bitoptions['reviews'] AND ( !$member or $container->can( 'read', $member ) );
		}
		else
		{
			return parent::supportsReviews() and ( !$member or Category::countWhere( 'read', $member, array( 'cbitoptions & 256' ) ) );
		}
	}
	
	/**
	 * Save the current files/screenshots into the backup in preparation for storing a new version
	 *
	 * @return	void
	 */
	public function saveVersion() : void
	{
		/* Move the old details into a backup record */
		$b_id = Db::i()->insert( 'downloads_filebackup', array(
			'b_fileid'		=> $this->id,
			'b_filetitle'	=> $this->name,
			'b_filedesc'	=> $this->desc,
			'b_hidden'		=> FALSE,
			'b_backup'		=> $this->published,
			'b_updated'		=> time(),
			'b_records'		=> implode( ',', iterator_to_array( Db::i()->select( 'record_id', 'downloads_files_records', array( 'record_file_id=? AND record_backup=0 AND record_hidden=0', $this->id ) ) ) ),
			'b_version'		=> $this->version,
			'b_changelog'	=> $this->changelog,
		) );
		
		/* Fetch the existing locations to prevent backups with the same file name as the current file from removing the disk files */
		$locations = array();
		$thumbs = array();
		$watermarks = array();
		foreach( Db::i()->select( '*', 'downloads_files_records', array( 'record_file_id=? AND record_backup=0 AND record_hidden=0', $this->id ) ) as $file )
		{
			$locations[] = $file['record_location'];
			
			if ( $file['record_thumb'] )
			{
				$thumbs[] = $file['record_thumb'];
			}
			
			if ( $file['record_no_watermark'] )
			{
				$watermarks[] = $file['record_no_watermark'];
			}
		}
		
		/* Update the attachment map for this version (NULL means the current version, anything else means previous version). */
		SystemFile::claimAttachments( "downloads-{$this->id}-changelog", $this->id, $b_id, 'changelog' );
		
		/* Set the old records to be backups */
		Db::i()->update( 'downloads_files_records', array( 'record_backup' => TRUE ), array( 'record_file_id=? AND record_backup=0 AND record_hidden=0', $this->id ) );
						
		/* Delete any old versions we no longer keep */
		$category = $this->container();
		if ( $category->versioning !== NULL )
		{
			$count = Db::i()->select( 'COUNT(*)', 'downloads_filebackup', array( 'b_fileid=?', $this->id ) )->first();
			if ( ( $count - $category->versioning + 1 ) > 0 )
			{
				foreach ( Db::i()->select( '*', 'downloads_filebackup', array( 'b_fileid=?', $this->id ), 'b_backup ASC', $count - $category->versioning + 1 ) as $backUp )
				{
					foreach ( Db::i()->select( '*', 'downloads_files_records', Db::i()->in( 'record_id', explode( ',', $backUp['b_records'] ) ) ) as $k => $file )
					{
						try
						{
							if ( !in_array( $file['record_location'], $locations ) )
							{
								$file = SystemFile::get( $file['record_type'] == 'upload' ? 'downloads_Files' : 'downloads_Screenshots', $file['record_location'] )->delete();
							}
						}
						catch ( Exception $e ) { }

						if( $file['record_type'] == 'ssupload' )
						{
							if( $file['record_thumb'] )
							{
								try
								{
									if ( !in_array( $file['record_thumb'], $thumbs ) )
									{
										$file = SystemFile::get( 'downloads_Screenshots', $file['record_thumb'] )->delete();
									}
								}
								catch ( Exception $e ) { }
							}

							if( $file['record_no_watermark'] )
							{
								try
								{
									if ( !in_array( $file['record_no_watermark'], $watermarks ) )
									{
										$file = SystemFile::get( 'downloads_Screenshots', $file['record_no_watermark'] )->delete();
									}
								}
								catch ( Exception $e ) { }
							}
						}
					}
					
					Db::i()->delete( 'downloads_files_records', Db::i()->in( 'record_id', explode( ',', $backUp['b_records'] ) ) );
					Db::i()->delete( 'downloads_filebackup', array( 'b_id=?', $backUp['b_id'] ) );
				}
			}
		}

	}
	
	/* !Followers */

	/**
	 * Users to receive immediate notifications (bulk)
	 *
	 * @param Category $category The category the files were posted in.
	 * @param Member|null $member The member posting the files or NULL for currently logged in member.
	 * @param array|int|null $limit LIMIT clause
	 * @param bool $countOnly Only return the count
	 * @return Select|int
	 */
	public static function _notificationRecipients( Category $category, Member $member=NULL, array|int|null $limit=array( 0, 25 ), bool $countOnly=FALSE ): Select|int
	{
		$member = $member ?: Member::loggedIn();

		/* Do we only want the count? */
		if( $countOnly )
		{
			$count	= 0;
			$count	+= $member->followersCount( 3, array( 'immediate' ) );
			$count	+= static::containerFollowerCount( $category, 3, array( 'immediate' ) );

			return $count;
		}

		$memberFollowers = $member->followers( 3, array( 'immediate' ), NULL, NULL );

		if( $memberFollowers !== NULL )
		{
			$unions	= array( 
				static::containerFollowers( $category, 3, array( 'immediate' ), NULL, NULL ),
				$memberFollowers
			);

			return Db::i()->union( $unions, 'follow_added', $limit );
		}
		else
		{
			return static::containerFollowers( $category, static::FOLLOW_PUBLIC + static::FOLLOW_ANONYMOUS, array( 'immediate' ), NULL, $limit, 'follow_added' );
		}
	}
	
	/**
	 * Send Notifications (bulk)
	 *
	 * @param Category $category	The category the files were posted in.
	 * @param	Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @return	void
	 */
	public static function _sendNotifications( Category $category, ?Member $member=NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		try
		{
			$count = static::_notificationRecipients($category, $member, NULL, TRUE);
		}
		catch( BadMethodCallException $e )
		{
			return;
		}
		
		$categoryIdColumn	= $category::$databaseColumnId;
		
		if ( $count > NOTIFICATION_BACKGROUND_THRESHOLD )
		{
			$queueData = array(
				'followerCount'		=> $count,
				'category_id'		=> $category->$categoryIdColumn,
				'member_id'			=> $member->member_id
			);
			
			Task::queue( 'downloads', 'Follow', $queueData, 2 );
		}
		else
		{
			static::_sendNotificationsBatch( $category, $member );
		}
	}
	
	/**
	 * Send Unapproved Notification (bulk)(
	 *
	 * @param Category $category	The category the files were posted too.
	 * @param Member|null $member		The member posting the images, or NULL for currently logged in member.
	 * @return	void
	 */
	public static function _sendUnapprovedNotifications( Category $category, ?Member $member=NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		
		$moderators = array( 'g' => array(), 'm' => array() );
		foreach( Db::i()->select( '*', 'core_moderators' ) AS $mod )
		{
			$canView = FALSE;
			if ( $mod['perms'] == '*' )
			{
				$canView = TRUE;
			}
			if ( $canView === FALSE )
			{
				$perms = json_decode( $mod['perms'], TRUE );
				
				if ( isset( $perms['can_view_hidden_content'] ) AND $perms['can_view_hidden_content'] )
				{
					$canView = TRUE;
				}
				else if ( isset( $perms['can_view_hidden_' . static::$title ] ) AND $perms['can_view_hidden_' . static::$title ] )
				{
					$canView = TRUE;
				}
			}
			if ( $canView === TRUE )
			{
				$moderators[ $mod['type'] ][] = $mod['id'];
			}
		}
		
		$notification = new Notification( Application::load('core'), 'unapproved_content_bulk', $category, array( $category, $member, $category::$contentItemClass ), array( $member->member_id ) );
		foreach ( Db::i()->select( '*', 'core_members', ( count( $moderators['m'] ) ? Db::i()->in( 'member_id', $moderators['m'] ) . ' OR ' : '' ) . Db::i()->in( 'member_group_id', $moderators['g'] ) . ' OR ' . Db::i()->findInSet( 'mgroup_others', $moderators['g'] ) ) as $moderator )
		{
			$notification->recipients->attach( Member::constructFromData( $moderator ) );
		}
		$notification->send();
	}
	
	/**
	 * Send Notification Batch (bulk)
	 *
	 * @param Category $category	The category the files were posted too.
	 * @param	Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @param int $offset		Offset
	 * @return	int|NULL				New Offset or NULL if complete
	 */
	public static function _sendNotificationsBatch( Category $category, Member $member=NULL, int $offset=0 ): ?int
	{
		/* Check notification initiator spam status */
		if( ( $member instanceof Member ) AND $member->members_bitoptions['bw_is_spammer'] )
		{
			/* Initiator is flagged as spammer, don't send notifications */
			return NULL;
		}

		$member				= $member ?: Member::loggedIn();
		
		$followIds = array();
		$followers = iterator_to_array( static::_notificationRecipients($category, $member, array($offset, static::NOTIFICATIONS_PER_BATCH)) );

		if( !count( $followers ) )
		{
			return NULL;
		}

		$notification = new Notification( Application::load( 'core' ), 'new_content_bulk', $category, array( $category, $member, $category::$contentItemClass ), array( $member->member_id ) );
		
		foreach( $followers AS $follower )
		{
			$followMember = Member::load( $follower['follow_member_id'] );
			if ( $followMember !== $member and $category->can( 'view', $followMember ) )
			{
				$followIds[] = $follower['follow_id'];
				$notification->recipients->attach( $followMember );
			}
		}
		
		Db::i()->update( 'core_follow', array( 'follow_notify_sent' => time() ), Db::i()->in( 'follow_id', $followIds ) );
		$notification->send();
		
		return $offset + static::NOTIFICATIONS_PER_BATCH;
	}
	
	/**
	 * @brief	Is first time approval
	 */
	protected bool $firstTimeApproval = FALSE;
	
	/**
	 * Unhide
	 *
	 * @param Member|null $member	The member doing the action (NULL for currently logged in member)
	 * @return	void
	 */
	public function unhide( ?Member $member=NULL ) : void
	{
		if ( $this->hidden() === 1 )
		{
			$this->firstTimeApproval = TRUE;
		}
		
		$this->_unhide( $member );
	}
	
	/**
	 * Send Approved Notification
	 *
	 * @return	void
	 */
	public function sendApprovedNotification() : void
	{
		if ( $this->firstTimeApproval )
		{
			$this->sendNotifications();
		}
		else
		{
			$this->sendUpdateNotifications();
		}
		$this->sendAuthorApprovalNotification();
	}
	
	/**
	 * Send notifications that the file has been updated
	 *
	 * @return	void
	 */
	public function sendUpdateNotifications() : void
	{		
		$count = Db::i()->select( 'count(*)', 'downloads_files_notify', array( 'notify_file_id=?', $this->id ) )->first();

		if ( $count )
		{
			$idColumn = static::$databaseColumnId;
			Task::queue( 'downloads', 'Notify', array( 'file' => $this->$idColumn, 'notifyCount' => $count ), 2 );
		}
	}

	/**
	 * Syncing when uploading a new version.
	 *
	 * @param array $values	Values from the form
	 * @return	void
	 */
	public function processAfterNewVersion( array $values ) : void
	{
		/* This method is mainly used to ensure a topic linked to a file download stays updated when uploading a new version (like when editing it normally),
			however it also accepts the values from the form so authors can overload and manipulate the data from it. */
		if ( Application::appIsEnabled('forums') AND $this->topic() )
		{
			/* And we need to make sure the "cached" values for primary screenshot and thumbnail are cleared so the updated topic always has the latest */
			$this->_primaryScreenshot = FALSE;
			$this->_primaryScreenshotThumb = FALSE;
			$this->syncTopic();
		}
		Webhook::fire( 'downloads_new_version', array( $this ) );
	}
	
	/* !Embeddable */
	
	/**
	 * Get image for embed
	 *
	 * @return    SystemFile|NULL
	 */
	public function embedImage(): ?SystemFile
	{
		return $this->primary_screenshot_thumb ?: NULL;
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'downloads', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'downloads' )->embedFile( $this, $this->url()->setQueryString( $params ), $this->embedImage() );
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @param	array|null	$backup	If provided, will output for a particular version - provide row from downloads_filebackup
	 * @return    array
	 * @apiresponse	int							id						ID number
	 * @apiresponse	string						title					Title
	 * @apiresponse	\IPS\downloads\Category		category				Category
	 * @apiresponse	\IPS\Member					author					Author
	 * @apiresponse	datetime					date					When the file was created
	 * @apiresponse	datetime					updated					When the file was last updated
	 * @apiresponse	string						description				Description
	 * @apiresponse	string						version					Current version number
	 * @apiresponse	string						changelog				Description of what changed between this version and the previous one
	 * @apiresponse	\IPS\File					primaryScreenshot		The primary screenshot
	 * @apiresponse	[\IPS\File]					screenshots				Screenshots
	 * @apiresponse	[\IPS\File]					screenshotsThumbnails	Screenshots in Thumbnail size
	 * @apiresponse	\IPS\File					primaryScreenshot		The primary screenshot
	 * @apiresponse	\IPS\File					primaryScreenshotThumb	The primary screenshot
	 * @apiresponse	int							downloads				Number of downloads
	 * @apiresponse	int							comments				Number of comments
	 * @apiresponse	int							reviews					Number of reviews
	 * @apiresponse	int							views					Number of views
	 * @apiresponse	string						prefix					The prefix tag, if there is one
	 * @apiresponse	[string]					tags					The tags
	 * @apiresponse	bool						locked					File is locked
	 * @apiresponse	bool						hidden					File is hidden
	 * @apiresponse	bool						pinned					File is pinned
	 * @apiresponse	bool						featured				File is featured
	 * @apiresponse	string						url						URL
	 * @apiresponse	\IPS\forums\Topic			topic					The topic
	 * @apiresponse	bool						isPaid					Is the file paid?
	 * @apiresponse	[float]						prices					Prices (key is currency, value is price). Does not consider associated packages.
	 * @apiresponse	bool						canDownload				If the authenticated member can download. Will be NULL for requests made using an API Key or the Client Credentials Grant Type
	 * @apiresponse	bool						canBuy					Can purchase the file
	 * @apiresponse	bool						canReview				Can review the file
	 * @apiresponse	float						rating					File rating
	 * @apiresponse	int							purchases				Number of purchases
	 * @apiresponse array						renewalTerm				File renewal term
	 * @apiresponse	bool						hasPendingVersion		Whether file has a new version pending. Will be NULL for client requests where the authorized member cannot upload new versions.
	 */
	public function apiOutput( ?Member $authorizedMember = NULL, ?array $backup=NULL ) : array
	{
		return array(
			'id'						=> $this->id,
			'title'						=> $backup ? $backup['b_filetitle'] : $this->name,
			'category'					=> $this->container()->apiOutput( $authorizedMember ),
			'author'					=> $this->author()->apiOutput( $authorizedMember ),
			'date'						=> DateTime::ts( $backup ? $backup['b_backup'] :  $this->submitted )->rfc3339(),
			'updated'					=> DateTime::ts( $this->updated )->rfc3339(),
			'description'				=> $backup ? $backup['b_filedesc'] : $this->content(),
			'version'					=> $backup ? $backup['b_version'] : $this->version,
			'changelog'					=> $backup ? $backup['b_changelog'] : $this->changelog,
			'screenshots'				=> array_values( array_map( function( $file ) use ( $authorizedMember ) {
				return $file->apiOutput( $authorizedMember );
			}, iterator_to_array( $this->screenshots( 0, TRUE, $backup ? $backup['b_id'] : NULL ) ) ) ),
			'screenshotsThumbnails'		=> array_values( array_map( function( $file ) use ( $authorizedMember ) {
				return $file->apiOutput( $authorizedMember );
			}, iterator_to_array( $this->screenshots( 1, TRUE, $backup ? $backup['b_id'] : NULL ) ) ) ),
			'primaryScreenshot'			=> $this->primary_screenshot ? ( $this->primary_screenshot->apiOutput( $authorizedMember ) ) : null,
			'primaryScreenshotThumb'	=> $this->primary_screenshot_thumb ? ( $this->primary_screenshot_thumb->apiOutput( $authorizedMember ) ) : null,
			'downloads'					=> $this->downloads,
			'comments'					=> $this->comments,
			'reviews'					=> $this->reviews,
			'views'						=> $this->views,
			'prefix'					=> $this->prefix(),
			'tags'						=> $this->tags(),
			'locked'					=> $this->locked(),
			'hidden'					=> (bool) $this->hidden(),
			'pinned'					=> (bool) $this->mapped('pinned'),
			'featured'					=> (bool) $this->mapped('featured'),
			'url'						=> (string) $this->url(),
			'topic'						=> $this->topic() ? $this->topic()->apiOutput( $authorizedMember ) : NULL,
			'isPaid'					=> $this->isPaid(),
			'isPurchasable'				=> $this->isPurchasable(),
			'prices'					=> ( $this->isPaid() and $this->cost ) ? array_map( function( $price ) { return $price['amount']; }, json_decode( $this->cost, TRUE ) ) : NULL,
			'canDownload'				=> $authorizedMember ? $this->canDownload( $authorizedMember ) : NULL,
			'canBuy'					=> $authorizedMember ? $this->canBuy( $authorizedMember ) : NULL,
			'canReview'					=> $authorizedMember ? ( $this->canReview( $authorizedMember ) AND !$this->hasReviewed( $authorizedMember ) ) : NULL,
			'rating'					=> $this->averageReviewRating(),
			'purchases'					=> $this->purchaseCount(),
			'renewalTerm'				=> $this->isPaid() ? $this->renewalTerm() : NULL,
			'hasPendingVersion'			=> ( !$authorizedMember OR $this->canEdit( $authorizedMember ) ) ? $this->hasPendingVersion() : NULL
		);
	}

	/**
	 * Message explaining to guests that if they log in they can download
	 *
	 * @return	string|NULL
	 */
	public function downloadTeaser(): ?string
	{
		/* If we're a guest and log in, can we download? */
		if ( !Member::loggedIn()->member_id )
		{
			$testUser = new Member;
			$testUser->member_group_id = Settings::i()->member_group;
			$this->canDownload = NULL;
			if ( $this->canDownload( $testUser ) )
			{
				return Theme::i()->getTemplate( 'view', 'downloads', 'front' )->downloadTeaser();
			}
		}

		return NULL;
	}
	
	/* !Reactions */
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'file_id';
	}
	
	/**
	 * Supported Meta Data Types
	 *
	 * @return	array
	 */
	public static function supportedMetaDataTypes(): array
	{
		return array( 'core_FeaturedComments', 'core_ContentMessages' );
	}

	/**
	 * Give a content item the opportunity to filter similar content
	 * 
	 * @note Intentionally blank but can be overridden by child classes
	 * @return array|NULL
	 */
	public function similarContentFilter(): ?array
	{
		if( $this->topicid )
		{
			return array(
				array( '!(tag_meta_app=? and tag_meta_area=? and tag_meta_id=?)', 'forums', 'forums', $this->topicid )
			);
		}

		return NULL;
	}

	/**
	 * Return size and downloads count when this content type is inserted as an attachment via the "Insert other media" button on an editor.
	 *
	 * @note Most content types do not support this, and those that do will need to override this method to return the appropriate info
	 * @return array
	 */
	public function getAttachmentInfo(): array
	{
		return array(
			'size' => Filesize::humanReadableFilesize( $this->size, FALSE, TRUE ),
			'downloads' => Member::loggedIn()->language()->formatNumber( $this->downloads )
		);
	}

	/**
	 * Get the topic title
	 *
	 * @return string
	 */
	function getTopicTitle(): string
	{
		$title = '';

		if( $prefix = $this->container()->_topic_prefix )
		{
			$title .= $prefix . ' ';
		}

		$title .= $this->name;

		if( $suffix = $this->container()->_topic_suffix )
		{
			$title .= ' ' . $suffix;
		}

		return $title;
	}

	/**
	 * Get the topic content
	 *
	 * @return mixed
	 */
	function getTopicContent(): mixed
	{
		return Theme::i()->getTemplate( 'submit', 'downloads', 'front' )->topic( $this );
	}

	/**
	 * Create/Update Topic
	 *
	 * @return	void
	 */
	public function syncTopic(): void
	{
		/* Run the parent so that we are sure everything is properly synched */
		$this->_syncTopic();

		if( $screenshot = $this->primary_screenshot_thumb )
		{
			/* Now we need to copy the primary screenshot as an attachment to the first post */
			$topic = $this->topic( false );
			if( $topic === null )
			{
				return;
			}

			/* Delete any existing attachments */
			$delete = [];
			foreach( Db::i()->select( '*', 'core_attachments_map', [ 'location_key=? and id1=? and id2=?', 'forums_Forums', $topic->tid, $topic->topic_firstpost ] )
				->join( 'core_attachments', 'attachment_id=attach_id' ) as $row )
			{
				try
				{
					SystemFile::get( 'forums_Forums', $row['attach_location'] )->delete();
				}
				catch( Exception ){}

				$delete[] = $row['attach_id'];
			}

			if( count( $delete ) )
			{
				Db::i()->delete( 'core_attachments_map', Db::i()->in( 'attachment_id', $delete ) );
				Db::i()->delete( 'core_attachments', Db::i()->in( 'attach_id', $delete ) );
			}

			$content = $topic->firstComment()->content();

			try
			{
				/* Copy the screenshot and generate an attachment */
				/* @var SystemFile $screenshot */
				$settings = json_decode( Settings::i()->upload_settings, true );
				$storageConfiguration = $settings['filestorage__core_Attachment'];
				$copy = $screenshot->copy( $storageConfiguration );

				$attachment = $copy->makeAttachment( md5( uniqid() ), $topic->author() );

				Db::i()->insert( 'core_attachments_map', [
					'attachment_id' => $attachment['attach_id'],
					'location_key' => 'forums_Forums',
					'id1' => $topic->tid,
					'id2' => $topic->topic_firstpost
				] );

				/* Now append to the post content */
				$dimensions = $copy->getImageDimensions();

				$attachment = Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedImage( (string) $copy, (string) $copy, $this->name, $attachment['attach_id'], $dimensions[0], $dimensions[1], $this->name );
				$content .= $attachment;
			}
			catch( RuntimeException ){}

			$topic->firstComment()->post = $content;
			$topic->firstComment()->save();
		}
	}

	/**
	 * Return the Forum ID
	 *
	 * @return	int
	 */
	public function getForumId() : int
	{
		return (int) $this->container()->forum_id;
	}

	/**
	 * Subscribed?
	 *
	 * @param Member|null $member
	 * @return bool
	 */
	function subscribed( ?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		try
		{
			Db::i()->select( '*', 'downloads_files_notify', array( 'notify_member_id=? and notify_file_id=?', $member->member_id, $this->id ) )->first();

			return TRUE;
		}
		catch ( UnderflowException $e )
		{
			return FALSE;
		}
	}

	/**
	 * @brief	Cache for pending version check
	 */
	protected ?bool $_pendingVersion = NULL;

	/**
	 * Check whether file has a pending new version
	 *
	 * @return	bool
	 */
	public function hasPendingVersion(): bool
	{
		if( $this->_pendingVersion !== NULL )
		{
			return $this->_pendingVersion;
		}

		try
		{
			Db::i()->select( 'pending_id', 'downloads_files_pending', array( 'pending_file_id=?', $this->id ) )->first();
			$this->_pendingVersion = TRUE;
		}
		catch ( UnderflowException $e )
		{
			$this->_pendingVersion = FALSE;
		}

		return $this->_pendingVersion;
	}

	/**
	 * Add form elements to 'new version' form
	 *
	 * @param	Form		$form
	 * @return	void
	 */
	public function newVersionFormElements( Form &$form ) : void
	{

	}


	/**
	 * Do Moderator Action
	 *
	 * @param string $action	The action
	 * @param	Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param string|null $reason	Reason (for hides)
	 * @param bool $immediately	Delete immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction( string $action, Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		if ( $action == 'delete' )
		{
			/* Delete pending version */
			if( $this->hasPendingVersion() )
			{
				PendingVersion::load($this->id, 'pending_file_id' )->delete();
				$this->_pendingVersion = FALSE;
			}
		}

		parent::modAction( $action, $member, $reason, $immediately );
	}

	/**
	 * Can the member delete the pending version
	 * 
	 * @param Member|null $member
	 * @return bool
	 */
	public function canDeletePendingVersion( Member $member = NULL): bool
	{
		if( !$this->hasPendingVersion() )
		{
			return FALSE;
		}

		if( $this->canEdit($member) )
		{
			return TRUE;
		}

		return FALSE;
	}


	public static string $itemMenuKey ='file_actions';
	public static string $itemMenuCss = '';

	/**
	 * @param Member|NULL $member
	 * @return Menu
	 */
	public function menu( Member $member = NULL ): Menu
	{
		$menu = parent::menu( $member );

		if( !$this->hasPendingVersion() AND $this->canEdit( $member ) )
		{
			$menu->add( new Menu\Link( url: $this->url()->setQueryString( array( 'do' => 'newVersion' ) ), languageString: Member::loggedIn()->language()->addToStack( 'upload_new_version') ) );
		}

		if( $this->isPurchasable() AND $this->canDisablePurchases( $member ) )
		{
			$menu->add( new Menu\Link( url: $this->url()->setQueryString( array( 'do' => 'purchaseStatus' , 'value' => 0 ) )->csrf(), languageString: Member::loggedIn()->language()->addToStack( 'disable_purchases') ) );
		}

		if( !$this->isPurchasable() AND $this->canEnablePurchases() )
		{
			$menu->add( new Menu\Link( url: $this->url()->setQueryString( array( 'do' => 'purchaseStatus' , 'value' => 1 ) )->csrf(), languageString: Member::loggedIn()->language()->addToStack( 'enable_purchases') ) );
		}

		return $menu;
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return SystemFile|null
	 */
	public function primaryImage() : ?SystemFile
	{
		return $this->primary_screenshot_thumb ?? parent::primaryImage();
	}
}