<?php
/**
 * @brief		Custom table helper for gallery images to override move menu
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Apr 2014
 */

namespace IPS\gallery\Image;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\gallery\Album;
use IPS\gallery\Category;
use IPS\gallery\Image;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfBoundsException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom table helper for gallery images to override move menu
 */
class Table extends Content
{
	/**
	 * Constructor
	 *
	 * @param	string					$class				Database table
	 * @param	Url			$baseUrl			Base URL
	 * @param	array|null				$where				WHERE clause (To restrict to a node, use $container instead)
	 * @param	Model|NULL	$container			The container
	 * @param	bool|null				$includeHidden		Flag to pass to getItemsWithPermission() method for $includeHiddenContent, defaults to NULL
	 * @param	string|NULL				$permCheck			Permission key to check
	 * @param	bool					$honorPinned		Honor pinned status (show pinned items first)
	 */
	public function __construct( string $class, Url $baseUrl, ?array $where=NULL, ?Model $container=NULL, ?bool $includeHidden= Filter::FILTER_AUTOMATIC, ?string $permCheck='view', bool $honorPinned=TRUE )
	{
		/* Are we changing the thumbnail viewing size? */
		if( isset( Request::i()->thumbnailSize ) )
		{
			Session::i()->csrfCheck();

			Request::i()->setCookie( 'thumbnailSize', Request::i()->thumbnailSize, DateTime::ts( time() )->add( new DateInterval( 'P1Y' ) ) );

			/* Do a 303 redirect to prevent indexing of the CSRF link */
			Output::i()->redirect( Request::i()->url(), '', 303 );
		}

		parent::__construct( $class, $baseUrl, $where, $container, $includeHidden, $permCheck, $honorPinned );
	}

	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues = NULL ): array
	{
		$this->sortOptions['title'] = $this->sortOptions['title'] . ' ASC, image_id ';

		return parent::getRows( $advancedSearchValues );
	}

	/**
	 * Build the form to move images
	 *
	 * @param	Model		$currentContainer	Current image container
	 * @param	string				$class 				Class to use
	 * @param	array 				$extraAlbumOptions	Additional options to use for the album helper (e.g. to force owner)
	 * @param	Member|NULL		$member				Member to check, or NULL for currently logged in member.
	 * @return	Form
	 */
	static public function buildMoveForm( Model $currentContainer, string $class, array $extraAlbumOptions=array(), ?Member $member=NULL ) : Form
	{
		$member = $member ?: Member::loggedIn();
		
		$form = new Form( 'form', 'move' );
		$form->class = 'ipsForm--vertical ipsForm--move-images';
		if ( Category::canOnAny('add') and Album::canOnAny('add') )
		{
			$options = array( 'category' => 'image_category', 'album' => 'image_album' );
			$toggles = array( 'category' => array( 'move_to_category' ), 'album' => array( 'move_to_album' ) );
			$extraFields = array();
			
			if ( Image::modPermission( 'edit' ) and Db::i()->select( 'COUNT(*)', 'gallery_categories', array( array( 'category_allow_albums>0' ), array( '(' . Db::i()->findInSet( 'core_permission_index.perm_' . Category::$permissionMap['add'], Member::loggedIn()->groups ) . ' OR ' . 'core_permission_index.perm_' . Category::$permissionMap['add'] . '=? )', '*' ) ) )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . Category::$databaseTable . "." . Category::$databasePrefix . Category::$databaseColumnId, Category::$permApp, Category::$permType ) )->first() )
			{
				$options['new_album'] = 'move_to_new_album';

				foreach ( Album::formFields( NULL, TRUE, Request::i()->move_to === 'new_album' ) as $field )
				{
					if ( !$field->htmlId )
					{
						$field->htmlId = $field->name . '_id';
					}
					$toggles['new_album'][] = $field->htmlId;
					
					$extraFields[] = $field;
				}
			}
			
			$form->add( new Radio( 'move_to', NULL, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
			foreach ( $extraFields as $field )
			{
				$form->add( $field );
			}
		}

		$form->add( new Node( 'move_to_category', NULL, NULL, array(
			'clubs'				=> true, 
			'class'				=> 'IPS\\gallery\\Category', 
			'permissionCheck'	=> function( $node ) use ( $currentContainer, $class, $member )
			{
				/* If the image is in the same category already, we can't move it there */
				if( $currentContainer instanceof Category and $currentContainer->id == $node->id )
				{
					return false;
				}

				/* If the category requires albums, we cannot move images directly to it */
				if( $node->allow_albums == 2 )
				{
					return false;
				}

				/* If the category is a club, check mod permissions appropriately */
				try
				{
					/* If the item is in a club, only allow moving to other clubs that you moderate */
					if ( $currentContainer and IPS::classUsesTrait( $currentContainer, 'IPS\Content\ClubContainer' ) and $currentContainer->club()  )
					{
						/* @var Item $class */
						return $class::modPermission( 'move', Member::loggedIn(), $node ) and $node->can( 'add' ) ;
					}
				}
				catch( OutOfBoundsException ) { }

				/* Can we add in this category? */
				if ( $node->can( 'add', $member ) )
				{
					return true;
				}
				
				return false;
			}
		), function( $val ) {
			if ( !$val and isset( Request::i()->move_to ) and Request::i()->move_to == 'category' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'move_to_category' ) );

		$form->add( new Node( 'move_to_album', NULL, NULL, array_merge( array(
			'class' 				=> 'IPS\\gallery\\Album', 
			'permissionCheck' 		=> function( $node ) use ( $currentContainer, $member )
			{
				/* If the image is in the same album already, we can't move it there */
				if( $currentContainer instanceof Album and $currentContainer->id == $node->id )
				{
					return false;
				}

				/* Do we have permission to add? */
				if( !$node->can( 'add', $member ) )
				{
					return false;
				}

				/* Have we hit an images per album limit? */
				if( $node->owner()->group['g_img_album_limit'] AND ( $node->count_imgs + $node->count_imgs_hidden ) >= $node->owner()->group['g_img_album_limit'] )
				{
					return false;
				}
				
				return true;
			}
		), $extraAlbumOptions ), function( $val ) {
			if ( !$val and isset( Request::i()->move_to ) and Request::i()->move_to == 'album' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'move_to_album' ) );

		return $form;
	}

	/**
	 * Get the form to move items
	 *
	 * @return string|array
	 */
	protected function getMoveForm(): array|string
	{
		$class = $this->class;
		$params = array();

		$currentContainer = $this->container;

		$form = static::buildMoveForm( $currentContainer, $class );
		
		if ( $values = $form->values() )
		{
			if ( isset( $values['move_to'] ) )
			{
				if ( $values['move_to'] == 'new_album' )
				{
					$albumValues = $values;
					unset( $albumValues['move_to'] );
					unset( $albumValues['move_to_category'] );
					unset( $albumValues['move_to_album'] );
					
					$target = new Album;
					$target->saveForm( $target->formatFormValues( $albumValues ) );
					$target->save();
				}
				else
				{						
					$target = ( Request::i()->move_to == 'category' ) ? $values['move_to_category'] : $values['move_to_album'];
				}
			}
			else
			{
				$target = $values['move_to_category'] ?? $values['move_to_album'];
			}

			$params[] = $target;
			$params[] = FALSE;

			return $params;
		}

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