<?php
/**
 * BlogPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: BlogPage.php 1509 2006-11-25 20:51:43Z xue $
 */

/**
 * BlogPage class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 */
class BlogPage extends TPage
{
	public function onPreInit($param)
	{
		parent::onPreInit($param);
		//		$this->setTheme($this->getApplication()->Parameters['ThemeName']);
	}

	public function getDataAccess()
	{
		return $this->getApplication()->getModule('data');
	}

	public function gotoDefaultPage()
	{
		$this->gotoPage($this->getService()->DefaultPage);
	}

	public function gotoPage($pagePath,$getParameters=null)
	{
		$this->getResponse()->redirect($this->getService()->constructUrl($pagePath,$getParameters,false));
	}

	public function reportError($errorCode)
	{
		$this->gotoPage('ErrorReport',array('id'=>$errorCode));
	}
}

?>