<?php
/**
 * @brief		Content Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Jul 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DomainException;
use ErrorException;
use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\cloud\Application as CloudApplication;
use IPS\Content\Search\SearchContent;
use IPS\core\Feature;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Helpers\Table\Db as TableDb;
use IPS\IPS;
use IPS\Application;
use IPS\Content;
use IPS\Content\Search\Index;
use IPS\Content\Search\Query;
use IPS\core\Alerts\Alert;
use IPS\core\DataLayer;
use IPS\core\Facebook\Pixel;
use IPS\core\FrontNavigation;
use IPS\core\Reports\Report;
use IPS\core\Reports\Types;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Events\Event;
use IPS\File;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\CoverPhoto\Controller as CoverPhotoController;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Rating;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use LengthException;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function array_keys;
use function array_merge;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_subclass_of;
use function strlen;
use function time;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Controller
 * @method multimodComment( string $string, Controller $controller)
 */
class Controller extends CoverPhotoController
{
	/**
	 * @brief	Should views and item markers be updated by AJAX requests?
	 */
	protected bool $updateViewsAndMarkersOnAjax = FALSE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* We do this to prevent SQL errors with page offsets */
		if ( isset( Request::i()->page ) )
		{
			Request::i()->page	= intval( Request::i()->page );
			if ( !Request::i()->page OR Request::i()->page < 1 )
			{
				Request::i()->page	= 1;
			}
		}

		/* Ensure JS loaded for forms/content functions such as moderation */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_core.js', 'core' ) );

		parent::execute();

