<?php

namespace Prado\Test\Unit;


class NewComponentNoListen extends NewComponent
{
	// this object does _not_ auto install global listeners during construction
	public function getAutoGlobalListen()
	{
		return false;
	}
}
