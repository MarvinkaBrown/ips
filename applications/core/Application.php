<?php
/**
 * @brief		Core Application Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */
 
namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application as SystemApplication;
use IPS\Application\Module;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Review;
use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Rank;
use IPS\core\Achievements\Rule;
use IPS\core\extensions\core\CommunityEnhancements\FacebookPixel;
use IPS\core\extensions\core\CommunityEnhancements\Sendgrid;
use IPS\core\extensions\core\CommunityEnhancements\Zapier;
use IPS\core\Warnings\Warning;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\PrivacyAction;
use IPS\MFA\MFAHandler;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_numeric;
use function ltrim;
use const IPS\CIC;
use const IPS\DEMO_MODE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Core Application Class
 */
class Application extends SystemApplication
{
	/**
	 * @brief	Cached advertisement count
	 */
	protected ?int $advertisements	= NULL;
	
	/**
	 * @brief	Cached clubs pending approval count
	 */
	protected ?int $clubs = NULL;

	/**
	 * ACP Menu Numbers
	 *
	 * @param	array	$queryString	Query String
	 * @return	int
	 */
	public function acpMenuNumber( string $queryString ): int
	{
		parse_str( $queryString, $queryString );
		switch ( $queryString['controller'] )
		{
			case 'advertisements':
				if( $this->advertisements === NULL )
				{
					$this->advertisements	= Db::i()->select( 'COUNT(*)', 'core_advertisements', array( 'ad_active=-1' ) )->first();
				}
				return $this->advertisements;
			
			case 'clubs':
				if( $this->clubs === NULL )
				{
					$this->clubs	= Db::i()->select( 'COUNT(*)', 'core_clubs', array( 'approved=0' ) )->first();
				}
				return $this->clubs;

			case 'privacy':
				$where = ['action IN (?)', Db::i()->in('action', [ PrivacyAction::TYPE_REQUEST_DELETE, PrivacyAction::TYPE_REQUEST_PII] ) ];
				return Db::i()->select( 'COUNT(*)', 'core_member_privacy_actions', $where)->first();

			case 'themes':
			case 'applications':
			case 'languages':
				return $this->_getUpdateCount( $queryString['controller'] );
		}
		
		return 0;
	}
	
	/**
	 * Returns the ACP Menu JSON for this application.
	 *
	 * @return array
	 */
	public function acpMenu(): array
	{
		$menu = parent::acpMenu();
		
		if ( DEMO_MODE )
		{
			unset( $menu['support'] );
		}

		return $menu;
	}
	
	/**
	 * Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	public function get__badge(): ?array
	{
		$return = parent::get__badge();
		
		if ( $return )
		{
			$availableUpgrade = $this->availableUpgrade( TRUE, FALSE );
			$return[2] = Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $availableUpgrade['version'], Url::internal( 'app=core&module=system&controller=upgrade', 'admin' ), DateTime::ts( $availableUpgrade['released'] )->localeDate(), FALSE );
		}
		
		return $return;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'cogs';
	}

	/**
	 * Install Other
	 *
	 * @return	void
	 */
	public function installOther() : void
	{
		/* Save installed domain to spam defense whitelist */
		$domain = rtrim( str_replace( 'www.', '', parse_url( Settings::i()->base_url, PHP_URL_HOST ) ), '/' );
		Db::i()->insert( 'core_spam_whitelist', array( 'whitelist_type' => 'domain', 'whitelist_content' => $domain, 'whitelist_date' => time(), 'whitelist_reason' => 'Invision Community Domain' ) );

		/* Generate VAPID keys for web push notifications */
		try 
		{
			$vapid = Notification::generateVapidKeys();
			Settings::i()->changeValues( array( 'vapid_public_key' => $vapid['publicKey'], 'vapid_private_key' => $vapid['privateKey'] ) );
		}
		catch (Exception $ex)
		{
			Log::log( $ex, 'create_vapid_keys' );
		}

		/* Install default ranks, rules and badges */
		Rule::importXml( $this->getApplicationPath() . "/data/achievements/rules.xml" );
		Rank::importXml( $this->getApplicationPath() . "/data/achievements/ranks.xml" );
		Badge::importXml( $this->getApplicationPath() . "/data/achievements/badges.xml" );
	}
	
