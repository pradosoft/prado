<?php

class ActiveHiddenFieldTest extends TPage
{
    public function onSetValue($sender, $param)
    {
        $this->HiddenFieldEmpty->setValue('No longer empty');
        $this->ResponseLabel->setText($this->HiddenFieldEmpty->getValue());
    }
    
    public function onGetValue($sender, $param)
    {
        $this->ResponseLabel->setText($this->HiddenFieldUsed->getValue());
    }
    
    public function onGetBothValues($sender, $param)
    {
        $this->ResponseLabel->setText($this->HiddenFieldEmpty->getValue().'|'.$this->HiddenFieldUsed->getValue());
    }
}

?>