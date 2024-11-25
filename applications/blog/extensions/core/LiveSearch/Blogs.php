<?php
/**
 * @brief		ACP Live Search Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		20 Mar 2014
 */

namespace IPS\blog\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\LiveSearchAbstract;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Live Search Extension
 */
class Blogs extends LiveSearchAbstract
{	
	/**
	 * Check we have access
	 *
	 * @return	bool
	 */
	public function hasAccess(): bool
	{
		/* Check Permissions */
		return Member::loggedIn()->hasAcpRestriction( 'blog', 'blog', 'blogs_manage' );
	}

	/**
	 * Get the search results
	 *
	 * @param string $searchTerm	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( string $searchTerm ): array
	{
		/* Init */
		$results = array();
		$searchTerm = mb_strtolower( $searchTerm );

		/* Then mix in blogs, but make sure we limit to be safe */
		if( $this->hasAccess() )
		{
			/* Perform the search */
			$blogs = Db::i()->select(
							"*",
							'blog_blogs',
							array( "blog_club_id IS NULL AND ( word_custom LIKE CONCAT( '%', ?, '%' ) AND lang_id=? )", $searchTerm, Member::loggedIn()->language()->id ),
							NULL,
							array( 0, 500 )
					)->join(
							'core_sys_lang_words',
							"word_key=CONCAT( 'blogs_blog_', blog_id )"
						);
			
			/* Format results */
			foreach ( $blogs as $blog )
			{
				$blog = Blog::constructFromData( $blog );
				
				$results[] = Theme::i()->getTemplate( 'livesearch', 'blog', 'admin' )->blog( $blog );
			}
		}
		
		return $results;
	}
	
	/**
	 * Is default for current page?
	 *
	 * @return	bool
	 */
	public function isDefault(): bool
	{
		return Dispatcher::i()->application->directory == 'blog';
	}
}