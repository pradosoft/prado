<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TJavascriptLogger;

class TJavascriptLoggerTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testToggleKeyDefaultsToJ()
	{
		$logger = new TJavascriptLogger();
		$this->assertSame('j', $logger->getToggleKey());
		$logger->setToggleKey('k');
		$this->assertSame('k', $logger->getToggleKey());
	}

	public function testRendersUsageInADiv()
	{
		$logger = new TJavascriptLogger();
		$logger->setToggleKey('k');
		$html = $this->render($logger);
		$this->assertStringStartsWith('<div>Press ALT-K (Or CTRL-K on OS X) to <a href="javascript:if(logConsole)logConsole.toggle()">', $html);
		$this->assertStringEndsWith('</div>', $html);
	}

	public static function keyCodeProvider(): array
	{
		return [
			'default j' => [null, 74],
			'letter' => ['a', 65],
			'uppercase letter' => ['Z', 90],
			'digit' => ['0', 48],
			'unknown key falls back to j' => ['?', 74],
		];
	}

	/**
	 * @dataProvider keyCodeProvider
	 */
	public function testPreRenderRegistersTheConsoleWithTheKeyCode(?string $key, int $code)
	{
		$page = new TPage();
		$cs = TTestRecordingClientScript::attachTo($page);
		$logger = new TJavascriptLogger();
		$logger->setID('log');
		$page->getControls()->add($logger);
		if ($key !== null) {
			$logger->setToggleKey($key);
		}
		$logger->onPreRender(null);
		$this->assertSame(['logger'], $cs->pradoScripts);
		$this->assertSame(
			['log' => "var logConsole; jQuery(function() { logConsole = new LogConsole($code)}); "],
			$cs->beginScripts
		);
	}

	public function testPreRenderRaisesOnPreRender()
	{
		$page = new TPage();
		TTestRecordingClientScript::attachTo($page);
		$logger = new TJavascriptLogger();
		$page->getControls()->add($logger);
		$raised = 0;
		$logger->attachEventHandler('OnPreRender', function () use (&$raised) {
			$raised++;
		});
		$logger->onPreRender(null);
		$this->assertSame(1, $raised);
	}
}
