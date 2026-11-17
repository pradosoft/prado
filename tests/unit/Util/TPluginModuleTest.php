<?php

namespace Prado\Test\Unit\Util;

use Prado\Exceptions\TException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TPluginModule;
use Prado\Web\Services\TPageService;

class TPluginModuleTest extends \PHPUnit\Framework\TestCase
{
	use TTempDirectoryTrait;

	private array $_appSnapshot;
	private array $_exceptionSnapshot;
	private int $_beginRequestCount;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->_appSnapshot = PradoUnit::snapshot($app, ['_service']);
		$this->_exceptionSnapshot = PradoUnit::snapshotStatic(TException::class, ['_messageFiles']);
		$this->_beginRequestCount = $app->onBeginRequest->getCount();
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		PradoUnit::restore($app, $this->_appSnapshot);
		PradoUnit::restoreStatic(TException::class, $this->_exceptionSnapshot);
		while ($app->onBeginRequest->getCount() > $this->_beginRequestCount) {
			$app->onBeginRequest->removeAt($app->onBeginRequest->getCount() - 1);
		}
		$this->removeTempDirectories();
	}

	private function makePlugin(string ...$subdirectories): TPluginModule
	{
		$dir = $this->makeTempDirectory();
		foreach ($subdirectories as $subdirectory) {
			mkdir($dir . DIRECTORY_SEPARATOR . $subdirectory);
		}
		$module = new TPluginModule();
		$module->setPluginPath($dir);
		return $module;
	}

	public function testPluginPathDefaultsToTheClassDirectory()
	{
		$module = new TPluginModule();
		self::assertSame(dirname((new \ReflectionClass(TPluginModule::class))->getFileName()), $module->getPluginPath());

		$module->setPluginPath('/some/path');
		self::assertSame('/some/path', $module->getPluginPath());
	}

	public function testPluginPagesPathFindsPages()
	{
		$module = $this->makePlugin('Pages');
		$pages = $module->getPluginPath() . DIRECTORY_SEPARATOR . 'Pages';

		self::assertSame($pages, $module->getPluginPagesPath());
		self::assertSame('Pages', $module->getRelativePagesPath());
	}

	public function testPluginPagesPathFindsLowercasePages()
	{
		$module = $this->makePlugin('pages');

		// A case-insensitive file system also resolves "Pages" to the "pages" directory.
		self::assertSame(0, strcasecmp($module->getPluginPath() . DIRECTORY_SEPARATOR . 'pages', $module->getPluginPagesPath()));
	}

	public function testPluginPagesPathIsFalseWithoutPages()
	{
		$module = $this->makePlugin();
		self::assertFalse($module->getPluginPagesPath());

		$module = $this->makePlugin();
		file_put_contents($module->getPluginPath() . DIRECTORY_SEPARATOR . 'Pages', 'a file');
		self::assertFalse($module->getPluginPagesPath());
	}

	public function testRelativePagesPath()
	{
		$module = $this->makePlugin('Views', 'Other');
		$module->setRelativePagesPath('Views');
		self::assertSame('Views', $module->getRelativePagesPath());
		self::assertSame($module->getPluginPath() . DIRECTORY_SEPARATOR . 'Views', $module->getPluginPagesPath());

		$module->setPluginPagesPath($module->getPluginPath() . DIRECTORY_SEPARATOR . 'Other');
		self::assertSame($module->getPluginPath() . DIRECTORY_SEPARATOR . 'Other', $module->getPluginPagesPath());
		self::assertSame('Other', $module->getRelativePagesPath());

		$module->setPluginPagesPath('/elsewhere/Pages');
		self::assertNull($module->getRelativePagesPath());
	}

	public function testErrorFile()
	{
		$module = $this->makePlugin();
		self::assertNull($module->getErrorFile());

		$file = $module->getPluginPath() . DIRECTORY_SEPARATOR . 'errorMessages.txt';
		file_put_contents($file, "plugin_test_error = A plugin error.\n");
		self::assertSame($file, $module->getErrorFile());
	}

	public function testInitRegistersTheErrorFile()
	{
		$module = $this->makePlugin();
		$file = $module->getPluginPath() . DIRECTORY_SEPARATOR . 'errorMessages.txt';
		file_put_contents($file, "plugin_test_error = A plugin error.\n");
		$module->init(null);

		self::assertContains($file, PradoUnit::getStaticProp(TException::class, '_messageFiles'));
		self::assertSame($this->_beginRequestCount, Prado::getApplication()->onBeginRequest->getCount());
	}

	public function testInitWithoutPagesAttachesNoPageHandler()
	{
		$module = $this->makePlugin();
		$module->init(null);

		self::assertSame($this->_beginRequestCount, Prado::getApplication()->onBeginRequest->getCount());
		self::assertSame(PradoUnit::getStaticProp(TException::class, '_messageFiles'), $this->_exceptionSnapshot['_messageFiles']);
	}

	public function testInitWithPagesServesPluginPages()
	{
		$app = Prado::getApplication();
		$module = $this->makePlugin('Pages');
		$module->init(null);
		self::assertTrue($app->onBeginRequest->contains([$module, 'attachPageServiceBehavior']));

		$service = new TPageService();
		PradoUnit::setProp($app, '_service', $service);
		$module->attachPageServiceBehavior($app, null);
		self::assertSame(
			[$module->getPluginPagesPath() . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'Users'],
			$service->onAdditionalPagePaths('Admin.Users')
		);
	}

	public function testAttachPageServiceBehaviorIgnoresOtherServices()
	{
		$app = Prado::getApplication();
		$module = $this->makePlugin('Pages');
		PradoUnit::setProp($app, '_service', null);
		$module->attachPageServiceBehavior($app, null);

		$service = new TPageService();
		self::assertSame([], $service->onAdditionalPagePaths('Admin.Users'));
	}

	public function testInitWithPagesAndErrorFile()
	{
		$module = $this->makePlugin('Pages');
		$file = $module->getPluginPath() . DIRECTORY_SEPARATOR . 'errorMessages.txt';
		file_put_contents($file, "plugin_test_error = A plugin error.\n");
		$module->init(null);

		self::assertContains($file, PradoUnit::getStaticProp(TException::class, '_messageFiles'));
		self::assertTrue(Prado::getApplication()->onBeginRequest->contains([$module, 'attachPageServiceBehavior']));
	}

	public function testPluginPagesPathOnACaseSensitiveFileSystem()
	{
		$module = $this->makePlugin();
		$path = $module->getPluginPath();
		file_put_contents($path . DIRECTORY_SEPARATOR . 'Pages', 'a file');
		if (file_exists($path . DIRECTORY_SEPARATOR . 'pages')) {
			self::markTestSkipped('The file system is case-insensitive, so "Pages" and "pages" are one name.');
		}
		mkdir($path . DIRECTORY_SEPARATOR . 'pages');
		self::assertSame($path . DIRECTORY_SEPARATOR . 'pages', $module->getPluginPagesPath());

		$module = $this->makePlugin();
		file_put_contents($module->getPluginPath() . DIRECTORY_SEPARATOR . 'pages', 'a file');
		self::assertFalse($module->getPluginPagesPath());
	}
}
