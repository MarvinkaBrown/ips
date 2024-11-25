<?php
/**
 * @brief		ACP Member Profile: Notes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Dec 2017
 */

namespace IPS\nexus\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\Block;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Notes
 */
class Notes extends Block
{
	/**
	 * @brief	Notes
	 */
	protected ?Db $notes = NULL;
	
	/**
	 * @brief	Note Count
	 */
	protected ?int $noteCount = NULL;
	
	/**
	 * Constructor
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		parent::__construct( $member );
		
		$this->notes = NULL;
		$this->noteCount = 0;
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_view' ) )
		{
			$this->noteCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_notes', array( 'note_member=?', $this->member->member_id ) )->first();
			
			$this->notes = new Db( 'nexus_notes', $this->member->acpUrl()->setQueryString( array( 'view' => 'notes' ) ), array( 'note_member=?', $this->member->member_id ) );
			$this->notes->tableTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'notes' );
			$this->notes->sortBy = 'note_date';
			
			$this->notes->parsers = array(
				'note_member'	=> function( $val )
				{
					return Member::load( $val );
				},
				'note_text'		=> function( $val )
				{
					return $val;
				}
			);
			
			$this->notes->rowButtons = function( $row )
			{
				$return = array();
				if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_edit' ) )
				{
					$return['edit'] = array(
						'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'noteForm', 'note_id' => $row['note_id'] ) ),
						'title'	=> 'edit',
						'icon'	=> 'pencil',
						'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit_note') )
					);
				}
				if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_delete' ) )
				{
					$return['delete'] = array(
						'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'deleteNote', 'note_id' => $row['note_id'] ) )->csrf(),
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'data'	=> array( 'confirm' => '' )
					);
				}
				return $return;
			};
			
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_add' ) )
			{
				$this->notes->rootButtons = array(
					'add'	=> array(
						'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'noteForm' ) ),
						'title'	=> 'add',
						'icon'	=> 'plus',
						'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add_note') )
					)
				);
			}
		}
	}
	
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$this->notes->limit = 2;
		$this->notes->tableTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'notesOverview' );
		$this->notes->rowsTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'notesOverviewRows' );
		
		return (string) Theme::i()->getTemplate( 'customers', 'nexus' )->notesBlock( $this->member, $this->noteCount, $this->notes );
	}
	
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function lazyOutput(): string
	{
		return (string) Theme::i()->getTemplate( 'customers', 'nexus' )->customerPopup( $this->notes );
	}
}