<?php
/**
 * TPanel class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TPanel class
 *
 * TPanel represents a component that acts as a container for other component.
 * It is especially useful when you want to generate components programmatically
 * or hide/show a group of components.
 *
 * By default, TPanel displays a &lt;div&gt; element on a page.
 * Children of TPanel are displayed as the body content of the element.
 * The property {@link setWrap Wrap} can be used to set whether the body content
 * should wrap or not. {@link setHorizontalAlign HorizontalAlign} governs how
 * the content is aligned horizontally, and {@link getDirection Direction} indicates
 * the content direction (left to right or right to left). You can set
 * {@link setBackImageUrl BackImageUrl} to give a background image to the panel,
 * and you can ste {@link setGroupingText GroupingText} so that the panel is
 * displayed as a field set with a legend text. Finally, you can specify
 * a default button to be fired when users press 'return' key within the panel
 * by setting the {@link setDefaultButton DefaultButton} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TPanel extends \Prado\Web\UI\WebControls\TWebControl
{
	/**
	 * @var string ID path to the default button
	 */
	private $_defaultButton = '';

	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Creates a style object to be used by the control.
	 * This method overrides the parent impementation by creating a TPanelStyle object.
	 * @return TPanelStyle the style used by TPanel.
	 */
	protected function createStyle()
	{
		return new TPanelStyle;
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter $writer the renderer
	 * @throws TInvalidDataValueException if default button is not right.
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($butt = $this->getDefaultButton()) !== '') {
			$writer->addAttribute('id', $this->getClientID());
		}
	}

	/**
	 * @return bool whether the content wraps within the panel. Defaults to true.
	 */
	public function getWrap()
	{
		return $this->getStyle()->getWrap();
	}

	/**
	 * Sets the value indicating whether the content wraps within the panel.
	 * @param bool $value whether the content wraps within the panel.
	 */
	public function setWrap($value)
	{
		$this->getStyle()->setWrap($value);
	}

	/**
	 * @return string the horizontal alignment of the contents within the panel, defaults to 'NotSet'.
	 */
	public function getHorizontalAlign()
	{
		return $this->getStyle()->getHorizontalAlign();
	}

	/**
	 * Sets the horizontal alignment of the contents within the panel.
	 * Valid values include 'NotSet', 'Justify', 'Left', 'Right', 'Center'
	 * @param string $value the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	/**
	 * @return string the URL of the background image for the panel component.
	 */
	public function getBackImageUrl()
	{
		return $this->getStyle()->getBackImageUrl();
	}

	/**
	 * Sets the URL of the background image for the panel component.
	 * @param string $value the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->getStyle()->setBackImageUrl($value);
	}

	/**
	 * @return string alignment of the content in the panel. Defaults to 'NotSet'.
	 */
	public function getDirection()
	{
		return $this->getStyle()->getDirection();
	}

	/**
	 * @param string $value alignment of the content in the panel.
	 * Valid values include 'NotSet', 'LeftToRight', 'RightToLeft'.
	 */
	public function setDirection($value)
	{
		$this->getStyle()->setDirection($value);
	}

	/**
	 * @return string the ID path to the default button. Defaults to empty.
	 */
	public function getDefaultButton()
	{
		return $this->_defaultButton;
	}

	/**
	 * Specifies the default button for the panel.
	 * The default button will be fired (clicked) whenever a user enters 'return'
	 * key within the panel.
	 * The button must be locatable via the function call {@link TControl::findControl findControl}.
	 * @param string $value the ID path to the default button.
	 */
	public function setDefaultButton($value)
	{
		$this->_defaultButton = $value;
	}

	/**
	 * @return string the legend text when the panel is used as a fieldset. Defaults to empty.
	 */
	public function getGroupingText()
	{
		return $this->getViewState('GroupingText', '');
	}

	/**
	 * @param string $value the legend text. If this value is not empty, the panel will be rendered as a fieldset.
	 */
	public function setGroupingText($value)
	{
		$this->setViewState('GroupingText', $value, '');
	}

	/**
	 * @return string the visibility and position of scroll bars in a panel control, defaults to None.
	 */
	public function getScrollBars()
	{
		return $this->getStyle()->getScrollBars();
	}

	/**
	 * @param string $value the visibility and position of scroll bars in a panel control.
	 * Valid values include None, Auto, Both, Horizontal and Vertical.
	 */
	public function setScrollBars($value)
	{
		$this->getStyle()->setScrollBars($value);
	}

	/**
	 * Renders the openning tag for the control (including attributes)
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		parent::renderBeginTag($writer);
		if (($text = $this->getGroupingText()) !== '') {
			$writer->renderBeginTag('fieldset');
			$writer->renderBeginTag('legend');
			$writer->write($text);
			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the closing tag for the control
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderEndTag($writer)
	{
		if ($this->getGroupingText() !== '') {
			$writer->renderEndTag();
		}
		parent::renderEndTag($writer);
	}

	public function render($writer)
	{
		parent::render($writer);

		if (($butt = $this->getDefaultButton()) !== '') {
			if (($button = $this->findControl($butt)) === null) {
				throw new TInvalidDataValueException('panel_defaultbutton_invalid', $butt);
			} else {
				$this->getPage()->getClientScript()->registerDefaultButton($this, $button);
			}
		}
	}
}
