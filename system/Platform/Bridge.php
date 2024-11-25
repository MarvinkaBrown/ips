<?php
/**
 * @brief		Platform bridge
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 May 2023
 */

namespace IPS\Platform;

use IPS\Application;
use IPS\Content;
use IPS\File;
use IPS\GeoLocation;
use IPS\Member;
use IPS\Request;
use IPS\forums\Topic;
use IPS\Output;

/**
 * Bridge model
 * This is really a handy way to call a method that runs the cloud method if cloud is enabled.
 * It avoids lots of appIsEnabled() checks and replaces a lot of the hooks we had.
 */
class Bridge
{
	/**
	 * @brief This is a map of the default return values for these methods. It's cleaner than writing an entire method for every single one
	 * @var array{mixed}
	 */
	protected const DEFAULT_RETURN_VALUES = array(
		'_topicHasSummary' => false,
		'topicPostSummaryBlurb' => '',
		'commentFormFields' => [],
		'imageLabelsForSearch' => [],
		'analyticsItem' => [],
		'awsGenerateTemporaryDownloadUrl' => null,
		'bounceMemberViewGetTableJoins' => [],
		'bounceMemberViewGetTableFilters' => [],
		'pagesAllowDatabaseAccess' => true,
		'core_admin_applications_apiKeys' => true,
		'checkPlatformPermission' => true,
		'coreAdminWebhooks' => true,
		'checkItemForSpam' => false,
		'checkCommentForSpam' => false
	);

	/**
	 * @brief This is an array of methods which should return a void type (instead of the default null)
	 * @var array{string}
	 */
	protected const VOID_RETURN_METHODS = array(
		'frontDispatcherFinish',
		'refreshRealTimeToken',
		'liveTopicCreateFormFromCalendar',
		'bounceMemberViewRequestEmailUnblock',
		'dispatcherFinish'
	);

	/**
	 * @brief	Bridge Instances
	 * @var static|\IPS\cloud\Platform\Bridge|null
	 */
	protected static NULL|Bridge|\IPS\cloud\Platform\Bridge $instance = NULL;

	/**
	 * Get instance
	 *
	 * @return	static|\IPS\cloud\Platform\Bridge
	 */
	public static function i(): static
	{
		if ( static::$instance === NULL )
		{
			$classname = ( Application::appIsEnabled( 'cloud' ) AND class_exists( '\\IPS\\cloud\\Platform\\Bridge' ) ) ? '\\IPS\\cloud\\Platform\\Bridge' : static::class;
			static::$instance = new $classname;
		}

		return static::$instance;
	}

	/**
	 * Call
	 *
	 * @param string $name	Method name
	 * @param array $args	Method arguments
	 *
	 * @return	mixed|void
	 */
	public function __call( string $name, array $args )
	{
		if ( !is_subclass_of( $this, self::class ) )
		{
			if ( array_key_exists( $name, self::DEFAULT_RETURN_VALUES ) )
			{
				return self::DEFAULT_RETURN_VALUES[$name];
			}

			if ( in_array( $name, self::VOID_RETURN_METHODS ) ) {
				return;
			}
		}

		return NULL;
	}

	/**
	 * @was cloud/hooks/ipsDispatcherAdmin::buildMenu()
	 */
	public function alterAcpMenu( $menu ): ?array
	{
		return $menu;
	}

    /**
     * @was cloud/hooks/ipsHelpersFormUpload::populateTempData()
     */
    public function uploadPopulateTempData(array $data, File $fileObj, array $options): array
    {
        return $data;
    }

	/**
	 * @was cloud/hooks/ipsGeolocation::getRequesterLocation()
	 */
	public function getRequesterLocation(): Geolocation
	{
		return Geolocation::getByIp( Request::i()->ipAddress() );
	}
	
	/**
	 *
	 * @param Topic $topic
	 * @param array|string|null $where
	 * @return array|string|null
	 */
	public function modifyTopicCommentFilter( Topic $topic, array|null|string $where ) : array|null|string
	{
		return $where;
	}

	/**
	 * @param string|array|null $where
	 * @return void
	 */
	public function addOrRemovePostFromSummary( string|array|null $where = null ) : void
	{
		Output::i()->json([
			'userMessage' => "Topic Summarization is not enabled on this community."
						  ], 401);
	}

	/**
	 * PBR Check for spam
	 *
	 * @param Member $member
	 * @param Content $content
	 * @param bool $moderated
	 * @return bool
	 */
	public function cloudPbrProcessMember( Member $member, Content $content, bool $moderated ): bool
	{
		return $moderated;
	}
}
