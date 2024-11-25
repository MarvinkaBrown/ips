<?php
/**
 * @brief		Moderator Control Panel Extension: Content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Dec 2013
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content\Comment;
use IPS\Content\Search\ContentFilter;
use IPS\Content\Search\Query;
use IPS\Extensions\ModCpAbstract;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function base64_encode;
use function count;
use function defined;
use function in_array;
use function json_encode;
use function str_replace;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Control Panel Extension: Content
 */
class Content extends ModCpAbstract
{	
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string
	 */
	public function getTab() : string
	{
		return 'hidden';
	}

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return	string
	 */
	public function manageType() : string
	{
		return 'content';
	}

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		if ( isset( Request::i()->modaction ) AND in_array( Request::i()->modaction, array( 'unhide', 'delete' ) ) )
		{
			$this->modaction();
		}

		/* What types are there? */
		$types = $this->_getContentTypes();
		$exclude = array();
		foreach( $types as $key => $class )
		{
			if ( Member::loggedIn()->modPermission( 'can_view_hidden_content' ) or Member::loggedIn()->modPermission( 'can_view_hidden_' . $class::$title ) )
			{
				if ( isset( $class::$containerNodeClass ) )
				{
					$containerClass = $class::$containerNodeClass;
					if ( isset( $containerClass::$modPerm ) )
					{
						$allowedContainers = Member::loggedIn()->modPermission( $containerClass::$modPerm );
						if ( $allowedContainers !== -1 and $allowedContainers !== true )
						{
							$exclude[ $class ] = $allowedContainers;
						}
					}
				}
			}
		}

