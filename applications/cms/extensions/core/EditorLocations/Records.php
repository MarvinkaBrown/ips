<?php
/**
 * @brief		Editor Extension: Record Form
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		20 Feb 2014
 */

namespace IPS\cms\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Records as RecordsClass;
use IPS\cms\Records\Comment;
use IPS\cms\Records\Review;
use IPS\Content;
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
 * Editor Extension: Record Content
 */
class Records extends EditorLocationsAbstract
{
	/**
	 * @brief	Flag to indicate we don't want to be listed as a selectable area when configuring buttons
	 */
	public static bool $buttonLocation	= FALSE;

	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	Member	$member	The member
	 * @param	Editor $field The editor instance
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( Member $member, Editor $field ): ?bool
	{
		if( !$field->options['allowAttachments'] )
		{
			return FALSE;
		}

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
		/* Creating/editing a record */
		if ( preg_match( '/^RecordField_(?:new|\d+)_\d+/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Creating/editing a comment or review */
		elseif ( preg_match( '/^(?:editComment|reply|review)\-cms\/records\d+\-\d+/', $field->options['autoSaveKey'] ) )
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
		if ( ! $id3 )
		{
			throw new OutOfRangeException;
		}

		$className	= $this->_getClassName( $id3 );
		$id			= ( mb_strpos( $className, 'Review' ) !== FALSE OR mb_strpos( $className, 'Comment' ) !== FALSE ) ? $id2 : $id1;
		
		try
		{
			/* @var RecordsClass|Comment|Review $className */
			return $className::load( $id )->canView( $member );
		}
		catch ( OutOfRangeException $e )
		{
			return FALSE;
		}
	}

	/**
	 * Figure out the correct class name to return
	 *
	 * @param int|string $id3	The id3 value stored
	 * @return	string
	 */
	protected function _getClassName( int|string $id3 ): string
	{
		/* Review? */
		if( mb_strpos( $id3, '-review' ) )
		{
			$bits = explode( '-', $id3 );
			$className = '\IPS\cms\Records\Review' . $bits[0];
		}
		/* Comment? */
		elseif( mb_strpos( $id3, '-comment' ) )
		{
			$bits = explode( '-', $id3 );
			$className = '\IPS\cms\Records\Comment' . $bits[0];
		}
		/* Record */
		else
		{
			$className = '\IPS\cms\Records' . $id3;
		}

		return $className;
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
		try
		{
			if ( $id3 )
			{
				$className	= $this->_getClassName( $id3 );
				$id			= ( mb_strpos( $className, 'Review' ) !== FALSE OR mb_strpos( $className, 'Comment' ) !== FALSE ) ? $id2 : $id1;

				/* @var RecordsClass|Comment|Review $className */
				$return = $className::load( $id );
				$return->url(); // Need to check that won't throw an exception later, which might happen if the database no longer has a page
				return $return;
			}
		}
		catch ( Exception $e ){}

		throw new LogicException;
	}
}