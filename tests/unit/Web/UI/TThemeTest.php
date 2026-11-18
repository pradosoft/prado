<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Web\UI\TTheme;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\TTemplate;
use Prado\Caching\TMemoryCache;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\Prado;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Web\UI\TNestedPathControl;
use Prado\Test\Unit\PradoUnit;

/**
 * A theme that skips each file named in {@see $skipped} through dyProcessFile.
 */
class TSkippingTheme extends TTheme
{
	public static array $skipped = [];

	public function dyProcessFile($returnValue, $file)
	{
		return in_array($file, static::$skipped, true);
	}
}

class TThemeTest extends \PHPUnit\Framework\TestCase
{
	/** @var ?TTestApplication The application of a test that needs its own cache, mode, or culture. */
	private ?TTestApplication $_testApp = null;

	private $_themePath;
	private $_app;

	/** @var ?string The per-test directory holding the theme directory. */
	private ?string $_tempDir = null;

	protected function setUp(): void
	{
		$this->_tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-theme-' . bin2hex(random_bytes(4));
		$this->_themePath = $this->_tempDir . DIRECTORY_SEPARATOR . 'testtheme';
		mkdir($this->_themePath, 0o755, true);
		$this->_app = Prado::getApplication();
	}

	protected function tearDown(): void
	{
		if ($this->_testApp !== null) {
			$this->_testApp->restoreApplication();
			$this->_testApp = null;
		}
		TSkippingTheme::$skipped = [];
		$this->removeDirectory($this->_tempDir);
		$this->_tempDir = null;
		$this->_themePath = null;
		$this->_app = null;
	}

	/**
	 * Removes a directory and everything in it.
	 * @param string $dir the directory to remove.
	 */
	private function removeDirectory(string $dir): void
	{
		if (!is_dir($dir) || is_link($dir)) {
			return;
		}
		foreach (scandir($dir) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $entry;
			if (is_dir($path) && !is_link($path)) {
				$this->removeDirectory($path);
			} else {
				unlink($path);
			}
		}
		rmdir($dir);
	}

	private function _createSkin($controlClass, $skinId, array $properties)
	{
		$skinId = $skinId ?? 0;
		$theme = PradoUnit::reflectionClass(TTheme::class)->newInstanceWithoutConstructor();

		$skins = [];
		if (!isset($skins[$controlClass])) {
			$skins[$controlClass] = [];
		}
		if (!isset($skins[$controlClass][$skinId])) {
			$skins[$controlClass][$skinId] = [];
		}
		$skins[$controlClass][$skinId] = array_merge($skins[$controlClass][$skinId], $properties);
		PradoUnit::setProp($theme, '_skins', $skins);
		PradoUnit::setProp($theme, '_name', 'testtheme');
		PradoUnit::setProp($theme, '_themeUrl', '/themes/testtheme');
		PradoUnit::setProp($theme, '_themePath', $this->_themePath);

		return $theme;
	}

	// ================================================================================
	// Basic Constructor Tests
	// ================================================================================

	public function testConstruct()
	{
		$themeUrl = '/themes/testtheme';
		$theme = new TTheme($this->_themePath, $themeUrl);
		$this->assertEquals('testtheme', $theme->getName());
		$this->assertEquals(realpath($this->_themePath), $theme->getBasePath());
		$this->assertEquals($themeUrl, $theme->getBaseUrl());
	}

	public function testGetName()
	{
		$theme = new TTheme($this->_themePath, '/themes/testtheme');
		$this->assertEquals('testtheme', $theme->getName());
	}

	public function testGetBaseUrl()
	{
		$theme = new TTheme($this->_themePath, '/themes/testtheme');
		$this->assertEquals('/themes/testtheme', $theme->getBaseUrl());
	}

	public function testGetBasePath()
	{
		$theme = new TTheme($this->_themePath, '/themes/testtheme');
		$this->assertEquals(realpath($this->_themePath), $theme->getBasePath());
	}

