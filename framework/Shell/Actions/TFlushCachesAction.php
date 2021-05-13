<?php
/**
 * TFlushCachesAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell\Actions
 */

namespace Prado\Shell\Actions;

use Prado\Caching\ICache;
use Prado\Shell\TShellAction;

/**
 * This command clears all application modules implementing ICache.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @package Prado\Shell\Actions
 * @since 4.2.0
 */
class TFlushCachesAction extends TShellAction
{
	protected $action = 'flushcaches';
	protected $parameters = [];
	protected $optional = ['directory'];
	protected $description = 'Flushes all application TCache modules. Use case: upgrading a performance mode website by clearing out the old cache.';

	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function performAction($args)
	{
		$app = null;
		if (count($args) > 1) {
			if (false === ($xml = $this->getAppConfigFile($args[1]))) {
				return false;
			}
			$app = $this->initializePradoApplication($args[1]);
		}
		$app->onLoadState();
		$app->onLoadStateComplete();
		$cachesFlushed = [];
		foreach ($app->getModulesByType('Prado\\Caching\\ICache') as $module) {
			$module->flush();
			$cachesFlushed[] = get_class($module);
		}
		$app->onSaveState();
		$app->onSaveStateComplete();
		if (!count($cachesFlushed)) {
			$cachesFlushed[] = 'no caches (none were found)';
		}
		echo "** Application flushed " . implode(', ', $cachesFlushed) . "\n";
		return true;
	}
}
