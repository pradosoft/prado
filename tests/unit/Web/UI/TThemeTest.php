<?php

use Prado\Web\UI\TTheme;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\TTemplate;
use Prado\TApplication;
use Prado\Prado;

class TThemeTest extends PHPUnit\Framework\TestCase
{
	private $_themePath;
	private $_app;

	protected function setUp(): void
	{
		$this->_themePath = sys_get_temp_dir() . '/testtheme';
		if (!is_dir($this->_themePath)) {
			mkdir($this->_themePath, 0755, true);
		}
		$this->_app = Prado::getApplication();
	}

	protected function tearDown(): void
	{
		$this->_themePath = null;
		$this->_app = null;
	}

	private function _createSkin($controlClass, $skinId, array $properties)
	{
		$skinId = $skinId ?? 0;
		$ref = new ReflectionClass(TTheme::class);
		$theme = $ref->newInstanceWithoutConstructor();
		
		$skinsProp = $ref->getProperty('_skins');
		$skinsProp->setAccessible(true);
		
		$skins = [];
		if (!isset($skins[$controlClass])) {
			$skins[$controlClass] = [];
		}
		if (!isset($skins[$controlClass][$skinId])) {
			$skins[$controlClass][$skinId] = [];
		}
		$skins[$controlClass][$skinId] = array_merge($skins[$controlClass][$skinId], $properties);
		$skinsProp->setValue($theme, $skins);
		
		$nameProp = $ref->getProperty('_name');
		$nameProp->setAccessible(true);
		$nameProp->setValue($theme, 'testtheme');
		
		$urlProp = $ref->getProperty('_themeUrl');
		$urlProp->setAccessible(true);
		$urlProp->setValue($theme, '/themes/testtheme');
		
		$pathProp = $ref->getProperty('_themePath');
		$pathProp->setAccessible(true);
		$pathProp->setValue($theme, $this->_themePath);
		
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
		$ref = new ReflectionClass(TTheme::class);
		$theme = $ref->newInstanceWithoutConstructor();
		
		$skinsProp = $ref->getProperty('_skins');
		$skinsProp->setAccessible(true);
		$skinsProp->setValue($theme, []);
		
		$nameProp = $ref->getProperty('_name');
		$nameProp->setAccessible(true);
		$nameProp->setValue($theme, 'testtheme');
		
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
		$ref = new ReflectionClass(TTheme::class);
		$theme = $ref->newInstanceWithoutConstructor();
		
		$skinsProp = $ref->getProperty('_skins');
		$skinsProp->setAccessible(true);
		$skinsProp->setValue($theme, [
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
		
		$nameProp = $ref->getProperty('_name');
		$nameProp->setAccessible(true);
		$nameProp->setValue($theme, 'testtheme');
		
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
}