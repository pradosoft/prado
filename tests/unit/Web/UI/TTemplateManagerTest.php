<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TTemplateManager;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TSkinTemplate;
use Prado\Web\UI\ITemplate;

class TTemplateManagerTest extends \PHPUnit\Framework\TestCase
{
	use TTempDirectoryTrait;

	/** @var string[] temp .tpl files created during tests */
	private array $_tmpFiles = [];

	/** @var ?array the application properties a test changes */
	private ?array $_appSnapshot = null;

	protected function tearDown(): void
	{
		if ($this->_appSnapshot !== null) {
			PradoUnit::restore(Prado::getApplication(), $this->_appSnapshot);
			$this->_appSnapshot = null;
		}
		$this->removeTempDirectories();
		foreach ($this->_tmpFiles as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
		$this->_tmpFiles = [];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Create a temporary .tpl file with the given content and return its path.
	 */
	private function createTplFile(string $content = '<com:TLabel Text="test" />'): string
	{
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('prado_tpl_', true) . '.tpl';
		file_put_contents($file, $content);
		$this->_tmpFiles[] = $file;
		return $file;
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testTemplateFileExtConstant(): void
	{
		$this->assertEquals('.tpl', TTemplateManager::TEMPLATE_FILE_EXT);
	}

	public function testTemplateCachePrefixConstant(): void
	{
		$this->assertEquals('prado:template:', TTemplateManager::TEMPLATE_CACHE_PREFIX);
	}

	// -----------------------------------------------------------------------
	// init()
	// -----------------------------------------------------------------------

	public function testInitRegistersManagerWithApplication(): void
	{
		$manager = new TTemplateManager();
		$manager->init(null);
		// After init, the application should have this manager registered
		$this->assertSame($manager, Prado::getApplication()->getTemplateManager());
	}

	// -----------------------------------------------------------------------
	// DefaultTemplateClass
	// -----------------------------------------------------------------------

	public function testGetDefaultTemplateClassDefaultIsTTemplate(): void
	{
		$manager = new TTemplateManager();
		$this->assertEquals(TTemplate::class, $manager->getDefaultTemplateClass());
	}

	public function testSetDefaultTemplateClassToTSkinTemplate(): void
	{
		$manager = new TTemplateManager();
		$manager->setDefaultTemplateClass(TSkinTemplate::class);
		$this->assertEquals(TSkinTemplate::class, $manager->getDefaultTemplateClass());
	}

	public function testSetDefaultTemplateClassToTTemplate(): void
	{
		$manager = new TTemplateManager();
		$manager->setDefaultTemplateClass(TTemplate::class);
		$this->assertEquals(TTemplate::class, $manager->getDefaultTemplateClass());
	}

	// -----------------------------------------------------------------------
	// getTemplateByFileName() — no cache (application has no cache module)
	// -----------------------------------------------------------------------

	public function testGetTemplateByFileNameReturnsNullForNonExistentFile(): void
	{
		$manager = new TTemplateManager();
		$result = $manager->getTemplateByFileName('/non/existent/file.tpl');
		$this->assertNull($result);
	}

	public function testGetTemplateByFileNameReturnsTTemplateForExistingFile(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('<com:TLabel Text="hello" />');
		$result = $manager->getTemplateByFileName($file);
		$this->assertInstanceOf(TTemplate::class, $result);
	}

	public function testGetTemplateByFileNameReturnsITemplateInstance(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('Just some content');
		$result = $manager->getTemplateByFileName($file);
		$this->assertInstanceOf(ITemplate::class, $result);
	}

	public function testGetTemplateByFileNameContextPathMatchesFileDirectory(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		$result = $manager->getTemplateByFileName($file);
		$this->assertNotNull($result);
		$this->assertEquals(dirname($file), $result->getContextPath());
	}

	public function testGetTemplateByFileNameWithExplicitTplClass(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		$result = $manager->getTemplateByFileName($file, TSkinTemplate::class);
		$this->assertInstanceOf(TSkinTemplate::class, $result);
	}

	public function testGetTemplateByFileNameReturnsNullForNonITemplateClass(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		// stdClass does not implement ITemplate
		$result = $manager->getTemplateByFileName($file, 'stdClass');
		$this->assertNull($result);
	}

	public function testGetTemplateByFileNameReturnsNullForNonSubclassOfITemplate(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		// A random non-ITemplate class
		$result = $manager->getTemplateByFileName($file, \Prado\TComponent::class);
		$this->assertNull($result);
	}

	public function testGetTemplateByFileNameUsesDefaultTemplateClassWhenNullPassed(): void
	{
		$manager = new TTemplateManager();
		$manager->setDefaultTemplateClass(TSkinTemplate::class);
		$file = $this->createTplFile('content');
		$result = $manager->getTemplateByFileName($file, null);
		$this->assertInstanceOf(TSkinTemplate::class, $result);
	}

	// -----------------------------------------------------------------------
	// getTemplateByClassName()
	// -----------------------------------------------------------------------

	public function testGetTemplateByClassNameReturnsNullWhenNoTplFileExists(): void
	{
		$manager = new TTemplateManager();
		// TTemplateManager itself has no .tpl file alongside it
		$result = $manager->getTemplateByClassName(TTemplateManager::class);
		$this->assertNull($result);
	}

	public function testGetTemplateByClassNameReturnsNullForClassWithoutTplFile(): void
	{
		$manager = new TTemplateManager();
		// Standard PHP class with no associated .tpl
		$result = $manager->getTemplateByClassName(\Prado\TComponent::class);
		$this->assertNull($result);
	}

	// -----------------------------------------------------------------------
	// getTemplateByFileName() — Prado3 dot-notation class name resolution
	// -----------------------------------------------------------------------

	public function testGetTemplateByFileNameWithPrado3SystemDotNotation(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		// System.Web.UI.TSkinTemplate resolves to Prado\Web\UI\TSkinTemplate via prado3NamespaceToPhpNamespace
		$result = $manager->getTemplateByFileName($file, 'System.Web.UI.TSkinTemplate');
		$this->assertInstanceOf(TSkinTemplate::class, $result);
	}

	public function testGetTemplateByFileNameWithPrado3PradoDotNotation(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		// Prado.Web.UI.TSkinTemplate resolves to Prado\Web\UI\TSkinTemplate
		$result = $manager->getTemplateByFileName($file, 'Prado.Web.UI.TSkinTemplate');
		$this->assertInstanceOf(TSkinTemplate::class, $result);
	}

	public function testGetTemplateByFileNameWithPrado3DotNotationDefaultClass(): void
	{
		$manager = new TTemplateManager();
		// Set default template class using Prado3 dot-notation
		$manager->setDefaultTemplateClass('System.Web.UI.TSkinTemplate');
		$file = $this->createTplFile('content');
		// getTemplateByFileName(null) falls back to default class; must resolve Prado3 name
		$result = $manager->getTemplateByFileName($file, null);
		$this->assertInstanceOf(TSkinTemplate::class, $result);
	}

	// -----------------------------------------------------------------------
	// getTemplateByFileName() — usingClass() false / null edge cases
	// -----------------------------------------------------------------------

	/**
	 * A directory namespace (usingClass returns false) is not a valid template
	 * class; getTemplateByFileName() must return null.
	 */
	public function testGetTemplateByFileName_withDirectoryNamespaceTplClass_returnsNull(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		$result = $manager->getTemplateByFileName($file, 'Prado\\Web\\UI\\*');
		$this->assertNull($result);
	}

	/**
	 * A Prado3 directory dot-notation (usingClass returns false) also returns null.
	 */
	public function testGetTemplateByFileName_withPrado3DirectoryNotation_returnsNull(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		$result = $manager->getTemplateByFileName($file, 'System.Web.UI.*');
		$this->assertNull($result);
	}

	/**
	 * An unknown class name (usingClass returns null) returns null.
	 */
	public function testGetTemplateByFileName_withUnknownTplClass_returnsNull(): void
	{
		$manager = new TTemplateManager();
		$file = $this->createTplFile('content');
		$result = $manager->getTemplateByFileName($file, 'TFakeTemplateClassXYZ99999');
		$this->assertNull($result);
	}

	// -----------------------------------------------------------------------
	// getTemplateByFileName() — with an application cache
	// -----------------------------------------------------------------------

	/**
	 * Gives the application an array cache, restored in tearDown().
	 */
	private function useCache(): TTestArrayCache
	{
		$app = Prado::getApplication();
		$this->_appSnapshot ??= PradoUnit::snapshot($app, ['_cache', '_globalization', '_mode']);
		$app->setCache($cache = new TTestArrayCache());
		return $cache;
	}

	/**
	 * Creates a template including another one under the alias `TplManagerInc`.
	 * @return array{string, string} the template file and the included file
	 */
	private function createIncludingTemplate(): array
	{
		$dir = $this->makeTempDirectory('prado_tplmgr_');
		file_put_contents($included = $dir . DIRECTORY_SEPARATOR . 'Part.tpl', 'part');
		file_put_contents($file = $dir . DIRECTORY_SEPARATOR . 'Main.tpl', 'main <%include TplManagerInc.Part %>');
		touch($included, time() - 100);
		touch($file, time() - 100);
		Prado::setPathOfAlias('TplManagerInc', $dir);
		return [$file, $included];
	}

	private function withAliases(callable $test): void
	{
		$aliases = PradoUnit::snapshotStatic(Prado::class, ['_aliases']);
		try {
			$test();
		} finally {
			PradoUnit::restoreStatic(Prado::class, $aliases);
		}
	}

	public function testGetTemplateByFileNameCachesTheTemplateWithItsFileTimes(): void
	{
		$this->withAliases(function () {
			$cache = $this->useCache();
			[$file, $included] = $this->createIncludingTemplate();
			$manager = new TTemplateManager();
			$template = $manager->getTemplateByFileName($file);
			$key = TTemplateManager::TEMPLATE_CACHE_PREFIX . $file . ':' . TTemplate::class;
			$this->assertSame([$template, [$file => filemtime($file), $included => filemtime($included)]], $cache->values[$key]);
			$this->assertSame($template, $manager->getTemplateByFileName($file));
		});
	}

	public function testGetTemplateByFileNameReparsesWhenAFileChanged(): void
	{
		$this->withAliases(function () {
			$this->useCache();
			[$file, $included] = $this->createIncludingTemplate();
			$manager = new TTemplateManager();
			$template = $manager->getTemplateByFileName($file);
			touch($file, time() + 100);
			$this->assertNotSame($template, $manager->getTemplateByFileName($file));
		});
	}

	public function testGetTemplateByFileNameReparsesWhenAnIncludedFileChanged(): void
	{
		$this->withAliases(function () {
			$this->useCache();
			[$file, $included] = $this->createIncludingTemplate();
			$manager = new TTemplateManager();
			$template = $manager->getTemplateByFileName($file);
			touch($included, time() + 100);
			$reparsed = $manager->getTemplateByFileName($file);
			$this->assertNotSame($template, $reparsed);
			$this->assertSame($reparsed, $manager->getTemplateByFileName($file));
		});
	}

	public function testGetTemplateByFileNameReparsesWhenACachedFileIsGone(): void
	{
		$cache = $this->useCache();
		$file = $this->createTplFile('content');
		$key = TTemplateManager::TEMPLATE_CACHE_PREFIX . $file . ':' . TTemplate::class;
		$stale = new TTemplate('stale', dirname($file));
		$cache->values[$key] = [$stale, [$file . '.gone' => 0]];
		$manager = new TTemplateManager();
		$template = $manager->getTemplateByFileName($file);
		$this->assertNotSame($stale, $template);
		$this->assertSame([$file => filemtime($file)], $cache->values[$key][1]);
	}

	public function testGetTemplateByFileNameInPerformanceModeKeepsTheCachedTemplate(): void
	{
		$this->withAliases(function () {
			$this->useCache();
			[$file] = $this->createIncludingTemplate();
			$manager = new TTemplateManager();
			$template = $manager->getTemplateByFileName($file);
			Prado::getApplication()->setMode(TApplicationMode::Performance);
			touch($file, time() + 100);
			$this->assertSame($template, $manager->getTemplateByFileName($file));
		});
	}

	// -----------------------------------------------------------------------
	// getTemplateByFileName() — localized templates
	// -----------------------------------------------------------------------

	/**
	 * Gives the application a globalization module with the culture, restored in tearDown().
	 * @param string $culture the culture of the globalization module
	 */
	private function useCulture(string $culture): void
	{
		$app = Prado::getApplication();
		$this->_appSnapshot ??= PradoUnit::snapshot($app, ['_cache', '_globalization', '_mode']);
		$globalization = new TGlobalization();
		$globalization->setCulture($culture);
		$app->setGlobalization($globalization);
	}

	public function testGetTemplateByFileNameFindsTheTemplateOfTheCulture(): void
	{
		$this->useCulture('fr_FR');
		$dir = $this->makeTempDirectory('prado_tplmgr_');
		mkdir($dir . DIRECTORY_SEPARATOR . 'fr');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'Page.tpl', 'default');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'fr' . DIRECTORY_SEPARATOR . 'Page.tpl', 'french');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'Page.de.tpl', 'german');
		$manager = new TTemplateManager();
		$file = $dir . DIRECTORY_SEPARATOR . 'Page.tpl';
		$this->assertSame($dir . DIRECTORY_SEPARATOR . 'fr' . DIRECTORY_SEPARATOR . 'Page.tpl', $manager->getTemplateByFileName($file)->getTemplateFile());
		$this->assertSame($dir . DIRECTORY_SEPARATOR . 'Page.de.tpl', $manager->getTemplateByFileName($file, null, 'de_DE')->getTemplateFile());
		$this->assertSame($file, $manager->getTemplateByFileName($file, null, 'es')->getTemplateFile());
	}

