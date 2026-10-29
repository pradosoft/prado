<?php

namespace Prado\Test\Unit\Web\HttpHeaders;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Web\HttpHeaders\TCorsModule;
use Prado\Web\THttpHeaderName;
use Prado\Web\THttpRequest;
use Prado\Web\THttpResponse;

/**
 * THttpResponse subclass that records headers, status code and body in memory.
 */
class TCorsCapturingResponse extends THttpResponse
{
	public int $status = 200;
	public array $headers = [];
	public string $body = '';

	public function getStatusCode(): int
	{
		return $this->status;
	}

	public function setStatusCode($status, $reason = null): void
	{
		$this->status = (int) $status;
	}

	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		$this->headers[] = $header;
	}

	public function write($str): void
	{
		$this->body .= $str;
	}

	/** Returns the first header line with the given name, or null. */
	public function headerLine(string $name): ?string
	{
		$needle = strtolower($name) . ':';
		foreach ($this->headers as $line) {
			if (str_starts_with(strtolower($line), $needle)) {
				return $line;
			}
		}
		return null;
	}
}

class TCorsModuleTest extends TestCase
{
	private ?TTestApplication $app = null;
	private array $savedServer = [];
	private TCorsCapturingResponse $response;

	protected function setUp(): void
	{
		$this->savedServer = $_SERVER;
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/index.php/api/users';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php/api/users';
		$_SERVER['PATH_INFO'] = '/api/users';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		unset($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);

		$this->app = new TTestApplication();
		$this->response = new TCorsCapturingResponse();
		$this->app->setResponse($this->response);
	}

	protected function tearDown(): void
	{
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
		$_SERVER = $this->savedServer;
	}

	/**
	 * Builds the request from `$_SERVER`, initializes a module with `$properties`,
	 * and raises `onBeginRequest`.
	 * @param array $properties
	 * @param string $method
	 */
	private function beginRequest(array $properties = [], string $method = 'GET'): TCorsModule
	{
		$request = new THttpRequest();
		$request->init(null);
		$_SERVER['REQUEST_METHOD'] = $method;

		$module = new TCorsModule();
		foreach ($properties as $name => $value) {
			$module->$name = $value;
		}
		$module->init(null);
		$this->app->onBeginRequest();
		return $module;
	}

	private function assertNoCorsHeaders(): void
	{
		foreach ($this->response->headers as $line) {
			self::assertStringStartsNotWith('Access-Control-', $line);
			self::assertStringStartsNotWith('Vary:', $line);
		}
		self::assertSame(200, $this->response->status);
		self::assertFalse($this->app->getRequestCompleted());
	}

	// ── Defaults and accessors ────────────────────────────────────────────────

	public function testDefaults(): void
	{
		$module = new TCorsModule();
		self::assertSame('*', $module->getAllowOrigin());
		self::assertSame('GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD', $module->getAllowMethods());
		self::assertSame('Content-Type, Authorization, X-Requested-With, Accept, X-Prado-Csrf-Token', $module->getAllowHeaders());
		self::assertSame('', $module->getExposeHeaders());
		self::assertFalse($module->getAllowCredentials());
		self::assertSame(86400, $module->getMaxAge());
		self::assertSame('', $module->getPathPrefix());
	}

	public function testAccessorsCoerceValues(): void
	{
		$module = new TCorsModule();
		$module->setAllowOrigin('https://a.example.com, https://b.example.com');
		$module->setAllowMethods('GET, POST');
		$module->setAllowHeaders('X-One');
		$module->setExposeHeaders('X-Total-Count');
		$module->setAllowCredentials('true');
		$module->setMaxAge('3600');
		$module->setPathPrefix('api');

		self::assertSame('https://a.example.com, https://b.example.com', $module->getAllowOrigin());
		self::assertSame('GET, POST', $module->getAllowMethods());
		self::assertSame('X-One', $module->getAllowHeaders());
		self::assertSame('X-Total-Count', $module->getExposeHeaders());
		self::assertTrue($module->getAllowCredentials());
		self::assertSame(3600, $module->getMaxAge());
		self::assertSame('api', $module->getPathPrefix());

		$module->setAllowCredentials('false');
		self::assertFalse($module->getAllowCredentials());
		$module->setAllowCredentials(1);
		self::assertTrue($module->getAllowCredentials());
	}

