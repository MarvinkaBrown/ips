<?php
/**
 * @brief		acpsearch
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		05 Feb 2024
 */

namespace IPS\core\modules\admin\developer;

use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * acpsearch
 */
class acpsearch extends Controller
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
		$json = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpsearch.json" );

		$data = array();
		foreach( $json as $k => $v )
		{
			$data[ $k ] = array(
				'controller' => $k,
				'lang' => $v['lang_key'],
				'keywords' => implode( ", ", $v['keywords'] ),
				'restriction' => $v['restriction']
			);
		}

		$table = new Custom( $data, $this->url );
		$table->quickSearch = 'keywords';
		$table->include = [ 'controller', 'keywords' ];
		$table->langPrefix = 'dev_acpsearch_';
		$table->parsers = [
			'controller' => function( $val, $row )
			{
				parse_str( $row['controller'], $params );
				if( ( isset( $params['app'] ) and isset( $params['module'] ) ) and ( !isset( $row['restriction'] ) or Member::loggedIn()->hasAcpRestriction( $params['app'], $params['module'], $row['restriction'] ) ) )
				{
					$title = "<a href='" . Url::internal( $row['controller'] ) . "' target='_blank'>" . Member::loggedIn()->language()->addToStack( $row['lang'] ) . "</a>";
				}

				$title = Member::loggedIn()->language()->addToStack( $row['lang'] );
				return $title . "<div class='i-font-size_-2 i-color_soft'>{$row['controller']}</div>";
			}
		];

		$table->rootButtons = array(
			'add' => array(
				'icon'	=> 'plus',
				'title'	=> 'add',
				'link'	=> $this->url->setQueryString( 'do', 'form' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
			)
		);

		$url = $this->url;
		$table->rowButtons = function( $row ) use ( $url )
		{
			return array(
				'view' => array(
					'icon' => 'search',
					'title' => 'view',
					'link' => Url::internal( $row['controller'] ),
					'target' => '_blank'
				),
				'edit' => array(
					'icon' => 'pencil',
					'title' => 'edit',
					'link' => $url->setQueryString( array( 'do' => 'form', 'key' => urlencode( $row['controller'] ) ) ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
				),
				'delete' => array(
					'icon' => 'times-circle',
					'title' => 'delete',
					'link' => $url->setQueryString( array( 'do' => 'delete', 'key' => urlencode( $row['controller'] ) ) )->csrf(),
					'data' => array( 'confirm' => '' )
				)
			);
		};

		Output::i()->output = (string) $table;
	}

	/**
	 * @return void
	 */
	protected function form() : void
	{
		$jsonFile = ROOT_PATH . "/applications/{$this->application->directory}/data/acpsearch.json";
		$json = $this->_getJson( $jsonFile );

		$controller = isset( Request::i()->key ) ? urldecode( Request::i()->key ) : null;
		$row = ( $controller !== null and isset( $json[ $controller ] ) ) ? $json[ $controller ] : array();

		$restrictions = array( '' => 'no_restriction' );
		foreach( $this->application->modules( 'admin' ) as $module )
		{
			$moduleKey = 'menu__' . $this->application->directory . '_' . $module->key;
			$moduleRestrictions = array();
			foreach( $this->_getRestrictions( $module ) as $group => $r )
			{
				foreach( $r as $k => $v )
				{
					$moduleRestrictions[ $k ] = $v;
				}
			}

			if( count( $moduleRestrictions ) )
			{
				$restrictions[ $moduleKey ] = $moduleRestrictions;
			}
		}

		$form = new Form;
		$form->add( new Text( 'dev_acpsearch_controller', $controller, true ) );
		$form->add( new Text( 'dev_acpsearch_lang', $row['lang_key'] ?? null, true ) );
		$form->add( new Select( 'dev_acpsearch_restriction', $row['restriction'] ?? null, false, array(
			'options' => $restrictions
		) ) );
		$form->add( new Form\Stack( 'dev_acpsearch_keywords', $row['keywords'] ?? null, true ) );

		if( $values = $form->values() )
		{
			$keywordData = [
				'lang_key' => $values['dev_acpsearch_lang'],
				'restriction' => $values['dev_acpsearch_restriction'] ?? null,
				'keywords' => $values['dev_acpsearch_keywords']
			];

			if( $controller == $values['dev_acpsearch_controller'] )
			{
				$json[ $controller ] = $keywordData;
			}
			else
			{
				$json[ $values['dev_acpsearch_controller'] ] = $keywordData;
				if( isset( $json[ $controller ] ) )
				{
					unset( $json[ $controller ] );
				}
			}

			$this->_writeJson( $jsonFile, $json );

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * @return void
	 */
	protected function delete() : void
	{
		Request::i()->confirmedDelete();

		$jsonFile = ROOT_PATH . "/applications/{$this->application->directory}/data/acpsearch.json";
		$json = $this->_getJson( $jsonFile );

		$controller = isset( Request::i()->key ) ? urldecode( Request::i()->key ) : null;

		if( isset( $json[ $controller ] ) )
		{
			unset( $json[ $controller ] );
		}

		$this->_writeJson( $jsonFile, $json );

		Output::i()->redirect( $this->url );
	}
}