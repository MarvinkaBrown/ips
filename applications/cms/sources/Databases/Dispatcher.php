<?php
/**
 * @brief		Database Dispatcher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		16 April 2013
 */

namespace IPS\cms\Databases;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cms\Categories;
use IPS\cms\Databases;
use IPS\cms\Pages\Page;
use IPS\cms\Records;
use IPS\core\DataLayer;
use IPS\Dispatcher as SystemDispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function intval;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Dispatcher
 */
class Dispatcher extends SystemDispatcher
{
	/**
	 * @brief	Singleton Instance (So we don't re-use the regular dispatcher)
	 */
	protected static mixed $instance = NULL;
	
	/**
	 * @brief	Controller location
	 */
	public string $controllerLocation = 'front';
	
	/**
	 * @brief	Database Id
	 */
	public ?int $databaseId = NULL;
	
	/**
	 * @brief	Category Id
	 */
	public ?int $categoryId = NULL;

	/**
	 * @brief	Record Id
	 */
	public ?int $recordId = NULL;

	/**
	 * @brief	Url
	 */
	public mixed $url = NULL;
	
	/**
	 * @brief	Module
	 */
	public ?string $module = NULL;
	
	/**
	 * @brief	Output to return
	 */
	public string $output = "";
	
	/**
	 * Set Database ID
	 *
	 * @param	mixed	$databaseId		Database key or ID
	 * @return	Dispatcher
	 */
	public function setDatabase( mixed $databaseId ): Dispatcher
	{
		/* Other areas rely on $this->databaseId being numeric */
		if ( !is_numeric( $databaseId ) )
		{
			$database   = Databases::load( $databaseId, 'database_key' );
			$databaseId = $database->id;
		}

		$this->databaseId = $databaseId;

		$database   = Databases::load( $databaseId );
		if ( ! $database->use_categories )
		{
			$this->categoryId = $database->_default_category;
		}

		return $this;
	}
	
	/**
	 * Set Category ID
	 *
	 * @param	mixed	$categoryId		Category ID
	 * @return	Dispatcher
	 */
	public function setCategory( mixed $categoryId ): Dispatcher
	{
		$this->categoryId = $categoryId;
		return $this;
	}
	
	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() : void
	{
		if ( ( Page::$currentPage AND ! ( Application::load('cms')->default AND ! Page::$currentPage->folder_id AND Page::$currentPage->default ) ) )
		{
			Output::i()->breadcrumb['module'] = array( Page::$currentPage->url(), Page::$currentPage->_title );
		}
	}

	/**
	 * Run
	 *
	 * @return void
	 */
	public function run() : void
	{
		/* Coming from a widget? */
		if ( isset( Request::i()->pageID ) and isset( Request::i()->blockID ) )
		{
			if ( Page::$currentPage === NULL )
			{
				/* make sure this is a valid widgetized page to stop tampering */
				try
				{
					$page = Page::load( Request::i()->pageID );
					foreach( $page->getAreasFromDatabase() as $area )
					{
						foreach( $area->getAllWidgets() as $block )
						{
							if ( $block['key'] === 'Database' and isset( $block['configuration']['database'] ) and intval( $block['configuration']['database'] ) === $this->databaseId )
							{
								Page::$currentPage = $page;
								break;
							}
						}
					}
				}
				catch( UnderflowException $e ) { }
			}

			/* Try again */
			if ( Page::$currentPage === NULL )
			{
				Output::i()->error( 'page_doesnt_exist', '2T251/1', 404 );
			}

			/* Unset do query param otherwise it confuses the controller->execute(); */
			Request::i()->do = NULL;
		}

		$url = 'app=cms&module=pages&controller=page&path=' . Page::$currentPage->full_path;

		try
		{
			$database = Databases::load( $this->databaseId );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'page_doesnt_exist', '2T251/2', 404 );
		}

		$path = '';
		if( isset( Request::i()->path ) )
		{
			$path = trim(  preg_replace( '#' . Page::$currentPage->full_path . '#', '', Request::i()->path, 1 ), '/' );

			/* If we visited the default page in a folder, the full_path will be like folder/page but the request path will just be folder */
			if( Request::i()->path . '/' . Page::$currentPage->seo_name == Page::$currentPage->full_path )
			{
				$path = '';
			}
		}

		list( $path, $pageNumber ) = Page::getStrippedPagePath( $path );
		
		$this->databaseId = $database->id;

		if ( ! $database->use_categories )
		{
			$this->categoryId = $database->default_category;
		}

