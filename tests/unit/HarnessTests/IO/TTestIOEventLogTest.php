<?php

/**
 * TTestIOEventLogTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Tests for the {@see TTestIOEventLog} harness — the recorder of IO 'on' events.  Pins the
 * sequence/count/parameter contract the TResource and TStream unit tests rely on to
 * assert lifecycle behavior.
 *
 * @package System.HarnessTests.IO
 */
class TTestIOEventLogTest extends PHPUnit\Framework\TestCase
{
	public function testRecordsResourceLifecycleInOrder(): void
	{
		$r = new TTestResource();
		$log = (new TTestIOEventLog())->listenTo($r, TTestIOEventLog::RESOURCE_EVENTS);

		$res = TTestIOHelper::memoryResource();
		$r->attachResource($res, true);   // onOpen
		$r->closeStream();                  // onFinalize, onClose

		$this->assertSame(['onOpen', 'onFinalize', 'onClose'], $log->events());
		$this->assertSame(1, $log->countOf('onOpen'));
		$this->assertSame([$res], $log->paramsOf('onOpen'));
		$this->assertTrue($log->has('onClose'));
		$this->assertFalse($log->has('onSeek'));
	}

	public function testRecordsStreamSeekAndEof(): void
	{
		$s = TTestIOHelper::dataStream('abc');
		$log = (new TTestIOEventLog())->listenTo($s, TTestIOEventLog::STREAM_EVENTS);

		$s->seek(2);                 // onSeek with offset 2
		$s->read(1);                 // 'c'
		$s->read(1);                 // '' at EOF -> onEndOfFile

		$this->assertSame([2], $log->paramsOf('onSeek'));
		$this->assertSame(2, $log->lastParam('onSeek'));
		$this->assertTrue($log->has('onEndOfFile'));
		$s->close();
	}

	public function testCountAndReset(): void
	{
		$s = TTestIOHelper::dataStream('abcdef');
		$log = (new TTestIOEventLog())->listenTo($s, ['onSeek']);
		$s->seek(1);
		$s->seek(2);
		$s->seek(3);
		$this->assertSame(3, $log->countOf('onSeek'));
		$log->reset();
		$this->assertSame([], $log->all());
		$this->assertSame(0, $log->countOf('onSeek'));
		$s->close();
	}

	public function testAbsentEventReportsEmpty(): void
	{
		$log = new TTestIOEventLog();
		$this->assertFalse($log->has('onClose'));
		$this->assertSame(0, $log->countOf('onClose'));
		$this->assertSame([], $log->paramsOf('onClose'));
		$this->assertNull($log->lastParam('onClose'));
	}
}
