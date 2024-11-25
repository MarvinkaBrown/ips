<?php
/**
 * @brief		Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\FormsAbstract;
use IPS\File;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\SocialGroup;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang\Setup\Lang;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use Throwable;
use UnderflowException;
use function count;
use function defined;
use function func_get_args;
use function in_array;
use function is_array;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Form Builder
 */
class Form
{
	/* Used by the Forms Extensions */
	const FORM_REGISTRATION = 'registration';
	const FORM_CHECKOUT = 'checkout';

	/**
	 * @brief	Form ID
	 */
	public ?string $id = '';
	
	/**
	 * @brief	Action URL
	 */
	public string|Url $action = '';
	
	/**
	 * @brief	Input Elements HTML
	 */
	public array $elements = array();

	/**
	 * @brief	Hidden Fields
	 */
	public array $hiddenFields = array();
	
	/**
	 * @brief	Tabs
	 */
	protected array $tabs = array();
	
	/**
	 * @brief	Current Tab we're adding elements to
	 */
	protected string $currentTab = '';
	
	/**
	 * @brief	Active tab
	 */
	public ?string $activeTab = NULL;
	
	/**
	 * @brief	Additional class for tables
	 */
	protected array $tabClasses = array();
		
	/**
	 * @brief	Sidebar
	 */
	public array $sidebar = array();
	
	/**
	 * @brief	CSS Class(es)
	 */
	public string $class = 'ipsForm--default ';
	
	/**
	 * @brief	Generic Form Error
	 */
	public string $error = '';
	
	/**
	 * @brief	Hidden Values
	 */
	public array $hiddenValues = array();
	
	/**
	 * @brief	Extra attributes for `<form>` tag
	 */
	public array $attributes = array();
	
	/**
	 * @brief	Action Buttons
	 */
	public array $actionButtons = array();
		
	/**
	 * @brief	If form has upload field, the maximum size (Needed to add enctype="multipart/form-data")
	 * @note	Only actually affects no-JS uploads, Plupload does it's own thing
	 */
	protected mixed $uploadField = FALSE;
	
	/**
	 * @brief	If enabled, and this form is submitted in a modal popup window, the next screen will be shown within the modal popup
	 */
	public bool $ajaxOutput = FALSE;
	
	/**
	 * @brief	Is the form using tabs with icons
	 */
	protected bool $iconTabs = FALSE;
	
	/**
	 * @brief	Copy Button URL
	 */
	public mixed $copyButton = NULL;
	
	/**
	 * @brief	Language keys to preload for efficiency
	 */
	protected array $languageKeys = array();
	
	/**
	 * @brief	This form can be reloaded after saving
	 */
	public bool $canSaveAndReload = false;

	/**
	 * @brief	Active filters (currently only used for achievement rules)
	 */
	public array $_activeFilters = [];

	/**
	 * Constructor
	 *
	 * @param string|null $id Form ID
	 * @param string|null $submitLang Language string for submit button
	 * @param Url|null $action Action URL
	 * @param array $attributes Extra attributes for `<form>` tag
	 */
	public function __construct( ?string $id='form', ?string $submitLang='save', Url $action=NULL, array $attributes=array() )
	{
		$this->id = $id;
		$this->action = $action ?: Request::i()->url()->stripQueryString( array( 'csrfKey', 'ajaxValidate' ) );
		
		$this->attributes = $attributes;
		
		if( $submitLang )
		{
			$this->actionButtons[] = Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( $submitLang, 'submit', null, 'ipsButton ipsButton--primary', array( 'tabindex' => '2', 'accesskey' => 's' ) );
		}
		
		$this->hiddenValues['csrfKey'] = Session::i()->csrfKey;
		
		$potentialMaxUploadValues	= array();
		if( (float) ini_get('upload_max_filesize') > 0 )
		{
			$potentialMaxUploadValues[]	= File::returnBytes( ini_get('upload_max_filesize') );
		}
		if( (float) ini_get('post_max_size') > 0 )
		{
			/* We need to reduce post_max_size lower because it includes the ENTIRE post and other data, such as the number of chunks, will also be sent with the request */
			$potentialMaxUploadValues[]	= File::returnBytes( ini_get('post_max_size') ) - 1048576;
		}
		if( (float) ini_get('memory_limit') > 0 )
		{
			$potentialMaxUploadValues[]	= File::returnBytes( ini_get('memory_limit') );
		}
		$this->uploadField = min( $potentialMaxUploadValues );
		
		/* This can be overridden in userland code, but by default takes the value sent by \IPS\Node\Controller::_afterSave() */
		if ( isset( Request::i()->activeTab ) )
		{
			$this->activeTab = Request::i()->activeTab;
		}
	}

