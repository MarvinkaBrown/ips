<?php
/**
 * @brief		privacy
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Mar 2023
 */

namespace IPS\core\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\PrivacyAction;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * privacy
 */
class privacy extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'privacy_manage' );
		Output::i()->sidebar['actions']['settings'] = array(
			'icon'		=> 'cog',
			'title'		=> 'settings',
			'link'		=> $this->url->setQueryString( 'do', 'settings' ),
			'data'	=>  array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
		);
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Some advanced search links may bring us here */
		Output::i()->bypassCsrfKeyCheck = true;

		/* Create the table */
		$where = [ [ Db::i()->in('action', [ PrivacyAction::TYPE_REQUEST_DELETE, PrivacyAction::TYPE_REQUEST_PII] ) ], ['approved=?', 0] ];
		$table = new TableDb( 'core_member_privacy_actions', Url::internal( 'app=core&module=members&controller=privacy' ), $where );
		$table->include = [ 'photo', 'name', 'email', 'joined','action','request_date' ];

		$table->filters = [
			'deletion_request'			=> ['action=?', PrivacyAction::TYPE_REQUEST_DELETE],
			'pii_data'					=> ['action=?', PrivacyAction::TYPE_REQUEST_PII],
		];

		$table->joins = [
			[ 'select' => 'm.*', 'from' => [ 'core_members', 'm' ], 'where' => 'core_member_privacy_actions.member_id=m.member_id' ]
		];

		$table->parsers = [
			'photo'				=> function( $val, $row )
			{
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( Member::constructFromData( $row ), 'tiny' );
			},
			'name' => function( $val, $row ){
				if( $val )
				{
					$member = Member::constructFromData( $row );

					if( $banned = $member->isBanned() )
					{
						if( $banned instanceof DateTime )
						{
							$title = Member::loggedIn()->language()->addToStack( 'suspended_until', FALSE, array( 'sprintf' => array( $banned->localeDate() ) ) );
						}else
						{
							$title = Member::loggedIn()->language()->addToStack( 'banned' );
						}
						return "<a href='". Url::internal( 'app=core&module=members&controller=members&do=view&id=' ).$row[ 'member_id' ]."'>".htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE )."</a> &nbsp; <span class='ipsBadge ipsBadge--negative'>".$title.'</span> ';
					}else
					{
						return "<a href='". Url::internal( 'app=core&module=members&controller=members&do=view&id=' ).$row[ 'member_id' ]."'>".htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ).'</a>';
					}
				}
				else
				{
					return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberReserved( Member::constructFromData( $row ) );
				}
			},
			'joined'			=> function( $val )
			{
				return DateTime::ts( $val )->localeDate();
			},
			'request_date'			=> function( $val )
			{
				return DateTime::ts( $val )->localeDate();
			},
			'action' 			=> function( $val )
			{
				switch ( $val )
				{
					case PrivacyAction::TYPE_REQUEST_PII:
						return Member::loggedIn()->language()->addToStack('pii_download_requested');
					case PrivacyAction::TYPE_REQUEST_DELETE:
						return Member::loggedIn()->language()->addToStack('account_deletion_requested');
				}
				return '';
			}
		];

		$table->rowButtons = function( $row )
		{
			$return = [];

			if( $row['action'] == PrivacyAction::TYPE_REQUEST_PII )
			{
				$return[ 'approve' ] = [
					'title' => 'approve',
					'icon' => 'check-circle',
					'link' => Url::internal( 'app=core&module=members&controller=privacy&do=approvePii&id='.$row[ 'id' ] )->csrf()->getSafeUrlFromFilters(),
				];
				$return['reject'] = [
					'icon'		=> 'times',
					'title'		=> 'reject',
					'link'		=> Url::internal( 'app=core&module=members&controller=privacy&do=rejectPii&id=' . $row['id'] )->csrf()->getSafeUrlFromFilters(),
				];
			}
			else if( $row['action'] == PrivacyAction::TYPE_REQUEST_DELETE )
			{
				$member = Member::constructFromData( $row );
				
				$return[ 'approveDeletion' ] = [
					'title' => 'approve',
					'icon' => 'check-circle',
					'link' => Url::internal( 'app=core&module=members&controller=privacy&do=approveDeletion&id='.$row[ 'id' ] )->csrf()->getSafeUrlFromFilters(),
					'data' => [ 'confirm' => '', 'confirmSubMessage' => Member::loggedIn()->language()->addToStack( 'delete_member_confirm', FALSE, array( 'sprintf' => $member->name ) ) ]
				];
				$return['rejectDeletion'] = [
					'icon'		=> 'times',
					'title'		=> 'reject',
					'link'		=> Url::internal( 'app=core&module=members&controller=privacy&do=rejectDeletion&id=' . $row['id'] )->csrf()->getSafeUrlFromFilters(),
					'data'	=> array(

					)
				];
			}

			
			return $return;
		};
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'menu__core_members_privacy');
		Output::i()->output	= Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
	}

	/**
	 * Approve PII Request
	 *
	 * @return void
	 */
	protected function approvePii() : void
	{
		Session::i()->csrfCheck();
		try
		{
			$request = PrivacyAction::load( Request::i()->id );
			$request->approvePiiRequest();
			Session::i()->log( 'acplog__piirequest_approved', array( $request->member->name => FALSE ) );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C432/1', 404, '' );
		}
		Output::i()->redirect( Url::internal( 'app=core&module=members&controller=privacy' )->getSafeUrlFromFilters(), 'approved' );
	}

	/**
	 * Reject PII Request
	 *
	 * @return void
	 */
	protected function rejectPii() : void
	{
		Session::i()->csrfCheck();
		try
		{
			$request = PrivacyAction::load( Request::i()->id );
			$request->rejectPiiRequest();
			Session::i()->log( 'acplog__piirequest_rejected', array( $request->member->name => FALSE ) );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C432/2', 404, '' );
		}
		Output::i()->redirect( Url::internal( 'app=core&module=members&controller=privacy' )->getSafeUrlFromFilters(), 'pii_request_rejected' );
	}

	/**
	 * Approve deletion request
	 *
	 * @return void
	 */
	protected function approveDeletion() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$request = PrivacyAction::load( Request::i()->id );

			Request::i()->confirmedDelete( message: Member::loggedIn()->language()->addToStack( 'delete_member_confirm', FALSE, array( 'sprintf' => $request->member->name ) ) );

			$request->deleteAccount();
			Session::i()->log( 'acplog__deletionrequest__approved', array( $request->member->name => FALSE ) );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C432/3', 404, '' );
		}
		Output::i()->redirect( Url::internal( 'app=core&module=members&controller=privacy' )->getSafeUrlFromFilters(), 'approved' );
	}

	/**
	 * Reject deletion request
	 *
	 * @return void
	 */
	protected function rejectDeletion() : void
	{
		Session::i()->csrfCheck();
		try
		{
			$request = PrivacyAction::load( Request::i()->id );
			$request->rejectDeletionRequest();
			Session::i()->log( 'acplog__deletionrequest__rejected', array( $request->member->name => FALSE ) );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C432/4', 404, '' );
		}
		Output::i()->redirect( Url::internal( 'app=core&module=members&controller=privacy' )->getSafeUrlFromFilters(), 'rejected' );
	}

	protected function settings() : void
	{
		$form = new Form();
		$form->add( new Radio( 'pii_type', Settings::i()->pii_type, FALSE, array(
			'options' => array(
				'off' => 'disabled',
				'on' => 'enabled',
				'redirect' => "pii_external" ),
			'toggles' => array(
				'off'	=> array( '' ),
				'redirect'	=> array( 'pii_link' ),
				'on'		=> array(),
			)
		) ) );

		$form->add( new FormUrl( 'pii_link', Settings::i()->pii_link, FALSE, array(), NULL, NULL, NULL, 'pii_link'  ) );


		$form->add( new Radio( 'right_to_be_forgotten_type', Settings::i()->right_to_be_forgotten_type, FALSE, array(
			'options' => array(
				'off' => 'disabled',
				'on' => 'enabled',
				'redirect' => "right_to_be_forgotten_external" ),
			'toggles' => array(
				'off'	=> array( '' ),
				'redirect'	=> array( 'right_to_be_forgotten_link' ),
				'on'		=> array(),
			)
		) ) );
		$form->add( new FormUrl( 'right_to_be_forgotten_link', Settings::i()->right_to_be_forgotten_link, FALSE, array(), NULL, NULL, NULL, 'right_to_be_forgotten_link'  ) );


		if( $values = $form->values() )
		{
			$form->saveAsSettings();
			Output::i()->redirect( Url::internal( 'app=core&module=members&controller=privacy' ), 'saved' );
		}
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'settings');
		Output::i()->output	= Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $form );
	}
}