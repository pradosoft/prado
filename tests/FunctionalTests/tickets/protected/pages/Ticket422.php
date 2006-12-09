<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket422 extends TPage
{
	private $_listItemsA;
	private $_listItemsB;

	public function onInit($param)
	{
		parent::onInit($param);
		$this->_listItemsA[] = array("title" => "Please select", "id" => -1,"category" => -1);
		$this->_listItemsA[] = array("title" => "Item A", "id" => 1,"category" => 1);
		$this->_listItemsA[] = array("title" => "Item B", "id" => 2,"category" => 1);
		$this->_listItemsA[] = array("title" => "Item C", "id" => 3,"category" => 1);
		$this->_listItemsA[] = array("title" => "Item D", "id" => 4,"category" => 1);
		$this->_listItemsA[] = array("title" => "Item E", "id" => 5,"category" => 1);

		$this->_listItemsB[] = array("title" => "List 2 Item A", "id" => 1,"category" => 1);
		$this->_listItemsB[] = array("title" => "List 2 Item B", "id" => 2,"category" => 1);
		$this->_listItemsB[] = array("title" => "List 2 Item C", "id" => 3,"category" => 1);
		$this->_listItemsB[] = array("title" => "List 2 Item D", "id" => 4,"category" => 2);
		$this->_listItemsB[] = array("title" => "List 2 Item E", "id" => 5,"category" => 2);
		$this->_listItemsB[] = array("title" => "List 2 Item F", "id" => 6,"category" => 2);
		$this->_listItemsB[] = array("title" => "List 2 Item G", "id" => 7,"category" => 3);
		$this->_listItemsB[] = array("title" => "List 2 Item H", "id" => 8,"category" => 3);
		$this->_listItemsB[] = array("title" => "List 2 Item I", "id" => 9,"category" => 3);
		$this->_listItemsB[] = array("title" => "List 2 Item J", "id" => 10,"category" => 4);
		$this->_listItemsB[] = array("title" => "List 2 Item K", "id" => 11,"category" => 4);
		$this->_listItemsB[] = array("title" => "List 2 Item L", "id" => 12,"category" => 4);

		$this->list1->DataValueField ='id';
		$this->list1->DataTextField = 'title';
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$this->list1->DataSource = $this->_listItemsA;
			$this->list1->dataBind();
		}
	}

	function list1_changed($sender)
	{
		if ($sender->getSelectedValue() == -1)
		{
			$this->label1->setText("Please select a category");
			$this->list2->setEnabled(false);
			return;
		}

		$this->addOptionsToListProblem($sender->getSelectedValue());
	}

	function addOptionsToListProblem($parent)
	{
		$foo = array();
		$bar = 0;
		$sel = array("title" => "Please select", "id" => -1,"category" => -1);
		$foo[] = $sel;
		foreach ($this->_listItemsB as $p)
		{
			if ($p["category"] == $parent)
			{
				$foo[] = $p;
			}
		}

		$this->list2->DataValueField = 'id';
		$this->list2->DataTextField = 'title';
		$this->list2->DataSource = $foo;
		$this->list2->dataBind();
		$this->list2->setEnabled(true);
	}

	function list2_changed($sender)
	{
		$this->label1->setText("Selection 2: ".$sender->getSelectedValue());
	}

	function select_index_3()
	{
		$this->list1->setSelectedIndex(3);
	}

	function clear_selections()
	{
		$this->list1->clearSelection();
	}

	function select_value_2()
	{
		$this->list1->setSelectedValue("value 2");
	}

	function select_index_3_plus()
	{
		$this->list1->setSelectedValue("value 3");
		$this->list1_changed($this->list1);
		$this->list2->setSelectedValue("value 3 - item 3");
	}
}

?>