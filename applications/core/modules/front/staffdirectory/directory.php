<?php
/**
 * @brief		Staff directory
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Sep 2013
 */

namespace IPS\core\modules\front\staffdirectory;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\StaffDirectory\Group;
use IPS\core\StaffDirectory\User;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Staff directory
 */
class directory extends Controller
{

	/**
	 * @brief These properties are used to specify datalayer context properties.
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'staff', 'odkUpdate' => 'true']
	);

	/**
	 * Main execute entry point - used to override breadcrumb
	 *
	 * @return void
	 */
	public function execute() : void
	{
		Output::i()->breadcrumb['module'] = array( Url::internal( 'app=core&module=staffdirectory&controller=directory', 'front', 'staffdirectory' ), Member::loggedIn()->language()->addToStack('staff') );

		parent::execute();
	}

	/**
	 * Show staff directory
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$groups = Group::roots();

		try
		{
			User::load( Member::loggedIn()->member_id, 'leader_type_id', array( 'leader_type=?', 'm' ) );
			$userIsStaff	= TRUE;
		}
		catch( OutOfRangeException $e )
		{
			$userIsStaff	= FALSE;
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('staff_directory');
		Output::i()->output	= Theme::i()->getTemplate( 'staffdirectory' )->template( $groups, $userIsStaff );
	}

	/**
	 * Edit your own information
	 *
	 * @return	void
	 */
	protected function form() : void
	{
		try
		{
			$user = User::load( Member::loggedIn()->member_id, 'leader_type_id', array( 'leader_type=?', 'm' ) );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C156/1', 404, '' );
		}

		$form	= new Form;
		$form->add( new Text( 'leader_custom_name', ( Member::loggedIn()->language()->checkKeyExists("core_staff_directory_name_{$user->id}") ) ? Member::loggedIn()->language()->get("core_staff_directory_name_{$user->id}") : Member::loggedIn()->name, FALSE ) );
		$form->add( new Text( 'leader_custom_title', ( Member::loggedIn()->language()->checkKeyExists("core_staff_directory_title_{$user->id}") ) ? Member::loggedIn()->language()->get("core_staff_directory_title_{$user->id}") : '', FALSE ) );
		$form->add( new Editor( 'leader_custom_bio', Member::loggedIn()->language()->addToStack("core_staff_directory_bio_{$user->id}"), FALSE, array(
				'app'			=> 'core',
				'key'			=> 'Staffdirectory',
				'autoSaveKey'	=> 'leader-' . $user->id,
				'attachIds'		=> array( $user->id )
		) ) );

		if( $values = $form->values() )
		{
			/* Save */
			Lang::saveCustom( 'core', "core_staff_directory_name_{$user->id}", $values['leader_custom_name'] );
			Lang::saveCustom( 'core', "core_staff_directory_title_{$user->id}", $values['leader_custom_title'] );
			Lang::saveCustom( 'core', "core_staff_directory_bio_{$user->id}", $values['leader_custom_bio'] );

			//$user->save();

			/* Redirect */
			Output::i()->redirect( Url::internal( "app=core&module=staffdirectory&controller=directory", 'front', 'staffdirectory' ), 'saved' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('leader_edit_mine');
		Output::i()->output	= Theme::i()->getTemplate( 'global' )->genericBlock( $form, '', 'i-padding_3' );
	}
}