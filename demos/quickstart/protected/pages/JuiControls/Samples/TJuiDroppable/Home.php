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
		$this->label1->Text="Dropped ".$draggable->ID." at: <br/>Top=".$top." Left=".$left;
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
		$this->label2->Text="Dropped ".$draggable->ID." at: <br/>Top=".$top." Left=".$left;
	}
}
