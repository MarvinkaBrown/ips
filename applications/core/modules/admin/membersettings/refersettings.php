<?php
/**
 * @brief		Referral Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		7 Aug 2019
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Referral Settings
 */
class refersettings extends Controller
{	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		$form = new Form;
		$form->add( new YesNo( 'ref_on', Settings::i()->ref_on, FALSE, array( 'togglesOn' => array( 'ref_member_input' ) ) ) );
		$form->add( new YesNo( 'ref_member_input', Settings::i()->ref_member_input, FALSE, array(), NULL, NULL, NULL, 'ref_member_input' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );
			
			if ( $values['ref_on'] )
			{
				Session::i()->log( 'acplog__referrals_enabled' );
			}
			else
			{
				Session::i()->log( 'acplog__referrals_disabled' );
			}

			/* update the essential cookie name list */
			unset( Store::i()->essentialCookieNames );
			Output::i()->redirect( Url::internal('app=core&module=membersettings&controller=referrals') );
		}
		
		Output::i()->output = $form;
	}
}