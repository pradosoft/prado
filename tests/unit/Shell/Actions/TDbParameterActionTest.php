<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Shell\Actions\TDbParameterAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Util\TDbParameterModule;

/**
 * Records {@see TDbParameterModule::set()} calls, and reports each key as stored,
 * without a database.
 */
class TDbParameterActionTestModule extends TDbParameterModule
{
	public array $sets = [];

	public function set($key, $value, $autoLoad = true, $setParameter = true)
	{
		$this->sets[] = [$key, $value, $autoLoad, $setParameter];
	}

	public function exists($key)
	{
		return true;
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
		$this->_action->setWriter(new TShellWriter($this->_text));
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
}
