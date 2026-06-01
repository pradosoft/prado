<?php

/**
 * TCallCollectorTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * TCallCollectorTrait records calls made to the methods of a test double.
 *
 * A method records itself by calling {@see collectCall()} as its first statement.
 * The trait reads the caller frame from {@see debug_backtrace()} — limited to two
 * frames, where frame 0 is `collectCall` and frame 1 is the calling method — and
 * stores the calling method name and its arguments. No bespoke per-method recording
 * arrays are needed.
 *
 * Accessors:
 *
 * | method                                | result                                          |
 * |---------------------------------------|-------------------------------------------------|
 * | {@see getCollectedCalls()}            | the full sequential call log                    |
 * | {@see getCollectedCalls('method')}    | the argument lists for each call to `method`    |
 * | {@see getCollectedCallCount()}        | total calls, or calls to a single method        |
 * | {@see getCollectedCall()}             | one recorded call by index                      |
 * | {@see resetCollectedCalls()}          | clears the log                                  |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TCallCollectorTrait
{
	/** @var array<int, array{method: string, args: array}> sequential call log. */
	protected array $_collectedCalls = [];

	/**
	 * Records the calling method and its arguments. Called as the first statement
	 * of the method whose invocation is being recorded.
	 */
	protected function collectCall(): void
	{
		// Frame 0 is collectCall(); frame 1 is the method that called it.
		$frame = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1] ?? null;
		if ($frame === null) {
			return;
		}
		$this->_collectedCalls[] = [
			'method' => $frame['function'],
			'args' => $frame['args'] ?? [],
		];
	}

	/**
	 * Returns the recorded calls.
	 *
	 * @param null|string $method when null, the full sequential log of
	 *   `['method' => string, 'args' => array]`; otherwise the list of argument
	 *   arrays for each call to that method, in call order.
	 * @return array the call log or the per-method argument lists.
	 */
	public function getCollectedCalls(?string $method = null): array
	{
		if ($method === null) {
			return $this->_collectedCalls;
		}
		$args = [];
		foreach ($this->_collectedCalls as $call) {
			if ($call['method'] === $method) {
				$args[] = $call['args'];
			}
		}
		return $args;
	}

	/**
	 * Returns the number of recorded calls, total or for a single method.
	 * @param null|string $method when given, counts only calls to that method.
	 * @return int the call count.
	 */
	public function getCollectedCallCount(?string $method = null): int
	{
		if ($method === null) {
			return count($this->_collectedCalls);
		}
		return count($this->getCollectedCalls($method));
	}

	/**
	 * Returns a single recorded call by its index in the sequential log.
	 * @param int $index the zero-based call index.
	 * @return null|array{method: string, args: array} the recorded call, or null when absent.
	 */
	public function getCollectedCall(int $index): ?array
	{
		return $this->_collectedCalls[$index] ?? null;
	}

	/**
	 * Clears the recorded call log.
	 */
	public function resetCollectedCalls(): void
	{
		$this->_collectedCalls = [];
	}
}
