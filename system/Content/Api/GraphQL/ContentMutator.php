<?php
/**
 * @brief		Base mutator class for content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 May 2019
 */

namespace IPS\Content\Api\GraphQL;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base mutator class for comments
 */
abstract class ContentMutator
{
	/**
	 * Add attachments to content
	 *
	 * @param	string		$postKey		Post key
	 * @param	string		$content		Post content
	 * @return	void
	 * @throws	DomainException	Size is too large
	 */
	protected function _addAttachmentsToContent( string $postKey, string &$content ): void
	{
		$maxTotalSize = Editor::maxTotalAttachmentSize( Member::loggedIn(), 0 ); // @todo Currently set to 0 because editing is not supported. Once editing is supported by GraphQL, that will need to set the correct value
				
		$fileAttachments = array();
		$totalSize = 0;
		foreach ( Db::i()->select( '*', 'core_attachments', array( 'attach_post_key=?', $postKey ) ) as $attachment )
		{
			if ( $maxTotalSize !== NULL )
			{
				$totalSize += $attachment['attach_filesize'];
				if ( $totalSize > $maxTotalSize )
				{
					throw new DomainException;
				}
			}
			
			$ext = mb_substr( $attachment['attach_file'], mb_strrpos( $attachment['attach_file'], '.' ) + 1 );
			if ( in_array( mb_strtolower( $ext ), File::$videoExtensions ) )
			{
				$content .= Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedVideo( $attachment['attach_location'], Url::baseUrl( Url::PROTOCOL_RELATIVE ) . "applications/core/interface/file/attachment.php?id=" . $attachment['attach_id'], $attachment['attach_file'], File::getMimeType( $attachment['attach_file'] ), $attachment['attach_id'] );
			}
			elseif ( $attachment['attach_is_image'] )
			{
				if ( $attachment['attach_thumb_location'] )
				{
					$ratio = round( ( $attachment['attach_thumb_height'] / $attachment['attach_thumb_width'] ) * 100, 2 );
					$width = $attachment['attach_thumb_width'];
				}
				else
				{
					$ratio = round( ( $attachment['attach_img_height'] / $attachment['attach_img_width'] ) * 100, 2 );
					$width = $attachment['attach_img_width'];
				}
				
				$content .= str_replace( '<fileStore.core_Attachment>', File::getClass('core_Attachment')->baseUrl(), Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedImage( $attachment['attach_location'], $attachment['attach_thumb_location'] ?: $attachment['attach_location'], $attachment['attach_file'], $attachment['attach_id'], $width, $ratio ) );
			}
			else
			{
				$fileAttachments[] = Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedFile( Url::baseUrl() . "applications/core/interface/file/attachment.php?id=" . $attachment['attach_id'] . ( $attachment['attach_security_key'] ? "&key={$attachment['attach_security_key']}" : '' ), $attachment['attach_file'], FALSE, $attachment['attach_ext'], $attachment['attach_id'], $attachment['attach_security_key'] );
			}
		}
		
		if( count( $fileAttachments ) )
		{
			$content .= "<p>" . implode( ' ', $fileAttachments ) . "</p>";
		}
				
		Db::i()->update( 'core_attachments', array( 'attach_post_key' => '' ), array( 'attach_post_key=?', $postKey ) );
	}
}