	// ── init() ───────────────────────────────────────────────────────────────

	public function testInitRejectsCredentialsWithWildcardOrigin(): void
	{
		$module = new TCorsModule();
		$module->setAllowCredentials(true);
		$this->expectException(TConfigurationException::class);
		$module->init(null);
	}

	public function testInitAcceptsCredentialsWithExplicitOrigin(): void
	{
		$module = new TCorsModule();
		$module->setAllowCredentials(true);
		$module->setAllowOrigin('https://app.example.com');
		$module->init(null);
		self::assertTrue($module->getAllowCredentials());
	}

	// ── isOriginAllowed() / appliesToPath() ──────────────────────────────────

	public function testIsOriginAllowed(): void
	{
		$module = new TCorsModule();
		self::assertTrue($module->isOriginAllowed('https://anything.example.com'));

		$module->setAllowOrigin(' https://a.example.com ,https://B.example.com');
		self::assertTrue($module->isOriginAllowed('https://a.example.com'));
		self::assertTrue($module->isOriginAllowed('HTTPS://A.EXAMPLE.COM'));
		self::assertTrue($module->isOriginAllowed('https://b.example.com'));
		self::assertFalse($module->isOriginAllowed('https://c.example.com'));
		self::assertFalse($module->isOriginAllowed('https://a.example.com.evil.com'));
		self::assertFalse($module->isOriginAllowed(''));
	}

	public function testAppliesToPath(): void
	{
		$module = new TCorsModule();
		self::assertTrue($module->appliesToPath('/anything'));
		self::assertTrue($module->appliesToPath(''));

		$module->setPathPrefix('api');
		self::assertTrue($module->appliesToPath('/api'));
		self::assertTrue($module->appliesToPath('api'));
		self::assertTrue($module->appliesToPath('/api/'));
		self::assertTrue($module->appliesToPath('/api/users/1'));
		self::assertFalse($module->appliesToPath('/apidocs'));
		self::assertFalse($module->appliesToPath('/apidocs/index'));
		self::assertFalse($module->appliesToPath('/'));
		self::assertFalse($module->appliesToPath('/other/api'));

		$module->setPathPrefix('/api/v1/');
		self::assertTrue($module->appliesToPath('/api/v1'));
		self::assertTrue($module->appliesToPath('/api/v1/users'));
		self::assertFalse($module->appliesToPath('/api/v10'));
	}

	// ── handleRequest() ──────────────────────────────────────────────────────

	public function testNoOriginHeaderSendsNothing(): void
	{
		$this->beginRequest();
		$this->assertNoCorsHeaders();
	}

	public function testOriginOutsideListSendsNothing(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com';
		$this->beginRequest(['AllowOrigin' => 'https://app.example.com']);
		$this->assertNoCorsHeaders();
	}

	public function testWildcardSendsStarWithoutVary(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$this->beginRequest();
		self::assertSame('Access-Control-Allow-Origin: *', $this->response->headerLine(THttpHeaderName::AccessControlAllowOrigin));
		self::assertNull($this->response->headerLine(THttpHeaderName::Vary));
		self::assertNull($this->response->headerLine(THttpHeaderName::AccessControlAllowCredentials));
		self::assertNull($this->response->headerLine(THttpHeaderName::AccessControlExposeHeaders));
		self::assertNull($this->response->headerLine(THttpHeaderName::AccessControlAllowMethods));
		self::assertSame(200, $this->response->status);
		self::assertFalse($this->app->getRequestCompleted());
	}

	public function testAllowListEchoesOriginWithVary(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://b.example.com';
		$this->beginRequest(['AllowOrigin' => 'https://a.example.com, https://b.example.com']);
		self::assertSame('Access-Control-Allow-Origin: https://b.example.com', $this->response->headerLine(THttpHeaderName::AccessControlAllowOrigin));
		self::assertSame('Vary: Origin', $this->response->headerLine(THttpHeaderName::Vary));
		self::assertNull($this->response->headerLine(THttpHeaderName::AccessControlAllowCredentials));
	}

	public function testCredentialsWithExplicitOrigin(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
		$this->beginRequest(['AllowOrigin' => 'https://app.example.com', 'AllowCredentials' => 'true']);
		self::assertSame('Access-Control-Allow-Origin: https://app.example.com', $this->response->headerLine(THttpHeaderName::AccessControlAllowOrigin));
		self::assertSame('Access-Control-Allow-Credentials: true', $this->response->headerLine(THttpHeaderName::AccessControlAllowCredentials));
		self::assertSame('Vary: Origin', $this->response->headerLine(THttpHeaderName::Vary));
	}

