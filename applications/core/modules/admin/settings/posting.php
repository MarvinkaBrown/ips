<?php
/**
 * @brief		Manage Posting Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Jun 2013
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\core\Acronym;
use IPS\core\Profanity;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Extensions\SSOAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use IPS\Xml\XMLReader;
use OutOfRangeException;
use UnderflowException;
use XMLWriter;
use function count;
use function defined;
use function intval;
use function is_array;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Manage Posting Settings
 */
class posting extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Tabs array
	 */
	protected array $tabs = array();

	/**
	 * @brief	Active tab
	 */
	protected ?string $activeTab = NULL;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Build tab list */
		$this->tabs = array();
		if( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage' ) )
		{
			$this->tabs['general']			= 'posting_general';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_acronym' ) )
		{
			$this->tabs['acronymExpansion'] = 'word_expansion';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_polls' ) )
		{
			$this->tabs['polls'] = 'polls';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_profanity' ) )
		{
			$this->tabs['profanityFilters'] = 'profanity';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_url' ) )
		{
			$this->tabs['urlFilters']			= 'url_settings';
			$this->tabs['embedUrlFilters'] = 'post_settings_embed';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_simplified_mode' ) )
		{
			$this->tabs['simplifiedMode']			= 'editor_simplified_mode';
		}

		/* Choose active tab */
		if ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $this->tabs ) )
		{
			$this->activeTab = Request::i()->tab;
		}
		else
		{
			$keys = array_keys( $this->tabs );
			$this->activeTab = array_shift( $keys );
		}
		
		/* Run */
		parent::execute();
	}

	/**
	 * Manage Posting Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Work out output */
		$methodFunction = '_manage' . IPS::mb_ucfirst( $this->activeTab );
		$activeTabContents = $this->$methodFunction();
		
		/* If this is an AJAX request, just return it */
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_settings_posting');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->tabs( $this->tabs, $this->activeTab, $activeTabContents, Url::internal( "app=core&module=settings&controller=posting" ) );
	}
		
	/**
	 * Manage general posting settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageGeneral() : string
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage' );
		
		/* Build Form */
		$form = new Form;
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_settings.js', 'core', 'admin' ) );
		$form->attributes['data-controller'] = 'core.admin.settings.posting';
		$form->addHeader('posting_attachments');
		$form->add( new Radio( 'attach_allowed_types', Settings::i()->attach_allowed_types, FALSE, array(
			'options' 	=> array( 'all' => 'attach_allowed_types_all', 'media' => 'attach_allowed_types_media', 'images' => 'attach_allowed_types_images', 'none' => 'attach_allowed_types_none' ),
			'toggles'	=> array( 'all' => array( 'attach_allowed_extensions', 'attachment_resample_size', 'attachment_image_size' ), 'media' => array( 'attachment_resample_size', 'attachment_image_size' ), 'images' => array( 'attachment_resample_size', 'attachment_image_size' ) )
		) ) );
		$form->add( new Text( 'attach_allowed_extensions', Settings::i()->attach_allowed_extensions ? explode( ',', Settings::i()->attach_allowed_extensions ) : NULL, FALSE, array( 'nullLang' => 'no_restriction', 'autocomplete' => array( 'freeChoice' => TRUE, 'source' => array( 'doc', 'docx', 'log', 'msg', 'odt', 'pages', 'rtf', 'tex', 'txt', 'wpd', 'wps', 'csv', 'dat', 'gbr', 'ged', 'key', 'keychain', 'pps', 'ppt', 'pptx', 'sdf', 'tar', 'tax2012', 'tax2014', 'vcf', 'xml', 'aif', 'iff', 'm3u', 'm4a', 'mid', 'mp3', 'mpa', 'ra', 'wav', 'wma', '3g2', '3gp', 'asf', 'asx', 'avi', 'flv', 'm4v', 'mov', 'mp4', 'mpg', 'rm', 'srt', 'swf', 'vob', 'wmv', '3dm', '3ds', 'max', 'obj', 'bmp', 'dds', 'gif', 'jpg', 'png', 'psd', 'pspimage', 'tga', 'thm', 'tif', 'tiff', 'yuv', 'ai', 'eps', 'ps', 'svg', 'indd', 'pct', 'pdf', 'xlr', 'xls', 'xlsx', 'accdb', 'db', 'dbf', 'mdb', 'pdb', 'sql', 'apk', 'app', 'bat', 'cgi', 'com', 'exe', 'gadget', 'jar', 'pif', 'vb', 'wsf', 'dem', 'gam', 'nes', 'rom', 'sav', 'dwg', 'dxf', 'gpx', 'kml', 'kmz', 'asp', 'aspx', 'cer', 'cfm', 'csr', 'css', 'htm', 'html', 'js', 'jsp', 'php', 'rss', 'xhtml', 'crx', 'plugin', 'fnt', 'fon', 'otf', 'ttf', 'cab', 'cpl', 'cur', 'deskthemepack', 'dll', 'dmp', 'drv', 'icns', 'ico', 'lnk', 'sys', 'cfg', 'ini', 'prf', 'hqx', 'mim', 'uue', '7z', 'cbr', 'deb', 'gz', 'pkg', 'rar', 'rpm', 'sitx', 'tar.gz', 'zip', 'zipx', 'bin', 'cue', 'dmg', 'iso', 'mdf', 'toast', 'vcd', 'c', 'class', 'cpp', 'cs', 'dtd', 'fla', 'h', 'java', 'lua', 'm', 'pl', 'py', 'sh', 'sln', 'swift', 'vcxproj', 'xcodeproj', 'bak', 'tmp', 'crdownload', 'ics', 'msi', 'part', 'torrent' ) ) ), NULL, NULL, NULL, 'attach_allowed_extensions' ) );
		$form->addHeader('posting_images');
        $form->add( new WidthHeight( 'attachment_resample_size', Settings::i()->attachment_resample_size ? explode( 'x', Settings::i()->attachment_resample_size ) : array( 0, 0 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'attachment_resample_size' ) );
		$current = ( isset( Settings::i()->attachment_image_size ) ) ? explode( 'x', Settings::i()->attachment_image_size ) : array( 1000, 750 );
		$form->add( new WidthHeight( 'attachment_image_size', $current, FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ), 'unlimitedLang' => 'attachment_image_size__no_thumbnails' ), function( $value ){

			if( $value[0] > 4800 OR $value[1] > 4800 )
			{
				throw new InvalidArgumentException('form_image_too_large');
			}
			$notAllowed = [ 0, NULL, "" ];

			if( in_array( $value[0], $notAllowed ) or in_array( $value[1], $notAllowed ) )
			{
				if( $value[0] == 0 AND $value[1] == 0 )
				{
					return TRUE;
				}
				throw new InvalidArgumentException('form_image_not_null');
			}
		}, NULL, NULL, 'attachment_image_size' ) );

		$form->add( new Number( 'max_internalembed_width', Settings::i()->max_internalembed_width, FALSE, array( 'unlimited' => 0 ), NULL, NULL, 'px', 'max_internalembed_width' ) );

		$form->addHeader('posting_content');
		if ( Login::registrationType() != 'disabled' )
		{
			/* Check SSO Extensions for overloads */
			$pbrDisabled = FALSE;
			$appBlocks = [];
			foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $app => $ext )
			{
				/* @var SSOAbstract $ext */
				if( $ext->isEnabled() AND array_search( 'post_before_registering', $ext->overrideSettings() ) )
				{
					$appBlocks[] = $app;
					$pbrDisabled = TRUE;
				}
			}

			if( $pbrDisabled )
			{
				$x = Member::loggedIn()->language()->addToStack( 'sso_setting_override', FALSE, [ 'pluralize' => [ count( $appBlocks ) ],'htmlsprintf' =>  [ Member::loggedIn()->language()->formatList( $appBlocks ) ] ] );
				Member::loggedIn()->language()->words['post_before_registering_desc'] = $x;
			}

			$form->add( new Radio( 'post_before_registering', Settings::i()->post_before_registering, FALSE, array( 'disabled' => $pbrDisabled,'options' => array( 1 => 'post_before_registering_on', 0 => 'post_before_registering_off' ) ), NULL, NULL, NULL, 'post_before_registering' ) );
		}
		Member::loggedIn()->language()->words['post_before_registering_on'] = sprintf( Member::loggedIn()->language()->get('post_before_registering_on'), Member::loggedIn()->language()->addToStack( 'core_group_' . Settings::i()->member_group ) );
		$form->add( new Number( 'max_title_length', Settings::i()->max_title_length, FALSE, array( 'max' => 255 ), NULL, NULL, Member::loggedIn()->language()->addToStack('max_title_length_suffix') ) );
		$form->add( new Interval( 'merge_concurrent_posts', intval( Settings::i()->merge_concurrent_posts ), FALSE, array( 'valueAs' => Interval::MINUTES, 'unlimited' => 0, 'unlimitedLang' => 'never' ) ) );
		$form->add( new Interval( 'flood_control', Settings::i()->flood_control, FALSE, array( 'valueAs' => Interval::SECONDS, 'unlimited' => 0, 'unlimitedLang' => 'none' ) ) );
		$form->addHeader('edit_log');
		$form->add( new Radio( 'edit_log', Settings::i()->edit_log, FALSE, array(
			'options' => array(
				0	=> 'edit_log_none',
				1	=> 'edit_log_simple',
				2	=> 'edit_log_full'
			),
			'toggles'	=> array(
				2	=> array( 'edit_log_public', 'edit_log_prune' )
			)
		) ) );
		$form->add( new YesNo( 'edit_log_public', Settings::i()->edit_log_public, FALSE, array(), NULL, NULL, NULL, 'edit_log_public' ) );
		$form->add( new Interval( 'edit_log_prune', Settings::i()->edit_log_prune, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => -1, 'unlimitedLang' => 'never' ), function( $value ){ if( $value < 1 AND $value != -1 ) { throw new InvalidArgumentException('form_required'); } }, NULL, NULL, 'edit_log_prune' ) );
		
		$form->addHeader('posting_items');
		$form->add( new Interval( 'topic_redirect_prune', Settings::i()->topic_redirect_prune, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL ) );
		
		$hasReviewableApps = false;
		
		foreach ( Application::allExtensions( 'core', 'ContentRouter', NULL, NULL, NULL, TRUE ) as $router )
		{
			foreach ( $router->classes as $class )
			{
				if ( isset( $class::$commentClass ) )
				{
					$hasReviewableApps = true;
					break 2;
				}
			}
		}
		
		if ( $hasReviewableApps )
		{
			$form->add( new Radio( 'reviews_rating_out_of', Settings::i()->reviews_rating_out_of, FALSE, array(
				'options' => array(
					5	=> 'reviews_rating_out_of_5',
					10	=> 'reviews_rating_out_of_10'
				)
			) ) );
		}
		
		$form->add( new Number( 'dellog_retention_period', Settings::i()->dellog_retention_period, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'immediately', 'max' => PHP_INT_MAX ), NULL, NULL, Member::loggedIn()->language()->addToStack('dellog_days_after_deleted') ) );
		
		$form->add( new Interval( 'ip_address_prune', Settings::i()->ip_address_prune, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never', 'max' => PHP_INT_MAX ) ) );

		$form->add( new Radio( 'editor_paragraph_padding',  Settings::i()->editor_paragraph_padding, FALSE, array( 'options' => array(
			1		=> 'editor_paragraph_padding_on',
			0		=> 'editor_paragraph_padding_off'
		) ) ) );
		
		/* Save values */
		if ( $values = $form->values() )
		{
			Db::i()->update( 'core_tasks', array( 'enabled' => ( isset( $values['post_before_registering'] ) ) ? (int) $values['post_before_registering'] : 0 ), array( '`key`=?', 'postBeforeRegisterFollowup' ) );

			$values['max_internalembed_width_css'] = ( $values['max_internalembed_width'] ) ? $values['max_internalembed_width'] . 'px' : 'none';
			$values['attachment_resample_size'] = implode( 'x', $values['attachment_resample_size'] );
			$values['attachment_image_size'] = implode( 'x', $values['attachment_image_size'] );
			$values['attach_allowed_extensions'] = ( is_array( $values['attach_allowed_extensions'] ) and count( $values['attach_allowed_extensions'] ) ) ? implode( ',', array_unique( array_map( function( $val )
			{
				return ltrim( $val, '.' );
			}, $values['attach_allowed_extensions'] ) ) ) : null;

			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__posting_general_settings' );

			Theme::deleteCompiledCss();

			/* redirect so CSS can rebuild */
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&tab=general' ), 'saved' );
		}
	
		return $form;
	}
	
	/**
	 * Show profanity filters
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageProfanityFilters() : string
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );
		
		/* Create the table */
		$table = Profanity::table();
				
		/* So people don't set up filters, test with their admin account and think it's not working */
		$groupsThatBypass = new ActiveRecordIterator( Db::i()->select( '*', 'core_groups', array( 'g_bypass_badwords=1' ) ), 'IPS\Member\Group' );
		if ( count( $groupsThatBypass ) )
		{
			$names = array();
			foreach ( $groupsThatBypass as $group )
			{
				$names[] = $group->name;
			}
			$message = Theme::i()->getTemplate( 'forms' )->blurb( Member::loggedIn()->language()->addToStack( 'profanity_bypass_groups', FALSE, array( 'htmlsprintf' => array( Member::loggedIn()->language()->formatList( $names ) ) ) ), FALSE, TRUE );
		}
		else
		{
			$message = Theme::i()->getTemplate( 'forms' )->blurb( 'profanity_no_bypass_groups', TRUE, TRUE );
		}
		
		/* Display */
		return $message . $table;
	}

	/**
	 * Link url filters
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageUrlFilters() : string
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_url' );
		
		/* Build Form */
		$form = new Form;
		$form->add( new Radio( 'ipb_url_filter_option', Settings::i()->ipb_url_filter_option, FALSE, array(
			'options' => array(
				'none' => 'url_none',
				'black' => 'url_blacklist',
				'white' => "url_whitelist" ),
			'toggles' => array(
				'black'	=> array( 'ipb_url_blacklist', 'url_filter_action' ),
				'white'	=> array( 'ipb_url_whitelist', 'url_filter_action' ),
				'none'		=> array( 'url_filter_any_action' ),
			)
		) ) );
		
		$form->add( new Stack( 'ipb_url_whitelist', Settings::i()->ipb_url_whitelist ? explode( ",", Settings::i()->ipb_url_whitelist ) : array(), FALSE, array(), NULL, NULL, NULL, 'ipb_url_whitelist' ) );
 		$form->add( new Stack( 'ipb_url_blacklist', Settings::i()->ipb_url_blacklist ? explode( ",", Settings::i()->ipb_url_blacklist ) : array(), TRUE, array(), NULL, NULL, NULL, 'ipb_url_blacklist' ) );
 		
 		$form->add( new Radio( 'url_filter_action', Settings::i()->url_filter_action, FALSE, array(
	 		'options'		=> array(
		 		'block'			=> 'url_filter_block',
		 		'moderate'		=> 'url_filter_moderate'
	 		),
	 		'descriptions'	=> array(
		 		'block'			=> 'url_filter_block_desc',
		 		'moderate'		=> 'url_filter_moderate_desc'
	 		)
 		), NULL, NULL, NULL, 'url_filter_action' ) );
 		
 		$form->add( new Radio( 'url_filter_any_action', Settings::i()->url_filter_any_action, FALSE, array(
	 		'options'		=> array(
		 		'allow'			=> 'url_filter_any_allow',
		 		'moderate'		=> 'url_filter_any_moderate'
	 		),
	 		'description'	=> array(
		 		'allow'			=> 'url_filter_any_allow_desc',
		 		'moderate'		=> 'url_filter_any_moderate_desc'
	 		)
 		), NULL, NULL, NULL, 'url_filter_any_action' ) );
		
		$form->add( new YesNo( 'links_external', Settings::i()->links_external ) );
 		$form->add( new YesNo( 'posts_add_nofollow', Settings::i()->posts_add_nofollow, FALSE, array( 'togglesOn' => array( 'posts_add_nofollow_exclude' ) ), NULL, NULL, NULL, 'posts_add_nofollow' ) );
 		$form->add( new Stack( 'posts_add_nofollow_exclude', Settings::i()->posts_add_nofollow_exclude ? json_decode( Settings::i()->posts_add_nofollow_exclude ) : array(), FALSE, array( 'placeholder' => 'example.com' ), NULL, NULL, NULL, 'posts_add_nofollow_exclude' ) );

		$form->add( new Radio( 'email_filter_action', Settings::i()->email_filter_action, FALSE, array(
			'options'		=> array(
				'allow'			=> 'email_filter_action_allow',
				'moderate'		=> 'email_filter_action_moderate',
				'replace'		=> 'email_filter_action_replace'
			),
			'description'	=> array(
				'allow'			=> 'email_filter_action_allow_desc',
				'moderate'		=> 'email_filter_action_moderate_desc',
				'replace'		=> 'email_filter_action_replace_desc'
			),
			'toggles'	=> array( 'replace' => array( 'email_filter_replace_text' ) )
		), NULL, NULL, NULL, 'email_filter_action' ) );

		$form->add( new Text( 'email_filter_replace_text', Settings::i()->email_filter_replace_text, FALSE, array(), NULL, NULL, NULL, 'email_filter_replace_text' ) );


		/* Save values */
		if ( $values = $form->values() )
		{
            $values['ipb_url_whitelist'] = implode( ",", $values['ipb_url_whitelist'] );
			$values['ipb_url_blacklist'] = implode( ",", $values['ipb_url_blacklist'] );

			$noFollowExclude = array();

			if( is_array( $values['posts_add_nofollow_exclude'] ) )
			{
				foreach( $values['posts_add_nofollow_exclude'] as $url )
				{
					$noFollowExclude[] = preg_replace( "/^http(s)?:\/\//", '', $url );
				}
			}

			$values['posts_add_nofollow_exclude'] = json_encode( $noFollowExclude );
			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__url_filter_settings' );

			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&tab=urlFilters' ), 'saved' );
		}

		return $form;
	}



	/**
	 * Embed url filters
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageEmbedUrlFilters() : string
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_url' );

		/* Build Form */
		$form = new Form;
		$form->addHeader('ipb_embed_url_header');
		$form->add( new YesNo( 'ipb_embed_url_filter_option', Settings::i()->ipb_embed_url_filter_option, FALSE, array(
			'togglesOn' => array('ipb_embed_url_whitelist','embed_url_filter_any_action' ),
		) ) );

		$form->add( new Stack( 'ipb_embed_url_whitelist', Settings::i()->ipb_embed_url_whitelist ? explode( ",", Settings::i()->ipb_embed_url_whitelist ) : array(), FALSE, array(), NULL, NULL, NULL, 'ipb_embed_url_whitelist' ) );

		$form->add( new Radio( 'embed_url_filter_any_action', Settings::i()->embed_url_filter_any_action, FALSE, array(
			'options'		=> array(
				'allow'			=> 'embed_url_filter_any_allow',
				'moderate'		=> 'embed_url_filter_any_moderate'
			),
			'description'	=> array(
				'allow'			=> 'embed_url_filter_any_allow_desc',
				'moderate'		=> 'embed_url_filter_any_moderate_desc'
			)
		), NULL, NULL, NULL, 'embed_url_filter_any_action' ) );

		$form->addHeader('fb_ig_oembed_header');
		$form->add( new Text( 'fb_ig_oembed_appid', Settings::i()->fb_ig_oembed_appid, FALSE ) );
		$form->add( new Text( 'fb_ig_oembed_appsecret', Settings::i()->fb_ig_oembed_appsecret, FALSE ) );

		/* Save values */
		if ( $values = $form->values() )
		{
			$values['ipb_embed_url_whitelist'] = implode( ",", $values['ipb_embed_url_whitelist'] );
			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__url_filter_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&tab=embedUrlFilters' ), 'saved' );
		}

		return $form;
	}
	
	/**
	 * Word expansion
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageAcronymExpansion() : string
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_acronym' );
		
		/* Create the table */
		$table = new TableDb( 'core_acronyms', Url::internal( 'app=core&module=settings&controller=posting&tab=acronymExpansion' ) );
		$table->langPrefix = 'word_';
		$table->mainColumn = 'a_short';
		$table->rowClasses = array( 'a_long' => array( 'ipsTable_wrap' ) );
	
		/* Columns we need */
		$table->include = array( 'a_short', 'a_long', 'a_casesensitive' );
	
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'a_short';
		$table->sortDirection = $table->sortDirection ?: 'asc';
	
		/* Search */
		$table->quickSearch = 'a_short';
	
		/* Custom parsers */
		$table->parsers = array(
			'a_casesensitive'=> function( $val )
			{
				return ( $val ) ? "<i class='fa-solid fa-check'></i>" : "<i class='fa-solid fa-xmark'></i>";
			},
		);
	
		/* Specify the buttons */
		$table->rootButtons = array(
				'add'	=> array(
						'icon'		=> 'plus',
						'title'		=> 'word_add',
						'link'		=> Url::internal( 'app=core&module=settings&controller=posting&do=acronym' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('word_add') )
				)
		);
	
		$table->rowButtons = function( $row )
		{
			$return = array();
	
			$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> Url::internal( 'app=core&module=settings&controller=posting&do=acronym&id=' ) . $row['a_id'],
			);
	
			$return['delete'] = array(
					'icon'		=> 'times',
					'title'		=> 'delete',
					'link'		=> Url::internal( 'app=core&module=settings&controller=posting&do=deleteAcronym&id=' ) . $row['a_id'],
					'data'		=> array( 'delete' => '' ),
			);
	
			return $return;
		};
	
		return $table;
	}
	
	/**
	 * Manage poll settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _managePolls() : string
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_polls' );
		
		$form = new Form();
		$form->addHeader('poll_creation');
		$form->add( new Number( 'max_poll_questions', Settings::i()->max_poll_questions ) );
		$form->add( new Number( 'max_poll_choices', Settings::i()->max_poll_choices ) );
		$form->add( new YesNo( 'poll_allow_public', Settings::i()->poll_allow_public ) );
		$form->add( new YesNo( 'ipb_poll_only', Settings::i()->ipb_poll_only ) );
		
		$form->add( new YesNo( 'allow_poll_creation_after', (bool)Settings::i()->startpoll_cutoff, FALSE, array( 'togglesOn' => array( 'startpoll_cutoff' ) ) ) );
		$form->add( new Interval( 'startpoll_cutoff', Settings::i()->startpoll_cutoff, FALSE, array( 'valueAs' => Interval::HOURS, 'unlimited' => -1, 'unlimitedLang' => 'always', 'max' => 43800 ), NULL, NULL, NULL, 'startpoll_cutoff' ) ); #max is roughly 5 years
		
		$form->addHeader('poll_voting');
		$form->add( new YesNo( 'allow_creator_vote', Settings::i()->allow_creator_vote ) );
		$form->add( new YesNo( 'poll_allow_vdelete', Settings::i()->poll_allow_vdelete, FALSE ) );
		$form->add( new YesNo( 'allow_result_view', Settings::i()->allow_result_view, FALSE, array(), NULL, NULL, NULL, 'allow_result_view' ) );
		
		if ( $values = $form->values(TRUE) )
		{
			if ( ! $values['allow_poll_creation_after'] )
			{
				$values['startpoll_cutoff'] = 0;
			}
			
			unset( $values['allow_poll_creation_after'] );
			
			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__poll_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&tab=polls' ), 'saved' );
		}
		
		return Theme::i()->getTemplate( 'forms' )->blurb( 'polls_blurb', TRUE, TRUE ) . $form;
	}
	
	/**
	 * Add/Edit Profanity Filter
	 *
	 * @return	void
	 */
	public function profanity() : void
	{
		/* Permission check */
		Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );
		
		/* Init */
		$current = NULL;
		if ( Request::i()->id )
		{
			try
			{
				$current = Profanity::load( Request::i()->id );
			}
			catch ( OutOfRangeException $e ) { }
		}
	
		/* Build form */
		$form = Profanity::form( $current );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Upload */
			if ( isset( $values['profanity_upload'] ) and $values['profanity_upload'] )
			{
				/* Move it to a temporary location */
				$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
				move_uploaded_file( $values['profanity_upload'], $tempFile );
									
				/* Initate a redirector */
				Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&do=importProfanity' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ) ) )->csrf() );
			}
			
			/* Normal */
			else
			{
				if ( $values['profanity_type'] )
				{
					if ( $values['profanity_action'] == 'swap' AND !$values['profanity_swop'] )
					{
						$form->error = Member::loggedIn()->language()->addToStack( 'profanity_add_error' );
					}
					else
					{
						$save = array(
							'type'		=> $values['profanity_type'],
							'swop'		=> $values['profanity_swop'],
							'm_exact'	=> $values['profanity_m_exact'],
							'action'	=> $values['profanity_action'],
							'min_posts' => $values['profanity_min_posts']
						);
						
						Profanity::createFromForm( $save, $current );
						
						if ( $current )
						{
							Session::i()->log( 'acplog__profanity_edited' );
						}
						else
						{
							Session::i()->log( 'acplog__profanity_added' );
						}
	
						Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&tab=profanityFilters' ), 'saved' );
					}
				}
				else
				{
					$form->error = Member::loggedIn()->language()->addToStack('profanity_add_error');
				}
			}
	
		}
	
		/* Display */
		Output::i()->title	 		= Member::loggedIn()->language()->addToStack('profanity');
		Output::i()->breadcrumb[]	= array( NULL, Output::i()->title );
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( Output::i()->title, $form, FALSE );
	}
	
	/**
	 * Download Profanity Filters
	 *
	 * @return	void
	 */
	public function downloadProfanity() : void
	{
		/* Permission Check */
		Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );
		
		$xml = new XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );
		$xml->startElement('badwordexport');
		$xml->startElement('badwordgroup');
		foreach ( Profanity::getProfanity() as $profanity )
		{
			$xml->startElement('badword');
			
			$xml->startElement('type');
			$xml->text( $profanity->type );
			$xml->endElement();
			
			$xml->startElement('swop');
			$xml->text( $profanity->swop );
			$xml->endElement();
			
			$xml->startElement('m_exact');
			$xml->text( $profanity->m_exact );
			$xml->endElement();
			
			$xml->startElement('action');
			$xml->text( $profanity->action );
			$xml->endElement();
			
			$xml->endElement();
		}
		$xml->endElement();
		$xml->endElement();
		$xml->endDocument();
		
		Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', sprintf( Member::loggedIn()->language()->get('profanity_download_name'),  Settings::i()->board_name ) . '.xml' ) ) );
	}
	
	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	protected function importProfanity() : void
	{
		Session::i()->csrfCheck();
		
		if ( !file_exists( Request::i()->file ) or md5_file( Request::i()->file ) !== Request::i()->key )
		{
			Output::i()->error( 'generic_error', '3C256/1', 500, '' );
		}
		
		$url = Url::internal( 'app=core&module=settings&controller=posting&do=importProfanity' )->setQueryString( array( 'file' => Request::i()->file, 'key' =>  Request::i()->key ) )->csrf();
		Output::i()->output = new MultipleRedirect(
			$url,
			function( $data )
			{
				$data = intval( $data );

				/* Open XML file */
				$xml = XMLReader::safeOpen( Request::i()->file );
				$xml->read(); //badwordexport
				$xml->read(); //badwordexport
				$xml->read(); //badwordgroup
				$xml->read(); //badwordgroup
				$xml->read();
				
				/* Skip */
				for ( $i = 0; $i < $data; $i++ )
				{
					$xml->next();
					if ( !$xml->read() or $xml->name != 'badword' )
					{
						return NULL;
					}
				}
								
				/* Import */
				$save = array();
				$xml->read();
				$xml->read();
				$save['type'] = $xml->readString();
				$xml->next();
				$xml->read();
				$save['swop'] = $xml->readString();
				$xml->next();
				$xml->read();
				$save['m_exact'] = $xml->readString();
				$xml->next();
				$xml->read();
				$save['action'] = $xml->readString();
				try
				{
					$current = Profanity::load( $save['type'], 'type' );
					Profanity::createFromForm( $save, $current );
				}
				catch ( OutOfRangeException $e )
				{
					Profanity::createFromForm( $save );
				}
							
				/* Move to next */
				return array( ++$data, Member::loggedIn()->language()->get('processing') );
			},
			function()
			{
				unset( Store::i()->languages );

				@unlink( Request::i()->file );

				Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&tab=profanityFilters' ) );
			}
		);
	}
	
	/**
	 * Add/Edit Acronym
	 *
	 * @return	void
	 */
	public function acronym() : void
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_acronym' );
		
		$current = NULL;
	
		if ( Request::i()->id )
		{
			$current = Acronym::load( Request::i()->id );
		}
	
		/* Build form */
		$form = Acronym::form( $current );
	
		if ( $values = $form->values() )
		{
			Acronym::createFromForm( $values, $current );
			
			if ( $current )
			{
				Session::i()->log( 'acplog__acronym_edited' );
			}
			else
			{
				Session::i()->log( 'acplog__acronym_added' );
			}

			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=posting&tab=acronymExpansion' ), 'saved' );
		}
	
		/* Display */
		Output::i()->title	 		= Member::loggedIn()->language()->addToStack('word_expansion');
		Output::i()->breadcrumb[]	= array( NULL, Output::i()->title );
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( Output::i()->title, $form, FALSE );
	}
	
	/**
	 * Delete Profanity Filter
	 *
	 * @return	void
	 */
	public function deleteProfanityFilters() : void
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		Profanity::load( Request::i()->id )->delete();
		
		Session::i()->log( 'acplog__profanity_deleted' );

		/* And redirect */
		Output::i()->redirect( Url::internal( "app=core&module=settings&controller=posting&tab=profanityFilters" ) );
	}
	
	/**
	 * Delete Acronym
	 *
	 * @return	void
	 */
	public function deleteAcronym() : void
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_acronym' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		Acronym::load( Request::i()->id )->delete();
		
		Session::i()->log( 'acplog__acronym_deleted' );

		/* And redirect */
		Output::i()->redirect( Url::internal( "app=core&module=settings&controller=posting&tab=acronymExpansion" ) );
	}
	
	/**
	 * Rebuild URL refs
	 *
	 * @return	void
	 */
	public function rebuildUrlRels() : void
	{
		Session::i()->csrfCheck();
		
		/* Remove any existing rebuilds */
		Db::i()->delete( 'core_queue', Db::i()->in( '`key`', array( 'RebuildUrlRels' ) ) );

		/* Unset task datastore */
		unset( Store::i()->currentUrlRefRebuild );

		foreach ( Content::routedClasses( FALSE, TRUE ) as $class )
		{
			if( isset( $class::$databaseColumnMap['content'] ) )
			{
				try
				{
					Task::queue( 'core', 'RebuildUrlRels', array( 'class' => $class ), 4 );
				}
				catch( OutOfRangeException $ex ) { }
			}
		}
		
		/* And redirect */
		Output::i()->redirect( Url::internal( "app=core&module=settings&controller=posting" ), 'refurls_rebuilt' );
	}

	/**
	 * @return string
	 */
	protected function _manageSimplifiedMode() : string
	{
		Dispatcher::i()->checkAcpPermission( 'posting_manage_simplified_mode' );
		$form = new Form();
		$form->attributes['data-controller'] = 'core.admin.core.editorRestrictions';

		$form->addHtml( Theme::i()->getTemplate( 'forms' )->blurb( 'editor_simplified_mode_desc', true, true ) );

		$dependencies = Form\Editor::getRestrictionDependencies();
		foreach ( Form\Editor::getAllRestrictions() as $restriction )
		{
			if ( $restriction === 'giphy' && !Settings::i()->giphy_enabled )
			{
				continue;
			}

			if ( isset( $dependencies[ $restriction ] ) )
			{
				$firstLine = '';
				try
				{
					$firstLine = Member::loggedIn()->language()->get( 'editor_r__' . $restriction . '__desc' ) . "<br>";
				}
				catch( UnderflowException ) { }

				Member::loggedIn()->language()->words['editor_r__' . $restriction . '_desc'] = $firstLine . sprintf( Member::loggedIn()->language()->get( 'editor_r__dependency' ), Member::loggedIn()->language()->addToStack( 'editor_r__' . $dependencies[$restriction] ) );
			}

			$dependents = [];
			foreach ( $dependencies as $dependent => $dependency )
			{
				if ( $dependency === $restriction )
				{
					$dependents[] = $form->id . '_' . "editor_r__" . $dependent;
				}
			}

			$form->add( new Form\Select( 'editor_r__' . $restriction, Form\Editor::getRestrictionLevel( $restriction ), false, [
				'options' => [
					-1 => 'editor_mode_all',
					0 => 'editor_mode_regular',
					1 => 'editor_mode_advanced',
					2 => 'editor_mode_none'
				],
				'toggles' => [
					"1" => $dependents,
					"0" => $dependents,
					"-1" => $dependents,
				],
			], customValidationCode: function( $val ) use ( $restriction, $dependencies ) {
				$dependency = $dependencies[$restriction] ?? null;
				if ( !$dependency )
				{
					return;
				}

				/* We don't care if the value wasn't changed */
				if ( (int) $val == Form\Editor::getRestrictionLevel( $restriction ) )
				{
					return;
				}

				$depkey = "editor_r__" . $dependency;

				/* If this is a dependant and it's set to a looser restriction than it's parent, we need to throw an error */
				if ( intval( $val ) < intval( isset( Request::i()->$depkey ) ? Request::i()->$depkey : Form\Editor::getRestrictionLevel( $dependency ) ) )
				{
					throw new InvalidArgumentException( 'dependent_less_strict_than_dependency' );
				}
			} ) );
		}

		$form->addHeader( 'editor_extensions' );
		$form->add( new Form\TextArea( 'editor_extension_restrictions', json_encode( Form\Editor::getRestrictionSetting() ), id: 'el_editor_extension_restrictions_input' ) );

		if ( $values = $form->values() )
		{
			foreach ( $values as $k => $value )
			{
				if ( str_starts_with( $k, 'editor_r__' ) and $restriction = preg_replace( '/^editor_r__/i','', $k ) and (int) $value != Form\Editor::getRestrictionLevel( $restriction ) )
				{
					Form\Editor::setRestrictionLevel( $restriction, (int) $value );
				}
			}

			if ( isset( $values['editor_extension_restrictions'] ) )
			{
				$settingRaw = json_decode( $values['editor_extension_restrictions'], true );
				foreach ( $settingRaw as $level => $restrictions )
				{
					foreach ( $restrictions as $restriction )
					{
						/* The JS will add this 'removed' key (that doesn't exist on the backend) when a restriction's custom extension is no longer active */
						if ( $level == 'removed' )
						{
							Form\Editor::removeRestriction( $restriction );
						}
						else if ( preg_match( "/^ipsCustom(Node|Mark|Extension)__/", $restriction ) )
						{
							Form\Editor::setRestrictionLevel( $restriction, (int) $level );
						}
					}
				}
			}

			Output::i()->redirect( Url::internal( "app=core&module=settings&controller=posting&tab=simplifiedMode" ), 'saved' );
		}

		return (string) $form;
	}

	/**
	 * Get a restriction input for an editor customization
	 */
	protected function getRestrictionInput() : void
	{
		if ( !Request::i()->isAjax() )
		{
			Output::i()->redirect( Url::internal( "app=core&module=settings&controller=posting&tab=simplifiedMode" ) );
		}

		if ( !Request::i()->restrictionKey or !Request::i()->restrictionLang or !Request::i()->restrictionDesc )
		{
			Output::i()->json( ['message' => 'Missing restrictionKey or restrictionLang parameter'], 400 );
		}

		$keys = explode( ',', Request::i()->restrictionKey );
		$langs = json_decode( Request::i()->restrictionLang, true );
		$descs = json_decode( Request::i()->restrictionDesc, true );

		if ( !is_array( $langs ) or !is_array( $descs ) or count( $keys ) != count( $langs ) or count( $keys ) != count( $descs ) )
		{
			Output::i()->json( [ 'message' => 'Mismatching number of restriction keys and langs' ], 400 );
		}

		$langs = array_values( $langs );
		$descs = array_values( $descs );

		$content = "";
		for ( $i = 0; $i < count( $keys ); $i++ )
		{
			$key = $keys[$i];
			$lang = htmlspecialchars( $langs[$i] );
			$desc = trim( htmlspecialchars( $descs[$i] ) );
			$val = Form\Editor::getRestrictionLevel( $key );
			Member::loggedIn()->language()->words[ $key ] = $lang;
			if ( !empty( $desc ) )
			{
				Member::loggedIn()->language()->words[ $key . '_desc' ] = $desc;
			}

			$input = new Form\Select( $key, $val, false, [
				'options' => [
					-1 => 'editor_mode_all',
					0  => 'editor_mode_regular',
					1  => 'editor_mode_advanced',
					2  => 'editor_mode_none'
				]
			] );

			$content .= $input . "\n";
		}

		Member::loggedIn()->language()->parseOutputForDisplay( $content );
		Output::i()->json( ['content' => $content] );
	}
}