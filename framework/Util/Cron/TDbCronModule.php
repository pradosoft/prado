<?php

/**
 * TDbCronModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Prado;
use Prado\TApplicationMode;

/**
 * TDbCronModule class.
 *
 * TDbCronModule is **deprecated** for TDbCronManager, the singleton. Use
 * {@see TDbCronManager} instead of this class. TCronModule may have multiple
 * modules in the configuration, but TDbCronManager can only have one.
 *
 * TDbCronModule does everything that TCronModule does but stores the tasks and
 * persistent data in its own database table.
 *
 * ```xml
 * <modules>
 *     <module id="cron" class="Prado\Util\Cron\TDbCronModule" ConnectionID="db" />
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'cron' => [
 *             'class' => 'Prado\Util\Cron\TDbCronModule',
 *             'properties' => ['ConnectionID' => 'db'],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @deprecated since 4.3.3 — use {@see TDbCronManager} instead.
 * @see TDbCronManager
 * @todo v4.4 remove this class because TDbCronManager replaces it
 */
class TDbCronModule extends TDbCronManager
{
	/**
	 * Initializes the module and, in Debug mode, logs a notice that Prado 4.4 removes
	 * this class.
	 * @param mixed $config the module configuration
	 */
	public function init($config)
	{
		if ($this->getApplication()?->getMode() === TApplicationMode::Debug) {
			Prado::notice('TDbCronModule is deprecated and removed in Prado 4.4; use TDbCronManager.', static::class);
		}
		parent::init($config);
	}
}
