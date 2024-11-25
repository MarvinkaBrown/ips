<?php
/**
 * @brief		Editor Extension: Event text
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		7 Jan 2014
 */

namespace IPS\calendar\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Event;
use IPS\calendar\Event\Comment;
use IPS\calendar\Event\Review;
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
 * Editor Extension: Calendar event text and comments
 */
class Calendar extends EditorLocationsAbstract
{
	/**
	 * Can we use attachments in this editor?
	 *
	 * @param Member $member
	 * @param Editor $field
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( Member $member, Editor $field ): bool|null
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
		/* Creating/editing an event */
		if ( preg_match( '/^calendar-event(?:\-\d+)?$/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Creating/editing a comment */
		elseif ( preg_match( '/^(?:editComment|reply|review)\-calendar\/calendar\-\d+/', $field->options['autoSaveKey'] ) )
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
	 * @param Member $member		The member
	 * @param int|null $id1		Primary ID
	 * @param int|null $id2		Secondary ID
	 * @param string|null $id3		Arbitrary data
	 * @param array $attachment	The attachment data
	 * @param bool $viewOnly	If true, just check if the user can see the attachment rather than download it
	 * @return	bool
	 */
	public function attachmentPermissionCheck( Member $member, ?int $id1, ?int $id2, ?string $id3, array $attachment, bool $viewOnly=FALSE ): bool
	{
		try
		{
			$event = Event::load( $id1 );
			return $event->canView( $member );
		}
		catch ( OutOfRangeException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param int|null $id1	Primary ID
	 * @param int|null $id2	Secondary ID
	 * @param string|null $id3	Arbitrary data
	 * @return    Content|Member|Model|Url|null
	 * @throws	LogicException
	 */
	public function attachmentLookup( ?int $id1=null, ?int $id2=null, ?string $id3=null ): Model|Content|Url|Member|null
	{
		if ( $id2 )
		{
			if ( $id3 === 'review' )
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
			return Event::load( $id1 );
		}
	}
}