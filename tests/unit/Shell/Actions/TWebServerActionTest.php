<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Shell\Actions\TWebServerAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestShellApplication;

/**
 * Runs a PHP process that exits at once in place of the `php -S` web server.
 */
class TWebServerActionTestExitingServer extends TWebServerAction
{
	public ?array $command = null;

	public function generateCommand(string $address, string $documentRoot, $router): array
	{
		$this->command = parent::generateCommand($address, $documentRoot, $router);
		return [PHP_BINARY, '-r', ''];
	}
}

/**
 * Tests for {@see TWebServerAction}.
 */
class TWebServerActionTest extends TestCase
{
	private TTestShellApplication $_app;
	private TTestCapturingTextWriter $_text;
	private mixed $_scriptFilename;

	protected function setUp(): void
	{
		$this->_app = new TTestShellApplication();
		$this->_text = new TTestCapturingTextWriter();
		$this->_scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? null;
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
	}

	protected function tearDown(): void
	{
		$_SERVER['SCRIPT_FILENAME'] = $this->_scriptFilename;
		$this->_app->restoreApplication();
	}

	private function createAction(string $class = TWebServerAction::class): TWebServerAction
	{
		$action = new $class();
		$action->setWriter(new TShellWriter($this->_text));
		return $action;
	}

	/**
	 * Returns a local TCP port that no process listens on.
	 */
	private function freePort(): int
	{
		$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
		$name = stream_socket_get_name($server, false);
		fclose($server);
		return (int) substr($name, strrpos($name, ':') + 1);
	}

	public function testActionServeQuietSendsServerOutputToTheNullDevice(): void
	{
		$this->_app->setQuietMode(2);
		$action = $this->createAction(TWebServerActionTestExitingServer::class);
		$action->setAddress('127.0.0.1')->setPort($port = $this->freePort());

		$this->assertTrue($action->actionServe(['http/serve']));

		$this->assertSame(['-S', "127.0.0.1:$port", '-t', __DIR__], array_slice($action->command, 1, 4));
		$this->assertSame('', $this->_text->getCaptured());
	}

	public function testActionServeNamesTheTakenAddressAndPort(): void
	{
		$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
		$name = stream_socket_get_name($server, false);
		$port = (int) substr($name, strrpos($name, ':') + 1);
		try {
			$action = $this->createAction(TWebServerActionTestExitingServer::class);
			$action->setAddress('127.0.0.1')->setPort($port);

			$this->assertTrue($action->actionServe(['http/serve']));
		} finally {
			fclose($server);
		}

		$this->assertNull($action->command);
		$this->assertStringContainsString("http://127.0.0.1:$port is taken by another process.", $this->_text->getCaptured());
	}

	public function testSetAddressBracketsABareIpv6Address(): void
	{
		$action = $this->createAction();

		$action->setAddress('::1');

		$this->assertSame('[::1]', $action->getAddress());
		$this->assertSame(8080, $action->getPort());
	}

	public function testSetAddressReadsThePortAfterABracketedIpv6Address(): void
	{
		$action = $this->createAction();

		$action->setAddress('[::1]:8777');

		$this->assertSame('[::1]', $action->getAddress());
		$this->assertSame(8777, $action->getPort());
	}
}
