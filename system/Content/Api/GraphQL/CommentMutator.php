<?php
/**
 * @brief		Base mutator class for comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Jun 2018
 */

namespace IPS\Content\Api\GraphQL;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Api\GraphQL\SafeException;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Reaction;
use IPS\Content\Search\Index;
use IPS\Content\Search\SearchContent;
use IPS\core\Reports\Report;
use IPS\core\Reports\Types;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\IPS;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function get_class;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base mutator class for comments
 */
abstract class CommentMutator extends ContentMutator
{

	/** 
	 * Revoke Comment Report
	 * 
	 * @param	array	$args		Arguments
	 * @param	Comment	$comment	Comment
	 *
	 * @return	Comment
	 */
	protected function _revokeCommentReport( array $args, Comment $comment ): Comment
	{
		try
		{
			$report = Db::i()->select( '*', 'core_rc_reports', array( 'id=? AND report_by=? AND date_reported > ?', $args['reportID'], Member::loggedIn()->member_id, time() - ( Settings::i()->automoderation_report_again_mins * 60 ) ) )->first();
		}
		catch( UnderflowException $e )
		{
			throw new SafeException( 'NO_REPORT', '1F295/1', 403 );
		}
		
		try
		{
			$index = Report::load( $report['rid'] );
		}
		catch( OutofRangeException $e )
		{
			throw new SafeException( 'NO_REPORT', '1F295/1', 403 );
		}
		
		Db::i()->delete( 'core_rc_reports', array( 'id=?', $args['reportID'] ) );
		
		/* Recalculate, we may have dropped below the threshold needed to hide a thing */
		$index->runAutomaticModeration();
		
		$comment->alreadyReported = NULL;
		$comment->reportData = array();

		return $comment;
	}

	/** 
	 * Report comment
	 * 
	 * @param	array	$args		Arguments
	 * @param	Comment	$comment	Comment
	 *
	 * @return	Comment
	 */
	protected function _reportComment( array $args, Comment $comment ): Comment
	{
		$class = $this->class;
		$canReport = $comment->canReport();

		if ( $canReport !== TRUE AND !( $canReport == 'report_err_already_reported' AND Settings::i()->automoderation_enabled ) )
		{
			throw new SafeException( 'CANNOT_REPORT', '1F295/1', 403 );
		}

		$itemIdColumn = $class::$databaseColumnId;
		$idColumn = $comment::$databaseColumnId;

		if ( Member::loggedIn()->member_id and Settings::i()->automoderation_enabled )
		{
			/* Has this member already reported this in the past 24 hours */
			try {
				$index = Report::loadByClassAndId( get_class( $comment ), $comment->$idColumn );
				$report = Db::i()->select( '*', 'core_rc_reports', array( 'rid=? and report_by=? and date_reported > ?', $index->id, Member::loggedIn()->member_id, time() - ( Settings::i()->automoderation_report_again_mins * 60 ) ) );

				throw new SafeException( 'ALREADY_REPORTED', '1F295/1', 403 );
			}
			catch( Exception $e ) {
				if( $e instanceof SafeException ){
					throw $e;
				}
			}

			if( !in_array( $args['reason'], array_keys( Types::roots() ) ) && $args['reason'] !== 0 )
			{
				throw new SafeException( 'INVALID_REASON', '1F295/1', 403 );
			}
		}

		if( !Settings::i()->automoderation_enabled )
		{
			$args['reason'] = 0;
		}

		$args['additionalInfo'] = "<p>" . $args['additionalInfo'] . "</p>";

		try 
		{
			$comment->report( $args['additionalInfo'], $args['reason'] );
		}
		catch( Exception $e )
		{
			throw new SafeException( 'REPORT_FAILED', '1F295/1', 403 );
		} 
		

		return $comment;
	}

	/**
	 * Comment reactions
	 *
	 * @param 	int 					$reactionID 	ID of reaction to add
	 * @param	Comment	$comment		The comment to add a reaction on
	 * @return	void
	 */
	protected function _reactComment( int $reactionID, Comment $comment ): void
	{
		try 
		{
			$reaction = Reaction::load( $reactionID );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'INVALID_REACTION', '1F295/1', 403 );
		}

