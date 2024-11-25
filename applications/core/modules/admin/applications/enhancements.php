<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use LogicException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancements
 */
class enhancements extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * @brief Enhancements plugins
	 */
	protected array $enhancements = array();
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'enhancements_manage' );
		$this->enhancements = Application::allExtensions( 'core', 'CommunityEnhancements' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Collect the enhancements into an array */
		$rows	= array();
				
		foreach( $this->enhancements as $key => $class )
		{
			if ( !method_exists( $class, 'isAvailable' ) or $class::isAvailable() )
			{
				$rows[ (int) $class->ips ][ $key ]	= array(
					'title'			=> "enhancements__{$key}",
					'description'	=> "enhancements__{$key}_desc",
					'app'			=> mb_substr( $key, 0, mb_strpos( $key, '_' ) ),
					'icon'			=> $class->icon,
					'enabled'		=> $class->enabled,
					'config'		=> (bool) $class->hasOptions
				);
			}
		}
		
		/* Display */
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/enhancements.css', 'core', 'admin' ) );
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_applications_enhancements');
		Output::i()->output 	= Theme::i()->getTemplate( 'applications' )->enhancements( $rows );
	}
	
	/**
	 * Edit
	 *
	 * @csrfChecked	Extension files use form helper 7 Oct 2019
	 * @return	void
	 */
	public function edit() : void
	{
		if ( isset( $this->enhancements[ Request::i()->id ] ) and ( !method_exists( $this->enhancements[ Request::i()->id ], 'isAvailable' ) or $this->enhancements[ Request::i()->id ]::isAvailable() ) )
		{
			$langKey = 'enhancements__' . Request::i()->id;
			Output::i()->title = Member::loggedIn()->language()->addToStack( $langKey );

			try
			{
				$this->enhancements[ Request::i()->id ]->edit();
			}
			catch ( DomainException $e )
			{
				Output::i()->error( $e->getMessage(), $e->getCode() );
			}
		}
		else
		{
			Output::i()->error( 'node_error', '2C115/1', 404, '' );
		}
	}
	
	/**
	 * Toggle
	 *
	 * @return	void
	 */
	public function enableToggle() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$this->enhancements[ Request::i()->id ]->toggle( Request::i()->status );
			Session::i()->log( Request::i()->status ? 'acplog__enhancements_enable' : 'acplog__enhancements_disable', array( 'enhancements__' . Request::i()->id => TRUE ) );

			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=enhancements" ), Request::i()->status ? Member::loggedIn()->language()->addToStack('acplog__enhancements_enable', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'enhancements__' . Request::i()->id ) ) ) ) : Member::loggedIn()->language()->addToStack('acplog__enhancements_disable', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'enhancements__' . Request::i()->id ) ) ) ) );
		}
		catch ( LogicException $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->error( $e->getMessage(), $e->getCode() );
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=applications&controller=enhancements&do=edit&id=" . Request::i()->id ) );
			}
		}
	}
}