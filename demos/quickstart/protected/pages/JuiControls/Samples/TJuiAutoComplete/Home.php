<?php

class Home extends TPage
{
    public function suggestNames($sender,$param) {
        // Get the token
        $token=$param->getToken();
        // Sender is the Suggestions repeater
        $sender->DataSource=$this->suggestionsForName($token);
        $sender->dataBind();
    }

    public function suggestionSelected1($sender,$param) {
        $id=$sender->Suggestions->DataKeys[ $param->selectedIndex ];
        $this->Selection1->Text='Selected ID: '.$id;
    }

    public function suggestionSelected2($sender,$param) {
        $id=$sender->Suggestions->DataKeys[ $param->selectedIndex ];
        $this->Selection2->Text='Selected ID: '.$id;
    }

    public function suggestionsForName($name) {
        $allChoices = array(
            array('id'=>1, 'name'=>'John'),
            array('id'=>2, 'name'=>'Paul'),
            array('id'=>3, 'name'=>'George'),
            array('id'=>4, 'name'=>'Ringo')
        );

		if($name) {
			return array_filter($allChoices, function ($el) use ($name) {
				return stripos($el['name'], $name) !== false;
			});
		} else
			return $allChoices;
    }

}