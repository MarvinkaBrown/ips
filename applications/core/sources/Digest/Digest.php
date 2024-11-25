<?php
/**
 * @brief		Digest Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		08 May 2014
 */

namespace IPS\core\Digest;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Application;
use IPS\Content;
use IPS\Content\Filter;
use IPS\Content\Tag;
use IPS\Content\Taggable;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Digest Class
 */
class Digest
{
	/**
	 * @brief	[IPS\Member]	Digest member object
	 */
	public ?Member $member = NULL;
	
	/**
	 * @brief	Output to include in digest email template
	 */
	public array $output = array( 'html' => '', 'plain' => '' );
	
	/**
	 * @brief	Frequency Daily/Weekly
	 */
	public ?string $frequency = NULL;
	
	/**
	 * @brief	Is there anything to send?
	 */
	public bool $hasContent = FALSE;
	
	/**
	 * @brief	Mail Object
	 */
	protected ?Email $mail;
	
	/**
	 * Build Digest
	 *
	 * @param	array	$data	Array of follow records
	 * @return	void
	 */
	public function build( array $data ) : void
	{
		/* Banned members should not be emailed */
		if( $this->member->isBanned() )
		{
			return;
		}
		
		/* Don't try on rows where the member may have been removed */
		if ( !$this->member->member_id )
		{
			return;
		}
		
		/* We just do it this way because for backwards-compatibility, template parsing expects an \IPS\Email object with a $language property
			This email is never actually sent and a new one is generated in send() */
		$this->mail = Email::buildFromTemplate( 'core', 'digest', array( $this->member, $this->frequency ), Email::TYPE_LIST );
		$this->mail->language = $this->member->language();

		$numberOfItems = 0;
		foreach( $data as $app => $area )
		{
			foreach ( $area as $items )
			{
				$numberOfItems += count( $items );
			}
		}
		$max	= ceil( 80 / $numberOfItems );

		foreach( $data as $app => $area )
		{
			foreach( $area as $key => $follows )
			{
				if( $key == 'tag' )
				{
					foreach( $follows as $follow )
					{
						try
						{
							$tag = Tag::load( $follow['follow_rel_id'] );
						}
						catch( OutOfRangeException )
						{
							continue;
						}

						$count = 0;
						$areaPlainOutput = NULL;
						$areaHtmlOutput = NULL;
						$added = FALSE;
						$header = sprintf( $this->member->language()->get( 'digest_area_core_tag' ), $tag->text );

						foreach( Db::i()->select( 't.*', [ 'core_tags', 't' ], [
							[ 't.tag_member_id!=?', $this->member->member_id ],
							[ 't.tag_added > ?', ( $follow['follow_notify_sent'] ?: $follow['follow_added'] ) ],
							[ 't.tag_text=?', $tag->text ],
							[ 'p.tag_perm_visible=?', 1 ],
							[ '(p.tag_perm_text=? OR ' . Db::i()->findInSet( 'p.tag_perm_text', $this->member->groups ) . ')', '*' ]
						], 't.tag_added desc', $max )->join( [ 'core_tags_perms', 'p' ], 't.tag_aai_lookup=p.tag_perm_aai_lookup and t.tag_aap_lookup=p.tag_perm_aap_lookup' ) as $row )
						{
							/* Check the application first */
							if( Application::appIsEnabled( $row['tag_meta_app'] ) )
							{
								/* Figure out which class this is */
								$class = null;
								foreach( Content::routedClasses( false, false, true ) as $itemClass )
								{
									if( IPS::classUsesTrait( $itemClass, Taggable::class ) and $itemClass::$application == $row['tag_meta_app'] and $itemClass::$module == $row['tag_meta_area'] )
									{
										$class = $itemClass;
										break;
									}
								}

								if( $class !== null )
								{
									try
									{
										$item = $class::load( $row['tag_meta_id'] );
										$areaPlainOutput .= Email::template( $row['tag_meta_app'], 'digests__item', 'plaintext', array( $item, $this->mail ) );
										$areaHtmlOutput .= Email::template( $row['tag_meta_app'], 'digests__item', 'html', array( $item, $this->mail ) );

										$added = TRUE;
										++$count;
									}
									catch( OutOfRangeException ){}
								}
							}
						}

						/* Wrapper */
						if( $added )
						{
							$this->output['plain'] .= Email::template( 'core', 'digests__areaWrapper', 'plaintext', array( $areaPlainOutput, $app, $key, $max, $count, $header, $this->mail ) );
							$this->output['html'] .= Email::template( 'core', 'digests__areaWrapper', 'html', array( $areaHtmlOutput, $app, $key, $max, $count, $header, $this->mail ) );

							$this->hasContent = TRUE;
						}
					}

					continue;
				}

				$count = 0;

				$areaPlainOutput = NULL;
				$areaHtmlOutput = NULL;
				$added = FALSE;
				$header = NULL;
				
				/* Following an item or node */
				$class = 'IPS\\' . $app . '\\' . IPS::mb_ucfirst( $key );

				if ( class_exists( $class ) AND Application::appIsEnabled( $app ) )
				{
					$parents = class_parents( $class );

					if ( in_array( 'IPS\Node\Model', $parents ) )
					{
						foreach ( $follows as $follow )
						{
							if ( property_exists( $class, 'contentItemClass' ) )
							{
								$itemClass= $class::$contentItemClass;

								/* Force custom profile fields not to be returned, as they can reference templates */
								if( isset( $itemClass::$commentClass ) )
								{
									$commentClass = $itemClass::$commentClass;

									$commentClass::$joinProfileFields	= FALSE;
								}

								/* @var array $databaseColumnMap */
								$where = array(
											array( 	$itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=? AND ' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['date'] . ' > ? AND ' . $itemClass::$databaseTable . '.' .$itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] . '!=?',
													$follow['follow_rel_id'],
													$follow['follow_notify_sent'] ?: $follow['follow_added'],
													$follow['follow_member_id']
												)
											);

								foreach ( $itemClass::getItemsWithPermission( array_merge( $itemClass::digestWhere(), $where ),
										$itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['date'] . ' ASC', 
										$max, 
										'read', 
										Filter::FILTER_OWN_HIDDEN,
										0,
										$this->member, 
										TRUE
								) as $item )
								{
									try
									{
										$areaPlainOutput .= Email::template( $app, 'digests__item', 'plaintext', array( $item, $this->mail ) );
										$areaHtmlOutput .= Email::template( $app, 'digests__item', 'html', array( $item, $this->mail ) );

										$added = TRUE;
										++$count;
									}
									catch ( BadMethodCallException | UnderflowException $e ) {}
								}
							}
						}
					}
					else if ( in_array( 'IPS\Content\Item', $parents ) )
					{
						foreach ( $follows as $follow )
						{
							try
							{
								$item = $class::load( $follow['follow_rel_id'] );

								/* Check the view permission */
								if( !$item->canView( $this->member ) )
								{
									continue;
								}

								/* Make sure the item is not archived */
								if ( isset( $item::$archiveClass ) and method_exists( $item, 'isArchived' ) )
								{
									if ( $item->isArchived() )
									{
										continue;
									}
								}

								/* Force custom profile fields not to be returned, as they can reference templates */
								if( isset( $item::$commentClass ) )
								{
									$commentClass = $item::$commentClass;

									$commentClass::$joinProfileFields	= FALSE;
								}

								foreach( $item->comments( 5, NULL, 'date', 'asc', NULL, FALSE, DateTime::ts( $follow['follow_notify_sent'] ?: $follow['follow_added'] ), NULL, FALSE, FALSE, FALSE ) as $comment )
								{

									try
									{
										$areaPlainOutput .= Email::template( $app, 'digests__comment', 'plaintext', array( $comment, $this->mail ) );
										$areaHtmlOutput .= Email::template( $app, 'digests__comment', 'html', array( $comment, $this->mail ) );
									}
									catch ( UnderflowException $e )
									{
										/* If an app forgot digest templates, we don't want the entire task to fail to ever run again */
										Log::debug( $e, 'digestBuild' );
										throw new OutOfRangeException;
									}

									$added = TRUE;
									++$count;
								}
							}
							catch( OutOfRangeException $e )
							{
							}
						}
					}

					/* Wrapper */
					if( $added )
					{
						$this->output['plain'] .= Email::template( 'core', 'digests__areaWrapper', 'plaintext', array( $areaPlainOutput, $app, $key, $max, $count, $header, $this->mail ) );
						$this->output['html'] .= Email::template( 'core', 'digests__areaWrapper', 'html', array( $areaHtmlOutput, $app, $key, $max, $count, $header, $this->mail ) );
					
						$this->hasContent = TRUE;
					}
				}
			}
		}
	}
	
