<?php
/**
 * NewCategory class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 */

/**
 * NewCategory class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
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

