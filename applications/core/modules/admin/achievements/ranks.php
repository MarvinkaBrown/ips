<?php
/**
 * @brief		Achievement ranks
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Apr 2013
 */

namespace IPS\core\modules\admin\achievements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\Achievements\Rank;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Xml\SimpleXML;
use function count;
use function defined;
use function intval;
use function mb_substr;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement ranks
 */
class ranks extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * @brief	Cached counts
	 */
	public static array $counts = [];
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\core\Achievements\Rank';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'ranks_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		static::$counts = [];
		$previousCount = NULL;
		
		$allRanks = array_values( Rank::getStore() );
		$totalRanks = count( $allRanks );
		for ( $i = 0; $i < $totalRanks; $i++ )
		{
			$where = [];
			$where[] = [ 'achievements_points>=' . intval( $allRanks[ $i ]->points ) ];
			if ( isset( $allRanks[ $i + 1 ] ) )
			{
				$where[] = [ 'achievements_points<' . intval( $allRanks[ $i + 1 ]->points ) ];
			}
			static::$counts[ $allRanks[ $i ]->id ] = Db::i()->select( 'COUNT(*)', 'core_members', $where )->first();
		}

		if( $data = Rule::getRebuildProgress() )
		{
			Output::i()->output .= Theme::i()->getTemplate( 'achievements' )->rebuildProgress( $data, TRUE );
		}

		Output::i()->sidebar['actions']['export'] = array(
			'primary' => false,
			'icon' => 'cloud-download',
			'link' => Url::internal('app=core&module=achievements&controller=ranks&do=export'),
			'data'	=> [],
			'title' => 'acp_achievements_export',
		);

		Output::i()->sidebar['actions']['import'] = array(
			'primary' => false,
			'icon' => 'cloud-upload',
			'link' => Url::internal('app=core&module=achievements&controller=ranks&do=importForm'),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acp_achievements_import') ),
			'title' => 'acp_achievements_import',
		);

		Output::i()->output .= Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'cheev_ranks_blurb' );

		parent::manage();
	}

	/**
	 * Import dialog
	 *
	 * @return void
	 */
	public function importForm() : void
	{
		$form = new Form( 'form', 'acp_achievements_import' );

		$form->add( new Radio( 'acp_achievements_import_option', 'replace', FALSE, [ 'options' =>
		[
			'wipe' => 'acp_achievements_import_option_wipe',
			'replace' => 'acp_achievements_import_option_replace'
		],
			'toggles' => [ 'wipe' => [ 'acp_achievements_import_option_wipe_sure' ] ]
		] ) );

		$form->add( new YesNo( 'acp_achievements_import_option_wipe_sure', 0, FALSE, [], NULL, NULL, NULL, 'acp_achievements_import_option_wipe_sure' ) );
		$form->add( new Upload( 'acp_achievements_import_xml', NULL, FALSE, [ 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ], NULL, NULL, NULL, 'acp_achievements_import_xml' ) );

		if ( $values = $form->values() )
		{
			if ( $values['acp_achievements_import_option'] == 'wipe' and empty( $values['acp_achievements_import_option_wipe_sure'] ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'acp_achievements_import_error' );
			}
			else
			{
				/* Move it to a temporary location */
				$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
				move_uploaded_file( $values['acp_achievements_import_xml'], $tempFile );

				/* Initate a redirector */
				Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=ranks&do=import' )->setQueryString( array('option' => $values['acp_achievements_import_option'], 'file' => $tempFile, 'key' => md5_file( $tempFile )) )->csrf() );
			}
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( Member::loggedIn()->language()->addToStack('acp_achievements_import'), $form, FALSE );
	}

	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	public function import() : void
	{
		Session::i()->csrfCheck();

		if ( !file_exists( Request::i()->file ) or md5_file( Request::i()->file ) !== Request::i()->key )
		{
			Output::i()->error( 'generic_error', '3C130/1', 500, '' );
		}

		try
		{
			Rank::importXml( Request::i()->file, Request::i()->option );
		}
		catch( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '2C423/1', 403, '' );
		}
		
		Session::i()->log( 'acplogs__imported_ranks' );
		
		Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=ranks' ), 'completed' );
	}

	/**
	 * Export ranks with images as an XML file (XML is better at potentially large values from raw image data)
	 *
	 * @return void
	 * @throws Exception
	 */
	public function export() : void
	{
		$xml = SimpleXML::create('ranks');
		$langs = [];

		foreach( Db::i()->select('word_key, word_default', 'core_sys_lang_words', [ "lang_id=? AND word_key LIKE 'core_member_rank_%'", Member::loggedIn()->language()->id ] ) as $row )
		{
			$langs[ mb_substr( $row['word_key'], 17 ) ] = $row['word_default'];
		}

		/* Ranks */
		foreach ( Db::i()->select( '*', 'core_member_ranks') as $row )
		{
			$forXml = [
				'title'  => $langs[$row['id']] ?? NULL,
				'points' => $row['points']
			];

			if ( $row['icon'] )
			{
				try
				{
					$icon = File::get( 'core_Ranks', $row['icon'] );
					$forXml['icon_name'] = $icon->originalFilename;
					$forXml['icon_data'] = base64_encode( $icon->contents() );
				}
				catch( Exception $e ) { }
			}

			$xml->addChild( 'rank', $forXml );
		}
		
		Session::i()->log( 'acplogs__exported_ranks' );

		Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', "Achievement_Ranks.xml" ) ) );
	}

	/**
	 * Fetch any additional HTML for this row
	 *
	 * @param	object	$node	Node returned from $nodeClass::load()
	 * @return	NULL|string
	 */
	public function _getRowHtml( object $node ): ?string
	{
		$url = Url::internal("app=core&module=members&controller=members&advanced_search_submitted=1&members_achievements_points={$node->id}")->csrf();
		
		return Theme::i()->getTemplate('achievements')->memberCount( $url, static::$counts[ $node->id ] );
	}
}