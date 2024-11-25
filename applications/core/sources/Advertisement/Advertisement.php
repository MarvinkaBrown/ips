<?php
/**
 * @brief		Advertisements Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Sept 2013
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Email;
use IPS\Extensions\AdvertisementLocationsAbstract;
use IPS\File;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Patterns\ActiveRecord;
use IPS\Redis;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use const IPS\CACHE_CONFIG;
use const IPS\CACHE_METHOD;
use const IPS\REDIS_CONFIG;
use const IPS\REDIS_ENABLED;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advertisements Model
 */
class Advertisement extends ActiveRecord
{
	/**
	 * @brief	HTML ad
	 */
	const AD_HTML	= 1;

	/**
	 * @brief	Images ad
	 */
	const AD_IMAGES	= 2;

	/**
	 * @brief	Email ad
	 */
	const AD_EMAIL	= 3;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_advertisements';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'ad_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	Advertisements loaded during this request (used to update impression count)
	 * @see		static::updateImpressions()
	 */
	public static array $advertisementIds	= array();

	/**
	 * @brief	Advertisements sent via email (used to update impression count)
	 * @see		static::updateEmailImpressions()
	 */
	public static array $advertisementIdsEmail = array();

	/**
	 * @brief	Stored advertisements we can display on this page
	 */
	protected static ?array $advertisements = NULL;

	/**
	 * @brief	Stored advertisements we can send in emails
	 */
	protected static ?array $emailAdvertisements = NULL;

	/**
	 * @brief	Stored advertisements for widgets
	 */
	protected static ?array $widgetAdvertisements = NULL;

	/**
	 * @brief	List all default location codes
	 */
	public static array $defaultLocations = [
		'ad_global_header',
		'ad_global_footer',
		'ad_sidebar'
	];

	/**
	 * Fetch advertisements and return the appropriate one to display
	 *
	 * @param	string		$location	Advertisement location
	 * @param 	int|null	$position	Position within a list
	 * @param 	int			$adsShown	Total ads already shown in this list
	 * @return    Advertisement|NULL
	 */
	public static function loadByLocation( string $location, ?int $position=null, int $adsShown=0 ) : ?static
	{
		/* If we know there are no ads, we don't need to bother */
		if ( !Settings::i()->ads_exist )
		{
			return NULL;
		}
		
		/* Fetch our advertisements, if we haven't already done so */
		if( static::$advertisements  === NULL )
		{
			static::$advertisements = array();

			$where[] = array( "ad_type!=?",  static::AD_EMAIL );
			$where[] = array( "ad_active=1" );
			$where[] = array( "ad_start<?", time() );
			$where[] = array( "(ad_end=0 OR ad_end>?)", time() );

			if( Dispatcher::hasInstance() AND ( !isset( Dispatcher::i()->dispatcherController ) OR !Dispatcher::i()->dispatcherController->isContentPage ) )
			{
				$where[] =array( 'ad_nocontent_page_output=?', 1 );
			}

			foreach( Db::i()->select( '*' ,'core_advertisements', $where ) as $row )
			{
				foreach ( explode( ',', $row['ad_location'] ) as $_location )
				{
					static::$advertisements[ $_location ][] = static::constructFromData( $row );
				}
			}
		}

		foreach( static::$advertisements as $adLocation => $ads )
		{
			foreach( $ads as $index => $ad )
			{
				/* Weed out any we don't see due to our group. This is done after loading the advertisements so that the cache can be properly primed regardless of group. Note that $ad->exempt, is, confusingly who to SHOW to, not who is exempt */
				if ( ! empty( $ad->exempt ) and $ad->exempt != '*' )
				{
					$groupsToHideFrom = array_diff( array_keys( Group::groups() ), json_decode( $ad->exempt, TRUE ) );

					if ( Member::loggedIn()->inGroup( $groupsToHideFrom ) )
					{
						unset( static::$advertisements[ $adLocation ][ $index ] );
						continue;
					}
				}

				/* Weed out any ads that we don't see due to the application settings */
				if( in_array( $adLocation, static::$defaultLocations ) AND isset( $ad->_additional_settings['ad_apps'] ) AND is_array( $ad->_additional_settings['ad_apps'] ) AND $ad->_additional_settings != 0 )
				{
					if( !in_array( Dispatcher::i()->application->directory, $ad->_additional_settings['ad_apps'] ) )
					{
						unset( static::$advertisements[ $adLocation ][ $index ] );
						continue;
					}
				}

				/* Remove advertisements that can't be shown based on settings */
				if( $extension = static::loadExtension( $location ) )
				{
					if( !$extension->canShow( $ad, $location ) )
					{
						unset( static::$advertisements[ $adLocation][ $index ] );
					}
				}
			}
		}

		/* No advertisements? Just return then */
		if( !count( static::$advertisements ) OR !isset( static::$advertisements[ $location ] ) OR !count( static::$advertisements[ $location ] ) )
		{
			return NULL;
		}

		return static::selectAdvertisement( static::$advertisements[ $location ], $position, $adsShown );
	}

