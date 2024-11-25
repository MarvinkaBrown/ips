<?php
/**
 * @brief		RSS Import extension: RssImport
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		09 Oct 2019
 */

namespace IPS\cms\extensions\core\RssImport;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Databases;
use IPS\cms\Fields;
use IPS\Content;
use IPS\core\Rss\Import;
use IPS\Db;
use IPS\Extensions\RssImportAbstract;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Http\Response;
use IPS\Image;
use IPS\Member;
use IPS\Node\Model;
use function count;
use function defined;
use function get_class;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	RSS Import extension: RssImport
 */
class RssImport extends RssImportAbstract
{
	/**
	 * @brief    Filestorage class
	 */
	public string $fileStorage = 'cms_Records';

	/**
	 * @brief    Enclosure images to process
	 */
	public static array $enclosures = array();

	/**
	 * Constructor
	 *
	 * @return    void
	 */
	public function __construct()
	{
		try
		{
			foreach ( Databases::databases() as $id => $database )
			{
				if ( $database->canImportRss() )
				{
					$this->classes[] = 'IPS\\cms\\Records' . $id;
				}
			}
		}
		catch ( Exception $e )
		{
		}
	}

	/**
	 * Show in the Admin CP?
	 *
	 * @return boolean
	 */
	public function showInAdminCp(): bool
	{
		return true;
	}

	/**
	 * Return available options for a Form\Select
	 *
	 * @return array
	 */
	public function availableOptions(): array
	{
		$options = array();
		try
		{
			foreach ( Databases::databases() as $id => $database )
			{
				if ( $database->canImportRss() )
				{
					$options['IPS\cms\Records' . $id] = $database->_title;
				}
			}
		}
		catch ( Exception $e )
		{
		} // If you have not upgraded pages but it is installed, this throws an error

		return $options;
	}

	/**
	 * Node selector options
	 *
	 * @param Import|null $rss Existing RSS object if editing|null if not
	 * @return array
	 */
	public function nodeSelectorOptions( ?Import $rss ): array
	{
		/* Get the correct class */
		$class = $rss->_class;
		$nodeClass = $class::$containerNodeClass;

		return array( 'class' => $nodeClass );
	}

	/**
	 * @param Import 	$rss 		RSS object
	 * @param array 				$article 	RSS feed article importing
	 * @param Model 		$container  Container object
	 * @param	string				$content	Post content with read more link if set
	 * @return Content|null
	 */
	public function create( Import $rss, array $article, Model $container, string $content ): ?Content
	{
		$settings = $rss->settings;
		$recordClass = $rss->_class;
		$member = Member::load( $rss->member );

		/* @var Fields $fieldsClass */
		$fieldsClass  = '\IPS\cms\Fields' . $recordClass::database()->id;
		$customFields = $fieldsClass::fields( $settings, 'add' );
		$fieldData = $fieldsClass::data();
		$linkedFields = [];

		$values = array(
			'content_field_' . $recordClass::database()->field_title =>  $rss->topic_pre . $article['title'],
			'content_field_' . $recordClass::database()->field_content => $content,
			'record_member_id' => $member
		);

		try
		{
			$record = $recordClass::createFromForm( $values, $container );
			
			$record->changeAuthor( $member, FALSE );

			if ( ! $settings['record_open'] )
			{
				$record->record_locked = 1;
			}

			if ( $settings['record_hide'] )
			{
				$record->record_approved = -1;
			}
			else
			{
				$record->record_approved = 1;
			}

			/* Any custom fields? */
			foreach( $settings as $k => $v )
			{
				if ( mb_substr( $k, 0, 6 ) == 'field_' )
				{
					$id = mb_substr( $k, 6 );

					/* Make sure field still exists */
					if ( isset( $customFields[ $id ] ) )
					{
						$record->$k = $v;
					}
					
					if( isset( $fieldData[ $id ] ) and $fieldData[ $id ]->type == 'Item' )
					{
						$linkedFields[] = $fieldData[ $id ];
					}
				}
			}

			$record->record_saved = $article['date']->getTimestamp();
			$record->record_updated = $article['date']->getTimestamp();
			$record->record_last_comment = $article['date']->getTimestamp();
			$record->record_publish_date = $article['date']->getTimestamp();

			if ( isset( static::$enclosures[ $article['guid'] ] ) )
			{
				$record->record_image = (string) static::$enclosures[ $article['guid'] ];

				$fixedFieldSettings = $recordClass::database()->fixed_field_settings;

				if ( isset( $fixedFieldSettings['record_image']['thumb_dims'] ) )
				{
					$record->record_image_thumb = (string) static::$enclosures[$article['guid']]->thumbnail( 'cms_Records', $fixedFieldSettings['record_image']['thumb_dims'][0], $fixedFieldSettings['record_image']['thumb_dims'][1] );
				}
				else
				{
					$record->record_image_thumb = $record->record_image;
				}
			}
			else if ( isset( $article['attachment'] ) )
			{
				Db::i()->insert( 'core_attachments_map', array(
					'attachment_id' => $article['attachment']['attach_id'],
					'location_key' => 'cms_Records' . $recordClass::database()->id,
					'id1' => $record->primary_id_field,
					'id3' => $recordClass::database()->id,
				) );
			}

			$record->save();
			
			/* Process Linked Fields */
			foreach( $linkedFields as $field )
			{
				$record->processItemFieldData( $field );
			}

			return $record;
		}
		catch( Exception $e )
		{
			return NULL;
		}
	}

