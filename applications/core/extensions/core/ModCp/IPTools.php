<?php
/**
 * @brief		Moderator Control Panel Extension: IP Tools
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 Oct 2014
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Extensions\ModCpAbstract;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Member;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
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
 * @brief	IP Tools
 */
class IPTools extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string|null
	 */
	public function getTab() : ?string
	{
		if ( ! \IPS\Member::loggedIn()->modPermission('can_use_ip_tools') )
		{
			return null;
		}
		
		return 'ip_tools';
	}

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return	string
	 */
	public function manageType() : string
	{
		return 'members';
	}
	
	/**
	 * Get content to display
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		if ( ! \IPS\Member::loggedIn()->modPermission('can_use_ip_tools') )
		{
			Output::i()->error( 'no_module_permission', '2C250/1', 403, '' );
		}
		
		Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'modcp_ip_tools' );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools", 'front', 'modcp_ip_tools' ), \IPS\Member::loggedIn()->language()->addToStack( 'modcp_ip_tools' ) );
		
		if ( isset( Request::i()->ip ) )
		{
			$ip = Request::i()->ip;
			Output::i()->title = $ip;
			
			$url =  Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools", 'front', 'modcp_ip_tools' )->setQueryString( 'ip', $ip );
			Output::i()->breadcrumb[] = array( $url, $ip );

			if ( isset( Request::i()->area ) )
			{
				$exploded = explode( '_', Request::i()->area );
				$extensions = Application::appIsEnabled( $exploded[0] ) ? Application::load( $exploded[0] )->extensions( 'core', 'IpAddresses' ) : array();

				/* If the extension no longer exists (application uninstalled) then fall back */
				if( isset( $extensions[ mb_substr( Request::i()->area, mb_strlen( $exploded[0] ) + 1 ) ] ) and ( !method_exists( $extensions[ mb_substr( Request::i()->area, mb_strlen( $exploded[0] ) + 1 ) ], 'supportedInModCp' ) or $extensions[ mb_substr( Request::i()->area, mb_strlen( $exploded[0] ) + 1 ) ]->supportedInModCp() ) )
				{
					$class = $extensions[ mb_substr( Request::i()->area, mb_strlen( $exploded[0] ) + 1 ) ];
					
					Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'ipAddresses__' .  Request::i()->area ) );
					Output::i()->output = $class->findByIp( str_replace( '*', '%', $ip ), $url->setQueryString( 'area', Request::i()->area ) );
				}
				else
				{
					Output::i()->error( 'node_error', '2C250/2', 404, '' );
				}
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
					if( !$ext->supportedInModCp() )
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
				
				Output::i()->output = Theme::i()->getTemplate( 'members', 'core', 'global' )->ipLookup( $url, $geolocation, $map, $hostName, array_merge( $otherCounts, $contentCounts ) );
			}
		}
		elseif ( isset( Request::i()->id ) )
		{
			$member = \IPS\Member::load( Request::i()->id );

			Output::i()->title = $member->name;
			
			$url =  Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools", 'front', 'modcp_ip_tools' )->setQueryString( 'id', $member->member_id );
			Output::i()->breadcrumb[] = array( $url, $member->name );

			/* Init Table */
			$ips = $member->ipAddresses();
			
			$table = new Custom( $ips, $url );
			$table->langPrefix		= 'members_iptable_';
			$table->mainColumn		= 'ip';
			$table->sortBy			= $table->sortBy ?: 'last';
			$table->quickSearch		= 'ip';
			$table->rowsTemplate	= array( Theme::i()->getTemplate( 'modcp', 'core' ), 'ipMemberRows' );
			$table->tableTemplate	= array( Theme::i()->getTemplate( 'modcp', 'core' ), 'ipMemberTable' );
			$table->extra			= $member;
			
			/* Parsers */
			$table->parsers = array(
				'first'			=> function( $val )
				{
					return DateTime::ts( $val )->localeDate();
				},
				'last'			=> function( $val )
				{
					return DateTime::ts( $val )->localeDate();
				},
			);
			
			/* Buttons */
			$table->rowButtons = function( $row )
			{
				return array(
					'view'	=> array(
						'icon'		=> 'search',
						'title'		=> 'see_uses',
						'link'		=> Url::internal( 'app=core&module=modcp&controller=modcp&tab=ip_tools&ip=' ) . $row['ip'],
					),
				);
			};

			Output::i()->output		= $table;
		}
		else
		{
			$form = new Form( 'form', 'continue' );
			$form->class = 'ipsForm--vertical ipsForm--ip-address';
			
			$form->add( new Text( 'ip_address', NULL, TRUE, array(), function( $val )
			{
				if( trim( $val, '*' ) == '' )
				{
					throw new DomainException('not_just_asterisk');
				}
			} ) );
			
			if ( $values = $form->values() )
			{
				Output::i()->redirect( Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools", 'front', 'modcp_ip_tools' )->setQueryString( 'ip', $values['ip_address'] ) );
			}

			$members = new Form( 'members', 'continue' );
			$members->class = 'ipsForm--vertical ipsForm--ip-username';
			$members->add( new Member( 'ip_username', NULL, TRUE ) );
			
			if ( $values = $members->values() )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=modcp&controller=modcp&tab=ip_tools' )->setQueryString( 'id', $values['ip_username']->member_id ) );
			}

			Output::i()->output = Theme::i()->getTemplate( 'modcp', 'core', 'front' )->iptools( $form, $members );
		}
	}
}