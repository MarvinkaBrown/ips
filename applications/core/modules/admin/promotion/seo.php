<?php
/**
 * @brief		SEO
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 Jun 2013
 */

namespace IPS\core\modules\admin\promotion;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Dispatcher\Front;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use IPS\Widget;
use RuntimeException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function strpos;
use const IPS\CIC;
use const IPS\CIC2;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * SEO
 */
class seo extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * @brief	Active tab
	 */
	protected string $activeTab	= '';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'seo_furls' );

		/* Get tab content */
		$this->activeTab = Request::i()->tab ?: 'urls';

		Output::i()->sidebar['actions']['rebuildsitemap'] = array(
			'link'	=> Url::internal( 'app=core&module=promotion&controller=seo&do=rebuildSitemap' )->csrf(),
			'title'	=> 'rebuild_sitemap',
			'icon' => 'history'
		);

		parent::execute();
	}

	/**
	 * SEO Settings
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
		
		/* Build tab list */
		$tabs = array();
		if ( !CIC AND Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'seo_furls' ) )
		{
			$tabs['urls']		= 'seo_tab_furls';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'seo_sitemap' ) )
		{
			$tabs['sitemap']	= 'seo_tab_sitemap';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'seo_meta' ) )
		{
			$tabs['metatags']	= 'seo_tab_metatags';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'seo_furls' ) )
		{
			$tabs['robotstxt']	= 'seo_tab_robotstxt';
		}
			
		/* Display */
		if ( $activeTabContents )
		{			
			Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_promotion_seo');
			Output::i()->output 	= Theme::i()->getTemplate( 'global' )->tabs( $tabs, $this->activeTab, $activeTabContents, Url::internal( "app=core&module=promotion&controller=seo" ) );
		}
	}

	/**
	 * Get setting to enable htaccess friendly URLs
	 *
	 * @param Form $form	Form to add the setting to
	 * @return	void
	 */
	public static function htaccessSetting( Form $form ) : void
	{
		$isApache = mb_stripos( $_SERVER['SERVER_SOFTWARE'], 'apache' ) !== FALSE;
		$form->add( new YesNo( 'htaccess_mod_rewrite', Settings::i()->htaccess_mod_rewrite, TRUE, array(), NULL, NULL, NULL, 'htaccess_mod_rewrite' ) );
		if ( !$isApache )
		{
			Member::loggedIn()->language()->words['htaccess_mod_rewrite_desc']		= Member::loggedIn()->language()->get('htaccess_mod_rewrite_desc_na');
		}
		if ( ( !isset( Request::i()->htaccess_mod_rewrite ) and Settings::i()->htaccess_mod_rewrite ) or Request::i()->htaccess_mod_rewrite or Request::i()->htaccess_mod_rewrite_checkbox )
		{
			try
			{
				$furlDefinition = Url::furlDefinition();
				$response = Url::external( Settings::i()->base_url . $furlDefinition['login']['friendly'] )->request( NULL, NULL, FALSE )->get();
				if ( !in_array( mb_substr( $response->httpResponseCode, 0, 1 ), array( '2', '3' ) ) and ( Settings::i()->site_online OR $response->httpResponseCode != 503 ) )
				{
					Member::loggedIn()->language()->words['htaccess_mod_rewrite_warning']	= Member::loggedIn()->language()->get( $isApache ? 'htaccess_mod_rewrite_err' : 'htaccess_mod_rewrite_err_na' );
				}
			}
			catch( \IPS\Http\Request\Exception $e )
			{
				Member::loggedIn()->language()->words['htaccess_mod_rewrite_warning']	= Member::loggedIn()->language()->get( $isApache ? 'htaccess_mod_rewrite_err' : 'htaccess_mod_rewrite_err_na' );
			}
		}
	}

	/**
	 * Manage robots.txt
	 *
	 * @return	string
	 */
	protected function _manageRobotstxt() : string
	{
		$value = Settings::i()->robots_txt;
		if ( ! in_array( Settings::i()->robots_txt, ['off', 'default'] ) )
		{
			$value = 'custom';
		}

		$form = new Form;

		if ( isset( Request::i()->_saved ) )
		{
			/* Do we have an existing robots.txt file in the community directory? */
			$dir = trim( str_replace( 'admin/index.php', '', $_SERVER['PHP_SELF'] ), '/' );

			/* Oh, the community is in a directory, so we need the user to download the file and manually upload it to root */
			if ( $dir )
			{
				$rootUrl = trim( str_replace( $dir, '', Url::baseUrl() ), '/' );
				if ( Settings::i()->robots_txt === 'off' )
				{
					$form->addMessage( Member::loggedIn()->language()->addToStack( 'use_robotstxt_warning_off', FALSE, ['sprintf' => [$rootUrl]] ), 'ipsMessage ipsMessage--warning' );
				}
				else
				{
					$form->addMessage( Member::loggedIn()->language()->addToStack( 'use_robotstxt_warning_download', FALSE, ['sprintf' => [$rootUrl]] ), 'ipsMessage ipsMessage--warning' );
				}
			}
			else if ( ! CIC2 AND file_exists( \IPS\ROOT_PATH . '/robots.txt' ) )
			{
				if ( Settings::i()->robots_txt === 'off' )
				{
					$form->addMessage( Member::loggedIn()->language()->addToStack( 'use_robotstxt_warning_existing_off', FALSE, ['sprintf' => [ rtrim( Url::baseUrl(), '/' ) ] ] ), 'ipsMessage ipsMessage--warning' );
				}
				else
				{
					$form->addMessage( Member::loggedIn()->language()->addToStack( 'use_robotstxt_warning_existing_download', FALSE, ['sprintf' => [ rtrim( Url::baseUrl(), '/' ) ] ] ), 'ipsMessage ipsMessage--warning' );
				}
			}
		}

		$form->add( new Radio( 'use_robotstxt', $value, FALSE, [
			'options' => [
				'off'     => 'use_robotstxt_off',
				'default' => 'use_robotstxt_default',
				'custom'  => 'use_robotstxt_custom'
				],
			'toggles' => [
				'custom' => [ 'use_robotstxt_custom_editor' ]
			]
		], NULL, NULL, NULL, 'use_robotstxt' ) );

		$form->add( new Codemirror( 'use_robotstxt_custom_editor', ( $value === 'custom' ? Settings::i()->robots_txt  : '' ), FALSE, [], NULL, NULL, NULL, 'use_robotstxt_custom_editor' ) );

		$form->add( new YesNo( 'seo_reduce_links', Settings::i()->seo_reduce_links, TRUE, [], NULL, NULL, NULL, 'seo_reduce_links' ) );

		/* Are we saving? */
		if ( $values = $form->values() )
		{
			$value = $values['use_robotstxt'];

			if ( $values['use_robotstxt'] === 'custom' )
			{
				$value = $values['use_robotstxt_custom_editor'];
			}

			$form->saveAsSettings( [ 'robots_txt' => $value ] );
			
			Session::i()->log( 'acplog__robotstxt_edited' );

			Output::i()->redirect( Url::internal( "app=core&module=promotion&controller=seo&tab=robotstxt&_saved=1" ) );
		}

		return $form;
	}

	/**
	 * Downloads the robots.txt file
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function downloadRobotstxt() : void
	{
		if ( Settings::i()->robots_txt != 'off' )
		{
			if ( Settings::i()->robots_txt == 'default' )
			{
				Output::i()->sendOutput( Front::robotsTxtRules(), 200, 'application/x-robotstxt', array( 'Content-Disposition' => 'attachment; filename=robots.txt' ) );
			}
			else
			{
				Output::i()->sendOutput( Settings::i()->robots_txt, 200, 'application/x-robotstxt', array( 'Content-Disposition' => 'attachment; filename=robots.txt' ) );
			}
		}
	}

	/**
	 * Manage FURL definitions
	 *
	 * @return	string
	 */
	protected function _manageUrls() : string
	{
		$form = new Form;

		$form->add( new YesNo( 'use_friendly_urls', Settings::i()->use_friendly_urls, TRUE, array( 'togglesOn' => array( 'htaccess_mod_rewrite', 'seo_r_on' ), 'togglesOff' => array( 'use_friendly_urls_warning' ) ), NULL, NULL, NULL, 'use_friendly_urls' ) );
		
		static::htaccessSetting( $form );

		$form->add( new YesNo( 'seo_r_on', Settings::i()->seo_r_on, TRUE, array( 'togglesOff' => array( 'seo_r_on_warning' ) ), NULL, NULL, NULL, 'seo_r_on' ) );
		
		/* Are we saving? */
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();

			/* Clear front navigation data store, otherwise it could still contain the non-rewrite furl for some items */
			unset( Store::i()->frontNavigation );

			Session::i()->log( 'acplogs__seo_furl_settings' );
			
			/* Clear Sidebar Caches */
			Widget::deleteCaches();
		}

		return $form;
	}

	/**
	 * Sitemap settings
	 *
	 * @return	string
	 */
	protected function _manageSitemap() : string
	{
		/* Init */
		$form = new Form;
		$form->add( new FormUrl( 'sitemap_url', Settings::i()->sitemap_url ?: Settings::i()->base_url . 'sitemap.php', FALSE ) );

		/* Get extension settings */
		$useRecommendedSettings = TRUE;
		$extraSettings = array();
		$toggles = array();
		$recommendedSettings = array();
		foreach ( Application::allExtensions( 'core', 'Sitemap', FALSE, 'core' ) as $extKey => $extension )
		{
			$toggles[] = "form_header_sitemap_{$extKey}";
			$recommendedSettings = array_merge( $recommendedSettings, $extension->recommendedSettings );
			foreach ( $extension->settings() as $k => $setting )
			{
				if ( $setting->value != $extension->recommendedSettings[ $k ] )
				{
					$useRecommendedSettings = FALSE;
				}
				
				$extraSettings[ $extKey ][] = $setting;
				$toggles[] = $setting->htmlId;
			}
		}
				
		/* Build form */
		$form->add( new YesNo( 'sitemap_configuration_info', $useRecommendedSettings, FALSE, array( 'togglesOff' => $toggles ) ) );
		foreach ( $extraSettings as $header => $settings )
		{
			$form->addHeader( 'sitemap_' . $header );
			foreach ( $settings as $setting )
			{
				$form->add( $setting );
			}
		}

		/* Are we saving? */
		if ( $values = $form->values() )
		{
			if ( $values['sitemap_configuration_info'] )
			{
				$values = array_merge( $values, $recommendedSettings );
			}

			try
			{
				if( !( $values['sitemap_url'] instanceof Url ) )
				{
					throw new RuntimeException;
				}

				$response = $values['sitemap_url']->setQueryString( 'testsettings', 1 )->request()->get();

				if ( $response->httpResponseCode != 200 or !mb_strpos( $values['sitemap_url'], "sitemap.php" ) )
				{
					$form->error = Member::loggedIn()->language()->addToStack( 'invalid_sitemap_url' );
				}

			}
			catch ( RuntimeException $e )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'form_url_bad' );
			}

			if( !$form->error )
			{
				$values['sitemap_url'] = (string) $values['sitemap_url'];

				foreach( Application::allExtensions( 'core', 'Sitemap', FALSE, 'core' ) as $extKey => $extension )
				{
					$extension->saveSettings( $values );
				}

				$form->saveAsSettings( array( 'sitemap_url' => $values['sitemap_url'] ) );

				Session::i()->log( 'acplogs__seo_sitemap_settings' );
			}
		}

		return Theme::i()->getTemplate( 'forms' )->blurb( 'sitemap_blurb', true, true ) . $form;
	}

	/**
	 * Get the meta tag tree
	 *
	 * @return	string
	 */
	protected function _manageMetatags() : string
	{
		/* Are we deleting? */
		if ( isset( Request::i()->delete ) )
		{
			/* Make sure the user confirmed the deletion */
			Request::i()->confirmedDelete();

			if( isset( Request::i()->root ) )
			{
				$meta	= Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', (int) Request::i()->root ) )->first();
				$tags	= array_diff_key( json_decode( $meta['meta_tags'], TRUE ), array( Request::i()->delete => 1 ) );

				Db::i()->update( 'core_seo_meta', array( 'meta_tags' => json_encode( $tags ) ), array( 'meta_id=?', (int) Request::i()->root ) );
			}
			else
			{
				Db::i()->delete( 'core_seo_meta', array( "meta_id=?", (int) Request::i()->delete ) );
			}

			unset( Store::i()->metaTags );

			if ( Request::i()->isAjax() )
			{
				return '';
			}
		}

		/* Show tree */
		$url	= Url::internal( "app=core&module=promotion&controller=seo&tab=metatags" );
		$output	= new Tree(
			$url,
			Member::loggedIn()->language()->addToStack('seo_tab_metatags'),
			/* Get Roots */
			function() use ( $url )
			{
				$rows = array();

				foreach ( Db::i()->select( '*', 'core_seo_meta' ) as $row )
				{
					$urlToDisplay = Theme::i()->getTemplate( 'promotion' )->metaTagUrl( trim( $row['meta_url'], '/' ) );

					$rows[ $row['meta_url'] ] = Theme::i()->getTemplate( 'trees' )->row( $url, $row['meta_id'], $urlToDisplay, TRUE, array(
						'edit'	=> array(
							'icon'		=> 'pencil',
							'title'		=> 'seo_meta_manage',
							'link'		=> Url::internal( "app=core&module=promotion&controller=seo&do=addMeta&id=" . $row['meta_id'] ),
							'hotkey'	=> 'e'
						),
						'delete'	=> array(
							'icon'		=> 'times-circle',
							'title'		=> 'delete',
							'link'		=> Url::internal( "app=core&module=promotion&controller=seo&tab=metatags&delete=" . $row['meta_id'] ),
							'data'		=> array( 'delete' => '' )
						)
					), "", NULL, NULL, FALSE, NULL, NULL, NULL, TRUE );
				}

				return $rows;
			},
			/* Get Row */
			function( $key, $root=FALSE ) use ( $url )
			{
				$meta	= Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', $key ) )->first();

				return Theme::i()->getTemplate( 'trees' )->row( $url, $key, $meta['meta_url'], TRUE, array(
					'edit'	=> array(
						'icon'		=> 'pencil',
						'title'		=> 'seo_meta_manage',
						'link'		=> Url::internal( "app=core&module=promotion&controller=seo&do=addMeta&id=" . $key ),
						'hotkey'	=> 'e'
					),
					'delete'	=> array(
						'icon'		=> 'times-circle',
						'title'		=> 'delete',
						'link'		=> Url::internal( "app=core&module=promotion&controller=seo&tab=metatags&delete=" . $key ),
						'data'		=> array( 'delete' => '' )
					)
				), '', NULL, NULL, $root );
			},
			/* Get Row's Parent ID */
			function( $id )
			{
				return NULL;
			},
			/* Get Children */
			function( $key ) use ( $url )
			{
				$meta	= Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', $key ) )->first();
				$tags	= json_decode( $meta['meta_tags'], TRUE );
				$rows	= array();

				if( is_array( $tags ) )
				{
					foreach ( $tags as $name => $content )
					{
						$rows[] = Theme::i()->getTemplate( 'trees' )->row( $url, $meta['meta_id'] . '-' . $name, $name, FALSE, array(
							'delete'	=> array(
								'icon'		=> 'times-circle',
								'title'		=> 'delete',
								'link'		=> Url::internal( $url . "&root={$key}&delete={$name}" ),
								'data'		=> array( 'delete' => '' )
							)
						), $content ?? Member::loggedIn()->language()->addToStack('meta_tag_acp_deleted') );
					}
				}

				return $rows;
			},
			/* Get Root Buttons */
			function()
			{
				return array(
					'add'		=> array(
						'icon'		=> 'plus',
						'title'		=> 'seo_meta_add',
						'link'		=> Url::internal( "app=core&module=promotion&controller=seo&do=addMeta" ),
					),
					'launch'	=> array(
						'icon'		=> 'magic',
						'title'		=> 'metatag_live_editor',
						'link'		=> Url::internal( "app=core&module=system&controller=metatags", "front" ),
						'target'	=> '_blank'
					),
				);
			},
			FALSE,
			TRUE,
			TRUE
		);

        /* Output or return */
        if ( ! Request::i()->isAjax() )
        {
	        $output	= Theme::i()->getTemplate( 'forms' )->blurb( "what_is_a_metatag", TRUE, TRUE ) . $output;
	    }

		return $output;
	}

	/**
	 * Form to add or edit a meta tag
	 *
	 * @return void
	 */
	public function addMeta() : void
	{
		$url	= NULL;
		$tags	= array();
		$title	= NULL;

		/* If we have a URL, load up the existing tags for it as we are "editing" */
		if( isset( Request::i()->id ) )
		{
			$meta	= Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', (int) Request::i()->id ) )->first();
			$tags	= json_decode( $meta['meta_tags'], TRUE );
			$url	= $meta['meta_url'];
			$title	= $meta['meta_title'];
		}

		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--add-meta i-padding_3';
		$form->add( new Text( 'metatag_url', $url, FALSE, array( 'placeholder' => 'profile/*' ), NULL, Settings::i()->base_url ) );
		$form->hiddenValues['original_url']	= $url;

		$form->add( new Text( 'metatag_title', $title, FALSE ) );

		/* Now add the rows */
		$matrix = new Matrix();
		$matrix->manageable = TRUE;
		$matrix->langPrefix = 'metatags_';
		$matrix->columns = array(
			'name'		=> function( $key, $value, $data )
			{
				return new Select( $key,
					$data ? $data['name'] : '',
					TRUE,
					array( 'options' => array( 'keywords' => 'meta_keywords', 'description' => 'meta_description', 'robots' => 'meta_robots', 'other' => 'meta_other' ), 'toggles' => array( 'other' => array( 'other_' . preg_replace( "/[^a-zA-Z0-9\-_]/", "_", $key ) ) ), 'userSuppliedInput' => 'other' ),
					NULL,
					NULL,
					NULL,
					$key
				);
			},
			'content'	=> function( $key, $value, $data )
			{
				return new TextArea( $key, $data ? $data['content'] : '', FALSE, array( 'nullLang' => 'meta_tag_null_acp_form' ) );
			},
		);
		
		/* Add rows */
		if( count( $tags ) )
		{
			foreach( $tags as $tagName => $tagValue )
			{
				$matrix->rows[]	= array( 'name' => $tagName, 'content' => $tagValue );
			}
		}

		$form->addMatrix( 'metatag_tags', $matrix );

		/* Are we saving? */
		if ( $values = $form->values() )
		{
			$tags	= array();
			$url	= $values['metatag_url'] ?: '/';
			$title	= $values['metatag_title'];

			foreach( $values['metatag_tags'] as $index => $data )
			{
				if( !$data['name'] OR $data['content'] === '' )
				{
					continue;
				}

				$tags[ $data['name'] ]	= $data['content'];
			}

			Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', $url ) );
			Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', Request::i()->original_url ) );

			if( $title or count( $tags ) )
			{
				Db::i()->insert( 'core_seo_meta', array( 'meta_url' => $url, 'meta_title' => $title, 'meta_tags' => json_encode( $tags ) ) );
			}

			Session::i()->log( 'acplogs__seo_metatag_settings' );
			
			unset( Store::i()->metaTags );

			Output::i()->redirect( Url::internal( "app=core&module=promotion&controller=seo&tab=metatags" ) );
		}

		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'promotion/meta.css', 'core', 'admin' ) );
		Output::i()->title  = Member::loggedIn()->language()->addToStack('seo_meta_add');
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'seo_meta_add', $form );
	}
	
	/**
	 * Download .htaccess file
	 *
	 * @return	void
	 */
	protected function htaccess() : void
	{
		$dir = str_replace( 'admin/index.php', '', $_SERVER['PHP_SELF'] );
		$path = $dir . 'index.php';

		if( strpos( $dir, ' ' ) !== FALSE )
		{
			$dir = '"' . $dir . '"';
			$path = '"' . $path . '"';
		}

		$htaccess = <<<FILE
<IfModule mod_rewrite.c>
Options -MultiViews
RewriteEngine On
RewriteBase {$dir}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule \\.(js|css|jpeg|jpg|gif|png|ico|map|webp)(\\?|$) {$dir}404error.php [L,NC]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$path} [L]
</IfModule>
FILE;

		Output::i()->sendOutput( $htaccess, 200, 'application/x-htaccess', array( 'Content-Disposition' => 'attachment; filename=.htaccess' ) );
	}

	/**
	 * Trigger the sitemap rebuild
	 */
	protected function rebuildSitemap() : void
	{
		Session::i()->csrfCheck();
		
		/* truncate sitemap */
		Db::i()->delete( 'core_sitemap' );

		$extensions	= Application::allExtensions( 'core', 'Sitemap', new Member, 'core' );
		foreach ( $extensions as  $k => $extension )
		{
			Task::queue( 'core', 'RebuildSitemap', array( 'extensionKey' => $k ), 5 );
		}

		Output::i()->redirect( Url::internal( "app=core&module=promotion&controller=seo" ), 'rebuild_sitemap_initialized' );
	}

}