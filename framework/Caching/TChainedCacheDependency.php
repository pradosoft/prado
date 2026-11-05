<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Prado;

/**
 * TChainedCacheDependency class
 *
 * TChainedCacheDependency represents a list of {@see \Prado\Caching\ICacheDependency}
 * objects and reports a dependency change when any one of them has changed.
 *
 * Dependencies are added via {@see getDependencies()}, which returns a
 * {@see \Prado\Caching\TCacheDependencyList} that can be used like an array
 * (see {@see \Prado\Collections\TList} for details). The list is created lazily
 * on first access.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TChainedCacheDependency extends TCacheDependency
{
	/** @var ?TCacheDependencyList lazily created list of chained dependencies */
	private ?TCacheDependencyList $_dependencies = null;

	/**
	 * Creates a new {@see \Prado\Caching\TCacheDependencyList} instance.
	 * Override in a subclass to return a custom list type.
	 * @return TCacheDependencyList a new dependency list.
	 * @since 4.4.0
	 */
	protected function newCacheDependencyList(): TCacheDependencyList
	{
		return Prado::createComponent(TCacheDependencyList::class);
	}

	/**
	 * Returns the stored dependency list without lazy initialization.
	 * @return ?TCacheDependencyList the stored list, or `null` if not yet created.
	 * @since 4.4.0
	 */
	protected function getDependenciesDirect(): ?TCacheDependencyList
	{
		return $this->_dependencies;
	}

	/**
	 * Stores the dependency list directly without side effects.
	 * @param ?TCacheDependencyList $value the list to store.
	 * @since 4.4.0
	 */
	protected function setDependenciesDirect(?TCacheDependencyList $value): void
	{
		$this->_dependencies = $value;
	}

	/**
	 * Returns the list of dependency objects, creating it on first access.
	 * @return TCacheDependencyList the dependency list.
	 */
	public function getDependencies(): TCacheDependencyList
	{
		if ($this->getDependenciesDirect() === null) {
			$this->setDependenciesDirect($this->newCacheDependencyList());
		}
		return $this->getDependenciesDirect();
	}

	/**
	 * @return bool whether any dependency in the chain has reported a change.
	 */
	public function getHasChanged(): bool
	{
		$dependencies = $this->getDependenciesDirect();
		if ($dependencies !== null) {
			foreach ($dependencies as $dependency) {
				if ($dependency->getHasChanged()) {
					return true;
				}
			}
		}
		return false;
	}
}
