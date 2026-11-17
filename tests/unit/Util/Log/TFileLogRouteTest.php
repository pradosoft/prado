<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Util\Log\TLogger;
use Prado\Util\Log\TSysLogRoute;
use Prado\Util\Log\TFileLogRoute;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

class TTestFileLogRoute extends TFileLogRoute {
}

class TFileLogRouteTest extends \PHPUnit\Framework\TestCase
{
	use TTempDirectoryTrait;

	private array $aliases = [];

	protected function setUp(): void
	{
		$this->aliases = PradoUnit::snapshotStatic(Prado::class, ['_aliases']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(Prado::class, $this->aliases);
		$this->removeTempDirectories();
	}

	/**
	 * Returns a route logging into a new temporary directory, set by its alias.
	 */
	private function routeInTempDirectory(): TTestFileLogRoute
	{
		$dir = $this->makeTempDirectory('prado_filelog_');
		Prado::setPathOfAlias('FileLogRouteTest', $dir);
		$route = new TTestFileLogRoute();
		$route->setLogPath('FileLogRouteTest.*');
		return $route;
	}
	
	public function testRotateFiles()
	{
		$logger = new TLogger();
		$route = new TTestFileLogRoute();
		
		$logger->log('token', TLogger::DEBUG);
		$logs = $logger->getLogs();
		
		$route->collectLogs($logger);
		$this->assertEquals(1, $route->getLogCount());
		
		$logFile = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		if(is_file($logFile))
			unlink($logFile);
		
		$route->collectLogs(true);
		$this->assertEquals(0, $route->getLogCount());
		
		$logFile = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		$this->assertTrue(is_file($logFile));
		$this->assertGreaterThan(0, @filesize($logFile));
		
		unlink($logFile);
	}

	public function testLogPath()
	{
		$route = new TTestFileLogRoute();
		$this->assertSame(Prado::getApplication()->getRuntimePath(), $route->getLogPath());

		$dir = $this->makeTempDirectory('prado_filelog_');
		Prado::setPathOfAlias('FileLogRouteTest', $dir);
		$this->assertSame($route, $route->setLogPath('FileLogRouteTest.*'));
		$this->assertSame($dir, $route->getLogPath());

		foreach (['NoAliasPath', 'FileLogRouteTest.missing.*', 'FileLogRouteTest.file.*'] as $path) {
			if ($path === 'FileLogRouteTest.file.*') {
				touch($dir . DIRECTORY_SEPARATOR . 'file');
			}
			try {
				$route->setLogPath($path);
				$this->fail("LogPath '{$path}' throws TConfigurationException.");
			} catch (TConfigurationException $e) {
				$this->assertSame('filelogroute_logpath_invalid', $e->getErrorCode(), $path);
			}
		}
		$this->assertSame($dir, $route->getLogPath());
	}

	public function testLogPathMustBeWritable()
	{
		if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
			$this->markTestSkipped('The root user writes into a read-only directory.');
		}
		$dir = $this->makeTempDirectory('prado_filelog_');
		mkdir($dir . DIRECTORY_SEPARATOR . 'readonly', 0o500);
		Prado::setPathOfAlias('FileLogRouteTest', $dir);
		$route = new TTestFileLogRoute();
		try {
			$route->setLogPath('FileLogRouteTest.readonly.*');
			$this->fail('A read-only LogPath throws TConfigurationException.');
		} catch (TConfigurationException $e) {
			$this->assertSame('filelogroute_logpath_invalid', $e->getErrorCode());
		} finally {
			chmod($dir . DIRECTORY_SEPARATOR . 'readonly', 0o700);
		}
	}

	public function testLogFile()
	{
		$route = new TTestFileLogRoute();
		$this->assertSame('prado.log', $route->getLogFile());
		$this->assertSame($route, $route->setLogFile('app.log'));
		$this->assertSame('app.log', $route->getLogFile());
	}

	public function testMaxFileSize()
	{
		$route = new TTestFileLogRoute();
		$this->assertSame(512, $route->getMaxFileSize());
		$this->assertSame($route, $route->setMaxFileSize('2'));
		$this->assertSame(2, $route->getMaxFileSize());
		foreach ([0, -1] as $size) {
			try {
				$route->setMaxFileSize($size);
				$this->fail("MaxFileSize {$size} throws TInvalidDataValueException.");
			} catch (TInvalidDataValueException $e) {
				$this->assertSame('filelogroute_maxfilesize_invalid', $e->getErrorCode());
			}
		}
		$this->assertSame(2, $route->getMaxFileSize());
	}

