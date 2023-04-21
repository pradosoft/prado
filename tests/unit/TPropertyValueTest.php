<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 */
class TPropertyValueTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}


	protected function tearDown(): void
	{
	}


	public function testEnsureBoolean()
	{
		self::assertEquals(true, TPropertyValue::ensureBoolean(true));
		self::assertEquals(true, TPropertyValue::ensureBoolean('true'));
		self::assertEquals(true, TPropertyValue::ensureBoolean('TRue'));
		self::assertEquals(true, TPropertyValue::ensureBoolean(1));
		self::assertEquals(true, TPropertyValue::ensureBoolean(0.001));
		self::assertEquals(true, TPropertyValue::ensureBoolean(100));
		self::assertEquals(true, TPropertyValue::ensureBoolean('1'));
		self::assertEquals(true, TPropertyValue::ensureBoolean('0.001'));
		self::assertEquals(true, TPropertyValue::ensureBoolean('100'));
		self::assertEquals(true, TPropertyValue::ensureBoolean(['value']));
		self::assertEquals(true, TPropertyValue::ensureBoolean(new stdClass()));
		
		self::assertEquals(false, TPropertyValue::ensureBoolean(false));
		self::assertEquals(false, TPropertyValue::ensureBoolean('false'));
		self::assertEquals(false, TPropertyValue::ensureBoolean('FAlse'));
		self::assertEquals(false, TPropertyValue::ensureBoolean(0));
		self::assertEquals(false, TPropertyValue::ensureBoolean('0'));
		self::assertEquals(false, TPropertyValue::ensureBoolean('value'));
		self::assertEquals(false, TPropertyValue::ensureBoolean(null));
	}
	
	
	public function testEnsureString()
	{
		$value = 'myLiteral';
		$literal = new TJavaScriptLiteral($value);
		
		self::assertEquals($value, TPropertyValue::ensureString($literal));
		self::assertEquals($value, TPropertyValue::ensureString($value));
		
		self::assertEquals('true', TPropertyValue::ensureString(true));
		self::assertEquals('false', TPropertyValue::ensureString(false));
		
		self::assertEquals('0', TPropertyValue::ensureString(0));
		self::assertEquals('', TPropertyValue::ensureString(null));
		self::assertEquals('4.8', TPropertyValue::ensureString(4.8));
	}
	
	public function testEnsureInteger()
	{
		self::assertEquals(0, TPropertyValue::ensureInteger(null));
		self::assertEquals(0, TPropertyValue::ensureInteger(''));
		self::assertEquals(0, TPropertyValue::ensureInteger([]));
		self::assertEquals(1, TPropertyValue::ensureInteger(['value']));
		self::assertEquals(1, TPropertyValue::ensureInteger(['value', 'v2']));
		self::assertEquals(0, TPropertyValue::ensureInteger(0));
		self::assertEquals(0, TPropertyValue::ensureInteger(0.0001));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.8));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.0001));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.5001));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.99999));

		self::assertEquals(0, TPropertyValue::ensureInteger('0'));
		self::assertEquals(0, TPropertyValue::ensureInteger('0.0001'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.8'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.0001'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.5001'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.99999'));
	}
	
	public function testEnsureFloat()
	{
		self::assertEquals(0.0, TPropertyValue::ensureFloat(null));
		self::assertEquals(0.0, TPropertyValue::ensureFloat(''));
		self::assertEquals(0.0, TPropertyValue::ensureFloat([]));
		self::assertEquals(1.0, TPropertyValue::ensureFloat(['value']));
		self::assertEquals(1.0, TPropertyValue::ensureFloat(['value', 'v2']));
		self::assertEquals(0.0, TPropertyValue::ensureFloat(0));
		self::assertEquals(0.0001, TPropertyValue::ensureFloat(0.0001));
		self::assertEquals(1.8, TPropertyValue::ensureFloat(1.8));
		self::assertEquals(1.99999, TPropertyValue::ensureFloat(1.99999));
		
		self::assertEquals(0, TPropertyValue::ensureFloat('0'));
		self::assertEquals(0.0001, TPropertyValue::ensureFloat('0.0001'));
		self::assertEquals(1.8, TPropertyValue::ensureFloat('1.8'));
		self::assertEquals(1.99999, TPropertyValue::ensureFloat('1.99999'));
	}
	
	public function testEnsureArray()
	{
		self::assertEquals([], TPropertyValue::ensureArray(null));
		self::assertEquals([], TPropertyValue::ensureArray(''));
		self::assertEquals([], TPropertyValue::ensureArray([]));
		self::assertEquals([0 => 0], TPropertyValue::ensureArray(0));
		self::assertEquals([0 => 1], TPropertyValue::ensureArray(1));
		self::assertEquals(['value'], TPropertyValue::ensureArray('value'));
		self::assertEquals(['value'], TPropertyValue::ensureArray(' value '));
		self::assertEquals([], TPropertyValue::ensureArray('()'));
		//self::assertEquals(['test', 'value'], TPropertyValue::ensureArray('(test, value)'));
		self::assertEquals(['my', 'prop'], TPropertyValue::ensureArray('("my", "prop")'));
		self::assertEquals(['my', 'prop'], TPropertyValue::ensureArray('("my", "prop")'));
		
		//self::assertEquals([0 => [12, 'bcd', 'wxy', '']], TPropertyValue::ensureArray('( (12, \'bcd\', \'wxy\', "") )'));
		//self::assertEquals([0 => 11, 1 => 'abc', 2 => 'xyz', 3 => '\'"dqt', 4 => '\'"sqt', 5 => [12, 'bcd', 'wxy', '']], 
		//		TPropertyValue::ensureArray('( 11, "abc", \'xyz\', "\'\\"dqt", \'\\\'"sqt\', (12, \'bcd\', \'wxy\', ""))'));
	}
	
	public function testEnsureObject()
	{
		self::assertEquals(new stdClass(), TPropertyValue::ensureObject(null));
		$obj = new stdClass();
		$obj->scalar = '';
		self::assertEquals($obj, TPropertyValue::ensureObject(''));
		self::assertEquals(new stdClass(), TPropertyValue::ensureObject([]));
		$obj->scalar = 0;
		self::assertEquals($obj, TPropertyValue::ensureObject(0));
		$obj->scalar = 1;
		self::assertEquals($obj, TPropertyValue::ensureObject(1));
		$obj->scalar = 'value';
		self::assertEquals($obj, TPropertyValue::ensureObject('value'));
		$obj = new stdClass();
		$obj->key = 'Prop';
		self::assertEquals($obj, TPropertyValue::ensureObject(['key' => 'Prop']));
		self::assertEquals($obj, TPropertyValue::ensureObject($obj));
	}
	
	public function testEnsureEnum()
	{
		//Multiple ways to operate.
		// $enums = ['value', 'value2', 'value3']
		self::assertEquals('value', TPropertyValue::ensureEnum('value', ['value', 'value2']));
		self::assertEquals('value2', TPropertyValue::ensureEnum('value2', ['value', 'value2']));
		try {
			self::assertEquals('value3', TPropertyValue::ensureEnum('value3', ['value', 'value2']));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch(TInvalidDataValueException $e) {
		}
		try {
			self::assertEquals('Value', TPropertyValue::ensureEnum('Value', ['value', 'value2']));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch(TInvalidDataValueException $e) {
		}
		
		// $enums = Class, look at class constant
		self::assertEquals('Off', TPropertyValue::ensureEnum('Off', \Prado\TApplicationMode::class));
		self::assertEquals('Debug', TPropertyValue::ensureEnum('Debug', \Prado\TApplicationMode::class));
		self::assertEquals('Normal', TPropertyValue::ensureEnum('Normal', \Prado\TApplicationMode::class));
		self::assertEquals('Performance', TPropertyValue::ensureEnum('Performance', \Prado\TApplicationMode::class));
		try {
			self::assertEquals('value', TPropertyValue::ensureEnum('value', \Prado\TApplicationMode::class));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch(TInvalidDataValueException $e) {
		}
		
		// more than one $enum param no function.
		self::assertEquals('Off', TPropertyValue::ensureEnum('Off', 'Off', 'Debug', 'Normal', 'Performance'));
		self::assertEquals('Debug', TPropertyValue::ensureEnum('Debug', 'Off', 'Debug', 'Normal', 'Performance'));
		self::assertEquals('Normal', TPropertyValue::ensureEnum('Normal', 'Off', 'Debug', 'Normal', 'Performance'));
		self::assertEquals('Performance', TPropertyValue::ensureEnum('Performance', 'Off', 'Debug', 'Normal', 'Performance'));
		try {
			self::assertEquals('value', TPropertyValue::ensureEnum('value', 'Off', 'Debug', 'Normal', 'Performance'));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch(TInvalidDataValueException $e) {
		}
	}
	
	public function testEnsureNullIfEmpty()
	{
		self::assertNull(TPropertyValue::ensureNullIfEmpty(''));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(""));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(null));
		self::assertNull(TPropertyValue::ensureNullIfEmpty([]));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(false));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(null));
		self::assertNull(TPropertyValue::ensureNullIfEmpty('0'));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(0));
			
		self::assertEquals(true, TPropertyValue::ensureNullIfEmpty(true));
		self::assertEquals('value', TPropertyValue::ensureNullIfEmpty('value'));
		self::assertEquals('11', TPropertyValue::ensureNullIfEmpty('11'));
		self::assertEquals(11, TPropertyValue::ensureNullIfEmpty(11));
		self::assertEquals([11], TPropertyValue::ensureNullIfEmpty([11]));
		self::assertEquals(new stdClass(), TPropertyValue::ensureNullIfEmpty(new stdClass()));
	}
	
	
	public function testEnsureHexColor()
	{
		// Integer Color in format 0x00RRGGBB
		self::assertEquals('#000000', TPropertyValue::ensureHexColor(0));
		self::assertEquals('#000101', TPropertyValue::ensureHexColor(257));
		self::assertEquals('#010101', TPropertyValue::ensureHexColor(257 + 65536));
		self::assertEquals('#FFFFFF', TPropertyValue::ensureHexColor(-1));
		
		// red green and blue as arguments.
		self::assertEquals('#808182', TPropertyValue::ensureHexColor(128, 129, 130));
		self::assertEquals('#008384', TPropertyValue::ensureHexColor(-1, 131, 132));
		self::assertEquals('#FF0001', TPropertyValue::ensureHexColor(256, 0, 1));
		self::assertEquals('#050000', TPropertyValue::ensureHexColor(5, -1, 0));
		self::assertEquals('#00FF0A', TPropertyValue::ensureHexColor(0, 256, 10));
		
		
		self::assertEquals('#808182', TPropertyValue::ensureHexColor([128, 129, 130]));
		self::assertEquals('#838485', TPropertyValue::ensureHexColor(['red' => 131, 'green' => 132, 'blue' => 133]));
		self::assertEquals('#898A8B', TPropertyValue::ensureHexColor([134, 135, 136, 'red' => 137, 'green' => 138, 'blue' => 139]));
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor([]));
			self::fail('failed to throw TInvalidDataValueException for null value');
		} catch(TInvalidDataValueException $e) {
		}
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor(null));
			self::fail('failed to throw TInvalidDataValueException for null value');
		} catch(TInvalidDataValueException $e) {
		}
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor(''));
			self::fail('failed to throw TInvalidDataValueException for blank value');
		} catch(TInvalidDataValueException $e) {
		}
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor('notAColor'));
			self::fail('failed to throw TInvalidDataValueException for not a color');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#0'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#0"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#00'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#00"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#0000'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#0000"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#00000'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#00000"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#0000000'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#0000000"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // bad data after '#', length 4
			self::assertEquals('value', TPropertyValue::ensureHexColor('#the'));
			self::fail('failed to throw TInvalidDataValueException for Improper data "#the"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // bad data after '#', length 7
			self::assertEquals('value', TPropertyValue::ensureHexColor('#avalue'));
			self::fail('failed to throw TInvalidDataValueException for Improper data "#avalue"');
		} catch(TInvalidDataValueException $e) {
		}
		
		// Valid cases
		self::assertEquals('#012012', TPropertyValue::ensureHexColor('#012012'));
		self::assertEquals('#345345', TPropertyValue::ensureHexColor('#345345'));
		self::assertEquals('#678678', TPropertyValue::ensureHexColor('#678678'));
		self::assertEquals('#9AB9AB', TPropertyValue::ensureHexColor('#9AB9AB'));
		self::assertEquals('#CDECDE', TPropertyValue::ensureHexColor('#CDECDE'));
		self::assertEquals('#FABFAB', TPropertyValue::ensureHexColor('#FabFab'));
		self::assertEquals('#CDECDE', TPropertyValue::ensureHexColor('#cdecde'));
		self::assertEquals('#F01F01', TPropertyValue::ensureHexColor('#f01f01'));
		
		self::assertEquals('#225588', TPropertyValue::ensureHexColor('#258'));
		self::assertEquals('#BBEEDD', TPropertyValue::ensureHexColor('#BED'));
		self::assertEquals('#CCDDEE', TPropertyValue::ensureHexColor('#cde'));
		
		// Web Colors 
		self::assertEquals('#FFFFFF', TPropertyValue::ensureHexColor('White'));
		self::assertEquals('#C0C0C0', TPropertyValue::ensureHexColor('silver')); //lower case
		self::assertEquals('#808080', TPropertyValue::ensureHexColor('GRAY'));	//uppers case
		self::assertEquals('#000000', TPropertyValue::ensureHexColor('Black'));
		self::assertEquals('#FF0000', TPropertyValue::ensureHexColor('Red'));
		self::assertEquals('#800000', TPropertyValue::ensureHexColor('Maroon'));
		self::assertEquals('#FFFF00', TPropertyValue::ensureHexColor('Yellow'));
		self::assertEquals('#808000', TPropertyValue::ensureHexColor('Olive'));
		self::assertEquals('#00FF00', TPropertyValue::ensureHexColor('Lime'));
		self::assertEquals('#008000', TPropertyValue::ensureHexColor('Green'));
		self::assertEquals('#00FFFF', TPropertyValue::ensureHexColor('Aqua'));
		self::assertEquals('#008080', TPropertyValue::ensureHexColor('Teal'));
		self::assertEquals('#0000FF', TPropertyValue::ensureHexColor('Blue'));
		self::assertEquals('#000080', TPropertyValue::ensureHexColor('Navy'));
		self::assertEquals('#FF00FF', TPropertyValue::ensureHexColor('Fuchsia'));
		self::assertEquals('#800080', TPropertyValue::ensureHexColor('Purple'));
		
		// Extended Web Colors
		// Pink
		self::assertEquals('#C71585', TPropertyValue::ensureHexColor('MediumVioletRed'));
		self::assertEquals('#FF1493', TPropertyValue::ensureHexColor('DeepPink'));
		self::assertEquals('#DB7093', TPropertyValue::ensureHexColor('PaleVioletRed'));
		self::assertEquals('#FF69B4', TPropertyValue::ensureHexColor('HotPink'));
		self::assertEquals('#FFB6C1', TPropertyValue::ensureHexColor('LightPink'));
		self::assertEquals('#FFC0CB', TPropertyValue::ensureHexColor('Pink'));
		
		//Red
		self::assertEquals('#8B0000', TPropertyValue::ensureHexColor('DarkRed'));
		self::assertEquals('#FF0000', TPropertyValue::ensureHexColor('Red'));
		self::assertEquals('#B22222', TPropertyValue::ensureHexColor('Firebrick'));
		self::assertEquals('#DC143C', TPropertyValue::ensureHexColor('Crimson'));
		self::assertEquals('#CD5C5C', TPropertyValue::ensureHexColor('IndianRed'));
		self::assertEquals('#F08080', TPropertyValue::ensureHexColor('LightCoral'));
		self::assertEquals('#FA8072', TPropertyValue::ensureHexColor('Salmon'));
		self::assertEquals('#E9967A', TPropertyValue::ensureHexColor('DarkSalmon'));
		self::assertEquals('#FFA07A', TPropertyValue::ensureHexColor('LightSalmon'));
		
		//Orange
		self::assertEquals('#FF4500', TPropertyValue::ensureHexColor('OrangeRed'));
		self::assertEquals('#FF6347', TPropertyValue::ensureHexColor('Tomato'));
		self::assertEquals('#FF8C00', TPropertyValue::ensureHexColor('DarkOrange'));
		self::assertEquals('#FF7F50', TPropertyValue::ensureHexColor('Coral'));
		self::assertEquals('#FFA500', TPropertyValue::ensureHexColor('Orange'));
		
		//Yellow
		self::assertEquals('#BDB76B', TPropertyValue::ensureHexColor('DarkKhaki'));
		self::assertEquals('#FFD700', TPropertyValue::ensureHexColor('Gold'));
		self::assertEquals('#F0E68C', TPropertyValue::ensureHexColor('Khaki'));
		self::assertEquals('#FFDAB9', TPropertyValue::ensureHexColor('PeachPuff'));
		self::assertEquals('#FFFF00', TPropertyValue::ensureHexColor('Yellow'));
		self::assertEquals('#EEE8AA', TPropertyValue::ensureHexColor('PaleGoldenrod'));
		self::assertEquals('#FFE4B5', TPropertyValue::ensureHexColor('Moccasin'));
		self::assertEquals('#FFEFD5', TPropertyValue::ensureHexColor('PapayaWhip'));
		self::assertEquals('#FAFAD2', TPropertyValue::ensureHexColor('LightGoldenrodYellow'));
		self::assertEquals('#FFFACD', TPropertyValue::ensureHexColor('LemonChiffon'));
		self::assertEquals('#FFFFE0', TPropertyValue::ensureHexColor('LightYellow'));
		
		//Brown
		self::assertEquals('#800000', TPropertyValue::ensureHexColor('Maroon'));
		self::assertEquals('#A52A2A', TPropertyValue::ensureHexColor('Brown'));
		self::assertEquals('#8B4513', TPropertyValue::ensureHexColor('SaddleBrown'));
		self::assertEquals('#A0522D', TPropertyValue::ensureHexColor('Sienna'));
		self::assertEquals('#D2691E', TPropertyValue::ensureHexColor('Chocolate'));
		self::assertEquals('#B8860B', TPropertyValue::ensureHexColor('DarkGoldenrod'));
		self::assertEquals('#CD853F', TPropertyValue::ensureHexColor('Peru'));
		self::assertEquals('#BC8F8F', TPropertyValue::ensureHexColor('RosyBrown'));
		self::assertEquals('#DAA520', TPropertyValue::ensureHexColor('Goldenrod'));
		self::assertEquals('#F4A460', TPropertyValue::ensureHexColor('SandyBrown'));
		self::assertEquals('#D2B48C', TPropertyValue::ensureHexColor('Tan'));
		self::assertEquals('#DEB887', TPropertyValue::ensureHexColor('Burlywood'));
		self::assertEquals('#F5DEB3', TPropertyValue::ensureHexColor('Wheat'));
		self::assertEquals('#FFDEAD', TPropertyValue::ensureHexColor('NavajoWhite'));
		self::assertEquals('#FFE4C4', TPropertyValue::ensureHexColor('Bisque'));
		self::assertEquals('#FFEBCD', TPropertyValue::ensureHexColor('BlanchedAlmond'));
		self::assertEquals('#FFF8DC', TPropertyValue::ensureHexColor('Cornsilk'));
		
		//purple, violet, magenta
		self::assertEquals('#4B0082', TPropertyValue::ensureHexColor('Indigo'));
		self::assertEquals('#800080', TPropertyValue::ensureHexColor('Purple'));
		self::assertEquals('#8B008B', TPropertyValue::ensureHexColor('DarkMagenta'));
		self::assertEquals('#9400D3', TPropertyValue::ensureHexColor('DarkViolet'));
		self::assertEquals('#483D8B', TPropertyValue::ensureHexColor('DarkSlateBlue'));
		self::assertEquals('#8A2BE2', TPropertyValue::ensureHexColor('BlueViolet'));
		self::assertEquals('#9932CC', TPropertyValue::ensureHexColor('DarkOrchid'));
		self::assertEquals('#FF00FF', TPropertyValue::ensureHexColor('Fuchsia'));
		self::assertEquals('#FF00FF', TPropertyValue::ensureHexColor('Magenta'));
		self::assertEquals('#6A5ACD', TPropertyValue::ensureHexColor('SlateBlue'));
		self::assertEquals('#7B68EE', TPropertyValue::ensureHexColor('MediumSlateBlue'));
		self::assertEquals('#BA55D3', TPropertyValue::ensureHexColor('MediumOrchid'));
		self::assertEquals('#9370DB', TPropertyValue::ensureHexColor('MediumPurple'));
		self::assertEquals('#DA70D6', TPropertyValue::ensureHexColor('Orchid'));
		self::assertEquals('#EE82EE', TPropertyValue::ensureHexColor('Violet'));
		self::assertEquals('#DDA0DD', TPropertyValue::ensureHexColor('Plum'));
		self::assertEquals('#D8BFD8', TPropertyValue::ensureHexColor('Thistle'));
		self::assertEquals('#E6E6FA', TPropertyValue::ensureHexColor('Lavender'));
		
		//blue
		self::assertEquals('#191970', TPropertyValue::ensureHexColor('MidnightBlue'));
		self::assertEquals('#000080', TPropertyValue::ensureHexColor('Navy'));
		self::assertEquals('#00008B', TPropertyValue::ensureHexColor('DarkBlue'));
		self::assertEquals('#0000CD', TPropertyValue::ensureHexColor('MediumBlue'));
		self::assertEquals('#0000FF', TPropertyValue::ensureHexColor('Blue'));
		self::assertEquals('#4169E1', TPropertyValue::ensureHexColor('RoyalBlue'));
		self::assertEquals('#4682B4', TPropertyValue::ensureHexColor('SteelBlue'));
		self::assertEquals('#1E90FF', TPropertyValue::ensureHexColor('DodgerBlue'));
		self::assertEquals('#00BFFF', TPropertyValue::ensureHexColor('DeepSkyBlue'));
		self::assertEquals('#6495ED', TPropertyValue::ensureHexColor('CornflowerBlue'));
		self::assertEquals('#87CEEB', TPropertyValue::ensureHexColor('SkyBlue'));
		self::assertEquals('#87CEFA', TPropertyValue::ensureHexColor('LightSkyBlue'));
		self::assertEquals('#B0C4DE', TPropertyValue::ensureHexColor('LightSteelBlue'));
		self::assertEquals('#ADD8E6', TPropertyValue::ensureHexColor('LightBlue'));
		self::assertEquals('#B0E0E6', TPropertyValue::ensureHexColor('PowderBlue'));
		
		//cyan
		self::assertEquals('#008080', TPropertyValue::ensureHexColor('Teal'));
		self::assertEquals('#008B8B', TPropertyValue::ensureHexColor('DarkCyan'));
		self::assertEquals('#20B2AA', TPropertyValue::ensureHexColor('LightSeaGreen'));
		self::assertEquals('#5F9EA0', TPropertyValue::ensureHexColor('CadetBlue'));
		self::assertEquals('#00CED1', TPropertyValue::ensureHexColor('DarkTurquoise'));
		self::assertEquals('#48D1CC', TPropertyValue::ensureHexColor('MediumTurquoise'));
		self::assertEquals('#40E0D0', TPropertyValue::ensureHexColor('Turquoise'));
		self::assertEquals('#00FFFF', TPropertyValue::ensureHexColor('Aqua'));
		self::assertEquals('#00FFFF', TPropertyValue::ensureHexColor('Cyan'));
		self::assertEquals('#7FFFD4', TPropertyValue::ensureHexColor('Aquamarine'));
		self::assertEquals('#AFEEEE', TPropertyValue::ensureHexColor('PaleTurquoise'));
		self::assertEquals('#E0FFFF', TPropertyValue::ensureHexColor('LightCyan'));
		
		//green
		self::assertEquals('#006400', TPropertyValue::ensureHexColor('DarkGreen'));
		self::assertEquals('#008000', TPropertyValue::ensureHexColor('Green'));
		self::assertEquals('#556B2F', TPropertyValue::ensureHexColor('DarkOliveGreen'));
		self::assertEquals('#228B22', TPropertyValue::ensureHexColor('ForestGreen'));
		self::assertEquals('#2E8B57', TPropertyValue::ensureHexColor('SeaGreen'));
		self::assertEquals('#808000', TPropertyValue::ensureHexColor('Olive'));
		self::assertEquals('#6B8E23', TPropertyValue::ensureHexColor('OliveDrab'));
		self::assertEquals('#3CB371', TPropertyValue::ensureHexColor('MediumSeaGreen'));
		self::assertEquals('#32CD32', TPropertyValue::ensureHexColor('LimeGreen'));
		self::assertEquals('#00FF00', TPropertyValue::ensureHexColor('Lime'));
		self::assertEquals('#00FF7F', TPropertyValue::ensureHexColor('SpringGreen'));
		self::assertEquals('#00FA9A', TPropertyValue::ensureHexColor('MediumSpringGreen'));
		self::assertEquals('#8FBC8F', TPropertyValue::ensureHexColor('DarkSeaGreen'));
		self::assertEquals('#66CDAA', TPropertyValue::ensureHexColor('MediumAquamarine'));
		self::assertEquals('#9ACD32', TPropertyValue::ensureHexColor('YellowGreen'));
		self::assertEquals('#7CFC00', TPropertyValue::ensureHexColor('LawnGreen'));
		self::assertEquals('#7FFF00', TPropertyValue::ensureHexColor('Chartreuse'));
		self::assertEquals('#90EE90', TPropertyValue::ensureHexColor('LightGreen'));
		self::assertEquals('#ADFF2F', TPropertyValue::ensureHexColor('GreenYellow'));
		self::assertEquals('#98FB98', TPropertyValue::ensureHexColor('PaleGreen'));
		
		//white
		self::assertEquals('#FFE4E1', TPropertyValue::ensureHexColor('MistyRose'));
		self::assertEquals('#FAEBD7', TPropertyValue::ensureHexColor('AntiqueWhite'));
		self::assertEquals('#FAF0E6', TPropertyValue::ensureHexColor('Linen'));
		self::assertEquals('#F5F5DC', TPropertyValue::ensureHexColor('Beige'));
		self::assertEquals('#F5F5F5', TPropertyValue::ensureHexColor('WhiteSmoke'));
		self::assertEquals('#FFF0F5', TPropertyValue::ensureHexColor('LavenderBlush'));
		self::assertEquals('#FDF5E6', TPropertyValue::ensureHexColor('OldLace'));
		self::assertEquals('#F0F8FF', TPropertyValue::ensureHexColor('AliceBlue'));
		self::assertEquals('#FFF5EE', TPropertyValue::ensureHexColor('Seashell'));
		self::assertEquals('#F8F8FF', TPropertyValue::ensureHexColor('GhostWhite'));
		self::assertEquals('#F0FFF0', TPropertyValue::ensureHexColor('Honeydew'));
		self::assertEquals('#FFFAF0', TPropertyValue::ensureHexColor('FloralWhite'));
		self::assertEquals('#F0FFFF', TPropertyValue::ensureHexColor('Azure'));
		self::assertEquals('#F5FFFA', TPropertyValue::ensureHexColor('MintCream'));
		self::assertEquals('#FFFAFA', TPropertyValue::ensureHexColor('Snow'));
		self::assertEquals('#FFFFF0', TPropertyValue::ensureHexColor('Ivory'));
		self::assertEquals('#FFFFFF', TPropertyValue::ensureHexColor('White'));
		
		//gray
		self::assertEquals('#000000', TPropertyValue::ensureHexColor('Black'));
		self::assertEquals('#2F4F4F', TPropertyValue::ensureHexColor('DarkSlateGray'));
		self::assertEquals('#696969', TPropertyValue::ensureHexColor('DimGray'));
		self::assertEquals('#708090', TPropertyValue::ensureHexColor('SlateGray'));
		self::assertEquals('#808080', TPropertyValue::ensureHexColor('Gray'));
		self::assertEquals('#778899', TPropertyValue::ensureHexColor('LightSlateGray'));
		self::assertEquals('#A9A9A9', TPropertyValue::ensureHexColor('DarkGray'));
		self::assertEquals('#C0C0C0', TPropertyValue::ensureHexColor('Silver'));
		self::assertEquals('#D3D3D3', TPropertyValue::ensureHexColor('LightGray'));
		self::assertEquals('#DCDCDC', TPropertyValue::ensureHexColor('Gainsboro'));
	}
}
