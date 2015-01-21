<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * TFirePhpLogRoute class.
 *
 * TFirePhpLogRoute prints log messages in the firebug log console via firephp.
 *
 * {@link http://www.getfirebug.com/ FireBug Website}
 * {@link http://www.firephp.org/ FirePHP Website}
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @package Prado\Util
 * @since 3.1.5
 */
class TFirePhpLogRoute extends TLogRoute
{
	/**
	 * Default group label
	 */
	const DEFAULT_LABEL = 'System.Util.TLogRouter(TFirePhpLogRoute)';

	private $_groupLabel = null;

	public function processLogs($logs)
	{
		if(empty($logs) || $this->getApplication()->getMode()==='Performance')
			return;

		if(headers_sent()) {
			echo '
				<div style="width:100%; background-color:darkred; color:#FFF; padding:2px">
					TFirePhpLogRoute.GroupLabel "<i>' . $this -> getGroupLabel() . '</i>" -
					Routing to FirePHP impossible, because headers already sent!
				</div>
			';
			$fallback = new TBrowserLogRoute();
			$fallback->processLogs($logs);
			return;
		}

		require_once Prado::getPathOfNamespace('System.3rdParty.FirePHPCore') . '/FirePHP.class.php';
		$firephp = FirePHP::getInstance(true);
		$firephp->setOptions(array('useNativeJsonEncode' => false));
		$firephp->group($this->getGroupLabel(), array('Collapsed' => true));
		$firephp->log('Time,  Message');

		$first = $logs[0][3];
		$c = count($logs);
		for($i=0,$n=$c;$i<$n;++$i)
		{
			$message	= $logs[$i][0];
			$level		= $logs[$i][1];
			$category	= $logs[$i][2];

			if ($i<$n-1)
			{
				$delta = $logs[$i+1][3] - $logs[$i][3];
				$total = $logs[$i+1][3] - $first;
			}
			else
			{
				$delta = '?';
				$total = $logs[$i][3] - $first;
			}

			$message = sPrintF('+%0.6f: %s', $delta, preg_replace('/\(line[^\)]+\)$/', '', $message));
			$firephp->fb($message, $category, self::translateLogLevel($level));
		}
		$firephp->log(sPrintF('%0.6f', $total), 'Cumulated Time');
		$firephp->groupEnd();
	}

	/**
	 * Translates a PRADO log level attribute into one understood by FirePHP for correct visualization
	 * @param string prado log level
	 * @return string FirePHP log level
	 */
	protected static function translateLogLevel($level)
	{
		switch($level)
		{
			case TLogger::INFO:
				return FirePHP::INFO;
			case TLogger::DEBUG:
			case TLogger::NOTICE:
				return FirePHP::LOG;
			case TLogger::WARNING:
				return FirePHP::WARN;
			case TLogger::ERROR:
			case TLogger::ALERT:
			case TLogger::FATAL:
				return FirePHP::ERROR;
			default:
				return FirePHP::LOG;
		}
	}

	/**
	 * @return string group label. Defaults to TFirePhpLogRoute::DEFAULT_LABEL
	 */
	public function getGroupLabel()
	{
		if($this->_groupLabel===null)
			$this->_groupLabel=self::DEFAULT_LABEL;

		return $this->_groupLabel;
	}

	/**
	 * @param string group label.
	 */
	public function setGroupLabel($value)
	{
		$this->_groupLabel=$value;
	}
}