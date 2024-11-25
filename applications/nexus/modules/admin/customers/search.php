<?php
/**
 * @brief		Customer Search
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		12 May 2014
 */

namespace IPS\nexus\modules\admin\customers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer\CustomField;
use IPS\Output;
use IPS\Theme;
use function defined;
use const IPS\Helpers\Table\SEARCH_BOOL;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
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
 * Customer Search
 */
class search extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customers_view' );
		parent::execute();
	}

	/**
	 * View Customers
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$table = new Db( 'core_members', Url::internal('app=nexus&module=customers&controller=search') );
		$table->langPrefix = 'customerTable_';
		$table->joins = array(
			array(
				'select'	=> 'nexus_customers.cm_first_name, nexus_customers.cm_last_name',
				'from'		=> 'nexus_customers',
				'where'		=> 'core_members.member_id=nexus_customers.member_id'
			),
			array(
				'select'	=> 'address',
				'from'		=> 'nexus_customer_addresses',
				'where'		=> 'nexus_customer_addresses.`member`=nexus_customers.member_id AND primary_billing=1'
			)
		);
		$table->include = array( 'photo', 'name', 'cm_last_name', 'cm_first_name', 'email', 'address' );
		$table->parsers = array(
			'photo'	=> function( $val, $row )
			{
				return Theme::i()->getTemplate('customers')->rowPhoto( Member::constructFromData( $row ) );
			},
			'address'		=> function( $val )
			{
				return $val ? GeoLocation::buildFromJson( $val ) : NULL;
			}
		);
		$table->noSort = array( 'photo', 'address' );
		$table->quickSearch = 'email';
		$table->rowClasses = array( 'address' => array( 'ipsTable_wrap' ) );
		
		$table->advancedSearch = array(
			'cm_first_name'		=> SEARCH_CONTAINS_TEXT,
			'cm_last_name'		=> SEARCH_CONTAINS_TEXT,
			'email'				=> SEARCH_CONTAINS_TEXT,
			'name'				=> SEARCH_CONTAINS_TEXT
		);
		foreach ( CustomField::roots() as $field )
		{
			switch ( $field->type )
			{
				case 'Checkbox':
				case 'YesNo':
					$table->advancedSearch[ $field->column ] = SEARCH_BOOL;
					break;
					
				case 'CheckboxSet':
				case 'Select':
				case 'Radio':
					$table->advancedSearch[ $field->column ] = array( SEARCH_SELECT, array( 'options' => json_decode( $field->extra ), 'multiple' => $field->multiple ) );
					break;
					
				case 'Date':
					$table->advancedSearch[ $field->column ] = SEARCH_DATE_RANGE;
					break;
					
				case 'Member':
					$table->advancedSearch[ $field->column ] = array( SEARCH_MEMBER, array( 'multiple' => $field->multiple ) );
					break;
					
				case 'Number':
					$table->advancedSearch[ $field->column ] = SEARCH_NUMERIC;
					break;
				
				default:
					$table->advancedSearch[ $field->column ] = SEARCH_CONTAINS_TEXT;
					break;
			}
			
			Member::loggedIn()->language()->words[ 'customerTable_field_' . $field->id ] = $field->_title;
			
			$table->parsers[ $field->column ] = array( $field, 'displayValue' );
		}
		
		$table->rowButtons = function( $row )
		{
			return array(
				'view'	=> array(
					'icon'	=> 'search',
					'title'	=> 'view',
					'link'	=> Url::internal("app=core&module=members&controller=members&do=view&tab=nexus_Main&id={$row['member_id']}"),
				),
			);
		};
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__nexus_customers');
		Output::i()->output = (string) $table;
	}
}