<?php

/**
 * TTestUrlAssetManager class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web;

use Prado\Web\TAssetManager;

/**
 * TTestUrlAssetManager returns a URL under `/assets` for a published path and copies no files.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestUrlAssetManager extends TAssetManager
{
	/**
	 * @return string `/assets`
	 */
	public function getBaseUrl()
	{
		return '/assets';
	}

	/**
	 * @return string the system temporary directory
	 */
	public function getBasePath()
	{
		return sys_get_temp_dir();
	}

	/**
	 * @param string $path the path to publish
	 * @return string the base name of the path in the base path
	 */
	public function getPublishedPath($path)
	{
		return $this->getBasePath() . DIRECTORY_SEPARATOR . basename($path);
	}

	/**
	 * @param string $path the path to publish
	 * @param bool $checkTimestamp unused
	 * @return string `/assets/` and the base name of the path
	 */
	public function publishFilePath($path, $checkTimestamp = false)
	{
		return '/assets/' . basename($path);
	}
}
