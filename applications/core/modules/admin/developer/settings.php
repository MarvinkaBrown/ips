<?php
/**
 * @brief		settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use IPS\Db;
use IPS\Developer\Controller;
use IPS\Helpers\Form\Matrix;
use IPS\IPS;
use IPS\Output;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * settings
 */
class settings extends Controller
{
	/**
	 * @var bool
	 */
	public static bool $csrfProtected = true;

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$settings = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/settings.json" );

		$form = new Matrix();
		$form->langPrefix = 'dev_settings_';
		$form->columns = array(
			'key'		=> array( 'Text', NULL, TRUE ),
			'default'	=> array( 'Text' ),
		);
		if ( in_array( $this->application->directory, IPS::$ipsApps ) )
		{
			$form->columns['report'] = array( 'Select', 'none', FALSE, array( 'options' => array(
				'none'	=> 'dev_settings_none',
				'full'	=> 'dev_settings_full',
				'bool'	=> 'dev_settings_bool',
			) ) );
		}
		$form->rows = $settings;

		if ( $form->values() !== FALSE )
		{
			$values = $form->values();

			if ( !empty( $form->changedRows ) )
			{
				foreach ( $form->changedRows as $key )
				{
					Db::i()->update( 'core_sys_conf_settings', array( 'conf_key' => $values[ $key ]['key'], 'conf_default' => $values[ $key ]['default'] ), array( 'conf_key=?', $form->rows[ $key ]['key'] ) );
				}
			}
			if ( !empty( $form->removedRows ) )
			{
				$delete = array();
				foreach ( $form->removedRows as $key )
				{
					$delete[] = $form->rows[ $key ]['key'];
				}

				Db::i()->delete( 'core_sys_conf_settings', Db::i()->in( 'conf_key', $delete ) );
			}

			/* Load existing keys before we add new ones, to prevent duplicates */
			$existingKeys = iterator_to_array(
				Db::i()->select( 'conf_key', 'core_sys_conf_settings', [ 'conf_app=?', $this->application->directory ] )
			);

			if ( !empty( $form->addedRows ) )
			{
				$insert = array();
				foreach ( $form->addedRows as $key )
				{
					if( !in_array( $values[ $key ]['key'], $existingKeys ) )
					{
						$insert[] = array( 'conf_key' => $values[ $key ]['key'], 'conf_value' => $values[ $key ]['default'], 'conf_default' => $values[ $key ]['default'], 'conf_app' => $this->application->directory );
					}
					else
					{
						/* If this is a duplicate, remove it from the values,
						otherwise it will add it to the JSON file */
						unset( $values[ $key ] );
					}
				}

				if( count( $insert ) )
				{
					Db::i()->insert( 'core_sys_conf_settings', $insert );
				}
			}

			$save = array();
			foreach ( $values as $data )
			{
				if ( $data['key'] )
				{
					$save[] = $data;
				}
			}

			usort( $save, function( $a, $b )
			{
				return strnatcmp( $a['key'], $b['key'] );
			} );

			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/settings.json", $save );
			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}
}