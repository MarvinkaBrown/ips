<?php
/**
 * @brief		Member Listener
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		22 May 2023
 */

namespace IPS\cms\listeners;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Events\ListenerType\MemberListenerType;
use IPS\Member;
use Exception;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Listener
 */
class Databases extends MemberListenerType
{
	/**
	 * Member is merged with another member
	 *
	 * @param	Member	$member		Member being kept
	 * @param	Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( Member $member, Member $member2 ) : void
	{
		Db::i()->update( 'cms_database_revisions', array( 'revision_member_id' => $member->member_id ), array( 'revision_member_id=?', $member2->member_id ) );

		try
		{
			foreach ( Db::i()->select( 'database_id', 'cms_databases' ) as $id )
			{
				Db::i()->update( 'cms_custom_database_' . $id, array( 'record_last_comment_by' => $member->member_id, 'record_last_comment_name' => $member->name ), array( 'record_last_comment_by=?', $member2->member_id ) );
				Db::i()->update( 'cms_custom_database_' . $id, array( 'record_last_review_by' => $member->member_id, 'record_last_review_name' => $member->name ), array( 'record_last_review_by=?', $member2->member_id ) );
				Db::i()->update( 'cms_custom_database_' . $id, array( 'record_edit_member_id' => $member->member_id, 'record_edit_member_name' => $member->name ), array( 'record_edit_member_id=?', $member2->member_id ) );
			}
		}
		catch ( Exception $e ) {} // If you have not upgraded pages but it is installed, this throws an error

		/* Update member fields in the db */
		foreach( Db::i()->select( '*', 'cms_database_fields', array( 'field_type=?', 'Member' ) ) as $field )
		{
			$where = array(
				Db::i()->findInSet( 'field_' . $field['field_id'], array( $member2->member_id ) )
			);

			foreach ( Db::i()->select( '*', 'cms_custom_database_' . $field['field_database_id'], $where ) as $record )
			{
				$members = explode( ',', $record['field_' . $field['field_id'] ] );

				foreach( $members as $k => $_member )
				{
					if( $_member == $member2->member_id )
					{
						$members[ $k ] = $member->member_id;
					}
				}

				Db::i()->update( 'cms_custom_database_' . $field['field_database_id'], array( 'field_' . $field['field_id'] => implode( ',', $members ) ), array( 'primary_id_field=?', $record['primary_id_field'] ) );
			}
		}
	}

	/**
	 * Member is deleted
	 *
	 * @param	$member	Member	The member
	 * @return	void
	 */
	public function onDelete( Member $member ) : void
	{
		Db::i()->update( 'cms_database_revisions', array( 'revision_member_id' => 0 ), array( 'revision_member_id=?', $member->member_id ) );

		try
		{
			foreach ( Db::i()->select( 'database_id', 'cms_databases' ) as $id )
			{
				Db::i()->update( 'cms_custom_database_' . $id, array( 'record_last_comment_by' => 0 ), array( 'record_last_comment_by=?', $member->member_id ) );
				Db::i()->update( 'cms_custom_database_' . $id, array( 'record_last_review_by' => 0 ), array( 'record_last_review_by=?', $member->member_id ) );
				Db::i()->update( 'cms_custom_database_' . $id, array( 'record_edit_member_id' => 0 ), array( 'record_edit_member_id=?', $member->member_id ) );
			}
		}
		catch ( Exception $e ) {} // If you have not upgraded pages but it is installed, this throws an error

		/* Update member fields in the db */
		try
		{
			foreach( Db::i()->select( '*', 'cms_database_fields', array( 'field_type=?', 'Member' ) ) as $field )
			{
				$where = array(
					Db::i()->findInSet( 'field_' . $field['field_id'], array( $member->member_id ) )
				);

				foreach ( Db::i()->select( '*', 'cms_custom_database_' . $field['field_database_id'], $where ) as $record )
				{
					$members = explode( ',', $record['field_' . $field['field_id'] ] );

					foreach( $members as $k => $_member )
					{
						if( $_member == $member->member_id )
						{
							unset( $members[ $k ] );
						}
					}

					Db::i()->update( 'cms_custom_database_' . $field['field_database_id'], array( 'field_' . $field['field_id'] => implode( ',', $members ) ), array( 'primary_id_field=?', $record['primary_id_field'] ) );
				}
			}
		}
		catch ( Exception $e ) {} // If you have not upgraded pages but it is installed, this throws an error
	}
}