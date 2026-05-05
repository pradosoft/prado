<?php

/**
 * THttpUtility class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Prado;

/**
 * THttpUtility class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THttpUtility
{
	private static $_encodeTable = ['<' => '&lt;', '>' => '&gt;', '"' => '&quot;'];
	private static $_decodeTable = ['&lt;' => '<', '&gt;' => '>', '&quot;' => '"'];
	private static $_stripTable = ['&lt;' => '', '&gt;' => '', '&quot;' => ''];

	/**
	 * HTML-encodes a string.
	 * This method translates the following characters to their corresponding
	 * HTML entities: <, >, "
	 * Note, unlike {@see htmlspecialchars}, & is not translated.
	 * @param string $s string to be encoded
	 * @return string encoded string
	 */
	public static function htmlEncode($s)
	{
		return strtr($s, self::$_encodeTable);
	}

	/**
	 * HTML-decodes a string.
	 * It is the inverse of {@see htmlEncode}.
	 * @param string $s string to be decoded
	 * @return string decoded string
	 */
	public static function htmlDecode($s)
	{
		return strtr($s, self::$_decodeTable);
	}

	/**
	 * This method strips the following characters from a string:
	 * HTML entities: <, >, "
	 * @param string $s string to be encoded
	 * @return string encoded string
	 */
	public static function htmlStrip($s)
	{
		return strtr($s, self::$_stripTable);
	}

	/**
	 * Builds an HTML attribute string from an array of name-value pairs,
	 * analogous to PHP's {@see http_build_query()} for query strings.
	 *
	 * Each entry in `$attributes` is rendered as follows:
	 * - `null` or `false` — attribute is omitted entirely.
	 * - `true` — boolean attribute rendered as just the name (e.g. `disabled`).
	 * - Any other scalar — cast to string and HTML-encoded via {@see htmlspecialchars()}.
	 *
	 * Prefixing the attribute name with `!` passes the value through directly
	 * without any HTML encoding. The `!` is stripped from the rendered name.
	 * Use this for values that are already encoded or otherwise trusted:
	 * ```php
	 * THttpUtility::buildHtmlAttributes(['!data-html' => '&amp;'])
	 * // → data-html="&amp;"   (written as-is)
	 *
	 * THttpUtility::buildHtmlAttributes(['data-html' => '&amp;'])
	 * // → data-html="&amp;amp;"  (double-encoded)
	 * ```
	 *
	 * Each attribute is prefixed with a space, so the return value can be
	 * inserted directly into a tag:
	 * ```php
	 * '<input' . THttpUtility::buildHtmlAttributes(['type' => 'text', 'disabled' => true]) . '>'
	 * // → '<input type="text" disabled>'
	 * ```
	 *
	 * Returns an empty string when `$attributes` is empty or every value is
	 * `null`/`false`.
	 *
	 * @param array $attributes associative array of attribute names to values.
	 *   Prefix a name with `!` to write its value without HTML encoding.
	 * @return string rendered attribute string, with a leading space per attribute.
	 * @since 4.3.3
	 */
	public static function buildHtmlAttributes(array $attributes): string
	{
		$html = '';
		foreach ($attributes as $name => $value) {
			if ($value === null || $value === false) {
				continue;
			}
			$raw = $name[0] === '!';
			if ($raw) {
				$name = substr($name, 1);
			}
			if ($value === true) {
				$html .= ' ' . $name;
			} elseif ($raw) {
				$html .= ' ' . $name . '="' . $value . '"';
			} else {
				$html .= ' ' . $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
			}
		}
		return $html;
	}
}
