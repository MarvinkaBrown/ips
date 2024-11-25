<?php
/**
 * @brief		Acronym Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Sept 2013
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Url;
use IPS\Helpers\Form\YesNo;
use IPS\Patterns\ActiveRecord;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Acronym Model
 */
class Acronym extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_acronyms';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = '';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'a_id';
	
	/**
	 * Display Form
	 *
	 * @param	static|NULL	$acronym	Acronym we are currently editing
	 * @return	Form
	 */
	public static function form( ?Acronym $acronym ) : Form
	{
		/* Build form */
		$form = new Form();
	
		$form->add( new Radio( 'word_a_type', ( $acronym ) ? $acronym->a_type : 'acronym', FALSE, array( 'options' => array( 'acronym' => 'word_type_acronym', 'link' => 'word_type_link' ), 'toggles' => array( 'acronym' => array( 'word_a_long' ), 'link' => array( 'word_a_url' ) ) ), NULL, NULL, NULL, 'word_a_type' ) );
		$form->add( new Text( 'word_a_short', ( $acronym ) ? $acronym->a_short : NULL, TRUE ) );
		$form->add( new Text( 'word_a_long', ( $acronym AND ( !$acronym->a_type OR $acronym->a_type == 'acronym' ) ) ? $acronym->a_long : NULL, NULL, array(), NULL, NULL, NULL, 'word_a_long' ) );
		$form->add( new Url( 'word_a_url', ( $acronym AND $acronym->a_type AND $acronym->a_type == 'link' ) ? $acronym->a_long : NULL, NULL, array(), NULL, NULL, NULL, 'word_a_url' ) );
		$form->add( new YesNo( 'word_a_casesensitive', ( $acronym ) ? $acronym->a_casesensitive : NULL, FALSE, array(), NULL, NULL, NULL, 'word_a_casesensitive' ) );
		
		return $form;
	}
	
	/**
	 * Create from form
	 *
	 * @param	array	$values	Values from form
	 * @param	static|null	$current	The acronym we are currently editing
	 * @return    Acronym
	 */
	public static function createFromForm( array $values, ?Acronym $current ) : Acronym
	{
		if( $current )
		{
			$obj = static::load( $current->a_id );
		}
		else
		{
			$obj = new static;
		}
		
		$obj->a_type = $values['word_a_type'];
		$obj->a_short = $values['word_a_short'];
		$obj->a_long = ( $values['word_a_type'] == 'acronym' ) ? $values['word_a_long'] : $values['word_a_url'];
		$obj->a_casesensitive = $values['word_a_casesensitive'];

		$obj->save();
	
		return $obj;
	}
}