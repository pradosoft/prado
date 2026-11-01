<?php

/**
 * Harness page for the TActiveSvg functional tests.
 *
 * "Grow" raises the bars of the morphed chart and the replaced chart and renames
 * them. "Same" sets the same content and name. "Restyle" gives the chart a new
 * fill class, whose `<style>` reaches the page in the callback response. "Decorate" toggles Decorative. "Redraw"
 * re-renders the template-content graphic. Each callback increments "Callbacks".
 */
class ActiveSvgTest extends TPage
{
	public function getStep()
	{
		return $this->getViewState('Step', 0);
	}

	public function getRedraws()
	{
		return $this->getViewState('Redraws', 0);
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->getIsPostBack() && !$this->getIsCallback()) {
			$this->draw('a');
		}
	}

	/**
	 * Sets the chart content and names for the step and the fill class.
	 * @param string $fill the fill class, `a` or `b`
	 */
	protected function draw(string $fill)
	{
		$step = $this->getStep();
		$color = $fill === 'a' ? 'rgb(0, 0, 255)' : 'rgb(0, 128, 0)';
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 60">'
			. '<style>.' . $fill . '{fill:' . $color . '}</style>'
			. '<rect id="bar1" class="' . $fill . '" x="10" y="0" width="30" height="' . (20 + $step * 10) . '"/>'
			. '<rect id="bar2" class="' . $fill . '" x="60" y="0" width="30" height="' . (10 + $step * 10) . '"/>'
			. '</svg>';
		foreach ([$this->chart, $this->replaced] as $chart) {
			$chart->setSvg($svg);
			$chart->setAlternateText('Sales ' . $step);
		}
		$this->setViewState('Fill', $fill, 'a');
	}

	protected function counted()
	{
		$this->calls->setText($this->calls->getText() + 1);
	}

	public function grow($sender, $param)
	{
		$this->setViewState('Step', $this->getStep() + 1);
		$this->draw($this->getViewState('Fill', 'a'));
		$this->counted();
	}

	public function same($sender, $param)
	{
		$this->draw($this->getViewState('Fill', 'a'));
		$this->counted();
	}

	public function restyle($sender, $param)
	{
		$this->draw('b');
		$this->counted();
	}

	public function decorate($sender, $param)
	{
		$this->chart->setDecorative(!$this->chart->getDecorative());
		$this->counted();
	}

	public function redraw($sender, $param)
	{
		$this->setViewState('Redraws', $this->getRedraws() + 1);
		$this->drawn->render($param->getNewWriter());
		$this->counted();
	}
}
