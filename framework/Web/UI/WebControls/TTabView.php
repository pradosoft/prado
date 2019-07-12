<?php
/**
 * TTabPanel class file.
 *
 * @author Tomasz Wolny <tomasz.wolny@polecam.to.pl> and Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 * @since 3.1.1
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TTabView class.
 *
 * TTabView represents a view in a {@link TTabPanel} control.
 *
 * The content in a TTabView can be specified by the {@link setText Text} property
 * or its child controls. In template syntax, the latter means enclosing the content
 * within the TTabView component element. If both are set, {@link getText Text} takes precedence.
 *
 * Each TTabView is associated with a tab in the tab bar of the TTabPanel control.
 * The tab caption is specified by {@link setCaption Caption}. If {@link setNavigateUrl NavigateUrl}
 * is set, the tab will contain a hyperlink pointing to the specified URL. In this case,
 * clicking on the tab will redirect the browser to the specified URL.
 *
 * TTabView may be toggled between visible (active) and invisible (inactive) by
 * setting the {@link setActive Active} property.
 *
 * @author Tomasz Wolny <tomasz.wolny@polecam.to.pl> and Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.1
 */
class TTabView extends \Prado\Web\UI\WebControls\TWebControl
{
	private $_active = false;

	/**
	 * @return the tag name for the view element
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
	 * @return string the caption displayed on this tab. Defaults to ''.
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption', '');
	}

	/**
	 * @param string $value the caption displayed on this tab
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
	 * If not empty, clicking on this tab will redirect the browser to the specified URL.
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
	 * @return bool whether this tab view is active. Defaults to false.
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param bool $value whether this tab view is active.
	 */
	public function setActive($value)
	{
		$this->_active = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Renders body contents of the tab view.
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
	 * Renders the tab associated with the tab view.
	 * @param THtmlWriter $writer the writer for rendering purpose.
	 */
	public function renderTab($writer)
	{
		if ($this->getVisible(false) && $this->getPage()->getClientSupportsJavaScript()) {
			$writer->addAttribute('id', $this->getClientID() . '_0');

			$style = $this->getActive() ? $this->getParent()->getActiveTabStyle() : $this->getParent()->getTabStyle();
			$style->addAttributesToRender($writer);

			$writer->renderBeginTag($this->getTagName());

			$this->renderTabContent($writer);

			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the content in the tab.
	 * By default, a hyperlink is displayed.
	 * @param THtmlWriter $writer the HTML writer
	 */
	protected function renderTabContent($writer)
	{
		if (($url = $this->getNavigateUrl()) === '') {
			$url = 'javascript://';
		}
		if (($caption = $this->getCaption()) === '') {
			$caption = '&nbsp;';
		}
		$writer->write("<a href=\"{$url}\">{$caption}</a>");
	}
}
