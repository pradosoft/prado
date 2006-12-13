<?php

class MyTabPanelTest extends TPage 
{
	private $panels = array('pnlContentsA', 'pnlContentsB', 'pnlContentsC', );

	private function showPanel($id, $param) 
	{
		foreach($this->panels as $panel) 
		{
			if($id == $panel) 
			{
				$this->$panel->setAttribute('style', 'display: block;');
				$this->$panel->setVisible(true);
				$this->$panel->render($param->NewWriter);
			} 
			else 
			{
				$this->$panel->setVisible(false);
			}
		}
	}

	public function onShowPanelA($sender, $param)
	{
		$this->showPanel('pnlContentsA', $param);
	}
	
	public function onShowPanelB($sender, $param) 
	{
		$this->showPanel('pnlContentsB', $param);
	}
	
	public function onShowPanelC($sender, $param) 
	{
		$this->showPanel('pnlContentsC', $param);
	} 
}



?>