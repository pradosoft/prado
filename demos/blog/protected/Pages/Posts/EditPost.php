<?php

class EditPost extends BlogPage
{
	public function getCurrentPost()
	{
		$id=TPropertyValue::ensureInteger($this->Request['id']);
		if(($post=$this->DataAccess->queryPostByID($id))!==null)
			return $post;
		else
			throw new BlogException('xxx');
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$postRecord=$this->getCurrentPost();
			$this->Title->Text=$postRecord->Title;
			$this->Content->Text=$postRecord->Content;
			$this->DraftMode->Checked=$postRecord->Status===0;
			$this->Categories->DataSource=$this->DataAccess->queryCategories();
			$this->Categories->dataBind();
			$cats=$this->DataAccess->queryCategoriesByPostID($postRecord->ID);
			$catIDs=array();
			foreach($cats as $cat)
				$catIDs[]=$cat->ID;
			$this->Categories->SelectedValues=$catIDs;
		}
	}

	public function saveButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$postRecord=$this->getCurrentPost();
			$postRecord->Title=$this->Title->Text;
			$postRecord->Content=$this->Content->Text;
			$postRecord->Status=$this->DraftMode->Checked?0:1;
			$postRecord->ModifyTime=time();
			$cats=array();
			foreach($this->Categories->SelectedValues as $value)
				$cats[]=TPropertyValue::ensureInteger($value);
			$this->DataAccess->updatePost($postRecord,$cats);
			$this->gotoPage('Posts.ViewPost',array('id'=>$postRecord->ID));
		}
	}
}

?>