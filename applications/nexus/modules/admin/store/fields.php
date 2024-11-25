<?php
/**
 * @brief		fields
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		01 May 2014
 */

namespace IPS\nexus\modules\admin\store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * fields
 */
class fields extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\nexus\Package\CustomField';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'package_fields_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'custom_package_fields_blurb' );
		parent::manage();
	}
	
	/**
	 * Warning about unconsecutive rules
	 *
	 * @return	void
	 */
	protected function warning() : void
	{
		Output::i()->output = Theme::i()->getTemplate( 'store' )->productOptionsChanged(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'nexus_packages', Db::i()->in( 'p_id', explode( ',', Request::i()->ids ) ) ),
				'IPS\nexus\Package'
			)
		);
	}	
	/**
	 * Redirect after save
	 *
	 * @param	Model|null	$old			A clone of the node as it was before or NULL if this is a creation
	 * @param	Model	$new			The node now
	 * @param	string			$lastUsedTab	The tab last used in the form
	 * @return	void
	 */
	protected function _afterSave( ?Model $old, Model $new, mixed $lastUsedTab = FALSE ): void
	{
		if ( $old AND $old->extra != $new->extra )
		{
			$products = Db::i()->select( 'DISTINCT(opt_package)', 'nexus_product_options', "opt_values LIKE '%\"{$new->_id}\":%'" );
			if ( count( $products ) )
			{
				Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'warning', 'ids' => implode( ',', iterator_to_array( $products ) ) ) ) );
			}
		}
		
		parent::_afterSave( $old, $new, $lastUsedTab );
	}
}