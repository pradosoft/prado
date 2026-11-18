<?php

namespace Prado\Test\Unit\Email\UI;

use PHPUnit\Framework\TestCase;
use Prado\Email\IEmailBody;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\UI\TEmailBody;
use Prado\Email\UI\TEmailBodyTemplate;
use Prado\Email\UI\TEmailPublishStyle;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;
use Prado\Web\UI\TPage;

class TEmailBodyTest extends TestCase
{
	use \Prado\Test\Unit\Email\TEmailModuleCleanupTrait;

	private function module(): TEmailModule
	{
		$module = $this->newEmailModule();
		$module->setBasePath(__DIR__ . '/../Fixtures/Emails');
		$module->setTransport(new TCaptureTransport());
		return $module;
	}

	public function testRendersWithoutAPageService()
	{
		$this->assertNotInstanceOf(\Prado\Web\Services\TPageService::class, Prado::getApplication()->getService());
		$body = new TEmailBody();
		$this->assertInstanceOf(TPage::class, $body);
		$this->assertInstanceOf(IEmailBody::class, $body);
		$this->assertFalse($body->getEnableViewState());
		$this->assertFalse($body->getIsPostBack());
		$this->assertFalse($body->getIsCallback());
		$this->assertTrue($body->getIsValid());
		$this->assertFalse($body->getAutoGlobalListen());
		$this->assertNull($body->getEmailModule());
		$this->assertSame('', $body->getEmailPath());
		$this->assertInstanceOf(TEmail::class, $body->getEmail());
		$email = new TEmail();
		$body->renderEmail($email);
		$this->assertSame($email, $body->getEmail());
		$this->assertNull($email->getHtml(), 'a body without a template leaves the Html alone');
	}

	public function testCreateEmailFromTemplateAndText()
	{
		$module = $this->module();
		$email = $module->createEmail('Simple');
		$body = $email->getBody();
		$this->assertSame(TEmailBody::class, $body::class);
		$this->assertSame($module, $body->getEmailModule());
		$this->assertSame('Simple', $body->getEmailPath());
		$this->assertSame($email, $body->getEmail());
		$this->assertInstanceOf(TEmailBodyTemplate::class, $body->getTemplate());
		$this->assertStringEndsWith('Simple.text', $body->getTextFile());
		$this->assertSame('Hello {{NAME}}', $email->getSubject(), 'the Email. directive is applied when the email is created');
		$this->assertSame('news', $email->getList());
		$this->assertNull($email->getHtml());

		$email->render();
		$html = $email->getHtml();
		$this->assertStringContainsString('<p class="note" style="color: #333333;">Hello {{NAME}}</p>', $html);
		$this->assertStringContainsString('Label text', $html);
		$this->assertSame('Hello {{NAME}} in text', $email->getText());
	}

	public function testApplicationCodeOverridesTheDirective()
	{
		$module = $this->module();
		$email = $module->createEmail('Simple');
		$email->setSubject('Custom subject');
		$email->setText('Custom text');
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$module->sendEmail($email);
		$this->assertSame('Custom subject', $email->getSubject());
		$this->assertSame('Custom text', $email->getText());
	}

	public function testBodyClassWithTemplate()
	{
		$module = $this->module();
		$email = $module->createEmail('Order/Shipped');
		$body = $email->getBody();
		$this->assertSame('Application\\Emails\\Order\\Shipped', $body::class);
		$this->assertSame('Order.Shipped', $body->getEmailPath());
		$body->setNumber(1042);
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$email->setData(['NAME' => 'Ann <ann>']);
		$module->sendEmail($email);
		$this->assertSame(1, $body->loads);
		$this->assertSame('<p>Order 1042 shipped to Ann &lt;ann&gt;.</p>', trim($email->getHtml()));
		$this->assertSame('Order 1042 shipped to Ann <ann>.', $email->getText());
		$this->assertSame('1042', $email->getHeader('X-Order'));
		$this->assertSame('Order shipped', $email->getSubject());
	}

	public function testBodyClassWithoutTemplate()
	{
		$module = $this->module();
		$email = $module->createEmail('ClassOnly');
		$this->assertSame('Application\\Emails\\ClassOnly', $email->getBody()::class);
		$email->render();
		$this->assertSame('From the class', $email->getSubject());
		$this->assertSame('<p>Built in code</p>', $email->getHtml());
	}

