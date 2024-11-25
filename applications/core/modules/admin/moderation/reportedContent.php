<?php
/**
 * @brief		reportedContent
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Dec 2017
 */

namespace IPS\core\modules\admin\moderation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reportedContent
 */
class reportedContent extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\core\Reports\Rules';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=moderation&controller=report" ), 'menu__core_moderation_report' );
		Output::i()->breadcrumb[] = array( NULL, 'manage_automatic_rules' );

		Output::i()->output = Theme::i()->getTemplate('forms')->blurb( 'automaticmoderation_blurb' );
		
		if ( ! Settings::i()->automoderation_enabled )
		{
			Output::i()->output .= Theme::i()->getTemplate('forms')->blurb( 'automaticmoderation_disabled_blurb' );
		}
		
		Dispatcher::i()->checkAcpPermission( 'reportedContent_manage' );
		parent::execute();
	}
}