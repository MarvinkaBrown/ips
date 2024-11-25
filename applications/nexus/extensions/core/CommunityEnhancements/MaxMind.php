<?php
/**
 * @brief		Community Enhancements: MaxMind
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		07 Mar 2014
 */

namespace IPS\nexus\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use LogicException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class MaxMind extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= FALSE;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "maxmind.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = (bool) Settings::i()->maxmind_key;
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;
		$form->add( new YesNo( 'maxmind_enable', (bool) Settings::i()->maxmind_key, FALSE, array( 'togglesOn' => array( 'maxmind_key', 'maxmind_gateways', 'maxmind_error' ) ) ) );
		$form->add( new Text( 'maxmind_id', Settings::i()->maxmind_id, FALSE, array(), NULL, NULL, NULL, 'maxmind_id' ) );
		$form->add( new Text( 'maxmind_key', Settings::i()->maxmind_key, FALSE, array(), NULL, NULL, NULL, 'maxmind_key' ) );
		$form->add( new Node( 'maxmind_gateways', ( !Settings::i()->maxmind_gateways or Settings::i()->maxmind_gateways === '*' ) ? 0 : explode( ',', Settings::i()->maxmind_gateways ), FALSE, array( 'class' => 'IPS\nexus\Gateway', 'multiple' => TRUE, 'zeroVal' => 'all' ), NULL, NULL, NULL, 'maxmind_gateways' ) );
		$form->add( new Radio( 'maxmind_error', Settings::i()->maxmind_error, FALSE, array( 'options' => array( 'okay' => 'maxmind_error_okay', 'hold' => 'maxmind_error_hold' ) ), NULL, NULL, NULL, 'maxmind_error' ) );
		$form->add( new YesNo( 'maxmind_tracking_code', (bool) Settings::i()->maxmind_tracking_code, FALSE, array(), NULL, NULL, NULL, 'maxmind_tracking_code' ) );

		if ( $values = $form->values() )
		{
			try
			{
				if ( $values['maxmind_enable'] )
				{
					unset( $values['maxmind_enable'] );
					$this->testSettings( $values['maxmind_key'], $values['maxmind_id'] );
					$values['maxmind_gateways'] = is_array( $values['maxmind_gateways'] ) ? implode( ',', array_keys( $values['maxmind_gateways'] ) ) : '*';
					$form->saveAsSettings( $values );
				}
				else
				{
					unset( $values['maxmind_enable'] );
					$values['maxmind_key'] = '';
					$values['maxmind_id'] = '';
					$values['maxmind_gateways'] = '*';
					$form->saveAsSettings( $values );
				}

				AdminNotification::remove( 'nexus', 'Maxmind' );
				Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
			}
			catch ( LogicException $e )
			{
				$form->error = $e->getMessage();
			}
		}
		
		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/maxmind' ),
				'target'	=> '_blank'
			),
		);
				
		Output::i()->output = $form;
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	LogicException
	 */
	public function toggle( bool $enabled ) : void
	{
		if ( $enabled )
		{
			throw new LogicException;
		}
		else
		{	
			Settings::i()->changeValues( array( 'maxmind_key' => '', 'maxmind_id' => '' ) );
			AdminNotification::remove( 'nexus', 'Maxmind' );
		}
	}
	
	/**
	 * Test Settings
	 *
	 * @param string|null $key	Key to check
	 * @param string|null $id		ID to check
	 * @return	void
	 * @throws	LogicException
	 */
	protected function testSettings( string $key=NULL, string $id=NULL ) : void
	{
		$testAddress = new GeoLocation;
		$testAddress->addressLines = array( 'Invision Power Services, Inc.', 'PO Box 2365' );
		$testAddress->city = 'Forest';
		$testAddress->region = 'VA';
		$testAddress->country = 'US';
		$testAddress->postalCode = '24551';
		
		$maxMind = new \IPS\nexus\Fraud\MaxMind\Request( FALSE, $key, $id );
		$maxMind->setIpAddress( Request::i()->ipAddress() );
		$maxMind->setBillingAddress( $testAddress );
		$maxMind = $maxMind->request();

		if ( $maxMind->error )
		{
			throw new LogicException( $maxMind->error );
		}
	}
}