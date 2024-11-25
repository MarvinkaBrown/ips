<?php
/**
 * @brief		Ignore Preferences
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Aug 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\core\Ignore as IgnoreClass;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Ignore Preferences
 */
class ignore extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C146/1', 403, '' );
		}

		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_ignore.js', 'core' ) );

		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$url = Url::internal( "app=core&module=system&controller=ignore", 'front', 'ignore' );
		
		/* Build form */
		$form =  IgnoreClass::form();
		if ( $values = $form->values() )
		{
			Session::i()->csrfCheck();

			try
			{
				 IgnoreClass::createFromForm( $values );
			}
			catch( InvalidArgumentException $e )
			{
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( array( 'error' => Member::loggedIn()->language()->addToStack( 'cannot_ignore_self' ) ), 403 );
				}
				else
				{
					Output::i()->error( Member::loggedIn()->language()->addToStack( $e->getMessage() ), '1C146/2', 403, '' );
				}
			}
				
			if ( Request::i()->isAjax() )
			{
				$data = array();
				foreach(  IgnoreClass::types() AS $type )
				{
					if ( $values["ignore_{$type}"] )
					{
						$data["ignore_{$type}"] = 1;
					}
				}
				
				Output::i()->json( array( 'name' => $values['member']->name, 'member_id' => $values['member']->member_id, 'data' => $data ) );
			}
			else
			{
				Output::i()->redirect( $url );
			}
		}
		
		/* Build table */
		$table = new Db( 'core_ignored_users', $url, array( array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) ) );
		$table->title = 'ignored_users_current';
		$table->langPrefix = 'ignore_';
		$table->tableTemplate = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'ignoreTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'system' ), 'ignoreTableRows' );
		$filters = array();
		foreach (  IgnoreClass::types() as $type )
		{
			$filters[ $type ] = "ignore_{$type}=1";
		}
		$table->filters = $filters;
				
		/* Display */
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('ignored_users') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('ignored_users');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->ignore( $form->customTemplate( array( Theme::i()->getTemplate( 'system' ), 'ignoreForm' ) ), (string) $table, ( isset( Request::i()->id ) ? Request::i()->id : 0 ) );
	}
	
	/**
	 * Add new ignored user
	 *
	 * @return	void
	 */
	protected function add() : void
	{
		Session::i()->csrfCheck();

		try
		{
			/* We have to html_entity_decode because the javascript code sends the encoded name here */
			$member = Member::load( html_entity_decode( Request::i()->name, ENT_QUOTES, 'UTF-8' ), 'name' );
			
			/* If \IPS\Member::load() cannot find a member, it just creates a new guest object, never throwing the exception */
			if ( !$member->member_id )
			{
				throw new OutOfRangeException( 'cannot_ignore_no_user' );
			}
			
			if ( $member->member_id == Member::loggedIn()->member_id )
			{
				throw new InvalidArgumentException( 'cannot_ignore_self' );
			}

			if ( !$member->canBeIgnored() )
			{
				throw new InvalidArgumentException( 'cannot_ignore_that_member' );
			}
			
			$ignore = NULL;
			try
			{
				$ignore =  IgnoreClass::load( $member->member_id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
			}
			catch ( OutOfRangeException $e ) {}
			
			$data = array();
			foreach (  IgnoreClass::types() as $t )
			{
				$data[ $t ] = $ignore ? $ignore->$t : FALSE;
			}

			Output::i()->json( $data );
		}
		catch( Exception $e )
		{
			Output::i()->json( array( 'error' => Member::loggedIn()->language()->addToStack( $e->getMessage() ) ), 403 );
		}
	}

	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		try
		{
			$member = Member::load( Request::i()->id );
			
			/* If \IPS\Member::load() cannot find a member, it just creates a new guest object, never throwing the exception */
			if ( !$member->member_id )
			{
				throw new OutOfRangeException( 'cannot_ignore_no_user' );
			}
			
			if ( $member->member_id == Member::loggedIn()->member_id )
			{
				throw new InvalidArgumentException( 'cannot_ignore_self' );
			}
			
			if ( !$member->canBeIgnored() )
			{
				throw new InvalidArgumentException( 'cannot_ignore_that_member' );
			}
			
			$ignore = NULL;
			try
			{
				$ignore =  IgnoreClass::load( $member->member_id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
			}
			catch ( OutOfRangeException $e ) {}
			
			$form = new Form( NULL, 'ignore_edit' );
			$form->class = 'ipsForm--vertical ipsForm--edit-ignore';
			
			foreach (  IgnoreClass::types() as $type )
			{
				$form->add( new Checkbox( "ignore_{$type}", $ignore ? $ignore->$type : FALSE ) );
			}

			/* Save values */
			if( $values = $form->values() )
			{
				foreach (  IgnoreClass::types() as $type )
				{
					$ignore->$type = $values["ignore_{$type}"];
				}

				$ignore->save();

				if( !Request::i()->isAjax() )
				{
					$this->manage();
				}
				else
				{
					Output::i()->json( 'OK' );
				}
			}
			
			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('ignored_users') );
			Output::i()->title = Member::loggedIn()->language()->addToStack('add_ignored_user');
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'system' ), 'ignoreEditForm' ) );
		}
		catch( Exception $e )
		{
			Output::i()->json( Member::loggedIn()->language()->addToStack( $e->getMessage() ), 403 );
		}
	}

	/**
	 * Remove (AJAX)
	 *
	 * @return	void
	 */
	protected function remove() : void
	{
		Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$member = Member::load( Request::i()->id );
			
			/* If \IPS\Member::load() cannot find a member, it just creates a new guest object, never throwing the exception */
			if ( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
			
			$ignore =  IgnoreClass::load( $member->member_id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
			$ignore->delete();
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'results' => 'ok', 'name' => $member->name ) );
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=system&controller=ignore", 'front', 'ignore' ), Member::loggedIn()->language()->addToStack( 'ignore_removed', FALSE, array( 'sprintf' => array( $member->name ) ) ) );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C146/5', 404, '' );
		}
	}
	
	/**
	 * Ignore Specific Content Type (AJAX)
	 *
	 * @return	void
	 */
	protected function ignoreType() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			if ( !in_array( Request::i()->type,  IgnoreClass::types() ) )
			{
				throw new OutOfRangeException( 'invalid_type' );
			}
			
			$member	= Member::load( Request::i()->member_id );
			
			if ( !$member->member_id )
			{
				throw new OutOfRangeException( 'cannot_ignore_no_user' );
			}
			
			if ( $member->member_id == Member::loggedIn()->member_id )
			{
				throw new InvalidArgumentException( 'cannot_ignore_self' );
			}
			
			if ( !$member->canBeIgnored() )
			{
				throw new InvalidArgumentException( 'cannot_ignore_that_member' );
			}
			
			$type = Request::i()->type;
			$value = !isset( Request::i()->off );
			
			try
			{
				$ignore =  IgnoreClass::load( $member->member_id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
				$ignore->$type = $value;
				$ignore->save();
			}
			catch( OutOfRangeException $e )
			{
				$ignore = new IgnoreClass;
				$ignore->$type = $value;
				$ignore->owner_id	= Member::loggedIn()->member_id;
				$ignore->ignore_id	= $member->member_id;
				$ignore->save();
			}
						
			Member::loggedIn()->members_bitoptions['has_no_ignored_users'] = FALSE;
			Member::loggedIn()->save();
						
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'result' => 'ok' ) );
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=system&controller=ignore", 'front', 'ignore' ), 'ignore_adjusted' );
			}
		}

		catch( Exception $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'error' => $e->getMessage() ), 403 );
			}
			else
			{
				Output::i()->error( $e->getMessage(), '1C146/4', 403, '' );
			}
		}
	}
}