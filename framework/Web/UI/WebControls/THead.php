<?php
/**
 * THead class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * THead class
 *
 * THead displays a &lt;head&gt; element on a page. It displays the content
 * enclosed in its body. In addition, it displays the page title set by the
 * {@link setTitle Title} property, and the meta tags registered via
 * {@link registerMetaTag}. Stylesheet and JavaScripts registered via
 * {@link TClientScriptManager::registerStyleSheet}, {@link TClientScriptManager::registerStyleSheetFile}
 * {@link TClientScriptManager::registerHeadJavaScript}, and
 * {@link TClientScriptManager::registerHeadJavaScriptFile} will also be displayed
 * in the head.
 *
 * Note, {@link TPage} has a property {@link TPage::getHead Head} that refers to
 * the THead control currently on the page. A page can have at most once THead
 * control. Although not required, it is recommended to place a THead on your page.
 * Without a THead on the page, stylesheets and javascripts in the current page
 * theme will not be rendered.
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class THead extends TControl
{
	/**
	 * @var array list of meta name tags to be loaded by {@link THead}
	 */
	private $_metaTags=array();

	/**
	 * Registers the head control with the current page.
	 * This method is invoked when the control enters 'Init' stage.
	 * The method raises 'Init' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->setHead($this);
	}

	/**
	 * @return string the page title.
	 */
	public function getTitle()
	{
		return $this->getViewState('Title','');
	}

	/**
	 * Sets the page title.
	 * This title will be rendered only if the {@link TPage::getTitle Title} property
	 * of the page is empty.
	 * @param string the page title.
	 */
	public function setTitle($value)
	{
		$this->setViewState('Title',$value,'');
	}

	/**
	 * Registers a meta tag to be imported with the page body
	 * @param string a key that identifies the meta tag to avoid repetitive registration
	 * @param TMetaTag the meta tag to be registered
	 * @see isTagRegistered()
	 */
	public function registerMetaTag($key,$metaTag)
	{
		$this->_metaTags[$key]=$metaTag;
	}

	/**
	 * @param string a key identifying the meta tag.
	 * @return boolean whether the named meta tag has been registered before
	 * @see registerMetaTag()
	 */
	public function isMetaTagRegistered($key)
	{
		return isset($this->_metaTags[$key]);
	}

	/**
	 * Renders the head control.
	 * @param THtmlWriter the writer for rendering purpose.
	 */
	public function render($writer)
	{
		$page=$this->getPage();
		if(($title=$page->getTitle())==='')
			$title=$this->getTitle();
		$writer->write("<head>\n<title>".THttpUtility::htmlEncode($title)."</title>\n");
		foreach($this->_metaTags as $metaTag)
		{
			$metaTag->render($writer);
			$writer->writeLine();
		}
		$cs=$page->getClientScript();
		$cs->renderStyleSheetFiles($writer);
		$cs->renderStyleSheets($writer);
		$cs->renderScriptFiles($writer);
		//$cs->renderHeadScripts($writer);
		parent::render($writer);
		$writer->write("</head>\n");
	}
}

/**
 * TMetaTag class.
 *
 * TMetaTag represents a meta tag appearing in a page head section.
 * You can set its {@link setID ID}, {@link setHttpEquiv HttpEquiv},
 * {@link setName Name}, {@link setContent Content}, {@link setScheme Scheme}
 * properties, which correspond to id, http-equiv, name, content, and scheme
 * attributes for a meta tag, respectively.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TMetaTag extends TComponent
{
	/**
	 * @var string id of the meta tag
	 */
	private $_id='';
	/**
	 * @var string http-equiv attribute of the meta tag
	 */
	private $_httpEquiv='';
	/**
	 * @var string name attribute of the meta tag
	 */
	private $_name='';
	/**
	 * @var string content attribute of the meta tag
	 */
	private $_content='';
	/**
	 * @var string scheme attribute of the meta tag
	 */
	private $_scheme='';

	/**
	 * @return string id of the meta tag
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of the meta tag
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return string http-equiv attribute of the meta tag
	 */
	public function getHttpEquiv()
	{
		return $this->_httpEquiv;
	}

	/**
	 * @param string http-equiv attribute of the meta tag
	 */
	public function setHttpEquiv($value)
	{
		$this->_httpEquiv=$value;
	}

	/**
	 * @return string name attribute of the meta tag
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string name attribute of the meta tag
	 */
	public function setName($value)
	{
		$this->_name=$value;
	}

	/**
	 * @return string content attribute of the meta tag
	 */
	public function getContent()
	{
		return $this->_content;
	}

	/**
	 * @param string content attribute of the meta tag
	 */
	public function setContent($value)
	{
		$this->_content=$value;
	}

	/**
	 * @return string scheme attribute of the meta tag
	 */
	public function getScheme()
	{
		return $this->_scheme;
	}

	/**
	 * @param string scheme attribute of the meta tag
	 */
	public function setScheme($value)
	{
		$this->_scheme=$value;
	}

	/**
	 * Renders the meta tag.
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function render($writer)
	{
		if($this->_id!=='')
			$writer->addAttribute('id',$this->_id);
		if($this->_name!=='')
			$writer->addAttribute('name',$this->_name);
		if($this->_httpEquiv!=='')
			$writer->addAttribute('http-equiv',$this->_name);
		if($this->_scheme!=='')
			$writer->addAttribute('scheme',$this->_name);
		$writer->addAttribute('content',$this->_name);
		$writer->renderBeginTag('meta');
		$writer->renderEndTag();
	}
}

?>