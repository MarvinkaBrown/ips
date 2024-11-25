<?php
/**
 * @brief		Editor Extension: Customer Fields and Notes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Aug 2014
 */

namespace IPS\nexus\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Content;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer as NexusCustomer;
use IPS\Node\Model;
use IPS\Text\Parser;
use LogicException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Customer Fields
 */
class Customer extends EditorLocationsAbstract
{
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
		if ( ( $id3 !== 'note' and $member->member_id == $id1 ) or $member->hasAcpRestriction( 'nexus', 'customers', 'customers_view' ) )
		{
			return TRUE;
		}
		return FALSE;
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
		if ( Dispatcher::i()->controllerLocation === 'admin' )
		{
			return NexusCustomer::load( $id1 )->acpUrl();
		}
		else
		{
			return Url::internal( 'app=nexus&module=clients&controller=info', 'front', 'clientsinfo' );
		}
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
		$did	= 0;

		/* Get editor fields */
		$editorFields	= array();

		foreach( Db::i()->select( '*', 'nexus_customer_fields', "f_type='Editor'" ) as $field )
		{
			$editorFields[]	= 'field_' . $field['f_id'];
		}

		if( !count( $editorFields ) )
		{
			return $did;
		}

		/* Now update the content */
		foreach( Db::i()->select( '*', 'nexus_customers', implode( " IS NOT NULL OR ", $editorFields ) . " IS NOT NULL", 'member_id ASC', array( $offset, $max ) ) as $member )
		{
			$did++;

			/* Update */
			$toUpdate	= array();

			foreach( $editorFields as $fieldId )
			{
				$rebuilt = FALSE;
				try
				{
					if( $callback[1] == 'parseStatic' )
					{
						$rebuilt = $callback( $member[ $fieldId ], Member::load( $member['member_id'] ) );
					}
					else
					{
						$rebuilt = $callback( $member[ $fieldId ] );
					}
				}
				catch( InvalidArgumentException $e )
				{
					if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
					{
						$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $member[ $fieldId ] );
					}
					else
					{
						throw $e;
					}
				}

				if( $rebuilt )
				{
					$toUpdate[ $fieldId ]	= $rebuilt;
				}
			}

			if( count( $toUpdate ) )
			{
				Db::i()->update( 'nexus_customers', $toUpdate, array( 'member_id=?', $member['member_id'] ) );
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
		/* Get editor fields */
		$editorFields	= array();

		foreach( Db::i()->select( '*', 'nexus_customer_fields', "f_type='Editor'" ) as $field )
		{
			$editorFields[]	= 'field_' . $field['f_id'];
		}

		if( !count( $editorFields ) )
		{
			return 0;
		}

		return Db::i()->select( 'COUNT(*) as count', 'nexus_customers', implode( " IS NOT NULL OR ", $editorFields ) . " IS NOT NULL" )->first();
	}
}