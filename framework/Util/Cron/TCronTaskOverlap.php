<?php

/**
 * TCronTaskOverlap class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

/**
 * TCronTaskOverlap class.
 *
 * TCronTaskOverlap defines what a {@see \Prado\Util\Cron\TCronTask} trigger does while the task's
 * previous run still holds its run lock ({@see \Prado\Util\Cron\TCronModule::getTaskLockName()}):
 *  - Skip: the trigger is skipped; the default.
 *  - Allow: the run takes no lock and starts beside the previous one.
 *  - Replace: the previous run in this process is stopped (a Process task's child is terminated, a
 *    task in the cron process is cancelled through its token) and the trigger runs; a run of another
 *    process is not stopped, and the trigger is skipped.
 *  - Queue: the trigger is claimed and runs when the previous run ends; one trigger waits at a time.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronTaskOverlap extends \Prado\TEnumerable
{
	public const Skip = 'Skip';
	public const Allow = 'Allow';
	public const Replace = 'Replace';
	public const Queue = 'Queue';
}
