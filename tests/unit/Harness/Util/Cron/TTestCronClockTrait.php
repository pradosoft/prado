<?php

/**
 * TTestCronClockTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Util\Cron;

/**
 * TTestCronClockTrait provides fakeable clock and environment seams for the
 * {@see \Prado\Util\Cron\TCronModule} harnesses.
 *
 * It overrides the seams added in 4.4.0 — {@see \Prado\Util\Cron\TCronModule::now()},
 * {@see \Prado\Util\Cron\TCronModule::mt_rand()},
 * {@see \Prado\Util\Cron\TCronModule::php_sapi_name()}, and
 * {@see \Prado\Util\Cron\TCronModule::time_sleep_until()} — behind public fields so tests can drive
 * scheduling deterministically:
 * - {@see $fakeNow} / {@see $fakeMicrotime} pin the time returned by {@see now()};
 * - {@see $fakeRand} pins the request-cron probability draw;
 * - {@see $fakeSapi} pins the server API name;
 * - {@see time_sleep_until()} records each requested timestamp into {@see $sleptUntil} and returns
 *   without sleeping unless {@see $reallySleep} is set;
 * - {@see waitForWake()} records each requested timestamp into {@see $waitedFor} and returns
 *   {@see $wakeSignaled} (simulating an arriving wake kick) without waiting.
 *
 * The seams are reachable through {@see pubTime()}, {@see pubMicrotime()}, {@see pubMtRand()},
 * {@see pubPhpSapiName()}, {@see pubTimeSleepUntil()}, and {@see pubWaitForWake()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TTestCronClockTrait
{
	/** @var ?int when set, {@see now()} reports this Unix second as "now" */
	public ?int $fakeNow = null;

	/** @var ?float when set, {@see now()} reports this Unix microtime as "now" (takes precedence over {@see $fakeNow}) */
	public ?float $fakeMicrotime = null;

	/** @var ?int when set, {@see mt_rand()} returns this instead of a random integer */
	public ?int $fakeRand = null;

	/** @var ?string when set, {@see php_sapi_name()} returns this instead of the real SAPI name */
	public ?string $fakeSapi = null;

	/** @var float[] the timestamps passed to {@see time_sleep_until()}, recorded instead of sleeping */
	public array $sleptUntil = [];

	/** @var bool when true, {@see time_sleep_until()} actually sleeps through the parent seam */
	public bool $reallySleep = false;

	/** @var float[] the timestamps passed to {@see waitForWake()}, recorded instead of waiting */
	public array $waitedFor = [];

	/** @var bool the value {@see waitForWake()} returns, simulating an arriving wake kick */
	public bool $wakeSignaled = false;

	/** @var int[] records each {@see signalWake()} call (the serviced minute timestamp) instead of sending */
	public array $wakeSignals = [];

	public function signalWake(): bool
	{
		$this->wakeSignals[] = $this->now()->getTimestamp();
		return true;
	}

	public function now(): \DateTimeImmutable
	{
		if ($this->fakeMicrotime !== null) {
			return \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6f', $this->fakeMicrotime));
		}
		if ($this->fakeNow !== null) {
			return new \DateTimeImmutable('@' . $this->fakeNow);
		}
		return parent::now();
	}

	// The production TClockTrait::time()/microtime() read the real clock for speed; derive them from the
	// (fakeable) now() here so a pinned clock drives scheduling deterministically.
	public function time(): int
	{
		return $this->now()->getTimestamp();
	}

	public function microtime(): float
	{
		return (float) $this->now()->format('U.u');
	}

	protected function mt_rand(): int
	{
		return $this->fakeRand ?? parent::mt_rand();
	}

	protected function php_sapi_name(): string
	{
		return $this->fakeSapi ?? parent::php_sapi_name();
	}

	protected function time_sleep_until($timestamp): bool
	{
		$this->sleptUntil[] = $timestamp;
		return $this->reallySleep ? parent::time_sleep_until($timestamp) : true;
	}

	public function pubTime(): int
	{
		return $this->time();
	}

	public function pubMicrotime(): float
	{
		return $this->microtime();
	}

	public function pubMtRand(): int
	{
		return $this->mt_rand();
	}

	public function pubPhpSapiName(): string
	{
		return $this->php_sapi_name();
	}

	public function pubTimeSleepUntil($timestamp): bool
	{
		return $this->time_sleep_until($timestamp);
	}

	protected function waitForWake($until): bool
	{
		$this->waitedFor[] = $until;
		if (!$this->wakeSignaled) {
			// Simulate sleeping through to the trigger so the scheduling loop advances under a pinned clock.
			if ($this->fakeMicrotime !== null) {
				$this->fakeMicrotime = max($this->fakeMicrotime, (float) $until);
			}
			if ($this->fakeNow !== null) {
				$this->fakeNow = max($this->fakeNow, (int) $until);
			}
		}
		return $this->wakeSignaled;
	}

	public function pubWaitForWake($until): bool
	{
		return $this->waitForWake($until);
	}
}
