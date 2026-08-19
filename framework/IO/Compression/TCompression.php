<?php

/**
 * TCompression class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

use Prado\Exceptions\TIOException;

/**
 * TCompression class.
 *
 * A static façade over the {@see ICompressor} codecs keyed by their HTTP content-coding
 * token, so a caller compresses by name and negotiates against a client's `Accept-Encoding`
 * without binding to one codec.  The tokens, in descending preference, are:
 *
 * | Token     | Codec                  | Availability |
 * |-----------|------------------------|--------------|
 * | `zstd`    | {@see TZstdCompressor}    | `zstd` extension |
 * | `br`      | {@see TBrotliCompressor}  | `brotli` extension |
 * | `gzip`    | {@see TGzipCompressor}    | `zlib` extension |
 * | `deflate` | {@see TZlibCompressor}    | `zlib` extension |
 *
 * The HTTP `deflate` coding is the zlib format (RFC 9110), so it maps to
 * {@see TZlibCompressor}.  Only codings a client and server both support are offered:
 * {@see getAvailableMethods()} filters by the loaded extensions, {@see getBestMethod()}
 * takes the most-preferred available coding, and {@see negotiate()} resolves an
 * `Accept-Encoding` header, honoring q-values and the `*` wildcard.  Codecs outside HTTP
 * content negotiation (bzip2, raw DEFLATE) are used through their classes directly.
 *
 * The `xz`/LZMA format stays out of this negotiation: it is not a registered HTTP content
 * coding, and having no PHP extension it is a command-backed {@see TXzCompressor} rather than
 * a native in-process codec.  It is used through that class directly.
 *
 * ```php
 * $method = TCompression::negotiate($request->getHeader('Accept-Encoding'));
 * if ($method !== null) {
 *     $body = TCompression::compress($body, $method);
 *     $response->setHeader('Content-Encoding', $method);
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCompression
{
	/** @var array<string, class-string<TBuiltinCompressor>> The content-coding token to codec map, in preference order. */
	private const CODECS = [
		'zstd' => TZstdCompressor::class,
		'br' => TBrotliCompressor::class,
		'gzip' => TGzipCompressor::class,
		'deflate' => TZlibCompressor::class,
	];

	/**
	 * Returns every known content-coding token, in descending preference.
	 * @return string[] The content-coding tokens.
	 */
	public static function getMethods(): array
	{
		return array_keys(self::CODECS);
	}

	/**
	 * Returns the codec class for a content-coding token, or null when the token is unknown.
	 * @param string $method The content-coding token (case-insensitive).
	 * @return ?class-string<TBuiltinCompressor> The codec class, or null.
	 */
	public static function getCodec(string $method): ?string
	{
		return self::CODECS[strtolower($method)] ?? null;
	}

	/**
	 * Returns whether a content-coding is known and its backing extension is loaded.
	 * @param string $method The content-coding token (case-insensitive).
	 * @return bool Whether the coding can be applied here.
	 */
	public static function isAvailable(string $method): bool
	{
		$codec = self::getCodec($method);
		return $codec !== null && $codec::isAvailable();
	}

	/**
	 * Returns the content-coding tokens whose codecs can run here, in descending preference.
	 * @return string[] The available content-coding tokens.
	 */
	public static function getAvailableMethods(): array
	{
		return array_values(array_filter(self::getMethods(), self::isAvailable(...)));
	}

	/**
	 * Returns the most-preferred content-coding available here, or null when none can run.
	 * @return ?string The best content-coding token, or null.
	 */
	public static function getBestMethod(): ?string
	{
		return self::getAvailableMethods()[0] ?? null;
	}

	/**
	 * Compresses data with the named content-coding, or the best available when none is named.
	 * @param string $data The raw bytes.
	 * @param ?string $method The content-coding token, or null for {@see getBestMethod()}.
	 * @param int $level The compression level, or -1 for the codec's default.
	 * @throws TIOException When the coding is unknown or no codec is available.
	 * @return string The compressed bytes.
	 */
	public static function compress(string $data, ?string $method = null, int $level = -1): string
	{
		return self::resolve($method)::compress($data, $level);
	}

	/**
	 * Decompresses data produced under the named content-coding.
	 * @param string $data The compressed bytes.
	 * @param string $method The content-coding token the data was compressed with.
	 * @throws TIOException When the coding is unknown, its codec is unavailable, or the data is corrupt.
	 * @return string The decompressed bytes.
	 */
	public static function decompress(string $data, string $method): string
	{
		return self::resolve($method)::decompress($data);
	}

	/**
	 * Resolves a content-coding token to an available codec class.
	 * @param ?string $method The content-coding token, or null for the best available.
	 * @throws TIOException When the coding is unknown or no codec is available.
	 * @return class-string<TBuiltinCompressor> The codec class.
	 */
	private static function resolve(?string $method): string
	{
		$method ??= self::getBestMethod();
		if ($method === null) {
			throw new TIOException('compression_none_available');
		}
		$codec = self::getCodec($method);
		if ($codec === null || !$codec::isAvailable()) {
			throw new TIOException('compression_method_unknown', $method);
		}
		return $codec;
	}

	/**
	 * Chooses the content-coding to serve for a client's `Accept-Encoding` header: the
	 * server's most preferred coding among those the client accepts, honoring q-values (a
	 * `q=0` rejects a coding) and the `*` wildcard.  The server's preference order decides
	 * between two accepted codings, not the client's relative q-values, which RFC 9110
	 * permits.  Returns null when no shared coding is acceptable, so the caller sends the
	 * content uncompressed (identity).
	 * @param ?string $acceptEncoding The client's `Accept-Encoding` header, or null when absent.
	 * @param ?string[] $allowed The codings the server will offer, or null for {@see getAvailableMethods()}.
	 * @return ?string The chosen content-coding token, or null for identity.
	 */
	public static function negotiate(?string $acceptEncoding, ?array $allowed = null): ?string
	{
		$offer = $allowed ?? self::getAvailableMethods();
		if ($offer === []) {
			return null;
		}
		if ($acceptEncoding === null || trim($acceptEncoding) === '') {
			return null;   // an absent header carries no preference; serve identity
		}
		$weights = self::parseAcceptEncoding($acceptEncoding);
		$wildcard = $weights['*'] ?? null;
		foreach ($offer as $method) {
			$token = strtolower($method);
			$q = $weights[$token] ?? $wildcard;
			if ($q !== null && $q > 0.0) {
				return $method;
			}
		}
		return null;
	}

	/**
	 * Parses an `Accept-Encoding` header into a token-to-qvalue map, lowercasing the tokens.
	 * @param string $header The `Accept-Encoding` header value.
	 * @return array<string, float> The content-coding token to q-value map.
	 */
	private static function parseAcceptEncoding(string $header): array
	{
		$weights = [];
		foreach (explode(',', $header) as $part) {
			$segments = explode(';', trim($part));
			$token = strtolower(trim($segments[0]));
			if ($token === '') {
				continue;
			}
			$q = 1.0;
			foreach (array_slice($segments, 1) as $parameter) {
				[$name, $value] = array_pad(explode('=', $parameter, 2), 2, '');
				if (strtolower(trim($name)) === 'q') {
					$q = (float) trim($value);
				}
			}
			$weights[$token] = $q;
		}
		return $weights;
	}
}
