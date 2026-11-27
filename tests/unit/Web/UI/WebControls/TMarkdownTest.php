<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TMarkdown;
use Prado\Web\UI\WebControls\TTextHighlighter;

class TMarkdownTest extends TestCase
{
	use TOutputBufferRestorationTrait;
	use TWebControlRenderTrait;

	private TTestRecordingClientScript $cs;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
	}

	protected function tearDown(): void
	{
		$this->restoreOutputBufferLevel();
	}

	/**
	 * Creates a TMarkdown with an ID on a page with a recording client script manager.
	 * @return TMarkdown the control
	 */
	private function onPage(): TMarkdown
	{
		$page = new TPage();
		$this->cs = TTestRecordingClientScript::attachTo($page);
		$markdown = new TMarkdown();
		$markdown->setID('doc');
		$page->getControls()->add($markdown);
		return $markdown;
	}

	public function testIsATextHighlighter()
	{
		$this->assertInstanceOf(TTextHighlighter::class, new TMarkdown());
	}

	public function testRendersTheHtmlInADiv()
	{
		$markdown = $this->onPage();
		$markdown->setText("# Title\n\nSome *text*.");
		$this->assertSame('<div id="doc"><h1>Title</h1>' . "\n" . '<p>Some <em>text</em>.</p></div>', $this->render($markdown));
	}

	public function testFencedCodeBlocksKeepTheirLanguage()
	{
		$markdown = $this->onPage();
		$markdown->setText("```js\nvar a = 1 < 2;\n```\n\n```\nplain\n```");
		$html = $this->render($markdown);
		$this->assertStringContainsString('<pre><code class="language-js">var a = 1 &lt; 2;</code></pre>', $html);
		$this->assertStringContainsString('<pre><code>plain</code></pre>', $html);
		$this->assertStringNotContainsString('<pre><code><pre>', $html);
	}

	public function testRenderingLeavesTheLanguageUnchanged()
	{
		$markdown = $this->onPage();
		$markdown->setText("```js\nx;\n```");
		$this->render($markdown);
		$this->assertSame('php', $markdown->getLanguage());
		$this->assertNull($markdown->getViewState('Language'));
	}

	public function testRegistersTheMarkdownClientScript()
	{
		$markdown = $this->onPage();
		$markdown->setShowLineNumbers(true);
		$markdown->setText('text');
		$this->render($markdown);
		$this->assertSame(['texthighlight'], $this->cs->pradoScripts);
		$this->assertSame(
			'new Prado.WebUI.TMarkdown({\'ID\':"doc",\'tabsize\':"    ",\'copycode\':false,\'linenum\':true});',
			$this->cs->endScripts['prado:doc']
		);
	}

	public function testBodyIsTheMarkdownWhenTextIsEmpty()
	{
		$markdown = $this->onPage();
		$markdown->addParsedObject("Use &lt;b&gt; for **bold**.");
		$this->assertSame('<p>Use <b> for <strong>bold</strong>.</p>', $this->renderContents($markdown));
	}

	public function testRawHtmlPassesThrough()
	{
		$markdown = $this->onPage();
		$markdown->setText('<span class="note">as written</span>');
		$this->assertStringContainsString('<span class="note">as written</span>', $this->renderContents($markdown));
	}

	public function testDeprecatedHighlightCodeEncodesTheBlock()
	{
		$markdown = new TMarkdown();
		$this->assertSame('a &lt; b', PradoUnit::invoke($markdown, 'highlightCode', ['', 'js', 'a &lt; b']));
	}
}
