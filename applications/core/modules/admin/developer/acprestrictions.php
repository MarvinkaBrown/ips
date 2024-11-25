<?php
/**
 * @brief		acprestrictions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * acprestrictions
 */
class acprestrictions extends Controller
{
	/**
	 * @var bool 
	 */
	public static bool $csrfProtected = true;

	/**
	 * @var array|null
	 */
	protected ?array $modules = null;

	/**
	 * @var array|null
	 */
	protected ?array $restrictions = null;

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$this->modules = $this->_getModules();
		$this->url = Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}" );
		$this->restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );
		$appKey = $this->application->directory;

		/* Display the table */
		$tree = new Tree( $this->url, 'dev_acprestrictions', array( $this, 'restrictionsGetRoots' ), array( $this, 'restrictionsGetRow' ),
			function ( $k )
			{
				if ( mb_substr( $k, 0, 1 ) === 'M' )
				{
					return NULL;
				}
				else
				{
					return 'M' . mb_substr( $k, 1, mb_strpos( $k, '-' ) - 1 );
				}
			},
			array( $this, 'restrictionsGetchildren' ),
			NULL,
			FALSE,
			TRUE,
			TRUE
		);

		Output::i()->output = (string) $tree;
	}

	/**
	 * @return void
	 */
	protected function reorder() : void
	{
		Session::i()->csrfCheck();

		/* Figure out the items we are reordering, we would only be reordering a single group */
		$newOrder = array();
		$position = 1;
		foreach( Request::i()->ajax_order as $i => $parent )
		{
			if( strpos( $i, '~' ) !== FALSE )
			{
				if( !isset( $newOrder[ $parent ] ) )
				{
					$newOrder[ $parent ] = array();
				}
				$newOrder[ $parent ][ $i ] = $position++;
			}
		}

		foreach( $newOrder as $groupKey => $order )
		{
			if( isset( Request::i()->ajax_order[ $groupKey ] ) )
			{
				$moduleKey = Request::i()->ajax_order[ $groupKey ];

				/* Sort */
				uasort( $this->restrictions[ $moduleKey ][ $groupKey ], function( $a, $b ) use ( $order ) {
					return ( isset( $order[ str_replace( '_', '~', $a ) ] ) ? $order[ str_replace( '_', '~', $a ) ] : 0 ) - ( isset( $order[ str_replace( '_', '~', $b ) ] ) ? $order[ str_replace( '_', '~', $b ) ] : 0 );
				});
			}
		}

		/* Write */
		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json", $this->restrictions );
	}

	/**
	 * Restrictions: Get Root Rows
	 *
	 * @return	array
	 */
	public function restrictionsGetRoots() : array
	{
		$rows = array();

		if( !empty( $this->modules['admin'] ) )
		{
			foreach ( $this->modules['admin'] as $k => $module )
			{
				if ( empty( $module['protected'] ) )
				{
					$rows[ $k ] = $this->_restrictionsModuleRow( $this->url, $k, $this->restrictions, FALSE );
				}
			}
		}
		return $rows;
	}

	/**
	 * Restrictions: Get Individual Row
	 *
	 * @param	string	$k		ID
	 * @param	bool	$root	Is root row?
	 * @return	string
	 */
	public function restrictionsGetRow( string $k, bool $root ) : string
	{
		if ( mb_substr( $k, 0, 1 ) === 'M' )
		{
			return $this->_restrictionsModuleRow( $this->url, mb_substr( $k, 1 ), $this->restrictions, $root );
		}
		else
		{
			$moduleKey = mb_substr( $k, 1, mb_strpos( $k, '-' ) - 1 );
			$groupKey = mb_substr( $k, mb_strpos( $k, '-' ) + 1 );
			return $this->_restrictionsGroupRow( $this->url, $groupKey, $moduleKey, $this->restrictions[ $moduleKey ][ $groupKey ], $root );
		}
	}

	/**
	 * Restrictions: Get Child Rows
	 *
	 * @param	string	$k	ID
	 * @return	array
	 */
	public function restrictionsGetChildren( string $k ) : array
	{
		$rows = array();

		if ( mb_substr( $k, 0, 1 ) === 'M' )
		{
			$k = mb_substr( $k, 1 );
			foreach ( $this->restrictions[ $k ] as $groupKey => $r )
			{
				$rows[ $groupKey ] = $this->_restrictionsGroupRow( $this->url, $groupKey, $k, $r, FALSE );
			}
		}
		else
		{
			$moduleKey = mb_substr( $k, 1, mb_strpos( $k, '-' ) - 1 );
			$groupKey = mb_substr( $k, mb_strpos( $k, '-' ) + 1 );

			$pos = 0;
			foreach ( $this->restrictions[ $moduleKey ][ $groupKey ] as $rKey )
			{
				$lang = "r__{$rKey}";
				$rows[ str_replace( '_', '~', $rKey ) ] = Theme::i()->getTemplate( 'trees' )->row( $this->url, $rKey, Member::loggedIn()->language()->addToStack( $lang ), FALSE, array(
					'delete' => array(
						'icon'	=> 'times-circle',
						'title'	=> 'delete',
						'link'	=> Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&do=restrictionRowDelete&module_key={$moduleKey}&group={$groupKey}&id={$rKey}" ),
						'data'	=> array( 'delete' => '' )
					)
				), $rKey, NULL, ++$pos );
			}
		}

		return $rows;
	}

	/**
	 * Get Module Row
	 *
	 * @param	string|Url	$url			URL
	 * @param	string	$k				Key
	 * @param	array	$restrictions	Restrictions JSON
	 * @param	bool	$root			As root?
	 * @return	string
	 */
	protected function _restrictionsModuleRow( string|Url $url, string $k, array $restrictions, bool $root ) : string
	{
		return Theme::i()->getTemplate( 'trees' )->row( $url, 'M'.$k, $k, isset( $restrictions[ $k ] ), array(
			'add'	=> array(
				'icon'	=> 'plus-circle',
				'title'	=> 'acprestrictions_addgroup',
				'link'	=> Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&do=restrictionGroupForm&module_key={$k}&id=0" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acprestrictions_addgroup') )
			)
		), '', NULL, NULL, $root );
	}

	/**
	 * Get Group Row
	 *
	 * @param	string|Url	$url		URL
	 * @param	string	$groupKey	Key
	 * @param	string	$moduleKey	Module Key
	 * @param	array	$r			Rows in this group
	 * @param	bool	$root		As root?
	 * @return	string
	 */
	protected function _restrictionsGroupRow( string|Url $url, string $groupKey, string $moduleKey, array $r, bool $root ) : string
	{
		$lang = "r__{$groupKey}";
		return Theme::i()->getTemplate( 'trees' )->row( $url, "G{$moduleKey}-{$groupKey}", Member::loggedIn()->language()->addToStack( $lang ), !empty( $r ), array(
			'add'	=> array(
				'icon'	=> 'plus-circle',
				'title'	=> 'acprestrictions_addrow',
				'link'	=> Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&do=restrictionRowForm&module_key={$moduleKey}&group={$groupKey}" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acprestrictions_addrow') )
			),
			'edit'	=> array(
				'icon'	=> 'pencil',
				'title'	=> 'edit',
				'link'	=> Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&do=restrictionGroupForm&module_key={$moduleKey}&id={$groupKey}" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') ),
				'hotkey'=> 'e'
			),
			'delete'=> array(
				'icon'	=> 'times-circle',
				'title'	=> 'delete',
				'link'	=> Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&do=restrictionGroupDelete&module_key={$moduleKey}&id={$groupKey}" ),
				'data'	=> array( 'delete' => '' )
			)
		), '', NULL, NULL, $root );
	}

	/**
	 * Restriction Group Form
	 *
	 * @return	void
	 */
	public function restrictionGroupForm() : void
	{
		/* Get restriction data */
		$restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );

		/* Load module */
		$module = $this->_loadModule();

		/* Do we have a default value? */
		$current = NULL;
		if ( Request::i()->id and isset( $restrictions[ $module->key ][ Request::i()->id ] ) )
		{
			$current = Request::i()->id;
		}

		/* Build Form */
		$form = new Form();
		$form->add( new Text( 'acprestrictions_groupkey', $current ?: '', TRUE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$rows = array();
			if ( $current !== NULL )
			{
				$rows = $restrictions[ $module->key ][ $current ];
				unset( $restrictions[ $module->key ][ $current ] );
			}

			$restrictions[ $module->key ][ $values['acprestrictions_groupkey'] ] = $rows;
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json", $restrictions );
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&root=M{$module->key}" ) );
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'acprestrictions_addgroup', $form, FALSE );
	}

	/**
	 * Delete Restriction Group
	 *
	 * @return	void
	 */
	public function restrictionGroupDelete() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		$restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );
		$module = $this->_loadModule();

		if( isset( $restrictions[ $module->key ][ Request::i()->id ] ) )
		{
			unset( $restrictions[ $module->key ][ Request::i()->id ] );
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json", $restrictions );
		}

		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&root=M{$module->key}" ) );
	}

	/**
	 * Restriction Row Form
	 *
	 * @return	void
	 */
	protected function restrictionRowForm() : void
	{
		$module = $this->_loadModule();
		$group = Request::i()->group;

		$form = new Form();
		$form->add( new Text( 'acprestrictions_rowkey', NULL, TRUE ) );
		if ( $values = $form->values() )
		{
			$restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );
			$restrictions[ $module->key ][ $group ][ $values['acprestrictions_rowkey'] ] = $values['acprestrictions_rowkey'];
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json", $restrictions );
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&root=G{$module->key}-{$group}" ) );
		}
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'acprestrictions_addrow', $form, FALSE );
	}

	/**
	 * Delete Restriction Row
	 *
	 * @return	void
	 */
	public function restrictionRowDelete() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		$restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );
		$module = $this->_loadModule();
		$group = Request::i()->group;
		$id = Request::i()->id;

		if( isset( $restrictions[ $module->key ][ $group ][ $id ] ) )
		{
			unset( $restrictions[ $module->key ][ $group ][ $id ] );
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json", $restrictions );
		}

		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=acprestrictions&appKey={$this->application->directory}&root=G{$module->key}-{$group}" ) );
	}
}