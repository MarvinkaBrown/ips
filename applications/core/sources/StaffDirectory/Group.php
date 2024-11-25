<?php
/**
 * @brief		Staff Directory Group Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Sep 2013
 */

namespace IPS\core\StaffDirectory;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Staff Directory Group Node
 */
class Group extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_leaders_groups';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'group_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'staff_directory';
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static ?string $subnodeClass = 'IPS\core\StaffDirectory\User';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'staff',
		'prefix'	=> 'leaders_',
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'core_staffgroups_';
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		/* Title field */
		$form->add( new Translatable( 'staff_group_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? "core_staffgroups_{$this->id}" : NULL ) ) ) );
		
		/* Build the layout selection radios */
		$templates = array();

		foreach ( [ 'layout_blocks','layout_full','layout_half' ] as $template )
		{
				$realTemplate = $template . '_preview';
				$templates[ $template ] = Theme::i()->getTemplate( 'staffdirectory', 'core', 'front' )->$realTemplate( );

		}
	
		$form->add( new Radio( 'staff_group_template', $this->id ? $this->template : NULL, TRUE, array( 'options' => $templates, 'parse' => 'none' ) ) );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( !$this->id )
		{
			$this->save();
		}

		if( isset( $values['staff_group_title'] ) )
		{
			Lang::saveCustom( 'core', "core_staffgroups_{$this->id}", $values['staff_group_title'] );
			unset( $values['staff_group_title'] );
		}

		if( isset( $values['staff_group_template'] ) )
		{
			$values['template']	= $values['staff_group_template'];
			unset( $values['staff_group_template'] );
		}

		return $values;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	* array(
	 		* array(
	 			* 'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 			* 'title'	=> 'foo',		// Language key to use for button's title parameter
	 			* 'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			* 'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		* ),
	 		* ...							// Additional buttons
	 	* );
	 * @endcode
	 * @param Url $url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ):array
	{
		$buttons = parent::getButtons( $url, $subnode );
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['title'] = 'staff_add_record';
		}
		
		return $buttons;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		return false;
	}

	/**
	 * Get members
	 *
	 * @return	array
	 */
	public function members() : array
	{
		$members = array();
		foreach ( $this->children() as $child )
		{
			if ( $child->type === 'm' )
			{
				$members[ $child->type_id ] = $child;
			}
			else
			{
				$memberIds = array();
				foreach ( Db::i()->select( 'member_id', 'core_members', array( 'member_group_id=? OR FIND_IN_SET( ?, mgroup_others )', $child->type_id, $child->type_id ), 'name' ) as $memberId )
				{
					$memberIds[] = $memberId;
				}
				
				foreach ( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', $memberIds ) ) ) as $m )
				{
					if ( !isset( $members[ $m['member_id'] ] ) )
					{
						$member = new User;
						$memberObj = Member::constructFromData( $m );
						$member->member = $memberObj;
						$members[ $memberObj->member_id ] = $member;
					}
				}
			}
		}
		
		return $members;
	}
}