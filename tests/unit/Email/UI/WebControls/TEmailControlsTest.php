<?php

namespace Prado\Test\Unit\Email\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\UI\TEmailBody;
use Prado\Email\UI\TEmailPublishStyle;
use Prado\Email\UI\WebControls\TEmailAttachment;
use Prado\Email\UI\WebControls\TEmailButton;
use Prado\Email\UI\WebControls\TEmailImage;
use Prado\Email\UI\WebControls\TEmailPreheader;
use Prado\Email\UI\WebControls\TEmailTrackedLink;
use Prado\Email\UI\WebControls\TEmailTrackerImage;
use Prado\Email\UI\WebControls\TEmailUnsubscribeLink;
use Prado\Email\UI\WebControls\TEmailViewLink;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\TEmailModuleCleanupTrait;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;

class TEmailControlsTest extends TestCase
{
	use TEmailModuleCleanupTrait;

	private const SERVICE = 'https://www.example.com/index.php?email={{email:tracking-id}}';

	private TEmailBody $_body;

	private TEmail $_email;

	private TEmailModule $_module;

	protected function setUp(): void
	{
		$this->_module = $this->newEmailModule();
		$this->_module->setStore($this->newEmailStore());
		$this->_module->setServiceID('email');
		$this->_module->setServiceUrl('https://www.example.com/index.php');
		$this->_email = new TEmail();
		$this->_body = new TEmailBody();
		$this->_body->setEmailModule($this->_module);
		$this->_body->setEmail($this->_email);
	}

	private function render(TControl $control, ?TPage $page = null): string
	{
		$control->setPage($page ?? $this->_body);
		$control->onPreRender(null);
		$writer = new THtmlWriter(new TTextWriter());
		$control->renderControl($writer);
		return $writer->flush();
	}

	private function withoutTracking(): void
	{
		$this->_module->setStore(null);
	}

	public function testTrackerImage()
	{
		$html = $this->render(new TEmailTrackerImage());
		$this->assertStringContainsString('src="' . self::SERVICE . '&open=1"', $html);
		$this->assertStringContainsString('alt=""', $html);
		$this->assertStringContainsString('role="presentation"', $html);
		$this->assertStringContainsString('width="1"', $html);
	}

	public function testTrackerImageWithoutTrackingRendersNothing()
	{
		$this->withoutTracking();
		$this->assertSame('', $this->render(new TEmailTrackerImage()));
		$this->assertSame('', $this->render(new TEmailTrackerImage(), new TPage()));
	}

	public function testViewLink()
	{
		$link = new TEmailViewLink();
		$this->assertSame('<a href="' . self::SERVICE . '">View this email in your browser</a>', $this->render($link));
		$custom = new TEmailViewLink();
		$custom->setText('Open online');
		$this->assertStringContainsString('>Open online</a>', $this->render($custom));
		$this->withoutTracking();
		$this->assertSame('', $this->render(new TEmailViewLink()));
	}

	public function testTrackedLinkRegistersItsTarget()
	{
		$link = new TEmailTrackedLink();
		$link->setNavigateUrl('https://www.example.com/a?x=1&y={{ID}}');
		$link->setText('Go');
		$this->assertTrue($link->getTracked());
		$this->assertSame('<a href="' . self::SERVICE . '&click=0">Go</a>', $this->render($link));
		$this->assertSame('https://www.example.com/a?x=1&y={{ID}}', $link->getNavigateUrl(), 'the NavigateUrl stays the target');
		$this->assertSame(['https://www.example.com/a?x=1&y={{ID}}'], $this->_email->getTrackedLinks());

		$same = new TEmailTrackedLink();
		$same->setNavigateUrl('https://www.example.com/a?x=1&y={{ID}}');
		$other = new TEmailTrackedLink();
		$other->setNavigateUrl('https://www.example.com/b');
		$this->assertStringContainsString('&click=0"', $this->render($same));
		$this->assertStringContainsString('&click=1"', $this->render($other));
		$this->assertCount(2, $this->_email->getTrackedLinks());
	}

