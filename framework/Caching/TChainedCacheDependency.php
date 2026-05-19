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
 * TChainedCacheDependency class.
 *
 * TChainedCacheDependency represents a list of cache dependency objects
 * and performs the dependency checking based on the checking results of
 * these objects. If any of them reports a dependency change, TChainedCacheDependency
 * will return true for the checking.
 *
 * To add dependencies to TChainedCacheDependency, use {@see \Prado\Caching\TChainedCacheDependency::getDependencies() Dependencies }
 * which gives a {@see \Prado\Caching\TCacheDependencyList} instance and can be used like an array
 * (see {@see \Prado\Collections\TList} for more details).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TChainedCacheDependency extends TCacheDependency
{
	private $_dependencies;

	/**
	 * @return TCacheDependencyList list of dependency objects
	 */
	public function getDependencies()
	{
		if ($this->_dependencies === null) {
			$this->_dependencies = new TCacheDependencyList();
		}
		return $this->_dependencies;
	}

	/**
	 * @return bool true if any dependency in the chain has changed.
	 */
	public function getHasChanged()
	{
		if ($this->_dependencies !== null) {
			foreach ($this->_dependencies as $dependency) {
				if ($dependency->getHasChanged()) {
					return true;
				}
			}
		}
		return false;
	}
}
