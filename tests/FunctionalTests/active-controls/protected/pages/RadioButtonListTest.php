<?php

class RadioButtonListTest extends TPage
{
	public function radChange($sender,$param){
	  $choice = 'Choice : ';
	  switch($this->rad_button_list->SelectedValue){
	    case 'yes':
	      $choice.='Yes :-)';
	    break;
	    case 'no':
	      $choice.='No :-(';
	    break;
	    case 'whynot':
	      $choice.='Why not ???';
	    break;
	  }
	  $this->label->Text = $choice;
	}
	
	public function action($sender,$param){
	  $this->label->Text = 'Action...';
	}

}


?>