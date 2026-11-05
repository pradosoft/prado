<?php

/**
 * IStreamCodec interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * IStreamCodec interface.
 *
 * An incremental byte-stream codec context, modeled on PHP's own {@see deflate_init()}/
 * {@see deflate_add()} (and {@see inflate_init()}/{@see inflate_add()}): the implementing
 * class hands back a fresh context, {@see add()} pushes each input chunk and returns
 * whatever output is ready, and {@see finish()} flushes the trailing state.  A context is
 * single-use and single-direction, so encoding and decoding are separate contexts.
 *
 * A context holds only the bounded state its algorithm needs — a carry buffer, a
 * dictionary, a partial byte — so it transforms a stream of any size in constant memory.
 * Chunk boundaries are invisible to the result: the same bytes fed as one call or as many
 * produce the same output.
 *
 * The interface exists so one codec implementation serves both consumers in the IO layer:
 *
 * | Consumer | How it drives the codec |
 * |----------|-------------------------|
 * | {@see ICompressor} (whole string) | `add($all) . finish()` |
 * | {@see \Prado\IO\Filter\TStreamCodecFilter} (streaming) | `process()` calls {@see add()}, `finish()` calls {@see finish()} |
 *
 * A codec is a plain class holding its own state:
 *
 * ```php
 * class TUpperPairCodec implements IStreamCodec
 * {
 *     private string $_carry = '';
 *
 *     public function add(string $data): string
 *     {
 *         $buffer = $this->_carry . $data;
 *         $whole = intdiv(strlen($buffer), 2) * 2;   // emit only complete pairs
 *         $this->_carry = substr($buffer, $whole);   // hold the odd byte for the next chunk
 *         return strtoupper(substr($buffer, 0, $whole));
 *     }
 *
 *     public function finish(): string
 *     {
 *         $tail = $this->_carry;
 *         $this->_carry = '';
 *         return strtoupper($tail);
 *     }
 * }
 *
 * $codec = new TUpperPairCodec();
 * $out  = $codec->add('ab');
 * $out .= $codec->add('c');     // 'c' is held; nothing emitted yet
 * $out .= $codec->finish();     // 'ABC'
 * ```
 *
 * The same context drives a stream filter, so the streaming path duplicates no logic:
 *
 * ```php
 * class TUpperPairFilter extends TStreamCodecFilter
 * {
 *     private IStreamCodec $_codec;
 *
 *     public static function getFilterName(): string
 *     {
 *         return 'prado.upperpair';
 *     }
 *
 *     public function onCreate(): bool
 *     {
 *         $this->_codec = new TUpperPairCodec();
 *         return true;
 *     }
 *
 *     protected function process(string $data): string
 *     {
 *         return $this->_codec->add($data);
 *     }
 *
 *     protected function finish(): string
 *     {
 *         return $this->_codec->finish();
 *     }
 * }
 *
 * TUpperPairFilter::registerOnce();
 * $stream = TStream::fromString('abc');
 * $stream->appendFilter(TUpperPairFilter::getFilterName(), STREAM_FILTER_READ);
 * echo $stream->getContents();   // 'ABC'
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IStreamCodec
{
	/**
	 * Pushes a chunk of input and returns the output produced so far.  Bounded state is
	 * carried to the next call, so a field split across chunks is handled correctly and an
	 * empty chunk is a no-op.
	 *
	 * ```php
	 * $codec->add('ab') . $codec->add('c');   // same bytes as $codec->add('abc')
	 * ```
	 * @param string $data The input chunk (may be '').
	 * @return string The output produced from this chunk (may be '').
	 */
	public function add(string $data): string;

	/**
	 * Flushes any pending state and returns the final output.  After this the context is
	 * spent; further {@see add()} calls are not defined.
	 *
	 * ```php
	 * $encoded = $codec->add($data) . $codec->finish();   // the whole-string form
	 * ```
	 * @return string The final output (may be '').
	 */
	public function finish(): string;
}
