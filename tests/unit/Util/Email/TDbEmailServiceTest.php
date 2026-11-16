<?php

namespace Prado\Test\Unit\Util\Email;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Util\Email\TDbEmailService;
use Prado\Util\Email\TEmail;

class TDbEmailServiceTestRequest
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

class TDbEmailServiceTestResponse
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

class TDbEmailServiceTestService extends TDbEmailService
{
	public $request;
	public TDbEmailServiceTestResponse $response;

	public function getRequest()
	{
		return $this->request;
	}

	public function getResponse()
	{
		return $this->response;
	}
}

class TDbEmailServiceTest extends TestCase
{
	private TDbEmailModuleTestModule $module;
	private TEmail $email;
	private int $link;

	protected function setUp(): void
	{
		$this->module = new TDbEmailModuleTestModule();
		$this->module->setServiceID('email');
		$this->module->setServiceUrl('https://example.com/index.php');
		$this->email = new TEmail();
		$this->email->setTo('ann@example.com');
		$this->email->setFrom('app@example.com');
		$this->email->setSubject('Hello');
		$this->email->setBody('<html><body>Hi {{NAME}}</body></html>');
		$this->link = $this->email->addTrackedLink('https://example.com/a');
		$this->module->sendEmail($this->email, ['NAME' => 'Ann']);
	}

	private function run_(array $items, string $type = 'GET', ?string $trackingId = null): TDbEmailServiceTestService
	{
		$service = new TDbEmailServiceTestService();
		$service->setEmailerModule($this->module);
		$service->request = new TDbEmailServiceTestRequest($items, $trackingId ?? $this->email->getTrackingId(), $type);
		$service->response = new TDbEmailServiceTestResponse();
		$service->run();
		return $service;
	}

	public function testOpenReturnsGif()
	{
		$service = $this->run_(['open' => 1]);
		$this->assertSame('image/gif', $service->response->type);
		$this->assertSame(base64_decode(TDbEmailService::TRACKER_GIF), $service->response->body);
		$this->assertSame(1, (int) $this->module->getEmailByTrackingId($this->email->getTrackingId())['open_count']);
	}

	public function testClickRedirectsToRegisteredLinkOnly()
	{
		$this->assertSame('https://example.com/a', $this->run_(['click' => $this->link])->response->redirect);
		$this->expectException(THttpException::class);
		$this->run_(['click' => 99]);
	}

	public function testViewShowsRenderedEmailWithoutScripts()
	{
		$service = $this->run_([]);
		$this->assertSame('<html><body>Hi Ann</body></html>', $service->response->body);
		$this->assertStringContainsString("script-src 'none'", implode("\n", $service->response->headers));
	}

	public function testUnknownEmailIs404()
	{
		$this->expectException(THttpException::class);
		$this->run_([], 'GET', 'unknown');
	}

	public function testSecureEmailNeedsItsOwner()
	{
		$email = new TEmail();
		$email->setTo('bob@example.com');
		$email->setFrom('app@example.com');
		$email->setBody('secret');
		$email->setSecure(true);
		$email->setUsername('bob');
		$this->module->sendEmail($email, null);
		try {
			$this->run_([], 'GET', $email->getTrackingId());
			$this->fail('Expected THttpException');
		} catch (THttpException $e) {
			$this->assertSame(403, $e->getStatusCode());
		}
	}

	public function testUnsubscribeConfirmsThenUnsubscribesOnPost()
	{
		$get = $this->run_(['unsubscribe' => 1]);
		$this->assertStringContainsString('<form method="post"', $get->response->body);
		$this->assertStringContainsString('<html lang=', $get->response->body);
		$this->assertNull($this->module->getEmailByTrackingId($this->email->getTrackingId())['unsubscribed_time']);

		$post = $this->run_(['unsubscribe' => 1, 'List-Unsubscribe' => 'One-Click'], 'POST');
		$this->assertStringContainsString('Unsubscribed', $post->response->body);
		$this->assertNotNull($this->module->getEmailByTrackingId($this->email->getTrackingId())['unsubscribed_time']);
	}

	public function testHiddenEmailsAre404()
	{
		$service = new TDbEmailServiceTestService();
		$service->setEmailerModule($this->module);
		$service->setShowEmails(false);
		$service->request = new TDbEmailServiceTestRequest([], $this->email->getTrackingId());
		$service->response = new TDbEmailServiceTestResponse();
		$this->expectException(THttpException::class);
		$service->run();
	}

	public function testBadAssetReferenceIs404()
	{
		$this->expectException(THttpException::class);
		$this->run_(['file' => '../../etc/passwd']);
	}

	public function testEmailerModuleMustBeDbEmailModule()
	{
		$service = new TDbEmailService();
		$this->assertNull($service->getEmailerModule());
		$service->setEmailerModule('no-such-module');
		$this->expectException(TConfigurationException::class);
		$service->getEmailerModule();
	}
}
