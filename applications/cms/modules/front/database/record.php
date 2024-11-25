<?php

/**
 * @brief		Record View
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		17 Apr 2014
 */

namespace IPS\cms\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateTimeInterface;
use DomainException;
use Exception;
use IPS\cms\Categories;
use IPS\cms\Databases;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Fields;
use IPS\cms\Pages\Page;
use IPS\cms\Records;
use IPS\cms\Records\Revisions;
use IPS\Content\Comment;
use IPS\Content\Controller;
use IPS\Content\Item;
use IPS\DateTime;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Output\UI\UiExtension;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use function count;
use function defined;
use function get_class;
use const IPS\THUMBNAIL_SIZE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Record View
 */
class record extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = '';
	
	/**
	 * Constructor
	 *
	 * @param Url|null $url		The base URL for this controller or NULL to calculate automatically
	 * @return	void
	 */
	public function __construct( Url $url=NULL )
	{
		static::$contentModel = 'IPS\cms\Records' . Dispatcher::i()->databaseId;
		
		parent::__construct( Dispatcher::i()->url );
	}
	
	/**
	 * View Record
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		/* Load record */
		try
		{
			$record = parent::manage();
		}
		catch( Exception $e )
		{
			Output::i()->error( 'node_error', '2T252/1', 403, '' );
		}
		
		if ( $record === NULL )
		{
			Output::i()->error( 'node_error', '2T252/2', 404, '' );
		}

		if ( Request::i()->view )
		{
			$this->_doViewCheck();
		}

		/* Sort out comments and reviews */
		$tabs  = $record->commentReviewTabs();
		$_tabs = array_keys( $tabs );
		$tab   = isset( Request::i()->tab ) ? Request::i()->tab : array_shift( $_tabs );
		$activeTabContents = $record->commentReviews( $tab );
		$comments = count( $tabs ) ? \IPS\cms\Theme::i()->getTemplate( $record->container()->_template_display, 'cms', 'database' )->commentsAndReviewsTabs( Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $tab, $activeTabContents, $record->url(), 'tab', FALSE, FALSE ), md5( $record->url() ) ) : NULL;

		if ( Request::i()->isAjax() and !isset( Request::i()->rating_submitted ) and ( isset( Request::i()->page ) OR isset( Request::i()->tab ) ) and $activeTabContents )
		{
			Output::i()->sendOutput( $activeTabContents );
		}

		/* @var Categories $class */
		$class = '\IPS\cms\Categories' . $record::$customDatabaseId;
		$category = $class::load( $record->category_id );

		$fieldsClass  = '\\IPS\\cms\\Fields'  . $record::$customDatabaseId;
		/* @var Fields $fieldsClass */
		$updateFields = $fieldsClass::fields( $record->fieldValues(), 'edit', $record->container(), $fieldsClass::FIELD_DISPLAY_COMMENTFORM, $record );
		$form         = null;
		
		/* We need edit permission to change the record */
		if ( count( $updateFields ) and $record->canEdit() )
		{
			$form = new Form( 'update_record', 'update', $record->url()->setQueryString( array( 'd' => $record::$customDatabaseId ) ) );
			$form->class = 'ipsForm--vertical ipsForm--manage-record';

			$hasAdditionalFields = false;
			foreach( $updateFields as $id => $field )
			{
				$form->add( $field );
				if( $id != $record::database()->field_title AND $id != $record::database()->field_content )
				{
					$hasAdditionalFields = true;
				}
			}

			/* The comment is only added for fields that are NOT the title/content. So don't show this checkbox if the only field available is one of those. */
			if( $hasAdditionalFields AND $record->canComment() )
			{
				$form->add( new Checkbox( 'record_display_field_change', TRUE, FALSE ) );
			}
			
			if ( $values = $form->values() )
			{
                $record->processBeforeEdit( $values );

				/* Custom fields */
				$customValues = array();
				$fieldsClass  = 'IPS\cms\Fields' . $record::$customDatabaseId;
				
				foreach( $values as $k => $v )
				{
					if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
					{
						$customValues[ mb_substr( $k, 8 ) ] = $v;
					}
				}

				if ( count( $customValues ) )
				{
					/* Store a revision before we change any values */
					if ( $record::database()->revisions )
					{
						$revision = new Revisions;
						$revision->database_id = $record::$customDatabaseId;
						$revision->record_id   = $record->_id;
						$revision->data        = $record->fieldValues( TRUE );
						$revision->save();
					}

					if ( isset( $values['record_display_field_change'] ) AND $values['record_display_field_change'] )
					{
						$record->addCommentWhenFiltersChanged( $values );
					}
					else
					{
						/* Set excludes for custom field updates. We'll send the notifications later, once we know the new content of the fields. */
						$record->setFieldQuoteAndMentionExcludes();
					}

					foreach( $fieldsClass::fields( $customValues, 'edit', $record->category_id ? $category : NULL, $fieldsClass::FIELD_DISPLAY_COMMENTFORM ) as $key => $field )
					{
						$key = 'field_' . $key;
						$record->$key = $field::stringValue($values[$field->name] ?? NULL);
					}
					
					/* Send custom field update notifications */
					if ( !isset( $values['record_display_field_change'] ) OR !$values['record_display_field_change'] )
					{
						$record->sendFieldQuoteAndMentionNotifications();
					}

					$record->save();
					$record->processAfterEdit( $values );

					$fieldObjects = $fieldsClass::data( NULL, $record->category_id ? $category : NULL );

					foreach( $fieldObjects as $id => $row )
					{
						if ( $row->type == 'Item' )
						{
							$record->processItemFieldData( $row );
						}
					}
					Output::i()->redirect( $record->url() );
				}
			}
		}

		if ( $record->record_meta_keywords )
		{
			Output::i()->metaTags['keywords'] = $record->record_meta_keywords;
		}
		
		if ( $record->record_meta_description )
		{
			Output::i()->metaTags['description'] = $record->record_meta_description;
			Output::i()->metaTags['og:description'] = $record->record_meta_description;
		}

		/* Set record URL as canonical tag */
		if ( $record::database()->canonical_flag == 1 and ( isset( Request::i()->page ) and Request::i()->page > 1 ) )
		{
			Output::i()->linkTags['canonical'] = $record->url()->setPage( 'page', Request::i()->page );
		}
		else
		{
			Output::i()->linkTags['canonical'] = $record->url();
		}

		/* Update location */
		if( $record->database()->use_categories )
		{
			Session::i()->setLocation( $record->url(), $record->onlineListPermissions(), 'loc_cms_viewing_db_record', array( $record->_title => FALSE, 'content_db_' . $record->database()->id => TRUE ,'content_cat_name_' . $category->id => TRUE ) );
		}
		else
		{
			Session::i()->setLocation( $record->url(), $record->onlineListPermissions(), 'loc_cms_viewing_db_record_no_cats', array( $record->_title => FALSE, 'content_db_' . $record->database()->id => TRUE ) );
		}

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'records/record.css', 'cms', 'front' ) );

		/* Next unread */
		try
		{
			$nextUnread	= $record->containerHasUnread();
		}
		catch( Exception $e )
		{
			$nextUnread	= NULL;
		}
		
		if ( $record->record_image )
		{
			Output::i()->metaTags['og:image'] = (string) File::get( 'cms_Records', $record->record_image )->url;
		}

		/* Add Json-LD */
		$jsonLdText = $record->truncated( TRUE, NULL );

		Output::i()->jsonLd['article']	= array(
			'@context'		=> "https://schema.org",
			'@type'			=> "Article",
			'url'			=> (string) $record->url(),
			'discussionUrl'	=> (string) $record->url(),
			'mainEntityOfPage'	=> (string) $record->url(),
			'name'			=> $record->_title,
			'headline'		=> $record->_title,
			'text'			=> $jsonLdText,
			'articleBody'	=> $jsonLdText,
			'dateCreated'	=> DateTime::ts( $record->record_saved )->format( DateTimeInterface::ATOM ),
			'datePublished'	=> DateTime::ts( $record->record_publish_date ?: $record->record_saved )->format( DateTimeInterface::ATOM ),
			'dateModified'	=> DateTime::ts( $record->record_edit_time ?: ( $record->record_publish_date ?: $record->record_saved ) )->format( DateTimeInterface::ATOM ),
			'pageStart'		=> 1,
			'pageEnd'		=> $record->commentPageCount(),
			'author'		=> array(
				'@type'		=> 'Person',
				'name'		=> (string) Member::load( $record->member_id )->name,
				'image'		=> (string) Member::load( $record->member_id )->get_photo( TRUE, TRUE )
			),
			'publisher'		=> array(
				'@id' => Settings::i()->base_url . '#organization',
				'member'	=> array(
					'@type'		=> "Person",
					'name'		=> Member::load( $record->member_id )->name,
					'image'		=> (string) Member::load( $record->member_id )->get_photo( TRUE, TRUE ),
				)
			),
			'interactionStatistic'	=> array(
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/ViewAction",
					'userInteractionCount'	=> $record->record_views
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/FollowAction",
					'userInteractionCount'	=> $record::containerFollowerCount( $record->container() )
				),
			),
		);

		/* Do we have a real author? */
		if( $record->member_id )
		{
			Output::i()->jsonLd['article']['author']['url']	= (string) Member::load( $record->member_id )->url();
			Output::i()->jsonLd['article']['publisher']['member']['url'] = (string) Member::load( $record->member_id )->url();
		}

		$logo = NULL;
		if( Theme::i()->logo['front'] )
		{
			try
			{
				$logo = File::get( 'core_Theme', Theme::i()->logo['front']['url'] )->url;
			}
				/* File doesn't exist */
			catch ( RuntimeException | DomainException $e ){}
		}
		Output::i()->jsonLd['article']['publisher']['logo'] = array(
			'@type'		=> 'ImageObject',
			'url'		=> $logo ? (string) $logo : (string) Member::load( $record->member_id )->get_photo( TRUE, TRUE ),
		);

		/* Image is required */
		if( $record->record_image )
		{
			try
			{
				$imageObj	= File::get( 'cms_Records', $record->record_image );

				Output::i()->jsonLd['article']['image'] = array(
					'@type'		=> 'ImageObject',
					'url'		=> (string) $imageObj->url
				);
			}
			/* File doesn't exist */
			catch ( RuntimeException | DomainException $e ){}
		}
		else
		{
			$photoVars = explode( 'x', THUMBNAIL_SIZE );
			
			Output::i()->jsonLd['article']['image'] = array(
				'@type'		=> 'ImageObject',
				'url'		=> Member::load( $record->member_id )->get_photo( TRUE, TRUE ),
				'width'		=> $photoVars[0],
				'height'	=> $photoVars[1]
			);
		}

		if( $record::database()->options['reviews'] or $record->container()->allow_rating )
		{
			Output::i()->jsonLd['article']['interactionStatistic'][]	= array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "https://schema.org/ReviewAction",
				'userInteractionCount'	=> $record->record_reviews
			);
		}

		if( $record::database()->options['comments'] )
		{
			Output::i()->jsonLd['article']['commentCount'] = $record->record_comments;
			Output::i()->jsonLd['article']['interactionStatistic'][]	= array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "https://schema.org/CommentAction",
				'userInteractionCount'	=> $record->record_comments
			);
		}

		$this->showClubHeader();
		/* Set default search to this record */
		if ( ! $record::database()->search )
		{
			Output::i()->defaultSearchOption = array( 'all', 'search_everything' );
		}
		else
		{
			$type = mb_strtolower( str_replace( '\\', '_', mb_substr( get_class( $record ), 4 ) ) );
			Output::i()->defaultSearchOption = array( $type, "{$type}_pl" );
			
			Output::i()->contextualSearchOptions = array();
			Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $record::$title ) ) ) ) ] = array( 'type' => $type, 'item' => $record->_id );
	
			try
			{
				$container = $record->container();
				Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_categories' ) ] = array( 'type' => mb_strtolower( str_replace( '\\', '_', mb_substr( get_class( $record ), 4 ) ) ), 'nodes' => $container->_id );
			}
			catch ( BadMethodCallException $e ) { }
		}

		Dispatcher::i()->output .= \IPS\cms\Theme::i()->getTemplate( $record->container()->_template_display, 'cms', 'database' )->record( $record, $comments, $form, $nextUnread );
		return null;
	}

	/**
	 * Set the breadcrumb and title
	 *
	 * @param Item $item	Content item
	 * @param bool $link	Link the content item element in the breadcrumb
	 * @return	void
	 */
	protected function _setBreadcrumbAndTitle( Item $item, bool $link=TRUE ): void
	{
		$database = Databases::load( Dispatcher::i()->databaseId );
		if ( $database->use_categories )
		{
			parent::_setBreadcrumbAndTitle( $item, $link );
		}
		else
		{
			Output::i()->breadcrumb[] = array( $link ? $item->url() : NULL, $item->mapped('title') );

			$title = ( isset( Request::i()->page ) and Request::i()->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $item->mapped('title') . ' - ' . $database->pageTitle(), Request::i()->page ) ) ) : $item->mapped('title') . ' - ' . $database->pageTitle();
			Output::i()->title = $title;
		}
	}

	/**
	 * View check
	 *
	 * @return	void
	 */
	protected function _doViewCheck() : void
	{
		try
		{
			/* @var Item $class */
			$class	= static::$contentModel;
			$topic	= $class::loadAndCheckPerms( Request::i()->id );
			
			switch( Request::i()->view )
			{
				case 'getnewpost':
					Output::i()->redirect( $topic->url( 'getNewComment' ) );
				break;
				
				case 'getlastpost':
					Output::i()->redirect( $topic->url( 'getLastComment' ) );
				break;
			}
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/F', 403, '' );
		}
	}
	
	/**
	 * Revisions
	 *
	 * @return	void
	 */
	protected function revisions() : void
	{
		/* @var Records $recordClass */
		$recordClass  = '\IPS\cms\Records' . Dispatcher::i()->databaseId;
		
		try
		{
			$record   = $recordClass::loadAndCheckPerms( Request::i()->id );
			$category = $record->container();
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T252/4', 403, '' );
		}
		
		if ( ! $record->canManageRevisions() )
		{
			Output::i()->error( 'module_no_permission', '2T252/5', 403, '' );
		}

		$title = Member::loggedIn()->language()->addToStack('content_revision_record_title', FALSE, array( 'sprintf' => array( $record->_title ) ) );
		
		$table = new Db( 'cms_database_revisions', $record->url('revisions'), array( 'revision_database_id=? and revision_record_id=?', $record::$customDatabaseId, $record->_id ) );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'revisions', 'cms', 'front' ), 'table' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'revisions', 'cms', 'front' ), 'rows' );
		$table->title = $title;
		$table->include = array( 'revision_id', 'revision_date', 'revision_data', 'revision_member_id' );
		$table->mainColumn = 'revision_date';
		$table->sortBy = $table->sortBy ?: 'revision_date';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Parsers */
		$table->parsers = array(
				'revision_member_id' => function( $val )
				{
					return Member::load( $val );
				},
				'revision_date' => function( $val )
				{
					return DateTime::ts( $val )->relative();
				},
				'revision_data' => function( $val, $row ) use ( $record )
				{
					return Revisions::load( $row['revision_id'] )->getDiffHtmlTables( $record::$customDatabaseId, $record, true );
				}
		);

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/diff.css', 'core', 'admin' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/diff_match_patch.js', 'core', 'interface' ) );
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_records.js', 'cms' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'records/record.css', 'cms', 'front' ) );
		
		/* Output */
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $table ) );
		}
		else
		{
			try
			{
				foreach( $category->parents() AS $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
			catch( Exception $e ) {}
			
			Output::i()->breadcrumb[] = array( $record->url(), $record->_title );
			
			Output::i()->title   					= $title;
			Dispatcher::i()->output .= (string) $table;
		}
	}
	
	/**
	 * Delete Revision
	 *
	 * @return	void
	 */
	protected function revisionDelete() : void
	{
		Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		/* @var Records $recordClass */
		$recordClass  = '\IPS\cms\Records' . Dispatcher::i()->databaseId;
	
		try
		{
			$record   = $recordClass::loadAndCheckPerms( Request::i()->id );
			$category = $record->container();
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T252/6', 403, '' );
		}
	
		try
		{
			$revision = Revisions::load( Request::i()->revision_id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T252/7', 403, '' );
		}
	
		if ( ! $record->canManageRevisions() )
		{
			Output::i()->error( 'module_no_permission', '2T252/8', 403, '' );
		}
		
		$revision->delete();
		
		if ( isset( Request::i()->ajax ) )
		{
			Output::i()->redirect( $record->url() );
		}
		else
		{
			Output::i()->redirect( $record->url('revisions') );
		}
	}
	
	/**
	 * View Revision
	 *
	 * @return	void
	 */
	protected function revisionView() : void
	{
		/* @var Records $recordClass */
		$recordClass  = '\IPS\cms\Records' . Dispatcher::i()->databaseId;
	
		try
		{
			$record   = $recordClass::loadAndCheckPerms( Request::i()->id );
			$category = $record->container();
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T252/9', 403, '' );
		}
		
		try
		{
			$revision = Revisions::load( Request::i()->revision_id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T252/A', 403, '' );
		}
	
		if ( ! $record->canManageRevisions() )
		{
			Output::i()->error( 'module_no_permission', '2T252/B', 403, '' );
		}

		$title        = Member::loggedIn()->language()->addToStack('content_revision_record_title', FALSE, array( 'sprintf' => array( $record->_title ) ) );

		/* @var Fields $fieldsClass */
		$fieldsClass  = 'IPS\cms\Fields' .  $record::$customDatabaseId;
		$customFields = $fieldsClass::data( 'view', $category );
		$conflicts    = array();
		$form         = new Form( 'form', 'content_revision_restore' );

		/* Add a "cancel" button that will take you back to the previous page */
		array_unshift( $form->actionButtons, Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( 'cancel', 'link', $record->url()->setQueryString( array( 'do' => 'revisions', 'd' => $record::$customDatabaseId ) ), 'ipsButton ipsButton--text', array( 'tabindex' => '3', 'accesskey' => 'c' ) ) );

		/* Build up our data set */
		$conflicts = $revision->getDiffHtmlTables( $record::$customDatabaseId, $record, true );

		/* If there is only one change, then clicking restore naturally means to revert that single change, so we don't need a form */
		if( count( $conflicts ) === 1 )
		{
			foreach( $conflicts as $conflict )
			{
				$form->hiddenValues[ 'conflict_' . $conflict['field']->id ] = 'old';
			}
		}
		/* Otherwise if multiple fields have changes to compare, let the admin decide what to do */
		else
		{
			foreach( $conflicts as $conflict )
			{
				$form->add( new Radio( 'conflict_' . $conflict['field']->id, 'no', false, array( 'options' => array( 'old' => '', 'new' => '' ) ) ) );
			}
		}

		if ( $values = $form->values() )
		{
			foreach( $values as $k => $v )
			{
				if ( $v === 'old' )
				{
					$fieldId = mb_substr( $k, 9 );
					$key     = 'field_' . $fieldId;
					$record->$key = $revision->get( $key );
				}
				
				Session::i()->modLog( 'modlog__content_revision_restored', array( $record->_title => FALSE, $revision->id => FALSE ) );
				
				$record->save();
				$revision->delete();
				
				Output::i()->redirect( $record->url(), 'content_revision_restored' );
			}
		}
		
		try
		{
			foreach( $category->parents() AS $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
		}
		catch( Exception $e ) {}
		
		Output::i()->breadcrumb[] = array( $record->url(), $record->_title );
		Output::i()->breadcrumb[] = array( $record->url()->setQueryString( array( 'do' => 'revisions', 'd' => $record::$customDatabaseId ) ), $title );
			
		Output::i()->title   = $title;
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/diff.css', 'core', 'admin' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/diff_match_patch.js', 'core', 'interface' ) );
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_records.js', 'cms' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
			
		Dispatcher::i()->output   = $form->customTemplate( array( Theme::i()->getTemplate( 'revisions', 'cms' ), 'view' ), $record, $revision, $conflicts );
	}
	
	/**
	 * Edit Item
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_records.js', 'cms' ) );

		/* @var Records $recordClass */
		$recordClass  = '\IPS\cms\Records' . Dispatcher::i()->databaseId;
		$fieldsClass  = '\IPS\cms\Fields' . Dispatcher::i()->databaseId;
		$database     = Databases::load( Dispatcher::i()->databaseId );
		try
		{
			$record       = $recordClass::loadAndCheckPerms( Request::i()->id );
			$category     = $record->container();
				
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T252/C', 403, '' );
		}
		
		$title        = Member::loggedIn()->language()->addToStack( 'content_record_form_edit_record', FALSE, array( 'sprintf' => array( $record->_title ) ) );
		$formElements = $recordClass::formElements( $record, $category );

		// We check if the form has been submitted to prevent the user loosing their content
		if ( isset( Request::i()->form_submitted ) )
		{
			if ( ! $record->couldEdit() )
			{
				Output::i()->error( 'module_no_permission', '2T252/G', 403, '' );
			}
		}
		else
		{
			if ( ! $record->canEdit() )
			{
				Output::i()->error( 'module_no_permission', '2T252/D', 403, '' );
			}
		}
		
		$form = new Form( 'form', isset( Member::loggedIn()->language()->words[ $recordClass::$formLangPrefix . '_save' ] ) ? $recordClass::$formLangPrefix . '_save' : 'save' );
		$form->class = 'ipsForm--vertical ipsForm--edit-record';
			
		foreach( $formElements as $name => $field )
		{
			$form->add( $field );
		}

		/* Now loop through and add all the elements to the form */
		foreach( UiExtension::i()->run( $record, 'formElements', array( $record->container() ) ) as $element )
		{
			$form->add( $element );
		}
		
		$hasModOptions = FALSE;

		/* @var Fields $fieldsClass */
		if ( $recordClass::modPermission( 'lock', NULL, $category ) or
			 $recordClass::modPermission( 'pin', NULL, $category ) or 
			 $record->canHide() or 
			 $recordClass::modPermission( 'feature', NULL, $category ) or
			 $fieldsClass::fixedFieldFormShow( 'record_allow_comments' ) or
			 $fieldsClass::fixedFieldFormShow( 'record_expiry_date' ) or
			 $fieldsClass::fixedFieldFormShow( 'record_comment_cutoff' ) or
			 Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			$hasModOptions = TRUE;
		}
		
		if ( $values = $form->values() )
		{
            $record->processBeforeEdit( $values );
			$record->processForm( $values );
			$record->processAfterEdit( $values );

			if ( isset( $recordClass::$databaseColumnMap['date'] ) and isset( $values[ $recordClass::$formLangPrefix . 'date' ] ) )
			{
				$column = $recordClass::$databaseColumnMap['date'];

				if ( $values[ $recordClass::$formLangPrefix . 'date' ] instanceof DateTime )
				{
					$record->$column = $values[ $recordClass::$formLangPrefix . 'date' ]->getTimestamp();
				}
			}

			$record->save();

			Session::i()->modLog( 'modlog__item_edit', array( $record::$title => FALSE, $record->url()->__toString() => FALSE, $record::$title => TRUE, $record->mapped( 'title' ) => FALSE ), $record );

			Output::i()->redirect( $record->url() );
		}
		
		Output::i()->allowDefaultWidgets = FALSE;
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->title = Member::loggedIn()->language()->addToStack( $title );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'records/form.css', 'cms', 'front' ) );
		
		try
		{
			if ( $database->use_categories )
			{
				foreach( $category->parents() AS $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
		}
		catch( Exception $e ) {}
		
		Output::i()->breadcrumb[] = array( $record->url(), $record->mapped('title') );

		$this->showClubHeader();
		Dispatcher::i()->output = $form->customTemplate( array( \IPS\cms\Theme::i()->getTemplate( $database->template_form, 'cms', 'database' ), 'recordForm' ), NULL, $category, $database, Page::$currentPage, $title, $hasModOptions );

	}
	
	/**
	 * Mark Database Record Read
	 *
	 * @return	void
	 */
	public function markRead() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$record = $this->_getRecord();
			$record->markRead();
			Output::i()->redirect( $record->url() );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2F173/C', 403, 'module_no_permission_guest' );
		}
	}
	
	/**
	 * Return a record based on query string 'id' param
	 * 
	 * @return Records
	 */
	public function _getRecord(): Records
	{
		/* @var Records $recordClass */
		$recordClass  = '\IPS\cms\Records' . Dispatcher::i()->databaseId;

		try
		{
			$record = $recordClass::loadAndCheckPerms( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T252/E', 403, '' );
		}
		
		return $record;
	}
	
	/* IP.Board integration */
	
	/**
	 * Hide Comment/Review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	public function _hide( string $commentClass, Comment $comment, Item $item  ): void
	{
		$this->_doSomething( '_hide', $commentClass, $comment, $item );
	}
	
	/**
	 * Unhide Comment/Review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	public function _unhide( string $commentClass, Comment $comment, Item $item ): void
	{
		$this->_doSomething( '_unhide', $commentClass, $comment, $item );
	}
	
	/**
	 * Split Comment
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return    void
	 * @throws	LogicException
	 */
	protected function _split( string $commentClass, Comment $comment, Item $item ): void
	{
		$this->_doSomething( '_split', $commentClass, $comment, $item );
	}
	
	/**
	 * Edit Comment/Review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _edit( string $commentClass, Comment $comment, Item $item ) : void
	{
		$this->_doSomething( '_edit', $commentClass, $comment, $item );
	}
	
	/**
	 * Report Comment/Review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _report( string $commentClass, Comment $comment, Item $item ) : void
	{
		$this->_doSomething( '_report', $commentClass, $comment, $item );
	}
	
	/**
	 * Edit Log
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	public function _editlog( string $commentClass, Comment $comment, Item $item ) : void
	{
		$this->_doSomething( '_editlog', $commentClass, $comment, $item );
	}
	
	/**
	 * Delete Comment/Review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _delete( string $commentClass, Comment $comment, Item $item ) : void
	{
		$this->_doSomething( '_delete', $commentClass, $comment, $item );
	}
	
	/**
	 * Rep Comment/Review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _react( string $commentClass, Comment $comment, Item $item ): void
	{
		$this->_doSomething( '_react', $commentClass, $comment, $item );
	}
	
	/**
	 * Show Comment/Review Rep
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _showReactions( string $commentClass, Comment $comment, Item $item ): void
	{
		$this->_doSomething( '_showReactions', $commentClass, $comment, $item );
	}
	
	/**
	 * Do something that needs to be overridden from the Content controller
	 *
	 * @param string $method			The method name
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _doSomething( string $method, string $commentClass, Comment $comment, Item $item ) : void
	{
		$record = $this->_getRecord();

		if ( $record->useForumComments() AND isset( Request::i()->comment) )
		{
			/* @var Records\CommentTopicSync $commentClass */
			$commentClass = 'IPS\cms\Records\CommentTopicSync' . $record::$customDatabaseId;
			$comment      = $commentClass::load( Request::i()->comment );
			$item         = $record;
		}

		try
		{
			parent::$method( $commentClass, $comment, $item );
		}
		catch( LogicException $e )
		{
			Output::i()->error( 'node_error', '2T252/F', 403, '' );
		}
	}

	/**
	 * Add the club header to \IPS\Output
	 *
	 * @return void
	 */
	protected function showClubHeader() : void
	{
		$record = $this->_getRecord();
		if ( $record and $club = $record->club )
		{
			$category = $record->container();

			Output::i()->sidebar['contextual'] = '';

			/* Club info in sidebar */
			if ( Settings::i()->clubs_header == 'sidebar' )
			{
				Output::i()->sidebar['enabled'] = true;
				Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core', 'front' )->header( $club, $category, 'sidebar' );
			}
			else
			{
				Dispatcher::i()->output .= Theme::i()->getTemplate( 'clubs', 'core', 'front' )->header( $club, $record->container(), 'full' );
			}

			if( ( GeoLocation::enabled() and Settings::i()->clubs_locations AND $location = $club->location() ) )
			{
				Output::i()->sidebar['enabled'] = true;
				Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core', 'front' )->clubLocationBox( $club, $location );
			}
		}
	}
}