	public function testMaxLogFiles()
	{
		$route = new TTestFileLogRoute();
		$this->assertSame(2, $route->getMaxLogFiles());
		$this->assertSame($route, $route->setMaxLogFiles('3'));
		$this->assertSame(3, $route->getMaxLogFiles());
		try {
			$route->setMaxLogFiles(0);
			$this->fail('MaxLogFiles 0 throws TInvalidDataValueException.');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('filelogroute_maxlogfiles_invalid', $e->getErrorCode());
		}
		$this->assertSame(3, $route->getMaxLogFiles());
	}

	public function testProcessLogsAppendsToTheFile()
	{
		$route = $this->routeInTempDirectory();
		$file = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		PradoUnit::invoke($route, 'processLogs', [TLogEntryFactory::entry('one'), TLogEntryFactory::entry('two')], true, []);
		PradoUnit::invoke($route, 'processLogs', [TLogEntryFactory::entry('three')], true, []);

		$lines = file($file, FILE_IGNORE_NEW_LINES);
		$this->assertCount(3, $lines);
		$this->assertStringEndsWith('[Info] [Test\\Category] one', $lines[0]);
		$this->assertStringEndsWith('[Info] [Test\\Category] three', $lines[2]);
		$this->assertFileDoesNotExist($file . '.1');
	}

	public function testProcessLogsRotatesALargeFile()
	{
		$route = $this->routeInTempDirectory();
		$route->setMaxFileSize(1);
		$file = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		file_put_contents($file, str_repeat('x', 1025));

		PradoUnit::invoke($route, 'processLogs', [TLogEntryFactory::entry('after')], true, []);

		$this->assertSame(1025, filesize($file . '.1'));
		$this->assertStringEndsWith("[Info] [Test\\Category] after\n", file_get_contents($file));
	}

	public function testRotateFilesShiftsTheNumberedFiles()
	{
		$route = $this->routeInTempDirectory();
		$route->setMaxLogFiles(3);
		$file = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		file_put_contents($file, 'current');
		file_put_contents($file . '.1', 'one');
		file_put_contents($file . '.3', 'three');

		PradoUnit::invoke($route, 'rotateFiles');

		$this->assertFileDoesNotExist($file);
		$this->assertSame('current', file_get_contents($file . '.1'));
		$this->assertSame('one', file_get_contents($file . '.2'));
		$this->assertFileDoesNotExist($file . '.3');

		PradoUnit::invoke($route, 'rotateFiles');
		$this->assertFileDoesNotExist($file . '.1');
		$this->assertSame('current', file_get_contents($file . '.2'));
		$this->assertSame('one', file_get_contents($file . '.3'));
	}

	public function testProcessLogsWithoutLogs()
	{
		$route = $this->routeInTempDirectory();
		$file = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		PradoUnit::invoke($route, 'processLogs', [], true, []);
		$this->assertFileDoesNotExist($file);

		$route->setMaxFileSize(1);
		file_put_contents($file, str_repeat('x', 1025));
		PradoUnit::invoke($route, 'processLogs', [], true, []);
		$this->assertFileDoesNotExist($file);
		$this->assertSame(1025, filesize($file . '.1'));
	}

	public function testRotateFilesWithOneFile()
	{
		$route = $this->routeInTempDirectory();
		$route->setMaxLogFiles(1);
		$file = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();

		PradoUnit::invoke($route, 'rotateFiles');
		$this->assertFileDoesNotExist($file . '.1');

		file_put_contents($file . '.1', 'old');
		PradoUnit::invoke($route, 'rotateFiles');
		$this->assertFileDoesNotExist($file . '.1');

		file_put_contents($file . '.1', 'old');
		file_put_contents($file, 'current');
		PradoUnit::invoke($route, 'rotateFiles');
		$this->assertFileDoesNotExist($file);
		$this->assertSame('current', file_get_contents($file . '.1'));
	}
}

