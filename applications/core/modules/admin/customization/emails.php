<?php
/**
 * @brief		Email template management
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 Jul 2013
 */

namespace IPS\core\modules\admin\customization;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Email;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Xml\SimpleXML;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Email template management
 */
class emails extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'emails_manage' );
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
		$table					= new \IPS\Helpers\Table\Db( 'core_email_templates', Url::internal( 'app=core&module=customization&controller=emails' ), array( array( "template_parent=?", 0 ) ) );
		$table->langPrefix		= 'emailtpl_';
		$table->mainColumn		= 'template_name';
		$table->include			= array( 'template_name', 'template_app' );
		$table->limit			= 100;

		/* Primary Sort */
		$table->primarySortBy			= 'template_pinned';
		$table->primarySortDirection	= 'desc';

		/* Default sort options */
		$table->noSort			= array( 'template_name' );
		$table->sortBy			= $table->sortBy ?: 'template_id';
		$table->sortDirection	= $table->sortDirection ?: 'desc';

		$table->quickSearch = function( $val )
		{
			$matches = Member::loggedIn()->language()->searchCustom( 'emailtpl_', $val, TRUE );
			if ( count( $matches ) )
			{
				return '(' . Db::i()->in( '`template_name`', array_keys( $matches ) ) . " OR `template_name` LIKE '%{$val}%')";
			}
			else
			{
				return "`template_name` LIKE '%" . Db::i()->escape_string( $val ) . "%'";
			}
		};

		/* Custom parsers */
		$table->parsers			= array(
			'template_name'	=> function( $val, $row )
			{
				return Member::loggedIn()->language()->addToStack( 'emailtpl_' . $val );
			},
			'template_app'	=> function( $val, $row )
			{
				return Member::loggedIn()->language()->addToStack( '__app_' . $val );
			}
		);

		$table->rowButtons = function( $row )
		{		
			$return = array();

			/* There isn't a separate permission option because literally the only thing you can do with email templates is edit the */
			$return['edit'] = array(
				'icon'		=> 'pencil',
				'title'		=> 'edit',
				'link'		=> Url::internal( 'app=core&module=customization&controller=emails&do=form&key=' ) . $row['template_key'],
			);

			if( $row['template_edited'] )
			{
				$return['revert'] = array(
					'icon'		=> 'undo',
					'title'		=> 'revert',
					'link'		=> Url::internal( 'app=core&module=customization&controller=emails&do=revert&key=' . $row['template_key'] )->csrf(),
					'data'		=> array( 'confirm' => '', 'confirmMessage' => Member::loggedIn()->language()->addToStack('email_revert_confirm') )
				);

				$return['export'] = array(
					'icon'		=> 'download',
					'title'		=> 'download',
					'link'		=> Url::internal( 'app=core&module=customization&controller=emails&do=export&key=' ) . $row['template_key'],
				);
			}

			return $return;
		};

		/* Buttons */
		Output::i()->sidebar['actions'] = array(
				'upload'	=> array(
						'title'		=> 'upload_email_template',
						'icon'		=> 'upload',
						'link'		=> Url::internal( 'app=core&module=customization&controller=emails&do=import' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('upload_email_template') )
                ),
                'preview' => array(
                    'title'		=> 'preview_email_wrapper',
                    'icon'		=> 'search',
                    'link'		=> Url::internal( 'app=core&module=customization&controller=emails&do=preview' ),
                    'data'		=> array( 'ipsDialog' => Url::internal( 'app=core&module=customization&controller=emails&do=preview' ), 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('preview_email_wrapper') )
                ),
		);

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('emailtpl_header');
		Output::i()->output	= (string) $table;
	}

	/**
	 * Export a customized email template
	 *
	 * @return	void
	 */
	public function export() : void
	{
		/* Get the template info */
		try
		{
			$template	= Db::i()->select( '*', 'core_email_templates', array( 'template_parent>0 AND template_key=?', Request::i()->key ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'email_template_not_found', '3S128/3', 403, '' );
		}

		$xml = SimpleXML::create('emails');

		$xml->addChild( 'template', array(
			'template_app'					=> $template['template_app'],
			'template_name'					=> $template['template_name'],
			'template_content_html'			=> $template['template_content_html'],
			'template_content_plaintext'	=> $template['template_content_plaintext'],
			'template_data'					=> $template['template_data'],
			'template_key'					=> $template['template_key'],
		) );

		$name = addslashes( str_replace( array( ' ', '.', ',' ), '_', $template['template_name'] ) . '.xml' );

		Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', $name ) ) );
	}

	/**
	 * Form to import an email template
	 *
	 * @return	void
	 */
	public function import() : void
	{
		$form = new Form( 'form', 'upload' );
		
		$form->add( new Upload( 'email_template_file', NULL, TRUE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );

		if ( $values = $form->values() )
		{
			/* Open XML file */
			try
			{
				$xml = SimpleXML::loadFile( $values['email_template_file'] );

				if( !count($xml->template) )
				{
					Output::i()->error( 'email_template_badform', '1S128/4', 403, '' );
				}
			}
			catch( InvalidArgumentException $e )
			{
				Output::i()->error( 'email_template_badform', '1C373/1', 403, '' );
			}

			foreach( $xml->template as $template )
			{
				$update	= array(
					'template_name'					=> (string) $template->template_name,
					'template_data'					=> (string) $template->template_data,
					'template_app'					=> (string) $template->template_app,
					'template_content_html'			=> (string) $template->template_content_html,
					'template_content_plaintext'	=> (string) $template->template_content_plaintext,
					'template_key'					=> (string) $template->template_key,
				);

				try
				{
					$existing = Db::i()->select( '*', 'core_email_templates', array( 'template_parent=0 AND template_key=?', $update['template_key'] ) )->first();
				}
				catch ( UnderflowException $e )
				{
					Output::i()->error( Member::loggedIn()->language()->addToStack('email_template_noexist', FALSE, array( 'sprintf' => array( $update['template_name'] ) ) ), '1S128/5', 403, '' );
				}
				
				$update['template_parent']	= $existing['template_id'];
				Db::i()->replace( 'core_email_templates', $update );
				Db::i()->update( 'core_email_templates', array( 'template_edited' => 1 ), array( 'template_id=?', $existing['template_id'] ) );
			}
			
			/* Redirect */
			Session::i()->log( 'acplogs__emailtemplate_updated' );
			Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=emails' ), 'email_template_uploaded' );
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( Member::loggedIn()->language()->addToStack('upload_email_template'), $form, FALSE );
	}

	/**
	 * Restore an email template to the original unedited version
	 *
	 * @return	void
	 */
	public function revert() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		/* Get the template info for the log */
		$template	= Db::i()->select( '*', 'core_email_templates', array( 'template_parent=0 AND template_key=?', Request::i()->key ) )->first();

		/* Revert any user-edited copies of the specified template */
		Db::i()->delete( 'core_email_templates', array( 'template_parent>0 AND template_key=?', Request::i()->key ) );

		/* Reset edited flag on parent */
		Db::i()->update( 'core_email_templates', array( 'template_edited' => 0 ), array( 'template_id=?', $template['template_id'] ) );

		/* Rebuild template */
		$this->_buildTemplate( $template );

		/* Log and redirect */
		Session::i()->log( 'acplog__emailtpl_reverted', array( $template['template_name'] => FALSE ) );
		Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=emails' ), 'reverted_emailtpl' );
	}

	/**
	 * Edit an email template
	 *
	 * @return	void
	 */
	public function form() : void
	{
		/* Get the template */
		if( empty( Request::i()->key ) )
		{
			Output::i()->error( 'emailtpl_nofind', '3S128/1', 403, '' );
		}
		
		try
		{
			$template = Db::i()->select( '*', 'core_email_templates', array( 'template_key=?', Request::i()->key ), 'template_parent DESC', 1 )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'emailtpl_nofind', '3S128/2', 403, '' );
		}

		/* Figure out tags for form helpers */
		$_tags	= explode( ",", $template['template_data'] );
		$tags	= array();

		foreach( $_tags as $_tag )
		{
			$_tag	= explode( '=', $_tag );

			$tags[ '{' . trim( $_tag[0] ) . '}' ]	= '';
		}

		/* Start the form */
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--edit-email';
		$form->add( new Codemirror( 'content_html', $template['template_content_html'], TRUE, array( 'tags' => $tags ) ) );
		$form->add( new TextArea( 'content_plaintext', $template['template_content_plaintext'], FALSE, array( 'tags' => $tags, 'rows' => 14 ) ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$save = array(
				'template_name'					=> $template['template_name'],
				'template_data'					=> $template['template_data'],
				'template_key'					=> $template['template_key'],
				'template_content_html'			=> $values['content_html'],
				'template_content_plaintext'	=> $values['content_plaintext'],
				'template_app'					=> $template['template_app'],
				'template_parent'				=> $template['template_parent'] ?: $template['template_id'],
			);

			if ( !empty($template['template_parent']) )
			{
				Db::i()->update( 'core_email_templates', $save, array( 'template_id=?', $template['template_id'] ) );
			}
			else
			{
				Db::i()->insert( 'core_email_templates', $save );
				Db::i()->update( 'core_email_templates', array( 'template_edited' => 1 ), array( 'template_id=?', $template['template_id'] ) );
			}

			$this->_buildTemplate( $save );

			Session::i()->log( 'acplogs__emailtpl_edited', array( $template['template_name'] => FALSE ) );

			Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=emails' ), 'saved' );
		}

		/* Output */

		Output::i()->title		= Member::loggedIn()->language()->addToStack('emailtpl_edit', FALSE, array('sprintf' => Member::loggedIn()->language()->addToStack( 'emailtpl_' . $template['template_name']) ) );
		Output::i()->output	= $form->customTemplate( array( Theme::i()->getTemplate( 'customization', 'core', 'admin' ), 'email' ) );
	}

	/**
	 * Build a template for later execution
	 *
	 * @param	array 	$template	Template data from core_email_templates
	 * @return	void
	 */
	protected function _buildTemplate( array $template ) : void
	{
		$htmlFunction	= 'namespace IPS\Theme;' . "\n" . Theme::compileTemplate( $template['template_content_html'], "email_html_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );
		$ptFunction		= 'namespace IPS\Theme;' . "\n" . Theme::compileTemplate( $template['template_content_plaintext'], "email_plaintext_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );

		$key	= $template['template_key'] . '_email_html';
		Store::i()->$key = $htmlFunction;

		$key	= $template['template_key'] . '_email_plaintext';
		Store::i()->$key = $ptFunction;
	}

	/**
	 * Simple wrapper for the email out, so that we can show it in an iframe
	 *
	 * @return	void
	 */
    public function preview() : void
    {
        Output::i()->title		= Member::loggedIn()->language()->addToStack('emailtpl_edit');
		Output::i()->output	= Theme::i()->getTemplate( 'customization', 'core', 'admin' )->emailFrame( Url::internal( 'app=core&module=customization&controller=emails&do=emailPreview' ) );
    }

    /**
	 * Outputs the raw email preview HTML (to be called inside an iframe)
	 *
	 * @return	void
	 */
    public function emailPreview() : void
    {
		$email = Email::buildFromContent( '', Member::loggedIn()->language()->addToStack( 'email_preview_content' ), NULL, Email::TYPE_TRANSACTIONAL );
		Output::i()->sendOutput( $email->compileContent( 'html' ) );
    }
}