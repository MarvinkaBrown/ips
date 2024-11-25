<?php

/**
 * @brief		topic
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		03 Mar 2017
 */

namespace IPS\cms\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Categories;
use IPS\cms\Databases;
use IPS\cms\Fields;
use IPS\cms\Pages\Page;
use IPS\cms\Records;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\forums\Topic as TopicClass;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * topic
 */
class topic extends Controller
{
	/**
	 * @var TopicClass|null
	 */
	protected ?TopicClass $topic = null;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->modPermission( 'can_copy_topic_database' ) )
		{
			Output::i()->error( 'node_error', '2T353/1', 403, '' );
		}
		
		try
		{
			$this->topic = TopicClass::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2T353/3', 404, '' );
		}
		
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$wizard = new Wizard( array(
			'database'	=> array( $this, '_database' ),
			'category'	=> array( $this, '_category' ),
		), Url::internal( "app=cms&module=database&controller=topic&id=" . Request::i()->id, 'front', 'topic_copy', $this->topic->title_seo ), FALSE );

		/* Set Breadcrumb */
		foreach ( $this->topic->container()->parents() as $parent )
		{
			Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
		}

		Output::i()->breadcrumb[] = array( $this->topic->container()->url(), $this->topic->container()->_title );

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'copy_topic_to_database' );
		Output::i()->output = (string) $wizard;
	}
	
	/**
	 * Database
	 *
	 * @param	mixed	$data	Data
	 * @return array|string
	 */
	public function _database( mixed $data ) : array|string
	{
		$form = new Form( 'database', 'copy_select_database', Url::internal( "app=cms&module=database&controller=topic&id=" . Request::i()->id, 'front', 'topic_copy', $this->topic->title_seo ) );
		$form->class = 'ipsForm--vertical ipsForm--copy-database-topic';
		$form->hiddenValues['topic_id'] = Request::i()->id;
		$form->add( new Node( 'database', NULL, TRUE, array( 'class' => '\IPS\cms\Databases', 'permissionCheck' => function( $row )
		{
			/* If this database does not have a title or content field, we cannot copy. */
			if ( !$row->field_title OR !$row->field_content )
			{
				return FALSE;
			}
			
			/* If this database is not on a page, then we cannot copy. */
			if ( !$row->page_id )
			{
				return FALSE;
			}
			
			return $row->can( 'add' );
		} ) ) );
		if ( $values = $form->values() )
		{
			return array(
				'topic_id'		=> $values['topic_id'],
				'database_id'	=> $values['database']->_id
			);
		}
		
		return $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Category
	 *
	 * @param	mixed	$data	Data
	 * @return	string
	 */
	public function _category( mixed $data ) : string
	{
		/* Do we need to even bother? */
		$database = Databases::load( $data['database_id'] );
		
		if ( !$database->use_categories )
		{
			Output::i()->redirect( Url::internal( "app=cms&module=database&controller=topic&do=form&id={$data['topic_id']}", 'front', 'topic_copy', $this->topic->title_seo )->setQueryString( array( 'database_id' => $data['database_id'], 'category_id' => $database->default_category ) ) );
		}
		
		$catClass = 'IPS\cms\Categories' . $data['database_id'];
		
		$form = new Form( 'category', 'copy_select_category', Url::internal( "app=cms&module=database&controller=topic&id=" . Request::i()->id, 'front', 'topic_copy', $this->topic->title_seo ) );
		$form->class = 'ipsForm--vertical ipsForm--copy-database-topic-category';
		$form->hiddenValues['topic_id'] = $data['topic_id'];
		$form->hiddenValues['database_id'] = $data['database_id'];
		$form->add( new Node( 'select_category', NULL, TRUE, array( 'class' => $catClass, 'permissionCheck' => 'add' ) ) );
		
		if ( $values = $form->values() )
		{
			Output::i()->redirect( Url::internal( "app=cms&module=database&controller=topic&do=form&id={$values['topic_id']}", 'front', 'topic_copy', $this->topic->title_seo )->setQueryString( array( 'database_id' => $values['database_id'], 'category_id' => $values['select_category']->_id ) ) );
		}
		
		return $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Form
	 *
	 * @return	void
	 */
	protected function form() : void
	{
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_records.js', 'cms' ) );
		
		try
		{
			$database	= Databases::load( Request::i()->database_id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2T353/2', 404, '' );
		}
		
		try
		{
			$page = Page::load( $database->page_id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2T353/4', 404, '' );
		}

		/* @var Categories $catClass
		 * @var Records $recordClass
		 * @var Fields $fieldClass */
		$recordClass	= 'IPS\cms\Records' . $database->_id;
		$fieldClass		= 'IPS\cms\Fields' . $database->_id;
		$catClass		= 'IPS\cms\Categories' . $database->_id;
		$category		= $catClass::load( Request::i()->category_id );
		
		$titleField		= "field_{$database->field_title}";
		$contentField	= "field_{$database->field_content}";
		
		$fakeRecord = new $recordClass;
		$fakeRecord->$titleField	= $this->topic->mapped('title');
		$fakeRecord->$contentField	= $this->topic->content();
		$fakeRecord->category_id	= Request::i()->category_id;

		if( $fakeRecord->_forum_record AND $fakeRecord->_forum_comments )
		{
			Member::loggedIn()->language()->words['copy_comments_desc']	= sprintf( Member::loggedIn()->language()->get( 'copy_comments_assoc_desc' ), $recordClass::_definiteArticle(), $recordClass::_definiteArticle() );
		}
		else
		{
			Member::loggedIn()->language()->words['copy_comments_desc']	= sprintf( Member::loggedIn()->language()->get( 'copy_comments_desc' ), $recordClass::_definiteArticle() );
		}
		
		Member::loggedIn()->language()->words['copy_author_desc']		= sprintf( Member::loggedIn()->language()->get( 'copy_author_desc' ), $recordClass::_definiteArticle() );

		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--copy-database-topic-categories';
		$form->hiddenValues['record_category_id'] = $category->_id;
		
		foreach( $recordClass::formElements( $fakeRecord, $category ) AS $key => $element )
		{
			/* Skip these */
			if ( in_array( $key, array( 'record_edit_reason', 'record_edit_show' ) ) )
			{
				continue;
			}
			
			$form->add( $element );
		}
		
		if ( $database->options['comments'] )
		{
			$form->add( new YesNo( 'copy_comments', FALSE, FALSE, array( 'togglesOn' => array( 'comments_show_message' ) ) ) );

			$form->add( new YesNo( 'comments_show_message', TRUE, FALSE, array( 'togglesOn' => array( 'comments_meta_message', 'comments_meta_color' ) ), NULL, NULL, NULL, 'comments_show_message' ) );
			$form->add( new Editor( 'comments_meta_message', Member::loggedIn()->language()->addToStack('default_copyposts_message'), FALSE, array( 'app' => 'core', 'key' => 'Meta', 'autoSaveKey' => "meta-message-new", 'attachIds' => NULL ), NULL, NULL, NULL, 'comments_meta_message' ) );
			$form->add( new Custom( 'comments_meta_color', 'none', FALSE, array( 'getHtml' => function( $element )
			{
				return Theme::i()->getTemplate( 'forms', 'core', 'front' )->colorSelection( $element->name, $element->value );
			} ), NULL, NULL, NULL, 'comments_meta_color' ) );
		}
		
		$form->add( new YesNo( 'copy_author', TRUE ) );
		
		if ( $values = $form->values() )
		{
			$comments	= FALSE;
			if ( array_key_exists( 'copy_comments', $values ) )
			{
				$comments	= $values['copy_comments'];
				unset( $values['copy_comments'] );
			}

			/* Determine if we are showing a meta message after */
			$metaMessage = NULL;

			/* We will only be sowing the message if we are copying comments */
			if( $comments )
			{
				if ( array_key_exists( 'comments_show_message', $values ) )
				{
					$metaMessage = array( 'show' => $values['comments_show_message'], 'message' => $values['comments_meta_message'], 'color' => $values['comments_meta_color'] );
				}
			}
			unset( $values['comments_show_message'], $values['comments_meta_message'], $values['comments_meta_color'] );
			
			/* Figure out author */
			$author		= $values['copy_author'];
			unset( $values['copy_author'] );

			/* If we are copying comments and we use the forums for comments, skip creating a topic as we will just reassocciate */
			if( $fakeRecord->_forum_record )
			{
				$recordClass::$skipTopicCreation = true;
			}
			
			$record = $recordClass::createFromForm( $values, $category );
			
			if ( $author )
			{
				$record->changeAuthor( $this->topic->author() );
			}

			if( $metaMessage !== NULL )
			{
				if( $metaMessage['show'] )
				{
					$id = $record->addMessage( $metaMessage['message'], $metaMessage['color'] );
					File::claimAttachments( "meta-message-new", $id, NULL, 'core_ContentMessages' );
				}
			}
			
			/* If the record syncs with the forums and we are copying topics, just associate with the existing topic */
			if( $record->_forum_record )
			{
				$record->record_topicid = $this->topic->tid;
				$record->save();
				
				/* Reload using the proper class so first and last comment data can be rebuilt properly */
				if( $comments and $record->_forum_comments )
				{
					$obj = $record->recordForTopicSynch();
					$obj->rebuildFirstAndLastCommentData();
				}

				try
				{
					/* If the database is on a page, go to the record */
					Output::i()->redirect( $record->url() );
				}
				catch( LogicException $e )
				{
					/* If it is NOT then go back to the topic */
					Output::i()->redirect( $this->topic->url() );
				}
			}
			elseif ( $comments )
			{
				Output::i()->redirect( Url::internal( "app=cms&module=database&controller=topic&do=comments&id={$this->topic->tid}", 'front', 'topic_copy', $this->topic->title_seo )->csrf()->setQueryString( array( 'record_id' => $record->primary_id_field, 'database_id' => $database->_id ) ) );
			}
			else
			{
				try
				{
					/* If the database is on a page, go to the record */
					Output::i()->redirect( $record->url() );
				}
				catch( LogicException $e )
				{
					/* If it is NOT then go back to the topic */
					Output::i()->redirect( $this->topic->url() );
				}
			}
		}
		
		$hasModOptions = FALSE;
		
		if ( $recordClass::modPermission( 'lock', NULL, $category ) or
			 $recordClass::modPermission( 'pin', NULL, $category ) or
			 $recordClass::modPermission( 'hide', NULL, $category ) or
			 $recordClass::modPermission( 'feature', NULL, $category ) or
			 $fieldClass::fixedFieldFormShow( 'record_allow_comments' ) or
			 $fieldClass::fixedFieldFormShow( 'record_expiry_date' ) or
			 $fieldClass::fixedFieldFormShow( 'record_comment_cutoff' ) or
			 Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			$hasModOptions = TRUE;
		}
		
		array_shift( Output::i()->breadcrumb );
		$container	= NULL;
		try
		{
			$container = $this->topic->container();
			foreach ( $container->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
		}
		catch ( Exception $e ) { }
		Output::i()->breadcrumb[] = array( $this->topic->url(), $this->topic->mapped( 'title' ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'copy_topic_to_database' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'copy_topic_to_database' );
		Output::i()->output = $form->customTemplate( array( \IPS\cms\Theme::i()->getTemplate( $database->template_form, 'cms', 'database' ), 'recordForm' ), NULL, $category, $database, $page, Member::loggedIn()->language()->addToStack( 'copy_topic_to_database' ), $hasModOptions );
	}
	
	/**
	 * Comments
	 *
	 * @return	void
	 */
	protected function comments() : void
	{
		Session::i()->csrfCheck();

		$mr = new MultipleRedirect( Url::internal( "app=cms&module=database&controller=topic&id=" . Request::i()->id . "&do=comments", 'front', 'topic_copy', $this->topic->title_seo )->csrf()->setQueryString( array( 'database_id' => Request::i()->database_id, 'record_id' => Request::i()->record_id ) ), function( $data ) {

			/* @var Records $recordClass
			 * @var Records\Comment $commentClass */
			$database		= Databases::load( Request::i()->database_id );
			$recordClass	= 'IPS\cms\Records' . Request::i()->database_id;
			$commentClass	= $recordClass::$commentClass;
			$record			= $recordClass::load( Request::i()->record_id );
			$total			= Db::i()->select( 'COUNT(*)', 'forums_posts', array( "topic_id=? AND new_topic=? AND queued!=?", Request::i()->id, 0, 2 ) )->first();
			
			$done = 0;
			foreach( new ActiveRecordIterator( Db::i()->select( '*', 'forums_posts', array( "topic_id=? AND new_topic=? AND queued!=?", Request::i()->id, 0, -2 ), "pid ASC", array( $data, 100 ) ), 'IPS\forums\Topic\Post' ) AS $post )
			{
				$commentClass::create( $record, $post->content(), FALSE, NULL, TRUE, $post->author(), DateTime::create(), $post->ip_address, $post->hidden() );
				$done++;
			}
			
			if ( !$done )
			{
				return NULL;
			}
			
			return array( $data + $done, Member::loggedIn()->language()->addToStack( 'copying_comments' ), 100 / $total * ( $data + 100 ) );
			
		}, function() {

			/* @var Records $recordClass */
			$recordClass	= 'IPS\cms\Records' . Request::i()->database_id;
			$record			= $recordClass::load( Request::i()->record_id );
			
			try
			{
				Output::i()->redirect( $record->url() );
			}
			catch( LogicException $e )
			{
				Output::i()->redirect( TopicClass::load( Request::i()->id )->url() );
			}
		} );
		
		$topic = TopicClass::load( Request::i()->id );
		
		array_shift( Output::i()->breadcrumb );
		$container	= NULL;
		try
		{
			$container = $topic->container();
			foreach ( $container->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
		}
		catch ( Exception $e ) { }
		Output::i()->breadcrumb[] = array( $topic->url(), $topic->mapped( 'title' ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'copy_topic_to_database' ) );
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'copying_comments' );
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'front' )->genericBlock( (string) $mr, Member::loggedIn()->language()->addToStack( 'copying_comments' ), 'ipsBox i-padding_3' );
	}
}