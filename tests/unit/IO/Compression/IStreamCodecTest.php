<?php

use Prado\IO\Compression\ICompressor;
use Prado\IO\Compression\IStreamCodec;
use Prado\IO\Filter\TStreamCodecFilter;
use Prado\IO\TStream;

/**
 * A codec with real carry state: it emits complete byte pairs uppercased and holds an odd
 * byte until the next chunk or {@see finish()}.  An implementation that ignored the carry
 * would produce different bytes for different chunkings, so the contract is testable.
 */
class UpperPairCodec implements IStreamCodec
{
	private string $_carry = '';

	public function add(string $data): string
	{
		$buffer = $this->_carry . $data;
		$whole = intdiv(strlen($buffer), 2) * 2;
		$this->_carry = substr($buffer, $whole);
		return strtoupper(substr($buffer, 0, $whole));
	}

	public function finish(): string
	{
		$tail = $this->_carry;
		$this->_carry = '';
		return strtoupper($tail);
	}
}

/** The whole-string consumer: ICompressor over one codec context per call. */
class UpperPairCompressor implements ICompressor
{
	public static function compress(string $data): string
	{
		$codec = new UpperPairCodec();
		return $codec->add($data) . $codec->finish();
	}

	public static function decompress(string $data): string
	{
		return strtolower($data);
	}
}

/** The streaming consumer: a stream filter driving the same codec. */
class UpperPairFilter extends TStreamCodecFilter
{
	private IStreamCodec $_codec;

	public static function getFilterName(): string
	{
		return 'prado.test.upperpair';
	}

	public function onCreate(): bool
	{
		$this->_codec = new UpperPairCodec();
		return true;
	}

	protected function process(string $data): string
	{
		return $this->_codec->add($data);
	}

	protected function finish(): string
	{
		return $this->_codec->finish();
	}
}

class IStreamCodecTest extends PHPUnit\Framework\TestCase
{
	public function testTheContractShape()
	{
		$codec = new UpperPairCodec();
		self::assertInstanceOf(IStreamCodec::class, $codec);
		$ref = new \ReflectionClass(IStreamCodec::class);
		self::assertSame(['add', 'finish'], array_map(fn ($m) => $m->getName(), $ref->getMethods()), 'The context is add() plus finish().');
	}

	public function testAddThenFinishProducesTheWholeOutput()
	{
		$codec = new UpperPairCodec();
		self::assertSame('AB', $codec->add('abc'), 'Only the complete pair is emitted.');
		self::assertSame('C', $codec->finish(), 'finish() flushes the held byte.');
	}

	public function testChunkBoundariesAreInvisibleToTheResult()
	{
		$data = 'the quick brown fox jumps over the lazy dog';
		$whole = (new UpperPairCodec())->add($data);
		$whole .= '';   // the reference output below is assembled the same way
		$reference = strtoupper($data);

		foreach ([[1], [2], [3], [5], [8], [1000]] as [$size]) {
			$codec = new UpperPairCodec();
			$out = '';
			foreach (str_split($data, $size) as $chunk) {
				$out .= $codec->add($chunk);
			}
			$out .= $codec->finish();
			self::assertSame($reference, $out, "chunk size {$size} yields the same bytes");
		}
		self::assertNotSame('', $whole);
	}

	public function testEmptyChunksAreNoOps()
	{
		$codec = new UpperPairCodec();
		self::assertSame('', $codec->add(''));
		self::assertSame('AB', $codec->add('ab'));
		self::assertSame('', $codec->add(''), 'An empty chunk emits nothing and disturbs no state.');
		self::assertSame('', $codec->finish(), 'Nothing is pending after a whole pair.');
	}

	public function testEmptyInputFinishesEmpty()
	{
		$codec = new UpperPairCodec();
		self::assertSame('', $codec->add(''));
		self::assertSame('', $codec->finish());
	}

	public function testContextsAreIndependent()
	{
		$a = new UpperPairCodec();
		$b = new UpperPairCodec();
		$a->add('x');                                   // 'x' held in $a only
		self::assertSame('YZ', $b->add('yz'), 'A second context carries none of the first context state.');
		self::assertSame('', $b->finish());
		self::assertSame('X', $a->finish());
	}

	// ---- the two consumers share the one implementation ------------------------

	public function testWholeStringConsumerMatchesTheCodec()
	{
		$data = 'abcde';
		$codec = new UpperPairCodec();
		self::assertSame($codec->add($data) . $codec->finish(), UpperPairCompressor::compress($data));
		self::assertSame('ABCDE', UpperPairCompressor::compress($data));
	}

	public function testStreamFilterConsumerMatchesTheCodec()
	{
		UpperPairFilter::registerOnce();
		self::assertTrue(UpperPairFilter::isRegistered(UpperPairFilter::getFilterName()));

		$data = 'abcde';   // odd length, so the filter's finish() must flush the carry
		$stream = TStream::fromString($data);
		$stream->appendFilter(UpperPairFilter::getFilterName(), STREAM_FILTER_READ);
		self::assertSame(UpperPairCompressor::compress($data), $stream->getContents(), 'The filter and the whole-string form agree.');
		$stream->close();
	}

	public function testStreamFilterAgreesAcrossReadSizes()
	{
		UpperPairFilter::registerOnce();
		$data = 'the quick brown fox';
		$expected = strtoupper($data);

		foreach ([1, 3, 7, 8192] as $readSize) {
			$handle = fopen('php://temp', 'r+b');
			fwrite($handle, $data);
			rewind($handle);
			stream_filter_append($handle, UpperPairFilter::getFilterName(), STREAM_FILTER_READ);
			$out = '';
			while (!feof($handle)) {
				$piece = fread($handle, $readSize);
				if ($piece === false) {
					break;
				}
				$out .= $piece;
			}
			fclose($handle);
			self::assertSame($expected, $out, "read size {$readSize} yields the same bytes");
		}
	}
}
