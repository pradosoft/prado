<?php
/**
 * THtmlElement class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */
namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * THtmlElement class.
 *
 * THtmlElement represents a generic HTML element whose tag name is specified
 * via {@link setTagName TagName} property. Because THtmlElement extends from
 * {@link TWebControl}, it enjoys all its functionalities.
 *
 * To change the default tag your subclass should override {@link getDefaultTagName}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <javalizard@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.2
 */
class THtmlElement extends \Prado\Web\UI\WebControls\TWebControl
{
	/**
	 * @var the tag of this element
	 */
	private $_tagName;

	/**
	 * @return string the tag name of this control. Defaults to 'span'.
	 */
	public function getTagName()
	{
		return ($this->_tagName !== null) ? $this->_tagName : ($this->_tagName = $this->getDefaultTagName());
	}

	/**
	 * @param string $value the tag name of this control.
	 */
	public function setTagName($value)
	{
		$this->_tagName = TPropertyValue::ensureString($value);
	}

	/**
	 *	This is the default tag when no other is specified
	 * @return string the default tag
	 */
	public function getDefaultTagName()
	{
		return 'span';
	}

	/**
	 * This tells you if this TagName has deviated from the original
	 * @return bool true if TagName has deviated from the default.
	 */
	public function getIsMutated()
	{
		return $this->_tagName !== null && $this->_tagName != $this->getDefaultTagName();
	}
}
