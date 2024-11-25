<?php
/**
 * @brief		Log Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Mar 2016
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DirectoryIterator;
use Error;
use Exception;
use IPS\Patterns\ActiveRecord;
use RuntimeException;
use function defined;
use function file_put_contents;
use function get_called_class;
use function get_class;
use function is_array;
use function str_replace;
use function ucwords;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Log Class
 */
class Log extends ActiveRecord
{
	const DEFAULT_LOG_LEVEL = 3;
	const MAX_LOG_LEVEL = 5;

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_log';

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->time = time();
	}

	/**
	 * @brief	Track if we are currently logging to prevent recursion for database issues
	 */
	static protected bool $currentlyLogging	= FALSE;

	/***
	 * Default log levels for different categories
	 *
	 * @var array
	 */
	protected static array $categoryLogLevels = [
		'content_debug' => 1,
		'redis_writes' => 1,
		'redis_exception' => 1,
		'upgrade' => 2,
		'auto_upgrade' => 2,
		'paypal' => 2,
		'duplicate_comment' => 3,
		'searchResults' => 3,
		'profile_completion' => 3,
		'digestBuild' => 3,
		'index_single_item' => 3,
		'spam-service' => 4,
		'group_promotion' => 4,
		'birthday_error' => 5,
		'cookie' => 5,
		'views' => 5,
		'cicloud' => 5,
		'facebook_oembed' => 5,
		'achievements' => 5,
		'embed_fail' => 5,
		'template_store_building' => 5,
		'deleteFilesTask' => 5,
		'warn_fix' => 5
	];

	/**
	 * Log categories that are only logged when we enable
	 * the DEBUG_LOG constant
	 *
	 * @var array
	 */
	protected static array $excludeFromSettings = [
		'ranges',
		'runQueue_log',
		'rebuildAttachmentThumbnails',
		'rebuildItemCounts',
		'rebuildContentImages',
		'rebuildImageProxy',
		'rebuildLazyLoad',
		'rebuildSearchIndex',
		'rebuildSitemap',
		'RebuildUrlRels',
		'RestoreBrokenAttachments',
		'rebuildCategories',
		'rebuildItems',
		'rebuildPosts',
		'rebuildTagCache',
		'ICrebuildPosts',
		'fixDeletionLog',
		'UpdateMemberSeoNames',
		'Upgrade46FeaturedContent'
	];

	/**
	 * Write a log message
	 *
	 * @param Exception|string|array $message	An Exception object or a generic message to log
	 * @param string|null $category	An optional string identifying the type of log (for example "upgrade")
	 * @return    Log|null
	 */
	public static function log( Exception|string|array $message, string $category = NULL ): ?Log
	{
		/* Anything to log? */
		if( !$message )
		{
			return NULL;
		}

		/* Try to log it to the database */
		try
		{
			if( static::$currentlyLogging === TRUE )
			{
				throw new RuntimeException;
			}

			static::$currentlyLogging	= TRUE;

			$log = new Log();

			if ( $message instanceof Exception )
			{
				$log->exceptionClass = get_class( $message );
				$log->exceptionCode = $message->getCode();

				if ( method_exists( $message, 'extraLogData' ) AND $extraData = $message->extraLogData() )
				{
					$log->message = $extraData . "\n" . $message->getMessage();
				}
				else
				{
					$log->message = $message->getMessage();
				}

				$log->backtrace = $message->getTraceAsString();
			}
			else
			{
				if ( is_array( $message ) )
				{
					$message = var_export( $message, TRUE );
				}
				$log->message = $message;
				$log->backtrace = ( new Exception )->getTraceAsString();
			}

			/* If this is an actual request and not command line-invoked */
			if( Dispatcher::hasInstance() AND mb_strpos( php_sapi_name(), 'cli' ) !== 0 )
			{
				try
				{
					// Early exceptions may occur before the logged in object is set up.
					$log->member_id = Member::loggedIn()->member_id ?: 0;
				}
				catch( Error $e )
				{
					$log->member_id = 0;
				}
			}

			$log->category = $category;
			$log->save();

			static::$currentlyLogging	= FALSE;

			return $log;
		}
		/* If that fails, log to disk */
		catch ( Exception $e )
		{
			if ( !NO_WRITES and !CIC)
			{
				/* What are we writing? */
				$date = date('r');
				if ( $message instanceof Exception )
				{
					$messageToLog = $date . "\n" . get_class( $message ) . '::' . $message->getCode() . "\n" . $message->getMessage() . "\n" . $message->getTraceAsString();
				}
				else
				{
					if ( is_array( $message ) )
					{
						$message = var_export( $message, TRUE );
					}
					$messageToLog = $date . "\n" . $message . "\n" . ( new Exception )->getTraceAsString();
				}

				/* Where are we writing it? */
				$dir = str_replace( '{root}', ROOT_PATH, LOG_FALLBACK_DIR);
				if ( !is_dir( $dir ) )
				{
					if ( !@mkdir( $dir ) or !@chmod( $dir, IPS_FOLDER_PERMISSION) )
					{
						return null;
					}
				}

				/* Write it */
				$header = "<?php exit; ?>\n\n";
				$file = $dir . '/' . date( 'Y' ) . '_' . date( 'm' ) . '_' . date('d') . '_' . ( $category ?: 'nocategory' ) . '.php';
				if ( file_exists( $file ) )
				{
					@file_put_contents( $file, "\n\n-------------\n\n" . $messageToLog, FILE_APPEND );
				}
				else
				{
					@file_put_contents( $file, $header . $messageToLog );
				}
				@chmod( $file, IPS_FILE_PERMISSION);
			}
		}

		return NULL;
	}

	/**
	 * Write a debug message
	 *
	 * @param Exception|string $message	An Exception object or a generic message to log
	 * @param string|null $category	An optional string identifying the type of log (for example "upgrade")
	 * @param int|null $logLevel	An optional number to identify the logging level for this message. The lower the level,
	 * the higher the priority
	 * @return    Log|null
	 */
	public static function debug( Exception|string $message, string $category = NULL, ?int $logLevel=null ): ?Log
	{
		/* If the constant is enabled, we log everything */
		if ( defined('\IPS\DEBUG_LOG') and DEBUG_LOG )
		{
			return static::log( $message, $category );
		}

		/* If the category is in the excluded settings, stop here */
		if( $category === null or in_array( $category, static::$excludeFromSettings ) )
		{
			return null;
		}

		/* Check if we have a custom logging method for this category */
		$method = '_debug' . str_replace( " ", "", ucwords( str_replace( '_', ' ', $category ) ) );
		if( method_exists( get_called_class(), $method ) )
		{
			return static::$method( $message, $category );
		}

		/* If we didn't specify a log level, check if we have a default */
		if( $logLevel === null )
		{
			$logLevel = static::$categoryLogLevels[ $category ] ?? static::DEFAULT_LOG_LEVEL;
		}
		elseif( $logLevel > static::MAX_LOG_LEVEL )
		{
			/* Make sure we didn't go over the max */
			$logLevel = static::MAX_LOG_LEVEL;
		}

		/* Check settings - only log messages that at or below the current value */
		if( Settings::i()->debug_log_level and Settings::i()->debug_log_level <= $logLevel )
		{
			return static::log( $message, $category );
		}

		return NULL;
	}

	/**
	 * @param Exception|string|array $message
	 * @param string|NULL $category
	 * @return Log|null
	 */
	protected static function _debugRequest( Exception|string|array $message, string $category = NULL ): ?Log
	{
		if( !empty( Settings::i()->debug_log_requests ) )
		{
			/* This setting would have URLs or URL fragments that we should check */
			foreach( json_decode( Settings::i()->debug_log_requests, true ) as $string )
			{
				if( strpos( $message, $string ) !== false )
				{
					return static::log( $message, $category );
				}
			}
		}

		return null;
	}

	/**
	 * @param Exception|string|array $message
	 * @param string|NULL $category
	 * @return Log|null
	 */
	protected static function _debugWebhookFireCall( Exception|string|array $message, string $category = NULL ): ?Log
	{
		if( !empty( Settings::i()->debug_log_webhooks ) )
		{
			/* This setting would have URLs or URL fragments that we should check */
			foreach( json_decode( Settings::i()->debug_log_webhooks, true ) as $string )
			{
				$stringToCheck = '[event] => ' . $string;
				if( strpos( $message, $stringToCheck ) !== false )
				{
					return static::log( $message, $category );
				}
			}
		}

		return null;
	}

	/**
	 * @param Exception|string|array $message
	 * @param string|NULL $category
	 * @return Log|null
	 */
	protected static function _debugEventFire( Exception|string|array $message, string $category = NULL ): ?Log
	{
		if( !empty( Settings::i()->debug_log_events ) )
		{
			/* This setting would have URLs or URL fragments that we should check */
			foreach( json_decode( Settings::i()->debug_log_events, true ) as $string )
			{
				$stringToCheck = '[event] => ' . $string;
				if( strpos( $message, $stringToCheck ) !== false )
				{
					return static::log( $message, $category );
				}
			}
		}

		return null;
	}

	/**
	 * Get fallback directory
	 *
	 * @return	string|null
	 */
	public static function fallbackDir(): ?string
	{
		if (CIC)
		{
			return NULL;
		}
		return str_replace( '{root}', ROOT_PATH, LOG_FALLBACK_DIR);
	}

	/**
	 * Prune logs
	 *
	 * @param int $days Older than (days) to prune
	 * @return    void
	 * @throws Exception
	 */
	public static function pruneLogs( int $days ) : void
	{
		Db::i()->delete( static::$databaseTable, array( 'time<?', DateTime::create()->sub( new DateInterval( 'P' . $days . 'D' ) )->getTimestamp() ) );

		if ( !NO_WRITES)
		{
			$dir = static::fallbackDir();
			if ( is_dir( $dir ) )
			{
				try
				{
					$it = new DirectoryIterator( $dir );
				}
				catch ( Exception $e )
				{
					return;
				}

				foreach( $it as $file )
				{
					try
					{
						if( $file->isDot() or !$file->isFile() )
						{
							continue;
						}

						if( preg_match( "#.cgi$#", $file->getFilename(), $matches ) or ( preg_match( "#.php$#", $file->getFilename(), $matches ) and $file->getMTime() < ( time() - ( 60 * 60 * 24 * $days ) ) ) )
						{
							@unlink( $file->getPathname() );
						}
					}
					catch ( Exception $e ) { }
				}
			}
		}
	}

	/**
	 * Get Hook details
	 *
	 * @param string $file	filename of loaded hook
	 * @return	array
	 */
	public static function hookDetails( string $file ): array
	{
		$bits = explode( '/', $file );

		$return = array();

		$return['type'] = 'app';
		$return['id'] = $bits[1];

		return $return;
	}
}