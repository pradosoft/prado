<?php

class Ticket500 extends TPage
{
	public function set_url()
	{
		$url = $this->Service->constructUrl('Cats.Buy.Browse', ['filter' => 'basket']);
		$this->link1->NavigateUrl = $url;
	}
}
