<?php

use Prado\Web\UI\TPage;

/**
 * Harness page for the server event functional tests (#1408).
 *
 * The publish buttons publish an event through the `serverevents` broker module;
 * each event carries the page's publish count. ChangedWatch takes the `changed`
 * events of `orders`, AnyWatch every event of `orders`, and PanelWatch, inside
 * WatchPanel, the events of `panel`. "Remove panel watch" re-renders the panel
 * without PanelWatch.
 */
class ServerEventTest extends TPage
{
	private function publish(string $channel, string $event): void
	{
		$count = (int) $this->getViewState('Published', 0) + 1;
		$this->setViewState('Published', $count, 0);
		$this->getApplication()->getModule('serverevents')->publish($channel, $event, ['n' => $count]);
	}

	public function publishChanged($sender, $param)
	{
		$this->publish('orders', 'changed');
	}

	public function publishRemoved($sender, $param)
	{
		$this->publish('orders', 'removed');
	}

	public function publishPanel($sender, $param)
	{
		$this->publish('panel', 'ping');
	}

	public function changedEvent($sender, $param)
	{
		$event = $param->getCallbackParameter();
		$this->ChangedLabel->setText($event->Event . ' ' . $event->Data->n);
	}

	public function anyEvent($sender, $param)
	{
		$event = $param->getCallbackParameter();
		$this->AnyLabel->setText($event->Event . ' ' . $event->Data->n);
	}

	public function panelEvent($sender, $param)
	{
		$event = $param->getCallbackParameter();
		$this->PanelLabel->setText($event->Event . ' ' . $event->Data->n);
	}

	public function refreshPanel($sender, $param)
	{
		$this->WatchPanel->render($param->getNewWriter());
	}

	public function removeWatch($sender, $param)
	{
		$this->PanelWatch->setVisible(false);
		$this->WatchPanel->render($param->getNewWriter());
	}
}
