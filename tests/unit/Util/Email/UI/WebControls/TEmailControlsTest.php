<?php

namespace Prado\Test\Unit\Util\Email\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Util\Email\TDbEmailModuleTestModule;
use Prado\Util\Email\UI\TEmailPage;
use Prado\Util\Email\UI\WebControls\TEmailAttachment;
use Prado\Util\Email\UI\WebControls\TEmailButton;
use Prado\Util\Email\UI\WebControls\TEmailImage;
use Prado\Util\Email\UI\WebControls\TEmailPreheader;
use Prado\Util\Email\UI\WebControls\TEmailTrackedLink;
use Prado\Util\Email\UI\WebControls\TEmailTrackerImage;
use Prado\Util\Email\UI\WebControls\TEmailUnsubscribeLink;
use Prado\Util\Email\UI\WebControls\TEmailViewLink;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TControl;

class TEmailControlsTest extends TestCase
{
	private TEmailPage $page;
	private string $id;

	protected function setUp(): void
	{
		$module = new TDbEmailModuleTestModule();
		$module->setServiceID('email');
		$module->setServiceUrl('https://example.com/index.php');
		$this->page = new TEmailPage();
		$this->page->setEmailerModule($module);
		$this->id = $this->page->getTrackingId();
	}

	private function render(TControl $control, bool $preRender = false): string
	{
		$control->setPage($this->page);
		if ($preRender) {
			$control->onPreRender(null);
		}
		$writer = new THtmlWriter(new TTextWriter());
		$control->renderControl($writer);
		return $writer->flush();
	}

	private function url(string $query = ''): string
	{
		return 'https://example.com/index.php?email=' . $this->id . $query;
	}

	public function testTrackerImage()
	{
		$html = $this->render(new TEmailTrackerImage());
		$this->assertStringContainsString('src="' . $this->url('&open=1') . '"', $html);
		$this->assertStringContainsString('alt=""', $html);
		$this->assertStringContainsString('role="presentation"', $html);
	}

	public function testTrackerImageWithoutServiceRendersNothing()
	{
		$this->page->getEmailerModule()->setServiceID(null);
		$this->assertSame('', $this->render(new TEmailTrackerImage()));
	}

	public function testViewLink()
	{
		$html = $this->render(new TEmailViewLink());
		$this->assertStringContainsString('href="' . $this->url() . '"', $html);
		$this->assertStringContainsString('View this email in your browser', $html);
	}

	public function testTrackedLinkRegistersTarget()
	{
		$link = new TEmailTrackedLink();
		$link->setNavigateUrl('https://example.com/offer');
		$html = $this->render($link);
		$this->assertStringContainsString('href="' . $this->url('&click=0') . '"', $html);
		$this->assertStringContainsString('>https://example.com/offer</a>', $html);
		$this->assertSame(['https://example.com/offer'], $this->page->getTrackedLinks());

		$link->setTracked(false);
		$this->assertStringContainsString('href="https://example.com/offer"', $this->render($link));
	}

	public function testButtonIsPresentationTable()
	{
		$button = new TEmailButton();
		$button->setNavigateUrl('https://example.com/go');
		$button->setText('Go');
		$button->setTracked(false);
		$html = $this->render($button);
		$this->assertStringStartsWith('<table role="presentation"', $html);
		$this->assertStringContainsString('bgcolor="#1a73e8"', $html);
		$this->assertStringContainsString('href="https://example.com/go"', $html);
		$this->assertStringContainsString('display:inline-block', $html);
		$this->assertStringContainsString('>Go</a>', $html);
	}

	public function testUnsubscribeLinkSetsOneClickHeaders()
	{
		$html = $this->render(new TEmailUnsubscribeLink(), true);
		$this->assertStringContainsString('href="' . $this->url('&unsubscribe=1') . '"', $html);
		$this->assertStringContainsString('>Unsubscribe</a>', $html);
		$headers = $this->page->getHeaders();
		$this->assertSame('<' . $this->url('&unsubscribe=1') . '>', $headers['List-Unsubscribe']);
		$this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
	}

	public function testUnsubscribeLinkWithCustomUrl()
	{
		$link = new TEmailUnsubscribeLink();
		$link->setNavigateUrl('https://example.com/prefs');
		$link->setMailto('unsub@example.com');
		$this->render($link, true);
		$headers = $this->page->getHeaders();
		$this->assertSame('<https://example.com/prefs>, <mailto:unsub@example.com>', $headers['List-Unsubscribe']);
		$this->assertArrayNotHasKey('List-Unsubscribe-Post', $headers);
	}

	public function testPreheaderIsHidden()
	{
		$preheader = new TEmailPreheader();
		$preheader->setText('Your <receipt>');
		$html = $this->render($preheader);
		$this->assertStringContainsString('aria-hidden="true"', $html);
		$this->assertStringContainsString('display:none', $html);
		$this->assertStringContainsString('Your &lt;receipt&gt;', $html);
		$this->assertStringContainsString(TEmailPreheader::PREVIEW_PAD, $html);
		$preheader->setPadPreview(false);
		$this->assertStringNotContainsString(TEmailPreheader::PREVIEW_PAD, $this->render($preheader));
		$this->assertSame('', $this->render(new TEmailPreheader()));
	}

	public function testImageNeedsAlternateText()
	{
		$image = new TEmailImage();
		$image->setImageUrl('https://example.com/logo.png');
		$image->setPage($this->page);
		try {
			$image->onPreRender(null);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
		}
		$image->setDecorative(true);
		$image->setAlternateText('ignored');
		$this->assertStringContainsString('alt=""', $this->render($image, true));
	}

	public function testImageFileEmbedsAsInlineAttachment()
	{
		$this->page->setPublishStyle('Attachment');
		$image = new TEmailImage();
		$image->setImageFile(__FILE__);
		$image->setAlternateText('Logo');
		$html = $this->render($image, true);
		$this->assertMatchesRegularExpression('/src="cid:[0-9a-f]{8}\.TEmailControlsTest\.php"/', $html);
		$attachment = current($this->page->getAttachments());
		$this->assertSame(__FILE__, $attachment['path']);
		$this->assertArrayHasKey('cid', $attachment);
	}

	public function testAttachmentAddsFileAndRendersNothing()
	{
		$attachment = new TEmailAttachment();
		$attachment->setFile(__FILE__);
		$attachment->setName('test.php');
		$this->assertSame('', $this->render($attachment, true));
		$this->assertSame(__FILE__, $this->page->getAttachments()['test.php']['path']);

		$missing = new TEmailAttachment();
		$missing->setFile('no-such-file.pdf');
		$missing->setPage($this->page);
		$this->expectException(TConfigurationException::class);
		$missing->onPreRender(null);
	}

	public function testControlsNeedAnEmailPage()
	{
		$this->expectException(TConfigurationException::class);
		(new TEmailViewLink())->getEmailPage();
	}
}
