<?php
/*
 * Created on 7/05/2006
 */

class AutoComplete extends TPage
{
	public function suggestEmails($sender, $param)
	{
		$words = array('hello', 'world');
		$sender->setDataSource($words);
		$sender->dataBind();
		$sender->render($param->getOutput());
	}
}

?>