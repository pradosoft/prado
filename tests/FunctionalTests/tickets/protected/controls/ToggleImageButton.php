<?php

/**
 *
 *
 * @author Christophe BOULAIN (Christophe.Boulain@ceram.fr)
 * @license url nameoflicense
 *
 */

class ToggleImageButton extends TImageButton
{
	public function getState()
	{
		return $this->getViewState('state', ToggleImageButtonState::Down);
	}

	public function setState($value)
	{
		$this->setViewState('state', TPropertyValue::ensureEnum($value, 'ToggleImageButtonState'));
	}

	public function toggleState()
	{
		$this->setState(($this->getState() === ToggleImageButtonState::Down) ? ToggleImageButtonState::Up : ToggleImageButtonState::Down);
	}

	public function onClick($param)
	{
		$this->toggleState();
		parent::onClick($param);
	}

	public function getImageUrl()
	{
		$img = ($this->getState() === ToggleImageButtonState::Down) ? 'down.gif' : 'up.gif';
		return $this->publishAsset($img, __CLASS__);
	}

	public function setImageUrl($url)
	{
		throw new TUnsupportedOperationException('ImageUrl property is read-only');
	}
}

enum ToggleImageButtonState: string
{
	case Down = 'Down';
	case Up = 'Up';
}
