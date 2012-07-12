<?php

// $Id: Home.php 1405 2006-09-10 01:03:56Z wei $
class Home extends TPage
{

	public function clickCell ($sender, $param)
	{
		$sender->Text .= "<br/>Clicked";
		$this->lblResult->Text='You clicked on cell #'.$param->SelectedCellIndex.' with id='.$sender->id;
		$sender->render($param->NewWriter);
	}

	public function clickRow ($sender, $param)
	{
		$sender->BackColor="yellow";
		$this->lblResult->Text='You clicked on row #'.$param->SelectedRowIndex.' with id='.$sender->id;
		$sender->render($param->NewWriter);
	}
}

?>