<?php

class Home extends TPage
{
	public function buttonClicked($sender,$param)
	{
		//echo $param->getPostBackValue();
		print_r($param);
	}
}

?>