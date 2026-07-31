<?php

/**
 * TTableColumn class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TTableColumn class.
 *
 * TTableColumn represents the HTML `<col>` element. A `<col>` spans one or more
 * table columns for column-scoped styling and belongs to a
 * {@see TTableColumnGroup} (`<colgroup>`) in the {@see TTable::getColumnGroups
 * ColumnGroups} of a {@see TTable}.
 *
 * Properties:
 * - <b>Span</b>, int — the number of columns the element spans. Rendered as the
 *   `span` attribute when greater than 1. Defaults to `1`.
 * - <b>CssClass</b>, string — the `class` attribute. Empty string renders no attribute.
 * - <b>Width</b>, string — the column width (e.g. `'8em'`, `'20%'`), rendered
 *   into the `style` attribute. Empty string renders no width.
 * - <b>Style</b>, string — additional CSS declarations (e.g.
 *   `'background-color:#eef;border-right:1px solid #ccc'`) merged into the
 *   `style` attribute.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTableColumn extends \Prado\TComponent
{
	private $_viewState = [];

	/**
	 * Returns a viewstate value.
	 * @param string $key the name of the viewstate value to be returned
	 * @param mixed $defaultValue the default value. If $key is not found in viewstate, $defaultValue will be returned
	 * @return mixed the viewstate value corresponding to $key
	 */
	protected function getViewState($key, $defaultValue = null)
	{
		return $this->_viewState[$key] ?? $defaultValue;
	}

	/**
	 * Sets a viewstate value.
	 * @param string $key the name of the viewstate value
	 * @param mixed $value the viewstate value to be set
	 * @param null|mixed $defaultValue default value. If $value===$defaultValue, the item will be cleared from the viewstate.
	 */
	protected function setViewState($key, $value, $defaultValue = null)
	{
		if ($value === $defaultValue) {
			unset($this->_viewState[$key]);
		} else {
			$this->_viewState[$key] = $value;
		}
	}

	/**
	 * @return string the tag name of the element
	 */
	protected function getTagName()
	{
		return 'col';
	}

	/**
	 * @return int the number of columns the element spans. Defaults to 1.
	 */
	public function getSpan()
	{
		return $this->getViewState('Span', 1);
	}

	/**
	 * @param int $value the number of columns the element spans; must be 1 or greater
	 * @throws TInvalidDataValueException if the value is less than 1
	 */
	public function setSpan($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1) {
			throw new TInvalidDataValueException('tablecolumn_span_invalid', $value);
		}
		$this->setViewState('Span', $value, 1);
	}

	/**
	 * @return string the `class` attribute of the element. Defaults to ''.
	 */
	public function getCssClass()
	{
		return $this->getViewState('CssClass', '');
	}

	/**
	 * @param string $value the `class` attribute of the element
	 */
	public function setCssClass($value)
	{
		$this->setViewState('CssClass', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the column width (e.g. `'8em'`, `'20%'`). Defaults to ''.
	 */
	public function getWidth()
	{
		return $this->getViewState('Width', '');
	}

	/**
	 * @param string $value the column width, rendered into the `style` attribute
	 */
	public function setWidth($value)
	{
		$this->setViewState('Width', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string additional CSS declarations for the element. Defaults to ''.
	 */
	public function getStyle()
	{
		return $this->getViewState('Style', '');
	}

	/**
	 * @param string $value additional CSS declarations (e.g. `'background-color:#eef'`)
	 */
	public function setStyle($value)
	{
		$this->setViewState('Style', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Renders the element.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		$this->addAttributesToRender($writer);
		$writer->renderBeginTag($this->getTagName());
		$writer->renderEndTag();
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if (($span = $this->getSpan()) > 1) {
			$writer->addAttribute('span', (string) $span);
		}
		$this->addStyleAttributesToRender($writer);
	}

	/**
	 * Adds the `class` attribute and `style` declarations to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addStyleAttributesToRender($writer)
	{
		if (($cssClass = $this->getCssClass()) !== '') {
			$writer->addAttribute('class', $cssClass);
		}
		if (($width = $this->getWidth()) !== '') {
			$writer->addStyleAttribute('width', $width);
		}
		if (($style = $this->getStyle()) !== '') {
			foreach (explode(';', $style) as $declaration) {
				if (strpos($declaration, ':') !== false) {
					[$name, $value] = explode(':', $declaration, 2);
					$writer->addStyleAttribute(trim($name), trim($value));
				}
			}
		}
	}
}
