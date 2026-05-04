<?php

use Prado\Prado;
use Prado\Web\UI\TTemplateManager;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TSkinTemplate;
use Prado\Web\UI\ITemplate;

class TTemplateManagerTest extends PHPUnit\Framework\TestCase
{
	/** @var string[] temp .tpl files created during tests */
	private array $_tmpFiles = [];

	protected function tearDown(): void
	{
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
}
