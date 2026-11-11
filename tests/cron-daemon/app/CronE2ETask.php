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
 * CronE2ETask records its run through the application's `probe` module ({@see CronE2EProbe}).
 */
class CronE2ETask extends TCronTask
{
	/**
	 * Records the run with the task name.
	 * @param \Prado\Util\Cron\TCronModule $cronModule the cron module running the task
	 */
	public function execute($cronModule)
	{
		$this->getApplication()->getModule('probe')->record(['e' => 'run', 'task' => $this->getName()]);
	}
}