	/**
	 * Find the Extension that handles this ad location
	 *
	 * @param string $location
	 * @return AdvertisementLocationsAbstract|null
	 */
	protected static function loadExtension( string $location ) : ?AdvertisementLocationsAbstract
	{
		foreach( Application::allExtensions( 'core', 'AdvertisementLocations' ) as $ext )
		{
			/* @var AdvertisementLocationsAbstract $ext */
			$extensionSettings = $ext->getSettings( array() );
			if( array_key_exists( $location, $extensionSettings['locations'] ) )
			{
				return $ext;
			}
		}

		return null;
	}

	/**
	 * Fetch advertisements for a particular widget
	 *
	 * @param	array	$additionalWhere
	 * @param	array|null	$ids	Specify the IDs to choose from
	 * @param 	int|null	$limit
	 * @return    array|NULL
	 */
	public static function loadForWidget( array $additionalWhere=array(), ?array $ids=null, ?int $limit=null ) : ?array
	{
		/* If we know there are no ads, we don't need to bother */
		if ( !Settings::i()->ads_exist )
		{
			return NULL;
		}

		/* Fetch our advertisements, if we haven't already done so */
		if( static::$widgetAdvertisements  === NULL )
		{
			static::$widgetAdvertisements = array();

			$where = [
				[ "ad_type!=?", static::AD_EMAIL ],
				[ "ad_active=?", 1 ],
				[ "ad_start<?", time() ],
				[ "(ad_end=0 OR ad_end>?)", time() ]
			];

			foreach( $additionalWhere as $clause )
			{
				$where[] = $clause;
			}

			foreach( Db::i()->select( '*' ,'core_advertisements', $where ) as $row )
			{
				static::$widgetAdvertisements[] = static::constructFromData( $row );
			}
		}

		$widgetsToUse = [];
		foreach( static::$widgetAdvertisements as $index => $ad )
		{
			/* Weed out any we don't see due to our group. This is done after loading the advertisements so that the cache can be properly primed regardless of group. Note that $ad->exempt, is, confusingly who to SHOW to, not who is exempt */
			if ( ! empty( $ad->exempt ) and $ad->exempt != '*' )
			{
				$groupsToHideFrom = array_diff( array_keys( Group::groups() ), json_decode( $ad->exempt, TRUE ) );

				if ( Member::loggedIn()->inGroup( $groupsToHideFrom ) )
				{
					continue;
				}
			}

			/* If the widget is not in our specified list, remove it */
			if( is_array( $ids ) and count( $ids ) and !in_array( $ad->id, $ids ) )
			{
				continue;
			}

			$widgetsToUse[] = $ad;
		}

		/* No advertisements? Just return then */
		if( !count( $widgetsToUse ) )
		{
			return NULL;
		}

		/* Shuffle so we display randomly */
		$limit = $limit ?? 1;
		if( count( $widgetsToUse ) > $limit )
		{
			shuffle( $widgetsToUse );
		}

		return array_slice( $widgetsToUse, 0, $limit );
	}

