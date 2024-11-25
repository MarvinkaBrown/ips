<?php
/**
 * @brief		Output Class for use when the templating engine isn't available
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Oct 2013
 */

namespace IPS\Output;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Output;
use IPS\Theme;
use function defined;
use function strstr;
use function uniqid;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Output Class
 */
class System extends Output
{
	/**
	 * Display Error Screen
	 *
	 * @param string $message language key for error message
	 * @param mixed $code Error code
	 * @param int $httpStatusCode HTTP Status Code
	 * @param string|null $adminMessage language key for error message to show to admins
	 * @param array $httpHeaders Additional HTTP Headers
	 * @param string|null $extra Additional information (such as API error)
	 * @param int|string|null $faultyAppOrHookId
	 * @return    void
	 */
	public function error( string $message, mixed $code, int $httpStatusCode=500, string $adminMessage=NULL, array $httpHeaders=array(), string $extra=NULL, int|string $faultyAppOrHookId=NULL ) : void
	{
		/* Send output */
		$this->sendOutput( Theme::i()->getTemplate('global', 'core', 'global')->error( $message ), $httpStatusCode, 'text/html', $httpHeaders );
	}

	/**
	 * Send output
	 *
	 * @param string $output Content to output
	 * @param int $httpStatusCode HTTP Status Code
	 * @param string $contentType HTTP Content-type
	 * @param array $httpHeaders Additional HTTP Headers
	 * @param bool $cacheThisPage
	 * @param bool $pageIsCached
	 * @param bool $parseFileObjects
	 * @param bool $parseEmoji
	 * @return    void
	 */
	public function sendOutput(string $output='', int $httpStatusCode=200, string $contentType='text/html', array $httpHeaders=array(), bool $cacheThisPage=TRUE, bool $pageIsCached=FALSE, bool $parseFileObjects=TRUE, bool $parseEmoji=TRUE ) : void
	{
		/* Set HTTP status */
		if( isset( $_SERVER['SERVER_PROTOCOL'] ) and strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
		{
			header( "HTTP/1.0 {$httpStatusCode} " . static::$httpStatuses[ $httpStatusCode ] );
		}
		else
		{
			header( "HTTP/1.1 {$httpStatusCode} " . static::$httpStatuses[ $httpStatusCode ] );
		}
				
		/* Buffer output */
		if ( $output )
		{
			if( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) and strstr( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false and (bool) ini_get('zlib.output_compression') === false )
			{
				ob_start('ob_gzhandler');
			}
			else
			{
				ob_start();
			}
			
			print Theme::i()->getTemplate('global', 'core', 'global')->globalTemplate( $output, static::i()->title, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) );
		}

		/* Send headers */
		$size = ob_get_length();
		header( "Content-type: {$contentType};charset=UTF-8" );
		header( "Content-Length: {$size}" );
		foreach ( $httpHeaders as $header )
		{
			header( $header );
		}
		header( "Connection: close" );
		
		/* Flush and exit */
		ob_end_flush();
		flush();
		exit;
	}

	/**
	 * Redirect
	 *
	 * @param Url|string $url URL to redirect to
	 * @param string|null $message Optional message to display
	 * @param int $httpStatusCode HTTP Status Code
	 * @param bool $forceScreen If TRUE, an intermeditate screen will be shown
	 * @return    void
	 */
	public function redirect( Url|string $url, ?string $message='', int $httpStatusCode=301, bool $forceScreen=FALSE ) : void
	{
		if ( $forceScreen === TRUE )
		{
			$this->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'global' )->redirect( $url, $message ), $httpStatusCode );
		}
		
		$this->sendOutput( '', $httpStatusCode, '', array( "Location: {$url}" ) );
	}

	/**
     * Create a HTML file of the MySQL debug backtrace
     *
	 * @param array $debug
	 * @return string
	 */
	public static function generateDbLogHtml( array $debug ): string
	{
		$queryCount = count( $debug ) - 1; # The first query is the connection
		$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Debug Log</title>
    <style>
    	* {
    		font-family: Arial, sans-serif;
    	}
        table {
            max-width: 100%;
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
		 td.query {
			font-family: monospace;
			word-wrap: break-word;
			font-size: 14px;
			text-wrap: wrap;
		}
		
		td.details {
			width: 10%;
		}
		
        tr.collapsible-content {
            display: none;
            background-color: #e5e5e5;
        }

        .toggle-button {
            cursor: pointer;
            color: blue;
        }
        tr.explain-table {
			background-color: #f5f5f5;
		}
    </style>
    <script>
        function toggleRow(id) {
            const rows = document.querySelectorAll(`[data-row="content-\${id}"]`);
			// Loop through each row and toggle visibility
			rows.forEach(row => {
			  if (row.style.display === "none") {
                document.querySelector(`td[data-query="\${id}"]`).style.backgroundColor = "lightblue";
				// If the row is hidden, show it
				row.style.display = "table-row";
			  } else {
				// If the row is visible, hide it
				document.querySelector(`td[data-query="\${id}"]`).style.backgroundColor = "transparent";
				row.style.display = "none";
			  }
   			 });
        }
    </script>
</head>
<body>

<h1>{$queryCount} Queries</h1>

<table>
    <thead>
        <tr>
            <th>Function</th>
            <th>File</th>
            <th>Line</th>
            <th style="width:150px">Details</th>
        </tr>
    </thead>
    <tbody>
HTML;

        foreach ($debug as $index => $data)
        {
			$unique = md5( $data['query'] . uniqid() );
			$explain = null;
			$explainHtml = '';

			/* If this is a select query, we can get the explain */
			if ( stripos( $data['query'], 'SELECT' ) !== false )
			{
				if( $result = Db::i()->query( "EXPLAIN " . $data['query'] ) )
				{
					$explain = $result->fetch_assoc();
				}
			}

			if ( $explain )
			{
				$explainHtml = '<table class="explain"><tr>';
				foreach( array_keys( $explain ) as $header )
				{
					$explainHtml .= "<th>{$header}</th>";
				}
				$explainHtml .= '</tr><tr>';

				foreach( $explain as $key => $value )
				{
					$explainHtml .= "<td>{$value}</td>";
				}
				
				$explainHtml .= '</tr></table>';
			}


			$html .= <<<HTML
		<tr>
			<td data-query="{$unique}" colspan="3" class="query">{$data['query']}</td>
			<td class="details"><span class="toggle-button" onclick="toggleRow('{$unique}')">Expand/Collapse</span></td>
		</tr>
HTML;

            foreach( $data['backtrace'] as $id => $trace )
			{
				$trace['file'] = isset( $trace['file'] ) ? str_replace( \IPS\ROOT_PATH, '', $trace['file'] ) : '';
				$trace['line'] = $trace['line'] ?? '';
				$html .= <<<HTML
        <tr data-row="content-{$unique}" style="display: none" class="collapsible-content">
			<td>
				{$trace['function']}
			</td>
			<td>
				{$trace['file']}
			</td>
			<td>
				{$trace['line']}
			</td>
			<td></td>
		</tr>
HTML;
			}

			$html .= <<<HTML
		<tr data-row="content-{$unique}" style="display: none" class="collapsible-content explain-table">
			<td colspan="4">
				{$explainHtml}
			</td>
		</tr>
HTML;

        }

        $html .= <<<HTML
        

    </tbody>
</table>

</body>
</html>
HTML;

        return $html;
	}
}