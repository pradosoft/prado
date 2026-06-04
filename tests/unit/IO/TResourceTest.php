<?php

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\IO\IResource;

/**
 * Unit tests for {@see \Prado\IO\TResource}, the abstract base of the IO layer.
 *
 * Built on the IO harness: {@see TTestResource} instantiates the abstract base,
 * {@see TTestIOHelper} opens resources and scratch files, and {@see TTestIOEventLog} records
 * the lifecycle events.
 */
class TResourceTest extends PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	public function testConstructAndIsOpen()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource($res);
		self::assertInstanceOf(IResource::class, $r);
		self::assertTrue($r->isOpen());
		self::assertSame($res, $r->getResource());
		self::assertTrue($r->getOwnsResource());
		$r->closeStream();
	}

	public function testConstructNullIsClosed()
	{
		$r = new TTestResource();
		self::assertFalse($r->isOpen());
		self::assertNull($r->getResource());
		self::assertNull($r->closeStream());
	}

	public function testConstructInvalidThrows()
	{
		self::expectException(TInvalidDataTypeException::class);
		new TTestResource('not a resource');
	}

	public function testAttachResource()
	{
		$r = new TTestResource();
		$res = TTestIOHelper::tempResource();
		$r->attachResource($res, false);
		self::assertTrue($r->isOpen());
		self::assertFalse($r->getOwnsResource());
		TTestIOHelper::closeAny($res);
	}

	public function testMetadata()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		self::assertIsArray($r->getMetadata());
		self::assertSame('PHP', $r->getMetadata('wrapper_type'));
		self::assertNull($r->getMetadata('no_such_key'));
		self::assertSame('stream', $r->getResourceType());
		$r->closeStream();
	}

	public function testBlocking()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		self::assertTrue($r->setBlocking(false));
		self::assertTrue($r->setBlocking(true));
		$r->closeStream();
	}

	public function testStat()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		$stat = $r->stat();
		self::assertIsArray($stat);
		self::assertArrayHasKey(7, $stat);
		$r->closeStream();
	}

	public function testFflush()
	{
		$r = new TTestResource(TTestIOHelper::dataResource('hello'));
		self::assertTrue($r->fflush());
		$r->closeStream();
	}

	public function testCloseClosesOwnedResource()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource($res);
		self::assertTrue($r->closeStream());
		self::assertSame(1, $r->closeResourceCalls, 'Owned close runs the close primitive once.');
		self::assertFalse($r->isOpen());
		self::assertFalse(is_resource($res), 'Owned resource should be closed.');
	}

	public function testCloseLeavesBorrowedResourceOpen()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource();
		$r->attachResource($res, false);
		self::assertFalse($r->closeStream());
		self::assertSame(0, $r->closeResourceCalls, 'Borrowed close skips the close primitive.');
		self::assertFalse($r->isOpen());
		self::assertTrue(is_resource($res), 'Borrowed resource must stay open.');
		TTestIOHelper::closeAny($res);
	}

	public function testDetachDoesNotClose()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource($res);
		$detached = $r->detach();
		self::assertSame($res, $detached);
		self::assertFalse($r->isOpen());
		self::assertTrue(is_resource($res), 'Detached resource must stay open.');
		TTestIOHelper::closeAny($res);
	}

	public function testDestructorClosesOwned()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource($res);
		unset($r);
		self::assertFalse(is_resource($res), 'Destructor should close owned resource.');
	}

	public function testDestructorLeavesBorrowedOpen()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource();
		$r->attachResource($res, false);
		unset($r);
		self::assertTrue(is_resource($res), 'Destructor must not close borrowed resource.');
		TTestIOHelper::closeAny($res);
	}

	public function testEventsFire()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		$log = (new TTestIOEventLog())->listenTo($r, ['onFinalize', 'onClose']);
		$r->closeStream();
		self::assertSame(['onFinalize', 'onClose'], $log->events());
	}

	public function testReattachClosesPriorOwnedHandle()
	{
		$a = TTestIOHelper::tempResource();
		$b = TTestIOHelper::tempResource();
		$r = new TTestResource($a);
		$r->attachResource($b);
		self::assertFalse(is_resource($a), 'Re-attach must close the prior owned handle (no leak).');
		self::assertSame($b, $r->getResource());
		$r->closeStream();
	}

	public function testReattachDoesNotCloseBorrowedPrior()
	{
		$a = TTestIOHelper::tempResource();
		$b = TTestIOHelper::tempResource();
		$r = new TTestResource();
		$r->attachResource($a, false);   // borrowed
		$r->attachResource($b);
		self::assertTrue(is_resource($a), 'Borrowed prior handle is left open.');
		TTestIOHelper::closeAny($a);
		$r->closeStream();
	}

	public function testReattachSameHandleIsNoOp()
	{
		$a = TTestIOHelper::tempResource();
		$r = new TTestResource($a);
		$r->attachResource($a);   // same handle — must not close it
		self::assertTrue(is_resource($a));
		self::assertSame($a, $r->getResource());
		$r->closeStream();
	}

	public function testCloneIsNonOwningSharingHandle()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource($res);
		$copy = clone $r;
		self::assertFalse($copy->getOwnsResource(), 'Clone does not own the shared handle.');
		self::assertSame($res, $copy->getResource());
		$copy->closeStream();                       // non-owning close leaves it open
		self::assertTrue(is_resource($res));
		$r->closeStream();
		self::assertFalse(is_resource($res));
	}

	public function testSetOwnsResource()
	{
		$res = TTestIOHelper::tempResource();
		$r = new TTestResource();
		$r->attachResource($res, false);
		self::assertFalse($r->getOwnsResource());
		$r->setOwnsResource(true);
		self::assertTrue($r->getOwnsResource());
		$r->closeStream();
		self::assertFalse(is_resource($res), 'Now-owned handle is closed.');
	}

	public function testDetachWhenEmpty()
	{
		$r = new TTestResource();
		self::assertNull($r->detach());
	}

	public function testDoubleCloseIsSafe()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		self::assertTrue($r->closeStream());
		self::assertNull($r->closeStream(), 'Second close returns null.');
	}

	public function testForcedCloseFailureResult()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		$r->closeResourceReturn = false;   // harness drives the failure path
		self::assertFalse($r->closeStream());
		self::assertFalse($r->isOpen());
	}

	public function testResourceTypeAndMetadataWhenClosed()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		$r->closeStream();
		self::assertNull($r->getResourceType());
		self::assertSame([], $r->getMetadata());
		self::assertNull($r->getMetadata('mode'));
		self::assertFalse($r->stat());
		self::assertNull($r->getBlocking());
	}

	public function testLockUnlockOnRealFile()
	{
		$path = TTestIOHelper::tempFile('', 'prado-tres');
		$r = new TTestResource(TTestIOHelper::fileResource($path, 'r+b'));
		self::assertTrue($r->lock(LOCK_EX));
		self::assertTrue($r->unlock());
		$r->closeStream();
	}

	public function testLockReturnsNullWhenClosed()
	{
		$r = new TTestResource();
		self::assertNull($r->lock(LOCK_EX));
		self::assertNull($r->unlock());
	}

	public function testChunkAndBufferSizes()
	{
		$path = TTestIOHelper::tempFile('', 'prado-tres');
		$r = new TTestResource(TTestIOHelper::fileResource($path, 'r+b'));
		self::assertIsInt($r->setChunkSize(1024));
		self::assertIsInt($r->setReadBuffer(0));
		self::assertIsInt($r->setWriteBuffer(0));
		$r->closeStream();
		self::assertFalse($r->setChunkSize(1024), 'Closed stream returns false.');
		self::assertSame(-1, $r->setReadBuffer(0), 'Closed stream returns -1.');
	}

	public function testLocalAndTtyProbes()
	{
		$path = TTestIOHelper::tempFile('', 'prado-tres');
		$r = new TTestResource(TTestIOHelper::fileResource($path, 'r+b'));
		self::assertTrue($r->getIsLocal());
		self::assertFalse($r->getIsTTY());
		$r->closeStream();
		self::assertFalse($r->getIsLocal(), 'Closed stream is not local.');
	}

	public function testTimedOutFalseAndDoesNotFireEvent()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		$log = (new TTestIOEventLog())->listenTo($r, ['onTimeout']);
		self::assertFalse($r->getTimedOut());
		self::assertFalse($log->has('onTimeout'), 'onTimeout does not fire when not timed out.');
		$r->closeStream();
	}

	public function testOpenDetachFlushEventsFire()
	{
		$r = new TTestResource();
		$log = (new TTestIOEventLog())->listenTo($r, ['onOpen', 'onDetach', 'onFlush']);
		$res = TTestIOHelper::tempResource();
		$r->attachResource($res);          // onOpen
		$r->fflush();                       // onFlush
		$r->detach();                       // onDetach
		self::assertSame(['onOpen', 'onFlush', 'onDetach'], $log->events());
		TTestIOHelper::closeAny($res);
	}

	public function testSerializationZapsHandle()
	{
		$r = new TTestResource(TTestIOHelper::tempResource());
		$revived = unserialize(serialize($r));
		self::assertInstanceOf(TTestResource::class, $revived);
		self::assertFalse($revived->isOpen(), 'Revived instance holds no handle.');
		self::assertFalse($revived->getOwnsResource());
		$r->closeStream();
	}

	public function testProcessResourceSkipsStreamOps()
	{
		$proc = proc_open(PHP_BINARY . ' -r "usleep(1);"', [], $pipes);
		if (!is_resource($proc)) {
			self::markTestSkipped('proc_open unavailable.');
		}
		$r = new TTestResource($proc);
		self::assertTrue($r->getIsProcess());
		self::assertSame([], $r->getMetadata());
		self::assertFalse($r->stat());
		self::assertFalse($r->setBlocking(false));
		// TResource::closeResource uses fclose; for a real process TProcess overrides this.
		$r->detach();
		proc_close($proc);
	}
}
