<?php
/**
 * @brief		Venue Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		2017
 */

namespace IPS\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Calendar Node
 */
class Venue extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'calendar_venues';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'venue_';

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'venues';

	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'calendar_venue';

	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=calendar&module=calendar&controller=venue&id=';

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
		'module'	=> 'venues',
		'prefix' => 'venues_'
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'calendar_venue_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';

	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static string $modPerm = 'calendar_venues';

	/**
	 * @brief	[Node] Maximum results to display at a time in any node helper form elements. Useful for user-submitted node types when there may be a lot. NULL for no limit.
	 */
	public static ?int $maxFormHelperResults = 2000;

	/**
	 * @brief   The class of the ACP \IPS\Node\Controller that manages this node type
	 */
	protected static ?string $acpController = "IPS\\calendar\\modules\\admin\\calendars\\venues";

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		return (bool) $this->enabled;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( bool|int $enabled ): void
	{
		$this->enabled	= $enabled;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_title_seo(): string
	{
		if( !$this->_data['title_seo'] )
		{
			$this->title_seo	= Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'calendar_venue_' . $this->id ) );
			$this->save();
		}

		return $this->_data['title_seo'] ?: Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'calendar_venue_' . $this->id ) );
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Translatable( 'venue_title', NULL, TRUE, array( 'app' => 'calendar', 'key' => ( $this->id ? "calendar_venue_{$this->id}" : NULL ) ) ) );
		$form->add( new Translatable( 'venue_description', NULL, FALSE, array( 'app' => 'calendar', 'key' => ( $this->id ? "calendar_venue_{$this->id}_desc" : NULL ), 'editor' => array( 'app' => 'calendar', 'key' => 'Venue', 'autoSaveKey' => ( $this->id ? "calendar-venue-{$this->id}" : "calendar-new-venue" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL ) ) ) );
		$form->add( new Address( 'venue_address', $this->id ? GeoLocation::buildFromJson( $this->address ) : NULL, TRUE, array( 'minimize' => !( ( $this->id and $this->address ) ), 'requireFullAddress' => FALSE, 'preselectCountry' => FALSE ), NULL, NULL, NULL, 'venue_address' ) );

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
			File::claimAttachments( 'calendar-new-venue', $this->id, NULL, 'description', TRUE );
		}
		else
		{
			foreach ( Lang::languages() as $lang )
			{
				Request::i()->setClearAutosaveCookie( "calendar-venue-{$this->id}{$lang->id}" );
			}
		}

		if( isset( $values['venue_title'] ) )
		{
			Lang::saveCustom( 'calendar', 'calendar_venue_' . $this->id, $values['venue_title'] );
			$values['title_seo']	= Friendly::seoTitle( $values['venue_title'][ Lang::defaultLanguage() ] );

			unset( $values['venue_title'] );
		}

		if( isset( $values['venue_description'] ) )
		{
			Lang::saveCustom( 'calendar', 'calendar_venue_' . $this->id .'_desc', $values['venue_description'] );
			unset( $values['venue_description'] );
		}

		$values['venue_address'] = ( $values['venue_address'] !== NULL ) ? json_encode( $values['venue_address'] ) : NULL;

		return $values;
	}

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
		Lang::deleteCustom( 'calendar', 'calendar_venue_' . $this->id );
		Lang::deleteCustom( 'calendar', 'calendar_venue_' . $this->id . '_desc' );

		parent::delete();
	}

	/**
	 * Return the map for the venue
	 *
	 * @param	int		$width	Width
	 * @param	int		$height	Height
	 * @return	string
	 * @note	\BadMethodCallException can be thrown if the google maps integration is shut off - don't show any error if that happens.
	 */
	public function map( int $width, int $height ): string
	{
		if( $this->address )
		{
			try
			{
				return GeoLocation::buildFromJson( $this->address )->map()->render( $width, $height );
			}
			catch( BadMethodCallException $e ){}
		}

		return '';
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse	int						id				ID number
	 * @apiresponse	string					title			Title
	 * @apiresponse	string					description		Description
	 * @apiresponse	\IPS\GeoLocation		address		The address
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'id'			=> $this->id,
			'title'			=> $this->_title,
			'description'	=> Member::loggedIn()->language()->addToStack('calendar_venue_' . $this->id . '_desc' ),
			'address'		=> GeoLocation::buildFromJson( $this->address )->apiOutput( $authorizedMember )
		);
	}
}