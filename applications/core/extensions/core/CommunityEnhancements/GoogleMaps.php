<?php
/**
 * @brief		Community Enhancement: Google Maps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Apr 2013
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Member\Group;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement: Google Maps
 */
class GoogleMaps extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= FALSE;

	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "google_maps.jpeg";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->google_maps_api_key and ( Settings::i()->googlemaps or Settings::i()->googleplacesautocomplete ) );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$wizard = new Wizard( array(
			'google_maps_enable_apis'		=> function( $data )
			{
				$form = new Form( 'google_maps_enable_apis', 'continue' );
				$form->addHeader('google_maps_choose_features');
				$form->add( new YesNo( 'googlemaps', $data['googlemaps'] ?? Settings::i()->googlemaps, FALSE, array( 'togglesOn' => array( 'googleApi_jsapi', 'googleApi_staticapi', 'googleApi_geocodeapi', 'google_maps_enable_apis_google_maps_static_use_embed', 'google_maps_groups', 'google_maps_zoom' ) ) ) );
				$form->add( new YesNo( 'google_maps_static_use_embed', $data['google_maps_static_use_embed'] ?? Settings::i()->google_maps_static_use_embed, FALSE ) );

				$groups = [];
				foreach( Group::groups() as $g )
				{
					$groups[ $g->g_id ] = $g->name;
				}

				$form->add( new Select( 'google_maps_groups', Settings::i()->google_maps_groups == '*' ? '*' : explode( ",", Settings::i()->google_maps_groups ), true, array(
					'options' => $groups,
					'multiple' => true,
					'noDefault' => true,
					'unlimited' => '*',
					'unlimitedLang' => 'google_maps_groups_all'
				), null, null, null, 'google_maps_groups' ) );
				$form->add( new Number( 'google_maps_zoom', Settings::i()->google_maps_zoom ?: -1, false, array(
					'unlimited' => -1,
					'unlimitedLang' => 'google_maps_zoom_auto',
					'decimals' => 0,
					'min' => 1,
					'max' => 22
				), null, null, null, 'google_maps_zoom' ) );
				$form->add( new YesNo( 'googleplacesautocomplete', $data['googleplacesautocomplete'] ?? Settings::i()->googleplacesautocomplete, FALSE, array( 'togglesOn' => array( 'googleApi_places' ) ) ) );
				$form->addHeader('google_maps_enable_apis');
				$form->addMessage('google_maps_create_project_message');
				foreach ( array( 'jsapi', 'staticapi', 'geocodeapi', 'places' ) as $k )
				{
					$form->addHtml( Theme::i()->getTemplate('applications')->enhancementsGoogleMapsApi( $k ) );
				}
				
				if ( $values = $form->values() )
				{
					if ( $values['googlemaps'] or $values['googleplacesautocomplete'] or $values['google_maps_static_use_embed'] )
					{
						return $values;
					}
					else
					{
						Settings::i()->changeValues( array( 'googlemaps' => 0, 'googleplacesautocomplete' => 0, 'google_maps_static_use_embed' => 0 ) );
						Output::i()->redirect( Url::internal('app=core&module=applications&controller=enhancements'), 'saved' );
					}
				}
				
				return (string) $form;
			},
			'google_maps_create_credentials'=> function( $data )
			{
				$websiteUrl = rtrim( Settings::i()->base_url, '/' ) . '/*';
				
				$form = new Form;
				if ( $data['googlemaps'] )
				{
					$form->addHeader('google_maps_create_public_key_header');
				}
				$form->addMessage('google_maps_create_public_key_message');
				$form->add( new Text( 'google_maps_api_key', Settings::i()->google_maps_api_key, TRUE, array(), function( $val )
				{
					try
					{
						$response = Url::external( 'https://maps.googleapis.com/maps/api/staticmap' )->setQueryString( array(
							'center'		=> '40.714224,-73.961452',
							'zoom'		=> NULL,
							'size'		=> "100x100",
							'sensor'		=> 'false',
							'markers'	=> '40.714224,-73.961452',
							'key'		=> $val,
						) )->request()->get();
					}
					catch ( Exception $e )
					{
						throw new DomainException('google_maps_api_error');
					}
					if ( $response->httpResponseCode != 200 )
					{
						throw new DomainException( $response ?: 'google_maps_api_key_invalid' );
					}
				} ) );
				$form->addHtml( Theme::i()->getTemplate('applications')->enhancementsGoogleMapsKeyRestrictions( TRUE, $websiteUrl, $data ) );
				if ( $data['googlemaps'] )
				{
					$form->addHeader('google_maps_create_secret_key_header');
					$form->addMessage('google_maps_create_secret_key_message');
					$form->add( new Text( 'google_maps_api_key_secret', Settings::i()->google_maps_api_key_secret, TRUE, array(), function( $val )
					{
						try
						{
							$response = Url::external( "https://maps.googleapis.com/maps/api/geocode/json" )->setQueryString( array(
								'latlng'	=> '40.714224,-73.961452',
								'sensor'	=> 'false',
								'key'		=> $val
							) )->request()->get()->decodeJson();
						}
						catch ( Exception $e )
						{
							throw new DomainException('google_maps_api_error');
						}
						if ( !isset( $response['status'] ) or $response['status'] !== 'OK' )
						{
							throw new DomainException( ( isset( $response['error_message'] ) ) ? $response['error_message'] : 'google_maps_api_key_invalid' );
						}
					} ) );
					$form->addHtml( Theme::i()->getTemplate('applications')->enhancementsGoogleMapsKeyRestrictions( FALSE, $websiteUrl, $data ) );
				}
				
				if ( $values = $form->values() )
				{
					$form->saveAsSettings( array(
						'googlemaps'						=> $data['googlemaps'],
						'googleplacesautocomplete'		=> $data['googleplacesautocomplete'],
						'google_maps_static_use_embed'  => $data['google_maps_static_use_embed'],
						'google_maps_api_key'			=> $values['google_maps_api_key'],
						'google_maps_api_key_secret'		=> $data['googlemaps'] ? $values['google_maps_api_key_secret'] : '',
						'google_maps_groups'			=> ( $data['google_maps_groups'] == '*' ? '*' : implode( ",", $data['google_maps_groups'] ) ),
						'google_maps_zoom'				=> ( $data['google_maps_zoom'] > -1 ? $data['google_maps_zoom'] : null )
					) );
					Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_GoogleMaps' => TRUE ) );
					Output::i()->redirect( Url::internal('app=core&module=applications&controller=enhancements'), 'saved' );
				}
				
				return (string) $form;
			},
		), Url::internal('app=core&module=applications&controller=enhancements&do=edit&id=core_GoogleMaps') );
		
		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/googlemaps' ),
				'target'	=> '_blank'
			),
		);
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GoogleMaps', $wizard );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 */
	public function toggle( bool $enabled ) : void
	{
		/* If we're disabling, just disable */
		if( !$enabled )
		{
			Settings::i()->changeValues( array( 'googlemaps' => 0, 'googleplacesautocomplete' => 0 ) );
		}

		/* Otherwise if we already have an API key, just toggle on */
		if( $enabled && Settings::i()->google_maps_api_key )
		{
			Settings::i()->changeValues( array( 'googlemaps' => 1, 'googleplacesautocomplete' => 1, 'mapbox' => 0 ) );
		}
		else
		{
			/* Otherwise we need to let them enter an API key before we can enable.  Throwing an exception causes you to be redirected to the settings page. */
			throw new DomainException;
		}
	}
}