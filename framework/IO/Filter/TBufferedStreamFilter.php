<?php

/**
 * TBufferedStreamFilter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Filter;

/**
 * TBufferedStreamFilter class.
 *
 * Serves as the base for stream filters whose transform needs the whole stream at once,
 * such as a stateful compressor.  It accumulates every input bucket and, when the stream
 * closes, runs {@see process()} over the complete buffer and emits the result as one
 * bucket.  A subclass implements only {@see process()}.
 *
 * For a transform that can work incrementally, extend {@see TStreamCodecFilter} instead.
 *
 * Attach in read mode (STREAM_FILTER_READ): a read reaches the closing flush at
 * end-of-stream, so the transformed output is returned in full.  In write mode the
 * flush happens only when the stream is closed, so the result must be read from a
 * reopened target rather than the still-open handle.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBufferedStreamFilter extends TStreamFilter
{
	/** @var string The accumulated input awaiting the closing flush. */
	private string $_buffer = '';

	/**
	 * Returns the raw accumulated input buffer.
	 * @return string The raw accumulated input buffer.
	 */
	protected function getBufferDirect(): string
	{
		return $this->_buffer;
	}

	/**
	 * Sets the raw accumulated input buffer.
	 * @param string $value The raw accumulated input buffer.
	 */
	protected function setBufferDirect(string $value): void
	{
		$this->_buffer = $value;
	}

	/**
	 * Buffers input and, on close, emits {@see process()} of the whole buffer.
	 * @param mixed $in The input bucket brigade.
	 * @param mixed $out The output bucket brigade.
	 * @param int &$consumed The running count of consumed bytes.
	 * @param bool $closing Whether the stream is closing.
	 * @return int PSFS_FEED_ME until closing, then PSFS_PASS_ON.
	 */
	public function filter($in, $out, &$consumed, bool $closing): int
	{
		while ($bucket = stream_bucket_make_writeable($in)) {
			$this->_buffer .= $bucket->data;
			$consumed += $bucket->datalen;
		}
		if (!$closing) {
			return PSFS_FEED_ME;
		}
		$data = $this->process($this->_buffer);
		$this->setBufferDirect('');
		if ($data !== '') {
			stream_bucket_append($out, stream_bucket_new($this->stream, $data));
		}
		return PSFS_PASS_ON;
	}

	/**
	 * Satisfies the {@see TStreamFilter} contract; unused because {@see filter()} buffers.
	 * @param object $bucket The bucket (unused).
	 * @param bool $closing Whether the stream is closing (unused).
	 * @return int PSFS_PASS_ON.
	 */
	protected function convert(object $bucket, bool $closing): int
	{
		return PSFS_PASS_ON;
	}

	/**
	 * Transforms the complete buffered input.
	 * @param string $data The whole accumulated input.
	 * @return string The transformed output.
	 */
	abstract protected function process(string $data): string;
}
