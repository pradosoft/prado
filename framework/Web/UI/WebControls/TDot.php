<?php
/**
 * TDot class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TPropertyValue;
use Prado\Web\IPublishable;
use Prado\Web\UI\TWebColor;

/**
 * TDot class.
 *
 * This renders an SVG of a 3D looking dot of a specific {@link getSize Size} and
 * {@link getColor Color}.  Specific coloration can be specified by setting {@link
 * getMainColor Main} and {@link getHighlightColor Highlight} colors.  The Main
 * Color is the top and middle color.  The highlight color is the bottom "refraction"
 * color.  A Main color darkened and a Highlight color lightened from the target color
 * by the {@link getDepth Depth} provides reasonable color dimensionality to the dot.
 *
 * When specifying a web RGB color, eg '#70FF90', to {@link getColor Color}, the
 * main and highlight colors are computed darker and lighter, respectively, by the
 * {@link getDepth Depth}. All named extended web colors have preset main and highlight
 * colors.
 *
 * All 16 Basic HTML color names are supported: White, Silver, Gray, Black, Red,
 * Maroon, Yellow, Olive, Lime, Green, Aqua, Teal, Blue, Navy, Fuchsia, and Purple.
 * All 125 Extended Web Colors are also presets, including Orange, Cyan, Magenta, and
 * RebeccaPurple.
 *
 * This can be used as a status indicator, specifically LimeGreen, Yellow, Red,
 * Blue, and Gray.
 *
 * If a 3D style is not preferred, set property {@link setFlat Flat} to "true" and
 * it will render a basic circle with a border.  The border is also optional depending
 * upon {@link setFlatBorder FlatBorder} being true, default true; its stroke width is
 * set by {@link setFlatBorderWidth FlatBorderWidth}, default "5%".  Flat 2d dots
 * will use {@link getColor Color} for the fill color, the slightly darker Main
 * color is used for the border when available.  If a 2d TDot Main and Highlight
 * colors are provided, the Main color is the fill color and the highlight is the
 * border color.
 *
 * The 3D drop shadow amount can be controlled with the property {@link setShadowOpacity
 * ShadowOpacity}.
 *
 * The SVG can be encoded into the ImageURL as data, published as a file, or written
 * inline into the page.  By default, when the application is in Debug Mode, TDot embeds
 * the SVG in the url; but in Normal and Performance mode it publishes the SVG data file.
 * This behavior can be changed by the {@link setPublishStyle} which accepts "Auto",
 * "Embed", "Publish", and "Inline", default "Auto".  "Inline" writes the SVG markup
 * directly into the page instead of an <img> element.
 *
 * This SVG is created by the @author and released into the public domain.
 *
 * <code>
 *   <com:TDot Size="16" Color="Green" Flat="false" />
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see https://en.wikipedia.org/wiki/Web_colors Extended Web Colors
 */
