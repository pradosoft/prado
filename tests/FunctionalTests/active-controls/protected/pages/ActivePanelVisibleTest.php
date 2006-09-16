<?php

class ActivePanelVisibleTest extends TPage
{
	public function showA()
	{
    	$this->pnlB->Visible = false;
    	$this->pnlA->Visible = true;
    }

    public function showB()
    {
    	$this->pnlB->Visible = true;
    	$this->pnlA->Visible = false;
    }
}

?>