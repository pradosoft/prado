<?php

/**
 * TTestMemoryCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TMemoryCache;

/**
 * TTestMemoryCache is a {@see TMemoryCache} harness exposing its protected store, sizing,
 * backing-persistence, serialized-contract, and serialize-helper seams.
 *
 * The clock is fakeable via {@see TTestCacheClockTrait}; every other protected method is
 * reachable through a `pub*()` accessor — including the dual-mode store core
 * ({@see readStore()} / {@see writeStore()} / {@see addStore()}).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestMemoryCache extends TMemoryCache
{
	use TTestCacheClockTrait;

	// ------------------------------------------------------------- value contract

	public function pubGetValue(string $key): mixed
	{
		return $this->getValue($key);
	}

	public function pubSetValue(string $key, mixed $value, int $expire): bool
	{
		return $this->setValue($key, $value, $expire);
	}

	public function pubAddValue(string $key, mixed $value, int $expire): bool
	{
		return $this->addValue($key, $value, $expire);
	}

	public function pubDeleteValue(string $key): bool
	{
		return $this->deleteValue($key);
	}

	// ------------------------------------------------------------- store core

	public function pubReadStore(string $key): mixed
	{
		return $this->readStore($key);
	}

	public function pubWriteStore(string $key, mixed $payload, int $expire): bool
	{
		return $this->writeStore($key, $payload, $expire);
	}

	public function pubAddStore(string $key, mixed $payload, int $expire): bool
	{
		return $this->addStore($key, $payload, $expire);
	}

	public function pubGetSerializedValue(string $key): false|string
	{
		return $this->getSerializedValue($key);
	}

	// ------------------------------------------------------------- backing persistence

	public function pubLoad(): bool
	{
		return $this->load();
	}

	public function pubSave(): bool
	{
		return $this->save();
	}

	public function pubLoadFromBacking(): ?array
	{
		return $this->loadFromBacking();
	}

	public function pubSaveToBacking(array $store): bool
	{
		return $this->saveToBacking($store);
	}

	// ------------------------------------------------------------- store directs

	public function pubGenerateUniqueKey(string $key): string
	{
		return $this->generateUniqueKey($key);
	}

	public function pubGetStore(): array
	{
		return $this->getStoreDirect();
	}

	public function &pubGetStoreRef(): array
	{
		return $this->getStoreDirect();
	}

	public function pubSetStore(array $value): void
	{
		$this->setStoreDirect($value);
	}

	public function pubGetStoreEntry(string $key): ?array
	{
		return $this->getStoreEntry($key);
	}

	public function pubHasStoreEntry(string $key): bool
	{
		return $this->hasStoreEntry($key);
	}

	public function pubSetStoreEntry(string $key, array $entry): void
	{
		$this->setStoreEntry($key, $entry);
	}

	public function pubClearStoreEntry(string $key): void
	{
		$this->clearStoreEntry($key);
	}

	// ------------------------------------------------------------- sizing

	public function pubComputeSizeFingerprint(): string
	{
		return $this->computeSizeFingerprint();
	}

	public function pubComputeCurrentSize(): int
	{
		return $this->computeCurrentSize();
	}

	public function pubEvictToFitMaximumSize(): void
	{
		$this->evictToFitMaximumSize();
	}

	public function pubValidateSizeCache(): void
	{
		$this->validateSizeCache();
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

	// ------------------------------------------------------------- misc seams

	public function pubGetChanged(): bool
	{
		return $this->getChanged();
	}

	public function pubGetHashKeys(): ?bool
	{
		return $this->getHashKeys();
	}

	public function pubGetSerializeValues(): bool
	{
		return $this->getSerializeValues();
	}

	public function pubSerialize(mixed $value): string
	{
		return $this->serialize($value);
	}

	public function pubUnserialize(string $data): mixed
	{
		return $this->unserialize($data);
	}

	public function pubGetContents(string $filePath): string|false
	{
		return $this->getContents($filePath);
	}

	public function pubPutContents(string $filePath, string $data, bool $exclusive = false): int|false
	{
		return $this->putContents($filePath, $data, $exclusive);
	}
}

/**
 * A {@see TMemoryCache} fixture overriding DEFAULT_BACKING_CACHE_KEY, to verify that the
 * constructor seeds the backing cache key via late static binding.
 */
class TTestMemoryCacheCustomKey extends TTestMemoryCache
{
	public const DEFAULT_BACKING_CACHE_KEY = 'custom.key';
}

/**
 * A {@see TMemoryCache} fixture overriding DEFAULT_MERGE_POLICY, to verify that the
 * constructor seeds the merge policy via late static binding.
 */
class TTestMemoryCacheCustomMergePolicy extends TTestMemoryCache
{
	public const DEFAULT_MERGE_POLICY = TMemoryCache::REPLACE;
}
