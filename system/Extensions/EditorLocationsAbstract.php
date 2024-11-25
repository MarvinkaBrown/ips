<?php

/**
 * @brief        EditorLocationsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/16/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use LogicException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class EditorLocationsAbstract
{

	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	Member					$member	The member
	 * @param	Editor	$field	The editor field
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	abstract public function canAttach( Member $member, Editor $field ): ?bool;

	/**
	 * Permission check for attachments
	 *
	 * @param	Member	    $member		The member
	 * @param	int|null	$id1		Primary ID
	 * @param	int|null	$id2		Secondary ID
	 * @param	string|null	$id3		Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @param	bool		$viewOnly	If true, just check if the user can see the attachment rather than download it
	 * @return	bool
	 */
	abstract public function attachmentPermissionCheck( Member $member, ?int $id1, ?int $id2, ?string $id3, array $attachment, bool $viewOnly=FALSE ): bool;

	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return    Content|Member|Model|Url|null
	 * @throws	LogicException
	 */
	abstract public function attachmentLookup( ?int $id1=NULL, ?int $id2=NULL, ?string $id3=NULL ): Model|Content|Url|Member|null;

	/**
	 * Can whatever is posted in this editor be moderated?
	 * If this returns TRUE, we must ensure the content is ran through word, link and image filters
	 *
	 * @param Member $member	The member
	 * @param Editor $field	The editor field
	 * @return	bool
	 */
	public function canBeModerated( Member $member, Editor $field ): bool
	{
		return FALSE;
	}

	/**
	 * Rebuild content post-upgrade
	 *
	 * @param	int|null	$offset	Offset to start from
	 * @param	int|null	$max	Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	//public function rebuildContent( ?int $offset, ?int $max ): int
	//{
	//}

	/**
	 * @brief	Store lazy loading status ( true = enabled )
	 */
	protected ?bool $_lazyLoadStatus = null;

	/**
	 * Rebuild content to add or remove lazy loading
	 *
	 * @param	int|null		$offset		Offset to start from
	 * @param	int|null		$max		Maximum to parse
	 * @param	bool			$status		Enable/Disable lazy loading
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	//public function rebuildLazyLoad( ?int $offset, ?int $max, bool $status=TRUE ): int
	//{
	//}

	/**
	 * Total content count to be used in progress indicator
	 *
	 * @return	int			Total Count
	 */
	//public function contentCount() : int
	//{
	//    return \IPS\Db::i()->select( 'COUNT(*) as count', '...', "..." )->setKeyField('count')->first();
	//}
}