<?php
/**
 * TStyle class file.
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
 * TTableStyle class.
 * TTableStyle represents the CSS style specific for HTML table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TTableStyle extends TStyle
{
	/**
	 * @var TVerticalAlign the URL of the background image for the table
	 */
	protected $_backImageUrl;
	/**
	 * @var THorizontalAlign horizontal alignment of the contents within the table
	 */
	protected $_horizontalAlign;
	/**
	 * @var int cellpadding of the table
	 */
	protected $_cellPadding;
	/**
	 * @var int cellspacing of the table
	 */
	protected $_cellSpacing;
	/**
	 * @var TTableGridLines grid line setting of the table
	 */
	protected $_gridLines;
	/**
	 * @var bool whether the table border should be collapsed
	 */
	protected $_borderCollapse;

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
		if ($this->_backImageUrl === null) {
			$exprops[] = "\0*\0_backImageUrl";
		}
		if ($this->_horizontalAlign === null) {
			$exprops[] = "\0*\0_horizontalAlign";
		}
		if ($this->_cellPadding === null) {
			$exprops[] = "\0*\0_cellPadding";
		}
		if ($this->_cellSpacing === null) {
			$exprops[] = "\0*\0_cellSpacing";
		}
		if ($this->_gridLines === null) {
			$exprops[] = "\0*\0_gridLines";
		}
		if ($this->_borderCollapse === null) {
			$exprops[] = "\0*\0_borderCollapse";
		}
	}

	/**
	 * Sets the style attributes to default values.
	 * This method overrides the parent implementation by
	 * resetting additional TTableStyle specific attributes.
	 */
	public function reset()
	{
		$this->_backImageUrl = null;
		$this->_horizontalAlign = null;
		$this->_cellPadding = null;
		$this->_cellSpacing = null;
		$this->_gridLines = null;
		$this->_borderCollapse = null;
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
		if ($style instanceof TTableStyle) {
			if ($style->_backImageUrl !== null) {
				$this->_backImageUrl = $style->_backImageUrl;
			}
			if ($style->_horizontalAlign !== null) {
				$this->_horizontalAlign = $style->_horizontalAlign;
			}
			if ($style->_cellPadding !== null) {
				$this->_cellPadding = $style->_cellPadding;
			}
			if ($style->_cellSpacing !== null) {
				$this->_cellSpacing = $style->_cellSpacing;
			}
			if ($style->_gridLines !== null) {
				$this->_gridLines = $style->_gridLines;
			}
			if ($style->_borderCollapse !== null) {
				$this->_borderCollapse = $style->_borderCollapse;
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
		if ($style instanceof TTableStyle) {
			if ($this->_backImageUrl === null && $style->_backImageUrl !== null) {
				$this->_backImageUrl = $style->_backImageUrl;
			}
			if ($this->_horizontalAlign === null && $style->_horizontalAlign !== null) {
				$this->_horizontalAlign = $style->_horizontalAlign;
			}
			if ($this->_cellPadding === null && $style->_cellPadding !== null) {
				$this->_cellPadding = $style->_cellPadding;
			}
			if ($this->_cellSpacing === null && $style->_cellSpacing !== null) {
				$this->_cellSpacing = $style->_cellSpacing;
			}
			if ($this->_gridLines === null && $style->_gridLines !== null) {
				$this->_gridLines = $style->_gridLines;
			}
			if ($this->_borderCollapse === null && $style->_borderCollapse !== null) {
				$this->_borderCollapse = $style->_borderCollapse;
			}
		}
	}


	/**
	 * Adds attributes related to CSS styles to renderer.
	 * This method overrides the parent implementation.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function addAttributesToRender($writer)
	{
		if (($url = trim($this->getBackImageUrl())) !== '') {
			$writer->addStyleAttribute('background-image', 'url(' . $url . ')');
		}

		if (($horizontalAlign = $this->getHorizontalAlign()) !== THorizontalAlign::NotSet) {
			$writer->addStyleAttribute('text-align', strtolower($horizontalAlign));
		}

		if (($cellPadding = $this->getCellPadding()) >= 0) {
			$writer->addAttribute('cellpadding', "$cellPadding");
		}

		if (($cellSpacing = $this->getCellSpacing()) >= 0) {
			$writer->addAttribute('cellspacing', "$cellSpacing");
		}

		if ($this->getBorderCollapse()) {
			$writer->addStyleAttribute('border-collapse', 'collapse');
		}

		switch ($this->getGridLines()) {
			case TTableGridLines::Horizontal: $writer->addAttribute('rules', 'rows'); break;
			case TTableGridLines::Vertical: $writer->addAttribute('rules', 'cols'); break;
			case TTableGridLines::Both: $writer->addAttribute('rules', 'all'); break;
		}

		parent::addAttributesToRender($writer);
	}

	/**
	 * @return string the URL of the background image for the table
	 */
	public function getBackImageUrl()
	{
		return $this->_backImageUrl === null ? '' : $this->_backImageUrl;
	}

	/**
	 * Sets the URL of the background image for the table
	 * @param string $value the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->_backImageUrl = $value;
	}

	/**
	 * @return THorizontalAlign the horizontal alignment of the contents within the table, defaults to THorizontalAlign::NotSet.
	 */
	public function getHorizontalAlign()
	{
		return $this->_horizontalAlign === null ? THorizontalAlign::NotSet : $this->_horizontalAlign;
	}

	/**
	 * Sets the horizontal alignment of the contents within the table.
	 * @param THorizontalAlign $value the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->_horizontalAlign = TPropertyValue::ensureEnum($value, 'THorizontalAlign');
	}

	/**
	 * @return int cellpadding of the table. Defaults to -1, meaning not set.
	 * @deprecated use border-collapse CSS property with its value set to collapse, and the padding property to the <td> element.
	 */
	public function getCellPadding()
	{
		return $this->_cellPadding === null ? -1 : $this->_cellPadding;
	}

	/**
	 * @param int $value cellpadding of the table. A value equal to -1 clears up the setting.
	 * @throws TInvalidDataValueException if the value is less than -1.
	 * @deprecated use border-collapse CSS property with its value set to collapse, and the padding property to the <td> element.
	 */
	public function setCellPadding($value)
	{
		if (($this->_cellPadding = TPropertyValue::ensureInteger($value)) < -1) {
			throw new TInvalidDataValueException('tablestyle_cellpadding_invalid');
		}
	}

	/**
	 * @return int cellspacing of the table. Defaults to -1, meaning not set.
	 * @deprecated use the border-spacing CSS property instead
	 */
	public function getCellSpacing()
	{
		return $this->_cellSpacing === null ? -1 : $this->_cellSpacing;
	}

	/**
	 * @param int $value cellspacing of the table. A value equal to -1 clears up the setting.
	 * @throws TInvalidDataValueException if the value is less than -1.
	 * @deprecated use the border-spacing CSS property instead
	 */
	public function setCellSpacing($value)
	{
		if (($this->_cellSpacing = TPropertyValue::ensureInteger($value)) < -1) {
			throw new TInvalidDataValueException('tablestyle_cellspacing_invalid');
		}
	}

	/**
	 * @return TTableGridLines the grid line setting of the table. Defaults to TTableGridLines::None.
	 * @deprecated use CSS to style the borders of individual elements
	 */
	public function getGridLines()
	{
		return $this->_gridLines === null ? TTableGridLines::None : $this->_gridLines;
	}

	/**
	 * Sets the grid line style of the table.
	 * @param TTableGridLines $value the grid line setting of the table
	 * @deprecated use CSS to style the borders of individual elements
	 */
	public function setGridLines($value)
	{
		$this->_gridLines = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TTableGridLines');
	}


	/**
	 * @return bool whether the table borders should be collapsed. Defaults to false.
	 */
	public function getBorderCollapse()
	{
		return $this->_borderCollapse === null ? false : $this->_borderCollapse;
	}

	/**
	 * @param bool $value whether the table borders should be collapsed.
	 */
	public function setBorderCollapse($value)
	{
		$this->_borderCollapse = TPropertyValue::ensureBoolean($value);
	}
}
