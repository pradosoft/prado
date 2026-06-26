<?php

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Util\Cron\TClosureCronTask;
use Prado\Util\Cron\TCronModule;
use Prado\Exceptions\TConfigurationException;

class TClosureCronTaskTest extends PHPUnit\Framework\TestCase
{
	public function testExecuteRunsClosure()
	{
		$flag = new stdClass();
		$flag->v = null;
		$task = new TClosureCronTask(function ($t, $cron) use ($flag) {
			$flag->v = 'ran';
			return 7;
		});
		$task->setName('cct_run');
		self::assertEquals(7, $task->execute(null));
		self::assertEquals('ran', $flag->v);
	}

	public function testExecutePassesTaskAndCron()
	{
		$captured = [];
		$task = new TClosureCronTask(function ($t, $cron) use (&$captured) {
			$captured = [$t, $cron];
		});
		$task->setName('cct_args');
		$cron = new TCronModule();
		$task->execute($cron);
		self::assertSame($task, $captured[0]);
		self::assertSame($cron, $captured[1]);
	}

	public function testExecuteWithoutClosureThrows()
	{
		$task = new TClosureCronTask();
		$task->setName('cct_none');
		self::expectException(TConfigurationException::class);
		$task->execute(null);
	}

	public function testSetClosureRequiresClosure()
	{
		$task = new TClosureCronTask();
		self::expectException(TConfigurationException::class);
		$task->setClosure('not a closure');
	}

	public function testGetSetClosure()
	{
		$task = new TClosureCronTask();
		self::assertNull($task->getClosure());
		$task->setClosure($c = fn () => 1);
		self::assertSame($c, $task->getClosure());
	}

	public function testStoreRawDefaultsFalse()
	{
		$task = new TClosureCronTask();
		self::assertFalse($task->getStoreRaw());
	}

	public function testSetStoreRawValues()
	{
		$task = new TClosureCronTask();
		$task->setStoreRaw('true');
		self::assertTrue($task->getStoreRaw());
		$task->setStoreRaw('false');
		self::assertFalse($task->getStoreRaw());
		$task->setStoreRaw(true);
		self::assertTrue($task->getStoreRaw());
		$task->setStoreRaw('Debug');
		self::assertEquals(TApplicationMode::Debug, $task->getStoreRaw());
		$task->setStoreRaw('debug');
		self::assertEquals(TApplicationMode::Debug, $task->getStoreRaw());
	}

	public function testSecurityManagerIDDefaultsToApplication()
	{
		$task = new TClosureCronTask();
		self::assertNull($task->getSecurityManagerID());
		$appSm = Prado::getApplication()->getSecurityManager();
		self::assertSame($appSm, $task->getSecurityManager());

		$task->setSecurityManagerID('');
		self::assertSame($appSm, $task->getSecurityManager());
	}

	public function testSecurityManagerIDInvalidThrows()
	{
		$task = new TClosureCronTask();
		$task->setSecurityManagerID('cct_nonexistent_module');
		self::expectException(TConfigurationException::class);
		$task->getSecurityManager();
	}

	public function testEncryptedRoundTrip()
	{
		$base = 5;
		$task = new TClosureCronTask(fn ($t, $c) => $base + 1);
		$task->setName('cct_enc');
		self::assertFalse($task->getStoreRaw());

		$restored = unserialize(serialize($task));
		self::assertInstanceOf(TClosureCronTask::class, $restored);
		self::assertEquals(6, $restored->getClosure()(null, null));
	}

	public function testUnencryptedRoundTrip()
	{
		$task = new TClosureCronTask(fn ($t, $c) => 'plain');
		$task->setName('cct_plain');
		$task->setStoreRaw(true);

		$restored = unserialize(serialize($task));
		self::assertEquals('plain', $restored->getClosure()(null, null));
	}

