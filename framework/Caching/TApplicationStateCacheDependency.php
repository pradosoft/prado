<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\TApplicationMode;

/**
 * TApplicationStateCacheDependency class
 *
 * TApplicationStateCacheDependency reports a cache-dependency change whenever
 * the application is not running in {@see \Prado\TApplicationMode::Performance} mode.
 * In performance mode the dependency is reported as unchanged, allowing cached
 * items to be reused without further staleness checks.
 *
 * Combine this dependency with others via {@see \Prado\Caching\TChainedCacheDependency}
 * to gate the remaining checks behind the performance-mode bypass.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TApplicationStateCacheDependency extends TCacheDependency
{
	/**
	 * @return bool `false` when the application is in performance mode (dependency
	 *   has not changed); `true` for all other modes (dependency has changed).
	 */
	public function getHasChanged(): bool
	{
		return $this->getApplication()->getMode() !== TApplicationMode::Performance;
	}
}
