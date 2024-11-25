<?php
/**
 * @brief		A HTMLPurifier Attribute Definition used for attributes which must be internal URLs or Integers (such as data-fileid for attachments)
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 October 2024
 */

namespace IPS\Text;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * A HTMLPurifier Attribute Definition used for attributes which must be internal URLs or Integers (such as data-fileid for attachments)
 */
class HtmlPurifierIntOrInternalLink extends HtmlPurifierInternalLinkDef
{
	/**
	 * Validate
	 *
	 * @param	string					$value
	 * @param	\HTMLPurifier_Config	$config
	 * @param	\HTMLPurifier_Context	$context
	 * @return	bool|string
	 */
	public function validate( $value, $config, $context ): bool|string
	{
		$parentCheck = parent::validate( $value, $config, $context );

		if( $parentCheck !== FALSE )
		{
			return $parentCheck;
		}

		$integer = new \HTMLPurifier_AttrDef_Integer( false );
		return $integer->validate( $value, $config, $context );
	}
}