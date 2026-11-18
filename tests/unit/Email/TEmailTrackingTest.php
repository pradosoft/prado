<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailService;
use Prado\Email\TEmailStatus;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;
use Prado\Xml\TXmlDocument;

class TEmailTrackingTest extends TestCase
{
	use TEmailModuleCleanupTrait;

	private const SERVICE = 'https://www.example.com/index.php';

	private TCaptureTransport $_transport;

	private TSqliteEmailStore $_store;

	private function module(): TEmailModule
	{
		$module = $this->newEmailModule();
		$module->setBasePath(__DIR__ . '/Fixtures/Emails');
		$module->setFrom('Shop <shop@example.com>');
		$module->setTransport($this->_transport = new TCaptureTransport());
		$module->setStore($this->_store = new TSqliteEmailStore(1700000000));
		$module->setServiceID('email');
		$module->setServiceUrl(self::SERVICE);
		return $module;
	}

	public function testTrackingUrl()
	{
		$module = $this->newEmailModule();
		$this->assertFalse($module->getHasTracking());
		$this->assertNull($module->getServiceID());
		try {
			$module->getTrackingUrl();
			$this->fail('a tracking URL needs a service');
		} catch (TConfigurationException $e) {
		}
		$module->setServiceID('mail');
		$this->assertFalse($module->getHasTracking(), 'tracking needs a store too');
		try {
			$module->getServiceUrl();
			$this->fail('on the command line the ServiceUrl must be set');
		} catch (TConfigurationException $e) {
		}
		$module->setServiceUrl('https://www.example.com/index.php?lang=en');
		$module->setStore(new TSqliteEmailStore());
		$this->assertTrue($module->getHasTracking());
		$this->assertSame('https://www.example.com/index.php?lang=en&mail={{email:tracking-id}}', $module->getTrackingUrl());
		$this->assertSame('https://www.example.com/index.php?lang=en&mail=abc&click=2&x=a%20b', $module->getTrackingUrl('abc', ['click' => 2, 'x' => 'a b']));
	}

	public function testServiceElementRegistersTheService()
	{
		$xml = new TXmlDocument();
		$xml->loadFromString('<module><service id="mailtrack" ShowEmails="false" /></module>');
		$module = $this->newEmailModule();
		$app = Prado::getApplication();
		try {
			$module->init($xml);
			$this->assertSame('mailtrack', $module->getServiceID());
			$config = $app->getRegisteredService('mailtrack');
			$this->assertSame(TEmailService::class, $config[0]);
			$this->assertSame('false', $config[1]['ShowEmails']);
			$this->assertSame($module, $config[1]['EmailModule']);
		} finally {
			$app->unregisterService('mailtrack');
		}
		$default = $this->newEmailModule();
		try {
			$default->init(['service' => []]);
			$this->assertSame(TEmailModule::DEFAULT_SERVICE_ID, $default->getServiceID());
		} finally {
			$app->unregisterService(TEmailModule::DEFAULT_SERVICE_ID);
		}
	}

