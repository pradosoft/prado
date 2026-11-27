<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TSafeHtml;

/**
 * TSafeHtml whose application has the runtime path a test sets.
 */
class TSafeHtmlTestRuntime extends TSafeHtml
{
	public string $runtimePath = '';

	public function getApplication()
	{
		$path = $this->runtimePath;
		return new class ($path) {
			public function __construct(private string $path)
			{
			}

			public function getRuntimePath()
			{
				return $this->path;
			}
		};
	}
}

class TSafeHtmlTest extends TestCase
{
	use TOutputBufferRestorationTrait;
	use TWebControlRenderTrait;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
	}

	protected function tearDown(): void
	{
		$this->restoreOutputBufferLevel();
	}

	public function testRenderPurifiesTheBody()
	{
		$safe = new TSafeHtml();
		$safe->getControls()->add('<p onclick="x()">Hi <script>alert(1)</script><b>there</b></p>');
		$this->assertSame('<p>Hi <b>there</b></p>', $this->render($safe));
	}

	public function testDefaultConfigCachesInTheRuntimeFolder()
	{
		$config = (new TSafeHtml())->getConfig();
		$path = Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'htmlpurifier';
		$this->assertInstanceOf(\HTMLPurifier_Config::class, $config);
		$this->assertSame($path, $config->get('Cache.SerializerPath'));
		$this->assertDirectoryExists($path);
	}

	public function testCustomConfigIsUsed()
	{
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('HTML.Allowed', 'p');
		$config->set('Cache.DefinitionImpl', null);
		$safe = new TSafeHtml();
		$safe->setConfig($config);
		$this->assertSame($config, $safe->getConfig());
		$safe->getControls()->add('<p><b>bold</b></p>');
		$this->assertSame('<p>bold</p>', $this->render($safe));
	}

	public function testUncreatableCacheFolderThrows()
	{
		$file = tempnam(sys_get_temp_dir(), 'runtime');
		try {
			$safe = new TSafeHtmlTestRuntime();
			$safe->runtimePath = $file;
			$safe->getConfig();
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('htmlpurifier_source_path_failed', $e->getErrorCode());
		} finally {
			unlink($file);
		}
	}

	public function testDefaultConfigCreatesTheCacheFolder()
	{
		$runtime = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('runtime');
		mkdir($runtime);
		$folder = $runtime . DIRECTORY_SEPARATOR . 'htmlpurifier';
		try {
			$safe = new TSafeHtmlTestRuntime();
			$safe->runtimePath = $runtime;
			$this->assertSame($folder, $safe->getConfig()->get('Cache.SerializerPath'));
			$this->assertDirectoryExists($folder);
			if (PHP_OS_FAMILY !== 'Windows') {
				// Windows reports every folder as 0777
				$this->assertSame(Prado::getDefaultDirPermissions(), fileperms($folder) & 0777);
			}
		} finally {
			@rmdir($folder);
			rmdir($runtime);
		}
	}
}
