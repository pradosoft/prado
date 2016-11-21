<?php
/**
 * CategoryPortlet class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

Prado::using('Application.Portlets.Portlet');

/**
 * CategoryPortlet class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */
class CategoryPortlet extends Portlet
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$cats=$this->Application->getModule('data')->queryCategories();
		foreach($cats as $cat)
		{
			$cat->ID=$this->Service->constructUrl('Posts.ListPost',array('cat'=>$cat->ID));
			$cat->Name.=' (' . $cat->PostCount .')';
		}
		$this->CategoryList->DataSource=$cats;
		$this->CategoryList->dataBind();
	}
}

