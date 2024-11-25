<?php
/**
 * @brief		Editor Extension: Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Feb 2018
 */

namespace IPS\core\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Text\Parser;
use IPS\Widget as WidgetClass;
use LogicException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Widget, Used for all the core widgets which need an editor
 */
class Widget extends EditorLocationsAbstract
{
	/**
	 * Array containing all widgets utilizing this Extension
	 */
	protected static array $widgets = array(
		'guestSignUp',
		'newsletter',
	);

	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	Member					$member	The member
	 * @param	Editor	$field	The editor field
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( Member $member, Editor $field ): ?bool
	{
		return NULL;
	}
	
	/**
	 * Permission check for attachments
	 *
	 * @param	Member	$member		The member
	 * @param	int|null	$id1		Primary ID
	 * @param	int|null	$id2		Secondary ID
	 * @param	string|null	$id3		Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @param	bool		$viewOnly	If true, just check if the user can see the attachment rather than download it
	 * @return	bool
	 */
	public function attachmentPermissionCheck( Member $member, ?int $id1, ?int $id2, ?string $id3, array $attachment, bool $viewOnly=FALSE ): bool
	{
		return TRUE;
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return	Url|Content|Model|Member|null
	 * @throws	LogicException
	 */
	public function attachmentLookup( int $id1=NULL, int $id2=NULL, string $id3=NULL ): Model|Content|Url|Member|null
	{
		return null;
	}

	/**
	 * Rebuild content post-upgrade
	 *
	 * @param	int|null	$offset	Offset to start from
	 * @param	int|null	$max	Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildContent( ?int $offset, ?int $max ): int
	{
		return $this->performRebuild( $offset, $max, array( 'IPS\Text\LegacyParser', 'parseStatic' ) );
	}

	/**
	 * Rebuild content to add or remove image proxy
	 *
	 * @param	int|null		$offset		Offset to start from
	 * @param	int|null		$max		Maximum to parse
	 * @param	bool			$proxyUrl	Use the cached image URL instead of the original URL
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildImageProxy( ?int $offset, ?int $max, bool $proxyUrl = FALSE ): int
	{
		$callback = function( $value ) use ( $proxyUrl ) {
			return Parser::removeImageProxy( $value, $proxyUrl );
		};
		return $this->performRebuild( $offset, $max, $callback );
	}

	/**
	 * Rebuild content to add or remove lazy loading
	 *
	 * @param	int|null		$offset		Offset to start from
	 * @param	int|null		$max		Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildLazyLoad( ?int $offset, ?int $max ): int
	{
		return $this->performRebuild( $offset, $max, [ 'IPS\Text\Parser', 'parseLazyLoad' ] );
	}

	/**
	 * Perform rebuild - abstracted as the call for rebuildContent() and rebuildAttachmentImages() is nearly identical
	 *
	 * @param	int|null	$offset		Offset to start from
	 * @param	int|null	$max		Maximum to parse
	 * @param	callable	$callback	Method to call to rebuild content
	 * @return	int			Number completed
	 */
	protected function performRebuild( ?int $offset, ?int $max, callable $callback ): int
	{
		$did = 0;

		$areas = array( 'core_widget_areas' );
		if ( Application::appIsEnabled('cms') )
		{
			$areas[] = 'cms_page_widget_areas';
		}

		foreach ( $areas as $table )
		{
			foreach ( Db::i()->select( '*', $table ) as $area )
			{
				$did++;

				$widgetsColumn = $table == 'core_widget_areas' ? 'widgets' : 'area_widgets';
				$whereClause = $table == 'core_widget_areas' ? array( 'id=? AND area=?', $area['id'], $area['area'] ) : array( 'area_page_id=? AND area_area=?', $area['area_page_id'], $area['area_area'] );

				$widgets = json_decode( $area[ $widgetsColumn ], TRUE );
				$update = FALSE;

				foreach ( $widgets as $k => $widget )
				{
					if ( in_array( $widget['key'], static::$widgets ) )
					{
						$appOrPlugin = Application::load( $widget['app'] );

						$widgetObject = WidgetClass::load( $appOrPlugin, $widget['key'], $widget['unique'] );
						$key = $widgetObject::$editorKey;
						$string =$widgetObject::$editorLangKey;

						if ( isset( $widget['configuration'][ $key ] ) AND is_array( $widget['configuration'][ $key ] ) )
						{
							foreach( $widget['configuration'][ $key ] as $contentKey => $content )
							{
								if( $rebuilt = $this->_rebuildWidgetContent( $content, $callback ) )
								{
									$widgets[ $k ]['configuration'][ $key ][ $contentKey ] = $rebuilt;
									$update = TRUE;
								}
							}
						}
						elseif ( isset( $widget['configuration'][ $key ] ) )
						{
							$rebuilt = $this->_rebuildWidgetContent( $widget['configuration'][ $key ], $callback );

							if( $rebuilt !== NULL )
							{
								$widgets[ $k ]['configuration'][ $key ] = $rebuilt;
								$update = TRUE;
							}
						}

						if ( $update )
						{
							/* Rebuild language bits */
							foreach( Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', $string ), 'word_id ASC', array( $offset, $max ) ) as $word )
							{
								$rebuilt = $this->_rebuildWidgetContent( $word['word_custom'], $callback );
								Db::i()->update( 'core_sys_lang_words', array( 'word_custom' => $rebuilt ), array( 'word_id=?', $word['word_id'] ) );
							}
						}

					}
				}
				if ( $update )
				{
					Db::i()->update( $table, array( $widgetsColumn => json_encode( $widgets ) ), $whereClause );
				}
			}
		}

		return $did;
	}

	/**
	 * Total content count to be used in progress indicator
	 *
	 * @return	int			Total Count
	 */
	public function contentCount(): int
	{
		$count	= 0;

		$areas = array( 'core_widget_areas' );
		if ( Application::appIsEnabled('cms') )
		{
			$areas[] = 'cms_page_widget_areas';
		}

		foreach ( $areas as $table )
		{
			foreach ( Db::i()->select( '*', $table ) as $area )
			{
				$widgetsColumn = $table == 'core_widget_areas' ? 'widgets' : 'area_widgets';

				$widgets = isset( $area[ $widgetsColumn ] ) ? json_decode( $area[ $widgetsColumn ], TRUE ) : [];

				foreach ( $widgets as $k => $widget )
				{
					if ( in_array( $widget['key'], static::$widgets ) )
					{
						$count++;
					}
				}
			}
		}

		return $count;
	}

	/**
	 * Rebuild Widget Content
	 *
	 * @param 	string		$content	Content to rebuild
	 * @param	callable	$callback	Method to call to rebuild content
	 * @return	string|null				Rebuilt content
	 */
	protected function _rebuildWidgetContent( string $content, callable $callback ) : ?string
	{
		$rebuilt = NULL;
		try
		{
			if( !empty( $content ) )
			{
				$rebuilt = $callback( $content );
			}
		}
		catch( InvalidArgumentException $e )
		{
			if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
			{
				$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $content );
			}
			else
			{
				throw $e;
			}
		}

		return $rebuilt;
	}
}