<?php

use Prado\Prado;
use Prado\Web\UI\TThemeManager;
use Prado\Web\UI\TTheme;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;

class TThemeManagerTest extends PHPUnit\Framework\TestCase
{
	/** @var string[] temp directories created during tests */
	private array $_tmpDirs = [];
	/** @var string[] temp files created during tests */
	private array $_tmpFiles = [];

	protected function tearDown(): void
	{
		foreach ($this->_tmpFiles as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
		$this->_tmpFiles = [];
		foreach (array_reverse($this->_tmpDirs) as $dir) {
			if (is_dir($dir)) {
				@rmdir($dir);
			}
		}
		$this->_tmpDirs = [];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Create a temp directory and register it for cleanup.
	 */
	private function createTmpDir(string $prefix = 'prado_theme_'): string
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid($prefix, true);
		mkdir($dir, 0755, true);
		$this->_tmpDirs[] = $dir;
		return $dir;
	}

	/**
	 * Use reflection to set _basePath directly, bypassing namespace resolution
	 * and assertUninitialized() guard.
	 */
	private function setBasePath(TThemeManager $manager, string $path): void
	{
		PradoUnit::setProp($manager, '_basePath', $path);
	}

	/**
	 * Use reflection to read _basePath.
	 */
	private function getBasePathValue(TThemeManager $manager): ?string
	{
		return PradoUnit::getProp($manager, '_basePath');
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testDefaultBasepathConstant(): void
	{
		$this->assertEquals('themes', TThemeManager::DEFAULT_BASEPATH);
	}

	public function testDefaultThemeClassConstant(): void
	{
		$this->assertEquals(TTheme::class, TThemeManager::DEFAULT_THEMECLASS);
	}

	// -----------------------------------------------------------------------
	// init()
	// -----------------------------------------------------------------------

	public function testInitRegistersManagerWithApplication(): void
	{
		$manager = new TThemeManager();
		$manager->init(null);
		$this->assertSame($manager, Prado::getApplication()->getThemeManager());
	}

	public function testInitMarksManagerAsInitialized(): void
	{
		$manager = new TThemeManager();
		$manager->init(null);
		// After init, setBasePath must throw TInvalidOperationException
		$this->expectException(TInvalidOperationException::class);
		$manager->setBasePath('Prado.Web.UI');
	}

	// -----------------------------------------------------------------------
	// setBaseUrl / getBaseUrl
	// -----------------------------------------------------------------------

	public function testSetAndGetBaseUrl(): void
	{
		$manager = new TThemeManager();
		$manager->setBaseUrl('/themes');
		$this->assertEquals('/themes', $manager->getBaseUrl());
	}

	public function testSetBaseUrlTrimsTrailingSlash(): void
	{
		$manager = new TThemeManager();
		$manager->setBaseUrl('/my/themes/');
		$this->assertEquals('/my/themes', $manager->getBaseUrl());
	}

	public function testSetBaseUrlTrimsMultipleTrailingSlashes(): void
	{
		$manager = new TThemeManager();
		$manager->setBaseUrl('/themes///');
		$this->assertEquals('/themes', $manager->getBaseUrl());
	}

	public function testSetBaseUrlAllowsEmptyString(): void
	{
		$manager = new TThemeManager();
		$manager->setBaseUrl('');
		$this->assertEquals('', $manager->getBaseUrl());
	}

	public function testSetBaseUrlOverwritesPreviousValue(): void
	{
		$manager = new TThemeManager();
		$manager->setBaseUrl('/old');
		$manager->setBaseUrl('/new');
		$this->assertEquals('/new', $manager->getBaseUrl());
	}

	// -----------------------------------------------------------------------
	// setBasePath() — namespace resolution + uninitialized guard
	// -----------------------------------------------------------------------

	public function testSetBasePathWithValidNamespaceStoresResolvedPath(): void
	{
		$manager = new TThemeManager();
		// 'Prado.Web.UI' is a registered alias path that resolves to a real directory
		$manager->setBasePath('Prado.Web.UI');
		$stored = $this->getBasePathValue($manager);
		$this->assertNotNull($stored);
		$this->assertIsString($stored);
		$this->assertDirectoryExists($stored);
	}

	public function testSetBasePathWithNonExistentPathThrows(): void
	{
		$manager = new TThemeManager();
		$this->expectException(TInvalidDataValueException::class);
		$manager->setBasePath('SomeAlias.NonExistent.Path.Forever');
	}

	public function testSetBasePathAfterInitThrowsInvalidOperationException(): void
	{
		$manager = new TThemeManager();
		$manager->init(null);
		$this->expectException(TInvalidOperationException::class);
		$manager->setBasePath('Prado.Web.UI');
	}

	// -----------------------------------------------------------------------
	// ThemeClass
	// -----------------------------------------------------------------------

	public function testGetThemeClassDefaultIsTTheme(): void
	{
		$manager = new TThemeManager();
		$this->assertEquals(TTheme::class, $manager->getThemeClass());
	}

	public function testSetAndGetThemeClass(): void
	{
		$manager = new TThemeManager();
		$manager->setThemeClass(TTheme::class);
		$this->assertEquals(TTheme::class, $manager->getThemeClass());
	}

	public function testSetThemeClassToNullRestoresDefault(): void
	{
		$manager = new TThemeManager();
		$manager->setThemeClass(null);
		$this->assertEquals(TTheme::class, $manager->getThemeClass());
	}

	// -----------------------------------------------------------------------
	// getAvailableThemes()
	// -----------------------------------------------------------------------

	public function testGetAvailableThemesReturnsArrayWithCreatedThemeDirs(): void
	{
		$manager = new TThemeManager();
		$basePath = $this->createTmpDir('prado_themes_');

		// Create theme subdirectories
		$themeA = $basePath . DIRECTORY_SEPARATOR . 'ThemeAlpha';
		$themeB = $basePath . DIRECTORY_SEPARATOR . 'ThemeBeta';
		mkdir($themeA);
		mkdir($themeB);
		$this->_tmpDirs[] = $themeA;
		$this->_tmpDirs[] = $themeB;

		$this->setBasePath($manager, $basePath);
		$themes = $manager->getAvailableThemes();

		$this->assertIsArray($themes);
		$this->assertContains('ThemeAlpha', $themes);
		$this->assertContains('ThemeBeta', $themes);
	}

	public function testGetAvailableThemesReturnsEmptyArrayForEmptyDirectory(): void
	{
		$manager = new TThemeManager();
		$basePath = $this->createTmpDir('prado_themes_empty_');
		$this->setBasePath($manager, $basePath);

		$themes = $manager->getAvailableThemes();
		$this->assertIsArray($themes);
		$this->assertEmpty($themes);
	}

	public function testGetAvailableThemesIgnoresDotAndDotDot(): void
	{
		$manager = new TThemeManager();
		$basePath = $this->createTmpDir('prado_themes_dots_');
		$this->setBasePath($manager, $basePath);

		$themes = $manager->getAvailableThemes();
		$this->assertNotContains('.', $themes);
		$this->assertNotContains('..', $themes);
	}

	public function testGetAvailableThemesDoesNotIncludeFiles(): void
	{
		$manager = new TThemeManager();
		$basePath = $this->createTmpDir('prado_themes_files_');
		$filePath = $basePath . DIRECTORY_SEPARATOR . 'notATheme.txt';
		file_put_contents($filePath, 'irrelevant');
		$this->_tmpFiles[] = $filePath;

		$themeDir = $basePath . DIRECTORY_SEPARATOR . 'RealTheme';
		mkdir($themeDir);
		$this->_tmpDirs[] = $themeDir;

		$this->setBasePath($manager, $basePath);
		$themes = $manager->getAvailableThemes();

		$this->assertNotContains('notATheme.txt', $themes);
		$this->assertContains('RealTheme', $themes);
	}

	// -----------------------------------------------------------------------
	// getTheme()
	// -----------------------------------------------------------------------

	public function testGetThemeReturnsTThemeInstance(): void
	{
		$manager = new TThemeManager();
		$basePath = $this->createTmpDir('prado_themes_get_');

		$themeDir = $basePath . DIRECTORY_SEPARATOR . 'MyTheme';
		mkdir($themeDir);
		$this->_tmpDirs[] = $themeDir;

		$this->setBasePath($manager, $basePath);
		$manager->setBaseUrl('/themes');

		$theme = $manager->getTheme('MyTheme');
		$this->assertInstanceOf(TTheme::class, $theme);
	}

	public function testGetThemeThemeUrlContainsThemeName(): void
	{
		$manager = new TThemeManager();
		$basePath = $this->createTmpDir('prado_themes_url_');

		$themeDir = $basePath . DIRECTORY_SEPARATOR . 'UrlTheme';
		mkdir($themeDir);
		$this->_tmpDirs[] = $themeDir;

		$this->setBasePath($manager, $basePath);
		$manager->setBaseUrl('/base');

		$theme = $manager->getTheme('UrlTheme');
		$this->assertInstanceOf(TTheme::class, $theme);
		// Theme URL should contain the theme name
		$this->assertStringContainsString('UrlTheme', $theme->getBaseUrl());
	}

	public function testGetThemeBaseUrlUsesSetBaseUrl(): void
	{
		$manager = new TThemeManager();
		$basePath = $this->createTmpDir('prado_themes_baseurl_');

		$themeDir = $basePath . DIRECTORY_SEPARATOR . 'ATheme';
		mkdir($themeDir);
		$this->_tmpDirs[] = $themeDir;

		$this->setBasePath($manager, $basePath);
		$manager->setBaseUrl('/custom/themes');

		$theme = $manager->getTheme('ATheme');
		$this->assertStringStartsWith('/custom/themes', $theme->getBaseUrl());
	}
}
