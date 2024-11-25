<?php
/**
 * @brief		PayPal Pay Out Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		7 Apr 2014
 */

namespace IPS\nexus\Gateway\PayPal;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Text;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Payout as NexusPayout;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use RuntimeException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PayPal Pay Out Gateway
 */
class Payout extends NexusPayout
{
	/**
	 * Extra HTML to display when the admin view the Payout in the ACP
	 *
	 * @return string
	 */
	public function acpHtml() : string
	{
		return Theme::i()->getTemplate( 'payouts', 'nexus' )->PayPal( $this );
	}

	/**
	 * ACP Settings
	 *
	 * @return	array
	 */
	public static function settings() : array
	{
		$settings = json_decode( Settings::i()->nexus_payout, TRUE );
		
		$return = array();
		$return[] = new Text( 'paypal_client_id', ( isset( $settings['PayPal']['client_id'] ) AND $settings['PayPal']['client_id'] ) ? $settings['PayPal']['client_id'] : '', NULL );
		$return[] = new Text( 'paypal_secret', ( isset( $settings['PayPal']['secret'] ) AND $settings['PayPal']['secret'] ) ? $settings['PayPal']['secret'] : '', NULL );
		return $return;
	}
	
	/**
	 * Payout Form
	 *
	 * @return	array
	 */
	public static function form() : array
	{		
		$return = array();
		$return[] = new Email( 'paypal_email', Member::loggedIn()->email, NULL, array(), function( $val )
		{
			if ( !$val and Request::i()->withdraw_method === 'PayPal' )
			{
				throw new DomainException('form_required');
			}
		} );
		return $return;
	}
	
	/**
	 * Get data and validate
	 *
	 * @param	array	$values	Values from form
	 * @return	mixed
	 * @throws	DomainException
	 */
	public function getData( array $values ) : mixed
	{
		return $values['paypal_email'];
	}

	/**
	 * Process the payout
	 * Return the new status for this payout record
	 *
	 * @return	string
	 * @throws	Exception
	 */
	public function process() : string
	{
		static::checkToken();

		$settings = json_decode( Settings::i()->nexus_payout, true );

		$data = array(
			'items' => array(
				array(
					'amount' => array(
						'currency' => $this->amount->currency,
						'value' => $this->amount->amountAsString()
					),
					'receiver' => $this->data,
					'recipient_type' => 'EMAIL'
				)
			),
			'sender_batch_header' => array(
				'recipient_type' => 'EMAIL',
				'sender_batch_id' => "Payout " . DateTime::create()->rfc3339()
			)
		);

		$response = Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api-m.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/payments/payouts' )
			->request()
			->forceTls()
			->setHeaders( array(
				'Content-Type'		=> 'application/json',
				'Authorization'		=> 'Bearer ' . $settings['PayPal']['token']
			) )
			->post( json_encode( $data ) );

		$json = $response->decodeJson();

		if( $response->httpResponseCode != 201 )
		{
			throw new Exception( $json['message'] );
		}

		if( isset( $json['batch_header'] ) AND $json['batch_header']['payout_batch_id'] )
		{
			$this->gw_id = $json['batch_header']['payout_batch_id'];
			switch( $json['batch_header']['batch_status'] )
			{
				case 'DENIED':
				case 'CANCELED':
					return static::STATUS_CANCELED;
					break;
				case 'SUCCESS':
					return static::STATUS_COMPLETE;
					break;
				default:
					return static::STATUS_PROCESSING;
					break;
			}
		}

		/* If we are still here, keep it at the current status */
		return $this->status;
	}
	
