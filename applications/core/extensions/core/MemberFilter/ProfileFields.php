<?php
/**
 * @brief		Member Filter Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		15 Apr 2020
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\ProfileFields\Field;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Member;
use IPS\Theme;
use LogicException;
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
 * Member Filter Extension
 */
class ProfileFields extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail', 'group_promotions' ) );
	}

	/** 
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		$return = array();

		foreach ( Field::fieldData() as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				/* Work out the object type so we can show the appropriate field */
				$type		= 'IPS\Helpers\Form\\' . $field['pf_type'];
				$helper		= NULL;
				$langKey	= "core_pfield_{$id}";

				switch ( $type )
				{
					case 'IPS\Helpers\Form\Text':
					case 'IPS\Helpers\Form\Tel':
					case 'IPS\Helpers\Form\Editor':
					case 'IPS\Helpers\Form\Email':
					case 'IPS\Helpers\Form\TextArea':
					case 'IPS\Helpers\Form\Url':
						$helper = new Text( $langKey, ( isset( $criteria[ $langKey ] ) ) ? $criteria[ $langKey ] : NULL, FALSE );
						break;
					case 'IPS\Helpers\Form\Date':
						$helper = new DateRange( $langKey, ( isset( $criteria[ $langKey ] ) ) ? $criteria[ $langKey ] : NULL, FALSE );
						break;
					case 'IPS\Helpers\Form\Number':
						$helper = new Custom( $langKey, ( isset( $criteria[ $langKey ] ) ) ? $criteria[ $langKey ] : NULL, FALSE, array(
							'getHtml'	=> function( $element )
							{
								return Theme::i()->getTemplate( 'forms', 'core' )->select( "{$element->name}[0]", ( is_array( $element->value ) AND isset( $element->value[0] ) ) ? $element->value[0] : NULL, $element->required, array(
									'any'	=> Member::loggedIn()->language()->addToStack('any'),
									'gt'	=> Member::loggedIn()->language()->addToStack('gt'),
									'lt'	=> Member::loggedIn()->language()->addToStack('lt'),
									'eq'	=> Member::loggedIn()->language()->addToStack('exactly'),
								),
								FALSE,
								NULL,
								FALSE,
								array(
									'any'	=> array(),
									'gt'	=> array( $element->name . '-qty' ),
									'lt'	=> array( $element->name . '-qty' ),
									'eq'	=> array( $element->name . '-qty' ),
								) )
								. ' '
								. Theme::i()->getTemplate( 'forms', 'core', 'global' )->number( "{$element->name}[1]", ( is_array( $element->value ) AND isset( $element->value[1] ) ) ? $element->value[1] : NULL, $element->required, NULL, FALSE, NULL, NULL, NULL, 0, NULL, FALSE, NULL, array(), array(), array( $element->name . '-qty' ) );
							}
						) );
						break;
					case 'IPS\Helpers\Form\Checkbox':
						$options = array(
							'' => 'any_value',
							1 => 'checked',
							0 => 'unchecked'
						);
						$helper = new Select( $langKey, $criteria[ $langKey ] ?? NULL, FALSE, array( 'options' => $options ) );
						break;
					case 'IPS\Helpers\Form\YesNo':
						$options = array(
							'' => 'any_value',
							1 => 'yes',
							0 => 'no'
						);
						$helper = new Select( $langKey, $criteria[ $langKey ] ?? NULL, FALSE, array( 'options' => $options ) );
						break;
					case 'IPS\Helpers\Form\Select':
					case 'IPS\Helpers\Form\Radio':
						if( $field['pf_multiple'] )
						{
							$options = array();
						}
						else
						{
							$options = array( '' => "");
						}

						$_options = json_decode( $field['pf_content'], true );

						if( count( $_options ) )
						{
							foreach ( $_options as $option )
							{
								$options[ $option ] = $option;
							}
						}

						$helper = new Select( $langKey, ( isset( $criteria[ $langKey ] ) ) ? $criteria[ $langKey ] : NULL, FALSE, array( 'options' => $options, 'multiple' => (bool)$field['pf_multiple'], 'noDefault' => true ) );
						break;
					case 'IPS\Helpers\Form\CheckboxSet':
						$options = json_decode( $field['pf_content'], true );
						$helper = new Select( $langKey, ( isset( $criteria[$langKey] ) ) ? $criteria[$langKey] : null, false, ['options' => $options, 'multiple' => true, 'noDefault' => true] );
						break;
				}

				if( $helper )
				{
					$return[] = $helper;
				}
			}
		}

		return $return;
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return	array			False, or an array of data to use later when filtering the members
	 * @throws LogicException
	 */
	public function save( array $post ): array
	{
		$values = array();

		foreach ( Field::fieldData() as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				$langKey	= "core_pfield_{$id}";
				if( isset( $post[ $langKey ] ) )
				{
					$values[ $langKey ] = $post[ $langKey ];
				}
				else
				{
					$values[ $langKey ] = NULL;
				}
			}
		}

		return $values;
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		$where	= array();

		foreach ( Field::fieldData() as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				/* Work out the object type so we can show the appropriate field */
				$type		= 'IPS\Helpers\Form\\' . $field['pf_type'];
				$helper		= NULL;
				$langKey	= "core_pfield_{$id}";
				
				/* We need to handle YesNo and Checkbox fields specially, as it will intentionally try and pass '0' which is valid, but returns true in empty() */
				switch( $type )
				{
					case 'IPS\Helpers\Form\YesNo':
					case 'IPS\Helpers\Form\Checkbox':
						if ( !isset( $data[ $langKey ] ) OR $data[ $langKey ] === '' )
						{
							continue 2;
						}
						break;
					
					default:
						if ( empty( $data[ $langKey ] ) )
						{
							continue 2;
						}
						break;
				}

				switch ( $type )
				{
					case 'IPS\Helpers\Form\Text':
					case 'IPS\Helpers\Form\Tel':
					case 'IPS\Helpers\Form\Editor':
					case 'IPS\Helpers\Form\Email':
					case 'IPS\Helpers\Form\TextArea':
					case 'IPS\Helpers\Form\Url':
						$where[] = "field_{$id} LIKE '%" . Db::i()->real_escape_string( $data[ $langKey ] ) . "%'";
						break;
					case 'IPS\Helpers\Form\Date':
						if ( $data[ $langKey ]['start'] )
						{
							$data[ $langKey ]['start'] = new DateTime( $data[ $langKey ]['start'] );

							$where[] = "field_{$id}>" . $data[ $langKey ]['start']->getTimestamp();
						}
						if ( $data[ $langKey ]['end'] )
						{
							$data[ $langKey ]['end'] = new DateTime( $data[ $langKey ]['end'] );

							$where[] = "field_{$id}<" . $data[ $langKey ]['end']->getTimestamp();
						}
						break;
					case 'IPS\Helpers\Form\Number':
						switch ( $data[ $langKey ][0] )
						{
							case 'gt':
								$where[] = "field_{$id}>'" . (float) $data[ $langKey ][1] . "'";
								break;
							case 'lt':
								$where[] = "field_{$id}<'" . (float) $data[ $langKey ][1] . "'";
								break;
							case 'eq':
								$where[] = "field_{$id}='" . (float) $data[ $langKey ][1] . "'";
								break;
						}
						break;
					case 'IPS\Helpers\Form\Checkbox':
					case 'IPS\Helpers\Form\YesNo':
						$where[] = "field_{$id}='" . $data[ $langKey ] . "'";
						break;
					case 'IPS\Helpers\Form\Select':
					case 'IPS\Helpers\Form\Radio':
					case 'IPS\Helpers\Form\CheckboxSet':
						if ( isset( $field['pf_multiple'] ) AND $field['pf_multiple'] )
						{
							$where[] = Db::i()->findInSet( 'field_' . $id, $data[ $langKey ] );
						}
						else
						{
							$where[] = "field_{$id}='" . $data[ $langKey ] . "'";
						}
						break;
				}
			}
		}

		if( !count( $where ) )
		{
			return NULL;
		}
		else
		{
			return array( '(' . implode( ' AND ', $where ) . ')' );
		}
	}
	
	/**
	 * Callback for member retrieval database query
	 * Can be used to set joins
	 *
	 * @param array $data	The array returned from the save() method
	 * @param	Db\Select	$query	The query
	 * @return	void
	 */
	public function queryCallback( array $data, Db\Select $query ) : void
	{
		$query->join( 'core_pfields_content', "core_members.member_id=core_pfields_content.member_id" );
	}

	/**
	 * Determine if a member matches specified filters
	 *
	 * @note	This is only necessary if availableIn() includes group_promotions
	 * @param	Member	$member		Member object to check
	 * @param	array 		$filters	Previously defined filters
	 * @param	object|NULL	$object		Calling class
	 * @return	bool
	 */
	public function matches( Member $member, array $filters, ?object $object=NULL ) : bool
	{
		$profileFieldData = $member->profileFields( Field::STAFF );

		foreach ( Field::fieldData() as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				/* Work out the object type so we can show the appropriate field */
				$type		= 'IPS\Helpers\Form\\' . $field['pf_type'];
				$helper		= NULL;
				$langKey	= "core_pfield_{$id}";

				if( !isset( $filters[ $langKey ] ) OR !$filters[ $langKey ] )
				{
					continue;
				}

				switch ( $type )
				{
					case 'IPS\Helpers\Form\Text':
					case 'IPS\Helpers\Form\Tel':
					case 'IPS\Helpers\Form\Editor':
					case 'IPS\Helpers\Form\Email':
					case 'IPS\Helpers\Form\TextArea':
					case 'IPS\Helpers\Form\Url':
						if( !$profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] OR mb_strpos( $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ], $filters[ $langKey ] ) === FALSE )
						{
							return FALSE;
						}
						break;
					case 'IPS\Helpers\Form\Date':

						$start = NULL;
						$end = NULL;
						if ( $filters[ $langKey ]['start'] )
						{
							$start = ( new DateTime( $filters[ $langKey ]['start'] ) )->getTimestamp();
						}
						if ( $filters[ $langKey ]['end'] )
						{
							$end = ( new DateTime( $filters[ $langKey ]['end'] ) )->getTimestamp();
						}

						if( ( $start OR $end ) AND !$profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] )
						{
							return FALSE;
						}

						if( ( $start AND $start > $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] ) OR ( $end AND $end < $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] ) )
						{
							return FALSE;
						}
						break;
					case 'IPS\Helpers\Form\Number':
						switch ( $filters[ $langKey ][0] )
						{
							case 'gt':
								if( !$profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] OR $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] < $filters[ $langKey ][1] )
								{
									return FALSE;
								}
								break;
							case 'lt':
								if( !$profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] OR $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] > $filters[ $langKey ][1] )
								{
									return FALSE;
								}
								break;
							case 'eq':
								if( !$profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] OR $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] != $filters[ $langKey ][1] )
								{
									return FALSE;
								}
								break;
						}
						break;
					case 'IPS\Helpers\Form\Select':
					case 'IPS\Helpers\Form\Radio':
						if( !$profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] )
						{
							return FALSE;
						}

						if ( isset( $field['pf_multiple'] ) AND $field['pf_multiple'] )
						{
							$values = explode( ',', $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] );

							foreach( $filters[ $langKey ] as $_filter )
							{
								if( !in_array( $_filter, $values ) )
								{
									return FALSE;
								}
							}
						}
						else
						{
							if( $profileFieldData['core_pfieldgroups_' . $group ][ $langKey ] != $filters[ $langKey ] )
							{
								return FALSE;
							}
						}
						break;
				}
			}
		}

		return TRUE;
	}
}