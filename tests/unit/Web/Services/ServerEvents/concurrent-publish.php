<?php

/**
 * Child process of TServerEventCacheBrokerTest: publishes events on one channel through a
 * TServerEventCacheBroker with its default mutex, starting at a shared time so the
 * publishes of several processes overlap.
 *
 * Arguments: autoload file, application base path, cache type (`file` or `db`), cache
 * location (a directory or an sqlite file), event count, worker name, start time.
 */

use Prado\Caching\ICache;
use Prado\Caching\TDbCache;
use Prado\Caching\TFileCache;
use Prado\TApplication;
use Prado\Web\Services\ServerEvents\TServerEventCacheBroker;

[, $autoload, $basePath, $type, $location, $count, $worker, $start] = $argv;
require $autoload;

new TApplication($basePath, false);

if ($type === 'file') {
	$cache = new TFileCache($location);
} else {
	$cache = new TDbCache();
	$cache->setConnectionString('sqlite:' . $location);
}

$broker = new class ($cache) extends TServerEventCacheBroker {
	public function __construct(private ICache $eventCache)
	{
		parent::__construct();
	}

	protected function getCache(): ICache
	{
		return $this->eventCache;
	}
};
$broker->setBufferSize(10000);
$broker->setLockTimeout(30);

while (microtime(true) < (float) $start) {
	usleep(1000);
}
for ($i = 0; $i < (int) $count; $i++) {
	$broker->publish('race', 'e', $worker . ':' . $i);
}
echo 'ok';
