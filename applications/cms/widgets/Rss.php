<?php
/**
 * @brief		Rss Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		10 Dec 2014
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Text\Parser;
use IPS\Widget\Customizable;
use IPS\Widget\StaticCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Rss Widget
 */
class Rss extends StaticCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'Rss';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';

	/**
	 * Constructor
	 *
	 * @param	String				$uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param	null|string|array	$access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	null|string			$orientation			Orientation (top, bottom, right, left)
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation );
		if( isset( $this->configuration['block_rss_import_cache'] ) )
		{
			$this->cacheExpiration = $this->configuration['block_rss_import_cache'] * 60;
		}
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

		$form->add( new Text( 'block_rss_import_title', ( $this->configuration['block_rss_import_title'] ?? NULL ), TRUE ) );
		$form->add( new Form\Url( 'block_rss_import_url', ( $this->configuration['block_rss_import_url'] ?? NULL ), TRUE ) );
		$form->add( new Number( 'block_rss_import_number', ( $this->configuration['block_rss_import_number'] ?? 5 ), TRUE ) );
		$form->add( new Number( 'block_rss_import_cache', ( $this->configuration['block_rss_import_cache'] ?? 30 ), TRUE, array(), NULL, NULL, Member::loggedIn()->language()->addToStack('block_rss_import_cache_suffix') ) );

		return $form;
	}
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
 		$values['block_rss_import_url'] = (string) $values['block_rss_import_url'];

	    return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if ( ! isset( $this->configuration['block_rss_import_url'] ) )
		{
			return '';
		}

		$key = "cms_rss_import_" . md5( json_encode( $this->configuration ) );

		if ( isset( Store::i()->$key ) )
		{
			$cache = Store::i()->$key;

			if ( isset( $cache['time'] ) and isset( $cache['items'] ) and $cache['time'] > ( time() - ( $this->configuration['block_rss_import_cache'] * 60 ) ) )
			{
				return $this->output( $cache['items'], $this->configuration['block_rss_import_title'] );
			}
		}

		/* Still here? Best grab the data then */
		try
		{
			$request = Url::external( $this->configuration['block_rss_import_url'] )->request()->get();

			$i = 0;
			$items = array();
			if( $request )
			{
				foreach ( $request->decodeXml()->articles() as $guid => $article )
				{
					if ( isset( $article['title'] ) and isset( $article['link'] ) )
					{
						$items[ $guid ] = array(
							'title'   => $article['title'],
							'content' => Parser::parseStatic( $article['content'], NULL, new Member ),
							'link'    => (string) $article['link'],
							'date'    => ( $article['date'] instanceof DateTime ) ? $article['date']->getTimestamp() : $article['date']
						);
					}

					$i++;

					if ( $i >= $this->configuration['block_rss_import_number'] )
					{
						break;
					}
				}
			}
		}
		catch( Exception $e )
		{
			$items = array();
		}

		Store::i()->$key = array( 'time' => time(), 'items' => $items );

		return $this->output( $items, $this->configuration['block_rss_import_title'] );
	}
}