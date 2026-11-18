<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailService;
use Prado\Email\TEmailStatus;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;

/**
 * A request with fixed query items, service parameter, and method.
 */
class TEmailServiceTestRequest
{
	public function __construct(public array $items, public string $trackingId, public string $type = 'GET')
	{
	}

	public function getServiceParameter()
	{
		return $this->trackingId;
	}

	public function contains($key)
	{
		return array_key_exists($key, $this->items);
	}

	public function itemAt($key)
	{
		return $this->items[$key] ?? null;
	}

	public function getRequestType()
	{
		return $this->type;
	}
}

/**
 * A response that records what the service writes.
 */
class TEmailServiceTestResponse
{
	public string $body = '';

	public ?string $type = null;

	public ?string $redirect = null;

	public array $headers = [];

	public function setContentType($type)
	{
		$this->type = $type;
	}

	public function appendHeader($header)
	{
		$this->headers[] = $header;
	}

	public function write($str)
	{
		$this->body .= $str;
	}

	public function redirect($url)
	{
		$this->redirect = $url;
	}
}

class TEmailServiceTest extends TestCase
{
	use TEmailModuleCleanupTrait;

	private TEmailModule $_module;

	private TSqliteEmailStore $_store;

	private TEmail $_email;

	protected function setUp(): void
	{
		$this->_module = $this->newEmailModule();
		$this->_module->setTransport(new TCaptureTransport());
		$this->_module->setStore($this->_store = new TSqliteEmailStore(1700000000));
		$this->_module->setServiceID('email');
		$this->_module->setServiceUrl('https://www.example.com/index.php');
		$this->_email = new TEmail();
		$this->_email->setFrom('shop@example.com');
		$this->_email->setTo('ann@example.com');
		$this->_email->setSubject('Hello <Ann>');
		$this->_email->setHtml('<html><body><p>Hello Ann</p><script>alert(1)</script></body></html>');
		$this->_email->setList('news');
		$this->_email->setUsername('ann');
		$this->_email->addTrackedLink('https://www.example.com/sale?a=1&b=2');
		$this->_email->addTrackedLink('javascript:alert(1)');
		$this->_module->sendEmail($this->_email);
	}

	private function request(array $items, ?string $id = null, string $type = 'GET', ?TEmailService $service = null): TEmailServiceTestResponse
	{
		$request = new TEmailServiceTestRequest($items, $id ?? $this->_email->getTrackingId(), $type);
		$response = new TEmailServiceTestResponse();
		$service ??= $this->service();
		$service->request = $request;
		$service->response = $response;
		$service->run();
		return $response;
	}

	private function service(): TEmailService
	{
		$service = new class () extends TEmailService {
			public $request;

			public $response;

			public function getRequest()
			{
				return $this->request;
			}

			public function getResponse()
			{
				return $this->response;
			}
		};
		$service->setEmailModule($this->_module);
		return $service;
	}

	private function info(): array
	{
		return $this->_store->getEmailInfo($this->_email->getTrackingId());
	}

	public function testOpenReturnsTheGifAndCounts()
	{
		$opened = [];
		$this->_module->attachEventHandler('onOpenEmail', function ($sender, $param) use (&$opened) {
			$opened[] = [$param->getTrackingId(), $param->getInfo()['open_count']];
		});
		$response = $this->request(['open' => '1']);
		$this->assertSame('image/gif', $response->type);
		$this->assertSame(base64_decode(TEmailService::TRACKER_GIF), $response->body);
		$this->assertStringStartsWith('GIF89a', $response->body);
		$this->assertContains('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', $response->headers);
		$this->request(['open' => '1']);
		$this->assertSame(2, $this->info()['open_count']);
		$this->assertSame([[$this->_email->getTrackingId(), 1], [$this->_email->getTrackingId(), 2]], $opened);
	}

	public function testOpenOfAnUnknownEmailStillReturnsTheGif()
	{
		foreach (['0123456789abcdef0123456789abcdef', 'x', "bad id\r\n"] as $id) {
			$response = $this->request(['open' => '1'], $id);
			$this->assertSame('image/gif', $response->type);
		}
		$this->assertSame(0, $this->info()['open_count']);
	}

	public function testClickRedirectsToTheRegisteredLink()
	{
		$clicked = [];
		$this->_module->attachEventHandler('onClickEmail', function ($sender, $param) use (&$clicked) {
			$clicked[] = $param->getUrl();
		});
		$response = $this->request(['click' => '0']);
		$this->assertSame('https://www.example.com/sale?a=1&b=2', $response->redirect);
		$this->assertSame(1, $this->info()['click_count']);
		$this->assertSame(['https://www.example.com/sale?a=1&b=2'], $clicked);
	}

