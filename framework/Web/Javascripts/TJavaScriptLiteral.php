<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

/**
 * TJavaScriptLiteral class that encloses string literals that are not
 * supposed to be escaped by {@see TJavaScript::encode() }
 *
 * Since Prado 3.2 all the data that gets sent clientside inside a javascript statement
 * is encoded by default to avoid any kind of injection.
 * Sometimes there's the need to bypass this encoding and send raw javascript code.
 * To ensure that a string doesn't get encoded by {@see TJavaScript::encode() },
 * construct a new TJavaScriptLiteral:
 * ```php
 * // a javascript test string
 * $js="alert('hello')";
 * // the string in $raw will not be encoded when sent clientside inside a javascript block
 * $raw=new TJavaScriptLiteral($js);
 * // shortened form
 * $raw=_js($js);
 * ```
 *
 * @since 3.2.0
 */
class TJavaScriptLiteral
{
	protected $_s;

	public function __construct($s)
	{
		$this->_s = $s;
	}

	public function __toString()
	{
		return (string) $this->_s;
	}

	public function toJavaScriptLiteral()
	{
		return $this->__toString();
	}
}
