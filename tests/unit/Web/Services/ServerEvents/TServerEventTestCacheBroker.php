<?php

namespace Prado\Test\Unit\Web\Services\ServerEvents;

use Prado\Caching\ICache;
use Prado\Web\Services\ServerEvents\TServerEventCacheBroker;

/**
 * A cache broker over a TServerEventArrayCache.
 */
class TServerEventTestCacheBroker extends TServerEventCacheBroker
{
	public function __construct(public TServerEventArrayCache $cache)
	{
		parent::__construct();
	}

	protected function getCache(): ICache
	{
		return $this->cache;
	}
}
