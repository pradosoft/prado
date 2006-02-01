<?php

class Sample2 extends TPage
{
	protected function getData()
	{
		// We use viewstate keep track of data.
		// In real applications, data should come from database.
		if(($data=$this->getViewState('Data',null))===null)
		{
			$data=array(
				array('id'=>'ITN001','name'=>'Motherboard','quantity'=>1,'price'=>100.00),
				array('id'=>'ITN002','name'=>'CPU','quantity'=>1,'price'=>150.00),
				array('id'=>'ITN003','name'=>'Harddrive','quantity'=>2,'price'=>80.00),
				array('id'=>'ITN004','name'=>'Sound card','quantity'=>1,'price'=>40.00),
				array('id'=>'ITN005','name'=>'Video card','quantity'=>1,'price'=>150.00),
				array('id'=>'ITN006','name'=>'Keyboard','quantity'=>1,'price'=>20.00),
				array('id'=>'ITN007','name'=>'Monitor','quantity'=>2,'price'=>300.00),
			);
			$this->saveData($data);
		}
		return $data;
	}

	protected function saveData($data)
	{
		// In real applications, data should be saved to database.
		$this->setViewState('Data',$data);
	}

	function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$this->DataList->DataSource=$this->Data;
			$this->DataList->dataBind();
		}
	}

	function editItem($sender,$param)
	{
		$this->DataList->EditItemIndex=$param->Item->ItemIndex;
		$this->DataList->DataSource=$this->Data;
		$this->DataList->dataBind();
	}

	function cancelItem($sender,$param)
	{
		$this->DataList->SelectedItemIndex=-1;
		$this->DataList->EditItemIndex=-1;
		$this->DataList->DataSource=$this->Data;
		$this->DataList->dataBind();
	}

	function updateItem($sender,$param)
	{
		$item=$param->Item;
		$data=$this->Data;
		$product=&$data[$item->ItemIndex];
		$product['name']=$item->ProductName->Text;
		$product['price']=TPropertyValue::ensureFloat($item->ProductPrice->Text);
		$product['quantity']=TPropertyValue::ensureInteger($item->ProductQuantity->Text);
		$this->saveData($data);
		$this->DataList->EditItemIndex=-1;
		$this->DataList->DataSource=$data;
		$this->DataList->dataBind();
	}

	function deleteItem($sender,$param)
	{
		$data=$this->Data;
		array_splice($data,$param->Item->ItemIndex,1);
		$this->saveData($data);
		$this->DataList->SelectedItemIndex=-1;
		$this->DataList->EditItemIndex=-1;
		$this->DataList->DataSource=$data;
		$this->DataList->dataBind();
	}

	function selectItem($sender,$param)
	{
		$this->DataList->DataSource=$this->Data;
		$this->DataList->dataBind();
	}
}

?>