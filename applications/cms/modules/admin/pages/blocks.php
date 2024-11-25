<?php
/**
 * @brief		Block Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		19 Feb 2013
 */

namespace IPS\cms\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cms\Blocks\Block;
use IPS\cms\Databases;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * blocks
 */
class blocks extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\cms\Blocks\Container';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'block_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 * 
	 * @return	void
	 */
	public function manage() : void
	{
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__cms_pages_blocks');

		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;
		
		if ( $nodeClass::canAddRoot() )
		{
			Output::i()->sidebar['actions']['add_meow'] = array(
				'primary'	=> true,
				'icon'	=> 'plus',
				'title'	=> 'content_block_cat_add',
				'link'	=> $this->url->setQueryString( 'do', 'form' ),
				'data'	=> ( $nodeClass::$modalForms ? array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('content_block_cat_add') ) : array() )
			);
		}
	
		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'pages', 'block_add' ) )
		{
			Output::i()->sidebar['actions']['add_block'] = array(
				'primary'	=> true,
				'icon'	=> 'puzzle-piece',
				'title'	=> 'content_block_block_add',
				'link'	=> $this->url->setQueryString( array( 'do' => 'addBlockType', 'subnode' => 1 ) ),
				'data'	=> ( $nodeClass::$modalForms ? array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('content_block_block_add') ) : array() )
			);
		}
		parent::manage();
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		if ( isset( Request::i()->id ) )
		{
			Session::i()->csrfCheck();
			Block::deleteCompiled( Request::i()->id );
		}
		
		parent::delete();
	}
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		return [];
	}

	/**
	 * Fetch any additional HTML for this row
	 *
	 * @param	object	$node	Node returned from $nodeClass::load()
	 * @return	NULL|string
	 */
	public function _getRowHtml( object $node ): ?string
	{
		return Theme::i()->getTemplate( 'blocks', 'cms', 'admin' )->rowHtml( $node );
	}
	
	/**
	 * Add block, pre form
	 * 
	 * @return void
	 */
	public function addBlockType() : void
	{
		Dispatcher::i()->checkAcpPermission( 'block_add' );
			
		$form = new Form( 'block_add_first_step', 'next');
		
		$form->add( new Radio( 'content_block_add_type', 'plugin', FALSE, array(
				'options' => array(
						'plugin'  => 'content_block_add_type_plugin',
						'editor'	=> 'content_block_add_custom_type_wysiwyg',
						'html'      => 'content_block_add_custom_type_html',
				),
				'toggles' => array(
						'plugin' => array( 'content_block_add_type_plugin' )
				)
			)
		) );

		$plugins = array();
		foreach ( Db::i()->select( "*", 'core_widgets', array( 'embeddable=1') ) as $widget )
		{
			/* Skip the RecordFeed */
			if( $widget['app'] == 'cms' and $widget['key'] == 'RecordFeed' )
			{
				continue;
			}

			try
			{
					$app = Application::load( $widget['app'] );
					if ( $app->enabled )
					{
						$plugins[ $app->_title ][ 'app__' . $widget['app'] . '__' . $widget['key'] ] = Member::loggedIn()->language()->addToStack( 'block_' . $widget['key'] );
					}
			}
			catch ( UnexpectedValueException | OutOfRangeException $e ) { }
		}

		$disabled = [];
		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'databases', 'databases_use' ) )
		{
			foreach ( Databases::databases() as $db )
			{
				if ( $db->page_id )
				{
					$plugins[Member::loggedIn()->language()->addToStack( 'cms_db_feed_title' )]['db_feed_' . $db->id] = Member::loggedIn()->language()->addToStack( 'cms_db_feed_block_with_name', FALSE, array('sprintf' => array($db->_title)) );
				}
				else
				{
					$disabled[] = 'db_feed_' . $db->id;
					$plugins[Member::loggedIn()->language()->addToStack( 'cms_db_feed_title' )]['db_feed_' . $db->id] = Member::loggedIn()->language()->addToStack( 'cms_db_feed_block_with_name_disabled', FALSE, array('sprintf' => array($db->_title)) );
				}
			}
		}

		$form->add( new Select( 'content_block_add_type_plugin', null, false, array( 'options' => $plugins, 'disabled' => $disabled ), NULL, NULL, NULL, 'content_block_add_type_plugin' ) );
		
		if ( $values = $form->values() )
		{
			if ( $values['content_block_add_type'] == 'plugin' )
			{
				if ( mb_substr( $values['content_block_add_type_plugin'], 0, 8 ) === 'db_feed_' )
				{
					Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=blocks&do=form&subnode=1&block_type=plugin&block_plugin=' . $values['content_block_add_type_plugin'] . '&block_plugin_app=cms&parent=' . Request::i()->parent ) );
				}
				else
				{
					list( $type, $value, $key ) = explode( '__', $values['content_block_add_type_plugin'] );
					Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=blocks&do=form&subnode=1&block_type=plugin&block_plugin=' . $key . "&block_plugin_{$type}={$value}" . '&parent=' . Request::i()->parent ) );
				}
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=blocks&do=form&subnode=1&block_type=custom&block_editor=' . $values['content_block_add_type'] . '&parent=' . Request::i()->parent ) );
			}
		}
		
		/* Display */
		Output::i()->output .= Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( Member::loggedIn()->language()->addToStack('content_block_block_add'), $form, FALSE );
		Output::i()->title   = Member::loggedIn()->language()->addToStack('content_block_block_add');
	}

    /**
     * View external embed options
     *
     * @return	void
     */
    public function embedOptions() : void
    {
        $block = Block::load( Request::i()->id );
        $embedKey = md5( $block->key . time() );
        /* Output */
        Output::i()->title	 = Member::loggedIn()->language()->addToStack('block_embed_title');
        Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( '', Theme::i()->getTemplate( 'blocks', 'cms', 'admin' )->embedCode( $block, $embedKey ) );
	}
    
    /**
	 * Load tags
	 *
	 * @return	void
	 */
	public function loadTags() : void
	{
		$tags = array();
		$tagLinks = array();
		
		/* If we can manage words, then the header needs to always show */
		if (  Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			$tags['cms_tag_lang'] = array();
			$tagLinks['cms_tag_lang']	= array(
				'icon'		=> 'plus',
				'title'		=> Member::loggedIn()->language()->addToStack('add_word'),
				'link'		=> Url::internal( "app=core&module=languages&controller=languages&do=addWord" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'add_word' ), 'ipsDialog-remoteSubmit' => TRUE )
			);
		}
		foreach( Db::i()->select( '*', 'core_sys_lang_words', array( "word_is_custom=? AND lang_id=?", 1, Member::loggedIn()->language()->_id ) ) AS $lang )
		{
			$tags['cms_tag_lang']['{lang="' . $lang['word_key'] . '"}'] = $lang['word_custom'] ?: $lang['word_default'];
		}
		
		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Theme::i()->getTemplate( 'forms', 'core', 'global' )->editorTags( $tags, $tagLinks ) ) );
	}

	/**
	 * Store the custom code to use for a block preview
	 *
	 * @return	void
	 */
	protected function storeTemporaryBlock() : void
	{
		Session::i()->csrfCheck();

		$key = md5( mt_rand() );

		while( isset( Store::i()->$key ) )
		{
			$key = md5( mt_rand() );
		}

		$data = array();

		foreach( Request::i() as $k => $v )
		{
			$data[ $k ] = $v;
		}

		Store::i()->$key = $data;

		Output::i()->redirect( Url::internal( "app=cms&module=pages&controller=builder&do=previewBlock&_key=" . $key, 'front' ) );
	}
}