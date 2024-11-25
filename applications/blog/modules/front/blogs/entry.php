<?php
/**
 * @brief		View Blog Entry Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		03 Mar 2014
 */

namespace IPS\blog\modules\front\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\blog\Blog;
use IPS\blog\Entry as EntryClass;
use IPS\blog\Entry\Category;
use IPS\Content\Controller;
use IPS\Content\ReadMarkers;
use IPS\core\FrontNavigation;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfBoundsException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function get_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View Blog Entry Controller
 */
class entry extends Controller
{	
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\blog\Entry';

	/**
	 * Entry object
	 */
	protected ?EntryClass $entry = NULL;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		try
		{
			$this->entry = EntryClass::load( Request::i()->id );
				
			if ( !$this->entry->canView( Member::loggedIn() ) )
			{
				Output::i()->error( 'node_error', '2B202/1', 403, '' );
			}

			if( $this->entry->cover_photo )
			{
				Output::i()->metaTags['og:image'] = File::get( 'blog_Entries', $this->entry->cover_photo )->url;
			}
			elseif ( $this->entry->container()->cover_photo )
			{
				Output::i()->metaTags['og:image'] = File::get( 'blog_Blogs', $this->entry->container()->cover_photo )->url;
			}
		}
		catch ( OutOfRangeException )
		{
			if ( !isset( Request::i()->do ) or Request::i()->do !== 'embed' )
			{
				Output::i()->error( 'node_error', '2B202/2', 404, '' );
			}
		}

		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		parent::manage();
		
		$this->entry->container()->clubCheckRules();

		$previous = NULL;
		$next = NULL;

		/* Prev */
		try
		{
			$previous = Db::i()->select(
				'*',
				'blog_entries',
				array( 'entry_blog_id=? AND entry_date<? AND entry_status=? AND entry_is_future_entry=0 AND entry_hidden=?', $this->entry->blog_id, $this->entry->date, "published", 1 ),
				'entry_date DESC'
				,1
			)->first();

			$previous = EntryClass::constructFromData( $previous );
		}
		catch ( UnderflowException ) {}

		/* Next */
		try
		{
			$next = Db::i()->select(
				'*',
				'blog_entries',
				array( 'entry_blog_id=? AND entry_date>? AND entry_status=? AND entry_is_future_entry=0 AND entry_hidden=?', $this->entry->blog_id, $this->entry->date, "published", 1 ),
				'entry_date ASC'
				,1
			)->first();

			$next = EntryClass::constructFromData( $next );
		}
		catch ( UnderflowException ) {}
		
		/* Online User Location */
		if( !$this->entry->container()->social_group )
		{
			Session::i()->setLocation( $this->entry->url(), $this->entry->onlineListPermissions(), 'loc_blog_viewing_entry', array( $this->entry->name => FALSE ) );
		}

