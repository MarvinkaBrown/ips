<?php
/**
 * @brief		View product
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
use IPS\Content\Controller;
use IPS\core\Facebook\Pixel;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Package\CustomField;
use IPS\nexus\Package\Item;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfBoundsException;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View product
 */
class product extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\nexus\Package\Item';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_store.js', 'nexus', 'front' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'store.css', 'nexus' ) );

		Output::setCacheTime( false );
		
		parent::execute();
	}

	/**
	 * View
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		/* Init */
		if ( !isset( $_SESSION['cart'] ) )
		{
			$_SESSION['cart'] = array();
		}
		$memberCurrency = ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency() );
		
		/* Load Package */
		$item = parent::manage();
		if ( !$item )
		{
			Output::i()->error( 'node_error', '2X240/1', 404, '' );
		}
		$package = Package::load( $item->id );
		
		/* Do we have any in the cart already (this will affect stock level)? */
		$inCart = array();
		foreach ( $_SESSION['cart'] as $itemInCart )
		{
			if ( $itemInCart->id === $package->id )
			{
				$optionValues = array();
				foreach( $package->optionIdKeys() as $id )
				{
					$optionValues[ $id ] = $itemInCart->details[$id];
				}
				$optionValues = json_encode( $optionValues );
				if ( !isset( $inCart[ $optionValues ] ) )
				{
					$inCart[ $optionValues ] = 0;
				}
				$inCart[ $optionValues ] += $itemInCart->quantity;
			}
		}
						
		/* Showing just the form to purchase, or full product page? */
		if ( Request::i()->purchase )
		{
			Output::i()->output = Theme::i()->getTemplate('store')->purchaseForm( $package, $item, $this->_getForm( $package, $inCart, TRUE ) );
		}
		
		/* No - show the full page */
		else
		{
			/* Do we have renewal terms? */
			$renewalTerm = NULL;
			$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
			$initialTerm = NULL;
			if ( count( $renewOptions ) )
			{
				$renewalTerm = TRUE;
				if ( count( $renewOptions ) === 1 )
				{
					$renewalTerm = array_pop( $renewOptions );
					$renewalTerm = new RenewalTerm( new Money( $renewalTerm['cost'][ $memberCurrency ]['amount'], $memberCurrency ), new DateInterval( 'P' . $renewalTerm['term'] . mb_strtoupper( $renewalTerm['unit'] ) ), $package->tax ? Tax::load( $package->tax ) : NULL, $renewalTerm['add'] );
				}
				if ( $package->initial_term )
				{
					$term = mb_substr( $package->initial_term, 0, -1 );
					switch( mb_substr( $package->initial_term, -1 ) )
					{
						case 'D':
							$initialTerm = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get('renew_days'), array( $term ) );
							break;
						case 'M':
							$initialTerm = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get('renew_months'), array( $term ) );
							break;
						case 'Y':
							$initialTerm = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get('renew_years'), array( $term ) );
							break;
					}
				}
			}
			
			/* Display */
			$formKey = "package_{$package->id}_submitted";
			if ( Request::i()->isAjax() and isset( Request::i()->$formKey ) )
			{
				Output::i()->sendOutput( $this->_getForm( $package, $inCart, TRUE ), 500 );
			}
			else
			{
				/* Set default search */
				Output::i()->defaultSearchOption = array( 'nexus_package_item', "nexus_package_item_el" );
				
				Output::i()->output = Theme::i()->getTemplate('store')->package( $package, $item, $this->_getForm( $package, $inCart, TRUE ), array_sum( $inCart ), $renewalTerm, $initialTerm );
			}

			try
			{
				$price = $package->price();
			}
			catch( OutOfBoundsException )
			{
				$price = NULL;
			}

			/* Facebook Pixel */
			Pixel::i()->ViewContent = array(
				'content_ids' => array( $package->id ),
				'content_type' => 'product'
			);

			/* A product MUST have an offer, so if there's no price (i.e. due to currency configuration) don't even output */
			if( $price !== NULL )
			{
				Output::i()->jsonLd['package']	= array(
					'@context'		=> "https://schema.org",
					'@type'			=> "Product",
					'name'			=> $package->_title,
					'description'	=> $item->truncated( TRUE, NULL ),
					'category'		=> $item->container()->_title,
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

				/* Stock status */
				if( $package->stockLevel() === 0 )
				{
					Output::i()->jsonLd['package']['offers']['availability'] = 'https://schema.org/OutOfStock';
				}
				else
				{
					Output::i()->jsonLd['package']['offers']['availability'] = 'https://schema.org/InStock';
				}

				if( $package->image )
				{
					Output::i()->jsonLd['package']['image'] = (string) $package->image;
					Output::i()->metaTags['og:image'] = (string)$package->image;
				}

				if( $package->reviewable AND $item->averageReviewRating() )
				{
					Output::i()->jsonLd['package']['aggregateRating'] = array(
						'@type'			=> 'AggregateRating',
						'ratingValue'	=> $item->averageReviewRating(),
						'ratingCount'	=> $item->reviews
					);
				}
			}
		}
		return null;
	}

	/**
	 * Get form
	 *
	 * @param	Package	$package	The package
	 * @param	array				$inCart		The number in the cart already for each of the field combinations
	 * @param	bool				$verticalForm	Whether to output a vertical form (true) or not
	 * @return	string
	 */
	protected function _getForm( Package $package, array $inCart, bool $verticalForm = FALSE ) : string
	{
		/* Is this a subscription package that we've already bought? */
		if ( $package->subscription )
		{
			try
			{
				$purchase = Purchase::constructFromData( Db::i()->select( '*', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_cancelled=0 AND ps_member=?', 'nexus', 'package', $package->id, Member::loggedIn()->member_id ) )->first() );
				return Theme::i()->getTemplate( 'store', 'nexus' )->subscriptionPurchase( $purchase );
			}
			catch ( UnderflowException ) {}
		}
		
		/* Get member's currency */
		$memberCurrency = ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency() );
				
		/* Init form */		
		$form = new Form( "package_{$package->id}", 'add_to_cart' );

		if ( $verticalForm )
		{
			$form->class = 'ipsForm--vertical ipsForm--add-to-cart';
		}
		
		/* Package-dependant fields */
		$package->storeForm( $form, $memberCurrency );
		
		/* Are we in stock? */
		if ( $package->stock != -1 and $package->stock != -2 and ( $package->stock - array_sum( $inCart ) ) <= 0 )
		{
			$form->actionButtons = array();
			$form->addButton( 'out_of_stock', 'submit', NULL, 'ipsButton ipsButton--primary', array( 'disabled' => 'disabled' ) );
		}
		
		/* And is it available for our currency */
		else
		{
			try
			{
				$price = $package->price();
			}
			catch ( OutOfBoundsException )
			{
				$form->actionButtons = array();
				$form->addButton( 'currently_unavailable', 'submit', NULL, 'ipsButton ipsButton--primary', array( 'disabled' => 'disabled' ) );
			}
		}

		/* Associate */
		if ( count( $package->associablePackages() ) )
		{
			$associableIds = array_keys( $package->associablePackages() );
			$associableOptions = array();
			foreach ( $_SESSION['cart'] as $k => $item )
			{
				if ( in_array( $item->id, $associableIds ) )
				{
					for ( $i = 0; $i < $item->quantity; $i++ )
					{
						$name = $item->name;
						if ( count( $item->details ) )
						{
							$customFields = CustomField::roots();
							$stickyFields = array();
							foreach ( $item->details as $_k => $v )
							{
								if ( $v and isset( $customFields[ $_k ] ) and $customFields[ $_k ]->sticky )
								{
									$stickyFields[] = $v;
								}
							}
							if ( count( $stickyFields ) )
							{
								$name .= ' (' . implode( ' &middot; ', $stickyFields ) . ')';
							}
						}
						$associableOptions['in_cart']["0.{$k}"] = $name;
					}
				}
			}
			foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', array( array( 'ps_member=? AND ps_app=? AND ps_type=?', Customer::loggedIn()->member_id, 'nexus', 'package' ), Db::i()->in( 'ps_item_id', $associableIds ) ) ), 'IPS\nexus\Purchase' ) as $purchase )
			{
				$associableOptions['existing_purchases']["1.{$purchase->id}"] = $purchase->name;
			}
			
			if ( !empty( $associableOptions ) )
			{
				if ( !$package->force_assoc )
				{
					array_unshift( $associableOptions, 'do_not_associate' );
				}
				$form->add( new Select( 'associate_with', NULL, $package->force_assoc, array( 'options' => $associableOptions ) ) );
			}
			elseif ( $package->force_assoc )
			{
				return Member::loggedIn()->language()->addToStack("nexus_package_{$package->id}_assoc");
			}
		}
		
		/* Renewal options */
		$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
		if ( count( $renewOptions ) > 1 )
		{
			$sortedRenewOptions = array();
			foreach ( $renewOptions as $k => $option )
			{
				if ( isset( $option['cost'][ $memberCurrency ] ) )
				{
					$sortedRenewOptions[ $k ] = new RenewalTerm( new Money( $option['cost'][ $memberCurrency ]['amount'], $memberCurrency ), new DateInterval( 'P' . $option['term'] . mb_strtoupper( $option['unit'] ) ), $package->tax ? Tax::load( $package->tax ) : NULL, $option['add'], $package->grace_period ? new DateInterval( 'P' . $package->grace_period . 'D' ) : NULL );
				}
			}
			uasort( $sortedRenewOptions, function( $a, $b ) {
				return $a->days() - $b->days();
			} );
			
			$options = array();
			$first = NULL;
			foreach ( $sortedRenewOptions as $k => $term )
			{
				$saving = NULL;
				if ( Settings::i()->nexus_show_renew_option_savings != 'none' )
				{
					if ( $first === NULL )
					{
						$first = $term;
					}
					else
					{
						$saving = $term->diff( $first, Settings::i()->nexus_show_renew_option_savings == 'percent' );
					}
				}
				
				if ( $saving and ( ( Settings::i()->nexus_show_renew_option_savings == 'percent' and $saving->isGreaterThanZero() ) or ( Settings::i()->nexus_show_renew_option_savings == 'amount' and $saving->amount->isGreaterThanZero() ) ) )
				{
					if ( Settings::i()->nexus_show_renew_option_savings == 'percent' )
					{
						$options[ $k ] = Member::loggedIn()->language()->addToStack( 'renewal_amount_with_pc_saving', FALSE, array( 'sprintf' => array( $term->toDisplay(), $saving->round(1) ) ) );
					}
					else
					{
						$options[ $k ] = Member::loggedIn()->language()->addToStack( 'renewal_amount_with_cost_saving', FALSE, array( 'sprintf' => array( $term->toDisplay(), $saving ) ) );
					}
				}
				else
				{
					$options[ $k ] = $term->toDisplay();
				}
			}
			
			ksort( $options );

			$form->add( new Radio( 'renewal_term', NULL, TRUE, array( 'options' => $options ) ) );
		}
				
		/* Custom Fields */
		$customFields = CustomField::roots( NULL, NULL, array( array( 'cf_purchase=1' ), array( Db::i()->findInSet( 'cf_packages', array( $package->id ) ) ) ) );
		foreach ( $customFields as $field )
		{
			/* @var CustomField $field */
			if( Request::i()->isAjax() && isset( Request::i()->stockCheck ) )
			{
				$field->required = FALSE; // Otherwise a required text field (for example) can block the price changing when a different radio (for example) value is selected
			}
			
			$form->add( $field->buildHelper() );
		}
		
		/* Is this the validation for the additional page? */
		if ( Request::i()->isAjax() and isset( Request::i()->additionalPageCheck ) )
		{
			if ( $additionalPage = $package->storeAdditionalPage( $_POST ) )
			{
				Output::i()->httpHeaders['X-IPS-FormNoSubmit'] = "true";
				Output::i()->json( $additionalPage );
			}
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Custom fields */
			$details = array();
			$editorUploadIds = array();
			foreach ( $customFields as $field )
			{
				if ( isset( $values[ 'nexus_pfield_' . $field->id ] ) )
				{
					$class = $field->buildHelper();
					if ( $class instanceof Upload )
					{
						$details[ $field->id ] = (string) $values[ 'nexus_pfield_' . $field->id ];
					}
					else
					{
						$details[ $field->id ] = $class::stringValue( $values[ 'nexus_pfield_' . $field->id ] );
					}
					
					if ( !isset( Request::i()->stockCheck ) and $field->type === 'Editor' )
					{
						$uploadId = Db::i()->insert( 'nexus_cart_uploads', array(
							'session_id'	=> Session::i()->id,
							'time'			=> time()
						) );
						$field->claimAttachments( $uploadId, 'cart' );
						$editorUploadIds[] = $uploadId;
					}
				}
			}
			$optionValues = $package->optionValues( $details );
						
			/* Stock check */
			$quantity = $values['quantity'] ?? 1;
			try
			{
				$data = $package->optionValuesStockAndPrice( $optionValues, TRUE );
			}
			catch ( UnderflowException )
			{
				Output::i()->error( 'product_options_price_error', '3X240/2', 500, 'product_options_price_error_admin' );
			}
			$inCartForThisFieldCombination = isset( $inCart[ json_encode( $optionValues ) ] ) ? $inCart[ json_encode( $optionValues ) ] : 0;
			
			if ( Request::i()->isAjax() && isset( Request::i()->stockCheck ) )
			{
				/* Stock */
				if ( $data['stock'] == -1 )
				{
					$return = array(
						'stock'	=> '',
						'okay'	=> true
					);
				}
				else
				{					
					$return = array(
						'stock'	=> Member::loggedIn()->language()->addToStack( 'x_in_stock', FALSE, array( 'pluralize' => array( $data['stock'] - $inCartForThisFieldCombination ) ) ),
						'okay'	=> ( $data['stock'] - $inCartForThisFieldCombination > 0 ),
					);
				}
							
				/* Price */	
				$_data = $package->optionValuesStockAndPrice( $optionValues, FALSE );
				$normalPrice = $_data['price'];
				
				/* Renewals */
				$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
				if ( !empty( $renewOptions ) )
				{
					$term = ( isset( Request::i()->renewal_term ) and isset( $renewOptions[ Request::i()->renewal_term ] ) ) ? $renewOptions[ Request::i()->renewal_term ] : array_shift( $renewOptions );

					switch( $term['unit'] )
					{
						case 'd':
							$initialTerm = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get('renew_days'), array( $term['term'] ) );
							break;
						case 'm':
							$initialTerm = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get('renew_months'), array( $term['term'] ) );
							break;
						case 'y':
							$initialTerm = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get('renew_years'), array( $term['term'] ) );
							break;
					}

					$return['initialTerm'] = sprintf( Member::loggedIn()->language()->get('package_initial_term_title'), $initialTerm );

					if ( $term['add'] )
					{
						$data['price']->amount = $data['price']->amount->add( new Number( number_format( $term['cost'][ $memberCurrency ]['amount'], Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) );
						$normalPrice->amount = $normalPrice->amount->add( new Number( number_format( $term['cost'][ $memberCurrency ]['amount'], Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) );
					}
					
					$return['renewal'] = ( new RenewalTerm(
						new Money(
							( new Number( number_format( $term['cost'][ $memberCurrency ]['amount'], Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) )
								->add( ( new Number( number_format( $_data['renewalAdjustment'], Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) ) )
						, $memberCurrency ),
						new DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ),
						$package->tax ? Tax::load( $package->tax ) : NULL, $term['add']
					) )->toDisplay();
				}
				else
				{
					$return['renewal'] = '';
				}
				
				/* Include tax? */
				if ( Settings::i()->nexus_show_tax and $package->tax )
				{
					try
					{
						$taxRate = new Number( Tax::load( $package->tax )->rate( Customer::loggedIn()->estimatedLocation() ) );
						
						$data['price']->amount = $data['price']->amount->add( $data['price']->amount->multiply( $taxRate ) );
						$normalPrice->amount = $normalPrice->amount->add( $normalPrice->amount->multiply( $taxRate ) );
					}
					catch ( OutOfRangeException ) { }
				}
				
				/* Format and return */
				if ( $data['price']->amount->compare( $normalPrice->amount ) !== 0 )
				{
					$return['price'] = Theme::i()->getTemplate( 'store', 'nexus' )->priceDiscounted( $normalPrice, $data['price'], FALSE, FALSE, NULL );
				}
				else
				{
					$return['price'] = Theme::i()->getTemplate( 'store', 'nexus' )->price( $data['price'], FALSE, FALSE, NULL );
				}
				Output::i()->json( $return );
			}
			elseif ( $data['stock'] != -1 and ( $data['stock'] - $inCartForThisFieldCombination ) < $quantity )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'not_enough_in_stock', FALSE, array( 'sprintf' => array( $data['stock'] - $inCartForThisFieldCombination ) ) );
				return (string) $form;
			}
			
			if ( ( !isset( Request::i()->additionalPageCheck ) or !Request::i()->isAjax() ) and $additionalPage = $package->storeAdditionalPage( $_POST ) )
			{
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( array( 'dialog' => $additionalPage ) );
				}
				else
				{
					return $additionalPage;
				}
			}
						
			/* Work out renewal term */
			$renewalTerm = NULL;
			if ( count( $renewOptions ) )
			{
				if ( count( $renewOptions ) === 1 )
				{
					$chosenRenewOption = array_pop( $renewOptions );
				}
				else
				{
					$chosenRenewOption = $renewOptions[ $values['renewal_term'] ];
				}
				
				$renewalTerm = new RenewalTerm( new Money( ( new Number( number_format( $chosenRenewOption['cost'][ $memberCurrency ]['amount'], 2, '.', '' ) ) )->add( new Number( number_format( $data['renewalAdjustment'], 2, '.', '' ) ) ), $memberCurrency ), new DateInterval( 'P' . $chosenRenewOption['term'] . mb_strtoupper( $chosenRenewOption['unit'] ) ), $package->tax ? Tax::load( $package->tax ) : NULL, $chosenRenewOption['add'], $package->grace_period ? new DateInterval( 'P' . $package->grace_period . 'D' ) : NULL );
			}
			
			/* Associations */
			$parent = NULL;
			if ( isset( $values['associate_with'] ) and $values['associate_with'] )
			{
				$exploded = explode( '.', $values['associate_with'] );
				if ( $exploded[0] )
				{
					$parent = Purchase::load( $exploded[1] );
				}
				else
				{
					$parent = (int) $exploded[1];
				}
			}
			
			/* Actually add to cart */
			$cartId = $package->addItemsToCartData( $details, $quantity, $renewalTerm, $parent, $values );
			Db::i()->update( 'nexus_cart_uploads', array( 'item_id' => $cartId ), Db::i()->in( 'id', $editorUploadIds ) );

			/* Redirect or AJAX */
			if ( Request::i()->isAjax() )
			{
				/* Upselling? */
				$upsell = Item::getItemsWithPermission( array( array( 'p_upsell=1' ), array( Db::i()->findInSet( 'p_associable', array( $package->id ) ) ) ), 'p_position' );
				
				/* Send */
				Output::i()->json( array( 'dialog' => Theme::i()->getTemplate('store')->cartReview( $package, $quantity, $upsell ), 'cart' => Theme::i()->getTemplate('store')->cartHeader(), 'css' => Output::i()->cssFiles ) );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=nexus&module=store&controller=cart&added=' . $package->id, 'front', 'store_cart' ) );
			}			
		}
		
		
		return (string) $form;
	}
}