<?php

namespace Prado\Test\Unit\Harness\Caching;

use Prado\Caching\TMemoryCache;

/**
 * A {@see TMemoryCache} fixture overriding DEFAULT_BACKING_CACHE_KEY, to verify that the
 * constructor seeds the backing cache key via late static binding.
 */
class TTestMemoryCacheCustomKey extends TTestMemoryCache
{
	public const DEFAULT_BACKING_CACHE_KEY = 'custom.key';
}
