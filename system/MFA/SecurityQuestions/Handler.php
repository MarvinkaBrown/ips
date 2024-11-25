<?php
/**
 * @brief		Multi Factor Authentication Handler for Security Questions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Sep 2016
 */

namespace IPS\MFA\SecurityQuestions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\core\modules\admin\settings\securityquestions;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Encrypt;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Multi Factor Authentication Handler for Security Questions
 */
class Handler extends MFAHandler
{	
	/**
	 * @brief	Key
	 */
	protected string $key = 'questions';
	
	/* !Setup */
	
	/**
	 * Handler is enabled
	 *
	 * @return	bool
	 */
	public function isEnabled(): bool
	{
		return Settings::i()->security_questions_enabled;
	}
		
	/**
	 * Member *can* use this handler (even if they have not yet configured it)
	 *
	 * @param	Member		$member		Member to check
	 * @return	bool
	 */
	public function memberCanUseHandler( Member $member ): bool
	{
		return Settings::i()->security_questions_groups == '*' or $member->inGroup( explode( ',', Settings::i()->security_questions_groups ) );
	}
	
	/**
	 * Member has configured this handler
	 *
	 * @param	Member		$member		Member to check
	 * @return	bool
	 */
	public function memberHasConfiguredHandler( Member $member ): bool
	{
		return $member->members_bitoptions['has_security_answers'];
	}
	
	/**
	 * Show a setup screen
	 *
	 * @param	Member		$member						The member
	 * @param	bool			$showingMultipleHandlers	Set to TRUE if multiple options are being displayed
	 * @param	Url	$url						URL for page
	 * @return	string
	 */
	public function configurationScreen( Member $member, bool $showingMultipleHandlers, Url $url ): string
	{
		$securityQuestions = array();
		foreach ( Question::roots() as $question )
		{
			$securityQuestions[ $question->id ] = $question->_title;
		}
				
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->securityQuestionsSetup( $securityQuestions, $showingMultipleHandlers );
	}
	
