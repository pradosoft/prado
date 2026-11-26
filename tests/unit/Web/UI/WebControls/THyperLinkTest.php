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

	public function testDisabledLinkIsAnnouncedDisabled()
	{
		$link = new THyperLink();
		$link->setNavigateUrl('/a');
		$link->setText('A');
		$link->setEnabled(false);
		$this->assertSame('<a role="link" aria-disabled="true">A</a>', $this->render($link));
		$link->setRole('menuitem');
		$this->assertSame('<a role="menuitem" aria-disabled="true">A</a>', $this->render($link));
	}

	public function testLinkInADisabledParentIsAnnouncedDisabled()
	{
		$parent = new \Prado\Web\UI\WebControls\TPanel();
		$link = new THyperLink();
		$link->setNavigateUrl('/a');
		$link->setText('A');
		$parent->getControls()->add($link);
		$parent->setEnabled(false);
		$this->assertSame('<a role="link" aria-disabled="true">A</a>', $this->render($link));
	}

	public function testTargetRendersWithAndWithoutHref()
	{
		$link = new THyperLink();
		$link->setText('A');
		$link->setTarget('_blank');
		$this->assertSame('_blank', $link->getTarget());
		$this->assertSame('<a target="_blank">A</a>', $this->render($link));
		$link->setNavigateUrl('/a');
		$this->assertSame('<a href="/a" target="_blank">A</a>', $this->render($link));
	}

	public function testDataIsTheText()
	{
		$link = new THyperLink();
		$link->setData('Docs');
		$this->assertSame('Docs', $link->getText());
		$this->assertSame('Docs', $link->getData());
	}

	public function testContentsFallBackToChildControlsThenTheUrl()
	{
		$link = new THyperLink();
		$link->setNavigateUrl('/a?b=<c>');
		$this->assertSame('<a href="/a?b=&lt;c&gt;">/a?b=&lt;c&gt;</a>', $this->render($link));
		$link->getControls()->add('<b>child</b>');
		$this->assertSame('<a href="/a?b=&lt;c&gt;"><b>child</b></a>', $this->render($link));
		$link->setText('A & <B>');
		$this->assertSame('<a href="/a?b=&lt;c&gt;">A & &lt;B&gt;</a>', $this->render($link));
	}

	public function testImagePropertiesDefault()
	{
		$link = new THyperLink();
		$this->assertSame('', $link->getImageUrl());
		$this->assertSame('', $link->getImageAlign());
		$this->assertSame('', $link->getImageWidth());
		$this->assertSame('', $link->getImageHeight());
		$this->assertSame($link->getImageStyle(), $link->getImageStyle());
	}

	public function testImageRendersInsteadOfText()
	{
		$link = new THyperLink();
		$link->setNavigateUrl('/home');
		$link->setImageUrl('home.png');
		$this->assertSame('<a href="/home"><img src="home.png" alt="" /></a>', $this->render($link));
	}

	public function testImageTakesTheTextToolTipStyleAndAlign()
	{
		$link = new THyperLink();
		$link->setNavigateUrl('/home');
		$link->setImageUrl('home.png');
		$link->setText('Home');
		$link->setToolTip('Go home');
		$link->setImageAlign('left');
		$link->setImageWidth('16px');
		$link->setImageHeight('12px');
		$link->getImageStyle()->setCssClass('icon');
		$this->assertSame('left', $link->getImageAlign());
		$this->assertSame('16px', $link->getImageWidth());
		$this->assertSame('12px', $link->getImageHeight());
		$this->assertSame(
			'<a title="Go home" href="/home"><img src="home.png" alt="Home" align="left" title="Go home" class="icon" style="width:16px;height:12px;" /></a>',
			$this->render($link)
		);
	}

	public function testImageCombinationsOfToolTipTextStyleAndAlign()
	{
		foreach (['', 'Tip'] as $tip) {
			foreach (['', 'Home'] as $text) {
				foreach ([false, true] as $styled) {
					foreach (['', 'top'] as $align) {
						$link = new THyperLink();
						$link->setImageUrl('i.png');
						$link->setToolTip($tip);
						$link->setText($text);
						if ($styled) {
							$link->setImageWidth('8px');
						}
						$link->setImageAlign($align);
						$expected = '<img src="i.png" alt="' . $text . '"'
							. ($align !== '' ? ' align="top"' : '')
							. ($tip !== '' ? ' title="Tip"' : '')
							. ($styled ? ' style="width:8px;"' : '')
							. ' />';
						$this->assertSame($expected, $this->renderContents($link), "tip '$tip', text '$text', styled " . var_export($styled, true) . ", align '$align'");
					}
				}
			}
		}
	}
}
