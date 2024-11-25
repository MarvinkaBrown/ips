<?php
/**
 * @brief		FTP Details input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Apr 2014
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DomainException;
use IPS\Ftp as FtpClass;
use IPS\Ftp\Exception;
use IPS\Ftp\Sftp as SftpClass;
use IPS\Output;
use IPS\Text\Encrypt;
use IPS\Theme;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * FTP input class for Form Builder
 */
class Ftp extends FormAbstract
{	
	/**
	 * @brief	Default Options
	 * @code
	 		'validate'				=> TRUE,		// Should details be validated?
	 		'allowBypassValidation'	=> TRUE,		// If TRUE, the user will be allowed to use value even if the validation fails
	 		'rejectUnsupportedSftp'	=> FALSE,		// If SFTP deatils are provided, but the server doesn't support it, should validation fail?
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'validate'				=> TRUE,
		'allowBypassValidation'	=> FALSE,
		'rejectUnsupportedSftp'	=> FALSE,
	);
		
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_forms.js', 'nexus', 'global' ) );
		
		$value = is_array( $this->value ) ? $this->value : json_decode( Encrypt::fromTag( $this->value )->decrypt(), TRUE );
		$defaultValue = is_array( $this->defaultValue ) ? $this->defaultValue : json_decode( Encrypt::fromTag( $this->defaultValue )->decrypt(), TRUE );
		if ( isset( $value['pw'] ) and isset( $defaultValue['pw'] ) and $value['pw'] and $value['pw'] === $defaultValue['pw'] and !$this->error )
		{
			$value['pw'] = '********';
		}
		
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->ftp( $this->name, $value, $this->options['allowBypassValidation'] and $this->error );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		$value = parent::getValue();
		
		if ( isset( $value['pw'] ) and $value['pw'] === '********' )
		{
			$defaultValue = is_array( $this->defaultValue ) ? $this->defaultValue : json_decode( Encrypt::fromTag( $this->defaultValue )->decrypt(), TRUE );
			$value['pw'] = $defaultValue['pw'];
		}
		
		return $value;
	}
	
	/** 
	 * Validate
	 *
	 * @param array $value	The value
	 * @return	FtpClass|SftpClass
	 */
	public static function connectFromValue( array $value ): FtpClass|SftpClass
	{
		if ( $value['protocol'] == 'sftp' )
		{
			$ftp = new SftpClass( $value['server'], $value['un'], $value['pw'], $value['port'] );
		}
		else
		{
			$ftp = new FtpClass( $value['server'], $value['un'], $value['pw'], $value['port'], ( $value['protocol'] == 'ssl_ftp' ), 3 );
		}
		
		$ftp->chdir( $value['path'] );
		
		return $ftp;
	}
	
	/** 
	 * Validate
	 *
	 * @return	bool
	 */
	public function validate(): bool
	{
		/* Do we have a value? */
		if ( $this->value['server'] or $this->value['un'] or $this->value['pw'] )
		{
			/* And is it different to what it was originally, or do we need to establish the connection for custom validation? */
			$defaultValue = is_array( $this->defaultValue ) ? $this->defaultValue : json_decode( Encrypt::fromTag( $this->defaultValue )->decrypt(), TRUE );
			if ( !isset( $defaultValue['protocol'] ) or $defaultValue['protocol'] != $this->value['protocol'] or $defaultValue['server'] != $this->value['server'] or $defaultValue['port'] != $this->value['port'] or $defaultValue['un'] != $this->value['un'] or $defaultValue['pw'] != $this->value['pw'] or $defaultValue['path'] != $this->value['path'] or $this->customValidationCode !== NULL )
			{
				/* And are we supposed to be validating? */
				if ( $this->options['validate'] and ( !$this->options['allowBypassValidation'] or !isset( $this->value['bypassValidation'] ) ) )
				{
					/* Do normal validation */
					try
					{
						$ftp = static::connectFromValue( $this->value );
					}
					catch ( Exception $e )
					{
						throw new DomainException( 'ftp_err-' . $e->getMessage() );
					}
					catch ( BadMethodCallException $e )
					{
						// This means we tried an SFTP connection, but the server doesn't support it. We'll have to assume it's correct unless we've specifically set not to
						if ( $this->options['rejectUnsupportedSftp'] )
						{
							throw new DomainException( 'ftp_err_no_sftp' );
						}
					}
				}
			}
			
			/* Do any custom validation */
			if( $this->customValidationCode !== NULL )
			{
				$validationFunction = $this->customValidationCode;
				$validationFunction( $ftp );
			}
		}
		/* If not, should we? */
		elseif ( $this->required )
		{
			throw new DomainException( 'form_required' );
		}

		return true;
	}
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return    string|int|null
	 */
	public static function stringValue( mixed $value ): string|int|null
	{
		return Encrypt::fromPlaintext( json_encode( $value ) )->tag();
	}
}