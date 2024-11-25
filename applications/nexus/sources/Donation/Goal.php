<?php
/**
 * @brief		Donation Goal Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Jun 2014
 */

namespace IPS\nexus\Donation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Widget;
use Iterator;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Donation Goal Node
 */
class Goal extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_donate_goals';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'd_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'donation_goals';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_donategoal_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';

	/**
	 * @brief	Donating publicly
	 */
	const DONATE_PUBLIC = 1;

	/**
	 * @brief	Donating anonymously
	 */
	const DONATE_ANONYMOUS = 2;

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
		'app'		=> 'nexus',
		'module'	=> 'payments',
		'all'		=> 'donationgoals_manage'
	);
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Translatable( 'd_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_donategoal_{$this->id}" : NULL ) ) );
		$form->add( new Translatable( 'd_desc', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_donategoal_{$this->id}_desc" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-donategoal-{$this->id}" : "nexus-new-donategoal" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'donategoal' ) : NULL, 'minimize' => 'd_desc_placeholder'
			)
		), NULL, NULL, NULL, 'd_desc_editor' ) );
				
		if ( count( Money::currencies() ) > 1 and !$this->current )
		{
			$form->add( new Radio( 'd_currency', $this->currency ?: Customer::loggedIn()->defaultCurrency(), TRUE, array(
				'options' => array_combine( Money::currencies(), Money::currencies() ),
			) ) );
		}

		foreach ( $this->donation_suggestions ? ( json_decode( $this->donation_suggestions, TRUE ) ?: array() ) : array() as $suggestion )
		{
			$amounts = array();
			foreach ( $suggestion as $currency => $amount )
			{
				$amounts[ $currency ] = new Number( $amount, $currency );
			}
		}

		$form->add( new Stack( 'd_suggestions', $this->suggestions ? json_decode( $this->suggestions, TRUE ) : array(), FALSE, array( 'stackFieldType' => 'IPS\Helpers\Form\Number', 'decimals' => 2 ) ) );
		$form->add( new YesNo( 'd_suggestions_open', $this->id ? $this->suggestions_open : TRUE, FALSE ) );

		$form->add( new Number( 'd_goal', $this->goal, FALSE, array( 'unlimited' => 0.0, 'unlimitedLang' => 'd_goal_none', 'decimals' => TRUE ) ) );
		$form->add( new Number( 'd_current', $this->current, FALSE, array( 'decimals' => TRUE ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( !$this->id )
		{
			$this->currency = Customer::loggedIn()->defaultCurrency();
			$this->save();
			File::claimAttachments( 'nexus-new-donategoal', $this->id, NULL, 'donategoal', TRUE );
		}

		if( isset( $values['d_name'] ) )
		{
			Lang::saveCustom( 'nexus', "nexus_donategoal_{$this->id}", $values['d_name'] );

			/* Save the SEO name */
			$this->name_seo = Friendly::seoTitle( $values[ 'd_name' ][ Lang::defaultLanguage() ] );
			$this->save();

			unset( $values['d_name'] );
		}

		if( isset( $values['d_desc'] ) )
		{
			Lang::saveCustom( 'nexus', "nexus_donategoal_{$this->id}_desc", $values['d_desc'] );
			unset( $values['d_desc'] );
		}
		
		if ( !isset( $values['d_currency'] ) )
		{
			$values['d_currency'] = Customer::loggedIn()->defaultCurrency();
		}

		$values['d_suggestions'] = json_encode( $values['d_suggestions'] );
		
		return $values;
	}
		
	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		Widget::deleteCaches( 'donations', 'nexus' );
		static::recountDonationGoals();
	}
	
	/**
	 * [ActiveRecord] Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();
		static::recountDonationGoals();
	}
	
	/**
	 * Recount card storage gateays
	 *
	 * @return	void
	 */
	protected static function recountDonationGoals() : void
	{
		$count = count( static::roots() );
		Settings::i()->changeValues( array( 'donation_goals' => $count ) );
	}

	/**
	 * @brief	Generated URL storage
	 */
	protected mixed $_url = NULL;
	
	/**
	 * Get URL
	 *
	 * @param string|null $action	Action
	 * @return	Url
	 */
	public function url( string $action=NULL ): Url
	{
		/* self-heal missing seo titles */
		if( $this->name_seo === null )
		{
			$language = Lang::load( Lang::defaultLanguage() );
			$this->name_seo = Friendly::seoTitle( $language->get( 'nexus_donategoal_' . $this->_id ) );
			$this->save();
		}

		if( $this->_url === null )
		{
			$this->_url = Url::internal( 'app=nexus&module=clients&controller=donations&id=' . $this->_id, 'front', 'clientsdonate', array( $this->name_seo ) );
		}

		return $this->_url;
	}

	/**
	 * Donors @param	int						$privacy		static::DONATE_PUBLIC + static::DONATE_ANONYMOUS
	 * @param array|int|null $limit			LIMIT clause
	 * @param bool $countOnly		Return only the count
	 *
	 * @return	Iterator|int
	 *
	 */
	public function donors( int $privacy=3, array|int $limit=NULL, bool $countOnly=FALSE ): Iterator|int
	{
		$where= array();
		$where[] = array( 'dl_goal=?', $this->id );

		/* Public / Anonymous */
		if ( !( $privacy & static::DONATE_PUBLIC ) )
		{
			$where[] = array( 'dl_anon=1' );
		}
		elseif ( !( $privacy & static::DONATE_ANONYMOUS ) )
		{
			$where[] = array( 'dl_anon=0' );
		}

		if ( $countOnly )
		{
			return (int) Db::i()->select( 'COUNT( DISTINCT nexus_donate_logs.dl_member ) as count', 'nexus_donate_logs', $where )->first();
		}
		else
		{
			return new ActiveRecordIterator( Db::i()->select( 'DISTINCT( core_members.member_id ), core_members.*', 'nexus_donate_logs', $where, NULL, $limit )->join( 'core_members', 'nexus_donate_logs.dl_member=core_members.member_id'), '\IPS\Member' );
		}
	}
}