<?php
/**
 * @brief		RSS Import extension: RssImport
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		09 Oct 2019
 */

namespace IPS\blog\extensions\core\RssImport;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Content\Search\Index;
use IPS\core\Rss\Import;
use IPS\Extensions\RssImportAbstract;
use IPS\Helpers\Form;
use IPS\Member;
use IPS\Node\Model;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	RSS Import extension: RssImport
 */
class RssImport extends RssImportAbstract
{
	public string $fileStorage = 'blog_Blogs';

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->classes = array( 'IPS\\blog\\Entry' );
	}

	/**
	 * Return available options for a Form\Select
	 *
	 * @return array
	 */
	public function availableOptions(): array
	{
		/* We don't want to set up Blog feeds in the ACP */
		return array();
	}

	/**
	 * Show in the Admin CP?
	 *
	 * @return boolean
	 */
	public function showInAdminCp(): bool
	{
		return false;
	}

	/**
	 * Node selector options
	 *
	 * @param Import|null $rss	Existing RSS object if editing|null if not
	 * @return array
	 */
	public function nodeSelectorOptions( ?Import $rss ): array
	{
		return array( 'class' => 'IPS\blog\Blog', 'permissionCheck' => 'view' );
	}

	/**
	 * @param Import 	$rss 		RSS object
	 * @param array $article 	RSS feed article importing
	 * @param Model 		$container  Container object
	 * @param string $content	Post content with read more link if set
	 * @return Content
	 */
	public function create( Import $rss, array $article, Model $container, string $content ): Content
	{
		$settings = $rss->settings;
		$class = $rss->_class;
		$member = Member::load( $rss->member );
		$entry = $class::createItem( $member, NULL, $article['date'], $container );
		$entry->name = $article['title'];
		$entry->content = $content;
		$entry->status = 'published';
		$entry->save();

		/* Add to search index */
		Index::i()->index( $entry );

		/* Send notifications */
		$entry->sendNotifications();

		$entry->setTags( $settings['tags'], $member );

		return $entry;
	}

	/**
	 * Addition Form elements
	 *
	 * @param Form $form	The form
	 * @param Import|null $rss	Existing RSS object if editing|null if not
	 * @return	void
	 */
	public function form( Form $form, ?Import $rss=NULL ): void
	{
		/* Blogs has it's own front end controller */
	}

	/**
	 * Process additional fields unique to this extension
	 *
	 * @param array $values	Values from form
	 * @param Import $rss	Existing RSS object
	 * @return	array
	 */
	public function saveForm( array &$values, Import $rss ): array
	{
		/* Blogs has it's own front end controller */
		return array( $values );
	}
}