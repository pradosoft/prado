<?php

class Sample1 extends TPage
{
	protected function getDataSource()
	{
		return array(
			array('name'=>'John','age'=>'31'),
			array('name'=>'Bea','age'=>'35'),
			array('name'=>'Rose','age'=>'33'),
			array('name'=>'Diane','age'=>'37'),
			array('name'=>'Bob','age'=>'30'),
		);
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->DataList->DataSource=$this->getDataSource();
		$this->DataList->SelectedItemIndex=1;
		$this->DataList->dataBind();
	}
}

?>