<?php
/**
 * @brief		Related Content Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Apr 2014
 * @note		This widget is designed to be enabled on a page that displays a content item (e.g. a topic) to show related content based on tags
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Controller;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Related Content Widget
 */
class relatedContent extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'relatedContent';
	
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
		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );
		
		/* We can't run the URL related logic if we have no dispatcher because this class could also be initialized by the CLI cron job */
		if( Dispatcher::hasInstance() )
		{
			/* Cache per item, not per block */
			$parts = parse_url( (string) Request::i()->url()->setPage() );

			if ( Settings::i()->htaccess_mod_rewrite )
			{
				$url = $parts['scheme'] . "://" . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . ( $parts['path'] ?? '' );
			}
			else
			{
				$url = $parts['scheme'] . "://" . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . ( $parts['path'] ?? '' ) . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
			}

			$this->cacheKey .= '_' . md5( $url );
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
		if ( !isset( $this->configuration['language_key'] ) )
		{
			$this->configuration['language_key'] = 'widget_title_' . md5( mt_rand() );
		}
		$values['language_key'] = $this->configuration['language_key'];

		Lang::saveCustom( 'core', $this->configuration['language_key'], $values['widget_feed_title'] );
		unset( $values['widget_feed_title'] );

		return $values;
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

		/* Block title */
		$form->add( new Translatable( 'widget_feed_title', isset( $this->configuration['language_key'] ) ? NULL : Member::loggedIn()->language()->addToStack( 'block_relatedContent' ), FALSE, array( 'app' => 'core', 'key' => ( $this->configuration['language_key'] ?? NULL ) ) ) );
		$form->add( new Number( 'toshow', $this->configuration['toshow'] ?? 5, TRUE ) );
		
		return $form;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if( !( Dispatcher::i()->dispatcherController instanceof Controller ) )
		{
			return '';
		}

		$limit = $this->configuration['toshow'] ?? 5;

		$related	= Dispatcher::i()->dispatcherController->getSimilarContent( $limit );

		if( $related === NULL or !count( $related ) )
		{
			return '';
		}

		if ( isset( $this->configuration['language_key'] ) )
		{
			$title = Member::loggedIn()->language()->addToStack( $this->configuration['language_key'], FALSE, array( 'escape' => TRUE ) );
		}
		else
		{
			$title = Member::loggedIn()->language()->addToStack( 'block_relatedContent' );
		}

		return $this->output( $related, $title );
	}
}