	public function testGetSkins()
	{
		$theme = new TTheme($this->_themePath, '/themes/testtheme');
		$skins = $theme->getSkins();
		$this->assertIsArray($skins);
	}

	// ================================================================================
	// CONFIG_VALUE Tests - Basic property setting
	// ================================================================================

	public function testApplySkin_ConfigValue_SimpleProperty()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Skin Text',
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Skin Text', $label->getText());
	}

	public function testApplySkin_ConfigValue_ComplexProperty_WithDot()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'font.size' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Font.Size',
				TTemplate::PROP_VALUE => 14,
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals(14, $label->getFont()->getSize());
	}

	public function testApplySkin_ConfigValue_MultipleProperties()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'First',
			],
			'font.bold' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Font.Bold',
				TTemplate::PROP_VALUE => true,
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('First', $label->getText());
		$this->assertTrue($label->getFont()->getBold());
	}

	/**
	 * A parent path is applied before any path nested beneath it, so a nested
	 * skin value survives regardless of the order the skin declares them in.
	 */
	public function testApplySkin_AppliesParentPathBeforeNestedPath()
	{
		$cfg = [
			TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
			TTemplate::PROP_NAME => 'Cfg',
			TTemplate::PROP_VALUE => 'value',
		];
		$cfgSize = [
			TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
			TTemplate::PROP_NAME => 'Cfg.Size',
			TTemplate::PROP_VALUE => 'child',
		];
		foreach ([
			['cfg.size' => $cfgSize, 'cfg' => $cfg],
			['cfg' => $cfg, 'cfg.size' => $cfgSize],
		] as $skin) {
			$theme = $this->_createSkin(TNestedPathControl::class, 0, $skin);
			$control = new TNestedPathControl();
			$this->assertTrue($theme->applySkin($control));
			$this->assertEquals('child', $control->getCfg()->getSize(), 'skin order: ' . implode(', ', array_keys($skin)));
		}
	}

	public function testApplySkin_ConfigValue_WithSkinID()
	{
		$theme = $this->_createSkin(TLabel::class, 'special', [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Special Skin',
			],
		]);

		$label = new TLabel();
		$label->setSkinID('special');
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Special Skin', $label->getText());
	}

	public function testApplySkin_ConfigValue_WithSkinIdZero()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Zero Skin',
			],
		]);

		$label = new TLabel();
		$label->setSkinID('0');
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Zero Skin', $label->getText());
	}

	public function testApplySkin_ConfigValue_EmptyStringValue()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => '',
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('', $label->getText());
	}

	public function testApplySkin_ConfigValue_NumericValue()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'font.size' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Font.Size',
				TTemplate::PROP_VALUE => 12,
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals(12, $label->getFont()->getSize());
	}

	public function testApplySkin_ConfigValue_BooleanFalse()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'font.bold' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Font.Bold',
				TTemplate::PROP_VALUE => false,
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertFalse($label->getFont()->getBold());
	}

	// ================================================================================
	// CONFIG_DATABIND Tests - Data binding
	// ================================================================================

	public function testApplySkin_ConfigDatabind()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_DATABIND,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'DataField',
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
	}

	public function testApplySkin_ConfigDatabind_BindsProperty()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_DATABIND,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Title',
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
	}

	// ================================================================================
	// CONFIG_EXPRESSION Tests - Evaluated expression
	// ================================================================================

	public function testApplySkin_ConfigExpression()
	{
		$page = new TPage();
		$page->setID('TestPage');
		
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_EXPRESSION,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => '"Expression Result"',
			],
		]);

		$label = new TLabel();
		$label->setPage($page);
		$label->setID('Label1');
		$page->getControls()->add($label);
		
		$this->assertTrue($theme->applySkin($label));
	}

	public function testApplySkin_ConfigExpression_VariableInExpression()
	{
		$page = new TPage();
		$page->setID('TestPage');
		
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_EXPRESSION,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => '1 + 1',
			],
		]);

		$label = new TLabel();
		$label->setPage($page);
		$label->setID('Label2');
		$page->getControls()->add($label);
		
		$this->assertTrue($theme->applySkin($label));
	}

	// ================================================================================
	// CONFIG_ASSET Tests - Asset URL with theme path
	// ================================================================================

	public function testApplySkin_ConfigAsset()
	{
		$theme = $this->_createSkin(TPanel::class, 0, [
			'backimageurl' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_ASSET,
				TTemplate::PROP_NAME => 'BackImageUrl',
				TTemplate::PROP_VALUE => 'images/background.png',
			],
		]);

		$panel = new TPanel();
		$this->assertTrue($theme->applySkin($panel));
	}

	public function testApplySkin_ConfigAsset_WithLeadingSlash()
	{
		$theme = $this->_createSkin(TPanel::class, 0, [
			'backimageurl' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_ASSET,
				TTemplate::PROP_NAME => 'BackImageUrl',
				TTemplate::PROP_VALUE => '/images/background.png',
			],
		]);

		$panel = new TPanel();
		$this->assertTrue($theme->applySkin($panel));
	}

	public function testApplySkin_ConfigAsset_NestedPath()
	{
		$theme = $this->_createSkin(TPanel::class, 0, [
			'backimageurl' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_ASSET,
				TTemplate::PROP_NAME => 'BackImageUrl',
				TTemplate::PROP_VALUE => 'assets/img/bg.png',
			],
		]);

		$panel = new TPanel();
		$this->assertTrue($theme->applySkin($panel));
	}

	// ================================================================================
	// CONFIG_PARAMETER Tests - Application parameter
	// ================================================================================

	public function testApplySkin_ConfigParameter()
	{
		$app = $this->_app;
		$app->getParameters()->add('themeparam', 'Parameter Value');
		
		try {
			$theme = $this->_createSkin(TLabel::class, 0, [
				'text' => [
					TTemplate::PROP_TYPE => TTemplate::CONFIG_PARAMETER,
					TTemplate::PROP_NAME => 'Text',
					TTemplate::PROP_VALUE => 'themeparam',
				],
			]);

			$label = new TLabel();
			$this->assertTrue($theme->applySkin($label));
			$this->assertEquals('Parameter Value', $label->getText());
		} finally {
			$app->getParameters()->remove('themeparam');
		}
	}

	public function testApplySkin_ConfigParameter_NumericValue()
	{
		$app = $this->_app;
		$app->getParameters()->add('count', 42);
		
		try {
			$theme = $this->_createSkin(TLabel::class, 0, [
				'font.size' => [
					TTemplate::PROP_TYPE => TTemplate::CONFIG_PARAMETER,
					TTemplate::PROP_NAME => 'Font.Size',
					TTemplate::PROP_VALUE => 'count',
				],
			]);

			$label = new TLabel();
			$this->assertTrue($theme->applySkin($label));
			$this->assertEquals(42, $label->getFont()->getSize());
		} finally {
			$app->getParameters()->remove('count');
		}
	}

	// ================================================================================
	// CONFIG_TEMPLATE Tests - Template value
	// ================================================================================

	public function testApplySkin_ConfigTemplate()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_TEMPLATE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Template Value',
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Template Value', $label->getText());
	}

	// ================================================================================
	// CONFIG_LOCALIZATION Tests - Localized string
	// ================================================================================

	public function testApplySkin_ConfigLocalization()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_LOCALIZATION,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Hello World',
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Hello World', $label->getText());
	}

	public function testApplySkin_ConfigLocalization_UnicodeChars()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_LOCALIZATION,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => '你好世界',
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('你好世界', $label->getText());
	}

	public function testApplySkin_ConfigLocalization_SpecialChars()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_LOCALIZATION,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => "Line1\nLine2\tTab",
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals("Line1\nLine2\tTab", $label->getText());
	}

	// ================================================================================
	// Error/Edge Case Tests
	// ================================================================================

	public function testApplySkin_InvalidPropertyType_ThrowsException()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => 999,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'value',
			],
		]);

		$label = new TLabel();
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$theme->applySkin($label);
	}

	public function testApplySkin_EmptySkinArray_ReturnsFalse()
	{
		$theme = PradoUnit::reflectionClass(TTheme::class)->newInstanceWithoutConstructor();

		PradoUnit::setProp($theme, '_skins', []);
		PradoUnit::setProp($theme, '_name', 'testtheme');
		
		$label = new TLabel();
		$this->assertFalse($theme->applySkin($label));
	}

	public function testApplySkin_NoSkinForControlClass_ReturnsFalse()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Label Skin',
			],
		]);

		$button = new TButton();
		$this->assertFalse($theme->applySkin($button));
	}

	public function testApplySkin_SpecificSkinIdNotMatching_ReturnsFalse()
	{
		$theme = $this->_createSkin(TLabel::class, 'specific', [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Specific Skin',
			],
		]);

		$label = new TLabel();
		$label->setSkinID('other');
		$this->assertFalse($theme->applySkin($label));
	}

	public function testApplySkin_NonexistentSkinId_ReturnsFalse()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Default Skin',
			],
		]);

		$label = new TLabel();
		$label->setSkinID('nonexistent');
		$this->assertFalse($theme->applySkin($label));
	}

	public function testApplySkin_MultipleSkinIds_SameControl()
	{
		$theme = PradoUnit::reflectionClass(TTheme::class)->newInstanceWithoutConstructor();

		PradoUnit::setProp($theme, '_skins', [
			TLabel::class => [
				0 => [
					'text' => [
						TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
						TTemplate::PROP_NAME => 'Text',
						TTemplate::PROP_VALUE => 'Default',
					],
				],
				'special' => [
					'text' => [
						TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
						TTemplate::PROP_NAME => 'Text',
						TTemplate::PROP_VALUE => 'Special',
					],
				],
			],
		]);
		PradoUnit::setProp($theme, '_name', 'testtheme');

		$label = new TLabel();
		$label->setSkinID('special');
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Special', $label->getText());
	}

	public function testApplySkin_MixedConfigTypes()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'text' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Text',
				TTemplate::PROP_VALUE => 'Mixed',
			],
			'font.bold' => [
				TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE,
				TTemplate::PROP_NAME => 'Font.Bold',
				TTemplate::PROP_VALUE => true,
			],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Mixed', $label->getText());
		$this->assertTrue($label->getFont()->getBold());
	}

	// ================================================================================
	// CSS/JS File Tests
	// ================================================================================

	public function testGetStyleSheetFiles()
	{
		$cssPath = $this->_themePath . '/style.css';
		file_put_contents($cssPath, 'body {}');
		
		try {
			$theme = new TTheme($this->_themePath, '/themes/testtheme');
			$files = $theme->getStyleSheetFiles();
			$this->assertNotEmpty($files);
		} finally {
			@unlink($cssPath);
		}
	}

	public function testGetJavaScriptFiles()
	{
		$jsPath = $this->_themePath . '/app.js';
		file_put_contents($jsPath, 'var x = 1;');
		
		try {
			$theme = new TTheme($this->_themePath, '/themes/testtheme');
			$files = $theme->getJavaScriptFiles();
			$this->assertNotEmpty($files);
		} finally {
			@unlink($jsPath);
		}
	}

	public function testGetStyleSheetFiles_EmptyDirectory()
	{
		$theme = new TTheme($this->_themePath, '/themes/testtheme');
		$files = $theme->getStyleSheetFiles();
		$this->assertIsArray($files);
	}

	public function testGetJavaScriptFiles_EmptyDirectory()
	{
		$theme = new TTheme($this->_themePath, '/themes/testtheme');
		$files = $theme->getJavaScriptFiles();
		$this->assertIsArray($files);
	}
	// ================================================================================
	// Construction from the theme directory
	// ================================================================================

	/**
	 * @return TTestApplication an application of this test, with no cache in Debug mode.
	 */
	private function newApplication(): TTestApplication
	{
		return $this->_testApp = new TTestApplication(__DIR__ . '/../app');
	}

	/**
	 * Writes a file into the theme directory.
	 * @param string $name the file name.
	 * @param string $content the file content.
	 * @return string the file path.
	 */
	private function writeThemeFile(string $name, string $content = ''): string
	{
		$path = $this->_themePath . DIRECTORY_SEPARATOR . $name;
		file_put_contents($path, $content);
		return $path;
	}

	public function testConstructParsesTheSkinFilesWithASkinID()
	{
		$this->newApplication();
		$this->writeThemeFile('label.skin', "<com:TLabel Text=\"Default\" />\n<com:TLabel SkinID=\"Big\" Text=\"Big\" />\n");

		$theme = new TTheme($this->_themePath, '/themes/testtheme');

		$skins = $theme->getSkins();
		$this->assertSame([0, 'Big'], array_keys($skins[TLabel::class]));
		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertSame('Default', $label->getText());
		$label = new TLabel();
		$label->setSkinID('Big');
		$this->assertTrue($theme->applySkin($label));
		$this->assertSame('Big', $label->getText());
	}

	public function testConstructRejectsANestedSkinControl()
	{
		$this->newApplication();
		$this->writeThemeFile('panel.skin', '<com:TPanel><com:TLabel Text="x" /></com:TPanel>');
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('cannot be within another skin');
		new TTheme($this->_themePath, '/themes/testtheme');
	}

	public function testConstructRejectsADuplicatedSkinIDAcrossSkinFiles()
	{
		$this->newApplication();
		$this->writeThemeFile('a.skin', '<com:TLabel SkinID="Big" Text="a" />');
		$this->writeThemeFile('b.skin', '<com:TLabel SkinID="Big" Text="b" />');
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('Big');
		new TTheme($this->_themePath, '/themes/testtheme');
	}

	public function testConstructSortsTheStyleSheetAndJavaScriptFilesAndIgnoresOtherFiles()
	{
		$this->newApplication();
		foreach (['b.css', 'a.css', 'b.js', 'a.js', 'readme.txt'] as $file) {
			$this->writeThemeFile($file);
		}
		$theme = new TTheme($this->_themePath, '/t');
		$this->assertSame(['/t' . DIRECTORY_SEPARATOR . 'a.css', '/t' . DIRECTORY_SEPARATOR . 'b.css'], $theme->getStyleSheetFiles());
		$this->assertSame(['/t' . DIRECTORY_SEPARATOR . 'a.js', '/t' . DIRECTORY_SEPARATOR . 'b.js'], $theme->getJavaScriptFiles());
		$this->assertSame([], $theme->getSkins());
	}

	public function testConstructSkipsAFileTheProcessFileEventClaims()
	{
		$this->newApplication();
		$this->writeThemeFile('a.css');
		$this->writeThemeFile('skip.css');
		TSkippingTheme::$skipped = ['skip.css'];
		$theme = new TSkippingTheme($this->_themePath, '/t');
		$this->assertSame(['/t' . DIRECTORY_SEPARATOR . 'a.css'], $theme->getStyleSheetFiles());
	}

	public function testConstructOfAMissingDirectoryThrows()
	{
		$this->newApplication();
		$this->expectException(\Prado\Exceptions\TPhpErrorException::class);
		new TTheme($this->_themePath . DIRECTORY_SEPARATOR . 'missing', '/t');
	}

	// ================================================================================
	// Right-to-left style sheets
	// ================================================================================

	/**
	 * Writes one left-to-right style sheet and three right-to-left ones.
	 */
	private function writeRtlStyleSheets(): void
	{
		foreach (['main.css', 'main.rtl.css', 'printrtl.css', 'side.rtl.print.css', 'x.y.css'] as $file) {
			$this->writeThemeFile($file);
		}
	}

	public function testALeftToRightCultureDropsTheRtlStyleSheets()
	{
		$this->newApplication()->getGlobalization()->setCulture('en');
		$this->writeRtlStyleSheets();
		$theme = new TTheme($this->_themePath, '/t');
		$this->assertSame(['/t/main.css', '/t/x.y.css'], array_values(str_replace(DIRECTORY_SEPARATOR, '/', $theme->getStyleSheetFiles())));
	}

	public function testARightToLeftCultureAppendsTheRtlStyleSheets()
	{
		$this->newApplication()->getGlobalization()->setCulture('ar');
		$this->writeRtlStyleSheets();
		$theme = new TTheme($this->_themePath, '/t');
		$this->assertSame(
			['/t/main.css', '/t/x.y.css', '/t/main.rtl.css', '/t/printrtl.css', '/t/side.rtl.print.css'],
			array_values(str_replace(DIRECTORY_SEPARATOR, '/', $theme->getStyleSheetFiles()))
		);
	}

	// ================================================================================
	// The theme cache
	// ================================================================================

	/**
	 * @return TMemoryCache the cache of a new application of this test.
	 */
	private function newCachingApplication(): TMemoryCache
	{
		$app = $this->newApplication();
		$app->setCache($cache = new TMemoryCache());
		return $cache;
	}

	/**
	 * @return string the cache key of the test theme.
	 */
	private function cacheKey(): string
	{
		return TTheme::THEME_CACHE_PREFIX . $this->_themePath;
	}

	/**
	 * @return array a skin list no skin file of this test holds.
	 */
	private function cachedSkins(): array
	{
		return [TLabel::class => [0 => ['text' => [TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE, TTemplate::PROP_NAME => 'Text', TTemplate::PROP_VALUE => 'Cached']]]];
	}

	public function testConstructCachesTheParsedTheme()
	{
		$cache = $this->newCachingApplication();
		$this->_testApp->setClock((new \Prado\Util\Clock\TMockClock())->setTime(5000));
		$this->writeThemeFile('label.skin', '<com:TLabel Text="Default" />');
		$this->writeThemeFile('a.css');

		$theme = new TTheme($this->_themePath, '/t');

		[$skins, $cssFiles, $jsFiles, $timestamp] = $cache->get($this->cacheKey());
		$this->assertSame($theme->getSkins(), $skins);
		$this->assertSame($theme->getStyleSheetFiles(), $cssFiles);
		$this->assertSame([], $jsFiles);
		$this->assertSame(5000, $timestamp);
	}

	public function testConstructUsesTheCachedSkinsWhileTheSkinFilesAreUnchanged()
	{
		$cache = $this->newCachingApplication();
		$skin = $this->writeThemeFile('label.skin', '<com:TLabel Text="Default" />');
		foreach (['a.css', 'a.js', 'readme.txt'] as $file) {
			$this->writeThemeFile($file);
		}
		touch($skin, 1000);
		$cache->set($this->cacheKey(), [$this->cachedSkins(), [], [], 2000]);

		$theme = new TTheme($this->_themePath, '/t');

		$this->assertSame($this->cachedSkins(), $theme->getSkins());
	}

	public function testConstructReparsesASkinFileNewerThanTheCache()
	{
		$cache = $this->newCachingApplication();
		$skin = $this->writeThemeFile('label.skin', '<com:TLabel Text="Default" />');
		touch($skin, 3000);
		$cache->set($this->cacheKey(), [$this->cachedSkins(), [], [], 2000]);

		$theme = new TTheme($this->_themePath, '/t');

		$label = new TLabel();
		$theme->applySkin($label);
		$this->assertSame('Default', $label->getText());
		$this->assertSame($theme->getSkins(), $cache->get($this->cacheKey())[0], 'the reparsed theme is cached again');
	}

	public function testConstructSkipsAClaimedFileWhileCheckingTheCache()
	{
		$cache = $this->newCachingApplication();
		$skin = $this->writeThemeFile('label.skin', '<com:TLabel Text="Default" />');
		touch($skin, 3000);
		$cache->set($this->cacheKey(), [$this->cachedSkins(), [], [], 2000]);
		TSkippingTheme::$skipped = ['label.skin'];

		$theme = new TSkippingTheme($this->_themePath, '/t');

		$this->assertSame($this->cachedSkins(), $theme->getSkins(), 'a claimed skin file does not invalidate the cache');
	}

	public function testConstructIgnoresACacheEntryThatIsNoArray()
	{
		$cache = $this->newCachingApplication();
		$this->writeThemeFile('label.skin', '<com:TLabel Text="Default" />');
		$cache->set($this->cacheKey(), 'corrupt');

		$theme = new TTheme($this->_themePath, '/t');

		$this->assertSame([0], array_keys($theme->getSkins()[TLabel::class]));
	}

	public function testConstructInPerformanceModeTrustsTheCacheWithoutReadingTheDirectory()
	{
		$cache = $this->newCachingApplication();
		$this->_testApp->setMode(TApplicationMode::Performance);
		$skin = $this->writeThemeFile('label.skin', '<com:TLabel Text="Default" />');
		touch($skin, 3000);
		$cache->set($this->cacheKey(), [$this->cachedSkins(), ['/t/cached.css'], ['/t/cached.js'], 2000]);

		$theme = new TTheme($this->_themePath, '/t');

		$this->assertSame($this->cachedSkins(), $theme->getSkins());
		$this->assertSame(['/t/cached.css'], $theme->getStyleSheetFiles());
		$this->assertSame(['/t/cached.js'], $theme->getJavaScriptFiles());
	}

	// ================================================================================
	// Protected setters and applySkin errors
	// ================================================================================

	public function testProtectedSetters()
	{
		$theme = new TTheme($this->_themePath, '/t');
		PradoUnit::invoke($theme, 'setName', 'other');
		PradoUnit::invoke($theme, 'setBaseUrl', '/u/');
		PradoUnit::invoke($theme, 'setBasePath', '/p');
		PradoUnit::invoke($theme, 'setSkins', ['k' => []]);
		PradoUnit::invoke($theme, 'setStyleSheetFiles', ['/u/a.css']);
		PradoUnit::invoke($theme, 'setJavaScriptFiles', ['/u/a.js']);
		$this->assertSame('other', $theme->getName());
		$this->assertSame('/u', $theme->getBaseUrl());
		$this->assertSame('/p', $theme->getBasePath());
		$this->assertSame(['k' => []], $theme->getSkins());
		$this->assertSame(['/u/a.css'], $theme->getStyleSheetFiles());
		$this->assertSame(['/u/a.js'], $theme->getJavaScriptFiles());
	}

	public function testApplySkin_ReadOnlyProperty_ThrowsException()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'clientid' => [TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE, TTemplate::PROP_NAME => 'ClientID', TTemplate::PROP_VALUE => 'x'],
		]);
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('read-only');
		$theme->applySkin(new TLabel());
	}

	public function testApplySkin_UndefinedProperty_ThrowsException()
	{
		$theme = $this->_createSkin(TLabel::class, 0, [
			'nosuchproperty' => [TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE, TTemplate::PROP_NAME => 'NoSuchProperty', TTemplate::PROP_VALUE => 'x'],
		]);
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('NoSuchProperty');
		$theme->applySkin(new TLabel());
	}
}
