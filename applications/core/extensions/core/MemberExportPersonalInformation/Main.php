<?php
/**
 * @brief		ACP Export Personal Information
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 May 2018
 */

namespace IPS\core\extensions\core\MemberExportPersonalInformation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\core\ProfileFields\Field;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\MemberExportPiiAbstract;
use IPS\Member;
use IPS\Member\Device;
use IPS\Patterns\ActiveRecordIterator;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Export Personal Information
 */
class Main extends MemberExportPiiAbstract
{
	/**
	 * Return data
	 * @param	Member		$member		The member
	 *
	 * @return	array
	 */
	public function getData( Member $member ): array
	{
		$return = array();
		
		/* Basic Data */
		$columns = array(
			'name',
			'email',
			'joined',
			'ip_address',
			'timezone',
			'last_visit',
			'last_post',
			'birthday',
			'allow_admin_mails',
		);
		
		foreach( $columns as $col )
		{
			$val = NULL;
			switch( $col )
			{
				case 'last_visit':
				case 'last_post':
					$val = ( ! empty( $member->$col ) ) ? DateTime::ts( $member->$col )->rfc3339() : NULL;
				break;
				case 'joined':
					$val = $member->joined->rfc3339();
				break;
				default:
					$val = (string) $member->$col;
				break;
			}
			
			$return['core'][ $col ] = $val;
		}
		
		/* Known IP addresses stored */
		$return['known_ip_addresses'] = array_keys( $member->ipAddresses() );
		
		/* Devices used */
		$devices = new ActiveRecordIterator( Db::i()->select( '*', 'core_members_known_devices', array( 'member_id=? AND last_seen>?', $member->member_id, ( new \DateTime )->sub( new DateInterval( Device::LOGIN_KEY_VALIDITY ) )->getTimestamp() ), 'last_seen DESC' ), 'IPS\Member\Device' );

		foreach ( $devices as $device )
		{
			try
			{
				$log = Db::i()->select( '*', 'core_members_known_ip_addresses', array( 'member_id=? AND device_key=?', $member->member_id, $device->device_key ), 'last_seen DESC' )->first();
			}
			catch ( UnderflowException $e )
			{
				continue;
			}
			
			$return['known_browsers'][] = array(
				'useragent' => $device->userAgent()->browser . ' ' . $device->userAgent()->browserVersion,
				'last_seen' => DateTime::ts( $log['last_seen'] )->rfc3339()
			);
		}
		
		/* Accepted T&S */
		foreach( Db::i()->select( '*', 'core_member_history', array( 'log_app=? and log_member=? and log_type=?', 'core', $member->member_id, 'terms_acceptance' ) ) as $row  )
		{
			$data = json_decode( $row['log_data'], TRUE );
			if ( ! empty( $data['type'] ) )
			{
				$return['terms_accepted'][ $data['type'] ][] = DateTime::ts( $row['log_date'] )->rfc3339();
			}
		}
		
		/* Accepted Bulk Email */
		foreach( Db::i()->select( '*', 'core_member_history', array( 'log_app=? and log_member=? and log_type=?', 'core', $member->member_id, 'admin_mails' ) ) as $row  )
		{
			$data = json_decode( $row['log_data'], TRUE );
			$return['bulk_mail_optins'][] = array(
				'enabled' => (boolean) $data['enabled'],
				'date'    => DateTime::ts( $row['log_date'] )->rfc3339()
			);
		}

		/* PII Profile Fields */
		$fieldValues	= Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $member->member_id ) )->first();

		foreach ( Field::values( $fieldValues, Field::PII_DATA_EXPORT ) as $group => $profielFields )
		{
			foreach( $profielFields as $fieldID => $value )
			{
				$return['pfield_'. $fieldID] = $value;

			}
		}

		return $return;
	}
	
}