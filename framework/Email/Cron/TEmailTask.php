<?php

/**
 * TEmailTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Cron;

use Prado\Email\TEmailModule;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Shell\TShellWriter;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;

/**
 * TEmailTask class.
 *
 * TEmailTask is the base class of the cron tasks of the email subsystem.  It finds
 * the {@see TEmailModule} of the task: the module of the task's ModuleId, or the
 * first email module of the application.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TEmailTask extends TCronTask
{
	/**
	 * @throws TInvalidDataValueException when the ModuleId is not the ID of a TEmailModule.
	 * @throws TConfigurationException when the application has no TEmailModule.
	 * @return TEmailModule the email module of the task.
	 */
	protected function getEmailModule(): TEmailModule
	{
		$app = $this->getApplication();
		if ($id = $this->getModuleId()) {
			$module = $app->getModule($id);
			if (!($module instanceof TEmailModule)) {
				throw new TInvalidDataValueException('emailtask_moduleid_invalid', $id);
			}
			return $module;
		}
		foreach (array_keys($app->getModulesByType(TEmailModule::class)) as $id) {
			if (($module = $app->getModule($id)) instanceof TEmailModule) {
				return $module;
			}
		}
		throw new TConfigurationException('emailtask_module_required');
	}

	/**
	 * Writes a line to the shell when cron runs there.
	 * @param mixed $cron the cron module running the task.
	 * @param string $line the line.
	 */
	protected function writeLine($cron, string $line): void
	{
		if (is_object($cron) && $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$cron->getOutputWriter()->writeLine($line, TShellWriter::GREEN);
		}
	}
}
