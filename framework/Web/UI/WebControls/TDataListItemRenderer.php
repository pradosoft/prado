<?php
/**
 * TDataListItemRenderer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDataListItemRenderer class
 *
 * TDataListItemRenderer can be used as a convenient base class to
 * define an item renderer class specific for {@link TDataList}.
 *
 * TDataListItemRenderer extends {@link TItemDataRenderer} and implements
 * the bubbling scheme for the OnCommand event of data list items.
 *
 * TDataListItemRenderer also implements the {@link IStyleable} interface,
 * which allows TDataList to apply CSS styles to the renders.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.0
 */
class TDataListItemRenderer extends TItemDataRenderer implements IStyleable
{
	/**
	 * Creates a style object to be used by the control.
	 * This method may be overriden by controls to provide customized style.
	 * @return TStyle
	 */
	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	/**
	 * @return bool whether the control has defined any style information
	 */
	public function getHasStyle()
	{
		return $this->getViewState('Style', null) !== null;
	}

	/**
	 * @return TStyle the object representing the css style of the control
	 */
	public function getStyle()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style;
		} else {
			$style = $this->createStyle();
			$this->setViewState('Style', $style, null);
			return $style;
		}
	}

	/**
	 * Removes all style data.
	 */
	public function clearStyle()
	{
		$this->clearViewState('Style');
	}

	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param TControl $sender the sender of the event
	 * @param TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof \Prado\Web\UI\TCommandEventParameter) {
			$this->raiseBubbleEvent($this, new TDataListCommandEventParameter($this, $sender, $param));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the tag name used for this control.
	 * By default, the tag name is 'span'.
	 * You can override this method to provide customized tag names.
	 * If the tag name is empty, the opening and closing tag will NOT be rendered.
	 * @return string tag name of the control to be rendered
	 */
	protected function getTagName()
	{
		return 'span';
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * By default, this method renders the style string.
	 * The method can be overriden to provide customized attribute rendering.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($style = $this->getViewState('Style', null)) {
			$style->addAttributesToRender($writer);
		}
	}

	/**
	 * Renders the control.
	 * This method overrides the parent implementation by replacing it with
	 * the following sequence:
	 * - {@link renderBeginTag}
	 * - {@link renderContents}
	 * - {@link renderEndTag}
	 * If the {@link getTagName TagName} is empty, only {@link renderContents} is invoked.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getTagName() !== '') {
			$this->renderBeginTag($writer);
			$this->renderContents($writer);
			$this->renderEndTag($writer);
		} else {
			$this->renderContents($writer);
		}
	}

	/**
	 * Renders the openning tag for the control (including attributes)
	 * This method is invoked when {@link getTagName TagName} is not empty.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		$this->addAttributesToRender($writer);
		$writer->renderBeginTag($this->getTagName());
	}

	/**
	 * Renders the body content enclosed between the control tag.
	 * By default, child controls and text strings will be rendered.
	 * You can override this method to provide customized content rendering.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		parent::renderChildren($writer);
	}

	/**
	 * Renders the closing tag for the control
	 * This method is invoked when {@link getTagName TagName} is not empty.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderEndTag($writer)
	{
		$writer->renderEndTag();
	}
}