class TDot extends TImage implements IPublishable
{
	/** The extended web color combos */
	public const COLORS = [
		//HTML colors
		'white' => ['#E3E3E3', '#FFFFFF'], // #FFFFFF
		'silver' => ['#A8A8A8', '#E8E8E8'],	// #C0C0C0
		'gray' => ['#606060', '#B0B0B0'], // #808080
		'black' => ['#000000', '#3C3C3C'], // #000000

		'red' => ['#B80000', '#FF4A4A'], // #FF0000
		'maroon' => ['#500000', '#B00000'], // #800000
		'orange' => ['#E89800', '#FFC700'], // #FFA500
		'yellow' => ['#D9D900', '#FFFF80'], // #FFFF00
		'olive' => ['#505000', '#9C9C00'], // #808000
		'lime' => ['#00CC00', '#36FF36'], // #00FF00
		'green' => ['#007000', '#00B000'], // #008000

		'aqua' => ['#00C0C0', '#44FFFF'], // #00FFFF
		'cyan' => ['#00C0C0', '#44FFFF'], // #00FFFF
		'teal' => ['#006060', '#00A0A0'], // #008080
		'blue' => ['#0000B0', '#3B3BFF'], // #0000FF
		'navy' => ['#000058', '#0000C8'], // #000080
		'fuchsia' => ['#DD00DD', '#FF80FF'], // #FF00FF
		'magenta' => ['#DD00DD', '#FF80FF'], // #FF00FF
		'purple' => ['#600060', '#AC00AC'], // #800080

		// ** extended web colors **
		//	Gray colors
		'darkslategray' => ['#234040', '#446868'], //#2F4F4F
		'dimgray' => ['#505050', '#A0A0A0'], // #696969
		'slategray' => ['#5C6A78', '#90A4B6'], // #708090
		'lightslategray' => ['#637288', '#99B2BD'], // #778899
		'darkgray' => ['#909090', '#D0D0D0'], // #A9A9A9
		'lightgray' => ['#BDBDBD', '#EFEFEF'], // #D3D3D3
		'gainsboro' => ['#C5C5C5', '#F9F9F9'], // #DCDCDC

		//	White colors
		'mistyrose' => ['#E0C6C3', '#FFF0ED'], // #FFE4E1
		'antiquewhite' => ['#DCCDB3', '#FDF4E8'], // #FAEBD7
		'linen' => ['#DCD3C8', '#FDF7F3'], // #FAF0E6
		'beige' => ['#D6D6BC', '#FAFAE6'], // #F5F5DC
		'whitesmoke' => ['#D8D8D8', '#FDFDFD'], // #F5F5F5
		'lavenderblush' => ['#E0D0D4', '#FFF6FA'], // #FFF0F5
		'oldlace' => ['#E1DAC4', '#FFFFFF'], // #FDF5E6
		'aliceblue' => ['#D0D8E0', '#F6FAFF'], // #F0F8FF
		'seashell' => ['#E3D6CE', '#FFFAF6'], // #FFF5EE
		'ghostwhite' => ['#DBDBE3', '#FFFFFF'], // #F8F8FF
		'honeydew' => ['#D0E0D0', '#F3FFF3'], // #F0FFF0
		'floralwhite' => ['#E0D9D0', '#FFFBF6'], // #FFFAF0
		'azure' => ['#D0E0E0', '#F5FFFF'], // #F0FFFF
		'mintcream' => ['#D0E3D9', '#F8FFFC'], // #F5FFFA
		'snow' => ['#E3DDDD', '#FFFDFD'], // #FFFAFA
		'ivory' => ['#E7E7D0', '#FFFFF6'], // #FFFFF0

		//	Pink Colors
		'mediumvioletred' => ['#B31370', '#E520A8'], // #C71585
		'deeppink' => ['#E31180', '#FF69B0'], // #FF1493
		'palevioletred' => ['#BD5F80', '#F288AD'], // #DB7093
		'hotpink' => ['#E3529C', '#FFA0D1'], // #FF69B4
		'lightpink' => ['#E39FA6', '#FFCAD8'], // #FFB6C1
		'pink' => ['#E3AAB0', '#FFD8E3'], // #FFC0CB

		//	Red Colors
		'darkred' => ['#5B0000', '#BB0B0B'], // #8B0000
		'firebrick' => ['#981111', '#CE4444'], // #B22222
		'crimson' => ['#B91030', '#F23053'], // #DC143C
		'indianred' => ['#B85050', '#E87B7B'], // #CD5C5C
		'lightcoral' => ['#DB7070', '#F49A9A'], // #F08080
		'salmon' => ['#E36A5D', '#FE9D90'], // #FA8072
		'darksalmon' => ['#CF7F64', '#F1B498'], // #E9967A
		'lightsalmon' => ['#FF9060', '#FFBF98'], // #FFA07A

		//	Orange Colors
		'orangered' => ['#E33300', '#FF6833'], // #FF4500
		'tomato' => ['#E3543A', '#FF8069'], // #FF6347
		'darkorange' => ['#E37100', '#FFA400'], // #FF8C00
		'coral' => ['#E36238', '#FFA080'], // #FF7F50

		//	Yellow Colors
		'darkkhaki' => ['#A09948', '#D8CF85'], // #BDB76B
		'gold' => ['#E0C000', '#FFE020'], // #FFD700
		'khaki' => ['#D3CC72', '#F8F2A4'], // #F0E68C
		'peachpuff' => ['#E3B09D', '#FFE5D0'], // #FFDAB9
		'palegoldenrod' => ['#D2CC99', '#F9F3BF'], // #EEE8AA
		'moccasin' => ['#E3C89D', '#FFF9CA'], // #FFE4B5
		'papayawhip' => ['#E3D2BB', '#FFF7E8'], // #FFEFD5
		'lightgoldenrodyellow' => ['#DFDFB6', '#FCFCE8'], // #FAFAD2
		'lemonchiffon' => ['#E7E3A7', '#FFFDE0'], // #FFFACD
		'lightyellow' => ['#E7E7BF', '#FFFFF2'], // #FFFFE0

		//	Brown Colors
		'brown' => ['#8D1616', '#C94141'], // #A52A2A
		'saddlebrown' => ['#703009', '#A85F2F'], // #8B4513
		'sienna' => ['#883D13', '#BA6D42'], // #A0522D
		'chocolate' => ['#BA5110', '#E57D3A'],  // #D2691E
		'darkgoldenrod' => ['#A06F08', '#D09E16'], // #B8860B
		'peru' => ['#B66E24', '#E69F57'], // #CD853F
		'rosybrown' => ['#a47777', '#d4a7a7'], // #BC8F8F
		'goldenrod' => ['#C28D16', '#F0BD30'], // #DAA520
		'sandybrown' => ['#D88B48', '#FBBC80'], // #F4A460
		'tan' => ['#ba9c74', '#eacca4'], // #D2B48C
		'burlywood' => ['#c6a06f', '#f6d09f'], // #DEB887
		'wheat' => ['#ddc69b', '#fbf2cb'], // #F5DEB3
		'navajowhite' => ['#e7c695', '#fff6c5'], // #FFDEAD
		'bisque' => ['#e7ccac', '#fff5d4'], // #FFE4C4
		'blanchedalmond' => ['#e7d3b5', '#fffCe2'], // #FFEBCD
		'cornsilk' => ['#e7e0c4', '#fffFf0'], // #FFF8DC

		//	Green Colors
		'darkgreen' => ['#004c00', '#187F18'], // #006400
		'darkolivegreen' => ['#3d5317', '#6d8347'], // #556B2F
		'forestgreen' => ['#117B11', '#3aBC3a'], // #228B22
		'seagreen' => ['#14783f', '#46b36f'], // #2E8B57
		'olivedrab' => ['#53760b', '#83a63b'], // #6B8E23
		'mediumseagreen' => ['#249b59', '#54cb89'], // #3CB371
		'limegreen' => ['#1ab51a', '#4ae54a'], // #32CD32

		'springgreen' => ['#00e767', '#18ff97'], // #00FF7F
		'mediumspringgreen' => ['#00e282', '#18ffb2'], // #00FA9A
		'darkseagreen' => ['#77a477', '#a7d4a7'], // #8FBC8F
		'mediumaquamarine' => ['#4eb592', '#7ee5c2'], // #66CDAA
		'yellowgreen' => ['#82b51a', '#b2e54a'], // #9ACD32
		'lawngreen' => ['#64e400', '#94ff18'], // #7CFC00
		'chartreuse' => ['#67e700', '#97ff18'], // #7FFF00
		'lightgreen' => ['#78d678', '#a8ffa8'], // #90EE90
		'greenyellow' => ['#95e717', '#c5ff47'], // #ADFF2F
		'palegreen' => ['#80e380', '#b0ffb0'], // #98FB98

		//	Cyan Colors
		'darkcyan' => ['#007373', '#06AEAE'], // #008B8B
		'lightseagreen' => ['#089a92', '#38cac2'], // #20B2AA
		'cadetblue' => ['#478688', '#77b6b8'], // #5F9EA0
		'darkturquoise' => ['#00b6b9', '#18e6e9'], // #00CED1
		'mediumturquoise' => ['#30b9b4', '#60e9e4'], // #48D1CC
		'turquoise' => ['#28c8b8', '#58f8e8'], // #40E0D0
		'aquamarine' => ['#67e7bc', '#97ffec'], // #7FFFD4
		'paleturquoise' => ['#97d6d6', '#c7ffff'], // #AFEEEE
		'lightcyan' => ['#cBeCeC', '#fAffff'], // #E0FFFF

		//	Blue Colors
		'midnightblue' => ['#0A0A58', '#3434B7'], // #191970
		'darkblue' => ['#00006A', '#0000D4'], // #00008B
		'mediumblue' => ['#000098', '#1A1Af0'], // #0000CD
		'royalblue' => ['#2951c9', '#5981f9'], // #4169E1
		'steelblue' => ['#2e6a9c', '#5e9acc'], // #4682B4
		'dodgerblue' => ['#0678e7', '#36a8ff'], // #1E90FF
		'deepskyblue' => ['#00a7e7', '#18d7ff'], // #00BFFF
		'cornflowerblue' => ['#4c7dd5', '#7cadff'], // #6495ED
		'skyblue' => ['#6fb6d3', '#9fe6ff'], // #87CEEB
		'lightskyblue' => ['#6fb6e2', '#9fe6ff'], // #87CEFA
		'lightsteelblue' => ['#98acc6', '#c8dcf6'], // #B0C4DE
		'lightblue' => ['#95c0ce', '#c5f0fe'], // #ADD8E6
		'powderblue' => ['#98c8ce', '#c8f8fe'], // #B0E0E6

		//	Purple, violet, magenta Colors
		'indigo' => ['#33006a', '#63009a'], // #4b0082'], // #4B0082
		'darkmagenta' => ['#740074', '#B900B9'],  // #8B008B
		'darkviolet' => ['#7c00bb', '#ac00eb'],  // #9400D3
		'darkslateblue' => ['#302573', '#6055a3'],  // #483D8B
		'blueviolet' => ['#7213ca', '#a243fa'], // #8A2BE2
		'darkorchid' => ['#811ab4', '#b14ae4'],  // #9932CC
		'slateblue' => ['#5242b5', '#8272e5'],  // #6A5ACD
		'mediumslateblue' => ['#6350d6', '#9380ff'],  // #7B68EE
		'mediumorchid' => ['#a23dbb', '#d26deb'],  // #BA55D3
		'mediumpurple' => ['#7b58c3', '#ab88f3'],  // #9370DB
		'orchid' => ['#c258be', '#f288ee'],  // #DA70D6
		'violet' => ['#d66ad6', '#ff9aff'],  // #EE82EE
		'plum' => ['#c588c5', '#f5b8f5'],  // #DDA0DD
		'thistle' => ['#c0a7c0', '#f0d7f0'],  // #D8BFD8
		'lavender' => ['#cacae0', '#f8f8ff'],  // #E6E6FA

		//	Other Colors
		'rebeccapurple' => ['#4e1b81', '#7e4bb1'] // #663399
	];

