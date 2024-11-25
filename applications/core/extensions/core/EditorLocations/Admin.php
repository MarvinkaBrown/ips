<?php
/**
 * @brief		Editor Extension: Admin CP Settings, etc.
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Aug 2013
 */

namespace IPS\core\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\core\Announcements\Announcement;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Settings;
use IPS\Text\Parser;
use LogicException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Admin CP Settings, etc.
 */
class Admin extends EditorLocationsAbstract
{
	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	Member					$member	The member
	 * @param	Editor	$field	The editor field
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( Member $member, Editor $field ): ?bool
	{
		return NULL;
	}

	/**
	 * Permission check for attachments
	 *
	 * @param	Member	$member		The member
	 * @param	int|null	$id1		Primary ID
	 * @param	int|null	$id2		Secondary ID
	 * @param	string|null	$id3		Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @param	bool		$viewOnly	If true, just check if the user can see the attachment rather than download it
	 * @return	bool
	 */
	public function attachmentPermissionCheck( Member $member, ?int $id1, ?int $id2, ?string $id3, array $attachment, bool $viewOnly=FALSE ): bool
	{
		return TRUE;
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return    Content|Member|Model|Url|null
	 * @throws	LogicException
	 */
	public function attachmentLookup( int $id1=NULL, int $id2=NULL, string $id3=NULL ): Model|Content|Url|Member|null
	{
		switch ( $id3 )
		{
			case 'appdisabled':
				return Url::internal( 'app=core&module=applications&controller=applications&id=' . Application::load( $id1, 'app_id' )->directory . '&do=enableToggle', 'admin' );
				
			case 'bulkmail':
				return Url::internal( 'app=core&module=bulkmail&controller=bulkmail&do=preview&id=' . $id1, 'admin' );
			
			case 'site_offline_message':
				return Url::internal( 'app=core&module=settings&controller=general&searchResult=site_offline_message', 'admin' );
			
			case 'gl_guidelines':
				return Url::internal( 'app=core&module=settings&controller=terms&searchResult=gl_guidelines', 'admin' );
			
			case 'privacy_text':
				return Url::internal( 'app=core&module=settings&controller=terms&searchResult=privacy_text', 'admin' );
				
			case 'reg_rules':
				return Url::internal( 'app=core&module=settings&controller=terms&searchResult=reg_rules', 'admin' );
			
			case 'announcement':
				return Announcement::load( $id1 );
				
			case 'forumsSavedAction':
				return Url::internal( 'app=forums&module=forums&controller=savedActions&do=form&id=' . $id1, 'admin' );

			case 'editor_stored_replies':
				return Url::internal( 'app=core&module=editor&controller=storedreplies&do=form&id=' . $id1, 'admin' );
		}
		
		return NULL;
	}

	/**
	 * Rebuild content post-upgrade
	 *
	 * @param	int|null	$offset	Offset to start from
	 * @param	int|null	$max	Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildContent( ?int $offset, ?int $max ): int
	{
		return $this->performRebuild( $offset, $max, array( 'IPS\Text\LegacyParser', 'parseStatic' ) );
	}

	/**
	 * Rebuild content to add or remove image proxy
	 *
	 * @param	int|null		$offset		Offset to start from
	 * @param	int|null		$max		Maximum to parse
	 * @param	bool			$proxyUrl	Use the cached image URL instead of the original URL
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildImageProxy( ?int $offset, ?int $max, bool $proxyUrl = FALSE ): int
	{
		$callback = function( $value ) use ( $proxyUrl ) {
			return Parser::removeImageProxy( $value, $proxyUrl );
		};
		return $this->performRebuild( $offset, $max, $callback );
	}

	/**
	 * Rebuild content to add or remove lazy loading
	 *
	 * @param	int|null		$offset		Offset to start from
	 * @param	int|null		$max		Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildLazyLoad( ?int $offset, ?int $max ): int
	{
		return $this->performRebuild( $offset, $max, [ 'IPS\Text\Parser', 'parseLazyLoad' ] );
	}

	/**
	 * Perform rebuild - abstracted as the call for rebuildContent() and rebuildAttachmentImages() is nearly identical
	 *
	 * @param	int|null	$offset		Offset to start from
	 * @param	int|null	$max		Maximum to parse
	 * @param	callable	$callback	Method to call to rebuild content
	 * @return	int			Number completed
	 */
	protected function performRebuild( ?int $offset, ?int $max, callable $callback ): int
	{
		/* We will do everything except bulk mails first */
		if( !$offset )
		{
			/* Language bits */
			foreach( Db::i()->select( '*', 'core_sys_lang_words', "word_key IN('guidelines_value', 'reg_rules_value', 'privacy_text_value')" ) as $word )
			{
				$rebuilt = FALSE;
				try
				{
					if( !empty( $word['word_custom'] ) )
					{
						$rebuilt = $callback( $word['word_custom'] );
					}
				}
				catch( InvalidArgumentException $e )
				{
					if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
					{
						$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $word['word_custom'] );
					}
					else
					{
						throw $e;
					}
				}

				if( $rebuilt !== FALSE )
				{
					Db::i()->update( 'core_sys_lang_words', array( 'word_custom' => $rebuilt ), 'word_id=' . $word['word_id'] );
				}
			}

			/* Site offline message setting */
			$newMessage = FALSE;
			try
			{
				if( !empty( Settings::i()->site_offline_message ) )
				{
					$newMessage = $callback( Settings::i()->site_offline_message );
				}
			}
			catch( InvalidArgumentException $e )
			{
				if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
				{
					$newMessage	= preg_replace( "#\[/?([^\]]+?)\]#", '', Settings::i()->site_offline_message );
				}
				else
				{
					throw $e;
				}
			}

			if( $newMessage !== FALSE )
			{
				Settings::i()->changeValues( array( 'site_offline_message' => $newMessage ) );
			}

			/* Application disabled messages */
			foreach( Db::i()->select( '*', 'core_applications' ) as $application )
			{
				$rebuilt = FALSE;
				try
				{
					if( !empty( $application['app_disabled_message'] ) )
					{
						$rebuilt	= $callback( $application['app_disabled_message'] );
					}
				}
				catch( InvalidArgumentException $e )
				{
					if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
					{
						$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $application['app_disabled_message'] );
					}
					else
					{
						throw $e;
					}
				}

				if( $rebuilt !== FALSE )
				{
					Db::i()->update( 'core_applications', array( 'app_disabled_message' => $rebuilt ), 'app_id=' . $application['app_id'] );
				}
			}

			/* Forum multimod */
			if( Db::i()->checkForTable( 'forums_topic_mmod' ) )
			{
				foreach( Db::i()->select( '*', 'forums_topic_mmod' ) as $mmod )
				{
					$rebuilt = FALSE;
					try
					{
						if( !empty( $mmod['topic_reply_content'] ) )
						{
							$rebuilt = $callback( $mmod['topic_reply_content'] );
						}
					}
					catch( InvalidArgumentException $e )
					{
						if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
						{
							$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $mmod['topic_reply_content'] );
						}
						else
						{
							throw $e;
						}
					}

					if( $rebuilt !== FALSE )
					{
						Db::i()->update( 'forums_topic_mmod', array( 'topic_reply_content' => $rebuilt ), 'mm_id=' . $mmod['mm_id'] );
					}
				}
			}
		}

		/* Now do bulk mails */
		$did	= 0;

		foreach( Db::i()->select( '*', 'core_bulk_mail', null, 'mail_id ASC', array( $offset, $max ) ) as $mail )
		{
			$did++;
			$rebuilt = FALSE;

			/* Update */
			try
			{
				$rebuilt = $callback( $mail['mail_content'] );
			}
			catch( InvalidArgumentException $e )
			{
				if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
				{
					$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $mail['mail_content'] );
				}
				else
				{
					throw $e;
				}
			}

			if( $rebuilt !== FALSE )
			{
				Db::i()->update( 'core_bulk_mail', array( 'mail_content' => $rebuilt ), array( 'mail_id=?', $mail['mail_id'] ) );
			}
		}

		return $did;
	}

	/**
	 * Total content count to be used in progress indicator
	 *
	 * @return	int			Total Count
	 */
	public function contentCount(): int
	{
		$count	= 4;

		$count	+= Db::i()->select( 'COUNT(*) as count', 'core_applications' )->first();

		if( Db::i()->checkForTable( 'forums_topic_mmod' ) )
		{
			$count	+= Db::i()->select( 'COUNT(*) as count', 'forums_topic_mmod' )->first();
		}

		$count	+= Db::i()->select( 'COUNT(*) as count', 'core_bulk_mail' )->first();

		return $count;
	}
}