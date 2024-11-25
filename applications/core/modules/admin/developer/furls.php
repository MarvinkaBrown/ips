<?php
/**
 * @brief		furls
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		05 Feb 2024
 */

namespace IPS\core\modules\admin\developer;

use InvalidArgumentException;
use IPS\Application;
use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Tree\Tree;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * furls
 */
class furls extends Controller
{
	/**
	 * @var bool
	 */
	public static bool $csrfProtected = true;

	/**
	 * @var array
	 */
	protected array $json = [];

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$this->json = $this->_getFurls();

		$tree = new Tree(
			$this->url,
			'dev_furls',
			array( $this, '_getFurlKeys' ),
			array( $this, '_getFurlRow' ),
			function( $folder ){
				return null;
			},
			null,
			function(){
				return array(
					'add' => array(
						'icon'	=> 'plus',
						'title'	=> 'add',
						'link'	=> $this->url->setQueryString( 'do', 'form' )
					),
					'top' => array(
						'icon' => 'edit',
						'title' => 'dev_furl_toplevel_edit',
						'link' => $this->url->setQueryString( 'do', 'topLevel' ),
						'data' => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'dev_furl_toplevel_edit' ) )
					)
				);
			},
			true
		);

		Output::i()->headerMessage = Theme::i()->getTemplate( 'global' )->message( 'dev_furls_info', 'info i-margin-bottom_1' );

		Output::i()->output = (string) $tree;
	}

	/**
	 * FURL Roots
	 *
	 * @return array
	 */
	public function _getFurlKeys() : array
	{
		$roots = [];
		foreach( $this->json['pages'] as $key => $data )
		{
			/* Replace all underscores because otherwise the sortable JS does not pick up the correct key */
			$roots[ str_replace( '_', '|', $key ) ] = $this->_getFurlRow( $key, $data );
		}
		return $roots;
	}

	/**
	 * FURL Rows
	 *
	 * @param string $key
	 * @param array|null $data
	 * @return string
	 */
	public function _getFurlRow( string $key, ?array $data ) : string
	{
		$key = str_replace( '|', '_', $key );
		if( empty( $data ) )
		{
			$data = $this->json[ 'pages' ][ $key ] ?? array();
		}

		$allKeys = array_keys( $this->json['pages'] );
		$position = array_search( $key, $allKeys ) + 1;

		return Theme::i()->getTemplate( 'trees' )->row(
			$this->url,
			str_replace( '_', '|', $key ),
			$key,
			false,
			array(
				'edit' => array(
					'icon' => 'pencil',
					'title' => 'edit',
					'link' => $this->url->setQueryString( array( 'do' => 'form', 'key' => $key ) )
				),
				'delete' => array(
					'icon' => 'times-circle',
					'title' => 'delete',
					'link' => $this->url->setQueryString( array( 'do' => 'delete', 'key' => $key ) )->csrf(),
					'data' => array( 'confirm' => '' )
				)
			),
			Theme::i()->getTemplate( 'developer', 'core' )->furlRowDescription( $data, $this->json['topLevel'] ),
			null,
			$position,
			false,
			null,
			null,
			null,
			false,
			true
		);
	}

	/**
	 * Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$rows = [];
		$this->json = $this->_getFurls();
		foreach( $this->json['pages'] as $key => $data )
		{
			if( stripos( $data['friendly'], Request::i()->input ) !== false or ( isset( $data['alias'] ) and stripos( $data['alias'], Request::i()->input ) !== false ) or stripos( $data['real'], Request::i()->input ) !== false )
			{
				$rows[] = $this->_getFurlRow( $key, $data );
			}
		}

		Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}

	/**
	 * @return void
	 */
	protected function reorder() : void
	{
		$json = $this->_getFurls();
		$originalList = $json['pages'];
		$json['pages'] = [];
		foreach( Request::i()->ajax_order as $key => $empty )
		{
			$key = str_replace( '|', '_', $key );
			$json['pages'][ $key ] = $originalList[ $key ];
		}

		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/furl.json", $json );
	}

	/**
	 * @return void
	 */
	protected function form() : void
	{
		$jsonFile = ROOT_PATH . "/applications/{$this->application->directory}/data/furl.json";
		$json = $this->_getFurls();

		$furlKey = Request::i()->key ?? null;
		$row = ( $furlKey !== null and isset( $json['pages'][ $furlKey ] ) ) ? $json['pages'][ $furlKey ] : array();

		$self = $this;
		$form = new Form;
		$form->add( new Text( 'dev_furl_key', $furlKey, true, array(), function( $val ) use ( $self, $furlKey, $json ){
			/* Check the current app first, but only if we're adding a new key */
			if( $furlKey === null and isset( $json['pages'][ $val ] ) )
			{
				throw new InvalidArgumentException( "err__dev_duplicate_furl_key" );
			}

			/* Now loop through all other apps and check for duplicate keys */
			foreach( Application::applications() as $application )
			{
				if( $application->directory != $self->application->directory )
				{
					$furls = $self->parseFurlsFile( ROOT_PATH . "/applications/{$application->directory}/data/furl.json" );
					if( isset( $furls['pages'][ $val ] ) )
					{
						throw new InvalidArgumentException( "err__dev_duplicate_furl_key" );
					}
				}
			}
		} ) );


		$form->add( new Text( 'dev_furl_friendly', $row['friendly'] ?? null, true, array(), function( $val ) use ( $self, $json ){
			/* Do we have a duplicate in this app? */
			foreach( $json['pages'] as $key => $data )
			{
				if( $data['friendly'] == $val and $key != Request::i()->dev_furl_key )
				{
					throw new InvalidArgumentException( "err__dev_duplicate_furl_friendly" );
				}
			}

			/* Check other apps for duplicates, this time including the topLevel in the test */
			$val = ( $json['topLevel'] ? $json['topLevel'] . '/' : '' ) . $val;
			foreach( Application::applications() as $application )
			{
				if( $application->directory != $self->application->directory )
				{
					$furls = $self->parseFurlsFile( ROOT_PATH . "/applications/{$application->directory}/data/furl.json" );
					if( isset( $furls['pages'] ) )
					{
						foreach( $furls['pages'] as $key => $data )
						{
							$test = ( $furls['topLevel'] ? $furls['topLevel'] . '/' : '' ) . $data['friendly'];
							if( $test == $val )
							{
								throw new InvalidArgumentException( "err__dev_duplicate_furl_friendly" );
							}
						}
					}
				}
			}
		}, ( $json['topLevel'] ? $json['topLevel'] . '/' : '' ) ) );
		$form->add( new Text( 'dev_furl_real', $row['real'] ?? null, true ) );
		$form->add( new Text( 'dev_furl_verify', $row['verify'] ?? null, false ) );
		$form->add( new Text( 'dev_furl_alias', $row['alias'] ?? null, false, array(), function( $val ) use ( $self, $furlKey, $json ){
			if( $val )
			{
				/* Does the alias match the FURL? */
				if( $val == Request::i()->dev_furl_friendly )
				{
					throw new InvalidArgumentException( 'err__dev_equal_alias' );
				}

				/* Check the current app */
				foreach( $json['pages'] as $k => $v )
				{
					if( $k != $furlKey and $v['alias'] == $val )
					{
						throw new InvalidArgumentException( "err__dev_duplicate_alias" );
					}
				}

				/* Now loop through all other apps and check for duplicate aliases */
				foreach( Application::applications() as $application )
				{
					if( $application->directory != $self->application->directory )
					{
						$furls = $self->parseFurlsFile( ROOT_PATH . "/applications/{$application->directory}/data/furl.json" );
						foreach( $furls['pages'] as $k => $v )
						{
							if( $v['alias'] == $val )
							{
								throw new InvalidArgumentException( "err__dev_duplicate_alias" );
							}
						}
					}
				}
			}
		} ) );
		$form->add( new YesNo( 'dev_furl_seopagination', $row['seoPagination'] ?? false, false ) );

		$form->addHtml( Theme::i()->getTemplate( 'forms' )->blurb( 'dev_furl_seotitle_class_info', true, true ) );
		$form->add( new Text( 'dev_furl_seotitle_class', isset( $row['seoTitles'] ) ? mb_substr( $row['seoTitles'][0]['class'], 5 ) : null, false, array(), function( $val ){
			if( empty( $val ) and ( Request::i()->dev_furl_seotitle_id or Request::i()->dev_furl_seotitle_property ) )
			{
				throw new InvalidArgumentException( 'form_required' );
			}
		}, 'IPS\\' ) );
		$form->add( new Text( 'dev_furl_seotitle_id', isset( $row['seoTitles'] ) ? $row['seoTitles'][0]['queryParam'] : null, false, array(), function( $val ){
			if( empty( $val ) and ( Request::i()->dev_furl_seotitle_class or Request::i()->dev_furl_seotitle_property ) )
			{
				throw new InvalidArgumentException( 'form_required' );
			}
		} ) );
		$form->add( new Text( 'dev_furl_seotitle_property', isset( $row['seoTitles'] ) ? $row['seoTitles'][0]['property'] : null, false, array(), function( $val ){
			if( empty( $val ) and ( Request::i()->dev_furl_seotitle_class or Request::i()->dev_furl_seotitle_id ) )
			{
				throw new InvalidArgumentException( 'form_required' );
			}
		} ) );

		if( $values = $form->values() )
		{
			/* If we changed the key, remove the old one */
			if( $furlKey !== null and $values['dev_furl_key'] != $furlKey )
			{
				unset( $json['pages'][ $furlKey ] );
			}

			$data = array(
				'friendly' => $values['dev_furl_friendly'],
				'real' => $values['dev_furl_real']
			);

			if( $values['dev_furl_verify'] )
			{
				$data['verify'] = $values['dev_furl_verify'];
			}

			if( $values['dev_furl_alias'] )
			{
				$data['alias'] = $values['dev_furl_alias'];
			}

			if( $values['dev_furl_seopagination'] )
			{
				$data['seoPagination'] = true;
			}

			/* Check for SEO Title */
			if( $values['dev_furl_seotitle_class'] and $values['dev_furl_seotitle_id'] and $values['dev_furl_seotitle_property'] )
			{
				$data['seoTitles'] = [
					[
						'class' => '\\IPS\\' . $values['dev_furl_seotitle_class'],
						'queryParam' => $values['dev_furl_seotitle_id'],
						'property' => $values['dev_furl_seotitle_property']
					]
				];
			}

			$json['pages'][ $values['dev_furl_key'] ] = $data;

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

		$json = $this->_getFurls();
		if( isset( $json['pages'][ Request::i()->key ] ) )
		{
			unset( $json['pages'][ Request::i()->key ] );
		}

		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/furl.json", $json );

		Output::i()->redirect( $this->url );
	}

	/**
	 * Change the top-level for the FURLs of this app
	 *
	 * @return void
	 */
	protected function topLevel() : void
	{
		$jsonFile = ROOT_PATH . "/applications/{$this->application->directory}/data/furl.json";
		$json = $this->_getFurls();

		$form = new Form;
		$form->add( new Text( 'dev_furl_toplevel', $json['topLevel'], false ) );
		if( $values = $form->values() )
		{
			$json['topLevel'] = $values['dev_furl_toplevel'];
			$this->_writeJson( $jsonFile, $json );

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * Load and parse the FURL json file.
	 * We need to account for the very long illegal comments in there
	 *
	 * @return array
	 */
	protected function _getFurls() : array
	{
		$file = ROOT_PATH . "/applications/{$this->application->directory}/data/furl.json";
		if( !file_exists( $file ) )
		{
			return array(
				'topLevel' => '',
				'pages' => array()
			);
		}

		return $this->parseFurlsFile( $file );
	}
}