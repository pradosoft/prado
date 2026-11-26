<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveHyperLink;
use Prado\Web\UI\TControl;

/**
 * The callback update of Text is encoded, as the THyperLink render is.
 */
class TActiveHyperLinkTest extends TestCase
{
	/**
	 * Initializes the application response, whose output buffer starts with it, outside a test.
	 */
	public static function setUpBeforeClass(): void
	{
		Prado::getApplication()->getResponse();
	}

	public function testSetTextUpdatesTheClientEncoded()
	{
		$page = new TTestCallbackPage();
		$link = new TActiveHyperLink();
		$link->setID('Target');
		$page->getControls()->add($link);
		PradoUnit::invoke($link, 'setControlStage', TControl::CS_LOADED);

		$link->setText('<b>a & b</b>');

		$functions = json_encode($page->getClientFunctions());
		$this->assertStringContainsString(json_encode('&lt;b&gt;a & b&lt;/b&gt;'), $functions);
		$this->assertStringNotContainsString(json_encode('<b>a & b</b>'), $functions);
		$this->assertSame('<b>a & b</b>', $link->getText());
	}

	private function createLink(bool $callback = true): array
	{
		$page = new TActiveTestPage($callback);
		$link = $page->addControl(new TActiveHyperLink(), 'Link');
		return [$page, $link];
	}

	public function testSetTextUnchangedOrOutsideACallbackUpdatesNothing()
	{
		[$page, $link] = $this->createLink();
		$link->setText('a');
		$link->setText('a');
		$this->assertCount(1, $page->getClientFunctions());

		[$page, $link] = $this->createLink(false);
		$link->setText('a');
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetImageUrlUpdatesTheContentWithTheImage()
	{
		[$page, $link] = $this->createLink();
		$link->setText('Home');
		$link->setImageUrl('/home.png');
		$link->setImageUrl('/home.png');
		$functions = $page->getClientFunctions();
		$this->assertCount(2, $functions);
		[$id, $method, [$html]] = $functions[1]['Prado.Element.j'];
		$this->assertSame(['Link', 'html'], [$id, $method]);
		$this->assertStringStartsWith('<img', $html);
		$this->assertStringContainsString('src="/home.png"', $html);
		$this->assertStringContainsString('alt="Home"', $html);
	}

	public function testSetImageUrlToEmptyOrOutsideACallbackUpdatesNothing()
	{
		[$page, $link] = $this->createLink();
		$link->setImageUrl('/home.png');
		$link->setImageUrl('');
		$this->assertCount(1, $page->getClientFunctions());
		$this->assertSame('', $link->getImageUrl());

		[$page, $link] = $this->createLink(false);
		$link->setImageUrl('/home.png');
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetNavigateUrlSetsTheDecodedHref()
	{
		[$page, $link] = $this->createLink();
		$link->setNavigateUrl('/a?x=1&amp;y=%41');
		$link->setNavigateUrl('/a?x=1&amp;y=%41');
		$this->assertSame([['Prado.Element.setAttribute' => ['Link', 'href', '/a?x=1&y=A']]], $page->getClientFunctions());
	}

	public function testSetNavigateUrlOutsideACallbackUpdatesNothing()
	{
		[$page, $link] = $this->createLink(false);
		$link->setNavigateUrl('/a');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('/a', $link->getNavigateUrl());
	}

	public function testSetNavigateUrlToEmptyRemovesTheHref()
	{
		[$page, $link] = $this->createLink();
		$link->setNavigateUrl('/a');
		$link->setNavigateUrl('');
		$this->assertSame(['Prado.Element.removeAttribute' => ['Link', 'href']], $page->getClientFunctions()[1]);
	}

	public function testSetNavigateUrlOfADisabledLinkRemovesTheHref()
	{
		[$page, $link] = $this->createLink();
		$link->setEnabled(false);
		$link->setNavigateUrl('/a');
		$this->assertSame(['Prado.Element.removeAttribute' => ['Link', 'href']], $page->getClientFunctions()[1]);
		$this->assertStringNotContainsString('href', $this->renderLink($link));
	}

	public function testDisableRemovesTheHrefAndEnableRestoresIt()
	{
		[$page, $link] = $this->createLink();
		$link->setNavigateUrl('/a');
		$link->setEnabled('false');
		$link->setEnabled(false);
		$link->setEnabled(true);
		$this->assertSame([
			['Prado.Element.setAttribute' => ['Link', 'href', '/a']],
			['Prado.Element.removeAttribute' => ['Link', 'href']],
			['Prado.Element.setAttribute' => ['Link', 'href', '/a']],
		], $page->getClientFunctions());
		$this->assertStringContainsString('href="/a"', $this->renderLink($link));
	}

	public function testEnableInADisabledParentKeepsTheHrefRemoved()
	{
		[$page, $link] = $this->createLink();
		$link->setNavigateUrl('/a');
		$link->setEnabled(false);
		$page->form->setEnabled(false);
		$link->setEnabled(true);
		$this->assertSame(['Prado.Element.removeAttribute' => ['Link', 'href']], $page->getClientFunctions()[2]);
	}

	public function testSetEnabledOutsideACallbackUpdatesNothing()
	{
		[$page, $link] = $this->createLink(false);
		$link->setEnabled(false);
		$this->assertSame([], $page->getClientFunctions());
		$this->assertFalse($link->getEnabled());
	}

	public function testSetTargetUpdatesTheTargetOnce()
	{
		[$page, $link] = $this->createLink();
		$link->setTarget('_blank');
		$link->setTarget('_blank');
		$this->assertSame([['Prado.Element.setAttribute' => ['Link', 'target', '_blank']]], $page->getClientFunctions());

		[$page, $link] = $this->createLink(false);
		$link->setTarget('_blank');
		$this->assertSame([], $page->getClientFunctions());
	}

	private function renderLink(TActiveHyperLink $link): string
	{
		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$link->renderControl($writer);
		return $writer->flush();
	}
}
