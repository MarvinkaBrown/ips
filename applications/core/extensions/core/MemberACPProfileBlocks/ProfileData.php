<?php
/**
 * @brief		ACP Member Profile: Profile Data Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\TabbedBlock;
use IPS\core\ProfileFields\Field;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Profile Data Block
 */
class ProfileData extends TabbedBlock
{
	/**
	 * @brief	Fields
	 */
	protected array $fields = array();
	
	/**
	 * @brief	Clubs
	 */
	protected array|ActiveRecordIterator|null $clubs = NULL;
	
	/**
	 * Constructor
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		parent::__construct( $member );
		
		$this->fields = $this->member->profileFields( Field::STAFF );
		$this->clubs = Settings::i()->clubs ? Club::clubs( NULL, NULL, 'last_activity', array( 'member' => $this->member, 'statuses' => array( Club::STATUS_MODERATOR, Club::STATUS_EXPIRED_MODERATOR, Club::STATUS_LEADER ) ) ) : array();
	}
	
	/**
	 * Get Block Title
	 *
	 * @return	string
	 */
	public function blockTitle() : string
	{
		return 'profile_data';
	}
	
	/**
	 * Get Tab Names
	 *
	 * @return	array
	 */
	public function tabs(): array
	{
		$return = array();
		if ( count( $this->fields ) || $this->member->rank['title'] || $this->member->rank['image'] || Settings::i()->profile_birthday_type != 'none' || Settings::i()->signatures_enabled )
		{
			$return['fields'] = 'profile_fields';
		}		
		if ( count( $this->clubs ) )
		{
			$return['clubs'] = 'club_ownership';
		}
		
		return $return;
	}
	
	/**
	 * Show Edit Link?
	 *
	 * @return	bool
	 */
	protected function showEditLink() : bool
	{
		return true;
	}

	/**
	 * Get output
	 *
	 * @param string $tab
	 * @return    mixed
	 */
	public function tabOutput(string $tab ): mixed
	{
		if ( $tab == 'fields' )
		{
			return Theme::i()->getTemplate('memberprofile')->profileData( $this->member, $this->fields );
		}
		elseif ( $tab == 'clubs' )
		{			
			return Theme::i()->getTemplate('memberprofile')->clubs( $this->member, $this->clubs );
		}

		return '';
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		/* Build basic form */
		$form = new Form;
		$form->addHeader('profile_data');
		if ( Settings::i()->profile_birthday_type !== 'none' )
		{
			$form->add( new Custom( 'bday', array( 'year' => $this->member->bday_year, 'month' => $this->member->bday_month, 'day' => $this->member->bday_day ), FALSE, array( 'getHtml' => function( $element )
			{
				return strtr( Member::loggedIn()->language()->preferredDateFormat(), array(
					'DD'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_day( $element->name, $element->value, $element->error ),
					'MM'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_month( $element->name, $element->value, $element->error ),
					'YY'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
					'YYYY'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
				) );
			} ) ) );
		}
		if ( Settings::i()->signatures_enabled )
		{
			$form->add( new Editor( 'signature', $this->member->signature, FALSE, array( 'app' => 'core', 'key' => 'Signatures', 'autoSaveKey' => "sig-{$this->member->member_id}", 'attachIds' => array( $this->member->member_id ) ) ) );
		}
	
		/* Profile Fields */
		try
		{
			$values = Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
		}
		catch( UnderflowException $e )
		{
			$values	= array();
		}
		if( count( $values ) )
		{
			foreach ( Field::fields( $values, Field::STAFF, $this->member ) as $group => $fields )
			{
				$form->addHeader( "core_pfieldgroups_{$group}" );
				foreach ( $fields as $field )
				{
					$form->add( $field );
				}
			}
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Profile Fields */
			try
			{
				$profileFields = Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
				
				if ( !is_array( $profileFields ) ) // If \IPS\Db::i()->select()->first() has only one column, then the contents of that column is returned. We do not want this here
				{
					$profileFields = array();
				}
			}
			catch( UnderflowException $e )
			{
				$profileFields	= array();
			}			
			$profileFields['member_id'] = $this->member->member_id;
			foreach ( Field::fields( $profileFields, Field::STAFF, $this->member ) as $group => $fields )
			{
				foreach ( $fields as $id => $field )
				{
					if ( $field instanceof Upload )
					{
						$profileFields[ "field_{$id}" ] = (string) $values[ $field->name ];
					}
					else
					{
						$profileFields[ "field_{$id}" ] = $field::stringValue( !empty( $values[ $field->name ] ) ? $values[ $field->name ] : NULL );
					}
				}
			}
			$this->member->changedCustomFields = $profileFields;
			Db::i()->replace( 'core_pfields_content', $profileFields );

			/* Profile Preferences */
			if ( $values['bday'] )
			{
				$this->member->bday_day	= $values['bday']['day'];
				$this->member->bday_month	= $values['bday']['month'];
				$this->member->bday_year	= $values['bday']['year'];
			}
			else
			{
				$this->member->bday_day = NULL;
				$this->member->bday_month = NULL;
				$this->member->bday_year = NULL;
			}
			if ( Settings::i()->signatures_enabled )
			{
				$this->member->signature = $values['signature'];
			}
			$this->member->save();
											
			/* Log and Redirect */
			Session::i()->log( 'acplog__members_edited_profile', array( $this->member->name => FALSE ) );
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$this->member->member_id}" ), 'saved' );
		}
		
		/* Display */
		return $form;
	}
}