	public function testUntrackedLinks()
	{
		$off = new TEmailTrackedLink();
		$off->setNavigateUrl('https://www.example.com/a');
		$off->setTracked('false');
		$off->setText('Go');
		$this->assertSame('<a href="https://www.example.com/a">Go</a>', $this->render($off));

		$mailto = new TEmailTrackedLink();
		$mailto->setNavigateUrl('mailto:help@example.com');
		$mailto->setText('Write');
		$this->assertSame('<a href="mailto:help@example.com">Write</a>', $this->render($mailto), 'only http and https links are tracked');
		$this->assertSame([], $this->_email->getTrackedLinks());

		$this->withoutTracking();
		$plain = new TEmailTrackedLink();
		$plain->setNavigateUrl('https://www.example.com/a');
		$plain->setText('Go');
		$this->assertSame('<a href="https://www.example.com/a">Go</a>', $this->render($plain));
	}

	public function testLinkWithoutTextShowsItsUrl()
	{
		$link = new TEmailTrackedLink();
		$link->setNavigateUrl('https://www.example.com/a?x=1&y=2');
		$this->assertStringContainsString('>https://www.example.com/a?x=1&amp;y=2</a>', $this->render($link));
	}

	public function testButtonIsAPresentationTableAroundALink()
	{
		$button = new TEmailButton();
		$button->setNavigateUrl('https://www.example.com/shop');
		$button->setText('Shop now');
		$html = $this->render($button);
		$this->assertStringStartsWith('<table role="presentation"', $html);
		$this->assertStringContainsString('bgcolor="#1a73e8"', $html);
		$this->assertStringContainsString('href="' . self::SERVICE . '&click=0"', $html);
		$this->assertStringContainsString('>Shop now</a>', $html);
		$this->assertStringContainsString('color:#ffffff', $html);
		$this->assertStringContainsString('padding:12px 24px', $html);
		$this->assertSame(1, substr_count($html, '<a '));
	}

	public function testUnsubscribeLinkUsesTheServiceAndSetsOneClickHeaders()
	{
		$link = new TEmailUnsubscribeLink();
		$url = self::SERVICE . '&unsubscribe=1';
		$this->assertSame('<a href="' . $url . '">Unsubscribe</a>', $this->render($link));
		$this->assertTrue($link->getIsOneClick());
		$this->assertSame('<' . $url . '>', $this->_email->getHeader('List-Unsubscribe'));
		$this->assertSame('List-Unsubscribe=One-Click', $this->_email->getHeader('List-Unsubscribe-Post'));
		$this->_email->finalize();
		$this->assertSame('<https://www.example.com/index.php?email=' . $this->_email->getTrackingId() . '&unsubscribe=1>', $this->_email->getHeader('List-Unsubscribe'));
	}

	public function testUnsubscribeLinkWithItsOwnUrl()
	{
		$link = new TEmailUnsubscribeLink();
		$link->setNavigateUrl('https://www.example.com/leave');
		$link->setMailto('leave@example.com');
		$link->setText('Leave');
		$this->assertSame('<a href="https://www.example.com/leave">Leave</a>', $this->render($link));
		$this->assertFalse($link->getIsOneClick());
		$this->assertSame('<https://www.example.com/leave>, <mailto:leave@example.com>', $this->_email->getHeader('List-Unsubscribe'));
		$this->assertNull($this->_email->getHeader('List-Unsubscribe-Post'));

		$oneClick = new TEmailUnsubscribeLink();
		$oneClick->setNavigateUrl('https://www.example.com/leave');
		$oneClick->setOneClick(true);
		$this->render($oneClick);
		$this->assertSame('List-Unsubscribe=One-Click', $this->_email->getHeader('List-Unsubscribe-Post'));
	}

	public function testUnsubscribeLinkWithoutHeaderOrTracking()
	{
		$link = new TEmailUnsubscribeLink();
		$link->setAddListUnsubscribeHeader('false');
		$this->render($link);
		$this->assertNull($this->_email->getHeader('List-Unsubscribe'));
		$this->withoutTracking();
		$this->assertSame('', $this->render(new TEmailUnsubscribeLink()));
		$this->assertNull($this->_email->getHeader('List-Unsubscribe'));
	}

