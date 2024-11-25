<?php
/**
 * @brief		Initial installation onboarding
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Feb 2020
 */

namespace IPS\core\modules\admin\overview;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\core\modules\admin\promotion\seo;
use IPS\core\modules\admin\settings\advanced;
use IPS\core\modules\admin\settings\terms;
use IPS\core\modules\admin\settings\webapp;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Image;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Initial installation onboarding
 */
class onboard extends Controller
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
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'dashboard/onboard.css', 'core', 'admin' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_dashboard.js', 'core', 'admin' ) );

		parent::execute();
	}
	
	/**
	 * Welcome page
	 *
	 * @return	void
	 */
	public function initial() : void
	{
		Session::i()->csrfCheck();
		Settings::i()->changeValues( array( 'onboard_complete' => 1 ) ); // Set the flag that we've hit this page so that it doesn't keep trying to redirect us back
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('onboard_pagetitle');
		Output::i()->showTitle	= FALSE;
		Output::i()->bypassCsrfKeyCheck = TRUE;
		Output::i()->output	.= Theme::i()->getTemplate( 'dashboard' )->onboardWelcome();
	}
	
	/**
	 * Dismiss
	 *
	 * @return	void
	 */
	public function dismiss() : void
	{
		Session::i()->csrfCheck();
		\IPS\Email::buildFromTemplate( 'core', 'onboard_reminder', array(), \IPS\Email::TYPE_TRANSACTIONAL )->send( Member::loggedIn() );
		Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=dashboard' ) );
	}
	
	/**
	 * Remind me later
	 *
	 * @return	void
	 */
	public function remind() : void
	{
		Session::i()->csrfCheck();
		Settings::i()->changeValues( array( 'onboard_complete' => DateTime::ts( time() )->add( new DateInterval( 'PT20M' ) )->getTimestamp() ) );
		Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=dashboard' ), 'onboard_will_remind' );
	}

	/**
	 * Save the form values
	 *
	 * @param array $values
	 * @param Form $form
	 * @return void
	 */
	public function handleForm( array $values, Form $form ) : void
	{
		/* If we dismissed, do that but also send an email */
		if( isset( Request::i()->dismiss ) and Request::i()->dismiss == 1 )
		{
			\IPS\Email::buildFromTemplate( 'core', 'onboard_reminder', array(), \IPS\Email::TYPE_TRANSACTIONAL )->send( Member::loggedIn() );

			Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=dashboard' ) );
		}

		/* If we said to remind us later, set the flag and redirect */
		if( isset( Request::i()->remind ) and Request::i()->remind == 1 )
		{
			Settings::i()->changeValues( array( 'onboard_complete'=> DateTime::ts( time() )->add( new DateInterval( 'PT20M' ) )->getTimestamp() ) );

			Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=dashboard' ), 'onboard_will_remind' );
		}

		array_walk( $values[ 'site_social_profiles' ], function( &$value ){
			$value[ 'key' ]= (string) $value[ 'key' ];
		} );
		$values[ 'site_social_profiles' ]=json_encode( array_filter( $values[ 'site_social_profiles' ], function( $value ){
			return (bool)$value[ 'key' ];
		} ) );

		$values[ 'site_address' ] = json_encode( $values[ 'site_address' ] );
		$values[ 'icons_favicon' ] = (string) $values[ 'icons_favicon' ];

		$values= webapp::processApplicationIcon( $values );
		$values= terms::processForm( $values );

		/* If a logo was uploaded, do stuff with it... */
		if( isset( $values[ 'initial_logo' ] ) )
		{
			$values[ 'email_logo' ]=(string)$values[ 'initial_logo' ];

			/* Do this once for efficiency */
			$image= Image::create( $values[ 'initial_logo' ]->contents() );
			$width=$image->width;
			$height=$image->height;
			unset( $image );

			foreach( Theme::themes() as $theme )
			{
				$logoUrl=(string)File::create( 'core_Theme', $values[ 'initial_logo' ]->originalFilename, $values[ 'initial_logo' ]->contents(), NULL, TRUE );

				$theme->saveSet( array( 'logo'=>array( 'front'=>array( 'url'=>$logoUrl, 'width'=>$width, 'height'=>$height ) ) ) );
			}

			unset( $values[ 'initial_logo' ] );
		}
		elseif( array_key_exists( 'initial_logo', $values ) )
		{
			unset( $values[ 'initial_logo' ] );
		}

		$form->saveAsSettings( $values );
	}

	/**
	 * Returns the onboarding form
	 *
	 * @return Form
	 */
	public function getForm(): Form
	{
		/* Create the form and add our message to it */
		$form = new Form;
		$form->class = "ipsForm--vertical ipsForm--onboard";

		$form->addTab( 'onboard_tab_identity' );

		/* Super basic configuration options */
		$form->add( new Text( 'board_name', Settings::i()->board_name, TRUE ) );
		$form->add( new Address( 'site_address', GeoLocation::buildFromJson( Settings::i()->site_address ), FALSE ) );
		$form->add( new Stack( 'site_social_profiles', Settings::i()->site_social_profiles ? json_decode( Settings::i()->site_social_profiles, true ) : array(), FALSE, array( 'stackFieldType' => '\IPS\core\Form\SocialProfiles', 'maxItems' => 50, 'key' => array( 'placeholder' => 'https://example.com', 'size' => 20 ) ) ) );

		$form->add( new Email( 'email_out', Settings::i()->email_out, TRUE, array(), NULL, NULL, NULL, 'email_out' ) );
		$form->add( new Email( 'email_in', Settings::i()->email_in, TRUE ) );

		$form->addTab( 'onboard_tab_appearance' );

		/* If we do not have an email logo and none of our themes have a logo, show the logo field */
		$hasCustomLogo = FALSE;

		foreach( Theme::themes() as $theme )
		{
			if( $theme->logo_front )
			{
				$hasCustomLogo = TRUE;
				break;
			}
		}

		if( !Settings::i()->email_logo and !$hasCustomLogo )
		{
			$form->add( new Upload( 'initial_logo', NULL, FALSE, array( 'image' => true, 'storageExtension' => 'core_Theme' ) ) );
		}

		/* Generic favicon - easy enough */
		$form->add( new Upload( 'icons_favicon', Settings::i()->icons_favicon ? File::get( 'core_Icons', Settings::i()->icons_favicon ) : NULL, FALSE, array( 'obscure' => false, 'allowedFileTypes' => array( 'ico', 'png', 'gif', 'jpeg', 'jpg', 'jpe' ), 'storageExtension' => 'core_Icons' ) ) );

		/* Homescreen icons - we accept one upload and create the images we need */
		$homeScreen = json_decode( Settings::i()->icons_homescreen, TRUE ) ?? array();
		$form->add( new Upload( 'icons_homescreen', ( isset( $homeScreen[ 'original' ] ) ) ? File::get( 'core_Icons', $homeScreen[ 'original' ] ) : NULL, FALSE, array( 'image' => true, 'storageExtension' => 'core_Icons' ) ) );

		$form->add( new Color( 'email_color', Settings::i()->email_color, TRUE ) );

		/* These options are only for non-cic */
		if( !CIC )
		{
			/* Get the task setting */
			$form->addTab( 'onboard_tab_tasks' );
			advanced::taskSetting( $form );

			/* Get the htaccess mod_Rewrite setting */
			$form->addTab( 'onboard_tab_furls' );
			seo::htaccessSetting( $form );
		}

		/* Add the terms/privacy policy/etc. */
		$form->addTab( 'onboard_tab_privacyterms' );
		terms::buildForm( $form );

		/* Add dismiss and remind me later buttons */
		if( Request::i()->initial )
		{
			$form->addButton( 'onboard_remind_later', 'submit', NULL, 'ipsButton ipsButton--secondary', array( 'name' => 'remind', 'value' => 1, 'csrfKey' => Session::i()->csrfKey ) );
			$form->addButton( 'dismiss', 'submit', NULL, 'ipsButton ipsButton--secondary', array( 'name' => 'dismiss', 'value' => 1, 'csrfKey' => Session::i()->csrfKey ) );
		}
		return $form;
	}

	/**
	 * Show a form allowing the admin to configure some common settings after an initial installation
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = $this->getForm();

		if ( $values = $form->values() )
		{
			$this->handleForm( $values, $form );

			/* Clear data stores */
			unset(
				Store::i()->manifest,
				Store::i()->iebrowserconfig,
				Store::i()->frontNavigation
			);

			Session::i()->log( 'acplogs__onboard_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=onboard&do=next' ), 'saved' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('onboard_pagetitle_basic');
		Output::i()->output	.= $form->customTemplate( array( Theme::i()->getTemplate( 'dashboard' ), 'onboardForm' ) );
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'settings/general.css', 'core', 'admin' ) );
	}

	/**
	 * Show a confirmation screen with "next steps"
	 *
	 * @return	void
	 */
	protected function next() : void
	{
		Output::i()->showTitle	= FALSE;
		Output::i()->title		= Member::loggedIn()->language()->addToStack('onboard_pagetitle');

		/* Add Emoji - We can't put this directly in the template since those without utf8mb4 will have issues */
		Member::loggedIn()->language()->words['onboard_complete'] .= ' 🎉';

		Output::i()->output	= Theme::i()->getTemplate( 'dashboard' )->onboard();
	}
}