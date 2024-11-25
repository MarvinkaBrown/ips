<?php
/**
 * @brief		Front Navigation Extension: Custom Item
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		21 Jan 2015
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Custom Item
 */
class YourActivityStreamsItem extends FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon = '\f4fd';
	
	/**
	 * @brief	The stream ID
	 */
	protected ?int	$streamId = null;

	/**
	 * Constructor
	 *
	 * @param array $configuration The configuration
	 * @param int $id The ID number
	 * @param string|null $permissions The permissions (* or comma-delimited list of groups)
	 * @param string $menuTypes The menu types (either * or json string)
	 * @param array|null $icon Array of icon data or null
	 */
	public function __construct( array $configuration, int $id, string|null $permissions, string $menuTypes, array|null $icon )
	{
		parent::__construct( $configuration, $id, $permissions, $menuTypes, $icon );
		
		if ( count( $configuration ) and isset( $configuration['menu_stream_id'] ) )
		{
			$this->streamId = $configuration['menu_stream_id'];
		}
		else
		{
			$this->streamId = $id;
		}
	}

	/**
	 * Return the default icon
	 *
	 * @return string
	 */
	public function getDefaultIcon(): string
	{
		return match ( $this->streamId )
		{
			1 => '\f1ea', //unread
			2 => '\f2bd', //started
			3 => '\e494', //followed content
			4 => '\f0c0', //followed member
			5 => '\f0ae', // posted
			default => parent::getDefaultIcon()
		};
	}

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('activity_stream_single');
	}
	
	/**
	 * Can access?
	 *
	 * @return    bool
	 */
	public function canAccessContent(): bool
	{
		if ( ! Member::loggedIn()->member_id and $this->streamId and $this->streamId <= 5 )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Allow multiple instances?
	 *
	 * @return    bool
	 */
	public static function allowMultiple(): bool
	{
		return TRUE;
	}
	
	/**
	 * Get configuration fields
	 *
	 * @param	array	$existingConfiguration	The existing configuration, if editing an existing item
	 * @param int|null $id						The ID number of the existing item, if editing
	 * @return    array
	 */
	public static function configuration(array $existingConfiguration, ?int $id = NULL ): array
	{
		$globalStreams = array();
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_streams', '`member` IS NULL' ), 'IPS\core\Stream' ) as $stream )
		{
			$globalStreams[ $stream->id ] = $stream->_title;
		}
				
		return array(
			new Select( 'menu_stream_id', $existingConfiguration['menu_stream_id'] ?? NULL, NULL, array( 'options' => $globalStreams ), NULL, NULL, NULL, 'menu_stream_id' ),
			new Radio( 'menu_title_type', $existingConfiguration['menu_title_type'] ?? 0, NULL, array( 'options' => array( 0 => 'menu_title_type_stream', 1 => 'menu_title_type_custom' ), 'toggles' => array( 1 => array( 'menu_stream_title' ) ) ), NULL, NULL, NULL, 'menu_title_type' ),
			new Translatable( 'menu_stream_title', NULL, NULL, array( 'app' => 'core', 'key' => $id ? "menu_stream_title_{$id}" : NULL ), NULL, NULL, NULL, 'menu_stream_title' ),
		);
	}
	
	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return    array
	 */
	public static function parseConfiguration( array $configuration, int $id ): array
	{
		if ( $configuration['menu_title_type'] )
		{
			Lang::saveCustom( 'core', "menu_stream_title_{$id}", $configuration['menu_stream_title'] );
		}
		else
		{
			Lang::deleteCustom( 'core', "menu_stream_title_{$id}" );
		}
		
		unset( $configuration['menu_stream_title'] );
		
		return $configuration;
	}
	
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		if ( ! empty( $this->configuration['title'] ) )
		{
			return $this->configuration['title'];
		}
		else if ( isset( $this->configuration['menu_title_type'] ) and $this->configuration['menu_title_type'] )
		{
			return Member::loggedIn()->language()->addToStack( "menu_stream_title_{$this->id}" );
		}
		
		return Member::loggedIn()->language()->addToStack( "stream_title_{$this->streamId}" );
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		switch ( $this->streamId )
		{
			case 1:
				$furlKey = 'discover_unread';
				break;
			case 2:
				$furlKey = 'discover_istarted';
				break;
			case 3:
				$furlKey = 'discover_followed';
				break;
			case 4:
				$furlKey = 'discover_following';
				break;
			case 5:
				$furlKey = 'discover_posted';
				break;
			default:
				$furlKey = 'discover_stream';
				break;
		}
		
		return Url::internal( "app=core&module=discover&controller=streams&id={$this->streamId}", 'front', $furlKey );
	}

	/**
	 * Get Attributes
	 *
	 * @return    string
	 */
	public function attributes(): string
	{
		return "data-streamid='{$this->id}'";
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		return Dispatcher::i()->application->directory === 'core' and Dispatcher::i()->module->key === 'discover' and Request::i()->id == $this->streamId;
	}
}