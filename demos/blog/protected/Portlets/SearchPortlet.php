<?php
/**
 * SearchPortlet class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

Prado::using('Application.Portlets.Portlet');

/**
 * SearchPortlet class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class SearchPortlet extends Portlet
{
	public function onInit($param)
	{
		parent::onInit($param);
		if(!$this->Page->IsPostBack && ($keyword=$this->Request['keyword'])!==null)
			$this->Keyword->Text=$keyword;
	}

	public function search($sender,$param)
	{
		$keyword=$this->Keyword->Text;
		$url=$this->Service->constructUrl('SearchPost',array('keyword'=>$keyword),false);
		$this->Response->redirect($url);
	}
}