	/**
	 * Process the enclosure
	 *
	 * @param Import $rss
	 * @param Response $response
	 * @param array $article
	 * @return bool
	 */
	public function processEnclosure( Import $rss, Response $response, array $article ): bool
	{
		$settings = $rss->settings;
		$recordClass = $rss->_class;

		if ( empty( $settings['record_image'] ) )
		{
			return FALSE;
		}

		try
		{
			$image = Image::create( $response );
			$fixedFieldSettings = $recordClass::database()->fixed_field_settings;

			$dims = NULL;
			if ( isset( $fixedFieldSettings['record_image']['image_dims'] ) and $fixedFieldSettings['record_image']['image_dims'][0] > 0 )
			{
				$dims = array('maxWidth' => $fixedFieldSettings['record_image']['image_dims'][0], 'maxHeight' => $fixedFieldSettings['record_image']['image_dims'][1]);
			}

			if ( $dims !== NULL )
			{
				$image->resizeToMax( $fixedFieldSettings['record_image']['image_dims'][0], $fixedFieldSettings['record_image']['image_dims'][1] );
			}

			static::$enclosures[ $article['guid'] ] = File::create( $this->fileStorage, 'rssImage-' . $article['guid'] . '.' . $image->type, (string)$image );

			return TRUE;
		}
		catch( Exception $e )
		{
			return FALSE;
		}
	}

	/**
	 * Addition Form elements
	 *
	 * @param Form $form	The form
	 * @param	Import|null		$rss	Existing RSS object
	 * @return	void
	 */
	public function form( Form $form, ?Import $rss=null ) : void
	{
		$settings = $rss->settings;
		$recordClass = $rss->_class;
		$nodeClass = $recordClass::$containerNodeClass;

		/* @var Fields $fieldsClass */
		$fieldsClass  = '\IPS\cms\Fields' . $recordClass::database()->id;
		$customFields = $fieldsClass::fields( $settings, 'add', $rss->node_id ? $nodeClass::load( $rss->node_id ) : NULL );

		$form->add( new Radio( 'rss_import_record_open', ( $settings ? $settings['record_open'] : 1 ), FALSE, array( 'options' => array( 1 => 'unlocked', 0 => 'locked' ) ) ) );
		$form->add( new Radio( 'rss_import_record_hide', ( $settings ? $settings['record_hide'] : 0 ), FALSE, array( 'options' => array( 0 => 'unhidden', 1 => 'hidden' ) ) ) );

		if ( $rss->has_enclosures and $fieldsClass::fixedFieldFormShow( 'record_image' ) )
		{
			$form->add( new Radio( 'rss_import_record_image', ( $settings ? $settings['record_image'] : 1 ), FALSE, array( 'options' => array( 1 => 'rss_import_record_image_header', 0 => 'rss_import_record_image_inline' ) ) ) );
		}

		if ( count( $customFields ) )
		{
			$fields = array();
			foreach( $customFields as $id => $field )
			{
				if ( $id == $recordClass::database()->field_title or $id ==  $recordClass::database()->field_content )
				{
					continue;
				}

				$fields[] = $field;
			}

			if ( count( $fields ) )
			{
				$form->addHeader('rss_import_cms_defaults');
				$form->addMessage('rss_import_cms_defaults_desc', 'i-color_soft');

				foreach( $fields as $f )
				{
					$form->add( $f );
				}
			}
		}
	}

	/**
	 * Process additional fields unique to this extension
	 *
	 * @param array $values	Values from form
	 * @param Import $rss	Existing RSS object
	 * @return	array
	 */
	public function saveForm( array &$values, Import $rss ): array
	{
		$return = array(
			'record_open' => $values['rss_import_record_open'],
			'record_hide' => $values['rss_import_record_hide']
		);

		unset( $values['rss_import_record_open'], $values['rss_import_record_hide'] );

		if ( isset( $values['rss_import_record_image'] ) )
		{
			$return['record_image'] = $values['rss_import_record_image'];

			unset( $values['rss_import_record_image'] );
		}

		$recordClass = $rss->_class;
		$fieldsClass  = '\IPS\cms\Fields' . $recordClass::database()->id;
		$customValues = array();

		foreach( $values as $k => $v )
		{
			if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
			{
				$customValues[ $k ] = $v;
				unset( $values[ $k ] );
			}
		}

		/* @var Fields $fieldsClass */
		$customFields = $fieldsClass::fields( $customValues, 'add' );

		foreach( $customFields as $key => $field )
		{
			if ( $key == $recordClass::database()->field_title or $key == $recordClass::database()->field_content )
			{
				continue;
			}

			$key = 'field_' . $key;

			if ( isset( $customValues[ $field->name ] ) and get_class( $field ) == 'IPS\Helpers\Form\Upload' )
			{
				if ( is_array( $customValues[ $field->name ] ) )
				{
					$items = array();
					foreach( $customValues[ $field->name ] as $obj )
					{
						$items[] = (string) $obj;
					}
					$return[ $key ] = implode( ',', $items );
				}
				else
				{
					$return[ $key ] = (string) $customValues[ $field->name ];
				}
			}
			/* If we're using decimals, then the database field is set to DECIMALS, so we cannot using stringValue() */
			else if ( isset( $customValues[ $field->name ] ) and get_class( $field ) == 'IPS\Helpers\Form\Number' and ( isset( $field->options['decimals'] ) and $field->options['decimals'] > 0 ) )
			{
				$return[ $key ] = $field->value;
			}
			else
			{
				$return[ $key ] = $field::stringValue($customValues[$field->name] ?? NULL);
			}
		}

		return $return;
	}
}