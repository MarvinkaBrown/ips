<?php
/**
 * @brief		API Key
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Dec 2015
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Theme;
use function defined;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API Key
 */
class Key extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_api_keys';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'api_';
				
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'api_keys';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'applications',
		'prefix' 	=> 'api_',
	);
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'core_api_name_';
	
	/**
	 * Get description
	 *
	 * @return	string|null
	 */
	public function get__description(): ?string
	{
		return Theme::i()->getTemplate('api')->apiKey( $this->id );
	}
	
	/**
	 * Check access
	 *
	 * @param	string	$app		Application key
	 * @param	string	$controller	Controller
	 * @param	string	$method		Method
	 * @return	bool
	 */
	public function canAccess( string $app, string $controller, string $method ) : bool
	{
		$permissions = json_decode( $this->permissions, TRUE );
		return isset( $permissions["{$app}/{$controller}/{$method}"] ) and $permissions["{$app}/{$controller}/{$method}"]['access'] == TRUE;
	}
	
	/**
	 * Should log?
	 *
	 * @param	string	$app		Application key
	 * @param	string	$controller	Controller
	 * @param	string	$method		Method
	 * @return	bool
	 */
	public function shouldLog( string $app, string $controller, string $method ) : bool
	{
		$permissions = json_decode( $this->permissions, TRUE );
		return isset( $permissions["{$app}/{$controller}/{$method}"] ) and isset( $permissions["{$app}/{$controller}/{$method}"]['log'] ) and $permissions["{$app}/{$controller}/{$method}"]['log'] == TRUE;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		/* API Key */
		$form->add( new Custom( 'api_id', $this->id ?: md5( mt_rand() ), TRUE, array(
			'getHtml'	=> function( $field )
			{
				return Theme::i()->getTemplate('api')->apiKeyField( $field->name, $field->value );
			},
			'disableCopy'	=> TRUE
		) ) );
		
		/* Description */
		$form->add( new Translatable( 'api_name', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? "core_api_name_{$this->id}" : NULL ) ) ) );
		
		/* Allowed IPs */
		$form->add( new Radio( 'api_enable_ip_restriction', intval( $this->allowed_ips !== NULL ), FALSE, array(
			'options'	=> array(
				0	=> 'api_enable_ip_restriction_off',
				1	=> 'api_enable_ip_restriction_on',
			),
			'toggles'	=> array(
				1	=> array( 'api_allowed_ips' )
			)
		) ) );
		
		if ( Request::i()->isCgi() )
		{
			Member::loggedIn()->language()->words['api_enable_ip_restriction_warning'] = Member::loggedIn()->language()->addToStack('api_enable_ip_restriction__warning');
		}
		
		$form->add( new Stack( 'api_allowed_ips', explode( ',', $this->allowed_ips ), NULL, array(), function( $val )
		{
			if ( $val )
			{
				foreach ( array_filter( is_array( $val ) ? $val : array( $val ) ) as $ip )
				{
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) === FALSE )
					{
						throw new DomainException( Member::loggedIn()->language()->addToStack( 'api_allowed_ips_err', FALSE, array( 'sprintf' => array( $ip ) ) ) );
					}
				}
			}
		}, NULL, NULL, 'api_allowed_ips' ) );
		
		/* Permissions */
		$form->add( new Custom( 'api_permissions', $this->permissions ? json_decode( $this->permissions, TRUE ) : array(), FALSE, array(
			'rowHtml'	=> function( $field )
			{
				$endpoints = Controller::getAllEndpoints('client');
				$endpointTree = [];
				foreach ( $endpoints as $key => $endpoint )
				{
					$pieces = explode('/', $key);
					$endpointTree[ $pieces[0] ][ $pieces[1] ][ $key ] = $endpoint;
				}
				
				return Theme::i()->getTemplate( 'api' )->permissionsField( $endpointTree, $field->name, $field->value );
			}
		) ) );

	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$values['api_permissions'] = json_encode( $values['api_permissions'] );
		
		if ( !$values['api_enable_ip_restriction'] )
		{
			$values['api_allowed_ips'] = NULL;
		}
		unset( $values['api_enable_ip_restriction'] );
				
		if ( !$this->id )
		{
			$this->id = $values['api_id'];
			$this->save();
		}
		
		Lang::saveCustom( 'core', "core_api_name_{$this->id}", $values['api_name'] );
		unset( $values['api_name'] );
		
		return $values;
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldId = $this->id;

		$this->id = md5( mt_rand() );
		$this->_new = TRUE;
		$this->save();

		/* ...and language */
		Lang::saveCustom( 'core', 'core_api_name_' . $this->id, iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', 'core_api_name_' . $oldId ) )->setKeyField( 'lang_id' )->setValueField( 'word_custom' ) ) );
	}
}