<?php
/**
 * @brief		Notification Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Apr 2013
 */

namespace IPS\core\extensions\core\Notifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\core\Reports\Report;
use IPS\core\Reports\Types;
use IPS\core\Warnings\Warning;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\NotificationsAbstract;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Notification\Inline;
use IPS\Settings;
use OutOfRangeException;
use function defined;
use function get_class;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class Moderation extends NotificationsAbstract
{	
	/**
	 * Get fields for configuration
	 *
	 * @param	Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	public static function configurationOptions( ?Member $member = NULL ): array
	{
		$return = array();
		
		/* Reports */
		if ( $member === NULL or $member->canAccessReportCenter() )
		{
			$return['report_center'] = array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'report_center' ),
				'title'				=> 'notifications__core_Moderation_report_center',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__core_Moderation_report_center_desc',
				'default'			=> array( 'email' ),
				'disabled'			=> array( 'inline', 'push' ),
				'extra'				=> array(
					'report_count'		=> array(
						'title'				=> 'report_count',
						'description'		=> 'report_count_desc',
						'icon'				=> 'list-check',
						'value'				=> $member ? ( !$member->members_bitoptions['no_report_count'] ) : NULL,
						'adminCanSetDefault'=> FALSE,
					)
				)
			);
			
			if ( Db::i()->select( 'COUNT(*)', 'core_automatic_moderation_rules', array( 'rule_enabled=1' ) )->first() )
			{
				$return['automatic_moderation'] = array(
					'type'				=> 'standard',
					'notificationTypes'	=> array( 'automatic_moderation' ),
					'title'				=> 'notifications__core_Moderation_automatic_moderation',
					'showTitle'			=> FALSE,
					'description'		=> 'notifications__core_Moderation_automatic_moderation_desc',
					'default'			=> array( 'inline', 'email' ),
					'disabled'			=> array(),
				);
			}
		}
		
		/* Content/Clubs needing approval */
		$canSeePendingContent = ( !$member or $member->modPermission( 'can_view_hidden_content' ) );
		if ( !$canSeePendingContent )
		{
			foreach ( Content::routedClasses( TRUE, TRUE ) as $class )
			{
				if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
				{
					if ( $member->modPermission( 'can_view_hidden_' . $class::$title ) )
					{
						$canSeePendingContent = TRUE;
						break;
					}
				}
			}
		}
		$canApproveClubs = FALSE;
		if ( Settings::i()->clubs and Settings::i()->clubs_require_approval and $module = Module::get( 'core', 'clubs', 'front' ) and $module->_enabled )
		{
			$canApproveClubs = ( !$member or $member->modPermission( 'can_access_all_clubs' ) );
		}
		if ( $canSeePendingContent or $canApproveClubs )
		{
			$return['unapproved_content'] = array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'unapproved_content', 'unapproved_club' ),
				'title'				=> 'notifications__core_Moderation_unapproved_content',
				'showTitle'			=> TRUE,
				'description'		=> ( $canSeePendingContent and $canApproveClubs ) ? 'notifications__core_Moderation_unapproved_content_desc_both' : ( $canSeePendingContent ? 'notifications__core_Moderation_unapproved_content_desc_content' : 'notifications__core_Moderation_unapproved_content_desc_clubs' ),
				'default'			=> array( 'inline', 'email' ),
				'disabled'			=> array(),
			);
		} 
		
		/* Warnings */
		if ( !$member or $member->modPermission( 'mod_see_warn' ) )
		{
			$return['warning_mods'] = array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'warning_mods' ),
				'title'				=> 'notifications__core_Moderation_warning_mods',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__core_Moderation_warning_mods_desc',
				'default'			=> array( 'inline' ),
				'disabled'			=> array(),
			);
		}

		/* Do we have any report types, and can we report things? ? */
		if ( Db::i()->select( 'COUNT(*)', 'core_automatic_moderation_types' )->first() and ( ! $member or $member->group['g_can_report'] ) )
		{
			$return['reports'] = [
				'type' => 'standard',
				'notificationTypes' => ['report_outcome'],
				'title' => 'notifications__core_Moderation_reports',
				'showTitle' => true,
				'description' => 'notifications__core_Moderation_reports_desc',
				'default' => ['email'],
				'disabled' => [ 'push', 'inline' ],
			];
		}

		return $return;
	}
	
	/**
	 * Save "extra" value
	 *
	 * @param	Member|null	$member	The member
	 * @param	string		$key	The key
	 * @param	mixed		$value	The value
	 * @return	void
	 */
	public static function saveExtra( ?Member $member, string $key, mixed $value ) : void
	{
		switch ( $key )
		{
			case 'report_count':
				$member->members_bitoptions['no_report_count'] = !$value;
				break;
		}
	}
	
	/**
	 * Reset "extra" value to the default for all accounts
	 *
	 * @return	void
	 */
	public static function resetExtra() : void
	{
		Db::i()->update( 'core_members', 'members_bitoptions2 = members_bitoptions2 &~' . Member::$bitOptions['members_bitoptions']['members_bitoptions2']['no_report_count'] );
	}
	
	/**
	 * Get configuration
	 *
	 * @param	Member|null	$member	The member
	 * @return	array
	 */
	public function getConfiguration( ?Member $member ) : array
	{
		$return = array();
										
		if ( $member === NULL or $member->modPermission( 'can_view_hidden_content' ) )
		{
			$return['unapproved_content'] = array( 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'lock' );
		}
		else
		{
			foreach ( Content::routedClasses( TRUE, TRUE ) as $class )
			{
				if ( IPS::classUSesTrait( $class, 'IPS\Content\Hideable' ) )
				{
					if ( $member->modPermission( 'can_view_hidden_' . $class::$title ) )
					{
						$return['unapproved_content'] = array( 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'lock' );
						break;
					}
				}
			}
		}
		
		return $return;
	}

	/**
	 * Parse notification: Report outcomes
	 *
	 * @param	\IPS\Notification\Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	return array(
	'title'		=> "Mark has replied to A Topic",	// The notification title
	'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	// explains what the notification is about - just include any appropriate content.
	// For example, if the notification is about a post, set this as the body of the post.
	'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	);
	 * @endcode
	 */
	public function parse_report_outcome( $notification, $htmlEscape=TRUE )
	{
		if ( ! $notification->item_sub or ! count( $notification->extra ) )
		{
			throw new \OutOfRangeException;
		}
		$reported = $notification->item_sub;
		$item = ( $reported instanceof Item ) ? $reported : $reported->item();

		try
		{
			$reportType = Types::load( $notification->extra['reportType'] );
			$class = $item->class;
			$url = $class::load( $item->content_id )->url();
		}
		catch( \UnderflowException )
		{
			throw new \OutOfRangeException;
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__report_outcome', FALSE, array(
					( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $item->mapped('title'), $reportType->_title, Member::loggedIn()->language()->addToStack( 'report_status_' . $item->status ) ) )
			),
			'url'		=> $url,
			'author'	=> Member::loggedIn(),
		);
	}

	/**
	 * Parse notification: unapproved_content_bulk
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_unapproved_content_bulk( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$node = $notification->item;
		
		if ( !$node )
		{
			throw new OutOfRangeException;
		}
		
		if ( $notification->extra )
		{
			/* \IPS\Notification->extra will always be an array, but for bulk content notifications we are only storing a single member ID,
				so we need to grab just the one array entry (the member ID we stored) */
			$memberId = $notification->extra;

			if( is_array( $memberId ) )
			{
				$memberId = array_pop( $memberId );
			}

			$author = Member::load( $memberId );
		}
		else
		{
			$author = new Member;
		}
		
		$contentClass = $node::$contentItemClass;
		
		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__unapproved_content_bulk', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array(
					$author->name,
					Member::loggedIn()->language()->get( $contentClass::$title . '_pl_lc' ),
					$node->getTitleForLanguage( Member::loggedIn()->language(), $htmlEscape ? array( 'escape' => TRUE ) : array() )
				)
			) ),
			'url'		=> $node->url(),
			'author'	=> $author
		);
	}
		
	/**
	 * Parse notification: warning_mods
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 	return array(
	 		'title'		=> "Mark has replied to A Topic",	// The notification title
	 		'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 		'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 														// explains what the notification is about - just include any appropriate content.
	 														// For example, if the notification is about a post, set this as the body of the post.
	 		'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 	);
	 * @endcode
	 */
	public function parse_warning_mods( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		if ( !$notification->item )
		{
			throw new OutOfRangeException;
		}
		
		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__warning_mods', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( Member::load( $notification->item->member )->name, Member::load( $notification->item->moderator )->name ) )
			),
			'url'		=> $notification->item->url(),
			'content'	=> $notification->item->note_mods,
			'author'	=> Member::load( $notification->item->moderator ),
		);
	}
	
	/**
	 * Parse notification for mobile: warning_mods
	 *
	 * @param	Lang					$language	The language that the notification should be in
	 * @param	Warning	$warning		The warning
	 * @return	array
	 */
	public static function parse_mobile_warning_mods( Lang $language, Warning $warning ) : array
	{
		return array(
			'title'		=> $language->addToStack( 'notification__warning_mods_title', FALSE, array( 'pluralize' => array(1) ) ),
			'body'		=> $language->addToStack( 'notification__warning_mods', FALSE, array( 'htmlsprintf' => array(
				Member::load( $warning->member )->name,
				Member::load( $warning->moderator )->name
			) ) ),
			'data'		=> array(
				'url'		=> (string) $warning->url(),
				'author'	=> $warning->moderator,
				'grouped'	=> $language->addToStack( 'notification__warning_mods_grouped'), // Pluralized on the client
				'groupedTitle' => $language->addToStack( 'notification__warning_mods_title' ), // Pluralized on the client
				'groupedUrl' => Url::internal( 'app=core&module=modcp&controller=modcp&tab=recent_warnings', 'front', 'modcp_recent_warnings' )
			),
			'tag' => md5('recent-warnings'), // Group warning notifications
			'channelId'	=> 'moderation',
		);
	}
	
	/**
	 * Parse notification for mobile: unapproved_content_bulk
	 *
	 * @param	Lang			$language		The language that the notification should be in
	 * @param	Model		$node			The node with the new content
	 * @param	Member			$author			The author
	 * @param	string				$contentClass	The content class
	 * @return	array
	 */
	public static function parse_mobile_unapproved_content_bulk( Lang $language, Model $node, Member $author, string $contentClass ) : array
	{
		/* @var Content $contentClass */
		return array(
			'title'		=> $language->addToStack( 'notification__unapproved_content_bulk_title', FALSE, array( 'htmlsprintf' => array(
				$language->get( $contentClass::$title . '_pl_lc' ),
			) ) ),
			'body'		=> $language->addToStack( 'notification__unapproved_content_bulk', FALSE, array( 'htmlsprintf' => array(
				$author->name,
				$language->get( $contentClass::$title . '_pl_lc' ),
				$node->getTitleForLanguage( $language )
			) ) ),
			'data'		=> array(
				'url'		=> (string) $node->url(),
				'author'	=> $author
			),
			'channelId'	=> 'moderation',
		);
	}
	
	/**
	 * Parse notification: report_center
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 	return array(
	 		'title'		=> "Mark has replied to A Topic",	// The notification title
	 		'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 		'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 														// explains what the notification is about - just include any appropriate content.
	 														// For example, if the notification is about a post, set this as the body of the post.
	 		'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 	);
	 * @endcode
	 */
	public function parse_report_center( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		if ( !$notification->item_sub )
		{
			throw new OutOfRangeException;
		}

		$reported = $notification->item_sub;
		$item = ( $reported instanceof Item ) ? $reported : $reported->item();

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__report_center', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $notification->item->author()->name, mb_strtolower( $reported->indefiniteArticle() ), $item->mapped('title' ) ) )
			),
			'url'		=> $notification->item->url(),
			'content'	=> NULL,
			'author'	=> $notification->item->author(),
		);
	}
	
	/**
	 * Parse notification for mobile: report_center
	 *
	 * @param	Lang					$language		The language that the notification should be in
	 * @param	Report	$report			The report
	 * @param	array						$latestReport	Information about this specific report
	 * @param	Content				$reportedContent	The content that was reported
	 * @return	array
	 */
	public static function parse_mobile_report_center( Lang $language, Report $report, array $latestReport, Content $reportedContent ) :array
	{
		return array(
			'title'		=> $language->addToStack( 'notification__report_center_title' ),
			'body'		=> $language->addToStack( 'notification__report_center', FALSE, array( 'htmlsprintf' => array(
				Member::load( $latestReport['report_by'] )->name,
				mb_strtolower( $reportedContent->indefiniteArticle( $language ) ),
				$report->mapped('title')
			) ) ),
			'data'		=> array(
				'url'		=> (string) $report->url(),
				'author'	=> $report->author(),
				'grouped'	=> $language->addToStack( 'notification__report_center_grouped'), // Pluralized on the client
				'groupedTitle' => $language->addToStack( 'notification__report_center_title' ), // Pluralized on the client
				'groupedUrl' => Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports', NULL, 'modcp_reports' )
			),
			'tag' => md5('report-center'),
			'channelId'	=> 'moderation',
		);
	}
	
	/**
	 * Parse notification: automatic_moderation
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 	return array(
	 		'title'		=> "Mark has replied to A Topic",	// The notification title
	 		'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 		'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 														// explains what the notification is about - just include any appropriate content.
	 														// For example, if the notification is about a post, set this as the body of the post.
	 		'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 	);
	 * @endcode
	 */
	public function parse_automatic_moderation( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		if ( !$notification->item_sub )
		{
			throw new OutOfRangeException;
		}

		$reported = $notification->item_sub;
		$item = ( $reported instanceof Item ) ? $reported : $reported->item();

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__automatic_moderation', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( mb_strtolower( $reported->indefiniteArticle() ), $item->mapped('title' ) ) )
			),
			'url'		=> $notification->item->url(),
			'content'	=> NULL,
			'author'	=> $notification->item->author(),
		);
	}
	
	/**
	 * Parse notification for mobile: automatic_moderation
	 *
	 * @param	Lang					$language		The language that the notification should be in
	 * @param	Report		$report			The report
	 * @param	array						$latestReport	Information about this specific report
	 * @param	Content					$reportedContent	The content that was reported
	 * @return	array
	 */
	public static function parse_mobile_automatic_moderation( Lang $language, Report $report, array $latestReport, Content $reportedContent ) : array
	{
		return array(
			'title'		=> $language->addToStack( 'notification__automatic_moderation_title' ),
			'body'		=> $language->addToStack( 'notification__automatic_moderation', FALSE, array( 'htmlsprintf' => array(
				mb_strtolower( $reportedContent->indefiniteArticle( $language ) ),
				$report->mapped('title')
			) ) ),
			'data'		=> array(
				'url'		=> (string) $report->url(),
				'author'	=> $report->author()
			),
			'channelId'	=> 'moderation',
		);
	}
	
	/**
	 * Parse notification: unapproved_content
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 	return array(
	 		'title'		=> "Mark has replied to A Topic",	// The notification title
	 		'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 		'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 														// explains what the notification is about - just include any appropriate content.
	 														// For example, if the notification is about a post, set this as the body of the post.
	 		'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 	);
	 * @endcode
	 */
	public function parse_unapproved_content( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		if ( !$notification->item )
		{
			throw new OutOfRangeException;
		}
		
		$item = $notification->item;
		
		if ( $item instanceof Comment )
		{
			$title = $item->item()->mapped('title');

			/* Unread? */
			$unread = false;
			if ( $item->item()->timeLastRead() instanceof DateTime )
			{
				$unread =( $item->item()->timeLastRead()->getTimestamp() < $notification->updated_time->getTimestamp() );
			}
		}
		else
		{
			$title = $item->mapped('title');
			$unread = (bool) ( $item->unread() );
		}
		
		$name = ( IPS::classUsesTrait( $item, 'IPS\Content\Anonymous' ) AND $item->isAnonymous() ) ? Member::loggedIn()->language()->addToStack( 'post_anonymously_placename' ) : $item->author()->name;
		
		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__unapproved_content', FALSE, array( ( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $name, mb_strtolower( $item->indefiniteArticle() ), $title ) ) ),
			'url'		=> $item->url(),
			'content'	=> $item->content(),
			'author'	=> $item->author(),
			'unread'	=> $unread,
		);
	}
	
	/**
	 * Parse notification for mobile: unapproved_content
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content		$content			The content
	 * @return	array
	 */
	public static function parse_mobile_unapproved_content( Lang $language, Content $content ) : array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();
		$container = $item->containerWrapper();
		$containerId = $container ? $container->_id : "-"; // This is used to generate the tag. Use ID if we have one, otherwise just a dash

		return array(
			'title'		=> $language->addToStack( 'notification__unapproved_content_title', FALSE, array( 'htmlsprintf' => array(
				( $content instanceof Item ) ? $content->definiteArticle( $language ) : $language->get( $content::$title . '_lc' ),
			) ) ),
			'body'		=> $language->addToStack( 'notification__unapproved_content', FALSE, array( 'htmlsprintf' => array(
				$content->author()->name,
				mb_strtolower( $content->indefiniteArticle( $language ) ),
				$item->mapped('title')
			) ) ),
			'data'		=> array(
				'url'		=> (string) $content->url(),
				'author'	=> $content->author(),
				'grouped'	=> $language->addToStack( 'notification__unapproved_content_grouped', FALSE, array( 'htmlsprintf' => array(
					$content->definiteArticle( $language, TRUE ),
					$container ? 
						$language->addToStack( 'notification__container', FALSE, array( 'sprintf' => array( $container->_title ) ) ) 
						: ""
				))), // Pluralized on the client
				'groupedTitle' => $language->addToStack( 'notification__unapproved_content_title', FALSE, array( 'htmlsprintf' => array(
					$content->definiteArticle( $language, TRUE ),
				)) ), // Pluralized on the client
				'groupedUrl' => $container ? $container->url() : NULL
			),
			'tag' => md5('unapproved' . get_class( $item ) . $containerId ),
			'channelId'	=> 'moderation',
		);
	}
}