		/* Do this to prevent non-existent and non-accessible items from returning a status of 200 */
		if ( empty( Output::i()->output ) AND !Request::i()->_bypassItemIdCheck AND isset( Request::i()->id ) )
		{
			$contentModel = static::$contentModel;
			try
			{
				$item = $contentModel::load( Request::i()->id );
				if ( !$item->can( 'read' ) )
				{
					Output::i()->error( 'node_error', '2S136/1X', 403, '' );
				}
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2S136/1Y', 404, '' );
			}
		}
	}

	/**
	 * View Item
	 *
	 * @return    mixed
	 */
	protected function manage() : mixed
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			/* Have we moved? If a user has loaded a forum, and a moderator merges two topics leaving a link, then we need to account for this if the
				user happens to hover over the topic that was removed before refreshing the page. */
			if ( isset( $class::$databaseColumnMap['state'] ) AND isset( $class::$databaseColumnMap['moved_to'] ) )
			{
				$stateColumn	= $class::$databaseColumnMap['state'];
				$movedToColumn	= $class::$databaseColumnMap['moved_to'];
				$movedTo		= explode( '&', $item->$movedToColumn );
				
				if ( $item->$stateColumn == 'link' OR $item->$stateColumn == 'merged' )
				{
					try
					{
						$moved = $class::loadAndCheckPerms( $movedTo[0] );

						if ( Request::i()->isAjax() AND Request::i()->preview )
						{
							return null;
						}

						Output::i()->redirect( $moved->url() );
					}
					catch( OutOfRangeException $e ) { }
				}
			}

			/* Publish if required */
			if ( IPS::classUsesTrait( $item, 'IPS\Content\FuturePublishing' ) and isset( $class::$databaseColumnMap['is_future_entry'] ) AND isset( $class::$databaseColumnMap['future_date'] ) )
			{
				$futureColumn = $class::$databaseColumnMap['future_date'];
				$futureEntry = $class::$databaseColumnMap['is_future_entry'];
				if ( $item->$futureEntry == 1 and $item->$futureColumn <= time() )
				{
					$item->publish();
				}
			}

			/* If this is an AJAX request (like the topic hovercard), we don't want to do any of the below like update views and mark read */
			if ( Request::i()->isAjax() and !$this->updateViewsAndMarkersOnAjax )
			{
				/* But we do want to mark read if we are paging through the content */
				if( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) AND isset( Request::i()->page ) AND Request::i()->page AND $item->isLastPage() )
				{
					$item->markRead();
				}

				/* We also want to update the views if we have a page parameter */
				if( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) AND isset( Request::i()->page ) AND Request::i()->page )
				{
					$idColumn = $class::$databaseColumnId;
					if ( IPS::classUsesTrait( $class, 'IPS\Content\ViewUpdates' ) AND isset( $class::$databaseColumnMap['views'] ) )
					{
						$item->updateViews();
					}
				}

				return $item;
			}

			/* Do we need to convert any legacy URL parameters? */
			if( $redirectToUrl = $item->checkForLegacyParameters() )
			{
				Output::i()->redirect( $redirectToUrl );
			}

			/* Get ready to store data layer info */
			$dataLayer = Settings::i()->core_datalayer_enabled;
			$dataLayerProperties = array();
			if ( $dataLayer )
			{
				$dataLayerProperties = $item->getDataLayerProperties();
			}

			/* Check we're on a valid page */
			$paginationType = 'comment';
			if ( isset( Request::i()->tab ) and  Request::i()->tab === 'reviews' )
			{
				$paginationType = 'review';
			}
			
			$methodName = "{$paginationType}PageCount";
			$pageCount = $item->$methodName();

			if ( isset( Request::i()->page ) )
			{
				$paginationType	= NULL;
				$container		= ( isset( $item::$databaseColumnMap['container'] ) ) ? $item->container() : NULL;

				if( $item::supportsComments( NULL, $container ) )
				{
					$paginationType	= 'comment';
				}

				if( ( isset( Request::i()->tab ) and Request::i()->tab === 'reviews' AND $item::supportsReviews( NULL, $container ) ) OR
					( $item::supportsReviews( NULL, $container ) AND $paginationType === NULL ) )
				{
					$paginationType = 'review';
				}

				if( $paginationType !== NULL )
				{
					$methodName = "{$paginationType}PageCount";
					$pageCount = $item->$methodName();
					if ( $pageCount and Request::i()->page > $pageCount )
					{
						$lastPageMethod = 'last' . IPS::mb_ucfirst( $paginationType ) . 'PageUrl';
						Output::i()->redirect( $item->$lastPageMethod(), NULL, 303 );
					}

					$dataLayerProperties['page_number'] = intval( Request::i()->page );
				}

				/* Add rel tags */
				if( Request::i()->page != 1 )
				{
					Output::i()->linkTags['first'] = (string) $item->url();

					if( Request::i()->page - 1 > 1 )
					{
						Output::i()->linkTags['prev'] = (string) $item->url()->setPage( 'page', Request::i()->page - 1 );
					}
					else
					{
						Output::i()->linkTags['prev'] = (string) $item->url();
					}
				}
				/* If we literally requested ?page=1 add canonical tag to get rid of the page query string param */
				elseif( isset( $item->url()->data[ Url::COMPONENT_QUERY ]['page' ] ) )
				{
					Output::i()->linkTags['canonical'] = (string) $item->url();
				}
			}

			/* Add rel tags */
			if ( $pageCount > 1 AND ( !Request::i()->page OR $pageCount > Request::i()->page ) )
			{
				Output::i()->linkTags['next'] = (string) $item->url()->setPage( 'page', ( Request::i()->page ?: 1 ) + 1 );
			}
			if ( $pageCount > 1 AND ( !Request::i()->page OR $pageCount != Request::i()->page ) )
			{
				Output::i()->linkTags['last'] = (string) $item->url()->setPage( 'page', $pageCount );
			}

			/* Update Views */
			$idColumn = $class::$databaseColumnId;
			if ( IPS::classUsesTrait( $class, 'IPS\Content\ViewUpdates' ) AND isset( $class::$databaseColumnMap['views'] ) )
			{
				$item->updateViews();
			}
						
			/* Mark read */
			if( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) )
			{	
				/* Note time last read before we mark it read so that the line is in the right place */
				$item->timeLastRead();
				
				if ( $item->isLastPage() )
				{
					$item->markRead();
				}
			}
			
			/* Set navigation and title */
			$this->_setBreadcrumbAndTitle( $item, FALSE );
			
			/* Set meta tags */
			Output::i()->linkTags['canonical'] = (string) ( Request::i()->page > 1 ) ? $item->url()->setPage( 'page', Request::i()->page ) : $item->url() ;
			Output::i()->metaTags['og:title'] = $item->mapped( 'title' );
			Output::i()->metaTags['og:type'] = 'website';
			Output::i()->metaTags['og:url'] = (string) $item->url();
			
			/* Do not set description tags for page 2+ as you end up with duplicate tags */
			if ( Request::i()->page < 2 )
			{
				Output::i()->metaTags['description'] = $item->metaDescription();
				/* If we had $_SESSION['_findComment'] and a specific comment's text was pulled, that var would have been wiped out on the first call */
				Output::i()->metaTags['og:description'] = Output::i()->metaTags['description'];
			}

			/* Facebook Pixel */
			$itemId = $class::$databaseColumnId;
			Pixel::i()->PageView = array(
				'item_id' => $item->$itemId,
				'item_name' => $item->mapped( 'title' ),
				'item_type' => $class::$contentType ?? $class::$title,
				'category_name' => isset( $item::$databaseColumnMap['container'] ) ? $item->container()->_title : NULL
			);

			/* Data Layer */
			if ( $dataLayer )
			{
				if ( !Request::i()->isAjax() )
				{
					DataLayer::i()->addEvent( 'content_view', $dataLayerProperties );
				}
				unset( $dataLayerProperties['ips_key'] );
				foreach ( $dataLayerProperties as $key => $value )
				{
					DataLayer::i()->addContextProperty( $key, $value );
				}
			}

			if( $item->mapped( 'updated' ) OR $item->mapped( 'last_comment' ) OR $item->mapped( 'last_review' ) )
			{
				Output::i()->metaTags['og:updated_time'] = DateTime::ts( $item->mapped( 'updated' ) ? $item->mapped( 'updated' ) : ( $item->mapped( 'last_comment' ) ? $item->mapped( 'last_comment' ) : $item->mapped( 'last_review' ) ) )->rfc3339();
			}

			if( IPS::classUsesTrait( $item, 'IPS\Content\Taggable' ) )
			{
				$tags = array();

				if( $item->prefix() !== NULL )
				{
					$tags[]	= $item->prefix();
				}

				if( $item->tags() !== NULL )
				{
					$tags = array_merge( $tags, $item->tags() );
				}

				if( count( $tags ) )
				{
					Output::i()->metaTags['keywords'] = implode( ', ', $tags );
				}
			}
			
			/* Add contextual search options */
			if( SearchContent::isSearchable( $item ) )
			{
				Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_' . $item::$title ) ] = array( 'type' => mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ), 'item' => $item->$idColumn );

				try
				{
					$container = $item->container();
					Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_' . $container::$nodeTitle ) ] = array( 'type' => mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ), 'nodes' => $container->_id );
				}
				catch ( BadMethodCallException $e ) { }
			}

			/* Contextual Sidebar */
			if( !isset( Output::i()->sidebar['contextual'] ) )
			{
				Output::i()->sidebar['contextual'] = '';
			}

			Output::i()->sidebar['contextual'] .= $item->ui( 'sidebar' );

			if( Application::appIsEnabled( 'cloud' ) )
			{
				Output::setCacheTime( CloudApplication::getCacheDateForPage( $item->mapped('updated'), ( ( $item::$application === 'forums' and $item->isArchived() ) ? 'archived' : 'content' ) ) );
			}

			/* Return */
			return $item;
		}
		catch ( LogicException $e )
		{
			try
			{
				Output::i()->redirect( $class::getRedirectFrom( Request::i()->id )->url() );
			}
			catch( OutOfRangeException $e ) { }

			return NULL;
		}
	}

	/**
	 * Get the item for this controller
	 *
	 * @param int|null	$id
	 *
	 * @return Item|null
	 */
	public static function loadItem( ?int $id=null ) : Item|null
	{
		if ( $id === null )
		{
			$id = Request::i()->id;
		}

		if ( is_numeric( $id ) )
		{
			$id = (int) $id;
		}

		if ( !is_int( $id ) )
		{
			return null;
		}

		try
		{
			$class = static::$contentModel;
			return $class::loadAndCheckPerms( $id );
		}
		catch ( UnderflowException|OutOfBoundsException|OutOfRangeException )
		{
			return null;
		}
	}
	
	/**
	 * AJAX - check for new replies
	 *
	 * @return    Item|NULL
	 */
	protected function checkForNewReplies(): Item|NULL
	{
		Session::i()->csrfCheck();

		/* If auto-polling isn't enabled, kill the polling now */
		if ( !Settings::i()->auto_polling_enabled )
		{
			Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
		}

		/* If we're filtering the topic (live topic questions) then the max commentID in the source will not be the max commentID possible
		   as not all comments will be shown */
		if ( isset( Request::i()->ltqid ) )
		{
			Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
		}

		/* If we're a guest, disable polling */
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
		}

		try
		{
			/* no need for polling for embeds */
			if ( !isset( static::$contentModel ) )
			{
				Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
			}

			$class = static::$contentModel;

			/* no need for polling if the content item doesn't have comments */
			if ( !isset( $class::$commentClass ) )
			{
				Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
			}

			$item = $class::loadAndCheckPerms( Request::i()->id );
			$commentClass = $class::$commentClass;

			/* Don't auto-poll archived content, it never gets updated */
			if( isset( $item::$archiveClass ) AND method_exists( $item, 'isArchived' ) AND $item->isArchived() )
			{
				Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
			}

			/* @var $databaseColumnMap array */
			$commentIdColumn = $commentClass::$databaseColumnId;
			$commentDateColumn = $commentClass::$databaseColumnMap['date'];
			$perPage = $class::getCommentsPerPage();
			
			/* The form field has an underscore, but this value is sent in a query string value without an underscore via AJAX */
			if( ! Request::i()->lastSeenID or ! Member::loggedIn()->member_id )
			{
				Output::i()->json( array( 'count' => 0 ) );
			}

			$lastComment = $commentClass::load( Request::i()->lastSeenID );
			$authorColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'];

            $where = array();

            /* Ignored Users */
            if( ! Member::loggedIn()->members_bitoptions['has_no_ignored_users'] )
            {
                $ignored = iterator_to_array( Db::i()->select( 'ignore_ignore_id', 'core_ignored_users', array( 'ignore_owner_id=? and ignore_messages=?', Member::loggedIn()->member_id, 1 ) ) );
                if( count( $ignored ) )
                {
                    $where[] = array( Db::i()->in( $authorColumn, $ignored, TRUE ) );
                }
            }

            /* We will fetch up to 200 comments - anything over this is excessive */
			$newComments = $item->comments( 200, 0, 'date', 'asc', NULL, NULL, DateTime::ts( $lastComment->$commentDateColumn ), array_merge( $where, array( "{$authorColumn} != " . Member::loggedIn()->member_id ) ) );
			
			/* Get the next to last comment for the spillover link as PMs do not support read markers */
			$nextLastComment = NULL;
			if( count( $newComments ) )
			{
				$nextLastComment = reset( $newComments );
			}
			
			if ( Request::i()->type === 'count' )
			{
				$data = array(
					'totalNewCount'	=> count( $newComments ),
					'count'			=> count( $newComments ), 	/* This is here for legacy purposes only */
					'perPage'		=> $perPage,
					'totalCount'	=> $item->mapped( 'num_comments' ),
					'title'			=> $item->mapped( 'title' ) ,
					'spillOverUrl'	=> ( $nextLastComment ) ? $nextLastComment->url() : $item->url(),
				);

				if( $data['count'] === 1 ){
					$itemData = reset( $newComments );
					$author = $itemData->author();

					$data['name'] = htmlspecialchars( $author->name, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE );
					$data['photo'] = (string) $author->photo;
				}

				Output::i()->json( $data );
			}
			else
			{
				$output = array();
				$lastId = 0;
				$showing = intval( Request::i()->showing ?: 0 );
				foreach ( $newComments as $newComment )
				{
					$lastId = ( $newComment->$commentIdColumn > $lastId ) ? $newComment->$commentIdColumn : $lastId;
					if ( ++$showing >= $perPage )
					{
						break;
					}
				}

				/* Only mark as read if we'll be staying on this page, otherwise we'll be marking it read despite the user not having seen everything */
				if ( IPS::classUSesTrait( $class, 'IPS\Content\ReadMarkers' ) AND count( $newComments ) + intval( Request::i()->showing ) <= $perPage )
				{
					$item->markRead();
				}
			}
			
			Output::i()->json( array(
				'content' 		=> $output ?? null,
				'id' 			=> $lastId ?? null,
				'totalCount' 	=> $item->mapped( 'num_comments' ),
				'totalNewCount' => count( $newComments ),
				'perPage' 		=> $perPage,
				'spillOverUrl' 	=> $nextLastComment ? $nextLastComment->url() : $item->url()
			) );
		}
		catch ( Exception $e )
		{
			Output::i()->json( $e->getMessage(), 500 );
		}
	}
	
	/**
	 * Edit Item
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			// We check if the form has been submitted to prevent the user loosing their content
			if ( isset( Request::i()->form_submitted ) )
			{
				if ( ! $item->couldEdit() )
				{
					throw new OutOfRangeException;
				}
			}
			else
			{
				if ( ! $item->canEdit() )
				{
					throw new OutOfRangeException;
				}
			}
			
			$container = NULL;
			try
			{
				$container = $item->container();
			}
			catch ( BadMethodCallException $e ) {}

			/* Build the form */
			$form = $item->buildEditForm();

			if ( $values = $form->values() )
			{
				/* @var $databaseColumnMap array */
				$titleField = $item::$databaseColumnMap['title'];
				$oldTitle = $item->$titleField;

				if ( $item->canEdit() )
				{
                    $item->processBeforeEdit( $values );
                    $item->processForm( $values );
					if ( isset( $item::$databaseColumnMap['updated'] ) )
					{
						$column = $item::$databaseColumnMap['updated'];
						$item->$column = time();
					}
	
					if ( isset( $item::$databaseColumnMap['date'] ) and isset( $values[ $item::$formLangPrefix . 'date' ] ) )
					{
						$column = $item::$databaseColumnMap['date'];
	
						if ( $values[ $item::$formLangPrefix . 'date' ] instanceof DateTime )
						{
							$item->$column = $values[ $item::$formLangPrefix . 'date' ]->getTimestamp();
						}
						else
						{
							$item->$column = time();
						}
					}

					$item->save();
					$item->processAfterEdit( $values );

					/* Moderator log */
					$toLog = array( $item::$title => TRUE, $item->url()->__toString() => FALSE, $item::$title => TRUE, $item->mapped( 'title' ) => FALSE );
					
					if ( $oldTitle != $item->$titleField )
					{
						array_push( $toLog, $oldTitle ); 
					}
					
					Session::i()->modLog( 'modlog__item_edit', $toLog, $item );

					Output::i()->redirect( $item->url() );
				}
				else
				{
					$form->error = Member::loggedIn()->language()->addToStack( 'edit_no_perm_err' );
				}
			}
			
			$this->_setBreadcrumbAndTitle( $item );

			if( Request::i()->isAjax() )
			{
				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
			}
			else
			{
				Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->editContentForm( Member::loggedIn()->language()->addToStack( 'edit_title', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $item::$title ) ) ) ), $this->getEditForm( $form ), $container );
			}
		}
		catch ( Exception $e )
		{
			Log::debug( $e, 'content_debug' );
			Output::i()->error( 'edit_no_perm_err', '2S136/E', 404, '' );
		}
	}

	/**
	 * Return the form for editing. Abstracted so controllers can define a custom template if desired.
	 *
	 * @param	Form	$form	The form
	 * @return	string
	 */
	protected function getEditForm( Form $form ): string
	{
		return $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Edit item's tags
	 *
	 * @return	void
	 */
	protected function editTags(): void
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			/* Make sure tagging is supported in this class */
			if ( !IPS::classUsesTrait( $class, 'IPS\Content\Taggable' ) )
			{
				throw new DomainException;
			}

			/* Get the container as we'll need it for permission checking */
			$container = NULL;
			try
			{
				$container = $item->container();
			}
			catch ( BadMethodCallException $e ) {}

			/* Make sure we can edit and tag */
			if ( !$item->canEdit() OR ( !$item::canTag( NULL, $container )  AND !count( $item->tags() ) ) )
			{
				throw new OutOfRangeException;
			}

			/* If the tag form field is generated, create the form, otherwise throw an exception */
			if( $tagsField = $item::tagsFormField( $item, $container, TRUE ) )
			{
				$form = new Form( 'form', Member::loggedIn()->language()->checkKeyExists( $item::$formLangPrefix . '_save' ) ? $item::$formLangPrefix . '_save' : 'save' );
				$form->class = 'ipsForm--vertical ipsForm--fullWidth ipsForm--edit-tags';

				$form->add( $tagsField );
			}
			else
			{
				throw new DomainException;
			}

			/* If we are simply removing a tag, do it */
			if( isset( Request::i()->removeTag ) )
			{
				/* Get our current tags */
				$existingTags = ( $item->prefix() ? array_merge( array( 'prefix' => $item->prefix() ), $item->tags() ) : $item->tags() );

				/* Remove the tag from the array. Preserve index since it could be 'prefix' which we need to remember. */
				foreach( $existingTags as $index => $tag )
				{
					if( $tag == Request::i()->removeTag )
					{
						unset( $existingTags[ $index ] );
						break;
					}
				}

				/* Now set the tags */
				$name = $tagsField->name;
				Request::i()->$name = implode( "\n", $existingTags );

				if( isset( $existingTags['prefix'] ) )
				{
					$prefix = $tagsField->name . '_prefix';
					Request::i()->$prefix = $existingTags['prefix'];

					$prefix = $tagsField->name . '_freechoice_prefix';
					Request::i()->$prefix = 'on';
				}

				$tagsField->setValue( FALSE, TRUE );

				$submittedKey = $form->id . "_submitted";
				Request::i()->$submittedKey = 1;
			}

			/* Process the values */
			if ( $values = $form->values() )
			{
				$item->setTags( $values[ $item::$formLangPrefix . 'tags' ] ?: array() );

				/* Update the search index */
				if( SearchContent::isSearchable( $item ) )
				{
					if( $item::$firstCommentRequired )
					{
						Index::i()->index( $item->firstComment() );
					}
					else
					{
						Index::i()->index( $item );
					}
				}

				if ( Request::i()->isAjax() )
				{
					/* Build html we'll need to display */
					$toReturn = array( 'tags' => '', 'prefix' => '' );

					foreach ( $item->tags() as $tag )
					{
						$toReturn['tags'] .= Theme::i()->getTemplate( 'global', 'core' )->tag( $tag, $item->url() );
					}

					if( $item->prefix() )
					{
						$toReturn['prefix'] = Theme::i()->getTemplate( 'global', 'core' )->tag( $item->prefix(), $item->url(), 'ipsTags__item--prefix' );
					}

					Output::i()->json( $toReturn );
				}
				else
				{
					Output::i()->redirect( $item->url() );
				}				
			}

			/* If we tried to delete a tag and this is an AJAX request, just return the error. If it's not AJAX, we
				can just let the regular form output which will show the error. */
			if( $tagsField->error AND Request::i()->isAjax() )
			{
				Output::i()->error( $tagsField->error, '1S136/13', 403, '' );
			}
			
			/* Show the output */
			$this->_setBreadcrumbAndTitle( $item );
			Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->editTagsForm( $form );
		}
		catch ( DomainException $e )
		{
			Output::i()->error( 'edit_no_tags_defined', '2S131/3', 403, 'edit_no_tags_defined_admin' );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'edit_no_perm_err', '2S136/12', 403, '' );
		}
	}
	
	/**
	 * Quick Edit Title
	 *
	 * @return	void
	 */
	public function ajaxEditTitle(): void
	{
		try
		{
			Session::i()->csrfCheck();
			
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			if ( !$item->canEditTitle() )
			{
				throw new RuntimeException;
			}
			
			$oldTitle = $item->mapped( 'title' );

			$maxLength	= Settings::i()->max_title_length ?: 255;
			/* @var $databaseColumnMap array */
			$titleField	= $item::$databaseColumnMap['title'];

			if( strlen( Request::i()->newTitle ) > $maxLength )
			{
				throw new LengthException( Member::loggedIn()->language()->addToStack( 'form_maxlength', FALSE, array( 'pluralize' => array( $maxLength ) ) ) );
			}
			elseif( !trim( Request::i()->newTitle ) )
			{
				throw new InvalidArgumentException('form_required');
			}

			$newTitle = new Text( 'newTitle', Request::i()->newTitle );

			if( $newTitle->validate() !== TRUE )
			{
				throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack( 'form_tags_not_allowed', FALSE, array( 'sprintf' => array( Request::i()->newTitle ) ) ) );
			}

			$item->$titleField = $newTitle->value;
			$idField = $item::$databaseColumnId;
			$item->save();

			/* rebuild the container last item data */
			if ( isset( $item::$containerNodeClass ) )
			{
				$item->container()->setLastComment();
				$item->container()->save();
			}

			if( SearchContent::isSearchable( $item ) )
			{
				Index::i()->index( $item );
			}
			
			/* Only add the mod log entry if we actually changed the title */
			if( $item->$titleField !== $oldTitle )
			{
				Session::i()->modLog( 'modlog__comment_edit_title', array( (string) $item->url() => FALSE, $item->$titleField => FALSE, $oldTitle => FALSE ), $item );
			}
			
			Output::i()->json( $item->$titleField );
		}
		catch( LogicException $e )
		{
			Output::i()->error( $e->getMessage(), '2S136/1M', 403, '' );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2S136/11', 404, '' );
		}
	}
	
	/**
	 * Set the breadcrumb and title
	 *
	 * @param Item $item	Content item
	 * @param	bool				$link	Link the content item element in the breadcrumb
	 * @return	void
	 */
	protected function _setBreadcrumbAndTitle( Item $item, bool $link=TRUE ): void
	{
		$container	= NULL;
		try
		{
			$container = $item->container();
			if ( IPS::classUsesTrait( $container, 'IPS\Content\ClubContainer' ) and $club = $container->club() )
			{
				FrontNavigation::$clubTabActive = TRUE;
				Output::i()->breadcrumb = array();
				Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
				Output::i()->breadcrumb[] = array( $club->url(), $club->name );
				Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
				
				if ( Settings::i()->clubs_header == 'sidebar' )
				{
					Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $container, 'sidebar' );
				}
			}
			else
			{
				foreach ( $container->parents() as $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
			}
		}
		catch ( Exception $e ) { }
		Output::i()->breadcrumb[] = array( $link ? $item->url() : NULL, $item->mapped( 'title' ) );

		$title = ( isset( Request::i()->page ) and Request::i()->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $item->mapped( 'title' ), Request::i()->page ) ) ) : $item->mapped( 'title' );
		Output::i()->title = $container ? ( $title . ' - ' . $container->_title ) : $title;
	}
	
	/**
	 * Toggle a poll status
	 *
	 * @return void
	 */
	protected function pollStatus(): void
	{
		try
		{
			Session::i()->csrfCheck();
						
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			
			if ( !IPS::classUsesTrait( $class, 'IPS\Content\Polls' ) )
			{
				throw new DomainException;
			}
			
			if ( $poll = $item->getPoll() )
			{
				if ( !$poll->canClose() )
				{
					Output::i()->error( 'no_module_permission', '2S136/Z', 403, '' );
				}

				$newStatus = ( Request::i()->value == 1 ? 0 : 1 );
				$poll->poll_closed = $newStatus;
				$redirectMessage = $newStatus == 0 ? 'poll_status_opened' : 'poll_status_closed';

				/* If opening the poll (after it has closed) remove the auto-close date */
				if( !$newStatus and ( $poll->poll_close_date instanceof DateTime ) )
				{
					$poll->poll_close_date = -1;
					$redirectMessage .= '_no_date';
				}

				$poll->save();

				$type = $poll->poll_closed ? 'closed' : 'opened';
				Session::i()->modLog( 'modlog__poll_' . $type, array( $item->url()->__toString() => FALSE, $item->mapped( 'title' ) => FALSE ), $item );

				Output::i()->redirect( $item->url(), $redirectMessage );
			}
			else
			{
				throw new UnderflowException;
			}
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2S136/Y', 404, '' );
		}
	}
	
	/**
	 * Moderate
	 *
	 * @return	void
	 */
	protected function moderate(): void
	{
		try
		{
			Session::i()->csrfCheck();
						
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			/* @var Item $item */
			if ( $item::$hideLogKey and Request::i()->action === 'hide' )
			{
				/* If this is an AJAX request, and we're coming from the approval queue, just do it. */
				if ( Request::i()->isAjax() AND isset( Request::i()->_fromApproval ) )
				{
					$item->modAction( Request::i()->action );
					Output::i()->json( 'OK' );
				}
				
				$this->_setBreadcrumbAndTitle( $item );
				
				$form = new Form;
				$form->add( new Text( 'hide_reason' ) );
				$this->moderationAlertField($form, $item);
				if ( $values = $form->values() )
				{
					$item->modAction( Request::i()->action, NULL, $values['hide_reason'] );

					if( isset( $values['moderation_alert_content']) AND $values['moderation_alert_content'])
					{
						$this->sendModerationAlert($values, $item);
					}
				}
				else
				{
					Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
					return;
				}
			}
			else if ( Request::i()->action === 'delete' AND isset( Request::i()->immediate ) )
			{
				$item->modAction( Request::i()->action, NULL, NULL, TRUE );
			}
			else
			{
				if( Request::i()->action === 'lock' )
				{
					if ( Member::loggedIn()->modPermission('can_manage_alerts') AND $item->author()->member_id )
					{
						$form = new Form;
						$this->moderationAlertField($form, $item);
						$this->_setBreadcrumbAndTitle( $item );

						if ( $values = $form->values() )
						{
							if( isset( $values['moderation_alert_content']) AND $values['moderation_alert_content'])
							{
								$this->sendModerationAlert($values, $item);
							}
						}
						else
						{
							Output::i()->bypassCsrfKeyCheck = TRUE;
							Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
							return;
						}
					}

				}

				$item->modAction( Request::i()->action );
			}
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( 'OK' );
			}
			else
			{
				if( Request::i()->action == 'delete' )
				{
					try
					{
						if( Member::loggedIn()->modPermission( 'can_view_reports' ) AND isset( Request::i()->_report ) )
						{
							try
							{
								$report = Report::loadAndCheckPerms( Request::i()->_report );
								Output::i()->redirect( $report->url() );
							}
							catch( OutOfRangeException $e )
							{
								Output::i()->redirect( $item->container()->url() );
							}
						}
						else
						{
							Output::i()->redirect( $item->container()->url() );
						}
					}
					catch( BadMethodCallException $e )
					{
						/* We could be deleting something from a report, in which case we can go back to the report */
						if( Member::loggedIn()->canAccessReportCenter() AND isset(  Request::i()->_report ) )
						{
							Output::i()->redirect( Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports&action=view&id=' . Request::i()->_report , 'front', 'modcp_report' ) );
						}
						else
						{
							/* Generic fallback in case we delete something that doesn't have a container */
							Output::i()->redirect( Url::internal('') );
						}
					}
				}
				else
				{
					Output::i()->redirect( $item->url(), 'mod_confirm_' . Request::i()->action );
				}
			}
		}
		catch( InvalidArgumentException $e )
		{
			Output::i()->error( 'mod_error_invalid_action', '3S136/1A', 403, 'mod_error_invalid_action_admin' );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/1', 404, '' );
		}
	}
	
	/**
	 * Move
	 *
	 * @return	void
	 */
	protected function move(): void
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			if ( !$item->canMove() )
			{
				throw new DomainException;
			}

			$container = $item->container();
			
			$form = new Form( 'form', Member::loggedIn()->language()->addToStack( 'move_send_to_container', FALSE, array( 'sprintf' => $container->_title ) ), NULL, array( 'data-bypassValidation' => true ) );
			$form->actionButtons[] = Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( Member::loggedIn()->language()->addToStack( 'move_send_to_item', FALSE, array( 'sprintf' => $item->definiteArticle() ) ), 'submit', null, 'ipsButton ipsButton--text', array( 'tabindex' => '3', 'accesskey' => 'i', 'value' => 'item', 'name' => 'returnto' ) );
			$form->class = 'ipsForm--vertical ipsForm--move';
			$form->add( new Node( 'move_to', NULL, TRUE, array(
				'class'				=> get_class( $item->container() ),
				'permissionCheck'	=> function( $node ) use ( $item )
				{
					if ( $node->id != $item->container()->id )
					{
						try
						{
							/* If the item is in a club, only allow moving to other clubs that you moderate */
							if ( IPS::classUsesTrait( $item->container(), 'IPS\Content\ClubContainer' ) and $item->container()->club()  )
							{
								return $item::modPermission( 'move', Member::loggedIn(), $node ) and $node->can( 'add' ) ;
							}
							
							if ( $node->can( 'add' ) )
							{
								return true;
							}
						}
						catch( OutOfBoundsException $e ) { }
					}
					
					return false;
				},
				'clubs'	=> TRUE
			) ) );

			$this->moderationAlertField( $form, $item);
			
			if ( isset( $class::$databaseColumnMap['moved_to'] ) )
			{
				$form->add( new Checkbox( 'move_keep_link' ) );
				
				if ( Settings::i()->topic_redirect_prune )
				{
					Member::loggedIn()->language()->words['move_keep_link_desc'] = Member::loggedIn()->language()->addToStack( '_move_keep_link_desc', FALSE, array( 'pluralize' => array( Settings::i()->topic_redirect_prune ) ) );
				}
			}

			if ( $values = $form->values() )
			{
				if ( $values['move_to'] === NULL OR !$values['move_to']->can( 'add' ) OR $values['move_to']->id == $item->container()->id )
				{
					Output::i()->error( 'node_move_invalid', '1S136/L', 403, '' );
				}

				/* If this item is read, we need to re-mark it as such after moving */
				if( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) )
				{
					$unread = $item->unread();
				}

				$item->move( $values['move_to'], $values['move_keep_link'] ?? FALSE );

				/* Mark it as read */
				if( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) and $unread == 0 )
				{
					$item->markRead( NULL, NULL, NULL, TRUE );
				}
				if( isset( $values['moderation_alert_content']) AND $values['moderation_alert_content'] )
				{
					$this->sendModerationAlert($values, $item);
				}

				Session::i()->modLog( 'modlog__action_move', array( $item::$title => TRUE, $item->url()->__toString() => FALSE, $item->mapped( 'title' ) ?: ( method_exists( $item, 'item' ) ? $item->item()->mapped( 'title' ) : NULL ) => FALSE ),  $item );

				Output::i()->redirect( ( isset( Request::i()->returnto ) AND Request::i()->returnto == 'item' ) ? $item->url() : $container->url() );
			}
			
			$this->_setBreadcrumbAndTitle( $item );
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'move_item', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title ) ) ) );
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
			
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2S136/D', 403, '' );
		}
	}
	
	/**
	 * Merge
	 *
	 * @return	void
	 */
	protected function merge(): void
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			if ( !$item->canMerge() )
			{
				throw new DomainException;
			}

			$form = $item->mergeForm();

			if ( $values = $form->values() )
			{
				$target = $class::loadFromUrl( $values['merge_with'] );
				if ( !$target->canView() )
				{
					throw new DomainException;
				}

				$item->mergeIn( array( $target ), $values['move_keep_link'] ?? FALSE );
				Output::i()->redirect( $item->url() );
			}
			
			$this->_setBreadcrumbAndTitle( $item );
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'merge_item', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title ) ) ) );
				
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2S136/G', 403, '' );
		}
	}
	
	/**
	 * Delete a report
	 *
	 * @return	void
	 */
	protected function deleteReport(): void
	{
		Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$report = Db::i()->select( '*', 'core_rc_reports', array( 'id=? AND report_by=? AND date_reported > ?', Request::i()->cid, Member::loggedIn()->member_id, time() - ( Settings::i()->automoderation_report_again_mins * 60 ) ) )->first();
		}
		catch( UnderflowException $e )
		{
			Output::i()->error( 'automoderation_cannot_find_report', '2S136/1G', 404, '' );
		}
		
		try
		{
			$index = Report::load( $report['rid'] );
		}
		catch( OutofRangeException $e )
		{
			Output::i()->error( 'automoderation_cannot_find_report', '2S136/1H', 404, '' );
		}
		
		$class = $index->class;
		
		Db::i()->delete( 'core_rc_reports', array( 'id=?', Request::i()->cid ) );
		
		/* Recalculate, we may have dropped below the threshold needed to hide a thing */
		$index->runAutomaticModeration();
		
		Output::i()->redirect( $class::load( $index->content_id )->url(), 'automoderation_deleted' );
	}

	/**
	 * View Edit Log of the Item
	 *
	 * @return	void
	 * @throws	LogicException
	 */
	public function editlog(): void
	{
		/* Permission check */
		if ( Settings::i()->edit_log != 2 or ( !Settings::i()->edit_log_public and !Member::loggedIn()->modPermission( 'can_view_editlog' ) ) )
		{
			throw new DomainException;
		}

		try
		{
			/* Init */
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			$this->_setBreadcrumbAndTitle( $item );

			/* Even if guests can see the changelog, we don't want this being indexed in Google */
			Output::i()->metaTags['robots'] = 'noindex';
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/1Q', 404, '' );
		}

		$idColumn = $class::$databaseColumnId;
		$where = array( array( 'class=? AND comment_id=?', $class, $item->$idColumn ) );
		if ( !Member::loggedIn()->modPermission( 'can_view_editlog' ) )
		{
			$where[] = array( '`member`=? AND public=1', $item->author()->member_id );
		}

		$table = new TableDb( 'core_edit_history', $item->url()->setQueryString( array( 'do' => 'editlog' ) ), $where );
		$table->sortBy = 'time';
		$table->sortDirection = 'desc';
		$table->limit = 10;
		$table->tableTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'commentEditHistoryTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'commentEditHistoryRows' );
		$table->parsers = array(
			'new' => function( $val )
			{
				return $val;
			},
			'old' => function( $val )
			{
				return $val;
			}
		);
		$table->extra = $item;

		$pageParam = $table->getPaginationKey();
		if( Request::i()->isAjax() AND isset( Request::i()->$pageParam ) )
		{
			Output::i()->sendOutput( (string) $table );
		}

		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'edit_history_title' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'edit_history_title' );
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'front' )->commentEditHistory( (string) $table, $item );
	}
	
	/**
	 * Report Item
	 *
	 * @return	void
	 */
	protected function report(): void
	{
		try
		{
			/* Init */
			$class = static::$contentModel;
			$commentClass = $class::$commentClass;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			
			/* Permission check */
			$canReport = $item->canReport();
			if ( $canReport !== TRUE AND !( $canReport == 'report_err_already_reported' AND Settings::i()->automoderation_enabled ) )
			{
				Output::i()->error( $canReport, '2S136/6', 403, '' );
			}
			
			/* Show form */
			$form = new Form( NULL, 'report_submit' );
			$form->class = 'ipsForm--vertical ipsForm--report';
			$idColumn = $class::$databaseColumnId;
			
			/* As we group by user id to determine if max points have been reached, guests cannot contribute to counts */
			if ( Member::loggedIn()->member_id and Settings::i()->automoderation_enabled )
			{
				/* Has this member already reported this in the past 24 hours */
				try
				{
					$index = Report::loadByClassAndId( get_class( $item ), $item->$idColumn );
					$report = Db::i()->select( '*', 'core_rc_reports', array( 'rid=? and report_by=? and date_reported > ?', $index->id, Member::loggedIn()->member_id, time() - ( Settings::i()->automoderation_report_again_mins * 60 ) ) )->first();
					
					Output::i()->output = Theme::i()->getTemplate( 'system', 'core' )->reportedAlready( $index, $report, $item );
					return;
				}
				catch( Exception $e ) { }
				
				$options = array( Report::TYPE_MESSAGE => Member::loggedIn()->language()->addToStack('report_message_item') );
				foreach( Types::roots() as $type )
				{
					$options[ $type->id ] = $type->_title;
				}
				
				$form->add( new Radio( 'report_type', NULL, FALSE, array( 'options' => $options ) ) );
			}
			
			$form->add( new Editor( 'report_message', NULL, FALSE, array( 'app' => 'core', 'key' => 'Reports', 'autoSaveKey' => "report-{$class::$application}-{$class::$module}-{$item->$idColumn}", 'minimize' => Request::i()->isAjax() ? 'report_message_placeholder' : NULL ) ) );
			if ( !Request::i()->isAjax() )
			{
				Member::loggedIn()->language()->words['report_message'] = Member::loggedIn()->language()->addToStack('report_message_fallback');
			}
			
			if( !Member::loggedIn()->member_id )
			{
				$form->add( new Captcha );
			}
			if ( $values = $form->values() )
			{
				$guestDetails = [];
				if ( ! Member::loggedIn()->member_id and Settings::i()->report_capture_guest_details )
				{
					$guestDetails = [ 'name' => $values['report_guest_name'], 'email' => $values['report_guest_email'] ];

					/* Has this guest already reported this? */
					try
					{
						$index = Db::i()->select( '*', 'core_rc_index', [ 'class=? and content_id=?', \get_class( $item ), $item->$idColumn ] )->first();
						$report = Db::i()->select( '*', 'core_rc_reports', [ 'rid=? and guest_email=?', $index['id'], $values['report_guest_email'] ] )->first();

						Output::i()->error( 'report_err_already_reported', '2S136/11', 403, '' );
					}
					catch( \UnderflowException )
					{
						/* The query failed, so this is a new report */
					}
				}

				$report = $item->report( $values['report_message'], ( isset( $values['report_type'] ) ) ? $values['report_type'] : 0, null, $guestDetails );
				File::claimAttachments( "report-{$class::$application}-{$class::$module}-{$item->$idColumn}", $report->id );
				if ( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( Member::loggedIn()->language()->addToStack( 'report_submit_success' ) );
				}
				else
				{
					Output::i()->redirect( $item->url(), 'report_submit_success' );
				}
			}

			$this->_setBreadcrumbAndTitle( $item );

			/* Even if guests can report something, we don't want the report form indexed in Google */
			Output::i()->metaTags['robots'] = 'noindex';

			Output::i()->output = Request::i()->isAjax() ? $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) : Theme::i()->getTemplate( 'system', 'core' )->reportForm( $form );
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/7', 404, '' );
		}
	}
	
	/**
	 * Get Next Unread Item
	 *
	 * @return	void
	 */
	protected function nextUnread(): void
	{
		try
		{
			$class		= static::$contentModel;
			$item		= $class::loadAndCheckPerms( Request::i()->id );
			$next		= $item->nextUnread();

			if ( $next instanceof Item)
			{
				Output::i()->redirect( $next->url()->setQueryString( array( 'do' => 'getNewComment' ) ) );
			}
		}
		catch( Exception $e )
		{
			Output::i()->error( 'next_unread_not_found', '2S136/J', 404, '' );
		}
	}
	
	/**
	 * React
	 *
	 * @return	void
	 */
	protected function react(): void
	{
		if( !IPS::classUsesTrait( static::$contentModel, 'IPS\Content\Reactable' ) )
		{
			Output::i()->error( 'node_error', '2S136/1N', 404, '' );
		}

		try
		{
			Session::i()->csrfCheck();
			
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$reaction = Reaction::load( Request::i()->reaction );
			$item->react( $reaction );

			/* Send a realtime event so that users on this page immediately see the reaction */
			if ( Bridge::i()->featureIsEnabled( 'realtime' ) )
			{
				$blurb = ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $item, true );
				Lang::load( Lang::defaultLanguage() )->parseOutputForDisplay( $blurb );
				Bridge::i()->publishRealtimeEvent( 'reaction-count', [
					'memberReacted' => Member::loggedIn()->member_id,
					'content' => $item->getDataLayerProperties(),
					'count' => count( $item->reactions() ),
					'score' => $item->reactionCount(),
					'blurb' => $blurb,
					'eventType' => 'item.react'
				]);
			}

			if ( Request::i()->isAjax() )
			{
				$output = array(
					'status' => 'ok',
					'count' => count( $item->reactions() ),
					'score' => $item->reactionCount(),
					'blurb' => ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $item )
				);

				if ( Settings::i()->core_datalayer_enabled )
				{
					$output['datalayer'] = array_replace( $item->getDataLayerProperties(), ['reaction_type' => $reaction->_title] );
				}

				Output::i()->json( $output );
			}
			else
			{
				if ( Settings::i()->core_datalayer_enabled )
				{
					DataLayer::i()->addEvent( 'content_react', array_replace( $item->getDataLayerProperties(), [ 'reaction_type' => $reaction->_title ] ) );
				}

				Output::i()->redirect( $item->url() );
			}
		}
		catch( OutOfRangeException | DomainException $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'error' => Member::loggedIn()->language()->addToStack( $e->getMessage() ) ), 403 );
			}
			else
			{
				Output::i()->error( $e->getMessage(), '1S136/14', 403, '' );
			}
		}
	}
	
	/**
	 * Unreact
	 *
	 * @return	void
	 */
	protected function unreact(): void
	{
		if( !IPS::classUsesTrait( static::$contentModel, 'IPS\Content\Reactable' ) )
		{
			Output::i()->error( 'node_error', '2S136/1O', 404, '' );
		}

		try
		{
			Session::i()->csrfCheck();

			$member = ( isset( Request::i()->member ) and Member::loggedIn()->modPermission('can_remove_reactions') ) ? Member::load( Request::i()->member ) : Member::loggedIn();
			
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$item->removeReaction( $member );

			/* Send a realtime event so that users on this page immediately see the reaction */
			if ( Bridge::i()->featureIsEnabled( 'realtime' ) )
			{
				$blurb = ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $item, true );
				Lang::load( Lang::defaultLanguage() )->parseOutputForDisplay( $blurb );
				Bridge::i()->publishRealtimeEvent( 'reaction-count', [
					'memberReacted' => Member::loggedIn()->member_id,
					'content' => $item->getDataLayerProperties(),
					'count' => count( $item->reactions() ),
					'score' => $item->reactionCount(),
					'blurb' => $blurb,
					'eventType' => 'item.unreact'
				]);
			}

			/* Log */
			if( $member->member_id !== Member::loggedIn()->member_id )
			{
				Session::i()->modLog( 'modlog__reaction_delete', array( $member->url()->__toString() => FALSE, $member->name => FALSE, $item::$title => TRUE, $item->url()->__toString() => FALSE, $item->mapped( 'title' ) => FALSE ), $item );
			}

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array(
					'status' => 'ok',
					'count' => count( $item->reactions() ),
					'score' => $item->reactionCount(),
					'blurb' => ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $item )
				));
			}
			else
			{
				Output::i()->redirect( $item->url() );
			}
		}
		catch( DomainException $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'error' => Member::loggedIn()->language()->addToStack( $e->getMessage() ) ), 403 );
			}
			else
			{
				Output::i()->error( $e->getMessage(), '1S136/15', 403, '' );
			}
		}
	}

	/**
	 * Get the reaction blurb for the content item
	 *
	 * @return void
	 */
	protected function reactionBlurb(): void
	{
		if ( !IPS::classUsesTrait( static::$contentModel, 'IPS\Content\Reactable' ) )
		{
			Output::i()->json( ['error' => 'reactions_not_available'], 404 );
		}
		$class = static::$contentModel;
		try
		{
			$item = $class::loadAndCheckPerms( Request::i()->id );
			Output::i()->json( [ 'blurb' => ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $item ) ] );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->json( [ 'error' => 'no_permission' ], 403 );
		}
	}

	/**
	 * Show Reactions
	 *
	 * @return    void
	 * @throws Exception
	 */
	protected function showReactions(): void
	{
		if( !IPS::classUsesTrait( static::$contentModel, 'IPS\Content\Reactable' ) )
		{
			Output::i()->error( 'node_error', '2S136/1P', 404, '' );
		}
		
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			
			if ( Request::i()->isAjax() and isset( Request::i()->tooltip ) and isset( Request::i()->reaction ) )
			{
				$reaction = Reaction::load( Request::i()->reaction );
				
				$numberToShowInPopup = 10;
				$where = $item->getReactionWhereClause( $reaction );
				$total = Db::i()->select( 'COUNT(*)', 'core_reputation_index', $where )->join( 'core_reactions', 'reaction=reaction_id' )->first();
				$names = Db::i()->select( 'name', 'core_reputation_index', $where, 'rep_date DESC', $numberToShowInPopup )->join( 'core_reactions', 'reaction=reaction_id' )->join( 'core_members', 'core_reputation_index.member_id=core_members.member_id' );
				
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'front' )->reactionTooltip( $reaction, $total ? $names : [], ( $total > $numberToShowInPopup ) ? ( $total - $numberToShowInPopup ) : 0 ) );
			}
			else
			{		
				$blurb = $item->reactBlurb();
				
				$this->_setBreadcrumbAndTitle( $item );
				
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'see_who_reacted' ) . ' - ' . Output::i()->title;
				
				$tabs = array();
				$tabs['all'] = array( 'title' => Member::loggedIn()->language()->addToStack('all'), 'count' => count( $item->reactions() ) );
				foreach(Reaction::roots() AS $reaction )
				{
					if ( $reaction->_enabled !== FALSE )
					{
						$tabs[ $reaction->id ] = array( 'title' => $reaction->_title, 'icon' => $reaction->_icon, 'count' => $blurb[$reaction->id] ?? 0 );
					}
				}
				
				$activeTab = 'all';
				if ( isset( Request::i()->reaction ) )
				{
					$activeTab = Request::i()->reaction;
				}
				
				$url = $item->url('showReactions');
				$url = $url->setQueryString( 'changed', 1 );

				if ( isset( Request::i()->item ) )
				{
					$url = $url->setQueryString( 'item', Request::i()->item );
				}
				
				if ( $activeTab !== 'all' )
				{
					$url = $url->setQueryString( 'reaction', $activeTab );
				}
	
				Output::i()->metaTags['robots'] = 'noindex';
				
				if ( Reaction::isLikeMode() or ( Request::i()->isAjax() AND isset( Request::i()->changed ) ) )
				{
					Output::i()->output = $item->reactionTable( $activeTab !== 'all' ? $activeTab : NULL, $url, 'reaction', FALSE );
				}
				else
				{
					Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'front' )->reactionTabs( $tabs, $activeTab, $item->reactionTable( $activeTab !== 'all' ? $activeTab : NULL ), $url, 'reaction', FALSE );
				}
			}
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/18', 404, '' );
		}
		catch( DomainException $e )
		{
			Output::i()->error( 'no_module_permission', '2S136/19', 403, '' );
		}
	}
	
	/**
	 * Moderator Log
	 *
	 * @return	void
	 */
	protected function modLog(): void
	{
		if( !Member::loggedIn()->modPermission( 'can_view_moderation_log' ) )
		{
			Output::i()->error( 'no_module_permission', '2S136/1F', 403, '' );
		}
		
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			/* Set up some stuff so we're not doing too much logic / assignment in the template */
			$modlog = $item->moderationTable();

			Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'front' )->modLog( $item, $modlog );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/T', 404, '' );
		}
		catch( DomainException $e )
		{
			Output::i()->error( 'no_module_permission', '2S136/U', 403, '' );
		}
	}

	/**
	 * Moderation Log
	 *
	 * @return	void
	 */
	protected function analytics() : void
	{
		if( !Member::loggedIn()->modPermission( 'can_view_moderation_log' ) )
		{
			Output::i()->error( 'no_module_permission', '2S136/1F', 403, '' );
		}

		try
		{
			/* @var Item $class */
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			/* Set up some stuff so we're not doing too much logic / assignment in the template */
			$lastCommenter = $members = $busy = $reacted = $images = NULL;
			try
			{
				$lastCommenter = $item->lastCommenter();
				if ( !$lastCommenter->member_id )
				{
					$lastCommenter = NULL;
				}
			}
			catch( BadMethodCallException $e ) { }

			if ( IPS::classUsesTrait( $item, 'IPS\Content\Statistics' ) )
			{
				$members	= $item->topPosters();
				$busy		= $item->popularDays();
				$reacted	= $item->topReactedPosts();
				$images		= $item->imageAttachments();
			}

			$commentCount = $item->commentCount();
			if( $class::$firstCommentRequired )
			{
				$commentCount--;
			}

			/* Set navigation */
			$this->_setBreadcrumbAndTitle( $item );

			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'analytics_and_stats' ) );
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'front' )->analytics( $item, $lastCommenter, $members, $busy, $reacted, $images, $commentCount );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/T', 404, '' );
		}
		catch( DomainException $e )
		{
			Output::i()->error( 'no_module_permission', '2S136/U', 403, '' );
		}
	}
	
	/**
	 * Go to new comment.
	 *
	 * @return	void
	 */
	public function getNewComment(): void
	{
		try
		{
			$class	= static::$contentModel;
			$item	= $class::loadAndCheckPerms( Request::i()->id );

			$timeLastRead = $item->timeLastRead();

			if ( $timeLastRead instanceof DateTime )
			{
				$comment = NULL;
				if( DateTime::ts( $item->mapped('date') ) < $timeLastRead )
				{
					$comment = $item->comments( 1, NULL, 'date', 'asc', NULL, NULL, $timeLastRead );
				}

				/* If we don't have any unread comments... */
				if ( !$comment and $class::$firstCommentRequired )
				{
					/* If we haven't read the item at all, go there */
					if ( $item->unread() )
					{
						Output::i()->redirect( $item->url() );
					}
					/* Otherwise, go to the last comment */
					else
					{
						$comment = $item->comments( 1, NULL, 'date', 'desc' );
					}
				}

				Output::i()->redirect( $comment ? $comment->url() : $item->url() );
			}
			else
			{
				if ( $item->unread() )
				{
					/* If we do not have a time last read set for this content, fallback to the reset time */
					$resetTimes = Member::loggedIn()->markersResetTimes( $class::$application );

					if ( ( is_array( $resetTimes ) AND array_key_exists( $item->container()->_id, $resetTimes ) ) and $item->mapped('date') < $resetTimes[ $item->container()->_id ] )
					{
						$comment = $item->comments( 1, NULL, 'date', 'asc', NULL, NULL, DateTime::ts( $resetTimes[ $item->container()->_id ] ) );
						
						if ( $class::$firstCommentRequired and $comment->isFirst() )
						{
							Output::i()->redirect( $item->url() );
						}
						
						Output::i()->redirect( $comment ? $comment->url() : $item->url() );
					}
					else
					{
						Output::i()->redirect( $item->url() );
					}
				}
				else
				{
					Output::i()->redirect( $item->url() );
				}
			}
		}
		catch( BadMethodCallException $e )
		{
			Output::i()->error( 'node_error', '2S136/I', 404, '' );
		}
		catch( OutOfRangeException $e )
		{
			$class = static::$contentModel;

			try
			{
				$item = $class::load( Request::i()->id );
				$error = ( !$item->canView() and ( $item->containerWrapper( TRUE ) and method_exists( $item->container(), 'errorMessage' ) ) ) ? $item->container()->errorMessage() : 'node_error';
			}
			catch( OutOfRangeException $e )
			{
				$error = 'node_error';
			}
			
			Output::i()->error( $error, '2S136/V', 403, '' );
		}
		catch( LogicException $e )
		{
			$class = static::$contentModel;

			try
			{
				$item = $class::load( Request::i()->id );
				$error = ( !$item->canView() and ( $item->containerWrapper( TRUE ) and method_exists( $item->container(), 'errorMessage' ) ) ) ? $item->container()->errorMessage() : 'node_error';
			}
			catch( OutOfRangeException $e )
			{
				$error = 'node_error';
			}

			Output::i()->error( $error, '2S136/R', 404, '' );
		}
	}
	
	/**
	 * Go to last comment
	 *
	 * @return	void
	 */
	public function getLastComment(): void
	{
		try
		{
			$class	= static::$contentModel;
			$item	= $class::loadAndCheckPerms( Request::i()->id );
			
			$comment = $item->comments( 1, NULL, 'date', 'desc' );
			
			if ( $comment !== NULL )
			{
				$this->_find( get_class( $comment ), $comment, $item );
			}
			else
			{
				Output::i()->redirect( $item->url() );
			}
		}
		catch( BadMethodCallException $e )
		{
			try
			{
				$item = $class::load( Request::i()->id );
				$error = ( !$item->canView() and ( $item->containerWrapper( TRUE ) and method_exists( $item->container(), 'errorMessage' ) ) ) ? $item->container()->errorMessage() : 'node_error';
			}
			catch( OutOfRangeException $e )
			{
				$error = 'node_error';
			}
			
			Output::i()->error( $error, '2S136/K', 404, '' );
		}
		catch( LogicException $e )
		{
			try
			{
				$item = $class::load( Request::i()->id );
				$error = ( !$item->canView() and ( $item->containerWrapper( TRUE ) and method_exists( $item->container(), 'errorMessage' ) ) ) ? $item->container()->errorMessage() : 'node_error';
			}
			catch( OutOfRangeException $e )
			{
				$error = 'node_error';
			}
			
			Output::i()->error( $error, '2S136/Q', 403, '' );
		}
	}
	
	/**
	 * Go to first comment
	 *
	 * @return	void
	 */
	public function getFirstComment(): void
	{
		try
		{
			$class	= static::$contentModel;
			$item	= $class::loadAndCheckPerms( Request::i()->id );
			
			if ( $class::$firstCommentRequired )
			{
				$comments = $item->comments( 2, NULL, 'date', 'asc' );
				$comment  = array_pop( $comments );
				unset( $comments );
			}
			else
			{
				$comment = $item->comments( 1, NULL, 'date', 'asc' );
			}
			
			if ( $comment !== NULL )
			{
				$this->_find( get_class( $comment ), $comment, $item );
			}
			else
			{
				Output::i()->redirect( $item->url() );
			}
		}
		catch( BadMethodCallException $e )
		{
			Output::i()->error( 'node_error', '2S136/W', 404, '' );
		}
		catch( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/X', 403, '' );
		}
	}
	
	/**
	 * Rate Review as helpful/unhelpful
	 *
	 * @return	void
	 */
	public function rateReview(): void
	{
		try
		{
			Session::i()->csrfCheck();
			
			/* Only logged in members */
			if ( !Member::loggedIn()->member_id )
			{
				throw new DomainException;
			}
			
			/* Init */
			$class = static::$contentModel;
			$reviewClass = $class::$reviewClass;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$review = $reviewClass::load( Request::i()->review );
			
			/* Review authors can't rate their own reviews */
			if ( $review->author()->member_id === Member::loggedIn()->member_id )
			{
				throw new DomainException;
			}
			
			/* Have we already rated? */
			/* @var $databaseColumnMap array */
			$dataColumn = $reviewClass::$databaseColumnMap['votes_data'];
			$votesData = $review->mapped( 'votes_data' ) ? json_decode( $review->mapped( 'votes_data' ), TRUE ) : array();
			if ( array_key_exists( Member::loggedIn()->member_id, $votesData ) )
			{
				Output::i()->error( 'you_have_already_rated', '2S136/A', 403, '' );
			}
			
			/* Add it */
			$votesData[ Member::loggedIn()->member_id ] = intval( Request::i()->helpful );
			if ( Request::i()->helpful )
			{
				$helpful = $reviewClass::$databaseColumnMap['votes_helpful'];
				$review->$helpful++;
			}
			$total = $reviewClass::$databaseColumnMap['votes_total'];
			$review->$total++;
			$review->$dataColumn = json_encode( $votesData );
			$review->save();
			
			/* Boink */
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( $review->html() );
			}
			else
			{
				Output::i()->redirect( $review->url() );
			}
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/9', 404, '' );
		}
	}

	/**
	 * Allow the author of a content item to reply to a review
	 *
	 * @return	void
	 */
	protected function _respond(): void
	{
		try
		{
			/* Init */
			$class = static::$contentModel;
			$reviewClass = $class::$reviewClass;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$review = $reviewClass::loadAndCheckPerms( Request::i()->review );

			/* Are we allowed to respond? */
			$editing = str_starts_with( Request::i()->do, 'edit' );
			if( $editing === TRUE )
			{
				if ( !$review->canEditResponse() )
				{
					throw new DomainException;
				}
			}
			else
			{
				if ( !$review->canRespond() )
				{
					throw new DomainException;
				}
			}
			
			$form = new Form;
			$form->add( new Editor( 'reviewResponse', $editing ? $review->mapped('author_response') : NULL, TRUE, array(
				'app'			=> 'core',
				'key'			=> 'ReviewResponses',
				'autoSaveKey' 	=> 'reviewResponse-' . $class::$application . '/' . $class::$module . '-' . Request::i()->review,
				'attachIds'		=> array( Request::i()->id, Request::i()->review, get_class( $item ) )
			) ) );
			
			if ( $values = $form->values() )
			{
				$review->setResponse( $values['reviewResponse'] );

				/* Claim attachments and clear the editor */
				File::claimAttachments( 'reviewResponse-' . $class::$application . '/' . $class::$module . '-' . Request::i()->review, Request::i()->id, Request::i()->review, get_class( $item ) );
				
				Output::i()->redirect( $review->url() );
			}

			Output::i()->metaTags['robots'] = 'noindex';
			
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/1I', 403, '' );
		}
	}

	/**
	 * Allow the author of a content item to edit their review response
	 *
	 * @return	void
	 */
	protected function _editResponse(): void
	{
		$this->_respond();
	}

	/**
	 * Delete a review response
	 *
	 * @return	void
	 */
	protected function _deleteResponse(): void
	{
		Session::i()->csrfCheck();

		try
		{
			/* Init */
			$class = static::$contentModel;
			$reviewClass = $class::$reviewClass;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$review = $reviewClass::loadAndCheckPerms( Request::i()->review );
			
			/* Are we allowed to delete responses? */
			if ( !$review->canDeleteResponse() )
			{
				throw new DomainException;
			}
			
			$review->author_response = NULL;
			$review->save();

			Output::i()->redirect( $review->url() );
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/1J', 403, '' );
		}
	}
	
	/**
	 * Message Form
	 *
	 * @return	void
	 */
	protected function messageForm(): void
	{
		$class = static::$contentModel;
		try
		{
			$item = $class::loadAndCheckPerms( Request::i()->id );

			$current = NULL;
			$metaData = $item->getMeta();
			if ( isset( Request::i()->meta_id ) AND isset( $metaData['core_ContentMessages'][ Request::i()->meta_id ] ) )
			{
				$current = $metaData['core_ContentMessages'][ Request::i()->meta_id ];
			}
			
			if ( !$item->canOnMessage( $current ? 'edit' : 'add' ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/1D', 404, '' );
		} 

		$form = new Form;
		$form->attributes['data-controller'] = 'core.front.core.contentMessage';
		$form->add( new YesNo( 'message_is_public', $current['is_public'] ?? FALSE ) );
		$form->add( new Editor( 'message', $current ? $current['message'] : NULL , TRUE, array( 'app' => 'core', 'key' => 'Meta', 'autoSaveKey' => $current ? "meta-message-" . Request::i()->meta_id : "meta-message-new", 'attachIds' => $current ? array( Request::i()->meta_id, NULL, 'core_ContentMessages' ) : NULL ) ) );
		$form->add( new Custom( 'message_color', $current['color'] ?? 'none', FALSE, array( 'getHtml' => function( $element )
        {
            return Theme::i()->getTemplate( 'forms', 'core', 'front' )->colorSelection( $element->name, $element->value );
        } ), NULL, NULL, NULL, 'message_color' ) );

		if ( $values = $form->values() )
		{
			if ( $current )
			{
				$item->editMessage( Request::i()->meta_id, $values['message'], $values['message_color'], NULL, (bool) $values['message_is_public'] );
				File::claimAttachments( "meta-message-" . Request::i()->meta_id, Request::i()->meta_id, NULL, 'core_ContentMessages' );
				
				Session::i()->modLog( 'modlog__message_edit', array(
					(string) $item->url()	=> FALSE,
					$item->mapped('title')	=> FALSE
				), $item );
			}
			else
			{
				$id = $item->addMessage( $values['message'], $values['message_color'], NULL, (bool) $values['message_is_public'] );
				File::claimAttachments( "meta-message-new", $id, NULL, 'core_ContentMessages' );
				
				Session::i()->modLog( 'modlog__message_add', array(
					(string) $item->url()	=> FALSE,
					$item->mapped('title')	=> FALSE
				), $item );
			}
			
			Output::i()->redirect( $item->url() );
		}
		
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Message Delete
	 *
	 * @return	void
	 */
	protected function messageDelete(): void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			if ( !$item->canOnMessage('delete') )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/1E', 404, '' );
		}
		
		$item->deleteMessage( Request::i()->meta_id );
		
		File::unclaimAttachments( 'core_Meta', Request::i()->meta_id, NULL, 'core_ContentMessages' );
		
		Session::i()->modLog( 'modlog__message_delete', array(
			(string) $item->url()	=> FALSE,
			$item->mapped('title')	=> FALSE
		), $item );
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( $item->url() );
		}
	}

	/**
	 * Solve the item
	 *
	 * @return	void
	 */
	public function solve(): void
	{
		Session::i()->csrfCheck();

		try
		{
			$class = static::$contentModel;
			$commentClass = $class::$commentClass;
			$item = $class::loadAndCheckPerms( Request::i()->id );

			if ( ! IPS::classUsesTrait( $item, 'IPS\Content\Solvable' ) )
			{
				throw new OutOfRangeException;
			}

			if ( ! $item->canSolve() )
			{
				throw new OutOfRangeException;
			}

			$comment = $commentClass::loadAndCheckPerms( Request::i()->answer );
			$idField = $comment::$databaseColumnId;

			if ( $comment->item() != $item )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/7', 404, '' );
		}

		$item->toggleSolveComment( Request::i()->answer, TRUE );

		/* Log */
		if ( Member::loggedIn()->modPermission('can_set_best_answer') )
		{
			Session::i()->modLog( 'modlog__best_answer_set', array( $comment->$idField => FALSE ), $item );
		}

		Output::i()->redirect( $item->url() );
	}

	/**
	 * Mark the comment helpful
	 *
	 * @return	void
	 */
	public function toggleHelpful(): void
	{
		Session::i()->csrfCheck();

		if( !IPS::classUsesTrait( static::$contentModel, 'IPS\Content\Helpful' ) )
		{
			Output::i()->error( 'node_error', '@todo', 404, '' );
		}

		try
		{
			/* Load comment */
			$class = static::$contentModel;
			$commentClass = $class::$commentClass;
			$comment = $commentClass::loadAndCheckPerms( Request::i()->answer );

			if( $comment->markedHelpful() )
			{
				$comment->unmarkHelpful();
			}
			else
			{
				$comment->markHelpful();
			}

			if ( Request::i()->isAjax() )
			{
				$mostHelpfulHtml = null;
				if( $mostHelpful = $comment->item()->helpfulPosts(1) and $mostHelpful[0]['count'] >= Settings::i()->forums_helpful_highlight )
				{
					$mostHelpfulHtml = Theme::i()->getTemplate( 'topics', 'forums', 'front' )->topicHelpfulMessage( $mostHelpful );
				}

				$output = array(
					'status' => 'ok',
					'count' => $comment->helpfulCount(),
					'helpfulReplies' => $comment->item()->helpfulsRepliesCount(),
					'mostHelpfulHtml' => $mostHelpfulHtml,
					'countLanguage' => Member::loggedin()->language()->addToStack('helpful_only_n_helpful', FALSE, array( 'pluralize' => [ $comment->item()->helpfulsRepliesCount() ] ) ),
					'button' => Theme::i()->getTemplate( 'system', 'core', 'front' )->helpfulButton( $comment, $comment->item() ),
				);

				Output::i()->json( $output );
			}
			else
			{
				Output::i()->redirect( $comment->url() );
			}
		}
		catch( OutOfRangeException | DomainException $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'error' => Member::loggedIn()->language()->addToStack( $e->getMessage() ) ), 403 );
			}
			else
			{
				Output::i()->error( $e->getMessage(), '1S136/14', 403, '' );
			}
		}
	}

	/**
	 * Unsolve the item
	 *
	 * @return	void
	 */
	public function unsolve(): void
	{
		Session::i()->csrfCheck();

		try
		{
			$class = static::$contentModel;
			$commentClass = $class::$commentClass;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$comment = $commentClass::loadAndCheckPerms( Request::i()->answer );

			$idField = $comment::$databaseColumnId;
			/* @var $databaseColumnMap array */
			$solvedField = $item::$databaseColumnMap['solved_comment_id'];

			if ( ! IPS::classUsesTrait( $item, 'IPS\Content\Solvable' ) )
			{
				throw new OutOfRangeException;
			}

			if ( ! $item->canSolve() )
			{
				throw new OutOfRangeException;
			}

			if ( $comment->item() != $item )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/G', 404, '' );
		}

		if ( $item->mapped('solved_comment_id') )
		{
			try
			{
				$item->toggleSolveComment( $item->mapped('solved_comment_id'), FALSE );

				if ( Member::loggedIn()->modPermission('can_set_best_answer') )
				{
					Session::i()->modLog( 'modlog__best_answer_unset', array( $comment->$idField => FALSE ), $item );
				}
			}
			catch ( Exception $e ) {}
		}

		Output::i()->redirect( $item->url() );
	}
	
	/**
	 * Toggle Item Moderation
	 *
	 * @return	void
	 */
	public function toggleItemModeration(): void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			
			if ( !$item->canToggleItemModeration() )
			{
				throw new BadMethodCallException;
			}
			
			if ( $item->itemModerationEnabled() )
			{
				$action = 'disable';
				$actionLang = 'disabled';
			}
			else
			{
				$action = 'enable';
				$actionLang = 'enabled';
			}
			
			$item->toggleItemModeration( $action );
			
			Session::i()->modLog( 'modlog__item_moderation_toggled', [$actionLang => TRUE], $item );
			
			Output::i()->redirect( $item->url(), $actionLang );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/1T', 404, '' );
		}
		catch( BadMethodCallException | InvalidArgumentException $e )
		{
			Output::i()->error( 'no_module_permission', '2S136/1U', 403, '' );
		}
	}

	/**
	 * Stuff that applies to both comments and reviews
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 */
	public function __call( string $method, array $args )
	{
		$class = static::$contentModel;
		
		try
		{
			$item = $class::loadAndCheckPerms( Request::i()->id );
			
			$comment = NULL;
			if ( mb_substr( $method, -7 ) === 'Comment' )
			{
				if ( isset( $class::$commentClass ) and ( isset( Request::i()->comment ) OR mb_substr( $method, 0, 8 ) === 'multimod' ) )
				{
					$class = $class::$commentClass;
					$method = '_' . mb_substr( $method, 0, mb_strlen( $method ) - 7 );
					
					$comment = $method === '_multimod' ? NULL : $class::load( Request::i()->comment );
				}
			}
			elseif ( mb_substr( $method, -6 ) === 'Review' )
			{
				if ( isset( $class::$reviewClass ) and ( isset( Request::i()->review ) OR mb_substr( $method, 0, 8 ) === 'multimod' ) )
				{
					$class = $class::$reviewClass;
					$method = '_' . mb_substr( $method, 0, mb_strlen( $method ) - 6 );
					$comment = $method === '_multimod' ? NULL : $class::load( Request::i()->review );
				}
			}
			
			if ( $method === '_multimod' )
			{
				$this->_multimod( $class, $item );
			}
									
			if ( !$comment or !method_exists( $this, $method ) )
			{
				if ( mb_substr( $method, 0, 4 ) === 'find' )
				{
					/* Nothing found, redirect to main URL */
					Output::i()->redirect( $item->url() );
				}
				else
				{
					Output::i()->error( 'page_not_found', '2S136/B', 404, '' );
				}
			}
			else
			{
				$this->$method( $class, $comment, $item );
			}
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/C', 404, '' );
		}
	}
	
	/**
	 * Find a Comment / Review (do=findComment/findReview)
	 *
	 * @param	string					$commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 */
	public function _find( string $commentClass, Comment $comment, Item $item ) : void
	{
		/* @var Comment $commentClass */
		$idColumn = $commentClass::$databaseColumnId;
		/* @var $databaseColumnMap array */
		$itemColumn = $commentClass::$databaseColumnMap['item'];
		
		/* Note in the session that we were looking for this comment. This can be used
			to set appropriate meta tag descriptions */
		$_SESSION['_findComment']	= $comment->$idColumn;
		
		/* Work out where the comment is in the item */	
		$directional = ( in_array( 'IPS\Content\Review', class_parents( $commentClass ) ) ) ? '>=?' : '<=?';
		$where = array(
			array( $commentClass::$databasePrefix . $itemColumn . '=?', $comment->$itemColumn ),
			array( $commentClass::$databasePrefix . $idColumn . $directional, $comment->$idColumn )
		);

		/* Exclude content pending deletion, as it will not be shown inline  */
		if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
		{
			$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '<>?', -2 );
			$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '<>?', -3 );
		}
		elseif( isset( $commentClass::$databaseColumnMap['hidden'] ) )
		{
			$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '<>?', -2 );
			$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '<>?', -3 );
		}

		if ( $commentClass::commentWhere() !== NULL )
		{
			$where[] = $commentClass::commentWhere();
		}
		if ( $container = $item->containerWrapper() )
		{
			if ( $commentClass::modPermission( 'view_hidden', NULL, $container ) === FALSE )
			{
				if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=?', 1 );
				}
				elseif( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=?', 0 );
				}
			}
		}
		$commentPosition = $commentClass::db()->select( 'COUNT(*) AS position', $commentClass::$databaseTable, $where )->first();
		
		/* Now work out what page that makes it */
		$url = $item->url();

		if ( in_array( 'IPS\Content\Review', class_parents( $commentClass ) ) )
		{
			$perPage = $item::$reviewsPerPage;
		}
		else
		{
			$perPage = $item::getCommentsPerPage();
		}

		$page = ceil( $commentPosition / $perPage );
		if ( $page != 1 )
		{
			$url = $url->setPage( 'page', $page );
		}

		if( $commentClass::$tabParameter !== NULL )
		{
			$url = $url->setQueryString( $commentClass::$tabParameter );
		}

		$fragment = 'comment';

		if ( in_array( 'IPS\Content\Review', class_parents( $commentClass ) ) )
		{
			$url = $url->setQueryString( array( 'sort' => 'newest' ) );
			$fragment = 'review';
		}

		if ( isset( Request::i()->showDeleted ) )
		{
			$url = $url->setQueryString( 'showDeleted', 1 );
		}
		
		if ( isset( Request::i()->_report ) )
		{
			$url = $url->setQueryString( '_report', Request::i()->_report );
		}
		
		/* And redirect */
		Output::i()->redirect( $url->setFragment( $fragment . '-' . $comment->$idColumn ) );
	}
	
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
		/* If this is an AJAX request, and we're coming from the approval queue, just do it. */
		if ( Request::i()->isAjax() AND isset( Request::i()->_fromApproval ) )
		{
			Session::i()->csrfCheck();

			$comment->modAction( 'hide' );
			Output::i()->json( 'OK' );
		}
		
		if ( $comment::$hideLogKey )
		{
			$form = new Form;
			$form->add( new Text( 'hide_reason' ) );
			$this->moderationAlertField( $form, $comment);

			if ( $values = $form->values() )
			{
				$comment->modAction( 'hide', NULL, $values['hide_reason'] );

				if( isset( $values['moderation_alert_content']) AND $values['moderation_alert_content'])
				{
					$this->sendModerationAlert($values, $comment);
				}
			}
			else
			{
				$this->_setBreadcrumbAndTitle( $item );
				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
				return;
			}
		}
		else
		{
			Session::i()->csrfCheck();

			$comment->modAction( 'hide' );
		}
		
		Output::i()->redirect( $comment->url() );
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
	public function _unhide( string $commentClass, Comment $comment, Item $item  ): void
	{
		Session::i()->csrfCheck();
		$comment->modAction( 'unhide' );

		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $comment->html() );
		}
		else
		{
			Output::i()->redirect( $comment->url() );
		}
	}
	
	/**
	 * Restore a Comment / Review
	 *
	 * @param	string					$commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _restore( string $commentClass, Comment $comment, Item $item ): void
	{
		Session::i()->csrfCheck();
		
		if ( isset( Request::i()->restoreAsHidden ) )
		{
			Session::i()->modLog( 'modlog__action_restore_hidden', array(
				$comment::$title				=> TRUE,
				$comment->url()->__toString()	=> FALSE
			) );

			$comment->modAction( 'restoreAsHidden' );
		}
		else
		{
			Session::i()->modLog( 'modlog__action_restore', array(
				$comment::$title				=> TRUE,
				$comment->url()->__toString()	=> FALSE
			) );

			$comment->modAction( 'restore' );
		}
		
		if ( isset( Request::i()->_report ) )
		{
			try
			{
				$report = Report::loadAndCheckPerms( Request::i()->_report );
				Output::i()->redirect( $report->url() );
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->redirect( $comment->url() );
			}
		}
		else
		{
			Output::i()->redirect( $comment->url() );
		}
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
		$class = static::$contentModel;
		/* @var $databaseColumnMap array */
		/* @var Comment $commentClass */
		$valueField = $commentClass::$databaseColumnMap['content'];
		$idField = $commentClass::$databaseColumnId;
		$itemIdField = $item::$databaseColumnId;

		if ( $comment->canEdit() )
		{
			$form = new Form( 'form', false );
			$form->class = 'ipsForm--vertical ipsForm--edit';
			
			if ( in_array( 'IPS\Content\Review', class_parents( $comment ) ) )
			{
				$ratingField = $commentClass::$databaseColumnMap['rating'];
				$form->add( new Rating( 'rating_value', $comment->$ratingField, TRUE, array( 'max' => Settings::i()->reviews_rating_out_of ) ) );
			}
			
			$form->add( new Editor( 'comment_value', $comment->$valueField, TRUE, array(
				'app'			=> $class::$application,
				'key'			=> IPS::mb_ucfirst( $class::$module ),
				'autoSaveKey' 	=> 'editComment-' . $class::$application . '/' . $class::$module . '-' . $comment->$idField,
				'attachIds'		=> $comment->attachmentIds()
			) ) );
			
			/* Post Anonymously */
			$container = $item->containerWrapper();
			if ( $container and $container->canPostAnonymously( $container::ANON_COMMENTS ) and IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) and ( $comment->author() and $comment->author()->group['gbw_can_post_anonymously'] or $comment->isAnonymous() ) )
			{
				$form->add ( new YesNo( 'post_anonymously', $comment->isAnonymous(), FALSE, array( 'label' => Member::loggedIn()->language()->addToStack( 'post_anonymously_suffix' ) ), NULL, NULL, NULL, 'post_anonymously' ) );
			}

			foreach( $comment->ui( 'formElements', array( $comment->item() ), TRUE ) as $element )
			{
				$form->add( $element );
			}

			$form->addButton( 'cancel', 'link', $item->url()->setQueryString( in_array( 'IPS\Content\Review', class_parents( $comment ) ) ? array( 'do' => 'findReview', 'review' => $comment->$idField ) : array( 'do' => 'findComment', 'comment' => $comment->$idField ) ), 'ipsButton ipsButton--text ipsButton--form-cancel', array( 'data-action' => 'cancelEditComment', 'data-comment-id' => $comment->$idField ) );

			$form->addButton( 'save', 'submit', null, 'ipsButton ipsButton--positive ipsButton--form-submit', array( 'tabindex' => '2', 'accesskey' => 's' ) );

			if ( IPS::classUsesTrait( $comment, 'IPS\Content\EditHistory' ) and Settings::i()->edit_log )
			{
				if ( Settings::i()->edit_log == 2 or isset( $commentClass::$databaseColumnMap['edit_reason'] ) )
				{
					$form->add( new Text( 'comment_edit_reason', ( isset( $commentClass::$databaseColumnMap['edit_reason'] ) ) ? $comment->mapped( 'edit_reason' ) : NULL, FALSE, array( 'maxLength' => 255 ) ) );
				}
				if ( Member::loggedIn()->group['g_append_edit'] )
				{
					$form->add( new Checkbox( 'comment_log_edit', FALSE ) );
				}
			}
			
			if ( $values = $form->values() )
			{
                Event::fire( 'onBeforeCreateOrEdit', $comment, array( $values ) );

				/* Log History */
				if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\EditHistory' ) and Settings::i()->edit_log )
				{
					$comment->logEdit( $values );
				}

				/* Determine if the comment is hidden to start with */
				$isHidden = $comment->hidden();
				
				/* Do it */
				$comment->editContents( $values['comment_value'] );

                /* Edit rating */
				$reloadPage = false;
				if ( isset( $values['rating_value'] ) and in_array( 'IPS\Content\Review', class_parents( $comment ) ) )
				{
					/* The star rating changes but is outside of JS scope to change when editing a comment */
					$ratingField = $comment::$databaseColumnMap['rating'];
					if ( $comment->$ratingField != $values['rating_value'] )
					{
						$reloadPage = true;
					}
					
					$comment->editRating( $values['rating_value'] );
				}

				/* Anonymous posting */
				if( isset( $values[ 'post_anonymously' ] ) AND IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) )
				{
					/* The anon changes to the user details (photo, name, etc) is outside the JS scope to change when editing a comment */
					if ( $comment->isAnonymous() !== (bool) $values['post_anonymously'] )
					{
						$reloadPage = true;
					}
					
					$comment->setAnonymous( $values[ 'post_anonymously' ], $comment->author() );
				}

				Event::fire( 'onCreateOrEdit', $comment, array( $values ) );

				$comment->ui( 'formPostSave', array( $values ) );
			
				/* Moderator log */
				Session::i()->modLog( 'modlog__comment_edit', array( $comment->url()->__toString() => FALSE, $item::$title => TRUE, $item->url()->__toString() => FALSE, $item->mapped( 'title' ) => FALSE ), $item );
				
				/* If this is an AJAX request and the comment hidden status has not changed just output the comment HTML */
				if ( Request::i()->isAjax() AND $isHidden == $comment->hidden() AND $reloadPage === false )
				{
					/* Make sure we get the latest data from the database tables to prevent replication lag issues */
					try
					{
						$comment->clearCaches();
						$comment = $comment::load( $comment->$idField );

						Output::i()->output = $comment->html();
						return;
					}
					catch( OutOfRangeException $e )
					{
						/* Something went wrong so redirect */
						Output::i()->redirect( $comment->url() );
					}
				}
				else
				{
					Output::i()->redirect( $comment->url() );
				}
			}
			
			$this->_setBreadcrumbAndTitle( $item );
			Output::i()->breadcrumb[] = array( NULL, in_array( 'IPS\Content\Review', class_parents( $commentClass ) )? Member::loggedIn()->language()->addToStack( 'edit_review' ) : Member::loggedIn()->language()->addToStack( 'edit_comment' ) );
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'edit_comment' ) . ' - ' . $item->mapped( 'title' );
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			throw new InvalidArgumentException;
		}
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
		Session::i()->csrfCheck();

		$currentPageCount = $item->commentPageCount();

		/* @var Comment $commentClass */
		/* @var $databaseColumnMap array */
		$valueField = $commentClass::$databaseColumnMap['content'];
		$idField = $commentClass::$databaseColumnId;
		
		if ( $item::$firstCommentRequired and $comment->mapped( 'first' ) )
		{
			if ( $item->canDelete() )
			{
				/* If we are retaining content for a period of time, we need to just hide it instead for deleting later - this only works, though, with items that implement \IPS\Content\Hideable */
				if ( Settings::i()->dellog_retention_period AND IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) AND !isset( Request::i()->immediately ) )
				{
					$item->logDelete();
				}
			}
			else
			{
				Output::i()->error( 'node_noperm_delete', '3S136/1L', 403, '' );
			}
		}
		else
		{
			if ( $comment->canDelete() )
			{
				/* If we are retaining content for a period of time, we need to just hide it instead for deleting later - this only works, though, with items that implement \IPS\Content\Hideable */
				if ( Settings::i()->dellog_retention_period AND IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) AND !isset( Request::i()->immediately ) )
				{
					$comment->logDelete();
				}
				else
				{
					$comment->delete();
				}
				
				/* Log */
				Session::i()->modLog( 'modlog__comment_delete', array( $item::$title => TRUE, $item->url()->__toString() => FALSE, $item->mapped( 'title' ) => FALSE ), $item );
			}
			else
			{
				Output::i()->error( 'node_noperm_delete', '3S136/1K', 403, '' );
			}
		}

		/* Reset best answer */
		if( $item->topic_answered_pid and $item->topic_answered_pid == $comment->$idField )
		{
			$item->topic_answered_pid = 0;
			$item->save();
		}
		
		if ( Request::i()->isAjax() )
		{
			$currentPageCount = Request::i()->page;
			$newPageCount = $item->commentPageCount( TRUE );
			if ( isset( Request::i()->page ) AND $currentPageCount != $newPageCount )
			{
				/* If we are on page 2 and delete a comment, and there are 3 pages, we don't want to be sent to page 3 (that makes no sense).
					Instead, we'll send you to the page requested. If it exists you'll be on the same page. If it doesn't, the controller will
					handle sending you to the correct location */
				Output::i()->json( array( 'type' => 'redirect', 'total' => $item->mapped( 'num_comments' ), 'url' => (string) $item->url()->setPage( 'page', (int) Request::i()->page ) ) );
			}
			else
			{
				Output::i()->json( array( 'page' => $newPageCount, 'total' => $item->mapped( 'num_comments' ) ) );
			}
		}
		else
		{
			if ( isset( Request::i()->_report ) )
			{
				try
				{
					$report = Report::loadAndCheckPerms( Request::i()->_report );
					Output::i()->redirect( $report->url() );
				}
				catch( OutOfRangeException $e )
				{
					Output::i()->redirect( $item->url() );
				}
			}
			else
			{
				Output::i()->redirect( $item->url() );
			}
		}
	}
	
	/**
	 * Split Comment
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _split( string $commentClass, Comment $comment, Item $item ) : void
	{
		if ( $comment->canSplit() )
		{
			/* @var Item $itemClass */
			$itemClass = $comment::$itemClass;
			$idColumn = $itemClass::$databaseColumnId;
			$commentIdColumn = $comment::$databaseColumnId;
			
			/* Create a copy of the old item for logging */
			$oldItem = $item;
			
			/* Construct a form */
			$form = $this->_splitForm( $item, $comment );

			/* Handle submissions */
			if ( $values = $form->values() )
			{
				/* Are we creating or using an existing? */
				if ( isset( $values['_split_type'] ) and $values['_split_type'] === 'new' )
				{
					$item = $itemClass::createItem( $comment->author(), $comment->mapped( 'ip_address' ), DateTime::ts( $comment->mapped( 'date' ) ), $values[$itemClass::$formLangPrefix . 'container'] ?? NULL );
					$item->processForm( $values );
					if ( isset( $itemClass::$databaseColumnMap['first_comment_id'] ) )
					{
						$firstCommentIdColumn = $itemClass::$databaseColumnMap['first_comment_id'];
						$item->$firstCommentIdColumn = $comment->$commentIdColumn;
					}

					/* Does the first post require moderator approval? */
					if ( $comment->hidden() === 1 )
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$column = $item::$databaseColumnMap['hidden'];
							$item->$column = 1;
						}
						elseif ( isset( $item::$databaseColumnMap['approved'] ) )
						{
							$column = $item::$databaseColumnMap['approved'];
							$item->$column = 0;
						}
					}
					/* Or is it hidden? */
					elseif ( $comment->hidden() === -1 )
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$column = $item::$databaseColumnMap['hidden'];
						}
						elseif ( isset( $item::$databaseColumnMap['approved'] ) )
						{
							$column = $item::$databaseColumnMap['approved'];
						}

						$item->$column = -1;
					}

					$item->save();

					if( $comment->hidden() !== 0 )
					{
						if ( isset( $comment::$databaseColumnMap['hidden'] ) )
						{
							$column = $comment::$databaseColumnMap['hidden'];
							$comment->$column = 0;
						}
						elseif ( isset( $comment::$databaseColumnMap['approved'] ) )
						{
							$column = $comment::$databaseColumnMap['approved'];
							$comment->$column = 1;
						}

						$comment->save();
					}
				}
				else
				{
					$item = $itemClass::loadFromUrl( $values['_split_into_url'] );

					if ( !$item->canView() )
					{
						throw new DomainException;
					}
				}

				/* Remove featured comment associations */
				if( IPS::classUsesTrait( $itemClass, 'IPS\Content\MetaData' ) AND $comment->isFeatured() AND $oldItem )
				{
					Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->unfeatureComment( $oldItem, $comment );
				}

				/* Remove solved associations */
				if ( $oldItem and IPS::classUsesTrait( $oldItem, 'IPS\Content\Solvable' ) and $oldItem->isSolved() and ( $oldItem->mapped('solved_comment_id') === $comment->$commentIdColumn ) )
				{
					$oldItem->toggleSolveComment( $comment->$commentIdColumn, FALSE );
				}

				$comment->move($item);
				$oldItem->rebuildFirstAndLastCommentData();

				/* Log it */
				Session::i()->modLog( 'modlog__action_split', array(
					$item::$title					=> TRUE,
					$item->url()->__toString()		=> FALSE,
					$item->mapped( 'title' )			=> FALSE,
					$oldItem->url()->__toString()	=> FALSE,
					$oldItem->mapped( 'title' )		=> FALSE
				), $item );

				if( isset( $values['moderation_alert_content'] ) AND $values['moderation_alert_content'] )
				{
					$this->sendModerationAlert( $values, $comment );
				}
			
				/* Redirect to it */
				Output::i()->redirect( $item->url() );
			}
			
			/* Display */
			$this->_setBreadcrumbAndTitle( $item );
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			throw new DomainException;
		}
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
		/* Permission check */
		if ( Settings::i()->edit_log != 2 or ( !Settings::i()->edit_log_public and !Member::loggedIn()->modPermission( 'can_view_editlog' ) ) )
		{
			throw new DomainException;
		}

		/* @var Comment $commentClass */
		$idColumn = $commentClass::$databaseColumnId;
		$where = array( array( 'class=? AND comment_id=?', $commentClass, $comment->$idColumn ) );
		if ( !Member::loggedIn()->modPermission( 'can_view_editlog' ) )
		{
			$where[] = array( '`member`=? AND public=1', $comment->author()->member_id );
		}

		$table = new TableDb( 'core_edit_history', $item->url()->setQueryString( array( 'do' => 'editlogComment', 'comment' => $comment->$idColumn ) ), $where );
		$table->sortBy = 'time';
		$table->sortDirection = 'desc';
		$table->limit = 10;
		$table->tableTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'commentEditHistoryTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'commentEditHistoryRows' );
		$table->parsers = array(
			'new' => function( $val )
			{
				return $val;
			},
			'old' => function( $val )
			{
				return $val;
			}
		);
		$table->extra = $comment;

		$pageParam = $table->getPaginationKey();
		if( Request::i()->isAjax() AND isset( Request::i()->$pageParam ) )
		{
			Output::i()->sendOutput( (string) $table );
		}
		
		/* Display */
		$container = NULL;
		try
		{
			$container = $item->container();
			foreach ( $container->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
		}
		catch ( Exception $e ) { }
		Output::i()->breadcrumb[] = array( $comment->url(), $item->mapped( 'title' ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'edit_history_title' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'edit_history_title' );
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'front' )->commentEditHistory( (string) $table, $comment );
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
		try
		{
			$class = static::$contentModel;

			/* Permission check */
			$canReport = $comment->canReport();
			if ( $canReport !== TRUE AND !( $canReport == 'report_err_already_reported' AND Settings::i()->automoderation_enabled ) )
			{
				Output::i()->error( $canReport, '2S136/4', 403, '' );
			}

			/* Show form */
			$form = new Form( 'report_submit', 'report_submit' );
			$form->class = 'ipsForm--vertical ipsForm--report';
			$itemIdColumn = $class::$databaseColumnId;
			$idColumn = $comment::$databaseColumnId;
			
			/* As we group by user id to determine if max points have been reached, guests cannot contribute to counts */
			if ( Member::loggedIn()->member_id and Settings::i()->automoderation_enabled )
			{
				/* Has this member already reported this in the past 24 hours */
				try
				{
					$index = Report::loadByClassAndId( get_class( $comment ), $comment->$idColumn );
					$report = Db::i()->select( '*', 'core_rc_reports', array( 'rid=? and report_by=? and date_reported > ?', $index->id, Member::loggedIn()->member_id, time() - ( Settings::i()->automoderation_report_again_mins * 60 ) ) )->first();
					
					Output::i()->output = Theme::i()->getTemplate( 'system', 'core' )->reportedAlready( $index, $report, $comment );
					return;
				}
				catch( Exception $e ) { }
			}

			$options = array( Report::TYPE_MESSAGE => Member::loggedIn()->language()->addToStack('report_message_comment') );
			foreach( Types::roots() as $type )
			{
				$options[ $type->id ] = $type->_title;
			}

			if ( count( $options ) > 1 )
			{
				$form->add( new Select( 'report_type', null, false, ['options' => $options] ) );
			}

			if ( ! Member::loggedIn()->member_id and Settings::i()->report_capture_guest_details )
			{
				/* Guest details */
				$form->add( new Text( 'report_guest_name', null, Settings::i()->report_guest_details_name_mandatory ) );
				$form->add( new Email( 'report_guest_email', null, true ) );
			}

			$form->add( new Editor( 'report_message', null, (bool) Settings::i()->report_content_mandatory, array( 'app' => 'core', 'key' => 'Reports', 'autoSaveKey' => "report-{$class::$application}-{$class::$module}-{$item->$itemIdColumn}-{$comment->$idColumn}", 'minimize' => Settings::i()->report_content_mandatory ? 'report_message_placeholder_mandatory' : 'report_message_placeholder' ) ) );

			if ( !Request::i()->isAjax() )
			{
				Member::loggedIn()->language()->words['report_message'] = Member::loggedIn()->language()->addToStack('report_message_fallback');
			}
			
			if( !Member::loggedIn()->member_id )
			{
				$form->add( new Captcha );
			}

			if ( $values = $form->values() )
			{
				$guestDetails = [];
				if ( ! Member::loggedIn()->member_id and Settings::i()->report_capture_guest_details )
				{
					$guestDetails = [ 'name' => $values['report_guest_name'], 'email' => $values['report_guest_email'] ];

					/* Has this guest already reported this? */
					try
					{
						$index = Db::i()->select( '*', 'core_rc_index', [ 'class=? and content_id=?', \get_class( $comment ), $comment->$idColumn ] )->first();
						$report = Db::i()->select( '*', 'core_rc_reports', [ 'rid=? and guest_email=?', $index['id'], $values['report_guest_email'] ] )->first();

						Output::i()->error( 'report_err_already_reported', '2S136/11', 403, '' );
					}
					catch( \UnderflowException )
					{
						/* The query failed, so this is a new report */
					}
				}

				$report = $comment->report( $values['report_message'], ( isset( $values['report_type'] ) ) ? $values['report_type'] : 0, null, $guestDetails  );
				File::claimAttachments( "report-{$class::$application}-{$class::$module}-{$item->$itemIdColumn}-{$comment->$idColumn}", $report->id );
				if ( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( Member::loggedIn()->language()->addToStack( 'report_submit_success' ) );
				}
				else
				{
					Output::i()->redirect( $comment->url(), 'report_submit_success' );
				}
			}
			$this->_setBreadcrumbAndTitle( $item );

			/* Even if guests can report something, we don't want the report form indexed in Google */
			Output::i()->metaTags['robots'] = 'noindex';

			Output::i()->output = Request::i()->isAjax() ? $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) : Theme::i()->getTemplate( 'system', 'core' )->reportForm( $form );
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'node_error', '2S136/10', 404, '' );
		}
	}
	
	/**
	 * React to a comment/review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _react( string $commentClass, Comment $comment, Item $item ): void
	{
		try
		{
			Session::i()->csrfCheck();

			$reaction = Reaction::load( Request::i()->reaction );
			$comment->react( $reaction );

			/* Send a realtime event so that users on this page immediately see the reaction */
			if ( Bridge::i()->featureIsEnabled( 'realtime' ) )
			{
				$blurb = ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $comment, true );
				Lang::load( Lang::defaultLanguage() )->parseOutputForDisplay( $blurb );
				Bridge::i()->publishRealtimeEvent( 'reaction-count', [
					'memberReacted' => Member::loggedIn()->member_id,
					'content' => $comment->getDataLayerProperties(),
					'blurb' => $blurb,
					'score' => $comment->reactionCount(),
					'count' => count( $comment->reactions() ),
					'eventType' => 'comment.react'
				]);
			}

			if ( Request::i()->isAjax() )
			{
				$output = array(
					'status' => 'ok',
					'count' => count( $comment->reactions() ),
					'score' => $comment->reactionCount(),
					'blurb' => ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $comment )
				);

				if ( Settings::i()->core_datalayer_enabled )
				{
					$item = $item ?? $comment->item();
					$object = ( !isset( $item::$databaseColumnMap['content'] ) AND ( $item::$commentClass ) AND $comment->isFirst() ) ? $item : $comment;
					$output['datalayer'] = array_replace( $object->getDataLayerProperties(), ['reaction_type' => $reaction->_title] );
				}


				Output::i()->json( $output );
			}
			else
			{
				/* Data Layer Event */
				if ( Settings::i()->core_datalayer_enabled )
				{
					$properties = $comment->getDataLayerProperties();
					$properties['reaction_type'] = $reaction->_title;

					DataLayer::i()->addEvent( 'content_react', $properties );
				}
				Output::i()->redirect( $comment->url() );
			}
		}
		catch( DomainException $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'error' => Member::loggedIn()->language()->addToStack( $e->getMessage() ) ), 403 );
			}
			else
			{
				Output::i()->error( $e->getMessage(), '1S136/16', 403, '' );
			}
		}
	}
	
	/**
	 * Unreact to a comment/review
	 *
	 * @param	string					$commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _unreact( string $commentClass, Comment $comment, Item $item ): void
	{
		try
		{
			Session::i()->csrfCheck();

			$member = ( isset( Request::i()->member ) and Member::loggedIn()->modPermission('can_remove_reactions') ) ? Member::load( Request::i()->member ) : Member::loggedIn();

			$comment->removeReaction( $member );

			/* Send a realtime event so that users on this page immediately see the reaction */
			if ( Bridge::i()->featureIsEnabled( 'realtime' ) )
			{
				$blurb = ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $comment, true );
				Lang::load( Lang::defaultLanguage() )->parseOutputForDisplay( $blurb );
				Bridge::i()->publishRealtimeEvent( 'reaction-count', [
					'memberReacted' => Member::loggedIn()->member_id,
					'content' => $comment->getDataLayerProperties(),
					'blurb' => $blurb,
					'score' => $comment->reactionCount(),
					'count' => count( $comment->reactions() ),
					'eventType' => 'comment.unreact'
				]);
			}

			/* Log */
			if( $member->member_id !== Member::loggedIn()->member_id )
			{
				Session::i()->modLog( 'modlog__comment_reaction_delete', array( $member->url()->__toString() => FALSE, $member->name => FALSE, $comment->url()->__toString() => FALSE, $item::$title => TRUE, $item->url()->__toString() => FALSE, $item->mapped( 'title' ) => FALSE ), $item );
			}
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array(
					'status' => 'ok',
					'count' => count( $comment->reactions() ),
					'score' => $comment->reactionCount(),
					'blurb' => ( Settings::i()->reaction_count_display == 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $comment )
				));
			}
			else
			{
				Output::i()->redirect( $comment->url() );
			}
		}
		catch( DomainException $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'error' => Member::loggedIn()->language()->addToStack( $e->getMessage() ) ), 403 );
			}
			else
			{
				Output::i()->error( $e->getMessage(), '1S136/17', 403, '' );
			}
		}
	}

	/**
	 * Get the reaction blurb for a comment (output in a JSON Response)
	 *
	 * @param string $commentClass
	 * @param Comment $comment
	 * @param Item $item
	 *
	 * @return void
	 */
	protected function _reactionBlurb( string $commentClass, Comment $comment, Item $item ): void
	{
		$blurb = ( Settings::i()->reaction_count_display === 'count' ) ? '' : Theme::i()->getTemplate( 'global', 'core' )->reactionBlurb( $comment );
		Output::i()->json([ 'blurb' => $blurb ]);
	}
	
	/**
	 * Show Comment/Review Reactions
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _showReactions( string $commentClass, Comment $comment, Item $item ): void
	{
		/* @var Comment $commentClass */
		$idColumn = $commentClass::$databaseColumnId;
		
		if ( Request::i()->isAjax() and isset( Request::i()->tooltip ) and isset( Request::i()->reaction ) )
		{
			$reaction = Reaction::load( Request::i()->reaction );
			
			$numberToShowInPopup = 10;
			$where = $comment->getReactionWhereClause( $reaction );
			$total = Db::i()->select( 'COUNT(*)', 'core_reputation_index', $where )->join( 'core_reactions', 'reaction=reaction_id' )->first();
			$names = Db::i()->select( 'name', 'core_reputation_index', $where, 'rep_date DESC', $numberToShowInPopup )->join( 'core_reactions', 'reaction=reaction_id' )->join( 'core_members', 'core_reputation_index.member_id=core_members.member_id' );
			
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'front' )->reactionTooltip( $reaction, $total ? $names : [], ( $total > $numberToShowInPopup ) ? ( $total - $numberToShowInPopup ) : 0 ) );
		}
		else
		{		
			$blurb = $comment->reactBlurb();
	
			$this->_setBreadcrumbAndTitle( $item );
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'see_who_reacted' ) . ' (' . $comment->$idColumn . ') - ' . Output::i()->title;
			
			$tabs = array();
			$tabs['all'] = array( 'title' => Member::loggedIn()->language()->addToStack('all'), 'count' => count( $comment->reactions() ) );
			foreach(Reaction::roots() AS $reaction )
			{
				if ( $reaction->_enabled !== FALSE )
				{
					$tabs[ $reaction->id ] = array( 'title' => $reaction->_title, 'icon' => $reaction->_icon, 'count' => $blurb[$reaction->id] ?? 0 );
				}
			}
	
			$activeTab = 'all';
			if ( isset( Request::i()->reaction ) )
			{
				$activeTab = Request::i()->reaction;
			}
			
			$url = $comment->url('showReactions');
			$url = $url->setQueryString( 'changed', 1 );
			
			if ( $activeTab !== 'all' )
			{
				$url = $url->setQueryString( 'reaction', $activeTab );
			}
	
			Output::i()->metaTags['robots'] = 'noindex';
			
			if ( Reaction::isLikeMode() or ( Request::i()->isAjax() AND isset( Request::i()->changed ) ) )
			{
				Output::i()->output = $comment->reactionTable( $activeTab !== 'all' ? $activeTab : NULL );
			}
			else
			{
				Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'front' )->reactionTabs( $tabs, $activeTab, $comment->reactionTable( $activeTab !== 'all' ? $activeTab : NULL ), $url, 'reaction', FALSE );
			}
		}
	}
	
	/**
	 * Multimod
	 *
	 * @param	string					$commentClass	The comment/review class
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _multimod( string $commentClass, Item $item ): void
	{
		/* @var Comment $commentClass */
		Session::i()->csrfCheck();
		
		$checkAgainst = Request::i()->modaction;
		
		$classToCheck = $commentClass;
		if( $checkAgainst == 'split' OR $checkAgainst == 'merge' )
		{
			$classToCheck = $item;
			$checkAgainst = 'split_merge';
		}
		
		if ( !$classToCheck::modPermission( $checkAgainst, NULL, $item->containerWrapper() ) )
		{
			throw new DomainException;
		}
		
		if ( Request::i()->modaction == 'split' )
		{
			$form = $this->_splitForm( $item );
			$form->hiddenValues['modaction'] = 'split';
			foreach ( Request::i()->multimod as $k => $v )
			{
				$form->hiddenValues['multimod['.$k.']'] = $v;
			}
			if ( $values = $form->values() )
			{
				$itemIdColumn = $item::$databaseColumnId;
				$commentIdColumn = $commentClass::$databaseColumnId;

				/* Create a copy of the old item for logging */
				$oldItem = $item;
				/* @var $databaseColumnMap array */
				$comments = iterator_to_array( new ActiveRecordIterator( Db::i()->select(
					'*',
					$commentClass::$databaseTable,
					array(
						array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $item->$itemIdColumn ),
						Db::i()->in( $commentClass::$databasePrefix . $commentClass::$databaseColumnId, array_keys( Request::i()->multimod ) )
					),
					$commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date']
				), $commentClass ) );
				
				foreach ( $comments as $comment )
				{
					$firstComment = $comment;
					break;
				}

				/* If we don't have a $firstComment, something went wrong - perhaps the input multimod comment ids are all from a different topic for instance */
				if( !isset( $firstComment ) )
				{
					$form->error	= Member::loggedIn()->language()->addToStack( 'mod_error_invalid_action' );
					goto splitFormError;
				}
					
				if ( isset( $values['_split_type'] ) and $values['_split_type'] === 'new' )
				{
					$item = $item::createItem( $firstComment->author(), $firstComment->mapped( 'ip_address' ), DateTime::ts( $firstComment->mapped( 'date' ) ), $values[ $item::$formLangPrefix . 'container' ] );
					$item->processForm( $values );
					if ( isset( $item::$databaseColumnMap['first_comment_id'] ) )
					{
						$firstCommentIdColumn = $item::$databaseColumnMap['first_comment_id'];
						$item->$firstCommentIdColumn = $firstComment->$commentIdColumn;
					}

					/* Does the first post require moderator approval? */
					if ( $firstComment->hidden() === 1 )
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$column = $item::$databaseColumnMap['hidden'];
							$item->$column = 1;
						}
						elseif ( isset( $item::$databaseColumnMap['approved'] ) )
						{
							$column = $item::$databaseColumnMap['approved'];
							$item->$column = 0;
						}
					}
					/* Or is it hidden? */
					elseif ( $firstComment->hidden() === -1 )
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$column = $item::$databaseColumnMap['hidden'];
						}
						elseif ( isset( $item::$databaseColumnMap['approved'] ) )
						{
							$column = $item::$databaseColumnMap['approved'];
						}

						$item->$column = -1;
					}

					$item->save();

				}
				else
				{
					$item = $item::loadFromUrl( $values['_split_into_url'] );

					if ( !$item->canView() )
					{
						throw new DomainException;
					}
				}
				
				foreach ( $comments as $comment )
				{
					/* Remove featured comment associations */
					if( IPS::classUsesTrait( $item, 'IPS\Content\MetaData' ) AND $comment->isFeatured() AND $oldItem )
					{
						Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->unfeatureComment( $oldItem, $comment );
					}

					/* Remove solved associations */
					if ( $oldItem and IPS::classUsesTrait( $oldItem, 'IPS\Content\Solvable' ) and $oldItem->isSolved() and ( $oldItem->mapped('solved_comment_id') === $comment->$commentIdColumn ) )
					{
						$oldItem->toggleSolveComment( $comment->$commentIdColumn, FALSE );
					}

					if( $comment == $firstComment AND $comment->hidden() !== 0 )
					{
						if ( isset( $comment::$databaseColumnMap['hidden'] ) )
						{
							$column = $comment::$databaseColumnMap['hidden'];
							$comment->$column = 0;
						}
						elseif ( isset( $comment::$databaseColumnMap['approved'] ) )
						{
							$column = $comment::$databaseColumnMap['approved'];
							$comment->$column = 1;
						}
					}

					$comment->move( $item, TRUE );
				}

				$item->rebuildFirstAndLastCommentData();
				$oldItem->rebuildFirstAndLastCommentData();

				/* Option to do some post split stuff */
				if ( method_exists( $item, 'splitComplete' ) )
				{
					$item->splitComplete( $oldItem, $item, $comments );
				}

				/* Now reindex all of the comments - we do this last to prevent the "is last comment" flag continously bouncing around while the comments are still being moved */
				foreach( $comments as $comment )
				{
					/* Add to search index */
					if( SearchContent::isSearchable( $comment ) )
					{
						Index::i()->index( $comment );
					}
				}
				
				Index::i()->rebuildAfterMerge( $item );
				Index::i()->rebuildAfterMerge( $oldItem );
				
				/* Log it */
				Session::i()->modLog( 'modlog__action_split', array(
					$item::$title					=> TRUE,
					$item->url()->__toString()		=> FALSE,
					$item->mapped( 'title' )			=> FALSE,
					$oldItem->url()->__toString()	=> FALSE,
					$oldItem->mapped( 'title' )		=> FALSE
				), $item );

				Output::i()->redirect( $firstComment->url() );
			}
			else
			{
				/* Label for goto command */
				splitFormError:
				$this->_setBreadcrumbAndTitle( $item );
				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
				
				if ( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( Output::i()->output  );
				}
				else
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
				}
			}
		}
		elseif ( Request::i()->modaction == 'merge' )
		{
			if ( !( count( Request::i()->multimod ) > 1 ) )
			{
				Output::i()->error( 'cannot_merge_one_post', '1S136/S', 403, '' );
			}
			
			$comments	= array();
			$authors	= array();
			$content	= array();
			foreach( array_keys( Request::i()->multimod ) AS $id )
			{
				try
				{
					$comments[$id]	= $commentClass::loadAndCheckPerms( $id );
					$content[]		= $comments[$id]->mapped( 'content' );
				}
				catch( Exception $e ) {}
			}
			
			$form = new Form;
			$form->class = 'ipsForm--vertical ipsForm--merge';
			$form->add( new Editor( 'final_comment_content', implode( '<p>&nbsp;</p>', $content ), TRUE, array(
				'app'			=> $item::$application,
				'key'			=> ucwords( $item::$module ),
				'autoSaveKey'	=> 'mod-merge-' . implode( '-', array_keys( $comments ) ),
			) ) );

			if ( $values = $form->values() )
			{
				/* @var $databaseColumnMap array */
				$idColumn			= $item::$databaseColumnId;
				$commentIdColumn	= $commentClass::$databaseColumnId;
				$commentIds			= array_keys( Request::i()->multimod );
				$firstComment		= $commentClass::loadAndCheckPerms( array_shift( $commentIds ) );
				$contentColumn		= $commentClass::$databaseColumnMap['content'];
				$firstComment->$contentColumn = $values['final_comment_content'];
				$firstComment->save();
				
				foreach( $commentIds AS $id )
				{
					try
					{
						$comment = $commentClass::loadAndCheckPerms( $id );
						Db::i()->update( 'core_attachments_map', array(
							'id1'	=> $item->$idColumn,
							'id2'	=> $firstComment->$commentIdColumn,
						), array( 'location_key=? AND id1=? AND id2=?', $item::$application . '_' . IPS::mb_ucfirst( $item::$module ), $item->$idColumn, $comment->$commentIdColumn ) );

						/* Merge likes */
						if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Reactable' ) )
						{
							Db::i()->update( 'core_reputation_index', array( 'type_id' => $firstComment->$commentIdColumn ), array( 'app=? and type=? and type_id=?', $item::$application, $comment::reactionType(), $id ) );
						}

						$comment->delete();
					}
					catch( Exception $e ) {}
				}

				/* Fix duplicated reactions */
				if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Reactable' ) )
				{
					Db::i()->delete( array( 'row1' => 'core_reputation_index', 'row2' => 'core_reputation_index' ), 'row1.id > row2.id AND row1.member_id = row2.member_id AND row1.app = \'' . $item::$application . '\' AND row1.type = \'' . $comment::reactionType() . '\' AND row2.app = \'' . $item::$application . '\' AND row2.type = \'' . $comment::reactionType() . '\' AND row1.type_id = ' . $firstComment->$commentIdColumn . ' AND row2.type_id = ' . $firstComment->$commentIdColumn, NULL, NULL, NULL, 'row1' );
				}

				$item->rebuildFirstAndLastCommentData();

				/* Log it */
				Session::i()->modLog( 'modlog__action_merge_comments', array(
					$firstComment::$title					=> TRUE,
					$firstComment->url()->__toString()		=> FALSE,
					$firstComment->$commentIdColumn			=> FALSE,
				), $item );
				
				Output::i()->redirect( $firstComment->url() );
			}
			else
			{
				$this->_setBreadcrumbAndTitle( $item );
				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
				
				if ( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( Output::i()->output );
				}
				else
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
				}
			}
		}
		elseif ( Request::i()->modaction == 'hide' )
		{
			if ( $commentClass::$hideLogKey )
			{
				$form = new Form;
				$form->class = 'ipsForm--vertical ipsForm--hide';
				$form->add( new Text( 'hide_reason' ) );

				if ( $values = $form->values() )
				{
					foreach( array_keys( Request::i()->multimod ) AS $id )
					{
						try
						{
							$comment = $commentClass::loadAndCheckPerms( $id );
							$comment->modAction( 'hide', NULL, $values['hide_reason'] );
						}
						catch( Exception $e ) { }
					}

					if( ! in_array( 'IPS\Content\Review', class_parents( $commentClass ) ) )
					{
						$item->rebuildFirstAndLastCommentData();
					}
					
					$url = $item->url();
					
					if ( isset( Request::i()->page ) )
					{
						$url = $url->setPage( 'page', (int) Request::i()->page );
					}
					
					Output::i()->redirect( $url );
				}
				else
				{
					$this->_setBreadcrumbAndTitle( $item );
					Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );

					if ( Request::i()->isAjax() )
					{
						Output::i()->sendOutput( Output::i()->output );
					}
					else
					{
						Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
					}
				}
			}
			else
			{
				foreach( array_keys( Request::i()->multimod ) AS $id )
				{
					try
					{
						$comment = $commentClass::loadAndCheckPerms( $id );
						$comment->modAction( 'hide' );
					}
					catch( Exception $e ) { }
				}
				
				$url = $item->url();
				
				if ( isset( Request::i()->page ) )
				{
					$url = $url->setPage( 'page', (int) Request::i()->page );
				}
				
				Output::i()->redirect( $url );
			}

		}
		else
		{
			$object = NULL;

			if( isset( Request::i()->multimod ) AND is_array( Request::i()->multimod ) )
			{
				foreach ( array_keys( Request::i()->multimod ) as $id )
				{
					try
					{
						$object = $commentClass::loadAndCheckPerms( $id );
						if ( Request::i()->modaction === 'delete' AND $object->isFeatured() )
						{
							$item->unfeatureComment( $object );
						}
						$object->modAction( Request::i()->modaction, Member::loggedIn() );
					}
					catch ( Exception $e ) {}
				}
			}

			$item->resyncCommentCounts();
			$item->save();
						
			if ( $object and Request::i()->modaction != 'delete' )
			{
				$url = $object->url();
				
				if ( isset( Request::i()->page ) )
				{
					$url = $url->setQueryString( 'page', (int) Request::i()->page );
				}
				
				Output::i()->redirect( $url );
			}
			else
			{
				$url = $item->url();
				
				if ( isset( Request::i()->page ) )
				{
					$url = $url->setPage( 'page', (int) Request::i()->page );
				}
				
				Output::i()->redirect( $url );
			}
		}
	}

	/**
	 * Form for splitting
	 *
	 * @param Item $item The item
	 * @param Comment|null $comment
	 * @return    Form
	 */
	protected function _splitForm(Item $item, Comment|null $comment = NULL ) : Form
	{
		try
		{
			$container = $item->container();
		}
		catch ( Exception $e )
		{
			$container = NULL;	
		}
		
		$form = new Form;
		if ( $item::canCreate(Member::loggedIn()) )
		{
			$toAdd = array();
			$toggles = array();
							
			foreach ($item::formElements($item) as $k => $field )
			{				
				if ( !in_array( $k, array( 'poll', 'content', 'comment_edit_reason', 'comment_log_edit' ) ) )
				{
					if ( $k === 'container' AND ( $container AND $container->can( 'add' ) ) )
					{
						$field->defaultValue = $container;
						if ( !$field->value )
						{
							$field->value = $field->defaultValue;
						}
					}
					
					if ( !$field->htmlId )
					{
						$field->htmlId = $field->name;
					}
					$toggles[] = $field->htmlId;
					
					$toAdd[] = $field;
				}
			}
			
			$form->add( new Radio( '_split_type', 'new', FALSE, array(
				'options' => array(
					'new'		=> Member::loggedIn()->language()->addToStack( 'split_type_new', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $item::$title ) ) ) ),
					'existing'	=> Member::loggedIn()->language()->addToStack( 'split_type_existing', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $item::$title ) ) ) )
				),
				'toggles' => array( 'new' => $toggles, 'existing' => array( 'split_into_url' ) ),
			) ) );

			foreach ( $toAdd as $field )
			{
				if ( $field->name == $item::$formLangPrefix . 'container' )
				{
					/* Add a custom permission check for splitting comments */
					$field->options['permissionCheck'] = function( $node ) use ( $item )
					{
						try
						{
							/* If the item is in a club, only allow moving to other clubs that you moderate */
							if ( Settings::i()->clubs and IPS::classUsesTrait( $item->container(), 'IPS\Content\ClubContainer' ) and $item->container()->club()  )
							{
								return $item::modPermission( 'move', Member::loggedIn(), $node ) and $node->can( 'add' ) ;
							}

							if ( $node->can( 'add' ) )
							{
								return true;
							}
						}
						catch( OutOfBoundsException $e ) { }

						return false;
					};
					if ( Settings::i()->clubs and IPS::classUsesTrait( $item->container(), 'IPS\Content\ClubContainer' ) )
					{
						$field->options['clubs'] = TRUE;
					}
				}

				$form->add( $field );
			}
		}
		$form->add( new FormUrl( '_split_into_url', NULL, FALSE, array(), function ( $val ) use ( $item )
		{
			if ( Request::i()->_split_type == 'existing' OR !isset( Request::i()->_split_type ) )
			{
				try
				{
					/* Validate the URL */
					$test = $item::loadFromUrl( $val );

					if ( !$test->canView() )
					{
						throw new InvalidArgumentException;
					}

					/* Make sure the URL matches the content type we're splitting */
					foreach( array( 'app', 'module', 'controller') as $index )
					{
						if( $test->url()->hiddenQueryString[ $index ] != $val->hiddenQueryString[ $index ] )
						{
							throw new InvalidArgumentException;
						}
					}
				}
				catch ( Exception $e )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_url_bad_item', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( '__defart_' . $item::$title ) ) ) ) );
				}

				if( !$test->canMerge() )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'no_merge_permission', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( '__defart_' . $item::$title ) ) ) ) );
				}
			}
		}, NULL, NULL, 'split_into_url' ) );

		/* multi mod doesn't support moderation alerts , so if $comment isn't set, we can't use it */
		if( $comment )
		{
			$this->moderationAlertField($form, $comment );
		}

		return $form;
	}

	/**
	 * Retrieve content tagged the same
	 *
	 * @param int $limit	How many items should be returned
	 *
	 * @note	Used with a widget, but can be used elsewhere too
	 * @return	array|NULL
	 */
	public function getSimilarContent( int $limit = 5 ): array|NULL
	{
		if( !isset( static::$contentModel ) )
		{
			return NULL;
		}

		$class = static::$contentModel;
		if( !is_subclass_of( $class, 'IPS\Content\Item' ) )
		{
			return NULL;
		}

		try
		{
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$items = [];

			/* Are we using elasticsearch? */
			if ( Settings::i()->search_method == 'elastic' )
			{
				$query = Query::init( Member::loggedIn() );
				$query->resultsToGet = $limit;
				$query->filterByMoreLikeThis( $item );
				$query->filterByLastUpdatedDate( DateTime::create()->sub( new DateInterval( 'P2Y' ) ) );
				$results = $query->search();
				$results->init();

				foreach( $results as $result )
				{
					$object = $result->asArray()['indexData'];
					$class = $object['index_class'];
					$loaded = $class::loadAndCheckPerms( $object['index_object_id'] );

					if ( $loaded instanceof Item)
					{
						$obj = $loaded;
					}
					else
					{
						$obj = $loaded->item();
					}

					$items[] = $obj;
				}
			}
			else
			{
				if ( !IPS::classUsesTrait( $item, 'IPS\Content\Taggable' ) or ( $item->tags() === NULL and $item->prefix() === NULL ) )
				{
					return NULL;
				}

				/* Store tags in array, so that we can add a prefix if set */
				$tags = $item->tags() ?: array();
				$tags[] = $item->prefix();

				/* Build the where clause */
				$where = array(
					array('(' . Db::i()->in( 'tag_text', $tags ) . ')'),
					array('!(tag_meta_app=? and tag_meta_area=? and tag_meta_id=?)', $class::$application, $class::$module, Request::i()->id),
					array('(' . Db::i()->findInSet( 'tag_perm_text', Member::loggedIn()->groups ) . ' OR ' . 'tag_perm_text=? )', '*'),
					array('tag_perm_visible=1')
				);

				/* Allow the item to manipulate the query if needed */
				if ( $item->similarContentFilter() )
				{
					$where = array_merge( $where, $item->similarContentFilter() );
				}

				$select = Db::i()->select(
					'tag_meta_app,tag_meta_area,tag_meta_id',
					'core_tags',
					$where,
					'tag_added DESC',
					array(0, $limit),
					array('tag_meta_app', 'tag_meta_area', 'tag_meta_id', 'tag_added')
				)->join(
					'core_tags_perms',
					array('tag_perm_aai_lookup=tag_aai_lookup')
				);

				foreach ( $select as $result )
				{
					foreach ( Application::load( $result['tag_meta_app'] )->extensions( 'core', 'ContentRouter' ) as $key => $router )
					{
						foreach ( $router->classes as $itemClass )
						{
							if ( $itemClass::$module == $result['tag_meta_area'] )
							{
								try
								{
									$items[$result['tag_meta_id']] = $itemClass::loadAndCheckPerms( $result['tag_meta_id'] );
									break;
								}
								catch ( Exception $e )
								{
								}
							}
						}
					}

				}
			}

			return $items;
		}
		catch ( Exception $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	protected function _coverPhotoStorageExtension(): string
	{
		$class = static::$contentModel;
		return $class::$coverPhotoStorageExtension;
	}
	
	/**
	 * Set Cover Photo
	 *
	 * @param	CoverPhoto	$photo	New Photo
	 * @return	void
	 */
	protected function _coverPhotoSet( CoverPhoto $photo ): void
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			/* @var $databaseColumnMap array */
			$photoColumn = $class::$databaseColumnMap['cover_photo'];
			$item->$photoColumn = (string) $photo->file;
			
			$offsetColumn = $class::$databaseColumnMap['cover_photo_offset'];
			$item->$offsetColumn = $photo->offset;
			
			$item->save();
		}
		catch ( OutOfRangeException $e ){}
	}

	/**
	 * Get Cover Photo
	 *
	 * @return	CoverPhoto
	 */
	protected function _coverPhotoGet(): CoverPhoto
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			
			return $item->coverPhoto();
		}
		catch ( OutOfRangeException $e )
		{
			return new CoverPhoto;
		}
	}
	
	/**
	 * Reveal
	 *
	 * @return	void
	 */
	protected function reveal(): void
	{
		Session::i()->csrfCheck();
		
		if( !Member::loggedIn()->modPermission( 'can_view_anonymous_posters' ) )
		{
			Output::i()->error( 'node_error', '2S136/1V', 403, '' );
		}
		
		$class = static::$contentModel;
		$item = $class::loadAndCheckPerms( Request::i()->id );
		
		if( isset( Request::i()->comment ) )
		{
			$class = $class::$commentClass;
			$id = Request::i()->comment;
		}
		else
		{
			$id = Request::i()->id;
		}
		
		try
		{
			$anonymousAuthor = Db::i()->select( 'anonymous_member_id', 'core_anonymous_posts', array( 'anonymous_object_class=? and anonymous_object_id=?', $class, $id )  )->first();
		}
		catch ( UnderflowException $e )
		{
			if( Request::i()->isAjax() )
			{
				Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicHover( Member::loggedIn()->language()->addToStack( 'anon_user_deleted' ) );
				return;
			}
			Output::i()->error( 'anon_user_deleted', '2S136/1W', 403, '' );
		}
		
		$author = Member::load( $anonymousAuthor );

		$addWarningUrl = Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$author->member_id}", 'front', 'warn_add', array( $author->members_seo_name ) );
		if ( isset( Request::i()->wr ) )
		{
			$addWarningUrl = $addWarningUrl->setQueryString( 'ref', Request::i()->wr );
		}
		
		if( Request::i()->isAjax() )
		{
			Output::i()->output = Theme::i()->getTemplate( 'profile', 'core' )->hovercard( $author, $addWarningUrl );
		}
		else
		{
			Output::i()->redirect( $author->url() );
		}
	}
	
	/**
	 * Reveal Comment
	 *
	 * @return	void
	 */
	protected function revealComment(): void
	{
		$this->reveal();
	}

	/**
	 * A convenient hook point to finish any set up in manage()
	 *
	 * @param Item $item	The item that is being set up in manage()
	 * @return	void
	 */
	protected function finishManage( Item $item ): void
	{
	}

	/**
	 * Get the moderation alert fields
	 *
	 * @param Form $form
	 * @param Content $item
	 * @return void
	 */
	protected function moderationAlertField( Form $form, Content $item ): void
	{
		if( Member::loggedIn()->modPermission('can_manage_alerts') AND $item->author()->member_id )
		{
			$form->add( new YesNo( 'moderation_alert', FALSE, FALSE, array( 'togglesOn' => array( 'moderation_alert_title', 'moderation_alert_content','moderation_alert_anonymous','moderation_alert_reply' ) ) ) );
			Member::loggedIn()->language()->words[ 'moderation_alert'] = Member::loggedIn()->language()->addToStack('moderation_alert_name', FALSE, ['sprintf' => [ $item->author()->name ] ] );
			$form->add( new Text( 'moderation_alert_title', NULL, TRUE, array( 'maxLength' => 255 ), NULL, NULL, NULL, 'moderation_alert_title' ) );
			$form->add( new Editor( 'moderation_alert_content', NULL, TRUE, array( 'app' => 'core', 'key' => 'Alert', 'autoSaveKey' => 'createAlert', 'attachIds' => NULL ), NULL, NULL, NULL, 'moderation_alert_content' ) );
			$form->add( new YesNo( 'moderation_alert_anonymous', FALSE, TRUE, array( 'togglesOff' => array( 'moderation_alert_reply') ), NULL, NULL, NULL, 'moderation_alert_anonymous' ) );
			$form->add( new Radio( 'moderation_alert_reply', 0, TRUE, array( 'disabled' => (bool) Member::loggedIn()->members_disable_pm, 'options' => array( '0' => 'alert_no_reply', '1' => 'alert_can_reply', '2' => 'alert_must_reply' ) ), NULL, NULL, NULL, 'moderation_alert_reply' ) );

			if ( Member::loggedIn()->members_disable_pm )
			{
				Member::loggedIn()->language()->words['alert_reply_desc'] = Member::loggedIn()->language()->get('alert_reply__nopm_desc');
			}
		}
	}

	/**
	 * Send the alert
	 *
	 * @param array $values
	 * @param Item|Comment $content
	 * @return Alert
	 */
	protected function sendModerationAlert(array $values, Item|Comment $content ) : Alert
	{
		$values['alert_title'] = $values['moderation_alert_title'];
		$values['alert_content'] = $values['moderation_alert_content'];
		$values['alert_recipient_type'] = 'user';
		$values['alert_recipient_user'] = $content->author()->member_id;
		$values['alert_anonymous'] = $values['moderation_alert_anonymous'];
		$values['alert_reply'] = $values['moderation_alert_reply'];
		return Alert::_createFromForm( $values, NULL );
	}

	/**
	 * Show Helpful
	 *
	 * @return    void
	 * @throws Exception
	 */
	protected function showHelpful(): void
	{
		try
		{
			$class = static::$contentModel;
			$commentClass = $class::$commentClass;
			$idColumn = $commentClass::$databaseColumnId;

			if( !IPS::classUsesTrait( $commentClass, 'IPS\Content\Helpful' ) or !Member::loggedIn()->group['gbw_view_helpful'] )
			{
				Output::i()->error( 'node_error', '2S136/29', 404, 'node_error' );
			}

			$comment = $commentClass::loadAndCheckPerms( Request::i()->answer );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S136/2A', 404, 'node_error' );
		}

		$table = new TableDb( 'core_solved_index', $comment->url()->setQueryString( array( 'do' => 'showHelpful' ) ), array( 'comment_id=?', $comment->$idColumn ) );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'helpfulLogTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'helpfulLog' );

		Output::i()->metaTags['robots'] = 'noindex';
		Output::i()->output = $table;
	}

	/**
	 * Feature promotion dialog
	 *
	 * @return    void
	 * @throws ErrorException
	 */
	protected function feature(): void
	{
		try
		{
			$class = static::$contentModel;
			$idColumn = $class::$databaseColumnId;
			$item = $class::loadAndCheckPerms( Request::i()->id );
		}
		catch( Exception )
		{
			Output::i()->error( 'page_not_found', '2C356/6', 404, '' );
		}

		if ( isset( Request::i()->comment ) )
		{
			try
			{
				$commentClass = $item::$commentClass;
				$comment = $commentClass::loadAndCheckPerms( Request::i()->comment );

				/* Is this the first comment in an item? */
				if ( $item::$firstCommentRequired and $comment->isFirst() )
				{
					/* Yeah, so just promote the item */
					$objectToPromote = $item;
				}
				else
				{
					$idColumn = $commentClass::$databaseColumnId;
					$objectToPromote = $comment;
				}
			}
			catch( Exception )
			{
				Output::i()->error( 'page_not_found', '2C356/H', 404, '' );
			}
		}
		else
		{
			$objectToPromote = $item;
		}

		/* Check that the object can be promoted */
		if( ! $objectToPromote->canFeature() )
		{
			Output::i()->error( 'no_promote_permission', '2S356/A', 403, '' );
		}

		$form = new Form( 'form', 'promote_submit' );
		$form->class = 'cPromoteDialog';

		$form->add( new YesNo( 'promote_toggle_custom', false, false, array(
			'togglesOn' => array( 'promote_title', 'promote_content' )
		) ) );

		$form->add( new Text( 'promote_social_title_internal', Feature::objectTitle( $objectToPromote, true ), FALSE, [], NULL, NULL, NULL, 'promote_title' ) );
		$form->add( new TextArea( 'promote_social_content_internal', Feature::objectContent( $objectToPromote ) ?: NULL, FALSE, array( 'maxLength' => 3000, 'rows' => 6 ), NULL, NULL, NULL, 'promote_content'  ) );

		/* Existing media */
		try
		{
			if ( $images = $objectToPromote->contentImages( 20 ) )
			{
				$form->addHtml( Theme::i()->getTemplate( 'promote', 'core', 'front' )->promoteDialogImages( $images ) );
			}
		}
		catch( BadMethodCallException $e ) { }

		/* Upload box */
		$uploader = new Upload( 'promote_media', NULL, FALSE, array(
			'multiple' => TRUE,
			'storageExtension' => 'core_Promote',
			'maxFiles' => 10,
			'image' => TRUE,
			'maxFileSize' => 3,
			'template' => "promote.imageUpload",
		) );

		$uploader->template = array( Theme::i()->getTemplate( 'promote', 'core', 'front' ), 'promoteAttachments' );
		$form->add( $uploader );

		/* Should we alert the member to say thanks? */
		$this->moderationAlertField( $form, $objectToPromote );

		/* Are we featuring a comment? We still use the metadata:featuredComments system for this as it's the most efficient */
		if ( $objectToPromote instanceof Comment and in_array( 'core_FeaturedComments', $item::supportedMetaDataTypes() ) )
		{
			$form->add( new YesNo( 'promote_toggle_comment', false, false, array(
				'togglesOn' => array( 'feature_mod_note' )
			) ) );

			$form->add( new Text( 'feature_mod_note', NULL, FALSE, [], NULL, NULL, NULL, 'feature_mod_note' ) );
		}

		/* Saving? */
		if ( $values = $form->values() )
		{
			$media = array();
			if ( is_array( $values['promote_media'] ) )
			{
				foreach( $values['promote_media'] as $key => $file )
				{
					$media[] = (string) $file;
				}
			}

			/* Content images */
			$saveImages = array();
			if ( isset( $images ) AND $images )
			{
				foreach( $images as $image )
				{
					foreach( $image as $extension => $path )
					{
						if ( Request::i()->attach_files and in_array( $path, array_keys( Request::i()->attach_files ) ) )
						{
							$saveImages[] = array( $extension => $path );
						}
					}
				}
			}

			$promote = new Feature();
			$promote->class = get_class( $objectToPromote );
			$promote->class_id = $objectToPromote->$idColumn;
			$promote->added_by = Member::loggedIn()->member_id;
			$promote->text = $values['promote_toggle_custom'] ? [ 'internal' => $values['promote_social_content_internal'] ] : '';
			$promote->images = $saveImages;
			$promote->media = $media;
			$promote->added = time();
			$promote->form_data = array (
				'internal' => $values['promote_toggle_custom'] ? array( 'title' => $values['promote_social_title_internal'] ) : null
			);

			$promote->author_id = $item->author()->member_id ?: 0;
			$promote->save();

			/* Now set the featured item */
			$objectToPromote->setFeatured( true );

			/* Is a comment, and we're featuring it at the top? */
			if ( $objectToPromote instanceof Comment and $values['promote_toggle_comment'] )
			{
				Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->featureComment( $objectToPromote->item(), $objectToPromote, $values['feature_mod_note'], Member::loggedIn() );

				Session::i()->modLog( 'modlog__featured_comment', array(
					(string) $objectToPromote->url()	=> FALSE,
					$item->mapped('title')		=> FALSE
				) );
			}

			/* Send an alert if required */
			if( isset( $values['moderation_alert_content'] ) AND $values['moderation_alert_content'] )
			{
				$this->sendModerationAlert( $values, $objectToPromote );
			}

			/* Points */
			$objectToPromote->author()->achievementAction( 'core', 'ContentPromotion', [
				'content' => $objectToPromote,
				'promotype' => 'promote'
			] );

			Webhook::fire( 'content_promoted', $item );

			Output::i()->redirect( $objectToPromote->url(), Member::loggedIn()->language()->addToStack( 'promote_complete' ) );
		}
		else
		{
			$output = Theme::i()->getTemplate( 'promote', 'core' )->promoteDialog( Member::loggedIn()->language()->addToStack('promote_social_title'), $form );
		}

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('front_promote.js', 'core' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/promote.css' ) );

		Output::i()->title = Member::loggedIn()->language()->addToStack('promote_social_button');
		Output::i()->output = $output;
	}

	/**
	 * Deletes a featured item
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function unfeature() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$class = static::$contentModel;
			$idColumn = $class::$databaseColumnId;
			$item = $class::loadAndCheckPerms( Request::i()->id );
		}
		catch( Exception )
		{
			Output::i()->error( 'page_not_found', '2C356/6', 404, '' );
		}

		if ( isset( Request::i()->comment ) )
		{
			try
			{
				$commentClass = $item::$commentClass;
				$comment = $commentClass::loadAndCheckPerms( Request::i()->comment );
				$idColumn = $commentClass::$databaseColumnId;
				$objectToPromote = $comment;
			}
			catch( Exception )
			{
				Output::i()->error( 'page_not_found', '2C356/I', 404, '' );
			}
		}
		else
		{
			$objectToPromote = $item;
		}

		/* Check that the object can be promoted */
		if( ! $objectToPromote->canFeature() )
		{
			Output::i()->error( 'no_promote_permission', '2S356/G', 403, '' );
		}

		try
		{
			$promote = Feature::loadByClassAndId( get_class( $objectToPromote ), $objectToPromote->$idColumn );

			/* Now set the featured item */
			$objectToPromote->setFeatured( false );

			/* .. and delete */
			$promote?->delete();

			/* Is a comment, and we're featuring it at the top? */
			if ( $objectToPromote instanceof Comment )
			{
				if ( Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->isCommentShownAtTheTop( $objectToPromote ) )
				{
					Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->unfeatureComment( $objectToPromote->item(), $objectToPromote, Member::loggedIn() );

					Session::i()->modLog( 'modlog__unfeatured_comment', array(
						(string) $objectToPromote->url()	=> FALSE,
						$item->mapped('title')		=> FALSE
					) );
				}
			}

			if( Request::i()->fromItem )
			{
				Output::i()->redirect( $item->url(), 'promote_item_deleted' );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=modcp&controller=modcp&tab=featured', 'front', 'modcp_featured' ), 'promote_item_deleted' );
			}
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'page_not_found', '2C356/3', 404, '' );
		}
	}

	/**
	 * Edit featured
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function editFeatured() : void
	{
		try
		{
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			$idColumn = $item::$databaseColumnId;
		}
		catch( Exception )
		{
			Output::i()->error( 'page_not_found', '2C356/5', 404, '' );
		}

		if ( isset( Request::i()->comment ) )
		{
			try
			{
				/* @var $item Content */
				$commentClass = $item::$commentClass;
				$comment = $commentClass::loadAndCheckPerms( Request::i()->comment );
				$class = $commentClass;

				/* Is this the first comment in an item? */
				if ( $item::$firstCommentRequired and $comment->isFirst() )
				{
					/* Yeah, so just promote the item */
					$objectToEdit = $item;
				}
				else
				{
					$idColumn = $commentClass::$databaseColumnId;
					$objectToEdit = $comment;
				}
			}
			catch( Exception )
			{
				Output::i()->error( 'page_not_found', '2C356/H', 404, '' );
			}
		}
		else
		{
			$objectToEdit = $item;
		}

		try
		{
			$promote = Feature::loadByClassAndId( $class, $objectToEdit->$idColumn );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'page_not_found', '2C356/5', 404, '' );
		}

		if ( ! $promote or $promote->added_by != Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission', '2C366/2', 403, '' );
		}

		$title = NULL;
		$settings = $promote->form_data;

		if ( ! empty( $settings['internal']['title'] ) )
		{
			$title = $settings['internal']['title'];
		}

		$form = new Form( 'form', 'save' );
		$form->class = 'ipsForm--vertical cPromoteDialog ipsForm--edit-featured';
		$form->add( new Text( 'promote_social_title_internal', $title, FALSE ) );
		$form->add( new TextArea( 'promote_internal_text', $promote->getText(), FALSE, array( 'rows' => 10 ) ) );

		/* Existing media */
		try
		{
			if ( $images = $item->contentImages( 20 ) )
			{
				$form->addHtml( Theme::i()->getTemplate( 'promote' )->promoteDialogImages( $images, $promote ) );
			}
		}
		catch( BadMethodCallException ) { }

		/* Upload box */
		$existingMedia = array();
		if ( $promote )
		{
			foreach( $promote->media as $file )
			{
				try
				{
					$existingMedia[] = File::get( 'core_Promote', $file );
				}
				catch ( OutOfRangeException $e ) { }
			}
		}

		$uploader = new Upload( 'promote_media', $existingMedia, FALSE, array(
			'multiple' => TRUE,
			'storageExtension' => 'core_Promote',
			'maxFiles' => 10,
			'image' => TRUE,
			'maxFileSize' => 3,
		) );

		$form->add( $uploader );

		/* Saving? */
		if ( $values = $form->values() )
		{
			/* Content images */
			$saveImages = array();
			if ( $images )
			{
				foreach( $images as $image )
				{
					foreach( $image as $extension => $path )
					{
						if ( Request::i()->attach_files and in_array( $path, array_keys( Request::i()->attach_files ) ) )
						{
							$saveImages[] = array( $extension => $path );
						}
					}
				}
			}

			$media = array();
			if ( is_array( $values['promote_media'] ) )
			{
				foreach( $values['promote_media'] as $key => $file )
				{
					$media[] = (string) $file;
				}
			}

			$settings['internal']['title'] = $values['promote_social_title_internal'];

			$promote->images = $saveImages;
			$promote->media = $media;
			$promote->form_data = $settings;
			$promote->setText( $values['promote_internal_text'] );
			$promote->save();

			Webhook::fire( 'content_promoted', $item );

			/* Redirect back to the list */
			Output::i()->redirect( Url::internal( 'app=core&module=modcp&controller=modcp&tab=featured', 'front', 'modcp_featured' ), 'saved' );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack('promote_internal_edit');
		Output::i()->output = Theme::i()->getTemplate( 'promote', 'core' )->edit( $form );
	}

	/**
	 * Mark as spam / unmark as spam controllers (but not currently used)
	 */
	protected function spam() : void
	{
		try
		{
			$itemClass = static::$contentModel;
			$item = $itemClass::load( Request::i()->id );
			Bridge::i()->markItemAsSpam( $item );
		}
		catch( OutOfRangeException ){}
	}

	protected function unspam() : void
	{
		try
		{
			$itemClass = static::$contentModel;
			$item = $itemClass::load( Request::i()->id );
			Bridge::i()->markItemAsNotSpam( $item );
		}
		catch( OutOfRangeException ){}
	}

	protected function _spam( $commentClass, $comment, $item ) : void
	{
		Bridge::i()->markCommentAsSpam( $commentClass, $comment, $item );
	}

	protected function _unspam( $commentClass, $comment, $item ) : void
	{
		Bridge::i()->markCommentAsNotSpam( $commentClass, $comment, $item );
	}
}