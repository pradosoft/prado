<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THyperLink;
use Prado\Web\UI\WebControls\TReferrerPolicy;

class THyperLinkTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testLinkAttributeDefaults()
	{
		$link = new THyperLink();
		$this->assertNull($link->getDownload());
		$this->assertSame('', $link->getRel());
		$this->assertSame('', $link->getHrefLang());
		$this->assertSame('', $link->getType());
		$this->assertSame(TReferrerPolicy::NotSet, $link->getReferrerPolicy());
		$this->assertSame('', $link->getPing());
		$link->setNavigateUrl('/docs');
		$link->setText('Docs');
		$this->assertSame('<a href="/docs">Docs</a>', $this->render($link));
	}

	public function testLinkAttributesRender()
	{
		$link = new THyperLink();
		$link->setNavigateUrl('/files/report.pdf');
		$link->setText('Report');
		$link->setDownload('report-2026.pdf');
		$link->setRel('nofollow');
		$link->setHrefLang('fr-CA');
		$link->setType('application/pdf');
		$link->setReferrerPolicy('NoReferrer');
		$link->setPing(" /track   /audit\n");
		$this->assertSame(
			'<a href="/files/report.pdf" download="report-2026.pdf" rel="nofollow" hreflang="fr-CA"'
			. ' type="application/pdf" referrerpolicy="no-referrer" ping="/track /audit">Report</a>',
			$this->render($link)
		);
	}

	public function testDownloadStates()
	{
		$link = new THyperLink();
		$link->setNavigateUrl('/a.csv');
		$link->setDownload(true);
		$this->assertSame('', $link->getDownload());
		$this->assertStringContainsString('download=""', $this->render($link));

		$link->setDownload('false');
		$this->assertNull($link->getDownload());
		$this->assertStringNotContainsString('download', $this->render($link));

		$link->setDownload('');
		$this->assertSame('', $link->getDownload());

		$link->setDownload(null);
		$this->assertNull($link->getDownload());
	}

	public function testLinkAttributesNeedHref()
	{
		$link = new THyperLink();
		$link->setText('Report');
		$link->setDownload(true);
		$link->setRel('nofollow');
		$this->assertSame('<a>Report</a>', $this->render($link));

		$link->setNavigateUrl('/report');
		$link->setEnabled(false);
		$output = $this->render($link);
		$this->assertStringNotContainsString('download', $output);
		$this->assertStringNotContainsString('rel=', $output);
	}

	public function testReferrerPolicyInvalid()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new THyperLink())->setReferrerPolicy('nobody');
	}
}