	/**
	 * The default {@see getDepth Depth}: the color offset that computes the Main and
	 * Highlight colors from a single color. The value is fit to track the {@see COLORS}
	 * presets jointly with {@see NUDGE_EXPONENT} and {@see MAIN_DEPTH_SCALE}.
	 * @since 4.4.0
	 */
	public const DEFAULT_DEPTH = 24;

	/**
	 * The exponent of the {@see nudge} depth falloff curve. A higher exponent keeps the
	 * midtones closer to the source and concentrates the change toward the channel
	 * extremes. The value is fit to track the {@see COLORS} presets most closely,
	 * jointly with {@see DEFAULT_DEPTH}, {@see MAIN_DEPTH_SCALE}, and {@see HSL_DEPTH_FRACTION}.
	 */
	protected const NUDGE_EXPONENT = 5.819;

	/**
	 * The Main (darken) depth as a fraction of the Highlight (lighten) depth. The
	 * presets darken less than they lighten, so the Main swing is scaled down. The value
	 * is fit to the presets jointly with {@see DEFAULT_DEPTH} and {@see NUDGE_EXPONENT}.
	 */
	protected const MAIN_DEPTH_SCALE = 0.9633;

	/**
	 * The share of the color offset applied as a hue-preserving HSL-lightness shift before
	 * the per-channel RGB {@see nudge} handles the remainder. This cascade tracks the
	 * {@see COLORS} presets more closely than nudging in either color space alone. The
	 * value is fit to the presets jointly with the other tuning constants.
	 * @since 4.4.0
	 */
	protected const HSL_DEPTH_FRACTION = 0.2312;

