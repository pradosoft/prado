<?php

/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * TFirePhpLogRoute class.
 *
 * TFirePhpLogRoute prints log messages in the firebug log console via firephp.
 *
 * {@see http://www.getfirebug.com/ FireBug Website}
 * {@see http://www.firephp.org/ FirePHP Website}
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @since 3.1.5
 */
class TFirePhpLogRoute extends TLogRoute implements IOutputLogRoute
{
	/**
	 * Default group label
	 */
	public const DEFAULT_LABEL = TLogRouter::class . '(TFirePhpLogRoute)';

	private $_groupLabel;

	/**
	 * Logs via FirePhp.
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		if (empty($logs) || $this->getApplication()->getMode() === 'Performance') {
			return;
		}

		if (headers_sent()) {
			echo '
				<div style="width:100%; background-color:darkred; color:#FFF; padding:2px">
					TFirePhpLogRoute.GroupLabel "<i>' . $this -> getGroupLabel() . '</i>" -
					Routing to FirePHP impossible, because headers already sent!
				</div>
			';
			$fallback = new TBrowserLogRoute();
			$fallback->processLogs($logs, $final, $meta);
			return;
		}

		$firephp = \FirePHP::getInstance(true);
		$firephp->setOptions(['useNativeJsonEncode' => false]);
		$firephp->group($this->getGroupLabel(), ['Collapsed' => true]);
		$firephp->log('Time,  Message');

		$startTime = $_SERVER["REQUEST_TIME_FLOAT"];
		$c = count($logs);
		for ($i = 0, $n = $c; $i < $n; ++$i) {
			$message = $logs[$i][TLogger::LOG_MESSAGE];
			$level = $logs[$i][TLogger::LOG_LEVEL];
			$category = $logs[$i][TLogger::LOG_CATEGORY];
			$delta = $logs[$i]['delta'];

			$message = sPrintF('+%0.6f: %s', $delta, preg_replace('/\(line[^\)]+\)$/', '', $message));
			$firephp->fb($message, $category, self::translateLogLevel($level));
		}
		$firephp->log(sPrintF('%0.6f', $meta['total']), 'Cumulated Time');
		$firephp->groupEnd();
	}

	/**
	 * Translates a PRADO log level attribute into one understood by FirePHP for correct visualization
	 * @param string $level prado log level
	 * @return string FirePHP log level
	 */
	protected static function translateLogLevel($level)
	{
		switch ($level) {
			case TLogger::INFO:
				return \FirePHP::INFO;
			case TLogger::PROFILE:
			case TLogger::PROFILE_BEGIN:
			case TLogger::PROFILE_END:
			case TLogger::DEBUG:
			case TLogger::NOTICE:
				return \FirePHP::LOG;
			case TLogger::WARNING:
				return \FirePHP::WARN;
			case TLogger::ERROR:
			case TLogger::ALERT:
			case TLogger::FATAL:
				return \FirePHP::ERROR;
			default:
				return \FirePHP::LOG;
		}
	}

	/**
	 * @return string group label. Defaults to TFirePhpLogRoute::DEFAULT_LABEL
	 */
	public function getGroupLabel()
	{
		if ($this->_groupLabel === null) {
			$this->_groupLabel = self::DEFAULT_LABEL;
		}

		return $this->_groupLabel;
	}

	/**
	 * @param string $value group label.
	 * @return static The current object.
	 */
	public function setGroupLabel($value): static
	{
		$this->_groupLabel = $value;
		return $this;
	}
}
