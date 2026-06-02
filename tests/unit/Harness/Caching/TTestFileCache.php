<?php

/**
 * TTestFileCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TFileCache;

/**
 * TTestFileCache is a {@see TFileCache} harness that exposes its protected filesystem,
 * hashing, sizing, and serialized-contract seams for unit tests.
 *
 * The clock is fakeable via {@see TTestCacheClockTrait}; {@see $hashTokenCallback} lets a
 * test inject a broken {@see hashToken()} after a successful {@see init()}; every other
 * protected method is reachable through a `pub*()` accessor.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestFileCache extends TFileCache
{
	use TTestCacheClockTrait;

	/**
	 * @var null|callable when set, {@see hashToken()} delegates to this callable instead
	 *   of the default sha1 implementation, letting tests inject a bad hashToken after
	 *   init() has already succeeded with the normal implementation.
	 */
	public $hashTokenCallback = null;

	protected function hashToken(string $token): string
	{
		return $this->hashTokenCallback !== null
			? ($this->hashTokenCallback)($token)
			: parent::hashToken($token);
	}

	// ---------------------------------------------------------------- exposers

	public function pubGenerateUniqueKey(string $key): string
	{
		return $this->generateUniqueKey($key);
	}

	public function pubHashToken(string $token): string
	{
		return $this->hashToken($token);
	}

	public function pubPathFor(string $key): string
	{
		return $this->pathFor($key);
	}

	public function pubGetSerializedValue(string $key): false|string
	{
		return $this->getSerializedValue($key);
	}

	public function pubSetSerializedValue(string $key, string $value, int $expire): bool
	{
		return $this->setSerializedValue($key, $value, $expire);
	}

	public function pubAddSerializedValue(string $key, string $value, int $expire): bool
	{
		return $this->addSerializedValue($key, $value, $expire);
	}

	public function pubGetContents(string $filePath): string|false
	{
		return $this->getContents($filePath);
	}

	public function pubPutContents(string $filePath, string $data, bool $exclusive = false): int|false
	{
		return $this->putContents($filePath, $data, $exclusive);
	}

	public function pubUnlink(string $filePath): bool
	{
		return $this->unlink($filePath);
	}

	public function pubRename(string $srcFilePath, string $destFilePath): bool
	{
		return $this->rename($srcFilePath, $destFilePath);
	}

	public function pubChmod(string $filePath, int $mode): bool
	{
		return $this->chmod($filePath, $mode);
	}

	public function pubTempnam(string $dir, string $prefix): string|false
	{
		return $this->tempnam($dir, $prefix);
	}

	public function pubGetTempFilePrefixDirect(): string
	{
		return $this->getTempFilePrefixDirect();
	}

	public function pubIsFile(string $path): bool
	{
		return $this->isFile($path);
	}

	public function pubTouch(string $filePath): bool
	{
		return $this->touch($filePath);
	}

	public function pubComputeSizeFingerprint(): string
	{
		return $this->computeSizeFingerprint();
	}

	public function pubComputeCurrentSize(): int
	{
		return $this->computeCurrentSize();
	}

	public function pubGetMaximumSizeDirect(): int
	{
		return $this->getMaximumSizeDirect();
	}

	public function pubGetCurrentSizeDirect(): int
	{
		return $this->getCurrentSizeDirect();
	}

	public function pubSetCurrentSizeDirect(int $value): void
	{
		$this->setCurrentSizeDirect($value);
	}

	public function pubGetSizeFingerprintDirect(): string
	{
		return $this->getSizeFingerprintDirect();
	}

	public function pubSetSizeFingerprintDirect(string $value): void
	{
		$this->setSizeFingerprintDirect($value);
	}
}
