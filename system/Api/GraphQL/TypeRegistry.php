<?php
/**
 * @brief		Type registry
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Api\GraphQL;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use IPS\Api\GraphQL\Types\FollowType;
use IPS\Api\GraphQL\Types\ImageType;
use IPS\Api\GraphQL\Types\ItemStateType;
use IPS\Api\GraphQL\Types\ModuleAccessType;
use IPS\Api\GraphQL\Types\MutationType;
use IPS\Api\GraphQL\Types\QueryType;
use IPS\Api\GraphQL\Types\ReputationType;
use IPS\Api\GraphQL\Types\RichTextType;
use IPS\Api\GraphQL\Types\UrlType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Type registry
 */
class TypeRegistry
{
	protected static ?QueryType $query = null;
	protected static ?MutationType $mutation = null;
	protected static ?ItemStateType $itemState = null;
	protected static ?ImageType $image = null;
	protected static ?ReputationType $reputation = null;
	protected static ?RichTextType $richText = null;
	protected static ?UrlType $url = null;
	protected static ?FollowType $follow = null;
	protected static ?ModuleAccessType $moduleAccess = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Defined to suppress static warnings
	}

	/**
	* @return QueryType
	*/
	public static function query(): QueryType
	{
		return self::$query ?: (self::$query = new QueryType());
	}

	/**
	* @return MutationType
	*/
	public static function mutation(): MutationType
	{
		return self::$mutation ?: (self::$mutation = new MutationType());
	}

	/**
	 * @return ItemStateType
	 */
	public static function itemState(): ItemStateType
	{
		return self::$itemState ?: (self::$itemState = new ItemStateType());
	}
	
	/**
	 * @return ImageType
	 */
	public static function image(): ImageType
	{
		return self::$image ?: (self::$image = new ImageType());
	}
	
	/**
	 * @return ReputationType
	 */
	public static function reputation(): ReputationType
	{
		return self::$reputation ?: (self::$reputation = new ReputationType());
	}
	
	/**
	 * @return RichTextType
	 */
	public static function richText(): RichTextType
	{
		return self::$richText ?: (self::$richText = new RichTextType());
	}

	/**
	 * @return UrlType
	 */
	public static function url(): UrlType
	{
		return self::$url ?: (self::$url = new UrlType());
	}

	/**
	 * @return FollowType
	 */
	public static function follow(): FollowType
	{
		return self::$follow ?: (self::$follow = new FollowType());
	}

	/**
	 * @return ModuleAccessType
	 */
	public static function moduleAccess(): ModuleAccessType
	{
		return self::$moduleAccess ?: (self::$moduleAccess = new ModuleAccessType());
	}

	/**
	* @return ScalarType
	*/
	public static function id(): IDType
	{
		return Type::id();
	}

	/**
	* @return ScalarType
	*/
	public static function string(): StringType
	{
		return Type::string();
	}

	/**
	* @return ScalarType
	*/
	public static function int(): IntType
	{
		return Type::int();
	}

	/**
	* @return ScalarType
	*/
	public static function float(): FloatType
	{
		return Type::float();
	}

	/**
	* @return ScalarType
	*/
	public static function boolean(): BooleanType
	{
		return Type::boolean();
	}

	/**
	 * @param mixed $type
	 * @return ListOfType
	 */
	public static function listOf( mixed $type): ListOfType
	{
		return new ListOfType($type);
	}

	/**
	 * @param array $config
	 * @return EnumType
	 */
	public static function eNum( array $config): EnumType
	{
		return new EnumType($config);
	}

	public static function inputObjectType( array $config): InputObjectType
	{
		return new InputObjectType($config);
	}

	/**
	* @param Type $type
	* @return NonNull
	*/
	public static function nonNull( mixed $type): NonNull
	{
		return new NonNull($type);
	}
}
