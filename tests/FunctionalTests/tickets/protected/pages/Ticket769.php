<?php

Prado::Using ('System.Web.UI.ActiveControls.*');

class Ticket769 extends TPage
{
	public function showAcallback( $sender, $param ) 
	{
		$this->A->Visible = true;
		$this->B->Visible = false;
		$this->Main->render( $param->NewWriter );	
	}
	                                                                                                                           
	public function showBcallback( $sender, $param )
	{
		$this->A->Visible = false;
		$this->B->Visible = true;
		$this->Main->render( $param->NewWriter );
	}
                                                                                                                                
	public function clicked( $sender, $param )
	{
		$sender->Text = $sender->Text.' clicked';
	}
}
?>