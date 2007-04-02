<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Callback extends TPage
{
	function callback1_Requested()
	{
		var_dump("ok!");
	}
}

?>