	/**
	 * Cross-modulates the {@see HSL_DEPTH_FRACTION} share by the source chroma: high-chroma
	 * colors take a larger hue-preserving lightness shift, near-grays a smaller one. The
	 * factor is `1 + HSL_CHROMA_MOD * (2 * chroma - 1)`. The value is fit to the presets
	 * jointly with the other tuning constants.
	 * @since 4.4.0
	 */
	protected const HSL_CHROMA_MOD = 0.2157;

	/**
	 * Cross-modulates the per-channel RGB share by the source saturation: saturated colors
	 * take a smaller per-channel shift (leaning on the lightness move), near-grays a larger
	 * one. The factor is `1 + RGB_SAT_MOD * (2 * saturation - 1)`. The value is fit to the
	 * presets jointly with the other tuning constants.
	 * @since 4.4.0
	 */
	protected const RGB_SAT_MOD = -0.2100;

	// =========================================================================
	// IPublishable API
	// =========================================================================

	/**
	 * @return string The virtual file path of the Dot SVG.
	 * @see \Prado\Web\IPublishable
	 */
	public function getAssetFilePath()
	{
		return '/prado/tdot-svgs/tdot-' . $this->getDataID() . '.svg';
	}

	/**
	 * The file doesn't change over time.
	 * @return int The modification date of the asset.
	 * @see \Prado\Web\IPublishable
	 */
	public function getAssetModificationDate()
	{
		return 0;
	}

	/**
	 * Generates the SVG and writes it to the destination file.
	 * @param string $dst The destination file path of the asset.
	 * @return ?bool Whether the content was written.
	 * @see \Prado\Web\IPublishable
	 */
	public function publish($dst): ?bool
	{
		return file_put_contents($dst, $this->generateSVG()) !== false;
	}

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * Gets the specified color of the dot. It is a {@see \Prado\Web\UI\TWebColor}
	 * constant name when the color matches one (eg "Green"), otherwise a hex color
	 * value (eg "#998877").
	 * @return null|string the TWebColor constant name or a hex color value of the dot.
	 */
	public function getColor()
	{
		return $this->getViewState('color');
	}

	/**
	 * Sets the specified color of the dot.
	 *
	 * The color is a {@see \Prado\Web\UI\TWebColor} constant name (eg "Blue" or
	 * "DeepSkyBlue") or a hex color value (eg "#0000FF"). A named web color uses its
	 * preset Main and Highlight colors. A hex value computes the Main and Highlight
	 * values lower and higher than the provided color, respectively, to give the 3D dot
	 * depth; the computation cascades a hue-preserving lightness shift with a per-channel
	 * adjustment (see {@see shade}). A web color prefixed with '-' overrides the preset
	 * with the standard web color and the computed depth, eg "-Blue".
	 *
	 * When the resolved color matches a TWebColor constant, {@see getColor} returns that
	 * constant name; otherwise it returns the hex value.
	 *
	 * @param string $color the TWebColor constant name or a hex color value.
	 * @throws TInvalidDataValueException If the color is not supported.
	 */
	public function setColor($color)
	{
		$tcolor = trim($color);
		$forceStandard = strlen($tcolor) > 0 && $tcolor[0] === '-';
		if ($forceStandard) {
			$tcolor = substr($tcolor, 1);
		}
		$preset = self::COLORS[strtolower($tcolor)] ?? null;
		if ($preset !== null && !$forceStandard) {
			$hex = TWebColor::valueOfConstant($tcolor, false);
			$this->setViewState('color', TWebColor::constantOfValue($hex, false) ?? $tcolor);
			$this->setViewState('mainColor', $preset[0]);
			$this->setViewState('highlightColor', $preset[1]);
			return;
		}
		$hex = TPropertyValue::ensureHexColor($tcolor);
		$r = hexdec(substr($hex, 1, 2));
		$g = hexdec(substr($hex, 3, 2));
		$b = hexdec(substr($hex, 5, 2));
		$depth = $this->getDepth();
		$this->setViewState('color', TWebColor::constantOfValue($hex, false) ?? $hex);
		$this->setViewState('mainColor', $this->shade($r, $g, $b, -$depth * self::MAIN_DEPTH_SCALE, true));
		$this->setViewState('highlightColor', $this->shade($r, $g, $b, $depth, false));
	}

	/**
	 * This is the specific color of the dot top and middle sections, as a hex color
	 * value, eg "#0000FF".
	 * @return string The main color of the dot. Default "#888888"
	 */
	public function getMainColor()
	{
		return $this->getViewState('mainColor', '#888888');
	}

