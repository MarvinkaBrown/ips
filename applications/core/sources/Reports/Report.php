<?php
/**
 * @brief		Report Index Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jul 2013
 */

namespace IPS\core\Reports;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\ReadMarkers;
use IPS\core\Reports\Comment as ReportComment;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Report Model
 */
class Report extends Item
{
	use ReadMarkers;
	
	/**
	 * @brief	Const	No type selected
	 */
	const	TYPE_MESSAGE = 0;

	/**
	 * @brief	Const	Reported content statuses
	 */
	const STATUS_NEW = 1;
	const STATUS_OPEN = 2;
	const STATUS_CLOSED = 3;
	const STATUS_REJECTED = 4;
	 
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_rc_index';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = '';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'content_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/* !\IPS\Content\Item */
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/**
	 * @brief	Application
	 */
	public static string $module = 'modcp';

	/**
	 * @brief	Allow the title to be editable via AJAX
	 */
	public bool $editableTitle	= FALSE;
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'date'			=> 'first_report_date',
		'author'		=> 'first_report_by',
		'author_count'	=> 'num_reports',
		'title'			=> 'title',
		'last_comment'	=> 'last_updated',
		'num_comments'	=> 'num_comments',
	);
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static string $formLangPrefix = 'report_';
	
	/**
	 * @brief	Comment Class
	 */
	public static ?string $commentClass = 'IPS\core\Reports\Comment';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'report';

	/**
	 * @brief	[Content\Item]	Include these items in trending content
	 */
	public static bool $includeInTrending = FALSE;

	/**
	 * Should IndexNow be skipped for this item? Can be used to prevent that Private Messages,
	 * Reports and other content which is never going to be visible to guests is triggering the requests.
	 * @var bool
	 */
	public static bool $skipIndexNow = TRUE;


	/**
	 * Get all the report types for this report index
	 *
	 * @return array
	 */
	public function getReportedTypes(): array
	{
		$reportedTypes = [];
		$reportTypes = [];
		foreach ( Types::roots() as $type )
		{
			$reportTypes[ $type->id ] = $type->_title;
		}

		foreach ( Db::i()->select( '*', 'core_rc_reports', ['rid=? and report_type > 0', $this->id] ) as $report )
		{
			if ( isset( $reportTypes[ $report['report_type'] ] ) )
			{
				$reportedTypes[ $report['report_type'] ] = $reportTypes[ $report['report_type'] ];
			}
		}

		return $reportedTypes;
	}

	/**
	 * Set the status of the report
	 *
	 * @param int $status
	 * @param int $authorNotification
	 * @param null $member
	 * @return void
	 */
	public function changeStatus( int $status, int $authorNotification=0, $member=null ): void
	{
		$member = $member ?: Member::loggedIn();
		$this->status = $status;
		$this->save();

		/* We only need to notify if there is an outcome */
		if ( $status == static::STATUS_CLOSED or static::STATUS_REJECTED )
		{
			$forNotifications = [];
			$forEmails = [];

			/* Now go through all the individual reports and see if we need to notify anyone */
			foreach ( Db::i()->select( '*', 'core_rc_reports', ['rid=?', $this->id] ) as $report )
			{
				if ( !$report['report_type'] )
				{
					/* We have not selected a reason, no nothing to worry about */
					continue;
				}

				/* Get the report type object */
				try
				{
					$reportType = Types::load( $report['report_type'] );
					$reportMember = Member::load( $report['report_by'] );
				}
				catch ( \Exception $e )
				{
					continue;
				}

				if ( $report['report_by'] )
				{
					/* We have a member */
					$forNotifications[$report['report_type']][] = [$reportMember, $reportType, $report];
				}
				else
				{
					if ( $report['guest_email'] )
					{
						/* We have an email */
						$forEmails[$report['report_type']][] = [$reportType, $report];
					}
				}
			}

			/* Members */
			foreach ( $forNotifications as $typeId => $data )
			{
				foreach ( $data as $reportData )
				{
					$reportMember = $reportData[0];
					$reportType = $reportData[1];
					$userReport = $reportData[2];

					if ( $this->getReportOutcomeBlurb( $userReport['id'] ) )
					{
						$notification = new Notification( Application::load( 'core' ), 'report_outcome', $this, [$this, $reportType, $userReport], ['reportType' => $reportType->id, 'userReport' => $userReport] );
						$notification->recipients->attach( $reportMember );
						$notification->send();
						Log::debug( "Sent notification to {$reportMember->name}", 'reports_notification' );
					}
				}
			}

			/* Guests */
			foreach ( $forEmails as $typeId => $data )
			{
				foreach ( $data as $reportData )
				{
					$reportType = $reportData[0];
					$guestEmail = $reportData[1]['guest_email'];
					$userReport = $reportData[1];

					if ( $guestEmail and $this->getReportOutcomeBlurb( $userReport['id'] ) )
					{
						Email::buildFromTemplate( 'core', 'notification_report_outcome', [$this, $reportType, $userReport], Email::TYPE_LIST )->send( $guestEmail );
					}
				}
			}

			/* Content author? */
			if ( $authorNotification )
			{
				try
				{
					$class = $this->_data['class'];
					$thing = $class::load( $this->_data['content_id'] );
					$content = ( $thing instanceof Comment ) ? $thing->truncated() : $thing->mapped( 'title' );

					if ( $thing->author() and $thing->author()->email )
					{
						$authorContent = static::getAuthorNotifications();

						if ( isset( $authorContent[ $authorNotification ] ) )
						{
							$notificationContent = $authorContent[$authorNotification]['text'];

							/* Substitutions */
							$subs = [
								'name' => $thing->author()->name,
								'content' => trim( $content ),
								'link' => (string) $thing->url(),
								'reason' => implode( ', ', $this->getReportedTypes() )
							];

							foreach( $subs as $key => $value )
							{
								$notificationContent = str_replace( '{' . $key . '}', $value, $notificationContent );
							}

							Email::buildFromTemplate( 'core', 'notification_report_content_author', [$this, $thing, $notificationContent], Email::TYPE_LIST )->send( $thing->author()->email );
						}
					}
				}
				catch ( \Exception $e ) { }
			}

		}

		/* Post a comment on the report */
		$content = $member->language()->addToStack( 'update_report_status_content', FALSE, array( 'sprintf' => array( $member->language()->addToStack( 'report_status_' . $this->status ) ) ) );
		$member->language()->parseOutputForDisplay( $content );

		$comment = ReportComment::create( $this, $content, TRUE, NULL, NULL, $member, new DateTime );
		$comment->save();

		/* And add to the moderator log */
		Session::i()->modLog( 'modlog__action_update_report_status', array( $this->url()->__toString() => FALSE ) );
	}

	/**
	 * Get the author notifications
	 *
	 * @return array
	 */
	public static function getAuthorNotifications(): array
	{
		$return = [];

		foreach( \IPS\Db::i()->select( '*', 'core_rc_author_notification_text', [], 'title ASC' ) as $row )
		{
			$return[ $row['id'] ] = $row;
		}

		return $return;
	}

	/**
	 * Get the report outcome blurb
	 *
	 * @param int $userReportId
	 * @return string
	 */
	public function getReportOutcomeBlurb( int $userReportId ): string
	{
		try
		{
			$userReport = Db::i()->select( '*', 'core_rc_reports', array( 'id=?', $userReportId ) )->first();
			$reportType = Types::load( $userReport['report_type'] );
			return $this->status === static::STATUS_REJECTED ? $reportType->getRejectedNotificationText( Member::load( $userReport['report_by'] ) ) : $reportType->getCompletedNotificationText( Member::load( $userReport['report_by'] ) );
		}
		catch( \Exception $e )
		{
			return '';
		}
	}

	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 */
	public static function incrementPostCount( Model $container = NULL ): bool
	{
		return FALSE;
	}
	
	/**
	 * Load by class and content_id
	 *
	 * @param	string	$class	Class to load
	 * @param	int		$id		ID to load
	 * @return	static
	 * @throws	OutofRangeException
	 */
	public static function loadByClassAndId( string $class, int $id ) : static
	{
		try
		{
			return static::constructFromData( Db::i()->select( '*', 'core_rc_index', array( 'class=? and content_id=?', $class, $id ) )->first() );
		}
		catch ( UnderflowException $e )
		{
			throw new OutofRangeException;
		}
	}
	
	/**
	 * Get mapped value
	 *
	 * @param string $key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( string $key ): mixed
	{
		/* Get the reported content items title */
		if ( $key === 'title' )
		{
			try
			{
				$class = $this->_data['class'];
				$thing = $class::load( $this->_data['content_id'] );
				$item = ( $thing instanceof Comment ) ? $thing->item() : $thing;

				if( isset( $item::$databaseColumnMap['content'] ) AND $item::$databaseColumnMap['content'] == $item::$databaseColumnMap['title'] )
				{
					$title = trim( mb_substr( strip_tags( $item->mapped( 'title' ) ), 0, 85 ) );
				}
				else
				{
					$title = trim( strip_tags( $item->mapped( 'title' ) ) );
				}

				return $title ?: Member::loggedIn()->language()->addToStack('report_no_title_available');
			}
			catch ( Exception $e )
			{
				return Member::loggedIn()->language()->addToStack( 'unknown' );
			}
		}

		return parent::mapped( $key );
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( string|null $action=NULL ): Url
	{
		$_key	= $action ? md5( $action ) : NULL;

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = Url::internal( "app=core&module=modcp&tab=reports&action=view&id={$this->id}", 'front', 'modcp_report' );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'action', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/* !\IPS\Helpers\Table */
		
	/**
	 * Method to add extra data to objects in this
	 * class when displaying in a table view
	 *
	 * @param	array	$rows	Array of objects of this class
	 * @return	void
	 */
	public static function tableGetRows( array $rows ) : void
	{
		$types = array();
		
		foreach ( $rows as $row )
		{
			$types[ $row->class ][ $row->content_id ] = $row;
		}

		foreach ( $types as $class => $objects )
		{
			if ( in_array( 'IPS\Content\Comment', class_parents( $class ) ) )
			{
				/* @var Comment $class */
				$itemClass = $class::$itemClass;
				$databaseTable = $class::$databaseTable;
				$itemDatabaseTable = $itemClass::$databaseTable;

				/* @var array $databaseColumnMap */
				$itemTitleField = $itemClass::$databaseColumnMap['title']; # Strange PHP issue can cause this to be lost when added to the query below.
				
				foreach( Db::i()->select(
					"{$databaseTable}.{$class::$databasePrefix}{$class::$databaseColumnId} as commentId, {$databaseTable}.{$class::$databasePrefix}{$class::$databaseColumnMap['item']} AS itemId, {$itemClass::$databaseTable}.{$itemClass::$databasePrefix}{$itemTitleField} AS title",
					$databaseTable,
					Db::i()->in( $databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId, array_keys( $objects ) )
				)->join(
					$itemDatabaseTable,
					"{$itemDatabaseTable}.{$itemClass::$databasePrefix}{$itemClass::$databaseColumnId}={$databaseTable}.{$class::$databasePrefix}{$class::$databaseColumnMap['item']}"
				)->setKeyField( 'commentId' ) as $k => $data
				)
				{
					$objects[ $k ]->_data = array_merge( $objects[ $k ]->_data, $data );
				}
			}
			elseif ( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
			{
				/* @var Item $class */
				/* @var array $databaseColumnMap */
				foreach( Db::i()->select(
					"{$class::$databasePrefix}{$class::$databaseColumnId} as itemId, {$class::$databasePrefix}{$class::$databaseColumnMap['title']} AS title",
					$class::$databaseTable,
					Db::i()->in( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId, array_keys( $objects ) )
				)->setKeyField( 'itemId' ) as $k => $data
				)
				{
					$objects[ $k ]->_data = array_merge( $objects[ $k ]->_data, $data );
				}
			}
		}
	}
	
	/**
	 * Method to get description for table view
	 *
	 * @return	string
	 */
	public function tableDescription() : string
	{
		$className = $this->class;
		try
		{
			$reportedContent = $className::load( $this->content_id );

			if( $reportedContent instanceof Comment )
			{
				$container = ( $reportedContent->item()->containerWrapper() !== NULL ) ? $reportedContent->item()->container() : NULL;
			}
			else
			{
				$container = ( $reportedContent->containerWrapper() !== NULL ) ? $reportedContent->container() : NULL;
			}
		}
		catch ( OutOfRangeException $ex )
		{
			$container = NULL;
		}

		return Theme::i()->getTemplate( 'modcp', 'core', 'front' )->reportTableDescription( $className, $this, $container );
	}

	/**
	 * Get content table states
	 *
	 * @return string
	 */
	public function tableStates(): string
	{
		$states = explode( ' ', parent::tableStates() );
		$states[] = "report_status_" . $this->status;

		return implode( ' ', $states );
	}
	
	/**
	 * Stats for table view
	 *
	 * @param bool $includeFirstCommentInCommentCount	Whether or not to include the first comment in the comment count
	 * @return	array
	 */
	public function stats( bool $includeFirstCommentInCommentCount=TRUE ): array
	{
		return array_merge( parent::stats( $includeFirstCommentInCommentCount ), array( 'num_reports' => $this->num_reports ) );
	}
	
	/**
	 * Icon for table view
	 *
	 * @return	array
	 */
	public function tableIcon() : array
	{
		return Theme::i()->getTemplate( 'modcp', 'core', 'front' )->reportToggle( $this );
	}

	/**
	 * Gets a special class for the row
	 *
	 * @return	string
	 */
	public function tableClass() : string
	{
		switch ( $this->status )
		{
			case 2:
				return 'warning';
			case 1:
				return 'new';
		}

		return '';
	}
	
	/**
	 * Do Moderator Action
	 *
	 * @param string $action	The action
	 * @param	Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param string|null $reason	Reason (for hides)
	 * @param bool $immediately	Delete immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction(string $action, Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		if ( mb_substr( $action, 0, -1 ) === 'report_status_' )
		{
			$this->changeStatus( (int) mb_substr( $action, -1 ) );
		}
		else
		{
			parent::modAction( $action, $member, $reason, $immediately );
		}
	}
	
	/**
	 * Return any custom multimod actions this content item class supports
	 *
	 * @return	array
	 */
	public function customMultimodActions(): array
	{
		return array_diff( array( 'report_status_1', 'report_status_2', 'report_status_3', 'report_status_4'), array( 'report_status_' . $this->status ) );
	}

	/**
	 * Return any available custom multimod actions this content item class supports
	 *
	 * @note	Return in format of array( array( 'action' => ..., 'icon' => ..., 'language' => ... ) )
	 * @return	array
	 */
	public static function availableCustomMultimodActions(): array
	{
		return array(
			array(
				'groupaction'	=> 'report_status',
				'icon'			=> 'flag',
				'grouplabel'	=> 'mark_as',
				'action'		=> array(
					array(
						'action'	=> 'report_status_1',
						'icon'		=> 'flag',
						'label'		=> 'report_status_1'
					),
					array(
						'action'	=> 'report_status_2',
						'icon'		=> 'exclamation-triangle',
						'label'		=> 'report_status_2'
					),
					array(
						'action'	=> 'report_status_3',
						'icon'		=> 'check-circle',
						'label'		=> 'report_status_3'
					),
					array(
						'action'	=> 'report_status_4',
						'icon'		=> 'archive',
						'label'		=> 'report_status_4'
					)
				)
			)
		);
	}
	
	/* !\IPS\core\Reports\report */
	
	/**
	 * Get reports
	 *
	 * @param string|null $filterByType	Report type to filter by, or NULL to not filter by type
	 * @return	array
	 */
	public function reports( string $filterByType=NULL ): array
	{
		$where = array( array( 'rid=?', $this->id ) );
		
		if ( $filterByType )
		{
			$where[] = array( 'report_type=?', $filterByType );
		}
		
		return iterator_to_array( Db::i()->select( '*', 'core_rc_reports', $where, 'date_reported' ) );
	}
	
	/**
	 * Rebuild
	 *
	 * @return	void
	 */
	public function rebuild() : void
	{
		$numReports = Db::i()->select( 'COUNT(*)', 'core_rc_reports', array( 'rid=?', $this->id ) )->first();
		if ( !$numReports )
		{
			$this->delete();
		}
		$this->num_reports = $numReports;
		
		$numComments = Db::i()->select( 'COUNT(*)', 'core_rc_comments', array( 'rid=?', $this->id ) )->first();
		$this->num_comments = $numComments;
		
		$this->save();
	}
	
	/**
	 * Delete Report
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();
	
		Db::i()->delete( 'core_rc_reports', array( 'rid=?', $this->id ) );
		Db::i()->delete( 'core_automatic_moderation_pending', array( 'pending_object_class=? and pending_object_id=?', $this->class, $this->content_id ) );
	}
	
	/**
	 * Lock auto moderation to prevent auto moderation from changing the status again
	 *
	 * @return void
	 */
	public function lockAutoModeration() : void
	{
		$this->auto_moderation_exempt = 1;
		$this->save();
	}
	
	/**
	 * Lock auto moderation to prevent auto moderation from changing the status again
	 *
	 * @return bool
	 */
	public function isAutoModerationLocked() : bool
	{
		return (bool) $this->auto_moderation_exempt;
	}
	
	/**
	 * Run any automatic moderation
	 *
	 * @return bool
	 */
	public function runAutomaticModeration() : bool
	{
		if ( ! Settings::i()->automoderation_enabled )
		{
			return FALSE;
		}
		
		/* If it is auto moderation locked, skip it */
		if ( $this->isAutoModerationLocked() )
		{
			return FALSE;
		}
		
		$className = $this->class;
		try
		{
			$reportedContent = $className::load( $this->content_id );
		}
		catch ( OutOfRangeException $ex )
		{
			/* No content, no moderation, no cry */
			return FALSE;
		}

		/* Is automatic moderation supported for this content type? */
		if ( !IPS::classUsesTrait( $reportedContent, 'IPS\Content\hideable' ) )
		{
			return FALSE;
		}

		/* Fetch a count of report flags so far */
		$typeCounts = $this->getReportTypeCounts();
		$ruleToUse  = NULL;
		
		/* Loop over all group promotion rules and get the last one that matches us */
		foreach(Rules::roots() as $rule )
		{
			if( $rule->enabled and $rule->matches( $reportedContent->author(), $typeCounts ) )
			{
				$ruleToUse = $rule->id;
			}
		}

		/* If there's no rule, return now */
		if( $ruleToUse === NULL )
		{
			/* It is possible a few reports have been removed so the threshold is no longer met, delete any pending rows if this is the case */
			Db::i()->delete( 'core_automatic_moderation_pending', array( 'pending_object_class=? and pending_object_id=?', $className, $this->content_id ) );
		}
		else
		{
			/* Log the bad boy for actioning later. A small delay allows users to retract their warning */
			Db::i()->replace( 'core_automatic_moderation_pending', array(
				'pending_object_class' => $className,
				'pending_object_id'    => $this->content_id,
				'pending_report_id'	   => $this->id,
				'pending_added'		   => time(),
				'pending_rule_id'	   => $ruleToUse
			) );
		}

		return true;
	}
	
	/**
	 * Fetch the report type counts
	 *
	 * @param	boolean	$totalOnly		Return either an int of the total counts, or an array with the breakdown
	 * @return int|array		( 1 => 10, 2 => 3 )
	 */
	public function getReportTypeCounts( bool $totalOnly=false ) : array|int
	{
		$typeCounts = array();
		$total = 0;
		$seen = array();
		foreach( Db::i()->select( '*', 'core_rc_reports', array( 'rid=? and report_type > 0', $this->id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER ) as $row )
		{
			if ( isset( $seen[ $row['report_by'] ] ) )
			{
				continue;
			}
			
			$seen[ $row['report_by'] ] = true;
			
			$typeCounts[ $row['report_type'] ][ $row['report_by'] ] = true;
		}
		
		$return = array();
		foreach(array_keys( Types::roots() ) as $type )
		{
			if ( isset( $typeCounts[ $type ] ) )
			{
				$return[ $type ] = count( $typeCounts[ $type ] );
				$total += count( $typeCounts[ $type ] );
			}
			else
			{
				$return[ $type ] = 0;
			}
		}
		
		return $totalOnly ? $total : $return;
	}
	
	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public static function getTableFilters(): array
	{
		return array(
			'read', 'unread', 'report_status_1', 'report_status_2', 'report_status_3'
		);
	}

	/**
	 * Build the where clause for finding reports
	 *
	 * @param Member|null $member	Member to base permissions on
	 * @return array
	 */
	public static function where( Member $member=NULL ): array
	{
		$member = $member ?: Member::loggedIn();
		$classWhere = [];
		$extensionWhere = [];
		$viewPermIds = [];
		$perms = $member->modPermissions();

		foreach( Db::i()->select( 'app, perm_id', 'core_permission_index', Db::i()->findInSet( 'perm_view', $member->permissionArray() ) . " OR perm_view='*'" ) as $perm )
		{
			$viewPermIds[ $perm['app'] ][] = $perm['perm_id'];
		}

		foreach ( array_merge( ['IPS\core\Messenger\Conversation', 'IPS\core\Messenger\Message'], array_values( Content::routedClasses( FALSE, TRUE ) ) ) as $class )
		{
			/* Got nodes? */
			$item = NULL;
			if ( is_subclass_of( $class, "IPS\Content\Item" ) )
			{
				$item = $class;
			}
			else if ( is_subclass_of( $class, "IPS\Content\Comment" ) or is_subclass_of( $class, "IPS\Content\Review" ) )
			{
				if ( isset( $class::$itemClass ) )
				{
					$item = $class::$itemClass;
				}
			}

			if ( ! $item )
			{
				continue;
			}

			$extensionWhereClause = '';
			if ( isset( $item::$containerNodeClass ) )
			{
				$container = $item::$containerNodeClass;
				if ( isset( $container::$modPerm ) )
				{
					if ( isset( $perms[$container::$modPerm] ) and $perms[$container::$modPerm] != '*' and $perms[$container::$modPerm] != '-1')
					{
						$extensionWhereClause = ' AND ' . Db::i()->in( 'node_id', is_array( $perms[$container::$modPerm] ) ? $perms[$container::$modPerm] : [ $perms[$container::$modPerm] ] );
					}
				}
			}

			$extensionWhereClause = '';

			/* Skip this if we have global permissions */
			if( is_array( $perms ) and !$perms['can_view_reports'] )
			{
				/* Skip anything where we have reports disabled */
				$permissionKey = 'can_view_reports_' . $item::$title;
				if( !isset( $perms[$permissionKey] ) or !$perms[$permissionKey] )
				{
					continue;
				}

				/* Container-level permissions */
				if ( isset( $item::$containerNodeClass ) )
				{
					$container = $item::$containerNodeClass;
					if ( isset( $container::$modPerm ) and isset( $perms[$container::$modPerm] ) and $perms[$container::$modPerm] != '*' and $perms[$container::$modPerm] != '-1' and isset( $perms[$permissionKey] ) and $perms[$permissionKey] )
					{
						$extensionWhereClause = ' AND ' . Db::i()->in( 'node_id', is_array( $perms[$container::$modPerm] ) ? $perms[$container::$modPerm] : [ $perms[$container::$modPerm] ] );
					}
				}
			}

			$workingClass = $class;
			if ( isset( $workingClass::$itemClass ) )
			{
				$workingClass = $workingClass::$itemClass;
			}
			if ( isset( $workingClass::$containerNodeClass ) )
			{
				$workingClass = $workingClass::$containerNodeClass;
			}
			if ( isset( $workingClass::$permissionMap ) and isset( $workingClass::$permissionMap['read'] ) and $workingClass::$permissionMap['read'] !== 'view' )
			{
				if ( !isset( $permIds[ $class::$application ][ $workingClass::$permissionMap['read'] ] ) )
				{
					$permIds[ $class::$application ][ $workingClass::$permissionMap['read'] ] = iterator_to_array( Db::i()->select( 'perm_id', 'core_permission_index', "(" . Db::i()->findInSet( 'perm_' . $workingClass::$permissionMap['read'], Member::loggedIn()->permissionArray() ) . " OR perm_" . $workingClass::$permissionMap['read'] . "='*' ) AND app='{$class::$application}'" ) );
				}

				if( isset( $viewPermIds[ $class::$application ] ) AND !empty( $permIds[ $class::$application ][ $workingClass::$permissionMap['read'] ] ) )
				{
					$classWhere[] = "( ( class='" . str_replace( '\\', '\\\\', $class ) . "' AND ( perm_id IN(" . implode( ',', array_intersect( $viewPermIds[ $class::$application ], $permIds[ $class::$application ][ $workingClass::$permissionMap['read'] ] ) ) . ") ) OR perm_id IS NULL )" . $extensionWhereClause . ")";
				}
			}
			else
			{
				if ( isset( $class::$application ) and isset( $viewPermIds[ $class::$application ] ) )
				{
					$classWhere[] = "( ( class='" . str_replace( '\\', '\\\\', $class ) . "' AND ( perm_id IN(" . implode( ',', $viewPermIds[ $class::$application ] ) . ") ) OR perm_id IS NULL )" . $extensionWhereClause . ")";
				}
				else
				{
					$classWhere[] = "( class='" . str_replace( '\\', '\\\\', $class ) . "' )";
				}
			}
		}

		return [ '(' . implode( ' OR ', array_values( $classWhere ) ) . ')' ];
	}

	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( ?Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		$return = parent::canView( $member );

		if ( $return AND $member->modPermission('can_view_reports') )
		{
			return TRUE;
		}

		$class = $this->_data['class'];
		try
		{
			$thing = $class::load( $this->_data['content_id'] );
			if( $thing instanceof Comment )
			{
				$itemClass = $thing::$itemClass;
				return $itemClass::modPermission( 'view_reports', $member, $thing->item()->containerWrapper() );
			}

			return $class::modPermission( 'view_reports', $member, $thing->containerWrapper() );
		}
		catch( OutOfRangeException $e ){}

		return FALSE;
	}
}