	/**
	 * Can view page even when user is a guest when guests cannot access the site
	 *
	 * @param	Module	$module			The module
	 * @param string $controller		The controller
	 * @param string|null $do				To "do" parameter
	 * @return	bool
	 */
	public function allowGuestAccess(Module $module, string $controller, ?string $do ): bool
	{
		return (
			$module->key == 'system'
			and
			in_array( $controller, array( 'login', 'register', 'lostpass', 'terms', 'ajax', 'privacy', 'editor',
				'language', 'theme', 'redirect', 'guidelines', 'announcement', 'metatags', 'serviceworker', 'offline', 'cookie' ) )
		)
		or
		( 
			$module->key == 'contact' and $controller == 'contact'
		)
        or
        (
            $module->key == 'discover' and in_array( $controller, array( 'rss', 'streams' ) )
		)
		or
		(
			$module->key == 'system' and $controller == 'metatags' and $do == 'manifest'
		);
	}
	
	/**
	 * Can view page even when site is offline
	 *
	 * @param	Module	$module			The module
	 * @param string $controller		The controller
	 * @param string|null $do				To "do" parameter
	 * @return	bool
	 */
	public function allowOfflineAccess( Module $module, string $controller, ?string $do ): bool
	{
		return (
			$module->key == 'system'
			and
			(
				in_array( $controller, array(
					'login', // Because you can login when offline
					'embed', // Because the offline message can contain embedded media
					'lostpass',
					'register',
					'announcement', // Announcements can be useful when the site is offline
					'redirect', // When email tracking is enabled we pass through here
					'metatags', // Manifest
					'serviceworker', // Service Worker
					'offline', // Service Worker offline page
					'cookie'
				) )
				or
				(
					$controller === 'ajax' and 
						( $do === 'states' OR  // Makes sure address input still works within the ACP otherwise the form to turn site back online is broken
						$do === 'passwordStrength' OR // Makes sure the password strength meter still works because it is used in the AdminCP and registration
						$do === 'getCsrfKey' ) // Makes sure we can still fetch the correct CSRF key for the ajax replacement
				)
			or
				in_array( $controller, ['terms', 'cookies'] )	// whitelist terms and cookies pages
			)
		);
	}

	/**
	 * Can view page even when the member is IP banned
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function allowBannedAccess( Module $module, string $controller, ?string $do ) : bool
	{
		return (
				$module->key == 'system'
				and
				in_array( $controller, [ 'warnings', 'privacy', 'guidelines', 'metatags' ] )
			)
			or
			(
				$module->key == 'contact' and $controller == 'contact'
			);
	}

	/**
	 * Can view page even when the member is validating
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function allowValidatingAccess( Module $module, string $controller, ?string $do ) : bool
	{
		return (
			$module->key == 'system'
			and
			in_array( $controller, [ 'register', 'login', 'redirect', 'cookies' ] )
		);
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation(): array
	{
		$activityTabs = array(
			array( 'key' => 'AllActivity' ),
			array( 'key' => 'YourActivityStreams' ),
		);
		
		foreach ( array( 1, 2 ) as $k )
		{
			try
			{
				Stream::load( $k );
				$activityTabs[] = array(
					'key'		=> 'YourActivityStreamsItem',
					'config'	=> array( 'menu_stream_id' => $k )
				);
			}
			catch ( Exception $e ) { }
		}

		$activityTabs[] = array( 'key' => 'Search' );
		$activityTabs[] = array( 'key' => 'Featured' );
		
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array(
				array( 'key' => 'Clubs' )
			),
			'browseTabsEnd'	=> array(
				array( 'key' => 'Guidelines' ),
				array( 'key' => 'StaffDirectory' ),
				array( 'key' => 'OnlineUsers' ),
				array( 'key' => 'Leaderboard' )
			),
			'activityTabs'	=> $activityTabs
		);
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters() : void
	{
		/* Convert &section= to &controller= */
		if ( isset( Request::i()->section ) AND !isset( Request::i()->controller ) )
		{
			Request::i()->controller = Request::i()->section;
		}