	/**
	 * Submit configuration screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	public function configurationScreenSubmit( Member $member ): bool
	{
		$answers = array();
		
		$isReconfiguring = $this->memberHasConfiguredHandler( $member );
		
		foreach ( Request::i()->security_question as $k => $v )
		{
			$answers[ $v ] = array(
				'answer_question_id'	=> $v,
				'answer_member_id'		=> $member->member_id,
				'answer_answer'			=> Encrypt::fromPlaintext( Request::i()->security_answer[ $k ] )->tag()
			);
		}
		
		if ( count( $answers ) >= Settings::i()->security_questions_number )
		{		
			Db::i()->delete( 'core_security_answers', array( 'answer_member_id=?', $member->member_id ) );
			Db::i()->insert( 'core_security_answers', $answers );
			
			$member->members_bitoptions['has_security_answers'] = TRUE;
			$member->save();

			/* Log MFA Enable */
			$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => TRUE, 'reconfigure' => $isReconfiguring ) );

			return TRUE;
		}
		
		return FALSE;
	}
	
	/* !Authentication */
	
	/**
	 * Get the form for a member to authenticate
	 *
	 * @param	Member		$member		The member
	 * @param	Url	$url		URL for page
	 * @return	string
	 */
	public function authenticationScreen( Member $member, Url $url ): string
	{
		try
		{
			$chosenAnswer = Db::i()->select( '*', 'core_security_answers', array( 'answer_member_id=? AND answer_is_chosen=1', $member->member_id ) )->first();
			$chosenQuestion = Question::load( $chosenAnswer['answer_question_id'] );
		}
		catch ( Exception $e )
		{
			$chosenQuestion = NULL;
			foreach ( Db::i()->select( '*', 'core_security_answers', array( 'answer_member_id=?', $member->member_id ), 'RAND()' ) as $chosenAnswer )
			{
				try
				{
					$chosenQuestion = Question::load( $chosenAnswer['answer_question_id'] );
					Db::i()->update( 'core_security_answers', array( 'answer_is_chosen' => 1 ), array( 'answer_member_id=? AND answer_question_id=?', $member->member_id, $chosenQuestion->id ) );
					break;
				}
				catch ( OutOfRangeException $e ) { }
			}
			if ( !$chosenQuestion )
			{
				return '';
			}
		}
		
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->securityQuestionsAuth( $chosenQuestion );
	}
	
	/**
	 * Submit authentication screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	public function authenticationScreenSubmit( Member $member ): bool
	{
		try
		{
			$authenticated = Request::i()->security_answer == Encrypt::fromTag( Db::i()->select( 'answer_answer', 'core_security_answers', array( 'answer_member_id=? AND answer_is_chosen=1', $member->member_id ) )->first() )->decrypt();
			
			if( $authenticated )
			{
				Db::i()->update( 'core_security_answers', array( 'answer_is_chosen' => 0 ), array( 'answer_member_id=? AND answer_is_chosen=?', $member->member_id, 1 ) );
			}

			return $authenticated;
		}
		catch ( UnderflowException $e )
		{
			return FALSE;
		}
	}
	
	/* !ACP */
	
	/**
	 * Toggle
	 *
	 * @param	bool	$enabled	On/Off
	 * @return	void
	 */
	public function toggle( bool $enabled ) : void
	{
		if( $enabled )
		{
			/* Check we have some questions */
			if ( !count( Question::roots() ) )
			{
				throw new DomainException( 'no_questions' );
			}
		}

		Settings::i()->changeValues( array( 'security_questions_enabled' => $enabled ) );
	}
	
	/**
	 * ACP Settings
	 *
	 * @return	string
	 */
	public function acpSettings(): string
	{
		/* Init */
		$activeTabContents = '';
		$tabs = array(
			'settings' 	=> 'security_question_settings',
			'questions'	=> 'security_questions_questions'
		);
		$activeTab = ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'handlers';
		
		/* Settings Form */
		if ( $activeTab === 'settings' )
		{
			$form = new Form;
			$form->add( new CheckboxSet( 'security_questions_groups', Settings::i()->security_questions_groups == '*' ? '*' : explode( ',', Settings::i()->security_questions_groups ), FALSE, array(
				'multiple'		=> TRUE,
				'options'		=> array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) ),
				'unlimited'		=> '*',
				'unlimitedLang'	=> 'everyone',
				'impliedUnlimited' => TRUE
			), NULL, NULL, NULL, 'security_questions_groups' ) );
			$form->add( new Radio( 'security_questions_prompt', Settings::i()->security_questions_prompt, FALSE, array( 'options' => array( 'register' => 'security_questions_prompt_register', 'optional' => 'security_questions_prompt_optional', 'access' => 'security_questions_prompt_access' ) ), NULL, NULL, NULL, 'security_questions_prompt' ) );
			$form->add( new Number( 'security_questions_number', Settings::i()->security_questions_number, FALSE, array( 'min' => 1, 'max' => Db::i()->select( 'COUNT(*)', 'core_security_questions' )->first() ?: NULL ), NULL, NULL, NULL, 'security_questions_number' ) );

			$form->addMessage('nexus_mfa_reset_answers');

			if ( $values = $form->values() )
			{
				$values['security_questions_groups'] = ( $values['security_questions_groups'] == '*' ) ? '*' : implode( ',', $values['security_questions_groups'] );
				$form->saveAsSettings( $values );			
				Session::i()->log( 'acplogs__mfa_handler_enabled', array( "mfa_questions_title" => TRUE ) );
				Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=mfa' ), 'saved' );
			}
			
			$activeTabContents = (string) $form;
		}
		
		/* Questions table */
		else
		{
			$controller = new securityquestions;
			$controller->execute();
			$activeTabContents = Output::i()->output;
		}
		
		
		/* Output */
		if( Request::i()->isAjax() )
		{
			return $activeTabContents;
		}
		else
		{
			return Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, Url::internal( "app=core&module=settings&controller=mfa&tab=handlers&do=settings&key=questions" ) );
		}
	}
	
	/**
	 * Configuration options when editing member account in ACP
	 *
	 * @param	Member			$member		The member
	 * @return	array
	 */
	public function acpConfiguration( Member $member ): array
	{
		$return = array();
		$return[] = new YesNo( "mfa_{$this->key}_title", $this->memberHasConfiguredHandler( $member ), FALSE, array( 'togglesOn' => array( 'security_question_matrix' ) ) );
		
		$securityQuestions = array();
		foreach (Question::roots() as $question )
		{
			$securityQuestions[ $question->id ] = $question->_title;
		}
		
		$matrix = new Matrix('security_question_matrix');
		$matrix->columns = array(
			'security_question_q'	=> function( $key, $value, $data ) use ( $securityQuestions )
			{
				return new Select( $key, $value, FALSE, array( 'options' => $securityQuestions ) );
			},
			'security_question_a'	=> function( $key, $value, $data )
			{
				return new Text( $key, $value );
			},
		);
		
		foreach ( $member->securityAnswers() as $questionId => $answer )
		{
			$matrix->rows[] = array(
				'security_question_q'	=> $questionId,
				'security_question_a'	=> Encrypt::fromTag( $answer )->decrypt()
			);
		}
		
		$return['security_answers'] = $matrix;
		
		return $return;
		
	}
	
	/**
	 * Save configuration when editing member account in ACP
	 *
	 * @param	Member		$member		The member
	 * @param array $values		Values from form
	 * @return	void
	 */
	public function acpConfigurationSave( Member $member, array $values ): void
	{		
		if ( isset( $values["mfa_{$this->key}_title"] ) and !$values["mfa_{$this->key}_title"] )
		{
			if ( $this->memberHasConfiguredHandler( $member ) )
			{
				$this->disableHandlerForMember( $member );
			}
			return;
		}
		
		Db::i()->delete( 'core_security_answers', array( 'answer_member_id=?', $member->member_id ) );
		
		$toInsert = array();
		
		foreach ( $values['security_question_matrix'] as $row )
		{
			if ( $row['security_question_a'] )
			{
				$toInsert[ $row['security_question_q'] ] = array(
					'answer_question_id'	=> $row['security_question_q'],
					'answer_member_id'		=> $member->member_id,
					'answer_answer'			=> Encrypt::fromPlaintext( $row['security_question_a'] )->tag()
				);
			}
		}
		
		if ( count( $toInsert ) )
		{
			Db::i()->insert( 'core_security_answers', $toInsert );
		}
		
		if ( count( $toInsert ) >= Settings::i()->security_questions_number )
		{
			$member->members_bitoptions['has_security_answers'] = TRUE;
		}
		else
		{
			$member->members_bitoptions['has_security_answers'] = FALSE;
		}
	}
	
	/* !Misc */
	
	/**
	 * If member has configured this handler, disable it
	 *
	 * @param	Member	$member	The member
	 * @return	void
	 */
	public function disableHandlerForMember( Member $member ) : void
	{
		Db::i()->delete( 'core_security_answers', array( 'answer_member_id=?', $member->member_id ) );
		$member->members_bitoptions['has_security_answers'] = FALSE;
		$member->save();

		/* Log MFA Disable */
		$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => FALSE ) );
	}
}