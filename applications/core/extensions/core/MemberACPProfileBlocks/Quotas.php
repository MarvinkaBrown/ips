<?php
/**
 * @brief		ACP Member Profile: Quotas Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\core\MemberACPProfile\Block;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Quotas Block
 */
class Quotas extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$messengerCount = NULL;
		$messengerPercent = NULL;
		if ( $this->member->canAccessModule( Module::get( 'core', 'messaging', 'front' ) ) and !$this->member->members_disable_pm )
		{
			$messengerCount = Db::i()->select( 'count(*)', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', $this->member->member_id ) )->first();
			
			if ( $this->member->group['g_max_messages'] > 0 )
			{
				$messengerPercent = floor( 100 / $this->member->group['g_max_messages'] * $messengerCount );
			}
		}
		
		$attachmentStorage = NULL;
		$attachmentPercent = NULL;
		if ( $this->member->group['g_attach_max'] != 0 )
		{
			$attachmentStorage = Db::i()->select( 'SUM(attach_filesize)', 'core_attachments', array( 'attach_member_id=?', $this->member->member_id ) )->first();
			if ( !$attachmentStorage )
			{
				$attachmentStorage = 0;
			}
			
			if ( $this->member->group['g_attach_max'] > 0 )
			{
				$attachmentPercent = floor( 100 / ( $this->member->group['g_attach_max'] * 1024 ) * $attachmentStorage );
			}
		}
		
		$viewAttachmentsLink = NULL;
		if ( $attachmentStorage and Member::loggedIn()->hasAcpRestriction( 'core', 'overview', 'files_view' ) )
		{
			$viewAttachmentsLink = Url::internal('app=core&module=overview&controller=files&advanced_search_submitted=1')->setQueryString( 'attach_member_id', $this->member->name )->csrf();
		}
		
		return (string) Theme::i()->getTemplate('memberprofile')->quotas( $this->member, $messengerCount, $messengerPercent, $attachmentStorage, $attachmentPercent, $viewAttachmentsLink );
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		Session::i()->csrfCheck();
		
		$old = $this->member->members_disable_pm;
		if ( Request::i()->enable )
		{
			$this->member->members_disable_pm = 0;
		}
		else
		{
			if ( Request::i()->prompt ) // Member cannot re-enable
			{
				$this->member->members_disable_pm = 2;
			}
			else // Member can re-enable
			{
				$this->member->members_disable_pm = 1;
			}
		}
		$this->member->save();
		if ( $old != $this->member->members_disable_pm )
		{
			$this->member->logHistory( 'core', 'warning', array( 'restrictions' => array( 'members_disable_pm' => array( 'old' => $old, 'new' => $this->member->members_disable_pm ) ) ) );
		}
		
		Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$this->member->member_id}" ), 'saved' );
	}
}