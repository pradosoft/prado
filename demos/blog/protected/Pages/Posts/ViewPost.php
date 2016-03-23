<?php
/**
 * ViewPost class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

/**
 * ViewPost class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class ViewPost extends BlogPage
{
	private $_post=null;

	public function onInit($param)
	{
		parent::onInit($param);
		$id=TPropertyValue::ensureInteger($this->Request['id']);
		$this->_post=$this->DataAccess->queryPostByID($id);
		if($this->_post===null)
			throw new BlogException(500,'post_id_invalid',$id);
		// if post is not published, only the author and admin can view it
		if($this->_post->Status!==PostRecord::STATUS_PUBLISHED && $this->_post->Status!==PostRecord::STATUS_STICKY && !$this->User->IsAdmin && $this->User->ID!==$this->_post->AuthorID)
			throw new BlogException(500,'post_view_disallowed',$id);
		$this->Title=htmlentities($this->_post->Title,ENT_QUOTES,'UTF-8');
	}

	public function getCanEditPost()
	{
		$user=$this->getUser();
		return $user->getIsAdmin() || $user->getID()===$this->_post->AuthorID;
	}

	public function getCurrentPost()
	{
		return $this->_post;
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->Status->Visible=$this->_post->Status!==PostRecord::STATUS_PUBLISHED && $this->_post->Status!==PostRecord::STATUS_STICKY;
		$this->CategoryList->DataSource=$this->DataAccess->queryCategoriesByPostID($this->_post->ID);
		$this->CategoryList->dataBind();
		$this->CommentList->DataSource=$this->DataAccess->queryCommentsByPostID($this->_post->ID);
		$this->CommentList->dataBind();
	}

	public function submitCommentButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$commentRecord=new CommentRecord;
			$commentRecord->PostID=$this->CurrentPost->ID;
			$commentRecord->AuthorName=$this->CommentAuthor->SafeText;
			$commentRecord->AuthorEmail=$this->CommentEmail->Text;
			$commentRecord->AuthorWebsite=$this->CommentWebsite->SafeText;
			$commentRecord->AuthorIP=$this->Request->UserHostAddress;
			$commentRecord->Content=$this->CommentContent->SafeText;
			$commentRecord->CreateTime=time();
			$commentRecord->Status=0;
			$this->DataAccess->insertComment($commentRecord);
			$this->Response->reload();
		}
	}

	public function deleteButtonClicked($sender,$param)
	{
		$this->DataAccess->deletePost($this->CurrentPost->ID);
		$this->gotoDefaultPage();
	}

	public function repeaterItemCommand($sender,$param)
	{
		$id=TPropertyValue::ensureInteger($param->CommandParameter);
		$this->DataAccess->deleteComment($id);
		$this->Response->reload();
	}
}

