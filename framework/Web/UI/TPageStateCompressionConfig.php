<?php

/**
 * TPageStateCompressionConfig class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\IO\Compression\TCompressionConfig;

/**
 * TPageStateCompressionConfig class.
 *
 * The compression settings the built-in page state persisters start from, and that
 * {@see TPage::getStateCompression()} keeps for a persister that holds none.  They differ
 * from {@see TCompressionConfig} in two defaults that page state requires:
 *
 * | Default | Value | Reason |
 * |---------|-------|--------|
 * | {@see DEFAULT_ENABLED} | `true` | page state has compressed by default since PRADO 3.1.6 |
 * | {@see DEFAULT_THRESHOLD} | `0` | every state compresses, whatever its length |
 *
 * The threshold is zero because a page state carries no marker naming the coding that
 * wrote it: the reader decompresses whenever the setting says the writer compressed.  A
 * length that skipped compression would be indistinguishable from one that did not, and
 * would read back as a corrupted state.  {@see TPageStateFormatter} therefore consults
 * {@see TCompressionConfig::getShouldCompress() ShouldCompress} and never the length.
 *
 * {@see TCompressionConfig::DEFAULT_METHOD} carries over unchanged: `deflate` is the zlib
 * format PRADO has always written the page state in, so a state written by an earlier
 * version reads back.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPageStateCompressionConfig extends TCompressionConfig
{
	/** Page state compresses unless an application turns it off. */
	public const DEFAULT_ENABLED = true;

	/** Every page state compresses, whatever its length. */
	public const DEFAULT_THRESHOLD = 0;
}
