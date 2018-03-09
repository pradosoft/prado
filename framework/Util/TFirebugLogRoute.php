<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

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
	protected function renderHeader()
	{
		$string = <<<EOD

<script type="text/javascript">
/*<![CDATA[*/
if (typeof(console) == 'object')
{
	console.log ("[Cumulated Time] [Time] [Level] [Category] [Message]");

EOD;

		return $string;
	}

	protected function renderMessage($log, $info)
	{
		$logfunc = $this->getFirebugLoggingFunction($log[1]);
		$total = sprintf('%0.6f', $info['total']);
		$delta = sprintf('%0.6f', $info['delta']);
		$msg = trim($this->formatLogMessage($log[0], $log[1], $log[2], ''));
		$msg = preg_replace('/\(line[^\)]+\)$/', '', $msg); //remove line number info
		$msg = "[{$total}] [{$delta}] " . $msg; // Add time spent and cumulated time spent
		$string = $logfunc . '(\'' . addslashes($msg) . '\');' . "\n";

		return $string;
	}


	protected function renderFooter()
	{
		$string = <<<EOD

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
				return 'console.log';
			case TLogger::WARNING:
				return 'console.warn';
			case TLogger::ERROR:
			case TLogger::ALERT:
			case TLogger::FATAL:
				return 'console.error';
			default:
				return 'console.log';
		}
	}
}
