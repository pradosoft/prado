<?php

use Prado\IO\Socket\TSocketServerAction;
use Prado\IO\Socket\TSocketServerModule;
use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;

/** A module whose serve() is a no-op, so actionServe can be driven without blocking. */
class StubSocketServerModule extends TSocketServerModule
{
	public bool $served = false;

	public function serve(): bool
	{
		$this->served = true;
		return true;
	}
}

/** A text writer that keeps every written byte, so flushed output can still be inspected. */
class CapturingTextWriter extends TTextWriter
{
	public string $captured = '';

	public function write($str)
	{
		$this->captured .= $str;
		parent::write($str);
	}
}

class TSocketServerActionTest extends PHPUnit\Framework\TestCase
{
	private TSocketServerAction $action;
	private CapturingTextWriter $capture;

	protected function setUp(): void
	{
		$this->action = new TSocketServerAction();
		$this->capture = new CapturingTextWriter();
		$this->action->setWriter(new TShellWriter($this->capture));
	}

	public function testModuleClassIsTheSocketServerModule()
	{
		self::assertSame(TSocketServerModule::class, $this->action->getModuleClass());
	}

	public function testInjectedModuleIsReturnedWithoutResolving()
	{
		$module = new TSocketServerModule();
		$this->action->setSocketServerModule($module);
		self::assertSame($module, $this->action->getSocketServerModule());
	}

	public function testGetModuleReturnsNullWhenNoneIsConfigured()
	{
		// The test application registers no TSocketServerModule; resolution reports none.
		self::assertNull($this->action->getSocketServerModule());
	}

	public function testAddressOverrideCoercion()
	{
		self::assertNull($this->action->getAddress());
		$this->action->setAddress('127.0.0.1');
		self::assertSame('127.0.0.1', $this->action->getAddress());
		$this->action->setAddress('');
		self::assertNull($this->action->getAddress(), 'An empty address clears the override.');
	}

	public function testPortOverrideCoercion()
	{
		self::assertNull($this->action->getPort());
		$this->action->setPort('8080');
		self::assertSame(8080, $this->action->getPort(), 'A numeric string becomes an int.');
		$this->action->setPort('');
		self::assertNull($this->action->getPort(), 'An empty port clears the override.');
	}

	public function testOptionsAreServeAddressAndPort()
	{
		self::assertSame(['address', 'port'], $this->action->options('serve'));
		self::assertSame([], $this->action->options('other'));
	}

	public function testOptionAliases()
	{
		self::assertSame(['a' => 'address', 'p' => 'port'], $this->action->optionAliases());
	}

	public function testActionServeAppliesOverridesAndRunsTheModule()
	{
		$module = new StubSocketServerModule();
		$this->action->setSocketServerModule($module);
		$this->action->setAddress('127.0.0.1');
		$this->action->setPort('9999');

		self::assertTrue($this->action->actionServe([]));
		self::assertTrue($module->served, 'actionServe runs the module.');
		self::assertSame('127.0.0.1', $module->getAddress(), 'The address override reaches the module.');
		self::assertSame(9999, $module->getPort(), 'The port override reaches the module.');
	}

	public function testActionServeReturnsTrueWhenNoModule()
	{
		self::assertTrue($this->action->actionServe([]), 'A missing module is reported, not fatal.');
	}

	public function testActionServeAnnouncesTheResolvedEndpoint()
	{
		$module = new StubSocketServerModule();
		$module->setAddress('127.0.0.1')->setPort(9000);
		$this->action->setSocketServerModule($module);

		self::assertTrue($this->action->actionServe([]));
		self::assertTrue($module->served, 'A valid endpoint runs the module.');
		self::assertStringContainsString('Socket server listening on tcp://127.0.0.1:9000', $this->capture->captured);
	}

	public function testActionServeReportsAMisconfiguredEndpointAsAnError()
	{
		$module = new StubSocketServerModule();   // port 0, no explicit endpoint: getEndpoint() throws
		$this->action->setSocketServerModule($module);

		self::assertTrue($this->action->actionServe([]), 'A misconfigured endpoint is reported, not fatal.');
		self::assertFalse($module->served, 'A misconfigured endpoint never starts the server.');
		self::assertStringContainsString('Error', $this->capture->captured, 'The misconfiguration is written as a shell error.');
	}

	public function testActionServeReportsAMissingModule()
	{
		self::assertTrue($this->action->actionServe([]));
		self::assertStringContainsString('is not found', $this->capture->captured);
	}

	public function testResolutionIsCachedAfterAMissAndDoesNotReportTwice()
	{
		self::assertNull($this->action->getSocketServerModule(), 'The first resolution finds no module.');
		self::assertStringContainsString('is not found', $this->capture->captured);

		$this->capture->captured = '';
		self::assertNull($this->action->getSocketServerModule(), 'The cached miss returns null again.');
		self::assertSame('', $this->capture->captured, 'A cached miss does not re-resolve or re-report.');
	}
}
