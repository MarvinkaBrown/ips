<?php
/**
 * @brief		Content Discovery Stream Subscription
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Jul 2021
 */

namespace IPS\core\Stream;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content\Comment;
use IPS\Content\Search\Query;
use IPS\Content\Search\SearchContent;
use IPS\core\Stream;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function array_slice;
use function count;
use function defined;
use function get_called_class;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Discovery Stream Subscription
 */
class Subscription extends ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_stream_subscriptions';


	/*
	 * Get the stream object
	 *
	 * return \IPS\core\Stream
	 */
	public function get_stream() : Stream
	{
		return Stream::load( $this->stream_id) ;
	}

	/**
	 * Send Digest
	 *
	 * @param array $data
	 * @param Stream $stream
	 * @param Member $recipient
	 * @param array $subscriptionRow
	 * @param bool $showMoreLink
	 * @return    void
	 */
	public function send( array $data, Stream $stream, Member $recipient, array $subscriptionRow, bool $showMoreLink = FALSE ) : void
	{
		$email = Email::buildFromTemplate( 'core', 'activity_stream_subscription', array( $stream, $data, $recipient, $subscriptionRow, $showMoreLink ), Email::TYPE_LIST );
		$email->setUnsubscribe( 'core', 'unsubscribeStream', array( $subscriptionRow['id'], $stream->_title, md5( $recipient->email . ';' . $recipient->ip_address . ';' . $recipient->joined->getTimestamp() ) ) );

		$email->send($recipient);
	}

	/**
	 * Process a batch of digests
	 *
	 * @param string $frequency One of either "daily" or "weekly" to denote the kind of digest to send
	 * @param int $numberToSend The number of digests to send for this batch
	 * @return    bool
	 */
	public static function sendBatch( string $frequency = 'daily', int $numberToSend = 25 ) : bool
	{
		$subscriptions = iterator_to_array(
			Db::i()->select( 'core_stream_subscriptions.*, core_members.last_visit', 'core_stream_subscriptions', array('frequency = ? AND sent < ? and last_visit > ?', $frequency, ( $frequency == 'daily' ) ? time() - 86400 : time() - 604800, time() - Settings::i()->activity_stream_subscriptions_inactive_limit * 86400), 'sent ASC', array(0, $numberToSend), NULL, NULL, Db::SELECT_DISTINCT | Db::SELECT_FROM_WRITE_SERVER )
			->join( 'core_members', 'core_stream_subscriptions.member_id=core_members.member_id') );

		if ( !count( $subscriptions ) )
		{
			/* Nothing to send */
			return FALSE;
		}

		$ids = [];
		foreach ( $subscriptions as $row )
		{
			$member = Member::load( $row['member_id'] );
			if ( !$member->email or $member->isBanned() )
			{
				/* Update sent time, so the batch doesn't get stuck in a loop */
				Db::i()->update( 'core_stream_subscriptions', array( 'sent' => time() ), array( 'id=?', $row['id'] ) );
				continue;
			}

			/* Build it */
			$mail = new static;
			$data = $mail->getContentForStream( $row );
			$ids[] = $row['id'];
			if( $items = count( $data ) )
			{
				$showMore = FALSE;
				if( $items > 10 )
				{
					$data = array_slice($data, 0, 10);
					$showMore = TRUE;
				}

				$mail->send( $data, Stream::load( $row['stream_id'] ), Member::load( $row['member_id'] ), $row, $showMore );
			}
		}

		if( count( $ids ) )
		{
			Db::i()->update( 'core_stream_subscriptions', array( 'sent' => time() ), Db::i()->in( 'id', $ids ) );
		}

		return TRUE;
	}

	/**
	 * @param Stream $stream
	 * @param Member|null $member
	 * @return Subscription|null
	 */
	public static function loadByStreamAndMember( Stream $stream, Member $member = NULL ) : ?Subscription
	{
		$member = $member ?: Member::loggedIn();
		try
		{
			return static::constructFromData( Db::i()->select('*', static::$databaseTable, ['stream_id=? AND member_id=?', $stream->id, $member->member_id ] )->first() );
		}
		catch( UnderflowException $e )
		{
			return NULL;
		}
	}

	/**
	 * Fetch all the content for the stream
	 *
	 * @param array $subscriptionRow
	 * @return array
	 */
	public function getContentForStream( array $subscriptionRow ) : array
	{
		$items = [];
		$stream = Stream::load( $subscriptionRow['stream_id'] );

		$query = $stream->query( Member::load( $subscriptionRow['member_id']) );

		
		/* Override the timeframe and set it to the last sent time */
		$query->filterByCreateDate( DateTime::ts( $subscriptionRow['sent'] ) );
		/* We want only 10 items for the email, so we'll grab 11 to see if we need to show the "more link" */
		$query->setLimit(11);

		/* Get the results */
		$results = $query->search( NULL, $stream->tags ? explode( ',', $stream->tags ) : NULL, ( $stream->include_comments ? Query::TAGS_MATCH_ITEMS_ONLY + Query::TERM_OR_TAGS : Query::TERM_OR_TAGS ) );

		/* Load data we need like the authors, etc */
		$results->init();

		foreach ( $results as $result )
		{
			$data = $result->asArray();
			$itemClass = $data['indexData']['index_class'];
			$object = $itemClass::load( $data['indexData']['index_object_id']);

			if( in_array( 'IPS\Content\Comment', class_parents( $itemClass ) ) )
			{
				$itemClass = $itemClass::$itemClass;
			}

			$containerUrl = NULL;
			$containerTitle = NULL;
			if ( isset( $itemClass::$containerNodeClass ) )
			{
				$containerClass	= $itemClass::$containerNodeClass;
				$containerTitle	= $containerClass::titleFromIndexData( $data['indexData'], $data['itemData'], $data['containerData'] );
				$containerUrl	= $containerClass::urlFromIndexData( $data['indexData'], $data['itemData'], $data['containerData'] );
			}

			$summaryLanguage = null;
			$title = $object instanceof Comment ? $object->item()->mapped( 'title' ) : $object->mapped( 'title' );
			if( $extension = SearchContent::extension( $object ) )
			{
				$summaryLanguage = $extension::searchResultSummaryLanguage( $data['authorData'], $extension::articlesFromIndexData( $itemClass, $data['containerData'] ), $data['indexData'], $data['itemData'], false );
				$title = $extension->searchIndexTitle();
			}

			try
			{
				$items[] = array_merge($data, [
				'title' => $title,
				'url' => $object->url(),
				'content' => $object->content(),
				'object' => $object,
				'date' => DateTime::ts( $object->mapped('date') ),
				'itemClass' => $itemClass,
				'containerUrl' => $containerUrl,
				'containerTitle' => $containerTitle,
				'summaryLanguage' => $summaryLanguage
				]);
			}
			catch( OutOfRangeException|BadMethodCallException $e )
			{
				/* If the item is no longer available, we'll just skip it */
			}

		}

		return $items;
	}
	/**
	 * Has the member any subscribed streams
	 *
	 * @param Member|null $member
	 * @return bool
	 */
	public static function hasSubscribedStreams( Member $member = NULL ) : bool
	{
		$member = $member ?: Member::loggedIn();
		return (bool) Db::i()->select( 'COUNT(*)', 'core_stream_subscriptions', array( 'member_id=?', $member->member_id ) )->first();
	}

	/**
	 * Has the member any subscribed streams
	 *
	 * @param Member|null $member
	 * @return ActiveRecordIterator
	 */
	public static function getSubscribedStreams( Member $member = NULL ) : ActiveRecordIterator
	{
		$member = $member ?: Member::loggedIn();
		return new ActiveRecordIterator( Db::i()->select( '*', 'core_stream_subscriptions', array( 'member_id=?', $member->member_id ) ), get_called_class() );
	}

}
