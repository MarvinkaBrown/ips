<?php
/**
 * @brief		ACP Member Profile Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		05 Dec 2017
 */

namespace IPS\nexus\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\core\MemberACPProfile\TabbedBlock;
use IPS\DateTime;
use IPS\Helpers\Chart;
use IPS\Helpers\Table\Custom;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Gateway;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\nexus\Subscription;
use IPS\nexus\Transaction;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile Block
 *
 * @property Customer $member
 */
class AccountInformation extends TabbedBlock
{
	/**
	 * Get Tab Names
	 *
	 * @return	array
	 */
	public function tabs(): array
	{
		$tabs = array(
			'overview'	=> array(
				'icon'		=> 'address-card',
				'count'		=> 0
			)
		);
		if ( count( Gateway::cardStorageGateways() ) )
		{
			$tabs['cards'] = array(
				'icon'		=> 'credit-card',
				'count'		=> \IPS\Db::i()->select( 'COUNT(*)', 'nexus_customer_cards', array( 'card_member=?', $this->member->member_id ) )->first()
			);
		}
		if ( count( Gateway::billingAgreementGateways() ) )
		{
			$tabs['paypal'] = array(
				'icon'		=> 'paypal',
				'count'		=> \IPS\Db::i()->select( 'COUNT(*)', 'nexus_billing_agreements', array( 'ba_member=? AND ba_canceled=0', $this->member->member_id ) )->first()
			);
		}
		$tabs['alts'] = array(
			'icon'		=> 'user',
			'count'		=> \IPS\Db::i()->select( 'COUNT(*)', 'nexus_alternate_contacts', array( 'main_id=?', $this->member->member_id ) )->first()
		);

		return $tabs;
	}
	
	/**
	 * Get output: OVERVIEW
	 *
	 * @return	mixed
	 */
	protected function _overview(): mixed
	{
		/* Sparkline */
		$sparkline = NULL;

		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view_statistics' ) )
		{
			$rows = array();
			$oneYearAgo = DateTime::create()->sub( new DateInterval( 'P1Y' ) );
			$date = clone $oneYearAgo;
			$endOfLastMonth = mktime( 23, 59, 59, date( 'n' ) - 1, date( 't' ), date( 'Y' ) );
			while ( $date->getTimestamp() < $endOfLastMonth )
			{
				foreach ( Money::currencies() as $currency )
				{
					$rows[$date->format( 'n Y' )][$currency] = 0;
				}
				$date->add( new DateInterval( 'P1M' ) );
			}
			$sparkline = new Chart;
			foreach ( \IPS\Db::i()->select( 'DATE_FORMAT( FROM_UNIXTIME(t_date), \'%c %Y\' ) AS time, SUM(t_amount)-SUM(t_partial_refund) AS amount, t_currency', 'nexus_transactions', array(array("t_member=? AND ( t_status=? OR t_status=? ) AND t_method>0 AND t_date>? AND t_date<?", $this->member->member_id, Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED, $oneYearAgo->getTimestamp(), time())), NULL, NULL, array('time', 't_currency') ) as $row )
			{
				if ( isset( $rows[$row['time']][$row['t_currency']] ) ) // Currency may no longer exist
				{
					$rows[$row['time']][$row['t_currency']] += $row['amount'];
				}
			}
			$sparkline->addHeader( Member::loggedIn()->language()->addToStack( 'date' ), 'date' );
			foreach ( Money::currencies() as $currency )
			{
				$sparkline->addHeader( $currency, 'number' );
			}
			foreach ( $rows as $time => $row )
			{
				$datetime = new DateTime;
				$datetime->setTime( 0, 0 );
				$exploded = explode( ' ', $time );
				$datetime->setDate( $exploded[1], $exploded[0], 1 );

				foreach ( $row as $currency => $value )
				{
					$row[$currency] = number_format( $value, 2, '.', '' );
				}

				$sparkline->addRow( array_merge( array($datetime), $row ) );
			}

			$sparkline = $sparkline->render( 'AreaChart', array(
				'areaOpacity' => 0.4,
				'backgroundColor' => '#fff',
				'colors' => array('#10967e'),
				'chartArea' => array(
					'left' => 0,
					'top' => 0,
					'width' => '100%',
					'height' => '100%',
				),
				'hAxis' => array(
					'baselineColor' => '#F3F3F3',
					'gridlines' => array(
						'count' => 0,
					)
				),
				'height' => 60,
				'legend' => array(
					'position' => 'none',
				),
				'lineWidth' => 1,
				'vAxis' => array(
					'baselineColor' => '#F3F3F3',
					'gridlines' => array(
						'count' => 0,
					)
				),
			) );
		}
		
		/* Primary Billing Address */
		$primaryBillingAddress = $this->member->primaryBillingAddress();
		$addressCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_customer_addresses', array( '`member`=?', $this->member->member_id ) )->first();
				
		/* Display */
		return Theme::i()->getTemplate( 'customers', 'nexus' )->accountInformationOverview( $this->member, $sparkline, $primaryBillingAddress, $addressCount );
	}
	
