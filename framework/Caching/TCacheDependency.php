<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

/**
 * TCacheDependency class.
 *
 * TCacheDependency is the base class implementing {@link ICacheDependency} interface.
 * Descendant classes must implement {@link getHasChanged()} to provide
 * actual dependency checking logic.
 *
 * The property value of {@link getHasChanged HasChanged} tells whether
 * the dependency is changed or not.
 *
 * You may disable the dependency checking by setting {@link setEnabled Enabled}
 * to false.
 *
 * Note, since the dependency objects often need to be serialized so that
 * they can persist across requests, you may need to implement __sleep() and
 * __wakeup() if the dependency objects contain resource handles which are
 * not serializable.
 *
 * Currently, the following dependency classes are provided in the PRADO release:
 * - {@link TFileCacheDependency}: checks whether a file is changed or not
 * - {@link TDirectoryCacheDependency}: checks whether a directory is changed or not
 * - {@link TGlobalStateCacheDependency}: checks whether a global state is changed or not
 * - {@link TChainedCacheDependency}: checks whether any of a list of dependencies is changed or not
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.1.0
 */
abstract class TCacheDependency extends \Prado\TComponent implements ICacheDependency
{
}
