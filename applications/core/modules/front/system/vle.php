<?php
/**
 * @brief		Visual Language Editor
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Jun 2013
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Widget;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Visual Language Editor
 */
class vle extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			Output::i()->json( 'NO_PERMISSION', 403 );
		}
		parent::execute();
	}
	
	/**
	 * Get
	 *
	 * @return	void
	 */
	public function get() : void
	{
		try
		{
			$word = Member::loggedIn()->language()->get( Request::i()->key );
		}
		catch ( UnderflowException $e )
		{
			$word = NULL;
		}
		
		Output::i()->json( $word );
	}
	
	/**
	 * Set
	 *
	 * @return	void
	 */
	public function set() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$word	= Db::i()->select( '*', 'core_sys_lang_words', array( 'lang_id=? AND word_key=?', Member::loggedIn()->language()->id, Request::i()->key ) )->first();
		}
		catch( UnderflowException $e )
		{
			$word	= NULL;
		}

		try
		{
			$application = Application::load( $word['word_app'] );
		}
		catch ( Exception $e )
		{
			return;
		}

		if ( $word !== NULL )
		{
			if ( $word['word_export'] )
			{
				Db::i()->update( 'core_sys_lang_words', array( 'word_custom' => Request::i()->value, 'word_custom_version' => $application->long_version ), array( 'word_id=?', $word['word_id'] ) );
			}
			else
			{
				Lang::saveCustom( $word['word_app'], $word['word_key'], array( Member::loggedIn()->language()->id => Request::i()->value ) );
			}

			$string	= Request::i()->value ?: $word['word_default'];
		}
		else
		{
			Lang::saveCustom( 'core', Request::i()->key, array( Member::loggedIn()->language()->id => Request::i()->value ) );

			$string	= Request::i()->value;
		}

		Db::i()->insert( 'core_admin_logs', array(
			'member_id'		=> Member::loggedIn()->member_id,
			'member_name'	=> Member::loggedIn()->name,
			'ctime'			=> time(),
			'note'			=> json_encode( array( $word['word_key'] => FALSE, Member::loggedIn()->language()->title => FALSE ) ),
			'ip_address'	=> Request::i()->ipAddress(),
			'appcomponent'	=> 'core',
			'module'		=> 'system',
			'controller'	=> 'vle',
			'do'			=> 'set',
			'lang_key'		=> 'acplogs__lang_translate'
		) );

		Widget::deleteCaches();

		Output::i()->sendOutput( $string, 200, 'text/text' );
	}
}