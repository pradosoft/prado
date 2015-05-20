<?php

class Home extends TPage
{
	public function drop1_ondrop($sender, $param)
	{
		$draggable=$param->DraggableControl;
		/* Equals to:
		 * $draggable=$param->getControl($param->getCallbackParameter()->draggable);
		 */
		$offset=$param->getCallbackParameter()->offset;
		$target=$param->getCallbackParameter()->target->offset;
		$top=$offset->top - $target->top;
		$left=$offset->left - $target->left;
		$this->label1b->Text="Dropped ".$draggable->ID." at: <br/>Top=".$top." Left=".$left;
		$this->label3->Text="Dropped into yellow droppable!";
	}

	public function drop2_ondrop($sender, $param)
	{
		$draggable=$param->DraggableControl;
		/* Equals to:
		 * $draggable=$param->getControl($param->getCallbackParameter()->draggable);
		 */
		$offset=$param->getCallbackParameter()->offset;
		$target=$param->getCallbackParameter()->target->offset;
		$top=$offset->top - $target->top;
		$left=$offset->left - $target->left;
		$this->label2b->Text="Dropped ".$draggable->ID." at: <br/>Top=".$top." Left=".$left;
		$this->label3->Text="Dropped into red droppable!";
	}

	public function drop1_changed($sender, $param) {
	  if ($sender->getChecked()) {
	    $this->drop1->getOptions()->accept = '.drag-lime';
	    $this->label1a->Text="is accepting lime draggable";
	  }
	  else {
	    $this->drop1->getOptions()->accept = '';
	    $this->label1a->Text="is NOT accepting lime draggable";
	  }
	}

	public function drop2_changed($sender, $param) {
	  if ($sender->getChecked()) {
	    $this->drop2->getOptions()->activeClass = 'active';
	    $this->label2a->Text="is highlighted while dragging";
	  }
	  else {
	    $this->drop2->getOptions()->activeClass = '';
	    $this->label2a->Text="is NOT highlighted while dragging";
	  }
	}
}
