<?php
/**
 * @brief		tags Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		05 Aug 2024
 */

namespace IPS\core\widgets;

use IPS\Content\Tag;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Member;
use IPS\Settings;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tags Widget
 */
class tags extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'tags';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

		$form->add( new Text( 'tag_block_title', $this->configuration['tag_block_title'] ?? Member::loggedIn()->language()->get( 'block_tags' ), true ) );

		$tags = iterator_to_array(
			Db::i()->select( 'tag_id, tag_text', 'core_tags_data', null, 'tag_text' )
				->setKeyField( 'tag_id' )
				->setValueField( 'tag_text' )
		);
		$form->add( new Select( 'tag_block_tags', $this->configuration['tag_block_tags'] ?? null, true, [
			'options' => $tags,
			'multiple' => true,
			'noDefault' => true
		] ) );

		$form->add( new Number( 'tag_block_limit', $this->configuration['tag_block_limit'] ?? 5, true ) );

 		return $form;
 	}

	/**
	 * @return string[]
	 */
	public function getSupportedLayouts() : array
	{
		$layouts = parent::getSupportedLayouts();

		/* Remove support for carousels; if we have tabs, there is way too much going on here */
		$return = [];
		foreach( $layouts as $k => $v )
		{
			if( !str_ends_with( $v, '-carousel' ) )
			{
				$return[] = $v;
			}
		}
		return $return;
	}


	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$data = [];
		$limit = $this->configuration['tag_block_limit'] ?? 5;
		$contentTypes = Tag::getTaggableContentTypes();

		foreach( ( $this->configuration['tag_block_tags'] ?? [] ) as $tag )
		{
			try
			{
				$tag = Tag::load( $tag );
			}
			catch( OutOfRangeException )
			{
				continue;
			}

			$items = [];
			foreach( Db::i()->select( '*', [ 'core_tags', 't' ], [
				[ 't.tag_text=?', $tag->text ],
				[ 'p.tag_perm_visible=?', 1 ],
				[ '(p.tag_perm_text=? or ' . Db::i()->findInSet( 'p.tag_perm_text', Member::loggedIn()->groups ) . ')', '*' ]
			], 't.tag_added desc', $limit )
						 ->join( [ 'core_tags_perms', 'p' ], 't.tag_aai_lookup=p.tag_perm_aai_lookup and t.tag_aap_lookup=p.tag_perm_aap_lookup' ) as $row )
			{
				foreach( $contentTypes as $k => $itemClass )
				{
					if( $itemClass::$application == $row['tag_meta_app'] and $itemClass::$module == $row['tag_meta_area'] )
					{
						try
						{
							$items[] = $itemClass::load( $row['tag_meta_id'] );
						}
						catch( OutOfRangeException ){}
						break;
					}
				}
			}

			if( count( $items ) )
			{
				$data[ $tag->text ] = $items;
			}
		}

		if( !count( $data ) )
		{
			return "";
		}

		$title = $this->configuration['tag_block_title'] ?? Member::loggedIn()->language()->get( 'block_tags' );
		return $this->output( $title, $data );
	}
}