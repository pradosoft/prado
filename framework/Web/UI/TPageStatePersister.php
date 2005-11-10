<?php

abstract class TPageStatePersister extends TComponent
{
	private $_page;

	public function __construct($page)
	{
		$this->_page=$page;
	}

	public function getPage()
	{
		return $this->_page;
	}

	abstract public function load();

	abstract public function save($state);
}

?>