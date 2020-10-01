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

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;

/**
 * TFirebugLogRoute class.
 *
 * TFirebugLogRoute prints selected log messages in the firebug log console.
 *
 * {@link http://www.getfirebug.com/ FireBug Website}
 *
 * @author Enrico Stahn <mail@enricostahn.com>, Christophe Boulain <Christophe.Boulain@gmail.com>
 * @package Prado\Util
 * @since 3.1.2
 */
class TFirebugLogRoute extends TBrowserLogRoute
{
	public function processLogs($logs)
	{
		$page = $this->getService()->getRequestedPage();
		if (empty($logs) || $this->getApplication()->getMode() === 'Performance') {
			return;
		}
		$first = $logs[0][3];
		$even = true;

		$blocks = [['info', 'Tot Time', 'Time    ', '[Level] [Category] [Message]']];
		for ($i = 0, $n = count($logs); $i < $n; ++$i) {
			if ($i < $n - 1) {
				$timing['delta'] = $logs[$i + 1][3] - $logs[$i][3];
				$timing['total'] = $logs[$i + 1][3] - $first;
			} else {
				$timing['delta'] = '?';
				$timing['total'] = $logs[$i][3] - $first;
			}
			$timing['even'] = !($even = !$even);
			$blocks[] = $this->renderMessageCallback($logs[$i], $timing);
		}

		try {
			$blocks = TJavaScript::jsonEncode($blocks);
		} catch (Exception $e) {
			// strip everythin not 7bit ascii
			$blocks = preg_replace('/[^(\x20-\x7F)]*/', '', serialize($blocks));
		}

		// the response has already been flushed
		$response = $this->getApplication()->getResponse();
		if ($page->getIsCallback()) {
			$content = $response->createHtmlWriter();
			$content->getWriter()->setBoundary(TActivePageAdapter::CALLBACK_DEBUG_HEADER);
			$content->write($blocks);
			$response->write($content);
		}
	}

	protected function renderHeader()
	{
		$page = $this->getService()->getRequestedPage();
		if ($page->getIsCallback()) {
			$string = <<<EOD

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

		return $string;
	}

	protected function renderMessage($log, $info)
	{
		$logfunc = 'console.' . $this->getFirebugLoggingFunction($log[1]);
		$total = sprintf('%0.6f', $info['total']);
		$delta = sprintf('%0.6f', $info['delta']);
		$msg = trim($this->formatLogMessage($log[0], $log[1], $log[2], ''));
		$msg = preg_replace('/\(line[^\)]+\)$/', '', $msg); //remove line number info
		$msg = "[{$total}] [{$delta}] " . $msg; // Add time spent and cumulated time spent
		$string = $logfunc . '(\'' . addslashes($msg) . '\');' . "\n";

		return $string;
	}

	protected function renderMessageCallback($log, $info)
	{
		$logfunc = $this->getFirebugLoggingFunction($log[1]);
		$total = sprintf('%0.6f', $info['total']);
		$delta = sprintf('%0.6f', $info['delta']);
		$msg = trim($this->formatLogMessage($log[0], $log[1], $log[2], ''));
		$msg = preg_replace('/\(line[^\)]+\)$/', '', $msg); //remove line number info

		return [$logfunc, $total, $delta, $msg];
	}

	protected function renderFooter()
	{
		$string = <<<EOD

	if(typeof console.groupEnd === "function")
		console.groupEnd();

}
</script>

EOD;

		return $string;
	}

	protected function getFirebugLoggingFunction($level)
	{
		switch ($level) {
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
