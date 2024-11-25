<?php
/**
 * @brief		AJAX actions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		04 Apr 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Application;
use IPS\Application\Module;
use IPS\cms\Pages\Page;
use IPS\Content\Item;
use IPS\core\AdminNotification;
use IPS\core\DataLayer;
use IPS\core\Messenger\Conversation;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Output\Plugin\Filesize;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Parser;
use IPS\Theme;
use IPS\Widget;
use IPS\Content\Controller as ContentController;
use OutOfRangeException;
use PasswordStrength;
use UnderflowException;
use UnexpectedValueException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_subclass_of;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * AJAX actions
 */
class ajax extends Controller
{
	/**
	 * Find Member
	 *
	 * @return	void
	 */
	public function subscribeToSolved() : void
	{
		Session::i()->csrfCheck();
			
		Member::loggedIn()->members_bitoptions['no_solved_reenage'] = 0;
		Member::loggedIn()->save();
		
		Output::i()->json( [ 'message' => Member::loggedIn()->language()->addToStack( 'mark_solved_reengage_back_on' ) ] );
	}

    /**
     * Refreshes the real time token
     *
     * @return void
     */
    public function refreshRealTimeToken(): void
    {
        Bridge::i()->refreshRealTimeToken();
    }

	public function trackPostShareIntent(): void
	{
		Session::i()->csrfCheck();
		if ( !intval( Request::i()->commentId ) )
		{
			Output::i()->json([ 'error' => 'No valid comment id passed in the url' ], 400 );
		}
		Bridge::i()->trackPostRankingEvent( (int) Request::i()->commentId, 'share' );
		Output::i()->json([ 'message' => 'ok' ]);
	}

	/**
	 * Find Member
	 *
	 * @return	void
	 */
	public function findMember() : void
	{
		$results = array();
		
		$input = str_replace( array( '%', '_' ), array( '\%', '\_' ), mb_strtolower( Request::i()->input ) );
		
		$where = array( "name LIKE CONCAT(?, '%')" );
		$binds = array( $input );
		if ( Dispatcher::i()->controllerLocation === 'admin' OR ( Member::loggedIn()->modPermission('can_see_emails') AND Request::i()->type == 'mod' ) )
		{
			$where[] = "email LIKE CONCAT(?, '%')";
			$binds[] = $input;
		}

		if ( Dispatcher::i()->controllerLocation === 'admin' )
		{
			if ( is_numeric( Request::i()->input ) )
			{
				$where[] = "member_id=?";
				$binds[] = intval( Request::i()->input );
			}
		}
			
		/* Build the array item for this member after constructing a record */
		/* The value should be just the name so that it's inserted into the input properly, but for display, we wrap it in the group *fix */
		foreach ( Db::i()->select( '*', 'core_members', array_merge( array( implode( ' OR ', $where ) ), $binds ), 'LENGTH(name) ASC', array( 0, 20 ) ) as $row )
		{
			$member = Member::constructFromData( $row );

			$extra = Dispatcher::i()->controllerLocation == 'admin' ? htmlspecialchars( $member->email, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) : $member->groupName;

			if ( Member::loggedIn()->modPermission('can_see_emails') AND Request::i()->type == 'mod' )
			{
				$extra = htmlspecialchars( $member->email, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) . '<br>' . $member->groupName;
			}

			$results[] = array(
				'id'	=> 	$member->member_id,
				'value' => 	$member->name,
				'name'	=> 	Dispatcher::i()->controllerLocation == 'admin' ? $member->group['prefix'] . htmlspecialchars( $member->name, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) . $member->group['suffix'] : htmlspecialchars( $member->name, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ),
				'extra'	=> 	$extra,
				'photo'	=> 	(string) $member->photo,
			);
		}
				
		Output::i()->json( $results );
	}

