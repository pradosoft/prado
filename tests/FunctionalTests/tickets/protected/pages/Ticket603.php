<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket603 extends TPage
{
	protected $_isHtml;

	public function onLoad($param) {
		parent::onLoad($param);
		$this->_isHtml = true;
	}

	public function switchContentTypeClicked( $sender, $param ) {
		$this->_isHtml = !$this->_isHtml;
		if ( $this->_isHtml ) {
			$this->EditHtmlTextBox->EnableVisualEdit = true;
			$this->EditHtmlTextBox->Text = '<b>somehtml</b>';
		} else {
			$this->EditHtmlTextBox->EnableVisualEdit = false;
			$this->EditHtmlTextBox->Text = 'plai bla bla';
		}
	}

	public function switchContentTypeCallback( $sender, $param ) {
		$this->ContentPanel->render( $param->NewWriter );
	}
}

?>