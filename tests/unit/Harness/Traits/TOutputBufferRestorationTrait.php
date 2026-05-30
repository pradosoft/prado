<?php

/**
 * TOutputBufferRestorationTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;

/**
 * TOutputBufferRestorationTrait keeps PHPUnit's output-buffer depth balanced
 * across both the test method and the fixture-class lifecycle, and folds in
 * Prado's one-shot framework bootstrap so every test class starts from a
 * clean state.
 *
 * ## Why it exists
 *
 * Several Prado classes call `ob_start()` during their own initialization —
 * {@see \Prado\Web\THttpResponse::init()} is the canonical example — and their
 * matching `ob_end_flush()` only runs at full request shutdown.  A unit test
 * never reaches that shutdown, so the buffer the framework opened is still
 * open when the test method (or the whole class) returns.  PHPUnit compares
 * the active output-buffer level at start with the level at end, and any
 * imbalance marks the test as risky ("Test code or tested code did not close
 * its own output buffers").
 *
 * ## What the trait provides
 *
 * - **Per-method save/restore** ({@see saveOutputBufferLevel()} /
 *   {@see restoreOutputBufferLevel()}) — call from `setUp()`/`tearDown()` so
 *   each test method enters and exits at the same OB depth.
 * - **Per-class save/restore** ({@see setUpBeforeClass()} /
 *   {@see tearDownAfterClass()}) — `setUpBeforeClass()` captures the OB
 *   depth, runs the one-shot Prado bootstrap that would otherwise open a
 *   buffer inside an arbitrary test (eagerly initializes the response via
 *   {@see \Prado\Prado::getApplication()} so `THttpResponse::init()` fires
 *   here), then closes any buffers that opened during bootstrap so every
 *   test starts at the captured baseline.  {@see tearDownAfterClass()}
 *   restores the depth after the last test runs.
 * - **{@see restoreClassOutputBufferLevel()}** — exposed so a using class
 *   that performs *additional* eager init can balance immediately after,
 *   instead of waiting until `tearDownAfterClass()`.
 *
 * Restoration discards rather than flushes, since the test environment has no
 * use for whatever the framework wrote.
 *
 * ## Usage — straight inclusion
 *
 * With no override of `setUpBeforeClass()`, the trait performs the bootstrap
 * automatically.  Each test method just needs to bracket itself with the
 * per-method save/restore:
 *
 * ```php
 * class MyIntegrationTest extends PHPUnit\Framework\TestCase
 * {
 *     use TOutputBufferRestorationTrait;
 *
 *     protected function setUp(): void
 *     {
 *         $this->saveOutputBufferLevel();
 *     }
 *
 *     protected function tearDown(): void
 *     {
 *         $this->restoreOutputBufferLevel();
 *     }
 * }
 * ```
 *
 * ## Usage — aliasing when the class needs its own setUpBeforeClass
 *
 * If the class needs additional class-level fixture work, alias the trait's
 * `setUpBeforeClass()` via PHP's `use ... as ...` syntax so the trait's
 * bootstrap can still run first.  Call the alias *before* any further eager
 * init, and call {@see restoreClassOutputBufferLevel()} after to balance
 * anything that opened a buffer:
 *
 * ```php
 * class MyIntegrationTest extends PHPUnit\Framework\TestCase
 * {
 *     use TOutputBufferRestorationTrait {
 *         setUpBeforeClass as outputSetup;
 *     }
 *
 *     public static function setUpBeforeClass(): void
 *     {
 *         static::outputSetup();                    // capture + Prado bootstrap
 *         // ... additional eager init that may open buffers ...
 *         static::restoreClassOutputBufferLevel();  // close them immediately
 *     }
 * }
 * ```
 *
 * Aliasing `tearDownAfterClass()` follows the same pattern when the class
 * also defines its own.  PHPUnit then calls the using class's overrides;
 * the trait's bodies are reached via the aliases.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TOutputBufferRestorationTrait
{
	/**
	 * Per-test OB depth captured by {@see saveOutputBufferLevel()}, used as
	 * the floor that {@see restoreOutputBufferLevel()} restores down to.
	 * @var int
	 */
	private int $_savedOutputBufferLevel = 0;

	/**
	 * Class-wide OB depth captured by {@see setUpBeforeClass()}, used as the
	 * floor that {@see restoreClassOutputBufferLevel()} and the trait's
	 * {@see tearDownAfterClass()} restore down to.
	 * @var int
	 */
	private static int $_savedClassOutputBufferLevel = 0;

    /**
     * Records the current `ob_get_level()` as the baseline that
     * {@see restoreOutputBufferLevel()} will restore down to.  Call once from
     * the test class's `setUp()` (or equivalent fixture hook) before any code
     * under test runs.
     */
    protected static function saveClassOutputBufferLevel(): void
    {
        self::$_savedClassOutputBufferLevel = ob_get_level();
    }

    /**
     * Closes any output buffers above the class-wide baseline captured by
     * {@see setUpBeforeClass()}.  A using class with eager initialization in
     * its own `setUpBeforeClass()` should call this right after the init so
     * the buffers don't stay open through the test methods.
     */
    protected static function restoreClassOutputBufferLevel(): void
    {
        while (ob_get_level() > self::$_savedClassOutputBufferLevel) {
            ob_end_clean();
        }
    }

	/**
	 * Records the current `ob_get_level()` as the baseline that
	 * {@see restoreOutputBufferLevel()} will restore down to.  Call once from
	 * the test class's `setUp()` (or equivalent fixture hook) before any code
	 * under test runs.
	 */
	protected function saveOutputBufferLevel(): void
	{
		$this->_savedOutputBufferLevel = ob_get_level();
	}

	/**
	 * Closes any output buffers opened after {@see saveOutputBufferLevel()}
	 * was called, discarding their contents, until the depth matches the
	 * saved baseline.  Call from `tearDown()` (or equivalent).  Buffers below
	 * the baseline — including PHPUnit's own — are not touched.
	 */
	protected function restoreOutputBufferLevel(): void
	{
		while (ob_get_level() > $this->_savedOutputBufferLevel) {
			ob_end_clean();
		}
	}

	/**
	 * Records the OB depth and eagerly initializes the Prado response so
	 * `THttpResponse::init()`'s one-shot `ob_start()` fires here — under the
	 * trait's control — rather than inside whichever test method happens to
	 * be the first to call `getResponse()`.  The buffer opened during the
	 * bootstrap is immediately closed so every test starts at the captured
	 * baseline.
	 *
	 * A using class with its own `setUpBeforeClass()` should alias this
	 * method (see the class docblock) so the bootstrap still runs.
	 */
	public static function setUpBeforeClass(): void
	{
		self::saveClassOutputBufferLevel();
		// Eagerly initialize THttpResponse so its one-time `ob_start()` (from
		// THttpResponse::init() when buffering is enabled) fires here, under
		// our control, rather than inside whichever test method happens to be
		// the first to call getResponse().  The application reference is used
		// only locally — the using class manages its own $app if it needs one.
		Prado::getApplication()?->getResponse();
		// Close the buffer that init() opened, so every test starts at the
		// class-wide OB baseline captured above.
		self::restoreClassOutputBufferLevel();
	}

	/**
	 * Restores the OB depth to the class-wide baseline captured by
	 * {@see setUpBeforeClass()}.  Invoked automatically by PHPUnit after the
	 * last test method runs; catches anything that opened a buffer during
	 * fixture bootstrap and was not balanced earlier.
	 */
	public static function tearDownAfterClass(): void
	{
		self::restoreClassOutputBufferLevel();
	}
}
