<?php
/**
 * MyPost class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

/**
 * MyPost class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */
class MyPost extends BlogPage
{
	protected function bindData()
	{
		$author=$this->User->ID;
		$offset=$this->PostGrid->CurrentPageIndex*$this->PostGrid->PageSize;
		$limit=$this->PostGrid->PageSize;
		$this->PostGrid->DataSource=$this->DataAccess->queryPosts("author_id=$author",'','ORDER BY a.status DESC, create_time DESC',"LIMIT $offset,$limit");
		$this->PostGrid->VirtualItemCount=$this->DataAccess->queryPostCount("author_id=$author",'');
		$this->PostGrid->dataBind();
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
			$this->bindData();
	}

	public function changePage($sender,$param)
	{
		$this->PostGrid->CurrentPageIndex=$param->NewPageIndex;
		$this->bindData();
	}

	public function pagerCreated($sender,$param)
	{
		$param->Pager->Controls->insertAt(0,'Page: ');
	}
}