	/**
	 * Returns size and download count of an array of attachments
	 *
	 * @return	void
	 */
	public function attachmentInfo() : void
	{
		$toReturn = array();
		$member = Member::loggedIn();
		$loadedExtensions = array();

		/* As you can insert "other media" from other apps (such as Downloads), we need to route the attachIDs appropriately. */
		$attachmentIds = array();

		foreach( array_keys( Request::i()->attachIDs ) as $attachId )
		{
			if( (int) $attachId == $attachId AND mb_strlen( (int) $attachId ) == mb_strlen( $attachId ) )
			{
				$attachmentIds[] = $attachId;
			}
			else
			{
				try
				{
					$url = Url::createFromString( $attachId );

					/* Get the "real" query string (whatever the query string is, plus what we can get from decoding the FURL) */
					$qs = array_merge( $url->queryString, $url->hiddenQueryString );

					/* We need an app, and it needs to not be an RSS link */
					if ( !isset( $qs['app'] ) )
					{
						throw new UnexpectedValueException;
					}

					/* Load the application */
					$application = Application::load( $qs['app'] );
					
					/* Loop through our content classes and see if we can find one that matches */
					foreach ( $application->extensions( 'core', 'ContentRouter' ) as $key => $extension )
					{
						$classes = $extension->classes;
		
						/* So for each of those... */
						foreach ( $classes as $class )
						{
							/* Try to load it */
							try
							{
								$item = $class::loadFromURL( $url );

								if( !$item->canView() )
								{
									throw new OutOfRangeException;
								}

								/* If we're still here, we should be good. Any exceptions will have been caught by our general try/catch. */
								$toReturn[ $attachId ] = $item->getAttachmentInfo();
								break;
							}
							catch( OutOfRangeException $e ){}
						}
					}
				}
				catch( Exception $e ){}
			}
		}

		/* Get attachments */
		if( count( $attachmentIds ) )
		{
			$attachments = Db::i()->select( '*', 'core_attachments', array( Db::i()->in( 'attach_id', $attachmentIds ) ) );

			foreach( $attachments as $attachment )
			{
				$permission = FALSE;			

				if( $member->member_id )
				{
					if ( $member->member_id == $attachment['attach_member_id'] )
					{
						$permission	= TRUE;
					}
				}

				if( $permission !== TRUE )
				{
					foreach ( Db::i()->select( '*', 'core_attachments_map', array( 'attachment_id=?', $attachment['attach_id'] ) ) as $map )
					{
						if ( !isset( $loadedExtensions[ $map['location_key'] ] ) )
						{
							$exploded = explode( '_', $map['location_key'] );
							try
							{
								$extensions = Application::load( $exploded[0] )->extensions( 'core', 'EditorLocations' );
								if ( isset( $extensions[ $exploded[1] ] ) )
								{
									$loadedExtensions[ $map['location_key'] ] = $extensions[ $exploded[1] ];
								}
							}
							catch ( OutOfRangeException $e ) { }
						}

						if ( isset( $loadedExtensions[ $map['location_key'] ] ) )
						{
							try
							{
								if ( $loadedExtensions[ $map['location_key'] ]->attachmentPermissionCheck( $member, $map['id1'], $map['id2'], $map['id3'], $attachment ) )
								{
									$permission = TRUE;
									break;
								}
							}
							catch ( OutOfRangeException $e ) { }
						}
					}
				}

				/* Permission check */
				if ( $permission )
				{
					if( $attachment['attach_is_image'] )
					{
						$toReturn[ $attachment['attach_id'] ] = array(
							'rotate' => $attachment['attach_img_rotate'] <> 0 ? (int)$attachment['attach_img_rotate'] : null
						);
					}
					else
					{
						$toReturn[ $attachment['attach_id'] ] = array(
							'size' => Filesize::humanReadableFilesize( $attachment['attach_filesize'], FALSE, TRUE ),
							'downloads' => Member::loggedIn()->language()->formatNumber( $attachment['attach_hits'] )
						);
					}
				}
			}
		}

		Output::i()->json( $toReturn );
	}
	
