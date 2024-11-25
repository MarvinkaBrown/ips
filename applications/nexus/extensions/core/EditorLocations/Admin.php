<?php
/**
 * @brief		Editor Extension: Admin
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Mar 2014
 */

namespace IPS\nexus\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Content;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\extensions\nexus\Item\Package;
use IPS\nexus\Package\Item;
use IPS\nexus\Purchase;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use LogicException;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Admin
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
		try
		{
			switch ( $id3 )
			{				
				case 'pkg':
				case 'pkg-assoc':
				case 'pkg-email':
					return Item::load( $id1 )->canView( $member );

				case 'custom-pg':
					try
					{
						$package = \IPS\nexus\Package::load( $id1 );
						return $package->custom == $member->member_id;
					}
					catch( OutOfRangeException )
					{
						throw new LogicException;
					}
					
				case 'pkg-pg':
					$customer = Customer::load( $member->member_id );
					if ( count( Package::getPurchases( $customer, $id1, FALSE ) ) )
					{
						$options = array( 'type' => 'attach', 'id' => $attachment['attach_id'], 'name' => $attachment['attach_file'] );
						if ( Request::i()->referrer() )
						{
							try
							{
								$purchase = Purchase::loadFromUrl( Request::i()->referrer() );

								if( !$purchase->can( 'view', $member ) )
								{
									throw new LogicException;
								}

								$options['ps_id'] = $purchase->id;
								$options['ps_name'] = $purchase->name;
							}
							catch ( LogicException ) { }
						}
						
						$customer->log( 'download', $options );
						return TRUE;
					}
					else
					{
						return FALSE;
					}
					
				case 'invoice-header';
				case 'invoice-footer':
				case 'pgroup':
					return TRUE;
				
				case 'network_status_text':
					return (bool) Settings::i()->network_status;
			}
		}
		catch ( OutOfRangeException )
		{
			return FALSE;
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
		switch ( $id3 )
		{
			case 'pkg':
			case 'pkg-assoc':
			case 'pkg-pg':
			case 'pkg-email':
				return Url::internal( "app=nexus&module=store&controller=packages&subnode=1&do=form&id={$id1}", 'admin' );
				
			case 'pgroup':
				return Url::internal( "app=nexus&module=store&controller=packages&do=form&id={$id1}", 'admin' );
				
			case 'invoice-header';
			case 'invoice-footer':
				return Url::internal( 'app=nexus&module=payments&controller=invoices&do=settings', 'admin' );
		}

		throw new LogicException;
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
		/* We will do everything except pages first */
		if( !$offset )
		{
			/* Language bits */
			foreach( Db::i()->select( '*', 'core_sys_lang_words', Db::i()->in( 'word_key', array( 'nexus_com_rules_val', 'network_status_text_val' ) ) . " OR word_key LIKE 'nexus_donategoal_%_desc' OR word_key LIKE 'nexus_gateway_%_ins' OR word_key LIKE 'nexus_pgroup_%_desc' OR word_key LIKE 'nexus_package_%_desc' OR word_key LIKE 'nexus_package_%_page' OR word_key LIKE 'nexus_department_%_desc'", 'word_id ASC', array( $offset, $max ) ) as $word )
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
			
			/* Settings */
			foreach ( array( 'nexus_invoice_header', 'nexus_invoice_footer' ) as $k )
			{
				$newMessage = FALSE;
				try
				{
					if( !empty( Settings::i()->$k ) )
					{
						$newMessage = $callback( Settings::i()->$k );
					}
				}
				catch( InvalidArgumentException $e )
				{
					if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
					{
						$newMessage	= preg_replace( "#\[/?([^\]]+?)\]#", '', Settings::i()->$k );
					}
					else
					{
						throw $e;
					}
				}
	
				if( $newMessage !== FALSE )
				{
					Settings::i()->changeValues( array( $k => $newMessage ) );
				}
			}
		}

		/* Now do packages */
		$did	= 0;

		foreach( Db::i()->select( '*', 'nexus_packages', null, 'p_id ASC', array( $offset, $max ) ) as $package )
		{
			$did++;
			$rebuilt = FALSE;

			/* Update */
			try
			{
				if( is_string( $package['p_page'] ) AND $callback[1] == 'parseStatic' )
				{
					$rebuilt = $callback( $package['p_page'], NULL, FALSE, 'nexus_Admin', $package['p_id'], NULL, 'pkg-pg' );
				}
				elseif( is_string( $package['p_page'] ) )
				{
					$rebuilt = $callback( $package['p_page'] );
				}
			}
			catch( InvalidArgumentException $e )
			{
				if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
				{
					$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $package['p_page'] );
				}
				else
				{
					throw $e;
				}
			}

			if( $rebuilt !== FALSE )
			{
				Db::i()->update( 'nexus_packages', array( 'p_page' => $rebuilt ), array( 'p_id=?', $package['p_id'] ) );
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
		$count	= 2;

		$count	+= Db::i()->select( 'COUNT(*) as count', 'core_sys_lang_words', Db::i()->in( 'word_key', array( 'nexus_com_rules_val', 'network_status_text_val' ) ) . " OR word_key LIKE 'nexus_donategoal_%_desc' OR word_key LIKE 'nexus_gateway_%_ins' OR word_key LIKE 'nexus_pgroup_%_desc' OR word_key LIKE 'nexus_package_%_desc' OR word_key LIKE 'nexus_package_%_page' OR word_key LIKE 'nexus_department_%_desc'" )->first();

		$count	+= Db::i()->select( 'COUNT(*) as count', 'nexus_packages' )->first();

		return $count;
	}
}