<?php

/**
 * TEncodeTrait trait file.
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TEncodeTrait trait.
 * TEncodeTrait backs the `Encode` property of a control that renders a text property as
 * HTML.  {@see getEncode()} and {@see setEncode()} keep the property in viewstate, and
 * {@see encodeText()} applies it to the text as it is rendered.  The using class names
 * the text the property covers in its docblock: `Text` for TLabel, `Caption` for
 * TTable, `GroupingText` for TPanel.  The using class has `getViewState()` and
 * `setViewState()`, as {@see \Prado\Web\UI\TControl} and
 * {@see \Prado\Web\UI\WebControls\TDataGridColumn} do.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TEncodeTrait
{
	/**
	 * @return bool whether the text the control renders is HTML-encoded. Defaults to false.
	 */
	public function getEncode()
	{
		return $this->getViewState('Encode', false);
	}

	/**
	 * @param bool $value whether the text the control renders is HTML-encoded.
	 */
	public function setEncode($value)
	{
		$this->setViewState('Encode', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Returns the text as it is rendered: HTML-encoded by
	 * {@see \Prado\Web\THttpUtility::htmlEncode()} when {@see getEncode() Encode} is
	 * true, otherwise as is.
	 * @param string $text the text to render.
	 * @return string the text, encoded when Encode is true.
	 */
	protected function encodeText($text)
	{
		return $this->getEncode() ? THttpUtility::htmlEncode($text) : $text;
	}
}
