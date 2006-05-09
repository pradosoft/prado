<?php

class SearchBox extends TTemplateControl 
{
	public function getText() 
	{
        $this->ensureChildControls();
        return $this->getRegisteredObject('search')->getText();
    }
    
    public function getTextBox()
    {
        $this->ensureChildControls();
        return $this->getRegisteredObject('search');
    }
	
	public function getButton()
	{
		$this->ensureChildControls();
        return $this->getRegisteredObject('find');
	}
	
	public function onInit($param)
	{
		parent::onInit($param);
		if(strlen($q = $this->Page->Request['q']) > 0)
			$this->search->setText($q);
	}

	public function doSearch($sender, $param)
	{
		if(strlen($query = $this->search->getText()) >0)
		{
			$ps = $this->getApplication()->getPageService();
			$page = $ps->constructUrl('Search', array('q' => $query));			
			$this->getApplication()->getResponse()->redirect($page);
		}
	}
}

?>