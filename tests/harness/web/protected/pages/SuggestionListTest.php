<?php

/**
 * Harness page for TSuggestionList and TActiveSuggestionList functional tests.
 *
 * The live list rebinds during the TActiveTextBox callback to the cities that
 * start with the typed text. The buttons change the active list and the active
 * text box's SuggestionList during a callback.
 */
class SuggestionListTest extends TPage
{
	private const CITIES = ['Paris', 'Prague', 'Porto', 'London', 'Lisbon', 'Rome'];

	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->getIsPostBack()) {
			$this->boundList->setDataSource(['Rome', 'Berlin', 'SF' => 'San Francisco']);
			$this->boundList->dataBind();
		}
	}

	public function live_changed($sender, $param)
	{
		$text = $this->live->getText();
		$matches = array_values(array_filter(self::CITIES, fn ($city) => $text !== '' && stripos($city, $text) === 0));
		$this->liveList->setDataSource($matches);
		$this->liveList->dataBind();
		$this->status->setText('matches: ' . count($matches));
	}

	public function add_item($sender, $param)
	{
		$this->liveList->getItems()->add(new \Prado\Web\UI\WebControls\TListItem('Madrid, Spain', 'Madrid'));
		$this->status->setText('added');
	}

	public function add_markup_item($sender, $param)
	{
		$this->liveList->getItems()->add(new \Prado\Web\UI\WebControls\TListItem('<img src=x onerror="window.__datalistXss=1">', 'markup'));
		$this->status->setText('markup added');
	}

	public function use_cities($sender, $param)
	{
		$this->live->setSuggestionList('cities');
		$this->status->setText('using cities');
	}

	public function detach_list($sender, $param)
	{
		$this->live->setSuggestionList('');
		$this->status->setText('detached');
	}

	public function submit($sender, $param)
	{
		$this->status->setText('submitted: ' . $this->city->getText());
	}
}
