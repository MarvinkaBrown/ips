<?php
/**
 * @brief		Form builder helper to allow user-created "groups" of other users
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Mar 2014
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Member;
use function count;
use function defined;
use function is_int;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Form builder helper to allow user-created "groups" of other users
 */
class SocialGroup extends FormMember
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'owner'			=> 1,		// \IPS\Member object or member ID who "owns" this group
	 		'multiple'	=> 1,	// Maximum number of members. NULL for any. Default is NULL.
	 	);
	 * @endcode
	 */
	public array $childDefaultOptions = array(
		'multiple'	=> NULL,
		'owner'		=> NULL,
	);

	/**
	 * @brief	Group ID, if already set
	 */
	public int|array|null $groupId	= NULL;

	/**
	 * @brief	Options array
	 */
	public array $options = array();

	/**
	 * Constructor
	 *
	 * @param string $name Name
	 * @param mixed $defaultValue Default value
	 * @param bool|null $required Required? (NULL for not required, but appears to be so)
	 * @param array $options Type-specific options
	 * @param callable|null $customValidationCode Custom validation code
	 * @param string|null $prefix HTML to show before input field
	 * @param string|null $suffix HTML to show after input field
	 * @param string|null $id The ID to add to the row
	 */
	public function __construct( string $name, mixed $defaultValue=NULL, ?bool $required=FALSE, array $options=array(), callable $customValidationCode=NULL, string $prefix=NULL, string $suffix=NULL, string $id=NULL )
	{
		/* Call parent constructor */
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );

		/* If we are editing, the value will be a group ID we need to load */
		if( is_int( $this->value ) )
		{
			$this->groupId	= $this->value;

			$values = array();

			foreach( Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=?', $this->value ) )->setKeyField('member_id')->setValueField('member_id') as $k => $v )
			{
				$values[ $k ] = Member::load( $v );
			}
			
			$this->value = $values;
		}

		/* Make sure we have an owner...fall back to logged in member */
		if( !isset($this->options['owner'] ) )
		{
			$this->options['owner']	= Member::loggedIn();
		}
		else if( !$this->options['owner'] instanceof Member AND is_int( $this->options['owner'] ) )
		{
			$this->options['owner']	= Member::load( $this->options['owner'] );
		}
	}

	/**
	 * Save the social group
	 *
	 * @return	void
	 */
	public function saveValue() : void
	{
		/* Delete any existing entries */
		if( $this->groupId )
		{
			Db::i()->delete( 'core_sys_social_group_members', array( 'group_id=?', $this->groupId ) );
		}
		else if( $this->value )
		{
			$this->groupId	= Db::i()->insert( 'core_sys_social_groups', array( 'owner_id' => $this->options['owner']->member_id ) );
		}

		if( $this->value )
		{
			$inserts = array();
			foreach( $this->value as $member )
			{
				$inserts[] = array( 'group_id' => $this->groupId, 'member_id' => $member->member_id );
			}
			
			if( count( $inserts ) )
			{
				Db::i()->insert( 'core_sys_social_group_members', $inserts );
			}
		}

		$this->value	= $this->groupId;
		
		Db::i()->update( 'core_members', array( 'permission_array' => NULL ) );
	}
}