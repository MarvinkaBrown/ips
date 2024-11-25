<?php
/**
 * @brief		Moderator Control Panel Extension: Member Management
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Oct 2013
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\ModCpAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member Management
 */
class Members extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string|null
	 */
	public function getTab() : ?string
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_modify_profiles') )
		{
			return null;
		}
		
		return 'members';
	}

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return	string
	 */
	public function manageType() : string
	{
		return 'members';
	}
	
	/**
	 * Get content to display
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_modify_profiles') )
		{
			Output::i()->error( 'no_module_permission', '2C228/1', 403, '' );
		}
		
		/* Which filter? */
		$area = Request::i()->area ?: 'banned';
		
		/* Member search form */
		$form = new Form( 'form', 'edit' );
	
		$form->class = 'ipsForm--vertical ipsForm--modcp-members';
		$form->add( new FormMember( 'modcp_member_find', NULL, TRUE, array( 'multiple' => 1, 'placeholder' => Member::loggedIn()->language()->addToStack('modcp_member_find'), 'autocomplete' => array(
				'source' => 'app=core&module=system&controller=ajax&do=findMember&type=mod', 'resultItemTemplate' 	=> 'core.autocomplete.memberItem',
				'commaTrigger'			=> false,
				'unique'				=> true,
				'minAjaxLength'			=> 3,
				'disallowedCharacters'  => array(),
				'lang'					=> 'mem_optional', 
		) ) ) );
		
		if ( $values = $form->values() )
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=profile&do=edit&id={$values['modcp_member_find']->member_id}", 'front', 'edit_profile', array( $values['modcp_member_find']->members_seo_name ) ) );
		}
		
		/* Load the extensions */
		$tabs = array();
		foreach ( Application::allExtensions( 'core', 'ModCpMemberManagement', TRUE, 'core', 'Banned' ) as $key => $extension )
		{
			$tab = $extension->getTab();

			if ( $tab )
			{
				$tabs[ $tab ][] = $key;
				$exploded = explode( "_", $key );
				if( mb_strtolower( $key ) == $exploded[0] . "_" . mb_strtolower( $area ) )
				{
					$content = $extension->manage();
				}
			}
		}

		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $content;
		}
		else
		{
			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'modcp_members' ) );
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'modcp_members' );
			Output::i()->output = Theme::i()->getTemplate( 'modcp' )->members( $content, $tabs, $area, $form );
		}
	}
}