<?php
/**
 * @brief		Database Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		22 Aug 2014
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Pages\Page;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Member;
use IPS\Request;
use IPS\Widget;
use OutOfRangeException;
use function count;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Widget
 */
class Database extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'Database';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';

	/**
	 * @brief	HTML if widget is called more than once, we store it.
	 */
	protected static ?string $html = NULL;

	/**
	 * @brief	Set to true if this widget must be the only one in its area
	 */
	public bool $soloWidget = true;

	/**
	 * @brief	Set to false if this widget should be hidden from the block list
	 * 			in the Page Editor
	 */
	public static bool $showInBlockList = false;

	/**
	 * @brief	If a widget should not be dropped into a particular area (e.g. a database widget in the header), list those areas here
	 */
	public static array $disallowedAreas = [ 'header', 'footer', 'sidebar', 'globalfooter' ];
	
	/**
	 * Specify widget configuration
	 *
	 * @param	Form|NULL	$form	Form helper
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );

 		$databases = array();
	    $disabled  = array();

 		foreach( Databases::databases() as $db )
 		{
		    $databases[ $db->id ] = $db->_title;

		    if ( $db->page_id and $db->page_id != Request::i()->pageID )
		    {
			    $disabled[] = $db->id;

				try
				{
					$page = Page::load( $db->page_id );
					$databases[ $db->id ] = Member::loggedIn()->language()->addToStack( 'cms_db_in_use_by_page', FALSE, array( 'sprintf' => array( $db->_title, $page->full_path ) ) );
				}
				catch( OutOfRangeException $ex )
				{
					unset( $databases[ $db->id ] );
				}
		    }
 		}

	    if ( ! count( $databases ) )
	    {
		    $form->addMessage('cms_err_no_databases_to_use');
	    }
 		else
	    {
			$form->add( new Select( 'database', ( isset( $this->configuration['database'] ) ? (int) $this->configuration['database'] : NULL ), FALSE, array( 'options' => $databases, 'disabled' => $disabled ) ) );
	    }

		return $form;
 	}

	/**
	 * Pre save
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( array $values ): array
	{
		if ( Request::i()->pageID and $values['database'] )
		{
			Page::load( Request::i()->pageID )->mapToDatabase( $values['database'] );
		}

		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if ( static::$html === NULL )
		{
			if ( isset( $this->configuration['database'] ) )
			{
				try
				{
					$database = Databases::load( intval( $this->configuration['database'] ) );
					
					if ( ! $database->page_id and Page::$currentPage )
					{
						$database->page_id = Page::$currentPage->id;
						$database->save();
					}

					static::$html = Dispatcher::i()->setDatabase( $database->id )->run();
				}
				catch ( OutOfRangeException $e )
				{
					static::$html = '';
				}
			}
			else
			{
				return '';
			}
		}
		
		return static::$html;
	}
}