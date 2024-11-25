<?php
/**
 * @brief		Custom templates
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 May 2023
 */

namespace IPS\core\modules\admin\customization;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\Theme\CustomTemplate;
use function defined;
use function htmlspecialchars;
use function implode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * themes
 */
class customtemplates extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 *
	 * @var CustomTemplate|string
	 */
	protected string $nodeClass = CustomTemplate::class;
	
	/**
	 * @brief	If true, will prevent any item from being moved out of its current parent, only allowing them to be reordered within their current parent
	 */
	protected bool $lockParents = TRUE;
	
	/**
	 * Title can contain HTML?
	 */
	public bool $_titleHtml = TRUE;

	/**
	 * Description can contain HTML?
	 */
	public bool $_descriptionHtml = TRUE;
	
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
			$this->nodeClass::$scopeKey = 'set_id';
			$this->nodeClass::$scopeValue = Request::i()->set_id;
		}
		else if ( isset( Request::i()->application ) )
		{
			$this->nodeClass::$scopeKey = 'appKey';
			$this->nodeClass::$scopeValue = Request::i()->appKey;
		}

		/* Yummy free food */
		Output::i()->breadcrumb = array(
			array(
				Url::internal('app=core&module=customization&controller=themes'),
				'menu__' . Dispatcher::i()->application->directory . '_' . Dispatcher::i()->module->key
			)
		);

		if ( $this->nodeClass::$scopeKey === 'set_id' )
		{
			Output::i()->breadcrumb[] = array(
				Url::internal('app=core&module=customization&controller=customtemplates&' . $this->nodeClass::$scopeKey. '=' . $this->nodeClass::$scopeValue  ),
				'custom_templates'
			);

			Output::i()->breadcrumb[] = array(
				NULL,
				Member::loggedIn()->language()->addToStack( 'core_theme_set_title_' . $this->nodeClass::$scopeValue )
			);
		}
		else
		{
			/* @todo logic for type=themes/apps */
		}

		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( $this->nodeClass::$scopeKey === 'set_id' )
		{
			Output::i()->output = Theme::i()->getTemplate('forms')->blurb( Member::loggedIn()->language()->addToStack( 'template_custom_theme_blurb', NULL, [ 'sprintf' => [ Theme::load( $this->nodeClass::$scopeValue )->_title ] ] ) );
		}

		parent::manage();
	}

	/**
	 * Get and return available template parameters
	 *
	 * @return void
	 */
	protected function ajaxGetAvailableParams(): void
	{
		$template = new CustomTemplate();
		$template->hookpoint = Request::i()->hookpoint;
		$template->hookpoint_type = Request::i()->hookpoint_type;

		Output::i()->json( implode( ', ', $template->availableParams( null, true ) ) );
	}

	protected function ajaxGetTemplate(): void
	{
		$template = new CustomTemplate();
		$template->hookpoint = Request::i()->hookpoint;
		$masterTemplate = trim( $template->hookedTemplate()['template_content'] );

		$masterTemplateEncoded = preg_replace( '#data-ips-hook=&quot;(\w+?)&quot;#', '<strong style="background-color: #FFFF00">data-ips-hook=&quot;$1&quot;</strong>', htmlspecialchars( $masterTemplate ) );
		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Theme::i()->getTemplate( 'customization', 'core', 'admin' )->viewTemplate( $masterTemplateEncoded ) ) );
	}

	/**
	 * Redirect after save
	 *
	 * @param Model|null $old A clone of the node as it was before or NULL if this is a creation
	 * @param Model $new The node now
	 * @param bool|string $lastUsedTab The tab last used in the form
	 * @return    void
	 * @throws Exception
	 */
	protected function _afterSave( ?Model $old, Model $new, bool|string $lastUsedTab = FALSE ): void
	{
		if( Request::i()->isAjax() )
		{
			Output::i()->json( array() );
		}
		else
		{
			if( isset( Request::i()->save_and_reload ) )
			{
				$buttons = $new->getButtons( $this->url, !( $new instanceof $this->nodeClass ) );

				Output::i()->redirect( ( $lastUsedTab ? $buttons['edit']['link']->setQueryString('activeTab', $lastUsedTab ) : $buttons['edit']['link'] ), 'saved' );
			}
			else
			{
				Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $new->parent() ? $new->parent()->_id : '' ), $this->nodeClass::$scopeKey => $this->nodeClass::$scopeValue ) ), 'saved' );
			}
		}
	}

	/**
	 * Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$rows = array();

		/* Get results */
		$nodeClass = $this->nodeClass;
		/** @var Model $nodeClass */
		$results = $nodeClass::search( 'template_name', Request::i()->input, 'template_name' );

		/* Convert to HTML */
		foreach ( $results as $result )
		{
			$rows[ $result->_id ] = $this->_getRow($result, FALSE, TRUE);
		}

		Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}

	/**
	 * Rebuilds the flags in core_theme_templates to denote if the template has a hook point or not
	 * @return void
	 * @throws Exception
	 */
	protected function rebuildHookPoints(): void
	{
		Theme::rebuildHookPointFlags();

		Output::i()->redirect( Url::internal('app=core&module=customization&controller=customtemplates&' . $this->nodeClass::$scopeKey. '=' . $this->nodeClass::$scopeValue  ), 'updated' );
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		if ( \IPS\IN_DEV )
		{
			/* Add a button for settings */
			$buttons['rebuild'] = array(
				'primary'	=> false,
				'icon'		=> 'cogs',
				'title'		=> 'custom_template_rebuild_hookpoints',
				'link'		=> Url::internal( "app=core&module=customization&controller=customtemplates&do=rebuildHookPoints&" . $this->nodeClass::$scopeKey. "=" . $this->nodeClass::$scopeValue )
			);
		}

		/* Add a button for settings */
		$buttons['add'] = array(
			'primary'	=> true,
			'icon'		=> 'plus',
			'title'		=> 'custom_template_create_new',
			'link'		=> Url::internal( "app=core&module=customization&controller=customtemplates&do=form&" . $this->nodeClass::$scopeKey. "=" . $this->nodeClass::$scopeValue )
		);

		return $buttons;
	}
}