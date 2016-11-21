<?php
/**
 * BlogPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

/**
 * BlogPage class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */
class BlogPage extends TPage
{
	public function onPreInit($param)
	{
		parent::onPreInit($param);
		$this->Theme=$this->Application->Parameters['ThemeName'];
	}

	public function getDataAccess()
	{
		return $this->getApplication()->getModule('data');
	}

	public function gotoDefaultPage()
	{
		$this->gotoPage($this->Service->DefaultPage);
	}

	public function gotoPage($pagePath,$getParameters=null)
	{
		$this->Response->redirect($this->Service->constructUrl($pagePath,$getParameters,false));
	}

	public function reportError($errorCode)
	{
		$this->gotoPage('ErrorReport',array('id'=>$errorCode));
	}
}

