<?php
/**
 * @brief		Promote Items
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Feb 2017
 */

namespace IPS\core\modules\front\feature;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Feature\PublicTable;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Curated "Our Picks" listing. (Very technical description there)
 */
class featured extends Controller
{

	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'our_picks', 'odkUpdate' => 'true']
	);

	/**
	 * List promoted internally promoted items
	 *
	 * @return void
	 */
	protected function manage() : void
	{
		/* Create the table */
		$table = new PublicTable( Url::internal( 'app=core&module=feature&controller=featured', 'front', 'featured_show' ) );
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/promote.css' ) );

		Output::i()->breadcrumb['module'] = array( Url::internal( "app=core&module=feature&controller=featured", 'front', 'featured_show' ), Member::loggedIn()->language()->addToStack('promoted_items_title') );

		Output::i()->title = Member::loggedIn()->language()->addToStack('promote_table_header');
		Output::i()->output = $table;
	}
}