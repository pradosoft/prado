<?php

class PostLoadingTest extends TPage
{
	public function onInit($param)
	{
		parent::onInit($param);

		// Text Box
		$textBox=new TTextBox();
		$textBox->setVisible(false);
		$textBox->setID("MyTextBox");
		$this->panel1->getControls()->add($textBox);
		$this->registerObject("MyTextBox", $textBox);


		// Submit button
		$button=new TActiveButton();
		$button->setVisible(false);
		$button->setID("MyButton");
		$button->setText("Submit");
		$button->attachEventHandler("OnCallback", array($this, "clickedButton"));
		$this->panel1->getControls()->add($button);
		$this->registerObject("MyButton", $button);

	}


	function callback1_requested($sender, $param)
	{
		$this->MyTextBox->visible = true;
		$this->MyButton->ActiveControl->EnableUpdate=false;
		$this->MyButton->visible = true;
		$this->panel1->render($param->NewWriter);
	}

	function clickedButton($sender, $param)
	{
		$this->panel1->getControls()->add('Result is '.$this->MyTextBox->getText());
		$this->panel1->render($param->NewWriter);
		$this->Page->CallbackClient->Highlight('heading');
	}
}

?>