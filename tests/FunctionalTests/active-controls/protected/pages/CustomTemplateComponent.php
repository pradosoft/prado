<?php

class CustomTemplateComponent extends TTemplateControl
{
	public function suboncallback ($sender, $param)
	{
		$sender->setText("Foo");
	}
}

?>