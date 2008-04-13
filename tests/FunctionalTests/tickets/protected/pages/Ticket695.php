<?php
Prado::Using ('System.Web.UI.ActiveControls.*');
class Ticket695 extends TPage {
    public function onATB($sender,$param) {
        $this->X->Text=$this->X->Text+1;
    }
}
?>