		/* Convert &showuser= */
		if ( isset( Request::i()->showuser ) and is_numeric( Request::i()->showuser ) )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=members&controller=profile&id=' . Request::i()->showuser ) );
		}
		
		/* Redirect ?app=core&module=attach&section=attach&attach_rel_module=post&attach_id= */
		if ( isset( Request::i()->app ) AND Request::i()->app == 'core' AND isset( Request::i()->controller ) AND Request::i()->controller == 'attach' AND isset( Request::i()->attach_id ) AND is_numeric( Request::i()->attach_id ) )
		{
			Output::i()->redirect( Url::internal( "applications/core/interface/file/attachment.php?id=" . Request::i()->attach_id, 'none' ) );
		}

		/* redirect vnc to new streams */
		if( isset( Request::i()->app ) AND Request::i()->app == 'core' AND  isset( Request::i()->controller ) AND Request::i()->controller == 'vnc' )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=discover&controller=streams' ) );
		}

		/* redirect 4.0 activity page to streams */
		if( isset( Request::i()->app ) AND Request::i()->app == 'core' AND isset( Request::i()->module ) AND (Request::i()->module == 'activity' ) AND isset( Request::i()->controller ) AND Request::i()->controller == 'activity' )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=discover&controller=streams' ) );
		}

		/* redirect old message link */
		if( isset( Request::i()->app ) AND Request::i()->app == 'members' AND isset( Request::i()->module ) AND ( Request::i()->module == 'messaging' ) AND Request::i()->controller == 'view' AND isset( Request::i()->topicID ) )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger&id=' . Request::i()->topicID, 'front', 'messenger_convo' ) );
		}

		/* redirect old messenger link */
		if( isset( Request::i()->app ) AND Request::i()->app == 'members' AND isset( Request::i()->module ) AND ( Request::i()->module == 'messaging' ) )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) );
		}

		/* redirect old messenger link */
		if( isset( Request::i()->module ) AND Request::i()->module == 'global' AND isset( Request::i()->controller ) AND (Request::i()->controller == 'register' ) )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register', 'front', 'register' ) );
		}

		/* redirect old reports */
		if( isset( Request::i()->app ) AND Request::i()->app == 'core' AND
			isset( Request::i()->module ) AND (Request::i()->module == 'reports' ) AND
			isset( Request::i()->do ) AND ( Request::i()->do == 'show_report' )  )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports&action=view&id=' . Request::i()->rid , 'front', 'modcp_report' ) );
		}
	}
	
	/**
	 * Get any third parties this app uses for the privacy policy
	 *
	 * @return array( title => language bit, description => language bit, privacyUrl => privacy policy URL )
	 */
	public function privacyPolicyThirdParties(): array
	{
		/* Apps can overload this */
		$subprocessors = array();
			
		/* Analytics */
		if ( Settings::i()->ga_enabled )
		{
			$subprocessors[] = array(
				'title' => Member::loggedIn()->language()->addToStack('enhancements__core_GoogleAnalytics'),
				'description' => Member::loggedIn()->language()->addToStack('pp_desc_GoogleAnalytics'),
				'privacyUrl' => 'https://www.google.com/intl/en/policies/privacy/'
			);
		}
		if ( Settings::i()->matomo_enabled )
		{
			$subprocessors[] = array(
				'title' => Member::loggedIn()->language()->addToStack('analytics_provider_matomo'),
				'description' => Member::loggedIn()->language()->addToStack('pp_desc_Matomo'),
				'privacyUrl' => 'https://matomo.org/privacy-policy/'
			);
		}
		
		/* Facebook Pixel */
		$fb = new FacebookPixel();
		if ( $fb->enabled )
		{
			$subprocessors[] = array(
				'title' => Member::loggedIn()->language()->addToStack('enhancements__core_FacebookPixel'),
				'description' => Member::loggedIn()->language()->addToStack('pp_desc_FacebookPixel'),
				'privacyUrl' => 'https://www.facebook.com/about/privacy/'
			);
		}
		
		/* IPS Spam defense */
		if ( Settings::i()->spam_service_enabled )
		{
			$subprocessors[] = array(
				'title' => Member::loggedIn()->language()->addToStack('enhancements__core_SpamMonitoring'),
				'description' => Member::loggedIn()->language()->addToStack('pp_desc_SpamMonitoring'),
				'privacyUrl' => 'https://invisioncommunity.com/legal/privacy'
			);
		}
		
		/* Send Grid */
		$sendgrid = new Sendgrid();
		if ( $sendgrid->enabled )
		{
			$subprocessors[] = array(
				'title' => Member::loggedIn()->language()->addToStack('enhancements__core_Sendgrid'),
				'description' => Member::loggedIn()->language()->addToStack('pp_desc_SendGrid'),
				'privacyUrl' => 'https://sendgrid.com/policies/privacy/'
			);
		}
		
		/* Captcha */
		if ( Settings::i()->bot_antispam_type !== 'none' )
		{
			switch ( Settings::i()->bot_antispam_type )
			{
				case 'recaptcha2':
					$subprocessors[] = array(
						'title' => Member::loggedIn()->language()->addToStack('captcha_type_recaptcha2'),
						'description' => Member::loggedIn()->language()->addToStack('pp_desc_captcha'),
						'privacyUrl' => 'https://www.google.com/policies/privacy/'
					);
					break;
				case 'invisible':
					$subprocessors[] = array(
						'title' => Member::loggedIn()->language()->addToStack('captcha_type_invisible'),
						'description' => Member::loggedIn()->language()->addToStack('pp_desc_captcha'),
						'privacyUrl' => 'https://www.google.com/policies/privacy/'
					);
					break;
				case 'keycaptcha':
					$subprocessors[] = array(
						'title' => Member::loggedIn()->language()->addToStack('captcha_type_keycaptcha'),
						'description' => Member::loggedIn()->language()->addToStack('pp_desc_captcha'),
						'privacyUrl' => 'https://www.keycaptcha.com'
					);
					break;
				case 'hcaptcha':
					$subprocessors[] = array(
						'title' => Member::loggedIn()->language()->addToStack('captcha_type_hcaptcha'),
						'description' => Member::loggedIn()->language()->addToStack('hcaptcha_privacy'),
						'privacyUrl' => 'https://www.hcaptcha.com/privacy'
					);
			}
		}
		
		return $subprocessors;
				
	}
	
	/**
	 * Get any settings that are uploads
	 *
	 * @return	array
	 */
	public function uploadSettings(): array
	{
		/* Apps can overload this */
		return array( 'email_logo' );
	}

	/**
	 * Imports an IN_DEV email template into the database
	 *
	 * @param	string		$path			Path to file
	 * @param	object		$file			DirectoryIterator File Object
	 * @param	string|null	$namePrefix		Name prefix
	 * @return  array
	 */
	protected function _buildEmailTemplateFromInDev( string $path, object $file, ?string $namePrefix='' ): array
	{
		$return = parent::_buildEmailTemplateFromInDev( $path, $file, $namePrefix );

		/* Make sure that the email wrapper is pinned to the top of the list */
		if( $file->getFilename() == 'emailWrapper.phtml' )
		{
			$return['template_pinned'] = 1;
		}

		return $return;
	}

	/**
	 * Get AdminCP Menu Count for resource updates
	 *
	 * @param	string	$type		resource type (applications/plugins/languages/themes)
	 * @return	int
	 */
	protected function _getUpdateCount( string $type ): int
	{
		$key = "updatecount_{$type}";
		if( isset( Store::i()->$key ) )
		{
			return Store::i()->$key;
		}

		$count = 0;
		switch( $type )
		{
			case 'applications':
				foreach( Application::applications() as $app )
				{
					if ( CIC AND IPS::isManaged() AND in_array( $app->directory, IPS::$ipsApps ) )
					{
						continue;
					}
					
					if( count( $app->availableUpgrade( TRUE ) ) )
					{
						$count++;
					}
				}
				break;
			case 'languages':
				foreach( Lang::languages() as $language )
				{
					if( $language->update_data )
					{
						$data = json_decode( $language->update_data, TRUE );
						if( !empty( $data['longversion'] ) AND $data['longversion'] > $language->version_long )
						{
							$count++;
						}
					}
				}
				break;
			case 'themes':
				foreach( Theme::themes() as $theme )
				{
					if( $theme->update_data )
					{
						$data = json_decode( $theme->update_data, TRUE );
						if( !empty( $data['longversion'] ) AND $data['longversion'] > $theme->long_version )
						{
							$count++;
						}
					}
				}
				break;
		}

		Store::i()->$key = $count;
		return (int) Store::i()->$key;
	}

	/**
	 * Returns a list of all existing webhooks and their payload in this app.
	 *
	 * @return array
	 */
	public function getWebhooks() : array
	{
		return array_merge(  [
				'club_created' => Club::class,
				'club_deleted' => Club::class,
				'club_member_added' => ['club' => Club::class, 'member' => Member::class, 'status' => "string" ],
				'club_member_removed' => ['club' => Club::class, 'member' => Member::class ],
				'member_create' => Member::class,
				'member_registration_complete' => Member::class,
				'member_edited' => [ 'member' => Member::class, 'changes' => "array" ],
				'member_delete' => Member::class,
				'member_warned' => Warning::class,
				'member_merged' => [ 'kept' => Member::class, 'removed' => Member::class],
				'content_promoted' => Content::class,
				'content_marked_solved' => [ 'item' => Item::class, 'comment' => Comment::class , 'markedBy' => Member::class ]
		], parent::getWebhooks() );
	}

	/**
	 * Do we run doMemberCheck for this controller?
	 * @see Application::doMemberCheck()(
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function skipDoMemberCheck( Module $module, string $controller, ?string $do ) : bool
	{
		return (
				$module->key == 'system'
				and
				in_array( $controller, [ 'privacy', 'terms', 'embed', 'metatags', 'serviceworker', 'settings', 'language', 'theme', 'ajax', 'register', 'login', 'cookies', 'redirect', 'editor' ] )
			) or (
				$module->key == 'contact' and $controller == 'contact'
			);
	}
	
	/**
	 * Do Member Check
	 *
	 * @return	Url|NULL
	 */
	public function doMemberCheck(): ?Url
	{
		/* Need their name or email... */
		if( ( Member::loggedIn()->real_name === '' or !Member::loggedIn()->email ) )
		{
			return Url::internal( 'app=core&module=system&controller=register&do=complete' )->setQueryString( 'ref', base64_encode( Request::i()->url() ) );
		}
		/* Need them to validate... */
		elseif(
			Member::loggedIn()->members_bitoptions['validating'] and !Dispatcher::i()->application->allowValidatingAccess( Dispatcher::i()->module, Dispatcher::i()->controller, Request::i()->do ?? null )
		)
		{
			return Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' );
		}
		/* Need them to reconfirm terms/privacy policy... */
		elseif ( ( Member::loggedIn()->members_bitoptions['must_reaccept_privacy'] or Member::loggedIn()->members_bitoptions['must_reaccept_terms'] ) )
		{
			return Url::internal( 'app=core&module=system&controller=register&do=reconfirm', 'front', 'register' )->setQueryString( 'ref', base64_encode( Request::i()->url() ) );
		}
		/* Have required profile actions that need completing */
		else if (
			Settings::i()->allow_reg AND
			!Member::loggedIn()->members_bitoptions['profile_completed'] AND
			Dispatcher::i()->controller != 'pixabay' AND
			$completion = Member::loggedIn()->profileCompletion() AND
			count( $completion['required'] )
		)
		{
			foreach( $completion['required'] AS $id => $completed )
			{
				if ( $completed === FALSE )
				{
					return Url::internal( "app=core&module=system&controller=register&do=finish&_new=1", 'front', 'register' )->setQueryString( 'ref', base64_encode( Request::i()->url() ) );
				}
			}
		}

		/* Need to set up MFA... */
		$haveAcceptableHandlers = FALSE;
		$haveConfiguredHandler = FALSE;
		foreach ( MFAHandler::handlers() as $key => $handler )
		{
			if ( $handler->isEnabled() and $handler->memberCanUseHandler( Member::loggedIn() ) )
			{
				$haveAcceptableHandlers = TRUE;
				if ( $handler->memberHasConfiguredHandler( Member::loggedIn() ) )
				{
					$haveConfiguredHandler = TRUE;
					break;
				}
			}
		}

		if ( !$haveConfiguredHandler and $haveAcceptableHandlers )
		{
			if ( Settings::i()->mfa_required_groups == '*' or Member::loggedIn()->inGroup( explode( ',', Settings::i()->mfa_required_groups ) ) )
			{
				if ( Settings::i()->mfa_required_prompt === 'immediate' )
				{
					return Url::internal( 'app=core&module=system&controller=settings&do=initialMfa', 'front', 'settings' )->setQueryString( 'ref', base64_encode( Request::i()->url() ) );
				}
			}
			elseif ( Settings::i()->mfa_optional_prompt === 'immediate' and !Member::loggedIn()->members_bitoptions['security_questions_opt_out'] )
			{
				return Url::internal( 'app=core&module=system&controller=settings&do=initialMfa', 'front', 'settings' )->setQueryString( 'ref', base64_encode( Request::i()->url() ) );
			}
		}

		/* Need to reset password */
		if ( ( Dispatcher::i()->controller !== 'settings' AND ( !isset( Request::i()->area ) OR Request::i()->area !== 'password' ) ) AND ! ( Dispatcher::i()->controller == 'alerts' AND Request::i()->do == 'dismiss'  ) )
		{
			foreach( Login::methods() AS $method )
			{
				if ( $url = $method->forcePasswordResetUrl( Member::loggedIn(), Request::i()->url() ) )
				{
					return $url;
				}
			}
		}
		
		return NULL;
	}

	/**
	 * Install the application's settings
	 *
	 * @return	void
	 */
	public function installSettings() : void
	{
		/* It's enough if we run this only for the core app instead for each app which is upgraded */
		Zapier::rebuildRESTApiPermissions();
		parent::installSettings();
	}

	/**
	 * Returns a list of essential cookies which are set by this app.
	 * Wildcards (*) can be used at the end of cookie names for PHP set cookies.
	 *
	 * @return string[]
	 */
	public function _getEssentialCookieNames(): array
	{
		$cookies = [ 'oauth_authorize', 'member_id', 'login_key', 'clearAutosave', 'lastSearch','device_key', 'IPSSessionFront', 'loggedIn', 'noCache', 'cookie_consent', 'cookie_consent_optional', 'theme_editor_vars' ];

		if( Settings::i()->guest_terms_bar )
		{
			$cookies[] = 'guestTermsDismissed';
		}

		if( count( Lang::getEnabledLanguages() ) > 1 )
		{
			$cookies[] = 'language';
		}

		if( Settings::i()->ref_on )
		{
			$cookies[] = 'referred_by';
		}

		foreach ( Login::methods() as $method )
		{
			if( isset( $method->pkceSupported ) AND $method->pkceSupported === TRUE )
			{
				$cookies[] = 'codeVerifier';
				break;
			}
		}

		return $cookies;
	}

	/**
	 * Retrieve additional form fields for adding an extension
	 * This should return an array where the key is the tag in
	 * the extension stub that will be replaced, and the value is
	 * the form field
	 *
	 * @param string $extensionType
	 * @param string $appKey The application creating the extension
	 * @return array
	 */
	public function extensionHelper( string $extensionType, string $appKey ) : array
	{
		$return = [];
		switch( $extensionType )
		{
			case 'AccountSettings':
				$return[ '{tabKey}' ] = new Text( 'extension_tab_key', null, true );
				break;

			case 'ModCp':
				$return[ '{tabKey}' ] = new Text( 'extension_tab_key', null, true );
				$return[ '{manageType}' ] = new Select( 'extension_modcp_manage_type', 'other', true, [
					'options' => [
						'content' => "Content",
						'members' => "Members",
						'other' => "Other"
					]
				]);
				break;

			case 'AdminNotifications':
				$return[ '{group}' ] = new Select( 'extension_acp_notify_group', 'other', true, [
					'options' => [
						'system' => 'System',
						'members' => 'Members',
						'important' => 'Important',
						'commerce' => 'Commerce',
						'other' => 'Other'
					]
				]);
				$return[ '{priority}' ] = new Number( 'extension_acp_notify_priority', 3, true, [
					'min' => 1,
					'max' => 5
				]);
				$return[ '{severity}' ] = new Select( 'extension_acp_notify_severity', 'static::SEVERITY_NORMAL', true, [
					'options' => [
						'static::SEVERITY_OPTIONAL' => ucwords( AdminNotification::SEVERITY_OPTIONAL ),
						'static::SEVERITY_NORMAL' => ucwords( AdminNotification::SEVERITY_NORMAL ),
						'static::SEVERITY_DYNAMIC' => ucwords( AdminNotification::SEVERITY_DYNAMIC ),
						'static::SEVERITY_HIGH' => ucwords( AdminNotification::SEVERITY_HIGH ),
						'static::SEVERITY_CRITICAL' => ucwords( AdminNotification::SEVERITY_CRITICAL )
					]
				]);
				break;

			case 'ContentRouter':
				$modules = [ '' => '' ];
				foreach( SystemApplication::load( $appKey )->modules( 'front' ) as $key => $module )
				{
					$modules[ $key ] = $module->_title;
				}
				$return[ '{module}' ] = new Select( 'extension_module_name', null, true, [ 'options' => $modules ] );
			/* Don't do a break statement here because we want the item field */

			case 'UIItem':
			case 'RssImport':
				$return[ '{item}' ] = new Text( 'extension_item_class', null, true, [], function( $val ){
					if( !is_subclass_of( $val, Item::class ) )
					{
						throw new DomainException( 'err_invalid_item_class' );
					}
				} );
				break;

			case 'UINode':
			case 'Permissions':
				$return[ '{node}' ] = new Text( 'extension_node_class', null, true, [], function( $val ){
					if( !is_subclass_of( $val, Model::class ) )
					{
						throw new DomainException( 'err_invalid_node_class' );
					}
				} );
				break;

			case 'UIComment':
				$return[ '{comment}' ] = new Text( 'extension_comment_class', null, true, [], function( $val ){
					if( !is_subclass_of( $val, Comment::class ) or is_subclass_of( $val, Review::class ) )
					{
						throw new DomainException( 'err_invalid_comment_class' );
					}
				} );
				break;

			case 'UIReview':
				$return[ '{review}' ] = new Text( 'extension_review_class', null, true, [], function( $val ){
					if( !is_subclass_of( $val, Review::class ) )
					{
						throw new DomainException( 'err_invalid_review_class' );
					}
				} );
				break;

			case 'Forms':
				$return[ '{formType}' ] = new Select( 'extension_form_type', null, true, [
					'options' => [
						'Form::FORM_REGISTRATION' => 'extension_form_type__' . Form::FORM_REGISTRATION,
						'Form::FORM_CHECKOUT' => 'extension_form_type__' . Form::FORM_CHECKOUT
					]
				]);
				break;

			case 'MemberFilter':
				$return[ '{areas}' ] = new CheckboxSet( 'extension_mf_areas', null, true, [
					'options' => [
						"'bulkmail'" => 'extension_mf_areas__bulkmail',
						"'group_promotions'" => 'extension_mf_areas__group_promotions',
						"'automatic_moderation'" => 'extension_mf_areas__automatic_moderation',
						"'passwordreset'" => 'extension_mf_areas__passwordreset',
					],
					'noDefault' => true
				]);
				break;

			case 'OverviewStatistics':
				$return[ '{statisticsPage}' ] = new Select( 'extension_statistics_page', null, true, [
					'options' => [
						'user' => 'User',
						'activity' => 'Activity'
					]
				] );
				break;
		}

		return $return;
	}

	/**
	 * Process additional form fields that are added in Application::extensionHelper()
	 *
	 * @param string $extensionType
	 * @param string $appKey
	 * @param array $values
	 * @return array
	 */
	public function extensionGenerate( string $extensionType, string $appKey, array $values ) : array
	{
		foreach( [ 'extension_item_class', 'extension_node_class', 'extension_comment_class', 'extension_review_class' ] as $field )
		{
			if( isset( $values[ $field ] ) and $values[ $field ] )
			{
				$values[ $field ] = ltrim( $values[ $field ], '\\' );
			}
		}

		return parent::extensionGenerate( $extensionType, $appKey, $values );
	}
}
