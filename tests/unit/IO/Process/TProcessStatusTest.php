<?php

use Prado\IO\Process\TProcessStatus;

class TProcessStatusTest extends PHPUnit\Framework\TestCase
{
	public function testReadsAllFieldsFromStatusArray()
	{
		$s = new TProcessStatus([
			'command' => '/bin/true', 'pid' => 4321, 'running' => false,
			'signaled' => true, 'stopped' => false, 'exitcode' => 2, 'termsig' => 9, 'stopsig' => 0,
		]);
		self::assertSame('/bin/true', $s->getCommand());
		self::assertSame(4321, $s->getPid());
		self::assertFalse($s->getRunning());
		self::assertTrue($s->getSignaled());
		self::assertFalse($s->getStopped());
		self::assertSame(2, $s->getExitCode());
		self::assertSame(9, $s->getTermSig());
		self::assertSame(0, $s->getStopSig());
	}

	public function testStoppedAndStopSig()
	{
		$s = new TProcessStatus([
			'command' => 'sleep', 'pid' => 10, 'running' => true,
			'signaled' => false, 'stopped' => true, 'exitcode' => -1, 'termsig' => 0, 'stopsig' => 19,
		]);
		self::assertTrue($s->getRunning());
		self::assertTrue($s->getStopped());
		self::assertSame(19, $s->getStopSig());
	}

	public function testMissingKeysFallBackToDefaults()
	{
		$s = new TProcessStatus([]);   // every proc_get_status key absent
		self::assertSame('', $s->getCommand());
		self::assertSame(0, $s->getPid());
		self::assertFalse($s->getRunning());
		self::assertFalse($s->getSignaled());
		self::assertFalse($s->getStopped());
		self::assertSame(-1, $s->getExitCode());
		self::assertSame(0, $s->getTermSig());
		self::assertSame(0, $s->getStopSig());
	}

	public function testCoercesLooselyTypedValues()
	{
		// proc_get_status values are normalized to the declared field types.
		$s = new TProcessStatus(['pid' => '777', 'running' => 1, 'exitcode' => '5']);
		self::assertSame(777, $s->getPid());
		self::assertTrue($s->getRunning());
		self::assertSame(5, $s->getExitCode());
	}
}
