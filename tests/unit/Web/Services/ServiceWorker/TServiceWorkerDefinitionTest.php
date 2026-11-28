<?php

namespace Prado\Test\Unit\Web\Services\ServiceWorker;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Services\ServiceWorker\TServiceWorkerDefinition;
use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;
use Prado\Web\Services\ServiceWorker\TWebAppManifest;

class TServiceWorkerDefinitionTest extends TestCase
{
	private $_scriptName;

	protected function setUp(): void
	{
		$this->_scriptName = $_SERVER['SCRIPT_NAME'] ?? null;
		$_SERVER['SCRIPT_NAME'] = '/app/index.php';
	}

	protected function tearDown(): void
	{
		if ($this->_scriptName === null) {
			unset($_SERVER['SCRIPT_NAME']);
		} else {
			$_SERVER['SCRIPT_NAME'] = $this->_scriptName;
		}
	}

	public function testDefaults()
	{
		$worker = new TServiceWorkerDefinition();
		$this->assertSame(TServiceWorkerModule::DEFAULT_WORKER, $worker->getName());
		$this->assertSame('/app/', $worker->getScope());
		$this->assertTrue($worker->getEnabled());
		$this->assertSame([], $worker->getContributors());
		$this->assertFalse($worker->getCacheAssets());
		$this->assertSame([], $worker->getNetworkFirst());
		$this->assertSame(TServiceWorkerDefinition::DEFAULT_NETWORK_TIMEOUT, $worker->getNetworkTimeout());
		$this->assertSame('', $worker->getOfflinePage());
		$this->assertFalse($worker->getNavigationPreload());
		$this->assertFalse($worker->getHasManifest());
		$this->assertNull($worker->getManifestData());
	}

	public function testScopeResolution()
	{
		$worker = new TServiceWorkerDefinition();
		$worker->setScope('/admin/');
		$this->assertSame('/admin/', $worker->getScope());
		$worker->setScope('admin/');
		$this->assertSame('/app/admin/', $worker->getScope());
		$worker->setScope('');
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$this->assertSame('/', $worker->getScope());
	}

	public function testNameIsValidated()
	{
		$worker = new TServiceWorkerDefinition();
		$worker->setName('admin-2_b');
		$this->assertSame('admin-2_b', $worker->getName());
		$this->expectException(TInvalidDataValueException::class);
		$worker->setName('admin worker');
	}

	public function testContributorsAdmission()
	{
		$worker = new TServiceWorkerDefinition();
		$this->assertTrue($worker->admitsContributor('anything'));
		$worker->setContributors('push, sync');
		$this->assertSame(['push', 'sync'], $worker->getContributors());
		$this->assertTrue($worker->admitsContributor('sync'));
		$this->assertFalse($worker->admitsContributor('other'));
	}

	public function testFetchStrategyProperties()
	{
		$worker = new TServiceWorkerDefinition();
		$worker->setCacheAssets('true');
		$worker->setNetworkFirst('Catalog, /help/ ,');
		$worker->setNetworkTimeout('1.5');
		$worker->setOfflinePage(' Offline ');
		$worker->setNavigationPreload(true);
		$worker->setEnabled('false');
		$this->assertTrue($worker->getCacheAssets());
		$this->assertSame(['Catalog', '/help/'], $worker->getNetworkFirst());
		$this->assertSame(1.5, $worker->getNetworkTimeout());
		$this->assertSame('Offline', $worker->getOfflinePage());
		$this->assertTrue($worker->getNavigationPreload());
		$this->assertFalse($worker->getEnabled());

		$this->expectException(TInvalidDataValueException::class);
		$worker->setNetworkTimeout(-1);
	}

	public function testManifestDataDefaultsToTheScope()
	{
		$worker = new TServiceWorkerDefinition();
		$worker->setScope('/admin/');
		$worker->getManifest()->setName('Admin');
		$this->assertTrue($worker->getHasManifest());
		$this->assertSame(['name' => 'Admin', 'start_url' => '/admin/', 'scope' => '/admin/'], $worker->getManifestData());
		$worker->getManifest()->setStartUrl('/admin/home');
		$this->assertSame('/admin/home', $worker->getManifestData()['start_url']);
	}

	public function testManifestFileIsTheManifestFile()
	{
		$worker = new TServiceWorkerDefinition();
		$worker->setManifestFile('/path/app.webmanifest');
		$this->assertSame('/path/app.webmanifest', $worker->getManifest()->getFile());
		$this->assertSame('/path/app.webmanifest', $worker->getManifestFile());
		$manifest = new TWebAppManifest();
		$worker->setManifest($manifest);
		$this->assertSame($manifest, $worker->getManifest());
	}

	public function testCloneCopiesTheManifest()
	{
		$worker = new TServiceWorkerDefinition();
		$worker->getManifest()->setName('Original');
		$copy = clone $worker;
		$copy->getManifest()->setName('Copy');
		$this->assertSame('Original', $worker->getManifest()->getName());
		$this->assertNotSame($worker->getManifest(), $copy->getManifest());
	}
}
