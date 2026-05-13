<?php

/**
 * TDbCronModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

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
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @deprecated since 4.3.3 — use {@see TDbCronManager} instead.
 * @see TDbCronManager
 * @todo v4.4 remove this class because TDbCronManager replaces it
 */
class TDbCronModule extends TDbCronManager
{
	// This class is the deprecated placeholder stub.
	// It will be removed in PRADO v4.4.0
}
