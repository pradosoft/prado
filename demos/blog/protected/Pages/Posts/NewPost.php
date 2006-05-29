<?php

class NewPost extends BlogPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$this->Categories->DataSource=$this->DataAccess->queryCategories();
			$this->Categories->dataBind();
		}
	}

	public function saveButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$postRecord=new PostRecord;
			$postRecord->Title=$this->Title->Text;
			$postRecord->Content=$this->Content->Text;
			$postRecord->Status=$this->DraftMode->Checked?0:1;
			$postRecord->CreateTime=time();
			$postRecord->AuthorID=$this->User->ID;
			$cats=array();
			foreach($this->Categories->SelectedValues as $value)
				$cats[]=TPropertyValue::ensureInteger($value);
			$this->DataAccess->insertPost($postRecord,$cats);
			$this->gotoPage('Posts.ViewPost',array('id'=>$postRecord->ID));
		}
	}
}

?>