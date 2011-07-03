<?php

class Home extends TPage
{
        public function onLoad($param)
        {
                parent::onLoad($param);
                $this->lab1->Text="";
        }

        public function executeTransaction($sender, $param)
        {
                $this->lab1->Text="executeTransaction ok";
        }
}

?>