	/**
	 * This is the specific color of the dot top and middle sections.
	 * It is a {@see \Prado\Web\UI\TWebColor} constant name or a hex color value, eg
	 * "Blue" or "#0000FF", stored as the hex value. Setting it clears {@see getColor}.
	 * @param string $color The main color of the dot.
	 * @throws TInvalidDataValueException If the color is not supported.
	 */
	public function setMainColor($color)
	{
		$this->clearViewState('color');
		$this->setViewState('mainColor', TPropertyValue::ensureHexColor($color));
	}

	/**
	 * The specific highlight color at the bottom of the dot, as a hex color value, eg
	 * "#0000FF".
	 * @return string The highlight color of the dot. Default "#E0E0E0"
	 */
	public function getHighlightColor()
	{
		return $this->getViewState('highlightColor', '#E0E0E0');
	}

	/**
	 * The specific highlight color at the bottom of the dot.
	 * It is a {@see \Prado\Web\UI\TWebColor} constant name or a hex color value, eg
	 * "Blue" or "#0000FF", stored as the hex value. Setting it clears {@see getColor}.
	 * @param string $color The highlight color of the dot.
	 * @throws TInvalidDataValueException If the color is not supported.
	 */
	public function setHighlightColor($color)
	{
		$this->clearViewState('color');
		$this->setViewState('highlightColor', TPropertyValue::ensureHexColor($color));
	}

	/**
	 * @return null|int The pixel size dot, default 21
	 */
	public function getSize()
	{
		return $this->getViewState('size', 21);
	}

	/**
	 * @param null|int $size The pixel size dot
	 */
	public function setSize($size)
	{
		$_size = TPropertyValue::ensureInteger($size);
		if ($_size < 1 && $size !== null && $size !== '') {
			throw new TInvalidDataValueException('dot_bad_size', $size);
		}
		if (!$size) {
			$_size = null;
		}
		$this->setViewState('size', $_size);
	}

	/**
	 * The amount the colors are offset from the specified color to compute the
	 * {@link getMainColor MainColor} and {@link getHighlightColor HighlightColor}
	 * to give the dot depth when only one color is specified.
	 * This is only used in {@link setColor}.
	 * @return null|int The color offset from the original color, default {@see DEFAULT_DEPTH}.
	 */
	public function getDepth()
	{
		return $this->getViewState('depth', self::DEFAULT_DEPTH);
	}

	/**
	 * @param int $value The color offset from the original color.
	 */
	public function setDepth($value)
	{
		$value = max(0, min(255, TPropertyValue::ensureInteger($value)));
		$this->setViewState('depth', $value);
		if (($color = $this->getColor()) !== null) {
			$this->setColor($color);
		}
	}

	/**
	 * The opacity of the 3d dot shadow.
	 * @return float The shadow Opacity, default (phi - 1) = 0.618
	 */
	public function getShadowOpacity()
	{
		return $this->getViewState('shadowOpacity', 0.618);
	}

	/**
	 * The opacity of the 3d dot shadow.
	 * @param float $opacity The shadow Opacity
	 */
	public function setShadowOpacity($opacity)
	{
		$this->setViewState('shadowOpacity', max(0.0, min(1.0, TPropertyValue::ensureFloat($opacity))));
	}

	/**
	 * @return bool Render a 2D version of the dot, default false
	 */
	public function getFlat()
	{
		return $this->getViewState('flat', false);
	}

	/**
	 * @param bool $flat Render a 2D version of the dot
	 */
	public function setFlat($flat)
	{
		$this->setViewState('flat', TPropertyValue::ensureBoolean($flat));
	}

	/**
	 * @return bool Does the flat 2d dot have a border, default true
	 */
	public function getFlatBorder()
	{
		return $this->getViewState('border', true);
	}

	/**
	 * @param bool $border Does the flat 2d dot have a border
	 */
	public function setFlatBorder($border)
	{
		$this->setViewState('border', TPropertyValue::ensureBoolean($border));
	}

	/**
	 * The stroke width of the flat 2d dot border, as an SVG stroke-width value: a
	 * percentage of the dot (eg "5%") or a length (eg "2"). It applies only when
	 * {@link getFlatBorder FlatBorder} is true.
	 * @return string The flat border stroke width, default "5%".
	 */
	public function getFlatBorderWidth()
	{
		return $this->getViewState('borderWidth', '5%');
	}

	/**
	 * Sets the flat 2d dot border stroke width. The value is an SVG stroke-width: a number
	 * (eg "2"), a percentage of the dot (eg "5%"), or a number with a CSS length unit (eg
	 * "2px"). An empty value restores the default. An invalid value throws.
	 * @param string $width The flat 2d dot border stroke width.
	 * @throws TInvalidDataValueException If the width is not a valid SVG stroke-width.
	 */
	public function setFlatBorderWidth($width)
	{
		$width = trim((string) $width);
		if ($width === '') {
			$this->clearViewState('borderWidth');
			return;
		}
		if (!preg_match('/^\d+(\.\d+)?(%|px|em|rem|pt|pc|cm|mm|in|ex|ch|vw|vh|vmin|vmax)?$/i', $width)) {
			throw new TInvalidDataValueException('dot_bad_border_width', $width);
		}
		$this->setViewState('borderWidth', $width);
	}

