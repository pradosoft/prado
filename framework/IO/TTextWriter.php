<?php

class TTextWriter extends TComponent implements ITextWriter
{
	private $_str='';

	public function flush()
	{
		$str=$this->_str;
		$this->_str='';
		return $str;
	}

	public function write($str)
	{
		$this->_str.=$str;
	}

	public function writeLine($str='')
	{
		$this->write($str."\n");
	}
}

?>