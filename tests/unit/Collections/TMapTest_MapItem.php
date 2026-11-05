<?php

namespace Prado\Test\Unit\Collections;


class TMapTest_MapItem
{
	public $data = 'data';
	
	public function __construct($d = null)
	{
		if($d !== null)
			$this->data = $d;
	}
}