		/* Init output */
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'search_everything' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'search_everything' );

		/* Init query */
		$query = Query::init()->setHiddenFilter( Query::HIDDEN_HIDDEN )->setOrder( Query::ORDER_NEWEST_UPDATED );

		/* Are we looking at a specific content type? */
		if( isset( Request::i()->filter ) AND array_key_exists( Request::i()->filter, $types ) )
		{
			$currentClass = $types[ Request::i()->filter ];
			if( isset( $exclude[ $currentClass ] ) )
			{
				$query->filterByContent( [
					ContentFilter::init( $currentClass )->onlyInContainers( $exclude[ $currentClass ] )
				] );
			}
			else
			{
				$query->filterByContent( [ ContentFilter::init( $currentClass ) ] );
			}
		}
		/* Can we only view some kinds of hidden content? */
		elseif ( count( $exclude ) )
		{
			$filters = array();
			foreach ( $exclude as $class => $allowedContainers )
			{
				if( $allowedContainers )
				{
					$filters[] = ContentFilter::init( $class )->excludeInContainers( $allowedContainers );
				}
				else
				{
					$filters[] = ContentFilter::init( $class );
				}
			}

			if( !empty( $filters ) )
			{
				$query->filterByContent( $filters, FALSE );
			}
		}

		/* Sort the sorting */
		if ( ! isset( Request::i()->sortby ) )
		{
			Request::i()->sortby = 'index_date_updated_desc';
		}

		if ( Request::i()->sortby === 'index_date_updated_asc' )
		{
			$query->setOrder( Query::ORDER_OLDEST_CREATED );
		}

		/* Limit to 250 items max */
		$query->setLimit( 250 );
		$total = $query->search()->count( TRUE );

		/* Query */
		$results = $query->search();
		$dataSource = [];
		$dataSourceAllKeys = [];
		foreach( $results as $result )
		{
			$data = $result->asArray();
			$modActions = [];
			try
			{
				$class = $data['indexData']['index_class'];
				$item = $class::load( $data['indexData']['index_object_id'] );

				/* Is this actually the first post of an item? */
				if ( isset( $class::$databaseColumnMap['first'] ) )
				{
					$column = $class::$databaseColumnMap['first'];
					if ( $item->$column )
					{
						$item = $item->item();
					}
				}

				if ( $item->canDelete() )
				{
					$modActions[] = 'delete';
				}

				if ( $item->canUnhide() )
				{
					$modActions[] = 'unhide';
				}
			}
			catch( Exception ) { }

			$dataSource[] = array_merge( ['index_mod_actions' => $modActions ], $data['indexData'] );
			$dataSourceAllKeys[ $data['indexData']['index_id'] ] = $result->asArray();
		}

		$table = new Custom( $dataSource, Url::internal( "app=core&module=modcp&controller=modcp&tab=hidden", "front", "modcp_hidden" ) );
		$table->baseUrl = Url::internal( "app=core&module=modcp&controller=modcp&tab=hidden", "front", "modcp_hidden" );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'modcp' ), 'hiddenTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'modcp' ), 'hiddenTableRows' );
		$table->title = 'modcp_hidden';
		$table->limit = 25;
		$table->sortBy = 'index_date_updated';
		$table->sortDirection = ( Request::i()->sortby === 'index_date_updated_asc' ) ? 'asc' : 'desc';
		$table->sortOptions = array( 'index_date_updated_desc', 'index_date_updated_asc' );
		$table->filters = $types;

		/* Custom parsers */
		$table->parsers = array(
			'index_content' => function( $val, $row ) use ( $dataSourceAllKeys )
			{
				$array = $dataSourceAllKeys[ $row['index_id'] ];

				try
				{
					$class = $row['index_class'];
					$item = $class::load( $row['index_object_id'] );

					/* Is this actually the first post of an item? */
					if ( isset( $class::$databaseColumnMap['first'] ) )
					{
						$column = $class::$databaseColumnMap['first'];
						if ( $item->$column )
						{
							$item = $item->item();
						}
					}

					if ( $item instanceof Comment )
					{
						$itemClass = $item::$itemClass;
						$ref = base64_encode( json_encode( array( 'app' => $itemClass::$application, 'module' => $itemClass::$module, 'id_1' => $item->mapped('item'), 'id_2' => $item->id ) ) );
						$title = $item->item()->mapped('title');
						$container = $item->item()->container();
					}
					else
					{
						$ref = base64_encode( json_encode( array( 'app' => $item::$application, 'module' => $item::$module, 'id_1' => $item->id ) ) );

						try
						{
							$container = $item->container();
						}
						catch ( Exception ) { }

						$title = $item->mapped('title');
					}

					$row['index_class'] = str_replace( "\\", "_", $row['index_class'] );

					return Theme::i()->getTemplate('modcp')->hiddenTableRow( $item, $ref, $container, $title, $row );
				}
				catch( Exception ) { }

				return '';
			}
		);

		$resortKey = $table->resortKey;
		if( Request::i()->isAjax() AND isset( Request::i()->$resortKey ) )
		{
			Output::i()->sendOutput( (string) $table );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate('modcp')->hiddenTableWrapper( $table, $total );
		}
	}

	/**
	 * Get hidden content types
	 *
	 * @return	array
	 */
	protected function _getContentTypes(): array
	{
		$types = array();
		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
			{
				if ( Member::loggedIn()->modPermission( 'can_view_hidden_content' ) or Member::loggedIn()->modPermission( 'can_view_hidden_' . $class::$title ) )
				{
					$types[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class;
				}
			}
		}

		/* Remove pending file versions */
		if( isset( $types['downloads_file_pendingversion'] ) )
		{
			unset( $types['downloads_file_pendingversion'] );
		}

		return $types;
	}

	/**
	 * Mod Action
	 * Yes, I'm a genius.
	 *
	 * @return    void
	 * @throws Exception
	 */
	public function modaction(): void
	{
		Session::i()->csrfCheck();

		$or = [];
		$classes = [];
		foreach( array_keys( Request::i()->moderate ) as $data )
		{
			[ $class, $objectId ] = explode( ':', $data );
			$classes[ str_replace( '_', '\\', $class ) ][] = $objectId;
		}

		foreach( $classes as $class => $ids )
		{
			$or[] = ContentFilter::initWithSpecificClass( $class )->onlyInIds( $ids );
		}

		/* Query and manually sort */
		$search = Query::init();
		/* Limit to 500 items max */
		$search->setLimit( 500 );

		$array = $search->filterByContent( $or )->search()->getArrayCopy();

		foreach( $array as $row )
		{
			try
			{
				$class = $row['index_class'];
				$item = $class::load( $row['index_object_id'] );

				/* Is this actually the first post of an item? */
				if ( isset( $class::$databaseColumnMap['first'] ) )
				{
					$column = $class::$databaseColumnMap['first'];
					if ( $item->$column )
					{
						$item = $item->item();
					}
				}

				if( $item instanceof \IPS\Content )
				{
					$item->modAction( Request::i()->modaction );
				}
			}
			catch( Exception ) { }
		}

		Output::i()->redirect( Url::internal( "app=core&module=modcp&controller=modcp&tab=hidden", 'front', 'modcp_hidden' ), 'saved' );
	}
}