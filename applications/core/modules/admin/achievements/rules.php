<?php
/**
 * @brief		Achievement rules
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Feb 2021
 */

namespace IPS\core\modules\admin\achievements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\Achievements\Rule;
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
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Xml\SimpleXML;
use function count;
use function defined;
use function mb_substr;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement rules
 */
class rules extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\core\Achievements\Rule';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'rules_manage' );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'achievements/achievements.css', 'core', 'admin' ) );
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_members.js', 'core' ) );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$baseUrl = Url::internal('app=core&module=achievements&controller=rules');
		$perPage = 25;
		$totalCount = Db::i()->select( 'COUNT(*)', 'core_achievements_rules' )->first();
		$totalPages = ceil( $totalCount / $perPage );
		$page = Request::i()->page ?? 1;

		$query = Db::i()->select( '*', 'core_achievements_rules', NULL, 'action, milestone ASC', [ ( $perPage * ( $page - 1 ) ), $perPage ] );
		$rules = new ActiveRecordIterator( $query, 'IPS\core\Achievements\Rule' );
		$pagination = $totalPages > 1 ? Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl, $totalPages, $page, $perPage ) : '';
		
		if( Request::i()->isAjax() )
		{
			Output::i()->json([
				'rows'			=> Theme::i()->getTemplate( 'achievements' )->rulesListRows( $rules ),
				'pagination'	=> $pagination
			]);
		}
		else
		{
			Output::i()->sidebar['actions']['export'] = array(
				'primary' => false,
				'icon' => 'cloud-download',
				'link' => Url::internal('app=core&module=achievements&controller=rules&do=export'),
				'data'	=> [],
				'title' => 'acp_achievements_export',
			);

			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'achievements', 'rules_add' ) )
			{
				Output::i()->sidebar['actions']['import'] = array(
					'primary' => false,
					'icon' => 'cloud-upload',
					'link' => Url::internal('app=core&module=achievements&controller=rules&do=importForm'),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acp_achievements_import') ),
					'title' => 'acp_achievements_import',
				);
			}


			Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'cheev_rules_blurb' ) . Theme::i()->getTemplate( 'achievements' )->rulesList( $rules, $pagination, $this->_getRootButtons() );
		}
	}
	
	/**
	 * Get form
	 *
	 * @param	Form	$form	The form as returned by _addEditForm()
	 * @return	string
	 */
	protected function _showForm( Form $form ): string
	{
		Output::i()->hiddenElements = array('acpHeader');

		if ( Request::i()->id )
		{

			Dispatcher::i()->checkAcpPermission( 'rules_edit' );
			try
			{
				$form->hiddenValues['rule_enabled'] = Rule::load( Request::i()->id )->enabled;
			}
			catch( Exception $e ) { }
		}
		else
		{
			Dispatcher::i()->checkAcpPermission( 'rules_add' );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_achievements_rules');
		
		return $form->customTemplate( [ Theme::i()->getTemplate( 'achievements' ), 'rulesForm' ] );
	}

	/**
	 * Toggle enabled status
	 *
	 * @return void
	 */
	protected function toggleEnabled() : void
	{
		Dispatcher::i()->checkAcpPermission( 'rules_edit' );

		Session::i()->csrfCheck();

		try
		{
			$rule = Rule::load( Request::i()->id );
			$rule->enabled = (int) Request::i()->enable;
			$rule->save();
			
			if ( Request::i()->enable )
			{
				Session::i()->log( 'acplogs__rule_enabled', array( $rule->id => FALSE ) );
			}
			else
			{
				Session::i()->log( 'acplogs__rule_disabled', array( $rule->id => FALSE ) );
			}

			Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=rules' ), 'updated' );
		}
		catch( Exception $e )
		{
			Output::i()->error( 'node_error', '2T353/3', 404, '' ); /* @todo error code */
		}
	}

	/**
	 * Export rules with badges as an XML file (XML is better at potentially large values from raw image data)
	 *
	 * @return void
	 * @throws Exception
	 */
	public function export() : void
	{
		$xml = SimpleXML::create('rules');
		$badgeMap = [];

		$langs = [];
		foreach( Db::i()->select( 'word_key, word_default', 'core_sys_lang_words', [ 'lang_id=? AND (word_key LIKE \'core_award_subject_badge_%\' OR word_key LIKE \'core_award_other_badge_%\')', Member::loggedIn()->language()->id ] ) as $row )
		{
			$langs[ $row['word_key'] ] = $row['word_default'];
		}

		/* Rules */
		foreach ( Db::i()->select( '*', 'core_achievements_rules') as $row )
		{
			$forXml = [];
			foreach( $row as $k => $v )
			{
				if ( $k !== 'id' )
				{
					if ( $k == 'badge_subject' and $v )
					{
						if ( !isset( $badgeMap[$row['badge_subject']] ) )
						{
							$badgeMap[$row['badge_subject']] = uniqid();
						}

						$v = $badgeMap[$row['badge_subject']];
					}
					if ( $k == 'badge_other' and $v )
					{
						if ( !isset( $badgeMap[$row['badge_other']] ) )
						{
							$badgeMap[$row['badge_other']] = uniqid();
						}

						$v = $badgeMap[$row['badge_other']];
					}

					$forXml[$k] = $v;
				}
			}

			$forXml['award_subject_lang'] = $langs['core_award_subject_badge_' . $row['id']] ?? NULL;
			$forXml['award_other_lang'] = $langs['core_award_other_badge_' . $row['id']] ?? NULL;

			$xml->addChild( 'rule', $forXml );
		}

		/* Badges */
		if ( count( $badgeMap ) )
		{
			$langs = [];
			foreach( Db::i()->select('word_key, word_default', 'core_sys_lang_words', [ Db::i()->like( 'word_key', 'core_badges_') ] ) as $row )
			{
				$langs[ mb_substr( $row['word_key'], 12 ) ] = $row['word_default'];
			}

			foreach( Db::i()->select('*', 'core_badges', [ Db::i()->in( '`id`', array_keys( $badgeMap ) ) ] ) as $badge )
			{
				try
				{
					$icon = File::get( 'core_Badges', $badge['image'] );

					$xml->addChild( 'badge', [
						'manually_awarded' => $badge['manually_awarded'],
						'id' => $badgeMap[ $badge['id'] ],
						'title' => $langs[ $badge['id'] ],
						'icon_name' => $icon->originalFilename,
						'icon_data' => base64_encode( $icon->contents() )
					] );
				}
				catch( Exception $e ) { }
			}
		}
		
		Session::i()->log( 'acplogs__rules_exported' );

		Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', "Achievement_Rules.xml" ) ) );
	}

	/**
	 * Import dialog
	 *
	 * @return void
	 */
	public function importForm() : void
	{
		Dispatcher::i()->checkAcpPermission( 'rules_add' );
		$form = new Form( 'form', 'acp_achievements_import' );

		$form->addMessage( Member::loggedIn()->language()->addToStack( 'acp_achievements_import_rules_message'), 'ipsMessage ipsMessage--info');
		$form->add( new YesNo( 'acp_achievements_import_option_rule_wipe', 0, FALSE, [], NULL, NULL, NULL, 'acp_achievements_import_option_rule_wipe' ) );
		$form->add( new Upload( 'acp_achievements_import_xml', NULL, FALSE, [ 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ], NULL, NULL, NULL, 'acp_achievements_import_xml' ) );

		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['acp_achievements_import_xml'], $tempFile );

			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=rules&do=import' )->setQueryString( array('wipe' => $values['acp_achievements_import_option_rule_wipe'], 'file' => $tempFile, 'key' => md5_file( $tempFile )) )->csrf() );
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
		Dispatcher::i()->checkAcpPermission( 'rules_add' );
		Session::i()->csrfCheck();

		if ( !file_exists( Request::i()->file ) or md5_file( Request::i()->file ) !== Request::i()->key )
		{
			Output::i()->error( 'generic_error', '3C130/1', 500, '' );
		}

		try
		{
			Rule::importXml( Request::i()->file, ( ! empty( Request::i()->wipe ) ) );
		}
		catch( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '2C424/1', 403, '' );
		}
		
		Session::i()->log( 'acplogs__rules_imported' );

		Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=rules' ), 'completed' );
	}
}