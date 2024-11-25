<?php
/**
 * @brief		Security Questions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Aug 2016
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
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
 * Security Questions
 */
class securityquestions extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\MFA\SecurityQuestions\Question';

	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;

	/**
	 * Form
	 *
	 * @return	void
	 */
	public function form() : void
	{
		Output::i()->breadcrumb[] = array( Url::internal('app=core&module=settings&controller=mfa&tab=handlers'), Member::loggedIn()->language()->addToStack('menu__core_settings_mfa') );
		Output::i()->breadcrumb[] = array( Url::internal('app=core&module=settings&controller=mfa&tab=questions&do=settings&key=questions'), Member::loggedIn()->language()->addToStack("mfa_questions_title") );
		parent::form();
	}
		
	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$this->manage();
	}

	/**
	 * Redirect after save
	 *
	 * @param Model|null $old A clone of the node as it was before or NULL if this is a creation
	 * @param Model $new The node now
	 * @param string $lastUsedTab The tab last used in the form
	 * @return    void
	 */
	protected function _afterSave( ?Model $old, Model $new, mixed $lastUsedTab = FALSE ): void
	{
		if( Request::i()->isAjax() )
		{
			Output::i()->json( array() );
		}
		else
		{
			Output::i()->redirect( Url::internal('app=core&module=settings&controller=mfa&tab=handlers&do=settings&key=questions&tab=questions'), 'saved' );
		}
	}
}