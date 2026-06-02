<?php

/**
 * TTestFileCacheDependency class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TFileCacheDependency;

/**
 * TTestFileCacheDependency is a {@see TFileCacheDependency} harness exposing its protected
 * file-name and timestamp seams.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestFileCacheDependency extends TFileCacheDependency
{
	public function pubSetFileNameDirect($value): void
	{
		$this->setFileNameDirect($value);
	}

	public function pubSetTimestamp($value): void
	{
		$this->setTimestamp($value);
	}
}
