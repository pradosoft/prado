<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado
 */

namespace Prado;

/**
 * TJavaScriptString class is an internal class that marks strings that will be
 * forcibly encoded when rendered inside a javascript block
 *
 * @package Prado
 * @since 3.2.0
 */
class TJavaScriptString extends TJavaScriptLiteral
{
	public function toJavaScriptLiteral()
	{
		return TJavaScript::jsonEncode((string)$this->_s,JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG);
	}
}