<?php

/**
 * TLiteral class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TLiteral class
 *
 * TLiteral displays a static text on the Web page.
 * TLiteral is similar to the TLabel control, except that the TLiteral
 * control does not have style properties (e.g. BackColor, Font, etc.)
 * You can programmatically control the text displayed in the control by setting
 * the {@see setText Text} property. The text displayed may be HTML-encoded
 * if the {@see setEncode Encode} property is set true (defaults to false).
 *
 * TLiteral will render the contents enclosed within its component tag
 * if {@see setText Text} is empty.
 *
 * Note, if {@see setEncode Encode} is false, make sure {@see setText Text}
 * does not contain unwanted characters that may bring security vulnerabilities.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TLiteral extends \Prado\Web\UI\TControl implements \Prado\IDataRenderer
{
	/**
	 * @return string the static text of the TLiteral
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * Sets the static text of the TLiteral
	 * @param string $value the text to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Returns the static text of the TLiteral.
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see getText()}.
	 * @return string the static text of the TLiteral
	 * @see getText
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->getText();
	}

	/**
	 * Sets the static text of the TLiteral.
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see setText()}.
	 * @param string $value the static text of the TLiteral
	 * @see setText
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setText($value);
	}

	/**
	 * @return bool whether the rendered text should be HTML-encoded. Defaults to false.
	 */
	public function getEncode()
	{
		return $this->getViewState('Encode', false);
	}

	/**
	 * @param bool $value whether the rendered text should be HTML-encoded.
	 */
	public function setEncode($value)
	{
		$this->setViewState('Encode', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Renders the literal control.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if (($text = $this->getText()) !== '') {
			if ($this->getEncode()) {
				$writer->write(THttpUtility::htmlEncode($text));
			} else {
				$writer->write($text);
			}
		} else {
			parent::render($writer);
		}
	}
}
