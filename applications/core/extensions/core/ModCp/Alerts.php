<?php
/**
 * @brief		Moderator Control Panel Extension: Alerts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		12 May 2022
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Alerts\Alert;
use IPS\Extensions\ModCpAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Moderator Control Panel Extension: Alerts
 */
class Alerts extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string|null
	 */
	public function getTab() : ?string
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_manage_alerts') )
		{
			return null;
		}

		return 'alerts';
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
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_manage_alerts') )
		{
			Output::i()->error( 'no_module_permission', '2C427/1', 403, '' );
		}

		$table = new Content( '\IPS\core\Alerts\Alert', Url::internal( 'app=core&module=modcp&controller=modcp&tab=alerts' ) );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'modcp', 'core', 'front' ), 'alertRow' );
		$table->include = array( 'alert_title' );
		$table->mainColumn = 'alert_title';
		$table->sortBy = 'alert_id';
		$table->sortDirection = 'desc';
		$table->sortOptions = array( 'alert_id' );

		/* Filters */
		$table->filters = array(
			'alert_filter_active' => [ 'alert_enabled=1' ],
			'alert_filter_inactive' => [ 'alert_enabled=0' ],
			'alert_filter_viewed'	=> [ 'alert_viewed > 0 ' ],
			'alert_filter_not_viewed'	=> [ 'alert_viewed = 0 ' ],
			'alert_filter_groups'	=> [ "alert_recipient_type='group'"],
			'alert_filter_user'		=> [ "alert_recipient_type='user'"]
		);

		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'modcp_alerts' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'modcp_alerts' );
		Output::i()->output = Theme::i()->getTemplate( 'modcp' )->alerts( (string) $table );
	}

	/**
	 * Add/Edit Alert
	 *
	 * @return	void
	 */
	public function create() : void
	{
		$current = NULL;
		if ( Request::i()->id )
		{
			$current = Alert::load( Request::i()->id );
		}

		$form = Alert::form( $current );
		$form->class = 'ipsForm--vertical ipsForm--edit-alert';

		if ( $values = $form->values() )
		{
			$alert = Alert::_createFromForm( $values, $current );

			Output::i()->redirect( Url::internal( "app=core&module=modcp&tab=alerts", 'front', 'modcp_alerts' ) );
		}

		if ( !is_null( $current ) )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'edit_alert' );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'add_alert' );
		}

		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=modcp&controller=modcp&tab=alerts' ), Member::loggedIn()->language()->addToStack( 'modcp_alerts' ) );
		Output::i()->breadcrumb[] = array( NULL, ( !is_null( $current ) ) ? Member::loggedIn()->language()->addToStack( 'edit_alert' ) : Member::loggedIn()->language()->addToStack( 'add_alert' ) );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Delete Alert
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		if ( !Member::loggedIn()->modPermission( 'can_manage_alerts' ) )
		{
			Output::i()->error( 'no_module_permission', '2C427/2', 403, '' );
		}

		Session::i()->csrfCheck();

		try
		{
			$alert = Alert::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C427/3', 404, '' );
		}

		$alert->delete();

		Output::i()->redirect( Url::internal( "app=core&module=modcp&tab=alerts", 'front', 'modcp_alerts' ) );
	}

	/**
	 * Change Author
	 *
	 * @return	void
	 */
	public function changeAuthor() : void
	{
		if ( !Member::loggedIn()->modPermission( 'can_manage_alerts' ) )
		{
			Output::i()->error( 'no_module_permission', '2C427/4', 403, '' );
		}

		try
		{
			$alert = Alert::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C427/5', 404, '' );
		}

		/* Build form */
		$form = new Form;
		$form->add( new FormMember( 'author', $alert->author(), TRUE ) );
		$form->class .= 'ipsForm--vertical ipsForm--change-author';

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$alert->changeAuthor( $values['author'] );
			Output::i()->redirect( Url::internal( "app=core&module=modcp&tab=alerts", 'front', 'modcp_alerts' ) );
		}

		/* Display form */
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Change Alert Status
	 *
	 * @return	void
	 */
	public function status() : void
	{
		if ( !Member::loggedIn()->modPermission( 'can_manage_alerts' ) )
		{
			Output::i()->error( 'no_module_permission', '2C427/6', 403, '' );
		}

		Session::i()->csrfCheck();

		try
		{
			$alert = Alert::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C427/7', 404, '' );
		}

		$alert->enabled = ( $alert->enabled === 1 ? 0 : 1 );
		$alert->save();

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'OK' ) );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=modcp&tab=alerts", 'front', 'modcp_alerts' ) );
		}
	}
}