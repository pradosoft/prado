<?php
/**
 * TTableHeaderCell class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TTableCell class
 */
Prado::using('System.Web.UI.WebControls.TTableCell');


/**
 * TTableHeaderCell class.
 *
 * TTableHeaderCell displays a table header cell on a Web page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableHeaderCell extends TTableCell
{
	/**
	 * @return string tag name for the table header cell
	 */
	protected function getTagName()
	{
		return 'th';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if(($scope=$this->getScope())!=='NotSet')
			$writer->addAttribute('scope',$scope==='Row'?'row':'col');
		if(($text=$this->getAbbreviatedText())!=='')
			$writer->addAttribute('abbr',$text);
		if(($text=$this->getCategoryText())!=='')
			$writer->addAttribute('axis',$text);
	}

	/**
	 * @return string the scope of the cells that the header cell applies to. Defaults to 'NotSet'.
	 */
	public function getScope()
	{
		return $this->getViewState('Scope','NotSet');
	}

	/**
	 * @param string the scope of the cells that the header cell applies to.
	 * Valid values include 'NotSet','Row','Column'.
	 */
	public function setScope($value)
	{
		$this->setViewState('Scope',TPropertyValue::ensureEnum($value,'NotSet','Row','Column'),'NotSet');
	}

	/**
	 * @return string  the abbr attribute of the HTML th element
	 */
	public function getAbbreviatedText()
	{
		return $this->getViewState('AbbreviatedText','');
	}

	/**
	 * @param string  the abbr attribute of the HTML th element
	 */
	public function setAbbreviatedText($value)
	{
		$this->setViewState('AbbreviatedText',$value,'');
	}

	/**
	 * @return string the axis attribute of the HTML th element
	 */
	public function getCategoryText()
	{
		return $this->getViewState('CategoryText','');
	}

	/**
	 * @param string the axis attribute of the HTML th element
	 */
	public function setCategoryText($value)
	{
		$this->setViewState('CategoryText',$value,'');
	}
}

?>