<?php

Prado::using('System.I18N.*');

/**
 * CommentList class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Sat May 27 17:53:15 AZOST 2006 $
 * @package Demo.Quickstart.comments
 * @since 3.0
 */
class CommentList extends TTemplateControl
{
	private $_exclude = array(
		'Comments', 
		'Markdown',
		'Search',
		'GettingStarted.Introduction');

	private $_quickstart;
	
	public function onLoad($param)
	{
		parent::onLoad($param);
		
		$this->_quickstart = new QuickStartComments();
	
		$page = $this->getService()->getRequestedPagePath();

		$this->listComments($page);
	}
	
	protected function listComments($page)
	{
		$this->comments->setDataSource($this->_quickstart->getComments($page));
		$this->comments->dataBind();		
	}

	public function addComment_Clicked($sender, $param)
	{
		$page = $this->getService()->getRequestedPagePath();
		$this->_quickstart->addNewComment($page, 
			$this->email->getText(), $this->content->getText());
		$this->multiView1->setActiveViewIndex(1);
	}
	
	public function setVisible($value)
	{
		$page = $this->getService()->getRequestedPagePath();
		if(in_array($page, $this->_exclude))
			parent::setVisible(false);
		else
			parent::setVisible($value);
	}
}

?>