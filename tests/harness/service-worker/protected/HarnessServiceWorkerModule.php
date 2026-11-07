<?php

/**
 * HarnessServiceWorkerModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;
use Prado\Web\Services\ServiceWorker\TServiceWorkerScript;

/**
 * The harness module: the Control page's state disables the workers and sets
 * the revision of the `harness` fragment, whose change makes the browser
 * install an updated worker.
 */
class HarnessServiceWorkerModule extends TServiceWorkerModule
{
	/**
	 * @return array the state the Control page stores
	 */
	public static function readState(): array
	{
		$file = Prado\Prado::getApplication()->getRuntimePath() . '/sw-harness.json';
		$state = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
		return (is_array($state) ? $state : []) + ['revision' => 1, 'disabled' => false];
	}

	/**
	 * @param array $state the state to store
	 */
	public static function writeState(array $state): void
	{
		file_put_contents(Prado\Prado::getApplication()->getRuntimePath() . '/sw-harness.json', json_encode($state));
	}

	public function getEnabled(): bool
	{
		return !static::readState()['disabled'] && parent::getEnabled();
	}

	public function contributeServiceWorker(TServiceWorkerScript $script): void
	{
		parent::contributeServiceWorker($script);
		$revision = (int) static::readState()['revision'];
		$script->addFragment('harness', 'prado.onMessage("revision", () => ' . $revision . ');', 50);
	}
}
