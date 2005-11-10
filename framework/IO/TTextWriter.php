<?php

class TTextWriter extends TComponent
{
	public function flush()
	{
	}

	public function write($str)
	{
	}

	public function writeLine($str='')
	{
		$this->write($str."\n");
	}
}

?>