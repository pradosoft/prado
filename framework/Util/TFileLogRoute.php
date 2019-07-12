<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TFileLogRoute class.
 *
 * TFileLogRoute records log messages in files.
 * The log files are stored under {@link setLogPath LogPath} and the file name
 * is specified by {@link setLogFile LogFile}. If the size of the log file is
 * greater than {@link setMaxFileSize MaxFileSize} (in kilo-bytes), a rotation
 * is performed, which renames the current log file by suffixing the file name
 * with '.1'. All existing log files are moved backwards one place, i.e., '.2'
 * to '.3', '.1' to '.2'. The property {@link setMaxLogFiles MaxLogFiles}
 * specifies how many files to be kept.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Util
 * @since 3.0
 */
class TFileLogRoute extends TLogRoute
{
	/**
	 * @var int maximum log file size
	 */
	private $_maxFileSize = 512; // in KB
	/**
	 * @var int number of log files used for rotation
	 */
	private $_maxLogFiles = 2;
	/**
	 * @var string directory storing log files
	 */
	private $_logPath;
	/**
	 * @var string log file name
	 */
	private $_logFile = 'prado.log';

	/**
	 * @return string directory storing log files. Defaults to application runtime path.
	 */
	public function getLogPath()
	{
		if ($this->_logPath === null) {
			$this->_logPath = $this->getApplication()->getRuntimePath();
		}
		return $this->_logPath;
	}

	/**
	 * @param string $value directory (in namespace format) storing log files.
	 * @throws TConfigurationException if log path is invalid
	 */
	public function setLogPath($value)
	{
		if (($this->_logPath = Prado::getPathOfNamespace($value)) === null || !is_dir($this->_logPath) || !is_writable($this->_logPath)) {
			throw new TConfigurationException('filelogroute_logpath_invalid', $value);
		}
	}

	/**
	 * @return string log file name. Defaults to 'prado.log'.
	 */
	public function getLogFile()
	{
		return $this->_logFile;
	}

	/**
	 * @param string $value log file name
	 */
	public function setLogFile($value)
	{
		$this->_logFile = $value;
	}

	/**
	 * @return int maximum log file size in kilo-bytes (KB). Defaults to 1024 (1MB).
	 */
	public function getMaxFileSize()
	{
		return $this->_maxFileSize;
	}

	/**
	 * @param int $value maximum log file size in kilo-bytes (KB).
	 * @throws TInvalidDataValueException if the value is smaller than 1.
	 */
	public function setMaxFileSize($value)
	{
		$this->_maxFileSize = TPropertyValue::ensureInteger($value);
		if ($this->_maxFileSize <= 0) {
			throw new TInvalidDataValueException('filelogroute_maxfilesize_invalid');
		}
	}

	/**
	 * @return int number of files used for rotation. Defaults to 2.
	 */
	public function getMaxLogFiles()
	{
		return $this->_maxLogFiles;
	}

	/**
	 * @param int $value number of files used for rotation.
	 */
	public function setMaxLogFiles($value)
	{
		$this->_maxLogFiles = TPropertyValue::ensureInteger($value);
		if ($this->_maxLogFiles < 1) {
			throw new TInvalidDataValueException('filelogroute_maxlogfiles_invalid');
		}
	}

	/**
	 * Saves log messages in files.
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs)
	{
		$logFile = $this->getLogPath() . DIRECTORY_SEPARATOR . $this->getLogFile();
		if (@filesize($logFile) > $this->_maxFileSize * 1024) {
			$this->rotateFiles();
		}
		foreach ($logs as $log) {
			error_log($this->formatLogMessage($log[0], $log[1], $log[2], $log[3]), 3, $logFile);
		}
	}

	/**
	 * Rotates log files.
	 */
	protected function rotateFiles()
	{
		$file = $this->getLogPath() . DIRECTORY_SEPARATOR . $this->getLogFile();
		for ($i = $this->_maxLogFiles; $i > 0; --$i) {
			$rotateFile = $file . '.' . $i;
			if (is_file($rotateFile)) {
				if ($i === $this->_maxLogFiles) {
					unlink($rotateFile);
				} else {
					rename($rotateFile, $file . '.' . ($i + 1));
				}
			}
		}
		if (is_file($file)) {
			rename($file, $file . '.1');
		}
	}
}
