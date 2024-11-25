<?php
/**
 * @brief		Edit History Trait for Content Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Oct 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\IPS;
use IPS\Settings;
use IPS\Theme;
use IPS\Member;
use IPS\Db;
use IPS\Db\Select;

use function defined;
use function header;
use function get_called_class;


if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Edit History Trait for Content Models
 */
trait EditHistory
{
	/**
	 * Get edit line
	 *
	 * @return	string|NULL
	 */
	public function editLine(): ?string
	{
		if ( $this->mapped('edit_time') and ( $this->mapped('edit_show') or Member::loggedIn()->modPermission('can_view_editlog') ) and Settings::i()->edit_log )
		{
			/* If anonymous and we can't see, it's a no. */
			if ( ( IPS::classUsesTrait( $this, 'IPS\Content\Anonymous' ) AND $this->isAnonymous() ) AND ! Member::loggedIn()->modPermission('can_view_anonymous_posters') )
			{
				return null;
			}

			$template = static::$editLineTemplate[1];
			return Theme::i()->getTemplate( static::$editLineTemplate[0][0], static::$editLineTemplate[0][1], ( isset( static::$editLineTemplate[0][2] ) ) ? static::$editLineTemplate[0][2] : NULL )->$template( $this, ( isset( static::$databaseColumnMap['edit_reason'] ) and $this->mapped('edit_reason') ) );
		}
		return NULL;
	}

	/**
	 * Get edit history
	 *
	 * @param	bool		$staff		Set true for moderators who have permission to view the full log which will show edits not made by the author and private edits
	 * @param	int|null 	$limit
	 * @return	Select
	 */
	public function editHistory( bool $staff=FALSE, ?int $limit=NULL ): Select
	{
		$idColumn = static::$databaseColumnId;
		$where = array( array( 'class=? AND comment_id=?', get_called_class(), $this->$idColumn ) );
		if ( !$staff )
		{
			$where[] = array( '`member`=? AND public=1', $this->author()->member_id );
		}

		return Db::i()->select( '*', 'core_edit_history', $where, 'time DESC', $limit );
	}
	
	/**
	 * Edit logging
	 *
	 * @param array $values
	 */
	public function logEdit( array $values ): void
	{
		$logEdit = 'log_edit';
		$editReason = 'edit_reason';
		$column = static::$databaseColumnMap['content'];
		$oldContent = $this->$column;
		if ( $this instanceof Comment )
		{
			$logEdit = 'comment_log_edit';
			$editReason = 'comment_edit_reason';
			$column = 'comment_value';
		}
		
		$editIsPublic = Member::loggedIn()->group['g_append_edit'] ? $values[ $logEdit ] : TRUE;
		$idField = static::$databaseColumnId;

		if ( Settings::i()->edit_log == 2 and isset( $values[static::$formLangPrefix . $column] ) )
		{
			$content =  $values[static::$formLangPrefix . $column];

			Db::i()->insert( 'core_edit_history', array(
				'class' => get_class( $this ),
				'comment_id' => $this->$idField,
				'member' => Member::loggedIn()->member_id,
				'time' => time(),
				'old' => $oldContent,
				'new' => $content,
				'public' => $editIsPublic,
				'reason' => $values[$editReason] ?? NULL,
			) );
		}

		if ( isset( static::$databaseColumnMap['edit_reason'] ) and isset( $values[ $editReason ] ) )
		{
			$field = static::$databaseColumnMap['edit_reason'];
			$this->$field = $values[ $editReason ];
		}
		if ( isset( static::$databaseColumnMap['edit_time'] ) )
		{
			$field = static::$databaseColumnMap['edit_time'];
			$this->$field = time();
		}
		if ( isset( static::$databaseColumnMap['edit_member_id'] ) )
		{
			$field = static::$databaseColumnMap['edit_member_id'];
			$this->$field = Member::loggedIn()->member_id;
		}
		if ( isset( static::$databaseColumnMap['edit_member_name'] ) )
		{
			$field = static::$databaseColumnMap['edit_member_name'];
			$this->$field = Member::loggedIn()->name;
		}
		if ( isset( static::$databaseColumnMap['edit_show'] ) and $editIsPublic )
		{
			$field = static::$databaseColumnMap['edit_show'];
			$this->$field = Member::loggedIn()->group['g_append_edit'] ? $values[ $logEdit ] : TRUE;
		}
		else
		{
			if ( isset( static::$databaseColumnMap['edit_show'] ) )
			{
				$field = static::$databaseColumnMap['edit_show'];
				$this->$field = 0;
			}
		}
		$this->save();
	}
}