<?php

class DMessagesPanel extends TTemplateControl
{
	private $_panelCssClass = '';

	public function onInit($param)
	{
		parent::onInit($param);
		$this->MessagesPanelEffect->Text = "";
	}

	public function setMessage($value)
	{
		$this->Message->Text = $value;
		if ($value != '') {
			$this->setVisible(true);
		} else {
			$this->setVisible(false);
		}
	}

	public function setVisible($value)
	{
		$this->ensureChildControls();
		if ($value === true) {
			echo "set visible";
			$this->MessagesPanel->Visible = true;
			$this->Message->Visible = true;
			$this->setEffect(null);
		} else {
			$this->MessagesPanel->Visible = false;
		}
	}

	public function setEffect($effect = null)
	{
		if ($effect !== null) {
			$text = "<script language=\"javascript\">\r\n";
			$text .= "// <![CDATA[\r\n";
			//$text .= "new Effect.$effect(\"" . $this->getPage()->DMessagesPanel->MessagesPanel->getClientID() . "\");\r\n";
			$text .= "new Effect.$effect(\"" . $this->getClientID() . "\");\r\n";
			$text .= "// ]]>\r\n";
			$text .= "</script>";
			$this->MessagesPanelEffect->Text = $text;
		} else {
			$this->MessagesPanelEffect->Text = '';
		}
	}

	public function setPanelCssClass($value)
	{
		$this->ensureChildControls();
		$this->MessagesPanel->CssClass = $value;
	}

	public function setMessageCssClass($value)
	{
		$this->ensureChildControls();
		$this->Message->CssClass = $value;
	}
}
