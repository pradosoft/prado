<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\WebControls\TStyleSheet;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Web\UI\TPage;

class TStyleSheetTest extends TestCase
{
	use TWebControlRenderTrait;

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
	}

	public function testWithoutChildrenRendersNothing()
	{
		$this->assertSame('', $this->render(new TStyleSheet()));
	}

	public function testRendersChildCssInStyleElement()
	{
		$control = new TStyleSheet();
		$control->getControls()->add('.a { color: red; }');
		$this->assertSame("<style type=\"text/css\">\n/*<![CDATA[*/\n.a { color: red; }\n/*]]>*/\n</style>\n", $this->render($control));
	}

	public function testStyleElementCarriesNonce()
	{
		TJavaScript::setScriptNonce('n0nce');
		$control = new TStyleSheet();
		$control->getControls()->add('.a{}');
		$this->assertStringStartsWith("<style type=\"text/css\" nonce=\"n0nce\">\n/*<![CDATA[*/\n", $this->render($control));
	}

	/**
	 * Creates a style sheet on a page whose client script records Prado styles and style sheet files.
	 * @return array{TStyleSheet, TTestRecordingClientScript} the control and its client script
	 */
	private function onPage(): array
	{
		$page = new TPage();
		$cs = TTestRecordingClientScript::attachTo($page);
		$control = new TStyleSheet();
		$page->getControls()->add($control);
		return [$control, $cs];
	}

	public function testDefaultsAndSetters()
	{
		$control = new TStyleSheet();
		$this->assertSame('', $control->getPradoStyles());
		$this->assertSame('', $control->getStyleSheetUrl());
		$this->assertSame('', $control->getMediaType());
		$control->setPradoStyles('jquery-ui');
		$control->setStyleSheetUrl('/site.css');
		$control->setMediaType('print');
		$this->assertSame('jquery-ui', $control->getPradoStyles());
		$this->assertSame('/site.css', $control->getStyleSheetUrl());
		$this->assertSame('print', $control->getMediaType());
	}

	public function testPreRenderRegistersTheStyleSheetFile()
	{
		[$control, $cs] = $this->onPage();
		$control->setStyleSheetUrl('/print.css');
		$control->setMediaType('print');
		$control->onPreRender(null);
		$this->assertTrue($cs->isStyleSheetFileRegistered('/print.css'));
		$this->assertStringContainsString('media="print" href="/print.css"', $this->renderStyleSheetFiles($cs));
	}

	public function testPreRenderWithoutUrlOrStylesRegistersNothing()
	{
		[$control, $cs] = $this->onPage();
		$control->onPreRender(null);
		$this->assertSame('', $this->renderStyleSheetFiles($cs));
		$this->assertSame([], $cs->pradoStyles);
	}

	public function testPreRenderRegistersEachPradoStyle()
	{
		[$control, $cs] = $this->onPage();
		$control->setPradoStyles("jquery-ui, bootstrap\n ,");
		$control->onPreRender(null);
		$this->assertSame(['jquery-ui', 'bootstrap'], $cs->pradoStyles);
	}

	/**
	 * Renders the registered style sheet files of a client script.
	 * @param TTestRecordingClientScript $cs the client script
	 * @return string the rendered markup
	 */
	private function renderStyleSheetFiles(TTestRecordingClientScript $cs): string
	{
		$tw = new \Prado\IO\TTextWriter();
		$cs->renderStyleSheetFiles(new \Prado\Web\UI\THtmlWriter($tw));
		return $tw->flush();
	}

	public function testPreRenderRegistersStylesAndTheStyleSheetFile()
	{
		[$control, $cs] = $this->onPage();
		$control->setPradoStyles('jquery-ui');
		$control->setStyleSheetUrl('/site.css');
		$control->onPreRender(null);
		$this->assertSame(['jquery-ui'], $cs->pradoStyles);
		$this->assertTrue($cs->isStyleSheetFileRegistered('/site.css'));
	}

	public function testPreRenderRaisesOnPreRender()
	{
		[$control] = $this->onPage();
		$raised = 0;
		$control->attachEventHandler('OnPreRender', function () use (&$raised) {
			$raised++;
		});
		$control->onPreRender(null);
		$this->assertSame(1, $raised);
	}
}
