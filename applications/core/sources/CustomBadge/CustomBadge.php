<?php
/**
 * @brief		Custom Badges - Helper class to create styled "badge" svg icons
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		October 2023
 */

namespace IPS\core;

use Exception;
use InvalidArgumentException;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Http\Url\Raw;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecord;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\DOMDocument;
use OutOfRangeException;
use UnderflowException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class CustomBadge {
	/**
	 * @var array|null
	 */
	protected ?array $icon = null;

	protected int $numberOverlay = 0;

	protected int $sides = 5;

	/**
	 * @var "square"|"circle"|"ngon"|"star"|"flower"
	 */
	protected string $shape = "circle";

	protected string $foreground = '';

	protected string $background = '';

	protected string $border = '';

	protected int $rotation = 0;

	protected string $svgCache = '';

	protected array $_data = [];

	/**

	 * @param string $shape
	 * @param string $foreground
	 * @param string $background
	 * @param string $border
	 * @param string|array|null $icon
	 * @param int $rotation
	 * @param int $sides
	 * @param int $numberOverlay
	 * @param string $svg The svg
	 */
	public function __construct( string $shape, string $foreground, string $background, string $border, string|array|null $icon, int $rotation=0, int $sides=5, int $numberOverlay=0, string $svg = '' )
	{
		if ( is_string( $icon ) )
		{
			try
			{
				$icon = json_decode( $icon, true );
			}
			catch ( Exception ) {}
		}

		if ( isset( $icon[0] ) AND is_array( $icon[0] ) )
		{
			$icon = $icon[0];
		}

		if ( !is_array( $icon ) OR !isset( $icon['raw'] ) OR !isset( $icon['type'] ) )
		{
			$icon = null;
		}

		$this->shape = $shape;
		$this->foreground = $foreground;
		$this->background = $background;
		$this->icon = $icon;
		$this->border = $border;
		$this->rotation = $rotation % 90; // since the minimum number of points/sides is 4, any rotation greater than 90 degrees can be simplified
		$this->sides = $sides;
		$this->numberOverlay = $numberOverlay;
		$this->svgCache = $svg;
	}

	/**
	 * The SVG url of this item
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		if ( !$this->svgCache )
		{
			$this->svgCache = static::generateSVG( $this->shape, $this->foreground, $this->background, $this->border, $this->compileIconElement(), $this->rotation, $this->sides, $this->numberOverlay );
		}
		return $this->svgCache;
	}


	/**
	 * Get a Data URL with the badge svg embedded in it
	 *
	 * @return Url
	 */
	public function url() : Url
	{
		if ( isset( $this->_data['id'] ) )
		{
			return Url::createFromString( rtrim( Settings::i()->base_url, '/' ) . '/applications/core/interface/icons/icons.php?icon=' . ( $this->_data['id'] ) . '&v=' . md5( json_encode( $this->_data['raw'] ) ) );
		}

		return new Raw( (string) $this, Raw::TYPE_SVG );
	}

	/**
	 * @param ActiveRecord $record
	 * @return CustomBadge
	 */
	public static function getRecordBadge( ActiveRecord $record ) : static
	{
		$class = trim( $record::class, '\\' );
		$idCol = $record::$databaseColumnId;
		$id = $record->$idCol;

		try
		{
			$row = Db::i()->select( '*', 'core_custom_badges', ['class=? AND active_record_id=?', $class, $id] )->first();
			$badge =  new static(
				$row['shape'],
				$row['foreground'],
				$row['background'],
				$row['border'],
				$row['icon'],
				$row['rotation'],
				$row['sides'],
				$row['number_overlay'],
				$row['raw']
			);

			$badge->_data = $row;
			return $badge;
		}
		catch ( UnderflowException ) {}

		return new static( 'circle', '#ffffff', '#eeb95f', '#f7d36f', null, 0, 5, 0 );
	}

	/**
	 * Load a badge by the id in the core_custom_badges database
	 *
	 * @param int $id	The id of the badge
	 *
	 * @return static
	 *
	 * @throws OutOfRangeException
	 */
	public static function load( int $id ) : static
	{
		try
		{
			$row = Db::i()->select( '*', 'core_custom_badges', [ 'id=?', $id ] )->first();
			$badge = new static (
				$row['shape'],
				$row['foreground'],
				$row['background'],
				$row['border'],
				$row['icon'],
				$row['rotation'],
				$row['sides'],
				$row['number_overlay'],
				$row['raw']
			);

			$badge->_data = $row;
			return $badge;
		}
		catch ( UnderflowException )
		{
			throw new OutOfRangeException;
		}
	}

	/**
	 * @param ActiveRecord $record
	 * @param CustomBadge $badge
	 * @return void
	 */
	public static function saveRecordBadge( ActiveRecord $record, CustomBadge $badge ) : void
	{
		$class = trim( $record::class, '\\' );
		$idCol = $record::$databaseColumnId;
		$id = $record->$idCol;
		$data = (string) $badge;

		$id = Db::i()->insert(
			'core_custom_badges',
			[
				'sides' => $badge->sides,
				'foreground' => $badge->foreground,
				'background' => $badge->background,
				'border' => $badge->border,
				'shape' => $badge->shape,
				'icon' => json_encode( $badge->icon ),
				'rotation' => $badge->rotation,
				'number_overlay' => $badge->numberOverlay,
				'raw' => $data,
				'class' => $class,
				'active_record_id' => $id
			],
			true
		);

		$cacheKey = 'ips__custom_badge_' . $id;
		Store::i()->$cacheKey = $data;
	}


	/**
	 * Add elements to the form which are responsible for creating custom badges, and loads the JS and CSS that forms the interactive and grid-style UI
	 *
	 * @param Form $form
	 * @param bool $defaultEnabled
	 * @param array $togglesOff
	 * @param string $prefix
	 * @param bool $includeNumberOverlay
	 * @return void
	 */
	public function addBadgeFieldsToForm( Form $form, bool $defaultEnabled = false, array $togglesOff = [], string $prefix = 'badge', bool $includeNumberOverlay = true ) : void
	{
		if ( $prefix !== 'badge' )
		{
			foreach (['border', 'foreground', 'background', 'shape', 'icon', 'sides', 'rotation', 'number_overlay', 'use_custom'] as $key)
			{
				Member::loggedIn()->language()->words["{$prefix}_{$key}"] = Member::loggedIn()->language()->get( "badge_{$key}" );
			}
		}

		$form->class .= " ipsBadgePicker--form";

		$form->add( new Form\YesNo(
						"{$prefix}_use_custom",
						$defaultEnabled,
						true,
						[
							'togglesOff' => $togglesOff,
							'togglesOn' => [
								"{$form->id}_header_badge_preview",
								"{$prefix}_border",
								"{$prefix}_foreground",
								"{$prefix}_background",
								"{$prefix}_shape",
								"{$prefix}_icon",
								"{$prefix}_sides",
								"{$prefix}_rotation",
								"{$prefix}_number_overlay",
								'preview'
							]
						]
					));

		$form->addHeader( 'badge_preview' );

		Output::i()->addJsfiles( 'admin_badgepreview.js', 'core' );
		$form->addHtml( Theme::i()->getTemplate( 'achievements', 'core', 'admin' )->badgePreview( (string) $this, $prefix ) );

		$form->add( new Form\Radio( "{$prefix}_shape", $this->shape, true, ['options' => [
			'square' => 'badge_square',
			'circle' => 'badge_circle',
			'ngon' => 'badge_n_gon',
			'star' => 'badge_star',
			'flower' => 'badge_flower'
		], 'toggles' => [
			'ngon' => ["{$prefix}_sides", "{$prefix}_rotation"],
			'star' => ["{$prefix}_sides", "{$prefix}_rotation"],
			'flower' => ["{$prefix}_sides", "{$prefix}_rotation"]
		], 'rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-shape']], null, null, null, "{$prefix}_shape") );

		$form->add( new Form\Icon( "{$prefix}_icon", $this->icon ? [$this->icon] : null, false, ['useSvgIcon' => true, 'rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-icon']], null, null, null, "{$prefix}_icon" ) );

		$form->add( new Form\Color( "{$prefix}_background", $this->background, true, ['rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-background']], null, null, null, "{$prefix}_background" ) );
		$form->add( new Form\Color( "{$prefix}_border", $this->border, true, ['rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-border']], null, null, null, "{$prefix}_border" ) );

		$form->add( new Form\Color( "{$prefix}_foreground", $this->foreground, true, ['rgba' => true, 'swatches' => true, 'rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-foreground']], null, null, null, "{$prefix}_foreground" ) );

		/* The overlay is optional */
		if ( $includeNumberOverlay )
		{
			$form->add( new Form\Number( "{$prefix}_number_overlay", $this->numberOverlay, false, [ 'step' => 1, 'min' => 1, 'max' => 999, 'unlimited' => 0, 'unlimitedLang' => 'none', 'rowClasses' => ['ipsFieldRow--badge-creator'] ], null, null, null, "{$prefix}_number_overlay" ) );
		}

		$form->add( new Form\Number( "{$prefix}_sides", $this->sides, true, ['step' => 1, 'min' => 4, 'max' => 12, 'range' => true, 'rowClasses' => ['ipsFieldRow--badge-creator']], null, null, null, "{$prefix}_sides" ) );
		$form->add( new Form\Number( "{$prefix}_rotation", $this->rotation, true, ['step' => 1, 'min' => 0, 'max' => 90, 'range' => true, 'rowClasses' => ['ipsFieldRow--badge-creator']], null, null, null, "{$prefix}_rotation" ) );
	}

	/**
	 * Generate the SVG of a badge from the form values; to be used on the values of a form that was passed to Badge::addBadgeFieldsToForm()
	 *
	 * @param array $values
	 * @param string $prefix
	 *
	 * @return static
	 */
	public static function generateBadgeFromFormValues( array &$values, string $prefix = 'badge' ) : static
	{
		$badge = new static(
			$values["{$prefix}_shape"],
			$values["{$prefix}_foreground"],
			$values["{$prefix}_background"],
			$values["{$prefix}_border"],
			$values["{$prefix}_icon"][0] ?? null,
			$values["{$prefix}_rotation"],
			$values["{$prefix}_sides"],
			$values["{$prefix}_number_overlay"] ?? 0
		);

		static::clearCustomBadgeFields( $values, $prefix );

		return $badge;
	}

	/**
	 * Remove the custom badge fields from the form values
	 *
	 * @param array $values
	 * @param string $prefix
	 * @return void
	 */
	public static function clearCustomBadgeFields( array &$values, string $prefix = 'badge' ) : void
	{
		foreach ( ['shape', 'foreground', 'background', 'border', 'icon', 'rotation', 'sides', 'number_overlay', 'use_custom'] as $key )
		{
			unset( $values["{$prefix}_{$key}"] );
		}
	}

	/**
	 * Generate the SVG for a badge
	 *
	 * @param "square"|"ngon"|"circle"|"star"|"flower" 	$shape			The shape of the icon
	 * @param string 							$foreground		The color of the foreground
	 * @param string 							$background		The color of the background
	 * @param string 							$border			The border color
	 * @param string							$icon			The icon to fill inside the svg
	 * @param int 								$rotation		The rotation of the icon
	 * @param int 								$sides			The number of sides
	 * @param int								$numberOverlay	The overlay that is shown over the icon
	 * @return string
	 */
	public static function generateSVG( string $shape, string $foreground, string $background, string $border, string $icon, int $rotation=0, int $sides=5, int $numberOverlay=0 ) : string
	{
		$foreground = $foreground ?: 'currentColor';
		$numberOverlay = static::getBadgeNumberOverlay( $numberOverlay );

		return match ( $shape )
		{
			'square' => static::generateSVG_square( $foreground, $background, $border, $icon, $numberOverlay ),
			'ngon' => static::generateSVG_ngon( $foreground, $background, $border, $icon, $rotation, $sides, $numberOverlay ),
			'circle' => static::generateSVG_circle( $foreground, $background, $border, $icon, $numberOverlay ),
			'star' => static::generateSVG_star( $foreground, $background, $border, $icon, $rotation, $sides, $numberOverlay ),
			'flower' => static::generateSVG_flower( $foreground, $background, $border, $icon, $rotation, $sides, $numberOverlay ),
			default => throw new InvalidArgumentException( 'invalid_svg_shape' ),
		};
	}



	/**
	 * Generate a square icon
	 *
	 * @param string $foreground
	 * @param string $background
	 * @param string $border
	 * @param string $icon
	 * @param string $numberOverlay
	 *
	 * @return string
	 */
	protected static function generateSVG_square( string $foreground, string $background, string $border, string $icon, string $numberOverlay='' ) : string
	{
		return static::generateSVG_ngon( $foreground, $background, $border, $icon, 45, 4, $numberOverlay );
	}

	/**
	 * Generate an n-gon
	 *
	 * @param string $foreground
	 * @param string $background
	 * @param string $border
	 * @param string $icon
	 * @param int $rotation
	 * @param int $sides
	 * @param string $numberOverlay
	 *
	 * @return string
	 */
	protected static function generateSVG_ngon( string $foreground, string $background, string $border, string $icon, int $rotation=0, int $sides=5, string $numberOverlay='' ) : string
	{
		$points = static::getRadialPoints( $sides, ($sides === 4 and $rotation % 90 === 45) ? sqrt( 47.4*47.4*2 ) : 47.4 );
		$path = "";
		foreach ( $points as $point )
		{
			$path .= $path ? ' L' : 'M';
			$path .= " {$point[0]} {$point[1]}";
		}
		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<path d="{$path} Z" stroke="{$border}" stroke-linejoin="round" stroke-width="5" fill="{$background}" transform="rotate({$rotation})" transform-origin="center" data-bgcolor-placeholder="fill" data-bordercolor-placeholder="stroke" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}

	/**
	 * Generate a circle badge icon
	 *
	 * @param string $foreground
	 * @param string $background
	 * @param string $border
	 * @param string $icon
	 * @param string $numberOverlay
	 *
	 * @return string
	 */
	protected static function generateSVG_circle( string $foreground, string $background, string $border, string $icon, string $numberOverlay='' ) : string
	{
		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<circle cx="50" cy="50" r="47.4" stroke="{$border}" stroke-width="5" fill="{$background}" data-bgcolor-placeholder="fill" data-bordercolor-placeholder="stroke" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}


	/**
	 * Generate a star svg badge
	 *
	 * @param string $foreground
	 * @param string $background
	 * @param string $border
	 * @param string $icon
	 * @param int $rotation
	 * @param int $sides
	 * @param string $numberOverlay
	 *
	 * @return string
	 */
	protected static function generateSVG_star( string $foreground, string $background, string $border, string $icon, int $rotation=0, int $sides=5, string $numberOverlay='' ) : string
	{
		$innerPoints = static::getRadialPoints( $sides, (0.75 + (0.095 * ( $sides - 4 ) / 8)) * ( 45 * ( cos( pi() / $sides ) ) ), 0.5 );
		$outerPoints = static::getRadialPoints( $sides, 47.4 );
		$path = "";
		for ( $i = 0; $i < $sides; $i++ )
		{
			$path .= !$path ? 'M ' : ' L ';
			$path .= "{$outerPoints[$i][0]} {$outerPoints[$i][1]} L {$innerPoints[$i][0]} {$innerPoints[$i][1]}";
		}
		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<path d="{$path} Z" stroke="{$border}" stroke-width="5" stroke-linejoin="round" fill="{$background}" transform="rotate({$rotation})" transform-origin="center" data-bgcolor-placeholder="fill" data-bordercolor-placeholder="stroke" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}

	/**
	 * Generate a flower svg badge
	 *
	 *
	 * @param string $foreground
	 * @param string $background
	 * @param string $border
	 * @param string $icon
	 * @param int $rotation
	 * @param int $sides
	 * @param string	$numberOverlay
	 *
	 * @return string
	 */
	protected static function generateSVG_flower( string $foreground, string $background, string $border, string $icon, int $rotation=0, int $sides=5, string $numberOverlay='' ) : string
	{
		$r = 45 * ((sin(pi() / $sides)) / (sin(pi() / $sides) + 1));
		$innerRadius = 45 - $r;
		$innerPoints = static::getRadialPoints( $sides, $innerRadius );
		$innerPoints[] = [$innerPoints[0][0], $innerPoints[0][1]];


		$ro = 51 * ((sin(pi() / $sides)) / (sin(pi() / $sides) + 1));
		$outerRadius = 51 - $ro;
		$outerPoints = static::getRadialPoints( $sides, $outerRadius );
		$outerPoints[] = [$outerPoints[0][0], $outerPoints[0][1]];

		$path = "";
		for ( $i = 0; $i < $sides + 1; $i++ )
		{
			if ( !$path )
			{
				$path .= "M {$innerPoints[$i][0]} {$innerPoints[$i][1]}";
			}
			else
			{
				$path .= " A {$r} {$r} 0 1 0 {$innerPoints[$i][0]} {$innerPoints[$i][1]}";
			}
		}

		$outerPath = "";
		for ( $i = 0; $i < $sides + 1; $i++ )
		{
			if ( !$outerPath )
			{
				$outerPath .= "M {$outerPoints[$i][0]} {$outerPoints[$i][1]}";
			}
			else
			{
				$outerPath .= " A {$ro} {$ro} 0 1 0 {$outerPoints[$i][0]} {$outerPoints[$i][1]}";
			}
		}

		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<path d="{$outerPath} Z" stroke="{$border}" stroke-width="1" stroke-join="miter" fill="{$border}" transform="rotate({$rotation})" transform-origin="center" data-bordercolor-placeholder="fill,stroke" />
		<path d="{$path} Z" fill="{$background}" transform="rotate({$rotation})" transform-origin="center" data-bgcolor-placeholder="fill" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}

	/**
	 * Get the element which can be embedded in the badge SVG
	 *
	 * @return string
	 */
	public function compileIconElement() : string
	{
		$iconData = $this->icon;
		$foreground = $this->foreground;

		if ( $iconData === null )
		{
			return "";
		}

		if ( $iconData['type'] === 'fa' )
		{
			$doc = new DOMDocument();
			$doc->loadXML( $iconData['raw'] );
			$group = $doc->createElement( 'g' );
			$group->setAttribute( 'color', $foreground );
			$group->setAttribute( 'data-fgcolor-placeholder', "color" );
			foreach ( $doc->getElementsByTagName( 'svg' ) as $root )
			{
				foreach ( $root->childNodes as $node )
				{
					if ( !$group->isSameNode( $node ) )
					{
						$group->appendChild( $node );
					}
				}
				$root->appendChild( $group );
				break;
			}
			$escapedIcon = rawurlencode( $doc->saveXML( $root ?? null ) );
			return <<<SVG
<image href="data:image/svg+xml,{$escapedIcon}" width="42" height="42" x="29" y="29" data-embedded-fg-color="" />
SVG;
		}
		else
		{
			return <<<SVG
<style>
	svg text.svg__text_icon:not(#x) {
		line-height: 50px;
		vertical-align: middle;
	}
</style>
<text text-anchor="middle" font-size="50" x="50" y="70" class="svg__text_icon" data-number-overlay="" >{$iconData['raw']}</text>
SVG;
		}
	}

	/**
	 * Get the number overlay. This will appear in the bottom right of the svg icon
	 *
	 * @param int $number
	 *
	 * @return string
	 */
	protected static function getBadgeNumberOverlay( int $number ) : string
	{
		if ( $number )
		{
			return <<<SVG
<style>
	svg text.svg__text_overlay:not(#x) {
		vertical-align: middle;
		line-height: 18px;
		fill: #ffffff;
	}
</style>
<circle cx="75" cy="75" r="20" fill="#334155" stroke="#fff" stroke-width="3"  data-number-overlay="" />
<text class="svg__text_overlay" x="75" y="81.5" fill="#fff" text-anchor="middle" font-size="18" font-weight="bolder" font-family="sans-serif"  data-number-overlay="" >{$number}</text>
SVG;
		}
		return "";
	}


	/**
	 * Get points evenly distributed over a given radius from the center. This is for ngons and stars; e.g. a polygon is 5 points of equal radius to the center, with even angular spacing
	 *
	 * @param 	int 	$n 			The number of coordinates to return
	 * @param 	float 	$radius		The radius. This method assumes the origin is 50, 50 (on a 100x100 grid)
	 * @param 	float 	$offset		The amount the points are angularly "offset". 0 means the first point is straight up, 1 means the first point is placed where the second point would be placed had the offset been 0. For example, a star's concave vertices should be offset from the convex points by 0.5
	 *
	 * @return array{float, float} Returns a set of SVG coordinate vectors (this is like cartesian except y increases as you move down)
	 */
	protected static function getRadialPoints( int $n, float $radius, float $offset=0 ) : array
	{
		if ( $n < 1 )
		{
			throw new InvalidArgumentException( 'too_few_points' );
		}

		$points = [];
		$th = ( 2 * pi() ) / $n;
		$angle = pi() / 2; // this is straight 'up'
		$angle += $offset * $th;
		for ( $i = 0; $i < $n; $i++ )
		{
			$x = 50 + ($radius * cos( $angle ));
			$y = 50 - ($radius * sin( $angle ));
			$points[] = [$x, $y];

			$angle += $th;
		}

		return $points;
	}
}