	/**
	 * Returns all form types available for extension
	 *
	 * @return array
	 */
	public static function availableForExtension() : array
	{
		return [
			static::FORM_REGISTRATION,
			static::FORM_CHECKOUT
		];
	}

	/**
	 * Check application extensions for custom fields
	 *
	 * @param string $formType
	 * @param array|null $params
	 * @return void
	 */
	public function addExtensionFields( string $formType, ?array $params=null ) : void
	{
		/* Validation check */
		if( !in_array( $formType, static::availableForExtension() ) )
		{
			return;
		}

		$params = is_array( $params ) ? array_values( $params ) : array();
		foreach( Application::allExtensions( 'core', 'Forms' ) as $ext )
		{
			/* @var FormsAbstract $ext */
			if( $ext::formType() == $formType )
			{
				foreach( $ext->formElements( ...$params ) as $element )
				{
					$this->add( $element );
				}
			}
		}
	}

	/**
	 * Save any custom fields added by extensions
	 *
	 * @param string $formType
	 * @param array	$values
	 * @param array|null $params
	 * @return void
	 */
	public static function saveExtensionFields( string $formType, array $values, ?array $params = null ) : void
	{
		/* Validation check */
		if( !in_array( $formType, static::availableForExtension() ) )
		{
			return;
		}

		$params = is_array( $params ) ? array_values( $params ) : array();
		foreach( Application::allExtensions( 'core', 'Forms' ) as $ext )
		{
			/* @var FormsAbstract $ext */
			if( $ext::formType() == $formType )
			{
				$ext->processFormValues( $values, ...$params );
			}
		}
	}
	
	/**
	 * Add Tab
	 *
	 * @param string $lang		Language key
	 * @param string|null $icon		Icon to use
	 * @param string|null $blurbLang	Language to use for the blurb
	 * @param string|null $css		CSS class to use for the tab
	 * @return	void
	 */
	public function addTab( string $lang, string $icon=NULL, string $blurbLang=NULL, string $css=NULL ) : void
	{
		$this->tabs[$lang]['title'] = $lang;
		$this->currentTab = $lang;
		if ( $this->activeTab === NULL )
		{
			$this->activeTab = $lang;
		}
		
		if ( $icon )
		{
			$this->tabs[$lang]['icon'] = $icon;
			$this->iconTabs = TRUE;
		}
		
		if ( $blurbLang )
		{
			$this->elements[ $this->currentTab ][] = Theme::i()->getTemplate( 'forms', 'core' )->blurb( $blurbLang, true, true );
		}
		
		if ( $css )
		{
			$this->tabClasses[ $this->currentTab ] = $css;
		}
	}
	
	/**
	 * Add Header
	 *
	 * @param string $lang		Language key
	 * @param string|null $after		The key of element to insert after
	 * @param string|null $tab		The tab to insert onto
	 * @return	void
	 */
	public function addHeader( string $lang, string $after=NULL, string $tab=NULL ) : void
	{
		/* Place the input into the correct position */
		$this->_insert( Theme::i()->getTemplate( 'forms', 'core' )->header( $lang, "{$this->id}_header_{$lang}" ), NULL, $tab, $after );
	}

	/**
	 * Add Seperator
	 *
	 * @param string|null $after		The key of element to insert after
	 * @param string|null $tab		The tab to insert onto
	 * @return	void
	 */
	public function addSeparator( string $after=NULL, string $tab=NULL ) : void
	{
		/* Place the input into the correct position */
		$this->_insert( Theme::i()->getTemplate( 'forms', 'core', 'front' )->seperator(), NULL, $tab, $after );
	}

