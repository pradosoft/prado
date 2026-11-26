<?php

/**
 * TTestRecordingResponse class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web;

/**
 * TTestRecordingResponse is a {@see TTestCookieResponse} that records what it writes
 * and the headers it sends instead of emitting them.
 *
 * {@see $written} holds the text passed to {@see write()}, and {@see $headers} holds
 * each `[header, replace, responseCode]` passed to {@see appendHeader()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestRecordingResponse extends TTestCookieResponse
{
	/**
	 * @var string the text written to the response
	 */
	public string $written = '';

	/**
	 * @var array the `[header, replace, responseCode]` of each header sent
	 */
	public array $headers = [];

	/**
	 * Records the text instead of echoing it.
	 * @param string $str the text
	 */
	public function write($str)
	{
		$this->written .= $str;
	}

	/**
	 * Records the header instead of calling `header()`.
	 * @param string $header the header
	 * @param bool $replace whether the header replaces a previous one of its type
	 * @param int $response_code the HTTP response code
	 */
	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		$this->headers[] = [$header, $replace, $response_code];
	}
}
