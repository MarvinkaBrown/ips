<?php
/**
 * @brief		Front Navigation Extension: Your Activity Streams
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Jun 2015
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Http\Url;
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
 * Front Navigation Extension: Your Activity Streams
 */
class YourActivityStreams extends FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon = '\f0ae';

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('your_activity_streams_acp');
	}
		
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return    bool
	 */
	public function canAccessContent(): bool
	{
		return Member::loggedIn()->canAccessModule( Module::get( 'core', 'discover' ) ) and count( $this->children() );
	}
	
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack('your_activity_streams');
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		return NULL;
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		return ( Dispatcher::i()->application->directory === 'core' and Dispatcher::i()->module->key === 'discover' and ( isset( Request::i()->id ) or ( isset( Request::i()->do ) and Request::i()->do == 'create' ) ) );
	}
	
	/**
	 * Cached Children
	 *
	 * @return	array
	 */
	protected static array $children = array();
	
	/**
	 * Items
	 *
	 * @param	Member|null	$member	Member or NULL for currently logged in member
	 * @return	array
	 */
	public static function items( ?Member $member = NULL ) : array
	{	
		$member = $member ?: Member::loggedIn();
		
		if ( !isset( static::$children[ $member->member_id ] ) )
		{
			static::$children[ $member->member_id ] = array();
			
			if ( !isset( Store::i()->globalStreamIds ) )
			{
				$globalStreamIds = array();
				foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_streams', '`member` IS NULL', 'position ASC' ), 'IPS\core\Stream' ) as $stream )
				{
					$globalStreamIds[ $stream->id ] = ( $stream->ownership == 'all' and $stream->read == 'all' and $stream->follow == 'all' and $stream->date_type != 'last_visit' );
				}
				
				Store::i()->globalStreamIds = $globalStreamIds;
			}
					
			$globalStreamIdsToShow = array_keys( !$member->member_id ? array_filter( Store::i()->globalStreamIds ) : Store::i()->globalStreamIds );
			
			if ( count( $globalStreamIdsToShow ) )
			{				
				foreach ( $globalStreamIdsToShow as $id )
				{
					static::$children[ $member->member_id ][] = new YourActivityStreamsItem( array(), $id, '*', '*', null );
				}
			}
			
			if ( $member->member_id )
			{
				if ( $member->member_streams and $streams = json_decode( $member->member_streams, TRUE ) and count( $streams['streams'] ) )
				{
					static::$children[ $member->member_id ][] = new MenuSeparator;
					foreach ( $streams['streams'] as $id => $title )
					{
						static::$children[ $member->member_id ][] = new YourActivityStreamsItem( array( 'title' => $title ), $id, '*', '*', null );
					}
				}
				static::$children[ $member->member_id ][] = new MenuButton( 'create_new_stream', Url::internal( "app=core&module=discover&controller=streams&do=create", 'front', 'discover_all' ) );

			}
		}
		
		return static::$children[ $member->member_id ];
	}
	
	/**
	 * Children
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return    array|null
	 */
	public function children( bool $noStore=FALSE ): array|null
	{	
		return static::items();		
	}
}