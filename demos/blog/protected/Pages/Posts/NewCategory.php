<?php

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

?>