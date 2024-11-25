<?php
/**
 * @brief		Support Clubs in sitemaps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		01 Nov 2022
 */

namespace IPS\core\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\SitemapAbstract;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Member\Club;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Sitemap;
use UnderflowException;
use function count;
use function defined;
use function intval;
use function strpos;
use const IPS\SITEMAP_MAX_PER_FILE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Clubs in sitemaps
 */
class Clubs extends SitemapAbstract
{
	/**
	 * @brief	Recommended Settings
	 */
	public array $recommendedSettings = array(
        'sitemap_club_include' => true,
        'sitemap_club_count' => -1,
        'sitemap_club_priority' => 1
    );

	/**
	 * Add settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings(): array
	{
        $settings = Settings::i()->sitemap_club_settings ? json_decode( Settings::i()->sitemap_club_settings, TRUE ) : array();
        $return = array();

        $countToInclude = $settings["sitemap_club_count"] ?? $this->recommendedSettings["sitemap_club_count"];
        $return["sitemap_club_include"] = new YesNo( "sitemap_club_include", $countToInclude != 0, FALSE, array( 'togglesOn' => array( "sitemap_club_count", "sitemap_club_priority" ) ), NULL, NULL, NULL, "sitemap_club_include" );
        $return["sitemap_club_include"]->label = Member::loggedIn()->language()->addToStack( 'sitemap_include_generic_desc' );
        $return["sitemap_club_count"]	 = new Number( "sitemap_club_count", $countToInclude, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, "sitemap_club_count" );
        $return["sitemap_club_count"]->label	= Member::loggedIn()->language()->addToStack( 'sitemap_number_generic' );
        $return["sitemap_club_priority"] = new Select( "sitemap_club_priority", $settings["sitemap_club_priority"] ?? $this->recommendedSettings["sitemap_club_priority"], FALSE, array( 'options' => Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, "sitemap_club_priority" );
        $return["sitemap_club_priority"]->label	= Member::loggedIn()->language()->addToStack( 'sitemap_priority_generic' );
        
	    return $return;
	}

	/**
	 * Save settings for ACP configuration
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	public function saveSettings( array $values ) : void
	{
		if ( $values['sitemap_configuration_info'] )
		{
			// Store default  values for any settings you define
			Settings::i()->changeValues( array( 'sitemap_club_settings' => json_encode( array() ) ) );
		}
		else
		{
			// Store the actual submitted value for any settings you define, from the $values array
			$settings = Settings::i()->sitemap_club_settings ? json_decode( Settings::i()->sitemap_club_settings, TRUE ) : array();
			$settings['sitemap_club_count'] = $values['sitemap_club_include'] ? $values['sitemap_club_count'] : 0;
			$settings['sitemap_club_priority'] = $values['sitemap_club_priority'];
            Settings::i()->changeValues( array( 'sitemap_club_settings' => json_encode( $settings ) ) );
		}
	}

	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames(): array
	{
        $settings = Settings::i()->sitemap_club_settings ? json_decode( Settings::i()->sitemap_club_settings, TRUE ) : array();
        $limit = $settings['sitemap_club_count'] ?? -1;
        if( $limit == 0 )
        {
            return array();
        }

		$visibleClubs = iterator_to_array(
			Db::i()->select( 'id', 'core_clubs', array( '`type` != ? and approved=?', Club::TYPE_PRIVATE, 1 ) )
		);
		$count = count( $visibleClubs );
        $files  = array();
        $count = ceil( $count / SITEMAP_MAX_PER_FILE );

        for( $i=1; $i <= $count; $i++ )
        {
            $files[] = 'sitemap_clubs_' . $i;
        }

		/* Skip if we have no visible clubs */
		foreach( $visibleClubs as $clubId )
		{
			$pagesCount = (int)Db::i()->select( 'count(page_id)', 'core_club_pages', array( 'page_club=? and page_meta_index=?', $clubId, 1 ) )->first();
			$pagesCount = ceil( $pagesCount / SITEMAP_MAX_PER_FILE );
			for( $i=1; $i <= $pagesCount; $i++ )
			{
				$files[] =  $clubId . '_sitemap_clubs_pages_' . $i;
			}
		}

