<?php

/**
 * TForkGuardExtension class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * TForkGuardExtension stops a forked test child that returns into PHPUnit.
 *
 * A test that forks must end its child with `exit()`.  A child that throws or returns
 * instead runs the rest of the suite as a duplicate process that shares stdout and
 * signals processes.  This extension records the runner's PID at bootstrap and checks it
 * at every test boundary.  A process with another PID is an escaped child: it writes the
 * test it escaped from to STDERR and kills itself with SIGKILL, so no PHPUnit shutdown
 * code or destructors run in it.
 *
 * Register it in `phpunit.xml`:
 * ```xml
 * <extensions>
 *   <bootstrap class="Prado\Test\Unit\Harness\TForkGuardExtension"/>
 * </extensions>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
final class TForkGuardExtension implements Extension
{
	/** @var int The PID of the PHPUnit runner process. */
	private static int $_runnerPid = 0;

	/**
	 * Records the runner PID and subscribes the guard to the test start and finish events.
	 * @param Configuration $configuration The PHPUnit configuration.
	 * @param Facade $facade The extension facade.
	 * @param ParameterCollection $parameters The extension parameters.
	 */
	public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
	{
		self::$_runnerPid = (int) getmypid();
		$facade->registerSubscribers(
			new class () implements PreparationStartedSubscriber {
				public function notify(PreparationStarted $event): void
				{
					TForkGuardExtension::guard($event->test(), 'starting');
				}
			},
			new class () implements FinishedSubscriber {
				public function notify(Finished $event): void
				{
					TForkGuardExtension::guard($event->test(), 'finishing');
				}
			}
		);
	}

	/**
	 * Kills the current process when it is not the PHPUnit runner.
	 * @param Test $test The test at the boundary.
	 * @param string $boundary The boundary being crossed, for the report.
	 */
	public static function guard(Test $test, string $boundary): void
	{
		$pid = (int) getmypid();
		if (self::$_runnerPid === 0 || $pid === self::$_runnerPid) {
			return;
		}
		fwrite(STDERR, PHP_EOL . "Forked child {$pid} of PHPUnit runner " . self::$_runnerPid . " escaped into the suite, {$boundary} {$test->id()}; killing it." . PHP_EOL);
		if (function_exists('posix_kill')) {
			posix_kill($pid, SIGKILL);
		}
		exit(1);
	}
}
