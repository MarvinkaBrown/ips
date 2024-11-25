<?php
/**
 * @brief		customtemplates
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use Exception;
use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Theme\CustomTemplate;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * customtemplates
 */
class customtemplates extends Controller
{
	/**
	 * @var bool 
	 */
	public static bool $csrfProtected = true;

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$url = Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$this->application->directory}" );
		$appKey = $this->application->directory;
		$templates = [];

		foreach( CustomTemplate::all() as $template )
		{
			if ( $template->app == $appKey )
			{
				$templates[] = $template;
			}
		}

		/* Display the table */
		$tree = new Tree( $url, 'dev_custom_templates',
			/* Get Roots */
			function () use ( $url, $appKey, $templates )
			{
				$rows	= array();
				$order	= 1;
				foreach ( $templates as $template )
				{
					$rows[ $template->id ] = Theme::i()->getTemplate( 'trees' )->row( $url, $template->id, $template->name, false, array(
						'add'	=> array(
							'icon'	=> 'pencil',
							'title'	=> 'edit',
							'link'	=> Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$appKey}&do=customTemplateForm&id=" . $template->id ),
						),
						$buttons['delete'] = array(
							'icon'	=> 'times-circle',
							'title'	=> 'delete',
							'link'	=> Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$appKey}&do=customTemplateDelete&id=" . $template->id ),
							'data' 	=> ['delete' => '']
						)
					), NULL, NULL, $order, FALSE, NULL, NULL, $template->_badge );
					$order++;
				}
				return $rows;
			},
			/* Get Row */
			NULL,
			/* Get Row Parent */
			function ()
			{
				return NULL;
			},
			/* Get Children */
			NULL,
			/* Get Root Buttons */
			function() use ( $appKey )
			{
				return array(
					'add'	=> array(
						'icon'		=> 'plus',
						'title'		=> 'custom_template_create_new',
						'link'		=> Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$appKey}&do=customTemplateForm" ),
					),
				);
			},
			FALSE,
			FALSE,
			FALSE
		);

		Output::i()->output = (string) $tree;
	}

	/**
	 * Delete a template
	 *
	 * @return void
	 */
	protected function customTemplateDelete(): void
	{
		try
		{
			$current = CustomTemplate::load( Request::i()->id );
		}
		catch( Exception )
		{
			Output::i()->error( 'node_error', '2C124/1', 404 );
		}

		/* We just made you, now we must destroy you */
		$current->delete();

		$this->application->buildCustomTemplates();

		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$this->application->directory}" ), 'deleted' );
	}

	/**
	 * Custom template add/edit form
	 *
	 * @return    void
	 * @throws Exception
	 */
	protected function customTemplateForm(): void
	{
		if ( isset( Request::i()->id ) )
		{
			$current = CustomTemplate::load( Request::i()->id );
		}
		else
		{
			$current = new CustomTemplate;
		}

		$current::$scopeKey = 'appKey';
		$current::$scopeValue = Request::i()->appKey;

		/* Show Form */
		$form = new Form();
		$current->form( $form );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$current->saveForm( $current->formatFormValues( $values ) );
			$this->application->buildCustomTemplates();

			if( Request::i()->isAjax() )
			{
				Output::i()->json( array() );
			}
			else
			{
				if( isset( Request::i()->save_and_reload ) )
				{
					Output::i()->redirect( Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$this->application->directory}&do=customTemplateForm&id=" . $current->id ), 'saved' );
				}
				else
				{
					Output::i()->redirect( Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$this->application->directory}" ), 'saved' );
				}
			}

		}

		Output::i()->breadcrumb[] = array(
			Url::internal( "app=core&module=developer&controller=customtemplates&appKey={$this->application->directory}" ),
			'custom_templates'
		);

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'custom_templates', $form, FALSE );
	}

	/**
	 * @return void
	 */
	protected function reorder() : void
	{
		Session::i()->csrfCheck();

		/* Normalise AJAX vs non-AJAX */
		if( isset( Request::i()->ajax_order ) )
		{
			$order = array();
			$position = array();
			foreach( Request::i()->ajax_order as $id => $parent )
			{
				if ( !isset( $order[ $parent ] ) )
				{
					$order[ $parent ] = array();
					$position[ $parent ] = 1;
				}
				$order[ $parent ][ $id ] = $position[ $parent ]++;
			}
		}
		/* Non-AJAX way */
		else
		{
			$order = array( Request::i()->root ?: 'null' => Request::i()->order );
		}

		/* Okay, now order */
		if ( isset( $order['null'] ) )
		{
			foreach ( $order['null'] as $id => $position )
			{
				try
				{
					$node = CustomTemplate::load( $id );
					$node->order = (int) $position;
					$node->save();
				}
				catch ( OutOfRangeException $e ){}
			}
		}

		$this->application->buildCustomTemplates();
	}
}