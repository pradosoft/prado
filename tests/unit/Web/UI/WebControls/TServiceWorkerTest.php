<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;
use Prado\Web\Services\TPageService;
use Prado\Web\TAssetManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\TServiceWorker;
use Prado\Xml\TXmlDocument;

/**
 * A page whose callback state is set by the test.
 */
class TServiceWorkerTestPage extends TPage
{
	public bool $callback = false;

	public function getIsCallback()
	{
		return $this->callback;
	}
}

class TServiceWorkerTest extends TestCase
{
	private ?TTestApplication $_app = null;
	private array $_server = [];

	protected function setUp(): void
	{
		$this->_server = ['SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null, 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null];
	}

	protected function tearDown(): void
	{
		$this->_app?->restoreApplication();
		$this->_app = null;
		foreach ($this->_server as $key => $value) {
			if ($value === null) {
				unset($_SERVER[$key]);
			} else {
				$_SERVER[$key] = $value;
			}
		}
	}

	private function createApp(string $requestUri = '/app/index.php', bool $withModule = true, array $properties = [], ?string $xml = null): ?TServiceWorkerModule
	{
		$_SERVER['SCRIPT_NAME'] = '/app/index.php';
		$_SERVER['REQUEST_URI'] = $requestUri;
		$this->_app = new TTestApplication();
		$assets = new TAssetManager();
		$assets->setBaseUrl('/app/assets');
		$this->_app->setAssetManager($assets);
		$this->_app->setService(new TPageService());
		if (!$withModule) {
			return null;
		}
		$config = null;
		if ($xml !== null) {
			$config = new TXmlDocument();
			$config->loadFromString($xml);
		}
		$module = new TServiceWorkerModule();
		$module->setID('serviceworker');
		$this->_app->setModule('serviceworker', $module);
		$module->setSubProperties($properties);
		$module->init($config);
		return $module;
	}

	private function createControl(?TPage $page = null, bool $withForm = true): TServiceWorker
	{
		$page ??= new TServiceWorkerTestPage();
		if ($withForm && $page->getForm() === null) {
			$page->setForm(new TForm());
		}
		$control = new TServiceWorker();
		$page->getControls()->add($control);
		return $control;
	}

	private function endScripts(TServiceWorker $control): array
	{
		return PradoUnit::getProp($control->getPage()->getClientScript(), '_endScripts');
	}

	public function testDefaults()
	{
		$control = new TServiceWorker();
		$this->assertSame(TServiceWorkerModule::AUTO, $control->getModuleID());
		$this->assertSame('', $control->getWorker());
		$control->setModuleID('');
		$this->assertSame(TServiceWorkerModule::AUTO, $control->getModuleID());
		$control->setWorker(' admin ');
		$this->assertSame('admin', $control->getWorker());
	}

	public function testRendersNoMarkup()
	{
		$this->createApp();
		$control = $this->createControl();
		$tw = new TTextWriter();
		$control->render(new THtmlWriter($tw));
		$this->assertSame('', $tw->flush());
	}

	public function testRegistersTheWorkerOfThePage()
	{
		$this->createApp();
		$control = $this->createControl();
		$control->onPreRender(null);
		$this->assertSame(
			'Prado.ServiceWorker.register("\/app\/index.php?sw=worker", {\'scope\':"\/app\/",\'worker\':"default"});',
			$this->endScripts($control)[TServiceWorker::SCRIPT_KEY]
		);
		$this->assertTrue($control->getPage()->getClientScript()->isEndScriptRegistered(TServiceWorker::SCRIPT_KEY));
	}

	public function testRendersTheRegistrationOnAPageWithoutAForm()
	{
		$this->createApp();
		$control = $this->createControl(null, false);
		$control->onPreRender(null);
		$this->assertArrayNotHasKey(TServiceWorker::SCRIPT_KEY, $this->endScripts($control));
		$tw = new TTextWriter();
		$control->render(new THtmlWriter($tw));
		$output = $tw->flush();
		$this->assertMatchesRegularExpression('#<script src="[^"]*/serviceworker/serviceworker\.js"></script>#', $output);
		$this->assertStringContainsString('Prado.ServiceWorker.register("\/app\/index.php?sw=worker"', $output);
		$this->assertLessThan(strpos($output, 'Prado.ServiceWorker.register'), strpos($output, 'serviceworker.js'));
	}

	public function testRegistersANamedWorker()
	{
		$this->createApp('/app/admin/users', true, [], '<module><worker name="admin" Scope="/app/admin/" /></module>');
		$control = $this->createControl();
		$control->setWorker('admin');
		$control->onPreRender(null);
		$this->assertStringContainsString('"\/app\/index.php?sw=worker&w=admin", {\'scope\':"\/app\/admin\/",\'worker\':"admin"}', $this->endScripts($control)[TServiceWorker::SCRIPT_KEY]);
	}

	public function testRegistersNothingOutsideTheScope()
	{
		$this->createApp('/app/admin/users', true, [], '<module><worker name="admin" Scope="/app/admin/" /></module>');
		$control = $this->createControl();
		$control->onPreRender(null);
		$this->assertArrayNotHasKey(TServiceWorker::SCRIPT_KEY, $this->endScripts($control));
		$this->assertNull($control->getServiceWorkerDefinition());
	}

	public function testRegistersNothingWhenDisabledOrInACallback()
	{
		$module = $this->createApp();
		$page = new TServiceWorkerTestPage();
		$page->callback = true;
		$control = $this->createControl($page);
		$control->onPreRender(null);
		$this->assertArrayNotHasKey(TServiceWorker::SCRIPT_KEY, $this->endScripts($control));

		$page->callback = false;
		$control->setEnabled(false);
		$control->onPreRender(null);
		$this->assertArrayNotHasKey(TServiceWorker::SCRIPT_KEY, $this->endScripts($control));

		$control->setEnabled(true);
		$module->setEnabled(false);
		$control->onPreRender(null);
		$this->assertArrayNotHasKey(TServiceWorker::SCRIPT_KEY, $this->endScripts($control));
		$this->assertNull($control->getServiceWorkerDefinition());
	}

	public function testWithoutAModuleRegistersNothing()
	{
		$this->createApp('/app/index.php', false);
		$control = $this->createControl();
		$this->assertNull($control->getServiceWorkerModule());
		$control->onPreRender(null);
		$this->assertArrayNotHasKey(TServiceWorker::SCRIPT_KEY, $this->endScripts($control));
	}

	public function testModuleIdOfAnotherModuleThrows()
	{
		$this->createApp();
		$control = $this->createControl();
		$control->setModuleID('request');
		$this->expectException(TConfigurationException::class);
		$control->getServiceWorkerModule();
	}

	public function testFindModuleById()
	{
		$module = $this->createApp();
		$this->assertSame($module, TServiceWorker::findModule($this->_app, 'serviceworker'));
		$this->assertSame($module, TServiceWorker::findModule($this->_app, TServiceWorkerModule::AUTO));
	}
}
