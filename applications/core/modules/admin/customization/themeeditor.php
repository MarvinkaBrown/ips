<?php
/**
 * @brief		themeeditor
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		19 Feb 2024
 */

namespace IPS\core\modules\admin\customization;

use IPS\Dispatcher;
use IPS\Node\Controller;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\Theme\Editor\Category;
use IPS\Theme\Editor\Setting;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * themeeditor
 */
class themeeditor extends Controller
{
	/**
	 * @var bool
	 */
	public static bool $csrfProtected = true;

	/**
	 * @var Theme|null
	 */
	protected ?Theme $theme = null;

	/**
	 * Node Class
	 *
	 * @var string
	 */
	protected string $nodeClass = Category::class;

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
		Dispatcher::i()->checkAcpPermission( 'theme_sets_manage' );

		/* Set the scope */
		if ( isset( Request::i()->set_id ) )
		{
			$this->theme = Theme::load( Request::i()->set_id );
			$this->url = $this->url->setQueryString( 'set_id', $this->theme->id );
		}

		$this->tabs = array(
			'settings' => 'dev_themeeditor_settings',
			'categories' => 'dev_themeeditor_categories'
		);

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

		/* Yummy free food */
		Output::i()->breadcrumb = array(
			array(
				Url::internal('app=core&module=customization&controller=themes'),
				'menu__' . Dispatcher::i()->application->directory . '_' . Dispatcher::i()->module->key
			)
		);

		Output::i()->breadcrumb[] = array(
			Url::internal('app=core&module=customization&controller=themeeditor&set_id=' . $this->theme->_id  ),
			'themeeditor_settings'
		);

		Output::i()->breadcrumb[] = array( null, $this->theme->_title );

		parent::execute();
	}

	protected function manage() : void
	{
		/* Work out output */
		$methodFunction = '_manage' . IPS::mb_ucfirst( $this->activeTab );
		$activeTabContents = $this->$methodFunction();

		/* If this is an AJAX request, just return it */
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
			return;
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'dev_themeeditor' );
		Output::i()->output = Theme::i()->getTemplate( 'global' )->tabs( $this->tabs, $this->activeTab, $activeTabContents, $this->url );
	}

	/**
	 * Editor Settings tab
	 * @return string
	 */
	protected function _manageSettings() : string
	{
		$table = new TableDb( 'core_theme_editor_settings', $this->url, array( array( 'setting_set_id=?', $this->theme->id ) ) );
		$table->include = [ 'setting_name', 'setting_type', 'setting_category_id' ];
		$table->noSort = $table->include;
		$table->rootButtons = [
			'add' => [
				'icon' => 'plus',
				'title' => 'add',
				'link' => $this->url->setQueryString( array( 'do' => 'form', 'subnode' => 1 ) )
			]
		];

		$table->rowButtons = function( $row )
		{
			return Setting::constructFromData( $row )->getButtons( $this->url, true );
		};

		$table->parsers = [
			'setting_name' => function( $val, $row )
			{
				return Member::loggedIn()->language()->addToStack( Setting::$titleLangPrefix . $row['setting_key'] );
			},
			'setting_type' => function( $val )
			{
				return Member::loggedIn()->language()->addToStack( 'themeeditor_setting_type__' . $val );
			},
			'setting_category_id' => function( $val, $row )
			{
				return Setting::constructFromData( $row )->parent()->_title;
			}
		];

		return (string) $table;
	}

	/**
	 * Editor Categories tab
	 * @return string
	 */
	protected function _manageCategories() : string
	{
		$table = new TableDb( 'core_theme_editor_categories', $this->url, array( array( 'cat_set_id=?', $this->theme->id ) ) );
		$table->include = [ 'cat_name' ];
		$table->noSort = [ 'cat_name' ];
		$table->langPrefix = 'themeeditor_';
		$table->rootButtons = [
			'add' => [
				'icon' => 'plus',
				'title' => 'add',
				'link' => $this->url->setQueryString( array( 'do' => 'form' ) )
			]
		];

		$table->rowButtons = function( $row )
		{
			$buttons = Category::constructFromData( $row )->getButtons( $this->url );
			if( isset( $buttons['add'] ) )
			{
				unset( $buttons['add'] );
			}
			if( isset( $buttons['child'] ) )
			{
				unset( $buttons['child'] );
			}
			return $buttons;
		};

		$table->parsers = [
			'cat_name' => function( $val, $row )
			{
				return "<i class='" . Category::constructFromData( $row )->icon() ."'></i> " . Member::loggedIn()->language()->addToStack( Category::$titleLangPrefix . $row['cat_key'] );
			}
		];

		return (string) $table;
	}
}