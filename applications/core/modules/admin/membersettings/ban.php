<?php
/**
 * @brief		ban
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Apr 2013
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use UnderflowException;
use function defined;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ban
 */
class ban extends Controller
{	
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'ban_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		$table = new TableDb( 'core_banfilters', Url::internal( 'app=core&module=membersettings&controller=ban' ) );
		
		$table->filters = array(
				'ban_filter_ip'		=> 'ban_type=\'ip\'',
				'ban_filter_email'	=> 'ban_type=\'email\'',
				'ban_filter_name'	=> 'ban_type=\'name\''
		);
		
		$table->include    = array( 'ban_type', 'ban_content', 'ban_reason', 'ban_date' );
		$table->mainColumn = 'ban_content';
		$table->rowClasses = array( 'ban_reason' => array( 'ipsTable_wrap' ) );
		
		$table->sortBy        = $table->sortBy        ?: 'ban_date';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->quickSearch   = 'ban_content';
		$table->advancedSearch = array(
			'ban_reason'	=> SEARCH_CONTAINS_TEXT,
			'ban_date'		=> SEARCH_DATE_RANGE
		);
		
		/* Custom parsers */
		$table->parsers = array(
				'ban_date'			=> function( $val )
				{
					return DateTime::ts( $val )->localeDate();
				},
				'ban_type'			=> function( $val )
				{
					switch( $val )
					{
						default:
						case 'ip':
							return Member::loggedIn()->language()->addToStack('ban_filter_ip_select');

						case 'email':
							return Member::loggedIn()->language()->addToStack('ban_filter_email_select');

						case 'name':
							return Member::loggedIn()->language()->addToStack('ban_filter_name_select');

					}
				}
		);
		
		/* Row buttons */
		$table->rowButtons = function( $row )
		{
			$return = array();
		
			$return['edit'] = array(
						'icon'		=> 'pencil',
						'title'		=> 'edit',
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') ),
						'link'		=> Url::internal( 'app=core&module=membersettings&controller=ban&do=form&id=' ) . $row['ban_id'],
			);
			
		
		
			$return['delete'] = array(
						'icon'		=> 'times-circle',
						'title'		=> 'delete',
						'link'		=> Url::internal( 'app=core&module=membersettings&controller=ban&do=delete&id=' ) . $row['ban_id'],
						'data'		=> array( 'delete' => '' ),
			);
		
			return $return;
		};
		
		/* Specify the buttons */
		Output::i()->sidebar['actions'] = array(
			'add'	=> array(
				'primary'	=> TRUE,
				'icon'		=> 'plus',
				'title'		=> 'ban_filter_add',
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('ban_filter_add') ),
				'link'		=> Url::internal( 'app=core&module=membersettings&controller=ban&do=form' )
			)
		);
		
        /* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_membersettings_ban');
		Output::i()->output	= (string) $table;
	}
	
	/**
	 * Add/Edit Rank
	 */
	public function form() : void
	{
		$current = NULL;
		if ( Request::i()->id )
		{
			$current = Db::i()->select( '*', 'core_banfilters', array( 'ban_id=?', Request::i()->id ) )->first();
		}
	
		/* Build form */
		$form = new Form();
		$form->add( new Select( 'ban_type', $current ? $current['ban_type'] : NULL , TRUE, array( 'options' => array(
			'ip'    => 'ban_filter_ip_select',
			'email' => 'ban_filter_email_select',
			'name'  => 'ban_filter_name_select'
		) ) ) );
		$form->add( new Text( 'ban_content', $current ? $current['ban_content'] : "", TRUE ) );
		$form->add( new Text( 'ban_reason', $current ? $current['ban_reason'] : "" ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$save = array(
				'ban_type'    => $values['ban_type'],
				'ban_content' => $values['ban_content'],
				'ban_reason'  => $values['ban_reason'],
				'ban_date'	  => time()  
			);
				
			if ( $current )
			{
				unset( $save['ban_date'] );
				Db::i()->update( 'core_banfilters', $save, array( 'ban_id=?', $current['ban_id'] ) );
				Session::i()->log( 'acplog__ban_edited', array( 'ban_filter_' . $save['ban_type'] . '_select' => TRUE, $save['ban_content'] => FALSE ) );
			}
			else
			{
				Db::i()->insert( 'core_banfilters', $save );
				Session::i()->log( 'acplog__ban_created', array( 'ban_filter_' . $save['ban_type'] . '_select' => TRUE, $save['ban_content'] => FALSE ) );
			}
			
			unset( Store::i()->bannedIpAddresses );
	
			Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=ban' ), 'saved' );
		}
	
		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( $current ? $current['ban_content'] : 'add', $form, FALSE );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		try
		{
			$current = Db::i()->select( '*', 'core_banfilters', array( 'ban_id=?', Request::i()->id ) )->first();
			Session::i()->log( 'acplog__ban_deleted', array( 'ban_filter_' . $current['ban_type'] . '_select' => TRUE, $current['ban_content'] => FALSE ) );
			Db::i()->delete( 'core_banfilters', array( 'ban_id=?', Request::i()->id ) );
			unset( Store::i()->bannedIpAddresses );
		}
		catch ( UnderflowException $e ) { }
	
		Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=ban' ) );
	}
}