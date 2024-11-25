<?php

/**
 * @brief        MemberListenerType
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/18/2023
 */

namespace IPS\Events\ListenerType;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Event;
use IPS\Content as ContentClass;
use IPS\Content\Reaction;
use IPS\Events\ListenerType;
use IPS\Http\Url;
use IPS\Member as MemberClass;
use IPS\Member\Club;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


/**
 * @method onCreateAccount( MemberClass $member ) : void
 * @method onValidate( MemberClass $member ) : void
 * @method onLogin( MemberClass $member ) : void
 * @method onLogout( MemberClass $member, Url $returnUrl ) : void
 * @method onProfileUpdate( MemberClass $member, array $changes ) : void
 * @method onSetAsSpammer( MemberClass $member ) : void
 * @method onUnSetAsSpammer( MemberClass $member ) : void
 * @method onMerge( MemberClass $member, MemberClass $member2 ) : void
 * @method onDelete( MemberClass $member ) : void
 * @method onEmailChange( MemberClass $member, string $new, string $old ) : void
 * @method onPassChange( MemberClass $member, string $new ) : void
 * @method onJoinClub( MemberClass $member, Club $club ) : void
 * @method onLeaveClub( MemberClass $member, Club $club ) : void
 * @method onEventRsvp( MemberClass $member, Event $event, int $response ) : void
 * @method onReact( MemberClass $member, ContentClass $content, Reaction $reaction ) : void
 * @method onUnreact( MemberClass $member, ContentClass $content ) : void
 * @method onFollow( MemberClass $member, object $object, bool $isAnonymous ): void
 * @method onUnfollow( MemberClass $member, object $object ) : void
 */
class MemberListenerType extends ListenerType
{
	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array(
		MemberClass::class
	);
}