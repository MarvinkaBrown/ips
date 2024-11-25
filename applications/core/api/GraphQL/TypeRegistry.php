<?php
/**
 * @brief		GraphQL: Core type registry
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL;
use IPS\core\api\GraphQL\Types\ActiveUsersType;
use IPS\core\api\GraphQL\Types\ActiveUserType;
use IPS\core\api\GraphQL\Types\AttachmentPermissionsType;
use IPS\core\api\GraphQL\Types\AttachmentType;
use IPS\core\api\GraphQL\Types\ClubNodeType;
use IPS\core\api\GraphQL\Types\ClubType;
use IPS\core\api\GraphQL\Types\ContentReactionType;
use IPS\core\api\GraphQL\Types\ContentSearchResultType;
use IPS\core\api\GraphQL\Types\GroupType;
use IPS\core\api\GraphQL\Types\IgnoreOptionType;
use IPS\core\api\GraphQL\Types\LanguageType;
use IPS\core\api\GraphQL\Types\LoginType;
use IPS\core\api\GraphQL\Types\MemberType;
use IPS\core\api\GraphQL\Types\MessengerConversationType;
use IPS\core\api\GraphQL\Types\MessengerFolderType;
use IPS\core\api\GraphQL\Types\MessengerParticipantType;
use IPS\core\api\GraphQL\Types\MessengerReplyType;
use IPS\core\api\GraphQL\Types\NotificationMethodType;
use IPS\core\api\GraphQL\Types\NotificationType;
use IPS\core\api\GraphQL\Types\NotificationTypeType;
use IPS\core\api\GraphQL\Types\PollQuestionType;
use IPS\core\api\GraphQL\Types\PollType;
use IPS\core\api\GraphQL\Types\PopularContributorType;
use IPS\core\api\GraphQL\Types\ProfileFieldGroupType;
use IPS\core\api\GraphQL\Types\ProfileFieldType;
use IPS\core\api\GraphQL\Types\PromotedItemType;
use IPS\core\api\GraphQL\Types\ReportReasonType;
use IPS\core\api\GraphQL\Types\ReportType;
use IPS\core\api\GraphQL\Types\SearchResultType;
use IPS\core\api\GraphQL\Types\SearchType;
use IPS\core\api\GraphQL\Types\SettingsType;
use IPS\core\api\GraphQL\Types\StatsType;
use IPS\core\api\GraphQL\Types\StreamType;
use IPS\core\api\GraphQL\Types\TagType;
use IPS\core\api\GraphQL\Types\UploadProgressType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Core type registry. GraphQL requires exactly one instance of each type,
 * so we'll generate singletons here.
 * @todo automate this somehow?
 */
