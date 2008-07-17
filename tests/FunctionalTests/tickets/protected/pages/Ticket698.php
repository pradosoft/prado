<?php

prado::using ('System.Web.UI.ActiveControls.*');

class Ticket698 extends TPage
{
	public function switchContentTypeClicked( $sender, $param ) {
		$this->EditHtmlTextBox->EnableVisualEdit = !$this->EditHtmlTextBox->EnableVisualEdit;
	}
	
	public function switchContentTypeCallback( $sender, $param ) {
		$this->ContentPanel->render( $param->NewWriter );	
	}
}
?>