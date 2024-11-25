<?php
/**
 * @brief		Upgrader Language Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Feb 2015
 */

namespace IPS\Lang\Upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Setup Language Class
 */
class Lang extends \IPS\Lang
{
	/**
	 * Add to output stack
	 *
	 * @param string $key	Language key
	 * @param bool|null $vle	Add VLE tags?
	 * @param array $options Options
	 * @return	string	Unique id
	 */
	public function addToStack( string $key, ?bool $vle=TRUE, array $options=array() ): string
	{
		/* If the key is not in the words list, try to pull it from the DB */
		if( !array_key_exists( $key, $this->words ) and !count( $options ) )
		{
			try
			{
				$row = Db::i()->select( '*', 'core_sys_lang_words', [ 'word_key=? and word_js=? and lang_id=?', $key, 0, static::defaultLanguage() ] )->first();
				$this->words[ $key ] = $row['word_custom'] ?? $row['word_default'];
			}
			catch( UnderflowException ){}
		}

		return parent::addToStack( $key, $vle, $options );
	}
}