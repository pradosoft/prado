<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TFlushOutput;

/**
 * Callback page that counts how often it flushes its writer.
 */
class TFlushOutputTestPage extends TTestCallbackPage
{
	public int $flushed = 0;

	public function flushWriter()
	{
		$this->flushed++;
	}
}

class TFlushOutputTest extends TestCase
{
	/**
	 * Renders a TFlushOutput on a page.
	 * @param bool $callback whether the page serves a callback
	 * @return TFlushOutputTestPage the page
	 */
	private function renderOnPage(bool $callback): TFlushOutputTestPage
	{
		$page = new TFlushOutputTestPage();
		$page->callback = $callback;
		$control = new TFlushOutput();
		$page->getControls()->add($control);
		$tw = new TTextWriter();
		$control->render(new THtmlWriter($tw));
		$this->assertSame('', $tw->flush());
		return $page;
	}

	public function testViewStateIsDisabled()
	{
		$this->assertFalse((new TFlushOutput())->getEnableViewState());
	}

	public function testContinueBuffering()
	{
		$control = new TFlushOutput();
		$this->assertTrue($control->getContinueBuffering());
		$control->setContinueBuffering('false');
		$this->assertFalse($control->getContinueBuffering());
	}

	public function testRenderFlushesThePageWriter()
	{
		$this->assertSame(1, $this->renderOnPage(false)->flushed);
	}

	public function testRenderInACallbackDoesNotFlush()
	{
		$this->assertSame(0, $this->renderOnPage(true)->flushed);
	}
}
