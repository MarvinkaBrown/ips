<?php
/**
 * @brief		FolderNavigation Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		04 Jan 2024
 */

namespace IPS\cms\widgets;

use IPS\cms\Pages\Folder;
use IPS\cms\Pages\Page;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Widget;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * FolderNavigation Widget
 */
class FolderNavigation extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'FolderNavigation';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';
	
	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_blocks.js', 'cms', 'front' ) );
		parent::init();
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

		$form->add( new Text( 'block_nav_title', $this->configuration['block_nav_title'] ?? null, true ) );
		$form->add( new Node( 'block_nav_folder', $this->configuration['block_nav_folder'] ?? 0, true, array(
			'class' => Folder::class,
			'multiple' => false,
			'zeroVal' => 'cms_navigation_root_folder'
		) ) );

 		return $form;
 	} 
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
		 $values['block_nav_folder'] = ( $values['block_nav_folder'] instanceof Folder ) ? $values['block_nav_folder']->_id : 0;
		 return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if( !Page::$currentPage )
		{
			return "";
		}

		$rootId = $this->configuration['block_nav_folder'] ?? 0;
		if( $rootId !== 0 )
		{
			try
			{
				$folders = Folder::load( $this->configuration['block_nav_folder'] );
			}
			catch( OutOfRangeException )
			{
				return "";
			}
		}
		else
		{
			$folders = Folder::roots();
		}

		$pages = iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'cms_pages', [ 'page_folder_id=?', $rootId ] ),
				Page::class
			)
		);

		return $this->output( $folders, $rootId, $pages, $this->configuration['block_nav_title'] ?? null, Page::$currentPage->folder_id ?: null );
	}
}