	/**
	 * Returns boolean in json indicating whether the supplied username already exists
	 *
	 * @return	void
	 */
	public function usernameExists() : void
	{
		$result = array( 'result' => 'ok' );
		
		/* The value comes urlencoded so we need to decode so length is correct (and not using a percent-encoded value) */
		$name = urldecode( Request::i()->input );
		
		/* Check is valid */
		if ( !$name )
		{
			$result = array( 'result' => 'fail', 'message' => Member::loggedIn()->language()->addToStack('form_required') );
		}
		elseif ( mb_strlen( $name ) < Settings::i()->min_user_name_length )
		{
			$result = array( 'result' => 'fail', 'message' => Member::loggedIn()->language()->addToStack( 'form_minlength', FALSE, array( 'pluralize' => array( Settings::i()->min_user_name_length ) ) ) );
		}
		elseif ( mb_strlen( $name ) > Settings::i()->max_user_name_length )
		{
			$result = array( 'result' => 'fail', 'message' => Member::loggedIn()->language()->addToStack( 'form_maxlength', FALSE, array( 'pluralize' => array( Settings::i()->max_user_name_length ) ) ) );
		}
		elseif ( !Login::usernameIsAllowed( $name ) )
		{
			$result = array( 'result' => 'fail', 'message' => Member::loggedIn()->language()->addToStack('form_bad_value') );
		}

		/* Check if it exists */
		else if ( $error = Login::usernameIsInUse( $name ) )
		{
			if ( Member::loggedIn()->isAdmin() )
			{
				$result = array( 'result' => 'fail', 'message' => $error );
			}
			else
			{
				$result = array( 'result' => 'fail', 'message' => Member::loggedIn()->language()->addToStack('member_name_exists') );
			}
		}
		
		/* Check it's not banned */
		if ( $result == array( 'result' => 'ok' ) )
		{
			foreach( Db::i()->select( 'ban_content', 'core_banfilters', array("ban_type=?", 'name') ) as $bannedName )
			{
				if( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedName, '/' ) ) . '$/i', $name ) )
				{
					$result = array( 'result' => 'fail', 'message' => Member::loggedIn()->language()->addToStack('form_name_banned') );
					break;
				}
			}
		}

		Output::i()->json( $result );
	}

	/**
	 * Get state/region list for country
	 *
	 * @return	void
	 */
	public function states() : void
	{
		$states = array();
		if ( array_key_exists( Request::i()->country, GeoLocation::$states ) )
		{
			$states = GeoLocation::$states[ Request::i()->country ];
		}
		
		Output::i()->json( $states );
	}
	
	/**
	 * Top Contributors
	 *
	 * @return	void
	 */
	public function topContributors() : void
	{
		/* How many? */
		$limit = intval( ( isset( Request::i()->limit ) and Request::i()->limit <= 25 and Request::i()->limit > 0 ) ? Request::i()->limit : 5 );
		
		/* What timeframe? */
		$where = array( array( 'member_received > 0' ) );
		$timeframe = 'all';
		if ( isset( Request::i()->time ) and Request::i()->time != 'all' )
		{
			switch ( Request::i()->time )
			{
				case 'week':
					$where[] = array( 'rep_date>' . DateTime::create()->sub( new DateInterval( 'P1W' ) )->getTimestamp() );
					$timeframe = 'week';
					break;
				case 'month':
					$where[] = array( 'rep_date>' . DateTime::create()->sub( new DateInterval( 'P1M' ) )->getTimestamp() );
					$timeframe = 'month';
					break;
				case 'year':
					$where[] = array( 'rep_date>' . DateTime::create()->sub( new DateInterval( 'P1Y' ) )->getTimestamp() );
					$timeframe = 'year';
					break;
			}

			$innerQuery = Db::i()->select( 'core_reputation_index.member_received as themember, SUM(rep_rating) as rep', 'core_reputation_index', $where, NULL, NULL, 'themember' );
            $topContributors = iterator_to_array( Db::i()->select( 'themember, rep', array( $innerQuery, 'in' ), NULL, 'rep DESC', $limit )->setKeyField('themember')->setValueField('rep') );
        }
        else
        {
            $topContributors = iterator_to_array( Db::i()->select( 'member_id as themember, pp_reputation_points as rep', 'core_members', array( 'pp_reputation_points > 0' ), 'rep DESC', $limit )->setKeyField('themember')->setValueField('rep') );
        }

		/* Load their data */	
		foreach ( Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', array_keys( $topContributors ) ) ) as $member )
		{
			Member::constructFromData( $member );
		}
		
		/* Render */
		$output = Theme::i()->getTemplate( 'widgets' )->topContributorRows( $topContributors, $timeframe, Request::i()->orientation );
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->metaTags['robots'] = 'noindex';
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'block_topContributors' );
			Output::i()->output = $output;
		}
	}
	
	/**
	 * Most Solved
	 *
	 * @return	void
	 */
	public function mostSolved() : void
	{
		/* How many? */
		$limit = intval( ( isset( Request::i()->limit ) and Request::i()->limit <= 25 ) ? Request::i()->limit : 5 );

		if( $limit < 0 )
		{
			$limit = 5;
		}
		
		/* What timeframe? */
		$where = array( 'member_id>0 and hidden=0' );
		$where[] = [ 'type=?','solved' ];
		$timeframe = 'all';
		if ( isset( Request::i()->time ) and Request::i()->time != 'all' )
		{
			switch ( Request::i()->time )
			{
				case 'week':
					$where[] = array( 'solved_date>' . DateTime::create()->sub( new DateInterval( 'P1W' ) )->getTimestamp() );
					$timeframe = 'week';
					break;
				case 'month':
					$where[] = array( 'solved_date>' . DateTime::create()->sub( new DateInterval( 'P1M' ) )->getTimestamp() );
					$timeframe = 'month';
					break;
				case 'year':
					$where[] = array( 'solved_date>' . DateTime::create()->sub( new DateInterval( 'P1Y' ) )->getTimestamp() );
					$timeframe = 'year';
					break;
			}
		
			$innerQuery = Db::i()->select( 'core_solved_index.member_id as themember, COUNT(*) as count', 'core_solved_index', $where, NULL, NULL, 'themember' );

            $topSolved = iterator_to_array( Db::i()->select( 'themember, count', array( $innerQuery, 'in' ), NULL, 'count DESC', $limit )->setKeyField('themember')->setValueField('count') );
        }
        else
        {
            $topSolved = iterator_to_array( Db::i()->select( 'MAX(member_id) as member_id, COUNT(*) as count', 'core_solved_index', array( 'type=?', 'solved'), 'count DESC', $limit, 'member_id' )->setKeyField('member_id')->setValueField('count') );
        }

		/* Load their data */
		foreach ( Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', array_keys( $topSolved ) ) ) as $member )
		{
			Member::constructFromData( $member );
		}
		
		/* Render */
		$output = Theme::i()->getTemplate( 'widgets' )->mostSolvedRows( $topSolved, $timeframe, Request::i()->orientation );
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->metaTags['robots'] = 'noindex';
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'block_mostSolved' );
			Output::i()->output = $output;
		}
	}
	
	/**
	 * Menu Preview
	 *
	 * @return	void
	 */
	public function menuPreview() : void
	{
		if ( isset( Request::i()->theme ) )
		{
			Theme::switchTheme( Request::i()->theme, FALSE );
		}
		
		$preview = Theme::i()->getTemplate( 'global', 'core', 'front' )->navBar( TRUE );
		Output::i()->metaTags['robots'] = 'noindex';
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/menumanager.css', 'core', 'admin' ) );
		Output::i()->sendOutput( Theme::i()->getTemplate( 'applications', 'core', 'admin' )->menuPreviewWrapper( $preview ) );
	}
	
	/**
	 * Instant Notifications
	 *
	 * @return	void
	 */
	public function instantNotifications() : void
	{
		/* If auto-polling isn't enabled, kill the polling now */
		if ( !Settings::i()->auto_polling_enabled )
		{
			Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
		}

		/* Get the initial counts */
		$return = array( 'notifications' => array( 'count' => Member::loggedIn()->notification_cnt, 'data' => array() ), 'messages' => array( 'count' => Member::loggedIn()->msg_count_new, 'data' => array() ) );
		
		/* If there's new notifications, get the actual data */
		if ( Request::i()->notifications < $return['notifications']['count'] )
		{
			$notificationsDifference = $return['notifications']['count'] - (int) Request::i()->notifications;

			/* Cap at 200 to prevent DOSing the server when there are like 1000+ notifications to send */
			if( $notificationsDifference > 200 )
			{
				$notificationsDifference = 200;
			}

			foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_notifications', array( '`member`=? AND ( read_time IS NULL OR read_time<? )', Member::loggedIn()->member_id, time() ), 'updated_time DESC', $notificationsDifference ), 'IPS\Notification\Inline' ) as $notification )
			{
				/* It is possible that the content has been removed after the iterator has started but before we fetch the data */
				try
				{
					$data = $notification->getData();
				}
				catch( OutOfRangeException $e )
				{
					continue;
				}
								
				$return['notifications']['data'][] = array(
					'id'			=> $notification->id,
					'title'			=> htmlspecialchars( $data['title'], ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ),
					'url'			=> (string) $data['url'],
					'content'		=> isset( $data['content'] ) ? htmlspecialchars( $data['content'], ENT_DISALLOWED, 'UTF-8', FALSE ) : NULL,
					'date'			=> $notification->updated_time->getTimestamp(),
					'author_photo'	=> $data['author'] ? $data['author']->photo : NULL
				);
			}
		}
		
		/* If there's new messages, get the actual data */
		if ( !Member::loggedIn()->members_disable_pm and Member::loggedIn()->canAccessModule( Module::get( 'core', 'messaging' ) ) )
		{
			if ( Request::i()->messages < $return['messages']['count'] )
			{
				$messagesDifference = $return['messages']['count'] - (int) Request::i()->messages;

				foreach ( Db::i()->select( 'map_topic_id', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0', Member::loggedIn()->member_id ), 'map_last_topic_reply DESC', $messagesDifference ) as $conversationId )
				{
					$conversation	= Conversation::load( $conversationId );
					$message		= $conversation->comments( 1, 0, 'date', 'desc' );

					if( $message )
					{
						$return['messages']['data'][] = array(
							'id'			=> $conversation->id,
							'title'			=> htmlspecialchars( $conversation->title, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ),
							'url'			=> (string) $conversation->url()->setQueryString( 'latest', 1 ),
							'message'		=> $message->truncated(),
							'date'			=> $message->mapped('date'),
							'author_photo'	=> (string) $message->author()->photo
						);
					}
					else
					{
						Log::log( "Private conversation {$conversation->id} titled {$conversation->title} has no messages", 'orphaned_data' );
					}
				}
			}
		}
		
		/* And return */
		Output::i()->json( $return );
	}

	/**
	 * Returns score in json indicating the strength of a password
	 *
	 * @return	void
	 */
	public function passwordStrength() : void
	{
		/* The value comes urlencoded so we need to decode so length is correct (and not using a percent-encoded value) */
		$password = urldecode( Request::i()->input );

		require_once ROOT_PATH . "/system/3rd_party/phpass/phpass.php";

		$phpass = new PasswordStrength();

		$score		= NULL;
		$granular	= NULL;

		if( isset( Request::i()->checkAgainstRequest ) AND is_array( Request::i()->checkAgainstRequest ) )
		{
			foreach( Request::i()->checkAgainstRequest as $notIdenticalValue )
			{
				if( $notIdenticalValue AND $password == urldecode( $notIdenticalValue ) )
				{
					$score		= $phpass::STRENGTH_VERY_WEAK;
					$granular	= 1;
				}
			}
		}

		$response = array( 'result' => 'ok', 'score' => $score ?? $phpass->classify( $password ), 'granular' => $granular ?? $phpass->calculate( $password ) );

		Output::i()->json( $response );
	}
	
	/**
	 * Show information about chart timezones
	 *
	 * @return	void
	 */
	public function chartTimezones() : void
	{
		$mysqlTimezone = Db::i()->query( "SELECT TIMEDIFF( NOW(), CONVERT_TZ( NOW(), @@session.time_zone, '+00:00' ) );" )->fetch_row()[0];
		if ( preg_match( '/^(-?)(\d{2}):00:00/', $mysqlTimezone, $matches ) )
		{
			$mysqlTimezone = "GMT" . ( ( $matches[2] == 0 ) ? '' : ( ( $matches[1] ?: '+' ) . intval( $matches[2] ) ) );
		}

		Output::i()->metaTags['robots'] = 'noindex';
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->chartTimezoneInfo( $mysqlTimezone );
	}
	
	/**
	 * Dismiss ACP Notification
	 *
	 * @return	void
	 */
	public function dismissAcpNotification() : void
	{
		Session::i()->csrfCheck();
		
		if ( Member::loggedIn()->isAdmin() )
		{
			AdminNotification::dismissNotification( Request::i()->id );
		}
		
		if( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'status' => 'OK' ) );
		}
		else
		{
			$ref = Request::i()->referrer();

			Output::i()->redirect( $ref ?? Url::internal( '' ) );
		}
	}

	/**
	 * Find suggested tags
	 *
	 * @return	void
	 */
	public function findTags() : void
	{
		$results = array();
		
		$input = mb_strtolower( Request::i()->input );

		/* First, get the admin-defined tags */
		$definedTags = array();

		if( isset( Request::i()->class ) )
		{
			$class = Request::i()->class;
			$containerClass = $class::$containerNodeClass;

			try
			{
				$container = $containerClass::load( (int) Request::i()->container );
			}
			catch( OutOfRangeException $e )
			{
				$container = NULL;
			}

			if( $definedTags = $class::definedTags() )
			{
				foreach( $definedTags as $tag )
				{
					/* Only include tags that match the input term */
					if( mb_stripos( $tag, $input ) !== FALSE )
					{
						$results[] = array(
							'value' 		=> $tag,
							'html'			=> $tag,
							'recommended'	=> true
						);
					}
				}
			}
		}
		
		/* Then look for used tags */
		$where = array(
			array( "tag_text LIKE CONCAT(?, '%')", $input ),
			array( '(tag_perm_visible=? OR tag_perm_aai_lookup IS NULL)', 1 ),
			array( '(' . Db::i()->findInSet( 'tag_perm_text', Member::loggedIn()->groups ) . ' OR ' . 'tag_perm_text=? OR tag_perm_text IS NULL)', '*' ),
		);
	
		foreach ( Db::i()->select( 'tag_text', 'core_tags', $where, 'LENGTH(tag_text) ASC', array( 0, 20 ), 'tag_text' )->join( 'core_tags_perms', array( 'tag_perm_aai_lookup=tag_aai_lookup' ) ) as $tag )
		{
			if( !in_array( $tag, $definedTags ) )
			{
				$results[] = array(
					'value'			=> $tag,
					'html'			=> $tag,
					'recommended'	=> false
				);
			}
		}
				
		Output::i()->json( $results );
	}

	/**
	 * Return current CSRF token
	 *
	 * @return void
	 */
	public function getCsrfKey() : void
	{
		/* Don't cache the CSRF key */
		Output::setCacheTime( false );

		if ( isset( Request::i()->path ) )
		{
			$baseUrlData = parse_url( Url::baseUrl() );

			/* If the baseURL was site.com/forums/, JS returns pathname which is /forums/foo
			   so we need to check for a path in the baseURL and make sure its removed from the
			   incoming path. We want to look for 'admin', so 'forums/admin' would confuse it */
			$pathToUse = '';
			if ( isset( $baseUrlData['path'] ) and $baseUrlData['path'] )
			{
				$pathToUse .= trim( $baseUrlData['path'], '/' );
			}

			$path = trim( Request::i()->path, '/' );

			if ( $pathToUse )
			{
				$path = trim( preg_replace( '#^' . $pathToUse . '#', '', $path ), '/' );
			}

			$bits = explode( '/', $path );

			/* This is an ACP URL, so we need the admin session */
			if ( $bits[0] == 'admin' )
			{
				/* Ask ajax to follow the redirect for the Admin session CSRF */
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login&do=getCsrfKey', 'admin' ) );
			}
		}

		Output::i()->json( [ 'key' => Session::i()->csrfKey ] );
	}

	/**
	 * Get any events in the session cache and clear the cache
	 *
	 * @return void
	 * @throws Exception
	 */
	public function getDataLayerEvents() : void
	{
		$payload = '{}';
		if ( Settings::i()->core_datalayer_enabled AND Member::loggedIn()->member_id )
		{
			$payload = DataLayer::i()->jsonEvents;
			DataLayer::i()->clearCache();
		}

		/* Using this instead of \IPS\Output::i()->json because it's already JSON encoded */
		Output::i()->sendOutput( Member::loggedIn()->language()->stripVLETags( $payload ), 200, 'application/json', Output::i()->httpHeaders );
	}

	/**
	 * Get the emoji index
	 *
	 * @return void
	 */
	public function getEmojiIndex() : void
	{
		try
		{
			Output::i()->json( Parser::getEmojiIndex() );
		}
		catch ( Exception )
		{
			Output::i()->json( ['error' => 'emojis_not_loaded'], 500 );
		}
	}

	/**
	 * Update the table of contents for a widget
	 *
	 * @return void
	 */
	public function updateTableOfContents() : void
	{
		/* Do they have permission? */
		if ( !Request::i()->isAjax() || mb_strtolower(Request::i()->requestMethod()) !== 'post' )
		{
			Output::i()->redirect( Url::internal( '' ) );
		}

		if ( !is_string( Request::i()->blockID ) or empty( Request::i()->blockID ) )
		{
			Output::i()->json( [ "No block id present in the request" ], 400 );
		}

		/* In this case, let's see if the block exists at all */
		foreach ( ['pageApp', 'pageModule', 'pageController', 'pageID', 'pageArea'] as $pageParam )
		{
			$$pageParam = Request::i()->$pageParam;
		}

		try
		{
			// If they're not a member, they definitely don't have permission
			if ( !Member::loggedIn()->member_id )
			{
				throw new OutOfRangeException;
			}

			if ( !$pageApp or !( $app = Application::load( $pageApp ) ) )
			{
				throw new OutOfRangeException();
			}

			$canEdit = false;
			$itemId = $pageID ?: Request::i()->pageElse;
			if ( $pageApp and $pageModule and $pageController and is_numeric( $itemId ) )
			{
				$controllerClass = "\\IPS\\{$pageApp}\\modules\\front\\{$pageModule}\\{$pageController}";
				if ( class_exists( $controllerClass ) && is_subclass_of( $controllerClass, ContentController::class ) )
				{
					$item = $controllerClass::loadItem( (int) $itemId );
					if ( $item instanceof Item and $item->author()?->member_id === Member::loggedIn()->member_id ) /* todo we may want to add a mod permission as well; for now we just do an author check */
					{
						$canEdit = true;
					}
				}
			}

			if ( !$canEdit && $app->canManageWidgets() )
			{
				$canEdit = true;
			}

			if ( !$canEdit )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			Output::i()->json( [ 'message' => 'no permission' ], 403 );
		}

		/* Does the block exist? */
		try
		{
			Db::i()->select( 'data', 'core_widgets_config', ['data IS NOT NULL and id=?', Request::i()->blockID] )->first();
		}
		catch ( UnderflowException )
		{
			// We want to get the default configuration if the widget exists inside the page area
			$areas = empty( $pageID ) ? Widget\Area::getAreasFromDatabase( $pageApp, $pageModule, $pageController, $pageArea ) : Page::load( $pageID )->getAreasFromDatabase();
			foreach ( $areas as $area )
			{
				foreach ( $area->getAllWidgets() as $widget )
				{
					if ( @$widget['unique'] === Request::i()->blockID )
					{
						$configuration = Widget::getConfiguration( Request::i()->blockID );
						break;
					}
				}
			}

			if ( !isset( $configuration ) )
			{
				throw new UnderflowException;
			}
		}
		catch ( Exception )
		{
			Output::i()->json( [ "message" => 'Block ' . ( is_string( Request::i()->blockID ) ? Request::i()->blockID : '"nil"' ) . ' not found' ], 404 );
			return;
		}

		/* Update it */
		try
		{
			$items = json_decode( Request::i()->items, true ) ?: [];
			if ( is_array( $items ) )
			{
				Db::i()->replace( 'core_table_of_contents', [
					'contents' => json_encode( $items ),
					'app' => $pageApp,
					'controller' => $pageController,
					'module' => $pageModule,
					'key' => strval( $pageID ?: Request::i()->pageElse ) ?: null
				]);

				Widget::deleteCaches( Request::i()->blockID );
			}
			else
			{
				Output::i()->json( ['message' => 'Error: the items is not a JSON encoded array'], 400 );
			}

			Output::i()->json( ['message' => 'OK' ], 201 );
		}
		catch ( Exception $e )
		{

			Output::i()->json( [ 'message' => 'Error', "error" => $e ], 500 );

		}

	}


	/**
	 * Endpoint to get the computed html contents for relative timestamps
	 * @return void
	 */
	public function getFormattedTimes() : void
	{
		$output = [];
		if ( !Request::i()->isAjax() )
		{
			Output::i()->json( [ "message" => "Not requested via ajax" ], 401 );
		}

		if ( empty( Request::i()->timestamps ) OR !is_string( Request::i()->timestamps ) OR !preg_match( "/^[\d,]+$/", Request::i()->timestamps ) )
		{
			Output::i()->json( [ "message" => "No timestamps request parameter, or the parameter is not a comma separated list of numbers" ], 400 );
		}

		$timestamps = array_map( "intval", explode( ",", Request::i()->timestamps ) );
		if ( count( $timestamps ) )
		{
			$lang = Lang::defaultLanguage() ? Lang::load( Lang::defaultLanguage() ) : Member::loggedIn()->language();
			foreach ( $timestamps as $timestamp )
			{
				// we cache based on how different the time is compared to the current time
				$delta = round( ( time() - $timestamp ) / 60 );
				$cacheKey = "system_ajax_getFormattedTimes_lang_" . $lang->_id . "_ts_" . ( $delta < 0 ? "min" : "pos" ) . "_" . $delta;
				try
				{
					$cached = Cache::i()->getWithExpire( $cacheKey, true );
					if ( empty( $cached ) or !is_string( $cached ) )
					{
						throw new OutOfRangeException;
					}
					$output[ 'time_' . $timestamp ] = $cached;
				}
				catch ( OutOfRangeException )
				{
					$datetime = DateTime::ts( $timestamp );
					$html = $datetime->html( memberOrLanguage: $lang );
					$lang->parseOutputForDisplay( $html );
					Cache::i()->storeWithExpire( $cacheKey, $html, DateTime::ts( time() + ( 86400 * 7 ) ), true ); // Cache for 7 days because the key is dependent on the relative time
					$output[ "time_" . $timestamp ] = $html;
				}
			}
		}

		Output::i()->json( [ "timestamps" => $output ] );
	}
}