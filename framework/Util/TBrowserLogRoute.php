<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
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
 * @since 3.0
 */
class TBrowserLogRoute extends TLogRoute implements IOutputLogRoute
{
	/**
	 * @var string css class for identifying the table structure in the dom tree
	 */
	private $_cssClass;
	/**
	 * @var bool colorize the deltas.
	 * @since 4.2.3
	 */
	private bool $_colorizeDelta = true;
	/**
	 * @var bool add the prefix to the message
	 * @since 4.2.3
	 */
	private bool $_addPrefix = false;

	/**
	 * Renders the logs in HTML to the browser.
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$app = $this->getApplication();
		if (empty($logs) || $app->getMode() === 'Performance' || $app instanceof \Prado\Shell\TShellApplication) {
			return;
		}
		$response = $this->getApplication()->getResponse();
		$response->write($this->renderHeader());
		$even = false;
		for ($i = 0, $n = count($logs); $i < $n; ++$i) {
			$logs[$i]['even'] = ($even = !$even);
			$response->write($this->renderMessage($logs[$i], $meta));
		}
		$response->write($this->renderFooter());
	}

	/**
	 * @return string the css class of the control
	 */
	public function getCssClass()
	{
		return $this->_cssClass;
	}

	/**
	 * @param string $value the css class of the control
	 */
	public function setCssClass($value): static
	{
		$this->_cssClass = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return bool Colorize the Deltas
	 * @since 4.2.3
	 */
	public function getColorizeDelta(): bool
	{
		return $this->_colorizeDelta;
	}

	/**
	 * @param bool|string $value Colorize the Deltas
	 * @since 4.2.3
	 */
	public function setColorizeDelta($value): static
	{
		$this->_colorizeDelta = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return bool Adds the prefix to the message
	 * @since 4.2.3
	 */
	public function getAddPrefix(): bool
	{
		return $this->_addPrefix;
	}

	/**
	 * @param bool|string $value Adds the prefix to the message
	 * @since 4.2.3
	 */
	public function setAddPrefix($value): static
	{
		$this->_addPrefix = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return string
	 */
	protected function renderHeader()
	{
		$string = '';
		if ($className = $this->getCssClass()) {
			$string =
<<<EOD
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
			$string =
<<<EOD
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
	 * @param array $log
	 * @param array $meta
	 * @return string
	 * @author Brad Anderson <belisoful@icloud.com> Colorization/weight of time deltas.
	 */
	protected function renderMessage($log, $meta)
	{
		$string = '';
		$total = sprintf('%0.6f', $log['total']);
		$delta = sprintf('%0.6f', $log['delta']);
		if ($this->_addPrefix) {
			$msg = $this->formatLogMessage($log);
		} else {
			$msg = $log[0];
		}
		$msg = preg_replace('/\(line[^\)]+\)$/', '', $msg); //remove line number info

		$msg = THttpUtility::htmlEncode($msg);
		if ($this->getCssClass()) {
			$colorCssClass = $log[TLogger::LOG_LEVEL];
			$messageCssClass = $log['even'] ? 'even' : 'odd';
			$category = $log[TLogger::LOG_CATEGORY];
			$string = <<<EOD
					<tr class="message level$colorCssClass $messageCssClass">
						<td class="code">&nbsp;</td>
						<td class="category">{$category}</td>
						<td class="message">{$msg}</td>
						<td class="time">{$delta}</td>
						<td class="cumulatedtime">{$total}</td>
					</tr>
				EOD;
		} else {
			$bgcolor = $log['even'] ? "#fff" : "#e8e8e8";
			if ($this->getColorizeDelta()) {
				$normalizedTime = $delta / ($meta['maxdelta'] === 0.0 ? 1 : $meta['maxdelta']);
				$textColor = 'color:' . TPropertyValue::ensureHexColor(static::getLogColor($normalizedTime));
				$weightCutOff = 0.75;
				$weightLightCutOff = 0.4;
				if ($normalizedTime > $weightCutOff) {
					$weight = '; font-weight: ' . round(400 + 500 * ($normalizedTime - $weightCutOff) / (1 - $weightCutOff));
				} elseif($normalizedTime < $weightLightCutOff) {
					$weight = '; font-weight: ' . round(400 - 300 * ($weightLightCutOff - $normalizedTime) / ($weightLightCutOff));
				} else {
					$weight = '';
				}
			} else {
				$textColor = '';
				$weight = '';
			}

			$color = $this->getColorLevel($log[TLogger::LOG_LEVEL]);
			$category = $log[TLogger::LOG_CATEGORY];
			$string = <<<EOD
					<tr style="background-color: {$bgcolor}; color:#000">
						<td style="border:1px solid silver;background-color: {$color}">&nbsp;</td>
						<td>{$category}</td>
						<td>{$msg}</td>
						<td style="text-align:center; {$textColor}{$weight}">{$delta}</td>
						<td style="text-align:center">{$total}</td>
					</tr>
				EOD;
		}
		return $string;
	}

	protected function getColorLevel($level)
	{
		switch ($level) {
			case TLogger::PROFILE:
			case TLogger::PROFILE_BEGIN_SELECT:
			case TLogger::PROFILE_BEGIN:
			case TLogger::PROFILE_END_SELECT:
			case TLogger::PROFILE_END: return 'lime';
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
