<?php
/**
 * @brief		Poll input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jan 2014
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\DateTime;
use IPS\Member;
use IPS\Output;
use IPS\Poll as SystemPoll;
use IPS\Settings;
use IPS\Theme;
use LengthException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Poll input class for Form Builder
 */
class Poll extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'allowPollOnly'	=> TRUE,	// Controls if "Allow poll only" option is allowed (if the admin has enabled it)
	 		'itemClass' => NULL, 		// The content item class attached to this poll
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'allowPollOnly'	=> FALSE,
	);

	/**
	 * @brief	Have we output the js yet?
	 */
	protected bool $jsSent	= FALSE;

	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$json	= array();
		
		if ( $this->value AND is_array( $this->value->choices ) AND count( $this->value->choices ) )
		{
			foreach ( $this->value->choices as $k => $choice )
			{
				$thisQuestion['title']			= $choice['question'];
				$thisQuestion['multiChoice']	= ( isset( $choice['multi'] ) and $choice['multi'] );
				
				$thisQuestion['choices']		= array();
				foreach ( $choice['choice'] as $i => $question )
				{
					$thisChoice['title']	= $question;
					$thisQuestion['choices'][ $i ] = $thisChoice;
				}
				
				$json[ $k ] = $thisQuestion;
			}
		}
				
		$json	= json_encode( $json );

		if( !$this->jsSent )
		{
		Output::i()->endBodyCode .= <<<EOF
		<script type='text/javascript'>
			ips.setSetting('pollData', {$json} )
		</script>
EOF;
			$this->jsSent	= TRUE;
		}
		
		/* Ensure poll JS is loaded */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_core.js', 'core' ) );
		
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->poll( $this->name, $this->value, $json, $this->options['allowPollOnly'] );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		/* Check we provided a value */
		$formValues = parent::getValue();
		
		/* Init object */
		if ( $this->defaultValue instanceof SystemPoll )
		{
			$poll = $this->defaultValue;
		}
		else
		{
			$poll = new SystemPoll;
			$poll->starter_id = Member::loggedIn()->member_id;

			if( isset( $this->options['itemClass'] ) )
			{
				$poll->poll_item_class = $this->options['itemClass'];
			}
		}

		/* Set values */
		$data =  parent::getValue();
		if ( !isset( $data['questions'] ) )
		{
			if ( isset( $data['fallback'] ) )
			{
				return $this->defaultValue;
			}
			
			return NULL;
		}
		$poll->setDataFromForm( $data, $this->options['allowPollOnly'] );
		if ( !$poll->poll_question )
		{
			if ( $poll->pid )
			{
				$poll->delete();
			}
			return NULL;
		}
				
		/* Return */
		return $poll;
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		/* Required? */
		if ( $this->value === NULL )
		{
			if ( $this->required )
			{
				throw new InvalidArgumentException('form_required');
			}
			else
			{
				return TRUE;
			}
		}

		if( mb_strlen( $this->value->poll_question ) > 255 )
		{
			throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('form_maxlength', FALSE, array( 'pluralize' => array( 255 ) ) ) );
		}

		/* Do we have at least one question? */
		if ( !count( $this->value->choices ) )
		{
			throw new InvalidArgumentException('form_poll_no_questions');
		}
		if ( count( $this->value->choices ) > Settings::i()->max_poll_questions )
		{
			throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('form_poll_too_many_questions', FALSE, array( 'pluralize' => array( Settings::i()->max_poll_questions ) ) ) );
		}
			
		/* Do all the questions have at least 2 options? */
		foreach ( $this->value->choices as $question )
		{
			 /* Do we have a question title? */
			if ( empty( trim( $question['question'] ) ) )
			{
				throw new InvalidArgumentException('form_poll_no_question_title');
			}
			if ( count( $question['choice'] ) < 2 )
			{
				throw new InvalidArgumentException('form_poll_too_few_answers');
			}
			if ( count( $question['choice'] ) > Settings::i()->max_poll_choices )
			{
				throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('form_poll_too_many_answers', FALSE, array( 'sprintf' => array( Settings::i()->max_poll_choices ) ) ) );
			}
		}

		/* Only validate if the timestamp is greater than zero */
		if( $this->value->poll_close_date instanceof DateTime )
		{
			/* Check maximum date */
			$maximum = DateTime::ts( 2147483647 );
			if ( $this->value->poll_close_date > $maximum )
			{
				throw new LengthException( Member::loggedIn()->language()->addToStack('form_date_max', FALSE, array( 'sprintf' => array( $maximum->localeDate() ) ) ) );
			}

			/* Check minimum date and time */
			$minimum = DateTime::ts( time() );
			if ( $this->value->poll_close_date < $minimum )
			{
				throw new LengthException( Member::loggedIn()->language()->addToStack('form_date_min', FALSE, array( 'sprintf' => array( $minimum->localeDate() . ' ' . $minimum->localeTime( FALSE ) ) ) ) );
			}
		}
		
		return TRUE;
	}
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return    string|int|null
	 */
	public static function stringValue( mixed $value ): string|int|null
	{
		return $value ? $value->pid : NULL;
	}
}