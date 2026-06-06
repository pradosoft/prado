<?php

use Prado\Exceptions\TIOException;
use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * Unit tests for {@see \Prado\IO\TStream}, the PSR-7 byte-stream wrapper.
 *
 * Built on the IO harness: {@see TTestIOHelper} opens resources/streams (including a
 * non-seekable pipe) and scratch state, and {@see TTestIOEventLog} records the onSeek /
 * onEndOfFile events.  The named-constructor and coercion tests call {@see TStream}
 * directly, since those factories are themselves under test.
 */
class TStreamTest extends PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	public function testImplementsPsr7()
	{
		$s = TStream::fromMemory();
		self::assertInstanceOf(StreamInterface::class, $s);
		$s->close();
	}

	public function testFromStringContentsAndToString()
	{
		$s = TStream::fromString('hello world');
		self::assertSame(11, $s->getSize());
		self::assertSame('hello world', (string) $s);   // __toString rewinds
		self::assertSame('hello world', (string) $s);   // repeatable
		$s->close();
	}

	public function testReadWriteTellSeekEof()
	{
		$s = TStream::fromMemory();
		self::assertSame(5, $s->write('abcde'));
		self::assertSame(5, $s->tell());
		$s->seek(0);
		self::assertSame(0, $s->tell());
		self::assertSame('ab', $s->read(2));
		self::assertSame('cde', $s->getContents());
		self::assertSame('', $s->read(4));
		self::assertTrue($s->eof());
		$s->close();
	}

	public function testCapabilities()
	{
		$r = TStream::fromFile('php://temp', 'rb');
		self::assertTrue($r->isReadable());
		self::assertFalse($r->isWritable());
		self::assertTrue($r->isSeekable());
		$r->close();

		$rw = TStream::fromMemory('r+b');
		self::assertTrue($rw->isReadable());
		self::assertTrue($rw->isWritable());
		$rw->close();
	}

	public function testWriteToReadOnlyThrows()
	{
		$s = TStream::fromFile('php://temp', 'rb');
		self::expectException(\RuntimeException::class);
		try {
			$s->write('x');
		} finally {
			$s->close();
		}
	}

	public function testReadFromWriteOnlyThrows()
	{
		// php://output is write-only
		$s = TStream::fromFile('php://output', 'wb');
		self::assertFalse($s->isReadable());
		self::expectException(\RuntimeException::class);
		$s->read(1);
	}

	public function testNegativeLengthThrows()
	{
		$s = TStream::fromString('abc');
		self::expectException(\RuntimeException::class);
		try {
			$s->read(-1);
		} finally {
			$s->close();
		}
	}

	public function testZeroLengthReadReturnsEmpty()
	{
		$s = TStream::fromString('abc');
		self::assertSame('', $s->read(0));
		$s->close();
	}

	public function testGetSizeInvalidatedOnWrite()
	{
		$s = TStream::fromString('abc');
		self::assertSame(3, $s->getSize());
		$s->seek(0, SEEK_END);
		$s->write('de');
		self::assertSame(5, $s->getSize());
		$s->close();
	}

	public function testFromFileFailureThrows()
	{
		self::expectException(TIOException::class);
		TStream::fromFile('/no/such/path/really/nope.txt', 'rb');
	}

	public function testFromResource()
	{
		$res = TTestIOHelper::dataResource('xyz');
		$s = TStream::fromResource($res, false);
		self::assertFalse($s->getOwnsResource());
		$s->seek(0);
		self::assertSame('xyz', $s->getContents());
		$s->close();
		self::assertTrue(is_resource($res), 'Borrowed resource stays open after close.');
		TTestIOHelper::closeAny($res);
	}

	public function testForCoercion()
	{
		$fromString = TStream::for('data');
		self::assertInstanceOf(StreamInterface::class, $fromString);
		self::assertSame('data', (string) $fromString);

		$already = TStream::fromMemory();
		self::assertSame($already, TStream::for($already), 'StreamInterface returned as-is.');
		$already->close();

		$res = TTestIOHelper::tempResource();
		$fromRes = TStream::for($res);
		self::assertInstanceOf(StreamInterface::class, $fromRes);
		$fromRes->close();
		TTestIOHelper::closeAny($res);

		$empty = TStream::for(null);
		self::assertSame(0, $empty->getSize());
		$empty->close();
	}

	public function testDetachLeavesResourceAndDisablesOps()
	{
		$s = TStream::fromString('abc');
		$res = $s->detach();
		self::assertTrue(is_resource($res));
		self::assertFalse($s->isReadable());
		self::assertFalse($s->isWritable());
		self::assertFalse($s->isSeekable());
		TTestIOHelper::closeAny($res);
	}

	public function testFilters()
	{
		self::assertTrue(TStream::filterExists('string.rot13'));
		self::assertContains('string.rot13', TStream::getAvailableFilters());

		$s = TStream::fromMemory();
		$filter = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		self::assertNotFalse($filter);
		self::assertCount(1, $s->getFilters());
		$s->write('hello');
		$s->seek(0);
		self::assertSame('uryyb', $s->getContents()); // rot13('hello')
		self::assertTrue($s->removeFilter($filter));
		self::assertCount(0, $s->getFilters());
		$s->close();
	}

	public function testDyReadBehaviorIntercepts()
	{
		$s = TStream::fromString('hello');
		$s->attachBehavior('upper', new TUpperReadBehavior());
		self::assertSame('HELLO', $s->read(5));
		$s->close();
	}

	public function testSerializationZapsResource()
	{
		$s = TStream::fromString('persisted');
		$data = serialize($s);              // must not fatal on the resource handle
		$revived = unserialize($data);
		self::assertInstanceOf(TStream::class, $revived);
		self::assertFalse($revived->isOpen(), 'Revived stream has no live handle.');
		self::assertFalse($revived->isReadable());
		self::assertNull($revived->getSize());
		$s->close();
	}

	public function testCloneIsNonOwningView()
	{
		$s = TStream::fromString('abc');
		$copy = clone $s;
		self::assertFalse($copy->getOwnsResource(), 'Clone must not own the shared handle.');
		self::assertSame([], $copy->getFilters(), 'Clone does not inherit filter handles.');
		// Closing the non-owning clone must leave the original usable.
		$copy->close();
		$s->seek(0);
		self::assertSame('abc', $s->getContents());
		$s->close();
	}

	public function testEofEvent()
	{
		$s = TStream::fromString('ab');
		$log = (new TTestIOEventLog())->listenTo($s, ['onEndOfFile']);
		$s->read(2);          // consumes all
		self::assertSame(0, $log->countOf('onEndOfFile'), 'No EOF until a read past the end.');
		$s->read(2);          // now at EOF, returns ''
		self::assertSame(1, $log->countOf('onEndOfFile'));
		$s->close();
	}

	public function testFromMemoryAndFromTemp()
	{
		$m = TStream::fromMemory();
		self::assertSame('php://memory', $m->getURI());
		self::assertTrue($m->isReadable() && $m->isWritable() && $m->isSeekable());
		$m->close();

		$t = TStream::fromTemp(1024);
		$t->write('temp');
		$t->seek(0);
		self::assertSame('temp', $t->getContents());
		$t->close();
	}

	public function testFromResourceOwnsTrueClosesOnClose()
	{
		$res = TTestIOHelper::tempResource();
		$s = TStream::fromResource($res, true);
		self::assertTrue($s->getOwnsResource());
		$s->close();
		self::assertFalse(is_resource($res), 'owns=true closes the resource.');
	}

	public function testGetSizeNullWhenClosed()
	{
		$s = TStream::fromString('abc');
		$s->close();
		self::assertNull($s->getSize());
	}

	public function testTellThrowsWhenDetached()
	{
		$s = TStream::fromString('abc');
		$s->detach();
		self::expectException(\RuntimeException::class);
		$s->tell();
	}

	public function testGetContentsThrowsWhenNonReadable()
	{
		$s = TStream::fromFile('php://output', 'wb');
		self::expectException(\RuntimeException::class);
		$s->getContents();
	}

	public function testNonSeekableStream()
	{
		// php://output is write-only and non-seekable.
		$s = TStream::fromFile('php://output', 'wb');
		self::assertFalse($s->isSeekable());
		try {
			$s->seek(0);
			self::fail('seek() must throw on a non-seekable stream.');
		} catch (\RuntimeException $e) {
			self::assertTrue(true);
		}
	}

	public function testNonSeekableReadablePipe()
	{
		// A popen pipe is readable but not seekable; read it through as one pass.
		$pipe = TTestIOHelper::pipeResource('streamed-bytes');
		$s = TStream::fromResource($pipe, false);   // borrowed: harness pcloses it
		self::assertTrue($s->isReadable());
		self::assertFalse($s->isSeekable());
		self::assertSame('streamed-bytes', $s->getContents());
		$s->close();
		TTestIOHelper::closeAny($pipe);
	}

	public function testPrependAndRemoveFilter()
	{
		$s = TStream::fromMemory();
		$f = $s->prependFilter('string.rot13', STREAM_FILTER_WRITE);
		self::assertNotFalse($f);
		self::assertCount(1, $s->getFilters());
		self::assertFalse($s->removeFilter('not-a-resource'));
		self::assertTrue($s->removeFilter($f));
		self::assertCount(0, $s->getFilters());
		$s->close();
	}

	public function testRemoveFilterByName()
	{
		$s = TStream::fromMemory();
		$s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		$s->appendFilter('string.tolower', STREAM_FILTER_WRITE);
		self::assertCount(2, $s->getFilters());
		self::assertTrue($s->removeFilter('string.rot13'), 'Removes a filter by name.');
		self::assertSame(['string.tolower'], $s->getFilterNames());
		self::assertFalse($s->removeFilter('string.rot13'), 'Removing an absent name returns false.');
		$s->close();
	}

	public function testRemoveFilterByNameRemovesFirstMatch()
	{
		$s = TStream::fromMemory();
		$s->appendFilter('string.rot13', STREAM_FILTER_WRITE);   // index 0 (front)
		$keep = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);   // index 1
		self::assertCount(2, $s->getFilters());
		self::assertTrue($s->removeFilter('string.rot13'));
		self::assertCount(1, $s->getFilters());
		self::assertSame($keep, $s->getFilters()[0], 'The second same-named filter remains.');
		$s->close();
	}

	public function testAppendFilterOnDetachedReturnsFalse()
	{
		$s = TStream::fromString('x');
		$s->detach();
		self::assertFalse($s->appendFilter('string.rot13'));
	}

	public function testFilterLookupByNameAndHandle()
	{
		$s = TStream::fromMemory();
		self::assertFalse($s->hasFilter('string.rot13'));
		self::assertNull($s->getFilterIndex('string.rot13'));

		$a = $s->appendFilter('string.tolower', STREAM_FILTER_WRITE);
		$b = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);

		// by name
		self::assertTrue($s->hasFilter('string.rot13'));
		self::assertTrue($s->hasFilter('string.tolower'));
		self::assertFalse($s->hasFilter('zlib.inflate'));
		self::assertSame(0, $s->getFilterIndex('string.tolower'));
		self::assertSame(1, $s->getFilterIndex('string.rot13'));
		self::assertNull($s->getFilterIndex('zlib.inflate'));

		// by handle
		self::assertTrue($s->hasFilter($a));
		self::assertSame(0, $s->getFilterIndex($a));
		self::assertSame(1, $s->getFilterIndex($b));

		self::assertSame(['string.tolower', 'string.rot13'], $s->getFilterNames());
		$s->close();
	}

	public function testFilterIndexReflectsPrependOrderAndRemoval()
	{
		$s = TStream::fromMemory();
		$appended = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		$s->prependFilter('string.tolower', STREAM_FILTER_WRITE);

		// prepend goes to the front (index 0)
		self::assertSame(0, $s->getFilterIndex('string.tolower'));
		self::assertSame(1, $s->getFilterIndex('string.rot13'));

		$s->removeFilter($appended);
		self::assertFalse($s->hasFilter('string.rot13'));
		self::assertSame(0, $s->getFilterIndex('string.tolower'));
		self::assertSame(['string.tolower'], $s->getFilterNames());
		$s->close();
	}

	public function testOnSeekEventFires()
	{
		$s = TStream::fromString('abcdef');
		$log = (new TTestIOEventLog())->listenTo($s, ['onSeek']);
		$s->seek(2);
		$s->rewind();
		self::assertSame([2, 0], $log->paramsOf('onSeek'));
		$s->close();
	}

	public function testDyWriteBehaviorIntercepts()
	{
		$s = TStream::fromMemory();
		$s->attachBehavior('doubler', new TDoubleWriteBehavior());
		self::assertSame(10, $s->write('abcde'), 'dyWrite doubled the reported count.');
		$s->close();
	}

	public function testForThrowsOnUnusableValue()
	{
		self::expectException(\Prado\Exceptions\TIOException::class);
		TStream::for([1, 2, 3]);
	}

	public function testReattachRecomputesCapabilitiesAndClosesPrior()
	{
		$ro = TTestIOHelper::tempResource('rb');      // read-only
		$rw = TTestIOHelper::tempResource('r+b');     // read-write
		$s = TStream::fromResource($ro, true);
		self::assertFalse($s->isWritable());
		$s->attachResource($rw, true);
		self::assertFalse(is_resource($ro), 'prior owned handle closed on re-attach');
		self::assertTrue($s->isWritable(), 'capabilities recomputed after re-attach');
		$s->close();
	}

	public function testCapabilityOverrideSeamGovernsSeek()
	{
		// A subclass override of isSeekable() must govern seek() even though the
		// underlying handle is really seekable (the self-encapsulation seam).
		$s = new TTestStream(TTestIOHelper::memoryResource());
		$s->write('payload');
		$s->forceSeekable = false;
		self::assertFalse($s->isSeekable());
		$this->expectException(\RuntimeException::class);
		try {
			$s->seek(0);
		} finally {
			$s->close();
		}
	}

	public function testCapabilityOverrideSeamGovernsReadAndWrite()
	{
		$s = new TTestStream(TTestIOHelper::memoryResource());
		$s->forceReadable = false;
		$s->forceWritable = false;
		$readThrew = false;
		try {
			$s->read(1);
		} catch (\RuntimeException $e) {
			$readThrew = true;
		}
		self::assertTrue($readThrew, 'read() honors the overridden readable capability.');
		$this->expectException(\RuntimeException::class);
		try {
			$s->write('x');
		} finally {
			$s->close();
		}
	}

	public function testCapabilityDyHooksVetoReadAndWrite()
	{
		// A behavior's dyIsReadable/dyIsWritable hooks force the capability false, so the
		// PSR is*() methods report false and read()/write() throw.
		$s = TStream::fromMemory();
		$s->attachBehavior('noio', new TNoReadWriteBehavior());
		self::assertFalse($s->isReadable());
		self::assertFalse($s->isWritable());
		self::assertTrue($s->isSeekable(), 'Other capabilities are unaffected.');

		$threw = false;
		try {
			$s->write('x');
		} catch (\RuntimeException $e) {
			$threw = true;
		}
		self::assertTrue($threw, 'write() throws when the writable capability is vetoed.');

		$s->detachBehavior('noio');
		self::assertTrue($s->isReadable());
		self::assertTrue($s->isWritable());
		$s->close();
	}
}

/**
 * Behavior that upper-cases bytes as they are read, via the dyRead hook.
 */
class TUpperReadBehavior extends \Prado\Util\TBehavior
{
	public function dyRead($data, $length, $chain = null)
	{
		$data = strtoupper($data);
		if ($chain !== null) {
			return $chain->dyRead($data, $length);
		}
		return $data;
	}
}

/**
 * Behavior that doubles the reported write byte-count, via the dyWrite hook.
 */
class TDoubleWriteBehavior extends \Prado\Util\TBehavior
{
	public function dyWrite($written, $string, $chain = null)
	{
		$written *= 2;
		if ($chain !== null) {
			return $chain->dyWrite($written, $string);
		}
		return $written;
	}
}

/**
 * Behavior that vetoes the readable and writable capabilities, via the dyIs* hooks.
 */
class TNoReadWriteBehavior extends \Prado\Util\TBehavior
{
	public function dyIsReadable($readable, $chain = null)
	{
		if ($chain !== null) {
			$chain->dyIsReadable($readable);
		}
		return false;
	}

	public function dyIsWritable($writable, $chain = null)
	{
		if ($chain !== null) {
			$chain->dyIsWritable($writable);
		}
		return false;
	}
}
