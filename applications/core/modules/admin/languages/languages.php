<?php
/**
 * @brief		languages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Jun 2013
 */

namespace IPS\core\modules\admin\languages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Output\Javascript;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\XMLReader;
use OutOfRangeException;
use UnderflowException;
use UnexpectedValueException;
use XMLWriter;
use function constant;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function substr;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_NODE;
use const IPS\IN_DEV;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * languages
 */
class languages extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\Lang';
	
	/**
	 * Title can contain HTML?
	 */
	public bool $_titleHtml = TRUE;
	
	/**
	 * Description can contain HTML?
	 */
	public bool $_descriptionHtml = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'lang_words' );
		parent::execute();
	}
	
	/**
	 * Allow overloading to change how the title is displayed in the tree
	 *
	 * @param	$node    Model    Node
	 * @return string
	 */
	protected static function nodeTitle( Model $node ): string
	{
		return Theme::i()->getTemplate('customization')->langRowTitle( $node );
	}

	/**
	 * Fetch any additional HTML for this row
	 *
	 * @param	object	$node	Node returned from $nodeClass::load()
	 * @return	NULL|string
	 */
	public function _getRowHtml( object $node ): ?string
	{
		$hasBeenCustomized = Db::i()->select( 'COUNT(*)', 'core_sys_lang_words', array( 'lang_id=? AND word_export=1 AND word_custom IS NOT NULL', $node->id ) )->first();
		return Theme::i()->getTemplate('customization' )->langRowAdditional( $node, $hasBeenCustomized );
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'flags.css', 'core', 'global' ) );
		
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'globe',
				'title'	=> 'lang_vle',
				'link'	=> Url::internal( 'app=core&module=languages&controller=languages&do=vle' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('lang_vle') )
			);
			
			Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'plus',
				'title'	=> 'add_word',
				'link'	=> Url::internal( "app=core&module=languages&controller=languages&do=addWord" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'add_word' ) )
			);
			
			/* @note If any more settings are added, then this condition needs to be moved. */
			if ( count( Lang::languages() ) > 1 )
			{
				Output::i()->sidebar['actions'][] = array(
					'icon'	=> 'cogs',
					'title'	=> 'settings',
					'link'	=> Url::internal( 'app=core&module=languages&controller=languages&do=settings' ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
				);
			}
		}
		
		parent::manage();
	}
	
	/**
	 * Add Word
	 *
	 * @return	void
	 */
	protected function addWord() : void
	{
		$form = new Form( 'wordForm', 'save', NULL, array( 'data-role' => 'wordForm' ) );
		Lang::wordForm( $form );
		
		if ( $values = $form->values() )
		{
			/* Save */
			Lang::saveCustom( 'core', $values['word_key'], $values['word_custom'] ?? NULL, FALSE, $values['word_default'] );
			
			Session::i()->log( 'acplog__custom_word_added', array( $values['word_key'] => FALSE ) );
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( 'OK' );
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=languages&controller=languages" ), 'saved' );
			}
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'add_word' );
		Output::i()->output = (string) $form;
	}
	
	/**
	 * Delete Word
	 *
	 * @return	void
	 */
	protected function deleteWord() : void
	{
		Session::i()->csrfCheck();
		
		/* Make sure this is actually a custom phrase */
		try
		{
			$word = Db::i()->select( '*', 'core_sys_lang_words', array( "word_key=? AND lang_id=?", Request::i()->key, Request::i()->langId ) )->first();
			
			if ( !$word['word_is_custom'] ) 
			{
				Output::i()->error( 'node_error', '1C126/9', 403, '' );
			}
		}
		catch( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '1C126/A', 403, '' );
		}
		
		Db::i()->delete( 'core_sys_lang_words', array( "word_key=?", Request::i()->key ) );
		
		Session::i()->log( 'acplog__custom_word_deleted', array( Request::i()->key => FALSE ) );
		
		Output::i()->redirect( Url::internal( "app=core&module=languages&controller=languages&do=translate&id=" . Request::i()->langId ), 'deleted' );
	}
	
	/**
	 * Manual Upload Form
	 *
	 * @return void
	 */
	protected function upload() : void
	{
		/* Build form */
		$form = new Form;
		$form->addMessage('languages_manual_install_warning');
		$form->add( new Upload( 'lang_upload', NULL, TRUE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );
		Lang::localeField( $form );
		
		$activeTabContents = $form;
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['lang_upload'], $tempFile );
			
			/* Work out locale */
			if( isset( $values['lang_short_custom'] ) )
			{
				if ( !isset($values['lang_short']) OR $values['lang_short'] === 'x' )
				{
					$locale = $values['lang_short_custom'];
				}
				else
				{
					$locale = $values['lang_short'];
				}
			}
								
			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ), 'locale' => $locale ) )->csrf() );
		}
		
		/* Display */
		Output::i()->output = $form;
	}
	
	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		/* If we have no ID number, this is the add form, which is handled differently to the edit */
		if ( !Request::i()->id )
		{			
			/* CREATE NEW */
			$max = Db::i()->select( 'MAX(lang_order)', 'core_sys_lang' )->first();

			/* Build form */
			$form = new Form;
			$form->addMessage('languages_create_blurb');
			$lang = new Lang;
			$lang->short = 'en_US';
			$lang->form( $form );
			
			/* Handle submissions */
			if ( $values = $form->values() )
			{
				/* Find the correct locale */
				if ( !isset($values['lang_short']) OR $values['lang_short'] === 'x' )
				{
					$values['lang_short'] = $values['lang_short_custom'];
				}
				unset( $values['lang_short_custom'] );

				/* reset default language if we want this to be default */
				if( isset( $values['lang_default'] ) and $values['lang_default'] )
				{
					Db::i()->update( 'core_sys_lang', array( 'lang_default' => 0 ) );
				}

				/* Add "UTF8" if we can */
				$currentLocale = setlocale( LC_ALL, '0' );

				foreach ( array( "{$values['lang_short']}.UTF-8", "{$values['lang_short']}.UTF8" ) as $l )
				{
					$test = setlocale( LC_ALL, $l );
					if ( $test !== FALSE )
					{
						$values['lang_short'] = $l;
						break;
					}
				}

				foreach( explode( ";", $currentLocale ) as $locale )
				{
					$parts = explode( "=", $locale );
					if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
					{
						setlocale( constant( $parts[0] ), $parts[1] );
					}
				}
				
				/* Insert the actual language */
				$values['lang_order'] = ++$max;
				$insertId = Db::i()->insert( 'core_sys_lang', $values );
				
				/* Copy over language strings */
				$default = Lang::defaultLanguage();
				$prefix = Db::i()->prefix;
				$defaultStmt = Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, NULL AS `word_custom`, `word_default_version`, NULL AS `word_custom_version`, `word_js`, `word_export` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=1" );
				$defaultStmt->execute();
				$customStmt = Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_is_custom` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_is_custom` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=0" );
				$customStmt->execute();

				unset( Store::i()->languages );

				/* Log */
				Session::i()->log( 'acplogs__lang_created', array( $values['lang_title'] => FALSE ) );
				
				/* Redirect */
				Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ), 'saved' );
			}
			
			/* Display */
			Output::i()->output = $form;
		}
		/* If it's an edit, we can just let the node controller handle it */
		else
		{
			parent::form();
		}
	}
	
	/**
	 * Toggle Enabled/Disable
	 *
	 * @return	void
	 */
	protected function enableToggle() : void
	{
		Session::i()->csrfCheck();
		
		/* Load Language */
		try
		{
			$language = Lang::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '3C126/1', 404, '' );
		}
		/* Check we're not locked */
		if( $language->_locked or !$language->canEdit() )
		{
			Output::i()->error( 'node_noperm_enable', '2C126/2', 403, '' );
		}
		
		/* Check if any members are using this */
		if ( !Request::i()->status )
		{
			$count = Db::i()->select( 'count(*)', 'core_members', array( 'language=?', $language->_id ) )->first();
			if ( $count )
			{
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( false, 500 );
				}
				else
				{
					$options = array();
					foreach ( Lang::languages() as $lang )
					{
						if ( $lang->id != $language->_id )
						{
							$options[ $lang->id ] = $lang->title;
						}
					}
					
					$form = new Form;
					$form->add( new Select( 'lang_change_to', Lang::defaultLanguage(), TRUE, array( 'options' => $options ) ) );
					
					if ( $values = $form->values() )
					{
						Db::i()->update( 'core_members', array( 'language' => $values['lang_change_to'] ), array( 'language=?', $language->_id ) );
					}
					else
					{
						Output::i()->output = $form;
						return;
					}
				}
			}
		}
		
		/* Do it */
		Db::i()->update( 'core_sys_lang', array( 'lang_enabled' => (bool) Request::i()->status ), array( 'lang_id=?', $language->_id ) );
		unset( Store::i()->languages );

		/* Update the essential cookie name list */
		unset( Store::i()->essentialCookieNames );

		/* Log */
		if ( Request::i()->status )
		{
			Session::i()->log( 'acplog__node_enabled', array( 'menu__core_languages_languages' => TRUE, $language->title => FALSE ) );
		}
		else
		{
			Session::i()->log( 'acplog__node_disabled', array( 'menu__core_languages_languages' => TRUE, $language->title => FALSE ) );
		}
		
		/* Redirect */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( (bool) Request::i()->status );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ), 'saved' );
		}
	}
	
	/**
	 * Visual Language Editor
	 *
	 * @return	void
	 */
	protected function vle() : void
	{
		if( IN_DEV )
		{
			Member::loggedIn()->language()->words['lang_vle_editor_warning']	= Member::loggedIn()->language()->addToStack( 'dev_lang_vle_editor_warn', FALSE );
		}

		$form = new Form();
		$form->add( new YesNo( 'lang_vle_editor', ( isset( Request::i()->cookie['vle_editor'] ) and Request::i()->cookie['vle_editor'] ) and !IN_DEV, FALSE, array( 'disabled' => IN_DEV ) ) );
		$form->add( new YesNo( 'lang_vle_keys', isset( Request::i()->cookie['vle_keys'] ) and Request::i()->cookie['vle_keys'] ) );
		
		if ( $values = $form->values() )
		{
			foreach ( array( 'vle_editor', 'vle_keys' ) as $k )
			{
				if ( $values[ 'lang_' . $k ] )
				{
					Request::i()->setCookie( $k, 1 );
				}
				elseif ( isset( Request::i()->cookie[ $k ] ) )
				{
					Request::i()->setCookie( $k, 0 );
				}
			}
			
			Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ) );
		}
		
		Output::i()->output = $form;
	}
	
	/**
	 * Language Setting
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$form = new Form;
		$form->add( new YesNo( 'lang_auto_detect', Settings::i()->lang_auto_detect, TRUE ) );
		
		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__language_settings_edited' );
			Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ) );
		}
		
		Output::i()->output = $form;
	}
	
	/**
	 * Translate
	 *
	 * @return	void
	 */
	protected function translate() : void
	{
		if ( Lang::vleActive() )
		{
			Output::i()->error( 'no_translate_with_vle', '1C126/8', 403, '' );
		}

		try
		{
			$lang = Lang::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C126/3', 404, '' );
		}
		
		$where = array(
			array( 'lang_id=? AND (word_export=1 OR word_is_custom=1)', Request::i()->id ),
		);
		
		$table = new \IPS\Helpers\Table\Db( 'core_sys_lang_words', Url::internal( 'app=core&module=languages&controller=languages&do=translate&id=' . Request::i()->id ), $where );
		$table->langPrefix = 'lang_';
		$table->classes = array( 'cTranslateTable' );
		$table->rowClasses = array( 'word_default' => array( 'ipsTable_wrap' ) );

		$table->include = array( 'word_app', 'word_theme', 'word_key', 'word_default', 'word_custom' );

		$table->parsers = array(
			'word_app' => function( $val, $row )
			{
				try
				{
					return Application::load( $row['word_app'] )->_title;
				}
				catch ( OutOfRangeException | InvalidArgumentException | UnexpectedValueException $e )
				{
					return Theme::i()->getTemplate( 'global' )->shortMessage( Member::loggedIn()->language()->addToStack('translate_na'), array( 'ipsBadge', 'ipsBadge--neutral' ) );
				}
			},
			'word_theme' => function( $val, $row )
			{
				try
				{
					return Theme::load( $row['word_theme'] )->_title;
				}
				catch ( OutOfRangeException $e )
				{
					return Theme::i()->getTemplate( 'global' )->shortMessage( Member::loggedIn()->language()->addToStack('translate_na'), array( 'ipsBadge', 'ipsBadge--neutral' ) );
				}
			},
			'word_default'	=> function( $val, $row )
			{
				return htmlspecialchars( $val, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8' );
			},
			'word_custom'	=> function( $val, $row )
			{
				return Theme::i()->getTemplate( 'customization' )->langString( $val, $row['word_key'], $row['lang_id'], $row['word_js'] );
			},
		);
		
		$table->sortBy = $table->sortBy ?: 'word_key';
		$table->sortDirection = $table->sortDirection ?: 'asc';

		$table->quickSearch = array( array( 'word_default', 'word_key' ), 'word_default' );
		$table->advancedSearch = array(
			'word_key'		=> SEARCH_CONTAINS_TEXT,
			'word_default'	=> SEARCH_CONTAINS_TEXT,
			'word_custom'	=> SEARCH_CONTAINS_TEXT,
			'word_app'		=> array( SEARCH_NODE, array( 'class' => 'IPS\Application', 'subnodes' => FALSE ) ),
		);
		
		$table->filters = array(
			'lang_filter_translated'	=> 'word_custom IS NOT NULL',
			'lang_filter_untranslated'	=> 'word_custom IS NULL',
			'lang_filter_out_of_date'	=> 'word_custom IS NOT NULL AND word_custom_version<word_default_version',
			'lang_filter_admin_custom'	=> 'word_is_custom!=0'
		);
		
		$table->widths = array( 'word_key' => 15, 'word_default' => 35, 'word_custom' => 50 );
		$table->rowButtons = function( $row ) {
			if ( $row['word_is_custom'] )
			{
				return array(
					'delete' => array(
						'icon'		=> 'times',
						'title'		=> 'delete',
						'link'		=> Url::internal( "app=core&module=languages&controller=languages&do=deleteWord&key={$row['word_key']}&langId=" . Request::i()->id )->csrf(),
						'data'		=> array( 'confirm' => '', 'confirmMessage' => Member::loggedIn()->language()->addToStack('delete_word_all_languages') )
					)
				);
			}
			else
			{
				return array();
			}
		};
		
		Member::loggedIn()->language()->words['lang_word_custom'] = $lang->title;
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customization/languages.css', 'core', 'admin' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_core.js' ) );
		Output::i()->title = $lang->title;
		Output::i()->output = (string) $table;
	}
	
	/**
	 * Translate Word
	 *
	 * @return	void
	 */
	protected function translateWord() : void
	{
		try
		{
			$lang = Lang::load( Request::i()->lang );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C126/4', 404, '' );
		}
		
		$word = Db::i()->select( '*', 'core_sys_lang_words', array( 'lang_id=? AND word_key=? AND word_js=?', Request::i()->lang, Request::i()->key, (int) Request::i()->js ) )->first();
		
		$form = new Form;
		$form->addDummy( 'lang_word_default', htmlspecialchars( $word['word_default'],ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) );
		$form->add( new Text( 'lang_word_custom', $word['word_custom'] ) );
		
		if ( $values = $form->values() )
		{
			$version = 0;
			try
			{
				if ( $word['word_app'] )
				{
					$version = Application::load( $word['word_app'] )->long_version;
				}
				elseif ( $word['word_theme'] )
				{
					$version = Theme::load( $word['word_theme'] )->long_version;
				}
			}
			catch ( OutOfRangeException $e ) { }
			
			Db::i()->update( 'core_sys_lang_words', array( 'word_custom' => ( $values['lang_word_custom'] ? urldecode( $values['lang_word_custom'] ) : NULL ), 'word_custom_version' => ( $values['lang_word_custom'] ? $version : NULL ) ), array( 'word_id=?', $word['word_id'] ) );
			Session::i()->log( 'acplogs__lang_translate', array( $word['word_key'] => FALSE, $lang->title => FALSE ) );
			
			if ( $word['word_js'] )
			{
				Javascript::clearLanguage( $lang );
			}

			if ( $word['word_key'] === '_list_format_' )
			{
				unset( Store::i()->listFormats );
			}
			
			if ( substr( $word['word_key'], 0, 10 ) === 'num_short_' )
			{
				unset( Store::i()->shortFormats );
			}

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array() );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages&do=translate&id=' . $word['lang_id'] ) );
			}
		}
		
		Member::loggedIn()->language()->words['lang_word_custom'] = $lang->title;
		Output::i()->output = $form;
	}
	
	/**
	 * Copy
	 *
	 * @return	void
	 */
	protected function copy() : void
	{
		Session::i()->csrfCheck();
		
		Output::i()->output = new MultipleRedirect(
			Url::internal( "app=core&module=languages&controller=languages&do=copy&id=" . intval( Request::i()->id ) )->csrf(),
			function( $data )
			{
				if ( !is_array( $data ) )
				{
					$lang = Db::i()->select( '*', 'core_sys_lang',  array( 'lang_id=?', Request::i()->id ) )->first();
					unset( $lang['lang_id'] );

					$lang['lang_title'] = $lang['lang_title'] . ' ' . Member::loggedIn()->language()->get('copy_noun');
					$lang['lang_default'] = FALSE;

					$insertId = Db::i()->insert( 'core_sys_lang', $lang );
					
					Session::i()->log( 'acplog__node_copied', array( 'menu__core_languages_languages' => TRUE, $lang['lang_title'] => FALSE ) );
					
					$words = Db::i()->select( 'count(*)', 'core_sys_lang_words', array( 'lang_id=?', Request::i()->id ) )->first();
					
					return array( array( 'id' => $insertId, 'done' => 0, 'total' => $words ), Member::loggedIn()->language()->addToStack('copying'), 1 );
				}
				else
				{
					$words = Db::i()->select(  '*', 'core_sys_lang_words', array( 'lang_id=?', Request::i()->id ), 'word_id', array( $data['done'], 100 ) );
					if ( !count( $words  ) )
					{
						return NULL;
					}
					else
					{
						foreach ( $words as $row )
						{
							unset( $row['word_id'] );
							$row['lang_id'] = $data['id'];
							Db::i()->replace( 'core_sys_lang_words', $row );
						}
					}
					
					
					$data['done'] += 100;
					return array( $data, Member::loggedIn()->language()->addToStack('copying'), ( 100 / $data['total'] * $data['done'] ) );
				}
			},
			function()
			{
				unset( Store::i()->languages );
				unset( Store::i()->listFormats );

				/* Update the essential cookie name list */
				unset( Store::i()->essentialCookieNames );

				Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ) );
			}
		);
	}
	
	/**
	 * Download
	 *
	 * @return	void
	 */
	protected function download() : void
	{
		/* Load language */
		try
		{
			$lang = Lang::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C126/5', 404, '' );
		}

		$form = new Form( 'form', 'langauge_export_button' );
		// This appears inside a dialog. The following instructs the dialog to handle the download via JS rather than using a native form download. This allows the dialog to close when done.
		$filename = $lang->title . " {$lang->version}.xml";
		$form->attributes['data-form-is-download'] = '';

		$form->add( new Text( 'language_export_author_name', $lang->author_name, false ) );
		$form->add( new FormUrl( 'language_export_author_url', $lang->author_url, false ) );
		$form->add( new FormUrl( 'language_export_update_check', $lang->update_url, false ) );
		$form->add( new Text( 'language_export_version', $lang->version, true, array( 'placeholder' => '1.0.0' ) ) );
		$form->add( new Number( 'language_export_long_version', $lang->version_long ?: 10000, true ) );

		if ( $values = $form->values() )
		{
			$lang->author_name		= $values['language_export_author_name'];
			$lang->author_url		= (string) $values['language_export_author_url'];
			$lang->update_url		= (string) $values['language_export_update_check'];
			$lang->version			= $values['language_export_version'];
			$lang->version_long		= (int) $values['language_export_long_version'];
			$lang->save();

			$count = 0;
			$where = [
				[ 'lang_id=?', $lang->id ],
				[ '(word_export=? or word_is_custom=?)', 1, 1 ],
				[ 'word_custom is not null' ]
			];

			try
			{
				$count = Db::i()->select( 'COUNT(word_id)', 'core_sys_lang_words', $where, 'word_id', NULL, 'word_id' )->first();
			}
			catch ( UnderflowException $e ) {}

			if ( $count < 1 )
			{
				Output::i()->error( 'core_lang_download_empty', '1C126/7', 404, '' );
			}

			/* Init */
			$xml = new XMLWriter;
			$xml->openMemory();
			$xml->setIndent( TRUE );
			$xml->startDocument( '1.0', 'UTF-8' );

			/* Root tag */
			$xml->startElement( 'language' );
			$xml->startAttribute( 'name' );
			$xml->text( $lang->title );
			$xml->endAttribute();
			$xml->startAttribute( 'rtl' );
			$xml->text( $lang->isrtl );
			$xml->endAttribute();

			$xml->startAttribute( 'author_name' );
			$xml->text( $lang->author_name );
			$xml->endAttribute();
			$xml->startAttribute( 'author_url' );
			$xml->text( $lang->author_url );
			$xml->endAttribute();

			$xml->startAttribute( 'version' );
			$xml->text( $lang->version );
			$xml->endAttribute();
			$xml->startAttribute( 'long_version' );
			$xml->text( $lang->version_long );
			$xml->endAttribute();

			$xml->startAttribute( 'update_check' );
			$xml->text( $lang->update_url );
			$xml->endAttribute();

			/* Loop applications */
			foreach ( Application::applications() as $app )
			{
				/* Initiate the <app> tag */
				$xml->startElement( 'app' );

				/* Set key */
				$xml->startAttribute( 'key' );
				$xml->text( $app->directory );
				$xml->endAttribute();

				/* Set version */
				$xml->startAttribute( 'version' );
				$xml->text( $app->long_version );
				$xml->endAttribute();

				/* Add words */
				$appWhere = $where;
				$appWhere[] = [ 'word_app=?', $app->directory ];
				foreach ( Db::i()->select( '*', 'core_sys_lang_words', $appWhere, 'word_id' ) as $row )
				{
					/* Start */
					$xml->startElement( 'word' );

					/* Add key */
					$xml->startAttribute( 'key' );
					$xml->text( $row['word_key'] );
					$xml->endAttribute();

					/* Is this a javascript string? */
					$xml->startAttribute( 'js' );
					$xml->text( $row['word_js'] );
					$xml->endAttribute();

					/* Write value */
					if ( preg_match( '/<|>|&/', $row['word_custom'] ) )
					{
						$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row['word_custom'] ) );
					}
					else
					{
						$xml->text( $row['word_custom'] );
					}

					/* End */
					$xml->endElement();
				}

				/* </app> */
				$xml->endElement();
			}

			/* Finish */
			$xml->endDocument();
			Output::i()->sendOutput(
				$xml->outputMemory(),
				200,
				'application/xml',
				array(
					'Content-Disposition' => Output::getContentDisposition( 'attachment', $filename ) ),
				FALSE,
				FALSE,
				FALSE
			);
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( Member::loggedIn()->language()->addToStack('language_export_title', FALSE, array( 'sprintf' => array( $lang->title ) ) ), $form, FALSE );
	}
	
	/**
	 * Upload new version
	 *
	 * @return	void
	 */
	public function uploadNewVersion() : void
	{
		Dispatcher::i()->checkAcpPermission( 'lang_words' );

		$language = Lang::load( Request::i()->id );
		
		$form = new Form;
		$form->add( new Upload( 'lang_upload', NULL, TRUE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['lang_upload'], $tempFile );
								
			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ), 'into' => Request::i()->id ) )->csrf() );
		}
		
		Output::i()->output = $form;
	}
	
	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	public function import() : void
	{
		Session::i()->csrfCheck();
		
		if ( !file_exists( Request::i()->file ) or md5_file( Request::i()->file ) !== Request::i()->key )
		{
			Output::i()->error( 'generic_error', '3C126/6', 500, '' );
		}
		
		$url = Url::internal( 'app=core&module=languages&controller=languages&do=import' )->setQueryString( array( 'file' => Request::i()->file, 'key' => Request::i()->key, 'locale' => Request::i()->locale ) )->csrf();
		if ( isset( Request::i()->into ) )
		{
			$url = $url->setQueryString( 'into', Request::i()->into );
		}
		
		Output::i()->output = new MultipleRedirect(
			$url,
			function( $data )
			{
				/* Open XML file */
				$xml = XMLReader::safeOpen( Request::i()->file );
				$xml->read();
				
				/* If this is the first batch, create the language record */
				if ( !is_array( $data ) )
				{
					/* Create the record */
					if ( isset( Request::i()->into ) )
					{
						$insertId = Request::i()->into;

						Db::i()->update( 'core_sys_lang', array(
							'lang_title'		=> $xml->getAttribute('name'),
							'lang_isrtl'		=> $xml->getAttribute('rtl'),
							'lang_version'		=> $xml->getAttribute('version'),
							'lang_version_long'	=> $xml->getAttribute('long_version'),
							'lang_author_name'	=> $xml->getAttribute('author_name'),
							'lang_author_url'	=> $xml->getAttribute('author_url'),
							'lang_update_url'	=> $xml->getAttribute('update_check')
						),
						array( 'lang_id=?', $insertId) );
					}
					else
					{
						/* Add "UTF8" if we can */
						$currentLocale = setlocale( LC_ALL, '0' );

						foreach ( array( Request::i()->locale . ".UTF-8", Request::i()->locale . ".UTF8" ) as $l )
						{
							$test = setlocale( LC_ALL, $l );
							if ( $test !== FALSE )
							{
								Request::i()->locale = $l;
								break;
							}
						}

						foreach( explode( ";", $currentLocale ) as $locale )
						{
							$parts = explode( "=", $locale );
							if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
							{
								setlocale( constant( $parts[0] ), $parts[1] );
							}
						}

						/* Insert the language pack record */
						$max = Db::i()->select( 'MAX(lang_order)', 'core_sys_lang' )->first();
						$insertId = Db::i()->insert( 'core_sys_lang', array(
							'lang_short'		=> Request::i()->locale,
							'lang_title'		=> $xml->getAttribute('name'),
							'lang_isrtl'		=> $xml->getAttribute('rtl'),
							'lang_order'		=> $max + 1,
							'lang_version'		=> $xml->getAttribute('version'),
							'lang_version_long'	=> $xml->getAttribute('long_version'),
							'lang_author_name'	=> $xml->getAttribute('author_name'),
							'lang_author_url'	=> $xml->getAttribute('author_url'),
							'lang_update_url'	=> $xml->getAttribute('update_check'),
						) );
					
						/* Copy over default language strings */
						$default = Lang::defaultLanguage();
						$prefix = Db::i()->prefix;
						$defaultStmt = Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, NULL AS `word_custom`, `word_default_version`, NULL AS `word_custom_version`, `word_js`, `word_export` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=1" );
						$defaultStmt->execute();
						$customStmt = Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=0" );
						$customStmt->execute();
					}
					
					/* Log */
					Session::i()->log( 'acplogs__lang_created', array( $xml->getAttribute('name') => FALSE ) );
					
					/* Start importing */
					$data = array( 'apps' => array(), 'id' => $insertId );
					return array( $data, Member::loggedIn()->language()->get('processing') );
				}

				/* Only import language strings from applications we have installed */
				$applications = array();
				foreach( Application::applications() as $app )
				{
					$applications[$app->directory] = $app->long_version;
				}
				
				/* Move to correct app */
				$appKey = NULL;
				$version = NULL;
				$xml->read();
				while ( $xml->read() )
				{
					$appKey = $xml->getAttribute('key');
					if ( !array_key_exists( $appKey, $data['apps'] ) AND array_key_exists( $appKey, $applications ) )
					{
						/* Get version */
						$version = $xml->getAttribute('version') ?: $applications[$appKey];
						
						/* Import */
						$xml->read();
						while ( $xml->read() and $xml->name == 'word' )
						{
							Db::i()->insert( 'core_sys_lang_words', array(
								'word_app'				=> $appKey,
								'word_key'				=> $xml->getAttribute('key'),
								'lang_id'				=> $data['id'],
								'word_custom'			=> $xml->readString(),
								'word_custom_version'	=> $version,
								'word_js'				=> (int) $xml->getAttribute('js'),
								'word_export'			=> 1,
							), TRUE );
							$xml->next();
						}
						
						/* Done */
						$data['apps'][ $appKey ] = TRUE;
						return array( $data, Member::loggedIn()->language()->get('processing') );
					}
					else
					{
						$xml->next();
					}
				}
							
				/* All done */
				return NULL;
			},
			function()
			{
				/* Clear language caches, including update counter */
				unset( Store::i()->languages, Store::i()->listFormats, Store::i()->updatecount_languages );

				/* Update the essential cookie name list */
				unset( Store::i()->essentialCookieNames );

				@unlink( Request::i()->file );
				
				Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ) );
			}
		);
	}
	
	/**
	 * Developer Import
	 *
	 * @return	void
	 */
	protected function devimport() : void
	{
		Session::i()->csrfCheck();
		
		Output::i()->output = new MultipleRedirect(
			Url::internal( "app=core&module=languages&controller=languages&do=devimport&id=" . intval( Request::i()->id ) )->csrf(),
			function ( $data )
			{
				if ( !is_array( $data ) )
				{
					Db::i()->delete( 'core_sys_lang_words', array( 'lang_id=? AND word_export=1', Request::i()->id ) );
					return array( array(), Member::loggedIn()->language()->addToStack('lang_dev_importing'), 1 );
				}
								
				$done = FALSE;
				foreach ( Application::applications() as $appKey => $app )
				{
					if ( !array_key_exists( $appKey, $data ) )
					{
						$words = array();
						$lang = Lang::readLangFiles( $app->directory );
						foreach ( $lang as $k => $v )
						{
							Db::i()->replace( 'core_sys_lang_words', array(
								'lang_id'				=> Request::i()->id,
								'word_app'				=> $app->directory,
								'word_key'				=> $k,
								'word_default'			=> $v,
								'word_custom'			=> NULL,
								'word_default_version'	=> $app->long_version,
								'word_custom_version'	=> NULL,
								'word_js'				=> 0,
								'word_export'			=> 1,
							) );
						}
												
						$data[ $appKey ] = 0;
						$done = TRUE;
						break;
					}
					elseif ( $data[ $appKey ] === 0 )
					{
						$words = array();
						$lang = Lang::readLangFiles( $app->directory, true );
						foreach ( $lang as $k => $v )
						{
							Db::i()->replace( 'core_sys_lang_words', array(
								'lang_id'				=> Request::i()->id,
								'word_app'				=> $app->directory,
								'word_key'				=> $k,
								'word_default'			=> $v,
								'word_custom'			=> NULL,
								'word_default_version'	=> $app->long_version,
								'word_custom_version'	=> NULL,
								'word_js'				=> 1,
								'word_export'			=> 1,
							) );
						}

						$data[ $appKey ] = 1;
						$done = TRUE;
						break;
					}
				}
				
				if ( $done === FALSE )
				{
					return NULL;
				}
				
				return array( $data, Member::loggedIn()->language()->addToStack('lang_dev_importing'), ( 100 / ( count( Application::applications() ) * 2 ) * count( $data ) ) );
			},
			function ()
			{
				unset( Store::i()->languages );
				Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ), 'saved' );
			}
		);
	}
	
	/**
	 * Set Members
	 *
	 * @return	void
	 */
	public function setMembers() : void
	{
		$form = new Form;
		$form->hiddenValues['id'] = Request::i()->id;
		$form->add( new CheckboxSet( 'member_reset_where', '*', TRUE, array( 'options' => Group::groups( TRUE, FALSE ), 'multiple' => TRUE, 'parse' => 'normal', 'unlimited' => '*', 'unlimitedLang' => 'all', 'impliedUnlimited' => TRUE ) ) );
		
		if ( $values = $form->values() )
		{
			if ( $values['member_reset_where'] === '*' )
			{
				$where = NULL;
			}
			else
			{
				$where = Db::i()->in( 'member_group_id', $values['member_reset_where'] );
			}
			
			if ( $where )
			{
				Db::i()->update( 'core_members', array( 'language' => Request::i()->id ), $where );
			}
			else
			{
				Member::updateAllMembers( array( 'language' => Request::i()->id ) );
			}
			
			Session::i()->log( 'acplogs__members_language_reset', array( Lang::load( Request::i()->id ?: Lang::defaultLanguage()  )->title  => FALSE ) );
			Output::i()->redirect( Url::internal( 'app=core&module=languages&controller=languages' ), 'reset' );
		}

		Output::i()->output = $form;
	}
}