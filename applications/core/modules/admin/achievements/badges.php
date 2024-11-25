<?php
/**
 * @brief		Achievement badges
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Feb 2021
 */

namespace IPS\core\modules\admin\achievements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Rule;
use IPS\core\CustomBadge;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
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
use OutOfRangeException;
use function count;
use function defined;
use function mb_substr;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement badges
 */
class badges extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\core\Achievements\Badge';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'badges_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if( $data = Rule::getRebuildProgress() )
		{
			Output::i()->output .= Theme::i()->getTemplate( 'achievements' )->rebuildProgress( $data, TRUE );
		}

		Output::i()->sidebar['actions']['export'] = array(
			'primary' => false,
			'icon' => 'cloud-download',
			'link' => Url::internal('app=core&module=achievements&controller=badges&do=exportForm'),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acp_achievements_export') ),
			'title' => 'acp_achievements_export',
		);

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'achievements', 'badges_manage' ) )
		{
			Output::i()->sidebar['actions']['import'] = array(
				'primary' => false,
				'icon' => 'cloud-upload',
				'link' => Url::internal('app=core&module=achievements&controller=badges&do=importForm'),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acp_achievements_import') ),
				'title' => 'acp_achievements_import',
			);
		}

		Output::i()->output .= Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'cheev_badges_blurb' );

		parent::manage();
	}

	/**
	 * Fetch any additional HTML for this row
	 *
	 * @param	object	$node	Node returned from $nodeClass::load()
	 * @return	NULL|string
	 */
	public function _getRowHtml( object $node ): ?string
	{
		$count = Db::i()->select( 'COUNT(*)', 'core_member_badges', [ 'badge=?', $node->id ] )->first();
		
		$url = Url::internal("app=core&module=achievements&controller=badges&do=view&id={$node->id}");
		
		return Theme::i()->getTemplate('achievements')->memberCount( $url, $count );
	}
	
	/**
	 * View who has earned a badge
	 *
	 * @return	void
	 */
	public function view() : void
	{
		$badge = Badge::load( Request::i()->id );
		
		$table = new \IPS\Helpers\Table\Db( 'core_member_badges', Url::internal("app=core&module=achievements&controller=badges&do=view&id={$badge->id}"), [ 'badge=?', $badge->id ] );
		$table->joins[] = [
			'select'	=> 'action,identifier',
			'from'		=> 'core_achievements_log',
			'where'		=> 'core_achievements_log.id=core_member_badges.action_log',
			'type'		=> 'LEFT'
		];
		$table->langPrefix = 'badge_log_';
		$table->include = [ 'member', 'action_log', 'rule', 'datetime' ];
		$table->sortBy = $table->sortBy ?: 'datetime';

		$table->advancedSearch = array(
			'datetime'	=> SEARCH_DATE_RANGE,
			);

		$table->parsers = array(
			'member'	=> function ( $val, $row )
			{
				return Theme::i()->getTemplate( 'global', 'core' )->userLink( Member::load( $val ) );
			},
			'action_log'	=> function( $val, $row ) {
				try
				{
					if ( $row['action'] )
					{
						$exploded = explode( '_', $row['action'] );
						$extension = Application::load( $exploded[0] )->extensions( 'core', 'AchievementAction' )[ $exploded[1] ];
						return $extension->logRow( $row['identifier'], explode( ',', $row['actor'] ) );
					}
					else
					{
						throw new OutOfRangeException;
					}
				}
				catch( OutOfRangeException $e )
				{
					return Member::loggedIn()->language()->addToStack('unknown');
				}
			},
			'rule'	=> function( $val, $row ) {
				$rule = NULL;
				if ( $val )
				{
					try
					{
						$rule = Rule::load( $val );
					}
					catch ( OutOfRangeException $e ) { }
				}
				
				if ( $rule )
				{
					return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( Url::internal("app=core&module=achievements&controller=rules&do=form&id={$rule->id}"), FALSE, $rule->extension()->ruleDescription( $rule ), TRUE, FALSE, FALSE, TRUE );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('unknown');
				}
			},
			'datetime'	=> function( $val )
			{
				return DateTime::ts( $val );
			}
		);
		
		Output::i()->title		= $badge->_title;
		Output::i()->output	= $table;
	}

	/**
	 * Import dialog
	 *
	 * @return void
	 */
	public function exportForm() : void
	{
		Dispatcher::i()->checkAcpPermission( 'badges_manage' );
		$assignedBadges = Badge::getAssignedBadgeIds();

		$where = NULL;
		if ( count( $assignedBadges ) )
		{
			$where = [ Db::i()->in( '`id`', $assignedBadges, TRUE ) ];
		}

		$exportableBadges = Db::i()->select( 'COUNT(*)', 'core_badges', $where )->first();

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'achievements' )->badgeExport( $exportableBadges );
	}

	/**
	 * Export ranks with images as an XML file (XML is better at potentially large values from raw image data)
	 *
	 * @return void
	 * @throws Exception
	 */
	public function export() : void
	{
		$xml = SimpleXML::create('badges');

		$langs = [];
		foreach( Db::i()->select('word_key, word_default', 'core_sys_lang_words', [ "lang_id=? AND word_key LIKE 'core_badges_%'", Member::loggedIn()->language()->id ] ) as $row )
		{
			$langs[ mb_substr( $row['word_key'], 12 ) ] = $row['word_default'];
		}

		$assignedBadges = Badge::getAssignedBadgeIds();

		$where = NULL;
		if ( count( $assignedBadges ) )
		{
			$where = [ Db::i()->in( '`id`', $assignedBadges, TRUE ) ];
		}

		/* Ranks */
		foreach ( Db::i()->select( '*', 'core_badges', $where ) as $badge )
		{
			try
			{
				$icon = File::get( 'core_Badges', $badge['image'] );

				$forXml = [
					'manually_awarded' => $badge['manually_awarded'],
					'title' => $langs[ $badge['id'] ],
					'icon_name' => $icon->originalFilename,
					'icon_data' => base64_encode( $icon->contents() )
				];

				$xml->addChild( 'badge', $forXml );
			}
			catch( Exception $e ) { }
		}
		
		Session::i()->log( 'acplogs__exported_badges' );

		Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', "Achievement_Badges.xml" ) ) );
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
			Badge::importXml( Request::i()->file, ( ! empty( Request::i()->wipe ) ) );
		}
		catch( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '2C422/1', 403, '' );
		}
		
		Session::i()->log( 'acplogs__imported_badges' );

		Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=badges' ), 'completed' );
	}

	/**
	 * Import dialog
	 *
	 * @return void
	 */
	public function importForm() : void
	{
		$form = new Form( 'form', 'acp_achievements_import' );

		$form->add( new YesNo( 'acp_achievements_import_option_badge_wipe', 0, FALSE, [], NULL, NULL, NULL, 'acp_achievements_import_option_rule_wipe' ) );
		$form->add( new Upload( 'acp_achievements_import_xml', NULL, FALSE, [ 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ], NULL, NULL, NULL, 'acp_achievements_import_xml' ) );

		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['acp_achievements_import_xml'], $tempFile );

			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=badges&do=import' )->setQueryString( array('wipe' => $values['acp_achievements_import_option_badge_wipe'], 'file' => $tempFile, 'key' => md5_file( $tempFile )) )->csrf() );
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( Member::loggedIn()->language()->addToStack('acp_achievements_import'), $form, FALSE );
	}


	public function badgePreview() : void
	{
		header('content-type: image/svg+xml');
		echo new CustomBadge(
			Request::i()->shape ?: 'circle',
			Request::i()->foreground ?: '#fff',
			Request::i()->background ?: '#eeb95f',
			Request::i()->border ?: '#f7d36f',
			Request::i()->icon ?: null,
			(int) Request::i()->rotation ?: 0,
			(int) Request::i()->sides ?: 5,
			(int) Request::i()->numberoverlay ?: 0
		);
		exit();
	}
}