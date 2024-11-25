<?php
/**
 * @brief		Category Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		27 Sep 2013
 */

namespace IPS\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content\ClubContainer;
use IPS\Content\Comment;
use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\Content\Search\Index;
use IPS\Content\ViewUpdates;
use IPS\DateTime;
use IPS\Db;
use IPS\downloads\File;
use IPS\File as SystemFile;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Node\Icon;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget;
use OutOfBoundsException;
use OutOfRangeException;
use UnderFlowException;
use function defined;
use function get_called_class;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Category Node
 */
class Category extends Model implements Permissions
{
	use ClubContainer, ViewUpdates, Icon;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'downloads_categories';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'c';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'categories';
			
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
		'app'		=> 'downloads',
		'module'	=> 'downloads',
		'prefix' => 'categories_'
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'downloads';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'category';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
		'view' 				=> 'view',
		'read'				=> 2,
		'add'				=> 3,
		'download'			=> 4,
		'reply'				=> 5,
		'review'			=> 6
	);
	
	/**
	 * @brief	Bitwise values for members_bitoptions field
	 */
	public static array $bitOptions = array(
		'bitoptions' => array(
			'bitoptions' => array(
				'allowss'				=> 1,	// Allow screenshots?
				'reqss'					=> 2,	// Require screenshots?
				'comments'				=> 4,	// Enable comments?
				'moderation'			=> 8,	// Require files to be approved?
				'comment_moderation'	=> 16,	// Require comments to be approved?
				# 32 is deprecated
				'moderation_edits'		=> 64,	// Edits must be approved?
				'submitter_log'			=> 128,	// File submitter can view downloads logs?
				'reviews'				=> 256,	// Enable reviews?
				'reviews_mod'			=> 512,	// Reviews must be approved?
				'reviews_download'		=> 1024,// Users must have downloaded before they can review?
				'topic_delete'			=> 2048,// Delete created topics when file is deleted?
				'topic_screenshot'		=> 4096,// Include screenshot with topics?
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
		'comments' 			=> 'comments',
		'reviews'			=> 'reviews',
		'moderate_comments'	=> 'comment_moderation',
		'moderate_items'	=> 'moderation',
		'moderate_reviews'  => 'reviews_mod',
		'tags'				=> 'tags_disabled',
		'prefix'			=> 'tags_noprefixes'
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'downloads_category_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static string $modPerm = 'download_categories';
	
	/**
	 * @brief	Content Item Class
	 */
	public static ?string $contentItemClass = 'IPS\downloads\File';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_file_';
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'open';

	/**
	 * @brief   The class of the ACP \IPS\Node\Controller that manages this node type
	 */
	protected static ?string $acpController = "IPS\\downloads\\modules\\admin\\downloads\\categories";

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * @brief	FileStorage extension
	 */
	public static string $iconStorageExtension = 'downloads_Icons';

	/**
	 * @var string
	 */
	public static string $iconFormPrefix = 'c';

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_furl(): string
	{
		if( !$this->_data['name_furl'] )
		{
			$this->name_furl	= Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'downloads_category_' . $this->id ) );
			$this->save();
		}

		return $this->_data['name_furl'] ?: Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'downloads_category_' . $this->id ) );
	}

	/**
	 * Get sort order
	 *
	 * @return string|null
	 */
	public function get__sortBy(): ?string
	{
		return $this->sortorder;
	}
	
	/**
	 * Get sort order
	 *
	 * @return	string
	 */
	public function get__sortOrder(): string
	{
		return $this->sortorder == 'file_name' ? 'ASC' : parent::get__sortOrder();
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	public function set__enabled( bool|int $enabled ) : void
	{
		parent::set__enabled( $enabled );
		
		static::updateSearchIndexOnEnableDisable( $this, (bool) $enabled );
		
		/* Trash widgets so files in this category are not viewable in widgets */
		Widget::deleteCaches( NULL, 'downloads' );
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
	 * Update the search index on enable / disable of a category
	 *
	 * @param Category $node		The Category
	 * @param bool $enabled	Enabled / Disable
	 * @return	void
	 */
	protected static function updateSearchIndexOnEnableDisable( Category $node, bool $enabled ) : void
	{
		Index::i()->massUpdate( static::$contentItemClass, $node->_id, NULL, ( $enabled ) ? $node->searchIndexPermissions() : '' );
		
		Db::i()->update( 'core_tags_perms', array( 'tag_perm_text' => ( $enabled ) ? $node->searchIndexPermissions() : '' ), array( 'tag_perm_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $node->_id ) ) );

		if ( $node->hasChildren( NULL ) )
		{
			foreach( $node->children( NULL ) AS $child )
			{
				static::updateSearchIndexOnEnableDisable( $child, $enabled );
			}
		}
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$customFields = array();
		foreach ( Db::i()->select( 'cf_id', 'downloads_cfields', NULL, 'cf_position' ) as $fieldId )
		{
			$customFields[ $fieldId ] = Member::loggedIn()->language()->addToStack( "downloads_field_{$fieldId}" );
		}
		
		$form->addTab( 'category_settings' );
		$form->addHeader( 'category_settings' );
		$form->add( new Translatable( 'cname', NULL, TRUE, array( 'app' => 'downloads', 'key' => ( $this->id ? "downloads_category_{$this->id}" : NULL ) ) ) );
		$form->add( new Translatable( 'cdesc', NULL, FALSE, array(
			'app'		=> 'downloads',
			'key'		=> ( $this->id ? "downloads_category_{$this->id}_desc" : NULL ),
			'editor'	=> array(
				'app'			=> 'downloads',
				'key'			=> 'Categories',
				'autoSaveKey'	=> ( $this->id ? "downloads-cat-{$this->id}" : "downloads-new-cat" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'cdesc_placeholder'
			)
		) ) );

		$class = get_called_class();

		$form->add( new Node( 'cparent', $this->parent ?: 0, FALSE, array(
			'class'		      => '\IPS\downloads\Category',
			'disabled'	      => false,
			'zeroVal'         => 'node_no_parentd',
			'permissionCheck' => function( $node ) use ( $class )
			{
				if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
				{
					return FALSE;
				}

				return !isset( Request::i()->id ) or ( $node->id != Request::i()->id and !$node->isChildOf( $node::load( Request::i()->id ) ) );
			}
		) ) );
		if ( !empty( $customFields ) )
		{
			$form->add( new CheckboxSet( 'ccfields', ( $this->id AND $this->_data['cfields'] ) ? explode( ',', $this->_data['cfields'] ) : array(), FALSE, array( 'options' => $customFields, 'multiple' => TRUE ), NULL, NULL, NULL, 'ccfields' ) );
		}
		$form->add( new YesNo( 'allow_anonymous', $this->id ? $this->allow_anonymous : FALSE, FALSE, array() ) );
		
		$form->addHeader( 'category_comments_and_reviews' );
		$form->add( new YesNo( 'cbitoptions_comments', $this->id ? $this->bitoptions['comments'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_comment_moderation' ) ), NULL, NULL, NULL, 'cbitoptions_comments' ) );
		$form->add( new YesNo( 'cbitoptions_comment_moderation', $this->bitoptions['comment_moderation'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_comment_moderation' ) );
		$form->add( new YesNo( 'cbitoptions_reviews', $this->id ? $this->bitoptions['reviews'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reviews_mod', 'cbitoptions_reviews_download' ) ) ) );
		$form->add( new YesNo( 'cbitoptions_reviews_download', $this->bitoptions['reviews_download'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reviews_download' ) );
		$form->add( new YesNo( 'cbitoptions_reviews_mod', $this->bitoptions['reviews_mod'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reviews_mod' ) );

		$form->addHeader( 'category_display' );
		$form->add( new Select( 'csortorder', $this->sortorder ?: 'updated', FALSE, array( 'options' => array( 'updated' => 'sort_updated', 'last_comment' => 'last_reply', 'title' => 'file_title', 'rating' => 'sort_rating', 'date' => 'sort_date', 'num_comments' => 'sort_num_comments', 'num_reviews' => 'sort_num_reviews', 'views' => 'sort_num_views' ) ), NULL, NULL, NULL, 'csortorder' ) );
		$form->add( new Translatable( 'cdisclaimer', NULL, FALSE, array( 'app' => 'downloads', 'key' => ( $this->id ? "downloads_category_{$this->id}_disclaimer" : NULL ), 'editor' => array( 'app' => 'downloads', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "downloads-cat-{$this->id}-disc" : "downloads-new-cat-disc" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'disclaimer' ) : NULL, 'minimize' => 'cdisclaimer_placeholder' ) ), NULL, NULL, NULL, 'cdisclaimer-editor' ) );
		$form->add( new Radio( 'cdisclaimer_location', $this->id ? $this->disclaimer_location : 'download', FALSE, array( 'options' => array( 'purchase' => 'cdisclaimer_purchase', 'download' => 'cdisclaimer_download', 'both' => 'cdisclaimer_both' ) ) ) );

		/* Insert logic for icons */
		$this->iconFormFields( $form );

		$form->add( new Upload( 'ccard_image', $this->card_image ? SystemFile::get( 'downloads_Cards', $this->card_image ) : NULL, FALSE, array( 'image' => array( 'maxWidth' => 800, 'maxHeight' => 800 ), 'storageExtension' => 'downloads_Cards', 'allowStockPhotos' => TRUE ), NULL, NULL, NULL, 'ccard_image' ) );

		$form->addHeader( 'category_logs' );
		$form->add( new YesNo( 'clog_on', $this->log !== 0, FALSE, array( 'disableCopy' => TRUE, 'togglesOn' => array( 'clog', 'submitter_log' ) ) ) );
		$form->add( new Interval( 'clog', $this->log === NULL ? -1 : $this->log, FALSE, array(
			'valueAs' => Interval::DAYS, 'unlimited' => -1,
			'endSuffix'	=> ( $this->id and Member::loggedIn()->hasAcpRestriction( 'downloads', 'downloads', 'categories_recount_downloads' ) ) ? '<a data-confirm data-confirmSubMessage="' . Member::loggedIn()->language()->addToStack('clog_recount_desc') . '" href="' . Url::internal( "app=downloads&module=downloads&controller=categories&do=recountDownloads&id={$this->id}")->csrf() . '">' . Member::loggedIn()->language()->addToStack('clog_recount') . '</a>' : ''
		), NULL, NULL, NULL, 'clog' ) );
		$form->add( new YesNo( 'cbitoptions_submitter_log', $this->bitoptions['submitter_log'], FALSE, array(), NULL, NULL, NULL, 'submitter_log' ) );
		
		$form->addTab( 'category_submissions' );
		$form->addHeader( 'category_allowed_files' );
		$form->add( new Text( 'ctypes', $this->id ? $this->_data['types'] : NULL, FALSE, array( 'autocomplete' => array( 'unique' => 'true' ), 'nullLang' => 'any_extensions' ), NULL, NULL, NULL, 'ctypes' ) );
		$form->add( new Number( 'cmaxfile', $this->maxfile ?: -1, FALSE, array( 'unlimited' => -1 ), function( $value ) {
			if( !$value )
			{
				throw new InvalidArgumentException('form_required');
			}
		}, NULL, Member::loggedIn()->language()->addToStack('filesize_raw_k'), 'cmaxfile' ) );
		$form->add( new YesNo( 'cmultiple_files', $this->id ? $this->multiple_files : TRUE ) );
		$form->add( new Translatable( 'csubmissionterms', $this->submissionterms, FALSE, array( 'app' => 'downloads', 'key' => ( $this->id ? "downloads_category_{$this->id}_subterms" : NULL ), 'editor' => array( 'app' => 'downloads', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "downloads-cat-{$this->id}-subt" : "downloads-new-cat-subt" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'subt' ) : NULL, 'minimize' => 'csubmissionterms_placeholder' ) ) ) );
		$form->addHeader( 'category_versioning' );
		$form->add( new Radio( 'cversion_numbers', $this->id ? $this->version_numbers : 1, TRUE, array( 'options' => array( 0 => 'version_numbers_disabled', 1 => 'version_numbers_enabled', 2 => 'version_numbers_required' ) ) ) );
		$form->add( new YesNo( 'cversioning_on', $this->versioning !== 0, FALSE, array( 'togglesOn' => array( 'cversioning', 'crequire_changelog' ) ) ) );
		$form->add( new Number( 'cversioning', $this->versioning === NULL ? -1 : $this->versioning, FALSE, array( 'unlimited' => -1 ), NULL, NULL, NULL, 'cversioning' ) );
        $form->add( new YesNo( 'crequire_changelog', $this->require_changelog ?: 0, FALSE, array( ), NULL, NULL, NULL, 'crequire_changelog' ) );
        $form->addHeader( 'category_moderation' );
		$form->add( new YesNo( 'cbitoptions_moderation', $this->bitoptions['moderation'], FALSE, array( 'togglesOn' => array( 'cbitoptions_moderation_edits' ) ) ) );
		$form->add( new YesNo( 'cbitoptions_moderation_edits', $this->bitoptions['moderation_edits'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_moderation_edits' ) );
		$form->addHeader( 'category_screenshots' );
		$form->add( new YesNo( 'cbitoptions_allowss', $this->id ? $this->bitoptions['allowss'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reqss', 'cmaxss', 'cmaxdims' ) ), NULL, NULL, NULL, 'cbitoptions_allowss' ) );
		$form->add( new YesNo( 'cbitoptions_reqss', $this->bitoptions['reqss'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reqss' ) );
		$form->add( new Number( 'cmaxss', $this->maxss, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('filesize_raw_k'), 'cmaxss' ) );
		$form->add( new WidthHeight( 'cmaxdims', $this->maxdims ? explode( 'x', $this->maxdims ) : array( 0, 0 ), FALSE, array( 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'cmaxdims' ) );
		
		$form->addTab( 'category_errors', NULL, 'category_errors_blurb' );
		$form->add( new Translatable( 'noperm_view', NULL, FALSE, array( 'app' => 'downloads', 'key' => ( $this->id ? "downloads_category_{$this->id}_npv" : NULL ), 'editor' => array( 'app' => 'downloads', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "downloads-cat-{$this->id}-npv" : "downloads-new-cat-npv" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'npv' ) : NULL, 'minimize' => 'noperm_view_placeholder' ), NULL, NULL, NULL, 'noperm_view' ) ) );
		$form->add( new Translatable( 'noperm_dl', NULL, FALSE, array( 'app' => 'downloads', 'key' => ( $this->id ? "downloads_category_{$this->id}_npd" : NULL ), 'editor' => array( 'app' => 'downloads', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "downloads-cat-{$this->id}-npd" : "downloads-new-cat-npd" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'npv' ) : NULL, 'minimize' => 'noperm_dl_placeholder' ), NULL, NULL, NULL, 'noperm_dl' ) ) );
		
		if ( Application::appIsEnabled( 'forums' ) )
		{
			if ( $this->id )
			{
				$rebuildUrl = Url::internal( 'app=downloads&module=downloads&controller=categories&id=' . $this->id . '&do=rebuildTopicContent' )->csrf();
			}

			$form->addTab( 'category_forums_integration' );
			$form->add( new YesNo( 'cforum_on', $this->forum_id, FALSE, array( 'disableCopy' => TRUE, 'togglesOn' => array(
				'cforum_id',
				'ctopic_prefix',
				'ctopic_suffix',
				'cbitoptions_topic_delete',
				'cbitoptions_topic_screenshot'
			) ), NULL, NULL, $this->id ? Member::loggedIn()->language()->addToStack( 'downloadcategory_topic_rebuild', FALSE, array( 'sprintf' => array( $rebuildUrl ) ) ) : NULL ) );
			$form->add( new Node( 'cforum_id', $this->forum_id ? $this->forum_id  : NULL, FALSE, array( 'class' => 'IPS\forums\Forum', 'permissionCheck' => function ( $forum ) { return $forum->sub_can_post and !$forum->redirect_url; } ), function( $val ) {
				if( Request::i()->cforum_on_checkbox AND !$val )
				{
					throw new DomainException( 'form_required' );
				}
			}, NULL, NULL, 'cforum_id' ) );
			$form->add( new Text( 'ctopic_prefix', $this->topic_prefix, FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'ctopic_prefix' ) );
			$form->add( new Text( 'ctopic_suffix', $this->topic_suffix, FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'ctopic_suffix' ) );
			$form->add( new YesNo( 'cbitoptions_topic_delete', $this->id ? $this->bitoptions['topic_delete'] : NULL, FALSE, array(), NULL, NULL, NULL, 'cbitoptions_topic_delete' ) );
			$form->add( new YesNo( 'cbitoptions_topic_screenshot', $this->id ? $this->bitoptions['topic_screenshot'] : NULL, FALSE, array(), NULL, NULL, NULL, 'cbitoptions_topic_screenshot' ) );
		}

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
			SystemFile::claimAttachments( 'downloads-new-cat', $this->id, NULL, 'description', TRUE );
			SystemFile::claimAttachments( 'downloads-new-cat-disc', $this->id, NULL, 'disclaimer', TRUE );
			SystemFile::claimAttachments( 'downloads-new-cat-subt', $this->id, NULL, 'subt', TRUE );
			SystemFile::claimAttachments( 'downloads-new-cat-npv', $this->id, NULL, 'npv', TRUE );
			SystemFile::claimAttachments( 'downloads-new-cat-npd', $this->id, NULL, 'npd', TRUE );
		}
				
		foreach ( array( 'cname' => "downloads_category_{$this->id}", 'cdesc' => "downloads_category_{$this->id}_desc", 'cdisclaimer' => "downloads_category_{$this->id}_disclaimer", 'csubmissionterms' => "downloads_category_{$this->id}_subterms", 'noperm_view' => "downloads_category_{$this->id}_npv", 'noperm_dl' => "downloads_category_{$this->id}_npd" ) as $fieldKey => $langKey )
		{
			if ( array_key_exists( $fieldKey, $values ) )
			{
				Lang::saveCustom( 'downloads', $langKey, $values[ $fieldKey ] );
				
				if ( $fieldKey === 'cname' )
				{
					$this->name_furl = Friendly::seoTitle( $values[ $fieldKey ][ Lang::defaultLanguage() ] );
				}
				
				unset( $values[ $fieldKey ] );
			}
		}
		
		foreach ( array( 'moderation', 'moderation_edits', 'allowss', 'reqss', 'comments', 'comment_moderation', 'submitter_log', 'reviews', 'reviews_mod', 'reviews_download', 'topic_delete', 'topic_screenshot' ) as $k )
		{
			if ( array_key_exists( "cbitoptions_{$k}", $values ) )
			{
				$values['bitoptions'][ $k ] = $values["cbitoptions_{$k}"];
				unset( $values["cbitoptions_{$k}"] );
			}
		}
		
		if ( isset( $values['cversioning_on'] ) or isset( $values['cversioning'] ) )
		{
			$values['cversioning'] = $values['cversioning_on'] ? ( ( $values['cversioning'] < 0 ) ? NULL : $values['cversioning'] ) : 0;
		}
		
		foreach ( array( 'cmaxfile', 'cmaxss' ) as $k )
		{
			if ( isset( $values[ $k ] ) and $values[ $k ] == -1 )
			{
				$values[ $k ] = NULL;
			}
		}

		if( isset( $values['clog_on'] ) AND $values['clog_on'] != 1 )
		{
			$values['clog'] = 0;
		}
		else if ( isset( $values[ 'clog' ] ) and $values[ 'clog' ] == -1 )
		{
			$values['clog'] = NULL;
		}

		if( array_key_exists( 'clog_on', $values ) )
		{
			unset( $values['clog_on'] );
		}

		if( array_key_exists( 'cversioning_on', $values ) )
		{
			unset( $values['cversioning_on'] );
		}

		if ( isset( $values['ctypes'] ) )
		{
			$values['ctypes'] = $values['ctypes'] ?: NULL;
		}

		if ( isset( $values['cmaxdims'] ) )
		{
			$values['cmaxdims'] = $values['cmaxdims'] ? implode( 'x', $values['cmaxdims'] ) : NULL;
		}

		if ( isset( $values['cparent'] ) )
		{
			/* Avoid "cparent cannot be null" error if no parent selected. */
			$values['cparent'] = $values['cparent'] ? intval( $values['cparent']->id ) : 0;
		}

		/* Handle Icons */
		$values = $this->formatIconFieldValues( $values );
		
		if ( isset( $values['cforum_on'] ) and !$values['cforum_on'] )
		{
			$values['cforum_id'] = 0;
		}
		
		if( isset( $values['cforum_id'] ) AND $values['cforum_id'] )
		{
			$values['cforum_id'] = ( $values['cforum_id'] instanceof Model ) ? intval( $values['cforum_id']->id ) : intval( $values['cforum_id'] );
		}

		if( array_key_exists( 'cforum_on', $values ) )
		{
			unset( $values['cforum_on'] );
		}

		foreach( $values as $k => $v )
		{
			if( mb_substr( $k, 0, 1 ) === 'c' AND mb_substr( $k, 0, 2 ) !== 'cc' )
			{
				unset( $values[ $k ] );
				$values[ mb_substr( $k, 1 ) ] = $v;
			}
		}

		/* Send to parent */
		return $values;
	}
	
	/**
	 * Get acceptable file extensions
	 *
	 * @return	array|NULL
	 */
	public function get_types(): ?array
	{
		return $this->_data['types'] ? explode( ',', $this->_data['types'] ) : NULL;
	}

	/**
	 * Get number of items
	 *
	 * @return int|null
	 */
	protected function get__items(): ?int
	{
		return $this->files;
	}
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Items
	 * @return	void
	 */
	protected function set__items( int $val ) : void
	{
		$this->files = $val;
	}
	
	/**
	 * @brief	Custom Field Cache
	 */
	protected ?array $_customFields = NULL;
	
	/**
	 * Get custom fields
	 *
	 * @return	array
	 */
	protected function get_cfields(): array
	{
		if ( $this->_customFields === NULL )
		{
			$this->_customFields = array();
			if ( $this->_data['cfields'] )
			{
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'downloads_cfields', array( Db::i()->in( 'cf_id', explode( ',', $this->_data['cfields'] ) ) ), 'cf_position ASC' ), 'IPS\downloads\Field' ) AS $field )
				{
					$this->_customFields[ $field->id ] = $field;
				}
			}
		}
		
		return $this->_customFields;
	}
	
	/**
	 * Get Topic Prefix
	 *
	 * @return	string
	 */
	public function get__topic_prefix(): string
	{
		return str_replace( '{catname}', $this->_title, $this->_data['topic_prefix'] );
	}
	
	/**
	 * Get Topic Suffix
	 *
	 * @return	string
	 */
	public function get__topic_suffix(): string
	{
		return str_replace( '{catname}', $this->_title, $this->_data['topic_suffix'] );
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=downloads&module=downloads&controller=browse&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'downloads_cat';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'name_furl';

	/**
	 * Get message
	 *
	 * @param string $type	'npv', 'npd', 'disclaimer'
	 * @return	string|null
	 */
	public function message( string $type ): ?string
	{
		if ( Member::loggedIn()->language()->checkKeyExists( "downloads_category_{$this->_id}_{$type}" ) )
		{
			$message = Member::loggedIn()->language()->get( "downloads_category_{$this->_id}_{$type}" );
			if ( $message and $message != '<p></p>' )
			{
				return trim( $message );
			}
		}
		
		return NULL;
	}

	/**
	 * Check permissions on any node
	 *
	 * For example - can be used to check if the user has
	 * permission to create content in any node to determine
	 * if there should be a "Submit" button
	 *
	 * @param	mixed								$permission						A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	Member|Group|NULL	$member							The member or group to check (NULL for currently logged in member)
	 * @param	array								$where							Additional WHERE clause
	 * @param	bool								$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public static function canOnAny( mixed $permission, Group|Member $member=NULL, array $where = array(), bool $considerPostBeforeRegistering = TRUE ): bool

	{
		$member	= ( $member === NULL ) ? Member::loggedIn() : $member;

		if ( $member->idm_block_submissions )
		{
			return FALSE;
		}

		return parent::canOnAny( $permission, $member, $where, $considerPostBeforeRegistering );
	}

	/**
	 * Get latest file information
	 *
	 * @return    File|NULL
	 */
	public function lastFile(): ?File
	{
		$latestFileData	= $this->getLatestFileId();
		$latestFile		= NULL;
		
		if( $latestFileData !== NULL )
		{
			try
			{
				$latestFile	= File::load( $latestFileData['id'] );
			}
			catch( OutOfRangeException $e ){}
		}

		return $latestFile;
	}

	/**
	 * Retrieve the latest file ID in categories and children categories
	 *
	 * @return	array|NULL
	 */
	protected function getLatestFileId(): ?array
	{
		$latestFile	= NULL;

		if ( $this->last_file_id )
		{
			$latestFile = array( 'id' => $this->last_file_id, 'date' => $this->last_file_date );
		}

		foreach( $this->children() as $child )
		{
			$childLatest = $child->getLatestFileId();

			if( $childLatest !== NULL AND ( $latestFile === NULL OR $childLatest['date'] > $latestFile['date'] ) )
			{
				$latestFile	= $childLatest;
			}
		}

		return $latestFile;
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		SystemFile::unclaimAttachments( 'downloads_Categories', $this->id );
		parent::delete();
		
		foreach ( array( 'cdisclaimer' => "downloads_category_{$this->id}_disclaimer", 'csubmissionterms' => "downloads_category_{$this->id}_subterms", 'noperm_view' => "downloads_category_{$this->id}_npv", 'noperm_dl' => "downloads_category_{$this->id}_npd" ) as $fieldKey => $langKey )
		{
			Lang::deleteCustom( 'downloads', $langKey );
		}

		if( $this->card_image )
		{
			try
			{
				SystemFile::get( 'downloads_Cards', $this->card_image )->delete();
			}
			catch( Exception $e ){}
		}
	}

	/**
	 * Get template for node tables
	 *
	 * @return	callable|array
	 */
	public static function nodeTableTemplate(): callable|array
	{
		return array( Theme::i()->getTemplate( 'browse', 'downloads' ), 'categoryRow' );
	}

	/**
	 * Get last comment time
	 *
	 * @note	This should return the last comment time for this node only, not for children nodes
	 * @param   Member|null    $member         MemberObject
	 * @return	DateTime|NULL
	 */
	public function getLastCommentTime( Member $member = NULL ): ?DateTime
	{
		return $this->last_file_date ? DateTime::ts( $this->last_file_date ) : NULL;
	}
	
	/**
	 * Set last file data
	 *
	 * @param File|NULL	$file	The latest file or NULL to work it out
	 * @return	void
	 */
	public function setLastFile( ?File $file=NULL ) : void
	{
		if( $file === NULL )
		{
			try
			{
				$file	= File::constructFromData( Db::i()->select( '*', 'downloads_files', array( 'file_cat=? AND file_open=1', $this->id ), 'file_submitted DESC', 1, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first() );
			}
			catch ( UnderflowException $e )
			{
				$this->last_file_id		= 0;
				$this->last_file_date	= 0;
				return;
			}
		}
	
		$this->last_file_id		= $file->id;
		$this->last_file_date	= $file->submitted;
	}

	/**
	 * Set last comment
	 *
	 * @param Comment|null $comment The latest comment or NULL to work it out
	 * @param Item|null $updatedItem We sometimes run setLastComment() when an item has been edited, if so, that item will be here
	 * @return    void
	 */
	protected function _setLastComment( ?Comment $comment=NULL, Item $updatedItem=NULL ) : void
	{
		$this->setLastFile( $updatedItem );
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldId = $this->id;
		$oldGridImage = $this->card_image;

		parent::__clone();

		foreach ( array( 'cdisclaimer' => "downloads_category_{$this->id}_disclaimer", 'csubmissionterms' => "downloads_category_{$this->id}_subterms", 'noperm_view' => "downloads_category_{$this->id}_npv", 'noperm_dl' => "downloads_category_{$this->id}_npd" ) as $fieldKey => $langKey )
		{
			$oldLangKey = str_replace( $this->id, $oldId, $langKey );
			Lang::saveCustom( 'downloads', $langKey, iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', $oldLangKey ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		}

		if ( $oldGridImage )
		{
			try
			{
				$gridImg = SystemFile::get( 'downloads_Cards', $oldGridImage );
				$newImage = SystemFile::create( 'downloads_Cards', $gridImg->originalFilename, $gridImg->contents() );
				$this->card_image = (string) $newImage;
			}
			catch ( Exception $e )
			{
				$this->card_image = NULL;
			}

			$this->save();
		}
	}
	
	/**
	 * [Node] Get the fully qualified node type (mostly for Pages because Pages is Pages)
	 *
	 * @return	string
	 */
	public static function fullyQualifiedType(): string
	{
		return Member::loggedIn()->language()->addToStack('__app_downloads') . ' ' . Member::loggedIn()->language()->addToStack( static::$nodeTitle . '_sg' );
	}
	
	/* !Clubs */
	
	/**
	 * Get acp language string
	 *
	 * @return	string
	 */
	public static function clubAcpTitle(): string
	{
		return 'downloads_categories';
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
		/* @var File $itemClass */
		$itemClass = static::$contentItemClass;
		$form->add( new Text( 'club_node_name', $this->_id ? $this->_title : Member::loggedIn()->language()->addToStack( $itemClass::$title . '_pl' ), TRUE, array( 'maxLength' => 255 ) ) );
		$form->add( new Editor( 'club_node_description', $this->_id ? Member::loggedIn()->language()->get( static::$titleLangPrefix . $this->_id . '_desc' ) : NULL, FALSE, array( 'app' => 'downloads', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "downloads-cat-{$this->id}" : "downloads-new-cat" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'cdesc_placeholder' ) ) );
		$form->add( new YesNo( 'cbitoptions_comments', $this->id ? $this->bitoptions['comments'] : TRUE, FALSE, array(), NULL, NULL, NULL, 'cbitoptions_comments' ) );
		$form->add( new YesNo( 'cbitoptions_reviews', $this->id ? $this->bitoptions['reviews'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reviews_download' ) ) ) );
		$form->add( new YesNo( 'cbitoptions_allowss', $this->id ? $this->bitoptions['allowss'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reqss', 'cmaxss', 'cmaxdims' ) ), NULL, NULL, NULL, 'cbitoptions_allowss' ) );
		$form->add( new YesNo( 'cbitoptions_reqss', $this->bitoptions['reqss'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reqss' ) );
		$form->add( new Text( 'ctypes', $this->id ? $this->_data['types'] : NULL, FALSE, array( 'autocomplete' => array( 'unique' => 'true', 'lang' => 'files_optional' ), 'nullLang' => 'any_extensions' ), NULL, NULL, NULL, 'ctypes' ) );
		
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
		foreach ( array( 'allowss', 'reqss', 'comments', 'reviews' ) as $k )
		{
			$this->bitoptions[ $k ] = $values[ 'cbitoptions_' . $k ];
		}
		
		if( isset( $values['ctypes'] ) )
		{
			$this->types = implode( ',', $values['ctypes'] );
		}
		
		if ( $values['club_node_name'] )
		{
			$this->name_furl = Friendly::seoTitle( $values['club_node_name'] );
		}
		
		if ( !$this->_id )
		{
			$this->save();
			SystemFile::claimAttachments( 'downloads-new-cat', $this->id, NULL, 'description' );
		}
	}
	
	/**
	 * Files in clubs the member can view
	 *
	 * @return	int
	 */
	public static function filesInClubNodes(): int
	{
		return File::getItemsWithPermission( array( array( static::$databasePrefix . static::clubIdColumn() . ' IS NOT NULL' ) ), NULL, 1, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE, FALSE, FALSE, TRUE );
	}
	
	/**
	 * Content was held for approval by container
	 * Allow node classes that can determine if content should be held for approval in individual nodes
	 *
	 * @param	string				$content	The type of content we are checking (item, comment, review).
	 * @param	Member|NULL	$member		Member to check or NULL for currently logged in member.
	 * @return	bool
	 */
	public function contentHeldForApprovalByNode( string $content, ?Member $member = NULL ): bool
	{
		/* If members group bypasses, then no. */
		$member = $member ?: Member::loggedIn();
		if ( $member->group['g_avoid_q'] )
		{
			return FALSE;
		}
		
		switch( $content )
		{
			case 'item':
				return (bool) $this->bitoptions['moderation'];
			
			case 'comment':
				return (bool) $this->bitoptions['comment_moderation'];
			
			case 'review':
				return (bool) $this->bitoptions['reviews_mod'];
		}

		return false;
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return SystemFile|null
	 */
	public function primaryImage() : ?SystemFile
	{
		if( $this->card_image )
		{
			return SystemFile::get( 'downloads_Cards', $this->card_image );
		}

		return parent::primaryImage();
	}
}