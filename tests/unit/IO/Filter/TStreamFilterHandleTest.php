<?php

use Prado\IO\TStream;
use Prado\IO\Filter\TStreamFilterHandle;
use Prado\IO\Filter\TStreamFilter;

class TStreamFilterHandleTest extends PHPUnit\Framework\TestCase
{
	public function testHandleRemovesSelfAndUntracksOwner()
	{
		$s = TStream::fromMemory();
		$handle = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		self::assertInstanceOf(TStreamFilterHandle::class, $handle);
		self::assertSame($s, $handle->getStream());
		self::assertSame('string.rot13', $handle->getName());
		self::assertTrue($handle->isActive());
		self::assertCount(1, $s->getFilters());

		self::assertTrue($handle->remove(), 'The handle removes its own filter.');
		self::assertFalse($handle->isActive());
		self::assertCount(0, $s->getFilters(), 'Removal untracks it on the owning stream.');
		self::assertFalse($handle->remove(), 'A second remove is a no-op.');
		$s->close();
	}

	public function testRemoveFilterDetachesAndUntracks()
	{
		$s = TStream::fromMemory();
		$handle = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		self::assertTrue($s->removeFilter($handle), 'removeFilter() detaches the filter.');
		self::assertCount(0, $s->getFilters(), 'and untracks it.');
		self::assertFalse($handle->isActive());
		$s->close();
	}

	public function testGetResourceExposesRawHandleUntilRemoved()
	{
		$s = TStream::fromMemory();
		$handle = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		self::assertTrue(is_resource($handle->getResource()));
		$handle->remove();
		self::assertNull($handle->getResource(), 'The raw resource is cleared after removal.');
		$s->close();
	}

	public function testStaticAppendWithTStreamSetsOwnerAndTracks()
	{
		// Passing a TStream (not its raw resource) yields an owned, tracked handle.
		$s = TStream::fromMemory();
		$handle = TStreamFilterHandleTestRot13::append($s, null, STREAM_FILTER_WRITE);
		self::assertInstanceOf(TStreamFilterHandle::class, $handle);
		self::assertSame($s, $handle->getStream(), 'A TStream argument sets the handle owner.');
		self::assertCount(1, $s->getFilters(), 'The handle is tracked on the stream.');
		$s->write('hi');
		$s->seek(0);
		self::assertSame('uv', $s->getContents());   // rot13('hi')
		$handle->remove();
		self::assertCount(0, $s->getFilters(), 'Removal untracks it on the stream.');
		$s->close();
	}

	public function testStaticPrependWithTStreamSetsOwner()
	{
		$s = TStream::fromMemory();
		$handle = TStreamFilterHandleTestRot13::prepend($s, null, STREAM_FILTER_WRITE);
		self::assertSame($s, $handle->getStream());
		self::assertCount(1, $s->getFilters());
		$s->close();
	}

	public function testGetFilterHandleByResourceNameOrHandle()
	{
		$s = TStream::fromMemory();
		$handle = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		self::assertSame($handle, $s->getFilterHandle($handle->getResource()), 'Finds the handle from its raw resource.');
		self::assertSame($handle, $s->getFilterHandle('string.rot13'), 'Finds the handle by name.');
		self::assertSame($handle, $s->getFilterHandle($handle), 'Finds the handle by itself.');
		self::assertNull($s->getFilterHandle('not-attached'));
		$s->close();
	}

	public function testForgetFilterByResourceUntracksWithoutDetaching()
	{
		$s = TStream::fromMemory();
		$handle = $s->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		$resource = $handle->getResource();
		$s->forgetFilter($resource);                 // untrack by raw resource
		self::assertCount(0, $s->getFilters(), 'forgetFilter by resource untracks the handle.');
		self::assertTrue(is_resource($resource), 'forgetFilter only untracks; the filter stays attached.');
		$s->close();
	}

	public function testForgetFilterIgnoresUnknownFilter()
	{
		$s = TStream::fromMemory();
		$other = TStream::fromMemory();
		$foreign = $other->appendFilter('string.rot13', STREAM_FILTER_WRITE);
		$s->forgetFilter($foreign);                  // a handle from another stream: ignored
		$s->forgetFilter('not-attached');            // an unknown name: ignored
		self::assertCount(1, $other->getFilters(), 'The other stream keeps its filter.');
		$s->close();
		$other->close();
	}

	public function testOwnerlessHandleFromStaticAppend()
	{
		TStreamFilterHandleTestRot13::registerOnce();
		$s = TStream::fromMemory();
		$handle = TStreamFilterHandleTestRot13::append($s->getResource(), null, STREAM_FILTER_WRITE);
		self::assertInstanceOf(TStreamFilterHandle::class, $handle);
		self::assertNull($handle->getStream(), 'A static-append handle has no owning stream.');
		$s->write('hello');
		$s->seek(0);
		self::assertSame('uryyb', $s->getContents());
		self::assertTrue($handle->remove());
		self::assertFalse($handle->isActive());
		$s->close();
	}
}

/**
 * rot13 filter for exercising owner-less handles from the static append.
 */
class TStreamFilterHandleTestRot13 extends TStreamFilter
{
	public static function getFilterName(): string
	{
		return 'prado.test.handle.rot13';
	}

	protected function convert(object $bucket, bool $closing): int
	{
		$bucket->data = str_rot13($bucket->data);
		$bucket->datalen = strlen($bucket->data);
		return PSFS_PASS_ON;
	}
}