		/* Add JSON-ld output */
		Output::i()->jsonLd['blog']	= array(
			'@context'		=> "https://schema.org",
			'@type'			=> "Blog",
			'url'			=> (string) $this->entry->container()->url(),
			'name'			=> $this->entry->container()->_title,
			'description'	=> Member::loggedIn()->language()->addToStack( Blog::$titleLangPrefix . $this->entry->container()->_id . Blog::$descriptionLangSuffix, TRUE, array( 'striptags' => TRUE, 'escape' => TRUE ) ),
			
			'commentCount'	=> $this->entry->container()->_comments,
			'interactionStatistic'	=> array(
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/ViewAction",
					'userInteractionCount'	=> $this->entry->container()->num_views
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/FollowAction",
					'userInteractionCount'	=> EntryClass::containerFollowerCount( $this->entry->container() )
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/CommentAction",
					'userInteractionCount'	=> $this->entry->container()->_comments
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/WriteAction",
					'userInteractionCount'	=> $this->entry->container()->_items
				)
			),
			'blogPost' => array(
				'@context'		=> "https://schema.org",
				'@type'			=> "BlogPosting",
				'url'			=> (string) $this->entry->url(),
				'mainEntityOfPage'	=> (string) $this->entry->url(),
				'name'			=> $this->entry->mapped('title'),
				'headline'		=> $this->entry->mapped('title'),
				'articleBody'	=> $this->entry->truncated( TRUE, NULL ),
				'commentCount'	=> $this->entry->mapped('num_comments'),
				'dateCreated'	=> DateTime::ts( $this->entry->date )->format( DateTime::ATOM ),
				'datePublished'	=> DateTime::ts( $this->entry->publish_date )->format( DateTime::ATOM ),
				'author'		=> array(
					'@type'		=> 'Person',
					'name'		=> Member::load( $this->entry->mapped('author') )->name,
					'url'		=> (string) Member::load( $this->entry->mapped('author') )->url(),
					'image'		=> Member::load( $this->entry->mapped('author') )->get_photo( TRUE, TRUE )
				),
				'publisher'		=> array(
					'@id' => Settings::i()->base_url . '#organization',
					'member' => array(
						'@type'		=> 'Person',
						'name'		=> Member::load( $this->entry->mapped('author') )->name,
						'url'		=> (string) Member::load( $this->entry->mapped('author') )->url(),
						'image'		=> Member::load( $this->entry->mapped('author') )->get_photo( TRUE, TRUE )
					)
				),
				'interactionStatistic'	=> array(
					array(
						'@type'					=> 'InteractionCounter',
						'interactionType'		=> "https://schema.org/ViewAction",
						'userInteractionCount'	=> $this->entry->views
					),
					array(
						'@type'					=> 'InteractionCounter',
						'interactionType'		=> "https://schema.org/FollowAction",
						'userInteractionCount'	=> EntryClass::containerFollowerCount( $this->entry->container() )
					),
					array(
						'@type'					=> 'InteractionCounter',
						'interactionType'		=> "https://schema.org/CommentAction",
						'userInteractionCount'	=> $this->entry->mapped('num_comments')
					)
				)
			)
		);

		if( $this->entry->container()->coverPhoto()->file )
		{
			Output::i()->jsonLd['blog']['image'] = (string) $this->entry->container()->coverPhoto()->file->url;
		}

		if( $this->entry->container()->member_id )
		{
			Output::i()->jsonLd['blog']['author'] = array(
				'@type'		=> 'Person',
				'name'		=> Member::load( $this->entry->container()->member_id )->name,
				'url'		=> (string) Member::load( $this->entry->container()->member_id )->url(),
				'image'		=> Member::load( $this->entry->container()->member_id )->get_photo( TRUE, TRUE )
			);
		}

		if( $this->entry->edit_time )
		{
			Output::i()->jsonLd['blog']['blogPost']['dateModified']	= DateTime::ts( $this->entry->edit_time )->format( DateTime::ATOM );
		}
		else
		{
			Output::i()->jsonLd['blog']['blogPost']['dateModified']	= DateTime::ts( $this->entry->publish_date ?: $this->entry->date )->format( DateTime::ATOM );
		}

		$file = NULL;
		if( $this->entry->image )
		{
			$file = File::get( 'blog_Blogs', $this->entry->image );
		}
		elseif( $this->entry->coverPhoto()->file )
		{
			$file = $this->entry->coverPhoto()->file;
		}
		elseif( $this->entry->container()->coverPhoto()->file )
		{
			$file = $this->entry->container()->coverPhoto()->file;
		}

		if( $file !== NULL )
		{
			try
			{
				$dimensions = $file->getImageDimensions();

				Output::i()->jsonLd['blog']['blogPost']['image'] = array(
					'@type'		=> 'ImageObject',
					'url'		=> (string) $file->url,
					'width'		=> $dimensions[0],
					'height'	=> $dimensions[1]
				);
			}
			/* File does not exist or image is invalid */
			catch( RuntimeException | InvalidArgumentException | DomainException ){}
		}

		/* Display */
		if( Settings::i()->blog_enable_sidebar and $this->entry->container()->sidebar )
		{
			Output::i()->sidebar['contextual'] = Theme::i()->getTemplate('view')->blogSidebar( $this->entry->container()->sidebar );
		}

		/* Breadcrumb */
		Output::i()->breadcrumb = array();
		if ( $club = $this->entry->container()->club() )
		{
			FrontNavigation::$clubTabActive = TRUE;
			Output::i()->breadcrumb = array();
			Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
			Output::i()->breadcrumb[] = array( $club->url(), $club->name );

		}
		else
		{
			Output::i()->breadcrumb['module'] = array( Url::internal( 'app=blog', 'front', 'blogs' ), Member::loggedIn()->language()->addToStack( '__app_blog' ) );
		}


		try
		{
			foreach( $this->entry->container()->category()->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $this->entry->container()->category()->url(), $this->entry->container()->category()->_title );
		} 
		catch ( OutOfRangeException ) {}
		
		/* Set default search option */
		Output::i()->defaultSearchOption = array( 'blog_entry', 'blog_entry_pl' );

		Output::i()->breadcrumb[] = array( $this->entry->container()->url(), $this->entry->container()->_title );
		Output::i()->breadcrumb[] = array( NULL, $this->entry->name );

		Output::i()->output = Theme::i()->getTemplate( 'view' )->entry( $this->entry, $previous, $next );
		return null;
	}

	/**
	 * Return the form for editing. Abstracted so controllers can define a custom template if desired.
	 *
	 * @param	Form	$form	The form
	 * @return	string
	 */
	protected function getEditForm( Form $form ): string
	{
		return $form->customTemplate( array( Theme::i()->getTemplate( 'submit', 'blog' ), 'submitFormTemplate' ) );
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
			$item = EntryClass::loadAndCheckPerms( Request::i()->id );
			if ( !$item->canMove() )
			{
				throw new DomainException;
			}

			$container = $item->container();
			
			$wizard = new Wizard( array(
				'blog'	=> function( $data ) use ( $container ) {
					$data['item'] = Request::i()->id;
					$item = EntryClass::loadAndCheckPerms( $data['item'] );
					$form = new Form;
					$form->class = 'ipsForm--vertical ipsForm--move-blog';
					$form->add( new Node( 'move_to', NULL, TRUE, array(
						'class'				=> get_class( $item->container() ),
						'permissionCheck'	=> function( $node ) use ( $item )
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
							catch( OutOfBoundsException ) { }
							
							return false;
						},
						'clubs'	=> TRUE
					) ) );
					
					if ( $values = $form->values() )
					{
						$data['blog'] = $values['move_to'];
						return $data;
					}
					
					return $form;
				},
				'category' => function( $data ) {
					$item = EntryClass::loadAndCheckPerms( $data['item'] );
					$newBlog = $data['blog'];
					
					$form = new Form;
					
					$categories = Category::roots( NULL, NULL, array( 'entry_category_blog_id=?', $newBlog->id ) );
					$choiceOptions = array( 0 => 'entry_category_choice_new' );
					$choiceToggles = array( 0 => array( 'blog_entry_new_category' ) );
			
					if( count( $categories ) )
					{
						$choiceOptions[1] = 'entry_category_choice_existing';
						$choiceToggles[1] = array( 'entry_category_id' );
					}
					
					$form->add( new Radio( 'entry_category_choice', 0, FALSE, array(
						'options' => $choiceOptions,
						'toggles' => $choiceToggles
					) ) );
			
					if( count( $categories ) )
					{
						$options = array();
						foreach ( $categories as $category )
						{
							$options[ $category->id ] = $category->name;
						}
			
						$form->add( new Select( 'entry_category_id', NULL, FALSE, array( 'options' => $options, 'parse' => 'normal' ), NULL, NULL, NULL, "entry_category_id" ) );
					}
					$form->add( new Text( 'blog_entry_new_category', NULL, TRUE, array(), NULL, NULL, NULL, "blog_entry_new_category" ) );
					$this->moderationAlertField( $form, $item );
					
					if ( $values = $form->values() )
					{
						if ( $data['blog'] === NULL OR !$data['blog']->can( 'add' ) )
						{
							Output::i()->error( 'node_move_invalid', '1S136/L', 403, '' );
						}
		
						/* If this item is read, we need to re-mark it as such after moving */
						if( IPS::classUsesTrait( $item, ReadMarkers::class ) )
						{
							$unread = $item->unread();
						}
		
						$item->move( $data['blog'], FALSE );
						
						
						if( $values['entry_category_choice'] == 1 and $values['entry_category_id'] )
						{
							$item->category_id = $values['entry_category_id'];
						}
						else
						{
							$newCategory = new Category;
							$newCategory->name = $values['blog_entry_new_category'];
							$newCategory->seo_name = Friendly::seoTitle( $values['blog_entry_new_category'] );
				
							$newCategory->blog_id = $item->blog_id;
							$newCategory->save();
				
							$item->category_id = $newCategory->id;
						}
						
						$item->save();
		
						/* Mark it as read */
						if( IPS::classUsesTrait( $item, ReadMarkers::class ) and $unread == 0 )
						{
							$item->markRead( NULL, NULL, NULL, TRUE );
						}
						
						if( isset( $values['moderation_alert_content']) AND $values['moderation_alert_content'] )
						{
							$this->sendModerationAlert($values, $item);
						}
		
						Session::i()->modLog( 'modlog__action_move', array( $item::$title => TRUE, $item->url()->__toString() => FALSE, $item->mapped( 'title' ) ?: ( method_exists( $item, 'item' ) ? $item->item()->mapped( 'title' ) : NULL ) => FALSE ),  $item );
		
						Output::i()->redirect( $item->url() );
					}
					
					return $form;
				}
			), $item->url()->setQueryString( array( 'do' => 'move' ) ) );
			
			$this->_setBreadcrumbAndTitle( $item );
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'move_item', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( EntryClass::$title ) ) ) );
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->box( $wizard, array( 'i-padding_2' ) );
		}
		catch ( Exception )
		{
			Output::i()->error( 'node_error', '2S136/D', 403, '' );
		}
	}
}