<?php
/**
 * @brief		Community Enhancements: SendGrid integration
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 October 2016
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Email\Outgoing\SendGrid as SendGridClass;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use LogicException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancements: SendGrid integration
 */
class SendGrid extends CommunityEnhancementsAbstract
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
	public string $icon	= "sendgrid.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->sendgrid_api_key && Settings::i()->sendgrid_use_for );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;
		$form->add( new Radio( 'sendgrid_use_for', Settings::i()->sendgrid_use_for, TRUE, array(
					'options'	=> array(
										'0'	=> 'sendgrid_donot_use',
										'1'	=> 'sendgrid_bulkmail_use',
										'2'	=> 'sendgrid_all_use'
										),
					'toggles'	=> array(
										'0'	=> array(),
										'1'	=> array( 'sendgrid_api_key', 'sendgrid_click_tracking', 'sendgrid_ip_pool' ),
										'2'	=> array( 'sendgrid_api_key', 'sendgrid_click_tracking', 'sendgrid_ip_pool' ),
										)
				) ) );
		$form->add( new Text( 'sendgrid_api_key', Settings::i()->sendgrid_api_key, FALSE, array(), NULL, NULL, Member::loggedIn()->language()->addToStack('sendgrid_api_key_suffix'), 'sendgrid_api_key' ) );
		$form->add( new YesNo( 'sendgrid_click_tracking', Settings::i()->sendgrid_click_tracking, FALSE, array(), NULL, NULL, NULL, 'sendgrid_click_tracking' ) );
		$form->add( new Text( 'sendgrid_ip_pool', Settings::i()->sendgrid_ip_pool ?: NULL, FALSE, array( 'nullLang' => 'sendgrid_ip_pool_none' ), NULL, NULL, NULL, 'sendgrid_ip_pool' ) );

		if ( $values = $form->values() )
		{
			try
			{
				$this->testSettings( $values );
			}
			catch ( Exception $e )
			{
				Output::i()->error( $e->getMessage(), '2C339/1' );
			}

			if( $values['sendgrid_use_for'] > 0 )
			{
				$values['sparkpost_use_for'] = 0;
			}

			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_SendGrid' => TRUE ) );
			Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
		}
		
		Output::i()->sidebar['actions'] = array(
			'help'		=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/sendgrid' ),
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
	 * @throws	DomainException
	 */
	public function toggle( bool $enabled ) : void
	{
		/* If we're disabling, just disable */
		if( !$enabled )
		{
			Settings::i()->changeValues( array( 'sendgrid_use_for' => 0 ) );
		}

		/* Otherwise if we already have an API key, just toggle bulk mail on */
		if( $enabled && Settings::i()->sendgrid_api_key )
		{
			Settings::i()->changeValues( array( 'sendgrid_use_for' => 1, 'sparkpost_use_for' => 0 ) );
		}
		else
		{
			/* Otherwise we need to let them enter an API key before we can enable.  Throwing an exception causes you to be redirected to the settings page. */
			throw new DomainException;
		}
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array 	$values	Form values
	 * @return	void
	 * @throws	LogicException
	 */
	protected function testSettings( array $values ) : void
	{
		/* If we've disabled, just shut off */
		if( (int) $values['sendgrid_use_for'] === 0 )
		{
			if( Settings::i()->mail_method == 'sendgrid' )
			{
				Settings::i()->changeValues( array( 'mail_method' => 'mail' ) );
			}

			return;
		}

		/* If we enable SendGrid but do not supply an API key, this is a problem */
		if( !$values['sendgrid_api_key'] )
		{
			throw new InvalidArgumentException( "sendgrid_enable_need_details" );
		}

		/* Test SendGrid settings */
		try
		{
			$sendgrid = new SendGridClass( $values['sendgrid_api_key'] );
			$scopes = $sendgrid->scopes();
			
			if ( !in_array( 'mail.send', $scopes['scopes'] ) )
			{
				throw new DomainException( 'sendgrid_bad_scopes' );
			}
		}
		catch ( Exception $e )
		{
			throw new DomainException( 'sendgrid_bad_credentials' );
		}
	}
}