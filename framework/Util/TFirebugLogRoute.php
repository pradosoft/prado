<?php

/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;

/**
 * TFirebugLogRoute class.
 *
 * TFirebugLogRoute prints selected log messages in the firebug log console.
 *
 * {@see http://www.getfirebug.com/ FireBug Website}
 *
 * @author Enrico Stahn <mail@enricostahn.com>, Christophe Boulain <Christophe.Boulain@gmail.com>
 * @since 3.1.2
 * @method \Prado\Web\Services\TPageService getService()
 */
class TFirebugLogRoute extends TBrowserLogRoute
{
	/**
	 * Logs via Firebug.
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$page = $this->getService()->getRequestedPage();
		if (empty($logs) || $this->getApplication()->getMode() === \Prado\TApplicationMode::Performance) {
			return;
		}
		$even = false;

		$blocks = [['info', 'Tot Time', 'Time    ', '[Level] [Category] [Message]']];
		for ($i = 0, $n = count($logs); $i < $n; ++$i) {
			$logs[$i]['even'] = ($even = !$even);
			$blocks[] = $this->renderMessageCallback($logs[$i]);
		}

		try {
			$blocks = TJavaScript::jsonEncode($blocks);
		} catch (\Exception $e) {
			// strip everythin not 7bit ascii
			$blocks = preg_replace('/[^(\x20-\x7F)]*/', '', serialize($blocks));
		}

		// the response has already been flushed
		$response = $this->getApplication()->getResponse();
		if ($page->getIsCallback()) {
			$content = $response->createHtmlWriter();
			$content->getWriter()->setBoundary(TActivePageAdapter::CALLBACK_DEBUG_HEADER);
			$content->write($blocks);
			$response->write($content->flush());
		}
	}

	protected function renderHeader()
	{
		$page = $this->getService()->getRequestedPage();
		if ($page->getIsCallback()) {
			return
<<<EOD

	<script>
	/*<![CDATA[*/
	if (typeof(console) == 'object')
	{
		var groupFunc = blocks.length < 10 ? 'group': 'groupCollapsed';
		if(typeof log[groupFunc] === "function")
			log[groupFunc]("Callback logs ("+blocks.length+" entries)");

		console.log ("[Tot Time] [Time    ] [Level] [Category] [Message]");

	EOD;
		}
		return '';
	}

	protected function renderMessage($log, $meta)
	{
		$logfunc = 'console.' . $this->getFirebugLoggingFunction($log[TLogger::LOG_LEVEL]);
		$total = sprintf('%0.6f', $log['total']);
		$delta = sprintf('%0.6f', $log['delta']);
		$msg = trim($this->formatFirebugLogMessage($log));
		$msg = preg_replace('/\(line[^\)]+\)$/', '', $msg); //remove line number info
		$msg = "[{$total}] [{$delta}] " . $msg; // Add time spent and cumulated time spent
		$string = $logfunc . '(\'' . addslashes($msg) . '\');' . "\n";

		return $string;
	}

	protected function renderMessageCallback($log)
	{
		$logfunc = $this->getFirebugLoggingFunction($log[TLogger::LOG_LEVEL]);
		$total = sprintf('%0.6f', $log['total']);
		$delta = sprintf('%0.6f', $log['delta']);
		$msg = trim($this->formatFirebugLogMessage($log));
		$msg = preg_replace('/\(line[^\)]+\)$/', '', $msg); //remove line number info

		return [$logfunc, $total, $delta, $msg];
	}

	protected function renderFooter()
	{
		$string =
<<<EOD
		if(typeof console.groupEnd === "function")
			console.groupEnd();

	}
	</script>

	EOD;

		return $string;
	}


	/**
	 * Formats a log message given different fields.
	 * @param array $log The log to format
	 * @return string formatted message
	 */
	public function formatFirebugLogMessage(array $log): string
	{
		$traces = [];
		if (!is_string($log[TLogger::LOG_MESSAGE])) {
			if ($log[TLogger::LOG_MESSAGE] instanceof \Exception || $log[TLogger::LOG_MESSAGE] instanceof \Throwable) {
				$log[TLogger::LOG_MESSAGE] = (string) $log[TLogger::LOG_MESSAGE];
			} else {
				$log[TLogger::LOG_MESSAGE] = \Prado\Util\TVarDumper::dump($log[TLogger::LOG_MESSAGE]);
			}
		}
		if (!is_string($log[TLogger::LOG_MESSAGE])) {
			if ($log[TLogger::LOG_MESSAGE] instanceof \Exception || $log[TLogger::LOG_MESSAGE] instanceof \Throwable) {
				$log[TLogger::LOG_MESSAGE] = (string) $log[TLogger::LOG_MESSAGE];
			} else {
				$log[TLogger::LOG_MESSAGE] = \Prado\Util\TVarDumper::dump($log[TLogger::LOG_MESSAGE]);
			}
		}
		if (isset($log[TLogger::LOG_TRACES])) {
			$traces = array_map(fn ($trace) => "in {$trace['file']}:{$trace['line']}", $log[TLogger::LOG_TRACES]);
		}
		return '[' . $this->getLevelName($log[TLogger::LOG_LEVEL]) . '] [' . $log[TLogger::LOG_CATEGORY] . '] ' . $log[TLogger::LOG_MESSAGE]
			. (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));
	}

	protected function getFirebugLoggingFunction($level)
	{
		switch ($level) {
			case TLogger::PROFILE:
			case TLogger::PROFILE_BEGIN:
			case TLogger::PROFILE_END:
			case TLogger::DEBUG:
			case TLogger::INFO:
			case TLogger::NOTICE:
				return 'info';
			case TLogger::WARNING:
				return 'warn';
			case TLogger::ERROR:
			case TLogger::ALERT:
			case TLogger::FATAL:
				return 'error';
			default:
				return 'log';
		}
	}
}
