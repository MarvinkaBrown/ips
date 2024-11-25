<?php
/**
 * @brief		Ignore Record
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Aug 2013
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Ignore Record
 */
class Ignore extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_ignored_users';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'ignore_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'ignore_ignore_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * Get types
	 *
	 * @return	array
	 */
	public static function types() : array
	{
		$types = array( 'topics', 'messages', 'mentions' );
		
		if( Settings::i()->signatures_enabled )
		{
			$types[] = 'signatures';
		}
		
		return $types;
	}
	
	/**
	 * Display Form
	 *
	 * @return	Form
	 */
	public static function form() : Form
	{
		$ignore = NULL;
		try
		{
			$ignore = static::load( Request::i()->id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
		}
		catch( OutOfRangeException $e )
		{
			if ( Request::i()->id )
			{
				$ignore = new static;
				$ignore->ignore_id = Request::i()->id;
			}
		}
		
		$form = new Form( 'ignore_form', $ignore ? 'ignore_edit' : 'ignore_submit' );
		$form->class = 'ipsForm--vertical ipsForm--ignore-record';
		$form->add( new FormMember( 'member', $ignore ? Member::load( $ignore->ignore_id ) : NULL, TRUE, array( 'placeholder' => Member::loggedIn()->language()->addToStack('ignore_placeholder') ) ) );
		
		foreach ( static::types() as $type )
		{
			$form->add( new Checkbox( "ignore_{$type}", $ignore ? $ignore->$type : NULL ) );
		}
				
		return $form;
	}
	
	/**
	 * Create from form
	 *
	 * @param	array	$values	Values from form
	 * @return    Ignore
	 */
	public static function createFromForm( array $values ) : static
	{
		try
		{
			$obj = static::load( $values['member']->member_id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
		}
		catch ( OutOfRangeException $e )
		{
			$obj = new static;
		}
		
		if ( $values['member']->member_id == Member::loggedIn()->member_id )
		{
			throw new InvalidArgumentException( 'cannot_ignore_self' );
		}
		
		if ( !$values['member']->canBeIgnored() )
		{
			throw new InvalidArgumentException( 'cannot_ignore_that_member' );
		}
		
		$obj->owner_id	= Member::loggedIn()->member_id;
		$obj->ignore_id	= $values['member']->member_id;
		
		foreach ( static::types() as $type )
		{
			$obj->$type = $values["ignore_{$type}"];
		}

		$obj->save();
		
		Member::loggedIn()->members_bitoptions['has_no_ignored_users'] = FALSE;
		Member::loggedIn()->save();
		
		return $obj;
	}
}