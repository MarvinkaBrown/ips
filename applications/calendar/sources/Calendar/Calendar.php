<?php
/**
 * @brief		Calendar Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		18 Dec 2013
 */

namespace IPS\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content\ClubContainer;
use IPS\Content\Item;
use IPS\Content\ViewUpdates;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Output;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Calendar Node
 */
class Calendar extends Model implements Permissions
{
	use ClubContainer, ViewUpdates;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'calendar_calendars';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'cal_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'calendars';
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'calendar',
		'module'	=> 'calendars',
		'prefix' => 'calendars_'
	);
	
	/** 
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'calendar';
	
	/** 
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'calendar';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
		'view' 				=> 'view',
		'read'				=> 2,
		'add'				=> 3,
		'reply'				=> 4,
		'review'			=> 7,
		'askrsvp'			=> 5,
		'rsvp'				=> 6,
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'calendar_calendar_';
	
	/** 
	 * @brief	[Node] Moderator Permission
	 */
	public static string $modPerm = 'calendar_calendars';
	
	/**
	 * @brief	Content Item Class
	 */
	public static ?string $contentItemClass = 'IPS\calendar\Event';

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'calperm_';

	/**
	 * @brief	Bitwise values for cal_bitoptions field
	 */
	public static array $bitOptions = array(
		'calendar_bitoptions' => array(
			'calendar_bitoptions' => array(
				'bw_disable_tagging'		=> 1,
				'bw_disable_prefixes'		=> 2
			)
		)
	);

	/**
	 * Mapping of node columns to specific actions (e.g. comment, review)
	 * Note: Mappings can also reference bitoptions keys.
	 *
	 * @var array
	 */
	public static array $actionColumnMap = array(
		'comments' 			=> 'allow_comments',
		'reviews'			=> 'allow_reviews',
		'moderate_comments'	=> 'comment_moderate',
		'moderate_items'	=> 'moderate',
		'moderate_reviews'  => 'review_moderate',
		'tags'				=> 'bw_disable_tagging', // bitoption
		'prefix'			=> 'bw_disable_prefixes' // bitoption
	);

	/**
	 * @brief   The class of the ACP \IPS\Node\Controller that manages this node type
	 */
	protected static ?string $acpController = "IPS\\calendar\\modules\\admin\\calendars\\calendars";

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;
		
	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param bool|int $enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( bool|int $enabled ) : void
	{
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_title_seo(): string
	{
		if( !$this->_data['title_seo'] )
		{
			$this->title_seo	= Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'calendar_calendar_' . $this->id ) );
			$this->save();
		}

		return $this->_data['title_seo'] ?: Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'calendar_calendar_' . $this->id ) );
	}

	/**
	 * Check the action column map if the action is enabled in this node
	 *
	 * @param string $action
	 * @return bool
	 */
	public function checkAction( string $action ) : bool
	{
		$return = parent::checkAction( $action );

		/* Some actions here are reversed, we mark them as disabled instead of enabled */
		if( in_array( $action, array( 'tags', 'prefix' ) ) )
		{
			return !$return;
		}

		return $return;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Translatable( 'cal_title', NULL, TRUE, array( 'app' => 'calendar', 'key' => ( $this->id ? "calendar_calendar_{$this->id}" : NULL ) ) ) );
		$form->add( new Color( 'cal_color', $this->id ? $this->color : $this->_generateColor(), TRUE ) );
		$form->add( new YesNo( 'cal_moderate', $this->id ? $this->moderate : FALSE, FALSE ) );
		$form->add( new YesNo( 'cal_allow_comments', $this->id ? $this->allow_comments : TRUE, FALSE, array( 'togglesOn' => array( 'cal_comment_moderate' ) ) ) );
		$form->add( new YesNo( 'cal_comment_moderate', $this->id ? $this->comment_moderate : FALSE, FALSE, array(), NULL, NULL, NULL, 'cal_comment_moderate' ) );
		$form->add( new YesNo( 'cal_allow_reviews', $this->id ? $this->allow_reviews : FALSE, FALSE, array( 'togglesOn' => array( 'cal_review_moderate' ) ) ) );
		$form->add( new YesNo( 'cal_review_moderate', $this->id ? $this->review_moderate : FALSE, FALSE, array(), NULL, NULL, NULL, 'cal_review_moderate' ) );
		$form->add( new YesNo( 'cal_allow_anonymous', $this->id ? $this->allow_anonymous : FALSE, FALSE, array(), NULL, NULL, NULL, 'cal_allow_anonymous' ) );

		parent::form( $form );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( !$this->id )
		{
			$this->save();
		}

		if( isset( $values['cal_title'] ) )
		{
			Lang::saveCustom( 'calendar', 'calendar_calendar_' . $this->id, $values['cal_title'] );
			$values['title_seo']	= Friendly::seoTitle( $values['cal_title'][ Lang::defaultLanguage() ] );

			unset( $values['cal_title'] );
		}

		return $values;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=calendar&module=calendar&controller=view&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'calendar_calendar';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'title_seo';

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		Db::i()->delete( 'calendar_import_feeds', array( 'feed_calendar_id=?', $this->id ) );

		parent::delete();
	}

	/**
	 * @brief Default colors
	 */
	protected static array $colors = array(
		'#6E4F99',		// Purple
		'#4F994F',		// Green
		'#4F7C99',		// Blue
		'#F3F781',		// Yellow
		'#DF013A',		// Red
		'#FFBF00',		// Orange
	);

	/**
	 * Grab the next available color
	 *
	 * @return	string
	 */
	public function _generateColor(): string
	{
		foreach( static::$colors as $color )
		{
			foreach( static::roots( NULL ) as $calendar )
			{
				if( mb_strtolower( $color ) == mb_strtolower( $calendar->color ) )
				{
					continue 2;
				}
			}

			return $color;
		}

		/* If we're still here, all of our pre-defined codes are used...generate something random */
		return '#' . str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT ) . 
			str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT ) . 
			str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
	}

	/**
	 * Add the appropriate CSS to the page output
	 *
	 * @return void
	 */
	public static function addCss() : void
	{
		$output	= '';

		foreach( static::roots() as $calendar )
		{
			$output	.= "a.cEvents_style{$calendar->id}, .cEvents_style{$calendar->id} a, .cCalendarIcon.cEvents_style{$calendar->id} {
	background-color: {$calendar->color};
}\n";
		}

		Output::i()->headCss	= Output::i()->headCss . $output;
	}
	
	/* !Clubs */
	
	/**
	 * Get front-end language string
	 *
	 * @return	string
	 */
	public static function clubFrontTitle(): string
	{
		return 'calendars_sg';
	}

	/**
	 * Set form for creating a node of this type in a club
	 *
	 * @param Form $form Form object
	 * @param Club $club
	 * @return    void
	 */
	public function clubForm( Form $form, Club $club ) : void
	{
		/* @var Item $itemClass */
		$itemClass = static::$contentItemClass;
		$form->add( new Text( 'club_node_name', $this->_id ? $this->_title : Member::loggedIn()->language()->addToStack( $itemClass::$title . '_pl' ), TRUE, array( 'maxLength' => 255 ) ) );
		$form->add( new YesNo( 'cal_allow_comments', $this->id ? $this->allow_comments : TRUE, FALSE, array( 'togglesOn' => array( 'cal_comment_moderate' ) ) ) );
		$form->add( new YesNo( 'cal_allow_reviews', $this->id ? $this->allow_reviews : FALSE, FALSE, array( 'togglesOn' => array( 'cal_review_moderate' ) ) ) );
		if( $club->type == 'closed' )
		{
			$form->add( new Radio( 'club_node_public', $this->id ? $this->isPublic() : 0, TRUE, array( 'options' => array( '0' => 'club_node_public_no', '1' => 'club_node_public_view', '2' => 'club_node_public_participate' ) ) ) );
		}
	}
	
	/**
	 * Class-specific routine when saving club form
	 *
	 * @param	Club	$club	The club
	 * @param	array				$values	Values
	 * @return	void
	 */
	public function _saveClubForm( Club $club, array $values ) : void
	{
		if ( $values['club_node_name'] )
		{
			$this->title_seo	= Friendly::seoTitle( $values['club_node_name'] );
		}

		$this->allow_comments = $values['cal_allow_comments'];
		$this->allow_reviews = $values['cal_allow_reviews'];
	}

	/**
	 * Get URL
	 *
	 * @return	Url|string|null
	 * @throws	BadMethodCallException
	 */
	public function url(): Url|string|null
	{
		$url = parent::url();

		if( Settings::i()->calendar_default_view == 'overview' )
		{
			$url = $url->setQueryString( array( 'view' => 'month') );
		}

		return $url;
	}
}