<?php
/**
 * @brief		Packages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\modules\admin\store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Email;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\LicenseKey\Standard;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function is_array;
use function is_object;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Packages
 */
class packages extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\nexus\Package\Group';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'packages_manage' );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_store.js', 'nexus', 'admin' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__nexus_store_packages');
		parent::execute();
	}
		
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		$buttons = parent::_getRootButtons();
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['title'] = 'create_new_group';
		}
		
		return $buttons;
	}
	
	/**
	 * Fetch any additional HTML for this row
	 *
	 * @param	object	$node	Node returned from $nodeClass::load()
	 * @return	NULL|string
	 */
	public function _getRowHtml( object $node ): ?string
	{
		if ( $node instanceof Package and Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) )
		{
			$active = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=1', 'nexus', 'package', $node->_id ) )->first();
			$expired = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=0 AND ps_cancelled=0', 'nexus', 'package', $node->_id ) )->first();
			$canceled = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=0 AND ps_cancelled=1', 'nexus', 'package', $node->_id ) )->first();
			
			return Theme::i()->getTemplate( 'store', 'nexus' )->productRowHtml( $node, $active, $expired, $canceled );
		}

		return NULL;
	}
	
	/**
	 * Redirect after save
	 *
	 * @param	Model|null	$old			A clone of the node as it was before or NULL if this is a creation
	 * @param	Model	$new			The node now
	 * @param	string			$lastUsedTab	The tab last used in the form
	 * @return	void
	 */
	protected function _afterSave( ?Model $old, Model $new, mixed $lastUsedTab = FALSE ): void
	{
		if ( !( $new instanceof Package ) )
		{
			parent::_afterSave( $old, $new, $lastUsedTab );
		}
				
		$changes = array();
		if ( $old )
		{
			foreach ( $new::updateableFields() as $k )
			{
				if ( $old->$k != $new->$k )
				{
					$changes[ $k ] = $old->$k;
				}
			}
		}

		/* Clear cache */
		unset( Store::i()->nexusPackagesWithReviews );

		/* If something has changed, see if anyone has purchased */
		$purchases = 0;

		if( count( $changes ) )
		{
			$purchases = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'nexus', 'package', $new->id ) )->first();
		}		
		
		/* Only show this screen if the package has been purchased. Otherwise even just copying a package and saving asks if you want to update
			existing purchases unnecessarily */
		if ( !empty( $changes ) AND $purchases )
		{
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->decision( 'product_change_blurb', array(
				'product_change_blurb_existing'	=> Url::internal( "app=nexus&module=store&controller=packages&do=updateExisting&id={$new->_id}" )->setQueryString( 'changes', json_encode( $changes ) )->csrf(),
				'product_change_blurb_new'		=> $this->url->setQueryString( array( 'root' => ( $new->parent() ? $new->parent()->_id : '' ) ) ),
			) );
		}
		else
		{
			parent::_afterSave( $old, $new, $lastUsedTab );
		}
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		/* If &subnode=1 is not in the URL, we are deleting a group and not a package, so we don't need to check the package */
		if( !Request::i()->subnode )
		{
			parent::delete();
			return;
		}

		/* Load package */
		try
		{
			$package = Package::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			parent::delete();
			return;
		}
		
		/* Are there any purchases of this product? */
		$active = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=1', 'nexus', 'package', $package->_id ) )->first();
		$expiredRenewable = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=0 AND ps_cancelled=0 AND ps_renewals>0', 'nexus', 'package', $package->_id ) )->first();
		$expredNonRenewable = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=0 AND ps_cancelled=0 AND ps_renewals=0', 'nexus', 'package', $package->_id ) )->first();
		$canceledCanBeReactivated = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=0 AND ps_cancelled=1 AND ps_can_reactivate=1', 'nexus', 'package', $package->_id ) )->first();
		$canceledCannotBeReactivated = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=0 AND ps_cancelled=1 AND ps_can_reactivate=0', 'nexus', 'package', $package->_id ) )->first();
		if ( $active or $expiredRenewable or $canceledCanBeReactivated )
		{
			/* If this is an ajax request, then we need to redirect so we can show the confirmation screen */
			if( Request::i()->isAjax() OR ( isset( Request::i()->wasConfirmed ) AND Request::i()->wasConfirmed ) )
			{
				Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'delete', 'subnode' => 1, 'id' => $package->id ) ) );
			}

			$upgradeTo = FALSE;
			$prices = json_decode( $package->base_price, TRUE );
			if ( $package->renew_options )
			{
				$renewalOptions = json_decode( $package->renew_options, TRUE );
				if ( !empty( $renewalOptions ) )
				{
					$option = array_shift( $renewalOptions );						
					if ( $option['add'] )
					{
						foreach ( $prices as $currency => $_price )
						{
							$prices[ $currency ]['amount'] += ( $option['cost'][ $currency ]['amount'] );
						}
					}
				}
			}
			foreach ( $package->parent()->children() as $_package )
			{
				if ( $_package->id === $package->id )
				{
					continue;
				}

				/* We cannot upgrade to legacy packages */
				if( $_package->locked )
				{
					continue;
				}
				
				$_prices = json_decode( $_package->base_price, TRUE );
				if ( $_package->renew_options )
				{
					$renewalOptions = json_decode( $_package->renew_options, TRUE );
					if ( !empty( $renewalOptions ) )
					{
						$option = array_shift( $renewalOptions );						
						if ( $option['add'] )
						{
							foreach ( $_prices as $currency => $_price )
							{
								$_prices[ $currency ]['amount'] += ( $option['cost'][ $currency ]['amount'] );
							}
						}
					}
				}
				
				foreach ( $_prices as $currency => $_price )
				{
					if ( ( $_price['amount'] <= $prices[ $currency ]['amount'] and $_package->allow_upgrading ) or ( $_price['amount'] > $prices[ $currency ]['amount'] and $_package->allow_downgrading ) )
					{
						$upgradeTo = TRUE;
						break 2;
					}
				}
			}
			
			Output::i()->title = $package->_title;
			Output::i()->output = Theme::i()->getTemplate( 'store' )->productDeleteWarning( $package, $active, $expiredRenewable, $expredNonRenewable, $canceledCanBeReactivated, $canceledCannotBeReactivated, $upgradeTo );
			return;
		}
		
		/* If not, just handle the delete as normal */		
		parent::delete();
	}
	
	/**
	 * Hide from store
	 *
	 * @return	void
	 */
	public function hide() : void
	{	
		Session::i()->csrfCheck();
		
		/* Load package */
		try
		{
			$package = Package::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X249/3', 404, '' );
		}
		
		/* Do it */
		$package->store = FALSE;
		$package->reg = FALSE;
		$package->save();
		
		Session::i()->log( 'acplogs__nexus_package_hidden', array( 'nexus_package_' . $package->id => TRUE ) );
		
		/* Redirect */
		Output::i()->redirect( Url::internal( "app=nexus&module=store&controller=packages" )->setQueryString( array( 'root' => ( $package->parent() ? $package->parent()->_id : '' ) ) ) );
	}
	
	/**
	 * Update Existing Purchases
	 *
	 * @return	void
	 */
	public function updateExisting() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$package = Package::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X249/1', 404, '' );
		}
		
		$changes = json_decode( Request::i()->changes, TRUE );
				
		if ( !isset( Request::i()->processing ) )
		{
			if ( isset( $changes['renew_options'] ) )
			{
				Output::i()->bypassCsrfKeyCheck = TRUE;
				$matrix = new Matrix( 'matrix', 'continue' );
				$matrix->manageable = FALSE;
				
				$newOptions = array( '-' => Member::loggedIn()->language()->addToStack('do_not_change') );
				$existingRenewOptions = json_decode( $package->renew_options, TRUE );

				if( !is_array( $existingRenewOptions ) )
				{
					$existingRenewOptions = array();
				}

				foreach ( $existingRenewOptions as $k => $newOption )
				{
					$costs = array();
					foreach ( $newOption['cost'] as $data )
					{
						$costs[] = new Money( $data['amount'], $data['currency'] );
					}
					
					switch ( $newOption['unit'] )
					{
						case 'd':
							$term = Member::loggedIn()->language()->addToStack('renew_days', FALSE, array( 'pluralize' => array( $newOption['term'] ) ) );
							break;
						case 'm':
							$term = Member::loggedIn()->language()->addToStack('renew_months', FALSE, array( 'pluralize' => array( $newOption['term'] ) ) );
							break;
						case 'y':
							$term = Member::loggedIn()->language()->addToStack('renew_years', FALSE, array( 'pluralize' => array( $newOption['term'] ) ) );
							break;
					}
					
					$newOptions[ "o{$k}" ] = Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $costs, Member::loggedIn()->language()->get('or_list_format') ), $term ) ) );
				}
				$newOptions['z'] = Member::loggedIn()->language()->addToStack('remove_renewal_no_expire_leave');
				$newOptions['y'] = Member::loggedIn()->language()->addToStack('remove_renewal_no_expire_reactivate');
				$newOptions['x'] = Member::loggedIn()->language()->addToStack('remove_renewal_expire');
				$matrix->columns = array(
					'customers_currently_paying' => function( $key, $value, $data )
					{
						return $data[0];
					},
					'now_pay' => function( $key, $value, $data ) use ( $newOptions )
					{
						return new Select( $key, $data[1], TRUE, array( 'options' => $newOptions, 'noDefault' => TRUE ) );
					},
				);
				
				if ( $changes['renew_options'] )
				{
					foreach ( json_decode( $changes['renew_options'], TRUE ) as $k => $oldOption )
					{
						$costs = array();
						foreach ( $oldOption['cost'] as $data )
						{
							$costs[] = new Money( $data['amount'], $data['currency'] );
						}
						
						switch ( $oldOption['unit'] )
						{
							case 'd':
								$term = Member::loggedIn()->language()->addToStack('renew_days', FALSE, array( 'pluralize' => array( $oldOption['term'] ) ) );
								break;
							case 'm':
								$term = Member::loggedIn()->language()->addToStack('renew_months', FALSE, array( 'pluralize' => array( $oldOption['term'] ) ) );
								break;
							case 'y':
								$term = Member::loggedIn()->language()->addToStack('renew_years', FALSE, array( 'pluralize' => array( $oldOption['term'] ) ) );
								break;
						}
						
						$matrix->rows[ $k ] = array( Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $costs, Member::loggedIn()->language()->get('or_list_format') ), $term ) ) ), "o{$k}" );
					}
				}
				$matrix->rows['x'] = array( 'any_other_amount', '-' );
				
				if ( $values = $matrix->values() )
				{	
					$renewOptions = json_decode( $changes['renew_options'], TRUE );
					$changes['renew_options'] = array();
					if( !empty( $renewOptions ) )
					{
						foreach ( $renewOptions as $k => $data )
						{
							$changes['renew_options'][ $k ] = array( 'old' => $data, 'new' =>  $values[ $k ]['now_pay'] );
						}
					}

					$changes['renew_options']['x'] = array( 'old' => 'x', 'new' => $values['x']['now_pay'] );
				}
				else
				{					
					Output::i()->output .= $matrix;
					return;
				}
			}
		}
				
		if ( ( isset( $changes['tax'] ) or isset( $changes['renew_options'] ) ) and !isset( Request::i()->ba ) )
		{
			$needBaPrompt = FALSE;
			$canChangeOptions = FALSE;
			if ( isset( $changes['renew_options'] ) )
			{
				foreach ( $changes['renew_options'] as $ro )
				{
					if ( !in_array( $ro['new'], array( '-', 'x', 'y', 'z' ) ) )
					{
						$canChangeOptions = TRUE;
						$needBaPrompt = TRUE;
						break;
					}
				}
			}
			if ( isset( $changes['tax'] ) )
			{
				$needBaPrompt = TRUE;
			}
			
			if ( $needBaPrompt and $withBillingAgreement = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_billing_agreement>0 AND ba_canceled=0', 'nexus', 'package', $package->id ) )->join( 'nexus_billing_agreements', 'ba_id=ps_billing_agreement' )->first() )
			{
				$options = array(
					'change_renew_ba_skip'			=> Url::internal( "app=nexus&module=store&controller=packages&do=updateExisting" )->setQueryString( array(
						'id'		=> Request::i()->id,
						'changes'	=> json_encode( $changes ),
						'processing'=> 1,
						'ba'		=> 0
					) )->csrf(),
					'change_renew_ba_cancel'		=> Url::internal( "app=nexus&module=store&controller=packages&do=updateExisting" )->setQueryString( array(
						'id'		=> Request::i()->id,
						'changes'	=> json_encode( $changes ),
						'processing'=> 1,
						'ba'		=> 1
					) )->csrf()
				);
				if ( $canChangeOptions )
				{
					$options['change_renew_ba_go_back'] = Url::internal( "app=nexus&module=store&controller=packages&do=updateExisting" )->setQueryString( array(
						'id'		=> Request::i()->id,
						'changes'	=> Request::i()->changes,
					) )->csrf();
				}

				Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->decision( 'change_renew_ba_blurb', $options );
				return;
			}			
		}
				
		Output::i()->output = new MultipleRedirect(
			Url::internal( "app=nexus&module=store&controller=packages&do=updateExisting&id=1&changes=secondary_group" )->setQueryString( array(
				'id'		=> Request::i()->id,
				'changes'	=> json_encode( $changes ),
				'processing'=> 1,
				'ba'		=> isset( Request::i()->ba ) ? Request::i()->ba : 0
			) )->csrf(),
			function( $data ) use ( $package, $changes )
			{
				if( !is_array( $data ) )
				{
					$data['offset'] = 0;
					$data['lastId'] = 0;
				}

				$select = Db::i()->select( '*', 'nexus_purchases', array( "ps_id>? and ps_app=? and ps_type=? and ps_item_id=?", $data['lastId'], 'nexus', 'package', $package->id ), 'ps_id', 1 );
				
				try
				{
					$purchase = Purchase::constructFromData( $select->first() );
					$total = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( "ps_app=? and ps_type=? and ps_item_id=?", 'nexus', 'package', $package->id ) )->first();
					
					$package->updatePurchase( $purchase, $changes, Request::i()->ba );
					
					return array( [ 'offset' => ++$data['offset'], 'lastId' => $purchase->id ], Member::loggedIn()->language()->get('processing'), 100 / $total * $data['offset'] );
				}
				catch ( UnderflowException )
				{
					return NULL;
				}
				
			},
			function() use ( $package )
			{
				Output::i()->redirect( Url::internal( "app=nexus&module=store&controller=packages" )->setQueryString( array( 'root' => ( $package->parent() ? $package->parent()->_id : '' ) ) ) );
			}
		);
	}
	
	/**
	 * Build Product Options Table
	 *
	 * @return	void
	 */
	public function productoptions() : void
	{
		Dispatcher::i()->checkAcpPermission( 'packages_edit' );
		
		if ( !Request::i()->fields or !Request::i()->package )
		{
			Output::i()->sendOutput('');
		}
		
		try
		{
			$package = Package::load( Request::i()->package );
		
			$fields = iterator_to_array( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_package_fields', Db::i()->in( 'cf_id', explode( ',', Request::i()->fields ) ) ), 'IPS\nexus\Package\CustomField' ) );
			$allTheOptions = array();
			foreach ( $fields as $field )
			{
				$options = array();
				foreach ( json_decode( $field->extra, TRUE ) as $option )
				{
					$options[] = json_encode( array( $field->id, $option ) );
				}
				$allTheOptions[ $field->id ] = $options;
			}
			$_rows = $this->arraycartesian( $allTheOptions );
			
			$rows = array();
			foreach ( $_rows as $_options )
			{
				$options = array();
				foreach ( $_options as $encoded )
				{
					$decoded = json_decode( $encoded, TRUE );
					$options[ $decoded[0] ] = $decoded[1];
				}
				$rows[ json_encode( $options ) ] = $options;
			}
			
			$existingValues = iterator_to_array( Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=?', $package->id ) )->setKeyField( 'opt_values' ) );
									
			Output::i()->sendOutput( Theme::i()->getTemplate('store')->productOptionsTable( $fields, $rows, $existingValues, Request::i()->renews ) );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->sendOutput( $e->getMessage(), 500 );
		}
	}
	
	/**
	 * Little function from the PHP manual comments
	 *
	 * @param	array	$_	Array
	 * @return	array
	 */
	protected function arraycartesian( array $_) : array
	{
	    if(count($_) == 0)
	        return array(array());
		foreach($_ as $k=>$a) {
	    	unset($_[$k]);
	    	break;
	    }
	    $c = $this->arraycartesian($_);
	    $r = array();
	    foreach($a as $v)
	        foreach($c as $p)
	            $r[] = array_merge(array($v), $p);
	    return $r;
	}
	
	/**
	 * View Purchases
	 *
	 * @return	void
	 */
	protected function viewPurchases() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_view', 'nexus', 'customers' );
		
		try
		{
			$package = Package::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X249/2', 404, '' );
		}
		
		$table = new \IPS\Helpers\Table\Db( 'nexus_purchases', Url::internal( "app=nexus&module=store&controller=packages&do=viewPurchases&id={$package->id}" ), array( array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'nexus', 'package', $package->id ) ) );
		$table->include = array( 'ps_id', 'ps_member', 'purchase_status', 'ps_start', 'ps_expire', 'ps_renewals' );
		$table->quickSearch = 'ps_id';
		$table->advancedSearch = array(
			'ps_member'	=> SEARCH_MEMBER,
			'ps_start'	=> SEARCH_DATE_RANGE,
			'ps_expire'	=> SEARCH_DATE_RANGE,
		);
		$table->noSort = array( 'purchase_status' );
		
		if ( $package->renew_options or Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_active=0 AND ps_cancelled=0', 'nexus', 'package', $package->_id ) )->first() )
		{
			$table->filters = array( 'purchase_tab_active' => 'ps_active=1', 'purchase_tab_expired' => 'ps_active=0 AND ps_cancelled=0', 'purchase_tab_canceled' => 'ps_active=0 AND ps_cancelled=1' );
		}
		else
		{
			$table->filters = array( 'purchase_tab_active' => 'ps_active=1', 'purchase_tab_canceled' => 'ps_active=0' );
		}
		
		$table->parsers = array(
			'ps_member'	=> function( $val ) {
				try
				{
					return Theme::i()->getTemplate('global', 'nexus')->userLink( Member::load( $val ) );
				}
				catch ( OutOfRangeException )
				{
					return Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'purchase_status' => function( $val, $row ) {
				$purchase = Purchase::constructFromData( $row );
				if ( $purchase->cancelled )
				{
					return Member::loggedIn()->language()->addToStack('purchase_canceled');
				}
				elseif ( !$purchase->active )
				{
					return Member::loggedIn()->language()->addToStack('purchase_expired');
				}
				elseif ( $purchase->grace_period and ( $purchase->expire and $purchase->expire->getTimestamp() < time() ) )
				{
					return Member::loggedIn()->language()->addToStack('purchase_in_grace_period');
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('purchase_active');
				}
			},
			'ps_start'	=> function( $val ) {
				return DateTime::ts( $val );
			},
			'ps_expire'	=> function( $val ) {
				return $val ? DateTime::ts( $val ) : '--';
			},
			'ps_renewals' => function( $val, $row ) {
				$purchase = Purchase::constructFromData( $row );
				return $purchase->grouped_renewals ? Member::loggedIn()->language()->addToStack('purchase_grouped') : ( (string) ( $purchase->renewals ?: '--' ) );
			}
		);
		$table->rowButtons = function( $row ) {
			$purchase = Purchase::constructFromData( $row );
			return array_merge( array(
				'view'	=> array(
					'link'	=> $purchase->acpUrl()->setQueryString( 'popup', true ),
					'title'	=> 'view',
					'icon'	=> 'search',
				)
			), $purchase->buttons() );
		};
		
		Output::i()->title = $package->_title;
		Output::i()->output = $table;
		Output::i()->sidebar['actions'] = array(
			'cancel_purchases'	=> array(
				'icon'	=> 'arrow-right',
				'title'	=> 'mass_change_all_purchases',
				'link'	=> Url::internal( "app=nexus&module=store&controller=packages" )->setQueryString( array( 'do' => 'massManagePurchases', 'id' => $package->_id ) ),
				'data'	=> array(
					'ipsDialog'			=> TRUE,
					'ipsDialog-title'	=> $package->_title
				)
			),
		);
	}
	
	/**
	 * Mass Change/Cancel Purchases
	 *
	 * @return	void
	 */
	protected function massManagePurchases() : void
	{
		try
		{
			$package = Package::load( Request::i()->id );
			if ( $package->deleteOrMoveQueued() )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X249/4', 404, '' );
		}
		
		$form = new Form( 'form', 'go' );
		$form->addMessage('mass_change_purchases_explain');
		
		$options = array();
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_edit' ) )
		{
			$options['change'] = 'mass_change_purchases_change';
			$options['expire'] = 'mass_change_purchases_expire';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_cancel' ) )
		{
			$options['cancel'] = 'mass_change_purchases_cancel';
		}
		
		$upgradeDowngradeOptions = array();
		$toggles = array();
		$renewFields = array();
		$desciptions = [];
		foreach ( $package->parent()->children() as $siblingPackage )
		{
			if ( $package->id === $siblingPackage->id )
			{
				continue;
			}
			
			$upgradeDowngradeOptions[ $siblingPackage->id ] = $siblingPackage->_title;
			
			$renewOptions = json_decode( $siblingPackage->renew_options, TRUE ) ?: array();
			$renewalOptions = array();
			$renewFieldsDescriptions = array();
			foreach ( $renewOptions as $k => $option )
			{
				$renewTermObject = new RenewalTerm( new Money( 0, '' ), new DateInterval( 'P' . $option['term'] . mb_strtoupper( $option['unit'] ) ) );
				
				$costs = array();
				foreach ( $option['cost'] as $cost )
				{
					$costs[] =  new Money( $cost['amount'], $cost['currency'] );
				}
				
				$renewalOptions[ $k ] = sprintf( Member::loggedIn()->language()->get( 'renew_option'), implode( ' / ', $costs ), $renewTermObject->getTermUnit() );
			}
			
			if ( $renewalOptions )
			{
				$desciptions[ $siblingPackage->id ] = Member::loggedIn()->language()->formatList( $renewalOptions, Member::loggedIn()->language()->get('or_list_format') );
				$renewFields[] = new Radio( 'renew_option_' . $siblingPackage->id, NULL, NULL, array( 'options' => $renewalOptions, 'descriptions' => $renewFieldsDescriptions, 'parse' => 'normal' ), NULL, NULL, NULL, 'renew_option_' . $siblingPackage->id );
				Member::loggedIn()->language()->words['renew_option_' . $siblingPackage->id] = Member::loggedIn()->language()->addToStack( 'renewal_term', FALSE );
				$toggles[ $siblingPackage->id ] = array( 'renew_option_' . $siblingPackage->id );
			}
			else
			{
				$desciptions[ $siblingPackage->id ] = Member::loggedIn()->language()->addToStack('mass_change_purchases_no_renewals');
			}
		}
		
		$form->add( new Radio( 'cancel_type', NULL, TRUE, array(
			'options'	=> $options,
			'toggles'	=> array(
				'change' => array( 'mass_change_purchases_to', 'mass_change_purchases_override' ),
				'expire' => array( 'ps_can_reactivate' ),
				'cancel' => array( 'ps_can_reactivate' ),
			),
			'disabled'	=> $upgradeDowngradeOptions ? [] : ['change']
		) ) );
		if ( !$upgradeDowngradeOptions )
		{
			Member::loggedIn()->language()->words['mass_change_purchases_change_desc'] = Member::loggedIn()->language()->addToStack('cancel_type_change_no_siblings');
		}
		
		$form->add( new Radio( 'mass_change_purchases_to', NULL, NULL, array( 'options' => $upgradeDowngradeOptions, 'descriptions' => $desciptions, 'toggles' => $toggles, 'parse' => 'normal' ), NULL, NULL, NULL, 'mass_change_purchases_to' ) );
		foreach ( $renewFields as $field )
		{
			$form->add( $field );
		}
		$form->add( new YesNo( 'mass_change_purchases_override', TRUE, NULL, array(), NULL, NULL, NULL, 'mass_change_purchases_override' ) );

		$form->add( new YesNo( 'ps_can_reactivate', NULL, FALSE, array(), NULL, NULL, NULL, 'ps_can_reactivate' ) );
		
		if ( $values = $form->values() )
		{
			// Note: Maybe we cannot upgrade/downgrade cancelled purchases? Reflect that in the form if so
			// Note: Must cancel billing agreements when upgrading/downgrading purchases. Reflect that in the form
			
			$values['id'] = $package->_id;
			$values['admin'] = Member::loggedIn()->member_id;
			Task::queue( 'nexus', 'MassChangePurchases', $values );
			
			Output::i()->redirect( Url::internal( "app=nexus&module=store&controller=packages" )->setQueryString( array( 'root' => ( $package->parent() ? $package->parent()->_id : '' ) ) ), 'mass_change_purchases_confirm' );
		}
		
		Output::i()->title = $package->_title;
		Output::i()->output = $form;
	}
	
	/**
	 * Show Email Preview
	 *
	 * @return	void
	 */
	public function emailPreview() : void
	{
		Session::i()->csrfCheck();
		
		$functionName = 'emailPreview_' . mt_rand();
		Theme::makeProcessFunction( Request::i()->value, $functionName, '$purchase' );
		
		$dummyPurchase = new Purchase;
		$dummyPurchase->name = Member::loggedIn()->language()->addToStack('p_email_preview_example');
		$dummyPurchase->member = Member::loggedIn();
		$dummyPurchase->expire = DateTime::create()->add( new DateInterval('P1M') );
		$dummyPurchase->renewals = new RenewalTerm( new Money( 10, Customer::loggedIn()->defaultCurrency() ), new DateInterval('P1M') );
		$dummyPurchase->custom_fields = array_fill( 0, Db::i()->select( 'MAX(cf_id)', 'nexus_package_fields' )->first(), Member::loggedIn()->language()->addToStack('p_email_preview_example') );
		$dummyPurchase->licenseKey = new Standard;
		$dummyPurchase->licenseKey->key = 'XXXX-XXXX-XXXX-XXXX';
		
		try
		{
			$themeFunction = 'IPS\\Theme\\'. $functionName;
			$output = Email::buildFromContent( 'Test', $themeFunction( $dummyPurchase ), NULL, Email::TYPE_TRANSACTIONAL )->compileContent( 'html', Member::loggedIn() );
		}
		catch ( Exception $e )
		{
			$output = Theme::i()->getTemplate( 'global', 'core' )->message( $e->getMessage(), 'error', $e->getMessage(), TRUE, TRUE );
		}
		Output::i()->sendOutput( $output );
	}

	/**
	 * Build the form to mass move content
	 *
	 * @param	Form	$form	The form helper object
	 * @param	mixed			$data		Data from the wizard helper
	 * @param	string			$nodeClass	Node class
	 * @param	Model	$node		Node we are working with
	 * @param	string			$contentItemClass	Content item class (if there is one)
	 * @return Form
	 */
	protected function _buildMassMoveForm(Form $form, mixed $data, string $nodeClass, Model $node, string $contentItemClass ): Form
	{
		$form->addHeader('node_mass_move_delete_then');
		$moveToClass = $data['moveToClass'] ?? $nodeClass;
		$form->add( new Node( 'node_move_products', isset( $data['moveTo'] ) ? $moveToClass::load( $data['moveTo'] ) : 0, TRUE, array( 'class' => $nodeClass, 'disabledIds' => array( $node->_id ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'products_delete_content', 'subnodes' => FALSE ) ) );

		return $form;
	}

	/**
	 * Process the mass move form submission
	 *
	 * @param	array			$values		Values from form submission
	 * @param	mixed			$data		Data from the wizard helper
	 * @param	string			$nodeClass	Node class
	 * @param	Model	$node		Node we are working with
	 * @param	string			$contentItemClass	Content item class (if there is one)
	 * @return	array	Wizard helper data
	 */
	protected function _processMassMoveForm(array $values, mixed $data, string $nodeClass, Model $node, string $contentItemClass ): array
	{
		$data['deleteWhenDone'] = FALSE;
		$data['class'] = get_class( $node );
		$data['id'] = $node->_id;
		
		if ( is_object( $values['node_move_products'] ) )
		{
			$data['moveToClass'] = get_class( $values['node_move_products'] );
			$data['moveTo'] = $values['node_move_products']->_id;
		}
		else
		{
			unset( $data['moveToClass'] );
			unset( $data['moveTo'] );
		}

		return $data;
	}
}