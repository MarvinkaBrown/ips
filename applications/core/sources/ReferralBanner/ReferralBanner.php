<?php
/**
 * @brief		Referral Banner Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		7 Aug 2019
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Http\Url;
use IPS\Node\Model;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Referral Banner Node
 */
class ReferralBanner extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_referral_banners';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'rb_';

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'order';

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'referral_banners';

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
		'module'	=> 'membersettings',
		'all'		=> 'referrals_banners'
	);

	/**
	 * Get image
	 *
	 * @return	string
	 */
	public function get__title(): string
	{
		return Theme::i()->getTemplate( 'global', 'core', 'global' )->referralBanner( $this->url );
	}

	/**
	 * [Node] Disable the Copy Feature
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		return false;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Radio( 'rb_upload', $this->id ? $this->upload : 1, TRUE, array(
			'options'	=> array(
				1			=> 'rb_upload_yes',
				0			=> 'rb_upload_no'
			),
			'toggles'	=> array(
				1		=> array( 'rb_url_upload' ),
				0		=> array( 'rb_url_url' )
			)
		) ) );

		$form->add( new Upload( 'rb_url_upload', $this->upload ? File::get( 'core_ReferralBanners', $this->url ) : NULL, NULL, array( 'storageExtension' => 'core_ReferralBanners' ), function( $val )
		{
			if ( Request::i()->rb_upload and !$val )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'rb_url_upload' ) );

		$form->add( new FormUrl( 'rb_url_url', !$this->upload ? $this->url : NULL, NULL, array( 'allowedMimes' => 'image/*' ), function( $val )
		{
			if ( !Request::i()->rb_upload and !$val )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'rb_url_url' ) );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['rb_url_upload'] ) or isset( $values['rb_url_url'] ) )
		{
			if ( $values['rb_upload'] )
			{
				$values['url'] = (string) $values['rb_url_upload'];
			}
			else
			{
				$values['url'] = (string) $values['rb_url_url'];
			}
			unset( $values['rb_url_upload'] );
			unset( $values['rb_url_url'] );
		}

		return $values;
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		if ( $this->upload )
		{
			try
			{
				File::get( 'core_ReferralBanners', $this->url )->delete();
			}
			catch( Exception $e ) { }
		}

		parent::delete();
	}

	/**
	 * Get URL
	 *
	 * @return	Url|string|null
	 * @note	Any nodes that have URLs (e.g. forums, gallery categories) should override this
	 */
	function url(): Url|string|null
	{
		if ( mb_substr( $this->url, 0, 4 ) == 'http')
		{
			return Url::external( $this->url );
		}
		else
		{
			return Url::internal( $this->url, 'none' );
		}
	}

}