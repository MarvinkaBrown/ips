<?php
/**
 * @brief		advertisements Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		27 Nov 2023
 */

namespace IPS\core\widgets;

use IPS\Application;
use IPS\core\Advertisement;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Node\Model;
use IPS\Widget;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * advertisements Widget
 */
class advertisements extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'advertisements';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
 		$form = parent::configuration( $form );

		 $ads = [];
		 foreach( Db::i()->select( 'ad_id', 'core_advertisements', [ 'ad_type!=?', Advertisement::AD_EMAIL ] ) as $adId )
		 {
			 $ads[$adId] = 'core_advert_' . $adId;
		 }

		 if( !count( $ads ) )
		 {
			 return $form;
		 }

		 $form->add( new Select( 'widget_ads', $this->configuration['widget_ads'] ?? -1, false, [
			 'options' => $ads,
			 'multiple' => true,
			 'noDefault' => true,
			 'unlimited' => -1,
			 'unlimitedLang' => 'any'
		 ] ) );

		 if( Application::appIsEnabled( 'nexus' ) )
		 {
			 /* Do we have any advertising packages configured? */
			 $count = (int) Db::i()->select( 'count(*)', 'nexus_packages', [ 'p_type=?', 'ad' ] )->first();
			 if( $count )
			 {
				 $form->add( new Node( 'widget_ad_package', $this->configuration['widget_ad_package'] ?? null, false, [
					 'class' => 'IPS\nexus\Package',
					 'multiple' => false,
					 'where' => [ 'p_type=?', 'ad' ]
				 ] ) );
			 }
		 }

		 $form->add( new Number( 'widget_ad_limit', $this->configuration['widget_ad_limit'] ?? 1, false ) );

 		return $form;
 	}

	/**
	 * Ran before saving widget configuration
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function preConfig( array $values ): array
	{
		$values['widget_ad_package'] = ( isset( $values['widget_ad_package'] ) and $values['widget_ad_package'] instanceof Model ) ? $values['widget_ad_package']->_id : null;
		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$ids = ( isset( $this->configuration['widget_ads'] ) and $this->configuration['widget_ads'] != -1 ) ? $this->configuration['widget_ads'] : [];

		/* Do we have a specific Commerce package selected? Get those ads */
		if( isset( $this->configuration['widget_ad_package'] ) and Application::appIsEnabled( 'nexus' ) )
		{
			foreach( Db::i()->select( 'ps_extra', 'nexus_purchases', [ 'ps_app=? and ps_type=? and ps_item_id=? and ps_active=?', 'nexus', 'package', $this->configuration['widget_ad_package'], 1 ] ) as $data )
			{
				if( $extra = json_decode( $data, true ) )
				{
					$ids[] = $extra['ad'];
				}
			}
		}

		return $this->output( Advertisement::loadForWidget( array(), ( count( $ids ) ? $ids : null ), ( $this->configuration['widget_ad_limit'] ?? null ) ) );
	}
}