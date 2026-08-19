<?php

/**
 * TMultipartStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TStream;

/**
 * TMultipartStream class.
 *
 * Builds a `multipart/form-data` request body as a read-only {@see TAppendStream} of the
 * part headers and contents, for posting form fields and file uploads.  Each element is an
 * array:
 *
 * | Key        | Required | Meaning                                                      |
 * |------------|----------|--------------------------------------------------------------|
 * | `name`     | yes      | The form field name.                                         |
 * | `contents` | yes      | The field value: a string, resource, or PSR-7 stream.        |
 * | `filename` | no       | A file name, which marks the part as a file upload.          |
 * | `headers`  | no       | Extra part headers as a name => value map.                   |
 *
 * The `Content-Disposition` header is generated; a known content length is added when the
 * part stream reports a size.  {@see getBoundary()} returns the boundary to put in the
 * request's `Content-Type: multipart/form-data; boundary=...` header.
 *
 * ```php
 * $body = new TMultipartStream([
 *     ['name' => 'title',  'contents' => 'Sunset'],
 *     ['name' => 'photo',  'contents' => TStream::fromFile('a.jpg', 'rb'), 'filename' => 'a.jpg',
 *      'headers' => ['Content-Type' => 'image/jpeg']],
 * ]);
 * // Content-Type: multipart/form-data; boundary={$body->getBoundary()}
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMultipartStream extends TAppendStream
{
	/** @var string The multipart boundary marker. */
	private string $_boundary;

	/**
	 * @param array<int, array<string, mixed>> $elements The form-data elements.
	 * @param ?string $boundary The boundary marker; null generates a random one.
	 */
	public function __construct(array $elements = [], ?string $boundary = null)
	{
		parent::__construct();
		$this->_boundary = $boundary ?? bin2hex(random_bytes(20));
		foreach ($elements as $element) {
			$this->addElement($element);
		}
		$this->add(TStream::fromString('--' . $this->_boundary . "--\r\n"));
	}

	/**
	 * Returns the boundary marker for the request's Content-Type header.
	 * @return string The multipart boundary.
	 */
	public function getBoundary(): string
	{
		return $this->_boundary;
	}

	/**
	 * Appends one form-data element (its header part, contents, and trailing CRLF).
	 * @param array<string, mixed> $element The element ('name', 'contents', optional 'filename'/'headers').
	 * @throws TInvalidDataValueException When 'name' or 'contents' is missing.
	 */
	private function addElement(array $element): void
	{
		if (!isset($element['name']) || !isset($element['contents'])) {
			throw new TInvalidDataValueException('multipartstream_element_invalid');
		}
		$stream = TStream::for($element['contents']);
		$headers = $this->partHeaders((string) $element['name'], $element['filename'] ?? null, $element['headers'] ?? [], $stream->getSize());
		$this->add(TStream::fromString('--' . $this->_boundary . "\r\n" . $headers . "\r\n"));
		$this->add($stream);
		$this->add(TStream::fromString("\r\n"));
	}

	/**
	 * Builds the header block for a part: Content-Disposition, then any explicit headers, then
	 * a Content-Length when the size is known and not already supplied.  The name, filename,
	 * and header strings are sanitized so a value cannot break out of its quoted parameter or
	 * inject a header line.
	 * @param string $name The field name.
	 * @param ?string $filename The file name, or null for a plain field.
	 * @param array<string, mixed> $headers Extra headers as a name => value map.
	 * @param ?int $size The part's content length, or null when unknown.
	 * @return string The CRLF-terminated header lines.
	 */
	private function partHeaders(string $name, ?string $filename, array $headers, ?int $size): string
	{
		$disposition = 'form-data; name="' . $this->quoteParameter($name) . '"';
		if ($filename !== null) {
			$disposition .= '; filename="' . $this->quoteParameter($filename) . '"';
		}
		$lines = ['Content-Disposition: ' . $disposition];
		$seen = ['content-disposition' => true];
		foreach ($headers as $key => $value) {
			$key = str_replace(["\r", "\n", ':'], '', (string) $key);
			$lines[] = $key . ': ' . str_replace(["\r", "\n"], '', (string) $value);
			$seen[strtolower($key)] = true;
		}
		if ($size !== null && !isset($seen['content-length'])) {
			$lines[] = 'Content-Length: ' . $size;
		}
		return implode("\r\n", $lines) . "\r\n";
	}

	/**
	 * Sanitizes a quoted Content-Disposition parameter: line breaks are removed and a double
	 * quote is escaped, so the value cannot end its quoting or start a new header line.
	 * @param string $value The raw parameter value.
	 * @return string The sanitized value, safe between double quotes.
	 */
	private function quoteParameter(string $value): string
	{
		return str_replace(['\\', '"'], ['\\\\', '\\"'], str_replace(["\r", "\n"], '', $value));
	}
}
