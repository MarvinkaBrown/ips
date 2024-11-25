<?php
/**
 * @brief		Search settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Apr 2014
 */

namespace IPS\core\modules\admin\discovery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Content\Search\Index;
use IPS\Data\Store;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function floatval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search settings
 */
class search extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'search_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		/* Rebuild button */
		Output::i()->sidebar['actions'] = array(
			'rebuildIndex'	=> array(
				'title'		=> 'search_rebuild_index',
				'icon'		=> 'undo',
				'link'		=> Url::internal( 'app=core&module=discovery&controller=search&do=queueIndexRebuild' )->csrf(),
				'data'		=> array( 'confirm' => '', 'confirmSubMessage' => Member::loggedIn()->language()->get('search_rebuild_index_confirm') ),
			),
		);
		
		$form = new Form;
		$form->addHeader('search_method');
		$form->add( new Radio( 'search_method', Settings::i()->search_method, FALSE, array(
			'options' => array(
				'mysql'		=> 'search_method_mysql',
				'elastic'	=> 'search_method_elastic'
			),
			'toggles' => array(
				'mysql'		=> array( 'search_index_timeframe' ),
				'elastic'	=> array( 'search_elastic_server', 'search_elastic_index', 'search_elastic_analyzer', 'search_decay', 'search_elastic_self_boost', 'search_index_maxresults' )
			)
		) ) );
		$form->add( new FormUrl( 'search_elastic_server', Settings::i()->search_elastic_server, NULL, array( 'placeholder' => 'http://localhost:9200' ), function( $val )
		{
			if( Request::i()->search_method != 'elastic' )
			{
				return;
			}

			if( !( $val instanceof Url ) )
			{
				throw new DomainException('form_url_error');
			}

			try
			{
				$response = \IPS\Content\Search\Elastic\Index::request( $val )->get()->decodeJson();
			}
			catch ( Exception $e )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack('search_elastic_server_error', FALSE, array( 'sprintf' => array( $e->getMessage() ) ) ) );
			}
			if ( !isset( $response['version']['number'] ) )
			{
				throw new DomainException('search_elastic_server_no_version');
			}

			/* Open search is compatible up to at least version 2 */
			if( isset( $response['version']['distribution'] ) and $response['version']['distribution'] == 'opensearch' )
			{
				if ( version_compare( $response['version']['number'], '2.2', '>=' ) )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack('search_opensearch_server_unsupported_version', FALSE, array( 'sprintf' => array( $response['version']['number'] ) ) ) );
				}
			}
			else
			{
				if ( version_compare( $response['version']['number'], \IPS\Content\Search\Elastic\Index::MINIMUM_VERSION, '<' ) )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack('search_elastic_server_unsupported_version', FALSE, array( 'sprintf' => array( \IPS\Content\Search\Elastic\Index::MINIMUM_VERSION, $response['version']['number'] ) ) ) );
				}
				if ( version_compare( $response['version']['number'], \IPS\Content\Search\Elastic\Index::UNSUPPORTED_VERSION, '>=' ) )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack('search_elastic_server_unsupported_version_high', FALSE, array( 'sprintf' => array( \IPS\Content\Search\Elastic\Index::UNSUPPORTED_VERSION, $response['version']['number'] ) ) ) );
				}
			}
		}, NULL, NULL, 'search_elastic_server' ) );
		Member::loggedIn()->language()->words['search_elastic_server_desc'] = sprintf( Member::loggedIn()->language()->get('search_elastic_server_desc'), \IPS\Content\Search\Elastic\Index::MINIMUM_VERSION, \IPS\Content\Search\Elastic\Index::UNSUPPORTED_VERSION );
		$form->add( new Text( 'search_elastic_index', Settings::i()->search_elastic_index, NULL, array( 'regex' => '/^[A-Z0-9_]*$/i' ), NULL, NULL, NULL, 'search_elastic_index' ) );
		$form->add( new Select( 'search_elastic_analyzer', Settings::i()->search_elastic_analyzer, FALSE, array(
			'options' => array(
				'language'		=> array(
					'arabic'		=> 'elastic_analyzer_arabic',
					'armenian'		=> 'elastic_analyzer_armenian',
					'basque'		=> 'elastic_analyzer_basque',
					'brazilian'		=> 'elastic_analyzer_brazilian',
					'bulgarian' 	=> 'elastic_analyzer_bulgarian',
					'catalan'		=> 'elastic_analyzer_catalan',
					'cjk'			=> 'elastic_analyzer_cjk',
					'czech' 		=> 'elastic_analyzer_czech',
					'danish' 		=> 'elastic_analyzer_danish',
					'dutch' 		=> 'elastic_analyzer_dutch',
					'english' 		=> 'elastic_analyzer_english',
					'finnish' 		=> 'elastic_analyzer_finnish',
					'french' 		=> 'elastic_analyzer_french',
					'galician' 		=> 'elastic_analyzer_galician',
					'german' 		=> 'elastic_analyzer_german',
					'greek' 		=> 'elastic_analyzer_greek',
					'hindi' 		=> 'elastic_analyzer_hindi',
					'hungarian' 	=> 'elastic_analyzer_hungarian',
					'indonesian' 	=> 'elastic_analyzer_indonesian',
					'irish' 		=> 'elastic_analyzer_irish',
					'italian' 		=> 'elastic_analyzer_italian',
					'latvian' 		=> 'elastic_analyzer_latvian',
					'lithuanian' 	=> 'elastic_analyzer_lithuanian',
					'norwegian' 	=> 'elastic_analyzer_norwegian',
					'persian' 		=> 'elastic_analyzer_persian',
					'portuguese' 	=> 'elastic_analyzer_portuguese',
					'romanian' 		=> 'elastic_analyzer_romanian',
					'russian' 		=> 'elastic_analyzer_russian',
					'sorani' 		=> 'elastic_analyzer_sorani',
					'spanish' 		=> 'elastic_analyzer_spanish',
					'swedish' 		=> 'elastic_analyzer_swedish',
					'turkish' 		=> 'elastic_analyzer_turkish',
					'thai' 			=> 'elastic_analyzer_thai',
				),
				'other'			=> array(
					'standard'		=> 'elastic_analyzer_standard',
					'custom'		=> 'elastic_analyzer_custom',
				)
			),
			'toggles'	=> array(
				'custom'	=> array( 'search_elastic_custom_analyzer_row' )
			)
		), NULL, NULL, NULL, 'search_elastic_analyzer' ) );
		$form->add( new Codemirror(
			'search_elastic_custom_analyzer',
			Settings::i()->search_elastic_custom_analyzer ?: "\t\"analyzer\": {\n\t\t\"my_custom_analyzer\": {\n\t\t\t\"type\": \"custom\",\n\t\t\t\"char_filter\": [\n\t\t\t\t\"emoticons\" \n\t\t\t],\n\t\t\t\"tokenizer\": \"punctuation\", \n\t\t\t\"filter\": [\n\t\t\t\t\"lowercase\",\n\t\t\t\t\"english_stop\" \n\t\t\t]\n\t\t}\n\t},\n\t\"tokenizer\": {\n\t\t\"punctuation\": { \n\t\t\t\"type\": \"pattern\",\n\t\t\t\"pattern\": \"[ .,!?]\"\n\t\t}\n\t},\n\t\"char_filter\": {\n\t\t\"emoticons\": { \n\t\t\t\"type\": \"mapping\",\n\t\t\t\"mappings\": [\n\t\t\t\t\":) => _happy_\",\n\t\t\t\t\":( => _sad_\"\n\t\t\t]\n\t\t}\n\t},\n\t\"filter\": {\n\t\t\"english_stop\": { \n\t\t\t\"type\": \"stop\",\n\t\t\t\"stopwords\": \"_english_\"\n\t\t}\n\t}",
			NULL,
			array( 'codeModeAllowedLanguages' => [ 'json' ] ),
			function( $val ) {
				$json = json_decode( '{' . $val . '}', TRUE );
				if ( $json === NULL or !isset( $json['analyzer'] ) or count( $json['analyzer'] ) !== 1 )
				{
					throw new DomainException( 'search_elastic_custom_analyzer_error' );
				}
			},
			'<code>"analysis": {</code>',
			'<code>}</code>',
			'search_elastic_custom_analyzer_row'
		) );
		$form->addHeader('search_options');
		$form->add( new Radio( 'search_default_operator', Settings::i()->search_default_operator, FALSE, array( 'options' => array(
			'or'	=> 'search_default_operator_or',
			'and'	=> 'search_default_operator_and',
		) ), NULL, Member::loggedIn()->language()->addToStack('search_default_operator_prefix') ) );
		$form->add( new Number( 'search_title_boost', Settings::i()->search_title_boost, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'search_title_boost_unlimited' ), NULL, Member::loggedIn()->language()->addToStack('search_title_boost_prefix'), Member::loggedIn()->language()->addToStack('search_title_boost_suffix'), 'search_title_boost' ) );
		$form->add( new Custom( 'search_decay', array( Settings::i()->search_decay_days, Settings::i()->search_decay_factor ), FALSE, array(
			'getHtml' => function( $field ) {
				return Theme::i()->getTemplate( 'settings' )->searchDecay( $field->value[0] ?? 0, $field->value[1] ?? 0 );
			}
		), NULL, NULL, NULL, 'search_decay' ) );
		$form->add( new Number( 'search_elastic_self_boost', Settings::i()->search_elastic_self_boost, FALSE, array( 'unlimited' => floatval( 0 ), 'unlimitedLang' => 'do_not_boost', 'decimals' => 1 ), NULL, Member::loggedIn()->language()->addToStack('search_elastic_self_boost_prefix'), Member::loggedIn()->language()->addToStack('search_elastic_self_boost_suffix'), 'search_elastic_self_boost' ) );
		$form->add( new Number( 'search_index_timeframe', Settings::i()->search_index_timeframe, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'search_index_timeframe_unlimited' ), NULL, Member::loggedIn()->language()->addToStack('search_index_timeframe_prefix'), Member::loggedIn()->language()->addToStack('search_index_timeframe_suffix'), 'search_index_timeframe' ) );
		$form->add( new Number( 'search_index_maxresults', Settings::i()->search_index_maxresults, FALSE, array(), NULL, NULL, NULL, 'search_index_maxresults' ) );


		$form->addHeader('search_logs');
		$groups	= array_combine( array_keys( Group::groups( TRUE, TRUE ) ), array_map( function( $_group ) { return (string) $_group; }, Group::groups( TRUE, TRUE ) ) );
		$form->add( new CheckboxSet( 'searchlog_exclude_groups', json_decode( Settings::i()->searchlog_exclude_groups, TRUE ), FALSE, array( 'options' => $groups, 'multiple' => true ), NULL, NULL, NULL, 'searchlog_exclude_groups' ) );

		if ( $values = $form->values() )
		{
			$engine = Settings::i()->search_method;
			$indexPrune = Settings::i()->search_index_timeframe;
			$analyzer = Settings::i()->search_elastic_analyzer;
			$customAnalyzer = Settings::i()->search_elastic_custom_analyzer;
			$maxResults = Settings::i()->search_index_maxresults;
			
			if ( isset( $values['search_decay'][2] ) )
			{
				$values['search_decay_days'] = 0;
				$values['search_decay_factor'] = 0;
			}
			else
			{
				$values['search_decay_days'] = $values['search_decay'][0];
				$values['search_decay_factor'] = $values['search_decay'][1];
			}
			unset( $values['search_decay'] );
			
			if( $engine != $values['search_method'] )
			{
				try
				{
					Index::i()->prune();
				}
				catch( Exception $e )
				{
					Log::log( $e, 'search_index_prune' );
				}
			}

			$values['searchlog_exclude_groups'] = json_encode( $values['searchlog_exclude_groups'] );

			/* Go ahead and save... */
			$form->saveAsSettings( $values );
			Session::i()->log( 'acplogs__search_settings' );
			
			/* And re-index if setting updated */
			if( $engine != $values['search_method'] or ( $values['search_method'] == 'elastic' and ( $values['search_elastic_analyzer'] != $analyzer or ( $values['search_elastic_analyzer'] == 'custom' and $values['search_elastic_custom_analyzer'] != $customAnalyzer ) or $values['search_index_maxresults'] != $maxResults ) ) )
			{
				/* We pass TRUE to the i() method to ensure we get a new instance, otherwise the old instance cached from the previous prune call will be used */
				Index::i( TRUE )->init();
				Index::i()->rebuild();
			}
			elseif( $indexPrune != $values['search_index_timeframe'] )
			{				
				Index::i()->rebuild();
			}
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_discovery_search');
		Output::i()->output	.= Theme::i()->getTemplate( 'global' )->block( 'menu__core_discovery_search', $form );
	}
	
	/**
	 * Queue an index rebuild
	 *
	 * @return	void
	 */
	protected function queueIndexRebuild() : void
	{
		Session::i()->csrfCheck();
		
		/* Clear MySQL minimum word length cached value */
		unset( Store::i()->mysqlMinWord );
		unset( Store::i()->mysqlMaxWord );

		Index::i()->rebuild();
	
		Session::i()->log( 'acplogs__queued_search_index' );
		Output::i()->redirect( Url::internal( 'app=core&module=discovery&controller=search' ), 'search_index_rebuilding' );
	}
}