		$comment->react( $reaction );
	}

	/**
	 * Remove comment reaction
	 *
	 * @param	Comment	$comment		The comment to remove the reaction on
	 * @return	void
	 */
	protected function _unreactComment( Comment $comment ): void
	{
		try {
			$comment->removeReaction();
		} 
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_REACTIONS', '1F295/1', 403 );
		}
	}

	/**
	 * Create
	 *
	 * @param array $commentData Array of data used to generate comment
	 * @param Item $item Content Item
	 * @param string|null $postKey Post key
	 * @param Comment|null $replyTo Reply To
	 * @return    Comment
	 */
	protected function _createComment( array $commentData, Item $item, string|null $postKey = NULL, Comment|null $replyTo = NULL ): Comment
	{
		/* Work out the date */
		$date = DateTime::create();
		$hidden = false;
		$quoteHtml = '';

		if( $replyTo instanceof Comment ){
			try {
				$idField = $replyTo::$databaseColumnId;
				$app = $replyTo::$application;
				$replyToItem = $replyTo->item();
				$contentType = $replyToItem::$module;
				$itemClassSafe = str_replace( '\\', '_', mb_substr( $replyTo::$itemClass, 4 ) );
				$citationLang = Member::loggedIn()->language()->get('_date_just_now_c') . ', ' . $replyTo->mapped('author_name');

				$quoteHtml = <<<HTML
					<blockquote class="ipsQuote" data-ipsquote="" data-ipsquote-contentapp="{$app}" data-ipsquote-contentclass="{$itemClassSafe}" data-ipsquote-contentcommentid="{$replyTo->mapped('id')}" data-ipsquote-contentid="{$replyTo->item()->mapped('id')}" data-ipsquote-contenttype="{$contentType}" data-ipsquote-timestamp="{$replyTo->mapped('date')}" data-ipsquote-userid="{$replyTo->author()->member_id}" data-ipsquote-username="{$replyTo->mapped('author_name')}">
						<div class="ipsQuote_citation">
							{$citationLang}
						</div>
						<div class='ipsQuote_contents'>{$replyTo->mapped('content')}</div>
					</blockquote>
HTML;
			} catch ( Exception $err ) {
				// If something goes wrong here, it isn't a big deal - just continue without the quote
			}
		}
		
		/* Add attachments */
		$attachmentIdsToClaim = array();
		if ( $postKey )
		{
			try
			{
				$this->_addAttachmentsToContent( $postKey, $commentData['content'] );
			}
			catch ( DomainException $e )
			{
				throw new SafeException( 'ATTACHMENTS_TOO_LARGE', '2S400/2', 403 );
			}
		}
		
		/* Parse */
		$content = $quoteHtml . Parser::parseStatic( $commentData['content'], array( md5( $postKey . ':' ) ), Member::loggedIn(), $item::$application . '_' . IPS::mb_ucfirst( $item::$module ) );
		
		/* Create post */
		$class = $this->class;
		/*if ( \in_array( 'IPS\Content\Review', class_parents( $class ) ) )
		{
			$comment = $class::create( $item, $content, FALSE, \intval( \IPS\Request::i()->rating ), $author->member_id ? NULL : $author->real_name, $author, $date, ( !$this->member and \IPS\Request::i()->ip_address ) ? \IPS\Request::i()->ip_address : \IPS\Request::i()->ipAddress(), $hidden );
		}
		else
		{*/
			$comment = $class::create( $item, $content, FALSE, Member::loggedIn()->member_id ? NULL : Member::loggedIn()->real_name, NULL, Member::loggedIn(), $date, Request::i()->ipAddress() );
		/*}*/
		$itemIdColumn = $item::$databaseColumnId;
		$commentIdColumn = $comment::$databaseColumnId;
		File::claimAttachments( "{$postKey}:", $item->$itemIdColumn, $comment->$commentIdColumn );
		
		/* Index */
		if( SearchContent::isSearchable( $item ) )
		{
			if ( $item::$firstCommentRequired and !$comment->isFirst() )
			{
				if( SearchContent::isSearchable( $class ) )
				{					
					Index::i()->index( $item->firstComment() );
				}
			}
			else
			{
				Index::i()->index( $item );
			}
		}
		if( SearchContent::isSearchable( $comment ) )
		{
			Index::i()->index( $comment );
		}
		
		/* Hide */
		if ( isset( $commentData['hidden'] ) and Member::loggedIn()->member_id and $comment->canHide() )
		{
			$comment->hide( Member::loggedIn() );
		}

		/* Mark it as read */
		if( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) )
		{
			$item->markRead();
		}
		
		/* Return */
		return $comment;
	}

}