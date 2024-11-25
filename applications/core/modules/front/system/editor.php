<?php
/**
 * @brief		Editor AJAX functions Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Apr 2013
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DomainException;
use Exception;
use IPS\Application;
use IPS\core\StoredReplies;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Helpers\Form\Editor as FormEditor;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Redis;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Parser;
use IPS\Theme;
use OutOfRangeException;
use UnexpectedValueException;
use function class_exists;
use function count;
use function defined;
use function in_array;
use function intval;
use function md5;
use function strtolower;
use function strtoupper;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor AJAX functions Controller
 */
class editor extends Controller
{
	/**
	 * Image Dialog
	 *
	 * @return	void
	 */
	protected function image() : void
	{
		$maxImageDims = Settings::i()->attachment_image_size ? explode( 'x', Settings::i()->attachment_image_size ) : array( 1000, 750 );

		/* Let's do some casting here */
		Request::i()->width = (int) Request::i()->width;
		Request::i()->height = (int) Request::i()->height;
		Request::i()->actualWidth = (int) Request::i()->actualWidth;
		Request::i()->actualHeight = (int) Request::i()->actualHeight;
		$maxImageDims = array_map('intval', $maxImageDims);

		foreach( array( 'width', 'height', 'actualWidth', 'actualHeight' ) as $key )
		{
			if( Request::i()->$key <= 0 )
			{
				Output::i()->error( 'invalid_image_dimensions', '2C270/2', 403, '' );
			}
		}

		$ratioH = round( Request::i()->height / Request::i()->width, 2 );
		$ratioW = round( Request::i()->width / Request::i()->height, 2 );

		if( $maxImageDims[0] === 0 && $maxImageDims[1] === 0 )
		{
			$maxWidth = (int) Request::i()->actualWidth;
			$maxHeight = (int) Request::i()->actualHeight;
		}
		else
		{
			$maxWidth = ( Request::i()->actualWidth < $maxImageDims[0] ) ?  Request::i()->actualWidth : $maxImageDims[0];
			$maxHeight = ( Request::i()->actualHeight < $maxImageDims[1] ) ? Request::i()->actualHeight : $maxImageDims[1];
		}			

		if ( Request::i()->width > $maxWidth )
		{			
			Request::i()->width = $maxWidth;
			Request::i()->height = floor( Request::i()->width * $ratioH );
		}

		if ( Request::i()->height > $maxHeight )
		{
			Request::i()->height = $maxHeight;
			Request::i()->width = floor( Request::i()->height * $ratioW );
		}
	
		Output::i()->output = Theme::i()->getTemplate( 'editor', 'core', 'global' )->image( Request::i()->editorId, Request::i()->width, Request::i()->height, $maxWidth, $maxHeight, Request::i()->float, Request::i()->link, $ratioW, $ratioH, urldecode( Request::i()->imageAlt ), Request::i()->editorUniqueId );
	}
	
