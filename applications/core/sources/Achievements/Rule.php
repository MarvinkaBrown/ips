<?php
/**
 * @brief		Achievements Rule Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Feb 2021
 */

namespace IPS\core\Achievements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Application;
use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use XMLReader;
use function count;
use function defined;
use function get_class;
use function intval;
use function is_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievements Rule Model
 */
class Rule extends Model
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_achievements_rules';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'achievements_rules';

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
		'module'	=> 'achievements',
		'prefix'	=> 'rules_',
	);

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = ['achievementRules'];
	
	/**
	 * [ActiveRecord] Get cached rules
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->achievementRules ) )
		{
			$v = [];
			foreach ( static::roots() as $rule )
			{
				if ( !isset( $v[ $rule->action ] ) )
				{
					$v[ $rule->action ] = [];
				}
				$v[ $rule->action ][ $rule->id ] = [
					'filters'		=> $rule->filters,
					'points_subject'=> $rule->points_subject,
					'badge_subject'	=> $rule->badge_subject,
					'points_other'	=> $rule->points_other,
					'badge_other'	=> $rule->badge_other,
					'enabled'       => $rule->enabled
				];
			}
			Store::i()->achievementRules = $v;
		}
		
		return Store::i()->achievementRules;
	}
	
	/**
	 * Get the extension
	 *
	 * @return	AchievementActionAbstract
	 */
	public function extension(): AchievementActionAbstract
	{
		$exploded = explode( '_', $this->action );
		return Application::load( $exploded[0] )->extensions( 'core', 'AchievementAction' )[ $exploded[1] ];
	}
		
	/**
	 * Get JSON-decoded filters
	 *
	 * @return	array|NULL
	 */
	protected function get_filters() : ?array
	{
		if ( isset( $this->_data['filters'] ) and is_array( $this->_data['filters'] ) )
		{
			return $this->_data['filters'];
		}

		return ( isset( $this->_data['filters'] ) and $this->_data['filters'] ) ? json_decode( $this->_data['filters'], TRUE ) : NULL;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$options = [];
		$toggles = [];
		$extraElements = [];
		foreach ( Application::allExtensions( 'core', 'AchievementAction' ) as $achievementAction )
		{
			if ( ! $achievementAction->canUse() )
			{
				continue;
			}

			$exploded = explode( '\\', get_class( $achievementAction ) );
			$options[ "{$exploded[1]}_{$exploded[5]}" ] = "AchievementAction__{$exploded[5]}";

			$toggles[ "{$exploded[1]}_{$exploded[5]}" ] = [ "{$exploded[1]}_{$exploded[5]}_award_subject" ];
			$toggles[ "{$exploded[1]}_{$exploded[5]}" ][] = "{$exploded[1]}_{$exploded[5]}_award_subject_badge";
			foreach ($achievementAction->filters( $this->filters, Url::internal( $this->id ? "app=core&module=achievements&controller=rules&do=form&id={$this->id}" : 'app=core&module=achievements&controller=rules&do=form' ) ) as $filterKey => $filterElement )
			{
				if ( is_array( $this->filters ) and array_key_exists( $filterKey, $this->filters ) )
				{
					$form->_activeFilters[] = $filterElement->name;
				}
				if ( !$filterElement->htmlId )
				{
					$filterElement->htmlId = $filterElement->name;
				}
				$toggles[ "{$exploded[1]}_{$exploded[5]}" ][] = $filterElement->htmlId;
				$extraElements[] = $filterElement;
			}

			$awardTo = $achievementAction->awardOptions( $this->filters );
			$awardSubjectField = new Custom( "{$exploded[1]}_{$exploded[5]}_award_subject", [ 'points' => $this->points_subject, 'badge' => $this->badge_subject ], FALSE, [
				'getHtml' => function( $field ) {
					return Theme::i()->getTemplate('achievements')->awardField( $field->name, $field->value );
				}
			], NULL, NULL, NULL, "{$exploded[1]}_{$exploded[5]}_award_subject" );
			if ( isset( $awardTo['subject'] ) )
			{
				$awardSubjectField->label = Member::loggedIn()->language()->addToStack( $awardTo['subject'] );
			}
			$extraElements[] = $awardSubjectField;
			$extraElements[] = new Translatable( "{$exploded[1]}_{$exploded[5]}_award_subject_badge", NULL, NULL, [ 'app' => 'core', 'key' => ( $this->id ) ? "core_award_subject_badge_" . $this->id : NULL ], NULL, NULL, NULL, "{$exploded[1]}_{$exploded[5]}_award_subject_badge" );

			if ( isset( $awardTo['other'] ) )
			{
				$awardOtherField = new Custom( "{$exploded[1]}_{$exploded[5]}_award_other", [ 'points' => $this->points_other, 'badge' => $this->badge_other ], FALSE, [
					'getHtml' => function( $field ) {
						return Theme::i()->getTemplate('achievements')->awardField( $field->name, $field->value );
					}
				], NULL, NULL, NULL, "{$exploded[1]}_{$exploded[5]}_award_other" );
				$awardOtherField->label = Member::loggedIn()->language()->addToStack( $awardTo['other'] );
				$extraElements[] = $awardOtherField;
				$extraElements[] = new Translatable( "{$exploded[1]}_{$exploded[5]}_award_other_badge", NULL, NULL, [ 'app' => 'core', 'key' => ( $this->id ) ? "core_award_other_badge_" . $this->id : NULL ], NULL, NULL, NULL, "{$exploded[1]}_{$exploded[5]}_award_other_badge" );
				$toggles[ "{$exploded[1]}_{$exploded[5]}" ][] = "{$exploded[1]}_{$exploded[5]}_award_other";
				$toggles[ "{$exploded[1]}_{$exploded[5]}" ][] = "{$exploded[1]}_{$exploded[5]}_award_other_badge";
			}
		}

		$form->add( new Select( 'achievement_rule_action', $this->action, TRUE, [ 'options' => $options, 'toggles' => $toggles, 'sort' => TRUE ] ) );

		foreach ( $extraElements as $element )
		{
			$form->add( $element );
		}
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
			$this->action = '';
			$this->filters = null;
			$this->points_other = 0;
			$this->points_subject = 0;
			$this->save();
		}

		$exploded = explode( '_', $values['achievement_rule_action'] );
		$extension = Application::load( $exploded[0] )->extensions( 'core', 'AchievementAction' )[ $exploded[1] ];
		
		$filterValues = [];
		foreach ( Request::i()->activeFilters ?: [] as $k => $v )
		{
			$filterValues[ $k ] = $values[ $k ];
		}
		$filters = $extension->formatFilterValues( $filterValues );

		foreach( [ 'subject', 'other' ] as $type )
		{
			if ( isset( $values[$values['achievement_rule_action'] . '_award_' . $type . '_badge'] ) )
			{
				if ( $values[$values['achievement_rule_action'] . '_award_' . $type]['badge'] )
				{
					Lang::saveCustom( 'core', "core_award_" . $type . "_badge_{$this->id}", $values[$values['achievement_rule_action'] . '_award_' . $type . '_badge'] );
				}
				else
				{
					Lang::deleteCustom( 'core', "core_award_" . $type . "_badge_{$this->id}" );
				}

				unset( $values[$values['achievement_rule_action'] . '_award_' . $type . '_badge'] );
			}
		}

		return [
			'action'		=> $values['achievement_rule_action'],
			'filters'		=> $filters ? json_encode( $filters ) : NULL,
			'milestone'		=> ( isset( $filters['milestone'] ) and is_numeric( $filters['milestone'] ) ) ? $filters['milestone'] : NULL,
			'points_subject'=> intval( $values[ $values['achievement_rule_action'] . '_award_subject' ]['points'] ),
			'badge_subject'	=> $values[ $values['achievement_rule_action'] . '_award_subject' ]['badge'] ? intval( $values[ $values['achievement_rule_action'] . '_award_subject' ]['badge'] ): NULL,
			'points_other'	=> intval( isset( $values[ $values['achievement_rule_action'] . '_award_other' ] ) ? $values[ $values['achievement_rule_action'] . '_award_other' ]['points'] : 0 ),
			'badge_other'	=> isset( $values[ $values['achievement_rule_action'] . '_award_other' ] ) ? ( $values[ $values['achievement_rule_action'] . '_award_other' ]['badge'] ? intval( $values[ $values['achievement_rule_action'] . '_award_other' ]['badge'] ) : NULL ) : NULL,
		];
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );

		/* Enable/Disable */
		$buttons['toggleEnabled']	= array(
			'icon'	=> ( $this->enabled ) ? 'pause-circle' : 'play-circle',
			'title'	=> ( $this->enabled ) ? 'acp_disable_rule' : 'acp_enable_rule',
			'link'	=> Url::internal( "app=core&module=achievements&controller=rules&do=toggleEnabled&id={$this->_id}&enable=" . ( $this->enabled ? 0 : 1 ) )->csrf(),
			'data'	=> [ 'data-confirm' => 'true' ]
		);

		return $buttons;
	}

	/**
	 * Get badge for subject
	 *
	 * @return    Badge|NULL
	 */
	public function badgeSubject(): ?Badge
	{
		if ( $this->badge_subject )
		{
			try
			{
				return Badge::load( $this->badge_subject );
			}
			catch( OutOfRangeException $e ) { }
		}
		return NULL;
	}
	
	/**
	 * Get badge for others
	 *
	 * @return    Badge|NULL
	 */
	public function badgeOther(): ?Badge
	{
		if ( $this->badge_other )
		{
			try
			{
				return Badge::load( $this->badge_other );
			}
			catch( OutOfRangeException $e ) { }
		}
		return NULL;
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();

		Db::i()->delete( 'core_achievements_log_milestones', array( 'milestone_rule=?', $this->id ) );

		Lang::deleteCustom( 'core', "core_award_subject_badge_{$this->id}" );
		Lang::deleteCustom( 'core', "core_award_other_badge_{$this->id}" );
	}

	/**
	 * [Node] Clone the rule
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE ) {
			return;
		}

		$oldId = $this->id;

		parent::__clone();

		foreach ( array( 'subject' => "core_award_subject_badge_{$this->id}", 'other' => "core_award_other_badge_{$this->id}") as $fieldKey => $langKey ) {
			$oldLangKey = str_replace( $this->id, $oldId, $langKey );
			Lang::saveCustom( 'core', $langKey, iterator_to_array(Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array('word_key=?', $oldLangKey ) )->setKeyField('lang_id')->setValueField('word_custom' ) ) );
		}
	}

	/**
	 * Get details about the rebuild progress so far
	 *
	 * @return array|NULL
	 */
	public static function getRebuildProgress(): ?array
	{
		$return = [ 'count' => 0, 'processed' => 0 ];
		$rebuilding = FALSE;
		foreach( Db::i()->select( '*', 'core_queue', [ [ '`key`=?', 'RebuildAchievements' ] ] ) as $rebuild )
		{
			$data = json_decode( $rebuild['data'], TRUE );

			if ( isset( $data['processed'] ) and isset( $data['count'] ) )
			{
				$rebuilding = TRUE;
				$return['processed'] += (int) $data['processed'];
				$return['count'] += (int) $data['count'];
			}
		}

		$return['percentage'] = ( $return['count'] ? ( round( 100 / $return['count'] * $return['processed'], 2 ) ) : 100 );

		return $rebuilding ? $return : NULL;
	}

	/**
	 * Rebuild all cheeeeeevs
	 *
	 * @param DateTime|null $time
	 */
	public static function rebuildAllAchievements( ?DateTime $time ) : void
	{
		Db::i()->delete( 'core_achievements_log' );
		Db::i()->delete( 'core_achievements_log_milestones' );
		Db::i()->delete( 'core_points_log' );
		Db::i()->delete( 'core_member_badges', [ 'rule > 0' ] ); /* 0 is a manually awarded badge. Don't hate the coder, hate the player */
		Db::i()->update( 'core_members', [ 'achievements_points' => 0 ] );

		/* Remove all previous tasks */
		Db::i()->delete( 'core_queue', [ '`key`=?', 'RebuildAchievements' ] );

		foreach( Application::applications() as $app )
		{
			foreach ( Application::load( $app->directory )->extensions( 'core', 'AchievementAction' ) as $extension )
			{
				$bits = explode( '\\', get_class( $extension ) );
				$className = array_pop( $bits );
				Task::queue( 'core', 'RebuildAchievements', [
				'extension' => $app->directory . '_' . $className,
				'data' => $extension::rebuildData(),
				'time' => ( $time ) ? $time->getTimestamp() : NULL,
				], 4 );
			}
		}
	}

	/**
	 * Import from Xml
	 *
	 * @param	string	$file			The file to import data from
	 * @param	boolean	$deleteExisting Remove existing rules first?
	 *
	 * @return	void
	 */
	public static function importXml( string $file, bool $deleteExisting=FALSE ) : void
	{
		/* Open XML file */
		$xml = \IPS\Xml\XMLReader::safeOpen( $file );

		if ( ! @$xml->read() )
		{
			throw new DomainException( 'xml_upload_invalid' );
		}

		/* Did we want to wipe first? */
		if ( $deleteExisting )
		{
			foreach(Rule::getStore() as $action => $rules )
			{
				foreach( $rules as $ruleId => $rule )
				{
					try
					{
						static::load( $ruleId )->delete();
					}
					catch( OutOfRangeException $e ){}

				}
			}
		}

		$allBadges = [];
		$badgeMap = [];

		/* Rules: Start looping through each row */
		while ( $xml->read() )
		{
			if( $xml->nodeType != XMLReader::ELEMENT )
			{
				continue;
			}

			if ( $xml->name == 'rule' )
			{
				$badgesToUpdate = [];
				$insert = array(
					'action' => NULL,
					'filters' => NULL,
					'milestone' => 0,
					'points_subject' => 0,
					'points_other' => 0,
					'badge_subject' => 0,
					'badge_other' => 0,
					'enabled' => 1,
				);

				$awardSubject = NULL;
				$awardOther = NULL;

				while ( $xml->read() and $xml->name != 'rule' )
				{
					if ( $xml->nodeType != XMLReader::ELEMENT )
					{
						continue;
					}

					/* Skip the forum related rule if we don't have forums installed */
					if ( $xml->name == 'action' AND !static::canImport( $xml->readString() ) )
					{
						continue 2;
					}

					switch ( $xml->name )
					{
						case 'action':
						case 'filters':
							$insert[$xml->name] = $xml->readString();
							break;
						case 'milestone':
						case 'points_subject':
						case 'points_other':
						case 'enabled':
							$insert[$xml->name] = (int)$xml->readString();
							break;
						case 'badge_subject':
						case 'badge_other':
							$badgesToUpdate[$xml->name] = $xml->readString();
							break;
						case 'award_subject_lang':
							$awardSubject = $xml->readString();
							break;
						case 'award_other_lang':
							$awardOther = $xml->readString();
							break;
					}
				}

				$insertId = Db::i()->insert( 'core_achievements_rules', $insert );
				$allBadges[ $insertId ] = $badgesToUpdate;

				if ( ! empty( $awardSubject ) )
				{
					Lang::saveCustom( 'core', "core_award_subject_badge_{$insertId}", $awardSubject );
				}

				if ( ! empty( $awardOther ) )
				{
					Lang::saveCustom( 'core', "core_award_other_badge_{$insertId}", $awardOther );
				}
			}
			/* Badges: Start looping through each row */
			else if ( $xml->name == 'badge' )
			{
				if( $xml->nodeType != XMLReader::ELEMENT )
				{
					continue;
				}

				$insert	= [];
				$title = NULL;
				$id = NULL;

				while ( $xml->read() and $xml->name != 'badge' )
				{
					if( $xml->nodeType != XMLReader::ELEMENT )
					{
						continue;
					}

					switch( $xml->name )
					{
						case 'manually_awarded':
							$insert[ $xml->name ] = (int) $xml->readString();
							break;
						case 'title':
							$title = $xml->readString();
							break;
						case 'icon_name':
							$insert['icon_name'] = $xml->readString();
							break;
						case 'icon_data':
							$insert['icon_data'] = base64_decode( $xml->readString() );
							break;
						case 'id':
							$id = $xml->readString();
							break;
					}
				}

				if ( ! empty( $insert['icon_name'] ) and ! empty( $insert['icon_data'] ) )
				{
					$insert['image'] = (string) File::create( 'core_Badges', $insert['icon_name'], $insert['icon_data'], NULL, TRUE, NULL, FALSE );
					unset( $insert['icon_name'] );
					unset( $insert['icon_data'] );
				}

				$insertId = Db::i()->insert( 'core_badges', $insert );
				$badgeMap[ $id ] = $insertId;

				if ( ! empty( $title ) )
				{
					Lang::saveCustom( 'core', "core_badges_{$insertId}", $title );
				}
			}
		}

		if ( count( $allBadges ) )
		{
			foreach( $allBadges as $ruleId => $data )
			{
				$update = [];
				foreach( [ 'badge_subject', 'badge_other' ] as $type )
				{
					if ( isset( $data[ $type ] ) and isset( $badgeMap[ $data[ $type ] ] ) )
					{
						$update[ $type ] = $badgeMap[ $data[ $type ] ];
					}
				}

				if ( count( $update ) )
				{
					Db::i()->update( 'core_achievements_rules', $update, ['`id`=?', $ruleId ] );
				}
			}
		}

		unset( Store::i()->achievementRules );
	}

	/**
	 * Only import the node if the application is installed
	 *
	 * @param string		$value  Actionname
	 * @return bool
	 */
	public static function canImport( string $value ) : bool
	{
		/* If we are in the middle of an installation, we can always import */
		if( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'setup' )
		{
			return true;
		}

		$appKey = explode( '_', $value );
		return Application::appIsEnabled( $appKey[0] );
	}

	/**
	 * Does this rule have a milestone set?
	 *
	 * @param array|null $filters  Actionname
	 * @return bool
	 */
	public static function ruleHasMilestone( ?array $filters ): bool
	{
		if ( is_array( $filters ) and count( $filters ) )
		{
			foreach( $filters as $key => $value )
			{
				if ( mb_substr( $key, 0, 9 ) == 'milestone' )
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}
	
	/**
	 * [Node] Get the title to store in the log
	 *
	 * @return	string|null
	 */
	public function titleForLog(): ?string
	{	
		$exploded = explode( '_', $this->action );
		return Lang::load( Lang::defaultLanguage() )->get( 'AchievementAction__' . $exploded[1] . '_title' ) . " #" . $this->id;
	}
}