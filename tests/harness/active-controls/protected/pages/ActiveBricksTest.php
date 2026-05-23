<?php

class ActiveBricksTest extends TPage
{
	public function insertBrick($sender, $param)
	{
		$x = Prado::createComponent("Application.pages.LTemplate");
		$x->Size = $sender->NamingContainer->findControl("MySize")->SelectedValue;
		$this->placeholder->getControls()->add($x);
		$this->getPage()->CallbackClient->insertContentAfter($this->AjaxInsertPoint, $x);
	}
}
