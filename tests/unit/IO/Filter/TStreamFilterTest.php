<?php

use Prado\Exceptions\TIOException;
use Prado\IO\TStream;
use Prado\IO\Filter\TStreamFilter;

class TStreamFilterTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		TRot13TestFilter::registerOnce();
	}

	public function testRegisterOnceIsIdempotentAndRegistered()
	{
		TRot13TestFilter::registerOnce();   // second call is a no-op
		self::assertTrue(TRot13TestFilter::isRegistered());
		self::assertContains('prado.test.rot13', TStreamFilter::getRegisteredFilters());
	}

	public function testRegisterDuplicateThrows()
	{
		self::expectException(TIOException::class);
		TRot13TestFilter::register();   // already registered by setUp
	}

	public function testFilterTransformsAndRemoves()
	{
		$s = TStream::fromMemory();
		$handle = TRot13TestFilter::append($s->getResource(), null, STREAM_FILTER_WRITE);
		self::assertNotFalse($handle);
		$s->write('hello');
		$s->seek(0);
		self::assertSame('uryyb', $s->getContents());   // rot13('hello')
		self::assertTrue(TRot13TestFilter::remove($handle));
		self::assertFalse(TRot13TestFilter::remove('not-a-resource'));
		$s->close();
	}

	public function testReadModeFilterTransformsOnRead()
	{
		$s = TStream::fromMemory();
		$s->write('hello');                 // raw, unfiltered
		$s->seek(0);
		$handle = TRot13TestFilter::append($s->getResource(), null, STREAM_FILTER_READ);
		self::assertNotFalse($handle);
		self::assertSame('uryyb', $s->getContents(), 'A read-mode filter transforms bytes as they are read.');
		$s->close();
	}

	public function testIsRegisteredAndBaseDefaultMode()
	{
		self::assertTrue(TStreamFilter::isRegistered('prado.test.rot13'));
		self::assertFalse(TStreamFilter::isRegistered('prado.test.never-registered'));
		self::assertSame(STREAM_FILTER_ALL, TStreamFilter::getDefaultMode(), 'The base default mode is STREAM_FILTER_ALL.');
	}

	public function testEmptyNameRegisterThrows()
	{
		self::expectException(TIOException::class);
		TStreamFilter::register('');   // base has no name
	}

	public function testPrependTransformsAndRemoves()
	{
		$s = TStream::fromMemory();
		$handle = TRot13TestFilter::prepend($s->getResource(), null, STREAM_FILTER_WRITE);
		self::assertNotFalse($handle);
		$s->write('hello');
		$s->seek(0);
		self::assertSame('uryyb', $s->getContents());
		self::assertTrue(TRot13TestFilter::remove($handle));
		$s->close();
	}

	public function testDefaultModeUsedWhenModeIsNull()
	{
		// TLifecycleTestFilter defaults to STREAM_FILTER_WRITE, so a null mode filters writes.
		TLifecycleTestFilter::registerOnce();
		$s = TStream::fromMemory();
		TLifecycleTestFilter::append($s->getResource());   // null mode -> getDefaultMode()
		$s->write('hi');
		$s->seek(0);
		self::assertSame('HI', $s->getContents());
		$s->close();
	}

	public function testParamsReachOnCreateAndOnCloseRuns()
	{
		TLifecycleTestFilter::registerOnce();
		$created = TLifecycleTestFilter::$created;
		$closed = TLifecycleTestFilter::$closed;
		$s = TStream::fromMemory();
		$handle = TLifecycleTestFilter::append($s->getResource(), null, STREAM_FILTER_WRITE, ['k' => 'v']);
		self::assertSame($created + 1, TLifecycleTestFilter::$created, 'onCreate ran on attach.');
		self::assertSame(['k' => 'v'], TLifecycleTestFilter::$seenParams, 'params reached onCreate.');
		TLifecycleTestFilter::remove($handle);
		self::assertSame($closed + 1, TLifecycleTestFilter::$closed, 'onClose ran on remove.');
		$s->close();
	}

	public function testFatalFilterFailsTheWrite()
	{
		TFatalTestFilter::registerOnce();
		$s = TStream::fromMemory();
		$resource = $s->getResource();
		TFatalTestFilter::append($resource, null, STREAM_FILTER_WRITE);
		self::assertFalse(@fwrite($resource, 'data'), 'A PSFS_ERR_FATAL filter fails the write.');
		$s->close();
	}
}

/**
 * Concrete filter that rot13-transforms bytes, for exercising the base.
 */
class TRot13TestFilter extends TStreamFilter
{
	public static function getFilterName(): string
	{
		return 'prado.test.rot13';
	}

	protected function convert(object $bucket, bool $closing): int
	{
		$bucket->data = str_rot13($bucket->data);
		$bucket->datalen = strlen($bucket->data);
		return PSFS_PASS_ON;
	}
}

/**
 * Filter with a non-default mode that records its create/close lifecycle and parameters.
 */
class TLifecycleTestFilter extends TStreamFilter
{
	public static int $created = 0;
	public static int $closed = 0;
	public static mixed $seenParams = null;

	public static function getFilterName(): string
	{
		return 'prado.test.lifecycle';
	}

	public static function getDefaultMode(): int
	{
		return STREAM_FILTER_WRITE;
	}

	public function onCreate(): bool
	{
		self::$created++;
		self::$seenParams = $this->params;
		return true;
	}

	public function onClose(): void
	{
		self::$closed++;
	}

	protected function convert(object $bucket, bool $closing): int
	{
		$bucket->data = strtoupper($bucket->data);
		return PSFS_PASS_ON;
	}
}

/**
 * Filter that aborts the brigade, to exercise the PSFS_ERR_FATAL path.
 */
class TFatalTestFilter extends TStreamFilter
{
	public static function getFilterName(): string
	{
		return 'prado.test.fatal';
	}

	protected function convert(object $bucket, bool $closing): int
	{
		return PSFS_ERR_FATAL;
	}
}
