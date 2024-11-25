<?php
/**
 * @brief		Interface for Shareable Comment Models
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Jul 2013
 */

namespace IPS\Content;

use IPS\Member;
use IPS\core\ShareLinks\Service;
use IPS\Text\Parser;

use function count;
use function defined;
use function header;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Interface for Shareable Content/Comment Models
 *
 * @note	Content classes will gain special functionality by implementing this interface
 */
trait Shareable
{
	/**
	 * @brief	[Content\Item]	Sharelink HTML
	 */
	protected array $sharelinks = array();
	
	/**
	 * Can share
	 *
	 * @return bool
	 */
	public function canShare(): bool
	{
		if( !$this->actionEnabled( 'share' ) )
		{
			return false;
		}

		if ( !$this->canView( Member::load( 0 ) ) )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	 
	/**
	 * Return sharelinks for this item
	 *
	 * @return array
	 */
	public function sharelinks(): array
	{
		if( !count( $this->sharelinks ) )
		{
			if ( $this->canShare() )
			{
				$shareUrl = $this->url();
				if( $this instanceof Item )
				{				
					$this->sharelinks = Service::getAllServices( $shareUrl, $this->mapped('title'), NULL, $this );
				}
				else
				{
					$this->sharelinks = Service::getAllServices( $shareUrl, $this->item()->mapped('title'), NULL, $this->item() );
				}
			}
			else
			{
				$this->sharelinks = array();
			}
		}
		
		return $this->sharelinks;
	}
	
	/**
	 * Web Share Data
	 *
	 * @return	array|NULL
	 */
	public function webShareData(): array|NULL
	{
		if( $this instanceof Item )
		{
			return array(
				'title'		=> Parser::truncate( $this->mapped('title'), TRUE ),
				'text'		=> Parser::truncate( $this->mapped('title'), TRUE ),
				'url'		=> (string) $this->url()
			);
		}
		else
		{
			return array(
				'title'		=> Parser::truncate( $this->item()->mapped('title'), TRUE ),
				'text'		=> $this->truncated( TRUE, NULL ),
				'url'		=> (string) $this->url()
			);
		}
	}
}