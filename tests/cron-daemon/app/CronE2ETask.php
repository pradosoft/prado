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
		$probe->record(['e' => 'run', 'task' => $this->getName()]);
		if ($this->_sleep > 0) {
			usleep((int) ($this->_sleep * 1000000));
			$probe->record(['e' => 'done', 'task' => $this->getName()]);
		}
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
