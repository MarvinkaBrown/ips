<?php
/**
 * @brief		Store and retrieve image notes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		21 Mar 2014
 * @note		This is available via javascript-only by the nature of the feature in question.
 * @note		This is a custom callback script for the jQuery-Notes plugin
 * @link		<a href='http://jquery-notes.rydygel.de/'>http://jquery-notes.rydygel.de/</a>
 */

namespace IPS\gallery\modules\front\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\gallery\Image;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Store and retrieve image notes
 */
class notes extends Controller
{
	/**
	 * @var Image|null
	 */
	protected ?Image $image = null;

	/**
	 * Determine what to show
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		try
		{
			$this->image = Image::load( Request::i()->imageId );
			
			if ( !$this->image->canView( Member::loggedIn() ) )
			{
				Output::i()->error( 'node_error', '2G191/1', 403, '' );
			}
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2G191/2', 404, '' );
		}

		$response	= NULL;

		/* jQuery-Notes will send 'image' parameter which we can ignore */
		$noteId		= Request::i()->id;
		$position	= Request::i()->position;
		$note		= strip_tags( Request::i()->note );

		if( Request::i()->get )
		{
			$response	= $this->image->_notes;
		}
		else if( Request::i()->add )
		{
			$response	= $this->_addNote( $position, $note );
		}
		else if( Request::i()->delete )
		{
			$response	= $this->_deleteNote( $noteId );
		}
		else if( Request::i()->edit )
		{
			$response	= $this->_editNote( $noteId, $position, $note );
		}

		Output::i()->json( $response );
	}

	/**
	 * Delete an image note
	 *
	 * @param int $id Note ID to delete
	 * @return bool|string
	 */
	protected function _deleteNote( int $id ): bool|string
	{
		/* Check permission */
		if( !$this->image->canEdit() )
		{
			Output::i()->error( 'notes_no_permission', '2G191/3', 403, '' );
		}

		Session::i()->csrfCheck();

		$notes	= $this->image->_notes;

		foreach( $notes as $key => $note )
		{
			if( $note['ID'] == $id )
			{
				unset( $notes[ $key ] );
				break;
			}
		}

		$this->image->_notes	= $notes;
		$this->image->save();

		return 'ok';
	}

	/**
	 * Add an image note
	 *
	 * @param	mixed	$position	Note position
	 * @param string $note		Note text
	 * @return	bool|array
	 */
	protected function _addNote( mixed $position, string $note ): array|bool
	{
		/* Check permission */
		if( !$this->image->canEdit() )
		{
			Output::i()->error( 'notes_no_permission', '2G191/4', 403, '' );
		}

		Session::i()->csrfCheck();

		$notes		= $this->image->_notes;
		$position	= explode( ',', $position );

		if( count( $position ) != 4 )
		{
			return FALSE;
		}

		$maxNoteId	= 0;

		foreach( $notes as $_note )
		{
			if( $_note['ID'] > $maxNoteId )
			{
				$maxNoteId	= $_note['ID'];
			}
		}

		$notes[]	= array(
			'ID'		=> $maxNoteId + 1,
			'LEFT'		=> round( $position[0], 5 ),
			'TOP'		=> round( $position[1], 5 ),
			'WIDTH'		=> round( $position[2], 5 ),
			'HEIGHT'	=> round( $position[3], 5 ),
			'NOTE'		=> trim( str_replace( "\n", ' ', str_replace( '  ', ' ', $note ) ) ),
		);

		$this->image->_notes	= $notes;
		$this->image->save();

		return array( 'id' => $maxNoteId + 1 );
	}

	/**
	 * Edit an image note
	 *
	 * @param int $id Note ID to edit
	 * @param mixed $position Note position
	 * @param string $noteText
	 * @return bool|string
	 */
	protected function _editNote(int $id, mixed $position='', string $noteText='' ): bool|string
	{
		/* Check permission */
		if( !$this->image->canEdit() )
		{
			Output::i()->error( 'notes_no_permission', '2G191/5', 403, '' );
		}

		Session::i()->csrfCheck();

		$notes		= $this->image->_notes;
		$position	= explode( ',', $position );

		foreach( $notes as $key => $note )
		{
			if( $note['ID'] == $id )
			{
				$notes[ $key ]	= $note;

				if( $noteText )
				{
					$notes[ $key ]['NOTE']		= trim( str_replace( "\n", ' ', str_replace( '  ', ' ', $noteText ) ) );
				}

				if( count( $position ) == 4 )
				{
					$notes[ $key ]['LEFT'] 		= round( $position[0], 5 );
					$notes[ $key ]['TOP']		= round( $position[1], 5 );
					$notes[ $key ]['WIDTH']		= round( $position[2], 5 );
					$notes[ $key ]['HEIGHT']	= round( $position[3], 5 );
				}

				break;
			}
		}

		$this->image->_notes	= $notes;
		$this->image->save();

		return 'ok';
	}
}