	/**
	 * The Publishing style of the dot:
	 *    -"Auto" encodes the SVG into the ImageURL during ApplicationMode::Debug and
	 *      publishes the asset in ApplicationMode::Normal and ApplicationMode::Performance.
	 *    -"Embed" puts the SVG encoded as data into the ImageURL.
	 *    -"Publish" publishes the virtual SVG as an asset.
	 *    -"Inline" renders the SVG markup directly into the page instead of an <img> element.
	 * @return string The publish style of the dot, default 'Auto'
	 */
	public function getPublishStyle()
	{
		return $this->getViewState('publishStyle', 'Auto');
	}

	/**
	 * The Publishing style of the dot:
	 *    -"Auto" for "Embed"s for Applications in Debug Mode, and "Publish"s for Normal and
	 *		Performance mode.  This is the default.
	 *    -"Embed" encodes the SVG as data into the Image URL.
	 *	  -"Publish" publishes the file with the TAssetManager and links to the file.
	 *	  -"Inline" renders the SVG markup directly into the page instead of an <img> element.
	 * @param string $style The publish style of the dot
	 */
	public function setPublishStyle($style)
	{
		$style = trim($style);
		TPropertyValue::ensureEnum(strtolower($style), ['auto', 'embed', 'publish', 'inline']);
		$this->setViewState('publishStyle', $style);
	}

	// =========================================================================
	// Rendering
	// =========================================================================

	/**
	 * Renders the dot. The "Inline" publish style nulls the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
	 * and renders the SVG as an <svg> element carrying the control's attributes (id, class,
	 * style, size); every other style renders the {@see TImage} <img> element whose source
	 * is the {@see getImageUrl ImageUrl}.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose.
	 */
	public function render($writer)
	{
		if ($this->resolvePublishStyle() === 'inline') {
			$this->_decorator = null;
		}
		parent::render($writer);
	}

	/**
	 * @return string The HTML tag name: "svg" for the "Inline" publish style, otherwise "img".
	 */
	protected function getTagName()
	{
		return ($this->resolvePublishStyle() === 'inline') ? 'svg' : parent::getTagName();
	}

	/**
	 * Adds the rendered attributes. The "Inline" publish style writes the <svg> root
	 * attributes (namespace, aspect ratio, size) followed by the generic web control
	 * attributes, skipping the <img>-specific source and alternate text. Every other style
	 * renders the {@see TImage} <img> attributes.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose.
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->resolvePublishStyle() === 'inline') {
			$writer->addAttribute('xmlns', 'http://www.w3.org/2000/svg');
			$writer->addAttribute('preserveAspectRatio', 'xMidYMid meet');
			$writer->addAttribute('width', (string) $this->getSize());
			$writer->addAttribute('height', (string) $this->getSize());
			TWebControl::addAttributesToRender($writer);
			return;
		}
		$writer->addAttribute('height', $this->getSize());
		$writer->addAttribute('width', $this->getSize());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the body content. The "Inline" publish style writes the inner SVG markup
	 * inside the <svg> element; every other style renders the empty <img> body.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose.
	 */
	public function renderContents($writer)
	{
		if ($this->resolvePublishStyle() === 'inline') {
			$writer->write($this->generateInnerSVG());
			return;
		}
		parent::renderContents($writer);
	}

	/**
	 * The image source for the dot's <img> element. "Publish" links to the asset file
	 * published through the {@see \Prado\Web\TAssetManager}; "Embed" (and "Inline", which
	 * has no URL of its own) encode the SVG as a base64 data URI.
	 * @return string The published asset URL or the encoded SVG image data.
	 */
	public function getImageUrl()
	{
		if ($this->resolvePublishStyle() === 'publish') {
			$app = $this->getApplication();
			if (($manager = $app ? $app->getAssetManager() : null) !== null) {
				return $manager->publishFilePath($this);
			}
		}
		return 'data:image/svg+xml;base64,' . base64_encode($this->generateSVG());
	}

	/**
	 * Resolves the effective publish style, mapping "Auto" to "Embed" in
	 * {@see \Prado\TApplicationMode::Debug} and to "Publish" otherwise.
	 * @return string The resolved style: "embed", "publish", or "inline".
	 */
	protected function resolvePublishStyle(): string
	{
		$style = strtolower($this->getPublishStyle());
		if ($style === 'auto') {
			$style = ($this->getApplication()->getMode() === TApplicationMode::Debug) ? 'embed' : 'publish';
		}
		return $style;
	}

	/**
	 * Nudges a color value and rounds it to an integer. This is the rounded form of
	 * {@see nudgeRaw}; the cascade in {@see shade} uses the raw form between stages and
	 * rounds only the final color.
	 * @param float $v the value being changed, [0..255].
	 * @param float $n the amount of change.
	 * @param null|bool|int $style the style of the nudge; see {@see nudgeRaw}.
	 * @return int $v nudged by $n, scaled at the boundaries and rounded.
	 */
	protected function nudge($v, $n, $style = null)
	{
		return (int) round($this->nudgeRaw($v, $n, $style));
	}

