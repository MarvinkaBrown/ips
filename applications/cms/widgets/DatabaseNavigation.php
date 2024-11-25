<?php

/**
 * @brief		DatabaseNavigation Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		03 Jan 2024
 */

namespace IPS\cms\widgets;

use IPS\cms\Categories;
use IPS\cms\Databases;
use IPS\cms\Databases\Dispatcher;
use IPS\Output;
use IPS\Request;
use IPS\Widget\PermissionCache;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * DatabaseNavigation Widget
 */
class DatabaseNavigation extends PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'DatabaseNavigation';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';

	/**
	 * Initialise this widget
	 *
	 * @return void
	 */
	public function init(): void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_blocks.js', 'cms', 'front' ) );
		parent::init();
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* adding/editing a record */
		if ( Request::i()->do == 'form' )
		{
			return '';
		}

		if ( ! Dispatcher::i()->databaseId )
		{
			return '';
		}

		try
		{
			$database = Databases::load( Dispatcher::i()->databaseId );
			$database->preLoadWords();
		}
		catch ( OutOfRangeException $e )
		{
			return '';
		}

		/* @var Categories $categoriesClass */
		$categoriesClass = 'IPS\cms\Categories' . $database->id;

		if( $database->use_categories )
		{
			$categories = $categoriesClass::roots();
		}
		else
		{
			$categories = $categoriesClass::load( $database->default_category );
		}

		return $this->output( $database, $categories, Dispatcher::i()->categoryId );
	}
}