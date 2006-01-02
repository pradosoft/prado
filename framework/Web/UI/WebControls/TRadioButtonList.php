<?php

class TRadioButtonList extends TCheckBoxList
{
	protected function getIsMultiSelect()
	{
		return false;
	}

	protected function createRepeatedControl()
	{
		return new TRadioButton;
	}

	public function loadPostData($key,$values)
	{
		$value=isset($values[$key])?$values[$key]:'';
		$oldSelection=$this->getSelectedIndex();
		$this->ensureDataBound();
		foreach($this->getItems() as $index=>$item)
		{
			if($item->getEnabled() && $item->getValue()===$value)
			{
				if($index===$oldSelection)
					return false;
				else
				{
					$this->setSelectedIndex($index);
					return true;
				}
			}
		}
		return false;
	}
}

?>