	/**
	 * These are the $n scale factor based upon $v and $style:
	 *
	 *  1|  ___________  	2|              /	2|\
	 *   | /           \ 	 |   $n < 0    / 	 | \  $n > 0
	 *   ||     $n      |	1|  -----------  	1|  -----------
	 *   ||   scalars   |	 | / reducing $v 	 |increasing $v\
	 *  0|/_____________\	0|/______________	0|______________\
	 *    0     $v    255	 0      $v    255	 0     $v     255
	 *      $style = 0		   $style = true	   $style = false
	 *
	 * @param float $v the value being changed, [0..255].
	 * @param float $n the amount of change.
	 * @param null|bool|int $style the style of the nudge. if null, $style is dependent
	 *   upon $n < 0 (is true). True is top exaggerated, false is bottom exaggerated.
	 *   Any other value (eg. 0) is tapered at the edges of the color value scale.
	 * @return float $v nudged by $n, scaled at the boundaries (unrounded).
	 */
	private function nudgeRaw($v, $n, $style = null)
	{
		$u = 2 * ($v / 255 - 0.5);
		$sign = ($u < 0) ? -1 : 1;
		$style = ($style === null) ? $n < 0 : $style;
		if ($style === true) {
			$f = 1 + $sign * pow(abs($u), self::NUDGE_EXPONENT);
		} elseif ($style === false) {
			$f = 1 - $sign * pow(abs($u), self::NUDGE_EXPONENT);
		} else {
			$f = 1 - pow(abs($u), self::NUDGE_EXPONENT + 1);
		}
		return $v + $n * $f;
	}

	/**
	 * Computes a shaded color from a source RGB by cascading two cross-modulated stages,
	 * tracking the {@see COLORS} presets more closely than nudging in either color space
	 * alone. The pipeline:
	 *
	 * 1. Split the offset: a {@see HSL_DEPTH_FRACTION} share to the HSL stage, the rest to RGB.
	 * 2. Scale the HSL share by source chroma ({@see HSL_CHROMA_MOD}) and the RGB share by
	 *    source saturation ({@see RGB_SAT_MOD}) — each stage modulated by the other space.
	 * 3. HSL stage: {@see nudgeRaw} the lightness for a hue-preserving overall lighten/darken.
	 * 4. RGB stage: {@see nudge} each channel by the remainder for the per-channel character.
	 * 5. Round once to the final hex color.
	 *
	 * @param float $r the source red channel, [0..255].
	 * @param float $g the source green channel, [0..255].
	 * @param float $b the source blue channel, [0..255].
	 * @param float $n the signed color offset; negative darkens, positive lightens.
	 * @param bool $style the nudge style; true exaggerates the top, false the bottom.
	 * @return string the shaded hex color value.
	 */
	private function shade($r, $g, $b, $n, $style)
	{
		$chroma = (max($r, $g, $b) - min($r, $g, $b)) / 255;
		[$h, $s, $l] = $this->rgbToHsl($r, $g, $b);
		$hslDepth = self::HSL_DEPTH_FRACTION * $n * (1 + self::HSL_CHROMA_MOD * (2 * $chroma - 1));
		$l = $this->nudgeRaw($l * 255, $hslDepth, $style) / 255;
		[$r, $g, $b] = $this->hslToRgb($h, $s, $l);
		$rgbDepth = (1 - self::HSL_DEPTH_FRACTION) * $n * (1 + self::RGB_SAT_MOD * (2 * $s - 1));
		return TPropertyValue::ensureHexColor($this->nudge($r, $rgbDepth, $style), $this->nudge($g, $rgbDepth, $style), $this->nudge($b, $rgbDepth, $style));
	}

	/**
	 * Converts an RGB color to HSL.
	 * @param float $r the red channel, [0..255].
	 * @param float $g the green channel, [0..255].
	 * @param float $b the blue channel, [0..255].
	 * @return array the hue, saturation, and lightness, each [0..1].
	 */
	private function rgbToHsl($r, $g, $b)
	{
		$r /= 255;
		$g /= 255;
		$b /= 255;
		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$l = ($max + $min) / 2;
		if ($max === $min) {
			return [0.0, 0.0, $l];
		}
		$d = $max - $min;
		$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
		if ($max === $r) {
			$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
		} elseif ($max === $g) {
			$h = ($b - $r) / $d + 2;
		} else {
			$h = ($r - $g) / $d + 4;
		}
		return [$h / 6, $s, $l];
	}

	/**
	 * Converts an HSL color to RGB.
	 * @param float $h the hue, [0..1].
	 * @param float $s the saturation, [0..1].
	 * @param float $l the lightness, [0..1].
	 * @return array the red, green, and blue channels, each [0..255].
	 */
	private function hslToRgb($h, $s, $l)
	{
		$l = max(0.0, min(1.0, $l));
		if ($s == 0.0) {
			$r = $g = $b = $l;
		} else {
			$q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
			$p = 2 * $l - $q;
			$r = $this->hueToRgb($p, $q, $h + 1 / 3);
			$g = $this->hueToRgb($p, $q, $h);
			$b = $this->hueToRgb($p, $q, $h - 1 / 3);
		}
		return [$r * 255, $g * 255, $b * 255];
	}

	/**
	 * Resolves one RGB channel from the HSL intermediates for {@see hslToRgb}.
	 * @param float $p the lower bound intermediate.
	 * @param float $q the upper bound intermediate.
	 * @param float $t the hue offset for this channel.
	 * @return float the channel value, [0..1].
	 */
	private function hueToRgb($p, $q, $t)
	{
		if ($t < 0) {
			$t += 1;
		}
		if ($t > 1) {
			$t -= 1;
		}
		if ($t < 1 / 6) {
			return $p + ($q - $p) * 6 * $t;
		}
		if ($t < 1 / 2) {
			return $q;
		}
		if ($t < 2 / 3) {
			return $p + ($q - $p) * (2 / 3 - $t) * 6;
		}
		return $p;
	}

