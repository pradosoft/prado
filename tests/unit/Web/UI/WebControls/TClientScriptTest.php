<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TClientScript;

class TClientScriptTest extends TestCase
{
	use TWebControlRenderTrait;

	private TTestRecordingClientScript $cs;

	/**
	 * Creates a TClientScript on a page with a recording client script manager.
	 * @return TClientScript the control
	 */
	private function onPage(): TClientScript
	{
		$page = new TPage();
		$this->cs = TTestRecordingClientScript::attachTo($page);
		$control = new TClientScript();
		$page->getControls()->add($control);
		return $control;
	}

	public function testDefaults()
	{
		$control = new TClientScript();
		$this->assertSame('', $control->getPradoScripts());
		$this->assertSame('', $control->getScriptUrl());
		$this->assertTrue($control->getFlushScriptFiles());
	}

	public function testSetters()
	{
		$control = new TClientScript();
		$control->setPradoScripts('ajax, effects');
		$control->setScriptUrl('/js/app.js');
		$control->setFlushScriptFiles('false');
		$this->assertSame('ajax, effects', $control->getPradoScripts());
		$this->assertSame('/js/app.js', $control->getScriptUrl());
		$this->assertFalse($control->getFlushScriptFiles());
	}

	public function testPreRenderRegistersEachPradoScript()
	{
		$control = $this->onPage();
		$control->setPradoScripts("ajax,effects  validator\n,");
		$control->onPreRender(null);
		$this->assertSame(['ajax', 'effects', 'validator'], $this->cs->pradoScripts);
	}

	public function testPreRenderWithoutScriptsRegistersNone()
	{
		$control = $this->onPage();
		$control->onPreRender(null);
		$this->assertSame([], $this->cs->pradoScripts);
	}

	public function testRenderFlushesTheScriptFiles()
	{
		$control = $this->onPage();
		$this->assertSame('<!--script files-->', $this->render($control));
		$this->assertSame([$control], $this->cs->flushedControls);
	}

	public function testRenderWithoutFlushWritesNothing()
	{
		$control = $this->onPage();
		$control->setFlushScriptFiles(false);
		$this->assertSame('', $this->render($control));
		$this->assertSame([], $this->cs->flushedControls);
	}

	public function testRenderScriptFileAndCustomScript()
	{
		$control = $this->onPage();
		$control->setFlushScriptFiles(false);
		$control->setScriptUrl('/js/app.js');
		$control->getControls()->add('alert(1);');
		$this->assertSame(
			TJavaScript::renderScriptFile('/js/app.js') . TJavaScript::renderScriptHeader() . 'alert(1);' . TJavaScript::renderScriptFooter(),
			$this->render($control)
		);
	}
}
