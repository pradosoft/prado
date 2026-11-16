<?php

namespace Prado\Test\Unit\Util\Email\UI;

use PHPUnit\Framework\TestCase;
use Prado\Util\Email\UI\TEmailPage;
use Prado\Util\Email\UI\TEmailPublishStyle;
use Prado\Util\Email\UI\TEmailTemplate;

class TEmailTemplateTest extends TestCase
{
	public function testPagePublishStyles()
	{
		$page = new TEmailPage();
		$page->setPublishStyle(TEmailPublishStyle::Embed);
		$this->assertStringStartsWith('data:', $page->publishFilePath(__FILE__));

		$page->setPublishStyle(TEmailPublishStyle::Attachment);
		$url = $page->publishEmailFile(__FILE__);
		$this->assertMatchesRegularExpression('/^cid:[0-9a-f]{8}\.TEmailTemplateTest\.php$/', $url);
		$this->assertSame(__FILE__, $page->getAttachments()[substr($url, 4)]['path']);
	}

	public function testUrlStyleNeedsAbsoluteUrl()
	{
		$page = new TEmailPage();
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$page->publishEmailFile(__FILE__);
	}

	public function testTemplateIsTTemplate()
	{
		$this->assertTrue(is_subclass_of(TEmailTemplate::class, \Prado\Web\UI\TTemplate::class));
	}
}