	public function testMasterClassRendersOutsideAPageService()
	{
		$email = $this->module()->createEmail('Help.Welcome');
		$email->render();
		$this->assertSame('<html><body><div id="layout"><p>Welcome inside the layout.</p></div></body></html>', preg_replace('/>\s+</', '><', trim($email->getHtml())));
	}

	public function testUnknownEmailThrows()
	{
		$module = $this->module();
		foreach (['NoSuchEmail', '../Simple', 'Simple.email', ''] as $path) {
			try {
				$module->createEmail($path);
				$this->fail("the email path '{$path}' is unknown");
			} catch (TConfigurationException $e) {
				$this->assertStringContainsString('no template', $e->getMessage());
			}
		}
	}

	public function testClassThatIsNotABodyThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->module()->createEmail('NotABody');
	}

	public function testAdditionalEmailPaths()
	{
		$module = $this->newEmailModule();
		$module->setBasePath(__DIR__);
		$asked = [];
		$module->attachEventHandler('onAdditionalEmailPaths', function ($sender, $emailPath) use (&$asked) {
			$asked[] = $emailPath;
			return __DIR__ . '/../Fixtures/Emails/' . strtr($emailPath, '.', '/');
		});
		$email = $module->createEmail('Order.Shipped');
		$this->assertSame(['Order.Shipped'], $asked);
		$this->assertSame('Order shipped', $email->getSubject());
	}

	public function testPostbackControlIsRefused()
	{
		$email = $this->module()->createEmail('Postback');
		try {
			$email->render();
			$this->fail('a postback control cannot be in an email');
		} catch (TConfigurationException $e) {
			$this->assertStringContainsString('TButton', $e->getMessage());
		}
	}

	public function testPublishStyleEmbed()
	{
		$email = $this->module()->createEmail('WithImage');
		$email->getBody()->setPublishStyle('embed');
		$this->assertSame(TEmailPublishStyle::Embed, $email->getBody()->getPublishStyle());
		$email->render();
		$this->assertStringContainsString('src="data:image/png;base64,' . base64_encode("\x89PNG\r\n\x1a\nfixture") . '"', $email->getHtml());
		$this->assertSame([], $email->getFiles());
	}

	public function testPublishStyleAttachment()
	{
		$email = $this->module()->createEmail('WithImage');
		$email->getBody()->setPublishStyle(TEmailPublishStyle::Attachment);
		$email->render();
		$files = $email->getInlineFiles();
		$this->assertCount(1, $files);
		$this->assertSame('logo.png', $files[0]->getName());
		$this->assertStringContainsString('src="cid:' . $files[0]->getContentId() . '"', $email->getHtml());
		$this->assertSame("\x89PNG\r\n\x1a\nfixture", $files[0]->getContent());
	}

	public function testPublishStyleUrlNeedsAnAbsoluteUrl()
	{
		$body = new TEmailBody();
		$this->assertSame(TEmailPublishStyle::URL, $body->getPublishStyle());
		$this->assertNull($body->getAbsoluteUrl());
		$body->setAbsoluteUrl('https://www.example.com/');
		$this->assertSame('https://www.example.com', $body->getAbsoluteUrl());
		$body->setAbsoluteUrl('');
		$this->expectException(TConfigurationException::class);
		$body->publishEmailFile(__FILE__);
	}

	public function testInlineStylesCanBeTurnedOff()
	{
		$email = $this->module()->createEmail('Simple');
		$this->assertTrue($email->getBody()->getInlineStyles());
		$email->getBody()->setInlineStyles('false');
		$email->render();
		$this->assertStringContainsString('<p class="note">Hello {{NAME}}</p>', $email->getHtml());
	}

	public function testConfigurationRulesApplyToTheBodyAndTheEmail()
	{
		$module = $this->module();
		$module->getConfiguration()->load(['properties' => ['Email.From' => 'config@example.com', 'InlineStyles' => 'false'], 'rules' => ['Simple' => ['Email.Subject' => 'From the rule', 'Email.Secure' => 'true']]]);
		$email = $module->createEmail('Simple');
		$this->assertSame('config@example.com', $email->getFrom()->getAddress());
		$this->assertTrue($email->getSecure());
		$this->assertFalse($email->getBody()->getInlineStyles());
		$this->assertSame('Hello {{NAME}}', $email->getSubject(), 'the template directive overrides the rule');
	}
}
