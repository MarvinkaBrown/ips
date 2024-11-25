<?php
/**
 * @brief		Magic Template Class for IN_DEV mode
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		18 Feb 2013
 */

namespace IPS\cms\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\cms\Theme;
use IPS\Db;
use IPS\Theme\Template as ThemeTemplate;
use UnderflowException;
use function defined;
use function function_exists;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Magic Template Class for IN_DEV mode
 */
class Template extends ThemeTemplate
{
	/**
	 * @brief	Source Folder
	 */
	public ?string $sourceFolder = NULL;

	/**
	 * Extract the data object from the param tag
	 * @note We always assume the tag is in the format of <ips:template parameters="" data="" />
	 *
	 * @param string $tag <ips:template> tag
	 * @param string $key Data key to fetch
	 * @return string|null
	 */
	public static function extractDataFromTag( string $tag, string $key="data" ): ?string
	{
		if ( ! preg_match( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $tag, $matches ) )
		{
			return NULL;
		}
		
		if ( preg_match( '#' . $key . '="([^"]+?)"#', $matches[2], $submatches ) )
		{
			return $submatches[1];
		}
		
		return NULL;
	}
	
	/**
	 * Extract the data object from the param tag
	 * @note We always assume the tag is in the format of <ips:template parameters="" data="" />
	 *
	 * @param string $tag	<ips:template> tag
	 * @return  null|string
	 */
	public static function extractParamsFromTag( string $tag ): ?string
	{
		if ( ! preg_match( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $tag, $matches ) )
		{
			return $matches[1];
		}
		
		return NULL;
	}
	
	/**
	 * Contructor
	 *
	 * @param	string	$app				Application Key
	 * @param	string	$templateLocation	Template location (admin/public/etc.)
	 * @param	string	$templateName		Template Name
	 * @return	void
	 */
	public function __construct( string $app, string $templateLocation, string $templateName )
	{
		parent::__construct( $app, $templateLocation, $templateName );
		$this->app = $app;
		$this->templateLocation = $templateLocation;
		$this->templateName = $templateName;
		
		if ( \IPS\IN_DEV )
		{
			$this->sourceFolder = \IPS\ROOT_PATH . "/applications/{$app}/dev/html/{$templateLocation}/{$templateName}/";
		}
	}
	
	/**
	 * Magic Method: Call Template Bit
	 *
	 * @param string $bit	Template Bit Name
	 * @param array $params	Parameters
	 */
	public function __call( string $bit, array $params )
	{
		if ( \IPS\IN_DEV )
		{
			/* What are we calling this? */
			$functionName = "theme_{$this->app}_{$this->templateLocation}_{$this->templateName}_{$bit}";
	
			/* If it doesn't exist, build it */
			if( !function_exists( 'IPS\\Theme\\'.$functionName ) )
			{
				/* Find the file */
				$file = $this->sourceFolder . $bit . '.phtml';
				
				/* Get the content */
				if ( !file_exists( $file ) )
				{
					/* Try the database */
					try
					{
						$template = Db::i()->select( '*', 'cms_templates', array( 'template_location=? and LOWER(template_group)=? and template_title=?', $this->templateLocation, $this->templateName, $bit ) )->first();
						
						Theme::makeProcessFunction( $template['template_content'], $functionName, $template['template_params'], TRUE );
					}
					catch ( UnderflowException $e )
					{
						throw new BadMethodCallException( 'NO_TEMPLATE_FILE - ' . $file );
					}
				}
				else
				{
					
					$output = file_get_contents( $file );
					
					/* Parse the header tag */
					if ( !preg_match( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $output, $matches ) )
					{
						throw new BadMethodCallException( 'NO_HEADER - ' . $file );
					}
					
					/* Strip it */
					$output = preg_replace( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', '', $output );
					
					/* Make it into a lovely function */
					Theme::makeProcessFunction( $output, $functionName, ( $matches[1] ?? '' ), TRUE );
				}
			}
			
			/* Run it */
			ob_start();
			$template = 'IPS\\Theme\\'.$functionName;
			$return = $template( ...$params );
			if( $error = ob_get_clean() )
			{
				echo "<strong>{$functionName}</strong><br>{$error}<br><br><pre>{$output}";
				exit;
			}
			
			/* Return */
			return $return;
		}
	}
	
}