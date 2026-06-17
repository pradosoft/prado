<?php

/**
 * TTestCacheStub class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/../Traits/TCallCollectorTrait.php';

use Prado\Caching\ICache;

/**
 * TTestCacheStub is an in-memory recording {@see ICache} for unit tests.
 *
 * Every method records its call through {@see TCallCollectorTrait}, so tests
 * can assert cache short-circuit and write-back behavior (for example
 * {@see \Prado\Util\TComposerReflection::loadInstalledPackages}) via
 * {@see getCollectedCalls()} and {@see getCollectedCallCount()}.
 *
 * {@see $getReturn} controls the value returned by {@see get()}; `false` models a
 * cache miss. When {@see $getReturn} is a {@see \Closure} it is invoked with the
 * requested id, so a test can compute the value per key.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestCacheStub implements ICache
{
	use TCallCollectorTrait;

	/** @var mixed Value returned by get(); a Closure is invoked with the id; false models a miss. */
	public mixed $getReturn = false;

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function get($id)
	{
		$this->collectCall();
		return $this->getReturn instanceof \Closure ? ($this->getReturn)($id) : $this->getReturn;
	}

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->collectCall();
		return true;
	}

	public function add($id, $value, $expire = 0, $dependency = null)
	{
		$this->collectCall();
		return true;
	}

	public function delete($id)
	{
		$this->collectCall();
		return true;
	}

	public function flush()
	{
		$this->collectCall();
		return true;
	}
}
