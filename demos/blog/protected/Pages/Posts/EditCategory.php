<?php

class EditCategory extends BlogPage
{
	public function getCurrentCategory()
	{
		$id=TPropertyValue::ensureInteger($this->Request['id']);
		if(($cat=$this->DataAccess->queryCategoryByID($id))!==null)
			return $cat;
		else
			throw new BlogException('xxx');
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$catRecord=$this->getCurrentCategory();
			$this->CategoryName->Text=$catRecord->Name;
			$this->CategoryDescription->Text=$catRecord->Description;
		}
	}

	public function saveButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$categoryRecord=$this->getCurrentCategory();
			$categoryRecord->Name=$this->CategoryName->Text;
			$categoryRecord->Description=$this->CategoryDescription->Text;
			$this->DataAccess->updateCategory($categoryRecord);
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