        return $files;
	}

	/**
	 * Generate the sitemap
	 *
	 * @param	string			$filename	The sitemap file to build (should be one returned from getFilenames())
	 * @param	Sitemap	$sitemap	Sitemap object reference
	 * @return	int|null
	 */
	public function generateSitemap( string $filename, Sitemap $sitemap ): ?int
	{
        $entries	= array();
        $lastId		= 0;
        $settings = Settings::i()->sitemap_club_settings ? json_decode( Settings::i()->sitemap_club_settings, TRUE ) : array();

        $exploded	= explode( '_', $filename );
        $block		= (int) array_pop( $exploded );
        $totalLimit = $settings["sitemap_club_count"] ?? -1;
        $offset		= ( $block - 1 ) * SITEMAP_MAX_PER_FILE;
        $limit		= SITEMAP_MAX_PER_FILE;

        if ( ! $totalLimit )
        {
            return null;
        }

        if ( $totalLimit > -1 and ( $offset + $limit ) > $totalLimit )
        {
            $limit = $totalLimit - $offset;
        }

        /* Create limit clause */
        $limitClause	= array( $offset, $limit );

		if( strpos( $filename, '_pages_' ) !== false )
		{
			$tmp = explode( '_', $filename );
			$clubId = intval( array_shift( $tmp ) );
			$where = array(
				array( 'page_club=?', $clubId ),
				array( 'page_meta_index=?', 1 )
			);

			try
			{
				$lastId = Db::i()->select( 'last_id', 'core_sitemap', array( array( 'sitemap=?', implode( '_', $exploded ) . '_' . ( $block - 1 ) ) ) )->first();
				if( $lastId > 0 )
				{
					$where[] = array( 'id > ?', $lastId );
					$limitClause	= $limit;
				}
			}
			catch( UnderflowException $e ){}

			foreach( new ActiveRecordIterator(
						 Db::i()->select( '*', 'core_club_pages', $where, 'page_id', $limitClause ),
						 'IPS\Member\Club\Page'
					 ) as $page )
			{
				if( !$page->canView( new Member ) )
				{
					continue;
				}

				$data = array(
					'url' => (string)$page->url()
				);

				$priority = $settings['sitemap_club_priority'] ?? 1;
				if ( $priority !== -1 )
				{
					$data['priority'] = $priority;
				}

				$entries[] = $data;

				$lastId = $page->id;
			}
		}
		else
		{
			$where = array(
				array( '`type` != ?', Club::TYPE_PRIVATE ),
				array( 'approved=?', 1 )
			);

			/* Try to fetch the highest ID built in the last sitemap, if it exists */
			try
			{
				$lastId = Db::i()->select( 'last_id', 'core_sitemap', array( array( 'sitemap=?', implode( '_', $exploded ) . '_' . ( $block - 1 ) ) ) )->first();

				if( $lastId > 0 )
				{
					$where[] = array( 'id > ?', $lastId );
					$limitClause	= $limit;
				}
			}
			catch( UnderflowException $e ){}

			foreach( new ActiveRecordIterator(
						 Db::i()->select( '*', 'core_clubs', $where, 'id', $limitClause ),
						 'IPS\Member\Club'
					 ) as $club )
			{
				if( !$club->canView( new Member ) )
				{
					continue;
				}

				$data = array(
					'url' => $club->url(),
					'lastmod' => $club->last_activity
				);

				$priority = $settings['sitemap_club_priority'] ?? 1;
				if ( $priority !== -1 )
				{
					$data['priority'] = $priority;
				}

				$entries[] = $data;

				$lastId = $club->id;
			}
		}

        $sitemap->buildSitemapFile( $filename, $entries, $lastId );
        return $lastId;
	}

}