	/**
	 * Get output: STORED PAYMENT METHODS
	 *
	 * @param	bool	$edit	Edit view?
	 * @return	mixed
	 */
	protected function _cards( bool $edit = FALSE ): mixed
	{
		$cards = array();
		foreach ( new ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_customer_cards', array( 'card_member=?', $this->member->member_id ), NULL, $edit ? NULL : 10 ), 'IPS\nexus\Customer\CreditCard' ) as $card )
		{
			/* @var Customer\CreditCard $card */
			try
			{
				$cardData = $card->card;
				$cards[ $card->id ] = array(
					'id'			=> $card->id,
					'card_type'		=> $cardData->type,
					'card_member'	=> $card->member->member_id,
					'card_number'	=> $cardData->lastFour ?: $cardData->number,
					'card_expire'	=> ( !is_null( $cardData->expMonth ) AND !is_null( $cardData->expYear ) ) ? str_pad( $cardData->expMonth , 2, '0', STR_PAD_LEFT ). '/' . $cardData->expYear : NULL
				);
			}
			catch ( Exception ) { }
		}
		$cards = new Custom( $cards, $this->member->acpUrl()->setQueryString( 'view', 'cards' ) );
		
		if ( Gateway::cardStorageGateways( TRUE ) )
		{
			$cards->rootButtons = array(
				'add'	=> array(
					'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( 'do', 'addCard' ),
					'title'	=> 'add',
					'icon'	=> 'plus',
					'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add_card') )
				)
			);
		}
		$cards->rowButtons = function( $row )
		{
			return array(
				'delete'	=> array(
					'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'deleteCard', 'card_id' => $row['id'] ) ),
					'title'	=> 'delete',
					'icon'	=> 'times-circle',
					'data'	=> array( 'delete' => '' )
				)
			);
		};
		
