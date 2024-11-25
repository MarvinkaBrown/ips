<?php
/**
 * @brief		IP Address Tools
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Apr 2013
 */

namespace IPS\core\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Tools
 */
class ip extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * @brief	GeoLocation
	 */
	protected ?GeoLocation $geoLocation = NULL;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'membertools_ip' );
		parent::execute();
	}

	/**
	 * IP Address Lookup
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( isset( Request::i()->ip ) )
		{
			$ip = Request::i()->ip;
			Output::i()->title = $ip;
			
			$url =  Url::internal( 'app=core&module=members&controller=ip' )->setQueryString( 'ip', $ip );
			
			if ( isset( Request::i()->area ) )
			{
				Output::i()->breadcrumb[] = array( $url, $ip );
				Output::i()->breadcrumb[] = array( NULL, 'ipAddresses__' .  Request::i()->area );

				$exploded = explode( '_', Request::i()->area );
				$extensions = Application::load( $exploded[0] )->extensions( 'core', 'IpAddresses' );
				$class = $extensions[ mb_substr( Request::i()->area, mb_strlen( $exploded[0] ) + 1 ) ];
				Output::i()->output = $class->findByIp( str_replace( '*', '%', $ip ), $url->setQueryString( 'area', Request::i()->area ) );
			}
			else
			{
				$geolocation	= NULL;
				$map			= NULL;
				$hostName		= $ip;

				if( filter_var( $ip, FILTER_VALIDATE_IP ) !== false )
				{
					try
					{
						$geolocation = GeoLocation::getByIp( $ip );
						$map = $geolocation->map()->render( 400, 350, 0.6 );
					}
					catch ( Exception $e ) {}

					$hostName	= @gethostbyaddr( $ip );
				}
				
				$contentCounts = array();
				$otherCounts = array();
				foreach ( Application::allExtensions( 'core', 'IpAddresses' ) as $k => $ext )
				{
					/* If the method does not exist, we presume it is supported - this is for legacy purposes as the method is new so
						third parties won't have it present */
					if( !$ext->supportedInAcp() )
					{
						continue;
					}

					$count = $ext->findByIp( str_replace( '*', '%', $ip ) );
					if ( $count !== NULL )
					{			
						if ( isset( $ext->class ) )
						{
							$class = $ext->class;
							if ( isset( $class::$databaseColumnMap['ip_address'] ) )
							{
								$contentCounts[ $k ] = $count;
							}
						}
						else
						{
							$otherCounts[ $k ] = $count;
						}
					}
				}
				
				Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( '', Theme::i()->getTemplate( 'members', 'core', 'global' )->ipLookup( $url, $geolocation, $map, $hostName, array_merge( $otherCounts, $contentCounts ) ) );
			}
		}
		else
		{
			$form = new Form( 'form', 'continue' );
			$form->add( new Text( 'ip_address', NULL, TRUE, array(), function( $val )
			{
				if( trim( $val, '*' ) == '' )
				{
					throw new DomainException('not_just_asterisk');
				}
			} ) );
			
			if ( $values = $form->values() )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=members&controller=ip' )->setQueryString( 'ip', $values['ip_address'] ) );
			}

			$members = new Form( 'members', 'continue' );
			$members->add( new FormMember( 'ip_username', NULL, TRUE ) );
			
			if ( $values = $members->values() )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=members&controller=members&do=ip' )->setQueryString( 'id', $values['ip_username']->member_id ) );
			}
			
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_members_ip');
			Output::i()->output = Theme::i()->getTemplate( 'members' )->ipform( $form, $members );
		}
	}
}