	/**
	 * AJAX validate link
	 *
	 * @return	void
	 */
	protected function validateLink() : void
	{
		/* CSRF check */
		Session::i()->csrfCheck();

		$internalEmbedAllowed = FormEditor::memberHasPermission( 'internal_embed', comments: isset( Request::i()->commenteditor ) );
		$externalEmbedAllowed = !Request::i()->noEmbed and FormEditor::memberHasPermission( 'external_embed', comments: isset( Request::i()->commenteditor ) );
		$imageRequest = boolval( !Request::i()->noEmbed and Request::i()->image and Request::i()->width and Request::i()->height );
		$externalImageAllowed = $imageRequest and $externalEmbedAllowed and FormEditor::memberHasPermission( 'external_image', comments: isset( Request::i()->commenteditor ) );

		/* Have we recently checked and validated this link? */
		$cacheKey = 'url_validate_' . md5( json_encode([
			Request::i()->url,
			(bool) Request::i()->noEmbed,
			Member::loggedIn()->language()->id,
			$imageRequest,
			$externalEmbedAllowed,
			$externalImageAllowed,
			$internalEmbedAllowed,
		]));

		try
		{
			$cachedResult = Cache::i()->getWithExpire( $cacheKey, TRUE );
			$cachedResult['fromCache'] = 1;
			Output::i()->json( $cachedResult );
		}
		catch( OutOfRangeException $e ){}

		/* Fetch the result and cache it, then return it */
		try
		{
			$title		= NULL;
			$isEmbed	= FALSE;
			$url		= Url::createFromString( Request::i()->url, TRUE, TRUE );
			$embed		= NULL;
			$error		= NULL;

			if ( !Request::i()->noEmbed )
			{
				// First make sure this url can be embedded under the current settings
				if ( $url instanceof Url\Internal and !$internalEmbedAllowed )
				{
					$embed = null;
				}
				else if ( !( $url instanceof Url\Internal ) and ( !$externalEmbedAllowed or ( $imageRequest and !$externalImageAllowed ) ) )
				{
					$embed = null;
				}
				else
				{
					try
					{
						if ( $imageRequest )
						{
							Parser::isAllowedContentUrl( $url );
							$embed = Parser::imageEmbed(
								$url instanceof Url\Internal ? $url : (string) Request::i()->url,
								intval( Request::i()->width ),
								intval( Request::i()->height )
							);
						}
						else
						{
							$embed = Parser::embeddableMedia( $url );
						}
					}
					catch ( UnexpectedValueException $e )
					{
						switch ( $e->getMessage() )
						{
							case 'embed__fail_404':
								$error = Member::loggedIn()->language()->addToStack( $e->getMessage(), FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->get( 'embed__fail_' . $e->getCode() ) ) ) );
								break;

							case 'embed__fail_403':
								$error = Member::loggedIn()->language()->addToStack( $e->getMessage(), FALSE, array( 'sprintf' => array( $url->data['host'], Member::loggedIn()->language()->get( 'embed__fail_' . $e->getCode() ) ) ) );
								break;

							case 'embed__fail_500':
								$error = Member::loggedIn()->language()->addToStack( $e->getMessage(), FALSE, array( 'sprintf' => array( $url->data['host'] ) ) );
								break;

							case 'embed__fail_400':
								$error = Member::loggedIn()->language()->addToStack( $e->getMessage(), FALSE, array( 'sprintf' => array( 'Bad Request' ) ) );
								break;

							default:
								$error = Member::loggedIn()->language()->addToStack( $e->getMessage() );
								break;
						}
					}
					catch ( Exception $e )
					{
						/* Log it if debug logging is enabled so we can see what happened. Maybe save another dev fifteen minutes of "why isn't this working" */
						Log::debug( $e, 'embed_fail' );
					}
				}
			}

			if ( $embed OR $error )
			{
				$insert = $embed ?: "";
				$isEmbed = !$error;
			}
			else
			{
				$title	= Request::i()->title ?: (string) $url;
				$insert	= "<a href='{$url}' ipsNoEmbed='true'>{$title}</a>";
			}

			$result = array(
				'preview' 		=> trim( $insert ),
				'title' 		=> $title,
				'embed' 		=> $isEmbed,
				'errorMessage' 	=> NULL,
				'allowCustom' 	=> ( !isset( Request::i()->noExternal ) and FormEditor::memberHasPermission( 'og_embed' ) and !$isEmbed and Parser::isAllowedContentUrl( $url ) ),
			);

			if( $error )
			{
				$result['errorMessage'] = Member::loggedIn()->language()->addToStack( 'embed_failure_message', FALSE, array( 'sprintf' => array( $error ) ) );
				Member::loggedIn()->language()->parseOutputForDisplay( $result['errorMessage'] );
				Log::debug( $url . "\n" . $result['errorMessage'], 'embed_fail' );
			}

			Cache::i()->storeWithExpire( $cacheKey, $result, DateTime::create()->add( new DateInterval( 'P10D' ) ), true );