	/**
	 * Fetch advertisements for emails and return the appropriate one to display
	 *
	 * @param	array|null	$container	The container that spawned the email, or NULL
	 * @return    Advertisement|NULL
	 */
	public static function loadForEmail( ?array $container=NULL ) : ?static
	{
		/* If we know there are no ads, we don't need to bother */
		if ( !Settings::i()->ads_exist )
		{
			return NULL;
		}
		
		/* Fetch our advertisements, if we haven't already done so */
		if( static::$emailAdvertisements  === NULL )
		{
			static::$emailAdvertisements = array();

			foreach( Db::i()->select( '*' ,'core_advertisements', array( "ad_type=? AND ad_active=1 AND ad_start < ? AND ( ad_end=0 OR ad_end > ? )", static::AD_EMAIL, time(), time() ) ) as $row )
			{
				foreach ( explode( ',', $row['ad_location'] ) as $_location )
				{
					static::$emailAdvertisements[] = static::constructFromData( $row );
				}
			}
		}

		/* Whittle down the advertisements to use based on container limitations */
		$adsToCheckFrom = array();

		/* First see if we have any for this specific configuration */
		if( $container !== NULL )
		{
			foreach( static::$emailAdvertisements as $advertisement )
			{
				if( isset( $advertisement->_additional_settings['email_container'] ) AND isset( $advertisement->_additional_settings['email_node'] ) )
				{
					if( $advertisement->_additional_settings['email_container'] == $container['className'] AND $advertisement->_additional_settings['email_node'] == $container['id'] )
					{
						$adsToCheckFrom[] = $advertisement;
					}
				}
			}
		}

		/* If we didn't find any, then look for generic ones for the node class */
		if( $container !== NULL )
		{
			if( !count( $adsToCheckFrom ) )
			{
				foreach( static::$emailAdvertisements as $advertisement )
				{
					if( isset( $advertisement->_additional_settings['email_container'] ) AND ( !isset( $advertisement->_additional_settings['email_node'] ) OR !$advertisement->_additional_settings['email_node'] ) )
					{
						if( $advertisement->_additional_settings['email_container'] == $container['className'] )
						{
							$adsToCheckFrom[] = $advertisement;
						}
					}
				}
			}
		}

		/* If we still don't have any, look for generic ones allowed in all emails */
		if( !count( $adsToCheckFrom ) )
		{
			foreach( static::$emailAdvertisements as $advertisement )
			{
				if( !isset( $advertisement->_additional_settings['email_container'] ) OR $advertisement->_additional_settings['email_container'] == '*' )
				{
					$adsToCheckFrom[] = $advertisement;
				}
			}
		}

		/* No advertisements? Just return then */
		if( !count( $adsToCheckFrom ) )
		{
			return NULL;
		}

		return static::selectAdvertisement( $adsToCheckFrom );
	}

	/**
	 * Select an advertisement from an array and return it
	 *
	 * @param	array		$ads	Array of advertisements to select from
	 * @param int|null $position Position within a list
	 * @param int $adsShown Total ads already shown in this list
	 * @return	static|null
	 */
	static protected function selectAdvertisement( array $ads, ?int $position=null, int $adsShown=0 ) : ?static
	{
		/* Reset so we don't throw an error */
		$ads = array_values( $ads );

		/* If we have more than one ad, sort them according to the circulation settings */
		if( count( $ads ) > 1 )
		{
			/* Figure out which one to show you */
			switch ( Settings::i()->ads_circulation )
			{
				case 'random':
					shuffle( $ads );
					break;

				case 'newest':
					usort( $ads, function ( $a, $b )
					{
						return strcmp( $a->start, $b->start );
					} );
					break;

				case 'oldest':
					usort( $ads, function ( $a, $b )
					{
						return strcmp( $b->start, $a->start );
					} );
					break;

				case 'least':
					usort( $ads, function ( $a, $b )
					{
						if ( $a->impressions == $b->impressions )
						{
							return 0;
						}

						return ( $a->impressions < $b->impressions ) ? -1 : 1;
					} );
					break;
			}
		}

		/* Loop through the ads and find one that matches, based on positions and other settings */
		foreach( $ads as $ad )
		{
			/* @var Advertisement $ad */
			/* If we have no position specified, just grab the first one and stop */
			if( $position === null )
			{
				$advertisement = $ad;
				break;
			}

			$indexNumber = $ad->_additional_settings[ 'ad_view_number' ] ?? false;
			$repeat = $ad->_additional_settings[ 'ad_view_repeat' ] ?? false;

			if( $indexNumber !== false )
			{
				/* Check the position and see if it's a match */
				if( $position > $indexNumber AND $position % $indexNumber === 1 )
				{
					/* Did we already show it the maximum times? */
					if( $repeat !== false )
					{
						if( $repeat === -1 OR ( isset( $adsShown ) AND $repeat > $adsShown ) )
						{
							$advertisement = $ad;
							break;
						}
					}
					else
					{
						$advertisement = $ad;
						break;
					}
				}
			}
		}

		return $advertisement ?? null;
	}