		/* Got a specific category ID? */
		if ( $this->categoryId !== NULL and ! $path and ( ( $database->use_categories and $database->cat_index_type !== 1 ) OR ( ! $database->use_categories and isset( Request::i()->do ) ) ) )
		{
			$this->module = 'category';
		}
		else if ( isset( Request::i()->c ) AND is_numeric( Request::i()->c ) )
		{
			$this->categoryId = Request::i()->c;
			$this->module = 'category';
		}
		else if ( empty( $path ) )
		{
			$this->module = 'index';
		}
		else
		{
			$url .= '/' . $path;

			/* @var Records $recordClass */
			$recordClass = '\IPS\cms\Records' . $database->id;
			$isLegacyCategoryUrl = FALSE;
			
			if ( $database->use_categories )
			{
				/* @var Categories $catClass */
				$catClass = '\IPS\cms\Categories' . $database->id;
				$category = $catClass::loadFromPath( $path, $database->id );
				
				/* /_/ was used in IP.Board 3.x to denote the articles database */
				if ( $category === NULL AND mb_substr( $path, 0, 2 ) === '_/' )
				{
					$category = $catClass::loadFromPath( mb_substr( $path, 2 ), $database->id );
					
					/* We may have a record URL still, so set a flag to handle this later if we never find anything */
					if ( $category !== NULL )
					{
						$isLegacyCategoryUrl = TRUE;
					}
				}

				if ( $category === NULL )
				{
					
					/* Is this a record? */
					$bits = explode( '/', $path );
					$slug = array_pop( $bits );

					try
					{
						$record = $recordClass::loadFromSlug( $slug );
						
						$this->_redirectToCorrectUrl( $record->url() );
					}
					catch ( OutOfRangeException $ex )
					{
						/* Check slug history */
						try
						{
							$record = $recordClass::loadFromSlugHistory( $slug );

							$this->_redirectToCorrectUrl( $record->url() );
						}
						catch ( OutOfRangeException $ex )
						{
							Output::i()->error( 'page_doesnt_exist', '2T251/4', 404 );
						}
					}
				}

				$whatsLeft = preg_replace( '#' . $category->full_path . '#', '', $path, 1 );

				$this->categoryId = $category->id;
			}
			else
			{
				$whatsLeft = $path;
			}

			if ( $whatsLeft )
			{
				/* Find the record */
				try
				{
					$record = $recordClass::loadFromSlug( $whatsLeft, TRUE, $this->categoryId );

					/* Make the Content controller all kinds of happy */
					Request::i()->id = $this->recordId = $record->primary_id_field;
				}
				catch( OutOfRangeException $ex )
				{
					/* Check slug history */
					try
					{
						$record = $recordClass::loadFromSlugHistory( $whatsLeft );

						$this->_redirectToCorrectUrl( $record->url() );
					}
					catch( OutOfRangeException $ex )
					{
						/* We are absolutely certain this is not a record, but we have found a legacy category - redirect */
						if ( $isLegacyCategoryUrl === TRUE )
						{
							Output::i()->redirect( $category->url() );
						}
						
						Output::i()->error( 'page_doesnt_exist', '2T251/5', 404 );
					}
				}
				
				/* Make sure the URL is correct, for instance, it could have moved categories */
				if ( $database->use_categories AND $this->categoryId != $record->category_id )
				{
					$this->_redirectToCorrectUrl( $record->url() );
				}
				
				$this->module = 'record';
			}
			else
			{
				/* It's a category listing */
				$this->module = 'category';
			}
		}
		
		$this->url = Url::internal( $url, 'front', 'content_page_path' );
		$className = '\\IPS\\cms\\modules\\front\\database\\' . $this->module;

		/* Init class */
		if( !class_exists( $className ) )
		{
			Output::i()->error( 'page_doesnt_exist', '2T251/6', 404 );
		}
		$controller = new $className;
		if( !( $controller instanceof Controller ) )
		{
			Output::i()->error( 'page_not_found', '3T251/7', 500, '' );
		}

		Dispatcher::i()->dispatcherController	= $controller;
		
		/* Is this the default application? Default app should search all */
		if ( ! Application::load('cms')->default )
		{
			/* If database doesn't allow search, default to all */
			if ( ! $database->search )
			{
				Output::i()->defaultSearchOption = array( 'all', 'search_everything' );
			}
			else
			{
				Output::i()->defaultSearchOption = array( "cms_records{$this->databaseId}", "cms_records{$this->databaseId}_pl" );
			}
		}
		else 
		{
			Output::i()->defaultSearchOption = array( 'all', 'search_everything' );
		}

		/* Add database key to body classes for easier database specific themeing */
		Output::i()->bodyClasses[] = 'cCmsDatabase_' . $database->key;
		
		/* Execute */
		$controller->execute();

		/* Data Layer Context */
		if ( $this->databaseId AND Settings::i()->core_datalayer_enabled )
		{
			try
			{
				$databaseOrCategory = Databases::load( $this->databaseId );
			}
			catch ( UnderflowException $e )
			{
				$this->finish();
				return;
			}

			/* Use the category instead of the database if there is one */
			if ( $this->categoryId AND $databaseOrCategory->use_categories )
			{
				try
				{
					/* @var Categories $catClass */
					$catClass = '\IPS\cms\Categories' . $this->databaseId;
					$databaseOrCategory = $catClass::load( $this->categoryId );
				}
				catch ( UnderflowException $e ) {}
			}

			/* Add the database's/category's data layer properties to the page context */
			foreach ( $databaseOrCategory->getDataLayerProperties() as $key => $property )
			{
				DataLayer::i()->addContextProperty( $key, $property );
				if ( $key === 'content_area' )
				{
					DataLayer::i()->addContextProperty( 'community_area', $property );
				}
			}
		}
		
		$this->finish();
	}
	
	/**
	 * Redirect to the "correct" URL (for example if the category slug is incorrect)
	 * while retaining any query string parameters in the request URL
	 * For example, an embed to a record might be example.com/records/cat-1/record/?do=embed
	 * If the record is moved so the URL is cat-2, the embed needs to redirect while retaining
	 * the /?do=embed
	 *
	 * @param Url $correctUrl		The URL for the record
	 * @return	void
	 */
	protected function _redirectToCorrectUrl( Url $correctUrl ) : void
	{
		$paramsToSet = array();
		foreach ( Request::i()->url()->queryString as $k => $v )
		{
			if ( !array_key_exists( $k, $correctUrl->queryString ) and !array_key_exists( $k, $correctUrl->hiddenQueryString ) )
			{
				$paramsToSet[ $k ] = $v;
			}
		}
		if ( count( $paramsToSet ) )
		{
			$correctUrl = $correctUrl->setQueryString( $paramsToSet );
		}
		
		Output::i()->redirect( $correctUrl, NULL );
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish() : void
	{
		Output::i()->output = $this->output ?: Output::i()->output;
		parent::finish();
	}
}