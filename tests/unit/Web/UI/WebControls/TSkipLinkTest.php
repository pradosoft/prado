<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\TAssetManager;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THyperLink;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TMain;
use Prado\Web\UI\WebControls\TSkipLink;

/**
 * Records published paths instead of copying files.
 */
class TSkipLinkTestAssetManager extends TAssetManager
{
	public array $published = [];

	public function publishFilePath($path, $checkTimestamp = false)
	{
		$this->published[] = $path;
		return '/assets/skiplink.css';
	}
}

/**
 * A naming container that prefixes the client IDs of its children.
 */
class TSkipLinkTestContainer extends TControl implements INamingContainer
{
}

class TSkipLinkTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	private function newPageLink(string $targetID): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$link = new TSkipLink();
		$link->setTargetID($targetID);
		$page->getControls()->add($link);
		$main = new TMain();
		$main->setID('Content');
		$page->getControls()->add($main);
		return [$page, $link];
	}

	public function testExtendsHyperLink()
	{
		$this->assertInstanceOf(THyperLink::class, new TSkipLink());
	}

	public function testTargetIDDefaultAndTrim()
	{
		$link = new TSkipLink();
		$this->assertSame('', $link->getTargetID());
		$link->setTargetID(' Content ');
		$this->assertSame('Content', $link->getTargetID());
	}

	public function testDefaultRendersClassAndText()
	{
		$link = new TSkipLink();
		$this->assertSame('', $link->getTargetClientID());
		$this->assertSame('', $link->getNavigateUrl());
		$this->assertSame('<a class="skip-link">Skip to main content</a>', $this->render($link));
	}

	public function testTargetControlLinksToClientID()
	{
		[, $link] = $this->newPageLink('Content');
		$this->assertSame('Content', $link->getTargetClientID());
		$this->assertSame('<a href="#Content" class="skip-link">Skip to main content</a>', $this->render($link));
	}

	public function testTargetInNamingContainerUsesClientID()
	{
		$page = new TPage();
		$box = new TSkipLinkTestContainer();
		$box->setID('Box');
		$page->getControls()->add($box);
		$link = new TSkipLink();
		$link->setTargetID('Content');
		$box->getControls()->add($link);
		$main = new TMain();
		$main->setID('Content');
		$box->getControls()->add($main);
		$this->assertSame('#Box_Content', $link->getNavigateUrl());
	}

	public function testTargetHtmlElementID()
	{
		[, $link] = $this->newPageLink('page-body');
		$this->assertSame('page-body', $link->getTargetClientID());
		$this->assertSame('#page-body', $link->getNavigateUrl());

		$orphan = new TSkipLink();
		$orphan->setTargetID('main');
		$this->assertSame('#main', $orphan->getNavigateUrl());
	}

	public function testNavigateUrlOverridesTarget()
	{
		[, $link] = $this->newPageLink('Content');
		$link->setNavigateUrl('#search');
		$this->assertSame('#search', $link->getNavigateUrl());
	}

	public function testTextImageAndBodyReplaceDefaultText()
	{
		$link = new TSkipLink();
		$link->setText('Skip <navigation>');
		$this->assertSame('<a class="skip-link">Skip &lt;navigation&gt;</a>', $this->render($link));

		$link = new TSkipLink();
		$link->setImageUrl('/skip.png');
		$this->assertStringContainsString('<img src="/skip.png"', $this->render($link));

		$link = new TSkipLink();
		$body = new TLiteral();
		$body->setText('Jump');
		$link->getControls()->add($body);
		$this->assertSame('<a class="skip-link">Jump</a>', $this->render($link));
	}

	public function testDefaultText()
	{
		$this->assertSame('Skip to main content', (new TSkipLink())->getDefaultText());
	}

	public function testCssClassFollowsSkipLinkClass()
	{
		$link = new TSkipLink();
		$link->setCssClass('skip-link brand  skip-link');
		$this->assertSame('<a class="skip-link brand">Skip to main content</a>', $this->render($link));
		$this->assertSame('skip-link brand  skip-link', $link->getCssClass());
	}

	public function testDisabledRendersNoHref()
	{
		[, $link] = $this->newPageLink('Content');
		$link->setEnabled(false);
		$this->assertSame('<a disabled="disabled" class="skip-link">Skip to main content</a>', $this->render($link));
	}

	public function testCssUrlDefaultAndSet()
	{
		$link = new TSkipLink();
		$this->assertSame('default', $link->getCssUrl());
		$link->setCssUrl('/theme/skip.css');
		$this->assertSame('/theme/skip.css', $link->getCssUrl());
		$link->setCssUrl('');
		$this->assertSame('', $link->getCssUrl());
	}

	public function testPreRenderPublishesBundledStyleSheet()
	{
		Prado::getApplication()->setAssetManager($manager = new TSkipLinkTestAssetManager());
		[$page, $link] = $this->newPageLink('Content');
		$link->onPreRender(null);
		$this->assertSame(
			[dirname((new \ReflectionClass(TSkipLink::class))->getFileName()) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'skiplink.css'],
			$manager->published
		);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/assets/skiplink.css'));
	}

	public function testPreRenderRegistersCustomStyleSheet()
	{
		Prado::getApplication()->setAssetManager($manager = new TSkipLinkTestAssetManager());
		[$page, $link] = $this->newPageLink('Content');
		$link->setCssUrl('/theme/skip.css');
		$link->onPreRender(null);
		$this->assertSame([], $manager->published);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/theme/skip.css'));
	}

	public function testPreRenderWithoutStyleSheet()
	{
		Prado::getApplication()->setAssetManager($manager = new TSkipLinkTestAssetManager());
		[$page, $link] = $this->newPageLink('Content');
		$link->setCssUrl('');
		$link->onPreRender(null);
		$this->assertSame([], $manager->published);
		$this->assertFalse($page->getClientScript()->isStyleSheetFileRegistered('/assets/skiplink.css'));
	}
}
