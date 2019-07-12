<?php
/**
 * THead class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\THttpUtility;

/**
 * THead class
 *
 * THead displays a head element on a page. It displays the content
 * enclosed in its body and the page title set by the
 * {@link setTitle Title} property. In addition, stylesheets and JavaScripts registered via
 * {@link TClientScriptManager::registerStyleSheet}, {@link TClientScriptManager::registerStyleSheetFile}
 * {@link TClientScriptManager::registerHeadJavaScript}, and
 * {@link TClientScriptManager::registerHeadJavaScriptFile} will also be displayed
 * in the head.
 * THead also manages and displays meta tags through its {@link getMetaTags MetaTags}
 * property. You can add a meta object to the collection in code dynamically,
 * or add it in template using the following syntax,
 * <code>
 * <com:THead>
 *   <com:TMetaTag HttpEquiv="Pragma" Content="no-cache" />
 *   <com:TMetaTag Name="keywords" Content="Prado" />
 * </com:THead>
 * </code>
 *
 * Note, {@link TPage} has a property {@link TPage::getHead Head} that refers to
 * the THead control currently on the page. A page can have at most one THead
 * control. Although not required, it is recommended to place a THead on your page.
 * Without a THead on the page, stylesheets and javascripts in the current page
 * theme will not be rendered.
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class THead extends \Prado\Web\UI\TControl
{
	/**
	 * @var TList list of meta name tags to be loaded by {@link THead}
	 */
	private $_metaTags;

	/**
	 * Registers the head control with the current page.
	 * This method is invoked when the control enters 'Init' stage.
	 * The method raises 'Init' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->setHead($this);
	}

	/**
	 * Processes an object that is created during parsing template.
	 * This method adds TMetaTag components into the {@link getMetaTags MetaTags}
	 * collection of the head control.
	 * @param string|TComponent $object text string or component parsed and instantiated in template
	 * @see createdOnTemplate
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TMetaTag) {
			$this->getMetaTags()->add($object);
		} else {
			parent::addParsedObject($object);
		}
	}

	/**
	 * @return string the page title.
	 */
	public function getTitle()
	{
		return $this->getViewState('Title', '');
	}

	/**
	 * Sets the page title.
	 * This title will be rendered only if the {@link TPage::getTitle Title} property
	 * of the page is empty.
	 * @param string $value the page title.
	 */
	public function setTitle($value)
	{
		$this->setViewState('Title', $value, '');
	}

	/**
	 * @return string base URL of the page. This URL is rendered as the 'href' attribute of <base> tag. Defaults to ''.
	 */
	public function getBaseUrl()
	{
		return $this->getViewState('BaseUrl', '');
	}

	/**
	 * @param string $url base URL of the page. This URL is rendered as the 'href' attribute of <base> tag.
	 */
	public function setBaseUrl($url)
	{
		$this->setViewState('BaseUrl', $url, '');
	}

	/**
	 * @return string the URL for the shortcut icon of the page. Defaults to ''.
	 */
	public function getShortcutIcon()
	{
		return $this->getViewState('ShortcutIcon', '');
	}

	/**
	 * @param string $url the URL for the shortcut icon of the page.
	 */
	public function setShortcutIcon($url)
	{
		$this->setViewState('ShortcutIcon', $url, '');
	}

	/**
	 * @return TMetaTagCollection meta tag collection
	 */
	public function getMetaTags()
	{
		if (($metaTags = $this->getViewState('MetaTags', null)) === null) {
			$metaTags = new TMetaTagCollection;
			$this->setViewState('MetaTags', $metaTags, null);
		}
		return $metaTags;
	}

	/**
	 * Renders the head control.
	 * @param THtmlWriter $writer the writer for rendering purpose.
	 */
	public function render($writer)
	{
		$page = $this->getPage();
		$title = $this->getTitle();
		$writer->write("<head>\n<title>" . THttpUtility::htmlEncode($title) . "</title>\n");
		if (($baseUrl = $this->getBaseUrl()) !== '') {
			$writer->write('<base href="' . $baseUrl . "\" />\n");
		}
		if (($icon = $this->getShortcutIcon()) !== '') {
			$writer->write('<link rel="shortcut icon" href="' . $icon . "\" />\n");
		}

		if (($metaTags = $this->getMetaTags()) !== null) {
			foreach ($metaTags as $metaTag) {
				$metaTag->render($writer);
				$writer->writeLine();
			}
		}
		$cs = $page->getClientScript();
		$cs->renderStyleSheetFiles($writer);
		$cs->renderStyleSheets($writer);
		if ($page->getClientSupportsJavaScript()) {
			$cs->renderHeadScriptFiles($writer);
			$cs->renderHeadScripts($writer);
		}
		parent::render($writer);
		$writer->write("</head>\n");
	}
}
