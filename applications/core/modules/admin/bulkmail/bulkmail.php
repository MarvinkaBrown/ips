<?php
/**
 * @brief		Bulk mail handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Jun 2013
 */

namespace IPS\core\modules\admin\bulkmail;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\BulkMail\Bulkmailer;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Email;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Bulk mail management
 */
class bulkmail extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'bulkmail_manage' );
		
		/* Make sure we have a community outgoing email */
		if ( !Settings::i()->email_out )
		{
			Output::i()->error( 'no_outgoing_address', '1C125/9', 403, '' );
		}
		
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Create the table */
		$table					= new \IPS\Helpers\Table\Db( 'core_bulk_mail', Url::internal( 'app=core&module=bulkmail&controller=bulkmail' ) );
		$table->langPrefix		= 'bulkmail_';
		$table->mainColumn		= 'mail_subject';
		$table->include			= array( 'mail_subject', 'mail_start', 'mail_sentto', 'mail_taken' );
		$table->quickSearch		= 'mail_subject';

		/* Default sort options */
		$table->sortBy			= $table->sortBy ?: 'mail_start';
		$table->sortDirection	= $table->sortDirection ?: 'desc';

		/* Custom parsers */
		$table->parsers			= array(
			'mail_start'	=> function( $val, $row )
			{
				if( !$val )
				{
					return Member::loggedIn()->language()->addToStack('bulkmail_notstarted');
				}

				return DateTime::ts( $val )->localeDate();
			},
			'mail_sentto'	=> function( $val, $row )
			{
				return Member::loggedIn()->language()->addToStack( 'bulkmail_sentto_members', FALSE, array( 'pluralize' => array( (int) $val ) ) );
			},
			'mail_taken'	=> function( $val, $row )
			{
				if( !$row['mail_updated'] )
				{
					return Member::loggedIn()->language()->addToStack('bulkmail_notstarted');
				}

				$started	= DateTime::ts( $row['mail_start'] );
				$updated	= DateTime::ts( $row['mail_updated'] );

				return DateTime::formatInterval( $updated->diff( $started ) );
			}
		);

		/* Specify the buttons */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'bulkmail', 'bulkmail_add' ) )
		{
			Output::i()->sidebar['actions'] = array(
				'add'	=> array(
					'primary'	=> TRUE,
					'icon'		=> 'plus',
					'title'		=> 'bulkmail_add',
					'link'		=> Url::internal( 'app=core&module=bulkmail&controller=bulkmail&do=form' ),
				)
			);
		}

		$table->rowButtons = function( $row )
		{		
			$return = array();

			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'bulkmail', 'bulkmail_edit' ) )
			{
				$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> Url::internal( 'app=core&module=bulkmail&controller=bulkmail&do=form&id=' ) . $row['mail_id'],
				);

				if( $row['mail_active'] )
				{
					$return['cancel'] = array(
						'icon'		=> 'minus-circle',
						'title'		=> 'cancel',
						'link'		=> Url::internal( 'app=core&module=bulkmail&controller=bulkmail&do=cancel&id=' . $row['mail_id'] )->csrf(),
					);
				}
				else
				{
					$return['resend'] = array(
						'icon'		=> 'refresh',
						'title'		=> 'resend',
						'link'		=> Url::internal( 'app=core&module=bulkmail&controller=bulkmail&do=resend&id=' ) . $row['mail_id'],
					);
				}
			}

			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'bulkmail', 'bulkmail_delete' ) )
			{
				$return['delete'] = array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> Url::internal( 'app=core&module=bulkmail&controller=bulkmail&do=delete&id=' ) . $row['mail_id'],
					'data'		=> array( 'delete' => '' ),
				);
			}

			return $return;
		};

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('manage_bulk_mail');
		Output::i()->output	= (string) $table;
	}

	/**
	 * Delete a bulk mail
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		/* Retrieve the bulk mail details for the log */
		try
		{
			$mail	= Bulkmailer::load( (int) Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'couldnt_find_bulkmail', '2C125/7', 404, '' );
		}
		
		/* Unclaim attachments */
		File::unclaimAttachments( 'core_Admin', $mail->id, NULL, 'bulkmail' );
		
		/* Delete the bulk mail */
		$mail->delete();
		
		/* Log and redirect */
		Session::i()->log( 'acplog__bulkmail_deleted', array( $mail->subject => FALSE ) );
		Output::i()->redirect( Url::internal( 'app=core&module=bulkmail&controller=bulkmail' ), 'deleted' );
	}

	/**
	 * Cancel a bulk mail
	 *
	 * @return	void
	 */
	public function cancel() : void
	{
		Session::i()->csrfCheck();
		
		/* Retrieve the bulk mail details for the log */
		try
		{
			$mail	= Bulkmailer::load( (int) Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'couldnt_find_bulkmail', '2C125/8', 404, '' );
		}

		/* Cancel the bulk mail */
		$mail->updated	= time();
		$mail->active	= 0;
		$mail->save();
		
		/* Remove any queue tasks */
		foreach( Db::i()->select( '*', 'core_queue', array( "`key`=?", 'Bulkmail' ) ) AS $task )
		{
			$data = json_decode( $task['data'], TRUE );
			if ( isset( $data['mail_id'] ) AND $data['mail_id'] == $mail->id )
			{
				Db::i()->delete( 'core_queue', array( "id=?", $task['id'] ) );
			}
		}

		/* Log and redirect */
		Session::i()->log( 'acplog__bulkmail_cancelled', array( $mail->subject => FALSE ) );
		Output::i()->redirect( Url::internal( 'app=core&module=bulkmail&controller=bulkmail' ), 'cancelled' );
	}

	/**
	 * Resend a bulk mail
	 *
	 * @return	void
	 * @see		_bulkmail::send()
	 * @note	This method simply redirects to the preview() method
	 */
	public function resend() : void
	{
		$this->preview();
	}

	/**
	 * Begin sending a bulk mail.  This method will display a preview form and allow the administrator to confirm before initiating the bulk mail send process.
	 *
	 * @return	void
	 * @note	The resend() method redirects here.  Additionally, upon successfully saving a bulk mail the user is redirected here.
	 */
	public function preview() : void
	{
		/* Retrieve the bulk mail details */
		try
		{
			$mail	= Bulkmailer::load( (int) Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'couldnt_find_bulkmail', '2C125/1', 404, '' );
		}

		/* Get the members */
		$results	= $mail->getQuery( array( 0, 1000 ) );
		
		/* Get a count of the members */
		$total 		= $mail->getQuery( Bulkmailer::GET_COUNT_ONLY )->first();

		/* Do we have anyone to send to? */
		if( !$total )
		{
			/* Disable the bulk mail - nothing to send */
			if( $mail->active )
			{
				$mail->active	= 0;
				$mail->save();
			}

			Output::i()->error( 'no_members_to_send_to', '1C125/3', 400, '' );
		}

		/* Output */
		Output::i()->title = Member::loggedIn()->language()->addToStack('bm_send_preview');
		Output::i()->output = Theme::i()->getTemplate( 'global' )->message( 'bulkmail_send_info', 'information' );
		Output::i()->output .= Theme::i()->getTemplate( 'members' )->bulkMailPreview( $mail, $results, $total );
	}

	/**
	 * Show a preview of the email that will be sent inside an iframe.  This allows us to use the email template properly.
	 *
	 * @return	void
	 */
	public function iframePreview() : void
	{
		/* Retrieve the bulk mail details */
		try
		{
			$mail	= Bulkmailer::load( (int) Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'couldnt_find_bulkmail', '2C125/2', 404, '' );
		}
		
		/* For the preview we need to display the tags replaced with this member's data */
		$content = $mail->content;
		foreach ( $mail->returnTagValues( 0, Member::loggedIn() ) as $k => $v )
		{
			$content = str_replace( array( $k, urlencode( $k ) ), $v, $content );
		}
		
		/* Display */
		$email	= Email::buildFromContent( $mail->subject, Email::staticParseTextForEmail( $content, Member::loggedIn()->language() ), NULL, Email::TYPE_BULK )->setUnsubscribe( 'core', 'unsubscribeBulk' );

		Output::i()->sendOutput( $email->compileContent( 'html', Member::loggedIn(), NULL, FALSE ) );
	}
	
	/**
	 * Actually send the bulk mail. We end up here once the preview has been confirmed the admin continues.
	 *
	 * @return	void
	 */
	public function send() : void
	{
		Session::i()->csrfCheck();
		
		/* Retrieve the bulk mail details */
		try
		{
			$mail	= Bulkmailer::load( (int) Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'couldnt_find_bulkmail', '2C125/5', 404, '' );
		}

		/* Make this bulk mail active */
		$mail->active	= 1;
		$mail->start	= time();
		$mail->sentto	= 0;
		$mail->save();

		Session::i()->log( 'acplogs__bulkmail_sent', array( $mail->subject => FALSE ) );
		
		/* And redirect */
		Task::queue( 'core', 'Bulkmail', array( 'mail_id' => $mail->id ) );
		Output::i()->redirect( Url::internal( 'app=core&module=bulkmail&controller=bulkmail' ), 'bm_initiated' );
	}

	/**
	 * Add or edit a bulk mail
	 *
	 * @return	void
	 */
	public function form() : void
	{
		/* Are we editing? */
		$mail	= array( '_options' => array() );

		if( (int) Request::i()->id )
		{
			/* Retrieve the bulk mail details */
			try
			{
				$mail	= Bulkmailer::load( (int) Request::i()->id );
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'couldnt_find_bulkmail', '2C125/6', 404, '' );
			}
		}
		else
		{
			$mail	= new Bulkmailer;
		}

		/* Get tags */
		$tags	= Bulkmailer::getTags();
		$autoSaveKey = 'bulkmail' . ( $mail->id ? '-' . $mail->id : '' );
		
		/* Start the form */
		$form = new Form;
		$form->class = '';
		$form->addTab( 'bulkmail__main', NULL, NULL, 'ipsForm--vertical ipsForm--bulk-mail' );
		$form->add( new Text( 'mail_subject', $mail->subject, TRUE ) );
		$form->add( new Editor( 'mail_body', $mail->content ?: '', TRUE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => $autoSaveKey, 'tags' => $tags, 'attachIds' => $mail->id ? array( $mail->id, NULL, 'bulkmail' ) : NULL ) ) );

		/* Add the filters tab and the "generic filters" header */
		$form->addTab( 'bulkmail__filters', NULL, NULL, 'ipsForm--horizontal' );
		$form->addHeader( 'generic_bm_filters' );

		$lastApp	= 'core';

		/* Now grab bulk mail extensions */
		foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
		{
			if( $extension->availableIn( 'bulkmail' ) )
			{
				/* See if we need a new form header - one per app */
				$_key		= explode( '_', $key );

				if( $_key[0] != $lastApp )
				{
					$lastApp	= $_key[0];
					$form->addHeader( $lastApp . '_bm_filters' );
				}

				/* Grab our fields and add to the form */
				$fields		= $extension->getSettingField( !empty( $mail->_options[ $key ] ) ? $mail->_options[ $key ] : array() );

				foreach( $fields as $field )
				{
					$form->add( $field );
				}
			}
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$mail->subject	= $values['mail_subject'];
			$mail->content	= $values['mail_body'];
			$mail->updated	= 0;
			$mail->start	= 0;
			$mail->sentto	= 0;
			$mail->active	= 0;

			$_options	= array();

			/* Loop over bulk mail extensions to format the options */
			foreach ( Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
			{
				if( $extension->availableIn( 'bulkmail' ) )
				{
					/* Grab our fields and add to the form */
					$_value		= $extension->save( $values );

					if( $_value )
					{
						$_options[ $key ]	= $_value;
					}
				}
			}

			$mail->_options	= $_options;

			if ( !empty( $mail->id ) )
			{
				$mail->save();

				Session::i()->log( 'acplogs__bulkmail_edited', array( $mail->subject => FALSE ) );
			}
			else
			{
				$mail->save();

				Session::i()->log( 'acplogs__bulkmail_added', array( $mail->subject => FALSE ) );
			}
			
			/* Claim attachments */
			File::claimAttachments( $autoSaveKey, $mail->id, NULL, 'bulkmail' );

			Output::i()->redirect( Url::internal( 'app=core&module=bulkmail&controller=bulkmail&do=preview&id=' . $mail->id ), 'saved' );
		}

		/* Output */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('mail_configuration');
		Output::i()->output	= Theme::i()->getTemplate( 'global' )->message( 'unsubscribed_users_mail', 'information' );
		Output::i()->output	.= Theme::i()->getTemplate( 'global' )->block( 'mail_configuration', $form );
	}
}