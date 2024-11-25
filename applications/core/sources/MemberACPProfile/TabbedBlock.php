<?php
/**
 * @brief		ACP Member Profile: Tabbed Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Nov 2017
 */

namespace IPS\core\MemberACPProfile;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Tabbed Block
 */
abstract class TabbedBlock extends Block
{
	/**
	 * Get Output
	 *
	 * @return	string
	 */
	public function output() : string
	{
		$tabs = $this->tabs();
		if ( !count( $tabs ) )
		{
			return '';
		} 
		$tabKeys = array_keys( $tabs );
		
		$exploded = explode( '\\', get_called_class() );
		$tabParam = $exploded[1] . '_' . $exploded[5];
		$activeTabKey = ( isset( Request::i()->block[$tabParam] ) and array_key_exists( Request::i()->block[$tabParam], $tabs ) ) ? Request::i()->block[$tabParam] : array_shift( $tabKeys );
		
		return Theme::i()->getTemplate('memberprofile')->tabbedBlock( $this->member, $tabParam, $this->blockTitle(), $tabs, $activeTabKey, $this->tabOutput( $activeTabKey ), $this->showEditLink() ? $this->editLink() : NULL );
	}
	
	/**
	 * Show Edit Link?
	 *
	 * @return	bool
	 */
	protected function showEditLink() : bool
	{
		return false;
	}
	
	/**
	 * Edit Link
	 *
	 * @return	Url
	 */
	protected function editLink() : Url
	{
		return Url::internal("app=core&module=members&controller=members&do=editBlock")->setQueryString( array(
			'block'	=> get_called_class(),
			'id'	=> $this->member->member_id
		) );
	}
	
	/**
	 * Get Block Title
	 *
	 * @return	string|null
	 */
	public function blockTitle() : ?string
	{
		return NULL;
	}
	
	/**
	 * Get Tab Names
	 *
	 * @return	array
	 */
	abstract public function tabs() : array;
	
	/**
	 * Get output
	 *
	 * @param string $tab
	 * @return    mixed
	 */
	abstract public function tabOutput(string $tab ): mixed;
}