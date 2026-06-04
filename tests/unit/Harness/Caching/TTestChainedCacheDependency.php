<?php

/**
 * TTestChainedCacheDependency class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TCacheDependencyList;
use Prado\Caching\TChainedCacheDependency;

/**
 * TTestChainedCacheDependency is a {@see TChainedCacheDependency} harness exposing its
 * protected dependency-list factory and `*Direct` accessor seams.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestChainedCacheDependency extends TChainedCacheDependency
{
	public function pubNewCacheDependencyList(): TCacheDependencyList
	{
		return $this->newCacheDependencyList();
	}

	public function pubGetDependenciesDirect(): ?TCacheDependencyList
	{
		return $this->getDependenciesDirect();
	}

	public function pubSetDependenciesDirect(?TCacheDependencyList $value): void
	{
		$this->setDependenciesDirect($value);
	}
}
