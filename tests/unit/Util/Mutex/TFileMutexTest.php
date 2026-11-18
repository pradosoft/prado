<?php

/**
 * TFileMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Test\Unit\PradoUnit;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\Mutex\TFileMutex;

/**
 * Exposes the file identity check of {@see TFileMutex}.
 */
class TFileMutexTestMutex extends TFileMutex
{
	public function pubIsSameFile($handle, string $file): bool
	{
		return $this->isSameFile($handle, $file);
	}
}

/**
 * A TFileMutex whose lock file is always replaced after it opens it.
 */
class TFileMutexTestReplacedMutex extends TFileMutex
{
	protected function isSameFile($handle, string $file): bool
	{
		return false;
	}
}

/**
 * Unit tests for {@see TFileMutex}: lock files, exclusion between handles and
 * processes, file and directory modes, and the deleted-file check.
 */
class TFileMutexTest extends \PHPUnit\Framework\TestCase
{
	private string $dir;

	private array $mutexes = [];

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-filemutex-' . bin2hex(random_bytes(6));
	}

	protected function tearDown(): void
	{
		foreach ($this->mutexes as $mutex) {
			$mutex->releaseAll();
		}
		$this->mutexes = [];
		if (is_dir($this->dir)) {
			foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') as $file) {
				@unlink($file);
			}
			@rmdir($this->dir);
		}
	}

	private function newMutex(): TFileMutexTestMutex
	{
		$mutex = new TFileMutexTestMutex();
		$mutex->setMutexPath($this->dir);
		$mutex->setRetryDelay(1);
		return $this->mutexes[] = $mutex;
	}

	public function testDefaultMutexPathIsRuntimeMutex(): void
	{
		$mutex = new TFileMutex();
		$this->assertSame(Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'mutex', $mutex->getMutexPath());
	}

	public function testMutexPathNamespace(): void
	{
		$mutex = new TFileMutex();
		$mutex->setMutexPath('Application.mutexdir');
		$this->assertSame(Prado::getPathOfNamespace('Application.mutexdir'), $mutex->getMutexPath());
	}

	public function testLockFileIsHashOfPrefixedName(): void
	{
		$mutex = $this->newMutex();
		$this->assertSame($this->dir . DIRECTORY_SEPARATOR . md5('job') . '.lock', $mutex->getLockFile('job'));
		$mutex->setKeyPrefix('app:');
		$this->assertSame($this->dir . DIRECTORY_SEPARATOR . md5('app:job') . '.lock', $mutex->getLockFile('job'));
	}

	public function testAcquireCreatesDirectoryAndLockFile(): void
	{
		$mutex = $this->newMutex();
		$this->assertDirectoryDoesNotExist($this->dir);
		$this->assertTrue($mutex->acquire('job'));
		$this->assertDirectoryExists($this->dir);
		$this->assertFileExists($mutex->getLockFile('job'));
		$this->assertTrue($mutex->release('job'));
		$this->assertFileDoesNotExist($mutex->getLockFile('job'));
	}

	public function testHandlesExcludeEachOther(): void
	{
		$a = $this->newMutex();
		$b = $this->newMutex();
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertFalse($b->acquire('job', 0.02));
		$this->assertTrue($b->acquire('other'));
		$a->release('job');
		$this->assertTrue($b->acquire('job'));
	}

	public function testOtherProcessIsExcluded(): void
	{
		if (!function_exists('proc_open')) {
			$this->markTestSkipped('proc_open is not available.');
		}
		$mutex = $this->newMutex();
		$this->assertTrue($mutex->acquire('job'));
		$this->assertSame('blocked', $this->tryLockInChild($mutex->getLockFile('job')));
		$mutex->release('job');
		$this->assertTrue($mutex->acquire('job'));
		$mutex->release('job');
		$this->assertSame('missing', $this->tryLockInChild($mutex->getLockFile('job')));
	}

	public function testFileMode(): void
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('File modes do not apply on Windows.');
		}
		$mutex = $this->newMutex();
		$this->assertNull($mutex->getFileMode());
		$mutex->setFileMode('0640');
		$this->assertSame(0o640, $mutex->getFileMode());
		$mutex->setDirMode('0750');
		$this->assertSame(0o750, $mutex->getDirMode());
		$this->assertTrue($mutex->acquire('job'));
		clearstatcache();
		$this->assertSame(0o640, fileperms($mutex->getLockFile('job')) & 0o777);
		$this->assertSame(0o750, fileperms($this->dir) & 0o777);
		$mutex->setFileMode('');
		$this->assertNull($mutex->getFileMode());
		$mutex->setDirMode(0o700);
		$this->assertSame(0o700, $mutex->getDirMode());
	}

	public function testDeletedLockFileIsNotTheSameFile(): void
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('Windows does not delete an open file.');
		}
		$mutex = $this->newMutex();
		mkdir($this->dir);
		$file = $mutex->getLockFile('job');
		$handle = fopen($file, 'c');
		$this->assertTrue($mutex->pubIsSameFile($handle, $file));
		unlink($file);
		$this->assertFalse($mutex->pubIsSameFile($handle, $file));
		touch($file);
		$this->assertFalse($mutex->pubIsSameFile($handle, $file));
		fclose($handle);
	}

	public function testForkedChildClosesWithoutUnlocking(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('job');
		$file = $mutex->getLockFile('job');
		$mutex->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertFalse($mutex->isAcquired('job'));
		$this->assertFileExists($file);
	}

	public function testUncreatableDirectoryThrows(): void
	{
		$blocker = $this->dir;
		file_put_contents($blocker, 'not a directory');
		try {
			$mutex = new TFileMutex();
			$mutex->setMutexPath($blocker . DIRECTORY_SEPARATOR . 'sub');
			$this->expectException(TConfigurationException::class);
			$mutex->acquire('job');
		} finally {
			unlink($blocker);
		}
	}

	/**
	 * Tries a non-blocking exclusive lock of a file in a child PHP process.
	 * @param string $file the lock file.
	 * @return string 'locked', 'blocked', or 'missing'.
	 */
	private function tryLockInChild(string $file): string
	{
		$code = '$f = $argv[1]; if (!is_file($f)) { echo "missing"; exit; } $h = fopen($f, "c"); echo flock($h, LOCK_EX | LOCK_NB) ? "locked" : "blocked";';
		$command = [PHP_BINARY, '-n', '-r', $code, $file];
		$process = proc_open($command, [1 => ['pipe', 'w']], $pipes);
		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		proc_close($process);
		return trim($output);
	}

	public function testReplacedLockFileIsNotAcquired(): void
	{
		$mutex = new TFileMutexTestReplacedMutex();
		$mutex->setMutexPath($this->dir);
		$this->mutexes[] = $mutex;
		$this->assertFalse($mutex->acquire('job'));
		$mutex->setFileMode(0o600);
		$this->assertFalse($mutex->acquire('job'));
		$this->assertTrue($this->newMutex()->acquire('job'), 'The rejected handle is unlocked.');
	}

	public function testUnopenableLockFileIsNotAcquired(): void
	{
		$mutex = $this->newMutex();
		mkdir($this->dir);
		mkdir($mutex->getLockFile('job'));
		try {
			$this->assertFalse($mutex->acquire('job'));
		} finally {
			rmdir($mutex->getLockFile('job'));
		}
	}

	public function testBackendReleaseAndForgetOfUnheldName(): void
	{
		$mutex = $this->newMutex();
		$this->assertFalse(PradoUnit::invoke($mutex, 'releaseLock', 'never-held'));
		PradoUnit::invoke($mutex, 'forgetLock', 'never-held');
		$this->assertSame([], $mutex->getAcquiredLocks());
	}

	public function testModeValues(): void
	{
		$mutex = $this->newMutex();
		$mutex->setFileMode(0o600);
		$this->assertSame(0o600, $mutex->getFileMode());
		$mutex->setFileMode(null);
		$this->assertNull($mutex->getFileMode());
		$mutex->setDirMode(null);
		$this->assertSame(TFileMutex::DEFAULT_DIR_MODE, $mutex->getDirMode());
	}

	public function testExistingDirectoryIsKept(): void
	{
		mkdir($this->dir, 0o700);
		$mutex = $this->newMutex();
		$mutex->setDirMode(0o755);
		$this->assertTrue($mutex->acquire('job'));
		clearstatcache();
		if (!TProcessHelper::isSystemWindows()) {
			$this->assertSame(0o700, fileperms($this->dir) & 0o777);
		}
	}

	public function testContendedLockWithFileMode(): void
	{
		$holder = $this->newMutex();
		$this->assertTrue($holder->acquire('job'));
		$mutex = $this->newMutex();
		$mutex->setFileMode(0o600);
		$this->assertFalse($mutex->acquire('job'));
	}
}
