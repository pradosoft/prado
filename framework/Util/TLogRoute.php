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

/**
 * TLogRoute class.
 *
 * TLogRoute is the base class for all log route classes.
 * A log route object retrieves log messages from a logger and sends it
 * somewhere, such as files, emails.
 * The messages being retrieved may be filtered first before being sent
 * to the destination. The filters include log level filter and log category filter.
 *
 * To specify level filter, set {@link setLevels Levels} property,
 * which takes a string of comma-separated desired level names (e.g. 'Error, Debug').
 * To specify category filter, set {@link setCategories Categories} property,
 * which takes a string of comma-separated desired category names (e.g. 'Prado\Web, Prado\IO').
 *
 * Level filter and category filter are combinational, i.e., only messages
 * satisfying both filter conditions will they be returned.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Util
 * @since 3.0
 */
abstract class TLogRoute extends \Prado\TApplicationComponent
{
	/**
	 * @var array lookup table for level names
	 */
	protected static $_levelNames = [
		TLogger::DEBUG => 'Debug',
		TLogger::INFO => 'Info',
		TLogger::NOTICE => 'Notice',
		TLogger::WARNING => 'Warning',
		TLogger::ERROR => 'Error',
		TLogger::ALERT => 'Alert',
		TLogger::FATAL => 'Fatal'
	];
	/**
	 * @var array lookup table for level values
	 */
	protected static $_levelValues = [
		'debug' => TLogger::DEBUG,
		'info' => TLogger::INFO,
		'notice' => TLogger::NOTICE,
		'warning' => TLogger::WARNING,
		'error' => TLogger::ERROR,
		'alert' => TLogger::ALERT,
		'fatal' => TLogger::FATAL
	];
	/**
	 * @var int log level filter (bits)
	 */
	private $_levels;
	/**
	 * @var array log category filter
	 */
	private $_categories;

	/**
	 * Initializes the route.
	 * @param TXmlElement $config configurations specified in {@link TLogRouter}.
	 */
	public function init($config)
	{
	}

	/**
	 * @return int log level filter
	 */
	public function getLevels()
	{
		return $this->_levels;
	}

	/**
	 * @param int|string $levels integer log level filter (in bits). If the value is
	 * a string, it is assumed to be comma-separated level names. Valid level names
	 * include 'Debug', 'Info', 'Notice', 'Warning', 'Error', 'Alert' and 'Fatal'.
	 */
	public function setLevels($levels)
	{
		if (is_int($levels)) {
			$this->_levels = $levels;
		} else {
			$this->_levels = null;
			$levels = strtolower($levels);
			foreach (explode(',', $levels) as $level) {
				$level = trim($level);
				if (isset(self::$_levelValues[$level])) {
					$this->_levels |= self::$_levelValues[$level];
				}
			}
		}
	}

	/**
	 * @return array list of categories to be looked for
	 */
	public function getCategories()
	{
		return $this->_categories;
	}

	/**
	 * @param array|string $categories list of categories to be looked for. If the value is a string,
	 * it is assumed to be comma-separated category names.
	 */
	public function setCategories($categories)
	{
		if (is_array($categories)) {
			$this->_categories = $categories;
		} else {
			$this->_categories = null;
			foreach (explode(',', $categories) as $category) {
				if (($category = trim($category)) !== '') {
					$this->_categories[] = $category;
				}
			}
		}
	}

	/**
	 * @param int $level level value
	 * @return string level name
	 */
	protected function getLevelName($level)
	{
		return isset(self::$_levelNames[$level]) ? self::$_levelNames[$level] : 'Unknown';
	}

	/**
	 * @param string $level level name
	 * @return int level value
	 */
	protected function getLevelValue($level)
	{
		return isset(self::$_levelValues[$level]) ? self::$_levelValues[$level] : 0;
	}

	/**
	 * Formats a log message given different fields.
	 * @param string $message message content
	 * @param int $level message level
	 * @param string $category message category
	 * @param int $time timestamp
	 * @return string formatted message
	 */
	protected function formatLogMessage($message, $level, $category, $time)
	{
		return @date('M d H:i:s', $time) . ' [' . $this->getLevelName($level) . '] [' . $category . '] ' . $message . "\n";
	}

	/**
	 * Retrieves log messages from logger to log route specific destination.
	 * @param TLogger $logger logger instance
	 */
	public function collectLogs(TLogger $logger)
	{
		$logs = $logger->getLogs($this->getLevels(), $this->getCategories());
		if (!empty($logs)) {
			$this->processLogs($logs);
		}
	}

	/**
	 * Processes log messages and sends them to specific destination.
	 * Derived child classes must implement this method.
	 * @param array $logs list of messages.  Each array elements represents one message
	 * with the following structure:
	 * array(
	 *   [0] => message
	 *   [1] => level
	 *   [2] => category
	 *   [3] => timestamp);
	 */
	abstract protected function processLogs($logs);
}
