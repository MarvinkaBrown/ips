<?php
/**
 * @brief		Emoticons
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 May 2013
 */

namespace IPS\core\modules\admin\editor;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function is_array;
use function mb_stristr;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Emoticons
 */
class emoticons extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'emoticons_manage' );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customization/emoticons.css', 'core', 'admin' ) );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$activeTabContents = Theme::i()->getTemplate( 'customization' )->emoticons( $this->_getEmoticons() );

		Output::i()->title		= Member::loggedIn()->language()->addToStack('custom_emoji');
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('admin_customization.js', 'core', 'admin') );
		Output::i()->output = $activeTabContents; // @todo Mayyyyyyybe we should make one tab per set, but I really think custom emoticons are too dumb for all that
	}
	
	/**
	 * Add
	 *
	 * @return	void
	 */
	protected function add() : void
	{
		Dispatcher::i()->checkAcpPermission( 'emoticons_add' );
	
		$groups = iterator_to_array( Db::i()->select( "emo_set, CONCAT( 'core_emoticon_group_', emo_set ) as emo_set_name", 'core_emoticons', null, null, null, 'emo_set' )->setKeyField('emo_set')->setValueField('emo_set_name') );

		$form = new Form;
		$form->add( new Upload( 'emoticons_upload', NULL, TRUE, array( 'multiple' => TRUE, 'image' => TRUE, 'storageExtension' => 'core_Emoticons', 'storageContainer' => 'emoticons', 'obscure' => FALSE ) ) );
		$form->add( new Radio( 'emoticons_add_group', 'create', TRUE, array(
			'options'	=> array( 'create' => 'emoticons_add_create', 'existing' => 'emoticons_add_existing' ),
			'toggles'	=> array( 'create' => array( 'emoticons_add_newgroup' ), 'existing' => array( 'emoticons_add_choosegroup' ) ),
			'disabled'	=> empty($groups)
		) ) );
		$form->add( new Translatable( 'emoticons_add_newgroup', NULL, FALSE, array(), function( $value )
		{
			if ( Request::i()->emoticons_add_group === 'create' )
			{
				foreach ( Lang::languages() as $lang )
				{
					if ( $lang->default )
					{
						if( ! $value[ $lang->id ] )
						{		
							throw new InvalidArgumentException('form_required');
						}
					}
				}
			}
		}, NULL, NULL, 'emoticons_add_newgroup' ) );
		
		if ( !empty( $groups ) )
		{
			$form->add( new Select( 'emoticons_add_choosegroup', NULL, FALSE, array( 'options' => $groups ), NULL, NULL, NULL, 'emoticons_add_choosegroup' ) );
		}
		
		if ( $values = $form->values() )
		{
			if ( $values['emoticons_add_group'] === 'create' )
			{
				$position = 0;
				$setId = mt_rand();
				Lang::saveCustom( 'core', "core_emoticon_group_{$setId}", $values['emoticons_add_newgroup'] );
                Session::i()->log( 'acplog__emoticon_group_created', array( "core_emoticon_group_{$setId}" => TRUE ) );
			}
			else
			{
				$setId = $values['emoticons_add_choosegroup'];
				$position = Db::i()->select( 'MAX(emo_position)', 'core_emoticons', array( 'emo_set=?', $setId ) )->first( );
			}
					
			if ( !is_array( $values['emoticons_upload'] ) )
			{
				$values['emoticons_upload'] = array( $values['emoticons_upload'] );
			}
			
			$inserts = array();
			$images2x = array();
			foreach ( $values['emoticons_upload'] as $file )
			{
				/* Is it "retina" */
				if( mb_stristr( $file->filename, '@2x' ) )
				{
					$filename_2x = preg_replace( "/^(.+?)\.[0-9a-f]{32}(?:\..+?)$/i", "$1", str_replace( '@2x', '', $file->filename ) );

					$images2x[ $this->_getRawFilename( $filename_2x ) ] = (string) $file;
					continue;
				}

				$filename	= preg_replace( "/^(.+?)\.[0-9a-f]{32}(?:\..+?)$/i", "$1", $file->filename );

				$inserts[] = array(
					'typed'			=> ':' . preg_replace( "#\s#", "", $this->_getRawFilename( $filename ) ) . ':',
					'image'			=> (string) $file,
					'clickable'		=> TRUE,
					'emo_set'		=> $setId,
					'emo_position'	=> ++$position,
				);
			}

			if( count( $inserts ) )
			{
				Db::i()->insert( 'core_emoticons', $inserts );
			}

			/* Add 2x */
			if( count( $images2x ) )
			{
				foreach( Db::i()->select( '*', 'core_emoticons', array( 'emo_set=?', $setId ) ) as $emo )
				{
					$file = File::get( 'core_Emoticons', $emo['image'] );
					$filename = $this->_getRawFilename( $file->filename );

					/* There isn't an original for the 2x emo */
					if( !isset( $images2x[ $filename ] ) )
					{
						continue;
					}

					/* Get the dimensions of the smaller emoticon */
					$imageDimensions = $file->getImageDimensions();

					Db::i()->update( 'core_emoticons', array(
						'image_2x' => $images2x[ $filename ],
						'width' => $imageDimensions[0],
						'height' => $imageDimensions[1]
					), 'id=' . $emo['id'] );

					unset( $images2x[ $filename ] );
				}

				/* Delete any unused 2x files */
				foreach( $images2x as $img )
				{
					File::get( 'core_Emoticons', $img )->delete();
				}
			}

			unset( Cache::i()->core_editor_emoji_sets );
			Settings::i()->changeValues( array( 'emoji_cache' => time() ) );

            Session::i()->log( 'acplog__emoticons_added', array( "core_emoticon_group_{$setId}" => TRUE ) );
			
			Output::i()->redirect( Url::internal( 'app=core&module=editor&controller=emoticons&tab=custom' ), 'saved' );
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'emoticons_add', $form, FALSE );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		Dispatcher::i()->checkAcpPermission( 'emoticons_edit' );
		Session::i()->csrfCheck();

		$position = 0;
		$set = NULL;
		
		if ( Request::i()->isAjax() )
		{
			$i = 1;
			if ( isset( Request::i()->setOrder ) )
			{
				foreach ( Request::i()->setOrder as $set )
				{
					$set = preg_replace( '/^core_emoticon_group_/', '', $set );
					
					Db::i()->update( 'core_emoticons', array( 'emo_set_position' => $i ), array( 'emo_set=?', $set ) );
					$i++;
				}
			}
			else
			{			
				$emoticons	= $this->_getEmoticons();
				$setPos		= 1;
				
				foreach ( $emoticons as $group => $emos )
				{
					if( isset( Request::i()->$group ) AND is_array( Request::i()->$group ) )
					{
						foreach( Request::i()->$group as $id )
						{
							Db::i()->update( 'core_emoticons', array( 'emo_position' => $i, 'emo_set_position' => $setPos, 'emo_set' => str_replace( 'core_emoticon_group_', '', $group ) ), array( 'id=?', $id ) );
							$i++;
						}
					}

					$setPos++;
				}
			}

			unset( Cache::i()->core_editor_emoji_sets );// clear the cache
			Settings::i()->changeValues( array( 'emoji_cache' => time() ) );
			
			Session::i()->log( 'acplog__emoticons_edited' );
			
			Output::i()->json( 'OK' );
		}

		// Do we need to unsquash any values?
		// Squashed values are json_encoded by javascript to prevent us exceeding max_post_vars		
		// If 'squashedField' isn't in the request it might indicate the user didn't have JS enabled
		if ( isset( Request::i()->emoticons_squashed ) )
		{
			if ( isset( Request::i()->emoticons_squashed ) )
			{
				$unsquashed = json_decode( Request::i()->emoticons_squashed, TRUE );
				
				foreach( $unsquashed as $key => $value )
				{
					Request::i()->$key = $value;
				}

				unset( Request::i()->emoticons_squashed );
			}
		}

		$emoticons = $this->_getEmoticons( FALSE );

		foreach ( Request::i()->emo as $id => $data )
		{
			if ( isset( $emoticons[ $id ] ) )
			{
				if ( !$data['name'] )
				{
					continue;
				}

				if ( $emoticons[ $id ]['typed'] !== $data['name'] )
				{
					$save = array( 'typed' => preg_replace( "#\s#", "", $data['name'] ) );

					if ( $set !== NULL )
					{
						$save['emo_set'] = str_replace( 'core_emoticon_group_', '', $data['set'] );
					}

					Db::i()->update( 'core_emoticons', $save, array( 'id=?', $id ) );
				}
			}
		}

		Settings::i()->changeValues( array( 'emoji_cache' => time() ) );

        Session::i()->log( 'acplog__emoticons_edited' );
		
		Output::i()->redirect( Url::internal( 'app=core&module=editor&controller=emoticons&tab=custom' ), 'saved' );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'emoticons_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$emoticon = Db::i()->select( '*', 'core_emoticons', array( 'id=?', Request::i()->id ) )->first();
			if ( $emoticon['id'] )
			{
				foreach ( ['image', 'image_2x'] as $field )
				{
					if ( is_string( $emoticon[$field] ) )
					{
						try
						{
							File::get( 'core_Emoticons', $emoticon[$field] )->delete();
						}
						catch ( OutOfRangeException ) {} // if the file doesn't exist anyway, we don't care
					}
				}
			}

			Db::i()->delete( 'core_emoticons', array( 'id=?', (int) Request::i()->id ) );

			/* delete the group name, if there are no other emoticons in this group */
			$emoticons = Db::i()->select( 'COUNT(*) as count', 'core_emoticons', array( 'emo_set =?', $emoticon['emo_set'] ) )->first();

			if ( $emoticons == 0 )
			{
				Lang::deleteCustom( 'core', 'core_emoticon_group_'. $emoticon['emo_set'] );
			}

	        Session::i()->log( 'acplog__emoticon_deleted' );

			Settings::i()->changeValues( array( 'emoji_cache' => time() ) );
			unset( Cache::i()->core_editor_emoji_sets ); // clear the cache
		}
		catch ( UnderflowException $e ) { }

		Output::i()->redirect( Url::internal( 'app=core&module=editor&controller=emoticons&tab=custom' ), 'saved' );
	}
	
	/**
	 * Delete set
	 *
	 * @return	void
	 */
	public function deleteSet() : void
	{
		Dispatcher::i()->checkAcpPermission( 'emoticons_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		$set = preg_replace( '/^core_emoticon_group_/', '', Request::i()->key );
		
		foreach ( Db::i()->select( '*', 'core_emoticons', array( 'emo_set=?', $set ) ) as $emoticon )
		{
			try
			{
				File::get( 'core_Emoticons', $emoticon['image'] )->delete();
				File::get( 'core_Emoticons', $emoticon['image_2x'] )->delete();
			}
			catch ( UnderflowException $e ) { }
		}
		
		Db::i()->delete( 'core_emoticons', array( 'emo_set=?', $set ) );
		Lang::deleteCustom( 'core', 'core_emoticon_group_'. $set );

		Settings::i()->changeValues( array( 'emoji_cache' => time() ) );
		unset( Cache::i()->core_editor_emoji_sets ); // clear the cache
		
		Session::i()->log( 'acplog__emoticon_set_deleted' );

		Output::i()->redirect( Url::internal( 'app=core&module=editor&controller=emoticons&tab=custom' ), 'saved' );
	}

	/**
	 * Edit group title
	 *
	 * @return	void
	 */
	protected function editTitle() : void
	{
		Dispatcher::i()->checkAcpPermission( 'emoticons_edit' );

		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--edit-emoticon-group ipsForm_fullWidth';
		$form->add( new Translatable( 'emoticons_add_newgroup', NULL, FALSE, array( 'app' => 'core', 'key' => Request::i()->key ), NULL, NULL, NULL, 'emoticons_add_newgroup' ) );
		
		if ( $values = $form->values() )
		{
			Lang::saveCustom( 'core', Request::i()->key, $values['emoticons_add_newgroup'] );
			
			Output::i()->redirect( Url::internal( 'app=core&module=editor&controller=emoticons&tab=custom' ), 'saved' );
		}
		
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $form;
			return;
		}

		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'emoticons_edit_groupname', $form, FALSE );
	}

	/**
	 * Get Emoticons
	 *
	 * @param	bool	$group	Group by their group?
	 * @return	array
	 */
	protected function _getEmoticons( bool $group=TRUE ) : array
	{
		$emoticons = array();
		foreach ( Db::i()->select( '*', 'core_emoticons', NULL, 'emo_set_position,emo_position' ) as $row )
		{			
			if ( $group )
			{
				$emoticons[ 'core_emoticon_group_' . $row['emo_set'] ][ $row['id'] ] = $row;
			}
			else
			{
				$emoticons[ $row['id'] ] = $row;
			}
		}
		
		return $emoticons;
	}

	/**
	 * Returns the filename and extension for given emoticon path
	 *
	 * @param	string		$path		Emoticon path
	 * @return	string
	 */
	protected function _getRawFilename( string $path ) : string
	{
		$parts = explode( '/', $path );
		$filenamePart = array_pop( $parts );
		return mb_substr( $filenamePart, 0, mb_strrpos( $filenamePart, '.' ) );
	}
}