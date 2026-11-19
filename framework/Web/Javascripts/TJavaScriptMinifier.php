<?php

/**
 * TJavaScriptMinifier class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

/**
 * TJavaScriptMinifier class
 *
 * TJavaScriptMinifier is the default {@see IMinifier} of {@see TJavaScript::minify()}.
 * It minifies JavaScript with Douglas Crockford's JSMin algorithm from the
 * `mrclay/jsmin-php` package, as {@see TJavaScript::JSMin()} does.  JSMin throws a
 * `\JSMin\UnterminatedStringException`, `\JSMin\UnterminatedCommentException`, or
 * `\JSMin\UnterminatedRegExpException` for source it cannot parse.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TJavaScriptMinifier implements IMinifier
{
	/**
	 * Minifies JavaScript with JSMin.
	 * @param string $code The JavaScript source.
	 * @return string The minified JavaScript.
	 */
	public function minify(string $code): string
	{
		return \JSMin\JSMin::minify($code);
	}
}
