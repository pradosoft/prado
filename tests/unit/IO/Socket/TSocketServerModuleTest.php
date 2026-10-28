<?php

namespace Prado\Test\Unit\IO\Socket;

use Prado\Exceptions\TConfigurationException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TRevoltEventLoop;
use Prado\IO\Socket\TSocketReactor;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketServerModule;
use Prado\IO\Socket\TSocketStream;
use Prado\IO\TUriScheme;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\TBehavior;
use Prado\Util\TSignalParameter;
use Prado\Util\TSignalsDispatcher;

/** Exposes the protected serve seams for direct testing. */
class TestableSocketServerModule extends TSocketServerModule
{
	public function publicCreateServer(): TSocketServer
	{
		return $this->createServer();
	}

	public function publicAcceptClient(TSocketServer $server, IEventLoop $loop): void
	{
		$this->acceptClient($server, $loop);
	}

	public function publicDispatchClient(TSocketStream $connection, IEventLoop $loop): void
	{
		$this->dispatchClient($connection, $loop);
	}
}

/** A subclass that tunes a default through its constant, exercising the static:: lookup. */
class TlsDefaultSocketServerModule extends TSocketServerModule
{
	public const DEFAULT_SCHEME = TUriScheme::TLS;
}

/** A behavior that denies the serve permission, exercising the dyServe gate. */
class DenyServeBehavior extends TBehavior
{
	public function dyServe($value, $callchain)
	{
		return true;   // the permission is denied
	}
}

/**
 * Runs serve()'s setup and teardown around an immediate stop, so the finally-block cleanup is
 * exercised in-process.  serveLoop() accepts one real connection, then stops the server.
 */
class StopWithinLoopSocketServerModule extends TSocketServerModule
{
	public bool $loopRan = false;
	public ?TSocketServer $loopServer = null;
	public ?TSocketStream $accepted = null;
	public ?TSocketStream $clientRef = null;

	protected function serveLoop(TSocketServer $server): void
	{
		$this->loopRan = true;
		$this->loopServer = $server;
		$this->clientRef = TSocketStream::connect('tcp://127.0.0.1:' . $server->getPort(), 1.0);
		$this->accepted = $server->accept(1.0);
		$this->stop();   // close the listening server and end the loop
	}
}

class TSocketServerModuleTest extends \PHPUnit\Framework\TestCase
{
	public function testSetSchemeRejectsDatagramSchemes()
	{
		$module = new TSocketServerModule();
		$module->setScheme('tcp');
		self::assertSame('tcp', $module->getScheme(), 'A stream scheme is accepted.');

		$this->expectException(TConfigurationException::class);
		$module->setScheme('udp');
	}

	public function testCreateServerRejectsDatagramEndpoint()
	{
		$module = new TestableSocketServerModule();
		$module->setEndpoint('udp://127.0.0.1:0');   // bypasses setScheme via an explicit endpoint
		$this->expectException(TConfigurationException::class);
		$module->publicCreateServer();
	}

	public function testCreateServerBindsAStreamEndpoint()
	{
		$module = new TestableSocketServerModule();
		$module->setEndpoint('tcp://127.0.0.1:0');
		$server = $module->publicCreateServer();
		self::assertInstanceOf(TSocketServer::class, $server);
		self::assertTrue($server->isListening());
		$server->close();
	}

