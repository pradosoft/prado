<?php

/**
 * TStreamCodecFilter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Filter;

/**
 * TStreamCodecFilter class.
 *
 * Serves as the base for stream filters that transform data incrementally: each input
 * bucket is fed to {@see process()} as it arrives, and {@see finish()} emits whatever
 * remains when the stream closes.  A subclass keeps only the bounded state its codec
 * needs (a carry buffer, a dictionary), so it filters streams of any size in constant
 * memory. A transform that needs the full input accumulates it inside {@see process()}
 * and emits in {@see finish()}. A streaming codec emits as it goes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TStreamCodecFilter extends TStreamFilter
{
	/**
	 * Feeds each input bucket to {@see process()} and emits the closing {@see finish()}.
	 * @param mixed $in The input bucket brigade.
	 * @param mixed $out The output bucket brigade.
	 * @param int &$consumed The running count of consumed bytes.
	 * @param bool $closing Whether the stream is closing.
	 * @return int PSFS_PASS_ON.
	 */
	public function filter($in, $out, &$consumed, bool $closing): int
	{
		while ($bucket = stream_bucket_make_writeable($in)) {
			$consumed += $bucket->datalen;
			$data = $this->process($bucket->data);
			if ($data !== '') {
				stream_bucket_append($out, stream_bucket_new($this->stream, $data));
			}
		}
		if ($closing) {
			$data = $this->finish();
			if ($data !== '') {
				stream_bucket_append($out, stream_bucket_new($this->stream, $data));
			}
		}
		return PSFS_PASS_ON;
	}

	/**
	 * Satisfies the {@see TStreamFilter} contract; unused because {@see filter()} streams.
	 * @param object $bucket The bucket (unused).
	 * @param bool $closing Whether the stream is closing (unused).
	 * @return int PSFS_PASS_ON.
	 */
	protected function convert(object $bucket, bool $closing): int
	{
		return PSFS_PASS_ON;
	}

	/**
	 * Transforms one chunk of input, returning the bytes produced so far.
	 * @param string $data The input chunk.
	 * @return string The output produced from this chunk (may be '').
	 */
	abstract protected function process(string $data): string;

	/**
	 * Emits any bytes still pending when the stream closes.
	 * @return string The final output (may be '').
	 */
	abstract protected function finish(): string;
}
