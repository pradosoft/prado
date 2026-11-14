<?php

/**
 * TCronTaskMode class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

/**
 * TCronTaskMode class.
 *
 * TCronTaskMode defines how {@see \Prado\Util\Cron\TCronModule::runTasks()} runs a
 * {@see \Prado\Util\Cron\TCronTask} in a sweep:
 *  - Serial: the task runs alone, in the cron process.  It starts after the running tasks of
 *    the sweep finish, and the sweep waits for it.
 *  - Parallel: the task runs in a fiber of the cron process beside the other Parallel tasks, at
 *    most {@see \Prado\Util\Cron\TCronModule::getConcurrency() Concurrency} at once.  A task that
 *    waits on the event loop lets the others run.
 *  - Process: the task runs in a child `cron/exec` process.  The sweep does not wait for it; the
 *    cron process waits for its children before it exits.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronTaskMode extends \Prado\TEnumerable
{
	public const Serial = 'Serial';
	public const Parallel = 'Parallel';
	public const Process = 'Process';
}
