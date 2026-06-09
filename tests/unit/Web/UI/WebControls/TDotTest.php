<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TApplicationMode;
use Prado\Web\UI\WebControls\TDot;

class TDotTest extends PHPUnit\Framework\TestCase
{
	use TWebControlRenderTrait;

	public static $app = null;
	public static $assetDir = null;
	
	protected $obj;

	protected function setUp(): void
	{
		// Fake environment variables needed to determine path
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/demos/personal/index.php?page=Links';
		$_SERVER['SCRIPT_NAME'] = '/demos/personal/index.php';
		$_SERVER['PHP_SELF'] = '/demos/personal/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Links';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
		$_SERVER['PATH_INFO'] = __FILE__;
		$_SERVER['HTTP_REFERER'] = 'https://github.com/pradosoft/prado';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';
		$_SERVER['REMOTE_HOST'] = 'localhost';
		
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/../../app');
		}
		
		if (self::$assetDir === null) {
			self::$assetDir = __DIR__ . '/../../assets';
		}
		// Make asset directory if not exists
		if (!file_exists(self::$assetDir)) {
			if (is_writable(dirname(self::$assetDir))) {
				mkdir(self::$assetDir) ;
			} else {
				throw new Exception('Directory ' . dirname(self::$assetDir) . ' is not writable');
			}
		} elseif (!is_dir(self::$assetDir)) {
			throw new Exception(self::$assetDir . ' exists and is not a directory');
		}
		// Define an alias to asset directory
		prado::setPathofAlias('AssetAlias', self::$assetDir);
		
