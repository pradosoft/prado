<?php
/**
 * NewCategory class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

/**
 * NewCategory class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */
class NewCategory extends BlogPage
{
	public function saveButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$categoryRecord=new CategoryRecord;
			$categoryRecord->Name=$this->CategoryName->Text;
			$categoryRecord->Description=$this->CategoryDescription->Text;
			$this->DataAccess->insertCategory($categoryRecord);
			$this->gotoPage('Posts.ListPost',array('cat'=>$categoryRecord->ID));
		}
	}

	public function checkCategoryName($sender,$param)
	{
		$name=$this->CategoryName->Text;
		$param->IsValid=$this->DataAccess->queryCategoryByName($name)===null;
	}
}

