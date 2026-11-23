<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
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
use Prado\Web\UI\WebControls\THtmlArea5;
use Prado\Web\UI\WebControls\TPanel;

class THtmlArea5Test extends TestCase
{
	use TAssetManagerMockTrait;
	use TTempDirectoryTrait;
	use TWebControlRenderTrait;

	private array $_savedStatics;
	private mixed $_savedGlobalization;
	private string $_savedMode;

	protected function setUp(): void
	{
		parent::setUp();
		$this->_savedStatics = PradoUnit::snapshotStatic(THtmlArea5::class);
		$app = Prado::getApplication();
		$this->_savedGlobalization = PradoUnit::getProp($app, '_globalization');
		$this->_savedMode = $app->getMode();
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(THtmlArea5::class, $this->_savedStatics);
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
			->onlyMethods(['registerPradoScript', 'registerEndScript', 'getPradoScriptAssetPath', 'registerPostBackControl'])->getMock();
		$cs->method('getPradoScriptAssetPath')->willReturn('/assets/tinymce');
		PradoUnit::setProp($page, '_clientScript', $cs);
		$area = new THtmlArea5();
		$area->setID('editor');
		if ($parent !== null) {
			$page->getControls()->add($parent);
			$parent->getControls()->add($area);
		} else {
			$page->getControls()->add($area);
		}
		return [$area, $cs];
	}

	public function testPropertyDefaultsAndSetters()
	{
		$area = new THtmlArea5();
		$this->assertSame('600px', $area->getWidth());
		$this->assertSame('250px', $area->getHeight());
		$this->assertSame('MultiLine', $area->getTextMode());
		$this->assertTrue($area->getEnableVisualEdit());
		$this->assertSame('', $area->getCulture());
		$this->assertSame('', $area->getOptions());
		$this->assertNull($area->getCustomPluginPath());
		$this->assertSame('Prado.WebUI.THtmlArea5', PradoUnit::invoke($area, 'getClientClassName'));
		$area->setEnableVisualEdit('false');
		$area->setCulture('fr');
		$area->setOptions('plugins: "code"');
		$area->setCustomPluginPath('/plugins/mine');
		$this->assertFalse($area->getEnableVisualEdit());
		$this->assertSame('fr', $area->getCulture());
		$this->assertSame('plugins: "code"', $area->getOptions());
		$this->assertSame('/plugins/mine', $area->getCustomPluginPath());
	}

	public function testTextModeIsReadOnly()
	{
		$this->expectException(TInvalidOperationException::class);
		(new THtmlArea5())->setTextMode('SingleLine');
	}

	public function testAutoPostBackOnlyWithoutVisualEdit()
	{
		$area = new THtmlArea5();
		$area->setAutoPostBack(true);
		$this->assertFalse($area->getAutoPostBack());
		$area->setEnableVisualEdit(false);
		$this->assertTrue($area->getAutoPostBack());
	}

	public function testAvailableListsComeFromTheTinyMcePackages()
	{
		PradoUnit::setStaticProp(THtmlArea5::class, '_langs', null);
		PradoUnit::setStaticProp(THtmlArea5::class, '_plugins', null);
		PradoUnit::setStaticProp(THtmlArea5::class, '_themes', null);
		$area = new THtmlArea5();
		$this->assertContains('fr_FR', $area->getAvailableLanguages());
		$this->assertNotContains('.', $area->getAvailableLanguages());
		$this->assertContains('code', $area->getAvailablePlugins());
		$this->assertContains('silver', $area->getAvailableThemes());
		$this->assertSame($area->getAvailablePlugins(), (new THtmlArea5())->getAvailablePlugins());
	}

	public function testScanAssetNamesSkipsDotsAndListsNothingForAMissingFolder()
	{
		$dir = $this->makeTempDirectory();
		touch($dir . DIRECTORY_SEPARATOR . 'a.js');
		mkdir($dir . DIRECTORY_SEPARATOR . 'b');
		$area = new THtmlArea5();
		$aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		try {
			Prado::setPathOfAlias('THtmlArea5TestScan', $dir);
			$names = PradoUnit::invoke($area, 'scanAssetNames', 'THtmlArea5TestScan');
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_aliases', $aliases);
		}
		sort($names);
		$this->assertSame(['a.js', 'b'], $names);
		$this->assertSame([], PradoUnit::invoke($area, 'scanAssetNames', 'Vendor\\prado-test-missing-folder'));
		$this->assertSame([], PradoUnit::invoke($area, 'scanAssetNames', 'NoSuchAlias\\langs'));
	}

