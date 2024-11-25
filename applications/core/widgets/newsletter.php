<?php
/**
 * @brief		newsletter Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Dec 2017
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Widget;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * newsletter Widget
 */
class newsletter extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'newsletter';

	/**
	 * @brief Language String Key used to store the editor content
	 */
	public static string $editorKey = 'block_newsletter_signup';

	/**
	 * @brief Language Key used to save the content
	 */
	public static string $editorLangKey = 'block_newsletter_signup';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
		



	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

		$form->add( new Translatable( 'block_newsletter_signup_text', NULL, TRUE, array(
			'app'			=> 'core',
			'key'			=> static::$editorLangKey,
			'editor'		=> array(
				'app'			=> 'core',
				'key'			=> 'Widget',
				'autoSaveKey' 	=> 'widget-' . $this->uniqueKey,
				'attachIds'	 	=> isset( $this->configuration['content'] ) ? array( 0, 0, static::$editorLangKey ) : NULL
			),
		) ) );
		return $form;
	}

	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete() : void
	{
		Lang::deleteCustom(  'core', static::$editorLangKey );

		foreach( Db::i()->select( '*', 'core_attachments_map', array( array( 'location_key=?', 'core_Newsletterwidget' ) ) ) as $map )
		{
			try
			{
				$attachment = Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $map['attachment_id'] ) )->first();

				Db::i()->delete( 'core_attachments_map', array( array( 'attachment_id=?', $attachment['attach_id'] ) ) );
				Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );


				File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
				if ( $attachment['attach_thumb_location'] )
				{
					File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
				}
			}
			catch ( Exception $e ) { }
		}
	}

	/**
	 * Ran before saving widget configuration
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function preConfig( array $values ): array
	{
		File::claimAttachments( 'widget-' . $this->uniqueKey, 0, 0, 'block_newsletter_signup' );
		Lang::saveCustom( 'core', 'block_newsletter_signup', $values[ 'block_newsletter_signup_text' ] );
		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if( Member::loggedIn()->allow_admin_mails )
		{
			return "";
		}

		// If we have just dropped the block, the ref will be the URL for that block drop, which will show a WSOD if we click the sign up button before reloading the page 
		if ( isset( Request::i()->blockID ) )
		{
			$url = Settings::i()->base_url;
		}
		else
		{
			$url = (string) Request::i()->url();
		}

		return $this->output( $url );
	}
}