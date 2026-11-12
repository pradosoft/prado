<?php

/**
 * CronE2ETask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Util\Cron\TCronTask;

/**
 * CronE2ETask records its run through the application's `probe` module ({@see CronE2EProbe}).  With
 * {@see setSleep Sleep}, it works that many seconds and records its end.
 */
class CronE2ETask extends TCronTask
{
	/** @var float the seconds the run works */
	private float $_sleep = 0.0;

	/**
	 * Records the run with the task name, and its end after the Sleep.
	 * @param \Prado\Util\Cron\TCronModule $cronModule the cron module running the task
	 */
	public function execute($cronModule)
	{
		$probe = $this->getApplication()->getModule('probe');
		$entry = ['e' => 'run', 'task' => $this->getName(), 'memory' => ini_get('memory_limit')];
		if (function_exists('posix_getpid') && PHP_OS_FAMILY !== 'Windows') {
			$entry['nice'] = Prado\Util\Helpers\TProcessHelper::getProcessPriority();
		}
		$probe->record($entry);
		if ($this->_echo !== '') {
			echo $this->_echo;
		}
		if ($this->_sleep > 0) {
			usleep((int) ($this->_sleep * 1000000));
			$probe->record(['e' => 'done', 'task' => $this->getName()]);
		}
		if ($this->_failTimes > 0) {
			$counter = dirname($probe->getLogFile()) . DIRECTORY_SEPARATOR . 'fail-' . preg_replace('/\W+/', '_', $this->getName()) . '.count';
			$failed = (int) @file_get_contents($counter);
			if ($failed < $this->_failTimes) {
				file_put_contents($counter, (string) ($failed + 1));
				throw new RuntimeException('planned failure ' . ($failed + 1));
			}
		}
	}

	/** @var string the text the run writes to its output */
	private string $_echo = '';

	/** @var int the number of runs that fail before one succeeds */
	private int $_failTimes = 0;

	/**
	 * @return string the text the run writes to its output
	 */
	public function getEcho(): string
	{
		return $this->_echo;
	}

	/**
	 * @param string $value the text the run writes to its output
	 */
	public function setEcho($value)
	{
		$this->_echo = (string) $value;
	}

	/**
	 * @return int the number of runs that fail before one succeeds
	 */
	public function getFailTimes(): int
	{
		return $this->_failTimes;
	}

	/**
	 * @param int|string $value the number of runs that fail before one succeeds
	 */
	public function setFailTimes($value)
	{
		$this->_failTimes = (int) $value;
	}

	/**
	 * @return float the seconds the run works
	 */
	public function getSleep(): float
	{
		return $this->_sleep;
	}

	/**
	 * @param float|string $seconds the seconds the run works
	 */
	public function setSleep($seconds)
	{
		$this->_sleep = (float) $seconds;
	}
}
