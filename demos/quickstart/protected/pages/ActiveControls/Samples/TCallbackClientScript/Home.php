<?php

// $Id: Home.php -1   $
class Home extends TPage
{
	public function buttonCallback ($sender, $param)
	{
		switch($this->radio1->SelectedValue)
		{
			case 1:
				$this->getCallbackClient()->evaluateScript("alert('something');");
				break;
			case 2:
				$this->getCallbackClient()->check($this->check1, !$this->check1->Checked);
				break;
			case 3:
				$this->getCallbackClient()->hide($this->label1);
				break;
			case 4:
				$this->getCallbackClient()->show($this->label1);
				break;
			case 5:
				$this->getCallbackClient()->toggle($this->label1);
				break;
			case 6:
				$this->getCallbackClient()->toggle($this->label1, 'fade');
				break;
			case 7:
				$this->getCallbackClient()->toggle($this->label1, 'slide');
				break;
			case 8:
				$this->getCallbackClient()->highlight($this->label1);
				break;
			case 9:
				$this->getCallbackClient()->focus($this->txt1);
				break;
			case 10:
				$this->getCallbackClient()->scrollTo($this->check1, array('duration' => 1000, 'offset' => 10));
				break;
			case 11:
				$this->getCallbackClient()->addCssClass($this->txt1, 'red_background');
				break;
			case 12:
				$this->getCallbackClient()->removeCssClass($this->txt1, 'red_background');
				break;
			case 13:
				$this->getCallbackClient()->jQuery($this->txt1, 'animate', array(
					array(	'width' => '+=100',
							'height' => '+=50'
						),
					array(
							'duration' => 1000,
						)
					));
				break;
			case 14:
				$this->getCallbackClient()->setAttribute($this->txt1, 'disabled', true);
				break;
			case 15:
				$this->getCallbackClient()->setStyle($this->pan1, array('background-color' => 'blue'));
				break;
			case 16:
				$this->getCallbackClient()->prependContent($this->pan1, 'prepend<br/>');
				$this->getCallbackClient()->appendContent($this->pan1, '<br/>append');
				break;
			case 17:
				$this->getCallbackClient()->insertContentBefore($this->pan1, 'before');
				$this->getCallbackClient()->insertContentAfter($this->pan1, 'after');
				break;
			case 18:
				$this->getCallbackClient()->replaceContent($this->pan1, 'No more Panel 1');
				break;
			case 19:
				$this->getCallbackClient()->remove($this->txt1);
				break;
			case 20:
				$this->getCallbackClient()->fadeOut($this->txt1);
				break;
			case 21:
				$this->getCallbackClient()->fadeIn($this->txt1);
				break;
			case 22:
				$this->getCallbackClient()->click($this->pan1);
				// alternative
				// $this->getCallbackClient()->raiseClientEvent($this->pan1, 'click');
				break;
			case 23:
				$this->getCallbackClient()->jQuery($this->txt1, 'toggle');
				break;

		}
	}
}

