<?php

namespace Prado\Test\Unit\Web\Services\ServiceWorker;

use Prado\TModule;
use Prado\Web\Services\ServiceWorker\IServiceWorkerContributor;
use Prado\Web\Services\ServiceWorker\IServiceWorkerRequestHandler;
use Prado\Web\Services\ServiceWorker\TServiceWorkerScript;
use Prado\Web\Services\ServiceWorker\TServiceWorkerService;

/**
 * A module that adds a fragment to each worker it is admitted to, records the
 * workers, and answers the `ping` request and any names set in $names.
 */
class TServiceWorkerTestContributor extends TModule implements IServiceWorkerContributor, IServiceWorkerRequestHandler
{
	/** @var string[] names of the workers contributed to */
	public array $workers = [];

	/** @var string[] request names this handler claims */
	public array $names = ['ping', 'worker'];

	/** @var array<int, array{string, TServiceWorkerService}> requests answered */
	public array $requests = [];

	public function contributeServiceWorker(TServiceWorkerScript $script): void
	{
		$name = $script->getWorker()->getName();
		$this->workers[] = $name;
		$script->addFragment('test-' . $name, 'prado.onMessage("ping", () => "pong");', 20, ['worker' => $name]);
		$script->addPrecache('/test/' . $name . '.png');
	}

	public function getServiceWorkerRequestNames(): array
	{
		return $this->names;
	}

	public function runServiceWorkerRequest(string $name, TServiceWorkerService $service): void
	{
		$this->requests[] = [$name, $service];
	}
}
