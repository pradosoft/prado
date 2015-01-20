<?php
/**
 * THead class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

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
			$writer->addAttribute('http-equiv',$this->_httpEquiv);
		if($this->_scheme!=='')
			$writer->addAttribute('scheme',$this->_scheme);
		$writer->addAttribute('content',$this->_content);
		$writer->renderBeginTag('meta');
		$writer->renderEndTag();
	}
}