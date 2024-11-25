<?php

use IPS\Db;
use IPS\Helpers\Form\Radio;
use IPS\Settings;

$options = array();
require "../upgrade/lang.php";

/* Is open tagging enabled? */
if( Settings::i()->tags_open_system == 'open' )
{
	$options[] = new Radio( '200000_open_tags', null, TRUE, [
		'options' => [ 'convert' => '200000_open_tags_convert', 'all' => '200000_open_tags_all', 'delete' => '200000_open_tags_delete' ]
	] );

	/* Do we have application-specific tags? */
	$nodeTagMapping = [
		'cms' => [ 'table' => 'cms_databases', 'field' => 'database_tags_predefined' ],
		'downloads' => [ 'table' => 'downloads_categories', 'field' => 'ctags_predefined' ],
		'forums' => [ 'table' => 'forums_forums', 'field' => 'tag_predefined' ],
		'gallery' => [ 'table' => 'gallery_categories', 'field' => 'category_preset_tags' ]
	];

	$nodeTags = false;
	foreach( $nodeTagMapping as $app => $data )
	{
		if( Db::i()->checkForTable( $data['table'] ) )
		{
			$hasTags = (bool) Db::i()->select( 'count(*)', $data['table'], [ "{$data['field']} is not null and {$data['field']} != ?", '' ] )->first();
			if( $hasTags )
			{
				$nodeTags = true;
				break;
			}
		}
	}

	if( $nodeTags )
	{
		$options[] = new Radio( '200000_node_tags', null, true, [
			'options' => [ 'convert' => '200000_node_tags_convert', 'delete' => '200000_node_tags_delete' ]
		] );
	}
}