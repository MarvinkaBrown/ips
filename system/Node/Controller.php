<?php
/**
 * @brief		Node Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Item;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller as DispatcherController;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Node as FormNode;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Tree\Tree;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Node Controller
 */
class Controller extends DispatcherController
{
	/**
	 * Title can contain HTML?
	 */
	public bool $_titleHtml = FALSE;
	
	/**
	 * Description can contain HTML?
	 */
	public bool $_descriptionHtml = FALSE;
	
	/**
	 * @brief	If true, will prevent any item from being moved out of its current parent, only allowing them to be reordered within their current parent
	 */
	protected bool $lockParents = FALSE;
	
	/**
	 * @brief	If true, root cannot be turned into sub-items, and other items cannot be turned into roots
	 */
	protected bool $protectRoots = FALSE;

	/**
	 * @var string
	 */
	protected string $title = '';

	/**
	 * @var bool
	 */
	protected bool $sortable = true;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Are we sortable? */
		$nodeClass = $this->nodeClass;
		$this->sortable = ( $nodeClass::$databaseColumnOrder and $nodeClass::$nodeSortable );
		
		/* Set the title */
		$title = $nodeClass::$nodeTitle;

		if( Request::i()->subnode == 1 AND isset( $nodeClass::$subnodeClass ) )
		{
			$subnodeClass = $nodeClass::$subnodeClass;
			$title = $subnodeClass::$nodeTitle;
		}

		$this->title = $title;
		Output::i()->title = Member::loggedIn()->language()->addToStack( $title );
				
		/* Do stuff */
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$nodeClass = $this->nodeClass;
		
		if ( isset( Request::i()->searchResult ) )
		{
			try
			{
				Output::i()->output .= Theme::i()->getTemplate( 'global', 'core' )->message( sprintf( Member::loggedIn()->language()->get('search_results_in_nodes'), mb_strtolower( Member::loggedIn()->language()->get( $nodeClass::$nodeTitle . '_sg' ) ) ), 'information' );
			}
			catch( UnderflowException $ex )
			{
				Log::log( $ex->getMessage(), "Language" );
			}
		}
		
		if ( $nodeClass::$databaseColumnParent === NULL )
		{
			$this->protectRoots = TRUE;
		}
					
