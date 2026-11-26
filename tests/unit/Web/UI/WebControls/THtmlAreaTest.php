<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THtmlArea;
use Prado\Web\UI\WebControls\TPanel;

class THtmlAreaTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TTempDirectoryTrait;
	use TWebControlRenderTrait;

	private mixed $_savedGlobalization;
	private string $_savedMode;

	protected function setUp(): void
	{
		parent::setUp();
		$app = Prado::getApplication();
		$this->_savedGlobalization = PradoUnit::getProp($app, '_globalization');
		$this->_savedMode = $app->getMode();
		$this->useGlobalization('en', ['en']);
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		PradoUnit::setProp($app, '_globalization', $this->_savedGlobalization);
		$app->setMode($this->_savedMode);
		$this->restoreAssetManager();
		$this->removeTempDirectories();
		parent::tearDown();
	}

	private function useGlobalization(string $culture, array $variants): void
	{
		$globalization = $this->getMockBuilder(TGlobalization::class)->onlyMethods(['getCulture', 'getCultureVariants'])->getMock();
		$globalization->method('getCulture')->willReturn($culture);
		$globalization->method('getCultureVariants')->willReturn($variants);
		PradoUnit::setProp(Prado::getApplication(), '_globalization', $globalization);
	}

	private function createArea(?TPanel $parent = null): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])
			->onlyMethods(['registerPradoScript', 'registerScriptFile', 'registerEndScript', 'registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$area = new THtmlArea();
		$area->setID('editor');
		$area->setEnableClientScript(false);
		if ($parent !== null) {
			$page->getControls()->add($parent);
			$parent->getControls()->add($area);
		} else {
			$page->getControls()->add($area);
		}
		return [$area, $cs];
	}

	private function mockTarAssets(): \Prado\Web\TAssetManager
	{
		$assets = $this->mockAssetManager(['publishTarFile', 'copyDirectory', 'getBasePath']);
		$assets->method('publishTarFile')->willReturn('/assets/abc123');
		$assets->method('getBasePath')->willReturn('/srv/assets');
		return $assets;
	}

	public function testPropertyDefaultsAndSetters()
	{
		$area = new THtmlArea();
		$this->assertSame('470px', $area->getWidth());
		$this->assertSame('250px', $area->getHeight());
		$this->assertSame('MultiLine', $area->getTextMode());
		$this->assertTrue($area->getEnableVisualEdit());
		$this->assertSame('', $area->getCulture());
		$this->assertSame('', $area->getOptions());
		$this->assertNull($area->getCustomPluginPath());
		$this->assertContains('table', $area->getAvailablePlugins());
		$this->assertContains('advanced', $area->getAvailableThemes());
		$this->assertSame('Prado.WebUI.THtmlArea', PradoUnit::invoke($area, 'getClientClassName'));
		$area->setEnableVisualEdit('false');
		$area->setCulture('fr');
		$area->setOptions('a: b');
		$area->setCustomPluginPath('/plugins/mine');
		$this->assertFalse($area->getEnableVisualEdit());
		$this->assertSame('fr', $area->getCulture());
		$this->assertSame('a: b', $area->getOptions());
		$this->assertSame('/plugins/mine', $area->getCustomPluginPath());
	}

	public function testTextModeIsReadOnly()
	{
		$this->expectException(TInvalidOperationException::class);
		(new THtmlArea())->setTextMode('SingleLine');
	}

	public function testAutoPostBackOnlyWithoutVisualEdit()
	{
		$area = new THtmlArea();
		$area->setAutoPostBack(true);
		$this->assertFalse($area->getAutoPostBack());
		$area->setEnableVisualEdit(false);
		$this->assertTrue($area->getAutoPostBack());
	}

	public static function languageProvider(): array
	{
		return [
			'mapped variant' => ['zh_CN', ['zh_CN', 'zh'], 'zh-cn'],
			'second variant' => ['de_AT', ['de_AT', 'de'], 'de'],
			'no variant' => ['xx', ['xx'], 'en'],
		];
	}

	/**
	 * @dataProvider languageProvider
	 */
	public function testLanguageSuffixFollowsTheCultureVariants(string $culture, array $variants, string $expected)
	{
		$this->useGlobalization($culture, $variants);
		$this->assertSame($expected, PradoUnit::invoke(new THtmlArea(), 'getLanguageSuffix', ''));
	}

	public function testParseEditorOptions()
	{
		$this->assertSame(['plugins' => 'table', 'relative_urls' => false, 'x' => true],
			PradoUnit::invoke(new THtmlArea(), 'parseEditorOptions', "plugins: 'table',\nrelative_urls: false\nx: true\nbare"));
	}

	public function testEditorOptions()
	{
		[$area] = $this->createArea();
		$area->setOptions('theme: simple');
		$options = PradoUnit::invoke($area, 'getEditorOptions');
		$this->assertSame('exact', $options['mode']);
		$this->assertSame('editor', $options['elements']);
		$this->assertSame('en', $options['language']);
		$this->assertSame('simple', $options['theme']);
		$this->assertArrayNotHasKey('readonly', $options);
		$this->assertDoesNotMatchRegularExpression('/\bon[a-z]+\b/', $options['extended_valid_elements']);
		$area->setReadOnly(true);
		$this->assertTrue(PradoUnit::invoke($area, 'getEditorOptions')['readonly']);
	}

	public function testRenderPublishesTheEditorAndRegistersIt()
	{
		$this->mockTarAssets()->expects($this->never())->method('copyDirectory');
		[$area, $cs] = $this->createArea();
		$cs->expects($this->once())->method('registerPradoScript')->with('htmlarea');
		$cs->expects($this->once())->method('registerScriptFile')->with('prado:THtmlArea', '/assets/abc123/tiny_mce/tiny_mce.js');
		$cs->expects($this->once())->method('registerEndScript')->with('prado:THtmlAreaeditor', $this->stringStartsWith('new Prado.WebUI.THtmlArea({'));
		$this->assertStringStartsWith('<textarea id="editor" name="editor"', $this->render($area));
	}

	public function testRenderCopiesTheCustomPlugins()
	{
		$plugin = $this->makeTempDirectory() . DIRECTORY_SEPARATOR . 'mine';
		mkdir($plugin);
		$this->mockTarAssets()->expects($this->once())->method('copyDirectory')->with($plugin, '/srv/assets/abc123/tiny_mce/plugins/mine');
		[$area] = $this->createArea();
		$area->setCustomPluginPath($plugin);
		$this->render($area);
	}

	public function testPerformanceModeSkipsAnExistingPluginCopy()
	{
		$base = $this->makeTempDirectory();
		mkdir($base . '/abc123/tiny_mce/plugins/mine', 0o777, true);
		Prado::getApplication()->setMode(TApplicationMode::Performance);
		$assets = $this->mockAssetManager(['publishTarFile', 'copyDirectory', 'getBasePath']);
		$assets->method('publishTarFile')->willReturn('/assets/abc123');
		$assets->method('getBasePath')->willReturn($base);
		$assets->expects($this->never())->method('copyDirectory');
		[$area] = $this->createArea();
		$area->setCustomPluginPath('Vendor\\mine');
		PradoUnit::invoke($area, 'getScriptDeploymentPath');
	}

	public function testMissingTarFileThrows()
	{
		$aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		$map = $aliases;
		unset($map['Vendor']);
		PradoUnit::setStaticProp(Prado::class, '_aliases', $map);
		try {
			PradoUnit::invoke(new THtmlArea(), 'getScriptDeploymentPath');
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('htmlarea_tarfile_invalid', $e->getErrorCode());
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_aliases', $aliases);
		}
	}

	public function testRenderWithoutVisualEditIsATextArea()
	{
		[$area, $cs] = $this->createArea();
		$area->setEnableVisualEdit(false);
		$cs->expects($this->never())->method('registerEndScript');
		$this->assertStringStartsWith('<textarea name="editor"', $this->render($area));
	}

	public function testRenderWhenDisabledRegistersNoEditor()
	{
		$panel = new TPanel();
		$panel->setEnabled(false);
		[$area, $cs] = $this->createArea($panel);
		$cs->expects($this->never())->method('registerEndScript');
		$this->render($area);
	}

	public static function oneOptionProvider(): array
	{
		return [
			'false' => ['a: false', ['a' => false]],
			'true' => ['a: true', ['a' => true]],
			'text' => ['a: "b"', ['a' => 'b']],
			'no colon' => ['a', []],
		];
	}

	/**
	 * @dataProvider oneOptionProvider
	 */
	public function testParseOneEditorOption(string $text, array $expected)
	{
		$this->assertSame($expected, PradoUnit::invoke(new THtmlArea(), 'parseEditorOptions', $text));
	}

	public static function pluginCopyProvider(): array
	{
		// plugin given as a folder (else a namespace), destination exists, performance mode => copies
		return [
			'folder new' => [true, false, false, true],
			'folder exists' => [true, true, false, true],
			'folder exists performance' => [true, true, true, false],
			'namespace new' => [false, false, false, true],
			'namespace exists' => [false, true, false, true],
			'namespace exists performance' => [false, true, true, false],
		];
	}

	/**
	 * @dataProvider pluginCopyProvider
	 */
	public function testCopyCustomPlugins(bool $folder, bool $exists, bool $performance, bool $copies)
	{
		$base = $this->makeTempDirectory();
		$source = $this->makeTempDirectory() . DIRECTORY_SEPARATOR . 'mine';
		mkdir($source);
		if ($exists) {
			mkdir($base . '/abc123/tiny_mce/plugins/mine', 0o777, true);
		}
		$aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		Prado::setPathOfAlias('THtmlAreaTestPlugins', dirname($source));
		if ($performance) {
			Prado::getApplication()->setMode(TApplicationMode::Performance);
		}
		try {
			$assets = $this->mockAssetManager(['copyDirectory', 'getBasePath']);
			$assets->method('getBasePath')->willReturn($base);
			$assets->expects($copies ? $this->once() : $this->never())->method('copyDirectory')->with($source, $base . '/abc123/tiny_mce/plugins/mine');
			$area = new THtmlArea();
			$area->setCustomPluginPath($folder ? $source : 'THtmlAreaTestPlugins\\mine');
			PradoUnit::invoke($area, 'copyCustomPlugins', '/assets/abc123');
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_aliases', $aliases);
		}
	}

	public function testCopyCustomPluginsWithoutAPluginPath()
	{
		$this->mockAssetManager(['copyDirectory'])->expects($this->never())->method('copyDirectory');
		PradoUnit::invoke(new THtmlArea(), 'copyCustomPlugins', '/assets/abc123');
	}
}
