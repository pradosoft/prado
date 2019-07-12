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
 * @package Prado\Web\Javascripts
 */

namespace Prado\Web\Javascripts;

/**
 * TJavaScriptString class is an internal class that marks strings that will be
 * forcibly encoded when rendered inside a javascript block
 *
 * @package Prado\Web\Javascripts
 * @since 3.2.0
 */
class TJavaScriptString extends TJavaScriptLiteral
{
	public function toJavaScriptLiteral()
	{
		return TJavaScript::jsonEncode((string) $this->_s, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG);
	}
}