	public function testHandleRequestRejectsCredentialsWithWildcardSetAfterInit(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
		$request = new THttpRequest();
		$request->init(null);
		$module = new TCorsModule();
		$module->init(null);
		$module->setAllowCredentials(true);
		$this->expectException(TConfigurationException::class);
		$module->handleRequest($this->app, null);
	}

	public function testExposeHeaders(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$this->beginRequest(['ExposeHeaders' => 'X-Total-Count, Link']);
		self::assertSame('Access-Control-Expose-Headers: X-Total-Count, Link', $this->response->headerLine(THttpHeaderName::AccessControlExposeHeaders));
	}

	public function testPreflightSendsPreflightHeadersAndCompletesRequest(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
		$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';
		$this->beginRequest(['AllowOrigin' => 'https://app.example.com', 'AllowMethods' => 'GET, PUT', 'AllowHeaders' => 'Content-Type', 'MaxAge' => '600'], 'OPTIONS');

		self::assertSame('Access-Control-Allow-Origin: https://app.example.com', $this->response->headerLine(THttpHeaderName::AccessControlAllowOrigin));
		self::assertSame('Access-Control-Allow-Methods: GET, PUT', $this->response->headerLine(THttpHeaderName::AccessControlAllowMethods));
		self::assertSame('Access-Control-Allow-Headers: Content-Type', $this->response->headerLine(THttpHeaderName::AccessControlAllowHeaders));
		self::assertSame('Access-Control-Max-Age: 600', $this->response->headerLine(THttpHeaderName::AccessControlMaxAge));
		self::assertSame(204, $this->response->status);
		self::assertTrue($this->app->getRequestCompleted());
		self::assertSame('', $this->response->body);
	}

	public function testPreflightMethodIsCaseInsensitive(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'POST';
		$this->beginRequest([], 'options');
		self::assertNotNull($this->response->headerLine(THttpHeaderName::AccessControlAllowMethods));
		self::assertSame(204, $this->response->status);
		self::assertTrue($this->app->getRequestCompleted());
	}

	public function testOptionsWithoutRequestMethodIsNotPreflight(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$this->beginRequest([], 'OPTIONS');
		self::assertSame('Access-Control-Allow-Origin: *', $this->response->headerLine(THttpHeaderName::AccessControlAllowOrigin));
		self::assertNull($this->response->headerLine(THttpHeaderName::AccessControlAllowMethods));
		self::assertNull($this->response->headerLine(THttpHeaderName::AccessControlAllowHeaders));
		self::assertNull($this->response->headerLine(THttpHeaderName::AccessControlMaxAge));
		self::assertSame(200, $this->response->status);
		self::assertFalse($this->app->getRequestCompleted());
	}

	public function testPreflightOutsideListSendsNothing(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com';
		$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';
		$this->beginRequest(['AllowOrigin' => 'https://app.example.com'], 'OPTIONS');
		$this->assertNoCorsHeaders();
	}

	public function testPathPrefixMatch(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$_SERVER['PATH_INFO'] = '/api/users';
		$this->beginRequest(['PathPrefix' => 'api']);
		self::assertSame('Access-Control-Allow-Origin: *', $this->response->headerLine(THttpHeaderName::AccessControlAllowOrigin));
	}

	public function testPathPrefixExactMatch(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$_SERVER['PATH_INFO'] = '/api';
		$this->beginRequest(['PathPrefix' => 'api']);
		self::assertSame('Access-Control-Allow-Origin: *', $this->response->headerLine(THttpHeaderName::AccessControlAllowOrigin));
	}

	public function testPathPrefixMismatchSendsNothing(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$_SERVER['PATH_INFO'] = '/pages/home';
		$this->beginRequest(['PathPrefix' => 'api']);
		$this->assertNoCorsHeaders();
	}

	public function testPathPrefixDoesNotMatchLongerSegment(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://any.example.com';
		$_SERVER['PATH_INFO'] = '/apidocs/index';
		$this->beginRequest(['PathPrefix' => 'api']);
		$this->assertNoCorsHeaders();
	}
}
