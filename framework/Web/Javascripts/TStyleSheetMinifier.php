<?php

/**
 * TStyleSheetMinifier class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

/**
 * TStyleSheetMinifier class
 *
 * TStyleSheetMinifier is the default {@see IMinifier} of {@see TStyleSheetHelper::minify()}.
 * It removes comments and whitespace from CSS and leaves every other byte in place,
 * so the result has the meaning of the source.  It reads strings, escapes, and
 * unquoted `url()` values as single tokens and copies them unchanged.
 *
 * | source | result |
 * |---|---|
 * | a comment | removed; it separates the tokens on either side as a space does |
 * | a comment that opens with `/*!` | kept, for license notices |
 * | a whitespace run | one space, or none where the table below allows |
 * | `;` before `}`, or a repeated `;` | removed |
 *
 * A space is removed next to these characters:
 *
 * | character | where |
 * |---|---|
 * | `{` `}` `;` `,` | either side |
 * | `:` | after; the space before is kept, since `a :hover` and `a:hover` differ |
 * | `!` | before |
 * | `(` | after; the space before is kept, since `and (` and `and(` differ |
 * | `)` | before |
 * | `>` `+` `~` | either side outside parentheses, where they are selector combinators; inside, `calc()` and media ranges need the spaces |
 *
 * The minifier works on bytes and changes only ASCII, so UTF-8 text passes through.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStyleSheetMinifier implements IMinifier
{
	/** The CSS whitespace characters. */
	public const WHITESPACE = " \t\n\r\f";

	/**
	 * Minifies CSS.
	 * @param string $code The CSS source.
	 * @return string The minified CSS.
	 */
	public function minify(string $code): string
	{
		$out = '';
		$length = strlen($code);
		$space = false;
		$semicolon = false;
		$depth = 0;
		for ($i = 0; $i < $length; $i++) {
			$c = $code[$i];
			if ($c === '/' && ($code[$i + 1] ?? '') === '*') {
				$end = strpos($code, '*/', $i + 2);
				$end = $end === false ? $length : $end + 2;
				if (($code[$i + 2] ?? '') === '!') {
					$this->flush($out, $space, $semicolon, '/', $depth);
					$out .= substr($code, $i, $end - $i);
				} else {
					$space = true;
				}
				$i = $end - 1;
				continue;
			}
			if (strpos(self::WHITESPACE, $c) !== false) {
				$space = true;
				continue;
			}
			if ($c === ';') {
				$space = false;
				$semicolon = true;
				continue;
			}
			if ($c === '}') {
				$semicolon = false;
			}
			$this->flush($out, $space, $semicolon, $c, $depth);
			if ($c === '"' || $c === "'") {
				$end = $this->stringEnd($code, $i, $c);
				$out .= substr($code, $i, $end - $i);
				$i = $end - 1;
			} elseif ($c === '\\') {
				$out .= substr($code, $i, 2);
				$i++;
			} elseif (($end = $this->unquotedUrlEnd($code, $i, $out)) !== null) {
				$out .= substr($code, $i, $end - $i);
				$i = $end - 1;
			} else {
				if ($c === '(') {
					$depth++;
				} elseif ($c === ')' && $depth > 0) {
					$depth--;
				}
				$out .= $c;
			}
		}
		if ($semicolon) {
			$out .= ';';
		}
		return $out;
	}

	/**
	 * Writes the pending semicolon and whitespace before the next character.
	 * @param string $out The minified output, appended to.
	 * @param bool $space Whether whitespace is pending; reset.
	 * @param bool $semicolon Whether a semicolon is pending; reset.
	 * @param string $next The next character written.
	 * @param int $depth The parenthesis depth.
	 */
	protected function flush(string &$out, bool &$space, bool &$semicolon, string $next, int $depth): void
	{
		if ($semicolon) {
			$out .= ';';
			$semicolon = false;
			$space = false;
		}
		if ($space && $this->keepsSpace(substr($out, -1), $next, $depth)) {
			$out .= ' ';
		}
		$space = false;
	}

	/**
	 * Indicates whether whitespace between two characters is kept as one space.
	 * @param string $prev The character before the whitespace, '' at the start.
	 * @param string $next The character after the whitespace.
	 * @param int $depth The parenthesis depth.
	 * @return bool Whether the space is kept.
	 */
	protected function keepsSpace(string $prev, string $next, int $depth): bool
	{
		if ($prev === '' || strpbrk($prev . $next, '{};,') !== false) {
			return false;
		}
		if ($prev === ':' || $prev === '(' || $next === ')' || $next === '!') {
			return false;
		}
		return $depth > 0 || strpbrk($prev . $next, '>+~') === false;
	}

	/**
	 * Returns the offset after a quoted string, its escapes included.  An unterminated
	 * string runs to the end of the source.
	 * @param string $code The CSS source.
	 * @param int $start The offset of the opening quote.
	 * @param string $quote The quote character.
	 * @return int The offset after the closing quote.
	 */
	protected function stringEnd(string $code, int $start, string $quote): int
	{
		$length = strlen($code);
		for ($i = $start + 1; $i < $length; $i++) {
			if ($code[$i] === '\\') {
				$i++;
			} elseif ($code[$i] === $quote) {
				return $i + 1;
			}
		}
		return $length;
	}

	/**
	 * Returns the offset after an unquoted `url(...)` token that starts at $start.  A
	 * quoted `url("...")` reads as a function and a string, so it returns null.
	 * @param string $code The CSS source.
	 * @param int $start The offset of the candidate token.
	 * @param string $out The minified output, whose last character precedes the token.
	 * @return ?int The offset after the closing parenthesis, or null when no unquoted
	 *   `url(` starts at $start.
	 */
	protected function unquotedUrlEnd(string $code, int $start, string $out): ?int
	{
		if (strncasecmp(substr($code, $start, 4), 'url(', 4) !== 0 || preg_match('/[\w-]$/', $out)) {
			return null;
		}
		$i = $start + 4 + strspn($code, self::WHITESPACE, $start + 4);
		if (($code[$i] ?? '') === '"' || ($code[$i] ?? '') === "'") {
			return null;
		}
		$length = strlen($code);
		for (; $i < $length; $i++) {
			if ($code[$i] === '\\') {
				$i++;
			} elseif ($code[$i] === ')') {
				return $i + 1;
			}
		}
		return $length;
	}
}
