<?php

namespace Prado\Test\Unit\Web\Services\ServiceWorker;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;
use Prado\Web\Services\ServiceWorker\TServiceWorkerService;
use Prado\Web\TAssetManager;
use Prado\Web\TMediaType;
use Prado\Xml\TXmlDocument;

/**
 * Request of the service: the service parameter, the items, and the headers.
 */
class TServiceWorkerServiceTestRequest
{
	public string $serviceParameter = 'worker';
	public array $items = [];
	public array $headers = [];

	public function getServiceParameter()
	{
		return $this->serviceParameter;
	}

	public function itemAt($key)
	{
		return $this->items[$key] ?? null;
	}

	public function getHeader($name, $default = null)
	{
		return $this->headers[$name] ?? $default;
	}
}

/**
 * Response of the service: records the status, type, headers, and body.
 */
class TServiceWorkerServiceTestResponse
{
	public int $status = 200;
	public string $contentType = '';
	public string $charset = '';
	public array $headers = [];
	public string $body = '';

	public function setStatusCode($status, $reason = null)
	{
		$this->status = $status;
	}

	public function setContentType($type)
	{
		$this->contentType = $type;
	}

	public function setCharset($charset)
	{
		$this->charset = $charset;
	}

	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		[$name, $value] = explode(': ', $header, 2);
		$this->headers[$name] = $value;
	}

	public function write($str)
	{
		$this->body .= $str;
	}
}

/**
 * The service with the test request and response.
 */
class TServiceWorkerServiceTestService extends TServiceWorkerService
{
	public TServiceWorkerServiceTestRequest $testRequest;
	public TServiceWorkerServiceTestResponse $testResponse;

	public function __construct()
	{
		$this->testRequest = new TServiceWorkerServiceTestRequest();
		$this->testResponse = new TServiceWorkerServiceTestResponse();
		parent::__construct();
	}

	public function getRequest()
	{
		return $this->testRequest;
	}

	public function getResponse()
	{
		return $this->testResponse;
	}
}

class TServiceWorkerServiceTest extends TestCase
{
	private ?TTestApplication $_app = null;
	private array $_server = [];
	private ?TServiceWorkerModule $_module = null;

