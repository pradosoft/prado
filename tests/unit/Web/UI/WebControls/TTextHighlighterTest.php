<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TTextHighlighter;
use Prado\Web\UI\WebControls\TTextProcessor;

class TTextHighlighterTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TOutputBufferRestorationTrait;
	use TWebControlRenderTrait;

	private TTestRecordingClientScript $cs;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
	}

	protected function tearDown(): void
	{
		$this->restoreAssetManager();
		$this->restoreOutputBufferLevel();
	}

	/**
	 * Creates a highlighter with an ID on a page with a recording client script manager.
	 * @return TTextHighlighter the highlighter
	 */
	private function onPage(): TTextHighlighter
	{
		$page = new TPage();
		$this->cs = TTestRecordingClientScript::attachTo($page);
		$highlighter = new TTextHighlighter();
		$highlighter->setID('code');
		$page->getControls()->add($highlighter);
		return $highlighter;
	}

	public function testIsATextProcessor()
	{
		$this->assertInstanceOf(TTextProcessor::class, new TTextHighlighter());
	}

	public function testDefaults()
	{
		$highlighter = new TTextHighlighter();
		$this->assertSame('php', $highlighter->getLanguage());
		$this->assertFalse($highlighter->getShowLineNumbers());
		$this->assertFalse($highlighter->getEnableCopyCode());
		$this->assertSame('default', $highlighter->getSyntaxStyle());
		$this->assertSame(4, $highlighter->getTabSize());
		$this->assertTrue($highlighter->getEncodeHtml());
		$this->assertSame('', $highlighter->getText());
	}

	public function testSetters()
	{
		$highlighter = new TTextHighlighter();
		$highlighter->setLanguage('JavaScript');
		$highlighter->setShowLineNumbers('true');
		$highlighter->setEnableCopyCode(true);
		$highlighter->setSyntaxStyle('github');
		$highlighter->setTabSize('2');
		$highlighter->setEncodeHtml('false');
		$highlighter->setText('x');
		$this->assertSame('javascript', $highlighter->getLanguage());
		$this->assertTrue($highlighter->getShowLineNumbers());
		$this->assertTrue($highlighter->getEnableCopyCode());
		$this->assertSame('github', $highlighter->getSyntaxStyle());
		$this->assertSame(2, $highlighter->getTabSize());
		$this->assertFalse($highlighter->getEncodeHtml());
		$this->assertSame('x', $highlighter->getText());
	}

	public function testProcessTextEncodesAndTrimsTheStart()
	{
		$highlighter = new TTextHighlighter();
		$this->assertSame('&lt;?php echo &quot;a&quot;; ?&gt;  ', $highlighter->processText("\n\t<?php echo \"a\"; ?>  "));
		$highlighter->setEncodeHtml(false);
		$this->assertSame("\n\t<b>", $highlighter->processText("\n\t<b>"));
	}

	public function testRendersPreAndCodeAndRegistersTheScript()
	{
		$highlighter = $this->onPage();
		$highlighter->setText('$a = 1;');
		$this->assertSame('<pre id="code"><code id="code_code" class="php">$a = 1;</code></pre>', $this->render($highlighter));
		$this->assertSame(['texthighlight'], $this->cs->pradoScripts);
		$this->assertSame(
			['prado:code' => 'new Prado.WebUI.TTextHighlighter({\'ID\':"code",\'tabsize\':"    ",\'copycode\':false,\'linenum\':false});'],
			$this->cs->endScripts
		);
	}

	public function testScriptOptionsFollowTheProperties()
	{
		$highlighter = $this->onPage();
		$highlighter->setLanguage('js');
		$highlighter->setTabSize(2);
		$highlighter->setEnableCopyCode(true);
		$highlighter->setShowLineNumbers(true);
		$this->assertSame('<pre id="code"><code id="code_code" class="js"></code></pre>', $this->render($highlighter));
		$this->assertSame(
			'new Prado.WebUI.TTextHighlighter({\'ID\':"code",\'tabsize\':"  ",\'copycode\':true,\'linenum\':true});',
			$this->cs->endScripts['prado:code']
		);
	}

	public function testChildControlsAreTheTextWhenTextIsEmpty()
	{
		$highlighter = $this->onPage();
		$highlighter->addParsedObject('if (a &lt; b) {}');
		$literal = new TLiteral();
		$literal->setText(' // done');
		$highlighter->getControls()->add($literal);
		$this->assertSame('if (a &lt; b) {} // done', $this->renderContents($highlighter));

		$highlighter->setText('x');
		$this->assertSame('x', $this->renderContents($highlighter));
	}

	public function testEmptyRendersNoContents()
	{
		$this->assertSame('', $this->renderContents($this->onPage()));
	}

	public function testPreRenderRegistersTheStyleSheetOnce()
	{
		$this->mockAssetManager();
		$highlighter = $this->onPage();
		$highlighter->setSyntaxStyle('github');
		$highlighter->onPreRender(null);
		$highlighter->onPreRender(null);
		$tw = new \Prado\IO\TTextWriter();
		$this->cs->renderStyleSheetFiles(new \Prado\Web\UI\THtmlWriter($tw));
		$this->assertSame(1, substr_count($tw->flush(), '/assets/github.css'));
	}

	public function testAddParsedObjectKeepsAControl()
	{
		$highlighter = $this->onPage();
		$literal = new TLiteral();
		$literal->setText('x');
		$highlighter->addParsedObject($literal);
		$this->assertSame($literal, $highlighter->getControls()->itemAt(0));
	}

	public function testChildrenThatRenderNothingRenderNoContents()
	{
		$highlighter = $this->onPage();
		$highlighter->getControls()->add(new TLiteral());
		$this->assertSame('', $this->renderContents($highlighter));
	}
}
