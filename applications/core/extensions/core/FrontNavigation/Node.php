<?php
/**
 * @brief		Front Navigation Extension: Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		21 Sep 2023
 */

namespace IPS\core\extensions\core\FrontNavigation;

use IPS\Content;
use IPS\Content\Search\SearchContent;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Node as NodeForm;
use IPS\Node\Model;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use OutOfRangeException;
use function count;
use function defined;
use function is_object;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Node
 */
class Node extends FrontNavigationAbstract
{
	/**
	 * The node object if one exists
	 */
	protected ?Model $node = null;

	/**
	 * Constructor
	 *
	 * @param	array	$configuration	The configuration
	 * @param	int		$id				The ID number
	 * @param	string|null	$permissions	The permissions (* or comma-delimited list of groups)
	 * @param	string	$menuTypes		The menu types (either * or json string)
	 * @param	array|null	$icon			Array of icon data or null
	 * @return	void
	 */
	public function __construct( array $configuration, int $id, string|null $permissions, string $menuTypes, array|null $icon )
	{
		parent::__construct( $configuration, $id, $permissions, $menuTypes, $icon );

		if ( count( $configuration ) and isset( $configuration['nodeClass'] ) and ! empty( $configuration['id'] ) )
		{
			try
			{
				$class = $configuration['nodeClass'];
				$this->node = $class::load( $configuration['id'] );
			}
			catch( OutOfRangeException )
			{

			}
		}
	}

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('frontnavigation_core_node');
	}

	/**
	 * Allow multiple instances?
	 *
	 * @return	bool
	 */
	public static function allowMultiple() : bool
	{
		return true;
	}
	
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return	bool
	 */
	public function canAccessContent(): bool
	{
		if ( $this->node === null )
		{
			return false;
		}

		if ( ! $this->node->canView() )
		{
			return false;
		}

		return true;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return (string) $this->node?->_title;
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		return $this->node?->url();
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active(): bool
	{
		return stristr( (string) Request::i()->url(), (string) $this->node->url() );
	}

	/**
	 * Get configuration fields
	 *
	 * @param	array	$existingConfiguration	The existing configuration, if editing an existing item
	 * @param	int|null		$id						The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function configuration( array $existingConfiguration, ?int $id = NULL ): array
	{
		$fields = array();
		$classes = array();
		$classToggles = array();

		foreach ( Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			if ( ! SearchContent::isSearchable( $class ) or ! isset( $class::$containerNodeClass ) )
			{
				continue;
			}

			$bits = explode( '\\', $class );
			if( method_exists( $class, 'database' ) )
			{
				$label = $class::database()->_title;
			}
			else if ( $bits[1] === 'gallery' )
			{
				$label = Member::loggedIn()->language()->addToStack( '__app_' . $bits[1] ) . ' - ' . Member::loggedIn()->language()->addToStack( $class::$title . '_pl' );
			}
			else
			{
				$label = Member::loggedIn()->language()->addToStack( '__app_' . $bits[1] );
			}

			$classes[ $class ] = $label;
			$classToggles[ $class ][] = 'containers_' . $class::$title;
		}

		/* Add the fields for them */
		$fields[] = new Select( 'menu_node_content_classes', $existingConfiguration['selected'] ?? null, null, array( 'options' => $classes, 'toggles' => $classToggles  ), null, null, null, 'classes' );

		/* Nodes */
		foreach ( $classToggles as $class => $id )
		{
			if ( isset( $class::$containerNodeClass ) )
			{
				$nodeClass = $class::$containerNodeClass;
				$value = 0;

				if ( isset( $existingConfiguration['selected'] ) and ! empty( $existingConfiguration['id'] ) )
				{
					$value = $existingConfiguration['id'];
				}

				/* @var Content\Item $class */
				$field = new NodeForm( 'containers_' . $class::$title, $value, null, array( 'class' => $nodeClass, 'clubs' => true, 'multiple' => false, 'forceOwner' => FALSE, 'subnodes' => FALSE, 'permissionCheck' => 'view' ), NULL, NULL, NULL, 'containers_' . $class::$title );

				if( method_exists( $class, 'database' ) )
				{
					$field->label = $class::database()->_title . ' - ' . Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle );
				}
				else
				{
					$field->label = Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle );
				}

				$field->description = Member::loggedIn()->language()->addToStack( 'menu_node_content_classes_select', NULL, [ 'sprintf' => [ Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle ) ] ] );

				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function parseConfiguration( array $configuration, int $id ): array
	{
		$class = $configuration['menu_node_content_classes'];
		$return = [ 'nodeClass' => $class::$containerNodeClass, 'selected' => $class ];

		foreach( $configuration as $field => $value )
		{
			if ( is_object( $value ) )
			{
				$return['id'] = $value->_id;
			}
		}

		return $return;
	}
}