<?php

use IPS\Application;
use IPS\File;
use IPS\Helpers\Form\Editor;
use IPS\Output;
use IPS\Session\Front;
use IPS\Settings;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/core/interface/editor/index.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
Front::i();

$pluginJs = "";
if( !IN_DEV )
{
	/* Call the hasPlugins method first so that we can be sure the JS is generated */
	if( Editor::hasPlugins() )
	{
		if( Settings::i()->editor_plugin_js )
		{
			try
			{
				$pluginJs = File::get( 'core_Theme', Settings::i()->editor_plugin_js )->contents();

			} catch ( Exception ) {}
		}
	}
}
else
{
	foreach( Application::enabledApplications() as $app )
	{
		$path = Application::getRootPath( $app->directory ) . "/applications/" . $app->directory . "/dev/editor";
		if( file_exists( $path ) )
		{
			foreach( new DirectoryIterator( $path ) as $file )
			{
				if( !$file->isDir() and !$file->isDot() and $file->getFilename() != 'js' )
				{
					$components = explode( '.', $file->getFilename() );
					$extension = array_pop( $components );
					if( $extension == 'js' )
					{
						$contents = file_get_contents( $path . "/" . $file->getFilename() );
						$module = "/applications/" . $app->directory . "/dev/editor/" . $file->getFilename();
						$pluginJs .= <<<js
;((() => {
"use strict";
/**
* @module {$module}
*/
try {
    
{$contents}

} catch (e) {
    
window.Debug?.error(e)

}
})())
js;

					}
				}
			}
		}
	}


	/* This tells the editor that the file is done processing */
	$pluginJs .= <<<js

document.dispatchEvent(new CustomEvent('ips:editorPluginsReady'));
js;

}

Output::i()->sendOutput( $pluginJs, "200", "text/javascript" );