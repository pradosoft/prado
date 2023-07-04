<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

/**
 * TCacheDependency class.
 *
 * TCacheDependency is the base class implementing {@see \Prado\Caching\ICacheDependency} interface.
 * Descendant classes must implement {@see \Prado\Caching\ICacheDependency::getHasChanged()} to provide
 * actual dependency checking logic.
 *
 * The property value of {@see \Prado\Caching\ICacheDependency::getHasChanged() HasChanged} tells whether
 * the dependency is changed or not.
 *
 * You may disable the dependency checking by setting {@see setEnabled() Enabled}
 * to false.
 *
 * Note, since the dependency objects often need to be serialized so that
 * they can persist across requests, you may need to implement __sleep() and
 * __wakeup() if the dependency objects contain resource handles which are
 * not serializable.
 *
 * Currently, the following dependency classes are provided in the PRADO release:
 * - {@see \Prado\Caching\TFileCacheDependency}: checks whether a file is changed or not
 * - {@see \Prado\Caching\TDirectoryCacheDependency}: checks whether a directory is changed or not
 * - {@see \Prado\Caching\TGlobalStateCacheDependency}: checks whether a global state is changed or not
 * - {@see \Prado\Caching\TChainedCacheDependency}: checks whether any of a list of dependencies is changed or not
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
abstract class TCacheDependency extends \Prado\TComponent implements ICacheDependency
{
}
