<?php
/**
 * @brief		Coupons
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		05 May 2014
 */

namespace IPS\nexus\modules\admin\store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Coupon;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * coupons
 */
class coupons extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\nexus\Coupon';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'coupons_manage' );
		parent::execute();
	}
	
	/**
	 * View Uses
	 *
	 * @return	void
	 */
	public function viewUses() : void
	{
		try
		{
			$coupon = Coupon::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X234/1', 404, '' );
		}
		
		$data = array();
		$usedBy = $coupon->used_by ? json_decode( $coupon->used_by, TRUE ) : null;
		if ( $usedBy )
		{
			foreach ( $usedBy as $member => $uses )
			{
				$data[] = array( 'coupon_customer' => $member, 'coupon_uses' => $uses );
			}
		}

				
		$table = new Custom( $data, Url::internal("app=nexus&module=store&controller=coupons&do=viewUses&id={$coupon->id}") );
		$table->parsers = array(
			'coupon_customer'	=> function ( $val )
			{
				return is_numeric( $val ) ? Theme::i()->getTemplate('global')->userLink( Member::load( $val ) ) : $val;
			}
		);
		$table->sortBy = 'coupon_uses';
		$table->noSort = array( 'coupon_customer' );
		
		Output::i()->title = $coupon->code;
		Output::i()->output = (string) $table;
	}
}