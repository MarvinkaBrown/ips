<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		08 Aug 2017
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DOMElement;
use Exception;
use IPS\Application;
use IPS\convert\App;
use IPS\Extensions\QueueAbstract;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use IPS\Text\DOMParser;
use IPS\Xml\DOMDocument;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class InvisionCommunityRebuildContent extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_SLOW;

	/**
	 * @var App|null
	 */
	protected ?App $app = null;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];

		Log::debug( "Getting preQueueData for " . $classname, 'ICrebuildPosts' );

		try
		{
			$data['count']		= $classname::db()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();
			$data['realCount']	= $classname::db()->select( 'COUNT(*)', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();

			/* We're going to use the < operator, so we need to ensure the most recent item is rebuilt */
		    $data['runPid'] = $data['count'] + 1;
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}

		Log::debug( "PreQueue count for " . $classname . " is " . $data['count'], 'ICrebuildPosts' );

		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['indexed']	= 0;

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int					New offset or NULL if complete
	 * @throws    QueueOutOfRangeException    Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$classname = $data['class'];
		$exploded = explode( '\\', $classname );
		if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new QueueOutOfRangeException;
		}

		/* Make sure there's even content to parse */
		if( !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new QueueOutOfRangeException;
		}

		/* Intentionally no try/catch as it means app doesn't exist */
		try
		{
			$this->app = App::load( $data['app'] );

			/* This extension is ONLY for InvisionCommunity conversions */
			if( $this->app->app_key != 'invisioncommunity' )
			{
				throw new QueueOutOfRangeException;
			}
		}
		catch( OutOfRangeException $e )
		{
			throw new QueueOutOfRangeException;
		}

		$softwareClass = $this->app->getSource( FALSE, FALSE );

		Log::debug( "Running " . $classname . ", with an offset of " . $offset, 'ICrebuildPosts' );

		$where	  = ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? ( is_array( $classname::commentWhere() ) ? array( $classname::commentWhere() ) : array() ) : array();
		$select   = $classname::db()->select( '*', $classname::$databaseTable, array_merge( $where, array( array( $classname::$databasePrefix . $classname::$databaseColumnId . ' < ?',  $data['runPid'] ) ) ), $classname::$databasePrefix . $classname::$databaseColumnId . ' DESC', array( 0, $this->rebuild ) );
		$iterator = new ActiveRecordIterator( $select, $classname );
		$last     = NULL;

		foreach( $iterator as $item )
		{
			$idColumn = $classname::$databaseColumnId;

			/* Is this converted content? */
			try
			{
				/* Just checking, we don't actually need anything */
				$this->app->checkLink( $item->$idColumn, $data['link'] );
			}
			catch( OutOfRangeException $e )
			{
				$last = $item->$idColumn;
				$data['indexed']++;
				continue;
			}

			$contentColumn	= $classname::$databaseColumnMap['content'];

			$source = new DOMDocument( '1.0', 'UTF-8' );
			$source->loadHTML( DOMDocument::wrapHtml( $item->$contentColumn ) );

			if( mb_stristr( $item->$contentColumn, 'data-mentionid' ) )
			{
				/* Get mentions */
				$mentions = $source->getElementsByTagName( 'a' );

				foreach( $mentions as $element )
				{
					if( $element->hasAttribute( 'data-mentionid' ) )
					{
						$this->updateMention( $element );
					}
				}
			}

			/* embeds */
			if( mb_stristr( $item->$contentColumn, 'data-embedcontent' ) )
			{
				/* Get mentions */
				$embeds = $source->getElementsByTagName( 'iframe' );

				foreach( $embeds as $element )
				{
					if( $element->hasAttribute( 'data-embedcontent' ) )
					{
						$this->updateEmbed( $element );
					}
				}
			}

			/* quotes */
			if( mb_stristr( $item->$contentColumn, 'data-ipsquote' ) )
			{
				/* Get mentions */
				$quotes = $source->getElementsByTagName( 'blockquote' );

				foreach( $quotes as $element )
				{
					if( $element->hasAttribute( 'data-ipsquote' ) )
					{
						$this->updateQuote( $element );
					}
				}
			}

			/* Get DOMDocument output */
			$content = DOMParser::getDocumentBodyContents( $source );

			/* Replace file storage tags */
			$content = preg_replace( '/&lt;fileStore\.([\d\w\_]+?)&gt;/i', '<fileStore.$1>', $content );

			/* DOMDocument::saveHTML will encode the base_url brackets, so we need to make sure it's in the expected format. */
			$item->$contentColumn = str_replace( '&lt;___base_url___&gt;', '<___base_url___>', $content );

			$item->save();

			$last = $item->$idColumn;

			$data['indexed']++;
		}

		/* Store the runPid for the next iteration of this Queue task. This allows the progress bar to show correctly. */
		$data['runPid'] = $last;

		if( $last === NULL )
		{
			throw new QueueOutOfRangeException;
		}

		/* Return the number rebuilt so far, so that the rebuild progress bar text makes sense */
		return $data['indexed'];
	}

	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		$class = $data['class'];
		$exploded = explode( '\\', $class );
		if ( !class_exists( $class ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}

		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_stuff', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}

	/**
	 * Update mentions with new display name, ID and URL
	 *
	 * @param	DOMElement		$element	DOM element
	 * @return	void
	 */
	public function updateMention( DOMElement $element ) : void
	{
		try
		{
			/* Get new member ID */
			$newMemberId = $this->app->getLink( $element->getAttribute( 'data-mentionid' ), 'core_members' );

			/* Get new member */
			$member = Member::load( $newMemberId );

			$element->setAttribute( 'data-mentionid', $newMemberId );
			$element->setAttribute( 'href', str_replace( Settings::i()->base_url, '<___base_url___>/', $member->url() ) );
			$element->setAttribute( 'data-ipshover-target', str_replace( Settings::i()->base_url, '<___base_url___>/', $member->url()->setQueryString( 'do', 'hovercard' ) ) );
			$element->nodeValue = '@' . $member->name;
		}
		catch( Exception $e ) {}
	}

	/**
	 * @brief	Mapping of content types to converter lookups - Add more for other apps when we support them
	 */
	protected array $embedLocations = array( 'forums' => array( 'content' => 'forums_topics', 'comment' => 'forums_posts' ) );

	/**
	 * Update local embeds for new names, IDs
	 *
	 * @param	DOMElement		$element	DOM element
	 * @return	void
	 */
	public function updateEmbed( DOMElement $element ) : void
	{
		try
		{
			$url = Url::createFromString( str_replace( '<___base_url___>', rtrim( Settings::i()->base_url, '/' ), $element->getAttribute( 'src' ) ) );

			if( !in_array( $url->hiddenQueryString['app'], array_keys( $this->embedLocations ) ) )
			{
				return;
			}

			$url->hiddenQueryString['id'] = $this->app->getLink( $url->hiddenQueryString['id'], $this->embedLocations[ $url->hiddenQueryString['app'] ]['content'] );

			try
			{
				if( isset( $url->queryString['comment'] ) )
				{
					$url = $url->setQueryString( 'comment', $this->app->getLink( $url->queryString['comment'], $this->embedLocations[ $url->hiddenQueryString['app'] ]['comment'] ) );
				}
			}
			catch( OutOfRangeException $e )
			{
				$url = $url->stripQueryString( 'comment' );
			}

			try
			{
				if( isset( $url->queryString['embedComment'] ) )
				{
					$url = $url->setQueryString( 'embedComment', $this->app->getLink( $url->queryString['embedComment'], $this->embedLocations[ $url->hiddenQueryString['app'] ]['comment'] ) );
				}
			}
			catch( OutOfRangeException $e )
			{
				$url = $url->stripQueryString( 'embedComment' );
			}

			$element->setAttribute( 'src', str_replace( Settings::i()->base_url, '<___base_url___>/', (string) $url->correctFriendlyUrl() ) );
		}
		catch( Exception $e ) {}
	}

	/**
	 * Update quotes for new names, IDs
	 *
	 * @param	DOMElement		$element	DOM element
	 * @return	void
	 */
	public function updateQuote( DOMElement $element ) : void
	{
		try
		{
			/* Lookup the memnbers new ID */
			$newMemberId = $this->app->getLink( $element->getAttribute( 'data-ipsquote-userid' ), 'core_members' );

			/* Get new member */
			$member = Member::load( $newMemberId );

			/* Get old username */
			$oldUsername = $element->getAttribute( 'data-ipsquote-username' );
			$element->setAttribute( 'data-ipsquote-username', $member->name );
			$element->setAttribute( 'data-ipsquote-userid', $member->member_id );

			/* Is this forums? */
			if( $element->hasAttribute( 'data-ipsquote-contentapp' ) AND $element->hasAttribute( 'data-ipsquote-contentapp' ) == 'forums' )
			{
				$element->setAttribute( 'data-ipsquote-contentcommentid', $this->app->getLink( $element->getAttribute( 'data-ipsquote-contentcommentid' ), 'forums_posts' ) );
				$element->setAttribute( 'data-ipsquote-contentid', $this->app->getLink( $element->getAttribute( 'data-ipsquote-contentid' ), 'forums_topics' ) );
			}

			/* find the citation to update the username */
			foreach ( $element->childNodes as $child )
			{
				if ( $child instanceof DOMElement and $child->getAttribute('class') == 'ipsQuote_citation' )
				{
					$child->nodeValue = str_replace( $oldUsername, $member->name, $child->nodeValue );
				}
			}
		}
		catch( Exception $e ) {}
	}
}