			Output::i()->json( $result );
		}
		catch ( Exception $e )
		{
			Output::i()->json( $e->getMessage(), 500 );
		}
	}

	/**
	 * Get data from an external endpoint to embed in the editor
	 *
	 * @return void
	 */
	protected function getLinkContent() : void
	{
		/* The csrf check is important to prevent spamming this endpoint, causing all sorts of requests */
		Session::i()->csrfCheck();
		$content = "";

		/* Have we recently checked and validated this link? */
		$cacheKey = md5( 'content' . Request::i()->url . Member::loggedIn()->language()->id );
		try
		{
			$cachedResult = Cache::i()->getWithExpire( $cacheKey, TRUE );

			Output::i()->json( [ 'content' => $cachedResult ] );
		}
		catch( OutOfRangeException $e ){}

		/* Let's make sure this IP address isn't spamming the endpoint */
		if ( Redis::isEnabled() )
		{
			try
			{
				Redis::i()->zIncrBy( 'linkContentRequest', 1, Request::i()->ipAddress(), 180 );

				/* If the IP address has attempted to get link content 50 times in the last 3 minutes, return nothing. Perhaps in the future, the editor can accept a return code and display a message "Chill out man" or some such */
				if ( Redis::i()->zScore( 'linkContentRequest', Request::i()->ipAddress() ) > 50 )
				{
					Output::i()->json( [ 'content' => '' ] );
				}
			}
			catch( Exception $e ) {}
		}

		/* Still here? Best get the link */
		try
		{
			if ( !Request::i()->url )
			{
				throw new DomainException;
			}

			$request = Url::external( Request::i()->url )->requestUntrusted();

			$response = $request->get();
			if ( is_string( $response->content ) and str_starts_with( (string) $response->httpResponseCode, '2' ) and str_starts_with( mb_strtolower( $response->httpHeaders['Content-Type'] ), 'text/html' ) )
			{
				$content = $response->content;
			}

		}
		catch ( Exception ) { }

		Cache::i()->storeWithExpire( $cacheKey, $content, DateTime::create()->add( new DateInterval( 'PT5M' ) ) );

		Output::i()->json([ "content" => $content ]);
	}
			
	/**
	 * Get Emoji
	 *
	 * @return	void
	 */
	protected function emoji() : void
	{
		try
		{
			$emojis = Cache::i()->getWithExpire( 'core_editor_emoji_sets' );
			if ( !is_array( $emojis ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			$emojis = array();
			$categoryNames = array();
			foreach ( Db::i()->select( '*', 'core_emoticons', NULL, 'emo_set_position,emo_position' ) as $row )
			{
				try
				{
					$categoryName = $categoryNames[ $row['emo_set'] ] ?? Member::loggedIn()->language()->get( 'core_emoticon_group_' . $row['emo_set'] );
				}
				catch ( OutOfRangeException )
				{
					$categoryName = '_default';
				}

				$code = 'custom-' . $row['emo_set'] . '-' . $row['id'];
				$categoryNames[ $row['emo_set'] ] = $categoryName;
				$emojis[ $categoryName ][ $code ] = array(
					'unicode'     => $code,
					'description' => trim( $row['typed'], ":" ),
					'skinTones'   => false,
					'hairStyles'  => false,
					'sortKey'     => $row['emo_position'],
					'image'       => array(
							'image'   	=> (string) File::get( 'core_Emoticons', $row['image'] )->url,
							'image2x' 	=> (string) ( $row['image_2x'] ? File::get( 'core_Emoticons', $row['image_2x'] )->url : "" ),
							'width'   	=> $row['width'] ?: 20,
							'height'  	=> $row['height'] ?: 20
					)
				);
			}

			Cache::i()->storeWithExpire( "core_editor_emoji_sets", $emojis, ( new DateTime )->add( new DateInterval( "P7D" ) ) );
		}

		Output::i()->json( $emojis );
	}
	
	/**
	 * My Media
	 *
	 * @return	void
	 */
	protected function myMedia() : void
	{
		/* Init */
		$perPage = 12;
		$search = isset( Request::i()->search ) ? Request::i()->search : null;
		
		/* Get all our available sources */
		$mediaSources = array();
		foreach ( Application::allExtensions( 'core', 'EditorMedia' ) as $k => $class )
		{
			if ( $class->count( Member::loggedIn(), isset( Request::i()->postKey ) ? Request::i()->postKey : '' ) )
			{
				$mediaSources[] = $k;
			}
		}
		/* Work out what tab we're on */
		if ( !Request::i()->tab )
		{
			if( !count( $mediaSources ) )
			{
				Output::i()->output = Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMedia( Request::i()->editorId, $mediaSources, NULL, NULL, NULL );
				return;
			}
			
			$sources = $mediaSources;
			Request::i()->tab = array_shift( $sources );
		}

		$exploded = explode( '_', Request::i()->tab );
		$classname = "IPS\\{$exploded[0]}\\extensions\\core\\EditorMedia\\{$exploded[1]}";
		$extension = new $classname;
		$url = Url::internal( "app=core&module=system&controller=editor&do=myMedia&tab=" . Request::i()->tab . "&key=" . Request::i()->key . "&postKey=" . Request::i()->postKey . "&existing=1" );
		
		/* Count how many we have */
		$count = $extension->count( Member::loggedIn(), isset( Request::i()->postKey ) ? Request::i()->postKey : '', $search );

		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		/* Display */
		if ( isset( Request::i()->existing ) )
		{
			if ( isset( Request::i()->search ) || ( isset( Request::i()->page ) && Request::i()->page !== 1 ) )
			{
				Output::i()->output = Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMediaResults(
					$extension->get( Member::loggedIn(), $search, isset( Request::i()->postKey ) ? Request::i()->postKey : '', $page, $perPage ),
					Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
						$url,
						ceil( $count / $perPage ),
						$page,
						$perPage
					),
					$url,
					Request::i()->tab
				);
			}
			else
			{
				Output::i()->output = Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMediaContent(
					$extension->get( Member::loggedIn(), $search, isset( Request::i()->postKey ) ? Request::i()->postKey : '', $page, $perPage ),
					Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
						$url,
						ceil( $count / $perPage ),
						$page,
						$perPage
					),
					$url,
					Request::i()->tab
				);
			}
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMedia( Request::i()->editorId, $mediaSources, Request::i()->tab, $url, Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMediaContent(
				$extension->get( Member::loggedIn(), $search, isset( Request::i()->postKey ) ? Request::i()->postKey : '', $page, $perPage ),
				Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
					$url,
					ceil( $count / $perPage ),
					$page,
					$perPage
				),
				$url,
				Request::i()->tab
			) );
		}
	}
	
	/**
	 * Mentions
	 *
	 * @return	void
	 */
	protected function mention() : void
	{
		$results = [];
		if ( mb_strlen( Request::i()->input ) > 0 )
		{
			$memberIds = [];
			if ( isset( Request::i()->contentClass ) AND isset( Request::i()->contentId ) )
			{
				$class = Request::i()->contentClass;
				if ( class_exists( $class ) AND IPS::classUsesTrait( $class, 'IPS\Content\Statistics' ) )
				{
					try
					{
						$item = $class::load( Request::i()->contentId );
						foreach( $item->mostRecent( Request::i()->input, 10, false ) AS $member )
						{
							$memberIds[] = $member->member_id;
							$results[] = [
								'userId'	=> (string) $member->member_id,
								'name'		=> $member->name,
								'link'		=> (string) $member->url(),
								'hoverLink' => (string) $member->url()->setQueryString([ 'do' => 'hovercard' ]),
								'photo'		=> (string) $member->photo,
							];
						}
					}
					catch( OutOfRangeException | BadMethodCallException $e ) { }
				}
			}
			
			/* If there are less than ten recent participants matching the input, fill it out with non-participants. */
			if ( count( $memberIds ) < 10 )
			{
				$where = array( Db::i()->like( 'name', Request::i()->input ) );
				$where[] = array( 'temp_ban !=?', '-1' );
				if ( count( $memberIds ) )
				{
					$where[] = array( Db::i()->in( 'member_id', $memberIds, TRUE ) );
				}
				
				foreach ( Db::i()->select( '*', 'core_members', $where, 'name', 10 - count( $memberIds ) ) as $row )
				{
					$member = Member::constructFromData( $row );
					$results[] = [
						'userId'	=> (string) $member->member_id,
						'name'		=> $member->name,
						'link'		=> (string) $member->url(),
						'hoverLink' => (string) $member->url()->setQueryString([ 'do' => 'hovercard' ]),
						'photo'		=> (string) $member->photo,
					];
				}
			}
		}
		
		Output::i()->json( $results );
	}

	/**
	 * @brief	Languages supported by giphy
	 * @see		https://developers.giphy.com/docs/optional-settings/#language-support
	 */
	protected static array $giphyLanguages = array( 'en', 'es', 'pt', 'id', 'fr', 'ar', 'tr', 'th', 'vi', 'hi', 'bn', 'da', 'fa', 'tl', 'fi',
											'de', 'it', 'ja', 'ru', 'ko', 'pl', 'nl', 'ro', 'hu', 'sv', 'cs', 'iw', 'ms', 'no', 'uk', 'zh-CN', 'zh-TW'
										);

	/**
	 * Get giphy images
	 *
	 * @return	void
	 */
	protected function giphy() : void
	{
		if ( Settings::i()->giphy_enabled )
		{
			try
			{
				$limit = isset( Request::i()->limit ) ? Request::i()->limit : 30;
				$offset = isset( Request::i()->offset ) ? Request::i()->offset : 0;

				$q = urlencode( Request::i()->search );

				/* Return the trending images if there's no search term */
				if ( !$q or $q == '' )
				{
					$url = Url::external( "https://api.giphy.com/v1/gifs/trending" );
				}
				else
				{
					$url = Url::external( "https://api.giphy.com/v1/gifs/search" )->setQueryString( 'q', $q );
				}

				$parameters = array(
					'api_key' => ( Settings::i()->giphy_apikey ? Settings::i()->giphy_apikey : Settings::i()->giphy_apikey_default ),
					'limit'   => $limit,
					'offset'  => $offset
				);

				$ourLocale = Member::loggedIn()->language()->short;

				if ( preg_match( '/^\w{2}[-_]\w{2}($|\.)/i', $ourLocale ) ) // This will only work for Unix-style locales
				{
					$langCode = strtolower( substr( $ourLocale, 0, 2 ) );

					/* Giphy supports zh-CN and zh-TW, so we have to handle this one special */
					if ( $langCode == 'zh' )
					{
						$langCode = strtolower( substr( $ourLocale, 0, 2 ) ) . '-' . strtoupper( substr( $ourLocale, 3, 2 ) );
					}

					if ( in_array( $langCode, static::$giphyLanguages ) )
					{
						$parameters['lang'] = $langCode;
					}
				}

				$url = $url->setQueryString( $parameters );

				if ( Settings::i()->giphy_rating and Settings::i()->giphy_rating !== 'x' )
				{
					$url = $url->setQueryString( 'rating', Settings::i()->giphy_rating );
				}

				$cacheKey = "IPS_GIPHY__" . md5( $url );
				$results = null;
				try
				{
					$results = json_decode( Cache::i()->getWithExpire( $cacheKey, true ), true );
				}
				catch ( OutOfRangeException ) {}

				if ( !is_array( $results ) )
				{
					$data = $url->request()->get()->decodeJson();
					if ( isset( $data['message'] ) and $data['message'] )
					{
						Output::i()->json( array( 'error' => $data['message'] ) );
					}

					if ( !isset( $data['data'] ) OR !is_array( $data['data'] ) )
					{
						Output::i()->json( ['error' => 'could not fetch results'], 500 );
					}

					$results = array( 'pagination' => $data['pagination'] ?? '', 'images' => [] );
					foreach ( $data['data'] as $row )
					{
						if ( !isset( $row['images']['fixed_height'] ) or !isset( $row['images']['fixed_height_small'] ) or !isset( $row['images']['fixed_height']['url'] ) )
						{
							continue;
						}
						
						$results['images'][] = array(
							'thumb' => $row['images']['fixed_height_small']['url'] ?? $row['images']['fixed_height']['url'],
							'url'   => $row['images']['fixed_height']['url'],
							'title' => $row['title'],
							'width' => intval($row['images']['fixed_height_small']['width'] ?? 150),
							'height' => intval($row['images']['fixed_height_small']['height'] ?? 100)
						);
					}

					Cache::i()->storeWithExpire( $cacheKey, json_encode( $results ), ( new DateTime() )->add( new DateInterval( 'PT1H' ) ), true );
				}

				Output::i()->json( $results );
			}
			catch ( Exception $e )
			{
				Output::i()->json( [ 'error' => var_export( $e, true ), 'request' => $data ?? '' ], 500 );
			}
		}
	}

	/**
	 * Get stored replies
	 *
	 * @return	void
	 */
	protected function storedReplies() : void
	{
		if ( isset( Request::i()->id ) )
		{
			try
			{
				$reply = StoredReplies::loadAndCheckPerms( Request::i()->id );

				Output::i()->json( [
					'id' => $reply->id,
					'title' => $reply->title,
					'reply' => $reply->text
				] );
			}
			catch( Exception )
			{
				Output::i()->json( [ 'error' => "An unexpected error occurred" ], 500 );
			}
		}
		else
		{
			try
			{
				$results = [];
				foreach ( StoredReplies::roots() as $reply )
				{
					if ( $reply->enabled )
					{
						$results[] = [ 'id' => $reply->id, 'title' => $reply->title ];
					}
				}
				Output::i()->json( [ "results" => $results ] );
			}
			catch ( Exception )
			{
				Output::i()->json( [ "error" => 'An unexpected error occurred', "results" => [] ], 500 );
			}
		}
	}
}