	/**
	 * Add Message Row
	 *
	 * @param string $lang		Language key or formatted string to display
	 * @param string|null $css		Custom CSS class(es) to apply
	 * @param bool $parse		Set this to false if the language string passed is already formatted for display
	 * @param string|null $_id		HTML ID
	 * @param string|null $after		The key of element to insert after
	 * @param string|null $tab		The tab to insert onto
	 * @return	void
	 */
	public function addMessage( string $lang, ?string $css='', bool $parse=TRUE, string $_id=NULL, string $after=NULL, string $tab=NULL ) : void
	{
		if ( !$_id )
		{
			$_id	= "{$this->id}_header_" . md5( $lang );
			if( $parse === FALSE )
			{
				$_id	= preg_replace( "/[^a-zA-Z0-9_]/", '_', $_id );
			}
		}

		/* Place the input into the correct position */
		$this->_insert( Theme::i()->getTemplate( 'forms', 'core', 'global' )->message( $lang, $_id, ( $css ?? '' ), $parse ), NULL, $tab, $after );
	}
	
	/**
	 * Add Html
	 *
	 * @param string $html	HTML to add
	 * @param string|null $after	The key of element to insert after
	 * @param string|null $tab	The tab to insert onto
	 * @return	void
	 */
	public function addHtml( string $html, string $after=NULL, string $tab=NULL ) : void
	{
		/* Place the input into the correct position */
		$this->_insert( $html, NULL, $tab, $after );
	}
	
	/**
	 * Add Sidebar
	 *
	 * @param string $contents	Contents
	 * @return	void
	 */
	public function addSidebar( string $contents ) : void
	{
		$this->sidebar[ $this->currentTab ] = $contents;
	}
	
	/**
	 * Add Matrix
	 *
	 * @param	mixed						$name	Name to identify matrix
	 * @param Matrix $matrix	The Matrix
	 * @param string|null $after	The key of element to insert after
	 * @param string|null $tab	The tab to insert onto
	 * @return	void
	 */
	public function addMatrix( mixed $name, Matrix $matrix, string $after=NULL, string $tab=NULL ) : void
	{
		$matrix->formId = $this->id;
		$this->tabClasses[ $this->currentTab ] = 'ipsForm--matrix';

		/* Place the input into the correct position */
		$this->_insert( $matrix, $name, $tab, $after );
	}
	
	/**
	 * Add Button
	 *
	 * @param string $lang	Language key
	 * @param string $type	'link', 'button' or 'submit'
	 * @param string|null $href	If type is 'link', the target
	 * @param string $class 	CSS class(es) to applys
	 * @param array $attributes Attributes to apply
	 * @return	void
	 */
	public function addButton( string $lang, string $type, string $href=NULL, string $class='', array $attributes=array() ) : void
	{
		$this->actionButtons[] = ' ' . Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( $lang, $type, $href, $class, $attributes );
	}
	
	/**
	 * Add Dummy Row
	 *
	 * @param string $langKey	Language key
	 * @param string $value		Value
	 * @param string $desc		Field description
	 * @param string $warning	Field warning
	 * @param string $id			Element ID
	 * @param string|null $after		The key of element to insert after
	 * @param string|null $tab		The tab to insert onto
	 * @return	void
	 */
	public function addDummy( string $langKey, string $value, string $desc='', string $warning='', string $id='', string $after=NULL, string $tab=NULL ) : void
	{
		/* Place the input into the correct position */
		$this->_insert( Theme::i()->getTemplate( 'forms', 'core' )->row( Member::loggedIn()->language()->addToStack( $langKey ), $value, $desc, $warning, FALSE, NULL, NULL, NULL, $id ), NULL, $tab, $after );
	}

	/**
	 * Add Input
	 *
	 * @param mixed $input	Form element to add
	 * @param string|null $after	The key of element to insert after
	 * @param string|null $tab	The tab to insert onto
	 * @return	void
	 */
	public function add( mixed $input, string $after=NULL, string $tab=NULL ) : void
	{
		/* Check if we have custom positioning */
		/* @var FormAbstract $input */
		$after = $after ?: $input->afterElement;
		$tab = $tab ?: $input->tab;

		/* Place the input into the correct position */
		$this->_insert( $input, $input->name, $tab, $after );
		
		/* If it's a captcha field, we need to add a hidden value */
		if ( $input instanceof Form\Captcha )
		{
			$this->hiddenValues[ $input->name ] = TRUE;
		}
		
		$preloadTypes = array( 'CheckboxSet', 'Radio' );
		
		/* Some form fields check for _desc and _warning so preload these */
		foreach( $preloadTypes as $type )
		{
			$class = 'IPS\Helpers\Form\\' . $type;
			
			if ( is_a( $input, $class ) )
			{
				$this->languageKeys[] = $input->name . '_desc';
				$this->languageKeys[] = $input->name . '_warning';
				
				if ( isset( $input->options['options'] ) and count( $input->options['options'] ) )
				{
					$this->languageKeys = array_merge( $this->languageKeys, array_map(
							function ($v )
							{	
								return $v . '_desc';
							},
							array_values( $input->options['options'] )
						)
					);
					$this->languageKeys = array_merge( $this->languageKeys, array_map(
							function ($v )
							{	
								return $v . '_warning';
							},
							array_values( $input->options['options'] )
						)
					);
				}
			}
		}
	}

