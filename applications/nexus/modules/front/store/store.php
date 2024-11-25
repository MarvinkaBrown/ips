<?php
/**
 * @brief		Browse Store
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\modules\front\store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Package\Group;
use IPS\nexus\Package\Item;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfBoundsException;
use OutofRangeException;
use function count;
use function defined;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Browse Store
 */
class store extends Controller
{
	/**
	 * @brief	Products per page
	 */
	protected static int $productsPerPage = 50;
	
	/**
	 * @brief	Currency
	 */
	protected mixed $currency = NULL;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Set CSS */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'store.css', 'nexus' ) );

		/* Work out currency */
		if ( isset( Request::i()->currency ) and in_array( Request::i()->currency, Money::currencies() ) )
		{
			if ( isset( Request::i()->csrfKey ) and Login::compareHashes( (string) Session::i()->csrfKey, (string) Request::i()->csrfKey ) )
			{
				$_SESSION['cart'] = array();
				Request::i()->setCookie( 'currency', Request::i()->currency );

				Output::i()->redirect( Url::internal( 'app=nexus&module=store&controller=store', 'front', 'store' ) );
			}
			$this->currency = Request::i()->currency;
		}
		else
		{
			$this->currency = Customer::loggedIn()->defaultCurrency();
		}

		Output::setCacheTime( false );
		
		/* Pass up */
		parent::execute();
	}

	/**
	 * Browse Store
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		/* If we have a category, display it */
		if ( isset( Request::i()->cat ) )
		{
			$this->_categoryView();
		}
		
		/* Otherwise, display the index */
		else
		{
			$this->_indexView();
		}
	}
	
	/**
	 * Show a category
	 *
	 * @return	void
	 */
	protected function _categoryView() : void
	{
		/* Load category */
		try
		{
			$category = Group::loadAndCheckPerms( Request::i()->cat );
		}
		catch ( OutofRangeException )
		{
			Output::i()->error( 'node_error', '1X241/1', 404, '' );
		}
		$url = $category->url();
		
		/* Set initial stuff for fetching packages */
		$currentPage = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;
		if( $currentPage < 1 )
		{
			$currentPage = 1;
		}
		$where = array(
			array( 'p_group=?', $category->id ),
			array( 'p_store=1' ),
			array( "( p_member_groups='*' OR " . Db::i()->findInSet( 'p_member_groups', Member::loggedIn()->groups ) . ' )' )
		);
		$havePackagesWhichAcceptReviews = (bool) Db::i()->select( 'COUNT(*)', 'nexus_packages', array_merge( $where, array( array( 'p_reviewable=1' ) ) ) )->first();
		$havePackagesWhichUseStockLevels = (bool) Db::i()->select( 'COUNT(*)', 'nexus_packages', array_merge( $where, array( array( 'p_stock<>-1' ) ) ) )->first();
		$joins = array();
				
		/* Apply Filters */
		if ( isset( Request::i()->filter ) and is_array( Request::i()->filter ) )
		{
			$url = $url->setQueryString( 'filter', Request::i()->filter );
			foreach ( Request::i()->filter as $filterId => $allowedValues )
			{
				$filterId = (int) $filterId;
				$where[] = array( Db::i()->findInSet( "filter{$filterId}.pfm_values", array_map( 'intval', explode( ',', $allowedValues ) ) ) );
				$joins[] = array( 'table' => array( 'nexus_package_filters_map', "filter{$filterId}" ), 'on' => array( "filter{$filterId}.pfm_package=p_id AND filter{$filterId}.pfm_filter=?", $filterId ) );
			}
		}
		foreach ( array( 'minPrice' => '>', 'maxPrice' => '<' ) as $k => $op )
		{
			if ( isset( Request::i()->$k ) and is_numeric( Request::i()->$k ) and floatval( Request::i()->$k ) > 0 )
			{
				$url = $url->setQueryString( $k, Request::i()->$k );
				$joins['nexus_package_base_prices'] = array( 'table' => 'nexus_package_base_prices', 'on' => array( 'id=p_id' ) );
				$where[] = array( $this->currency . $op . '=?', floatval( Request::i()->$k ) );
			}
		}
		if ( isset( Request::i()->minRating ) and is_numeric( Request::i()->minRating ) and floatval( Request::i()->minRating ) > 0 )
		{
			$url = $url->setQueryString( 'minRating', Request::i()->minRating );
			$where[] = array( 'p_rating>=?', intval( Request::i()->minRating ) );
		}
		if ( isset( Request::i()->inStock ) )
		{
			$url = $url->setQueryString( 'inStock', Request::i()->inStock );
			$where[] = array( '( p_stock>0 OR ( p_stock=-2 AND (?)>0 ) )', Db::i()->select( 'MAX(opt_stock)', 'nexus_product_options', 'opt_package=p_id' ) );
		}
		
		/* Figure out the sorting */
		switch ( Request::i()->sortby )
		{
			case 'name':
				$joins['core_sys_lang_words'] = array( 'table' => 'core_sys_lang_words', 'on' => array( "word_app='nexus' AND word_key=CONCAT( 'nexus_package_', p_id ) AND lang_id=?", Member::loggedIn()->language()->id ) );
				$sortBy = 'word_custom';
				break;
				
			case 'price_low':
			case 'price_high':
				$joins['nexus_package_base_prices'] = array( 'table' => 'nexus_package_base_prices', 'on' => array( 'id=p_id' ) );
				$sortBy = Request::i()->sortby == 'price_low' ? $this->currency : ( $this->currency . ' DESC' );
				break;
				
			case 'rating':
				$sortBy = 'p_rating DESC';
				break;
				
			default:
				$sortBy = 'p_position';
				break;
		}
		
		/* Fetch the packages */
		$select = Db::i()->select( '*', 'nexus_packages', $where, $sortBy, array( ( $currentPage - 1 ) * static::$productsPerPage, static::$productsPerPage ) );
		foreach ( $joins as $join )
		{
			$select->join( $join['table'], $join['on'] );
		}
		$packages = new ActiveRecordIterator( $select, 'IPS\nexus\Package' );

		/* Get packages count */
		$select = Db::i()->select( 'COUNT(*)', 'nexus_packages', $where );
		foreach ( $joins as $join )
		{
			$select->join( $join['table'], $join['on'] );
		}
		$totalCount = $select->first();
		
		/* Pagination */
		$totalPages = ceil( $totalCount / static::$productsPerPage );
		if ( $totalPages and $currentPage > $totalPages )
		{
			Output::i()->redirect( $category->url()->setPage( 'page', $totalPages ), NULL, 303 );
		} 
		$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $category->url(), $totalPages, $currentPage, static::$productsPerPage );
		
		/* Other stuff we need for the view */
		$subcategories = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_package_groups', array( 'pg_parent=?', $category->id ), 'pg_position ASC' ), 'IPS\nexus\Package\Group' );
		$packagesWithCustomFields = array();
		foreach ( iterator_to_array( Db::i()->select( 'cf_packages', 'nexus_package_fields', 'cf_purchase=1' ) ) as $ids )
		{
			$packagesWithCustomFields = array_merge( $packagesWithCustomFields, array_filter( explode( ',', $ids ) ) );
		}
		
		/* Output */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array(
				'contents' 	=> Theme::i()->getTemplate('store')->categoryContents( $category, $subcategories, $packages, $pagination, $packagesWithCustomFields, $totalCount ),
				'sidebar'	=> Theme::i()->getTemplate('store')->categorySidebar( $category, $subcategories, $url, count( $packages ), $this->currency, $havePackagesWhichAcceptReviews, $havePackagesWhichUseStockLevels )
			)) ;
		}
		else
		{
			foreach ( $category->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			Output::i()->title = $category->_title;
			Output::i()->sidebar['contextual'] = Theme::i()->getTemplate('store')->categorySidebar( $category, $subcategories, $url, count( $packages ), $this->currency, $havePackagesWhichAcceptReviews, $havePackagesWhichUseStockLevels );

			/* Set default search */
			Output::i()->defaultSearchOption = array( 'nexus_package_item', "nexus_package_item_el" );

			Output::i()->output = Theme::i()->getTemplate('store')->category( $category, $subcategories, $packages, $pagination, $packagesWithCustomFields, $totalCount );
			Output::i()->globalControllers[] = 'nexus.front.store.category';
		}

		/* JSON-LD
			@note Google does not like categories marked up and instead recommends marking up each product separately.
			@link https://developers.google.com/search/docs/guides/intro-structured-data#multiple-entities-on-the-same-page */
		foreach( $packages as $package )
		{
			/* @var Package $package */
			$item	= Item::load( $package->id );

			try
			{
				$price = $package->price();
			}
			catch( OutOfBoundsException )
			{
				$price = NULL;
			}

			/* A product MUST have an offer, so if there's no price (i.e. due to currency configuration) don't even output */
			if( $price !== NULL )
			{
				Output::i()->jsonLd['package' . $package->id ]	= array(
					'@context'		=> "https://schema.org",
					'@type'			=> "Product",
					'name'			=> $package->_title,
					'description'	=> $item->truncated( TRUE, NULL ),
					'category'		=> $category->_title,
					'url'			=> (string) $package->url(),
					'sku'			=> $package->id,
					'offers'		=> array(
										'@type'			=> 'Offer',
										'price'			=> $price->amountAsString(),
										'priceCurrency'	=> $price->currency,
										'seller'		=> array(
															'@type'		=> 'Organization',
															'name'		=> Settings::i()->board_name
														),
									),
				);

				/* Stock levels */
				if( $package->stockLevel() === 0 )
				{
					Output::i()->jsonLd['package' . $package->id ]['offers']['availability'] = 'https://schema.org/OutOfStock';
				}
				else
				{
					Output::i()->jsonLd['package' . $package->id ]['offers']['availability'] = 'https://schema.org/InStock';
				}

				if( $package->image )
				{
					Output::i()->jsonLd['package' . $package->id ]['image'] = (string) $package->image;
				}

				if( $package->reviewable and $item->averageReviewRating() )
				{
					Output::i()->jsonLd['package' . $package->id ]['aggregateRating'] = array(
						'@type'			=> 'AggregateRating',
						'ratingValue'	=> $item->averageReviewRating(),
						'ratingCount'	=> $item->reviews
					);
				}
			}
		}		
	}
		
	/**
	 * Set a price filter
	 *
	 * @return	void
	 */
	protected function priceFilter() : void
	{
		if ( !isset( Request::i()->maxPrice ) )
		{
			Request::i()->maxPrice = 0;
		}
		
		$form = new Form( 'price_filter', 'filter' );
		$form->class = 'ipsForm--vertical ipsForm--price-filter';
		$form->add( new Number( 'minPrice', FALSE, 0, array( 'decimals' => Money::numberOfDecimalsForCurrency( $this->currency ) ), NULL, NULL, $this->currency ) );
		$form->add( new Number( 'maxPrice', FALSE, 0, array( 'decimals' => Money::numberOfDecimalsForCurrency( $this->currency ), 'unlimited' => 0 ), NULL, NULL, $this->currency ) );
		
		if ( $values = $form->values() )
		{
			$url = Request::i()->url()->setQueryString( 'do', NULL );
			if ( $values['minPrice'] )
			{
				$url = $url->setQueryString( 'minPrice', $values['minPrice'] );
			}
			else
			{
				$url = $url->setQueryString( 'minPrice', NULL );
			}
			if ( $values['maxPrice'] )
			{
				$url = $url->setQueryString( 'maxPrice', $values['maxPrice'] );
			}
			else
			{
				$url = $url->setQueryString( 'maxPrice', NULL );
			}
			Output::i()->redirect( $url );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('price_filter');
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Show the index
	 *
	 * @return	void
	 */
	protected function _indexView() : void
	{
		/* New Products */
		$newProducts = array();
		$nexus_store_new = explode( ',', Settings::i()->nexus_store_new );
		if ( $nexus_store_new[0] )
		{
			$newProducts = Item::getItemsWithPermission( array( array( 'p_date_added>?', DateTime::create()->sub( new DateInterval( 'P' . $nexus_store_new[1] . 'D' ) )->getTimestamp() ) ), 'p_date_added DESC', $nexus_store_new[0] );
		}
		
		/* Popular Products */
		$popularProducts = array();
		$nexus_store_popular = explode( ',', Settings::i()->nexus_store_popular );
		if ( $nexus_store_popular[0] )
		{
			$where = array();
			$where[] = array( 'ps_app=? AND ps_type=? AND ps_start>?', 'nexus', 'package', DateTime::create()->sub( new DateInterval( 'P' . $nexus_store_popular[1] . 'D' ) )->getTimestamp() );
			$where[] = "( p_member_groups='*' OR " . Db::i()->findInSet( 'p_member_groups', Member::loggedIn()->groups ) . ' )';
			$where[] = array( 'p_store=?', 1 );

			$popularIds = Db::i()->select( 'nexus_purchases.ps_item_id', 'nexus_purchases', $where, 'COUNT(ps_item_id) DESC', $nexus_store_popular[0], 'ps_item_id' )->join( 'nexus_packages', 'ps_item_id=p_id' );
			if( count( $popularIds ) )
			{
				$popularProducts = new ActiveRecordIterator( Db::i()->select( 'nexus_packages.*', 'nexus_packages', array( Db::i()->in( 'p_id', iterator_to_array( $popularIds ) ) ), 'FIELD(p_id, ' . implode( ',', iterator_to_array($popularIds) ) . ')' ), 'IPS\nexus\Package' );
			}
		}
					
		/* Display */
		Output::i()->sidebar['contextual'] = Theme::i()->getTemplate('store')->categorySidebar( );
		Output::i()->linkTags['canonical'] = (string) Url::internal( 'app=nexus&module=store&controller=store', 'front', 'store' );
		Output::i()->output = Theme::i()->getTemplate('store')->index( Customer::loggedIn()->cm_credits[ $this->currency ], $newProducts, $popularProducts );
		Output::i()->title = Member::loggedIn()->language()->addToStack('module__nexus_store');
	}
	
	/**
	 * Registration Packages
	 *
	 * @return	void
	 */
	public function register() : void
	{
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->output = Theme::i()->getTemplate('store')->register( Item::getItemsWithPermission( array( array( 'p_reg=1' ) ), 'pg_position, p_position', 10, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('sign_up');
	}
		
	/**
	 * View Cart
	 *
	 * @return	void
	 */
	protected function cart() : void
	{
		Output::i()->output = Theme::i()->getTemplate('store')->cart();
	}
}