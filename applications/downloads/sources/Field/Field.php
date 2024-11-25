<?php
/**
 * @brief		Field Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		3 Oct 2013
 */

namespace IPS\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\CustomField;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Field Node
 */
class Field extends CustomField
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'downloads_cfields';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'cf_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[CustomField] Column Map
	 */
	public static array $databaseColumnMap = array(
		'not_null'	=> 'not_null'
	);
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'ccfields';
	
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static string $langKey = 'downloads_field';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'downloads_field_';
	
	/**
	 * @brief	[CustomField] Content Table
	 */
	public static string $contentDatabaseTable = 'downloads_ccontent';
	
	/**
	 * @brief	[CustomField] Set to TRUE if uploads fields are capable of holding the submitted content for moderation
	 */
	public static bool $uploadsCanBeModerated = TRUE;
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'downloads',
		'module'	=> 'downloads',
		'prefix'	=> 'fields_',
	);

	/**
	 * @brief	[CustomField] Editor Options
	 */
	public static array $editorOptions = array( 'app' => 'downloads', 'key' => 'Downloads' );
	
	/**
	 * @brief	[CustomField] FileStorage Extension for Upload fields
	 */
	public static string $uploadStorageExtension = 'downloads_FileField';

	/**
	 * @brief   [CustomField] An array of the 'keys' of the Field types toggles that shouldn't be an option. The main use is for excluding polls
	 */
	public static array $disabledFieldTypes = array( 'Poll' );


	/**
	 * Get topic format
	 *
	 * @return	string|null
	 */
	public function get_topic_format() : string|null
	{
		return $this->format;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		parent::form( $form );


		unset( $form->elements['']['pf_search_type'] );
		unset( $form->elements['']['pf_search_type_on_off'] );
		Member::loggedIn()->language()->words['field_displayoptions'] = Member::loggedIn()->language()->addToStack('pfield_displayoptions');

		$form->add( new Radio( 'downloads_field_location', $this->id ? $this->display_location : 'below', FALSE, array( 'options' => array( 'sidebar' => 'idm_cfield_sidebar', 'below' => 'idm_cfield_below', 'tab' => 'idm_cfield_tab' ) ), NULL, NULL, NULL, 'idm_field_location' ) );

		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
		{
			$form->add( new YesNo( 'downloads_field_paid', $this->id ? $this->paid_field : FALSE, FALSE, array( 'togglesOff' => array( 'idm_cf_topic', 'idm_pf_format', 'form_' . ( $this->id ?? 'new' ) . '_header_category_forums_integration' ) ) ) );
		}

		if ( Application::appIsEnabled( 'forums' ) )
		{
			$form->addHeader('category_forums_integration');
			$form->add( new YesNo( 'cf_topic', $this->topic, FALSE, array(), NULL, NULL, NULL, 'idm_cf_topic' ) );
			$form->add( new TextArea( 'pf_format', $this->id ? $this->topic_format : '', FALSE, array(), NULL, NULL, NULL, 'idm_pf_format' ) );

			Member::loggedIn()->language()->words['pf_format_desc'] = Member::loggedIn()->language()->addToStack('cf_format_desc');
		}
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( Application::appIsEnabled( 'forums' ) AND isset( $values['cf_topic'] ) )
		{
			/* Forcibly disable include in topic option if it is a paid field */
			$values['topic'] = ( isset( $values['downloads_field_paid'] ) AND $values['downloads_field_paid'] ) ? 0 : $values['cf_topic'];
			unset( $values['cf_topic'] );
		}

		$values['allow_attachments']	= $values['pf_allow_attachments'];
		unset( $values['pf_search_type'] );
		unset( $values['pf_allow_attachments'] );

		$values['display_location'] = $values['downloads_field_location'];
		$values['paid_field'] = ( isset( $values['downloads_field_paid'] ) ) ? $values['downloads_field_paid'] : 0;
		unset( $values['downloads_field_location'], $values['downloads_field_paid'] );

		return parent::formatFormValues( $values );
	}
}