	public function testLanguagesSkipShortAndNonScriptNames()
	{
		$area = $this->getMockBuilder(THtmlArea5::class)->onlyMethods(['scanAssetNames'])->getMock();
		$area->method('scanAssetNames')->willReturn(['de.js', '.js', 'readme.md', 'fr_FR.js']);
		PradoUnit::setStaticProp(THtmlArea5::class, '_langs', null);
		$this->assertSame(['de', 'fr_FR'], $area->getAvailableLanguages());
	}

	public static function languageProvider(): array
	{
		return [
			'variant found' => ['fr_FR', ['fr_FR', 'fr'], 'fr_FR'],
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
		PradoUnit::setStaticProp(THtmlArea5::class, '_langs', ['de', 'fr_FR']);
		$this->assertSame($expected, PradoUnit::invoke(new THtmlArea5(), 'getLanguageSuffix', ''));
	}

	public function testParseEditorOptions()
	{
		$area = new THtmlArea5();
		$this->assertSame([
			'plugins' => 'code table',
			'branding' => false,
			'paste_as_text' => true,
			'url' => 'http://x',
		], PradoUnit::invoke($area, 'parseEditorOptions', "plugins: 'code table',\n branding : False\npaste_as_text: \"TRUE\"\nnovalue\nurl: http://x"));
		$this->assertSame([], PradoUnit::invoke($area, 'parseEditorOptions', ''));
	}

	public function testEditorOptions()
	{
		$this->useGlobalization('en', ['en']);
		[$area] = $this->createArea();
		$area->setOptions('menubar: true');
		$options = PradoUnit::invoke($area, 'getEditorOptions');
		$this->assertSame('#editor', $options['selector']);
		$this->assertSame('en', $options['language']);
		$this->assertSame('silver', $options['theme']);
		$this->assertSame('600px', $options['width']);
		$this->assertSame('250px', $options['height']);
		$this->assertTrue($options['menubar']);
		$this->assertArrayNotHasKey('readonly', $options);
	}

	public function testReadOnlyEditorOptions()
	{
		$this->useGlobalization('en', ['en']);
		[$area] = $this->createArea();
		$area->setReadOnly(true);
		$options = PradoUnit::invoke($area, 'getEditorOptions');
		$this->assertTrue($options['readonly']);
		$this->assertFalse($options['toolbar']);
		$this->assertFalse($options['menubar']);
		$this->assertFalse($options['statusbar']);
	}

	public function testRenderRegistersTheEditorAndCopiesTheLanguages()
	{
		$this->useGlobalization('en', ['en']);
		$assets = $this->mockAssetManager(['copyDirectory']);
		$assets->expects($this->once())->method('copyDirectory')->with(Prado::getPathOfNameSpace('Vendor\\pradosoft\\tinymce-langs\\langs'), '/assets/tinymce/langs');
		[$area, $cs] = $this->createArea();
		$area->setEnableClientScript(false);
		$cs->expects($this->once())->method('registerPradoScript')->with('htmlarea5');
		$cs->expects($this->once())->method('registerEndScript')->with('prado:THtmlArea5editor', $this->stringStartsWith('new Prado.WebUI.THtmlArea5({'));
		$this->assertStringStartsWith('<textarea id="editor" name="editor"', $this->render($area));
	}

	public function testRenderCopiesTheCustomPlugins()
	{
		$this->useGlobalization('en', ['en']);
		$plugin = $this->makeTempDirectory() . DIRECTORY_SEPARATOR . 'mine';
		mkdir($plugin);
		$assets = $this->mockAssetManager(['copyDirectory']);
		$copies = [];
		$assets->method('copyDirectory')->willReturnCallback(function ($from, $to) use (&$copies) {
			$copies[] = $to;
		});
		[$area] = $this->createArea();
		$area->setEnableClientScript(false);
		$area->setCustomPluginPath($plugin);
		$this->render($area);
		$this->assertSame(['/assets/tinymce/langs', '/assets/tinymce/plugins/mine'], $copies);
	}

	public function testPerformanceModeSkipsAnExistingCopy()
	{
		$this->useGlobalization('en', ['en']);
		$base = $this->makeTempDirectory();
		mkdir($base . '/langs');
		mkdir($base . '/plugins/mine', 0o777, true);
		Prado::getApplication()->setMode(TApplicationMode::Performance);
		$assets = $this->mockAssetManager(['copyDirectory']);
		$assets->expects($this->never())->method('copyDirectory');
		[$area] = $this->createArea();
		$fresh = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$area->getPage()])
			->onlyMethods(['registerPradoScript', 'registerEndScript', 'getPradoScriptAssetPath'])->getMock();
		$fresh->method('getPradoScriptAssetPath')->willReturn($base);
		PradoUnit::setProp($area->getPage(), '_clientScript', $fresh);
		$area->setCustomPluginPath('Vendor\\mine');
		PradoUnit::invoke($area, 'loadJavascriptLibrary');
	}

	public function testRenderWithoutVisualEditIsATextArea()
	{
		[$area, $cs] = $this->createArea();
		$area->setEnableVisualEdit(false);
		$area->setEnableClientScript(false);
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
		$this->assertSame($expected, PradoUnit::invoke(new THtmlArea5(), 'parseEditorOptions', $text));
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
			mkdir($base . '/plugins/mine', 0o777, true);
		}
		$aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		Prado::setPathOfAlias('THtmlArea5TestPlugins', dirname($source));
		if ($performance) {
			Prado::getApplication()->setMode(TApplicationMode::Performance);
		}
		try {
			$assets = $this->mockAssetManager(['copyDirectory']);
			$assets->expects($copies ? $this->once() : $this->never())->method('copyDirectory')->with($source, $base . '/plugins/mine');
			[$area] = $this->createArea();
			$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$area->getPage()])->onlyMethods(['getPradoScriptAssetPath'])->getMock();
			$cs->method('getPradoScriptAssetPath')->willReturn($base);
			PradoUnit::setProp($area->getPage(), '_clientScript', $cs);
			$area->setCustomPluginPath($folder ? $source : 'THtmlArea5TestPlugins\\mine');
			PradoUnit::invoke($area, 'copyCustomPlugins');
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_aliases', $aliases);
		}
	}

	public function testCopyLangsWhenTheyExistOutsidePerformanceMode()
	{
		$base = $this->makeTempDirectory();
		mkdir($base . '/langs');
		$this->mockAssetManager(['copyDirectory'])->expects($this->once())->method('copyDirectory');
		[$area] = $this->createArea();
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$area->getPage()])->onlyMethods(['getPradoScriptAssetPath'])->getMock();
		$cs->method('getPradoScriptAssetPath')->willReturn($base);
		PradoUnit::setProp($area->getPage(), '_clientScript', $cs);
		PradoUnit::invoke($area, 'copyLangs');
	}

	public static function oneLanguageFileProvider(): array
	{
		return [
			'script' => [['de.js'], ['de']],
			'short' => [['.js'], []],
			'other' => [['readme'], []],
			'none' => [[], []],
		];
	}

	/**
	 * @dataProvider oneLanguageFileProvider
	 */
	public function testLanguagesFromOneFile(array $files, array $expected)
	{
		$area = $this->getMockBuilder(THtmlArea5::class)->onlyMethods(['scanAssetNames'])->getMock();
		$area->method('scanAssetNames')->willReturn($files);
		PradoUnit::setStaticProp(THtmlArea5::class, '_langs', null);
		$this->assertSame($expected, $area->getAvailableLanguages());
		$this->assertSame($expected, $area->getAvailableLanguages());
	}

	public function testThemesAreLoadedOnce()
	{
		$area = $this->getMockBuilder(THtmlArea5::class)->onlyMethods(['scanAssetNames'])->getMock();
		$area->expects($this->once())->method('scanAssetNames')->willReturn(['silver']);
		PradoUnit::setStaticProp(THtmlArea5::class, '_themes', null);
		$this->assertSame(['silver'], $area->getAvailableThemes());
		$this->assertSame(['silver'], $area->getAvailableThemes());
	}
}
