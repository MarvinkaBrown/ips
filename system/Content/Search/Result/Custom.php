<?php
/**
 * @brief		Search Result not from Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Sep 2015
*/

namespace IPS\Content\Search\Result;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Search\Result;
use IPS\DateTime;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Result not from Index
 */
class Custom extends Result
{
	/**
	 * @brief	HTML
	 */
	protected string $html;
	
	/**
	 * @brief	Image
	 */
	protected string|null $image;

	/**
	 * @brief	Data used for merging multiple extra items
	 */
	protected array $mergeData;
	
	/**
	 * Constructor
	 *
	 * @param	DateTime	$time	The time for this result
	 * @param	string			$html	HTML to display
	 * @param	string|NULL		$image	HTML for image to display
	 * @param	string|NULL		$mergeData	Data for merging
	 * @return	void
	 */
	public function __construct( DateTime $time, string $html, string|null $image = NULL, string|null $mergeData = NULL )
	{
		$this->createdDate = $time;
		$this->lastUpdatedDate = $time;
		$this->html = $html;
		$this->image = $image;
		$this->mergeData = array( $mergeData );
	}

	/**
	 * Merge in new data
	 *
	 * @param	string			$html 		New HTML to merge in as a list
	 * @param	string|NULL		$mergeData	Data for merging
	 * @return	void
	 */
	public function mergeInData( string $html, string|null $mergeData = NULL ): void
	{
		$this->html = $html;
		$this->mergeData[] = $mergeData;
	}

	/**
	 * Get data for merging
	 *
	 * @return	array
	 */
	public function getMergeData(): array
	{
		return $this->mergeData ?: array();
	}
	
	/**
	 * HTML
	 *
	 * @param	string	$view	The view to use (expanded or condensed)
	 * @return	string
	 */
	public function html( string $view = 'expanded' ): string
	{
		return Theme::i()->getTemplate( 'streams', 'core' )->extraItem( $this->createdDate, $this->image, $this->html, $view );
	}
}