<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\Actions\THelpAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\TTestShellApplication;

/**
 * Tests for {@see THelpAction}.
 */
class THelpActionTest extends TestCase
{
	private TTestCapturingTextWriter $_text;
	private THelpAction $_action;

	protected function setUp(): void
	{
		$this->_text = new TTestCapturingTextWriter();
		$this->_action = new THelpAction();
		$this->_action->setWriter(new TShellWriter($this->_text));
	}

	private function runInShellApplication(array $args): bool
	{
		$app = new TTestShellApplication();
		try {
			$app->addShellActionClass(TFlushCachesAction::class);
			$app->addShellActionClass(THelpAction::class);
			return $this->_action->actionIndex($args);
		} finally {
			$app->restoreApplication();
		}
	}

	public function testActionIndexRendersTheNamedCommand(): void
	{
		$this->assertTrue($this->runInShellApplication(['help', 'CACHE']));

		$this->assertStringContainsString('cache/flush-all', $this->_text->getCaptured());
	}

	public function testActionIndexIgnoresAMethodSuffix(): void
	{
		$this->assertTrue($this->runInShellApplication(['help', 'cache/flush']));

		$this->assertStringContainsString('cache/flush-all', $this->_text->getCaptured());
	}

	public function testActionIndexDoesNotMatchACommandByPrefix(): void
	{
		$this->assertFalse($this->runInShellApplication(['help', 'cachexyz']));

		$this->assertSame('', $this->_text->getCaptured());
	}

	public function testActionIndexWithoutAShellApplicationReturnsFalse(): void
	{
		$app = new TTestApplication();
		try {
			$this->assertFalse($this->_action->actionIndex(['help']));
			$this->assertFalse($this->_action->actionIndex(['help', 'cache']));
		} finally {
			$app->restoreApplication();
		}
	}
}
