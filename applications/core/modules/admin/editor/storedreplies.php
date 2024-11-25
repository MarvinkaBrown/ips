<?php

/**
 * @brief		Editor Stored Replies aka Stock Actions aka whatever else I rename it during this coding session
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Code
 * @since		03 September 2021
 */

namespace IPS\core\modules\admin\editor;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\StoredReplies as StoredRepliesClass;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stored Replies
 */
class storedreplies extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\core\StoredReplies';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'stored_replies_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Have we not dragged the button to the editor bar? */
		parent::manage();

		Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'editor_stored_replies_blurb' ) . Output::i()->output;
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
		$results = [];

		/* Convert to HTML */
		/* @var StoredRepliesClass $nodeClass */
		foreach ( $nodeClass::search( 'reply_title', Request::i()->input, 'reply_title' ) as $result )
		{
			$id = ( $result instanceof $this->nodeClass ? '' : 's.' ) . $result->_id;
			$rows[ $id ] = $this->_getRow( $result, FALSE, TRUE );
		}

		Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}
}