	public function testDebugEncryptRoundTrip()
	{
		$task = new TClosureCronTask(fn ($t, $c) => 'dbg');
		$task->setName('cct_dbg');
		$task->setStoreRaw('Debug');

		$restored = unserialize(serialize($task));
		self::assertEquals('dbg', $restored->getClosure()(null, null));
	}

	public function testTamperedRawPayloadIsRejected()
	{
		// A raw (unencrypted) payload is HMAC-signed, so tampering is detected on decode rather than
		// eval()'d — the closure-RCE guard. (The signer is configured before signing, see encodeClosure.)
		$task = new TClosureCronTask(fn ($t, $c) => 'legit');
		$task->setName('cct_tamper');
		$task->setStoreRaw(true);
		$blob = serialize($task);
		self::assertStringContainsString('legit', $blob); // raw payload is readable
		$tampered = str_replace('legit', 'pwned', $blob);
		self::expectException(\Laravel\SerializableClosure\Exceptions\InvalidSignatureException::class);
		unserialize($tampered)->getClosure();
	}

	public function testRoundTripPreservesTaskProperties()
	{
		$task = new TClosureCronTask(fn ($t, $c) => 1);
		$task->setName('cct_props');
		$task->setSchedule('0 0 * * *');

		$restored = unserialize(serialize($task));
		self::assertEquals('cct_props', $restored->getName());
		self::assertEquals('0 0 * * *', $restored->getSchedule());
	}

	public function testDataDefaultsEmpty()
	{
		$task = new TClosureCronTask();
		self::assertSame([], $task->getData());
		self::assertCount(0, $task);
	}

	public function testSetData()
	{
		$task = new TClosureCronTask();
		self::assertSame($task, $task->setData(['a' => 1, 'b' => 2])); // chainable
		self::assertSame(['a' => 1, 'b' => 2], $task->getData());

		$task->setData(new ArrayObject(['x' => 9])); // Traversable collected to array
		self::assertSame(['x' => 9], $task->getData());

		$task->setData(null); // null clears
		self::assertSame([], $task->getData());
	}

	public function testArrayAccess()
	{
		$task = new TClosureCronTask();
		$task['accountId'] = 42;
		self::assertTrue(isset($task['accountId']));
		self::assertSame(42, $task['accountId']);
		self::assertNull($task['missing']);
		self::assertFalse(isset($task['missing']));

		$task[] = 'appended';
		self::assertSame('appended', $task[0]);

		unset($task['accountId']);
		self::assertFalse(isset($task['accountId']));
		self::assertSame([0 => 'appended'], $task->getData());
	}

	public function testCountableAndIterable()
	{
		$task = new TClosureCronTask();
		$task->setData(['a' => 1, 'b' => 2, 'c' => 3]);
		self::assertCount(3, $task);

		$seen = [];
		foreach ($task as $k => $v) {
			$seen[$k] = $v;
		}
		self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $seen);
	}

	public function testDataRoundTripSerialization()
	{
		$task = new TClosureCronTask(fn ($t, $c) => 1);
		$task->setName('cct_data');
		$task['accountId'] = 42;
		$task['tags'] = ['x', 'y'];

		$restored = unserialize(serialize($task));
		self::assertSame(42, $restored['accountId']);
		self::assertSame(['x', 'y'], $restored['tags']);
		self::assertCount(2, $restored);
	}

	public function testEmptyDataRoundTrips()
	{
		$task = new TClosureCronTask(fn ($t, $c) => 1);
		$task->setName('cct_nodata');
		$restored = unserialize(serialize($task));
		self::assertSame([], $restored->getData());
	}

	public function testClosureReadsAndMutatesData()
	{
		$task = new TClosureCronTask(function ($t, $cron) {
			$t['runs'] = ($t['runs'] ?? 0) + 1;
			return $t['accountId'];
		});
		$task->setName('cct_data_exec');
		$task['accountId'] = 7;

		self::assertSame(7, $task->execute(null));
		self::assertSame(1, $task['runs']);
		self::assertSame(7, $task->execute(null));
		self::assertSame(2, $task['runs']);
	}
}
