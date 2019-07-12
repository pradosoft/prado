<?php
/**
 * TAccordion class file.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 * @since 3.2
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * Class TAccordionView.
 *
 * TAccordionView represents a single view in a {@link TAccordion}.
 *
 * TAccordionView is represented inside the {@link TAccordion} with an header label whose text is defined by
 * the {@link setCaption Caption} property; optionally the label can be an hyperlink: use the
 * {@link setNavigateUrl NavigateUrl} property to define the destination url.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @package Prado\Web\UI\WebControls
 * @since 3.2
 */
class TAccordionView extends \Prado\Web\UI\WebControls\TWebControl
{
	private $_active = false;

	/**
	 * @return string the tag name for the view element
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		if (!$this->getActive() && $this->getPage()->getClientSupportsJavaScript()) {
			$this->getStyle()->setStyleField('display', 'none');
		}

		$this->getStyle()->mergeWith($this->getParent()->getViewStyle());

		parent::addAttributesToRender($writer);

		$writer->addAttribute('id', $this->getClientID());
	}

	/**
	 * @return string the caption displayed on this header. Defaults to ''.
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption', '');
	}

	/**
	 * @param string $value the caption displayed on this header
	 */
	public function setCaption($value)
	{
		$this->setViewState('Caption', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the URL of the target page. Defaults to ''.
	 */
	public function getNavigateUrl()
	{
		return $this->getViewState('NavigateUrl', '');
	}

	/**
	 * Sets the URL of the target page.
	 * If not empty, clicking on this header will redirect the browser to the specified URL.
	 * @param string $value the URL of the target page.
	 */
	public function setNavigateUrl($value)
	{
		$this->setViewState('NavigateUrl', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the text content displayed on this view. Defaults to ''.
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * Sets the text content to be displayed on this view.
	 * If this is not empty, the child content of the view will be ignored.
	 * @param string $value the text content displayed on this view
	 */
	public function setText($value)
	{
		$this->setViewState('Text', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether this accordion view is active. Defaults to false.
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param bool $value whether this accordion view is active.
	 */
	public function setActive($value)
	{
		$this->_active = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Renders body contents of the accordion view.
	 * @param THtmlWriter $writer the writer used for the rendering purpose.
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getText()) !== '') {
			$writer->write($text);
		} elseif ($this->getHasControls()) {
			parent::renderContents($writer);
		}
	}

	/**
	 * Renders the header associated with the accordion view.
	 * @param THtmlWriter $writer the writer for rendering purpose.
	 */
	public function renderHeader($writer)
	{
		if ($this->getVisible(false) && $this->getPage()->getClientSupportsJavaScript()) {
			$writer->addAttribute('id', $this->getClientID() . '_0');

			$style = $this->getActive() ? $this->getParent()->getActiveHeaderStyle() : $this->getParent()->getHeaderStyle();

			$style->addAttributesToRender($writer);

			$writer->renderBeginTag($this->getTagName());

			$this->renderHeaderContent($writer);

			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the content in the header.
	 * By default, a hyperlink is displayed.
	 * @param THtmlWriter $writer the HTML writer
	 */
	protected function renderHeaderContent($writer)
	{
		$url = $this->getNavigateUrl();
		if (($caption = $this->getCaption()) === '') {
			$caption = '&nbsp;';
		}

		if ($url != '') {
			$writer->write("<a href=\"{$url}\">");
		}
		$writer->write("{$caption}");
		if ($url != '') {
			$writer->write("</a>");
		}
	}
}
