<?php
/**
 * @brief		webhooks Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Apr 2020
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Output;
use IPS\Settings;
use IPS\Task;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * webhooks Task
 */
class webhooks extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws    Task\Exception
	 */
	public function execute() : mixed
	{
		/* Get our webhooks */
		$webhooks = iterator_to_array( Db::i()->select( '*', 'core_api_webhooks', array('enabled=1') )->setKeyField('id') );
		
		/* Run through the queue */		
		$this->runUntilTimeout( function() use ( $webhooks ) {
			/* Try and get a queue item */
			try
			{
				$fire = Db::i()->select( '*', 'core_api_webhook_fires', array( "status='pending'" ), '`time` ASC', 1, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
				
				try
				{
					if ( !isset( $webhooks[ $fire['webhook'] ] ) )
					{
						throw new DomainException("Webhook Disabled");
					}

					Output::i()->parseFileObjectUrls( $fire['data'] );

					$request = Url::external( $webhooks[ $fire['webhook'] ]['url'] )
						->request()
						->setHeaders(
							[
								'User-Agent' => "InvisionCommunity/" . Application::load('core')->long_version ,
								'Webhook-Event' => $fire['event'],
								'Content-Type' => $webhooks[ $fire['webhook'] ]['content_type']
							]
						);

					if( !empty( $webhooks[ $fire['webhook'] ]['api_key'] ) )
					{
						$request = $request->login( $webhooks[ $fire['webhook'] ]['api_key'], '' );
					}

					$response = $request->post( $fire['data'] );

					if ( Settings::i()->webhook_logs_success )
					{
						Db::i()->update( 'core_api_webhook_fires', [
							'status'			=> 'successful',
							'response_code'	=> $response->httpResponseCode,
							'response_body'	=> $response->content
						], [ 'id=?', $fire['id'] ] );
					}
					else
					{
						Db::i()->delete( 'core_api_webhook_fires', [ 'id=?', $fire['id'] ] );
					}
				}
				catch ( Exception $e )
				{
					$update = [
						'response_code'	=> NULL,
						'response_body'	=> IPS::getExceptionDetails( $e ),
						'fails'			=> $fire['fails'] + 1,
					];
					if ( $fire['fails'] >= ( Settings::i()->webhooks_allowed_fails - 1 ) )
					{
						$update['status'] = 'failed';
					}
					
					if ( isset( $update['status'] ) and $update['status'] === 'failed' and !Settings::i()->webhook_logs_fail )
					{
						Db::i()->delete( 'core_api_webhook_fires', [ 'id=?', $fire['id'] ] );
					}
					else
					{
						Db::i()->update( 'core_api_webhook_fires', $update, [ 'id=?', $fire['id'] ] );
					}
				}
				
				return TRUE;
			}
			/* If there's no queue items left, disable this task and return */
			catch ( UnderflowException $e )
			{				
				$this->enabled = FALSE;
				$this->save();
				return FALSE;
			}
		});

		return null;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}