	public function testMailMergeGivesEachRecipientTheirOwnLinks()
	{
		$module = $this->module();
		$count = $module->sendEach($module->createEmail('News'), [
			'ann@example.com' => ['NAME' => 'Ann', 'ID' => 'a 1'],
			'bob@example.com' => ['NAME' => 'Bob', 'ID' => 'b&2'],
		]);
		$this->assertSame(2, $count);
		[$ann, $bob] = $this->_transport->sent;
		$this->assertNotSame($ann->getTrackingId(), $bob->getTrackingId());
		$this->assertNotSame($ann->getMessageId(), $bob->getMessageId());
		foreach ([[$ann, 'Ann', $bob], [$bob, 'Bob', $ann]] as [$email, $name, $other]) {
			$base = self::SERVICE . '?email=' . $email->getTrackingId();
			$html = $email->getHtml();
			$this->assertSame("News for {$name}", $email->getSubject());
			$this->assertStringContainsString("<p>Hello {$name}</p>", $html);
			$this->assertStringContainsString('href="' . $base . '&click=0"', $html);
			$this->assertStringContainsString('href="' . $base . '&click=1"', $html);
			$this->assertStringContainsString('href="' . $base . '"', $html);
			$this->assertStringContainsString('href="' . $base . '&unsubscribe=1"', $html);
			$this->assertStringContainsString('src="' . $base . '&open=1"', $html);
			$this->assertStringNotContainsString($other->getTrackingId(), $html);
			$this->assertStringNotContainsString('{{', $html);
			$this->assertSame('<' . $base . '&unsubscribe=1>', $email->getHeader('List-Unsubscribe'));
			$this->assertSame('List-Unsubscribe=One-Click', $email->getHeader('List-Unsubscribe-Post'));
			$this->assertStringContainsString("Hello {$name}", $email->getText());
			$this->assertStringContainsString('See the sale (' . $base . '&click=0)', $email->getText());
			$this->assertStringNotContainsString('This month', $email->getText(), 'the hidden preheader is not in the text');
		}
		$this->assertSame(['https://www.example.com/sale?u=a%201', 'https://www.example.com/shop'], $ann->getTrackedLinks());
		$this->assertSame('https://www.example.com/sale?u=b%262', $bob->getTrackedLinks()[0]);

		$this->assertSame('https://www.example.com/sale?u=a%201', $module->recordClick($ann->getTrackingId(), 0));
		$this->assertTrue($module->recordOpen($ann->getTrackingId()));
		$annInfo = $this->_store->getEmailInfo($ann->getTrackingId());
		$bobInfo = $this->_store->getEmailInfo($bob->getTrackingId());
		$this->assertSame([1, 1], [$annInfo['click_count'], $annInfo['open_count']]);
		$this->assertSame([0, 0], [$bobInfo['click_count'], $bobInfo['open_count']], 'the click and open of one recipient are not counted for the other');

		$this->assertSame('bob@example.com', $module->recordUnsubscribe($bob->getTrackingId())['suppressed']);
		$this->assertNull($this->_store->getEmailInfo($ann->getTrackingId())['unsubscribed_time']);
		$again = $module->sendEach($module->createEmail('News'), ['ann@example.com' => ['NAME' => 'Ann'], 'bob@example.com' => ['NAME' => 'Bob']]);
		$this->assertSame(1, $again, 'the unsubscribed recipient is not sent the next email of the list');
		$this->assertCount(3, $this->_transport->sent);
		$this->assertSame(1, $this->_store->countEmails(null, TEmailStatus::Suppressed));
	}

	public function testTrackingWithoutStoreRecordsNothing()
	{
		$module = $this->newEmailModule();
		$this->assertFalse($module->recordOpen('0123456789abcdef'));
		$this->assertNull($module->recordClick('0123456789abcdef', 0));
		$this->assertNull($module->recordUnsubscribe('0123456789abcdef'));
		$stored = $this->module();
		$this->assertFalse($stored->recordOpen('0123456789abcdef'));
		$this->assertNull($stored->recordClick('0123456789abcdef', 0));
		$this->assertNull($stored->recordUnsubscribe('0123456789abcdef'));
	}

	public function testTemplateWithoutTrackingRendersPlainLinks()
	{
		$module = $this->newEmailModule();
		$module->setBasePath(__DIR__ . '/Fixtures/Emails');
		$module->setFrom('shop@example.com');
		$module->setTransport($transport = new TCaptureTransport());
		$email = $module->createEmail('News');
		$email->setTo('ann@example.com');
		$email->setData(['NAME' => 'Ann', 'ID' => 7]);
		$this->assertSame(TEmailStatus::Sent, $module->sendEmail($email));
		$html = $transport->sent[0]->getHtml();
		$this->assertStringContainsString('href="https://www.example.com/sale?u=7"', $html);
		$this->assertStringContainsString('href="https://www.example.com/shop"', $html);
		$this->assertStringNotContainsString('<img', $html);
		$this->assertStringNotContainsString('Unsubscribe', $html);
		$this->assertNull($email->getHeader('List-Unsubscribe'));
	}
}
