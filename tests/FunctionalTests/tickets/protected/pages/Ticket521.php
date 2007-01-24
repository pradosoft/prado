<?php

class Ticket521 extends TPage
{

  public function doOnClick($s, $p){
  	$this->label1->Text = "Button 1 was clicked ";
  }
  public function doSave($s, $p){
  	$this->label1->Text .= " on callback ";

  }


}
?>