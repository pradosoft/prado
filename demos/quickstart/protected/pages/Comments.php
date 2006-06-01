<?php

Prado::using('System.I18N.*');

/**
 * Comments class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Sat May 27 20:23:00 AZOST 2006 $
 * @package Demo.Quickstart
 * @since 3.0
 */
class Comments extends TPage
{
	private $_quickstart;
	
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->_quickstart = new QuickStartComments;
		if(!$this->getIsPostBack())
			$this->refreshData();
	}
	
	protected function refreshData()
	{
		$this->comments->setDataSource($this->_quickstart->getQuequedComments());
		$this->comments->dataBind();		
	}
	
	public function approveComment($sender, $param)
	{
		$ID = $this->comments->DataKeys[$this->comments->SelectedItemIndex];
		$this->_quickstart->approveComment($ID);
		$this->refreshData();
		$this->comments->SelectedItemIndex=-1;
	}
	
	public function editComment($sender, $param)
	{
		$this->comments->SelectedItemIndex=-1;
		$this->comments->EditItemIndex=$param->Item->ItemIndex;
		$this->refreshData();	
	}
	
	public function cancelEdit($sender, $param)
	{
		$this->comments->SelectedItemIndex=-1;
		$this->comments->EditItemIndex=-1;
		$this->refreshData();
	}
	
	public function deleteComment($sender, $param)
	{
		$ID = $this->comments->DataKeys[$param->Item->ItemIndex];
		$this->_quickstart->deleteComment($ID);
		$this->comments->SelectedItemIndex=-1;
		$this->comments->EditItemIndex=-1;
		$this->refreshData();
	}
	
	public function updateComment($sender, $param)
	{
		$item=$param->Item;
		$this->_quickstart->updateComment(
			$this->comments->DataKeys[$item->ItemIndex],
			$item->page->Text,
			$item->email->Text,
			$item->content->Text);
			
		$this->comments->EditItemIndex=-1;
		$this->refreshData();
	}
}

?>