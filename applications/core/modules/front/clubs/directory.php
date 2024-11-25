<?php
/**
 * @brief		Clubs List
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Feb 2017
 */

namespace IPS\core\modules\front\clubs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\Content\Search\Query;
use IPS\core\Stream;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Radio;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Club\CustomField;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function floatval;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Clubs List
 */
class directory extends Controller
{

	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 * @example array(
	'community_area' => array( 'value' => 'search', 'odkUpdate' => 'true' )
	 * )
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'clubs', 'odkUpdate' => 'true']
	);

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Permission check */
		if ( !Settings::i()->clubs )
		{
			Output::i()->error( 'no_module_permission', '2C349/2', 403, '' );
		}
		
		/* CSS */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/clubs.css', 'core', 'front' ) );

		/* JS */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_clubs.js', 'core', 'front' ) );
		
		/* Location for online list */
		Session::i()->setLocation( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), array(), 'loc_clubs_directory' );

		/* Pass up */
		parent::execute();
	}
	
	/**
	 * List
	 *
	 * @return	void
	 */
	protected function manage() : void
	{	
		$baseUrl = Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' );

		/* Display */
		$view = Settings::i()->clubs_default_view;
		if ( Settings::i()->clubs_allow_view_change )
		{
			if ( isset( Request::i()->view ) and in_array( Request::i()->view, array( 'grid', 'list' ) ) )
			{
				Session::i()->csrfCheck();
				Request::i()->setCookie( 'clubs_view', Request::i()->view, DateTime::create()->add( new DateInterval('P1Y' ) ) );
				$view = Request::i()->view;

				Output::i()->redirect( $baseUrl );
			}
			elseif ( isset( Request::i()->cookie['clubs_view'] ) and in_array( Request::i()->cookie['clubs_view'], array( 'grid', 'list' ) ) )
			{
				$view = Request::i()->cookie['clubs_view'];
			}
		}
		
		/* All Clubs: Sort */
		$sortOption = Settings::i()->clubs_default_sort;
		if ( isset( Request::i()->sort ) and in_array( Request::i()->sort, array( 'last_activity', 'members', 'content', 'created', 'name' ) ) )
		{
			$sortOption = Request::i()->sort;

			$baseUrl = $baseUrl->setQueryString( 'sort', $sortOption );
		}
		
		/* All Clubs: Filters */
		$filters = array();
		$mineOnly = FALSE;
		$extraWhere = NULL;
		if ( isset( Request::i()->filter ) and Request::i()->filter === 'mine' AND Member::loggedIn()->member_id )
		{
			$mineOnly = TRUE;
			$baseUrl = $baseUrl->setQueryString( 'filter', 'mine' );
		}
		if ( isset( Request::i()->type ) and in_array( Request::i()->type, array( 'free', 'paid' ) ) )
		{
			$baseUrl = $baseUrl->setQueryString( 'type', Request::i()->type );
			
			if ( Request::i()->type === 'free' )
			{
				$extraWhere[] = array( 'fee IS NULL' );
			}
			else
			{
				$extraWhere[] = array( 'fee IS NOT NULL' );
			}
		}
		foreach ( CustomField::fields() as $field )
		{
			$k = "f{$field->id}";
			if ( $field->filterable and isset( Request::i()->$k ) )
			{
				switch ( $field->type )
				{
					case 'Checkbox':
					case 'YesNo':
						if ( in_array( Request::i()->$k, array( 0, 1 ) ) )
						{
							$filters[ $field->id ] = Request::i()->$k;
							$baseUrl = $baseUrl->setQueryString( 'f' . $field->id, Request::i()->$k );
						}
						break;
						
					case 'CheckboxSet':
					case 'Radio':
					case 'Select':
						$options = json_decode( $field->extra, TRUE );
						foreach ( Request::i()->$k as $id )
						{
							if ( isset( $options[ $id ] ) )
							{
								if( $field->type == 'CheckboxSet' )
								{
									$filters[ $field->id ][ $id ] = $id;
								}
								else
								{
									$filters[ $field->id ][ $id ] = $options[ $id ];
								}
							}
						}
						if( isset( $filters[ $field->id ] ) )
						{
							$baseUrl = $baseUrl->setQueryString( 'f' . $field->id, array_keys( $filters[ $field->id ] ) );
						}
						
						break;
				}
			}
		}
		
		/* Get Featured Clubs */
		$featuredClubs = Club::clubs( Member::loggedIn(), NULL, 'RAND()', FALSE, array(), 'featured=1' );
		
		/* Get All Clubs */
		$perPage = 24;
		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;
		if( $page < 1 )
		{
			$page = 1;
		}

		$clubsCount	= Club::clubs( Member::loggedIn(), array( ( $page - 1 ) * $perPage, $perPage ), $sortOption, $mineOnly, $filters, $extraWhere, TRUE );
		$allClubs	= Club::clubs( Member::loggedIn(), array( ( $page - 1 ) * $perPage, $perPage ), $sortOption, $mineOnly, $filters, $extraWhere );
		$pagination	= Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl, ceil( $clubsCount / $perPage ), $page, $perPage );
		
		/* Get my clubs and invites */
		$myClubsActivity = NULL;
		$myClubsInvites = NULL;
		if ( Member::loggedIn()->member_id )
		{
			$myClubsActivity = new Stream;
			$myClubsActivity = $myClubsActivity->query()->filterByClub( Member::loggedIn()->clubs() )->setLimit(6)->setOrder( Query::ORDER_NEWEST_UPDATED )->search();
			$myClubsInvites = new ActiveRecordIterator( Db::i()->select( '*', 'core_clubs_memberships', array( 'member_id=? and ( status=? or status=? )', Member::loggedIn()->member_id, Club::STATUS_REQUESTED, Club::STATUS_INVITED )
								)->join(
										'core_clubs',
										"core_clubs_memberships.club_id=core_clubs.id"
			), '\IPS\Member\Club' );
		}
		
		/* Build Map */
		$mapMarkers = NULL;
		if ( GeoLocation::enabled() )
		{
			$mapMarkers = array();
			
			$where = array( array( 'location_lat IS NOT NULL' ) );
			if ( !Member::loggedIn()->member_id )
			{
				$where[] = array( "type<>?", Club::TYPE_PRIVATE );
			}
			elseif ( !Member::loggedIn()->modPermission('can_access_all_clubs') )
			{
				$where[] = array( "( type<>? OR status IN('" . Club::STATUS_MEMBER .  "','" . Club::STATUS_MODERATOR . "','" . Club::STATUS_LEADER . "','" . Club::STATUS_EXPIRED . "','" . Club::STATUS_EXPIRED_MODERATOR . "') )", Club::TYPE_PRIVATE );
			}
			
			$select = Db::i()->select( array( 'id', 'name', 'location_lat', 'location_long' ), 'core_clubs', $where );
			if ( Member::loggedIn()->member_id )
			{
				$select->join( 'core_clubs_memberships', array( 'club_id=id AND member_id=?', Member::loggedIn()->member_id ) );
			}
			
			foreach ( $select as $club )
			{
				$mapMarkers[ $club['id'] ] = array(
					'lat'	=> floatval( $club['location_lat'] ),
					'long'	=> floatval( $club['location_long'] ),
					'title'	=> $club['name']
				);
			}
		}

		if ( Member::loggedIn()->member_id )
		{
			Output::i()->sidebar['contextual'] = Theme::i()->getTemplate('clubs')->myClubsSidebar( Club::clubs( Member::loggedIn(), 10, 'last_activity', TRUE ), $myClubsActivity, $myClubsInvites );

			/* Prime the cache */
			$allClubs = iterator_to_array( $allClubs );
			
			if( count( $allClubs ) )
			{
				foreach( $allClubs as $clubId => $clubData )
				{
					if( !isset( $clubData->memberStatuses[ Member::loggedIn()->member_id ] ) )
					{
						$clubData->memberStatuses[ Member::loggedIn()->member_id ] = NULL;
					}
				}

				foreach( Db::i()->select( '*', 'core_clubs_memberships', array( 'member_id=? AND ' . Db::i()->in( 'club_id', array_keys( $allClubs ) ), Member::loggedIn()->member_id ) ) as $clubMembership )
				{
					$allClubs[ $clubMembership['club_id'] ]->memberStatuses[ Member::loggedIn()->member_id ] = $clubMembership['status'];
				}
			}
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack('module__core_clubs');
		Output::i()->output = Theme::i()->getTemplate('clubs')->directory( $featuredClubs, $allClubs, $pagination, $baseUrl, $sortOption, $myClubsActivity, $mapMarkers, $view, $mineOnly );
	}
	
	/**
	 * Filter Form
	 *
	 * @return	void
	 */
	protected function filters() : void
	{		
		$fields = CustomField::roots();
		
		$form = new Form('filter', 'filter');
		
		if ( Member::loggedIn()->member_id )
		{
			$form->add( new Radio( 'club_filter_type', isset( Request::i()->filter ) ? Request::i()->filter : 'all', TRUE, array( 'options' => array(
				'all'	=> 'all_clubs',
				'mine'	=> 'my_clubs'
			) ) ) );
		}
		
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->clubs_paid_on )
		{
			$form->add( new Radio( 'club_membership_fee', isset( Request::i()->type ) ? Request::i()->type : 'all', TRUE, array( 'options' => array(
				'all'	=> 'all_clubs',
				'free'	=> 'club_membership_free',
				'paid'	=> 'club_membership_paid'
			) ) ) );
		}
		
		foreach ( $fields as $field )
		{
			if ( $field->filterable )
			{
				$k = "f{$field->id}";
				switch ( $field->type )
				{
					case 'Checkbox':
					case 'YesNo':
						$input = new CheckboxSet( 'field_' . $field->id, isset( Request::i()->$k ) ? Request::i()->$k : array( 1, 0 ), FALSE, array( 'options' => array(
							1			=> 'yes',
							0			=> 'no',
						) ) );
						$input->label = $field->_title;
						$form->add( $input );
						break;
						
					case 'CheckboxSet':
					case 'Radio':
					case 'Select':
						$options = json_decode( $field->extra, TRUE );
						$input = new CheckboxSet( 'field_' . $field->id, isset( Request::i()->$k ) ? Request::i()->$k : array_keys( $options ), FALSE, array( 'options' => $options ) );
						$input->label = $field->_title;
						$form->add( $input );
						break;
				}
			}
		}
		
		if ( $values = $form->values() )
		{
			$url = Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' );
			
			if ( Member::loggedIn()->member_id and $values['club_filter_type'] === 'mine' )
			{
				$url = $url->setQueryString( 'filter', 'mine' );
			}
			
			if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->clubs_paid_on and $values['club_membership_fee'] !== 'all' )
			{
				$url = $url->setQueryString( 'type', $values['club_membership_fee'] );
			}
			
			foreach ( $fields as $field )
			{
				if ( $field->filterable )
				{					
					switch ( $field->type )
					{
						case 'Checkbox':
						case 'YesNo':
							if ( count( $values[ 'field_' . $field->id ] ) === 1 )
							{
								$url = $url->setQueryString( 'f' . $field->id, array_pop( $values[ 'field_' . $field->id ] ) );
							}
							break;
							
						case 'CheckboxSet':
						case 'Radio':
						case 'Select':
							$options = json_decode( $field->extra, TRUE );
							if ( count( $values[ 'field_' . $field->id ] ) > 0 and count( $values[ 'field_' . $field->id ] ) < count( $options ) )
							{
								$url = $url->setQueryString( 'f' . $field->id, $values[ 'field_' . $field->id ] );
							}
							break;
					}
				}
			}
			
			Output::i()->redirect( $url );
		}
		
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			Output::i()->output = $form;
		}
	}
	
	/**
	 * Create
	 *
	 * @return	void
	 */
	protected function create() : void
	{
		$availableTypes = array();
		if ( Member::loggedIn()->member_id ) // Guests can't create any type of clubs
		{
			foreach ( explode( ',', Member::loggedIn()->group['g_create_clubs'] ) as $type )
			{
				if ( $type !== '' )
				{
					$availableTypes[ $type ] = 'club_type_' . $type;
				}
			}
		}
		if ( !$availableTypes )
		{
			Output::i()->error( Member::loggedIn()->member_id ? 'no_module_permission' : 'no_module_permission_guest', '2C349/1', 403, '' );
		}
		
		if ( Member::loggedIn()->group['g_club_limit'] )
		{
			if ( Db::i()->select( 'COUNT(*)', 'core_clubs', array( 'owner=?', Member::loggedIn()->member_id ) )->first() >= Member::loggedIn()->group['g_club_limit'] )
			{
				Output::i()->error( Member::loggedIn()->language()->addToStack( 'too_many_clubs', FALSE, array( 'pluralize' => array( Member::loggedIn()->group['g_club_limit'] ) ) ), '2C349/3', 403, '' );
			}
		}
		
		$club = new Club;
		if ( $form = $club->form( FALSE, TRUE, $availableTypes ) )
		{
			if( $values = $form->values() )
			{
				$club->processForm( $values, FALSE, TRUE, $availableTypes );

				Output::i()->redirect( $club->url() );
			}

			$form->class = 'ipsForm--vertical ipsForm--clubs-directory';
		}
		else
		{
			if ( !$club->approved and Member::loggedIn()->modPermission('can_access_all_clubs') )
			{
				$club->approved = TRUE;
				$club->save();
			}
			
			if( $club->approved )
			{
				Member::loggedIn()->achievementAction( 'core', 'NewClub', $club );
			}
			
			Output::i()->redirect( $club->url() );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('create_club');
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'clubs' )->create( $form );
		}
	}
}