		$this->obj = new TDot();
	}

	private function removeDirectory($dir)
	{
		// Let's be sure $dir is a directory to avoid any error. Clear the cache !
		clearstatcache();
		if (is_dir($dir)) {
			foreach (scandir($dir) as $content) {
				if ($content === '.' || $content === '..') {
					continue;
				} // skip . and ..
				$content = $dir . '/' . $content;
				if (is_dir($content)) {
					$this->removeDirectory($content);
				} // Recursively remove directories
				else {
					unlink($content);
				} // Remove file
			}
			// Now, directory should be empty, remove it
			rmdir($dir);
		}
	}
	
	protected function tearDown(): void
	{//  Clean up the asset directory.
		$this->removeDirectory(self::$assetDir);
		$this->obj = null;
	}

	protected function colorDistance($v1, $v2)
	{
		$r1 = hexdec(substr($v1, 1, 2));
		$g1 = hexdec(substr($v1, 3, 2));
		$b1 = hexdec(substr($v1, 5, 2));
		
		$r2 = hexdec(substr($v2, 1, 2));
		$g2 = hexdec(substr($v2, 3, 2));
		$b2 = hexdec(substr($v2, 5, 2));
		return sqrt(pow($r1 - $r2, 2) + pow($g1 - $g2, 2) + pow($b1 - $b2, 2));
	}
	
	public const COLOR_DIST_MAX = 26;
	
	public function testConstruct()
	{
		self::assertInstanceOf('\\Prado\\Web\\UI\\WebControls\\TDot', $this->obj);
		
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
	}
	
	public function testColor()
	{
		$value = 'Green';
		$this->obj->setColor($value);
		self::assertEquals($value, $this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#007000', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#00B000', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
	}

	public function testColorTWebColor()
	{
		// A lower-case web color name normalizes to the TWebColor constant name.
		$this->obj->setColor('green');
		self::assertEquals('Green', $this->obj->getColor());

		// A hex value matching a TWebColor constant stores the constant name.
		$this->obj->setColor('#00BFFF');
		self::assertEquals('DeepSkyBlue', $this->obj->getColor());

		// A '-' prefix overrides the preset with the standard web color.
		$this->obj->setColor('-Green');
		self::assertEquals('Green', $this->obj->getColor());

		// A hex value with no matching constant stays a hex value.
		$this->obj->setColor('#998877');
		self::assertEquals('#998877', $this->obj->getColor());

		// The published file name prefers the color name when set.
		$this->obj->setColor('DeepSkyBlue');
		self::assertEquals(1, preg_match('/tdot\-deepskyblue\-/i', $this->obj->getAssetFilePath()));

		// An unsupported color throws.
		try {
			$this->obj->setColor('NotAColor');
			self::fail('Expected TInvalidDataValueException for an unsupported color');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testMainHighlightTWebColor()
	{
		// Main and Highlight colors accept TWebColor constant names, stored as hex.
		$this->obj->setMainColor('Maroon');
		self::assertNull($this->obj->getColor());
		self::assertEquals('#800000', $this->obj->getMainColor());

		$this->obj->setHighlightColor('Lime');
		self::assertEquals('#00FF00', $this->obj->getHighlightColor());

		// The file name falls back to main and highlight when no color is set.
		self::assertEquals(1, preg_match('/tdot\-800000\-00ff00\-/i', $this->obj->getAssetFilePath()));
	}

	public function testColorComputed()
	{
		// Darkening cannot take a zero channel below 0; lightening cannot exceed 255.
		$this->obj->setColor('#000000');
		self::assertEquals('#000000', $this->obj->getMainColor());          // darken preserves black
		self::assertNotEquals('#000000', $this->obj->getHighlightColor());  // highlight lifts it

		$this->obj->setColor('#FFFFFF');
		self::assertEquals('#FFFFFF', $this->obj->getHighlightColor());      // lighten preserves white
		self::assertNotEquals('#FFFFFF', $this->obj->getMainColor());        // main darkens it

		// A pure-channel hex keeps its zero channels when darkened; the highlight lifts them.
		$this->obj->setColor('-Green'); // '#008000' computed, not the preset
		$main = $this->obj->getMainColor();
		$highlight = $this->obj->getHighlightColor();
		self::assertEquals('00', strtoupper(substr($main, 1, 2)));           // R stays 0
		self::assertEquals('00', strtoupper(substr($main, 5, 2)));           // B stays 0
		self::assertLessThan(0x80, hexdec(substr($main, 3, 2)));             // G darkened below 128
		self::assertGreaterThan(0x80, hexdec(substr($highlight, 3, 2)));     // G lightened above 128

		// The '-' override yields different colors than the named preset.
		$this->obj->setColor('Green');
		self::assertEquals('#007000', $this->obj->getMainColor());
		$this->obj->setColor('-Green');
		self::assertNotEquals('#007000', $this->obj->getMainColor());
	}

	public function testColorCascade()
	{
		// The computed (non-preset) path cascades a hue-preserving HSL-lightness shift into
		// a per-channel RGB nudge, each cross-modulated by the other space (chroma scales
		// the HSL stage, saturation the RGB stage). These are the main/highlight for a hex.
		$this->obj->setColor('#998877');
		self::assertEquals('#816F5D', $this->obj->getMainColor());
		self::assertEquals('#B2A292', $this->obj->getHighlightColor());

		// The cascade and cross-modulation tuning constants are defined.
		$rc = new ReflectionClass(TDot::class);
		self::assertEqualsWithDelta(0.9633, $rc->getConstant('MAIN_DEPTH_SCALE'), 1e-9);
		self::assertEqualsWithDelta(0.2312, $rc->getConstant('HSL_DEPTH_FRACTION'), 1e-9);
		self::assertEqualsWithDelta(0.2157, $rc->getConstant('HSL_CHROMA_MOD'), 1e-9);
		self::assertEqualsWithDelta(-0.2100, $rc->getConstant('RGB_SAT_MOD'), 1e-9);

		// A pure-channel color keeps its zero channels through the cascade when darkened.
		$this->obj->setColor('-Green'); // computed #008000, not the preset
		$main = $this->obj->getMainColor();
		self::assertEquals('00', strtoupper(substr($main, 1, 2)));   // R stays 0
		self::assertEquals('00', strtoupper(substr($main, 5, 2)));   // B stays 0
	}

	public function testColorAlias()
	{
		// Aliases that share a hex collapse to the first-declared TWebColor constant.
		$this->obj->setColor('cyan');
		self::assertEquals('Aqua', $this->obj->getColor());
		self::assertEquals('#00C0C0', $this->obj->getMainColor());   // the aqua/cyan preset main

		$this->obj->setColor('magenta');
		self::assertEquals('Fuchsia', $this->obj->getColor());
	}

	public function testDefaultDepth()
	{
		// The Depth default is published as a constant and used by a fresh dot.
		self::assertEquals(24, TDot::DEFAULT_DEPTH);
		self::assertEquals(TDot::DEFAULT_DEPTH, (new TDot())->getDepth());
	}

	public function testHslRoundTrip()
	{
		// The cascade's RGB<->HSL converters round-trip: rgbToHsl then hslToRgb returns the
		// source (within rounding) across grays, primaries, and arbitrary colors.
		$r2h = new ReflectionMethod(TDot::class, 'rgbToHsl');
		$h2r = new ReflectionMethod(TDot::class, 'hslToRgb');
		$r2h->setAccessible(true);
		$h2r->setAccessible(true);

		foreach ([[0, 0, 0], [255, 255, 255], [128, 128, 128], [255, 0, 0], [0, 128, 0], [0, 0, 255], [153, 136, 119], [64, 200, 30]] as $rgb) {
			[$h, $s, $l] = $r2h->invoke($this->obj, ...$rgb);
			$back = $h2r->invoke($this->obj, $h, $s, $l);
			foreach ($rgb as $i => $v) {
				self::assertEqualsWithDelta($v, $back[$i], 0.5, "channel $i of " . implode(',', $rgb));
			}
		}
	}

	public function testNudge()
	{
		// nudge() scales a channel change toward the channel extremes. setColor only ever
		// passes true/false, so the null-default and taper branches are exercised here.
		$nudge = new ReflectionMethod(TDot::class, 'nudge');
		$nudge->setAccessible(true);

		// A null style follows the sign of the change: negative is top-exaggerated, positive bottom.
		self::assertEquals(0, $nudge->invoke($this->obj, 0, -10, null));    // darken at the floor stays 0
		self::assertEquals(20, $nudge->invoke($this->obj, 0, 10, null));    // lighten at the floor doubles

		// true (top-exaggerated) makes no change at the channel floor.
		self::assertEquals(0, $nudge->invoke($this->obj, 0, -10, true));

		// false (bottom-exaggerated) makes no change at the ceiling and doubles at the floor.
		self::assertEquals(255, $nudge->invoke($this->obj, 255, 10, false));
		self::assertEquals(-20, $nudge->invoke($this->obj, 0, -10, false));

		// Any other style (eg 0) tapers both edges: extremes are pinned, the full change lands midband.
		self::assertEquals(0, $nudge->invoke($this->obj, 0, -10, 0));
		self::assertEquals(255, $nudge->invoke($this->obj, 255, 10, 0));
		self::assertEquals(138, $nudge->invoke($this->obj, 128, 10, 0));
	}

	public function testDepthRecompute()
	{
		// A custom hex color recomputes its main/highlight when the depth changes.
		$this->obj->setColor('#998877');
		$main = $this->obj->getMainColor();
		$this->obj->setDepth(60);
		self::assertEquals(60, $this->obj->getDepth());
		self::assertEquals('#998877', $this->obj->getColor());
		self::assertNotEquals($main, $this->obj->getMainColor());   // deeper => different shade

		// A named preset color is depth independent.
		$this->obj->setColor('Green');
		$presetMain = $this->obj->getMainColor();
		$this->obj->setDepth(120);
		self::assertEquals($presetMain, $this->obj->getMainColor());
	}

	public function testDepthClamp()
	{
		$this->obj->setDepth(999);
		self::assertEquals(255, $this->obj->getDepth());
		$this->obj->setDepth(-5);
		self::assertEquals(0, $this->obj->getDepth());
	}

	public function testShadowOpacityClamp()
	{
		$this->obj->setShadowOpacity(5.0);
		self::assertEquals(1.0, $this->obj->getShadowOpacity());
		$this->obj->setShadowOpacity(-3.0);
		self::assertEquals(0.0, $this->obj->getShadowOpacity());
	}

	public function testSizeEdgeCases()
	{
		// A non-positive size throws.
		try {
			$this->obj->setSize(-4);
			self::fail('Expected TInvalidDataValueException for a non-positive size');
		} catch (TInvalidDataValueException $e) {
		}
		// null and '' fall back to the default size.
		$this->obj->setSize(null);
		self::assertEquals(21, $this->obj->getSize());
		$this->obj->setSize('');
		self::assertEquals(21, $this->obj->getSize());
	}

	public function testFlatRendering()
	{
		$this->obj->setColor('Green');
		$this->obj->setFlat(true);
		$this->obj->setSize(16);

		// File name is color-stroke-width for a bordered flat dot (5% default width => 5pct).
		self::assertStringContainsString('/tdot-green-007000-5pct.svg', $this->obj->getAssetFilePath());

		$svg = $this->decodeEmbed();
		self::assertStringContainsString("fill='Green'", $svg);
		self::assertStringContainsString("stroke='#007000'", $svg);
		self::assertStringContainsString("stroke-width='5%'", $svg);
		self::assertStringNotContainsString('radialGradient', $svg);

		// Borderless flat drops the stroke and the stroke segment of the file name.
		$this->obj->setFlatBorder(false);
		self::assertStringContainsString('/tdot-green.svg', $this->obj->getAssetFilePath());
		self::assertStringNotContainsString('stroke=', $this->decodeEmbed());
	}

	public function testFlatBorderWidth()
	{
		self::assertEquals('5%', $this->obj->getFlatBorderWidth());   // default

		$this->obj->setColor('Green');
		$this->obj->setFlat(true);
		$this->obj->setFlatBorderWidth('2%');
		self::assertEquals('2%', $this->obj->getFlatBorderWidth());

		// The width drives the SVG stroke-width and the published file name.
		self::assertStringContainsString("stroke-width='2%'", $this->decodeEmbed());
		self::assertStringContainsString('/tdot-green-007000-2pct.svg', $this->obj->getAssetFilePath());

		// A different width yields a different file name.
		$this->obj->setFlatBorderWidth('5%');
		self::assertStringContainsString('/tdot-green-007000-5pct.svg', $this->obj->getAssetFilePath());

		// A bare length and a percentage no longer collide in the file name.
		$this->obj->setFlatBorderWidth('5');
		self::assertStringContainsString('/tdot-green-007000-5.svg', $this->obj->getAssetFilePath());
	}

	public function testFlatBorderWidthValidation()
	{
		// Valid forms: a bare number, a percentage, and a length unit.
		$this->obj->setFlatBorderWidth('3');
		self::assertEquals('3', $this->obj->getFlatBorderWidth());
		$this->obj->setFlatBorderWidth('2.5%');
		self::assertEquals('2.5%', $this->obj->getFlatBorderWidth());
		$this->obj->setFlatBorderWidth('4px');
		self::assertEquals('4px', $this->obj->getFlatBorderWidth());

		// An empty value restores the default.
		$this->obj->setFlatBorderWidth('');
		self::assertEquals('5%', $this->obj->getFlatBorderWidth());

		// Invalid forms throw.
		foreach (['abc', '5em5', '#5', '-2', '5 px'] as $bad) {
			try {
				$this->obj->setFlatBorderWidth($bad);
				self::fail("Expected TInvalidDataValueException for FlatBorderWidth '$bad'");
			} catch (TInvalidDataValueException $e) {
			}
		}
	}

	public function testGenerateSVG3D()
	{
		$this->obj->setColor('Green');
		$svg = $this->decodeEmbed();
		self::assertStringContainsString('radialGradient', $svg);
		self::assertStringContainsString("stop-color='#007000'", $svg);   // main
		self::assertStringContainsString("stop-color='#00B000'", $svg);   // highlight
	}

	public function testPublishIntegration()
	{
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBasePath('AssetAlias');
		$manager->init(null);

		$this->obj->setColor('Green');
		self::$app->setMode(TApplicationMode::Normal);

		// Publish through TAssetManager and confirm the file lands where it reports.
		$url = $manager->publishFilePath($this->obj);
		self::assertNotEmpty($url);
		self::assertEquals($manager->getPublishedUrl($this->obj), $url);
		$path = $manager->getPublishedPath($this->obj);
		self::assertTrue(is_file($path));

		// The published file content equals the embedded SVG for the same dot.
		self::assertEquals($this->decodeEmbed(), file_get_contents($path));

		// Re-publishing returns the cached URL without error.
		self::assertEquals($url, $manager->publishFilePath($this->obj));
	}

	public function testInlineRendering()
	{
		$this->obj->setColor('Green');
		$this->obj->setSize(24);
		$this->obj->setPublishStyle('Inline');
		self::assertEquals('Inline', $this->obj->getPublishStyle());

		// Inline writes an <svg> element directly: no <img>, no data URI.
		$output = $this->render($this->obj);
		self::assertStringStartsWith('<svg', $output);
		self::assertStringContainsString('</svg>', $output);
		self::assertStringContainsString('radialGradient', $output);          // 3D inner markup
		self::assertStringContainsString("stop-color='#007000'", $output);    // main color
		self::assertStringNotContainsString('<img', $output);
		self::assertStringNotContainsString('data:image/svg', $output);

		// The Size is honored as the <svg> width and height.
		self::assertStringContainsString('width="24"', $output);
		self::assertStringContainsString('height="24"', $output);
	}

	public function testInlineHonorsImgProperties()
	{
		// The web control (img) properties land on the inline <svg> root element.
		$this->obj->setColor('Green');
		$this->obj->setPublishStyle('Inline');
		$this->obj->setCssClass('badge');
		$this->obj->setToolTip('a green dot');

		$output = $this->render($this->obj);
		self::assertStringContainsString('class="badge"', $output);
		self::assertStringContainsString('title="a green dot"', $output);
		self::assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $output);
	}

	public function testInlineNullsDecorator()
	{
		// Inline nulls any TWebControlDecorator so the <svg> stands alone.
		$this->obj->setColor('Green');
		$this->obj->setPublishStyle('Inline');
		$this->obj->getDecorator(true);
		self::assertNotNull($this->obj->getDecorator(false));

		$this->render($this->obj);
		self::assertNull($this->obj->getDecorator(false));
	}

	public function testInlineFlatRendering()
	{
		$this->obj->setColor('Green');
		$this->obj->setFlat(true);
		$this->obj->setPublishStyle('Inline');

		$output = $this->render($this->obj);
		self::assertStringContainsString("<circle fill='Green'", $output);
		self::assertStringNotContainsString('<img', $output);
	}

	public function testInlineGetImageUrlFallsBackToData()
	{
		// Inline has no URL of its own, so getImageUrl degrades to the data URI.
		$this->obj->setColor('Green');
		$this->obj->setPublishStyle('Inline');
		self::assertStringStartsWith('data:image/svg+xml;base64,', $this->obj->getImageUrl());
	}

	public function testGetImageUrlPublishStyle()
	{
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBasePath('AssetAlias');
		$manager->init(null);

		// Explicit Publish publishes even in Debug mode (Auto would embed).
		self::$app->setMode(TApplicationMode::Debug);
		$this->obj->setPublishStyle('Publish');
		$url = $this->obj->getImageUrl();
		self::assertStringStartsNotWith('data:', $url);
		self::assertEquals($manager->getPublishedUrl($this->obj), $url);
	}

	/**
	 * @return string the SVG of the dot, decoded from the Embed image url.
	 */
	private function decodeEmbed(): string
	{
		$style = $this->obj->getPublishStyle();
		$this->obj->setPublishStyle('Embed');
		$prefix = 'data:image/svg+xml;base64,';
		$svg = base64_decode(substr($this->obj->getImageUrl(), strlen($prefix)));
		$this->obj->setPublishStyle($style);
		return $svg;
	}

	public function testMainColor()
	{
		$value = '#998877';
		$this->obj->setMainColor($value);
		self::assertNull($this->obj->getColor());
		self::assertEquals($value, $this->obj->getMainColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
	}
	
	public function testHighlightColor()
	{
		$value = '#AA9988';
		$this->obj->setHighlightColor($value);
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertEquals($value, $this->obj->getHighlightColor());
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
	}
	
	public function testSize()
	{
		$value = 111.11;
		$this->obj->setSize($value);
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertEquals(round($value), $this->obj->getSize());
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
	}
	
	public function testDepth()
	{
		$value = 48;
		$this->obj->setDepth($value);
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals($value, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
	}
	
	public function testShadowOpacity()
	{
		$value = .333;
		$this->obj->setShadowOpacity($value);
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertEquals($value, $this->obj->getShadowOpacity());
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
	}
	
	public function testFlat()
	{
		$value = true;
		$this->obj->setFlat($value);
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertEquals($value, $this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
		
		$value = 'false';
		$this->obj->setFlat($value);
		self::assertEquals(false, $this->obj->getFlat());
		$value = 'true';
		$this->obj->setFlat($value);
		self::assertEquals(true, $this->obj->getFlat());
	}
	
	public function testFlatBorder()
	{
		$value = false;
		$this->obj->setFlatBorder($value);
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertEquals($value, $this->obj->getFlatBorder());
		self::assertEquals('Auto', $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
		
		$value = 'true';
		$this->obj->setFlat($value);
		self::assertEquals(true, $this->obj->getFlat());
		$value = 'false';
		$this->obj->setFlat($value);
		self::assertEquals(false, $this->obj->getFlat());
	}
	
	
	public function testPublishStyle()
	{
		$value = 'Publish';
		$this->obj->setPublishStyle($value);
		self::assertNull($this->obj->getColor());
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#848484', $this->obj->getMainColor()));
		self::assertLessThan(self::COLOR_DIST_MAX, $this->colorDistance('#DADADA', $this->obj->getHighlightColor()));
		self::assertLessThan(7, abs(18 - $this->obj->getSize()));
		self::assertEquals(24, $this->obj->getDepth());
		self::assertLessThan(0.1, abs(0.618 - $this->obj->getShadowOpacity()));
		self::assertFalse($this->obj->getFlat());
		self::assertTrue($this->obj->getFlatBorder());
		self::assertEquals($value, $this->obj->getPublishStyle());
		self::assertEquals(1, preg_match('/^\/prado\/tdot\-svgs\/tdot\-(.*?)\.svg$/i', $this->obj->getAssetFilePath()));
		self::assertEquals(0, $this->obj->getAssetModificationDate());
		
		$value = 'Embed';
		$this->obj->setPublishStyle($value);
		self::assertEquals($value, $this->obj->getPublishStyle());
		
		$value = 'Auto';
		$this->obj->setPublishStyle($value);
		self::assertEquals($value, $this->obj->getPublishStyle());
		
		try {
			$this->obj->setPublishStyle('BadValue');
			self::fail('Expected TInvalidDataValueException for an unsupported publish style');
		} catch (TInvalidDataValueException $e) {
		}
	}
	
	public function testGetImageUrl()
	{
		//Build TAssetManager
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBasePath('AssetAlias');
		$manager->init(null);
		
		//this publishes the asset or encodes
		self::$app->setMode(TApplicationMode::Debug);
		$value = 'Embed';
		$this->obj->setPublishStyle($value);
		$str = 'data:image/svg+xml;base64,';
		self::assertEquals(0, strncmp($str, $this->obj->getImageUrl(), strlen($str)));
		
		$value = 'Auto';
		$this->obj->setPublishStyle($value);
		$str = 'data:image/svg+xml;base64,';
		self::assertEquals(0, strncmp($str, $this->obj->getImageUrl(), strlen($str)));
		
		self::$app->setMode(TApplicationMode::Normal);
		$publishedUrl = $this->obj->getImageUrl();
		self::assertEquals($manager->getPublishedUrl($this->obj), $publishedUrl);
		self::assertTrue(is_file($manager->getPublishedPath($this->obj)));
	}
}
