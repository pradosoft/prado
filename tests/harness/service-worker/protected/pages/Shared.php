<?php

/**
 * Subscribes to the harness event stream through Prado.SharedConnection and
 * shows the received IDs and whether this tab leads.
 */
class Shared extends Prado\Web\UI\TPage
{
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$url = Prado\Web\Javascripts\TJavaScript::quoteString(dirname($this->getRequest()->getApplicationUrl()) . '/events.php');
		$cs = $this->getClientScript();
		$cs->registerPradoScript('sharedconnection');
		$cs->registerEndScript('shared', <<<JS
			window.received = [];
			window.subscription = Prado.SharedConnection.subscribe({
				key: 'harness-events',
				heartbeat: 500,
				open: Prado.SharedConnection.eventSource($url),
				onMessage(message) {
					window.received.push(Number(message.id));
					document.getElementById('log').textContent = window.received.join(',');
				},
			});
			setInterval(() => {
				document.getElementById('leader').textContent = window.subscription.isLeader() ? 'yes' : 'no';
			}, 100);
			JS);
	}
}