		$tree = new Tree( $this->url, $nodeClass::$nodeTitle, array( $this, '_getRoots' ), array( $this, '_getRow' ), array( $this, '_getRowParentId' ), array( $this, '_getChildren' ), array( $this, '_getRootButtons' ), TRUE, $this->lockParents, $this->protectRoots, $this->_getRootsPerPage(), array( $this, '_getTotalRoots' ) );
		Output::i()->output .= $tree;
	}

	/**
	 * @return int|null
	 */
	public function _getRootsPerPage(): ?int
	{
		return null;
	}
	
	/**
	 * Get Root Rows
	 *
	 * @return	array
	 */
	public function _getRoots(): array
	{
		$nodeClass = $this->nodeClass;
		$rows = array();
		foreach( $nodeClass::roots( NULL ) as $node )
		{
			$rows[ $node->_id ] = $this->_getRow($node);
		}
		
		return $rows;
	}

	/**
	 * @return int
	 */
	public function _getTotalRoots(): int
	{
		$nodeClass = $this->nodeClass;
		return count( $nodeClass::roots( NULL ) );
	}

	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = TRUE;
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		$nodeClass = $this->nodeClass;
		
		if ( $nodeClass::canAddRoot() )
		{
			$add = array(
				'icon'	=> 'plus',
				'title'	=> 'add',
				'link'	=> $this->url->setQueryString( 'do', 'form' ),
				'data'	=> ( $nodeClass::$modalForms ? array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') ) : array() )
			);

			if ( Request::i()->isAjax() or !$this->_addButtonInRoot )
			{
				return array( 'add' => $add );
			} 
			else
			{
				Output::i()->sidebar['actions']['add'] = ( array( 'primary' => true ) + $add );
			}
		}
		return array();
	}

	/**
	 * Return the custom badge for each row
	 *
	 * @param	Model	$node	Node returned from $nodeClass::load()
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	public function _getRowBadge( Model $node ): ?array
	{
		return NULL;
	}
	
	/**
	 * Fetch any additional HTML for this row
	 *
	 * @param object $node	Node returned from $nodeClass::load()
	 * @return	NULL|string
	 */
	public function _getRowHtml( object $node ): ?string
	{
		return null;
	}
	
	/**
	 * Get Single Row
	 *
	 * @param	mixed	$id		May be ID number (or key) or an \IPS\Node\Model object
	 * @param bool $root	Format this as the root node?
	 * @param bool $noSort	If TRUE, sort options will be disabled (used for search results)
	 * @return	string
	 */
	public function _getRow( mixed $id, bool $root=FALSE, bool $noSort=FALSE ): string
	{
		$nodeClass = $this->nodeClass;
		if ( $id instanceof Model)
		{
			$node = $id;
		}
		else
		{
			try
			{
				$node = $nodeClass::load( $id );
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2S101/P', 404, '' );
			}
		}
		
		$id = ( $node instanceof $nodeClass ) ? $node->_id :  "s.{$node->_id}";

		/* @var Model $class */
		$class = get_class( $node );
		
		$buttons = $node->getButtons($this->url, !($node instanceof $this->nodeClass));
		if ( isset( Request::i()->searchResult ) and isset( $buttons['edit'] ) )
		{
			$buttons['edit']['link'] = $buttons['edit']['link']->setQueryString( 'searchResult', Request::i()->searchResult );
		}
		if ( isset( $buttons['delete'] ) and isset( Request::i()->root ) and Request::i()->root == $id )
		{
			unset( $buttons['delete']['data']['delete'] );
			$buttons['delete']['data']['confirm'] = '';
		}
										
		return Theme::i()->getTemplate( 'trees', 'core' )->row(
			$this->url,
			$id,
			static::nodeTitle( $node ),
			$node->childrenCount( NULL ),
			$buttons,
			$node->_description,
			$node->_icon ? $node->_icon : NULL,
			( $noSort === FALSE and $class::$nodeSortable and $node->canEdit() ) ? $node->_position : NULL,
			$root,
			$node->_enabled,
			( $node->_locked or !$node->canEdit() ),
			( ( $node instanceof Model) ? $node->_badge : $this->_getRowBadge( $node ) ),
			$this->_titleHtml,
			$this->_descriptionHtml,
			$node->canAdd(),
			( $node instanceof $nodeClass ),
			$this->_getRowHtml( $node ) . $node->ui( 'rowHtml' ),
			isset( $node->_lockedLang ) ? $node->_lockedLang : null,
			isset( $class::$featureColumnName ),
			isset( $class::$featureColumnName ) ? $node->_featureColor : null
		);
	}
	
	/**
	 * Get Row parent ID
	 *
	 * @param mixed $id		Row ID
	 * @return	mixed	Parent ID
	 */
	public function _getRowParentId( mixed $id ): mixed
	{
		$nodeClass = $this->nodeClass;

		try
		{
			return $nodeClass::load( $id )->parent();
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/Q', 404, '' );
		}
	}
	
	/**
	 * Get Child Rows
	 *
	 * @param	int|string	$id		Row ID
	 * @return	array
	 */
	public function _getChildren( int|string $id ): array
	{
		$rows = array();

		$nodeClass = $this->nodeClass;

		try
		{
			$node	= $nodeClass::load( $id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/R', 404, '' );
		}

		foreach ( $node->children( NULL ) as $child )
		{
			$id = ( $child instanceof $this->nodeClass ? '' : 's.' ) . $child->_id;
			$rows[ $id ] = $this->_getRow($child);
		}
		return $rows;
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		/* What class are we working with? */
		$nodeClass = $this->nodeClass;
		$parentNodeClass = NULL;
		if ( Request::i()->subnode )
		{
			$parentNodeClass = $nodeClass;
			$nodeClass = $nodeClass::$subnodeClass;
		}
		$node = NULL;
		
		/* Init Edit */
		if ( Request::i()->id )
		{
			/* Load the node being edited */
			try
			{
				$node = $nodeClass::load( Request::i()->id );
				Output::i()->title = $node->_title;
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2S101/K', 404, '' );
			}
			
			/* Check we have permission to edit it */
			if( !$node->canEdit() )
			{
				Output::i()->error( 'node_noperm_edit', '2S101/N', 403, '' );
			}
		}
		
		/* Init Create */
		else
		{
			/* Create a new object */
			$node = new $nodeClass;
			
			/* Set an appropriate title */
			if ( !$this->title )
			{
				Output::i()->title = Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle . '_add_child' );
			}
			else
			{
				Output::i()->title = Member::loggedIn()->language()->addToStack( $this->title );
			}
			
			/* Are we creating a child of an existing node? */
			if ( Request::i()->parent )
			{
				$parentColumn = NULL;
				
				/* Sub node? */
				if ( Request::i()->subnode )
				{
					if ( isset( $nodeClass::$parentNodeColumnId ) )
					{
						try
						{
							$parent = $parentNodeClass::load( Request::i()->parent );
							if ( !$parent->canAdd() )
							{
								Output::i()->error( 'node_noperm_edit', '2S101/W', 403, '' );
							}
							$parentColumn = $nodeClass::$parentNodeColumnId;
						}
						catch ( OutOfRangeException $e ) { }
					}
				}
				/* Nope, normal */
				elseif ( isset( $nodeClass::$databaseColumnParent ) )
				{
					try
					{
						$parent = $nodeClass::load( Request::i()->parent );
						if ( !$parent->canAdd() )
						{
							Output::i()->error( 'node_noperm_edit', '2S101/V', 403, '' );
						}
						$parentColumn = $nodeClass::$databaseColumnParent;
					}
					catch ( OutOfRangeException $e ) { }
				}
				
				/* Set the value */
				if ( $parentColumn !== NULL )
				{
					$node->$parentColumn = Request::i()->parent;
				}
			}
			/* No - creating a root - check permission */
			else
			{
				if( !$nodeClass::canAddRoot() )
				{
					Output::i()->error( 'node_noperm_edit', '2S101/U', 403, '' );
				}
			}
		}
		/* Build form */
		$form = $this->_addEditForm( $node );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if ( isset( Request::i()->massChangeValue ) )
			{
				Output::i()->json( (string) $values[ Request::i()->massChangeValue ] );
			}
			
			try
			{
				$new = !$node->_id;
				if ( $new and isset( $node::$databaseColumnOrder ) AND $node::$automaticPositionDetermination === TRUE )
				{
					$orderColumn = $node::$databaseColumnOrder;
					$node->$orderColumn = intval( Db::i()->select( 'MAX(' . $node::$databasePrefix . $orderColumn . ')', $node::$databaseTable  )->first() ) + 1;
				}
				
				$old = NULL;
				if ( !$new )
				{
					$node->skipCloneDuplication = TRUE;
					$old = clone $node;
				}
				$node->saveForm( $node->formatFormValues( $values ) );
								
				if ( $new )
				{
					Session::i()->log( 'acplog__node_created', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
					
					if ( $node->canManagePermissions() )
					{
						Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'permissions', 'id' => $node->_id, 'subnode' => ( isset( Request::i()->subnode ) ? Request::i()->subnode : 0 ) ) ) );
					}
				}
				else
				{
					if ( $node->parent() )
					{
						foreach( $node->parent()->children() AS $child )
						{
							$child->setLastComment();
							$child->setLastReview();
							$child->save();
						}
						
						if ( Request::i()->subnode )
						{
							if ( isset( $nodeClass::$parentNodeColumnId ) )
							{
								$parentColumn = $nodeClass::$parentNodeColumnId;
							}
						}
						elseif ( isset( $nodeClass::$databaseColumnParent ) )
						{
							$parentColumn = $nodeClass::$databaseColumnParent;
						}

						if( isset( $parentColumn ) )
						{
							$node->$parentColumn = $node->parent()->_id;
						}

						$node->setLastComment();
						$node->setLastReview();
						$node->save();
					}
					
					Session::i()->log( 'acplog__node_edited', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
				}

				$this->_afterSave( $old, $node, $form->getLastUsedTab() );
				return;
			}
			catch ( LogicException $e )
			{
				$form->error = $e->getMessage();
			}
		}

		/* Display */
		Output::i()->output .= $this->_showForm( $form );
	}
	
	/**
	 * Get form
	 *
	 * @param Model $node
	 * @return	Form
	 */
	protected function _addEditForm( Model $node ): Form
	{
		$form = new Form( 'form_' . ( $node->_id ?: 'new' ) );
		if ( $node->_id AND !$node->noCopyButton )
		{
			$form->copyButton = $this->url->setQueryString( array( 'do' => 'massChange', 'from' => $node->_id ) );
			if ( !( $node instanceof $this->nodeClass ) )
			{
				$form->copyButton = $form->copyButton->setQueryString( 'subnode', 1 );
			}
		}

		$node->form($form);
		
		return $form;
	}
	
	/**
	 * Get form
	 *
	 * @param	Form	$form	The form as returned by _addEditForm()
	 * @return	string
	 */
	protected function _showForm( Form $form ): string
	{
		return (string) $form;
	}
	
	/**
	 * Redirect after save
	 *
	 * @param Model|null $old			A clone of the node as it was before or NULL if this is a creation
	 * @param Model $new			The node now
	 * @param bool|string $lastUsedTab	The tab last used in the form
	 * @return	void
	 */
	protected function _afterSave( ?Model $old, Model $new, bool|string $lastUsedTab = FALSE ): void
	{
		if( Request::i()->isAjax() )
		{
			Output::i()->json( array() );
		}
		else
		{
			if( isset( Request::i()->save_and_reload ) )
			{
				$buttons = $new->getButtons($this->url, !($new instanceof $this->nodeClass));

				Output::i()->redirect( ( $lastUsedTab ? $buttons['edit']['link']->setQueryString('activeTab', $lastUsedTab ) : $buttons['edit']['link'] ), 'saved' );
			}
			else
			{
				Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $new->parent() ? $new->parent()->_id : '' ) ) ), 'saved' );
			}
		}
	}
	
	/**
	 * Mass Change
	 *
	 * @return	void
	 */
	protected function massChange() : void
	{
		/* Check permission */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}
		try
		{
			$node = $nodeClass::load( Request::i()->from );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/S', 404, '' );
		}
		if( !$node->canEdit() )
		{
			Output::i()->error( 'node_noperm_edit', '2S101/T', 403, '' );
		}
		
		/* Get the value */
		$key = Request::i()->key;
		$sessionKey = 'MC' . $key;
		$dummyForm = new Form;
		$node->form( $dummyForm );

		if( isset( Request::i()->value ) )
		{
			$_SESSION[ $sessionKey ] = Request::i()->value;
		}

		if( isset( $_SESSION[ $sessionKey ] ) )
		{
			$value = $_SESSION[ $sessionKey ];
		}
		else
		{
			$value = isset( Request::i()->value ) ? Request::i()->value : NULL;
			if ( $value === NULL )
			{
				foreach ( $dummyForm->elements as $tab => $elements )
				{
					if ( isset( $elements[ $key ] ) )
					{
						$value = $elements[ $key ]->value;
						break;
					}
				}
			}

			$_SESSION[ $sessionKey ] = $value;
		}
		
		/* Build Form */
		$form = new Form;
		$form->ajaxOutput = TRUE;
		$field = new FormNode( 'nodes', array(), TRUE, array( 'url' =>  $this->url->setQueryString( array( 'do' => 'massChange', 'key' => $key, 'from' => $node->_id ) ), 'class' => $nodeClass, 'zeroVal' => 'all', 'multiple' => TRUE, 'permissionCheck' => function( $node ) use ( $key, $value )
		{
			return $node->canCopyValue( $key, $value );
		} ) );
		$field->label = Member::loggedIn()->language()->get( 'copy_value_to' );
		$form->add( $field );

		/* Display */
		if ( $values = $form->values() )
		{
			$url = $this->url;
			$multiRedirectUrl = $this->url->setQueryString( array( 'do' => 'massChange', 'key' => Request::i()->key, 'nodes' => Request::i()->nodes ?: $values['nodes'], 'from' => Request::i()->from, 'form_submitted' => 1, 'csrfKey' => Request::i()->csrfKey ) );
			if ( Request::i()->subnode )
			{
				$multiRedirectUrl = $multiRedirectUrl->setQueryString( 'subnode', 1 );
			}
			Output::i()->output = new MultipleRedirect( $multiRedirectUrl,
				function( $doneSoFar ) use ( $nodeClass, $sessionKey )
				{
					$count	= Db::i()->select( 'COUNT(*)', $nodeClass::$databaseTable, Request::i()->nodes == 0 ? NULL : Db::i()->in( $nodeClass::$databasePrefix . $nodeClass::$databaseColumnId, explode( ',', Request::i()->nodes) ) )->first();
					if ( !$count )
					{
						return NULL;
					}

					$select = Db::i()->select( '*', $nodeClass::$databaseTable, Request::i()->nodes == 0 ? NULL : Db::i()->in( $nodeClass::$databasePrefix . $nodeClass::$databaseColumnId, explode( ',', Request::i()->nodes) ), $nodeClass::$databasePrefix . $nodeClass::$databaseColumnId, array( $doneSoFar, 50 ) );

					$did	= 0;
					foreach ( $select as $row )
					{
						$did++;
						$_node = $nodeClass::constructFromData( $row );
						
						if ( $_node->canCopyValue( Request::i()->key, $_SESSION[ $sessionKey ] ) )
						{
							$values = $_node->formatFormValues( array( Request::i()->key => $_SESSION[ $sessionKey ] ) );
														
							foreach( $values as $k => $v )
							{
                                $k = preg_replace( '#^' . preg_quote( $nodeClass::$databasePrefix, '#' ) . '#', "", $k );
								$val = $_node->$k;

								if( is_array( $v ) )
								{
									foreach( $v as $_k => $_v )
									{
										$val[ $_k ]	= $_v;
									}
	
									$_node->$k	= $val;
								}
								else
								{
									$_node->$k	= $v;
								}
							}

							$_node->save();
						}
					}

					if( !$did )
					{
						return NULL;
					}
					
					$doneSoFar += 50;
					return array( $doneSoFar, Member::loggedIn()->language()->addToStack('copying'), 100 / $count * $doneSoFar );
				}, 
				function() use( $url, $sessionKey )
				{
					/* Clear the value in the session */
					unset( $_SESSION[ $sessionKey ] );

					/* And redirect */
					$finishUrl = $url->setQueryString( array( 'do' => 'form', 'id' => Request::i()->from ) );
					if ( Request::i()->subnode )
					{
						$finishUrl = $finishUrl->setQueryString( 'subnode', 1 );
					}
					Output::i()->redirect( $finishUrl );
				},
				FALSE 
			);
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate('global', 'core')->block( '', $form );
		}
	}
				
	/**
	 * Toggle Enabled/Disable
	 *
	 * @return	void
	 */
	protected function enableToggle() : void
	{
		Session::i()->csrfCheck();
		
		/* Work out which class we're using */
		$nodeClass = $this->nodeClass;
		if ( mb_substr( Request::i()->id, 0, 2 ) === 's.' )
		{
			Request::i()->id = mb_substr( Request::i()->id, 2 );
			$nodeClass = $nodeClass::$subnodeClass;
		}
	
		/* Load Node */
		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '3S101/A', 404, '' );
		}
		
		/* Check we're not locked */
		if( $node->_locked or !$node->canEdit() )
		{
			Output::i()->error( 'node_noperm_enable', '2S101/3', 403, '' );
		}
		
		/* Toggle */
		$node->_enabled = Request::i()->status;
		$node->save();

		/* Recount if needed */
		if( $node->parent() )
		{
			$node->parent()->setLastComment();
			$node->parent()->setLastReview();
			$node->parent()->save();
		}

		$this->_logToggleAndRedirect( $node );
	}

	/**
	 * Following a toggle, log the action and redirect. Abstracted so it can be called separately externally.
	 *
	 * @param Model $node	The node we are working with
	 * @return void
	 */
	public function _logToggleAndRedirect( Model $node ) : void
	{
		/* Log */
		if ( $node->_enabled )
		{
			Session::i()->log( 'acplog__node_enabled', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
		}
		else
		{
			Session::i()->log( 'acplog__node_disabled', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
		}
				
		/* If this is an AJAX request, just respond */
		if( Request::i()->isAjax() )
		{
			Output::i()->json( $node->_enabled );
		}
		/* Otherwise, redirect */
		else
		{
			Output::i()->redirect( $this->url->setQueryString( array( 'root' => Request::i()->root ) ) );
		}
	}
	
	/**
	 * Copy
	 *
	 * @return	void
	 */
	protected function copy() : void
	{
		/* Get node */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}
		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/L', 404, '' );
		}

		/* Do we have any children? */
		if ( $node->hasChildren( NULL, NULL, FALSE ) and !isset( Request::i()->skipChildren ) )
		{
			$form = new Form;
			$form->add( new YesNo( 'node_copy_children', NULL, FALSE, array(), function( $val ) use ( &$form )
			{
				if ( $val )
				{
					$form->ajaxOutput = TRUE;
				}
			} ) );

			/* A multiredirect has been initiated so we need to let the code flow to the MR helper below, but check CSRF key first */
			if( Request::i()->node_copy_children )
			{
				Session::i()->csrfCheck();
			}

			if ( $values = $form->values() OR Request::i()->node_copy_children )
			{
				/* Copy Children */
				if ( $values['node_copy_children'] OR Request::i()->node_copy_children )
				{
					$multipleRedirect = new MultipleRedirect(
						$this->url->setQueryString( array( 'do' => 'copy', 'id' => $node->_id, 'subnode' => Request::i()->subnode, 'form_submitted' => 1, 'csrfKey' => Request::i()->csrfKey, 'node_copy_children' => 1 ) ),
						/* Process */
						function( $data ) use ( $node, $nodeClass )
						{
							/* Init */
							if ( !is_array( $data ) )
							{
								return array( array( 'copy' => array( array( 'id' => $node->_id, 'subnode' => 0 ) ), 'ids' => array() ), Member::loggedIn()->language()->addToStack('copying') );
							}
							/* Process */
							else
							{
								/* Have we finished? */
								if ( empty( $data['copy'] ) )
								{
									return NULL;
								}
								
								/* No, still going */
								foreach( $data['copy'] as $k => $itemData )
								{
									/* Load */
									if ( $itemData['subnode'] )
									{
										$nodeClass = $nodeClass::$subnodeClass;
									}
									$item = $nodeClass::load( $itemData['id'] );
									
									/* Copy it */
									$new = clone $item;
									$data['ids'][ get_class( $item ) ][ $item->_id ] = $new->_id;

									/* Update it's parent */
									if ( $item->parent() )
									{
										if ( array_key_exists( $item->parent()->_id, $data['ids'][ get_class( $item->parent() ) ] ) )
										{
											$parentColumn = $nodeClass::$databaseColumnParent;
											if ( $itemData['subnode'] )
											{
												$parentColumn = $nodeClass::$parentNodeColumnId;
											}
																						
											$new->$parentColumn = $data['ids'][ get_class( $item->parent() ) ][ $item->parent()->_id ];
											$new->save();
										}
									}
									
									/* Remove this one from our array */
									unset( $data['copy'][ $k ] );
									
									/* And add all it's children */
									foreach ( $item->children( NULL ) as $child )
									{
										$data['copy'][] = array( 'id' => $child->_id, 'subnode' => !( $child instanceof $nodeClass ) );
									}
									
									/* Return */
									return array( $data, Member::loggedIn()->language()->addToStack('copying') );
								}
							}

							return [];
						},
						/* Finish */
						function() use ( $node )
						{
							Session::i()->log( 'acplog__node_copied_c', array( $node->title => TRUE, $node->titleForLog() => FALSE ) );
							Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->id : '' ) ) ), 'saved' );
						}
					);
					Output::i()->output = $multipleRedirect;
					return;
				}
			}
			else
			{
				/* Show form */
				Output::i()->output = $form;
				return;
			}
		}
		else
		{
			Session::i()->csrfCheck();
		}

		/* Copy it */
		$new = clone $node;
		Session::i()->log( 'acplog__node_copied', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );

		/* Boink */
		$url = $this->url->setQueryString( array( 'do' => 'form', 'id' => $new->_id ) );
		if ( isset( Request::i()->subnode ) )
		{
			$url = $url->setQueryString( 'subnode', 1 );
		}
		Output::i()->redirect( $url, 'copied' );
	}
		
	/**
	 * Reorder
	 *
	 * @return	void
	 */
	protected function reorder() : void
	{
		Session::i()->csrfCheck();
		
		/* Init */
		$nodeClass = $this->nodeClass;
		
		/* Normalise AJAX vs non-AJAX */
		if( isset( Request::i()->ajax_order ) )
		{
			$order = array();
			$position = array();
			foreach( Request::i()->ajax_order as $id => $parent )
			{
				if ( !isset( $order[ $parent ] ) )
				{
					$order[ $parent ] = array();
					$position[ $parent ] = 1;
				}
				$order[ $parent ][ $id ] = $position[ $parent ]++;
			}
		}
		/* Non-AJAX way */
		else
		{
			$order = array( Request::i()->root ?: 'null' => Request::i()->order );
		}

		/* Okay, now order */
		foreach( $order as $parent => $nodes )
		{
			foreach ( $nodes as $id => $position )
			{
				/* Load Node */
				try
				{
					if ( mb_substr( $id, 0, 2 ) === 's.' )
					{
						$subnodeClass = $nodeClass::$subnodeClass;

						$node = $subnodeClass::load( mb_substr( $id, 2 ) );
						$parentColumn = $node::$parentNodeColumnId;
					}
					else
					{
						$node = $nodeClass::load( $id );
						$parentColumn = $node::$databaseColumnParent;
					}
				}
				catch ( OutOfRangeException $e )
				{
					Output::i()->error( 'node_error', '3S101/B', 404, '' );
				}
				$orderColumn = $node::$databaseColumnOrder;
				$idColumn = $node::$databaseColumnId;
				
				/* Check permission */
				if( !$node->canEdit() )
				{
					continue;
				}
				if( !$node::$nodeSortable or $orderColumn === NULL )
				{
					continue;
				}
								
				/* Do it */
				if ( $parentColumn )
				{
					if ( is_numeric( $parent ) and $node->$idColumn == $parent AND !isset( $nodeClass::$subnodeClass ) )
					{
						/* It is attempting to assign a parent ID of itself which will break the tree */
						$parent = $nodeClass::$databaseColumnParentRootValue;
					}

					$node->$parentColumn = ( $parent === 'null' ) ? $nodeClass::$databaseColumnParentRootValue : ( is_numeric( $parent ) ? $parent : $nodeClass::$databaseColumnParentRootValue );
				}

				$node->$orderColumn = $position;
				$node->save();
			}

			if( $parent !== 'null' )
			{
				$node = $nodeClass::load( $parent );
				$node->setLastComment();
				$node->setLastReview();
				$node->save();
			}
		}

		/* Log */
		Session::i()->log( 'acplog__node_reorder', array( $this->title => TRUE ), TRUE );

		/* Allow plugins to act if necessary */
		$this->_afterReorder( $order );
				
		/* If this is an AJAX request, just respond */
		if( Request::i()->isAjax() )
		{
			return;
		}
		/* Otherwise, redirect */
		else
		{
			Output::i()->redirect( $this->url->setQueryString( array( 'root' => Request::i()->root ) ) );
		}
	}

	/**
	 * Function to execute after nodes are reordered. Do nothing by default but plugins can extend.
	 *
	 * @param array $order	The new ordering that was saved
	 * @return	void
	 */
	protected function _afterReorder( array $order ) : void
	{
		
	}
	
	/**
	 * Permissions
	 *
	 * @return	void
	 */
	protected function permissions() : void
	{
		/* Get node */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}
		$node = NULL;
		
		if ( Request::i()->id )
		{
			try
			{
				$node = $nodeClass::load( Request::i()->id );
				Output::i()->title = $node->_title;
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2S101/M', 404, '' );
			}
		}
		else
		{
			Output::i()->error( 'node_error', '2S101/X', 404, '' );
		}
		
		/* Check permission */
		if( !$node->canManagePermissions() )
		{
			Output::i()->error( 'node_noperm_edit', '2S101/O', 403, '' );
		}
		
		/* Get current permissions */
		try
		{
			$current = Db::i()->select( '*', 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', $nodeClass::$permApp, $nodeClass::$permType, $node->_id ) )->first();
		}
		catch( UnderflowException $e )
		{
			/* Recommended permissions */
			$current = array();
			foreach ( $nodeClass::$permissionMap as $k => $v )
			{
				switch ( $k )
				{
					case 'view':
					case 'read':
						$current["perm_{$v}"] = '*';
						break;
						
					case 'add':
					case 'reply':
					case 'review':
					case 'upload':
					case 'download':
					default:
						$current["perm_{$v}"] = implode( ',', array_keys( Group::groups( TRUE, FALSE ) ) );
						break;
				}
			}
		}
		
		/* Build Matrix */
		$matrix = new Matrix;
		$matrix->manageable = FALSE;
		$matrix->langPrefix = $nodeClass::$permissionLangPrefix . 'perm__';
		$matrix->styledRowTitle = TRUE;
		$matrix->columns = array(
			'label'		=> function( $key, $value, $data )
			{
				$groupId = explode( '[', $key )[0];
				$groupLink = NULL;

				/* Let's try to get a link to the group's permissions */
				try
				{
					Group::load( $groupId );
					$groupLink = Url::internal( "app=core&module=members&controller=groups&do=permissions&id=" . $groupId );
				}
				catch ( OutOfRangeException $e ) {	}

				return Theme::i()->getTemplate( 'global', 'core', 'global' )->titleWithLink( $value, $groupLink, 'all_permissions', Member::loggedIn()->language()->addToStack( "all_permissions_group_hovertitle", FALSE, array( 'sprintf' => [ $value ] ) ) );
			},
		);
		
		try
		{
			$disabledPermissions = $node->disabledPermissions();
		}
		catch( UnderflowException $e )
		{
			if( $e->getCode() != 199 )
			{
				throw $e;
			}

			Output::i()->error( 'generic_error', '4F383/1', 500, $e->getMessage() );
		}
		
		foreach ( $node->permissionTypes() as $k => $v )
		{
			$matrix->columns[ $k ] = function( $key, $value, $data ) use ( $current, $k, $v, $disabledPermissions )
			{
				$groupId  = mb_substr( $key, 0, -( 2 + mb_strlen( $k ) ) );
				$disabled = FALSE;
				
				if ( array_key_exists( $groupId, $disabledPermissions ) and is_array( $disabledPermissions[ $groupId ] ) )
				{
					$disabled = in_array( $v, array_values( $disabledPermissions[ $groupId ] ) );
				}
				
				if ( $disabled === FALSE )
				{
					$disabled = ( $groupId == Settings::i()->guest_group AND in_array( $k, array( 'review', 'rate' ) ) );
				}
				
				$fieldValue = ( isset( $current[ "perm_{$v}" ] ) and ( $current[ "perm_{$v}" ] === '*' or in_array( $groupId, explode( ',', $current[ "perm_{$v}" ] ) ) ) );

				return new Checkbox( $key, ( $disabled ? 0 : $fieldValue ), NULL, array( 'disabled' => $disabled ) );
			};
			$matrix->checkAlls[ $k ] = ( $current[ "perm_{$v}" ] === '*' );
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

			/* Check for "all" checkboxes */
			foreach ( $nodeClass::$permissionMap as $k => $v )
			{
				if ( isset( Request::i()->__all[ $k ] ) )
				{
					$shouldCheckAll = TRUE;
					foreach( $matrix->elements as $group => $element )
					{
						if ( isset( $element[ $k ] ) and $element[ $k ]->options['disabled'] )
						{
							$shouldCheckAll = FALSE;
							break;
						}
					}
					
					$_perms[ $v ] = $shouldCheckAll ? '*' : array();
				}
				else
				{
					$_perms[ $v ] = array();
				}
			}
			
			/* Prepare insert */
			$insert = array( 'app' => $nodeClass::$permApp, 'perm_type' => $nodeClass::$permType, 'perm_type_id' => $node->_id );
			if ( isset( $current['perm_id'] ) )
			{
				$insert['perm_id'] = $current['perm_id'];
			}
			
			/* Loop groups */
			foreach ( $values as $group => $perms )
			{
				foreach ( $nodeClass::$permissionMap as $k => $v )
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
				$insert[ "perm_{$k}" ] = is_array( $v ) ? implode( ',', $v ) : $v;
			}
			
			/* Set the permissions */
			$node->setPermissions( $insert );

			unset(Store::i()->modules);

			/* Log */
			Session::i()->log( 'permissions_adjusted_node', array( $node->titleForLog() => FALSE ) );
			
			/* Redirect */
			$this->_afterSave( NULL, $node );
			return;
		}
		
		/* Display */
		Output::i()->output .= $matrix;
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		/* Get node */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}
		
		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/J', 404, '' );
		}
		 
		/* Permission check */
		if( !$node->canDelete() )
		{
			Output::i()->error( 'node_noperm_delete', '2S101/H', 403, '' );
		}

		/* Do we have any children or content? */
		if ( $node->hasChildren( NULL, NULL, TRUE ) or $node->showDeleteOrMoveForm() or isset( Request::i()->ajaxValidate ) )
		{			
			$form = $node->deleteOrMoveForm( FALSE );
			if ( $values = $form->values() )
			{
				$node->deleteOrMoveFormSubmit( $values );

				if ( isset( $values['node_move_children'] ) AND $values['node_move_children'] )
				{
					$moveToId = ( isset( $values['node_destination'] ) ) ? $values['node_destination'] : Request::i()->node_move_children;

					Session::i()->log( 'acplog__node_deleted_m', array( $this->title => TRUE, $node->titleForLog() => FALSE, $node::load( $moveToId )->titleForLog() => FALSE ) );
				}
				else
				{
					Session::i()->log( 'acplog__node_deleted_c', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
				}

				Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->_id : '' ) ) ), 'deleted' );
			}
			else
			{
				/* Show form */
				Output::i()->output = $form;
				return;
			}
		}
		else
		{
			/* Make sure the user confirmed the deletion */
			Request::i()->confirmedDelete();
		}
		
		/* Delete it */
		Session::i()->log( 'acplog__node_deleted', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
		$node->delete();

		/* Boink */
		if( Request::i()->isAjax() )
		{
			Output::i()->json( "OK" );
		}
		else
		{
			Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->_id : '' ) ) ), 'deleted' );
		}
	}
	
	/**
	 * Mass Move/Delete Content
	 *
	 * @return	void
	 */
	protected function massManageContent() : void
	{
		/* Get node */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}
		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/X', 404, '' );
		}
		 
		/* Permission check */
		if( !$node->canMassManageContent() )
		{
			Output::i()->error( 'node_noperm_delete', '2S101/Y', 403, '' );
		}
		
		/* Is there any content? */
		if ( !isset( $nodeClass::$contentItemClass ) or !$node->getContentItemCount() )
		{
			Output::i()->error( 'node_mass_content_none', '2S101/Z' );
		}
		$contentItemClass = $nodeClass::$contentItemClass;
		
		/* Build Wizard */
		Output::i()->title = $node->_title;
		Output::i()->output = (string) new Wizard(
			array(
				'node_mass_content_form'	=> function( $data ) use( $nodeClass, $node, $contentItemClass )
				{
					/* Fetch the form to mass move content - abstracted so other apps can adjust if necessary */
					$form = new Form( 'delete_node_form', 'continue', $this->url->setQueryString( array( 'do' => 'massManageContent', 'id' => $node->_id, 'subnode' => Request::i()->subnode, '_step' => 'node_mass_content_form' ) ) );
					$form = $this->_buildMassMoveForm( $form, $data, $nodeClass, $node, $contentItemClass );
					
					/* Handle submissions */
					if ( $values = $form->values() )
					{
						return $this->_processMassMoveForm( $values, $data, $nodeClass, $node, $contentItemClass );
					}
					
					/* Display Form */
					return (string) $form;
				},
				'node_mass_content_confirm'	=> function( $data ) use ( $node, $contentItemClass )
				{
					return $this->_performMassMove( $data, $node, $contentItemClass );
				}
			),
			$this->url->setQueryString( array( 'do' => 'massManageContent', 'id' => $node->_id, 'subnode' => Request::i()->subnode ) )
		);
	}

	/**
	 * Build the form to mass move content
	 *
	 * @param Form $form	The form helper object
	 * @param	mixed			$data		Data from the wizard helper
	 * @param	string			$nodeClass	Node class
	 * @param Model $node		Node we are working with
	 * @param string $contentItemClass	Content item class (if there is one)
	 * @return Form
	 */
	protected function _buildMassMoveForm( Form $form, mixed $data, string $nodeClass, Model $node, string $contentItemClass ): Form
	{
		$form->addHeader('node_mass_move_delete_if');
		$form->add( new Form\Member( 'node_move_author', isset( $data['additional']['author'] ) ? array_map( function($id ) {
			return Member::load( $id );
		}, $data['additional']['author'] ) : NULL, FALSE, array( 'nullLang' => 'node_mass_move_anyone', 'multiple' => NULL ) ) );
		$form->add( new Date( 'node_move_date', $data['additional']['date'] ?? 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'node_mass_move_any_time' ) ) );
		if ( isset( $contentItemClass::$commentClass ) )
		{						
			$form->add( new Number( 'node_move_comments', $data['additional']['num_comments'] ?? -1, FALSE, array( 'unlimited' => -1 ), NULL, NULL, NULL, 'node_move_comments_less' ) );
			$form->add( new Date( 'node_move_last_post', $data['additional']['last_post'] ?? 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'node_mass_move_any_time' ) ) );
		}
		if ( IPS::classUsesTrait( $contentItemClass, 'IPS\Content\Lockable' ) )
		{
			$form->add( new Radio( 'node_move_state', $data['additional']['state'] ?? 'any', FALSE, array( 'options' => array( 'locked' => 'locked', 'open' => 'unlocked', 'any' => 'node_mass_move_either' ) ) ) );
		}
		if ( IPS::classUsesTrait( $contentItemClass, 'IPS\Content\Pinnable' ) )
		{
			$form->add( new Radio( 'node_move_pinned', $data['additional']['pinned'] ?? 'any', FALSE, array( 'options' => array( '1' => 'pinned', '0' => 'node_move_pinned_not_pinned', 'any' => 'node_mass_move_either' ) ) ) );
		}
		if ( IPS::classUsesTrait( $contentItemClass, 'IPS\Content\Featurable' ) )
		{
			$form->add( new Radio( 'node_move_featured', $data['additional']['featured'] ?? 'any', FALSE, array( 'options' => array( '1' => 'featured', '0' => 'node_move_pinned_not_featured', 'any' => 'node_mass_move_either' ) ) ) );
		}
		$form->addHeader('node_mass_move_delete_then');
		$moveToClass = $data['moveToClass'] ?? $nodeClass;
		$form->add( new FormNode( 'node_move_content', isset( $data['moveTo'] ) ? $moveToClass::load( $data['moveTo'] ) : 0, TRUE, array( 'class' => $nodeClass, 'disabledIds' => array( $node->_id ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'node_delete_content', 'subnodes' => FALSE, 'permissionCheck' => function( $node )
		{
			return array_key_exists( 'add', $node->permissionTypes() );
		} ) ) );

		return $form;
	}

	/**
	 * Process the mass move form submission
	 *
	 * @param array $values		Values from form submission
	 * @param	mixed			$data		Data from the wizard helper
	 * @param string $nodeClass	Node class
	 * @param Model $node		Node we are working with
	 * @param string $contentItemClass	Content item class (if there is one)
	 * @return	array	Wizard helper data
	 */
	protected function _processMassMoveForm(array $values, mixed $data, string $nodeClass, Model $node, string $contentItemClass ): array
	{
		$data['deleteWhenDone'] = FALSE;
		$data['class'] = get_class( $node );
		$data['id'] = $node->_id;
		
		if ( is_object( $values['node_move_content'] ) )
		{
			$data['moveToClass'] = get_class( $values['node_move_content'] );
			$data['moveTo'] = $values['node_move_content']->_id;
		}
		else
		{
			unset( $data['moveToClass'] );
			unset( $data['moveTo'] );
		}
		
		if ( count( $values['node_move_author'] ) )
		{
			$data['additional']['author'] = array_keys( $values['node_move_author'] );
		}
		else
		{
			unset( $data['additional']['author'] );
		}
		
		if ( $values['node_move_date'] )
		{
			$data['additional']['date'] = $values['node_move_date']->setTime( 23, 59, 59 )->getTimestamp();
		}
		else
		{
			unset( $data['additional']['date'] );
		}
		
		if ( isset( $values['node_move_comments'] ) and $values['node_move_comments'] > -1 )
		{
			$data['additional']['num_comments'] = $values['node_move_comments'];
		}
		else
		{
			unset( $data['additional']['num_comments'] );
		}
		
		if ( isset( $values['node_move_last_post'] ) and $values['node_move_last_post'] )
		{
			$data['additional']['last_post'] = $values['node_move_last_post']->setTime( 23, 59, 59 )->getTimestamp();
		}
		else
		{
			unset( $data['additional']['last_post'] );
		}
		
		if ( isset( $values['node_move_state'] ) and $values['node_move_state'] != 'any' )
		{
			$data['additional']['state'] = $values['node_move_state'];
		}
		else
		{
			unset( $data['additional']['state'] );
		}
		
		if ( isset( $values['node_move_pinned'] ) and $values['node_move_pinned'] != 'any' )
		{
			$data['additional']['pinned'] = $values['node_move_pinned'];
		}
		else
		{
			unset( $data['additional']['pinned'] );
		}
		
		if ( isset( $values['node_move_featured'] ) and $values['node_move_featured'] != 'any' )
		{
			$data['additional']['featured'] = $values['node_move_featured'];
		}
		else
		{
			unset( $data['additional']['featured'] );
		}

		return $data;
	}

	/**
	 * Actually perform the mass move operation (called from the wizard helper)
	 *
	 * @param	mixed			$data		Data from the wizard helper
	 * @param Model $node		Node we are working with
	 * @param string $contentItemClass	Content item class (if there is one)
	 * @return mixed
	 */
	protected function _performMassMove( mixed $data, Model $node, string $contentItemClass ): mixed
	{
		if ( isset( Request::i()->confirm ) )
		{
			Session::i()->csrfCheck();
			
			Task::queue( 'core', 'DeleteOrMoveContent', $data );

			if ( isset( $data['moveTo'] ) )
			{
				$newNode = $data['moveToClass']::load( $data['moveTo'] );
				Session::i()->log( 'acplog__node_mass_move', array( $this->title => TRUE, $node->titleForLog() => FALSE, $newNode->titleForLog() => FALSE ) );
				Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->_id : '' ) ) ), 'node_mass_content_moving' );
			}
			else
			{
				Session::i()->log( 'acplog__node_mass_delete', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
				Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->_id : '' ) ) ), 'node_mass_content_deleting' );
			}
		}
		else
		{
			$number = $node->getContentItems( NULL, NULL, $node->massMoveorDeleteWhere( $data ), TRUE );

			/* @var Item $contentItemClass */
			return Theme::i()->getTemplate( 'global', 'core' )->nodeMoveDeleteContent( $this->url->setQueryString( array( 'do' => 'massManageContent', 'id' => $node->_id, 'subnode' => Request::i()->subnode ) )->csrf(), Member::loggedIn()->language()->addToStack( $contentItemClass::$title . '_pl_lc' ), $number, isset( $data['moveTo'] ) ? $data['moveToClass']::load( $data['moveTo'] ) : NULL );
		}
	}
	
	/**
	 * Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$rows = array();
		
		/* Get results */
		$nodeClass = $this->nodeClass;
		$results = $nodeClass::search( '_title', Request::i()->input, '_title' );
		
		/* Get results of subnodes */
		if ( isset( $nodeClass::$subnodeClass ) )
		{
			$subnodeClass = $nodeClass::$subnodeClass;
			$results = array_merge( $results, array_values( $subnodeClass::search( '_title', Request::i()->input, '_title' ) ) );
			
			usort( $results, function( $a, $b ) {
				return strnatcasecmp( $a->_title, $b->_title );
			} );
		}
		
		/* Convert to HTML */
		foreach ( $results as $result )
		{
			$id = ( $result instanceof $this->nodeClass ? '' : 's.' ) . $result->_id;
			$rows[ $id ] = $this->_getRow($result, FALSE, TRUE);
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}
	
	/**
	 * Allow overloading to change how the title is displayed in the tree
	 *
	 * @param	$node    Model    Node
	 * @return string
	 */
	protected static function nodeTitle( Model $node ): string
	{
		return $node->_title;
	}
}