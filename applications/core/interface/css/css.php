<?php

use IPS\Output;
use IPS\Request;
use IPS\Theme;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';

Output::setCacheTime( false );

if ( \IPS\IN_DEV !== true )
{
	exit();
}

$needsParsing = FALSE;

if( strstr( Request::i()->css, ',' ) )
{
	$contents = '';
	foreach( explode( ',', Request::i()->css ) as $css )
	{
		if ( mb_substr( $css, -4 ) !== '.css' )
		{
			continue;
		}
		
		$css	= str_replace( array( '../', '..\\' ), array( '&#46;&#46;/', '&#46;&#46;\\' ), $css );
		$file	= file_get_contents( \IPS\ROOT_PATH . '/' . $css );
		$params	= processFile( $file );
		
		if ( $params['hidden'] === 1 )
		{
			continue;
		}
		
		$contents .= "\n" . $file;

		if ( needsParsing( $css ) )
		{
			$needsParsing = TRUE;
		}
	}
}
else
{
	if ( mb_substr( Request::i()->css, -4 ) !== '.css' )
	{
		exit();
	}

	$contents  = file_get_contents( \IPS\ROOT_PATH . '/' . str_replace( array( '../', '..\\' ), array( '&#46;&#46;/', '&#46;&#46;\\' ), Request::i()->css ) );
	
	$params = processFile( $contents );
		
	if ( $params['hidden']  === 1 )
	{
		exit;
	}
	
	if ( needsParsing( Request::i()->css ) )
	{
		$needsParsing = TRUE;
	}
}

if ( $needsParsing )
{
	
	$functionName = 'css_' . mt_rand();
	Theme::makeProcessFunction( $contents, $functionName, '', false, true );
	$functionName = "IPS\Theme\\{$functionName}";
	Output::i()->sendOutput( $functionName(), 200, 'text/css' );
}
else
{ 
	Output::i()->sendOutput( $contents, 200, 'text/css' );
}

/**
 * Determine whether this file needs parsing or not
 *
 * @return boolean
 */
function needsParsing( $fileName )
{
	if( \IPS\IN_DEV === TRUE )
	{
		preg_match( '#applications/(.+?)/dev/css/(.+?)/(.*)\.css#', $fileName, $appMatches );
		preg_match( '#plugins/(.+?)/dev/css/(.*)\.css#', $fileName, $pluginMatches );
		return ( count( $appMatches ) or count( $pluginMatches ) );
	}
	else
	{
		preg_match( '#themes/(?:\d+)/css/(.+?)/(.+?)/(.*)\.css#', $fileName, $themeMatches );
		return count( $themeMatches );
	}

	return FALSE;
}

/**
 * Process the file to extract the header tag params
 *
 * @return array
 */
function processFile( $contents )
{
	$return = array( 'module' => '', 'app' => '', 'pos' => '', 'hidden' => 0 );
	
	/* Parse the header tag */
	preg_match_all( '#^/\*<ips:css([^>]+?)>\*/\n#', $contents, $params, PREG_SET_ORDER );
	foreach( $params as $param )
	{
		preg_match_all( '#([\d\w]+?)=\"([^"]+?)"#i', $param[1], $items, PREG_SET_ORDER );
			
		foreach( $items as $id => $attr )
		{
			switch( trim( $attr[1] ) )
			{
				case 'module':
					$return['module'] = trim( $attr[2] );
					break;
				case 'app':
					$return['app'] = trim( $attr[2] );
					break;
				case 'position':
					$return['pos'] = intval( $attr[2] );
					break;
				case 'hidden':
					$return['hidden'] = intval( $attr[2] );
					break;
			}
		}
	}
	
	return $return;
}