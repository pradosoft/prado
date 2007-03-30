<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket504 extends TPage
{
	private $panels = array('panelA', 'panelB', 'panelC','panelD',);
	private function showPanel($id, $param) {
  		foreach($this->panels as $panel) {
  			if($id == $panel) {
  				$this->$panel->setAttribute('style', 'display: block;');
  				$this->$panel->render($param->NewWriter);
				$this->$panel->setVisible(true);
  			} else {
  				$this->$panel->setVisible(false);
  			}
  		}
  	}
	public function changePanel($sender,$param){
		$this->showPanel($param->CallbackParameter, $param);
	}
	public function loadData_Callback($sender, $param){
		die("parameter is ".$param->CallbackParameter);
	}
}

?>