	public function testGetTemplateByFileNameOfACultureWithoutAnyTemplateIsNull(): void
	{
		$this->useCulture('fr_FR');
		$dir = $this->makeTempDirectory('prado_tplmgr_');
		mkdir($dir . DIRECTORY_SEPARATOR . 'Page.tpl');
		$manager = new TTemplateManager();
		$this->assertNull($manager->getTemplateByFileName($dir . DIRECTORY_SEPARATOR . 'Page.tpl'));
		$this->assertNull($manager->getTemplateByFileName($dir . DIRECTORY_SEPARATOR . 'Missing.tpl'));
	}

	public function testGetTemplateByFileNameFindsTheTemplateOfTheFullCulture(): void
	{
		$this->useCulture('fr_FR');
		$dir = $this->makeTempDirectory('prado_tplmgr_');
		mkdir($dir . DIRECTORY_SEPARATOR . 'fr_FR');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'Page.tpl', 'default');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'fr_FR' . DIRECTORY_SEPARATOR . 'Page.tpl', 'french');
		$manager = new TTemplateManager();
		$template = $manager->getTemplateByFileName($dir . DIRECTORY_SEPARATOR . 'Page.tpl');
		$this->assertSame($dir . DIRECTORY_SEPARATOR . 'fr_FR' . DIRECTORY_SEPARATOR . 'Page.tpl', $template->getTemplateFile());
	}

	public function testGetTemplateByFileNameSkipsDirectories(): void
	{
		$this->useCulture('fr_FR');
		$dir = $this->makeTempDirectory('prado_tplmgr_');
		foreach (['fr_FR' . DIRECTORY_SEPARATOR . 'Page.tpl', 'fr' . DIRECTORY_SEPARATOR . 'Page.tpl', 'Page.fr_FR.tpl', 'Page.fr.tpl', 'Page.tpl'] as $path) {
			mkdir($dir . DIRECTORY_SEPARATOR . $path, 0o777, true);
		}
		$manager = new TTemplateManager();
		$this->assertNull($manager->getTemplateByFileName($dir . DIRECTORY_SEPARATOR . 'Page.tpl'));
	}
}
