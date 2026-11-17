<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Shell\Actions\TWebServerAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
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
		$writer = new TShellWriter($this->_text);
		$writer->setColorSupported(false);
		$action->setWriter($writer);
		return $action;
	}

	/**
	 * Returns an exiting-server action on a free local port.
	 */
	private function createExitingAction(): TWebServerActionTestExitingServer
	{
		$action = $this->createAction(TWebServerActionTestExitingServer::class);
		$action->setAddress('127.0.0.1')->setPort($this->freePort());
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

	public function testAll(): void
	{
		$action = $this->createAction();

		$this->assertFalse($action->getAll());
		$this->assertSame($action, $action->setAll(null));
		$this->assertTrue($action->getAll());
		$action->setAll('false');
		$this->assertFalse($action->getAll());
		$action->setAll('');
		$this->assertTrue($action->getAll());
		$action->setAll(false);
		$this->assertFalse($action->getAll());
	}

	public function testGetAddressDefaultsFromIpv6AndAll(): void
	{
		$action = $this->createAction();

		$this->assertSame('127.0.0.1', $action->getAddress());
		$action->setAll(true);
		$this->assertSame('0.0.0.0', $action->getAddress());
		$action->setIpv6(true);
		$this->assertSame('[::0]', $action->getAddress());
		$action->setAll(false);
		$this->assertSame('localhost', $action->getAddress());
	}

	public function testSetAddressReadsANumericPort(): void
	{
		$action = $this->createAction();

		$this->assertSame($action, $action->setAddress('localhost:8777'));
		$this->assertSame('localhost', $action->getAddress());
		$this->assertSame(8777, $action->getPort());
	}

	public function testSetAddressKeepsANonNumericPortInTheAddress(): void
	{
		$action = $this->createAction();

		$action->setAddress('localhost:http');

		$this->assertSame('localhost:http', $action->getAddress());
		$this->assertSame(8080, $action->getPort());
	}

	public function testSetAddressWithoutAPort(): void
	{
		$action = $this->createAction();

		$action->setAddress('testdomain.com');
		$this->assertSame('testdomain.com', $action->getAddress());

		$action->setAddress('[::1]');
		$this->assertSame('[::1]', $action->getAddress());

		$action->setAddress('[::1');
		$this->assertSame('[::1', $action->getAddress());
		$this->assertSame(8080, $action->getPort());
	}

	public function testSetAddressEmptyRestoresTheDefault(): void
	{
		$action = $this->createAction();
		$action->setAddress('testdomain.com');

		$action->setAddress('');
		$this->assertSame('127.0.0.1', $action->getAddress());

		$action->setAddress('testdomain.com');
		$action->setAddress(null);
		$this->assertSame('127.0.0.1', $action->getAddress());
	}

	public function testPort(): void
	{
		$action = $this->createAction();

		$this->assertSame(8080, $action->getPort());
		$this->assertSame($action, $action->setPort('8777'));
		$this->assertSame(8777, $action->getPort());
	}

	public function testIpv6(): void
	{
		$action = $this->createAction();

		$this->assertFalse($action->getIpv6());
		$this->assertSame($action, $action->setIpv6(''));
		$this->assertTrue($action->getIpv6());
		$action->setIpv6('false');
		$this->assertFalse($action->getIpv6());
		$action->setIpv6(null);
		$this->assertTrue($action->getIpv6());
	}

	public function testWorkers(): void
	{
		$action = $this->createAction();

		$this->assertSame(1, $action->getWorkers());
		$this->assertSame($action, $action->setWorkers(''));
		$this->assertSame(8, $action->getWorkers());
		$action->setWorkers('3');
		$this->assertSame(3, $action->getWorkers());
		$action->setWorkers(0);
		$this->assertSame(1, $action->getWorkers());
		$action->setWorkers(null);
		$this->assertSame(8, $action->getWorkers());
	}

	public function testOptionsAndAliases(): void
	{
		$action = $this->createAction();

		$this->assertSame(['address', 'port', 'workers', 'ipv6', 'all'], $action->options('serve'));
		$this->assertSame([], $action->options('index'));
		$this->assertSame(['a' => 'address', 'p' => 'port', 'w' => 'workers', '6' => 'ipv6', 'i' => 'all'], $action->optionAliases());
	}

	public function testGenerateCommandAppendsTheRouter(): void
	{
		$action = $this->createAction();

		$command = $action->generateCommand('127.0.0.1:8080', '/root', null);
		$this->assertSame(['-S', '127.0.0.1:8080', '-t', '/root'], array_slice($command, -4));

		$command = $action->generateCommand('127.0.0.1:8080', '/root', '/root/router.php');
		$this->assertSame(['-S', '127.0.0.1:8080', '-t', '/root', '/root/router.php'], array_slice($command, -5));
	}

	public function testActionServeShowsTheDocumentRootAndRouter(): void
	{
		$action = $this->createExitingAction();
		$action->setWorkers(4);

		$this->assertTrue($action->actionServe(['http/serve', __FILE__]));

		$this->assertSame(__FILE__, end($action->command));
		$captured = $this->_text->getCaptured();
		$this->assertStringContainsString('Document root is "' . __DIR__ . '"', $captured);
		$this->assertStringContainsString('Routing file is "' . __FILE__ . '"', $captured);
		$this->assertStringContainsString('To quit press CTRL-C or COMMAND-C.', $captured);
	}

	public function testActionServeResolvesARelativeRouter(): void
	{
		$cwd = getcwd();
		chdir(__DIR__);
		try {
			$action = $this->createExitingAction();

			$this->assertTrue($action->actionServe(['http/serve', basename(__FILE__)]));
		} finally {
			chdir($cwd);
		}

		$this->assertSame(__FILE__, end($action->command));
	}

	public function testActionServeWithoutTheRouterFileWritesAnError(): void
	{
		$action = $this->createExitingAction();

		$this->assertTrue($action->actionServe(['http/serve', 'missing-router.php']));

		$this->assertNull($action->command);
		$this->assertStringContainsString('Routing file "missing-router.php" does not exist.', $this->_text->getCaptured());
	}

	public function testActionServeWithoutTheDocumentRootWritesAnError(): void
	{
		$root = DIRECTORY_SEPARATOR . 'missing-' . uniqid();
		$_SERVER['SCRIPT_FILENAME'] = $root . DIRECTORY_SEPARATOR . 'index.php';
		$action = $this->createExitingAction();

		$this->assertTrue($action->actionServe(['http/serve']));

		$this->assertNull($action->command);
		$this->assertStringContainsString('Document root "' . $root . '" does not exist.', $this->_text->getCaptured());
	}

	public function testActionServeQuietErrorsWritesNoError(): void
	{
		$this->_app->setQuietMode(3);

		$action = $this->createExitingAction();
		$this->assertTrue($action->actionServe(['http/serve', 'missing-router.php']));

		$_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'missing' . DIRECTORY_SEPARATOR . 'index.php';
		$this->assertTrue($action->actionServe(['http/serve']));

		$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
		$name = stream_socket_get_name($server, false);
		try {
			$_SERVER['SCRIPT_FILENAME'] = __FILE__;
			$action->setPort((int) substr($name, strrpos($name, ':') + 1));
			$this->assertTrue($action->actionServe(['http/serve']));
		} finally {
			fclose($server);
		}

		$this->assertNull($action->command);
		$this->assertSame('', $this->_text->getCaptured());
	}

	public function testActionServeOutsideAShellApplicationIsNotQuiet(): void
	{
		$app = new TTestApplication();
		try {
			$action = $this->createExitingAction();

			$this->assertTrue($action->actionServe(['http/serve']));
		} finally {
			$app->restoreApplication();
		}

		$this->assertStringContainsString('To quit press CTRL-C or COMMAND-C.', $this->_text->getCaptured());
	}

	public function testSetAddressKeepsANonNumericPortAfterABracketedIpv6Address(): void
	{
		$action = $this->createAction();

		$action->setAddress('[::1]:http');

		$this->assertSame('[::1]:http', $action->getAddress());
		$this->assertSame(8080, $action->getPort());
	}
}
