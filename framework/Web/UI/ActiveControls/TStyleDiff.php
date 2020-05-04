<?php
/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TStyle;

/**
 * TStyleDiff class.
 *
 * Calculates the changes to the Style properties.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TStyleDiff extends TViewStateDiff
{
	/**
	 * @param TStyle $obj control style
	 * @return array all the style properties combined.
	 */
	protected function getCombinedStyle($obj)
	{
		if (!($obj instanceof TStyle)) {
			return [];
		}
		$style = $obj->getStyleFields();
		$style = array_merge($style, $this->getStyleFromString($obj->getCustomStyle()));
		if ($obj->hasFont()) {
			$style = array_merge($style, $this->getStyleFromString($obj->getFont()->toString()));
		}
		return $style;
	}

	/**
	 * @param string $string CSS custom style string.
	 * @return array $string CSS style as name-value array.
	 */
	protected function getStyleFromString($string)
	{
		$style = [];
		if (!is_string($string)) {
			return $style;
		}

		foreach (explode(';', $string) as $sub) {
			$arr = explode(':', $sub);
			if (isset($arr[1]) && trim($arr[0]) !== '') {
				$style[trim($arr[0])] = trim($arr[1]);
			}
		}
		return $style;
	}

	/**
	 * @return string changes to the CSS class name.
	 */
	protected function getCssClassDiff()
	{
		if ($this->_old === null) {
			return ($this->_new !== null) && $this->_new->hasCssClass()
						? $this->_new->getCssClass() : null;
		} else {
			return $this->_old->getCssClass() !== $this->_new->getCssClass() ?
				$this->_new->getCssClass() : null;
		}
	}

	/**
	 * @return array list of changes to the control style.
	 */
	protected function getStyleDiff()
	{
		$diff = array_diff_assoc(
			$this->getCombinedStyle($this->_new),
			$this->getCombinedStyle($this->_old)
		);
		return count($diff) > 0 ? $diff : null;
	}

	/**
	 * @return array list of changes to the control style and CSS class name.
	 */
	public function getDifference()
	{
		if ($this->_new === null) {
			return $this->_null;
		}

		$css = $this->getCssClassDiff();
		$style = $this->getStyleDiff();
		if (($css !== null) || ($style !== null)) {
			return ['CssClass' => $css, 'Style' => $style];
		}

		return $this->_null;
	}
}