	public function testClickNeverRedirectsElsewhere()
	{
		foreach ([['click' => '2'], ['click' => '-1'], ['click' => 'https://evil.example'], ['click' => '0x0'], ['click' => '1']] as $items) {
			try {
				$this->request($items);
				$this->fail('the link ' . $items['click'] . ' is not a redirect target');
			} catch (THttpException $e) {
				$this->assertSame(404, $e->getStatusCode());
			}
		}
		try {
			$this->request(['click' => '0'], '0123456789abcdef0123456789abcdef');
			$this->fail('an unknown email has no links');
		} catch (THttpException $e) {
			$this->assertSame(404, $e->getStatusCode());
		}
	}

	public function testView()
	{
		$response = $this->request([]);
		$this->assertSame('text/html', $response->type);
		$this->assertSame($this->_email->getHtml(), $response->body);
		$policy = array_values(array_filter($response->headers, fn ($h) => str_starts_with($h, 'Content-Security-Policy:')))[0];
		$this->assertStringContainsString('sandbox', $policy);
		$this->assertStringContainsString("script-src 'none'", $policy);
		$this->assertStringContainsString("form-action 'none'", $policy);
		$this->assertContains('Referrer-Policy: no-referrer', $response->headers);
		$this->assertContains('X-Content-Type-Options: nosniff', $response->headers);
		$this->assertContains('Cache-Control: private, no-store', $response->headers);
	}

	public function testViewOfATextEmailEscapesIt()
	{
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$email->setSubject('A <b>subject</b>');
		$email->setText('1 < 2 & <script>x</script>');
		$this->_module->sendEmail($email);
		$response = $this->request([], $email->getTrackingId());
		$this->assertStringContainsString('<title>A &lt;b&gt;subject&lt;/b&gt;</title>', $response->body);
		$this->assertStringContainsString('<pre>1 &lt; 2 &amp; &lt;script&gt;x&lt;/script&gt;</pre>', $response->body);
		$this->assertStringContainsString('<html lang="', $response->body);
		$this->assertStringContainsString('<main>', $response->body);
	}

	public function testViewErrors()
	{
		try {
			$this->request([], '0123456789abcdef0123456789abcdef');
			$this->fail('an unknown email is not found');
		} catch (THttpException $e) {
			$this->assertSame(404, $e->getStatusCode());
		}
		$service = $this->service();
		$service->setShowEmails('false');
		$this->assertFalse($service->getShowEmails());
		try {
			$this->request([], null, 'GET', $service);
			$this->fail('viewing is turned off');
		} catch (THttpException $e) {
			$this->assertSame(404, $e->getStatusCode());
		}
	}

	public function testSecureEmailIsForbiddenToOthers()
	{
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$email->setText('Private');
		$email->setSecure(true);
		$email->setUsername('ann');
		$this->_module->sendEmail($email);
		try {
			$this->request([], $email->getTrackingId());
			$this->fail('a secure email is not shown to a guest');
		} catch (THttpException $e) {
			$this->assertSame(403, $e->getStatusCode());
		}
	}

	public function testUnsubscribeConfirmsOnGetAndActsOnPost()
	{
		$events = [];
		$this->_module->attachEventHandler('onUnsubscribe', function ($sender, $param) use (&$events) {
			$events[] = [$param->getTrackingId(), $param->getSuppressed(), $param->getInfo()['list_name']];
		});
		$id = $this->_email->getTrackingId();
		$response = $this->request(['unsubscribe' => '1']);
		$this->assertStringContainsString('<form method="post" action="https://www.example.com/index.php?email=' . $id . '&amp;unsubscribe=1">', $response->body);
		$this->assertStringContainsString('<button type="submit">', $response->body);
		$this->assertStringContainsString('<h1>', $response->body);
		$this->assertNull($this->info()['unsubscribed_time'], 'a GET does not unsubscribe');
		$this->assertSame([], $this->_store->getSuppressions());

		$response = $this->request(['unsubscribe' => '1'], null, 'POST');
		$this->assertStringContainsString('<h1>', $response->body);
		$this->assertSame(1700000000, $this->info()['unsubscribed_time']);
		$this->assertSame(['ann@example.com'], $this->_store->getSuppressed(['ann@example.com'], 'news'));
		$this->assertSame([[$id, 'ann@example.com', 'news']], $events);

		$next = new TEmail();
		$next->setFrom('shop@example.com');
		$next->setTo('ann@example.com');
		$next->setText('More news');
		$next->setList('news');
		$this->assertSame(TEmailStatus::Suppressed, $this->_module->sendEmail($next));
	}

	public function testUnsubscribeOfAnUnknownEmail()
	{
		foreach (['GET', 'POST'] as $type) {
			try {
				$this->request(['unsubscribe' => '1'], '0123456789abcdef0123456789abcdef', $type);
				$this->fail('an unknown email cannot be unsubscribed');
			} catch (THttpException $e) {
				$this->assertSame(404, $e->getStatusCode());
			}
		}
	}

	public function testEmailModuleIsRequired()
	{
		$service = new TEmailService();
		$service->init(null);
		$this->expectException(TConfigurationException::class);
		$service->getEmailModule();
	}
}
