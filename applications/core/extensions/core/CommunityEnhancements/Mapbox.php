<?php
/**
 * @brief		Community Enhancement: Mapbox
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Nov 2017
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select as FormSelect;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
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
 * Community Enhancement: Mapbox
 */
class Mapbox extends CommunityEnhancementsAbstract
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
	public string $icon	= "mapbox.png";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->mapbox_api_key and ( Settings::i()->mapbox ) );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$validation = function( $val ) {
			if ( $val and !Request::i()->mapbox_api_key )
			{
				throw new DomainException('mapbox_api_key_req');
			}
		};
		
		$form = new Form;
		$form->add( new Text( 'mapbox_api_key', Settings::i()->mapbox_api_key, FALSE, array(), function( $val ) {
			if ( $val )
			{			
				/* Check API */
				try
				{
					$location = '-73.961452,40.714224';

					$response = Url::external( "https://api.mapbox.com/geocoding/v5/mapbox.places/{$location}.json" )->setQueryString( array(
						'access_token'		=> $val,
					) )->request()->get()->decodeJson();
				}
				catch ( Exception $e )
				{
					throw new DomainException('mapbox_api_error');
				}

				if ( isset( $response['message'] ) )
				{
					throw new DomainException('mapbox_api_key_invalid');
				}

			}
		} ) );

		$form->add( new YesNo( 'mapbox', Settings::i()->mapbox, FALSE, array( 'togglesOn' => array( 'mapbox_groups', 'mapbox_zoom' ) ), $validation ) );
		$groups = [];
		foreach( Group::groups() as $g )
		{
			$groups[ $g->g_id ] = $g->name;
		}

		$form->add( new FormSelect( 'mapbox_groups', Settings::i()->mapbox_groups == '*' ? '*' : explode( ",", Settings::i()->mapbox_groups ), true, array(
			'options' => $groups,
			'multiple' => true,
			'noDefault' => true,
			'unlimited' => '*',
			'unlimitedLang' => 'mapbox_groups_all'
		), null, null, null, 'mapbox_groups' ) );
		$form->add( new Number( 'mapbox_zoom', Settings::i()->mapbox_zoom ?: -1, false, array(
			'unlimited' => -1,
			'unlimitedLang' => 'mapbox_zoom_auto',
			'decimals' => 0,
			'min' => 1,
			'max' => 22
		), null, null, null, 'mapbox_zoom' ) );

		if ( $values = $form->values() )
		{
			if( $values['mapbox'] > 0 )
			{
				$values['googlemaps'] = 0;
				$values['googleplacesautocomplete'] = 0;
			}

			$values['mapbox_groups'] = ( $values['mapbox_groups'] == '*' ) ? '*' : implode( ",", $values['mapbox_groups'] );
			$values['mapbox_zoom'] = ( $values['mapbox_zoom'] > -1  ) ? $values['mapbox_zoom'] : null;

			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_MapboxMaps' => TRUE ) );
			Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
		}
		
		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/mapboxmaps' ),
				'target'	=> '_blank'
			),
		);
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_MapboxMaps', $form );
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
			Settings::i()->changeValues( array( 'mapbox' => 0 ) );
		}

		/* Otherwise if we already have an API key, just toggle on */
		if( $enabled && Settings::i()->mapbox_api_key )
		{
			Settings::i()->changeValues( array( 'mapbox' => 1, 'googlemaps' => 0, 'googleplacesautocomplete' => 0 ) );
		}
		else
		{
			/* Otherwise we need to let them enter an API key before we can enable.  Throwing an exception causes you to be redirected to the settings page. */
			throw new DomainException;
		}
	}
}