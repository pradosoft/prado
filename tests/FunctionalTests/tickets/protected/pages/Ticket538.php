<?php
// $Id: Home.php 1405 2006-09-10 01:03:56Z wei $
Prado::using('System.Web.UI.ActiveControls.*');
class Ticket538 extends TPage
{
	public function checkboxClicked($sender,$param)
	{
		$sender->Text= $sender->ClientID . " clicked";
	}

	public function checkboxCallback($sender, $param)
	{
		$sender->Text .= ' using callback';
	}

	public function readData()
	{
		$data=array(
		array('id'=>'001','name'=>'John','age'=>31),
		array('id'=>'002','name'=>'Mary','age'=>30),
		array('id'=>'003','name'=>'Cary','age'=>20),
		array('id'=>'004','name'=>'Kevin','age'=>65),
		array('id'=>'005','name'=>'Steven','age'=>10),
		array('id'=>'006','name'=>'Josh','age'=>23),
		array('id'=>'007','name'=>'Lary','age'=>54));
		return $data;
	}

	//--------------------------------------------------------------------
	//  TListBox
	//--------------------------------------------------------------------

	public function dataSelector2_Clicked($sender, $param)
	{
		$this->DataViewer2->DataTextField='name';
		$this->DataViewer2->Items->clear();
		foreach ($this->readData() as $index=>$person)
		{
			$item = new TListItem('G1: '.$person['name'].'=>'.$person['age'],$index);
			$item->Attributes->Group = 'test1';
			$this->DataViewer2->Items->add($item);
		}
		foreach ($this->readData() as $index=>$person)
		{
			$item2 = new TListItem('G2: '.$person['name'].'=>'.$person['age'],$index+100);
			$item2->Attributes->Group = 'test2';
			$this->DataViewer2->Items->add($item2);
		}
		$this->DataViewer2->dataBind();
	}

	public function selectBtn2_Clicked()
	{
		$text = '';
		foreach ($this->DataViewer2->SelectedIndices as $index)
		{
			$text .= '"'.$this->DataViewer2->Items[$index]->Attributes->Group.'", ';
		}
		$this->ALLog->setText($text);
	}

	//--------------------------------------------------------------------
	//  TActiveListBox
	//--------------------------------------------------------------------


	public function dataSelector_Clicked($sender, $param)
	{
		$this->DataViewer->DataTextField='name';
		$this->DataViewer->Items->clear();
		foreach ($this->readData() as $index=>$person)
		{
			$item = new TListItem('G1: '.$person['name'].'=>'.$person['age'],$index);
			$item->Attributes->Group = 'test1';
			$this->DataViewer->Items->add($item);
		}

		foreach ($this->readData() as $index=>$person)
		{
			$item2 = new TListItem('G2: '.$person['name'].'=>'.$person['age'],$index+100);
			$item2->Attributes->Group = 'test2';
			$this->DataViewer->Items->add($item2);
		}
		$this->DataViewer->dataBind();
	}

	public function selectBtn_Clicked()
	{
		$text = '';
		foreach ($this->DataViewer->SelectedIndices as $index)
		{
			if($this->DataViewer->Items[$index]->Attributes['Group'])
				$text .= $index .'- "'.$this->DataViewer->Items[$index]->Attributes->Group.'", ';
			else
				$text .= $index.',';
		}
		$this->ALLog->setText($text);
	}

}

?>