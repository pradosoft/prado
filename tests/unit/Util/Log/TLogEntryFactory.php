<?php

/**
 * TLogEntryFactory class file.
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Log;

use Prado\Util\Log\TLogger;

/**
 * Builds log entries in the form a route's processLogs() receives them.
 */
class TLogEntryFactory
{
	/**
	 * Returns one log entry with its 'delta' and 'total' timing.
	 * @param mixed $message the logged message.
	 * @param int $level the TLogger level.
	 * @param string $category the category.
	 * @param float|string $delta the time to the next entry, or '?' for the last.
	 * @param float $total the time since the request started.
	 * @param ?array $traces the call traces.
	 * @param ?int $pid the process ID; null for this process.
	 * @param ?float $time the timestamp; null for 2026-01-02 03:04:05.25 UTC.
	 * @return array the log entry.
	 */
	public static function entry(mixed $message = 'message', int $level = TLogger::INFO, string $category = 'Test\Category', float|string $delta = 0.5, float $total = 1.5, ?array $traces = null, ?int $pid = null, ?float $time = null): array
	{
		return [
			TLogger::LOG_MESSAGE => $message,
			TLogger::LOG_LEVEL => $level,
			TLogger::LOG_CATEGORY => $category,
			TLogger::LOG_TIME => $time ?? 1767323045.25,
			TLogger::LOG_MEMORY => 1024,
			TLogger::LOG_CONTROL => null,
			TLogger::LOG_TRACES => $traces,
			TLogger::LOG_PID => $pid ?? getmypid(),
			'delta' => $delta,
			'total' => $total,
		];
	}
}
