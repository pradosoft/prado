<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Shell\Actions\TDbParameterAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Util\TDbParameterModule;
use Prado\Util\TVarDumper;

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

	public function testAll(): void
	{
		$this->assertFalse($this->_action->getAll());
		$this->_action->setAll('');
		$this->assertTrue($this->_action->getAll());
		$this->_action->setAll('false');
		$this->assertFalse($this->_action->getAll());
		$this->_action->setAll(true);
		$this->assertTrue($this->_action->getAll());
	}

	public function testOptionsAreAllForIndexOnly(): void
	{
		$this->assertSame(['all'], $this->_action->options('index'));
		$this->assertSame([], $this->_action->options('get'));
		$this->assertSame(['a' => 'all'], $this->_action->optionAliases());
	}

	public function testGetDbParameterModuleFindsTheApplicationModule(): void
	{
		$action = new TDbParameterAction();
		$this->assertNull($action->getDbParameterModule());

		$module = new TDbParameterActionTestModule();
		$this->_app->setModule('shellTestDbParam', $module);
		$this->assertNull($action->getDbParameterModule());

		$action = new TDbParameterAction();
		$this->assertSame($module, $action->getDbParameterModule());
	}

	public function testActionIndexWithoutAModuleWritesAnError(): void
	{
		$this->_action->setDbParameterModule(null);

		$this->assertNull($this->_action->actionIndex(['param/index']));
		$this->assertStringContainsString('No TDbParameterModule found', $this->_text->getCaptured());
	}

	public function testActionIndexListsTheStoredParameters(): void
	{
		$this->_module->stored = ['shellStored'];
		$this->_app->getParameters()['shellStored'] = 'kept';
		$this->_app->getParameters()['shellNotStored'] = 'skipped';

		$this->assertTrue($this->_action->actionIndex(['param/index']));

		$captured = $this->_text->getCaptured();
		$this->assertMatchesRegularExpression('/^shellStored +kept$/m', $captured);
		$this->assertStringNotContainsString('shellNotStored', $captured);
	}

	public function testActionIndexAllListsEveryParameter(): void
	{
		$this->_module->stored = [];
		$this->_action->setAll(true);
		$this->_app->getParameters()['shellNotStored'] = 'listed';

		$this->assertTrue($this->_action->actionIndex(['param/index']));

		$this->assertMatchesRegularExpression('/^shellNotStored +listed$/m', $this->_text->getCaptured());
	}

	public function testActionIndexWidensTheKeyColumnForALongKey(): void
	{
		$key = str_repeat('k', 30);
		$this->_app->getParameters()[$key] = 'v';

		$this->_action->actionIndex(['param/index']);

		$lines = explode("\n", $this->_text->getCaptured());
		$this->assertSame('Parameter Key' . str_repeat(' ', 18) . 'Value', $lines[1]);
		$this->assertContains($key . ' v', $lines);
	}

	public function testActionIndexNamesObjectAndArrayValues(): void
	{
		$this->_app->getParameters()['shellObject'] = new \stdClass();
		$this->_app->getParameters()['shellArray'] = [1, 2];

		$this->_action->actionIndex(['param/index']);

		$captured = $this->_text->getCaptured();
		$this->assertMatchesRegularExpression('/^shellObject +\(object\)$/m', $captured);
		$this->assertMatchesRegularExpression('/^shellArray +\(array\)$/m', $captured);
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

	public function testActionGetShowsTheValue(): void
	{
		$this->_app->getParameters()['shellTestKey'] = ['a' => 1];

		$this->assertTrue($this->_action->actionGet(['param/get', 'shellTestKey']));

		$this->assertStringContainsString('Parameter shellTestKey: ' . TVarDumper::dump(['a' => 1]), $this->_text->getCaptured());
	}

	public function testActionGetReadsTheKeyZero(): void
	{
		$this->_app->getParameters()['0'] = 'zero';

		$this->assertTrue($this->_action->actionGet(['param/get', '0']));

		$this->assertStringContainsString("Parameter 0: 'zero'", $this->_text->getCaptured());
	}

	public function testActionGetWithoutAKeyWritesAnError(): void
	{
		$this->assertTrue($this->_action->actionGet(['param/get']));
		$this->assertStringContainsString('Get Parameter needs a key', $this->_text->getCaptured());

		$this->_text->flush();
		$this->assertTrue($this->_action->actionGet(['param/get', '']));
		$this->assertStringContainsString('Get Parameter needs a key', $this->_text->getCaptured());
	}

	public function testActionSetStoresTheKeyZero(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set', '0', 'zero']));

		$this->assertSame([['0', 'zero', true, false]], $this->_module->sets);
	}

	public function testActionSetTakesTheAutoloadArgument(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set', 'shellTestKey', 'v', 'false']));

		$this->assertSame([['shellTestKey', 'v', false, false]], $this->_module->sets);
		$this->assertStringContainsString("Set Parameter shellTestKey To: 'v'", $this->_text->getCaptured());
	}

	public function testActionSetWithoutAModuleWritesAnError(): void
	{
		$this->_action->setDbParameterModule(null);

		$this->assertTrue($this->_action->actionSet(['param/set', 'shellTestKey', 'v']));
		$this->assertStringContainsString('No TDbParameterModule found', $this->_text->getCaptured());
	}

	public function testActionSetWithAnEmptyKeyWritesAnError(): void
	{
		$this->assertTrue($this->_action->actionSet(['param/set', '', 'v']));

		$this->assertSame([], $this->_module->sets);
		$this->assertStringContainsString('Set Parameter needs a key', $this->_text->getCaptured());
	}
}
