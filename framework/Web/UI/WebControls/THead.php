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
 * This component is used to provide access to the &lt;head&gt; HTML
 * element through prado code. You can access it via the
 *
 * 	<code>
 	$this->Page->Head
 	</code>
 * property.
 *
 * The THead component provides functionality that is also available through
 * the TPage component (it will remain in the TPage component for cases where a THead
 * component is not included on the page), including:
 *
 * - <b>registerScriptFile</b>
 * - <b>registerStyleFile</b>
 *
 * Additionally, there are additional methods
 *
 * - <b>registerScriptBlock</b>
 * 	 <br/>Register script to be output between &lt;script&gt; tags
 * - <b>registerStyleBlock</b>
 * 	 <br/>Register script to be output between &lt;style&gt; tags
 * - <b>registerMetaInfo</b>
 *   <br/>Register information to be output in a &lt;meta&gt; tag
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>Title</b>, string, kept in viewstate
 *   <br/>Gets or sets the &lt;title&gt; of the page
 *
 * Examples
 * - On a page template file, insert the following line to create a THead component,
 * <code>
 *   <com:THead Title="My Prado Page"/>
 * </code>
 * The checkbox will show "Agree" text on its right side. If the user makes any change
 * to the <b>Checked</b> state, the checkAgree() method of the page class will be invoked automatically.
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class THead extends TControl
{
	/**
	 * @var array list of javascript files to be loaded by {@link THead}
	 */
	private $_scriptFiles=array();
	/**
	 * @var array list of CSS style files to be loaded by {@link THead}
	 */
	private $_styleFiles=array();
	/**
	 * @var array list of meta name tags to be loaded by {@link THead}
	 */
	private $_metaTags=array();

	/**
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
		return $this->getPage()->getTitle();
	}

	/**
	 * @param string the page's title
	 */
	public function setTitle($value)
	{
		$this->getPage()->setTitle($value);
	}

	/**
	 * Registers a javascript file to be loaded in client side
	 * @param string a key that identifies the script file to avoid repetitive registration
	 * @param string the javascript file which can be relative or absolute URL
	 * @see isScriptFileRegistered()
	 */
	public function registerScriptFile($key,$scriptFile)
	{
		$this->_scriptFiles[$key] = $scriptFile;
	}

	/**
	 * Registers a CSS style file to be imported with the page body
	 * @param string a key that identifies the style file to avoid repetitive registration
	 * @param string the javascript file which can be relative or absolute URL
	 * @see isStyleFileRegistered()
	 */
	public function registerStyleFile($key,$styleFile)
	{
		$this->_styleFiles[$key] = $styleFile;
	}

	/**
	 * Registers a meta tag to be imported with the page body
	 * @param string a key that identifies the meta tag to avoid repetitive registration
	 * @param string the content of the meta tag
	 * @param string the language of the tag
	 * @see isTagRegistered()
	 */
	public function registerMetaTag($key,$metaTag)
	{
		$this->_metaTags[$key] = $metaTag;
	}

	/**
	 * Indicates whether the named scriptfile has been registered before.
	 * @param string the name of the scriptfile
	 * @return boolean
	 * @see registerScriptFile()
	 */
	public function isScriptFileRegistered($key)
	{
		return isset($this->_scriptFiles[$key]);
	}

	/**
	 * Indicates whether the named CSS style file has been registered before.
	 * @param string the name of the style file
	 * @return boolean
	 * @see registerStyleFile()
	 */
	public function isStyleFileRegistered($key)
	{
		return isset($this->_styleFiles[$key]);
	}

	/**
	 * Indicates whether the named meta tag has been registered before.
	 * @param string the name of tag
	 * @param string the lang of the tag
	 * @return boolean
	 * @see registerMetaTag()
	 */
	public function isMetaTagRegistered($key)
	{
		return isset($this->_metaTags[$key]);
	}

	/**
	 * Render the &lt;head&gt; tag
	 * @return the rendering result.
	 */
	public function render($writer)
	{
		$writer->renderBeginTag('head');
		$writer->writeLine();
		$writer->renderBeginTag('title');
		$writer->write($this->getPage()->getTitle());
		$writer->renderEndTag();
		$writer->writeLine();
		foreach($this->_metaTags as $metaTag)
		{
			$metaTag->render($writer);
			$writer->writeLine();
		}
		foreach($this->_scriptFiles as $scriptFile)
		{
		}
		foreach($this->_styleFiles as $styleFile)
		{
		}
		parent::render($writer);
		$writer->renderEndTag();
	}
}

class TMetaTag extends TComponent
{
	private $_id='';
	private $_httpEquiv='';
	private $_name='';
	private $_content='';
	private $_scheme='';

	public function getID()
	{
		return $this->_id;
	}

	public function setID($value)
	{
		$this->_id=$value;
	}

	public function getHttpEquiv()
	{
		return $this->_httpEquiv;
	}

	public function setHttpEquiv($value)
	{
		$this->_httpEquiv=$value;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setName($value)
	{
		$this->_name=$value;
	}

	public function getContent()
	{
		return $this->_content;
	}

	public function setContent($value)
	{
		$this->_content=$value;
	}

	public function getScheme()
	{
		return $this->_scheme;
	}

	public function setScheme($value)
	{
		$this->_scheme=$value;
	}

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