	/**
	 * @param string $color Web color maybe starting with '#'
	 * @return string The color without the # and lowercased.
	 */
	private function sanitizeColor($color)
	{
		if (strlen($color) > 0 && $color[0] == '#') {
			$color = substr($color, 1);
		}
		return strtolower($color);
	}

	/**
	 * Encodes {@see getFlatBorderWidth FlatBorderWidth} into a file-name token. The percent
	 * sign maps to "pct" so a percentage and a bare length yield distinct names (eg "5%"
	 * becomes "5pct", "5" stays "5").
	 * @return string The file-name-safe border width token.
	 */
	private function sanitizeBorderWidth()
	{
		return preg_replace('/[^a-z0-9]/i', '', str_replace('%', 'pct', $this->getFlatBorderWidth()));
	}

	/**
	 * The encode the parameters of the dot to make a unique identifier.
	 * @return string unique ID of the SVG file.
	 */
	protected function getDataID()
	{
		$color = $this->getColor();
		$main = $this->getMainColor();
		$highlight = $this->getHighlightColor();
		if ($this->getFlat()) {
			$strokeColor = $color ? ($main ? $main : $highlight) : $highlight;
			$fill = $color ? $color : $main;
			return $this->sanitizeColor($fill) . ($this->getFlatBorder() ? '-' . $this->sanitizeColor($strokeColor) . '-' . $this->sanitizeBorderWidth() : '');
		}
		$opacity = $this->getShadowOpacity();
		$size = $this->getSize();
		$blurx = round($size * 0.08); //  $size / 12.5
		$blury = round($size * 0.023); // $size / 43.5
		$shBlur = floor($size * 0.02); // $size / 50
		$colorId = $color ? $this->sanitizeColor($color) : $this->sanitizeColor($main) . '-' . $this->sanitizeColor($highlight);
		return $colorId . '-' . $blurx . $blury . $shBlur . '-' . round($opacity * 255);
	}

	/**
	 * Generates the full SVG document for the dot, wrapping {@see generateInnerSVG} in the
	 * <svg> root element. This is the markup used by the "Embed" and "Publish" styles.
	 * @return string the SVG of the colored dot.
	 */
	protected function generateSVG()
	{
		return "<svg xmlns='http://www.w3.org/2000/svg' preserveAspectRatio='xMidYMid meet'>" . $this->generateInnerSVG() . "</svg>";
	}

	/**
	 * Generates the inner SVG markup for both the flat 2D and 3D versions based upon set
	 * colors, shadows and borders. The "Inline" style renders this inside an <svg> element
	 * built from the control's attributes.
	 * @return string the inner SVG markup of the colored dot.
	 * @author 3D SVG & 2D SVG - Brad Anderson <belisoful@icloud.com>  [public domain]
	 */
	protected function generateInnerSVG()
	{
		$main = $this->getMainColor();
		$highlight = $this->getHighlightColor();
		if ($this->getFlat()) {
			$color = $this->getColor();
			$strokeColor = $color ? ($main ? $main : $highlight) : $highlight;
			$color = $color ? $color : $main;
			$stroke = $this->getFlatBorder() ? " stroke='" . $strokeColor . "' stroke-width='" . $this->getFlatBorderWidth() . "' " : '';
			return "<circle fill='{$color}' cx='50%' cy='50%' r='46%'{$stroke}/>";
		}

		$opacity = $this->getShadowOpacity();
		$size = $this->getSize();

		$blurx = round($size * 0.08); //	/ 12.5
		$blury = round($size * 0.023); //	/ 43.5
		$shBlur = floor($size * 0.02); //	/ 50
		return "<defs><filter id='hblur'><feGaussianBlur in='SourceGraphic' stdDeviation='{$blurx} {$blury}' /></filter><radialGradient id='highgrad' cy='5%' r='45%' gradientTransform='translate(-0.25 0) scale(1.5 1)'><stop offset='10%' stop-color='white' stop-opacity='100'/><stop offset='100%' stop-color='white' stop-opacity='0'/></radialGradient><radialGradient id='grad' cy='92%' r='60%' gradientTransform='translate(-0.2 0) scale(1.4 1)'><stop offset='0%' stop-color='{$highlight}'/><stop offset='100%' stop-color='{$main}'/></radialGradient><filter id='shadowBlur'><feGaussianBlur in='SourceGraphic' stdDeviation='{$shBlur}' /></filter></defs><circle filter='url(#shadowBlur)' fill='black' opacity='{$opacity}' cx='50%' cy='49%' r='45%'/><circle fill='url(#grad)' cx='50%' cy='45%' r='45%'/><clipPath id='hclip'><circle cx='50%' cy='45%' r='45%' /></clipPath><clipPath id='hcclip'><circle cx='50%' cy='40%' r='37%' /></clipPath><g filter='url(#hblur)' clip-path='url(#hclip)'><circle fill='url(#highgrad)' cx='50%' cy='50%' r='48%' clip-path='url(#hcclip)' /></g>";
	}
}
