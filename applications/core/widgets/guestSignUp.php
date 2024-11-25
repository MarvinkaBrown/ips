<?php
/**
 * @brief		guestSignUp Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Mar 2017
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Login;
use IPS\Member;
use IPS\Widget;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * guestSignUp Widget
 */
class guestSignUp extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'guestSignUp';

	/**
	 * @brief Language String Key used to store the editor content
	 */
	public static string $editorKey = 'block_guestsignup_message';

	/**
	 * @brief Language Key used to save the content
	 */
	public static string $editorLangKey = 'widget_guestsignup_text';


	/**
	 * @brief	App
	 */
	public string $app = 'core';



	/**
	 * Constructor
	 *
	 * @param String $uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param array|string|null $access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation			Orientation (top, bottom, right, left)
	 * @param string $layout
	 * @return	void
	 */
	public function __construct(string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='table' )
	{
		parent::__construct($uniqueKey, $configuration, $access, $orientation, $layout);
		$this->errorMessage = 'guest_signup_admin_message';
	}

		/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

 		$form->add( new Translatable( 'block_guestsignup_title', NULL, TRUE, array( 'app'=>'core', 'key' =>'widget_guestsignup_title' ) ) );
		$form->add( new Translatable( 'block_guestsignup_message', NULL, TRUE, array(
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
		Lang::deleteCustom(  'core', 'widget_guestsignup_title' );
		Lang::deleteCustom(  'core', 'widget_guestsignup_text' );

		foreach( Db::i()->select( '*', 'core_attachments_map', array( array( 'location_key=?', 'core_GuestSignupWidget' ) ) ) as $map )
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
		File::claimAttachments( 'widget-' . $this->uniqueKey, 0, 0, 'widget_guestsignup_text' );
		Lang::saveCustom( 'core', static::$editorLangKey, $values[ 'block_guestsignup_message' ] );
		Lang::saveCustom( 'core', 'widget_guestsignup_title', $values[ 'block_guestsignup_title' ] );
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* Show this only to guests */
		if ( Member::loggedIn()->member_id  )
		{
			return '';
		}
		else
		{
			$title = Member::loggedIn()->language()->addToStack( 'widget_guestsignup_title' );
			$text = Member::loggedIn()->language()->addToStack( 'widget_guestsignup_text' );
			if ( !Member::loggedIn()->language()->checkKeyExists( 'widget_guestsignup_title' ) )
			{
				return '';
			}
			
			$login = new Login( Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
			return $this->output( $login, $text, $title );
		}
	}
}