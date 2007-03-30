<?php

Prado::using('System.Web.UI.ActiveControls.*');
class Ticket500 extends TPage
{
	function set_url()
	{
		$url=$this->Service->constructUrl('Cats.Buy.Browse',array('filter' => 'basket'));
		$this->link1->NavigateUrl = $url;
	}
}

?>