	/**
	 * Actually place the element in the correct position
	 *
	 * @param	mixed			$element	Thing we are adding (could be a form input, message, etc.)
	 * @param string|null $elementKey	The key of the element
	 * @param string|null $tab		The tab to insert this thing into
	 * @param string|null $after		The key of the element we want to insert this thing after
	 * @return	void
	 */
	protected function _insert( mixed $element, string $elementKey=NULL, string $tab=NULL, string $after=NULL ) : void
	{
		$tab = $tab ?: $this->currentTab;
		
		if ( $after )
		{
			$elements = array();
			foreach ( $this->elements[ $tab ] as $key => $_element )
			{
				$elements[ $key ] = $_element;
				if ( $key === $after )
				{
					$elements[ $elementKey ] = $element;
				}
			}
			$this->elements[ $tab ] = $elements;
		}
		elseif( $elementKey )
		{
			$this->elements[ $tab ][ $elementKey ] = $element;
		}
		else
		{
			$this->elements[ $tab ][] = $element;
		}
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		/* Preload languages */
		if ( count( $this->languageKeys ) and !( Member::loggedIn()->language() instanceof Lang ) )
		{
			try
			{
				Member::loggedIn()->language()->get( $this->languageKeys );
			}
			catch( UnderflowException $e ) { }
		}
		
		try
		{
			$html = array();
			$errorTabs = array();
			foreach ( $this->elements as $tab => $elements )
			{
				$html[ $tab ] = '';
				foreach ( $elements as $k => $element )
				{
					if ( $element instanceof Form\Matrix )
					{
						$html[ $tab ] .= Theme::i()->getTemplate( 'forms', 'core' )->emptyRow( $element->nested(), $k );
						continue;
					}
					if ( !is_string( $element ) and $element->error )
					{
						$errorTabs[] = $tab;
					}
					$html[ $tab ] .= ( $element instanceof FormAbstract ) ? $element->rowHtml( $this ) : (string) $element;
				}
			}
			
			if ( $this->canSaveAndReload )
			{
				$this->addButton( 'save_and_reload', 'submit', null, 'ipsButton ipsButton--primary', array( 'name' => 'save_and_reload', 'value' => 1 ) );
			}
			
			return Theme::i()->getTemplate( 'forms', 'core' )->template( $this->id, $this->action, $html, $this->activeTab, $this->error, $errorTabs, $this->hiddenValues, $this->actionButtons, $this->uploadField, $this->sidebar, $this->tabClasses, $this->class, $this->attributes, $this->tabs, $this->iconTabs );
		}
		catch ( Exception | Throwable $e )
		{
			IPS::exceptionHandler( $e );
		}

		return '';
	}
	
	/**
	 * Get HTML using custom template
	 *
	 * @param callback $template	The template to use
	 * @return	string
	 */
	public function customTemplate( callable $template ): string
	{
		$args = func_get_args();
		
		if ( count( $args ) > 1 )
		{
			array_shift( $args );
		}
		else
		{
			$args = array();
		}
		
		/* Preload languages */
		if ( count( $this->languageKeys ) )
		{
			try
			{
				Member::loggedIn()->language()->get( $this->languageKeys );
			}
			catch( UnderflowException $e ) { }
		}

		$errorTabs = array();
		foreach ( $this->elements as $tab => $elements )
		{
			foreach ( $elements as $k => $element )
			{
				if ( !( $element instanceof Form\Matrix ) and !is_string( $element ) and $element->error )
				{
					$errorTabs[] = $tab;
				}
			}
		}

		$templateArguments = array_merge( $args, array( $this->id, $this->action, $this->elements, $this->hiddenValues, $this->actionButtons, $this->uploadField, $this->class, $this->attributes, $this->sidebar, $this, $errorTabs ) );
		return $template( ...$templateArguments );
	}
	
