<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Shell\Actions\TDbParameterAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Util\TDbParameterModule;

/**
 * Records {@see TDbParameterModule::set()} calls, and reports the keys in `$stored`
 * as stored, or every key when `$stored` is null, without a database.
 */
class TDbParameterActionTestModule extends TDbParameterModule
{
	public array $sets = [];
	public ?array $stored = null;

	public function set($key, $value, $autoLoad = true, $setParameter = true)
	{
		$this->sets[] = [$key, $value, $autoLoad, $setParameter];
	}

	public function exists($key)
	{
		return $this->stored === null || in_array($key, $this->stored, true);
	}
}

/**
 * Tests for {@see TDbParameterAction}.
 */
class TDbParameterActionTest extends TestCase
{
	private TTestApplication $_app;
	private TTestCapturingTextWriter $_text;
	private TDbParameterActionTestModule $_module;
	private TDbParameterAction $_action;

	protected function setUp(): void
	{
		$this->_app = new TTestApplication();
		$this->_text = new TTestCapturingTextWriter();
		$this->_module = new TDbParameterActionTestModule();
		$this->_action = new TDbParameterAction();
		$writer = new TShellWriter($this->_text);
		$writer->setColorSupported(false);
		$writer->setWidth(200);
		$this->_action->setWriter($writer);
		$this->_action->setDbParameterModule($this->_module);
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
	}

	public function testActionIndexHeadsTheKeyAndValueColumns(): void
	{
		$this->_app->getParameters()['shellTestKey'] = 'shellTestValue';

		$this->assertTrue($this->_action->actionIndex(['param/index']));

		$lines = explode("\n", $this->_text->getCaptured());
		$this->assertMatchesRegularExpression('/^Parameter Key +Value$/', $lines[1]);
		$this->assertMatchesRegularExpression('/^shellTestKey +shellTestValue$/m', $this->_text->getCaptured());
	}

	public function testActionSetStoresZero(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set', 'shellTestKey', '0']));

		$this->assertSame([['shellTestKey', '0', true, false]], $this->_module->sets);
	}

	public function testActionSetStoresTheEmptyString(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set', 'shellTestKey', '']));

		$this->assertSame([['shellTestKey', '', true, false]], $this->_module->sets);
	}

	public function testActionSetWithoutAKeyNamesSet(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set']));

		$this->assertSame([], $this->_module->sets);
		$this->assertStringContainsString('Set Parameter needs a key', $this->_text->getCaptured());
		$this->assertStringNotContainsString('Get Parameter', $this->_text->getCaptured());
	}

	public function testActionSetWithoutAValueReportsIt(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set', 'shellTestKey']));

		$this->assertSame([], $this->_module->sets);
		$this->assertStringContainsString('Set Parameter needs a key and Value', $this->_text->getCaptured());
	}

	public function testActionIndexShowsANullOrBooleanValue(): void
	{
		$this->_app->getParameters()['shellNull'] = null;
		$this->_app->getParameters()['shellFalse'] = false;
		$this->_app->getParameters()['shellInt'] = 5;

		$this->assertTrue($this->_action->actionIndex(['param/index']));

		$captured = $this->_text->getCaptured();
		$this->assertMatchesRegularExpression('/^shellNull *$/m', $captured);
		$this->assertMatchesRegularExpression('/^shellFalse +false$/m', $captured);
		$this->assertMatchesRegularExpression('/^shellInt +5$/m', $captured);
	}

	public function testActionGetReadsTheKeyZero(): void
	{
		$this->_app->getParameters()['0'] = 'zero';

		$this->assertTrue($this->_action->actionGet(['param/get', '0']));

		$this->assertStringContainsString("Parameter 0: 'zero'", $this->_text->getCaptured());
	}

	public function testActionSetStoresTheKeyZero(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set', '0', 'zero']));

		$this->assertSame([['0', 'zero', true, false]], $this->_module->sets);
	}
}
