<?php

class ViewSource extends TPage
{
	protected function onLoad($param)
	{
		$pageName = $this->Request->getParameter("source");
		var_dump($pageName);
	}
}

?>