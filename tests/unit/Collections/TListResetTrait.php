<?php

namespace Prado\Test\Unit\Collections;


trait TListResetTrait 
{
	public function resetReadOnly($value)
	{
		$this->setReadOnly($value);
	}
}
