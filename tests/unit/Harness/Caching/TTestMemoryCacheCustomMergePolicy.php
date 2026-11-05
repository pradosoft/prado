<?php

namespace Prado\Test\Unit\Harness\Caching;

use Prado\Caching\TMemoryCache;

/**
 * A {@see TMemoryCache} fixture overriding DEFAULT_MERGE_POLICY, to verify that the
 * constructor seeds the merge policy via late static binding.
 */
class TTestMemoryCacheCustomMergePolicy extends TTestMemoryCache
{
	public const DEFAULT_MERGE_POLICY = TMemoryCache::REPLACE;
}
