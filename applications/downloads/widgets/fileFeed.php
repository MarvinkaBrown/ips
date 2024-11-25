<?php
/**
 * @brief		Files Feed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		22 Jun 2015
 */

namespace IPS\downloads\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content\Widget;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Files Entry Feed Widget
 */
class fileFeed extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'fileFeed';
	
	/**
	 * @brief	App
	 */
	public string $app = 'downloads';
		
	/**
	 * @brief	Plugin
	 */
	public string $plugin = '';
	
	/**
	 * Class
	 */
	protected static string $class = 'IPS\downloads\File';

	/**
	* Init the widget
	*
	* @return	void
	*/
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'downloads', 'front' ) );
		\IPS\downloads\Application::outputCss();
		parent::init();
	}

	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
		{
			$options = array(
				'free'		=> 'file_free',
				'paid'		=> 'file_paid',
				'any'		=> 'any'
			);

			$form->add( new Radio( 'file_cost_type', $this->configuration['file_cost_type'] ?? 'any', TRUE, array( 'options'	=> $options ) ) );
		}

		return $form;
	}

	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere(): array
	{
		$where = parent::buildWhere();

		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
		{
			if( isset( $this->configuration['file_cost_type'] ) )
			{
				switch( $this->configuration['file_cost_type'] )
				{
					case 'free':
						$where[] = array( "( ( file_cost='' OR file_cost IS NULL ) AND ( file_nexus='' OR file_nexus IS NULL ) )" );
						break;
					case 'paid':
						$where[] = array( "( file_cost<>'' OR file_nexus>0 )" );
						break;
				}
			}
		}

		return $where;
	}
}