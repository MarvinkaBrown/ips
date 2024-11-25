<?php
/**
 * @brief		Codemirror Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		13 Dec 2023
 */

namespace IPS\cms\widgets;

use Exception;
use IPS\Data\Store;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Log;
use IPS\Output;
use IPS\Theme;
use IPS\Widget;
use LogicException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * RAW HTML Widget: Used to use CodeMirror, but now uses Tiptap in IPS v5
 */
class Codemirror extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'Codemirror';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';

	/**
	 * Specify widget configuration
	 *
	 * @param	Form|NULL	$form	Form helper
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

		$form->add(
			new Form\TextArea(
				'content',
				( isset( $this->configuration['content'] ) and $this->configuration['content'] ) ?
					htmlentities( $this->configuration['content'], ENT_DISALLOWED, 'UTF-8' ) :
					NULL,
				FALSE,
				[
					'tagSource'	=> Url::internal( "app=cms&module=pages&controller=ajax&do=loadTags" ),
					'codeMode'	=> true,
					'codeModeAllowedLanguages' => ['ipsphtml']
				],
				function( $val )
				{
					try
					{
						Theme::checkTemplateSyntax( $val );
					}
					catch( LogicException $e )
					{
						throw new LogicException('cms_page_error_bad_syntax');
					}
				},
				NULL,
				NULL,
				'page_content'
			)
		);

		return $form;
	}

	/**
	 * Pre-save config method
	 *
	 * @param	array	$values		Form values
	 * @return array
	 */
	public function preConfig( array $values=array() ) : array
	{
		$functionName = 'content_widget_' .  $this->uniqueKey;
		if ( isset( Store::i()->$functionName ) )
		{
			unset( Store::i()->$functionName );
		}

		return $values;
	}

	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete() : void
	{
		$functionName = 'content_widget_' .  $this->uniqueKey;
		if ( isset( Store::i()->$functionName ) )
		{
			unset( Store::i()->$functionName );
		}
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		try
		{
			$functionName = 'content_widget_' .  $this->uniqueKey;
			if ( ! isset( Store::i()->$functionName ) )
			{
				Store::i()->$functionName = Theme::compileTemplate( $this->configuration['content'] ?? '', $functionName );
			}

			Theme::runProcessFunction( Store::i()->$functionName, $functionName );

			$themeFunction = 'IPS\\Theme\\'. $functionName;

			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_core.js', 'core' ) );
			return $this->output( $themeFunction() );
		}
		catch( Exception $e )
		{
			Log::log( $e->getMessage(), 'codeblock' );
			return "";
		}
	}
}