	/**
	 * Send Digest
	 *
	 * @return	void
	 */
	public function send() : void
	{		
		if( $this->hasContent )
		{
			$this->mail->setUnsubscribe( 'core', 'unsubscribeDigest' );
			$subject = $this->mail->compileSubject( $this->member );
			$htmlContent = str_replace( "___digest___", $this->output['html'], $this->mail->compileContent( 'html', $this->member ) );
			$plaintextContent = str_replace( "___digest___", $this->output['plain'], $this->mail->compileContent( 'plaintext', $this->member ) );
			
			Email::buildFromContent( $subject, $htmlContent, $plaintextContent, Email::TYPE_LIST, Email::WRAPPER_NONE, $this->frequency . '_digest' )->send( $this->member );
		}
		
		/* After sending digest update core_follows to set notify_sent (don't forget where clause for frequency) */
		Db::i()->update( 'core_follow', array( 'follow_notify_sent' => time() ), array( 'follow_member_id=? AND follow_notify_freq=?', $this->member->member_id, $this->frequency ) );
	}

	/**
	 * Process a batch of digests
	 *
	 * @param	string	$frequency		One of either "daily" or "weekly" to denote the kind of digest to send
	 * @param	int		$numberToSend	The number of digests to send for this batch
	 * @return	bool
	 */
	public static function sendDigestBatch( string $frequency='daily', int $numberToSend=50 ) : bool
	{
		/* Grab some members to send digests to. */
		$members = iterator_to_array( Db::i()->select( 'follow_member_id, follow_notify_sent', 'core_follow', array( 'follow_notify_do=1 AND follow_notify_freq = ? AND follow_notify_sent < ?', $frequency, ( $frequency == 'daily' ) ? time() - 86400 : time() - 604800 ), 'follow_notify_sent ASC', array( 0, $numberToSend ), NULL, NULL, Db::SELECT_DISTINCT ) );

		if( !count( $members ) )
		{
			/* Nothing to send */
			return FALSE;
		}

		$memberIDs = array();
		foreach( $members as $member )
		{
			$memberIDs[] = $member['follow_member_id'];
		}

		/* Fetch the member's follows so we can build their digest */
		$follows = Db::i()->select( '*', 'core_follow', array( 'follow_notify_do=1 AND follow_notify_freq=? AND follow_notify_sent < ? AND ' . Db::i()->in( 'follow_member_id', $memberIDs ), $frequency, ( $frequency == 'daily' ) ? time() - 86400 : time() - 604800 ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER );

		$groupedFollows = array();
		foreach( $follows as $follow )
		{
			$groupedFollows[ $follow['follow_member_id'] ][ $follow['follow_app'] ][ $follow['follow_area'] ][] = $follow;
		}

		foreach( $groupedFollows as $id => $data )
		{
			$member = Member::load( $id );
			if( !$member->email )
			{
				/* Update notification sent time, so the batch doesn't get stuck in a loop */
				Db::i()->update( 'core_follow', array( 'follow_notify_sent' => time() ), array( 'follow_member_id=? AND follow_notify_freq=?', $id, $frequency ) );
				continue;
			}

			/* Build it */
			$digest = new static;
			$digest->member = $member;
			$digest->frequency = $frequency;
			$digest->build( $data );

			/* Send it */
			$digest->send();
		}

		return TRUE;
	}
}