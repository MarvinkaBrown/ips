<?php
/**
 * @brief		Manual Pay Out Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		7 Apr 2014
 */

namespace IPS\nexus\Gateway\Manual;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\nexus\Payout as NexusPayout;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Manual Pay Out Gateway
 */
class Payout extends NexusPayout
{
	/**
	 * @brief	Requires manual approval?
	 */
	public static bool $requiresApproval = TRUE;

	/**
	 * Extra HTML to display when the admin view the Payout in the ACP
	 *
	 * @return string
	 */
	public function acpHtml() : string
	{
		return Theme::i()->getTemplate( 'payouts', 'nexus' )->Manual( $this );
	}
	
	/**
	 * ACP Settings
	 *
	 * @return	array
	 */
	public static function settings() : array
	{
		$settings = json_decode( Settings::i()->nexus_payout, TRUE );

		$return = array();
		$return[] = new Text( 'manual_name', isset( $settings['Manual'] ) ? $settings['Manual']['name'] : '' , NULL, array(), function( $val ) {
			if ( !$val AND isset( Request::i()->nexus_payout['Manual'] ) AND  Request::i()->nexus_payout['Manual']  == 1 )
			{
				throw new DomainException( 'form_required' );
			}
		});
		$return[] = new Text( 'manual_title', isset( $settings['Manual'] ) ? $settings['Manual']['title'] : '', NULL, array(), function( $val ) {
			if ( !$val AND isset( Request::i()->nexus_payout['Manual'] ) AND  Request::i()->nexus_payout['Manual']  == 1 )
			{
				throw new DomainException( 'form_required' );
			}
		});

		return $return;
	}
	
	/**
	 * Payout Form
	 *
	 * @return	array
	 */
	public static function form() : array
	{		
		$settings = json_decode( Settings::i()->nexus_payout, TRUE );
		
		$field = new TextArea( 'manual_details', NULL, TRUE, array() );
		$field->label = $settings['Manual']['title'];
		return array( $field );
	}
	
	/**
	 * Get data and validate
	 *
	 * @param	array	$values	Values from form
	 * @return	mixed
	 * @throws	DomainException
	 */
	public function getData( array $values ) : mixed
	{
		return $values['manual_details'];
	}

	/**
	 * Process the payout
	 * Return the new status for this payout record
	 *
	 * @return	string
	 * @throws	Exception
	 */
	public function process() : string
	{
		return static::STATUS_COMPLETE;
	}
}