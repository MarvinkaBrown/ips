<?php
/**
 * @brief		reportedContentTypes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		14 Dec 2017
 */

namespace IPS\core\modules\admin\moderation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reportedContentTypes
 */
class reportedContentTypes extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\core\Reports\Types';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=moderation&controller=reportedContent" ), 'menu__core_moderation_report' );
		Output::i()->breadcrumb[] = array( NULL, 'reportedContent_types' );

		if ( ! Request::i()->do )
		{
			Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'moderation_reporttypes_desc' );
		}

		Dispatcher::i()->checkAcpPermission( 'reportedContentTypes_manage' );
		parent::execute();
	}
}