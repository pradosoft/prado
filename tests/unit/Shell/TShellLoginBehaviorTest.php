<?php

namespace Prado\Test\Unit\Shell;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TExitException;
use Prado\Security\TAuthManager;
use Prado\Shell\TShellLoginBehavior;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\TTestShellApplication;
use Prado\Util\Helpers\TProcessHelper;

/**
 * Records login() calls and accepts the password `secret`.
 */
class TShellLoginBehaviorTestAuthManager extends TAuthManager
{
	public array $logins = [];

	public function login($username, #[\SensitiveParameter] $password, $expire = 0)
	{
		$this->logins[] = [$username, $password];
		return $password === 'secret';
	}
}

/**
 * Reads scripted lines and records terminal echo changes.
 */
class TShellLoginBehaviorTestBehavior extends TShellLoginBehavior
{
	public array $lines = [];
	public array $echoes = [];
	public bool $echoChanges = true;
	public bool $terminal = false;

	protected function readLine(): string
	{
		return array_shift($this->lines) ?? '';
	}

	protected function setTerminalEcho(bool $echo): bool
	{
		$this->echoes[] = $echo;
		return $this->echoChanges;
	}

	public function callReadPassword(bool $hide = true): string
	{
		return $this->readPassword($hide);
	}

	public function callSetTerminalEcho(bool $echo): bool
	{
		return parent::setTerminalEcho($echo);
	}

	public function callIsStdInTerminal(): bool
	{
		return parent::isStdInTerminal();
	}

	protected function isStdInTerminal(): bool
	{
		return $this->terminal;
	}
}

/**
 * Tests for {@see TShellLoginBehavior}.
 */
class TShellLoginBehaviorTest extends TestCase
{
	private TTestShellApplication $_app;
	private TTestCapturingTextWriter $_text;
	private TShellLoginBehaviorTestAuthManager $_auth;
	private TShellLoginBehaviorTestBehavior $_behavior;

	protected function setUp(): void
	{
		$this->_app = new TTestShellApplication();
		$this->_text = new TTestCapturingTextWriter();
		$this->_app->setWriter(new TShellWriter($this->_text));
		$this->_auth = new TShellLoginBehaviorTestAuthManager();
		$this->_behavior = new TShellLoginBehaviorTestBehavior();
		$this->_auth->attachBehavior('shellLogin', $this->_behavior);
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
	}

	public function testAttachRegistersTheUserAndPasswordOptions(): void
	{
		$options = $this->_app->pubGetOptions();
		$aliases = $this->_app->pubGetOptionAliases();

		$this->assertSame([$this->_behavior, 'setUsername'], $options['user']);
		$this->assertSame([$this->_behavior, 'setPassword'], $options['password']);
		$this->assertSame('user', $aliases['u']);
		$this->assertSame('password', $aliases['p']);
	}

	public function testLoginPromptsForTheMissingCredentials(): void
	{
		$this->_behavior->lines = [' alice ', 'secret'];
		$this->_behavior->echoChanges = false;

		$this->_behavior->shellApplicationLogin($this->_auth, null);

		$this->assertSame([['alice', 'secret']], $this->_auth->logins);
		$this->assertSame('alice', $this->_behavior->getUsername());
		$this->assertStringContainsString('Login Username: ', $this->_text->getCaptured());
		$this->assertStringContainsString('Password: ', $this->_text->getCaptured());
	}

	public function testLoginWithTheOptionsDoesNotPrompt(): void
	{
		$this->_behavior->setUsername('alice');
		$this->_behavior->setPassword('secret');

		$this->_behavior->shellApplicationLogin($this->_auth, null);

		$this->assertSame([['alice', 'secret']], $this->_auth->logins);
		$this->assertSame([], $this->_behavior->echoes);
		$this->assertStringNotContainsString('Login Username', $this->_text->getCaptured());
	}

	public function testLoginWithTheUserOptionPromptsForThePassword(): void
	{
		$this->_behavior->setUsername('alice');
		$this->_behavior->lines = ['secret'];
		$this->_behavior->echoChanges = false;

		$this->_behavior->shellApplicationLogin($this->_auth, null);

		$this->assertSame([['alice', 'secret']], $this->_auth->logins);
		$this->assertStringContainsString("Login Username: alice\n", $this->_text->getCaptured());
	}

