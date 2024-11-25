<?php
/**
 * @brief		Editor Extension: File descriptions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		8 Oct 2013
 */

namespace IPS\downloads\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\downloads\File;
use IPS\downloads\File\Comment;
use IPS\downloads\File\Review;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: File descriptions and comments
 */
class Downloads extends EditorLocationsAbstract
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
	 * Can whatever is posted in this editor be moderated?
	 * If this returns TRUE, we must ensure the content is ran through word, link and image filters
	 *
	 * @param	Member					$member	The member
	 * @param	Editor	$field	The editor field
	 * @return	bool
	 */
	public function canBeModerated( Member $member, Editor $field ): bool
	{
		/* Uploading a new file */
		if ( preg_match( '/^(?:filedata_\d+_)?downloads\-new\-file$/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Uploading a new version */
		elseif ( preg_match( '/^downloads\-\d+\-changelog$/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Editing a file's details */
		if ( preg_match( '/^downloads\-file\-\d+$/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Custom fields */
		elseif ( preg_match( '/^[a-z0-9]{32}$/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Creating/editing a comment/review */
		elseif ( preg_match( '/^(?:editComment|reply|review)\-downloads\/downloads\-\d+/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Unknown */
		else
		{
			if ( \IPS\IN_DEV )
			{
				throw new RuntimeException( 'Unknown canBeModerated: ' . $field->options['autoSaveKey'] );
			}

			return parent::canBeModerated( $member, $field );
		}
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
		try
		{
			$file = File::load( $id1 );
			return $file->canView( $member );
		}
		catch ( OutOfRangeException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return	Url|Content|Model|Member|null
	 * @throws	LogicException
	 */
	public function attachmentLookup( int $id1=NULL, int $id2=NULL, string $id3=NULL ): Model|Content|Url|Member|null
	{
		if ( $id2 )
		{
			if ( $id3 === 'fields' )
			{
				return File::load( $id1 );
			}
			else if ( $id3 === 'review' )
			{
				return Review::load( $id2 );
			}
			else
			{
				return Comment::load( $id2 );
			}
		}
		else
		{
			return File::load( $id1 );
		}
	}
}