class TypeRegistry
{
	protected static ?ActiveUsersType $activeUsers = null;
	protected static ?ActiveUserType $activeUser = null;
	protected static ?AttachmentType $attachment = null;
	protected static ?AttachmentPermissionsType $attachmentPermissions = null;
	protected static ?ClubNodeType $clubNode = null;
	protected static ?ClubType $club = null;
	protected static ?ContentReactionType $contentReaction = null;
	protected static ?ContentSearchResultType $contentSearchResult = null;
	protected static ?GroupType $group = null;
	protected static ?IgnoreOptionType $ignoreOption = null;
	protected static ?MemberType $member = null;
	protected static ?LanguageType $language = null;
	protected static ?LoginType $login = null;
	protected static ?MessengerConversationType $messengerConversation = null;
	protected static ?MessengerFolderType $messengerFolder = null;
	protected static ?MessengerParticipantType $messengerParticipant = null;
	protected static ?MessengerReplyType $messengerReply = null;
	protected static ?NotificationType $notification = null;
	protected static ?NotificationMethodType $notificationMethod = null;
	protected static ?NotificationTypeType $notificationType = null;
	protected static ?PollType $poll = null;
	protected static ?PollQuestionType $pollQuestion = null;
	protected static ?PopularContributorType $popularContributor = null;
	protected static ?ProfileFieldGroupType $profileFieldGroup = null;
	protected static ?ProfileFieldType $profileField = null;
	protected static ?PromotedItemType $promotedItem = null;
	protected static ?ReportType $report = null;
	protected static ?ReportReasonType $reportReason = null;
	protected static ?SearchType $search = null;
	protected static ?SearchResultType $searchResult = null;
	protected static ?StatsType $stats = null;
	protected static ?StreamType $stream = null;
	protected static ?TagType $tag = null;
	protected static ?SettingsType $settings = null;
	protected static ?UploadProgressType $uploadProgress = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Defined to suppress static warnings
	}

	/**
	 * @return ActiveUsersType
	 */
	public static function activeUsers() : ActiveUsersType
	{
		return self::$activeUsers ?: (self::$activeUsers = new ActiveUsersType());
	}

	/**
	 * @return ActiveUserType
	 */
	public static function activeUser() : ActiveUserType
	{
		return self::$activeUser ?: (self::$activeUser = new ActiveUserType());
	}
	
	/**
	 * @return AttachmentType
	 */
	public static function attachment() : AttachmentType
	{
		return self::$attachment ?: (self::$attachment = new AttachmentType());
	}
	
	/**
	 * @return AttachmentPermissionsType
	 */
	public static function attachmentPermissions() : AttachmentPermissionsType
	{
		return self::$attachmentPermissions ?: (self::$attachmentPermissions = new AttachmentPermissionsType());
	}

	/**
	 * @return ClubNodeType
	 */
	public static function clubNode() : ClubNodeType
	{
		return self::$clubNode ?: (self::$clubNode = new ClubNodeType());
	}

	/**
	 * @return ClubType
	 */
	public static function club() : ClubType
	{
		return self::$club ?: (self::$club = new ClubType());
	}

	/**
	 * @return ContentReactionType
	 */
	public static function contentReaction() : ContentReactionType
	{
		return self::$contentReaction ?: (self::$contentReaction = new ContentReactionType());
	}

	/**
	 * @return GroupType
	 */
	public static function group() : GroupType
	{
		return self::$group ?: (self::$group = new GroupType());
	}

	/**
	 * @return IgnoreOptionType
	 */
	public static function ignoreOption() : IgnoreOptionType
	{
		return self::$ignoreOption ?: (self::$ignoreOption = new IgnoreOptionType());
	}

	/**
	 * @return MemberType
	 */
	public static function member() : MemberType
	{
		return self::$member ?: (self::$member = new MemberType());
	}

	/**
	 * @return LanguageType
	 */
	public static function language() : LanguageType
	{
		return self::$language ?: (self::$language = new LanguageType());
	}

	/**
	 * @return LoginType
	 */
	public static function login() : LoginType
	{
		return self::$login ?: (self::$login = new LoginType());
	}

	/**
	 * @return MessengerConversationType
	 */
	public static function messengerConversation() : MessengerConversationType
	{
		return self::$messengerConversation ?: (self::$messengerConversation = new MessengerConversationType());
	}

	/**
	 * @return MessengerFolderType
	 */
	public static function messengerFolder() : MessengerFolderType
	{
		return self::$messengerFolder ?: (self::$messengerFolder = new MessengerFolderType());
	}

	/**
	 * @return MessengerParticipantType
	 */
	public static function messengerParticipant() : MessengerParticipantType
	{
		return self::$messengerParticipant ?: (self::$messengerParticipant = new MessengerParticipantType());
	}

	/**
	 * @return MessengerReplyType
	 */
	public static function messengerReply() : MessengerReplyType
	{
		return self::$messengerReply ?: (self::$messengerReply = new MessengerReplyType());
	}

	/**
	 * @return NotificationType
	 */
	public static function notification() : NotificationType
	{
		return self::$notification ?: (self::$notification = new NotificationType());
	}

	/**
	 * @return NotificationMethodType
	 */
	public static function notificationMethod() : NotificationMethodType
	{
		return self::$notificationMethod ?: (self::$notificationMethod = new NotificationMethodType());
	}

	/**
	 * @return NotificationTypeType
	 */
	public static function notificationType() : NotificationTypeType
	{
		return self::$notificationType ?: (self::$notificationType = new NotificationTypeType());
	}

	/**
	 * @return PollType
	 */
	public static function poll() : PollType
	{
		return self::$poll ?: (self::$poll = new PollType());
	}

	/**
	 * @return PollQuestionType
	 */
	public static function pollQuestion(): PollQuestionType
	{
		return self::$pollQuestion ?: (self::$pollQuestion = new PollQuestionType());
	}

	/**
	 * @return PopularContributorType
	 */
	public static function popularContributor(): PopularContributorType
	{
		return self::$popularContributor ?: (self::$popularContributor = new PopularContributorType());
	}

	/**
	 * @return ProfileFieldGroupType
	 */
	public static function profileFieldGroup(): ProfileFieldGroupType
    {
        return self::$profileFieldGroup ?: (self::$profileFieldGroup = new ProfileFieldGroupType());
    }

	/**
	 * @return ProfileFieldType
	 */
	public static function profileField(): ProfileFieldType
	{
		return self::$profileField ?: (self::$profileField = new ProfileFieldType());
	}

	/**
	 * @return PromotedItemType
	 */
	public static function promotedItem(): PromotedItemType
	{
		return self::$promotedItem ?: (self::$promotedItem = new PromotedItemType());
	}

	/**
	 * @return ContentSearchResultType
	 */
	public static function contentSearchResult(): ContentSearchResultType
	{
		return self::$contentSearchResult ?: (self::$contentSearchResult = new ContentSearchResultType());
	}

	/**
	 * @return ReportType
	 */
	public static function report(): ReportType
    {
        return self::$report ?: (self::$report = new ReportType());
    }

	/**
	 * @return ReportReasonType
	 */
	public static function reportReason(): ReportReasonType
    {
        return self::$reportReason ?: (self::$reportReason = new ReportReasonType());
    }

	/**
	 * @return SearchResultType
	 */
	public static function searchResult(): SearchResultType
	{
		return self::$searchResult ?: (self::$searchResult = new SearchResultType());
	}

	/**
	 * @return SearchType
	 */
	public static function search(): SearchType
	{
		return self::$search ?: (self::$search = new SearchType());
	}

	/**
	 * @return StatsType
	 */
	public static function stats(): StatsType
	{
		return self::$stats ?: (self::$stats = new StatsType());
	}

	/**
	 * @return StreamType
	 */
	public static function stream(): StreamType
	{
		return self::$stream ?: (self::$stream = new StreamType());
	}

	/**
	 * @return TagType
	 */
	public static function tag(): TagType
	{
		return self::$tag ?: (self::$tag = new TagType());
	}

	/**
	 * @return SettingsType
	 */
	public static function settings(): SettingsType
	{
		return self::$settings ?: (self::$settings = new SettingsType());
	}

	/**
	 * @return UploadProgressType
	 */
	public static function uploadProgress(): UploadProgressType
	{
		return self::$uploadProgress ?: (self::$uploadProgress = new UploadProgressType());
	}
}