<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TLinkTag;
use Prado\Web\UI\WebControls\TLinkTagCollection;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TMetaTag;
use Prado\Web\UI\WebControls\TMetaTagCollection;

class THeadTest extends TestCase
{
	use TWebControlRenderTrait;

	private TTestRecordingClientScript $cs;

	/**
	 * Creates a head on a page with a recording client script manager.
	 * @return THead the head
	 */
	private function onPage(): THead
	{
		$page = new TPage();
		$this->cs = TTestRecordingClientScript::attachTo($page);
		$head = new THead();
		$page->getControls()->add($head);
		return $head;
	}

	public function testDefaults()
	{
		$head = new THead();
		$this->assertSame('', $head->getTitle());
		$this->assertSame('', $head->getBaseUrl());
		$this->assertSame('', $head->getShortcutIcon());
		$this->assertInstanceOf(TMetaTagCollection::class, $head->getMetaTags());
		$this->assertInstanceOf(TLinkTagCollection::class, $head->getLinkTags());
		$this->assertSame($head->getMetaTags(), $head->getMetaTags());
		$this->assertSame($head->getLinkTags(), $head->getLinkTags());
	}

	public function testSetters()
	{
		$head = new THead();
		$head->setTitle('Home');
		$head->setBaseUrl('https://example.com/');
		$head->setShortcutIcon('/favicon.ico');
		$this->assertSame('Home', $head->getTitle());
		$this->assertSame('https://example.com/', $head->getBaseUrl());
		$this->assertSame('/favicon.ico', $head->getShortcutIcon());
	}

	public function testInitRegistersTheHeadWithThePage()
	{
		$head = $this->onPage();
		$head->onInit(null);
		$this->assertSame($head, $head->getPage()->getHead());
	}

	public function testAddParsedObjectRoutesTags()
	{
		$head = new THead();
		$meta = new TMetaTag();
		$link = new TLinkTag();
		$literal = new TLiteral();
		$head->addParsedObject($meta);
		$head->addParsedObject($link);
		$head->addParsedObject($literal);
		$this->assertSame([$meta], $head->getMetaTags()->toArray());
		$this->assertSame([$link], $head->getLinkTags()->toArray());
		$this->assertSame($literal, $head->getControls()->itemAt(0));
	}

	public function testRenderEmptyHead()
	{
		$this->assertSame("<head>\n<title></title>\n</head>\n", $this->render($this->onPage()));
	}

	public function testRenderEncodesTheTitle()
	{
		$head = $this->onPage();
		$head->setTitle('A <b> "T"');
		$this->assertSame("<head>\n<title>A &lt;b&gt; &quot;T&quot;</title>\n</head>\n", $this->render($head));
	}

	public function testRenderBaseUrlAndShortcutIcon()
	{
		$head = $this->onPage();
		$head->setBaseUrl('https://example.com/');
		$head->setShortcutIcon('/favicon.ico');
		$this->assertSame(
			"<head>\n<title></title>\n<base href=\"https://example.com/\" />\n<link rel=\"shortcut icon\" href=\"/favicon.ico\" />\n</head>\n",
			$this->render($head)
		);
	}

	public function testRenderEncodesBaseUrlAndShortcutIcon()
	{
		$head = $this->onPage();
		$head->setBaseUrl('/a?x=1&y="2"');
		$head->setShortcutIcon('/i.ico"><script>');
		$html = $this->render($head);
		$this->assertStringContainsString('<base href="/a?x=1&y=&quot;2&quot;" />', $html);
		$this->assertStringContainsString('<link rel="shortcut icon" href="/i.ico&quot;&gt;&lt;script&gt;" />', $html);
	}

	public function testRenderMetaAndLinkTagsThenChildren()
	{
		$head = $this->onPage();
		$meta = new TMetaTag();
		$meta->setName('description');
		$meta->setContent('About');
		$head->getMetaTags()->add($meta);
		$link = new TLinkTag();
		$link->setRel('stylesheet');
		$link->setHref('/site.css');
		$head->getLinkTags()->add($link);
		$head->getControls()->add('<!--child-->');
		$html = $this->render($head);
		$metaAt = strpos($html, '<meta ');
		$linkAt = strpos($html, '<link ');
		$childAt = strpos($html, '<!--child-->');
		$this->assertNotFalse($metaAt);
		$this->assertGreaterThan($metaAt, $linkAt);
		$this->assertGreaterThan($linkAt, $childAt);
		$this->assertStringEndsWith("<!--child--></head>\n", $html);
	}

	public function testRenderHeadScriptsOnlyWhenTheClientSupportsJavaScript()
	{
		$head = $this->onPage();
		$this->cs->registerStyleSheet('site', '.a{}');
		$this->cs->registerHeadScript('boot', 'boot();');
		$html = $this->render($head);
		$this->assertStringContainsString('.a{}', $html);
		$this->assertStringContainsString('boot();', $html);

		$head->getPage()->setClientSupportsJavaScript(false);
		$html = $this->render($head);
		$this->assertStringContainsString('.a{}', $html);
		$this->assertStringNotContainsString('boot();', $html);
	}
}
