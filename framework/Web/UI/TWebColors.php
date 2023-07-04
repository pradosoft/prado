<?php
/**
 * TWebColors class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * TWebColors class.
 * TWebColors defines the Extended Web Colors and their respective hex web color.
 * This is used by {@see \Prado\TPropertyValue::ensureHexColor} to convert the web color
 * names to their hex values.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 * @see https://en.wikipedia.org/wiki/Web_colors  Web Colors
 */
class TWebColors extends \Prado\TEnumerable
{
	// ** Basic Web Colors **
	public const White = '#FFFFFF';
	public const Silver = '#C0C0C0';
	public const Gray = '#808080';
	public const Black = '#000000';

	public const Red = '#FF0000';
	public const Maroon = '#800000';
	public const Orange = '#FFA500';
	public const Yellow = '#FFFF00';
	public const Olive = '#808000';
	public const Lime = '#00FF00';
	public const Green = '#008000';

	public const Aqua = '#00FFFF';
	public const Cyan = '#00FFFF';
	public const Teal = '#008080';
	public const Blue = '#0000FF';
	public const Navy = '#000080';
	public const Fuchsia = '#FF00FF';
	public const Magenta = '#FF00FF';
	public const Purple = '#800080';

	// ** Extended Web Colors **
	//	Gray colors
	public const DarkSlateGray = '#2F4F4F';
	public const DimGray = '#696969';
	public const SlateGray = '#708090';
	public const LightSlateGray = '#778899';
	public const DarkGray = '#A9A9A9';
	public const LightGray = '#D3D3D3';
	public const Gainsboro = '#DCDCDC';

	//	White colors
	public const MistyRose = '#FFE4E1';
	public const AntiqueWhite = '#FAEBD7';
	public const Linen = '#FAF0E6';
	public const Beige = '#F5F5DC';
	public const WhiteSmoke = '#F5F5F5';
	public const LavenderBlush = '#FFF0F5';
	public const OldLace = '#FDF5E6';
	public const AliceBlue = '#F0F8FF';
	public const Seashell = '#FFF5EE';
	public const GhostWhite = '#F8F8FF';
	public const Honeydew = '#F0FFF0';
	public const FloralWhite = '#FFFAF0';
	public const Azure = '#F0FFFF';
	public const MintCream = '#F5FFFA';
	public const Snow = '#FFFAFA';
	public const Ivory = '#FFFFF0';

	//	Pink colors
	public const MediumVioletRed = '#C71585';
	public const DeepPink = '#FF1493';
	public const PaleVioletRed = '#DB7093';
	public const HotPink = '#FF69B4';
	public const LightPink = '#FFB6C1';
	public const Pink = '#FFC0CB';

	//	Red colors
	public const DarkRed = '#8B0000';
	public const Firebrick = '#B22222';
	public const Crimson = '#DC143C';
	public const IndianRed = '#CD5C5C';
	public const LightCoral = '#F08080';
	public const Salmon = '#FA8072';
	public const DarkSalmon = '#E9967A';
	public const LightSalmon = '#FFA07A';

	//	Orange colors
	public const OrangeRed = '#FF4500';
	public const Tomato = '#FF6347';
	public const DarkOrange = '#FF8C00';
	public const Coral = '#FF7F50';

	//	Yellow colors
	public const DarkKhaki = '#BDB76B';
	public const Gold = '#FFD700';
	public const Khaki = '#F0E68C';
	public const PeachPuff = '#FFDAB9';
	public const PaleGoldenrod = '#EEE8AA';
	public const Moccasin = '#FFE4B5';
	public const PapayaWhip = '#FFEFD5';
	public const LightGoldenrodYellow = '#FAFAD2';
	public const LemonChiffon = '#FFFACD';
	public const LightYellow = '#FFFFE0';

	//	Brown colors
	public const Brown = '#A52A2A';
	public const SaddleBrown = '#8B4513';
	public const Sienna = '#A0522D';
	public const Chocolate = '#D2691E';
	public const DarkGoldenrod = '#B8860B';
	public const Peru = '#CD853F';
	public const RosyBrown = '#BC8F8F';
	public const Goldenrod = '#DAA520';
	public const SandyBrown = '#F4A460';
	public const Tan = '#D2B48C';
	public const Burlywood = '#DEB887';
	public const Wheat = '#F5DEB3';
	public const NavajoWhite = '#FFDEAD';
	public const Bisque = '#FFE4C4';
	public const BlanchedAlmond = '#FFEBCD';
	public const Cornsilk = '#FFF8DC';

	//	Green colors
	public const DarkGreen = '#006400';
	public const DarkOliveGreen = '#556B2F';
	public const ForestGreen = '#228B22';
	public const SeaGreen = '#2E8B57';
	public const OliveDrab = '#6B8E23';
	public const MediumSeaGreen = '#3CB371';
	public const LimeGreen = '#32CD32';

	public const SpringGreen = '#00FF7F';
	public const MediumSpringGreen = '#00FA9A';
	public const DarkSeaGreen = '#8FBC8F';
	public const MediumAquamarine = '#66CDAA';
	public const YellowGreen = '#9ACD32';
	public const LawnGreen = '#7CFC00';
	public const Chartreuse = '#7FFF00';
	public const LightGreen = '#90EE90';
	public const GreenYellow = '#ADFF2F';
	public const PaleGreen = '#98FB98';

	//	Cyan colors
	public const DarkCyan = '#008B8B';
	public const LightSeaGreen = '#20B2AA';
	public const CadetBlue = '#5F9EA0';
	public const DarkTurquoise = '#00CED1';
	public const MediumTurquoise = '#48D1CC';
	public const Turquoise = '#40E0D0';
	public const Aquamarine = '#7FFFD4';
	public const PaleTurquoise = '#AFEEEE';
	public const LightCyan = '#E0FFFF';

	//	Blue colors
	public const MidnightBlue = '#191970';
	public const DarkBlue = '#00008B';
	public const MediumBlue = '#0000CD';
	public const RoyalBlue = '#4169E1';
	public const SteelBlue = '#4682B4';
	public const DodgerBlue = '#1E90FF';
	public const DeepSkyBlue = '#00BFFF';
	public const CornflowerBlue = '#6495ED';
	public const SkyBlue = '#87CEEB';
	public const LightSkyBlue = '#87CEFA';
	public const LightSteelBlue = '#B0C4DE';
	public const LightBlue = '#ADD8E6';
	public const PowderBlue = '#B0E0E6';

	//	Purple, violet, magenta Colors
	public const Indigo = '#4B0082';
	public const DarkMagenta = '#8B008B';
	public const DarkViolet = '#9400D3';
	public const DarkSlateBlue = '#483D8B';
	public const BlueViolet = '#8A2BE2';
	public const DarkOrchid = '#9932CC';
	public const SlateBlue = '#6A5ACD';
	public const MediumSlateBlue = '#7B68EE';
	public const MediumOrchid = '#BA55D3';
	public const MediumPurple = '#9370DB';
	public const Orchid = '#DA70D6';
	public const Violet = '#EE82EE';
	public const Plum = '#DDA0DD';
	public const Thistle = '#D8BFD8';
	public const Lavender = '#E6E6FA';

	//	Other Colors
	public const RebeccaPurple = '#663399';
}
