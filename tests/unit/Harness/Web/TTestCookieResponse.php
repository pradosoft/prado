<?php

/**
 * TTestCookieResponse class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web;

use Prado\Web\THttpResponse;

/**
 * TTestCookieResponse is a {@see THttpResponse} that records its `Set-Cookie` calls
 * instead of emitting them, which `setcookie()` cannot do in the CLI test runner.
 *
 * {@see getCookies()} holds the cookies added, and {@see $setCookies} holds each
 * `[name, value, options]` passed to `setcookie()`, with the value the browser
 * receives: signed when `EnableCookieValidation` is on.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestCookieResponse extends THttpResponse
{
	/**
	 * @var array the `[name, value, options]` of each cookie sent
	 */
	public array $setCookies = [];

	/**
	 * Records the cookie instead of calling `setcookie()`.
	 * @param string $name the cookie name
	 * @param mixed ...$args the value and options
	 * @return bool true
	 */
	protected function responseSetCookie(string $name, ...$args): bool
	{
		$this->setCookies[] = [$name, ...$args];
		return true;
	}
}
