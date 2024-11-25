<?php


/**
 * @brief		Fields Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		8 April 2014
 */

namespace IPS\cms\modules\admin\databases;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\Dispatcher;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * categories
 */
class categories extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\cms\Categories';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'databases_use' );

		/* This controller can not be accessed without a database ID */
		if( !Request::i()->database_id )
		{
			Output::i()->error( 'node_error', '2S390/1', 404, '' );
		}

		$this->url = $this->url->setQueryString( array( 'database_id' => Request::i()->database_id ) );
		
		/* Assign the correct nodeClass so contentItem is specified */
		$this->nodeClass = '\IPS\cms\Categories' . Request::i()->database_id;
		
		Dispatcher::i()->checkAcpPermission( 'categories_manage' );

		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;

		$childLang = Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle . '_add_child' );
		$nodeClass::$nodeTitle = Member::loggedIn()->language()->addToStack('content_cat_db_title', FALSE, array( 'sprintf' => array( Databases::load( Request::i()->database_id )->_title ) ) );
		Member::loggedIn()->language()->words[ $nodeClass::$nodeTitle . '_add_child' ] = $childLang;
		parent::execute();
	}
	
	/**
	 * Get Root Rows
	 *
	 * @return	array
	 */
	public function _getRoots(): array
	{
		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;
		$rows = array();
	
		foreach( $nodeClass::roots( NULL ) as $node )
		{
			if ( $node->database_id == Request::i()->database_id )
			{
				$rows[ $node->_id ] = $this->_getRow( $node );
			}
		}
	
		return $rows;
	}

	/**
	 * Function to execute after nodes are reordered. Do nothing by default but plugins can extend.
	 *
	 * @param	array	$order	The new ordering that was saved
	 * @return	void
	 * @note	Pages needs to readjust category_full_path values when a category is moved to a different category
	 */
	protected function _afterReorder( array $order ) : void
	{
		/* @var Model $categoryClass */
		$categoryClass = $this->nodeClass;

		foreach( $order as $parent => $nodes )
		{
			foreach ( $nodes as $id => $position )
			{
				$categoryClass::resetPath( $id );
			}
		}

		parent::_afterReorder( $order );
	}
}