<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveClientScript;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\THtmlWriter;

/**
 * TActiveClientScript renders its script file and inline script into the page, and in
 * a callback registers the file and sends the inline script in the response.
 */
class TActiveClientScriptTest extends TestCase
{
	private array $snapshot = [];

	protected function setUp(): void
	{
		$this->snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_response', '_errorHandler']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	/**
	 * @return array{TTestCallbackPage, TActiveClientScript} a client script with the ID `Script`
	 *   and the script `go();`, in a page that is a callback or not
	 */
	private function createScript(bool $callback, string $url = '/s.js'): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		if ($callback) {
			Prado::getApplication()->setResponse(new TTestRecordingResponse());
			$page->setAdapter(new TActivePageAdapter($page));
		}
		$script = new TActiveClientScript();
		$script->setID('Script');
		$script->setFlushScriptFiles(false);
		$script->setScriptUrl($url);
		$script->getControls()->add('go();');
		$page->getControls()->add($script);
		return [$page, $script];
	}

	private function render(TActiveClientScript $script): string
	{
		$writer = new THtmlWriter(new TTextWriter());
		$script->render($writer);
		return $writer->flush();
	}

	public function testOutsideACallbackTheScriptsAreRendered()
	{
		[, $script] = $this->createScript(false);
		$output = $this->render($script);
		$this->assertStringContainsString('src="/s.js"', $output);
		$this->assertStringContainsString('go();', $output);
	}

	public function testWithoutAUrlOrContentNothingIsRendered()
	{
		[, $script] = $this->createScript(false, '');
		$script->getControls()->clear();
		$this->assertSame('', $this->render($script));
	}

	public function testInACallbackTheFileIsRegisteredOnceAndTheScriptSent()
	{
		[$page, $script] = $this->createScript(true);
		$this->assertSame('', $this->render($script));
		$this->render($script);
		$cs = $page->getClientScript();
		$this->assertTrue($cs->isScriptFileRegistered('Script_custom'));
		$this->assertSame(['/s.js'], array_values($cs->getScriptUrls()));
		$functions = $page->getClientFunctions();
		$this->assertCount(2, $functions);
		$boundary = $functions[0]['Prado.Element.appendScriptBlock'][0];
		$writers = PradoUnit::getProp(Prado::getApplication()->getResponse()->getAdapter(), '_writers');
		$sent = null;
		foreach ($writers as $writer) {
			if ($writer->getBoundary() === $boundary) {
				$sent = $writer->flushWithoutBoundary();
			}
		}
		$this->assertSame("/*<![CDATA[*/\ngo();\n/*]]>*/", $sent);
	}
}