	/**
	 * Binds a listener, connects a client, and accepts it through the module so the module watches
	 * the connection on the loop.
	 * @param TestableSocketServerModule $module The module under test.
	 * @param IEventLoop $loop The loop the module watches the connection on.
	 * @return array{0: TSocketServer, 1: TSocketStream, 2: TSocketStream} The server, the client, and the accepted connection.
	 */
	private function acceptOne(TestableSocketServerModule $module, IEventLoop $loop): array
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$accepted = null;
		$module->attachEventHandler('onClientConnect', function ($sender, $param) use (&$accepted) {
			$accepted = $param;
		});
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $server->getPort(), 1.0);
		$deadline = microtime(true) + 2.0;
		while ($accepted === null && microtime(true) < $deadline) {
			$module->publicAcceptClient($server, $loop);
			if ($accepted === null) {
				usleep(10000);
			}
		}
		self::assertInstanceOf(TSocketStream::class, $accepted, 'The module accepts the connection.');
		return [$server, $client, $accepted];
	}

	public function testDispatchRaisesClientDataWhenReadable()
	{
		$loop = new TSocketReactor();
		$module = new TestableSocketServerModule();
		[$server, $client, $conn] = $this->acceptOne($module, $loop);
		$data = null;
		$disconnected = false;
		$module->attachEventHandler('onClientData', function ($sender, $param) use (&$data) {
			$data = $param;
		});
		$module->attachEventHandler('onClientDisconnect', function () use (&$disconnected) {
			$disconnected = true;
		});

		$client->write('hi');
		$loop->tick(1.0);   // the loop dispatches the readable connection through dispatchClient()
		self::assertSame($conn, $data, 'A readable connection with data raises onClientData.');
		self::assertFalse($disconnected, 'Data does not raise onClientDisconnect.');
		self::assertCount(1, $loop->getIdentifiers(), 'The connection stays watched after data.');

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testDispatchDetectsHalfCloseThroughPeek()
	{
		$loop = new TSocketReactor();
		$module = new TestableSocketServerModule();
		[$server, $client, $conn] = $this->acceptOne($module, $loop);
		$dataRaised = false;
		$closed = null;
		$module->attachEventHandler('onClientData', function () use (&$dataRaised) {
			$dataRaised = true;
		});
		$module->attachEventHandler('onClientDisconnect', function ($sender, $param) use (&$closed) {
			$closed = $param;
		});

		$client->close();   // the connection is now readable at end of stream
		$module->publicDispatchClient($conn, $loop);

		self::assertSame($conn, $closed, 'A readable connection at end of stream raises onClientDisconnect.');
		self::assertFalse($dataRaised, 'A half-close does not raise onClientData.');
		self::assertFalse($conn->isOpen(), 'The disconnected connection is closed.');
		self::assertSame([], $loop->getIdentifiers(), 'The disconnected connection is no longer watched.');
		$server->close();
	}

	public function testDispatchStopsWatchingAConnectionClosedByAHandler()
	{
		$loop = new TSocketReactor();
		$module = new TestableSocketServerModule();
		[$server, $client, $conn] = $this->acceptOne($module, $loop);
		$module->attachEventHandler('onClientData', function ($sender, $param) {
			$param->close();   // the handler closes the connection itself
		});

		$client->write('bye');
		$module->publicDispatchClient($conn, $loop);

		self::assertFalse($conn->isOpen());
		self::assertSame([], $loop->getIdentifiers(), 'A connection closed by a handler is no longer watched.');
		$client->close();
		$server->close();
	}

	public function testEventLoopDefaultsToTheRevoltLoopAndAcceptsALoop()
	{
		$module = new TSocketServerModule();
		self::assertSame(TRevoltEventLoop::class, $module->getEventLoopClass());
		self::assertInstanceOf(TRevoltEventLoop::class, $module->getEventLoop(), 'The default loop is created from EventLoopClass.');

		$reactor = new TSocketReactor();
		$module->setEventLoop($reactor);
		self::assertSame($reactor, $module->getEventLoop(), 'An injected loop is used as is.');

		$module->setEventLoop(null)->setEventLoopClass(TSocketReactor::class);
		self::assertInstanceOf(TSocketReactor::class, $module->getEventLoop(), 'EventLoopClass selects the default loop.');

		$this->expectException(TConfigurationException::class);
		$module->setEventLoopClass(TSocketServer::class);
	}

	public function testConstructorAppliesTheDefaultConstants()
	{
		$module = new TSocketServerModule();
		self::assertSame(TUriScheme::TCP, $module->getScheme());
		self::assertSame('0.0.0.0', $module->getAddress());
		self::assertSame(0, $module->getPort());
		self::assertSame(TSocketServer::class, $module->getServerClass());
	}

	public function testSubclassDefaultConstantIsHonored()
	{
		$module = new TlsDefaultSocketServerModule();
		self::assertSame(TUriScheme::TLS, $module->getScheme(), 'static:: picks up the subclass default constant.');
	}

	public function testEndpointComposesFromSchemeAddressPort()
	{
		$module = new TSocketServerModule();
		$module->setScheme('tcp')->setAddress('127.0.0.1')->setPort(8080);
		self::assertSame('tcp://127.0.0.1:8080', $module->getEndpoint());
	}

	public function testEndpointRequiresAPositivePortOrExplicitUri()
	{
		$module = new TSocketServerModule();   // port 0, no explicit endpoint
		$this->expectException(TConfigurationException::class);
		$module->getEndpoint();
	}

	public function testExplicitEndpointOverridesCompositionAndClears()
	{
		$module = new TSocketServerModule();
		$module->setEndpoint('unix:///tmp/app.sock');
		self::assertSame('unix:///tmp/app.sock', $module->getEndpoint());

		$module->setEndpoint('')->setPort(9000);   // cleared, so it composes again
		self::assertSame('tcp://0.0.0.0:9000', $module->getEndpoint());
	}

	public function testServerClassDefaultsAndAcceptsAServerClass()
	{
		$module = new TSocketServerModule();
		self::assertSame(TSocketServer::class, $module->getServerClass());
		$module->setServerClass(TSocketServer::class);
		self::assertSame(TSocketServer::class, $module->getServerClass());
	}

	public function testSetServerClassRejectsANonServerClass()
	{
		$module = new TSocketServerModule();
		$this->expectException(TConfigurationException::class);
		$module->setServerClass(\stdClass::class);
	}

	public function testSignalHandlersClearTheExitAndRequestStop()
	{
		$module = new TSocketServerModule();
		$terminate = new TSignalParameter(0, true);   // would exit the process by default
		$module->fxSignalTerminate($module, $terminate);
		self::assertFalse($terminate->getIsExiting(), 'SIGTERM clears the exit so serve() unwinds.');

		$interrupt = new TSignalParameter(0, true);
		$module->fxSignalInterrupt($module, $interrupt);
		self::assertFalse($interrupt->getIsExiting(), 'SIGINT clears the exit so serve() unwinds.');
	}

	public function testForkedServeAcceptsAConnectionAndStopsOnSignal()
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl forking is not available.');
		}

		// Reserve a likely-free port the forked child binds after the fork.
		$probe = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $probe->getPort();
		$probe->close();

		$pid = TProcessHelper::fork();
		if ($pid === 0) {
			// Child: run the daemon, echoing each client's bytes, until a signal stops it.
			// The child always exits; a throw (e.g. the port was taken before bind) must never
			// return into PHPUnit, which would run the rest of the suite as a duplicate process.
			try {
				$module = new TSocketServerModule();
				$module->setEndpoint("tcp://127.0.0.1:{$port}");
				$module->attachEventHandler('onClientData', function ($sender, $conn) {
					$conn->write((string) $conn->read(65536));
				});
				$module->serve();
				exit(0);
			} catch (\Throwable $e) {
				fwrite(STDERR, 'Forked server child failed: ' . $e->getMessage() . PHP_EOL);
				exit(1);
			}
		}

		self::assertGreaterThan(0, $pid, 'The server was forked.');
		try {
			$client = null;
			$deadline = microtime(true) + 3.0;
			while ($client === null && microtime(true) < $deadline) {
				try {
					$client = TSocketStream::connect("tcp://127.0.0.1:{$port}", 1.0);
				} catch (\Throwable $e) {
					usleep(20000);   // the child has not bound yet; retry
				}
			}
			self::assertNotNull($client, 'Connected to the forked server.');

			$client->write('ping');
			$client->setBlocking(false);
			$echo = '';
			$deadline = microtime(true) + 3.0;
			while ($echo !== 'ping' && microtime(true) < $deadline) {
				$chunk = $client->read(16);
				$echo .= $chunk;
				if ($chunk === '') {
					usleep(20000);
				}
			}
			self::assertSame('ping', $echo, 'The forked daemon accepted the connection and echoed the data.');
			$client->close();
		} finally {
			// Reap the child without hanging the suite: poll for a graceful exit, then escalate to
			// SIGKILL so a wedged child never zombies the test run.
			TProcessHelper::sendSignal(SIGTERM, $pid);
			$status = 0;
			$waited = 0;
			$deadline = microtime(true) + 5.0;
			do {
				$waited = pcntl_waitpid($pid, $status, WNOHANG);
				if ($waited === $pid) {
					break;
				}
				usleep(20000);
			} while (microtime(true) < $deadline);
			if ($waited !== $pid) {
				TProcessHelper::sendSignal(SIGKILL, $pid);
				pcntl_waitpid($pid, $status);
			}
		}

		self::assertTrue(pcntl_wifexited($status), 'The daemon exited cleanly rather than being signal-killed.');
		self::assertSame(0, pcntl_wexitstatus($status), 'SIGTERM was handled gracefully (clean exit).');
	}

	public function testAddressAndPortRoundTripWithCoercion()
	{
		$module = new TSocketServerModule();
		$module->setAddress('1.2.3.4');
		self::assertSame('1.2.3.4', $module->getAddress());
		$module->setPort('8080');
		self::assertSame(8080, $module->getPort(), 'A numeric string port becomes an int.');
	}

	public function testGetServerIsNullBeforeServing()
	{
		self::assertNull((new TSocketServerModule())->getServer());
	}

	public function testStopWithoutAServerIsSafe()
	{
		$module = new TSocketServerModule();
		$module->stop();   // no running server: requests stop without error
		self::assertNull($module->getServer());
	}

	public function testSocketOptionsIsALazyReusedMap()
	{
		$module = new TSocketServerModule();
		$options = $module->getSocketOptions();
		self::assertInstanceOf(\Prado\Collections\TMap::class, $options);
		self::assertSame($options, $module->getSocketOptions(), 'The options map is created once and reused.');
		self::assertSame(0, $options->getCount());
	}

	public function testCreateServerAppliesSocketContextOptions()
	{
		$module = new TestableSocketServerModule();
		$module->setEndpoint('tcp://127.0.0.1:0');
		$module->getSocketOptions()->add('socket', ['backlog' => 128]);   // a non-empty context branch
		$server = $module->publicCreateServer();
		self::assertTrue($server->isListening(), 'The server binds with stream context options applied.');
		$server->close();
	}

	public function testGetPermissionsDeclaresTheSocketServerPermission()
	{
		$permissions = (new TSocketServerModule())->getPermissions(null);
		self::assertCount(1, $permissions);
		self::assertInstanceOf(TPermissionEvent::class, $permissions[0]);
		self::assertSame(TSocketServerModule::PERM_SOCKET_SERVER, $permissions[0]->getName());
		self::assertContains('dyserve', (array) $permissions[0]->getEvents(), 'The permission is tied to dyServe (stored lower case).');
	}

	public function testInitRegistersTheShellActionOnAuthenticationComplete()
	{
		$module = new TSocketServerModule();
		$app = $module->getApplication();
		$handler = [$module, 'registerShellAction'];
		try {
			$module->init(null);
			self::assertTrue(
				$app->getEventHandlers('onAuthenticationComplete')->contains($handler),
				'init() registers the shell action for the authentication-complete event.'
			);
		} finally {
			$app->detachEventHandler('onAuthenticationComplete', $handler);   // keep the shared app clean
		}
	}

	public function testServeIsDeniedWhenThePermissionIsDenied()
	{
		$module = new TSocketServerModule();
		$module->setEndpoint('tcp://127.0.0.1:0');
		$module->attachBehavior('deny', new DenyServeBehavior());

		self::assertFalse($module->serve(), 'A denied permission stops serve() before it binds.');
		self::assertNull($module->getServer(), 'A denied serve() binds no server.');
	}

	public function testAcceptClientRaisesConnectAndWatchesTheConnection()
	{
		$loop = new TSocketReactor();
		$module = new TestableSocketServerModule();
		[$server, $client, $connected] = $this->acceptOne($module, $loop);

		self::assertInstanceOf(TSocketStream::class, $connected, 'acceptClient raises onClientConnect with the connection.');
		self::assertCount(1, $loop->getIdentifiers(), 'The accepted connection is watched on the loop.');

		$connected->close();
		$client->close();
		$server->close();
	}

	public function testAcceptClientIgnoresAReadableServerWithNoConnection()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$loop = new TSocketReactor();
		$module = new TestableSocketServerModule();
		$raised = false;
		$module->attachEventHandler('onClientConnect', function () use (&$raised) {
			$raised = true;
		});

		$module->publicAcceptClient($server, $loop);   // no pending connection: the early return
		self::assertFalse($raised, 'No pending connection raises no onClientConnect.');
		self::assertSame([], $loop->getIdentifiers(), 'Nothing is watched without a connection.');

		$server->close();
	}

	public function testDispatchTreatsABrokenConnectionAsDisconnect()
	{
		$loop = new TSocketReactor();
		$module = new TestableSocketServerModule();
		[$server, $client, $conn] = $this->acceptOne($module, $loop);
		$dataRaised = false;
		$closed = null;
		$module->attachEventHandler('onClientData', function () use (&$dataRaised) {
			$dataRaised = true;
		});
		$module->attachEventHandler('onClientDisconnect', function ($sender, $param) use (&$closed) {
			$closed = $param;
		});

		$conn->close();   // the resource is gone, so a peek returns false (a broken connection)
		$module->publicDispatchClient($conn, $loop);

		self::assertSame($conn, $closed, 'A broken connection (peek false) raises onClientDisconnect.');
		self::assertFalse($dataRaised, 'A broken connection does not raise onClientData.');
		self::assertSame([], $loop->getIdentifiers(), 'The broken connection is no longer watched.');

		$client->close();
		$server->close();
	}

	public function testServeClosesTheServerAndConnectionsOnExit()
	{
		// serve() lazily creates the signals dispatcher singleton; restore the global state afterward
		// only when this test was the one that created it.
		$createdSignals = TSignalsDispatcher::singleton(false) === null;
		$module = new StopWithinLoopSocketServerModule();
		$module->setEndpoint('tcp://127.0.0.1:0');
		$wasListening = $module->getListeningToGlobalEvents();   // a module auto-listens to global events

		try {
			self::assertTrue($module->serve(), 'serve() ran.');
			self::assertTrue($module->loopRan, 'serveLoop ran.');
			self::assertInstanceOf(TSocketStream::class, $module->accepted, 'A connection was accepted in the loop.');
			self::assertFalse($module->accepted->isOpen(), 'serve() closed the open connection on exit.');
			self::assertFalse($module->loopServer->isListening(), 'serve() closed the listening server on exit.');
			self::assertNull($module->getServer(), 'serve() clears the running server on exit.');
			self::assertSame($wasListening, $module->getListeningToGlobalEvents(), 'serve() restores the prior global-event listening state on exit.');
		} finally {
			$module->clientRef?->close();
			$module->unlisten();
			if ($createdSignals) {
				TSignalsDispatcher::singleton(false)?->detach();
			}
		}
	}

	public function testServeRestoresAModuleThatWasNotListening()
	{
		$createdSignals = TSignalsDispatcher::singleton(false) === null;
		$module = new StopWithinLoopSocketServerModule();
		$module->setEndpoint('tcp://127.0.0.1:0');
		$module->unlisten();   // opt out of global events before serving
		self::assertFalse($module->getListeningToGlobalEvents(), 'The module no longer listens.');

		try {
			self::assertTrue($module->serve(), 'serve() ran.');
			self::assertFalse($module->getListeningToGlobalEvents(), 'serve() restores the not-listening state on exit.');
		} finally {
			$module->clientRef?->close();
			$module->unlisten();
			if ($createdSignals) {
				TSignalsDispatcher::singleton(false)?->detach();
			}
		}
	}
}
