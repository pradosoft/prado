<?php

/**
 * TFileCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TFileCacheDependency;

/**
 * Unit tests for {@see \Prado\Caching\TFileCacheDependency}.
 *
 * File-system stat results are cached by the OS/PHP. All tests that change a
 * file's mtime call {@see clearstatcache()} immediately afterward so that
 * subsequent {@see filemtime()} calls reflect the new value.
 */
class TFileCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	private string $_tempFile;

	protected function setUp(): void
	{
		$this->_tempFile = __DIR__ . '/temp/TFileCacheDependencyTest_file.txt';
		file_put_contents($this->_tempFile, 'initial');
		clearstatcache();
	}

	protected function tearDown(): void
	{
		if (file_exists($this->_tempFile)) {
			unlink($this->_tempFile);
		}
	}

	// -------------------------------------------------------------------------
	// Construction and property access
	// -------------------------------------------------------------------------

	public function testConstructorExistingFile(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$this->assertSame($this->_tempFile, $dep->getFileName());
		$this->assertSame(filemtime($this->_tempFile), $dep->getTimestamp());
	}

	public function testConstructorNonExistentFile(): void
	{
		$missing = __DIR__ . '/temp/does_not_exist_xyz.txt';
		$dep = new TFileCacheDependency($missing);
		$this->assertSame($missing, $dep->getFileName());
		$this->assertFalse($dep->getTimestamp());
	}

	// -------------------------------------------------------------------------
	// setFileName / setFileNameDirect
	// -------------------------------------------------------------------------

	public function testSetFileNameUpdatesTimestamp(): void
	{
		$dep = new TFileCacheDependency(__DIR__ . '/temp/does_not_exist_xyz.txt');
		$this->assertFalse($dep->getTimestamp(), 'Timestamp must be false for a missing file.');

		$dep->setFileName($this->_tempFile);
		$this->assertSame($this->_tempFile, $dep->getFileName());
		$this->assertSame(filemtime($this->_tempFile), $dep->getTimestamp());
	}

	public function testSetFileNameDirectDoesNotUpdateTimestamp(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$originalTimestamp = $dep->getTimestamp();

		// Touch the file so its mtime changes.
		touch($this->_tempFile, $originalTimestamp - 60);
		clearstatcache();

		// setFileNameDirect must not re-read the mtime — timestamp stays the same.
		$this->callProtected($dep, 'setFileNameDirect', [$this->_tempFile]);
		$this->assertSame($originalTimestamp, $dep->getTimestamp());
	}

	// -------------------------------------------------------------------------
	// updateTimestamp
	// -------------------------------------------------------------------------

	public function testUpdateTimestampRefreshesStoredValue(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$originalTimestamp = $dep->getTimestamp();

		// Set the file's mtime to a known past value.
		touch($this->_tempFile, $originalTimestamp - 120);
		clearstatcache();

		$dep->updateTimestamp();
		$this->assertSame(filemtime($this->_tempFile), $dep->getTimestamp());
		$this->assertNotSame($originalTimestamp, $dep->getTimestamp());
	}

	public function testUpdateTimestampStoresFalseForMissingFile(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$this->assertIsInt($dep->getTimestamp());

		unlink($this->_tempFile);
		clearstatcache();

		$dep->updateTimestamp();
		$this->assertFalse($dep->getTimestamp());
	}

	// -------------------------------------------------------------------------
	// getHasChanged
	// -------------------------------------------------------------------------

	public function testGetHasChangedFalseWhenFileUnchanged(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		clearstatcache();
		$this->assertFalse($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenMtimeChanges(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$originalMtime = filemtime($this->_tempFile);

		// Wind the mtime back so it differs from the stored snapshot.
		touch($this->_tempFile, $originalMtime - 10);
		clearstatcache();

		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenFileDeleted(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		unlink($this->_tempFile);
		clearstatcache();

		// File gone → filemtime returns false, stored mtime is an int → changed.
		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenMissingFileIsCreated(): void
	{
		$missing = __DIR__ . '/temp/TFileCacheDep_new_file.txt';
		@unlink($missing);
		clearstatcache();

		// Dependency records false (file absent).
		$dep = new TFileCacheDependency($missing);
		$this->assertFalse($dep->getTimestamp());

		// File is created.
		file_put_contents($missing, 'created');
		clearstatcache();

		// false (stored) !== int (current) → changed.
		$this->assertTrue($dep->getHasChanged());

		unlink($missing);
	}

	// -------------------------------------------------------------------------
	// Serialization round-trip
	// -------------------------------------------------------------------------

	public function testSerializationPreservesState(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$restored = unserialize(serialize($dep));

		$this->assertSame($dep->getFileName(), $restored->getFileName());
		$this->assertSame($dep->getTimestamp(), $restored->getTimestamp());
	}

	public function testGetHasChangedAfterSerializationWhenUnchanged(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$serialized = serialize($dep);

		clearstatcache();
		$restored = unserialize($serialized);
		$this->assertFalse($restored->getHasChanged());
	}

	public function testGetHasChangedAfterSerializationWhenChanged(): void
	{
		$dep = new TFileCacheDependency($this->_tempFile);
		$serialized = serialize($dep);

		// Change the mtime after serializing.
		touch($this->_tempFile, filemtime($this->_tempFile) - 30);
		clearstatcache();

		$restored = unserialize($serialized);
		$this->assertTrue($restored->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// Helper
	// -------------------------------------------------------------------------

	private function callProtected(object $obj, string $method, array $args = []): mixed
	{
		$rm = new ReflectionMethod($obj, $method);
		$rm->setAccessible(true);
		return $rm->invokeArgs($obj, $args);
	}
}
