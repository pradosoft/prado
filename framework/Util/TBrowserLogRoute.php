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

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TBrowserLogRoute class.
 *
 * TBrowserLogRoute prints selected log messages in the response.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Util
 * @since 3.0
 */
class TBrowserLogRoute extends TLogRoute
{
	/**
	 * @var string css class for indentifying the table structure in the dom tree
	 */
	private $_cssClass;

	public function processLogs($logs)
	{
		if (empty($logs) || $this->getApplication()->getMode() === 'Performance') {
			return;
		}
		$first = $logs[0][3];
		$even = true;
		$response = $this->getApplication()->getResponse();
		$response->write($this->renderHeader());
		for ($i = 0, $n = count($logs); $i < $n; ++$i) {
			if ($i < $n - 1) {
				$timing['delta'] = $logs[$i + 1][3] - $logs[$i][3];
				$timing['total'] = $logs[$i + 1][3] - $first;
			} else {
				$timing['delta'] = '?';
				$timing['total'] = $logs[$i][3] - $first;
			}
			$timing['even'] = !($even = !$even);
			$response->write($this->renderMessage($logs[$i], $timing));
		}
		$response->write($this->renderFooter());
	}

	/**
	 * @param string $value the css class of the control
	 */
	public function setCssClass($value)
	{
		$this->_cssClass = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the css class of the control
	 */
	public function getCssClass()
	{
		return TPropertyValue::ensureString($this->_cssClass);
	}

	/**
	 * @return string
	 */
	protected function renderHeader()
	{
		$string = '';
		if ($className = $this->getCssClass()) {
			$string = <<<EOD
<table class="$className">
	<tr class="header">
		<th colspan="5">
			Application Log
		</th>
	</tr><tr class="description">
	    <th>&nbsp;</th>
		<th>Category</th><th>Message</th><th>Time Spent (s)</th><th>Cumulated Time Spent (s)</th>
	</tr>
EOD;
		} else {
			$string = <<<EOD
<table cellspacing="0" cellpadding="2" border="0" width="100%" style="table-layout:auto">
	<tr>
		<th style="background-color: black; color:white;" colspan="5">
			Application Log
		</th>
	</tr><tr style="background-color: #ccc; color:black">
	    <th style="width: 15px">&nbsp;</th>
		<th style="width: auto">Category</th><th style="width: auto">Message</th><th style="width: 120px">Time Spent (s)</th><th style="width: 150px">Cumulated Time Spent (s)</th>
	</tr>
EOD;
		}
		return $string;
	}

	/**
	 * @param $log
	 * @param $info
	 * @return string
	 */
	protected function renderMessage($log, $info)
	{
		$string = '';
		$total = sprintf('%0.6f', $info['total']);
		$delta = sprintf('%0.6f', $info['delta']);
		$msg = preg_replace('/\(line[^\)]+\)$/', '', $log[0]); //remove line number info
		$msg = THttpUtility::htmlEncode($msg);
		if ($this->getCssClass()) {
			$colorCssClass = $log[1];
			$messageCssClass = $info['even'] ? 'even' : 'odd';
			$string = <<<EOD
	<tr class="message level$colorCssClass $messageCssClass">
		<td class="code">&nbsp;</td>
		<td class="category">{$log[2]}</td>
		<td class="message">{$msg}</td>
		<td class="time">{$delta}</td>
		<td class="cumulatedtime">{$total}</td>
	</tr>
EOD;
		} else {
			$bgcolor = $info['even'] ? "#fff" : "#eee";
			$color = $this->getColorLevel($log[1]);
			$string = <<<EOD
	<tr style="background-color: {$bgcolor}; color:#000">
		<td style="border:1px solid silver;background-color: $color;">&nbsp;</td>
		<td>{$log[2]}</td>
		<td>{$msg}</td>
		<td style="text-align:center">{$delta}</td>
		<td style="text-align:center">{$total}</td>
	</tr>
EOD;
		}
		return $string;
	}

	protected function getColorLevel($level)
	{
		switch ($level) {
			case TLogger::DEBUG: return 'green';
			case TLogger::INFO: return 'black';
			case TLogger::NOTICE: return '#3333FF';
			case TLogger::WARNING: return '#33FFFF';
			case TLogger::ERROR: return '#ff9933';
			case TLogger::ALERT: return '#ff00ff';
			case TLogger::FATAL: return 'red';
		}
		return '';
	}

	protected function renderFooter()
	{
		$string = '';
		if ($this->getCssClass()) {
			$string .= '<tr class="footer"><td colspan="5">';
			foreach (self::$_levelValues as $name => $level) {
				$string .= '<span class="level' . $level . '">' . strtoupper($name) . "</span>";
			}
		} else {
			$string .= "<tr><td colspan=\"5\" style=\"text-align:center; background-color:black; border-top: 1px solid #ccc; padding:0.2em;\">";
			foreach (self::$_levelValues as $name => $level) {
				$string .= "<span style=\"color:white; border:1px solid white; background-color:" . $this->getColorLevel($level);
				$string .= ";margin: 0.5em; padding:0.01em;\">" . strtoupper($name) . "</span>";
			}
		}
		$string .= '</td></tr></table>';
		return $string;
	}
}