	/**
	 * Convert the advertisement to an HTML string
	 *
	 * @param	string				$emailType	html or plaintext email advertisement
	 * @param	Email|NULL		$email		For an email advertisement, this will be the email object, otherwise NULL
	 * @return	string
	 */
	public function toString( string $emailType='html', ?Email $email=NULL ) : string
	{
		/* Showing HTML or an image? */
		if( $this->type == static::AD_HTML )
		{
			if( Request::i()->isSecure() AND $this->html_https_set )
			{
				$result	= $this->html_https;
			}
			else
			{
				$result	= $this->html;
			}
		}
		elseif( $this->type == static::AD_IMAGES )
		{
			$result	= Theme::i()->getTemplate( 'global', 'core', 'global' )->advertisementImage( $this );
		}
		elseif( $this->type == static::AD_EMAIL )
		{
			$result = Email::template( 'core', 'advertisement', $emailType, array( $this, $email ) );
		}

		/* Did we just hit the maximum impression count? If so, disable and then clear the cache so it will rebuild next time. */
		if( $this->maximum_unit == 'i' AND $this->maximum_value > -1 AND $this->impressions + 1 >= $this->maximum_value )
		{
			$this->active	= 0;
			$this->save();
			
			if ( !Db::i()->select( 'COUNT(*)', 'core_advertisements', 'ad_active=1' )->first() )
			{
				Settings::i()->changeValues( array( 'ads_exist' => 0 ) );
			}			
		}

		/* Store the id so we can update impression count and return the ad */
		if( $this->type == static::AD_EMAIL )
		{
			static::$advertisementIdsEmail[] = $this->id;
		}
		else
		{
			static::$advertisementIds[]	= $this->id;
		}
		
		return $result ?? '';
	}

	/**
	 * Convert the advertisement to an HTML string
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	/**
	 * Get images
	 *
	 * @return	array
	 */
	public function get__images() : array
	{
		if( !isset( $this->_data['_images'] ) )
		{
			$this->_data['_images']	= $this->_data['images'] ? json_decode( $this->_data['images'], TRUE ) : array();
		}

		return $this->_data['_images'];
	}
	
	/**
	 * Get additional settings
	 *
	 * @return	array
	 */
	public function get__additional_settings() : array
	{
		if( !isset( $this->_data['_additional_settings'] ) )
		{
			$this->_data['_additional_settings'] = $this->_data['additional_settings'] ? json_decode( $this->_data['additional_settings'], TRUE ) : array();
		}

		return $this->_data['_additional_settings'];
	}

	/**
	 * Get the file system storage extension
	 *
	 * @return string
	 */
	public function storageExtension() : string
	{
		if ( $this->member )
		{
			return 'nexus_Ads';
		}
		else
		{
			return 'core_Advertisements';
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* If we have images, delete them */
		if( count( $this->_images ) )
		{
			File::get( $this->storageExtension(), $this->_images['large'] )->delete();

			if( isset( $this->_images['small'] ) )
			{
				File::get( $this->storageExtension(), $this->_images['small'] )->delete();
			}

			if( isset( $this->_images['medium'] ) )
			{
				File::get( $this->storageExtension(), $this->_images['medium'] )->delete();
			}
		}

		/* Delete the translatable title */
		Lang::deleteCustom( 'core', "core_advert_{$this->id}" );
		
		/* Delete */
		parent::delete();
		
		/* Make sure we still have active ads */
		if ( !Db::i()->select( 'COUNT(*)', 'core_advertisements', 'ad_active=1' )->first() )
		{
			Settings::i()->changeValues( array( 'ads_exist' => 0 ) );
		}
	}

	/**
	 * Update ad impressions for advertisements loaded
	 *
	 * @return	void
	 */
	public static function updateImpressions() : void
	{
		if( count( static::$advertisementIds ) )
		{
			static::updateCounter( static::$advertisementIds );

			/* Reset in case execution continues and more ads are shown */
			static::$advertisementIds = array();
		}
	}

	/**
	 * Update ad impressions for advertisements sent in emails
	 *
	 * @param	int		$impressions	Number of impressions (may be more than one if mergeAndSend() was called)
	 * @return	void
	 */
	public static function updateEmailImpressions( int $impressions=1 ) : void
	{
		if( count( static::$advertisementIdsEmail ) )
		{
			static::updateCounter( static::$advertisementIdsEmail, $impressions );

			/* Reset in case execution continues and more ads are sent */
			static::$advertisementIdsEmail = array();
		}
	}

	/**
	 * Update the advert impression counters
	 *
	 * @param array $ids	Array of IDs
	 * @param int $by		Number to increment by
	 * @return void
	 */
	protected static function updateCounter( array $ids, int $by=1 ) : void
	{
		$countUpdated = false;
		if ( Redis::isEnabled() )
		{
			foreach( $ids as $id )
			{
				try
				{
					Redis::i()->zIncrBy( 'advert_impressions', $by, $id );
					$countUpdated = true;
				}
				catch ( Exception $e )
				{
				}
			}
		}

		if ( ! $countUpdated )
		{
			Db::i()->update( 'core_advertisements', "ad_impressions=ad_impressions+" . $by . ", ad_daily_impressions=ad_daily_impressions+" . $by, "ad_id IN(" . implode( ',', $ids ) . ")" );
		}
	}
}


