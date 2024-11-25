<?php
/**
 * @brief		Key/Value input class for social profile links
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Feb 2017
 */

namespace IPS\core\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Helpers\Form\KeyValue;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Url;
use IPS\Theme;
use LengthException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Key/Value input class for social profile links
 */
class SocialProfiles extends KeyValue
{
	/**
	 * @brief	Default Options
	 * @see		\IPS\Helpers\Form\Date::$defaultOptions
	 * @code
	 	$defaultOptions = array(
	 		'start'			=> array( ... ),
	 		'end'			=> array( ... ),
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'key'		=> array(
		),
		'value'		=> array(
		),
	);

	/**
	 * @brief	Key Object
	 */
	public mixed $keyField = NULL;
	
	/**
	 * @brief	Value Object
	 */
	public mixed $valueField = NULL;
	
	/**
	 * Constructor
	 * Creates the two date objects
	 *
	 * @param	string $name			Form helper name
	 * @param mixed|null $defaultValue	Default value for the helper
	 * @param bool $required		Helper is required (TRUE) or not (FALSE)
	 * @param array $options		Options for the helper instance
	 * @return	void
	 *@see		\IPS\Helpers\Form\Abstract::__construct
	 */
	public function __construct(string $name, mixed $defaultValue=NULL, bool $required=FALSE, array $options=array() )
	{
		$options = array_merge( $this->defaultOptions, $options );

		$options = $this->addSocialNetworks( $options );

		parent::__construct( $name, $defaultValue, $required, $options );
		
		$this->keyField = new Url( "{$name}[key]", $defaultValue['key'] ?? NULL, FALSE, $options['key'] ?? array() );
		$this->valueField = new Select( "{$name}[value]", $defaultValue['value'] ?? NULL, FALSE, $options['value'] ?? array() );
	}

	/**
	 * Add social networks to the options array
	 *
	 * @note	Abstracted so third parties can extend as needed
	 * @param array $options	Options array
	 * @return	array
	 */
	protected function addSocialNetworks( array $options ): array
	{
		$options['value']['options'] = array(
			'facebook'		=> "siteprofilelink_facebook",
			'youtube'		=> "siteprofilelink_youtube",
			'x'				=> "siteprofilelink_x",
			'tumblr'		=> "siteprofilelink_tumblr",
			'deviantart'	=> "siteprofilelink_deviantart",
			'etsy'			=> "siteprofilelink_etsy",
			'flickr'		=> "siteprofilelink_flickr",
			'foursquare'	=> "siteprofilelink_foursquare",
			'github'		=> "siteprofilelink_github",
			'instagram'		=> "siteprofilelink_instagram",
			'pinterest'		=> "siteprofilelink_pinterest",
			'linkedin'		=> "siteprofilelink_linkedin",
			'slack'			=> "siteprofilelink_slack",
			'xing'			=> "siteprofilelink_xing",
			'weibo'			=> "siteprofilelink_weibo",
			'vk'			=> "siteprofilelink_vk",
			'discord'		=> "siteprofilelink_discord",
			'twitch'		=> "siteprofilelink_twitch",
		);

		return $options;
	}
	
	/**
	 * Format Value
	 *
	 * @return    array
	 */
	public function formatValue(): array
	{
		return array(
			'key'	=> $this->keyField->formatValue(),
			'value'	=> $this->valueField->formatValue()
		);
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		return Theme::i()->getTemplate( 'forms', 'core', 'admin' )->socialProfiles( $this->keyField->html(), $this->valueField->html() );
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @throws	LengthException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		$this->keyField->validate();
		$this->valueField->validate();
		
		if( $this->customValidationCode !== NULL )
		{
			$validationCode = $this->customValidationCode;
			$validationCode( $this->value );
		}

		return TRUE;
	}
}