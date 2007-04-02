<?php
Prado::using('System.Web.UI.ActiveControls.*');
class Ticket585 extends TPage
{

	public function ChkDate ($sender, $param)
	{
		if ($param->Value == "15-03-2007")
		{
			$param->IsValid=false;
		}
		else
			$param->IsValid=true;
	}

}

?>