<?php

/**
 * TTestDirectoryCacheDependency class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TDirectoryCacheDependency;

/**
 * TTestDirectoryCacheDependency is a {@see TDirectoryCacheDependency} harness exposing its
 * protected directory accessor, validation, and timestamp-generation seams.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestDirectoryCacheDependency extends TDirectoryCacheDependency
{
	public function pubGetDirectoryDirect(): ?string
	{
		return $this->getDirectoryDirect();
	}

	public function pubSetDirectoryDirect(?string $value): void
	{
		$this->setDirectoryDirect($value);
	}

	public function pubValidateFile(string $fileName): bool
	{
		return $this->validateFile($fileName);
	}

	public function pubValidateDirectory(string $directory): bool
	{
		return $this->validateDirectory($directory);
	}

	public function pubGenerateTimestamps(string $directory, int $level = 0): array
	{
		return $this->generateTimestamps($directory, $level);
	}
}
