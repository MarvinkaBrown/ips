<?php
/**
 * @brief		Dashboard extension: Admin notes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Jul 2013
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Extensions\DashboardAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\TextArea;
use IPS\Http\Url;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Admin notes
 */
class AdminNotes extends DashboardAbstract
{
	/**
	 * Can the current user view this dashboard item?
	 *
	 * @return	bool
	 */
	public function canView(): bool
	{
		return TRUE;
	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		$form	= new Form( 'form', 'save', Url::internal( "app=core&module=overview&controller=dashboard&do=getBlock&appKey=core&blockKey=core_AdminNotes" )->csrf() );
		$form->add( new TextArea( 'admin_notes', ( isset( Settings::i()->acp_notes ) ) ? htmlspecialchars( Settings::i()->acp_notes, ENT_DISALLOWED, 'UTF-8', FALSE ) : '' ) );

		if( $values = $form->values() )
		{
			Settings::i()->changeValues( array( 'acp_notes' => $values['admin_notes'], 'acp_notes_updated' => time() ) );

			if( Request::i()->isAjax() )
			{
				return (string) DateTime::ts( intval( Settings::i()->acp_notes_updated ) );
			}
		}

		return $form->customTemplate( array( Theme::i()->getTemplate( 'dashboard' ), 'adminnotes' ), ( isset( Settings::i()->acp_notes_updated ) and Settings::i()->acp_notes_updated ) ? (string) DateTime::ts( intval( Settings::i()->acp_notes_updated ) ) : '' );
	}
}