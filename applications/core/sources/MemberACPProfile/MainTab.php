<?php
/**
 * @brief		ACP Member Profile: Main Tab
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\MemberACPProfile;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Member;
use IPS\Theme;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Main Tab
 */
abstract class MainTab
{
	/**
	 * @brief	Member
	 */
	protected ?Member $member = null;
	
	/**
	 * Constructor
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		$this->member = $member;
	}
	
	/**
	 * Can View this Tab
	 *
	 * @return	bool
	 */
	public function canView() : bool
	{
		/* Extensions can override */
		return TRUE;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public static function title() : string
	{
		$class = get_called_class();
		$exploded = explode( '\\', $class );
		return Member::loggedIn()->language()->addToStack( 'memberACPProfileTitle_' . $exploded[1] . '_' . $exploded[5] );
	}
	
	/**
	 * Get left-column blocks
	 *
	 * @return	array
	 */
	public function leftColumnBlocks() : array
	{
		return array();
	}
	
	/**
	 * Get main-column blocks
	 *
	 * @return	array
	 */
	public function mainColumnBlocks() : array
	{
		return array();
	}
		
	/**
	 * Get Output
	 *
	 * @return	string
	 */
	public function output() : string
	{
		$seenBlocks = array();
		$leftColumnBlocks = array();
		foreach ( $this->leftColumnBlocks() as $class )
		{
			$seenBlocks[] = $class;
			$leftColumnBlocks[] = new $class( $this->member );
		}
		
		$mainColumnBlocks = array();
		foreach ( $this->mainColumnBlocks() as $class )
		{
			$seenBlocks[] = $class;
			$mainColumnBlocks[] = new $class( $this->member );
		}

		$class = get_called_class();
		$exploded = explode( '\\', $class );
		$thisTab = $exploded[1] . '_' . $exploded[5];

		/* Check other applications for possible blocks that should show on this tab */
		foreach( Application::allExtensions( 'core', 'MemberACPProfileBlocks', TRUE, 'core', 'Main', FALSE ) AS $key => $ext )
		{
			/* If we are already displaying this class, skip it */
			if( in_array( $ext, $seenBlocks ) )
			{
				continue;
			}

			if( isset( $ext::$displayTab ) AND $ext::$displayTab == $thisTab )
			{
				$class = new $ext( $this->member );
				if( isset( $ext::$displayColumn ) )
				{
					switch( $ext::$displayColumn )
					{
						case 'left':
							$leftColumnBlocks[] = $class;
							break;
						case 'main':
							$mainColumnBlocks[] = $class;
							break;
					}
				}
			}
		}
				
		return Theme::i()->getTemplate('memberprofile')->tabTemplate( $leftColumnBlocks, $mainColumnBlocks );
	}
}