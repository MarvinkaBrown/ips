<?php
/**
 * @brief		Class for managing small XML files
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Xml;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DOMDocument;
use InvalidArgumentException;
use SimpleXMLElement;
use function count;
use function defined;
use function get_called_class;
use function gettype;
use function in_array;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Class for managing small XML files
 */
class SimpleXML extends SimpleXMLElement
{
	/**
	 * Create New XML Document
	 *
	 * @param string $rootElement	Name of Root Element
	 * @param string|null $xmlns		Namespace
	 * @return	static
	 */
	public static function create( string $rootElement, string $xmlns=NULL ): static
	{
		if ( $xmlns )
		{
			return static::loadString( "<?xml version='1.0' encoding='UTF-8'?><{$rootElement} xmlns='{$xmlns}'></{$rootElement}>" );
		}
		else
		{
			return static::loadString( "<?xml version='1.0' encoding='UTF-8'?><{$rootElement}></{$rootElement}>" );
		}
	}
	
	/**
	 * Load File
	 *
	 * @param string $filename	Filename of XML file
	 * @return	static
	 * @throws	InvalidArgumentException
	 */
	public static function loadFile( string $filename ): static
	{
		return static::loadString( file_get_contents( $filename ) );
	}
	
	/**
	 * Load String
	 *
	 * @param string $xml	XML
	 * @return	static
	 * @throws	InvalidArgumentException
	 */
	public static function loadString( string $xml ): static
	{
		if ( version_compare( PHP_VERSION, '8.0.0' ) >= 0 )
		{
			$class = @simplexml_load_string( $xml, get_called_class() );
		}
		else
		{
			/* Commented out because this is deprecated */
			/* Turn off external entity loader to prevent XXE in PHP less than 8.0 */
			//$entityLoaderValue = libxml_disable_entity_loader( TRUE );
			
			/* Load it */
			$class = @simplexml_load_string( $xml, get_called_class() );

			/* Commented out because this is deprecated */
			/* Turn external entity loader back to what it was before so we're not messing with other
				PHP scripts on this server */
			//libxml_disable_entity_loader( $entityLoaderValue );
		}
		
		/* Return */
		if ( $class === FALSE )
		{
			throw new InvalidArgumentException;
		}
		return $class->subtype();
	}
	
	/**
	 * Get appropriate subtype
	 *
	 * @return	static
	 */
	protected function subtype(): static
	{
		if ( get_called_class() === 'IPS\Xml\SimpleXML' )
		{
			if ( $this->getName() === 'rss' )
			{
				return Rss::loadString( $this->asXml() );
			}
			if ( $this->getName() === 'feed' )
			{
				return Atom::loadString( $this->asXml() );
			}
			if ( mb_strtolower( $this->getName() ) === 'rdf' )
			{
				/* Verify this is an RSS 1.0 document */
				if( in_array( 'https://purl.org/rss/1.0/', $this->getNamespaces( true ) ) )
				{
					return Rss1::loadString( $this->asXml() );
				}
			}
		}

		return $this;
	}

	/**
	 * Get articles
	 *
	 * @param mixed|null $guidKey	In previous versions, we encoded a key with the GUID. For legacy purposes, this can be passed here.
	 * @return	array
	 * @note		Subtypes (ATOM and RSS) define this method
	 * @throws	BadMethodCallException
	 */
	public function articles( mixed $guidKey=NULL ): array
	{
		throw new BadMethodCallException;
	}
	
	/**
	 * Add Child
	 * Modified to support passing other data types (including multi-dimensional arrays and other \IPS\XML\SimpleXML objects) as values
	 *
	 * @param	string		$qualifiedName	Element Name
	 * @param	mixed		$value	Value
	 * @param	string|null	$namespace		Namespace
	 * @return    SimpleXMLElement|null
	 *@see		<a href='http://www.php.net/manual/en/simplexmlelement.addchild.php'>SimpleXMLElement::addChild</a>
	 */
	public function addChild( string $qualifiedName, mixed $value=null, ?string $namespace=null ): null|static
	{		
		/* Arrays are possibly numerically indexed, which XML won't have */
		if( gettype( $qualifiedName ) === 'integer' )
		{
			$qualifiedName = $this->getName();
			
			/* If the name ends in s (e.g. <elements>) - we'll name it's children without the s (e.g. <element>) */
			if( mb_substr( $qualifiedName, -2 ) === 'es' )
			{
				$qualifiedName = mb_substr( $qualifiedName, 0, mb_strlen( $qualifiedName ) - 2 );
			}
			else if( mb_substr( $qualifiedName, -1 ) === 's' )
			{
				$qualifiedName = mb_substr( $qualifiedName, 0, mb_strlen( $qualifiedName ) - 1 );
			}				
		}
		
		/* If it's not an array, we can just let the default SimpleXML method handle this */
		if( !is_array( $value ) and !( $value instanceof SimpleXML) )
		{			
			/* Unless it's a boolean value, then we should cast that to an integer */
			if( gettype( $value ) === 'boolean' )
			{
				$value = intval( $value );
			}
			
			/* Needs CDATA? */
			if ( preg_match( '/[<>&]/', $value ) )
			{
				$element = parent::addChild( $qualifiedName, '', $namespace );

				$node = dom_import_simplexml( $element ); 
				$no = $node->ownerDocument; 
				$node->appendChild( $no->createCDATASection( $value ) );
				
				return $element;
			}
			else
			{
				return parent::addChild( $qualifiedName, $value, $namespace );
			}
			
			/* Return */
		}
		/* If it is an array, we need to be a little clever */
		else
		{			
			/* Create an element with a blank value */
			$element = parent::addChild( $qualifiedName, '', $namespace );
			
			/* And loop through each value and rerun this method for each */
			foreach ( $value as $k => $v )
			{
				/* If the value is an XML text element, just get the value */
				if ( $v instanceof SimpleXML and count( $v->children() ) === 0 )
				{
					$v = (string) $v;
				}
				
				/* And add it */
				$element->addChild( $k, $v, $namespace );
			}
		}

		return null;
	}
	
	/**
	 * Remove Node
	 *
	 * @return	void
	 */
	public function removeNode()
	{
		
	}
	
	/**
	 * Format XML in a readable way (normally only used for debugging purposes)
	 *
	 * @return	string
	 */
	public function format(): string
	{
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		@$dom->loadXML( $this->asXML() );
		return $dom->saveXML();
	}
}