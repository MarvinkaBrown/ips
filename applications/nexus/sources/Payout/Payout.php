<?php
/**
 * @brief		Payout Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\DateTime;
use IPS\Email;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Request;
use IPS\Task\Queue\OutOfRangeException;
use IPS\Theme;
use function defined;
use function get_called_class;
use function strlen;
use function substr;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NUMERIC;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Payout Model
 *
 * @property Customer $member
 */
abstract class Payout extends ActiveRecord
{	
	const STATUS_COMPLETE = 'done';
	const STATUS_PENDING  = 'pend';
	const STATUS_CANCELED = 'canc';
	const STATUS_PROCESSING = 'wait';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_payouts';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'po_';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 * @throws OutOfRangeException
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$classname = Gateway::payoutGateways()[ $data['po_gateway'] ];

		/* If the classname doesn't exist, then we can't load this */
		if( !$classname )
		{
			throw new OutOfRangeException;
		}
		
		/* Initiate an object */
		$obj = new $classname;
		$obj->_new = FALSE;
		
		/* Import data */
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = substr( $k, strlen( static::$databasePrefix ) );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();
						
		/* Return */
		return $obj;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->date = new DateTime;
		$this->status = static::STATUS_PENDING;
	}
	
	/**
	 * @brief	Requires manual approval?
	 */
	public static bool $requiresApproval = FALSE;
	
	/**
	 * Get payouts table
	 *
	 * @param	array			$where	Where clause
	 * @param	Url	$url	URL to display table on
	 * @return	Db
	 */
	public static function table( array $where, Url $url ) : Db
	{
		$table = new Db( 'nexus_payouts', $url, $where );
		$table->include = array( 'po_status', 'po_id', 'po_gateway', 'po_member', 'po_amount', 'po_date' );
		$table->parsers = array(
			'po_status'	=> function( $val )
			{
				return Theme::i()->getTemplate( 'payouts', 'nexus' )->status( $val );
			},
			'po_member'	=> function ( $val )
			{
				return Theme::i()->getTemplate('global')->userLink( Member::load( $val ) );
			},
			'po_amount'	=> function( $val, $row )
			{
				return (string) new Money( $val, $row['po_currency'] );
			},
			'po_date'	=> function( $val )
			{
				return DateTime::ts( $val );
			}
		);
		$table->filters = array(
			'postatus_pend'	=> array( 'po_status=?', 'pend' ),
		);
		$table->advancedSearch = array(
			'po_status'	=> array( SEARCH_SELECT, array( 'options' => array(
				Payout::STATUS_COMPLETE	=> 'postatus_' . Payout::STATUS_COMPLETE,
				Payout::STATUS_PENDING	=> 'postatus_' . Payout::STATUS_PENDING,
				Payout::STATUS_CANCELED	=> 'postatus_' . Payout::STATUS_CANCELED,
			), 'multiple' => TRUE ) ),
			'po_member'	=> SEARCH_MEMBER,
			'po_amount'	=> SEARCH_NUMERIC,
			'po_date'	=> SEARCH_DATE_RANGE,
		);
		$table->rowButtons = function( $row )
		{
			return array_merge( array(
				'view'	=> array(
					'icon'	=> 'search',
					'link'	=> Url::internal( "app=nexus&module=payments&controller=payouts&do=view&id={$row['po_id']}" ),
					'title'	=> 'view',
				),
			), Payout::constructFromData( $row )->buttons( 't' ) );
		};
		$table->sortBy = $table->sortBy ?: 'po_date';
		
		return $table;
	}
	
	/**
	 * Get amount
	 *
	 * @return    Money
	 */
	public function get_amount() : Money
	{		
		return new Money( $this->_data['amount'], $this->_data['currency'] );
	}
	
	/**
	 * Set amount
	 *
	 * @param Money $amount	The total
	 * @return	void
	 */
	public function set_amount(Money $amount ) : void
	{
		$this->_data['amount'] = $amount->amount;
		$this->_data['currency'] = $amount->currency;
	}
	
	/**
	 * Get member
	 *
	 * @return	Customer
	 */
	public function get_member() : Customer
	{
		return Customer::load( $this->_data['member'] );
	}
	
	/**
	 * Set member
	 *
	 * @param	Member $member
	 * @return	void
	 */
	public function set_member( Member $member ) : void
	{
		$this->_data['member'] = $member->member_id;
	}
		
	/**
	 * Get date
	 *
	 * @return	DateTime
	 */
	public function get_date() : DateTime
	{
		return DateTime::ts( $this->_data['date'] );
	}
	
	/**
	 * Set date
	 *
	 * @param	DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_date( DateTime $date ) : void
	{
		$this->_data['date'] = $date->getTimestamp();
	}
	
	/**
	 * Get completed date
	 *
	 * @return	DateTime|NULL
	 */
	public function get_completed() : DateTime|null
	{
		return $this->_data['completed'] ? DateTime::ts( $this->_data['completed'] ) : NULL;
	}
	
	/**
	 * Set completed date
	 *
	 * @param	DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_completed( DateTime $date ) : void
	{
		$this->_data['completed'] = $date->getTimestamp();
	}
	
	/**
	 * Get approving member
	 *
	 * @return	Customer|null
	 */
	public function get_processed_by() : Customer|null
	{
		return $this->_data['processed_by'] ? Customer::load( $this->_data['processed_by'] ) : NULL;
	}
	
	/**
	 * Set approving member
	 *
	 * @param	Member $member
	 * @return	void
	 */
	public function set_processed_by( Member $member ) : void
	{
		$this->_data['processed_by'] = $member->member_id;
	}
	
	/**
	 * ACP Buttons
	 *
	 * @param	string	$ref	Referer
	 * @return	array
	 */
	public function buttons( string $ref='v' ) : array
	{
		/* @var Url\Internal $url */
		$url = $this->acpUrl()->setQueryString( array( 'r' => $ref, 'filter' => Request::i()->filter ) );
		$return = array();
		
		if ( $this->status === static::STATUS_PENDING )
		{
			if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_process' ) )
			{
				$return['approve'] = array(
					'title'		=> 'approve',
					'icon'		=> 'check',
					'link'		=> $url->setQueryString( 'do', 'process' )->csrf(),
					'data'		=> array( 'confirm' => '' )
				);
			}
			if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_cancel' ) )
			{
				$return['cancel'] = array(
					'title'		=> 'cancel',
					'icon'		=> 'times',
					'link'		=> $url->setQueryString( 'do', 'cancel' )->csrf(),
					'data'		=> array(
						'confirm'			=> '',
						'confirmMessage'	=> Member::loggedIn()->language()->addToStack('payout_cancel_confirm'),
						'confirmType'		=> 'verify',
						'confirmIcon'		=> 'question',
						'confirmButtons'	=> json_encode( array(
							'yes'				=>	Member::loggedIn()->language()->addToStack('yes'),
							'no'				=>	Member::loggedIn()->language()->addToStack('no'),
							'cancel'			=>	Member::loggedIn()->language()->addToStack('cancel'),
						) )
					)
				);
			}
		}
		
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_delete' ) )
		{
			$return['delete'] = array(
				'title'		=> 'delete',
				'icon'		=> 'times-circle',
				'link'		=> $url->setQueryString( 'do', 'delete' ),
				'data'		=> array( 'delete' => '' )
			);
		}
		
		return $return;
	}
	
	/**
	 * ACP URL
	 *
	 * @return	Url
	 */
	public function acpUrl() : Url
	{
		return Url::internal( "app=nexus&module=payments&controller=payouts&do=view&id={$this->id}", 'admin' );
	}

	/**
	 * Extra HTML to display when the admin view the Payout in the ACP
	 *
	 * @return string
	 */
	public function acpHtml() : string
	{
		return "";
	}

	/**
	 * Mark the payout as completed.
	 * Moved this out of the controllers because there are times when
	 * the payout may not be processed immediately (e.g. via PayPal batch)
	 *
	 * @return void
	 */
	public function markCompleted() : void
	{
		$this->status = static::STATUS_COMPLETE;
		$this->completed = new DateTime;
		$this->save();

		/* Notify member */
		Email::buildFromTemplate( 'nexus', 'payoutComplete', array( $this ), Email::TYPE_TRANSACTIONAL )->send( $this->member );
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		if( $this->_new )
		{
			/* Find the gateway key based on the class we called */
			$class = get_called_class();
			foreach( Gateway::payoutGateways() as $k => $v )
			{
				if( $v == $class )
				{
					$this->gateway = $k;
					break;
				}
			}
		}

		parent::save();
	}

	/**
	 * ACP Settings
	 *
	 * @return	array
	 */
	abstract public static function settings() : array;

	/**
	 * Payout Form
	 *
	 * @return	array
	 */
	abstract public static function form() :array;

	/**
	 * Get data and validate
	 *
	 * @param	array	$values	Values from form
	 * @return	mixed
	 * @throws	DomainException
	 */
	abstract public function getData( array $values ) : mixed;

	/**
	 * Process the payout
	 * Return the new status for this payout record
	 *
	 * @return	string
	 * @throws	Exception
	 */
	abstract public function process() : string;
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse			int						id				ID number
	 * @apiresponse			string					status			Status: 'done' = Payment sent; 'pend' = Pending; 'canc' = Canceled
	 * @apiresponse			\IPS\nexus\Money		amount			Amount
	 * @apiresponse			string					gateway			The gateway that will process the withdrawal
	 * @apiresponse			string					data			The data provided by the member for the process. For example, if the gateway is PayPal, this will be their PayPal email address
	 * @apiresponse			datetime				requestedDate	Date withdrawal was requested
	 * @apiresponse			datetime				completedDate	Date withdrawal was completed
	 * @clientapiresponse	string					gatewayId		Any ID number provided by the gateway to identify the transaction on their end
	 * @apiresponse			\IPS\nexus\Customer		customer		Customer
	 */
	public function apiOutput( ?Member $authorizedMember = NULL ): array
	{
		return array(
			'id'				=> $this->id,
			'status'			=> $this->status,
			'amount'			=> $this->amount->apiOutput( $authorizedMember ),
			'member'			=> $this->member->apiOutput( $authorizedMember ),
			'gateway'			=> $this->gateway,
			'data'				=> $this->data,
			'requestedDate'		=> $this->date->rfc3339(),
			'completedDate'		=> $this->completed?->rfc3339(),
			'gatewayId'			=> $this->gw_id,
		);
	}
}