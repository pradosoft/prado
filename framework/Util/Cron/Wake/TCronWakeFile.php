<?php

/**
 * TCronWakeFile class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron\Wake;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TCronWakeFile class.
 *
 * TCronWakeFile is the {@see \Prado\Util\Cron\Wake\ICronWakeTransport} that signals through a file:
 * a wake writes the current microtime into {@see setWakeFile WakeFile}, and the waiting cron
 * process polls the file's content every {@see setPollInterval PollInterval}.  The content is the
 * stamp rather than the modification time, which some file systems keep to the second.  On a
 * shared file system the wake reaches the cron process of another host.
 *
 * The default file is `cronwake` in the application runtime directory.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeFile extends TCronWakePoll
{
	/** The default wake file name in the runtime directory. */
	public const DEFAULT_FILE_NAME = 'cronwake';

	/** @var ?string the wake file path, or null for the default under the runtime path */
	private ?string $_wakeFile = null;

	/**
	 * @param ?string $wakeFile the wake file path, or null for the default under the runtime path
	 */
	public function __construct(?string $wakeFile = null)
	{
		$this->_wakeFile = $wakeFile;
		parent::__construct();
	}

	/**
	 * @return string the wake file path; defaults to `cronwake` in the application runtime directory
	 */
	public function getWakeFile(): string
	{
		if ($this->_wakeFile === null) {
			$this->_wakeFile = Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . static::DEFAULT_FILE_NAME;
		}
		return $this->_wakeFile;
	}

	/**
	 * @param string $value the wake file path, as a path or a namespace
	 * @return static the current object
	 */
	public function setWakeFile($value): static
	{
		$value = TPropertyValue::ensureString($value);
		$this->_wakeFile = Prado::getPathOfNamespace($value) ?? $value;
		return $this;
	}

	/**
	 * @return bool whether the wake file's directory exists
	 */
	protected function getIsAvailable(): bool
	{
		return is_dir(dirname($this->getWakeFile()));
	}

	/**
	 * @return ?string the wake file content, or null when the file is absent
	 */
	protected function readStamp(): mixed
	{
		$file = $this->getWakeFile();
		clearstatcache(true, $file);
		$content = @file_get_contents($file);
		return ($content === false) ? null : $content;
	}

	/**
	 * Writes the current microtime into the wake file.
	 * @return bool whether the file was written
	 */
	protected function writeStamp(): bool
	{
		return @file_put_contents($this->getWakeFile(), sprintf('%.6F', \microtime(true)), LOCK_EX) !== false;
	}
}
