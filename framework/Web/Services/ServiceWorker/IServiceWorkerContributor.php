<?php

/**
 * IServiceWorkerContributor interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServiceWorker;

/**
 * IServiceWorkerContributor interface
 *
 * IServiceWorkerContributor adds code to the service workers of
 * {@see TServiceWorkerModule}. The module collects every application module
 * that implements it; {@see TServiceWorkerModule::addContributor()} adds any
 * other object.
 * ```php
 * class TOrderSyncModule extends TModule implements IServiceWorkerContributor
 * {
 *     public function contributeServiceWorker(TServiceWorkerScript $script): void
 *     {
 *         if ($script->getWorker()->getName() === 'default') {
 *             $script->addFragment('ordersync', 'prado.onMessage("sync", () => true);', 20);
 *             $script->addPrecache('/index.php?page=Orders.Offline');
 *         }
 *     }
 * }
 * ```
 * The module calls the contributor once for each worker whose
 * {@see TServiceWorkerDefinition::getContributors() Contributors} admit it, when
 * the browser requests that worker's script. A page request builds no script.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IServiceWorkerContributor
{
	/**
	 * Adds fragments, precache URLs, and excluded paths to a worker script.
	 * @param TServiceWorkerScript $script the script being built; its
	 *   {@see TServiceWorkerScript::getWorker() Worker} is the definition of the worker.
	 */
	public function contributeServiceWorker(TServiceWorkerScript $script): void;
}
