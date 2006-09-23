<?php
class ActiveBricksTest extends TPage
{

	function insertBrick($sender, $param)
	{
		$x= Prado::createComponent("Application.pages.LTemplate");
		$x->Size = $sender->NamingContainer->findControl("MySize")->SelectedValue;
		$this->placeholder->getControls()->add($x);
		$this->Page->CallbackClient->insertContentAfter($this->AjaxInsertPoint, $x);
	}
}
?>