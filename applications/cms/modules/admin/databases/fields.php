<?php
/**
 * @brief		Fields Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		31 March 2014
 */

namespace IPS\cms\modules\admin\databases;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\cms\Fields as FieldsClass;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * fields
 */
class fields extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\cms\Fields';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		$this->url = $this->url->setQueryString( array( 'database_id' => Request::i()->database_id ) );
		
		$this->nodeClass = '\IPS\cms\Fields' . Request::i()->database_id;

		Dispatcher::i()->checkAcpPermission( 'databases_use' );
		Dispatcher::i()->checkAcpPermission( 'cms_fields_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* If we lose the database id because of a log in, do something more useful than an uncaught exception */
		if ( ! isset( Request::i()->database_id ) )
		{
			Output::i()->redirect( Url::internal( "app=cms&module=databases" ) );
		}
		
		parent::manage();
		
		$url = Url::internal( "app=cms&module=databases&controller=fields&database_id=" . Request::i()->database_id  );

		/* @var FieldsClass $class */
		$class = '\IPS\cms\Fields' . Request::i()->database_id;
		
		/* Build fixed fields */
		$fixed	= array_merge( array( 'record_publish_date' => array(), 'record_expiry_date' => array(), 'record_allow_comments' => array(), 'record_comment_cutoff' => array(), 'record_image' => array() ), $class::fixedFieldPermissions() );

		/* Fixed fields */
		$fixedFields = new Tree(
			$url,
			Member::loggedIn()->language()->addToStack('content_fields_fixed_title'),
			function() use ( $fixed, $url )
			{
				$rows = array();
				
				foreach( $fixed as $field => $data )
				{
					$description = ( $field === 'record_publish_date' ) ? Member::loggedIn()->language()->addToStack( 'content_fields_fixed_record_publish_date_desc' ) : NULL;
					$rows[ $field ] = Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $field, Member::loggedIn()->language()->addToStack( 'content_fields_fixed_'. $field ), FALSE, array(
						'permission'	=> array(
							'icon'		=> 'lock',
							'title'		=> 'permissions',
							'link'		=> $url->setQueryString( array( 'field' => $field, 'do' => 'fixedPermissions' ) ),
							'data'      => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'content_fields_fixed_'. $field ) )
						)
					), $description, NULL, NULL, NULL, !empty($data['visible']));
				}
				
				return $rows;
			},
			function( $key, $root=FALSE ) use ( $fixed, $url ) {},
			function() { return 0; },
			function() { return array(); },
			function() { return array(); },
			FALSE,
			TRUE,
			TRUE
		);

		Output::i()->output .= Theme::i()->getTemplate( 'databases' )->fieldsWrapper( $fixedFields );

		Output::i()->title = Member::loggedIn()->language()->addToStack('content_database_field_area', FALSE, array( 'sprintf' => array( Databases::load( Request::i()->database_id)->_title ) ) );
	}
	
	/**
	 * Get Root Rows
	 *
	 * @return	array
	 */
	public function _getRoots(): array
	{
		/* @var FieldsClass $nodeClass */
		$nodeClass = $this->nodeClass;
		$rows = array();
		
		foreach( $nodeClass::roots( NULL ) as $node )
		{
			if ( $node->database_id == Request::i()->database_id )
			{
				$rows[ $node->_id ] = $this->_getRow( $node );
			}
		}

		return $rows;
	}

	/**
	 * Fixed field permissions
	 *
	 * @return void
	 */
	public function enableToggle() : void
	{
		Session::i()->csrfCheck();

		/* @var FieldsClass $class */
		$class = '\IPS\cms\Fields' . Request::i()->database_id;
		
		$class::setFixedFieldVisibility( Request::i()->id, (boolean) Request::i()->status );
		
		/* Redirect */
		if ( Request::i()->status )
		{
			Output::i()->redirect( Url::internal( "app=cms&module=databases&controller=fields&database_id=" . Request::i()->database_id . '&do=fixedPermissions&field=' . Request::i()->id ) );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=cms&module=databases&controller=fields&database_id=" . Request::i()->database_id ), 'saved' );
		}
	}

	/**
	 * Set this field as the record title
	 *
	 * @return void
	 */
	public function setAsTitle() : void
	{
		Session::i()->csrfCheck();

		/* @var FieldsClass $class */
		$class    = '\IPS\cms\Fields' . Request::i()->database_id;
		$database = Databases::load( Request::i()->database_id );

		try
		{
			$field = $class::load( Request::i()->id );

			if ( $field->canBeTitleField() )
			{
				$database->field_title = $field->id;
				$database->save();
			}

			Output::i()->redirect( Url::internal( "app=cms&module=databases&controller=fields&database_id=" . Request::i()->database_id ), 'saved' );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'cms_cannot_find_field', '2T255/1', 403, '' );
		}
	}

	/**
	 * Set this field as the record content
	 *
	 * @return void
	 */
	public function setAsContent() : void
	{
		Session::i()->csrfCheck();

		/* @var FieldsClass $class */
		$class    = '\IPS\cms\Fields' . Request::i()->database_id;
		$database = Databases::load( Request::i()->database_id );

		try
		{
			$field = $class::load( Request::i()->id );

			if ( $field->canBeContentField() )
			{
				$database->field_content = $field->id;
				$database->save();
			}

			Output::i()->redirect( Url::internal( "app=cms&module=databases&controller=fields&database_id=" . Request::i()->database_id ), 'saved' );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'cms_cannot_find_field', '2T255/2', 403, '' );
		}
	}

	/**
	 * Fixed field permissions
	 * 
	 * @return void
	 */
	public function fixedPermissions() : void
	{
		/* @var FieldsClass $class */
		$class = '\IPS\cms\Fields' . Request::i()->database_id;
		$perms = $class::fixedFieldPermissions( Request::i()->field );

		$permMap = array( 'view' => 'view', 'edit' => 2, 'add' => 3 );

		foreach( $permMap as $k => $v )
		{
			if ( ! isset( $perms[ 'perm_' . $v ] ) )
			{
				$perms[ 'perm_' . $v ] = NULL;
			}
		}

		/* Build Matrix */
		$matrix = new Matrix;
		$matrix->manageable = FALSE;
		$matrix->langPrefix = 'content_perm_fixed_fields__';
		$matrix->columns = array(
				'label'		=> function( $key, $value, $data )
				{
					return $value;
				},
		);
		foreach ( $permMap as $k => $v )
		{
			$matrix->columns[ $k ] = function( $key, $value, $data ) use ( $perms, $k, $v )
			{
				$groupId = mb_substr( $key, 0, -( 2 + mb_strlen( $k ) ) );
				return new Checkbox( $key, isset( $perms[ "perm_{$v}" ] ) and ( $perms[ "perm_{$v}" ] === '*' or in_array( $groupId, explode( ',', $perms[ "perm_{$v}" ] ) ) ) );
			};
			$matrix->checkAlls[ $k ] = ( $perms[ "perm_{$v}" ] === '*' );
		}
		$matrix->checkAllRows = TRUE;
		
		$rows = array();
		foreach ( Group::groups() as $group )
		{
			$rows[ $group->g_id ] = array(
					'label'	=> $group->name,
					'view'	=> TRUE,
			);
		}
		$matrix->rows = $rows;
		
		/* Handle submissions */
		if ( $values = $matrix->values() )
		{
			$_perms = array();
			$save   = array();
				
			/* Check for "all" checkboxes */
			foreach ( $permMap as $k => $v )
			{
				if ( isset( Request::i()->__all[ $k ] ) )
				{
					$_perms[ $v ] = '*';
				}
				else
				{
					$_perms[ $v ] = array();
				}
			}
				
			/* Loop groups */
			foreach ( $values as $group => $perms )
			{
				foreach ( $permMap as $k => $v )
				{
					if ( isset( $perms[ $k ] ) and $perms[ $k ] and is_array( $_perms[ $v ] ) )
					{
						$_perms[ $v ][] = $group;
					}
				}
			}
				
			/* Finalise */
			foreach ( $_perms as $k => $v )
			{
				$save[ "perm_{$k}" ] = is_array( $v ) ? implode( ',', $v ) : $v;
			}
			
			$class::setFixedFieldPermissions( Request::i()->field, $save );
			
			/* Redirect */
			Output::i()->redirect( Url::internal( "app=cms&module=databases&controller=fields&database_id=" . Request::i()->database_id ), 'saved' );
		}
		
		/* Display */
		Output::i()->output .= $matrix;
		Output::i()->title  = Member::loggedIn()->language()->addToStack('content_database_manage_fields');
	}

	/**
	 * Manage field toggles
	 *
	 * @return void
	 */
	protected function toggles() : void
	{
		/* @var FieldsClass $class */
		$class = '\IPS\cms\Fields' . Request::i()->database_id;
		$database = Databases::load( Request::i()->database_id );
		try
		{
			$field = $class::load( Request::i()->id );
			if( !in_array( $field->type, $class::$canUseTogglesFields ) )
			{
				Output::i()->error( 'This field type cannot use toggles', 'CMSFT/2' );
			}
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '1CMSFT/1', 404 );
		}

		$form = new Form;
		$form->class = 'ipsPad';

		$form->addMessage( 'cms_fields_toggles_info', 'ipsMessage ipsMessage--info' );
		if( $field->type == 'YesNo' OR $field->type == 'Checkbox' )
		{
			$options = array(
				'togglesOn' => Member::loggedIn()->language()->addToStack( 'field_toggles_on' ),
				'togglesOff' => Member::loggedIn()->language()->addToStack( 'field_toggles_off' )
			);
		}
		else
		{
			$options = $field->extra;
		}

		$currentToggles = ( $field->toggles ? json_decode( $field->toggles, true ) : array() );
		foreach( $options as $k => $v )
		{
			$formField = new Node( 'field_field_toggles_' . $k, ( $currentToggles[$k] ?? null ), false, array(
				'class' => $class,
				'multiple' => true,
				'disabledIds' => array( $field->id, $database->field_title, $database->field_content )
			) );
			$formField->label = $v;
			$form->add( $formField );
		}

		if( $values = $form->values() )
		{
			$toggles = array();
			foreach( $options as $k => $v )
			{
				$key = 'field_field_toggles_' . $k;
				if( isset( $values[$key] ) AND is_array( $values[$key] ) AND count( $values[$key] ) )
				{
					$toggles[$k] = array_keys( $values[$key] );
				}
			}

			$field->toggles = json_encode( $toggles );
			$field->save();

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}
}