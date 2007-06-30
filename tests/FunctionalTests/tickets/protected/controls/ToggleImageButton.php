<?php

/**
 *
 *
 * @author Christophe BOULAIN (Christophe.Boulain@ceram.fr)
 * @copyright Copyright &copy; 2007, CERAM Sophia Antipolis
 * @license url nameoflicense
 * @version $Id$
 *
 */

class ToggleImageButton extends TImageButton {

	public function getState () {
		return $this->getViewState('state', ToggleImageButtonState::Down);
	}

	public function setState($value) {
		$this->setViewState('state', TPropertyValue::ensureEnum($value, 'ToggleImageButtonState'));
	}

	public function toggleState () {
		$this->setState(($this->getState()===ToggleImageButtonState::Down)?ToggleImageButtonState::Up:ToggleImageButtonState::Down);
	}

	public function onClick ($param) {
		$this->toggleState();
		parent::onClick($param);
	}

	public function getImageUrl () {
		$img=($this->getState()===ToggleImageButtonState::Down)?'down.gif':'up.gif';
		return $this->publishAsset($img,__CLASS__);
	}

	public function setImageUrl($url) {
		throw new TUnsupportedOperationException('ImageUrl property is read-only');
	}
}

class ToggleImageButtonState extends TEnumerable {
	const Down='Down';
	const Up='Up';
}

?>