<?php
/**
 * TDataGridPagerStyle class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TDataGridPagerStyle class.
 *
 * TDataGridPagerStyle specifies the styles available for a datagrid pager.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataGridPagerStyle extends TPanelStyle
{
	protected $_mode;
	protected $_nextText;
	protected $_prevText;
	protected $_firstText;
	protected $_lastText;
	protected $_buttonCount;
	protected $_position;
	protected $_visible;
	protected $_buttonType;

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array &$exprops
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_mode === null) {
			$exprops[] = "\0*\0_mode";
		}
		if ($this->_nextText === null) {
			$exprops[] = "\0*\0_nextText";
		}
		if ($this->_prevText === null) {
			$exprops[] = "\0*\0_prevText";
		}
		if ($this->_firstText === null) {
			$exprops[] = "\0*\0_firstText";
		}
		if ($this->_lastText === null) {
			$exprops[] = "\0*\0_lastText";
		}
		if ($this->_buttonCount === null) {
			$exprops[] = "\0*\0_buttonCount";
		}
		if ($this->_position === null) {
			$exprops[] = "\0*\0_position";
		}
		if ($this->_visible === null) {
			$exprops[] = "\0*\0_visible";
		}
		if ($this->_buttonType === null) {
			$exprops[] = "\0*\0_buttonType";
		}
	}

	/**
	 * @return TDataGridPagerMode pager mode. Defaults to TDataGridPagerMode::NextPrev.
	 */
	public function getMode()
	{
		return $this->_mode === null ? TDataGridPagerMode::NextPrev : $this->_mode;
	}

	/**
	 * @param TDataGridPagerMode $value pager mode.
	 */
	public function setMode($value)
	{
		$this->_mode = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TDataGridPagerMode');
	}

	/**
	 * @return TDataGridPagerButtonType the type of command button. Defaults to TDataGridPagerButtonType::LinkButton.
	 */
	public function getButtonType()
	{
		return $this->_buttonType === null ? TDataGridPagerButtonType::LinkButton : $this->_buttonType;
	}

	/**
	 * @param TDataGridPagerButtonType $value the type of command button
	 */
	public function setButtonType($value)
	{
		$this->_buttonType = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TDataGridPagerButtonType');
	}

	/**
	 * @return string text for the next page button. Defaults to '>'.
	 */
	public function getNextPageText()
	{
		return $this->_nextText === null ? '>' : $this->_nextText;
	}

	/**
	 * @param string $value text for the next page button.
	 */
	public function setNextPageText($value)
	{
		$this->_nextText = $value;
	}

	/**
	 * @return string text for the previous page button. Defaults to '<'.
	 */
	public function getPrevPageText()
	{
		return $this->_prevText === null ? '<' : $this->_prevText;
	}

	/**
	 * @param string $value text for the previous page button.
	 */
	public function setPrevPageText($value)
	{
		$this->_prevText = $value;
	}

	/**
	 * @return string text for the first page button. Defaults to '<<'.
	 */
	public function getFirstPageText()
	{
		return $this->_firstText === null ? '<<' : $this->_firstText;
	}

	/**
	 * @param string $value text for the first page button.
	 */
	public function setFirstPageText($value)
	{
		$this->_firstText = $value;
	}

	/**
	 * @return string text for the last page button. Defaults to '>>'.
	 */
	public function getLastPageText()
	{
		return $this->_lastText === null ? '>>' : $this->_lastText;
	}

	/**
	 * @param string $value text for the last page button.
	 */
	public function setLastPageText($value)
	{
		$this->_lastText = $value;
	}

	/**
	 * @return int maximum number of pager buttons to be displayed. Defaults to 10.
	 */
	public function getPageButtonCount()
	{
		return $this->_buttonCount === null ? 10 : $this->_buttonCount;
	}

	/**
	 * @param int $value maximum number of pager buttons to be displayed
	 * @throws TInvalidDataValueException if the value is less than 1.
	 */
	public function setPageButtonCount($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 1) {
			throw new TInvalidDataValueException('datagridpagerstyle_pagebuttoncount_invalid');
		}
		$this->_buttonCount = $value;
	}

	/**
	 * @return TDataGridPagerPosition where the pager is to be displayed. Defaults to TDataGridPagerPosition::Bottom.
	 */
	public function getPosition()
	{
		return $this->_position === null ? TDataGridPagerPosition::Bottom : $this->_position;
	}

	/**
	 * @param TDataGridPagerPosition $value where the pager is to be displayed.
	 */
	public function setPosition($value)
	{
		$this->_position = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TDataGridPagerPosition');
	}

	/**
	 * @return bool whether the pager is visible. Defaults to true.
	 */
	public function getVisible()
	{
		return $this->_visible === null ? true : $this->_visible;
	}

	/**
	 * @param bool $value whether the pager is visible.
	 */
	public function setVisible($value)
	{
		$this->_visible = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Resets the style to the original empty state.
	 */
	public function reset()
	{
		parent::reset();
		$this->_visible = null;
		$this->_position = null;
		$this->_buttonCount = null;
		$this->_prevText = null;
		$this->_nextText = null;
		$this->_mode = null;
		$this->_buttonType = null;
	}

	/**
	 * Copies the fields in a new style to this style.
	 * If a style field is set in the new style, the corresponding field
	 * in this style will be overwritten.
	 * @param TStyle $style the new style
	 */
	public function copyFrom($style)
	{
		parent::copyFrom($style);
		if ($style instanceof TDataGridPagerStyle) {
			if ($style->_visible !== null) {
				$this->_visible = $style->_visible;
			}
			if ($style->_position !== null) {
				$this->_position = $style->_position;
			}
			if ($style->_buttonCount !== null) {
				$this->_buttonCount = $style->_buttonCount;
			}
			if ($style->_prevText !== null) {
				$this->_prevText = $style->_prevText;
			}
			if ($style->_nextText !== null) {
				$this->_nextText = $style->_nextText;
			}
			if ($style->_mode !== null) {
				$this->_mode = $style->_mode;
			}
			if ($style->_buttonType !== null) {
				$this->_buttonType = $style->_buttonType;
			}
		}
	}

	/**
	 * Merges the style with a new one.
	 * If a style field is not set in this style, it will be overwritten by
	 * the new one.
	 * @param TStyle $style the new style
	 */
	public function mergeWith($style)
	{
		parent::mergeWith($style);
		if ($style instanceof TDataGridPagerStyle) {
			if ($this->_visible === null) {
				$this->_visible = $style->_visible;
			}
			if ($this->_position === null) {
				$this->_position = $style->_position;
			}
			if ($this->_buttonCount === null) {
				$this->_buttonCount = $style->_buttonCount;
			}
			if ($this->_prevText === null) {
				$this->_prevText = $style->_prevText;
			}
			if ($this->_nextText === null) {
				$this->_nextText = $style->_nextText;
			}
			if ($this->_mode === null) {
				$this->_mode = $style->_mode;
			}
			if ($this->_buttonType === null) {
				$this->_buttonType = $style->_buttonType;
			}
		}
	}
}
