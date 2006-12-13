<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket413 extends TPage
{
	private $_data = array(
			array('id'=>'ITN001','name'=>'Motherboard','quantity'=>1,'price'=>100.00,'imported'=>true),
			array('id'=>'ITN002','name'=>'CPU','quantity'=>1,'price'=>150.00,'imported'=>true),
			array('id'=>'ITN003','name'=>'Harddrive','quantity'=>2,'price'=>80.00,'imported'=>true),
			array('id'=>'ITN004','name'=>'Sound card','quantity'=>1,'price'=>40.00,'imported'=>false));
	public function onLoad($param)
	{
		parent::onLoad($param);
		
		if (!$this->IsPostBack)
		{
			$this->locations_datagrid->setDataSource($this->_data);
			$this->locations_datagrid->dataBind();
		}

	}
   
	public function bla($sender, $param)
	{
		$sender->Text = 'a';
	}
	
}

?>