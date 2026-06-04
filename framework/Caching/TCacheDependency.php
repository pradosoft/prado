<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\TApplicationComponent;

/**
 * TCacheDependency class
 *
 * TCacheDependency is the abstract base class for all cache dependency implementations.
 * Subclasses must implement {@see \Prado\Caching\ICacheDependency::getHasChanged()} to provide
 * the actual staleness-detection logic.
 *
 * Because dependency objects are often serialized so that they can persist across
 * requests, subclasses that hold resource handles (database connections, file handles,
 * and so on) must implement `__sleep()` and `__wakeup()` accordingly.
 *
 * The following dependency classes are included in PRADO:
 * - {@see \Prado\Caching\TFileCacheDependency}: reports a change when a file's mtime changes.
 * - {@see \Prado\Caching\TDirectoryCacheDependency}: reports a change when any file in a directory changes.
 * - {@see \Prado\Caching\TGlobalStateCacheDependency}: reports a change when a global application state changes.
 * - {@see \Prado\Caching\TApplicationStateCacheDependency}: reports a change when the application is not in performance mode.
 * - {@see \Prado\Caching\TChainedCacheDependency}: reports a change when any dependency in a list has changed.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
abstract class TCacheDependency extends TApplicationComponent implements ICacheDependency
{
}