	/** 
	 * Mass Process
	 *
	 * @param	ActiveRecordIterator	$payouts	Iterator of payouts to process
	 * @return	void
	 * @throws	\Exception
	 */
	public static function massProcess( ActiveRecordIterator $payouts ) : void
	{
		/* Make sure we check the token first so that we have proper authorization to API calls */
		static::checkToken();

		$settings = json_decode( Settings::i()->nexus_payout, TRUE );

		/* Build a batch of payout items */
		$payoutIds = $payoutData = [];
		foreach( $payouts as $payout )
		{
			$payoutIds[ $payout->amount->currency ] = $payout->id;
			$payoutData[ $payout->amount->currency ][] = [
				'amount' => [
					'currency' => $payout->amount->currency,
					'value' => $payout->amount->amountAsString()
				],
				'receiver' => $payout->data,
				'recipient_type' => 'EMAIL'
			];
		}

		foreach( $payoutData as $currency => $batchData )
		{
			$requestData = [
				'items' => $batchData,
				'sender_batch_header' => [
					'recipient_type' => 'EMAIL',
					'sender_batch_id' => "Payout-{$currency}-" . DateTime::create()->rfc3339()
				]
			];

			$response = Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api-m.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/payments/payouts' )
				->request()
				->forceTls()
				->setHeaders( [
					'Content-Type'		=> 'application/json',
					'Authorization'		=> 'Bearer ' . $settings['PayPal']['token']
				] )
				->post( json_encode( $requestData ) );

			if( $response->httpResponseCode != 201 )
			{
				throw new RuntimeException( (string) $response, $response->httpResponseCode );
			}

			$response = $response->decodeJson();

			if( isset( $response['batch_header'] ) AND $response['batch_header']['payout_batch_id'] )
			{
				$update = [
					'po_gw_id' => $response['batch_header']['payout_batch_id']
				];

				switch( $response['batch_header']['batch_status'] )
				{
					case 'DENIED':
					case 'CANCELED':
						$update['po_status'] = static::STATUS_CANCELED;
						break;
					case 'SUCCESS':
						$update['po_status'] = static::STATUS_COMPLETE;
						break;
					default:
						$update['po_status'] = static::STATUS_PROCESSING;
						break;
				}

				Db::i()->update( 'nexus_payouts', $update, Db::i()->in( 'po_id', $payoutIds[ $currency ] ) );
			}
		}
	}

	/**
	 * @brief   cache batch results for further lookup
	 */
	protected static array $_batchCache = [];

	/**
	 * Check the status of a payout batch and update the withdrawal requests accordingly
	 *
	 * @param string	$batchId
	 * @return string|NULL
	 */
	public static function checkStatus( string $batchId ):? string
	{
		if( isset( static::$_batchCache[ $batchId ] ) )
		{
			return static::$_batchCache[ $batchId ];
		}

		/* Make sure we check the token first so that we have proper authorization to API calls */
		static::checkToken();

		$settings = json_decode( Settings::i()->nexus_payout, TRUE );

		$response = Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api-m.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/payments/payouts/' . $batchId )
			->request()
			->forceTls()
			->setHeaders( array(
				'Content-Type'		=> 'application/json',
				'Authorization'		=> 'Bearer ' . $settings['PayPal']['token']
			) )
			->get()
			->decodeJson();

		if( isset( $response['batch_header'] ) AND $response['batch_header']['payout_batch_id'] )
		{
			switch( $response['batch_header']['batch_status'] )
			{
				case 'DENIED':
				case 'CANCELED':
					return static::$_batchCache[ $batchId ] = static::STATUS_CANCELED;
					break;
				case 'SUCCESS':
					return static::$_batchCache[ $batchId ] = static::STATUS_COMPLETE;
					break;
				default:
					return static::$_batchCache[ $batchId ] = static::STATUS_PROCESSING;
					break;
			}
		}

		return NULL;
	}

	/**
	 * Get Token
	 *
	 * @return	void
	 * @throws	Exception
	 * @throws	UnexpectedValueException
	 */
	protected static function checkToken() : void
	{
		$payoutSettings = json_decode( Settings::i()->nexus_payout, true );
		$settings = $payoutSettings['PayPal'];

		if ( !isset( $settings['token'] ) or $settings['token_expire'] < time() )
		{
			$response = Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api-m.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/oauth2/token' )
				->request()
				->forceTls()
				->setHeaders( array(
					'Accept'			=> 'application/json',
					'Accept-Language'	=> 'en_US',
				) )
				->login( $settings['client_id'], $settings['secret'] )
				->post( array( 'grant_type' => 'client_credentials' ) )
				->decodeJson();

			if ( !isset( $response['access_token'] ) )
			{
				throw new UnexpectedValueException($response['error_description'] ?? $response);
			}

			$settings['token'] = $response['access_token'];
			$settings['token_expire'] = ( time() + $response['expires_in'] );
			$payoutSettings['PayPal'] = $settings;

			Settings::i()->changeValues( array(
				'nexus_payout' => json_encode( $payoutSettings )
			) );
		}
	}
}