<?php
/**
 * @brief		Webhook
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		5 Feb 2020
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\Form\WebhookSelector;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Node\Model;
use IPS\Theme;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Webhook
 */
class Webhook extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_api_webhooks';
	
	/**
	 * @brief	cache for get_url()
	 */
	protected mixed $_url = NULL;
	
	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array('webhooks');

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'api_webhooks';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	array(
	'app'		=> 'core',				// The application key which holds the restrictrions
	'module'	=> 'foo',				// The module key which holds the restrictions
	'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	'add'			=> 'foo_add',
	'edit'			=> 'foo_edit',
	'permissions'	=> 'foo_perms',
	'delete'		=> 'foo_delete'
	),
	'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'applications',
		'prefix' 	=> 'api_',
	);

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->content_type = 'application/x-www-form-urlencoded';
		$this->_data['filters'] = '{}';
		$this->_data['url'] = '';
		$this->_data['events'] = '';
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new FormUrl( 'url', $this->url ?? "", TRUE ) );

		if( in_array($this->content_type, ['application/x-www-form-urlencoded', 'application/json']))
		{
			$selectedHeader = $this->content_type;
			$customHeader = '';
		}
		else
		{
			$selectedHeader = 'custom';
			$customHeader = $this->content_type;
		}
		$form->add( new Select('webhook_content_type', $selectedHeader, TRUE , ['options' => ['application/x-www-form-urlencoded' => 'x-www-form-urlencoded', 'application/json' => 'application/json', 'custom' => 'Other'] , 'toggles'=> ['custom' => ['webhook_content_type_other'] ] ] ) );
		$form->add( new Text('webhook_content_type_other', $customHeader, FALSE , [], NULL, NULL, NULL, 'webhook_content_type_other' ) );
		$events = [];

		foreach (Webhook::getAvailableWebhooks() as $app => $hooks )
		{
			if( count( $hooks ))
			{
				foreach( $hooks as $hook => $params)
				{
					$events[$hook] = "webhook_". $hook ;
				}
			}
		}

		$form->add( new WebhookSelector('webhook_events', $this->events ?? [], TRUE , [ 'options' => $events] ) );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$values['events'] = $values['webhook_events'];

		if( $values['webhook_content_type'] AND $values['webhook_content_type'] === 'custom' )
		{
			$values['content_type'] = $values['webhook_content_type_other'];
		}
		else
		{
			$values['content_type'] = $values['webhook_content_type'];
		}

		unset( $values['webhook_events'], $values['webhook_content_type'], $values['webhook_content_type_other'] );

		return $values;
	}

	public function get__title(): string
	{
		return (string) $this->url;
	}

	/**
	 * [Node] Get Node Description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		return Theme::i()->getTemplate( 'api', 'core' )->webhookDesc( $this );
	}

	/**
	 * This method will log the webhook to be fired, it's going to be fired via a task!
	 *
	 * @param	string	$event		The event key
	 * @param	mixed	$data		Data
	 * @param	array	$filters		Filters
	 * @return	void
	 */
	public static function fire( string $event, mixed $data = NULL, array $filters = array() ) : void
	{
		/* This will intentionally not call $data::apiOutput to avoid unnecessary overhead here */
		Log::debug(  print_r( array_merge(['event' => $event], ['payload' => $data], ['filters' =>  $filters ]) , TRUE ), 'webhook_fire_call');

		/* Get our webhooks from cache */
		if ( !isset( Store::i()->webhooks ) )
		{
			$webhooks = array();
			foreach ( Db::i()->select( '*', 'core_api_webhooks', array('enabled=1') ) as $row )
			{
				foreach ( explode( ',', $row['events'] ) as $e )
				{
					if ( !isset( $webhooks[ $e ] ) )
					{
						$webhooks[ $e ] = array();
					}
					
					$webhooks[ $e ][ $row['id'] ] = array(
						'key'		=> $row['api_key'],
						'url'		=> $row['url'],
						'filters'	=> json_decode( $row['filters'], TRUE ),
						'content_type_header' => $row['content_type']
					);
				}
			}
			Store::i()->webhooks = $webhooks;
		}
		
		/* If we have webhooks for this event... */
		$enable = FALSE;
		if ( isset( Store::i()->webhooks[ $event ] ) )
		{
			/* Normalise data */
			if ( is_object( $data ) and method_exists( $data, 'apiOutput' ) )
			{
				$data = $data->apiOutput();
			}
			else if ( is_array( $data ) )
			{
				foreach ( $data as $key => &$item )
				{
					/* Normalise data */
					if ( is_object( $item ) and method_exists( $item, 'apiOutput' ) )
					{
						$item = $item->apiOutput();
					}
				}
			}

			/* We need to replace and langstring hashes ( like node names) */
			Member::loggedIn()->language()->parseOutputForDisplay( $data );

			$data = $data ? json_encode( $data ) : NULL;
			
			/* Loop through each one... */
			foreach ( Store::i()->webhooks[ $event ] as $id => $webhook )
			{					
				/* Skip over it if the filters don't match */
				if ( isset( $webhook['filters'][ $event ] ) )
				{
					foreach ( $webhook['filters'][ $event ] as $k => $v )
					{
						if ( isset( $filters[ $k ] ) )
						{
							if ( is_array( $v ) and !is_array( $filters[ $k ] ) )
							{
								if ( !in_array( $filters[ $k ], $v ) )
								{
									continue 2;
								}
							}
							else
							{
								if ( $filters[ $k ] != $v )
								{
									continue 2;
								}
							}
						}
					}
				}
														
				/* Insert it */
				$enable = TRUE;
				Db::i()->insert( 'core_api_webhook_fires', [
					'webhook'	=> $id,
					'event'		=> $event,
					'data'		=> $data,
					'time'		=> time(),
					'status'		=> 'pending'
				] );
			}
		}
		
		/* Make sure the task is enabled */
		if ( $enable )
		{
			Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'webhooks' ) );
		}
	}
	
	/**
	 * Get API Key
	 *
	 * @return    Key
	 */
	public function get_api_key() : Key
	{
		return Key::load( $this->_data['api_key'] );
	}
	
	/**
	 * Set API Key
	 *
	 * @param Key $key		The API Key
	 * @return	void
	 */
	public function set_api_key(Key $key ) : void
	{
		$this->_data['api_key'] = $key->id;
	}
	
	/**
	 * Get Events
	 *
	 * @return	array
	 */
	public function get_events() : array
	{
		return explode( ',', $this->_data['events'] );
	}
	
	/**
	 * Set Events
	 *
	 * @param	string|array	$events	List of events
	 * @return	void
	 */
	public function set_events( string|array $events ) : void
	{
		if( !is_array( $events ) )
		{
			$events = [$events];
		}
		$this->_data['events'] = implode( ',', $events );
	}
	
	/**
	 * Get URL
	 *
	 * @return Url|string
	 */
	public function get_url() : Url|string
	{
		return $this->_data['url'] ? new Url( $this->_data['url'] ) : '';
	}
	
	/**
	 * Set Url
	 *
	 * @param	Url	$url		The URL
	 * @return	void
	 */
	public function set_url( Url $url ) : void
	{
		$this->_url = $url;
		$this->_data['url'] = (string) $this->_url;
	}
	
	/**
	 * Get Filters
	 *
	 * @return	array
	 */
	public function get_filters() : array
	{
		return $this->_data['filters'] ? json_decode( $this->_data['filters'], TRUE ) : array();
	}
	
	/**
	 * Set Filters
	 *
	 * @param	array	$filters		Filter
	 * @return	void
	 */
	public function set_filters( array $filters ) : void
	{
		$this->_data['filters'] = json_encode( $filters );
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse		int		id		ID number
	 * @apiresponse		array	events	List of events this hook is subscribed to
	 * @apiresponse		string	url		URL to send webhook to
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'id'		=> $this->id,
			'events'	=> $this->events,
			'url'		=> (string) $this->url
		);
	}

	public static function getAvailableWebhooks() : array
	{
		$hooks = [];
		foreach ( Application::applications() as $app )
		{
			if( $app->enabled )
			{
				$hooks[$app->directory] = $app->getWebhooks();
			}
		}

		return $hooks;
	}

	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( string $column, string $query, string $order=NULL, mixed $where=array() ): array
	{
		if ( $column === '_title' )
		{
			$column	= 'url';
		}

		if( $order == '_title' )
		{
			$order	= 'url';
		}

		return parent::search( $column, $query, $order, $where );
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete(): void
	{
		Db::i()->delete( 'core_api_webhook_fires', array( 'webhook=?', $this->id ) );
		parent::delete();
	}
}
