<?php
/**
 * @brief		Product Review
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		5 May 2014
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Hideable;
use IPS\Content\Reportable;
use IPS\Content\Review as ContentReview;
use IPS\Content\Shareable;
use IPS\core\Approval;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function get_called_class;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package Review
 */
class Review extends ContentReview implements Embeddable,
	Filter
{
	use Reportable, Shareable, EditHistory, Hideable;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static ?string $itemClass = 'IPS\nexus\Package\Item';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_reviews';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'review_';

	/**
	 * @brief	Title
	 */
	public static string $title = 'product_reviews';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'box';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'product',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'text',
		'date'				=> 'date',
		'approved'			=> 'approved',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_date',
		'edit_show'			=> 'edit_show',
		'edit_member_name'	=> 'edit_member_name',
		'edit_reason'		=> 'edit_reason',
		'edit_member_id'	=> 'edit_member_id',
		'rating'			=> 'rating',
		'votes_total'		=> 'votes',
		'votes_helpful'		=> 'useful',
		'votes_data'		=> 'vote_data',
		'author_response'	=> 'author_response',
	);
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'store';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'nexus-products';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static bool $firstCommentRequired = FALSE;
	
	/**
	 * @brief	Include In Sitemap
	 */
	public static bool $includeInSitemap = FALSE;
	
	/**
	 * Get content for header in content tables
	 *
	 * @return	callable
	 */
	public function contentTableHeader(): string
	{
		return (string) Theme::i()->getTemplate( 'global', static::$application )->commentTableHeader( $this, Package::load( $this->item()->id ), $this->item() );
	}

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
		return Theme::i()->getTemplate( 'global', 'nexus' )->embedProductReview( $this, $this->item(), $renewalTerm, $this->url()->setQueryString( $params ) );
	}
	
	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate(): void
	{
		parent::postCreate();
		
		/* If this review is moderated, then let the parent class do it's thing and if it didn't find a reason, see if this specific product requires reviews to be approved */
		if ( $this->hidden() === 1 )
		{
			try
			{
				Approval::loadFromContent( get_called_class(), $this->id );
			}
			catch( OutOfRangeException )
			{
				/* No reason found - see if product requires approval of reviews */
				if ( $this->item()->review_moderate )
				{
					$log = new Approval;
					$log->content_class	= get_called_class();
					$log->content_id	= $this->id;
					$log->held_reason	= 'item';
					$log->save();
				}
			}
		}
	}
}