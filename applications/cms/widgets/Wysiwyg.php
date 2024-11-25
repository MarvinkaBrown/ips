<?php
/**
 * @brief		WYSIWYG Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		22 Aug 2014
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Output;
use IPS\Widget\StaticCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * WYSIWYG Widget
 */
class Wysiwyg extends StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'Wysiwyg';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';
		


	/**
	 * Specify widget configuration
	 *
	 * @param	Form|NULL	$form	Form helper
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );
 		
		$form->add( new Editor( 'content', ( $this->configuration['content'] ?? NULL ), FALSE, array(
			'app'			=> $this->app,
			'key'			=> 'Widgets',
			'autoSaveKey' 	=> 'widget-' . $this->uniqueKey,
			'attachIds'	 	=> isset( $this->configuration['content'] ) ? array( 0, 0, $this->uniqueKey ) : NULL
		) ) );
		
		return $form;
 	}
 	
 	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( '*', 'core_attachments_map', array( array( 'location_key=? and id3=?', 'cms_Widgets', $this->uniqueKey ) ) ) as $map )
		{
			try
			{				
				$attachment = Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $map['attachment_id'] ) )->first();
				
				Db::i()->delete( 'core_attachments_map', array( array( 'attachment_id=?', $attachment['attach_id'] ) ) );
				Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );
				
				
				File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
				if ( $attachment['attach_thumb_location'] )
				{
					File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
				}
			}
			catch ( Exception $e ) { }
		}
	}
	
 	/**
 	 * Pre-save config method
 	 *
 	 * @param	array	$values		Form values
 	 * @return array
 	 */
 	public function preConfig( array $values=array() ) : array
 	{
	 	File::claimAttachments( 'widget-' . $this->uniqueKey, 0, 0, $this->uniqueKey );
	 	
	 	return $values;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_core.js', 'core' ) );
		return $this->output( $this->configuration['content'] ?? '' );
	}
}