	public function testFailedLoginWritesAnErrorAndExits(): void
	{
		$this->_behavior->setUsername('alice');
		$this->_behavior->setPassword('wrong');

		try {
			$this->_behavior->shellApplicationLogin($this->_auth, null);
			$this->fail('A failed login throws TExitException.');
		} catch (TExitException $e) {
			$this->assertStringContainsString('Could not Authenticate the user', $this->_text->getCaptured());
		}
	}

	public function testThePasswordIsNotKeptAfterLogin(): void
	{
		$this->_behavior->setUsername('alice');
		$this->_behavior->setPassword('secret');
		$this->_behavior->shellApplicationLogin($this->_auth, null);
		$this->_behavior->lines = ['secret'];
		$this->_behavior->echoChanges = false;

		$this->_behavior->shellApplicationLogin($this->_auth, null);

		$this->assertSame([['alice', 'secret'], ['alice', 'secret']], $this->_auth->logins);
		$this->assertSame([false], $this->_behavior->echoes);
	}

	public function testReadPasswordTurnsEchoOffAndPrintsTheNewline(): void
	{
		$this->_behavior->lines = ['secret'];

		$this->expectOutputString("\n");
		$this->assertSame('secret', $this->_behavior->callReadPassword());
		$this->assertSame([false, true], $this->_behavior->echoes);
	}

	public function testReadPasswordWithoutATerminalReadsTheLine(): void
	{
		$this->_behavior->lines = ['secret'];
		$this->_behavior->echoChanges = false;

		$this->expectOutputString('');
		$this->assertSame('secret', $this->_behavior->callReadPassword());
		$this->assertSame([false], $this->_behavior->echoes);
	}

	public function testReadPasswordKeepsSurroundingSpaces(): void
	{
		$this->_behavior->lines = [' pass word '];
		$this->_behavior->echoChanges = false;

		$this->assertSame(' pass word ', $this->_behavior->callReadPassword());
	}

	public function testReadPasswordShownLeavesEchoAlone(): void
	{
		$this->_behavior->lines = ['visible'];

		$this->expectOutputString('');
		$this->assertSame('visible', $this->_behavior->callReadPassword(false));
		$this->assertSame([], $this->_behavior->echoes);
	}

	public function testSetTerminalEchoWithoutATerminalChangesNothing(): void
	{
		$this->_behavior->terminal = false;

		$this->assertFalse($this->_behavior->callSetTerminalEcho(false));
	}

	public function testSetTerminalEchoOnATerminalRunsStty(): void
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('Windows has no stty.');
		}
		$this->_behavior->terminal = true;
		exec('stty echo 2>/dev/null', $output, $code);

		try {
			$this->assertSame($code === 0, $this->_behavior->callSetTerminalEcho(false));
		} finally {
			$this->assertSame($code === 0, $this->_behavior->callSetTerminalEcho(true));
		}
	}

	public function testIsStdInTerminalReadsStandardInput(): void
	{
		$this->assertSame(stream_isatty(STDIN), $this->_behavior->callIsStdInTerminal());
	}

	public function testLoginWithThePasswordOptionPromptsForTheUser(): void
	{
		$this->_behavior->setPassword('secret');
		$this->_behavior->lines = ['alice'];

		$this->_behavior->shellApplicationLogin($this->_auth, null);

		$this->assertSame([['alice', 'secret']], $this->_auth->logins);
		$this->assertSame([], $this->_behavior->echoes);
		$this->assertStringContainsString("Password: **********\n", $this->_text->getCaptured());
	}

	public function testDisabledBehaviorDoesNotLogin(): void
	{
		$this->_behavior->setEnabled(false);

		$this->_behavior->shellApplicationLogin($this->_auth, null);

		$this->assertSame([], $this->_auth->logins);
		$this->assertSame('', $this->_text->getCaptured());
	}

	public function testOutsideAShellApplicationNeitherRegistersOptionsNorLogsIn(): void
	{
		$this->_app->restoreApplication();
		$app = new TTestApplication();
		try {
			$auth = new TShellLoginBehaviorTestAuthManager();
			$auth->attachBehavior('shellLogin', $behavior = new TShellLoginBehaviorTestBehavior());

			$behavior->shellApplicationLogin($auth, null);

			$this->assertSame([], $auth->logins);
		} finally {
			$app->restoreApplication();
			$this->_app = new TTestShellApplication();
		}
	}
}
