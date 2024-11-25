<?php
/**
 * @brief		A HTMLPurifier Attribute Definition which imitates HTMLPurifier_AttrDef_Switch but allows checking if certain attributes exist
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		4 May 2016
 */

namespace IPS\Text;

/* To prevent PHP errors (extending class does not exist) revealing path */

use HTMLPurifier_AttrDef;
use HTMLPurifier_AttrDef_Switch;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * A HTMLPurifier Attribute Definition which imitates HTMLPurifier_AttrDef_Switch but allows checking if certain attributes exist
 */
class HtmlPurifierSwitchAttrDef extends HTMLPurifier_AttrDef_Switch
{
	/**
	 * @brief	Is the attribute required
	 */
	public bool $required = FALSE;
	
	/**
	 * @brief	Attributes
	 */
	protected array $attributes = array();
	
	/**
	 * Constructor
	 *
     * @param	string					$tag			The tag name to check
     * @param	array					$attributes		The attributes to check on
     * @param	HTMLPurifier_AttrDef	$with_tag		If $tag matches and all $attributes are present, this definition will be used
     * @param	HTMLPurifier_AttrDef	$without_tag	Otherwise, this definition will be used
     */
    public function __construct( string $tag, array $attributes, $with_tag, HTMLPurifier_AttrDef $without_tag )
    {
	    $this->attributes = $attributes;
		parent::__construct( $tag, $with_tag, $without_tag );
    }
    
	/**
	 * Validate
	 * 
     * @param	string					$string
     * @param	HTMLPurifier_Config	$config
     * @param	HTMLPurifier_Context	$context
     * @return	bool|string
     */
    public function validate( $string, $config, $context ): bool|string
	{
	    $token = $context->get('CurrentToken', true);
	    
        if ( count( array_diff( $this->attributes, array_keys( $token->attr ) ) ) )
        {
			return $this->withoutTag->validate( $string, $config, $context );
        }
        
		return parent::validate( $string, $config, $context );
	}
}