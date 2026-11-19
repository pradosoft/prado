<?php

/**
 * IMinifier interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

/**
 * IMinifier interface
 *
 * A minifier removes the comments and whitespace that source text does not need and
 * returns text with the same meaning.  {@see TJavaScript::minify()} runs the minifier
 * of {@see TJavaScript::setMinifier()}, {@see TJavaScriptMinifier} by default, and
 * {@see TStyleSheetHelper::minify()} runs the minifier of
 * {@see TStyleSheetHelper::setMinifier()}, {@see TStyleSheetMinifier} by default.
 * An application replaces either with its own implementation.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IMinifier
{
	/**
	 * Minifies source text.
	 * @param string $code The source text.
	 * @return string The minified text.
	 */
	public function minify(string $code): string;
}