		if ( $edit )
		{
			$cards->tableTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'cardsTable' );
			$cards->rowsTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'cardsTableRows' );

			return Theme::i()->getTemplate( 'customers', 'nexus' )->customerPopup( $cards );
		}
		else
		{
			$cards->tableTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'cardsOverview' );
			$cards->rowsTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'cardsOverviewRows' );
			
			$cardCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_customer_cards', array( 'card_member=?', $this->member->member_id ) )->first();
			return Theme::i()->getTemplate( 'customers', 'nexus' )->accountInformationTablePreview( $this->member, $cards, Member::loggedIn()->language()->addToStack( 'num_credit_card', FALSE, array( 'pluralize' => array( $cardCount ) ) ), 'cards' );
		}
	}
	
	/**
	 * Get output: BILLING AGREEMENTS
	 *
	 * @param	bool	$edit	Edit view?
	 * @return	mixed
	 */
	protected function _paypal( bool $edit = FALSE ): mixed
	{
		$billingAgreementCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_billing_agreements', array( 'ba_member=? AND ba_canceled=0', $this->member->member_id ) )->first();
		$billingAgreements = array();
		foreach ( \IPS\Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_member=? AND ba_canceled=0', $this->member->member_id ), NULL, $edit ? NULL : 10 ) as $billingAgreement )
		{
			$billingAgreements[ $billingAgreement['ba_id'] ] = array(
				'id'						=> $billingAgreement['ba_id'],
				'gw_id'						=> $billingAgreement['ba_gw_id'],
				'started'					=> $billingAgreement['ba_started'],
				'next_cycle'				=> $billingAgreement['ba_next_cycle'],
			);
		}
		$billingAgreements = new Custom( $billingAgreements, $this->member->acpUrl()->setQueryString( 'view', 'billingagreements' ) );
		$billingAgreements->parsers = array(
			'started'	=> function( $val ) {
				return $val ? DateTime::ts( $val )->relative() : null;
			},
			'next_cycle'	=> function( $val ) {
				return $val ? DateTime::ts( $val )->relative() : null;
			},
		);
		$billingAgreements->rowButtons = function( $row, $id )
		{
			return array(
				'view'	=> array(
					'link'	=> Url::internal("app=nexus&module=payments&controller=billingagreements&id={$id}"),
					'title'	=> 'view',
					'icon'	=> 'search',
				)
			);
		};
		if ( $edit )
		{
			$billingAgreements->exclude = array( 'id', 'last_transaction_currency' );
			$billingAgreements->langPrefix = 'ba_';
			return Theme::i()->getTemplate( 'customers', 'nexus' )->customerPopup( $billingAgreements );
		}
		else
		{
			$billingAgreements->tableTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'billingAgreementsOverview' );
			$billingAgreements->rowsTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'billingAgreementsOverviewRows' );
			
			return Theme::i()->getTemplate( 'customers', 'nexus' )->accountInformationTablePreview( $this->member, $billingAgreements, Member::loggedIn()->language()->addToStack( 'num_billing_agreements', FALSE, array( 'pluralize' => array( $billingAgreementCount ) ) ), 'paypal' );
		}
	}
	
	/**
	 * Get output: ALTERNATE CONTACTS
	 *
	 * @param	bool	$edit	Edit view?
	 * @return	mixed
	 */
	protected function _alts( bool $edit = FALSE ): mixed
	{
		$altContactCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_alternate_contacts', array( 'main_id=?', $this->member->member_id ) )->first();
		$alternativeContacts = new Db( 'nexus_alternate_contacts', $this->member->acpUrl()->setQueryString( 'view', 'alternatives' ), array( 'main_id=?', $this->member->member_id ) );
		$alternativeContacts->langPrefix = 'altcontactTable_';
		$alternativeContacts->include = array( 'alt_id', 'purchases', 'billing' );
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_details' ) )
		{
			$alternativeContacts->parsers = array(
				'alt_id'	=> function( $val )
				{
					return  Theme::i()->getTemplate( 'global', 'nexus' )->userLink( Customer::load( $val ) );
				},
				'email'		=> function ( $val, $row )
				{
					return htmlspecialchars( Customer::load( $row['alt_id'] )->email, ENT_DISALLOWED, 'UTF-8', FALSE );
				},
				'purchases'	=> function( $val )
				{
					return implode( '<br>', array_map( function( $id )
					{
						try
						{
							return Theme::i()->getTemplate( 'purchases', 'nexus' )->link( Purchase::load( $id ) );
						}
						catch ( OutOfRangeException )
						{
							return '';
						}
					}, explode( ',', $val ) ) );
				},
				'billing'	=> function( $val )
				{
					return $val ? "<i class='fa-solid fa-check'></i>" : "<i class='fa-solid fa-xmark'></i>";
				}
			);
			
			$alternativeContacts->rootButtons = array(
				'add'	=> array(
					'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( 'do', 'alternativeContactForm' ),
					'title'	=> 'add',
					'icon'	=> 'plus',
					'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('altcontact_add') )
				)
			);
			$alternativeContacts->rowButtons = function( $row )
			{
				return array(
					'edit'	=> array(
						'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'alternativeContactForm', 'alt_id' => $row['alt_id'] ) ),
						'title'	=> 'edit',
						'icon'	=> 'pencil',
						'data'	=> array( 'ipsDialog' => true )
					),
					'delete'	=> array(
						'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'deleteAlternativeContact', 'alt_id' => $row['alt_id'] ) ),
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'data'	=> array( 'delete' => '' )
					)
				);
			};
		}
		if ( $edit )
		{
			return Theme::i()->getTemplate( 'customers', 'nexus' )->customerPopup( $alternativeContacts );
		}
		else
		{
			$alternativeContacts->include[] = 'email';
			$alternativeContacts->limit = 2;
			$alternativeContacts->tableTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'altContactsOverview' );
			$alternativeContacts->rowsTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'altContactsOverviewRows' );
			
			return Theme::i()->getTemplate( 'customers', 'nexus' )->accountInformationTablePreview( $this->member, $alternativeContacts, Member::loggedIn()->language()->addToStack( 'num_alternate_contacts', FALSE, array( 'pluralize' => array( $altContactCount ) ) ), 'alts' );
		}
	}

	/**
	 * Get output
	 *
	 * @param string $tab
	 * @return    mixed
	 */
	public function tabOutput( string $tab ): mixed
	{
		$method = "_{$tab}";
		return $this->$method();
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		if ( array_key_exists( Request::i()->type, $this->tabs() ) )
		{
			$method = "_" . Request::i()->type;
			return $this->$method( TRUE );
		}
		return parent::edit();
	}
	
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$tabs = $this->tabs();
		if ( !count( $tabs ) )
		{
			return '';
		} 
		$tabKeys = array_keys( $tabs );
		$activeTabKey = ( isset( Request::i()->block['nexus_AccountInformation'] ) and array_key_exists( Request::i()->block['nexus_AccountInformation'], $tabs ) ) ? Request::i()->block['nexus_AccountInformation'] : array_shift( $tabKeys );
		
		$activeSubscription = FALSE;
		if ( Settings::i()->nexus_subs_enabled )
		{
			$activeSubscription = Subscription::loadByMember( $this->member, TRUE );
		}
		
		return (string) Theme::i()->getTemplate( 'customers', 'nexus' )->accountInformation( $this->member, $tabs, $activeTabKey, $this->tabOutput( $activeTabKey ), $activeSubscription );
	}
}