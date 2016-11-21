<?php
/**
 * NewPost class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

/**
 * NewPost class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */
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
			$postRecord->Title=$this->Title->SafeText;
			$postRecord->Content=$this->Content->SafeText;
			if($this->DraftMode->Checked)
				$postRecord->Status=PostRecord::STATUS_DRAFT;
			else if(!$this->User->IsAdmin && TPropertyValue::ensureBoolean($this->Application->Parameters['PostApproval']))
				$postRecord->Status=PostRecord::STATUS_PENDING;
			else
				$postRecord->Status=PostRecord::STATUS_PUBLISHED;
			$postRecord->CreateTime=time();
			$postRecord->ModifyTime=$postRecord->CreateTime;
			$postRecord->AuthorID=$this->User->ID;
			$cats=array();
			foreach($this->Categories->SelectedValues as $value)
				$cats[]=TPropertyValue::ensureInteger($value);
			$this->DataAccess->insertPost($postRecord,$cats);
			$this->gotoPage('Posts.ViewPost',array('id'=>$postRecord->ID));
		}
	}
}