	public function testPreheaderIsHiddenAndEncoded()
	{
		$preheader = new TEmailPreheader();
		$this->assertSame('', $this->render($preheader));
		$preheader->setText('Sale & <more>');
		$html = $this->render($preheader);
		$this->assertStringContainsString('aria-hidden="true"', $html);
		$this->assertStringContainsString('display:none', $html);
		$this->assertStringContainsString('Sale &amp; &lt;more&gt;', $html);
		$this->assertSame(TEmailPreheader::PREVIEW_PAD_COUNT, substr_count($html, TEmailPreheader::PREVIEW_PAD));
		$preheader->setPadPreview(false);
		$this->assertStringNotContainsString(TEmailPreheader::PREVIEW_PAD, $this->render($preheader));
	}

	public function testImageNeedsAlternateText()
	{
		$image = new TEmailImage();
		$image->setImageUrl('https://www.example.com/logo.png');
		try {
			$this->render($image);
			$this->fail('an image needs alternate text');
		} catch (TConfigurationException $e) {
		}
		$image->setAlternateText('Logo of Shop');
		$this->assertStringContainsString('alt="Logo of Shop"', $this->render($image));
		$decorative = new TEmailImage();
		$decorative->setImageUrl('https://www.example.com/line.png');
		$decorative->setDecorative(true);
		$this->assertStringContainsString('alt=""', $this->render($decorative));
	}

	public function testImageFileFollowsThePublishStyle()
	{
		$file = realpath(__DIR__ . '/../../Fixtures/Emails/logo.png');
		$this->_body->setPublishStyle(TEmailPublishStyle::Attachment);
		$image = new TEmailImage();
		$image->setImageFile($file);
		$image->setAlternateText('Logo');
		$html = $this->render($image);
		$files = $this->_email->getInlineFiles();
		$this->assertCount(1, $files);
		$this->assertSame($file, $files[0]->getPath());
		$this->assertStringContainsString('src="cid:' . $files[0]->getContentId() . '"', $html);

		$this->_body->setPublishStyle(TEmailPublishStyle::Embed);
		$embedded = new TEmailImage();
		$embedded->setImageFile($file);
		$embedded->setAlternateText('Logo');
		$this->assertStringContainsString('src="data:image/png;base64,', $this->render($embedded));

		$missing = new TEmailImage();
		$missing->setImageFile('no-such-file.png');
		$missing->setAlternateText('Logo');
		$this->expectException(TConfigurationException::class);
		$this->render($missing);
	}

	public function testAttachmentAddsTheFileAndRendersNothing()
	{
		$attachment = new TEmailAttachment();
		$attachment->setFile(__FILE__);
		$attachment->setName('report.txt');
		$attachment->setMime('text/plain');
		$this->assertSame('', $this->render($attachment));
		$files = $this->_email->getAttachments();
		$this->assertCount(1, $files);
		$this->assertSame('report.txt', $files[0]->getName());
		$this->assertSame('text/plain', $files[0]->getMimeType());
		$this->assertSame(__FILE__, $files[0]->getPath());
		$this->assertSame('', $this->render(new TEmailAttachment()));
		$this->assertCount(1, $this->_email->getAttachments());
	}

	public function testControlsNeedAnEmailBody()
	{
		$attachment = new TEmailAttachment();
		$attachment->setFile(__FILE__);
		try {
			$this->render($attachment, new TPage());
			$this->fail('an attachment control needs an email body');
		} catch (TConfigurationException $e) {
		}
		$link = new TEmailTrackedLink();
		$link->setNavigateUrl('https://www.example.com/a');
		$link->setText('Go');
		$this->assertSame('<a href="https://www.example.com/a">Go</a>', $this->render($link, new TPage()), 'a tracked link on a web page is a plain link');
	}

	public function testTrackingUrlNeedsAModule()
	{
		$body = new TEmailBody();
		$image = new TEmailTrackerImage();
		$image->setPage($body);
		$this->assertFalse($image->getHasTracking());
		$this->expectException(TConfigurationException::class);
		$image->getEmailTrackingUrl();
	}
}