	/**
	 * Return the last used tab in the current form
	 *
	 * @return string
	 */
	public function getLastUsedTab(): string
	{
		$name = "{$this->id}_activeTab";
		if ( isset( Request::i()->$name ) )
		{
			return Request::i()->$name;
		}
		
		return '';
	}
	
	/**
	 * Get submitted values
	 *
	 * @param bool $stringValues	If true, all values will be returned as strings
	 * @return	array|FALSE		Array of field values or FALSE if the form has not been submitted or if there were validation errors
	 */
	public function values( bool $stringValues=FALSE ): array|FALSE
	{
		$values = array();
		$name = "{$this->id}_submitted";
		$uploadFieldNames = array();
		$uploadRetainDeleted = array();
		$uploadCurrentFiles = array();
		
		/* Did we submit the form? */
		if( isset( Request::i()->$name ) and Login::compareHashes( Session::i()->csrfKey, (string) Request::i()->csrfKey ) )
		{
			/* Work out which fields are being toggled by other fields */
			$htmlIdsToIgnoreBecauseTheyAreHiddenByToggles = array();
			$htmlIdsWeWantBecauseTheyAreActivatedByToggles = array();
			foreach ( $this->elements as $elements )
			{
				foreach ( $elements as $_name => $element )
				{
					if ( isset( $element->options['togglesOn'] ) )
					{
						if ( !$element->value )
						{
							$htmlIdsToIgnoreBecauseTheyAreHiddenByToggles = array_merge( $htmlIdsToIgnoreBecauseTheyAreHiddenByToggles, $element->options['togglesOn'] );
						}
						else
						{
							$htmlIdsWeWantBecauseTheyAreActivatedByToggles = array_merge( $htmlIdsWeWantBecauseTheyAreActivatedByToggles, $element->options['togglesOn'] );
						}
					}
					if ( isset( $element->options['togglesOff'] ) )
					{
						if ( $element->value )
						{
							$htmlIdsToIgnoreBecauseTheyAreHiddenByToggles = array_merge( $htmlIdsToIgnoreBecauseTheyAreHiddenByToggles, $element->options['togglesOff'] );
						}
						else
						{
							$htmlIdsWeWantBecauseTheyAreActivatedByToggles = array_merge( $htmlIdsWeWantBecauseTheyAreActivatedByToggles, $element->options['togglesOff'] );
						}
					}
					if ( isset( $element->options['zeroValTogglesOff'] ) )
					{
						if ( $element->value === 0 )
						{
							$htmlIdsToIgnoreBecauseTheyAreHiddenByToggles = array_merge( $htmlIdsToIgnoreBecauseTheyAreHiddenByToggles, $element->options['zeroValTogglesOff'] );
						}
						else
						{
							$htmlIdsWeWantBecauseTheyAreActivatedByToggles = array_merge( $htmlIdsWeWantBecauseTheyAreActivatedByToggles, $element->options['zeroValTogglesOff'] );
						}
					}
					if ( isset( $element->options['toggles'] ) )
					{
						foreach ( $element->options['toggles'] as $toggleValue => $toggleHtmlIds )
						{
							if ( $element instanceof CheckboxSet and $element->value === '*' )
							{
								$match = true;
							}
							else
							{
								$match = is_array( $element->value ) ? in_array( $toggleValue, $element->value ) : $toggleValue == $element->value;
							}

							if ( !$match )
							{
								$htmlIdsToIgnoreBecauseTheyAreHiddenByToggles = array_merge( $htmlIdsToIgnoreBecauseTheyAreHiddenByToggles, $toggleHtmlIds );
							}
							else
							{
								$htmlIdsWeWantBecauseTheyAreActivatedByToggles = array_merge( $htmlIdsWeWantBecauseTheyAreActivatedByToggles, $toggleHtmlIds );
							}
						}
					}
				}
			}

			$htmlIdsToIgnore = array_diff( array_unique( $htmlIdsToIgnoreBecauseTheyAreHiddenByToggles ), array_unique( $htmlIdsWeWantBecauseTheyAreActivatedByToggles ) );
			
			/* Loop elements */
			foreach ( $this->elements as $elements )
			{
				foreach ( $elements as $_name => $element )
				{
					/* If it's a matrix, populate the values from it */
					if ( ( $element instanceof Form\Matrix ) )
					{
						$values[ $_name ] = $element->values( TRUE );
						continue;
					}
					
					/* If it's not a form element, skip */
					if ( !( $element instanceof Form\FormAbstract ) )
					{
						continue;
					}
					
					/* If this is dependant on a toggle which isn't set, don't return a value so that it doesn't
						trigger an error we cannot see */
					if ( $element->htmlId and in_array( $element->htmlId, $htmlIdsToIgnore ) )
					{ 
						$values[ $_name ] = $stringValues ? $element::stringValue( $element->defaultValue ) : $element->defaultValue;
						continue;
					}
										
					/* Make sure we have a value (someone might try to be sneaky and remove the HTML from the form before submitting) */
					if ( !$element->valueSet )
					{
						$element->setValue( FALSE, TRUE );
					}
					
					/* If it's an upload field, we'll need to remember the name */
					if ( ( $element instanceof Form\Upload ) )
					{
						$uploadFieldNames[] = $element->name;
						if ( $element->options['retainDeleted'] )
						{
							$uploadRetainDeleted[] = $element->name;
							if ( is_array( $element->value ) )
							{
								foreach( $element->value AS $value )
								{
									$uploadCurrentFiles[] = $value->originalFilename;
								}
							}
							elseif( $element->value !== NULL )
							{
								$uploadCurrentFiles[] = $element->value->originalFilename;
							}
						}
					}

					/* If it has an error, set it and return */
					if( !empty( $element->error ) )
					{
						Output::i()->httpHeaders['X-IPS-FormError'] = "true";
						return FALSE;
					}

					/* If it's a poll, save it */
					if ( $element instanceof Form\Poll and $element->value !== NULL )
					{
						$element->value->save();
					}

					/* If it's a social group, save it */
					if ( $element instanceof SocialGroup )
					{
						$element->saveValue();
					}

					/* If the element has requested the form doesn't submit, return */
					if ( $element->reloadForm === TRUE )
					{
						Output::i()->httpHeaders['X-IPS-FormNoSubmit'] = "true";
						return FALSE;
					}
					
					/* Still here? Then add the value */
					$values[ $element->name ] = $stringValues ? $element::stringValue( $element->value ) : $element->value;
				}
			}

			foreach ( $this->hiddenValues as $key => $value )
			{
				if( $key != 'csrfKey' )
				{
					$values[$key] = $value;
				}
			}

			/* If we've reached this point, all fields have acceptable values. If we're just checking that, return that it's okay */
			if ( Request::i()->isAjax() and Request::i()->ajaxValidate )
			{
				if ( $this->ajaxOutput === TRUE )
				{
					Output::i()->httpHeaders['X-IPS-FormNoSubmit'] = "true";
				}
				else
				{
					Output::i()->json( array( 'validate' => true ) );
				}
			}
			
			/* At this point we are about to return the values. Any uploaded files are now the responsibility of the controller, so release the hold on them */
			foreach ( $uploadFieldNames as $name )
			{
				if ( !in_array( $name, $uploadRetainDeleted ) )
				{
					Db::i()->delete( 'core_files_temp', array( 'upload_key=?', md5( $name . session_id() ) ) );
				}
				else
				{
					/* If we're retaining deleted files, then remove any files that are actually are a part of the value */
					Db::i()->delete( 'core_files_temp', array( "upload_key=? AND " . Db::i()->in( 'filename', $uploadCurrentFiles ), md5( $name . session_id() ) ) );
				}
			}
			
			/* And return */
			return $values;
		}
		/* Nope, return FALSE */
		else
		{
			return FALSE;
		}
	}

	/**
	 * Save values to settings table
	 *
	 * @param array|null $values		Form Values
	 * @return bool
	 */
	public function saveAsSettings( array $values=NULL ): bool
	{
		if ( !$values )
		{
			$values = $this->values(TRUE);
		}
		
		if ( $values )
		{
			Settings::i()->changeValues( $values );
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Flood Check
	 *
	 * @return	void
	 */
	public static function floodCheck() : void
	{
		if ( Settings::i()->flood_control and !Member::loggedIn()->group['g_avoid_flood'] )
		{
			if ( time() - Member::loggedIn()->member_last_post < Settings::i()->flood_control )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack('error_flood_control', FALSE, array( 'sprintf' => array( Settings::i()->flood_control - ( time() - Member::loggedIn()->member_last_post ) ) ) ) );
			}
		}
	}
}