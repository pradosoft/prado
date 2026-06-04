<?php

/**
 * TTestGlobalStateCacheDependency class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TGlobalStateCacheDependency;

/**
 * TTestGlobalStateCacheDependency is a {@see TGlobalStateCacheDependency} harness exposing
 * its protected state-name accessor seams.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestGlobalStateCacheDependency extends TGlobalStateCacheDependency
{
	public function pubGetStateNameDirect(): string
	{
		return $this->getStateNameDirect();
	}

	public function pubSetStateNameDirect(string $value): void
	{
		$this->setStateNameDirect($value);
	}
}
