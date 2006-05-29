<?php

class ViewPost extends BlogPage
{
	private $_postID=null;
	private $_post=null;

	public function getPostID()
	{
		if($this->_postID===null)
			$this->_postID=TPropertyValue::ensureInteger($this->Request['id']);
		return $this->_postID;
	}

	public function getCurrentPost()
	{
		if($this->_post===null)
		{
			if(($this->_post=$this->DataAccess->queryPostByID($this->getPostID()))===null)
				$this->reportError(BlogErrors::ERROR_POST_NOT_FOUND);
		}
		return $this->_post;
	}

	public function getCanEditPost()
	{
		$user=$this->getUser();
		$authorID=$this->getCurrentPost()->AuthorID;
		return $authorID===$user->getID() || $user->isInRole('admin');
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->CategoryList->DataSource=$this->DataAccess->queryCategoriesByPostID($this->getPostID());
		$this->CategoryList->dataBind();
		$this->CommentList->DataSource=$this->DataAccess->queryCommentsByPostID($this->getPostID());
		$this->CommentList->dataBind();
	}

	public function submitCommentButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$commentRecord=new CommentRecord;
			$commentRecord->PostID=$this->CurrentPost->ID;
			$commentRecord->AuthorName=$this->CommentAuthor->Text;
			$commentRecord->AuthorEmail=$this->CommentEmail->Text;
			$commentRecord->AuthorWebsite=$this->CommentWebsite->Text;
			$commentRecord->AuthorIP=$this->Request->UserHostAddress;
			$commentRecord->Content=$this->CommentContent->Text;
			$commentRecord->CreateTime=time();
			$commentRecord->Status=0;
			$this->DataAccess->insertComment($commentRecord);
			$this->Response->reload();
		}
	}

	public function deleteButtonClicked($sender,$param)
	{
		$this->DataAccess->deletePost($this->PostID);
		$this->gotoDefaultPage();
	}

	public function repeaterItemCommand($sender,$param)
	{
		$id=TPropertyValue::ensureInteger($param->CommandParameter);
		$this->DataAccess->deleteComment($id);
		$this->Response->reload();
	}
}

?>