	protected function setUp(): void
	{
		$this->_server = ['SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null, 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null];
		$_SERVER['SCRIPT_NAME'] = '/app/index.php';
		$_SERVER['REQUEST_URI'] = '/app/index.php';
		$this->_app = new TTestApplication();
		$assets = new TAssetManager();
		$assets->setBaseUrl('/app/assets');
		$this->_app->setAssetManager($assets);
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

	private function createModule(array $properties = [], ?string $xml = null): TServiceWorkerModule
	{
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
		return $this->_module = $module;
	}

	private function createService(string $parameter = 'worker', array $items = [], array $headers = []): TServiceWorkerServiceTestService
	{
		$service = new TServiceWorkerServiceTestService();
		$service->testRequest->serviceParameter = $parameter;
		$service->testRequest->items = $items;
		$service->testRequest->headers = $headers;
		return $service;
	}

	public function testPropertyDefaults()
	{
		$service = new TServiceWorkerService();
		$this->assertSame('sw', TServiceWorkerService::SERVICE_ID);
		$this->assertSame(TServiceWorkerModule::AUTO, $service->getModuleID());
		$this->assertFalse($service->getAutoRegistered());
		$service->setAutoRegistered('true');
		$this->assertTrue($service->getAutoRegistered());
		$service->setModuleID('auto');
		$this->assertSame(TServiceWorkerModule::AUTO, $service->getModuleID());
	}

	public function testModuleResolution()
	{
		$module = $this->createModule();
		$service = new TServiceWorkerService();
		$this->assertSame($module, $service->getModule());
		$service->setModuleID('serviceworker');
		$this->assertSame($module, $service->getModule());
	}

	public function testMissingModuleThrows()
	{
		$service = new TServiceWorkerService();
		try {
			$service->getModule();
			$this->fail('A service without a module was accepted.');
		} catch (TConfigurationException $e) {
			$this->assertSame('serviceworkerservice_module_required', $e->getErrorCode());
		}
		$service->setModuleID('request');
		try {
			$service->getModule();
			$this->fail('A ModuleID of another class was accepted.');
		} catch (TConfigurationException $e) {
			$this->assertSame('serviceworkerservice_module_invalid', $e->getErrorCode());
		}
	}

	public function testWorkerScriptResponse()
	{
		$module = $this->createModule(['CacheAssets' => true]);
		$service = $this->createService('worker');
		$service->run();
		$response = $service->testResponse;
		$this->assertSame(200, $response->status);
		$this->assertSame(TMediaType::JAVASCRIPT, $response->contentType);
		$this->assertSame('UTF-8', $response->charset);
		$this->assertSame('/app/', $response->headers['Service-Worker-Allowed']);
		$this->assertSame('no-cache', $response->headers['Cache-Control']);
		$this->assertMatchesRegularExpression('/^"[0-9a-f]{32}"$/', $response->headers['ETag']);
		$this->assertSame($module->renderScript($module->getDefinition()), $response->body);
		$this->assertStringContainsString('"worker":"default"', $response->body);
	}

	public function testEmptyParameterIsTheWorkerScript()
	{
		$this->createModule();
		$service = $this->createService('');
		$service->run();
		$this->assertStringContainsString('prado.start();', $service->testResponse->body);
	}

	public function testNamedWorkerScript()
	{
		$this->createModule([], '<module><worker name="admin" Scope="/app/admin/" /></module>');
		$service = $this->createService('worker', ['w' => 'admin']);
		$service->run();
		$this->assertSame('/app/admin/', $service->testResponse->headers['Service-Worker-Allowed']);
		$this->assertStringContainsString('"worker":"admin"', $service->testResponse->body);
	}

	public function testUnknownWorkerIs404()
	{
		$this->createModule();
		$service = $this->createService('worker', ['w' => 'missing']);
		try {
			$service->run();
			$this->fail('An unknown worker was served.');
		} catch (THttpException $e) {
			$this->assertSame(404, $e->getStatusCode());
			$this->assertSame('serviceworkerservice_worker_unknown', $e->getErrorCode());
		}
	}

	public function testMatchingEtagIs304()
	{
		$this->createModule();
		$first = $this->createService('worker');
		$first->run();
		$etag = $first->testResponse->headers['ETag'];

		$second = $this->createService('worker', [], ['If-None-Match' => 'W/"other", ' . $etag]);
		$second->run();
		$this->assertSame(304, $second->testResponse->status);
		$this->assertSame('', $second->testResponse->body);
		$this->assertSame($etag, $second->testResponse->headers['ETag']);
	}

	public function testDisabledWorkerServesTheRemovalScript()
	{
		$this->createModule(['Enabled' => false]);
		$service = $this->createService('worker');
		$service->run();
		$this->assertStringContainsString('registration.unregister()', $service->testResponse->body);
	}

	public function testManifestResponse()
	{
		$this->createModule(['Manifest.Name' => 'Orders', 'Manifest.ShortName' => 'Ord']);
		$service = $this->createService('manifest');
		$service->run();
		$response = $service->testResponse;
		$this->assertSame(TMediaType::MANIFEST_JSON, $response->contentType);
		$this->assertSame('no-cache', $response->headers['Cache-Control']);
		$this->assertArrayNotHasKey('Service-Worker-Allowed', $response->headers);
		$this->assertSame(['name' => 'Orders', 'short_name' => 'Ord', 'start_url' => '/app/', 'scope' => '/app/'], json_decode($response->body, true));
	}

	public function testMissingManifestIs404()
	{
		$this->createModule();
		$service = $this->createService('manifest');
		try {
			$service->run();
			$this->fail('A missing manifest was served.');
		} catch (THttpException $e) {
			$this->assertSame(404, $e->getStatusCode());
			$this->assertSame('serviceworkerservice_manifest_unknown', $e->getErrorCode());
		}
	}

	public function testContributorRequest()
	{
		$contributor = new TServiceWorkerTestContributor();
		$this->_app->setModule('push', $contributor);
		$this->createModule();
		$service = $this->createService('ping');
		$service->run();
		$this->assertCount(1, $contributor->requests);
		$this->assertSame(['ping', $service], $contributor->requests[0]);
	}

	public function testUnknownRequestIs404()
	{
		$this->createModule();
		$service = $this->createService('nothing');
		try {
			$service->run();
			$this->fail('An unknown request was answered.');
		} catch (THttpException $e) {
			$this->assertSame(404, $e->getStatusCode());
			$this->assertSame('serviceworkerservice_request_unknown', $e->getErrorCode());
		}
	}
}
